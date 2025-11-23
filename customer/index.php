<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $uri = $_SERVER['REQUEST_URI'];
    if (stripos($uri, '/rapphim') === 0) {
        $_SERVER['REQUEST_URI'] = str_ireplace('/rapphim', '', $uri);
    }
    
    require __DIR__ . '/../vendor/autoload.php';
    require __DIR__ . '/../config/database.php';
    require __DIR__ . '/../routes/customer.php';
?>