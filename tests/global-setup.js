// @ts-check
const { test: setup, expect } = require('@playwright/test');
const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const ENV_PATH = '/home/cnst/www/html/webservice/.env';
const AUTH_FILE = path.join(__dirname, 'auth.json');

const TEST_ADMIN_USER = 'test_admin_kakao';
const TEST_ADMIN_PASS = 'KakaoTest2026!';

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

function phpExec(code) {
    return execSync(
        `docker exec project1_php php -r "${code.replace(/"/g, '\\"')}"`,
        { encoding: 'utf8', timeout: 10000 }
    ).trim();
}

setup('글로벌 설정: 테스트 데이터 생성 + 관리자 로그인', async ({ browser }) => {
    // 1. 테스트 관리자 계정 생성
    const passwordHash = phpExec(
        "echo password_hash('KakaoTest2026!', PASSWORD_BCRYPT);"
    );

    // 기존 테스트 관리자 삭제 후 재생성
    runSQL(`DELETE FROM admin_users WHERE username = '${TEST_ADMIN_USER}'`);
    runSQL(
        `INSERT INTO admin_users (username, password_hash, totp_enabled) VALUES ('${TEST_ADMIN_USER}', '${passwordHash}', 0)`
    );
    console.log('  [setup] 테스트 관리자 계정 생성 완료');

    // 2. 테스트 판매의뢰 생성 (3건: pending, approved+sent, rejected+failed)
    // 기존 테스트 데이터 정리
    runSQL("DELETE FROM kakao_notifications WHERE board_type = 'consignment' AND board_id IN (SELECT id FROM board_consignment WHERE title LIKE '[UITEST]%')");
    runSQL("DELETE FROM board_consignment WHERE title LIKE '[UITEST]%'");

    const bcryptPw = phpExec("echo password_hash('uitest', PASSWORD_BCRYPT);");

    // 2a. Pending 의뢰 (승인 플로우 테스트용)
    runSQL(`INSERT INTO board_consignment (title, content, writer, password, company_name, category, item_type, stock_quantity, price_info, contact_person, contact_phone, contact_email, location, status) VALUES ('[UITEST] UI테스트 승인대기 의뢰', 'UI 테스트용 판매의뢰입니다.', 'UI테스터', '${bcryptPw}', 'UI테스트(주)', 'H형강', 'steel', '30톤', '협의', 'UI담당자', '01012349999', 'uitest@test.com', '인천시 서구', 'pending')`);

    // 2b. Approved + sent (배지 테스트용)
    runSQL(`INSERT INTO board_consignment (title, content, writer, password, company_name, category, item_type, stock_quantity, price_info, contact_person, contact_phone, status, approved_price, kakao_send_status, reviewed_by, reviewed_at) VALUES ('[UITEST] UI테스트 승인완료', '승인완료 의뢰', 'UI테스터', '${bcryptPw}', 'UI테스트(주)', '앵글', 'steel', '10톤', '100만원', 'UI담당자', '01055551234', 'approved', 1000000, 'sent', 1, NOW())`);

    // 2c. Rejected + failed (배지 테스트용)
    runSQL(`INSERT INTO board_consignment (title, content, writer, password, company_name, category, item_type, stock_quantity, price_info, contact_person, contact_phone, status, reject_reason, kakao_send_status, reviewed_by, reviewed_at) VALUES ('[UITEST] UI테스트 거절됨', '거절된 의뢰', 'UI테스터', '${bcryptPw}', 'UI테스트(주)', 'C형강', 'steel', '5톤', '50만원', 'UI담당자', '01077778888', 'rejected', '테스트 거절사유', 'failed', 1, NOW())`);

    console.log('  [setup] 테스트 판매의뢰 3건 생성 완료');

    // 3. 관리자 로그인 → storageState 저장
    const context = await browser.newContext({ ignoreHTTPSErrors: true });
    const page = await context.newPage();

    const baseURL = process.env.BASE_URL || 'https://cnst.co.kr';
    // CSP 리포트가 nginx API rate limit을 소진하지 않도록 차단
    await page.route('**/csp_report**', route => route.abort());
    await page.goto(baseURL + '/admin/admin_login.php');

    await page.fill('#admin_id', TEST_ADMIN_USER);
    await page.fill('#admin_pw', TEST_ADMIN_PASS);
    await page.click('.login-btn');

    // 로그인 성공 확인 (admin_index.php 또는 admin 페이지로 이동)
    await page.waitForURL('**/admin/**', { timeout: 15000 });
    console.log('  [setup] 관리자 로그인 성공: ' + page.url());

    // storageState 저장
    await context.storageState({ path: AUTH_FILE });
    await context.close();

    console.log('  [setup] 인증 상태 저장 완료: ' + AUTH_FILE);
});
