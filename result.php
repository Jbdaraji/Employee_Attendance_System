<?php
session_start();
$login_email = $_SESSION['email'];
// Database connection
require '__dbconnection.php';

// Get matched employee name from URL
$matched_name = isset($_GET['matched_name']) ? $_GET['matched_name'] : '';

if ($matched_name != '') {
    // Fetch employee details using name
    $sql = "SELECT id, email FROM employees WHERE email = '$matched_name'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $emp_id = $row['id'];
        $email = $row['email'];

        // Check if attendance is already marked for today
        $check_sql = "SELECT * FROM attendance WHERE id = '$emp_id' AND DATE(timestamp) = CURDATE()";
        $check_result = mysqli_query($conn, $check_sql);

        if (mysqli_num_rows($check_result) == 0) {
            // Mark attendance as "Present"
            if($login_email == $matched_name)
            {
                $insert_sql = "INSERT INTO attendance (id, email, status) VALUES ('$emp_id', '$email', 'Present')";
                if (mysqli_query($conn, $insert_sql)) {
                    $message = "Attendance marked successfully for $matched_name.";
                } else {
                    $message = "Error marking attendance: " . mysqli_error($conn);
                }
            } else {
                $message = "You are not authorized to mark attendance for this employee.";
            }
        } else {
            $message = "Attendance already marked for today.";
        }
    } else {
        $message = "No matching employee found.";
    }
} else {
    $message = "No employee name provided.";
}

// Close database connection
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Matched Employee</title>
</head>
<body>
    <h2>Matched Employee</h2>
    <p>Employee Name: <strong><?php echo htmlspecialchars($matched_name); ?></strong></p>
    <p><?php echo $message; ?></p>
    <a href="employee_dashboard.php">Go Back</a>
</body>
</html>
