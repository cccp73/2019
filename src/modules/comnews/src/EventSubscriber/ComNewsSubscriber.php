<?php 
/*
 
*/
namespace Drupal\comnews\EventSubscriber;
 
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class ComNewsSubscriber implements EventSubscriberInterface {

  public function init(GetResponseEvent $event) {
    // boost init
    \Drupal::service('comnews.boost')->init();
    
    /************************************* */
    /*
        node redirect
    */
    
    $request = \Drupal::request();
    $url = explode('?',$request->getRequestUri());
    $path = explode('/', \Drupal::service('path.alias_manager')->getPathByAlias($url[0]));
    $q = '';
    if(isset($url[1])&& !empty($url[1])) $q = '?'.$url[1];
    if($path[1] == 'node' && isset($path[2]) && intval($path[2]) != 0 && (!isset($path[3]) || $path[3] == '')){
        // If there is HTTP Exception..

        if ($exception = $request->attributes->get('exception')) {
            // Get the status code.
            
            $status_code = $exception->getStatusCode();
            if (in_array($status_code, array(404))) {
                if(intval($path[2])< 200000 ){ 
                \Drupal::service('comnews.boost')->clear();  
                $response = new RedirectResponse('/content/'.intval($path[2]).$q, 302);
                $response->send();
                } else {
                 
                  \Drupal::service('comnews.boost')->clear();
                }
            }
        } else {
            
            $node = \Drupal\node\Entity\Node::load(intval($path[2]));  
            if(isset($node->field_old_id->value) && intval($node->field_old_id->value) >0){
              // fake new article = redirect to old
              \Drupal::service('comnews.boost')->clear();  
              $response = new RedirectResponse('/content/'.intval($node->field_old_id->value).$q, 302);
              $response->send();
                
            } else {
              $node_type = $node->getType();
              if(in_array($node_type, array('article','vopros','interview','editorial','quote','pressrelease','review'))){
                  
                  $prefix = 'content';  
                  $folders = $node->get('field_folders')->getValue();
                  foreach($folders as $folder){
                    if(in_array($folder['target_id'],array('1012','1016','1022'))){ // папки статей ЦЭ
                      $prefix = 'digital-economy/content';
                      break;
                    }
                  } 

                  $url = explode('/', \Drupal::service('path.alias_manager')->getAliasByPath('/node/'.$node->id()));
                  $url[1] = $prefix;
                  $url = implode('/',$url);
                  \Drupal::service('comnews.boost')->clear();
                  $response = new RedirectResponse($url.$q, 302);
                  $response->send();
              } else if(in_array($node_type, array('event'))){ // выставки
                $url = '/exhibition/'.$node->id();
                \Drupal::service('comnews.boost')->clear();
                $response = new RedirectResponse($url.$q, 302);
                $response->send();
              }   
            }
        }
    } else {
      /*
      if ($exception = $request->attributes->get('exception')) {
        \Drupal::service('comnews.boost')->clear();  
      }
      */
    }
    /**************************************/  
    
  }

  public function terminate() {
    // boost 
      \Drupal::service('comnews.boost')->process();
    
  }
  public function onRespond(FilterResponseEvent $event) {
    // boost 
    
      $response = $event->getResponse();
      \Drupal::service('comnews.boost')->process($response);
      \Drupal::service('comnews.boost')->clear();
    
  }
  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('init');
    //$events[KernelEvents::TERMINATE][] = array('terminate');
    $events[KernelEvents::RESPONSE][] = array('onRespond', -1028);
    return $events;
  }

  

}