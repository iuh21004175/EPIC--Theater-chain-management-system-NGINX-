<?php
namespace App\Services;

use App\Models\DanhGia;
use Carbon\Carbon;
class Sc_DanhGia {
    public function docTheoPhim($phimId)
    {
        return DanhGia::with('khachHang')
            ->where('phim_id', $phimId)
            ->get();
    }

    public function them(){
        $user = $_SESSION['user'];
        $user_id = $user['id'];

        $data = json_decode(file_get_contents('php://input'), true);
        $phim_id = $data['phim_id'] ?? null;
        $so_sao = $data['so_sao'] ?? null;
        $cmt= $data['cmt'] ?? null;

        $danhGia = DanhGia::create([
            'khachhang_id' => $user_id,
            'phim_id' => $phim_id,
            'so_sao' => $so_sao,
            'cmt' => $cmt
        ]);

        if ($danhGia) {
            return $danhGia;
        }
        return false;
    }

    public function sua($id){
        if (!$id) {
            throw new \Exception("Thiếu ID đánh giá cần sửa");
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $so_sao = $data['so_sao'] ?? null;
        $cmt = $data['cmt'] ?? null;

        $danhGia = DanhGia::find($id);
        if (!$danhGia) {
            throw new \Exception("Không tìm thấy đánh giá với ID: $id");
        }

        $danhGia->so_sao = $so_sao ?? $danhGia->so_sao;
        $danhGia->cmt = $cmt ?? $danhGia->cmt;
        $danhGia->save();

        return $danhGia;
    }

    public function xoa($id){
        if (!$id) {
            throw new \Exception("Thiếu ID đánh giá cần xóa");
        }

        $danhGia = DanhGia::find($id);
        if (!$danhGia) {
            throw new \Exception("Không tìm thấy đánh giá với ID: $id");
        }

        $danhGia->delete();
    }


}
?>