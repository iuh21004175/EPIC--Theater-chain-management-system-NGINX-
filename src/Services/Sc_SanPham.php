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
            return DanhMuc::all();
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
            $data = json_decode(file_get_contents('php://input'), true);
            $ten = $data['ten'];
            $mo_ta = $data['mo_ta'];
            $gia = $data['gia'];
            $danh_muc_id = $data['danh_muc_id'];
            $sanPham = SanPham::find($id);
            if($sanPham){
                $sanPham->ten = $ten;
                $sanPham->mo_ta = $mo_ta;
                $sanPham->gia = $gia;
                $sanPham->danh_muc_id = $danh_muc_id;
                $sanPham->id_rapphim = $_SESSION['UserInternal']['ID_RapPhim'];
                return $sanPham->save();
            }
            return false;
        }

        public function docSanPhamTheoRap($idRap)
        {
            return SanPham::with('danhMuc')
                ->where('id_rapphim', $idRap)
                ->get();
        }
    }
?>