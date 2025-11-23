<?php
    namespace App\Services;
    use App\Models\PhienChat;
    use App\Models\TinNhan;
    use function App\Core\getRedisConnection;

    class Sc_TuVan {
        // Khách hàng tạo phiên chat mới
        public function taoPhienChat(){
            $idKhachHang = $_SESSION['user']['id'];
            $idRapPhim = $_POST['id_rapphim'];
            $chuDe = $_POST['chu_de'];
            $phienChat = null;
            try {
                $phienChat = PhienChat::create([
                    'chu_de' => $chuDe,
                    'id_rapphim' => $idRapPhim,
                    'id_khachhang' => $idKhachHang,
                    'trang_thai' => 1 // Mới tạo, chờ nhân viên trả lời
                ]);
                // Tạo tin nhắn đầu tiên gửi đến khách hàng
                if($phienChat){
                    $noiDung = "Phiên chat mới đã được tạo. Vui lòng chờ nhân viên tư vấn.";
                    $phienChat->tinNhan()->create([
                        'noi_dung' => $noiDung,
                        'loai_noidung' => 1, // Text
                        'nguoi_gui' => null, // Hệ thống
                        'id_nhanvien' => null,
                        'trang_thai' => 1 //  (Khách hàng đã xem) vì khi tao phiên chat thì khách hàng đang ở trang chat
                    ]);
                    getRedisConnection()->publish('khach-hang-tao-phien-chat', json_encode($phienChat));
                    return $phienChat;
                }
                else{
                    throw new \Exception("Tạo phiên chat thất bại");
                }
            } catch (\Exception $e) {
                $phienChat?->delete();
                throw new \Exception($e->getMessage());
            }
        }
        
        // Khách hàng lấy danh sách phiên chat của mình
        public function khachHangLayDanhSachPhienChat(){
            $idKhachHang = $_SESSION['user']['id'];
            $danhSachPhienChat = PhienChat::where('id_khachhang', $idKhachHang)
                ->with(['rapphim', 'tinNhan' => function($query) {
                    $query->orderBy('created_at', 'desc')->limit(1);
                }])
                ->orderBy('updated_at', 'desc')
                ->get();
            
            // Thêm số tin nhắn chưa đọc cho từng phiên chat
            foreach ($danhSachPhienChat as $phienChat) {
                // Đếm tin nhắn chưa đọc (từ nhân viên hoặc hệ thống) trong phiên chat
                $phienChat->so_tin_nhan_chua_doc = TinNhan::where('id_phienchat', $phienChat->id)
                    ->whereIn('nguoi_gui', [2, null]) // 2: Nhân viên, null: Hệ thống
                    ->where('trang_thai', 0) // 0: Chưa đọc
                    ->count();
            }
            
            return $danhSachPhienChat;
        }
        
        // Khách hàng lấy danh sách tin nhắn trong phiên chat với phân trang
        public function layDanhSachTinNhanPhanTrang($idPhienChat, $page = 1, $perPage = 15, $lastMessageId = null) {
            // Kiểm tra quyền truy cập phiên chat
            $phienChat = PhienChat::where('id', $idPhienChat)
                ->first();
                
            if(!$phienChat){
                throw new \Exception("Phiên chat không tồn tại hoặc không thuộc về bạn");
            }

            // Tính tổng số tin nhắn
            $totalMessages = TinNhan::where('id_phienchat', $idPhienChat)->count();
            $totalPages = ceil($totalMessages / $perPage);

            // Xây dựng query cơ bản
            $query = TinNhan::where('id_phienchat', $idPhienChat);

            // Nếu có lastMessageId, lấy tin nhắn cũ hơn (cho infinite scroll)
            if ($lastMessageId) {
                $query->where('id', '<', $lastMessageId);
            }

            // Lấy tin nhắn theo thứ tự ngược (mới nhất trước) rồi đảo lại
            $messages = $query->with('nhanVien') // Load thông tin nhân viên từ quan hệ
                            ->orderBy('created_at', 'desc')
                            ->orderBy('id', 'desc')
                            ->limit($perPage)
                            ->get()
                            ->reverse()
                            ->values();
    
            // Thêm thông tin nhân viên vào mỗi tin nhắn
            foreach ($messages as $message) {
                // Nếu tin nhắn từ nhân viên, thêm thông tin tên nhân viên
                if ($message->nguoi_gui == 2 && $message->nhanVien) {
                    $message->ten_nhanvien = $message->nhanVien->ten; // Đúng tên field trong model
                    $message->ma_nhanvien = $message->nhanVien->id; // Sử dụng id thay vì ma_nhanvien
                } else {
                    // Đảm bảo là null cho tin nhắn từ khách hàng
                    $message->ten_nhanvien = null;
                    $message->ma_nhanvien = null;
                }
            }

            // Cập nhật trạng thái tin nhắn thành đã xem cho tin nhắn từ nhân viên
            if (!$lastMessageId) { // Chỉ cập nhật khi load lần đầu (không phải infinite scroll)
                if(isset($_SESSION['user'])){
                    // Khách hàng mở phiên chat
                    TinNhan::where('id_phienchat', $idPhienChat)
                        ->whereIn('nguoi_gui', [2, null]) // Nhân viên
                        ->where('trang_thai', 0) // Chưa xem
                        ->update(['trang_thai' => 1]); // Cập nhật thành đã xem
                    // Gửi sự kiện qua Redis
                    getRedisConnection()->publish('khach-hang-mo-phien-chat', json_encode([
                        'id' => $idPhienChat,
                        'id_khachhang' => $phienChat->id_khachhang
                    ]));
                }
                if(isset($_SESSION['UserInternal'])){
                    // Nhân viên mở phiên chat
                    TinNhan::where('id_phienchat', $idPhienChat)
                        ->where('nguoi_gui', 1) // Khách hàng
                        ->where('trang_thai', 0) // Chưa xem
                        ->update(['trang_thai' => 1]); // Cập nhật thành đã xem
                    $phienChat->dang_chat = 1; // Đang chat
                    $phienChat->save();
                    // Gửi sự kiện qua Redis
                    getRedisConnection()->publish('nhan-vien-mo-phien-chat', json_encode([
                        'id' => $idPhienChat,
                        'id_nhanvien' => $_SESSION['UserInternal']['ID']
                    ]));
                }
            }

            // Xác định thông tin phân trang
            $hasMore = $messages->count() == $perPage && 
                      ($lastMessageId ? 
                        TinNhan::where('id_phienchat', $idPhienChat)->where('id', '<', $messages->first()->id ?? 0)->exists() :
                        $totalMessages > $perPage);

            $oldestMessageId = $messages->first()->id ?? null;
            $newestMessageId = $messages->last()->id ?? null;

            return [
                'messages' => $messages,
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $totalMessages,
                'total_pages' => $totalPages,
                'has_more' => $hasMore,
                'oldest_message_id' => $oldestMessageId,
                'newest_message_id' => $newestMessageId
            ];
        }
        public function nhanVienLayDanhSachPhienChatPhanTrang($page = 1, $perPage = 10) {
            $idRapPhim = $_SESSION['UserInternal']['ID_RapPhim'];
            
            // Tính tổng số phiên chat để làm phân trang thủ công
            $totalSessions = PhienChat::where('id_rapphim', $idRapPhim)->count();
            $totalPages = ceil($totalSessions / $perPage);
            
            // Tính offset dựa trên trang hiện tại
            $offset = ($page - 1) * $perPage;
            
            // Lấy danh sách phiên chat theo trang
            $danhSachPhienChat = PhienChat::where('id_rapphim', $idRapPhim)
                ->with('khachhang')
                ->orderBy('updated_at', 'desc')
                ->skip($offset)
                ->take($perPage)
                ->get();

            // Lấy ID các phiên chat trong trang này
            $phienChatIds = $danhSachPhienChat->pluck('id')->all();

            // Lấy tin nhắn mới nhất cho từng phiên chat
            // Cách 1: Nếu cơ sở dữ liệu hỗ trợ ROW_NUMBER() (MySQL >= 8.0, SQL Server, PostgreSQL)
            try {
                $tinNhanMoiNhat = TinNhan::whereIn('id_phienchat', $phienChatIds)
                    ->selectRaw('*, ROW_NUMBER() OVER (PARTITION BY id_phienchat ORDER BY created_at DESC, id DESC) as rn')
                    ->having('rn', 1)
                    ->get()
                    ->groupBy('id_phienchat');
            } catch (\Exception $e) {
                // Cách 2: Fallback nếu DB không hỗ trợ window functions
                $tinNhanMoiNhat = [];
                
                // Lấy tin nhắn mới nhất cho từng phiên chat bằng cách thủ công
                foreach ($phienChatIds as $phienChatId) {
                    $lastMsg = TinNhan::where('id_phienchat', $phienChatId)
                        ->orderBy('created_at', 'desc')
                        ->orderBy('id', 'desc')
                        ->first();
                        
                    if ($lastMsg) {
                        $tinNhanMoiNhat[$phienChatId] = [$lastMsg];
                    }
                }
            }

            // Gắn tin nhắn mới nhất vào từng phiên chat
            foreach ($danhSachPhienChat as $phienChat) {
                $phienChat->tin_nhan_moi_nhat = $tinNhanMoiNhat[$phienChat->id][0] ?? null;
                
                // Đếm số tin nhắn từ khách hàng mà nhân viên chưa đọc
                $phienChat->so_tin_nhan_chua_doc = TinNhan::where('id_phienchat', $phienChat->id)
                    ->where('nguoi_gui', 1) // 1: Khách hàng
                    ->where('trang_thai', 0) // 0: Chưa đọc
                    ->count();
            }

            // Xác định có còn dữ liệu để load thêm không
            $hasMore = $totalSessions > ($offset + $perPage);
            
            // Trả về kết quả với thông tin phân trang tự tạo
            return [
                'data' => $danhSachPhienChat,
                'pagination' => [
                    'current_page' => (int)$page,
                    'per_page' => (int)$perPage,
                    'total' => $totalSessions,
                    'total_pages' => $totalPages,
                    'has_more' => $hasMore
                ]
            ];
        }
    }
?>