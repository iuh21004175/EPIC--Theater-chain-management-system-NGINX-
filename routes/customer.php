<?php
use App\Controllers\Ctrl_XacThucCustomer;
use App\Controllers\Ctrl_Phim;
use App\Controllers\Ctrl_RapPhim;
use App\Controllers\Ctrl_KhachHang;
use App\Controllers\Ctrl_Ghe;
use App\Controllers\Ctrl_DonHang;
use App\Controllers\Ctrl_SanPhamAnUong;
use App\Controllers\Ctrl_TheQuaTang;
use App\Controllers\Ctrl_TuVan;
use App\Controllers\Ctrl_GoiVideo;
use App\Controllers\Ctrl_TinTuc;
use function App\Core\view;

// Vai trò: Khách hàng thành viên, Khách hàng vãng lại
$dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {
    $r->addRoute('GET', '/', [Ctrl_XacThucCustomer::class, 'index']);
    $r->addRoute('GET', '/dang-xuat', [Ctrl_XacThucCustomer::class, 'dangXuat']);
    $r->addRoute('GET', '/doi-mat-khau', [Ctrl_XacThucCustomer::class, 'doiMatKhau']);
    $r->addRoute('GET', '/thong-tin-ca-nhan', [Ctrl_KhachHang::class, 'index']);
    $r->addRoute('GET', '/phim', [Ctrl_Phim::class, 'indexKhachHang']);
    $r->addRoute('GET', '/lich-chieu', [Ctrl_Phim::class, 'lichChieu']);
    $r->addRoute('GET', '/dat-ve/{id}', [Ctrl_Phim::class, 'datVe']);
    $r->addRoute('GET', '/dat-ve-online/{id}', [Ctrl_Phim::class, 'datVeOnline']);
    $r->addRoute('GET', '/rap/{id}', [Ctrl_RapPhim::class, 'rapKhachHang']);
    $r->addRoute('GET', '/goc-dien-anh', [Ctrl_Phim::class, 'gocDienAnh']);
    $r->addRoute('GET', '/goc-dien-anh/{name}', [Ctrl_Phim::class, 'chiTietPhim']);
    $r->addRoute('GET', '/reset-password', [Ctrl_XacThucCustomer::class, 'resetPassword']);
    $r->addRoute('GET', '/so-do-ghe/{id}', [Ctrl_Ghe::class, 'soDoGhe']);
    $r->addRoute('GET', '/ve-cua-toi', [Ctrl_DonHang::class, 'index']);
    $r->addRoute('GET', '/the-qua-tang', [Ctrl_TheQuaTang::class, 'index']);
    $r->addRoute('GET', '/san-pham', [Ctrl_SanPhamAnUong::class, 'sanPham']);
    $r->addRoute('GET', '/tin-tuc', [Ctrl_TinTuc::class, 'index']);
    $r->addRoute('GET', '/chi-tiet-tin-tuc/{id}', [Ctrl_TinTuc::class, 'chiTiet']);
    $r->addRoute('GET', '/tu-van/chat-truc-tuyen', [Ctrl_TuVan::class, 'pageChatTrucTuyen']);
    $r->addRoute('GET', '/tu-van/goi-video', [Ctrl_TuVan::class, 'khachHangDatLichGoiVideo']);
    $r->addRoute('GET', '/tu-van/dat-lich-goi-video', [Ctrl_GoiVideo::class, 'pageDatLichGoiVideo']);
    $r->addRoute('GET', '/video-call', [Ctrl_GoiVideo::class, 'pageVideoCall']);
});

$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        http_response_code(404);
        echo view("customer.404");
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        echo view("internal.405");
        break;
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];
        if (is_array($handler)) {
            if(count($handler) == 3){
                // Kiểm tra phân quyền
                $requiredRoles = $handler[2]; // Lấy vai trò yêu cầu từ định tuyến
                if (!isset($_SESSION['UserInternal']) || !in_array($_SESSION['UserInternal']['VaiTro'], $requiredRoles)) {
                    // Người dùng không có quyền truy cập
                    echo view("internal.403");
                    exit();
                }
            }
            // Sửa lại: lấy class và method từ array $handler
            $class = $handler[0];  // Ctrl_XacThuc::class
            $method = $handler[1]; // 'indexDangNhap' hoặc 'index'
                
            $controller = new $class();
            $result = call_user_func([$controller, $method], $vars);
            if (is_array($result)) {
                echo json_encode($result);
            } else {
                echo $result;
            }
            } else {
                echo call_user_func($handler, $vars);
            }        
        break;
}
?>
