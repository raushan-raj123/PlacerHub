<?php
require_once '../config/config.php';
requireLogin();

$auth = new Auth();
$user = $auth->getCurrentUser();
$db = getDB();

// Handle search and filters (same as index.php)
$search = sanitizeInput($_GET['search'] ?? '');
$courseFilter = sanitizeInput($_GET['course'] ?? '');
$departmentFilter = sanitizeInput($_GET['department'] ?? '');
$statusFilter = sanitizeInput($_GET['status'] ?? '');
$format = sanitizeInput($_GET['format'] ?? 'csv');

// Build query
$whereConditions = [];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(name LIKE ? OR student_id LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($courseFilter)) {
    $whereConditions[] = "course = ?";
    $params[] = $courseFilter;
}

if (!empty($departmentFilter)) {
    $whereConditions[] = "department = ?";
    $params[] = $departmentFilter;
}

if (!empty($statusFilter)) {
    $whereConditions[] = "status = ?";
    $params[] = $statusFilter;
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Get students
$query = "SELECT student_id, name, email, phone, course, department, year_of_study, admission_date, status, address, created_at FROM students $whereClause ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll();

// Log export activity
$stmt = $db->prepare("
    INSERT INTO activity_logs (user_id, action, table_name, ip_address, user_agent) 
    VALUES (?, ?, ?, ?, ?)
");
$stmt->execute([
    $user['id'],
    'students_exported',
    'students',
    $_SERVER['REMOTE_ADDR'] ?? '',
    $_SERVER['HTTP_USER_AGENT'] ?? ''
]);

// Export based on format
switch ($format) {
    case 'csv':
        exportToCSV($students, 'students_' . date('Y-m-d_H-i-s') . '.csv', [
            'Student ID', 'Name', 'Email', 'Phone', 'Course', 'Department', 
            'Year of Study', 'Admission Date', 'Status', 'Address', 'Created At'
        ]);
        break;
        
    case 'excel':
        // For Excel export, we'll use CSV format but with .xlsx extension
        // In a real application, you might use PHPSpreadsheet library
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="students_' . date('Y-m-d_H-i-s') . '.xlsx"');
        
        $output = fopen('php://output', 'w');
        
        // Add headers
        fputcsv($output, [
            'Student ID', 'Name', 'Email', 'Phone', 'Course', 'Department', 
            'Year of Study', 'Admission Date', 'Status', 'Address', 'Created At'
        ]);
        
        // Add data rows
        foreach ($students as $student) {
            fputcsv($output, [
                $student['student_id'],
                $student['name'],
                $student['email'],
                $student['phone'],
                $student['course'],
                $student['department'],
                'Year ' . $student['year_of_study'],
                formatDate($student['admission_date']),
                ucfirst($student['status']),
                $student['address'],
                formatDateTime($student['created_at'])
            ]);
        }
        
        fclose($output);
        exit;
        
    case 'pdf':
        // For PDF export, we'll create a simple HTML to PDF conversion
        // In a real application, you might use libraries like TCPDF or DomPDF
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="students_' . date('Y-m-d_H-i-s') . '.pdf"');
        
        // Simple HTML for PDF conversion (this is a basic implementation)
        echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Students Report</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .header { text-align: center; margin-bottom: 20px; }
        .meta { margin-bottom: 10px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Students Report</h1>
        <div class="meta">Generated on: ' . date('F j, Y, g:i a') . '</div>
        <div class="meta">Total Records: ' . count($students) . '</div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Student ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Course</th>
                <th>Department</th>
                <th>Year</th>
                <th>Status</th>
                <th>Admission Date</th>
            </tr>
        </thead>
        <tbody>';
        
        foreach ($students as $student) {
            echo '<tr>
                <td>' . htmlspecialchars($student['student_id']) . '</td>
                <td>' . htmlspecialchars($student['name']) . '</td>
                <td>' . htmlspecialchars($student['email']) . '</td>
                <td>' . htmlspecialchars($student['course']) . '</td>
                <td>' . htmlspecialchars($student['department']) . '</td>
                <td>Year ' . $student['year_of_study'] . '</td>
                <td>' . ucfirst($student['status']) . '</td>
                <td>' . formatDate($student['admission_date']) . '</td>
            </tr>';
        }
        
        echo '</tbody>
    </table>
</body>
</html>';
        exit;
        
    default:
        // Default to CSV
        exportToCSV($students, 'students_' . date('Y-m-d_H-i-s') . '.csv', [
            'Student ID', 'Name', 'Email', 'Phone', 'Course', 'Department', 
            'Year of Study', 'Admission Date', 'Status', 'Address', 'Created At'
        ]);
}
?>
