<?php
namespace SosyalliftAIPro\Includes\Exceptions;

class ApiException extends \Exception {
    private array $context;
    
    public function __construct(string $message, array $context = [], int $code = 0, \Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }
    
    public function getContext(): array {
        return $this->context;
    }
    
    public function toArray(): array {
        return [
            'error' => $this->getMessage(),
            'code' => $this->getCode(),
            'context' => $this->context
        ];
    }
}