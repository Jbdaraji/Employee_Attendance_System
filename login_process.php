<?php
// Start session
session_start();

// Include database connection
include '__dbconnection.php';

// Check if the form was submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $role = isset($_POST['role']) ? $_POST['role'] : '';
    
    // Validate data
    if (empty($email) || empty($password) || empty($role)) {
        $_SESSION['error'] = "All fields are required";
        header("Location: index.php");
        exit;
    }
    
    // Prepare SQL statement to prevent SQL injection
    $sql = "SELECT * FROM employees WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // For debugging - log user data
        error_log("User found: " . print_r($user, true));
        
        // Verify password (assuming you're using password_hash for storage)
        if (password_verify($password, $user['password'])) {
            // Check if role matches
            if ($user['role'] == $role) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['logged_in'] = true;
                
                // Log role for debugging
                error_log("User role: " . $role);
                
                // Redirect based on role
                if ($role == 'Admin') {
                    error_log("Redirecting to admin dashboard");
                    header("Location: admin_dashboard.php");
                    exit;
                } else if ($role == 'Employee') {
                    error_log("Redirecting to employee dashboard");
                    // Use absolute URL to ensure proper redirection
                    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
                    $redirect_url = $base_url . "/EMP_ATTENDANCE/employee_dashboard.php";
                    error_log("Redirect URL: " . $redirect_url);
                    header("Location: " . $redirect_url);
                    exit;
                }
            } else {
                $_SESSION['error'] = "Incorrect role selected";
                header("Location: index.php");
                exit;
            }
        } else {
            $_SESSION['error'] = "Incorrect password";
            header("Location: index.php");
            exit;
        }
    } else {
        $_SESSION['error'] = "User not found";
        header("Location: index.php");
        exit;
    }
} else {
    // If not a POST request, redirect to login page
    header("Location: index.php");
    exit;
}
?> 