<?php
    namespace App\Controllers;
    use App\Services\Sc_TheQuaTang;
    use function App\Core\view;
    class Ctrl_TheQuaTang {
        public function index() {
           return view('customer.the-qua-tang');
        }

        public function docTheQuaTang(){
            $service = new Sc_TheQuaTang();
        try {
            $theQuaTang = $service->doc();
            if ($theQuaTang) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Lấy thẻ quà tặng thành công',
                    'data' => $theQuaTang
                ]);
                exit;
            }
            echo json_encode([
                'success' => false, 
                'message' => 'Không tìm thấy thẻ quà tặng'
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

        public function taoTheQuaTang(){
            $service = new Sc_TheQuaTang();
        try {
            $theQuaTang = $service->tao();
            if ($theQuaTang) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Tạo thẻ quà tặng thành công'
                ]);
                exit;
            }
            echo json_encode([
                'success' => false, 
                'message' => 'Tạo thẻ quà tặng không thành công'
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

        public function suaGiaTriThe($argc) {
            $service = new Sc_TheQuaTang();
            try {
                // Lấy dữ liệu JSON từ request
                $data = json_decode(file_get_contents("php://input"), true);

                // Kiểm tra dữ liệu có id không
                if (!isset($data['id'])) {
                    return [
                        'success' => false,
                        'message' => 'Thiếu tham số id'
                    ];
                }

                // Gọi service và truyền cả data
                $result = $service->sua($data);

                if ($result) {
                    return [
                        'success' => true,
                        'message' => 'Cập nhật giá trị thành công'
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Cập nhật giá trị thất bại'
                    ];
                }
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Lỗi khi cập nhật giá trị: ' . $e->getMessage()
                ];
            }
        }


    }
?>