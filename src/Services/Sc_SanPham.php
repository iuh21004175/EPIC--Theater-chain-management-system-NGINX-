<?php
    namespace App\Services;
    use App\Models\DanhMuc;
    use App\Models\SanPham;
    use function App\Core\getS3Client;
use Exception;

    class Sc_SanPham {
        // Properties and methods for the Sc_SanPham class
        public function themDanhMuc(){
            $ten = $_POST['ten'];
            return DanhMuc::create(['ten' => $ten]);
        }
        public function docDanhMuc(){
            // Lấy danh sách danh mục và đếm số sản phẩm theo rạp hiện tại
            $idRapPhim = $_SESSION['UserInternal']['ID_RapPhim'] ?? null;
            
            if ($idRapPhim) {
                // Đếm số sản phẩm theo từng danh mục và rạp
                $danhMucs = DanhMuc::all();
                return $danhMucs->map(function($danhMuc) use ($idRapPhim) {
                    $soSanPham = SanPham::where('danh_muc_id', $danhMuc->id)
                        ->where('id_rapphim', $idRapPhim)
                        ->count();
                    return [
                        'id' => $danhMuc->id,
                        'ten' => $danhMuc->ten,
                        'so_sanpham' => $soSanPham
                    ];
                });
            } else {
                // Nếu không có rạp, trả về tất cả danh mục với số sản phẩm = 0
                return DanhMuc::all()->map(function($danhMuc) {
                    return [
                        'id' => $danhMuc->id,
                        'ten' => $danhMuc->ten,
                        'so_sanpham' => 0
                    ];
                });
            }
        }
        public function suaDanhMuc($id){
            $data = json_decode(file_get_contents('php://input'), true);
            $ten = $data['ten'];
            $danhMuc = DanhMuc::find($id);
            if($danhMuc){
                $danhMuc->ten = $ten;
                return $danhMuc->save();
            }
            return false;
        }
        public function themSanPham(){
            $ten = $_POST['ten'];
            $mo_ta = $_POST['mo_ta'];
            $gia = $_POST['gia'];
            $hinh_anh = $_FILES['hinh_anh']['name'];
            $danh_muc_id = $_POST['danh_muc_id'];
            $sanPham = null;
            $bucket = 'san-pham';
            try {
                $sanPham = SanPham::create([
                    'ten' => $ten,
                    'mo_ta' => $mo_ta,
                    'gia' => $gia,
                    'danh_muc_id' => $danh_muc_id,
                    'id_rapphim'=> $_SESSION['UserInternal']['ID_RapPhim']
                ]);
                if ($hinh_anh) {
                    $fileName = 'san_pham' . '_' . time() . '.' . pathinfo($hinh_anh, PATHINFO_EXTENSION);
                    getS3Client()->putObject([
                        'Bucket' => $bucket,
                        'Key'    => $fileName,
                        'SourceFile' => $_FILES['hinh_anh']['tmp_name']
                    ]);
                    $sanPham->hinh_anh = $bucket . '/' . $fileName;
                    $sanPham->save();
                }
                return $sanPham;
            }
            catch (\Exception $e) {
                if ($sanPham) {
                    $sanPham->delete();
                }
                throw new Exception($e);
            }
        }
        public function docSanPham($id = null, $danh_muc_id = null, $tukhoa = null){
            $query = SanPham::with('danhMuc')->where('id_rapphim', $_SESSION['UserInternal']['ID_RapPhim']);
            if ($id) {
                $query->where('id', $id);
            }
            if ($danh_muc_id) {
                $query->where('danh_muc_id', $danh_muc_id);
            }
            if ($tukhoa) {
                $query->where('ten', 'like', '%' . $tukhoa . '%')
                    ->orWhere('mo_ta', 'like', '%' . $tukhoa . '%');
            }
            return $query->get();
        }
        public function suaSanPham($id){
            $ten = $_POST['ten'] ?? '';
            $mo_ta = $_POST['mo_ta'] ?? '';
            $gia = $_POST['gia'] ?? '';
            $danh_muc_id = $_POST['danh_muc_id'] ?? '';
            
            $sanPham = SanPham::find($id);
            if(!$sanPham){
                throw new \Exception('Sản phẩm không tồn tại');
            }
            
            $sanPham->ten = $ten;
            $sanPham->mo_ta = $mo_ta;
            $sanPham->gia = $gia;
            $sanPham->danh_muc_id = $danh_muc_id;
            $sanPham->id_rapphim = $_SESSION['UserInternal']['ID_RapPhim'];
            
            // Xử lý upload hình ảnh nếu có
            if (isset($_FILES['hinh_anh']) && $_FILES['hinh_anh']['error'] === UPLOAD_ERR_OK) {
                $hinh_anh = $_FILES['hinh_anh'];
                $bucket = 'san-pham';
                $fileName = 'san_pham' . '_' . time() . '.' . pathinfo($hinh_anh['name'], PATHINFO_EXTENSION);
                try {
                    getS3Client()->putObject([
                        'Bucket' => $bucket,
                        'Key'    => $fileName,
                        'SourceFile' => $hinh_anh['tmp_name']
                    ]);
                    $sanPham->hinh_anh = $bucket . '/' . $fileName;
                } catch (\Exception $e) {
                    // Nếu upload thất bại, giữ nguyên hình ảnh cũ
                    error_log('Lỗi upload ảnh sản phẩm: ' . $e->getMessage());
                }
            }
            $sanPham->save();
        }

        public function docSanPhamTheoRap($idRap)
        {
            return SanPham::with('danhMuc')
                ->where('id_rapphim', $idRap)
                ->get();
        }
    }
?>