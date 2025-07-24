<?php
require_once '../db.php';
require_once '../member_check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);
    exit;
}

try {
    // POST 데이터 받기
    $customer_name = $_POST['customer_name'] ?? '';
    $company = $_POST['company'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $products = $_POST['products'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $items = isset($_POST['items']) ? json_decode($_POST['items'], true) : [];
    
    // 필수 항목 검증
    if (empty($customer_name) || empty($phone) || empty($products)) {
        echo json_encode(['success' => false, 'message' => '필수 항목을 입력해주세요.']);
        exit;
    }
    
    // 트랜잭션 시작
    $pdo->beginTransaction();
    
    // 견적서 저장
    $sql = "INSERT INTO product_quotes (customer_name, company, phone, email, products, notes, member_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $customer_name,
        $company,
        $phone,
        $email,
        $products,
        $notes,
        $_SESSION['member_id'] ?? null
    ]);
    
    $quote_id = $pdo->lastInsertId();
    
    // 견적서 아이템 저장
    if (!empty($items)) {
        $item_sql = "INSERT INTO product_quote_items (quote_id, product_id, product_name, specifications, quantity) 
                     VALUES (?, ?, ?, ?, ?)";
        $item_stmt = $pdo->prepare($item_sql);
        
        foreach ($items as $item) {
            // product_id가 문자열인 경우 (rebar_quote에서 온 경우) null로 처리
            $product_id = isset($item['id']) && is_numeric($item['id']) ? $item['id'] : null;
            
            $item_stmt->execute([
                $quote_id,
                $product_id,
                $item['name'],
                $item['specifications'] ?? '',
                $item['quantity']
            ]);
        }
    }
    
    // 트랜잭션 커밋
    $pdo->commit();
    
    // 세션 스토리지의 카트 비우기를 위한 플래그
    echo json_encode([
        'success' => true, 
        'message' => '견적 요청이 성공적으로 접수되었습니다.',
        'quote_id' => $quote_id,
        'clear_cart' => true
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Product Quote DB Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '데이터베이스 오류가 발생했습니다. 관리자에게 문의해주세요.', 'error' => $e->getMessage()]);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Product Quote Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>