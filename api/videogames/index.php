<?php

require_once './crud/get_delete.php';
require_once './crud/post.php';
require_once './crud/put.php';
require_once  __DIR__ .  '/crud/get_delete.php';
require_once  __DIR__ .  '/crud/post.php';
require_once  __DIR__ .  '/crud/put.php';

try {
    header('Content-Type: application/json');

    $method = $_SERVER['REQUEST_METHOD'];

    // Se è POST con override nel body o header, sovrascrivi il metodo
    if ($method === 'POST') {
        if (isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        } elseif (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
            $method = strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
        }
    }

    switch ($method) {
        case 'GET':
            handleGet($pdo);
            break;
        case 'POST':
            handlePost($pdo);
            break;
        case 'PUT':
            handlePut($pdo);
            break;
        case 'DELETE':
            handleDelete($pdo);
            break;
        default:
            http_response_code(405); // Metodo non consentito
            echo json_encode(['error' => 'Metodo non supportato']);
    }
} catch (Throwable $e) {

    // header('Content-Type: text/html; charset=utf-8');
    echo json_encode(['error' => $e->getMessage()]);
}
