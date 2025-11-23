<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đổi mật khẩu</title>
    <link rel="stylesheet" href="{{$_ENV['URL_INTERNAL_BASE']}}/css/tailwind.css">
    <script type="module" src="{{$_ENV['URL_INTERNAL_BASE']}}/js/doi-mat-khau.js"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white p-10 rounded-xl shadow-lg">
        <div class="text-center">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">EPIC CINEMA</h1>
            <h2 class="text-xl font-semibold text-red-600">Đổi mật khẩu</h2>
            <div class="mt-4">
                <svg class="h-16 w-16 mx-auto text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 11c1.104 0 2-.896 2-2V7a2 2 0 10-4 0v2c0 1.104.896 2 2 2zm6 2v6a2 2 0 01-2 2H8a2 2 0 01-2-2v-6a6 6 0 1112 0z" />
                </svg>
            </div>
        </div>
        <form id="form-doi-mat-khau" method="POST" action="{{$_ENV['URL_WEB_BASE']}}/api/doi-mat-khau" class="mt-8 space-y-6">
            <div>
                <label for="old-password" class="block text-sm font-medium text-gray-700 mb-1">Mật khẩu cũ</label>
                <input id="old-password" name="matkhau_cu" type="password" required autocomplete="current-password"
                       class="appearance-none relative block w-full px-3 py-2 border border-gray-300 
                       placeholder-gray-500 text-gray-900 rounded-md focus:outline-none 
                       focus:ring-red-500 focus:border-red-500 focus:z-10 sm:text-sm">
            </div>
            <div>
                <label for="new-password" class="block text-sm font-medium text-gray-700 mb-1">Mật khẩu mới</label>
                <input id="new-password" name="matkhau_moi" type="password" required autocomplete="new-password"
                       class="appearance-none relative block w-full px-3 py-2 border border-gray-300 
                       placeholder-gray-500 text-gray-900 rounded-md focus:outline-none 
                       focus:ring-red-500 focus:border-red-500 focus:z-10 sm:text-sm">
            </div>
            <div>
                <label for="confirm-password" class="block text-sm font-medium text-gray-700 mb-1">Xác nhận mật khẩu mới</label>
                <input id="confirm-password" name="xacnhan_matkhau_moi" type="password" required autocomplete="new-password"
                       class="appearance-none relative block w-full px-3 py-2 border border-gray-300 
                       placeholder-gray-500 text-gray-900 rounded-md focus:outline-none 
                       focus:ring-red-500 focus:border-red-500 focus:z-10 sm:text-sm">
            </div>
            <button type="submit" 
                    class="w-full py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 
                    focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition duration-150 ease-in-out">
                Đổi mật khẩu
            </button>
        </form>
        <div class="text-center text-sm text-gray-500 mt-4">
            &copy; 2025 - Hệ thống quản lý chuỗi rạp chiếu phim
        </div>
    </div>
</body>
</html>