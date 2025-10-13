<?php
session_start();
require_once '../db.php';
require_once 'admin_check.php';

// 현재 파일명
$currentFile = basename($_SERVER['PHP_SELF']);

function parse_category_code_from_url($url, $pdo) {
    $parts = @parse_url($url);
    if (!$parts) return [null, 'URL을 파싱할 수 없습니다.'];
    $query = [];
    if (isset($parts['query'])) parse_str($parts['query'], $query);

    if (!empty($query['category'])) {
        return [$query['category'], null];
    }
    if (!empty($query['id'])) {
        $id = (int)$query['id'];
        $stmt = $pdo->prepare("SELECT category_code FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row && !empty($row['category_code'])) {
            return [$row['category_code'], null];
        } else {
            return [null, '해당 ID의 제품을 찾을 수 없습니다.'];
        }
    }
    return [null, 'category 또는 id 파라미터가 필요합니다.'];
}

// 업로드 및 압축 유틸
function compress_image_to_target(string $path, string $mime, int $targetSizeBytes): void {
    if (!extension_loaded('gd')) return;
    $info = @getimagesize($path);
    if ($info === false) return;
    list($width, $height) = $info;
    $src = null;
    switch ($mime) {
        case 'image/jpeg':
        case 'image/jpg': $src = @imagecreatefromjpeg($path); break;
        case 'image/png': $src = @imagecreatefrompng($path); break;
        case 'image/gif': $src = @imagecreatefromgif($path); break;
        case 'image/webp': if (function_exists('imagecreatefromwebp')) $src = @imagecreatefromwebp($path); break;
    }
    if (!$src) return;
    // 1) 최대변 1600px 축소
    $maxDim = 1600;
    $scale = min(1.0, $maxDim / max($width, $height));
    $newW = (int)max(1, round($width * $scale));
    $newH = (int)max(1, round($height * $scale));
    $dst = imagecreatetruecolor($newW, $newH);
    if ($mime === 'image/png' || $mime === 'image/gif') {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefilledrectangle($dst, 0, 0, $newW, $newH, $transparent);
    }
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $width, $height);
    $tmpPath = $path . '.tmp';
    @unlink($tmpPath);
    $ok = false;
    if ($mime === 'image/jpeg' || $mime === 'image/jpg') {
        foreach ([85,80,75,70,65,60,55,50,45,40] as $q) {
            @imagejpeg($dst, $tmpPath, $q);
            if (file_exists($tmpPath) && filesize($tmpPath) <= $targetSizeBytes) { $ok = true; break; }
        }
    } elseif ($mime === 'image/webp' && function_exists('imagewebp')) {
        foreach ([80,75,70,65,60,55,50,45,40] as $q) {
            @imagewebp($dst, $tmpPath, $q);
            if (file_exists($tmpPath) && filesize($tmpPath) <= $targetSizeBytes) { $ok = true; break; }
        }
    } elseif ($mime === 'image/png') {
        @imagepng($dst, $tmpPath, 9);
        if (file_exists($tmpPath) && filesize($tmpPath) <= $targetSizeBytes) { $ok = true; }
        if (!$ok) {
            $maxDim2 = 1000;
            $scale2 = min(1.0, $maxDim2 / max($newW, $newH));
            $w2 = (int)max(1, round($newW * $scale2));
            $h2 = (int)max(1, round($newH * $scale2));
            $dst2 = imagecreatetruecolor($w2, $h2);
            imagealphablending($dst2, false);
            imagesavealpha($dst2, true);
            $transparent2 = imagecolorallocatealpha($dst2, 0, 0, 0, 127);
            imagefilledrectangle($dst2, 0, 0, $w2, $h2, $transparent2);
            imagecopyresampled($dst2, $dst, 0, 0, 0, 0, $w2, $h2, $newW, $newH);
            @imagepng($dst2, $tmpPath, 9);
            imagedestroy($dst2);
            if (file_exists($tmpPath) && filesize($tmpPath) <= $targetSizeBytes) { $ok = true; }
        }
    } elseif ($mime === 'image/gif') {
        @imagegif($dst, $tmpPath);
        if (file_exists($tmpPath) && filesize($tmpPath) <= $targetSizeBytes) { $ok = true; }
    }
    if (file_exists($tmpPath)) {
        $orig = @filesize($path) ?: PHP_INT_MAX;
        $cand = @filesize($tmpPath) ?: PHP_INT_MAX;
        if ($ok || $cand < $orig) {
            @rename($tmpPath, $path);
        } else {
            @unlink($tmpPath);
        }
    }
    imagedestroy($dst);
    imagedestroy($src);
}

