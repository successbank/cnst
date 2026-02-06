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
$product = trim($_POST['product'] ?? '');
$quantity = trim($_POST['quantity'] ?? '');
$specifications = trim($_POST['specifications'] ?? '');
$company = trim($_POST['company'] ?? '');
$author = trim($_POST['author'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$urgency = $_POST['urgency'] ?? 'normal';
$additional_notes = trim($_POST['additional_notes'] ?? '');

// 유효성 검사
if (!$product || !$company || !$author || !$phone) {
    echo json_encode(['success' => false, 'message' => '필수 항목을 모두 입력해주세요.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => '올바른 이메일 주소를 입력해주세요.']);
    exit;
}

// 입력값 검증 (SQL 인젝션 패턴 차단)
$validation = validateQuoteInput([
    'product' => $product, 'company' => $company, 'author' => $author,
    'specifications' => $specifications, 'additional_notes' => $additional_notes
]);
if (!$validation['valid']) {
    echo json_encode(['success' => false, 'message' => $validation['message']]);
    exit;
}

// Rate Limiting (1분 내 3회 제한)
if (!checkRateLimit('quote_submit', 3, 60)) {
    echo json_encode(['success' => false, 'message' => '잠시 후 다시 시도해주세요.']);
    exit;
}

try {
    // 제목 생성
    $title = $product . ' 견적문의';
    
    // 내용 생성
    $content = "제품명: {$product}\n";
    $content .= "수량: {$quantity}\n";
    $content .= "규격: {$specifications}\n";
    $content .= "긴급도: {$urgency}\n";
    $content .= "추가사항: {$additional_notes}";
    
    // board_quote 테이블에 저장
    $stmt = $pdo->prepare("
        INSERT INTO board_quote (
            title, content, writer, password, company, 
            phone, email, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    // 임시 비밀번호 생성
    $temp_password = password_hash($user_id . time(), PASSWORD_DEFAULT);
    
    $stmt->execute([
        $title, $content, $user_id, $temp_password, $company,
        $phone, $email
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
                'writer' => $author,
                'company' => $company
            ];
            $kakaoService->sendNotification('quote', $insertId, $admin['phone'], '관리자', 'QUOTE_NEW', $templateData);
        }
        
        // 작성자에게 접수 확인 알림
        if ($phone) {
            $kakaoService->sendNotification('quote', $insertId, $phone, $author, 'QUOTE_RECEIVED', [
                'title' => $title
            ]);
        }
    } catch (Exception $e) {
        // 알림 실패는 무시
    }
    
    echo json_encode([
        'success' => true, 
        'message' => '견적문의가 성공적으로 등록되었습니다.',
        'id' => $insertId
    ]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => '견적문의 등록 중 오류가 발생했습니다.']);
}
?>