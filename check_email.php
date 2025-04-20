<?php
// Include database connection
include '__dbconnection.php';

// Get email parameter from request
$email = isset($_GET['email']) ? $_GET['email'] : '';
$response = ['exists' => false];

if (!empty($email)) {
    // Prepare SQL statement to prevent SQL injection
    $sql = "SELECT COUNT(*) as count FROM employees WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    // Set response based on query result
    $response['exists'] = ($row['count'] > 0);
}

// Set appropriate content type header
header('Content-Type: application/json');

// Output JSON response
echo json_encode($response);
?> 