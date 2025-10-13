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
$isEdit = false;
$item = [
    'id' => 0,
    'category' => '',
    'category_icon' => '🟢',
    'title' => '',
    'page_number' => 1,
    'display_order' => 0,
    'is_active' => 1
];

// 수정 모드
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $isEdit = true;
    $stmt = $pdo->prepare("SELECT * FROM ebook_toc WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        header('Location: admin_ebook_toc.php');
        exit;
    }
}

// 폼 제출 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = trim($_POST['category'] ?? '');
    $categoryIcon = trim($_POST['category_icon'] ?? '🟢');
    $title = trim($_POST['title'] ?? '');
    $pageNumber = (int)($_POST['page_number'] ?? 1);
    $displayOrder = (int)($_POST['display_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    // 유효성 검사
    $errors = [];
    if (empty($category)) {
        $errors[] = '카테고리를 입력해주세요.';
    }
    if (empty($title)) {
        $errors[] = '제목을 입력해주세요.';
    }
    if ($pageNumber < 1) {
        $errors[] = '페이지 번호는 1 이상이어야 합니다.';
    }

    if (empty($errors)) {
        try {
            if ($isEdit) {
                // 수정
                $stmt = $pdo->prepare("
                    UPDATE ebook_toc
                    SET category = ?, category_icon = ?, title = ?, page_number = ?,
                        display_order = ?, is_active = ?
                    WHERE id = ?
                ");
                $stmt->execute([$category, $categoryIcon, $title, $pageNumber, $displayOrder, $isActive, $item['id']]);
                $message = '목차가 수정되었습니다.';
            } else {
                // 신규 등록
                // 자동으로 가장 큰 display_order + 1 설정
                if ($displayOrder === 0) {
                    $maxOrder = $pdo->query("SELECT MAX(display_order) FROM ebook_toc")->fetchColumn();
                    $displayOrder = $maxOrder + 1;
                }

                $stmt = $pdo->prepare("
                    INSERT INTO ebook_toc (category, category_icon, title, page_number, display_order, is_active)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$category, $categoryIcon, $title, $pageNumber, $displayOrder, $isActive]);
                $message = '목차가 추가되었습니다.';
            }

            $messageType = 'success';

            // 성공 시 목록으로 리다이렉트
            header('Location: admin_ebook_toc.php?message=' . urlencode($message));
            exit;
        } catch (PDOException $e) {
            $message = '저장 중 오류가 발생했습니다: ' . $e->getMessage();
            $messageType = 'error';
        }
    } else {
        $message = implode('<br>', $errors);
        $messageType = 'error';
    }
}

// 기존 카테고리 목록
$categories = $pdo->query("
    SELECT DISTINCT category, category_icon
    FROM ebook_toc
    ORDER BY category
")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = $isEdit ? '목차 수정' : '목차 추가';
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
            max-width: 900px;
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

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-group label .required {
            color: #f44336;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #2196F3;
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-help {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .checkbox-group label {
            margin-bottom: 0 !important;
            cursor: pointer;
        }

        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            text-align: center;
        }

        .btn-primary {
            background: #2196F3;
            color: white;
            flex: 1;
        }

        .btn-primary:hover {
            background: #1976D2;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .category-suggestions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }

        .category-tag {
            padding: 6px 12px;
            background: #e3f2fd;
            color: #1976d2;
            border-radius: 16px;
            font-size: 12px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .category-tag:hover {
            background: #bbdefb;
        }

        .icon-picker {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }

        .icon-option {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .icon-option:hover {
            border-color: #2196F3;
            transform: scale(1.1);
        }

        .icon-option.selected {
            border-color: #2196F3;
            background: #e3f2fd;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .btn-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php echo $isEdit ? '✏️' : '➕'; ?> <?php echo $pageTitle; ?></h1>
            <p><?php echo $isEdit ? '목차 정보를 수정합니다' : '새로운 목차를 추가합니다'; ?></p>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <div class="content">
            <form method="post">
                <div class="form-group">
                    <label>카테고리 <span class="required">*</span></label>
                    <input type="text"
                           name="category"
                           class="form-control"
                           value="<?php echo htmlspecialchars($item['category']); ?>"
                           placeholder="예: 철근류, 형강류"
                           list="category-list"
                           required>
                    <datalist id="category-list">
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat['category']); ?>">
                        <?php endforeach; ?>
                    </datalist>
                    <div class="form-help">기존 카테고리를 선택하거나 새로운 카테고리를 입력하세요</div>

                    <?php if (!empty($categories)): ?>
                    <div class="category-suggestions">
                        <?php foreach ($categories as $cat): ?>
                        <span class="category-tag" onclick="document.querySelector('[name=category]').value='<?php echo htmlspecialchars($cat['category']); ?>'">
                            <?php echo htmlspecialchars($cat['category_icon'] . ' ' . $cat['category']); ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label>카테고리 아이콘</label>
                    <input type="text"
                           name="category_icon"
                           id="category_icon"
                           class="form-control"
                           value="<?php echo htmlspecialchars($item['category_icon']); ?>"
                           placeholder="🟢"
                           maxlength="10">
                    <div class="form-help">카테고리를 구분할 아이콘을 선택하세요</div>
                    <div class="icon-picker">
                        <?php
                        $icons = ['🟢', '🔵', '🟡', '🔴', '🟣', '🟤', '⚫', '⚪', '🟠', '📌', '📍', '🔸', '🔹', '⭐', '💎'];
                        foreach ($icons as $icon):
                        ?>
                        <span class="icon-option <?php echo $item['category_icon'] === $icon ? 'selected' : ''; ?>"
                              onclick="selectIcon(this, '<?php echo $icon; ?>')"><?php echo $icon; ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label>목차 제목 <span class="required">*</span></label>
                    <input type="text"
                           name="title"
                           class="form-control"
                           value="<?php echo htmlspecialchars($item['title']); ?>"
                           placeholder="예: H형강 ................................. 21"
                           required>
                    <div class="form-help">전자책에 표시될 목차 제목을 입력하세요</div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>페이지 번호 <span class="required">*</span></label>
                        <input type="number"
                               name="page_number"
                               class="form-control"
                               value="<?php echo $item['page_number']; ?>"
                               min="1"
                               placeholder="1"
                               required>
                        <div class="form-help">이동할 페이지 번호</div>
                    </div>

                    <div class="form-group">
                        <label>표시 순서</label>
                        <input type="number"
                               name="display_order"
                               class="form-control"
                               value="<?php echo $item['display_order']; ?>"
                               min="0"
                               placeholder="0">
                        <div class="form-help">0이면 자동으로 마지막에 추가</div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox"
                               name="is_active"
                               id="is_active"
                               value="1"
                               <?php echo $item['is_active'] ? 'checked' : ''; ?>>
                        <label for="is_active">활성화 (체크 시 전자책에 표시됨)</label>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        💾 <?php echo $isEdit ? '수정' : '추가'; ?>하기
                    </button>
                    <a href="admin_ebook_toc.php" class="btn btn-secondary">취소</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function selectIcon(element, icon) {
            // 모든 아이콘에서 selected 클래스 제거
            document.querySelectorAll('.icon-option').forEach(el => {
                el.classList.remove('selected');
            });

            // 선택된 아이콘에 selected 클래스 추가
            element.classList.add('selected');

            // 입력 필드에 값 설정
            document.getElementById('category_icon').value = icon;
        }
    </script>
</body>
</html>
