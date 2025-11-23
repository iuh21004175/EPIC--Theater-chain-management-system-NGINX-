<?php
    namespace App\Controllers;
    use App\Services\Sc_NhanVien;
    use function App\Core\view;
    class Ctrl_NhanVien {
        // Các phương thức và thuộc tính của controller sẽ được định nghĩa ở đây
        public function index() {
            // Mã cho phương thức index
           return view('internal.nhan-vien');
        }
        public function themNhanVien(){
            // Code to handle adding a new employee
            $service = new Sc_NhanVien();
            try {
                $result = $service->them();
                if ($result) {
                    return [
                        'success' => true,
                        'message' => 'Thêm nhân viên thành công'
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Thêm nhân viên thất bại'
                    ];
                }
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Lỗi khi thêm nhân viên: ' . $e->getMessage()
                ];
            }
        }
        public function docNhanVien() {
            $service = new Sc_NhanVien();
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
            
            try {
                $result = $service->doc($page, $perPage);
                return [
                    'success' => true,
                    'data' => $result['data'],
                    'pagination' => $result['pagination']
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Lỗi khi tải danh sách nhân viên: ' . $e->getMessage()
                ];
            }
        }
        public function suaNhanVien($argc){
            $service = new Sc_NhanVien();
            try {
                $result = $service->sua($argc['id']);
                if ($result) {
                    return [
                        'success' => true,
                        'message' => 'Cập nhật nhân viên thành công'
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Cập nhật nhân viên thất bại'
                    ];
                }
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Lỗi khi cập nhật nhân viên: ' . $e->getMessage()
                ];
            }
        }
        public function thayDoiTrangThai($argc){
            $id = $argc['id'] ?? 0;
            $service = new Sc_NhanVien();
            if($service->trangThai($id)){
                return [
                    'success' => true,
                    'message' => 'Đã thay đổi trạng thái nhân viên thành công'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Không thể thay đổi trạng thái nhân viên'
                ];
            }
        }
    }
?>