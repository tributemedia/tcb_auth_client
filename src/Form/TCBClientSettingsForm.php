<?php

namespace Drupal\tcb_auth_client\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tcb_auth_client\TCBConfigManager;

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
    $form['server_url'] = [
      '#type' => 'textfield',
      '#title' => 'TCB Server URL',
      '#default_value' => $config->getServerURL(),
    ];
    
    return parent::buildForm($form, $formState);
    
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(&$form, FormStateInterface $formState) {
    
    $config = new TCBConfigManager();
    
    $config->setServerURL($formState->getValue('server_url'));
    
  }
}