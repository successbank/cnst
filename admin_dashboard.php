<?php
session_start();
require_once 'db.php';

// 통계 데이터 조회
$stats = [];

// 전체 제품 수
$stmt = $pdo->query("SELECT COUNT(*) as total FROM products");
$stats['total_products'] = $stmt->fetchColumn();

// 계산기 활성화 제품 수
$stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE has_calculator = 1");
$stats['calculator_enabled'] = $stmt->fetchColumn();

// 단위중량 미설정 제품 수
$stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE (unit_weight_data IS NULL OR unit_weight_data = '{}') AND has_calculator = 1");
$stats['missing_weight'] = $stmt->fetchColumn();

// 카테고리별 제품 수
$stmt = $pdo->query("
    SELECT pc.category_name, p.category_code, COUNT(p.id) as count,
           SUM(CASE WHEN p.has_calculator = 1 THEN 1 ELSE 0 END) as calculator_count
    FROM products p
    LEFT JOIN product_categories pc ON p.category_code = pc.category_code
    GROUP BY p.category_code, pc.category_name
    ORDER BY count DESC
");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = '제품 관리 대시보드';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Noto Sans KR', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        h1 {
            color: white;
            font-size: 36px;
            margin-bottom: 10px;
            text-align: center;
        }

        .subtitle {
            color: rgba(255,255,255,0.8);
            text-align: center;
            margin-bottom: 40px;
            font-size: 18px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }

        .stat-icon.blue {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .stat-icon.green {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
        }

        .stat-icon.orange {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }

        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #7f8c8d;
            font-size: 14px;
        }

        .categories-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .category-list {
            display: grid;
            gap: 15px;
        }

        .category-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            transition: all 0.3s;
        }

        .category-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }

        .category-name {
            font-weight: 600;
            color: #495057;
            font-size: 16px;
        }

        .category-stats {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .category-stat {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .category-stat-value {
            font-weight: bold;
            color: #2c3e50;
            font-size: 18px;
        }

        .category-stat-label {
            font-size: 12px;
            color: #7f8c8d;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 15px 30px;
            background: white;
            color: #495057;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }

        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        }

        .action-btn.primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
            background: #e3f2fd;
            color: #1976d2;
        }

        .badge.success {
            background: #e8f5e9;
            color: #388e3c;
        }

        .badge.warning {
            background: #fff3e0;
            color: #f57c00;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .category-stats {
                flex-direction: column;
                gap: 10px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .action-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 제품 관리 대시보드</h1>
        <p class="subtitle">제품 데이터 현황 및 관리 시스템</p>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">📦</div>
                <div class="stat-number"><?php echo number_format($stats['total_products']); ?></div>
                <div class="stat-label">전체 제품 수</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green">⚙️</div>
                <div class="stat-number"><?php echo number_format($stats['calculator_enabled']); ?></div>
                <div class="stat-label">계산기 활성화</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon orange">⚠️</div>
                <div class="stat-number"><?php echo number_format($stats['missing_weight']); ?></div>
                <div class="stat-label">단위중량 미설정</div>
            </div>
        </div>

        <div class="categories-section">
            <h2 class="section-title">📂 카테고리별 제품 현황</h2>
            <div class="category-list">
                <?php foreach ($categories as $category): ?>
                <div class="category-item">
                    <div>
                        <div class="category-name">
                            <?php echo htmlspecialchars($category['category_name'] ?: $category['category_code']); ?>
                        </div>
                        <div style="margin-top: 5px;">
                            <span class="badge"><?php echo $category['category_code']; ?></span>
                            <?php if ($category['calculator_count'] == $category['count']): ?>
                                <span class="badge success">계산기 완료</span>
                            <?php elseif ($category['calculator_count'] > 0): ?>
                                <span class="badge warning">일부 활성</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="category-stats">
                        <div class="category-stat">
                            <div class="category-stat-value"><?php echo $category['count']; ?></div>
                            <div class="category-stat-label">제품</div>
                        </div>
                        <div class="category-stat">
                            <div class="category-stat-value"><?php echo $category['calculator_count']; ?></div>
                            <div class="category-stat-label">계산기</div>
                        </div>
                        <a href="admin_products.php?category=<?php echo urlencode($category['category_code']); ?>"
                           class="action-btn" style="padding: 10px 20px; font-size: 14px;">
                            관리 →
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="action-buttons">
            <a href="admin_products.php" class="action-btn primary">
                <span>🛠️</span>
                <span>제품 관리</span>
            </a>
            <a href="admin_products.php?category=unequal-angle" class="action-btn">
                <span>📐</span>
                <span>부등변ㄱ형강 관리</span>
            </a>
            <a href="products.php" class="action-btn">
                <span>🏪</span>
                <span>제품 페이지</span>
            </a>
            <a href="index.php" class="action-btn">
                <span>🏠</span>
                <span>홈페이지</span>
            </a>
        </div>
    </div>
</body>
</html>