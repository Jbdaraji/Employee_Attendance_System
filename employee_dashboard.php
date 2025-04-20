<?php
    session_start();
    include '__dbconnection.php'; // Database connection file

    // Check if user is logged in and is an employee
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'Employee') {
        header("Location: index.php");
        exit;
    }

    // Get current view from URL parameter
    $view = isset($_GET['view']) ? $_GET['view'] : 'dashboard';

    $email = $_SESSION['email'];
    $employee_id = $_SESSION['user_id'];

    // Handle profile update
    if (isset($_POST['update_profile'])) {
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Check if current password is correct
        $check_sql = "SELECT password FROM employees WHERE id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $employee_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $employee = $result->fetch_assoc();
        
        $update_success = false;
        $message = "";
        
        // Update name
        if ($name != $_SESSION['name']) {
            $update_sql = "UPDATE employees SET name = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $name, $employee_id);
            
            if ($update_stmt->execute()) {
                $_SESSION['name'] = $name;
                $update_success = true;
                $message = "Profile updated successfully";
            } else {
                $message = "Error updating profile: " . $conn->error;
            }
        }
        
        // Update password if provided
        if (!empty($current_password) && !empty($new_password) && !empty($confirm_password)) {
            if (!password_verify($current_password, $employee['password'])) {
                $message = "Current password is incorrect";
            } else if ($new_password != $confirm_password) {
                $message = "New passwords do not match";
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $password_sql = "UPDATE employees SET password = ? WHERE id = ?";
                $password_stmt = $conn->prepare($password_sql);
                $password_stmt->bind_param("si", $hashed_password, $employee_id);
                
                if ($password_stmt->execute()) {
                    $update_success = true;
                    $message = "Profile and password updated successfully";
                } else {
                    $message = "Error updating password: " . $conn->error;
                }
            }
        }
        
        $_SESSION['message'] = $message;
        $_SESSION['message_type'] = $update_success ? "success" : "danger";
    }

    // Fetch employee data
    $query = "SELECT e.*, l.name as location_name, l.latitude, l.longitude 
              FROM employees e 
              LEFT JOIN locations l ON e.location_id = l.id 
              WHERE e.email = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $employeeData = $row;
        $assignedLocation = $row['location_name'];
        $assignedLat = $row['latitude'];
        $assignedLon = $row['longitude'];
    } else {
        $assignedLocation = "No assigned location found.";
        $assignedLat = null;
        $assignedLon = null;
    }

    // For attendance history view, fetch attendance data
    $attendanceData = [];
    $monthlyData = [];
    $yearlyData = [];
    
    if ($view == 'history') {
        // Get current year and month for default filters
        $currentYear = date('Y');
        $currentMonth = date('m');
        
        // Get filter values from URL if provided
        $selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : $currentYear;
        $selectedMonth = isset($_GET['month']) ? (int)$_GET['month'] : $currentMonth;
        
        // Fetch monthly attendance data
        $monthly_sql = "SELECT 
                          status, 
                          COUNT(*) as count 
                        FROM 
                          attendance 
                        WHERE 
                          id = ? AND 
                          YEAR(timestamp) = ? AND 
                          MONTH(timestamp) = ?
                        GROUP BY 
                          status";
        
        $monthly_stmt = $conn->prepare($monthly_sql);
        $monthly_stmt->bind_param("iii", $employee_id, $selectedYear, $selectedMonth);
        $monthly_stmt->execute();
        $monthly_result = $monthly_stmt->get_result();
        
        $present_monthly = 0;
        $absent_monthly = 0;
        
        while ($row = $monthly_result->fetch_assoc()) {
            if ($row['status'] == 'Present') {
                $present_monthly = $row['count'];
            } else if ($row['status'] == 'Absent') {
                $absent_monthly = $row['count'];
            }
        }
        
        $monthlyData = [
            'present' => $present_monthly,
            'absent' => $absent_monthly,
            'total' => $present_monthly + $absent_monthly
        ];
        
        // Fetch yearly attendance data
        $yearly_sql = "SELECT 
                         status, 
                         COUNT(*) as count 
                       FROM 
                         attendance 
                       WHERE 
                         id = ? AND 
                         YEAR(timestamp) = ?
                       GROUP BY 
                         status";
        
        $yearly_stmt = $conn->prepare($yearly_sql);
        $yearly_stmt->bind_param("ii", $employee_id, $selectedYear);
        $yearly_stmt->execute();
        $yearly_result = $yearly_stmt->get_result();
        
        $present_yearly = 0;
        $absent_yearly = 0;
        
        while ($row = $yearly_result->fetch_assoc()) {
            if ($row['status'] == 'Present') {
                $present_yearly = $row['count'];
            } else if ($row['status'] == 'Absent') {
                $absent_yearly = $row['count'];
            }
        }
        
        $yearlyData = [
            'present' => $present_yearly,
            'absent' => $absent_yearly,
            'total' => $present_yearly + $absent_yearly
        ];
        
        // Fetch recent attendance records
        $recent_sql = "SELECT 
                         DATE(timestamp) as date, 
                         TIME(timestamp) as time,
                         status
                       FROM 
                         attendance 
                       WHERE 
                         id = ?
                       ORDER BY 
                         timestamp DESC
                       LIMIT 10";
        
        $recent_stmt = $conn->prepare($recent_sql);
        $recent_stmt->bind_param("i", $employee_id);
        $recent_stmt->execute();
        $recent_result = $recent_stmt->get_result();
        
        while ($row = $recent_result->fetch_assoc()) {
            $attendanceData[] = $row;
        }
    }

    mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ucfirst($view); ?> - Face Attendance System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="static/modern-style.css">
    <?php if($view == 'history'): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php endif; ?>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-user-check"></i>
            <span>Employee Portal</span>
        </div>
        <ul class="sidebar-nav">
            <li class="sidebar-nav-item">
                <a href="employee_dashboard.php" class="sidebar-nav-link <?php echo $view == 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="employee_dashboard.php?view=history" class="sidebar-nav-link <?php echo $view == 'history' ? 'active' : ''; ?>">
                    <i class="fas fa-history"></i>
                    <span>Attendance History</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="employee_dashboard.php?view=profile" class="sidebar-nav-link <?php echo $view == 'profile' ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i>
                    <span>My Profile</span>
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
            <h1 class="h3">
                <?php 
                if($view == 'profile') {
                    echo 'My Profile';
                } elseif($view == 'history') {
                    echo 'Attendance History';
                } else {
                    echo 'Welcome, ' . $_SESSION['name'];
                }
                ?>
            </h1>
            <div class="d-flex align-items-center gap-3">
                <button class="dark-mode-toggle">
                    <i class="fas fa-moon"></i>
                </button>
                <div class="avatar">
                    <img src="<?php echo isset($_SESSION['profile_image']) ? $_SESSION['profile_image'] : 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['name']); ?>" alt="Profile">
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

        <?php if($view == 'profile'): ?>
        <!-- Profile Content -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Employee Profile</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 text-center mb-4">
                        <div class="avatar-large mx-auto mb-3">
                            <?php if(isset($employeeData['face_image']) && $employeeData['face_image']): ?>
                                <img src="data:image/jpeg;base64,<?php echo base64_encode($employeeData['face_image']); ?>" 
                                    alt="<?php echo $employeeData['name']; ?>" 
                                    class="rounded-circle img-fluid" 
                                    style="width: 150px; height: 150px; object-fit: cover;">
                            <?php else: ?>
                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($employeeData['name']); ?>&size=150&background=4361ee&color=fff" 
                                    alt="<?php echo $employeeData['name']; ?>" 
                                    class="rounded-circle img-fluid">
                            <?php endif; ?>
                        </div>
                        <h4><?php echo $employeeData['name']; ?></h4>
                        <p class="text-muted mb-2"><?php echo $employeeData['email']; ?></p>
                        <span class="badge <?php echo $employeeData['role'] == 'Admin' ? 'badge-primary' : 'badge-info'; ?>">
                            <?php echo $employeeData['role']; ?>
                        </span>
                    </div>
                    <div class="col-md-8">
                        <form method="POST" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" class="form-control" name="name" value="<?php echo $employeeData['name']; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email Address</label>
                                <input type="email" class="form-control" value="<?php echo $employeeData['email']; ?>" readonly>
                                <small class="text-muted">Email cannot be changed</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Assigned Location</label>
                                <input type="text" class="form-control" value="<?php echo $assignedLocation; ?>" readonly>
                            </div>
                            <hr>
                            <h5>Change Password (Optional)</h5>
                            <div class="mb-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" class="form-control" name="current_password">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" class="form-control" name="new_password">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" name="confirm_password">
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php elseif($view == 'history'): ?>
        <!-- Attendance History Content -->
        <!-- Time Period Filter -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Filter Attendance</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <input type="hidden" name="view" value="history">
                    <div class="col-md-5">
                        <label class="form-label">Year</label>
                        <select class="form-select" name="year" id="year">
                            <?php for($y = date('Y'); $y >= date('Y')-5; $y--): ?>
                            <option value="<?php echo $y; ?>" <?php echo isset($_GET['year']) && $_GET['year'] == $y ? 'selected' : ($y == date('Y') && !isset($_GET['year']) ? 'selected' : ''); ?>><?php echo $y; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Month</label>
                        <select class="form-select" name="month" id="month">
                            <?php 
                            $months = [
                                1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                                5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                                9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                            ];
                            foreach($months as $num => $name): 
                            ?>
                            <option value="<?php echo $num; ?>" <?php echo isset($_GET['month']) && $_GET['month'] == $num ? 'selected' : ($num == date('m') && !isset($_GET['month']) ? 'selected' : ''); ?>><?php echo $name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-2"></i>Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Attendance Charts -->
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">Monthly Attendance</h5>
                    </div>
                    <div class="card-body d-flex flex-column align-items-center">
                        <canvas id="monthlyChart" width="300" height="300"></canvas>
                        <div class="text-center mt-3">
                            <h4>
                                <?php 
                                echo $months[isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m')]; 
                                echo ' ' . (isset($_GET['year']) ? $_GET['year'] : date('Y'));
                                ?>
                            </h4>
                            <div class="d-flex justify-content-center gap-4 mt-2">
                                <div class="text-center">
                                    <h5><?php echo $monthlyData['present']; ?></h5>
                                    <span class="badge badge-success">Present</span>
                                </div>
                                <div class="text-center">
                                    <h5><?php echo $monthlyData['absent']; ?></h5>
                                    <span class="badge badge-danger">Absent</span>
                                </div>
                                <div class="text-center">
                                    <h5><?php echo $monthlyData['total']; ?></h5>
                                    <span class="badge badge-info">Total</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">Yearly Attendance</h5>
                    </div>
                    <div class="card-body d-flex flex-column align-items-center">
                        <canvas id="yearlyChart" width="300" height="300"></canvas>
                        <div class="text-center mt-3">
                            <h4><?php echo isset($_GET['year']) ? $_GET['year'] : date('Y'); ?></h4>
                            <div class="d-flex justify-content-center gap-4 mt-2">
                                <div class="text-center">
                                    <h5><?php echo $yearlyData['present']; ?></h5>
                                    <span class="badge badge-success">Present</span>
                                </div>
                                <div class="text-center">
                                    <h5><?php echo $yearlyData['absent']; ?></h5>
                                    <span class="badge badge-danger">Absent</span>
                                </div>
                                <div class="text-center">
                                    <h5><?php echo $yearlyData['total']; ?></h5>
                                    <span class="badge badge-info">Total</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Attendance Records -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Recent Attendance Records</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($attendanceData) > 0): ?>
                                <?php foreach ($attendanceData as $record): ?>
                                <tr>
                                    <td><?php echo date('d M Y', strtotime($record['date'])); ?></td>
                                    <td><?php echo date('h:i A', strtotime($record['time'])); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $record['status'] == 'Present' ? 'success' : 'danger'; ?>">
                                            <?php echo $record['status']; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center">No attendance records found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php else: ?>
        <!-- Dashboard Content -->
        <!-- Stats Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-info">
                    <p>Today's Status</p>
                    <h3 id="attendanceStatus">Not Marked</h3>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <p>Check-in Time</p>
                    <h3 id="checkInTime">--:--</h3>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-sign-in-alt"></i>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <p>Check-out Time</p>
                    <h3 id="checkOutTime">--:--</h3>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-sign-out-alt"></i>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <p>Working Hours</p>
                    <h3 id="workingHours">--.--</h3>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-business-time"></i>
                </div>
            </div>
        </div>

        <!-- Location Status -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Location Status</h5>
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Assigned Location</label>
                            <input type="text" class="form-control" value="<?php echo $assignedLocation; ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Current Location</label>
                            <input type="text" class="form-control" id="currentLocation" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Location Status</label>
                            <div class="alert" id="locationStatus" role="alert"></div>
                        </div>
                        <button class="btn btn-primary" onclick="checkLocation()">
                            <i class="fas fa-location-arrow me-2"></i>Check Location
                        </button>
                    </div>
                    <div class="col-md-6">
                        <div id="map" style="height: 300px; border-radius: 8px;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mark Attendance Card -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-flex gap-3">
                    <button id="markAttendanceBtn" class="btn btn-primary" onclick="window.location.href='http://127.0.0.1:1000/start'" disabled>
                        <i class="fas fa-camera me-2"></i>Mark Attendance
                    </button>
                    <button class="btn btn-info" onclick="window.location.href='employee_dashboard.php?view=history'">
                        <i class="fas fa-history me-2"></i>View History
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if($view == 'dashboard'): ?>
    <script src="https://apis.mapmyindia.com/advancedmaps/v1/565c1e6bc66610c192e80079ec366ca6/map_load?v=1.5"></script>
    <script>
        // Initialize map
        let map;
        let isAtCorrectLocation = false;
        const API_KEY = "565c1e6bc66610c192e80079ec366ca6";

        // Check location
        function checkLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                    const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        
                        // Update or initialize map with MapMyIndia
                        if (!map && typeof MapmyIndia !== 'undefined') {
                            map = new MapmyIndia.Map('map', { center: [lat, lng], zoom: 14 });
                            // Add marker for current location
                            L.marker([lat, lng]).addTo(map).bindPopup("Your Current Location").openPopup();
                            
                            // Add marker for assigned location if available
                            const assignedLat = <?php echo $assignedLat ?: 'null'; ?>;
                            const assignedLng = <?php echo $assignedLon ?: 'null'; ?>;
                            
                            if (assignedLat !== null && assignedLng !== null) {
                                L.marker([assignedLat, assignedLng]).addTo(map)
                                 .bindPopup("Assigned Location: <?php echo addslashes($assignedLocation); ?>").openPopup();
                            }
                        }

                        // Get location name using MapMyIndia reverse geocoding
                        fetch(`https://apis.mapmyindia.com/advancedmaps/v1/${API_KEY}/rev_geocode?lat=${lat}&lng=${lng}`)
                        .then(response => response.json())
                        .then(data => {
                            let locationName = "Unknown Location";
                            if (data.results && data.results.length > 0) {
                                locationName = data.results[0].formatted_address;
                            }
                                document.getElementById("currentLocation").value = locationName;
                                
                                // Compare with assigned location using coordinates
                                const assignedLat = <?php echo $assignedLat ?: 'null'; ?>;
                                const assignedLng = <?php echo $assignedLon ?: 'null'; ?>;
                                
                                if (assignedLat === null || assignedLng === null) {
                                    document.getElementById("locationStatus").className = "alert alert-warning";
                                    document.getElementById("locationStatus").innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>No assigned location found';
                                    document.getElementById("markAttendanceBtn").disabled = true;
                                    isAtCorrectLocation = false;
                                    return;
                                }
                                
                                // Calculate distance between current and assigned location
                                const distance = calculateDistance(lat, lng, assignedLat, assignedLng);
                                
                                // Allow 100 meters of distance tolerance (0.1 km)
                                if (distance <= 0.1) {
                                    document.getElementById("locationStatus").className = "alert alert-success";
                                    document.getElementById("locationStatus").innerHTML = '<i class="fas fa-check-circle me-2"></i>You are at your assigned location';
                                    document.getElementById("markAttendanceBtn").disabled = false;
                                    isAtCorrectLocation = true;
                            } else {
                                    document.getElementById("locationStatus").className = "alert alert-danger";
                                    document.getElementById("locationStatus").innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>You are not at your assigned location (Distance: ' + distance.toFixed(2) + ' km)';
                                    document.getElementById("markAttendanceBtn").disabled = true;
                                    isAtCorrectLocation = false;
                            }
                        })
                        .catch(error => {
                            console.error("Reverse geocoding error:", error);
                                document.getElementById("locationStatus").className = "alert alert-danger";
                                document.getElementById("locationStatus").innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>Error retrieving location name';
                                document.getElementById("markAttendanceBtn").disabled = true;
                                isAtCorrectLocation = false;
                            });
                    },
                    (error) => {
                        console.error("Error getting location:", error);
                        document.getElementById("locationStatus").className = "alert alert-danger";
                        document.getElementById("locationStatus").innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>Error getting location: ' + error.message;
                        document.getElementById("markAttendanceBtn").disabled = true;
                        isAtCorrectLocation = false;
                    }
                );
            } else {
                document.getElementById("locationStatus").className = "alert alert-danger";
                document.getElementById("locationStatus").innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>Geolocation is not supported by this browser';
                document.getElementById("markAttendanceBtn").disabled = true;
                isAtCorrectLocation = false;
            }
        }

        // Calculate distance between two coordinates in kilometers (using Haversine formula)
        function calculateDistance(lat1, lon1, lat2, lon2) {
            if (lat1 === null || lat2 === null) return 9999; // Return large distance if coordinates are invalid
            
            const R = 6371; // Radius of the Earth in km
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = 
                Math.sin(dLat/2) * Math.sin(dLat/2) +
                Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * 
                Math.sin(dLon/2) * Math.sin(dLon/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            const distance = R * c; // Distance in km
            return distance;
        }

        // Check location on page load
        window.onload = checkLocation;
    </script>
    <?php endif; ?>

    <?php if($view == 'history'): ?>
    <script>
        // Monthly Attendance Chart
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyChart = new Chart(monthlyCtx, {
            type: 'pie',
            data: {
                labels: ['Present', 'Absent'],
                datasets: [{
                    data: [<?php echo $monthlyData['present']; ?>, <?php echo $monthlyData['absent']; ?>],
                    backgroundColor: [
                        'rgba(46, 204, 113, 0.8)',
                        'rgba(231, 76, 60, 0.8)'
                    ],
                    borderColor: [
                        'rgba(46, 204, 113, 1)',
                        'rgba(231, 76, 60, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const value = context.raw;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100) || 0;
                                return `${context.label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

        // Yearly Attendance Chart
        const yearlyCtx = document.getElementById('yearlyChart').getContext('2d');
        const yearlyChart = new Chart(yearlyCtx, {
            type: 'pie',
            data: {
                labels: ['Present', 'Absent'],
                datasets: [{
                    data: [<?php echo $yearlyData['present']; ?>, <?php echo $yearlyData['absent']; ?>],
                    backgroundColor: [
                        'rgba(46, 204, 113, 0.8)',
                        'rgba(231, 76, 60, 0.8)'
                    ],
                    borderColor: [
                        'rgba(46, 204, 113, 1)',
                        'rgba(231, 76, 60, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const value = context.raw;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100) || 0;
                                return `${context.label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    </script>
    <?php endif; ?>

    <script>
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

        // Dark mode toggle
        document.querySelector('.dark-mode-toggle').addEventListener('click', function() {
            document.body.classList.toggle('dark-mode');
        });
</script>
</body>
</html>