<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Epic Cinema</title>
    <link rel="stylesheet" href="{{$_ENV['URL_WEB_BASE']}}/css/tailwind.css">
    <style>    
        .btn-outline-primary-custom {
            color: #007bff;
            background-color: transparent;
            border: 1px solid #007bff;
        }

        .btn-outline-primary-custom:hover {
            color: #fff;
            background-color: #007bff;
            border-color: #007bff;
        }

        nav a {
            text-decoration: none !important;
        }

        nav a:hover {
            text-decoration: none !important;
        }

        /* Modal chính */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
            justify-content: center;
            align-items: center;
        }

        /* Nội dung của modal */
        .modal-content {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 90%;
            position: relative;
            z-index: 1001;
        }

        /* Class để hiển thị modal */
        .modal.is-open {
            display: flex;
        }

        /* Vô hiệu hóa thanh cuộn của body khi modal mở */
        body.modal-open {
            overflow: hidden;
        }

        .modal-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            border-bottom: 1px solid #e5e5e5;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }

        .modal-header .close {
            position: absolute;
            right: 15px;
            top: 15px;
            font-size: 1.5rem;
            font-weight: bold;
            color: #aaa;
            cursor: pointer;
            background: none;
            border: none;
        }

        .modal-header .close:hover {
            color: #000;
        }

        .modal-body {
            padding-bottom: 15px;
        }

        .modal-footer {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            border-top: 1px solid #e5e5e5;
            padding-top: 15px;
        }
        /* Toast animation */
    @keyframes slideIn {
      0% { transform: translateX(100%); opacity: 0; }
      100% { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
      0% { transform: translateX(0); opacity: 1; }
      100% { transform: translateX(100%); opacity: 0; }
    }
        .toast { animation: slideIn 0.5s forwards; }
    .toast-hide { animation: slideOut 0.5s forwards; }
    
    /* Chatbot AI Button Styles - Nút trong header */
    .ai-chatbot-btn-header {
        display: flex;
        align-items: center;
        gap: 6px;
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        border: none;
        border-radius: 8px;
        padding: 8px 14px;
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
        transition: all 0.3s ease;
        color: white;
    }
    
    .ai-chatbot-btn-header:hover {
        background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
        transform: translateY(-1px);
    }
    
    .ai-chatbot-btn-header:active {
        transform: translateY(0);
    }
    
    .ai-chatbot-btn-header svg {
        width: 18px;
        height: 18px;
        flex-shrink: 0;
    }
    
    .ai-chatbot-label-header {
        color: white;
        font-weight: 600;
        font-size: 14px;
        white-space: nowrap;
    }
    
    /* Chatbot AI Button Styles - Nút cố định (nếu cần) */
    .ai-chatbot-btn {
        position: relative;
        display: flex;
        align-items: center;
        gap: 12px;
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        border: none;
        border-radius: 50px;
        padding: 12px 20px;
        cursor: pointer;
        box-shadow: 0 4px 15px rgba(37, 99, 235, 0.4);
        transition: all 0.3s ease;
        z-index: 50;
    }
    
    .ai-chatbot-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(37, 99, 235, 0.5);
        background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
    }
    
    .ai-chatbot-btn:active {
        transform: translateY(0);
    }
    
    .ai-chatbot-icon {
        width: 44px;
        height: 44px;
        flex-shrink: 0;
        position: relative;
    }
    
    .ai-chatbot-icon svg {
        filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
        animation: pulse-glow 2s ease-in-out infinite;
    }
    
    @keyframes pulse-glow {
        0%, 100% {
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
        }
        50% {
            filter: drop-shadow(0 2px 8px rgba(255, 255, 255, 0.6));
        }
    }
    
    .ai-chatbot-label-left {
        color: white;
        font-weight: 600;
        font-size: 15px;
        white-space: nowrap;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    }
    
    /* Chatbox Panel Animation */
    @keyframes fade-in {
        from {
            opacity: 0;
            transform: translateY(20px) scale(0.95);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }
    
    .animate-fade-in {
        animation: fade-in 0.3s ease-out;
    }
    
    /* Quick Messages Topics Styles */
    .topic-btn {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        border: 1px solid #e5e7eb;
        background: white;
        color: #6b7280;
        cursor: pointer;
        transition: all 0.2s ease;
        white-space: nowrap;
        flex-shrink: 0;
    }
    
    .topic-btn:hover {
        background: #f3f4f6;
        border-color: #d1d5db;
        color: #374151;
    }
    
    .topic-btn.active {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        color: white;
        border-color: #2563eb;
        box-shadow: 0 2px 4px rgba(37, 99, 235, 0.3);
    }
    
    /* Scrollbar Styles */
    .scrollbar-thin::-webkit-scrollbar {
        width: 6px;
    }
    
    .scrollbar-thin::-webkit-scrollbar-track {
        background: transparent;
    }
    
    .scrollbar-thin::-webkit-scrollbar-thumb {
        background-color: #93c5fd;
        border-radius: 3px;
    }
    
    .scrollbar-thin::-webkit-scrollbar-thumb:hover {
        background-color: #60a5fa;
    }
    
    /* Nút đóng chatbox */
    .btn-close-chatbox {
        position: relative;
        z-index: 10;
        cursor: pointer;
        background: rgba(255, 255, 255, 0.1);
        border: 2px solid rgba(255, 255, 255, 0.3);
        min-width: 40px;
        min-height: 40px;
        display: flex !important;
        align-items: center;
        justify-content: center;
        line-height: 1;
    }
    
    .btn-close-chatbox:hover {
        background: rgba(220, 38, 38, 0.9) !important;
        border-color: rgba(255, 255, 255, 0.5);
        transform: scale(1.1);
    }
    
    .btn-close-chatbox:active {
        transform: scale(0.95);
    }
    
    /* Responsive cho mobile */
    @media (max-width: 768px) {
        /* Ẩn nút chatbot trong header trên mobile */
        nav #ai-chatbox {
            display: none;
        }
        
        .ai-chatbot-btn {
            padding: 10px 16px;
            gap: 8px;
        }
        
        .ai-chatbot-icon {
            width: 40px;
            height: 40px;
        }
        
        .ai-chatbot-label-left {
            font-size: 13px;
        }
        
        #chatbox-panel {
            width: calc(100vw - 20px) !important;
            right: 10px !important;
            bottom: 80px !important;
            height: calc(100vh - 100px) !important;
            max-height: 500px !important;
        }
    }
    </style>
    <script>
            window.config = {
                url: "{{ $_ENV['URL_WEB_BASE'] }}",
                socketUrl: "{{ $_ENV['URL_SERVER_REALTIME'] }}",
            }
    </script>
