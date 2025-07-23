<?php
// 에러 표시 설정 (프로덕션에서는 비활성화)
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

session_start();
require_once '../db.php';
require_once 'admin_check.php';

$pageTitle = '철근 제품 관리';

// 디버깅 메시지를 저장할 배열 (디버깅 모드일 때만 사용)
$debug_mode = false;
$debug_messages = [];

// 철근 테이블 생성 확인
try {
    // 테이블 존재 여부 확인
    $tables = $pdo->query("SHOW TABLES LIKE 'rebar_%'")->fetchAll(PDO::FETCH_COLUMN);
    $debug_messages[] = "현재 rebar 테이블: " . implode(', ', $tables);
    
    // 테이블이 없으면 생성
    $sql = file_get_contents('../sql/create_rebar_products_table.sql');
    if ($sql === false) {
        throw new Exception("테이블 생성 SQL 파일을 읽을 수 없습니다.");
    }
    
    $statements = explode(';', $sql);
    $createCount = 0;
    foreach($statements as $statement) {
        if(trim($statement)) {
            try {
                $pdo->exec($statement);
                $createCount++;
            } catch (PDOException $e) {
                // 테이블이 이미 존재하는 경우 무시
                if (strpos($e->getMessage(), 'already exists') === false) {
                    $debug_messages[] = "테이블 생성 오류: " . $e->getMessage();
                }
            }
        }
    }
    
    if ($createCount > 0) {
        $debug_messages[] = "테이블 생성 완료: {$createCount}개";
    }
} catch (Exception $e) {
    $debug_messages[] = "테이블 생성 중 오류: " . $e->getMessage();
}

// POST 데이터 디버깅
if ($debug_mode) {
    $debug_messages[] = "POST 데이터: " . print_r($_POST, true);
}

