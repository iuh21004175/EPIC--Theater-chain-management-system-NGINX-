<?php
    namespace App\Controllers;
    use App\Services\Sc_RapPhim;
    use function App\Core\view;
    class Ctrl_RapPhim {
        public function index() {
            return view('internal.rap-phim');
        }
        public function rapKhachHang() {
            return view('customer.rap');
        }
        public function themRapPhim() {
            // Code to handle adding a new cinema
            $service = new Sc_RapPhim();
            try {
                $result = $service->them();
                if($result){
                    return [
                        'success' => true,
                        'message' => 'Tạo rạp phim thành công'
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Tạo rạp phim thất bại'
                    ];
                }
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Lỗi: ' . $e->getMessage()
                ];
            }
        }
        public function docRapPhim() {
            $service = new Sc_RapPhim();
            $rapPhims = $service->doc();
            return [
                'success' => true,
                'data' => $rapPhims
            ];
        }
        public function docRapPhimTheoID($id) {
            $service = new Sc_RapPhim();
            $rapPhim = $service->docTheoID($id);
            return [
                'success' => true,
                'data' => $rapPhim
            ];
        }
        public function thayDoiTrangThai($argc) {
            $service = new Sc_RapPhim();
            $result = $service->trangThai($argc['id']);
            try {
                if($result){
                    return [
                        'success' => true,
                        'message' => 'Thay đổi trạng thái rạp phim thành công'
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Rạp phim không tồn tại'
                    ];
                }
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Lỗi: ' . $e->getMessage()
                ];
            }
        }
        public function suaRapPhim($argc) {
            $service = new Sc_RapPhim();
            $result = $service->sua($argc['id']);
            try {
                if($result){
                    return [
                        'success' => true,
                        'message' => 'Cập nhật thông tin rạp phim thành công'
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Rạp phim không tồn tại'
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