<?php
/**
 * 제품 견적요청 상세 (관리자)
 *
 * - 요청 항목(원산지/재질/길이/수량)을 확인한다.
 * - 관리자 메모(admin_note, 내부용)와 고객 답변(admin_reply, 발송용)을 분리해 저장한다.
 * - 저장 시 "이메일 발송"을 체크하면 ProductQuoteMailer::sendReply() 로 고객에게 답변을 보낸다.
 *
 * POST 처리는 admin_head.php require 이전에 두어 header() 리다이렉트가 가능하게 한다.
 * 문서: dev_docs/PRD_product_quote_v2.md 8.3절
 */

require_once '../db.php';
require_once 'admin_check.php';              // 세션 시작 + POST 시 CSRF 전역 검증
require_once '../includes/ProductQuoteMailer.php';

$quote_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($quote_id <= 0) {
    header('Location: admin_product_quotes.php');
    exit;
}

// ── 견적 조회 ─────────────────────────────────────────────────────────────
$quote = false;
try {
    $stmt = $pdo->prepare(
        "SELECT pq.*,
                m.user_id AS member_user_id,
                m.name    AS member_name,
                m.email   AS member_email
           FROM product_quotes pq
           LEFT JOIN members m ON pq.member_id = m.id
          WHERE pq.id = ?"
    );
    $stmt->execute([$quote_id]);
    $quote = $stmt->fetch();
} catch (PDOException $e) {
    error_log('admin_product_quote_view.php 견적 조회 실패(id=' . $quote_id . '): ' . $e->getMessage());
    header('Location: admin_product_quotes.php?msg=load_failed');
    exit;
}

if (!$quote) {
    header('Location: admin_product_quotes.php?msg=notfound');
    exit;
}

$status_labels = [
    'pending'    => '접수',
    'processing' => '처리중',
    'completed'  => '완료',
    'cancelled'  => '취소',
];

$error = '';

// ── 저장 처리 (헤더 출력 전) ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = isset($_POST['status']) ? (string)$_POST['status'] : '';
    if (!array_key_exists($new_status, $status_labels)) {
        $new_status = $quote['status'];
    }

    $admin_note  = trim((string)($_POST['admin_note'] ?? ''));
    $admin_reply = trim((string)($_POST['admin_reply'] ?? ''));
    $send_email  = isset($_POST['send_email']);

    $prev_reply = trim((string)($quote['admin_reply'] ?? ''));
    // 답변이 새로 채워졌거나 내용이 바뀐 경우에만 답변일시를 갱신한다.
    $reply_changed = ($admin_reply !== '' && $admin_reply !== $prev_reply);

    try {
        if ($reply_changed) {
            $sql = "UPDATE product_quotes
                       SET status = ?, admin_note = ?, admin_reply = ?, replied_at = NOW(), updated_at = NOW()
                     WHERE id = ?";
        } else {
            $sql = "UPDATE product_quotes
                       SET status = ?, admin_note = ?, admin_reply = ?, updated_at = NOW()
                     WHERE id = ?";
        }
        $update_stmt = $pdo->prepare($sql);
        $update_stmt->execute([$new_status, $admin_note, $admin_reply, $quote_id]);

        $msg = 'saved';

        if ($send_email && $admin_reply !== '') {
            $to = ProductQuoteMailer::resolveCustomerEmail($pdo, $quote);
            if ($to === '') {
                $msg = 'no_email';
            } else {
                $msg = ProductQuoteMailer::sendReply($pdo, $quote_id) ? 'sent' : 'mail_failed';
            }
        }

        header('Location: admin_product_quote_view.php?id=' . $quote_id . '&msg=' . $msg);
        exit;
    } catch (PDOException $e) {
        error_log('admin_product_quote_view.php 저장 실패(id=' . $quote_id . '): ' . $e->getMessage());
        $error = '저장 중 오류가 발생했습니다. 잠시 후 다시 시도해 주세요.';
        // 입력값 유실 방지를 위해 화면에는 방금 입력한 내용을 그대로 보여준다.
        $quote['status']      = $new_status;
        $quote['admin_note']  = $admin_note;
        $quote['admin_reply'] = $admin_reply;
    }
}

