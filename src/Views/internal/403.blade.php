<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>403 Forbidden</title>
    <link rel="stylesheet" href="{{$_ENV['URL_INTERNAL_BASE']}}/css/tailwind.css">
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center">
    <div class="bg-white shadow-2xl p-10 rounded-2xl max-w-md text-center">
        <h1 class="text-5xl font-bold text-red-600 mb-4">403</h1>
        <p class="text-xl text-gray-700 mb-6">Bạn không có quyền truy cập trang này.</p>
        <a href="{{ $_ENV['URL_INTERNAL_BASE'] }}/" class="inline-block bg-blue-600 text-white px-6 py-2 rounded-full hover:bg-blue-700 transition">Quay về trang chủ</a>
    </div>
</body>
</html>
