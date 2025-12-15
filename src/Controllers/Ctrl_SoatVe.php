<?php
namespace App\Controllers;

use App\Models\Ve;
use App\Models\DonHang;
use App\Services\Sc_PhongChieu;
use App\Services\Sc_SuatChieu;
use function App\Core\view;

class Ctrl_SoatVe {
    
    // Hiển thị trang soát vé
    public function index() {
        $sc = new Sc_PhongChieu();
        $phongChieu = $sc->layPhongChieuTheoRap();
        return view('internal.soat-ve', [
            'phongChieu' => $phongChieu
        ]);
    }


public function kiemTraVe() {
    header('Content-Type: application/json');

    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $maVe = $data['ma_ve'] ?? null;
        $idSuatChieu = $data['id_suat_chieu'] ?? null;

        // Validate input
        if (!$maVe) {
            return [
                'success' => false,
                'message' => 'Mã vé không hợp lệ'
            ];
        }

        if (!$idSuatChieu) {
            return [
                'success' => false,
                'message' => 'Chưa chọn suất chiếu'
            ];
        }

        // Tìm đơn hàng theo mã vé (ma_ve nằm ở bảng donhang); fallback tìm theo qr_code
        $donHang = DonHang::with(['ve.suatchieu.phim', 've.suatchieu.phongChieu', 've.ghe', 've.khachhang'])
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

        // ===== KIỂM TRA MỚI: Vé có thuộc suất chiếu đang soát không =====
        if ($suatChieu->id != $idSuatChieu) {
            return [
                'success' => false,
                'message' => 'Vé này không thuộc suất chiếu đang soát. Vui lòng kiểm tra lại phòng và suất chiếu.'
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
        $thoiGianChieu = strtotime($suatChieu->batdau);
        $hienTai = time();

        // Cho phép soát vé trước giờ chiếu 30 phút
        if ($hienTai < ($thoiGianChieu - 1800)) {
            $thoiGianSoat = date('H:i d/m/Y', $thoiGianChieu - 1800);
            return [
                'success' => false,
                'message' => "Chưa đến giờ soát vé. Vui lòng quay lại sau {$thoiGianSoat}"
            ];
        }

        // Không cho phép soát vé sau khi suất chiếu kết thúc
        $thoiGianKetThuc = strtotime($suatChieu->ketthuc);
        if ($hienTai > $thoiGianKetThuc) {
            return [
                'success' => false,
                'message' => 'Suất chiếu đã kết thúc'
            ];
        }

        // Cập nhật trạng thái toàn bộ vé thuộc đơn hàng thành đã soát (4)
        foreach ($danhSachVe as $ve) {
            $ve->trang_thai = 4;
            $ve->thoi_gian_soat = date('Y-m-d H:i:s'); // Lưu thời gian soát
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
                'ten_phim' => $suatChieu->phim->ten_phim ?? 'N/A',
                'phong_chieu' => $suatChieu->phongChieu->ten ?? 'N/A',
                'ghe' => implode(', ', $danhSachGhe),
                'gio_chieu' => date('H:i d/m/Y', strtotime($suatChieu->batdau)),
                'khach_hang' => $tenKhach,
                'gia_ve' => number_format($veDau->gia_ve, 0, ',', '.') . ' VNĐ',
                'so_luong_ve' => $danhSachVe->count(),
                'thoi_gian_soat' => date('H:i:s d/m/Y')
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
                          ->whereDate('batdau', $ngayHienTai);
                })
                ->with(['suatchieu.phim', 'suatchieu.phongChieu', 'ghe', 'khachhang', 'donhang'])
                ->orderBy('updated_at', 'desc')
                ->get();

            return [
                'success' => true,
                'data' => $danhSachVe->map(function($ve) {
                    return [
                        'id' => $ve->id,
                        'ma_ve' => $ve->donhang->ma_ve ?? ('VE-' . str_pad($ve->id, 6, '0', STR_PAD_LEFT)),
                        'ten_phim' => $ve->suatchieu->phim->ten_phim ?? 'N/A',
                        'phong_chieu' => $ve->suatchieu->phongChieu->ten ?? 'N/A',
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
    public function layDanhSachSuatChieu($argc){
        $idPhongChieu = $argc['id'] ?? null;
        if (!$idPhongChieu) {
            return [
                'success' => false,
                'message' => 'ID phòng chiếu không hợp lệ'
            ];
        }
        $service = new Sc_SuatChieu();
        try {
            $danhSachSuatChieu = $service->docSuatChieuTheoPhongChieu($idPhongChieu);
            return [
                'success' => true,
                'data' => $danhSachSuatChieu
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Đã xảy ra lỗi: ' . $e->getMessage()
            ];
        }
    }
}
