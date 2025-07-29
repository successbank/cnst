<?php
// 로그인 이력 표시 컴포넌트
// admin_members.php의 회원 상세 페이지에 포함될 파일

if (!isset($member_id)) {
    return;
}

// 로그인 요약 정보 가져오기
$stmt = $pdo->prepare("
    SELECT 
        s.*,
        m.total_login_count as member_total_login,
        m.last_login,
        m.last_login_imported
    FROM members m
    LEFT JOIN member_login_summary s ON m.id = s.member_id
    WHERE m.id = ?
");
$stmt->execute([$member_id]);
$login_summary = $stmt->fetch();

// 최근 로그인 로그 가져오기 (최근 20개)
$stmt = $pdo->prepare("
    SELECT * FROM member_login_logs 
    WHERE member_id = ? 
    ORDER BY login_date DESC 
    LIMIT 20
");
$stmt->execute([$member_id]);
$recent_logins = $stmt->fetchAll();

// 월별 로그인 통계
$stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(login_date, '%Y-%m') as month,
        COUNT(*) as login_count
    FROM member_login_logs 
    WHERE member_id = ? 
    AND login_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(login_date, '%Y-%m')
    ORDER BY month DESC
");
$stmt->execute([$member_id]);
$monthly_stats = $stmt->fetchAll();
?>

<!-- 로그인 통계 섹션 -->
<div class="member-detail" style="margin-top: 24px;">
    <h3 style="font-size: 18px; margin-bottom: 16px;">로그인 이력</h3>
    
    <!-- 요약 정보 -->
    <div class="detail-grid" style="margin-bottom: 20px;">
        <div class="detail-item">
            <div class="detail-label">총 로그인 횟수</div>
            <div class="detail-value">
                <?php echo number_format($login_summary['member_total_login'] ?? 0); ?>회
            </div>
        </div>
        <div class="detail-item">
            <div class="detail-label">마지막 로그인 (시스템)</div>
            <div class="detail-value">
                <?php 
                if ($login_summary['last_login']) {
                    echo date('Y-m-d H:i:s', strtotime($login_summary['last_login']));
                } else {
                    echo '-';
                }
                ?>
            </div>
        </div>
        <div class="detail-item">
            <div class="detail-label">마지막 로그인 (가져온 데이터)</div>
            <div class="detail-value">
                <?php 
                if ($login_summary['last_login_imported']) {
                    echo date('Y-m-d H:i:s', strtotime($login_summary['last_login_imported']));
                } else {
                    echo '-';
                }
                ?>
            </div>
        </div>
        <div class="detail-item">
            <div class="detail-label">최근 30일 로그인</div>
            <div class="detail-value">
                <?php echo number_format($login_summary['last_30days_count'] ?? 0); ?>회
            </div>
        </div>
    </div>
    
    <!-- 월별 통계 차트 (간단한 텍스트 차트) -->
    <?php if (!empty($monthly_stats)): ?>
    <div style="background: white; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
        <h4 style="font-size: 14px; margin-bottom: 12px; color: #666;">월별 로그인 통계 (최근 12개월)</h4>
        <div style="font-family: monospace; font-size: 12px;">
            <?php 
            $max_count = max(array_column($monthly_stats, 'login_count'));
            foreach ($monthly_stats as $stat): 
                $bar_width = $max_count > 0 ? round(($stat['login_count'] / $max_count) * 40) : 0;
            ?>
            <div style="margin-bottom: 4px;">
                <span style="display: inline-block; width: 70px;"><?php echo $stat['month']; ?></span>
                <span style="display: inline-block; width: 40px; text-align: right;"><?php echo str_pad($stat['login_count'], 3, ' ', STR_PAD_LEFT); ?>회</span>
                <span style="color: #1A237E;"><?php echo str_repeat('█', $bar_width); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- 최근 로그인 기록 -->
    <?php if (!empty($recent_logins)): ?>
    <div style="background: white; padding: 16px; border-radius: 8px;">
        <h4 style="font-size: 14px; margin-bottom: 12px; color: #666;">최근 로그인 기록</h4>
        <table style="width: 100%; font-size: 13px;">
            <thead>
                <tr style="border-bottom: 1px solid #E5E5E7;">
                    <th style="padding: 8px; text-align: left;">로그인 일시</th>
                    <th style="padding: 8px; text-align: left;">IP 주소</th>
                    <th style="padding: 8px; text-align: left;">브라우저</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_logins as $log): ?>
                <tr style="border-bottom: 1px solid #F5F5F5;">
                    <td style="padding: 8px;">
                        <?php echo date('Y-m-d H:i:s', strtotime($log['login_date'])); ?>
                    </td>
                    <td style="padding: 8px;">
                        <?php echo $log['login_ip'] ?: '-'; ?>
                    </td>
                    <td style="padding: 8px;">
                        <?php 
                        if ($log['user_agent']) {
                            // 간단한 브라우저 이름 추출
                            if (strpos($log['user_agent'], 'Chrome') !== false) echo 'Chrome';
                            elseif (strpos($log['user_agent'], 'Firefox') !== false) echo 'Firefox';
                            elseif (strpos($log['user_agent'], 'Safari') !== false) echo 'Safari';
                            elseif (strpos($log['user_agent'], 'Edge') !== false) echo 'Edge';
                            else echo 'Other';
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div style="background: white; padding: 16px; border-radius: 8px; text-align: center; color: #666;">
        로그인 기록이 없습니다.
    </div>
    <?php endif; ?>
</div>