<?php
session_start();
require_once 'db.php';
require_once 'includes/auth_tokens.php';
require_once 'includes/csrf.php';

// Remember Me 토큰 확인 (세션이 없는 경우)
if(!isset($_SESSION['member_id']) && isset($_COOKIE['remember_me'])) {
    $token = parseRememberCookie();
    if($token) {
        $member_id = verifyRememberToken($token['selector'], $token['validator']);
        if($member_id) {
            // 토큰이 유효한 경우 자동 로그인
            try {
                $stmt = $pdo->prepare("SELECT id, user_id, name, email, member_grade, is_admin FROM members WHERE id = ? AND is_active = 1");
                $stmt->execute([$member_id]);
                $member = $stmt->fetch();

                if($member) {
                    $_SESSION['member_id'] = $member['id'];
                    $_SESSION['user_id'] = $member['user_id'];
                    $_SESSION['member_name'] = $member['name'];
                    $_SESSION['member_email'] = $member['email'];
                    $_SESSION['member_grade'] = $member['member_grade'] ?? 'normal';
                    $_SESSION['is_admin'] = $member['is_admin'] ?? 0;

                    // 새 토큰 생성 (단일 사용 원칙)
                    $prefs = getMemberSessionPreferences($member_id);
                    $duration = $prefs['session_duration'];

                    // 종일 옵션 처리
                    if($duration == -1) {
                        $duration = calculateAllDayDuration();
                    } else if($duration == 0) {
                        $duration = 2592000; // 기본 30일
                    }

                    $new_token = createRememberToken($member_id, $duration);
                    if($new_token) {
                        setRememberCookie($new_token['selector'], $new_token['validator'], $duration);
                    }

                    header('Location: index.php');
                    exit;
                }
            } catch(PDOException $e) {
                // 오류 발생 시 토큰 삭제
                deleteRememberCookie();
            }
        } else {
            // 토큰이 유효하지 않은 경우 쿠키 삭제
            deleteRememberCookie();
        }
    }
}

