<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

include '__dbconnection.php';

// Get the current view from URL parameter
$view = isset($_GET['view']) ? $_GET['view'] : 'list';

// Handle employee deletion if requested
if(isset($_POST['delete_employee'])) {
    $id = mysqli_real_escape_string($conn, $_POST['employee_id']);
    $sql = "DELETE FROM employees WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if($stmt->execute()) {
        $_SESSION['message'] = "Employee deleted successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error deleting employee!";
        $_SESSION['message_type'] = "danger";
    }
}

// Handle employee registration
if(isset($_POST['register_employee'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $location_id = (int)$_POST['location_id'];

    // Check if email already exists
    $check_sql = "SELECT id FROM employees WHERE email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if($check_result->num_rows > 0) {
        $_SESSION['message'] = "Email already exists!";
        $_SESSION['message_type'] = "danger";
    } else {
        $sql = "INSERT INTO employees (name, email, password, role, location_id) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $name, $email, $password, $role, $location_id);
        
        if($stmt->execute()) {
            $_SESSION['message'] = "Employee registered successfully!";
            $_SESSION['message_type'] = "success";
            header("Location: manage_employees.php");
            exit;
        } else {
            $_SESSION['message'] = "Error registering employee!";
            $_SESSION['message_type'] = "danger";
        }
    }
}

// Get all employees with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Search functionality
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where_clause = '';
if(!empty($search)) {
    $where_clause = " WHERE name LIKE '%$search%' OR email LIKE '%$search%'";
}

// Get total records for pagination
$total_records_sql = "SELECT COUNT(*) as count FROM employees" . $where_clause;
$result = $conn->query($total_records_sql);
$total_records = $result->fetch_assoc()['count'];
$total_pages = ceil($total_records / $records_per_page);

// Get employees for current page
$sql = "SELECT * FROM employees" . $where_clause . " ORDER BY id DESC LIMIT ?, ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $offset, $records_per_page);
$stmt->execute();
$result = $stmt->get_result();

// Get all locations for dropdowns
$locations_sql = "SELECT * FROM locations";
$locations_result = $conn->query($locations_sql);
$locations = [];
while($loc = $locations_result->fetch_assoc()) {
    $locations[] = $loc;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ucfirst($view); ?> Employees - Face Attendance System</title>
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
                <a href="manage_employees.php?view=register" class="sidebar-nav-link <?php echo $view == 'register' ? 'active' : ''; ?>">
                    <i class="fas fa-user-plus"></i>
                    <span>Employee Registration</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="manage_employees.php" class="sidebar-nav-link <?php echo $view == 'list' ? 'active' : ''; ?>">
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
            <h1 class="h3"><?php echo ucfirst($view); ?> Employees</h1>
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

        <?php if($view == 'register'): ?>
        <!-- Registration Form -->
        <div class="card">
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="name" required>
                        <div class="invalid-feedback">Please enter the full name.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-control" name="email" required>
                        <div class="invalid-feedback">Please enter a valid email address.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" required>
                        <div class="invalid-feedback">Please enter a password.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select class="form-control" name="role" required>
                            <option value="Employee">Employee</option>
                            <option value="Admin">Admin</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <select class="form-control" name="location_id" required>
                            <?php foreach($locations as $loc): ?>
                            <option value="<?php echo $loc['id']; ?>"><?php echo $loc['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="text-end">
                        <button type="submit" name="register_employee" class="btn btn-primary">
                            <i class="fas fa-user-plus me-2"></i>Register Employee
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php else: ?>
        <!-- Search and Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" name="search" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="btn btn-primary">Search</button>
                            <?php if(!empty($search)): ?>
                                <a href="manage_employees.php" class="btn btn-secondary">Clear</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <a href="manage_employees.php?view=register" class="btn btn-success">
                            <i class="fas fa-plus me-2"></i>Add New Employee
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Employees Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Employee</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Location</th>
                                <th>Face Data</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
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
                                <td>
                                    <span class="badge <?php echo $row['role'] == 'Admin' ? 'badge-primary' : 'badge-info'; ?>">
                                        <?php echo $row['role']; ?>
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
                                <td>
                                    <?php if($row['face_image']): ?>
                                        <span class="badge badge-success">Registered</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Not Registered</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-primary" onclick="editEmployee(<?php echo $row['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-info" onclick="viewEmployee(<?php echo $row['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if($row['role'] != 'Admin'): ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this employee?');">
                                            <input type="hidden" name="employee_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" name="delete_employee" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                        </li>
                        <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>">Next</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Edit Employee Modal -->
        <div class="modal fade" id="editEmployeeModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Employee</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="editEmployeeForm" method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="edit_id" id="edit_id">
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" name="edit_name" id="edit_name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="edit_email" id="edit_email" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Role</label>
                                <select class="form-control" name="edit_role" id="edit_role" required>
                                    <option value="Employee">Employee</option>
                                    <option value="Admin">Admin</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Location</label>
                                <select class="form-control" name="edit_location" id="edit_location" required>
                                    <?php foreach($locations as $loc): ?>
                                    <option value="<?php echo $loc['id']; ?>"><?php echo $loc['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" name="update_employee" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- View Employee Modal -->
        <div class="modal fade" id="viewEmployeeModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Employee Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="text-center mb-4">
                            <div id="view_face_image" class="mx-auto mb-3" style="width: 100px; height: 100px;"></div>
                            <h4 id="view_name" class="mb-0"></h4>
                            <p id="view_email" class="text-muted"></p>
                        </div>
                        <div class="row">
                            <div class="col-sm-6">
                                <p class="mb-0">Role</p>
                                <p id="view_role" class="text-muted"></p>
                            </div>
                            <div class="col-sm-6">
                                <p class="mb-0">Location</p>
                                <p id="view_location" class="text-muted"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
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

        // Handle edit employee
        function editEmployee(id) {
            fetch('manage_employees.php?action=get_employee&id=' + id)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('edit_id').value = data.id;
                    document.getElementById('edit_name').value = data.name;
                    document.getElementById('edit_email').value = data.email;
                    document.getElementById('edit_role').value = data.role;
                    document.getElementById('edit_location').value = data.location_id;
                    new bootstrap.Modal(document.getElementById('editEmployeeModal')).show();
                });
        }

        // Handle view employee
        function viewEmployee(id) {
            fetch('manage_employees.php?action=get_employee&id=' + id)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('view_name').textContent = data.name;
                    document.getElementById('view_email').textContent = data.email;
                    document.getElementById('view_role').textContent = data.role;
                    document.getElementById('view_location').textContent = data.location_name;
                    
                    const imageContainer = document.getElementById('view_face_image');
                    if (data.face_image) {
                        imageContainer.innerHTML = `<img src="data:image/jpeg;base64,${data.face_image}" 
                                                      class="rounded-circle img-fluid" 
                                                      style="width: 100px; height: 100px; object-fit: cover;">`;
                    } else {
                        imageContainer.innerHTML = `<img src="https://ui-avatars.com/api/?name=${encodeURIComponent(data.name)}&size=100&background=4361ee&color=fff" 
                                                      class="rounded-circle img-fluid">`;
                    }
                    
                    new bootstrap.Modal(document.getElementById('viewEmployeeModal')).show();
                });
        }

        // Handle form submission
        if (document.getElementById('editEmployeeForm')) {
            document.getElementById('editEmployeeForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'update_employee');

                fetch('manage_employees.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                });
            });
        }
    </script>

    <?php
    // Handle AJAX requests
    if (isset($_GET['action']) && $_GET['action'] == 'get_employee') {
        $id = (int)$_GET['id'];
        $sql = "SELECT e.*, l.name as location_name FROM employees e 
                LEFT JOIN locations l ON e.location_id = l.id 
                WHERE e.id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $employee = $result->fetch_assoc();
        
        if ($employee['face_image']) {
            $employee['face_image'] = base64_encode($employee['face_image']);
        }
        
        header('Content-Type: application/json');
        echo json_encode($employee);
        exit;
    }

    // Handle employee update
    if (isset($_POST['action']) && $_POST['action'] == 'update_employee') {
        $id = (int)$_POST['edit_id'];
        $name = mysqli_real_escape_string($conn, $_POST['edit_name']);
        $email = mysqli_real_escape_string($conn, $_POST['edit_email']);
        $role = mysqli_real_escape_string($conn, $_POST['edit_role']);
        $location_id = (int)$_POST['edit_location'];

        $sql = "UPDATE employees SET name = ?, email = ?, role = ?, location_id = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssii", $name, $email, $role, $location_id, $id);
        
        $response = array();
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = "Employee updated successfully!";
        } else {
            $response['success'] = false;
            $response['message'] = "Error updating employee: " . $conn->error;
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    ?>
</body>
</html> 