</head>
<body class="bg-gray-100">
    <div class="chat-container" id="notifyBox">
        <div class="chat-header">Tin nhắn mới từ Epic</div>
        <div id="messages" class="chat-messages"></div>
    </div>

    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; }

        /* Box mini chat thông báo */
        .chat-container {
        width: 320px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        display: none; /* Ẩn mặc định */
        flex-direction: column;
        overflow: hidden;
        position: fixed;
        bottom: 20px;
        right: 20px;
        opacity: 1;
        transition: opacity 0.5s;
        z-index: 9999;
        }

        .chat-header {
        background: #0084ff;
        color: white;
        padding: 10px;
        font-weight: bold;
        text-align: center;
        font-size: 14px;
        }

        .chat-messages {
        padding: 10px;
        background: #f9f9f9;
        min-height: 60px;
        display: flex;
        flex-direction: column;
        gap: 8px;
        }

        .message {
        display: inline-block;
        padding: 8px 12px;
        border-radius: 18px;
        max-width: 80%;
        word-wrap: break-word;
        font-size: 14px;
        }
        .message.left {
        background: #e5e5ea;
        color: black;
        align-self: flex-start;
        border-bottom-left-radius: 4px;
        }
        .message.right {
        background: #0084ff;
        color: white;
        align-self: flex-end;
        border-bottom-right-radius: 4px;
        }
    </style>
