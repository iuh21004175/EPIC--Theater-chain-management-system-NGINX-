<?php
    namespace App\Controllers;
    use function App\Core\view;
    use App\Services\Sc_XacThucInternal;

    class Ctrl_XacThucInternal
    {
        public function index()
        {
            return view('internal.dang-nhap');
        }
        public function pageBangDieuKhien()
        {
            return view('internal.bang-dieu-khien');
        }
        public function pageQuenMatKhau()
        {
            return view('internal.quen-mat-khau');
        }
        public function pageDoiMatKhau()
        {
            return view('internal.doi-mat-khau');
        }
        public function dangNhap()
        {
            try{
                // Xử lý đăng nhập
                $scXacThuc = new Sc_XacThucInternal();
                if ($scXacThuc->scDangNhap()) {
                    // Đăng nhập thành công, chuyển hướng đến trang dashboard
                    return [ 'status' => 'success', 'message' => 'Đăng nhập thành công!', 'redirect' => './bang-dieu-khien' ];
                } else {
                    // Đăng nhập thất bại, hiển thị thông báo lỗi
                    $error = "Tên đăng nhập hoặc mật khẩu không đúng.";
                    return [ 'status' => 'error', 'message' => $error ];
                }
            } catch (\Exception $e) {
                return [ 'status' => 'error', 'message' => 'Đã xảy ra lỗi. Vui lòng thử lại sau.', 'error' => $e->getMessage() ];
            }
        }
        public function dangXuat()
        {
            // Xử lý đăng xuất
            session_destroy();
            header('Location: ' . $_ENV['URL_INTERNAL_BASE'] . '/');
            exit();
        }
        public function xacThucEmailLayLaiMatKhau()
        {
            try {
                $scXacThuc = new Sc_XacThucInternal();
                $scXacThuc->scXacThucEmailLayLaiMatKhau();
                return [
                    'success'  => true,
                    'message'  => 'Mật khẩu mới đã được gửi đến email của bạn. Vui lòng kiểm tra hộp thư đến.',
                    'href'     => $_ENV['URL_INTERNAL_BASE'] . '/'
                ];
            } catch (\Exception $e) {
                return [
                    'success'  => false,
                    'message' => 'Đã xảy ra lỗi. Vui lòng thử lại sau.',
                    'error'   => $e->getMessage()
                ];
            }
        }
        public function doiMatKhau()
        {
            try {
                $scXacThuc = new Sc_XacThucInternal();
                $scXacThuc->scDoiMatKhau();
                return [
                    'success'  => true,
                    'message'  => 'Đổi mật khẩu thành công.',
                    'href'     => $_ENV['URL_INTERNAL_BASE'] . '/'
                ];
            } catch (\Exception $e) {
                return [
                    'success'  => false,
                    'message' => 'Đã xảy ra lỗi. Vui lòng thử lại sau.',
                    'error'   => $e->getMessage()
                ];
            }
        }
    }
?>