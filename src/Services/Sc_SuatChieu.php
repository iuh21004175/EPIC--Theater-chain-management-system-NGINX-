<?php
    namespace App\Services;
    use App\Models\SuatChieu;
    use App\Models\LogSuatChieu;
    use App\Models\Phim;
    use App\Models\KeHoachSuatChieu;
    use Carbon\Carbon;

    class Sc_SuatChieu {
        public function them(){
            $idPhim = $_POST['id_phim'] ?? '';
            $listPhongChieu = $_POST['list_phongChieu'] ?? '';
            if (!is_array($listPhongChieu)) {
                $listPhongChieu = explode(',', $listPhongChieu);
            }
            $batDau = $_POST['batdau'] ?? '';
            $ketThuc = $_POST['ketthuc'] ?? '';

            $phim = Phim::find($idPhim);
            if(!$phim){
                throw new \Exception("Phim không tồn tại");
            }

            foreach ($listPhongChieu as $idPhongChieu) {
                $suatChieu = SuatChieu::create([
                    'id_phim' => $idPhim,
                    'id_phongchieu' => $idPhongChieu,
                    'batdau' => $batDau,
                    'ketthuc' => $ketThuc,
                    'tinh_trang' => 0 // Mặc định là "Chờ duyệt"
                ]);
                // Load relationships để lấy thông tin
                $suatChieu->load(['phim', 'phongChieu.rapChieuPhim']);
                
                $suatChieu->logSuatChieu()->create([
                    'hanh_dong' => 0, // Tạo mới
                    'id_phim' => $suatChieu->phim->id ?? null,
                    'ten_phim' => $suatChieu->phim->ten_phim ?? null,
                    'batdau' => $suatChieu->batdau,
                    'tinh_trang' => 0, // Chờ duyệt
                    'ten_rap' => $suatChieu->phongChieu->rapChieuPhim->ten ?? 'Không rõ',
                    'ten_phong' => $suatChieu->phongChieu->ten_phongchieu ?? 'Không rõ',
                    'da_xem' => 0, // Đánh dấu quản lý chuỗi rạp chưa xem
                    'rap_da_xem' => 1 // Đánh dấu rạp đã xem
                ]);
            }
        }
        public function sua($id){
            $idRapPhim = $_SESSION['UserInternal']['ID_RapPhim'];
            $suatChieu = SuatChieu::with('phim', 'phongChieu')
                ->whereHas('phongChieu', function($query) use ($idRapPhim) {
                    $query->where('id_rapphim', $idRapPhim);
                })
                ->where('id', $id)
                ->first();
            if (!$suatChieu) {
                throw new \Exception("Suất chiếu không tồn tại");
                exit();
            }
            $data = file_get_contents('php://input');
            $json = json_decode($data, true);
            if (isset($json['id_phim'])) {
                $phim = Phim::find($json['id_phim']);
                if (!$phim) {
                    throw new \Exception("Phim không tồn tại");
                    exit();
                }
                $suatChieu->id_phim = $json['id_phim'];
                $suatChieu->id_phongchieu = $json['id_phongChieu'] ;
                $suatChieu->batdau = $json['batdau'];
                $suatChieu->ketthuc = $json['ketthuc'];
                if($suatChieu->tinh_trang == 0){ //  thì nguyên trạng thí chờ duyệt
                    $suatChieu->tinh_trang = 0; // Đặt lại trạng thái về chờ duyệt nếu đã duyệt hoặc từ chối trước đó
                }
                // Lưu trạng thái ban đầu để ghi log chính xác
                $tinhTrangBanDau = $suatChieu->tinh_trang;
                
                if($suatChieu->tinh_trang == 2){// Nếu từ chối
                    $suatChieu->tinh_trang = 0; // Đưa về trạng thái chờ duyệt (thay vì chờ duyệt lại)
                    $suatChieu->da_xem = 0; // Đánh dấu quản lý chuỗi rạp chưa xem lại
                }
                
                // Ghi log với thông tin đầy đủ về trạng thái trước và sau khi sửa
                $suatChieu->logSuatChieu()->create([
                    'hanh_dong' => 1, // Cập nhật
                    'id_phim' => $suatChieu->phim->id ?? null,
                    'ten_phim' => $suatChieu->phim->ten_phim ?? null,
                    'batdau' => $suatChieu->batdau,
                    'tinh_trang' => $suatChieu->tinh_trang, // Trạng thái sau khi sửa
                    'ten_rap' => $suatChieu->phongChieu->rapChieuPhim->ten ?? 'Không rõ',
                    'ten_phong' => $suatChieu->phongChieu->ten_phongchieu ?? 'Không rõ',
                    'da_xem' => 0, // Đánh dấu quản lý chuỗi rạp chưa xem
                    'rap_da_xem' => 1, // Đánh dấu rạp đã xem
                ]);
                $suatChieu->save();
            }
        }
        public function xoa($id){
            $suatChieu = SuatChieu::with('phim')->find($id);

            if (!$suatChieu) {
                throw new \Exception("Suất chiếu không tồn tại");
                exit();
            }

            // Load relationships để lấy thông tin trước khi xóa
            $suatChieu->load(['phim', 'phongChieu.rapChieuPhim']);
            
            // Lưu log trước khi xóa
            $suatChieu->logSuatChieu()->create([
                'hanh_dong' => 2, // Xóa
                'id_phim' => $suatChieu->phim->id ?? null,
                'ten_phim' => $suatChieu->phim->ten_phim ?? null,
                'batdau' => $suatChieu->batdau,
                'tinh_trang' => $suatChieu->tinh_trang,
                'ten_rap' => $suatChieu->phongChieu->rapChieuPhim->ten ?? 'Không rõ',
                'ten_phong' => $suatChieu->phongChieu->ten_phongchieu ?? 'Không rõ',
                'da_xem' => 0, // Đánh dấu quản lý chuỗi rạp chưa xem
                'rap_da_xem' => 1 // Đánh dấu rạp đã xem
            ]);

            $suatChieu->delete();
        }
        
        /**
         * Hoàn tác suất chiếu: Xóa suất chiếu và cập nhật trạng thái trong kế hoạch về chờ duyệt
         * Giới hạn: 
         * - Chỉ cho phép hoàn tác nếu suất chiếu chưa bắt đầu và còn cách ít nhất 2 giờ
         * - Không cho phép hoàn tác nếu đã có vé được đặt hoặc đang giữ chỗ
         */
        public function hoanTac($id) {
            $suatChieu = SuatChieu::with(['phim', 'phongChieu'])->find($id);

            if (!$suatChieu) {
                throw new \Exception("Suất chiếu không tồn tại");
            }

            // Kiểm tra xem có vé nào đã được đặt hoặc đang giữ chỗ không
            $soVeDaDat = \App\Models\Ve::where('suat_chieu_id', $id)
                ->whereIn('trang_thai', [1, 2]) // 1: Giữ chỗ, 2: Đã đặt
                ->count();
            
            if ($soVeDaDat > 0) {
                throw new \Exception("Không thể hoàn tác suất chiếu đã có vé được đặt hoặc đang giữ chỗ. Vui lòng hủy vé trước khi hoàn tác.");
            }

            // Kiểm tra giới hạn thời gian: chỉ cho phép hoàn tác nếu còn cách ít nhất 2 giờ trước khi suất chiếu bắt đầu
            $batDauCarbon = Carbon::parse($suatChieu->batdau);
            $now = Carbon::now();
            
            // Nếu suất chiếu đã bắt đầu thì không cho phép hoàn tác
            if ($batDauCarbon <= $now) {
                throw new \Exception("Không thể hoàn tác suất chiếu đã bắt đầu hoặc đã kết thúc");
            }
            
            // Kiểm tra xem còn ít nhất 2 giờ trước khi suất chiếu bắt đầu
            $thoiGianConLai = $now->diffInHours($batDauCarbon, false); // Số giờ còn lại
            if ($thoiGianConLai < 2) {
                throw new \Exception("Chỉ có thể hoàn tác suất chiếu khi còn ít nhất 2 giờ trước khi suất chiếu bắt đầu");
            }

            // Lưu thông tin trước khi xóa
            $idPhim = $suatChieu->id_phim;
            $idPhongChieu = $suatChieu->id_phongchieu;
            $batDau = $suatChieu->batdau;
            $ketThuc = $suatChieu->ketthuc;

            // Tìm suất chiếu tương ứng trong kế hoạch
            $keHoachChiTiet = \App\Models\KeHoachChiTiet::where('id_phim', $idPhim)
                ->where('id_phongchieu', $idPhongChieu)
                ->where('batdau', $batDau)
                ->where('ketthuc', $ketThuc)
                ->first();

            // Load relationships để lấy thông tin trước khi xóa
            $suatChieu->load(['phim', 'phongChieu.rapChieuPhim']);
            
            // Xác định loại hoàn tác dựa trên trạng thái hiện tại
            $tinhTrangTruocKhiHoanTac = $suatChieu->tinh_trang;
            $hanhDongLog = 2; // Mặc định là Xóa
            // Nếu suất chiếu đã được duyệt (tinh_trang = 1), đây là hoàn tác duyệt
            // Nếu suất chiếu bị từ chối (tinh_trang = 2), đây là hoàn tác từ chối
            // Hiện tại dùng chung hanh_dong = 2 (Xóa/Hoàn tác) vì cả hai đều xóa suất chiếu
            
            // Lưu log trước khi xóa - ghi rõ trạng thái trước khi hoàn tác
            $suatChieu->logSuatChieu()->create([
                'hanh_dong' => $hanhDongLog, // Xóa/Hoàn tác
                'id_phim' => $suatChieu->phim->id ?? null,
                'ten_phim' => $suatChieu->phim->ten_phim ?? null,
                'batdau' => $suatChieu->batdau,
                'tinh_trang' => $tinhTrangTruocKhiHoanTac, // Lưu trạng thái trước khi hoàn tác để biết là hoàn tác duyệt hay từ chối
                'ten_rap' => $suatChieu->phongChieu->rapChieuPhim->ten ?? 'Không rõ',
                'ten_phong' => $suatChieu->phongChieu->ten_phongchieu ?? 'Không rõ',
                'da_xem' => 0, // Đánh dấu quản lý chuỗi rạp chưa xem
                'rap_da_xem' => 1 // Đánh dấu rạp đã xem
            ]);

            // Xóa suất chiếu
            $suatChieu->delete();

            // Cập nhật trạng thái trong kế hoạch về chờ duyệt (0) nếu tìm thấy
            if ($keHoachChiTiet) {
                $keHoachChiTiet->update([
                    'tinh_trang' => 0 // Chờ duyệt
                ]);
            }
        }
        
        public function taoKhungGioGoiY($ngay, $id_phong_chieu, $thoi_luong_phim)
        {
            // --- BƯỚC 1: ĐỊNH NGHĨA CÁC QUY TẮC ---
            $gio_mo_cua = Carbon::parse($ngay)->setTime(8, 0, 0);
            $gio_dong_cua_cuoi = Carbon::parse($ngay)->setTime(22, 0, 0); // Suất chiếu cuối cùng không được bắt đầu sau giờ này
            $khoang_cach_giua_suat = 30; // 30 phút buffer trước và sau mỗi suất chiếu
            $buoc_nhay_goi_y = 15; // Mỗi gợi ý cách nhau 15 phút

            // --- BƯỚC 2: TẠO "VÙNG CẤM" DỰA TRÊN LỊCH CHIẾU HIỆN TẠI ---
            $vungCam = [];
            $cacSuatChieuDaCo = SuatChieu::where('id_phongchieu', $id_phong_chieu)
                                        ->whereDate('batdau', $ngay)
                                        ->get();

            foreach ($cacSuatChieuDaCo as $suatChieu) {
                $batDauThucTe = Carbon::parse($suatChieu->batdau);
                $ketThucThucTe = Carbon::parse($suatChieu->ketthuc);

                $vungCam[] = [
                    'batdau' => $batDauThucTe->copy()->subMinutes($khoang_cach_giua_suat),
                    'ketthuc' => $ketThucThucTe->copy()->addMinutes($khoang_cach_giua_suat),
                ];
            }

            // --- BƯỚC 3: TẠO VÀ KIỂM TRA CÁC KHUNG GIỜ TIỀM NĂNG ---
            $khungGioGoiY = [];
            $gioKiemTra = $gio_mo_cua->copy();

            while ($gioKiemTra <= $gio_dong_cua_cuoi) {
                $gioKetThucTiemNang = $gioKiemTra->copy()->addMinutes($thoi_luong_phim);
                $isAvailable = true;

                // Kiểm tra xem khung giờ tiềm năng [bắt đầu, kết thúc] có bị chồng lấn với "vùng cấm" nào không
                foreach ($vungCam as $cam) {
                    if ($gioKiemTra < $cam['ketthuc'] && $gioKetThucTiemNang > $cam['batdau']) {
                        $isAvailable = false;
                        break; // Nếu đã trùng, không cần kiểm tra thêm
                    }
                }
                
                if ($isAvailable) {
                    $khungGioGoiY[] = $gioKiemTra->format('H:i');
                }

                // Tăng giờ kiểm tra lên cho lần lặp tiếp theo
                $gioKiemTra->addMinutes($buoc_nhay_goi_y);
            }

            return $khungGioGoiY;
        }
        public function kiemTraSuatChieu($batDau, $idPhongChieu, $thoiLuongPhim)
        {
            // --- BƯỚC 1: ĐỊNH NGHĨA LẠI CÁC QUY TẮC ---
            $khoang_cach_giua_suat = 30; // 30 phút buffer

            // Chuyển đổi đầu vào thành đối tượng Carbon để xử lý
            $gioBatDauMoi = Carbon::parse($batDau);
            $gioKetThucMoi = $gioBatDauMoi->copy()->addMinutes($thoiLuongPhim);
            $ngayChieu = $gioBatDauMoi->copy()->startOfDay();

            // --- BƯỚC 2: KIỂM TRA GIỜ HOẠT ĐỘNG ---
            $gio_mo_cua = $ngayChieu->copy()->setTime(8, 0, 0);
            $gio_dong_cua_cuoi = $ngayChieu->copy()->setTime(22, 0, 0);

            // Suất chiếu phải bắt đầu trong khung giờ cho phép
            if ($gioBatDauMoi < $gio_mo_cua || $gioBatDauMoi > $gio_dong_cua_cuoi) {
                throw new \Exception("Giờ bắt đầu suất chiếu phải trong khoảng từ 08:00 đến 22:00");
            }

            // --- BƯỚC 3: KIỂM TRA XUNG ĐỘT VỚI CÁC SUẤT CHIẾU KHÁC ---
            $cacSuatChieuDaCo = SuatChieu::where('id_phongchieu', $idPhongChieu)
                                        ->whereDate('batdau', $ngayChieu->toDateString())
                                        ->get();

            foreach ($cacSuatChieuDaCo as $suatChieu) {
                // Tạo "vùng cấm" cho mỗi suất chiếu đã có
                $vungCamBatDau = Carbon::parse($suatChieu->batdau)->subMinutes($khoang_cach_giua_suat);
                $vungCamKetThuc = Carbon::parse($suatChieu->ketthuc)->addMinutes($khoang_cach_giua_suat);

                // Kiểm tra sự chồng lấn
                // Nếu giờ bắt đầu của suất mới NẰM TRƯỚC giờ kết thúc của vùng cấm
                // VÀ giờ kết thúc của suất mới NẰM SAU giờ bắt đầu của vùng cấm
                // -> Xung đột!
                if ($gioBatDauMoi < $vungCamKetThuc && $gioKetThucMoi > $vungCamBatDau) {
                    throw new \Exception("Suất chiếu xung đột với lịch chiếu hiện tại");
                }
            }

            // Nếu vượt qua tất cả các kiểm tra, suất chiếu là hợp lệ
            return true;

        }
        public function duyetSuatChieu($id){
            $suatChieu = SuatChieu::find($id);
            if (!$suatChieu) {
                throw new \Exception("Suất chiếu không tồn tại");
                exit();
            }
            // Load relationships để lấy thông tin
            $suatChieu->load(['phim', 'phongChieu.rapChieuPhim']);
            
            $suatChieu->tinh_trang = 1; // Đã duyệt
            $suatChieu->logSuatChieu()->create([
                'hanh_dong' => 3, // Duyệt suất chiếu
                'id_phim' => $suatChieu->phim->id ?? null,
                'ten_phim' => $suatChieu->phim->ten_phim ?? null,
                'batdau' => $suatChieu->batdau,
                'tinh_trang' => 1, // Đã duyệt
                'ten_rap' => $suatChieu->phongChieu->rapChieuPhim->ten ?? 'Không rõ',
                'ten_phong' => $suatChieu->phongChieu->ten_phongchieu ?? 'Không rõ',
                'da_xem' => 1, // Đánh dấu quản lý chuỗi rạp đã xem
                'rap_da_xem' => 0 // Đánh dấu rạp chưa xem
            ]);
            $suatChieu->save();
        }
        public function tuChoiSuatChieu($id){
            $suatChieu = SuatChieu::find($id);
            if (!$suatChieu) {
                throw new \Exception("Suất chiếu không tồn tại");
                exit();
            }
            // Load relationships để lấy thông tin
            $suatChieu->load(['phim', 'phongChieu.rapChieuPhim']);
            
            $suatChieu->tinh_trang = 2; // Từ chối
            $suatChieu->logSuatChieu()->create([
                'hanh_dong' => 4, // Từ chối suất chiếu
                'id_phim' => $suatChieu->phim->id ?? null,
                'ten_phim' => $suatChieu->phim->ten_phim ?? null,
                'batdau' => $suatChieu->batdau,
                'tinh_trang' => 2, // Từ chối
                'ten_rap' => $suatChieu->phongChieu->rapChieuPhim->ten ?? 'Không rõ',
                'ten_phong' => $suatChieu->phongChieu->ten_phongchieu ?? 'Không rõ',
                'da_xem' => 1, // Đánh dấu quản lý chuỗi rạp đã xem
                'rap_da_xem' => 0 // Đánh dấu rạp chưa xem
            ]);
            $suatChieu->save();
        }
        public function doc($ngay){
            // Kiểm tra và chuyển đổi ngày nếu có dạng d/m/y sang y-m-d
            if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $ngay)) {
                $parts = explode('/', $ngay);
                // Đảm bảo đúng thứ tự: ngày/tháng/năm -> năm-tháng-ngày
                $ngay = $parts[2] . '-' . str_pad($parts[1], 2, '0', STR_PAD_LEFT) . '-' . str_pad($parts[0], 2, '0', STR_PAD_LEFT);
            }
            if(isset($_SESSION['UserInternal']['ID_RapPhim'])){
                $idRapPhim = $_SESSION['UserInternal']['ID_RapPhim'];
                $suatChieu = SuatChieu::with('phim', 'phongChieu')
                ->whereHas('phongChieu', function($query) use ($idRapPhim) {
                    $query->where('id_rapphim', $idRapPhim);
                })
                ->whereDate('batdau', $ngay)
                ->orderBy('batdau', 'desc')->get();
            }
            else{
                $idRapPhim = $_GET['id_rap'] ?? 0;
                if($idRapPhim == 0){
                    throw new \Exception("Thiếu ID rạp phim");
                }
                $suatChieu = SuatChieu::with('phim', 'phongChieu')
                ->whereHas('phongChieu', function($query) use ($idRapPhim) {
                    $query->where('id_rapphim', $idRapPhim);
                })
                ->whereDate('batdau', $ngay)
                ->orderBy('batdau', 'desc')->get();
            }
            
            // Thêm thông tin số vé đã đặt cho mỗi suất chiếu
            $suatChieu = $suatChieu->map(function($sc) {
                $soVeDaDat = \App\Models\Ve::where('suat_chieu_id', $sc->id)
                    ->whereIn('trang_thai', [1, 2]) // 1: Giữ chỗ, 2: Đã đặt
                    ->count();
                $sc->so_ve_da_dat = $soVeDaDat;
                return $sc;
            });
            
            return $suatChieu;
        }
        public function docSuatChieuChuaXem($idRapPhim){
            // Lấy suất chiếu chờ duyệt từ kế hoạch (KeHoachChiTiet)
            $keHoachChiTiet = \App\Models\KeHoachChiTiet::with(['phim', 'phongChieu'])
                ->whereHas('phongChieu', function($query) use ($idRapPhim) {
                    $query->where('id_rapphim', $idRapPhim);
                })
                ->where('tinh_trang', 0) // Chờ duyệt
                ->where('batdau', '>=', Carbon::now())
                ->orderBy('batdau', 'asc')
                ->get();
            
            // Chuyển đổi KeHoachChiTiet sang format giống SuatChieu để tương thích với frontend
            $suatChieu = $keHoachChiTiet->map(function($item) {
                return (object)[
                    'id' => $item->id,
                    'id_phim' => $item->id_phim,
                    'id_phongchieu' => $item->id_phongchieu,
                    'batdau' => $item->batdau,
                    'ketthuc' => $item->ketthuc,
                    'tinh_trang' => $item->tinh_trang,
                    'phim' => $item->phim,
                    'phongChieu' => $item->phongChieu
                ];
            });
            
            return $suatChieu;
        }
        public function tinhTrangSuatChieu($ngay, $idRapPhim){
            $ngayThuHai = Carbon::parse($ngay)->startOfWeek(Carbon::MONDAY)->toDateString();
            $ngayChuNhat = Carbon::parse($ngay)->endOfWeek(Carbon::SUNDAY)->toDateString();
            $suatChieu = SuatChieu::with('phim', 'phongChieu')
                ->whereHas('phongChieu', function($query) use ($idRapPhim) {
                    $query->where('id_rapphim', $idRapPhim);
                })
                ->whereBetween('batdau', [$ngayThuHai, $ngayChuNhat])
                ->orderBy('batdau', 'asc')->get();
            $tinhTrang = [
                'cho_duyet' => 0,
                'da_duyet' => 0,
                'tu_choi' => 0,
                'cho_duyet_lai' => 0
            ];
            foreach($suatChieu as $sc){
                if($sc->trang_thai == 0){
                    $tinhTrang['cho_duyet'] += 1;
                }
                elseif($sc->trang_thai == 1){
                    $tinhTrang['da_duyet'] += 1;
                }
                elseif($sc->trang_thai == 2){
                    $tinhTrang['tu_choi'] += 1;
                }
                elseif($sc->trang_thai == 3){
                    $tinhTrang['cho_duyet_lai'] += 1;
                }
            }
            return $tinhTrang;
        }      
        public function docSuatChieuKH($ngay = null, $idPhim, $idRap = null)
        {
            $query = SuatChieu::with(['phim', 'phongChieu.rapChieuPhim'])
                ->where('id_phim', $idPhim);

            if ($idRap) {
                $query->whereHas('phongChieu.rapChieuPhim', function($q) use ($idRap) {
                    $q->where('id', $idRap);
                });
            }

            if ($ngay) {
                try {
                    $ngayFormat = Carbon::parse($ngay)->toDateString();
                    $today = Carbon::today()->toDateString();

                    if ($ngayFormat === $today) {
                        $query->where('batdau', '>=', Carbon::now());
                    } elseif ($ngayFormat > $today) {
                        $query->whereDate('batdau', $ngayFormat);
                    } else {
                        return collect([]);
                    }
                } catch (\Exception $e) {
                    $query->where('batdau', '>=', Carbon::now());
                }
            } else {
                $query->where('batdau', '>=', Carbon::now());
            }

            return $query->orderBy('batdau', 'asc')->get();
        }

        public function docPhimTheoRap($ngay = null, $idRap = null) {
            $query = SuatChieu::with(['phim', 'phongChieu.rapChieuPhim'])
                ->whereHas('phongChieu', function ($q) use ($idRap) {
                    $q->where('id_rapphim', $idRap);
                });

            // Xử lý ngày
            $today = Carbon::today()->toDateString();
            if ($ngay) {
                try {
                    $ngayFormat = Carbon::parse($ngay)->toDateString();
                    if ($ngayFormat === $today) {
                        // Nếu là hôm nay, chỉ lấy suất chiếu còn trong tương lai
                        $query->where('batdau', '>=', Carbon::now());
                    } else {
                        // Ngày khác, lấy tất cả suất chiếu trong ngày
                        $query->whereDate('batdau', $ngayFormat);
                    }
                } catch (\Exception $e) {
                    // Nếu parse ngày lỗi, mặc định lấy từ hiện tại trở đi
                    $query->where('batdau', '>=', Carbon::now());
                }
            } else {
                // Không truyền ngày, lấy từ hiện tại trở đi
                $query->where('batdau', '>=', Carbon::now());
                $ngayFormat = $today;
            }

            // Lấy tất cả suất chiếu, sắp xếp theo thời gian bắt đầu
            $suatChieuList = $query->orderBy('batdau', 'asc')->get();

            // Lọc phim dựa trên **suất chiếu trong ngày** trước khi unique
            $phimList = $suatChieuList
            ->filter(fn($suat) => Carbon::parse($suat->batdau)->toDateString() === $ngayFormat)
            ->sortBy('batdau')      // <--- thêm dòng này
            ->pluck('phim')
            ->filter()
            ->unique('id')
            ->values();
            return $phimList;
        }
        public function docNhatKy($idRapPhim = null){
            // Ưu tiên lấy ID rạp từ session nếu user là quản lý rạp
            if (!$idRapPhim && isset($_SESSION['UserInternal']['ID_RapPhim'])) {
                $idRapPhim = $_SESSION['UserInternal']['ID_RapPhim'];
            }
            
            // Lấy ngày cách đây 7 ngày
            $bayNgayTruoc = Carbon::now()->subDays(7)->toDateString();

            // Sửa lại eager loading để đảm bảo load đầy đủ quan hệ
            $query = LogSuatChieu::with(['suatChieu' => function($q) {
                    $q->with(['phongChieu.rapChieuPhim']);
                }, 'phim'])
                ->whereDate('created_at', '>=', $bayNgayTruoc);

            // Nếu có ID rạp phim (Quản lý rạp) -> Chỉ lấy nhật ký của rạp đó
            if ($idRapPhim) {
                $query->whereHas('suatChieu.phongChieu', function($q) use ($idRapPhim) {
                    $q->where('id_rapphim', $idRapPhim);
                });
            }
            // Nếu không có ID rạp phim (Quản lý chuỗi rạp) -> Lấy tất cả nhật ký

            // Sắp xếp theo thời gian từ mới đến cũ (desc)
            $nhatKy = $query->orderBy('created_at', 'desc')->get();
            // Đảm bảo các trường cần thiết được set (cho các log cũ chưa có)
            $nhatKy = $nhatKy->map(function($log) {
                // Nếu log chưa có tinh_trang (log cũ), lấy từ suatChieu
                if ($log->tinh_trang === null && $log->suatChieu) {
                    $log->tinh_trang = $log->suatChieu->tinh_trang;
                } elseif ($log->tinh_trang === null) {
                    $log->tinh_trang = 0;
                }
                
                // Nếu log chưa có ten_phong (log cũ), lấy từ suatChieu
                if (empty($log->ten_phong) && $log->suatChieu) {
                    if (!$log->suatChieu->relationLoaded('phongChieu')) {
                        $log->suatChieu->load('phongChieu');
                    }
                    $log->ten_phong = $log->suatChieu->phongChieu->ten_phongchieu ?? 'Không rõ';
                } elseif (empty($log->ten_phong)) {
                    $log->ten_phong = 'Không rõ';
                }
                
                // Nếu log chưa có ten_rap (log cũ), lấy từ suatChieu
                if (empty($log->ten_rap) && $log->suatChieu) {
                    if (!$log->suatChieu->relationLoaded('phongChieu.rapChieuPhim')) {
                        $log->suatChieu->load(['phongChieu.rapChieuPhim']);
                    }
                    $log->ten_rap = $log->suatChieu->phongChieu->rapChieuPhim->ten ?? 'Không rõ';
                } elseif (empty($log->ten_rap)) {
                    $log->ten_rap = 'Không rõ';
                }
                
                return $log;
            });

            return $nhatKy;
        }
        public function danhDauDaXem($idRapPhim){ // Đánh dấu quản lý chuỗi rạp đã xem
            $nhatKy = LogSuatChieu::whereHas('suatChieu.phongChieu', function($q) use ($idRapPhim) {
                $q->where('id_rapphim', $idRapPhim);
            })
            ->where('da_xem', 0)
            ->update(['da_xem' => 1]);
            return $nhatKy;
        }
        public function danhDauRapDaXem(){// Đánh đau quản lý rạp đã xem
            $idRapPhim = $_SESSION['UserInternal']['ID_RapPhim'];
            $nhatKy = LogSuatChieu::whereHas('suatChieu.phongChieu', function($q) use ($idRapPhim) {
                $q->where('id_rapphim', $idRapPhim);
            })
            ->where('rap_da_xem', 0)
            ->update(['rap_da_xem' => 1]);
            return $nhatKy;
        }
        public function docKeHoach($batDau, $ketThuc){
            $query = KeHoachSuatChieu::with(['keHoachChiTiet.phim', 'keHoachChiTiet.phongChieu']);
            if(isset($_SESSION['UserInternal']['ID_RapPhim'])){
                $idRapPhim = $_SESSION['UserInternal']['ID_RapPhim'];
                $query->whereHas('keHoachChiTiet.phongChieu', function($q) use ($idRapPhim) {
                    $q->where('id_rapphim', $idRapPhim);
                });
            }
            $query->where('batdau', '>=', $batDau)
                  ->where('ketthuc', '<=', $ketThuc);
            $keHoach = $query->orderBy('batdau', 'asc')->get();
            return $keHoach;
        }
        public function luuSuatChieuVaoKeHoach($batDau, $ketThuc){
            $idRapPhim = $_SESSION['UserInternal']['ID_RapPhim'];
            $data = json_decode(file_get_contents('php://input'), true);
            $danhSachSuatChieu = $data['danh_sach_suat_chieu'] ?? [];
            
            if(empty($danhSachSuatChieu)){
                throw new \Exception("Không có suất chiếu nào để lưu");
            }
            
            // Tìm hoặc tạo kế hoạch
            $keHoach = KeHoachSuatChieu::where('batdau', $batDau)
                        ->where('ketthuc', $ketThuc)
                        ->first();
            
            if(!$keHoach){
                $keHoach = KeHoachSuatChieu::create([
                    'batdau' => $batDau,
                    'ketthuc' => $ketThuc
                ]);
            }
            
            // Lưu chi tiết kế hoạch (suất chiếu)
            foreach($danhSachSuatChieu as $suatChieu){
                // Kiểm tra xem suất chiếu này đã tồn tại trong kế hoạch chưa
                $exists = $keHoach->keHoachChiTiet()
                    ->where('id_phim', $suatChieu['id_phim'])
                    ->where('id_phongchieu', $suatChieu['id_phongchieu'])
                    ->where('batdau', $suatChieu['batdau'])
                    ->first();
                
                if(!$exists){
                    $keHoach->keHoachChiTiet()->create([
                        'id_phim' => $suatChieu['id_phim'],
                        'id_phongchieu' => $suatChieu['id_phongchieu'],
                        'batdau' => $suatChieu['batdau'],
                        'ketthuc' => $suatChieu['ketthuc'],
                        'tinh_trang' => 0 // Chờ duyệt
                    ]);
                }
            }
            
            return $keHoach;
        }
        
        public function xoaSuatChieuKhoiKeHoach($idKeHoachChiTiet){
            $chiTiet = \App\Models\KeHoachChiTiet::find($idKeHoachChiTiet);
            if(!$chiTiet){
                throw new \Exception("Không tìm thấy suất chiếu trong kế hoạch");
            }
            $chiTiet->delete();
        }
        public function docSuatChieuTheoPhongChieu($idPhongChieu){
            $suatChieu = SuatChieu::with('phim', 'phongChieu')
                ->where('id_phongchieu', $idPhongChieu)
                ->whereDate('batdau', Carbon::today())
                ->orderBy('batdau', 'asc')
                ->get();
            return $suatChieu;
        }
    }
?>