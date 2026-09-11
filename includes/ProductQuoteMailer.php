<?php
/**
 * 제품 견적요청 메일 발송
 *
 * 발송 지점은 두 곳뿐이다.
 *   1) 접수: ajax/submit_quote_cart.php 의 commit() 직후 → sendReceipt()
 *   2) 답변: admin/admin_product_quote_view.php 의 저장 직후 → sendReply()
 *
 * 메일 발송 실패가 견적 저장을 깨뜨려서는 안 되므로 모든 메서드는 내부에서 예외를 삼키고
 * error_log 로만 남긴다. 반환값으로 성공 여부를 알 수 있다.
 *
 * 문서: dev_docs/PRD_product_quote_v2.md
 */

require_once __DIR__ . '/EmailService.php';
require_once __DIR__ . '/settings.php';

class ProductQuoteMailer
{
    /** 상태 코드 → 한글 표기 */
    const STATUS_LABELS = [
        'pending'    => '접수',
        'processing' => '처리중',
        'completed'  => '완료',
        'cancelled'  => '취소',
    ];

    /**
     * 견적 1건과 그 항목들을 조회한다.
     *
     * @return array|null ['quote' => array, 'items' => array]
     */
    private static function load(PDO $pdo, $quoteId)
    {
        $stmt = $pdo->prepare("SELECT * FROM product_quotes WHERE id = ?");
        $stmt->execute([(int)$quoteId]);
        $quote = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$quote) {
            return null;
        }

