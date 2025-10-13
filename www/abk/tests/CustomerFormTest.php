<?php
declare(strict_types=1);

/**
 * Unit Tests for Customer Information Entry Form
 * 
 * Tests cover:
 * - Database operations
 * - File upload handling
 * - Data validation
 * - CSRF token generation and verification
 * 
 * Requirements:
 * - PHPUnit 9.x or higher
 * - PHP 7.4 or higher
 * 
 * Installation:
 * composer require --dev phpunit/phpunit
 * 
 * Run tests:
 * ./vendor/bin/phpunit CustomerFormTest.php
 */

use PHPUnit\Framework\TestCase;
require_once 'CustomerFormMock.php';

// ==================== TEST CASES ====================

/**
 * Test suite for Customer Form functionality
 */
class CustomerFormTest extends TestCase
{
    private DatabaseMock $database;
    private ValidatorMock $validator;
    private FileUploadHandlerMock $fileHandler;
    
    protected function setUp(): void
    {
        $this->database = new DatabaseMock();
        $this->validator = new ValidatorMock();
        $this->fileHandler = new FileUploadHandlerMock();
    }
    
    // ==================== DATABASE TESTS ====================
    
    /**
     * Test database connection
     */
    public function testDatabaseConnection(): void
    {
        $connection = $this->database->getConnection();
        $this->assertInstanceOf(PDO::class, $connection);
    }
    
    /**
     * Test successful customer insertion
     */
    public function testInsertCustomerSuccess(): void
    {
        $customerData = [
            'lastname'   => 'Doe',
            'firstname'  => 'John',
            'email'      => 'john.doe@example.com',
            'city'       => 'New York',
            'country'    => 'United States',
            'image_path' => 'uploads/customer_123.jpg',
        ];
        
        $customerId = $this->database->insertCustomer($customerData);
        
        $this->assertGreaterThan(0, $customerId);
        $this->assertIsInt($customerId);
    }
    
    /**
     * Test retrieving customer by ID
     */
    public function testGetCustomerById(): void
    {
        // Insert test customer
        $customerData = [
            'lastname'   => 'Smith',
            'firstname'  => 'Jane',
            'email'      => 'jane.smith@example.com',
            'city'       => 'London',
            'country'    => 'United Kingdom',
            'image_path' => null,
        ];
        
        $customerId = $this->database->insertCustomer($customerData);
        
        // Retrieve customer
        $customer = $this->database->getCustomerById($customerId);
        
        $this->assertNotNull($customer);
        $this->assertEquals('Smith', $customer['lastname']);
        $this->assertEquals('Jane', $customer['firstname']);
        $this->assertEquals('jane.smith@example.com', $customer['email']);
    }
    
    /**
     * Test retrieving non-existent customer
     */
    public function testGetNonExistentCustomer(): void
    {
        $customer = $this->database->getCustomerById(99999);
        $this->assertNull($customer);
    }
    
    // ==================== VALIDATION TESTS ====================
    
    /**
     * Test validation with valid data
     */
    public function testValidationWithValidData(): void
    {
        $validData = [
            'lastname'  => 'Johnson',
            'firstname' => 'Michael',
            'email'     => 'michael.johnson@example.com',
            'city'      => 'Toronto',
            'country'   => 'Canada',
        ];
        
        $isValid = $this->validator->validateCustomerData($validData);
        
        $this->assertTrue($isValid);
        $this->assertEmpty($this->validator->getErrors());
    }
    
    /**
     * Test validation with missing lastname
     */
    public function testValidationMissingLastname(): void
    {
        $invalidData = [
            'lastname'  => '',
            'firstname' => 'John',
            'email'     => 'john@example.com',
            'city'      => 'Paris',
            'country'   => 'France',
        ];
        
        $isValid = $this->validator->validateCustomerData($invalidData);
        
        $this->assertFalse($isValid);
        $this->assertContains('Last name is required.', $this->validator->getErrors());
    }
    
    /**
     * Test validation with invalid email
     */
    public function testValidationInvalidEmail(): void
    {
        $invalidData = [
            'lastname'  => 'Doe',
            'firstname' => 'John',
            'email'     => 'invalid-email',
            'city'      => 'Berlin',
            'country'   => 'Germany',
        ];
        
        $isValid = $this->validator->validateCustomerData($invalidData);
        
        $this->assertFalse($isValid);
        $this->assertContains('Valid email is required.', $this->validator->getErrors());
    }
    
