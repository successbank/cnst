<?php
// 세션 및 데이터베이스 연결 먼저 처리
session_start();
require_once '../db.php';
require_once 'admin_check.php';
// CSRF 토큰은 admin_check.php → includes/csrf.php가 생성/관리한다. 폼에는 csrfField()로 출력.

$pageTitle = '회원 추가';

// 추가 스타일 정의
$additionalStyles = '
.form-container {
    background: white;
    padding: 32px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    max-width: 800px;
    margin: 0 auto;
}

.form-group {
    margin-bottom: 24px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
    font-size: 14px;
}

.form-group label .required {
    color: #E53935;
    margin-left: 4px;
}

.form-control {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #E5E5E7;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: #1A237E;
    box-shadow: 0 0 0 3px rgba(26,35,126,0.1);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
}

.form-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 32px;
    padding-top: 24px;
    border-top: 1px solid #E5E5E7;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
}

.btn-primary {
    background: #1A237E;
    color: white;
}

.btn-primary:hover {
    background: #283593;
}

.btn-secondary {
    background: #666;
    color: white;
}

.btn-secondary:hover {
    background: #555;
}

.phone-inputs {
    display: flex;
    gap: 8px;
    align-items: center;
}

.phone-inputs select,
.phone-inputs input {
    padding: 12px;
    border: 2px solid #E5E5E7;
    border-radius: 8px;
    font-size: 14px;
}

.phone-inputs select {
    width: 100px;
}

.phone-inputs input {
    width: 90px;
}

.phone-inputs span {
    padding: 0 4px;
}

.help-text {
    font-size: 12px;
    color: #666;
    margin-top: 4px;
}

.checkbox-group {
    display: flex;
    align-items: center;
    gap: 8px;
}

.checkbox-group input[type="checkbox"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
}

.checkbox-group label {
    margin-bottom: 0;
    font-weight: normal;
    cursor: pointer;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}
';

require_once 'admin_head.php';
?>

<div class="page-header">
    <h1>회원 추가</h1>
    <p>새로운 회원을 등록합니다.</p>
</div>

<?php
// 메시지 표시
if (isset($_SESSION['error_message'])) {
    echo '<div class="msg error">' . htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8') . '</div>';
    unset($_SESSION['error_message']);
}
?>

<div class="form-container">
    <form method="POST" action="admin_members_action.php" id="add-member-form">
        <input type="hidden" name="action" value="add_member">
        <?php echo csrfField(); ?>

        <div class="form-row">
            <div class="form-group">
                <label>아이디 <span class="required">*</span></label>
                <input type="text" name="user_id" class="form-control" required minlength="4" maxlength="20" pattern="[a-zA-Z0-9_]+">
                <div class="help-text">영문, 숫자, 언더스코어(_)만 사용 가능 (4-20자)</div>
            </div>
            
            <div class="form-group">
                <label>비밀번호 <span class="required">*</span></label>
                <input type="password" name="password" class="form-control" required minlength="4">
                <div class="help-text">최소 4자 이상</div>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>이름 <span class="required">*</span></label>
                <input type="text" name="name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>이메일 <span class="required">*</span></label>
                <input type="email" name="email" class="form-control" required>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>회사명</label>
                <input type="text" name="company" class="form-control">
            </div>
            
            <div class="form-group">
                <label>홈페이지</label>
                <input type="url" name="homepage" class="form-control" placeholder="https://example.com">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>휴대폰</label>
                <div class="phone-inputs">
                    <select name="phone_prefix">
                        <option value="010">010</option>
                        <option value="011">011</option>
                        <option value="016">016</option>
                        <option value="017">017</option>
                        <option value="018">018</option>
                        <option value="019">019</option>
                    </select>
                    <span>-</span>
                    <input type="text" name="phone_middle" placeholder="0000" maxlength="4">
                    <span>-</span>
                    <input type="text" name="phone_last" placeholder="0000" maxlength="4">
                </div>
                <input type="hidden" name="phone" id="phone-hidden">
            </div>
            
            <div class="form-group">
                <label>일반전화번호</label>
                <div class="phone-inputs">
                    <select name="landline_area" style="width: 120px;">
                        <option value="">지역번호</option>
                        <option value="02">02 (서울)</option>
                        <option value="031">031 (경기)</option>
                        <option value="032">032 (인천)</option>
                        <option value="033">033 (강원)</option>
                        <option value="041">041 (충남)</option>
                        <option value="042">042 (대전)</option>
                        <option value="043">043 (충북)</option>
                        <option value="044">044 (세종)</option>
                        <option value="051">051 (부산)</option>
                        <option value="052">052 (울산)</option>
                        <option value="053">053 (대구)</option>
                        <option value="054">054 (경북)</option>
                        <option value="055">055 (경남)</option>
                        <option value="061">061 (전남)</option>
                        <option value="062">062 (광주)</option>
                        <option value="063">063 (전북)</option>
                        <option value="064">064 (제주)</option>
                        <option value="070">070 (인터넷)</option>
                        <option value="other">기타</option>
                    </select>
                    <input type="text" name="landline_area_other" placeholder="0000" maxlength="4" style="width: 80px; display: none;">
                    <span>-</span>
                    <input type="text" name="landline_middle" placeholder="0000" maxlength="4">
                    <span>-</span>
                    <input type="text" name="landline_last" placeholder="0000" maxlength="4">
                </div>
                <input type="hidden" name="landline" id="landline-hidden">
            </div>
        </div>
        
        <div class="form-group">
            <label>주소</label>
            <div style="display: flex; gap: 12px; margin-bottom: 12px;">
                <input type="text" id="zipcode" name="zipcode" placeholder="우편번호" readonly 
                       class="form-control" style="width: 150px; background: #F8F9FA;">
                <button type="button" class="btn btn-secondary" onclick="findZipcode()">우편번호 찾기</button>
            </div>
            <input type="text" id="address" name="address" placeholder="기본주소" readonly 
                   class="form-control" style="background: #F8F9FA; margin-bottom: 12px;">
            <input type="text" id="address_detail" name="address_detail" placeholder="상세주소" 
                   class="form-control">
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>직급/부서</label>
                <input type="text" name="position" class="form-control">
            </div>
            
            <div class="form-group">
                <label>관리자 메모</label>
                <textarea name="memo" class="form-control" rows="3" placeholder="관리자만 볼 수 있는 메모입니다."></textarea>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" name="is_active" id="is_active" value="1" checked>
                    <label for="is_active">계정 활성화</label>
                </div>
                <div class="help-text">체크 해제 시 로그인이 제한됩니다.</div>
            </div>
        </div>
        
        <div class="form-actions">
            <a href="admin_members.php" class="btn btn-secondary">취소</a>
            <button type="submit" class="btn btn-primary">회원 추가</button>
        </div>
    </form>
