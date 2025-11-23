<?php
    namespace App\Controllers;
    use function App\Core\view;
    use App\Services\Sc_KhachHang;
    class Ctrl_KhachHang {
        function index() {
            return view('customer.thong-tin-ca-nhan');
        }
        function khachHang() {
            return view('internal.khach-hang');
        }
        public function docKhachHang(){
            $service = new Sc_KhachHang();
            try {
                $result = $service->doc();
                return [
                    'success' => true,
                    'data' => $result
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Lỗi khi tải danh sách khách hàng: ' . $e->getMessage()
                ];
            }
        }
        function thongTinKhachHang() {
            $scKhachHang = new Sc_KhachHang();
            $user = $_SESSION['user'];
            $userId = $user['id'] ?? null; 
            $khachHang = $userId ? $scKhachHang->findById($userId) : null;

            return [
                'success' => true,
                'data' => $khachHang
            ];
        }

        public function updateThongTinKhachHang() {
            header('Content-Type: application/json; charset=utf-8');

            $scKhachHang = new Sc_KhachHang();
            $user = $_SESSION['user'] ?? null;
            $userId = $user['id'] ?? null;
            
            // Lấy dữ liệu JSON từ request body
            $data = json_decode(file_get_contents('php://input'), true);
            try {
                if (!$userId) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Người dùng chưa đăng nhập.'
                    ]);
                    return;
                }
                if (!$data || !is_array($data)) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Dữ liệu cập nhật trống hoặc không hợp lệ.'
                    ]);
                    return;
                }
                $khachHang = $scKhachHang->update($userId, $data);

                if ($khachHang) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Cập nhật thông tin khách hàng thành công!',
                        'data' => $khachHang
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Không tìm thấy khách hàng để cập nhật.'
                    ]);
                }
            } catch (\Exception $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Đã xảy ra lỗi. Vui lòng thử lại sau.',
                    'error' => $e->getMessage()
                ]);
            }
            exit; // đảm bảo không xuất thêm content ngoài JSON
        }

         public function suaTrangThai($argc){
            $service = new Sc_KhachHang();
            try {
                $result = $service->updateTrangThai($argc['id']);
                if ($result) {
                    return [
                        'success' => true,
                        'message' => 'Cập nhật trạng thái thành công'
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Cập nhật trạng thái thất bại'
                    ];
                }
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Lỗi khi cập nhật trạng thái: ' . $e->getMessage()
                ];
            }
        }

    }
?>