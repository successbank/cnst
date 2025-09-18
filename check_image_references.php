<?php
require_once 'db.php';

echo "<h2>Checking for image references in database</h2>";

// Check products table
echo "<h3>Products table structure:</h3>";
$stmt = $pdo->query("DESCRIBE products");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "<pre>";
print_r(array_filter($columns, function($col) {
    return strpos(strtolower($col), 'image') !== false || 
           strpos(strtolower($col), 'img') !== false ||
           strpos(strtolower($col), 'photo') !== false ||
           strpos(strtolower($col), 'pic') !== false;
}));
echo "</pre>";

// Check products with image paths
echo "<h3>Products with image data:</h3>";
$imageColumns = [];
foreach ($columns as $col) {
    if (strpos(strtolower($col), 'image') !== false || 
        strpos(strtolower($col), 'img') !== false ||
        strpos(strtolower($col), 'photo') !== false) {
        $imageColumns[] = $col;
    }
}

if (!empty($imageColumns)) {
    foreach ($imageColumns as $imgCol) {
        $stmt = $pdo->query("SELECT id, product_name, $imgCol FROM products WHERE $imgCol IS NOT NULL AND $imgCol != '' LIMIT 10");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($products)) {
            echo "<h4>Column: $imgCol</h4>";
            echo "<table border='1'>";
            echo "<tr><th>ID</th><th>Name</th><th>Image Path</th></tr>";
            foreach ($products as $product) {
                echo "<tr>";
                echo "<td>{$product['id']}</td>";
                echo "<td>{$product['product_name']}</td>";
                echo "<td>{$product[$imgCol]}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
}

// Check product_categories table
echo "<h3>Product Categories table structure:</h3>";
$stmt = $pdo->query("DESCRIBE product_categories");
$catColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "<pre>";
print_r(array_filter($catColumns, function($col) {
    return strpos(strtolower($col), 'image') !== false || 
           strpos(strtolower($col), 'img') !== false ||
           strpos(strtolower($col), 'icon') !== false;
}));
echo "</pre>";

// Check categories with image paths
$catImageColumns = [];
foreach ($catColumns as $col) {
    if (strpos(strtolower($col), 'image') !== false || 
        strpos(strtolower($col), 'img') !== false ||
        strpos(strtolower($col), 'icon') !== false) {
        $catImageColumns[] = $col;
    }
}

if (!empty($catImageColumns)) {
    foreach ($catImageColumns as $imgCol) {
        $stmt = $pdo->query("SELECT category_code, category_name, $imgCol FROM product_categories WHERE $imgCol IS NOT NULL AND $imgCol != ''");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($categories)) {
            echo "<h4>Column: $imgCol</h4>";
            echo "<table border='1'>";
            echo "<tr><th>Code</th><th>Name</th><th>Image Path</th></tr>";
            foreach ($categories as $cat) {
                echo "<tr>";
                echo "<td>{$cat['category_code']}</td>";
                echo "<td>{$cat['category_name']}</td>";
                echo "<td>{$cat[$imgCol]}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
}

// Search for any 114 references in all text columns
echo "<h3>Searching for '114' references in products table:</h3>";
$textColumns = ['description', 'specifications', 'features', 'notes'];
foreach ($textColumns as $col) {
    if (in_array($col, $columns)) {
        $stmt = $pdo->query("SELECT id, product_name, $col FROM products WHERE $col LIKE '%114%' LIMIT 10");
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($results)) {
            echo "<h4>Found in column: $col</h4>";
            foreach ($results as $result) {
                echo "<p>ID: {$result['id']} - Name: {$result['product_name']}</p>";
                echo "<pre>" . htmlspecialchars($result[$col]) . "</pre>";
            }
        }
    }
}
?>