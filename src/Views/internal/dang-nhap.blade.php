<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{$_ENV['URL_INTERNAL_BASE']}}/css/tailwind.css">
    <script type="module" src="{{$_ENV['URL_INTERNAL_BASE']}}/js/dang-nhap.js"></script>
    <title>Đăng nhập</title>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white p-10 rounded-xl shadow-lg">
        <div class="text-center">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">EPIC CINEMA</h1>
            <h2 class="text-xl font-semibold text-red-600">Hệ thống quản lý chuỗi rạp chiếu phim</h2>
            <div class="mt-4">
                <svg class="h-16 w-16 mx-auto text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                </svg>
            </div>
        </div>

        <form action="{{$_ENV['URL_WEB_BASE']}}/api/dang-nhap" method="POST" class="mt-8 space-y-6" id="form-dang-nhap">
            <!-- Thông báo lỗi nếu có -->
            <div class="text-red-500 text-center hidden">
                Tên đăng nhập hoặc mật khẩu không đúng
            </div>
            
            <div class="rounded-md -space-y-px">
                <div class="mb-4">
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Tên đăng nhập</label>
                    <input id="username" name="TenDangNhap" type="text" required autocomplete="off"
                           class="appearance-none relative block w-full px-3 py-2 border border-gray-300 
                           placeholder-gray-500 text-gray-900 rounded-md focus:outline-none 
                           focus:ring-red-500 focus:border-red-500 focus:z-10 sm:text-sm">
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Mật khẩu</label>
                    <input id="password" name="MatKhau" type="password" required 
                           class="appearance-none relative block w-full px-3 py-2 border border-gray-300 
                           placeholder-gray-500 text-gray-900 rounded-md focus:outline-none 
                           focus:ring-red-500 focus:border-red-500 focus:z-10 sm:text-sm">
                </div>
            </div>

            <div class="flex">
                <a href="{{$_ENV['URL_INTERNAL_BASE']}}/quen-mat-khau" class="text-sm text-red-600 hover:underline">
                    Quên mật khẩu?
                </a>
            </div>

            <div>
                <button type="submit" 
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent 
                        text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 
                        focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition duration-150 ease-in-out">
                    Đăng nhập
                </button>
            </div>
        </form>
        
        <div class="text-center text-sm text-gray-500 mt-4">
            &copy; 2025 - Hệ thống quản lý chuỗi rạp chiếu phim
        </div>
    </div>
</body>
</html>