<?php
session_start();
require_once 'db.php';
require_once 'includes/csrf.php';

// 이미 로그인한 경우 메인으로 리다이렉트
if(isset($_SESSION['member_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF 검증
    if (!verifyCsrfToken(false)) {
        $error = '잘못된 요청입니다. 페이지를 새로고침 해주세요.';
    }

    $user_id = trim($_POST['user_id'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $landline = trim($_POST['landline'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $homepage = trim($_POST['homepage'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $zipcode = trim($_POST['zipcode'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $address_detail = trim($_POST['address_detail'] ?? '');

    // 유효성 검사
    if($error) {
        // CSRF 실패 시 건너뛰기
    } elseif(strlen($user_id) < 4) {
        $error = '아이디는 4자 이상이어야 합니다.';
    } elseif(strlen($password) < 4 || strlen($password) > 64) {
        $error = '비밀번호는 4자 이상이어야 합니다.';
    } elseif($password !== $password_confirm) {
        $error = '비밀번호가 일치하지 않습니다.';
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '올바른 이메일 주소를 입력해주세요.';
    } elseif(empty($phone)) {
        $error = '휴대폰 번호를 입력해주세요.';
    } elseif(!preg_match('/^01[0-9][-]?[0-9]{3,4}[-]?[0-9]{4}$/', $phone)) {
        $error = '올바른 휴대폰 번호 형식을 입력해주세요. (예: 010-0000-0000 또는 01000000000)';
    } else {
        try {
            // 아이디 중복 체크
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE user_id = ?");
            $stmt->execute([$user_id]);
            if($stmt->fetchColumn() > 0) {
                $error = '이미 사용중인 아이디입니다.';
            } else {
                // 이메일 중복 체크
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE email = ?");
                $stmt->execute([$email]);
                if($stmt->fetchColumn() > 0) {
                    $error = '이미 사용중인 이메일입니다.';
                } else {
                    // 회원가입 처리
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("
                        INSERT INTO members (user_id, password, name, email, phone, landline, company, homepage, position, zipcode, address, address_detail) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$user_id, $hashed_password, $name, $email, $phone, $landline, $company, $homepage, $position, $zipcode, $address, $address_detail]);
                    
                    // 회원가입 성공 시 자동 로그인 처리
                    session_regenerate_id(true);
                    $_SESSION['member_id'] = $pdo->lastInsertId();
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['member_name'] = $name;
                    $_SESSION['member_email'] = $email;
                    
                    // 메인 페이지로 즉시 이동
                    header('Location: index.php');
                    exit;
                }
            }
        } catch(PDOException $e) {
            $error = '회원가입 중 오류가 발생했습니다. 다시 시도해주세요.';
            error_log('Register error: ' . $e->getMessage());
        }
    }
}

$currentPage = 'register';
$pageTitle = '회원가입';
include 'head.php';
?>

<style>
.register-section {
    padding: 60px 0;
    background: #F8F9FA;
    min-height: 80vh;
}

.register-container {
    max-width: 600px;
    margin: 0 auto;
    padding: 0 20px;
}

.register-box {
    background: white;
    padding: 40px;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.register-title {
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

.form-group label span {
    color: #F44336;
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

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.address-group {
    display: flex;
    gap: 12px;
    margin-bottom: 12px;
}

.zipcode-input {
    width: 150px !important;
}

.btn-find-zipcode {
    padding: 12px 20px;
    background: #666;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    cursor: pointer;
    white-space: nowrap;
}

.btn-find-zipcode:hover {
    background: #555;
}

/* 아이디 중복체크·비밀번호 확인 실시간 메시지 */
.id-check-message,
.field-check-message {
    margin-top: 6px;
    font-size: 13px;
    min-height: 18px;
}

.id-check-message.available,
.field-check-message.available {
    color: #1a7f37;
}

.id-check-message.unavailable,
.field-check-message.unavailable {
    color: #d32f2f;
}

.id-check-message.checking,
.field-check-message.checking {
    color: #888;
}

/* 우편번호 검색 레이어(모달) 팝업 */
.postcode-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 10000;
    align-items: center;
    justify-content: center;
    padding: 16px;
}

.postcode-modal-overlay.active {
    display: flex;
}

.postcode-modal {
    position: relative;
    width: 100%;
    max-width: 500px;
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.25);
}

.postcode-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    border-bottom: 1px solid #eee;
    font-size: 15px;
    font-weight: 600;
}

.postcode-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    line-height: 1;
    cursor: pointer;
    color: #666;
    padding: 0 4px;
}

.postcode-modal-close:hover {
    color: #111;
}

.postcode-modal-body {
    width: 100%;
    height: 460px;
}

.postcode-modal-body > div,
.postcode-modal-body iframe {
    width: 100% !important;
    height: 100% !important;
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

.alert.success {
    background: #E8F5E9;
    color: #2E7D32;
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

.login-link {
    text-align: center;
    margin-top: 24px;
    font-size: 14px;
    color: #666;
}

.login-link a {
    color: var(--primary-blue);
    text-decoration: none;
    font-weight: 600;
}

.login-link a:hover {
    text-decoration: underline;
}

@media (max-width: 768px) {
    .register-box {
        padding: 24px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<section class="register-section">
    <div class="register-container">
        <div class="register-box">
            <h2 class="register-title">회원가입</h2>
            
            <?php if($error): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="alert success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <?php echo csrfField(); ?>
                <div class="form-group">
                    <label for="user_id">아이디 <span>*</span></label>
                    <input type="text" id="user_id" name="user_id" required 
                           minlength="4" maxlength="20"
                           pattern="[a-zA-Z0-9]+"
                           title="영문자와 숫자만 사용 가능합니다"
                           value="<?php echo htmlspecialchars($_POST['user_id'] ?? ''); ?>">
                    <div class="id-check-message" id="idCheckMessage"></div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password">비밀번호 <span>*</span></label>
                        <input type="password" id="password" name="password" required minlength="4" maxlength="64" placeholder="영문 또는 숫자 4자 이상">
                        <div class="field-check-message" id="pwLengthMessage"></div>
                    </div>

                    <div class="form-group">
                        <label for="password_confirm">비밀번호 확인 <span>*</span></label>
                        <input type="password" id="password_confirm" name="password_confirm" required minlength="4" maxlength="64">
                        <div class="field-check-message" id="pwMatchMessage"></div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="name">이름 <span>*</span></label>
                    <input type="text" id="name" name="name" required 
                           value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">이메일 <span>*</span></label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="phone">휴대폰 번호 <span>*</span></label>
                    <input type="tel" id="phone" name="phone" required
                           placeholder="010-0000-0000"
                           pattern="01[0-9]-?[0-9]{3,4}-?[0-9]{4}"
                           title="올바른 휴대폰 번호를 입력해주세요 (예: 010-0000-0000 또는 01000000000)"
                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="landline">일반전화번호</label>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <?php
                        $landline_parts = [];
                        $landline = $_POST['landline'] ?? '';
                        if ($landline) {
                            $landline_parts = explode('-', $landline);
                        }
                        // 기존 지역번호 목록에 없는 경우 확인
                        $known_areas = ['02', '031', '032', '033', '041', '042', '043', '044', '050', '051', '052', '053', '054', '055', '061', '062', '063', '064', '070'];
                        $is_other = isset($landline_parts[0]) && !in_array($landline_parts[0], $known_areas);
                        ?>
                        <select name="landline_area" style="width: 138px; padding: 12px 16px; border: 2px solid #E5E5E7; border-radius: 8px; font-size: 16px;">
                            <option value="">지역번호</option>
                            <option value="02" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '02') ? 'selected' : ''; ?>>02 (서울)</option>
                            <option value="031" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '031') ? 'selected' : ''; ?>>031 (경기)</option>
                            <option value="032" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '032') ? 'selected' : ''; ?>>032 (인천)</option>
                            <option value="033" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '033') ? 'selected' : ''; ?>>033 (강원)</option>
                            <option value="041" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '041') ? 'selected' : ''; ?>>041 (충남)</option>
                            <option value="042" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '042') ? 'selected' : ''; ?>>042 (대전)</option>
                            <option value="043" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '043') ? 'selected' : ''; ?>>043 (충북)</option>
                            <option value="044" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '044') ? 'selected' : ''; ?>>044 (세종)</option>
                            <option value="050" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '050') ? 'selected' : ''; ?>>050 (평신)</option>
                            <option value="051" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '051') ? 'selected' : ''; ?>>051 (부산)</option>
                            <option value="052" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '052') ? 'selected' : ''; ?>>052 (울산)</option>
                            <option value="053" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '053') ? 'selected' : ''; ?>>053 (대구)</option>
                            <option value="054" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '054') ? 'selected' : ''; ?>>054 (경북)</option>
                            <option value="055" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '055') ? 'selected' : ''; ?>>055 (경남)</option>
                            <option value="061" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '061') ? 'selected' : ''; ?>>061 (전남)</option>
                            <option value="062" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '062') ? 'selected' : ''; ?>>062 (광주)</option>
                            <option value="063" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '063') ? 'selected' : ''; ?>>063 (전북)</option>
                            <option value="064" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '064') ? 'selected' : ''; ?>>064 (제주)</option>
                            <option value="070" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '070') ? 'selected' : ''; ?>>070 (인터넷)</option>
                            <option value="other" <?php echo $is_other ? 'selected' : ''; ?>>기타</option>
                        </select>
                        <input type="text" name="landline_area_other" placeholder="0000" maxlength="4" 
                               style="width: 90px; padding: 12px 16px; border: 2px solid #E5E5E7; border-radius: 8px; font-size: 16px; display: <?php echo $is_other ? 'inline-block' : 'none'; ?>;" 
                               value="<?php echo $is_other ? htmlspecialchars($landline_parts[0]) : ''; ?>">
                        <span style="padding: 0 5px;">-</span>
                        <input type="text" name="landline_middle" placeholder="0000" maxlength="4" 
                               style="width: 90px; padding: 12px 16px; border: 2px solid #E5E5E7; border-radius: 8px; font-size: 16px;"
                               value="<?php echo htmlspecialchars($landline_parts[1] ?? ''); ?>">
                        <span style="padding: 0 5px;">-</span>
                        <input type="text" name="landline_last" placeholder="0000" maxlength="4" 
                               style="width: 90px; padding: 12px 16px; border: 2px solid #E5E5E7; border-radius: 8px; font-size: 16px;"
                               value="<?php echo htmlspecialchars($landline_parts[2] ?? ''); ?>">
                    </div>
                    <input type="hidden" id="landline" name="landline" value="<?php echo htmlspecialchars($_POST['landline'] ?? ''); ?>">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="company">회사명</label>
                        <input type="text" id="company" name="company" 
                               value="<?php echo htmlspecialchars($_POST['company'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="homepage">홈페이지</label>
                        <input type="url" id="homepage" name="homepage" 
                               placeholder="https://example.com"
                               value="<?php echo htmlspecialchars($_POST['homepage'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="position">직급/부서</label>
                        <input type="text" id="position" name="position" 
                               value="<?php echo htmlspecialchars($_POST['position'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address">주소</label>
                    <div class="address-group">
                        <input type="text" id="zipcode" name="zipcode" class="zipcode-input" 
                               placeholder="우편번호" readonly
                               value="<?php echo htmlspecialchars($_POST['zipcode'] ?? ''); ?>">
                        <button type="button" class="btn-find-zipcode" onclick="findZipcode()">우편번호 찾기</button>
                    </div>
                    <input type="text" id="address" name="address" placeholder="기본주소" readonly
                           value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <input type="text" id="address_detail" name="address_detail" placeholder="상세주소"
                           value="<?php echo htmlspecialchars($_POST['address_detail'] ?? ''); ?>">
                </div>
                
                <button type="submit" class="submit-btn">회원가입</button>
            </form>
            
            <div class="login-link">
                이미 회원이신가요? <a href="login.php">로그인</a>
            </div>
        </div>
    </div>