<header class="bg-white shadow-md sticky top-0 z-50">
    <div class="container mx-auto max-w-screen-xl px-4 py-2 flex justify-between items-center">
        <a href="/">
            <img src="https://res.cloudinary.com/dtkm5uyx1/image/upload/v1756391269/logo_cinema_z2pcda.jpg" alt="Cinema Logo" class="h-14">
        </a>
        <nav class="hidden md:flex items-center space-x-8 relative">
            <a href="{{$_ENV['URL_WEB_BASE']}}/" class="text-gray-600 hover:text-red-600 font-semibold text-base transition duration-300 no-underline">Trang chủ</a>
            <!-- <a href="{{$_ENV['URL_WEB_BASE']}}/phim" class="text-gray-600 hover:text-red-600 font-semibold text-base transition duration-300 no-underline">Phim</a> -->
            <div class="relative group" id="phim-dropdown">
                <button class="text-gray-600 hover:text-red-600 font-semibold flex items-center gap-1">
                    Phim
                    <svg class="w-4 h-4 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div id="phim-menu" class="absolute left-0 top-full bg-white border border-gray-200 rounded-md shadow-lg min-w-[250px] max-w-[400px] w-auto z-50 transition duration-300 ease-in-out opacity-0 group-hover:opacity-100 invisible group-hover:visible">
                    
                </div>
            </div>
            <div class="relative group" id="rap-dropdown">
                <button class="text-gray-600 hover:text-red-600 font-semibold flex items-center gap-1">
                    Rạp
                    <svg class="w-4 h-4 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div id="rap-menu" class="absolute left-0 top-full bg-white border border-gray-200 rounded-md shadow-lg min-w-[250px] max-w-[400px] w-auto z-50 transition duration-300 ease-in-out opacity-0 group-hover:opacity-100 invisible group-hover:visible">
                </div>
            </div>
            <a href="{{$_ENV['URL_WEB_BASE']}}/goc-dien-anh" class="text-gray-600 hover:text-red-600 font-semibold text-base transition duration-300 no-underline">Góc điện ảnh</a>
            <a href="{{$_ENV['URL_WEB_BASE']}}/san-pham" class="text-gray-600 hover:text-red-600 font-semibold text-base transition duration-300 no-underline">Sản phẩm</a>
            <div class="relative group" id="tin-tuc-dropdown">
                <button class="text-gray-600 hover:text-red-600 font-semibold flex items-center gap-1">
                    Tin tức
                    <svg class="w-4 h-4 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div id="tin-tuc-menu" class="absolute left-0 top-full bg-white border border-gray-200 rounded-md shadow-lg min-w-[250px] max-w-[400px] w-auto z-50 transition duration-300 ease-in-out opacity-0 group-hover:opacity-100 invisible group-hover:visible">
                    <a class="block px-4 py-2 text-gray-700 hover:bg-red-600 hover:text-white whitespace-nowrap" href="{{$_ENV['URL_WEB_BASE']}}/tin-tuc">Tất cả tin tức</a>
                </div>
            </div>
            <a href="{{$_ENV['URL_WEB_BASE']}}/lich-chieu" class="text-gray-600 hover:text-red-600 font-semibold text-base transition duration-300 no-underline">Xem phim trực tuyến</a>
            <div class="relative group" id="rap-dropdown">
                <button class="text-gray-600 hover:text-red-600 font-semibold flex items-center gap-1">
                    Tư vấn
                    <svg class="w-4 h-4 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div id="tu-van-menu" class="absolute left-0 top-full bg-white border border-gray-200 rounded-md shadow-lg min-w-[250px] max-w-[400px] w-auto z-50 transition duration-300 ease-in-out opacity-0 group-hover:opacity-100 invisible group-hover:visible">
                    <a class="block px-4 py-2 text-gray-700 hover:bg-red-600 hover:text-white whitespace-nowrap" href="{{$_ENV['URL_WEB_BASE']}}/tu-van/chat-truc-tuyen">Chat trực tuyến</a>
                    <a class="block px-4 py-2 text-gray-700 hover:bg-red-600 hover:text-white whitespace-nowrap" href="{{$_ENV['URL_WEB_BASE']}}/tu-van/goi-video">Gọi video</a>
                </div>
            </div>
        </nav>
        <div id="user-area">
            <?php if (isset($_SESSION['user'])): 
                $user = $_SESSION['user']; 
            ?>
                <!-- Hidden input để Pusher notify -->
                <input type="hidden" id="userid" value="<?= htmlspecialchars($user['id']) ?>">

                <!-- Dropdown user -->
                <div id="user-dropdown" class="relative group">
                    <button id="btn-user" class="flex items-center gap-2 bg-gray-100 px-4 py-2 rounded-md hover:bg-gray-200 transition">
                        <span id="user-name"><?= htmlspecialchars($user['ho_ten']) ?></span>
                        <svg class="w-4 h-4 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div id="dropdown-menu" class="absolute right-0 w-48 bg-white border border-gray-200 rounded-md shadow-lg z-50 opacity-0 invisible transition-opacity duration-300 group-hover:opacity-100 group-hover:visible">
                        <div class="px-4 py-2 text-gray-500 border-b border-gray-200">
                            Xin chào, <?= htmlspecialchars($user['ho_ten']) ?>
                        </div>
                        <a href="{{ $_ENV['URL_WEB_BASE'] }}/thong-tin-ca-nhan" class="block px-4 py-2 text-gray-700 hover:bg-red-600 hover:text-white">Thông tin cá nhân</a>
                        <a href="{{ $_ENV['URL_WEB_BASE'] }}/ve-cua-toi" class="block px-4 py-2 text-gray-700 hover:bg-red-600 hover:text-white">Vé của tôi</a>
                        <a href="{{ $_ENV['URL_WEB_BASE'] }}/the-qua-tang" class="block px-4 py-2 text-gray-700 hover:bg-red-600 hover:text-white">Thẻ quà tặng</a>
                        <a href="{{ $_ENV['URL_WEB_BASE'] }}/doi-mat-khau" class="block px-4 py-2 text-gray-700 hover:bg-red-600 hover:text-white">Đổi mật khẩu</a>
                        <a href="{{ $_ENV['URL_WEB_BASE'] }}/dang-xuat" class="block px-4 py-2 text-gray-700 hover:bg-red-600 hover:text-white">Đăng xuất</a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Nút đăng nhập -->
                <button id="btn-login" class="bg-red-600 text-white font-bold py-2 px-5 rounded-md hover:bg-red-700 transition duration-300 text-sm">
                    Đăng nhập
                </button>
            <?php endif; ?>
        </div>

    </div>  
    </div>
