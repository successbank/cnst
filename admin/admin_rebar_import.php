<?php
session_start();
require_once '../db.php';
require_once 'admin_check.php';

$pageTitle = '철근 데이터 재임포트';

// POST 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_import'])) {
    // Python 스크립트 실행
    $output = [];
    $return_var = 0;
    exec('cd /home/successbank/projects/docker/project1/html/admin && /usr/bin/python3 import_rebar_data.py 2>&1', $output, $return_var);

    $output_str = implode("\n", $output);

    if ($return_var === 0 && strpos($output_str, '✅ 임포트 완료!') !== false) {
        $_SESSION['msg'] = "엑셀 데이터 재임포트가 완료되었습니다.";
        $_SESSION['msgType'] = 'success';
    } else {
        $_SESSION['msg'] = "임포트 중 오류 발생: " . substr($output_str, -500);
        $_SESSION['msgType'] = 'error';
    }

    header('Location: admin_rebar_manage.php');
    exit;
}

require_once 'admin_head.php';
?>

<style>
.import-container {
    max-width: 800px;
    margin: 20px auto;
    padding: 20px;
}

.import-box {
    background: white;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.warning-box {
    background: #FFF3CD;
    border: 2px solid #FFC107;
    color: #856404;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.info-box {
    background: #D1ECF1;
    border: 1px solid #BEE5EB;
    color: #0C5460;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.file-info {
    background: #F8F9FA;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-family: monospace;
}

.btn-group {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
}

.btn-primary {
    background: #007BFF;
    color: white;
}

.btn-danger {
    background: #DC3545;
    color: white;
}

.btn-secondary {
    background: #6C757D;
    color: white;
}

.btn:hover {
    opacity: 0.9;
}

ul {
    margin: 10px 0;
    padding-left: 20px;
}

li {
    margin: 5px 0;
}
</style>

<div class="import-container">
    <h1>🔄 철근 데이터 재임포트</h1>

    <div class="import-box">
        <div class="warning-box">
            <strong>⚠️ 주의사항</strong>
            <ul>
                <li>기존 데이터는 백업 테이블에 저장됩니다</li>
                <li>모든 기존 데이터가 삭제되고 새 데이터로 교체됩니다</li>
                <li>이 작업은 되돌릴 수 없으니 신중히 진행하세요</li>
            </ul>
        </div>

        <div class="info-box">
            <strong>📋 임포트 정보</strong>
            <ul>
                <li>소스 파일: /home/successbank/projects/docker/project1/html/114/2/철근.xlsx</li>
                <li>임포트 스크립트: import_rebar_data.py</li>
                <li>대상 테이블: rebar_length_data</li>
                <li>백업 테이블: rebar_length_data_backup</li>
            </ul>
        </div>

        <?php
        // 현재 데이터 개수 확인
        $countQuery = "SELECT COUNT(*) as total, COUNT(DISTINCT spec_name) as specs FROM rebar_length_data";
        $countStmt = $pdo->query($countQuery);
        $counts = $countStmt->fetch(PDO::FETCH_ASSOC);
        ?>

        <div class="file-info">
            <strong>현재 데이터 상태:</strong><br>
            - 전체 레코드: <?php echo number_format($counts['total']); ?>개<br>
            - 규격 종류: <?php echo number_format($counts['specs']); ?>개
        </div>

        <form method="POST" onsubmit="return confirmImport()">
            <input type="hidden" name="confirm_import" value="1">

            <div class="btn-group">
                <button type="submit" class="btn btn-danger">재임포트 실행</button>
                <a href="admin_rebar_manage.php" class="btn btn-secondary">취소</a>
            </div>
        </form>
    </div>
</div>

<script>
function confirmImport() {
    return confirm('정말로 엑셀 데이터를 재임포트하시겠습니까?\n\n기존 데이터는 백업되지만, 이 작업은 되돌릴 수 없습니다.');
}
</script>

<?php require_once 'admin_tail.php'; ?>