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
use Drupal\social_auth\Event\SocialAuthEvents;
use Drupal\social_auth\Event\SocialAuthUserEvent;
use Drupal\social_auth\Event\SocialAuthUserFieldsEvent;
use Drupal\social_auth\SettingsTrait;
use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\social_auth\User\UserManager;

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
  public function createUser($name, $email) {
    // Make sure we have everything we need.
    if (!$name) {
      $this->loggerFactory
        ->get($this->getPluginId())
        ->error('Failed to create user. Name: @name', ['@name' => $name]);
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
      $fields = $this->getUserFields($name, $email, $langcode);

      /** @var \Drupal\user\Entity\User $new_user */
      $new_user = $this->entityTypeManager
        ->getStorage('user')
        ->create($fields);

      $new_user->save();

      $this->loggerFactory
        ->get($this->getPluginId())
        ->notice('New user created. Username @username, UID: @uid', [
          '@username' => $new_user->getAccountName(),
          '@uid' => $new_user->id(),
        ]);

      // Dispatches SocialAuthEvents::USER_CREATED event.
      $event = new SocialAuthUserEvent($new_user, $this->getPluginId());
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
}