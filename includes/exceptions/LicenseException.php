<?php
namespace SosyalliftAIPro\Includes\Exceptions;

class LicenseException extends \Exception {
    private string $error_type;
    
    public function __construct(string $message, string $error_type = 'general', int $code = 0, \Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->error_type = $error_type;
    }
    
    public function getErrorType(): string {
        return $this->error_type;
    }
}