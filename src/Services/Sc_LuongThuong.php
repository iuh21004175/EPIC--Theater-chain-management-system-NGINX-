<?php
namespace App\Services;
use App\Models\LuongThuong;
class Sc_LuongThuong {
    public function taoThuong(){
        $data = json_decode(file_get_contents('php://input'), true);

        $idNhanVien = $data['id_nhanvien'] ?? null;
        $thang = $data['thang'] ?? null;
        $thuong = $data['thuong'] ?? 0;

        if ($idNhanVien && $thang) {
            // Tự động kiểm tra: nếu có thì cập nhật, nếu không thì tạo mới
            return LuongThuong::updateOrCreate(
                ['id_nhanvien' => $idNhanVien, 'thang' => $thang],
                ['thuong' => $thuong]
            );
        } else {
            throw new \Exception("ID nhân viên và tháng không được để trống.");
        }
    }

    public function layThuongTheoNhanVien($idNhanVien, $thang = null){
        if (!$idNhanVien) {
            throw new \Exception("Thiếu ID nhân viên.");
        }

        $query = LuongThuong::where('id_nhanvien', $idNhanVien);

        if ($thang) {
            $query->where('thang', $thang); 
        }

        return $query->orderBy('thang', 'desc')->get();
    }

    public function layThuong1NhanVien($thang){

        $idNhanVien = $_SESSION['UserInternal']['ID'];

        return LuongThuong::where('id_nhanvien', $idNhanVien)
                        ->where('thang', $thang)
                        ->first();  
    }

    public function duyetLuongThuong(){
        $data = json_decode(file_get_contents('php://input'), true);

        $idNhanVien   = $data['id_nhanvien']   ?? null;
        $thang        = $data['thang']         ?? null;
        $soNgayCong   = $data['so_ngay_cong']  ?? 0;
        $soGioCong    = $data['so_gio_cong']   ?? 0;
        $tongThuNhap  = $data['tong_thu_nhap'] ?? 0;
        $trangThai    = $data['trang_thai']    ?? 0;
        $tongLuong    = $data['tong_luong']    ?? 0;
        if (!$idNhanVien || !$thang) {
            throw new \Exception("Thiếu ID nhân viên hoặc tháng.");
        }

        return LuongThuong::updateOrCreate(
            [
                'id_nhanvien' => $idNhanVien,
                'thang'       => $thang
            ],
            [
                'so_ngay_cong'  => $soNgayCong,
                'so_gio_cong'   => $soGioCong,
                'tong_luong'    => $tongLuong,
                'tong_thu_nhap' => $tongThuNhap,
                'trang_thai'    => $trangThai
            ]
        );
    }
}
?>