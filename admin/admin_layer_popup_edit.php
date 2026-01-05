<?php
require_once 'admin_check.php';
require_once '../db.php';

$isEdit = false;
$popup = null;

// 수정 모드인 경우 기존 데이터 가져오기
if (isset($_GET['id'])) {
    $isEdit = true;
    $stmt = $pdo->prepare("SELECT * FROM layer_popups WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $popup = $stmt->fetch();

    if (!$popup) {
        $_SESSION['message'] = '팝업을 찾을 수 없습니다.';
        $_SESSION['message_type'] = 'danger';
        header('Location: admin_layer_popups.php');
        exit;
    }
}

$pageTitle = $isEdit ? '레이어팝업 수정' : '레이어팝업 추가';

// 추가 스타일
$additionalStyles = '
/* 에디터 모드 토글 */
.editor-mode-toggle {
    display: flex;
    gap: 0;
    margin-bottom: 12px;
    background: #f5f5f5;
    border-radius: 8px;
    padding: 4px;
    width: fit-content;
}

.editor-mode-btn {
    padding: 8px 20px;
    border: none;
    background: transparent;
    color: #666;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    border-radius: 6px;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 6px;
}

.editor-mode-btn:hover {
    color: #333;
}

.editor-mode-btn.active {
    background: #1A237E;
    color: white;
}

.editor-mode-btn i {
    font-size: 12px;
}

/* 에디터 컨테이너 */
.editor-container {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    overflow: hidden;
}

.html-editor-container {
    display: none;
}

.html-editor-container.show {
    display: block;
}

.visual-editor-container {
    display: block;
}

.visual-editor-container.hide {
    display: none;
}

/* HTML 코드 에디터 스타일 */
.html-code-editor {
    width: 100%;
    min-height: 400px;
    padding: 16px;
    border: none;
    font-family: "Consolas", "Monaco", "Courier New", monospace;
    font-size: 14px;
    line-height: 1.6;
    background: #1e1e1e;
    color: #d4d4d4;
    resize: vertical;
}

.html-code-editor:focus {
    outline: none;
}

/* HTML 미리보기 */
.html-preview-panel {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    margin-top: 16px;
    max-height: 300px;
    overflow: auto;
}

.html-preview-title {
    font-size: 12px;
    color: #666;
    margin-bottom: 12px;
    padding-bottom: 8px;
    border-bottom: 1px solid #e0e0e0;
}

/* TinyMCE 커스텀 스타일 */
.tox-tinymce {
    border-radius: 8px !important;
}

.form-container {
    background: white;
    padding: 32px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    max-width: 900px;
    margin: 0 auto;
}

.form-group {
    margin-bottom: 24px;
}

.form-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
}

.form-control {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    font-size: 16px;
    transition: border-color 0.2s;
}

.form-control:focus {
    outline: none;
    border-color: #1A237E;
}

textarea.form-control {
    resize: vertical;
    min-height: 150px;
}

