<?php
class DatabaseMock
{
    private ?PDO $connection = null;
    
    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            // Use SQLite in-memory database for testing
            $this->connection = new PDO('sqlite::memory:');
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->createTestTable();
        }
        return $this->connection;
    }

    /**
     * Clean up after each test
     * Removes test data
     */
    protected function tearDown(): void
    {
        $pdo = $this->getConnection();

        if ($pdo !== null) {
            // Clean up test data
            $pdo->exec('TRUNCATE TABLE customers');
        }
    }
    
    private function createTestTable(): void
    {
        // Create table with UNIQUE constraint on email
        $sql = 'CREATE TABLE IF NOT EXISTS customers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            lastname VARCHAR(255) NOT NULL,
            firstname VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            city VARCHAR(255) NOT NULL,
            country VARCHAR(255) NOT NULL,
            image_path VARCHAR(500) DEFAULT NULL
        )';
        
        $this->connection->exec($sql);
    }
    
    public function insertCustomer(array $data): int
    {
        $pdo = $this->getConnection();
        
        $sql = 'INSERT INTO customers (lastname, firstname, email, city, country, image_path) 
                VALUES (:lastname, :firstname, :email, :city, :country, :image_path)';
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);
        
        return (int) $pdo->lastInsertId();
    }
    
    public function getCustomerByEmail(string $email): ?array
    {
        $pdo = $this->getConnection();
        $stmt = $pdo->prepare('SELECT * FROM customers WHERE email = :email');
        $stmt->execute([':email' => $email]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
    
    public function updateCustomer(array $data): bool
    {
        $pdo = $this->getConnection();
        
        $sql = 'UPDATE customers SET 
                    lastname = :lastname, 
                    firstname = :firstname, 
                    city = :city, 
                    country = :country, 
                    image_path = :image_path 
                WHERE email = :email';
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($data);
    }
}

class ValidatorMock
{
    private array $errors = [];
    
    public function validateCustomerData(array $data): bool
    {
        $this->errors = [];
        
        if (empty($data['lastname']) || !is_string($data['lastname'])) {
            $this->errors[] = 'Last name is required.';
        }
        
        if (empty($data['firstname']) || !is_string($data['firstname'])) {
            $this->errors[] = 'First name is required.';
        }
        
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = 'Valid email is required.';
        }
        
        if (empty($data['city']) || !is_string($data['city'])) {
            $this->errors[] = 'City is required.';
        }
        
        $validCountries = ['United States', 'Canada', 'Japan', 'United Kingdom', 'France', 'Germany'];
        if (empty($data['country']) || !in_array($data['country'], $validCountries, true)) {
            $this->errors[] = 'Valid country is required.';
        }
        
        return empty($this->errors);
    }
    
    public function getErrors(): array
    {
        return $this->errors;
    }
}

class FileUploadHandlerMock
{
    public function validateFileType(string $filename): bool
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($extension, ['jpg', 'jpeg'], true);
    }
    
    public function validateFileSize(int $size): bool
    {
        return $size <= 5242880; // 5MB
    }
    
    public function generateSecureFilename(string $extension): string
    {
        return sprintf(
            'customer_%s_%s.%s',
            date('Ymd_His'),
            bin2hex(random_bytes(8)),
            $extension
        );
    }
}
?>