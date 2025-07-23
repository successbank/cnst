<?php
$pageTitle = '회원 관리';

// 추가 스타일 정의
$additionalStyles = '
.search-bar {
    display: flex;
    gap: 12px;
    margin-bottom: 24px;
}

.search-input {
    flex: 1;
    padding: 12px 16px;
    border: 2px solid #E5E5E7;
    border-radius: 8px;
    font-size: 14px;
}

.search-input:focus {
    outline: none;
    border-color: #1A237E;
}

.filter-select {
    padding: 12px 16px;
    border: 2px solid #E5E5E7;
    border-radius: 8px;
    font-size: 14px;
    background: white;
    cursor: pointer;
}

.search-btn {
    padding: 12px 24px;
    background: #1A237E;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.search-btn:hover {
    background: #283593;
}

.members-table {
    width: 100%;
    border-collapse: collapse;
}

.members-table th,
.members-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #E5E5E7;
}

.members-table th {
    font-weight: 600;
    color: #666;
    font-size: 14px;
}

.members-table tr:hover {
    background: #F5F5F7;
}

.status-active {
    background: #E8F5E9;
    color: #2E7D32;
}

.status-inactive {
    background: #FFEBEE;
    color: #C62828;
}


.member-detail {
    background: #F5F5F7;
    padding: 24px;
    border-radius: 8px;
    margin-bottom: 24px;
}

.detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
}

.detail-item {
    background: white;
    padding: 16px;
    border-radius: 8px;
}

.detail-label {
    font-size: 12px;
    color: #666;
    margin-bottom: 4px;
}

.detail-value {
    font-size: 16px;
    font-weight: 600;
    color: #333;
}

.back-btn {
    display: inline-block;
    padding: 10px 20px;
    background: #666;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-size: 14px;
    margin-bottom: 20px;
}

.back-btn:hover {
    background: #555;
}

.content-box {
    background: white;
    padding: 32px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
';

require_once 'admin_head.php';

// 액션 처리
$action = $_GET['action'] ?? 'list';

// 회원 상태 변경 처리
if($action === 'toggle_status' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("UPDATE members SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: admin_members.php?msg=status_changed');
        exit;
    } catch(PDOException $e) {
        $error = "상태 변경 중 오류가 발생했습니다.";
    }
}

// 회원 삭제 처리
if($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM members WHERE id = ? AND is_admin = 0");
        $stmt->execute([$id]);
        header('Location: admin_members.php?msg=deleted');
        exit;
    } catch(PDOException $e) {
        $error = "삭제 중 오류가 발생했습니다.";
    }
}

// 검색 및 필터
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? 'all';

// 회원 상세 정보
$member_detail = null;
if($action === 'view' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
        $stmt->execute([$id]);
        $member_detail = $stmt->fetch();
        
        if(!$member_detail) {
            header('Location: admin_members.php');
            exit;
        }
    } catch(PDOException $e) {
        $error = "회원 정보를 불러올 수 없습니다.";
    }
}

