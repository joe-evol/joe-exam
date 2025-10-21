<?php
// fetch_data.php - Minimal data import from dummyjson.com
require_once '/../secret.inc';

$dbConfig = [
    'host' => DB_HOST,
    'port' => DB_PORT,
    'user' => DB_USER,
    'pass' => DB_PASS,
    'db' => DB_NAME
];

try {
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['db']};charset=utf8mb4",
        $dbConfig['user'],
        $dbConfig['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "Connected to database\n";
    
    // Fetch categories
    echo "Fetching categories...\n";
    $categoriesData = json_decode(file_get_contents('https://dummyjson.com/products/categories'), true);
    
    $stmt = $pdo->prepare("
        INSERT INTO joe_categories (slug, name) 
        VALUES (:slug, :name)
        ON DUPLICATE KEY UPDATE 
            name = VALUES(name)
    ");
    
    foreach ($categoriesData as $cat) {
        $stmt->execute([
            'slug' => $cat['slug'],
            'name' => $cat['name']
        ]);
    }
    echo "Categories imported: " . count($categoriesData) . "\n";
    
    // Fetch all products
    echo "Fetching products...\n";
    $productsData = json_decode(file_get_contents('https://dummyjson.com/products?limit=0'), true);
    
    $stmt = $pdo->prepare("
        INSERT INTO joe_products (title, price, description, category, thumbnail)
        VALUES (:title, :price, :description, :category, :thumbnail)
        ON DUPLICATE KEY UPDATE
            title = VALUES(title),
            price = VALUES(price),
            description = VALUES(description),
            category = VALUES(category),
            thumbnail = VALUES(thumbnail),
            updated_at = CURRENT_TIMESTAMP
    ");
    
    foreach ($productsData['products'] as $product) {
        $stmt->execute([
            'title' => $product['title'],
            'price' => $product['price'],
            'description' => $product['description'] ?? null,
            'category' => $product['category'] ?? null,
            'thumbnail' => $product['thumbnail'] ?? null
        ]);
    }
    
    echo "Products imported: " . count($productsData['products']) . "\n";
    echo "Done!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}