</section>

<!-- 우편번호 검색 레이어(모달) 팝업 -->
<div class="postcode-modal-overlay" id="postcodeModal">
    <div class="postcode-modal">
        <div class="postcode-modal-header">
            <span>우편번호 검색</span>
            <button type="button" class="postcode-modal-close" onclick="closePostcodeModal()" aria-label="닫기">&times;</button>
        </div>
        <div class="postcode-modal-body" id="postcodeModalBody"></div>
    </div>
</div>

<script src="//t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js"></script>
<script>
// DOM이 완전히 로드된 후 실행
document.addEventListener('DOMContentLoaded', function() {
    // 지역번호 선택 변경 시 기타 입력 필드 표시/숨김
    const landlineAreaSelect = document.querySelector('select[name="landline_area"]');
    if (landlineAreaSelect) {
        landlineAreaSelect.addEventListener('change', function() {
            const otherInput = document.querySelector('input[name="landline_area_other"]');
            if (otherInput) {
                if (this.value === 'other') {
                    otherInput.style.display = 'inline-block';
                    otherInput.focus();
                } else {
                    otherInput.style.display = 'none';
                    otherInput.value = '';
                }
            }
        });
    }

    // 폼 제출 시 일반전화번호 조합
    const registerForm = document.querySelector('#register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            const areaSelect = document.querySelector('select[name="landline_area"]');
            if (areaSelect) {
                let area = areaSelect.value;
                
                // 기타 선택 시 직접 입력한 값 사용
                if (area === 'other') {
                    const areaOther = document.querySelector('input[name="landline_area_other"]');
                    area = areaOther ? areaOther.value : '';
                }
                
                const middleInput = document.querySelector('input[name="landline_middle"]');
                const lastInput = document.querySelector('input[name="landline_last"]');
                const landlineInput = document.querySelector('input[name="landline"]');
                
                if (middleInput && lastInput && landlineInput) {
                    const middle = middleInput.value;
                    const last = lastInput.value;
                    
                    if (area && middle && last) {
                        landlineInput.value = area + '-' + middle + '-' + last;
                    } else {
                        landlineInput.value = '';
                    }
                }
            }
        });
    }

    // 숫자만 입력 가능하도록 제한
    document.querySelectorAll('input[name="landline_middle"], input[name="landline_last"], input[name="landline_area_other"]').forEach(input => {
        if (input) {
            input.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
        }
    });

    // 비밀번호 · 비밀번호 확인 실시간 검증
    const passwordInput = document.getElementById('password');
    const passwordConfirm = document.getElementById('password_confirm');
    const pwLengthMessage = document.getElementById('pwLengthMessage');
    const pwMatchMessage = document.getElementById('pwMatchMessage');

    function setFieldMessage(el, text, state) {
        if (!el) return;
        el.textContent = text;
        el.className = 'field-check-message' + (state ? ' ' + state : '');
    }

    function validatePasswords() {
        if (!passwordInput || !passwordConfirm) return;
        const pw = passwordInput.value;
        const confirm = passwordConfirm.value;

        // 비밀번호 길이 실시간 안내
        if (pw.length === 0) {
            setFieldMessage(pwLengthMessage, '', '');
        } else if (pw.length < 4) {
            setFieldMessage(pwLengthMessage, '비밀번호는 4자 이상이어야 합니다.', 'unavailable');
        } else {
            setFieldMessage(pwLengthMessage, '사용 가능한 비밀번호입니다.', 'available');
        }

        // 비밀번호 일치 실시간 안내
        if (confirm.length === 0) {
            setFieldMessage(pwMatchMessage, '', '');
            passwordConfirm.setCustomValidity('');
        } else if (pw !== confirm) {
            setFieldMessage(pwMatchMessage, '비밀번호가 일치하지 않습니다.', 'unavailable');
            passwordConfirm.setCustomValidity('비밀번호가 일치하지 않습니다.');
        } else {
            setFieldMessage(pwMatchMessage, '비밀번호가 일치합니다.', 'available');
            passwordConfirm.setCustomValidity('');
        }
    }

    if (passwordInput && passwordConfirm) {
        passwordInput.addEventListener('input', validatePasswords);
        passwordConfirm.addEventListener('input', validatePasswords);
    }
    // 아이디 실시간 중복체크
    const userIdInput = document.getElementById('user_id');
    const idCheckMessage = document.getElementById('idCheckMessage');
    let idCheckTimer = null;
    let idCheckState = ''; // '' | 'available' | 'unavailable'

    function setIdCheckMessage(text, state) {
        idCheckMessage.textContent = text;
        idCheckMessage.className = 'id-check-message' + (state ? ' ' + state : '');
    }

    if (userIdInput && idCheckMessage) {
        userIdInput.addEventListener('input', function() {
            clearTimeout(idCheckTimer);
            const value = this.value.trim();
            idCheckState = '';

            if (value.length === 0) {
                setIdCheckMessage('', '');
                return;
            }
            if (value.length < 4) {
                setIdCheckMessage('아이디는 4자 이상이어야 합니다.', 'unavailable');
                return;
            }
            if (!/^[a-zA-Z0-9]+$/.test(value)) {
                setIdCheckMessage('아이디는 영문자와 숫자만 사용 가능합니다.', 'unavailable');
                return;
            }

            setIdCheckMessage('확인 중...', 'checking');
            idCheckTimer = setTimeout(function() {
                const params = new URLSearchParams();
                params.append('user_id', value);
                const csrfInput = document.querySelector('input[name="csrf_token"]');
                if (csrfInput) {
                    params.append('csrf_token', csrfInput.value);
                }
                fetch('ajax/check_userid.php', { method: 'POST', body: params })
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        // 응답 도착 전 입력이 바뀐 경우 무시
                        if (userIdInput.value.trim() !== value) return;
                        idCheckState = data.available ? 'available' : 'unavailable';
                        setIdCheckMessage(data.message, idCheckState);
                    })
                    .catch(function() {
                        setIdCheckMessage('확인 중 오류가 발생했습니다.', 'checking');
                    });
            }, 400);
        });

        // 중복 아이디 상태에서 제출 차단
        if (userIdInput.form) {
            userIdInput.form.addEventListener('submit', function(e) {
                if (idCheckState === 'unavailable') {
                    e.preventDefault();
                    alert('사용할 수 없는 아이디입니다. 다른 아이디를 입력해주세요.');
                    userIdInput.focus();
                }
            });
        }
    }

    // 우편번호 모달: 배경(오버레이) 클릭 또는 ESC 키로 닫기
    const postcodeModal = document.getElementById('postcodeModal');
    if (postcodeModal) {
        postcodeModal.addEventListener('click', function(e) {
            if (e.target === postcodeModal) {
                closePostcodeModal();
            }
        });
    }
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closePostcodeModal();
        }
    });
}); // DOMContentLoaded 끝

// findZipcode / closePostcodeModal 함수는 전역 스코프에 있어야 함
function findZipcode() {
    var modal = document.getElementById('postcodeModal');
    var body = document.getElementById('postcodeModalBody');
    if (!modal || !body) { return; }

    // 이전에 남은 내용 정리 후 모달 표시(embed 전 컨테이너가 보여야 크기 계산이 정확함)
    body.innerHTML = '';
    modal.classList.add('active');

    new daum.Postcode({
        oncomplete: function(data) {
            document.getElementById('zipcode').value = data.zonecode;
            document.getElementById('address').value = data.roadAddress;
            closePostcodeModal();
            document.getElementById('address_detail').focus();
        },
        onresize: function(size) {
            body.style.height = size.height + 'px';
        },
        width: '100%',
        height: '100%'
    }).embed(body);
}

function closePostcodeModal() {
    var modal = document.getElementById('postcodeModal');
    if (!modal) { return; }
    modal.classList.remove('active');
    var body = document.getElementById('postcodeModalBody');
    if (body) { body.innerHTML = ''; }
}
</script>

<?php include 'tail.php'; ?>