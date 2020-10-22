<?php
header('Content-Type: application/rss+xml; charset=utf-8');
print '<?xml version="1.0" encoding="UTF-8" ?>'; 

?>
<rss version="2.0" xmlns:media="http://search.yahoo.com/mrss/" xmlns:content="http://purl.org/rss/1.0/modules/content/" >

<channel>
  <title>Comnews articles (point of view)</title>
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


	$query = \Drupal::entityQuery('node')->condition('type', 'interview')->condition('status', 1)
        ->condition('field_folders', 1013)
        ->condition('field_hp_date', cn_shortDBDate())
        ->sort('field_seq','ASC')
        ;
    $nids = $query->execute();

    if(!count($nids)){
      $query = \Drupal::entityQuery('node')->condition('type', 'interview')->condition('status', 1)
        ->condition('field_folders', 1013)
        ->sort('field_date','DESC')->sort('field_seq','ASC')->sort('created','DESC')->range(0,1);
        $nids = $query->execute();
    } 

    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nodes = $node_storage->loadMultiple($nids);
    
    foreach($nodes as $node){
    

			
			print '<item>';

			$title = $node->title->value;
			print '<title>'.htmlspecialchars($title).'</title>';
			$p = array();
			if(count($node->field_i_persons->getValue())) $p = explode(';',$node->field_i_persons->value);
			else if(count($node->field_authors->getValue())) $p = explode(';',$node->field_authors->value);
			if(count($p)){
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
				print '<author>'.htmlspecialchars($a).'</author>';
			}
			print '<description>'.rtrim(htmlspecialchars(cn_renderLid($node->body,300)), '\n .').'</description>';
			
			print '<link>https://www.comnews.ru'.cn_getNodeAlias($node).'</link>';
			print '<guid>https://www.comnews.ru'.cn_getNodeAlias($node).'</guid>';

			if(count($node->field_image->getValue())){
				$tmp = explode('.php',cn_getImgUrl('interview_big', $node->field_image));
				if(count($tmp)>1){
					$img = 'https://www.comnews.ru'.$tmp[1];
				} else {
					$img = $tmp[0];
				}
				print '<media:content url="'.$img.'"></media:content>';
			}
			print '<category>ТОЧКА ЗРЕНИЯ</category>';
			print '</item>';

	}
?>


</channel>

</rss>