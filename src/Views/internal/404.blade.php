<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>404 Not Found</title>
    <link rel="stylesheet" href="{{$_ENV['URL_INTERNAL_BASE']}}/css/tailwind.css">
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center">
    <div class="text-center bg-white p-10 rounded-2xl shadow-xl max-w-lg">
        <h1 class="text-6xl font-extrabold text-red-500 mb-4">404</h1>
        <p class="text-xl text-gray-700 mb-6">Trang bạn yêu cầu không tồn tại.</p>
        <a href="{{$_ENV['URL_INTERNAL_BASE']}}/bang-dieu-khien" class="bg-blue-600 text-white px-6 py-2 rounded-full hover:bg-blue-700 transition">Về bảng điều khiển</a>
    </div>
</body>
</html>