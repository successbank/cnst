<?php
require_once '../member_check.php';
require_once '../db.php';
require_once '../includes/input_validator.php';

// JSON 응답 설정
header('Content-Type: application/json; charset=utf-8');

// 로그인 체크
if (!isset($_SESSION['member_id'])) {
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}

$user_id = $_SESSION['user_id'] ?? '';
$member_id = $_SESSION['member_id'];

// POST 데이터 받기
$title = trim($_POST['title'] ?? '');
$category = $_POST['category'] ?? '';
$company_name = trim($_POST['company_name'] ?? '');
$stock_quantity = trim($_POST['stock_quantity'] ?? '');
$price_info = trim($_POST['price_info'] ?? '');
$contact_person = trim($_POST['contact_person'] ?? '');
$contact_phone = trim($_POST['contact_phone'] ?? '');
$contact_email = trim($_POST['contact_email'] ?? '');
$location = trim($_POST['location'] ?? '');
$content = trim($_POST['content'] ?? '');

// 유효성 검사
if (!$title || !$category || !$company_name || !$contact_person || !$contact_phone) {
    echo json_encode(['success' => false, 'message' => '필수 항목을 모두 입력해주세요.']);
    exit;
}

if ($contact_email && !filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => '올바른 이메일 주소를 입력해주세요.']);
    exit;
}

// SQL 인젝션 패턴 검증
$validation = validateFormInput([
    'title' => $title,
    'company_name' => $company_name,
    'stock_quantity' => $stock_quantity,
    'price_info' => $price_info,
    'contact_person' => $contact_person,
    'contact_phone' => $contact_phone,
    'contact_email' => $contact_email,
    'location' => $location,
    'content' => $content
]);
if (!$validation['valid']) {
    echo json_encode(['success' => false, 'message' => $validation['message']]);
    exit;
}

// Rate Limiting (60초 내 최대 3회)
if (!checkRateLimit('consignment_submit', 3, 60)) {
    echo json_encode(['success' => false, 'message' => '너무 많은 요청이 감지되었습니다. 잠시 후 다시 시도해주세요.']);
    exit;
}

try {
    // board_consignment 테이블에 저장
    $stmt = $pdo->prepare("
        INSERT INTO board_consignment (
            title, content, writer, password, company_name, category, 
            stock_quantity, price_info, contact_person, contact_phone, 
            contact_email, location, status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())
    ");
    
    // 임시 비밀번호 생성
    $temp_password = password_hash($user_id . time(), PASSWORD_DEFAULT);
    
    $stmt->execute([
        $title, $content, $user_id, $temp_password, $company_name, $category,
        $stock_quantity, $price_info, $contact_person, $contact_phone,
        $contact_email, $location
    ]);
    
    $insertId = $pdo->lastInsertId();
    
    // 카카오톡 알림 발송 (필요시)
    try {
        require_once '../includes/KakaoNotificationService.php';
        $kakaoService = new KakaoNotificationService($pdo);
        
        // 관리자에게 알림
        $adminStmt = $pdo->prepare("SELECT phone FROM members WHERE is_admin = 1 AND phone IS NOT NULL LIMIT 1");
        $adminStmt->execute();
        $admin = $adminStmt->fetch();
        
        if ($admin && $admin['phone']) {
            $templateData = [
                'title' => $title,
                'company_name' => $company_name,
                'category' => $category
            ];
            $kakaoService->sendNotification('consignment', $insertId, $admin['phone'], '관리자', 'CONSIGN_NEW', $templateData);
        }
        
        // 작성자에게 등록 확인 알림
        if ($contact_phone) {
            $kakaoService->sendNotification('consignment', $insertId, $contact_phone, $contact_person, 'CONSIGN_RECEIVED', [
                'title' => $title
            ]);
        }
    } catch (Exception $e) {
        // 알림 실패는 무시
    }
    
    echo json_encode([
        'success' => true, 
        'message' => '중계판매 신청이 성공적으로 등록되었습니다.',
        'id' => $insertId
    ]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => '중계판매 신청 중 오류가 발생했습니다.']);
}
?>