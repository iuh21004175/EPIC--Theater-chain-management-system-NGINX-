<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chưa đăng nhập</title>
    <link rel="stylesheet" href="{{$_ENV['URL_INTERNAL_BASE']}}/css/tailwind.css">
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center">
    <div class="bg-white p-10 rounded-2xl shadow-xl text-center max-w-md">
        <h1 class="text-4xl font-bold text-red-500 mb-4">Bạn chưa đăng nhập</h1>
        <p class="text-gray-700 text-lg mb-6">Vui lòng đăng nhập để tiếp tục truy cập trang này.</p>
        <a href="./dang-nhap" class="bg-blue-600 text-white px-6 py-2 rounded-full hover:bg-blue-700 transition">
            Đăng nhập ngay
        </a>
    </div>
</body>
</html>