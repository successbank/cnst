// @ts-check
const { test, expect } = require('@playwright/test');
const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const ENV_PATH = '/home/cnst/www/html/webservice/.env';
const BASE_URL = process.env.BASE_URL || 'https://cnst.co.kr';

// ========== DB 헬퍼 ==========
function getDbRootPassword() {
    const envContent = fs.readFileSync(ENV_PATH, 'utf8');
    const match = envContent.match(/MYSQL_ROOT_PASSWORD=(.+)/);
    if (!match) throw new Error('MYSQL_ROOT_PASSWORD not found in .env');
    return match[1].trim();
}

function runSQL(sql) {
    const password = getDbRootPassword();
    const escaped = sql.replace(/'/g, "'\\''");
    return execSync(
        `docker exec project1_mysql mysql -u root -p'${password}' project1_db -N -e '${escaped}'`,
        { encoding: 'utf8', timeout: 10000 }
    ).trim();
}

// 테스트용 pending 의뢰 ID 조회
function getPendingTestId() {
    const result = runSQL("SELECT id FROM board_consignment WHERE title = '[UITEST] UI테스트 승인대기 의뢰' AND status = 'pending' ORDER BY id DESC LIMIT 1");
    return parseInt(result, 10);
}

// ========== 테스트 ==========
test.describe.serial('카카오 알림톡 UI 테스트 (시나리오 33~40)', () => {

    // CSP 리포트가 nginx API rate limit을 소진하지 않도록 차단
    test.beforeEach(async ({ page }) => {
        await page.route('**/csp_report**', route => route.abort());
    });

    // ===== 그룹 6: 관리자 로그인 + 목록 =====

    test('#33 관리자 로그인 후 판매의뢰 목록 페이지 접근', async ({ page }) => {
        await page.goto(BASE_URL + '/admin/admin_consignment.php');

        // 테이블 존재 확인
        const table = page.locator('.data-table table');
        await expect(table).toBeVisible({ timeout: 10000 });

        // '발송' 컬럼 헤더 존재 확인
        const headerRow = page.locator('.data-table table thead tr');
        const headerText = await headerRow.textContent();
        expect(headerText).toContain('발송');

        await page.screenshot({ path: path.join(__dirname, 'screenshots', '33_list_page.png') });
    });

    test('#34 목록에서 발송 상태 배지 표시', async ({ page }) => {
        await page.goto(BASE_URL + '/admin/admin_consignment.php');

        // 배지 중 하나라도 표시되는지 확인 (sent/failed/pending/-)
        // [UITEST] approved 의뢰는 kakao_send_status='sent' → '발송' 배지
        // [UITEST] rejected 의뢰는 kakao_send_status='failed' → '실패' 배지
        const badges = page.locator('.kakao-badge');
        const badgeCount = await badges.count();

        // 최소 1개 이상의 배지가 있어야 함 (sent 또는 failed)
        expect(badgeCount).toBeGreaterThanOrEqual(1);

        // 발송 또는 실패 배지 텍스트 확인
        const allBadgeTexts = await badges.allTextContents();
        const validTexts = ['발송', '실패', '대기'];
        const hasValidBadge = allBadgeTexts.some(t => validTexts.includes(t.trim()));
        expect(hasValidBadge).toBeTruthy();

        await page.screenshot({ path: path.join(__dirname, 'screenshots', '34_badges.png') });
    });

    test('#35 목록에서 상세 페이지 이동', async ({ page }) => {
        await page.goto(BASE_URL + '/admin/admin_consignment.php');

        // [UITEST] 의뢰의 '보기' 버튼 클릭
        // 먼저 UITEST 의뢰가 있는 행 찾기
        const viewLinks = page.locator('a.btn-view');
        const count = await viewLinks.count();
        expect(count).toBeGreaterThan(0);

        // 첫 번째 '보기' 클릭
        await viewLinks.first().click();

        // admin_consignment_view.php로 이동 확인
        await page.waitForURL('**/admin_consignment_view.php?id=*', { timeout: 10000 });
        expect(page.url()).toContain('admin_consignment_view.php');

        await page.screenshot({ path: path.join(__dirname, 'screenshots', '35_detail_page.png') });
    });

    // ===== 그룹 7: 승인 모달 테스트 =====

    test('#36 승인 모달 열기', async ({ page }) => {
        // pending 상태의 UITEST 의뢰 상세 페이지로 이동
        const pendingId = getPendingTestId();
        expect(pendingId).toBeGreaterThan(0);

        await page.goto(BASE_URL + '/admin/admin_consignment_view.php?id=' + pendingId);

        // 승인 버튼 클릭
        const approveBtn = page.locator('.btn-approve').first();
        await expect(approveBtn).toBeVisible();
        await approveBtn.click();

        // 모달 visible 확인
        const modal = page.locator('#approveModal');
        await expect(modal).toBeVisible();

        // 의뢰 요약 정보 표시 확인
        const summary = modal.locator('.approve-summary');
        await expect(summary).toBeVisible();
        const summaryText = await summary.textContent();
        expect(summaryText).toContain('UITEST');

        await page.screenshot({ path: path.join(__dirname, 'screenshots', '36_approve_modal.png') });
    });

    test('#37 금액 입력 + 천단위 콤마 자동 포맷', async ({ page }) => {
        const pendingId = getPendingTestId();
        await page.goto(BASE_URL + '/admin/admin_consignment_view.php?id=' + pendingId);

        // 모달 열기
        await page.locator('.btn-approve').first().click();
        await expect(page.locator('#approveModal')).toBeVisible();

        // 금액 입력
        const priceInput = page.locator('#approvedPrice');
        await priceInput.fill('1500000');

        // oninput 이벤트 트리거 (formatPrice)
        await priceInput.dispatchEvent('input');

        // 천단위 콤마 확인
        const formatted = await priceInput.inputValue();
        expect(formatted).toBe('1,500,000');

        await page.screenshot({ path: path.join(__dirname, 'screenshots', '37_price_format.png') });
    });

    test('#38 금액 미입력 후 승인 시도', async ({ page }) => {
        const pendingId = getPendingTestId();
        await page.goto(BASE_URL + '/admin/admin_consignment_view.php?id=' + pendingId);

        // 모달 열기
        await page.locator('.btn-approve').first().click();
        await expect(page.locator('#approveModal')).toBeVisible();

        // 금액 비우기
        const priceInput = page.locator('#approvedPrice');
        await priceInput.fill('');

        // alert 대화상자 캡처
        let alertMessage = '';
        page.on('dialog', async (dialog) => {
            alertMessage = dialog.message();
            await dialog.dismiss();
        });

        // 승인 버튼 클릭 (모달 내부의 승인 버튼)
        await page.locator('#approveModal .btn-approve').click();

        // 짧은 대기 후 alert 메시지 확인
        await page.waitForTimeout(500);
        expect(alertMessage).toContain('금액');

        await page.screenshot({ path: path.join(__dirname, 'screenshots', '38_empty_price_alert.png') });
    });

    test('#39 미리보기 클릭 후 메시지 미리보기 표시', async ({ page }) => {
        const pendingId = getPendingTestId();
        await page.goto(BASE_URL + '/admin/admin_consignment_view.php?id=' + pendingId);

        // 모달 열기
        await page.locator('.btn-approve').first().click();
        await expect(page.locator('#approveModal')).toBeVisible();

        // 금액 입력
        const priceInput = page.locator('#approvedPrice');
        await priceInput.fill('2000000');
        await priceInput.dispatchEvent('input');

        // dialog 핸들러 (alert 캡처)
        page.on('dialog', async (dialog) => {
            await dialog.accept();
        });

        // 미리보기 버튼 클릭 + API 응답 대기
        const [response] = await Promise.all([
            page.waitForResponse(
                resp => resp.url().includes('preview_consignment_message'),
                { timeout: 15000 }
            ),
            page.locator('#approveModal button', { hasText: '미리보기' }).click()
        ]);

        // 응답 확인
        expect(response.status()).toBe(200);
        const responseBody = await response.json();
        expect(responseBody.success).toBeTruthy();

        // 미리보기 영역 표시 확인
        const previewSection = page.locator('#approvePreview');
        await expect(previewSection).toBeVisible({ timeout: 10000 });

        // 미리보기 내용 검증
        const previewText = await page.locator('#approvePreviewContent').textContent();
        expect(previewText).toContain('2,000,000');
        expect(previewText).toContain('UI담당자');

        const metaText = await page.locator('#approvePreviewMeta').textContent();
        expect(metaText).toContain('수신자');

        await page.screenshot({ path: path.join(__dirname, 'screenshots', '39_preview.png') });
    });

    test('#40 승인 완료 후 페이지 리로드 및 상태 확인', async ({ page }) => {
        const pendingId = getPendingTestId();
        if (!pendingId || isNaN(pendingId)) {
            test.skip(true, '승인 대기 의뢰를 찾을 수 없음');
            return;
        }

        await page.goto(BASE_URL + '/admin/admin_consignment_view.php?id=' + pendingId);

        // 모달 열기
        await page.locator('.btn-approve').first().click();
        await expect(page.locator('#approveModal')).toBeVisible();

        // 금액 입력
        const priceInput = page.locator('#approvedPrice');
        await priceInput.fill('3000000');
        await priceInput.dispatchEvent('input');

        // confirm + alert 대화상자 처리
        page.on('dialog', async (dialog) => {
            await dialog.accept();
        });

        // 승인 버튼 클릭 + API 응답 대기
        const [response] = await Promise.all([
            page.waitForResponse(
                resp => resp.url().includes('admin_review_consignment'),
                { timeout: 15000 }
            ),
            page.locator('#approveModal .btn-approve').click()
        ]);

        // 승인 API 응답 확인
        expect(response.status()).toBe(200);
        const responseBody = await response.json();
        expect(responseBody.success).toBeTruthy();

        // location.reload() 대기 (alert 수락 후 리로드 발생)
        await page.waitForURL('**/admin_consignment_view.php?id=' + pendingId, { timeout: 15000 });
        await page.waitForLoadState('domcontentloaded');

        // 상태 표시 확인: '승인(게시중)' 텍스트
        const pageContent = await page.textContent('body');
        expect(pageContent).toContain('승인');

        // 금액 표시 확인
        expect(pageContent).toContain('3,000,000');

        // 카카오 배지 표시 확인 (발송완료 or 테스트 모드)
        const hasSendStatus = pageContent.includes('발송완료') || pageContent.includes('발송대기') || pageContent.includes('알림톡');
        expect(hasSendStatus).toBeTruthy();

        // 발송 이력 테이블 존재 확인 (AJAX 로드)
        await page.waitForTimeout(2000);
        const historyContainer = page.locator('#kakaoHistoryContainer');
        const historyText = await historyContainer.textContent();
        const hasHistory = historyText.includes('발송완료') || historyText.includes('대기') || historyText.includes('이력');
        expect(hasHistory).toBeTruthy();

        await page.screenshot({ path: path.join(__dirname, 'screenshots', '40_approved_result.png') });

        // DB 상태 최종 검증
        const dbStatus = runSQL(`SELECT status, approved_price, kakao_send_status FROM board_consignment WHERE id = ${pendingId}`);
        expect(dbStatus).toContain('approved');
        expect(dbStatus).toContain('3000000');
    });

    // ===== 클린업 =====

    test.afterAll(async () => {
        try {
            // 테스트 알림 삭제
            runSQL("DELETE FROM kakao_notifications WHERE board_type = 'consignment' AND board_id IN (SELECT id FROM board_consignment WHERE title LIKE '[UITEST]%')");
            // 테스트 의뢰 삭제
            runSQL("DELETE FROM board_consignment WHERE title LIKE '[UITEST]%'");
            // 테스트 관리자 삭제
            runSQL("DELETE FROM admin_users WHERE username = 'test_admin_kakao'");
            // 로그인 로그 삭제
            runSQL("DELETE FROM admin_login_logs WHERE admin_username = 'test_admin_kakao'");
            // auth.json 삭제
            const authFile = path.join(__dirname, 'auth.json');
            if (fs.existsSync(authFile)) fs.unlinkSync(authFile);
            console.log('  [cleanup] 테스트 데이터 정리 완료');
        } catch (e) {
            console.error('  [cleanup] 정리 중 오류:', e.message);
        }
    });
});
