<?php

namespace Drupal\tcb_auth_client;

use Drupal\user\Entity\Role;

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
  public function parsePermissionSet(&$perms = []) {
    
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
      
      \Drupal::logger('tcb_auth_client')
        ->error('Global key not implemented yet.');
      return ['error' => 'Global key not implemented yet.'];
      
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