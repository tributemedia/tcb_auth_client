<?php

namespace Drupal\tcb_auth_client\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Transliteration\PhpTransliteration;
use Drupal\Core\Utility\Token;
use Drupal\social_api\User\UserManager as SocialApiUserManager;
use Drupal\social_auth\User\SocialAuthUserInterface;
use Drupal\social_auth\Event\SocialAuthEvents;
use Drupal\social_auth\Event\UserEvent;
use Drupal\social_auth\Event\SocialAuthUserFieldsEvent;
use Drupal\social_auth\SettingsTrait;
use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\social_auth\User\UserManager;
use Drupal\tcb_auth_client\TCBConfigManager;

/**
 * Defines a customized instance of social auth's UserManager class
 */
class TCBUserManager extends UserManager {

  public function __construct(UserManager $theParent) {

    parent::__construct($theParent->entityTypeManager, $theParent->messenger, 
      $theParent->loggerFactory, $theParent->configFactory,
      $theParent->entityFieldManager, $theParent->transliteration, 
      $theParent->languageManager, $theParent->eventDispatcher, 
      $theParent->token);
    //$this->setPluginId('tcb_auth_client');

  }

   /**
   * Create a new user account.
   *
   * @param string $name
   *   User's name on Provider.
   * @param string $email
   *   User's email address.
   *
   * @return \Drupal\user\Entity\User|false
   *   Drupal user account if user was created
   *   False otherwise
   */
  public function createUser(SocialAuthUserInterface $user) {

    $name = $user->getName();
    $email = $user->getEmail();
  
    // Check email domain to make sure it is allowed for account creation
    $domain = explode('@', $email)[1];
    $tcbConfig = new TCBConfigManager();
    $tcbInfo = json_decode($tcbConfig->getSiteInfo());
    
    if(!empty($tcbInfo->valid_domains)) {
      
      $domainIsValid = FALSE;
      
      // Loop over each valid domain and if it matches the domain
      // of the user trying to create an account, the user is allowed
      // to create an account on the site
      foreach($tcbInfo->valid_domains as $validDomain) {
        
        if($domain == $validDomain) {
          
          $domainIsValid = TRUE;
          break;
          
        }
        
      }
      
      if($domainIsValid == FALSE) {
        
        $this->failUserCreation();
        
        return FALSE;
        
      }
      else {
        
        $this->loggerFactory->get($this->getPluginId())
          ->notice('User with VALID domain attempted to create account.');
        
      }
      
    }
    // If no valid domains are specified, fail any attempt to create
    // a user
    else {
      
      $this->failUserCreation();
      return FALSE;
      
    }
    
    // Check if site configuration allows new users to register.
    /*
    if ($this->isRegistrationDisabled()) {
      $this->loggerFactory
        ->get($this->getPluginId())
        ->warning('Failed to create user. User registration is disabled. 
          Name: @name, email: @email.', ['@name' => $name, 
            '@email' => $email]);

      return FALSE;
    }
    */

    // Get the current UI language.
    $langcode = $this->languageManager->getCurrentLanguage()->getId();

    // Try to save the new user account.
    try {
      // Initializes the user fields.
      $fields = $this->getUserFields($user, $langcode);

      /** @var \Drupal\user\Entity\User $new_user */
      $new_user = $this->entityTypeManager
        ->getStorage('user')
        ->create($fields);
        
      $new_user->save();
      
      // If the newly created user is blocked by default, unblock
      if($new_user->isBlocked()) {
        $this->loggerFactory
          ->get($this->getPluginId())
          ->notice('New user blocked, unblocking...');
        $new_user->activate();
        $new_user->save();
      }

      $this->loggerFactory
        ->get($this->getPluginId())
        ->notice('New user created. Username @username, UID: @uid', [
          '@username' => $new_user->getAccountName(),
          '@uid' => $new_user->id(),
        ]);

      // Dispatches SocialAuthEvents::USER_CREATED event.
      $event = new UserEvent($new_user, $this->getPluginId(), $user);
      $this->eventDispatcher
        ->dispatch(SocialAuthEvents::USER_CREATED, $event);

      return $new_user;
    }
    catch (\Exception $ex) {
      $this->loggerFactory
        ->get($this->getPluginId())
        ->error('Could not create new user. Exception: @message', 
          ['@message' => $ex->getMessage()]);
    }

    $this->messenger->addError($this->t('You could not be authenticated,' . 
      ' please contact the administrator.'));
    return FALSE;
    
  }
  
  /**
   * Helper method to print message for failed user creation 
   */
  private function failUserCreation() {
    
    $this->loggerFactory->get($this->getPluginId())
      ->warning('User with invalid domain attempted to create account.');
    $this->messenger->addError('You are not allowed to create an ' . 
      'account on this site.');
    
  }
}