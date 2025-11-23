<?php
    namespace App\Controllers;
    use App\Services\Sc_DinhVi;
    use function App\Core\view;

    class Ctrl_DinhVi
    {
        public function index(){
            $scDinhVi = new Sc_DinhVi();
            $dinhVi = $scDinhVi->getDinhVi();
            return view('internal.dinh-vi', ['dinhVi' => $dinhVi]);
        }
        
        public function updateDinhVi(){
            $scDinhVi = new Sc_DinhVi();
            try {
                $scDinhVi->updateDinhVi();
                return [
                    'success' => true,
                    'message' => 'Cập nhật thông tin định vị thành công!'
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Cập nhật thông tin định vị thất bại: ' . $e->getMessage()
                ];
            }
        }
    }
?>