</header>

<!-- Chatbox Panel (Fixed position) -->
<div id="chatbox-panel" class="hidden bg-white rounded-2xl shadow-2xl w-96 h-[500px] flex flex-col overflow-hidden border border-blue-200 animate-fade-in fixed bottom-24 right-10 z-50" data-url="{{$_ENV['URL_WEB_BASE']}}">
    <!-- Header -->
    <div class="flex items-center justify-between bg-gradient-to-r from-blue-600 to-blue-700 text-white px-4 py-3 rounded-t-2xl relative">
        <div class="flex items-center gap-2">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z"></path>
                <path d="M15 7v2a4 4 0 01-4 4H9.828l-1.766 1.767c.28.149.599.233.938.233h2l3 3v-3h2a2 2 0 002-2V9a2 2 0 00-2-2h-1z"></path>
            </svg>
            <span class="font-semibold text-base">Epic AI Chatbot</span>
        </div>
        <button id="btn-close-chat" class="btn-close-chatbox text-white text-3xl font-bold leading-none hover:text-red-300 hover:bg-red-600 rounded-full w-10 h-10 flex items-center justify-center transition-all duration-200 shadow-lg hover:shadow-xl hover:scale-110" title="Đóng chat" aria-label="Đóng chatbox">
            &times;
        </button>
    </div>
    
    <!-- Messages Area -->
    <div id="chatbox-messages" class="flex-1 p-4 overflow-y-auto text-sm bg-gradient-to-b from-blue-50 to-white scrollbar-thin scrollbar-thumb-blue-300 scrollbar-track-transparent">
        <div class="text-center text-gray-400 text-xs py-2">
            <p>Chào mừng bạn đến với Epic AI Chatbot!</p>
            <p class="mt-1">Hãy đặt câu hỏi để được hỗ trợ.</p>
        </div>
    </div>
    
    <!-- Quick Messages Area -->
    <div id="quick-messages-container" class="bg-blue-50 border-t border-blue-100">
        <div id="quick-topics" class="px-2 pt-2"></div>
        <div id="quick-messages" class="p-2 overflow-x-auto whitespace-nowrap">
            <div class="flex space-x-2 pb-1">
                <!-- Các nút sẽ được tạo bởi JavaScript -->
            </div>
        </div>
    </div>
    
    <!-- Input Form -->
    <form id="chatbox-form" class="flex border-t border-blue-200 bg-white p-3 rounded-b-2xl">
        <input id="chatbox-input" autocomplete="off" type="text" class="flex-1 px-4 py-2.5 rounded-l-full border border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-blue-400 text-sm" placeholder="Nhập câu hỏi của bạn...">
        <button class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-5 py-2.5 rounded-r-full hover:from-blue-700 hover:to-blue-800 transition-all shadow-md hover:shadow-lg font-medium text-sm" type="submit">
            <svg class="w-5 h-5 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
            </svg>
        </button>
    </form>
