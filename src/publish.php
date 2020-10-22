<?php

/* Календарь */

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

$autoloader = require_once 'autoload.php';
$request = Request::createFromGlobals();
//$kernel = new DrupalKernel('prod', $autoloader);

$kernel = DrupalKernel::createFromRequest($request, $autoloader, 'prod');
$kernel->boot();
$kernel->prepareLegacyRequest($request);

	$date = time();
    $query = \Drupal::entityQuery('node')
        ->condition('status', 0)
        ->condition('field_publish_date', cn_convertDateToStorageFormat($date),'<=')
        ->condition('field_isready', 1)
        ->sort('created','DESC');
    $nids = $query->execute();
       
    if(count($nids)){
      $node_storage = \Drupal::entityTypeManager()->getStorage('node');
      $nodes = $node_storage->loadMultiple($nids);


	    $i = 0;
	    foreach($nodes as $node){
            print '<li>'.$node->title->value.'</li>';
            $node->setPublished(true);
            $node->save();
            $i++;
        }
        \Drupal::logger('comnews')->info('Published (%count) articles.', array('%count' => $i));
        print 'Publish. '.$i.' documents was published. ';

    }

?>
