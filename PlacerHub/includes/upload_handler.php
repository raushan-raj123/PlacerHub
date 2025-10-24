<?php
require_once '../config/config.php';

class FileUploadHandler {
    private $allowed_types = [
        'resume' => ['pdf', 'doc', 'docx'],
        'photo' => ['jpg', 'jpeg', 'png', 'gif'],
        'document' => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']
    ];
    
    private $max_sizes = [
        'resume' => 5242880, // 5MB
        'photo' => 2097152,  // 2MB
        'document' => 5242880 // 5MB
    ];
    
    public function uploadFile($file, $type, $user_id) {
        // Validate file
        $validation = $this->validateFile($file, $type);
        if ($validation !== true) {
            return ['success' => false, 'message' => $validation];
        }
        
        // Generate unique filename
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = $user_id . '_' . $type . '_' . time() . '.' . $extension;
        
        // Determine upload directory
        $upload_dir = UPLOAD_PATH . $type . 's/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $upload_path = $upload_dir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            return [
                'success' => true,
                'filename' => $filename,
                'path' => $upload_path,
                'url' => SITE_URL . '/uploads/' . $type . 's/' . $filename
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to upload file'];
        }
    }
    
    private function validateFile($file, $type) {
        // Check if file was uploaded
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            return 'No file uploaded';
        }
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return 'File upload error: ' . $this->getUploadErrorMessage($file['error']);
        }
        
        // Check file size
        if ($file['size'] > $this->max_sizes[$type]) {
            $max_size_mb = round($this->max_sizes[$type] / 1024 / 1024, 1);
            return "File size exceeds maximum limit of {$max_size_mb}MB";
        }
        
        // Check file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowed_types[$type])) {
            $allowed = implode(', ', $this->allowed_types[$type]);
            return "Invalid file type. Allowed types: {$allowed}";
        }
        
        // Check if file is actually uploaded
        if (!is_uploaded_file($file['tmp_name'])) {
            return 'Invalid file upload';
        }
        
        return true;
    }
    
    private function getUploadErrorMessage($error_code) {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'File size too large';
            case UPLOAD_ERR_PARTIAL:
                return 'File upload incomplete';
            case UPLOAD_ERR_NO_FILE:
                return 'No file uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file';
            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by extension';
            default:
                return 'Unknown upload error';
        }
    }
    
    public function deleteFile($filename, $type) {
        $file_path = UPLOAD_PATH . $type . 's/' . $filename;
        if (file_exists($file_path)) {
            return unlink($file_path);
        }
        return true; // File doesn't exist, consider it deleted
    }
}
?>
