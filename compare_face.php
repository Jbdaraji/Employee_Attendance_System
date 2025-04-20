<?php 
    include "__dbconnection.php";

    if (!isset($_POST['image']) || empty($_POST['image'])) {
        die("Error: No image data received.");
    }

    $imageData = $_POST['image']; // Base64-encoded image from the user
    $tempImagePath = "static/temp/face_capture.jpg";

    // Decode base64 image and save temporarily
    $imageBinary = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $imageData));
    file_put_contents($tempImagePath, $imageBinary);

    // Call Python script for face recognition
    $output = shell_exec("python3 compare_faces.py " . escapeshellarg($tempImagePath));

    if (trim($output) === "MATCHED") {
        echo "Face Matched! Attendance Marked.";
    } else {
        echo "Face Not Recognized.";
    }
?>
