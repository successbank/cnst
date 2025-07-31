<?php
require_once 'admin_check.php';
require_once '../db.php';

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin_product_groups.php');
    exit;
}

$action = $_POST['action'] ?? '';
$category_code = $_POST['category_code'] ?? '';

if (empty($action)) {
    $_SESSION['error'] = '필수 파라미터가 누락되었습니다.';
    header('Location: admin_origin_stock.php');
    exit;
}

try {
    switch ($action) {
        case 'update_origin':
            $new_origin = $_POST['new_origin'] ?? '';
            if (empty($new_origin)) {
                throw new Exception('원산지를 선택해주세요.');
            }
            
            // 카테고리의 모든 제품 원산지 업데이트
            $stmt = $pdo->prepare("
                UPDATE products 
                SET origin = ?, 
                    updated_at = NOW() 
                WHERE category_code = ? 
                AND is_active = 1
            ");
            $stmt->execute([$new_origin, $category_code]);
            
            $affected = $stmt->rowCount();
            $_SESSION['success'] = "{$affected}개 제품의 원산지를 '{$new_origin}'(으)로 변경했습니다.";
            break;
            
        case 'update_products_origin_stock':
            $product_ids = $_POST['product_ids'] ?? [];
            $product_origins = $_POST['product_origins'] ?? [];
            $product_stock_types = $_POST['product_stock_types'] ?? [];
            
            if (empty($product_ids)) {
                throw new Exception('변경할 제품을 선택해주세요.');
            }
            
            $origin_updated = 0;
            $stock_updated = 0;
            
            // 선택된 각 제품의 원산지와 재고 상태 업데이트
            foreach ($product_ids as $product_id) {
                $updates = [];
                $params = [];
                
                // 원산지 변경
                if (isset($product_origins[$product_id]) && !empty($product_origins[$product_id])) {
                    $updates[] = "origin = ?";
                    $params[] = $product_origins[$product_id];
                }
                
                // 재고 상태 변경
                if (isset($product_stock_types[$product_id]) && !empty($product_stock_types[$product_id])) {
                    $updates[] = "stock_type = ?";
                    $params[] = $product_stock_types[$product_id];
                }
                
                if (!empty($updates)) {
                    $updates[] = "updated_at = NOW()";
                    $params[] = $product_id;
                    
                    $sql = "UPDATE products SET " . implode(", ", $updates) . " WHERE id = ? AND is_active = 1";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    
                    if ($stmt->rowCount() > 0) {
                        if (isset($product_origins[$product_id]) && !empty($product_origins[$product_id])) {
                            $origin_updated++;
                        }
                        if (isset($product_stock_types[$product_id]) && !empty($product_stock_types[$product_id])) {
                            $stock_updated++;
                        }
                    }
                }
            }
            
            $messages = [];
            if ($origin_updated > 0) {
                $messages[] = "{$origin_updated}개 제품의 원산지";
            }
            if ($stock_updated > 0) {
                $messages[] = "{$stock_updated}개 제품의 재고 상태";
            }
            
            if (!empty($messages)) {
                $_SESSION['success'] = implode(", ", $messages) . "를 변경했습니다.";
            } else {
                $_SESSION['error'] = "변경된 제품이 없습니다. 변경할 항목을 선택해주세요.";
            }
            break;
            
        case 'update_stock_type':
            $new_stock_type = $_POST['new_stock_type'] ?? '';
            if (empty($new_stock_type)) {
                throw new Exception('재고 상태를 선택해주세요.');
            }
            
            // 카테고리의 모든 제품 재고 상태 업데이트
            $stmt = $pdo->prepare("
                UPDATE products 
                SET stock_type = ?, 
                    updated_at = NOW() 
                WHERE category_code = ? 
                AND is_active = 1
            ");
            $stmt->execute([$new_stock_type, $category_code]);
            
            $affected = $stmt->rowCount();
            $stock_type_name = [
                'normal' => '일반',
                'long_term' => '장기재고',
                'used' => '중고'
            ];
            $_SESSION['success'] = "{$affected}개 제품의 재고 상태를 '{$stock_type_name[$new_stock_type]}'(으)로 변경했습니다.";
            break;
            
        default:
            throw new Exception('유효하지 않은 작업입니다.');
    }
    
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

header('Location: admin_origin_stock.php');
exit;