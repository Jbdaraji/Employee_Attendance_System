<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

include '__dbconnection.php';

// Get total employees count
$sql = "SELECT COUNT(*) as total FROM employees";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$totalEmployees = $row['total'];

// Get today's attendance count (Present)
$today = date('Y-m-d');
$sql = "SELECT COUNT(*) as present FROM attendance WHERE DATE(timestamp) = ? AND status = 'Present'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$presentToday = $row['present'];

// Get today's absent count
$sql = "SELECT COUNT(*) as absent FROM attendance WHERE DATE(timestamp) = ? AND status = 'Absent'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$absentToday = $row['absent'];

// Get total cameras
$sql = "SELECT COUNT(*) as total FROM locations";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$totalCameras = $row['total'];

// Get recent activity
$sql = "SELECT a.*, e.name, e.face_image 
        FROM attendance a 
        JOIN employees e ON a.id = e.id 
        ORDER BY a.timestamp DESC LIMIT 5";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Face Attendance System</title>
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
                <a href="admin_dashboard.php" class="sidebar-nav-link active">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="employee_registration.php" class="sidebar-nav-link">
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
            <h1 class="h3">Dashboard Overview</h1>
            <div class="d-flex align-items-center gap-3">
                <button class="dark-mode-toggle">
                    <i class="fas fa-moon"></i>
                </button>
                <div class="avatar">
                    <img src="https://ui-avatars.com/api/?name=Admin&background=4361ee&color=fff" alt="Admin">
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-info">
                    <p>Total Employees</p>
                    <h3><?php echo $totalEmployees; ?></h3>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <p>Present Today</p>
                    <h3><?php echo $presentToday; ?></h3>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <p>Absent Today</p>
                    <h3><?php echo $absentToday; ?></h3>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <p>Total Cameras</p>
                    <h3><?php echo $totalCameras; ?></h3>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-camera"></i>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Activity</h5>
                <button class="btn btn-outline-primary btn-sm" onclick="location.reload()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Location</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $result->fetch_assoc()): ?>
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
                                <td><?php echo date('h:i A', strtotime($row['timestamp'])); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $row['status'] == 'Present' ? 'success' : 'danger'; ?>">
                                        <?php echo $row['status']; ?>
                                    </span>
                                </td>
                                <td>
                    <?php
                                        $loc_sql = "SELECT name FROM locations WHERE id = ?";
                                        $loc_stmt = $conn->prepare($loc_sql);
                                        $loc_stmt->bind_param("i", $row['location_id']);
                                        $loc_stmt->execute();
                                        $loc_result = $loc_stmt->get_result();
                                        $location = $loc_result->fetch_assoc();
                                        echo $location ? $location['name'] : 'N/A';
                                    ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-2">
                            <button class="btn btn-primary" onclick="location.href='employee_registration.php'">
                                <i class="fas fa-user-plus"></i> Add Employee
                            </button>
                            <button class="btn btn-success" onclick="location.href='mark_attendance.php'">
                                <i class="fas fa-camera"></i> Start Attendance
                            </button>
                            <button class="btn btn-info" onclick="location.href='export_report.php'">
                                <i class="fas fa-file-export"></i> Export Report
                            </button>
                            <button class="btn btn-warning" onclick="location.href='settings.php'">
                                <i class="fas fa-cog"></i> Settings
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">System Status</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-column gap-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Face Recognition System</span>
                                <span class="badge badge-success">Active</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Database Connection</span>
                                <span class="badge badge-success">Connected</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Camera Status</span>
                                <span class="badge badge-success">Online</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span>System Updates</span>
                                <span class="badge badge-warning">Available</span>
                            </div>
                        </div>
                    </div>
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
    </script>
</body>
</html>
