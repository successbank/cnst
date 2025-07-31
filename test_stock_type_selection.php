<?php
require_once 'db.php';

// 다양한 재고 상태를 가진 테스트 데이터 설정
$test_data = [
    ['id' => 728, 'stock_types' => '["일반재고"]', 'desc' => '단일 재고 상태 (일반재고만)'],
    ['id' => 729, 'stock_types' => '["일반재고", "장기재고"]', 'desc' => '복수 재고 상태 (일반재고, 장기재고)'],
    ['id' => 730, 'stock_types' => '["일반재고", "중고"]', 'desc' => '복수 재고 상태 (일반재고, 중고)'],
    ['id' => 731, 'stock_types' => '["일반재고", "장기재고", "중고"]', 'desc' => '모든 재고 상태'],
    ['id' => 732, 'stock_types' => '["중고"]', 'desc' => '단일 재고 상태 (중고만)']
];

echo "<h2>재고 상태 선택 기능 테스트</h2>";
echo "<p>제품 상세 페이지에서 재고 상태를 선택할 수 있는 기능이 구현되었습니다.</p>";

// 테스트 데이터 설정
echo "<h3>1. 테스트 데이터 설정</h3>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th>제품 ID</th>";
echo "<th>제품명</th>";
echo "<th>재고 상태</th>";
echo "<th>설명</th>";
echo "<th>테스트 링크</th>";
echo "</tr>";

foreach ($test_data as $data) {
    // 데이터 업데이트
    $stmt = $pdo->prepare("UPDATE products SET stock_types = ? WHERE id = ?");
    $stmt->execute([$data['stock_types'], $data['id']]);
    
    // 제품 정보 가져오기
    $stmt = $pdo->prepare("SELECT product_name FROM products WHERE id = ?");
    $stmt->execute([$data['id']]);
    $product = $stmt->fetch();
    
    if ($product) {
        $stock_types = json_decode($data['stock_types'], true);
        echo "<tr>";
        echo "<td style='text-align: center;'>{$data['id']}</td>";
        echo "<td>{$product['product_name']}</td>";
        echo "<td>" . implode(', ', $stock_types) . "</td>";
        echo "<td style='color: #666;'>{$data['desc']}</td>";
        echo "<td><a href='/product_detail.php?id={$data['id']}' target='_blank' style='color: #1976d2;'>상세 페이지 보기</a></td>";
        echo "</tr>";
    }
}

echo "</table>";

echo "<h3>2. 구현된 기능</h3>";
echo "<ul>";
echo "<li><strong>단일 재고 상태</strong>: 텍스트로만 표시 (선택 불가)</li>";
echo "<li><strong>복수 재고 상태</strong>: 드롭다운으로 선택 가능</li>";
echo "<li><strong>시각적 피드백</strong>: 장기재고는 주황색, 중고는 회색으로 표시</li>";
echo "<li><strong>선택 저장</strong>: 선택한 재고 상태는 브라우저 세션에 저장되어 페이지 재방문 시 유지</li>";
echo "</ul>";

echo "<h3>3. 사용 방법</h3>";
echo "<ol>";
echo "<li>위 테스트 링크를 클릭하여 제품 상세 페이지로 이동</li>";
echo "<li>재고 상태가 여러 개인 제품은 드롭다운이 표시됨</li>";
echo "<li>드롭다운에서 원하는 재고 상태 선택</li>";
echo "<li>선택한 상태는 자동으로 저장되어 다음 방문 시에도 유지됨</li>";
echo "</ol>";

echo "<h3>4. 기타 링크</h3>";
echo "<ul>";
echo "<li><a href='/admin/admin_origin_stock.php' target='_blank'>관리자 - 원산지 재고 형식 관리</a></li>";
echo "<li><a href='/products_new.php?category=rebar' target='_blank'>제품 목록 (재고 상태 표시 안함)</a></li>";
echo "</ul>";

// 스타일 추가
echo "<style>";
echo "body { font-family: Arial, sans-serif; padding: 20px; line-height: 1.6; }";
echo "h2 { color: #1976d2; }";
echo "h3 { color: #333; margin-top: 30px; }";
echo "table { margin: 20px 0; }";
echo "a { text-decoration: none; }";
echo "a:hover { text-decoration: underline; }";
echo "ul, ol { margin: 15px 0; }";
echo "li { margin: 8px 0; }";
echo "</style>";
?>