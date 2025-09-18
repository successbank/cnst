<?php
require_once 'db.php';

// 철근 번들 데이터의 재질 분포 확인
$stmt = $pdo->query("
    SELECT p_material, COUNT(DISTINCT p_standard) as specs, COUNT(*) as total_records
    FROM rebar_bundle_data
    GROUP BY p_material
    ORDER BY p_material
");
$materials = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Rebar Bundle Data Materials Distribution:</h2>";
echo "<table border='1'>";
echo "<tr><th>Material</th><th>Specs Count</th><th>Total Records</th></tr>";
foreach ($materials as $mat) {
    echo "<tr>";
    echo "<td>{$mat['p_material']}</td>";
    echo "<td>{$mat['specs']}</td>";
    echo "<td>{$mat['total_records']}</td>";
    echo "</tr>";
}
echo "</table>";

// 규격별 재질 확인
echo "<h2>Materials by Standard:</h2>";
$stmt = $pdo->query("
    SELECT p_standard, GROUP_CONCAT(DISTINCT p_material) as materials
    FROM rebar_bundle_data
    GROUP BY p_standard
    ORDER BY p_standard
");
$standards = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1'>";
echo "<tr><th>Standard</th><th>Available Materials</th></tr>";
foreach ($standards as $std) {
    echo "<tr>";
    echo "<td>{$std['p_standard']}</td>";
    echo "<td>{$std['materials']}</td>";
    echo "</tr>";
}
echo "</table>";
?>