// ── 견적 항목 조회 ────────────────────────────────────────────────────────
$items = [];
try {
    $items_stmt = $pdo->prepare("SELECT * FROM product_quote_items WHERE quote_id = ? ORDER BY id ASC");
    $items_stmt->execute([$quote_id]);
    $items = $items_stmt->fetchAll();
} catch (PDOException $e) {
    error_log('admin_product_quote_view.php 항목 조회 실패(id=' . $quote_id . '): ' . $e->getMessage());
    $error = $error ?: '요청 항목을 불러오지 못했습니다.';
}

// 신규 견적요청(v2) 건은 금액을 산출하지 않으므로 금액 컬럼을 숨긴다.
$is_v2 = (($quote['source'] ?? '') === 'v2');

// 답변 메일 수신 가능 여부
$customer_email = ProductQuoteMailer::resolveCustomerEmail($pdo, $quote);
$can_send_email = ($customer_email !== '');

/** 숫자 뒤 불필요한 0 제거 */
function quoteFormatNumber($value, $decimals)
{
    if ($value === null || $value === '') {
        return '';
    }
    $s = number_format((float)$value, $decimals);
    if (strpos($s, '.') !== false) {
        $s = rtrim(rtrim($s, '0'), '.');
    }
    return $s;
}

$pageTitle = '제품 견적요청 상세';
$currentFile = basename($_SERVER['PHP_SELF']);
require_once 'admin_head.php';

$h = function ($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
};

$msg = isset($_GET['msg']) ? (string)$_GET['msg'] : '';
$notice = '';
$notice_class = 'alert-success';
switch ($msg) {
    case 'saved':
        $notice = '저장했습니다.';
        break;
    case 'sent':
        $notice = '저장했습니다. 답변 메일을 발송했습니다.';
        break;
    case 'mail_failed':
        $notice = '저장했으나 메일 발송에 실패했습니다. 잠시 후 다시 시도하거나 전화로 회신해 주세요.';
        $notice_class = 'alert-danger';
        break;
    case 'no_email':
        $notice = '저장했으나 수신 이메일이 없어 메일을 발송하지 못했습니다.';
        $notice_class = 'alert-danger';
        break;
}
?>

