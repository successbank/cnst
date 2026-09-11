<?php
/**
 * 제품 상세페이지 라우터
 *
 * 관리자 > 사이트 관리 의 '제품 상세페이지 모드' 설정에 따라
 * 아래 두 화면 중 하나를 로드한다. URL 은 기존과 동일하게 유지된다.
 *
 *   calc  (기본값) : product_detail_calc.php  - 기존 자동계산 방식 (내용 무변경)
 *   quote           : product_detail_v2.php    - 신규 견적요청 방식
 *
 * 안전 원칙
 *  - 설정값이 없거나 알 수 없는 값이면 항상 기존 방식(calc)으로 동작한다.
 *  - ?category= 레거시 계산기 페이지는 모드와 무관하게 항상 기존 파일로 보낸다.
 *  - 관리자 로그인 상태에서 ?preview=calc|quote 로 설정을 무시하고 미리 볼 수 있다.
 *
 * 문서: dev_docs/PRD_product_quote_v2.md
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/settings.php';

/**
 * 현재 제품 상세페이지 모드를 반환한다.
 *
 * getSetting() 은 행이 존재하면 빈 문자열도 그대로 반환하므로(includes/settings.php)
 * 반드시 ?: 로 기본값을 보정해야 한다.
 *
 * @return string 'calc' 또는 'quote'
 */
function getProductDetailMode()
{
    $mode = getSetting('product_detail_mode') ?: 'calc';
    return in_array($mode, ['calc', 'quote'], true) ? $mode : 'calc';
}

$__pd_mode = getProductDetailMode();

// 관리자 미리보기: 설정을 바꾸지 않고 신규/기존 화면을 확인한다.
if (isset($_GET['preview'])) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $__pd_is_admin = (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true)
        || (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1);

    if ($__pd_is_admin && in_array($_GET['preview'], ['calc', 'quote'], true)) {
        $__pd_mode = $_GET['preview'];
    }
}

// ?category= 레거시 계산기 페이지는 신규 방식에 대응 화면이 없으므로 항상 기존 파일로 보낸다.
if (empty($_GET['id']) && !empty($_GET['category'])) {
    $__pd_mode = 'calc';
}

if ($__pd_mode === 'quote' && !empty($_GET['id'])) {
    require __DIR__ . '/product_detail_v2.php';
} else {
    require __DIR__ . '/product_detail_calc.php';
}
