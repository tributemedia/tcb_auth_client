<?php

namespace Drupal\tcb_auth_client\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tcb_auth_client\TCBConfigManager;
use Drupal\tcb_auth_client\TCBServerConnectionWorker;

class TCBClientSettingsForm extends ConfigFormBase {
  
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    
    return 'tcb_auth_client.settings';
    
  }
  
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    
    return 'tcb_auth_client_admin_settings';
    
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $formState = null) {
    
    $config = new TCBConfigManager();
    $defaultProtocol = $config->getServerProtocol();
    $siteConfigJSON = $config->getSiteInfo();
    
    // If no default protocol is set, make it HTTP be default
    if(empty($defaultProtocol)) {
      
      $defaultProtocol = 'http';
      
    }
    
    // If there is no config JSON, display text that informs the user
    // Otherwise, format the JSON (pretty print the string) so that 
    // it looks nice when displayed to the user.
    if(empty($siteConfigJSON)) {
      
      $siteConfigJSON = 'No configuration information to display.';
      
    }
    else {
      
      $siteConfigJSON = json_encode(json_decode($siteConfigJSON), 
                          JSON_PRETTY_PRINT);
      
    }
    
    // Setup form fields
    $form['server_url'] = [
      '#type' => 'textfield',
      '#title' => 'TCB Server Domain',
      '#description' => 'ex: www.tcbserver.com',
      '#default_value' => $config->getServerURL(),
    ];
    
    $form['protocol'] = [
      '#type' => 'select',
      '#title' => 'Server Protocol',
      '#default_value' => $defaultProtocol,
      '#options' => [
        'http' => 'HTTP',
        'https' => 'HTTPS',
      ],
    ];
    
    $form['config_json'] = [
      '#type' => 'textarea',
      '#title' => 'Config JSON',
      '#default_value' => $siteConfigJSON,
      '#disabled' => true,
    ];
    
    return parent::buildForm($form, $formState);
    
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(&$form, FormStateInterface $formState) {
    
    $config = new TCBConfigManager();
    
    $config->setServerURL($formState->getValue('server_url'));
    $config->setServerProtocol($formState->getValue('protocol'));
    
  }
  
  /**
   * {@inheritdoc}
   */
  public function validateForm(&$form, FormStateInterface $formState) {
    
    $serverWorker = new TCBServerConnectionWorker();
    $server = $formState->getValue('server_url');
    $protocol = $formState->getValue('protocol');
    
    if(!empty($server)) {
    
      $serverWorker->validateServerConnection($server, $protocol);
      $serverWorker->getServerInfo(false);
      drupal_set_message('TCB server URL saved.');
      
    }
    else {
    
      $formState->setErrorByName('server_url', 'Fill in a value for the ' . 
        ' TCB Server URL field.');
      
    }
    
  }
}