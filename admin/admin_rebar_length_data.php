<?php
session_start();
require_once '../db.php';
require_once 'admin_check.php';

$spec_name = $_GET['spec_name'] ?? '';

if (!$spec_name) {
    echo '<p>규격명이 필요합니다.</p>';
    exit;
}

// 해당 규격의 데이터 가져오기
$stmt = $pdo->prepare("SELECT * FROM rebar_length_data
                       WHERE spec_name = ?
                       ORDER BY length ASC");
$stmt->execute([$spec_name]);
$lengthData = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($lengthData)) {
    echo '<p>데이터가 없습니다.</p>';
    exit;
}
?>

<style>
.length-data-table {
    width: 100%;
    border-collapse: collapse;
}

.length-data-table th {
    background: #F5F5F7;
    padding: 10px;
    text-align: left;
    font-weight: 600;
    border: 1px solid #E5E5E7;
    font-size: 13px;
}

.length-data-table td {
    padding: 8px 10px;
    border: 1px solid #E5E5E7;
    font-size: 13px;
}

.length-data-table tr:hover {
    background: #F8F9FA;
}

.data-summary {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
    margin-bottom: 20px;
}

.summary-box {
    background: #F8F9FA;
    padding: 15px;
    border-radius: 8px;
    text-align: center;
}

.summary-box .label {
    font-size: 12px;
    color: #666;
    margin-bottom: 5px;
}

.summary-box .value {
    font-size: 18px;
    font-weight: bold;
    color: #1A237E;
}
</style>

<?php
// 통계 정보 계산
$totalCount = count($lengthData);
$avgWeight = array_sum(array_column($lengthData, 'piece_weight')) / $totalCount;
$avgPieces = array_sum(array_column($lengthData, 'pieces_per_length')) / $totalCount;
$unitWeight = $lengthData[0]['unit_weight'] ?? 0;
?>

<div class="data-summary">
    <div class="summary-box">
        <div class="label">전체 데이터</div>
        <div class="value"><?php echo number_format($totalCount); ?>개</div>
    </div>
    <div class="summary-box">
        <div class="label">단위중량</div>
        <div class="value"><?php echo number_format($unitWeight, 3); ?> kg/m</div>
    </div>
    <div class="summary-box">
        <div class="label">평균 본중</div>
        <div class="value"><?php echo number_format($avgWeight, 2); ?> kg</div>
    </div>
    <div class="summary-box">
        <div class="label">평균 길이당 본수</div>
        <div class="value"><?php echo number_format($avgPieces, 1); ?>개</div>
    </div>
</div>

<div style="max-height: 400px; overflow-y: auto;">
    <table class="length-data-table">
        <thead>
            <tr>
                <th width="10%">길이(m)</th>
                <th width="20%">본중(kg)</th>
                <th width="20%">길이당 본수</th>
                <th width="20%">톤당 중량(kg)</th>
                <th width="15%">단위중량(kg/m)</th>
                <th width="15%">액션</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($lengthData as $data): ?>
            <tr>
                <td><strong><?php echo number_format($data['length'], 1); ?></strong></td>
                <td><?php echo number_format($data['piece_weight'], 2); ?></td>
                <td><?php echo number_format($data['pieces_per_length']); ?>개</td>
                <td><?php echo number_format($data['weight_per_ton'], 2); ?></td>
                <td><?php echo number_format($data['unit_weight'], 3); ?></td>
                <td>
                    <button onclick="editLengthDataRow(<?php echo $data['id']; ?>)"
                            style="padding: 3px 8px; background: #007BFF; color: white; border: none; border-radius: 3px; font-size: 12px; cursor: pointer;">수정</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
function editLengthDataRow(id) {
    alert('수정 기능은 준비 중입니다. ID: ' + id);
}
</script>