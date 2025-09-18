<?php
require_once 'db.php';

// 철근 제품 찾기
$stmt = $pdo->prepare("
    SELECT p.*, pc.category_name
    FROM products p 
    JOIN product_categories pc ON p.category_code = pc.category_code 
    WHERE p.id = 219 AND p.is_active = 1
");
$stmt->execute();
$product = $stmt->fetch(PDO::FETCH_ASSOC);

// 철근 규격 추출
preg_match('/(H?D\d+)/', $product['product_name'], $matches);
$rebar_spec = isset($matches[1]) ? $matches[1] : '';

// 번들 데이터 조회
$bundle_data = [];
if ($rebar_spec) {
    // 해당 규격에 사용 가능한 재질 찾기
    $material_stmt = $pdo->prepare("
        SELECT DISTINCT p_material 
        FROM rebar_bundle_data 
        WHERE p_standard = ?
        LIMIT 1
    ");
    $material_stmt->execute([$rebar_spec]);
    $rebar_material = $material_stmt->fetchColumn();
    
    if ($rebar_material) {
        $bundle_stmt = $pdo->prepare("
            SELECT p_unit_length, p_bd_count, p_bd_weight 
            FROM rebar_bundle_data 
            WHERE p_standard = ? AND p_material = ? 
            AND p_bd_count > 0 AND p_bd_weight > 0
            ORDER BY p_unit_length
            LIMIT 10
        ");
        $bundle_stmt->execute([$rebar_spec, $rebar_material]);
        while ($row = $bundle_stmt->fetch(PDO::FETCH_ASSOC)) {
            $bundle_data[$row['p_unit_length']] = [
                'bd_count' => $row['p_bd_count'],
                'bd_weight' => $row['p_bd_weight']
            ];
        }
    }
}

echo "Product: " . $product['product_name'] . "\n";
echo "Spec: " . $rebar_spec . "\n"; 
echo "Material: " . $rebar_material . "\n\n";
echo "Bundle Data:\n";

?>
<select>
    <option value="">길이를 선택하세요</option>
    <?php foreach ($bundle_data as $length => $info): ?>
    <option value="<?php echo $length; ?>">
        <?php echo $length; ?>m (번들당 <?php echo $info['bd_count']; ?>본, <?php echo $info['bd_weight']; ?>kg)
    </option>
    <?php endforeach; ?>
</select>

<pre>
<?php print_r($bundle_data); ?>
</pre>