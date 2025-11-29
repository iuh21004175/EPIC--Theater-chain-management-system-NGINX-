<?php
    namespace App\Services;
    use App\Models\Ngay;
    use Carbon\Carbon;

    class Sc_GanNgay {
        public function ganNgay() {
            $data = json_decode(file_get_contents('php://input'), true);
            $ngay = $data['ngay'];
            $loai_ngay = $data['loai_ngay'];
            $dac_biet = $data['dac_biet'];
            // Kiểm tra nếu ngày đã tồn tại trong bảng 'ngay'
            $existingNgay = Ngay::where('ngay', $ngay)->first();
            if ($existingNgay) {
                $existingNgay->dac_biet = $dac_biet;
                $existingNgay->loai_ngay = $loai_ngay;
                $existingNgay->save();
                return $existingNgay->id; // Trả về ID của ngày đã tồn tại
            }

            // Nếu ngày chưa tồn tại, tạo mới
            $newNgay = Ngay::create([
                'ngay' => $ngay,
                'loai_ngay' => $loai_ngay,
                'dac_biet' => $dac_biet,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);

            return $newNgay; // Trả về ID của ngày mới tạo
        }
        public function doc($thang, $nam) {
            return Ngay::whereYear('ngay', $nam)
                        ->whereMonth('ngay', $thang)
                        ->get();

        }
    }
?>