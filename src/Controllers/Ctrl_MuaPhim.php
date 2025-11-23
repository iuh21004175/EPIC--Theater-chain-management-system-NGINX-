<?php
namespace App\Controllers;

use App\Services\Sc_MuaPhim;
use function App\Core\view;

class Ctrl_MuaPhim {
    public function docTrangThaiMuaPhim() {
        $khachHangId = $_GET['khachHangId'] ?? null;
        $phimId = $_GET['phimId'] ?? null;

        $service = new Sc_MuaPhim();
        try {
            $trangThai = $service->daMua($khachHangId); 
            return ['success' => true, 'trang_thai' => $trangThai];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }

     public function themMuaPhim() {
        header('Content-Type: application/json'); 
        $service = new Sc_MuaPhim();
        try {
            $muaphim = $service->them();
            if ($muaphim) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Thêm đơn mua phim thành công',
                    'data' => $muaphim 
                ]);
                exit;
            }
            echo json_encode([
                'success' => false, 
                'message' => 'Thêm đơn mua phim thất bại'
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
}
?>
