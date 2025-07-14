<?php
require_once '../db.php';
require_once '../member_check.php';

header('Content-Type: application/json');

// 로그인 체크
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);
    exit;
}

try {
    // POST 데이터 받기
    $product = trim($_POST['product'] ?? '');
    $quantity = trim($_POST['quantity'] ?? '');
    $specifications = trim($_POST['specifications'] ?? '');
    $urgency = $_POST['urgency'] ?? 'normal';
    $company = trim($_POST['company'] ?? '');
    $author = trim($_POST['author'] ?? '');  // 폼 필드명과 일치
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $additional_notes = trim($_POST['additional_notes'] ?? '');
    
    // 필수 항목 검증
    if (empty($product) || empty($company) || empty($author) || empty($phone) || empty($email)) {
        $missing = [];
        if (empty($product)) $missing[] = '제품명';
        if (empty($company)) $missing[] = '회사명';
        if (empty($author)) $missing[] = '담당자명';
        if (empty($phone)) $missing[] = '연락처';
        if (empty($email)) $missing[] = '이메일';
        
        echo json_encode([
            'success' => false, 
            'message' => '필수 항목을 모두 입력해주세요. 누락된 항목: ' . implode(', ', $missing)
        ]);
        exit;
    }
    
    // 이메일 형식 검증
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => '올바른 이메일 형식이 아닙니다.']);
        exit;
    }
    
    // 세션에서 사용자 정보 가져오기
    $member_id = $_SESSION['member_id'] ?? null;
    $user_id = $_SESSION['user_id'] ?? '';
    $writer = $_SESSION['name'] ?? $author;
    
    // 제목 생성
    $title = "[견적문의] {$product}" . (!empty($quantity) ? " - {$quantity}" : "");
    
    // 내용 조합
    $content = "■ 제품 정보\n";
    $content .= "제품명: {$product}\n";
    if (!empty($quantity)) $content .= "수량: {$quantity}\n";
    if (!empty($specifications)) $content .= "규격/사양: {$specifications}\n";
    $content .= "납기: ";
    switch($urgency) {
        case 'urgent': $content .= "긴급\n"; break;
        case 'flexible': $content .= "협의\n"; break;
        default: $content .= "일반\n"; break;
    }
    $content .= "\n";
    
    if (!empty($additional_notes)) {
        $content .= "■ 추가 요청사항\n";
        $content .= $additional_notes . "\n";
    }
    
    // board_quote 테이블에 저장
    // password 필드는 필수이므로 빈 문자열로 설정 (회원이 작성한 경우)
    $sql = "INSERT INTO board_quote (
        title, content, writer, password, company, email, phone, 
        member_id, created_at, updated_at
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, 
        ?, NOW(), NOW()
    )";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $title,
        $content,
        $writer,
        '', // 빈 패스워드 (로그인 사용자는 member_id로 확인)
        $company,
        $email,
        $phone,
        $member_id
    ]);
    
    $quote_id = $pdo->lastInsertId();
    
    // 카카오톡 알림 전송 (옵션)
    try {
        // 카카오톡 설정 확인
        $kakao_stmt = $pdo->prepare("SELECT use_kakao FROM site_settings WHERE id = 1");
        $kakao_stmt->execute();
        $use_kakao = $kakao_stmt->fetchColumn();
        
        if ($use_kakao) {
            // 알림 전송 로직 (필요시 구현)
        }
    } catch (Exception $e) {
        // 알림 실패는 무시
    }
    
    echo json_encode([
        'success' => true, 
        'message' => '견적문의가 성공적으로 등록되었습니다.',
        'quote_id' => $quote_id
    ]);
    
} catch (PDOException $e) {
    // 개발 중에는 구체적인 오류 메시지 표시
    echo json_encode([
        'success' => false, 
        'message' => '데이터베이스 오류가 발생했습니다.',
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>