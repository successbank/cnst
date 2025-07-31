<?php
require_once 'db.php';

echo "<h2>필터 기능 테스트</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    .test-link { display: inline-block; margin: 5px; padding: 10px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
    .test-link:hover { background: #0056b3; }
</style>";

// 테스트 링크들
echo "<h3>필터 테스트 링크</h3>";
echo "<div>";
echo "<a href='products_new.php?category=all&view=tile&origin=국산&stock=all' class='test-link'>국산 제품만 보기</a>";
echo "<a href='products_new.php?category=all&view=tile&origin=중국산&stock=all' class='test-link'>중국산 제품만 보기</a>";
echo "<a href='products_new.php?category=all&view=tile&origin=일본산&stock=all' class='test-link'>일본산 제품만 보기</a>";
echo "<a href='products_new.php?category=all&view=tile&origin=국산&stock=long_term' class='test-link'>국산 + 장기재고</a>";
echo "<a href='products_new.php?category=all&view=tile&origin=국산&stock=used' class='test-link'>국산 + 중고</a>";
echo "</div>";

// 필터별 제품 수 확인
echo "<h3>필터별 제품 수</h3>";
echo "<table>";
echo "<tr><th>필터 조건</th><th>제품 수</th></tr>";

// 원산지별
$origins = ['국산', '중국산', '일본산', '베트남산', '바레인산', '수입산'];
foreach ($origins as $origin) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE is_active = 1 AND origin = ?");
    $stmt->execute([$origin]);
    $result = $stmt->fetch();
    echo "<tr><td>원산지: {$origin}</td><td>{$result['count']}개</td></tr>";
}

// 재고 상태별
echo "<tr><td colspan='2' style='background: #f8f9fa; font-weight: bold;'>재고 상태별</td></tr>";
$stock_types = [
    'normal' => '일반',
    'long_term' => '장기재고',
    'used' => '중고'
];
foreach ($stock_types as $type => $label) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE is_active = 1 AND stock_type = ?");
    $stmt->execute([$type]);
    $result = $stmt->fetch();
    echo "<tr><td>재고 상태: {$label}</td><td>{$result['count']}개</td></tr>";
}

// 조합 필터
echo "<tr><td colspan='2' style='background: #f8f9fa; font-weight: bold;'>조합 필터</td></tr>";
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE is_active = 1 AND origin = '국산' AND stock_type = 'normal'");
$stmt->execute();
$result = $stmt->fetch();
echo "<tr><td>국산 + 일반</td><td>{$result['count']}개</td></tr>";

$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE is_active = 1 AND origin = '국산' AND stock_type = 'long_term'");
$stmt->execute();
$result = $stmt->fetch();
echo "<tr><td>국산 + 장기재고</td><td>{$result['count']}개</td></tr>";

echo "</table>";

// 관리자 페이지 링크
echo "<h3>관리자 페이지</h3>";
echo "<a href='admin/admin_product_groups.php' class='test-link'>제품군 단위 관리 페이지</a>";
?>