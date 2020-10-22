<?php
header('Content-Type: application/rss+xml; charset=utf-8');
print '<?xml version="1.0" encoding="UTF-8" ?>'; 

?>
<rss version="2.0" xmlns:media="http://search.yahoo.com/mrss/" xmlns:content="http://purl.org/rss/1.0/modules/content/" >

<channel>
  <title>Comnews articles (editorial)</title>
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



//$start = strtotime('today -1 day 20:00:00');
//$end = strtotime('today 20:00:00');
//$start = strtotime('2018-04-15 20:00:00');
//$end = strtotime('2018-04-16 20:00:00');

$type= '';

$query = \Drupal::entityQuery('node')->condition('type', 'editorial')->condition('status', 1)
        ->condition('field_folders', 1002)
        ->sort('field_date','DESC')->sort('field_seq','DESC')->sort('created','DESC')->range(0,1);
        $nids = $query->execute();
     

    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nodes = $node_storage->loadMultiple($nids);
    
    foreach($nodes as $node){		
			
		$p = explode(';',$node->field_authors->value);
		$a = '';
		if(count($p) == 1){
			$p = explode(',',$p[0]);
			if(count($p) > 1){
				$a .= '<strong>'.cn_t($p[0]).'</strong>, '.cn_t($p[1]);    
			} else $a .= '<strong>'.cn_t($p[0]).'</strong>';
		} else if(count($p) == 2){
			$a .= '<strong>'.cn_t($p[0]).'</strong>, '.cn_t($p[1]);
		} else{
			$out .= '<strong>'.cn_t($p[0]).'</strong>, '.cn_t($p[2]);    
		}

			print '<item>';
			print '<title>'.htmlspecialchars($node->title->value).'</title>';
			print '<description>'.rtrim(htmlspecialchars(cn_renderLid($node->body,300)), '\n .').'</description>';
			
			print '<link>https://www.comnews.ru'.cn_getNodeAlias($node).'</link>';
			print '<guid>https://www.comnews.ru'.cn_getNodeAlias($node).'</guid>';
			
			print '<author>'.htmlspecialchars($a).'</author>';
			//print '<content:encoded>'.htmlspecialchars($node->field_author_title['ru'][0]['value']).'</content:encoded>';
			if(count($node->field_image->getValue())){
				$tmp = explode('.php',cn_getImgUrl('interview_big', $node->field_image));
				if(count($tmp)>1){
					$img = 'https://www.comnews.ru'.$tmp[1];
				} else {
					$img = $tmp[0];
				}
				print '<media:content url="'.$img.'"></media:content>';
			}
			print '<category>РЕДКОЛОНКА</category>';
			print '</item>';
			
		
	}
?>
             
             
</channel>

</rss>             