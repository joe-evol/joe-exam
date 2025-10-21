<?php
// api.php - All API endpoints in one file
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
error_log("API called: path=" . ($_GET['path'] ?? 'none'));
if (isset($_GET['search'])) {
    error_log("Search term: " . $_GET['search']);
}

// require_once '/config.php';
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        exit(json_encode(['error' => 'Unauthorized']));
    }
}

header('Content-Type: application/json');
logAccess();

$method = $_SERVER['REQUEST_METHOD'];
$path = $_GET['path'] ?? '';

// Rate limiting - max 100 requests per hour per IP
function checkRateLimit() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if ($ip === '127.0.0.1' || $ip === '::1') {
        return;
    }
    $key = 'ratelimit_' . $ip;
    $limit = 100;
    $window = 3600; // 1 hour
    
    $pdo = getDB();
    
    // Clean old entries
    $pdo->exec("DELETE FROM joe_rate_limits WHERE expires_at < NOW()");
    
    // Get current count
    $stmt = $pdo->prepare("SELECT requests FROM joe_rate_limits WHERE ip_address = :ip");
    $stmt->execute(['ip' => $ip]);
    $row = $stmt->fetch();
    
    if ($row && $row['requests'] >= $limit) {
        http_response_code(429);
        exit(json_encode(['error' => 'Rate limit exceeded. Try again later.']));
    }
    
    // Increment or create
    if ($row) {
        $pdo->prepare("UPDATE joe_rate_limits SET requests = requests + 1 WHERE ip_address = :ip")
            ->execute(['ip' => $ip]);
    } else {
        $pdo->prepare("INSERT INTO joe_rate_limits (ip_address, requests, expires_at) VALUES (:ip, 1, DATE_ADD(NOW(), INTERVAL :window SECOND))")
            ->execute(['ip' => $ip, 'window' => $window]);
    }
}

checkRateLimit();

try {
    $pdo = getDB();
    
    // GET /api.php?path=categories
    if ($path === 'categories' && $method === 'GET') {
        $stmt = $pdo->query("SELECT * FROM joe_categories ORDER BY name");
        jsonResponse($stmt->fetchAll());
    }
    
    // GET /api.php?path=products
    elseif ($path === 'products' && $method === 'GET') {
        $page = intval($_GET['page'] ?? 1);
        $limit = intval($_GET['limit'] ?? 20);
        $category = $_GET['category'] ?? null;
        $search = $_GET['search'] ?? null;
        $offset = ($page - 1) * $limit;
        
        $where = [];
        $params = [];
        
        if ($category) {
            $where[] = "category = :category";
            $params['category'] = $category;
        }
        
        if ($search) {
            $where[] = "title LIKE :search";
            $params['search'] = '%' . $search . '%';
            // jsonResponse([
            //     'debug' => true,
            //     'search' => $search,
            //     'params' => $params,
            //     'whereClause' => $whereClause,
            //     'sql' => "SELECT * FROM joe_products $whereClause ORDER BY id DESC LIMIT $limit OFFSET $offset"
            // ]);
        }
        
        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // Get total count
        $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM joe_products $whereClause");
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];
        
        $stmt = $pdo->prepare("
            SELECT * FROM joe_products 
            $whereClause 
            ORDER BY id DESC 
            LIMIT :limit OFFSET :offset
        ");

        $executeParams = $params;
        $executeParams['limit'] = $limit;
        $executeParams['offset'] = $offset;

        $stmt->execute($executeParams);
        
        jsonResponse([
            'products' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ]);
    }
    
    // POST /api.php?path=products 
    elseif ($path === 'products' && $method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        error_log("POST data: " . json_encode($data));
        error_log("Session token: " . ($_SESSION['csrf_token'] ?? 'none'));
        if (!isset($data['csrf_token'])) {
            jsonResponse(['error' => 'CSRF token missing'], 400);
        }
        if (!verifyCsrfToken($data['csrf_token'] ?? '')) {
            jsonResponse(['error' => 'Invalid CSRF token'], 403);
        }
        if (!$data || !isset($data['title'])) {
            jsonResponse(['error' => 'Title is required'], 400);
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO joe_products (title, description, category, price, thumbnail)
            VALUES (:title, :description, :category, :price, :thumbnail)
        ");
        
        $stmt->execute([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'] ?? null,
            'price' => $data['price'] ?? null,
            'thumbnail' => $data['thumbnail'] ?? null
        ]);
        
        jsonResponse(['success' => true, 'id' => $pdo->lastInsertId()], 201);
    }
    
    // GET /api.php?path=analytics
    elseif ($path === 'analytics' && $method === 'GET') {
        $date = $_GET['date'] ?? date('Y-m-d');
        $logFile = __DIR__ . '/../logs/access.log';

    
        if (!file_exists($logFile)) {
            jsonResponse(['date' => $date, 'data' => []]);
        }
        // Read and parse log file
        $logs = [];
        $fp = fopen($logFile, 'r');
        if ($fp) {
            while (($line = fgets($fp)) !== false) {
                // Parse: [2025-10-21 15:30:00] IP:123.45.67.89 ...
                if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}):\d{2}\] IP:([\d\.]+)/', $line, $matches)) {
                    $datetime = $matches[1] . ':00';
                    $ip = $matches[2];
                    
                    // Filter by date
                    if (strpos($datetime, $date) === 0) {
                        if (!isset($logs[$datetime])) {
                            $logs[$datetime] = [];
                        }
                        $logs[$datetime][] = $ip;
                    }
                }
            }
            fclose($fp);
        }

        // Aggregate by minute (count unique IPs)
        $data = [];
        foreach ($logs as $minute => $ips) {
            $data[] = [
                'minute' => $minute,
                'dau' => count(array_unique($ips))
            ];
        }
        
        // Sort by time
        usort($data, function($a, $b) {
            return strcmp($a['minute'], $b['minute']);
        });
        
        jsonResponse(['date' => $date, 'data' => $data]);
        
        // DAU by minute
        //     $stmt = $pdo->prepare("
        //         SELECT 
        //             DATE_FORMAT(access_time, '%Y-%m-%d %H:%i:00') as minute,
        //             COUNT(DISTINCT ip_address) as dau
        //         FROM joe_access_logs
        //         WHERE DATE(access_time) = :date
        //         GROUP BY minute
        //         ORDER BY minute
        //     ");
        //     $stmt->execute(['date' => $date]);
            
        //     jsonResponse([
        //         'date' => $date,
        //         'data' => $stmt->fetchAll()
        //     ]);
    }
    
    // POST /api.php?path=upload (Image upload placeholder)
    elseif ($path === 'upload' && $method === 'POST') {
        // TODO: Implement S3 upload
        jsonResponse(['error' => 'Upload not implemented yet'], 501);
    }
    
    else {
        jsonResponse(['error' => 'Endpoint not found'], 404);
    }
    
} catch (PDOException $e) {
    jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}

