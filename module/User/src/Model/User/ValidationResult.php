<?php
namespace User\Model\User;

class ValidationResult {
    private $code = 0;
    private $message;
    
    public function setMessage($message) {
        $this->message = $message;
    }
    
    public function getMessage() {
        return $this->message;
    }
    
    public function setFail() {
        $this->code = 0;
    }
    
    public function setSuccess() {
        $this->code = 1;
    }
    
    public function isValid() {
        return $this->code == 1;
    }
}