<?php
    namespace App\Controllers;
    use App\Services\Sc_Ghe;
    use function App\Core\view;
    class Ctrl_Ghe {
        // Properties and methods for the Ctrl_Ghe class
        public function index() {
            // Code for the index method
           return view('internal.ghe');
        }
        public function soDoGhe() {
            // Code for the index method
           return view('customer.so-do-ghe');
        }
        public function themGhe(){
            $service = new Sc_Ghe();
            try {
                $result = $service->them();
                if($result){
                    return ['success' => true, 'message' => 'Thêm ghế thành công'];
                } else {
                    return ['success' => false, 'message' => 'Thêm ghế thất bại'];
                }
            } catch (\Exception $e) {
                return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
            }
        }
        public function docGhe(){
            $service = new Sc_Ghe();
            try {
                $result = $service->doc();
                return ['success' => true, 'data' => $result];
            } catch (\Exception $e) {
                return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
            }
        }
        public function suaGhe($argc){
            $service = new Sc_Ghe();
            $id = $argc['id'];
            try {
                $result = $service->sua($id);
                return ['success' => true, 'data' => $result];
            } catch (\Exception $e) {
                return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
            }
        }
    }
?>