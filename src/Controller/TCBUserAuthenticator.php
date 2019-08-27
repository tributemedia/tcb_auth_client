<?php

namespace Drupal\tcb_auth_client\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\social_api\User\UserAuthenticator as SocialApiUserAuthenticator;
use Drupal\social_auth\Event\SocialAuthEvents;
use Drupal\social_auth\Event\SocialAuthUserEvent;
use Drupal\social_auth\SettingsTrait;
use Drupal\social_auth\SocialAuthDataHandler;
use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\social_auth\User\UserAuthenticator;
use Drupal\tcb_auth_client\Controller\TCBUserManager;

/**
 * Defines CustomUserManager class
 */
class TCBUserAuthenticator extends UserAuthenticator {

  public function __construct(UserAuthenticator $theParent) {

    parent::__construct($theParent->currentUser, $theParent->messenger, 
      $theParent->loggerFactory, $theParent->userManager, 
      $theParent->dataHandler, $theParent->configFactory, 
      $theParent->routeProvider, $theParent->eventDispatcher);
      
    $this->userManager = new TCBUserManager($this->userManager);
    $this->pluginId = $theParent->getPluginId();
    $this->userManager->setPluginId($this->getPluginId());
  }

  /**
   * Creates and/or authenticates an user.
   *
   * @param string $name
   *   The user's name.
   * @param string $email
   *   The user's email address.
   * @param string $provider_user_id
   *   The unique id returned by the user.
   * @param string $token
   *   The access token for making additional API calls.
   * @param string|bool $picture_url
   *   The user's picture.
   * @param string $data
   *   The additional user_data to be stored in database.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response.
   */
  public function authenticateUser($name, $email, $provider_user_id, $token, 
    $picture_url = FALSE, $data = '') {

    // Checks for record in Social Auth entity.
    $user_id = $this->userManager->getDrupalUserId($provider_user_id);

    // If user is already authenticated.
    if ($this->currentUser->isAuthenticated()) {

      // If no record for provider exists.
      if ($user_id === FALSE) {
        $this->associateNewProvider($provider_user_id, $token, $data);

        return $this->response;
      }
      // User is authenticated and provider is already associated.
      else {
        return $this->getPostLoginRedirection();
      }
    }

    // If user previously authorized the provider, load user through provider.
    if ($user_id) {
      
      $this->authenticateWithProvider($user_id);

      return $this->response;
    }

    // Try to authenticate user using email address.
    if ($email) {
      // If authentication with email was successful.
      if ($this->authenticateWithEmail($email, $provider_user_id, $token, 
        $data)) {
        
        return $this->response;
      
      }
    }

    // At this point, create a new user.
    $drupal_user = $this->userManager->createNewUser($name, $email, 
      $provider_user_id, $token, $picture_url, $data);

    $this->authenticateNewUser($drupal_user);

    return $this->response;
  }
}