<?php
    namespace App\Services;
    use App\Models\PhongChieu;
    use App\Models\Ve;
    use App\Models\SuatChieu;
    class Sc_PhongChieu {
        // Các phương thức liên quan đến phòng chiếu sẽ được thêm vào đây
        public function them(){
            $phongChieu = null;
            try{
                $ten = $_POST['ten'] ?? '';
                $maPhong = $_POST['ma_phong'] ?? '';
                $moTa = $_POST['mo_ta'] ?? '';
                $loaiPhongChieu = $_POST['loai_phongchieu'] ?? '';
                $trangThai = $_POST['trang_thai'] ?? '';
                $soHangGhe = $_POST['sohang_ghe'] ?? 0;
                $soCotGhe = $_POST['socot_ghe'] ?? 0;
                $phongChieu = PhongChieu::create([
                    'ten' => $ten,
                    'ma_phong' => $maPhong,
                    'mo_ta' => $moTa,
                    'loai_phongchieu' => $loaiPhongChieu,
                    'trang_thai' => $trangThai,
                    'sohang_ghe' => $soHangGhe,
                    'socot_ghe' => $soCotGhe,
                    'id_rapphim' => $_SESSION['UserInternal']['ID_RapPhim'],
                ]);
                if($phongChieu){
                    $danhSachGhe = $_POST['danh_sach_ghe'] ?? [];
                    if(count($danhSachGhe) == 0){
                        throw new \Exception("Số lượng ghế không hợp lệ. ". count($danhSachGhe));
                    }
                    foreach($danhSachGhe as $ghe){
                        if(empty($ghe['loaighe_id'])){
                            $phongChieu->soDoGhe()->create([
                                'so_ghe' => $ghe['so_ghe'],
                                'loaighe_id' => null,
                                'phongchieu_id' => $phongChieu->id,
                            ]);
                        }
                        else{
                            $phongChieu->soDoGhe()->create([
                                'so_ghe' => $ghe['so_ghe'],
                                'phongchieu_id' => $phongChieu->id,
                                'loaighe_id' => $ghe['loaighe_id'],
                            ]);
                        }
                        
                    }
                    $phongChieu->capNhatSoLuongGhe();
                    return true;
                }
                return false;
            } catch (\Exception $e) {
                $phongChieu?->delete();
                throw new \Exception($e->getMessage());
            }
        }
        public function capNhat($id){
            // bổ sung logic sau
            $phongChieu = PhongChieu::with('soDoGhe.loaiGhe')->find($id);
            if(!$phongChieu){
                throw new \Exception("Phòng chiếu không tồn tại");
            }
            $phongChieuCu = $phongChieu;
            // Cập nhật thông tin phòng chiếu
            $data = file_get_contents('php://input');
            $input = json_decode($data, true);
            try{
                $phongChieu->ten = $input['ten'] ?? $phongChieu->ten;
                $phongChieu->ma_phong = $input['ma_phong'] ?? $phongChieu->ma_phong;
                $phongChieu->mo_ta = $input['mo_ta'] ?? $phongChieu->mo_ta;
                $phongChieu->loai_phongchieu = $input['loai_phongchieu'] ?? $phongChieu->loai_phongchieu;
                $phongChieu->trang_thai = $input['trang_thai'] ?? $phongChieu->trang_thai;
                $phongChieu->sohang_ghe = $input['sohang_ghe'] ?? $phongChieu->sohang_ghe;
                $phongChieu->socot_ghe = $input['socot_ghe'] ?? $phongChieu->socot_ghe;
                $phongChieu->save();
                $danhSachGhe = $input['danh_sach_ghe'] ?? [];
                if(count($danhSachGhe) == 0){
                    throw new \Exception("Số lượng ghế không hợp lệ. ". count($danhSachGhe));
                }
                $phongChieu->soDoGhe()->delete();
                foreach($danhSachGhe as $ghe){
                    if(empty($ghe['loaighe_id'])){
                        $phongChieu->soDoGhe()->create([
                            'so_ghe' => $ghe['so_ghe'],
                            'loaighe_id' => null,
                            'phongchieu_id' => $phongChieu->id,
                        ]);
                    }
                    else{
                        $phongChieu->soDoGhe()->create([
                            'so_ghe' => $ghe['so_ghe'],
                            'phongchieu_id' => $phongChieu->id,
                            'loaighe_id' => $ghe['loaighe_id'],
                        ]);
                    }
                    
                }
                $phongChieu->capNhatSoLuongGhe();
                return true;
            } catch (\Exception $e) {
                $phongChieu->soDoGhe()->delete();
                $phongChieu = $phongChieuCu;
                $phongChieu->save();
                throw new \Exception("Cập nhật thông tin phòng chiếu thất bại: " . $e->getMessage());
            }
            
        }
        public function doc($tuKhoa = null, $loaiPhongChieu = null, $trangThai = null){
            $query = PhongChieu::with(['soDoGhe.loaiGhe'])
                    ->where('id_rapphim', $_SESSION['UserInternal']['ID_RapPhim']);
            if($tuKhoa){
                $query->where(function($q) use ($tuKhoa){
                    $q->where('ten', 'like', "%$tuKhoa%")
                    ->orWhere('ma_phong', 'like', "%$tuKhoa%");
                });
            }
            if($loaiPhongChieu){
                $query->where('loai_phongchieu', $loaiPhongChieu);
            }
            if($trangThai){
                $query->where('trang_thai', $trangThai);
            }
            $dsPhongChieu = $query->get();

            // Thêm số ghế theo từng loại vào từng phòng chiếu
            foreach($dsPhongChieu as $phong){
                $loaiGheCounts = [];
                foreach($phong->soDoGhe as $ghe){
                    if($ghe->loaighe_id){
                        $tenLoai = $ghe->loaiGhe ? $ghe->loaiGhe->ten : 'Khác';
                        if(!isset($loaiGheCounts[$tenLoai])) $loaiGheCounts[$tenLoai] = 0;
                        $loaiGheCounts[$tenLoai]++;
                    }
                }
                $phong->so_ghe_theo_loai = $loaiGheCounts;
            }

            return $dsPhongChieu;
        }

        public function chiTiet($idSuatChieu)
        {
            $now = date('Y-m-d H:i:s');
            // Lấy vé của suất chiếu kèm thông tin ghế và loại ghế
            $ves = Ve::with('ghe.loaiGhe')
            ->where('suat_chieu_id', $idSuatChieu)
            ->where(function($q) use ($now) {
                 $q->whereIn('trang_thai', [1,2,3]) // 0 là hủy vé
                ->orWhere(function($q2) use ($now) {
                    $q2->where('trang_thai', 1)
                        ->where('het_han_giu', '>', $now); // chỉ lấy khi có hạn giữ hợp lệ
                });
            })
            ->get();

            // Lấy suất chiếu kèm phòng chiếu, sơ đồ ghế, loại ghế, phim và rạp
            $suat = SuatChieu::with([
                'phongChieu.soDoGhe.loaiGhe',
                'phongChieu.rapChieuPhim', 
                'phim'
            ])->find($idSuatChieu);

            if (!$suat || !$suat->phongChieu) {
                throw new \Exception("Không tìm thấy phòng chiếu");
            }

            $phong = $suat->phongChieu;

            // Map dữ liệu ghế + trạng thái
            $soDoGhe = $phong->soDoGhe->map(function ($ghe) use ($ves) {
                $ve = $ves->firstWhere('ghe_id', $ghe->id);
                return [
                    'id'     => $ghe->id,
                    'so_ghe'     => $ghe->so_ghe,
                    'loaighe_id' => $ghe->loaighe_id,
                    'loai_ghe'   => $ghe->loaiGhe ? [
                        'ten'     => $ghe->loaiGhe->ten,
                        'ma_mau'  => $ghe->loaiGhe->ma_mau,
                        'phu_thu' => $ghe->loaiGhe->phu_thu,
                        'mo_ta'   => $ghe->loaiGhe->mo_ta,
                    ] : null,
                    'trang_thai' => $ve ? $ve->trang_thai : 3,
                ];
            })->values();

            return [
                'phim' => $suat->phim ? [
                    'id'          => $suat->phim->id,
                    'ten_phim'    => $suat->phim->ten_phim,
                    'do_tuoi'   => $suat->phim->do_tuoi,
                    'dao_dien'    => $suat->phim->dao_dien,
                    'dien_vien'   => $suat->phim->dien_vien,
                    'thoi_luong'  => $suat->phim->thoi_luong,
                    'ngay_cong_chieu' => $suat->phim->ngay_cong_chieu,
                    'poster_url'  => $suat->phim->poster_url,
                ] : null,
                'suat_chieu' => [
                    'id'        => $suat->id,
                    'bat_dau'   => $suat->batdau,
                    'ket_thuc'  => $suat->ketthuc,
                ],
                'phong' => [
                    'id'         => $phong->id,
                    'id_rapphim' => $phong->id_rapphim,
                    'ten'        => $phong->ten,
                    'sohang_ghe' => $phong->sohang_ghe,
                    'socot_ghe'  => $phong->socot_ghe,
                    'loai_phongchieu'  => $phong->loai_phongchieu,
                    'soDoGhe'    => $soDoGhe,
                ],
                'rap' => $phong->rapChieuPhim ? [
                    'id'   => $phong->rapChieuPhim->id,
                    'ten'  => $phong->rapChieuPhim->ten,
                    'dia_chi' => $phong->rapChieuPhim->dia_chi ?? null,
                ] : null,
            ];
        }
        public function layPhongChieuTheoRap(){
            $dsPhongChieu = PhongChieu::where('id_rapphim', $_SESSION['UserInternal']['ID_RapPhim'])
                            ->get();
            return $dsPhongChieu;
        }
    }

?>