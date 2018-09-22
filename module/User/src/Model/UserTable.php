<?php
namespace User\Model;

use Application\Model\BaseTable;
use User\Model\User\ValidationResult;
use Zend\Crypt\Password\Bcrypt;
use Zend\Authentication\Result;

class UserTable extends BaseTable {
    
    /**
     * 
     * @return Bcrypt
     */
    public function getCrypter() {
        if (!self::$crypter) {
            self::$crypter = new Bcrypt();
        }
        return self::$crypter;
    }
    
    /**
     * 
     * @param array $data
     * @return Result
     */
    public function auth($data) {
        $username = trim($data['username']);
        $password = trim($data['password']);
        
        $userRow = $this->getRowById($username);
        if (!$userRow) {
            $code = Result::FAILURE_IDENTITY_NOT_FOUND;
            $identity = null;
        } else {
            if ($this->getCrypter()->verify($password, $userRow->password)) {
                $code = Result::SUCCESS;
            } else {
                $code = Result::FAILURE;
            }
            $identity = $userRow->username;
        }
        return new Result($code, $identity, array());
    }
    
    /**
     * 
     * @param array $data
     * @return ValidationResult
     */
    public function register($data) {
        $validationResult = new ValidationResult();
        $username = trim($data['username']);
        if (empty($username)) {
            $validationResult->setMessage('Please provide a username.');
            return $validationResult;
        }
        
        $password = trim($data['password']);
        $confirm = trim($data['confirm_password']);
        
        if (empty($password) || empty($confirm)) {
            $validationResult->setMessage('Please provide a password or confirm password.');
            return $validationResult;
        }
        
        if ($password != $confirm) {
            $validationResult->setMessage('Please provide the same password.');
            return $validationResult;
        }
        
        $find = $this->getRowById($username);
        if ($find) {
            $validationResult->setMessage('Username is already in use, please try another one.');
            return $validationResult;
        }
        $this->tableGateway->insert([
            'username' => $username,
            'email' => $username . '@ibport.com',
            'password' => $this->getCrypter()->create($password),
        ]);
        
        $validationResult->setSuccess();
        return $validationResult;
    }
    
    public function edit($data, $username) {
        $validationResult = new ValidationResult();
        
        $password = trim($data['password']);
        $confirm = trim($data['confirm_password']);
        
        if (empty($password) || empty($confirm)) {
            $validationResult->setMessage('Please provide a password or confirm password.');
            return $validationResult;
        }
        
        if ($password != $confirm) {
            $validationResult->setMessage('Please provide the same password.');
            return $validationResult;
        }
        
        $this->tableGateway->update([
            'password' => $this->getCrypter()->create($password)
        ], 'username = \'' . $username . '\'');
        
        $validationResult->setSuccess();
        return $validationResult;
    }

    protected $primary = 'username';
    /**
     *
     * @var Bcrypt
     */
    private static $crypter;
}