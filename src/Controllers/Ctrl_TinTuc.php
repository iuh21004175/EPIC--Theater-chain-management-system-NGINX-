<?php
    namespace App\Controllers;
    use App\Services\Sc_TinTuc;
    use function App\Core\view;
    class Ctrl_TinTuc {
        public function index() {
           return view('customer.tin-tuc');
        }

        public function chiTiet() {
           return view('customer.chi-tiet-tin-tuc');
        }

        public function tinTuc() {
           return view('internal.tin-tuc');
        }

        public function docTinTuc(){
            header('Content-Type: application/json; charset=utf-8');
            $service = new Sc_TinTuc();
            try {
                // Lấy các tham số lọc và phân trang từ query string
                $filters = [
                    'rap_id' => $_GET['rap_id'] ?? '',
                    'search' => $_GET['search'] ?? '',
                    'sort_by' => $_GET['sort_by'] ?? 'ngay_tao',
                    'sort_order' => $_GET['sort_order'] ?? 'desc',
                    'page' => $_GET['page'] ?? 1,
                    'per_page' => $_GET['per_page'] ?? 10
                ];

                $result = $service->doc($filters);
                
                echo json_encode([
                    'success' => true,
                    'data' => $result['data'],
                    'pagination' => [
                        'total' => $result['total'],
                        'page' => $result['page'],
                        'per_page' => $result['per_page'],
                        'total_pages' => $result['total_pages']
                    ]
                ]);
                exit;
            } catch (\Exception $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Lỗi khi tải danh sách tin tức: ' . $e->getMessage()
                ]);
                exit;
            }
        }

        public function docChiTiet($argc){
            $service = new Sc_TinTuc();
            try {
                $result = $service->findById($argc['id']);
                if ($result) {
                    return [
                        'success' => true,
                        'message' => 'Đọc chi tiết tin tức thành công',
                        'data' => $result
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Đọc chi tiết tin tức thất bại'
                    ];
                }
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Lỗi khi cập nhật trạng thái: ' . $e->getMessage()
                ];
            }
        }

        public function themTinTuc() {
            try {
                // Set header để báo với client rằng response là JSON
                header('Content-Type: application/json');
                
                $service = new Sc_TinTuc();
                $result = $service->themTinTuc();
                
                echo json_encode($result);
                exit;
                
            } catch (\Exception $e) {
                // Log lỗi
                error_log($e->getMessage());
                
                // Trả về JSON error
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
                exit;
            }
        }

        public function suaTinTuc($id)
        {
            try {
                header('Content-Type: application/json');

                $service = new Sc_TinTuc();
                $result = $service->suaTinTuc($id);

                echo json_encode($result);
                exit;
            } catch (\Exception $e) {
                error_log($e->getMessage());
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
                exit;
            }
        }

        public function docGuiYCBaiViet()
        {
            $service = new Sc_TinTuc();
            try {
                $result = $service->docGuiYCBaiViet();

                return [
                    'success' => true,
                    'data' => $result
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Lỗi khi tải chi tiết bài viết: ' . $e->getMessage()
                ];
            }
        }

        public function chiTietTinTuc($id)
        {
            try {
                header('Content-Type: application/json');
                $service = new Sc_TinTuc();
                $result = $service->docChiTietBaiViet($id);
                echo json_encode($result);
                exit;
            } catch (\Exception $e) {
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
                exit;
            }
        }

        public function docTinTucDaGui(){
            $sc_TinTuc = new Sc_TinTuc();
            try {
                $tinTuc = $sc_TinTuc->docTinTucDaGui();
                return ['success' => true, 'data' => $tinTuc];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        public function docTinTucTheoRap()
{
    header('Content-Type: application/json; charset=utf-8');
    $sc_TinTuc = new Sc_TinTuc();

    try {
        $tinTuc = $sc_TinTuc->docTinTucTheoRap();
        echo json_encode([
            'success' => true,
            'data' => $tinTuc
        ]);
        exit;
    } catch (\Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
    }
}

              
        public function duyetTinTuc($argc)
        {
            $sc_TinTuc = new Sc_TinTuc();
            try {
                $result = $sc_TinTuc->duyetTinTuc($argc['id']);
                return [
                    'success' => true,
                    'message' => 'Duyệt tin tức thành công',
                    'data' => $result
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
    }

?>