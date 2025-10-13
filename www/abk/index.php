<?php
declare(strict_types=1); // Enable strict typing for type safety

/**
 * Customer Information Entry Form
 * 
 * A secure, well-structured PHP application following best practices:
 * - Strict typing for type safety
 * - Proper error handling with try-catch
 * - PDO with mysqlnd for optimal database performance
 * - Input validation and sanitization
 * - CSRF protection
 * - Secure file upload handling
 * - PSR-12 coding standards
 * 
 */

// Start session to maintain uploaded image across page reloads
session_start();

// Initialize csrf, constants, and required classes
require_once './utils/constant.php';
require_once './utils/csrf_token.php';
require_once './utils/database.php';
require_once './utils/input_validation.php';
require_once './utils/file_upload.php';

// Initialize variables
$errors = [];
$success = '';
$details = [];
$uploadedImage = $_SESSION['uploaded_image'] ?? '';
$csrfToken = generateCsrfToken();

// Initialize classes
$database = new Database();
$fileHandler = new FileUploadHandler();
$validator = new Validator();

// ==================== HANDLE IMAGE UPLOAD ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_image'])) {
    try {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
            throw new RuntimeException('Invalid CSRF token. Please refresh the page.');
        }
        
        if (isset($_FILES['customer_image']) && $_FILES['customer_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadedPath = $fileHandler->upload($_FILES['customer_image']);
            $_SESSION['uploaded_image'] = $uploadedPath;
            $uploadedImage = $uploadedPath;
            $success = 'Image uploaded successfully!';
        }
        
    } catch (RuntimeException $e) {
        $errors[] = $e->getMessage();
    } catch (Exception $e) {
        error_log('Upload error: ' . $e->getMessage());
        $errors[] = 'An unexpected error occurred during upload.';
    }
}

// ==================== HANDLE FORM SUBMISSION ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    try {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
            throw new RuntimeException('Invalid CSRF token. Please refresh the page.');
        }
        
        // Sanitize and prepare data
        $formData = [
            'lastname'   => trim($_POST['lastname'] ?? ''),
            'firstname'  => trim($_POST['firstname'] ?? ''),
            'email'      => trim($_POST['email'] ?? ''),
            'city'       => trim($_POST['city'] ?? ''),
            'country'    => $_POST['country'] ?? '',
            'image_path' => $uploadedImage,
        ];
        
        // Validate form data
        if ($validator->validateCustomerData($formData)) {
            $queryString = "";

            if (isset($_GET['email'])) {
                // Insert into database
                $customerId = $database->insertCustomer($formData);

                $queryString = '?success=1&id=' . $customerId;
            } else {
               $database->updateCustomer($formData);
               $queryString = '?email=' . urlencode($formData['email']) . "&success=1";
            }

            // Clear session data
            unset($_SESSION['uploaded_image']);
            
            // Regenerate CSRF token
            unset($_SESSION['csrf_token']);
            
            // Redirect to prevent form resubmission
            header('Location: ' . $_SERVER['PHP_SELF'] . $queryString);
            exit;
        } else {
            $errors = $validator->getErrors();
        }
        
    } catch (PDOException $e) {
        error_log('Database error: ' . $e->getMessage());
        $errors[] = 'Database error occurred. Please try again later.';
    } catch (RuntimeException $e) {
        $errors[] = $e->getMessage();
    } catch (Exception $e) {
        error_log('Unexpected error: ' . $e->getMessage());
        $errors[] = 'An unexpected error occurred. Please try again.';
    }
}

// ==================== HANDLE CANCEL BUTTON ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel'])) {
    // Verify CSRF token
    if (isset($_POST['csrf_token']) && verifyCsrfToken($_POST['csrf_token'])) {
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// ==================== HANDLE SUCCESS MESSAGE ====================
if (isset($_GET['success']) && $_GET['success'] === '1') {
    $customerId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $success = $customerId 
        ? "Customer information saved successfully! (Customer ID: {$customerId})"
        : 'Customer information saved successfully!';
}

// ==================== HANDLE FETCH DETAILS BY EMAIL ====================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['email'])) {
    try {
        $formatted_string = str_replace(' ', '+', $_GET['email']);

        // Get customer details by email
        $customer = $database->getCustomerById($formatted_string);

        $details = $customer ?? [];
        $uploadedImage = $details['image_path'] ?? '';
    } catch (PDOException $e) {
        error_log('Customer not found: ' . $e->getMessage());
        $errors[] = 'Customer not found.';
    } catch (Exception $e) {
        error_log('Unexpected error: ' . $e->getMessage());
        $errors[] = 'An unexpected error occurred. Please try again.';
    }
}

