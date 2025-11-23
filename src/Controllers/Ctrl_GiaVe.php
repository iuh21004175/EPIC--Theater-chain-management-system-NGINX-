<?php
    namespace App\Controllers;
    use App\Services\Sc_GiaVe;
    use function App\Core\view;
    class Ctrl_GiaVe{
        public function index(){
            return view('internal.gia-ve');
        }
        public function themQuyTac(){
            $service = new Sc_GiaVe();
            try {
                if($service->them()){
                    return [
                        'success' => true,
                        'message' => 'Thêm quy tắc giá vé thành công',
                    ];
                }
                else{
                    return [
                        'success' => false,
                        'message' => 'Thêm quy tắc giá vé thất bại',
                    ];
                }
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Lỗi khi thêm quy tắc giá vé: ' . $e->getMessage(),
                ];
            }
        }
        public function docQuyTac(){
            $service = new Sc_GiaVe();
            try {
                $data = $service->doc();
                return [
                    'success' => true,
                    'data' => $data,
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Lỗi khi đọc quy tắc giá vé: ' . $e->getMessage(),
                ];
            }
        }
        public function suaQuyTac($argc){
            $service = new Sc_GiaVe();
            $id = $argc['id'];
            try {
                if($service->sua($id)){
                    return [
                        'success' => true,
                        'message' => 'Sửa quy tắc giá vé thành công',
                    ];
                }
                else{
                    return [
                        'success' => false,
                        'message' => 'Sửa quy tắc giá vé thất bại',
                    ];
                }
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Lỗi khi sửa quy tắc giá vé: ' . $e->getMessage(),
                ];
            }
        }

        public function docGiaVe($params)
        {
            header('Content-Type: application/json');
            $loaiGheId = $params['loaiGheId'] ?? null;
            $ngay = $params['ngay'] ?? null;
            $dinhDangPhim = $params['dinhDangPhim'] ?? null;

            $service = new Sc_GiaVe();
            try {
                if (!$loaiGheId) throw new \Exception("Thiếu tham số loaiGheId");
                $data = $service->tinhGiaGhe($loaiGheId, $ngay, $dinhDangPhim);
                echo json_encode(['success'=>true,'data'=>$data]);
            } catch (\Exception $e) {
                echo json_encode(['success'=>false,'message'=>'Lỗi khi đọc quy tắc giá vé: '.$e->getMessage()]);
            }
            exit;
        }
    }
?>