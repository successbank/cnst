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

/* 템플릿 선택 섹션 */
.template-section {
    margin-bottom: 32px;
}

.template-description {
    color: #666;
    margin-bottom: 20px;
    font-size: 14px;
}

.template-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 16px;
}

@media (max-width: 1200px) {
    .template-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

@media (max-width: 992px) {
    .template-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    .template-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

.template-card {
    background: white;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.3s ease;
}

.template-card:hover {
    border-color: #1A237E;
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(26, 35, 126, 0.15);
}

.template-card.selected {
    border-color: #1A237E;
    box-shadow: 0 0 0 3px rgba(26, 35, 126, 0.2);
}

.template-preview {
    height: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.template-preview-content {
    text-align: center;
    color: white;
}

.template-badge {
    display: block;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1px;
    background: rgba(255,255,255,0.25);
    padding: 4px 10px;
    border-radius: 12px;
    margin-bottom: 6px;
}

.template-title-preview {
    display: block;
    font-size: 18px;
    font-weight: 700;
}

.template-info {
    padding: 12px;
    text-align: center;
}

.template-info strong {
    display: block;
    font-size: 13px;
    color: #333;
    margin-bottom: 4px;
}

.template-info span {
    font-size: 11px;
    color: #888;
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

        <!-- 템플릿 선택 (신규 등록 시에만 표시) -->
        <?php if (!$isEdit): ?>
        <h3 class="section-title"><i class="fas fa-palette"></i> 템플릿 선택</h3>
        <div class="template-section">
            <p class="template-description">원하는 템플릿을 선택하면 자동으로 내용이 채워집니다. 선택 후 수정할 수 있습니다.</p>
            <div class="template-grid">
                <!-- 템플릿 1: 이벤트 프로모션 -->
                <div class="template-card" onclick="applyTemplate(1)">
                    <div class="template-preview" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <div class="template-preview-content">
                            <span class="template-badge">EVENT</span>
                            <span class="template-title-preview">이벤트</span>
                        </div>
                    </div>
                    <div class="template-info">
                        <strong>이벤트 프로모션</strong>
                        <span>특별 이벤트, 프로모션 안내</span>
                    </div>
                </div>

                <!-- 템플릿 2: 할인 세일 -->
                <div class="template-card" onclick="applyTemplate(2)">
                    <div class="template-preview" style="background: linear-gradient(135deg, #f5576c 0%, #f093fb 100%);">
                        <div class="template-preview-content">
                            <span class="template-badge">SALE</span>
                            <span class="template-title-preview">할인</span>
                        </div>
                    </div>
                    <div class="template-info">
                        <strong>할인 세일</strong>
                        <span>할인, 특가 세일 안내</span>
                    </div>
                </div>

                <!-- 템플릿 3: 신제품 출시 -->
                <div class="template-card" onclick="applyTemplate(3)">
                    <div class="template-preview" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                        <div class="template-preview-content">
                            <span class="template-badge">NEW</span>
                            <span class="template-title-preview">신제품</span>
                        </div>
                    </div>
                    <div class="template-info">
                        <strong>신제품 출시</strong>
                        <span>새로운 제품 출시 안내</span>
                    </div>
                </div>

                <!-- 템플릿 4: 공지사항 -->
                <div class="template-card" onclick="applyTemplate(4)">
                    <div class="template-preview" style="background: linear-gradient(135deg, #1A237E 0%, #3949AB 100%);">
                        <div class="template-preview-content">
                            <span class="template-badge">NOTICE</span>
                            <span class="template-title-preview">공지</span>
                        </div>
                    </div>
                    <div class="template-info">
                        <strong>공지사항</strong>
                        <span>중요 공지, 안내사항</span>
                    </div>
                </div>

                <!-- 템플릿 5: 휴무 안내 -->
                <div class="template-card" onclick="applyTemplate(5)">
                    <div class="template-preview" style="background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);">
                        <div class="template-preview-content">
                            <span class="template-badge">CLOSED</span>
                            <span class="template-title-preview">휴무</span>
                        </div>
                    </div>
                    <div class="template-info">
                        <strong>휴무 안내</strong>
                        <span>휴일, 연휴 휴무 안내</span>
                    </div>
                </div>

                <!-- 템플릿 6: 회원 혜택 -->
                <div class="template-card" onclick="applyTemplate(6)">
                    <div class="template-preview" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <div class="template-preview-content">
                            <span class="template-badge">VIP</span>
                            <span class="template-title-preview">혜택</span>
                        </div>
                    </div>
                    <div class="template-info">
                        <strong>회원 혜택</strong>
                        <span>회원 전용 혜택, 적립금</span>
                    </div>
                </div>

                <!-- 템플릿 7: 무료 배송 -->
                <div class="template-card" onclick="applyTemplate(7)">
                    <div class="template-preview" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <div class="template-preview-content">
                            <span class="template-badge">FREE</span>
                            <span class="template-title-preview">배송</span>
                        </div>
                    </div>
                    <div class="template-info">
                        <strong>무료 배송</strong>
                        <span>배송비 무료, 빠른 배송</span>
                    </div>
                </div>

                <!-- 템플릿 8: 고객 감사 -->
                <div class="template-card" onclick="applyTemplate(8)">
                    <div class="template-preview" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                        <div class="template-preview-content">
                            <span class="template-badge">THANKS</span>
                            <span class="template-title-preview">감사</span>
                        </div>
                    </div>
                    <div class="template-info">
                        <strong>고객 감사</strong>
                        <span>감사 인사, 사은 행사</span>
                    </div>
                </div>

                <!-- 템플릿 9: 시즌 인사 -->
                <div class="template-card" onclick="applyTemplate(9)">
                    <div class="template-preview" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);">
                        <div class="template-preview-content">
                            <span class="template-badge">SEASON</span>
                            <span class="template-title-preview">인사</span>
                        </div>
                    </div>
                    <div class="template-info">
                        <strong>시즌 인사</strong>
                        <span>명절, 새해 인사말</span>
                    </div>
                </div>

                <!-- 템플릿 10: 문의 안내 -->
                <div class="template-card" onclick="applyTemplate(10)">
                    <div class="template-preview" style="background: linear-gradient(135deg, #5ee7df 0%, #b490ca 100%);">
                        <div class="template-preview-content">
                            <span class="template-badge">CONTACT</span>
                            <span class="template-title-preview">문의</span>
                        </div>
                    </div>
                    <div class="template-info">
                        <strong>문의 안내</strong>
                        <span>상담, 문의 방법 안내</span>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

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

            <!-- 비주얼 에디터 (Summernote) -->
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

<!-- jQuery (Summernote 의존성) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- Summernote CDN (MIT 라이선스 - 완전 무료) -->
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/lang/summernote-ko-KR.min.js"></script>

<style>
/* Summernote 커스텀 스타일 */
.note-editor.note-frame {
    border: 1px solid #e0e0e0 !important;
    border-radius: 8px !important;
    overflow: hidden;
}

.note-editor .note-toolbar {
    background: #f8f9fa !important;
    border-bottom: 1px solid #e0e0e0 !important;
    padding: 8px !important;
}

.note-editor .note-editing-area {
    background: white;
}

.note-editor .note-editing-area .note-editable {
    padding: 16px !important;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    font-size: 16px;
    line-height: 1.6;
    color: #333;
}

.note-editor .note-statusbar {
    background: #f8f9fa !important;
    border-top: 1px solid #e0e0e0 !important;
}

.note-btn {
    border-radius: 4px !important;
}

.note-btn:hover {
    background: #e9ecef !important;
}

/* 코드뷰 스타일 */
.note-editor .note-codable {
    background: #1e1e1e !important;
    color: #d4d4d4 !important;
    font-family: "Consolas", "Monaco", "Courier New", monospace !important;
    font-size: 14px !important;
    padding: 16px !important;
}
</style>

<script>
// 현재 에디터 모드
let currentEditorMode = 'visual';

// Summernote 초기화
document.addEventListener('DOMContentLoaded', function() {
    initSummernote();
});

function initSummernote() {
    $('#content').summernote({
        lang: 'ko-KR',
        height: 400,
        placeholder: '팝업 내용을 입력하세요...',
        tabsize: 2,
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
            ['fontname', ['fontname']],
            ['fontsize', ['fontsize']],
            ['color', ['forecolor', 'backcolor']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['height', ['height']],
            ['table', ['table']],
            ['insert', ['link', 'picture', 'video', 'hr']],
            ['view', ['fullscreen', 'codeview', 'help']]
        ],
        fontNames: ['맑은 고딕', '굴림', '돋움', '바탕', 'Arial', 'Arial Black', 'Comic Sans MS', 'Courier New', 'Helvetica', 'Impact', 'Tahoma', 'Times New Roman', 'Verdana'],
        fontNamesIgnoreCheck: ['맑은 고딕', '굴림', '돋움', '바탕'],
        styleTags: ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'],
        callbacks: {
            onInit: function() {
                console.log('Summernote initialized');
            },
            onImageUpload: function(files) {
                // 이미지 업로드 처리
                for (let i = 0; i < files.length; i++) {
                    uploadImage(files[i]);
                }
            },
            onChange: function(contents) {
                // 변경 시 숨겨진 textarea 동기화
                $('#content').val(contents);
            },
            onCodeviewChange: function(contents) {
                // 코드뷰 변경 시에도 동기화
                $('#content').val(contents);
            }
        }
    });
}

// 이미지 업로드 함수
function uploadImage(file) {
    const formData = new FormData();
    formData.append('image', file);

    fetch('upload_popup_image.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            // 에디터에 이미지 삽입
            $('#content').summernote('insertImage', result.url, function($image) {
                $image.css('max-width', '100%');
                $image.attr('alt', file.name);
            });
        } else {
            alert('이미지 업로드 실패: ' + (result.message || '알 수 없는 오류'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('이미지 업로드 중 오류가 발생했습니다.');
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
        const htmlContent = $('#content').summernote('code');
        htmlCodeEditor.value = formatHTML(htmlContent);
        previewContent.innerHTML = htmlContent;
        contentTextarea.value = htmlContent;

        visualBtn.classList.remove('active');
        htmlBtn.classList.add('active');
        visualContainer.classList.add('hide');
        htmlContainer.classList.add('show');
        currentEditorMode = 'html';

    } else {
        // HTML -> 비주얼 모드
        const htmlContent = htmlCodeEditor.value;

        // Summernote에 HTML 내용 설정
        $('#content').summernote('code', htmlContent);
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
    // HTML 모드일 때 동기화
    if (currentEditorMode === 'html') {
        const htmlContent = document.getElementById('htmlCodeEditor').value;
        document.getElementById('content').value = htmlContent;
        $('#content').summernote('code', htmlContent);
    } else {
        // 비주얼 모드일 때 Summernote 내용 저장
        const content = $('#content').summernote('code');
        document.getElementById('content').value = content;
    }
});

// ============================================
// 팝업 템플릿 데이터 및 적용 함수
// ============================================

const popupTemplates = {
    // 템플릿 1: 이벤트 프로모션
    1: {
        title: '이벤트 프로모션',
        content: `
<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 30px; text-align: center; color: white;">
    <div style="background: rgba(255,255,255,0.2); display: inline-block; padding: 6px 16px; border-radius: 20px; font-size: 12px; font-weight: 700; letter-spacing: 1px; margin-bottom: 16px;">SPECIAL EVENT</div>
    <h2 style="font-size: 28px; font-weight: 700; margin: 0 0 12px 0; color: white;">특별 이벤트 진행중!</h2>
    <p style="font-size: 16px; margin: 0 0 24px 0; opacity: 0.9; color: white;">지금 참여하시면 다양한 혜택을 드립니다</p>
    <div style="background: white; color: #667eea; padding: 12px 32px; border-radius: 30px; display: inline-block; font-weight: 700; font-size: 15px;">자세히 보기</div>
</div>
<div style="padding: 24px 30px; background: #f8f9fa; text-align: center;">
    <p style="margin: 0; color: #666; font-size: 14px;"><strong>이벤트 기간:</strong> 2026.01.01 ~ 2026.01.31</p>
</div>`,
        width: 420,
        height: 380
    },

    // 템플릿 2: 할인 세일
    2: {
        title: '할인 세일',
        content: `
<div style="background: linear-gradient(135deg, #f5576c 0%, #f093fb 100%); padding: 40px 30px; text-align: center; color: white;">
    <div style="font-size: 14px; font-weight: 600; margin-bottom: 8px; letter-spacing: 2px;">LIMITED TIME OFFER</div>
    <div style="font-size: 72px; font-weight: 900; line-height: 1; margin-bottom: 8px;">50%</div>
    <div style="font-size: 24px; font-weight: 700; margin-bottom: 20px;">할인 세일</div>
    <p style="font-size: 15px; opacity: 0.9; margin-bottom: 24px;">전 품목 최대 50% 특별 할인!</p>
    <div style="background: white; color: #f5576c; padding: 14px 40px; border-radius: 30px; display: inline-block; font-weight: 700; font-size: 16px;">쇼핑하러 가기</div>
</div>
<div style="padding: 20px; background: #fff3f5; text-align: center;">
    <p style="margin: 0; color: #f5576c; font-size: 13px; font-weight: 600;">* 일부 품목 제외, 재고 소진 시 조기 종료</p>
</div>`,
        width: 400,
        height: 420
    },

    // 템플릿 3: 신제품 출시
    3: {
        title: '신제품 출시',
        content: `
<div style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); padding: 40px 30px; text-align: center; color: white;">
    <div style="background: rgba(255,255,255,0.25); display: inline-block; padding: 6px 16px; border-radius: 20px; font-size: 11px; font-weight: 700; letter-spacing: 2px; margin-bottom: 16px;">NEW ARRIVAL</div>
    <h2 style="font-size: 26px; font-weight: 700; margin: 0 0 16px 0; color: white;">신제품이 출시되었습니다</h2>
    <p style="font-size: 15px; margin: 0 0 24px 0; opacity: 0.9; line-height: 1.6; color: white;">더 나은 품질, 더 합리적인 가격으로<br>새롭게 선보입니다</p>
    <div style="background: white; color: #11998e; padding: 12px 32px; border-radius: 30px; display: inline-block; font-weight: 700; font-size: 15px;">제품 보러가기</div>
</div>
<div style="padding: 20px 30px; background: #e8f5e9;">
    <div style="display: flex; justify-content: center; gap: 30px; text-align: center;">
        <div><div style="font-size: 20px; font-weight: 700; color: #11998e;">최고급</div><div style="font-size: 12px; color: #666;">품질</div></div>
        <div><div style="font-size: 20px; font-weight: 700; color: #11998e;">합리적</div><div style="font-size: 12px; color: #666;">가격</div></div>
        <div><div style="font-size: 20px; font-weight: 700; color: #11998e;">빠른</div><div style="font-size: 12px; color: #666;">배송</div></div>
    </div>
</div>`,
        width: 420,
        height: 400
    },

    // 템플릿 4: 공지사항
    4: {
        title: '공지사항',
        content: `
<div style="background: linear-gradient(135deg, #1A237E 0%, #3949AB 100%); padding: 30px; color: white;">
    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
        <div style="width: 40px; height: 40px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px;">📢</div>
        <div style="font-size: 20px; font-weight: 700;">공지사항</div>
    </div>
    <p style="font-size: 14px; opacity: 0.9; margin: 0; line-height: 1.6;">중요한 안내사항을 알려드립니다.</p>
</div>
<div style="padding: 28px 30px; background: white;">
    <h3 style="font-size: 18px; font-weight: 700; color: #333; margin: 0 0 16px 0;">[안내] 시스템 점검 안내</h3>
    <p style="font-size: 14px; color: #666; line-height: 1.7; margin: 0 0 20px 0;">
        안녕하세요, 충남스틸입니다.<br><br>
        더 나은 서비스 제공을 위해 시스템 점검이 진행될 예정입니다. 이용에 참고 부탁드립니다.
    </p>
    <div style="background: #f5f5f5; padding: 16px; border-radius: 8px; font-size: 13px; color: #666;">
        <strong>점검 일시:</strong> 2026년 1월 15일 02:00 ~ 06:00
    </div>
</div>`,
        width: 440,
        height: 420
    },

    // 템플릿 5: 휴무 안내
    5: {
        title: '휴무 안내',
        content: `
<div style="background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%); padding: 40px 30px; text-align: center;">
    <div style="font-size: 48px; margin-bottom: 16px;">🏠</div>
    <h2 style="font-size: 24px; font-weight: 700; color: #333; margin: 0 0 12px 0;">휴무 안내</h2>
    <p style="font-size: 15px; color: #666; margin: 0 0 24px 0;">아래 기간 동안 휴무입니다</p>
    <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
        <div style="font-size: 14px; color: #999; margin-bottom: 8px;">휴무 기간</div>
        <div style="font-size: 20px; font-weight: 700; color: #f5576c;">2026.01.24 (금) ~ 01.27 (월)</div>
        <div style="font-size: 13px; color: #666; margin-top: 12px;">설 연휴 (4일간)</div>
    </div>
</div>
<div style="padding: 20px 30px; background: white; text-align: center;">
    <p style="margin: 0; font-size: 14px; color: #666; line-height: 1.6;">
        휴무 기간 중 주문 및 문의는<br><strong>1월 28일(화)</strong>부터 순차 처리됩니다.
    </p>
</div>`,
        width: 400,
        height: 440
    },

    // 템플릿 6: 회원 혜택
    6: {
        title: '회원 혜택',
        content: `
<div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); padding: 40px 30px; text-align: center; color: white;">
    <div style="font-size: 12px; letter-spacing: 3px; margin-bottom: 12px; opacity: 0.9;">MEMBERS ONLY</div>
    <div style="font-size: 48px; margin-bottom: 12px;">👑</div>
    <h2 style="font-size: 26px; font-weight: 700; margin: 0 0 12px 0; color: white;">회원 특별 혜택</h2>
    <p style="font-size: 15px; opacity: 0.9; margin: 0; color: white;">지금 가입하고 혜택을 받으세요!</p>
</div>
<div style="padding: 28px 30px; background: white;">
    <div style="display: flex; flex-direction: column; gap: 12px;">
        <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: #fff0f3; border-radius: 8px;">
            <span style="font-size: 20px;">🎁</span>
            <span style="font-size: 14px; color: #333;"><strong>신규 가입</strong> 5,000P 즉시 지급</span>
        </div>
        <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: #fff0f3; border-radius: 8px;">
            <span style="font-size: 20px;">💰</span>
            <span style="font-size: 14px; color: #333;"><strong>구매 적립</strong> 최대 5% 적립</span>
        </div>
        <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: #fff0f3; border-radius: 8px;">
            <span style="font-size: 20px;">🚚</span>
            <span style="font-size: 14px; color: #333;"><strong>무료배송</strong> 월 1회 제공</span>
        </div>
    </div>
    <div style="text-align: center; margin-top: 20px;">
        <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 12px 32px; border-radius: 30px; display: inline-block; font-weight: 700; font-size: 15px;">회원가입</div>
    </div>
</div>`,
        width: 400,
        height: 520
    },

    // 템플릿 7: 무료 배송
    7: {
        title: '무료 배송',
        content: `
<div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); padding: 40px 30px; text-align: center; color: white;">
    <div style="font-size: 56px; margin-bottom: 16px;">🚚</div>
    <h2 style="font-size: 28px; font-weight: 700; margin: 0 0 12px 0; color: white;">무료배송 이벤트</h2>
    <p style="font-size: 16px; opacity: 0.9; margin: 0; color: white;">전 품목 배송비 무료!</p>
</div>
<div style="padding: 28px 30px; background: white; text-align: center;">
    <div style="background: #e3f2fd; padding: 20px; border-radius: 12px; margin-bottom: 20px;">
        <div style="font-size: 14px; color: #666; margin-bottom: 8px;">조건</div>
        <div style="font-size: 24px; font-weight: 700; color: #4facfe;">5만원 이상 구매 시</div>
    </div>
    <p style="font-size: 13px; color: #888; margin: 0 0 20px 0;">
        * 도서산간 지역 추가 배송비 발생<br>
        * 이벤트 기간: 2026.01.01 ~ 01.31
    </p>
    <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 12px 32px; border-radius: 30px; display: inline-block; font-weight: 700; font-size: 15px;">쇼핑하기</div>
</div>`,
        width: 400,
        height: 450
    },

    // 템플릿 8: 고객 감사
    8: {
        title: '고객 감사',
        content: `
<div style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); padding: 40px 30px; text-align: center; color: white;">
    <div style="font-size: 56px; margin-bottom: 16px;">🙏</div>
    <h2 style="font-size: 26px; font-weight: 700; margin: 0 0 12px 0; color: white;">고객 감사 이벤트</h2>
    <p style="font-size: 15px; opacity: 0.95; margin: 0; color: white;">항상 저희를 믿고 찾아주셔서<br>진심으로 감사드립니다</p>
</div>
<div style="padding: 28px 30px; background: white; text-align: center;">
    <div style="font-size: 15px; color: #666; margin-bottom: 20px; line-height: 1.7;">
        고객님의 성원에 보답하고자<br>
        <strong style="color: #fa709a;">특별 감사 혜택</strong>을 준비했습니다.
    </div>
    <div style="background: linear-gradient(135deg, #fff5f7 0%, #fffef0 100%); padding: 20px; border-radius: 12px; margin-bottom: 20px;">
        <div style="font-size: 32px; font-weight: 900; color: #fa709a; margin-bottom: 4px;">10% 할인</div>
        <div style="font-size: 13px; color: #666;">전 품목 적용</div>
    </div>
    <div style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; padding: 12px 32px; border-radius: 30px; display: inline-block; font-weight: 700; font-size: 15px;">혜택 받기</div>
</div>`,
        width: 400,
        height: 480
    },

    // 템플릿 9: 시즌 인사
    9: {
        title: '시즌 인사',
        content: `
<div style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); padding: 40px 30px; text-align: center;">
    <div style="font-size: 56px; margin-bottom: 16px;">🎊</div>
    <h2 style="font-size: 28px; font-weight: 700; color: #333; margin: 0 0 12px 0;">새해 복 많이 받으세요!</h2>
    <p style="font-size: 16px; color: #666; margin: 0 0 24px 0;">2026년 병오년 새해가 밝았습니다</p>
    <div style="background: white; padding: 24px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
        <p style="font-size: 15px; color: #555; line-height: 1.8; margin: 0;">
            항상 충남스틸을 이용해 주시는<br>
            고객님께 깊은 감사를 드리며,<br>
            새해에도 건강과 행복이<br>
            가득하시길 기원합니다.
        </p>
        <div style="margin-top: 20px; font-size: 14px; color: #888;">
            - 충남스틸 임직원 일동 -
        </div>
    </div>
</div>`,
        width: 400,
        height: 440
    },

    // 템플릿 10: 문의 안내
    10: {
        title: '문의 안내',
        content: `
<div style="background: linear-gradient(135deg, #5ee7df 0%, #b490ca 100%); padding: 30px; color: white;">
    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
        <div style="font-size: 32px;">📞</div>
        <div>
            <div style="font-size: 20px; font-weight: 700;">문의 안내</div>
            <div style="font-size: 13px; opacity: 0.9;">언제든지 연락주세요!</div>
        </div>
    </div>
</div>
<div style="padding: 28px 30px; background: white;">
    <div style="margin-bottom: 20px;">
        <div style="font-size: 12px; color: #999; margin-bottom: 6px;">대표전화</div>
        <div style="font-size: 24px; font-weight: 700; color: #333;">032-564-1616</div>
    </div>
    <div style="margin-bottom: 20px;">
        <div style="font-size: 12px; color: #999; margin-bottom: 6px;">팩스</div>
        <div style="font-size: 16px; font-weight: 600; color: #555;">032-564-0090</div>
    </div>
    <div style="margin-bottom: 20px;">
        <div style="font-size: 12px; color: #999; margin-bottom: 6px;">이메일</div>
        <div style="font-size: 16px; font-weight: 600; color: #555;">cn1616@naver.com</div>
    </div>
    <div style="background: #f5f5f5; padding: 16px; border-radius: 8px;">
        <div style="font-size: 12px; color: #999; margin-bottom: 6px;">상담 시간</div>
        <div style="font-size: 14px; color: #333;">
            <strong>평일</strong> 09:00 ~ 18:00<br>
            <strong>토요일</strong> 09:00 ~ 13:00
        </div>
    </div>
</div>`,
        width: 380,
        height: 480
    }
};

// 템플릿 적용 함수
function applyTemplate(templateId) {
    const template = popupTemplates[templateId];
    if (!template) {
        alert('템플릿을 찾을 수 없습니다.');
        return;
    }

    // 확인 메시지
    if ($('#content').summernote('code').trim() !== '' &&
        $('#content').summernote('code').trim() !== '<p><br></p>') {
        if (!confirm('현재 작성 중인 내용이 있습니다. 템플릿을 적용하면 덮어씌워집니다. 계속하시겠습니까?')) {
            return;
        }
    }

    // 제목 설정
    document.getElementById('title').value = template.title;

    // 내용 설정
    $('#content').summernote('code', template.content.trim());

    // 크기 설정
    document.getElementById('width').value = template.width;
    document.getElementById('height').value = template.height;

    // 선택된 카드 표시
    document.querySelectorAll('.template-card').forEach(card => {
        card.classList.remove('selected');
    });
    event.currentTarget.classList.add('selected');

    // 스크롤 이동
    document.querySelector('.section-title i.fa-image').parentElement.scrollIntoView({
        behavior: 'smooth',
        block: 'start'
    });

    // 알림
    showToast('템플릿이 적용되었습니다. 내용을 수정한 후 등록하세요.');
}

// 토스트 메시지 표시
function showToast(message) {
    // 기존 토스트 제거
    const existingToast = document.querySelector('.template-toast');
    if (existingToast) existingToast.remove();

    const toast = document.createElement('div');
    toast.className = 'template-toast';
    toast.innerHTML = '<i class="fas fa-check-circle"></i> ' + message;
    toast.style.cssText = `
        position: fixed;
        bottom: 30px;
        left: 50%;
        transform: translateX(-50%);
        background: #333;
        color: white;
        padding: 14px 28px;
        border-radius: 8px;
        font-size: 14px;
        z-index: 10000;
        animation: toastFadeIn 0.3s ease;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    `;
    document.body.appendChild(toast);

    // CSS 애니메이션 추가
    if (!document.querySelector('#toastAnimation')) {
        const style = document.createElement('style');
        style.id = 'toastAnimation';
        style.textContent = `
            @keyframes toastFadeIn {
                from { opacity: 0; transform: translateX(-50%) translateY(20px); }
                to { opacity: 1; transform: translateX(-50%) translateY(0); }
            }
        `;
        document.head.appendChild(style);
    }

    setTimeout(() => {
        toast.style.animation = 'toastFadeIn 0.3s ease reverse';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}
</script>

<?php require_once 'admin_tail.php'; ?>
