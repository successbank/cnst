<?php
session_start();

// 관리자 권한 확인
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '인증되지 않은 요청입니다.']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

// Mailcow API 설정
define('MAILCOW_URL', 'https://mail.cnst.co.kr');
define('MAILCOW_ADMIN_USER', 'admin@cnst.co.kr');
define('MAILCOW_ADMIN_PASS', 'cndskatmxlf!@#4');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

/**
 * Mailcow 세션 로그인 후 API 호출
 */
function mailcowApiCall($method, $endpoint, $data = null) {
    $cookieFile = sys_get_temp_dir() . '/mailcow_session_' . md5(MAILCOW_ADMIN_USER) . '.txt';

    // 세션 쿠키가 없거나 오래된 경우 로그인
    if (!file_exists($cookieFile) || (time() - filemtime($cookieFile)) > 600) {
        $loginSuccess = mailcowLogin($cookieFile);
        if (!$loginSuccess) {
            return ['error' => 'Mailcow 로그인 실패'];
        }
    }

    $url = MAILCOW_URL . $endpoint;
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-Requested-With: XMLHttpRequest'],
        CURLOPT_TIMEOUT => 15,
    ]);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // 인증 만료 시 재로그인 후 재시도
    if ($httpCode === 401 || ($response === '' && $httpCode === 200)) {
        $loginSuccess = mailcowLogin($cookieFile);
        if ($loginSuccess) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_COOKIEFILE => $cookieFile,
                CURLOPT_COOKIEJAR => $cookieFile,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-Requested-With: XMLHttpRequest'],
                CURLOPT_TIMEOUT => 15,
            ]);
            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                if ($data !== null) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
            }
            $response = curl_exec($ch);
            curl_close($ch);
        }
    }

    $decoded = json_decode($response, true);
    return $decoded !== null ? $decoded : $response;
}

/**
 * Mailcow 관리자 로그인
 */
function mailcowLogin($cookieFile) {
    // 1. GET으로 CSRF 토큰 획득
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => MAILCOW_URL . '/',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_TIMEOUT => 10,
    ]);
    $html = curl_exec($ch);
    curl_close($ch);

    // CSRF 토큰 추출
    if (preg_match('/value="([a-f0-9]{64})"/', $html, $matches)) {
        $csrfToken = $matches[1];
    } else {
        return false;
    }

    // 2. POST 로그인
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => MAILCOW_URL . '/',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'login_user' => MAILCOW_ADMIN_USER,
            'pass_user' => MAILCOW_ADMIN_PASS,
            'csrf_token' => $csrfToken,
        ]),
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 10,
    ]);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // 302 리다이렉트 = 로그인 성공
    return $httpCode === 302;
}

try {
    switch ($action) {
        case 'list':
            $result = mailcowApiCall('GET', '/api/v1/get/mailbox/all');
            if (isset($result['error'])) {
                echo json_encode(['success' => false, 'message' => $result['error']]);
            } else {
                echo json_encode(['success' => true, 'data' => $result]);
            }
            break;

        case 'add':
            $input = json_decode(file_get_contents('php://input'), true);
            $localPart = trim($input['local_part'] ?? '');
            $name = trim($input['name'] ?? '');
            $password = $input['password'] ?? '';
            $quota = intval($input['quota'] ?? 3072); // MB

            if (empty($localPart) || empty($password)) {
                echo json_encode(['success' => false, 'message' => '아이디와 비밀번호는 필수입니다.']);
                break;
            }

            if (strlen($password) < 6) {
                echo json_encode(['success' => false, 'message' => '비밀번호는 6자 이상이어야 합니다.']);
                break;
            }

            if (!preg_match('/^[a-zA-Z0-9._-]+$/', $localPart)) {
                echo json_encode(['success' => false, 'message' => '아이디는 영문, 숫자, ., _, - 만 사용할 수 있습니다.']);
                break;
            }

            $result = mailcowApiCall('POST', '/api/v1/add/mailbox', [
                'local_part' => $localPart,
                'domain' => 'cnst.co.kr',
                'name' => $name,
                'password' => $password,
                'password2' => $password,
                'quota' => $quota,
                'active' => '1',
                'force_pw_update' => '0',
                'tls_enforce_in' => '0',
                'tls_enforce_out' => '0',
            ]);

            if (is_array($result) && isset($result[0]['type']) && $result[0]['type'] === 'success') {
                echo json_encode(['success' => true, 'message' => $localPart . '@cnst.co.kr 메일 계정이 생성되었습니다.']);
            } else {
                $errorMsg = '메일 계정 생성에 실패했습니다.';
                if (is_array($result) && isset($result[0]['msg'])) {
                    $errorMsg .= ' (' . implode(', ', (array)$result[0]['msg']) . ')';
                }
                echo json_encode(['success' => false, 'message' => $errorMsg]);
            }
            break;

        case 'delete':
            $input = json_decode(file_get_contents('php://input'), true);
            $username = trim($input['username'] ?? '');

            if (empty($username)) {
                echo json_encode(['success' => false, 'message' => '삭제할 메일 주소를 지정해주세요.']);
                break;
            }

            // admin@cnst.co.kr 삭제 방지
            if ($username === 'admin@cnst.co.kr') {
                echo json_encode(['success' => false, 'message' => '관리자 메일 계정은 삭제할 수 없습니다.']);
                break;
            }

            $result = mailcowApiCall('POST', '/api/v1/delete/mailbox', [$username]);

            if (is_array($result) && isset($result[0]['type']) && $result[0]['type'] === 'success') {
                echo json_encode(['success' => true, 'message' => $username . ' 메일 계정이 삭제되었습니다.']);
            } else {
                $errorMsg = '메일 계정 삭제에 실패했습니다.';
                if (is_array($result) && isset($result[0]['msg'])) {
                    $errorMsg .= ' (' . implode(', ', (array)$result[0]['msg']) . ')';
                }
                echo json_encode(['success' => false, 'message' => $errorMsg]);
            }
            break;

        case 'toggle_active':
            $input = json_decode(file_get_contents('php://input'), true);
            $username = trim($input['username'] ?? '');
            $active = intval($input['active'] ?? 1);

            if (empty($username)) {
                echo json_encode(['success' => false, 'message' => '메일 주소를 지정해주세요.']);
                break;
            }

            $result = mailcowApiCall('POST', '/api/v1/edit/mailbox', [
                'items' => [$username],
                'attr' => ['active' => $active ? '1' : '0'],
            ]);

            if (is_array($result) && isset($result[0]['type']) && $result[0]['type'] === 'success') {
                $statusText = $active ? '활성화' : '비활성화';
                echo json_encode(['success' => true, 'message' => $username . ' 계정이 ' . $statusText . '되었습니다.']);
            } else {
                echo json_encode(['success' => false, 'message' => '상태 변경에 실패했습니다.']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => '알 수 없는 액션입니다.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '오류: ' . $e->getMessage()]);
}