    /**
     * Test validation with invalid country
     */
    public function testValidationInvalidCountry(): void
    {
        $invalidData = [
            'lastname'  => 'Doe',
            'firstname' => 'John',
            'email'     => 'john@example.com',
            'city'      => 'Sydney',
            'country'   => 'Australia', // Not in allowed list
        ];
        
        $isValid = $this->validator->validateCustomerData($invalidData);
        
        $this->assertFalse($isValid);
        $this->assertContains('Valid country is required.', $this->validator->getErrors());
    }
    
    /**
     * Test validation with multiple errors
     */
    public function testValidationMultipleErrors(): void
    {
        $invalidData = [
            'lastname'  => '',
            'firstname' => '',
            'email'     => 'bad-email',
            'city'      => '',
            'country'   => 'InvalidCountry',
        ];
        
        $isValid = $this->validator->validateCustomerData($invalidData);
        
        $this->assertFalse($isValid);
        $this->assertCount(5, $this->validator->getErrors());
    }
    
    // ==================== FILE UPLOAD TESTS ====================
    
    /**
     * Test valid JPEG file extension
     */
    public function testValidateFileTypeJPEG(): void
    {
        $validFiles = [
            'image.jpg',
            'photo.jpeg',
            'picture.JPG',
            'customer.JPEG',
        ];
        
        foreach ($validFiles as $filename) {
            $this->assertTrue(
                $this->fileHandler->validateFileType($filename),
                "Failed for filename: {$filename}"
            );
        }
    }
    
    /**
     * Test invalid file extensions
     */
    public function testValidateFileTypeInvalid(): void
    {
        $invalidFiles = [
            'image.png',
            'photo.gif',
            'document.pdf',
            'script.php',
            'file.exe',
        ];
        
        foreach ($invalidFiles as $filename) {
            $this->assertFalse(
                $this->fileHandler->validateFileType($filename),
                "Should reject filename: {$filename}"
            );
        }
    }
    
    /**
     * Test file size validation - valid sizes
     */
    public function testValidateFileSizeValid(): void
    {
        $validSizes = [
            1024,           // 1KB
            102400,         // 100KB
            1048576,        // 1MB
            5242880,        // 5MB (max)
        ];
        
        foreach ($validSizes as $size) {
            $this->assertTrue(
                $this->fileHandler->validateFileSize($size),
                "Failed for size: {$size} bytes"
            );
        }
    }
    
    /**
     * Test file size validation - invalid sizes
     */
    public function testValidateFileSizeInvalid(): void
    {
        $invalidSizes = [
            5242881,        // 5MB + 1 byte
            10485760,       // 10MB
            52428800,       // 50MB
        ];
        
        foreach ($invalidSizes as $size) {
            $this->assertFalse(
                $this->fileHandler->validateFileSize($size),
                "Should reject size: {$size} bytes"
            );
        }
    }
    
    /**
     * Test secure filename generation
     */
    public function testGenerateSecureFilename(): void
    {
        $filename1 = $this->fileHandler->generateSecureFilename('jpg');
        $filename2 = $this->fileHandler->generateSecureFilename('jpg');
        
        // Filenames should be unique
        $this->assertNotEquals($filename1, $filename2);
        
        // Should contain .jpg extension
        $this->assertStringEndsWith('.jpg', $filename1);
        
        // Should start with customer_
        $this->assertStringStartsWith('customer_', $filename1);
        
        // Should contain date format
        $this->assertMatchesRegularExpression('/customer_\d{8}_\d{6}_[a-f0-9]{16}\.jpg/', $filename1);
    }
    
    // ==================== CSRF TOKEN TESTS ====================
    
    /**
     * Test CSRF token generation
     */
    public function testCsrfTokenGeneration(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Generate token
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        $token = $_SESSION['csrf_token'];
        
        $this->assertNotEmpty($token);
        $this->assertEquals(64, strlen($token)); // 32 bytes = 64 hex characters
    }
    
    /**
     * Test CSRF token verification
     */
    public function testCsrfTokenVerification(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        
        // Valid token should pass
        $this->assertTrue(hash_equals($_SESSION['csrf_token'], $token));
        
        // Invalid token should fail
        $invalidToken = bin2hex(random_bytes(32));
        $this->assertFalse(hash_equals($_SESSION['csrf_token'], $invalidToken));
    }
    