// Get driver information
$driverInfo = $database->getDriverInfo();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Customer Information Entry Form">
    <meta name="author" content="Your Name">
    <title>ABK - Proj Exercises</title>
    <style>
        .main-container {
            display: grid;
            grid-template-columns: 50% 50%;
            gap: 10px;
        }
    </style>
    <link rel="stylesheet" href="./styles/customer-form.css">
    <link rel="stylesheet" href="./styles/mini-calc.css">
    <link rel="stylesheet" href="./styles/screen-sharing.css">
    <script src="./js/mini-calc.js"></script>
    <script src="./js/screen-sharing.js"></script>
</head>
<body>
    <div class="main-container">
        <!-- Customer Form -->
        <div class="customer-form-container">
            <h1>Customer Information</h1>
            <p class="subtitle">Secure data entry with validation</p>
            
            <!-- Display PDO + mysqlnd driver status -->
            <!-- <div class="db-info">
                <strong>Database Driver:</strong> PDO (<?php echo htmlspecialchars($driverInfo['driver']); ?>)<br>
                <strong>Client Version:</strong> <?php echo htmlspecialchars($driverInfo['client_version']); ?><br>
                <strong>mysqlnd Status:</strong> 
                <?php if ($driverInfo['mysqlnd_active']): ?>
                    <span class="db-status-ok">✓ Active</span>
                <?php else: ?>
                    <span class="db-status-error">✗ Inactive</span>
                <?php endif; ?>
            </div> -->
            
            <!-- Display error messages -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error" role="alert">
                    <strong>Please fix the following errors:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <!-- Display success message -->
            <?php if (!empty($success)): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
            
            <!-- Customer Information Form -->
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" id="customerForm" novalidate>
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                
                <!-- Last Name Field -->
                <div class="form-group">
                    <label for="lastname">
                        Last Name <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="lastname" 
                        name="lastname" 
                        placeholder="Enter last name"
                        maxlength="255"
                        required
                        aria-required="true"
                        value="<?php echo htmlspecialchars($details['lastname'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                    >
                </div>
                
                <!-- First Name Field -->
                <div class="form-group">
                    <label for="firstname">
                        First Name <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="firstname" 
                        name="firstname" 
                        placeholder="Enter first name"
                        maxlength="255"
                        required
                        aria-required="true"
                        value="<?php echo htmlspecialchars($details['firstname'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                    >
                </div>
                
                <!-- Email Field -->
                <div class="form-group">
                    <label for="email">
                        Email <span class="required">*</span>
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="Enter email address"
                        maxlength="255"
                        required
                        aria-required="true"
                        value="<?php echo htmlspecialchars($details['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                    >
                </div>
                
                <!-- City Field -->
                <div class="form-group">
                    <label for="city">
                        City <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="city" 
                        name="city" 
                        placeholder="Enter city"
                        maxlength="255"
                        required
                        aria-required="true"
                        value="<?php echo htmlspecialchars($details['city'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                    >
                </div>
                
                <!-- Country Dropdown -->
                <div class="form-group">
                    <label for="country">
                        Country <span class="required">*</span>
                    </label>
                    <select
                        id="country"
                        name="country"
                        required
                        aria-required="true"
                    >
                        <?php
                            $countries = ['', 'United States', 'Canada', 'Japan', 'United Kingdom', 'France', 'Germany'];
                            $option = "";

                            foreach ($countries as $country) {
                                $selected = ($details['country'] ?? '') === $country ? 'selected' : '';
                                $display = $country === '' ? '-- Select Country --' : $country;
                                $option .= "<option value=\"" . htmlspecialchars($country, ENT_QUOTES, 'UTF-8') . "\" $selected>" . htmlspecialchars($display, ENT_QUOTES, 'UTF-8') . "</option>\n";
                            }

                            echo $option
                        ?>
                    </select>
                </div>
                
                <!-- Action Buttons -->
                <div class="button-group">
                    <button type="submit" name="save" class="btn-save">Save Customer</button>
                    <button type="submit" name="cancel" class="btn-cancel">Cancel</button>
                </div>
            </form>
            
            <!-- Separate Form for Image Upload -->
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" enctype="multipart/form-data" style="margin-top: 30px;">
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                
                <div class="form-group">
                    <label for="customer_image">Customer Picture (JPEG only, max 5MB)</label>
                    <div class="image-upload-section">
                        <input 
                            type="file" 
                            id="customer_image" 
                            name="customer_image" 
                            accept=".jpg,.jpeg,image/jpeg"
                            aria-describedby="upload-help"
                        >
                        <small id="upload-help" style="display: block; margin-bottom: 10px; color: #666;">
                            Allowed formats: JPG, JPEG | Maximum size: 5MB
                        </small>
                        <button type="submit" name="upload_image" class="btn-upload">Upload Image</button>
                        
                        <!-- Display uploaded image preview -->
                        <?php if (!empty($uploadedImage)): ?>
                            <div class="image-preview">
                                <p><strong>Current Image:</strong></p>
                                <img src="<?php echo htmlspecialchars($uploadedImage, ENT_QUOTES, 'UTF-8'); ?>" alt="Customer Picture">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </form>


        </div>

        <!-- Mini Pocket Calculator -->
        <div class="calculator-container">
            <h2>Mini Pocket Calculator</h2>
            <input type="text" id="resultField" class="result-field" readonly placeholder="Result appears here">
            <iframe id="displayFrame" sandbox="allow-scripts allow-same-origin" src="./calc-display-frame.html"></iframe>
            <iframe id="buttonFrame" sandbox="allow-scripts allow-same-origin" src="./calc-button-frame.html"></iframe>
            <div class="security-note">
                <strong>Security:</strong> PostMessage API with origin validation, input sanitization, and sandboxed iframes.
            </div>
        </div>

        <!-- Screen Sharing Section -->
        <div class="screen-sharing-container">
            <h1>🖥️ Screen Share - Co-worker Collaboration</h1>
            <p class="subtitle">Share your screen with co-workers to review customer information together</p>
            
            <div class="status">
                <div class="status-text" id="statusText">Ready to share</div>
                <div class="status-detail" id="statusDetail">Click "Start Screen Share" to begin sharing your screen</div>
            </div>
            
            <div class="controls">
                <button id="startBtn" class="btn-primary" onclick="startSharing()">
                    <span>📺</span> Start Screen Share
                </button>
                <button id="stopBtn" class="btn-danger" onclick="stopSharing()" disabled>
                    <span>⏹️</span> Stop Sharing
                </button>
            </div>
            
            <div id="shareLinkContainer" class="share-link-container">
                <label class="share-link-label">📤 Share this link with your co-worker:</label>
                <div class="share-link-box">
                    <input type="text" id="shareLink" class="share-link" readonly>
                    <button class="btn-copy" onclick="copyLink()">📋 Copy Link</button>
                </div>
                <div class="connection-stats" id="connectionStats"></div>
            </div>
            
            <div id="videoContainer" class="video-container">
                <div class="video-overlay">
                    <span class="recording-indicator"></span>
                    <span id="viewerCount">0 viewers</span>
                </div>
                <video id="localVideo" autoplay muted playsinline></video>
            </div>
            
            <div class="info-box">
                <strong>ℹ️ How it works:</strong>
                <ul style="margin-left: 20px; margin-top: 8px; line-height: 1.6;">
                    <li>Click "Start Screen Share" and select which screen/window to share</li>
                    <li>Copy and send the generated link to your co-worker</li>
                    <li>Your co-worker opens the link to view your screen in real-time</li>
                    <li>Perfect for reviewing customer data, troubleshooting, or collaboration</li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- JavaScript for client-side validation -->
    <script>
        'use strict';
        
        // Email validation on form submit
        document.getElementById('customerForm').addEventListener('submit', function(e) {
            const emailInput = document.getElementById('email');
            const email = emailInput.value.trim();
            
            // Email validation regex
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (email && !emailRegex.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address.\n\nExample: user@example.com');
                emailInput.focus();
                return false;
            }
        });
        
        // Real-time email validation feedback
        document.getElementById('email').addEventListener('blur', function() {
            const email = this.value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (email && !emailRegex.test(email)) {
                this.style.borderColor = '#e74c3c';
            } else if (email) {
                this.style.borderColor = '#28a745';
            } else {
                this.style.borderColor = '#ddd';
            }
        });
        
        // File upload validation
        document.getElementById('customer_image').addEventListener('change', function() {
            const file = this.files[0];
            
            if (file) {
                const fileType = file.type;
                const fileSize = file.size;
                const maxSize = <?php echo MAX_FILE_SIZE; ?>;
                
                // Check file type (JPEG only)
                if (fileType !== 'image/jpeg' && fileType !== 'image/jpg') {
                    alert('Only JPEG images are allowed.');
                    this.value = '';
                    return false;
                }
                
                // Check file size (max 5MB)
                if (fileSize > maxSize) {
                    alert('File size must be less than 5MB.\nYour file is ' + (fileSize / 1024 / 1024).toFixed(2) + 'MB');
                    this.value = '';
                    return false;
                }
            }
        });

        // Prevent double form submission
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function() {
                const buttons = this.querySelectorAll('button[type="submit"]');
                buttons.forEach(button => {
                    // button.disabled = true; // causing not detecting $_POST 'save', 'cancel' & 'image_upload' in PHP
                    button.textContent = 'Processing...';
                });
            });
        });
    </script>
</body>
</html>