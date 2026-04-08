<?php
/**
 * 카카오 알림톡 발송 시스템 - 통합 테스트 (시나리오 1~32)
 * 실행: docker exec project1_php php /var/www/html/tests/test_kakao_consignment.php
 */

// ========== CLI 환경 설정 ==========
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'PHPUnit-CLI-Test';
$_SERVER['REQUEST_URI'] = '/tests/test_kakao_consignment.php';
$_SERVER['QUERY_STRING'] = '';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_NAME'] = 'localhost';

require_once '/var/www/html/db.php';
require_once '/var/www/html/includes/KakaoNotificationService.php';

// ========== 테스트 프레임워크 ==========
$testResults = ['passed' => 0, 'failed' => 0, 'errors' => []];
$testConsignmentIds = [];
$testNotificationIds = [];

function test($num, $name, $assertions) {
    global $testResults;
    $failures = [];
    foreach ($assertions as $a) {
        if (!$a[0]) {
            $failures[] = $a[1];
        }
    }
    if (empty($failures)) {
        $testResults['passed']++;
        echo "\033[32m[PASS]\033[0m #{$num} {$name}\n";
    } else {
        $testResults['failed']++;
        $detail = implode('; ', $failures);
        $testResults['errors'][] = "#{$num} {$name} - {$detail}";
        echo "\033[31m[FAIL]\033[0m #{$num} {$name} - {$detail}\n";
    }
}

function createTestConsignment($pdo, $data) {
    $defaults = [
        'title' => '[TEST] 테스트 의뢰',
        'content' => '테스트 내용입니다.',
        'writer' => '테스트유저',
        'password' => password_hash('test1234', PASSWORD_BCRYPT),
        'company_name' => '테스트회사',
        'category' => 'H형강',
        'item_type' => 'steel',
        'stock_quantity' => '100톤',
        'price_info' => '협의',
        'contact_person' => '테스트담당자',
        'contact_phone' => '01012345678',
        'contact_email' => 'test@test.com',
        'location' => '인천시 서구',
        'status' => 'pending',
    ];
    $d = array_merge($defaults, $data);
    $stmt = $pdo->prepare("
        INSERT INTO board_consignment
        (title, content, writer, password, company_name, category, item_type,
         stock_quantity, price_info, contact_person, contact_phone, contact_email,
         location, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $d['title'], $d['content'], $d['writer'], $d['password'],
        $d['company_name'], $d['category'], $d['item_type'],
        $d['stock_quantity'], $d['price_info'], $d['contact_person'],
        $d['contact_phone'], $d['contact_email'], $d['location'], $d['status']
    ]);
    return (int)$pdo->lastInsertId();
}

