<?php
    // Import lớp Capsule
use Illuminate\Database\Capsule\Manager as Capsule;
date_default_timezone_set('Asia/Ho_Chi_Minh');
// Tạo một đối tượng Capsule mới
$capsule = new Capsule;

// Thêm thông tin kết nối CSDL
$capsule->addConnection([
    'driver'    => $_ENV['DB_CONNECTION'] ?? 'mysql',
    'host'      => $_ENV['DB_HOST'] ?? 'localhost',
    'database'  => $_ENV['DB_DATABASE'] ?? 'epic',
    'username'  => $_ENV['DB_USERNAME'] ?? 'admin_epic',
    'password'  => $_ENV['DB_PASSWORD'] ?? 'epic2025',
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_general_ci',
    'prefix'    => '',
]);

// Thiết lập để Capsule có thể được sử dụng ở mọi nơi (global)
$capsule->setAsGlobal();

// Khởi động Eloquent ORM
$capsule->bootEloquent();
?>