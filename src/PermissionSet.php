<?php

namespace Drupal\tcb_auth_client;

use Drupal\user\Entity\Role;
use Drupal\tcb_auth_client\TCBConfigManager;
use Drupal\tcb_auth_client\TCBServerConnectionWorker;

class PermissionSet {
  
  private $permStr;
  private $validated;
  
  /**
   * Constructs a new permission set.
   * @param string $permStr The string value containing the permission set
   */
  public function __construct($permStr) {
    
    $this->permStr = $permStr;
    $this->validated = FALSE;
    
  }
  
  /**
   * Parses a permission set to return the permissions associated with
   * the permission set (role). An error is returned if the set doesn't
   * validate or a role can't be found.
   * @return array
   */
  public function parsePermissionSet(&$perms = [], &$iterations = 0) {
    
    // Only allow so many recursive calls of parsePermissionSet
    if($iterations >= 5) {
      
      \Drupal::logger('tcb_auth_client')
        ->error('No more than 5 nested permission sets are supported.');
        
      return ['permissions' => $perms];
      
    }
    
    // If not validated, validate. If validation fails, return error.
    if(!$this->validated) {
      
      $this->validate();
      
      if(!$this->validated) {
        
        return ['error' => 'Permission set does not validate.'];
        
      }
      
    }
    
    $permSetKey = $this->getSetKey();
    
    // If we're parsing a permissions from a local role
    if($permSetKey == 'local') {
      
      $roleToGet = $this->getSetValue();
      $localRole = Role::load(strtolower($roleToGet));
      
      // If the role exists locally, return the permissions.
      // Otherwise return an error.
      if(!empty($localRole)) {
        
        $tempPerms = $localRole->getPermissions();
        
        foreach($tempPerms as $temp) {
          
          $perms[] = $temp;
          
        }
        
        return ['permissions' => $perms];
        
      }
      else {
        
        \Drupal::logger('tcb_auth_client')
          ->error('Attempt to get permissions on non-existent role: ' . 
            $roleToGet);
        return ['error' => 'Role ' . $roleToGet . ' does not exist locally.'];
        
      }
      
    }
    else {
      
      $roleToGet = $this->getSetValue();
      $worker = new TCBServerConnectionWorker();
      $globalRole = json_decode($worker->getRoleInfo($roleToGet));
      
      // If there was no error getting the information, continue. Otherwise,
      // return an error.
      if(empty($globalRole->error)) {
        
        $configManager = new TCBConfigManager();
        $validRoles = json_decode($configManager->getSiteInfo())->valid_roles;
        $isValidRole = FALSE;
        
        // Make sure the global role being pointed to is a valid role
        foreach($validRoles as $validRole) {
          
          if($globalRole->name == $validRole->name) {
            
            $isValidRole = TRUE;
            break;
            
          }
          
        }
        
        // If not a valid role, error out.
        if(!$isValidRole) {
          
          \Drupal::logger('tcb_auth_client')
            ->error('Attempt to retrieve permissions on invalid role: ' .
              $globalRole->name);
          return ['error' => 'Attempt to get permissions on invalid role.'];
          
        }
        
        // Loop over each permission in the role and add it. If another
        // permission set is in the role's permissions, parse the permissions
        // out of it and add them to the list.
        foreach($globalRole->permissions as $permission) {
          
          if(strpos($permission, '{') === FALSE) {
        
            $perms[] = $permission;
        
          }
          else {
            
            $nestedPermSet = new PermissionSet($permission);
            $iterations += 1;
            $checkForError = $nestedPermSet->parsePermissionSet($perms, 
                              $iterations);
              
            // If an error was found parsing, return the error.
            if(!empty($checkForError['error'])) {
              
              \Drupal::logger('tcb_auth_client')
                ->error('Error parsing nested global permissions set.');
              
              return $checkForError;
              
            }
            
          }
          
        }
        
        return ['permissions' => $perms];
        
      }
      else {
        
        \Drupal::logger('tcb_auth_client')
          ->error($roleToGet . ' is not a TCB server role.');
        return ['error' => $roleToGet . ' is not a TCB server role.'];
        
      }
      
    }
    
  }
  
  /**
   * Gets the permission set key. Either global, or local.
   * @return string
   */
  private function getSetKey() {
    
    return substr($this->permStr, strpos($this->permStr, '{') + 1, 
            strpos($this->permStr, ':') - 1);
    
  }
  
  /**
   * Gets the permission set value (the name of a role).
   * @return string
   */
  private function getSetValue() {
    
    $colonPos = strpos($this->permStr, ':');
    $endBracePos = strpos($this->permStr, '}');
    
    return substr($this->permStr, $colonPos + 1, 
            ($endBracePos - $colonPos) - 1);
    
  }
  
  /**
   * To validate, a permission set must adhere to the following:
   * No more than two braces (an opening and closing brace)
   * Must contain one colon (:) to separate the key value
   * key must be 'global' or 'local'
   */
  private function validate() {
    
    $numOpenBraces = substr_count($this->permStr, '{');
    $numCloseBraces = substr_count($this->permStr, '}');
    $numColons = substr_count($this->permStr, ':');
    
    if($numOpenBraces == 1 && $numCloseBraces == 1 && $numColons == 1) {
      
      $setKey = $this->getSetKey();
                  
      if($setKey == 'local' || $setKey == 'global') {
        
        $this->validated = TRUE;
        
      }
      else {
        
        $this->validated =  FALSE;
        
      }
      
    }
    else {
      
      $this->validated = FALSE;
      
    }
    
  }
  
}