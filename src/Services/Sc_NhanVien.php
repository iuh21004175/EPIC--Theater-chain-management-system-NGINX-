<?php
    namespace App\Services;
    use App\Models\TaiKhoanInternal;
    use App\Models\NguoiDungInternal;
    class Sc_NhanVien{
        public function them(){
            $ten = $_POST['ten'] ?? '';
            $email = $_POST['email'] ?? '';
            $dienThoai = $_POST['dien_thoai'] ?? '';
            $tenDangNhap = $_POST['ten_dang_nhap'] ?? '';
            $matKhau = $_POST['mat_khau'] ?? '';
            $taiKhoan = null;
            try{
                $taiKhoan = TaiKhoanInternal::create([
                    'tendangnhap' => $tenDangNhap,
                    'matkhau_bam' => password_hash($matKhau, PASSWORD_ARGON2ID),
                    'id_vaitro' => 4 // Vai trò nhân viên
                ]);
                if($taiKhoan){
                    $taiKhoan->nguoiDungInternals()->create([
                        'ten' => $ten,
                        'email' => $email,
                        'dien_thoai' => $dienThoai,
                        'id_taikhoan' => $taiKhoan->id,
                        'id_rapphim' => $_SESSION['UserInternal']['ID_RapPhim'] // Gán id_rapphim từ người quản lý rạp đang đăng nhập
                    ]);
                    return true;
                }
                return false;
            } catch (\Exception $e) {
                // Xử lý lỗi
                $taiKhoan?->delete();
                throw new \Exception($e->getMessage());
            }
        }
        public function doc($page = 1, $perPage = 10) {
            // Get the currently logged-in theater manager's id_rapphim
            $idRapPhim = null;
            if (isset($_SESSION['UserInternal']) && isset($_SESSION['UserInternal']['ID_RapPhim'])) {
                $idRapPhim = $_SESSION['UserInternal']['ID_RapPhim'];
            }
            
            // Get total count for pagination
            $total = NguoiDungInternal::whereHas('taiKhoan', function($query) use ($idRapPhim) {
                    $query->where('id_vaitro', 4) // Role id for staff
                            ->where('id_rapphim', $idRapPhim);
                })
                ->where('id_rapphim', $idRapPhim)
                ->count();
            
            // Query staff members with pagination
            $employees = NguoiDungInternal::with('taiKhoan')
                ->whereHas('taiKhoan', function($query) {
                    $query->where('id_vaitro', 4); // Role id for staff
                })
                ->where('id_rapphim', $idRapPhim)
                ->orderBy('created_at', 'desc')
                ->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get();
            
            // Return both data and pagination metadata
            return [
                'data' => $employees,
                'pagination' => [
                    'current_page' => (int)$page,
                    'per_page' => (int)$perPage,
                    'total' => $total,
                    'total_pages' => ceil($total / $perPage)
                ]
            ];
        }
        public function sua($id){
            $data = file_get_contents('php://input');
            $json = json_decode($data, true);
            $ten = $json['ten'] ?? '';
            $email = $json['email'] ?? '';
            $dienThoai = $json['dien_thoai'] ?? '';
            $tenDangNhap = $json['ten_dang_nhap'] ?? '';

            $nguoiDung = NguoiDungInternal::find($id);
            if($nguoiDung){
                // Kiểm tra email có bị trùng với người dùng khác không
                $emailTrung = NguoiDungInternal::where('email', $email)
                    ->where('id', '!=', $id)
                    ->first();
                
                if($emailTrung){
                    throw new \Exception('Email đã tồn tại');
                }
                
                $nguoiDung->ten = $ten;
                $nguoiDung->email = $email;
                $nguoiDung->dien_thoai = $dienThoai;
                $nguoiDung->taiKhoan->tendangnhap = $tenDangNhap;
                $nguoiDung->save();
                return true;
            }
             return false;
        }
        public function trangThai($id){
            $data = file_get_contents('php://input');
            $json = json_decode($data, true);
            $trangThai = isset($json['trang_thai']) ? (int)$json['trang_thai'] : null;

            // Tìm người dùng thông qua ID tài khoản
            $nguoiDung = NguoiDungInternal::find($id);
            
            if($nguoiDung && $trangThai !== null){
                // Cập nhật trạng thái: 1 = đang hoạt động, -1 = đã nghỉ việc
                $nguoiDung->trang_thai = $trangThai;
                return $nguoiDung->save();
            }
            return false;
        }
    }
?>