</div>

<div id="modalLogin" class="modal">
    <div class="modal-content">
        <form action="{{ $_ENV['URL_WEB_BASE'] }}/api/dang-nhap-khach-hang" id="loginForm" name='formDangNhap' method="POST">
            <div class="modal-header">
                <img src="https://res.cloudinary.com/dtkm5uyx1/image/upload/v1756390333/icon-login.fbbf1b2d_qfrlwb.svg" alt="Login Icon" class="mb-2" style="width:190px; height:120px;">
                <h5 class="modal-title w-100 text-lg font-bold">Đăng Nhập Tài Khoản</h5>
                <button type="button" class="close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group mb-4">
                    <label class="block text-gray-700">Email</label>
                    <input type="text" id="loginEmail" name="loginEmail" class="form-control w-full border border-gray-300 rounded-md p-2 mt-1" placeholder="Nhập Email">
                    <span id="tbLoginEmail" class="text-red-500 text-sm"></span>
                </div>
                <div class="form-group mb-4">
                    <label class="block text-gray-700">Mật khẩu</label>
                    <input type="password" id="loginPassword" name="loginPassword" class="form-control w-full border border-gray-300 rounded-md p-2 mt-1" placeholder="Nhập Mật khẩu">
                    <span id="tbLoginPassword" class="text-red-500 text-sm"></span>
                </div>
                <div class="form-group mb-4">
                    <button type="submit" class="btn btn-primary btn-block w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700 transition duration-300" name="btnLogin" id="btnLogin">Đăng nhập</button>
                </div>
                <div class="form-group mb-4">
                    <a href="<?= $_ENV['URL_WEB_BASE'] ?>/api/google" 
                        class="w-full bg-red-600 text-white font-semibold py-2 rounded-md text-center inline-block hover:bg-red-700 transition duration-300">
                        Đăng nhập bằng Google
                    </a>
                </div>
                <div class="form-group text-center">
                    <a href="#" id="btnForgotPassword" class="text-blue-600 hover:underline">Quên mật khẩu?</a>
                </div>
            </div>
            <div class="modal-footer">
                <p class="mb-2 text-gray-600">Bạn chưa có tài khoản?</p>
                <button type="button" class="btn btn-outline-primary-custom btn-block w-full py-2 rounded-md" id="btnRegister">Đăng ký</button>
            </div>
        </form>
    </div>
