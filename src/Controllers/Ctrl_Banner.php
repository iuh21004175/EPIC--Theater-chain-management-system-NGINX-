<?php
    namespace App\Controllers;
    use function App\Core\view;
    use App\Services\Sc_Banner;
    class Ctrl_Banner {
        // Properties and methods for the Ctrl_Banner class
        public function index() {
            // Code for the index method
           return view('internal.banner');
        }
        public function them(){
            try{
                $scBanner = new Sc_Banner();
                $scBanner->them();
                return [
                    'success' => true,
                    'message' => 'Thêm banner thành công!',
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Đã xảy ra lỗi. Vui lòng thử lại sau.',
                    'error' => $e->getMessage()
                ];
            }
        }
        public function suaAnh($argc){
            try{
                $scBanner = new Sc_Banner();
                $scBanner->suaAnh($argc['id']);
                return [
                    'success' => true,
                    'message' => 'Cập nhật ảnh banner thành công!'
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Đã xảy ra lỗi. Vui lòng thử lại sau.',
                    'error' => $e->getMessage()
                ];
            }
        }
        public function xoa($argc){
            try{
                $scBanner = new Sc_Banner();
                $scBanner->xoa($argc['id']);
                return [
                    'success' => true,
                    'message' => 'Xoá banner thành công!',
                    'redirect' => './banner'
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Đã xảy ra lỗi. Vui lòng thử lại sau.',
                    'error' => $e->getMessage()
                ];
            }
        }
        public function thayDoiTrangThai($argc){
            try{
                $scBanner = new Sc_Banner();
                $trangThai = $scBanner->thayDoiTrangThai($argc['id']);
                return [
                    'success' => true,
                    'message' => 'Cập nhật trạng thái banner thành công!',
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Đã xảy ra lỗi. Vui lòng thử lại sau.',
                    'error' => $e->getMessage()
                ];
            }
        }
        public function docSideShow(){
            try{
                $scBanner = new Sc_Banner();
                $banners = $scBanner->docSideShow();
                return [
                    'success' => true,
                    'data' => $banners
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Đã xảy ra lỗi. Vui lòng thử lại sau.',
                    'error' => $e->getMessage()
                ];
            }
        }
        public function docTatCa(){
            try{
                $scBanner = new Sc_Banner();
                $banners = $scBanner->docTatCa();
                return [
                    'success' => true,
                    'data' => $banners
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Đã xảy ra lỗi. Vui lòng thử lại sau.',
                    'error' => $e->getMessage()
                ];
            }
        }
        public function sapXep(){
            try{
                $scBanner = new Sc_Banner();
                $scBanner->capNhatSideShow();
                return [
                    'success' => true,
                    'message' => 'Cập nhật thứ tự banner thành công!',
                ];
            }catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Đã xảy ra lỗi. Vui lòng thử lại sau.',
                    'error' => $e->getMessage()
                ];
            }
        }
    }
?>