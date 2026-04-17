<?php
session_start();
require_once '../../db.php';
require_once '../../includes/csrf.php';

// H3: 관리자 권한 확인 (표준 패턴)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '관리자 인증이 필요합니다.']);
    exit;
}

// [보안] CSRF 토큰 검증 (2026-04-17)
if (!verifyCsrfToken(false)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'CSRF 토큰이 유효하지 않습니다.']);
    exit;
}

// JSON 데이터 받기
$input = json_decode(file_get_contents('php://input'), true);
$id = isset($input['id']) ? (int)$input['id'] : 0;
$direction = isset($input['direction']) ? $input['direction'] : '';

if (!$id || !in_array($direction, ['up', 'down'])) {
    echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);
    exit;
}

try {
    // 트랜잭션 시작
    $pdo->beginTransaction();
    
    // 현재 아이콘 정보 가져오기
    $stmt = $pdo->prepare("SELECT id, display_order FROM product_icons WHERE id = ?");
    $stmt->execute([$id]);
    $currentIcon = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$currentIcon) {
        throw new Exception('존재하지 않는 아이콘입니다.');
    }
    
    // 교체할 아이콘 찾기
    if ($direction === 'up') {
        // 이전 아이콘 찾기 (display_order가 작은 것 중 가장 큰 것)
        $sql = "SELECT id, display_order FROM product_icons 
                WHERE display_order < ? 
                ORDER BY display_order DESC 
                LIMIT 1";
    } else {
        // 다음 아이콘 찾기 (display_order가 큰 것 중 가장 작은 것)
        $sql = "SELECT id, display_order FROM product_icons 
                WHERE display_order > ? 
                ORDER BY display_order ASC 
                LIMIT 1";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$currentIcon['display_order']]);
    $targetIcon = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$targetIcon) {
        // 같은 순서값을 가진 아이콘들 중에서 찾기
        if ($direction === 'up') {
            $sql = "SELECT id, display_order FROM product_icons 
                    WHERE display_order = ? AND id < ? 
                    ORDER BY id DESC 
                    LIMIT 1";
        } else {
            $sql = "SELECT id, display_order FROM product_icons 
                    WHERE display_order = ? AND id > ? 
                    ORDER BY id ASC 
                    LIMIT 1";
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$currentIcon['display_order'], $currentIcon['id']]);
        $targetIcon = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    if (!$targetIcon) {
        throw new Exception('더 이상 이동할 수 없습니다.');
    }
    
    // 순서 교체
    $stmt = $pdo->prepare("UPDATE product_icons SET display_order = ? WHERE id = ?");
    $stmt->execute([$targetIcon['display_order'], $currentIcon['id']]);
    $stmt->execute([$currentIcon['display_order'], $targetIcon['id']]);
    
    // 트랜잭션 커밋
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => '순서가 변경되었습니다.']);
    
} catch (Exception $e) {
    // 트랜잭션 롤백
    $pdo->rollBack();
    error_log("update_icon_order error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '처리 중 오류가 발생했습니다.']);
}