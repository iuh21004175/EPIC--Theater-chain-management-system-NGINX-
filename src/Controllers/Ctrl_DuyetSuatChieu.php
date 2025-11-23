<?php
    namespace App\Controllers;
    use function App\Core\view;
    use App\Services\Sc_RapPhim;
    use App\Services\Sc_SuatChieu;
    use Exception;

    class Ctrl_DuyetSuatChieu{
        public function index(){
            return view('internal.duyet-suat-chieu');
        }
        public function chiTiet($argc){
            $id = $argc['id'];
            try{
                $service = new Sc_RapPhim();
                $rapPhim = $service->docTheoID($id);
                if(!$rapPhim){
                    echo view("internal.404");
                    exit();
                }
                return view('internal.duyet-suat-chieu-chi-tiet', ['rapPhim' => $rapPhim]);
            }
            catch (\Exception $e) {
                echo view("internal.500");
                echo $e->getMessage();
                exit();
            }
            return view('internal.duyet-suat-chieu-chi-tiet');
        }
        public function tinhTrangSuatChieuTuan($argc){
            $idRap = $argc['id_rap'];
            $ngay = $_GET['ngay'] ?? null;
            try{
                return [
                    'status' => 'success',
                    'data' => (new Sc_SuatChieu())->tinhTrangSuatChieu($ngay, $idRap)
                ];
            }
            catch(Exception $e){
                return [
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
            }
        }
        public function docSuatChieuChuaXem($argc){
            $service = new Sc_SuatChieu();
            try{
                $result = $service->docSuatChieuChuaXem($argc['id_rap']);
                return [
                    'success' => true,
                    'message' => 'Đọc suất chiếu chưa xem thành công',
                    'data' => $result
                ];
            }
            catch(\Exception $e){
                return [
                    'success' => false,
                    'message' => 'Lỗi khi đọc suất chiếu chưa xem: ' . $e->getMessage()
                ];
            }
        }
        public function duyetSuatChieu($argc){
            $id = $argc['id'];
            try{
                (new Sc_SuatChieu())->duyetSuatChieu($id);
                return [
                    'success' => true,
                    'message' => 'Duyệt suất chiếu thành công'
                ];
            }
            catch(\Exception $e){
                return [
                    'success' => false,
                    'message' => 'Lỗi khi duyệt suất chiếu: ' . $e->getMessage()
                ];
            }
        }
        public function tuChoiSuatChieu($argc){
            $id = $argc['id'];
            try{
                (new Sc_SuatChieu())->tuChoiSuatChieu($id);
                return [
                    'success' => true,
                    'message' => 'Từ chối suất chiếu thành công'
                ];
            }
            catch(\Exception $e){
                return [
                    'success' => false,
                    'message' => 'Lỗi khi từ chối suất chiếu: ' . $e->getMessage()
                ];
            }
        }
    }
?>