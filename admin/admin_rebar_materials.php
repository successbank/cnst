<?php
session_start();
require_once '../db.php';
require_once 'admin_check.php';

$pageTitle = '철근 재질 및 단가 관리';

// 추가 스타일 정의
$additionalStyles = '
/* 모바일 메뉴 버튼 숨기기 */
.mobile-menu-toggle {
    display: none !important;
}

.data-table table {
    table-layout: fixed;
}

.data-table th {
    text-align: center;
    background: #f8f9fa;
    font-weight: 600;
    padding: 12px;
    border-bottom: 2px solid #dee2e6;
}

.data-table td {
    text-align: center;
    padding: 12px;
    border-bottom: 1px solid #e9ecef;
    vertical-align: middle;
}

.page-header {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 30px;
}

.page-header h1 {
    margin: 0 0 10px 0;
    color: #2c3e50;
    font-size: 28px;
}

.page-header p {
    margin: 0;
    color: #6c757d;
    font-size: 16px;
}

.content-section {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 30px;
}

.content-section h2 {
    font-size: 20px;
    font-weight: 600;
    color: #333;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
}

.data-table {
    width: 100%;
    margin-top: 20px;
}

.data-table table {
    width: 100%;
    border-collapse: collapse;
}

.price-input {
    width: 120px;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    text-align: right;
    background: white;
}

.price-input:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 2px rgba(0,123,255,0.1);
}

