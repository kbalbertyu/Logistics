<?php
namespace Application\Model\Auth;

use Zend\Permissions\Acl\Acl;
use Zend\Permissions\Acl\Role\GenericRole as Role;
use Zend\Permissions\Acl\Resource\GenericResource as Resource;
use User\Model\User;
use Application\Model\Auth;

class Permission {
    
    /**
     * @var Acl
     */
    private static $acl;
    
    /**
     * @return Acl
     */
    public static function getAclInstance() {
        if (self::$acl == null) {
            self::$acl = self::loadAcl();
        }
        return self::$acl;
    }
        
    /**
     * @return Acl
     */
    private static function loadAcl() {
        $acl = new Acl();
        foreach (User::ROLES as $role => $parent) {
            $acl->addRole(new Role($role), $parent);
        }
        foreach (self::RESOURCES as $controller => $actions) {
            foreach ($actions as $action => $allowedRoles) {
                $resourceName = Auth::formatResourceName($controller, $action);
                
                $acl->addResource(new Resource($resourceName));
                $allowedRoles = (array) $allowedRoles;
                foreach ($allowedRoles as $role) {
                    $acl->allow($role, $resourceName);
                }
            }
        }
        return $acl;
        
    }
    
    private const RESOURCES = [
        'User\Controller\UserController' => [
            'edit' => 'member'
        ]
    ];
}