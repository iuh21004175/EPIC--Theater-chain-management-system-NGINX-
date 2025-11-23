<?php
    namespace App\Services;
    use App\Models\TaiKhoanInternal;

    class Sc_TaiKhoanInternal {
        public function them(){
            $taiKhoan = null;
            try{
                $data = file_get_contents('php://input');
                $json = json_decode($data, true);
                $taiKhoan = TaiKhoanInternal::create([
                    'tendangnhap' => $json['tendangnhap'],
                    'matkhau_bam' => password_hash($json['matkhau'], PASSWORD_ARGON2ID),
                    'id_vaitro' => 3
                ]);
                if($taiKhoan){
                    $taiKhoan->nguoiDungInternals()->create([
                        'ten' => $json['ten'],
                        'email' => $json['email'],
                        'dien_thoai' => $json['dien_thoai'],
                        'id_taikhoan' => $taiKhoan->id,
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
        public function doc($id = null){
            $query = TaiKhoanInternal::with('vaiTro', 'nguoiDungInternals.rapPhim')
                ->whereNotIn('id_vaitro', [1, 2, 4]);  // Exclude Admin (1), Quản lý chuỗi rạp (2), and another role (4)

            if ($id) {
                $query->where('id', $id);
            }

            return $query->get();
        }
        public function phanCong($id){
            $data = file_get_contents('php://input');
            $json = json_decode($data, true);
            $taiKhoan = TaiKhoanInternal::find($id);
            if($taiKhoan){
                $taiKhoan->nguoiDungInternals()->update([
                    'id_rapphim' => $json['id_rapphim']
                ]);
                return $taiKhoan->save();
            }
            return false;
        }
        public function sua($id){
            $data = file_get_contents('php://input');
            $json = json_decode($data, true);
            $taiKhoan = TaiKhoanInternal::find($id);
            if($taiKhoan){
                // Update user info in the nguoiDungInternals relation
                $taiKhoan->nguoiDungInternals()->update([
                    'ten' => $json['ten'],
                    'email' => $json['email'],
                    'dien_thoai' => $json['dien_thoai'],
                    // Remove tendangnhap from here as it doesn't belong in this model
                ]);
                
                // Update tendangnhap in the TaiKhoanInternal model itself
                if (isset($json['tendangnhap'])) {
                    $taiKhoan->tendangnhap = $json['tendangnhap'];
                }
                
                // Rest of the method stays the same...
                if($json['dat_lai_mat_khau'] == true){
                    $matKhauMoi = bin2hex(random_bytes(4)); // Generates 8 characters
                    $taiKhoan->matkhau_bam = password_hash($json['mat_khau_moi'], PASSWORD_ARGON2ID);
                    // Gửi email thông báo mật khẩu mới
                    // Dũng cài đặt thư viện gửi email phpmailer
                }
                if($json['khoa_tai_khoan'] == true){
                    $taiKhoan->nguoiDungInternals()->update([
                        'trang_thai' => 0
                    ]);
                } else {
                    $taiKhoan->nguoiDungInternals()->update([
                        'trang_thai' => 1
                    ]);
                }
                return $taiKhoan->save();
            }
            return false;
        }
    }
?>