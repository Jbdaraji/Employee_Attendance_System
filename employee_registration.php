<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

include '__dbconnection.php';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $designation = mysqli_real_escape_string($conn, $_POST['designation']);
    $department = mysqli_real_escape_string($conn, $_POST['department']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = 'Employee'; // Default role for new registrations

    // Check if email already exists
    $check_sql = "SELECT id FROM employees WHERE email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $_SESSION['message'] = "Email already exists!";
        $_SESSION['message_type'] = "danger";
    } else {
        // Insert new employee
        $sql = "INSERT INTO employees (name, email, password, role, phone_number, designation, department) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssss", $name, $email, $password, $role, $phone, $designation, $department);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Employee registered successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error registering employee: " . $conn->error;
            $_SESSION['message_type'] = "danger";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Registration - Face Attendance System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="static/modern-style.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-user-shield"></i>
            <span>Admin Panel</span>
        </div>
        <ul class="sidebar-nav">
            <li class="sidebar-nav-item">
                <a href="admin_dashboard.php" class="sidebar-nav-link">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="employee_registration.php" class="sidebar-nav-link active">
                    <i class="fas fa-user-plus"></i>
                    <span>Employee Registration</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="manage_employees.php" class="sidebar-nav-link">
                    <i class="fas fa-users"></i>
                    <span>Manage Employees</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="mark_attendance.php" class="sidebar-nav-link">
                    <i class="fas fa-calendar-check"></i>
                    <span>Mark Attendance</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="attendance_details.php" class="sidebar-nav-link">
                    <i class="fas fa-list"></i>
                    <span>Attendance Details</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="manage_cameras.php" class="sidebar-nav-link">
                    <i class="fas fa-camera"></i>
                    <span>Manage Cameras</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="settings.php" class="sidebar-nav-link">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="logout.php" class="sidebar-nav-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3">Employee Registration</h1>
            <div class="d-flex align-items-center gap-3">
                <button class="dark-mode-toggle">
                    <i class="fas fa-moon"></i>
                </button>
                <div class="avatar">
                    <img src="https://ui-avatars.com/api/?name=Admin&background=4361ee&color=fff" alt="Admin">
                </div>
            </div>
        </div>

        <?php if(isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php 
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
        endif; 
        ?>

        <div class="card">
            <div class="card-body p-4">
                <form action="" method="POST" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone Number</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                <input type="tel" class="form-control" name="phone" required>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" name="password" required>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Designation</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-briefcase"></i></span>
                                <input type="text" class="form-control" name="designation" required>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Department</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-building"></i></span>
                                <input type="text" class="form-control" name="department" required>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Register Employee
                        </button>
                        <button type="reset" class="btn btn-secondary">
                            <i class="fas fa-undo me-2"></i>Reset Form
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Recently Added Employees -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">Recently Added Employees</h5>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Designation</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT * FROM employees ORDER BY id DESC LIMIT 5";
                            $result = $conn->query($sql);
                            while($row = $result->fetch_assoc()):
                            ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <?php if($row['face_image']): ?>
                                            <img src="data:image/jpeg;base64,<?php echo base64_encode($row['face_image']); ?>" 
                                                 alt="<?php echo $row['name']; ?>" 
                                                 class="rounded-circle" 
                                                 style="width: 32px; height: 32px; object-fit: cover;">
                                        <?php else: ?>
                                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($row['name']); ?>&background=4361ee&color=fff" 
                                                 alt="<?php echo $row['name']; ?>" 
                                                 class="rounded-circle" 
                                                 style="width: 32px; height: 32px;">
                                        <?php endif; ?>
                                        <span><?php echo $row['name']; ?></span>
                                    </div>
                                </td>
                                <td><?php echo $row['email']; ?></td>
                                <td><?php echo $row['department']; ?></td>
                                <td><?php echo $row['designation']; ?></td>
                                <td>
                                    <span class="badge badge-success">Active</span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Dark mode toggle
        document.querySelector('.dark-mode-toggle').addEventListener('click', function() {
            document.body.classList.toggle('dark-mode');
        });

        // Form validation
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        form.classList.add('was-validated')
                    }, false)
                })
        })()
    </script>
</body>
</html> 