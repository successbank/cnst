<?php
require_once '../member_check.php';
require_once '../db.php';

// 로그인 체크
if (!isset($_SESSION['member_id'])) {
    die('로그인이 필요합니다.');
}

// 견적 아이템 받기
$items = isset($_POST['items']) ? json_decode($_POST['items'], true) : [];

if (empty($items)) {
    die('견적 제품이 없습니다.');
}

// 사용자 정보 가져오기
$stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
$stmt->execute([$_SESSION['member_id']]);
$member = $stmt->fetch();
    
// HTML 내용 생성
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>충남스틸 견적서</title>
    <style>
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
        body {
            font-family: "Malgun Gothic", "맑은 고딕", sans-serif;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
        }
        .quote-container {
            max-width: 900px;
            margin: 0 auto;
            border: 1px solid #000;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            position: relative;
        }
        .header h1 {
            font-size: 36px;
            margin: 0;
            letter-spacing: 10px;
        }
        .header-info {
            position: absolute;
            top: 0;
            right: 0;
            text-align: right;
            font-size: 11px;
            line-height: 1.3;
        }
        .quote-info {
            margin-bottom: 20px;
            border: 1px solid #000;
            display: table;
            width: 100%;
        }
        .quote-info-row {
            display: table-row;
        }
        .quote-info-cell {
            display: table-cell;
            border-right: 1px solid #000;
            padding: 8px 12px;
            vertical-align: middle;
        }
        .quote-info-cell:last-child {
            border-right: none;
        }
        .quote-info-label {
            font-weight: bold;
            background-color: #f0f0f0;
            width: 100px;
        }
        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .main-table th, .main-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
            font-size: 13px;
        }
        .main-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .main-table td {
            height: 25px;
        }
        .summary-section {
            margin-top: 20px;
            border: 1px solid #000;
            padding: 15px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .company-footer {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        .footer-left {
            font-size: 12px;
            line-height: 1.5;
        }
        .footer-right {
            text-align: center;
        }
        .qr-placeholder {
            width: 100px;
            height: 100px;
            border: 1px solid #ccc;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f0f0f0;
        }
        .btn-download {
            background: #1428A0;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin: 20px;
        }
        .btn-download:hover {
            background: #0F1F7A;
        }
        .notice-text {
            color: red;
            font-size: 12px;
            margin: 10px 0;
        }
        .empty-row {
            height: 30px;
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button class="btn-download" onclick="window.print()">PDF로 인쇄/저장</button>
        <p style="color: #666; font-size: 14px;">브라우저의 인쇄 기능을 사용하여 PDF로 저장할 수 있습니다.</p>
    </div>
    
    <div class="quote-container">
        <div class="header">
            <div class="header-info">
                NO. _____________<br>
                <div style="margin-top: 20px; line-height: 1.4;">
                    취 급 품 목<br>
                    철근, 형강(H빔, 앵글, 찬넬, 환봉, 평철, C형강, 데크플레이트,<br>
                    레일,<br>
                    와이어메쉬 등), 철판(일반, 냉연, 아연도금, 무늬, 망철판 등),<br>
                    파이프<br>
                    (강관, 각관 등), 스테인레스, 도목자재, 철스크랩(고철), 각종<br>
                    철강류
                </div>
            </div>
            <h1>견 적 서</h1>
        </div>
        
        <div class="quote-info">
            <div class="quote-info-row">
                <div class="quote-info-cell quote-info-label">검 적 일 시</div>
                <div class="quote-info-cell" colspan="3">' . date('Y년 m월 d일') . '</div>
                <div class="quote-info-cell quote-info-label">충 남 스 틸 (주)</div>
            </div>
            <div class="quote-info-row">
                <div class="quote-info-cell quote-info-label">고 객 명</div>
                <div class="quote-info-cell">' . htmlspecialchars($member['company'] ?? '') . '</div>
                <div class="quote-info-cell quote-info-label">대 표</div>
                <div class="quote-info-cell">김영건</div>
                <div class="quote-info-cell">인천시 서구 봉수대로 1626 (금곡동) [금곡동 377-25]</div>
            </div>
            <div class="quote-info-row">
                <div class="quote-info-cell quote-info-label">도 착 지 역</div>
                <div class="quote-info-cell">' . htmlspecialchars($member['address'] ?? '') . '</div>
                <div class="quote-info-cell quote-info-label">전 화 번 호</div>
                <div class="quote-info-cell">(032) 564-1616</div>
                <div class="quote-info-cell quote-info-label">팩 스</div>
                <div class="quote-info-cell">(032) 564-0090</div>
            </div>
            <div class="quote-info-row">
                <div class="quote-info-cell quote-info-label">견 적 담 당</div>
                <div class="quote-info-cell" colspan="5">
                    <span class="notice-text">원정 ( 반주시 정확한 견적은 본사 영업부에서 확인하시 바랍니다 )</span>
                </div>
            </div>
        </div>
        
        <p style="text-align: center; margin: 10px 0;">( <span style="color: #666;">_________</span> ) 부기세포함</p>
        
        <table class="main-table">
            <thead>
                <tr>
                    <th width="5%">순번</th>
                    <th width="15%">품명</th>
                    <th width="15%">규격</th>
                    <th width="10%">길이(M)</th>
                    <th width="10%">BD / PCS</th>
                    <th width="10%">중량</th>
                    <th width="10%">단가</th>
                    <th width="10%">공급가액</th>
                    <th width="15%">비고</th>
                </tr>
            </thead>
            <tbody>';

    // 제품 목록 출력
    foreach ($items as $index => $item) {
        $html .= '
                <tr>
                    <td>' . ($index + 1) . '</td>
                    <td>' . htmlspecialchars($item['name']) . '</td>
                    <td>' . htmlspecialchars($item['specifications'] ?? '') . '</td>
                    <td>/</td>
                    <td>/</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>';
    }
    
    // 빈 행 추가 (총 20개 행이 되도록)
    $emptyRows = 20 - count($items);
    for ($i = 0; $i < $emptyRows; $i++) {
        $html .= '
                <tr class="empty-row">
                    <td>' . (count($items) + $i + 1) . '</td>
                    <td></td>
                    <td></td>
                    <td>/</td>
                    <td>/</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>';
    }
    
    $html .= '
            </tbody>
        </table>
        
        <div class="summary-section">
            <div class="summary-row">
                <div style="display: flex; gap: 30px;">
                    <div><strong>PCS합계</strong>: EA</div>
                    <div><strong>수량합계</strong>: KG</div>
                </div>
                <div><strong>세액 ( 부가가치세 )</strong>: </div>
            </div>
            <div class="summary-row">
                <div><strong>공급가총액</strong>: </div>
                <div style="color: red; font-weight: bold;">※ 견적담당자</div>
            </div>
            <div style="margin-top: 15px;">
                <strong>※ 특이사항</strong>: 
            </div>
        </div>
        
        <div class="company-footer">
            <div class="footer-left">
                <strong>1.결제조건 :</strong><br>
                <strong>2.운임조건 :</strong><br>
                <strong>3.납품기일 :</strong>
            </div>
            <div class="footer-right">
                <div class="qr-placeholder">
                    <span style="color: #999;">충남스틸(주)</span>
                </div>
                <p style="margin: 5px 0; font-size: 12px;">충남스틸(주)</p>
            </div>
        </div>
        
        <div style="margin-top: 20px; font-size: 11px; color: red;">
            <p>※ 상기 견처 금액은 해고 없이 변동될 수도 있습니다.</p>
            <p>※ 견적서 미확인, 담은 변신 및 발주 오류로 인한 반품 불가.</p>
            <p>※ 제강사 제고는 실시간 변경될 수 있음.</p>
        </div>
        
        <div style="margin-top: 30px; text-align: center; font-size: 12px;">
            <strong>E-MAIL : cn1616@naver.com</strong>
        </div>
    </div>
</body>
</html>';
    
// HTML 출력
header('Content-Type: text/html; charset=UTF-8');
echo $html;
?>