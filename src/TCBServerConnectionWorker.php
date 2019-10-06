<?php

namespace Drupal\tcb_auth_client;

use Drupal\tcb_auth_client\TCBConfigManager;

/**
 * Handles connecting to TCB server to retrieve information.
 */
class TCBServerConnectionWorker {
  
  private $host;
  private $tcbConfig;
  private $endpoint;
  
  public function __construct() {
    
    $this->host = \Drupal::request()->getHost();
    $this->tcbConfig = new TCBConfigManager();
    $this->endpoint = 'site';
    
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
   * Gets the information about a user from TCB Server based on the 
   * email address passed in. The information is returned in the form of
   * a JSON string.
   * @param string $email The user's email to query.
   * @return JSONObject
   */
  public function getUserInfo($email) {
    
    $server = $this->tcbConfig->getServerURL();
    $protocol = $this->tcbConfig->getServerProtocol();
      
    if(empty($server)) {
        
      \Drupal::logger('tcb_auth_client')
        ->error('Attempt to connect to server with no valid stored URL.');
      
      return '';
        
    }
    
    $request = $this->getUserRequest($server, $protocol, $email);
    return $request->getBody()->getContents();
    
  }
  
  /**
   * Gets the information about a TCB Role from the TCB server based on
   * the name of the role passed in.
   * @param string $name The name of the TCB Role to query the server for.
   * @return JSONObject
   */
  public function getRoleInfo($name) {
    
    $server = $this->tcbConfig->getServerURL();
    $protocol = $this->tcbConfig->getServerProtocol();
      
    if(empty($server)) {
        
      \Drupal::logger('tcb_auth_client')
        ->error('Attempt to connect to server with no valid stored URL.');
      
      return '';
        
    }
    
    $request = $this->getRoleRequest($server, $protocol, $name);
    return $request->getBody()->getContents();
    
  }
  
  /**
   * Makes the worker connect to the site endpoint.
   */
  public function querySiteEndpoint() {
    
    $this->endpoint = 'site';
    
  }
  
  /**
   * Makes the worker connect to the user endpoint.
   */
  public function queryUserEndpoint() {
    
    $this->endpoint = 'user';
    
  }
  
  /**
   * Makes the worker connect to the role endpoint.
   */
  public function queryRoleEndpoint() {
    
    $this->endpoint = 'role';
    
  }
  
  /**
   * Connects to TCB Server and queries the user endpoint for a user
   * with the passed in email.
   * @param string $server The host to connect to.
   * @param string $protocol The protocol to use when connecting.
   * @param string $email The email to search for.
   * @return Psr\Http\Message\ResponseInterface
   */
  private function getUserRequest($server, $protocol, $email) {
    
    // Change the query endpoint to user if it's something else
    if($this->endpoint != 'user') {
      
      $this->queryUserEndpoint();
      
    }
    
    // Make the connection, and return the connection object
    $connection = \Drupal::httpClient();
    $requestURL = $protocol . '://' . $server . 
      '/api/v1/' . $this->endpoint . '?_format=json&email=' .
      $email . 
      '&time=' . time();
    
    return $connection->request('GET', $requestURL);
    
  }
  
  /**
   * Connects to a passed in server and returns the request object.
   * @param string $server The host to connect to.
   * @param string $protocol The protocol to use when connecting.
   * @return Psr\Http\Message\ResponseInterface 
   */
  private function getServerRequest($server, $protocol) {
    
    // If the worker is not set to query the site endpoint, change it.
    if($this->endpoint != 'site') {
      
      $this->querySiteEndpoint();
      
    }
    
    $connection = \Drupal::httpClient();
    $requestURL = $protocol . '://' . $server . 
      '/api/v1/' . $this->endpoint . '?_format=json&name=' .
      $this->host . 
      '&time=' . time();
    
    return $connection->request('GET', $requestURL);
    
  }
  
  /**
   * Connects to TCB and returns request object for role request.
   * @param string $server The host to connect to.
   * @param string $protocol The protocol to use when connecting.
   * @param string $name The name of the TCB Role to query.
   * @return Psr\Http\Message\ResponseInterface 
   */
  private function getRoleRequest($server, $protocol, $name) {
    
    // If the worker is not set to query the role endpoint, change it.
    if($this->endpoint != 'role') {
      
      $this->queryRoleEndpoint();
      
    }
    
    $connection = \Drupal::httpClient();
    $requestURL = $protocol . '://' . $server . 
      '/api/v1/' . $this->endpoint . '?_format=json&name=' .
      $name . 
      '&time=' . time();
    
    return $connection->request('GET', $requestURL);
    
  }
  
}