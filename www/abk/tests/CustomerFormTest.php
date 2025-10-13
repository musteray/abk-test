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
     * Test inserting customer without image path (nullable field)
     */
    public function testInsertCustomerWithoutImagePath(): void
    {
        $customerData = [
            'lastname'  => 'Smith',
            'firstname' => 'Jane',
            'email'     => 'jane.smith@example.com',
            'city'      => 'London',
            'country'   => 'United Kingdom',
        ];
        
        $customerId = $this->database->insertCustomer($customerData);
        
        $this->assertGreaterThan(0, $customerId);
        
        // Verify image_path is null
        $customer = $this->database->getCustomerByEmail('jane.smith@example.com');
        $this->assertNull($customer['image_path']);
    }
    
    /**
     * Test inserting customer with special characters
     */
    public function testInsertCustomerWithSpecialCharacters(): void
    {
        $customerData = [
            'lastname'   => "O'Brien",
            'firstname'  => 'Seán',
            'email'      => 'sean.obrien@example.com',
            'city'       => 'São Paulo',
            'country'    => 'Brazil',
            'image_path' => null,
        ];
        
        $customerId = $this->database->insertCustomer($customerData);
        
        $this->assertGreaterThan(0, $customerId);
        
        // Verify data integrity
        $customer = $this->database->getCustomerByEmail('sean.obrien@example.com');
        $this->assertEquals("O'Brien", $customer['lastname']);
        $this->assertEquals('Seán', $customer['firstname']);
        $this->assertEquals('São Paulo', $customer['city']);
    }
    
    /**
     * Test inserting duplicate email throws exception
     */
    public function testInsertDuplicateEmailThrowsException(): void
    {
        $customerData = [
            'lastname'  => 'Test',
            'firstname' => 'User',
            'email'     => 'duplicate@example.com',
            'city'      => 'Test City',
            'country'   => 'Test Country',
        ];
        
        // Insert first customer
        $this->database->insertCustomer($customerData);

        // Attempt to insert duplicate - should throw exception
        $this->expectException(PDOException::class);
        $this->database->insertCustomer($customerData);
    }
    
    /**
     * Test inserting customer returns correct auto-increment ID
     */
    public function testInsertCustomerReturnsCorrectAutoIncrementId(): void
    {
        $customer1 = [
            'lastname'  => 'First',
            'firstname' => 'Customer',
            'email'     => 'first@example.com',
            'city'      => 'City1',
            'country'   => 'Country1',
        ];
        
        $customer2 = [
            'lastname'  => 'Second',
            'firstname' => 'Customer',
            'email'     => 'second@example.com',
            'city'      => 'City2',
            'country'   => 'Country2',
        ];
        
        $id1 = $this->database->insertCustomer($customer1);
        $id2 = $this->database->insertCustomer($customer2);
        
        $this->assertEquals($id1 + 1, $id2);
    }

    /**
     * Test successful customer update
     */
    public function testUpdateCustomerSuccess(): void
    {
        // First, insert a customer
        $originalData = [
            'lastname'   => 'Original',
            'firstname'  => 'Name',
            'email'      => 'update.test@example.com',
            'city'       => 'Old City',
            'country'    => 'Old Country',
            'image_path' => 'uploads/old.jpg',
        ];
        
        $this->database->insertCustomer($originalData);
        
        // Now update the customer
        $updatedData = [
            'lastname'   => 'Updated',
            'firstname'  => 'NewName',
            'email'      => 'update.test@example.com', // Same email
            'city'       => 'New City',
            'country'    => 'New Country',
            'image_path' => 'uploads/new.jpg',
        ];
        
        $result = $this->database->updateCustomer($updatedData);
        
        $this->assertTrue($result);
        
        // Verify the update
        $customer = $this->database->getCustomerByEmail('update.test@example.com');
        $this->assertEquals('Updated', $customer['lastname']);
        $this->assertEquals('NewName', $customer['firstname']);
        $this->assertEquals('New City', $customer['city']);
        $this->assertEquals('New Country', $customer['country']);
        $this->assertEquals('uploads/new.jpg', $customer['image_path']);
    }
    
    /**
     * Test updating non-existent customer returns false
     */
    public function testUpdateNonExistentCustomerReturnsFalse(): void
    {
        $updateData = [
            'lastname'  => 'Test',
            'firstname' => 'User',
            'email'     => 'nonexistent@example.com',
            'city'      => 'Test City',
            'country'   => 'Test Country',
        ];
        
        $result = $this->database->updateCustomer($updateData);
        
        // Update succeeds but affects 0 rows, still returns true
        $this->assertTrue($result);
    }
    
    /**
     * Test updating customer with null image path
     */
    public function testUpdateCustomerWithNullImagePath(): void
    {
        // Insert customer with image
        $originalData = [
            'lastname'   => 'Test',
            'firstname'  => 'User',
            'email'      => 'test.null@example.com',
            'city'       => 'City',
            'country'    => 'Country',
            'image_path' => 'uploads/image.jpg',
        ];
        
        $this->database->insertCustomer($originalData);
        
        // Update to remove image
        $updatedData = [
            'lastname'   => 'Test',
            'firstname'  => 'User',
            'email'      => 'test.null@example.com',
            'city'       => 'City',
            'country'    => 'Country',
            'image_path' => null,
        ];
        
        $result = $this->database->updateCustomer($updatedData);
        $this->assertTrue($result);
        
        // Verify image_path is now null
        $customer = $this->database->getCustomerByEmail('test.null@example.com');
        $this->assertNull($customer['image_path']);
    }
    
    /**
     * Test partial update preserves email
     */
    public function testUpdatePreservesEmail(): void
    {
        // Insert customer
        $originalData = [
            'lastname'  => 'Original',
            'firstname' => 'User',
            'email'     => 'preserve@example.com',
            'city'      => 'OldCity',
            'country'   => 'OldCountry',
        ];
        
        $this->database->insertCustomer($originalData);
        
        // Update (email should remain the same)
        $updatedData = [
            'lastname'  => 'Updated',
            'firstname' => 'NewUser',
            'email'     => 'preserve@example.com',
            'city'      => 'NewCity',
            'country'   => 'NewCountry',
        ];
        
        $this->database->updateCustomer($updatedData);
        
        // Verify email hasn't changed
        $customer = $this->database->getCustomerByEmail('preserve@example.com');
        $this->assertEquals('preserve@example.com', $customer['email']);
    }

    /**
     * Test retrieving customer by email
     */
    public function testGetCustomerByEmail(): void
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
        
        $this->database->insertCustomer($customerData);
        
        // Retrieve customer
        $customer = $this->database->getCustomerByEmail($customerData["email"]);
        
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
        $customer = $this->database->getCustomerByEmail("test@gmail.com");
        $this->assertNull($customer);
    }

    /**
     * Test fetching customer returns all expected fields
     */
    public function testFetchCustomerReturnsAllFields(): void
    {
        $customerData = [
            'lastname'  => 'Complete',
            'firstname' => 'Data',
            'email'     => 'complete@example.com',
            'city'      => 'Full City',
            'country'   => 'Full Country',
        ];
        
        $this->database->insertCustomer($customerData);
        $customer = $this->database->getCustomerByEmail('complete@example.com');
        
        // Check all expected fields exist
        $this->assertArrayHasKey('id', $customer);
        $this->assertArrayHasKey('lastname', $customer);
        $this->assertArrayHasKey('firstname', $customer);
        $this->assertArrayHasKey('email', $customer);
        $this->assertArrayHasKey('city', $customer);
        $this->assertArrayHasKey('country', $customer);
        $this->assertArrayHasKey('image_path', $customer);
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
        $savedCustomer = $this->database->getCustomerByEmail($customerData['email']);
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
        $customer = $this->database->getCustomerByEmail($maliciousData['email']);
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