<?php
use App\Controllers\Ctrl_XacThucInternal;
use App\Controllers\Ctrl_TaiKhoanInternal;
use App\Controllers\Ctrl_RapPhim;
use App\Controllers\Ctrl_NhanVien;
use App\Controllers\Ctrl_XacThucCustomer;
use App\Controllers\Ctrl_KhachHang;
use App\Controllers\Ctrl_Phim;
use App\Controllers\Ctrl_Ghe;
use App\Controllers\Ctrl_PhongChieu;
use App\Controllers\Ctrl_SuatChieu;
use App\Controllers\Ctrl_KeHoachSuatChieu;
use App\Controllers\Ctrl_GanNgay;
use App\Controllers\Ctrl_GiaVe;
use App\Controllers\Ctrl_SanPhamAnUong;
use App\Controllers\Ctrl_Ve;
use App\Controllers\Ctrl_DonHang;
use App\Controllers\Ctrl_DuyetSuatChieu;
use App\Controllers\Ctrl_GiaoDich;
use App\Controllers\Ctrl_PhanCong;
use App\Controllers\Ctrl_ChiTietDonHang;
use App\Controllers\Ctrl_TheQuaTang;
use App\Controllers\Ctrl_DanhGia;
use App\Controllers\Ctrl_Banner;
use App\Controllers\Ctrl_MuaPhim;
use App\Controllers\Ctrl_TuVan;
use App\Controllers\Ctrl_GoiVideo;
use App\Controllers\Ctrl_ChatBotAI;
use App\Controllers\Ctrl_ThongKe;
use App\Controllers\Ctrl_TinTuc;
use App\Controllers\Ctrl_LuongThuong;
use App\Controllers\Ctrl_ChamCong;
use App\Controllers\Ctrl_DinhVi;

/**
 * Làm sạch mảng đệ quy để đảm bảo tất cả string đều là UTF-8 hợp lệ
 * 
 * @param mixed $data Dữ liệu cần làm sạch
 * @return mixed Dữ liệu đã được làm sạch
 */
function cleanArrayForJson($data) {
    if (is_array($data)) {
        return array_map('cleanArrayForJson', $data);
    } elseif (is_string($data)) {
        // Loại bỏ ký tự không hợp lệ
        $data = mb_convert_encoding($data, 'UTF-8', 'UTF-8');
        // Loại bỏ ký tự control không hợp lệ (giữ lại \n, \r, \t)
        $data = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $data);
        return $data;
    } else {
        return $data;
    }
}

$dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {
    $r->addRoute('POST', '/dang-nhap', [Ctrl_XacThucInternal::class, 'dangNhap']);
    $r->addRoute('POST', '/nhan-vien-quen-mat-khau', [Ctrl_XacThucInternal::class, 'xacThucEmailLayLaiMatKhau']);
    $r->addRoute('POST', '/doi-mat-khau', [Ctrl_XacThucInternal::class, 'doiMatKhau', ['Nhân viên', 'Quản lý rạp']]);
    $r->addRoute('POST', '/tai-khoan', [Ctrl_TaiKhoanInternal::class, 'themTaiKhoan', ['Admin']]);
    $r->addRoute('GET', '/tai-khoan', [Ctrl_TaiKhoanInternal::class, 'docTaiKhoan', ['Admin']]);
    $r->addRoute('GET', '/tai-khoan/{id:\d+}', [Ctrl_TaiKhoanInternal::class, 'docTaiKhoan', ['Admin']]);
    $r->addRoute('PUT', '/tai-khoan/{id:\d+}/phan-cong', [Ctrl_TaiKhoanInternal::class, 'phanCongTaiKhoan', ['Admin']]);
    $r->addRoute('PUT', '/tai-khoan/{id:\d+}', [Ctrl_TaiKhoanInternal::class, 'suaTaiKhoan', ['Admin']]);
    $r->addRoute('POST', '/rap-phim', [Ctrl_RapPhim::class, 'themRapPhim', ['Quản lý chuỗi rạp']]);
    $r->addRoute('GET', '/rap-phim', [Ctrl_RapPhim::class, 'docRapPhim', ['Quản lý chuỗi rạp', 'Admin']]);
    $r->addRoute('GET', '/rap-phim/{id:\d+}/trang-thai', [Ctrl_RapPhim::class, 'thayDoiTrangThai', ['Quản lý chuỗi rạp']]);
    $r->addRoute('POST', '/rap-phim/{id:\d+}', [Ctrl_RapPhim::class, 'suaRapPhim', ['Quản lý chuỗi rạp']]);
    $r->addRoute('POST', '/nhan-vien', [Ctrl_NhanVien::class, 'themNhanVien', ['Quản lý rạp']]);
    $r->addRoute('GET', '/nhan-vien', [Ctrl_NhanVien::class, 'docNhanVien', ['Quản lý rạp']]);
    $r->addRoute('PUT', '/nhan-vien/{id:\d+}', [Ctrl_NhanVien::class, 'suaNhanVien', ['Quản lý rạp']]);
    $r->addRoute('PUT', '/nhan-vien/{id:\d+}/trang-thai', [Ctrl_NhanVien::class, 'thayDoiTrangThai', ['Quản lý rạp']]);
    $r->addRoute('POST', '/the-loai-phim', [Ctrl_Phim::class, 'themTheLoaiPhim', ['Quản lý chuỗi rạp']]);
    $r->addRoute('GET', '/the-loai-phim', [Ctrl_Phim::class, 'docTheLoaiPhim', ['Quản lý chuỗi rạp']]);
    $r->addRoute('PUT', '/the-loai-phim/{id:\d+}', [Ctrl_Phim::class, 'suaTenTheLoaiPhim', ['Quản lý chuỗi rạp']]);
    $r->addRoute('POST', '/phim', [Ctrl_Phim::class, 'themPhim', ['Quản lý chuỗi rạp']]);
    $r->addRoute('GET', '/phim/', [Ctrl_Phim::class, 'docPhim', ['Quản lý chuỗi rạp', 'Quản lý rạp']]);
    $r->addRoute('POST', '/phim/{id:\d+}', [Ctrl_Phim::class, 'suaPhim', ['Quản lý chuỗi rạp']]);
    $r->addRoute('POST', '/phim/{id:\d+}/phan-phoi', [Ctrl_Phim::class, 'phanPhoiPhim', ['Quản lý chuỗi rạp']]);
    $r->addRoute('POST', '/phim/chuyen-doi-hls-hoan-tat', [Ctrl_Phim::class, 'webhookChuyenDoiHLSHoanTat']);
    $r->addRoute('GET', '/phan-phoi-phim/{id:\d+}', [Ctrl_Phim::class, 'docPhimTheoRap', ['Quản lý chuỗi rạp']]);
    $r->addRoute('PUT', '/phan-phoi-phim/them', [Ctrl_Phim::class, 'themPhanPhoi', ['Quản lý chuỗi rạp']]);
    $r->addRoute('PUT', '/phan-phoi-phim/xoa', [Ctrl_Phim::class, 'xoaPhanPhoi', ['Quản lý chuỗi rạp']]);
    $r->addRoute('POST', '/ghe', [Ctrl_Ghe::class, 'themGhe', ['Quản lý chuỗi rạp']]);
    $r->addRoute('GET', '/ghe', [Ctrl_Ghe::class, 'docGhe', ['Quản lý chuỗi rạp', 'Quản lý rạp']]);
    $r->addRoute('PUT', '/ghe/{id:\d+}', [Ctrl_Ghe::class, 'suaGhe', ['Quản lý chuỗi rạp']]);
    $r->addRoute('POST', '/phong-chieu', [Ctrl_PhongChieu::class, 'themPhongChieu', ['Quản lý rạp']]);
    $r->addRoute('PUT', '/phong-chieu/{id:\d+}', [Ctrl_PhongChieu::class, 'capNhatPhongChieu', ['Quản lý rạp']]);
    $r->addRoute('GET', '/phong-chieu', [Ctrl_PhongChieu::class, 'docPhongChieu', ['Quản lý rạp', 'Quản lý chuỗi rạp']]);
    $r->addRoute('POST', '/suat-chieu', [Ctrl_SuatChieu::class, 'themSuatChieu', ['Quản lý rạp']]);
    $r->addRoute('GET', '/suat-chieu', [Ctrl_SuatChieu::class, 'docSuatChieu', ['Quản lý chuỗi rạp', 'Quản lý rạp']]);
    $r->addRoute('GET', '/suat-chieu/tao-khung-gio-goi-y', [Ctrl_SuatChieu::class, 'taoKhungGioGoiY', ['Quản lý rạp']]);
    $r->addRoute('GET', '/suat-chieu/kiem-tra-hop-le', [Ctrl_SuatChieu::class, 'kiemTraSuatChieuHopLe', ['Quản lý rạp']]);
    $r->addRoute('PUT', '/suat-chieu/{id:\d+}', [Ctrl_SuatChieu::class, 'suaSuatChieu', ['Quản lý rạp']]);
    $r->addRoute('DELETE', '/suat-chieu/{id:\d+}', [Ctrl_SuatChieu::class, 'xoaSuatChieu', ['Quản lý rạp']]);
    $r->addRoute('POST', '/suat-chieu/{id:\d+}/hoan-tac', [Ctrl_SuatChieu::class, 'hoanTacSuatChieu', ['Quản lý chuỗi rạp']]);
    // $r->addRoute('GET', '/suat-chieu/chua-xem/{id_rap:\d+}', [Ctrl_DuyetSuatChieu::class, 'docSuatChieuChuaXem', ['Quản lý chuỗi rạp']]);
    $r->addRoute('POST', '/duyet-suat-chieu/{id:\d+}/duyet', [Ctrl_DuyetSuatChieu::class, 'duyetSuatChieu', ['Quản lý chuỗi rạp']]);
    $r->addRoute('POST', '/duyet-suat-chieu/{id:\d+}/tu-choi', [Ctrl_DuyetSuatChieu::class, 'tuChoiSuatChieu', ['Quản lý chuỗi rạp']]);
    $r->addRoute(['POST', 'PUT'], '/gan-ngay', [Ctrl_GanNgay::class, 'ganNgay', ['Quản lý chuỗi rạp']]);
    $r->addRoute('GET', '/gan-ngay/{thang:\d+}-{nam:\d+}', [Ctrl_GanNgay::class, 'doc', ['Quản lý chuỗi rạp', 'Quản lý rạp']]);
    $r->addRoute('POST', '/quy-tac-gia-ve', [Ctrl_GiaVe::class, 'themQuyTac', ['Quản lý chuỗi rạp']]);
    $r->addRoute('GET', '/quy-tac-gia-ve', [Ctrl_GiaVe::class, 'docQuyTac', ['Quản lý chuỗi rạp']]);
    $r->addRoute('PUT', '/quy-tac-gia-ve/{id:\d+}', [Ctrl_GiaVe::class, 'suaQuyTac', ['Quản lý chuỗi rạp']]);
    $r->addRoute('GET', '/danh-muc', [Ctrl_SanPhamAnUong::class, 'docDanhMuc', ['Quản lý rạp']]);
    $r->addRoute('POST', '/danh-muc', [Ctrl_SanPhamAnUong::class, 'themDanhMuc', ['Quản lý rạp']]);
    $r->addRoute('PUT', '/danh-muc/{id:\d+}', [Ctrl_SanPhamAnUong::class, 'suaDanhMuc', ['Quản lý rạp']]);
    $r->addRoute('POST', '/san-pham', [Ctrl_SanPhamAnUong::class, 'themSanPham', ['Quản lý rạp']]);
    $r->addRoute('GET', '/san-pham', [Ctrl_SanPhamAnUong::class, 'docSanPham', ['Quản lý rạp']]);
    $r->addRoute('GET', '/san-pham/{id:\d+}', [Ctrl_SanPhamAnUong::class, 'docSanPham', ['Quản lý rạp']]);
    $r->addRoute(['PUT', 'POST'], '/san-pham/{id:\d+}', [Ctrl_SanPhamAnUong::class, 'suaSanPham', ['Quản lý rạp']]);
    $r->addRoute('GET', '/nhat-ky-suat-chieu', [Ctrl_SuatChieu::class, 'docNhatKy', ['Quản lý rạp', 'Quản lý chuỗi rạp']]);
    $r->addRoute('PUT', '/nhat-ky-suat-chieu/rap-da-xem', [Ctrl_SuatChieu::class, 'quanLyRapXemNhatKy', ['Quản lý rạp']]);
    $r->addRoute('PUT', '/nhat-ky-suat-chieu/chuoi-rap-da-xem', [Ctrl_SuatChieu::class, 'quanLyChuoiXemNhatKy', ['Quản lý chuỗi rạp']]);
    
    // API cho kế hoạch suất chiếu (sử dụng controller riêng)
    $r->addRoute('GET', '/ke-hoach-suat-chieu', [Ctrl_KeHoachSuatChieu::class, 'docKeHoach', ['Quản lý rạp', 'Quản lý chuỗi rạp']]);
    $r->addRoute('POST', '/ke-hoach-suat-chieu', [Ctrl_KeHoachSuatChieu::class, 'luuKeHoach', ['Quản lý rạp']]);
    $r->addRoute('DELETE', '/ke-hoach-suat-chieu/{id:\d+}', [Ctrl_KeHoachSuatChieu::class, 'xoaSuatChieuTrongKeHoach', ['Quản lý rạp']]);
    $r->addRoute('GET', '/ke-hoach-suat-chieu/tao-khung-gio-goi-y', [Ctrl_KeHoachSuatChieu::class, 'taoKhungGioGoiYChoKeHoach', ['Quản lý rạp']]);
    $r->addRoute('GET', '/ke-hoach-suat-chieu/kiem-tra-hop-le', [Ctrl_KeHoachSuatChieu::class, 'kiemTraSuatChieuKeHoachHopLe', ['Quản lý rạp']]);
    
    // API thống kê toàn rạp (cho Admin/Quản lý chuỗi rạp)
    // API duy nhất - lấy dữ liệu thô từ database, JavaScript sẽ xử lý format/tổng hợp ở client side
    $r->addRoute('GET', '/thong-ke-toan-rap/du-lieu-tho', [Ctrl_ThongKe::class, 'layDuLieuThoThongKeToanRap', ['Quản lý chuỗi rạp']]);
    // API thống kê doanh thu theo suất chiếu
    $r->addRoute('GET', '/thong-ke-toan-rap/doanh-thu-theo-suat-chieu', [Ctrl_ThongKe::class, 'thongKeDoanhThuTheoSuatChieu', ['Quản lý chuỗi rạp']]);
    // API thống kê tổng quan toàn rạp
    $r->addRoute('GET', '/thong-ke-toan-rap', [Ctrl_ThongKe::class, 'thongKeToanRap', ['Quản lý chuỗi rạp']]);
    // API xu hướng doanh thu toàn rạp
    $r->addRoute('GET', '/xu-huong-doanh-thu-toan-rap', [Ctrl_ThongKe::class, 'xuHuongDoanhThuToanRap', ['Quản lý chuỗi rạp']]);
    // API hiệu suất theo rạp toàn rạp
    $r->addRoute('GET', '/hieu-suat-theo-rap-toan-rap', [Ctrl_ThongKe::class, 'hieuSuatTheoRapToanRap', ['Quản lý chuỗi rạp']]);
    // API top 10 phim toàn rạp
    $r->addRoute('GET', '/top10-phim-toan-rap', [Ctrl_ThongKe::class, 'top10PhimToanRap', ['Quản lý chuỗi rạp']]);
    // API top 10 sản phẩm bán chạy nhất
    $r->addRoute('GET', '/top10-san-pham-ban-chay-nhat', [Ctrl_ThongKe::class, 'top10SanPhamBanChayNhat', ['Quản lý chuỗi rạp']]);
    
    // API thống kê theo rạp (cho Quản lý rạp)
    $r->addRoute('GET', '/thong-ke-theo-rap/tong-quan', [Ctrl_ThongKe::class, 'thongKeTongQuanTheoRap', ['Quản lý rạp']]);
    $r->addRoute('GET', '/thong-ke-theo-rap/phan-tich-doanh-thu', [Ctrl_ThongKe::class, 'phanTichDoanhThuTheoRap', ['Quản lý rạp']]);
    $r->addRoute('GET', '/thong-ke-theo-rap/top10-phim', [Ctrl_ThongKe::class, 'top10PhimTheoRap', ['Quản lý rạp']]);
    $r->addRoute('GET', '/thong-ke-theo-rap/top10-san-pham', [Ctrl_ThongKe::class, 'top10SanPhamTheoRap', ['Quản lý rạp']]);
    
    // API thống kê cho nhân viên
    $r->addRoute('GET', '/thong-ke-nhan-vien/tong-quan', [Ctrl_ThongKe::class, 'thongKeTongQuanNhanVien', ['Nhân viên']]);
    $r->addRoute('GET', '/thong-ke-nhan-vien/xu-huong-doanh-thu', [Ctrl_ThongKe::class, 'xuHuongDoanhThuNhanVien', ['Nhân viên']]);
    $r->addRoute('GET', '/thong-ke-nhan-vien/top5-phim', [Ctrl_ThongKe::class, 'top5PhimNhanVien', ['Nhân viên']]);
    
    $r->addRoute('POST', '/ke-hoach-suat-chieu/{id:\d+}/duyet', [Ctrl_KeHoachSuatChieu::class, 'duyetKeHoach', ['Quản lý chuỗi rạp']]);
    $r->addRoute('POST', '/ke-hoach-suat-chieu/{id:\d+}/tu-choi', [Ctrl_KeHoachSuatChieu::class, 'tuChoiKeHoach', ['Quản lý chuỗi rạp']]);
    $r->addRoute('POST', '/ke-hoach-suat-chieu/{id:\d+}/hoan-tac', [Ctrl_KeHoachSuatChieu::class, 'hoanTacKeHoach', ['Quản lý chuỗi rạp']]);
    $r->addRoute('POST', '/ke-hoach-suat-chieu/duyet-tuan', [Ctrl_KeHoachSuatChieu::class, 'duyetTuan', ['Quản lý chuỗi rạp']]);
    
    $r->addRoute('GET', '/vi-tri-cong-viec', [Ctrl_PhanCong::class, 'docViTri', ['Quản lý rạp']]);
    $r->addRoute('POST', '/vi-tri-cong-viec', [Ctrl_PhanCong::class, 'themViTri', ['Quản lý rạp']]);
    $r->addRoute('PUT', '/vi-tri-cong-viec/{id:\d+}', [Ctrl_PhanCong::class, 'suaViTri', ['Quản lý rạp']]);
    $r->addRoute('GET', '/doc-yeu-cau-da-gui', [Ctrl_PhanCong::class, 'docYCDaGui', ['Quản lý rạp']]);
    $r->addRoute('PUT', '/duyet-yeu-cau-nghi/{id:\d+}', [Ctrl_PhanCong::class, 'sua1PhanCong', ['Quản lý rạp']]);
    $r->addRoute('POST', '/phan-cong', [Ctrl_PhanCong::class, 'phanCong1NhanVien', ['Quản lý rạp']]);
    $r->addRoute('DELETE', '/phan-cong/{id:\d+}', [Ctrl_PhanCong::class, 'xoa1PhanCong', ['Quản lý rạp']]);
    $r->addRoute('GET', '/phan-cong', [Ctrl_PhanCong::class, 'docPhanCong', ['Quản lý rạp']]);
    $r->addRoute('GET', '/phan-cong-theo-nv', [Ctrl_PhanCong::class, 'docPhanCongTheoNV', ['Nhân viên']]);
    $r->addRoute('GET', '/lich-lam-viec', [Ctrl_PhanCong::class, 'docLichLamViec', ['Nhân viên']]);
    $r->addRoute('GET', '/doc-cham-cong', [Ctrl_PhanCong::class, 'docChamCong', ['Nhân viên']]);
    $r->addRoute('GET', '/doc-cham-cong-toan-rap', [Ctrl_PhanCong::class, 'docChamCongToanRap', ['Quản lý rạp']]);
    $r->addRoute('GET', '/yeu-cau-lich', [Ctrl_PhanCong::class, 'docGuiYCLich', ['Nhân viên']]);
    $r->addRoute('GET', '/yeu-cau-bai-viet', [Ctrl_TinTuc::class, 'docGuiYCBaiViet', ['Nhân viên']]);
    $r->addRoute('POST', '/them-tin-tuc', [Ctrl_TinTuc::class, 'themTinTuc', ['Nhân viên', 'Quản lý rạp']]);
    $r->addRoute('POST', '/sua-tin-tuc/{id}', [Ctrl_TinTuc::class, 'suaTinTuc', ['Nhân viên', 'Quản lý rạp']]);
    $r->addRoute('GET', '/doc-tin-tuc-da-gui', [Ctrl_TinTuc::class, 'docTinTucDaGui', ['Quản lý rạp']]);
    $r->addRoute('GET', '/doc-tin-tuc-theo-rap', [Ctrl_TinTuc::class, 'docTinTucTheoRap', ['Quản lý rạp']]);
    $r->addRoute('GET', '/chi-tiet-tin-tuc/{id}', [Ctrl_TinTuc::class, 'chiTietTinTuc', ['Nhân viên', 'Quản lý rạp']]);
    $r->addRoute('PUT', '/gui-yeu-cau-nghi/{id:\d+}', [Ctrl_PhanCong::class, 'sua1PhanCong', ['Nhân viên', 'Quản lý rạp']]);
    $r->addRoute('POST', '/tao-thuong', [Ctrl_LuongThuong::class, 'taoThuong', ['Quản lý rạp']]);
    $r->addRoute('GET', '/lay-thuong/{id:\d+}', [Ctrl_LuongThuong::class, 'layThuong', ['Quản lý rạp']]);
    $r->addRoute('GET', '/lay-thuong-nhan-vien', [Ctrl_LuongThuong::class, 'layThuong1NhanVien', ['Nhân viên']]);
    $r->addRoute('PUT', '/duyet-luong-thuong', [Ctrl_LuongThuong::class, 'duyetLuongThuong', ['Quản lý rạp']]);
    $r->addRoute('PUT', '/duyet-tin-tuc/{id:\d+}', [Ctrl_TinTuc::class, 'duyetTinTuc', ['Quản lý rạp']]);
    $r->addRoute('GET', '/doc-khach-hang', [Ctrl_KhachHang::class, 'docKhachHang', ['Nhân viên']]);
    $r->addRoute('GET', '/doc-don-hang-theo-rap/{id:\d+}', [Ctrl_DonHang::class, 'docDonHangTheoRap', ['Nhân viên']]);
    $r->addRoute('GET', '/doc-giao-dich/{id:\d+}', [Ctrl_DonHang::class, 'docDonHangKH', ['Nhân viên']]);
    $r->addRoute('PUT', '/trang-thai-khach-hang/{id:\d+}', [Ctrl_KhachHang::class, 'suaTrangThai', ['Nhân viên']]);
    $r->addRoute('POST', '/banner', [Ctrl_Banner::class, 'them', ['Admin']]);
    $r->addRoute('GET', '/banner', [Ctrl_Banner::class, 'docTatCa', ['Admin']]);
    $r->addRoute('GET', '/banner/side-show', [Ctrl_Banner::class, 'docSideShow']);
    $r->addRoute('POST', '/banner/{id:\d+}', [Ctrl_Banner::class, 'suaAnh', ['Admin']]);
    $r->addRoute('PUT', '/banner/{id:\d+}/trang-thai', [Ctrl_Banner::class, 'thayDoiTrangThai', ['Admin']]);
    $r->addRoute('DELETE', '/banner/{id:\d+}', [Ctrl_Banner::class, 'xoa', ['Admin']]);
    $r->addRoute('PUT', '/banner/cap-nhat-side-show', [Ctrl_Banner::class, 'sapXep', ['Admin']]);
    $r->addRoute('GET', '/danh-sach-phien-chat', [Ctrl_TuVan::class, 'nhanVienLayDanhSachPhienChatPhanTrang', ['Nhân viên']]);
    // API thống kê theo rạp (cho Quản lý rạp)
    // API duy nhất - lấy dữ liệu thô từ database, JavaScript sẽ xử lý format/tổng hợp ở client side
    $r->addRoute('GET', '/thong-ke/du-lieu-tho', [Ctrl_ThongKe::class, 'layDuLieuThoThongKeTheoRap', ['Quản lý rạp']]);
    // API thống kê doanh thu theo suất chiếu (cho Quản lý rạp)
    $r->addRoute('GET', '/thong-ke/doanh-thu-theo-suat-chieu', [Ctrl_ThongKe::class, 'thongKeDoanhThuTheoSuatChieuTheoRap', ['Quản lý rạp']]);
    // Khách hàng
    $r->addRoute('POST', '/dang-ky', [Ctrl_XacThucCustomer::class, 'dangKy']);
    $r->addRoute('GET', '/google', [Ctrl_XacThucCustomer::class, 'googleLogin']);
    $r->addRoute('GET', '/google-callback', [Ctrl_XacThucCustomer::class, 'googleCallback']);
    $r->addRoute('POST', '/dang-nhap-khach-hang', [Ctrl_XacThucCustomer::class, 'dangNhap']);
    $r->addRoute('GET', '/check-login', [Ctrl_XacThucCustomer::class, 'checkLogin']);
    $r->addRoute('GET', '/thong-tin-ca-nhan', [Ctrl_KhachHang::class, 'thongTinKhachHang']);
    $r->addRoute('PUT', '/thong-tin-ca-nhan', [Ctrl_KhachHang::class, 'updateThongTinKhachHang']);
    $r->addRoute('PUT', '/doi-mat-khau', [Ctrl_XacThucCustomer::class, 'xuLyDoiMatKhau']);
    $r->addRoute('GET', '/rap-phim-khach', [Ctrl_RapPhim::class, 'docRapPhim']);
    $r->addRoute('GET', '/rap/{id}', [Ctrl_RapPhim::class, 'docRapPhimTheoID']);
    $r->addRoute('POST', '/check-email', [Ctrl_XacThucCustomer::class, 'checkEmail']);
    $r->addRoute('POST', '/reset-password', [Ctrl_XacThucCustomer::class, 'sendResetPassword']);
    $r->addRoute('POST', '/reset-pass', [Ctrl_XacThucCustomer::class, 'ResetPass']);
    $r->addRoute('GET', '/loai-phim', [Ctrl_Phim::class, 'docTheLoaiPhim']);
    $r->addRoute('GET', '/phim', [Ctrl_Phim::class, 'docPhimKH']);
    $r->addRoute('GET', '/phim-online', [Ctrl_Phim::class, 'docPhimKHOnline']);
    $r->addRoute('GET', '/dat-ve/{id}', [Ctrl_Phim::class, 'docChiTietPhim']);
    $r->addRoute('GET', '/phim-moi', [Ctrl_Phim::class, 'docPhimMoiNhat']);
    $r->addRoute('GET', '/suat-chieu-khach', [Ctrl_SuatChieu::class, 'docSuatChieuKH']);
    $r->addRoute('GET', '/so-do-ghe/{id}', [Ctrl_PhongChieu::class, 'chiTietPhongChieu']);
    $r->addRoute('GET','/tinh-gia-ve/{loaiGheId}/{ngay}/{dinhDangPhim}',[Ctrl_GiaVe::class, 'docGiaVe']);
    $r->addRoute('GET', '/phim-theo-rap/{idRap:\d+}', [Ctrl_SuatChieu::class, 'docPhimTheoRapKH']);
    $r->addRoute('POST', '/tao-ve', [Ctrl_Ve::class, 'themVe']);
    $r->addRoute('POST', '/tao-don-hang', [Ctrl_DonHang::class, 'themDonHang']);
    $r->addRoute('POST', '/tao-don-hang-nv', [Ctrl_DonHang::class, 'themDonHangNV']);
    $r->addRoute('POST', '/luu-giao-dich', [Ctrl_GiaoDich::class, 'handleWebhook']);
    $r->addRoute('POST', '/lay-trang-thai', [Ctrl_GiaoDich::class, 'checkTrangThai']);
    $r->addRoute('POST', '/gui-don-hang', [Ctrl_DonHang::class, 'guiDonHang']);
    $r->addRoute('GET', '/lay-san-pham-khach/{id}', [Ctrl_SanPhamAnUong::class, 'docSanPhamTheoRap']);
    $r->addRoute('POST', '/tao-chi-tiet-don-hang', [Ctrl_ChiTietDonHang::class, 'themChiTietDonHang']);
    $r->addRoute('GET', '/doc-don-hang', [Ctrl_DonHang::class, 'docDonHang']);
    $r->addRoute('GET', '/doc-don-hang-online', [Ctrl_DonHang::class, 'docDonHangOnline']);
    $r->addRoute('GET', '/doc-chi-tiet-don-hang/{id}', [Ctrl_ChiTietDonHang::class, 'docChiTietDonHang']);
    $r->addRoute('GET', '/doc-the-qua-tang', [Ctrl_TheQuaTang::class, 'docTheQuaTang']);
    $r->addRoute('PUT', '/sua-gia-tri-the', [Ctrl_TheQuaTang::class, 'suaGiaTriThe']);
    $r->addRoute('POST', '/tao-the-qua-tang', [Ctrl_TheQuaTang::class, 'taoTheQuaTang']);
    $r->addRoute('PUT', '/cap-nhat-trang-thai-don-hang', [Ctrl_DonHang::class, 'capNhatTrangThaiDonHang']);
    $r->addRoute('PUT', '/cap-nhat-trang-thai-ve', [Ctrl_Ve::class, 'capNhatTrangThai']);
    $r->addRoute('POST', '/them-danh-gia', [Ctrl_DanhGia::class, 'themDanhGia']);
    $r->addRoute('PUT', '/sua-danh-gia/{id}', [Ctrl_DanhGia::class, 'suaDanhGia']);
    $r->addRoute('DELETE', '/xoa-danh-gia/{id}', [Ctrl_DanhGia::class, 'xoaDanhGia']);
    $r->addRoute('GET', '/doc-danh-gia/{id}', [Ctrl_DanhGia::class, 'docDanhGia']);
    $r->addRoute('GET', '/phim-dien-anh', [Ctrl_Phim::class, 'docPhim']);
    $r->addRoute('GET', '/doc-phim-ban-chay', [Ctrl_Ve::class, 'doctop4PhimTheoVe']);
    $r->addRoute('GET', '/lay-trang-thai-mua-phim', [Ctrl_MuaPhim::class, 'docTrangThaiMuaPhim']);
    $r->addRoute('POST', '/them-mua-phim', [Ctrl_MuaPhim::class, 'themMuaPhim']);
    $r->addRoute('GET', '/lich-su-chat', [Ctrl_ChatBotAI::class, 'getMessages']);
    $r->addRoute('POST', '/gui-tin-nhan-chatbot', [Ctrl_ChatBotAI::class, 'addMessage']);
    $r->addRoute('POST', '/chatbot-ai/tra-loi', [Ctrl_ChatBotAI::class, 'getAIAnswer']);
    $r->addRoute('GET', '/doc-tin-tuc', [Ctrl_TinTuc::class, 'docTinTuc']);
    $r->addRoute('GET', '/doc-chi-tiet-tin-tuc/{id}', [Ctrl_TinTuc::class, 'docChiTiet']);

    // Tư vấn
    $r->addRoute('POST', '/tao-phien-chat', [Ctrl_TuVan::class, 'khachHangTaoPhienChat']);
    $r->addRoute('GET', '/danh-sach-phien-chat-khach-hang', [Ctrl_TuVan::class, 'khachHangLayDanhSachPhienChat']);
    $r->addRoute('GET', '/chi-tiet-phien-chat/{id:\d+}', [Ctrl_TuVan::class, 'layChiTietPhienChat']);
    
    // Routes cho gọi video
    $r->addRoute('POST', '/goi-video/dat-lich', [Ctrl_GoiVideo::class, 'khachHangDatLichGoiVideo']);
    $r->addRoute('GET', '/goi-video/danh-sach-lich', [Ctrl_GoiVideo::class, 'nhanVienLayDanhSachLichCho', ['Nhân viên', 'Quản lý rạp']]);
    $r->addRoute('POST', '/goi-video/{id:\d+}/chon-tu-van', [Ctrl_GoiVideo::class, 'nhanVienChonTuVan', ['Nhân viên', 'Quản lý rạp']]);
    $r->addRoute('GET', '/goi-video/{id:\d+}/trang-thai', [Ctrl_GoiVideo::class, 'khachHangKiemTraTrangThai']);
    $r->addRoute('POST', '/goi-video/{id:\d+}/huy', [Ctrl_GoiVideo::class, 'nhanVienHuyTuVan', ['Nhân viên', 'Quản lý rạp']]);
    $r->addRoute('POST', '/goi-video/bat-dau', [Ctrl_GoiVideo::class, 'batDauCuocGoi']);
    $r->addRoute('POST', '/goi-video/ket-thuc', [Ctrl_GoiVideo::class, 'ketThucCuocGoi']);
    $r->addRoute('GET', '/lich-goi-video-theo-ngay', [Ctrl_GoiVideo::class, 'khachHangLayLichTheoNgay']);
    $r->addRoute('POST', '/dat-lich-goi-video', [Ctrl_GoiVideo::class, 'datLichGoiVideo']);
    
    // API Chấm công bằng khuôn mặt (cho Nhân viên)
    $r->addRoute('POST', '/cham-cong/dang-ky-khuon-mat', [Ctrl_ChamCong::class, 'xuLyDangKyKhuonMat', ['Nhân viên']]);
    $r->addRoute('POST', '/cham-cong/cham-cong', [Ctrl_ChamCong::class, 'chamCongKhuonMat', ['Nhân viên']]);
    $r->addRoute('GET', '/cham-cong/lich-su', [Ctrl_ChamCong::class, 'lichSuChamCong', ['Nhân viên']]);
    $r->addRoute('GET', '/cham-cong/kiem-tra-dang-ky', [Ctrl_ChamCong::class, 'kiemTraDangKy', ['Nhân viên']]);
    // API Quản lý thông tin định vị (cho Quản lý rạp)
    $r->addRoute('POST', '/thong-tin-dinh-vi', [Ctrl_DinhVi::class, 'updateDinhVi', ['Quản lý rạp']]);
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
        header('Content-Type: application/json', true, 404);
        echo json_encode([
            'success' => false,
            'message' => '404 Not Found',
        ]);
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        header('Content-Type: application/json', true, 405);
        echo json_encode([
            'success' => false,
            'message' => '405 Method Not Allowed'
        ]);
        break;
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];
        header("Content-Type: application/json");
        if (is_array($handler)) {
            if(count($handler) == 3){
                $headers = getallheaders();

                // Kiểm tra xác thực người dùng nội bộ
                if (!isset($_SESSION['UserInternal']) && !isset($headers['Token-Dev'])) {
                    header('Content-Type: application/json', true, 403);
                    echo json_encode([
                        'success' => false,
                        'message' => '403 Bạn chưa đăng nhập để truy cập api này'
                    ]);
                    exit();
                }
                if (isset($headers['Token-Dev']) && !hash_equals($headers['Token-Dev'], $_ENV['TOKEN_DEV_KEY'])) {
                    header('Content-Type: application/json', true, 401);
                    echo json_encode([
                        'success' => false,
                        'message' => '401 Token-Dev không hợp lệ'
                    ]);
                    exit();
                }
                $requiredRoles = $handler[2]; // Lấy vai trò yêu cầu từ định tuyến
                if(isset($_SESSION['UserInternal']) && !in_array($_SESSION['UserInternal']['VaiTro'], $requiredRoles)) {
                    header('Content-Type: application/json', true, 403);
                    echo json_encode([
                        'success' => false,
                        'message' => '403 Bạn không có quyền truy cập api này'
                    ]);
                    exit();

                }
                // Kiểm tra xác thực khách hàng
                // bổ sung logic sau
            }
            // Sửa lại: lấy class và method từ array $handler
            $class = $handler[0];  // Ctrl_XacThuc::class
            $method = $handler[1]; // 'indexDangNhap' hoặc 'index'
            $controller = new $class();
            $result = call_user_func([$controller, $method], $vars);
            
            // Đảm bảo result là array hoặc object
            if ($result === null || $result === false) {
                $result = ['success' => false, 'error' => 'No response from controller'];
            }
            
            // Làm sạch dữ liệu trước khi encode để tránh lỗi UTF-8
            $result = cleanArrayForJson($result);
            
            $json = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE);
            if ($json === false) {
                // Nếu json_encode fail, trả về error
                $json = json_encode([
                    'success' => false,
                    'error' => 'JSON encoding error: ' . json_last_error_msg()
                ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE);
            }
            echo $json;
        } else {
            $result = call_user_func($handler, $vars);
            
            // Đảm bảo result là array hoặc object
            if ($result === null || $result === false) {
                $result = ['success' => false, 'error' => 'No response from handler'];
            }
            
            // Làm sạch dữ liệu trước khi encode để tránh lỗi UTF-8
            $result = cleanArrayForJson($result);
            
            $json = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE);
            if ($json === false) {
                // Nếu json_encode fail, trả về error
                $json = json_encode([
                    'success' => false,
                    'error' => 'JSON encoding error: ' . json_last_error_msg()
                ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE);
            }
            echo $json;
        }
        break;
}

?>