.form-inline {
    display: flex;
    gap: 15px;
    align-items: end;
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.form-group {
    margin-bottom: 0;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    color: #555;
    font-size: 14px;
}

.form-group input {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    background: white;
}

.active-badge {
    background: #28a745;
    color: white;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
}

.inactive-badge {
    background: #dc3545;
    color: white;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
}

.data-table input[type="checkbox"] {
    margin-right: 8px;
    vertical-align: middle;
}

.data-table td form {
    display: contents;
}

.calculation-example {
    background: #f0f7ff;
    padding: 30px;
    border-radius: 12px;
    border: 1px solid #d0e5ff;
    margin: 20px 0;
}

.calculation-example h3 {
    color: #0056b3;
    margin-bottom: 20px;
    font-size: 18px;
}

.calculation-example .formula {
    font-family: "Courier New", monospace;
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin: 10px 0;
    line-height: 1.8;
    border: 1px solid #e3e6ea;
}
';

// 재질 추가/수정 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_material') {
            // 재질 추가
            $stmt = $pdo->prepare("
                INSERT INTO rebar_materials (material_code, material_name, additional_price, description, display_order) 
                VALUES (:material_code, :material_name, :additional_price, :description, :display_order)
            ");
            $stmt->execute([
                'material_code' => $_POST['material_code'],
                'material_name' => $_POST['material_name'],
                'additional_price' => $_POST['additional_price'],
                'description' => $_POST['description'],
                'display_order' => $_POST['display_order']
            ]);
            $_SESSION['success_message'] = '재질이 추가되었습니다.';
        } elseif ($_POST['action'] === 'update_material') {
            // 재질 수정
            $stmt = $pdo->prepare("
                UPDATE rebar_materials 
                SET material_name = :material_name, 
                    additional_price = :additional_price, 
                    description = :description,
                    display_order = :display_order,
                    is_active = :is_active
                WHERE id = :id
            ");
            $stmt->execute([
                'id' => $_POST['material_id'],
                'material_name' => $_POST['material_name'],
                'additional_price' => $_POST['additional_price'],
                'description' => $_POST['description'],
                'display_order' => $_POST['display_order'],
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            ]);
            $_SESSION['success_message'] = '재질 정보가 수정되었습니다.';
        } elseif ($_POST['action'] === 'update_prices') {
            // 기본 단가 업데이트
            foreach ($_POST['prices'] as $spec_id => $price) {
                if (!empty($price)) {
                    // 기존 가격 비활성화
                    $stmt = $pdo->prepare("UPDATE rebar_prices SET is_active = FALSE WHERE spec_id = ?");
                    $stmt->execute([$spec_id]);
                    
                    // 새 가격 추가
                    $stmt = $pdo->prepare("
                        INSERT INTO rebar_prices (spec_id, unit_price, effective_date, is_active, created_by) 
                        VALUES (?, ?, CURDATE(), TRUE, ?)
                    ");
                    $stmt->execute([$spec_id, $price, $_SESSION['admin_id'] ?? 0]);
                }
            }
            $_SESSION['success_message'] = '단가가 업데이트되었습니다.';
        }
        
        header('Location: admin_rebar_materials.php');
        exit;
    }
}

// 재질 목록 조회
$materials = $pdo->query("SELECT * FROM rebar_materials ORDER BY display_order")->fetchAll();

// 규격별 현재 가격 조회
$stmt = $pdo->query("
    SELECT 
        rs.id,
        rs.spec_name,
        rs.diameter,
        rs.unit_weight,
        rp.unit_price,
        rp.effective_date
    FROM rebar_specifications rs
    LEFT JOIN rebar_prices rp ON rs.id = rp.spec_id AND rp.is_active = TRUE
    WHERE rs.is_active = TRUE
    ORDER BY rs.display_order
");
$specifications = $stmt->fetchAll();

require_once 'admin_head.php';
?>

<?php
// 메시지 표시
if (isset($_SESSION['success_message'])) {
    echo '<div class="msg success">' . $_SESSION['success_message'] . '</div>';
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    echo '<div class="msg error">' . $_SESSION['error_message'] . '</div>';
    unset($_SESSION['error_message']);
}
?>

<!-- 재질 관리 섹션 -->
<div class="content-section">
    <h2>재질 관리</h2>
    
    <!-- 재질 추가 폼 -->
    <form method="POST" class="form-inline">
        <input type="hidden" name="action" value="add_material">
        <div class="form-group">
            <label>재질 코드</label>
            <input type="text" name="material_code" required style="width: 100px;">
        </div>
        <div class="form-group">
            <label>재질명</label>
            <input type="text" name="material_name" required style="width: 120px;">
        </div>
        <div class="form-group">
            <label>추가단가(원/kg)</label>
            <input type="number" name="additional_price" min="0" step="0.01" required style="width: 120px;">
        </div>
        <div class="form-group">
            <label>설명</label>
            <input type="text" name="description" style="width: 200px;">
        </div>
        <div class="form-group">
            <label>표시순서</label>
            <input type="number" name="display_order" value="0" style="width: 80px;">
        </div>
        <button type="submit" class="btn btn-primary">재질 추가</button>
    </form>
    
    <!-- 재질 목록 -->
    <div class="data-table">
    <table>
        <thead>
            <tr>
                <th>재질 코드</th>
                <th>재질명</th>
                <th>추가단가</th>
                <th>설명</th>
                <th>표시순서</th>
                <th>상태</th>
                <th>작업</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($materials as $material): ?>
            <tr>
                <form method="POST">
                    <input type="hidden" name="action" value="update_material">
                    <input type="hidden" name="material_id" value="<?= $material['id'] ?>">
                    <td><?= htmlspecialchars($material['material_code']) ?></td>
                    <td><input type="text" name="material_name" value="<?= htmlspecialchars($material['material_name']) ?>" class="price-input"></td>
                    <td><input type="number" name="additional_price" value="<?= $material['additional_price'] ?>" min="0" step="0.01" class="price-input"></td>
                    <td><input type="text" name="description" value="<?= htmlspecialchars($material['description'] ?? '') ?>" style="width: 200px;"></td>
                    <td><input type="number" name="display_order" value="<?= $material['display_order'] ?>" style="width: 60px;"></td>
                    <td>
                        <label style="display: inline-flex; align-items: center; margin: 0;">
                            <input type="checkbox" name="is_active" value="1" <?= $material['is_active'] ? 'checked' : '' ?>>
                            <?= $material['is_active'] ? '<span class="active-badge">활성</span>' : '<span class="inactive-badge">비활성</span>' ?>
                        </label>
                    </td>
                    <td><button type="submit" class="btn btn-sm btn-secondary">수정</button></td>
                </form>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

<!-- 기본 단가 관리 섹션 -->
<div class="content-section">
    <h2>기본 단가 관리</h2>
    
    <form method="POST">
        <input type="hidden" name="action" value="update_prices">
        <div class="data-table">
        <table>
            <thead>
                <tr>
                    <th>규격</th>
                    <th>직경(mm)</th>
                    <th>단위중량(kg/m)</th>
                    <th>현재단가(원/kg)</th>
                    <th>신규단가(원/kg)</th>
                    <th>최종변경일</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($specifications as $spec): ?>
                <tr>
                    <td><?= htmlspecialchars($spec['spec_name']) ?></td>
                    <td><?= $spec['diameter'] ?></td>
                    <td><?= $spec['unit_weight'] ?></td>
                    <td><?= $spec['unit_price'] ? number_format($spec['unit_price']) : '-' ?></td>
                    <td>
                        <input type="number" name="prices[<?= $spec['id'] ?>]" 
                               placeholder="<?= $spec['unit_price'] ?: '입력' ?>" 
                               min="0" step="0.01" class="price-input">
                    </td>
                    <td><?= $spec['effective_date'] ?: '-' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <div style="margin-top: 20px; text-align: right;">
            <button type="submit" class="btn btn-primary">단가 업데이트</button>
        </div>
    </form>
</div>

<!-- 계산 예시 -->
<div class="calculation-example">
    <h3>견적 계산 예시</h3>
    <div class="formula">
        <strong>예시: D10, 단위중량 0.56kg/m, 길이 8m, BD 본수 210개, 기본 단가 2,000원/kg, 재질: SD500 (+40원/kg)</strong><br><br>
        
        1. 본당 중량 = 단위중량 × 길이<br>
        &nbsp;&nbsp;&nbsp;= 0.56 × 8 = 4.48kg<br><br>
        
        2. 총 중량 = 본당 중량 × BD 본수<br>
        &nbsp;&nbsp;&nbsp;= 4.48 × 210 = 940.8kg<br><br>
        
        3. 적용 단가 = 기준단가 + 재질 추가단가<br>
        &nbsp;&nbsp;&nbsp;= 2,000 + 40 = 2,040원/kg<br><br>
        
        4. 총 금액 = 총 중량 × 적용 단가<br>
        &nbsp;&nbsp;&nbsp;= 940.8 × 2,040 = 1,919,232원
    </div>
</div>

<?php require_once 'admin_foot.php'; ?>