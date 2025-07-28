<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>철근 계산기 테스트</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .test-section {
            border: 1px solid #ddd;
            padding: 20px;
            margin: 20px 0;
            background: #f5f5f5;
        }
        iframe {
            width: 100%;
            height: 600px;
            border: 1px solid #ccc;
        }
        .data-display {
            background: white;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        pre {
            background: #333;
            color: #fff;
            padding: 10px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>철근 계산기 테스트 페이지</h1>
    
    <div class="test-section">
        <h2>철근 제품 목록</h2>
        <div class="data-display">
            <?php
            require_once 'db.php';
            
            $stmt = $pdo->query("SELECT * FROM products WHERE category_code = 'rebar' AND is_active = 1 ORDER BY product_name");
            $products = $stmt->fetchAll();
            
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>제품명</th><th>가격</th><th>상세페이지</th></tr>";
            foreach ($products as $product) {
                echo "<tr>";
                echo "<td>{$product['id']}</td>";
                echo "<td>{$product['product_name']}</td>";
                echo "<td>" . number_format($product['price']) . "원/kg</td>";
                echo "<td><a href='product_detail.php?id={$product['id']}' target='_blank'>보기</a></td>";
                echo "</tr>";
            }
            echo "</table>";
            ?>
        </div>
    </div>
    
    <div class="test-section">
        <h2>D10 제품 페이지 테스트</h2>
        <p>아래 iframe에서 철근 D10 제품의 계산기를 테스트해보세요:</p>
        <iframe src="product_detail.php?id=1008"></iframe>
    </div>
    
    <div class="test-section">
        <h2>API 테스트</h2>
        <button onclick="testAPI()">API 테스트 실행</button>
        <pre id="apiResult"></pre>
    </div>
    
    <script>
    function testAPI() {
        // D10의 spec_id = 49
        fetch('/api/get_rebar_lengths.php?spec_id=49')
            .then(response => response.json())
            .then(data => {
                document.getElementById('apiResult').textContent = JSON.stringify(data, null, 2);
            })
            .catch(error => {
                document.getElementById('apiResult').textContent = 'Error: ' + error;
            });
    }
    </script>
</body>
</html>