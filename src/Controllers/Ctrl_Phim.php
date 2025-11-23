<?php
    namespace App\Controllers;
    use App\Services\Sc_Phim;
    use function App\Core\view;

    class Ctrl_Phim{
        public function index(){
            return view('internal.phim');
        }
        public function indexKhachHang(){
            return view('customer.phim');
        }
        public function lichChieu(){
            return view('customer.lich-chieu');
        }
        public function datVe(){
            return view('customer.dat-ve');
        }
        public function datVeOnline(){
            return view('customer.dat-ve-online');
        }
        public function gocDienAnh(){
            return view('customer.goc-dien-anh');
        }
        public function chiTietPhim(){
            return view('customer.chi-tiet-phim');
        }
        public function banVe(){
            return view('internal.ban-ve');
        }


        public function themTheLoaiPhim(){
            $service = new Sc_Phim();
            try {
                $result = $service->themTheLoai();
                if ($result) {
                    return [
                        'success' => true,
                        'message' => 'Thêm thể loại thành công'
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Thêm thể loại thất bại'
                    ];
                }
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Lỗi khi thêm thể loại: ' . $e->getMessage()
                ];
            }
        }
        public function docTheLoaiPhim(){
            $service = new Sc_Phim();
            try {
                $result = $service->docTheLoai();
                return [
                    'success' => true,
                    'data' => $result
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Lỗi khi tải danh sách thể loại: ' . $e->getMessage()
                ];
            }
        }
        public function suaTenTheLoaiPhim($argc){
            $service = new Sc_Phim();
            try {
                $result = $service->suaTheLoai($argc['id']);
                if ($result) {
                    return [
                        'success' => true,
                        'message' => 'Cập nhật thể loại thành công'
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Cập nhật thể loại thất bại'
                    ];
                }
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Lỗi khi cập nhật thể loại: ' . $e->getMessage()
                ];
            }
        }
        public function themPhim(){
            $service = new Sc_Phim();
            try {
                $result = $service->themPhim();
                if ($result) {
                    return [
                        'success' => true,
                        'message' => 'Thêm phim thành công'
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Thêm phim thất bại'
                    ];
                }
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Lỗi khi thêm phim: ' . $e->getMessage()
                ];
            }
        }
        public function docPhim() {
            header('Content-Type: application/json');

            try {
                $service = new Sc_Phim();
                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                $result = $service->docPhim(
                    $page,
                    $_GET['tuKhoaTimKiem'] ?? null,
                    $_GET['trangThai'] ?? null,
                    $_GET['theLoaiId'] ?? null,
                    $_GET['idRap'] ?? null,
                    $_GET['doTuoi'] ?? null,
                    $_GET['year'] ?? null,
                    $_GET['dangChieu'] ?? null,
                    $_GET['xemNhieu'] ?? null
                );

                // Xóa toàn bộ output buffer cũ
                if (ob_get_level()) {
                    ob_end_clean();
                }

                echo json_encode([
                    'success' => true,
                    'data' => $result['data'],
                    'pagination' => [
                        'total' => $result['total'],
                        'total_pages' => $result['total_pages'],
                        'current_page' => $result['current_page']
                    ]
                ]);
                exit; // đảm bảo không có output nào khác
            } catch (\Exception $e) {
                if (ob_get_level()) ob_end_clean();
                echo json_encode([
                    'success' => false,
                    'message' => 'Lỗi khi tải danh sách phim: ' . $e->getMessage()
                ]);
                exit;
            }
        }



        public function docPhimKH()
        {
            $service = new Sc_Phim();
            try {
                $result = $service->docPhimKH(
                    $_GET['tuKhoaTimKiem'] ?? null,
                    $_GET['theLoaiId'] ?? null,
                    $_GET['doTuoi'] ?? null
                );

                return [
                    'success' => true,
                    'data' => $result['data']
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Lỗi khi tải danh sách phim: ' . $e->getMessage()
                ];
            }
        }
        public function docPhimKHOnline()
        {
            $service = new Sc_Phim();
            try {
                $result = $service->docPhimKHOnline(
                    $_GET['tuKhoaTimKiem'] ?? null,
                    $_GET['theLoaiId'] ?? null,
                    $_GET['doTuoi'] ?? null
                );

                return [
                    'success' => true,
                    'data' => $result['data']
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Lỗi khi tải danh sách phim: ' . $e->getMessage()
                ];
            }
        }

        public function docPhimMoiNhat() {
            $service = new Sc_Phim();
            try {
                $result = $service->docPhimMoiNhat();
                return [
                    'success' => true,
                    'data' => $result['data']
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Lỗi khi tải phim mới nhất: ' . $e->getMessage()
                ];
            }
        }
        public function docChiTietPhim($argc)
        {
            $service = new Sc_Phim();
            try {
                $result = $service->docChiTietPhim($argc['id']);
                return [
                    'success' => true,
                    'data' => $result['data']
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Lỗi khi tải chi tiết phim: ' . $e->getMessage()
                ];
            }
        }
        public function suaPhim($argc){
            $service = new Sc_Phim();
            try {
                $result = $service->suaPhim($argc['id']);
                if ($result) {
                    return [
                        'success' => true,
                        'message' => 'Cập nhật phim thành công'
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Cập nhật phim thất bại'
                    ];
                }
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Lỗi khi cập nhật phim: ' . $e->getMessage()
                ];
            }
        }
        public function phanPhoiPhim($argc){
            $service = new Sc_Phim();
            try {
                $service->phanPhoi($argc['id']);
                
                return [
                    'success' => true,
                    'message' => 'Phân phối phim thành công'
                ];
    
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Lỗi khi phân phối phim: ' . $e->getMessage()
                ];
            }
        }
        public function docPhimTheoRap($argc){
            $service = new Sc_Phim();
            try {
                $result = $service->docPhimTheoRap($argc['id']);
                return [
                    'success' => true,
                    'data' => $result
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Lỗi khi tải danh sách phim theo rạp: ' . $e->getMessage()
                ];
            }
        }
        public function themPhanPhoi(){
            $service = new Sc_Phim();
            try {
                $service->themPhanPhoiPhim();
                return [
                    'success' => true,
                    'message' => 'Thêm phân phối phim thành công'
                ];
               
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Lỗi khi thêm phân phối phim: ' . $e->getMessage()
                ];
            }
        }
        public function xoaPhanPhoi(){
            $service = new Sc_Phim();
            try {
                $service->xoaPhanPhoiPhim();
                return [
                    'success' => true,
                    'message' => 'Xóa phân phối phim thành công'
                ];
               
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Lỗi khi xóa phân phối phim: ' . $e->getMessage()
                ];
            }
        }

    }
?>