$message = '';
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_url = trim($_POST['group_url'] ?? '');
    $only_empty = isset($_POST['only_empty']);
    $apply_all = isset($_POST['apply_all']);

    if (!$group_url) {
        $message = '제품군 URL을 입력하세요.';
    } elseif (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $message = '이미지 파일을 선택하세요.';
    } else {
        list($category_code, $err) = parse_category_code_from_url($group_url, $pdo);
        if ($err) {
            $message = $err;
        } else {
            // 업로드 처리
            $file = $_FILES['image'];
            $allowed = ['image/jpeg','image/jpg','image/png','image/gif','image/webp'];
            if (!in_array($file['type'], $allowed)) {
                $message = 'JPG, PNG, GIF, WEBP 형식만 허용됩니다.';
            } elseif ($file['size'] > 25 * 1024 * 1024) {
                $message = '파일 크기는 25MB 이하여야 합니다.';
            } else {
                $upload_dir = dirname(__DIR__) . '/uploads/products/';
                if (!is_dir($upload_dir)) @mkdir($upload_dir, 0777, true);
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $filename = 'bulk_' . preg_replace('/[^a-z0-9\-_.]/i', '-', $category_code) . '_' . time() . '.' . $ext;
                $path = $upload_dir . $filename;
                $web_path = '/uploads/products/' . $filename;
                if (!move_uploaded_file($file['tmp_name'], $path)) {
                    $message = '파일 저장에 실패했습니다.';
                } else {
                    // 2MB 초과 시 600KB 목표로 압축
                    if (filesize($path) >= 2 * 1024 * 1024) {
                        $info = @getimagesize($path);
                        if ($info && !empty($info['mime'])) {
                            compress_image_to_target($path, $info['mime'], 600 * 1024);
                        }
                    }
                    // 적용 대상 카운트
                    if ($only_empty && !$apply_all) {
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_code = ? AND (main_image IS NULL OR main_image = '')");
                        $stmt->execute([$category_code]);
                    } else {
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_code = ?");
                        $stmt->execute([$category_code]);
                    }
                    $target_count = (int)$stmt->fetchColumn();

                    // 업데이트 실행
                    if ($only_empty && !$apply_all) {
                        $stmt = $pdo->prepare("UPDATE products SET main_image = ?, updated_at = NOW() WHERE category_code = ? AND (main_image IS NULL OR main_image = '')");
                        $stmt->execute([$web_path, $category_code]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE products SET main_image = ?, updated_at = NOW() WHERE category_code = ?");
                        $stmt->execute([$web_path, $category_code]);
                    }
                    $affected = $stmt->rowCount();

                    $result = [
                        'category_code' => $category_code,
                        'image_url' => $web_path,
                        'target_count' => $target_count,
                        'affected' => $affected,
                    ];
                    $message = '적용이 완료되었습니다.';
                }
            }
        }
    }
}
?>
<?php include 'admin_head.php'; ?>
<style>
.bulk-card { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); max-width: 820px; margin: 0 auto; }
.form-row { display:flex; gap:16px; align-items:flex-start; }
.form-row .col { flex: 1; }
.form-group { margin-bottom: 16px; }
.form-group label { display:block; font-weight:600; margin-bottom: 6px; }
.form-group input[type="text"], .form-group input[type="file"] { width:100%; padding:10px 12px; border:1px solid #ddd; border-radius:6px; }
.hint { color:#6c757d; font-size:12px; }
.msg { margin-bottom: 12px; padding:10px 12px; border-radius:6px; }
.msg.ok { background:#d4edda; color:#155724; }
.msg.err { background:#f8d7da; color:#721c24; }
.preview { border:1px dashed #ccc; border-radius:8px; padding:10px; display:flex; align-items:center; gap:12px; }
.preview img { max-width:220px; max-height:160px; display:block; }
.actions { display:flex; gap:8px; justify-content:flex-end; }
.btn { padding:10px 16px; border-radius:6px; border:none; cursor:pointer; font-weight:600; }
.btn-primary { background:#007bff; color:#fff; }
.btn-secondary { background:#f1f3f5; }
</style>

<div class="content" style="padding: 20px;">
    <h2 style="margin-bottom:16px;">제품 이미지 일괄 등록</h2>
    <div class="bulk-card">
        <?php if ($message): ?>
            <div class="msg <?php echo $result ? 'ok' : 'err'; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($result): ?>
            <div class="preview" style="margin-bottom:16px;">
                <img src="<?php echo htmlspecialchars($result['image_url']); ?>" alt="미리보기">
                <div>
                    <div><strong>카테고리</strong>: <?php echo htmlspecialchars($result['category_code']); ?></div>
                    <div><strong>대상</strong>: <?php echo number_format($result['target_count']); ?>개</div>
                    <div><strong>적용됨</strong>: <?php echo number_format($result['affected']); ?>개</div>
                </div>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label>제품군 URL</label>
                <input type="text" name="group_url" placeholder="예) /products_new.php?category=angle 또는 /product_detail.php?category=rebar" value="<?php echo htmlspecialchars($_POST['group_url'] ?? ''); ?>">
                <div class="hint">URL의 query에서 category 또는 id를 읽어 카테고리를 판별합니다. id만 주면 해당 제품의 카테고리 전체에 적용됩니다.</div>
            </div>
            <div class="form-row">
                <div class="col">
                    <div class="form-group">
                        <label>대표 이미지(=목록 썸네일)</label>
                        <input type="file" name="image" accept="image/*">
                        <div class="hint">JPG/PNG/GIF/WebP, 최대 25MB. 2MB 초과 시 약 600KB로 자동 압축됩니다.</div>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label><input type="checkbox" name="only_empty" <?php echo isset($_POST['only_empty']) ? 'checked' : 'checked'; ?>> 이미지가 없는 제품에만 적용</label>
                <div class="hint">기본값 권장(초기 세팅용). 체크 해제 시 해당 카테고리의 모든 제품 이미지를 덮어씁니다.</div>
            </div>
            <div class="actions">
                <button type="submit" class="btn btn-primary">일괄 적용</button>
            </div>
        </form>
    </div>

    <!-- 제품 설명 이미지 일괄 등록 섹션 -->
    <h2 style="margin: 40px 0 16px 0;">제품 설명 이미지 일괄 등록</h2>
    <div class="bulk-card">
        <?php
        // 제품 설명 이미지 일괄 등록 처리
        $desc_message = '';
        $desc_result = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_description_images'])) {
            $desc_image_url = trim($_POST['description_image_url'] ?? '');
            $apply_to_all = isset($_POST['apply_to_all']);

            // 이미지 파일 업로드 처리
            if (isset($_FILES['description_image']) && $_FILES['description_image']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['description_image'];
                $allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                $mime = mime_content_type($file['tmp_name']);

                if (!in_array($mime, $allowed)) {
                    $desc_message = '지원하지 않는 이미지 형식입니다.';
                } else {
                    $upload_dir = '../uploads/products/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }

                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = 'desc_' . time() . '_' . uniqid() . '.' . $ext;
                    $upload_path = $upload_dir . $filename;

                    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                        // 2MB 초과 시 압축
                        if (filesize($upload_path) > 2 * 1024 * 1024) {
                            compress_image_to_target($upload_path, $mime, 600 * 1024);
                        }
                        $desc_image_url = '/uploads/products/' . $filename;
                    } else {
                        $desc_message = '파일 업로드에 실패했습니다.';
                    }
                }
            }

            if (empty($desc_image_url)) {
                $desc_message = $desc_message ?: '이미지 파일을 업로드하거나 URL을 입력해주세요.';
            } else {
                try {
                    // 모든 제품 조회
                    $products_query = "SELECT id, product_name, description FROM products WHERE is_active = 1";
                    $stmt = $pdo->query($products_query);
                    $all_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    $updated_count = 0;

                    foreach ($all_products as $product) {
                        $current_desc = $product['description'] ?? '';

                        // 이미 이미지가 있는지 확인
                        $has_image = preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $current_desc);

                        // 모든 제품에 적용 또는 이미지가 없는 경우만
                        if ($apply_to_all || !$has_image) {
                            // 기존 설명이 있으면 유지하고 이미지 URL 추가
                            $new_desc = trim($current_desc);
                            if (!empty($new_desc)) {
                                $new_desc .= "\n";
                            }
                            $new_desc .= $desc_image_url;

                            $update_stmt = $pdo->prepare("UPDATE products SET description = ? WHERE id = ?");
                            $update_stmt->execute([$new_desc, $product['id']]);
                            $updated_count++;
                        }
                    }

                    $desc_result = [
                        'image_url' => $desc_image_url,
                        'total_count' => count($all_products),
                        'updated_count' => $updated_count
                    ];
                    $desc_message = "총 {$updated_count}개 제품의 설명에 이미지가 추가되었습니다.";

                } catch (Exception $e) {
                    $desc_message = '오류: ' . $e->getMessage();
                }
            }
        }
        ?>

        <?php if ($desc_message): ?>
            <div class="msg <?php echo $desc_result ? 'ok' : 'err'; ?>"><?php echo htmlspecialchars($desc_message); ?></div>
        <?php endif; ?>

        <?php if ($desc_result): ?>
            <div class="preview" style="margin-bottom:16px;">
                <img src="<?php echo htmlspecialchars($desc_result['image_url']); ?>" alt="미리보기">
                <div>
                    <div><strong>전체 제품</strong>: <?php echo number_format($desc_result['total_count']); ?>개</div>
                    <div><strong>업데이트됨</strong>: <?php echo number_format($desc_result['updated_count']); ?>개</div>
                </div>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <div class="form-row">
                <div class="col">
                    <div class="form-group">
                        <label>제품 설명 이미지 업로드</label>
                        <input type="file" name="description_image" accept="image/*">
                        <div class="hint">JPG/PNG/GIF/WebP, 최대 25MB. 2MB 초과 시 약 600KB로 자동 압축됩니다.</div>
                    </div>
                </div>
            </div>

            <div class="form-group" style="margin-top: 10px;">
                <label>또는 이미지 URL 직접 입력</label>
                <input type="text" name="description_image_url" placeholder="예) /uploads/products/description_image.jpg" value="<?php echo htmlspecialchars($_POST['description_image_url'] ?? ''); ?>">
                <div class="hint">파일 업로드 또는 URL 입력 중 하나만 사용하세요. 파일 업로드 시 URL은 무시됩니다.</div>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="apply_to_all" <?php echo isset($_POST['apply_to_all']) ? 'checked' : ''; ?>>
                    이미 이미지가 있는 제품도 덮어쓰기
                </label>
                <div class="hint">체크 해제 시 이미지가 없는 제품에만 추가됩니다 (권장).</div>
            </div>

            <div class="actions">
                <button type="submit" name="apply_description_images" class="btn btn-primary">모든 제품에 일괄 적용</button>
            </div>
        </form>
    </div>
</div>

<?php include 'admin_foot.php'; ?>

