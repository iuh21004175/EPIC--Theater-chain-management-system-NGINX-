<?php
namespace App\Controllers;

use App\Models\Ve;
use App\Models\DonHang;
use function App\Core\view;

class Ctrl_SoatVe {
    
    // Hiển thị trang soát vé
    public function index() {
        return view('internal.soat-ve');
    }
    
    // API kiểm tra và xác nhận vé
    public function kiemTraVe() {
        header('Content-Type: application/json');

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $maVe = $data['ma_ve'] ?? null;

            if (!$maVe) {
                return[
                    'success' => false,
                    'message' => 'Mã vé không hợp lệ'
                ];
            }

            // Tìm đơn hàng theo mã vé (ma_ve nằm ở bảng donhang); fallback tìm theo qr_code
            $donHang = DonHang::with(['ve.suatchieu.phim', 've.suatchieu.phongchieu', 've.ghe', 've.khachhang'])
                ->where('ma_ve', $maVe)
                ->orWhere('qr_code', $maVe)
                ->first();

            if (!$donHang) {
                return [
                    'success' => false,
                    'message' => 'Không tìm thấy vé trong hệ thống'
                ];
            }

            // Đơn hàng phải được thanh toán (trang_thai = 2)
            if ($donHang->trang_thai != 2) {
                return [
                    'success' => false,
                    'message' => 'Đơn hàng chưa được thanh toán'
                ];
            }

            // Lấy danh sách vé thuộc đơn hàng
            $danhSachVe = $donHang->ve;
            if (!$danhSachVe || $danhSachVe->count() === 0) {
                return [
                    'success' => false,
                    'message' => 'Đơn hàng không có vé nào'
                ];
            }

            // Dùng vé đầu tiên để lấy thông tin chung suất chiếu
            $veDau = $danhSachVe->first();
            $suatChieu = $veDau->suatchieu;

            if (!$suatChieu) {
                return [
                    'success' => false,
                    'message' => 'Không tìm thấy thông tin suất chiếu'
                ];
            }

            // Kiểm tra trạng thái từng vé
            foreach ($danhSachVe as $ve) {
                if ($ve->trang_thai == 0) {
                    return [
                        'success' => false,
                        'message' => 'Có vé đã bị hủy trong đơn hàng'
                    ];
                }
                if ($ve->trang_thai == 3) {
                    return [
                        'success' => false,
                        'message' => 'Có vé chưa được đặt'
                    ];
                }
                if ($ve->trang_thai == 1) {
                    return [
                        'success' => false,
                        'message' => 'Có vé đang giữ chỗ, chưa thanh toán'
                    ];
                }
                if ($ve->trang_thai == 4) {
                    return [
                        'success' => false,
                        'message' => 'Vé đã được soát trước đó. Không thể sử dụng lại.'
                    ];
                }
            }

            // Kiểm tra thời gian suất chiếu
            $thoiGianChieu = strtotime($suatChieu->ngay_chieu . ' ' . $suatChieu->gio_bat_dau);
            $hienTai = time();

            if ($hienTai < ($thoiGianChieu - 1800)) {
                return [
                    'success' => false,
                    'message' => 'Chưa đến giờ soát vé. Vui lòng quay lại trước giờ chiếu 30 phút.'
                ];
            }

            $thoiGianKetThuc = strtotime($suatChieu->ngay_chieu . ' ' . $suatChieu->gio_ket_thuc);
            if ($hienTai > $thoiGianKetThuc) {
                return [
                    'success' => false,
                    'message' => 'Suất chiếu đã kết thúc'
                ];
            }

            // Cập nhật trạng thái toàn bộ vé thuộc đơn hàng thành đã soát (4)
            foreach ($danhSachVe as $ve) {
                $ve->trang_thai = 4;
                $ve->save();
            }

            // Chuẩn bị thông tin trả về
            $danhSachGhe = $danhSachVe->map(function($ve) {
                return ($ve->ghe->hang ?? '') . ($ve->ghe->cot ?? '');
            })->filter()->values()->all();

            $tenKhach = $donHang->user->ten ?? ($veDau->khachhang->ten ?? 'Khách vãng lai');

            return [
                'success' => true,
                'message' => 'Vé hợp lệ',
                'data' => [
                    'ma_ve' => $donHang->ma_ve,
                    'ten_phim' => $suatChieu->phim->ten ?? 'N/A',
                    'phong_chieu' => $suatChieu->phongchieu->ten ?? 'N/A',
                    'ghe' => implode(', ', $danhSachGhe),
                    'gio_chieu' => date('H:i', strtotime($suatChieu->gio_bat_dau)) . ' - ' . date('d/m/Y', strtotime($suatChieu->ngay_chieu)),
                    'khach_hang' => $tenKhach,
                    'gia_ve' => number_format($veDau->gia_ve, 0, ',', '.') . ' VNĐ'
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }
    
    // Lấy danh sách vé đã soát (lịch sử)
    public function lichSuSoatVe() {
        
        try {
            $idRapPhim = $_SESSION['UserInternal']['ID_RapPhim'];
            $ngayHienTai = date('Y-m-d');

            $danhSachVe = Ve::where('trang_thai', 4)
                ->whereHas('suatchieu', function($query) use ($idRapPhim, $ngayHienTai) {
                    $query->where('rap_phim_id', $idRapPhim)
                          ->where('ngay_chieu', $ngayHienTai);
                })
                ->with(['suatchieu.phim', 'suatchieu.phongchieu', 'ghe', 'khachhang', 'donhang'])
                ->orderBy('updated_at', 'desc')
                ->get();

            return [
                'success' => true,
                'data' => $danhSachVe->map(function($ve) {
                    return [
                        'id' => $ve->id,
                        'ma_ve' => $ve->donhang->ma_ve ?? ('VE-' . str_pad($ve->id, 6, '0', STR_PAD_LEFT)),
                        'ten_phim' => $ve->suatchieu->phim->ten ?? 'N/A',
                        'phong_chieu' => $ve->suatchieu->phongchieu->ten ?? 'N/A',
                        'ghe' => ($ve->ghe->hang ?? '') . ($ve->ghe->cot ?? ''),
                        'gio_soat' => date('H:i:s', strtotime($ve->updated_at)),
                        'khach_hang' => $ve->khachhang->ten ?? 'Khách vãng lai'
                    ];
                })
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }
}
