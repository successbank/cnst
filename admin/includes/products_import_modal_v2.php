<?php
/**
 * 제품 임포트 4단계 위자드 모달 v2
 * Step 1: 파일 업로드 + 다운로드 옵션
 * Step 2: 미리보기 & Diff
 * Step 3: 확인
 * Step 4: 결과
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$modal_category = $category_filter ?? '';
?>

<div id="importModalV2" class="imv2-overlay" onclick="if(event.target===this) ImV2.close()">
<div class="imv2-modal">
    <!-- 헤더 -->
    <div class="imv2-header">
        <h3>제품 데이터 임포트</h3>
        <button type="button" class="imv2-close" onclick="ImV2.close()">&times;</button>
    </div>

    <!-- 스텝 인디케이터 -->
    <div class="imv2-steps">
        <div class="imv2-step active" data-step="1"><span class="imv2-step-num">1</span> 파일 선택</div>
        <div class="imv2-step" data-step="2"><span class="imv2-step-num">2</span> 미리보기</div>
        <div class="imv2-step" data-step="3"><span class="imv2-step-num">3</span> 확인</div>
        <div class="imv2-step" data-step="4"><span class="imv2-step-num">4</span> 결과</div>
    </div>

    <!-- 바디 -->
    <div class="imv2-body">
        <!-- Step 1: 파일 업로드 -->
        <div class="imv2-panel" id="imv2Step1">
            <div class="imv2-info">
                <p><strong>CSV 파일 형식:</strong> UTF-8 인코딩, 최대 10MB</p>
                <p><strong>필수 항목:</strong> 카테고리코드, 제품명, 규격(텍스트)</p>
                <?php if ($modal_category): ?>
                <p><strong>현재 카테고리:</strong> <?php echo htmlspecialchars($modal_category); ?></p>
                <?php endif; ?>
            </div>

            <!-- 다운로드 옵션 -->
            <div class="imv2-downloads">
                <span class="imv2-dl-label">다운로드:</span>
                <a href="ajax/products_export_v2.php?category=<?php echo urlencode($modal_category); ?>" class="imv2-dl-btn">현재 데이터 CSV</a>
                <a href="ajax/products_template_v2.php?category=<?php echo urlencode($modal_category); ?>" class="imv2-dl-btn">빈 템플릿</a>
                <a href="ajax/products_template_v2.php?category=<?php echo urlencode($modal_category); ?>&sample" class="imv2-dl-btn">샘플 템플릿</a>
            </div>

            <!-- 파일 드롭존 -->
            <div class="imv2-dropzone" id="imv2Dropzone">
                <input type="file" id="imv2FileInput" accept=".csv,.txt" style="display:none">
                <div class="imv2-drop-content">
                    <div class="imv2-drop-icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#999" stroke-width="1.5"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    </div>
                    <p>파일을 여기로 드래그하거나 <strong>클릭하여 선택</strong></p>
                    <span class="imv2-hint">CSV 또는 TXT 파일</span>
                </div>
            </div>

            <div class="imv2-file-info" id="imv2FileInfo" style="display:none"></div>
        </div>

        <!-- Step 2: 미리보기 & Diff -->
        <div class="imv2-panel" id="imv2Step2" style="display:none">
            <!-- 로딩 -->
            <div class="imv2-loading" id="imv2Loading" style="display:none">
                <div class="imv2-spinner"></div>
                <p>파일 분석 중...</p>
            </div>

            <!-- 요약 뱃지 -->
            <div class="imv2-summary" id="imv2Summary"></div>

            <!-- 포맷 정보 -->
            <div class="imv2-format-info" id="imv2FormatInfo"></div>

            <!-- 행 목록 테이블 -->
            <div class="imv2-table-wrap">
                <table class="imv2-table" id="imv2Table">
                    <thead>
                        <tr>
                            <th style="width:50px">행</th>
                            <th style="width:60px">ID</th>
                            <th>제품명</th>
                            <th>규격</th>
                            <th style="width:70px">작업</th>
                            <th style="width:50px">상태</th>
                        </tr>
                    </thead>
                    <tbody id="imv2TableBody"></tbody>
                </table>
            </div>

            <!-- 오류 목록 -->
            <div class="imv2-errors" id="imv2Errors" style="display:none">
                <h5>검증 오류</h5>
                <ul id="imv2ErrorList"></ul>
            </div>
        </div>

        <!-- Step 3: 확인 -->
        <div class="imv2-panel" id="imv2Step3" style="display:none">
            <div class="imv2-confirm-summary" id="imv2ConfirmSummary"></div>
            <div class="imv2-confirm-warning" id="imv2ConfirmWarning" style="display:none">
                <strong>주의:</strong> 삭제된 제품은 삭제 로그에 백업되지만 복구하려면 수동 처리가 필요합니다.
            </div>
        </div>

        <!-- Step 4: 결과 -->
        <div class="imv2-panel" id="imv2Step4" style="display:none">
            <div class="imv2-progress" id="imv2Progress">
                <div class="imv2-progress-bar">
                    <div class="imv2-progress-fill" id="imv2ProgressFill"></div>
                </div>
                <p class="imv2-progress-text" id="imv2ProgressText">처리 중...</p>
            </div>
            <div class="imv2-result" id="imv2Result" style="display:none"></div>
        </div>
    </div>

    <!-- 푸터 -->
    <div class="imv2-footer">
        <button type="button" class="imv2-btn imv2-btn-secondary" onclick="ImV2.close()">취소</button>
        <div class="imv2-footer-right">
            <button type="button" class="imv2-btn imv2-btn-secondary" id="imv2BtnPrev" style="display:none" onclick="ImV2.prev()">이전</button>
            <button type="button" class="imv2-btn imv2-btn-primary" id="imv2BtnNext" disabled onclick="ImV2.next()">다음: 미리보기</button>
        </div>
    </div>
</div>
</div>

<style>
/* 오버레이 */
.imv2-overlay {
    display: none;
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 10000;
    justify-content: center;
    align-items: center;
}
.imv2-modal {
    background: #fff;
    border-radius: 12px;
    width: 95%;
    max-width: 850px;
    max-height: 92vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 10px 40px rgba(0,0,0,0.25);
}

