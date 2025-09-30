<?php
require_once 'db.php';

try {
    $pdo = getDB();

    // Get summary by specification
    $summary_query = "
        SELECT
            spec_name,
            COUNT(*) as record_count,
            MIN(length) as min_length,
            MAX(length) as max_length,
            MIN(pieces_per_length) as min_pieces,
            MAX(pieces_per_length) as max_pieces,
            AVG(pieces_per_length) as avg_pieces
        FROM rebar_length_data
        GROUP BY spec_name
        ORDER BY spec_name
    ";

    $stmt = $pdo->query($summary_query);
    $summaries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get detailed data grouped by spec
    $detail_query = "
        SELECT spec_name, length, pieces_per_length, piece_weight
        FROM rebar_length_data
        ORDER BY spec_name, length
    ";

    $stmt = $pdo->query($detail_query);
    $all_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group data by specification
    $grouped_data = [];
    foreach ($all_data as $row) {
        $grouped_data[$row['spec_name']][] = $row;
    }

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>철근 데이터 요약</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Noto Sans KR', sans-serif;
            background: #f5f6fa;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        h1 {
            color: #2c3e50;
            margin-bottom: 30px;
            text-align: center;
            font-size: 2.5rem;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .summary-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .summary-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }

        .spec-title {
            font-size: 1.5rem;
            color: #3498db;
            margin-bottom: 15px;
            font-weight: bold;
        }

        .spec-info {
            display: grid;
            gap: 10px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid #ecf0f1;
        }

        .info-label {
            color: #7f8c8d;
            font-weight: 500;
        }

        .info-value {
            color: #2c3e50;
            font-weight: bold;
        }

        .detail-section {
            margin-top: 40px;
        }

        .spec-detail {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .spec-detail h3 {
            color: #2980b9;
            margin-bottom: 15px;
            font-size: 1.3rem;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            background: #3498db;
            color: white;
            padding: 10px;
            text-align: left;
        }

        .data-table td {
            padding: 8px;
            border-bottom: 1px solid #ecf0f1;
        }

        .data-table tr:hover {
            background: #f8f9fa;
        }

        .navigation {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-bottom: 30px;
        }

        .nav-btn {
            padding: 10px 20px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .nav-btn:hover {
            background: #2980b9;
        }

        .quick-stats {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
        }

        .quick-stats h2 {
            font-size: 2rem;
            margin-bottom: 20px;
        }

        .stats-row {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 20px;
        }

        .stat-item {
            flex: 1;
            min-width: 150px;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .summary-grid {
                grid-template-columns: 1fr;
            }

            h1 {
                font-size: 1.8rem;
            }

            .quick-stats h2 {
                font-size: 1.5rem;
            }

            .stat-number {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏗️ 철근 데이터 요약 대시보드</h1>

        <div class="navigation">
            <a href="rebar_data_view.php" class="nav-btn">📊 전체 데이터 보기</a>
            <a href="export_rebar_data.php" class="nav-btn">📥 Excel 다운로드</a>
            <a href="index.php" class="nav-btn">🏠 메인으로</a>
        </div>

        <div class="quick-stats">
            <h2>전체 통계</h2>
            <div class="stats-row">
                <div class="stat-item">
                    <div class="stat-number"><?php echo count($summaries); ?></div>
                    <div class="stat-label">철근 규격 종류</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo count($all_data); ?></div>
                    <div class="stat-label">전체 데이터 수</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">6-12m</div>
                    <div class="stat-label">길이 범위</div>
                </div>
            </div>
        </div>

        <h2 style="margin-bottom: 20px; color: #2c3e50;">규격별 요약</h2>
        <div class="summary-grid">
            <?php foreach ($summaries as $summary): ?>
                <div class="summary-card">
                    <div class="spec-title"><?php echo htmlspecialchars($summary['spec_name']); ?></div>
                    <div class="spec-info">
                        <div class="info-row">
                            <span class="info-label">데이터 수:</span>
                            <span class="info-value"><?php echo $summary['record_count']; ?>개</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">길이 범위:</span>
                            <span class="info-value"><?php echo $summary['min_length']; ?>m - <?php echo $summary['max_length']; ?>m</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">본수 범위:</span>
                            <span class="info-value"><?php echo $summary['min_pieces']; ?> - <?php echo $summary['max_pieces']; ?>본</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">평균 본수:</span>
                            <span class="info-value"><?php echo number_format($summary['avg_pieces'], 1); ?>본</span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="detail-section">
            <h2 style="margin-bottom: 20px; color: #2c3e50;">상세 데이터</h2>

            <?php foreach ($grouped_data as $spec => $data): ?>
                <div class="spec-detail">
                    <h3><?php echo htmlspecialchars($spec); ?> 상세</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>길이 (m)</th>
                                <th>본수</th>
                                <th>본중 (kg)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $count = 0;
                            foreach ($data as $row):
                                if ($count >= 10) {
                                    echo '<tr><td colspan="3" style="text-align: center; padding: 10px;">... ' . (count($data) - 10) . '개 더 있음 ...</td></tr>';
                                    break;
                                }
                            ?>
                                <tr>
                                    <td><?php echo number_format($row['length'], 1); ?></td>
                                    <td><?php echo $row['pieces_per_length']; ?>본</td>
                                    <td><?php echo $row['piece_weight'] ? number_format($row['piece_weight'], 2) : '-'; ?></td>
                                </tr>
                            <?php
                                $count++;
                            endforeach;
                            ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>