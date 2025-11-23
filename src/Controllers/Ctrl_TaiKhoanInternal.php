<?php
    namespace App\Controllers;
    use App\Services\Sc_TaiKhoanInternal;
    use function App\Core\view;
    class Ctrl_TaiKhoanInternal {
        // Properties and methods for the Ctrl_TaiKhoanInternal class
        public function index() {
            // Code for the index method
           return view('internal.tai-khoan');
        }
        public function themTaiKhoan(){
            $service = new Sc_TaiKhoanInternal();
            try {
                $result = $service->them();
                if($result){
                    return [
                        'success' => true,
                        'message' => 'Tạo tài khoản quản lý rạp thành công'
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Tạo tài khoản quản lý rạp thất bại'
                    ];
                }
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Lỗi: ' . $e->getMessage()
                ];
            }
        }
        public function docTaiKhoan($argc = null){
            $id = $argc['id'] ?? null;
            $service = new Sc_TaiKhoanInternal();
            $taiKhoans = $service->doc($id);
            return [
                'success' => true,
                'data' => $taiKhoans
            ];
        }
        public function phanCongTaiKhoan($argc){
            $service = new Sc_TaiKhoanInternal();
            try {
                $result = $service->phanCong($argc['id']);
                if($result){
                    return [
                        'success' => true,
                        'message' => 'Phân công tài khoản quản lý rạp thành công'
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Phân công tài khoản quản lý rạp thất bại'
                    ];
                }
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Lỗi: ' . $e->getMessage()
                ];
            }
        }
        public function suaTaiKhoan($argc){
            $service = new Sc_TaiKhoanInternal();
            try {
                $result = $service->sua($argc['id']);
                if($result){
                    return [
                        'success' => true,
                        'message' => 'Cập nhật thông tin tài khoản quản lý rạp thành công'
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Cập nhật thông tin tài khoản quản lý rạp thất bại'
                    ];
                }
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Lỗi: ' . $e->getMessage()
                ];
            }
        }
    }
?>