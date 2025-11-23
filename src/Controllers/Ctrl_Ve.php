<?php
namespace App\Controllers;

use App\Services\Sc_Ve;

class Ctrl_Ve {
    public function themVe() {
        header('Content-Type: application/json'); 
        $service = new Sc_Ve();
        try {
            $ve = $service->them();
            if ($ve) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Thêm vé thành công',
                    'data' => $ve
                ]);
                exit;
            }
            echo json_encode([
                'success' => false, 
                'message' => 'Thêm vé thất bại'
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

    public function capNhatTrangThai(){
        $body = json_decode(file_get_contents('php://input'), true);
        $id = $body['donhang_id'] ?? null;
        if(!$id){ echo json_encode(['success'=>false,'message'=>'Thiếu id vé']); exit; }

        $service = new Sc_Ve();
        $result = $service->capNhat($id); 
        echo json_encode(['success'=>$result,'message'=>$result?'Cập nhật vé thành công':'Thất bại']);
        exit;
    }

    public function doctop4PhimTheoVe(){
        $service = new Sc_Ve();
        try {
            $result = $service->top4PhimTheoVe();
            return [
                'success' => true,
                'data' => $result
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Lỗi khi tải danh sách thể loại: ' . $e->getMessage()
            ];
        }
    }
    
}
