<?php
session_start();
require_once 'db.php';

// 관리자 권한 체크 (필요시 추가)
// if (!isset($_SESSION['admin'])) {
//     header('Location: login.php');
//     exit;
// }

// 액션 처리
$action = $_GET['action'] ?? '';
$message = '';
$error = '';

// 제품 업데이트 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update') {
    $product_id = intval($_POST['product_id']);
    $unit_weight = floatval($_POST['unit_weight']);
    $specification = $_POST['specification'];

    try {
        // unit_weight_data JSON 업데이트
        $unit_weight_json = json_encode([$specification => $unit_weight], JSON_UNESCAPED_UNICODE);

        $stmt = $pdo->prepare("
            UPDATE products
            SET unit_weight_data = ?,
                specification_weight = ?
            WHERE id = ?
        ");
        $stmt->execute([$unit_weight_json, $unit_weight, $product_id]);

        $message = "제품 정보가 업데이트되었습니다.";
    } catch (Exception $e) {
        $error = "업데이트 실패: " . $e->getMessage();
    }
}

// 일괄 업데이트 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'bulk_update') {
    $updates = $_POST['updates'] ?? [];
    $success_count = 0;

    foreach ($updates as $update) {
        if (!empty($update['id']) && !empty($update['unit_weight'])) {
            $product_id = intval($update['id']);
            $unit_weight = floatval($update['unit_weight']);
            $specification = $update['specification'];

            try {
                // unit_weight_data JSON 업데이트
                $unit_weight_json = json_encode([$specification => $unit_weight], JSON_UNESCAPED_UNICODE);

                $stmt = $pdo->prepare("
                    UPDATE products
                    SET unit_weight_data = ?,
                        specification_weight = ?
                    WHERE id = ?
                ");
                $stmt->execute([$unit_weight_json, $unit_weight, $product_id]);
                $success_count++;
            } catch (Exception $e) {
                // 개별 오류는 무시하고 계속 진행
            }
        }
    }

    if ($success_count > 0) {
        $message = "{$success_count}개 제품이 업데이트되었습니다.";
    }
}

// 카테고리 필터
$category_filter = $_GET['category'] ?? 'unequal-angle';