</div>

<div id="modalRegister" class="modal">
    <div class="modal-content">
        <form action="{{ $_ENV['URL_WEB_BASE'] }}/api/dang-ky" id="registerForm" name="formDangKy" method="POST">
            <div class="modal-header">
                
                <h5 class="modal-title w-100 text-lg font-bold">Đăng Ký Tài Khoản</h5>
                <button type="button" class="close">&times;</button>
            </div>
            <div class="modal-body space-y-4">
                <div class="form-group">
                    <label class="block text-gray-700 font-medium">Họ tên</label>
                    <input type="text" id="registerName" name="registerName" class="w-full border border-gray-300 rounded-md p-2 mt-1 focus:ring-blue-500 focus:border-blue-500" placeholder="Nhập Họ tên của bạn">
                    <span id="tbRegisterName" class="text-red-500 text-sm mt-1 block"></span>
                </div>

                <div class="form-group">
                    <label class="block text-gray-700 font-medium">Email</label>
                    <input type="email" id="registerEmail" name="registerEmail" class="w-full border border-gray-300 rounded-md p-2 mt-1 focus:ring-blue-500 focus:border-blue-500" placeholder="Nhập Email của bạn">
                    <span id="tbRegisterEmail" class="text-red-500 text-sm mt-1 block"></span>
                </div>

                <div class="flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
                    <div class="form-group flex-1">
                        <label for="sexSelect" class="block text-gray-700 font-medium">Giới tính</label>
                        <select id="sexSelect" name="sex" class="w-full border border-gray-300 rounded-md p-2 mt-1 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">-- Chọn --</option>
                            <option value="1">Nam</option>
                            <option value="0">Nữ</option>
                        </select>
                        <span id="tbSex" class="text-red-500 text-sm mt-1 block"></span>
                    </div>

                    <div class="form-group flex-1">
                        <label class="block text-gray-700 font-medium">Ngày sinh</label>
                        <input type="date" id="txtNgaySinh" name="txtNgaySinh" class="w-full border border-gray-300 rounded-md p-2 mt-1 focus:ring-blue-500 focus:border-blue-500">
                        <span id="tbNgaySinh" class="text-red-500 text-sm mt-1 block"></span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="block text-gray-700 font-medium">Số điện thoại</label>
                    <input type="text" id="registerPhone" name="registerPhone"
                        class="w-full border border-gray-300 rounded-md p-2 mt-1 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Nhập số điện thoại">
                    <span id="tbRegisterPhone" class="text-red-500 text-sm mt-1 block"></span>
                </div>

                <div class="flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
                    <!-- Mật khẩu -->
                    <div class="form-group flex-1">
                        <label class="block text-gray-700 font-medium">Mật khẩu</label>
                        <input type="password" id="registerPassword" name="registerPassword"
                            class="w-full border border-gray-300 rounded-md p-2 mt-1 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Tạo mật khẩu mạnh">
                        <span id="tbRegisterPassword" class="text-red-500 text-sm mt-1 block"></span>
                    </div>

                    <!-- Nhập lại mật khẩu -->
                    <div class="form-group flex-1">
                        <label class="block text-gray-700 font-medium">Nhập lại mật khẩu</label>
                        <input type="password" id="registerPasswordConfirm" name="registerPasswordConfirm"
                            class="w-full border border-gray-300 rounded-md p-2 mt-1 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Nhập lại mật khẩu">
                        <span id="tbRegisterPasswordConfirm" class="text-red-500 text-sm mt-1 block"></span>
                    </div>
                </div>
                
                <div class="form-group flex items-center">
                    <input type="checkbox" id="termsCheckbox" class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <label for="termsCheckbox" class="ml-2 text-sm text-gray-600">
                        Tôi đồng ý với 
                        <a href="#" id="btnTerms" class="text-blue-600 hover:underline">Điều khoản dịch vụ</a>
                    </label>
                </div>
                <button type="button" class="w-full bg-blue-400 text-white font-semibold py-2 rounded-md transition duration-300 cursor-not-allowed" id="btnSave" disabled>
                    Đăng Ký
                </button>
            </div>
        </form>
        <div class="modal-footer">
            <p class="text-sm text-gray-500">
                Đã có tài khoản? <a href="#" id="btnBackToLogin" class="text-blue-600 hover:underline">Đăng nhập</a>
            </p>
        </div>
    </div>
