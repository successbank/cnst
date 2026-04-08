<?php
$pageTitle = '카카오톡 관리';

// 추가 스타일 정의
$additionalStyles = '
.kakao-tabs {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 24px;
    overflow: hidden;
}

.tab-menu {
    display: flex;
    border-bottom: 1px solid #E5E5E7;
}

.tab-item {
    flex: 1;
    padding: 16px 24px;
    text-align: center;
    text-decoration: none;
    color: #666;
    font-weight: 500;
    border-bottom: 3px solid transparent;
    transition: all 0.3s ease;
}

.tab-item:hover {
    background: #F5F5F7;
    color: #1A237E;
}

.tab-item.active {
    color: #1A237E;
    border-bottom-color: #1A237E;
    background: #F8F9FA;
}

.kakao-menu {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 24px;
}

.menu-card {
    background: white;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    text-decoration: none;
    color: #333;
    transition: all 0.3s ease;
}

.menu-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
}

.menu-icon {
    width: 48px;
    height: 48px;
    background: #FEE500;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin-bottom: 16px;
}

.menu-title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 8px;
}

.menu-desc {
    font-size: 14px;
    color: #666;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 24px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.stat-label {
    font-size: 14px;
    color: #666;
    margin-bottom: 8px;
}

.stat-value {
    font-size: 32px;
    font-weight: 700;
    color: #333;
}

.stat-meta {
    font-size: 12px;
    color: #999;
    margin-top: 4px;
}

.recent-table {
    width: 100%;
    border-collapse: collapse;
}

.recent-table th,
.recent-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #E5E5E7;
}

.recent-table th {
    font-weight: 600;
    color: #666;
    font-size: 14px;
}

.status-sent {
    background: #E8F5E9;
    color: #2E7D32;
}

.status-failed {
    background: #FFEBEE;
    color: #C62828;
}

.status-pending {
    background: #FFF3E0;
    color: #F57C00;
}

