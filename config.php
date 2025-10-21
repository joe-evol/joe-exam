<?php

session_start();
require_once 'conf.inc';

function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function getCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function getDB() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['error' => 'Database connection failed']));
        }
    }
    
    return $pdo;
}

// Log access for analytics
function logAccess() {
    try {
        $logFile = __DIR__ . '/logs/access.log';
        $datetime = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $endpoint = $_SERVER['REQUEST_URI'] ?? '';
        $method = $_SERVER['REQUEST_METHOD'] ?? '';
        
        $logLine = "[{$datetime}] IP:{$ip} UA:{$userAgent} {$method} {$endpoint}\n";
        
        // File locking for concurrent writes
        $fp = fopen($logFile, 'a');
        if ($fp) {
            flock($fp, LOCK_EX);
            fwrite($fp, $logLine);
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    } catch (Exception $e) {
    }
}

// JSON response helper
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Security: Prevent XSS
function escape($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
