# TÀI LIỆU TRIỂN KHAI VIDEO CALL

## MỤC LỤC

1. [Tổng quan](#1-tổng-quan)
2. [Kiến trúc hệ thống](#2-kiến-trúc-hệ-thống)
3. [Công nghệ sử dụng](#3-công-nghệ-sử-dụng)
4. [Yêu cầu hệ thống](#4-yêu-cầu-hệ-thống)
5. [Cài đặt và cấu hình](#5-cài-đặt-và-cấu-hình)
6. [Luồng hoạt động](#6-luồng-hoạt-động)
7. [API và Events](#7-api-và-events)
8. [Bảo mật và xác thực](#8-bảo-mật-và-xác-thực)
9. [Troubleshooting](#9-troubleshooting)
10. [Tối ưu hóa](#10-tối-ưu-hóa)

---

## 1. TỔNG QUAN

Hệ thống Video Call được xây dựng để hỗ trợ tư vấn trực tuyến 1:1 giữa khách hàng và nhân viên tư vấn của EPIC Cinema. Hệ thống sử dụng WebRTC cho kết nối peer-to-peer và Socket.IO cho signaling server.

### 1.1. Tính năng chính

- ✅ Video call 1:1 giữa khách hàng và nhân viên
- ✅ Audio/Video quality control (mute/unmute, bật/tắt camera)
- ✅ Screen sharing (chia sẻ màn hình)
- ✅ Real-time connection status
- ✅ Call timer (đếm thời gian cuộc gọi)
- ✅ Auto-reconnection khi mất kết nối
- ✅ Xác thực quyền tham gia room
- ✅ Hỗ trợ đa thiết bị (disconnect thiết bị cũ khi đăng nhập mới)

### 1.2. Quy trình sử dụng

1. **Khách hàng đặt lịch gọi video** → Tạo lịch trong hệ thống
2. **Nhân viên chọn tư vấn** → Tạo room ID và lưu vào Redis
3. **Khách hàng tham gia** → Kết nối Socket.IO và join room
4. **Nhân viên tham gia** → Kết nối Socket.IO và join room
5. **Thiết lập WebRTC** → Exchange offer/answer và ICE candidates
6. **Bắt đầu cuộc gọi** → Cập nhật trạng thái "Đang gọi"
7. **Kết thúc cuộc gọi** → Cleanup và cập nhật trạng thái

---

## 2. KIẾN TRÚC HỆ THỐNG

### 2.1. Sơ đồ kiến trúc

```
┌─────────────┐         ┌──────────────┐         ┌─────────────┐
│   Client   │◄───────►│ Socket.IO    │◄───────►│   Redis     │
│  (Browser) │  WebRTC │   Server     │         │   Cache     │
└────────────┘         └──────────────┘         └─────────────┘
      │                        │                        │
      │                        │                        │
      ▼                        ▼                        ▼
┌─────────────┐         ┌──────────────┐         ┌─────────────┐
│   WebRTC    │         │   Express    │         │   MySQL     │
│  Peer-to-   │         │   Server     │         │  Database   │
│    Peer     │         │  (Node.js)   │         │             │
└─────────────┘         └──────────────┘         └─────────────┘
      │                        │
      │                        │
      ▼                        ▼
┌─────────────┐         ┌──────────────┐
│   TURN      │         │   PHP API     │
│   Server    │         │   Backend     │
└─────────────┘         └──────────────┘
```

### 2.2. Các thành phần chính

#### 2.2.1. Frontend (Client)
- **File**: `customer/js/video-call.js`
- **Template**: `src/Views/customer/video-call.blade.php`
- **Chức năng**:
  - Quản lý WebRTC peer connection
  - Xử lý media streams (audio/video)
  - UI controls (mute, camera, screen share)
  - Kết nối Socket.IO cho signaling

#### 2.2.2. Backend Server (Node.js)
- **File**: `ServiceRealtime/server.js`
- **Chức năng**:
  - Socket.IO server cho signaling
  - Quản lý rooms và participants
  - Xác thực quyền tham gia
  - Redis integration

#### 2.2.3. Video Call Handler
- **File**: `ServiceRealtime/sockets/videoCallHandler.js`
- **Chức năng**:
  - Xử lý join-room events
  - WebRTC signaling (offer/answer/ICE)
  - Quản lý room participants
  - Force disconnect khi đăng nhập từ thiết bị khác

#### 2.2.4. PHP Backend
- **Service**: `src/Services/Sc_GoiVideo.php`
- **Controller**: `src/Controllers/Ctrl_GoiVideo.php`
- **Chức năng**:
  - Quản lý lịch gọi video
  - Tạo room và lưu vào Redis
  - Xác thực quyền truy cập
  - Cập nhật trạng thái cuộc gọi

#### 2.2.5. Redis Cache
- **Chức năng**:
  - Lưu thông tin room (`videoroom:{roomId}`)
  - Lưu socket IDs (`videoroom:{roomId}:sockets`)
  - Pub/Sub cho real-time notifications

---

## 3. CÔNG NGHỆ SỬ DỤNG

### 3.1. WebRTC
- **Mục đích**: Peer-to-peer video/audio communication
- **Features**:
  - Media capture (getUserMedia)
  - RTCPeerConnection
  - RTCDataChannel (nếu cần)
  - ICE candidate exchange

### 3.2. Socket.IO
- **Version**: 4.8.1
- **Mục đích**: Signaling server cho WebRTC
- **Events**:
  - `join-room`: Tham gia room
  - `offer`: WebRTC offer
  - `answer`: WebRTC answer
  - `ice-candidate`: ICE candidate
  - `user-joined`: Người dùng mới tham gia
  - `user-left`: Người dùng rời khỏi
  - `leave-room`: Rời room

### 3.3. STUN/TURN Servers

#### 3.3.1. STUN Server (Session Traversal Utilities for NAT)

**Mục đích**: Giúp client phát hiện địa chỉ IP công khai (public IP) của mình khi đang ở sau NAT/Firewall.

**Cách hoạt động**:
- Client gửi request đến STUN server
- STUN server trả về địa chỉ IP và port mà nó nhìn thấy
- Client sử dụng thông tin này để thiết lập peer-to-peer connection

**Khi nào chỉ cần STUN**:
- ✅ **Môi trường localhost/local network**: Cả 2 client cùng mạng, không có NAT phức tạp
- ✅ **Symmetric NAT không có**: NAT đơn giản, có thể traverse được
- ✅ **Testing/Development**: Demo nội bộ, không cần production quality

**STUN Servers (Google - miễn phí)**:
```
stun:stun.l.google.com:19302
stun:stun1.l.google.com:19302
stun:stun2.l.google.com:19302
```

**Hạn chế của STUN**:
- ❌ Không hoạt động với **Symmetric NAT** (nhiều router/corporate firewall)
- ❌ Không relay traffic → Nếu không thể P2P thì sẽ fail
- ❌ Không đảm bảo kết nối thành công trong mọi trường hợp

#### 3.3.2. TURN Server (Traversal Using Relays around NAT)

**Mục đích**: Relay server để forward traffic khi không thể thiết lập peer-to-peer connection.

**Cách hoạt động**:
- Client gửi traffic đến TURN server
- TURN server forward traffic đến peer khác
- Hoạt động như một "trung gian" khi P2P không thể

**Khi nào cần TURN**:
- ✅ **Production environment (VPS/Cloud)**: Client ở các mạng khác nhau, có NAT phức tạp
- ✅ **Corporate networks**: Firewall chặn P2P connections
- ✅ **Symmetric NAT**: Router không cho phép direct connection
- ✅ **Mobile networks**: 3G/4G/5G thường có NAT phức tạp
- ✅ **Đảm bảo reliability**: Muốn đảm bảo cuộc gọi luôn thành công

**TURN Server (Custom - EPIC Cinema)**:
```
turn:epiccinema.io.vn:3478
Username: videocall
Credential: 2025
```

**Ưu điểm của TURN**:
- ✅ Hoạt động trong **mọi** network condition
- ✅ Đảm bảo kết nối thành công
- ✅ Hỗ trợ cả TCP và UDP

**Nhược điểm của TURN**:
- ❌ Tốn bandwidth (phải relay qua server)
- ❌ Tốn chi phí server (phải host TURN server)
- ❌ Latency cao hơn P2P (traffic phải đi qua server)

#### 3.3.3. Tại sao Local chỉ cần STUN, VPS lại cần TURN?

**Môi trường Local (localhost/local network)**:
```
Client A (192.168.1.10) ←→ Router ←→ Client B (192.168.1.11)
```
- Cùng một mạng LAN
- NAT đơn giản, có thể traverse
- STUN đủ để phát hiện IP và thiết lập P2P
- ✅ **Chỉ cần STUN là đủ**

**Môi trường Production (VPS/Internet)**:
```
Client A (Nhà) ←→ ISP NAT ←→ Internet ←→ ISP NAT ←→ Client B (Văn phòng)
```
- Khác mạng, có nhiều lớp NAT
- Firewall của ISP có thể chặn P2P
- Symmetric NAT không thể traverse
- ❌ **STUN không đủ → Cần TURN để relay**

#### 3.3.4. WebRTC ICE Candidate Strategy

WebRTC sử dụng **ICE (Interactive Connectivity Establishment)** để tìm đường kết nối tốt nhất:

1. **Host candidates**: Direct connection (cùng mạng)
2. **Server Reflexive candidates**: STUN (phát hiện public IP)
3. **Relay candidates**: TURN (relay qua server)

**Thứ tự ưu tiên**:
```
1. Host (P2P cùng mạng) ← Nhanh nhất, rẻ nhất
2. Server Reflexive (STUN) ← Nhanh, miễn phí
3. Relay (TURN) ← Chậm hơn, tốn phí nhưng đảm bảo
```

**Fallback mechanism**:
- WebRTC sẽ thử P2P trước
- Nếu fail → Thử STUN
- Nếu vẫn fail → Dùng TURN (luôn thành công)

#### 3.3.5. Cấu hình STUN/TURN trong Code

**Frontend (video-call.js)**:
```javascript
const iceServers = [
  // STUN servers (miễn phí, dùng cho local/testing)
  { urls: 'stun:stun.l.google.com:19302' },
  { urls: 'stun:stun1.l.google.com:19302' },
  
  // TURN server (production, cần cho VPS)
  {
    urls: 'turn:epiccinema.io.vn:3478',
    username: 'videocall',
    credential: '2025'
  }
];

const peerConnection = new RTCPeerConnection({ iceServers });
```

**Lưu ý**:
- Development: Có thể chỉ dùng STUN để test
- Production: **Bắt buộc** phải có TURN để đảm bảo reliability

### 3.4. Redis
- **Mục đích**: Cache và pub/sub
- **Keys**:
  - `videoroom:{roomId}`: Thông tin room (TTL: 86400s)
  - `videoroom:{roomId}:sockets`: Hash chứa socket IDs

### 3.5. Node.js Dependencies
```json
{
  "socket.io": "^4.8.1",
  "express": "^5.1.0",
  "ioredis": "^5.7.0",
  "axios": "^1.12.2",
  "dotenv": "^17.2.2"
}
```

---

## 4. YÊU CẦU HỆ THỐNG

### 4.1. Server Requirements

#### Node.js Server
- **Node.js**: >= 16.x
- **Port**: 3000 (có thể cấu hình)
- **RAM**: Tối thiểu 512MB
- **CPU**: 1 core (khuyến nghị 2 cores)

#### Redis
- **Version**: >= 6.0
- **Memory**: Tùy theo số lượng rooms đồng thời
- **Network**: Kết nối ổn định với Node.js server

#### PHP Backend
- **PHP**: >= 7.4
- **Extensions**: 
  - `php-redis`
  - `php-curl`
  - `php-json`

### 4.2. Client Requirements

#### Browser Support
- ✅ Chrome/Edge >= 90
- ✅ Firefox >= 88
- ✅ Safari >= 14
- ✅ Opera >= 76

#### Browser Features Required
- WebRTC API
- MediaDevices API
- WebSocket support
- ES6 Modules support

#### Hardware
- Camera (webcam hoặc built-in)
- Microphone
- Kết nối internet ổn định (tối thiểu 1 Mbps upload/download)

---

## 5. CÀI ĐẶT VÀ CẤU HÌNH

### 5.1. Cài đặt Node.js Server

#### 5.1.1. Clone và cài đặt dependencies

```bash
cd /home/nguye/code/ServiceRealtime
npm install
```

#### 5.1.2. Cấu hình Environment Variables

Tạo file `.env` trong thư mục `ServiceRealtime`:

```env
# Socket.IO Server
PORT=3000
URL_WEB=http://localhost:3000
URL_SERVER_REALTIME=http://localhost:3000

# Redis Configuration
REDIS_HOST=redis-18469.crce194.ap-seast-1-1.ec2.redns.redis-cloud.com
REDIS_PORT=18469
REDIS_USERNAME=default
REDIS_PASSWORD=wVL6uW0sbgq4w6esirgrLnxiFZdO8UJV

# API Backend
URL_API=https://your-domain.com/api
```

#### 5.1.3. Chạy server

**Development mode:**
```bash
npm run dev
```

**Production mode (với PM2):**
```bash
pm2 start server.js --name "epic-realtime"
pm2 save
pm2 startup
```

### 5.2. Cấu hình PHP Backend

#### 5.2.1. Environment Variables

Thêm vào file `.env` của PHP project:

```env
# Socket.IO Server URL
URL_SERVER_REALTIME=http://your-socket-server:3000

# Redis Configuration (nếu chưa có)
REDIS_HOST=redis-18469.crce194.ap-seast-1-1.ec2.redns.redis-cloud.com
REDIS_PORT=18469
REDIS_USERNAME=default
REDIS_PASSWORD=wVL6uW0sbgq4w6esirgrLnxiFZdO8UJV
```

#### 5.2.2. Cấu hình Redis Connection

File: `src/Core/Function.php`

```php
function getRedisConnection() {
    static $redis = null;
    
    if ($redis === null) {
        $host = $_ENV['REDIS_HOST'] ?? 'localhost';
        $port = $_ENV['REDIS_PORT'] ?? 6379;
        $username = $_ENV['REDIS_USERNAME'] ?? null;
        $password = $_ENV['REDIS_PASSWORD'] ?? null;
        
        $redis = new \Redis();
        $redis->pconnect($host, $port);
        
        if (!empty($username)) {
            $redis->auth([$username, $password]);
        } else if (!empty($password)) {
            $redis->auth($password);
        }
        
        $redis->setOption(\Redis::OPT_READ_TIMEOUT, 2.5);
    }
    
    return $redis;
}
```

### 5.3. Cấu hình TURN Server

> **⚠️ Lưu ý quan trọng**: TURN server chỉ cần thiết cho **production environment (VPS)**. 
> Đối với development/local testing, chỉ cần STUN server là đủ.

#### 5.3.1. Khi nào cần cài đặt TURN Server?

**Cần TURN Server khi**:
- ✅ Deploy lên VPS/Cloud server
- ✅ Client có thể ở các mạng khác nhau (nhà, văn phòng, mobile)
- ✅ Muốn đảm bảo cuộc gọi luôn thành công
- ✅ Production environment

**Không cần TURN Server khi**:
- ❌ Development local (localhost)
- ❌ Testing trong cùng mạng LAN
- ❌ Demo nội bộ

#### 5.3.2. Cài đặt Coturn

```bash
# Ubuntu/Debian
sudo apt-get update
sudo apt-get install coturn

# CentOS/RHEL
sudo yum install coturn

# Kiểm tra version
turnserver --version
```

#### 5.3.3. Cấu hình Coturn

File: `/etc/turnserver.conf`

```conf
# Listening port (STUN/TURN)
listening-port=3478
listening-ip=0.0.0.0  # Lắng nghe trên tất cả interfaces

# Realm (domain của bạn)
realm=epiccinema.io.vn

# User credentials (username:password)
# Format: user=username:password
user=videocall:2025

# External IP (quan trọng cho VPS)
# Thay bằng IP public của VPS
external-ip=YOUR_VPS_PUBLIC_IP

# Port range cho relay (quan trọng!)
# Mở range này trong firewall
relay-ip=0.0.0.0
min-port=49152
max-port=65535

# Logging
log-file=/var/log/turnserver.log
verbose
no-stdout-log

# Security
# Không cho phép anonymous (bắt buộc auth)
no-cli
no-tls
no-dtls

# Performance
total-quota=100  # Giới hạn số connections đồng thời
user-quota=10    # Giới hạn mỗi user
```

**Lưu ý quan trọng**:
- `external-ip`: Phải là IP public của VPS (không phải localhost)
- `min-port` và `max-port`: Phải mở range này trong firewall
- `user`: Format `username:password` (không hash)

#### 5.3.4. Khởi động TURN Server

```bash
# Khởi động service
sudo systemctl start coturn
sudo systemctl enable coturn

# Kiểm tra status
sudo systemctl status coturn

# Xem logs
sudo tail -f /var/log/turnserver.log
```

#### 5.3.5. Cấu hình Firewall cho TURN Server

**Mở các ports cần thiết**:

```bash
# Port 3478 (STUN/TURN)
sudo ufw allow 3478/tcp
sudo ufw allow 3478/udp

# Port range cho relay (49152-65535)
sudo ufw allow 49152:65535/udp
sudo ufw allow 49152:65535/tcp

# Reload firewall
sudo ufw reload
```

#### 5.3.6. Test TURN Server

**Sử dụng tool online**:
- https://webrtc.github.io/samples/src/content/peerconnection/trickle-ice/
- Nhập TURN server credentials và test

**Sử dụng command line**:
```bash
# Test STUN
turnutils_stunclient epiccinema.io.vn

# Test TURN (cần credentials)
turnutils_peer -u videocall -w 2025 epiccinema.io.vn
```

**Kiểm tra từ code**:
```javascript
// Test trong browser console
const pc = new RTCPeerConnection({
  iceServers: [{
    urls: 'turn:epiccinema.io.vn:3478',
    username: 'videocall',
    credential: '2025'
  }]
});

pc.onicecandidate = (event) => {
  if (event.candidate) {
    console.log('ICE Candidate:', event.candidate);
    // Kiểm tra type: 'relay' = TURN đang hoạt động
  }
};
```

#### 5.3.7. Troubleshooting TURN Server

**Vấn đề: TURN server không hoạt động**

**Kiểm tra**:
```bash
# 1. Service đang chạy?
sudo systemctl status coturn

# 2. Port đang listen?
sudo netstat -tulpn | grep 3478

# 3. Firewall đã mở?
sudo ufw status

# 4. Logs có lỗi?
sudo tail -f /var/log/turnserver.log
```

**Lỗi thường gặp**:
- ❌ `external-ip` chưa set → TURN không hoạt động đúng
- ❌ Firewall chưa mở port range → Relay fail
- ❌ Credentials sai → Authentication fail
- ❌ `realm` không đúng → Connection reject

#### 5.3.8. Tối ưu TURN Server

**Giới hạn bandwidth**:
```conf
# Giới hạn bandwidth per user (KB/s)
max-bps=1000000  # 1 Mbps
```

**Giới hạn connections**:
```conf
# Tổng số connections đồng thời
total-quota=100

# Mỗi user tối đa
user-quota=10
```

**Monitoring**:
```bash
# Xem số lượng active sessions
turnutils_peer -u videocall -w 2025 epiccinema.io.vn -s

# Xem stats
sudo turnadmin -l -b /var/lib/turn/turndb
```

### 5.4. Cấu hình Nginx (Reverse Proxy)

> **Lưu ý**: Nginx chỉ cần cho Socket.IO server, không cần cho TURN server.
> TURN server hoạt động độc lập trên port 3478.

Nếu chạy Socket.IO server trên VPS, cần cấu hình Nginx để proxy:

```nginx
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name epiccinema.io.vn www.epiccinema.io.vn;
    ...
    # REALTIME
    location /socket.io/ {
        proxy_pass http://127.0.0.1:3000;

        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";

        proxy_set_header Host $host;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;

        proxy_read_timeout 86400;
    }
    ...
}
```

### 5.5. Cấu hình Firewall

```bash
# Mở port 3000 (Socket.IO)
sudo ufw allow 3000/tcp

# Mở port 3478 (TURN server)
sudo ufw allow 3478/tcp
sudo ufw allow 3478/udp

# Mở port range cho TURN (49152-65535)
sudo ufw allow 49152:65535/udp
```

---

## 6. LUỒNG HOẠT ĐỘNG

### 6.1. Luồng tạo room và tham gia

```
1. Khách hàng đặt lịch
   ↓
2. Nhân viên chọn tư vấn
   ├─ Tạo room ID: video_{idLich}_{timestamp}
   ├─ Lưu vào MySQL: LichGoiVideo, WebRTCSession
   └─ Lưu vào Redis: videoroom:{roomId}
   ↓
3. Khách hàng truy cập URL: /video-call?room={roomId}
   ├─ Load trang video-call.blade.php
   ├─ Kết nối Socket.IO
   └─ Emit 'join-room' với {roomId, userId, userType}
   ↓
4. Server xác thực
   ├─ Kiểm tra room trong Redis
   ├─ Kiểm tra quyền (userId phải đúng)
   └─ Join room và emit 'room-joined'
   ↓
5. Nhân viên tham gia (tương tự bước 3-4)
   ↓
6. Khi có 2 người trong room
   ├─ Tự động bật camera/mic (nếu chưa bật)
   ├─ Tạo RTCPeerConnection
   └─ Bắt đầu WebRTC signaling
```

### 6.2. Luồng WebRTC Signaling

```
1. Customer tạo offer
   ├─ createOffer()
   ├─ setLocalDescription(offer)
   └─ socket.emit('offer', {roomId, offer})
   ↓
2. Server forward offer
   └─ socket.to(roomId).emit('offer', {offer, from})
   ↓
3. Staff nhận offer
   ├─ setRemoteDescription(offer)
   ├─ createAnswer()
   ├─ setLocalDescription(answer)
   └─ socket.emit('answer', {roomId, answer})
   ↓
4. Server forward answer
   └─ socket.to(roomId).emit('answer', {answer, from})
   ↓
5. Customer nhận answer
   └─ setRemoteDescription(answer)
   ↓
6. ICE Candidates exchange
   ├─ onicecandidate → emit('ice-candidate')
   └─ Nhận candidate → addIceCandidate()
   ↓
7. Connection established
   └─ ontrack → Hiển thị remote video
```

### 6.3. Luồng xử lý disconnect

```
1. User đóng tab/refresh
   ├─ beforeunload event
   ├─ socket.emit('leave-room')
   └─ socket.disconnect()
   ↓
2. Server xử lý disconnect
   ├─ socket.on('disconnect')
   ├─ Xóa socket khỏi Redis
   └─ Emit 'user-left' cho người còn lại
   ↓
3. User còn lại nhận 'user-left'
   ├─ Reset peer connection
   ├─ Dừng remote stream
   └─ Hiển thị "Đang chờ kết nối lại..."
```

---

## 7. API VÀ EVENTS

### 7.1. Socket.IO Events

#### Client → Server

##### `join-room`
Tham gia room video call.

**Payload:**
```javascript
{
  roomId: string,    // Room ID từ URL parameter
  userId: string,    // User ID từ session
  userType: string   // 'customer' hoặc 'staff'
}
```

**Response Events:**
- `room-joined`: Thành công
- `join-error`: Lỗi (room không tồn tại, không có quyền)

##### `offer`
Gửi WebRTC offer.

**Payload:**
```javascript
{
  roomId: string,
  offer: RTCSessionDescription
}
```

##### `answer`
Gửi WebRTC answer.

**Payload:**
```javascript
{
  roomId: string,
  answer: RTCSessionDescription
}
```

##### `ice-candidate`
Gửi ICE candidate.

**Payload:**
```javascript
{
  roomId: string,
  candidate: RTCIceCandidate
}
```

##### `leave-room`
Rời khỏi room.

**Payload:** Không có

#### Server → Client

##### `room-joined`
Thông báo đã tham gia room thành công.

**Payload:**
```javascript
{
  roomId: string,
  participants: {
    customer: string,  // Socket ID của customer
    staff: string     // Socket ID của staff
  }
}
```

##### `user-joined`
Thông báo có người mới tham gia.

**Payload:**
```javascript
{
  userId: string,
  userType: string,
  socketId: string
}
```

##### `user-left`
Thông báo người dùng rời khỏi.

**Payload:**
```javascript
{
  userId: string,
  userType: string
}
```

##### `offer`
Nhận WebRTC offer từ người khác.

**Payload:**
```javascript
{
  offer: RTCSessionDescription,
  from: string  // 'customer' hoặc 'staff'
}
```

##### `answer`
Nhận WebRTC answer từ người khác.

**Payload:**
```javascript
{
  answer: RTCSessionDescription,
  from: string
}
```

##### `ice-candidate`
Nhận ICE candidate từ người khác.

**Payload:**
```javascript
{
  candidate: RTCIceCandidate,
  from: string
}
```

##### `force-disconnect`
Bị disconnect do đăng nhập từ thiết bị khác.

**Payload:**
```javascript
{
  message: string
}
```

##### `join-error`
Lỗi khi join room.

**Payload:**
```javascript
{
  message: string
}
```

### 7.2. PHP API Endpoints

#### `POST /api/goi-video/bat-dau`
Cập nhật trạng thái cuộc gọi thành "Đang gọi".

**Request:**
```json
{
  "room_id": "video_123_1234567890"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Cuộc gọi đã bắt đầu"
}
```

---

## 8. BẢO MẬT VÀ XÁC THỰC

### 8.1. Xác thực quyền tham gia room

#### 8.1.1. Kiểm tra room tồn tại
- Server kiểm tra `videoroom:{roomId}` trong Redis
- Nếu không tồn tại → Reject với message "Room không tồn tại hoặc đã hết hạn"

#### 8.1.2. Kiểm tra quyền user
- **Customer**: Phải đúng `id_khachhang` trong room info
- **Staff**: Phải đúng `id_nhanvien` trong room info
- Nếu không đúng → Reject với message tương ứng

### 8.2. Bảo mật Socket.IO

#### 8.2.1. CORS Configuration
```javascript
const io = new Server(http, {
  cors: {
    origin: process.env.URL_WEB,  // Chỉ cho phép origin từ env
    methods: ["GET", "POST"],
    credentials: true
  }
});
```

#### 8.2.2. Room Isolation
- Mỗi room là một Socket.IO room riêng biệt
- Events chỉ được gửi trong room đó
- Không thể truy cập room của người khác

### 8.3. Bảo mật TURN Server

- Sử dụng username/password authentication
- Chỉ cho phép domain được chỉ định
- Logging để theo dõi truy cập

### 8.4. Redis Security

- Sử dụng password authentication
- TTL cho room data (24 giờ)
- Không lưu thông tin nhạy cảm trong Redis

---

## 9. TROUBLESHOOTING

### 9.1. Lỗi kết nối Socket.IO

#### Vấn đề: Không thể kết nối đến Socket.IO server

**Nguyên nhân:**
- Server chưa chạy
- Port bị chặn bởi firewall
- CORS configuration sai
- URL_SERVER_REALTIME không đúng

**Giải pháp:**
```bash
# Kiểm tra server đang chạy
ps aux | grep node
netstat -tulpn | grep 3000

# Kiểm tra firewall
sudo ufw status

# Kiểm tra logs
pm2 logs epic-realtime
```

### 9.2. Lỗi WebRTC Connection

#### Vấn đề: Không thể thiết lập peer connection

**Nguyên nhân:**
- STUN/TURN server không accessible
- Firewall chặn UDP ports
- NAT traversal issues
- **Thiếu TURN server trong production** (quan trọng!)

**Giải pháp:**
**Phân biệt lỗi**:

**Local/Development (chỉ dùng STUN)**:
- ✅ Cùng mạng LAN → Hoạt động tốt
- ❌ Khác mạng → Có thể fail nếu có Symmetric NAT

**Production/VPS (cần TURN)**:
- ❌ Chỉ có STUN → **Sẽ fail** khi client ở mạng khác nhau
- ✅ Có TURN → **Luôn thành công** (relay qua server)

```javascript
// Kiểm tra ICE connection state
peerConnection.oniceconnectionstatechange = () => {
  console.log('ICE state:', peerConnection.iceConnectionState);
  
  // Các trạng thái:
  // - 'new': Đang tìm đường kết nối
  // - 'checking': Đang kiểm tra candidates
  // - 'connected': Đã kết nối (P2P hoặc relay)
  // - 'completed': Hoàn tất
  // - 'failed': Thất bại → Cần kiểm tra TURN server
  // - 'disconnected': Mất kết nối
  // - 'closed': Đã đóng
  
  if (peerConnection.iceConnectionState === 'failed') {
    console.error('WebRTC connection failed!');
    console.log('Có thể do:');
    console.log('1. Thiếu TURN server (production)');
    console.log('2. Firewall chặn ports');
    console.log('3. TURN server không hoạt động');
  }
};

// Kiểm tra ICE candidates
peerConnection.onicecandidate = (event) => {
  if (event.candidate) {
    console.log('ICE Candidate:', event.candidate);
    console.log('Type:', event.candidate.type);
    // 'host' = P2P cùng mạng
    // 'srflx' = STUN (server reflexive)
    // 'relay' = TURN (relay) ← Cần cho production!
    
    if (event.candidate.type === 'relay') {
      console.log('✅ TURN server đang hoạt động!');
    }
  } else {
    console.log('✅ Đã gather hết ICE candidates');
  }
};

// Test TURN server
// Sử dụng: https://webrtc.github.io/samples/src/content/peerconnection/trickle-ice/
// Nhập TURN credentials và kiểm tra có candidate type 'relay' không
```

**Debug checklist**:

1. **Local testing (chỉ STUN)**:
   ```javascript
   const iceServers = [
     { urls: 'stun:stun.l.google.com:19302' }
   ];
   ```
   - ✅ Nếu cùng mạng → Hoạt động
   - ❌ Nếu khác mạng → Có thể fail

2. **Production (cần TURN)**:
   ```javascript
   const iceServers = [
     { urls: 'stun:stun.l.google.com:19302' },
     {
       urls: 'turn:epiccinema.io.vn:3478',
       username: 'videocall',
       credential: '2025'
     }
   ];
   ```
   - ✅ Phải có TURN server
   - ✅ TURN server phải accessible từ internet
   - ✅ Firewall phải mở ports

**Kiểm tra TURN server hoạt động**:
```bash
# Test từ command line
turnutils_peer -u videocall -w 2025 epiccinema.io.vn

# Test từ browser
# Mở: https://webrtc.github.io/samples/src/content/peerconnection/trickle-ice/
# Thêm TURN server và kiểm tra có candidate type 'relay'
```

### 9.3. Lỗi không có audio/video

#### Vấn đề: Không thấy video hoặc không nghe được audio

**Nguyên nhân:**
- Chưa bật camera/mic
- Browser chưa cấp quyền
- Media tracks chưa được add vào peer connection

**Giải pháp:**
```javascript
// Kiểm tra local stream
console.log('Local stream tracks:', localStream.getTracks());

// Kiểm tra remote stream
peerConnection.ontrack = (event) => {
  console.log('Remote stream tracks:', event.streams[0].getTracks());
};

// Kiểm tra quyền browser
navigator.permissions.query({name: 'camera'}).then(result => {
  console.log('Camera permission:', result.state);
});
```

### 9.4. Lỗi Redis Connection

#### Vấn đề: Không thể kết nối Redis

**Nguyên nhân:**
- Redis server chưa chạy
- Credentials sai
- Network issues

**Giải pháp:**
```bash
# Kiểm tra Redis
redis-cli -h your-redis-host -p 18469 -a your-password ping

# Kiểm tra connection từ Node.js
node -e "const Redis = require('ioredis'); const r = new Redis({host: '...', port: 18469, password: '...'}); r.ping().then(console.log);"
```

### 9.5. Lỗi "Room không tồn tại"

#### Vấn đề: Join room bị reject

**Nguyên nhân:**
- Room đã hết hạn (TTL 24h)
- Room chưa được tạo
- Room ID sai

**Giải pháp:**
```bash
# Kiểm tra room trong Redis
redis-cli GET "videoroom:video_123_1234567890"

# Kiểm tra room trong MySQL
SELECT * FROM lich_goi_video WHERE room_id = 'video_123_1234567890';
```

### 9.6. Debug Tips

#### Enable verbose logging

**Node.js Server:**
```javascript
// Thêm vào server.js
process.env.DEBUG = 'socket.io:*';
```

**Client:**
```javascript
// Mở DevTools Console
localStorage.debug = 'socket.io-client:*';
```

#### Monitor WebRTC stats

```javascript
// Lấy stats từ peer connection
peerConnection.getStats().then(stats => {
  stats.forEach(report => {
    console.log(report);
  });
});
```

---

## 10. TỐI ƯU HÓA

### 10.1. Performance Optimization

#### 10.1.1. Video Quality Settings
```javascript
// Giảm resolution để tăng performance
const constraints = {
  video: {
    width: { ideal: 640 },   // Thay vì 1280
    height: { ideal: 480 },  // Thay vì 720
    frameRate: { ideal: 15, max: 30 }
  }
};
```

#### 10.1.2. Adaptive Bitrate
- Sử dụng RTCRtpSender.setParameters() để điều chỉnh bitrate
- Monitor network conditions và điều chỉnh tự động

#### 10.1.3. Connection Pooling
- Sử dụng connection pooling cho Redis
- Reuse Socket.IO connections

### 10.2. Scalability

#### 10.2.1. Load Balancing
- Sử dụng Nginx load balancer
- Multiple Socket.IO servers với Redis adapter
- Sticky sessions (session affinity)

#### 10.2.2. Redis Clustering
- Sử dụng Redis Cluster cho high availability
- Replication cho backup

### 10.3. Monitoring

#### 10.3.1. Metrics to Monitor
- Số lượng rooms đồng thời
- Số lượng active connections
- Average call duration
- Connection success rate
- WebRTC connection quality

#### 10.3.2. Logging
- Log tất cả join/leave events
- Log errors với stack traces
- Log WebRTC connection states

### 10.4. Best Practices

1. **Cleanup Resources**
   - Luôn cleanup peer connections khi disconnect
   - Stop media tracks khi không dùng
   - Xóa Redis keys khi room kết thúc

2. **Error Handling**
   - Try-catch cho tất cả async operations
   - Retry logic cho network errors
   - User-friendly error messages

3. **Security**
   - Validate tất cả inputs
   - Rate limiting cho API endpoints
   - HTTPS/WSS cho production

4. **Testing**
   - Test trên nhiều browsers
   - Test với network conditions khác nhau
   - Test với firewall/NAT

---

## PHỤ LỤC

### A. Cấu trúc thư mục

```
ServiceRealtime/
├── server.js                 # Entry point
├── config/
│   └── redisClient.js        # Redis configuration
├── sockets/
│   ├── socketHandler.js      # Main socket handler
│   └── videoCallHandler.js  # Video call handler
└── services/
    ├── redisHandler.js       # Redis pub/sub handler
    └── redisSub.js           # Redis subscriber

epiccinema.io.vn/
├── customer/
│   └── js/
│       └── video-call.js    # Frontend video call logic
└── src/
    ├── Views/
    │   └── customer/
    │       └── video-call.blade.php  # Video call UI
    ├── Services/
    │   └── Sc_GoiVideo.php   # Video call service
    └── Controllers/
        └── Ctrl_GoiVideo.php # Video call controller
```

### B. Environment Variables Checklist

**ServiceRealtime/.env:**
- [ ] PORT
- [ ] URL_WEB
- [ ] URL_SERVER_REALTIME
- [ ] REDIS_HOST
- [ ] REDIS_PORT
- [ ] REDIS_USERNAME
- [ ] REDIS_PASSWORD
- [ ] URL_API

**epiccinema.code/.env:**
- [ ] URL_SERVER_REALTIME
- [ ] URL_WEB_BASE
- [ ] REDIS_HOST
- [ ] REDIS_PORT
- [ ] REDIS_USERNAME
- [ ] REDIS_PASSWORD

### C. Useful Commands

```bash
# Start Node.js server
cd ServiceRealtime && npm start

# Start với PM2
pm2 start server.js --name epic-realtime

# View logs
pm2 logs epic-realtime

# Restart server
pm2 restart epic-realtime

# Check Redis connection
redis-cli -h your-host -p 18469 -a your-password ping

# Monitor Redis keys
redis-cli --scan --pattern "videoroom:*"

# Test TURN server
turnutils_stunclient epiccinema.io.vn
```

---

**Tài liệu được cập nhật lần cuối:** 2025-01-XX  
**Phiên bản:** 1.0.0  
**Tác giả:** EPIC Cinema Development Team