/* 헤더 */
.imv2-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 18px 24px;
    border-bottom: 1px solid #e9ecef;
}
.imv2-header h3 { margin: 0; font-size: 18px; color: #333; }
.imv2-close {
    background: none; border: none; font-size: 28px; color: #999; cursor: pointer; padding: 0; line-height: 1;
}
.imv2-close:hover { color: #333; }

/* 스텝 인디케이터 */
.imv2-steps {
    display: flex;
    padding: 0 24px;
    border-bottom: 1px solid #e9ecef;
    background: #f8f9fa;
}
.imv2-step {
    flex: 1;
    text-align: center;
    padding: 12px 8px;
    font-size: 13px;
    color: #aaa;
    border-bottom: 3px solid transparent;
    transition: all 0.3s;
}
.imv2-step.active {
    color: #1428A0;
    border-bottom-color: #1428A0;
    font-weight: 600;
}
.imv2-step.done {
    color: #28a745;
    border-bottom-color: #28a745;
}
.imv2-step-num {
    display: inline-block;
    width: 22px; height: 22px;
    border-radius: 50%;
    background: #ddd;
    color: #fff;
    font-size: 12px;
    line-height: 22px;
    text-align: center;
    margin-right: 4px;
}
.imv2-step.active .imv2-step-num { background: #1428A0; }
.imv2-step.done .imv2-step-num { background: #28a745; }

/* 바디 */
.imv2-body {
    padding: 20px 24px;
    overflow-y: auto;
    flex: 1;
    min-height: 300px;
}

/* 패널 (각 스텝) */
.imv2-panel { animation: imv2FadeIn 0.2s ease; }
@keyframes imv2FadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

/* 인포 박스 */
.imv2-info {
    background: #e8f0fe;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 16px;
}
.imv2-info p { margin: 4px 0; font-size: 13px; color: #1a56db; }

/* 다운로드 영역 */
.imv2-downloads {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 16px;
    flex-wrap: wrap;
}
.imv2-dl-label { font-size: 13px; color: #666; font-weight: 500; }
.imv2-dl-btn {
    display: inline-block;
    padding: 6px 14px;
    background: #f0f0f0;
    border-radius: 6px;
    font-size: 12px;
    color: #333;
    text-decoration: none;
    transition: background 0.2s;
}
.imv2-dl-btn:hover { background: #e0e0e0; }

/* 드롭존 */
.imv2-dropzone {
    border: 2px dashed #ccc;
    border-radius: 12px;
    padding: 40px 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
    background: #fafafa;
}
.imv2-dropzone:hover, .imv2-dropzone.drag-over {
    border-color: #1428A0;
    background: #f0f4ff;
}
.imv2-drop-icon { margin-bottom: 12px; }
.imv2-drop-content p { margin: 0; color: #666; }
.imv2-hint { font-size: 12px; color: #999; margin-top: 4px; display: inline-block; }

/* 파일 정보 */
.imv2-file-info {
    margin-top: 12px;
    padding: 10px 16px;
    background: #d4edda;
    border-radius: 6px;
    color: #155724;
    font-size: 14px;
    font-weight: 500;
}

/* 로딩 */
.imv2-loading { text-align: center; padding: 40px 0; }
.imv2-spinner {
    width: 36px; height: 36px;
    border: 3px solid #e9ecef;
    border-top-color: #1428A0;
    border-radius: 50%;
    animation: imv2Spin 0.8s linear infinite;
    margin: 0 auto 12px;
}
@keyframes imv2Spin { to { transform: rotate(360deg); } }

/* 요약 뱃지 */
.imv2-summary {
    display: flex;
    gap: 10px;
    margin-bottom: 16px;
    flex-wrap: wrap;
}
.imv2-badge {
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
}
.imv2-badge.insert { background: #d4edda; color: #155724; }
.imv2-badge.update { background: #fff3cd; color: #856404; }
.imv2-badge.delete { background: #f8d7da; color: #721c24; }
.imv2-badge.error  { background: #f8d7da; color: #721c24; }
.imv2-badge.total  { background: #e2e3e5; color: #383d41; }

.imv2-format-info {
    font-size: 12px; color: #888; margin-bottom: 12px;
}

/* 테이블 */
.imv2-table-wrap {
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #e9ecef;
    border-radius: 6px;
}
.imv2-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.imv2-table th, .imv2-table td {
    padding: 8px 10px;
    text-align: left;
    border-bottom: 1px solid #f0f0f0;
}
.imv2-table th {
    background: #f8f9fa;
    font-weight: 600;
    position: sticky;
    top: 0;
    z-index: 1;
}
.imv2-table tr.expandable { cursor: pointer; }
.imv2-table tr.expandable:hover { background: #f5f7ff; }
.imv2-table tr.row-error { background: #fff8e1; }

/* 작업 뱃지 */
.imv2-action {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    color: #fff;
}
.imv2-action.INSERT { background: #28a745; }
.imv2-action.UPDATE { background: #ffc107; color: #333; }
.imv2-action.DELETE { background: #dc3545; }
.imv2-action.ERROR  { background: #6c757d; }

/* Diff 행 */
.imv2-diff-row td { padding: 0; border: none; }
.imv2-diff-wrap {
    padding: 8px 16px;
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
}
.imv2-diff-item {
    display: flex;
    gap: 8px;
    font-size: 12px;
    padding: 3px 0;
    align-items: baseline;
}
.imv2-diff-field { font-weight: 600; color: #333; min-width: 120px; }
.imv2-diff-before { color: #dc3545; text-decoration: line-through; word-break: break-all; }
.imv2-diff-arrow { color: #999; }
.imv2-diff-after { color: #28a745; font-weight: 500; word-break: break-all; }

/* 오류 목록 */
.imv2-errors {
    margin-top: 16px;
    padding: 12px 16px;
    background: #fff3cd;
    border-radius: 6px;
}
.imv2-errors h5 { margin: 0 0 8px; color: #856404; font-size: 14px; }
.imv2-errors ul { margin: 0; padding-left: 20px; font-size: 13px; color: #856404; }

/* 확인 (Step 3) */
.imv2-confirm-summary {
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    text-align: center;
    margin-bottom: 16px;
}
.imv2-confirm-summary h4 { margin: 0 0 16px; font-size: 16px; }
.imv2-confirm-summary .imv2-summary { justify-content: center; }
.imv2-confirm-warning {
    padding: 12px 16px;
    background: #fff3cd;
    border: 1px solid #ffc107;
    border-radius: 6px;
    font-size: 13px;
    color: #856404;
}

/* 진행률 (Step 4) */
.imv2-progress { text-align: center; }
.imv2-progress-bar {
    height: 8px;
    background: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 12px;
}
.imv2-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #1428A0, #2196f3);
    width: 0%;
    transition: width 0.4s ease;
}
.imv2-progress-text { font-size: 14px; color: #666; }

/* 결과 (Step 4) */
.imv2-result { text-align: center; padding: 20px 0; }
.imv2-result-icon { font-size: 48px; margin-bottom: 12px; }
.imv2-result h4 { margin: 0 0 16px; font-size: 18px; }
.imv2-result .imv2-summary { justify-content: center; }
.imv2-result-errors {
    margin-top: 16px;
    text-align: left;
    max-height: 150px;
    overflow-y: auto;
    background: #f8f9fa;
    padding: 12px;
    border-radius: 6px;
    font-size: 13px;
}
.imv2-result-history {
    margin-top: 12px;
    font-size: 13px;
    color: #888;
}

/* 푸터 */
.imv2-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 24px;
    border-top: 1px solid #e9ecef;
}
.imv2-footer-right { display: flex; gap: 8px; }

/* 버튼 */
.imv2-btn {
    padding: 10px 22px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
}
.imv2-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.imv2-btn-primary { background: #1428A0; color: #fff; }
.imv2-btn-primary:hover:not(:disabled) { background: #0F1F7A; }
.imv2-btn-secondary { background: #f0f0f0; color: #333; }
.imv2-btn-secondary:hover { background: #e0e0e0; }
.imv2-btn-danger { background: #dc3545; color: #fff; }
.imv2-btn-danger:hover:not(:disabled) { background: #c82333; }
.imv2-btn-success { background: #28a745; color: #fff; }
.imv2-btn-success:hover:not(:disabled) { background: #218838; }

@media (max-width: 640px) {
    .imv2-modal { width: 100%; max-width: 100%; max-height: 100vh; border-radius: 0; }
    .imv2-steps { font-size: 11px; }
    .imv2-step { padding: 10px 4px; }
    .imv2-downloads { flex-direction: column; }
}
</style>

<script>
const ImV2 = (() => {
    let currentStep = 1;
    let selectedFile = null;
    let previewData = null;
    const csrfToken = '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>';
    const categoryFilter = '<?php echo htmlspecialchars($modal_category); ?>';

    function open() {
        document.getElementById('importModalV2').style.display = 'flex';
        document.body.style.overflow = 'hidden';
        reset();
    }

    function close() {
        document.getElementById('importModalV2').style.display = 'none';
        document.body.style.overflow = '';
    }

    function reset() {
        currentStep = 1;
        selectedFile = null;
        previewData = null;
        showStep(1);
        document.getElementById('imv2FileInput').value = '';
        document.getElementById('imv2FileInfo').style.display = 'none';
        document.getElementById('imv2BtnNext').disabled = true;
        document.getElementById('imv2BtnNext').textContent = '다음: 미리보기';
        document.getElementById('imv2BtnPrev').style.display = 'none';
    }

    function showStep(step) {
        currentStep = step;
        for (let i = 1; i <= 4; i++) {
            document.getElementById('imv2Step' + i).style.display = (i === step) ? 'block' : 'none';
        }
        // 스텝 인디케이터 업데이트
        document.querySelectorAll('.imv2-step').forEach(el => {
            const s = parseInt(el.dataset.step);
            el.classList.remove('active', 'done');
            if (s === step) el.classList.add('active');
            else if (s < step) el.classList.add('done');
        });
        // 버튼 상태
        const btnNext = document.getElementById('imv2BtnNext');
        const btnPrev = document.getElementById('imv2BtnPrev');
        btnPrev.style.display = (step > 1 && step < 4) ? 'inline-block' : 'none';
        switch (step) {
            case 1:
                btnNext.textContent = '다음: 미리보기';
                btnNext.className = 'imv2-btn imv2-btn-primary';
                btnNext.disabled = !selectedFile;
                btnNext.onclick = () => next();
                break;
            case 2:
                btnNext.textContent = '다음: 확인';
                btnNext.className = 'imv2-btn imv2-btn-primary';
                btnNext.disabled = !previewData || previewData.has_errors;
                btnNext.onclick = () => next();
                break;
            case 3:
                btnNext.textContent = '적용 실행';
                btnNext.className = 'imv2-btn imv2-btn-danger';
                btnNext.disabled = false;
                btnNext.onclick = () => next();
                break;
            case 4:
                btnNext.textContent = '닫기 & 새로고침';
                btnNext.className = 'imv2-btn imv2-btn-success';
                btnNext.disabled = false;
                btnNext.onclick = () => { close(); location.reload(); };
                break;
        }
    }

    function next() {
        if (currentStep === 1) {
            goToPreview();
        } else if (currentStep === 2) {
            goToConfirm();
        } else if (currentStep === 3) {
            executeImport();
        }
    }

    function prev() {
        if (currentStep > 1 && currentStep < 4) {
            showStep(currentStep - 1);
        }
    }

    // ─── 파일 선택 ───
    function handleFileSelect(file) {
        if (!file) return;
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
        const info = document.getElementById('imv2FileInfo');
        info.textContent = file.name + ' (' + formatSize(file.size) + ')';
        info.style.display = 'block';
        document.getElementById('imv2BtnNext').disabled = false;
        previewData = null;
    }

    function formatSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    // ─── 미리보기 (Step 2) ───
    function goToPreview() {
        if (!selectedFile) return;
        showStep(2);
        document.getElementById('imv2Loading').style.display = 'block';
        document.getElementById('imv2Summary').innerHTML = '';
        document.getElementById('imv2TableBody').innerHTML = '';
        document.getElementById('imv2Errors').style.display = 'none';
        document.getElementById('imv2BtnNext').disabled = true;

        const fd = new FormData();
        fd.append('preview_file', selectedFile);
        fd.append('csrf_token', csrfToken);

        fetch('ajax/products_preview_v2.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                document.getElementById('imv2Loading').style.display = 'none';
                if (!data.success) {
                    alert(data.error || '미리보기 실패');
                    showStep(1);
                    return;
                }
                previewData = data;
                renderPreview(data);
                document.getElementById('imv2BtnNext').disabled = data.has_errors;
            })
            .catch(err => {
                document.getElementById('imv2Loading').style.display = 'none';
                alert('서버 오류가 발생했습니다.');
                console.error(err);
                showStep(1);
            });
    }

    function renderPreview(data) {
        // 포맷 정보
        document.getElementById('imv2FormatInfo').textContent =
            '감지된 포맷: ' + data.format.toUpperCase() + ' (' + data.column_count + '컬럼)';

        // 요약
        const sum = document.getElementById('imv2Summary');
        sum.innerHTML =
            `<div class="imv2-badge total">${data.total_rows}건</div>` +
            (data.summary.insert > 0 ? `<div class="imv2-badge insert">+${data.summary.insert} 추가</div>` : '') +
            (data.summary.update > 0 ? `<div class="imv2-badge update">~${data.summary.update} 수정</div>` : '') +
            (data.summary.delete > 0 ? `<div class="imv2-badge delete">-${data.summary.delete} 삭제</div>` : '') +
            (data.summary.error > 0 ? `<div class="imv2-badge error">!${data.summary.error} 오류</div>` : '');

        // 테이블
        const tbody = document.getElementById('imv2TableBody');
        tbody.innerHTML = '';
        data.preview_data.forEach((row, idx) => {
            const hasError = row.errors && row.errors.length > 0;
            const hasDiff = row.diff && Object.keys(row.diff).length > 0;
            const tr = document.createElement('tr');
            if (hasError) tr.classList.add('row-error');
            if (hasDiff) tr.classList.add('expandable');
            tr.innerHTML = `
                <td>${row.line}</td>
                <td>${row.id || '-'}</td>
                <td>${escHtml(row.name) || '-'}</td>
                <td>${escHtml(row.spec) || '-'}</td>
                <td><span class="imv2-action ${row.action}">${row.action}</span></td>
                <td>${hasError ? '!!' : (hasDiff ? '...' : 'OK')}</td>
            `;
            if (hasDiff) {
                tr.onclick = () => toggleDiff('diff-' + idx);
            }
            tbody.appendChild(tr);

            // diff 행
            if (hasDiff) {
                const diffTr = document.createElement('tr');
                diffTr.id = 'diff-' + idx;
                diffTr.style.display = 'none';
                diffTr.classList.add('imv2-diff-row');
                let diffHtml = '<td colspan="6"><div class="imv2-diff-wrap">';
                for (const [field, change] of Object.entries(row.diff)) {
                    diffHtml += `<div class="imv2-diff-item">
                        <span class="imv2-diff-field">${escHtml(field)}</span>
                        <span class="imv2-diff-before">${escHtml(truncate(change.before, 60))}</span>
                        <span class="imv2-diff-arrow">&rarr;</span>
                        <span class="imv2-diff-after">${escHtml(truncate(change.after, 60))}</span>
                    </div>`;
                }
                diffHtml += '</div></td>';
                diffTr.innerHTML = diffHtml;
                tbody.appendChild(diffTr);
            }

            // 오류 표시 행
            if (hasError && !hasDiff) {
                const errTr = document.createElement('tr');
                errTr.classList.add('imv2-diff-row');
                errTr.innerHTML = `<td colspan="6"><div class="imv2-diff-wrap" style="background:#fff3cd;color:#856404;">${row.errors.join(', ')}</div></td>`;
                tbody.appendChild(errTr);
            }
        });

        // 오류 목록
        if (data.validation_errors && data.validation_errors.length > 0) {
            document.getElementById('imv2Errors').style.display = 'block';
            const ul = document.getElementById('imv2ErrorList');
            ul.innerHTML = '';
            data.validation_errors.slice(0, 20).forEach(err => {
                const li = document.createElement('li');
                li.textContent = `라인 ${err.line}: ${err.errors.join(', ')}`;
                ul.appendChild(li);
            });
            if (data.validation_errors.length > 20) {
                const li = document.createElement('li');
                li.textContent = `... 외 ${data.validation_errors.length - 20}건`;
                ul.appendChild(li);
            }
        }
    }

    function toggleDiff(id) {
        const el = document.getElementById(id);
        if (el) el.style.display = el.style.display === 'none' ? '' : 'none';
    }

    // ─── 확인 (Step 3) ───
    function goToConfirm() {
        showStep(3);
        const d = previewData;
        const html = `
            <h4>다음 변경사항을 적용하시겠습니까?</h4>
            <div class="imv2-summary">
                ${d.summary.insert > 0 ? `<div class="imv2-badge insert">+${d.summary.insert} 추가</div>` : ''}
                ${d.summary.update > 0 ? `<div class="imv2-badge update">~${d.summary.update} 수정</div>` : ''}
                ${d.summary.delete > 0 ? `<div class="imv2-badge delete">-${d.summary.delete} 삭제</div>` : ''}
            </div>
        `;
        document.getElementById('imv2ConfirmSummary').innerHTML = html;
        document.getElementById('imv2ConfirmWarning').style.display = d.summary.delete > 0 ? 'block' : 'none';
    }

    // ─── 임포트 실행 (Step 4) ───
    function executeImport() {
        showStep(4);
        document.getElementById('imv2Progress').style.display = 'block';
        document.getElementById('imv2Result').style.display = 'none';
        document.getElementById('imv2ProgressFill').style.width = '30%';
        document.getElementById('imv2ProgressText').textContent = '파일 업로드 중...';
        document.getElementById('imv2BtnNext').disabled = true;

        const fd = new FormData();
        fd.append('import_file', selectedFile);
        fd.append('csrf_token', csrfToken);

        // 진행률 시뮬레이션
        let pct = 30;
        const timer = setInterval(() => {
            pct = Math.min(pct + 5, 85);
            document.getElementById('imv2ProgressFill').style.width = pct + '%';
            if (pct >= 50) document.getElementById('imv2ProgressText').textContent = '데이터 처리 중...';
        }, 400);

        fetch('ajax/products_import_v2.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                clearInterval(timer);
                document.getElementById('imv2ProgressFill').style.width = '100%';
                document.getElementById('imv2ProgressText').textContent = '완료!';

                setTimeout(() => {
                    document.getElementById('imv2Progress').style.display = 'none';
                    renderResult(data);
                }, 500);
            })
            .catch(err => {
                clearInterval(timer);
                document.getElementById('imv2ProgressFill').style.width = '100%';
                document.getElementById('imv2ProgressText').textContent = '오류 발생';
                console.error(err);
                setTimeout(() => {
                    document.getElementById('imv2Progress').style.display = 'none';
                    renderResult({ success: false, error: '서버 오류가 발생했습니다.' });
                }, 500);
            });
    }

    function renderResult(data) {
        const div = document.getElementById('imv2Result');
        div.style.display = 'block';
        document.getElementById('imv2BtnNext').disabled = false;

        if (data.success) {
            let html = `
                <div class="imv2-result-icon" style="color:#28a745;">&#10004;</div>
                <h4>임포트 완료</h4>
                <div class="imv2-summary">
                    ${data.created > 0 ? `<div class="imv2-badge insert">+${data.created} 추가</div>` : ''}
                    ${data.updated > 0 ? `<div class="imv2-badge update">~${data.updated} 수정</div>` : ''}
                    ${data.deleted > 0 ? `<div class="imv2-badge delete">-${data.deleted} 삭제</div>` : ''}
                    ${data.error_count > 0 ? `<div class="imv2-badge error">!${data.error_count} 오류</div>` : ''}
                </div>
            `;
            if (data.errors && data.errors.length > 0) {
                html += '<div class="imv2-result-errors">';
                data.errors.slice(0, 10).forEach(e => { html += '<div>' + escHtml(e) + '</div>'; });
                if (data.errors.length > 10) html += `<div>... 외 ${data.errors.length - 10}건</div>`;
                html += '</div>';
            }
            if (data.history_id) {
                html += `<div class="imv2-result-history">임포트 이력 ID: #${data.history_id}</div>`;
            }
            div.innerHTML = html;
        } else {
            div.innerHTML = `
                <div class="imv2-result-icon" style="color:#dc3545;">&#10008;</div>
                <h4>임포트 실패</h4>
                <p style="color:#dc3545;">${escHtml(data.error || '알 수 없는 오류')}</p>
            `;
        }
    }

    // 유틸
    function escHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function truncate(str, len) {
        if (!str) return '';
        return str.length > len ? str.substring(0, len) + '...' : str;
    }

    // ─── 이벤트 바인딩 ───
    document.addEventListener('DOMContentLoaded', () => {
        const dropzone = document.getElementById('imv2Dropzone');
        const fileInput = document.getElementById('imv2FileInput');

        dropzone.addEventListener('click', () => fileInput.click());
        dropzone.addEventListener('dragover', e => { e.preventDefault(); dropzone.classList.add('drag-over'); });
        dropzone.addEventListener('dragleave', () => dropzone.classList.remove('drag-over'));
        dropzone.addEventListener('drop', e => {
            e.preventDefault();
            dropzone.classList.remove('drag-over');
            if (e.dataTransfer.files.length > 0) handleFileSelect(e.dataTransfer.files[0]);
        });
        fileInput.addEventListener('change', e => {
            if (e.target.files.length > 0) handleFileSelect(e.target.files[0]);
        });
    });

    return { open, close, next, prev };
})();
</script>