// 엑셀 데이터 임포트 처리
if (isset($_POST['import_excel_data'])) {
    $debug_messages[] = "엑셀 데이터 임포트 시작";
    error_log("엑셀 데이터 임포트 시작");
    
    try {
        // 기존 데이터 삭제 (선택사항)
        if (isset($_POST['delete_existing'])) {
            $debug_messages[] = "기존 데이터 삭제 시작";
            error_log("기존 데이터 삭제 시작");
            
            try {
                $result1 = $pdo->exec("DELETE FROM rebar_length_info");
                $debug_messages[] = "rebar_length_info 삭제: {$result1}행";
                
                $result2 = $pdo->exec("DELETE FROM rebar_prices");
                $debug_messages[] = "rebar_prices 삭제: {$result2}행";
                
                $result3 = $pdo->exec("DELETE FROM rebar_specifications");
                $debug_messages[] = "rebar_specifications 삭제: {$result3}행";
                
                error_log("기존 데이터 삭제 완료");
            } catch (PDOException $e) {
                $debug_messages[] = "삭제 중 오류: " . $e->getMessage();
                throw $e;
            }
        }
        
        // SQL 파일 경로 확인
        $sqlFilePath = __DIR__ . '/../import_rebar_data_v2.sql';
        error_log("SQL 파일 경로: " . $sqlFilePath);
        
        if (!file_exists($sqlFilePath)) {
            throw new Exception("SQL 파일을 찾을 수 없습니다: " . $sqlFilePath);
        }
        
        // SQL 파일 실행
        $sql = file_get_contents($sqlFilePath);
        if ($sql === false) {
            throw new Exception("SQL 파일 읽기 실패");
        }
        
        $debug_messages[] = "SQL 파일 크기: " . strlen($sql) . " bytes";
        error_log("SQL 파일 크기: " . strlen($sql) . " bytes");
        
        // SQL 파일 내용 일부 확인
        $debug_messages[] = "SQL 파일 시작 부분: " . substr($sql, 0, 200) . "...";
        
        $statements = explode(';', $sql);
        $debug_messages[] = "총 SQL 구문 수: " . count($statements);
        error_log("총 SQL 구문 수: " . count($statements));
        
        // 처음 몇 개 구문 확인
        for ($i = 0; $i < min(3, count($statements)); $i++) {
            $debug_messages[] = "구문 #" . ($i+1) . " 길이: " . strlen($statements[$i]) . " bytes";
        }
        
        $successCount = 0;
        $failCount = 0;
        
        foreach($statements as $index => $statement) {
            $trimmed = trim($statement);
            
            // 디버깅: 구문 확인
            if ($index < 3) {
                $debug_messages[] = "구문 #" . ($index+1) . " 확인 - 비어있음: " . (empty($trimmed) ? "예" : "아니오") . ", 주석: " . (strpos($trimmed, '--') === 0 ? "예" : "아니오");
                $debug_messages[] = "구문 #" . ($index+1) . " 처음 50자: " . substr($trimmed, 0, 50);
            }
            
            // 주석 제거 후 실제 SQL 추출
            $lines = explode("\n", $trimmed);
            $sqlLines = [];
            foreach($lines as $line) {
                if (strpos(trim($line), '--') !== 0) {
                    $sqlLines[] = $line;
                }
            }
            $cleanSQL = trim(implode("\n", $sqlLines));
            
            if(!empty($cleanSQL)) {
                try {
                    // 디버깅: 실행할 SQL 로그
                    if ($successCount < 10) {  // 처음 10개만 로그
                        $debug_messages[] = "실행 SQL #" . ($successCount + 1) . ": " . substr($cleanSQL, 0, 100) . "...";
                    }
                    
                    // spec_id를 동적으로 매핑
                    if (strpos($cleanSQL, 'INSERT INTO rebar_length_info') !== false || 
                        strpos($cleanSQL, 'INSERT INTO rebar_prices') !== false) {
                        
                        // 현재 규격 ID 매핑 가져오기
                        $specMapping = [];
                        $specs = $pdo->query("SELECT id, spec_name FROM rebar_specifications ORDER BY spec_name")->fetchAll();
                        $specOrder = ['D10' => 1, 'D13' => 2, 'D16' => 3, 'D19' => 4, 'D22' => 5, 'D25' => 6, 
                                     'D29' => 7, 'D32' => 8, 'D35' => 9, 'D38' => 10, 'D41' => 11, 'D51' => 12];
                        
                        foreach ($specs as $spec) {
                            if (isset($specOrder[$spec['spec_name']])) {
                                $specMapping[$specOrder[$spec['spec_name']]] = $spec['id'];
                            }
                        }
                        
                        // spec_id 치환
                        foreach ($specMapping as $oldId => $newId) {
                            $cleanSQL = str_replace("({$oldId},", "({$newId},", $cleanSQL);
                        }
                    }
                    
                    $result = $pdo->exec($cleanSQL);
                    $successCount++;
                    
                    // 영향받은 행 수 로그
                    if ($result !== false && $result > 0) {
                        $debug_messages[] = "SQL #" . $successCount . " 영향받은 행: " . $result;
                    }
                    
                    if ($successCount % 100 == 0) {
                        error_log("진행 상황: " . $successCount . " 구문 실행 완료");
                    }
                } catch (PDOException $e) {
                    $failCount++;
                    $debug_messages[] = "SQL 오류 #" . ($index + 1) . ": " . $e->getMessage();
                    error_log("SQL 실행 오류 (구문 #" . ($index + 1) . "): " . $e->getMessage());
                    error_log("실패한 SQL: " . substr($trimmed, 0, 200) . "...");
                    
                    // 첫 번째 오류 발생 시 중단
                    if ($failCount == 1) {
                        throw new Exception("SQL 실행 오류: " . $e->getMessage());
                    }
                }
            }
        }
        
        $debug_messages[] = "임포트 완료 - 성공: " . $successCount . ", 실패: " . $failCount;
        error_log("임포트 완료 - 성공: " . $successCount . ", 실패: " . $failCount);
        
        $_SESSION['success_message'] = "철근 데이터가 성공적으로 임포트되었습니다. (총 " . $successCount . "개 구문 실행)";
        $_SESSION['debug_messages'] = $debug_messages;
        header('Location: admin_rebar_products.php');
        exit;
    } catch (Exception $e) {
        $debug_messages[] = "데이터 임포트 오류: " . $e->getMessage();
        error_log("데이터 임포트 오류: " . $e->getMessage());
        $_SESSION['error_message'] = "데이터 임포트 중 오류 발생: " . $e->getMessage();
        $_SESSION['debug_messages'] = $debug_messages;
    }
}

// 가격 업데이트는 admin_rebar_materials.php에서 처리

// 데이터 카운트 확인 (디버깅용)
$count_specs = $pdo->query("SELECT COUNT(*) FROM rebar_specifications")->fetchColumn();
$count_lengths = $pdo->query("SELECT COUNT(*) FROM rebar_length_info")->fetchColumn();
$count_prices = $pdo->query("SELECT COUNT(*) FROM rebar_prices")->fetchColumn();

$debug_messages[] = "현재 데이터베이스 상태: 규격={$count_specs}개, 길이정보={$count_lengths}개, 가격={$count_prices}개";

// 규격별 ID 확인 (디버깅용)
if ($count_specs > 0) {
    $specs = $pdo->query("SELECT id, spec_name FROM rebar_specifications ORDER BY id")->fetchAll();
    $spec_info = [];
    foreach ($specs as $spec) {
        $spec_info[] = $spec['spec_name'] . "=" . $spec['id'];
    }
    $debug_messages[] = "규격 ID 매핑: " . implode(', ', $spec_info);
}

