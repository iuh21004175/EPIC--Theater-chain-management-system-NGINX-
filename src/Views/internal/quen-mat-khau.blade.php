<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên mật khẩu</title>
    <link rel="stylesheet" href="{{$_ENV['URL_INTERNAL_BASE']}}/css/tailwind.css">
    <script type="module" src="{{$_ENV['URL_INTERNAL_BASE']}}/js/quen-mat-khau.js"></script>
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
        <form id="forgot-password-form" method="POST" action="{{$_ENV['URL_WEB_BASE']}}/api/nhan-vien-quen-mat-khau" class="mt-8 space-y-6">
            <div class="mb-4">
                <label for="email" class="block text-sm font-medium mb-1">Email nhân viên</label>
                <input type="email" id="email" name="email" required class="w-full border rounded px-3 py-2" placeholder="Nhập email của bạn">
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 font-semibold">
                Gửi yêu cầu lấy lại mật khẩu
            </button>
        </form>
        <div class="mt-6 text-sm text-gray-700 bg-yellow-50 border-l-4 border-yellow-400 p-3 rounded">
            <strong>Lưu ý:</strong> Chỉ tài khoản <span class="font-semibold text-blue-700">nhân viên</span> mới sử dụng được chức năng này.<br>
            Nếu bạn là <span class="font-semibold text-red-600">quản lý</span>, vui lòng liên hệ với <span class="font-semibold">quản trị viên</span> để xin lại mật khẩu.
        </div>
        <div class="text-center text-sm text-gray-500 mt-4">
            &copy; 2025 - Hệ thống quản lý chuỗi rạp chiếu phim
        </div>
    </div>
</body>
</html>