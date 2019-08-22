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
    
    if(empty($defaultProtocol)) {
      
      $defaultProtocol = 'http';
      
    }
    
    $form['server_url'] = [
      '#type' => 'textfield',
      '#title' => 'TCB Server URL',
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