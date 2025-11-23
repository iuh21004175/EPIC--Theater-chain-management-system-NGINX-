<?php
namespace App\Controllers;

use App\Services\Sc_LuongThuong;

class Ctrl_LuongThuong {
    public function taoThuong(){
        $service = new Sc_LuongThuong();
        try {
            $result = $service->taoThuong();

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Thêm thưởng thành công'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Thêm thưởng thất bại'
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Lỗi khi thêm thưởng: ' . $e->getMessage()
            ];
        }
    }

    public function layThuong($argc){
        $service = new Sc_LuongThuong();

        try {
            $id = $argc['id']; 
            $thang = $_GET['thang'] ?? null;

            $result = $service->layThuongTheoNhanVien($id, $thang);

            return [
                'success' => true,
                'data' => $result
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Lỗi khi lấy thưởng: ' . $e->getMessage()
            ];
        }
    }

    public function layThuong1NhanVien($argc){
        $service = new Sc_LuongThuong();

        try {
            $thang = $_GET['thang'] ?? null;

            $result = $service->layThuong1NhanVien( $thang);

            return [
                'success' => true,
                'data' => $result
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Lỗi khi lấy thưởng: ' . $e->getMessage()
            ];
        }
    }

    public function duyetLuongThuong(){
        $service = new Sc_LuongThuong();
        try {
            $result = $service->duyetLuongThuong();

            return [
                'success' => true,
                'message' => 'Cập nhật thưởng thành công',
                'data'    => $result
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Lỗi khi cập nhật thưởng: ' . $e->getMessage()
            ];
        }
    }

}
?>