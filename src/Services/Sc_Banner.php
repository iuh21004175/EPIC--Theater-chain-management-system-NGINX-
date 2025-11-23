<?php
    namespace App\Services;
    use App\Models\Banner;
    use function App\Core\getS3Client;
    class Sc_Banner {
        public function them(){
            // Kiểm tra file upload có tồn tại và hợp lệ không
            if (!isset($_FILES['AnhUrl']) || $_FILES['AnhUrl']['error'] !== UPLOAD_ERR_OK) {
                throw new \Exception("Vui lòng tải lên hình ảnh banner");
            }
            
            // Kiểm tra file có phải là ảnh không
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $fileType = $_FILES['AnhUrl']['type'];
            if (!in_array($fileType, $allowedTypes)) {
                throw new \Exception("Chỉ chấp nhận file ảnh định dạng JPG, PNG hoặc GIF");
            }
            
            // Kiểm tra kích thước file (tối đa 2MB)
            $maxSize = 2 * 1024 * 1024; // 2MB
            if ($_FILES['AnhUrl']['size'] > $maxSize) {
                throw new \Exception("Kích thước file không được vượt quá 2MB");
            }
            
            $bucket = "banner";
            $extension = pathinfo($_FILES['AnhUrl']['name'], PATHINFO_EXTENSION);
            $key = "banner_".time().".".$extension;
            
            getS3Client()->putObject([
                'Bucket' => $bucket,
                'Key'    => $key,
                'SourceFile' => $_FILES['AnhUrl']['tmp_name'],
            ]);
            
            Banner::create([
                'anh_url' => $bucket."/".$key
            ]);
        }
        public function suaAnh($id){
            $banner = Banner::find($id);
            if (!$banner) {
                throw new \Exception("Banner không tồn tại.");
            }
            if(!isset($_FILES['AnhUrl'])) {
                throw new \Exception("Không tìm thấy file upload với tên AnhUrl");
            }
            $bucket = "banner";

            // Chỉ xử lý nếu có file mới
            if (isset($_FILES['AnhUrl']) && $_FILES['AnhUrl']['tmp_name']) {
                $newKey = "banner_".time().".".pathinfo($_FILES['AnhUrl']['name'], PATHINFO_EXTENSION);
                getS3Client()->putObject([
                    'Bucket' => $bucket,
                    'Key'    => $newKey,
                    'SourceFile' => $_FILES['AnhUrl']['tmp_name'],
                ]);
                // Xóa ảnh cũ
                getS3Client()->deleteObject([
                    'Bucket' => $bucket,
                    'Key'    => $banner->anh_url
                ]);
                $banner->anh_url = $bucket . "/" . $newKey;
                $banner->save();
            }
            
            
        }
        public function xoa($id){
            $banner = Banner::find($id);
            if (!$banner) {
                throw new \Exception("Banner không tồn tại.");
            }
            $bucket = "banner";
            getS3Client()->deleteObject([
                'Bucket' => $bucket,
                'Key'    => $banner->anh_url
            ]);
            $banner->delete();
            $bannners = Banner::where('trang_thai', 1)
                ->orderBy('thu_tu')
                ->get();
            if($bannners->count() > 0) {
                $currentThuTu = 1;
                foreach($bannners as $b) {
                    $b->thu_tu = $currentThuTu++;
                    $b->save();
                }
            }
        }
        public function thayDoiTrangThai($id){
            $banner = Banner::find($id);
            $data = json_decode(file_get_contents('php://input'), true);
            $trangThai = $data['trangThai'];
            $thuTu = $data['thuTu'];
            if (!$banner) {
                throw new \Exception("Banner không tồn tại.");
                exit();
            }
            $banner->trang_thai = $trangThai;
            if($trangThai == 1 && $thuTu) {
                $banner->thu_tu = $thuTu;
            }
            else if($trangThai == 0) {
                $banner->thu_tu = null;
            }
            $banner->save();
            // Gán lại giá trị thu_tu cho các banner có thu_tu lớn hơn banner hiện tại
            $banners = Banner::where('trang_thai', 1)
                ->orderBy('thu_tu')
                ->get();
           
            if($trangThai == 0 && $banners->count() > 0) {
                $currentThuTu = 1;
                foreach($banners as $b) {
                    $b->thu_tu = $currentThuTu++;
                    $b->save();
                }
            }
        }
        public function docSideShow(){
            $banners = Banner::where('trang_thai', 1)->orderBy('thu_tu')->get();
            return $banners;
        }
        public function docTatCa(){
            $banners = Banner::all();
            return $banners;
        }
        public function capNhatSideShow(){
            $data = json_decode(file_get_contents('php://input'), true);
            $ids = $data['ids'];
            foreach($ids as $index => $id){
                $banner = Banner::find($id);
                if ($banner) {
                    $banner->thu_tu = $index + 1;
                    $banner->trang_thai = 1; // Đảm bảo tất cả banner trong danh sách đều được hiển thị
                    $banner->save();
                }
            }
            Banner::whereNotIn('id', $ids)->update(['trang_thai' => 0]);
        }
    }
?>