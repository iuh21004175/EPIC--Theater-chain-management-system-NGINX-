<?php
namespace App\Controllers;

use function App\Core\view;
use App\Services\Sc_GoiVideo;

class Ctrl_GoiVideo {
    // Trang đặt lịch gọi video của khách hàng
    public function pageDatLichGoiVideo() {
        return view('customer.dat-lich-goi-video');
    }

    // Trang quản lý lịch gọi video của nhân viên
    public function pageDuyetLichGoiVideo() {
        return view('internal.duyet-lich-goi-video');
    }

    // Trang gọi video
    public function pageVideoCall() {
        $roomId = $_GET['room'] ?? null;
        
        if (!$roomId) {
            header('Location: ' . $_ENV['URL_WEB_BASE']);
            exit;
        }
        
        $sc = new Sc_GoiVideo();
        
        // Kiểm tra quyền truy cập phòng
        try {
            $roomInfo = $sc->layThongTinPhongGoiVideo($roomId);
            
            // Nếu là nhân viên, kiểm tra xem có phải nhân viên được phân công không
            if (isset($_SESSION['UserInternal'])) {
                $idNhanVien = $_SESSION['UserInternal']['ID'];
                
                if (!$roomInfo || $roomInfo->id_nhanvien != $idNhanVien) {
                    // Không phải nhân viên được phân công
                    http_response_code(403);
                    return view('internal.403', [
                        'message' => 'Bạn không có quyền tham gia cuộc gọi này. Chỉ nhân viên được phân công mới có thể tham gia.'
                    ]);
                }
            }
            
            return view('customer.video-call', ['roomId' => $roomId, 'roomInfo' => $roomInfo]);
        } catch (\Exception $e) {
            http_response_code(404);
            if (isset($_SESSION['UserInternal'])) {
                return view('internal.403', ['message' => $e->getMessage()]);
            }
            header('Location: ' . $_ENV['URL_WEB_BASE']);
            exit;
        }
    }

    // API: Khách hàng đặt lịch gọi video
    public function khachHangDatLichGoiVideo() {
        $scGoiVideo = new Sc_GoiVideo();
        
        try {
            $lich = $scGoiVideo->khachHangDatLichGoiVideo();
            
            return [
                'success' => true,
                'message' => 'Đặt lịch thành công. Vui lòng chờ nhân viên xác nhận.',
                'data' => $lich
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    // API: Nhân viên lấy danh sách lịch chờ
    public function nhanVienLayDanhSachLichCho() {
        $scGoiVideo = new Sc_GoiVideo();
        
        try {
            // Kiểm tra xem có yêu cầu phân trang không
            $page = isset($_GET['page']) ? (int)$_GET['page'] : null;
            $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
            
            if ($page) {
                // Trả về kết quả phân trang
                $result = $scGoiVideo->nhanVienLayDanhSachLichPhanTrang($page, $perPage);
                return [
                    'success' => true,
                    'data' => $result['data'],
                    'pagination' => $result['pagination']
                ];
            } else {
                // Trả về toàn bộ (backward compatible)
                $danhSach = $scGoiVideo->nhanVienLayDanhSachLichCho();
                return [
                    'success' => true,
                    'data' => $danhSach
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    // API: Nhân viên chọn tư vấn
    public function nhanVienChonTuVan($argc) {
        $idLich = $argc['id'] ?? null;
        
        if (!$idLich) {
            return [
                'success' => false,
                'message' => 'Thiếu ID lịch'
            ];
        }

        $scGoiVideo = new Sc_GoiVideo();
        
        try {
            $result = $scGoiVideo->nhanVienChonTuVan($idLich);
            
            return [
                'success' => true,
                'message' => 'Đã nhận tư vấn cho khách hàng',
                'data' => $result
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    // API: Khách hàng kiểm tra trạng thái lịch
    public function khachHangKiemTraTrangThai($argc) {
        $idLich = $argc['id'] ?? null;
        
        if (!$idLich) {
            return [
                'success' => false,
                'message' => 'Thiếu ID lịch'
            ];
        }

        $scGoiVideo = new Sc_GoiVideo();
        
        try {
            $lich = $scGoiVideo->khachHangKiemTraTrangThai($idLich);
            
            return [
                'success' => true,
                'data' => $lich
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    // API: Nhân viên hủy tư vấn
    public function nhanVienHuyTuVan($argc) {
        $idLich = $argc['id'] ?? null;
        
        if (!$idLich) {
            return [
                'success' => false,
                'message' => 'Thiếu ID lịch'
            ];
        }

        $scGoiVideo = new Sc_GoiVideo();
        
        try {
            $lich = $scGoiVideo->nhanVienHuyTuVan($idLich);
            
            return [
                'success' => true,
                'message' => 'Đã hủy tư vấn',
                'data' => $lich
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    // API: Khách hàng lấy danh sách lịch gọi video theo ngày
    public function khachHangLayLichTheoNgay() {
        $ngay = $_GET['ngay'] ?? null;
        
        if (!$ngay) {
            return [
                'success' => false,
                'message' => 'Thiếu tham số ngày (format: YYYY-MM-DD)'
            ];
        }

        $scGoiVideo = new Sc_GoiVideo();
        
        try {
            $danhSach = $scGoiVideo->khachHangLayLichTheoNgay($ngay);
            
            return [
                'success' => true,
                'data' => $danhSach
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    // API: Đặt lịch gọi video (endpoint mới với format đơn giản)
    public function datLichGoiVideo() {
        $scGoiVideo = new Sc_GoiVideo();
        
        try {
            $lich = $scGoiVideo->datLichGoiVideo();
            
            return [
                'success' => true,
                'message' => 'Đặt lịch thành công. Vui lòng chờ nhân viên xác nhận.',
                'data' => $lich
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    // API: Bắt đầu cuộc gọi (gọi từ Socket.IO server)
    public function batDauCuocGoi() {
        $data = json_decode(file_get_contents('php://input'), true);
        $roomId = $data['room_id'] ?? null;
        
        if (!$roomId) {
            return [
                'success' => false,
                'message' => 'Thiếu room_id'
            ];
        }

        $scGoiVideo = new Sc_GoiVideo();
        
        try {
            $lich = $scGoiVideo->batDauCuocGoi($roomId);
            
            return [
                'success' => true,
                'message' => 'Đã bắt đầu cuộc gọi',
                'data' => $lich
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    // API: Kết thúc cuộc gọi (gọi từ Socket.IO server)
    public function ketThucCuocGoi() {
        $data = json_decode(file_get_contents('php://input'), true);
        $roomId = $data['room_id'] ?? null;
        
        if (!$roomId) {
            return [
                'success' => false,
                'message' => 'Thiếu room_id'
            ];
        }

        $scGoiVideo = new Sc_GoiVideo();
        
        try {
            $lich = $scGoiVideo->ketThucCuocGoi($roomId);
            
            return [
                'success' => true,
                'message' => 'Đã kết thúc cuộc gọi',
                'data' => $lich
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