// 이미 로그인한 경우 메인으로 리다이렉트
if(isset($_SESSION['member_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$redirect = $_GET['redirect'] ?? 'index.php';
// [보안] 오픈 리다이렉트 방지 - 내부 경로만 허용
if (preg_match('/^https?:\/\//i', $redirect) || preg_match('/^\/\//i', $redirect)) {
    $redirect = 'index.php';
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF 검증
    if (!verifyCsrfToken(false)) {
        $error = '잘못된 요청입니다. 페이지를 새로고침 해주세요.';
    }
    $user_id = trim($_POST['user_id'] ?? '');
    $password = $_POST['password'] ?? '';

    // [보안] IP 기반 로그인 Rate Limiting (15분 내 10회 실패 시 차단)
    $client_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!$error) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM member_login_logs WHERE login_ip = ? AND login_status = 'failed' AND login_date > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
            $stmt->execute([$client_ip]);
            if ($stmt->fetchColumn() >= 10) {
                $error = '로그인 시도가 너무 많습니다. 15분 후 다시 시도해주세요.';
            }
        } catch(Exception $e) {
            // 테이블 없으면 무시
        }
    }

    if($error) {
        // CSRF 검증 실패 또는 Rate Limit 초과 시 처리 건너뛰기
    } elseif($user_id && $password) {
        try {
            $stmt = $pdo->prepare("SELECT id, user_id, password, name, email, is_active, member_grade, is_admin FROM members WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $member = $stmt->fetch();
            
            if($member && password_verify($password, $member['password'])) {
                if($member['is_active'] == 1) {
                    // M5: 세션 고정 공격 방지
                    session_regenerate_id(true);
                    $_SESSION = []; // 세션 데이터 초기화 (관리자 세션 포함)

                    // 로그인 성공 - 회원 세션만 설정
                    $_SESSION['member_id'] = $member['id'];
                    $_SESSION['user_id'] = $member['user_id'];
                    $_SESSION['member_name'] = $member['name'];
                    $_SESSION['member_email'] = $member['email'];
                    $_SESSION['member_grade'] = $member['member_grade'] ?? 'normal';
                    $_SESSION['is_admin'] = $member['is_admin'] ?? 0;

                    // Remember Me 기능 처리
                    $remember_me = isset($_POST['remember']) && $_POST['remember'] == 'on';
                    if($remember_me) {
                        // 세션 설정 가져오기
                        $prefs = getMemberSessionPreferences($member['id']);

                        // 기능이 활성화된 경우에만 토큰 생성
                        if($prefs['remember_me_enabled']) {
                            $duration = $prefs['session_duration'];

                            // 종일 옵션 처리
                            if($duration == -1) {
                                $duration = calculateAllDayDuration();
                            } else if($duration == 0) {
                                $duration = 2592000; // 기본 30일
                            }

                            // 토큰 생성 및 쿠키 설정
                            $token = createRememberToken($member['id'], $duration);
                            if($token) {
                                setRememberCookie($token['selector'], $token['validator'], $duration);
                            }
                        }
                    }

                    // 마지막 로그인 시간 업데이트
                    $stmt = $pdo->prepare("UPDATE members SET last_login = NOW() WHERE id = ?");
                    $stmt->execute([$member['id']]);

                    // 로그인 로그 기록
                    try {
                        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
                        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

                        // 로그인 로그 추가
                        $stmt = $pdo->prepare("INSERT INTO member_login_logs
                            (member_id, login_date, login_ip, user_agent, login_status)
                            VALUES (?, NOW(), ?, ?, 'success')");
                        $stmt->execute([$member['id'], $ip_address, $user_agent]);
                        
                        // 로그인 카운트 증가
                        $stmt = $pdo->prepare("UPDATE members SET total_login_count = IFNULL(total_login_count, 0) + 1 WHERE id = ?");
                        $stmt->execute([$member['id']]);
                        
                        // 요약 테이블 업데이트
                        $stmt = $pdo->prepare("INSERT INTO member_login_summary 
                            (member_id, total_login_count, last_30days_count, last_7days_count, today_count) 
                            VALUES (?, 1, 1, 1, 1)
                            ON DUPLICATE KEY UPDATE 
                            total_login_count = total_login_count + 1,
                            last_30days_count = (
                                SELECT COUNT(*) FROM member_login_logs 
                                WHERE member_id = ? AND login_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                            ),
                            last_7days_count = (
                                SELECT COUNT(*) FROM member_login_logs 
                                WHERE member_id = ? AND login_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                            ),
                            today_count = (
                                SELECT COUNT(*) FROM member_login_logs 
                                WHERE member_id = ? AND DATE(login_date) = CURDATE()
                            )");
                        $stmt->execute([$member['id'], $member['id'], $member['id'], $member['id']]);
                    } catch(Exception $e) {
                        // 로그 기록 실패해도 로그인은 진행
                        error_log("Login log error: " . $e->getMessage());
                    }
                    
                    // 리다이렉트
                    header('Location: ' . urldecode($redirect));
                    exit;
                } else {
                    $error = '정지된 계정입니다. 관리자에게 문의하세요.';
                }
            } else {
                $error = '아이디 또는 비밀번호가 올바르지 않습니다.';
                // [보안] 로그인 실패 로그 기록
                try {
                    $fail_member_id = $member ? $member['id'] : null;
                    $stmt = $pdo->prepare("INSERT INTO member_login_logs (member_id, login_date, login_ip, user_agent, login_status) VALUES (?, NOW(), ?, ?, 'failed')");
                    $stmt->execute([$fail_member_id, $client_ip, $_SERVER['HTTP_USER_AGENT'] ?? '']);
                } catch(Exception $e) {
                    error_log("Login failure log error: " . $e->getMessage());
                }
            }
        } catch(PDOException $e) {
            $error = '로그인 중 오류가 발생했습니다.';
        }
    } else {
        $error = '아이디와 비밀번호를 입력해주세요.';
    }
}

$currentPage = 'login';
$pageTitle = '로그인';
include 'head.php';
?>

<style>
.login-section {
    padding: 80px 0;
    background: #F8F9FA;
    min-height: 80vh;
    display: flex;
    align-items: center;
}

.login-container {
    max-width: 400px;
    margin: 0 auto;
    padding: 0 20px;
    width: 100%;
}

.login-box {
    background: white;
    padding: 40px;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.login-title {
    font-size: 28px;
    font-weight: 700;
    color: #333;
    text-align: center;
    margin-bottom: 32px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
    font-size: 14px;
}

.form-group input {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #E5E5E7;
    border-radius: 8px;
    font-size: 16px;
    transition: all 0.3s ease;
}

.form-group input:focus {
    outline: none;
    border-color: var(--primary-blue);
}

.remember-forgot {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    font-size: 14px;
}

.remember-me {
    display: flex;
    align-items: center;
    gap: 8px;
}

.forgot-link {
    color: #666;
    text-decoration: none;
}

.forgot-link:hover {
    color: var(--primary-blue);
    text-decoration: underline;
}

.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    text-align: center;
}

.alert.error {
    background: #FFEBEE;
    color: #C62828;
}

.submit-btn {
    width: 100%;
    padding: 14px;
    background: var(--primary-blue);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.submit-btn:hover {
    background: #0F1F7A;
    transform: translateY(-1px);
}

.divider {
    text-align: center;
    margin: 24px 0;
    position: relative;
}

.divider::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    background: #E5E5E7;
}

.divider span {
    background: white;
    padding: 0 16px;
    position: relative;
    color: #999;
    font-size: 14px;
}

.register-btn {
    width: 100%;
    padding: 14px;
    background: white;
    color: var(--primary-blue);
    border: 2px solid var(--primary-blue);
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: block;
    text-align: center;
}

.register-btn:hover {
    background: var(--primary-blue);
    color: white;
}

@media (max-width: 768px) {
    .login-box {
        padding: 24px;
    }
}
</style>

<section class="login-section">
    <div class="login-container">
        <div class="login-box">
            <h2 class="login-title">로그인</h2>
            
            <?php if($error): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <?php echo csrfField(); ?>
                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">
                
                <div class="form-group">
                    <label for="user_id">아이디</label>
                    <input type="text" id="user_id" name="user_id" required autofocus
                           value="<?php echo htmlspecialchars($_POST['user_id'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">비밀번호</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="remember-forgot">
                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">로그인 상태 유지</label>
                    </div>
                    <a href="find_password.php" class="forgot-link">비밀번호 찾기</a>
                </div>
                
                <button type="submit" class="submit-btn">로그인</button>
            </form>
            
            <div class="divider">
                <span>또는</span>
            </div>
            
            <a href="register.php" class="register-btn">회원가입</a>
        </div>
    </div>
</section>

<?php include 'tail.php'; ?>