.form-help {
    font-size: 14px;
    color: #666;
    margin-top: 4px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-row-4 {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

@media (max-width: 768px) {
    .form-row, .form-row-4 {
        grid-template-columns: 1fr;
    }
}

.image-preview {
    margin-top: 16px;
    max-width: 400px;
}

.image-preview img {
    width: 100%;
    height: auto;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.form-check {
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-check input[type="checkbox"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
}

.form-actions {
    display: flex;
    gap: 12px;
    margin-top: 32px;
    padding-top: 24px;
    border-top: 1px solid #e0e0e0;
}

.file-input-wrapper {
    position: relative;
    display: inline-block;
    cursor: pointer;
    width: 100%;
}

.file-input-wrapper input[type="file"] {
    position: absolute;
    opacity: 0;
    width: 100%;
    height: 100%;
    cursor: pointer;
}

.file-input-label {
    display: block;
    padding: 12px 16px;
    border: 2px dashed #e0e0e0;
    border-radius: 8px;
    text-align: center;
    color: #666;
    transition: all 0.2s;
}

.file-input-wrapper:hover .file-input-label {
    border-color: #1A237E;
    color: #1A237E;
}

.current-image {
    margin-bottom: 16px;
}

.current-image img {
    max-width: 300px;
    height: auto;
    border-radius: 8px;
}

.btn {
    display: inline-flex;
    align-items: center;
    padding: 12px 24px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    font-size: 16px;
    transition: all 0.3s;
    cursor: pointer;
    border: none;
}

.btn i {
    margin-right: 8px;
}

.btn-primary {
    background: #1A237E;
    color: white;
}

.btn-primary:hover {
    background: #303F9F;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(26, 35, 126, 0.3);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
}

.section-title {
    font-size: 18px;
    font-weight: 600;
    color: #1A237E;
    margin-bottom: 16px;
    padding-bottom: 8px;
    border-bottom: 2px solid #e3f2fd;
}

.preview-box {
    background: #f5f5f5;
    border-radius: 8px;
    padding: 20px;
    margin-top: 16px;
    position: relative;
    min-height: 200px;
    overflow: hidden;
}

.preview-popup-sample {
    position: absolute;
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
    transition: all 0.3s;
}

.preview-popup-header {
    background: #1A237E;
    color: white;
    padding: 8px 12px;
    font-size: 12px;
    border-radius: 8px 8px 0 0;
}

.preview-popup-body {
    padding: 12px;
    font-size: 11px;
    color: #666;
}
';

// 추가 스크립트
$additionalScripts = '
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();

        reader.onload = function(e) {
            const preview = document.getElementById("image-preview");
            preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
            preview.style.display = "block";
        }

        reader.readAsDataURL(input.files[0]);

        // 파일명 표시
        const fileName = input.files[0].name;
        document.querySelector(".file-input-label").innerHTML = `<i class="fas fa-check"></i> ${fileName}`;
    }
}

function updatePositionPreview() {
    const top = parseInt(document.getElementById("position_top").value) || 100;
    const left = parseInt(document.getElementById("position_left").value) || 100;
    const width = parseInt(document.getElementById("width").value) || 400;
    const height = parseInt(document.getElementById("height").value) || 500;

    const previewPopup = document.getElementById("previewPopupSample");
    if (previewPopup) {
        // 미리보기 박스 내에서 비율 조정
        const scale = 0.15;
        previewPopup.style.top = (top * scale) + "px";
        previewPopup.style.left = (left * scale) + "px";
        previewPopup.style.width = (width * scale) + "px";
        previewPopup.style.height = (height * scale) + "px";
    }
}

document.addEventListener("DOMContentLoaded", function() {
    // 위치/크기 입력 필드에 이벤트 리스너 추가
    ["position_top", "position_left", "width", "height"].forEach(function(id) {
        const input = document.getElementById(id);
        if (input) {
            input.addEventListener("input", updatePositionPreview);
        }
    });

    updatePositionPreview();
});
';

require_once 'admin_head.php';
?>

<div class="container">
    <div class="content-header">
        <h1><?php echo $pageTitle; ?></h1>
    </div>

    <form action="admin_layer_popup_action.php" method="POST" enctype="multipart/form-data" class="form-container">
        <?php if ($isEdit): ?>
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" value="<?php echo $popup['id']; ?>">
        <?php else: ?>
        <input type="hidden" name="action" value="create">
        <?php endif; ?>

        <!-- 기본 정보 -->
        <h3 class="section-title"><i class="fas fa-info-circle"></i> 기본 정보</h3>

        <div class="form-group">
            <label for="title" class="form-label">팝업 제목 *</label>
            <input type="text" id="title" name="title" class="form-control"
                   value="<?php echo $isEdit ? htmlspecialchars($popup['title']) : ''; ?>" required>
            <div class="form-help">관리 목적으로 사용되는 제목입니다. 방문자에게는 표시되지 않습니다.</div>
        </div>

        <!-- 팝업 내용 -->
        <h3 class="section-title"><i class="fas fa-image"></i> 팝업 내용</h3>

        <div class="form-group">
            <label for="image" class="form-label">팝업 이미지</label>

            <?php if ($isEdit && $popup['image_path']): ?>
            <div class="current-image">
                <p style="margin-bottom: 8px; font-weight: 500;">현재 이미지:</p>
                <img src="../uploads/popups/<?php echo htmlspecialchars($popup['image_path']); ?>"
                     alt="Current popup">
            </div>
            <?php endif; ?>

            <div class="file-input-wrapper">
                <input type="file" id="image" name="image" accept="image/*" onchange="previewImage(this)">
                <label for="image" class="file-input-label">
                    <i class="fas fa-upload"></i> 이미지 선택 (JPG, PNG, GIF)
                </label>
            </div>
            <div class="form-help">권장 크기: 팝업 너비에 맞춰 이미지를 업로드하세요.</div>
            <div id="image-preview" class="image-preview" style="display: none;"></div>
        </div>

        <div class="form-group">
            <label for="content" class="form-label">팝업 내용</label>

            <!-- 에디터 모드 토글 -->
            <div class="editor-mode-toggle">
                <button type="button" class="editor-mode-btn active" id="visualModeBtn" onclick="switchEditorMode('visual')">
                    <i class="fas fa-eye"></i> 비주얼 모드
                </button>
                <button type="button" class="editor-mode-btn" id="htmlModeBtn" onclick="switchEditorMode('html')">
                    <i class="fas fa-code"></i> HTML 모드
                </button>
            </div>

            <!-- 비주얼 에디터 (TinyMCE) -->
            <div class="visual-editor-container" id="visualEditorContainer">
                <textarea id="content" name="content"><?php echo $isEdit ? htmlspecialchars($popup['content'] ?? '') : ''; ?></textarea>
            </div>

            <!-- HTML 코드 에디터 -->
            <div class="html-editor-container" id="htmlEditorContainer">
                <textarea class="html-code-editor" id="htmlCodeEditor"><?php echo $isEdit ? htmlspecialchars($popup['content'] ?? '') : ''; ?></textarea>
                <div class="html-preview-panel" id="htmlPreviewPanel">
                    <div class="html-preview-title"><i class="fas fa-desktop"></i> 미리보기</div>
                    <div id="htmlPreviewContent"></div>
                </div>
            </div>

            <div class="form-help" style="margin-top: 12px;">
                <i class="fas fa-info-circle"></i> 비주얼 모드에서는 직접 서식을 지정하고, HTML 모드에서는 HTML 코드를 직접 편집할 수 있습니다.
            </div>
        </div>

        <!-- 링크 설정 -->
        <h3 class="section-title"><i class="fas fa-link"></i> 링크 설정</h3>

        <div class="form-row">
            <div class="form-group">
                <label for="link_url" class="form-label">링크 URL</label>
                <input type="url" id="link_url" name="link_url" class="form-control"
                       placeholder="https://example.com"
                       value="<?php echo $isEdit ? htmlspecialchars($popup['link_url'] ?? '') : ''; ?>">
                <div class="form-help">팝업 클릭 시 이동할 URL입니다.</div>
            </div>

            <div class="form-group">
                <label for="link_target" class="form-label">링크 타겟</label>
                <select id="link_target" name="link_target" class="form-control">
                    <option value="_self" <?php echo ($isEdit && $popup['link_target'] == '_self') ? 'selected' : ''; ?>>
                        현재 창에서 열기
                    </option>
                    <option value="_blank" <?php echo ($isEdit && $popup['link_target'] == '_blank') ? 'selected' : ''; ?>>
                        새 창에서 열기
                    </option>
                </select>
            </div>
        </div>

        <!-- 위치 및 크기 -->
        <h3 class="section-title"><i class="fas fa-expand-arrows-alt"></i> 위치 및 크기</h3>

        <div class="form-row-4">
            <div class="form-group">
                <label for="position_left" class="form-label">좌측 위치 (px)</label>
                <input type="number" id="position_left" name="position_left" class="form-control"
                       value="<?php echo $isEdit ? $popup['position_left'] : '100'; ?>" min="0">
            </div>

            <div class="form-group">
                <label for="position_top" class="form-label">상단 위치 (px)</label>
                <input type="number" id="position_top" name="position_top" class="form-control"
                       value="<?php echo $isEdit ? $popup['position_top'] : '100'; ?>" min="0">
            </div>

            <div class="form-group">
                <label for="width" class="form-label">너비 (px)</label>
                <input type="number" id="width" name="width" class="form-control"
                       value="<?php echo $isEdit ? $popup['width'] : '400'; ?>" min="200" max="800">
            </div>

            <div class="form-group">
                <label for="height" class="form-label">높이 (px)</label>
                <input type="number" id="height" name="height" class="form-control"
                       value="<?php echo $isEdit ? $popup['height'] : '500'; ?>" min="200" max="800">
            </div>
        </div>

        <div class="form-help" style="margin-top: -16px; margin-bottom: 24px;">
            <i class="fas fa-info-circle"></i> 팝업이 화면에 표시될 위치와 크기를 설정합니다. 여러 개의 팝업이 있을 경우 서로 겹치지 않도록 위치를 조정하세요.
        </div>

        <div class="preview-box">
            <div style="font-size: 12px; color: #666; margin-bottom: 8px;">위치 미리보기 (축소)</div>
            <div id="previewPopupSample" class="preview-popup-sample">
                <div class="preview-popup-header">팝업</div>
                <div class="preview-popup-body">미리보기</div>
            </div>
        </div>

        <!-- 노출 기간 -->
        <h3 class="section-title" style="margin-top: 32px;"><i class="fas fa-calendar-alt"></i> 노출 기간</h3>

        <div class="form-row">
            <div class="form-group">
                <label for="start_date" class="form-label">시작일</label>
                <input type="date" id="start_date" name="start_date" class="form-control"
                       value="<?php echo $isEdit && $popup['start_date'] ? $popup['start_date'] : ''; ?>">
                <div class="form-help">비워두면 즉시 노출됩니다.</div>
            </div>

            <div class="form-group">
                <label for="end_date" class="form-label">종료일</label>
                <input type="date" id="end_date" name="end_date" class="form-control"
                       value="<?php echo $isEdit && $popup['end_date'] ? $popup['end_date'] : ''; ?>">
                <div class="form-help">비워두면 무기한 노출됩니다.</div>
            </div>
        </div>

        <!-- 옵션 -->
        <h3 class="section-title"><i class="fas fa-cog"></i> 옵션</h3>

        <div class="form-group">
            <label for="display_order" class="form-label">표시 순서</label>
            <input type="number" id="display_order" name="display_order" class="form-control"
                   value="<?php echo $isEdit ? $popup['display_order'] : '0'; ?>" min="0" style="max-width: 200px;">
            <div class="form-help">낮은 숫자가 먼저 표시됩니다. 여러 팝업의 z-index에도 영향을 줍니다.</div>
        </div>

        <div class="form-group">
            <div class="form-check">
                <input type="checkbox" id="hide_today" name="hide_today" value="1"
                       <?php echo (!$isEdit || $popup['hide_today']) ? 'checked' : ''; ?>>
                <label for="hide_today">오늘 하루 보지 않기 버튼 표시</label>
            </div>
            <div class="form-help">체크 시 방문자가 24시간 동안 팝업을 숨길 수 있습니다.</div>
        </div>

        <div class="form-group">
            <div class="form-check">
                <input type="checkbox" id="is_active" name="is_active" value="1"
                       <?php echo (!$isEdit || $popup['is_active']) ? 'checked' : ''; ?>>
                <label for="is_active">활성화</label>
            </div>
            <div class="form-help">체크 해제 시 팝업이 표시되지 않습니다.</div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> <?php echo $isEdit ? '수정' : '등록'; ?>
            </button>
            <a href="admin_layer_popups.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> 취소
            </a>
        </div>
    </form>
</div>

<!-- TinyMCE CDN -->
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
// 현재 에디터 모드
let currentEditorMode = 'visual';
let tinymceEditor = null;

// TinyMCE 초기화
document.addEventListener('DOMContentLoaded', function() {
    initTinyMCE();
});

function initTinyMCE() {
    tinymce.init({
        selector: '#content',
        height: 400,
        language: 'ko_KR',
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'help', 'wordcount', 'emoticons'
        ],
        toolbar: 'undo redo | blocks | ' +
            'bold italic forecolor backcolor | alignleft aligncenter ' +
            'alignright alignjustify | bullist numlist outdent indent | ' +
            'link image table emoticons | removeformat code | help',
        menubar: 'file edit view insert format tools table help',
        content_style: `
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                font-size: 16px;
                line-height: 1.6;
                color: #333;
                padding: 10px;
            }
            img { max-width: 100%; height: auto; }
            a { color: #1A237E; }
        `,
        branding: false,
        promotion: false,
        statusbar: true,
        resize: true,
        automatic_uploads: true,
        file_picker_types: 'image',
        // 이미지 업로드 핸들러
        images_upload_handler: function(blobInfo, progress) {
            return new Promise((resolve, reject) => {
                const formData = new FormData();
                formData.append('image', blobInfo.blob(), blobInfo.filename());

                fetch('upload_popup_image.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        resolve(result.url);
                    } else {
                        reject({ message: result.message || '이미지 업로드 실패', remove: true });
                    }
                })
                .catch(error => {
                    reject({ message: '이미지 업로드 중 오류가 발생했습니다.', remove: true });
                });
            });
        },
        setup: function(editor) {
            tinymceEditor = editor;

            editor.on('init', function() {
                console.log('TinyMCE initialized');
            });

            editor.on('change', function() {
                editor.save();
            });
        }
    });
}