// 제품 목록 조회
$stmt = $pdo->prepare("
    SELECT p.*, pc.category_name
    FROM products p
    LEFT JOIN product_categories pc ON p.category_code = pc.category_code
    WHERE p.category_code = ?
    ORDER BY p.id
");
$stmt->execute([$category_filter]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 카테고리 목록 조회
$categories_stmt = $pdo->query("
    SELECT DISTINCT p.category_code, pc.category_name, COUNT(p.id) as product_count
    FROM products p
    LEFT JOIN product_categories pc ON p.category_code = pc.category_code
    GROUP BY p.category_code, pc.category_name
    ORDER BY pc.category_name
");
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = '제품군 관리';
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
            background: #f5f5f5;
            color: #333;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        h1 {
            font-size: 28px;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .filters {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .filter-tabs {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-tab {
            padding: 10px 20px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            text-decoration: none;
            color: #495057;
            transition: all 0.3s;
        }

        .filter-tab:hover {
            background: #e9ecef;
        }

        .filter-tab.active {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }

        .filter-tab span {
            display: inline-block;
            background: rgba(0,0,0,0.1);
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            margin-left: 5px;
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

        .products-table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f8f9fa;
        }

        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
        }

        td {
            padding: 12px 15px;
            border-bottom: 1px solid #dee2e6;
        }

        tbody tr:hover {
            background: #f8f9fa;
        }

        .input-unit-weight {
            width: 100px;
            padding: 5px 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 14px;
        }

        .input-unit-weight:focus {
            outline: none;
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background: #0056b3;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .btn-warning:hover {
            background: #e0a800;
        }

        .action-buttons {
            position: sticky;
            bottom: 0;
            background: white;
            padding: 20px;
            border-top: 2px solid #dee2e6;
            margin-top: 20px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-badge.active {
            background: #d4edda;
            color: #155724;
        }

        .status-badge.inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .spec-info {
            font-size: 12px;
            color: #6c757d;
        }

        .checkbox-col {
            width: 40px;
        }

        .checkbox-all {
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            table {
                font-size: 12px;
            }

            th, td {
                padding: 8px 10px;
            }

            .input-unit-weight {
                width: 80px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🛠️ 제품군 관리 시스템</h1>
            <p style="color: #6c757d;">제품별 단위중량 및 규격 정보를 관리합니다.</p>
        </header>

        <?php if ($message): ?>
        <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="filters">
            <h3 style="margin-bottom: 15px; color: #495057;">카테고리 선택</h3>
            <div class="filter-tabs">
                <?php foreach ($categories as $cat): ?>
                <a href="?category=<?php echo urlencode($cat['category_code']); ?>"
                   class="filter-tab <?php echo $category_filter === $cat['category_code'] ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($cat['category_name'] ?: $cat['category_code']); ?>
                    <span><?php echo $cat['product_count']; ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <form method="POST" action="?action=bulk_update&category=<?php echo urlencode($category_filter); ?>">
            <div class="products-table">
                <table>
                    <thead>
                        <tr>
                            <th class="checkbox-col">
                                <input type="checkbox" class="checkbox-all" onclick="toggleAll(this)">
                            </th>
                            <th>ID</th>
                            <th>제품명</th>
                            <th>규격</th>
                            <th>단위중량 (kg/m)</th>
                            <th>현재 설정값</th>
                            <th>상태</th>
                            <th>작업</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $index => $product):
                            $unit_weight_data = !empty($product['unit_weight_data']) ? json_decode($product['unit_weight_data'], true) : [];
                            $current_weight = 0;
                            if ($product['specification'] && isset($unit_weight_data[$product['specification']])) {
                                $current_weight = is_array($unit_weight_data[$product['specification']])
                                    ? reset($unit_weight_data[$product['specification']])
                                    : $unit_weight_data[$product['specification']];
                            }
                        ?>
                        <tr>
                            <td class="checkbox-col">
                                <input type="checkbox" name="selected[]" value="<?php echo $product['id']; ?>">
                            </td>
                            <td><?php echo $product['id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($product['product_name']); ?></strong>
                                <div class="spec-info">
                                    <?php echo htmlspecialchars($product['category_name']); ?>
                                </div>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($product['specification'] ?? '-'); ?>
                                <input type="hidden" name="updates[<?php echo $index; ?>][specification]"
                                       value="<?php echo htmlspecialchars($product['specification'] ?? ''); ?>">
                            </td>
                            <td>
                                <input type="number"
                                       step="0.01"
                                       class="input-unit-weight"
                                       name="updates[<?php echo $index; ?>][unit_weight]"
                                       value="<?php echo $current_weight; ?>"
                                       placeholder="0.00">
                                <input type="hidden" name="updates[<?php echo $index; ?>][id]"
                                       value="<?php echo $product['id']; ?>">
                            </td>
                            <td>
                                <?php if ($product['specification_weight']): ?>
                                    <strong><?php echo number_format($product['specification_weight'], 2); ?></strong> kg/m
                                <?php else: ?>
                                    <span style="color: #dc3545;">미설정</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($product['has_calculator']): ?>
                                    <span class="status-badge active">계산기 활성</span>
                                <?php else: ?>
                                    <span class="status-badge inactive">계산기 비활성</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="product_detail.php?id=<?php echo $product['id']; ?>"
                                   target="_blank"
                                   class="btn btn-primary btn-sm">
                                    보기
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="action-buttons">
                <button type="button" onclick="applyToSelected()" class="btn btn-warning">
                    선택 항목 일괄 적용
                </button>
                <button type="submit" class="btn btn-success">
                    전체 저장
                </button>
            </div>
        </form>
    </div>

    <script>
        function toggleAll(checkbox) {
            const checkboxes = document.querySelectorAll('input[name="selected[]"]');
            checkboxes.forEach(cb => cb.checked = checkbox.checked);
        }

        function applyToSelected() {
            const selectedBoxes = document.querySelectorAll('input[name="selected[]"]:checked');
            if (selectedBoxes.length === 0) {
                alert('적용할 항목을 선택해주세요.');
                return;
            }

            const value = prompt('선택한 항목에 적용할 단위중량 값을 입력하세요 (kg/m):');
            if (value !== null && value !== '') {
                const floatValue = parseFloat(value);
                if (isNaN(floatValue)) {
                    alert('올바른 숫자를 입력해주세요.');
                    return;
                }

                selectedBoxes.forEach(checkbox => {
                    const row = checkbox.closest('tr');
                    const input = row.querySelector('.input-unit-weight');
                    if (input) {
                        input.value = floatValue;
                        input.style.background = '#ffffcc';
                    }
                });
            }
        }

        // 값이 변경된 입력 필드 하이라이트
        document.querySelectorAll('.input-unit-weight').forEach(input => {
            const originalValue = input.value;
            input.addEventListener('change', function() {
                if (this.value !== originalValue) {
                    this.style.background = '#ffffcc';
                } else {
                    this.style.background = '';
                }
            });
        });
    </script>
</body>
</html>