<?php
    namespace App\Controllers;
    use App\Services\Sc_KeHoachSuatChieu;
    use function App\Core\view;

    class Ctrl_KeHoachSuatChieu {
        
        /**
         * Tạo khung giờ gợi ý cho kế hoạch
         * GET /api/ke-hoach-suat-chieu/tao-khung-gio-goi-y
         * Params: ngay, id_phong_chieu, thoi_luong_phim
         * Body (POST JSON): { "suat_chieu_hien_tai": [...] }
         */
        public function taoKhungGioGoiYChoKeHoach() {
            $service = new Sc_KeHoachSuatChieu();
            
            try {
                // Lấy params từ query string
                $ngay = $_GET['ngay'] ?? null;
                $idPhongChieu = (int) ($_GET['id_phong_chieu'] ?? 0);
                $thoiLuongPhim = (int) ($_GET['thoi_luong_phim'] ?? 0);

                if (!$ngay || !$idPhongChieu || !$thoiLuongPhim) {
                    return [
                        'success' => false,
                        'message' => 'Thiếu thông tin bắt buộc (ngay, id_phong_chieu, thoi_luong_phim)'
                    ];
                }

                // Lấy danh sách suất chiếu hiện tại trong modal từ request body
                $data = json_decode(file_get_contents('php://input'), true);
                $cacSuatChieuTrongModal = $data['suat_chieu_hien_tai'] ?? [];

                $result = $service->taoKhungGioGoiYChoKeHoach($ngay, $idPhongChieu, $thoiLuongPhim, $cacSuatChieuTrongModal);
                
                return [
                    'success' => true,
                    'data' => $result
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
        }

        /**
         * Kiểm tra suất chiếu kế hoạch có hợp lệ không
         * GET /api/ke-hoach-suat-chieu/kiem-tra-hop-le
         * Params: batdau, id_phong_chieu, thoi_luong_phim
         * Body (POST JSON): { "suat_chieu_hien_tai": [...] }
         */
        public function kiemTraSuatChieuKeHoachHopLe() {
            $service = new Sc_KeHoachSuatChieu();
            
            try {
                // Lấy params từ query string
                $batDau = $_GET['batdau'] ?? null;
                $idPhongChieu = (int) ($_GET['id_phong_chieu'] ?? 0);
                $thoiLuongPhim = (int) ($_GET['thoi_luong_phim'] ?? 0);

                if (!$batDau || !$idPhongChieu || !$thoiLuongPhim) {
                    return [
                        'success' => false,
                        'message' => 'Thiếu thông tin bắt buộc (batdau, id_phong_chieu, thoi_luong_phim)'
                    ];
                }

                // Lấy danh sách suất chiếu hiện tại trong modal từ request body
                $data = json_decode(file_get_contents('php://input'), true);
                $cacSuatChieuTrongModal = $data['suat_chieu_hien_tai'] ?? [];

                $result = $service->kiemTraSuatChieuKeHoach($batDau, $idPhongChieu, $thoiLuongPhim, $cacSuatChieuTrongModal);
                
                if ($result) {
                    return [
                        'success' => true,
                        'message' => 'Suất chiếu hợp lệ'
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Suất chiếu bị xung đột với suất chiếu khác'
                    ];
                }
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
        }

        /**
         * Đọc chi tiết suất chiếu trong kế hoạch
         * GET /api/ke-hoach-suat-chieu?batdau=YYYY-MM-DD&ketthuc=YYYY-MM-DD
         * Trả về mảng chi tiết suất chiếu của tuần
         */
        public function docKeHoach() {
            $service = new Sc_KeHoachSuatChieu();
            
            try {
                $batDau = $_GET['batdau'] ?? null;
                $ketThuc = $_GET['ketthuc'] ?? null;
                
                if (!$batDau || !$ketThuc) {
                    return [
                        'success' => false,
                        'message' => 'Thiếu thông tin bắt buộc (batdau, ketthuc)'
                    ];
                }
                
                $chiTietSuatChieu = $service->docKeHoach($batDau, $ketThuc);
                
                return [
                    'success' => true,
                    'data' => $chiTietSuatChieu
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
        }

        /**
         * Lưu kế hoạch suất chiếu
         * POST /api/ke-hoach-suat-chieu
         */
        public function luuKeHoach() {
            $service = new Sc_KeHoachSuatChieu();
            
            try {
                $data = json_decode(file_get_contents('php://input'), true);
                
                $result = $service->luuSuatChieuVaoKeHoach();
                
                return [
                    'success' => true,
                    'message' => 'Lưu kế hoạch thành công',
                    'data' => $result
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
        }

        /**
         * Xóa suất chiếu trong kế hoạch
         * DELETE /api/ke-hoach-suat-chieu/{id}
         */
        public function xoaSuatChieuTrongKeHoach($argc) {
            $service = new Sc_KeHoachSuatChieu();
            
            try {
                $service->xoaSuatChieuKhoiKeHoach($argc['id']);
                
                return [
                    'success' => true,
                    'message' => 'Xóa suất chiếu khỏi kế hoạch thành công'
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
        }

        /**
         * Duyệt suất chiếu trong kế hoạch
         * POST /api/ke-hoach-suat-chieu/{id}/duyet
         * Response: Trả về suất chiếu đã tạo và kế hoạch chi tiết đã cập nhật
         */
        public function duyetKeHoach($argc) {
            $service = new Sc_KeHoachSuatChieu();
            
            try {
                $result = $service->duyetKeHoach($argc['id']);
                
                return [
                    'success' => true,
                    'message' => 'Duyệt suất chiếu trong kế hoạch thành công',
                    'data' => $result
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
        }

        /**
         * Từ chối suất chiếu trong kế hoạch
         * POST /api/ke-hoach-suat-chieu/{id}/tu-choi
         */
        public function tuChoiKeHoach($argc) {
            $service = new Sc_KeHoachSuatChieu();
            
            try {
                $result = $service->tuChoiKeHoach($argc['id']);
                
                return [
                    'success' => true,
                    'message' => 'Từ chối suất chiếu trong kế hoạch thành công',
                    'data' => $result
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
        }

        /**
         * Hoàn tác suất chiếu bị từ chối trong kế hoạch
         * POST /api/ke-hoach-suat-chieu/{id}/hoan-tac
         */
        public function hoanTacKeHoach($argc) {
            $service = new Sc_KeHoachSuatChieu();
            
            try {
                $result = $service->hoanTacKeHoach($argc['id']);
                
                return [
                    'success' => true,
                    'message' => 'Hoàn tác suất chiếu thành công. Suất chiếu đã được đưa về trạng thái chờ duyệt.',
                    'data' => $result
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
        }

        /**
         * Duyệt toàn bộ tuần
         * POST /api/ke-hoach-suat-chieu/duyet-tuan
         * Body (JSON): { "batdau": "YYYY-MM-DD", "ketthuc": "YYYY-MM-DD", "id_rap": 123 }
         */
        public function duyetTuan() {
            $service = new Sc_KeHoachSuatChieu();
            
            try {
                $data = json_decode(file_get_contents('php://input'), true);
                $batDau = $data['batdau'] ?? null;
                $ketThuc = $data['ketthuc'] ?? null;
                $idRap = $data['id_rap'] ?? null;
                
                if (!$batDau || !$ketThuc) {
                    return [
                        'success' => false,
                        'message' => 'Thiếu thông tin bắt buộc (batdau, ketthuc)'
                    ];
                }
                
                $result = $service->duyetTuan($batDau, $ketThuc, $idRap);
                
                return [
                    'success' => true,
                    'message' => $result['message'],
                    'data' => [
                        'count' => $result['count'],
                        'suat_chieu' => $result['suat_chieu'] ?? []
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
