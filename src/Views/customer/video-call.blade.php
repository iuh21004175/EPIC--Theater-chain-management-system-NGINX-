<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Call Tư Vấn - EPIC CINEMAS</title>
    <link rel="stylesheet" href="{{ $_ENV['URL_WEB_BASE'] }}/css/tailwind.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .video-container {
            aspect-ratio: 16/9;
        }
        .remote-video {
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .local-video {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .call-controls button:hover {
            transform: translateY(-2px);
        }
        .call-timer {
            font-variant-numeric: tabular-nums;
        }
    </style>
</head>
<body class="bg-gray-900 text-white min-h-screen">
    <!-- Header logo -->
    <header class="bg-black bg-opacity-50 border-b border-gray-800">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <div class="flex items-center">
                <a href="{{ $_ENV['URL_WEB_BASE'] }}">
                    <img src="https://res.cloudinary.com/dtkm5uyx1/image/upload/v1756391269/logo_cinema_z2pcda.jpg" alt="EPIC Cinemas Logo" class="h-8">
                </a>
                <span class="ml-4 text-sm text-gray-400">| Video Tư Vấn</span>
            </div>
            <div class="call-timer text-lg font-semibold">00:00</div>
        </div>
    </header>

    <!-- Hidden User Info - Auto-detect customer or staff -->
    @php
        // Tự động phát hiện user type từ session
        $userId = '';
        $userName = '';
        $userType = '';
        
        if (isset($_SESSION['user']['id'])) {
            // Khách hàng
            $userId = $_SESSION['user']['id'];
            $userName = $_SESSION['user']['ho_ten'] ?? 'Khách hàng';
            $userType = 'customer';
        } elseif (isset($_SESSION['UserInternal']['ID'])) {
            // Nhân viên
            $userId = $_SESSION['UserInternal']['ID'];
            $userName = $_SESSION['UserInternal']['Ten'] ?? 'Nhân viên';
            $userType = 'staff';
        }
    @endphp
    <input type="hidden" id="userid" value="{{ $userId }}">
    <input type="hidden" id="username" value="{{ $userName }}">
    <input type="hidden" id="usertype" value="{{ $userType }}">
    
    <!-- Màn hình chờ tham gia - ẨN mặc định -->
    <div id="waitingScreen" class="fixed inset-0 bg-gray-900 z-50 flex items-center justify-center hidden">
        <div class="text-center max-w-md mx-4">
            <div class="bg-gray-800 rounded-xl p-8 shadow-2xl">
                <div class="w-24 h-24 mx-auto mb-6 bg-red-600 rounded-full flex items-center justify-center">
                    <i class="fas fa-video text-4xl text-white"></i>
                </div>
                <h2 class="text-2xl font-bold mb-4">Sẵn sàng tham gia cuộc gọi?</h2>
                <p class="text-gray-400 mb-6">Nhấn nút bên dưới để tham gia cuộc gọi video với nhân viên tư vấn.</p>
                
                <div class="space-y-4">
                    <button id="joinCallBtn" class="w-full px-8 py-4 bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold transition-all duration-200 transform hover:scale-105">
                        <i class="fas fa-phone-alt mr-2"></i>
                        Tham gia cuộc gọi
                    </button>
                    
                    <a href="{{ $_ENV['URL_WEB_BASE'] }}" class="block w-full px-8 py-3 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Quay lại
                    </a>
                </div>
                
                <div class="mt-6 p-4 bg-gray-900 rounded-lg text-left">
                    <h4 class="text-sm font-semibold mb-2 text-yellow-500">
                        <i class="fas fa-info-circle mr-1"></i>
                        Lưu ý:
                    </h4>
                    <ul class="text-xs text-gray-400 space-y-1">
                        <li>• Bạn có thể tham gia cuộc gọi mà không cần bật camera/mic</li>
                        <li>• Chỉ bật camera/mic khi bạn muốn nói chuyện</li>
                        <li>• Kết nối internet ổn định để có trải nghiệm tốt nhất</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <main id="videoCallContainer" class="container mx-auto px-4 py-8 hidden">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Video chính -->
            <div class="lg:col-span-2">
                <div class="relative video-container bg-gray-800 rounded-xl overflow-hidden">
                    <video id="remoteVideo" class="remote-video w-full h-full object-cover" autoplay playsinline></video>
                    <div class="absolute top-4 left-4 bg-black bg-opacity-60 py-1 px-3 rounded-full flex items-center">
                        <div class="w-2 h-2 bg-red-500 rounded-full mr-2 animate-pulse"></div>
                        <span class="text-sm">LIVE</span>
                    </div>
                    <div id="remoteUserInfo" class="absolute bottom-4 left-4 bg-black bg-opacity-60 py-1 px-3 rounded-lg">
                        <span class="text-sm font-medium" id="remoteUserLabel">Đang kết nối...</span>
                    </div>
                    <div id="connectionStatus" class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 text-center bg-black bg-opacity-75 py-4 px-6 rounded-lg">
                        <div class="animate-spin rounded-full h-10 w-10 border-4 border-white border-t-transparent mx-auto mb-3"></div>
                        <p id="statusText">Đang kết nối...</p>
                    </div>
                </div>

                <!-- Điều khiển cuộc gọi -->
                <div class="call-controls mt-6 flex items-center justify-center space-x-6">
                    <button id="toggleMic" class="w-14 h-14 bg-gray-700 hover:bg-gray-600 transition-all duration-200 rounded-full flex items-center justify-center text-white">
                        <i class="fas fa-microphone text-xl"></i>
                    </button>
                    <button id="toggleVideo" class="w-14 h-14 bg-gray-700 hover:bg-gray-600 transition-all duration-200 rounded-full flex items-center justify-center text-white">
                        <i class="fas fa-video text-xl"></i>
                    </button>
                    <button id="shareScreen" class="w-14 h-14 bg-gray-700 hover:bg-gray-600 transition-all duration-200 rounded-full flex items-center justify-center text-white">
                        <i class="fas fa-desktop text-xl"></i>
                    </button>
                    <button id="endCall" class="w-16 h-16 bg-red-600 hover:bg-red-700 transition-all duration-200 rounded-full flex items-center justify-center text-white">
                        <i class="fas fa-phone-slash text-2xl"></i>
                    </button>
                </div>
            </div>

            <!-- Video nhỏ và thông tin cuộc gọi -->
            <div id="sidePanel" class="lg:col-span-1 space-y-6">
                <!-- Video của người dùng -->
                <div class="bg-gray-800 rounded-xl overflow-hidden">
                    <div class="relative video-container aspect-video">
                        <video id="localVideo" class="local-video w-full h-full object-cover" autoplay muted playsinline></video>
                        <div class="absolute bottom-3 left-3 bg-black bg-opacity-60 py-1 px-3 rounded-lg">
                            <span class="text-sm font-medium">Bạn</span>
                        </div>
                    </div>
                </div>

                <!-- Thông tin cuộc gọi -->
                <div class="bg-gray-800 rounded-xl p-4">
                    <h3 class="text-lg font-semibold mb-3">Thông tin cuộc gọi</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-400">Thời gian bắt đầu:</span>
                            <span id="callStartTime">{{ $roomInfo->thoi_gian_bat_dau ?? 'Chưa có' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">Chất lượng cuộc gọi:</span>
                            <span id="callQuality" class="text-green-500">Tốt</span>
                        </div>
                        <div class="flex justify-between">
                            @if(isset($_SESSION['user']['id']))
                                <span class="text-gray-400">Nhân viên tư vấn:</span>
                                <span id="advisorName">{{ $roomInfo->nhanvien->ten ?? 'Chưa có' }}</span>
                            @elseif(isset($_SESSION['UserInternal']['ID']))
                                <span class="text-gray-400">Khách hàng:</span>
                                <span id="advisorName">{{ $roomInfo->khachhang->ho_ten ?? 'Chưa có' }}</span>
                            @endif
                            
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modals -->
    <div id="callEndedModal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 hidden">
        <div class="bg-gray-800 rounded-xl p-6 max-w-md w-full mx-4 text-center">
            <div class="w-20 h-20 mx-auto mb-6 bg-red-100 rounded-full flex items-center justify-center">
                <i class="fas fa-phone-slash text-3xl text-red-600"></i>
            </div>
            <h3 class="text-xl font-bold mb-2">Cuộc gọi đã kết thúc</h3>
            <p class="text-gray-300 mb-6">Cảm ơn bạn đã sử dụng dịch vụ tư vấn của EPIC Cinema.</p>
            <div class="flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-3">
                @if(isset($_SESSION['user']['id'])){
                    <a href="{{ $_ENV['URL_WEB_BASE'] }}" class="flex-1 px-6 py-3 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-colors">
                        Quay về trang chủ
                    </a>
                }
                @elseif(isset($_SESSION['UserInternal']['ID'])){
                     <a href="{{ $_ENV['URL_WEB_BASE'] }}/internal/bang-dieu-khien" class="flex-1 px-6 py-3 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-colors">
                        Bảng điều khiển
                    </a>
                }
                <button id="callAgain" class="flex-1 px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    Gọi lại
                </button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        window.config = {
            socketUrl: "{{ $_ENV['URL_SERVER_REALTIME'] }}"
        };
    </script>
    <script src="https://webrtc.github.io/adapter/adapter-latest.js"></script>
    <script type="module" src="{{ $_ENV['URL_WEB_BASE'] }}/js/video-call.js"></script>
</body>
</html>