<?php
require_once 'db.php';

// Get filter parameters
$spec_filter = isset($_GET['spec']) ? $_GET['spec'] : '';
$length_filter = isset($_GET['length']) ? $_GET['length'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'spec_name';
$order = isset($_GET['order']) ? $_GET['order'] : 'ASC';

try {
    $pdo = getDB();

    // Build query with filters
    $query = "SELECT * FROM rebar_length_data WHERE 1=1";
    $params = [];

    if ($spec_filter) {
        $query .= " AND spec_name = :spec";
        $params[':spec'] = $spec_filter;
    }

    if ($length_filter) {
        $query .= " AND length = :length";
        $params[':length'] = $length_filter;
    }

    // Add sorting
    $allowed_sorts = ['spec_name', 'length', 'pieces_per_length', 'piece_weight', 'created_at'];
    if (in_array($sort, $allowed_sorts)) {
        $query .= " ORDER BY $sort $order";
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get unique specs for filter dropdown
    $spec_stmt = $pdo->query("SELECT DISTINCT spec_name FROM rebar_length_data ORDER BY spec_name");
    $specs = $spec_stmt->fetchAll(PDO::FETCH_COLUMN);

    // Get unique lengths for filter dropdown
    $length_stmt = $pdo->query("SELECT DISTINCT length FROM rebar_length_data ORDER BY length");
    $lengths = $length_stmt->fetchAll(PDO::FETCH_COLUMN);

    // Get summary statistics
    $stats_stmt = $pdo->query("
        SELECT
            COUNT(*) as total_records,
            COUNT(DISTINCT spec_name) as total_specs,
            MIN(length) as min_length,
            MAX(length) as max_length
        FROM rebar_length_data
    ");
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Toggle sort order
$new_order = ($order == 'ASC') ? 'DESC' : 'ASC';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>철근 데이터 조회 - 전체 데이터</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Noto Sans KR', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .header p {
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            padding: 30px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-align: center;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #4f46e5;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .filters {
            padding: 30px;
            background: #fff;
            border-bottom: 2px solid #e9ecef;
        }

        .filter-group {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filter-item label {
            font-size: 0.9rem;
            color: #6c757d;
            font-weight: 500;
        }

        .filter-item select,
        .filter-item button {
            padding: 8px 15px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            font-size: 1rem;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
        }

        .filter-item select:hover,
        .filter-item button:hover {
            border-color: #4f46e5;
        }

        .filter-item select:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .btn-primary {
            background: #4f46e5;
            color: white;
            border: none;
        }

        .btn-primary:hover {
            background: #4338ca;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            border: none;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .table-container {
            padding: 30px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f8f9fa;
        }

        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
            position: sticky;
            top: 0;
            background: #f8f9fa;
            cursor: pointer;
            user-select: none;
            white-space: nowrap;
        }

        th:hover {
            background: #e9ecef;
        }

        th.sortable::after {
            content: ' ↕';
            color: #adb5bd;
            font-size: 0.9rem;
        }

        th.sorted-asc::after {
            content: ' ↑';
            color: #4f46e5;
        }

        th.sorted-desc::after {
            content: ' ↓';
            color: #4f46e5;
        }

        td {
            padding: 12px 15px;
            border-bottom: 1px solid #e9ecef;
            color: #495057;
        }

        tbody tr {
            transition: background 0.2s;
        }

        tbody tr:hover {
            background: #f8f9fa;
        }

        .spec-badge {
            display: inline-block;
            padding: 4px 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .length-value {
            font-weight: 600;
            color: #2d3748;
        }

        .pieces-value {
            background: #edf2f7;
            padding: 4px 8px;
            border-radius: 5px;
            font-weight: 600;
            color: #4a5568;
        }

        .no-data {
            text-align: center;
            padding: 60px;
            color: #6c757d;
        }

        .no-data h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        .footer {
            padding: 20px;
            text-align: center;
            background: #f8f9fa;
            color: #6c757d;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 1.5rem;
            }

            .stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .filter-group {
                flex-direction: column;
                align-items: stretch;
            }

            .table-container {
                padding: 15px;
            }

            th, td {
                padding: 8px;
                font-size: 0.9rem;
            }
        }

        .export-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #28a745;
            color: white;
            padding: 15px 25px;
            border-radius: 50px;
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
            transition: all 0.3s;
            font-weight: 600;
        }

        .export-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 철근 데이터 전체 조회</h1>
            <p>데이터베이스에 저장된 모든 철근 길이별 데이터</p>
        </div>

        <div class="stats">
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($stats['total_records']); ?></div>
                <div class="stat-label">전체 레코드</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total_specs']; ?>종</div>
                <div class="stat-label">철근 규격</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['min_length']; ?>m</div>
                <div class="stat-label">최소 길이</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['max_length']; ?>m</div>
                <div class="stat-label">최대 길이</div>
            </div>
        </div>

        <div class="filters">
            <form method="GET" action="">
                <div class="filter-group">
                    <div class="filter-item">
                        <label>철근 규격</label>
                        <select name="spec" onchange="this.form.submit()">
                            <option value="">전체</option>
                            <?php foreach ($specs as $spec): ?>
                                <option value="<?php echo $spec; ?>" <?php echo ($spec_filter == $spec) ? 'selected' : ''; ?>>
                                    <?php echo $spec; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-item">
                        <label>길이 (m)</label>
                        <select name="length" onchange="this.form.submit()">
                            <option value="">전체</option>
                            <?php foreach ($lengths as $length): ?>
                                <option value="<?php echo $length; ?>" <?php echo ($length_filter == $length) ? 'selected' : ''; ?>>
                                    <?php echo number_format($length, 1); ?>m
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-item">
                        <label>&nbsp;</label>
                        <button type="button" onclick="window.location.href='?'" class="btn-secondary">필터 초기화</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-container">
            <?php if (count($data) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th class="sortable <?php echo ($sort == 'spec_name') ? 'sorted-' . strtolower($order) : ''; ?>">
                                <a href="?sort=spec_name&order=<?php echo $new_order; ?>&spec=<?php echo $spec_filter; ?>&length=<?php echo $length_filter; ?>" style="color: inherit; text-decoration: none;">
                                    규격
                                </a>
                            </th>
                            <th class="sortable <?php echo ($sort == 'length') ? 'sorted-' . strtolower($order) : ''; ?>">
                                <a href="?sort=length&order=<?php echo $new_order; ?>&spec=<?php echo $spec_filter; ?>&length=<?php echo $length_filter; ?>" style="color: inherit; text-decoration: none;">
                                    길이 (m)
                                </a>
                            </th>
                            <th class="sortable <?php echo ($sort == 'piece_weight') ? 'sorted-' . strtolower($order) : ''; ?>">
                                <a href="?sort=piece_weight&order=<?php echo $new_order; ?>&spec=<?php echo $spec_filter; ?>&length=<?php echo $length_filter; ?>" style="color: inherit; text-decoration: none;">
                                    본중 (kg)
                                </a>
                            </th>
                            <th class="sortable <?php echo ($sort == 'pieces_per_length') ? 'sorted-' . strtolower($order) : ''; ?>">
                                <a href="?sort=pieces_per_length&order=<?php echo $new_order; ?>&spec=<?php echo $spec_filter; ?>&length=<?php echo $length_filter; ?>" style="color: inherit; text-decoration: none;">
                                    본수
                                </a>
                            </th>
                            <th>톤당 중량</th>
                            <th>단위 중량</th>
                            <th class="sortable <?php echo ($sort == 'created_at') ? 'sorted-' . strtolower($order) : ''; ?>">
                                <a href="?sort=created_at&order=<?php echo $new_order; ?>&spec=<?php echo $spec_filter; ?>&length=<?php echo $length_filter; ?>" style="color: inherit; text-decoration: none;">
                                    등록일
                                </a>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data as $row): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><span class="spec-badge"><?php echo htmlspecialchars($row['spec_name']); ?></span></td>
                                <td class="length-value"><?php echo number_format($row['length'], 1); ?></td>
                                <td><?php echo $row['piece_weight'] ? number_format($row['piece_weight'], 2) : '-'; ?></td>
                                <td><span class="pieces-value"><?php echo $row['pieces_per_length'] ?? '-'; ?>본</span></td>
                                <td><?php echo $row['weight_per_ton'] ? number_format($row['weight_per_ton'], 2) : '-'; ?></td>
                                <td><?php echo $row['unit_weight'] ? number_format($row['unit_weight'], 4) : '-'; ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($row['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <h3>데이터가 없습니다</h3>
                    <p>선택한 필터 조건에 맞는 데이터가 없습니다.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="footer">
            <p>총 <?php echo number_format(count($data)); ?>개의 레코드가 표시되고 있습니다.</p>
            <p>데이터베이스: project1_db | 테이블: rebar_length_data</p>
        </div>
    </div>

    <a href="export_rebar_data.php?spec=<?php echo $spec_filter; ?>&length=<?php echo $length_filter; ?>" class="export-btn">
        📥 Excel 내보내기
    </a>
</body>
</html>