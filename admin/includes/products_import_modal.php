<?php
/**
 * 제품 업로드 모달 컴포넌트
 * 드래그앤드롭, 미리보기, 진행률 표시 지원
 */

// CSRF 토큰 생성 (세션이 시작되지 않았다면)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 현재 카테고리 필터
$modal_category = $category_filter ?? '';
?>

<!-- 업로드 모달 -->
<div id="importModal" class="import-modal" onclick="if(event.target===this) closeImportModal()">
    <div class="import-modal-content">
        <div class="import-modal-header">
            <h3>📤 제품 데이터 업로드</h3>
            <button type="button" class="close-btn" onclick="closeImportModal()">&times;</button>
        </div>
        
        <div class="import-modal-body">
            <!-- 안내 영역 -->
            <div class="import-info">
                <p><strong>📋 업로드 형식:</strong> CSV 파일 (최대 10MB)</p>
                <p><strong>📌 필수 항목:</strong> 카테고리코드, 제품명, 규격</p>
                <?php if ($modal_category): ?>
                <p><strong>📂 현재 카테고리:</strong> <?php echo htmlspecialchars($modal_category); ?></p>
                <?php endif; ?>
            </div>
            
            <!-- 파일 드롭 영역 -->
            <div class="file-drop-zone" id="fileDropZone">
                <input type="file" id="modalFileInput" accept=".csv,.txt" style="display:none">
                <div class="drop-content">
                    <span class="drop-icon">📄</span>
                    <p>파일을 여기로 드래그하거나<br><strong>클릭하여 선택</strong>하세요</p>
                </div>
            </div>
            
            <div class="file-selected-info" id="modalFileSelected" style="display:none"></div>
            
            <!-- 미리보기 버튼 -->
            <div class="preview-actions" id="previewActions" style="display:none">
                <button type="button" class="btn btn-outline" onclick="previewFile()">
                    🔍 미리보기
                </button>
            </div>
            
            <!-- 미리보기 섹션 -->
            <div class="preview-section" id="previewSection" style="display:none">
                <h4>📊 미리보기 결과</h4>
                
                <!-- 요약 -->
                <div class="preview-summary" id="previewSummary"></div>
                
                <!-- 데이터 테이블 -->
                <div class="preview-table-wrapper">
                    <table class="preview-table" id="previewTable">
                        <thead>
                            <tr>
                                <th>행</th>
                                <th>ID</th>
                                <th>카테고리</th>
                                <th>제품명</th>
                                <th>규격</th>
                                <th>작업</th>
                                <th>상태</th>
                            </tr>
                        </thead>
                        <tbody id="previewTableBody"></tbody>
                    </table>
                </div>
                
                <!-- 오류 목록 -->
                <div class="validation-errors" id="validationErrors" style="display:none">
                    <h5>⚠️ 검증 오류</h5>
                    <ul id="errorList"></ul>
                </div>
            </div>
            
            <!-- 진행률 표시 -->
            <div class="progress-section" id="progressSection" style="display:none">
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill"></div>
                </div>
                <p class="progress-text" id="progressText">처리 중...</p>
            </div>
        </div>
        
        <div class="import-modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeImportModal()">취소</button>
            <button type="button" class="btn btn-primary" id="uploadBtn" disabled onclick="uploadFile()">
                업로드 실행
            </button>
        </div>
    </div>
</div>

<style>
/* 모달 스타일 */
.import-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 10000;
    justify-content: center;
    align-items: center;
}

.import-modal-content {
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 700px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
}

.import-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 25px;
    border-bottom: 1px solid #e9ecef;
}

.import-modal-header h3 {
    margin: 0;
    font-size: 18px;
    color: #333;
}

.close-btn {
    background: none;
    border: none;
    font-size: 28px;
    color: #999;
    cursor: pointer;
    padding: 0;
    line-height: 1;
}

.close-btn:hover {
    color: #333;
}

.import-modal-body {
    padding: 25px;
    overflow-y: auto;
    flex: 1;
}

.import-info {
    background: #e3f2fd;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.import-info p {
    margin: 5px 0;
    font-size: 14px;
    color: #1565c0;
}

/* 파일 드롭 영역 */
.file-drop-zone {
    border: 2px dashed #ccc;
    border-radius: 12px;
    padding: 40px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background: #fafafa;
}

.file-drop-zone:hover,
.file-drop-zone.drag-over {
    border-color: #2196f3;
    background: #e3f2fd;
}

