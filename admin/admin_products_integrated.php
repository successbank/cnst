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

include 'admin_head.php';
?>

<style>
.page-header {
    margin-bottom: 30px;
}

.page-header h2 {
    font-size: 28px;
    font-weight: 700;
    color: #333;
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

.btn {
    padding: 10px 20px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    display: inline-block !important;
    visibility: visible !important;
    opacity: 1 !important;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-primary:hover {
    background: #0056b3;
}

.btn-success {
    background: #28a745;
    color: white;
}

.btn-success:hover {
    background: #218838;
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
    
    .btn {
        /* Keep buttons visible on mobile */
        display: inline-block !important;
        visibility: visible !important;
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
</style>

<div class="page-header">
    <h2>제품 통합 관리</h2>
</div>

<!-- 탭 네비게이션 -->
<div class="tab-navigation">
    <a href="?tab=products" class="tab-item <?php echo $current_tab === 'products' ? 'active' : ''; ?>">
        제품 목록
    </a>
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

<?php include 'admin_tail.php'; ?>