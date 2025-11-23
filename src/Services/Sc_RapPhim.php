<?php
    namespace App\Services;
    use App\Models\RapPhim;
    use App\Models\KeHoachChiTiet;
    use Carbon\Carbon;

    class Sc_RapPhim {
        /**
         * Extract Google Maps embed URL from iframe HTML or return URL as-is
         */
        private function extractMapUrl($input) {
            if (empty($input)) {
                return null;
            }
            
            $value = trim($input);
            
            // If it's already a valid embed URL, return as-is
            if (strpos($value, 'https://www.google.com/maps/embed') === 0 || 
                strpos($value, 'http://www.google.com/maps/embed') === 0) {
                return $value;
            }
            
            // Try to extract URL from iframe HTML
            // Match src="..." or src='...' in iframe tag
            if (preg_match('/<iframe[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $value, $matches)) {
                $extractedUrl = trim($matches[1]);
                // Verify it's a Google Maps embed URL
                if (strpos($extractedUrl, 'google.com/maps/embed') !== false) {
                    return $extractedUrl;
                }
            }
            
            // Try to match src attribute directly (for partial iframe code)
            if (preg_match('/src=["\']([^"\']*google\.com\/maps\/embed[^"\']*)["\']/i', $value, $matches)) {
                return trim($matches[1]);
            }
            
            // If no match, return original value (might be invalid, but preserve it)
            return $value;
        }
        
        public function them(){
            $rapPhim = null;
            try{
                $banDo = isset($_POST['ban_do']) ? $this->extractMapUrl($_POST['ban_do']) : null;

                $rapPhim = RapPhim::create([
                    'ten' => $_POST['ten'],
                    'dia_chi' => $_POST['diachi'],
                    'hotline' => $_POST['hotline'] ?? null,
                    'mo_ta' => $_POST['mota'] ?? null,
                    'ban_do' => $banDo,
                    'kinh_do' => isset($_POST['kinh_do']) && $_POST['kinh_do'] !== '' ? floatval($_POST['kinh_do']) : null,
                    'vi_do' => isset($_POST['vi_do']) && $_POST['vi_do'] !== '' ? floatval($_POST['vi_do']) : null,
                    'trang_thai' => 1 // Mặc định là đang hoạt động
                ]);
                return $rapPhim ? true : false;
            } catch (\Exception $e) {
                // Xử lý lỗi
                $rapPhim?->delete();
                throw new \Exception($e->getMessage());
            }
            
        }
        public function doc()
        {
            $rapPhim = RapPhim::all();
            // Tính số suất chiếu chờ duyệt từ kế hoạch cho mỗi rạp (chỉ từ hôm nay trở đi)
            $rapPhim = $rapPhim->map(function($rap) {
                $soSuatChuaDuyet = KeHoachChiTiet::whereHas('phongChieu', function($q) use ($rap) {
                    $q->where('id_rapphim', $rap->id);
                })
                ->where('tinh_trang', 0) // Chờ duyệt
                ->where('batdau', '>=', Carbon::now()) // Chỉ lấy từ hôm nay trở đi
                ->count();
                $rap->so_suat_chua_duyet = $soSuatChuaDuyet;
                return $rap;
            });
            return $rapPhim;
        }
        public function docTheoID($id) {
            $rapPhim = RapPhim::find($id);
            if ($rapPhim) {
                // Tính số suất chiếu chờ duyệt từ kế hoạch (chỉ từ hôm nay trở đi)
                $soSuatChuaXem = KeHoachChiTiet::whereHas('phongChieu', function($q) use ($id) {
                    $q->where('id_rapphim', $id);
                })
                ->where('tinh_trang', 0) // Chờ duyệt
                ->where('batdau', '>=', Carbon::now()) // Chỉ lấy từ hôm nay trở đi
                ->count();
                $rapPhim->so_suat_chua_xem = $soSuatChuaXem;
            }
            return $rapPhim;
        }
        public function trangThai($id){
            $rapPhim = RapPhim::find($id);
            if(!$rapPhim){
               return false;
            }
            else{
                $rapPhim->trang_thai = $rapPhim->trang_thai == 1 ? 0 : 1;
                return $rapPhim->save();
            }
        }
        public function sua($id){
            $rapPhim = RapPhim::find($id);
            if(!$rapPhim){
               return false;
            }
            else{
                $banDo = isset($_POST['ban_do']) ? $this->extractMapUrl($_POST['ban_do']) : null;
                
                $rapPhim->ten = $_POST['ten'];
                $rapPhim->dia_chi = $_POST['diachi'];
                $rapPhim->hotline = $_POST['hotline'] ?? null;
                $rapPhim->mo_ta = $_POST['mota'] ?? null;
                $rapPhim->ban_do = $banDo;
                $rapPhim->kinh_do = isset($_POST['kinh_do']) && $_POST['kinh_do'] !== '' ? floatval($_POST['kinh_do']) : null;
                $rapPhim->vi_do = isset($_POST['vi_do']) && $_POST['vi_do'] !== '' ? floatval($_POST['vi_do']) : null;
                return $rapPhim->save();
            }
        }
    }
?>