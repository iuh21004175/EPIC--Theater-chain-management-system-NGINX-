# TÀI LIỆU CÔNG NGHỆ - HỆ THỐNG CHẤM CÔNG KHUÔN MẶT & XÁC THỰC MẠNG NỘI BỘ

## MỤC LỤC

1.  [Tổng quan hệ thống](https://www.google.com/search?q=%231-t%E1%BB%95ng-quan-h%E1%BB%87-th%E1%BB%91ng)
2.  [Cơ chế bảo mật kép (WiFi + GPS)](https://www.google.com/search?q=%232-c%C6%A1-ch%E1%BA%BF-b%E1%BA%A3o-m%E1%BA%ADt-k%C3%A9p)
3.  [Luồng xử lý chấm công](https://www.google.com/search?q=%233-lu%E1%BB%93ng-x%E1%BB%AD-l%C3%BD-ch%E1%BA%A5m-c%C3%B4ng)
4.  [Chi tiết kỹ thuật](https://www.google.com/search?q=%234-chi-ti%E1%BA%BFt-k%E1%BB%B9-thu%E1%BA%ADt)
5.  [Các kịch bản chống gian lận](https://www.google.com/search?q=%235-c%C3%A1c-k%E1%BB%8Bch-b%E1%BA%A3n-ch%E1%BB%91ng-gian-l%E1%BA%ADn)

-----

## 1\. TỔNG QUAN HỆ THỐNG

### 1.1. Mô tả chức năng

Hệ thống chấm công là giải pháp xác thực danh tính kết hợp với **chứng thực hạ tầng mạng**. Hệ thống yêu cầu nhân viên phải có mặt thực tế tại rạp và kết nối vào hệ thống mạng nội bộ (LAN) của rạp để thực hiện chấm công.

Cốt lõi của giải pháp bao gồm:

1.  **Xác thực hạ tầng mạng (Primary Check)**: Nhân viên phải kết nối đúng WiFi của rạp để có thể giao tiếp với **Máy chủ định vị cục bộ (Local Anchor Device)** qua mạng LAN.
2.  **Nhận diện khuôn mặt (Identity Check)**: Sử dụng AI để đảm bảo chính chủ.
3.  **Xác thực vị trí (Secondary Check)**: Sử dụng tọa độ GPS được ký số (JWT) từ máy chủ cục bộ để chống giả mạo điểm phát sóng WiFi.

### 1.2. Quy trình tóm tắt

Nhân viên mở web chấm công -\> Hệ thống tự động kết nối tới IP Local của Rạp (ví dụ: `192.168.1.200:2552`) -\> Nếu kết nối thành công, lấy Token chứa tọa độ -\> Gửi Token + Video khuôn mặt lên Server -\> Server xác thực.

-----

## 2\. CƠ CHẾ BẢO MẬT KÉP (WIFI + GPS)

Đây là điểm khác biệt quan trọng của hệ thống. Chúng tôi không sử dụng GPS của điện thoại nhân viên (vốn dễ bị fake), mà sử dụng **GPS của thiết bị cố định tại rạp** để xác thực mạng WiFi.

### Tại sao cần kết hợp cả WiFi và GPS?

Nếu chỉ xác thực tên WiFi (SSID) và IP Local, hệ thống sẽ gặp các rủi ro bảo mật sau. Cơ chế GPS trong JWT sinh ra để giải quyết triệt để các trường hợp này:

| Rủi ro gian lận | Mô tả kịch bản tấn công | Cơ chế ngăn chặn (Vai trò của GPS/JWT) |
| :--- | :--- | :--- |
| **1. Trùng lặp hạ tầng** | 2 rạp phim khác nhau (Rạp A và Rạp B) vô tình đặt trùng tên WiFi (VD: `Cinema_Staff`) và trùng dải IP Local (`192.168.1.100`). Nhân viên ở Rạp A có thể chấm công cho Rạp B. | **Kiểm tra tọa độ**: Token từ thiết bị tại Rạp A sẽ chứa tọa độ A. Khi gửi lên chấm công cho Rạp B, hệ thống thấy tọa độ không khớp với Rạp B -\> **Chặn**. |
| **2. Giả mạo điểm phát sóng (Evil Twin)** | Nhân viên dùng điện thoại phát 4G (Hotspot) đặt tên WiFi là `Cinema_Staff` và thiết lập IP tĩnh để giả lập mạng rạp. | **Không thể lấy Token**: Dù nhân viên tạo được mạng WiFi giả, họ **không có "Thiết bị định vị cố định"** đang chạy port 2552 trong mạng giả đó. Web chấm công sẽ không thể kết nối tới `192.168.x.x:2552` để lấy token -\> **Chặn**. |
| **3. Giả lập Server Local** | Nhân viên cao tay tự dựng một server ảo trên laptop cá nhân, mở port 2552 để giả làm thiết bị rạp. | **Không có Secret Key**: Token GPS được ký bằng `HMAC-SHA256` với khóa bí mật (`GPS_SECRET_KEY`) chỉ thiết bị thật của rạp mới có. Server giả không thể tạo ra token hợp lệ -\> **Chặn**. |

-----

## 3\. LUỒNG XỬ LÝ CHẤM CÔNG

### 3.1. Sơ đồ luồng dữ liệu

```mermaid
sequenceDiagram
    actor NV as Nhân viên (Client)
    participant LAN as Local Network (WiFi Rạp)
    participant DEVICE as Thiết bị Định vị Cố định (Port 2552)
    participant SRV as Server Backend (Internet)
    participant DB as Database

    Note over NV, DEVICE: GIAI ĐOẠN 1: CHỨNG THỰC MẠNG (LAN)
    
    NV->>NV: Kết nối WiFi "Cinema_Staff"
    NV->>LAN: Request GET http://192.168.1.xxx:2552
    
    alt Không kết nối được (Sai WiFi / 4G)
        LAN--xNV: Connection Refused / Timeout
        NV->>NV: Báo lỗi: "Vui lòng kết nối WiFi rạp"
    else Kết nối thành công
        LAN->>DEVICE: Forward request
        DEVICE->>DEVICE: Tạo JWT Token (chứa GPS Rạp + Time)
        DEVICE->>DEVICE: Ký Token bằng SecretKey
        DEVICE-->>NV: Trả về {token: "eyJ..."}
    end

    Note over NV, SRV: GIAI ĐOẠN 2: XÁC THỰC SERVER & AI

    NV->>NV: Quay video khuôn mặt
    NV->>SRV: POST (Video + Token)
    
    SRV->>SRV: Validate Token Signature (Chống giả server local)
    SRV->>SRV: Validate Token Expiration (Chống replay)
    SRV->>SRV: Decode GPS từ Token
    
    SRV->>DB: Lấy tọa độ gốc của Rạp cần chấm công
    SRV->>SRV: So sánh GPS Token vs GPS DB (Haversine)
    
    alt Khoảng cách > 100m
        SRV--xNV: Lỗi: "Dữ liệu định vị không khớp rạp này"
    else Khoảng cách OK
        SRV->>SRV: Xử lý AI nhận diện khuôn mặt
        SRV-->>NV: Thành công
    end
```

-----

## 4\. CHI TIẾT KỸ THUẬT

### 4.1. Thiết bị định vị cố định (Anchor Device)

Đây là thành phần quan trọng nhất để chứng minh nhân viên đang ở tại rạp.

  * **Vai trò**: Là một máy tính nhỏ/điện thoại/IoT device nằm cố định tại rạp, luôn chạy 24/7.
  * **Mạng**: Có địa chỉ IP Tĩnh trong mạng LAN (Ví dụ: `192.168.1.200`).
  * **Phần mềm**: Chạy một HTTP Server lắng nghe ở cổng **2552**.
  * **Bảo mật**: Chứa `GPS_SECRET_KEY` được hardcode hoặc cấu hình bảo mật.

### 4.2. Giao thức xác thực LAN

Khi nhân viên truy cập trang web chấm công, Browser sẽ thực hiện lệnh `fetch` tới IP nội bộ:

```javascript
// Frontend Code Logic
async function getProofOfPresence() {
    // IP này được cấu hình theo từng rạp trong Database
    const localDeviceIp = document.getElementById('camera-section').dataset.ip; 
    
    try {
        // Cố gắng giao tiếp với thiết bị trong mạng LAN
        // Nếu dùng 4G hoặc WiFi nhà, request này sẽ chết (Timeout/Unreachable)
        const response = await fetch(`http://${localDeviceIp}:2552/get-token`, {
            timeout: 5000 // Timeout ngắn 5s
        });
        
        return await response.json(); // Trả về JWT
    } catch (e) {
        throw new Error("Không tìm thấy thiết bị chấm công. Vui lòng kiểm tra kết nối WiFi Rạp.");
    }
}
```

### 4.3. Cấu trúc JWT Token

Token này chứng minh: "Tôi đã nói chuyện được với thiết bị xịn của rạp lúc [Timestamp] tại tọa độ [GPS]".

```json
{
  "alg": "HS256",
  "typ": "JWT"
}
.
{
  "device_id": "RAP_A_DEVICE_01",
  "lat": 10.762622,   // Tọa độ CỨNG của thiết bị tại rạp
  "lng": 106.660172,
  "timestamp": 1705123456, // Thời gian tạo token
  "exp": 1705123486   // Hết hạn sau 30 giây (Chống dùng lại)
}
.
[SIGNATURE] // Ký bằng GPS_SECRET_KEY
```

-----

## 5\. HƯỚNG DẪN CẤU HÌNH CHO KỸ THUẬT VIÊN

Để hệ thống hoạt động chính xác và tránh xung đột, kỹ thuật viên cần tuân thủ:

### 5.1. Cấu hình Thiết bị định vị (Anchor Device)

1.  **IP Tĩnh**: Bắt buộc set IP tĩnh cho thiết bị (VD: `192.168.1.250`) để tránh DHCP đổi IP làm web không gọi được.
2.  **Cổng**: Đảm bảo Firewall của mạng Wifi Rạp cho phép giao tiếp nội bộ qua port `2552`.

### 5.2. Cấu hình trên CMS (Web Quản trị)

Khi khai báo một Rạp mới, cần điền chính xác:

1.  **IP Local**: IP của thiết bị định vị (để Frontend gọi).
2.  **Tên WiFi**: Để hiển thị hướng dẫn cho nhân viên (VD: "Vui lòng kết nối wifi: Cinema\_Guest").
3.  **Tọa độ (Lat/Long)**: Tọa độ thực tế của rạp (Dùng để đối chiếu với tọa độ trong Token gửi lên).

### 5.3. Xử lý lỗi thường gặp (Troubleshooting)

**Lỗi: "Không thể kết nối tới Server GPS"**

  * *Nguyên nhân 1*: Nhân viên đang dùng 4G hoặc WiFi quán cafe bên cạnh. -\> **Yêu cầu kết nối đúng WiFi rạp**.
  * *Nguyên nhân 2*: Thiết bị định vị tại rạp bị tắt nguồn hoặc mất kết nối mạng.
  * *Nguyên nhân 3*: Nhân viên dùng iPhone bật tính năng "Private Wi-Fi Address" hoặc VPN chặn truy cập LAN.

**Lỗi: "Dữ liệu vị trí không hợp lệ (Khoảng cách xa)"**

  * *Nguyên nhân*: Có 2 rạp (Rạp A và Rạp B) dùng chung dải mạng `192.168.1.x` và nhân viên Rạp A đang cố chấm công vào ca của Rạp B. Web Rạp B gọi nhầm vào thiết bị Rạp A (do trùng IP). Server phát hiện tọa độ thiết bị A không khớp với Rạp B -\> Chặn.

-----

*Tài liệu này nhấn mạnh tính chất "Proof of Presence" (Chứng minh sự hiện diện) thông qua khả năng truy cập mạng nội bộ, coi đó là lớp bảo vệ tiên quyết trước khi thực hiện nhận diện khuôn mặt.*