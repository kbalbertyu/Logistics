<?php
namespace Application\Model;

use Application\Model\Auth\Permission;
use Application\Controller\AbstractBaseController;

class Auth {
    function isAllow(AbstractBaseController $controllerObj, $user) {
        $routeParams = $controllerObj->params()->fromRoute();
        $resourceName = self::formatResourceName($routeParams['controller'], $routeParams['action']);
        $role = !empty($user) ? $user->role : 'guest';
        $acl = Permission::getAclInstance();
        if (!$acl->hasResource($resourceName)) {
            return false;
        }
        
        if ($acl->isAllowed($role, $resourceName)) {
            return true;
        }
        return false;
    }
    
    public static function formatResourceName($controller, $action) {
        return $controller . ':' . $action;
    }
}