<?php
/**
 * Rubedo -- ECM solution
 * Copyright (c) 2013, WebTales (http://www.webtales.fr/).
 * All rights reserved.
 * licensing@webtales.fr
 *
 * Open Source License
 * ------------------------------------------------------------------------------------------
 * Rubedo is licensed under the terms of the Open Source GPL 3.0 license. 
 *
 * @category   Rubedo
 * @package    Rubedo
 * @copyright  Copyright (c) 2012-2013 WebTales (http://www.webtales.fr)
 * @license    http://www.gnu.org/licenses/gpl.html Open Source GPL 3.0 license
 */
namespace Rubedo\Security;

use Rubedo\Interfaces\Security\IAcl, Rubedo\Services\Manager, Rubedo\Collection\AbstractCollection;

/**
 * Interface of Access Control List Implementation
 *
 *
 * @author jbourdin
 * @category Rubedo
 * @package Rubedo
 */
class Acl implements IAcl
{

    /**
     * Path of the directory where role definition json are stored
     *
     * @var string
     */
    protected $_rolesDirectory;
    
    protected static $_hasAccessRults = array();

    public function __construct ()
    {
        $this->_rolesDirectory = realpath(APPLICATION_PATH . '/configs/roles');
    }

    /**
     * Check if the current user has access to a given resource for a given
     * access mode
     *
     * @param string $resource
     *            resource name
     * @return boolean
     */
    public function hasAccess ($resource)
    {
        if (! isset(self::$_hasAccessRults[$resource])) {
            $result = false;
            $currentUserService = Manager::getService('CurrentUser');
            
            $wasFiltered = AbstractCollection::disableUserFilter();
            $groups = $currentUserService->getGroups();
            AbstractCollection::disableUserFilter($wasFiltered);
            
            $roleArray = array();
            foreach ($groups as $group) {
                $roleArray = $this->_addGroupToRoleArray($roleArray, $group);
            }
            $wasFiltered = AbstractCollection::disableUserFilter();
            $roleArray = $this->_addGroupToRoleArray($roleArray, Manager::getService('Groups')->getPublicGroup());
            AbstractCollection::disableUserFilter($wasFiltered);
            
            foreach ($roleArray as $role) {
                if ($this->_roleHasAccess($resource, $role)) {
                    $result = true || $result;
                    break;
                }
            }
            self::$_hasAccessRults[$resource] = $result;
        }
        return self::$_hasAccessRults[$resource];
    }

    /**
     * add role of the group to the current role Array
     * 
     * @param array $roleArray
     * @param array $group
     * @return array
     */
    protected function _addGroupToRoleArray (array $roleArray, array $group=null)
    {
        if(is_null($group)){
            return $roleArray;
        }
        
        if (isset($group['roles']) && is_array($group['roles'])) {
            $groupRoleArray = array_values($group['roles']);
            $roleArray = array_merge($roleArray, $groupRoleArray);
        }
        return $roleArray;
    }

    /**
     * Check if a given role has access to the ressource
     * 
     * @param string $resource
     * @param string $role
     * @return boolean
     */
    protected function _roleHasAccess ($resource, $role)
    {
         // @todo temporary disabling workflow components
         if (strpos($resource, 'workflows') !== false) {
             return false;
         }
         
         // @todo temporary disabling  nested contents
         if (strpos($resource, 'dependantTypes') !== false) {
             return false;
         }
        
        if (is_null($role)) {
            return false;
        }
        
        $rightsArray = $this->_getRightsByRoleName($role);
        foreach ($rightsArray as $rightAccess) {
            $rightAccess = str_replace('.', '\.', $rightAccess);
            $rightAccess = str_replace('@dot', '.', $rightAccess);
            if (1 == preg_match('#' . $rightAccess . '#', $resource)) {
                return true;
            }
        }
        
        return false;
    }


    /**
     * Return the role configuration from its name
     *
     * Read infos from configs/role/jsonfile
     *
     * @param string $name            
     * @return array null
     */
    protected function _getRoleByName ($name)
    {
        $pathName = $this->_rolesDirectory . '/' . $name . '.json';
        if (is_file($pathName)) {
            $roleInfos = \Zend_Json::decode(file_get_contents($pathName));
            return $roleInfos;
        } else {
            return null;
        }
    }

    /**
     * Return the array of rights of a given role
     *
     * @param string $name            
     * @return array
     */
    protected function _getRightsByRoleName ($name,$max=5)
    {
        $rightsArray = array();
        $roleInfos = $this->_getRoleByName($name);
        if ($roleInfos) {
            $rightsArray = $roleInfos['rights'];
            if(isset($roleInfos['includes']) && $max > 0){
                foreach ($roleInfos['includes'] as $include){
                    $rightsArray = array_merge($rightsArray,$this->_getRightsByRoleName($include,$max - 1));
                }
            }
        }
        return $rightsArray;
    }

    /**
     * For a given list of ressource, build an array of authorized ressources
     *
     * @param array $ressourceArray
     *            array of ressources
     * @return array the array of boolean with ressource as key name
     */
    public function accessList (array $ressourceArray)
    {
        $aclArray = array();
        if (isset($this->_service)) {
            $object = $this->_service;
        } else {
            $object = $this;
        }
        foreach ($ressourceArray as $value) {
            $aclArray[$value] = $object->hasAccess($value);
        }
        return $aclArray;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \Rubedo\Interfaces\Security\IAcl::getAvailaibleRoles()
     */
    public function getAvailaibleRoles ()
    {
    	$userLang='en'; //default value
        $currentUserLanguage=Manager::getService('CurrentUser')->getLanguage();
        if (!empty($currentUserLanguage)){
        	$userLang=$currentUserLanguage;
        }
        $templateDirIterator = new \DirectoryIterator($this->_rolesDirectory);
        if (! $templateDirIterator) {
            throw new \Rubedo\Exceptions\Server('Can not instanciate iterator for role dir', "Exception67");
        }
        
        $rolesInfosArray = array();
        
        foreach ($templateDirIterator as $file) {
            if ($file->isDot() || $file->isDir()) {
                continue;
            }
            if ($file->getExtension() == 'json') {
                $roleJson = file_get_contents($file->getPathname());
                $roleInfos = \Zend_Json::decode($roleJson);
                $roleLabel = $roleInfos['label'][$userLang];
                $roleInfos['label'] = $roleLabel;
                unset($roleInfos['rights']);
                $rolesInfosArray[] = $roleInfos;
            }
        }
        
        $response = array();
        $response['total'] = count($rolesInfosArray);
        $response['data'] = $rolesInfosArray;
        $response['success'] = TRUE;
        $response['message'] = 'OK';
        
        return $response;
    }
}
