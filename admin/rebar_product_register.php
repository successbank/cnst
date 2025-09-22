<?php
session_start();
require_once '../db.php';
require_once '../includes/rebar_unit_weights.php';
require_once 'admin_check.php';

// 관리자 권한 확인
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

$message = '';
$error = '';

// 철근 규격 목록
$specs = getAllRebarSpecs($pdo);

// 철근 재질 목록
$materials = getAllRebarMaterials($pdo);

// 원산지 목록
$origins = [
    ['code' => 'domestic', 'name' => '국산', 'price' => 0],
    ['code' => 'china', 'name' => '중국', 'price' => -50],
    ['code' => 'japan', 'name' => '일본', 'price' => 100],
    ['code' => 'usa', 'name' => '미국', 'price' => 150]
];

// 폼 제출 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $spec_id = $_POST['spec_id'];
        $material_id = $_POST['material_id'];
        $origin = $_POST['origin'];
        $base_price = floatval($_POST['base_price']);
        $origin_price = floatval($_POST['origin_price']);
        $lengths = $_POST['lengths']; // 배열

        // 규격 정보 가져오기
        $spec_stmt = $pdo->prepare("SELECT * FROM rebar_specifications WHERE id = ?");
        $spec_stmt->execute([$spec_id]);
        $spec = $spec_stmt->fetch();

        // 재질 정보 가져오기
        $material_stmt = $pdo->prepare("SELECT * FROM rebar_materials WHERE id = ?");
        $material_stmt->execute([$material_id]);
        $material = $material_stmt->fetch();

        $inserted_count = 0;

        // 각 길이별로 제품 등록
        foreach ($lengths as $length) {
            $length = floatval($length);
            if ($length <= 0) continue;

            // 번들 정보 가져오기
            $bundle_info = getRebarBundleInfo($pdo, $spec['spec_name'], $length);

            if ($bundle_info) {
                // 가격 계산
                $quantity = $bundle_info['pieces_per_ton'];
                $calculated_price = calculateRebarPrice(
                    $base_price,
                    $origin_price,
                    $material['price_per_kg'],
                    $length,
                    $quantity,
                    $spec['weight_per_meter']
                );

                // 제품명 생성
                $product_name = "철근 {$spec['spec_name']} {$material['material_name']} {$length}m";

                // products 테이블에 삽입
                $insert_stmt = $pdo->prepare("
                    INSERT INTO products (
                        category_code,
                        product_name,
                        specifications,
                        description,
                        price,
                        origin,
                        material,
                        length,
                        stock_status,
                        is_active
                    ) VALUES (
                        'rebar',
                        :product_name,
                        :specifications,
                        :description,
                        :price,
                        :origin,
                        :material,
                        :length,
                        'in_stock',
                        1
                    )
                ");

                $specifications = "{$spec['spec_name']} ({$spec['diameter']}mm, {$spec['weight_per_meter']}kg/m)";
                $description = "재질: {$material['material_name']}, 길이: {$length}m, 번들당: {$quantity}개, 번들중량: {$bundle_info['weight_per_ton']}kg";

                $insert_stmt->execute([
                    ':product_name' => $product_name,
                    ':specifications' => $specifications,
                    ':description' => $description,
                    ':price' => $calculated_price,
                    ':origin' => $origin,
                    ':material' => $material['material_name'],
                    ':length' => $length
                ]);

                $product_id = $pdo->lastInsertId();

                // rebar_products 테이블에도 삽입
                $rebar_insert = $pdo->prepare("
                    INSERT INTO rebar_products (
                        spec_id,
                        material_id,
                        origin,
                        price
                    ) VALUES (?, ?, ?, ?)
                ");
                $rebar_insert->execute([$spec_id, $material_id, $origin, $calculated_price]);

                $inserted_count++;
            }
        }

        $message = "{$inserted_count}개의 철근 제품이 성공적으로 등록되었습니다.";

    } catch (Exception $e) {
        $error = "오류 발생: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>철근 제품 등록 - 관리자</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Noto Sans KR', sans-serif; background: #f5f5f5; }

        .container {
            max-width: 800px;
            margin: 50px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        h1 {
            color: #333;
            margin-bottom: 30px;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #555;
        }

        select, input[type="number"], input[type="text"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .length-inputs {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }

        .length-checkbox {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .calculate-preview {
            background: #f0f8ff;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            display: none;
        }

        .calculate-preview.show {
            display: block;
        }

        .price-formula {
            background: #fffbf0;
            padding: 10px;
            border-left: 3px solid #ffa500;
            margin: 10px 0;
            font-family: monospace;
        }

        .submit-btn {
            background: #4CAF50;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .submit-btn:hover {
            background: #45a049;
        }

        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #4CAF50;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>철근 제품 등록</h1>

        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" id="rebarForm">
            <div class="form-group">
                <label>철근 규격</label>
                <select name="spec_id" id="spec_id" required>
                    <option value="">선택하세요</option>
                    <?php foreach ($specs as $spec): ?>
                        <option value="<?php echo $spec['id']; ?>"
                                data-weight="<?php echo $spec['weight_per_meter']; ?>"
                                data-name="<?php echo $spec['spec_name']; ?>">
                            <?php echo $spec['spec_name']; ?> (직경: <?php echo $spec['diameter']; ?>mm, 단중: <?php echo $spec['weight_per_meter']; ?>kg/m)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>철근 재질</label>
                <select name="material_id" id="material_id" required>
                    <option value="">선택하세요</option>
                    <?php foreach ($materials as $material): ?>
                        <option value="<?php echo $material['id']; ?>"
                                data-price="<?php echo $material['price_per_kg']; ?>"
                                data-name="<?php echo $material['material_name']; ?>">
                            <?php echo $material['material_name']; ?> (kg당 <?php echo $material['price_per_kg']; ?>원)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>원산지</label>
                <select name="origin" id="origin" required>
                    <option value="">선택하세요</option>
                    <?php foreach ($origins as $origin): ?>
                        <option value="<?php echo $origin['code']; ?>"
                                data-price="<?php echo $origin['price']; ?>">
                            <?php echo $origin['name']; ?> (추가: <?php echo $origin['price']; ?>원)
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="origin_price" id="origin_price" value="0">
            </div>

            <div class="form-group">
                <label>기준 단가 (원)</label>
                <input type="number" name="base_price" id="base_price" required min="0" step="1" value="1000">
            </div>

            <div class="form-group">
                <label>길이 선택 (m)</label>
                <div class="length-inputs">
                    <?php
                    $common_lengths = [6, 6.5, 7, 7.5, 8, 8.5, 9, 9.5, 10, 10.5, 11, 12];
                    foreach ($common_lengths as $length):
                    ?>
                        <div class="length-checkbox">
                            <input type="checkbox" name="lengths[]" value="<?php echo $length; ?>" id="length_<?php echo $length; ?>">
                            <label for="length_<?php echo $length; ?>"><?php echo $length; ?>m</label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="calculate-preview" id="calculatePreview">
                <h3>가격 계산 미리보기</h3>
                <div class="price-formula">
                    계산식: (기준 단가 + 원산지 단가 + 재질 단가) × 길이 × 단위(개)
                </div>
                <div id="previewContent"></div>
            </div>

            <button type="submit" class="submit-btn">철근 제품 등록</button>
        </form>

        <a href="admin_index.php" class="back-link">← 관리자 메인으로</a>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('rebarForm');
        const specSelect = document.getElementById('spec_id');
        const materialSelect = document.getElementById('material_id');
        const originSelect = document.getElementById('origin');
        const originPriceInput = document.getElementById('origin_price');
        const basePriceInput = document.getElementById('base_price');
        const previewDiv = document.getElementById('calculatePreview');
        const previewContent = document.getElementById('previewContent');

        // 원산지 선택 시 가격 업데이트
        originSelect.addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            originPriceInput.value = selected.dataset.price || 0;
            updatePreview();
        });

        // 미리보기 업데이트
        function updatePreview() {
            const spec = specSelect.options[specSelect.selectedIndex];
            const material = materialSelect.options[materialSelect.selectedIndex];
            const origin = originSelect.options[originSelect.selectedIndex];
            const basePrice = parseFloat(basePriceInput.value) || 0;

            if (!spec.value || !material.value || !origin.value) {
                previewDiv.classList.remove('show');
                return;
            }

            const unitWeight = parseFloat(spec.dataset.weight);
            const materialPrice = parseFloat(material.dataset.price);
            const originPrice = parseFloat(origin.dataset.price);

            // 선택된 길이들
            const selectedLengths = [];
            document.querySelectorAll('input[name="lengths[]"]:checked').forEach(cb => {
                selectedLengths.push(parseFloat(cb.value));
            });

            if (selectedLengths.length === 0) {
                previewDiv.classList.remove('show');
                return;
            }

            let html = '<table style="width:100%; border-collapse: collapse;">';
            html += '<tr><th>길이</th><th>무게(kg)</th><th>예상 가격</th></tr>';

            selectedLengths.forEach(length => {
                const weight = unitWeight * length;
                // 번들당 100개 가정 (실제는 DB에서 가져와야 함)
                const quantity = 100;
                const totalWeight = weight * quantity;
                const materialCost = materialPrice * totalWeight;
                const totalPrice = (basePrice + originPrice) * quantity + materialCost;

                html += `<tr>
                    <td>${length}m</td>
                    <td>${totalWeight.toFixed(2)}</td>
                    <td>${totalPrice.toLocaleString()}원</td>
                </tr>`;
            });

            html += '</table>';
            previewContent.innerHTML = html;
            previewDiv.classList.add('show');
        }

        // 이벤트 리스너 추가
        specSelect.addEventListener('change', updatePreview);
        materialSelect.addEventListener('change', updatePreview);
        basePriceInput.addEventListener('input', updatePreview);
        document.querySelectorAll('input[name="lengths[]"]').forEach(cb => {
            cb.addEventListener('change', updatePreview);
        });
    });
    </script>
</body>
</html>