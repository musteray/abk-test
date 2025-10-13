<?php
// ==================== FILE UPLOAD HANDLER ====================

/**
 * Secure file upload handler with validation
 */
class FileUploadHandler
{
    /**
     * Handle file upload with security checks
     * 
     * @param array $file $_FILES array element
     * @return string|null Path to uploaded file or null on error
     * @throws RuntimeException on upload error
     */
    public function upload(array $file): ?string
    {
        // Check if file was uploaded
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('File upload failed.');
        }
        
        // Validate file size
        if ($file['size'] > MAX_FILE_SIZE) {
            throw new RuntimeException('File size exceeds maximum allowed size of 5MB.');
        }
        
        // Validate MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, ALLOWED_MIME_TYPES, true)) {
            throw new RuntimeException('Only JPEG images are allowed.');
        }
        
        // Validate file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ALLOWED_EXTENSIONS, true)) {
            throw new RuntimeException('Invalid file extension. Only JPG/JPEG allowed.');
        }
        
        // Create upload directory if it doesn't exist
        if (!is_dir(UPLOAD_DIR)) {
            if (!mkdir(UPLOAD_DIR, 0755, true) && !is_dir(UPLOAD_DIR)) {
                throw new RuntimeException('Failed to create upload directory.');
            }
        }
        
        // Generate secure filename
        $newFilename = $this->generateSecureFilename($extension);
        $destination = UPLOAD_DIR . $newFilename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new RuntimeException('Failed to move uploaded file.');
        }
        
        // Return relative path for database storage
        return 'uploads/' . $newFilename;
    }
    
    /**
     * Generate secure random filename
     * 
     * @param string $extension File extension
     * @return string Secure filename
     */
    private function generateSecureFilename(string $extension): string
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