<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>405 Method Not Allowed</title>
    <link rel="stylesheet" href="{{$_ENV['URL_INTERNAL_BASE']}}/css/tailwind.css">
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center">
    <div class="text-center bg-white p-10 rounded-2xl shadow-xl max-w-lg">
        <h1 class="text-6xl font-extrabold text-yellow-500 mb-4">405</h1>
        <p class="text-xl text-gray-700 mb-6">Phương thức HTTP không được hỗ trợ cho URL này.</p>
        <a href="{{$_ENV['URL_INTERNAL_BASE']}}/" class="bg-blue-600 text-white px-6 py-2 rounded-full hover:bg-blue-700 transition">Về trang chủ</a>
    </div>
</body>
</html>