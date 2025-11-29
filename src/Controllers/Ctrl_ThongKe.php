<?php
    namespace App\Controllers;
    use function App\Core\view;
    use App\Services\Sc_ThongKe;
    class Ctrl_ThongKe {
        // Controller code here
        function index() {
            return view('internal.thong-ke');
        }
        /**
         * API thống kê toàn rạp (cho Admin/Quản lý chuỗi rạp)
         * Hiển thị: Tổng doanh thu, Tổng vé bán, Tỉ lệ lấp đầy, Doanh thu F&B
         * Tham số: tuNgay, denNgay, idRap (optional - nếu muốn filter theo rạp cụ thể)
         * So sánh: soSanhVoiKyTruoc (true/false)
         */
        public function thongKeToanRap(){
            $scThongKe = new Sc_ThongKe();
            try{
                // Lấy tham số từ GET request
                $tuNgay = $_GET['tuNgay'] ?? date('Y-m-01'); // Mặc định từ ngày đầu tháng
                $denNgay = $_GET['denNgay'] ?? date('Y-m-t'); // Mặc định đến ngày cuối tháng
                $idRap = $_GET['idRap'] ?? 'all'; // 'all' = tất cả rạp, hoặc ID rạp cụ thể
                $soSanhVoiKyTruoc = isset($_GET['soSanh']) && $_GET['soSanh'] === 'true';
                
                return [
                    'success' => true,
                    'data' => $scThongKe->thongKeTongQuanToanRap($tuNgay, $denNgay, $idRap, $soSanhVoiKyTruoc)
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
        }

        /**
         * API xu hướng doanh thu toàn rạp
         * Hiển thị biểu đồ xu hướng doanh thu theo thời gian
         * Tham số: tuNgay, denNgay, idRap, loaiXuHuong (daily/weekly/monthly)
         */
        public function xuHuongDoanhThuToanRap(){
            $scThongKe = new Sc_ThongKe();
            try{
                $tuNgay = $_GET['tuNgay'] ?? date('Y-m-01');
                $denNgay = $_GET['denNgay'] ?? date('Y-m-t');
                $idRap = $_GET['idRap'] ?? 'all';
                $loaiXuHuong = $_GET['loaiXuHuong'] ?? 'daily'; // daily, weekly, monthly
                
                return [
                    'success' => true,
                    'data' => $scThongKe->xuHuongDoanhThuToanRap($tuNgay, $denNgay, $idRap, $loaiXuHuong)
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
        }

        /**
         * API Top 10 phim toàn rạp
         * Hiển thị danh sách 10 phim có doanh thu cao nhất
         * Tham số: tuNgay, denNgay, idRap
         */
        public function top10PhimToanRap(){
            $scThongKe = new Sc_ThongKe();
            try{
                $tuNgay = $_GET['tuNgay'] ?? date('Y-m-01');
                $denNgay = $_GET['denNgay'] ?? date('Y-m-t');
                $idRap = $_GET['idRap'] ?? 'all';
                
                return [
                    'success' => true,
                    'data' => $scThongKe->top10PhimToanRap($tuNgay, $denNgay, $idRap)
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
        }

        /**
         * API Hiệu suất theo rạp
         * Hiển thị biểu đồ so sánh doanh thu giữa các rạp
         * Tham số: tuNgay, denNgay, idRap (optional)
         */
        public function hieuSuatTheoRapToanRap(){
            $scThongKe = new Sc_ThongKe();
            try{
                $tuNgay = $_GET['tuNgay'] ?? date('Y-m-01');
                $denNgay = $_GET['denNgay'] ?? date('Y-m-t');
                $idRap = $_GET['idRap'] ?? 'all';
                
                return [
                    'success' => true,
                    'data' => $scThongKe->hieuSuatTheoRap($tuNgay, $denNgay, $idRap)
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
        }

        /**
         * API Top 10 sản phẩm F&B bán chay nhất
         * Hiển thị bảng top 10 sản phẩm có số lượng bán cao nhất
         * Tham số: tuNgay, denNgay, idRap (optional)
         */
        public function top10SanPhamBanChayNhat(){
            $scThongKe = new Sc_ThongKe();
            try{
                $tuNgay = $_GET['tuNgay'] ?? date('Y-m-01');
                $denNgay = $_GET['denNgay'] ?? date('Y-m-t');
                $idRap = $_GET['idRap'] ?? 'all';
                
                return [
                    'success' => true,
                    'data' => $scThongKe->top10SanPhamBanChayNhat($tuNgay, $denNgay, $idRap)
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
        }

        /**
         * API duy nhất lấy dữ liệu thô thống kê toàn rạp
         * Trả về dữ liệu thô từ database, JavaScript sẽ xử lý format/tổng hợp ở client side
         * Luôn lấy dữ liệu cho tất cả rạp, filter theo rạp được xử lý ở client side
         * Tham số: tuNgay, denNgay
         */
        public function layDuLieuThoThongKeToanRap(){
            $scThongKe = new Sc_ThongKe();
            try{
                $tuNgay = $_GET['tuNgay'] ?? date('Y-m-01');
                $denNgay = $_GET['denNgay'] ?? date('Y-m-t');
                
                return [
                    'success' => true,
                    'data' => $scThongKe->layDuLieuThoThongKeToanRap($tuNgay, $denNgay)
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
        }

        /**
         * API lấy dữ liệu thô thống kê theo rạp (cho Quản lý rạp)
         * Trả về dữ liệu thô từ database, JavaScript sẽ xử lý format/tổng hợp ở client side
         */
        public function layDuLieuThoThongKeTheoRap(){
            $scThongKe = new Sc_ThongKe();
            try{
                $idRap = $_SESSION['UserInternal']['ID_RapPhim'] ?? null;
                if (!$idRap) {
                    return [
                        'success' => false,
                        'message' => 'Không tìm thấy thông tin rạp phim'
                    ];
                }
                
                $tuNgay = $_GET['tuNgay'] ?? date('Y-m-01');
                $denNgay = $_GET['denNgay'] ?? date('Y-m-t');
                
                return [
                    'success' => true,
                    'data' => $scThongKe->layDuLieuThoThongKeTheoRap($tuNgay, $denNgay, $idRap)
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
        }

        /**
         * API thống kê doanh thu theo suất chiếu
         * Tính doanh thu từ vé và F&B cho từng suất chiếu trong khoảng thời gian
         * Tham số: tuNgay, denNgay
         */
        public function thongKeDoanhThuTheoSuatChieu(){
            $scThongKe = new Sc_ThongKe();
            try{
                $tuNgay = $_GET['tuNgay'] ?? date('Y-m-01');
                $denNgay = $_GET['denNgay'] ?? date('Y-m-t');
                
                return [
                    'success' => true,
                    'data' => $scThongKe->thongKeDoanhThuTheoSuatChieu($tuNgay, $denNgay)
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
        }

        /**
         * API thống kê tổng quan theo rạp (cho Quản lý rạp)
         * Hiển thị: Tổng doanh thu, Tổng vé bán, Tỉ lệ lấp đầy, Doanh thu F&B
         * Tham số: tuNgay, denNgay
         * So sánh: soSanhVoiKyTruoc (true/false)
         */
        public function thongKeTongQuanTheoRap(){
            $scThongKe = new Sc_ThongKe();
            try{
                $idRap = $_SESSION['UserInternal']['ID_RapPhim'] ?? null;
                if (!$idRap) {
                    return [
                        'success' => false,
                        'message' => 'Không tìm thấy thông tin rạp phim'
                    ];
                }
                
                $tuNgay = $_GET['tuNgay'] ?? date('Y-m-01');
                $denNgay = $_GET['denNgay'] ?? date('Y-m-t');
                $soSanhVoiKyTruoc = isset($_GET['soSanh']) && $_GET['soSanh'] === 'true';
                
                return [
                    'success' => true,
                    'data' => $scThongKe->thongKeTongQuanToanRap($tuNgay, $denNgay, $idRap, $soSanhVoiKyTruoc)
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
        }

        /**
         * API phân tích doanh thu theo rạp (cho Quản lý rạp)
         * Hiển thị biểu đồ xu hướng doanh thu theo thời gian
         * Tham số: tuNgay, denNgay
         */
        public function phanTichDoanhThuTheoRap(){
            $scThongKe = new Sc_ThongKe();
            try{
                $idRap = $_SESSION['UserInternal']['ID_RapPhim'] ?? null;
                if (!$idRap) {
                    return [
                        'success' => false,
                        'message' => 'Không tìm thấy thông tin rạp phim'
                    ];
                }
                
                $tuNgay = $_GET['tuNgay'] ?? date('Y-m-01');
                $denNgay = $_GET['denNgay'] ?? date('Y-m-t');
                
                return [
                    'success' => true,
                    'data' => $scThongKe->phanTichDoanhThuTheoRap($idRap, $tuNgay, $denNgay)
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
        }

        /**
         * API Top 10 phim theo rạp (cho Quản lý rạp)
         * Hiển thị danh sách 10 phim có doanh thu cao nhất
         * Tham số: tuNgay, denNgay
         */
        public function top10PhimTheoRap(){
            $scThongKe = new Sc_ThongKe();
            try{
                $idRap = $_SESSION['UserInternal']['ID_RapPhim'] ?? null;
                if (!$idRap) {
                    return [
                        'success' => false,
                        'message' => 'Không tìm thấy thông tin rạp phim'
                    ];
                }
                
                $tuNgay = $_GET['tuNgay'] ?? date('Y-m-01');
                $denNgay = $_GET['denNgay'] ?? date('Y-m-t');
                
                // Sử dụng top10PhimToanRap với filter theo idRap
                return [
                    'success' => true,
                    'data' => $scThongKe->top10PhimToanRap($tuNgay, $denNgay, $idRap)
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
        }

        /**
         * API Top 10 sản phẩm theo rạp (cho Quản lý rạp)
         * Hiển thị danh sách 10 sản phẩm có số lượng bán cao nhất
         * Tham số: tuNgay, denNgay
         */
        public function top10SanPhamTheoRap(){
            $scThongKe = new Sc_ThongKe();
            try{
                $idRap = $_SESSION['UserInternal']['ID_RapPhim'] ?? null;
                if (!$idRap) {
                    return [
                        'success' => false,
                        'message' => 'Không tìm thấy thông tin rạp phim'
                    ];
                }
                
                $tuNgay = $_GET['tuNgay'] ?? date('Y-m-01');
                $denNgay = $_GET['denNgay'] ?? date('Y-m-t');
                
                return [
                    'success' => true,
                    'data' => $scThongKe->top10SanPhamBanChayNhat($tuNgay, $denNgay, $idRap)
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
        }

        /**
         * API thống kê doanh thu theo suất chiếu (cho Quản lý rạp)
         * Tính doanh thu từ vé và F&B cho từng suất chiếu trong khoảng thời gian của rạp
         * Tham số: tuNgay, denNgay
         */
        public function thongKeDoanhThuTheoSuatChieuTheoRap(){
            $scThongKe = new Sc_ThongKe();
            try{
                $idRap = $_SESSION['UserInternal']['ID_RapPhim'] ?? null;
                if (!$idRap) {
                    return [
                        'success' => false,
                        'message' => 'Không tìm thấy thông tin rạp phim'
                    ];
                }
                
                $tuNgay = $_GET['tuNgay'] ?? date('Y-m-01');
                $denNgay = $_GET['denNgay'] ?? date('Y-m-t');
                
                // Lấy dữ liệu từ service và filter theo rạp
                $data = $scThongKe->thongKeDoanhThuTheoSuatChieu($tuNgay, $denNgay);
                
                // Filter danh sách suất chiếu theo rạp
                if (isset($data['danh_sach']) && is_array($data['danh_sach'])) {
                    $data['danh_sach'] = array_filter($data['danh_sach'], function($item) use ($idRap) {
                        return isset($item['id_rap']) && $item['id_rap'] == $idRap;
                    });
                    $data['danh_sach'] = array_values($data['danh_sach']); // Re-index array
                    
                    // Tính lại tổng kết
                    $data['tong_ket'] = [
                        'tong_so_suat_chieu' => count($data['danh_sach']),
                        'tong_doanh_thu_ve' => array_sum(array_column($data['danh_sach'], 'doanh_thu_ve')),
                        'tong_doanh_thu_fnb' => array_sum(array_column($data['danh_sach'], 'doanh_thu_fnb')),
                        'tong_doanh_thu' => array_sum(array_column($data['danh_sach'], 'tong_doanh_thu')),
                        'tong_ve_ban' => array_sum(array_column($data['danh_sach'], 'so_ve_ban'))
                    ];
                }
                
                return [
                    'success' => true,
                    'data' => $data
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
        }
        
        /**
         * Thống kê tổng quan cho nhân viên
         */
        public function thongKeTongQuanNhanVien() {
            $scThongKe = new Sc_ThongKe();
            try {
                // Thử nhiều key có thể có
                $idNhanVien = $_SESSION['UserInternal']['id'] ?? $_SESSION['UserInternal']['ID'] ?? $_SESSION['UserInternal']['Id'] ?? null;
                if (!$idNhanVien) {
                    return [
                        'success' => false,
                        'message' => 'Không tìm thấy thông tin nhân viên. Vui lòng đăng nhập lại.'
                    ];
                }
                
                $tuNgay = $_GET['tuNgay'] ?? date('Y-m-01');
                $denNgay = $_GET['denNgay'] ?? date('Y-m-t');
                $soSanh = isset($_GET['soSanh']) && $_GET['soSanh'] === 'true';
                
                $data = $scThongKe->thongKeTongQuanNhanVien($idNhanVien, $tuNgay, $denNgay, $soSanh);
                
                return [
                    'success' => true,
                    'data' => $data
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
        }
        
        /**
         * Xu hướng doanh thu cho nhân viên
         */
        public function xuHuongDoanhThuNhanVien() {
            $scThongKe = new Sc_ThongKe();
            try {
                // Thử nhiều key có thể có
                $idNhanVien = $_SESSION['UserInternal']['id'] ?? $_SESSION['UserInternal']['ID'] ?? $_SESSION['UserInternal']['Id'] ?? null;
                if (!$idNhanVien) {
                    return [
                        'success' => false,
                        'message' => 'Không tìm thấy thông tin nhân viên. Vui lòng đăng nhập lại.'
                    ];
                }
                
                $tuNgay = $_GET['tuNgay'] ?? date('Y-m-01');
                $denNgay = $_GET['denNgay'] ?? date('Y-m-t');
                
                $data = $scThongKe->xuHuongDoanhThuNhanVien($idNhanVien, $tuNgay, $denNgay);
                
                return [
                    'success' => true,
                    'data' => $data
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
        }
        
        /**
         * Top 5 phim bán chạy của nhân viên
         */
        public function top5PhimNhanVien() {
            $scThongKe = new Sc_ThongKe();
            try {
                // Thử nhiều key có thể có
                $idNhanVien = $_SESSION['UserInternal']['id'] ?? $_SESSION['UserInternal']['ID'] ?? $_SESSION['UserInternal']['Id'] ?? null;
                if (!$idNhanVien) {
                    return [
                        'success' => false,
                        'message' => 'Không tìm thấy thông tin nhân viên. Vui lòng đăng nhập lại.'
                    ];
                }
                
                $tuNgay = $_GET['tuNgay'] ?? date('Y-m-01');
                $denNgay = $_GET['denNgay'] ?? date('Y-m-t');
                
                $data = $scThongKe->top5PhimBanChayNhanVien($idNhanVien, $tuNgay, $denNgay);
                
                return [
                    'success' => true,
                    'data' => [
                        'danh_sach' => $data,
                        'tong_so' => count($data)
                    ]
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
        }
    }
?>