        $stmt = $pdo->prepare("SELECT * FROM product_quote_items WHERE quote_id = ? ORDER BY id ASC");
        $stmt->execute([(int)$quoteId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return ['quote' => $quote, 'items' => $items];
    }

    /**
     * 고객 수신 이메일을 결정한다.
     *
     * 회원이면 members.email 을 우선하고, 없으면 견적에 입력된 email 을 쓴다.
     *
     * @return string 없으면 빈 문자열
     */
    private static function customerEmail(PDO $pdo, array $quote)
    {
        if (!empty($quote['member_id'])) {
            try {
                $stmt = $pdo->prepare("SELECT email FROM members WHERE id = ?");
                $stmt->execute([(int)$quote['member_id']]);
                $email = (string)$stmt->fetchColumn();
                if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    return $email;
                }
            } catch (PDOException $e) {
                error_log('ProductQuoteMailer::customerEmail 조회 실패: ' . $e->getMessage());
            }
        }

        $email = isset($quote['email']) ? trim((string)$quote['email']) : '';

        return ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) ? $email : '';
    }

    /**
     * 요청 항목 표를 HTML 로 만든다.
     */
    private static function itemsTable(array $items)
    {
        $h = function ($v) {
            return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        };

        $html = '<table cellpadding="8" cellspacing="0" border="0" '
              . 'style="width:100%;border-collapse:collapse;font-size:14px;">'
              . '<thead><tr style="background:#f5f5f5;">'
              . '<th align="left" style="border-bottom:2px solid #ddd;">제품</th>'
              . '<th align="left" style="border-bottom:2px solid #ddd;">규격</th>'
              . '<th align="left" style="border-bottom:2px solid #ddd;">원산지</th>'
              . '<th align="left" style="border-bottom:2px solid #ddd;">재질</th>'
              . '<th align="right" style="border-bottom:2px solid #ddd;">길이</th>'
              . '<th align="right" style="border-bottom:2px solid #ddd;">수량</th>'
              . '</tr></thead><tbody>';

        foreach ($items as $it) {
            $length = '';
            if ($it['length_value'] !== null && $it['length_value'] !== '') {
                $length = rtrim(rtrim(number_format((float)$it['length_value'], 2), '0'), '.')
                        . ' ' . $it['length_unit'];
            }
            $qty = rtrim(rtrim(number_format((float)$it['quantity'], 3), '0'), '.')
                 . ' ' . $it['quantity_unit'];

            $html .= '<tr>'
                  . '<td style="border-bottom:1px solid #eee;">' . $h($it['product_name']) . '</td>'
                  . '<td style="border-bottom:1px solid #eee;">' . $h($it['product_spec']) . '</td>'
                  . '<td style="border-bottom:1px solid #eee;">' . $h($it['origin']) . '</td>'
                  . '<td style="border-bottom:1px solid #eee;">' . $h($it['material']) . '</td>'
                  . '<td align="right" style="border-bottom:1px solid #eee;">' . $h($length) . '</td>'
                  . '<td align="right" style="border-bottom:1px solid #eee;">' . $h($qty) . '</td>'
                  . '</tr>';

            if (!empty($it['note'])) {
                $html .= '<tr><td colspan="6" style="border-bottom:1px solid #eee;color:#666;">'
                      . '요청사항: ' . $h($it['note']) . '</td></tr>';
            }
        }

        return $html . '</tbody></table>';
    }

    /**
     * 공통 메일 레이아웃.
     */
    private static function layout($title, $bodyHtml)
    {
        $h = function ($v) {
            return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        };

        return '<div style="max-width:680px;margin:0 auto;font-family:\'Malgun Gothic\',sans-serif;color:#222;">'
             . '<h2 style="border-bottom:3px solid #0d47a1;padding-bottom:12px;color:#0d47a1;">'
             . $h($title) . '</h2>'
             . $bodyHtml
             . '<p style="margin-top:32px;padding-top:16px;border-top:1px solid #ddd;'
             . 'color:#888;font-size:12px;">본 메일은 발신 전용입니다. 문의는 충남스틸로 연락해 주세요.</p>'
             . '</div>';
    }

    /**
     * 견적 접수 알림을 보낸다.
     *
     * 관리자에게는 항상 발송하고, 고객에게는 이메일이 있을 때만 발송한다.
     * (비회원이 이메일을 입력하지 않은 경우가 있다)
     *
     * @return array ['admin' => bool, 'customer' => bool]
     */
    public static function sendReceipt(PDO $pdo, $quoteId)
    {
        $result = ['admin' => false, 'customer' => false];

        try {
            $data = self::load($pdo, $quoteId);
            if ($data === null) {
                return $result;
            }

            $quote = $data['quote'];
            $items = $data['items'];
            $h = function ($v) {
                return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
            };

            $mailer = new EmailService($pdo);
            $itemsTable = self::itemsTable($items);

            $infoRows = '<table cellpadding="6" cellspacing="0" border="0" style="font-size:14px;margin-bottom:20px;">'
                . '<tr><td style="color:#666;width:90px;">접수번호</td><td><strong>#' . (int)$quote['id'] . '</strong></td></tr>'
                . '<tr><td style="color:#666;">요청자</td><td>' . $h($quote['customer_name'])
                . (!empty($quote['member_id']) ? ' (회원)' : ' (비회원)') . '</td></tr>'
                . (!empty($quote['company']) ? '<tr><td style="color:#666;">회사명</td><td>' . $h($quote['company']) . '</td></tr>' : '')
                . '<tr><td style="color:#666;">연락처</td><td>' . $h($quote['phone']) . '</td></tr>'
                . (!empty($quote['email']) ? '<tr><td style="color:#666;">이메일</td><td>' . $h($quote['email']) . '</td></tr>' : '')
                . '<tr><td style="color:#666;">접수일시</td><td>' . $h($quote['created_at']) . '</td></tr>'
                . '</table>';

            $notes = !empty($quote['notes'])
                ? '<h3 style="font-size:15px;margin-top:24px;">요청사항</h3>'
                  . '<div style="background:#fafafa;padding:12px;border-radius:4px;white-space:pre-wrap;">'
                  . $h($quote['notes']) . '</div>'
                : '';

            // 1) 관리자 알림
            $adminTo = getSetting('contact_email') ?: '';
            if ($adminTo !== '') {
                $adminBody = self::layout(
                    '새 견적요청이 접수되었습니다',
                    $infoRows . $itemsTable . $notes
                    . '<p style="margin-top:24px;">'
                    . '<a href="https://cnst.co.kr/admin/admin_login.php" '
                    . 'style="display:inline-block;background:#0d47a1;color:#fff;padding:10px 20px;'
                    . 'border-radius:4px;text-decoration:none;">관리자에서 보기</a></p>'
                );
                $res = $mailer->send(
                    $adminTo,
                    '[충남스틸] 새 견적요청 #' . (int)$quote['id'] . ' - ' . $quote['customer_name'],
                    $adminBody
                );
                $result['admin'] = !empty($res['success']);
            } else {
                error_log('ProductQuoteMailer: contact_email 설정이 비어 있어 관리자 알림을 보내지 못했습니다.');
            }

            // 2) 고객 접수 확인
            $customerTo = self::customerEmail($pdo, $quote);
            if ($customerTo !== '') {
                $customerBody = self::layout(
                    '견적요청이 접수되었습니다',
                    '<p>' . $h($quote['customer_name']) . '님, 견적요청을 접수했습니다. '
                    . '담당자가 확인 후 회신드리겠습니다.</p>'
                    . $infoRows . $itemsTable . $notes
                );
                $res = $mailer->send(
                    $customerTo,
                    '[충남스틸] 견적요청이 접수되었습니다 (접수번호 #' . (int)$quote['id'] . ')',
                    $customerBody
                );
                $result['customer'] = !empty($res['success']);
            }
        } catch (\Throwable $e) {
            error_log('ProductQuoteMailer::sendReceipt 실패(quote_id=' . $quoteId . '): ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * 관리자 답변을 고객에게 보낸다.
     *
     * 관리자 메모(admin_note)는 절대 포함하지 않는다. 고객 공개 답변(admin_reply)만 보낸다.
     *
     * @return bool 발송 성공 여부
     */
    public static function sendReply(PDO $pdo, $quoteId)
    {
        try {
            $data = self::load($pdo, $quoteId);
            if ($data === null) {
                return false;
            }

            $quote = $data['quote'];
            $to = self::customerEmail($pdo, $quote);
            if ($to === '') {
                error_log('ProductQuoteMailer::sendReply: 수신 이메일이 없습니다(quote_id=' . $quoteId . ')');
                return false;
            }

            $reply = trim((string)$quote['admin_reply']);
            if ($reply === '') {
                return false;
            }

            $h = function ($v) {
                return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
            };
            $statusLabel = isset(self::STATUS_LABELS[$quote['status']])
                ? self::STATUS_LABELS[$quote['status']]
                : $quote['status'];

            $body = self::layout(
                '견적요청 답변이 등록되었습니다',
                '<p>' . $h($quote['customer_name']) . '님, 요청하신 견적에 대한 답변입니다.</p>'
                . '<table cellpadding="6" cellspacing="0" border="0" style="font-size:14px;margin-bottom:20px;">'
                . '<tr><td style="color:#666;width:90px;">접수번호</td><td><strong>#' . (int)$quote['id'] . '</strong></td></tr>'
                . '<tr><td style="color:#666;">처리상태</td><td>' . $h($statusLabel) . '</td></tr>'
                . '</table>'
                . '<h3 style="font-size:15px;">답변 내용</h3>'
                . '<div style="background:#f1f7ff;padding:16px;border-left:4px solid #0d47a1;'
                . 'border-radius:4px;white-space:pre-wrap;">' . $h($reply) . '</div>'
                . '<h3 style="font-size:15px;margin-top:24px;">요청 항목</h3>'
                . self::itemsTable($data['items'])
            );

            $mailer = new EmailService($pdo);
            $res = $mailer->send(
                $to,
                '[충남스틸] 견적요청 답변 (접수번호 #' . (int)$quote['id'] . ')',
                $body
            );

            return !empty($res['success']);
        } catch (\Throwable $e) {
            error_log('ProductQuoteMailer::sendReply 실패(quote_id=' . $quoteId . '): ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 해당 견적에 답변 메일을 보낼 수 있는지 확인한다. (관리자 화면 안내용)
     *
     * @return string 보낼 수 있으면 이메일 주소, 없으면 빈 문자열
     */
    public static function resolveCustomerEmail(PDO $pdo, array $quote)
    {
        return self::customerEmail($pdo, $quote);
    }
}
