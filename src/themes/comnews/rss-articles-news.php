<?php

header('Content-Type: application/rss+xml; charset=utf-8');
print '<?xml version="1.0" encoding="UTF-8" ?>';

?>
<rss version="2.0" xmlns:media="http://search.yahoo.com/mrss/">

<channel>
  <title>Comnews articles (news)</title>
  <link>https://www.comnews.ru</link>
  <description></description>


<?php
use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;
$autoloader = require_once '../../autoload.php';
$request = Request::createFromGlobals();
//$kernel = new DrupalKernel('prod', $autoloader);
$kernel = DrupalKernel::createFromRequest($request, $autoloader, 'prod');
$kernel->boot();
$kernel->prepareLegacyRequest($request);


	$query = \Drupal::entityQuery('node')->condition('type', 'article')->condition('status', 1)
        ->condition('field_folders', 1007)
        ->condition('field_hp_date', cn_shortDBDate())
        ->sort('field_date','DESC')->range(0,1);
        ;
    $nids1 = $query->execute();
    if(!count($nids)){
      $query = \Drupal::entityQuery('node')->condition('type', 'article')->condition('status', 1)
        ->condition('field_folders', 1007)
        ->sort('field_date','DESC')->sort('field_seq','ASC')->sort('created','DESC')->range(0,1);
        $nids1 = $query->execute();
	} 
	
	$query = \Drupal::entityQuery('node')->condition('type', 'article')->condition('status', 1)
        ->condition('field_folders', 1008) 
        ->condition('field_hp_date', cn_shortDBDate())
        ->sort('field_seq','ASC')->range(0,3);
        ;
    $nids2 = $query->execute();

    if(count($nids) < 3){
      $query = \Drupal::entityQuery('node')->condition('type', 'article')->condition('status', 1)
        ->condition('field_folders', 1008)
        ->sort('field_date','DESC')->sort('field_seq','ASC')->sort('created','DESC')->range(0,3);
        $nids2 = $query->execute();
    } 
	$nids = $nids1 + $nids2;
	
	$node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nodes = $node_storage->loadMultiple($nids);
    
    foreach($nodes as $node){	

		print '<item>';

		print '<title>'.htmlspecialchars($node->title->value).'</title>';
		
 		print '<description>'.rtrim(htmlspecialchars(cn_renderLid($node->body,220,true)), '\n .').'</description>';
		print '<link>https://www.comnews.ru'.cn_getNodeAlias($node).'</link>';
		print '<guid>https://www.comnews.ru'.cn_getNodeAlias($node).'</guid>';

		 if(count($node->field_image->getValue())){
			$tmp = explode('.php',cn_getImgUrl('article_bigimg', $node->field_image));
			if(count($tmp)>1){
				$img = 'https://www.comnews.ru'.$tmp[1];
			} else {
				$img = $tmp[0];
			}
			print '<media:content url="'.$img.'"></media:content>';
		}
		print '<category>НОВОСТИ</category>';
		print '</item>';
		 
	}

?>


</channel>

</rss>