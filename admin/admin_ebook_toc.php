<?php
session_start();
require_once '../db.php';

// 관리자 권한 체크
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

$pdo = getDB();
$message = '';
$messageType = '';

// 삭제 처리
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM ebook_toc WHERE id = ?");
        $stmt->execute([$id]);
        $message = '목차 항목이 삭제되었습니다.';
        $messageType = 'success';
    } catch (PDOException $e) {
        $message = '삭제 중 오류가 발생했습니다: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// 활성화/비활성화 토글
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    try {
        $stmt = $pdo->prepare("UPDATE ebook_toc SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$id]);
        $message = '상태가 변경되었습니다.';
        $messageType = 'success';
    } catch (PDOException $e) {
        $message = '상태 변경 중 오류가 발생했습니다: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// 순서 변경 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order'])) {
    try {
        $pdo->beginTransaction();

        foreach ($_POST['order'] as $id => $order) {
            $stmt = $pdo->prepare("UPDATE ebook_toc SET display_order = ? WHERE id = ?");
            $stmt->execute([$order, $id]);
        }

        $pdo->commit();
        $message = '순서가 변경되었습니다.';
        $messageType = 'success';
    } catch (PDOException $e) {
        $pdo->rollBack();
        $message = '순서 변경 중 오류가 발생했습니다: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// 카테고리 목록 조회
$categories = $pdo->query("
    SELECT DISTINCT category, category_icon
    FROM ebook_toc
    ORDER BY MIN(display_order)
")->fetchAll(PDO::FETCH_ASSOC);

// 선택된 카테고리
$selectedCategory = $_GET['category'] ?? '';

// 목차 목록 조회
if ($selectedCategory) {
    $stmt = $pdo->prepare("
        SELECT *
        FROM ebook_toc
        WHERE category = ?
        ORDER BY display_order, id
    ");
    $stmt->execute([$selectedCategory]);
} else {
    $stmt = $pdo->query("
        SELECT *
        FROM ebook_toc
        ORDER BY display_order, id
    ");
}
$tocItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = '전자책 목차 관리';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - 충남스틸 관리자</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
            color: white;
            padding: 30px;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .header p {
            opacity: 0.9;
            font-size: 14px;
        }

        .toolbar {
            padding: 20px 30px;
            background: #f8f9fa;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .toolbar-left {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .category-filter {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            background: white;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #2196F3;
            color: white;
        }

        .btn-primary:hover {
            background: #1976D2;
        }

        .btn-success {
            background: #4CAF50;
            color: white;
        }

        .btn-success:hover {
            background: #45a049;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-danger {
            background: #f44336;
            color: white;
            font-size: 12px;
            padding: 6px 12px;
        }

        .btn-danger:hover {
            background: #d32f2f;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        .alert {
            padding: 15px 30px;
            margin: 0;
            border-left: 4px solid;
        }

        .alert-success {
            background: #e8f5e9;
            border-color: #4CAF50;
            color: #2e7d32;
        }

        .alert-error {
            background: #ffebee;
            border-color: #f44336;
            color: #c62828;
        }

        .content {
            padding: 30px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }

        .stat-card h3 {
            font-size: 32px;
            margin-bottom: 5px;
        }

        .stat-card p {
            font-size: 14px;
            opacity: 0.9;
        }

        .table-container {
            overflow-x: auto;
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
            color: #333;
            border-bottom: 2px solid #e0e0e0;
            font-size: 14px;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .category-badge {
            display: inline-block;
            padding: 6px 12px;
            background: #e3f2fd;
            color: #1976d2;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-active {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-inactive {
            background: #ffebee;
            color: #c62828;
        }

        .order-input {
            width: 60px;
            padding: 6px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
            font-size: 13px;
        }

        .actions {
            display: flex;
            gap: 8px;
        }

        .icon-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 18px;
            padding: 5px;
            transition: transform 0.2s;
        }

        .icon-btn:hover {
            transform: scale(1.2);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state svg {
            width: 80px;
            height: 80px;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        @media (max-width: 768px) {
            .toolbar {
                flex-direction: column;
                align-items: stretch;
            }

            .toolbar-left {
                flex-direction: column;
            }

            table {
                font-size: 12px;
            }

            th, td {
                padding: 10px 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📚 <?php echo $pageTitle; ?></h1>
            <p>전자책 모바일 페이지의 목차를 관리합니다</p>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <div class="toolbar">
            <div class="toolbar-left">
                <form method="get" style="display: flex; gap: 10px;">
                    <select name="category" class="category-filter" onchange="this.form.submit()">
                        <option value="">전체 카테고리</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat['category']); ?>"
                                <?php echo $selectedCategory === $cat['category'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['category_icon'] . ' ' . $cat['category']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <div style="display: flex; gap: 10px;">
                <a href="admin_ebook_toc_edit.php" class="btn btn-primary">➕ 새 목차 추가</a>
                <a href="/ebook/mobile/index.html" target="_blank" class="btn btn-secondary">👁️ 미리보기</a>
                <a href="admin_dashboard.php" class="btn btn-secondary">🏠 대시보드</a>
            </div>
        </div>

        <div class="content">
            <div class="stats">
                <?php
                $totalItems = $pdo->query("SELECT COUNT(*) FROM ebook_toc WHERE is_active = 1")->fetchColumn();
                $totalCategories = $pdo->query("SELECT COUNT(DISTINCT category) FROM ebook_toc")->fetchColumn();
                ?>
                <div class="stat-card">
                    <h3><?php echo $totalItems; ?></h3>
                    <p>활성 목차 항목</p>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <h3><?php echo $totalCategories; ?></h3>
                    <p>카테고리</p>
                </div>
            </div>

            <?php if (empty($tocItems)): ?>
            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                </svg>
                <h3>목차 항목이 없습니다</h3>
                <p>새 목차를 추가하여 전자책을 구성하세요.</p>
            </div>
            <?php else: ?>
            <form method="post">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 60px;">순서</th>
                                <th style="width: 150px;">카테고리</th>
                                <th>제목</th>
                                <th style="width: 80px;">페이지</th>
                                <th style="width: 80px;">상태</th>
                                <th style="width: 180px; text-align: center;">관리</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tocItems as $item): ?>
                            <tr>
                                <td>
                                    <input type="number"
                                           name="order[<?php echo $item['id']; ?>]"
                                           value="<?php echo $item['display_order']; ?>"
                                           class="order-input"
                                           min="0">
                                </td>
                                <td>
                                    <span class="category-badge">
                                        <?php echo htmlspecialchars($item['category_icon'] . ' ' . $item['category']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($item['title']); ?></td>
                                <td style="text-align: center;">
                                    <strong><?php echo $item['page_number']; ?></strong>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $item['is_active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $item['is_active'] ? '활성' : '비활성'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="actions" style="justify-content: center;">
                                        <a href="admin_ebook_toc_edit.php?id=<?php echo $item['id']; ?>"
                                           class="icon-btn" title="수정">✏️</a>
                                        <a href="?toggle=<?php echo $item['id']; ?><?php echo $selectedCategory ? '&category=' . urlencode($selectedCategory) : ''; ?>"
                                           class="icon-btn" title="활성화/비활성화"
                                           onclick="return confirm('상태를 변경하시겠습니까?')">
                                            <?php echo $item['is_active'] ? '👁️' : '🚫'; ?>
                                        </a>
                                        <a href="?delete=<?php echo $item['id']; ?><?php echo $selectedCategory ? '&category=' . urlencode($selectedCategory) : ''; ?>"
                                           class="icon-btn" title="삭제"
                                           onclick="return confirm('정말 삭제하시겠습니까?')">🗑️</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div style="margin-top: 20px; text-align: right;">
                    <button type="submit" name="update_order" class="btn btn-success">💾 순서 저장</button>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
