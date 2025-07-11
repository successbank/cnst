<?php
// 단중표 목록 include 파일
$product_type_filter = isset($_GET['product_type']) ? trim($_GET['product_type']) : '';

// 단중표 삭제 처리
if (isset($_GET['delete_weight']) && isset($_GET['weight_id'])) {
    $id = (int)$_GET['weight_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM unit_weights WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: admin_products_integrated.php?tab=weights&message=weight_deleted");
        exit;
    } catch(PDOException $e) {
        $error = "삭제 중 오류가 발생했습니다.";
    }
}

// 조건 설정
$where = ["1=1"];
$params = [];

if ($product_type_filter) {
    $where[] = "product_type = ?";
    $params[] = $product_type_filter;
}

$whereClause = implode(" AND ", $where);

// 전체 개수
$stmt = $pdo->prepare("SELECT COUNT(*) FROM unit_weights WHERE $whereClause");
$stmt->execute($params);
$totalWeightCount = $stmt->fetchColumn();

// 데이터 조회
$stmt = $pdo->prepare("
    SELECT uw.*, 
           COUNT(p.id) as product_count
    FROM unit_weights uw
    LEFT JOIN products p ON p.specifications = uw.specification
    WHERE $whereClause
    GROUP BY uw.id
    ORDER BY uw.product_type, CAST(uw.height AS UNSIGNED), CAST(uw.width AS UNSIGNED) 
    LIMIT 50
");
$stmt->execute($params);
$weights = $stmt->fetchAll();

// 제품 타입 목록
$product_types = ['H형강', 'I형강', 'ㄱ형강', 'ㄷ형강', '환봉', '평철', 'C형강'];
?>

<style>
.filter-bar {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.weights-table {
    width: 100%;
    border-collapse: collapse;
}

.weights-table th {
    background: #f8f9fa;
    padding: 10px;
    text-align: left;
    font-weight: 600;
    border-bottom: 2px solid #dee2e6;
    font-size: 14px;
}

.weights-table td {
    padding: 10px;
    border-bottom: 1px solid #f0f0f0;
    font-size: 14px;
}

.spec-cell {
    font-family: monospace;
    font-weight: 600;
}

.material-badge {
    background: #e3f2fd;
    color: #1976d2;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
}

.product-count {
    background: #d4edda;
    color: #155724;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
}

.btn-sm {
    padding: 4px 10px;
    font-size: 12px;
    border-radius: 4px;
    text-decoration: none;
}

/* 모바일 반응형 */
@media (max-width: 768px) {
    .filter-bar {
        flex-direction: column;
        gap: 10px;
    }
    
    .filter-bar form {
        width: 100%;
    }
    
    .filter-bar select {
        width: 100%;
    }
    
    /* 모바일 테이블 스크롤 */
    .weights-table {
        display: block;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin: 0 -20px;
        width: calc(100% + 40px);
    }
    
    .weights-table tbody {
        display: table;
        width: 100%;
    }
    
    .weights-table th,
    .weights-table td {
        font-size: 12px;
        padding: 8px 5px;
        white-space: nowrap;
    }
    
    /* 모바일에서 일부 컬럼 숨기기 */
    @media (max-width: 576px) {
        .weights-table th:nth-child(4),
        .weights-table td:nth-child(4),
        .weights-table th:nth-child(5),
        .weights-table td:nth-child(5),
        .weights-table th:nth-child(6),
        .weights-table td:nth-child(6),
        .weights-table th:nth-child(7),
        .weights-table td:nth-child(7),
        .weights-table th:nth-child(8),
        .weights-table td:nth-child(8) {
            display: none;
        }
    }
    
    .btn-sm {
        padding: 3px 8px;
        font-size: 11px;
    }
}
</style>

<!-- 필터 -->
<div class="filter-bar">
    <form method="GET" action="" style="display: flex; gap: 10px;">
        <input type="hidden" name="tab" value="weights">
        <select name="product_type" onchange="this.form.submit()">
            <option value="">전체 제품 타입</option>
            <?php foreach ($product_types as $type): ?>
                <option value="<?php echo $type; ?>" <?php echo $product_type_filter == $type ? 'selected' : ''; ?>>
                    <?php echo $type; ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
    <div style="margin-left: auto; color: #666;">
        총 <?php echo number_format($totalWeightCount); ?>개의 단중표
    </div>
</div>

<!-- 단중표 목록 -->
<table class="weights-table">
    <thead>
        <tr>
            <th width="100">제품타입</th>
            <th width="150">규격</th>
            <th width="100">단위중량</th>
            <th width="80">재질</th>
            <th width="60">높이</th>
            <th width="60">너비</th>
            <th width="60">웹</th>
            <th width="80">플랜지</th>
            <th width="100">제품등록</th>
            <th width="100">관리</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($weights as $weight): ?>
        <tr>
            <td><?php echo htmlspecialchars($weight['product_type']); ?></td>
            <td class="spec-cell"><?php echo htmlspecialchars($weight['specification']); ?></td>
            <td style="font-weight: 600; color: #007bff;">
                <?php echo number_format($weight['unit_weight'], 1); ?> kg/m
            </td>
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
                <?php if ($weight['product_count'] > 0): ?>
                    <span class="product-count">등록됨 (<?php echo $weight['product_count']; ?>)</span>
                <?php else: ?>
                    <span style="color: #dc3545;">미등록</span>
                <?php endif; ?>
            </td>
            <td>
                <a href="admin_unit_weights_edit.php?id=<?php echo $weight['id']; ?>" 
                   class="btn-sm" style="background: #ffc107; color: #000;">수정</a>
                <a href="?tab=weights&delete_weight=1&weight_id=<?php echo $weight['id']; ?>" 
                   class="btn-sm" style="background: #dc3545; color: white;"
                   onclick="return confirm('삭제하시겠습니까?')">삭제</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php if ($totalWeightCount > 50): ?>
<div style="text-align: center; margin-top: 20px; color: #666;">
    처음 50개만 표시됩니다. 더 많은 데이터를 보려면 필터를 사용하세요.
</div>
<?php endif; ?>