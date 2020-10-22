<?php
header('Content-Type: application/rss+xml; charset=utf-8');
print '<?xml version="1.0" encoding="UTF-8" ?>'; 

$items = 'all';
if(isset($_REQUEST['items'])) $items = explode(',',$_REQUEST['items']);

?>
<rss version="2.0">

<channel>
  <title>Comnews articles (digital economy news)</title>
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

$type= '';


	$query = \Drupal::entityQuery('node')->condition('type', 'article')->condition('status', 1)
        ->condition('field_folders', 1012)
        ->sort('field_date','DESC')->sort('field_seq','ASC')->sort('created','DESC')->range(0,4);
        $nids = $query->execute();
     

    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nodes = $node_storage->loadMultiple($nids);
    $i=1;
    foreach($nodes as $node){
			if($items == 'all' || array_search($i,$items) !== false){
				
				print '<item>';
				print '<title>'.htmlspecialchars($node->title->value).'</title>';
		
				print '<description>'.rtrim(htmlspecialchars(cn_renderLid($node->body,300)), '\n .').'</description>';
				print '<link>https://www.comnews.ru'.cn_getNodeAlias($node).'</link>';
				print '<guid>https://www.comnews.ru'.cn_getNodeAlias($node).'</guid>';

				print '<category>ЦИФРОВАЯ ЭКОНОМИКА / НОВОСТИ</category>';
				print '</item>';
			}
			$i++;
			
		
		
	}
	
?>
             
             
</channel>

</rss>             