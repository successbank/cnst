<?php
// 데이터베이스 연결 설정 가져오기
require_once 'db.php';

// PDO 연결 가져오기
$pdo = getDB();

// 검색 파라미터
$search = isset($_GET['search']) ? $_GET['search'] : '';
$material_filter = isset($_GET['material']) ? $_GET['material'] : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// 총 개수 조회
$count_sql = "SELECT COUNT(*) as total FROM products WHERE category_code = 'h-beam'";
$params = [];
if ($search) {
    $count_sql .= " AND (specification LIKE :search1 OR product_name LIKE :search2)";
    $params['search1'] = '%' . $search . '%';
    $params['search2'] = '%' . $search . '%';
}
if ($material_filter) {
    $count_sql .= " AND available_materials LIKE :material";
    $params['material'] = '%' . $material_filter . '%';
}
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_count / $per_page);

// 제품 조회
$sql = "SELECT * FROM products WHERE category_code = 'h-beam'";
$params2 = [];
if ($search) {
    $sql .= " AND (specification LIKE :search1 OR product_name LIKE :search2)";
    $params2['search1'] = '%' . $search . '%';
    $params2['search2'] = '%' . $search . '%';
}
if ($material_filter) {
    $sql .= " AND available_materials LIKE :material";
    $params2['material'] = '%' . $material_filter . '%';
}
$sql .= " ORDER BY id ASC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params2 as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue('limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue('offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$result = $stmt;

// 모든 재질 목록 가져오기
$materials_sql = "SELECT DISTINCT available_materials FROM products WHERE category_code = 'h-beam'";
$materials_stmt = $pdo->query($materials_sql);
$all_materials = [];
while ($row = $materials_stmt->fetch(PDO::FETCH_ASSOC)) {
    $mats = json_decode($row['available_materials'], true);
    if (is_array($mats)) {
        $all_materials = array_merge($all_materials, $mats);
    }
}
$all_materials = array_unique($all_materials);
sort($all_materials);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>H형강 제품 관리</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
        }
        h1 {
            color: #333;
            margin-bottom: 30px;
            padding-bottom: 10px;
            border-bottom: 3px solid #4CAF50;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        .stat-card h3 {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 10px;
        }
        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
        }
        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        .search-box {
            flex: 1;
            min-width: 300px;
        }
        .search-box input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        select {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            background: white;
            cursor: pointer;
        }
        .btn {
            padding: 12px 24px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #45a049;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background: #f8f9fa;
            padding: 15px 10px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #dee2e6;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        td {
            padding: 12px 10px;
            border-bottom: 1px solid #dee2e6;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .specification {
            font-weight: 600;
            color: #2c3e50;
        }
        .materials {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        .material-tag {
            background: #e3f2fd;
            color: #1976d2;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
        }
        .origin-tag {
            background: #f3e5f5;
            color: #7b1fa2;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
        }
        .weight {
            color: #e74c3c;
            font-weight: 600;
        }
        .price {
            color: #27ae60;
            font-weight: 600;
        }
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }
        .page-link {
            padding: 10px 15px;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            color: #333;
            text-decoration: none;
            transition: all 0.3s;
        }
        .page-link:hover {
            background: #4CAF50;
            color: white;
            border-color: #4CAF50;
        }
        .page-link.active {
            background: #4CAF50;
            color: white;
            border-color: #4CAF50;
        }
        .actions {
            display: flex;
            gap: 10px;
        }
        .btn-sm {
            padding: 5px 10px;
            font-size: 14px;
        }
        .btn-edit {
            background: #ffc107;
            color: #333;
        }
        .btn-view {
            background: #17a2b8;
            color: white;
        }
        .no-data {
            text-align: center;
            padding: 50px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏗️ H형강 제품 관리 시스템</h1>

        <div class="stats">
            <div class="stat-card">
                <h3>총 제품 수</h3>
                <div class="number"><?php echo $total_count; ?>개</div>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <h3>재질 종류</h3>
                <div class="number"><?php echo count($all_materials); ?>종</div>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <h3>현재 페이지</h3>
                <div class="number"><?php echo $page; ?>/<?php echo $total_pages; ?></div>
            </div>
        </div>

        <form method="GET" class="filters">
            <div class="search-box">
                <input type="text" name="search" placeholder="규격 또는 제품명 검색..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <select name="material">
                <option value="">모든 재질</option>
                <?php foreach ($all_materials as $mat): ?>
                    <option value="<?php echo htmlspecialchars($mat); ?>" <?php echo $material_filter == $mat ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($mat); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn">검색</button>
            <a href="?" class="btn btn-secondary" style="text-decoration: none;">초기화</a>
        </form>

        <?php if ($stmt->rowCount() > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th width="60">ID</th>
                        <th width="200">규격</th>
                        <th width="100">단위중량</th>
                        <th>재질</th>
                        <th>원산지</th>
                        <th width="100">기준가격</th>
                        <th width="100">계산방식</th>
                        <th width="100">표준길이</th>
                        <th width="150">작업</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)): ?>
                        <?php
                        $materials = json_decode($row['available_materials'], true) ?: [];
                        $origins = json_decode($row['available_origins'], true) ?: [];
                        $material_prices = json_decode($row['material_price_data'], true) ?: [];
                        $origin_prices = json_decode($row['origin_price_data'], true) ?: [];
                        ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td class="specification"><?php echo htmlspecialchars($row['specification']); ?></td>
                            <td class="weight"><?php echo number_format($row['specification_weight'], 1); ?> kg/m</td>
                            <td>
                                <div class="materials">
                                    <?php foreach ($materials as $mat): ?>
                                        <span class="material-tag"><?php echo htmlspecialchars($mat); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td>
                                <div class="materials">
                                    <?php foreach ($origins as $origin): ?>
                                        <span class="origin-tag"><?php echo htmlspecialchars($origin); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td class="price">₩<?php echo number_format($row['price']); ?></td>
                            <td><?php echo $row['calculation_type']; ?></td>
                            <td><?php echo $row['standard_length']; ?>m</td>
                            <td>
                                <div class="actions">
                                    <a href="/product_detail.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-view" target="_blank">보기</a>
                                    <a href="/admin/admin_products_edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-edit" target="_blank">편집</a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=1&search=<?php echo urlencode($search); ?>&material=<?php echo urlencode($material_filter); ?>" class="page-link">처음</a>
                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&material=<?php echo urlencode($material_filter); ?>" class="page-link">이전</a>
                <?php endif; ?>

                <?php
                $start = max(1, $page - 2);
                $end = min($total_pages, $page + 2);
                for ($i = $start; $i <= $end; $i++):
                ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&material=<?php echo urlencode($material_filter); ?>"
                       class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&material=<?php echo urlencode($material_filter); ?>" class="page-link">다음</a>
                    <a href="?page=<?php echo $total_pages; ?>&search=<?php echo urlencode($search); ?>&material=<?php echo urlencode($material_filter); ?>" class="page-link">마지막</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="no-data">
                <h2>검색 결과가 없습니다.</h2>
                <p>다른 검색어를 시도해보세요.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
<?php
// PDO는 자동으로 연결을 정리합니다
?>