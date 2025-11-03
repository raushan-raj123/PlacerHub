<?php
require_once '../config/config.php';
requireLogin();

header('Content-Type: application/json');

$auth = new Auth();
$user = $auth->getCurrentUser();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!validateCSRFToken($input['csrf_token'] ?? '')) {
    sendJsonResponse(['success' => false, 'message' => 'Invalid CSRF token'], 403);
}

$studentId = intval($input['id'] ?? 0);

if ($studentId <= 0) {
    sendJsonResponse(['success' => false, 'message' => 'Invalid student ID']);
}

try {
    // Get student details before deletion for logging
    $stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$studentId]);
    $student = $stmt->fetch();
    
    if (!$student) {
        sendJsonResponse(['success' => false, 'message' => 'Student not found']);
    }
    
    // Delete the student
    $stmt = $db->prepare("DELETE FROM students WHERE id = ?");
    $stmt->execute([$studentId]);
    
    if ($stmt->rowCount() > 0) {
        // Log activity
        $stmt = $db->prepare("
            INSERT INTO activity_logs (user_id, action, table_name, record_id, old_values, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user['id'],
            'student_deleted',
            'students',
            $studentId,
            json_encode($student),
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        
        // Create notification
        $stmt = $db->prepare("
            INSERT INTO notifications (user_id, title, message, type) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $user['id'],
            'Student Deleted',
            "Student '{$student['name']}' has been deleted from the system.",
            'warning'
        ]);
        
        sendJsonResponse(['success' => true, 'message' => 'Student deleted successfully']);
    } else {
        sendJsonResponse(['success' => false, 'message' => 'Failed to delete student']);
    }
    
} catch (Exception $e) {
    sendJsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