<div class="content">
    <div class="products-header">
        <h2>제품 견적요청 상세</h2>
        <div class="header-actions">
            <a href="admin_product_quotes.php" class="btn btn-secondary">목록으로</a>
        </div>
    </div>

    <?php if ($notice !== ''): ?>
        <div class="alert <?php echo $notice_class; ?>"><?php echo $h($notice); ?></div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger"><?php echo $h($error); ?></div>
    <?php endif; ?>

    <div class="detail-container">
        <div class="detail-section">
            <h3>견적 정보</h3>
            <table class="detail-table">
                <tr>
                    <th width="150">접수번호</th>
                    <td>#<?php echo (int)$quote['id']; ?></td>
                    <th width="150">접수일시</th>
                    <td><?php echo $h($quote['created_at']); ?></td>
                </tr>
                <tr>
                    <th>상태</th>
                    <td>
                        <?php
                        $status_class_map = [
                            'pending'    => 'badge-warning',
                            'processing' => 'badge-info',
                            'completed'  => 'badge-success',
                            'cancelled'  => 'badge-danger',
                        ];
                        $status_class = $status_class_map[$quote['status']] ?? 'badge-info';
                        $status_text  = $status_labels[$quote['status']] ?? $quote['status'];
                        ?>
                        <span class="badge <?php echo $status_class; ?>"><?php echo $h($status_text); ?></span>
                    </td>
                    <th>최종수정</th>
                    <td><?php echo $h($quote['updated_at']); ?></td>
                </tr>
                <tr>
                    <th>요청자</th>
                    <td>
                        <?php if (!empty($quote['member_id'])): ?>
                            회원
                            <?php echo $h($quote['member_name'] ?: $quote['customer_name']); ?>
                            (<?php echo $h($quote['member_user_id'] ?: ('#' . (int)$quote['member_id'])); ?>)
                        <?php else: ?>
                            비회원
                        <?php endif; ?>
                    </td>
                    <th>답변일시</th>
                    <td><?php echo !empty($quote['replied_at']) ? $h($quote['replied_at']) : '-'; ?></td>
                </tr>
            </table>
        </div>

        <div class="detail-section">
            <h3>고객 정보</h3>
            <table class="detail-table">
                <tr>
                    <th width="150">담당자명</th>
                    <td><?php echo $h($quote['customer_name']); ?></td>
                    <th width="150">회사명</th>
                    <td><?php echo $h($quote['company'] ?: '-'); ?></td>
                </tr>
                <tr>
                    <th>연락처</th>
                    <td><?php echo $h($quote['phone'] ?: '-'); ?></td>
                    <th>이메일</th>
                    <td>
                        <?php if ($can_send_email): ?>
                            <?php echo $h($customer_email); ?>
                        <?php else: ?>
                            <span class="text-muted">없음</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if (!empty($quote['address'])): ?>
                <tr>
                    <th>주소</th>
                    <td colspan="3">
                        <?php echo $h(trim($quote['address'] . ' ' . (string)$quote['address_detail'])); ?>
                    </td>
                </tr>
                <?php endif; ?>
            </table>
        </div>

        <div class="detail-section">
            <h3>요청 항목</h3>
            <?php if (empty($items)): ?>
                <p class="text-muted">등록된 요청 항목이 없습니다.</p>
            <?php else: ?>
            <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th width="60">번호</th>
                        <th>제품명</th>
                        <th>규격</th>
                        <th width="90">원산지</th>
                        <th width="90">재질</th>
                        <th width="110">길이</th>
                        <th width="110">수량</th>
                        <?php if (!$is_v2): ?>
                        <th width="110">단가</th>
                        <th width="110">금액</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $index => $item): ?>
                    <?php
                    $length_text = quoteFormatNumber($item['length_value'] ?? null, 2);
                    if ($length_text !== '') {
                        $length_text .= ' ' . (string)$item['length_unit'];
                    } else {
                        $length_text = '-';
                    }

                    $qty_text = quoteFormatNumber($item['quantity'] ?? null, 3);
                    if ($qty_text !== '') {
                        $qty_text .= ' ' . (string)$item['quantity_unit'];
                    } else {
                        $qty_text = '-';
                    }
                    ?>
                    <tr>
                        <td class="text-center"><?php echo $index + 1; ?></td>
                        <td><?php echo $h($item['product_name']); ?></td>
                        <td><?php echo $h($item['product_spec'] ?: '-'); ?></td>
                        <td class="text-center"><?php echo $h($item['origin'] ?: '-'); ?></td>
                        <td class="text-center"><?php echo $h($item['material'] ?: '-'); ?></td>
                        <td class="text-right"><?php echo $h($length_text); ?></td>
                        <td class="text-right"><?php echo $h($qty_text); ?></td>
                        <?php if (!$is_v2): ?>
                        <td class="text-right"><?php echo $h(number_format((float)($item['unit_price'] ?? 0))); ?></td>
                        <td class="text-right"><?php echo $h(number_format((float)($item['subtotal'] ?? 0))); ?></td>
                        <?php endif; ?>
                    </tr>
                    <?php if (!empty($item['note'])): ?>
                    <tr class="item-note-row">
                        <td></td>
                        <td colspan="<?php echo $is_v2 ? 6 : 8; ?>">
                            요청사항: <?php echo nl2br($h($item['note'])); ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($quote['notes'])): ?>
        <div class="detail-section">
            <h3>추가 요청사항</h3>
            <div class="notes-content">
                <?php echo nl2br($h($quote['notes'])); ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="detail-section">
            <h3>답변 및 상태 관리</h3>
            <form method="POST" action="admin_product_quote_view.php?id=<?php echo (int)$quote_id; ?>">
                <?php echo csrfField(); ?>
                <input type="hidden" name="update_status" value="1">

                <div class="form-group">
                    <label for="admin_note">관리자 메모 <span class="label-hint">(고객에게 발송되지 않습니다)</span></label>
                    <textarea id="admin_note" name="admin_note" class="form-control" rows="4"
                              placeholder="내부 참고용 메모입니다."><?php echo $h($quote['admin_note'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="admin_reply">고객 답변 <span class="label-hint">(저장 시 고객 이메일로 발송됩니다)</span></label>
                    <textarea id="admin_reply" name="admin_reply" class="form-control" rows="6"
                              placeholder="고객에게 전달할 견적 답변을 작성하세요."><?php echo $h($quote['admin_reply'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="status">상태</label>
                    <select id="status" name="status" class="form-control" style="width: 200px;">
                        <?php foreach ($status_labels as $code => $label): ?>
                        <option value="<?php echo $h($code); ?>" <?php echo $quote['status'] === $code ? 'selected' : ''; ?>>
                            <?php echo $h($label); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="send_email" name="send_email" value="1"
                               <?php echo $can_send_email ? '' : 'disabled'; ?>>
                        저장 시 고객에게 이메일 발송
                    </label>
                    <?php if ($can_send_email): ?>
                        <p class="field-help">수신: <?php echo $h($customer_email); ?> (답변 내용이 있어야 발송됩니다)</p>
                    <?php else: ?>
                        <p class="field-help field-help-warn">이 요청자는 이메일이 없어 답변 메일을 보낼 수 없습니다.</p>
                    <?php endif; ?>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">저장</button>
                    <a href="admin_product_quotes.php" class="btn btn-secondary">취소</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($can_send_email): ?>
<script>
// 답변 내용이 비어 있으면 이메일 발송 체크박스를 잠근다.
(function () {
    var reply = document.getElementById('admin_reply');
    var check = document.getElementById('send_email');
    if (!reply || !check) { return; }

    function sync() {
        var hasReply = reply.value.trim().length > 0;
        check.disabled = !hasReply;
        if (!hasReply) { check.checked = false; }
    }
    reply.addEventListener('input', sync);
    sync();
})();
</script>
<?php endif; ?>

<style>
.products-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.products-header h2 {
    font-size: 28px;
    font-weight: 700;
    color: #333;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    font-weight: 500;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
}

.btn-primary {
    background: #4A90E2;
    color: white;
}

.btn-primary:hover {
    background: #357ABD;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
}

.detail-container {
    max-width: 1100px;
}

.detail-section {
    background: white;
    padding: 25px;
    margin-bottom: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.detail-section h3 {
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e5e5e7;
    font-size: 18px;
    color: #333;
    font-weight: 600;
}

.detail-table {
    width: 100%;
    border-collapse: collapse;
}

.detail-table th,
.detail-table td {
    padding: 12px;
    border-bottom: 1px solid #e5e5e7;
    text-align: left;
}

.detail-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #333;
    width: 150px;
}

.detail-table td {
    color: #666;
}

.table-scroll {
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th {
    background: #f8f9fa;
    padding: 12px;
    text-align: left;
    font-weight: 600;
    color: #333;
    border-bottom: 2px solid #dee2e6;
    white-space: nowrap;
}

.data-table td {
    padding: 12px;
    border-bottom: 1px solid #dee2e6;
}

.data-table tbody tr:hover {
    background: #f8f9fa;
}

.item-note-row td {
    background: #fafafa;
    color: #666;
    font-size: 13px;
}

.text-center {
    text-align: center;
}

.text-right {
    text-align: right;
}

.text-muted {
    color: #999;
}

.notes-content {
    padding: 15px;
    background: #f8f9fa;
    border-radius: 6px;
    line-height: 1.6;
    color: #666;
}

.badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
}

.badge-warning {
    background-color: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

.badge-info {
    background-color: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}

.badge-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.badge-danger {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
}

.label-hint {
    font-weight: 400;
    font-size: 13px;
    color: #888;
}

.checkbox-label {
    display: flex !important;
    align-items: center;
    gap: 8px;
    font-weight: 500 !important;
}

.field-help {
    margin-top: 6px;
    font-size: 13px;
    color: #888;
}

.field-help-warn {
    color: #b45309;
}

.form-control {
    width: 100%;
    padding: 10px 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.form-control:focus {
    outline: none;
    border-color: #4A90E2;
}

textarea.form-control {
    resize: vertical;
    min-height: 100px;
    font-family: inherit;
    line-height: 1.6;
}

.form-actions {
    margin-top: 20px;
    display: flex;
    gap: 10px;
}

.alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 4px;
    font-size: 14px;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
</style>

<?php require_once 'admin_tail.php'; ?>