function getConsignment($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM board_consignment WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * 승인 처리 시뮬레이션 (admin_review_consignment.php 로직 재현)
 */
function simulateApproval($pdo, $kakaoService, $id, $approved_price, $approved_message = '', $admin_id = 1, $admin_username = 'test_admin') {
    $price = (int)preg_replace('/[^0-9]/', '', (string)$approved_price);
    if ($price <= 0) {
        return ['success' => false, 'message' => '중개 금액을 입력해주세요. (0원 초과)'];
    }

    $post = getConsignment($pdo, $id);
    if (!$post) {
        return ['success' => false, 'message' => '게시글을 찾을 수 없습니다.'];
    }

    $stmt = $pdo->prepare("
        UPDATE board_consignment
        SET status = 'approved', reviewed_by = ?, reviewed_at = NOW(),
            reject_reason = NULL, approved_price = ?, approved_message = ?,
            kakao_send_status = 'pending'
        WHERE id = ?
    ");
    $stmt->execute([$admin_id, $price, $approved_message ?: null, $id]);

    $kakao_result = null;
    if (!empty($post['contact_phone'])) {
        $templateData = [
            'title' => $post['title'],
            'contact_person' => $post['contact_person'] ?? $post['writer'],
            'category' => $post['category'] ?? '-',
            'company_name' => $post['company_name'] ?? '-',
            'approved_price' => number_format($price),
            'custom_message' => $approved_message,
            'stock_quantity' => $post['stock_quantity'] ?? '-',
        ];
        $kakao_result = $kakaoService->sendNotification(
            'consignment', $id, $post['contact_phone'],
            $post['contact_person'] ?? $post['writer'],
            'CONSIGN_APPROVED', $templateData,
            ['sent_by' => $admin_username]
        );
        $sendStatus = ($kakao_result['success']) ? 'sent' : 'failed';
        $stmt = $pdo->prepare("UPDATE board_consignment SET kakao_send_status = ?, kakao_sent_at = NOW() WHERE id = ?");
        $stmt->execute([$sendStatus, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE board_consignment SET kakao_send_status = NULL WHERE id = ?");
        $stmt->execute([$id]);
    }

    return ['success' => true, 'message' => '승인', 'kakao_result' => $kakao_result];
}

/**
 * 거절 처리 시뮬레이션
 */
function simulateRejection($pdo, $kakaoService, $id, $reject_reason, $reject_message = '', $admin_id = 1, $admin_username = 'test_admin') {
    if (empty(trim($reject_reason))) {
        return ['success' => false, 'message' => '거절 사유를 입력해주세요.'];
    }

    $post = getConsignment($pdo, $id);
    if (!$post) {
        return ['success' => false, 'message' => '게시글을 찾을 수 없습니다.'];
    }

    $stmt = $pdo->prepare("
        UPDATE board_consignment
        SET status = 'rejected', reviewed_by = ?, reviewed_at = NOW(),
            reject_reason = ?, approved_message = ?,
            kakao_send_status = 'pending'
        WHERE id = ?
    ");
    $stmt->execute([$admin_id, $reject_reason, $reject_message ?: null, $id]);

    $kakao_result = null;
    if (!empty($post['contact_phone'])) {
        $templateData = [
            'title' => $post['title'],
            'contact_person' => $post['contact_person'] ?? $post['writer'],
            'reason' => $reject_reason,
            'custom_message' => $reject_message,
        ];
        $kakao_result = $kakaoService->sendNotification(
            'consignment', $id, $post['contact_phone'],
            $post['contact_person'] ?? $post['writer'],
            'CONSIGN_REJECTED', $templateData,
            ['sent_by' => $admin_username]
        );
        $sendStatus = ($kakao_result['success']) ? 'sent' : 'failed';
        $stmt = $pdo->prepare("UPDATE board_consignment SET kakao_send_status = ?, kakao_sent_at = NOW() WHERE id = ?");
        $stmt->execute([$sendStatus, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE board_consignment SET kakao_send_status = NULL WHERE id = ?");
        $stmt->execute([$id]);
    }

    return ['success' => true, 'message' => '반려', 'kakao_result' => $kakao_result];
}

/**
 * 재발송 시뮬레이션 (resend_consignment_kakao.php 로직 재현)
 */
function simulateResend($pdo, $kakaoService, $id, $admin_username = 'test_admin') {
    $post = getConsignment($pdo, $id);
    if (!$post) {
        return ['success' => false, 'message' => '게시글을 찾을 수 없습니다.'];
    }
    if (!in_array($post['status'], ['approved', 'rejected'])) {
        return ['success' => false, 'message' => '승인 또는 거절 상태의 의뢰만 재발송할 수 있습니다.'];
    }
    if (empty($post['contact_phone'])) {
        return ['success' => false, 'message' => '수신자 연락처가 없어 발송할 수 없습니다.'];
    }

    if ($post['status'] === 'approved') {
        $templateData = [
            'title' => $post['title'],
            'contact_person' => $post['contact_person'] ?? $post['writer'],
            'category' => $post['category'] ?? '-',
            'company_name' => $post['company_name'] ?? '-',
            'approved_price' => number_format((int)$post['approved_price']),
            'custom_message' => $post['approved_message'] ?? '',
            'stock_quantity' => $post['stock_quantity'] ?? '-',
        ];
        $messageType = 'CONSIGN_APPROVED';
    } else {
        $templateData = [
            'title' => $post['title'],
            'contact_person' => $post['contact_person'] ?? $post['writer'],
            'reason' => $post['reject_reason'] ?? '-',
            'custom_message' => $post['approved_message'] ?? '',
        ];
        $messageType = 'CONSIGN_REJECTED';
    }

    $kakao_result = $kakaoService->sendNotification(
        'consignment', $id, $post['contact_phone'],
        $post['contact_person'] ?? $post['writer'],
        $messageType, $templateData,
        ['sent_by' => $admin_username]
    );

    $sendStatus = ($kakao_result['success']) ? 'sent' : 'failed';
    $stmt = $pdo->prepare("UPDATE board_consignment SET kakao_send_status = ?, kakao_sent_at = NOW() WHERE id = ?");
    $stmt->execute([$sendStatus, $id]);

    return $kakao_result;
}

// ========== 테스트 시작 ==========
echo "\n========================================\n";
echo " 카카오 알림톡 통합 테스트 (시나리오 1~32)\n";
echo " KAKAO_TEST_MODE: " . (KAKAO_TEST_MODE ? 'ON (실제 API 미호출)' : 'OFF') . "\n";
echo "========================================\n\n";

if (!KAKAO_TEST_MODE) {
    echo "\033[31m[경고] KAKAO_TEST_MODE가 OFF입니다! 실제 API가 호출될 수 있습니다.\033[0m\n";
    echo "site_settings에서 kakao_test_mode='1'로 설정하세요.\n\n";
}

$kakaoService = new KakaoNotificationService($pdo);

// ========== 그룹 1: 가상 유저 판매의뢰 생성 (시나리오 1~5) ==========
echo "--- 그룹 1: 가상 유저 판매의뢰 생성 ---\n";

// #1 유저A: 철강류 (연락처 있음)
$idA = createTestConsignment($pdo, [
    'title' => '[TEST] H형강 200x200 판매의뢰',
    'content' => '상태 양호한 H형강 판매합니다.',
    'writer' => '김철수',
    'company_name' => '테스트철강(주)',
    'category' => 'H형강',
    'item_type' => 'steel',
    'stock_quantity' => '50톤',
    'contact_person' => '김철수',
    'contact_phone' => '01012345678',
]);
$testConsignmentIds[] = $idA;
$rowA = getConsignment($pdo, $idA);
test(1, '가상유저A 철강류 의뢰 생성', [
    [$rowA['status'] === 'pending', "status: '{$rowA['status']}' != 'pending'"],
    [$rowA['contact_phone'] === '01012345678', "contact_phone 불일치"],
    [$rowA['item_type'] === 'steel', "item_type 불일치"],
]);

// #2 유저B: 목재류 (연락처 있음)
$idB = createTestConsignment($pdo, [
    'title' => '[TEST] 각재 2x4 판매의뢰',
    'content' => '건조된 각재 판매합니다.',
    'writer' => '박영희',
    'company_name' => '시험목재상사',
    'category' => '각재',
    'item_type' => 'lumber',
    'stock_quantity' => '200개',
    'contact_person' => '박영희',
    'contact_phone' => '01087654321',
]);
$testConsignmentIds[] = $idB;
$rowB = getConsignment($pdo, $idB);
test(2, '가상유저B 목재류 의뢰 생성', [
    [$rowB['item_type'] === 'lumber', "item_type: '{$rowB['item_type']}' != 'lumber'"],
    [$rowB['category'] === '각재', "category 불일치"],
    [$rowB['stock_quantity'] === '200개', "stock_quantity 불일치"],
]);

// #3 유저C: 연락처 없음
$idC = createTestConsignment($pdo, [
    'title' => '[TEST] 앵글 판매의뢰 (연락처없음)',
    'content' => '앵글 판매합니다.',
    'writer' => '이민수',
    'company_name' => '무연락철강',
    'category' => '앵글',
    'contact_person' => '이민수',
    'contact_phone' => null,
]);
$testConsignmentIds[] = $idC;
$rowC = getConsignment($pdo, $idC);
test(3, '가상유저C 연락처 없음 의뢰 생성', [
    [$rowC['contact_phone'] === null, "contact_phone should be NULL, got: " . var_export($rowC['contact_phone'], true)],
]);

// #4 유저D: 특수문자 포함
$specialTitle = '[TEST] 특수문자 H형강(200x200) "신품" & \'중고\' <할인>';
$specialContent = "특수문자 테스트: 가나다 ABC 123 !@#\$%^&*() 괄호(소) [중] {대} <꺾>\n줄바꿈도 포함";
$idD = createTestConsignment($pdo, [
    'title' => $specialTitle,
    'content' => $specialContent,
    'writer' => '최특수',
    'company_name' => '특수(주)',
    'category' => 'C형강',
    'contact_person' => '최특수',
    'contact_phone' => '01055556666',
]);
$testConsignmentIds[] = $idD;
$rowD = getConsignment($pdo, $idD);
test(4, '가상유저D 특수문자 포함 의뢰 생성', [
    [$rowD['title'] === $specialTitle, "title 특수문자 저장 불일치"],
    [strpos($rowD['content'], '!@#') !== false, "content 특수문자 누락"],
]);

// #5 유저E: 3건 동시 생성
$idE1 = createTestConsignment($pdo, [
    'title' => '[TEST] E 의뢰 1번',
    'writer' => '정다건',
    'contact_person' => '정다건',
    'contact_phone' => '01011112222',
]);
$idE2 = createTestConsignment($pdo, [
    'title' => '[TEST] E 의뢰 2번',
    'writer' => '정다건',
    'contact_person' => '정다건',
    'contact_phone' => '01033334444',
]);
$idE3 = createTestConsignment($pdo, [
    'title' => '[TEST] E 의뢰 3번',
    'writer' => '정다건',
    'contact_person' => '정다건',
    'contact_phone' => '01077778888',
]);
$testConsignmentIds = array_merge($testConsignmentIds, [$idE1, $idE2, $idE3]);
$rE1 = getConsignment($pdo, $idE1);
$rE2 = getConsignment($pdo, $idE2);
$rE3 = getConsignment($pdo, $idE3);
test(5, '가상유저E 3건 동시 생성', [
    [$rE1['status'] === 'pending', "E1 status 불일치"],
    [$rE2['status'] === 'pending', "E2 status 불일치"],
    [$rE3['status'] === 'pending', "E3 status 불일치"],
]);

// 추가 테스트용 의뢰: 연락처 없음 (거절 테스트 #26용)
$idF = createTestConsignment($pdo, [
    'title' => '[TEST] 거절용 연락처없음',
    'writer' => '무연락',
    'contact_person' => '무연락',
    'contact_phone' => null,
]);
$testConsignmentIds[] = $idF;

// 추가 테스트용 의뢰: 서비스 단위 테스트용
$idH = createTestConsignment($pdo, [
    'title' => '[TEST] 서비스 단위테스트용',
    'writer' => '단위테스터',
    'contact_person' => '단위테스터',
    'contact_phone' => '01099990000',
]);
$testConsignmentIds[] = $idH;

// 추가 테스트용 의뢰: 이력 없는 의뢰 (#32용)
$idI = createTestConsignment($pdo, [
    'title' => '[TEST] 이력없는 의뢰',
    'writer' => '이력없음',
    'contact_person' => '이력없음',
    'contact_phone' => '01066667777',
]);
$testConsignmentIds[] = $idI;

echo "\n--- 그룹 2: KakaoNotificationService 단위 테스트 ---\n";

// #6 previewMessage('CONSIGN_APPROVED') 정상
$previewData = [
    'title' => '테스트 H형강',
    'contact_person' => '홍길동',
    'category' => 'H형강',
    'company_name' => '테스트(주)',
    'approved_price' => '1,500,000',
    'custom_message' => '빠른 처리 부탁드립니다.',
    'stock_quantity' => '50톤',
];
$r6 = $kakaoService->previewMessage('CONSIGN_APPROVED', $previewData);
test(6, 'previewMessage(CONSIGN_APPROVED) 정상 데이터', [
    [$r6['success'] === true, "success=false: " . ($r6['error'] ?? '')],
    [strpos($r6['preview'], '{') === false, "미치환 변수 존재"],
    [strpos($r6['preview'], '1,500,000') !== false, "금액 포맷 누락"],
    [strpos($r6['preview'], '홍길동') !== false, "이름 치환 누락"],
]);

// #7 previewMessage('CONSIGN_REJECTED') 정상
$r7 = $kakaoService->previewMessage('CONSIGN_REJECTED', [
    'title' => '테스트 매물',
    'contact_person' => '김거절',
    'reason' => '재질 확인 불가',
    'custom_message' => '재확인 후 재의뢰 부탁드립니다.',
]);
test(7, 'previewMessage(CONSIGN_REJECTED) 정상 데이터', [
    [$r7['success'] === true, "success=false: " . ($r7['error'] ?? '')],
    [strpos($r7['preview'], '재질 확인 불가') !== false, "{reason} 치환 누락"],
    [strpos($r7['preview'], '김거절') !== false, "{contact_person} 치환 누락"],
]);

// #8 previewMessage - 존재하지 않는 템플릿
$r8 = $kakaoService->previewMessage('NONEXISTENT_TEMPLATE', ['title' => 'test']);
test(8, 'previewMessage - 존재하지 않는 템플릿', [
    [$r8['success'] === false, "존재하지 않는 템플릿에 success=true 반환"],
    [!empty($r8['error']), "error 메시지 없음"],
]);

// #9 previewMessage - custom_message 빈 값 (연속 빈 줄 정리)
$r9 = $kakaoService->previewMessage('CONSIGN_APPROVED', [
    'title' => '테스트',
    'contact_person' => '홍길동',
    'category' => 'H형강',
    'company_name' => '테스트(주)',
    'approved_price' => '500,000',
    'custom_message' => '',
    'stock_quantity' => '10톤',
]);
test(9, 'previewMessage - custom_message 빈 값 (빈줄 정리)', [
    [$r9['success'] === true, "success=false"],
    [preg_match('/\n\s*\n\s*\n/', $r9['preview']) === 0, "연속 3줄 빈 줄 존재"],
]);

// #10 previewMessage - 긴 custom_message (1000자 이상)
$longMsg = str_repeat('가나다라마바사아자차카타파하 ', 70); // ~1050자
$r10 = $kakaoService->previewMessage('CONSIGN_APPROVED', [
    'title' => '테스트',
    'contact_person' => '홍길동',
    'category' => 'H형강',
    'company_name' => '테스트(주)',
    'approved_price' => '100,000',
    'custom_message' => $longMsg,
    'stock_quantity' => '5톤',
]);
test(10, 'previewMessage - 긴 메시지 (1000자 이상)', [
    [$r10['success'] === true, "success=false"],
    [mb_strlen($r10['preview']) > 1000, "미리보기 길이 1000자 미만: " . mb_strlen($r10['preview'])],
]);

// #11 sendNotification - 정상 승인 (테스트 모드)
$r11 = $kakaoService->sendNotification(
    'consignment', $idH, '01099990000', '단위테스터',
    'CONSIGN_APPROVED',
    ['title' => '테스트', 'contact_person' => '단위테스터', 'category' => 'H형강',
     'company_name' => '테스트(주)', 'approved_price' => '1,000,000',
     'custom_message' => '', 'stock_quantity' => '10톤'],
    ['sent_by' => 'test_admin']
);
if (!empty($r11['notification_id'])) $testNotificationIds[] = $r11['notification_id'];
$nRow11 = null;
if (!empty($r11['notification_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM kakao_notifications WHERE id = ?");
    $stmt->execute([$r11['notification_id']]);
    $nRow11 = $stmt->fetch();
}
test(11, 'sendNotification - 정상 승인 (테스트 모드)', [
    [$r11['success'] === true, "success=false: " . ($r11['error'] ?? '')],
    [($r11['test_mode'] ?? false) === true, "test_mode 아님"],
    [$nRow11 !== null && $nRow11['status'] === 'sent', "DB status != 'sent'"],
]);

// #12 sendNotification - 정상 거절 (테스트 모드)
$r12 = $kakaoService->sendNotification(
    'consignment', $idH, '01099990000', '단위테스터',
    'CONSIGN_REJECTED',
    ['title' => '테스트', 'contact_person' => '단위테스터',
     'reason' => '테스트 거절 사유', 'custom_message' => ''],
    ['sent_by' => 'test_admin']
);
if (!empty($r12['notification_id'])) $testNotificationIds[] = $r12['notification_id'];
test(12, 'sendNotification - 정상 거절 (테스트 모드)', [
    [$r12['success'] === true, "success=false: " . ($r12['error'] ?? '')],
    [!empty($r12['notification_id']), "notification_id 없음"],
]);

// #13 sendNotification - 유효하지 않은 전화번호 (영문)
$r13 = $kakaoService->sendNotification(
    'consignment', $idH, 'abcdefghij', '잘못된폰',
    'CONSIGN_APPROVED',
    ['title' => '테스트', 'contact_person' => '테스트', 'category' => '-',
     'company_name' => '-', 'approved_price' => '100', 'custom_message' => '', 'stock_quantity' => '-']
);
test(13, 'sendNotification - 유효하지 않은 전화번호', [
    [$r13['success'] === false, "invalid phone에 success=true"],
    [strpos($r13['error'] ?? '', '유효하지 않은 전화번호') !== false, "에러 메시지 불일치: " . ($r13['error'] ?? '')],
]);

// #14 sendNotification - 전화번호 형식 다양성
$phoneFormats = ['010-1234-5678', '01012345678', '010 1234 5678'];
$allPhoneOk = true;
$phoneErrors = [];
foreach ($phoneFormats as $phone) {
    $r = $kakaoService->sendNotification(
        'consignment', $idH, $phone, '폰테스트',
        'CONSIGN_APPROVED',
        ['title' => '테스트', 'contact_person' => '폰테스트', 'category' => '-',
         'company_name' => '-', 'approved_price' => '100', 'custom_message' => '', 'stock_quantity' => '-'],
        ['sent_by' => 'test_admin']
    );
    if (!empty($r['notification_id'])) $testNotificationIds[] = $r['notification_id'];
    if (!$r['success']) {
        $allPhoneOk = false;
        $phoneErrors[] = "'{$phone}' 실패: " . ($r['error'] ?? '');
    }
}
test(14, 'sendNotification - 전화번호 형식 다양성', [
    [$allPhoneOk, implode('; ', $phoneErrors)],
]);

// #15 sendNotification - sent_by 전달 확인
$r15 = $kakaoService->sendNotification(
    'consignment', $idH, '01099990000', '단위테스터',
    'CONSIGN_APPROVED',
    ['title' => '테스트', 'contact_person' => '단위테스터', 'category' => '-',
     'company_name' => '-', 'approved_price' => '100', 'custom_message' => '', 'stock_quantity' => '-'],
    ['sent_by' => '관리자김']
);
if (!empty($r15['notification_id'])) $testNotificationIds[] = $r15['notification_id'];
$nRow15 = null;
if (!empty($r15['notification_id'])) {
    $stmt = $pdo->prepare("SELECT sent_by FROM kakao_notifications WHERE id = ?");
    $stmt->execute([$r15['notification_id']]);
    $nRow15 = $stmt->fetch();
}
test(15, 'sendNotification - sent_by 전달 확인', [
    [$r15['success'] === true, "success=false"],
    [$nRow15 !== null && $nRow15['sent_by'] === '관리자김', "sent_by 불일치: " . ($nRow15['sent_by'] ?? 'null')],
]);

echo "\n--- 그룹 3: 승인 처리 + 카카오 발송 ---\n";

// #16 승인 - 금액 1,500,000원 + 추가 메시지
$r16 = simulateApproval($pdo, $kakaoService, $idA, '1,500,000', '빠른 거래 부탁드립니다.');
$row16 = getConsignment($pdo, $idA);
test(16, '승인 - 금액 1,500,000원 + 추가 메시지', [
    [$r16['success'] === true, "승인 실패: " . ($r16['message'] ?? '')],
    [(int)$row16['approved_price'] === 1500000, "approved_price: {$row16['approved_price']} != 1500000"],
    [$row16['kakao_send_status'] === 'sent', "kakao_send_status: '{$row16['kakao_send_status']}' != 'sent'"],
]);
if (!empty($r16['kakao_result']['notification_id'])) {
    $testNotificationIds[] = $r16['kakao_result']['notification_id'];
}

// #17 승인 - 금액만 (추가 메시지 없음)
$r17 = simulateApproval($pdo, $kakaoService, $idB, '800000', '');
$row17 = getConsignment($pdo, $idB);
test(17, '승인 - 금액만 (추가 메시지 없음)', [
    [$r17['success'] === true, "승인 실패"],
    [$row17['approved_message'] === null, "approved_message가 NULL이 아님: " . var_export($row17['approved_message'], true)],
    [$row17['kakao_send_status'] === 'sent', "kakao_send_status 불일치"],
]);
if (!empty($r17['kakao_result']['notification_id'])) {
    $testNotificationIds[] = $r17['kakao_result']['notification_id'];
}

// #18 승인 - 금액 0원 시도
$r18 = simulateApproval($pdo, $kakaoService, $idE2, '0');
test(18, '승인 - 금액 0원 검증 실패', [
    [$r18['success'] === false, "0원에 success=true 반환"],
    [strpos($r18['message'], '금액') !== false, "에러 메시지에 '금액' 없음"],
]);

// #19 승인 - 음수 금액 시도 (숫자 제거 후 0)
$r19 = simulateApproval($pdo, $kakaoService, $idE2, '-');
test(19, '승인 - 음수/빈 금액 검증 실패', [
    [$r19['success'] === false, "음수/빈 금액에 success=true"],
]);

// #20 승인 - 연락처 없는 의뢰
$r20 = simulateApproval($pdo, $kakaoService, $idC, '500000');
$row20 = getConsignment($pdo, $idC);
test(20, '승인 - 연락처 없는 의뢰 (카카오 미발송)', [
    [$r20['success'] === true, "승인 자체 실패"],
    [$row20['status'] === 'approved', "status 불일치"],
    [$row20['kakao_send_status'] === null, "연락처 없음인데 kakao_send_status가 null이 아님: " . var_export($row20['kakao_send_status'], true)],
    [$r20['kakao_result'] === null, "카카오 발송 시도됨"],
]);

// #21 승인 - 이미 승인된 의뢰 재승인
$r21 = simulateApproval($pdo, $kakaoService, $idA, '2,000,000', '금액 변경');
$row21 = getConsignment($pdo, $idA);
test(21, '승인 - 이미 승인된 의뢰 재승인', [
    [$r21['success'] === true, "재승인 실패"],
    [(int)$row21['approved_price'] === 2000000, "금액 업데이트 실패: {$row21['approved_price']}"],
    [$row21['status'] === 'approved', "status 불일치"],
]);
if (!empty($r21['kakao_result']['notification_id'])) {
    $testNotificationIds[] = $r21['kakao_result']['notification_id'];
}

// #22 승인 후 DB 전체 검증 (idA의 현재 상태)
$row22 = getConsignment($pdo, $idA);
test(22, '승인 후 DB 전체 검증', [
    [$row22['status'] === 'approved', "status: '{$row22['status']}'"],
    [$row22['reviewed_by'] !== null, "reviewed_by is null"],
    [$row22['reviewed_at'] !== null, "reviewed_at is null"],
    [(int)$row22['approved_price'] === 2000000, "approved_price: {$row22['approved_price']}"],
    [$row22['kakao_sent_at'] !== null, "kakao_sent_at is null"],
    [$row22['kakao_send_status'] === 'sent', "kakao_send_status: '{$row22['kakao_send_status']}'"],
]);

echo "\n--- 그룹 4: 거절 처리 + 카카오 발송 ---\n";

// #23 거절 - 사유 + 추가 메시지
$r23 = simulateRejection($pdo, $kakaoService, $idD, '재질 증명서 미첨부', '서류 보완 후 재신청 바랍니다.');
$row23 = getConsignment($pdo, $idD);
test(23, '거절 - 사유 + 추가 메시지', [
    [$r23['success'] === true, "거절 실패"],
    [$row23['reject_reason'] === '재질 증명서 미첨부', "reject_reason 불일치"],
    [$row23['kakao_send_status'] === 'sent', "kakao_send_status: '{$row23['kakao_send_status']}'"],
]);
if (!empty($r23['kakao_result']['notification_id'])) {
    $testNotificationIds[] = $r23['kakao_result']['notification_id'];
}

// #24 거절 - 사유만 (추가 메시지 없음)
$r24 = simulateRejection($pdo, $kakaoService, $idE1, '수량 부족');
$row24 = getConsignment($pdo, $idE1);
test(24, '거절 - 사유만 (추가 메시지 없음)', [
    [$r24['success'] === true, "거절 실패"],
    [$row24['approved_message'] === null, "approved_message가 null이 아님"],
    [$row24['kakao_send_status'] === 'sent', "kakao_send_status 불일치"],
]);
if (!empty($r24['kakao_result']['notification_id'])) {
    $testNotificationIds[] = $r24['kakao_result']['notification_id'];
}

// #25 거절 - 사유 미입력
$r25 = simulateRejection($pdo, $kakaoService, $idE2, '');
test(25, '거절 - 사유 미입력 검증 실패', [
    [$r25['success'] === false, "빈 사유에 success=true"],
    [strpos($r25['message'], '사유') !== false, "에러 메시지에 '사유' 없음"],
]);

// #26 거절 - 연락처 없는 의뢰
$r26 = simulateRejection($pdo, $kakaoService, $idF, '테스트 거절');
$row26 = getConsignment($pdo, $idF);
test(26, '거절 - 연락처 없는 의뢰 (카카오 미발송)', [
    [$r26['success'] === true, "거절 자체 실패"],
    [$row26['status'] === 'rejected', "status 불일치"],
    [$row26['kakao_send_status'] === null, "연락처 없음인데 kakao_send_status가 null이 아님"],
]);

// #27 거절 후 DB 전체 검증 (idD)
$row27 = getConsignment($pdo, $idD);
test(27, '거절 후 DB 전체 검증', [
    [$row27['status'] === 'rejected', "status: '{$row27['status']}'"],
    [$row27['reject_reason'] === '재질 증명서 미첨부', "reject_reason 불일치"],
    [$row27['reviewed_at'] !== null, "reviewed_at is null"],
    [$row27['reviewed_by'] !== null, "reviewed_by is null"],
    [$row27['kakao_send_status'] === 'sent', "kakao_send_status 불일치"],
]);

echo "\n--- 그룹 5: 재발송 + 이력 ---\n";

// #28 승인된 의뢰 재발송 (idA)
$beforeCount28 = count($kakaoService->getNotificationsByBoardId('consignment', $idA));
$r28 = simulateResend($pdo, $kakaoService, $idA);
$afterCount28 = count($kakaoService->getNotificationsByBoardId('consignment', $idA));
$row28 = getConsignment($pdo, $idA);
test(28, '승인된 의뢰 재발송', [
    [$r28['success'] === true, "재발송 실패: " . ($r28['error'] ?? '')],
    [$afterCount28 > $beforeCount28, "새 notification 레코드 미생성 (before:{$beforeCount28}, after:{$afterCount28})"],
    [$row28['kakao_send_status'] === 'sent', "kakao_send_status 갱신 안됨"],
]);
if (!empty($r28['notification_id'])) $testNotificationIds[] = $r28['notification_id'];

// #29 거절된 의뢰 재발송 (idD)
$r29 = simulateResend($pdo, $kakaoService, $idD);
test(29, '거절된 의뢰 재발송', [
    [$r29['success'] === true, "재발송 실패: " . ($r29['error'] ?? '')],
    [($r29['test_mode'] ?? false) === true, "test_mode 아님"],
]);
if (!empty($r29['notification_id'])) $testNotificationIds[] = $r29['notification_id'];

// #30 pending 상태 의뢰 재발송 시도 (idE2는 아직 pending)
$r30 = simulateResend($pdo, $kakaoService, $idE2);
test(30, 'pending 상태 의뢰 재발송 시도 (실패 기대)', [
    [$r30['success'] === false, "pending 상태에서 재발송 success=true"],
    [strpos($r30['message'], '승인 또는 거절') !== false, "에러 메시지 불일치: " . ($r30['message'] ?? '')],
]);

// #31 발송 이력 조회 - 복수 건 (idA: 승인 + 재승인 + 재발송 = 3건 이상)
$history31 = $kakaoService->getNotificationsByBoardId('consignment', $idA);
$historyDates = array_column($history31, 'created_at');
$isSorted = true;
for ($i = 1; $i < count($historyDates); $i++) {
    if ($historyDates[$i] > $historyDates[$i - 1]) {
        $isSorted = false;
        break;
    }
}
test(31, '발송 이력 조회 - 복수 건 + 최신순 정렬', [
    [count($history31) >= 2, "이력 2건 미만: " . count($history31)],
    [$isSorted, "최신순 정렬 아님"],
]);

// #32 발송 이력 조회 - 이력 없는 의뢰 (idI)
$history32 = $kakaoService->getNotificationsByBoardId('consignment', $idI);
test(32, '발송 이력 조회 - 이력 없는 의뢰', [
    [is_array($history32), "배열이 아님"],
    [count($history32) === 0, "이력이 존재: " . count($history32)],
]);

// ========== 클린업 ==========
echo "\n--- 클린업 ---\n";

// kakao_notifications 삭제
if (!empty($testConsignmentIds)) {
    $placeholders = implode(',', array_fill(0, count($testConsignmentIds), '?'));
    $stmt = $pdo->prepare("DELETE FROM kakao_notifications WHERE board_type = 'consignment' AND board_id IN ($placeholders)");
    $stmt->execute($testConsignmentIds);
    echo "kakao_notifications 삭제: {$stmt->rowCount()}건\n";
}

// board_consignment 삭제
$stmt = $pdo->prepare("DELETE FROM board_consignment WHERE title LIKE '[TEST]%'");
$stmt->execute();
echo "board_consignment 삭제: {$stmt->rowCount()}건\n";

// ========== 결과 ==========
$total = $testResults['passed'] + $testResults['failed'];
echo "\n========================================\n";
if ($testResults['failed'] === 0) {
    echo "\033[32m=== 결과: {$total}/{$total} 성공 ===\033[0m\n";
} else {
    echo "\033[31m=== 결과: {$testResults['passed']}/{$total} 성공, {$testResults['failed']}건 실패 ===\033[0m\n";
    echo "\n실패 목록:\n";
    foreach ($testResults['errors'] as $err) {
        echo "  - {$err}\n";
    }
}
echo "========================================\n\n";

exit($testResults['failed'] > 0 ? 1 : 0);
