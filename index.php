<?php
// Start session at the very beginning of the file
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Attendance System</title>
    <link rel="stylesheet" href="static/app-styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4f46e5;
            --primary-dark: #4338ca;
            --primary-light: #818cf8;
            --primary-bg: #eef2ff;
            --secondary-color: #7e22ce;
            --secondary-dark: #6b21a8;
            --accent-color: #14b8a6;
            --text-dark: #1f2937;
            --text-medium: #4b5563;
            --text-light: #9ca3af;
            --bg-white: #ffffff;
            --bg-light: #f9fafb;
            --bg-dark: #111827;
            --danger: #ef4444;
            --warning: #f59e0b;
            --success: #10b981;
            --border-color: #e5e7eb;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --radius-sm: 0.25rem;
            --radius: 0.5rem;
            --radius-lg: 1rem;
            --radius-full: 9999px;
            --header-height: 70px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background: var(--bg-light);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .app-header {
            height: var(--header-height);
            background: var(--bg-white);
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 100;
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logo-icon {
            width: 2.5rem;
            height: 2.5rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: var(--radius-full);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
        }

        .nav-links {
            display: flex;
            gap: 1.5rem;
        }

        .nav-link {
            color: var(--text-medium);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            transition: color 0.2s;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .nav-link:hover {
            color: var(--primary-color);
        }

        .nav-link.active {
            color: var(--primary-color);
        }

        .auth-container {
            padding-top: var(--header-height);
            min-height: 100vh;
            display: flex;
            width: 100%;
        }

        .auth-image {
            flex: 1.2;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }

        .auth-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxkZWZzPjxwYXR0ZXJuIGlkPSJwYXR0ZXJuIiB4PSIwIiB5PSIwIiB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHBhdHRlcm5Vbml0cz0idXNlclNwYWNlT25Vc2UiIHBhdHRlcm5UcmFuc2Zvcm09InJvdGF0ZSgzMCkiPjxyZWN0IHg9IjAiIHk9IjAiIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCIgZmlsbD0icmdiYSgyNTUsMjU1LDI1NSwwLjA1KSI+PC9yZWN0PjwvcGF0dGVybj48L2RlZnM+PHJlY3QgeD0iMCIgeT0iMCIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNwYXR0ZXJuKSI+PC9yZWN0Pjwvc3ZnPg==');
            opacity: 0.8;
        }

        .auth-content {
            z-index: 10;
            max-width: 500px;
            text-align: center;
        }

        .auth-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .auth-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .auth-features {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            text-align: left;
            margin-top: 2rem;
        }

        .feature {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .feature-icon {
            width: 2.5rem;
            height: 2.5rem;
            background: rgba(255, 255, 255, 0.2);
            border-radius: var(--radius-full);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .feature-text {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .auth-form {
            flex: 0.8;
            padding: 3rem 4rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background-color: var(--bg-white);
        }

        .form-header {
            margin-bottom: 2rem;
            text-align: center;
        }

        .form-title {
            font-size: 1.75rem;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .form-subtitle {
            font-size: 0.9rem;
            color: var(--text-medium);
        }

        .tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            border-bottom: 1px solid var(--border-color);
        }

        .tab {
            padding: 0.75rem 1rem;
            font-weight: 500;
            color: var(--text-medium);
            cursor: pointer;
            position: relative;
            transition: color 0.2s;
        }

        .tab:hover {
            color: var(--primary-color);
        }

        .tab.active {
            color: var(--primary-color);
        }

        .tab.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 2px;
            background: var(--primary-color);
        }

        .form-container {
            display: none;
        }

        .form-container.active {
            display: block;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .form-control {
            display: block;
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            line-height: 1.5;
            color: var(--text-dark);
            background-color: var(--bg-white);
            background-clip: padding-box;
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-control:focus {
            border-color: var(--primary-light);
            outline: 0;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .input-group {
            position: relative;
        }

        .input-group .form-control {
            padding-left: 3rem;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
        }

        .btn {
            display: inline-block;
            font-weight: 500;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            user-select: none;
            border: 1px solid transparent;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            line-height: 1.5;
            border-radius: var(--radius);
            transition: all 0.2s;
            cursor: pointer;
        }

        .btn-primary {
            color: white;
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        .btn-lg {
            padding: 1rem 2rem;
            font-size: 1.125rem;
            border-radius: var(--radius);
        }

        .btn-block {
            display: block;
            width: 100%;
        }

        .divider {
            display: flex;
            align-items: center;
            color: var(--text-light);
            font-size: 0.875rem;
            margin: 1.5rem 0;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background-color: var(--border-color);
        }

        .divider::before {
            margin-right: 1rem;
        }

        .divider::after {
            margin-left: 1rem;
        }

        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: var(--radius);
            font-size: 0.9rem;
        }

        .alert-success {
            color: var(--success);
            background-color: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .alert-danger {
            color: var(--danger);
            background-color: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .loading {
            position: relative;
            pointer-events: none;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 1.5rem;
            height: 1.5rem;
            margin-left: -0.75rem;
            margin-top: -0.75rem;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Media queries for responsive design */
        @media (max-width: 992px) {
            .auth-container {
                flex-direction: column;
            }
            .auth-image {
                display: none;
            }
            .auth-form {
                padding: 2rem;
            }
        }

        @media (max-width: 768px) {
            .app-header {
                padding: 0 1rem;
            }
            .logo-text {
                display: none;
            }
            .auth-form {
                padding: 1.5rem;
            }
        }

        @media (max-width: 576px) {
            .nav-link span {
                display: none;
            }
            .nav-link {
                font-size: 1.25rem;
            }
            .tabs {
                gap: 0;
            }
            .tab {
                flex: 1;
                text-align: center;
                padding: 0.75rem 0;
            }
        }

        /* Animation for initial load */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.5s ease-out forwards;
        }
    </style>
</head>
<body>
    <header class="app-header">
        <div class="logo-container">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-id-card"></i>
                </div>
                <span class="logo-text">AttendX</span>
            </div>
        </div>
        <nav class="nav-links">
            <a href="#" class="nav-link active">
                <i class="fas fa-info-circle"></i>
                <span>About</span>
            </a>
            <a href="#" class="nav-link">
                <i class="fas fa-question-circle"></i>
                <span>Help</span>
            </a>
        </nav>
    </header>

    <main class="auth-container">
        <div class="auth-image">
            <div class="auth-overlay"></div>
            <div class="auth-content">
                <h1 class="auth-title">Employee Attendance System</h1>
                <p class="auth-subtitle">A comprehensive solution for managing employee attendance, time tracking, and productivity monitoring.</p>
                
                <div class="auth-features">
                    <div class="feature">
                        <div class="feature-icon">
                            <i class="fas fa-camera"></i>
                        </div>
                        <div>
                            <h3>Face Recognition</h3>
                            <p class="feature-text">Secure authentication using advanced facial recognition technology.</p>
                        </div>
                    </div>
                    <div class="feature">
                        <div class="feature-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div>
                            <h3>Real-time Tracking</h3>
                            <p class="feature-text">Monitor attendance and time records in real-time with accurate timestamps.</p>
                        </div>
                    </div>
                    <div class="feature">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div>
                            <h3>Detailed Analytics</h3>
                            <p class="feature-text">Generate comprehensive reports and analytics on attendance patterns.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="auth-form">
            <div class="form-header">
                <h2 class="form-title">Welcome Back</h2>
                <p class="form-subtitle">Sign in to continue to your dashboard</p>
            </div>
            
            <div id="message-container">
                <?php
                // Display error message if exists
                if (isset($_SESSION['error'])) {
                    echo '<div class="alert alert-danger">';
                    echo '<i class="fas fa-exclamation-circle"></i> ' . $_SESSION['error'];
                    echo '</div>';
                    unset($_SESSION['error']); // Clear the error message
                }
                
                // Display success message if exists
                if (isset($_SESSION['success'])) {
                    echo '<div class="alert alert-success">';
                    echo '<i class="fas fa-check-circle"></i> ' . $_SESSION['success'];
                    echo '</div>';
                    unset($_SESSION['success']); // Clear the success message
                }
                ?>
            </div>
            
            <div class="tabs">
                <div class="tab active" data-tab="login">Login</div>
                <div class="tab" data-tab="register">Sign Up</div>
            </div>
            
            <div id="login-form" class="form-container active">
                <form action="login_process.php" method="post" id="loginFormElement">
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email" id="email" name="email" class="form-control" placeholder="you@example.com" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="login_role" class="form-label">Select Role</label>
                        <div class="input-group">
                            <i class="fas fa-user-shield input-icon"></i>
                            <select id="login_role" name="role" class="form-control" required>
                                <option value="">Select your role</option>
                                <option value="Admin">Admin</option>
                                <option value="Employee">Employee</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group" style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <input type="checkbox" id="remember" name="remember">
                            <label for="remember" style="font-size: 0.875rem; color: var(--text-medium);">Remember me</label>
                        </div>
                        <a href="#" style="font-size: 0.875rem; color: var(--primary-color); text-decoration: none;">Forgot password?</a>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="login" class="btn btn-primary btn-block">Sign In</button>
                    </div>
                </form>
                
                <div class="text-center mt-4">
                    <p style="font-size: 0.875rem; color: var(--text-medium);">
                        Don't have an account? 
                        <a href="#" id="goToRegister" style="color: var(--primary-color); text-decoration: none; font-weight: 500;">
                            Create one now
                        </a>
                    </p>
                </div>
            </div>
            
            <div id="register-form" class="form-container">
                <form action="signup_process.php" method="post" id="registerFormElement">
                    <div class="form-group">
                        <label for="full_name" class="form-label">Full Name</label>
                        <div class="input-group">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" id="full_name" name="full_name" class="form-control" placeholder="John Doe" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="reg_email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email" id="reg_email" name="email" class="form-control" placeholder="you@example.com" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="reg_password" class="form-label">Password</label>
                        <div class="input-group">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" id="reg_password" name="password" class="form-control" placeholder="••••••••" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <div class="input-group">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="••••••••" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="register_role" class="form-label">Select Role</label>
                        <div class="input-group">
                            <i class="fas fa-user-shield input-icon"></i>
                            <select id="register_role" name="role" class="form-control" required>
                                <option value="">Select your role</option>
                                <option value="Admin">Admin</option>
                                <option value="Employee">Employee</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="register" class="btn btn-primary btn-block">Register & Continue</button>
                    </div>
                </form>
                
                <div class="text-center mt-4">
                    <p style="font-size: 0.875rem; color: var(--text-medium);">
                        Already have an account? 
                        <a href="#" id="goToLogin" style="color: var(--primary-color); text-decoration: none; font-weight: 500;">
                            Sign in
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Tab switching functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.tab');
            const goToRegisterLink = document.getElementById('goToRegister');
            const goToLoginLink = document.getElementById('goToLogin');
            const registerForm = document.getElementById('registerFormElement');
            
            // Navigate between forms using links
            goToRegisterLink.addEventListener('click', function(e) {
                e.preventDefault();
                activateTab('register');
            });
            
            goToLoginLink.addEventListener('click', function(e) {
                e.preventDefault();
                activateTab('login');
            });
            
            // Password confirmation check for registration form
            if (registerForm) {
                registerForm.addEventListener('submit', function(e) {
                    const password = document.getElementById('reg_password').value;
                    const confirmPassword = document.getElementById('confirm_password').value;
                    
                    if (password !== confirmPassword) {
                        e.preventDefault();
                        alert('Passwords do not match!');
                        return false;
                    }
                    
                    const role = document.getElementById('register_role').value;
                    if (!role) {
                        e.preventDefault();
                        alert('Please select a role to continue.');
                        return false;
                    }
                    
                    // Form will submit normally if validations pass
                    return true;
                });
            }
            
            function activateTab(tabName) {
                // Update tabs
                tabs.forEach(t => {
                    if(t.getAttribute('data-tab') === tabName) {
                        t.classList.add('active');
                    } else {
                        t.classList.remove('active');
                    }
                });
                
                // Update form containers
                document.querySelectorAll('.form-container').forEach(form => {
                    form.classList.remove('active');
                });
                document.getElementById(tabName + '-form').classList.add('active');
                
                // Update form header
                const formTitle = document.querySelector('.form-title');
                const formSubtitle = document.querySelector('.form-subtitle');
                
                if (tabName === 'login') {
                    formTitle.textContent = 'Welcome Back';
                    formSubtitle.textContent = 'Sign in to continue to your dashboard';
                } else {
                    formTitle.textContent = 'Create an Account';
                    formSubtitle.textContent = 'Join us to start managing attendance';
                }
            }
            
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    activateTab(this.getAttribute('data-tab'));
                });
            });
        });
    </script>
</body>
</html>
