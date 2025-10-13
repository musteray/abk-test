<?php
// ==================== VALIDATOR CLASS ====================

/**
 * Input validation handler
 */
class Validator
{
    private array $errors = [];
    
    /**
     * Validate customer form data
     * 
     * @param array $data Form data to validate
     * @return bool True if validation passes
     */
    public function validateCustomerData(array $data): bool
    {
        $this->errors = [];
        
        // Validate lastname
        if (empty($data['lastname']) || !is_string($data['lastname'])) {
            $this->errors[] = 'Last name is required.';
        } elseif (strlen($data['lastname']) > 255) {
            $this->errors[] = 'Last name must not exceed 255 characters.';
        }
        
        // Validate firstname
        if (empty($data['firstname']) || !is_string($data['firstname'])) {
            $this->errors[] = 'First name is required.';
        } elseif (strlen($data['firstname']) > 255) {
            $this->errors[] = 'First name must not exceed 255 characters.';
        }
        
        // Validate email
        if (empty($data['email']) || !is_string($data['email'])) {
            $this->errors[] = 'Email is required.';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = 'Please enter a valid email address.';
        } elseif (strlen($data['email']) > 255) {
            $this->errors[] = 'Email must not exceed 255 characters.';
        }
        
        // Validate city
        if (empty($data['city']) || !is_string($data['city'])) {
            $this->errors[] = 'City is required.';
        } elseif (strlen($data['city']) > 255) {
            $this->errors[] = 'City must not exceed 255 characters.';
        }
        
        // Validate country
        $validCountries = [
            'United States',
            'Canada',
            'Japan',
            'United Kingdom',
            'France',
            'Germany'
        ];
        
        if (empty($data['country']) || !in_array($data['country'], $validCountries, true)) {
            $this->errors[] = 'Please select a valid country.';
        }
        
        return empty($this->errors);
    }
    
    /**
     * Get validation errors
     * 
     * @return array Validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
?>