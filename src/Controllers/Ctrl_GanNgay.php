<?php
    namespace App\Controllers;
    use App\Services\Sc_GanNgay;
    use function App\Core\view;
    class Ctrl_GanNgay {
        // Properties and methods for the Ctrl_GanNgay class
        public function index() {
            // Code for the index method
           return view('internal.gan-ngay');
        }
        public function ganNgay(){
            $service = new Sc_GanNgay();
            try {
                if($service->ganNgay()){
                    return [
                        'success' => true,
                        'message' => 'Gán ngày thành công',
                    ];
                }
                else{
                    return [
                        'success' => false,
                        'message' => 'Gán ngày thất bại',
                    ];
                }
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Lỗi khi gán ngày: ' . $e->getMessage(),
                ];
            }
        }
        public function doc($argc){
            $service = new Sc_GanNgay();
            try {
                $ngay = $service->doc($argc['thang'], $argc['nam']);
                return [
                        'success' => true,
                        'data' => $ngay,
                    ];
                } catch (\Exception $e) {
                    return [
                        'success' => false,
                        'message' => 'Lỗi khi đọc ngày: ' . $e->getMessage(),
                    ];
                }
        }

        
    }
?>