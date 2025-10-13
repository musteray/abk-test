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
            // Insert into database
            $customerId = $database->insertCustomer($formData);
            
            // Clear session data
            unset($_SESSION['uploaded_image']);
            
            // Regenerate CSRF token
            unset($_SESSION['csrf_token']);
            
            // Redirect to prevent form resubmission
            header('Location: ' . $_SERVER['PHP_SELF'] . '?success=1&id=' . $customerId);
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
        $customer = $database->fetchCustomerById($formatted_string);

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
    <title>Customer Information Entry - Secure Form</title>
    <style>
        /* ==================== GLOBAL STYLES ==================== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        /* ==================== CONTAINER STYLES ==================== */
        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            max-width: 600px;
            width: 100%;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .subtitle {
            color: #666;
            text-align: center;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        /* ==================== DATABASE INFO STYLES ==================== */
        .db-info {
            background: #e8f4f8;
            border: 1px solid #b3d9e6;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 20px;
            font-size: 12px;
            color: #0066a1;
        }
        
        .db-info strong {
            color: #004d7a;
        }
        
        .db-status-ok {
            color: #28a745;
            font-weight: bold;
        }
        
        .db-status-error {
            color: #dc3545;
            font-weight: bold;
        }
        
        /* ==================== ALERT STYLES ==================== */
        .alert {
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-error {
            background-color: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .alert-success {
            background-color: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }
        
        .alert ul {
            margin-left: 20px;
            margin-top: 5px;
        }
        
        /* ==================== FORM STYLES ==================== */
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        
        .required {
            color: #e74c3c;
        }
        
        input[type="text"],
        input[type="email"],
        select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        input[type="text"]:focus,
        input[type="email"]:focus,
        select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        /* ==================== IMAGE UPLOAD STYLES ==================== */
        .image-upload-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            border: 2px dashed #ddd;
        }
        
        .image-preview {
            margin-top: 15px;
            text-align: center;
        }
        
        .image-preview img {
            max-width: 200px;
            max-height: 200px;
            border-radius: 5px;
            border: 2px solid #ddd;
            object-fit: cover;
        }
        
        input[type="file"] {
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        /* ==================== BUTTON STYLES ==================== */
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        
        button {
            flex: 1;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .btn-save {
            background: #667eea;
            color: white;
        }
        
        .btn-save:hover:not(:disabled) {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-cancel {
            background: #e74c3c;
            color: white;
        }
        
        .btn-cancel:hover:not(:disabled) {
            background: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.4);
        }
        
        .btn-upload {
            background: #3498db;
            color: white;
            width: auto;
            padding: 10px 20px;
        }
        
        .btn-upload:hover:not(:disabled) {
            background: #2980b9;
        }
        
        /* ==================== RESPONSIVE DESIGN ==================== */
        @media (max-width: 600px) {
            .container {
                padding: 20px;
            }
            
            .button-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
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

        // d@gmail.com
        
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