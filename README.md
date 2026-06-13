# 🎬 Hệ Thống Quản Lý Chuỗi Rạp Chiếu Phim EPIC

## 📖 Giới thiệu

EPIC Theater Chain Management System là dự án xây dựng hệ thống quản lý chuỗi rạp chiếu phim trên nền tảng web. Hệ thống hỗ trợ quản lý phim, lịch chiếu, phòng chiếu, ghế ngồi, đặt vé và các hoạt động quản trị khác của rạp chiếu phim.

Dự án được phát triển nhằm áp dụng các kiến thức về phát triển web, thiết kế cơ sở dữ liệu và quản lý hệ thống thông tin vào một bài toán thực tế.

---

## 🎯 Mục tiêu dự án

* Xây dựng hệ thống quản lý rạp chiếu phim hoàn chỉnh.
* Áp dụng mô hình MVC trong phát triển ứng dụng web.
* Thiết kế cơ sở dữ liệu phục vụ quản lý và đặt vé.
* Tối ưu khả năng quản lý và vận hành rạp chiếu phim.

---

## ✨ Chức năng chính

### 👤 Khách hàng

* Đăng ký tài khoản
* Đăng nhập / Đăng xuất
* Xem danh sách phim
* Tìm kiếm phim
* Xem thông tin chi tiết phim
* Xem lịch chiếu
* Chọn ghế ngồi
* Đặt vé trực tuyến
* Xem lịch sử đặt vé

### 👨‍💼 Quản trị viên

* Quản lý phim (Thêm / Sửa / Xóa)
* Quản lý thể loại phim
* Quản lý rạp chiếu
* Quản lý phòng chiếu
* Quản lý ghế ngồi
* Quản lý lịch chiếu
* Quản lý người dùng
* Quản lý đơn đặt vé
* Thống kê doanh thu

---

## 🛠️ Công nghệ sử dụng

### Frontend

* HTML5
* CSS3
* JavaScript
* Bootstrap

### Backend

* PHP
* NGINX

### Database

* MySQL

### Công cụ hỗ trợ

* Git
* GitHub
* phpMyAdmin

---

## 🏗️ Kiến trúc hệ thống

Hệ thống được xây dựng theo mô hình:

```text
Presentation Layer (Giao diện)
        ↓
Business Logic Layer (Xử lý nghiệp vụ)
        ↓
Data Access Layer (Cơ sở dữ liệu)
```

---

## 📂 Cấu trúc thư mục

```text
EPIC-Theater-chain-management-system/
│
├── assets/
├── config/
├── controllers/
├── models/
├── views/
├── database/
├── public/
├── uploads/
└── README.md
```

---

## ⚙️ Hướng dẫn cài đặt

### 1. Clone dự án

```bash
git clone https://github.com/iuh21004175/EPIC--Theater-chain-management-system-NGINX-.git
```

### 2. Tạo cơ sở dữ liệu

Tạo database MySQL mới.

Ví dụ:

```sql
CREATE DATABASE epic_theater;
```

### 3. Import dữ liệu

Import file SQL có sẵn trong thư mục `database`.

### 4. Cấu hình kết nối Database

Cập nhật thông tin kết nối trong file cấu hình:

```php
$db_host = "localhost";
$db_name = "epic_theater";
$db_user = "root";
$db_pass = "";
```

### 5. Cấu hình NGINX

Ví dụ:

```nginx
server {
    listen 80;
    server_name localhost;

    root /path/to/project/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

### 6. Khởi động hệ thống

Truy cập:

```text
http://localhost
```

---

## 🗄️ Thiết kế cơ sở dữ liệu

Các bảng chính:

* Users
* Movies
* Genres
* Theaters
* Rooms
* Seats
* Showtimes
* Bookings
* Tickets

---

## 📸 Hình ảnh hệ thống

### Trang đăng nhập

```markdown
![Login](screenshots/login.png)
```

### Trang quản trị

```markdown
![Dashboard](screenshots/dashboard.png)
```

### Quản lý phim

```markdown
![Movies](screenshots/movies.png)
```

### Đặt vé

```markdown
![Booking](screenshots/booking.png)
```

---

## 🚀 Hướng phát triển

* Thanh toán trực tuyến (VNPay, MoMo)
* Quét mã QR cho vé điện tử
* Gửi email xác nhận đặt vé
* Giao diện Responsive cho thiết bị di động
* Hệ thống đánh giá phim
* Quản lý nhiều chi nhánh rạp chiếu phim

---

## 📚 Kiến thức đạt được

Thông qua dự án này, tôi đã tích lũy được kinh nghiệm về:

* Lập trình PHP
* Thiết kế cơ sở dữ liệu MySQL
* Mô hình MVC
* Quản lý mã nguồn bằng Git
* Cấu hình NGINX
* Phân tích và thiết kế hệ thống thông tin
* Phát triển ứng dụng web thực tế

---

## 👨‍💻 Tác giả

**Nguyễn Trọng Nam**
Sinh viên ngành Hệ thống Thông tin

**Nguyễn Tuấn Dũng**
Sinh viên ngành Hệ thống Thông tin

GitHub:
https://github.com/iuh21004175

---

## 📄 Giấy phép

Dự án được phát triển phục vụ mục đích học tập và nghiên cứu.
