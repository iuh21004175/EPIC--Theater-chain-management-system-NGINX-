<?php
    session_start();
    $uri = $_SERVER['REQUEST_URI'];
    if (stripos($uri, '/internal') === 0) {
        $_SERVER['REQUEST_URI'] = str_ireplace('/internal', '', $uri);
    }
    
    require __DIR__ . '/../vendor/autoload.php';
    require __DIR__ . '/../config/database.php';
    require __DIR__ . '/../routes/internal.php';
?>