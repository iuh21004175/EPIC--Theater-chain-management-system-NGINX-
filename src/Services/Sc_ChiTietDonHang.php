<?php
namespace App\Services;

use App\Models\ChiTietDonHang;
use App\Models\DonHang;
use App\Models\Ve;

class Sc_ChiTietDonHang {
    public function them() {
        $data = json_decode(file_get_contents('php://input'), true);

        $donhang_id = $data['donhang_id'] ?? null;
        $sanpham_id = $data['sanpham_id'] ?? null;
        $so_luong = $data['so_luong'] ?? null;
        $don_gia = $data['don_gia'] ?? null;
        $thanh_tien = $data['thanh_tien'] ?? null;

        $chitietdonhang = ChiTietDonHang::create([
            'donhang_id' => $donhang_id,
            'sanpham_id' => $sanpham_id,
            'so_luong' => $so_luong,
            'don_gia' => $don_gia,
            'thanh_tien' => $thanh_tien
        ]);

        if ($chitietdonhang) {
            return $chitietdonhang;
        }
        return false;
    }

    public function doc($id)
    {
        $donHang = DonHang::with([
            've.donHang.chiTietDonHang.sanPham',  // sản phẩm ăn uống
            've.suatChieu.phim',       // thông tin phim
            've.suatChieu.phongChieu.rapChieuPhim', // rạp
            've.ghe',                  // ghế
            've.khachhang',
            've.donHang.nhanVien.nguoiDungInternals'             // khách hàng
        ])->find($id);

        if (!$donHang) {
            return null;
        }

        return $donHang;
    }
}
