<?php

namespace Drupal\tcb_auth_client\EventSubscriber;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Implementer of the EventSubscriberInterface to respond to exceptions
 * raised during Guzzle web requests to a TCB server.
 */
class ExceptionEventSubscriber implements EventSubscriberInterface {
  
  protected $logger;
  
  public function __construct(LoggerChannelFactoryInterface $logger) {
    
    $this->logger = $logger;
    
  }
  
  /**
   * {@inheritdoc}
   */
  public function onException(GetResponseForExceptionEvent $exceptionEvent ) {
    
    $exception = $exceptionEvent->getException();
    
    // If there was an exception raised during the attempt to connect to 
    // a server, inform the user an incorrect server URL was provided.
    // A ConnectException is raised for an invalid domain
    // A RequestException is raised for a request that returns any form of 
    // error (either a 4xx or 5xx).
    if($exception instanceof ConnectException || 
      $exception instanceof RequestException) {
      
      drupal_set_message('Invalid URL', 'error');
      $exceptionEvent->setResponse(
        new RedirectResponse(\Drupal::url('tcb_auth_client.settings')));
      
    }
    
  }
  
  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    
    $events[KernelEvents::EXCEPTION][] = ['onException', 80];
    return $events;
    
  }
  
}