// 에디터 모드 전환
function switchEditorMode(mode) {
    const visualBtn = document.getElementById('visualModeBtn');
    const htmlBtn = document.getElementById('htmlModeBtn');
    const visualContainer = document.getElementById('visualEditorContainer');
    const htmlContainer = document.getElementById('htmlEditorContainer');
    const htmlCodeEditor = document.getElementById('htmlCodeEditor');
    const contentTextarea = document.getElementById('content');
    const previewContent = document.getElementById('htmlPreviewContent');

    if (mode === 'html') {
        // 비주얼 -> HTML 모드
        if (tinymceEditor) {
            const htmlContent = tinymceEditor.getContent();
            htmlCodeEditor.value = formatHTML(htmlContent);
            previewContent.innerHTML = htmlContent;
        }

        visualBtn.classList.remove('active');
        htmlBtn.classList.add('active');
        visualContainer.classList.add('hide');
        htmlContainer.classList.add('show');
        currentEditorMode = 'html';

    } else {
        // HTML -> 비주얼 모드
        const htmlContent = htmlCodeEditor.value;

        // TinyMCE에 HTML 내용 설정
        if (tinymceEditor) {
            tinymceEditor.setContent(htmlContent);
        }

        // 숨겨진 textarea 동기화
        contentTextarea.value = htmlContent;

        visualBtn.classList.add('active');
        htmlBtn.classList.remove('active');
        visualContainer.classList.remove('hide');
        htmlContainer.classList.remove('show');
        currentEditorMode = 'visual';
    }
}

