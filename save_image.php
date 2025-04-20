<?php
// Start session
session_start();

// Include database connection
include '__dbconnection.php';

// Check if user registration data exists in session
if (!isset($_SESSION['signup_data'])) {
    // If no session data, redirect to registration page
    $_SESSION['error'] = "Registration session expired. Please try again.";
    header("Location: index.php");
    exit;
}

// Check if the form was submitted via POST with image data
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['image_data'])) {
    // Get image data
    $image_data = $_POST['image_data'];
    
    // Get redirect URL if provided
    $redirect_url = isset($_POST['redirect_url']) ? $_POST['redirect_url'] : '';
    
    // Check if we should open in a new tab
    $open_new_tab = isset($_POST['open_new_tab']) && $_POST['open_new_tab'] === 'true';
    
    // Get user data from session
    $user_data = $_SESSION['signup_data'];
    $full_name = $user_data['full_name'];
    $email = $user_data['email'];
    $password = $user_data['password'];
    $role = $user_data['role'];
    
    // Process image data (remove the Data URL prefix)
    $image_data = str_replace('data:image/png;base64,', '', $image_data);
    $image_data = str_replace(' ', '+', $image_data);
    $image_binary = base64_decode($image_data);
    
    // Create images directory if it doesn't exist
    $upload_dir = 'uploads/faces/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Generate unique filename for the image
    $image_filename = 'face_' . time() . '_' . md5($email) . '.png';
    $image_path = $upload_dir . $image_filename;
    
    // Save image to file
    $file_saved = file_put_contents($image_path, $image_binary);
    
    if (!$file_saved) {
        $_SESSION['error'] = "Failed to save image. Please try again.";
        header("Location: index.php");
        exit;
    }
    
    // Hash password for security
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        // Insert user with image path instead of binary data
        $insert_sql = "INSERT INTO employees (name, email, password, role, face_image) VALUES (?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("sssss", $full_name, $email, $hashed_password, $role, $image_path);
        $insert_stmt->execute();
        
        // Clear signup data from session
        unset($_SESSION['signup_data']);
        
        // Set success message
        $_SESSION['success'] = "Registration successful! You can now login with your credentials.";
        
        // If we need to open in a new tab and have a redirect URL, use JavaScript
        if ($open_new_tab && !empty($redirect_url)) {
            // Append the email as a query parameter to the redirect URL
            $redirect_url_with_email = $redirect_url . "?email=" . urlencode($email);
            
            // Output HTML with JavaScript to open a new tab and then redirect current page
            echo "<!DOCTYPE html>
                <html>
                <head>
                    <title>Registration Complete</title>
                    <style>
                        body {
                            font-family: Arial, sans-serif;
                            text-align: center;
                            margin-top: 100px;
                        }
                        .success-icon {
                            color: #10b981;
                            font-size: 48px;
                            margin-bottom: 20px;
                        }
                        .button {
                            background-color: #4f46e5;
                            color: white;
                            border: none;
                            padding: 10px 20px;
                            font-size: 16px;
                            border-radius: 4px;
                            cursor: pointer;
                            margin-top: 20px;
                        }
                        .auto-redirect {
                            margin-top: 20px;
                            color: #666;
                            font-size: 14px;
                        }
                    </style>
                    <link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css\">
                </head>
                <body>
                    <div class=\"success-icon\">
                        <i class=\"fas fa-check-circle\"></i>
                    </div>
                    <h1>Registration Complete!</h1>
                    <p>Your account has been successfully created.</p>
                    <p>Now we need to capture additional face images for recognition.</p>
                    <button id=\"openFlaskApp\" class=\"button\">
                        <i class=\"fas fa-camera\"></i> Continue with Face Capture
                    </button>
                    <p class=\"auto-redirect\">Redirecting to Face Capture in <span id=\"countdown\">5</span> seconds...</p>
                    
                    <script>
                        // Set up the countdown timer
                        let seconds = 5;
                        const countdownElement = document.getElementById('countdown');
                        const countdown = setInterval(function() {
                            seconds--;
                            countdownElement.textContent = seconds;
                            if (seconds <= 0) {
                                clearInterval(countdown);
                                // Redirect to the Flask app instead of index.php
                                window.location.href = '" . $redirect_url_with_email . "';
                            }
                        }, 1000);
                        
                        // Set up button click handler
                        document.getElementById('openFlaskApp').addEventListener('click', function() {
                            window.open('" . $redirect_url_with_email . "', '_blank');
                            clearInterval(countdown);
                            // Redirect to index.php after clicking button
                            window.location.href = 'index.php';
                        });
                        
                        // Also try to open the window automatically, but this might be blocked
                        setTimeout(function() {
                            const newWindow = window.open('" . $redirect_url_with_email . "', '_blank');
                            // If popup was blocked, we'll wait for the countdown or button click
                            if (!newWindow || newWindow.closed || typeof newWindow.closed=='undefined') {
                                document.getElementById('openFlaskApp').style.animation = 'pulse 1s infinite';
                                // Update button text to make it clearer
                                document.getElementById('openFlaskApp').innerHTML = '<i class=\"fas fa-camera\"></i> Click here for Face Capture';
                            } else {
                                // If popup opened successfully, redirect to index.php
                                clearInterval(countdown);
                                window.location.href = 'index.php';
                            }
                        }, 500);
                    </script>
                </body>
                </html>";
            exit;
        } else if (!empty($redirect_url)) {
            // Regular redirect in the same tab
            $redirect_url_with_email = $redirect_url . "?email=" . urlencode($email);
            header("Location: " . $redirect_url_with_email);
            exit;
        } else {
            // No redirect URL provided, go back to index
            header("Location: index.php");
            exit;
        }
    } catch (Exception $e) {
        // Remove image file if database insert failed
        if (file_exists($image_path)) {
            unlink($image_path);
        }
        
        // Set error message
        $_SESSION['error'] = "Registration failed: " . $e->getMessage();
        
        // Redirect back to registration page
        header("Location: index.php");
        exit;
    }
} else {
    // If not a POST request or no image data, redirect to registration page
    $_SESSION['error'] = "No image data received. Please try again.";
    header("Location: index.php");
    exit;
}
?> 