    // ==================== INTEGRATION TESTS ====================
    
    /**
     * Test complete customer creation workflow
     */
    public function testCompleteCustomerCreationWorkflow(): void
    {
        // Step 1: Validate data
        $customerData = [
            'lastname'   => 'Williams',
            'firstname'  => 'Sarah',
            'email'      => 'sarah.williams@example.com',
            'city'       => 'Tokyo',
            'country'    => 'Japan',
            'image_path' => 'uploads/customer_test.jpg',
        ];
        
        $isValid = $this->validator->validateCustomerData($customerData);
        $this->assertTrue($isValid, 'Data should be valid');
        
        // Step 2: Insert into database
        $customerId = $this->database->insertCustomer($customerData);
        $this->assertGreaterThan(0, $customerId, 'Customer ID should be positive');
        
        // Step 3: Retrieve and verify
        $savedCustomer = $this->database->getCustomerById($customerId);
        $this->assertNotNull($savedCustomer, 'Customer should be retrievable');
        $this->assertEquals($customerData['email'], $savedCustomer['email']);
        $this->assertEquals($customerData['country'], $savedCustomer['country']);
    }
    
    /**
     * Test error handling for invalid data
     */
    public function testErrorHandlingInvalidData(): void
    {
        $invalidData = [
            'lastname'  => '',
            'firstname' => 'Test',
            'email'     => 'invalid',
            'city'      => 'Test City',
            'country'   => 'Invalid',
        ];
        
        $isValid = $this->validator->validateCustomerData($invalidData);
        $this->assertFalse($isValid);
        
        $errors = $this->validator->getErrors();
        $this->assertNotEmpty($errors);
        $this->assertGreaterThanOrEqual(3, count($errors));
    }
    
    // ==================== EDGE CASE TESTS ====================
    
    /**
     * Test email validation with edge cases
     */
    public function testEmailValidationEdgeCases(): void
    {
        $testCases = [
            'user@example.com'           => true,
            'user.name@example.com'      => true,
            'user+tag@example.co.uk'     => true,
            'user123@test-domain.com'    => true,
            'invalid.email'              => false,
            '@example.com'               => false,
            'user@'                      => false,
            'user @example.com'          => false,
            ''                           => false,
        ];
        
        foreach ($testCases as $email => $shouldBeValid) {
            $data = [
                'lastname'  => 'Test',
                'firstname' => 'User',
                'email'     => $email,
                'city'      => 'Test',
                'country'   => 'Canada',
            ];
            
            $isValid = $this->validator->validateCustomerData($data);
            
            if ($shouldBeValid) {
                $this->assertTrue($isValid, "Email '{$email}' should be valid");
            } else {
                $this->assertFalse($isValid, "Email '{$email}' should be invalid");
            }
        }
    }
    
    /**
     * Test SQL injection prevention
     */
    public function testSQLInjectionPrevention(): void
    {
        $maliciousData = [
            'lastname'   => "'; DROP TABLE customers; --",
            'firstname'  => '<script>alert("xss")</script>',
            'email'      => 'test@example.com',
            'city'       => "1' OR '1'='1",
            'country'    => 'United States',
            'image_path' => null,
        ];
        
        // Should insert safely without SQL injection
        $customerId = $this->database->insertCustomer($maliciousData);
        $this->assertGreaterThan(0, $customerId);
        
        // Retrieve and verify data is stored as-is (not executed)
        $customer = $this->database->getCustomerById($customerId);
        $this->assertEquals("'; DROP TABLE customers; --", $customer['lastname']);
        
        // Verify table still exists (wasn't dropped)
        $stmt = $this->database->getConnection()->query('SELECT COUNT(*) FROM customers');
        $this->assertNotNull($stmt);
    }
}

// ==================== TEST RUNNER ====================

/**
 * Run tests if this file is executed directly
 */
if (php_sapi_name() === 'cli') {
    echo "\n";
    echo "===========================================\n";
    echo "  Customer Form PHP Unit Tests\n";
    echo "===========================================\n\n";
    echo "To run these tests with PHPUnit:\n";
    echo "1. Install PHPUnit: composer require --dev phpunit/phpunit\n";
    echo "2. Run: ./vendor/bin/phpunit CustomerFormTest.php\n\n";
    echo "Or run with verbose output:\n";
    echo "./vendor/bin/phpunit --testdox CustomerFormTest.php\n\n";
}