<?php
    namespace App\Services;
    use App\Models\KeHoachSuatChieu;
    use App\Models\KeHoachChiTiet;
    use App\Models\Phim;
    use App\Models\SuatChieu;
    use Carbon\Carbon;

    class Sc_KeHoachSuatChieu {
        
        /**
         * Tạo khung giờ gợi ý cho kế hoạch suất chiếu
         * Lọc ra các suất chiếu đã thêm trong modal (tạm thời), các suất chiếu đã có trong DB (kế hoạch)
         * VÀ các suất chiếu đã được duyệt (trong bảng SuatChieu)
         */
        public function taoKhungGioGoiYChoKeHoach($ngay, $id_phong_chieu, $thoi_luong_phim, $cacSuatChieuTrongModal = [])
        {
            // --- BƯỚC 1: ĐỊNH NGHĨA CÁC QUY TẮC ---
            $gio_mo_cua = Carbon::parse($ngay)->setTime(8, 0, 0);
            $gio_dong_cua_cuoi = Carbon::parse($ngay)->setTime(22, 0, 0);
            $khoang_cach_giua_suat = 30; // 30 phút buffer
            $buoc_nhay_goi_y = 15; // Mỗi gợi ý cách nhau 15 phút

            // --- BƯỚC 2: TẠO "VÙNG CẤM" DỰA TRÊN CÁC SUẤT CHIẾU ĐÃ CÓ TRONG DB ---
            $vungCam = [];
            
            // 2.1. Lấy các suất chiếu đã có trong KẾ HOẠCH từ DB (bảng KeHoachChiTiet)
            $cacSuatChieuTrongKeHoach = KeHoachChiTiet::where('id_phongchieu', $id_phong_chieu)
                                                ->whereDate('batdau', $ngay)
                                                ->get();

            // Thêm vùng cấm từ kế hoạch
            foreach ($cacSuatChieuTrongKeHoach as $suatChieu) {
                $batDau = Carbon::parse($suatChieu->batdau);
                $ketThuc = Carbon::parse($suatChieu->ketthuc);
                
                $vungCam[] = [
                    'bat_dau' => $batDau->copy()->subMinutes($khoang_cach_giua_suat),
                    'ket_thuc' => $ketThuc->copy()->addMinutes($khoang_cach_giua_suat)
                ];
            }

            // 2.2. Lấy các suất chiếu ĐÃ DUYỆT từ DB (bảng SuatChieu)
            $cacSuatChieuDaDuyet = SuatChieu::where('id_phongchieu', $id_phong_chieu)
                                            ->whereDate('batdau', $ngay)
                                            ->whereIn('tinh_trang', [0, 1, 3]) // Chỉ lấy suất đã duyệt
                                            ->get();

            // Thêm vùng cấm từ suất chiếu đã duyệt
            foreach ($cacSuatChieuDaDuyet as $suatChieu) {
                $batDau = Carbon::parse($suatChieu->batdau);
                $ketThuc = Carbon::parse($suatChieu->ketthuc);
                
                $vungCam[] = [
                    'bat_dau' => $batDau->copy()->subMinutes($khoang_cach_giua_suat),
                    'ket_thuc' => $ketThuc->copy()->addMinutes($khoang_cach_giua_suat)
                ];
            }

            // --- BƯỚC 3: THÊM "VÙNG CẤM" TỪ CÁC SUẤT CHIẾU TROG MODAL (TẠM THỜI) ---
            foreach ($cacSuatChieuTrongModal as $suatChieu) {
                // Chỉ lọc nếu cùng phòng chiếu
                if ($suatChieu['id_phongchieu'] != $id_phong_chieu) {
                    continue;
                }

                $batDau = Carbon::parse($suatChieu['batdau']);
                $ketThuc = Carbon::parse($suatChieu['ketthuc']);
                
                $vungCam[] = [
                    'bat_dau' => $batDau->copy()->subMinutes($khoang_cach_giua_suat),
                    'ket_thuc' => $ketThuc->copy()->addMinutes($khoang_cach_giua_suat)
                ];
            }

            // --- BƯỚC 4: TẠO VÀ KIỂM TRA CÁC KHUNG GIỜ TIỀM NĂNG ---
            $khungGioGoiY = [];
            $gioKiemTra = $gio_mo_cua->copy();

            while ($gioKiemTra <= $gio_dong_cua_cuoi) {
                $gioKetThuc = $gioKiemTra->copy()->addMinutes($thoi_luong_phim);
                $hopLe = true;

                // Kiểm tra xem khung giờ này có trùng với vùng cấm nào không
                foreach ($vungCam as $vung) {
                    if ($gioKiemTra < $vung['ket_thuc'] && $gioKetThuc > $vung['bat_dau']) {
                        $hopLe = false;
                        break;
                    }
                }

                if ($hopLe) {
                    $khungGioGoiY[] = $gioKiemTra->format('H:i');
                }

                $gioKiemTra->addMinutes($buoc_nhay_goi_y);
            }

            return $khungGioGoiY;
        }

        /**
         * Kiểm tra suất chiếu trong kế hoạch có hợp lệ không
         * Kiểm tra với suất chiếu trong DB (kế hoạch), suất chiếu đã duyệt (SuatChieu) và các suất chiếu tạm trong modal
         */
        public function kiemTraSuatChieuKeHoach($batDau, $idPhongChieu, $thoiLuongPhim, $cacSuatChieuTrongModal = [])
        {
            // --- BƯỚC 1: ĐỊNH NGHĨA CÁC QUY TẮC ---
            $khoang_cach_giua_suat = 30; // 30 phút buffer

            $gioBatDauMoi = Carbon::parse($batDau);
            $gioKetThucMoi = $gioBatDauMoi->copy()->addMinutes($thoiLuongPhim);
            $ngayChieu = $gioBatDauMoi->copy()->startOfDay();

            // --- BƯỚC 2: KIỂM TRA GIỜ HOẠT ĐỘNG ---
            $gio_mo_cua = $ngayChieu->copy()->setTime(8, 0, 0);
            $gio_dong_cua_cuoi = $ngayChieu->copy()->setTime(22, 0, 0);

            if ($gioBatDauMoi < $gio_mo_cua || $gioBatDauMoi > $gio_dong_cua_cuoi) {
                return false;
            }

            // --- BƯỚC 3: KIỂM TRA XUNG ĐỘT VỚI CÁC SUẤT CHIẾU TRONG KẾ HOẠCH (KeHoachChiTiet) ---
            $cacSuatChieuTrongKeHoach = KeHoachChiTiet::where('id_phongchieu', $idPhongChieu)
                                                ->whereDate('batdau', $ngayChieu)
                                                ->get();

            foreach ($cacSuatChieuTrongKeHoach as $suatChieu) {
                $batDauHienTai = Carbon::parse($suatChieu->batdau);
                $ketThucHienTai = Carbon::parse($suatChieu->ketthuc);

                $vungCamBatDau = $batDauHienTai->copy()->subMinutes($khoang_cach_giua_suat);
                $vungCamKetThuc = $ketThucHienTai->copy()->addMinutes($khoang_cach_giua_suat);

                if ($gioBatDauMoi < $vungCamKetThuc && $gioKetThucMoi > $vungCamBatDau) {
                    return false;
                }
            }

            // --- BƯỚC 3.1: KIỂM TRA XUNG ĐỘT VỚI CÁC SUẤT CHIẾU ĐÃ DUYỆT (SuatChieu) ---
            $cacSuatChieuDaDuyet = SuatChieu::where('id_phongchieu', $idPhongChieu)
                                            ->whereDate('batdau', $ngayChieu)
                                            ->whereIn('tinh_trang', [0, 1, 3]) // Chỉ lấy suất đã duyệt
                                            ->get();

            foreach ($cacSuatChieuDaDuyet as $suatChieu) {
                $batDauHienTai = Carbon::parse($suatChieu->batdau);
                $ketThucHienTai = Carbon::parse($suatChieu->ketthuc);

                $vungCamBatDau = $batDauHienTai->copy()->subMinutes($khoang_cach_giua_suat);
                $vungCamKetThuc = $ketThucHienTai->copy()->addMinutes($khoang_cach_giua_suat);

                if ($gioBatDauMoi < $vungCamKetThuc && $gioKetThucMoi > $vungCamBatDau) {
                    return false;
                }
            }

            // --- BƯỚC 4: KIỂM TRA XUNG ĐỘT VỚI CÁC SUẤT CHIẾU TRONG MODAL (TẠM THỜI) ---
            foreach ($cacSuatChieuTrongModal as $suatChieu) {
                // Chỉ kiểm tra nếu cùng phòng chiếu
                if ($suatChieu['id_phongchieu'] != $idPhongChieu) {
                    continue;
                }

                $batDauHienTai = Carbon::parse($suatChieu['batdau']);
                $ketThucHienTai = Carbon::parse($suatChieu['ketthuc']);

                $vungCamBatDau = $batDauHienTai->copy()->subMinutes($khoang_cach_giua_suat);
                $vungCamKetThuc = $ketThucHienTai->copy()->addMinutes($khoang_cach_giua_suat);

                if ($gioBatDauMoi < $vungCamKetThuc && $gioKetThucMoi > $vungCamBatDau) {
                    return false;
                }
            }

            return true;
        }

        /**
         * Đọc chi tiết suất chiếu trong kế hoạch theo khoảng thời gian
         * Mỗi tuần chỉ có 1 kế hoạch, trả về danh sách chi tiết suất chiếu của tuần đó
         * Cải thiện: Trả về thông tin đầy đủ hơn, bao gồm cả thông tin kế hoạch
         */
        public function docKeHoach($batDau, $ketThuc)
        {
            $idRapPhim = null;
            if(isset($_SESSION['UserInternal']['ID_RapPhim'])){
                $idRapPhim = $_SESSION['UserInternal']['ID_RapPhim'];
            }
            if(isset($_GET['id_rap'])){
                $idRapPhim = (int)($_GET['id_rap'] ?? 0);
            }
            
            // Tìm kế hoạch của tuần này (mỗi tuần chỉ có 1 kế hoạch)
            $keHoach = KeHoachSuatChieu::where('batdau', $batDau)
                ->where('ketthuc', $ketThuc)
                ->first();
            
            // Nếu không có kế hoạch, trả về array rỗng
            if (!$keHoach) {
                return [];
            }
            
            // Lấy chi tiết suất chiếu của kế hoạch này, chỉ lấy các suất của rạp hiện tại
            $query = KeHoachChiTiet::with(['phim', 'phongChieu'])
                ->where('id_kehoach', $keHoach->id);
            
            if ($idRapPhim) {
                $query->whereHas('phongChieu', function($q) use ($idRapPhim) {
                    $q->where('id_rapphim', $idRapPhim);
                });
            }
            
            $chiTietSuatChieu = $query->orderBy('batdau', 'asc')->get();
            
            // Thêm thông tin tổng hợp
            $tongSoSuat = $chiTietSuatChieu->count();
            $soSuatDaDuyet = $chiTietSuatChieu->where('tinh_trang', 1)->count();
            $soSuatChoDuyet = $chiTietSuatChieu->where('tinh_trang', 0)->count();
            $soSuatTuChoi = $chiTietSuatChieu->where('tinh_trang', 2)->count();
            
            return [
                'ke_hoach' => $keHoach,
                'chi_tiet' => $chiTietSuatChieu,
                'thong_ke' => [
                    'tong_so' => $tongSoSuat,
                    'da_duyet' => $soSuatDaDuyet,
                    'cho_duyet' => $soSuatChoDuyet,
                    'tu_choi' => $soSuatTuChoi
                ]
            ];
        }

        /**
         * Lưu suất chiếu vào kế hoạch
         * Mỗi tuần chỉ có 1 kế hoạch, tìm hoặc tạo kế hoạch cho tuần này
         * QUAN TRỌNG: Xóa các suất chiếu cũ của NGÀY được chọn trước khi lưu (tránh trùng lặp)
         * Cải thiện: Xử lý update thay vì tạo mới nếu có id_kehoach_chitiet
         */
        public function luuSuatChieuVaoKeHoach()
        {
            $data = json_decode(file_get_contents('php://input'), true);
            $suatChieuList = $data['suat_chieu'] ?? [];
            $ngayChieu = $data['ngay_chieu'] ?? null; // Ngày đã chọn trong modal
            
            if (empty($suatChieuList)) {
                throw new \Exception('Không có suất chiếu nào để lưu');
            }

            if (!$ngayChieu) {
                throw new \Exception('Thiếu thông tin ngày chiếu');
            }

            // Tạo $batDau là thứ 2 của tuần chứa $ngayChieu, $ketThuc là chủ nhật của tuần chứa $ngayChieu
            $ngay = Carbon::parse($ngayChieu);
            $batDau = $ngay->copy()->startOfWeek(Carbon::MONDAY);
            $ketThuc = $batDau->copy()->addDays(6);

            // Tìm hoặc tạo kế hoạch cho tuần này (mỗi tuần 1 kế hoạch duy nhất)
            $keHoach = KeHoachSuatChieu::where('batdau', $batDau->toDateString())
                ->where('ketthuc', $ketThuc->toDateString())
                ->first();
            
            if (!$keHoach) {
                $keHoach = KeHoachSuatChieu::create([
                    'batdau' => $batDau->toDateString(),
                    'ketthuc' => $ketThuc->toDateString()
                ]);
            }

            // XÓA CHỈ CÁC CHI TIẾT KẾ HOẠCH TRONG NGÀY ĐƯỢC CHỌN (KHÔNG XÓA CẢ TUẦN)
            // Lý do: Frontend giờ gửi chỉ ngày đang chỉnh, chỉ cần xóa các suất của ngày đó
            $idRapPhim = $_SESSION['UserInternal']['ID_RapPhim'] ?? null;
            $ngayXoa = Carbon::parse($ngayChieu)->toDateString(); // 'YYYY-MM-DD'

            // Lấy danh sách ID các suất chiếu sẽ được giữ lại (có id_kehoach_chitiet)
            $idsGiuLai = [];
            foreach ($suatChieuList as $suatChieu) {
                if (isset($suatChieu['id_kehoach_chitiet']) && $suatChieu['id_kehoach_chitiet']) {
                    $idsGiuLai[] = $suatChieu['id_kehoach_chitiet'];
                }
            }

            // Xóa các suất chiếu cũ của ngày (trừ những suất có ID trong danh sách giữ lại và đã duyệt)
            $queryXoa = KeHoachChiTiet::where('id_kehoach', $keHoach->id)
                ->whereDate('batdau', $ngayXoa)
                ->where('tinh_trang', '!=', 1); // KHÔNG xóa suất đã duyệt
            
            if (!empty($idsGiuLai)) {
                $queryXoa->whereNotIn('id', $idsGiuLai);
            }
            
            if ($idRapPhim) {
                $queryXoa->whereHas('phongChieu', function($query) use ($idRapPhim) {
                    $query->where('id_rapphim', $idRapPhim);
                });
            }
            
            $queryXoa->delete();

            // Lưu hoặc cập nhật suất chiếu
            foreach ($suatChieuList as $suatChieu) {
                // Kiểm tra xem có id_kehoach_chitiet không (để update thay vì create)
                if (isset($suatChieu['id_kehoach_chitiet']) && $suatChieu['id_kehoach_chitiet']) {
                    $existingPlan = KeHoachChiTiet::find($suatChieu['id_kehoach_chitiet']);
                    if ($existingPlan) {
                        // Chỉ update nếu chưa duyệt
                        if ($existingPlan->tinh_trang != 1) {
                            $existingPlan->update([
                                'id_phim' => $suatChieu['id_phim'],
                                'id_phongchieu' => $suatChieu['id_phongchieu'],
                                'batdau' => $suatChieu['batdau'],
                                'ketthuc' => $suatChieu['ketthuc']
                            ]);
                        }
                        continue;
                    }
                }
                
                // Tạo mới suất chiếu
                KeHoachChiTiet::create([
                    'id_kehoach' => $keHoach->id,
                    'id_phim' => $suatChieu['id_phim'],
                    'id_phongchieu' => $suatChieu['id_phongchieu'],
                    'batdau' => $suatChieu['batdau'],
                    'ketthuc' => $suatChieu['ketthuc'],
                    'tinh_trang' => 0 // Mặc định: Chờ duyệt
                ]);
            }

            return $keHoach;
        }

        /**
         * Xóa suất chiếu khỏi kế hoạch
         * KHÔNG cho phép xóa suất chiếu đã duyệt (tinh_trang = 1)
         */
        public function xoaSuatChieuKhoiKeHoach($idKeHoachChiTiet)
        {
            $keHoachChiTiet = KeHoachChiTiet::find($idKeHoachChiTiet);
            
            if (!$keHoachChiTiet) {
                throw new \Exception('Không tìm thấy suất chiếu trong kế hoạch');
            }

            // Không cho xóa suất chiếu đã duyệt
            if ($keHoachChiTiet->tinh_trang == 1) {
                throw new \Exception('Không thể xóa suất chiếu đã được duyệt');
            }

            $keHoachChiTiet->delete();
        }

        /**
         * Duyệt suất chiếu trong kế hoạch
         * - Cập nhật tinh_trang = 1 trong KeHoachChiTiet
         * - Tạo suất chiếu thực tế trong bảng SuatChieu
         * - Ghi log với hanh_dong = 5 (Duyệt từ kế hoạch)
         */
        public function duyetKeHoach($idKeHoachChiTiet)
        {
            $keHoachChiTiet = KeHoachChiTiet::with(['phim', 'phongChieu'])->find($idKeHoachChiTiet);
            
            if (!$keHoachChiTiet) {
                throw new \Exception('Không tìm thấy suất chiếu trong kế hoạch');
            }

            // Kiểm tra trạng thái
            if ($keHoachChiTiet->tinh_trang == 1) {
                throw new \Exception('Suất chiếu này đã được duyệt trước đó');
            }


            $logSuatChieu = null;
            try {
                // 1. Cập nhật trạng thái kế hoạch chi tiết
                $keHoachChiTiet->update([
                    'tinh_trang' => 1
                ]);

                // 2. Tạo suất chiếu thực tế
                $suatChieu = \App\Models\SuatChieu::create([
                    'id_phim' => $keHoachChiTiet->id_phim,
                    'id_phongchieu' => $keHoachChiTiet->id_phongchieu,
                    'batdau' => $keHoachChiTiet->batdau,
                    'ketthuc' => $keHoachChiTiet->ketthuc,
                    'tinh_trang' => 1 // Đã duyệt
                ]);

                // 3. Ghi log với hanh_dong = 5 (Duyệt từ kế hoạch)
                $logSuatChieu = \App\Models\LogSuatChieu::create([
                    'id_suatchieu' => $suatChieu->id,
                    'hanh_dong' => 5, // Duyệt từ kế hoạch
                    'batdau' => $keHoachChiTiet->batdau,
                    'id_phim' => $keHoachChiTiet->id_phim,
                    'ten_phim' => $keHoachChiTiet->phim->ten ?? '',
                    'da_xem' => 0,
                    'rap_da_xem' => 0
                ]);


                
                return [
                    'ke_hoach_chi_tiet' => $keHoachChiTiet,
                    'suat_chieu' => $suatChieu
                ];
            } catch (\Exception $e) {
                // Nếu có lỗi, rollback các thay đổi
                if ($logSuatChieu) {
                    $logSuatChieu->delete();
                }
                throw $e;
            }
        }

        /**
         * Từ chối suất chiếu trong kế hoạch
         * - Cập nhật tinh_trang = 2 trong KeHoachChiTiet
         */
        public function tuChoiKeHoach($idKeHoachChiTiet)
        {
            $keHoachChiTiet = KeHoachChiTiet::find($idKeHoachChiTiet);
            
            if (!$keHoachChiTiet) {
                throw new \Exception('Không tìm thấy suất chiếu trong kế hoạch');
            }

            // Kiểm tra trạng thái
            if ($keHoachChiTiet->tinh_trang == 1) {
                throw new \Exception('Không thể từ chối suất chiếu đã được duyệt');
            }

            // Cập nhật trạng thái
            $keHoachChiTiet->update([
                'tinh_trang' => 2
            ]);

            return $keHoachChiTiet;
        }

        /**
         * Hoàn tác suất chiếu bị từ chối trong kế hoạch
         * - Cập nhật tinh_trang từ 2 (Từ chối) về 0 (Chờ duyệt)
         */
        public function hoanTacKeHoach($idKeHoachChiTiet)
        {
            $keHoachChiTiet = KeHoachChiTiet::find($idKeHoachChiTiet);
            
            if (!$keHoachChiTiet) {
                throw new \Exception('Không tìm thấy suất chiếu trong kế hoạch');
            }

            // Chỉ cho phép hoàn tác nếu suất chiếu đang ở trạng thái từ chối (tinh_trang = 2)
            if ($keHoachChiTiet->tinh_trang != 2) {
                throw new \Exception('Chỉ có thể hoàn tác suất chiếu đã bị từ chối');
            }

            // Cập nhật trạng thái về chờ duyệt
            $keHoachChiTiet->update([
                'tinh_trang' => 0
            ]);

            return $keHoachChiTiet;
        }

        /**
         * Sao chép kế hoạch từ tuần trước
         * - Lấy kế hoạch tuần trước (lùi 7 ngày)
         * - Chuyển đổi ngày sang tuần hiện tại
         * - Giữ nguyên giờ chiếu và phòng chiếu
         */
        public function saoChepKeHoachTuTuanTruoc($batDauTuanHienTai, $ketThucTuanHienTai, $idRap = null)
        {
            // Tính tuần trước (lùi 7 ngày)
            $batDauTuanTruoc = Carbon::parse($batDauTuanHienTai)->subDays(7)->toDateString();
            $ketThucTuanTruoc = Carbon::parse($ketThucTuanHienTai)->subDays(7)->toDateString();
            
            // Lấy kế hoạch tuần trước
            $keHoachTuanTruoc = KeHoachSuatChieu::where('batdau', $batDauTuanTruoc)
                ->where('ketthuc', $ketThucTuanTruoc)
                ->first();
            
            if (!$keHoachTuanTruoc) {
                throw new \Exception('Không tìm thấy kế hoạch tuần trước để sao chép');
            }
            
            // Lấy chi tiết kế hoạch tuần trước
            $query = KeHoachChiTiet::with(['phim', 'phongChieu'])
                ->where('id_kehoach', $keHoachTuanTruoc->id);
            
            if ($idRap) {
                $query->whereHas('phongChieu', function($q) use ($idRap) {
                    $q->where('id_rapphim', $idRap);
                });
            }
            
            $chiTietTuanTruoc = $query->get();
            
            if ($chiTietTuanTruoc->isEmpty()) {
                throw new \Exception('Không có suất chiếu nào trong kế hoạch tuần trước');
            }
            
            // Tìm hoặc tạo kế hoạch cho tuần hiện tại
            $keHoachHienTai = KeHoachSuatChieu::where('batdau', $batDauTuanHienTai)
                ->where('ketthuc', $ketThucTuanHienTai)
                ->first();
            
            if (!$keHoachHienTai) {
                $keHoachHienTai = KeHoachSuatChieu::create([
                    'batdau' => $batDauTuanHienTai,
                    'ketthuc' => $ketThucTuanHienTai
                ]);
            }
            
            // Chuyển đổi và lưu từng suất chiếu
            $soLuongSaoChep = 0;
            foreach ($chiTietTuanTruoc as $chiTiet) {
                // Tính số ngày từ thứ 2 của tuần trước đến ngày của suất chiếu
                $ngayTuanTruoc = Carbon::parse($chiTiet->batdau);
                $thu2TuanTruoc = Carbon::parse($batDauTuanTruoc);
                $soNgay = $thu2TuanTruoc->diffInDays($ngayTuanTruoc);
                
                // Tính ngày mới trong tuần hiện tại
                $thu2TuanHienTai = Carbon::parse($batDauTuanHienTai);
                $ngayMoi = $thu2TuanHienTai->copy()->addDays($soNgay);
                
                // Giữ nguyên giờ chiếu
                $gioBatDau = Carbon::parse($chiTiet->batdau)->format('H:i:s');
                $gioKetThuc = Carbon::parse($chiTiet->ketthuc)->format('H:i:s');
                
                $batDauMoi = $ngayMoi->copy()->setTimeFromTimeString($gioBatDau);
                $ketThucMoi = $ngayMoi->copy()->setTimeFromTimeString($gioKetThuc);
                
                // Tạo suất chiếu mới (luôn ở trạng thái chờ duyệt)
                KeHoachChiTiet::create([
                    'id_kehoach' => $keHoachHienTai->id,
                    'id_phim' => $chiTiet->id_phim,
                    'id_phongchieu' => $chiTiet->id_phongchieu,
                    'batdau' => $batDauMoi->toDateTimeString(),
                    'ketthuc' => $ketThucMoi->toDateTimeString(),
                    'tinh_trang' => 0 // Luôn chờ duyệt khi sao chép
                ]);
                
                $soLuongSaoChep++;
            }
            
            return [
                'message' => "Đã sao chép {$soLuongSaoChep} suất chiếu từ tuần trước",
                'count' => $soLuongSaoChep,
                'ke_hoach' => $keHoachHienTai
            ];
        }

        /**
         * Áp dụng kế hoạch đã duyệt vào suất chiếu thực tế
         * - Chỉ áp dụng các suất chiếu đã duyệt (tinh_trang = 1)
         * - Tạo suất chiếu thực tế trong bảng SuatChieu
         * - Ghi log với hanh_dong = 5 (Duyệt từ kế hoạch)
         */
        public function apDungKeHoach($batDau, $ketThuc, $idRap = null)
        {
            // Lấy tất cả suất chiếu đã duyệt trong tuần
            $query = KeHoachChiTiet::with(['phim', 'phongChieu'])
                ->whereBetween('batdau', [$batDau, $ketThuc])
                ->where('tinh_trang', 1); // Chỉ lấy suất đã duyệt

            // Lọc theo rạp nếu có
            if ($idRap) {
                $query->whereHas('phongChieu', function($q) use ($idRap) {
                    $q->where('id_rapphim', $idRap);
                });
            }

            $cacSuatChieuDaDuyet = $query->get();

            if ($cacSuatChieuDaDuyet->isEmpty()) {
                return [
                    'message' => 'Không có suất chiếu nào đã duyệt để áp dụng',
                    'count' => 0
                ];
            }

            try {
                $danhSachSuatChieuMoi = [];
                $danhSachLog = [];
                
                foreach ($cacSuatChieuDaDuyet as $keHoachChiTiet) {
                    // Kiểm tra xem suất chiếu đã tồn tại chưa (tránh trùng lặp)
                    $suatChieuTonTai = SuatChieu::where('id_phim', $keHoachChiTiet->id_phim)
                        ->where('id_phongchieu', $keHoachChiTiet->id_phongchieu)
                        ->where('batdau', $keHoachChiTiet->batdau)
                        ->where('ketthuc', $keHoachChiTiet->ketthuc)
                        ->first();
                    
                    if ($suatChieuTonTai) {
                        continue; // Bỏ qua nếu đã tồn tại
                    }

                    // Tạo suất chiếu thực tế
                    $suatChieu = SuatChieu::create([
                        'id_phim' => $keHoachChiTiet->id_phim,
                        'id_phongchieu' => $keHoachChiTiet->id_phongchieu,
                        'batdau' => $keHoachChiTiet->batdau,
                        'ketthuc' => $keHoachChiTiet->ketthuc,
                        'tinh_trang' => 1 // Đã duyệt
                    ]);

                    // Ghi log
                    $logSuatChieu = \App\Models\LogSuatChieu::create([
                        'id_suatchieu' => $suatChieu->id,
                        'hanh_dong' => 5, // Duyệt từ kế hoạch
                        'batdau' => $keHoachChiTiet->batdau,
                        'id_phim' => $keHoachChiTiet->id_phim,
                        'ten_phim' => $keHoachChiTiet->phim->ten_phim ?? $keHoachChiTiet->phim->ten ?? '',
                        'da_xem' => 0,
                        'rap_da_xem' => 0
                    ]);

                    $danhSachSuatChieuMoi[] = $suatChieu;
                    $danhSachLog[] = $logSuatChieu;
                }
                
                return [
                    'message' => 'Áp dụng kế hoạch thành công',
                    'count' => count($danhSachSuatChieuMoi),
                    'suat_chieu' => $danhSachSuatChieuMoi
                ];
            } catch (\Exception $e) {
                // Nếu có lỗi, rollback các thay đổi
                foreach ($danhSachSuatChieuMoi as $suatChieu) {
                    $suatChieu->delete();
                }
                foreach ($danhSachLog as $log) {
                    $log->delete();
                }
                throw $e;
            }
        }

        /**
         * Xóa toàn bộ kế hoạch tuần
         * - Chỉ xóa các suất chiếu chưa duyệt (tinh_trang != 1)
         * - Nếu không còn suất chiếu nào, xóa luôn kế hoạch
         */
        public function xoaKeHoachTuan($batDau, $ketThuc, $idRap = null)
        {
            // Tìm kế hoạch
            $keHoach = KeHoachSuatChieu::where('batdau', $batDau)
                ->where('ketthuc', $ketThuc)
                ->first();
            
            if (!$keHoach) {
                throw new \Exception('Không tìm thấy kế hoạch để xóa');
            }
            
            // Xóa các suất chiếu chưa duyệt
            $query = KeHoachChiTiet::where('id_kehoach', $keHoach->id)
                ->where('tinh_trang', '!=', 1); // Không xóa suất đã duyệt
            
            if ($idRap) {
                $query->whereHas('phongChieu', function($q) use ($idRap) {
                    $q->where('id_rapphim', $idRap);
                });
            }
            
            $soLuongXoa = $query->count();
            $query->delete();
            
            // Kiểm tra xem còn suất chiếu nào không
            $conLai = KeHoachChiTiet::where('id_kehoach', $keHoach->id)->count();
            if ($conLai == 0) {
                $keHoach->delete();
            }
            
            return [
                'message' => "Đã xóa {$soLuongXoa} suất chiếu khỏi kế hoạch",
                'count' => $soLuongXoa
            ];
        }

        /**
         * Duyệt toàn bộ tuần
         * - Duyệt tất cả suất chiếu chờ duyệt (tinh_trang = 0) trong khoảng thời gian
         */
        public function duyetTuan($batDau, $ketThuc, $idRap = null)
        {
            // Lấy tất cả suất chiếu chờ duyệt trong tuần
            $query = KeHoachChiTiet::with(['phim', 'phongChieu'])
                ->whereBetween('batdau', [$batDau, $ketThuc])
                ->where('tinh_trang', 0);

            // Lọc theo rạp nếu có
            if ($idRap) {
                $query->whereHas('phongChieu', function($q) use ($idRap) {
                    $q->where('id_rapphim', $idRap);
                });
            }

            $cacSuatChieuChoDuyet = $query->get();

            if ($cacSuatChieuChoDuyet->isEmpty()) {
                return [
                    'message' => 'Không có suất chiếu nào chờ duyệt trong tuần này',
                    'count' => 0
                ];
            }

            
            try {
                $danhSachSuatChieuMoi = [];
                
                foreach ($cacSuatChieuChoDuyet as $keHoachChiTiet) {
                    // Cập nhật trạng thái kế hoạch
                    $keHoachChiTiet->update([
                        'tinh_trang' => 1
                    ]);

                    // Tạo suất chiếu thực tế
                    $suatChieu = \App\Models\SuatChieu::create([
                        'id_phim' => $keHoachChiTiet->id_phim,
                        'id_phongchieu' => $keHoachChiTiet->id_phongchieu,
                        'batdau' => $keHoachChiTiet->batdau,
                        'ketthuc' => $keHoachChiTiet->ketthuc,
                        'tinh_trang' => 1
                    ]);

                    // Ghi log
                    $logSuatChieu = \App\Models\LogSuatChieu::create([
                        'id_suatchieu' => $suatChieu->id,
                        'hanh_dong' => 5,
                        'batdau' => $keHoachChiTiet->batdau,
                        'id_phim' => $keHoachChiTiet->id_phim,
                        'ten_phim' => $keHoachChiTiet->phim->ten ?? '',
                        'da_xem' => 0,
                        'rap_da_xem' => 0
                    ]);

                    $danhSachSuatChieuMoi[] = $suatChieu;
                }
                
                return [
                    'message' => 'Duyệt toàn bộ tuần thành công',
                    'count' => count($danhSachSuatChieuMoi),
                    'suat_chieu' => $danhSachSuatChieuMoi
                ];
            } catch (\Exception $e) {
                // Nếu có lỗi, rollback các thay đổi
                foreach ($danhSachSuatChieuMoi as $suatChieu) {
                    // Xóa suất chiếu
                    $suatChieu->delete();
                    // Xóa log liên quan
                    \App\Models\LogSuatChieu::where('id_suatchieu', $suatChieu->id)->delete();
                }
                throw $e;
            }
        }
    }
?>