.drop-icon {
    font-size: 48px;
    display: block;
    margin-bottom: 15px;
}

.drop-content p {
    margin: 0;
    color: #666;
    line-height: 1.6;
}

.file-selected-info {
    margin-top: 15px;
    padding: 12px 15px;
    background: #d4edda;
    border-radius: 6px;
    color: #155724;
    font-weight: 500;
}

.preview-actions {
    margin-top: 15px;
    text-align: center;
}

/* 미리보기 섹션 */
.preview-section {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #e9ecef;
}

.preview-section h4 {
    margin: 0 0 15px 0;
    font-size: 16px;
}

.preview-summary {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

.summary-item {
    padding: 10px 15px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
}

.summary-item.insert { background: #d4edda; color: #155724; }
.summary-item.update { background: #fff3cd; color: #856404; }
.summary-item.delete { background: #f8d7da; color: #721c24; }
.summary-item.error { background: #f8d7da; color: #721c24; }
.summary-item.total { background: #e2e3e5; color: #383d41; }

.preview-table-wrapper {
    max-height: 250px;
    overflow-y: auto;
    border: 1px solid #e9ecef;
    border-radius: 6px;
}

.preview-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.preview-table th,
.preview-table td {
    padding: 8px 10px;
    text-align: left;
    border-bottom: 1px solid #e9ecef;
}

.preview-table th {
    background: #f8f9fa;
    font-weight: 600;
    position: sticky;
    top: 0;
}

.preview-table tr:last-child td {
    border-bottom: none;
}

.action-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
}

.action-badge.insert { background: #28a745; color: white; }
.action-badge.update { background: #ffc107; color: #333; }
.action-badge.delete { background: #dc3545; color: white; }
.action-badge.error { background: #dc3545; color: white; }

.status-icon { font-size: 16px; }

.validation-errors {
    margin-top: 15px;
    padding: 15px;
    background: #fff3cd;
    border-radius: 6px;
}

.validation-errors h5 {
    margin: 0 0 10px 0;
    color: #856404;
}

.validation-errors ul {
    margin: 0;
    padding-left: 20px;
    color: #856404;
    font-size: 13px;
}

/* 진행률 */
.progress-section {
    margin-top: 20px;
}

.progress-bar {
    height: 8px;
    background: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #2196f3, #21cbf3);
    width: 0%;
    transition: width 0.3s ease;
}

.progress-text {
    text-align: center;
    margin-top: 10px;
    font-size: 14px;
    color: #666;
}

/* 모달 푸터 */
.import-modal-footer {
    padding: 15px 25px;
    border-top: 1px solid #e9ecef;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.btn-outline {
    background: white;
    border: 1px solid #2196f3;
    color: #2196f3;
}

.btn-outline:hover {
    background: #e3f2fd;
}
</style>

<script>
// 모달 관련 변수
let selectedFile = null;
const csrfToken = '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>';
const returnCategory = '<?php echo htmlspecialchars($modal_category); ?>';

// 파일 드롭 영역 이벤트
const dropZone = document.getElementById('fileDropZone');
const fileInput = document.getElementById('modalFileInput');

dropZone.addEventListener('click', () => fileInput.click());

dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.classList.add('drag-over');
});

dropZone.addEventListener('dragleave', () => {
    dropZone.classList.remove('drag-over');
});

dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('drag-over');
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        handleFileSelect(files[0]);
    }
});

fileInput.addEventListener('change', (e) => {
    if (e.target.files.length > 0) {
        handleFileSelect(e.target.files[0]);
    }
});

// 파일 선택 처리
function handleFileSelect(file) {
    const ext = file.name.split('.').pop().toLowerCase();
    if (!['csv', 'txt'].includes(ext)) {
        alert('CSV 파일만 업로드 가능합니다.');
        return;
    }

    if (file.size > 10 * 1024 * 1024) {
        alert('파일 크기가 너무 큽니다. (최대 10MB)');
        return;
    }

    selectedFile = file;
    const fileInfo = document.getElementById('modalFileSelected');
    fileInfo.textContent = '선택된 파일: ' + file.name + ' (' + formatFileSize(file.size) + ')';
    fileInfo.style.display = 'block';

    document.getElementById('previewActions').style.display = 'block';
    document.getElementById('previewSection').style.display = 'none';
    document.getElementById('uploadBtn').disabled = true;
}

// 파일 크기 포맷
function formatFileSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

// 미리보기 요청
function previewFile() {
    if (!selectedFile) {
        alert('파일을 선택해주세요.');
        return;
    }

    const formData = new FormData();
    formData.append('preview_file', selectedFile);
    formData.append('csrf_token', csrfToken);

    // 로딩 표시
    document.getElementById('progressSection').style.display = 'block';
    document.getElementById('progressFill').style.width = '30%';
    document.getElementById('progressText').textContent = '파일 분석 중...';

    fetch('ajax/products_preview.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('progressSection').style.display = 'none';

        if (data.success) {
            showPreviewResult(data);
        } else {
            alert(data.error || '미리보기 실패');
        }
    })
    .catch(error => {
        document.getElementById('progressSection').style.display = 'none';
        alert('서버 오류가 발생했습니다.');
        console.error(error);
    });
}

// 미리보기 결과 표시
function showPreviewResult(data) {
    document.getElementById('previewSection').style.display = 'block';

    // 요약 표시
    const summary = document.getElementById('previewSummary');
    summary.innerHTML = `
        <div class="summary-item total">전체: ${data.total_rows}건</div>
        <div class="summary-item insert">추가: ${data.summary.insert}건</div>
        <div class="summary-item update">수정: ${data.summary.update}건</div>
        <div class="summary-item delete">삭제: ${data.summary.delete}건</div>
        ${data.summary.error > 0 ? `<div class="summary-item error">오류: ${data.summary.error}건</div>` : ''}
    `;

    // 테이블 표시
    const tbody = document.getElementById('previewTableBody');
    tbody.innerHTML = '';

    data.preview_data.forEach(row => {
        const tr = document.createElement('tr');
        const hasError = row.errors && row.errors.length > 0;
        const actionClass = row.action.toLowerCase();

        tr.innerHTML = `
            <td>${row.line}</td>
            <td>${row.id || '-'}</td>
            <td>${row.category}</td>
            <td>${row.name}</td>
            <td>${row.spec}</td>
            <td><span class="action-badge ${actionClass}">${row.action}</span></td>
            <td class="status-icon">${hasError ? '⚠️' : '✅'}</td>
        `;

        if (hasError) {
            tr.style.background = '#fff8e1';
            tr.title = row.errors.join(', ');
        }

        tbody.appendChild(tr);
    });

    // 오류 목록 표시
    if (data.validation_errors && data.validation_errors.length > 0) {
        document.getElementById('validationErrors').style.display = 'block';
        const errorList = document.getElementById('errorList');
        errorList.innerHTML = '';
        data.validation_errors.slice(0, 10).forEach(err => {
            const li = document.createElement('li');
            li.textContent = `라인 ${err.line}: ${err.errors.join(', ')}`;
            errorList.appendChild(li);
        });
        if (data.validation_errors.length > 10) {
            const li = document.createElement('li');
            li.textContent = `... 외 ${data.validation_errors.length - 10}건`;
            errorList.appendChild(li);
        }
    } else {
        document.getElementById('validationErrors').style.display = 'none';
    }

    // 오류가 없으면 업로드 버튼 활성화
    document.getElementById('uploadBtn').disabled = data.has_errors;
}

// 실제 업로드 실행
function uploadFile() {
    if (!selectedFile) {
        alert('파일을 선택해주세요.');
        return;
    }

    if (!confirm('업로드를 실행하시겠습니까?')) {
        return;
    }

    // 폼 생성 및 제출
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'products_import.php';
    form.enctype = 'multipart/form-data';

    // CSRF 토큰
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = 'csrf_token';
    csrfInput.value = csrfToken;
    form.appendChild(csrfInput);

    // 리턴 카테고리
    const categoryInput = document.createElement('input');
    categoryInput.type = 'hidden';
    categoryInput.name = 'return_category';
    categoryInput.value = returnCategory;
    form.appendChild(categoryInput);

    // 파일 입력 (원본 input을 form으로 이동 - cloneNode는 FileList를 복제하지 못함)
    fileInput.name = 'excel_file';
    form.appendChild(fileInput);

    document.body.appendChild(form);

    // 진행률 표시
    document.getElementById('progressSection').style.display = 'block';
    document.getElementById('progressFill').style.width = '60%';
    document.getElementById('progressText').textContent = '업로드 중...';

    form.submit();
}
</script>