// 현재 철근 규격 목록 조회
$stmt = $pdo->query("
    SELECT 
        rs.*,
        rp.unit_price,
        (SELECT COUNT(*) FROM rebar_length_info WHERE spec_id = rs.id) as length_count
    FROM rebar_specifications rs
    LEFT JOIN rebar_prices rp ON rs.id = rp.spec_id AND rp.is_active = TRUE
    ORDER BY rs.display_order
");
$rebar_specs = $stmt->fetchAll();

// 추가 스타일
$additionalStyles = '
.rebar-section {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 30px;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.section-header h2 {
    font-size: 20px;
    font-weight: 600;
    color: #333;
    margin: 0;
}

.import-form {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

.price-form {
    margin-top: 20px;
}

.price-table {
    width: 100%;
    border-collapse: collapse;
}

.price-table th {
    background: #f8f9fa;
    padding: 12px;
    text-align: left;
    font-weight: 600;
    border-bottom: 2px solid #dee2e6;
}

.price-table td {
    padding: 12px;
    border-bottom: 1px solid #e9ecef;
}

.price-input {
    width: 120px;
    padding: 8px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    text-align: right;
}

.btn-import {
    background: #28a745;
    color: white;
    padding: 8px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
}

.btn-import:hover {
    background: #218838;
}

.info-box {
    background: #f8f9fa;
    border-left: 4px solid #17a2b8;
    padding: 15px;
    margin-bottom: 20px;
}

.formula-box {
    background: #e3f2fd;
    padding: 15px;
    border-radius: 8px;
    margin: 15px 0;
    font-family: monospace;
}

.spec-details {
    display: none;
    background: #f8f9fa;
    padding: 15px;
    margin-top: 10px;
    border-radius: 8px;
}

.toggle-details {
    cursor: pointer;
    color: #007bff;
    text-decoration: underline;
}
';

require_once 'admin_head.php';
?>

<div class="page-header">
    <h1>철근 제품 관리</h1>
    <p>철근 제품의 규격, 가격 정보를 관리합니다.</p>
</div>

<?php
// 디버깅 메시지 표시 (디버깅 모드일 때만)
if ($debug_mode) {
    if (isset($_SESSION['debug_messages'])) {
        echo '<div style="background: #ffc; border: 1px solid #cc0; padding: 10px; margin: 10px 0;">';
        echo '<h3>디버깅 정보:</h3>';
        foreach ($_SESSION['debug_messages'] as $msg) {
            echo '<pre>' . htmlspecialchars($msg) . '</pre>';
        }
        echo '</div>';
        unset($_SESSION['debug_messages']);
    }

    // 현재 디버깅 메시지 표시
    if (!empty($debug_messages)) {
        echo '<div style="background: #ffc; border: 1px solid #cc0; padding: 10px; margin: 10px 0;">';
        echo '<h3>현재 페이지 디버깅 정보:</h3>';
        foreach ($debug_messages as $msg) {
            echo '<pre>' . htmlspecialchars($msg) . '</pre>';
        }
        echo '</div>';
    }
}

// 메시지 표시
if (isset($_SESSION['success_message'])) {
    echo '<div class="msg success">' . $_SESSION['success_message'] . '</div>';
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    echo '<div class="msg error">' . $_SESSION['error_message'] . '</div>';
    unset($_SESSION['error_message']);
}
?>

<!-- 엑셀 데이터 임포트 섹션 -->
<div class="rebar-section">
    <div class="section-header">
        <h2>엑셀 데이터 임포트</h2>
    </div>
    
    <div class="info-box">
        <p><strong>엑셀 파일 위치:</strong> ./html/114/철근.xlsx</p>
        <p><strong>데이터 정보:</strong></p>
        <ul>
            <li>철근 규격: 12개 (D10, D13, D16, D19, D22, D25, D29, D32, D35, D38, D41, D51)</li>
            <li>길이 옵션: 61개 (6m ~ 12m, 0.1m 간격)</li>
            <li>총 데이터: 732개 (12 규격 × 61 길이)</li>
        </ul>
        <p>이 버튼을 클릭하면 엑셀 파일의 데이터를 데이터베이스에 자동으로 입력합니다.</p>
    </div>
    
    <form method="POST" action="" class="import-form">
        <label>
            <input type="checkbox" name="delete_existing" value="1">
            기존 데이터 삭제 후 임포트
        </label>
        <button type="submit" name="import_excel_data" value="1" class="btn-import" 
                onclick="return confirm('엑셀 데이터를 임포트하시겠습니까?')">
            엑셀 데이터 임포트
        </button>
    </form>
    
    
</div>

<!-- 철근 규격 및 가격 관리 -->
<div class="rebar-section">
    <div class="section-header">
        <h2>철근 규격 및 가격 설정</h2>
    </div>
    
    <div class="formula-box">
        <strong>가격 계산 공식:</strong> 총금액 = 본수(수량) × 길이(m) × 단위중량(kg/m) × 단가(원/kg)
    </div>
    
    <?php if (empty($rebar_specs)): ?>
        <p>등록된 철근 규격이 없습니다. 먼저 엑셀 데이터를 임포트해주세요.</p>
    <?php else: ?>
        <div>
            <table class="price-table">
                <thead>
                    <tr>
                        <th>규격</th>
                        <th>직경(mm)</th>
                        <th>단위중량(kg/m)</th>
                        <th>톤당 본수<br><small>(8m 기준)</small></th>
                        <th>단가(원/kg)</th>
                        <th>길이 옵션</th>
                        <th>상태</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rebar_specs as $spec): 
                        // 8m 기준 톤당 본수 조회
                        $stmt_8m = $pdo->prepare("SELECT pieces_per_ton FROM rebar_length_info WHERE spec_id = ? AND length = 8 LIMIT 1");
                        $stmt_8m->execute([$spec['id']]);
                        $pieces_8m = $stmt_8m->fetchColumn();
                    ?>
                    <tr>
                        <td><strong><?php echo escape($spec['spec_name']); ?></strong></td>
                        <td><?php echo escape($spec['diameter']); ?></td>
                        <td><?php echo escape($spec['unit_weight']); ?></td>
                        <td><?php echo $pieces_8m ? escape($pieces_8m) : '-'; ?></td>
                        <td>
                            <span style="color: #666;">
                                <?php echo $spec['unit_price'] ? number_format($spec['unit_price']) . '원' : '미설정'; ?>
                            </span>
                            <br>
                            <small style="color: #999;">
                                <a href="admin_rebar_materials.php" style="color: #3498db;">재질/단가 관리에서 설정</a>
                            </small>
                        </td>
                        <td>
                            <span class="toggle-details" onclick="toggleDetails(<?php echo $spec['id']; ?>)">
                                <?php echo $spec['length_count']; ?>개 길이
                            </span>
                        </td>
                        <td>
                            <?php if ($spec['is_active']): ?>
                                <span style="color: green;">활성</span>
                            <?php else: ?>
                                <span style="color: red;">비활성</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="6">
                            <div id="details_<?php echo $spec['id']; ?>" class="spec-details">
                                <?php
                                // 길이별 정보 조회
                                $stmt2 = $pdo->prepare("
                                    SELECT length, weight_per_piece, pieces_per_ton 
                                    FROM rebar_length_info 
                                    WHERE spec_id = ? 
                                    ORDER BY length
                                ");
                                $stmt2->execute([$spec['id']]);
                                $lengths = $stmt2->fetchAll();
                                
                                if ($lengths) {
                                    echo '<table style="width: 100%; font-size: 13px;">';
                                    echo '<tr><th>길이(m)</th><th>본중(kg)</th><th>톤당 본수</th></tr>';
                                    foreach ($lengths as $length) {
                                        echo '<tr>';
                                        echo '<td>' . $length['length'] . '</td>';
                                        echo '<td>' . $length['weight_per_piece'] . '</td>';
                                        echo '<td>' . $length['pieces_per_ton'] . '</td>';
                                        echo '</tr>';
                                    }
                                    echo '</table>';
                                }
                                ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- 가격 업데이트는 재질/단가 관리 페이지에서 처리 -->
        </div>
    <?php endif; ?>
</div>

<!-- 사용 방법 안내 -->
<div class="rebar-section">
    <div class="section-header">
        <h2>사용 방법</h2>
    </div>
    
    <ol>
        <li><strong>엑셀 데이터 임포트:</strong> 상단의 "엑셀 데이터 임포트" 버튼을 클릭하여 철근 규격 정보를 가져옵니다.</li>
        <li><strong>단가 설정:</strong> "철근 재질/단가" 메뉴에서 기본 단가와 재질별 추가 단가를 설정합니다.</li>
        <li><strong>사용자 화면:</strong> 사용자는 제품 페이지에서 철근을 선택하고, 길이와 수량을 입력하면 자동으로 견적이 계산됩니다.</li>
    </ol>
    
    <div class="info-box" style="margin-top: 20px;">
        <h4>계산 예시</h4>
        <p>D16 철근, 8m 길이, 100본 주문 시:</p>
        <ul>
            <li>단위중량: 1.56 kg/m</li>
            <li>총중량: 100본 × 8m × 1.56kg/m = 1,248kg</li>
            <li>단가가 1,000원/kg인 경우: 1,248kg × 1,000원 = 1,248,000원</li>
        </ul>
    </div>
</div>

<script>
function toggleDetails(specId) {
    const details = document.getElementById('details_' + specId);
    if (details.style.display === 'none' || details.style.display === '') {
        details.style.display = 'block';
    } else {
        details.style.display = 'none';
    }
}
</script>

<?php require_once 'admin_tail.php'; ?>