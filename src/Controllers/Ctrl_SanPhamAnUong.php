<?php
    namespace App\Controllers;
    use App\Services\Sc_SanPham;
    use function App\Core\view;

    class Ctrl_SanPhamAnUong{
        public function index(){
            $service = new Sc_SanPham();
            $danhMucs = $service->docDanhMuc();
            return view('internal.san-pham-an-uong', ['danhMucs' => $danhMucs]);
        }
        public function sanPham(){
            return view('customer.san-pham');
        }
        public function themDanhMuc(){
            $service = new Sc_SanPham();
            try {
                $result = $service->themDanhMuc();
                if($result){
                    return ['success' => true, 'message' => 'Thêm danh mục thành công'];
                } else {
                    return ['success' => false, 'message' => 'Thêm danh mục thất bại'];
                }
            } catch (\Exception $e) {
                return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
            }
        }
        public function docDanhMuc(){
            $service = new Sc_SanPham();
            try {
                $result = $service->docDanhMuc();
                return ['success' => true, 'data' => $result];
            } catch (\Exception $e) {
                return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
            }
        }
        public function suaDanhMuc($argc){
            $service = new Sc_SanPham();
            $id = $argc['id'];
            try {
                $result = $service->suaDanhMuc($id);
                if($result){
                    return ['success' => true, 'message' => 'Sửa danh mục thành công'];
                } else {
                    return ['success' => false, 'message' => 'Sửa danh mục thất bại'];
                }
            } catch (\Exception $e) {
                return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
            }
        }
        public function themSanPham(){
            $service = new Sc_SanPham();
            try {
                $result = $service->themSanPham();
                if($result){
                    return ['success' => true, 'message' => 'Thêm sản phẩm thành công', 'data' => $result];
                } else {
                    return ['success' => false, 'message' => 'Thêm sản phẩm thất bại'];
                }
            } catch (\Exception $e) {
                return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
            }
        }

        public function docSanPham($argc){
            $service = new Sc_SanPham();
            try {
                $result = $service->docSanPham($argc['id'] ?? null, $_GET['tukhoa'] ?? null, $_GET['danh_muc_id'] ?? null);
                return ['success' => true, 'data' => $result];
            } catch (\Exception $e) {
                return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
            }
        }
        public function suaSanPham($argc){
            $service = new Sc_SanPham();
            $id = $argc['id'];
            try {
                $service->suaSanPham($id);
                return ['success' => true, 'message' => 'Sửa sản phẩm thành công'];
            } catch (\Exception $e) {
                return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
            }
        }
         public function docSanPhamTheoRap($argc){
            $service = new Sc_SanPham();
            try {
                $result = $service->docSanPhamTheoRap($argc['id']);
                return ['success' => true, 'data' => $result];
            } catch (\Exception $e) {
                return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
            }
        }
    }
?>