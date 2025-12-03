<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{$_ENV['URL_INTERNAL_BASE']}}/css/tailwind.css">
    <title>Bảng điều khiển - EPIC CINEMA</title>
    @if($_SESSION['UserInternal']['VaiTro'] == 'Quản lý chuỗi rạp' || $_SESSION['UserInternal']['VaiTro'] == 'Quản lý rạp' || $_SESSION['UserInternal']['VaiTro'] == 'Nhân viên')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    @endif
    <style>
        .tooltip {
            position: relative;
        }
        .tooltip::before {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            padding: 5px 10px;
            background-color: rgba(0, 0, 0, 0.8);
            color: white;
            border-radius: 4px;
            font-size: 12px;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s, visibility 0.3s;
            z-index: 100;
            margin-bottom: 5px;
            pointer-events: none;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .tooltip:hover::before {
            opacity: 1;
            visibility: visible;
        }
    </style>
    <script>
        window.config = {
            url: "{{ $_ENV['URL_WEB_BASE'] }}",
            socketUrl: "{{ $_ENV['URL_SERVER_REALTIME'] }}",
            urlServerMinio: "{{ $_ENV['MINIO_SERVER_URL'] }}",
        }
    </script>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm z-10">
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="{{$_ENV['URL_INTERNAL_BASE']}}/bang-dieu-khien" class="flex items-center">
                        <svg class="h-8 w-8 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                        </svg>
                        <span class="ml-2 text-xl font-bold text-gray-900">EPIC CINEMA</span>
                    </a>
                </div>

                <!-- User Menu -->
                <div class="flex items-center">
                    <div class="relative ml-3">
                        <div>
                            <button type="button" class="flex items-center max-w-xs text-sm rounded-full focus:outline-none focus:shadow-outline" id="user-menu-button" aria-expanded="false" aria-haspopup="true">
                                <span class="mr-2 text-gray-700">{{$_SESSION['UserInternal']['Ten'] ? $_SESSION['UserInternal']['Ten'] : $_SESSION['UserInternal']['VaiTro']}}</span>
                                <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center text-gray-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </button>
                        </div>

                        <!-- Dropdown menu - hidden by default -->
                        <div class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 hidden" style="z-index: 1" role="menu" aria-orientation="vertical" aria-labelledby="user-menu-button" id="user-menu">
                            <a href="{{$_ENV['URL_INTERNAL_BASE']}}/thong-tin-ca-nhan" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                                Thông tin cá nhân
                            </a>
                            @if($_SESSION['UserInternal']['VaiTro'] == 'Nhân viên')
                            <a href="{{$_ENV['URL_INTERNAL_BASE']}}/dang-ky-khuon-mat" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                                Đăng ký khuôn mặt
                            </a>
                            @endif
                            <a href="{{$_ENV['URL_INTERNAL_BASE']}}/doi-mat-khau" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                                Đổi mật khẩu
                            </a>
                            <div class="border-t border-gray-100"></div>
                            <a href="{{$_ENV['URL_INTERNAL_BASE']}}/dang-xuat" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100" role="menuitem">
                                Đăng xuất
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h2 class="text-2xl font-semibold text-gray-800 mb-8">Chức năng</h2>
        
        <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-4">
            @if($_SESSION['UserInternal']['VaiTro'] == 'Admin')
            <!-- Quản lý banner -->
            <a href="{{$_ENV['URL_INTERNAL_BASE']}}/banner" class="tooltip flex flex-col items-center bg-white rounded-md shadow hover:shadow-md p-4 transition-all hover:translate-y-[-2px]" data-tooltip="Quản lý banner">
                <div class="w-12 h-12 flex items-center justify-center bg-sky-100 rounded-full mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <span class="text-xs text-center font-medium text-gray-700">Banner</span>
            </a>
            <!-- Quản lý tài khoản -->
            <a href="{{$_ENV['URL_INTERNAL_BASE']}}/tai-khoan" class="tooltip flex flex-col items-center bg-white rounded-md shadow hover:shadow-md p-4 transition-all hover:translate-y-[-2px]" data-tooltip="Quản lý tài khoản">
                <div class="w-12 h-12 flex items-center justify-center bg-fuchsia-100 rounded-full mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-fuchsia-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="text-xs text-center font-medium text-gray-700">Tài khoản</span>
            </a>
            @elseif($_SESSION['UserInternal']['VaiTro'] == 'Quản lý chuỗi rạp')
            <!-- Quản lý rạp phim -->
            <a href="{{$_ENV['URL_INTERNAL_BASE']}}/rap-phim" class="tooltip flex flex-col items-center bg-white rounded-md shadow hover:shadow-md p-4 transition-all hover:translate-y-[-2px]" data-tooltip="Quản lý danh sách rạp phim">
                <div class="w-12 h-12 flex items-center justify-center bg-indigo-100 rounded-full mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <span class="text-xs text-center font-medium text-gray-700">Rạp phim</span>
            </a>
            <!-- Quản lý phim -->
            <a href="{{$_ENV['URL_INTERNAL_BASE']}}/phim" class="tooltip flex flex-col items-center bg-white rounded-md shadow hover:shadow-md p-4 transition-all hover:translate-y-[-2px]" data-tooltip="Quản lý phim">
                <div class="w-12 h-12 flex items-center justify-center bg-red-100 rounded-full mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                    </svg>
                </div>
                <span class="text-xs text-center font-medium text-gray-700">Phim</span>
            </a>
            <!-- Quản lý loại ghế -->
            <a href="{{$_ENV['URL_INTERNAL_BASE']}}/ghe" class="tooltip flex flex-col items-center bg-white rounded-md shadow hover:shadow-md p-4 transition-all hover:translate-y-[-2px]" data-tooltip="Quản lý loại ghế">
                <div class="w-12 h-12 flex items-center justify-center bg-amber-100 rounded-full mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                    </svg>
                </div>
                <span class="text-xs text-center font-medium text-gray-700">Loại ghế</span>
            </a>
            <!-- Quản lý giá vé -->
            <a href="{{$_ENV['URL_INTERNAL_BASE']}}/gia-ve" class="tooltip flex flex-col items-center bg-white rounded-md shadow hover:shadow-md p-4 transition-all hover:translate-y-[-2px]" data-tooltip="Quản lý quy tắc giá vé">
                <div class="w-12 h-12 flex items-center justify-center bg-green-100 rounded-full mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                    </svg>
                </div>
                <span class="text-xs text-center font-medium text-gray-700">Giá vé</span>
            </a>
            <!-- Gán ngày -->
            <a href="{{$_ENV['URL_INTERNAL_BASE']}}/gan-ngay" class="tooltip flex flex-col items-center bg-white rounded-md shadow hover:shadow-md p-4 transition-all hover:translate-y-[-2px]" data-tooltip="Gán nhãn cho ngày">
                <div class="w-12 h-12 flex items-center justify-center bg-orange-100 rounded-full mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <span class="text-xs text-center font-medium text-gray-700">Ngày</span>
            </a>
            <!-- Thống kê toàn rạp -->
            <a href="{{$_ENV['URL_INTERNAL_BASE']}}/thong-ke-toan-rap" class="tooltip flex flex-col items-center bg-white rounded-md shadow hover:shadow-md p-4 transition-all hover:translate-y-[-2px]" data-tooltip="Thống kê toàn rạp">
                <div class="w-12 h-12 flex items-center justify-center bg-violet-100 rounded-full mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                    </svg>
                </div>
                <span class="text-xs text-center font-medium text-gray-700">Thống kê toàn rạp</span>
            </a>
            <!-- Duyệt suất chiếu -->
            <a href="{{$_ENV['URL_INTERNAL_BASE']}}/duyet-suat-chieu" class="tooltip flex flex-col items-center bg-white rounded-md shadow hover:shadow-md p-4 transition-all hover:translate-y-[-2px]" data-tooltip="Duyệt suất chiếu">
                <div class="w-12 h-12 flex items-center justify-center bg-lime-100 rounded-full mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-lime-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="text-xs text-center font-medium text-gray-700">Duyệt suất chiếu</span>
            </a>
            @elseif($_SESSION['UserInternal']['VaiTro'] == 'Quản lý rạp')
            <!-- Quản lý phòng chiếu -->
            <a href="{{$_ENV['URL_INTERNAL_BASE']}}/phong-chieu" class="tooltip flex flex-col items-center bg-white rounded-md shadow hover:shadow-md p-4 transition-all hover:translate-y-[-2px]" data-tooltip="Quản lý phòng chiếu">
                <div class="w-12 h-12 flex items-center justify-center bg-blue-100 rounded-full mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                    </svg>
                </div>
                <span class="text-xs text-center font-medium text-gray-700">Phòng chiếu</span>
            </a>
            <!-- Quản lý suất chiếu -->
            <a href="{{$_ENV['URL_INTERNAL_BASE']}}/suat-chieu" class="tooltip flex flex-col items-center bg-white rounded-md shadow hover:shadow-md p-4 transition-all hover:translate-y-[-2px]" data-tooltip="Quản lý suất chiếu">
                <div class="w-12 h-12 flex items-center justify-center bg-purple-100 rounded-full mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="text-xs text-center font-medium text-gray-700">Suất chiếu</span>
            </a>
            <!-- Quản lý nhân viên -->
            <a href="{{$_ENV['URL_INTERNAL_BASE']}}/nhan-vien" class="tooltip flex flex-col items-center bg-white rounded-md shadow hover:shadow-md p-4 transition-all hover:translate-y-[-2px]" data-tooltip="Quản lý nhân viên">
                <div class="w-12 h-12 flex items-center justify-center bg-yellow-100 rounded-full mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <span class="text-xs text-center font-medium text-gray-700">Nhân viên</span>
            </a>
            <!-- Phân công nhân viên -->
            <a href="{{$_ENV['URL_INTERNAL_BASE']}}/phan-cong" class="tooltip flex flex-col items-center bg-white rounded-md shadow hover:shadow-md p-4 transition-all hover:translate-y-[-2px]" data-tooltip="Phân công nhân viên">
                <div class="w-12 h-12 flex items-center justify-center bg-teal-100 rounded-full mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
                <span class="text-xs text-center font-medium text-gray-700">Phân công</span>
            </a>
            <!-- Quản lý sản phẩm ăn uống -->
            <a href="{{$_ENV['URL_INTERNAL_BASE']}}/san-pham-an-uong" class="tooltip flex flex-col items-center bg-white rounded-md shadow hover:shadow-md p-4 transition-all hover:translate-y-[-2px]" data-tooltip="Quản lý sản phẩm ăn uống">
                <div class="w-12 h-12 flex items-center justify-center bg-pink-100 rounded-full mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-pink-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <span class="text-xs text-center font-medium text-gray-700">Sản phẩm ăn uống</span>
            </a>
            <!-- Thống kê -->
            <a href="{{$_ENV['URL_INTERNAL_BASE']}}/thong-ke" class="tooltip flex flex-col items-center bg-white rounded-md shadow hover:shadow-md p-4 transition-all hover:translate-y-[-2px]" data-tooltip="Thống kê">
                <div class="w-12 h-12 flex items-center justify-center bg-lime-100 rounded-full mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-lime-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <span class="text-xs text-center font-medium text-gray-700">Thống kê</span>
            </a>
            <a href="{{$_ENV['URL_INTERNAL_BASE']}}/duyet-yeu-cau" 
                class="tooltip flex flex-col items-center bg-white rounded-md shadow hover:shadow-md p-4 transition-all hover:translate-y-[-2px]" 
                data-tooltip="Duyệt yêu cầu">
                    <div class="w-12 h-12 flex items-center justify-center bg-teal-100 rounded-full mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" 
                            class="h-6 w-6 text-teal-600" 
                            fill="none" viewBox="0 0 24 24" 
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                d="M9 12h6m2 0a2 2 0 002-2V7a2 2 0 00-2-2h-2l-2-2H9L7 5H5a2 2 0 00-2 2v3a2 2 0 002 2m14 0v7a2 2 0 01-2 2H7a2 2 0 01-2-2v-7h14z" />
                        </svg>
                    </div>
                    <span class="text-xs text-center font-medium text-gray-700">Duyệt yêu cầu</span>
            </a>
            <!-- Nút Quản lý tin tức -->
            <a href="{{ $_ENV['URL_INTERNAL_BASE'] }}/tin-tuc" 
                class="tooltip flex flex-col items-center bg-white rounded-md shadow hover:shadow-md p-4 transition-all hover:translate-y-[-2px]" 
                data-tooltip="Quản lý tin tức">
                <div class="w-12 h-12 flex items-center justify-center bg-yellow-100 rounded-full mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" 
                        class="h-6 w-6 text-yellow-600" 
                        fill="none" viewBox="0 0 24 24" 
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                            d="M19 11H5m14 4H5m14-8H5m2-2h10a2 2 0 012 2v12a2 2 0 01-2 2H7a2 2 0 01-2-2V7a2 2 0 012-2z" />
                    </svg>
                </div>
                <span class="text-xs text-center font-medium text-gray-700">Quản lý tin tức</span>
            </a>
            <a href="{{$_ENV['URL_INTERNAL_BASE']}}/quan-ly-luong" class="tooltip flex flex-col items-center bg-white rounded-md shadow hover:shadow-md p-4 transition-all hover:translate-y-[-2px]" data-tooltip="Quản lý lương">
                <div class="w-12 h-12 flex items-center justify-center bg-emerald-100 rounded-full mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="text-xs text-center font-medium text-gray-700">Quản lý lương</span>
            </a>
            <!-- Quản lý thông tin định vị -->
            <a href="{{$_ENV['URL_INTERNAL_BASE']}}/thong-tin-dinh-vi" class="tooltip flex flex-col items-center bg-white rounded-md shadow hover:shadow-md p-4 transition-all hover:translate-y-[-2px]" data-tooltip="Quản lý thông tin định vị chấm công">
                <div class="w-12 h-12 flex items-center justify-center bg-red-100 rounded-full mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <span class="text-xs text-center font-medium text-gray-700">Thông tin định vị</span>
            </a>
            @else
            <!-- Bán vé -->
            <a href="{{$_ENV['URL_INTERNAL_BASE']}}/ban-ve" class="tooltip flex flex-col items-center bg-white rounded-md shadow hover:shadow-md p-4 transition-all hover:translate-y-[-2px]" data-tooltip="Bán vé">
                <div class="w-12 h-12 flex items-center justify-center bg-rose-100 rounded-full mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                    </svg>
                </div>
                <span class="text-xs text-center font-medium text-gray-700">Bán vé</span>
            </a>
            <!-- Xem lịch làm việc -->
            <a href="{{$_ENV['URL_INTERNAL_BASE']}}/lich-lam-viec" class="tooltip flex flex-col items-center bg-white rounded-md shadow hover:shadow-md p-4 transition-all hover:translate-y-[-2px]" data-tooltip="Xem lịch làm việc">
                <div class="w-12 h-12 flex items-center justify-center bg-cyan-100 rounded-full mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-cyan-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
                <span class="text-xs text-center font-medium text-gray-700">Xem lịch làm việc</span>
            </a>
            <!-- Xem lương -->
            <a href="{{$_ENV['URL_INTERNAL_BASE']}}/luong" class="tooltip flex flex-col items-center bg-white rounded-md shadow hover:shadow-md p-4 transition-all hover:translate-y-[-2px]" data-tooltip="Xem lương">
                <div class="w-12 h-12 flex items-center justify-center bg-emerald-100 rounded-full mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="text-xs text-center font-medium text-gray-700">Xem lương</span>
            </a>
            <!-- Chấm công -->
            <a href="{{$_ENV['URL_INTERNAL_BASE']}}/cham-cong" class="tooltip flex flex-col items-center bg-white rounded-md shadow hover:shadow-md p-4 transition-all hover:translate-y-[-2px]" data-tooltip="Chấm công">
                <div class="w-12 h-12 flex items-center justify-center bg-amber-100 rounded-full mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="text-xs text-center font-medium text-gray-700">Chấm công</span>
            </a>
            <!-- Gửi yêu cầu -->
            <a href="{{$_ENV['URL_INTERNAL_BASE']}}/yeu-cau" 
                class="tooltip flex flex-col items-center bg-white rounded-md shadow hover:shadow-md p-4 transition-all hover:translate-y-[-2px]" 
                data-tooltip="Yêu cầu">
                    <div class="w-12 h-12 flex items-center justify-center bg-teal-100 rounded-full mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" 
                            class="h-6 w-6 text-teal-600" 
                            fill="none" viewBox="0 0 24 24" 
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                d="M9 12h6m2 0a2 2 0 002-2V7a2 2 0 00-2-2h-2l-2-2H9L7 5H5a2 2 0 00-2 2v3a2 2 0 002 2m14 0v7a2 2 0 01-2 2H7a2 2 0 01-2-2v-7h14z" />
                        </svg>
                    </div>
                    <span class="text-xs text-center font-medium text-gray-700">Gửi yêu cầu</span>
            </a>
            <!-- Chat -->
            <a href="{{$_ENV['URL_INTERNAL_BASE']}}/tu-van" 
                class="tooltip flex flex-col items-center bg-white rounded-md shadow hover:shadow-md p-4 transition-all hover:translate-y-[-2px]" 
                data-tooltip="Tư vấn khách hàng">
                    <div class="w-12 h-12 flex items-center justify-center bg-indigo-100 rounded-full mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" 
                            class="h-6 w-6 text-indigo-600" 
                            fill="none" 
                            viewBox="0 0 24 24" 
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8-1.486 0-2.882-.324-4.057-.889L3 20l1.356-3.215C3.486 15.62 3 13.865 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                    </div>
                    <span class="text-xs text-center font-medium text-gray-700">Tư vấn</span>
            </a>
            <!-- Quản lý đơn hàng -->
            <a href="{{$_ENV['URL_INTERNAL_BASE']}}/don-hang"
            class="tooltip flex flex-col items-center bg-white rounded-md shadow hover:shadow-md p-4 transition-all hover:translate-y-[-2px]"
            data-tooltip="Quản lý đơn hàng">
                <div class="w-12 h-12 flex items-center justify-center bg-blue-100 rounded-full mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="h-6 w-6 text-blue-600"
                        fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 3h18l-2 13H5L3 3zm3 16a2 2 0 104 0 2 2 0 00-4 0zm10 0a2 2 0 104 0 2 2 0 00-4 0z" />
                    </svg>
                </div>
                <span class="text-xs text-center font-medium text-gray-700">Quản lý đơn hàng</span>
            </a>

            <!-- Quản lý khách hàng -->
            <a href="{{$_ENV['URL_INTERNAL_BASE']}}/khach-hang" 
                class="tooltip flex flex-col items-center bg-white rounded-md shadow hover:shadow-md p-4 transition-all hover:translate-y-[-2px]" 
                data-tooltip="Quản lý khách hàng">
                <div class="w-12 h-12 flex items-center justify-center bg-purple-100 rounded-full mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" 
                        class="h-6 w-6 text-purple-600" 
                        fill="none" viewBox="0 0 24 24" 
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                            d="M16 14a4 4 0 10-8 0v4h8v-4zM12 2a4 4 0 100 8 4 4 0 000-8z" />
                    </svg>
                </div>
                <span class="text-xs text-center font-medium text-gray-700">Quản lý thành viên</span>
            </a>
            @endif    
            
        </div>

        @if($_SESSION['UserInternal']['VaiTro'] == 'Quản lý chuỗi rạp')
        <!-- Phần thống kê cho Quản lý chuỗi rạp -->
        <div class="mt-10">
            <h2 class="text-2xl font-semibold text-gray-800 mb-6 flex items-center">
                <svg class="w-6 h-6 mr-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                Thống kê tổng quan
            </h2>
            
            <!-- KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Tổng doanh thu -->
                <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-xl shadow-lg p-6 border-2 border-red-200">
                    <div class="flex items-center justify-between mb-4">
                        <div class="bg-red-500 rounded-lg p-3">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <span class="text-xs font-semibold text-red-600 bg-red-200 px-2 py-1 rounded">Tháng này</span>
                    </div>
                    <h3 class="text-sm font-medium text-gray-600 mb-1">Tổng doanh thu</h3>
                    <p class="text-2xl font-bold text-gray-800" id="tong-doanh-thu">0</p>
                    <p class="text-xs text-gray-500 mt-2" id="tong-doanh-thu-change"></p>
                </div>

                <!-- Tổng vé bán -->
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl shadow-lg p-6 border-2 border-blue-200">
                    <div class="flex items-center justify-between mb-4">
                        <div class="bg-blue-500 rounded-lg p-3">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
                            </svg>
                        </div>
                        <span class="text-xs font-semibold text-blue-600 bg-blue-200 px-2 py-1 rounded">Tháng này</span>
                    </div>
                    <h3 class="text-sm font-medium text-gray-600 mb-1">Tổng vé bán</h3>
                    <p class="text-2xl font-bold text-gray-800" id="tong-ve-ban">0</p>
                    <p class="text-xs text-gray-500 mt-2" id="tong-ve-ban-change"></p>
                </div>

                <!-- Tỉ lệ lấp đầy -->
                <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl shadow-lg p-6 border-2 border-green-200">
                    <div class="flex items-center justify-between mb-4">
                        <div class="bg-green-500 rounded-lg p-3">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <span class="text-xs font-semibold text-green-600 bg-green-200 px-2 py-1 rounded">Tháng này</span>
                    </div>
                    <h3 class="text-sm font-medium text-gray-600 mb-1">Tỉ lệ lấp đầy</h3>
                    <p class="text-2xl font-bold text-gray-800" id="ty-le-lap-day">0%</p>
                    <p class="text-xs text-gray-500 mt-2" id="ty-le-lap-day-change"></p>
                </div>

                <!-- Doanh thu F&B -->
                <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl shadow-lg p-6 border-2 border-purple-200">
                    <div class="flex items-center justify-between mb-4">
                        <div class="bg-purple-500 rounded-lg p-3">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <span class="text-xs font-semibold text-purple-600 bg-purple-200 px-2 py-1 rounded">Tháng này</span>
                    </div>
                    <h3 class="text-sm font-medium text-gray-600 mb-1">Doanh thu F&B</h3>
                    <p class="text-2xl font-bold text-gray-800" id="doanh-thu-fnb">0</p>
                    <p class="text-xs text-gray-500 mt-2" id="doanh-thu-fnb-change"></p>
                </div>
            </div>

            <!-- Biểu đồ -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Biểu đồ doanh thu theo thời gian -->
                <div class="bg-white rounded-xl shadow-lg p-6 border-2 border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Xu hướng doanh thu</h3>
                    <div id="chart-doanh-thu" style="height: 300px;"></div>
                </div>

                <!-- Biểu đồ doanh thu theo rạp -->
                <div class="bg-white rounded-xl shadow-lg p-6 border-2 border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Doanh thu theo rạp</h3>
                    <div id="chart-doanh-thu-rap" style="height: 300px;"></div>
                </div>
            </div>

            <!-- Top phim và Top sản phẩm -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Top 5 phim -->
                <div class="bg-white rounded-xl shadow-lg p-6 border-2 border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Top 5 phim doanh thu cao nhất</h3>
                    <div id="top-phim-list" class="space-y-3">
                        <div class="flex items-center justify-center py-8">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-red-600"></div>
                        </div>
                    </div>
                </div>

                <!-- Top 5 sản phẩm -->
                <div class="bg-white rounded-xl shadow-lg p-6 border-2 border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Top 5 sản phẩm bán chạy</h3>
                    <div id="top-san-pham-list" class="space-y-3">
                        <div class="flex items-center justify-center py-8">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-red-600"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
        @if($_SESSION['UserInternal']['VaiTro'] == 'Quản lý rạp')
        <!-- Phần thống kê cho Quản lý rạp -->
        <div class="mt-10">
            <h2 class="text-2xl font-semibold text-gray-800 mb-6 flex items-center">
                <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                Thống kê rạp
            </h2>
            
            <!-- KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Tổng doanh thu -->
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl shadow-lg p-6 border-2 border-blue-200">
                    <div class="flex items-center justify-between mb-4">
                        <div class="bg-blue-500 rounded-lg p-3">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <span class="text-xs font-semibold text-blue-600 bg-blue-200 px-2 py-1 rounded">Tháng này</span>
                    </div>
                    <h3 class="text-sm font-medium text-gray-600 mb-1">Tổng doanh thu</h3>
                    <p class="text-2xl font-bold text-gray-800" id="tong-doanh-thu-rap">0</p>
                    <p class="text-xs text-gray-500 mt-2" id="tong-doanh-thu-rap-change"></p>
                </div>

                <!-- Tổng vé bán -->
                <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 rounded-xl shadow-lg p-6 border-2 border-indigo-200">
                    <div class="flex items-center justify-between mb-4">
                        <div class="bg-indigo-500 rounded-lg p-3">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
                            </svg>
                        </div>
                        <span class="text-xs font-semibold text-indigo-600 bg-indigo-200 px-2 py-1 rounded">Tháng này</span>
                    </div>
                    <h3 class="text-sm font-medium text-gray-600 mb-1">Tổng vé bán</h3>
                    <p class="text-2xl font-bold text-gray-800" id="tong-ve-ban-rap">0</p>
                    <p class="text-xs text-gray-500 mt-2" id="tong-ve-ban-rap-change"></p>
                </div>

                <!-- Tỉ lệ lấp đầy -->
                <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 rounded-xl shadow-lg p-6 border-2 border-emerald-200">
                    <div class="flex items-center justify-between mb-4">
                        <div class="bg-emerald-500 rounded-lg p-3">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <span class="text-xs font-semibold text-emerald-600 bg-emerald-200 px-2 py-1 rounded">Tháng này</span>
                    </div>
                    <h3 class="text-sm font-medium text-gray-600 mb-1">Tỉ lệ lấp đầy</h3>
                    <p class="text-2xl font-bold text-gray-800" id="ty-le-lap-day-rap">0%</p>
                    <p class="text-xs text-gray-500 mt-2" id="ty-le-lap-day-rap-change"></p>
                </div>

                <!-- Doanh thu F&B -->
                <div class="bg-gradient-to-br from-violet-50 to-violet-100 rounded-xl shadow-lg p-6 border-2 border-violet-200">
                    <div class="flex items-center justify-between mb-4">
                        <div class="bg-violet-500 rounded-lg p-3">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <span class="text-xs font-semibold text-violet-600 bg-violet-200 px-2 py-1 rounded">Tháng này</span>
                    </div>
                    <h3 class="text-sm font-medium text-gray-600 mb-1">Doanh thu F&B</h3>
                    <p class="text-2xl font-bold text-gray-800" id="doanh-thu-fnb-rap">0</p>
                    <p class="text-xs text-gray-500 mt-2" id="doanh-thu-fnb-rap-change"></p>
                </div>
            </div>

            <!-- Biểu đồ -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Biểu đồ doanh thu theo thời gian -->
                <div class="bg-white rounded-xl shadow-lg p-6 border-2 border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Xu hướng doanh thu</h3>
                    <div id="chart-doanh-thu-rap" style="height: 300px;"></div>
                </div>

                <!-- Biểu đồ phân bổ doanh thu -->
                <div class="bg-white rounded-xl shadow-lg p-6 border-2 border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Phân bổ doanh thu</h3>
                    <div id="chart-phan-bo-doanh-thu-rap" style="height: 300px;"></div>
                </div>
            </div>

            <!-- Top phim và Top sản phẩm -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Top 5 phim -->
                <div class="bg-white rounded-xl shadow-lg p-6 border-2 border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Top 5 phim doanh thu cao nhất</h3>
                    <div id="top-phim-list-rap" class="space-y-3">
                        <div class="flex items-center justify-center py-8">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                        </div>
                    </div>
                </div>

                <!-- Top 5 sản phẩm -->
                <div class="bg-white rounded-xl shadow-lg p-6 border-2 border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Top 5 sản phẩm bán chạy</h3>
                    <div id="top-san-pham-list-rap" class="space-y-3">
                        <div class="flex items-center justify-center py-8">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
        @if($_SESSION['UserInternal']['VaiTro'] == 'Nhân viên')
        <!-- Phần thống kê cho Nhân viên -->
        <div class="mt-10">
            <h2 class="text-2xl font-semibold text-gray-800 mb-6 flex items-center">
                <svg class="w-6 h-6 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                Thống kê cá nhân
            </h2>
            
            <!-- KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Tổng doanh thu -->
                <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl shadow-lg p-6 border-2 border-green-200">
                    <div class="flex items-center justify-between mb-4">
                        <div class="bg-green-500 rounded-lg p-3">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <span class="text-xs font-semibold text-green-600 bg-green-200 px-2 py-1 rounded">Tháng này</span>
                    </div>
                    <h3 class="text-sm font-medium text-gray-600 mb-1">Tổng doanh thu</h3>
                    <p class="text-2xl font-bold text-gray-800" id="tong-doanh-thu-nv">0</p>
                    <p class="text-xs text-gray-500 mt-2" id="tong-doanh-thu-nv-change"></p>
                </div>

                <!-- Tổng vé bán -->
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl shadow-lg p-6 border-2 border-blue-200">
                    <div class="flex items-center justify-between mb-4">
                        <div class="bg-blue-500 rounded-lg p-3">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
                            </svg>
                        </div>
                        <span class="text-xs font-semibold text-blue-600 bg-blue-200 px-2 py-1 rounded">Tháng này</span>
                    </div>
                    <h3 class="text-sm font-medium text-gray-600 mb-1">Tổng vé bán</h3>
                    <p class="text-2xl font-bold text-gray-800" id="tong-ve-ban-nv">0</p>
                    <p class="text-xs text-gray-500 mt-2" id="tong-ve-ban-nv-change"></p>
                </div>

                <!-- Tổng đơn hàng -->
                <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl shadow-lg p-6 border-2 border-purple-200">
                    <div class="flex items-center justify-between mb-4">
                        <div class="bg-purple-500 rounded-lg p-3">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        </div>
                        <span class="text-xs font-semibold text-purple-600 bg-purple-200 px-2 py-1 rounded">Tháng này</span>
                    </div>
                    <h3 class="text-sm font-medium text-gray-600 mb-1">Tổng đơn hàng</h3>
                    <p class="text-2xl font-bold text-gray-800" id="tong-don-hang-nv">0</p>
                    <p class="text-xs text-gray-500 mt-2" id="tong-don-hang-nv-change"></p>
                </div>
            </div>

            <!-- Biểu đồ -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Biểu đồ xu hướng doanh thu -->
                <div class="bg-white rounded-xl shadow-lg p-6 border-2 border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Xu hướng doanh thu</h3>
                    <div id="chart-doanh-thu-nv" style="height: 300px;"></div>
                </div>

                <!-- Top 5 phim bán chạy -->
                <div class="bg-white rounded-xl shadow-lg p-6 border-2 border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Top 5 phim bán chạy</h3>
                    <div id="top-phim-list-nv" class="space-y-3">
                        <div class="flex items-center justify-center py-8">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </main>
    <script>
        // Toggle User Menu
        document.getElementById('user-menu-button').addEventListener('click', function() {
            document.getElementById('user-menu').classList.toggle('hidden');
        });
        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            const userMenu = document.getElementById('user-menu');
            const userMenuButton = document.getElementById('user-menu-button');
            if (!userMenuButton.contains(event.target) && !userMenu.contains(event.target)) {
                userMenu.classList.add('hidden');
            }
        });
    </script>
        
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ... existing code ...
        });
    </script>
    @if($_SESSION['UserInternal']['VaiTro'] == 'Quản lý chuỗi rạp')
    <script type="module" src="{{$_ENV['URL_INTERNAL_BASE']}}/js/bang-dieu-khien-thong-ke.js"></script>
    @elseif($_SESSION['UserInternal']['VaiTro'] == 'Quản lý rạp')
    <script type="module" src="{{$_ENV['URL_INTERNAL_BASE']}}/js/bang-dieu-khien-thong-ke-rap.js"></script>
    @elseif($_SESSION['UserInternal']['VaiTro'] == 'Nhân viên')
    <script type="module" src="{{$_ENV['URL_INTERNAL_BASE']}}/js/bang-dieu-khien-thong-ke-nv.js"></script>
    @endif
</body>
</html>