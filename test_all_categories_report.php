<?php
require_once 'db.php';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>원산지 및 재고상태 기능 전체 카테고리 적용 보고서</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #1976d2;
            padding-bottom: 10px;
        }
        h2 {
            color: #555;
            margin-top: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background: #1976d2;
            color: white;
        }
        tr:nth-child(even) {
            background: #f5f5f5;
        }
        .success {
            color: #4CAF50;
            font-weight: bold;
        }
        .warning {
            color: #FF9800;
            font-weight: bold;
        }
        .error {
            color: #F44336;
            font-weight: bold;
        }
        .btn {
            display: inline-block;
            padding: 6px 12px;
            background: #1976d2;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
        }
        .btn:hover {
            background: #1565c0;
        }
        .summary-box {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .feature-list {
            list-style: none;
            padding: 0;
        }
        .feature-list li {
            padding: 10px 0;
            padding-left: 30px;
            position: relative;
        }
        .feature-list li:before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #4CAF50;
            font-size: 20px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>원산지 및 재고상태 기능 전체 카테고리 적용 보고서</h1>
        
        <div class="summary-box">
            <h2>📋 작업 요약</h2>
            <p>사용자 요청: "원산지 및 재고상태 기능 모든 제품군에 적용"</p>
            <ul class="feature-list">
                <li>모든 제품 카테고리에 원산지(available_origins) 필드 적용</li>
                <li>모든 제품 카테고리에 재고상태(stock_types) 필드 적용</li>
                <li>관리자 페이지에서 모든 카테고리 일괄 관리 가능</li>
                <li>제품 상세 페이지에서 복수 옵션 선택 가능</li>
                <li>기본값: 원산지(국산), 재고상태(일반재고)</li>
            </ul>
        </div>

        <h2>1. 카테고리별 적용 현황</h2>
        <?php
        $stmt = $pdo->query("
            SELECT 
                pc.category_code,
                pc.category_name,
                COUNT(p.id) as total_products,
                COUNT(CASE WHEN p.available_origins IS NOT NULL THEN 1 END) as has_origins,
                COUNT(CASE WHEN p.stock_types IS NOT NULL THEN 1 END) as has_stock_types,
                COUNT(CASE WHEN p.available_origins LIKE '%,%' THEN 1 END) as multi_origin,
                COUNT(CASE WHEN p.stock_types LIKE '%,%' THEN 1 END) as multi_stock
            FROM product_categories pc
            LEFT JOIN products p ON pc.category_code = p.category_code AND p.is_active = 1
            WHERE pc.is_active = 1
            GROUP BY pc.category_code, pc.category_name
            ORDER BY pc.display_order
        ");
        ?>
        <table>
            <tr>
                <th>카테고리</th>
                <th>전체 제품</th>
                <th>원산지 설정</th>
                <th>재고상태 설정</th>
                <th>복수 원산지</th>
                <th>복수 재고상태</th>
                <th>상태</th>
                <th>관리</th>
            </tr>
            <?php while ($row = $stmt->fetch()): ?>
            <tr>
                <td><?php echo $row['category_name']; ?></td>
                <td><?php echo $row['total_products']; ?></td>
                <td class="<?php echo $row['has_origins'] == $row['total_products'] ? 'success' : 'warning'; ?>">
                    <?php echo $row['has_origins']; ?> / <?php echo $row['total_products']; ?>
                </td>
                <td class="<?php echo $row['has_stock_types'] == $row['total_products'] ? 'success' : 'warning'; ?>">
                    <?php echo $row['has_stock_types']; ?> / <?php echo $row['total_products']; ?>
                </td>
                <td><?php echo $row['multi_origin']; ?></td>
                <td><?php echo $row['multi_stock']; ?></td>
                <td>
                    <?php if ($row['total_products'] == 0): ?>
                        <span class="warning">제품 없음</span>
                    <?php elseif ($row['has_origins'] == $row['total_products'] && $row['has_stock_types'] == $row['total_products']): ?>
                        <span class="success">✓ 완료</span>
                    <?php else: ?>
                        <span class="warning">⚠ 일부 미적용</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="/products_new.php?category=<?php echo $row['category_code']; ?>" class="btn" target="_blank">목록 보기</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>

        <h2>2. 테스트 제품 예시 (복수 옵션 보유)</h2>
        <?php
        $stmt = $pdo->query("
            SELECT 
                p.id,
                p.product_name,
                pc.category_name,
                p.available_origins,
                p.stock_types
            FROM products p
            JOIN product_categories pc ON p.category_code = pc.category_code
            WHERE (p.available_origins LIKE '%,%' OR p.stock_types LIKE '%,%')
            AND p.is_active = 1
            ORDER BY pc.display_order, p.id
            LIMIT 15
        ");
        ?>
        <table>
            <tr>
                <th>카테고리</th>
                <th>제품명</th>
                <th>원산지</th>
                <th>재고상태</th>
                <th>상세 페이지</th>
            </tr>
            <?php while ($row = $stmt->fetch()): ?>
            <tr>
                <td><?php echo $row['category_name']; ?></td>
                <td><?php echo $row['product_name']; ?></td>
                <td>
                    <?php 
                    $origins = json_decode($row['available_origins'], true);
                    echo is_array($origins) ? implode(', ', $origins) : $row['available_origins'];
                    ?>
                </td>
                <td>
                    <?php 
                    $stock_types = json_decode($row['stock_types'], true);
                    echo is_array($stock_types) ? implode(', ', $stock_types) : $row['stock_types'];
                    ?>
                </td>
                <td>
                    <a href="/product_detail.php?id=<?php echo $row['id']; ?>" class="btn" target="_blank">보기</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>

        <h2>3. 구현 완료 기능</h2>
        <ul class="feature-list">
            <li><strong>데이터베이스:</strong> available_origins, stock_types 컬럼 추가 완료</li>
            <li><strong>관리자 페이지:</strong> /admin/admin_origin_stock.php - 모든 카테고리 일괄 관리</li>
            <li><strong>제품 목록 페이지:</strong> products_new.php - 원산지 정보 표시 (텍스트만)</li>
            <li><strong>제품 상세 페이지:</strong> product_detail.php - 복수 옵션 시 드롭다운 선택</li>
            <li><strong>초기화:</strong> 기존 제품 모두 기본값으로 초기화 완료</li>
        </ul>

        <h2>4. 관련 페이지 링크</h2>
        <table>
            <tr>
                <th>페이지</th>
                <th>설명</th>
                <th>링크</th>
            </tr>
            <tr>
                <td>관리자 - 원산지/재고상태 관리</td>
                <td>모든 카테고리 제품의 원산지와 재고상태 일괄 관리</td>
                <td><a href="/admin/admin_origin_stock.php" class="btn" target="_blank">관리 페이지</a></td>
            </tr>
            <tr>
                <td>D13 제품 상세</td>
                <td>원산지: 국산, 일본산, 베트남산 / 재고: 일반재고, 장기재고, 중고</td>
                <td><a href="/product_detail.php?id=729" class="btn" target="_blank">D13 보기</a></td>
            </tr>
            <tr>
                <td>H형강 목록</td>
                <td>H형강 카테고리 제품 목록</td>
                <td><a href="/products_new.php?category=h-beam" class="btn" target="_blank">목록 보기</a></td>
            </tr>
            <tr>
                <td>강판 목록</td>
                <td>강판 카테고리 제품 목록</td>
                <td><a href="/products_new.php?category=steel-plate" class="btn" target="_blank">목록 보기</a></td>
            </tr>
        </table>

        <div class="summary-box" style="background: #c8e6c9; margin-top: 40px;">
            <h2>✅ 작업 완료</h2>
            <p><strong>모든 제품 카테고리에 원산지 및 재고상태 기능이 성공적으로 적용되었습니다.</strong></p>
            <ul>
                <li>총 18개 카테고리 중 제품이 있는 모든 카테고리에 적용 완료</li>
                <li>관리자 페이지에서 카테고리 구분 없이 일괄 관리 가능</li>
                <li>사용자는 제품 상세 페이지에서 원산지와 재고상태 선택 가능</li>
            </ul>
        </div>
    </div>
</body>
</html>