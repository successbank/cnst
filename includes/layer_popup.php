<?php
/**
 * 레이어 팝업 모듈
 *
 * tail.php에서 include되어 모든 페이지에서 활성 팝업을 표시.
 * display_pages JSON 컬럼으로 페이지별 타겟팅 지원.
 */

// $pdo가 없으면 팝업 표시하지 않음
if (!isset($pdo)) {
    return;
}

// 현재 페이지 식별 ($currentPage가 설정되어 있으면 사용, 없으면 'main')
$popupCurrentPage = (isset($currentPage) && $currentPage !== '') ? $currentPage : 'main';

// 활성 팝업 조회
$layerPopups = [];
try {
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("
        SELECT * FROM layer_popups
        WHERE is_active = 1
        AND (start_date IS NULL OR start_date <= ?)
        AND (end_date IS NULL OR end_date >= ?)
        ORDER BY display_order ASC, id DESC
    ");
    $stmt->execute([$today, $today]);
    $allPopups = $stmt->fetchAll();

    // display_pages로 현재 페이지에 해당하는 팝업만 필터링
    foreach ($allPopups as $popup) {
        $pages = json_decode($popup['display_pages'] ?? '["main"]', true);
        if (!is_array($pages)) {
            $pages = ['main'];
        }
        if (in_array('all', $pages) || in_array($popupCurrentPage, $pages)) {
            $layerPopups[] = $popup;
        }
    }
} catch (PDOException $e) {
    $layerPopups = [];
}

if (!empty($layerPopups)):
?>
<!-- 레이어 팝업 -->
<style>
.layer-popup {
    position: fixed;
    z-index: 9999;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
    overflow: hidden;
    display: none;
}

.layer-popup.show {
    display: block;
    animation: popupFadeIn 0.3s ease;
}

@keyframes popupFadeIn {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.layer-popup-header {
    display: flex;
    justify-content: flex-end;
    padding: 8px 12px;
    background: #f5f5f5;
    border-bottom: 1px solid #e0e0e0;
}

.layer-popup-close {
    background: none;
    border: none;
    font-size: 20px;
    color: #666;
    cursor: pointer;
    padding: 4px 8px;
    line-height: 1;
    border-radius: 4px;
    transition: all 0.2s;
}

.layer-popup-close:hover {
    background: #e0e0e0;
    color: #333;
}

.layer-popup-content {
    overflow: auto;
}

.layer-popup-content img {
    display: block;
    max-width: 100%;
    height: auto;
}

.layer-popup-content a {
    display: block;
    text-decoration: none;
}

.layer-popup-html {
    padding: 20px;
}

.layer-popup-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    background: #f5f5f5;
    border-top: 1px solid #e0e0e0;
}

.layer-popup-hide-today {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: #666;
    cursor: pointer;
}

.layer-popup-hide-today input {
    cursor: pointer;
}

.layer-popup-close-btn {
    padding: 8px 16px;
    background: #1A237E;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s;
}

.layer-popup-close-btn:hover {
    background: #303F9F;
}

/* 모바일 대응 */
@media (max-width: 768px) {
    .layer-popup {
        left: 50% !important;
        top: 50% !important;
        transform: translate(-50%, -50%) !important;
        max-width: 90vw !important;
        max-height: 80vh !important;
    }

    .layer-popup.show {
        animation: popupFadeInMobile 0.3s ease;
    }

    @keyframes popupFadeInMobile {
        from {
            opacity: 0;
            transform: translate(-50%, -50%) scale(0.95);
        }
        to {
            opacity: 1;
            transform: translate(-50%, -50%) scale(1);
        }
    }
}
</style>

<?php foreach ($layerPopups as $index => $popup): ?>
<div class="layer-popup" id="layerPopup<?php echo $popup['id']; ?>"
     data-popup-id="<?php echo $popup['id']; ?>"
     data-hide-today="<?php echo $popup['hide_today']; ?>"
     style="left: <?php echo $popup['position_left']; ?>px;
            top: <?php echo $popup['position_top']; ?>px;
            width: <?php echo $popup['width']; ?>px;
            z-index: <?php echo 9999 - $index; ?>;">

    <div class="layer-popup-header">
        <button class="layer-popup-close" onclick="closeLayerPopup(<?php echo $popup['id']; ?>)" title="닫기">&times;</button>
    </div>

    <div class="layer-popup-content" style="max-height: <?php echo $popup['height'] - 100; ?>px;">
        <?php if ($popup['image_path']): ?>
            <?php if ($popup['link_url']): ?>
            <a href="<?php echo htmlspecialchars($popup['link_url']); ?>" target="<?php echo htmlspecialchars($popup['link_target']); ?>">
                <img src="uploads/popups/<?php echo htmlspecialchars($popup['image_path']); ?>" alt="<?php echo htmlspecialchars($popup['title']); ?>">
            </a>
            <?php else: ?>
            <img src="uploads/popups/<?php echo htmlspecialchars($popup['image_path']); ?>" alt="<?php echo htmlspecialchars($popup['title']); ?>">
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($popup['content']): ?>
        <div class="layer-popup-html">
            <?php if ($popup['link_url'] && !$popup['image_path']): ?>
            <a href="<?php echo htmlspecialchars($popup['link_url']); ?>" target="<?php echo htmlspecialchars($popup['link_target']); ?>">
                <?php echo $popup['content']; ?>
            </a>
            <?php else: ?>
            <?php echo $popup['content']; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="layer-popup-footer">
        <?php if ($popup['hide_today']): ?>
        <label class="layer-popup-hide-today">
            <input type="checkbox" id="hideToday<?php echo $popup['id']; ?>">
            오늘 하루 보지 않기
        </label>
        <?php else: ?>
        <span></span>
        <?php endif; ?>
        <button class="layer-popup-close-btn" onclick="closeLayerPopup(<?php echo $popup['id']; ?>)">닫기</button>
    </div>
</div>
<?php endforeach; ?>

<script>
// 레이어 팝업 초기화
document.addEventListener('DOMContentLoaded', function() {
    const popups = document.querySelectorAll('.layer-popup');

    popups.forEach(function(popup) {
        const popupId = popup.dataset.popupId;
        const cookieName = 'hidePopup' + popupId;

        // 쿠키 확인
        if (!getLayerPopupCookie(cookieName)) {
            popup.classList.add('show');
        }
    });
});

// 팝업 닫기
function closeLayerPopup(popupId) {
    const popup = document.getElementById('layerPopup' + popupId);
    const hideCheckbox = document.getElementById('hideToday' + popupId);

    if (hideCheckbox && hideCheckbox.checked) {
        // 오늘 하루 보지 않기 쿠키 설정 (자정까지)
        const now = new Date();
        const midnight = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1);
        setLayerPopupCookie('hidePopup' + popupId, '1', midnight);
    }

    popup.classList.remove('show');
}

// 쿠키 설정
function setLayerPopupCookie(name, value, expires) {
    let cookieStr = name + '=' + encodeURIComponent(value);
    if (expires) {
        cookieStr += '; expires=' + expires.toUTCString();
    }
    cookieStr += '; path=/';
    document.cookie = cookieStr;
}

// 쿠키 가져오기
function getLayerPopupCookie(name) {
    const nameEQ = name + '=';
    const cookies = document.cookie.split(';');
    for (let i = 0; i < cookies.length; i++) {
        let cookie = cookies[i].trim();
        if (cookie.indexOf(nameEQ) === 0) {
            return decodeURIComponent(cookie.substring(nameEQ.length));
        }
    }
    return null;
}
</script>
<?php endif; ?>