// 회원 목록 가져오기
if($action === 'list') {
    try {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        // 기본 쿼리
        $where_conditions = [];
        $params = [];
        
        if($search) {
            $where_conditions[] = "(user_id LIKE ? OR name LIKE ? OR email LIKE ? OR company LIKE ?)";
            $search_param = "%$search%";
            $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
        }
        
        if($filter === 'active') {
            $where_conditions[] = "is_active = 1";
        } elseif($filter === 'inactive') {
            $where_conditions[] = "is_active = 0";
        }
        
        $where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        // 전체 개수
        $count_query = "SELECT COUNT(*) FROM members $where_clause";
        $stmt = $pdo->prepare($count_query);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();
        
        // 회원 목록
        $list_query = "SELECT * FROM members $where_clause ORDER BY id DESC LIMIT ? OFFSET ?";
        
        $stmt = $pdo->prepare($list_query);
        // 기존 파라미터 바인딩
        $paramIndex = 1;
        foreach($params as $param) {
            $stmt->bindValue($paramIndex++, $param);
        }
        // LIMIT과 OFFSET은 정수로 바인딩
        $stmt->bindValue($paramIndex++, $limit, PDO::PARAM_INT);
        $stmt->bindValue($paramIndex, $offset, PDO::PARAM_INT);
        $stmt->execute();
        $members = $stmt->fetchAll();
        
        $totalPages = ceil($total / $limit);
    } catch(PDOException $e) {
        $members = [];
        $totalPages = 0;
        $error = "데이터를 불러올 수 없습니다: " . $e->getMessage();
    }
}
?>

        <?php if(isset($_GET['msg'])): ?>
            <div class="msg success">
                <?php
                switch($_GET['msg']) {
                    case 'status_changed': echo "회원 상태가 변경되었습니다."; break;
                    case 'deleted': echo "회원이 삭제되었습니다."; break;
                    case 'password_changed': echo "비밀번호가 성공적으로 변경되었습니다."; break;
                    case 'info_updated': echo "회원 정보가 성공적으로 수정되었습니다."; break;
                }
                ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($error)): ?>
            <div class="msg error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if(isset($_GET['error'])): ?>
            <div class="msg error">
                <?php
                switch($_GET['error']) {
                    case 'password_too_short': echo "비밀번호는 4자 이상이어야 합니다."; break;
                    case 'password_mismatch': echo "비밀번호가 일치하지 않습니다."; break;
                    case 'update_failed': echo "정보 수정 중 오류가 발생했습니다."; break;
                    case 'invalid_member': echo "잘못된 회원 정보입니다."; break;
                    case 'invalid_email': echo "올바른 이메일 주소를 입력해주세요."; break;
                    case 'email_exists': echo "이미 사용중인 이메일입니다."; break;
                }
                ?>
            </div>
        <?php endif; ?>
        
        <?php if($action === 'view' && $member_detail): ?>
            <a href="admin_members.php" class="back-btn">← 목록으로</a>
            
            <div class="page-header">
                <h1>회원 상세 정보</h1>
                <p><?php echo htmlspecialchars($member_detail['name']); ?>님의 상세 정보입니다.</p>
            </div>
            
            <div class="content-box">
                <!-- 회원 정보 수정 폼 -->
                <div class="member-detail">
                    <h3 style="font-size: 18px; margin-bottom: 16px;">회원 정보</h3>
                    <form method="POST" action="admin_members_action.php">
                        <input type="hidden" name="action" value="update_member">
                        <input type="hidden" name="member_id" value="<?php echo $member_detail['id']; ?>">
                        <div class="detail-grid">
                            <div class="detail-item">
                                <div class="detail-label">아이디</div>
                                <div class="detail-value"><?php echo htmlspecialchars($member_detail['user_id']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">이름</div>
                                <div class="detail-value"><?php echo htmlspecialchars($member_detail['name']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">이메일</div>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($member_detail['email']); ?>" 
                                       style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">회사명</div>
                                <input type="text" name="company" value="<?php echo htmlspecialchars($member_detail['company'] ?? ''); ?>" 
                                       style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            </div>
                            <div class="detail-item" style="grid-column: 1 / -1;">
                                <div class="detail-label">휴대폰</div>
                                <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                                    <?php
                                    $phone_parts = [];
                                    $phone = $member_detail['phone'] ?? '';
                                    if ($phone) {
                                        $phone_parts = explode('-', $phone);
                                    }
                                    ?>
                                    <select name="phone_prefix" style="width: 80px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                                        <option value="010" <?php echo (!isset($phone_parts[0]) || $phone_parts[0] == '010') ? 'selected' : ''; ?>>010</option>
                                        <option value="011" <?php echo (isset($phone_parts[0]) && $phone_parts[0] == '011') ? 'selected' : ''; ?>>011</option>
                                        <option value="016" <?php echo (isset($phone_parts[0]) && $phone_parts[0] == '016') ? 'selected' : ''; ?>>016</option>
                                        <option value="017" <?php echo (isset($phone_parts[0]) && $phone_parts[0] == '017') ? 'selected' : ''; ?>>017</option>
                                        <option value="018" <?php echo (isset($phone_parts[0]) && $phone_parts[0] == '018') ? 'selected' : ''; ?>>018</option>
                                        <option value="019" <?php echo (isset($phone_parts[0]) && $phone_parts[0] == '019') ? 'selected' : ''; ?>>019</option>
                                    </select>
                                    <span style="padding: 0 4px;">-</span>
                                    <input type="text" name="phone_middle" placeholder="0000" maxlength="4" 
                                           style="width: 70px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;"
                                           value="<?php echo htmlspecialchars($phone_parts[1] ?? ''); ?>">
                                    <span style="padding: 0 4px;">-</span>
                                    <input type="text" name="phone_last" placeholder="0000" maxlength="4" 
                                           style="width: 70px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;"
                                           value="<?php echo htmlspecialchars($phone_parts[2] ?? ''); ?>">
                                </div>
                                <input type="hidden" name="phone" value="">
                            </div>
                            <div class="detail-item" style="grid-column: 1 / -1;">
                                <div class="detail-label">일반전화번호</div>
                                <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                                    <?php
                                    $landline_parts = [];
                                    $landline = $member_detail['landline'] ?? '';
                                    if ($landline) {
                                        $landline_parts = explode('-', $landline);
                                    }
                                    $known_areas = ['02', '031', '032', '033', '041', '042', '043', '044', '050', '051', '052', '053', '054', '055', '061', '062', '063', '064', '070'];
                                    $is_other = isset($landline_parts[0]) && !in_array($landline_parts[0], $known_areas);
                                    ?>
                                    <select name="landline_area" style="width: 100px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
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
                                           style="width: 60px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; display: <?php echo $is_other ? 'inline-block' : 'none'; ?>;" 
                                           value="<?php echo $is_other ? htmlspecialchars($landline_parts[0]) : ''; ?>">
                                    <span style="padding: 0 4px;">-</span>
                                    <input type="text" name="landline_middle" placeholder="0000" maxlength="4" 
                                           style="width: 65px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;"
                                           value="<?php echo htmlspecialchars($landline_parts[1] ?? ''); ?>">
                                    <span style="padding: 0 4px;">-</span>
                                    <input type="text" name="landline_last" placeholder="0000" maxlength="4" 
                                           style="width: 65px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;"
                                           value="<?php echo htmlspecialchars($landline_parts[2] ?? ''); ?>">
                                </div>
                                <input type="hidden" name="landline" value="">
                            </div>
                            <div class="detail-item" style="grid-column: 1 / -1;">
                                <div class="detail-label">주소</div>
                                <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap; margin-bottom: 8px;">
                                    <input type="text" name="zipcode" id="zipcode" placeholder="우편번호" 
                                           value="<?php echo htmlspecialchars($member_detail['zipcode'] ?? ''); ?>" 
                                           style="width: 120px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" readonly>
                                    <button type="button" onclick="findZipcode()" 
                                            style="padding: 8px 16px; background: #666; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px;">
                                        우편번호 찾기
                                    </button>
                                </div>
                                <input type="text" name="address" id="address" placeholder="기본주소" 
                                       value="<?php echo htmlspecialchars($member_detail['address'] ?? ''); ?>" 
                                       style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 8px;" readonly>
                                <input type="text" name="address_detail" placeholder="상세주소" 
                                       value="<?php echo htmlspecialchars($member_detail['address_detail'] ?? ''); ?>" 
                                       style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">가입일</div>
                                <div class="detail-value"><?php echo date('Y-m-d H:i', strtotime($member_detail['created_at'])); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">상태</div>
                                <div class="detail-value">
                                    <label style="display: inline-flex; align-items: center; cursor: pointer;">
                                        <input type="checkbox" name="is_active" value="1" 
                                               <?php echo $member_detail['is_active'] ? 'checked' : ''; ?>
                                               style="margin-right: 8px; width: 18px; height: 18px; cursor: pointer;">
                                        <span id="status-text" style="font-weight: 600; color: <?php echo $member_detail['is_active'] ? '#2E7D32' : '#C62828'; ?>">
                                            <?php echo $member_detail['is_active'] ? '활성' : '비활성'; ?>
                                        </span>
                                    </label>
                                </div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">관리자 여부</div>
                                <div class="detail-value"><?php echo $member_detail['is_admin'] ? '관리자' : '일반회원'; ?></div>
                            </div>
                        </div>
                        <button type="submit" style="margin-top: 16px; padding: 10px 20px; background: #1A237E; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600;">
                            정보 수정
                        </button>
                    </form>
                </div>
                
                <!-- 비밀번호 변경 섹션 -->
                <div class="member-detail" style="margin-top: 24px;">
                    <h3 style="font-size: 18px; margin-bottom: 16px;">비밀번호 변경</h3>
                    <form method="POST" action="admin_members_action.php">
                        <input type="hidden" name="action" value="change_password">
                        <input type="hidden" name="member_id" value="<?php echo $member_detail['id']; ?>">
                        <div class="detail-grid">
                            <div class="detail-item" style="grid-column: 1 / -1;">
                                <div class="detail-label">새 비밀번호</div>
                                <input type="password" name="new_password" required 
                                       style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            </div>
                            <div class="detail-item" style="grid-column: 1 / -1;">
                                <div class="detail-label">새 비밀번호 확인</div>
                                <input type="password" name="new_password_confirm" required 
                                       style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            </div>
                        </div>
                        <button type="submit" 
                                style="margin-top: 16px; padding: 10px 20px; background: #1A237E; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600;">
                            비밀번호 변경
                        </button>
                    </form>
                </div>
                
                <div class="action-links" style="margin-top: 24px;">
                    <a href="?action=toggle_status&id=<?php echo $member_detail['id']; ?>" 
                       class="btn-toggle"
                       onclick="return confirm('상태를 변경하시겠습니까?')">
                        상태 변경
                    </a>
                    <?php if(!$member_detail['is_admin']): ?>
                        <a href="?action=delete&id=<?php echo $member_detail['id']; ?>" 
                           class="btn-delete"
                           onclick="return confirm('정말 삭제하시겠습니까?')">
                            삭제
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <script>
            // 지역번호 선택 변경 시 기타 입력 필드 표시/숨김
            document.querySelector('select[name="landline_area"]')?.addEventListener('change', function() {
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
            document.querySelector('form[action="admin_members_action.php"]')?.addEventListener('submit', function(e) {
                // 휴대폰 번호 조합
                const phonePrefix = document.querySelector('select[name="phone_prefix"]')?.value || '';
                const phoneMiddle = document.querySelector('input[name="phone_middle"]')?.value || '';
                const phoneLast = document.querySelector('input[name="phone_last"]')?.value || '';
                
                if (phonePrefix && phoneMiddle && phoneLast) {
                    document.querySelector('input[name="phone"]').value = phonePrefix + '-' + phoneMiddle + '-' + phoneLast;
                } else {
                    document.querySelector('input[name="phone"]').value = '';
                }
                
                // 일반전화번호 조합
                const areaSelect = document.querySelector('select[name="landline_area"]');
                let area = areaSelect?.value || '';
                
                // 기타 선택 시 직접 입력한 값 사용
                if (area === 'other') {
                    area = document.querySelector('input[name="landline_area_other"]').value;
                }
                
                const middle = document.querySelector('input[name="landline_middle"]')?.value || '';
                const last = document.querySelector('input[name="landline_last"]')?.value || '';
                
                if (area && middle && last) {
                    document.querySelector('input[name="landline"]').value = area + '-' + middle + '-' + last;
                } else {
                    document.querySelector('input[name="landline"]').value = '';
                }
            });

            // 숫자만 입력 가능하도록 제한
            document.querySelectorAll('input[name="phone_middle"], input[name="phone_last"], input[name="landline_middle"], input[name="landline_last"], input[name="landline_area_other"]').forEach(input => {
                input?.addEventListener('input', function(e) {
                    this.value = this.value.replace(/[^0-9]/g, '');
                });
            });
            
            // 상태 체크박스 변경 이벤트
            const statusCheckbox = document.querySelector('input[name="is_active"]');
            const statusText = document.getElementById('status-text');
            
            statusCheckbox?.addEventListener('change', function() {
                if (this.checked) {
                    statusText.textContent = '활성';
                    statusText.style.color = '#2E7D32';
                } else {
                    statusText.textContent = '비활성';
                    statusText.style.color = '#C62828';
                }
            });
            
            function findZipcode() {
                new daum.Postcode({
                    oncomplete: function(data) {
                        document.getElementById('zipcode').value = data.zonecode;
                        document.getElementById('address').value = data.roadAddress;
                        document.querySelector('input[name="address_detail"]').focus();
                    }
                }).open();
            }
            </script>
            
            <script src="//t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js"></script>
            
        <?php else: ?>
            <div class="page-header">
                <h1>회원 관리</h1>
                <p>가입된 회원을 조회하고 관리할 수 있습니다.</p>
            </div>
            
            <form method="GET" action="" class="search-bar">
                <input type="text" name="search" class="search-input" 
                       placeholder="아이디, 이름, 이메일, 회사명으로 검색" 
                       value="<?php echo htmlspecialchars($search); ?>">
                <select name="filter" class="filter-select">
                    <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>전체</option>
                    <option value="active" <?php echo $filter === 'active' ? 'selected' : ''; ?>>활성</option>
                    <option value="inactive" <?php echo $filter === 'inactive' ? 'selected' : ''; ?>>비활성</option>
                </select>
                <button type="submit" class="search-btn">검색</button>
                <?php if($search || $filter !== 'all'): ?>
                    <a href="admin_members.php" class="search-btn" style="background: #666; text-decoration: none; display: inline-flex; align-items: center;">초기화</a>
                <?php endif; ?>
            </form>
            
            <div class="content-box">
                <table class="members-table">
                    <thead>
                        <tr>
                            <th width="60">번호</th>
                            <th width="120">아이디</th>
                            <th width="100">이름</th>
                            <th>이메일</th>
                            <th width="150">회사명</th>
                            <th width="120">가입일</th>
                            <th width="80">상태</th>
                            <th width="150">관리</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($members)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 40px;">
                                    등록된 회원이 없습니다.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($members as $member): ?>
                                <tr>
                                    <td><?php echo $member['id']; ?></td>
                                    <td><?php echo htmlspecialchars($member['user_id']); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($member['name']); ?>
                                        <?php if($member['is_admin']): ?>
                                            <small style="color: #1A237E;">(관리자)</small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($member['email']); ?></td>
                                    <td><?php echo htmlspecialchars($member['company'] ?? '-'); ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($member['created_at'])); ?></td>
                                    <td>
                                        <?php if($member['is_active']): ?>
                                            <span class="status-badge status-active">활성</span>
                                        <?php else: ?>
                                            <span class="status-badge status-inactive">비활성</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-links">
                                            <a href="?action=view&id=<?php echo $member['id']; ?>" class="btn-view">상세</a>
                                            <a href="?action=toggle_status&id=<?php echo $member['id']; ?>" 
                                               class="btn-toggle"
                                               onclick="return confirm('상태를 변경하시겠습니까?')">
                                                상태
                                            </a>
                                            <?php if(!$member['is_admin']): ?>
                                                <a href="?action=delete&id=<?php echo $member['id']; ?>" 
                                                   class="btn-delete"
                                                   onclick="return confirm('정말 삭제하시겠습니까?')">
                                                    삭제
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <?php if($totalPages > 1): ?>
                    <div class="pagination">
                        <?php
                        $queryString = http_build_query(array_merge($_GET, ['page' => '']));
                        for($i = 1; $i <= $totalPages; $i++):
                        ?>
                            <a href="?<?php echo $queryString . $i; ?>" 
                               class="page-link <?php echo $page == $i ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

<?php require_once 'admin_tail.php'; ?>