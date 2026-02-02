<?php
// 세션 및 데이터베이스 연결 먼저 처리
session_start();
require_once '../db.php';
require_once 'admin_check.php';
require_once '../includes/MemberAdminLogService.php';

// CSRF 토큰 생성 (없으면)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// 액션 처리 (헤더 출력 전에 처리)
$action = $_GET['action'] ?? 'list';
$tab = $_GET['tab'] ?? 'list'; // 탭 파라미터 (list: 회원 목록, permissions: 권한설정)

// 로그 서비스 초기화 (상세 페이지용)
$memberLogService = new MemberAdminLogService($pdo);

$pageTitle = '회원 관리';

// 추가 스타일 정의
$additionalStyles = '
.search-bar {
    display: flex;
    gap: 12px;
    margin-bottom: 24px;
}

.search-input {
    flex: 1;
    padding: 12px 16px;
    border: 2px solid #E5E5E7;
    border-radius: 8px;
    font-size: 14px;
}

.search-input:focus {
    outline: none;
    border-color: #1A237E;
}

.filter-select {
    padding: 12px 16px;
    border: 2px solid #E5E5E7;
    border-radius: 8px;
    font-size: 14px;
    background: white;
    cursor: pointer;
}

.search-btn {
    padding: 12px 24px;
    background: #1A237E;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.search-btn:hover {
    background: #283593;
}

.members-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

.members-table th,
.members-table td {
    padding: 12px 8px;
    text-align: left;
    border-bottom: 1px solid #E5E5E7;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.members-table th {
    font-weight: 600;
    color: #666;
    font-size: 14px;
    background: #F8F9FA;
}

.members-table tr:hover {
    background: #F5F5F7;
}

/* 테이블 컬럼 너비 비율 */
.members-table th:nth-child(1),
.members-table td:nth-child(1) { width: 8%; text-align: center; } /* 번호 */
.members-table th:nth-child(2),
.members-table td:nth-child(2) { width: 15%; } /* 아이디 */
.members-table th:nth-child(3),
.members-table td:nth-child(3) { width: 12%; } /* 이름 */
.members-table th:nth-child(4),
.members-table td:nth-child(4) { width: 25%; } /* 회사명 */
.members-table th:nth-child(5),
.members-table td:nth-child(5) { width: 8%; text-align: center; } /* 등급 */
.members-table th:nth-child(6),
.members-table td:nth-child(6) { width: 12%; text-align: center; } /* 가입일 */
.members-table th:nth-child(7),
.members-table td:nth-child(7) { width: 8%; text-align: center; } /* 상태 */
.members-table th:nth-child(8),
.members-table td:nth-child(8) { width: 12%; text-align: center; } /* 관리 */

/* 테이블 wrapper 스타일 추가 */
.table-wrapper {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    margin: 0 -32px;
    padding: 0 32px;
}

/* 회사명 등 긴 텍스트 처리 */
.text-ellipsis {
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
}

/* 액션 링크 스타일 */
.action-links {
    display: flex;
    gap: 3px;
    justify-content: center;
    flex-wrap: nowrap;
}

.action-links a {
    padding: 3px 6px;
    border-radius: 3px;
    font-size: 11px;
    text-decoration: none;
    transition: all 0.3s ease;
    white-space: nowrap;
}

.btn-view {
    background: #E8EAF6;
    color: #3F51B5;
}

.btn-view:hover {
    background: #C5CAE9;
}

.btn-toggle {
    background: #FFF3E0;
    color: #FB8C00;
}

.btn-toggle:hover {
    background: #FFE0B2;
}

.btn-delete {
    background: #FFEBEE;
    color: #E53935;
}

.btn-delete:hover {
    background: #FFCDD2;
}

/* 버튼 태그용 스타일 */
button.btn-toggle {
    background: #FFF3E0;
    color: #FB8C00;
    border: none;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
}

button.btn-toggle:hover {
    background: #FFE0B2;
}

button.btn-delete {
    background: #FFEBEE;
    color: #E53935;
    border: none;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
}

button.btn-delete:hover {
    background: #FFCDD2;
}

button.btn-view {
    background: #E3F2FD;
    color: #1976D2;
    border: none;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
}

button.btn-view:hover {
    background: #BBDEFB;
}

.status-active {
    background: #E8F5E9;
    color: #2E7D32;
}

.status-inactive {
    background: #FFEBEE;
    color: #C62828;
}

/* 회원등급 배지 스타일 */
.grade-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.grade-normal {
    background: #F5F5F5;
    color: #666;
}

.grade-silver {
    background: #E0E0E0;
    color: #424242;
}

.grade-gold {
    background: #FFF8E1;
    color: #F57C00;
    border: 1px solid #FFE082;
}

.grade-vip {
    background: #E8EAF6;
    color: #3F51B5;
    border: 1px solid #C5CAE9;
}


.member-detail {
    background: #F5F5F7;
    padding: 24px;
    border-radius: 8px;
    margin-bottom: 24px;
}

.detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
}

.detail-item {
    background: white;
    padding: 16px;
    border-radius: 8px;
}

.detail-label {
    font-size: 12px;
    color: #666;
    margin-bottom: 4px;
}

.detail-value {
    font-size: 16px;
    font-weight: 600;
    color: #333;
}

.back-btn {
    display: inline-block;
    padding: 10px 20px;
    background: #666;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-size: 14px;
    margin-bottom: 20px;
}

.back-btn:hover {
    background: #555;
}

.pagination {
    display: flex;
    gap: 4px;
    align-items: center;
}

