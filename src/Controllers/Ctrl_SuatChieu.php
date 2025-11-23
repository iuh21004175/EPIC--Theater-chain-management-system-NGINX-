<?php
    namespace App\Controllers;
    use App\Services\Sc_SuatChieu;
    use function App\Core\view;
    class Ctrl_SuatChieu{
        public function index(){
            return view('internal.suat-chieu');
        }
        public function themSuatChieu(){
            $service = new Sc_SuatChieu();
            try {
                $service->them();
                return [
                    'success' => true,
                    'message' => 'Thêm suất chiếu thành công',
                ];
            }
            catch (\Exception $e) {
                
                return [
                    'success' => false,
                    'message' => 'Lỗi khi thêm suất chiếu: ' . $e->getMessage()
                ];
            }
        }
        public function taoKhungGioGoiY(){
            $service = new Sc_SuatChieu();
            try {
                $result = $service->taoKhungGioGoiY($_GET['ngay'], (int) $_GET['id_phong_chieu'], (int) $_GET['thoi_luong_phim']);
                if ($result) {
                    return [
                        'success' => true,
                        'message' => 'Tạo khung giờ gợi ý thành công',
                        'data' => $result
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Tạo khung giờ gợi ý thất bại',
                    ];
                }
            }
            catch (\Exception $e) {
                
                return [
                    'success' => false,
                    'message' => 'Lỗi khi tạo khung giờ gợi ý: ' . $e->getMessage()
                ];
            }
        }
        public function kiemTraSuatChieuHopLe(){
            $service = new Sc_SuatChieu();
            try {
                $result = $service->kiemTraSuatChieu($_GET['batdau'], (int) $_GET['id_phong_chieu'], (int) $_GET['thoi_luong_phim']);
                if ($result) {
                    return [
                        'success' => true,
                        'message' => 'Suất chiếu hợp lệ',
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Suất chiếu không hợp lệ',
                    ];
                }
            }
            catch (\Exception $e) {
                
                return [
                    'success' => false,
                    'message' => 'Lỗi khi kiểm tra suất chiếu: ' . $e->getMessage()
                ];
            }
        }
        public function docSuatChieu(){
            $service = new Sc_SuatChieu();
            $ngay = $_GET['ngay'] ?? date('Y-m-d');
            try{
                $result = $service->doc($ngay);
                return [
                    'success' => true,
                    'message' => 'Đọc suất chiếu thành công',
                    'data' => $result
                ];
            }
            catch(\Exception $e){
                return [
                    'success' => false,
                    'message' => 'Lỗi khi đọc suất chiếu: ' . $e->getMessage()
                ];
            }
        }
        public function docSuatChieuKH(){
            $service = new Sc_SuatChieu();
            $ngay = $_GET['ngay'] ?? date('Y-m-d');
            $idPhim = $_GET['id_phim'] ?? null;
            $idRap = $_GET['id_rapphim'] ?? null;

            try{
                $result = $service->docSuatChieuKH($ngay, $idPhim, $idRap);
                return [
                    'success' => true,
                    'message' => 'Đọc suất chiếu khách hàng thành công',
                    'data' => $result
                ];
            }
            catch(\Exception $e){
                return [
                    'success' => false,
                    'message' => 'Lỗi khi đọc suất chiếu khách hàng: ' . $e->getMessage()
                ];
            }
        }

        public function docPhimTheoRapKH($idRap)
        {
            $service = new Sc_SuatChieu();

            // Lấy ngày từ query string, mặc định hôm nay
            $ngay = $_GET['ngay'] ?? date('Y-m-d');

            try {
                // Gọi service để lấy danh sách phim theo rạp và ngày
                $phimList = $service->docPhimTheoRap($ngay, $idRap);

                return [
                    'success' => true,
                    'message' => 'Đọc phim theo rạp thành công',
                    'data' => $phimList
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Lỗi khi đọc phim theo rạp: ' . $e->getMessage(),
                    'data' => []
                ];
            }
        }

        public function suaSuatChieu($id){
            $service = new Sc_SuatChieu();
            try{
                $service->sua($id);
                return [
                    'success' => true,
                    'message' => 'Sửa suất chiếu thành công',
                ];
            }
            catch(\Exception $e){
                return [
                    'success' => false,
                    'message' => 'Lỗi khi sửa suất chiếu: ' . $e->getMessage()
                ];
            }
        }
        public function xoaSuatChieu($argc){
            $service = new Sc_SuatChieu();
            try{
                $service->xoa($argc['id']);
                return [
                    'success' => true,
                    'message' => 'Xóa suất chiếu thành công',
                ];
            }
            catch(\Exception $e){
                return [
                    'success' => false,
                    'message' => 'Lỗi khi xóa suất chiếu: ' . $e->getMessage()
                ];
            }
        }
        
        /**
         * POST /api/suat-chieu/{id}/hoan-tac
         * Hoàn tác suất chiếu: Xóa suất chiếu và cập nhật trạng thái trong kế hoạch về chờ duyệt
         */
        public function hoanTacSuatChieu($argc){
            $service = new Sc_SuatChieu();
            try{
                $service->hoanTac($argc['id']);
                return [
                    'success' => true,
                    'message' => 'Hoàn tác suất chiếu thành công',
                ];
            }
            catch(\Exception $e){
                return [
                    'success' => false,
                    'message' => 'Lỗi khi hoàn tác suất chiếu: ' . $e->getMessage()
                ];
            }
        }
        public function docNhatKy(){
            $service = new Sc_SuatChieu();
            try{
                $idRap = $_REQUEST['idRap'] ?? null;
                $result = $service->docNhatKy($idRap);
                return [
                    'success' => true,
                    'message' => 'Đọc nhật ký suất chiếu thành công',
                    'data' => $result
                ];
            }
            catch(\Exception $e){
                return [
                    'success' => false,
                    'message' => 'Lỗi khi đọc nhật ký suất chiếu: ' . $e->getMessage()
                ];
            }
        }
        public function quanLyRapXemNhatKy(){
            $service = new Sc_SuatChieu();
            try{
                $service->danhDauRapDaXem();
                return [
                    'success' => true,
                    'message' => 'Đọc nhật ký suất chiếu theo quản lý rạp thành công',
                ];
            }
            catch(\Exception $e){
                return [
                    'success' => false,
                    'message' => 'Lỗi khi đọc nhật ký suất chiếu theo quản lý rạp: ' . $e->getMessage()
                ];
            }
        }
        public function quanLyChuoiXemNhatKy(){
            $service = new Sc_SuatChieu();
            try{
                $idRap = $_REQUEST['idRap'];
                $service->danhDauDaXem($idRap);
                return [
                    'success' => true,
                    'message' => 'Đọc nhật ký suất chiếu theo quản lý chuỗi thành công',
                ];
            }
            catch(\Exception $e){
                return [
                    'success' => false,
                    'message' => 'Lỗi khi đọc nhật ký suất chiếu theo quản lý chuỗi: ' . $e->getMessage()
                ];
            }
        }
        
        public function docKeHoach(){
            $service = new Sc_SuatChieu();
            try{
                $batDau = $_GET['batdau'] ?? null;
                $ketThuc = $_GET['ketthuc'] ?? null;
                
                if(!$batDau || !$ketThuc){
                    return [
                        'success' => false,
                        'message' => 'Thiếu tham số batdau hoặc ketthuc'
                    ];
                }
                
                $result = $service->docKeHoach($batDau, $ketThuc);
                return [
                    'success' => true,
                    'message' => 'Đọc kế hoạch suất chiếu thành công',
                    'data' => $result
                ];
            }
            catch(\Exception $e){
                return [
                    'success' => false,
                    'message' => 'Lỗi khi đọc kế hoạch suất chiếu: ' . $e->getMessage()
                ];
            }
        }
        
        public function luuKeHoach(){
            $service = new Sc_SuatChieu();
            try{
                $data = json_decode(file_get_contents('php://input'), true);
                $batDau = $data['batdau'] ?? null;
                $ketThuc = $data['ketthuc'] ?? null;
                
                if(!$batDau || !$ketThuc){
                    return [
                        'success' => false,
                        'message' => 'Thiếu tham số batdau hoặc ketthuc'
                    ];
                }
                
                $result = $service->luuSuatChieuVaoKeHoach($batDau, $ketThuc);
                return [
                    'success' => true,
                    'message' => 'Lưu kế hoạch suất chiếu thành công',
                    'data' => $result
                ];
            }
            catch(\Exception $e){
                return [
                    'success' => false,
                    'message' => 'Lỗi khi lưu kế hoạch suất chiếu: ' . $e->getMessage()
                ];
            }
        }
        
        public function xoaSuatChieuTrongKeHoach($argc){
            $service = new Sc_SuatChieu();
            try{
                $idKeHoachChiTiet = $argc['id'];
                $service->xoaSuatChieuKhoiKeHoach($idKeHoachChiTiet);
                return [
                    'success' => true,
                    'message' => 'Xóa suất chiếu khỏi kế hoạch thành công',
                ];
            }
            catch(\Exception $e){
                return [
                    'success' => false,
                    'message' => 'Lỗi khi xóa suất chiếu khỏi kế hoạch: ' . $e->getMessage()
                ];
            }
        }
    }
?>