</div>

<script src="//t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js"></script>
<script>
// 지역번호 선택 변경 시 기타 입력 필드 표시/숨김
document.querySelector('select[name="landline_area"]').addEventListener('change', function() {
    const otherInput = document.querySelector('input[name="landline_area_other"]');
    if (this.value === 'other') {
        otherInput.style.display = 'inline-block';
        otherInput.focus();
    } else {
        otherInput.style.display = 'none';
        otherInput.value = '';
    }
});

// 폼 제출 시 전화번호 조합
document.getElementById('add-member-form').addEventListener('submit', function(e) {
    // 휴대폰 번호 조합
    const phonePrefix = document.querySelector('select[name="phone_prefix"]').value;
    const phoneMiddle = document.querySelector('input[name="phone_middle"]').value;
    const phoneLast = document.querySelector('input[name="phone_last"]').value;
    
    const phoneHidden = document.getElementById('phone-hidden');
    if (phonePrefix && phoneMiddle && phoneLast) {
        phoneHidden.value = phonePrefix + '-' + phoneMiddle + '-' + phoneLast;
    } else {
        phoneHidden.value = '';
    }
    
    // 일반전화번호 조합
    const areaSelect = document.querySelector('select[name="landline_area"]');
    let area = areaSelect.value;
    
    // 기타 선택 시 직접 입력한 값 사용
    if (area === 'other') {
        area = document.querySelector('input[name="landline_area_other"]').value;
    }
    
    const middle = document.querySelector('input[name="landline_middle"]').value;
    const last = document.querySelector('input[name="landline_last"]').value;
    
    const landlineHidden = document.getElementById('landline-hidden');
    if (area && middle && last) {
        landlineHidden.value = area + '-' + middle + '-' + last;
    } else {
        landlineHidden.value = '';
    }
});

// 숫자만 입력 가능하도록 제한
document.querySelectorAll('input[name="phone_middle"], input[name="phone_last"], input[name="landline_middle"], input[name="landline_last"], input[name="landline_area_other"]').forEach(input => {
    input.addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '');
    });
});

// 아이디 입력 필드 - 영문, 숫자, 언더스코어만 허용
document.querySelector('input[name="user_id"]').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^a-zA-Z0-9_]/g, '');
});

function findZipcode() {
    new daum.Postcode({
        oncomplete: function(data) {
            document.getElementById('zipcode').value = data.zonecode;
            document.getElementById('address').value = data.roadAddress;
            document.getElementById('address_detail').focus();
        }
    }).open();
}
</script>

<?php require_once 'admin_tail.php'; ?>