</div>


<div id="modalForgotPassword" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <img src="https://res.cloudinary.com/dtkm5uyx1/image/upload/v1756390333/icon-login.fbbf1b2d_qfrlwb.svg" alt="Forgot Password Icon" class="mb-2" style="width:190px; height:120px;">
            <h5 class="modal-title w-100 text-lg font-bold">Quên Mật Khẩu</h5>
            <button type="button" class="close">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group mb-4">
                <label class="block text-gray-700">Email</label>
                <input type="text" id="forgotEmail" class="form-control w-full border border-gray-300 rounded-md p-2 mt-1" placeholder="Nhập Email">
                <span id="tbForgotEmail" class="text-red-500 text-sm"></span>
            </div>
            <div class="form-group">
                <button class="btn btn-primary btn-block w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700 transition duration-300" id="btnSendReset">Gửi yêu cầu</button>
            </div>
        </div>
    </div>
</div>

<div id="modalTerms" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title w-100 text-lg font-bold">Điều khoản dịch vụ</h5>
            <button type="button" class="close">&times;</button>
        </div>
        <div class="modal-body space-y-3 text-gray-700 max-h-[400px] overflow-y-auto">
            <p>Chào mừng bạn đến với <b>Epic Cinema</b>! Khi sử dụng dịch vụ của chúng tôi, bạn đồng ý với các điều khoản sau:</p>
            <ul class="list-disc list-inside space-y-2">
                <li>Không được chia sẻ tài khoản cho người khác.</li>
                <li>Phải đảm bảo thông tin đăng ký là chính xác.</li>
                <li>Tôn trọng các quy định khi đặt vé và sử dụng dịch vụ.</li>
                <li>Mọi hành vi gian lận sẽ bị khóa tài khoản ngay lập tức.</li>
            </ul>
            <p class="font-semibold">Nếu bạn có thắc mắc, vui lòng liên hệ bộ phận CSKH để được hỗ trợ.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition" id="btnCloseTerms">Đã hiểu</button>
        </div>
    </div>
</div>

