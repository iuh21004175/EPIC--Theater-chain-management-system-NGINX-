<?php
    namespace App\Core;

    use eftec\bladeone\BladeOne;
    use Aws\S3\S3Client;
    use Dotenv\Dotenv;
    // ...existing code...


    $dotenv = Dotenv::createImmutable(__DIR__.'/../..');
    $dotenv->load();
    // Define the view function using the global $blade variable
    function view($name, $data = [])
    {
        static $blade = null;
        if ($blade === null) {
            $views = __DIR__ . '/../../src/Views';
            $cache = __DIR__ . '/../../cache/views';
            $blade = new BladeOne($views, $cache, BladeOne::MODE_AUTO);
        }
        return $blade->run($name, $data);
    }
    function getS3Client() {
        static $s3Client = null;
        if ($s3Client === null) {
            // Cấu hình Client
            $s3Client = new S3Client([
                'version' => 'latest',
                'region'  => 'us-east-1', // Bắt buộc, nhưng không quan trọng với MinIO
                'endpoint' => $_ENV['MINIO_ENDPOINT'], // URL đến MinIO server của bạn
                'use_path_style_endpoint' => true, // Cực kỳ quan trọng!
                'credentials' => [
                    'key'    => $_ENV['MINIO_ACCESS_KEY'],     // Access Key bạn đã tạo
                    'secret' => $_ENV['MINIO_SECRET_KEY'], // Secret Key bạn đã tạo
                ],
            ]);

        }
        return $s3Client;
    }
    
    // Hàm này trả về một đối tượng Redis đã kết nối hoặc null nếu thất bại
function getRedisConnection() {
    // Biến static để giữ lại kết nối, chỉ tạo một lần duy nhất
    static $redis = null;
    static $checked = false;

    // Kiểm tra extension Redis có tồn tại không
    if (!$checked) {
        $checked = true;
        if (!class_exists('Redis')) {
            error_log("Cảnh báo: Redis class không tồn tại. Vui lòng cài đặt php-redis extension.");
            return null;
        }
    }

    if ($redis === null && class_exists('Redis')) {
        // --- Kết nối Redis bình thường (không SSL) ---
        $host = $_ENV['REDIS_HOST'] ?? 'redis-18469.crce194.ap-seast-1-1.ec2.redns.redis-cloud.com';
        $port = $_ENV['REDIS_PORT'] ?? 18469;
        $username = $_ENV['REDIS_USERNAME'] ?? 'default';
        $password = $_ENV['REDIS_PASSWORD'] ?? 'wVL6uW0sbgq4w6esirgrLnxiFZdO8UJV';

        try {
            $redis = new \Redis();
            // Kết nối không SSL
            $redis->pconnect($host, $port);

            // Nếu có username (Redis 6+ ACL), dùng auth với username và password
            if (!empty($username)) {
                $redis->auth([$username, $password]);
            } else {
                $redis->auth($password);
            }

            // Đặt timeout cho các thao tác đọc để tránh bị treo
            $redis->setOption(\Redis::OPT_READ_TIMEOUT, 2.5);

            // Kiểm tra kết nối
            $redis->ping('+OK');
            $redis->set('my_key', 'Hello from VPS!');

        } catch (\Exception $e) {
            error_log("Lỗi kết nối Redis Cloud: " . $e->getMessage());
            $redis = null; // Đảm bảo trả về null khi thất bại
            // KHÔNG throw exception, return null để app vẫn hoạt động
        }
    }

    return $redis;
}
?>