.content-box {
    background: white;
    padding: 32px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.chart-container {
    position: relative;
    height: 400px;
    margin-top: 20px;
}

.chart-box {
    background: white;
    padding: 32px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-top: 24px;
}

.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.chart-title {
    font-size: 18px;
    font-weight: 600;
    color: #333;
}

.chart-legend {
    display: flex;
    gap: 20px;
    font-size: 14px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
}

.legend-color {
    width: 12px;
    height: 12px;
    border-radius: 2px;
}
';

require_once 'admin_head.php';
require_once '../includes/KakaoNotificationService.php';

$kakaoService = new KakaoNotificationService($pdo);

// 통계 조회
$todayStats = $kakaoService->getStatistics(null, date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59'));
$monthStats = $kakaoService->getStatistics(null, date('Y-m-01 00:00:00'), date('Y-m-t 23:59:59'));

// 최근 발송 내역
$recentNotifications = $kakaoService->getRecentNotifications(10);

// 최근 7일간 일별 발송 통계 (차트용)
$chartData = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $dayStats = $kakaoService->getStatistics(null, $date . ' 00:00:00', $date . ' 23:59:59');
    $chartData[] = [
        'date' => $date,
        'sent' => $dayStats['sent'] ?? 0,
        'failed' => $dayStats['failed'] ?? 0,
        'pending' => $dayStats['pending'] ?? 0
    ];
}
?>

<div class="page-header">
    <h1>카카오톡 관리</h1>
    <p>카카오톡 알림 발송 현황을 확인하고 관리합니다.</p>
</div>

<!-- 탭 메뉴 -->
<div class="kakao-tabs">
    <div class="tab-menu">
        <a href="admin_kakao.php" class="tab-item active">대시보드</a>
        <a href="admin_kakao_quote.php" class="tab-item">견적문의 알림</a>
        <a href="admin_kakao_consignment.php" class="tab-item">위탁판매 알림</a>
        <a href="admin_kakao_settings.php" class="tab-item">설정</a>
    </div>
</div>

<!-- 메뉴 카드 -->
<div class="kakao-menu">
    <a href="admin_kakao_quote.php" class="menu-card">
        <div class="menu-icon">📋</div>
        <div class="menu-title">견적문의 알림</div>
        <div class="menu-desc">견적문의 관련 카카오톡 발송 내역</div>
    </a>
    
    <a href="admin_kakao_consignment.php" class="menu-card">
        <div class="menu-icon">📦</div>
        <div class="menu-title">위탁판매 알림</div>
        <div class="menu-desc">위탁판매 관련 카카오톡 발송 내역</div>
    </a>
    
    <a href="admin_kakao_settings.php" class="menu-card">
        <div class="menu-icon">⚙️</div>
        <div class="menu-title">설정</div>
        <div class="menu-desc">카카오톡 API 설정 및 템플릿 관리</div>
    </a>
</div>

<!-- 통계 -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">오늘 발송</div>
        <div class="stat-value"><?php echo $todayStats['total'] ?? 0; ?></div>
        <div class="stat-meta">
            성공: <?php echo $todayStats['sent'] ?? 0; ?> / 
            실패: <?php echo $todayStats['failed'] ?? 0; ?>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-label">이번 달 발송</div>
        <div class="stat-value"><?php echo $monthStats['total'] ?? 0; ?></div>
        <div class="stat-meta">
            성공: <?php echo $monthStats['sent'] ?? 0; ?> / 
            실패: <?php echo $monthStats['failed'] ?? 0; ?>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-label">성공률</div>
        <div class="stat-value">
            <?php 
            $successRate = ($monthStats['total'] > 0) 
                ? round(($monthStats['sent'] / $monthStats['total']) * 100, 1) 
                : 0;
            echo $successRate;
            ?>%
        </div>
        <div class="stat-meta">이번 달 기준</div>
    </div>
</div>

<!-- 최근 발송 내역 -->
<div class="content-box">
    <h2 style="font-size: 18px; margin-bottom: 20px;">최근 발송 내역</h2>
    
    <table class="recent-table">
        <thead>
            <tr>
                <th>발송일시</th>
                <th>유형</th>
                <th>수신자</th>
                <th>제목</th>
                <th>상태</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($recentNotifications)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 40px; color: #999;">
                        최근 발송 내역이 없습니다.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach($recentNotifications as $notification): ?>
                    <tr>
                        <td><?php echo date('Y-m-d H:i', strtotime($notification['created_at'])); ?></td>
                        <td>
                            <?php 
                            switch($notification['message_type']) {
                                case 'quote':
                                    echo '견적문의';
                                    break;
                                case 'consignment':
                                    echo '위탁판매';
                                    break;
                                default:
                                    echo '기타';
                            }
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($notification['phone_number']); ?></td>
                        <td><?php echo htmlspecialchars($notification['message_content']); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $notification['status']; ?>">
                                <?php 
                                switch($notification['status']) {
                                    case 'sent':
                                        echo '발송완료';
                                        break;
                                    case 'failed':
                                        echo '발송실패';
                                        break;
                                    case 'pending':
                                        echo '대기중';
                                        break;
                                }
                                ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- 발송 통계 차트 -->
<div class="chart-box">
    <div class="chart-header">
        <h2 class="chart-title">최근 7일간 발송 통계</h2>
        <div class="chart-legend">
            <div class="legend-item">
                <div class="legend-color" style="background: #1428A0;"></div>
                <span>발송 성공</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #FF6900;"></div>
                <span>발송 실패</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #767676;"></div>
                <span>대기중</span>
            </div>
        </div>
    </div>
    <div class="chart-container">
        <canvas id="sendingChart"></canvas>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// 차트 데이터 준비
const chartData = <?php echo json_encode($chartData); ?>;
const labels = chartData.map(item => {
    const date = new Date(item.date);
    return (date.getMonth() + 1) + '/' + date.getDate();
});

// 차트 설정
const ctx = document.getElementById('sendingChart').getContext('2d');
const sendingChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [
            {
                label: '발송 성공',
                data: chartData.map(item => item.sent),
                backgroundColor: '#1428A0',
                borderRadius: 6,
                barPercentage: 0.8
            },
            {
                label: '발송 실패',
                data: chartData.map(item => item.failed),
                backgroundColor: '#FF6900',
                borderRadius: 6,
                barPercentage: 0.8
            },
            {
                label: '대기중',
                data: chartData.map(item => item.pending),
                backgroundColor: '#767676',
                borderRadius: 6,
                barPercentage: 0.8
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: 12,
                titleFont: {
                    size: 14
                },
                bodyFont: {
                    size: 13
                },
                callbacks: {
                    title: function(tooltipItems) {
                        return chartData[tooltipItems[0].dataIndex].date;
                    }
                }
            }
        },
        scales: {
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    font: {
                        size: 12
                    }
                }
            },
            y: {
                beginAtZero: true,
                grid: {
                    borderDash: [2, 2],
                    color: '#E5E5E7'
                },
                ticks: {
                    font: {
                        size: 12
                    },
                    stepSize: 1
                }
            }
        }
    }
});
</script>

<?php require_once 'admin_tail.php'; ?>