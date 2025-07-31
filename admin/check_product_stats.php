<?php
require_once '../db.php';

// 전체 제품 수 확인
$stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE is_active = 1");
$result = $stmt->fetch();
$total = $result['total'];

// 원산지별 제품 수
$stmt = $pdo->query("
    SELECT origin, COUNT(*) as count 
    FROM products 
    WHERE is_active = 1 
    GROUP BY origin 
    ORDER BY count DESC
");
$origins = $stmt->fetchAll();

// 재고 상태별 제품 수
$stmt = $pdo->query("
    SELECT stock_type, COUNT(*) as count 
    FROM products 
    WHERE is_active = 1 
    GROUP BY stock_type 
    ORDER BY count DESC
");
$stock_types = $stmt->fetchAll();

// JSON으로 결과 출력
header('Content-Type: application/json');
echo json_encode([
    'total_products' => $total,
    'by_origin' => $origins,
    'by_stock_type' => $stock_types
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);