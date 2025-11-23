<?php
namespace App\Services;
use App\Models\MuaPhim;
class Sc_MuaPhim {
    public function daMua($khachHangId)
    {
        $now = date('Y-m-d H:i:s'); // ngày giờ hiện tại

        $mua = MuaPhim::where('khach_hang_id', $khachHangId)
                    ->where('ngay_het_han', '>', $now) // chỉ lấy những vé còn hạn
                    ->first();

        return $mua ? $mua->trang_thai : 0; // nếu tồn tại và còn hạn thì trả về trạng thái, không thì 0
    }
    public function them() {
        $user = $_SESSION['user'];
        $data = json_decode(file_get_contents('php://input'), true);

        $don_hang_id = $data['don_hang_id'] ?? null;
        $khach_hang_id = $user['id'];
        $trang_thai = $data['trang_thai'] ?? 1;
        $so_tien = $data['so_tien'] ?? 0;
        $phuong_thuc = $data['phuong_thuc'] ?? 1;
        $now = date('Y-m-d H:i:s');

        $muaPhim = MuaPhim::create([
            'khach_hang_id' => $khach_hang_id,
            'so_tien' => $so_tien,
            'trang_thai' => $trang_thai,
            'phuong_thuc' => $phuong_thuc,
            'don_hang_id' => $don_hang_id,
            'ngay_het_han' => $now
        ]);

        if ($muaPhim) {
            return $muaPhim;
        }
        return false;
    }
}
       