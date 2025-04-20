<?php
// Start session
session_start();

// Include database connection
include '__dbconnection.php';

// Check if the form was submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $full_name = isset($_POST['full_name']) ? $_POST['full_name'] : '';
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    $role = isset($_POST['role']) ? $_POST['role'] : '';
    
    // Validate data
    if (empty($full_name) || empty($email) || empty($password) || empty($confirm_password) || empty($role)) {
        $_SESSION['error'] = "All fields are required";
        header("Location: index.php");
        exit;
    }
    
    // Check if passwords match
    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match";
        header("Location: index.php");
        exit;
    }
    
    // Check if email already exists
    $check_sql = "SELECT COUNT(*) as count FROM employees WHERE email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        $_SESSION['error'] = "Email already exists. Please use a different email or login.";
        header("Location: index.php");
        exit;
    }
    
    // Store data in session for use in capture_image.php
    $_SESSION['signup_data'] = [
        'full_name' => $full_name,
        'email' => $email,
        'password' => $password, // Will be hashed before final storage
        'role' => $role
    ];
    
    // Redirect to face capture page
    header("Location: capture_image.php");
    exit;
} else {
    // If not a POST request, redirect to signup page
    header("Location: index.php");
    exit;
}
?> 