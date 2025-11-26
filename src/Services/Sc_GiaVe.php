<?php
    namespace App\Services;
    use App\Models\QuyTac_GiaVe;
    use App\Models\Ghe;
    use Carbon\Carbon;
    class Sc_GiaVe {
        // Properties and methods for the Sc_GiaVe class
        public function them(){
            $data = json_decode(file_get_contents('php://input'), true);
            // Xử lý logic thêm quy tắc giá vé
            return QuyTac_GiaVe::create([
                'ten' => $data['ten'],
                'loai_hanhdong' => $data['loai_hanhdong'], // 'Thiết lập giá' hoặc 'Cộng thêm tiền'
                'gia_tri' => $data['gia_tri'],
                'dieu_kien' => json_encode($data['dieu_kien']),
                'trang_thai' => $data['trang_thai'],
                'do_uu_tien' => $data['do_uu_tien'], // Độ ưu tiên từ 1 đến 5 với 1 là cao nhất
            ]);
        }
        public function doc(){
            return QuyTac_GiaVe::all();
        }
        public function sua($id){
            $data = json_decode(file_get_contents('php://input'), true);
            $quyTac = QuyTac_GiaVe::find($id);
            if($quyTac){
                $quyTac->ten = $data['ten'];
                $quyTac->loai_hanhdong = $data['loai_hanhdong']; // 'Thiết lập giá' hoặc 'Cộng thêm tiền'
                $quyTac->gia_tri = $data['gia_tri'];
                $quyTac->dieu_kien = json_encode($data['dieu_kien']);
                $quyTac->trang_thai = $data['trang_thai'];
                $quyTac->do_uu_tien = $data['do_uu_tien']; // Độ ưu tiên từ 1 đến 5 với 1 là cao nhất
                return $quyTac->save();
            }
            return false;
        }  

        public function tinhGiaGhe($loaiGheId, $ngay, $dinhDangPhim)
        {
            // Xác định loại ngày (Ngày thường / Cuối tuần)
            $date = Carbon::parse($ngay);
            $dayType = ($date->dayOfWeek === 0 || $date->dayOfWeek === 6) ? 'Cuối tuần' : 'Ngày thường';

            // Giá cơ bản theo loại ngày
            $giaCoBan = QuyTac_GiaVe::where('trang_thai', 1)
                ->whereRaw("JSON_CONTAINS(dieu_kien, ?, '$')", [
                    json_encode(['type' => 'day_type', 'value' => $dayType])
                ])
                ->value('gia_tri') ?? 0;

            // Giá cộng thêm theo định dạng phim (2D, 3D, IMAX,...)
            $giaPhim = QuyTac_GiaVe::where('trang_thai', 1)
                ->whereRaw("JSON_CONTAINS(dieu_kien, ?, '$')", [
                    json_encode(['type' => 'movie_format', 'value' => $dinhDangPhim])
                ])
                ->value('gia_tri') ?? 0;

            // Lấy thông tin loại ghế từ ID (do frontend chỉ gửi ID)
            $loaiGhe = Ghe::find($loaiGheId);
            if (!$loaiGhe) {
                throw new \Exception("Loại ghế không tồn tại");
            }

            // Giá phụ thu theo tên loại ghế (trùng với JSON value: "VIP", "NORMAL", "PREMIUM")
            $giaGhe = QuyTac_GiaVe::where('trang_thai', 1)
                ->whereRaw("JSON_CONTAINS(dieu_kien, ?, '$')", [
                    json_encode(['type' => 'seat_type', 'value' => $loaiGhe->ten])
                ])
                ->value('gia_tri') ?? 0;

            // Tổng giá
            $tong = $giaCoBan + $giaPhim + $giaGhe;
            return $tong;
        }
    }
?>