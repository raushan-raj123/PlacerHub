<?php
/**
 * Common Functions
 * CompusDB Management System
 */

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email format
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number
 */
function validatePhone($phone) {
    return preg_match('/^[+]?[0-9\s\-\(\)]{10,15}$/', $phone);
}

/**
 * Generate random string
 */
function generateRandomString($length = 10) {
    return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
}

/**
 * Format file size
 */
function formatFileSize($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

/**
 * Format date for display
 */
function formatDate($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

/**
 * Format datetime for display
 */
function formatDateTime($datetime, $format = 'M d, Y H:i') {
    return date($format, strtotime($datetime));
}

/**
 * Time ago function
 */
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    
    return floor($time/31536000) . ' years ago';
}

/**
 * Generate pagination
 */
function generatePagination($currentPage, $totalPages, $baseUrl, $params = []) {
    $pagination = '';
    $range = 2; // Number of pages to show on each side of current page
    
    if ($totalPages <= 1) return '';
    
    $pagination .= '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    // Previous button
    if ($currentPage > 1) {
        $prevParams = array_merge($params, ['page' => $currentPage - 1]);
        $prevUrl = $baseUrl . '?' . http_build_query($prevParams);
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $prevUrl . '">Previous</a></li>';
    }
    
    // First page
    if ($currentPage > $range + 1) {
        $firstParams = array_merge($params, ['page' => 1]);
        $firstUrl = $baseUrl . '?' . http_build_query($firstParams);
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $firstUrl . '">1</a></li>';
        if ($currentPage > $range + 2) {
            $pagination .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    // Page numbers
    for ($i = max(1, $currentPage - $range); $i <= min($totalPages, $currentPage + $range); $i++) {
        $pageParams = array_merge($params, ['page' => $i]);
        $pageUrl = $baseUrl . '?' . http_build_query($pageParams);
        $active = ($i == $currentPage) ? ' active' : '';
        $pagination .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $pageUrl . '">' . $i . '</a></li>';
    }
    
    // Last page
    if ($currentPage < $totalPages - $range) {
        if ($currentPage < $totalPages - $range - 1) {
            $pagination .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        $lastParams = array_merge($params, ['page' => $totalPages]);
        $lastUrl = $baseUrl . '?' . http_build_query($lastParams);
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $lastUrl . '">' . $totalPages . '</a></li>';
    }
    
    // Next button
    if ($currentPage < $totalPages) {
        $nextParams = array_merge($params, ['page' => $currentPage + 1]);
        $nextUrl = $baseUrl . '?' . http_build_query($nextParams);
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $nextUrl . '">Next</a></li>';
    }
    
    $pagination .= '</ul></nav>';
    return $pagination;
}

/**
 * Upload file
 */
function uploadFile($file, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'], $maxSize = MAX_UPLOAD_SIZE) {
    try {
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            return ['success' => false, 'message' => 'No file uploaded'];
        }
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Upload error: ' . $file['error']];
        }
        
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => 'File too large. Maximum size: ' . formatFileSize($maxSize)];
        }
        
        $fileInfo = pathinfo($file['name']);
        $extension = strtolower($fileInfo['extension']);
        
        if (!in_array($extension, $allowedTypes)) {
            return ['success' => false, 'message' => 'File type not allowed. Allowed types: ' . implode(', ', $allowedTypes)];
        }
        
        $fileName = uniqid() . '_' . time() . '.' . $extension;
        $uploadPath = UPLOAD_PATH . $fileName;
        
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return [
                'success' => true,
                'filename' => $fileName,
                'original_name' => $file['name'],
                'size' => $file['size'],
                'path' => $uploadPath
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to move uploaded file'];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Upload failed: ' . $e->getMessage()];
    }
}

/**
 * Delete file
 */
function deleteFile($filename) {
    $filePath = UPLOAD_PATH . $filename;
    if (file_exists($filePath)) {
        return unlink($filePath);
    }
    return true;
}

/**
 * Send JSON response
 */
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Log error
 */
function logError($message, $context = []) {
    $logMessage = date('Y-m-d H:i:s') . ' - ' . $message;
    if (!empty($context)) {
        $logMessage .= ' - Context: ' . json_encode($context);
    }
    error_log($logMessage);
}

/**
 * Generate breadcrumb
 */
function generateBreadcrumb($items) {
    $breadcrumb = '<nav aria-label="breadcrumb"><ol class="breadcrumb">';
    
    $count = count($items);
    foreach ($items as $index => $item) {
        if ($index === $count - 1) {
            // Last item (current page)
            $breadcrumb .= '<li class="breadcrumb-item active" aria-current="page">' . htmlspecialchars($item['title']) . '</li>';
        } else {
            // Other items
            if (isset($item['url'])) {
                $breadcrumb .= '<li class="breadcrumb-item"><a href="' . htmlspecialchars($item['url']) . '">' . htmlspecialchars($item['title']) . '</a></li>';
            } else {
                $breadcrumb .= '<li class="breadcrumb-item">' . htmlspecialchars($item['title']) . '</li>';
            }
        }
    }
    
    $breadcrumb .= '</ol></nav>';
    return $breadcrumb;
}

/**
 * Get system setting
 */
function getSystemSetting($key, $default = null) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT setting_value, setting_type FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $setting = $stmt->fetch();
        
        if (!$setting) {
            return $default;
        }
        
        $value = $setting['setting_value'];
        
        // Convert based on type
        switch ($setting['setting_type']) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'number':
                return is_numeric($value) ? (float)$value : $default;
            case 'json':
                return json_decode($value, true) ?: $default;
            default:
                return $value;
        }
    } catch (Exception $e) {
        logError("Failed to get system setting: $key", ['error' => $e->getMessage()]);
        return $default;
    }
}

/**
 * Set system setting
 */
function setSystemSetting($key, $value, $type = 'string') {
    try {
        $db = getDB();
        
        // Convert value based on type
        switch ($type) {
            case 'boolean':
                $value = $value ? 'true' : 'false';
                break;
            case 'json':
                $value = json_encode($value);
                break;
            default:
                $value = (string)$value;
        }
        
        $stmt = $db->prepare("
            INSERT INTO system_settings (setting_key, setting_value, setting_type) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = ?, setting_type = ?, updated_at = NOW()
        ");
        $stmt->execute([$key, $value, $type, $value, $type]);
        
        return true;
    } catch (Exception $e) {
        logError("Failed to set system setting: $key", ['error' => $e->getMessage()]);
        return false;
    }
}

/**
 * Generate secure token
 */
function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Validate CSRF token
 */
function validateCSRF($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get user avatar URL
 */
function getUserAvatar($profilePicture = null, $email = null) {
    if ($profilePicture && file_exists(UPLOAD_PATH . $profilePicture)) {
        return BASE_URL . 'uploads/' . $profilePicture;
    }
    
    // Fallback to Gravatar
    if ($email) {
        $hash = md5(strtolower(trim($email)));
        return "https://www.gravatar.com/avatar/$hash?d=identicon&s=150";
    }
    
    return BASE_URL . 'assets/images/default-avatar.png';
}

/**
 * Export data to CSV
 */
function exportToCSV($data, $filename, $headers = []) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Add headers if provided
    if (!empty($headers)) {
        fputcsv($output, $headers);
    } elseif (!empty($data)) {
        // Use first row keys as headers
        fputcsv($output, array_keys($data[0]));
    }
    
    // Add data rows
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}
?>
