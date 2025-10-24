<?php
// Database installation script for PlacerHub
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'placerhub';

try {
    // Connect to MySQL server
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>PlacerHub Database Installation</h2>";
    echo "<p>Starting installation...</p>";
    
    // Read and execute SQL file
    $sql = file_get_contents('../database/schema.sql');
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    echo "<div style='color: green; padding: 10px; border: 1px solid green; background: #f0fff0; margin: 10px 0;'>";
    echo "<strong>✓ Installation completed successfully!</strong><br>";
    echo "Database '$database' has been created with all required tables.<br>";
    echo "Default admin credentials:<br>";
    echo "Email: admin@placerhub.com<br>";
    echo "Password: password (please change after first login)";
    echo "</div>";
    
    echo "<p><a href='../index.php' style='background: #4f46e5; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to PlacerHub</a></p>";
    
} catch (PDOException $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red; background: #fff0f0; margin: 10px 0;'>";
    echo "<strong>✗ Installation failed!</strong><br>";
    echo "Error: " . $e->getMessage();
    echo "</div>";
}
?>
