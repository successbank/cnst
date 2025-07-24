<?php
session_start();
require_once '../db.php';
require_once 'admin_check.php';

// 현재 탭
$current_tab = $_GET['tab'] ?? 'products';

// 페이지네이션 설정
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// 카테고리 목록 가져오기
$stmt = $pdo->query("SELECT * FROM product_categories ORDER BY display_order");
$categories = $stmt->fetchAll();

$pageTitle = '제품 통합 관리';

// 추가 스타일 정의
$additionalStyles = '
/* 모바일 메뉴 버튼 숨기기 */
.mobile-menu-toggle {
    display: none !important;
}

.data-table table {
    table-layout: fixed;
}

.data-table th {
    text-align: center;
    background: #f8f9fa;
    font-weight: 600;
    padding: 12px;
    border-bottom: 2px solid #dee2e6;
}

.data-table td {
    text-align: center;
    padding: 12px;
    border-bottom: 1px solid #e9ecef;
    vertical-align: middle;
    word-break: break-word;
}

.data-table td:nth-child(2) {
    text-align: left;
}

.page-header {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 30px;
}

.page-header h1 {
    margin: 0 0 10px 0;
    color: #2c3e50;
    font-size: 28px;
}

.page-header p {
    margin: 0;
    color: #6c757d;
    font-size: 16px;
}

.content-section {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 30px;
}

.content-section h2 {
    margin: 0 0 20px 0;
    color: #2c3e50;
    font-size: 24px;
    padding-bottom: 15px;
    border-bottom: 2px solid #e9ecef;
}

.form-inline {
    display: flex;
    gap: 15px;
    align-items: center;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

.form-group {
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-group label {
    font-weight: 500;
    color: #495057;
    font-size: 14px;
}

.price-input {
    padding: 8px 12px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 14px;
    transition: all 0.3s ease;
}

.price-input:focus {
    outline: none;
    border-color: #80bdff;
    box-shadow: 0 0 0 3px rgba(0,123,255,.25);
}

.btn-secondary {
    background: #6c757d;
    color: white;
    border: none;
    padding: 6px 16px;
    border-radius: 4px;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-secondary:hover {
    background: #5a6268;
}

.active-badge {
    display: inline-block;
    padding: 3px 8px;
    background: #28a745;
    color: white;
    border-radius: 3px;
    font-size: 12px;
    margin-left: 5px;
}

.inactive-badge {
    display: inline-block;
    padding: 3px 8px;
    background: #6c757d;
    color: white;
    border-radius: 3px;
    font-size: 12px;
    margin-left: 5px;
}

.filter-form input[type="text"] {
    flex: 1;
    min-width: 200px;
}

.tab-navigation {
    display: flex;
    gap: 0;
    background: white;
    border-radius: 8px 8px 0 0;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: -1px;
}

.tab-item {
    padding: 15px 30px;
    background: #f8f9fa;
    color: #666;
    text-decoration: none;
    font-weight: 500;
    border-right: 1px solid #dee2e6;
    transition: all 0.3s ease;
    position: relative;
}

.tab-item:hover {
    background: #e9ecef;
    color: #333;
}

.tab-item.active {
    background: white;
    color: #007bff;
    border-bottom: 3px solid #007bff;
}

.tab-content {
    background: white;
    padding: 30px;
    border-radius: 0 0 8px 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.tab-actions {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    margin-bottom: 20px;
}

/* 모든 링크와 버튼 항상 표시 */
.btn, .btn-sm, .tab-actions a, table a {
    display: inline-block !important;
    visibility: visible !important;
    opacity: 1 !important;
    transition: none !important;
}

/* 모바일 반응형 스타일 */
@media (max-width: 768px) {
    .tab-navigation {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .tab-item {
        padding: 12px 20px;
        font-size: 14px;
    }
    
    .tab-content {
        padding: 20px;
    }
    
    .tab-actions {
        flex-direction: column;
        gap: 10px;
        align-items: flex-start;
    }
    
    .tab-actions h3 {
        margin-bottom: 10px;
    }
    
    /* 테이블 반응형 */
    table {
        font-size: 13px;
    }
    
    th, td {
        padding: 8px 5px !important;
    }
    
    /* 폼 반응형 */
    form > div {
        flex-direction: column !important;
    }
    
    select, button {
        width: 100% !important;
    }
}
';

require_once 'admin_head.php';
?>

<?php
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

<div class="page-header">
    <h1>제품 통합 관리</h1>
    <p>제품 및 카테고리를 통합 관리할 수 있습니다.</p>
</div>

<!-- 탭 네비게이션 -->
<div class="tab-navigation">
</div>

<div class="tab-content">
    <?php
    // 메시지 처리
    if (isset($_GET['message'])) {
        $messages = [
            'generated' => (isset($_GET['count']) ? $_GET['count'] : '0') . '개의 제품이 자동 생성되었습니다.',
            'bulk_deleted' => '선택한 제품이 삭제되었습니다.',
            'bulk_activated' => '선택한 제품이 활성화되었습니다.',
            'bulk_deactivated' => '선택한 제품이 비활성화되었습니다.',
            'weight_deleted' => '단중표 데이터가 삭제되었습니다.',
            'error' => '처리 중 오류가 발생했습니다.'
        ];
        
        if (isset($messages[$_GET['message']])) {
            echo '<div style="padding: 12px 20px; background: #d4edda; color: #155724; border-radius: 6px; margin-bottom: 20px;">';
            echo $messages[$_GET['message']];
            echo '</div>';
        }
    }
    
    // 업로드 성공 메시지 처리
    if (isset($_SESSION['import_message'])) {
        echo '<div style="padding: 12px 20px; background: #d4edda; color: #155724; border-radius: 6px; margin-bottom: 20px;">';
        echo htmlspecialchars($_SESSION['import_message']);
        echo '</div>';
        unset($_SESSION['import_message']);
    }
    ?>
    
    <?php if ($current_tab === 'products'): ?>
        <!-- 제품 목록 탭 -->
        <div class="tab-actions">
            <a href="admin_products_edit.php" class="btn btn-primary">새 제품 추가</a>
        </div>
        
        <!-- 기존 제품 목록 코드 -->
        <?php include 'includes/products_list_improved.php'; ?>
        
    <?php endif; ?>
</div>

<?php require_once 'admin_tail.php'; ?>