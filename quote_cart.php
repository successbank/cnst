<?php
/**
 * 견적 장바구니 (회원·비회원 공용)
 *
 * 비회원도 사용하는 페이지이므로 checkLogin() 을 호출하지 않는다.
 * 회원은 $_SESSION['member_id'], 비회원은 qcart 쿠키 토큰으로 장바구니를 식별한다.
 * (식별 로직은 전부 QuoteCart 클래스가 담당한다)
 *
 * 문서: dev_docs/PRD_product_quote_v2.md 8.2절
 */

require_once 'db.php';
require_once 'includes/QuoteCart.php';
require_once 'includes/sub_layout.php';

// 아래에서 $_SESSION 을 읽기 전에 세션을 시작해야 한다.
// db.php 는 세션 쿠키 옵션만 설정하고 세션을 열지 않으므로, 여기서 열지 않으면
// 로그인한 회원이 비회원으로 잘못 판정된다.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** 소수점 뒤 불필요한 0을 정리해 표시용 문자열을 만든다. */
if (!function_exists('qcFormatNumber')) {
    function qcFormatNumber($value)
    {
        if ($value === null || $value === '') {
            return '';
        }
        $num = (float)$value;
        // 정수면 정수로, 소수면 최대 3자리까지만 표시
        if (abs($num - round($num)) < 0.0005) {
            return (string)(int)round($num);
        }
        return rtrim(rtrim(number_format($num, 3, '.', ''), '0'), '.');
    }
}

// ── 회원 정보 조회 (로그인 상태일 때만) ───────────────────────────────
$isMember = !empty($_SESSION['member_id']);
$member   = null;

