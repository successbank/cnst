<?php
// 관리 이력 표시 컴포넌트
// admin_members.php의 회원 상세 페이지에 포함될 파일

if (!isset($member_id) || !isset($memberLogService)) {
    return;
}

// 관리 이력 가져오기 (최근 20개)
$admin_logs = $memberLogService->getLogsByMember($member_id, 20);

// 액션 타입별 아이콘
$actionIcons = [
    'view' => '👁️',
    'update' => '✏️',
    'password_change' => '🔑',
    'status_change' => '🔄',
    'grade_change' => '⭐',
    'delete' => '🗑️',
    'memo_add' => '📝'
];
?>

<!-- 관리 이력 섹션 -->
<div class="member-detail" style="margin-top: 24px;">
    <h3 style="font-size: 18px; margin-bottom: 16px;">관리 이력</h3>
    
    <?php if (!empty($admin_logs)): ?>
    <div style="background: white; padding: 16px; border-radius: 8px;">
        <table style="width: 100%; font-size: 13px; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 2px solid #E5E5E7;">
                    <th style="padding: 10px 8px; text-align: left; font-weight: 600; color: #666;">일시</th>
                    <th style="padding: 10px 8px; text-align: center; font-weight: 600; color: #666;">작업</th>
                    <th style="padding: 10px 8px; text-align: left; font-weight: 600; color: #666;">내용</th>
                    <th style="padding: 10px 8px; text-align: left; font-weight: 600; color: #666;">관리자</th>
                    <th style="padding: 10px 8px; text-align: left; font-weight: 600; color: #666;">IP</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($admin_logs as $log): ?>
                <tr style="border-bottom: 1px solid #F5F5F5;">
                    <td style="padding: 10px 8px; white-space: nowrap;">
                        <?php echo $log['created_at_formatted']; ?>
                    </td>
                    <td style="padding: 10px 8px; text-align: center;">
                        <span style="display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; background: <?php echo $log['action_color']; ?>20; color: <?php echo $log['action_color']; ?>;">
                            <?php echo $actionIcons[$log['action_type']] ?? ''; ?> <?php echo htmlspecialchars($log['action_label']); ?>
                        </span>
                    </td>
                    <td style="padding: 10px 8px; max-width: 300px;">
                        <?php if ($log['description']): ?>
                            <span style="display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($log['description']); ?>">
                                <?php echo htmlspecialchars($log['description']); ?>
                            </span>
                        <?php else: ?>
                            <span style="color: #999;">-</span>
                        <?php endif; ?>
                        
                        <?php if ($log['old_value_decoded'] && $log['new_value_decoded']): ?>
                            <button type="button" class="log-detail-btn" 
                                    onclick="showLogDetail(<?php echo htmlspecialchars(json_encode($log, JSON_UNESCAPED_UNICODE)); ?>)"
                                    style="background: none; border: 1px solid #ddd; border-radius: 4px; padding: 2px 6px; font-size: 11px; color: #666; cursor: pointer; margin-top: 4px;">
                                상세보기
                            </button>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 10px 8px;">
                        <?php if ($log['admin_name']): ?>
                            <?php echo htmlspecialchars($log['admin_name']); ?>
                            <span style="color: #999; font-size: 11px;">(<?php echo htmlspecialchars($log['admin_user_id']); ?>)</span>
                        <?php else: ?>
                            <span style="color: #999;">시스템</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 10px 8px; font-family: monospace; font-size: 12px; color: #666;">
                        <?php echo $log['ip_address'] ?: '-'; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div style="background: white; padding: 24px; border-radius: 8px; text-align: center; color: #666;">
        관리 이력이 없습니다.
    </div>
    <?php endif; ?>
</div>

<!-- 로그 상세 모달 -->
<div id="logDetailModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background: white; border-radius: 12px; max-width: 600px; width: 90%; max-height: 80vh; overflow: auto; padding: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h4 style="margin: 0; font-size: 18px;">변경 상세 내역</h4>
            <button onclick="closeLogDetail()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #666;">&times;</button>
        </div>
        <div id="logDetailContent"></div>
    </div>
</div>

<script>
function showLogDetail(log) {
    const modal = document.getElementById('logDetailModal');
    const content = document.getElementById('logDetailContent');
    
    let html = '<div style="font-size: 14px;">';
    html += '<p style="color: #666; margin-bottom: 16px;">' + (log.created_at_formatted || '') + ' | ' + (log.action_label || '') + '</p>';
    
    if (log.old_value_decoded && log.new_value_decoded) {
        html += '<table style="width: 100%; border-collapse: collapse;">';
        html += '<thead><tr style="background: #F8F9FA;"><th style="padding: 10px; text-align: left; border: 1px solid #E5E5E7;">항목</th><th style="padding: 10px; text-align: left; border: 1px solid #E5E5E7;">변경 전</th><th style="padding: 10px; text-align: left; border: 1px solid #E5E5E7;">변경 후</th></tr></thead>';
        html += '<tbody>';
        
        const fieldLabels = {
            'email': '이메일',
            'company': '회사명',
            'homepage': '홈페이지',
            'phone': '휴대폰',
            'landline': '일반전화',
            'zipcode': '우편번호',
            'address': '주소',
            'address_detail': '상세주소',
            'is_active': '상태',
            'memo': '메모',
            'member_grade': '회원등급'
        };
        
        for (const key in log.old_value_decoded) {
            const oldVal = log.old_value_decoded[key];
            const newVal = log.new_value_decoded ? log.new_value_decoded[key] : '';
            const label = fieldLabels[key] || key;
            
            let displayOld = oldVal;
            let displayNew = newVal;
            
            // 상태 변환
            if (key === 'is_active') {
                displayOld = oldVal == 1 ? '활성' : '비활성';
                displayNew = newVal == 1 ? '활성' : '비활성';
            }
            
            // 메모는 줄여서 표시
            if (key === 'memo') {
                displayOld = oldVal ? (oldVal.length > 50 ? oldVal.substring(0, 50) + '...' : oldVal) : '-';
                displayNew = newVal ? (newVal.length > 50 ? newVal.substring(0, 50) + '...' : newVal) : '-';
            }
            
            html += '<tr>';
            html += '<td style="padding: 10px; border: 1px solid #E5E5E7; font-weight: 500;">' + escapeHtml(label) + '</td>';
            html += '<td style="padding: 10px; border: 1px solid #E5E5E7; background: #FFF3E0;">' + escapeHtml(String(displayOld || '-')) + '</td>';
            html += '<td style="padding: 10px; border: 1px solid #E5E5E7; background: #E8F5E9;">' + escapeHtml(String(displayNew || '-')) + '</td>';
            html += '</tr>';
        }
        
        html += '</tbody></table>';
    } else if (log.old_value_decoded) {
        // 삭제된 경우 (old_value만 있음)
        html += '<h5 style="margin: 16px 0 8px;">삭제 전 정보:</h5>';
        html += '<pre style="background: #F8F9FA; padding: 12px; border-radius: 6px; overflow: auto; font-size: 12px;">' + escapeHtml(JSON.stringify(log.old_value_decoded, null, 2)) + '</pre>';
    }
    
    html += '</div>';
    
    content.innerHTML = html;
    modal.style.display = 'flex';
}

function closeLogDetail() {
    document.getElementById('logDetailModal').style.display = 'none';
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// 모달 바깥 클릭 시 닫기
document.getElementById('logDetailModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeLogDetail();
    }
});
</script>
