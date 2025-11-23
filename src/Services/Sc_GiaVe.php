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
            // 1. Xác định ngày dựa trên URL
            $date = Carbon::parse($ngay);
            $dayType = ($date->dayOfWeek === 0 || $date->dayOfWeek === 6) ? 'Cuối tuần' : 'Ngày thường';

            // 2. Giá cơ bản theo dayType
            $giaCoBan = QuyTac_GiaVe::where('trang_thai', 1)
                ->whereRaw("JSON_CONTAINS(dieu_kien, ?, '$')", 
                            [json_encode(['type'=>'day_type','value'=>$dayType])])
                ->value('gia_tri') ?? 0;

            // 3. Giá cộng thêm định dạng phim
            $giaPhim = 0;
            // Kiểm tra trực tiếp giá trị của tham số, không cần kiểm tra sự tồn tại nữa
            $giaPhim = QuyTac_GiaVe::where('trang_thai', 1)
                ->whereRaw("JSON_CONTAINS(dieu_kien, ?, '$')", 
                            [json_encode(['type'=>'movie_format','value'=>$dinhDangPhim])])
                ->value('gia_tri') ?? 0;
            
            // 4. Phụ thu theo loại ghế
            $loaiGhe = Ghe::find($loaiGheId);
            if (!$loaiGhe) {
                throw new \Exception("Loại ghế không tồn tại");
            }
            $phuThu = $loaiGhe->phu_thu ?? 0;

            // 5. Tổng giá
            return $giaCoBan + $giaPhim + $phuThu;
        }
    }
?>