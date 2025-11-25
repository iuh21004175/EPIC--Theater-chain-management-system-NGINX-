# TÀI LIỆU CÔNG NGHỆ - HỆ THỐNG CHẤM CÔNG BẰNG KHUÔN MẶT

## MỤC LỤC

1. [Tổng quan hệ thống](#1-tổng-quan-hệ-thống)
   - [Mô tả chức năng](#11-mô-tả-chức-năng)
   - [Các tính năng chính](#12-các-tính-năng-chính)
   - [Ưu điểm và Nhược điểm](#13-ưu-điểm-và-nhược-điểm-của-giải-pháp)
2. [Kiến trúc hệ thống](#2-kiến-trúc-hệ-thống)
3. [Công nghệ sử dụng](#3-công-nghệ-sử-dụng)
4. [Luồng xử lý chấm công](#4-luồng-xử-lý-chấm-công)
5. [Chi tiết các thành phần](#5-chi-tiết-các-thành-phần)
6. [API Endpoints](#6-api-endpoints)
7. [Cơ sở dữ liệu](#7-cơ-sở-dữ-liệu)
8. [Bảo mật](#8-bảo-mật)
9. [Xử lý lỗi và Logging](#9-xử-lý-lỗi-và-logging)
10. [Hướng dẫn triển khai](#10-hướng-dẫn-triển-khai)
11. [Troubleshooting](#11-troubleshooting)

---

## 1. TỔNG QUAN HỆ THỐNG

### 1.1. Mô tả chức năng

Hệ thống chấm công bằng khuôn mặt là một giải pháp công nghệ cao cho phép nhân viên chấm công vào/ra bằng cách sử dụng nhận diện khuôn mặt kết hợp với xác thực vị trí GPS. Hệ thống đảm bảo tính chính xác, bảo mật và chống gian lận thông qua:

- **Nhận diện khuôn mặt**: Sử dụng AI/ML để xác thực danh tính nhân viên
- **Xác thực vị trí**: Kiểm tra nhân viên có đang ở đúng vị trí rạp phim (bán kính 100m) thông qua việc kết nối với thiết bị GPS cố định tại rạp phim
- **Xác thực GPS qua JWT**: Sử dụng JWT token để đảm bảo dữ liệu GPS được lấy từ thiết bị GPS cố định do quản lý rạp quản lý (không phải theo dõi vị trí nhân viên)

### 1.2. Các tính năng chính

1. **Đăng ký khuôn mặt**: Nhân viên đăng ký khuôn mặt lần đầu để hệ thống lưu trữ embedding
2. **Chấm công vào (Check-in)**: Ghi nhận thời gian nhân viên bắt đầu ca làm việc
3. **Chấm công ra (Check-out)**: Ghi nhận thời gian nhân viên kết thúc ca làm việc
4. **Lịch sử chấm công**: Xem lịch sử chấm công trong 7 ngày gần nhất
5. **Kiểm tra trạng thái đăng ký**: Xác minh nhân viên đã đăng ký khuôn mặt chưa

### 1.3. Ưu điểm và Nhược điểm của Giải pháp

#### 1.3.1. Ưu điểm

**1. Bảo mật cao và chống gian lận**
- ✅ **Xác thực đa lớp**: Kết hợp nhận diện khuôn mặt + GPS verification + JWT token
- ✅ **Không thể giả mạo**: Khó có thể fake khuôn mặt và vị trí GPS đồng thời
- ✅ **JWT token có chữ ký**: Đảm bảo dữ liệu GPS không bị giả mạo
- ✅ **Kiểm tra khoảng cách thực tế**: Haversine formula đảm bảo nhân viên thực sự ở rạp phim

**2. Trải nghiệm người dùng tốt**
- ✅ **Không cần thiết bị chuyên dụng**: Chỉ cần browser và camera (có sẵn trên hầu hết thiết bị)
- ✅ **Quy trình đơn giản**: Chỉ cần click button và quay video 3 giây
- ✅ **Real-time feedback**: MediaPipe phát hiện khuôn mặt ngay lập tức
- ✅ **Giao diện trực quan**: UI hiện đại với Tailwind CSS

**3. Công nghệ tiên tiến**
- ✅ **AI/ML hiện đại**: Sử dụng FaceNet (InceptionResnetV1) - model state-of-the-art
- ✅ **Vector database**: ChromaDB cho phép tìm kiếm similarity nhanh
- ✅ **Face detection tốt**: MTCNN phát hiện khuôn mặt chính xác
- ✅ **Video quality check**: Tự động kiểm tra chất lượng video trước khi xử lý

**4. Khả năng mở rộng**
- ✅ **Kiến trúc tách biệt**: PHP backend, Python AI, ChromaDB độc lập
- ✅ **Dễ dàng nâng cấp**: Có thể thay đổi model AI mà không ảnh hưởng backend
- ✅ **Scalable database**: ChromaDB hỗ trợ scale horizontal
- ✅ **RESTful API**: Dễ dàng tích hợp với hệ thống khác

**5. Bảo trì và giám sát**
- ✅ **Logging chi tiết**: Ghi log đầy đủ cho debugging và audit
- ✅ **Error handling tốt**: Xử lý lỗi rõ ràng, thông báo user-friendly
- ✅ **Monitoring dễ dàng**: Có thể monitor qua log files và database queries

**6. Hiệu suất**
- ✅ **Cosine similarity nhanh**: So sánh embedding vector rất nhanh
- ✅ **Video ngắn**: Chỉ cần 3 giây video, giảm bandwidth và storage
- ✅ **Caching embeddings**: Embeddings được lưu sẵn, không cần tính lại mỗi lần

#### 1.3.2. Nhược điểm

**1. Phụ thuộc vào môi trường**
- ❌ **Yêu cầu camera**: Phải có camera hoạt động tốt
- ❌ **Yêu cầu ánh sáng**: Cần ánh sáng đủ để video đạt chất lượng
- ❌ **Yêu cầu wifi**: Phải kết nối đúng wifi của rạp phim
- ❌ **Yêu cầu GPS**: Thiết bị phải có GPS hoặc native app phải chạy

**2. Độ phức tạp kỹ thuật**
- ❌ **Nhiều thành phần**: PHP + Python + ChromaDB + MySQL - khó setup ban đầu
- ❌ **Python dependencies nặng**: PyTorch, FaceNet yêu cầu nhiều tài nguyên
- ❌ **Native app dependency**: Phụ thuộc vào native app cho GPS service
- ❌ **Khó debug**: Lỗi có thể xảy ra ở nhiều layer (frontend, PHP, Python, DB)

**3. Hiệu suất và tài nguyên**
- ❌ **Xử lý chậm**: Python script xử lý video có thể mất 2-5 giây
- ❌ **Tốn tài nguyên**: FaceNet model lớn, cần RAM và CPU mạnh
- ❌ **GPU recommended**: Chạy tốt nhất trên GPU, CPU sẽ chậm hơn
- ❌ **Video upload**: Upload video qua mạng có thể chậm nếu bandwidth thấp

**4. Độ chính xác**
- ❌ **False negatives**: Có thể từ chối nhân viên hợp lệ (similarity < 0.6)
- ❌ **Ảnh hưởng bởi điều kiện**: Ánh sáng, góc quay, trang điểm có thể ảnh hưởng
- ❌ **GPS không chính xác**: Thiết bị GPS cố định tại rạp có thể sai lệch 10-50m, đặc biệt trong nhà hoặc khi tín hiệu GPS yếu
- ❌ **Threshold cố định**: Threshold 0.6 có thể không phù hợp với mọi người

**5. Bảo mật và Privacy**
- ❌ **Privacy concerns**: Lưu trữ face embeddings có thể gây lo ngại về privacy
- ❌ **Video storage**: Video tạm thời được lưu trên server (cần cleanup)
- ❌ **JWT secret key**: Nếu leak secret key, có thể fake GPS data
- ⚠️ **Lưu ý**: GPS là thiết bị cố định tại rạp phim do quản lý quản lý, không phải theo dõi vị trí nhân viên. Hệ thống chỉ kiểm tra nhân viên có trong phạm vi rạp phim (100m) thông qua việc kết nối với thiết bị GPS cố định này

**6. Chi phí và bảo trì**
- ❌ **Chi phí infrastructure**: Cần server mạnh để chạy AI models
- ❌ **Chi phí ChromaDB**: Nếu dùng cloud version
- ❌ **Maintenance phức tạp**: Cần maintain nhiều services
- ❌ **Training/Support**: Cần training nhân viên sử dụng hệ thống

**7. Hạn chế kỹ thuật**
- ❌ **Single face only**: Chỉ cho phép 1 khuôn mặt trong khung hình
- ❌ **Video format**: Chỉ hỗ trợ WebM format từ browser
- ❌ **Browser compatibility**: Yêu cầu browser hiện đại (WebRTC, MediaRecorder)
- ❌ **Offline không hoạt động**: Cần internet để gọi API

**8. Business Logic**
- ❌ **Phụ thuộc phân công**: Phải có bản ghi `phan_cong` trước khi chấm công
- ❌ **Không linh hoạt**: Không cho phép chấm công ngoài giờ hoặc địa điểm khác
- ❌ **Không có fallback**: Nếu AI fail, không có phương án dự phòng

#### 1.3.3. So sánh với Giải pháp Khác

| Tiêu chí | Chấm công khuôn mặt | Chấm công vân tay | Chấm công thẻ | Chấm công QR code |
|----------|---------------------|-------------------|--------------|-------------------|
| **Bảo mật** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐ | ⭐⭐ |
| **Tiện lợi** | ⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| **Chi phí thiết bị** | ⭐⭐⭐⭐⭐ | ⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| **Độ chính xác** | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐ |
| **Khả năng giả mạo** | Rất khó | Khó | Dễ | Rất dễ |
| **Yêu cầu phần cứng** | Camera | Máy quét vân tay | Máy đọc thẻ | Camera/QR scanner |
| **Privacy** | ⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ |

#### 1.3.4. Khuyến nghị Cải thiện

**Ngắn hạn (1-3 tháng)**:
1. ✅ Thêm fallback mechanism khi AI fail (cho phép admin approve manual)
2. ✅ Cải thiện error messages để user-friendly hơn
3. ✅ Tối ưu Python script để giảm thời gian xử lý
4. ✅ Thêm retry mechanism cho GPS token retrieval
5. ✅ Auto cleanup video files sau khi xử lý

**Trung hạn (3-6 tháng)**:
1. ✅ Hỗ trợ GPU để tăng tốc độ xử lý
2. ✅ Thêm admin dashboard để monitor và quản lý
3. ✅ Cải thiện video quality check với ML model
4. ✅ Thêm batch processing cho nhiều nhân viên
5. ✅ Implement caching cho embeddings

**Dài hạn (6-12 tháng)**:
1. ✅ Fine-tune FaceNet model với dataset riêng
2. ✅ Thêm liveness detection để chống ảnh/video fake
3. ✅ Hỗ trợ offline mode với sync sau
4. ✅ Mobile app native để cải thiện GPS accuracy
5. ✅ Multi-factor authentication (face + voice + location)

---

## 2. KIẾN TRÚC HỆ THỐNG

### 2.1. Sơ đồ kiến trúc tổng quan

```
┌─────────────────────────────────────────────────────────────────┐
│                        CLIENT (Browser)                          │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  Frontend (Blade Template + JavaScript)                  │  │
│  │  - MediaPipe Face Detection                              │  │
│  │  - Camera Capture (WebRTC)                               │  │
│  │  - Video Recording (MediaRecorder API)                   │  │
│  └──────────────────────────────────────────────────────────┘  │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             │ HTTPS Request
                             │ (Video + JWT Token)
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                    SERVER (PHP Backend)                         │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  Routes (FastRoute)                                      │  │
│  │  - POST /cham-cong/cham-cong                             │  │
│  │  - POST /cham-cong/dang-ky-khuon-mat                     │  │
│  │  - GET  /cham-cong/lich-su                                │  │
│  │  - GET  /cham-cong/kiem-tra-dang-ky                      │  │
│  └──────────────┬───────────────────────────────────────────┘  │
│                 │                                                │
│  ┌──────────────▼───────────────────────────────────────────┐  │
│  │  Controller (Ctrl_ChamCong)                               │  │
│  │  - Xử lý request/response                                │  │
│  │  - Xác thực quyền truy cập                                │  │
│  └──────────────┬───────────────────────────────────────────┘  │
│                 │                                                │
│  ┌──────────────▼───────────────────────────────────────────┐  │
│  │  Service (Sc_ChamCong)                                   │  │
│  │  - Validate input                                         │  │
│  │  - Decode JWT token                                       │  │
│  │  - Verify GPS location                                    │  │
│  │  - Call Python script                                     │  │
│  │  - Update database                                        │  │
│  └──────────────┬───────────────────────────────────────────┘  │
└─────────────────┼───────────────────────────────────────────────┘
                  │
                  │ exec() Python Script
                  ▼
┌─────────────────────────────────────────────────────────────────┐
│              PYTHON SCRIPT (face.py)                            │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  Face Recognition Pipeline                                │  │
│  │  1. Video Quality Check (OpenCV)                        │  │
│  │  2. Face Detection (MTCNN)                               │  │
│  │  3. Face Embedding (FaceNet)                            │  │
│  │  4. Similarity Comparison (Cosine)                      │  │
│  └──────────────┬──────────────────────────────────────────┘  │
└─────────────────┼───────────────────────────────────────────────┘
                  │
                  │ HTTP API
                  ▼
┌─────────────────────────────────────────────────────────────────┐
│                    ChromaDB (Vector Database)                   │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  Collection: face_embeddings                              │  │
│  │  - Store face embeddings (512-dim vectors)              │  │
│  │  - Cosine similarity search                             │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                  │
                  │ Eloquent ORM
                  ▼
┌─────────────────────────────────────────────────────────────────┐
│                    MySQL Database                               │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  Tables:                                                 │  │
│  │  - phan_cong (attendance records)                        │  │
│  │  - dangky_khuonmat (face registration)                   │  │
│  │  - nguoidung_noibo (employees)                           │  │
│  │  - rapphim (cinema locations)                           │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
```

### 2.2. Luồng dữ liệu chấm công

```
1. User clicks "Check In" button
   ↓
2. Frontend captures video (3 seconds)
   ↓
3. Frontend gets GPS token from native app server (http://IP:2552)
   ↓
4. Frontend uploads video + token to PHP backend
   ↓
5. PHP validates session, decodes JWT token
   ↓
6. PHP verifies GPS location (within 100m radius)
   ↓
7. PHP calls Python script with video file
   ↓
8. Python checks video quality
   ↓
9. Python extracts face embedding from video
   ↓
10. Python retrieves stored embedding from ChromaDB
   ↓
11. Python calculates cosine similarity
   ↓
12. If similarity >= 0.6: SUCCESS
   ↓
13. PHP updates phan_cong table
   ↓
14. PHP returns success response
   ↓
15. Frontend displays result and refreshes history
```

---

## 3. CÔNG NGHỆ SỬ DỤNG

### 3.1. Backend (PHP)

- **Framework**: Custom PHP với Eloquent ORM (Laravel components)
- **Routing**: FastRoute
- **Database**: MySQL với Eloquent ORM
- **Session Management**: PHP Native Sessions
- **File Processing**: PHP native file handling

### 3.2. Frontend

- **Template Engine**: Blade (Laravel)
- **JavaScript**: Vanilla ES6+ (Module)
- **Face Detection**: MediaPipe Face Detector (BlazeFace)
- **Video Capture**: WebRTC MediaDevices API
- **Video Recording**: MediaRecorder API
- **UI Framework**: Tailwind CSS

### 3.3. AI/ML (Python)

- **Face Detection**: MTCNN (Multi-task CNN)
- **Face Recognition**: FaceNet (InceptionResnetV1)
- **Framework**: PyTorch, facenet-pytorch
- **Computer Vision**: OpenCV (cv2)
- **Vector Database**: ChromaDB (HTTP client)
- **Similarity Metric**: Cosine Similarity

### 3.4. Database

- **Relational DB**: MySQL
  - `phan_cong`: Lưu lịch sử chấm công
  - `dangky_khuonmat`: Lưu thông tin đăng ký khuôn mặt
  - `nguoidung_noibo`: Thông tin nhân viên
  - `rapphim`: Thông tin rạp phim (tọa độ GPS)

- **Vector DB**: ChromaDB
  - Collection: `face_embeddings`
  - Storage: Face embeddings (512-dimensional vectors)
  - Similarity: Cosine distance

### 3.5. Native App Integration

- **GPS Service**: Native app chạy server HTTP trên port 2552
- **Protocol**: HTTP GET request để lấy JWT token chứa GPS data
- **Authentication**: JWT với HMAC-SHA256 signature

---

## 4. LUỒNG XỬ LÝ CHẤM CÔNG

### 4.1. Đăng ký khuôn mặt (Face Registration)

**File**: `src/Services/Sc_ChamCong.php` - Method: `dangKyKhuonMat()`

**Luồng xử lý**:

1. **Validate Input**:
   - Kiểm tra session nhân viên
   - Kiểm tra file video được upload

2. **Gọi Python Script**:
   ```bash
   python3 bin/python/face.py <video_path> <id_nhanvien> register
   ```

3. **Python xử lý**:
   - Kiểm tra chất lượng video (brightness, sharpness, noise, contrast)
   - Phát hiện khuôn mặt trong video (MTCNN)
   - Tạo embedding từ video (FaceNet)
   - Lưu embedding vào ChromaDB

4. **Lưu thông tin đăng ký**:
   - Tạo/update record trong bảng `dangky_khuonmat`
   - Trạng thái: "Đang hoạt động"

**Code Reference**:
```49:102:src/Services/Sc_ChamCong.php
public function dangKyKhuonMat()
{
    $idNhanVien = $_SESSION['UserInternal']['ID'] ?? null;
    if (!$idNhanVien) {
        throw new Exception('Thiếu id nhân viên');
    }
    
    $videoPath = $_FILES['video']['tmp_name'] ?? null;
    if (!$videoPath) {
        throw new Exception('Thiếu file video tải lên');
    }
    
    $envPython = $_ENV['PYTHON_PATH'] ?? 'python3';
    $filePython = __DIR__ . '/../../bin/python/face.py';
    $fileLog = __DIR__ . '/../../cache/log/face_register.log';
    
    $command = escapeshellcmd("$envPython $filePython $videoPath $idNhanVien register");
    
    // Thực thi lệnh và chuyển hướng đầu ra lỗi vào file log
    exec("$command 2>> $fileLog", $output, $returnVar);
    
    if ($returnVar != 0) {
        throw new Exception('Lỗi khi gọi Đăng ký khuôn mặt. Xem log để biết thêm chi tiết.');
    }
    
    // Ghi log đầu ra từ Python
    file_put_contents($fileLog, implode("\n", $output) . "\n", FILE_APPEND);
    
    // Phân tích kết quả trả về từ Python
    $result = implode("\n", $output);
    
    // Kiểm tra kết quả đăng ký thành công
    if (strpos($result, 'Face registration SUCCESSFUL') === false) {
        throw new Exception('Đăng ký khuôn mặt thất bại. Vui lòng kiểm tra chất lượng video và thử lại.');
    }
    
    $dangKyKhuonMat = DangKyKhuonMat::where('id_nhanvien', $idNhanVien)->first();
    if ($dangKyKhuonMat) {
        $dangKyKhuonMat->update([
            'ngay_dang_ky' => date('Y-m-d H:i:s'),
        ]);
    } else {
        $created = DangKyKhuonMat::create([
            'id_nhanvien' => $idNhanVien,
            'ngay_dang_ky' => date('Y-m-d H:i:s'),
            'trang_thai' => 'Đang hoạt động'
        ]);
        if (!$created) {
            throw new Exception('Lỗi lưu thông tin đăng ký khuôn mặt');
        }
    }
    
    return ['success' => true, 'message' => 'Đăng ký khuôn mặt thành công'];
}
```

### 4.2. Chấm công (Check-in/Check-out)

**File**: `src/Services/Sc_ChamCong.php` - Method: `chamCongKhuonMat()`

**Luồng xử lý chi tiết**:

#### Bước 1: Validate Input
- Kiểm tra session nhân viên
- Validate loại chấm công (`checkin` hoặc `checkout`)
- Kiểm tra nhân viên đã đăng ký khuôn mặt
- Validate JWT token

#### Bước 2: Decode và Verify JWT Token
- Decode JWT token từ client
- Verify signature (HMAC-SHA256)
- Kiểm tra expiration time
- Parse GPS data từ payload

**Code Reference**:
```103:178:src/Services/Sc_ChamCong.php
public function decodeToken($token){
    // Tách token thành 3 phần: header.payload.signature
    $parts = explode('.', $token);
    
    if (count($parts) !== 3) {
        return [
            'error' => true,
            'message' => 'Invalid token format'
        ];
    }
    
    list($headerB64, $payloadB64, $signatureB64) = $parts;
    
    // Giải mã header và payload
    $header = $this->base64UrlDecode($headerB64);
    $payload = $this->base64UrlDecode($payloadB64);
    
    // Parse JSON
    $headerData = json_decode($header, true);
    $payloadData = json_decode($payload, true);
    
    if (!$headerData || !$payloadData) {
        return [
            'error' => true,
            'message' => 'Invalid JSON in token'
        ];
    }
    
    // Kiểm tra algorithm
    if (!isset($headerData['alg']) || $headerData['alg'] !== 'HS256') {
        return [
            'error' => true,
            'message' => 'Unsupported algorithm: ' . ($headerData['alg'] ?? 'none')
        ];
    }
    
    // Xác thực chữ ký
    $signature = $this->base64UrlDecode($signatureB64);
    $expectedSignature = $this->sign($headerB64 . '.' . $payloadB64);
    
    if (!hash_equals($signature, $expectedSignature)) {
        return [
            'error' => true,
            'message' => 'Invalid signature'
        ];
    }
    
    // Kiểm tra thời gian hết hạn
    if (isset($payloadData['exp'])) {
        if (time() > $payloadData['exp']) {
            return [
                'error' => true,
                'message' => 'Token expired',
                'expired_at' => date('Y-m-d H:i:s', $payloadData['exp'])
            ];
        }
    }
    
    // Kiểm tra thời gian bắt đầu có hiệu lực
    if (isset($payloadData['nbf'])) {
        if (time() + 2 < $payloadData['nbf']) {
            return [
                'error' => true,
                'message' => 'Token not yet valid',
                'valid_from' => date('Y-m-d H:i:s', $payloadData['nbf'])
            ];
        }
    }
    
    // Trả về payload đã được xác thực
    return [
        'error' => false,
        'data' => $payloadData,
        'header' => $headerData
    ];
}
```

#### Bước 3: Verify GPS Location
- Parse GPS data từ JWT payload (tọa độ từ thiết bị GPS cố định tại rạp phim)
- Lấy tọa độ rạp phim từ database (tọa độ đã cấu hình)
- Tính khoảng cách giữa tọa độ từ thiết bị GPS cố định và tọa độ rạp phim (Haversine formula)
- Kiểm tra khoảng cách <= 100m (đảm bảo thiết bị GPS cố định ở đúng vị trí rạp phim)

**Code Reference**:
```396:430:src/Services/Sc_ChamCong.php
private function tinhKhoangCach($kinhDoNhanVien, $viDoNhanVien, $kinhDoRapPhim, $viDoRapPhim)
{
    // Lấy tọa độ rạp phim từ biến môi trường hoặc config
    
    if (!$kinhDoRapPhim || !$viDoRapPhim) {
        throw new Exception('Chưa cấu hình tọa độ rạp phim. Vui lòng liên hệ quản trị viên.');
    }
    
    // Chuyển đổi sang float để đảm bảo tính toán chính xác
    $kinhDoNhanVien = (float) $kinhDoNhanVien;
    $viDoNhanVien = (float) $viDoNhanVien;
    $kinhDoRapPhim = (float) $kinhDoRapPhim;
    $viDoRapPhim = (float) $viDoRapPhim;
    
    // Bán kính Trái Đất tính bằng mét
    $banKinhTraiDat = 6371000; // 6371 km = 6371000 mét
    
    // Chuyển đổi độ sang radian
    $lat1Rad = deg2rad($viDoNhanVien);
    $lat2Rad = deg2rad($viDoRapPhim);
    $deltaLatRad = deg2rad($viDoRapPhim - $viDoNhanVien);
    $deltaLonRad = deg2rad($kinhDoRapPhim - $kinhDoNhanVien);
    
    // Công thức Haversine
    $a = sin($deltaLatRad / 2) * sin($deltaLatRad / 2) +
         cos($lat1Rad) * cos($lat2Rad) *
         sin($deltaLonRad / 2) * sin($deltaLonRad / 2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    
    // Khoảng cách tính bằng mét
    $khoangCach = $banKinhTraiDat * $c;
    
    return $khoangCach;
}
```

#### Bước 4: Face Recognition
- Gọi Python script để xác thực khuôn mặt
- Python kiểm tra chất lượng video
- Python tạo embedding từ video
- Python so sánh với embedding đã lưu (Cosine similarity >= 0.6)

**Python Script Flow**:
```303:348:bin/python/face.py
def check_face(video_path, id_employee):
    """
    Xác thực khuôn mặt từ video với khuôn mặt đã đăng ký
    Returns: True nếu khớp, False nếu không khớp
    """
    print(f"\n{'='*60}")
    print(f"CHECKING FACE FOR ID={id_employee}")
    print(f"{'='*60}")
    
    # Bước 1: Kiểm tra chất lượng video
    result_check_quality = check_quality(video_path)
    if not result_check_quality:
        print(f"\n✗ Face verification FAILED (low quality) - {datetime.datetime.now()}")
        return False
    
    print(f"\n✓ Face quality check passed - {datetime.datetime.now()}")
    
    # Bước 2: Lấy embedding đã lưu từ database
    embedding_stored = get_embedding(id_employee)
    if embedding_stored is None:
        print(f"\n✗ Face verification FAILED (no stored embedding for ID={id_employee}) - {datetime.datetime.now()}")
        return False
    
    # Bước 3: Tạo embedding từ video xác nhận
    print(f"\nCreating embedding from verification video...")
    embedding_confirm = faceToEmbedding(video_path)
    if embedding_confirm is None:
        print(f"\n✗ Face verification FAILED (cannot create embedding from video) - {datetime.datetime.now()}")
        return False
    
    # Bước 4: So sánh 2 embedding
    similarity = cosine_similarity(embedding_stored, embedding_confirm)
    print(f"\n{'='*60}")
    print(f"SIMILARITY SCORE: {similarity:.4f}")
    print(f"THRESHOLD: {SIMILARITY_THRESHOLD}")
    print(f"{'='*60}")
    
    # Bước 5: Đánh giá kết quả
    if similarity >= SIMILARITY_THRESHOLD:
        print(f"\n✓ Face verification SUCCESSFUL - Match confirmed! - {datetime.datetime.now()}")
        print(f"  Similarity: {similarity:.4f} >= {SIMILARITY_THRESHOLD}")
        return True
    else:
        print(f"\n✗ Face verification FAILED - No match - {datetime.datetime.now()}")
        print(f"  Similarity: {similarity:.4f} < {SIMILARITY_THRESHOLD}")
        return False
```

#### Bước 5: Update Database
- Tìm bản ghi `phan_cong` của nhân viên trong ngày
- Update `gio_vao` (check-in) hoặc `gio_ra` (check-out)
- Validate logic: không cho phép check-in 2 lần, phải check-in trước khi check-out

**Code Reference**:
```336:369:src/Services/Sc_ChamCong.php
// 5. Lưu vào bảng chấm công
$ngayHienTai = date('Y-m-d');
$gioHienTai = date('Y-m-d H:i:s');

$daChamCong = PhanCong::where('id_nhanvien', $idNhanVien)
    ->where('ngay', $ngayHienTai)
    ->first();

if ($daChamCong) {
    // Đã có bản ghi - update
    if ($loai == 'checkin') {
        // Kiểm tra đã check-in chưa
        if ($daChamCong->gio_vao) {
            throw new Exception('Bạn đã chấm công vào rồi. Thời gian: ' . $daChamCong->gio_vao);
        }
        $daChamCong->update([
            'gio_vao' => $gioHienTai
        ]);
    } else if ($loai == 'checkout') {
        // Kiểm tra đã check-in chưa
        if (!$daChamCong->gio_vao) {
            throw new Exception('Bạn chưa chấm công vào. Vui lòng chấm công vào trước.');
        }
        // Kiểm tra đã check-out chưa
        if ($daChamCong->gio_ra) {
            throw new Exception('Bạn đã chấm công ra rồi. Thời gian: ' . $daChamCong->gio_ra);
        }
        $daChamCong->update([
            'gio_ra' => $gioHienTai
        ]);
    }
} else {
    throw new Exception('Nhận diện khuôn mặt thành công nhưng không tìm thấy bản ghi phân công hiện tại.');
}
```

---

## 5. CHI TIẾT CÁC THÀNH PHẦN

### 5.1. Service Layer: Sc_ChamCong

**File**: `src/Services/Sc_ChamCong.php`

**Các phương thức chính**:

#### 5.1.1. `kiemTraDangKy()`
- **Mục đích**: Kiểm tra nhân viên đã đăng ký khuôn mặt chưa
- **Input**: Session nhân viên
- **Output**: Thông tin đăng ký hoặc Exception
- **Logic**: Query `dangky_khuonmat` với `trang_thai = 'Đang hoạt động'`

#### 5.1.2. `dangKyKhuonMat()`
- **Mục đích**: Đăng ký khuôn mặt mới cho nhân viên
- **Input**: File video từ `$_FILES['video']`
- **Output**: Success message hoặc Exception
- **Process**:
  1. Validate input
  2. Gọi Python script với command `register`
  3. Parse kết quả từ Python
  4. Lưu thông tin vào `dangky_khuonmat`

#### 5.1.3. `chamCongKhuonMat()`
- **Mục đích**: Xử lý chấm công vào/ra
- **Input**: 
  - `$_POST['loai']`: 'checkin' hoặc 'checkout'
  - `$_POST['token']`: JWT token chứa GPS data
  - `$_FILES['video']`: Video file
- **Output**: Success response với thông tin chấm công
- **Process**: Xem chi tiết ở mục 4.2

#### 5.1.4. `lichSuChamCong()`
- **Mục đích**: Lấy lịch sử chấm công 7 ngày gần nhất
- **Input**: Session nhân viên
- **Output**: Array các bản ghi `PhanCong`
- **Logic**: Query `phan_cong` với `whereBetween('ngay', [7 ngày trước, hôm nay])`

#### 5.1.5. `decodeToken($token)`
- **Mục đích**: Decode và verify JWT token
- **Input**: JWT token string
- **Output**: Array với `error` flag và `data` payload
- **Validation**:
  - Format token (3 parts: header.payload.signature)
  - Algorithm (HS256)
  - Signature verification (HMAC-SHA256)
  - Expiration time
  - Not-before time

#### 5.1.6. `parseGPSData($payload)`
- **Mục đích**: Parse GPS data từ JWT payload
- **Input**: JWT payload array
- **Output**: Array với GPS coordinates và metadata
- **Fields**: status, latitude, longitude, accuracy, altitude, timestamp, google_maps_url

#### 5.1.7. `tinhKhoangCach(...)`
- **Mục đích**: Tính khoảng cách giữa 2 tọa độ GPS
- **Algorithm**: Haversine formula
- **Input**: Longitude và Latitude của 2 điểm
- **Output**: Khoảng cách tính bằng mét

### 5.2. Controller Layer: Ctrl_ChamCong

**File**: `src/Controllers/Ctrl_ChamCong.php`

**Các phương thức**:

#### 5.2.1. `index()`
- **Route**: `GET /cham-cong` (internal route)
- **Mục đích**: Hiển thị trang chấm công
- **Output**: Blade view `internal.cham-cong`
- **Data**: Thông tin định vị (wifi IP, wifi name)

#### 5.2.2. `dangKyKhuonMat()`
- **Route**: `GET /dang-ky-khuon-mat` (internal route)
- **Mục đích**: Hiển thị trang đăng ký khuôn mặt
- **Output**: Blade view `internal.dang-ky-khuon-mat`

#### 5.2.3. `xuLyDangKyKhuonMat()`
- **Route**: `POST /api/cham-cong/dang-ky-khuon-mat`
- **Mục đích**: Xử lý đăng ký khuôn mặt
- **Output**: JSON response với success flag

#### 5.2.4. `kiemTraDangKy()`
- **Route**: `GET /api/cham-cong/kiem-tra-dang-ky`
- **Mục đích**: API kiểm tra trạng thái đăng ký
- **Output**: JSON với thông tin đăng ký

#### 5.2.5. `chamCongKhuonMat()`
- **Route**: `POST /api/cham-cong/cham-cong`
- **Mục đích**: API xử lý chấm công
- **Output**: JSON với kết quả chấm công

#### 5.2.6. `lichSuChamCong()`
- **Route**: `GET /api/cham-cong/lich-su`
- **Mục đích**: API lấy lịch sử chấm công
- **Output**: JSON với danh sách lịch sử

### 5.3. Frontend: cham-cong.js

**File**: `internal/js/cham-cong.js`

**Các chức năng chính**:

#### 5.3.1. Face Detection với MediaPipe
- Sử dụng MediaPipe Face Detector (BlazeFace)
- Real-time detection trong video stream
- Chỉ cho phép 1 khuôn mặt trong khung hình

#### 5.3.2. Video Recording
- Sử dụng MediaRecorder API
- Format: WebM (VP9 codec)
- Duration: 3 giây
- Resolution: 1280x720

#### 5.3.3. GPS Token Retrieval
- Gọi native app server: `http://<wifi_ip>:2552`
- Lấy JWT token chứa GPS data
- Timeout: 10 giây
- Error handling cho các trường hợp:
  - Không kết nối được server
  - Wifi không đúng
  - Server trả về lỗi

**Code Reference**:
```252:297:internal/js/cham-cong.js
async function getTokenApi(){
    const wifiIp = cameraSection.dataset.ip;
    const wifiTen = cameraSection.dataset.ten;
    
    if (!wifiIp) {
        throw new Error('Không tìm thấy thông tin IP wifi. Vui lòng liên hệ quản lý rạp.');
    }
    
    try {
        // Thêm timeout cho fetch request (10 giây)
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 10000);
        
        const response = await fetch(`http://${wifiIp}:2552`, {
            signal: controller.signal,
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });
        
        clearTimeout(timeoutId);
        
        if (!response.ok) {
            throw new Error(`Hãy đảm bảo bạn đã kết nối tới wifi: ${wifiTen}\nNếu đã kết nối đúng tới wifi, hãy liên hệ quản lý rạp để xử lý.`);
        }
        
        const data = await response.json();
        if(data.status != 'success'){
            throw new Error(`Hệ thống đang cập nhật wifi vui lòng thử lại sau.`)
        }
        else {
            return data.token;
        }
    }
    catch (error) {
        if (error.name === 'AbortError') {
            throw new Error(`Vui lòng kết nối tới wifi ${wifiTen}. Kiểm tra kết nối mạng và thử lại.`);
        } else if (error.message.includes('Failed to fetch') || error.message.includes('NetworkError')) {
            throw new Error(`Không thể kết nối tới Server GPS.\nVui lòng đảm bảo:\n- Bạn đã kết nối đúng wifi\n- Router đang hoạt động bình thường\n- Liên hệ quản lý rạp nếu vấn đề vẫn tiếp tục.`);
        } else {
            throw new Error(`Lỗi hệ thống: ${error.message}`);
        }
    }

}
```

#### 5.3.4. Upload và Xử lý Response
- Upload video + token lên server
- Hiển thị loading modal
- Xử lý response và cập nhật UI
- Refresh lịch sử chấm công

### 5.4. Python Script: face.py

**File**: `bin/python/face.py`

**Các hàm chính**:

#### 5.4.1. `check_quality(video_path)`
- **Mục đích**: Kiểm tra chất lượng video
- **Metrics**:
  - Brightness: 50-230
  - Sharpness: >= 150 (Laplacian variance)
  - Noise: <= 100 (std deviation)
  - Contrast: >= 10 (RMS contrast)
- **Process**: Đọc 10 frames đầu tiên, tính trung bình các metrics

#### 5.4.2. `faceToEmbedding(video_path)`
- **Mục đích**: Tạo face embedding từ video
- **Process**:
  1. Load MTCNN detector và FaceNet model
  2. Đọc video frame by frame (sample rate: 5)
  3. Detect face trong mỗi frame
  4. Tạo embedding cho mỗi face
  5. Tính embedding trung bình và chuẩn hóa
- **Output**: 512-dimensional vector (numpy array)

#### 5.4.3. `save_embedding(embedding, id_employee)`
- **Mục đích**: Lưu embedding vào ChromaDB
- **Collection**: `face_embeddings`
- **ID Format**: `face_<id_employee>`
- **Metadata**: id_employee, created_at/updated_at

#### 5.4.4. `get_embedding(id_employee)`
- **Mục đích**: Lấy embedding từ ChromaDB
- **ID Format**: `face_<id_employee>`
- **Output**: 512-dimensional vector hoặc None

#### 5.4.5. `cosine_similarity(emb1, emb2)`
- **Mục đích**: Tính độ tương đồng cosine giữa 2 embedding
- **Formula**: `dot(emb1, emb2) / (norm(emb1) * norm(emb2))`
- **Output**: Float từ -1 đến 1 (1 = hoàn toàn giống nhau)

#### 5.4.6. `register_face(video_path, id_employee)`
- **Mục đích**: Đăng ký khuôn mặt mới
- **Process**:
  1. Kiểm tra chất lượng video
  2. Tạo embedding
  3. Lưu vào ChromaDB
  4. In kết quả (SUCCESS/FAILED)

#### 5.4.7. `check_face(video_path, id_employee)`
- **Mục đích**: Xác thực khuôn mặt
- **Process**:
  1. Kiểm tra chất lượng video
  2. Lấy embedding đã lưu
  3. Tạo embedding từ video mới
  4. So sánh similarity
  5. Return True nếu similarity >= 0.6

**Threshold**: `SIMILARITY_THRESHOLD = 0.6`

---

## 6. API ENDPOINTS

### 6.1. Internal Routes (Web Pages)

| Method | Route | Controller Method | Permission | Mô tả |
|--------|-------|-------------------|------------|-------|
| GET | `/cham-cong` | `Ctrl_ChamCong::index()` | Nhân viên | Trang chấm công |
| GET | `/dang-ky-khuon-mat` | `Ctrl_ChamCong::dangKyKhuonMat()` | Nhân viên | Trang đăng ký khuôn mặt |

### 6.2. API Routes (JSON)

| Method | Route | Controller Method | Permission | Mô tả |
|--------|-------|-------------------|------------|-------|
| POST | `/api/cham-cong/dang-ky-khuon-mat` | `Ctrl_ChamCong::xuLyDangKyKhuonMat()` | Nhân viên | Đăng ký khuôn mặt |
| POST | `/api/cham-cong/cham-cong` | `Ctrl_ChamCong::chamCongKhuonMat()` | Nhân viên | Chấm công vào/ra |
| GET | `/api/cham-cong/lich-su` | `Ctrl_ChamCong::lichSuChamCong()` | Nhân viên | Lịch sử chấm công |
| GET | `/api/cham-cong/kiem-tra-dang-ky` | `Ctrl_ChamCong::kiemTraDangKy()` | Nhân viên | Kiểm tra trạng thái đăng ký |

### 6.3. API Request/Response Examples

#### 6.3.1. POST `/api/cham-cong/cham-cong`

**Request**:
```http
POST /api/cham-cong/cham-cong
Content-Type: multipart/form-data

video: <File>
loai: checkin|checkout
token: <JWT_TOKEN>
wifiTen: <WIFI_NAME>
```

**Response Success**:
```json
{
    "success": true,
    "message": "Chấm công thành công",
    "data": {
        "success": true,
        "message": "Chấm công thành công",
        "loai": "checkin",
        "thoi_gian": "2024-01-15 08:30:00",
        "nhan_vien": {
            "id": 123,
            "ten": "Nguyễn Văn A"
        }
    }
}
```

**Response Error**:
```json
{
    "success": false,
    "message": "Lỗi hệ thống: Bạn chưa đăng ký khuôn mặt. Vui lòng đăng ký trước khi chấm công."
}
```

#### 6.3.2. POST `/api/cham-cong/dang-ky-khuon-mat`

**Request**:
```http
POST /api/cham-cong/dang-ky-khuon-mat
Content-Type: multipart/form-data

video: <File>
```

**Response Success**:
```json
{
    "success": true,
    "message": "Đăng ký khuôn mặt thành công"
}
```

#### 6.3.3. GET `/api/cham-cong/lich-su`

**Response**:
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "id_nhanvien": 123,
            "ngay": "2024-01-15",
            "gio_vao": "2024-01-15 08:30:00",
            "gio_ra": "2024-01-15 17:30:00",
            "trang_thai": "Đúng giờ"
        },
        ...
    ]
}
```

#### 6.3.4. GET `/api/cham-cong/kiem-tra-dang-ky`

**Response Success**:
```json
{
    "success": true,
    "message": "Đã đăng ký khuôn mặt",
    "data": {
        "id": 1,
        "id_nhanvien": 123,
        "ngay_dang_ky": "2024-01-10 10:00:00",
        "trang_thai": "Đang hoạt động"
    }
}
```

**Response Error**:
```json
{
    "success": false,
    "message": "Lỗi hệ thống: Chưa đăng ký khuôn mặt"
}
```

---

## 7. CƠ SỞ DỮ LIỆU

### 7.1. MySQL Tables

#### 7.1.1. Bảng `phan_cong`

**Mục đích**: Lưu trữ lịch sử chấm công và phân công ca làm việc

**Schema**:
```sql
CREATE TABLE `phan_cong` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `id_nhanvien` INT NOT NULL,
    `id_congviec` INT,
    `ngay` DATE NOT NULL,
    `ca` VARCHAR(50),
    `gio_vao` DATETIME,
    `gio_ra` DATETIME,
    `ly_do` TEXT,
    `trang_thai` TINYINT DEFAULT 0, -- 0: Lịch làm, 1: Chờ duyệt, 2: Đã duyệt nghỉ, 3: Từ chối
    `created_at` TIMESTAMP,
    `updated_at` TIMESTAMP,
    FOREIGN KEY (`id_nhanvien`) REFERENCES `nguoidung_noibo`(`id`)
);
```

**Model**: `App\Models\PhanCong`

**Quan hệ**:
- `belongsTo(NguoiDungInternal::class, 'id_nhanvien')`
- `belongsTo(ViTriCongViec::class, 'id_congviec')`

#### 7.1.2. Bảng `dangky_khuonmat`

**Mục đích**: Lưu trữ thông tin đăng ký khuôn mặt của nhân viên

**Schema**:
```sql
CREATE TABLE `dangky_khuonmat` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `id_nhanvien` INT NOT NULL UNIQUE,
    `ngay_dang_ky` DATETIME NOT NULL,
    `trang_thai` VARCHAR(20) DEFAULT 'Đang hoạt động',
    `created_at` TIMESTAMP,
    `updated_at` TIMESTAMP,
    FOREIGN KEY (`id_nhanvien`) REFERENCES `nguoidung_noibo`(`id`)
);
```

**Model**: `App\Models\DangKyKhuonMat`

**Quan hệ**:
- `belongsTo(NguoiDungInternal::class, 'id_nhanvien')`

**Trạng thái**:
- `'Đang hoạt động'`: Khuôn mặt đã đăng ký và có thể sử dụng
- Các trạng thái khác: Có thể mở rộng trong tương lai

#### 7.1.3. Bảng `nguoidung_noibo`

**Mục đích**: Thông tin nhân viên nội bộ

**Schema** (relevant fields):
```sql
CREATE TABLE `nguoidung_noibo` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `id_taikhoan` INT NOT NULL,
    `id_rapphim` INT NOT NULL,
    `ten` VARCHAR(255),
    `email` VARCHAR(255),
    `dien_thoai` VARCHAR(20),
    `trang_thai` TINYINT DEFAULT 1, -- 1: Đang hoạt động, 0: Đã khóa, -1: Đã nghỉ việc
    ...
);
```

**Model**: `App\Models\NguoiDungInternal`

#### 7.1.4. Bảng `rapphim`

**Mục đích**: Thông tin rạp phim (bao gồm tọa độ GPS)

**Schema** (relevant fields):
```sql
CREATE TABLE `rapphim` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `ten` VARCHAR(255),
    `dia_chi` TEXT,
    `kinh_do` DECIMAL(10, 8), -- Longitude
    `vi_do` DECIMAL(10, 8),   -- Latitude
    `trang_thai` TINYINT DEFAULT 1,
    ...
);
```

**Model**: `App\Models\RapPhim`

### 7.2. ChromaDB Collection

#### 7.2.1. Collection: `face_embeddings`

**Mục đích**: Lưu trữ face embeddings (vector database)

**Configuration**:
- **Name**: `face_embeddings`
- **Similarity Metric**: Cosine
- **Vector Dimension**: 512
- **Storage**: In-memory hoặc persistent (tùy cấu hình ChromaDB)

**ID Format**: `face_<id_nhanvien>`

**Metadata**:
```json
{
    "id_employee": "123",
    "created_at": "2024-01-10T10:00:00",
    "updated_at": "2024-01-10T10:00:00"
}
```

**Vector**: 512-dimensional float array (normalized)

**Operations**:
- `add()`: Thêm embedding mới
- `upsert()`: Update hoặc insert embedding
- `get()`: Lấy embedding theo ID

**Code Reference**:
```192:232:bin/python/face.py
def save_embedding(embedding, id_employee):
    """Lưu embedding vào ChromaDB (HTTP API dùng vectors, không dùng embeddings)"""
    if collection is None:
        print("ERROR: Collection not initialized")
        return False

    target_id = "face_" + str(id_employee)
    emb_list = embedding.tolist()

    try:
        existing  = collection.get(ids=[target_id], include=["embeddings"])

        ts = datetime.datetime.now().isoformat()

        metadata = {
            "id_employee": str(id_employee),
            "updated_at" if existing and existing.get("ids") else "created_at": ts
        }
        if existing and existing.get("ids"): # HTTP API: dùng vectors 
            collection.upsert( ids=[target_id], embeddings=[emb_list], metadatas=[metadata] ) 
            print(f"SUCCESS: Updated embedding for ID={id_employee}") 
        else: 
            collection.add( ids=[target_id], vectors=[emb_list], metadatas=[metadata] ) 
            print(f"SUCCESS: Added new embedding for ID={id_employee}") 
            return True
       
            # HTTP API: dùng vectors
        collection.upsert(
            ids=[target_id],
            embeddings=[emb_list],
            metadatas=[metadata]
        )
        print(f"SUCCESS: Updated embedding for ID={id_employee}")
      

        return True

    except Exception as e:
        print(f"ERROR: Failed to save embedding: {e}")
        return False
```

---

## 8. BẢO MẬT

### 8.1. Xác thực và Phân quyền

#### 8.1.1. Session-based Authentication
- Tất cả API endpoints yêu cầu session nhân viên hợp lệ
- Session được tạo khi đăng nhập thành công
- Session chứa: `ID`, `Ten`, `Email`, `VaiTro`, `ID_RapPhim`

**Code Reference**:
```16:29:src/Services/Sc_ChamCong.php
$idNhanVien = $_SESSION['UserInternal']['ID'] ?? null;
if (!$idNhanVien) {
    throw new Exception('Không xác định được nhân viên');
}
```

#### 8.1.2. Role-based Access Control
- Routes được bảo vệ bởi middleware kiểm tra vai trò
- Chức năng chấm công chỉ dành cho vai trò "Nhân viên"

**Route Definition**:
```267:270:routes/apiv1.php
// API Chấm công bằng khuôn mặt (cho Nhân viên)
$r->addRoute('POST', '/cham-cong/dang-ky-khuon-mat', [Ctrl_ChamCong::class, 'xuLyDangKyKhuonMat', ['Nhân viên']]);
$r->addRoute('POST', '/cham-cong/cham-cong', [Ctrl_ChamCong::class, 'chamCongKhuonMat', ['Nhân viên']]);
$r->addRoute('GET', '/cham-cong/lich-su', [Ctrl_ChamCong::class, 'lichSuChamCong', ['Nhân viên']]);
$r->addRoute('GET', '/cham-cong/kiem-tra-dang-ky', [Ctrl_ChamCong::class, 'kiemTraDangKy', ['Nhân viên']]);
```

### 8.2. JWT Token Security

#### 8.2.1. Token Structure
- **Format**: `header.payload.signature`
- **Algorithm**: HS256 (HMAC-SHA256)
- **Secret Key**: Lưu trong biến môi trường `GPS_SECRET_KEY`

#### 8.2.2. Ý đồ thiết kế: Tại sao lưu GPS vào JWT Token?

Việc lưu tọa độ GPS (kinh độ, vĩ độ) vào JWT token thay vì gửi trực tiếp có các mục đích bảo mật quan trọng:

**1. Chống giả mạo GPS Data (Tamper-proof)**
- ✅ **Signature verification**: GPS coordinates được ký bằng HMAC-SHA256 với secret key
- ✅ **Không thể sửa đổi**: Nếu ai đó cố gắng thay đổi latitude/longitude trong token, signature sẽ không khớp
- ✅ **Backend verify**: Backend có thể xác thực GPS data không bị thay đổi bằng cách verify signature

**2. Xác thực nguồn gốc (Origin Authentication)**
- ✅ **Chỉ thiết bị GPS mới tạo được**: Token chỉ có thể được tạo bởi native app (thiết bị GPS cố định) vì chỉ có nó mới có secret key
- ✅ **Client không thể fake**: Frontend/browser không có secret key nên không thể tự tạo token hợp lệ
- ✅ **Đảm bảo data từ thiết bị thực tế**: GPS coordinates chắc chắn đến từ thiết bị GPS cố định, không phải từ client

**3. Chống Replay Attack**
- ✅ **Expiration time (exp)**: Token có thời gian hết hạn ngắn, không thể dùng lại token cũ
- ✅ **Not-before time (nbf)**: Token chỉ có hiệu lực sau một thời điểm nhất định
- ✅ **Real-time data**: Đảm bảo GPS data là mới nhất, không phải data cũ được lưu lại

**4. Kiểm tra kết nối thực tế**
- ✅ **Phải kết nối wifi rạp**: Client phải kết nối với wifi của rạp để truy cập `http://wifi_ip:2552`
- ✅ **Không thể fake từ xa**: Không thể chấm công từ xa vì không thể truy cập được native app server
- ✅ **Xác thực vị trí vật lý**: Đảm bảo nhân viên thực sự ở rạp phim (đã kết nối wifi)

**5. Integrity và Non-repudiation**
- ✅ **Data integrity**: Đảm bảo GPS data không bị thay đổi trong quá trình truyền
- ✅ **Non-repudiation**: Không thể phủ nhận GPS data đã được gửi vì có signature
- ✅ **Audit trail**: Có thể log và audit GPS data với timestamp trong token

**6. So sánh với cách gửi trực tiếp**

| Cách gửi trực tiếp | Cách dùng JWT Token |
|-------------------|-------------------|
| ❌ Client có thể fake GPS | ✅ Client không thể fake (cần secret key) |
| ❌ Không verify được nguồn gốc | ✅ Verify được từ thiết bị GPS cố định |
| ❌ Có thể sửa đổi data | ✅ Không thể sửa (signature verify) |
| ❌ Có thể replay data cũ | ✅ Chống replay (exp, nbf) |
| ❌ Không có timestamp | ✅ Có timestamp trong token |

**Ví dụ tấn công bị ngăn chặn**:
```
Kịch bản tấn công: Nhân viên muốn chấm công từ nhà
1. ❌ Không thể: Phải kết nối wifi rạp để lấy token
2. ❌ Không thể: Không có secret key để tự tạo token
3. ❌ Không thể: Token cũ đã hết hạn, không dùng được
4. ❌ Không thể: Sửa GPS trong token → signature không khớp
```

#### 8.2.3. Token Validation
1. **Format Check**: Token phải có 3 phần được phân tách bởi dấu chấm
2. **Algorithm Check**: Header phải chỉ định `alg: HS256`
3. **Signature Verification**: Signature được tính bằng HMAC-SHA256 với secret key
4. **Expiration Check**: Token phải chưa hết hạn (`exp` claim)
5. **Not-Before Check**: Token phải đã có hiệu lực (`nbf` claim)

**Code Reference**:
```103:178:src/Services/Sc_ChamCong.php
public function decodeToken($token){
    // Tách token thành 3 phần: header.payload.signature
    $parts = explode('.', $token);
    
    if (count($parts) !== 3) {
        return [
            'error' => true,
            'message' => 'Invalid token format'
        ];
    }
    
    list($headerB64, $payloadB64, $signatureB64) = $parts;
    
    // Giải mã header và payload
    $header = $this->base64UrlDecode($headerB64);
    $payload = $this->base64UrlDecode($payloadB64);
    
    // Parse JSON
    $headerData = json_decode($header, true);
    $payloadData = json_decode($payload, true);
    
    if (!$headerData || !$payloadData) {
        return [
            'error' => true,
            'message' => 'Invalid JSON in token'
        ];
    }
    
    // Kiểm tra algorithm
    if (!isset($headerData['alg']) || $headerData['alg'] !== 'HS256') {
        return [
            'error' => true,
            'message' => 'Unsupported algorithm: ' . ($headerData['alg'] ?? 'none')
        ];
    }
    
    // Xác thực chữ ký
    $signature = $this->base64UrlDecode($signatureB64);
    $expectedSignature = $this->sign($headerB64 . '.' . $payloadB64);
    
    if (!hash_equals($signature, $expectedSignature)) {
        return [
            'error' => true,
            'message' => 'Invalid signature'
        ];
    }
    
    // Kiểm tra thời gian hết hạn
    if (isset($payloadData['exp'])) {
        if (time() > $payloadData['exp']) {
            return [
                'error' => true,
                'message' => 'Token expired',
                'expired_at' => date('Y-m-d H:i:s', $payloadData['exp'])
            ];
        }
    }
    
    // Kiểm tra thời gian bắt đầu có hiệu lực
    if (isset($payloadData['nbf'])) {
        if (time() + 2 < $payloadData['nbf']) {
            return [
                'error' => true,
                'message' => 'Token not yet valid',
                'valid_from' => date('Y-m-d H:i:s', $payloadData['nbf'])
            ];
        }
    }
    
    // Trả về payload đã được xác thực
    return [
        'error' => false,
        'data' => $payloadData,
        'header' => $headerData
    ];
}
```

### 8.3. GPS Location Verification

#### 8.3.1. Distance Check
- Khoảng cách tối đa cho phép: **100 mét**
- Algorithm: Haversine formula (tính khoảng cách trên bề mặt Trái Đất)
- Tọa độ rạp phim được lưu trong database (tọa độ đã cấu hình)
- Tọa độ từ thiết bị GPS cố định được lấy từ JWT token (đã được xác thực)
- **Lưu ý**: GPS là thiết bị cố định tại rạp phim do quản lý quản lý, không phải theo dõi vị trí nhân viên. Hệ thống kiểm tra xem thiết bị GPS cố định có ở đúng vị trí rạp phim không (trong phạm vi 100m)

**Code Reference**:
```282:284:src/Services/Sc_ChamCong.php
if ($khoangCach > 100) {
    throw new Exception('Kiểm tra kết nối wifi: '.$_POST['wifiTen'].'. Khoản cách không hợp lệ. Vui lòng liên hệ quản lý rạp để xử lý.');
}
```

#### 8.3.2. GPS Data Validation
- Kiểm tra `status === 'success'` trong GPS data
- Validate latitude và longitude là số hợp lệ
- Log khoảng cách để audit

### 8.4. Face Recognition Security

#### 8.4.1. Similarity Threshold
- **Threshold**: 0.6 (60% similarity)
- Chỉ chấp nhận khi cosine similarity >= 0.6
- Threshold có thể điều chỉnh trong `face.py`

#### 8.4.2. Video Quality Check
- Kiểm tra chất lượng video trước khi xử lý
- Reject video có:
  - Brightness < 50 hoặc > 230
  - Sharpness < 150
  - Noise > 100
  - Contrast < 10

#### 8.4.3. Single Face Requirement
- Frontend chỉ cho phép 1 khuôn mặt trong khung hình
- Nếu phát hiện nhiều khuôn mặt, từ chối và yêu cầu thử lại

### 8.5. Input Validation

#### 8.5.1. File Upload
- Chỉ chấp nhận file video
- Validate file size (tùy cấu hình PHP)
- Validate file type

#### 8.5.2. Business Logic Validation
- Không cho phép check-in 2 lần trong cùng ngày
- Phải check-in trước khi check-out
- Không cho phép check-out 2 lần trong cùng ngày
- Phải có bản ghi `phan_cong` trong ngày

---

## 9. XỬ LÝ LỖI VÀ LOGGING

### 9.1. Log Files

#### 9.1.1. Face Registration Log
**File**: `cache/log/face_register.log`

**Nội dung**:
- Timestamp
- Employee ID
- Python script output
- Success/Failure messages

#### 9.1.2. Face Check-in Log
**File**: `cache/log/face_checkin.log`

**Nội dung**:
- Timestamp
- Employee ID
- Loại chấm công (checkin/checkout)
- Khoảng cách GPS
- Tọa độ nhân viên và rạp phim
- Python script output
- Similarity score

#### 9.1.3. Face Check-out Log
**File**: `cache/log/face_checkout.log`

**Nội dung**: Tương tự check-in log

### 9.2. Error Handling

#### 9.2.1. PHP Error Handling

**Controller Layer**:
- Tất cả exceptions được catch trong Controller
- Trả về JSON response với format chuẩn:
  ```json
  {
      "success": false,
      "message": "Lỗi hệ thống: <error_message>"
  }
  ```

**Code Reference**:
```82:98:src/Controllers/Ctrl_ChamCong.php
public function chamCongKhuonMat(){
    $service = new Sc_ChamCong();
    try{
        $nhanVien = $service->chamCongKhuonMat();
        return [
            'success' => true,
            'message' => 'Chấm công thành công',
            'data' => $nhanVien
        ];
    }
    catch(\Exception $e){
        return [
            'success' => false,
            'message' => 'Lỗi hệ thống: ' . $e->getMessage()
        ];
    }
}
```

**Service Layer**:
- Validate input và throw Exception với message rõ ràng
- Log errors vào file log tương ứng
- Các loại lỗi thường gặp:
  - `Thiếu id nhân viên`: Session không hợp lệ
  - `Loại chấm công không hợp lệ`: Giá trị `loai` không đúng
  - `Bạn chưa đăng ký khuôn mặt`: Chưa có bản ghi trong `dangky_khuonmat`
  - `Thiếu token`: JWT token không được gửi
  - `Invalid token format`: Token không đúng format
  - `Token expired`: Token đã hết hạn
  - `Invalid signature`: Signature không khớp
  - `Không tìm thấy dữ liệu GPS`: GPS data không hợp lệ
  - `Hệ thống đang cập nhật wifi`: GPS status không phải 'success'
  - `Khoản cách không hợp lệ`: Khoảng cách > 100m
  - `Thiếu file video tải lên`: Không có file video
  - `Lỗi khi gọi xác thực khuôn mặt`: Python script lỗi
  - `Xác thực khuôn mặt thất bại`: Similarity < 0.6
  - `Bạn đã chấm công vào rồi`: Đã check-in trong ngày
  - `Bạn chưa chấm công vào`: Chưa check-in trước khi check-out
  - `Bạn đã chấm công ra rồi`: Đã check-out trong ngày
  - `Không tìm thấy bản ghi phân công`: Không có `phan_cong` trong ngày

#### 9.2.2. Python Error Handling

**Error Types**:
1. **File Not Found**: Video file không tồn tại
2. **Video Quality Failed**: Video không đạt chất lượng
3. **No Face Detected**: Không phát hiện khuôn mặt trong video
4. **No Stored Embedding**: Không tìm thấy embedding đã lưu
5. **ChromaDB Connection Error**: Không kết nối được ChromaDB
6. **Low Similarity**: Similarity score < threshold

**Error Output**:
- Tất cả errors được in ra stdout/stderr
- PHP capture output và ghi vào log file
- Format: `✗ <error_type> - <timestamp>`

**Code Reference**:
```303:348:bin/python/face.py
def check_face(video_path, id_employee):
    """
    Xác thực khuôn mặt từ video với khuôn mặt đã đăng ký
    Returns: True nếu khớp, False nếu không khớp
    """
    # Bước 1: Kiểm tra chất lượng video
    result_check_quality = check_quality(video_path)
    if not result_check_quality:
        print(f"\n✗ Face verification FAILED (low quality) - {datetime.datetime.now()}")
        return False
    
    # Bước 2: Lấy embedding đã lưu từ database
    embedding_stored = get_embedding(id_employee)
    if embedding_stored is None:
        print(f"\n✗ Face verification FAILED (no stored embedding for ID={id_employee}) - {datetime.datetime.now()}")
        return False
    
    # Bước 3: Tạo embedding từ video xác nhận
    embedding_confirm = faceToEmbedding(video_path)
    if embedding_confirm is None:
        print(f"\n✗ Face verification FAILED (cannot create embedding from video) - {datetime.datetime.now()}")
        return False
    
    # Bước 4: So sánh 2 embedding
    similarity = cosine_similarity(embedding_stored, embedding_confirm)
    
    # Bước 5: Đánh giá kết quả
    if similarity >= SIMILARITY_THRESHOLD:
        print(f"\n✓ Face verification SUCCESSFUL - Match confirmed! - {datetime.datetime.now()}")
        return True
    else:
        print(f"\n✗ Face verification FAILED - No match - {datetime.datetime.now()}")
        return False
```

#### 9.2.3. Frontend Error Handling

**Error Types**:
1. **Camera Access Denied**: Không có quyền truy cập camera
2. **GPS Server Connection Failed**: Không kết nối được native app server
3. **GPS Token Invalid**: Token không hợp lệ hoặc hết hạn
4. **Upload Failed**: Lỗi khi upload video
5. **Server Error**: Lỗi từ backend

**Error Display**:
- Sử dụng `alert()` để hiển thị lỗi cho user
- Log errors vào browser console
- Hiển thị loading state và disable buttons khi có lỗi

**Code Reference**:
```252:297:internal/js/cham-cong.js
async function getTokenApi(){
    const wifiIp = cameraSection.dataset.ip;
    const wifiTen = cameraSection.dataset.ten;
    
    if (!wifiIp) {
        throw new Error('Không tìm thấy thông tin IP wifi. Vui lòng liên hệ quản lý rạp.');
    }
    
    try {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 10000);
        
        const response = await fetch(`http://${wifiIp}:2552`, {
            signal: controller.signal,
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });
        
        clearTimeout(timeoutId);
        
        if (!response.ok) {
            throw new Error(`Hãy đảm bảo bạn đã kết nối tới wifi: ${wifiTen}\nNếu đã kết nối đúng tới wifi, hãy liên hệ quản lý rạp để xử lý.`);
        }
        
        const data = await response.json();
        if(data.status != 'success'){
            throw new Error(`Hệ thống đang cập nhật wifi vui lòng thử lại sau.`)
        }
        else {
            return data.token;
        }
    }
    catch (error) {
        if (error.name === 'AbortError') {
            throw new Error(`Vui lòng kết nối tới wifi ${wifiTen}. Kiểm tra kết nối mạng và thử lại.`);
        } else if (error.message.includes('Failed to fetch') || error.message.includes('NetworkError')) {
            throw new Error(`Không thể kết nối tới Server GPS.\nVui lòng đảm bảo:\n- Bạn đã kết nối đúng wifi\n- Router đang hoạt động bình thường\n- Liên hệ quản lý rạp nếu vấn đề vẫn tiếp tục.`);
        } else {
            throw new Error(`Lỗi hệ thống: ${error.message}`);
        }
    }
}
```

### 9.3. Log Rotation và Maintenance

#### 9.3.1. Log File Management
- Log files có thể phát triển lớn theo thời gian
- Khuyến nghị: Setup log rotation (cron job hoặc logrotate)
- Xóa log cũ định kỳ (giữ lại 30-90 ngày)

#### 9.3.2. Log Analysis
- Sử dụng `grep` hoặc log analysis tools để tìm patterns
- Ví dụ: Tìm tất cả failed check-ins:
  ```bash
  grep "FAILED" cache/log/face_checkin.log
  ```
- Phân tích similarity scores để điều chỉnh threshold

---

## 10. HƯỚNG DẪN TRIỂN KHAI

### 10.1. Yêu cầu hệ thống

#### 10.1.1. Server Requirements

**PHP**:
- PHP >= 7.4
- Extensions:
  - `php-mbstring`
  - `php-xml`
  - `php-curl`
  - `php-gd`
  - `php-mysql`
  - `php-session`

**Python**:
- Python >= 3.8
- Packages (requirements.txt):
  ```
  torch>=1.9.0
  torchvision>=0.10.0
  facenet-pytorch>=2.5.0
  opencv-python>=4.5.0
  numpy>=1.21.0
  chromadb>=0.4.0
  ```

**Database**:
- MySQL >= 5.7 hoặc MariaDB >= 10.3
- ChromaDB (HTTP server mode)

**Web Server**:
- Apache hoặc Nginx
- PHP-FPM (khuyến nghị)

#### 10.1.2. Client Requirements

**Browser**:
- Chrome/Edge >= 90
- Firefox >= 88
- Safari >= 14
- Hỗ trợ WebRTC và MediaRecorder API

**Hardware**:
- Camera (webcam hoặc built-in)
- GPS-enabled device (cho native app)

### 10.2. Cài đặt Backend

#### 10.2.1. Clone Repository và Install Dependencies

```bash
# Clone repository
git clone <repository_url>
cd epiccinema.code

# Install PHP dependencies
composer install

# Install Node.js dependencies (cho Tailwind CSS)
npm install
```

#### 10.2.2. Cấu hình Environment Variables

Tạo file `.env` từ `.env.example`:

```env
# Database
DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=epic
DB_USERNAME=admin_epic
DB_PASSWORD=epic2025

# Python
PYTHON_PATH=python3

# GPS JWT Secret
GPS_SECRET_KEY=your_secret_key_here_min_32_chars

# URLs
URL_WEB_BASE=https://your-domain.com
URL_INTERNAL_BASE=https://your-domain.com/internal
```

**Lưu ý**: `GPS_SECRET_KEY` phải giống với secret key trong native app.

#### 10.2.3. Cấu hình Database

```sql
-- Đảm bảo các bảng đã được tạo:
-- - phan_cong
-- - dangky_khuonmat
-- - nguoidung_noibo
-- - rapphim

-- Cập nhật tọa độ GPS cho rạp phim
UPDATE rapphim 
SET kinh_do = <longitude>, 
    vi_do = <latitude> 
WHERE id = <rap_phim_id>;
```

#### 10.2.4. Cài đặt Python Dependencies

```bash
# Tạo virtual environment (khuyến nghị)
python3 -m venv venv
source venv/bin/activate  # Linux/Mac
# hoặc
venv\Scripts\activate  # Windows

# Install packages
pip install torch torchvision
pip install facenet-pytorch
pip install opencv-python
pip install numpy
pip install chromadb
```

#### 10.2.5. Cấu hình ChromaDB

**Option 1: Standalone Server (Khuyến nghị cho production)**

```bash
# Install ChromaDB
pip install chromadb

# Start ChromaDB server
chroma run --host localhost --port 8000
```

**Option 2: In-process (Development)**

ChromaDB sẽ tự động tạo database khi chạy lần đầu.

#### 10.2.6. Cấu hình Web Server

**Apache (.htaccess)**:
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^api/(.*)$ api/index.php [L,QSA]
RewriteRule ^internal/(.*)$ internal/index.php [L,QSA]
```

**Nginx**:
```nginx
location /api/ {
    try_files $uri $uri/ /api/index.php?$query_string;
}

location /internal/ {
    try_files $uri $uri/ /internal/index.php?$query_string;
}
```

#### 10.2.7. Tạo thư mục Log

```bash
mkdir -p cache/log
chmod 755 cache/log
```

### 10.3. Cài đặt Native App (GPS Service)

**Yêu cầu**:
- Native app phải chạy HTTP server trên port 2552
- Server phải trả về JWT token với GPS data khi nhận GET request

**JWT Token Format**:
```json
{
  "header": {
    "alg": "HS256",
    "typ": "JWT"
  },
  "payload": {
    "status": "success",
    "latitude": 10.762622,
    "longitude": 106.660172,
    "accuracy": 10.5,
    "altitude": 5.2,
    "timestamp": 1705123456,
    "exp": 1705123556,
    "nbf": 1705123450
  }
}
```

**Secret Key**: Phải giống với `GPS_SECRET_KEY` trong `.env`

### 10.4. Cấu hình Wifi và Định vị

#### 10.4.1. Cấu hình Wifi cho Rạp phim

1. Đăng nhập vào trang quản lý định vị (Quản lý rạp)
2. Cập nhật thông tin:
   - **Wifi IP**: IP của router (ví dụ: 192.168.1.1)
   - **Wifi Tên**: Tên mạng wifi
3. Lưu cấu hình

#### 10.4.2. Cấu hình Tọa độ Rạp phim

1. Lấy tọa độ GPS của rạp phim (Google Maps)
2. Cập nhật trong database:
   ```sql
   UPDATE rapphim 
   SET kinh_do = <longitude>, 
       vi_do = <latitude> 
   WHERE id = <rap_phim_id>;
   ```

### 10.5. Build Frontend Assets

```bash
# Build CSS cho internal pages
npm run build:css:internal

# Build CSS cho customer pages (nếu cần)
npm run build:css:customer
```

### 10.6. Kiểm tra Triển khai

#### 10.6.1. Test Checklist

- [ ] PHP dependencies đã cài đặt
- [ ] Python dependencies đã cài đặt
- [ ] ChromaDB đang chạy
- [ ] Database kết nối thành công
- [ ] Environment variables đã cấu hình
- [ ] Log directories có quyền ghi
- [ ] Native app server đang chạy trên port 2552
- [ ] Wifi IP và tên đã cấu hình
- [ ] Tọa độ rạp phim đã cập nhật
- [ ] Frontend assets đã build

#### 10.6.2. Test Flow

1. **Test Đăng ký khuôn mặt**:
   - Đăng nhập với tài khoản nhân viên
   - Truy cập `/dang-ky-khuon-mat`
   - Quay video và đăng ký
   - Kiểm tra log: `cache/log/face_register.log`

2. **Test Chấm công**:
   - Kết nối wifi của rạp phim
   - Truy cập `/cham-cong`
   - Click "Check In"
   - Kiểm tra database: `phan_cong.gio_vao` đã được cập nhật
   - Kiểm tra log: `cache/log/face_checkin.log`

3. **Test GPS Verification**:
   - Thử chấm công khi không kết nối wifi → Phải báo lỗi
   - Thử chấm công khi ở xa rạp (>100m) → Phải báo lỗi

---

## 11. TROUBLESHOOTING

### 11.1. Lỗi thường gặp và Giải pháp

#### 11.1.1. "Không xác định được nhân viên"

**Nguyên nhân**: Session không hợp lệ hoặc chưa đăng nhập

**Giải pháp**:
- Kiểm tra đã đăng nhập chưa
- Clear browser cookies và đăng nhập lại
- Kiểm tra session configuration trong PHP

#### 11.1.2. "Bạn chưa đăng ký khuôn mặt"

**Nguyên nhân**: Chưa đăng ký khuôn mặt hoặc trạng thái không đúng

**Giải pháp**:
- Đăng ký khuôn mặt tại `/dang-ky-khuon-mat`
- Kiểm tra bảng `dangky_khuonmat`:
  ```sql
  SELECT * FROM dangky_khuonmat WHERE id_nhanvien = <id>;
  ```
- Đảm bảo `trang_thai = 'Đang hoạt động'`

#### 11.1.3. "Lỗi khi gọi xác thực khuôn mặt"

**Nguyên nhân**: Python script lỗi

**Giải pháp**:
1. Kiểm tra log file:
   ```bash
   tail -f cache/log/face_checkin.log
   ```
2. Kiểm tra Python dependencies:
   ```bash
   python3 -c "import torch; import facenet_pytorch; import cv2; import chromadb"
   ```
3. Kiểm tra ChromaDB đang chạy:
   ```bash
   curl http://localhost:8000/api/v1/heartbeat
   ```
4. Kiểm tra quyền thực thi:
   ```bash
   chmod +x bin/python/face.py
   ```

#### 11.1.4. "Không tìm thấy dữ liệu GPS"

**Nguyên nhân**: JWT token không hợp lệ hoặc GPS data không đúng format

**Giải pháp**:
1. Kiểm tra native app server đang chạy:
   ```bash
   curl http://<wifi_ip>:2552
   ```
2. Kiểm tra JWT secret key khớp giữa native app và backend
3. Kiểm tra log để xem payload:
   ```php
   error_log('GPS Data parse failed. Payload: ' . json_encode($payload));
   ```

#### 11.1.5. "Khoản cách không hợp lệ"

**Nguyên nhân**: Nhân viên ở xa rạp phim (>100m)

**Giải pháp**:
1. Kiểm tra đã kết nối đúng wifi chưa
2. Kiểm tra tọa độ rạp phim trong database:
   ```sql
   SELECT kinh_do, vi_do FROM rapphim WHERE id = <id>;
   ```
3. Kiểm tra log để xem khoảng cách thực tế:
   ```bash
   tail -f cache/log/face_checkin.log | grep "Khoảng cách"
   ```
4. Nếu cần, điều chỉnh bán kính trong code (mặc định 100m)

#### 11.1.6. "Chất lượng video không đạt yêu cầu"

**Nguyên nhân**: Video quá tối, mờ, hoặc nhiều noise

**Giải pháp**:
- Quay video ở nơi có ánh sáng tốt
- Đảm bảo camera sạch sẽ
- Giữ camera ổn định khi quay
- Kiểm tra log để xem metrics:
  ```bash
  grep "Brightness\|Sharpness\|Noise\|Contrast" cache/log/face_checkin.log
  ```

#### 11.1.7. "Khuôn mặt không khớp"

**Nguyên nhân**: Similarity score < 0.6

**Giải pháp**:
1. Kiểm tra similarity score trong log:
   ```bash
   grep "SIMILARITY SCORE" cache/log/face_checkin.log
   ```
2. Đăng ký lại khuôn mặt nếu cần
3. Điều chỉnh threshold trong `face.py` (không khuyến nghị)

#### 11.1.8. "Không thể kết nối tới Server GPS"

**Nguyên nhân**: Native app server không chạy hoặc không truy cập được

**Giải pháp**:
1. Kiểm tra native app đang chạy:
   - Xem process list
   - Kiểm tra port 2552 đang listen
2. Kiểm tra firewall:
   ```bash
   # Linux
   sudo ufw allow 2552
   ```
3. Kiểm tra network:
   - Ping wifi IP
   - Kiểm tra đã kết nối đúng wifi chưa
4. Kiểm tra CORS (nếu cần)

#### 11.1.9. "Không tìm thấy bản ghi phân công hiện tại"

**Nguyên nhân**: Chưa có bản ghi `phan_cong` trong ngày

**Giải pháp**:
- Tạo bản ghi phân công cho nhân viên trong ngày
- Kiểm tra quy trình tạo phân công tự động

#### 11.1.10. ChromaDB Connection Error

**Nguyên nhân**: ChromaDB server không chạy hoặc không kết nối được

**Giải pháp**:
1. Kiểm tra ChromaDB đang chạy:
   ```bash
   curl http://localhost:8000/api/v1/heartbeat
   ```
2. Khởi động lại ChromaDB:
   ```bash
   chroma run --host localhost --port 8000
   ```
3. Kiểm tra firewall cho port 8000

### 11.2. Performance Issues

#### 11.2.1. Chấm công chậm

**Nguyên nhân**:
- Video quá lớn
- Python script xử lý chậm
- ChromaDB query chậm
- Network latency

**Giải pháp**:
1. Giảm độ dài video (hiện tại 3 giây)
2. Tối ưu Python script:
   - Giảm số frames xử lý
   - Sử dụng GPU nếu có
3. Tối ưu ChromaDB:
   - Sử dụng persistent storage
   - Tăng memory limit
4. Kiểm tra network latency

#### 11.2.2. Memory Issues

**Nguyên nhân**: Video files hoặc embeddings quá lớn

**Giải pháp**:
- Tăng PHP memory limit:
  ```ini
  memory_limit = 256M
  ```
- Cleanup temp files sau khi xử lý
- Giảm video resolution nếu cần

### 11.3. Debug Mode

#### 11.3.1. Enable Debug Logging

**PHP**:
```php
// Trong Sc_ChamCong.php
error_log('Debug: ' . json_encode($data));
```

**Python**:
```python
# Trong face.py
import logging
logging.basicConfig(level=logging.DEBUG)
```

#### 11.3.2. Test Python Script Manually

```bash
# Test đăng ký
python3 bin/python/face.py /path/to/video.webm 123 register

# Test xác thực
python3 bin/python/face.py /path/to/video.webm 123 check
```

#### 11.3.3. Test JWT Token

```bash
# Decode token (không verify)
echo "<token>" | cut -d. -f2 | base64 -d | jq

# Test với curl
curl -X POST http://localhost/api/cham-cong/cham-cong \
  -F "video=@test.webm" \
  -F "loai=checkin" \
  -F "token=<jwt_token>"
```

### 11.4. Monitoring và Maintenance

#### 11.4.1. Health Checks

**ChromaDB**:
```bash
curl http://localhost:8000/api/v1/heartbeat
```

**Database**:
```sql
SELECT COUNT(*) FROM dangky_khuonmat WHERE trang_thai = 'Đang hoạt động';
SELECT COUNT(*) FROM phan_cong WHERE ngay = CURDATE();
```

#### 11.4.2. Log Monitoring

```bash
# Monitor check-in logs
tail -f cache/log/face_checkin.log

# Count failed check-ins today
grep "FAILED" cache/log/face_checkin.log | grep "$(date +%Y-%m-%d)" | wc -l

# Find high similarity scores
grep "SIMILARITY SCORE" cache/log/face_checkin.log | awk '{print $NF}' | sort -n
```

#### 11.4.3. Database Maintenance

```sql
-- Xóa bản ghi đăng ký cũ (nhân viên đã nghỉ)
DELETE dk FROM dangky_khuonmat dk
LEFT JOIN nguoidung_noibo nv ON dk.id_nhanvien = nv.id
WHERE nv.trang_thai = -1;

-- Backup trước khi xóa
CREATE TABLE dangky_khuonmat_backup AS SELECT * FROM dangky_khuonmat;
```

---

## 12. PHỤ LỤC

### 12.1. Glossary

- **Embedding**: Vector đại diện cho khuôn mặt (512 dimensions)
- **Similarity Score**: Độ tương đồng giữa 2 embeddings (0-1)
- **Threshold**: Ngưỡng tối thiểu để chấp nhận match (0.6)
- **Haversine Formula**: Công thức tính khoảng cách giữa 2 tọa độ GPS
- **JWT**: JSON Web Token - chuẩn xác thực
- **MTCNN**: Multi-task CNN - model phát hiện khuôn mặt
- **FaceNet**: Model nhận diện khuôn mặt (InceptionResnetV1)

### 12.2. References

- [MediaPipe Face Detection](https://developers.google.com/mediapipe/solutions/vision/face_detector)
- [FaceNet Paper](https://arxiv.org/abs/1503.03832)
- [ChromaDB Documentation](https://docs.trychroma.com/)
- [JWT Specification](https://tools.ietf.org/html/rfc7519)
- [Haversine Formula](https://en.wikipedia.org/wiki/Haversine_formula)

### 12.3. Changelog

**Version 1.0** (2024-01-15):
- Initial release
- Face registration
- Check-in/Check-out
- GPS verification
- History tracking

---

**Tài liệu này được cập nhật lần cuối**: 2024-01-15
**Phiên bản**: 1.0
**Tác giả**: Development Team