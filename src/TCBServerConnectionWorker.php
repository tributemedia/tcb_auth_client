<?php

namespace Drupal\tcb_auth_client;

use Drupal\tcb_auth_client\TCBConfigManager;

/**
 * Handles connecting to TCB server to retrieve information.
 */
class TCBServerConnectionWorker {
  
  private $host;
  private $tcbConfig;
  
  public function __construct() {
    
    $this->host = \Drupal::request()->getHost();
    $this->tcbConfig = new TCBConfigManager();
    
  }
  
  /**
   * Attempts to connect to a passed in server as though it was a TCB server.
   * If it is valid, the server host and protocol get saved. If there are
   * any problems connecting, the exceptions are handled in the 
   * ExceptionEventSubscriber class.
   * @param string $server The host to attempt to connect to.
   * @param string $protocol The protocol to use to connect to the host.
   */
  public function validateServerConnection($server, $protocol) {
    
    $this->getServerRequest($server, $protocol);
    $this->tcbConfig->setServerURL($server);
    $this->tcbConfig->setServerProtocol($protocol);
    
  }
  
  /**
   * Gets either cached server information from the TCB server, or fresh
   * information, depending on the value passed in.
   * @param boolean $referToCache Get info from cache or retrieve from server
   * @return JSONObject
   */
  public function getServerInfo($referToCache = true) {
    
    // If referToCache is true, just return cached json
    if($referToCache) {
      
      return $this->tcbConfig->getSiteInfo();
      
    }
    // Otherwise, read information from the TCB server and save it into
    // the cache for later retrieval.
    else {
      
      $server = $this->tcbConfig->getServerURL();
      $protocol = $this->tcbConfig->getServerProtocol();
      
      if(empty($server)) {
          
        \Drupal::logger('tcb_auth_client')
          ->error('Attempt to connect to server with no valid stored URL.');
        
        return '';
          
      }
      
      $request = $this->getServerRequest($server, $protocol);
      $responseStr = $request->getBody()->getContents();
    
      $this->tcbConfig->setSiteInfo($responseStr);
      
      return $responseStr;
    }
    
  }
  
  /**
   * Connects to a passed in server and returns the request object.
   * @param string $server The host to connect to.
   * @param string $protocol The protocol to use when connecting.
   * @return Psr\Http\Message\ResponseInterface 
   */
  private function getServerRequest($server, $protocol) {
    
    $connection = \Drupal::httpClient();
    $requestURL = $protocol . '://' . $server . 
      '/api/v1/site?_format=json&name=' .
      $this->host . 
      '&time=' . time();
    
    return $connection->request('GET', $requestURL);
    
  }
  
}