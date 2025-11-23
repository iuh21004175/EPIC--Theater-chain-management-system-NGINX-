<?php
    // Bắt đầu output buffering để tránh output trước JSON
    ob_start();
    
    // Tắt hiển thị lỗi để tránh output trước JSON
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    
    try {
        session_start();
        
        // CORS Headers để cho phép Socket.IO server gọi API
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Content-Type: application/json');
        
        // Handle preflight OPTIONS request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            ob_end_clean();
            exit;
        }
        
        $uri = $_SERVER['REQUEST_URI'];
        if (stripos($uri, '/api/') === 0) {
            $_SERVER['REQUEST_URI'] = str_ireplace('/api/', '/', $uri);
        }
        
        require __DIR__ . '/../vendor/autoload.php';
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__.'/..');
        $dotenv->load();
        require __DIR__ . '/../config/database.php';
        
        // Xóa output buffer trước khi include routes
        ob_clean();
        
        require __DIR__ . '/../routes/apiv1.php';
        
        // Flush output buffer
        ob_end_flush();
        
    } catch (\Throwable $e) {
        // Xóa output buffer nếu có lỗi
        ob_clean();
        
        // Trả về JSON error
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Internal server error',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], JSON_UNESCAPED_UNICODE);
        
        // Log lỗi
        error_log("API Error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        
        ob_end_flush();
        exit;
    }
?>