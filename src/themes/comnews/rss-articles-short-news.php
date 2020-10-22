<?php 
header('Content-Type: application/rss+xml; charset=utf-8');
print '<?xml version="1.0" encoding="UTF-8" ?>'; 

?>
<rss version="2.0">

<channel>
  <title>Comnews articles (short news)</title>
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

$shortnews = db_query("SELECT s.field_seq_value, n.nid "
				 ."FROM {node} n, {field_data_field_folder} f, {field_data_field_date} d, {field_data_field_is_comnews} c,{field_data_field_seq} s"  
				 ." WHERE"
				 ." n.status = 1"
				 ." and f.entity_id = n.nid"
				 ." and f.field_folder_tid = 8"
				 ." and d.entity_id = n.nid"
				 ." and d.field_date_value > :s"
				 ." and d.field_date_value <= :e"
				 ." and s.entity_id = n.nid"
				 ." and c.entity_id = n.nid"
				 //." and c.field_is_comnews_value =1"
				 ." and s.entity_id = n.nid"
				 //." and s.field_seq_value <= 100 "
				 ." order by d.field_date_value desc, s.field_seq_value    ", array(':s' => $start, ':e' => $end))->fetchAll();//->fetchField();


	if (count($shortnews)){
 
		foreach($shortnews as $record){
		
			$node = node_load($record->nid);
			print '<item>';
			print '<title>'.htmlspecialchars(str_replace("~","",$node->title)).'</title>';
			print '<description></description>';
			print '<link>https://www.comnews.ru/'.drupal_get_path_alias('node/'.$node->nid).'</link>';
			print '<guid>https://www.comnews.ru/'.drupal_get_path_alias('node/'.$node->nid).'</guid>';
			print '<category>КОРОТКО</category>';
			print '</item>';
			
		}
	}
?>
             
             
</channel>

</rss>             