</body>
<script>
    const baseUrl = "{{ $_ENV['URL_WEB_BASE'] }}";
    const salt = "{{ $_ENV['URL_SALT'] }}";
    const rapMenu = document.getElementById('rap-menu');
    const phimMenu = document.getElementById('phim-menu');
    const tinTucMenu = document.getElementById('tin-tuc-menu');
    function base64Encode(str) {
        return btoa(unescape(encodeURIComponent(str)));
    }
    function slugify(str) {
        return str
            .toLowerCase()
            .normalize("NFD").replace(/[\u0300-\u036f]/g, "") // bỏ dấu tiếng Việt
            .replace(/[^a-z0-9]+/g, "-") // thay ký tự đặc biệt thành "-"
            .replace(/^-+|-+$/g, ""); // bỏ dấu - thừa
    }
   
    if (rapMenu) {
        fetch(baseUrl + "/api/rap-phim-khach")
            .then(res => res.json())
            .then(data => {
                if (data.success && data.data.length > 0) {
                    data.data.forEach(rap => {
                        const encoded = base64Encode(rap.id + salt);
                        const a = document.createElement('a');
                        a.href = `${baseUrl}/rap/${slugify(rap.ten)}-${encoded}`;
                        a.textContent = rap.ten;
                        a.className = "block px-4 py-2 text-gray-700 hover:bg-red-600 hover:text-white whitespace-nowrap";
                        rapMenu.appendChild(a);
                    });
                } else {
                    rapMenu.innerHTML = `<div class="px-4 py-2 text-gray-500">Không có rạp nào</div>`;
                }
            })
            .catch(err => console.error('Lỗi load rạp:', err));
    }

    if (phimMenu) {
        fetch(baseUrl + "/api/loai-phim")
            .then(res => res.json())
            .then(data => {
                if (data.success && data.data.length > 0) {
                    phimMenu.innerHTML = "";
                    data.data.forEach(loai => {
                        const a = document.createElement("a");
                        a.href = `${baseUrl}/phim?theLoai=${loai.id}`; 
                        a.textContent = loai.ten;
                        a.className = "block px-4 py-2 text-gray-700 hover:bg-red-600 hover:text-white whitespace-nowrap";
                        phimMenu.appendChild(a);
                    });
                } else {
                    phimMenu.innerHTML = '<div class="px-4 py-2 text-gray-500">Không có thể loại</div>';
                }
            })
            .catch(err => console.error("Lỗi load thể loại:", err));
    }

    if (tinTucMenu) {
        fetch(baseUrl + "/api/rap-phim-khach")
            .then(res => res.json())
            .then(data => {
                if (data.success && data.data.length > 0) {
                    // Giữ lại link "Tất cả tin tức" đã có
                    const allLink = tinTucMenu.querySelector('a[href*="/tin-tuc"]');
                    tinTucMenu.innerHTML = '';
                    if (allLink) {
                        tinTucMenu.appendChild(allLink);
                    }
                    data.data.forEach(rap => {
                        const encoded = base64Encode(rap.id + salt);
                        const a = document.createElement('a');
                        a.href = `${baseUrl}/tin-tuc?rap=${encoded}`;
                        a.textContent = `Tin tức ${rap.ten}`;
                        a.className = "block px-4 py-2 text-gray-700 hover:bg-red-600 hover:text-white whitespace-nowrap";
                        tinTucMenu.appendChild(a);
                    });
                } else {
                    tinTucMenu.innerHTML = `<div class="px-4 py-2 text-gray-500">Không có rạp nào</div>`;
                }
            })
            .catch(err => console.error('Lỗi load rạp cho tin tức:', err));
    }


    document.getElementById('btnSendReset').addEventListener('click', function() {
        const btn = this; // nút
        const email = document.getElementById('forgotEmail').value.trim();
        const tbForgotEmail = document.getElementById('tbForgotEmail');

        if(email === '') {
            tbForgotEmail.textContent = 'Vui lòng nhập email.';
            return;
        }

        // đổi chữ nút
        const originalText = btn.textContent;
        btn.textContent = 'Đang gửi...';
        btn.disabled = true;

        fetch(baseUrl + "/api/reset-password", {  
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ email: email })
        })
        .then(res => res.json())
        .then(data => {
            tbForgotEmail.textContent = data.message; 
        })
        .catch(err => {
            console.error('Lỗi kiểm tra email / gửi mail:', err);
            tbForgotEmail.textContent = 'Có lỗi xảy ra, vui lòng thử lại.';
        })
        .finally(() => {
            // trả lại chữ nút
            btn.textContent = originalText;
            btn.disabled = false;
        });
        
    });

    document.querySelectorAll("nav a").forEach(link => {
        const currentPath = window.location.pathname.replace(/\/$/, "");
        const linkPath = new URL(link.href).pathname.replace(/\/$/, "");
        
        if (linkPath === currentPath) {
            link.classList.remove("text-gray-600", "hover:text-red-600");
            link.classList.add("text-red-600", "font-bold");
        }
    });

</scrip>
<script src="{{ $_ENV['URL_WEB_BASE'] }}/js/auth.js"></script>
</html>