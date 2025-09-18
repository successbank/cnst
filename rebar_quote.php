<?php
session_start();
require_once 'db.php';

// 철근 규격 목록 조회
$stmt = $pdo->query("
    SELECT 
        rs.*,
        rp.unit_price
    FROM rebar_specifications rs
    LEFT JOIN rebar_prices rp ON rs.id = rp.spec_id AND rp.is_active = TRUE
    WHERE rs.is_active = TRUE
    ORDER BY rs.display_order
");
$rebar_specs = $stmt->fetchAll();

// 재질 목록 조회
$stmt = $pdo->query("
    SELECT * FROM rebar_materials 
    WHERE is_active = TRUE 
    ORDER BY display_order
");
$rebar_materials = $stmt->fetchAll();

// AJAX 요청 처리
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    if ($_GET['action'] === 'get_lengths' && isset($_GET['spec_id'])) {
        // 특정 규격의 길이 목록 조회
        $stmt = $pdo->prepare("
            SELECT 
                rl.*,
                rs.unit_weight,
                rp.unit_price
            FROM rebar_length_info rl
            JOIN rebar_specifications rs ON rl.spec_id = rs.id
            LEFT JOIN rebar_prices rp ON rs.id = rp.spec_id AND rp.is_active = TRUE
            WHERE rl.spec_id = ?
            ORDER BY rl.length
        ");
        $stmt->execute([$_GET['spec_id']]);
        $lengths = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'data' => $lengths]);
        exit;
    }
    
    if ($_GET['action'] === 'calculate' && isset($_GET['spec_id']) && isset($_GET['length']) && isset($_GET['quantity'])) {
        // 견적 계산
        $stmt = $pdo->prepare("
            SELECT 
                rl.*,
                rs.spec_name,
                rs.unit_weight,
                rp.unit_price,
                rl.total_weight
            FROM rebar_length_info rl
            JOIN rebar_specifications rs ON rl.spec_id = rs.id
            LEFT JOIN rebar_prices rp ON rs.id = rp.spec_id AND rp.is_active = TRUE
            WHERE rl.spec_id = ? AND rl.length = ?
        ");
        $stmt->execute([$_GET['spec_id'], $_GET['length']]);
        $result = $stmt->fetch();
        
        if ($result) {
            $ton_quantity = intval($_GET['quantity']); // 사용자 입력은 톤 단위
            $material_id = isset($_GET['material_id']) ? intval($_GET['material_id']) : null;
            
            // 재질 추가 단가 조회
            $material_price = 0;
            $material_name = '';
            if ($material_id) {
                $stmt = $pdo->prepare("SELECT material_name, additional_price FROM rebar_materials WHERE id = ?");
                $stmt->execute([$material_id]);
                $material = $stmt->fetch();
                if ($material) {
                    $material_price = $material['additional_price'];
                    $material_name = $material['material_name'];
                }
            }
            
            // 톤당 본수를 이용하여 실제 본수 계산
            $pieces_per_ton = $result['pieces_per_ton'];
            $actual_quantity = $ton_quantity * $pieces_per_ton; // 실제 본수 = 톤 수 × 톤당 본수
            
            // 계산식 적용
            $weight_per_piece = $result['unit_weight'] * $result['length']; // 본당 중량 = 단위중량 × 길이
            
            // 엑셀의 고정 중량값 사용 (total_weight가 있으면 사용, 없으면 계산)
            $has_missing_data = false;
            if ($result['total_weight'] && $result['pieces_per_ton'] && $result['pieces_per_ton'] > 0) {
                $total_weight = $result['total_weight'] * $ton_quantity; // 총 중량 = 엑셀 톤당 중량 × 톤수
            } else if (!$result['pieces_per_ton'] || $result['pieces_per_ton'] == 0 || !$result['total_weight']) {
                // 본수 또는 중량 데이터가 없는 경우 (0도 없는 것으로 처리)
                $has_missing_data = true;
                $total_weight = 0; // 임시값
                $actual_quantity = 0; // 본수도 0으로
            } else {
                $total_weight = $weight_per_piece * $actual_quantity; // 총 중량 = 본당 중량 × 실제 본수
            }
            $base_price = $result['unit_price'] ?: 0;
            $final_price = $base_price + $material_price; // 적용 단가 = 기준단가 + 재질 추가단가
            $total_price = $total_weight * $final_price; // 총 금액 = 총 중량 × 적용 단가
            
            $data = [
                'spec_name' => $result['spec_name'],
                'length' => $result['length'],
                'ton_quantity' => $ton_quantity,  // 톤 단위 수량
                'quantity' => $actual_quantity,    // 실제 본수
                'unit_weight' => $result['unit_weight'],
                'weight_per_piece' => $weight_per_piece,
                'total_weight' => $total_weight,
                'base_price' => $base_price,
                'material_name' => $material_name,
                'material_price' => $material_price,
                'final_price' => $final_price,
                'total_price' => $total_price,
                'pieces_per_ton' => $pieces_per_ton,
                'has_missing_data' => $has_missing_data
            ];
            
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            echo json_encode(['success' => false, 'message' => '데이터를 찾을 수 없습니다.']);
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>철근 견적 계산</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Noto Sans KR', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 32px;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #7f8c8d;
            font-size: 18px;
        }
        
        .main-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        @media (max-width: 768px) {
            .main-content {
                grid-template-columns: 1fr;
            }
        }
        
        .section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .section h2 {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #ecf0f1;
        }
        
        .spec-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 10px;
            margin-bottom: 30px;
        }
        
        .spec-btn {
            padding: 15px;
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s ease;
            font-size: 16px;
            font-weight: 500;
        }
        
        .spec-btn:hover {
            background: #e9ecef;
            border-color: #3498db;
        }
        
        .spec-btn.active {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }
        
        .form-group select,
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .btn:hover {
            background: #2980b9;
        }
        
        .btn:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
        }
        
        .result-box {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            margin-top: 20px;
        }
        
        .result-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .result-item:last-child {
            border-bottom: none;
            margin-top: 10px;
            padding-top: 20px;
            border-top: 2px solid #ddd;
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .result-label {
            color: #7f8c8d;
        }
        
        .result-value {
            font-weight: 500;
            color: #2c3e50;
        }
        
        .info-box {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
            font-size: 14px;
            color: #1976d2;
        }
        
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
            color: #7f8c8d;
        }
        
        .error {
            color: #e74c3c;
            padding: 10px;
            background: #fee;
            border-radius: 6px;
            margin-top: 10px;
        }
        
        .product-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn-quote {
            flex: 1;
            padding: 18px 30px;
            background: #1428A0;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-quote:hover {
            background: #0F1F7A;
            transform: translateY(-2px);
        }

        .btn-secondary {
            padding: 18px 30px;
            background: white;
            color: #1428A0;
            border: 2px solid #1428A0;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
            text-align: center;
        }

        .btn-secondary:hover {
            background: #1428A0;
            color: white;
        }
        
        @media (max-width: 768px) {
            .product-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>철근 견적 계산기</h1>
            <p>원하시는 철근 규격과 길이, 수량을 선택하시면 즉시 견적을 확인하실 수 있습니다.</p>
        </div>
        
        <div class="main-content">
            <!-- 왼쪽: 제품 선택 -->
            <div class="section">
                <h2>제품 선택</h2>
                
                <!-- 규격 선택 -->
                <div class="form-group">
                    <label>철근 규격 선택</label>
                    <div class="spec-grid">
                        <?php foreach ($rebar_specs as $spec): ?>
                            <button class="spec-btn" data-spec-id="<?= $spec['id'] ?>" data-spec-name="<?= htmlspecialchars($spec['spec_name']) ?>">
                                <?= htmlspecialchars($spec['spec_name']) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- 재질 선택 -->
                <div class="form-group">
                    <label>재질 선택</label>
                    <div class="spec-grid">
                        <?php foreach ($rebar_materials as $material): ?>
                            <button class="spec-btn material-btn" data-material-id="<?= $material['id'] ?>" data-material-name="<?= htmlspecialchars($material['material_name']) ?>" data-material-price="<?= $material['additional_price'] ?>">
                                <?= htmlspecialchars($material['material_name']) ?>
                                <small style="display: block; font-size: 12px; margin-top: 3px;">+<?= number_format($material['additional_price']) ?>원/kg</small>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- 길이 선택 -->
                <div class="form-group">
                    <label>길이 선택</label>
                    <select id="length-select" disabled>
                        <option value="">먼저 규격을 선택해주세요</option>
                    </select>
                </div>
                
                <!-- 수량 입력 -->
                <div class="form-group">
                    <label>수량 (톤)</label>
                    <input type="number" id="quantity-input" min="1" value="1" disabled>
                    <small style="display: block; margin-top: 5px; color: #666;">* 입력한 톤 수 × 톤당 본수로 계산됩니다</small>
                </div>
                
                <div class="loading" id="loading">계산 중...</div>
                <div id="error-message"></div>
            </div>
            
            <!-- 오른쪽: 견적 결과 -->
            <div class="section">
                <h2>견적 결과</h2>
                
                <div id="result-container" style="display: none;">
                    <div class="result-box">
                        <div class="result-item">
                            <span class="result-label">선택한 제품</span>
                            <span class="result-value" id="result-product">-</span>
                        </div>
                        <div class="result-item">
                            <span class="result-label">길이</span>
                            <span class="result-value" id="result-length">-</span>
                        </div>
                        <div class="result-item">
                            <span class="result-label">톤수</span>
                            <span class="result-value" id="result-ton-quantity">-</span>
                        </div>
                        <div class="result-item">
                            <span class="result-label">총 본수</span>
                            <span class="result-value" id="result-quantity">-</span>
                        </div>
                        <div class="result-item">
                            <span class="result-label">재질</span>
                            <span class="result-value" id="result-material">-</span>
                        </div>
                        <div class="result-item">
                            <span class="result-label">단위중량</span>
                            <span class="result-value" id="result-unit-weight">-</span>
                        </div>
                        <div class="result-item">
                            <span class="result-label">본당 중량</span>
                            <span class="result-value" id="result-weight-per-piece">-</span>
                        </div>
                        <div class="result-item">
                            <span class="result-label">총 중량</span>
                            <span class="result-value" id="result-total-weight">-</span>
                        </div>
                        <div class="result-item">
                            <span class="result-label">기본 단가</span>
                            <span class="result-value" id="result-base-price">-</span>
                        </div>
                        <div class="result-item">
                            <span class="result-label">재질 추가단가</span>
                            <span class="result-value" id="result-material-price">-</span>
                        </div>
                        <div class="result-item">
                            <span class="result-label">적용 단가</span>
                            <span class="result-value" id="result-final-price">-</span>
                        </div>
                        <div class="result-item">
                            <span class="result-label">톤당 본수</span>
                            <span class="result-value" id="result-pieces-per-ton">-</span>
                        </div>
                        <div class="result-item">
                            <span class="result-label">총 금액</span>
                            <span class="result-value" id="result-total-price">-</span>
                        </div>
                    </div>
                    
                    <div class="info-box">
                        <strong>계산식:</strong><br>
                        본당 중량 = 단위중량 × 길이<br>
                        총 중량 = 본당 중량 × 수량(BD 본수)<br>
                        적용 단가 = 기준단가 + 재질 추가단가<br>
                        총 금액 = 총 중량 × 적용 단가
                    </div>
                    
                    <div class="product-actions">
                        <button type="button" onclick="sendQuote()" class="btn-quote">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M2 5a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V5z"/>
                                <path d="M5 7h10M5 10h10M5 13h6"/>
                            </svg>
                            견적 문의하기
                        </button>
                        <a href="tel:032-564-1616" class="btn-secondary">
                            전화 문의
                        </a>
                    </div>
                </div>
                
                <div id="empty-result" class="result-box">
                    <p style="text-align: center; color: #7f8c8d; padding: 40px 0;">
                        철근 규격, 재질, 길이, 수량을 모두 선택하면 자동으로 계산됩니다.
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        let selectedSpecId = null;
        let selectedSpecName = '';
        let selectedMaterialId = null;
        let selectedMaterialName = '';
        
        // 규격 버튼 클릭 이벤트
        document.querySelectorAll('.spec-btn:not(.material-btn)').forEach(btn => {
            btn.addEventListener('click', function() {
                // 이전 선택 제거
                document.querySelectorAll('.spec-btn:not(.material-btn)').forEach(b => b.classList.remove('active'));
                
                // 현재 선택 추가
                this.classList.add('active');
                selectedSpecId = this.dataset.specId;
                selectedSpecName = this.dataset.specName;
                
                // 길이 옵션 로드
                loadLengths(selectedSpecId);
            });
        });
        
        // 재질 버튼 클릭 이벤트
        document.querySelectorAll('.material-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // 이전 선택 제거
                document.querySelectorAll('.material-btn').forEach(b => b.classList.remove('active'));
                
                // 현재 선택 추가
                this.classList.add('active');
                selectedMaterialId = this.dataset.materialId;
                selectedMaterialName = this.dataset.materialName;
                
                autoCalculate();
            });
        });
        
        // 길이 옵션 로드
        function loadLengths(specId) {
            const lengthSelect = document.getElementById('length-select');
            lengthSelect.innerHTML = '<option value="">로딩 중...</option>';
            lengthSelect.disabled = true;
            
            fetch(`rebar_quote.php?action=get_lengths&spec_id=${specId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        lengthSelect.innerHTML = '<option value="">길이를 선택하세요</option>';
                        data.data.forEach(item => {
                            const option = document.createElement('option');
                            option.value = item.length;
                            option.dataset.piecesPerTon = item.pieces_per_ton;
                            option.textContent = `${item.length}m (톤당 ${item.pieces_per_ton}본)`;
                            lengthSelect.appendChild(option);
                        });
                        lengthSelect.disabled = false;
                        document.getElementById('quantity-input').disabled = false;
                        autoCalculate();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('길이 정보를 불러오는데 실패했습니다.');
                });
        }
        
        // 자동 계산 함수
        function autoCalculate() {
            const lengthSelect = document.getElementById('length-select');
            const quantityInput = document.getElementById('quantity-input');
            
            // 모든 필수 항목이 선택되었는지 확인
            if (selectedSpecId && selectedMaterialId && lengthSelect.value && quantityInput.value > 0) {
                performCalculation();
            }
        }
        
        // 실제 계산 수행
        function performCalculation() {
            const length = document.getElementById('length-select').value;
            const quantity = document.getElementById('quantity-input').value;
            
            document.getElementById('loading').style.display = 'block';
            
            fetch(`rebar_quote.php?action=calculate&spec_id=${selectedSpecId}&material_id=${selectedMaterialId}&length=${length}&quantity=${quantity}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('loading').style.display = 'none';
                    
                    if (data.success) {
                        showResult(data.data);
                    } else {
                        showError(data.message || '계산 중 오류가 발생했습니다.');
                    }
                })
                .catch(error => {
                    document.getElementById('loading').style.display = 'none';
                    console.error('Error:', error);
                    showError('계산 중 오류가 발생했습니다.');
                });
        }
        
        // 길이 선택 변경 이벤트
        document.getElementById('length-select').addEventListener('change', autoCalculate);
        
        // 수량 입력 변경 이벤트
        document.getElementById('quantity-input').addEventListener('input', function() {
            // 디바운싱을 위한 타이머
            clearTimeout(this.inputTimer);
            this.inputTimer = setTimeout(() => {
                autoCalculate();
            }, 500); // 0.5초 후 자동 계산
        });
        
        // 결과 표시
        function showResult(data) {
            // 데이터가 누락된 경우 문의 모달 표시
            if (data.has_missing_data) {
                showInquiryModal();
                return;
            }
            
            document.getElementById('empty-result').style.display = 'none';
            document.getElementById('result-container').style.display = 'block';
            
            document.getElementById('result-product').textContent = data.spec_name;
            document.getElementById('result-length').textContent = data.length + 'm';
            document.getElementById('result-ton-quantity').textContent = data.ton_quantity + '톤';
            document.getElementById('result-quantity').textContent = data.quantity.toLocaleString() + '본 (' + data.ton_quantity + '톤 × ' + data.pieces_per_ton + '본)';
            document.getElementById('result-material').textContent = data.material_name || '-';
            document.getElementById('result-unit-weight').textContent = data.unit_weight + 'kg/m';
            document.getElementById('result-weight-per-piece').textContent = data.weight_per_piece.toFixed(2) + 'kg';
            document.getElementById('result-total-weight').textContent = data.total_weight.toFixed(2) + 'kg';
            document.getElementById('result-base-price').textContent = data.base_price.toLocaleString() + '원/kg';
            document.getElementById('result-material-price').textContent = '+' + data.material_price.toLocaleString() + '원/kg';
            document.getElementById('result-final-price').textContent = data.final_price.toLocaleString() + '원/kg';
            document.getElementById('result-pieces-per-ton').textContent = data.pieces_per_ton + '본';
            document.getElementById('result-total-price').textContent = Math.round(data.total_price).toLocaleString() + '원';
        }
        
        // 에러 표시
        function showError(message) {
            const errorDiv = document.getElementById('error-message');
            errorDiv.innerHTML = `<div class="error">${message}</div>`;
            setTimeout(() => {
                errorDiv.innerHTML = '';
            }, 3000);
        }
        
        // 견적 카트에 제품 추가
        function addToQuoteCart() {
            // 계산된 데이터가 있는지 확인
            const resultContainer = document.getElementById('result-container');
            if (resultContainer.style.display === 'none') {
                alert('먼저 제품을 선택하고 계산을 완료해주세요.');
                return;
            }
            
            // 계산된 데이터 수집
            const productName = document.getElementById('result-product').textContent;
            const quoteData = {
                id: 'rebar_' + selectedSpecId + '_' + selectedMaterialId, // 고유 ID 생성
                name: productName,
                specifications: productName + ' ' + document.getElementById('result-material').textContent,
                length: document.getElementById('result-length').textContent,
                ton_quantity: document.getElementById('result-ton-quantity').textContent,
                quantity: document.getElementById('result-quantity').textContent.split('본')[0].trim(),
                material: document.getElementById('result-material').textContent,
                totalWeight: document.getElementById('result-total-weight').textContent,
                totalPrice: document.getElementById('result-total-price').textContent,
                unitPrice: document.getElementById('result-final-price').textContent,
                category: '철근'
            };
            
            // 세션 스토리지에서 기존 카트 가져오기
            let quoteCart = JSON.parse(sessionStorage.getItem('quoteCart') || '[]');
            
            // 중복 확인
            const existingIndex = quoteCart.findIndex(item => 
                item.name === quoteData.name && 
                item.length === quoteData.length &&
                item.material === quoteData.material
            );
            
            if (existingIndex === -1) {
                // 새 제품 추가
                quoteCart.push({
                    ...quoteData,
                    addedAt: new Date().toISOString()
                });
            } else {
                // 이미 있는 제품은 수량 증가
                const existingQty = parseInt(quoteCart[existingIndex].quantity) || 1;
                const newQty = parseInt(quoteData.quantity) || 1;
                quoteCart[existingIndex].quantity = (existingQty + newQty).toString();
                // 금액도 업데이트
                quoteCart[existingIndex].totalPrice = document.getElementById('result-total-price').textContent;
                quoteCart[existingIndex].totalWeight = document.getElementById('result-total-weight').textContent;
            }
            
            // 세션 스토리지에 저장
            sessionStorage.setItem('quoteCart', JSON.stringify(quoteCart));
            
            // 카트 카운트 업데이트
            updateCartCount();
            
            // 모달에 제품명 표시
            document.getElementById('modalProductName').textContent = productName + ' ' + quoteData.material;
            
            // 모달 표시
            document.getElementById('quoteModal').style.display = 'block';
        }
        
        // sendQuote 함수를 addToQuoteCart로 연결
        function sendQuote() {
            addToQuoteCart();
        }
        
        // 카트 카운트 업데이트
        function updateCartCount() {
            const quoteCart = JSON.parse(sessionStorage.getItem('quoteCart') || '[]');
            const cartCount = quoteCart.length; // 아이템(건) 단위로 카운트
            
            // 상단 카트 아이콘의 카운트 업데이트 (head.php에 있다면)
            const cartCountElement = document.querySelector('.cart-count');
            if (cartCountElement) {
                cartCountElement.textContent = cartCount;
                cartCountElement.style.display = cartCount > 0 ? 'block' : 'none';
            }
        }
        
        // 모달 닫기
        function closeQuoteModal() {
            document.getElementById('quoteModal').style.display = 'none';
        }
        
        // 제품견적서 페이지로 이동
        function goToQuote() {
            // 마이페이지의 제품견적서로 이동
            window.location.href = 'my_quote_cart.php';
        }
        
        // 모달 외부 클릭 시 닫기
        document.getElementById('quoteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeQuoteModal();
            }
        });
        
        // 문의 모달 표시
        function showInquiryModal() {
            document.getElementById('inquiryModal').style.display = 'block';
        }
        
        // 문의 모달 닫기
        function closeInquiryModal() {
            document.getElementById('inquiryModal').style.display = 'none';
        }
        
        // 페이지 로드 시 카트 카운트 업데이트
        window.addEventListener('DOMContentLoaded', function() {
            updateCartCount();
            
            // 문의 모달 외부 클릭 시 닫기
            document.getElementById('inquiryModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    closeInquiryModal();
                }
            });
        });
    </script>

