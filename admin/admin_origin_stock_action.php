<?php
session_start();
require_once '../db.php';

// 관리자 로그인 확인
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = '잘못된 접근입니다.';
    header('Location: admin_origin_stock.php');
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'update_products_origin_stock') {
    $category_code = $_POST['category_code'] ?? '';
    $product_ids = $_POST['product_ids'] ?? [];
    $product_origins = $_POST['product_origins'] ?? [];
    $product_stock_types = $_POST['product_stock_types'] ?? [];
    
    if (empty($product_ids)) {
        $_SESSION['error'] = '선택된 제품이 없습니다.';
        header('Location: admin_origin_stock.php');
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        $updated_count = 0;
        
        foreach ($product_ids as $product_id) {
            // 원산지 업데이트
            $origins = $product_origins[$product_id] ?? [];
            if (!empty($origins)) {
                $origins_json = json_encode($origins, JSON_UNESCAPED_UNICODE);
                $stmt = $pdo->prepare("
                    UPDATE products 
                    SET available_origins = ?, 
                        origin = ?,
                        updated_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$origins_json, $origins[0], $product_id]);
            }
            
            // 재고 상태 업데이트
            $stock_types = $product_stock_types[$product_id] ?? [];
            if (!empty($stock_types)) {
                $stock_types_json = json_encode($stock_types, JSON_UNESCAPED_UNICODE);
            } else {
                $stock_types_json = '["일반재고"]';
            }
            
            $stmt = $pdo->prepare("
                UPDATE products 
                SET stock_types = ?,
                    updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$stock_types_json, $product_id]);
            
            $updated_count++;
        }
        
        $pdo->commit();
        
        $_SESSION['success'] = "{$updated_count}개 제품의 원산지 및 재고 상태가 업데이트되었습니다.";
        
    } catch (Exception $e) {
        $pdo->rollback();
        $_SESSION['error'] = '업데이트 중 오류가 발생했습니다: ' . $e->getMessage();
    }
    
} elseif ($action === 'update_stock_type') {
    // 일괄 재고 상태 변경 (기존 코드 호환)
    $category_code = $_POST['category_code'] ?? '';
    $new_stock_type = $_POST['new_stock_type'] ?? '';
    
    if (!$category_code || !$new_stock_type) {
        $_SESSION['error'] = '필수 정보가 누락되었습니다.';
        header('Location: admin_origin_stock.php');
        exit;
    }
    
    try {
        // stock_type 값을 stock_types JSON으로 변환
        $stock_types_mapping = [
            'normal' => '["일반재고"]',
            'long_term' => '["장기재고"]',
            'used' => '["중고"]'
        ];
        
        $stock_types_json = $stock_types_mapping[$new_stock_type] ?? '["일반재고"]';
        
        $stmt = $pdo->prepare("
            UPDATE products 
            SET stock_type = ?,
                stock_types = ?,
                updated_at = NOW() 
            WHERE category_code = ?
        ");
        $stmt->execute([$new_stock_type, $stock_types_json, $category_code]);
        
        $affected = $stmt->rowCount();
        
        $_SESSION['success'] = "{$affected}개 제품의 재고 상태가 일괄 변경되었습니다.";
        
    } catch (Exception $e) {
        $_SESSION['error'] = '업데이트 중 오류가 발생했습니다: ' . $e->getMessage();
    }
}

header('Location: admin_origin_stock.php');
exit;
?>