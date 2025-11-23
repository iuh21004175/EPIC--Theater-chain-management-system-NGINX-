<?php
namespace App\Services;

use App\Models\Ve;
use App\Models\DonHang;
use App\Models\SuatChieu;
use App\Models\Phim;

class Sc_Ve {
    public function them() {
        $user = $_SESSION['user'] ?? null;
        $data = json_decode(file_get_contents('php://input'), true);

        $donhang_id = $data['donhang_id'] ?? null;
        $suat_chieu_id = $data['suat_chieu_id'] ?? null;
        $khach_hang_id = $user['id'] ?? null;
        $trang_thai = $data['trang_thai'] ?? 'giu_cho';
        $het_han_giu = $data['het_han_giu'] ?? null;

        $veCreated = [];

        // Nếu gửi mảng ghế
        if (!empty($data['seats']) && is_array($data['seats'])) {
            foreach ($data['seats'] as $seat) {
                $ghe_id = $seat['ghe_id'] ?? null;
                $gia_ve = $seat['gia_ve'] ?? 0;
                $ma_ve = $seat['ma_ve'] ?? null;

                $ve = Ve::create([
                    'donhang_id' => $donhang_id,
                    'suat_chieu_id' => $suat_chieu_id,
                    'ghe_id' => $ghe_id,
                    'gia_ve' => $gia_ve,
                    'khach_hang_id' => $khach_hang_id,
                    'trang_thai' => $trang_thai,
                    'ngay_tao' => date('Y-m-d H:i:s'),
                    'het_han_giu' => date('Y-m-d H:i:s', strtotime('+10 minutes'))
                ]);

                if ($ve) {
                    $veCreated[] = $ve;
                }
            }
        }

        return !empty($veCreated) ? $veCreated : false;
    }
    public function capNhat($donhang_id) {
        $updated = Ve::where('donhang_id', $donhang_id)
                    ->update(['trang_thai' => 0]);
        return $updated > 0;
    }
    public function top4PhimTheoVe()
    {
        return Phim::select('phim.*')
            ->withCount(['suatChieu as so_ve_ban' => function($query) {
                $query->join('ve', 've.suat_chieu_id', '=', 'suatchieu.id');
            }])
            ->where('trang_thai', 1) 
            ->orderByDesc('so_ve_ban')
            ->limit(4)
            ->get();
    }
}
