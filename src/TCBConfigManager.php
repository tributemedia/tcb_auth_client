<?php

namespace Drupal\tcb_auth_client;

use Drupal\user\Entity\Role;

/**
 * A class that provides functionality for accessing and setting
 * TCB client config values
 */
class TCBConfigManager {
  
  /**
   * Gets the configured URL that TCB client will get config info from.
   * @return string
   */
  public function getServerURL() {
    
    $url = \Drupal::config('tcb_auth_client.settings')
            ->get('server_url');
    
    return $url;
    
  }
  
  /**
   * Gets the protocol used to send requests to the TCB server
   * @return string
   */
  public function getServerProtocol() {
    
    $protocol = \Drupal::config('tcb_auth_client.settings')
                  ->get('server_protocol');
    return $protocol;
    
  }
  
  /**
   * Gets the site configuration information in its JSON form from the 
   * the TCB server
   * @return string
   */
  public function getSiteInfo() {
    
    return \Drupal::config('tcb_auth_client.settings')->get('site_info');
    
  }
  
  /**
   * Sets the server URL to get configuration info from.
   * @param string $newURL The URL to send requests to
   */
  public function setServerURL($newURL) {
    
    \Drupal::service('config.factory')
      ->getEditable('tcb_auth_client.settings')
      ->set('server_url', $newURL)
      ->save();
    
  }
  
  /**
   * Sets the protocol used to communicate with the TCB server
   * @param string $protocol The protocol used to communicate
   */
  public function setServerProtocol($protocol) {
    
    \Drupal::service('config.factory')
      ->getEditable('tcb_auth_client.settings')
      ->set('server_protocol', $protocol)
      ->save();
    
  }
  
  /**
   * Sets the JSON string returned from a TCB server
   * @param string $siteInfo The JSON string to save
   */
  public function setSiteInfo($siteInfo) {
    
    // Grab cached info
    $cachedSiteInfo = json_decode($this->getSiteInfo());
    
    // Save site info into cache
    \Drupal::service('config.factory')
      ->getEditable('tcb_auth_client.settings')
      ->set('site_info', $siteInfo)
      ->save();
      
    // Parse through roles, creating any new roles necessary
    $validRoles = json_decode($siteInfo)->valid_roles;
    
    foreach($validRoles as $validRole) {
      
      // Attempt to load role as though it exists
      $existingRole = Role::load(strtolower($validRole->name));
      
      // If the existingRole variable is empty or null, we know it 
      // doesn't exist. Proceed to create the role. Otherwise, continue.
      if(empty($existingRole)) {
        
        // Log message to note that we're creating a new role.
        \Drupal::logger('tcb_auth_client')
          ->notice('Creating new role: ' . $validRole->name);
          
        // Create the role and save it.
        // Creating a role with permissions that do not exist on the site
        // will not cause any errors, so it is safe to just copy over
        // the permissions specified from TCB Server without validating.
        $newRole = Role::create([
                    'id' => strtolower($validRole->name),
                    'label' => $validRole->name,
                    'permissions' => $validRole->permissions]);
        $newRole->save();
        
      }
      // If the role already exists...
      else {
        
        // Loop over each cached role, and see if the permissions
        // are different.
        // NOTE: Future optimization suggestion: Change this foreach 
        // to something else so that we're not continuously re-iterating
        // over the same array with each existing role encountered
        foreach($cachedSiteInfo->valid_roles as $cachedRole) {
          
          if($cachedRole->name == $validRole->name) {
            
            // Check to see if the permissions are different by hashing
            // the contents of the permissions arrays
            $cachedRolePermissionsHash = hash('sha256', implode('', 
                                          $cachedRole->permissions));
            $validRolePermissionsHash = hash('sha256', implode('', 
                                          $validRole->permissions));
                 
            // If the permissions are different, set the role permissions
            // to the contents of what was retrieved from the server
            if($cachedRolePermissionsHash != $validRolePermissionsHash) {
              
              $existingRole->set('permissions', $validRole->permissions);
              $existingRole->save();
              
              \Drupal::logger('tcb_auth_client')
                ->notice('Changed permissions on role: ' . $validRole->name);
              
            }
            
          }
          
        }
        
      }
      
    }
    
  }
  
}