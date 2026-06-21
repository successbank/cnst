<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../member_check.php';
require_once __DIR__ . '/../includes/BoardPermissionHelper.php';
require_once __DIR__ . '/../includes/input_validator.php';
require_once __DIR__ . '/../includes/csrf.php';

function jsonResp($success, $message = '', $data = []) {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data), JSON_UNESCAPED_UNICODE);
    exit;
}

$validBoards = ['consignment', 'quote', 'brokerage'];

// GET: 댓글 목록 조회
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    if ($action === 'list') {
        $boardType = $_GET['board_type'] ?? '';
        $boardId = (int)($_GET['board_id'] ?? 0);

        if (!in_array($boardType, $validBoards) || !$boardId) {
            jsonResp(false, '잘못된 요청입니다.');
        }

        try {
            $pdo = getDB();
            $stmt = $pdo->prepare("
                SELECT id, board_type, board_id, member_id, is_admin, content, writer, created_at, updated_at
                FROM board_comments
                WHERE board_type = ? AND board_id = ? AND is_deleted = 0
                ORDER BY created_at ASC
            ");
            $stmt->execute([$boardType, $boardId]);
            $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $currentMemberId = $_SESSION['member_id'] ?? null;
            $isCurrentAdmin = (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true)
                           || (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1);

            foreach ($comments as &$c) {
                $c['can_delete'] = false;
                if ($isCurrentAdmin) {
                    $c['can_delete'] = true;
                } elseif ($currentMemberId && $c['member_id'] == $currentMemberId) {
                    $c['can_delete'] = true;
                } elseif (!$c['member_id']) {
                    $c['can_delete'] = 'password_required';
                }
                $c['created_at'] = date('Y-m-d H:i', strtotime($c['created_at']));
            }
            unset($c);

            jsonResp(true, '', ['comments' => $comments]);
        } catch (PDOException $e) {
            error_log("board_comments list error: " . $e->getMessage());
            jsonResp(false, '댓글을 불러오는 중 오류가 발생했습니다.');
        }
    }

    jsonResp(false, '알 수 없는 요청입니다.');
}

// POST: 댓글 작성/삭제
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF 토큰 검증
    if (!verifyCsrfToken(false)) {
        http_response_code(403);
        jsonResp(false, 'CSRF 토큰이 유효하지 않습니다.');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    switch ($action) {
        case 'create':
            // IP 기반 Rate Limiting (5분 내 20회)
            if (!checkIpRateLimit('board_comment', 20, 300)) {
                jsonResp(false, '너무 많은 요청이 감지되었습니다. 잠시 후 다시 시도해주세요.');
            }

            // 세션 기반 Rate Limiting (1분 내 5회)
            if (!checkRateLimit('board_comment', 5, 60)) {
                jsonResp(false, '잠시 후 다시 시도해주세요.');
            }

            $boardType = $input['board_type'] ?? '';
            $boardId = (int)($input['board_id'] ?? 0);
            $content = trim($input['content'] ?? '');
            $writer = trim($input['writer'] ?? '');
            $password = $input['password'] ?? null;

            if (!in_array($boardType, $validBoards) || !$boardId) {
                jsonResp(false, '잘못된 요청입니다.');
            }

            if (!BoardPermissionHelper::canAccess($boardType, 'comment')) {
                jsonResp(false, '댓글 작성 권한이 없습니다.');
            }

            if (empty($content)) {
                jsonResp(false, '댓글 내용을 입력해주세요.');
            }
            if (mb_strlen($content) > 1000) {
                jsonResp(false, '댓글은 1000자 이내로 입력해주세요.');
            }
            if (empty($writer)) {
                jsonResp(false, '작성자명을 입력해주세요.');
            }

            // 입력값 검증 (SQL 인젝션 패턴 차단)
            if (containsSuspiciousPattern($content) || containsSuspiciousPattern($writer)) {
                jsonResp(false, '허용되지 않는 입력값이 포함되어 있습니다.');
            }

            $memberId = $_SESSION['member_id'] ?? null;
            $isAdmin = (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true)
                    || (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) ? 1 : 0;

            if (!$memberId && empty($password)) {
                jsonResp(false, '비밀번호를 입력해주세요.');
            }

            $hashedPassword = $password ? password_hash($password, PASSWORD_DEFAULT) : null;

            try {
                $pdo = getDB();
                $stmt = $pdo->prepare("
                    INSERT INTO board_comments (board_type, board_id, member_id, is_admin, content, writer, password)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$boardType, $boardId, $memberId, $isAdmin, $content, $writer, $hashedPassword]);

                jsonResp(true, '댓글이 등록되었습니다.');
            } catch (PDOException $e) {
                error_log("board_comments create error: " . $e->getMessage());
                jsonResp(false, '댓글 등록 중 오류가 발생했습니다.');
            }
            break;

        case 'delete':
            $commentId = (int)($input['comment_id'] ?? 0);
            $password = $input['password'] ?? null;

            if (!$commentId) {
                jsonResp(false, '잘못된 요청입니다.');
            }

            try {
                $pdo = getDB();
                $stmt = $pdo->prepare("SELECT id, member_id, password FROM board_comments WHERE id = ? AND is_deleted = 0");
                $stmt->execute([$commentId]);
                $comment = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$comment) {
                    jsonResp(false, '댓글을 찾을 수 없습니다.');
                }

                $currentMemberId = $_SESSION['member_id'] ?? null;
                $isCurrentAdmin = (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true)
                               || (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1);

                $canDelete = false;

                if ($isCurrentAdmin) {
                    $canDelete = true;
                } elseif ($currentMemberId && $comment['member_id'] == $currentMemberId) {
                    $canDelete = true;
                } elseif (!$comment['member_id'] && $password) {
                    if (password_verify($password, $comment['password'])) {
                        $canDelete = true;
                    } else {
                        jsonResp(false, '비밀번호가 일치하지 않습니다.');
                    }
                }

                if (!$canDelete) {
                    jsonResp(false, '삭제 권한이 없습니다.');
                }

                $stmt = $pdo->prepare("UPDATE board_comments SET is_deleted = 1, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$commentId]);

                jsonResp(true, '댓글이 삭제되었습니다.');
            } catch (PDOException $e) {
                error_log("board_comments delete error: " . $e->getMessage());
                jsonResp(false, '댓글 삭제 중 오류가 발생했습니다.');
            }
            break;

        default:
            jsonResp(false, '알 수 없는 액션입니다.');
    }
}
