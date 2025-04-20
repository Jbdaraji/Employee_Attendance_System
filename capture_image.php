<?php
// Start session at the very beginning of the file
session_start();

// Check if we have signup data in the session
if (!isset($_SESSION['signup_data']) && !strpos($_SERVER['REQUEST_URI'], '?')) {
    // If no session data and no URL parameters, redirect back to registration
    $_SESSION['error'] = "Please complete the registration form first.";
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Capture Face Image - AttendX</title>
    <link rel="stylesheet" href="static/app-styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            <a href="index.php" class="nav-link">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Login</span>
            </a>
            <a href="#" class="nav-link">
                <i class="fas fa-question-circle"></i>
                <span>Help</span>
            </a>
        </nav>
    </header>

    <div class="app-container">
        <div class="main-content">
            <div class="dashboard-header">
                <h1 class="dashboard-title">Face Registration</h1>
                <p class="dashboard-subtitle">Please follow the instructions to capture your face for biometric authentication</p>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Capture Your Face</h2>
                </div>
                <div class="card-body">
                    <div class="steps">
                        <div class="step active">
                            1
                            <span class="step-title">Position</span>
                        </div>
                        <div class="step">
                            2
                            <span class="step-title">Capture</span>
                        </div>
                        <div class="step">
                            3
                            <span class="step-title">Confirm</span>
                        </div>
                        <div class="step">
                            4
                            <span class="step-title">Complete</span>
                        </div>
                    </div>
                    
                    <div id="instructions" class="alert alert-warning">
                        <i class="fas fa-info-circle"></i> Position your face within the frame and ensure good lighting. Keep your face centered and visible.
                    </div>
                    
                    <div class="camera-container">
                        <video id="video" width="100%" height="100%" autoplay muted></video>
                        <div class="camera-frame"></div>
                        <div id="flash" class="flash"></div>
                        <img id="capturedImage" width="100%" height="100%" style="display: none;">
                    </div>
                    
                    <div style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: center;">
                        <button id="captureBtn" class="btn btn-primary">
                            <i class="fas fa-camera"></i> Capture Image
                        </button>
                        <button id="retakeBtn" class="btn btn-outline-primary" style="display: none;">
                            <i class="fas fa-redo"></i> Retake Photo
                        </button>
                        <button id="submitBtn" class="btn btn-success" style="display: none;">
                            <i class="fas fa-check"></i> Confirm & Save
                        </button>
                    </div>
                    
                    <canvas id="canvas" style="display: none;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        // DOM Elements
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const capturedImageEl = document.getElementById('capturedImage');
        const captureBtn = document.getElementById('captureBtn');
        const retakeBtn = document.getElementById('retakeBtn');
        const submitBtn = document.getElementById('submitBtn');
        const flash = document.getElementById('flash');
        const instructions = document.getElementById('instructions');
        const steps = document.querySelectorAll('.step');
        
        // Global variables
        let stream = null;
        let capturedImage = null;
        
        // Start webcam
        async function startWebcam() {
            try {
                stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        width: { ideal: 1280 },
                        height: { ideal: 720 },
                        facingMode: 'user'
                    },
                    audio: false
                });
                video.srcObject = stream;
                updateStep(1);
            } catch (err) {
                console.error('Error accessing webcam:', err);
                instructions.className = 'alert alert-danger';
                instructions.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Could not access webcam. Please allow camera access and refresh the page.';
            }
        }
        
        // Capture image from webcam
        function captureImage() {
            const context = canvas.getContext('2d');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            // Create flash effect
            flash.classList.add('flash-animation');
            setTimeout(() => {
                flash.classList.remove('flash-animation');
            }, 500);
            
            // Convert to data URL
            capturedImage = canvas.toDataURL('image/png');
            capturedImageEl.src = capturedImage;
            
            // Update UI
            video.style.display = 'none';
            capturedImageEl.style.display = 'block';
            captureBtn.style.display = 'none';
            retakeBtn.style.display = 'inline-block';
            submitBtn.style.display = 'inline-block';
            
            instructions.className = 'alert alert-success';
            instructions.innerHTML = '<i class="fas fa-check-circle"></i> Image captured successfully! Please confirm if you are satisfied with the photo.';
            
            updateStep(2);
        }
        
        // Retake photo
        function retakePhoto() {
            video.style.display = 'block';
            capturedImageEl.style.display = 'none';
            captureBtn.style.display = 'inline-block';
            retakeBtn.style.display = 'none';
            submitBtn.style.display = 'none';
            
            instructions.className = 'alert alert-warning';
            instructions.innerHTML = '<i class="fas fa-info-circle"></i> Position your face within the frame and ensure good lighting. Keep your face centered and visible.';
            
            updateStep(1);
        }
        
        // Submit photo
        function submitPhoto() {
            // Create a form to submit the image data
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'save_image.php';
            
            // Create hidden input for the image data
            const imageInput = document.createElement('input');
            imageInput.type = 'hidden';
            imageInput.name = 'image_data';
            imageInput.value = capturedImage;
            form.appendChild(imageInput);
            
            // Create hidden input for redirect URL
            const redirectInput = document.createElement('input');
            redirectInput.type = 'hidden';
            redirectInput.name = 'redirect_url';
            redirectInput.value = 'http://127.0.0.1:1000/add';
            form.appendChild(redirectInput);
            
            // Create hidden input to indicate we want to open a new tab
            const newTabInput = document.createElement('input');
            newTabInput.type = 'hidden';
            newTabInput.name = 'open_new_tab';
            newTabInput.value = 'true';
            form.appendChild(newTabInput);
            
            // Add form to document and submit
            document.body.appendChild(form);
            
            // Update UI to show processing state
            updateStep(3);
            instructions.className = 'alert alert-success';
            instructions.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing your image...';
            submitBtn.disabled = true;
            retakeBtn.disabled = true;
            
            // Submit the form
            form.submit();
        }
        
        // Update step indicator
        function updateStep(stepNum) {
            steps.forEach((step, index) => {
                if (index + 1 < stepNum) {
                    step.className = 'step completed';
                } else if (index + 1 === stepNum) {
                    step.className = 'step active';
                } else {
                    step.className = 'step';
                }
            });
        }
        
        // Event listeners
        captureBtn.addEventListener('click', captureImage);
        retakeBtn.addEventListener('click', retakePhoto);
        submitBtn.addEventListener('click', submitPhoto);
        
        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            startWebcam();
        });
    </script>
</body>
</html>
