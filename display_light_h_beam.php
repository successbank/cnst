<?php
require_once 'db.php';

// URL로 바로 접근 테스트
$currentPage = 'products';

// 뷰 모드 설정
$view_mode = $_GET['view'] ?? 'tile';

// 검색어 처리
$search = $_GET['search'] ?? '';
$search_clean = trim($search);

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>경량H형강 - 충남스틸</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            padding: 30px;
        }

        h1 {
            color: #1428A0;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #1428A0;
        }

        .view-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .search-box {
            display: flex;
            gap: 10px;
            flex: 1;
            max-width: 400px;
        }

        .search-box input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .search-box button {
            padding: 10px 20px;
            background: #1428A0;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
        }

        .view-buttons {
            display: flex;
            gap: 10px;
        }

        .view-btn {
            padding: 8px 16px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .view-btn.active {
            background: #1428A0;
            color: white;
            border-color: #1428A0;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .product-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            transition: all 0.3s;
            cursor: pointer;
        }

        .product-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-color: #1428A0;
        }

        .product-icon {
            font-size: 48px;
            text-align: center;
            margin-bottom: 15px;
        }

        .product-name {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }

        .product-spec {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
        }

        .product-weight {
            font-size: 14px;
            color: #1428A0;
            font-weight: 600;
        }

        .products-list {
            margin-top: 20px;
        }

        .list-header {
            display: grid;
            grid-template-columns: 60px 200px 1fr 150px 100px;
            gap: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .list-item {
            display: grid;
            grid-template-columns: 60px 200px 1fr 150px 100px;
            gap: 15px;
            padding: 15px;
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            margin-bottom: 8px;
            align-items: center;
            transition: all 0.3s;
        }

        .list-item:hover {
            background: #f8f9fa;
            border-color: #1428A0;
        }

        .calculator-section {
            margin-top: 40px;
            padding: 30px;
            background: #f0f4ff;
            border-radius: 8px;
        }

        .calculator-title {
            font-size: 24px;
            color: #1428A0;
            margin-bottom: 20px;
        }

        .calc-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }

        .form-group select,
        .form-group input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .calc-btn {
            grid-column: span 2;
            padding: 12px;
            background: #1428A0;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }

        .calc-btn:hover {
            background: #0F1F7A;
        }

        .result-box {
            margin-top: 20px;
            padding: 20px;
            background: white;
            border-radius: 4px;
            display: none;
        }

        .result-box.show {
            display: block;
        }

        .result-value {
            font-size: 32px;
            color: #1428A0;
            font-weight: 700;
            margin-top: 10px;
        }

        .no-results {
            text-align: center;
            padding: 40px;
            color: #666;
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏢 경량H형강 (Light H-Beam)</h1>

        <!-- 검색 및 뷰 컨트롤 -->
        <div class="view-controls">
            <form class="search-box" method="get">
                <input type="hidden" name="category" value="light-h-beam">
                <input type="text" name="search" placeholder="규격으로 검색 (예: LHB 100*100)" value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit">검색</button>
            </form>
            <div class="view-buttons">
                <button class="view-btn <?php echo $view_mode === 'tile' ? 'active' : ''; ?>" onclick="changeView('tile')">타일뷰</button>
                <button class="view-btn <?php echo $view_mode === 'list' ? 'active' : ''; ?>" onclick="changeView('list')">리스트뷰</button>
            </div>
        </div>

        <?php
        try {
            $pdo = getDB();

            // 경량H형강 데이터 조회
            $sql = "SELECT * FROM products_light_h_beam WHERE 1=1";

            if (!empty($search_clean)) {
                $sql .= " AND specification LIKE :search";
            }

            $sql .= " ORDER BY unit_weight ASC";

            $stmt = $pdo->prepare($sql);

            if (!empty($search_clean)) {
                $stmt->bindValue(':search', '%' . $search_clean . '%');
            }

            $stmt->execute();
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($products) > 0):
                if ($view_mode === 'tile'):
        ?>

        <!-- 타일 뷰 -->
        <div class="products-grid">
            <?php foreach ($products as $product): ?>
            <div class="product-card" onclick="selectProduct('<?php echo $product['specification']; ?>')">
                <div class="product-icon">🏢</div>
                <div class="product-name"><?php echo htmlspecialchars($product['product_name']); ?></div>
                <div class="product-spec">규격: <?php echo htmlspecialchars($product['specification']); ?></div>
                <div class="product-weight">단중: <?php echo number_format($product['unit_weight'], 1); ?> kg/m</div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php else: ?>

        <!-- 리스트 뷰 -->
        <div class="products-list">
            <div class="list-header">
                <div>아이콘</div>
                <div>제품명</div>
                <div>규격</div>
                <div>단중 (kg/m)</div>
                <div>선택</div>
            </div>
            <?php foreach ($products as $product): ?>
            <div class="list-item">
                <div style="font-size: 24px;">🏢</div>
                <div><?php echo htmlspecialchars($product['product_name']); ?></div>
                <div><?php echo htmlspecialchars($product['specification']); ?></div>
                <div style="font-weight: 600; color: #1428A0;"><?php echo number_format($product['unit_weight'], 1); ?></div>
                <div>
                    <button style="padding: 6px 12px; background: #1428A0; color: white; border: none; border-radius: 4px; cursor: pointer;"
                            onclick="selectProduct('<?php echo $product['specification']; ?>')">선택</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php
                endif;
            else:
        ?>
        <div class="no-results">
            <?php if (!empty($search_clean)): ?>
                "<?php echo htmlspecialchars($search); ?>"에 대한 검색 결과가 없습니다.
            <?php else: ?>
                경량H형강 제품 데이터가 없습니다.
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- 중량 계산기 -->
        <div class="calculator-section">
            <h2 class="calculator-title">📊 경량H형강 중량 계산기</h2>
            <form class="calc-form" onsubmit="calculateWeight(event)">
                <div class="form-group">
                    <label>규격 선택</label>
                    <select id="calc-spec" required>
                        <option value="">규격을 선택하세요</option>
                        <?php foreach ($products as $product): ?>
                        <option value="<?php echo $product['unit_weight']; ?>">
                            <?php echo htmlspecialchars($product['specification']); ?> (<?php echo $product['unit_weight']; ?> kg/m)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>길이 (m)</label>
                    <input type="number" id="calc-length" step="0.01" min="0.01" required placeholder="예: 6">
                </div>
                <button type="submit" class="calc-btn">중량 계산하기</button>
            </form>

            <div id="result" class="result-box">
                <h3>계산 결과</h3>
                <div class="result-value" id="result-value">0 kg</div>
                <div style="margin-top: 15px; color: #666;">
                    <div id="calc-formula"></div>
                </div>
            </div>
        </div>

        <?php
        } catch (PDOException $e) {
            echo '<div class="no-results">데이터베이스 연결 오류: ' . $e->getMessage() . '</div>';
        }
        ?>
    </div>

    <script>
    function changeView(mode) {
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('view', mode);
        urlParams.set('category', 'light-h-beam');
        window.location.search = urlParams.toString();
    }

    function selectProduct(spec) {
        alert('선택한 규격: ' + spec + '\n\n견적문의는 전화 또는 이메일로 문의해주세요.');
    }

    function calculateWeight(event) {
        event.preventDefault();

        const unitWeight = parseFloat(document.getElementById('calc-spec').value);
        const length = parseFloat(document.getElementById('calc-length').value);

        if (unitWeight && length) {
            const totalWeight = unitWeight * length;

            document.getElementById('result-value').textContent = totalWeight.toFixed(2) + ' kg';
            document.getElementById('calc-formula').innerHTML =
                `계산식: ${unitWeight} kg/m × ${length} m = ${totalWeight.toFixed(2)} kg`;

            document.getElementById('result').classList.add('show');
        }
    }
    </script>
</body>
</html>