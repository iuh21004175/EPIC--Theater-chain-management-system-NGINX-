<?php
namespace App\Controllers;

use App\Services\Sc_ChiTietDonHang;

class Ctrl_ChiTietDonHang {
    public function themChiTietDonHang() {
        header('Content-Type: application/json'); 
        $service = new Sc_ChiTietDonHang();
        try {
            $chitietdonhang = $service->them();
            if ($chitietdonhang ) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Thêm chi tiết đơn hàng thành công',
                    'data' => $chitietdonhang 
                ]);
                exit;
            }
            echo json_encode([
                'success' => false, 
                'message' => 'Thêm chi tiết đơn hàng thất bại'
            ]);
            exit;
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false, 
                'message' => 'Lỗi: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    public function docChiTietDonHang($id)
    {
        $service = new Sc_ChiTietDonHang();
        try {
            $result = $service->doc($id); 
            return [
                'success' => true,
                'data' => $result
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Lỗi khi tải chi tiết phim: ' . $e->getMessage()
            ];
        }
    }
}
