<?php
    namespace App\Controllers;
    use App\Services\Sc_ServerChamCong;
    use function App\Core\view;

    class Ctrl_ServerChamCong
    {
        public function index(){
            $scServerChamCong = new Sc_ServerChamCong();
            $serverChamCong = $scServerChamCong->getServerChamCong();
            return view('internal.server-cham-cong', ['serverChamCong' => $serverChamCong]);
        }
        
        public function updateServerChamCong(){
            $scServerChamCong = new Sc_ServerChamCong();
            try {
                $scServerChamCong->updateServerChamCong();
                return [
                    'success' => true,
                    'message' => 'Cập nhật thông tin server chấm công thành công!'
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Cập nhật thông tin server chấm công thất bại: ' . $e->getMessage()
                ];
            }
        }
    }
?>