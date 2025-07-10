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
                }
                ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($error)): ?>
            <div class="msg error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if($action === 'view' && $member_detail): ?>
            <a href="admin_members.php" class="back-btn">← 목록으로</a>
            
            <div class="page-header">
                <h1>회원 상세 정보</h1>
                <p><?php echo htmlspecialchars($member_detail['name']); ?>님의 상세 정보입니다.</p>
            </div>
            
            <div class="content-box">
                <div class="member-detail">
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
                            <div class="detail-value"><?php echo htmlspecialchars($member_detail['email']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">회사명</div>
                            <div class="detail-value"><?php echo htmlspecialchars($member_detail['company'] ?? '-'); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">연락처</div>
                            <div class="detail-value"><?php echo htmlspecialchars($member_detail['phone'] ?? '-'); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">가입일</div>
                            <div class="detail-value"><?php echo date('Y-m-d H:i', strtotime($member_detail['created_at'])); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">상태</div>
                            <div class="detail-value">
                                <?php if($member_detail['is_active']): ?>
                                    <span class="status-badge status-active">활성</span>
                                <?php else: ?>
                                    <span class="status-badge status-inactive">비활성</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">관리자 여부</div>
                            <div class="detail-value"><?php echo $member_detail['is_admin'] ? '관리자' : '일반회원'; ?></div>
                        </div>
                    </div>
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