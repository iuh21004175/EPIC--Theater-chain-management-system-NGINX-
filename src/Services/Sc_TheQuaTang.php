<?php
namespace App\Services;

use App\Models\TheQuaTang;
use Carbon\Carbon;
class Sc_TheQuaTang {
    public function doc() {
        $user = $_SESSION['user'];
        $idKhachHang = $user['id'];

        $now = Carbon::now();

        $theQuaTang = TheQuaTang::where('khach_hang_id', $idKhachHang)
            ->where('trang_thai', 1)
            ->where('gia_tri', '>', 0)
            ->where('ngay_het_han', '>', $now) // chỉ lấy vé còn hạn
            ->get();

        return $theQuaTang;
    }
    public function sua($data){
        $theQuaTang = TheQuaTang::find($data['id']);
        if ($theQuaTang) {
            $theQuaTang->gia_tri = $data['gia_tri'] ?? $theQuaTang->gia_tri;
            $theQuaTang->save();
        }
        return $theQuaTang;
    }

    public function tao() {
        $user = $_SESSION['user'];
        $idKhachHang = $user['id'];
        $data = json_decode(file_get_contents('php://input'), true);
        $ten = 'Thẻ hoàn vé';
        $giaTri = $data['gia_tri'] ?? null;
        $id_donhang = $data['id_donhang'] ?? null;
        $ngay_phat_hanh = date('Y-m-d H:i:s');
        $ngay_het_han = date('Y-m-d H:i:s', strtotime('+30 days')); // hết hạn sau 30 ngày

        $theQuaTang = TheQuaTang::create([
            'khach_hang_id' => $idKhachHang,
            'ten' => $ten,
            'gia_tri' => $giaTri,
            'id_donhang' => $id_donhang,
            'trang_thai' => 1,
            'ngay_phat_hanh' => $ngay_phat_hanh,
            'ngay_het_han' => $ngay_het_han,
            'ghi_chu' => ''
        ]);

        return $theQuaTang;
    }

}
?>