.page-link {
    padding: 8px 12px;
    border: 1px solid #E5E5E7;
    text-decoration: none;
    color: #333;
    font-size: 14px;
    border-radius: 4px;
    transition: all 0.3s ease;
    min-width: 36px;
    text-align: center;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.page-link:hover {
    background: #F5F5F5;
    border-color: #1A237E;
    color: #1A237E;
}

.page-link.active {
    background: #1A237E;
    color: white;
    border-color: #1A237E;
    font-weight: 600;
}

.page-dots {
    color: #999;
    padding: 0 8px;
    font-size: 14px;
}

.pagination-wrapper {
    border-top: 1px solid #E5E5E7;
    padding-top: 20px;
}

.show-entries select {
    font-size: 14px;
    height: 36px;
}

.page-info {
    min-width: 200px;
    text-align: right;
}

.memo-icon {
    display: inline-block;
    width: 20px;
    height: 20px;
    background: #FFA726;
    border-radius: 50%;
    color: white;
    text-align: center;
    line-height: 20px;
    font-size: 12px;
    margin-left: 4px;
    cursor: help;
    position: relative;
}

.memo-icon:hover::after {
    content: "메모 있음";
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    background: #333;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    white-space: nowrap;
    margin-bottom: 4px;
}

.memo-textarea {
    width: 100%;
    min-height: 120px;
    padding: 12px;
    border: 2px solid #E5E5E7;
    border-radius: 8px;
    font-size: 14px;
    resize: vertical;
    font-family: inherit;
}

.memo-textarea:focus {
    outline: none;
    border-color: #1A237E;
}

.memo-list {
    background: #F5F5F7;
    border-radius: 8px;
    padding: 16px;
    margin-top: 8px;
}

.memo-item {
    background: white;
    padding: 12px 16px;
    border-radius: 6px;
    margin-bottom: 8px;
    border: 1px solid #E5E5E7;
    position: relative;
}

.memo-item:last-child {
    margin-bottom: 0;
}

.memo-date {
    font-size: 12px;
    color: #666;
    margin-bottom: 4px;
}

.memo-content {
    font-size: 14px;
    color: #333;
    line-height: 1.5;
    white-space: pre-wrap;
}

.memo-actions {
    position: absolute;
    top: 12px;
    right: 12px;
    display: flex;
    gap: 8px;
}

.memo-edit-btn, .memo-delete-btn {
    padding: 4px 8px;
    border: none;
    border-radius: 4px;
    font-size: 12px;
    cursor: pointer;
    transition: background 0.3s ease;
}

.memo-edit-btn {
    background: #2196F3;
    color: white;
}

.memo-edit-btn:hover {
    background: #1976D2;
}

.memo-delete-btn {
    background: #F44336;
    color: white;
}

.memo-delete-btn:hover {
    background: #D32F2F;
}

.memo-add-section {
    margin-top: 16px;
    padding: 16px;
    background: #E3F2FD;
    border-radius: 8px;
    display: none;
}

.memo-add-section.active {
    display: block;
}

.add-memo-btn {
    padding: 8px 16px;
    background: #4CAF50;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.3s ease;
}

.add-memo-btn:hover {
    background: #45A049;
}

.memo-add-btn {
    padding: 3px 6px;
    background: #2196F3;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 11px;
    cursor: pointer;
    transition: background 0.3s ease;
    display: inline-block;
    vertical-align: middle;
}

.memo-add-btn:hover {
    background: #1976D2;
}

.memo-view-btn {
    padding: 3px 6px;
    background: #FF9800;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 11px;
    cursor: pointer;
    margin-left: 6px;
    transition: background 0.3s ease;
    display: inline-block;
    vertical-align: middle;
}

.memo-view-btn:hover {
    background: #F57C00;
}

.member-name-cell {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: nowrap;
    min-width: 0;
}

.member-name-cell > span {
    white-space: nowrap;
}

.search-highlight {
    background-color: #FFEB3B;
    padding: 1px 3px;
    border-radius: 3px;
    font-weight: 600;
}

/* 모달 스타일 */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 0;
    border-radius: 12px;
    width: 90%;
    max-width: 600px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.modal-header {
    padding: 20px 24px;
    background: #1A237E;
    color: white;
    border-radius: 12px 12px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    margin: 0;
    font-size: 20px;
}

.modal-close {
    color: white;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    line-height: 20px;
    transition: opacity 0.3s ease;
}

.modal-close:hover {
    opacity: 0.8;
}

.modal-body {
    padding: 24px;
    max-height: 60vh;
    overflow-y: auto;
}

.modal-memo-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.modal-memo-item {
    background: #F5F5F7;
    padding: 16px;
    border-radius: 8px;
    border-left: 4px solid #1A237E;
}

.modal-memo-date {
    font-size: 12px;
    color: #666;
    margin-bottom: 8px;
}

.modal-memo-content {
    font-size: 14px;
    color: #333;
    line-height: 1.6;
    white-space: pre-wrap;
}

.modal-no-memo {
    text-align: center;
    color: #999;
    padding: 40px 0;
}

.modal-footer {
    padding: 20px 24px;
    background: #F5F5F7;
    border-top: 1px solid #E5E5E7;
    border-radius: 0 0 12px 12px;
}

.modal-add-memo-section {
    display: none;
}

.modal-add-memo-section.active {
    display: block;
}

.modal-memo-textarea {
    width: 100%;
    min-height: 100px;
    padding: 12px;
    border: 2px solid #E5E5E7;
    border-radius: 8px;
    font-size: 14px;
    resize: vertical;
    font-family: inherit;
    margin-bottom: 12px;
}

.modal-memo-textarea:focus {
    outline: none;
    border-color: #1A237E;
}

.modal-btn {
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.3s ease;
}

.modal-btn-primary {
    background: #4CAF50;
    color: white;
    margin-right: 8px;
}

.modal-btn-primary:hover {
    background: #45A049;
}

.modal-btn-secondary {
    background: #666;
    color: white;
}

.modal-btn-secondary:hover {
    background: #555;
}

.modal-add-btn {
    background: #2196F3;
    color: white;
}

.modal-add-btn:hover {
    background: #1976D2;
}

@media (max-width: 768px) {
    .pagination-wrapper {
        flex-direction: column;
        gap: 16px;
        align-items: stretch;
    }
    
    .show-entries {
        order: 1;
    }
    
    .page-info {
        order: 2;
        text-align: center;
        min-width: auto;
    }
    
    .pagination {
        order: 3;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .page-link {
        min-width: 32px;
        padding: 6px 10px;
        font-size: 13px;
    }
}

.content-box {
    background: white;
    padding: 32px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    overflow: hidden;
}

/* 탭 메뉴 스타일 */
.member-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 24px;
}

.member-tab {
    padding: 12px 24px;
    border: 2px solid #E5E5E7;
    border-radius: 8px;
    background: white;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    color: #666;
    text-decoration: none;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.member-tab:hover {
    border-color: #1A237E;
    color: #1A237E;
}

.member-tab.active {
    background: #1A237E;
    border-color: #1A237E;
    color: white;
}

.member-tab .badge {
    background: rgba(0,0,0,0.1);
    padding: 2px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.member-tab.active .badge {
    background: rgba(255,255,255,0.2);
}

/* 권한설정 탭 스타일 */
.permission-section {
    background: white;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 24px;
}

.permission-section-title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 20px;
    color: #333;
    display: flex;
    align-items: center;
    gap: 10px;
    padding-bottom: 12px;
    border-bottom: 2px solid #E5E5E7;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.stat-card {
    background: #F8F9FA;
    padding: 20px;
    border-radius: 10px;
    text-align: center;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.stat-card:hover {
    border-color: #1A237E;
    transform: translateY(-2px);
}

.stat-card .stat-grade {
    font-size: 14px;
    font-weight: 600;
    color: #666;
    margin-bottom: 8px;
}

.stat-card .stat-count {
    font-size: 28px;
    font-weight: 700;
    color: #1A237E;
}

.stat-card .stat-count small {
    font-size: 14px;
    color: #999;
    font-weight: 400;
}

/* 등급 설정 테이블 */
.grade-settings-table {
    width: 100%;
    border-collapse: collapse;
}

.grade-settings-table th,
.grade-settings-table td {
    padding: 12px;
    text-align: center;
    border-bottom: 1px solid #E5E5E7;
}

.grade-settings-table th {
    background: #F8F9FA;
    font-weight: 600;
    color: #666;
    font-size: 14px;
}

.grade-settings-table input[type="text"],
.grade-settings-table input[type="number"] {
    padding: 8px 12px;
    border: 2px solid #E5E5E7;
    border-radius: 6px;
    font-size: 14px;
    text-align: center;
    width: 80px;
}

.grade-settings-table input:focus {
    outline: none;
    border-color: #1A237E;
}

/* 권한 매트릭스 */
.permission-matrix {
    width: 100%;
    border-collapse: collapse;
    margin-top: 16px;
}

.permission-matrix th,
.permission-matrix td {
    padding: 12px;
    text-align: center;
    border-bottom: 1px solid #E5E5E7;
}

.permission-matrix th {
    background: #F8F9FA;
    font-weight: 600;
    color: #666;
    font-size: 14px;
}

.permission-matrix td:first-child {
    text-align: left;
    font-weight: 500;
}

.permission-matrix input[type="checkbox"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
}

/* 일괄 등급 변경 */
.bulk-change-form {
    display: flex;
    gap: 16px;
    align-items: center;
    flex-wrap: wrap;
}

.bulk-change-form select {
    padding: 10px 16px;
    border: 2px solid #E5E5E7;
    border-radius: 8px;
    font-size: 14px;
    background: white;
}

.bulk-change-form select:focus {
    outline: none;
    border-color: #1A237E;
}

/* 관리자 목록 */
.admin-list-table {
    width: 100%;
    border-collapse: collapse;
}

.admin-list-table th,
.admin-list-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #E5E5E7;
}

.admin-list-table th {
    background: #F8F9FA;
    font-weight: 600;
    color: #666;
    font-size: 14px;
}

.admin-toggle-btn {
    padding: 6px 12px;
    border: none;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.admin-toggle-btn.is-admin {
    background: #E8F5E9;
    color: #2E7D32;
}

.admin-toggle-btn.not-admin {
    background: #E3F2FD;
    color: #1976D2;
}

.admin-toggle-btn:hover {
    opacity: 0.8;
}

/* 버튼 스타일 */
.btn-primary {
    padding: 10px 20px;
    background: #1A237E;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background: #283593;
}

.btn-secondary {
    padding: 10px 20px;
    background: #E5E5E7;
    color: #333;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-secondary:hover {
    background: #D5D5D7;
}

.btn-success {
    padding: 10px 20px;
    background: #4CAF50;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-success:hover {
    background: #45A049;
}

/* 로딩 스피너 */
.loading-spinner {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 2px solid #E5E5E7;
    border-radius: 50%;
    border-top-color: #1A237E;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* 알림 메시지 */
.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 16px;
    font-size: 14px;
}

.alert-success {
    background: #E8F5E9;
    color: #2E7D32;
    border: 1px solid #C8E6C9;
}

.alert-error {
    background: #FFEBEE;
    color: #C62828;
    border: 1px solid #FFCDD2;
}
';

require_once 'admin_head.php';

// 검색 및 필터
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? 'all';
$grade_filter = $_GET['grade'] ?? 'all';

// 회원 상세 정보
$member_detail = null;
if($action === 'view' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
        $stmt->execute([$id]);
        $member_detail = $stmt->fetch();
        
        if(!$member_detail) {
            header('Location: admin_members.php');
            exit;
        }
    } catch(PDOException $e) {
        $error = "회원 정보를 불러올 수 없습니다.";
    }
}

// 회원 목록 가져오기
if($action === 'list') {
    try {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        // 유효한 limit 값만 허용
        if(!in_array($limit, [10, 20, 30, 50, 100])) {
            $limit = 20;
        }
        $offset = ($page - 1) * $limit;
        
        // 기본 쿼리
        $where_conditions = [];
        $params = [];
        
        if($search) {
            $where_conditions[] = "(user_id LIKE ? OR name LIKE ? OR email LIKE ? OR company LIKE ? OR memo LIKE ?)";
            $search_param = "%$search%";
            $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
        }
        
        if($filter === 'active') {
            $where_conditions[] = "is_active = 1";
        } elseif($filter === 'inactive') {
            $where_conditions[] = "is_active = 0";
        }

        // 등급 필터
        if($grade_filter !== 'all' && in_array($grade_filter, ['normal', 'silver', 'gold', 'vip'])) {
            $where_conditions[] = "member_grade = ?";
            $params[] = $grade_filter;
        }

        $where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        // 전체 개수
        $count_query = "SELECT COUNT(*) FROM members $where_clause";
        $stmt = $pdo->prepare($count_query);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();
        
        // 회원 목록
        $list_query = "SELECT * FROM members $where_clause ORDER BY id DESC LIMIT ? OFFSET ?";
        
        $stmt = $pdo->prepare($list_query);
        // 기존 파라미터 바인딩
        $paramIndex = 1;
        foreach($params as $param) {
            $stmt->bindValue($paramIndex++, $param);
        }
        // LIMIT과 OFFSET은 정수로 바인딩
        $stmt->bindValue($paramIndex++, $limit, PDO::PARAM_INT);
        $stmt->bindValue($paramIndex, $offset, PDO::PARAM_INT);
        $stmt->execute();
        $members = $stmt->fetchAll();
        
        $totalPages = ceil($total / $limit);
    } catch(PDOException $e) {
        $members = [];
        $totalPages = 0;
        $error = "데이터를 불러올 수 없습니다: " . $e->getMessage();
    }
}
?>

        <?php if(isset($_GET['msg'])): ?>
            <div class="msg success">
                <?php
                switch($_GET['msg']) {
                    case 'status_changed': echo "회원 상태가 변경되었습니다."; break;
                    case 'deleted': echo "회원이 삭제되었습니다."; break;
                    case 'password_changed': echo "비밀번호가 성공적으로 변경되었습니다."; break;
                    case 'info_updated': echo "회원 정보가 성공적으로 수정되었습니다."; break;
                }
                ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($error)): ?>
            <div class="msg error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if(isset($_GET['error'])): ?>
            <div class="msg error">
                <?php
                switch($_GET['error']) {
                    case 'password_too_short': echo "비밀번호는 4자 이상이어야 합니다."; break;
                    case 'password_mismatch': echo "비밀번호가 일치하지 않습니다."; break;
                    case 'update_failed': echo "정보 수정 중 오류가 발생했습니다."; break;
                    case 'invalid_member': echo "잘못된 회원 정보입니다."; break;
                    case 'invalid_email': echo "올바른 이메일 주소를 입력해주세요."; break;
                    case 'email_exists': echo "이미 사용중인 이메일입니다."; break;
                }
                ?>
            </div>
        <?php endif; ?>
        
        <?php if($action === 'view' && $member_detail): ?>
            <a href="admin_members.php" class="back-btn">← 목록으로</a>
            
            <div class="page-header">
                <h1>회원 상세 정보</h1>
                <p><?php echo htmlspecialchars($member_detail['name']); ?>님의 상세 정보입니다.</p>
            </div>
            
            <div class="content-box">
                <!-- 회원 정보 수정 폼 -->
                <div class="member-detail">
                    <h3 style="font-size: 18px; margin-bottom: 16px;">회원 정보</h3>
                    <form method="POST" action="admin_members_action.php">
                        <input type="hidden" name="action" value="update_member">
                        <input type="hidden" name="member_id" value="<?php echo $member_detail['id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <div class="detail-grid">
                            <div class="detail-item">
                                <div class="detail-label">아이디</div>
                                <div class="detail-value"><?php echo htmlspecialchars($member_detail['user_id']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">이름</div>
                                <div class="detail-value"><?php echo htmlspecialchars($member_detail['name']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">이메일</div>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($member_detail['email']); ?>" 
                                       style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">회사명</div>
                                <input type="text" name="company" value="<?php echo htmlspecialchars($member_detail['company'] ?? ''); ?>" 
                                       style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">홈페이지</div>
                                <input type="url" name="homepage" placeholder="https://example.com"
                                       value="<?php echo htmlspecialchars($member_detail['homepage'] ?? ''); ?>" 
                                       style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            </div>
                            <div class="detail-item" style="grid-column: 1 / -1;">
                                <div class="detail-label">휴대폰</div>
                                <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                                    <?php
                                    $phone_parts = [];
                                    $phone = $member_detail['phone'] ?? '';
                                    if ($phone) {
                                        $phone_parts = explode('-', $phone);
                                    }
                                    ?>
                                    <select name="phone_prefix" style="width: 80px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                                        <option value="010" <?php echo (!isset($phone_parts[0]) || $phone_parts[0] == '010') ? 'selected' : ''; ?>>010</option>
                                        <option value="011" <?php echo (isset($phone_parts[0]) && $phone_parts[0] == '011') ? 'selected' : ''; ?>>011</option>
                                        <option value="016" <?php echo (isset($phone_parts[0]) && $phone_parts[0] == '016') ? 'selected' : ''; ?>>016</option>
                                        <option value="017" <?php echo (isset($phone_parts[0]) && $phone_parts[0] == '017') ? 'selected' : ''; ?>>017</option>
                                        <option value="018" <?php echo (isset($phone_parts[0]) && $phone_parts[0] == '018') ? 'selected' : ''; ?>>018</option>
                                        <option value="019" <?php echo (isset($phone_parts[0]) && $phone_parts[0] == '019') ? 'selected' : ''; ?>>019</option>
                                    </select>
                                    <span style="padding: 0 4px;">-</span>
                                    <input type="text" name="phone_middle" placeholder="0000" maxlength="4" 
                                           style="width: 70px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;"
                                           value="<?php echo htmlspecialchars($phone_parts[1] ?? ''); ?>">
                                    <span style="padding: 0 4px;">-</span>
                                    <input type="text" name="phone_last" placeholder="0000" maxlength="4" 
                                           style="width: 70px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;"
                                           value="<?php echo htmlspecialchars($phone_parts[2] ?? ''); ?>">
                                </div>
                                <input type="hidden" name="phone" value="">
                            </div>
                            <div class="detail-item" style="grid-column: 1 / -1;">
                                <div class="detail-label">일반전화번호</div>
                                <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                                    <?php
                                    $landline_parts = [];
                                    $landline = $member_detail['landline'] ?? '';
                                    if ($landline) {
                                        $landline_parts = explode('-', $landline);
                                    }
                                    $known_areas = ['02', '031', '032', '033', '041', '042', '043', '044', '050', '051', '052', '053', '054', '055', '061', '062', '063', '064', '070'];
                                    $is_other = isset($landline_parts[0]) && !in_array($landline_parts[0], $known_areas);
                                    ?>
                                    <select name="landline_area" style="width: 100px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                                        <option value="">지역번호</option>
                                        <option value="02" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '02') ? 'selected' : ''; ?>>02 (서울)</option>
                                        <option value="031" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '031') ? 'selected' : ''; ?>>031 (경기)</option>
                                        <option value="032" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '032') ? 'selected' : ''; ?>>032 (인천)</option>
                                        <option value="033" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '033') ? 'selected' : ''; ?>>033 (강원)</option>
                                        <option value="041" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '041') ? 'selected' : ''; ?>>041 (충남)</option>
                                        <option value="042" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '042') ? 'selected' : ''; ?>>042 (대전)</option>
                                        <option value="043" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '043') ? 'selected' : ''; ?>>043 (충북)</option>
                                        <option value="044" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '044') ? 'selected' : ''; ?>>044 (세종)</option>
                                        <option value="050" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '050') ? 'selected' : ''; ?>>050 (평신)</option>
                                        <option value="051" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '051') ? 'selected' : ''; ?>>051 (부산)</option>
                                        <option value="052" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '052') ? 'selected' : ''; ?>>052 (울산)</option>
                                        <option value="053" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '053') ? 'selected' : ''; ?>>053 (대구)</option>
                                        <option value="054" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '054') ? 'selected' : ''; ?>>054 (경북)</option>
                                        <option value="055" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '055') ? 'selected' : ''; ?>>055 (경남)</option>
                                        <option value="061" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '061') ? 'selected' : ''; ?>>061 (전남)</option>
                                        <option value="062" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '062') ? 'selected' : ''; ?>>062 (광주)</option>
                                        <option value="063" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '063') ? 'selected' : ''; ?>>063 (전북)</option>
                                        <option value="064" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '064') ? 'selected' : ''; ?>>064 (제주)</option>
                                        <option value="070" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '070') ? 'selected' : ''; ?>>070 (인터넷)</option>
                                        <option value="other" <?php echo $is_other ? 'selected' : ''; ?>>기타</option>
                                    </select>
                                    <input type="text" name="landline_area_other" placeholder="0000" maxlength="4" 
                                           style="width: 60px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; display: <?php echo $is_other ? 'inline-block' : 'none'; ?>;" 
                                           value="<?php echo $is_other ? htmlspecialchars($landline_parts[0]) : ''; ?>">
                                    <span style="padding: 0 4px;">-</span>
                                    <input type="text" name="landline_middle" placeholder="0000" maxlength="4" 
                                           style="width: 65px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;"
                                           value="<?php echo htmlspecialchars($landline_parts[1] ?? ''); ?>">
                                    <span style="padding: 0 4px;">-</span>
                                    <input type="text" name="landline_last" placeholder="0000" maxlength="4" 
                                           style="width: 65px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;"
                                           value="<?php echo htmlspecialchars($landline_parts[2] ?? ''); ?>">
                                </div>
                                <input type="hidden" name="landline" value="">
                            </div>
                            <div class="detail-item" style="grid-column: 1 / -1;">
                                <div class="detail-label">주소</div>
                                <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap; margin-bottom: 8px;">
                                    <input type="text" name="zipcode" id="zipcode" placeholder="우편번호" 
                                           value="<?php echo htmlspecialchars($member_detail['zipcode'] ?? ''); ?>" 
                                           style="width: 120px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" readonly>
                                    <button type="button" onclick="findZipcode()" 
                                            style="padding: 8px 16px; background: #666; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px;">
                                        우편번호 찾기
                                    </button>
                                </div>
                                <input type="text" name="address" id="address" placeholder="기본주소" 
                                       value="<?php echo htmlspecialchars($member_detail['address'] ?? ''); ?>" 
                                       style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 8px;" readonly>
                                <input type="text" name="address_detail" placeholder="상세주소" 
                                       value="<?php echo htmlspecialchars($member_detail['address_detail'] ?? ''); ?>" 
                                       style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">가입일</div>
                                <div class="detail-value"><?php echo date('Y-m-d H:i', strtotime($member_detail['created_at'])); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">상태</div>
                                <div class="detail-value">
                                    <label style="display: inline-flex; align-items: center; cursor: pointer;">
                                        <input type="checkbox" name="is_active" value="1" 
                                               <?php echo $member_detail['is_active'] ? 'checked' : ''; ?>
                                               style="margin-right: 8px; width: 18px; height: 18px; cursor: pointer;">
                                        <span id="status-text" style="font-weight: 600; color: <?php echo $member_detail['is_active'] ? '#2E7D32' : '#C62828'; ?>">
                                            <?php echo $member_detail['is_active'] ? '활성' : '비활성'; ?>
                                        </span>
                                    </label>
                                </div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">관리자 여부</div>
                                <div class="detail-value"><?php echo $member_detail['is_admin'] ? '관리자' : '일반회원'; ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">회원등급</div>
                                <div class="detail-value">
                                    <select name="member_grade" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                                        <option value="normal" <?php echo ($member_detail['member_grade'] ?? 'normal') === 'normal' ? 'selected' : ''; ?>>일반 (할인 0%)</option>
                                        <option value="silver" <?php echo ($member_detail['member_grade'] ?? '') === 'silver' ? 'selected' : ''; ?>>실버 (할인 3%)</option>
                                        <option value="gold" <?php echo ($member_detail['member_grade'] ?? '') === 'gold' ? 'selected' : ''; ?>>골드 (할인 5%)</option>
                                        <option value="vip" <?php echo ($member_detail['member_grade'] ?? '') === 'vip' ? 'selected' : ''; ?>>VIP (할인 10%)</option>
                                    </select>
                                    <?php if($member_detail['grade_updated_at']): ?>
                                        <small style="color: #999; margin-left: 8px;">
                                            (변경일: <?php echo date('Y-m-d H:i', strtotime($member_detail['grade_updated_at'])); ?>)
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="detail-item" style="grid-column: 1 / -1;">
                                <div class="detail-label">관리자 메모</div>
                                <input type="hidden" name="memo" value="<?php echo htmlspecialchars($member_detail['memo'] ?? ''); ?>">
                                
                                <!-- 메모 리스트 -->
                                <div class="memo-list">
                                    <?php if (!empty($member_detail['memo'])): ?>
                                        <?php 
                                        // 메모를 줄바꿈으로 구분하여 개별 메모로 처리
                                        $memos = array_filter(explode("\n---\n", $member_detail['memo']));
                                        foreach ($memos as $index => $memo): 
                                            $memo_parts = explode("\n", $memo, 2);
                                            $date_line = $memo_parts[0] ?? '';
                                            $content = $memo_parts[1] ?? $memo;
                                        ?>
                                            <div class="memo-item">
                                                <?php if (strpos($date_line, '[') === 0): ?>
                                                    <div class="memo-date"><?php echo htmlspecialchars($date_line); ?></div>
                                                    <div class="memo-content"><?php echo htmlspecialchars($content); ?></div>
                                                <?php else: ?>
                                                    <div class="memo-content"><?php echo htmlspecialchars($memo); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p style="text-align: center; color: #999; margin: 0;">등록된 메모가 없습니다.</p>
                                    <?php endif; ?>
                                </div>

                                <!-- toggleMemoAdd 함수를 버튼보다 먼저 정의 -->
                                <script>
                                function toggleMemoAdd() {
                                    const addSection = document.getElementById('memo-add-section');
                                    addSection.classList.toggle('active');
                                    if (addSection.classList.contains('active')) {
                                        document.getElementById('new-memo-content').focus();
                                    }
                                }
                                </script>

                                <!-- 메모 추가 버튼 -->
                                <button type="button" class="add-memo-btn" onclick="toggleMemoAdd()" style="margin-top: 12px;">
                                    + 메모 추가
                                </button>
                                
                                <!-- 메모 추가 섹션 -->
                                <div class="memo-add-section" id="memo-add-section">
                                    <textarea id="new-memo-content" class="memo-textarea" placeholder="새 메모를 작성하세요..." style="margin-bottom: 12px;"></textarea>
                                    <div style="display: flex; gap: 8px;">
                                        <button type="button" onclick="addMemo(<?php echo $member_detail['id']; ?>)" 
                                                style="padding: 8px 16px; background: #4CAF50; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600;">
                                            저장
                                        </button>
                                        <button type="button" onclick="toggleMemoAdd()" 
                                                style="padding: 8px 16px; background: #666; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px;">
                                            취소
                                        </button>
                                    </div>
                                </div>
                                
                                <span id="memo-save-result" style="margin-left: 12px; font-size: 14px;"></span>
                            </div>
                        </div>
                        <button type="submit" style="margin-top: 16px; padding: 10px 20px; background: #1A237E; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600;">
                            정보 수정
                        </button>
                    </form>
                </div>
                
                <!-- 비밀번호 변경 섹션 -->
                <div class="member-detail" style="margin-top: 24px;">
                    <h3 style="font-size: 18px; margin-bottom: 16px;">비밀번호 변경</h3>
                    <form method="POST" action="admin_members_action.php">
                        <input type="hidden" name="action" value="change_password">
                        <input type="hidden" name="member_id" value="<?php echo $member_detail['id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <div class="detail-grid">
                            <div class="detail-item" style="grid-column: 1 / -1;">
                                <div class="detail-label">새 비밀번호</div>
                                <input type="password" name="new_password" required 
                                       style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            </div>
                            <div class="detail-item" style="grid-column: 1 / -1;">
                                <div class="detail-label">새 비밀번호 확인</div>
                                <input type="password" name="new_password_confirm" required 
                                       style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            </div>
                        </div>
                        <button type="submit" 
                                style="margin-top: 16px; padding: 10px 20px; background: #1A237E; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600;">
                            비밀번호 변경
                        </button>
                    </form>
                </div>
                
                <!-- 로그인 이력 섹션 -->
                <?php
                $member_id = $member_detail['id'];
                include 'includes/member_login_history.php';
                ?>

                <!-- 관리 이력 섹션 -->
                <?php include 'includes/member_admin_history.php'; ?>

                <!-- 상태 변경 / 삭제 (POST 폼) -->
                <div class="member-detail" style="margin-top: 24px;">
                    <h3 style="font-size: 18px; margin-bottom: 16px;">회원 관리</h3>
                    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                        <!-- 상태 변경 폼 -->
                        <form method="POST" action="admin_members_action.php" style="display: inline;" onsubmit="return confirm('상태를 변경하시겠습니까?');">
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="member_id" value="<?php echo $member_detail['id']; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                            <button type="submit" class="btn-toggle" style="padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600; background: <?php echo $member_detail['is_active'] ? '#FF9800' : '#4CAF50'; ?>; color: white;">
                                <?php echo $member_detail['is_active'] ? '비활성화' : '활성화'; ?>
                            </button>
                        </form>

                        <?php if(!$member_detail['is_admin']): ?>
                        <!-- 삭제 폼 -->
                        <form method="POST" action="admin_members_action.php" style="display: inline;" onsubmit="return confirm('정말 삭제하시겠습니까? 이 작업은 되돌릴 수 없습니다.');">
                            <input type="hidden" name="action" value="delete_member">
                            <input type="hidden" name="member_id" value="<?php echo $member_detail['id']; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                            <button type="submit" class="btn-delete" style="padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600; background: #F44336; color: white;">
                                회원 삭제
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <script>
            // 지역번호 선택 변경 시 기타 입력 필드 표시/숨김
            document.querySelector('select[name="landline_area"]')?.addEventListener('change', function() {
                const otherInput = document.querySelector('input[name="landline_area_other"]');
                if (this.value === 'other') {
                    otherInput.style.display = 'inline-block';
                    otherInput.focus();
                } else {
                    otherInput.style.display = 'none';
                    otherInput.value = '';
                }
            });

            // 폼 제출 시 전화번호 조합
            document.querySelector('form[action="admin_members_action.php"]')?.addEventListener('submit', function(e) {
                // 휴대폰 번호 조합
                const phonePrefix = document.querySelector('select[name="phone_prefix"]')?.value || '';
                const phoneMiddle = document.querySelector('input[name="phone_middle"]')?.value || '';
                const phoneLast = document.querySelector('input[name="phone_last"]')?.value || '';
                
                if (phonePrefix && phoneMiddle && phoneLast) {
                    document.querySelector('input[name="phone"]').value = phonePrefix + '-' + phoneMiddle + '-' + phoneLast;
                } else {
                    document.querySelector('input[name="phone"]').value = '';
                }
                
                // 일반전화번호 조합
                const areaSelect = document.querySelector('select[name="landline_area"]');
                let area = areaSelect?.value || '';
                
                // 기타 선택 시 직접 입력한 값 사용
                if (area === 'other') {
                    area = document.querySelector('input[name="landline_area_other"]').value;
                }
                
                const middle = document.querySelector('input[name="landline_middle"]')?.value || '';
                const last = document.querySelector('input[name="landline_last"]')?.value || '';
                
                if (area && middle && last) {
                    document.querySelector('input[name="landline"]').value = area + '-' + middle + '-' + last;
                } else {
                    document.querySelector('input[name="landline"]').value = '';
                }
            });

            // 숫자만 입력 가능하도록 제한
            document.querySelectorAll('input[name="phone_middle"], input[name="phone_last"], input[name="landline_middle"], input[name="landline_last"], input[name="landline_area_other"]').forEach(input => {
                input?.addEventListener('input', function(e) {
                    this.value = this.value.replace(/[^0-9]/g, '');
                });
            });
            
            // 상태 체크박스 변경 이벤트
            const statusCheckbox = document.querySelector('input[name="is_active"]');
            const statusText = document.getElementById('status-text');
            
            statusCheckbox?.addEventListener('change', function() {
                if (this.checked) {
                    statusText.textContent = '활성';
                    statusText.style.color = '#2E7D32';
                } else {
                    statusText.textContent = '비활성';
                    statusText.style.color = '#C62828';
                }
            });
            
            function findZipcode() {
                new daum.Postcode({
                    oncomplete: function(data) {
                        document.getElementById('zipcode').value = data.zonecode;
                        document.getElementById('address').value = data.roadAddress;
                        document.querySelector('input[name="address_detail"]').focus();
                    }
                }).open();
            }

            function addMemo(memberId) {
                const newMemoContent = document.getElementById('new-memo-content').value.trim();
                if (!newMemoContent) {
                    alert('메모 내용을 입력해주세요.');
                    return;
                }
                
                const resultSpan = document.getElementById('memo-save-result');
                
                // 현재 메모 가져오기
                const currentMemo = document.querySelector('input[name="memo"]').value;
                
                // 새 메모 포맷 (날짜 포함)
                const now = new Date();
                const dateStr = now.getFullYear() + '-' + 
                               String(now.getMonth() + 1).padStart(2, '0') + '-' + 
                               String(now.getDate()).padStart(2, '0') + ' ' + 
                               String(now.getHours()).padStart(2, '0') + ':' + 
                               String(now.getMinutes()).padStart(2, '0');
                
                const newMemoFormatted = '[' + dateStr + ']\n' + newMemoContent;
                
                // 기존 메모와 새 메모 결합
                const updatedMemo = currentMemo ? currentMemo + '\n---\n' + newMemoFormatted : newMemoFormatted;
                
                // AJAX 요청
                fetch('admin_members_action.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=save_memo&member_id=' + memberId + '&memo=' + encodeURIComponent(updatedMemo)
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        resultSpan.textContent = '✅ 메모가 추가되었습니다';
                        resultSpan.style.color = '#4CAF50';
                        setTimeout(() => {
                            location.reload(); // 페이지 새로고침으로 목록 업데이트
                        }, 1000);
                    } else {
                        resultSpan.textContent = '❌ ' + (data.message || '저장 실패');
                        resultSpan.style.color = '#F44336';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    resultSpan.textContent = '❌ 오류 발생';
                    resultSpan.style.color = '#F44336';
                });
            }
            </script>
            
            <script src="//t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js"></script>
            
        <?php else: ?>
            <div class="page-header">
                <h1>회원 관리</h1>
                <p>가입된 회원을 조회하고 관리할 수 있습니다. <?php if($search && $tab === 'list'): ?><strong>검색어: "<?php echo htmlspecialchars($search); ?>"</strong><?php endif; ?></p>
            </div>

            <!-- 탭 메뉴 -->
            <div class="member-tabs">
                <a href="?tab=list" class="member-tab <?php echo $tab === 'list' ? 'active' : ''; ?>">
                    회원 목록 <span class="badge"><?php echo number_format($total ?? 0); ?></span>
                </a>
                <a href="?tab=permissions" class="member-tab <?php echo $tab === 'permissions' ? 'active' : ''; ?>">
                    권한설정
                </a>
            </div>

            <?php if($tab === 'list'): ?>
            <!-- 회원 목록 탭 -->
            <div style="margin-bottom: 20px; text-align: right;">
                <a href="admin_members_add.php" class="btn btn-primary" style="padding: 10px 20px; background: #1A237E; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 14px; display: inline-block;">+ 회원 추가</a>
            </div>
            
            <form method="GET" action="" class="search-bar">
                <input type="text" name="search" class="search-input" 
                       placeholder="아이디, 이름, 이메일, 회사명, 메모로 검색" 
                       value="<?php echo htmlspecialchars($search); ?>">
                <select name="filter" class="filter-select">
                    <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>전체 상태</option>
                    <option value="active" <?php echo $filter === 'active' ? 'selected' : ''; ?>>활성</option>
                    <option value="inactive" <?php echo $filter === 'inactive' ? 'selected' : ''; ?>>비활성</option>
                </select>
                <select name="grade" class="filter-select">
                    <option value="all" <?php echo $grade_filter === 'all' ? 'selected' : ''; ?>>전체 등급</option>
                    <option value="normal" <?php echo $grade_filter === 'normal' ? 'selected' : ''; ?>>일반</option>
                    <option value="silver" <?php echo $grade_filter === 'silver' ? 'selected' : ''; ?>>실버</option>
                    <option value="gold" <?php echo $grade_filter === 'gold' ? 'selected' : ''; ?>>골드</option>
                    <option value="vip" <?php echo $grade_filter === 'vip' ? 'selected' : ''; ?>>VIP</option>
                </select>
                <button type="submit" class="search-btn">검색</button>
                <?php if($search || $filter !== 'all' || $grade_filter !== 'all'): ?>
                    <a href="admin_members.php" class="search-btn" style="background: #666; text-decoration: none; display: inline-flex; align-items: center;">초기화</a>
                <?php endif; ?>
            </form>
            
            <div class="content-box">
                <div class="table-wrapper">
                    <table class="members-table">
                        <thead>
                            <tr>
                                <th>번호</th>
                                <th>아이디</th>
                                <th>이름</th>
                                <th>회사명</th>
                                <th>등급</th>
                                <th>가입일</th>
                                <th>상태</th>
                                <th>관리</th>
                            </tr>
                        </thead>
                    <tbody>
                        <?php if(empty($members)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 40px;">
                                    등록된 회원이 없습니다.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php
                            // 등급 레이블 정의
                            $gradeLabels = [
                                'normal' => '일반',
                                'silver' => '실버',
                                'gold' => '골드',
                                'vip' => 'VIP'
                            ];
                            ?>
                            <?php foreach($members as $member): ?>
                                <tr>
                                    <td><?php echo $member['id']; ?></td>
                                    <td><?php echo htmlspecialchars($member['user_id']); ?></td>
                                    <td>
                                        <div class="member-name-cell">
                                            <span>
                                                <?php echo htmlspecialchars($member['name']); ?>
                                                <?php if($member['is_admin']): ?>
                                                    <small style="color: #1A237E;">(관리자)</small>
                                                <?php endif; ?>
                                            </span>
                                            <?php if(!empty($member['memo'])): ?>
                                                <button type="button" class="memo-view-btn" onclick="showMemoModal(<?php echo $member['id']; ?>, '<?php echo htmlspecialchars($member['name']); ?>', <?php echo htmlspecialchars(json_encode($member['memo'])); ?>)">
                                                    메모 보기
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="memo-add-btn" onclick="showMemoModal(<?php echo $member['id']; ?>, '<?php echo htmlspecialchars($member['name']); ?>', '')">메모 추가</button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-ellipsis" title="<?php echo htmlspecialchars($member['company'] ?? '-'); ?>">
                                            <?php echo htmlspecialchars($member['company'] ?? '-'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php $grade = $member['member_grade'] ?? 'normal'; ?>
                                        <span class="grade-badge grade-<?php echo $grade; ?>">
                                            <?php echo $gradeLabels[$grade] ?? '일반'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('Y-m-d', strtotime($member['created_at'])); ?></td>
                                    <td>
                                        <?php if($member['is_active']): ?>
                                            <span class="status-badge status-active">활성</span>
                                        <?php else: ?>
                                            <span class="status-badge status-inactive">비활성</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-links">
                                            <a href="?action=view&id=<?php echo $member['id']; ?>" class="btn-view">상세</a>
                                            <button type="button" class="btn-toggle" onclick="toggleMemberStatus(<?php echo $member['id']; ?>)">상태</button>
                                            <?php if(!$member['is_admin']): ?>
                                                <button type="button" class="btn-delete" onclick="deleteMember(<?php echo $member['id']; ?>)">삭제</button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
                
                <!-- 페이지네이션 및 표시 개수 선택 -->
                <div class="pagination-wrapper" style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px;">
                    <div class="show-entries">
                        <form method="GET" action="" style="display: flex; align-items: center; gap: 8px;">
                            <?php foreach($_GET as $key => $value): ?>
                                <?php if($key !== 'limit' && $key !== 'page'): ?>
                                    <input type="hidden" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($value); ?>">
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <label style="font-size: 14px;">표시:</label>
                            <select name="limit" onchange="this.form.submit()" class="filter-select" style="width: auto; padding: 6px 10px;">
                                <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10개</option>
                                <option value="20" <?php echo $limit == 20 ? 'selected' : ''; ?>>20개</option>
                                <option value="30" <?php echo $limit == 30 ? 'selected' : ''; ?>>30개</option>
                                <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50개</option>
                                <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100개</option>
                            </select>
                        </form>
                    </div>
                    
                    <?php if($totalPages > 1): ?>
                        <div class="pagination">
                            <?php
                            $queryParams = $_GET;
                            unset($queryParams['page']);
                            $baseQuery = http_build_query($queryParams);
                            $baseUrl = '?' . ($baseQuery ? $baseQuery . '&' : '');
                            
                            // 페이지 범위 계산
                            $visiblePages = 5; // 표시할 페이지 번호 개수
                            $halfVisible = floor($visiblePages / 2);
                            
                            $startPage = max(1, $page - $halfVisible);
                            $endPage = min($totalPages, $page + $halfVisible);
                            
                            // 시작 페이지 조정
                            if($endPage - $startPage + 1 < $visiblePages) {
                                if($startPage == 1) {
                                    $endPage = min($totalPages, $startPage + $visiblePages - 1);
                                } else {
                                    $startPage = max(1, $endPage - $visiblePages + 1);
                                }
                            }
                            ?>
                            
                            <!-- 첫 페이지 -->
                            <?php if($page > 1): ?>
                                <a href="<?php echo $baseUrl; ?>page=1" class="page-link" title="첫 페이지">
                                    <span style="font-size: 12px;">≪</span>
                                </a>
                            <?php endif; ?>
                            
                            <!-- 이전 페이지 -->
                            <?php if($page > 1): ?>
                                <a href="<?php echo $baseUrl; ?>page=<?php echo $page - 1; ?>" class="page-link" title="이전 페이지">
                                    <span style="font-size: 12px;">＜</span>
                                </a>
                            <?php endif; ?>
                            
                            <!-- 페이지 번호 -->
                            <?php if($startPage > 1): ?>
                                <span class="page-dots">...</span>
                            <?php endif; ?>
                            
                            <?php for($i = $startPage; $i <= $endPage; $i++): ?>
                                <a href="<?php echo $baseUrl; ?>page=<?php echo $i; ?>" 
                                   class="page-link <?php echo $page == $i ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if($endPage < $totalPages): ?>
                                <span class="page-dots">...</span>
                            <?php endif; ?>
                            
                            <!-- 다음 페이지 -->
                            <?php if($page < $totalPages): ?>
                                <a href="<?php echo $baseUrl; ?>page=<?php echo $page + 1; ?>" class="page-link" title="다음 페이지">
                                    <span style="font-size: 12px;">＞</span>
                                </a>
                            <?php endif; ?>
                            
                            <!-- 마지막 페이지 -->
                            <?php if($page < $totalPages): ?>
                                <a href="<?php echo $baseUrl; ?>page=<?php echo $totalPages; ?>" class="page-link" title="마지막 페이지">
                                    <span style="font-size: 12px;">≫</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="page-info" style="font-size: 14px; color: #666;">
                        전체 <?php echo number_format($total); ?>명 중 
                        <?php echo number_format(($page - 1) * $limit + 1); ?>-<?php echo number_format(min($page * $limit, $total)); ?>명
                    </div>
                </div>
            </div>
            
            <!-- 메모 모달 -->
            <div id="memoModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 id="modalMemberName">회원 메모</h2>
                        <span class="modal-close" onclick="closeMemoModal()">&times;</span>
                    </div>
                    <div class="modal-body">
                        <div id="modalMemoContent" class="modal-memo-list">
                            <!-- 메모 내용이 여기에 표시됩니다 -->
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="modal-btn modal-add-btn" onclick="toggleModalMemoAdd()">
                            + 메모 추가
                        </button>
                        <div class="modal-add-memo-section" id="modal-add-memo-section">
                            <textarea id="modal-new-memo-content" class="modal-memo-textarea" placeholder="새 메모를 작성하세요..."></textarea>
                            <div>
                                <button type="button" class="modal-btn modal-btn-primary" onclick="saveModalMemo()">
                                    저장
                                </button>
                                <button type="button" class="modal-btn modal-btn-secondary" onclick="toggleModalMemoAdd()">
                                    취소
                                </button>
                            </div>
                            <span id="modal-memo-save-result" style="margin-left: 12px; font-size: 14px;"></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <script>
            let currentModalMemberId = null;
            let currentModalMemberName = null;
            
            function showMemoModal(memberId, memberName, memoData) {
                const modal = document.getElementById('memoModal');
                const modalTitle = document.getElementById('modalMemberName');
                const modalContent = document.getElementById('modalMemoContent');
                
                // 현재 회원 정보 저장
                currentModalMemberId = memberId;
                currentModalMemberName = memberName;
                
                // 타이틀 설정
                modalTitle.textContent = memberName + '님의 메모';
                
                // 메모 내용 파싱 및 표시
                if (memoData) {
                    const memos = memoData.split('\\n---\\n').filter(memo => memo.trim());
                    
                    if (memos.length > 0) {
                        modalContent.innerHTML = memos.map(memo => {
                            const lines = memo.split('\\n');
                            let dateStr = '';
                            let content = memo;
                            
                            // 날짜 형식 검사
                            if (lines[0] && lines[0].startsWith('[') && lines[0].endsWith(']')) {
                                dateStr = lines[0];
                                content = lines.slice(1).join('\\n');
                            }
                            
                            return `
                                <div class="modal-memo-item">
                                    ${dateStr ? `<div class="modal-memo-date">${dateStr}</div>` : ''}
                                    <div class="modal-memo-content">${content.replace(/\\n/g, '<br>')}</div>
                                </div>
                            `;
                        }).join('');
                    } else {
                        modalContent.innerHTML = '<div class="modal-no-memo">등록된 메모가 없습니다.</div>';
                    }
                } else {
                    modalContent.innerHTML = '<div class="modal-no-memo">등록된 메모가 없습니다.</div>';
                }
                
                // 메모 추가 섹션 초기화
                document.getElementById('modal-add-memo-section').classList.remove('active');
                document.getElementById('modal-new-memo-content').value = '';
                
                // 메모가 없으면 자동으로 메모 추가 섹션 열기
                if (!memoData || memoData.trim() === '') {
                    setTimeout(() => {
                        toggleModalMemoAdd();
                    }, 100);
                }
                
                // 모달 표시
                modal.style.display = 'block';
            }
            
            function closeMemoModal() {
                document.getElementById('memoModal').style.display = 'none';
                currentModalMemberId = null;
                currentModalMemberName = null;
            }
            
            function toggleModalMemoAdd() {
                const addSection = document.getElementById('modal-add-memo-section');
                addSection.classList.toggle('active');
                if (addSection.classList.contains('active')) {
                    document.getElementById('modal-new-memo-content').focus();
                }
            }
            
            function saveModalMemo() {
                if (!currentModalMemberId) return;
                
                const newMemoContent = document.getElementById('modal-new-memo-content').value.trim();
                if (!newMemoContent) {
                    alert('메모 내용을 입력해주세요.');
                    return;
                }
                
                const resultSpan = document.getElementById('modal-memo-save-result');
                
                // 현재 메모 가져오기 (로커 검색을 통해 현재 메모 상태 파악)
                const modalContent = document.getElementById('modalMemoContent');
                const hasExistingMemo = !modalContent.querySelector('.modal-no-memo');
                
                // 새 메모 포맷 (날짜 포함)
                const now = new Date();
                const dateStr = now.getFullYear() + '-' + 
                               String(now.getMonth() + 1).padStart(2, '0') + '-' + 
                               String(now.getDate()).padStart(2, '0') + ' ' + 
                               String(now.getHours()).padStart(2, '0') + ':' + 
                               String(now.getMinutes()).padStart(2, '0');
                
                const newMemoFormatted = '[' + dateStr + ']\\n' + newMemoContent;
                
                // AJAX 요청
                fetch('admin_members_action.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=add_memo&member_id=' + currentModalMemberId + '&new_memo=' + encodeURIComponent(newMemoFormatted)
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        resultSpan.textContent = '✅ 메모가 추가되었습니다';
                        resultSpan.style.color = '#4CAF50';
                        
                        // 모달에 새 메모 추가
                        const newMemoHtml = `
                            <div class="modal-memo-item">
                                <div class="modal-memo-date">[${dateStr}]</div>
                                <div class="modal-memo-content">${newMemoContent.replace(/\n/g, '<br>')}</div>
                            </div>
                        `;
                        
                        if (hasExistingMemo) {
                            modalContent.insertAdjacentHTML('afterbegin', newMemoHtml);
                        } else {
                            modalContent.innerHTML = newMemoHtml;
                        }
                        
                        // 입력 필드 초기화
                        document.getElementById('modal-new-memo-content').value = '';
                        toggleModalMemoAdd();
                        
                        setTimeout(() => {
                            resultSpan.textContent = '';
                        }, 3000);
                    } else {
                        resultSpan.textContent = '❌ ' + (data.message || '저장 실패');
                        resultSpan.style.color = '#F44336';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    resultSpan.textContent = '❌ 오류 발생';
                    resultSpan.style.color = '#F44336';
                });
            }
            
            // 모달 외부 클릭 시 닫기
            window.onclick = function(event) {
                const modal = document.getElementById('memoModal');
                if (event.target === modal) {
                    closeMemoModal();
                }
            }
            
            // ESC 키로 모달 닫기
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    closeMemoModal();
                }
            });
            </script>
            <?php endif; // end tab === 'list' ?>

            <?php if($tab === 'permissions'): ?>
            <!-- 권한설정 탭 -->
            <div id="permissions-content">
                <!-- 등급별 통계 -->
                <div class="permission-section">
                    <h3 class="permission-section-title">등급별 회원 현황</h3>
                    <div class="stats-grid" id="grade-stats">
                        <div class="stat-card">
                            <div class="stat-grade">일반</div>
                            <div class="stat-count" id="stat-normal">-</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-grade">실버</div>
                            <div class="stat-count" id="stat-silver">-</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-grade">골드</div>
                            <div class="stat-count" id="stat-gold">-</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-grade">VIP</div>
                            <div class="stat-count" id="stat-vip">-</div>
                        </div>
                    </div>
                </div>

                <!-- 등급 설정 -->
                <div class="permission-section">
                    <h3 class="permission-section-title">등급별 할인율 설정</h3>
                    <div id="grade-settings-alert"></div>
                    <table class="grade-settings-table">
                        <thead>
                            <tr>
                                <th>등급코드</th>
                                <th>등급명</th>
                                <th>할인율 (%)</th>
                                <th>정렬순서</th>
                                <th>활성</th>
                            </tr>
                        </thead>
                        <tbody id="grade-settings-body">
                            <tr><td colspan="5" style="text-align:center;padding:40px;color:#999;">로딩 중...</td></tr>
                        </tbody>
                    </table>
                    <div style="margin-top: 16px; display: flex; gap: 12px;">
                        <button type="button" class="btn-primary" onclick="saveGradeSettings()">등급 설정 저장</button>
                    </div>
                </div>

                <!-- 등급별 접근 권한 -->
                <div class="permission-section">
                    <h3 class="permission-section-title">등급별 접근 권한</h3>
                    <div id="permission-matrix-alert"></div>
                    <table class="permission-matrix" id="permission-matrix">
                        <thead>
                            <tr>
                                <th style="width:40%;">권한</th>
                                <th>일반</th>
                                <th>실버</th>
                                <th>골드</th>
                                <th>VIP</th>
                            </tr>
                        </thead>
                        <tbody id="permission-matrix-body">
                            <tr><td colspan="5" style="text-align:center;padding:40px;color:#999;">로딩 중...</td></tr>
                        </tbody>
                    </table>
                    <div style="margin-top: 16px; display: flex; gap: 12px;">
                        <button type="button" class="btn-primary" onclick="savePermissions()">권한 설정 저장</button>
                    </div>
                </div>

                <!-- 일괄 등급 변경 -->
                <div class="permission-section">
                    <h3 class="permission-section-title">일괄 등급 변경</h3>
                    <div id="bulk-change-alert"></div>
                    <div class="bulk-change-form">
                        <label>현재 등급:</label>
                        <select id="bulk-from-grade">
                            <option value="all">전체</option>
                            <option value="normal">일반</option>
                            <option value="silver">실버</option>
                            <option value="gold">골드</option>
                            <option value="vip">VIP</option>
                        </select>
                        <span style="font-size:20px;">→</span>
                        <label>변경할 등급:</label>
                        <select id="bulk-to-grade">
                            <option value="normal">일반</option>
                            <option value="silver">실버</option>
                            <option value="gold">골드</option>
                            <option value="vip">VIP</option>
                        </select>
                        <button type="button" class="btn-secondary" onclick="previewBulkChange()">미리보기</button>
                        <button type="button" class="btn-success" onclick="executeBulkChange()">일괄 변경</button>
                    </div>
                    <div id="bulk-preview" style="margin-top: 16px; display: none;"></div>
                </div>

                <!-- 관리자 목록 -->
                <div class="permission-section">
                    <h3 class="permission-section-title">관리자 권한 관리</h3>
                    <div id="admin-list-alert"></div>
                    <table class="admin-list-table">
                        <thead>
                            <tr>
                                <th>아이디</th>
                                <th>이름</th>
                                <th>회사명</th>
                                <th>관리자 권한</th>
                            </tr>
                        </thead>
                        <tbody id="admin-list-body">
                            <tr><td colspan="4" style="text-align:center;padding:40px;color:#999;">로딩 중...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <script>
            // 권한설정 탭 JavaScript
            document.addEventListener('DOMContentLoaded', function() {
                loadGradeStats();
                loadGradeSettings();
                loadPermissionMatrix();
                loadAdminList();
            });

            // 등급별 통계 로드
            function loadGradeStats() {
                fetch('ajax/member_permissions.php?action=get_stats')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('stat-normal').innerHTML = data.stats.normal + '<small>명</small>';
                            document.getElementById('stat-silver').innerHTML = data.stats.silver + '<small>명</small>';
                            document.getElementById('stat-gold').innerHTML = data.stats.gold + '<small>명</small>';
                            document.getElementById('stat-vip').innerHTML = data.stats.vip + '<small>명</small>';
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }

            // 등급 설정 로드
            function loadGradeSettings() {
                fetch('ajax/member_permissions.php?action=get_grade_settings')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const tbody = document.getElementById('grade-settings-body');
                            tbody.innerHTML = data.grades.map(grade => `
                                <tr data-grade-code="${grade.grade_code}">
                                    <td><code>${grade.grade_code}</code></td>
                                    <td><input type="text" name="grade_name" value="${grade.grade_name}" style="width:100px;"></td>
                                    <td><input type="number" name="discount_rate" value="${grade.discount_rate}" step="0.5" min="0" max="100" style="width:70px;">%</td>
                                    <td><input type="number" name="sort_order" value="${grade.sort_order}" min="1" max="100" style="width:60px;"></td>
                                    <td><input type="checkbox" name="is_active" ${grade.is_active == 1 ? 'checked' : ''}></td>
                                </tr>
                            `).join('');
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }

            // 등급 설정 저장
            function saveGradeSettings() {
                const rows = document.querySelectorAll('#grade-settings-body tr');
                const grades = [];

                rows.forEach(row => {
                    const gradeCode = row.dataset.gradeCode;
                    if (gradeCode) {
                        grades.push({
                            grade_code: gradeCode,
                            grade_name: row.querySelector('input[name="grade_name"]').value,
                            discount_rate: parseFloat(row.querySelector('input[name="discount_rate"]').value),
                            sort_order: parseInt(row.querySelector('input[name="sort_order"]').value),
                            is_active: row.querySelector('input[name="is_active"]').checked ? 1 : 0
                        });
                    }
                });

                fetch('ajax/member_permissions.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'save_grade_settings', grades: grades })
                })
                .then(response => response.json())
                .then(data => {
                    const alertDiv = document.getElementById('grade-settings-alert');
                    if (data.success) {
                        alertDiv.innerHTML = '<div class="alert alert-success">등급 설정이 저장되었습니다.</div>';
                    } else {
                        alertDiv.innerHTML = '<div class="alert alert-error">' + (data.message || '저장 실패') + '</div>';
                    }
                    setTimeout(() => alertDiv.innerHTML = '', 3000);
                })
                .catch(error => console.error('Error:', error));
            }

            // 권한 매트릭스 로드
            function loadPermissionMatrix() {
                fetch('ajax/member_permissions.php?action=get_permission_matrix')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const tbody = document.getElementById('permission-matrix-body');
                            tbody.innerHTML = data.permissions.map(perm => {
                                const grades = ['normal', 'silver', 'gold', 'vip'];
                                const checkboxes = grades.map(grade => {
                                    const checked = data.matrix[grade] && data.matrix[grade].includes(perm.permission_code);
                                    return `<td><input type="checkbox" data-permission="${perm.permission_code}" data-grade="${grade}" ${checked ? 'checked' : ''}></td>`;
                                }).join('');
                                return `
                                    <tr>
                                        <td>
                                            <strong>${perm.permission_name}</strong>
                                            <br><small style="color:#999;">${perm.description || ''}</small>
                                        </td>
                                        ${checkboxes}
                                    </tr>
                                `;
                            }).join('');
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }

            // 권한 설정 저장
            function savePermissions() {
                const checkboxes = document.querySelectorAll('#permission-matrix-body input[type="checkbox"]');
                const permissions = {};

                checkboxes.forEach(cb => {
                    const grade = cb.dataset.grade;
                    const permission = cb.dataset.permission;
                    if (!permissions[grade]) {
                        permissions[grade] = [];
                    }
                    if (cb.checked) {
                        permissions[grade].push(permission);
                    }
                });

                fetch('ajax/member_permissions.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'save_permissions', permissions: permissions })
                })
                .then(response => response.json())
                .then(data => {
                    const alertDiv = document.getElementById('permission-matrix-alert');
                    if (data.success) {
                        alertDiv.innerHTML = '<div class="alert alert-success">권한 설정이 저장되었습니다.</div>';
                    } else {
                        alertDiv.innerHTML = '<div class="alert alert-error">' + (data.message || '저장 실패') + '</div>';
                    }
                    setTimeout(() => alertDiv.innerHTML = '', 3000);
                })
                .catch(error => console.error('Error:', error));
            }

            // 일괄 변경 미리보기
            function previewBulkChange() {
                const fromGrade = document.getElementById('bulk-from-grade').value;
                const toGrade = document.getElementById('bulk-to-grade').value;

                if (fromGrade === toGrade) {
                    alert('현재 등급과 변경할 등급이 같습니다.');
                    return;
                }

                fetch(`ajax/member_permissions.php?action=preview_bulk_change&from=${fromGrade}&to=${toGrade}`)
                    .then(response => response.json())
                    .then(data => {
                        const preview = document.getElementById('bulk-preview');
                        if (data.success) {
                            preview.style.display = 'block';
                            preview.innerHTML = `
                                <div style="padding: 16px; background: #E3F2FD; border-radius: 8px;">
                                    <strong>변경 대상: ${data.count}명</strong>
                                    <br><small style="color:#666;">변경하려면 "일괄 변경" 버튼을 클릭하세요.</small>
                                </div>
                            `;
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }

            // 일괄 등급 변경 실행
            function executeBulkChange() {
                const fromGrade = document.getElementById('bulk-from-grade').value;
                const toGrade = document.getElementById('bulk-to-grade').value;

                if (fromGrade === toGrade) {
                    alert('현재 등급과 변경할 등급이 같습니다.');
                    return;
                }

                if (!confirm('정말 일괄 변경하시겠습니까? 이 작업은 되돌릴 수 없습니다.')) {
                    return;
                }

                fetch('ajax/member_permissions.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'execute_bulk_change', from: fromGrade, to: toGrade })
                })
                .then(response => response.json())
                .then(data => {
                    const alertDiv = document.getElementById('bulk-change-alert');
                    if (data.success) {
                        alertDiv.innerHTML = `<div class="alert alert-success">${data.count}명의 등급이 변경되었습니다.</div>`;
                        document.getElementById('bulk-preview').style.display = 'none';
                        loadGradeStats(); // 통계 갱신
                    } else {
                        alertDiv.innerHTML = '<div class="alert alert-error">' + (data.message || '변경 실패') + '</div>';
                    }
                    setTimeout(() => alertDiv.innerHTML = '', 3000);
                })
                .catch(error => console.error('Error:', error));
            }

            // 관리자 목록 로드
            function loadAdminList() {
                fetch('ajax/member_permissions.php?action=get_admin_list')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const tbody = document.getElementById('admin-list-body');
                            tbody.innerHTML = data.members.map(member => `
                                <tr>
                                    <td>${member.user_id}</td>
                                    <td>${member.name}</td>
                                    <td>${member.company || '-'}</td>
                                    <td>
                                        <button type="button" class="admin-toggle-btn ${member.is_admin == 1 ? 'is-admin' : 'not-admin'}"
                                                onclick="toggleAdmin(${member.id}, ${member.is_admin})">
                                            ${member.is_admin == 1 ? '관리자' : '관리자 부여'}
                                        </button>
                                    </td>
                                </tr>
                            `).join('');
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }

            // 관리자 권한 토글
            function toggleAdmin(memberId, currentStatus) {
                const action = currentStatus == 1 ? '관리자 권한을 해제하시겠습니까?' : '관리자 권한을 부여하시겠습니까?';
                if (!confirm(action)) return;

                fetch('ajax/member_permissions.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'toggle_admin', member_id: memberId })
                })
                .then(response => response.json())
                .then(data => {
                    const alertDiv = document.getElementById('admin-list-alert');
                    if (data.success) {
                        alertDiv.innerHTML = '<div class="alert alert-success">관리자 권한이 변경되었습니다.</div>';
                        loadAdminList(); // 목록 갱신
                    } else {
                        alertDiv.innerHTML = '<div class="alert alert-error">' + (data.message || '변경 실패') + '</div>';
                    }
                    setTimeout(() => alertDiv.innerHTML = '', 3000);
                })
                .catch(error => console.error('Error:', error));
            }
            </script>
            <?php endif; // end tab === 'permissions' ?>
        <?php endif; ?>

<!-- 목록 페이지용 상태변경/삭제 폼 -->
<form id="memberActionForm" method="POST" action="admin_members_action.php" style="display: none;">
    <input type="hidden" name="action" id="memberActionType">
    <input type="hidden" name="member_id" id="memberActionId">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
</form>

<script>
// 회원 상태 변경 (목록 페이지용)
function toggleMemberStatus(memberId) {
    if (!confirm('상태를 변경하시겠습니까?')) return;

    document.getElementById('memberActionType').value = 'toggle_status';
    document.getElementById('memberActionId').value = memberId;
    document.getElementById('memberActionForm').submit();
}

// 회원 삭제 (목록 페이지용)
function deleteMember(memberId) {
    if (!confirm('정말 삭제하시겠습니까? 이 작업은 되돌릴 수 없습니다.')) return;

    document.getElementById('memberActionType').value = 'delete_member';
    document.getElementById('memberActionId').value = memberId;
    document.getElementById('memberActionForm').submit();
}
</script>

<?php require_once 'admin_tail.php'; ?>