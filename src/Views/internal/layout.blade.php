<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{$_ENV['URL_INTERNAL_BASE']}}/css/tailwind.css">
    @yield('head')
    <title>@yield('title') - EPIC CINEMA</title>
    <style>
        /* Ẩn thanh cuộn cho tất cả các trình duyệt */
        html, body {
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE và Edge */
        }
        
        /* Ẩn thanh cuộn cho Chrome, Safari và Opera */
        ::-webkit-scrollbar {
            display: none;
        }
    </style>
    <script>
        window.config = {
            url: "{{ $_ENV['URL_WEB_BASE'] }}",
            socketUrl: "{{ $_ENV['URL_SERVER_REALTIME'] }}",
            urlServerMinio: "{{ $_ENV['URL_SERVER_MINIO'] }}",
        }
    </script>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
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
                        <div class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 hidden" role="menu" aria-orientation="vertical" aria-labelledby="user-menu-button" id="user-menu">
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
    <div class="flex-1 overflow-auto">
        <!-- Breadcrumb -->
        <div class="bg-white shadow-sm">
            <div class="max-w-full mx-auto py-3 px-4 sm:px-6 lg:px-8">
                <nav class="flex" aria-label="Breadcrumb">
                    <ol class="flex items-center space-x-4">
                        <li>
                            <div>
                                <a href="{{$_ENV['URL_INTERNAL_BASE']}}/bang-dieu-khien" class="text-gray-400 hover:text-gray-500">
                                    <svg class="flex-shrink-0 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                                    </svg>
                                    <span class="sr-only">Trang chủ</span>
                                </a>
                            </div>
                        </li>
                        @yield('breadcrumbs')
                    </ol>
                </nav>
            </div>
        </div>
        
        <!-- Page Content -->
        <main class="max-w-full mx-auto py-6 px-4 sm:px-6 lg:px-8">
            @yield('content')
        </main>
    </div>
    
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
</body>
</html>