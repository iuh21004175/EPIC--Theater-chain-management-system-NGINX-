<?php
    namespace App\Services;
    use App\Models\Phim_TheLoai;
    use App\Models\Phim;
    use App\Models\TheLoai;
    use App\Models\PhanPhoiPhim;
    use function App\Core\getS3Client;
    use Carbon\Carbon;
    class Sc_Phim {
        public function themTheLoai(){
            $ten = $_POST['ten'] ?? '';
            $theLoai = TheLoai::create([
                'ten' => $ten,
            ]);
            if($theLoai){
                return true;
            }
            return false;
        }
        public function docTheLoai(){
            return TheLoai::all();
        }
        public function suaTheLoai($id){
            $data = file_get_contents('php://input');
            $data = json_decode($data, true);
            $ten = $data['ten'] ?? '';
            $theLoai = TheLoai::find($id);
            if($theLoai){
                $theLoai->ten = $ten;
                $theLoai->save();
                return true;
            }
            return false;
        }
        private function capNhatSoPhimTheLoai() {
            $theLoai = TheLoai::all();
            foreach ($theLoai as $tl) {
                $soPhim = Phim_TheLoai::where('theloai_id', $tl->id)->count();
                $tl->so_phim = $soPhim;
                $tl->save();
            }
        }
        public function themPhim(){
            $phim = null;
            $bucket = "poster";
            
            try{
                if (! isset($_FILES['poster']) || $_FILES['poster']['error'] !== UPLOAD_ERR_OK) {
                    throw new \Exception('Poster chưa được upload');
                }
                if (! isset($_FILES['video'])  || $_FILES['video']['error']  !== UPLOAD_ERR_OK) {
                    throw new \Exception('Video chưa được upload');
                }
                $ten = $_POST['ten'] ?? '';
                $daoDien = $_POST['dao_dien'] ?? '';
                $dienVien = $_POST['dien_vien'] ?? '';
                $thoiLuong = $_POST['thoi_luong'] ?? '';
                $doTuoi = $_POST['do_tuoi'] ?? '';
                $quocGia = $_POST['quoc_gia'] ?? '';
                $moTa = $_POST['mo_ta'] ?? '';
                $ngayCongChieu = $_POST['ngay_cong_chieu'] ?? '';
                $trangThai = $_POST['trang_thai'] ?? '';
                $theLoaiIds = $_POST['the_loai_ids'] ?? [];
                $hinhAnh = $_FILES['poster'] ?? null;
                $trailerUrl = $_POST['trailer_url'] ?? '';
                $video = $_FILES['video'] ?? null;
                
                // Xử lý poster
                $posterUrl = null;
                $keyName = '';
                if ($hinhAnh && isset($hinhAnh['name']) && !empty($hinhAnh['name'])) {
                    $fileExtension = pathinfo($hinhAnh['name'], PATHINFO_EXTENSION);
                    $keyName = 'poster_phim' . '_' . time() . '.' . $fileExtension;
                    $posterUrl = $bucket.'/'.$keyName;
                }
                
                // Xử lý video - chỉ tạo path, chưa upload
                $videoUrl = null;
                $trangThaiVideo = null; // Không có video
                if ($video && isset($video['name']) && !empty($video['name'])) {
                    $pathVideoCSDL = 'video/'.'phim' . '_' . time() . '/';
                    $videoUrl = $pathVideoCSDL.'master.m3u8';
                    $trangThaiVideo = 2; // Video đang xử lý
                }
                
                $phim = Phim::create([
                    'ten_phim' => $ten,
                    'mo_ta' => $moTa,
                    'ngay_cong_chieu' => $ngayCongChieu,
                    'trang_thai' => $trangThai,
                    'dao_dien' => $daoDien,
                    'dien_vien' => $dienVien,
                    'thoi_luong' => $thoiLuong,
                    'do_tuoi' => $doTuoi,
                    'quoc_gia' => $quocGia,
                    'poster_url' => $posterUrl,
                    'trailer_url' => $trailerUrl,
                    'video_url' => $videoUrl,
                    'trang_thai_video' => $trangThaiVideo
                ]);
                
                if($phim){
                    // Upload poster lên MinIO nếu có
                    if($keyName && $hinhAnh && isset($hinhAnh['tmp_name'])){
                        getS3Client()->putObject([
                            'Bucket' => $bucket,
                            'Key'    => $keyName,
                            'SourceFile' => $hinhAnh['tmp_name'],
                        ]);
                    }
                    
                    // Gọi API Python để convert video nếu có
                    if($video && isset($video['tmp_name'])){
                        // $this->convertVideoThroughAPI($video['tmp_name'], 'private', $pathVideoCSDL);
                        $this->run_hls_uploader($video['tmp_name'], 'private', $pathVideoCSDL, true);
                    }
                    
                    // Thêm thể loại
                    foreach($theLoaiIds as $theLoaiId){
                        $phim->TheLoai()->create([
                            'theloai_id' => $theLoaiId,
                            'phim_id' => $phim->id,
                        ]);
                    }
                    $this->capNhatSoPhimTheLoai();
                    return true;
                }
                return false;
            }
            catch(\Exception $e){
                if($phim){
                    $phim->delete();
                }
                throw new \Exception('Lỗi khi thêm phim: ' . $e->getMessage());
            }
        }
        public function handleWebhookChuyenDoiHLS() {
            $data = json_decode(file_get_contents('php://input'), true);
            $filePathOutput = $data['path_minio_output'];
            $videoUrl = $filePathOutput . 'master.m3u8';
            $phim = Phim::where('video_url', $videoUrl)->first();
            if ($phim) {
                $phim->trang_thai_video = 1; // Đã xử lý xong
                $phim->save();
            } 
        }
        public function docPhim($page, $tuKhoaTimKiem = null, $trangThai = null, $theLoaiId = null, $idRap = null, $doTuoi = null, $year = null, $dangChieu = null, $xemNhieu = null) {
            $query = Phim::with(['TheLoai.TheLoai']);
            
            // Nếu người dùng là Quản lý rạp, chỉ lấy phim được phân phối cho rạp của họ
            if($_SESSION['UserInternal']['VaiTro'] == 'Quản lý rạp') {
                $idRapPhim = $_SESSION['UserInternal']['ID_RapPhim'];
                
                if($idRapPhim) {
                    // Lấy danh sách ID phim được phân phối cho rạp này
                    $phimIdsDaPhanPhoi = [];
                    $phanPhoiRecords = PhanPhoiPhim::where('id_rapphim', $idRapPhim)->get();
                    foreach ($phanPhoiRecords as $record) {
                        $phimIdsDaPhanPhoi[] = $record->id_phim;
                    }
                    
                    // Chỉ lấy phim đã được phân phối cho rạp này
                    if (!empty($phimIdsDaPhanPhoi)) {
                        $query->whereIn('id', $phimIdsDaPhanPhoi);
                    } else {
                        // Nếu không có phim nào được phân phối, trả về kết quả rỗng
                        $query->whereRaw('1=0');
                    }
                }
            }
            
            if ($tuKhoaTimKiem) {
                $query->where(function($q) use ($tuKhoaTimKiem) {
                    $q->where('ten_phim', 'LIKE', "%$tuKhoaTimKiem%")
                    ->orWhere('dao_dien', 'LIKE', "%$tuKhoaTimKiem%")
                    ->orWhere('dien_vien', 'LIKE', "%$tuKhoaTimKiem%");
                });
            }

            if ($trangThai !== null && $trangThai !== '') {
                $query->where('trang_thai', $trangThai);
            }

            if ($theLoaiId) {
                $query->whereHas('TheLoai', function($q) use ($theLoaiId) {
                    $q->where('theloai_id', $theLoaiId);
                });
            }

            // Nếu tham số idRap được truyền vào, ưu tiên dùng tham số này
            if ($idRap && !isset($_SESSION['UserInternal']['VaiTro'])) {
                $phimIdsDaPhanPhoi = \App\Models\PhanPhoiPhim::where('id_rapphim', $idRap)->pluck('id_phim')->toArray();
                if (!empty($phimIdsDaPhanPhoi)) {
                    $query->whereNotIn('id', $phimIdsDaPhanPhoi);
                }
            }

            if ($doTuoi) {
                $query->where('do_tuoi', $doTuoi);
            }

            if ($year) {
                $query->whereYear('created_at', $year);
            }

            if ($dangChieu !== null && $dangChieu !== '') {
                $today = Carbon::today();
                if ($dangChieu === 'dang-chieu') {
                    // Phim đang chiếu
                    $query->whereDate('ngay_cong_chieu', '<=', $today);
                } elseif ($dangChieu === 'sap-chieu') {
                    // Phim sắp chiếu
                    $query->whereDate('ngay_cong_chieu', '>', $today);
                }
            }

            if ($xemNhieu === 'xem-nhieu') {
                $query->withCount(['suatChieu as so_ve_ban' => function($q) {
                    $q->join('ve', 've.suat_chieu_id', '=', 'suatchieu.id');
                }])->orderByDesc('so_ve_ban');
            } else {
                $query->orderBy('id', 'desc');
            }

            $pageSize = 10;
            $total = $query->count();
            $totalPages = ceil($total / $pageSize);

            $phims = $query->orderBy('id', 'desc')
                        ->skip(($page - 1) * $pageSize)
                        ->take($pageSize)
                        ->get();

            return [
                'data' => $phims,
                'total' => $total,
                'total_pages' => $totalPages,
                'current_page' => $page
            ];
        }


        public function themPhanPhoiPhim(){
            $data = json_decode(file_get_contents('php://input'), true);
            $idRap = $data['id_rap'] ?? null;
            $phimId = $data['phim_id'] ?? [];
            $phim = PhanPhoiPhim::create([
                'id_phim' => $phimId,
                'id_rapphim' => $idRap,
            ]);
            if(!$phim){
                throw new \Exception('Lỗi khi thêm phân phối phim');
            }
        }
        public function xoaPhanPhoiPhim(){
            $data = json_decode(file_get_contents('php://input'), true);
            $idRap = $data['id_rap'] ?? null;
            $phimId = $data['phim_id'] ?? [];
            PhanPhoiPhim::where('id_rapphim', $idRap)
                        ->where('id_phim', $phimId)
                        ->delete();
        }
        public function docPhimKH($tuKhoaTimKiem = null, $theLoaiId = null, $doTuoi = null)
        {
            $query = Phim::with(['TheLoai.TheLoai']); // load quan hệ
            $query->where('trang_thai', 1);
            // tìm kiếm theo từ khóa
            if ($tuKhoaTimKiem) {
                $query->where(function ($q) use ($tuKhoaTimKiem) {
                    $q->where('ten_phim', 'LIKE', "%$tuKhoaTimKiem%")
                    ->orWhere('dao_dien', 'LIKE', "%$tuKhoaTimKiem%")
                    ->orWhere('dien_vien', 'LIKE', "%$tuKhoaTimKiem%");
                });
            }

            // lọc theo thể loại
            if ($theLoaiId) {
                $query->whereHas('TheLoai', function ($q) use ($theLoaiId) {
                    $q->where('theloai_id', $theLoaiId);
                });
            }

            if($doTuoi){
                $query->where('do_tuoi', $doTuoi);
            }
            $phims = $query->orderBy('id', 'desc')->get();

            return [
                'data' => $phims
            ];
        }

        public function docPhimKHOnline($tuKhoaTimKiem = null, $theLoaiId = null, $doTuoi = null)
        {
            $query = Phim::with(['TheLoai.TheLoai']); // load quan hệ

            // chỉ lấy phim có url_video
            $query->whereNotNull('video_url')->where('video_url', '!=', '');

            // tìm kiếm theo từ khóa
            if ($tuKhoaTimKiem) {
                $query->where(function ($q) use ($tuKhoaTimKiem) {
                    $q->where('ten_phim', 'LIKE', "%$tuKhoaTimKiem%")
                    ->orWhere('dao_dien', 'LIKE', "%$tuKhoaTimKiem%")
                    ->orWhere('dien_vien', 'LIKE', "%$tuKhoaTimKiem%");
                });
            }

            // lọc theo thể loại
            if ($theLoaiId) {
                $query->whereHas('TheLoai', function ($q) use ($theLoaiId) {
                    $q->where('theloai_id', $theLoaiId);
                });
            }
            if($doTuoi){
                $query->where('do_tuoi', $doTuoi);
            }

            $phims = $query->orderBy('id', 'desc')->get();

            return [
                'data' => $phims
            ];
        }

        public function docPhimMoiNhat() {
            $phims = Phim::with(['TheLoai.TheLoai'])
                        ->where('trang_thai', 1)
                        ->orderBy('id', 'desc') 
                        ->take(4) // lấy 4 phim
                        ->get();

            return [
                'data' => $phims
            ];
        }
        public function docChiTietPhim($id)
        {
            $phim = Phim::with(['TheLoai.TheLoai'])->find($id);
            if (!$phim) {
                throw new \Exception('Phim không tồn tại');
            }
            return [
                'data' => $phim
            ];
        }
        public function suaPhim($id){
            $bucket = "poster";
            $phimCu = null;
            $phim = null;
            
            try{
                $phim = Phim::with('TheLoai')->find($id);
                if(!$phim){
                    throw new \Exception('Phim không tồn tại');
                }
                $phimCu = clone $phim;
                
                $ten = $_POST['ten'] ?? '';
                $daoDien = $_POST['dao_dien'] ?? '';
                $dienVien = $_POST['dien_vien'] ?? '';
                $thoiLuong = $_POST['thoi_luong'] ?? '';
                $doTuoi = $_POST['do_tuoi'] ?? '';
                $quocGia = $_POST['quoc_gia'] ?? '';
                $moTa = $_POST['mo_ta'] ?? '';
                $ngayCongChieu = $_POST['ngay_cong_chieu'] ?? '';
                $trangThai = $_POST['trang_thai'] ?? '';
                $theLoaiIds = $_POST['the_loai_ids'] ?? [];
                $hinhAnh = $_FILES['poster'] ?? null;
                $trailerUrl = $_POST['trailer_url'] ?? '';
                $video = $_FILES['video'] ?? null;
                
                // Xử lý poster
                $posterUrl = $phimCu->poster_url;
                if ($hinhAnh && isset($hinhAnh['name']) && !empty($hinhAnh['name'])) {
                    $fileExtension = pathinfo($hinhAnh['name'], PATHINFO_EXTENSION);
                    $keyName = 'poster_phim' . '_' . time() . '.' . $fileExtension;
                    $posterUrl = $bucket.'/'.$keyName;
                }
                
                // Xử lý video
                $videoUrl = $phimCu->video_url;
                $trangThaiVideo = $phimCu->trang_thai_video;
                if ($video && isset($video['name']) && !empty($video['name'])) {
                    $pathVideoCSDL = 'video/'.'phim' . '_' . time() . '/';
                    $videoUrl = $pathVideoCSDL.'master.m3u8';
                    $trangThaiVideo = 2; // Video đang xử lý
                }
                
                $phim->update([
                    'ten_phim' => $ten,
                    'mo_ta' => $moTa,
                    'ngay_cong_chieu' => $ngayCongChieu,
                    'trang_thai' => $trangThai,
                    'dao_dien' => $daoDien,
                    'dien_vien' => $dienVien,
                    'thoi_luong' => $thoiLuong,
                    'do_tuoi' => $doTuoi,
                    'quoc_gia' => $quocGia,
                    'poster_url' => $posterUrl,
                    'trailer_url' => $trailerUrl,
                    'video_url' => $videoUrl,
                    'trang_thai_video' => $trangThaiVideo
                ]);
                
                if($phim){
                    // Upload poster mới nếu có
                    if($hinhAnh && isset($hinhAnh['tmp_name']) && isset($keyName)){
                        getS3Client()->putObject([
                            'Bucket' => $bucket,
                            'Key'    => $keyName,
                            'SourceFile' => $hinhAnh['tmp_name'],
                        ]);
                        // Xóa poster cũ
                        if($phimCu->poster_url){
                            getS3Client()->deleteObject([
                                'Bucket' => $bucket,
                                'Key'    => str_replace($bucket.'/', '', $phimCu->poster_url),
                            ]);
                        }
                    }
                    
                    // Gọi API Python để convert video nếu có
                    if($video && isset($video['tmp_name'])){
                        // $this->convertVideoThroughAPI($video['tmp_name'], 'private', $pathVideoCSDL);
                        $this->run_hls_uploader($video['tmp_name'], 'private', $pathVideoCSDL, true);
                    }
                    
                    // Cập nhật thể loại
                    Phim_TheLoai::where('phim_id', $phimCu->id)->delete();
                    foreach($theLoaiIds as $theLoaiId){
                        $phim->TheLoai()->create([
                            'theloai_id' => $theLoaiId,
                            'phim_id' => $phim->id,
                        ]);
                    }
                    $this->capNhatSoPhimTheLoai();
                    return true;
                }
                return false;
            }
            catch(\Exception $e){
                throw new \Exception('Lỗi khi sửa phim: ' . $e->getMessage());
            }
        }
        public function phanPhoi($idRap){
            $phimIds = $_POST['phim_ids'] ?? [];
            // Xóa phân phối cũ
            PhanPhoiPhim::where('id_rapphim', $idRap)->delete();
            // Thêm phân phối mới
            foreach ($phimIds as $phimId) {
                PhanPhoiPhim::create([
                    'id_rapphim' => $idRap,
                    'id_phim' => $phimId
                ]);
            }
        }
        public function docPhimTheoRap($idRap){
            $phimIds = PhanPhoiPhim::where('id_rapphim', $idRap)->pluck('id_phim')->toArray();
            $phims = Phim::with(['TheLoai.TheLoai'])->whereIn('id', $phimIds)->get();
            return $phims;
        }
        private function convertVideoThroughAPI($videoTmpPath, $bucket, $path) {
            try {
            $apiUrl = $_ENV['URL_API_PYTHON'] . '/api/video/convert-hls';

            // Tạo CURLFile để upload
            $videoFile = new \CURLFile($videoTmpPath);

            $postData = [
                'video' => $videoFile,
                'bucket_output' => $bucket,
                'path_minio_output' => $path
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 1800); // 30 phút timeout

            $response = curl_exec($ch);
            // $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // Xử lý response nếu cần
            // if ($httpCode !== 200) {
            //     throw new \Exception("API Python trả về lỗi: " . $httpCode);
            // }
            // $result = json_decode($response, true);
            // if (!$result['success']) {
            //     throw new \Exception("Lỗi từ API Python: " . ($result['message'] ?? 'Unknown error'));
            // }

            } catch (\Exception $e) {
            // Log lỗi nhưng không throw để không làm fail việc tạo phim
            error_log("Lỗi convert video: " . $e->getMessage());
            }
        }
        /**
         * Chạy binary hls_uploader từ PHP
         *
         * @param string $inputPath  đường dẫn file input (tmp_name)
         * @param string $minioAlias mc alias (ví dụ myminio)
         * @param string $bucket
         * @param string $outPrefix  đường dẫn output trong bucket (ví dụ video/phim_123)
         * @param bool   $background nếu true => chạy background và trả về pid
         * @return array
         */
        function run_hls_uploader(string $inputPath, string $bucket, string $outPrefix, bool $background = false) {
            $binary = __DIR__ . '/../../bin/videotohls';

            // Validate basic
            if (!file_exists($binary) || !is_executable($binary)) {
                throw new \Exception('HLS uploader binary not found or not executable');
            }
            if (!file_exists($inputPath)) {
                throw new \Exception('Input file not found');
            }

            // Escape args
            $cmd = escapeshellcmd($binary)
                . ' --input ' . escapeshellarg($inputPath)
                . ' --bucket ' . escapeshellarg($bucket)
                . ' --out-prefix ' . escapeshellarg($outPrefix);

            if ($background) {
                // redirect stdout/stderr to log, run in background and return PID
                $log = __DIR__ . '/../../cache/log/hls_uploader.log';
                // ensure log is writable by PHP user
                $bgCmd = $cmd . " > " . escapeshellarg($log) . " 2>&1 & echo $!";
                exec($bgCmd, $out, $ret);
                if ($ret === 0 && isset($out[0])) {
                    // return ['success'=>true, 'pid'=> (int)$out[0], 'log'=>$log];
                } else {
                    throw new \Exception('Failed to start HLS uploader in background');
                }
            } else {
                // chạy đồng bộ: trả về output và exit code
                exec($cmd . ' 2>&1', $output, $exitCode);
                if ($exitCode !== 0) {
                    throw new \Exception('HLS uploader failed: ' . implode("\n", $output));
                }
            }
        }
    }
?>