<?php
// ==================== DATABASE CLASS ====================

/**
 * Database connection and operations handler
 * Implements PDO with mysqlnd for optimal performance
 */
class Database
{
    private ?PDO $connection = null;
    
    /**
     * Get database connection using PDO with mysqlnd
     * 
     * @return PDO Database connection
     * @throws PDOException on connection failure
     */
    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            try {
                $dsn = sprintf(
                    'mysql:host=%s;dbname=%s;charset=%s',
                    DB_HOST,
                    DB_NAME,
                    DB_CHARSET
                );
                
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false, // Use native prepared statements
                    PDO::ATTR_PERSISTENT         => false,
                    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                    PDO::ATTR_TIMEOUT            => 5,
                ];
                
                $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
                
            } catch (PDOException $e) {
                error_log('Database connection error: ' . $e->getMessage());
                throw new PDOException('Database connection failed. Please try again later.');
            }
        }
        
        return $this->connection;
    }
    
    /**
     * Insert customer data into database
     * 
     * @param array $data Customer data
     * @return int Last inserted ID
     * @throws PDOException on database error
     */
    public function insertCustomer(array $data): int
    {
        $pdo = $this->getConnection();
        
        $sql = 'INSERT INTO customers (lastname, firstname, email, city, country, image_path) 
                VALUES (:lastname, :firstname, :email, :city, :country, :image_path)';
        
        $stmt = $pdo->prepare($sql);
        
        $stmt->execute([
            ':lastname'   => $data['lastname'],
            ':firstname'  => $data['firstname'],
            ':email'      => $data['email'],
            ':city'       => $data['city'],
            ':country'    => $data['country'],
            ':image_path' => $data['image_path'] ?? null,
        ]);
        
        return (int) $pdo->lastInsertId();
    }

    /**
     * Fetch customer details by email
     * 
     * @param string $email Customer email
     * @return array|null Customer data or null if not found
     * @throws PDOException on database error
     */
    public function fetchCustomerById(string $email): ?array
    {
        $pdo = $this->getConnection();
        
        $sql = 'SELECT * FROM customers WHERE email = :email';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => $email]);
        
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        return $customer !== false ? $customer : null;
    }
    
    /**
     * Get mysqlnd driver information
     * 
     * @return array Driver information
     */
    public function getDriverInfo(): array
    {
        try {
            $pdo = $this->getConnection();
            $clientVersion = $pdo->getAttribute(PDO::ATTR_CLIENT_VERSION);
            
            return [
                'driver'         => $pdo->getAttribute(PDO::ATTR_DRIVER_NAME),
                'client_version' => $clientVersion,
                'mysqlnd_active' => stripos($clientVersion, 'mysqlnd') !== false,
            ];
        } catch (PDOException $e) {
            return [
                'driver'         => 'Unknown',
                'client_version' => 'Unknown',
                'mysqlnd_active' => false,
                'error'          => $e->getMessage(),
            ];
        }
    }
}
?>