<?php
require_once '../db.php';
require_once '../member_check.php';

// 로그인 체크
if (!isLoggedIn()) {
    exit;
}

$member_id = $_SESSION['member_id'];
$user_name = $_SESSION['name'] ?? '';
$type = $_GET['type'] ?? 'all';

try {
    // 문의 내역 조회
    $inquiries = [];
    $total = 0;
    
    if ($type === 'all' || $type === 'quote') {
        // 견적문의 조회
        $stmt = $pdo->prepare("
            SELECT 'quote' as type, id, title, company, created_at, is_answered as status
            FROM board_quote 
            WHERE member_id = ? OR writer = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$member_id, $user_name]);
        $quotes = $stmt->fetchAll();
        
        foreach($quotes as $quote) {
            $quote['type_label'] = '견적문의';
            $quote['status_label'] = $quote['status'] ? '답변완료' : '대기중';
            $inquiries[] = $quote;
        }
    }
    
    if ($type === 'all' || $type === 'consignment') {
        // 중계판매 문의 조회
        $stmt = $pdo->prepare("
            SELECT 'consignment' as type, id, title, company_name as company, created_at, status
            FROM board_consignment 
            WHERE writer = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$user_name]);
        $consignments = $stmt->fetchAll();
        
        foreach($consignments as $consignment) {
            $consignment['type_label'] = '중계판매';
            $statusLabels = [
                'active' => '진행중',
                'sold' => '판매완료',
                'inactive' => '종료'
            ];
            $consignment['status_label'] = $statusLabels[$consignment['status']] ?? '진행중';
            $inquiries[] = $consignment;
        }
    }
    
    // 날짜순 정렬
    usort($inquiries, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    $total = count($inquiries);
    
    // HTML 출력
    if (empty($inquiries)) {
        echo '<tr><td colspan="7" class="text-center">조회된 문의내역이 없습니다.</td></tr>';
    } else {
        $index = 0;
        foreach($inquiries as $inquiry) {
            $index++;
            ?>
            <tr>
                <td class="text-center"><?php echo $total - ($index - 1); ?></td>
                <td class="text-center">
                    <span class="type-badge type-<?php echo $inquiry['type']; ?>">
                        <?php echo $inquiry['type_label']; ?>
                    </span>
                </td>
                <td>
                    <strong><?php echo htmlspecialchars($inquiry['title']); ?></strong>
                </td>
                <td><?php echo htmlspecialchars($inquiry['company'] ?? '-'); ?></td>
                <td class="text-center"><?php echo date('Y-m-d', strtotime($inquiry['created_at'])); ?></td>
                <td class="text-center">
                    <span class="status-badge <?php echo $inquiry['type'] === 'quote' ? ($inquiry['status'] ? 'status-answered' : 'status-waiting') : 'status-' . $inquiry['status']; ?>">
                        <?php echo $inquiry['status_label']; ?>
                    </span>
                </td>
                <td class="text-center">
                    <button type="button" class="btn-small" onclick="viewInquiryDetail('<?php echo $inquiry['type']; ?>', <?php echo $inquiry['id']; ?>)">보기</button>
                </td>
            </tr>
            <?php
        }
    }
    
} catch(PDOException $e) {
    echo '<tr><td colspan="7" class="text-center">데이터 조회 중 오류가 발생했습니다.</td></tr>';
}
?>