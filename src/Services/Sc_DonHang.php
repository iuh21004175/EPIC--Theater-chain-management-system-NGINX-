<?php
namespace App\Services;

use App\Models\DonHang;

class Sc_DonHang {
    public function them() {
        $user = $_SESSION['user'] ?? null;
        $user_id = $user['id'] ?? null;
        $input = json_decode(file_get_contents('php://input'), true);
        $tong_tien = $input['tong_tien'] ?? 0;
        $suat_chieu_id = $input['suat_chieu_id'] ?? null;
        $thequatang_id = $input['thequatang_id'] ?? null;
        $phim_id = $input['phim_id'] ?? null;
        $rap_id = $input['rap_id'] ?? null;
        $the_qua_tang_su_dung = $input['the_qua_tang_su_dung'] ?? null;
        $phuong_thuc_thanh_toan = $input['phuong_thuc_thanh_toan'] ?? 1;
        $trang_thai = $input['trang_thai'] ?? 1;
        $ma_ve = $input['ma_ve'] ?? null;
        $phuong_thuc_mua = $input['phuong_thuc_mua'] ?? 0;
        $qr_code = 'https://quickchart.io/qr?text=' . urlencode($ma_ve) . '&size=300';
        $donhang = DonHang::create([
            'user_id' => $user_id,
            'suat_chieu_id' => $suat_chieu_id,
            'thequatang_id' => $thequatang_id,
            'the_qua_tang_su_dung' => $the_qua_tang_su_dung,
            'ma_ve' => $ma_ve,
            'qr_code' => $qr_code,
            'tong_tien' => $tong_tien,
            'phim_id' => $phim_id,
            'rap_id' => $rap_id,
            'phuong_thuc_thanh_toan' => $phuong_thuc_thanh_toan,
            'trang_thai' => $trang_thai,
            'phuong_thuc_mua' => $phuong_thuc_mua,
            'ngay_dat' => date('Y-m-d H:i:s')
        ]);

        if ($donhang) {
            return $donhang;
        }
        return false;
    }

    public function themDonHang() {
        $idNhanVien = $_SESSION['UserInternal']['ID'] ?? null;
        $input = json_decode(file_get_contents('php://input'), true);
        $tong_tien = $input['tong_tien'] ?? 0;
        $suat_chieu_id = $input['suat_chieu_id'] ?? null;
        $thequatang_id = $input['thequatang_id'] ?? null;
        $phim_id = $input['phim_id'] ?? null;
        $the_qua_tang_su_dung = $input['the_qua_tang_su_dung'] ?? null;
        $phuong_thuc_thanh_toan = $input['phuong_thuc_thanh_toan'] ?? 1;
        $trang_thai = $input['trang_thai'] ?? 1;
        $ma_ve = $input['ma_ve'] ?? null;
        $rap_id = $input['rap_id'] ?? null;
        $phuong_thuc_mua = $input['phuong_thuc_mua'] ?? 0;
        $qr_code = 'https://quickchart.io/qr?text=' . urlencode($ma_ve) . '&size=300';
        $donhang = DonHang::create([
            'suat_chieu_id' => $suat_chieu_id,
            'id_nhanvien' => $idNhanVien,
            'thequatang_id' => $thequatang_id,
            'the_qua_tang_su_dung' => $the_qua_tang_su_dung,
            'ma_ve' => $ma_ve,
            'qr_code' => $qr_code,
            'tong_tien' => $tong_tien,
            'phim_id' => $phim_id,
            'rap_id' => $rap_id,
            'phuong_thuc_thanh_toan' => $phuong_thuc_thanh_toan,
            'trang_thai' => $trang_thai,
            'phuong_thuc_mua' => $phuong_thuc_mua,
            'ngay_dat' => date('Y-m-d H:i:s')
        ]);

        if ($donhang) {
            return $donhang;
        }
        return false;
    }

    public function doc() {
        $user = $_SESSION['user'];
        $idKhachHang = $user['id'];

        $donhang = DonHang::where('user_id', $idKhachHang)
                    ->whereIn('trang_thai', [0, 2])
                    ->where('phuong_thuc_mua', 0)
                    ->with([
                        'suatChieu.phongChieu.rapChieuPhim',
                        'suatChieu.phim',
                        'theQuaTang'
                    ])
                    ->orderBy('id', 'desc') 
                    ->get();

        return $donhang;
    }
    public function docDonHangTheoRap($idRap)
    {
        $donhang = DonHang::with([
                'user',
                'suatChieu.phongChieu.rapChieuPhim',
                'suatChieu.phim',
                'theQuaTang',
                'chiTietDonHang',
                've'
            ])
            ->whereHas('suatChieu.phongChieu.rapChieuPhim', function ($query) use ($idRap) {
                $query->where('id', $idRap);
            })
            ->orderBy('id', 'desc')
            ->get();

        return $donhang;
    }

    public function docDonHang($idKhachHang, $filters = []) {
        $query = DonHang::where('user_id', $idKhachHang)
                    ->with([
                        'suatChieu.phongChieu.rapChieuPhim',
                        'suatChieu.phim',
                        'theQuaTang'
                    ]);

        // Lọc theo trạng thái
        if (isset($filters['trang_thai']) && $filters['trang_thai'] !== 'all' && $filters['trang_thai'] !== '') {
            $query->where('trang_thai', $filters['trang_thai']);
        }

        // Lọc theo ngày từ
        if (isset($filters['ngay_tu']) && $filters['ngay_tu']) {
            $query->whereDate('ngay_dat', '>=', $filters['ngay_tu']);
        }

        // Lọc theo ngày đến
        if (isset($filters['ngay_den']) && $filters['ngay_den']) {
            $query->whereDate('ngay_dat', '<=', $filters['ngay_den']);
        }

        // Lọc theo số tiền tối thiểu
        if (isset($filters['tong_tien_tu']) && $filters['tong_tien_tu'] !== '') {
            $query->where('tong_tien', '>=', $filters['tong_tien_tu']);
        }

        // Lọc theo số tiền tối đa
        if (isset($filters['tong_tien_den']) && $filters['tong_tien_den'] !== '') {
            $query->where('tong_tien', '<=', $filters['tong_tien_den']);
        }

        // Sắp xếp
        $sortBy = $filters['sort_by'] ?? 'id';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // Phân trang
        $page = isset($filters['page']) ? (int)$filters['page'] : 1;
        $perPage = isset($filters['per_page']) ? (int)$filters['per_page'] : 10;
        
        $total = $query->count();
        $donhang = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

        return [
            'data' => $donhang,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    public function docOnline() {
        $user = $_SESSION['user'];
        $idKhachHang = $user['id'];

        $donhang = DonHang::where('user_id', $idKhachHang)
                    ->whereIn('trang_thai', [0, 2])
                    ->where('phuong_thuc_mua', 1)
                    ->with('phim')
                    ->orderBy('id', 'desc') 
                    ->get();

        return $donhang;
    }

    public function capNhat($id){
        $donHang = DonHang::find($id);
        if($donHang){
            $donHang->trang_thai = 0;
            return $donHang->save();
        }
        return false;
    }
}