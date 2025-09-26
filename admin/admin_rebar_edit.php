<?php
session_start();
require_once '../db.php';
require_once 'admin_check.php';

$id = $_GET['id'] ?? 0;
if (!$id) {
    $_SESSION['msg'] = "잘못된 접근입니다.";
    $_SESSION['msgType'] = 'error';
    header('Location: admin_rebar_manage.php');
    exit;
}

// 제품 정보 가져오기
$stmt = $pdo->prepare("SELECT * FROM rebar_products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    $_SESSION['msg'] = "제품을 찾을 수 없습니다.";
    $_SESSION['msgType'] = 'error';
    header('Location: admin_rebar_manage.php');
    exit;
}

// POST 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = $_POST['name'];
        $diameter = $_POST['diameter'];
        $weight_per_meter = $_POST['weight_per_meter'];
        $price_per_ton = $_POST['price_per_ton'] ?? 0;
        $is_active = $_POST['is_active'];

        $updateStmt = $pdo->prepare("UPDATE rebar_products
                                     SET name = ?, diameter = ?, weight_per_meter = ?,
                                         price_per_ton = ?, is_active = ?, updated_at = NOW()
                                     WHERE id = ?");
        $updateStmt->execute([$name, $diameter, $weight_per_meter, $price_per_ton, $is_active, $id]);

        $_SESSION['msg'] = "철근 제품 정보가 수정되었습니다.";
        $_SESSION['msgType'] = 'success';
        header('Location: admin_rebar_manage.php');
        exit;
    } catch (Exception $e) {
        $error = "오류: " . $e->getMessage();
    }
}

$pageTitle = '철근 제품 수정';
require_once 'admin_head.php';
?>

<style>
.edit-container {
    max-width: 800px;
    margin: 20px auto;
    padding: 20px;
}

.form-box {
    background: white;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
    color: #333;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #DDD;
    border-radius: 6px;
    font-size: 14px;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #007BFF;
    box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
}

.btn-group {
    display: flex;
    gap: 10px;
    margin-top: 30px;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
}

.btn-primary {
    background: #007BFF;
    color: white;
}

.btn-secondary {
    background: #6C757D;
    color: white;
}

.btn:hover {
    opacity: 0.9;
}

.error-msg {
    background: #F8D7DA;
    color: #721C24;
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 20px;
}
</style>

<div class="edit-container">
    <h1>철근 제품 수정</h1>

    <div class="form-box">
        <?php if (isset($error)): ?>
        <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="name">제품명</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
            </div>

            <div class="form-group">
                <label for="diameter">직경 (mm)</label>
                <input type="number" id="diameter" name="diameter" value="<?php echo $product['diameter']; ?>" step="1" required>
            </div>

            <div class="form-group">
                <label for="weight_per_meter">단위중량 (kg/m)</label>
                <input type="number" id="weight_per_meter" name="weight_per_meter"
                       value="<?php echo $product['weight_per_meter']; ?>" step="0.001" required>
            </div>

            <div class="form-group">
                <label for="price_per_ton">톤당 가격 (원)</label>
                <input type="number" id="price_per_ton" name="price_per_ton"
                       value="<?php echo $product['price_per_ton']; ?>" step="1000">
            </div>

            <div class="form-group">
                <label for="is_active">상태</label>
                <select id="is_active" name="is_active">
                    <option value="1" <?php echo $product['is_active'] ? 'selected' : ''; ?>>활성</option>
                    <option value="0" <?php echo !$product['is_active'] ? 'selected' : ''; ?>>비활성</option>
                </select>
            </div>

            <div class="btn-group">
                <button type="submit" class="btn btn-primary">저장</button>
                <a href="admin_rebar_manage.php" class="btn btn-secondary">취소</a>
            </div>
        </form>
    </div>
</div>

<?php require_once 'admin_tail.php'; ?>