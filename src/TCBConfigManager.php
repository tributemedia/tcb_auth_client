<?php

namespace Drupal\tcb_auth_client;

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
   * Sets the server URL to get configuration info from.
   * @param string $newURL The URL to send requests to
   */
  public function setServerURL($newURL) {
    
    \Drupal::service('config.factory')
      ->getEditable('tcb_auth_client.settings')
      ->set('server_url', $newURL)
      ->save();
    
  }
  
}