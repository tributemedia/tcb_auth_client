<?php

namespace Drupal\tcb_auth_client\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    
    if ($route = $collection->get('social_auth_google.callback')) {
      
      $route->setDefault('_controller', 
        '\Drupal\tcb_auth_client\Controller\TCBGoogleAuthController' .
        '::callback');
      
    }
  }

}