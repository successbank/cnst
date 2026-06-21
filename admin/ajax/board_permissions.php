<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../../db.php';
require_once '../admin_check.php';
require_once '../../includes/BoardPermissionHelper.php';

function jsonResponse($success, $message = '', $data = []) {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    // CSRF 검증
    $token = $input['csrf_token'] ?? '';
    if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        jsonResponse(false, '보안 토큰이 유효하지 않습니다.');
    }

    switch ($action) {
        case 'save_matrix':
            $matrix = $input['matrix'] ?? [];
            if (empty($matrix)) {
                jsonResponse(false, '저장할 데이터가 없습니다.');
            }

            // 허용 board_type / role_code / action_code 검증
            $validBoards = ['consignment', 'quote', 'brokerage'];
            $validRoles = ['guest', 'normal', 'silver', 'gold', 'vip', 'admin'];
            $validActions = ['list', 'read', 'write', 'comment'];

            foreach ($matrix as $board => $roles) {
                if (!in_array($board, $validBoards)) {
                    jsonResponse(false, '유효하지 않은 게시판 타입입니다.');
                }
                foreach ($roles as $role => $actions) {
                    if (!in_array($role, $validRoles)) {
                        jsonResponse(false, '유효하지 않은 역할입니다.');
                    }
                    foreach ($actions as $act => $val) {
                        if (!in_array($act, $validActions)) {
                            jsonResponse(false, '유효하지 않은 액션입니다.');
                        }
                    }
                }
            }

            if (BoardPermissionHelper::saveMatrix($matrix)) {
                jsonResponse(true, '권한 설정이 저장되었습니다.');
            } else {
                jsonResponse(false, '저장 중 오류가 발생했습니다.');
            }
            break;

        default:
            jsonResponse(false, '알 수 없는 액션입니다.');
    }
}

// GET: 매트릭스 조회
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    if ($action === 'get_matrix') {
        $matrix = BoardPermissionHelper::getFullMatrix();
        jsonResponse(true, '', ['matrix' => $matrix]);
    }

    jsonResponse(false, '알 수 없는 액션입니다.');
}
