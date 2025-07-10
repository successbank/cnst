<?php
/**
 * 사이트 설정 관리 함수들
 */

/**
 * 설정 값 가져오기
 */
function getSetting($key, $default = '') {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        
        return $result ? $result['setting_value'] : $default;
    } catch (PDOException $e) {
        return $default;
    }
}

/**
 * 그룹별 설정 가져오기
 */
function getSettingsByGroup($group) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM site_settings WHERE setting_group = ?");
        $stmt->execute([$group]);
        $results = $stmt->fetchAll();
        
        $settings = [];
        foreach ($results as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        return $settings;
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * 설정 값 저장하기
 */
function saveSetting($key, $value, $group = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value, setting_group) VALUES (?, ?, ?) 
                              ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = CURRENT_TIMESTAMP");
        $stmt->execute([$key, $value, $group, $value]);
        
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * 여러 설정 한번에 저장하기
 */
function saveSettings($settings, $group = null) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        foreach ($settings as $key => $value) {
            saveSetting($key, $value, $group);
        }
        
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        return false;
    }
}

/**
 * SEO 메타 태그 출력
 */
function printSeoMeta($pageTitle = null) {
    $siteTitle = getSetting('site_title', '충남스틸');
    $description = getSetting('site_description', '');
    $keywords = getSetting('site_keywords', '');
    
    // 페이지별 제목이 있으면 사이트 제목과 결합
    if ($pageTitle) {
        $fullTitle = $pageTitle . ' | ' . $siteTitle;
    } else {
        $fullTitle = $siteTitle;
    }
    
    echo '<title>' . htmlspecialchars($fullTitle) . '</title>' . "\n";
    
    if ($description) {
        echo '<meta name="description" content="' . htmlspecialchars($description) . '">' . "\n";
    }
    
    if ($keywords) {
        echo '<meta name="keywords" content="' . htmlspecialchars($keywords) . '">' . "\n";
    }
    
    // Open Graph 태그
    echo '<meta property="og:title" content="' . htmlspecialchars($fullTitle) . '">' . "\n";
    echo '<meta property="og:description" content="' . htmlspecialchars($description) . '">' . "\n";
    echo '<meta property="og:type" content="website">' . "\n";
}