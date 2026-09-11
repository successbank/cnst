<?php
/**
 * 제품 견적 장바구니 서비스
 *
 * 회원은 member_id, 비회원은 쿠키에 담긴 cart_token 으로 장바구니를 식별한다.
 * 서버 세션을 쓰지 않는 이유: login.php 가 로그인 성공 시 $_SESSION 을 통째로 비우기 때문에
 * 비회원 장바구니를 회원 장바구니로 승계할 수 없다.
 *
 * 모든 조회/수정/삭제는 소유자 조건(member_id 또는 cart_token)을 WHERE 에 반드시 포함한다.
 *
 * 문서: dev_docs/PRD_product_quote_v2.md
 */

class QuoteCart
{
    /** 비회원 장바구니 토큰 쿠키 이름 */
    const COOKIE_NAME = 'qcart';

    /** 비회원 장바구니 보관 일수 */
    const COOKIE_DAYS = 30;

    /** 장바구니 최대 항목 수 */
    const MAX_ITEMS = 50;

    /** 허용 길이 단위 */
    const LENGTH_UNITS = ['M', 'mm'];

    /** 허용 수량 단위 */
    const QUANTITY_UNITS = ['EA', '본', 'TON', 'kg', '장', 'SET', 'M'];

    /**
     * 현재 장바구니 소유자를 반환한다.
     *
     * 로그인 회원이면 member_id 를, 비회원이면 cart_token 을 채워 돌려준다.
     * 둘 중 하나만 값이 있고 나머지는 null 이다.
     *
     * @param bool $createToken 비회원이고 토큰이 없을 때 새로 발급할지 여부.
     *                          담기(쓰기) 시에만 true 로 호출한다. 단순 조회 시 true 로 부르면
     *                          방문자 전원에게 불필요한 쿠키가 발급된다.
     * @return array ['member_id' => int|null, 'cart_token' => string|null]
     */
    public static function owner($createToken = false)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!empty($_SESSION['member_id'])) {
            return ['member_id' => (int)$_SESSION['member_id'], 'cart_token' => null];
        }

        $token = isset($_COOKIE[self::COOKIE_NAME]) ? $_COOKIE[self::COOKIE_NAME] : '';

        // 쿠키 값 형식 검증 (64자 hex 이외는 신뢰하지 않는다)
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            $token = '';
        }

        if ($token === '' && $createToken) {
            $token = self::issueGuestToken();
        }

        return ['member_id' => null, 'cart_token' => $token !== '' ? $token : null];
    }

    /**
     * 비회원 장바구니 토큰을 새로 발급하고 쿠키로 내려준다.
     *
     * @return string 64자 hex 토큰
     */
    public static function issueGuestToken()
    {
        $token = bin2hex(random_bytes(32));

        setcookie(self::COOKIE_NAME, $token, [
            'expires'  => time() + (self::COOKIE_DAYS * 86400),
            'path'     => '/',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        // 같은 요청 안에서 바로 읽을 수 있도록 슈퍼글로벌에도 반영한다.
        $_COOKIE[self::COOKIE_NAME] = $token;

        return $token;
    }

    /**
     * 비회원 장바구니 토큰 쿠키를 폐기한다.
     */
    public static function clearGuestToken()
    {
        setcookie(self::COOKIE_NAME, '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        unset($_COOKIE[self::COOKIE_NAME]);
    }

    /**
     * 소유자 조건을 SQL 조각과 바인딩 값으로 만들어 준다.
     *
     * @param array $owner owner() 반환값
     * @return array|null ['sql' => string, 'params' => array] 소유자가 없으면 null
     */
    private static function ownerClause(array $owner)
    {
        if (!empty($owner['member_id'])) {
            return ['sql' => 'member_id = ?', 'params' => [$owner['member_id']]];
        }
        if (!empty($owner['cart_token'])) {
            return ['sql' => 'cart_token = ?', 'params' => [$owner['cart_token']]];
        }
        return null;
    }

    /**
     * 장바구니에 항목을 담는다.
     *
     * 호출 전에 상위(엔드포인트)에서 제품을 재조회해 product_name / product_spec 을
     * 서버 값으로 채워 넘겨야 한다. 클라이언트가 보낸 제품명은 신뢰하지 않는다.
     *
     * @param PDO   $pdo
     * @param array $item product_id, product_name, product_spec, origin, material,
     *                    length_value, length_unit, quantity, quantity_unit, note
     * @return int 생성된 행 id
     * @throws RuntimeException 소유자 없음 / 최대 항목 초과
     */
    public static function add(PDO $pdo, array $item)
    {
        $owner = self::owner(true);
        if (empty($owner['member_id']) && empty($owner['cart_token'])) {
            throw new RuntimeException('장바구니를 사용할 수 없습니다.');
        }

        if (self::count($pdo, $owner) >= self::MAX_ITEMS) {
            throw new RuntimeException('장바구니에는 최대 ' . self::MAX_ITEMS . '개까지 담을 수 있습니다.');
        }

        $stmt = $pdo->prepare("
            INSERT INTO quote_cart_items
                (member_id, cart_token, product_id, product_name, product_spec,
                 origin, material, length_value, length_unit, quantity, quantity_unit, note)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $owner['member_id'],
            $owner['cart_token'],
            !empty($item['product_id']) ? (int)$item['product_id'] : null,
            $item['product_name'],
            isset($item['product_spec']) && $item['product_spec'] !== '' ? $item['product_spec'] : null,
            isset($item['origin']) && $item['origin'] !== '' ? $item['origin'] : null,
            isset($item['material']) && $item['material'] !== '' ? $item['material'] : null,
            isset($item['length_value']) && $item['length_value'] !== '' ? $item['length_value'] : null,
            !empty($item['length_unit']) ? $item['length_unit'] : 'M',
            isset($item['quantity']) ? $item['quantity'] : 1,
            !empty($item['quantity_unit']) ? $item['quantity_unit'] : 'EA',
            isset($item['note']) && $item['note'] !== '' ? mb_substr($item['note'], 0, 500) : null,
        ]);

        return (int)$pdo->lastInsertId();
    }

    /**
     * 현재 소유자의 장바구니 목록을 반환한다.
     *
     * @param PDO        $pdo
     * @param array|null $owner 미지정 시 owner() 로 조회
     * @return array
     */
    public static function items(PDO $pdo, array $owner = null)
    {
        $owner = $owner ?: self::owner(false);
        $clause = self::ownerClause($owner);
        if ($clause === null) {
            return [];
        }

        $stmt = $pdo->prepare("
            SELECT * FROM quote_cart_items
            WHERE {$clause['sql']}
            ORDER BY created_at DESC, id DESC
        ");
        $stmt->execute($clause['params']);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 현재 소유자의 장바구니 항목 수를 반환한다.
     *
     * @param PDO        $pdo
     * @param array|null $owner
     * @return int
     */
    public static function count(PDO $pdo, array $owner = null)
    {
        $owner = $owner ?: self::owner(false);
        $clause = self::ownerClause($owner);
        if ($clause === null) {
            return 0;
        }

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM quote_cart_items WHERE {$clause['sql']}");
        $stmt->execute($clause['params']);

        return (int)$stmt->fetchColumn();
    }

    /**
     * 지정한 id 들 중 현재 소유자의 것만 조회한다.
     *
     * @param PDO   $pdo
     * @param array $ids
     * @return array
     */
    public static function itemsByIds(PDO $pdo, array $ids)
    {
        $ids = array_values(array_filter(array_map('intval', $ids), function ($v) {
            return $v > 0;
        }));
        if (empty($ids)) {
            return [];
        }

        $owner = self::owner(false);
        $clause = self::ownerClause($owner);
        if ($clause === null) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("
            SELECT * FROM quote_cart_items
            WHERE {$clause['sql']} AND id IN ($placeholders)
            ORDER BY created_at DESC, id DESC
        ");
        $stmt->execute(array_merge($clause['params'], $ids));

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 장바구니 항목을 수정한다. 소유자 조건이 포함되므로 타인 항목은 변경되지 않는다.
     *
     * @param PDO   $pdo
     * @param int   $id
     * @param array $fields quantity, quantity_unit, length_value, length_unit, note 중 일부
     * @return bool 변경 여부
     */
    public static function update(PDO $pdo, $id, array $fields)
    {
        $owner = self::owner(false);
        $clause = self::ownerClause($owner);
        if ($clause === null) {
            return false;
        }

        $allowed = ['quantity', 'quantity_unit', 'length_value', 'length_unit', 'note'];
        $sets = [];
        $params = [];

        foreach ($allowed as $key) {
            if (array_key_exists($key, $fields)) {
                $sets[] = "$key = ?";
                $params[] = $fields[$key];
            }
        }

        if (empty($sets)) {
            return false;
        }

        $params = array_merge($params, $clause['params'], [(int)$id]);
        $stmt = $pdo->prepare("
            UPDATE quote_cart_items SET " . implode(', ', $sets) . "
            WHERE {$clause['sql']} AND id = ?
        ");
        $stmt->execute($params);

        if ($stmt->rowCount() > 0) {
            return true;
        }

        // rowCount 는 '값이 실제로 바뀐 행 수' 이므로, 같은 값으로 다시 저장하면 0 이 된다.
        // '변경 없음' 과 '내 항목이 아님' 을 구분하기 위해 소유 여부를 한 번 더 확인한다.
        $check = $pdo->prepare("
            SELECT 1 FROM quote_cart_items
            WHERE {$clause['sql']} AND id = ?
            LIMIT 1
        ");
        $check->execute(array_merge($clause['params'], [(int)$id]));

        return (bool)$check->fetchColumn();
    }

    /**
     * 장바구니 항목을 삭제한다. 소유자 조건이 포함되므로 타인 항목은 삭제되지 않는다.
     *
     * @param PDO   $pdo
     * @param array $ids
     * @return int 삭제된 행 수
     */
    public static function remove(PDO $pdo, array $ids)
    {
        $ids = array_values(array_filter(array_map('intval', $ids), function ($v) {
            return $v > 0;
        }));
        if (empty($ids)) {
            return 0;
        }

        $owner = self::owner(false);
        $clause = self::ownerClause($owner);
        if ($clause === null) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("
            DELETE FROM quote_cart_items
            WHERE {$clause['sql']} AND id IN ($placeholders)
        ");
        $stmt->execute(array_merge($clause['params'], $ids));

        return $stmt->rowCount();
    }

    /**
     * 비회원 장바구니를 회원 장바구니로 승계한다.
     *
     * login.php 에서 세션 설정이 끝난 직후(반드시 $_SESSION = [] 이후)에 호출한다.
     * 승계 후 비회원 토큰 쿠키는 폐기한다.
     *
     * @param PDO $pdo
     * @param int $memberId
     * @return int 이관된 행 수
     */
    public static function mergeGuestCart(PDO $pdo, $memberId)
    {
        $memberId = (int)$memberId;
        if ($memberId <= 0) {
            return 0;
        }

        $token = isset($_COOKIE[self::COOKIE_NAME]) ? $_COOKIE[self::COOKIE_NAME] : '';
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return 0;
        }

        $moved = 0;
        try {
            // 회원 장바구니가 이미 상한에 도달했다면 초과분은 남기지 않고 버린다.
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM quote_cart_items WHERE member_id = ?");
            $stmt->execute([$memberId]);
            $existing = (int)$stmt->fetchColumn();
            $room = self::MAX_ITEMS - $existing;

            if ($room > 0) {
                $stmt = $pdo->prepare("
                    UPDATE quote_cart_items
                    SET member_id = ?, cart_token = NULL
                    WHERE cart_token = ?
                    ORDER BY created_at ASC
                    LIMIT $room
                ");
                $stmt->execute([$memberId, $token]);
                $moved = $stmt->rowCount();
            }

            // 승계되지 못하고 남은 비회원 행은 정리한다.
            $stmt = $pdo->prepare("DELETE FROM quote_cart_items WHERE cart_token = ?");
            $stmt->execute([$token]);
        } catch (PDOException $e) {
            error_log('QuoteCart::mergeGuestCart 실패: ' . $e->getMessage());
        }

        self::clearGuestToken();

        return $moved;
    }

    /**
     * 길이 단위를 허용 목록으로 정규화한다.
     */
    public static function normalizeLengthUnit($unit)
    {
        return in_array($unit, self::LENGTH_UNITS, true) ? $unit : 'M';
    }

    /**
     * 수량 단위를 허용 목록으로 정규화한다.
     */
    public static function normalizeQuantityUnit($unit)
    {
        return in_array($unit, self::QUANTITY_UNITS, true) ? $unit : 'EA';
    }
}
