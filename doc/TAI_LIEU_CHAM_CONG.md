# TÀI LIỆU CÔNG NGHỆ - HỆ THỐNG CHẤM CÔNG KHUÔN MẶT & XÁC THỰC MẠNG NỘI BỘ

## MỤC LỤC

1.  [Kiến trúc hệ thống](#1-kiến-trúc-hệ-thống)
2.  [Stack công nghệ & Thư viện](#2-stack-công-nghệ--thư-viện)
3.  [Cơ sở dữ liệu & Models](#3-cơ-sở-dữ-liệu--models)
4.  [Cơ chế bảo mật kép (WiFi + GPS)](#4-cơ-chế-bảo-mật-kép-wifi--gps)
5.  [Luồng xử lý chấm công](#5-luồng-xử-lý-chấm-công)
6.  [Chi tiết kỹ thuật AI/ML](#6-chi-tiết-kỹ-thuật-aiml)
7.  [Cấu hình hạ tầng](#7-cấu-hình-hạ-tầng)
8.  [Các kịch bản chống gian lận](#8-các-kịch-bản-chống-gian-lận)

-----

## 1. KIẾN TRÚC HỆ THỐNG

### 1.1. Sơ đồ kiến trúc tổng quan

```mermaid
graph TB
    subgraph "CLIENT LAYER - Nhân viên"
        A[Browser/Mobile<br/>HTML5 + JavaScript]
        A1[MediaRecorder API<br/>Camera Access]
        A2[MediaPipe FaceDetector<br/>Real-time Detection]
    end
    
    subgraph "NATIVE APP LAYER - Ứng dụng Native"
        B[React Native App<br/>iOS/Android]
        B1[Camera Module]
        B2[GPS Module]
    end
    
    subgraph "LOCAL NETWORK LAYER - Mạng LAN Rạp"
        C[WiFi Router<br/>192.168.x.x]
        D[Anchor Device<br/>GPS Server :2552]
        D1[JWT Token Generator<br/>HMAC-SHA256]
    end
    
    subgraph "API GATEWAY LAYER - Internet"
        E[NGINX Reverse Proxy<br/>SSL/TLS]
        F[Load Balancer]
    end
    
    subgraph "BUSINESS LOGIC LAYER - Backend"
        G[PHP 8.1 Backend<br/>Laravel Framework]
        G1[Sc_ChamCong Service]
        G2[JWT Validator]
        G3[GPS Distance Calculator<br/>Haversine Formula]
    end
    
    subgraph "AI/ML PROCESSING LAYER"
        H[Python 3.10 AI Engine]
        H1[Quality Check Module<br/>Brightness/Sharpness/Noise]
        H2[Anti-Spoofing Engine<br/>MiniFASNetV2]
        H3[Face Recognition<br/>FaceNet InceptionResnetV1]
    end
    
    subgraph "DATA LAYER - Cơ sở dữ liệu"
        I[(MySQL 8.0<br/>Relational DB)]
        J[(ChromaDB<br/>Vector Database)]
        K[Redis Cache<br/>Session & Realtime]
    end
    
    subgraph "STORAGE LAYER"
        L[AWS S3/MinIO<br/>Video Storage]
        M[Local Filesystem<br/>Logs & Temp Files]
    end
    
    A --> A1 --> A2
    B --> B1 --> B2
    A2 --> C
    B2 --> C
    C --> D --> D1
    D1 --> E
    E --> F --> G
    G --> G1 --> G2 --> G3
    G3 --> H
    H --> H1 --> H2 --> H3
    H3 --> J
    G --> I
    G --> K
    H --> L
    H --> M
    
    style A fill:#e1f5ff
    style B fill:#e1f5ff
    style D fill:#fff4e1
    style G fill:#f0f0f0
    style H fill:#ffe1e1
    style I fill:#e1ffe1
    style J fill:#e1ffe1
```

### 1.2. Kiến trúc phân tầng (Layered Architecture)

```mermaid
graph LR
    subgraph "Presentation Layer"
        UI[Web UI<br/>Blade Templates]
        JS[JavaScript Modules<br/>cham-cong.js]
    end
    
    subgraph "API Layer"
        REST[RESTful API<br/>routes/internal.php]
        CTRL[Controllers<br/>C_ChamCong]
    end
    
    subgraph "Business Layer"
        SVC[Services<br/>Sc_ChamCong]
        VALID[Validators<br/>JWT/GPS/Quality]
    end
    
    subgraph "AI/ML Layer"
        PY[Python Scripts<br/>face.py]
        ML[ML Models<br/>MiniFASNet/FaceNet]
    end
    
    subgraph "Data Access Layer"
        ORM[Eloquent ORM]
        CHROMA[ChromaDB Client]
    end
    
    subgraph "Database Layer"
        SQL[(MySQL)]
        VECTOR[(ChromaDB)]
    end
    
    UI --> JS --> REST --> CTRL --> SVC --> VALID
    SVC --> PY --> ML
    SVC --> ORM --> SQL
    PY --> CHROMA --> VECTOR
    
    style UI fill:#4a90e2
    style SVC fill:#f39c12
    style PY fill:#e74c3c
    style SQL fill:#27ae60
```

## 2. STACK CÔNG NGHỆ & THƯ VIỆN

### 2.1. Frontend Stack

| Thành phần | Công nghệ | Phiên bản | Mục đích |
|------------|-----------|-----------|----------|
| **Framework UI** | Blade Templates (Laravel) | 10.x | Server-side rendering |
| **CSS Framework** | Tailwind CSS | 3.4.x | Responsive design & styling |
| **JavaScript** | Vanilla ES6+ | - | Client-side logic |
| **Face Detection** | MediaPipe Face Detector | 0.10.0 | Real-time face detection trong browser |
| **Video Recording** | MediaRecorder API | Native HTML5 | Record 3s video WebM |
| **HTTP Client** | Fetch API | Native | API communication |

**Dependencies Frontend:**
```javascript
// CDN Libraries
- @mediapipe/tasks-vision v0.10.0
  → Face detection model (blaze_face_short_range)
  → WASM-based, chạy trên client
- FilesetResolver
  → Load MediaPipe models
```

### 2.2. Backend Stack (PHP)

| Thành phần | Công nghệ | Phiên bản | Vai trò |
|------------|-----------|-----------|---------|
| **Framework** | Laravel | 10.x | MVC framework |
| **Web Server** | NGINX + PHP-FPM | 1.24 + 8.1 | HTTP server |
| **ORM** | Eloquent ORM | 10.x | Database abstraction |
| **JWT Handler** | Custom implementation | - | Token validation (HMAC-SHA256) |
| **GPS Calculator** | Haversine Formula | - | Distance calculation |

**PHP Libraries:**
```json
{
  "require": {
    "php": "^8.1",
    "laravel/framework": "^10.0",
    "illuminate/database": "^10.0",
    "vlucas/phpdotenv": "^5.5"
  }
}
```

### 2.3. AI/ML Stack (Python)

| Thành phần | Công nghệ | Phiên bản | Chức năng |
|------------|-----------|-----------|-----------|
| **Runtime** | Python | 3.10+ | AI script execution |
| **Computer Vision** | OpenCV (cv2) | 4.8.x | Video processing |
| **Deep Learning** | PyTorch | 2.1.x | Neural network inference |
| **Face Recognition** | facenet-pytorch | 2.5.3 | MTCNN + InceptionResnetV1 |
| **Anti-Spoofing** | MiniFASNetV2 | Custom | Liveness detection |
| **Vector DB** | ChromaDB | 0.4.x | Face embedding storage |
| **Numeric Computing** | NumPy | 1.24.x | Array operations |

**Python Dependencies:**
```python
# requirements.txt
torch==2.1.0
torchvision==0.16.0
opencv-python==4.8.1.78
facenet-pytorch==2.5.3
chromadb==0.4.18
numpy==1.24.3
Pillow==10.1.0
```

**AI Models Chi Tiết:**

1. **MTCNN (Multi-task Cascaded Convolutional Networks)**
   - **Tác giả**: Zhang et al. (2016)
   - **Mục đích**: Face detection & alignment
   - **Output**: Bounding box + 5 facial landmarks
   - **Size**: ~2MB
   - **Inference time**: ~50ms/frame

2. **InceptionResnetV1 (FaceNet)**
   - **Pretrained on**: VGGFace2 dataset (3.31M images)
   - **Architecture**: Inception + ResNet hybrid
   - **Embedding size**: 512 dimensions
   - **Similarity metric**: Cosine similarity
   - **Threshold**: 0.6 (adjustable)

3. **MiniFASNetV2 (Anti-Spoofing)**
   - **Model file**: `2.7_80x80_MiniFASNetV2.pth`
   - **Input size**: 80x80 RGB
   - **Scale factor**: 2.7 (crop around face)
   - **Output**: 3 classes (Real/Fake/Unknown)
   - **Liveness threshold**: 0.85
   - **Variance threshold**: 0.002 (detect static images)

### 2.4. Database Stack

| Database | Loại | Phiên bản | Vai trò |
|----------|------|-----------|---------|
| **MySQL** | Relational DB | 8.0 | Dữ liệu nghiệp vụ |
| **ChromaDB** | Vector DB | 0.4.x | Face embeddings |
| **Redis** | In-memory Cache | 7.0 | Session & realtime data |

### 2.5. Infrastructure Stack

```mermaid
graph TB
    subgraph "Production Environment"
        LB[Load Balancer<br/>NGINX]
        WEB1[Web Server 1<br/>PHP-FPM 8.1]
        WEB2[Web Server 2<br/>PHP-FPM 8.1]
        AI[AI Server<br/>Python 3.10 + GPU]
    end
    
    subgraph "Data Tier"
        DB[(MySQL Master<br/>8.0)]
        DBSLAVE[(MySQL Slave<br/>Read Replica)]
        CHROMA[(ChromaDB<br/>HTTP Server :8000)]
        REDIS[(Redis<br/>:6379)]
    end
    
    subgraph "Storage Tier"
        S3[MinIO S3<br/>Video Storage]
        NFS[NFS Share<br/>Logs & Temp]
    end
    
    LB --> WEB1
    LB --> WEB2
    WEB1 --> DB
    WEB2 --> DB
    WEB1 --> DBSLAVE
    WEB2 --> DBSLAVE
    WEB1 --> AI
    WEB2 --> AI
    AI --> CHROMA
    WEB1 --> REDIS
    WEB2 --> REDIS
    AI --> S3
    AI --> NFS
    
    style LB fill:#3498db
    style AI fill:#e74c3c
    style DB fill:#27ae60
    style CHROMA fill:#9b59b6
```

## 3. CƠ SỞ DỮ LIỆU & MODELS

### 3.1. MySQL Schema (Relational Database)

```mermaid
erDiagram
    NGUOI_DUNG_INTERNAL ||--o{ PHAN_CONG : "được phân công"
    NGUOI_DUNG_INTERNAL ||--o| DANG_KY_KHUON_MAT : "đăng ký"
    RAP_PHIM ||--o{ PHAN_CONG : "có"
    RAP_PHIM ||--o{ DINH_VI : "có thiết bị"
    
    NGUOI_DUNG_INTERNAL {
        int ID PK
        string HoTen
        string Email
        string MatKhau
        int ID_RapPhim FK
        datetime created_at
        datetime updated_at
    }
    
    PHAN_CONG {
        int id PK
        int id_nhanvien FK
        int id_rapphim FK
        date ngay
        string ca
        time gio_vao
        time gio_ra
        int trang_thai
        datetime created_at
        datetime updated_at
    }
    
    DANG_KY_KHUON_MAT {
        int id PK
        int id_nhanvien FK
        datetime ngay_dang_ky
        string trang_thai
        datetime created_at
        datetime updated_at
    }
    
    RAP_PHIM {
        int ID PK
        string TenRap
        string DiaChi
        float kinh_do
        float vi_do
        datetime created_at
        datetime updated_at
    }
    
    DINH_VI {
        int id PK
        int id_rapphim FK
        string wifi_ten
        string wifi_ip
        string device_id
        datetime created_at
        datetime updated_at
    }
```

### 3.2. ChromaDB Collections (Vector Database)

```mermaid
graph LR
    subgraph "ChromaDB HTTP Server :8000"
        COLL[Collection: face_embeddings]
    end
    
    subgraph "Document Structure"
        DOC1["ID: face_1<br/>Embedding: [512 dims]<br/>Metadata: {id_employee, timestamps}"]
        DOC2["ID: face_2<br/>Embedding: [512 dims]"]
        DOC3["ID: face_N<br/>Embedding: [512 dims]"]
    end
    
    COLL --> DOC1
    COLL --> DOC2
    COLL --> DOC3
    
    style COLL fill:#9b59b6
    style DOC1 fill:#ecf0f1
```

**ChromaDB Collection Config:**
```python
collection = client.get_or_create_collection(
    name="face_embeddings",
    metadata={"hnsw:space": "cosine"}  # Cosine similarity
)

# Document format:
{
    "id": "face_123",
    "embedding": [0.123, -0.456, ...],  # 512 float values
    "metadata": {
        "id_employee": "123",
        "created_at": "2025-12-06T10:30:00",
        "updated_at": "2025-12-06T10:30:00"
    }
}
```

### 3.3. Laravel Models (Eloquent ORM)

```php
// app/Models/PhanCong.php
class PhanCong extends Model {
    protected $table = 'phan_cong';
    protected $fillable = [
        'id_nhanvien', 'id_rapphim', 'ngay', 'ca',
        'gio_vao', 'gio_ra', 'trang_thai'
    ];
    
    // Relationships
    public function nhanVien() {
        return $this->belongsTo(NguoiDungInternal::class, 'id_nhanvien');
    }
    
    public function rapPhim() {
        return $this->belongsTo(RapPhim::class, 'id_rapphim');
    }
}

// app/Models/DangKyKhuonMat.php
class DangKyKhuonMat extends Model {
    protected $table = 'dang_ky_khuon_mat';
    protected $fillable = ['id_nhanvien', 'ngay_dang_ky', 'trang_thai'];
    
    const TRANG_THAI_HOAT_DONG = 'Đang hoạt động';
    const TRANG_THAI_VO_HIEU = 'Vô hiệu hóa';
}

// app/Models/RapPhim.php
class RapPhim extends Model {
    protected $table = 'rap_phim';
    protected $fillable = ['TenRap', 'DiaChi', 'kinh_do', 'vi_do'];
    
    public function dinhVi() {
        return $this->hasOne(DinhVi::class, 'id_rapphim');
    }
}
```

### 3.4. Data Flow Diagram

```mermaid
sequenceDiagram
    participant UI as Frontend
    participant PHP as Backend PHP
    participant MYSQL as MySQL
    participant PY as Python AI
    participant CHROMA as ChromaDB
    
    Note over UI,CHROMA: Registration Flow
    UI->>PHP: POST /dang-ky-khuon-mat (video)
    PHP->>PY: exec face.py register
    PY->>PY: Quality Check + Liveness
    PY->>PY: FaceNet Extract Embedding
    PY->>CHROMA: Save embedding (face_123)
    CHROMA-->>PY: Success
    PY-->>PHP: ✓ Registration SUCCESSFUL
    PHP->>MYSQL: INSERT dang_ky_khuon_mat
    PHP-->>UI: {success: true}
    
    Note over UI,CHROMA: Attendance Check-in Flow
    UI->>PHP: POST /cham-cong (video + token)
    PHP->>PHP: Validate JWT Token
    PHP->>PHP: Calculate GPS Distance
    PHP->>PY: exec face.py check
    PY->>CHROMA: GET embedding (face_123)
    CHROMA-->>PY: [512 dims vector]
    PY->>PY: Liveness + Quality Check
    PY->>PY: Extract + Compare Embeddings
    PY-->>PHP: ✓ Verification SUCCESSFUL
    PHP->>MYSQL: UPDATE phan_cong SET gio_vao
    PHP-->>UI: {success: true, message: "Chấm công thành công"}
```

## 4. CƠ CHẾ BẢO MẬT KÉP (WIFI + GPS)

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

## 5. LUỒNG XỬ LÝ CHẤM CÔNG

### 5.1. Sơ đồ luồng dữ liệu tổng quan

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

### 5.2. Luồng xử lý chi tiết (Step by Step)

```mermaid
flowchart TD
    START([Nhân viên mở trang chấm công]) --> CHECK_WIFI{Kết nối WiFi Rạp?}
    CHECK_WIFI -->|Không| ERROR1[❌ Lỗi: Vui lòng kết nối WiFi]
    CHECK_WIFI -->|Có| CALL_DEVICE[Gọi http://192.168.x.x:2552]
    
    CALL_DEVICE --> DEVICE_RESP{Thiết bị phản hồi?}
    DEVICE_RESP -->|Timeout| ERROR2[❌ Thiết bị định vị không hoạt động]
    DEVICE_RESP -->|OK| GET_TOKEN[Nhận JWT Token chứa GPS]
    
    GET_TOKEN --> CAMERA[Khởi động Camera]
    CAMERA --> FACE_DETECT[MediaPipe: Detect Face]
    
    FACE_DETECT --> FACE_CHECK{Phát hiện khuôn mặt?}
    FACE_CHECK -->|Không| WAIT[Chờ khuôn mặt xuất hiện]
    WAIT --> FACE_DETECT
    FACE_CHECK -->|Có 1 mặt| RECORD[Record 3s video WebM]
    FACE_CHECK -->|>1 mặt| ERROR3[❌ Phát hiện nhiều khuôn mặt]
    
    RECORD --> UPLOAD[Upload video + token]
    UPLOAD --> BACKEND[Backend PHP nhận request]
    
    BACKEND --> VALIDATE_TOKEN{Validate JWT?}
    VALIDATE_TOKEN -->|Sai| ERROR4[❌ Token không hợp lệ]
    VALIDATE_TOKEN -->|Đúng| DECODE_GPS[Decode GPS từ Token]
    
    DECODE_GPS --> CALC_DIST[Tính khoảng cách Haversine]
    CALC_DIST --> CHECK_DIST{Khoảng cách < 100m?}
    CHECK_DIST -->|Không| ERROR5[❌ Vị trí không hợp lệ]
    CHECK_DIST -->|Có| CALL_AI[Gọi Python AI Engine]
    
    CALL_AI --> QUALITY[Quality Check Module]
    QUALITY --> QUALITY_OK{Chất lượng OK?}
    QUALITY_OK -->|Không| ERROR6[❌ Video chất lượng thấp]
    QUALITY_OK -->|Có| LIVENESS[Anti-Spoofing Check]
    
    LIVENESS --> VARIANCE[Tính Score Variance]
    VARIANCE --> VAR_CHECK{Variance >= 0.002?}
    VAR_CHECK -->|Không| ERROR7[❌ Phát hiện ảnh giả<br/>Static image detected]
    VAR_CHECK -->|Có| SCORE_CHECK{Mean Score >= 0.85?}
    SCORE_CHECK -->|Không| ERROR8[❌ Liveness Failed]
    SCORE_CHECK -->|Có| EXTRACT[Extract Face Embedding]
    
    EXTRACT --> GET_STORED[ChromaDB: Get Stored Embedding]
    GET_STORED --> COMPARE[Cosine Similarity]
    COMPARE --> SIMILARITY{Similarity >= 0.6?}
    SIMILARITY -->|Không| ERROR9[❌ Khuôn mặt không khớp]
    SIMILARITY -->|Có| UPDATE_DB[MySQL: UPDATE gio_vao/gio_ra]
    
    UPDATE_DB --> SUCCESS([✅ Chấm công thành công])
    
    style START fill:#4a90e2,color:#fff
    style SUCCESS fill:#27ae60,color:#fff
    style ERROR1 fill:#e74c3c,color:#fff
    style ERROR2 fill:#e74c3c,color:#fff
    style ERROR3 fill:#e74c3c,color:#fff
    style ERROR4 fill:#e74c3c,color:#fff
    style ERROR5 fill:#e74c3c,color:#fff
    style ERROR6 fill:#e74c3c,color:#fff
    style ERROR7 fill:#e74c3c,color:#fff
    style ERROR8 fill:#e74c3c,color:#fff
    style ERROR9 fill:#e74c3c,color:#fff
    style LIVENESS fill:#f39c12
    style EXTRACT fill:#9b59b6
```

-----

## 6. CHI TIẾT KỸ THUẬT AI/ML

### 6.1. Pipeline xử lý video (Python)

```mermaid
graph LR
    subgraph "Video Input"
        VID[video.webm<br/>3 seconds<br/>1280x720]
    end
    
    subgraph "Quality Analysis"
        Q1[Frame Sampling<br/>Every 5th frame]
        Q2[Brightness Check<br/>50-230]
        Q3[Sharpness Check<br/>Laplacian >= 25]
        Q4[Noise Check<br/>StdDev <= 100]
        Q5[Contrast Check<br/>>= 10]
    end
    
    subgraph "Anti-Spoofing Pipeline"
        AS1[MTCNN Face Detection]
        AS2[Scale Crop 2.7x]
        AS3[Resize to 80x80]
        AS4[Normalize /255.0]
        AS5[MiniFASNet Inference]
        AS6[Check 10 frames]
        AS7[Calculate Variance]
        AS8[Score >= 0.85?]
    end
    
    subgraph "Face Recognition"
        FR1[MTCNN Detect & Align]
        FR2[Resize to 160x160]
        FR3[InceptionResnetV1]
        FR4[512-dim Embedding]
        FR5[L2 Normalization]
        FR6[Average Embeddings]
    end
    
    subgraph "Verification"
        V1[Get Stored Embedding<br/>from ChromaDB]
        V2[Cosine Similarity]
        V3[Threshold 0.6]
    end
    
    VID --> Q1 --> Q2 --> Q3 --> Q4 --> Q5
    Q5 --> AS1 --> AS2 --> AS3 --> AS4 --> AS5
    AS5 --> AS6 --> AS7 --> AS8
    AS8 --> FR1 --> FR2 --> FR3 --> FR4 --> FR5 --> FR6
    FR6 --> V1 --> V2 --> V3
    
    style VID fill:#3498db
    style AS5 fill:#e74c3c
    style FR3 fill:#9b59b6
    style V2 fill:#27ae60
```

### 6.2. MiniFASNet Architecture

```mermaid
graph TB
    INPUT[Input: 80x80x3 RGB] --> CONV1[Conv2d_cd 3→32<br/>stride=2]
    CONV1 --> CONV2[DepthwiseConv 32→32<br/>groups=32]
    CONV2 --> CONV23[Conv2d_cd 32→64<br/>stride=2]
    CONV23 --> CONV3[DepthwiseConv 64→64<br/>groups=64]
    CONV3 --> CONV34[Conv2d_cd 64→128<br/>stride=2]
    CONV34 --> CONV4[DepthwiseConv 128→128<br/>groups=128]
    CONV4 --> CONV45[Conv2d_cd 128→128<br/>stride=2]
    CONV45 --> CONV5[DepthwiseConv 128→128<br/>groups=128]
    CONV5 --> CONV6_SEP[Conv2d 128→512<br/>1x1]
    CONV6_SEP --> CONV6_DW[DepthwiseConv 512→512<br/>5x5, groups=512]
    CONV6_DW --> FLATTEN[Flatten]
    FLATTEN --> LINEAR1[Linear 512→128]
    LINEAR1 --> BN[BatchNorm1d]
    BN --> DROP[Dropout 0.4]
    DROP --> LINEAR2[Linear 128→3]
    LINEAR2 --> SOFT[Softmax]
    SOFT --> OUTPUT[Output: [Fake, Real, Unknown]]
    
    style INPUT fill:#3498db,color:#fff
    style OUTPUT fill:#27ae60,color:#fff
    style CONV6_DW fill:#e74c3c,color:#fff
```

### 6.3. Anti-Spoofing Detection Logic

```python
# Pseudo-code logic
def check_liveness_video(video_path):
    all_scores = []
    real_votes = 0
    fake_votes = 0
    
    for frame in video.frames[:10]:
        # MTCNN face detection
        face_box = mtcnn.detect(frame)
        
        # Scale crop 2.7x
        face_crop = crop_with_scale(frame, face_box, scale=2.7)
        
        # Preprocess
        img = resize(face_crop, (80, 80))
        img = normalize(img)  # /255.0
        
        # MiniFASNet inference
        prediction = liveness_model(img)
        real_score = prediction[1]  # Index 1 = Real class
        all_scores.append(real_score)
        
        if real_score > THRESHOLD:
            real_votes += 1
        else:
            fake_votes += 1
    
    # Statistical analysis
    variance = np.var(all_scores)
    mean_score = np.mean(all_scores)
    
    # 4-layer security checks
    if variance < 0.002:
        return False  # Static image detected
    
    if len(set(all_scores)) == 1:
        return False  # All scores identical
    
    if mean_score < 0.85:
        return False  # Average too low
    
    if real_votes / len(all_scores) < 0.7:
        return False  # Too many fake frames
    
    return True  # All checks passed
```

### 6.4. Face Recognition Workflow

```mermaid
sequenceDiagram
    participant V as Video Frame
    participant M as MTCNN
    participant I as InceptionResnetV1
    participant C as ChromaDB
    
    Note over V,C: Registration Phase
    V->>M: Detect face
    M->>M: Align & crop to 160x160
    M->>I: Face tensor
    I->>I: Forward pass
    I->>I: Extract 512-dim embedding
    I->>I: L2 normalize
    I->>C: Store embedding (face_123)
    
    Note over V,C: Verification Phase
    V->>M: Detect face
    M->>I: Face tensor
    I->>I: Extract embedding
    C->>I: Get stored embedding
    I->>I: Cosine similarity
    alt Similarity >= 0.6
        I-->>V: ✓ Match
    else Similarity < 0.6
        I-->>V: ✗ No match
    end
```

-----

## 7. CẤU HÌNH HẠ TẦNG

### 7.1. Network Topology

```mermaid
graph TB
    subgraph "Internet"
        CLIENT[Client Browser/App]
    end
    
    subgraph "DMZ - Demilitarized Zone"
        FW[Firewall<br/>UFW/iptables]
        NGINX[NGINX Reverse Proxy<br/>:80, :443<br/>SSL/TLS]
    end
    
    subgraph "Cinema LAN Network - 192.168.1.0/24"
        ROUTER[WiFi Router<br/>192.168.1.1]
        ANCHOR[Anchor Device<br/>192.168.1.200:2552<br/>GPS Server]
        POS[POS Terminals<br/>192.168.1.10-50]
        PRINTER[Network Printers<br/>192.168.1.100]
    end
    
    subgraph "Application Tier - 10.0.1.0/24"
        WEB1[Web Server 1<br/>10.0.1.10<br/>PHP-FPM]
        WEB2[Web Server 2<br/>10.0.1.11<br/>PHP-FPM]
        AI_SERVER[AI Server<br/>10.0.1.20<br/>Python + GPU]
    end
    
    subgraph "Database Tier - 10.0.2.0/24"
        DB_MASTER[(MySQL Master<br/>10.0.2.10:3306)]
        DB_SLAVE[(MySQL Slave<br/>10.0.2.11:3306)]
        CHROMA_DB[(ChromaDB<br/>10.0.2.20:8000)]
        REDIS_CACHE[(Redis<br/>10.0.2.30:6379)]
    end
    
    subgraph "Storage Tier - 10.0.3.0/24"
        MINIO[MinIO S3<br/>10.0.3.10:9000]
        NFS_SHARE[NFS Server<br/>10.0.3.20]
    end
    
    CLIENT -->|HTTPS| FW
    FW --> NGINX
    NGINX --> WEB1
    NGINX --> WEB2
    
    ROUTER -.->|LAN Only| ANCHOR
    
    WEB1 --> DB_MASTER
    WEB2 --> DB_MASTER
    WEB1 --> DB_SLAVE
    WEB2 --> DB_SLAVE
    
    WEB1 --> AI_SERVER
    WEB2 --> AI_SERVER
    
    AI_SERVER --> CHROMA_DB
    AI_SERVER --> MINIO
    AI_SERVER --> NFS_SHARE
    
    WEB1 --> REDIS_CACHE
    WEB2 --> REDIS_CACHE
    
    style ANCHOR fill:#fff4e1
    style AI_SERVER fill:#ffe1e1
    style CHROMA_DB fill:#e1d4ff
```

### 7.2. Anchor Device Configuration

**Hardware Specification:**
```yaml
Anchor Device (GPS Server):
  Type: Raspberry Pi 4B / Mini PC / Android Device
  CPU: ARM Cortex-A72 / x86_64
  RAM: 2GB minimum
  Storage: 16GB SD Card
  Network: Ethernet 1Gbps (preferred) or WiFi
  GPS Module: U-blox NEO-6M / NEO-M8N
  Power: 5V/3A USB-C (UPS backup recommended)
```

**Software Stack:**
```yaml
OS: Raspbian Lite / Ubuntu Server 22.04
Runtime: Node.js 18.x / Python 3.10
GPS Library: gpsd / python-gps
HTTP Server: Express.js / FastAPI
Port: 2552
Startup: systemd service (auto-start on boot)
```

**Server Code Example (Node.js):**
```javascript
// server.js - GPS Token Server
const express = require('express');
const jwt = require('jsonwebtoken');
const gps = require('node-gpsd');

const app = express();
const SECRET_KEY = process.env.GPS_SECRET_KEY;

// GPS coordinates (hardcoded or from GPS module)
const CINEMA_LAT = 10.762622;
const CINEMA_LNG = 106.660172;

app.get('/', (req, res) => {
    const payload = {
        device_id: 'CINEMA_A_DEVICE_01',
        latitude: CINEMA_LAT,
        longitude: CINEMA_LNG,
        timestamp: Math.floor(Date.now() / 1000),
        exp: Math.floor(Date.now() / 1000) + 30  // 30s expiry
    };
    
    const token = jwt.sign(payload, SECRET_KEY, { algorithm: 'HS256' });
    
    res.json({
        status: 'success',
        token: token,
        google_maps_url: `https://maps.google.com/?q=${CINEMA_LAT},${CINEMA_LNG}`
    });
});

app.listen(2552, '0.0.0.0', () => {
    console.log('GPS Token Server running on port 2552');
});
```

### 7.3. Server Requirements

**Web Server (PHP-FPM):**
```ini
; /etc/php/8.1/fpm/pool.d/www.conf
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 500
request_terminate_timeout = 120s

; PHP ini settings
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 120
memory_limit = 512M
```

**AI Server (Python):**
```yaml
Hardware:
  CPU: Intel Xeon / AMD EPYC 8+ cores
  RAM: 16GB minimum, 32GB recommended
  GPU: NVIDIA RTX 3060 / Tesla T4 (optional but recommended)
  Storage: 100GB SSD
  
Software:
  OS: Ubuntu 22.04 LTS
  Python: 3.10+
  CUDA: 11.8 (if GPU enabled)
  cuDNN: 8.6
  
Python Environment:
  Virtual Env: /var/www/epiccinema.code/venv
  Requirements: requirements.txt
  Models Path: bin/python/anti_spoof_models/
```

### 7.4. NGINX Configuration

```nginx
# /etc/nginx/sites-available/epiccinema
upstream php_backend {
    server 10.0.1.10:9000;
    server 10.0.1.11:9000;
    keepalive 32;
}

server {
    listen 80;
    server_name cinema.example.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name cinema.example.com;
    
    ssl_certificate /etc/ssl/certs/cinema.crt;
    ssl_certificate_key /etc/ssl/private/cinema.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    
    root /var/www/epiccinema.code/public;
    index index.php index.html;
    
    client_max_body_size 50M;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass php_backend;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 120s;
    }
    
    location /api/cham-cong/ {
        fastcgi_pass php_backend;
        fastcgi_read_timeout 180s;  # AI processing can take longer
        include fastcgi_params;
    }
}
```

-----

## 8. CÁC KỊCH BẢN CHỐNG GIAN LẬN

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

### 8.5. Monitoring & Logging

**Log Files Structure:**
```
cache/log/
├── face_checkin.log      # Check-in AI logs
├── face_checkout.log     # Check-out AI logs
├── face_register.log     # Registration logs
├── nginx_access.log      # NGINX access logs
├── nginx_error.log       # NGINX error logs
├── php_errors.log        # PHP application errors
└── chromadb.log          # Vector database logs
```

**Metrics to Monitor:**
- Anchor Device uptime & response time
- AI inference latency (should be < 3s)
- ChromaDB query performance
- MySQL connection pool usage
- Failed authentication attempts
- GPS distance violations
- Anti-spoofing detection rate

-----

## 9. DEPLOYMENT & MAINTENANCE

### 9.1. Deployment Checklist

```mermaid
graph LR
    A[Git Pull Code] --> B[Install Dependencies]
    B --> C[Configure .env]
    C --> D[Database Migration]
    D --> E[Download AI Models]
    E --> F[Setup Anchor Device]
    F --> G[Configure NGINX]
    G --> H[Start Services]
    H --> I[Test End-to-End]
    
    style A fill:#3498db
    style I fill:#27ae60
```

**Step-by-step:**
```bash
# 1. Clone repository
git clone https://github.com/your-org/epiccinema.git
cd epiccinema.code

# 2. PHP dependencies
composer install --no-dev --optimize-autoloader

# 3. Python dependencies
python3 -m venv venv
source venv/bin/activate
pip install -r requirements.txt

# 4. Download AI models
mkdir -p bin/python/anti_spoof_models
wget https://storage.url/MiniFASNetV2.pth -O bin/python/anti_spoof_models/2.7_80x80_MiniFASNetV2.pth

# 5. Database setup
php artisan migrate --force

# 6. Start ChromaDB
docker run -d -p 8000:8000 chromadb/chroma:latest

# 7. Setup systemd services
sudo systemctl enable nginx php8.1-fpm
sudo systemctl start nginx php8.1-fpm
```

### 9.2. Backup Strategy

```yaml
Daily Backups:
  MySQL:
    Type: Full dump
    Schedule: 2:00 AM daily
    Retention: 7 days
    Command: mysqldump --all-databases | gzip > backup_$(date +%Y%m%d).sql.gz
    
  ChromaDB:
    Type: Collection export
    Schedule: 2:30 AM daily
    Retention: 30 days
    Storage: S3/MinIO
    
  Application Logs:
    Type: Compressed archive
    Schedule: Daily rotation
    Retention: 90 days
```

### 9.3. Troubleshooting Guide

| Vấn đề | Nguyên nhân khả dĩ | Giải pháp |
|--------|-------------------|-----------|
| Không kết nối được Anchor Device | - Thiết bị tắt<br/>- Sai WiFi<br/>- Port 2552 bị chặn | - Kiểm tra nguồn điện<br/>- Xác nhận kết nối WiFi<br/>- Mở firewall port 2552 |
| AI xử lý quá chậm | - GPU không được sử dụng<br/>- Model chưa cache | - Cài PyTorch với CUDA<br/>- Warm-up model khi start |
| ChromaDB connection refused | - Service chưa chạy<br/>- Port 8000 conflict | - `docker ps` kiểm tra container<br/>- Đổi port nếu conflict |
| Face không khớp dù đúng người | - Threshold quá cao<br/>- Ánh sáng thay đổi nhiều | - Giảm threshold về 0.55<br/>- Đăng ký lại trong điều kiện tương tự |
| Liveness luôn fail | - Model file corrupt<br/>- Preprocessing sai | - Download lại model<br/>- Kiểm tra normalize /255.0 |

-----

## 10. KẾT LUẬN

### 10.1. Điểm mạnh của hệ thống

✅ **Bảo mật đa lớp:**
- Xác thực mạng LAN (WiFi)
- GPS định vị chống giả mạo
- JWT token với HMAC-SHA256
- AI anti-spoofing với 4 lớp kiểm tra
- Face recognition với cosine similarity

✅ **Độ chính xác cao:**
- FaceNet pretrained trên 3.31M images
- Threshold tùy chỉnh (0.6 default)
- Variance detection cho ảnh tĩnh

✅ **Scalable Architecture:**
- Load balancer hỗ trợ multiple web servers
- ChromaDB vector search nhanh
- MySQL replication cho read-heavy workload

### 10.2. Roadmap phát triển

**Q1 2026:**
- [ ] Thêm face mask detection
- [ ] Mobile app native (React Native)
- [ ] Real-time dashboard monitoring

**Q2 2026:**
- [ ] Multi-factor authentication (OTP)
- [ ] Behavioral biometrics (typing pattern)
- [ ] Edge AI deployment (on-device inference)

**Q3 2026:**
- [ ] Blockchain audit trail
- [ ] Federated learning cho privacy
- [ ] Kubernetes deployment

-----

*Tài liệu này cung cấp cái nhìn toàn diện về kiến trúc, công nghệ và triển khai hệ thống chấm công AI. Mọi thay đổi về infrastructure hoặc algorithm cần cập nhật vào tài liệu này.*

**Phiên bản tài liệu:** 2.0  
**Cập nhật lần cuối:** 2025-12-06  
**Tác giả:** Development Team - EPIC Cinema  
**Liên hệ:** tech@epiccinema.com