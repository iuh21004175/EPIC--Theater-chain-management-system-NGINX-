<?php
    namespace App\Controllers;
    use App\Services\Sc_PhongChieu;
    use function App\Core\view;
    class Ctrl_PhongChieu {
        // Các phương thức và thuộc tính của controller sẽ được định nghĩa ở đây
        public function index() {
            // Mã cho phương thức index
           return view('internal.phong-chieu');
        }
        public function themPhongChieu() {
            $service = new Sc_PhongChieu();
            try {
                if ($service->them()) {
                    // Chuyển hướng hoặc trả về phản hồi thành công
                    return [
                        'success' => true,
                        'message' => 'Thêm phòng chiếu thành công'
                    ];
                } else {
                    // Xử lý lỗi nếu cần
                    return [
                        'success' => false,
                        'message' => 'Thêm phòng chiếu thất bại'
                    ];
                }
            } catch (\Exception $e) {
                // Xử lý ngoại lệ nếu cần
                return [
                    'success' => false,
                    'message' => 'Đã xảy ra lỗi: ' . $e->getMessage()
                ];
            }
        }
        public function capNhatPhongChieu($argc) {
            $id = $argc['id'] ?? null;
            if (!$id) {
                return [
                    'success' => false,
                    'message' => 'ID phòng chiếu không hợp lệ'
                ];
            }
            $service = new Sc_PhongChieu();
            try {
                if ($service->capNhat($id)) {
                    return [
                        'success' => true,
                        'message' => 'Cập nhật phòng chiếu thành công'
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Cập nhật phòng chiếu thất bại'
                    ];
                }
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Đã xảy ra lỗi: ' . $e->getMessage()
                ];
            }
        }
        public function docPhongChieu($vars) {
            $tuKhoa = $vars['tu_khoa'] ?? null;
            $loaiPhongChieu = $vars['loai_phongchieu'] ?? null;
            $trangThai = $vars['trang_thai'] ?? null;
            $service = new Sc_PhongChieu();
            try {
                $danhSachPhongChieu = $service->doc($tuKhoa, $loaiPhongChieu, $trangThai);
                return [
                    'success' => true,
                    'data' => $danhSachPhongChieu
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Đã xảy ra lỗi: ' . $e->getMessage()
                ];
            }
        }

        public function chiTietPhongChieu($vars)
        {   

            $service = new Sc_PhongChieu();

            try {
                $id = is_array($vars) ? ($vars['id'] ?? null) : $vars;

                $chiTiet = $service->chiTiet((int) $id);

                return [
                    'success' => true,
                    'data'    => $chiTiet
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Không tìm thấy phòng chiếu: ' . $e->getMessage()
                ];
            }
        }

    }
?>