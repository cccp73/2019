<?php 
header('Content-Type: application/rss+xml; charset=utf-8');
print '<?xml version="1.0" encoding="UTF-8" ?>'; 

?>
<rss version="2.0">

<channel>
  <title>Comnews articles (quote)</title>
  <link>https://www.comnews.ru</link>
  <description></description>


<?php
define('DRUPAL_ROOT', $_SERVER['DOCUMENT_ROOT']);


include_once DRUPAL_ROOT . '/includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

include_once DRUPAL_ROOT . '/themes/comnews/template.php';
//print $_GET['id'];


$start = strtotime('today -1 day 20:00:00');
$end = strtotime('today 20:00:00');
$type= '';

$quote = db_query("SELECT n.nid "
				 ."FROM {node} n, {field_data_field_folder} f, {field_data_field_date} d"
				 ." WHERE"
				 ." n.status = 1"
				 ." and f.entity_id = n.nid"
				 ." and f.field_folder_tid = 310"
				 ." and d.entity_id = n.nid"
				 ." and d.field_date_value > :s"
				 ." and d.field_date_value <= :e"
				
				 , array(':s' => $start, ':e' => $end))->fetchAll();//->fetchField();




	if (count($quote)){
						
		foreach($quote as $record){
		
			$node = node_load($record->nid);
			print '<item>';
			print '<title>'.htmlspecialchars(str_replace("~","",$node->title)).'</title>';
			print '<description>"'.htmlspecialchars($node->body['ru'][0]['value']).'"</description>';
			print '<link>https://www.comnews.ru/'.drupal_get_path_alias('node/'.$node->nid).'</link>';
			print '<guid>https://www.comnews.ru/'.drupal_get_path_alias('node/'.$node->nid).'</guid>';
			print '<category>ЦИТАТА ДНЯ</category>';
			print '</item>';
			
		}
	}
?>
             
             
</channel>

</rss>             