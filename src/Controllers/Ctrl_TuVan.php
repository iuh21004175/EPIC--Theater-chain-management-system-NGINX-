<?php
    namespace App\Controllers;
    use function App\Core\view;
    use App\Services\Sc_RapPhim;
    use App\Services\Sc_TuVan;
    class Ctrl_TuVan {
        public function pageChatTrucTuyen() {
            $scRapPhim = new Sc_RapPhim();
            $scRapPhim = $scRapPhim->doc();
            // Truyền dữ liệu rạp phim vào view nếu cần thiết
            return view('customer.chat-truc-tuyen', ['listRapPhim' => $scRapPhim]);
        }
        public function pageNhanVienTuVan() {
            return view('internal.tu-van');
        }
        public function khachHangDatLichGoiVideo() {
            return view('customer.dat-lich-goi-video');
        }
        public function khachHangLayDanhSachPhienChat(){
            $scTuVan = new Sc_TuVan();
            try {
                $danhSachPhienChat = $scTuVan->khachHangLayDanhSachPhienChat();
                return [
                    'success' => true,
                    'data' => $danhSachPhienChat
                ];
            } catch (\Exception $e) {
                // Xử lý lỗi nếu cần thiết
                return [
                    'success' => false,
                    'message' => 'Đã xảy ra lỗi: ' . $e->getMessage()
                ];
            }
            
        }
        public function khachHangTaoPhienChat(){
            $scTuVan = new Sc_TuVan();
            try {
                $phienChat = $scTuVan->taoPhienChat();
                return [
                    'success' => true,
                    'message' => 'Tạo phiên chat thành công',
                    'data' => $phienChat
                ];
            } catch (\Exception $e) {
                // Xử lý lỗi nếu cần thiết
                return [
                    'success' => false,
                    'message' => 'Đã xảy ra lỗi: ' . $e->getMessage()
                ];
            }
        }
        public function layChiTietPhienChat($argc){
            $idPhienChat = $argc['id'] ?? null;
            if(!$idPhienChat){
                return [
                    'success' => false,
                    'message' => 'Thiếu tham số id phiên chat'
                ];
            }
            
            // Lấy tham số phân trang từ query string
            $page = $_GET['page'] ?? 1;
            $perPage = $_GET['per_page'] ?? 15;
            $lastMessageId = $_GET['last_message_id'] ?? null;
            
            $scTuVan = new Sc_TuVan();
            try {
                $result = $scTuVan->layDanhSachTinNhanPhanTrang($idPhienChat, $page, $perPage, $lastMessageId);
                return [
                    'success' => true,
                    'data' => $result['messages'],
                    'pagination' => [
                        'current_page' => $result['current_page'],
                        'per_page' => $result['per_page'],
                        'total' => $result['total'],
                        'total_pages' => $result['total_pages'],
                        'has_more' => $result['has_more'],
                        'oldest_message_id' => $result['oldest_message_id'],
                        'newest_message_id' => $result['newest_message_id']
                    ]
                ];
            } catch (\Exception $e) {
                // Xử lý lỗi nếu cần thiết
                return [
                    'success' => false,
                    'message' => 'Đã xảy ra lỗi: ' . $e->getMessage()
                ];
            }
        }
        public function nhanVienLayDanhSachPhienChatPhanTrang() {
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;

            $scTuVan = new Sc_TuVan();
            try {
                $result = $scTuVan->nhanVienLayDanhSachPhienChatPhanTrang($page, $perPage);
                return [
                    'success' => true,
                    'data' => $result['data'],
                    'pagination' => $result['pagination']
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Đã xảy ra lỗi: ' . $e->getMessage()
                ];
            }
        }
    }
?>