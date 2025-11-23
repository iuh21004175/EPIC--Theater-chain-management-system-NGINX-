<?php
    namespace App\Services;
    use App\Models\KhachHang;
    class Sc_KhachHang {
        public function doc(){
            return KhachHang::all();
        }
        public function findById($id){
            return KhachHang::find($id);
        }

        public function update($id, $data){
            $khachHang = KhachHang::find($id);
            if ($khachHang) {
                $khachHang->ho_ten = $data['ho_ten'] ?? $khachHang->ho_ten;
                $khachHang->email = $data['email'] ?? $khachHang->email;
                $khachHang->ngay_sinh = $data['ngay_sinh'] ?? $khachHang->ngay_sinh;
                $khachHang->gioi_tinh = $data['gioi_tinh'] ?? $khachHang->gioi_tinh;
                $khachHang->so_dien_thoai = $data['so_dien_thoai'] ?? $khachHang->so_dien_thoai;
                $khachHang->save();
            }
            return $khachHang;
        }

        public function updateTrangThai($id){
            $data = file_get_contents('php://input');
            $data = json_decode($data, true);

            $trangThaiMoi = $data['trang_thai'] ?? null;

            if ($trangThaiMoi === null) {
                return false;
            }

            $khachHang = KhachHang::find($id);
            if($khachHang){
                $khachHang->trang_thai = $trangThaiMoi;
                $khachHang->save();
                return true;
            }
            return false;
        }

    }
?>