<!-- 문의요망 모달 -->
<div id="inquiryModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); max-width: 400px; width: 90%;">
        <h3 style="margin-bottom: 20px; color: #333; font-size: 20px;">견적 문의 요망</h3>
        <p style="margin-bottom: 24px; color: #666; line-height: 1.6;">
            선택하신 제품의 상세 견적은 전화 문의 부탁드립니다.<br><br>
            <strong style="color: #1428A0; font-size: 18px;">문의 전화: 032-564-1616</strong>
        </p>
        <div style="display: flex; gap: 12px; justify-content: center;">
            <button onclick="closeInquiryModal()" style="padding: 10px 24px; background: #f0f0f0; border: none; border-radius: 6px; cursor: pointer; font-size: 16px;">닫기</button>
            <a href="tel:032-564-1616" style="padding: 10px 24px; background: #1428A0; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; text-decoration: none; display: inline-block;">전화하기</a>
        </div>
    </div>
</div>

<!-- 견적문의 모달 -->
<div id="quoteModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); max-width: 400px; width: 90%;">
        <h3 style="margin-bottom: 20px; color: #333; font-size: 20px;">견적문의 추가</h3>
        <p style="margin-bottom: 24px; color: #666; line-height: 1.6;">
            <strong id="modalProductName" style="color: #1428A0;"></strong> 제품이 견적서에 담겼습니다.<br>
            제품견적서 페이지로 이동하시겠습니까?
        </p>
        <div style="display: flex; gap: 12px; justify-content: flex-end;">
            <button onclick="closeQuoteModal()" style="padding: 10px 24px; background: #f0f0f0; border: none; border-radius: 6px; cursor: pointer; font-size: 16px;">아니오</button>
            <button onclick="goToQuote()" style="padding: 10px 24px; background: #1428A0; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 16px;">예</button>
        </div>
    </div>
</div>

</body>
</html>