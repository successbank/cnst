<?php
session_start();
require_once 'admin_check.php';
require_once '../db.php';

$pageTitle = '트리뷰 진단';
require_once 'admin_head.php';
?>

<div class="admin-content">
    <div class="page-header">
        <h1>카테고리 트리 진단</h1>
        <p>트리뷰 문제를 진단합니다.</p>
    </div>

    <style>
        .test-section {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .test-result {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .warning { background: #fff3cd; color: #856404; }
        .info { background: #d1ecf1; color: #0c5460; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 4px; overflow: auto; }
    </style>

    <!-- 1. 데이터베이스 체크 -->
    <div class="test-section">
        <h2>1. 데이터베이스 체크</h2>
        <?php
        $columns = $pdo->query("SHOW COLUMNS FROM product_categories")->fetchAll();
        $requiredColumns = ['parent_id', 'level', 'path'];
        $missingColumns = [];

        foreach ($requiredColumns as $required) {
            $found = false;
            foreach ($columns as $col) {
                if ($col['Field'] == $required) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $missingColumns[] = $required;
            }
        }

        if (empty($missingColumns)) {
            echo '<div class="test-result success">✅ 모든 필수 컬럼이 존재합니다.</div>';
        } else {
            echo '<div class="test-result error">❌ 누락된 컬럼: ' . implode(', ', $missingColumns) . '</div>';
            echo '<div class="test-result warning">⚠️ <a href="run_migration.php">마이그레이션 실행</a>이 필요합니다.</div>';
        }
        ?>
    </div>

    <!-- 2. AJAX 엔드포인트 체크 -->
    <div class="test-section">
        <h2>2. AJAX 엔드포인트 체크</h2>
        <button onclick="testAjax()" class="btn btn-primary">AJAX 테스트</button>
        <div id="ajaxResult"></div>
    </div>

    <!-- 3. 카테고리 데이터 -->
    <div class="test-section">
        <h2>3. 카테고리 데이터</h2>
        <?php
        $count = $pdo->query("SELECT COUNT(*) FROM product_categories")->fetchColumn();
        echo '<div class="test-result info">총 카테고리 수: ' . $count . '개</div>';

        $stmt = $pdo->query("
            SELECT pc.*, COUNT(p.id) as product_count
            FROM product_categories pc
            LEFT JOIN products p ON pc.category_code = p.category_code
            GROUP BY pc.id
            ORDER BY IFNULL(pc.parent_id, 0), pc.display_order
            LIMIT 10
        ");
        $categories = $stmt->fetchAll();

        echo '<pre>';
        foreach ($categories as $cat) {
            $indent = str_repeat('  ', $cat['level'] ?? 0);
            echo sprintf("%s[%d] %s (parent: %s, products: %d)\n",
                $indent,
                $cat['id'],
                $cat['category_name'],
                $cat['parent_id'] ?? 'NULL',
                $cat['product_count']
            );
        }
        echo '</pre>';
        ?>
    </div>

    <!-- 4. 트리뷰 링크 -->
    <div class="test-section">
        <h2>4. 트리뷰 페이지</h2>
        <div class="test-result info">
            <a href="admin_product_categories_tree.php" target="_blank" class="btn btn-primary">트리뷰 V1 열기</a>
            <a href="admin_product_categories_tree_v2.php" target="_blank" class="btn btn-success">트리뷰 V2 (개선) 열기</a>
            <a href="admin_product_categories.php" target="_blank" class="btn btn-secondary">테이블뷰 열기</a>
        </div>
    </div>

    <!-- 5. 콘솔 로그 -->
    <div class="test-section">
        <h2>5. 콘솔 로그</h2>
        <div class="test-result warning">
            브라우저 개발자 도구(F12)를 열고 Console 탭을 확인하세요.
        </div>
        <pre id="consoleLog" style="max-height: 300px;"></pre>
    </div>
</div>

<script>
// 콘솔 로그 캡처
const originalLog = console.log;
const originalError = console.error;
const logContainer = document.getElementById('consoleLog');

console.log = function(...args) {
    originalLog.apply(console, args);
    const msg = args.map(arg => typeof arg === 'object' ? JSON.stringify(arg, null, 2) : arg).join(' ');
    logContainer.innerHTML += `<span style="color: blue;">[LOG]</span> ${msg}\n`;
};

console.error = function(...args) {
    originalError.apply(console, args);
    const msg = args.map(arg => typeof arg === 'object' ? JSON.stringify(arg, null, 2) : arg).join(' ');
    logContainer.innerHTML += `<span style="color: red;">[ERROR]</span> ${msg}\n`;
};

// AJAX 테스트
function testAjax() {
    const resultDiv = document.getElementById('ajaxResult');
    resultDiv.innerHTML = '<div class="test-result info">테스트 중...</div>';

    // 테스트 1: get_categories_tree.php
    fetch('ajax/get_categories_tree.php')
        .then(response => {
            console.log('Response status:', response.status);
            return response.text();
        })
        .then(text => {
            console.log('Response length:', text.length);

            let html = '<div class="test-result success">✅ AJAX 응답 받음 (' + text.length + ' bytes)</div>';

            try {
                const data = JSON.parse(text);
                if (data.success) {
                    html += '<div class="test-result success">✅ JSON 파싱 성공</div>';
                    html += '<div class="test-result info">카테고리 수: ' + (data.categories ? data.categories.length : 0) + '개</div>';
                    html += '<pre>' + JSON.stringify(data, null, 2).substring(0, 500) + '...</pre>';
                } else {
                    html += '<div class="test-result error">❌ 오류: ' + data.message + '</div>';
                    if (data.error_detail) {
                        html += '<pre>' + data.error_detail + '</pre>';
                    }
                }
            } catch (e) {
                html += '<div class="test-result error">❌ JSON 파싱 실패: ' + e.message + '</div>';
                html += '<pre>응답 내용:\n' + text.substring(0, 500) + '...</pre>';
            }

            resultDiv.innerHTML = html;
        })
        .catch(error => {
            console.error('Fetch error:', error);
            resultDiv.innerHTML = '<div class="test-result error">❌ 네트워크 오류: ' + error.message + '</div>';
        });
}

// 페이지 로드 시 자동 테스트
window.addEventListener('DOMContentLoaded', function() {
    console.log('진단 페이지 로드 완료');
    setTimeout(testAjax, 1000);
});
</script>

<?php require_once 'admin_tail.php'; ?>