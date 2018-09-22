<?php
namespace User\Model;

use Application\Model\BaseModel;

/**
 * @property string username
 * @property string email
 * @property string password
 * @property integer level
 * @property string role
 */
class User extends BaseModel {

    private static $commonRegion = 'Other';
    
    private static $userRegionMappings = [
        'SF' => ['SFDS', 'SFPL'],
        'Dover' => ['DVDS', 'DVPL'],
        'RS' => ['RSDS', 'RSPL']
    ];
    
    public const DEFAULT_LEVEL = 20;
    
    public const DEFAULT_ROLE = 'guest';
    
    public const ROLES = [
        self::DEFAULT_ROLE => null,
        'member' => self::DEFAULT_ROLE,
        'finance' => 'member',
        'health' => 'member',
        'manager' => ['finance', 'health'],
        'admin' => 'manager'
    ];
    
    public const DEFAULT_ROUTES = [
        'health' => 'health',
        'finance' => 'user'
    ];

    public function exchangeArray(array $data) {
        parent::exchangeArray($data);
        $this->role = !empty($data['role']) ? $data['role'] : self::DEFAULT_ROLE;
    }
    
    public function isSuperUser() {
        return $this->level === 0;
    }
    
    public function isNormalUser() {
        return $this->level === 20;
    }
    
    public function isGroupUser() {
        return $this->level === 10;
    }
    
    public function getGroupRegions() {
        if ($this->isGroupUser()) {
            $regions = self::$userRegionMappings[$this->username];
            $regions[] = self::$commonRegion;
            return $regions;
        } elseif ($this->isNormalUser()) {
            return [
                $this->username,
                self::$commonRegion
            ];
        }
        return [];
    }
}