// HTML 코드 에디터 입력 시 미리보기 업데이트
document.getElementById('htmlCodeEditor').addEventListener('input', function() {
    const previewContent = document.getElementById('htmlPreviewContent');
    const contentTextarea = document.getElementById('content');

    previewContent.innerHTML = this.value;
    contentTextarea.value = this.value;
});

// HTML 코드 정리 (보기 좋게 포맷팅)
function formatHTML(html) {
    if (!html) return '';

    let formatted = '';
    let indent = 0;
    const tab = '    ';

    // 간단한 HTML 포맷팅
    html = html.replace(/>\s*</g, '>\n<');

    const lines = html.split('\n');
    lines.forEach(line => {
        line = line.trim();
        if (!line) return;

        // 닫는 태그 체크
        if (line.match(/^<\/\w/)) {
            indent = Math.max(0, indent - 1);
        }

        formatted += tab.repeat(indent) + line + '\n';

        // 여는 태그이면서 닫는 태그가 아닌 경우
        if (line.match(/^<\w[^>]*[^\/]>.*$/) && !line.match(/^<(area|base|br|col|embed|hr|img|input|link|meta|param|source|track|wbr)/i) && !line.match(/<\/\w+>$/)) {
            indent++;
        }
    });

    return formatted.trim();
}

// 폼 제출 전 동기화
document.querySelector('form').addEventListener('submit', function(e) {
    // HTML 모드일 때 TinyMCE와 동기화
    if (currentEditorMode === 'html') {
        const htmlContent = document.getElementById('htmlCodeEditor').value;
        document.getElementById('content').value = htmlContent;

        if (tinymceEditor) {
            tinymceEditor.setContent(htmlContent);
        }
    } else {
        // 비주얼 모드일 때 TinyMCE 내용 저장
        if (tinymceEditor) {
            tinymceEditor.save();
        }
    }
});
</script>

<?php require_once 'admin_tail.php'; ?>
