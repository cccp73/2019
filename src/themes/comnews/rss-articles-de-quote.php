<?php
header('Content-Type: application/rss+xml; charset=utf-8');
print '<?xml version="1.0" encoding="UTF-8" ?>'; 

?>
<rss version="2.0">

<channel>
  <title>Comnews articles (digital economy quote)</title>
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

$de_quot = db_query("SELECT s.field_seq_value, n.nid "
				 ."FROM {node} n, {field_data_field_date} d, {field_data_field_seq} s,{field_data_field_de_article_type} f"
				 ." WHERE"
				 ." n.status = 1 "
				 ." and n.type = 'de_article' "
				 ." and f.entity_id = n.nid"
				 ." and f.field_de_article_type_value = 'quot' "
				 ." and d.entity_id = n.nid"
				 ." and d.field_date_value > :s"
				 ." and d.field_date_value <= :e"
				 ." and s.entity_id = n.nid"
				 ." order by s.field_seq_value ", array(':s' => $start, ':e' => $end))->fetchAll();//->fetchField();
		
	if (count($de_quot)){
			 
		foreach($de_quot as $record){
		
			$node = node_load($record->nid);
			print '<item>';
			print '<title>'.htmlspecialchars(str_replace("~","",$node->title)).'</title>';
			$a = explode(',',$node->title);
			print '<author>'.htmlspecialchars($a[0]).'</author>'; 
			print '<content:encoded>'.htmlspecialchars($a[1]).'</content>';
			print '<description>'.htmlspecialchars($node->body['ru'][0]['value']).'</description>';
			$turl = explode('/',drupal_get_path_alias('node/'.$node->nid));
			$turl[1] .= '/quote';
			$turl = implode('/',$turl);
			print '<link>https://www.comnews.ru/digital-economy/'.$turl.'</link>'; 
			print '<guid>https://www.comnews.ru/digital-economy/'.$turl.'</guid>'; 
			if(count($node->field_bigimg['ru'])){
				print '<media:content url="'.image_style_url('interview_big', $node->field_bigimg['ru'][0]['uri']).'"></media>';	
			}
			print '<category>ЦИФРОВАЯ ЭКОНОМИКА / ЦИТАТА</category>';
			print '</item>';
			
		}
	}

?>
             
             
</channel>

</rss>             