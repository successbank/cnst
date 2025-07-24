<?php
require_once 'member_check.php';
require_once 'db.php';

// 로그인 체크
checkLogin();

$member_id = $_SESSION['member_id'];

// 액션 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM member_addresses WHERE id = ? AND member_id = ?");
        $stmt->execute([$id, $member_id]);
    } elseif ($action === 'set_default') {
        $id = (int)$_POST['id'];
        
        // 모든 주소의 기본 설정 해제
        $stmt = $pdo->prepare("UPDATE member_addresses SET is_default = 0 WHERE member_id = ?");
        $stmt->execute([$member_id]);
        
        // 선택한 주소를 기본으로 설정
        $stmt = $pdo->prepare("UPDATE member_addresses SET is_default = 1 WHERE id = ? AND member_id = ?");
        $stmt->execute([$id, $member_id]);
    }
    
    header('Location: address_manager.php');
    exit;
}

// 주소 목록 가져오기
$stmt = $pdo->prepare("SELECT * FROM member_addresses WHERE member_id = ? ORDER BY is_default DESC, id DESC");
$stmt->execute([$member_id]);
$addresses = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>주소록 관리</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 700px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: #1428A0;
            color: white;
            padding: 20px;
        }
        
        .header h1 {
            font-size: 20px;
            margin-bottom: 5px;
        }
        
        .header p {
            font-size: 14px;
            opacity: 0.8;
        }
        
        .content {
            padding: 20px;
        }
        
        .address-item {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            position: relative;
        }
        
        .address-item.default {
            border-color: #1428A0;
            background: #f0f4ff;
        }
        
        .address-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .default-badge {
            background: #1428A0;
            color: white;
            font-size: 12px;
            padding: 2px 8px;
            border-radius: 4px;
        }
        
        .address-info {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .address-actions {
            margin-top: 10px;
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #1428A0;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0F1F7A;
        }
        
        .btn-outline {
            background: white;
            color: #666;
            border: 1px solid #ddd;
        }
        
        .btn-outline:hover {
            background: #f5f5f5;
        }
        
        .btn-danger {
            background: white;
            color: #dc3545;
            border: 1px solid #dc3545;
        }
        
        .btn-danger:hover {
            background: #dc3545;
            color: white;
        }
        
        .add-address {
            text-align: center;
            padding: 20px;
            border-top: 1px solid #e0e0e0;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        .empty-state svg {
            width: 60px;
            height: 60px;
            margin-bottom: 15px;
            fill: #ddd;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>주소록 관리</h1>
            <p>배송지 주소를 관리합니다.</p>
        </div>
        
        <div class="content">
            <?php if (empty($addresses)): ?>
            <div class="empty-state">
                <svg viewBox="0 0 24 24">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                </svg>
                <p>등록된 주소가 없습니다.</p>
            </div>
            <?php else: ?>
                <?php foreach ($addresses as $addr): ?>
                <div class="address-item <?php echo $addr['is_default'] ? 'default' : ''; ?>">
                    <div class="address-name">
                        <?php echo htmlspecialchars($addr['address_name'] ?? '주소'); ?>
                        <?php if ($addr['is_default']): ?>
                        <span class="default-badge">기본</span>
                        <?php endif; ?>
                    </div>
                    <div class="address-info">
                        <?php if ($addr['recipient_name']): ?>
                        <div>수령인: <?php echo htmlspecialchars($addr['recipient_name']); ?></div>
                        <?php endif; ?>
                        <?php if ($addr['recipient_phone']): ?>
                        <div>연락처: <?php echo htmlspecialchars($addr['recipient_phone']); ?></div>
                        <?php endif; ?>
                        <div>(<?php echo htmlspecialchars($addr['zipcode']); ?>) <?php echo htmlspecialchars($addr['address']); ?></div>
                        <?php if ($addr['address_detail']): ?>
                        <div><?php echo htmlspecialchars($addr['address_detail']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="address-actions">
                        <button type="button" onclick="selectAddress(<?php echo htmlspecialchars(json_encode([
                            'id' => $addr['id'],
                            'zipcode' => $addr['zipcode'],
                            'address' => $addr['address'],
                            'address_detail' => $addr['address_detail'] ?? ''
                        ])); ?>)" class="btn btn-primary">선택</button>
                        
                        <?php if (!$addr['is_default']): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="set_default">
                            <input type="hidden" name="id" value="<?php echo $addr['id']; ?>">
                            <button type="submit" class="btn btn-outline">기본 배송지로 설정</button>
                        </form>
                        <?php endif; ?>
                        
                        <form method="POST" style="display: inline;" onsubmit="return confirm('이 주소를 삭제하시겠습니까?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $addr['id']; ?>">
                            <button type="submit" class="btn btn-danger">삭제</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="add-address">
            <button type="button" onclick="window.close()" class="btn btn-outline">닫기</button>
        </div>
    </div>
    
    <script>
    function selectAddress(addressData) {
        // 부모 창의 함수 호출
        if (window.opener && !window.opener.closed) {
            window.opener.applySelectedAddress(addressData);
            window.close();
        }
    }
    </script>
</body>
</html>