if ($isMember) {
    try {
        $stmt = $pdo->prepare("SELECT id, name, email, phone, company FROM members WHERE id = ?");
        $stmt->execute([(int)$_SESSION['member_id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $member = $row ?: null;
    } catch (PDOException $e) {
        error_log('quote_cart.php 회원정보 조회 실패: ' . $e->getMessage());
        $member = null;
    }
}

// 회원 정보를 불러오지 못한 경우(비회원 포함)에는 이름·연락처를 직접 받는다.
$requireContact = ($member === null);

$defaultName    = $member['name']    ?? '';
$defaultPhone   = $member['phone']   ?? '';
$defaultEmail   = $member['email']   ?? '';
$defaultCompany = $member['company'] ?? '';

// ── 장바구니 항목 조회 ────────────────────────────────────────────────
$cartItems = [];
$loadError = false;

try {
    $cartItems = QuoteCart::items($pdo);
} catch (Throwable $e) {
    error_log('quote_cart.php 장바구니 조회 실패: ' . $e->getMessage());
    $loadError = true;
}

$currentPage = 'quote_cart';
$pageTitle   = '견적 장바구니';
include 'head.php';

startSubPage('견적 장바구니', 'quote_cart');

// 로그인 회원에게만 마이페이지 사이드바를 노출한다.
if ($isMember) {
    myPageSidebar('quote_cart');
}
?>
<style>
/* 견적 장바구니 전용 스타일 */
.qc-wrap { padding: 0; }
.qc-table-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; }
.qc-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
    min-width: 860px;
}
.qc-table thead th {
    background: #F8F9FA;
    color: #333;
    font-weight: 700;
    font-size: 13px;
    padding: 14px 10px;
    border-bottom: 2px solid #E5E5E7;
    white-space: nowrap;
    text-align: center;
}
.qc-table tbody td {
    padding: 14px 10px;
    border-bottom: 1px solid #F0F0F0;
    color: #555;
    text-align: center;
    vertical-align: middle;
}
.qc-table tbody tr:hover { background: #FAFBFC; }
.qc-table .qc-name { text-align: left; font-weight: 600; color: #222; min-width: 160px; }
.qc-table .qc-note { text-align: left; color: #777; max-width: 220px; word-break: break-all; }
.qc-check { width: 18px; height: 18px; cursor: pointer; accent-color: #1A237E; }
.qc-qty-input {
    width: 84px;
    padding: 8px 10px;
    border: 1px solid #E5E5E7;
    border-radius: 6px;
    font-size: 14px;
    text-align: right;
}
.qc-qty-input:focus { outline: none; border-color: #1A237E; box-shadow: 0 0 0 3px rgba(26,35,126,0.1); }
.qc-qty-cell { white-space: nowrap; }
.qc-qty-unit { margin-left: 6px; color: #666; font-size: 13px; }
.qc-del-btn {
    border: 1px solid #E5E5E7;
    background: #fff;
    color: #999;
    border-radius: 6px;
    width: 32px;
    height: 32px;
    cursor: pointer;
    font-size: 14px;
    line-height: 1;
    transition: all .2s ease;
}
.qc-del-btn:hover { background: #FFF1F1; border-color: #F0B7B7; color: #D32F2F; }

.qc-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    padding: 16px 0 12px;
}
.qc-count { font-size: 14px; color: #666; }
.qc-count strong { color: #1A237E; }

.qc-section-title {
    font-size: 16px;
    font-weight: 700;
    color: #222;
    margin: 32px 0 16px;
    padding-bottom: 10px;
    border-bottom: 2px solid #1A237E;
}
.qc-form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
}
.qc-field { display: flex; flex-direction: column; }
.qc-field.qc-full { grid-column: 1 / -1; }
.qc-field label {
    font-size: 13px;
    font-weight: 600;
    color: #333;
    margin-bottom: 6px;
}
.qc-field .qc-req { color: #D32F2F; margin-left: 2px; }
.qc-field input,
.qc-field textarea {
    width: 100%;
    padding: 12px 14px;
    border: 1px solid #E5E5E7;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    box-sizing: border-box;
}
.qc-field input:focus,
.qc-field textarea:focus { outline: none; border-color: #1A237E; box-shadow: 0 0 0 3px rgba(26,35,126,0.1); }
.qc-field textarea { min-height: 96px; resize: vertical; }
.qc-help { font-size: 12px; color: #888; margin-top: 6px; line-height: 1.5; }
.qc-member-badge {
    display: inline-block;
    background: #E8EAF6;
    color: #1A237E;
    font-size: 12px;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 12px;
    margin-left: 8px;
}

.qc-actions { text-align: center; margin: 28px 0 8px; }
.qc-submit-btn {
    background: #1A237E;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 15px 48px;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    transition: background .2s ease;
}
.qc-submit-btn:hover { background: #283593; }
.qc-submit-btn:disabled { background: #B0B3C7; cursor: not-allowed; }

.qc-empty { text-align: center; padding: 70px 20px; color: #888; }
.qc-empty i { font-size: 44px; color: #D8DAE5; display: block; margin-bottom: 18px; }
.qc-empty p { font-size: 16px; margin-bottom: 22px; }
.qc-empty a {
    display: inline-block;
    background: #1A237E;
    color: #fff;
    text-decoration: none;
    padding: 13px 32px;
    border-radius: 8px;
    font-weight: 600;
}
.qc-empty a:hover { background: #283593; }

.qc-alert {
    padding: 14px 16px;
    border-radius: 8px;
    font-size: 14px;
    margin-bottom: 16px;
}
.qc-alert-error { background: #FFF1F1; border: 1px solid #F5C6C6; color: #C62828; }
.qc-alert-info  { background: #F1F5FF; border: 1px solid #C9D6F5; color: #1A237E; }

/* 모바일: 표를 카드형으로 전환 */
@media (max-width: 768px) {
    .qc-form-grid { grid-template-columns: 1fr; }
    .qc-table-scroll { overflow-x: visible; }
    .qc-table { min-width: 0; display: block; }
    .qc-table thead { display: none; }
    .qc-table tbody, .qc-table tr, .qc-table td { display: block; width: 100%; }
    .qc-table tbody tr {
        border: 1px solid #E5E5E7;
        border-radius: 10px;
        padding: 8px 4px;
        margin-bottom: 12px;
        position: relative;
    }
    .qc-table tbody td {
        border-bottom: 1px dashed #F0F0F0;
        text-align: right;
        padding: 10px 12px 10px 96px;
        position: relative;
        min-height: 20px;
        box-sizing: border-box;
    }
    .qc-table tbody td:last-child { border-bottom: none; }
    .qc-table tbody td::before {
        content: attr(data-label);
        position: absolute;
        left: 12px;
        top: 10px;
        width: 76px;
        text-align: left;
        font-weight: 700;
        font-size: 12px;
        color: #888;
    }
    .qc-table .qc-name, .qc-table .qc-note { text-align: right; max-width: none; }
    .qc-table td.qc-td-check { padding-left: 12px; text-align: left; }
    .qc-table td.qc-td-check::before { position: static; width: auto; }
    .qc-toolbar { padding: 12px 0; }
}
@media (max-width: 420px) {
    .qc-table tbody td { padding-left: 88px; }
    .qc-table tbody td::before { width: 70px; }
    .qc-submit-btn { width: 100%; padding: 15px 20px; }
}
</style>

<main class="sub-content">
    <div class="content-header">
        <h2>견적 장바구니</h2>
        <p>담아두신 제품을 선택해 견적을 요청하시면 담당자가 확인 후 회신드립니다.</p>
    </div>

    <div class="content-body qc-wrap">
        <?php if ($loadError): ?>
            <div class="qc-alert qc-alert-error">
                장바구니를 불러오지 못했습니다. 잠시 후 다시 시도해 주세요.
            </div>
        <?php endif; ?>

        <?php if (empty($cartItems)): ?>
            <div class="qc-empty">
                <i class="fas fa-cart-shopping"></i>
                <p>장바구니가 비어 있습니다.</p>
                <a href="products.php">제품 둘러보기</a>
            </div>
        <?php else: ?>
            <div class="qc-toolbar">
                <div class="qc-count">
                    전체 <strong><?php echo count($cartItems); ?></strong>건
                </div>
                <div>
                    <button type="button" class="qc-del-btn" id="qcRemoveSelected"
                            style="width:auto;height:34px;padding:0 14px;">선택 삭제</button>
                </div>
            </div>

            <div class="qc-table-scroll">
                <table class="qc-table" id="qcTable">
                    <thead>
                        <tr>
                            <th style="width:44px;">
                                <input type="checkbox" class="qc-check" id="qcCheckAll" checked
                                       title="전체선택">
                            </th>
                            <th>제품</th>
                            <th>규격</th>
                            <th>원산지</th>
                            <th>재질</th>
                            <th>길이</th>
                            <th>수량</th>
                            <th>요청사항</th>
                            <th style="width:60px;">삭제</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($cartItems as $item): ?>
                        <?php
                        $itemId   = (int)$item['id'];
                        $lengthTx = qcFormatNumber($item['length_value']);
                        $qtyTx    = qcFormatNumber($item['quantity']);
                        ?>
                        <tr data-id="<?php echo $itemId; ?>">
                            <td class="qc-td-check" data-label="선택">
                                <input type="checkbox" class="qc-check qc-item-check"
                                       value="<?php echo $itemId; ?>" checked>
                            </td>
                            <td class="qc-name" data-label="제품">
                                <?php echo htmlspecialchars($item['product_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td data-label="규격">
                                <?php echo htmlspecialchars(($item['product_spec'] ?? '') !== '' ? $item['product_spec'] : '-', ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td data-label="원산지">
                                <?php echo htmlspecialchars(($item['origin'] ?? '') !== '' ? $item['origin'] : '-', ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td data-label="재질">
                                <?php echo htmlspecialchars(($item['material'] ?? '') !== '' ? $item['material'] : '-', ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td data-label="길이">
                                <?php
                                if ($lengthTx !== '') {
                                    echo htmlspecialchars($lengthTx . ' ' . ($item['length_unit'] ?? 'M'), ENT_QUOTES, 'UTF-8');
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td class="qc-qty-cell" data-label="수량">
                                <input type="number" class="qc-qty-input qc-qty"
                                       value="<?php echo htmlspecialchars($qtyTx !== '' ? $qtyTx : '1', ENT_QUOTES, 'UTF-8'); ?>"
                                       min="0.001" step="any" inputmode="decimal"
                                       data-prev="<?php echo htmlspecialchars($qtyTx !== '' ? $qtyTx : '1', ENT_QUOTES, 'UTF-8'); ?>">
                                <span class="qc-qty-unit"><?php echo htmlspecialchars($item['quantity_unit'] ?? 'EA', ENT_QUOTES, 'UTF-8'); ?></span>
                            </td>
                            <td class="qc-note" data-label="요청사항">
                                <?php echo htmlspecialchars(($item['note'] ?? '') !== '' ? $item['note'] : '-', ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td data-label="삭제">
                                <button type="button" class="qc-del-btn qc-remove"
                                        title="삭제" aria-label="삭제">
                                    <i class="fas fa-xmark"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <h3 class="qc-section-title">
                요청자 정보
                <?php if ($member !== null): ?>
                    <span class="qc-member-badge">회원</span>
                <?php endif; ?>
            </h3>

            <?php if ($requireContact): ?>
                <div class="qc-alert qc-alert-info">
                    비회원도 견적을 요청하실 수 있습니다. 회신을 위해 이름과 연락처를 입력해 주세요.
                </div>
            <?php endif; ?>

            <div class="qc-form-grid">
                <div class="qc-field">
                    <label for="qcName">이름<?php echo $requireContact ? '<span class="qc-req">*</span>' : ''; ?></label>
                    <input type="text" id="qcName" maxlength="50"
                           value="<?php echo htmlspecialchars($defaultName, ENT_QUOTES, 'UTF-8'); ?>"
                           placeholder="성함을 입력해 주세요">
                </div>
                <div class="qc-field">
                    <label for="qcPhone">연락처<?php echo $requireContact ? '<span class="qc-req">*</span>' : ''; ?></label>
                    <input type="tel" id="qcPhone" maxlength="20"
                           value="<?php echo htmlspecialchars($defaultPhone, ENT_QUOTES, 'UTF-8'); ?>"
                           placeholder="010-0000-0000">
                </div>
                <div class="qc-field">
                    <label for="qcEmail">이메일</label>
                    <input type="email" id="qcEmail" maxlength="100"
                           value="<?php echo htmlspecialchars($defaultEmail, ENT_QUOTES, 'UTF-8'); ?>"
                           placeholder="example@domain.com">
                    <p class="qc-help">이메일을 입력하시면 답변을 메일로 받아보실 수 있습니다.</p>
                </div>
                <div class="qc-field">
                    <label for="qcCompany">회사명</label>
                    <input type="text" id="qcCompany" maxlength="100"
                           value="<?php echo htmlspecialchars($defaultCompany, ENT_QUOTES, 'UTF-8'); ?>"
                           placeholder="회사명 (선택)">
                </div>
                <div class="qc-field qc-full">
                    <label for="qcNotes">요청사항</label>
                    <textarea id="qcNotes" maxlength="1000"
                              placeholder="납기, 가공 여부 등 추가로 전달하실 내용을 적어주세요. (선택)"></textarea>
                </div>
            </div>

            <div class="qc-actions">
                <button type="button" class="qc-submit-btn" id="qcSubmit">선택 항목 견적요청</button>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php
endSubPage();
include 'tail.php';
?>
<script>
(function () {
    'use strict';

    var table = document.getElementById('qcTable');
    if (!table) { return; }

    // tail.php 의 CSRF 인터셉터 등록 순서에 의존하지 않도록 토큰을 직접 넣는다.
    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function postForm(url, form) {
        form.append('csrf_token', csrfToken());
        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': csrfToken(), 'X-Requested-With': 'XMLHttpRequest' },
            body: form
        }).then(function (res) {
            return res.json().catch(function () {
                return { success: false, message: '서버 응답을 처리하지 못했습니다.' };
            });
        });
    }

    var checkAll = document.getElementById('qcCheckAll');

    function itemChecks() {
        return Array.prototype.slice.call(table.querySelectorAll('.qc-item-check'));
    }

    function syncCheckAll() {
        var list = itemChecks();
        if (!checkAll) { return; }
        checkAll.checked = list.length > 0 && list.every(function (c) { return c.checked; });
    }

    if (checkAll) {
        checkAll.addEventListener('change', function () {
            itemChecks().forEach(function (c) { c.checked = checkAll.checked; });
        });
    }

    table.addEventListener('change', function (e) {
        if (e.target.classList.contains('qc-item-check')) {
            syncCheckAll();
        }
    });

    // 수량 인라인 수정
    table.addEventListener('change', function (e) {
        var input = e.target;
        if (!input.classList.contains('qc-qty')) { return; }

        var row = input.closest('tr');
        if (!row) { return; }

        var prev = input.getAttribute('data-prev');
        var qty = parseFloat(input.value);

        if (!isFinite(qty) || qty <= 0) {
            alert('수량은 0보다 큰 숫자로 입력해 주세요.');
            input.value = prev;
            return;
        }

        var form = new FormData();
        form.append('id', row.getAttribute('data-id'));
        form.append('quantity', String(qty));

        input.disabled = true;
        postForm('/ajax/cart_update.php', form).then(function (data) {
            input.disabled = false;
            if (data && data.success) {
                input.setAttribute('data-prev', String(qty));
            } else {
                alert((data && data.message) ? data.message : '수량을 변경하지 못했습니다.');
                input.value = prev;
            }
        }).catch(function () {
            input.disabled = false;
            input.value = prev;
            alert('수량을 변경하지 못했습니다.');
        });
    });

    // 개별 삭제
    table.addEventListener('click', function (e) {
        var btn = e.target.closest('.qc-remove');
        if (!btn) { return; }

        var row = btn.closest('tr');
        if (!row) { return; }
        if (!confirm('이 항목을 장바구니에서 삭제하시겠습니까?')) { return; }

        removeIds([row.getAttribute('data-id')]);
    });

    // 선택 삭제
    var removeSelected = document.getElementById('qcRemoveSelected');
    if (removeSelected) {
        removeSelected.addEventListener('click', function () {
            var ids = itemChecks().filter(function (c) { return c.checked; })
                                  .map(function (c) { return c.value; });
            if (ids.length === 0) {
                alert('삭제할 항목을 선택해 주세요.');
                return;
            }
            if (!confirm('선택한 ' + ids.length + '개 항목을 삭제하시겠습니까?')) { return; }
            removeIds(ids);
        });
    }

    function removeIds(ids) {
        var form = new FormData();
        ids.forEach(function (id) { form.append('ids[]', id); });

        postForm('/ajax/cart_remove.php', form).then(function (data) {
            if (data && data.success) {
                window.location.reload();
            } else {
                alert((data && data.message) ? data.message : '삭제하지 못했습니다.');
            }
        }).catch(function () {
            alert('삭제하지 못했습니다.');
        });
    }

    // 견적요청 제출
    var submitBtn = document.getElementById('qcSubmit');
    var requireContact = <?php echo $requireContact ? 'true' : 'false'; ?>;

    if (submitBtn) {
        submitBtn.addEventListener('click', function () {
            var ids = itemChecks().filter(function (c) { return c.checked; })
                                  .map(function (c) { return c.value; });
            if (ids.length === 0) {
                alert('견적을 요청할 항목을 선택해 주세요.');
                return;
            }

            var name  = (document.getElementById('qcName').value || '').trim();
            var phone = (document.getElementById('qcPhone').value || '').trim();
            var email = (document.getElementById('qcEmail').value || '').trim();

            // 비회원은 물론, 회원정보가 비어 있는 경우에도 이름·연락처는 반드시 필요하다.
            if (name === '') {
                alert(requireContact ? '이름을 입력해 주세요.' : '이름이 비어 있습니다. 이름을 입력해 주세요.');
                document.getElementById('qcName').focus();
                return;
            }
            if (phone === '') {
                alert('연락처를 입력해 주세요.');
                document.getElementById('qcPhone').focus();
                return;
            }
            if (email !== '' && email.indexOf('@') === -1) {
                alert('이메일 주소를 정확히 입력해 주세요.');
                document.getElementById('qcEmail').focus();
                return;
            }

            var form = new FormData();
            ids.forEach(function (id) { form.append('ids[]', id); });
            form.append('customer_name', name);
            form.append('phone', phone);
            form.append('email', email);
            form.append('company', (document.getElementById('qcCompany').value || '').trim());
            form.append('notes', (document.getElementById('qcNotes').value || '').trim());

            submitBtn.disabled = true;
            submitBtn.textContent = '전송 중...';

            postForm('/ajax/submit_quote_cart.php', form).then(function (data) {
                if (data && data.success) {
                    var msg = (data.message ? data.message : '견적요청이 접수되었습니다.');
                    if (data.quote_id) {
                        msg += '\n접수번호: ' + data.quote_id;
                    }
                    alert(msg);
                    window.location.reload();
                } else {
                    submitBtn.disabled = false;
                    submitBtn.textContent = '선택 항목 견적요청';
                    alert((data && data.message) ? data.message : '견적요청에 실패했습니다. 잠시 후 다시 시도해 주세요.');
                }
            }).catch(function () {
                submitBtn.disabled = false;
                submitBtn.textContent = '선택 항목 견적요청';
                alert('견적요청에 실패했습니다. 잠시 후 다시 시도해 주세요.');
            });
        });
    }

    syncCheckAll();
})();
</script>
