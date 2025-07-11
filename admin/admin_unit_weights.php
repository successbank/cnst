<?php
session_start();
require_once '../db.php';
require_once 'admin_check.php';

// 페이지네이션 설정
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 30;
$offset = ($page - 1) * $perPage;

// 필터
$product_type = isset($_GET['product_type']) ? trim($_GET['product_type']) : '';

// 삭제 처리
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM unit_weights WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: admin_unit_weights.php?message=deleted");
        exit;
    } catch(PDOException $e) {
        $error = "삭제 중 오류가 발생했습니다.";
    }
}

// 제품 자동생성 처리
if (isset($_POST['auto_generate'])) {
    $product_type = $_POST['product_type'] ?? 'H형강';
    
    try {
        // 해당 제품 타입의 단중표 데이터 가져오기
        $stmt = $pdo->prepare("SELECT * FROM unit_weights WHERE product_type = ? AND is_active = 1");
        $stmt->execute([$product_type]);
        $unit_weights = $stmt->fetchAll();
        
        $count = 0;
        foreach ($unit_weights as $weight) {
            // 제품이 이미 존재하는지 확인
            $stmt = $pdo->prepare("SELECT id FROM products WHERE product_name = ? AND specifications = ?");
            $product_name = $product_type . ' ' . $weight['specification'];
            $stmt->execute([$product_name, $weight['specification']]);
            
            if (!$stmt->fetch()) {
                // 제품 생성
                $stmt = $pdo->prepare("
                    INSERT INTO products (
                        category_code, product_name, specifications, 
                        description, unit, is_active
                    ) VALUES (?, ?, ?, ?, ?, 1)
                ");
                
                $category_code = 'h-beam'; // H형강 카테고리
                $description = $product_type . ' 규격: ' . $weight['specification'] . ', 단위중량: ' . $weight['unit_weight'] . 'kg/m';
                
                $stmt->execute([
                    $category_code, 
                    $product_name, 
                    $weight['specification'],
                    $description,
                    'TON'
                ]);
                $count++;
            }
        }
        
        header("Location: admin_unit_weights.php?message=generated&count=$count");
        exit;
    } catch(PDOException $e) {
        $error = "제품 생성 중 오류가 발생했습니다: " . $e->getMessage();
    }
}

// 제품 타입 목록
$product_types = ['H형강', 'I형강', 'ㄱ형강', 'ㄷ형강'];

// 조건 설정
$where = ["1=1"];
$params = [];

if ($product_type) {
    $where[] = "product_type = ?";
    $params[] = $product_type;
}

$whereClause = implode(" AND ", $where);

// 전체 개수
$stmt = $pdo->prepare("SELECT COUNT(*) FROM unit_weights WHERE $whereClause");
$stmt->execute($params);
$totalCount = $stmt->fetchColumn();

// 데이터 조회
$stmt = $pdo->prepare("
    SELECT * FROM unit_weights 
    WHERE $whereClause 
    ORDER BY product_type, CAST(height AS UNSIGNED), CAST(width AS UNSIGNED) 
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$weights = $stmt->fetchAll();

$totalPages = ceil($totalCount / $perPage);

include 'admin_head.php';
?>

<style>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.page-header h2 {
    font-size: 28px;
    font-weight: 700;
    color: #333;
}

.header-actions {
    display: flex;
    gap: 10px;
}

.btn {
    padding: 10px 20px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-primary:hover {
    background: #0056b3;
}

.btn-success {
    background: #28a745;
    color: white;
}

.btn-success:hover {
    background: #218838;
}

.filter-section {
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.filter-section form {
    display: flex;
    gap: 15px;
    align-items: center;
}

.filter-select {
    padding: 8px 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.data-table {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.data-table table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th {
    background: #f8f9fa;
    padding: 15px;
    text-align: left;
    font-weight: 600;
    font-size: 14px;
    border-bottom: 2px solid #dee2e6;
}

.data-table td {
    padding: 15px;
    border-bottom: 1px solid #f0f0f0;
    font-size: 14px;
}

.data-table tr:hover {
    background: #f8f9fa;
}

.spec-cell {
    font-family: monospace;
    font-weight: 600;
    color: #333;
}

.weight-cell {
    font-weight: 600;
    color: #007bff;
}

.material-badge {
    display: inline-block;
    padding: 4px 10px;
    background: #e3f2fd;
    color: #1976d2;
    border-radius: 20px;
    font-size: 12px;
}

.action-btns {
    display: flex;
    gap: 5px;
}

.btn-sm {
    padding: 5px 10px;
    font-size: 12px;
}

.btn-edit {
    background: #ffc107;
    color: #000;
}

.btn-delete {
    background: #dc3545;
    color: white;
}

.alert {
    padding: 12px 20px;
    border-radius: 6px;
    margin-bottom: 20px;
}

.alert.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.auto-generate-form {
    background: #e8f5e9;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    border: 1px solid #a5d6a7;
}

.auto-generate-form h3 {
    margin: 0 0 15px 0;
    color: #2e7d32;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 5px;
    margin-top: 30px;
}

.pagination a,
.pagination span {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    text-decoration: none;
    color: #333;
}

.pagination a:hover {
    background: #f8f9fa;
}

.pagination .current {
    background: #007bff;
    color: white;
    border-color: #007bff;
}
</style>

<div class="page-header">
    <h2>단중표 관리</h2>
    <div class="header-actions">
        <a href="admin_unit_weights_edit.php" class="btn btn-primary">단중표 추가</a>
    </div>
</div>

<?php if (isset($_GET['message'])): ?>
    <?php if ($_GET['message'] == 'deleted'): ?>
        <div class="alert success">단중표 데이터가 삭제되었습니다.</div>
    <?php elseif ($_GET['message'] == 'saved'): ?>
        <div class="alert success">단중표 데이터가 저장되었습니다.</div>
    <?php elseif ($_GET['message'] == 'generated'): ?>
        <div class="alert success">
            <?php echo isset($_GET['count']) ? $_GET['count'] : '0'; ?>개의 제품이 자동 생성되었습니다.
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="alert error"><?php echo $error; ?></div>
<?php endif; ?>

<!-- 제품 자동생성 -->
<div class="auto-generate-form">
    <h3>단중표 기반 제품 자동생성</h3>
    <form method="POST" style="display: flex; gap: 10px; align-items: center;">
        <select name="product_type" class="filter-select">
            <?php foreach ($product_types as $type): ?>
                <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" name="auto_generate" class="btn btn-success">
            선택한 제품 타입 자동생성
        </button>
        <span style="font-size: 14px; color: #666;">
            단중표에 등록된 규격을 기준으로 제품을 자동 생성합니다.
        </span>
    </form>
</div>

<!-- 필터 -->
<div class="filter-section">
    <form method="GET" action="">
        <select name="product_type" class="filter-select" onchange="this.form.submit()">
            <option value="">전체 제품 타입</option>
            <?php foreach ($product_types as $type): ?>
                <option value="<?php echo $type; ?>" <?php echo $product_type == $type ? 'selected' : ''; ?>>
                    <?php echo $type; ?>
                </option>
            <?php endforeach; ?>
        </select>
        <span style="color: #666; font-size: 14px;">
            총 <?php echo number_format($totalCount); ?>개의 단중표 데이터
        </span>
    </form>
</div>

<!-- 데이터 테이블 -->
<div class="data-table">
    <table>
        <thead>
            <tr>
                <th width="100">제품타입</th>
                <th width="150">규격</th>
                <th width="120">단위중량(kg/m)</th>
                <th width="100">재질</th>
                <th width="80">높이</th>
                <th width="80">너비</th>
                <th width="80">웹두께</th>
                <th width="100">플랜지두께</th>
                <th width="120">관리</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($weights as $weight): ?>
            <tr>
                <td><?php echo htmlspecialchars($weight['product_type']); ?></td>
                <td class="spec-cell"><?php echo htmlspecialchars($weight['specification']); ?></td>
                <td class="weight-cell"><?php echo number_format($weight['unit_weight'], 1); ?></td>
                <td>
                    <?php if ($weight['material']): ?>
                        <span class="material-badge"><?php echo htmlspecialchars($weight['material']); ?></span>
                    <?php endif; ?>
                </td>
                <td><?php echo $weight['height']; ?></td>
                <td><?php echo $weight['width']; ?></td>
                <td><?php echo $weight['web_thickness']; ?></td>
                <td><?php echo $weight['flange_thickness']; ?></td>
                <td>
                    <div class="action-btns">
                        <a href="admin_unit_weights_edit.php?id=<?php echo $weight['id']; ?>" 
                           class="btn btn-sm btn-edit">수정</a>
                        <a href="?delete=1&id=<?php echo $weight['id']; ?>" 
                           class="btn btn-sm btn-delete"
                           onclick="return confirm('삭제하시겠습니까?')">삭제</a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPages > 1): ?>
<div class="pagination">
    <?php if ($page > 1): ?>
        <a href="?page=1<?php echo $product_type ? '&product_type=' . urlencode($product_type) : ''; ?>">처음</a>
        <a href="?page=<?php echo $page - 1; ?><?php echo $product_type ? '&product_type=' . urlencode($product_type) : ''; ?>">이전</a>
    <?php endif; ?>
    
    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
        <?php if ($i == $page): ?>
            <span class="current"><?php echo $i; ?></span>
        <?php else: ?>
            <a href="?page=<?php echo $i; ?><?php echo $product_type ? '&product_type=' . urlencode($product_type) : ''; ?>"><?php echo $i; ?></a>
        <?php endif; ?>
    <?php endfor; ?>
    
    <?php if ($page < $totalPages): ?>
        <a href="?page=<?php echo $page + 1; ?><?php echo $product_type ? '&product_type=' . urlencode($product_type) : ''; ?>">다음</a>
        <a href="?page=<?php echo $totalPages; ?><?php echo $product_type ? '&product_type=' . urlencode($product_type) : ''; ?>">마지막</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php include 'admin_tail.php'; ?>