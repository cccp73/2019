<?php 
header('Content-Type: application/rss+xml; charset=utf-8');
print '<?xml version="1.0" encoding="UTF-8" ?>'; 

?>
<rss version="2.0">

<channel>
  <title>Comnews articles</title>
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
$news = db_query("SELECT s.field_seq_value, n.nid "
				 ."FROM {node} n, {field_data_field_folder} f, {field_data_field_date} d, {field_data_field_is_comnews} c,{field_data_field_seq} s"
				 ." WHERE"
				 ." n.status = 1"
				 ." and f.entity_id = n.nid"
				 ." and f.field_folder_tid = 6"
				 ." and d.entity_id = n.nid"
				 ." and d.field_date_value > :s"
				 ." and d.field_date_value <= :e"
				 ." and s.entity_id = n.nid"
				 ." and c.entity_id = n.nid"
				 ." and c.field_is_comnews_value =1"
				 ." and s.entity_id = n.nid"
				 ." and s.field_seq_value <= 100 "
				 ." order by s.field_seq_value ", array(':s' => $start, ':e' => $end))->fetchAll();//->fetchField();

$news_digest = db_query("SELECT s.field_seq_value, n.nid "
				 ."FROM {node} n, {field_data_field_folder} f, {field_data_field_date} d, {field_data_field_is_comnews} c, {field_data_field_add_tomail} m,{field_data_field_seq} s"
				 ." WHERE"
				 ." n.status = 1"
				 ." and f.entity_id = n.nid"
				 ." and f.field_folder_tid = 6"
				 ." and d.entity_id = n.nid"
				 ." and d.field_date_value > :s"
				 ." and d.field_date_value <= :e"
				 ." and s.entity_id = n.nid"
				 ." and c.entity_id = n.nid"
				 ." and c.field_is_comnews_value =0"
				 ." and m.entity_id = n.nid"
				 ." and m.field_add_tomail_value =1"
				 ." and s.entity_id = n.nid"
				 ." and s.field_seq_value > 100 "
				 ." order by s.field_seq_value ", array(':s' => $start, ':e' => $end))->fetchAll();//->fetchField();

$de_news = db_query("SELECT s.field_seq_value, n.nid "
				 ."FROM {node} n, {field_data_field_date} d, {field_data_field_seq} s,{field_data_field_de_article_type} f"
				 ." WHERE"
				 ." n.status = 1 "
				 ." and n.type = 'de_article' "
				 ." and f.entity_id = n.nid"
				 ." and f.field_de_article_type_value = 'news' "
				 ." and d.entity_id = n.nid"
				 ." and d.field_date_value > :s"
				 ." and d.field_date_value <= :e"
				 ." and s.entity_id = n.nid"
				 ." order by s.field_seq_value ", array(':s' => $start, ':e' => $end))->fetchAll();//->fetchField();
// вчерашние позднии новости
$start1 = strtotime('today -2 day 20:00:00');
$end1 = strtotime('today -1 day 20:00:00');
$de_news1 = db_query("SELECT s.field_seq_value, n.nid "
				 ."FROM {node} n, {field_data_field_date} d, {field_data_field_seq} s,{field_data_field_de_article_type} f, {field_data_field_add_tomail} m "
				 ." WHERE"
				 ." n.status = 1 "
				 ." and n.type = 'de_article' "
				 ." and f.entity_id = n.nid"
				 ." and f.field_de_article_type_value = 'news' "
				 ." and d.entity_id = n.nid"
				 ." and d.field_date_value > :s"
				 ." and d.field_date_value <= :e"
				 ." and s.entity_id = n.nid"
				 ." and m.entity_id = n.nid"
				 ." and m.field_add_tomail_value =1"
				 ." order by s.field_seq_value ", array(':s' => $start1, ':e' => $end1))->fetchAll();//->fetchField();

$de_opinion = db_query("SELECT s.field_seq_value, n.nid "
				 ."FROM {node} n, {field_data_field_date} d, {field_data_field_seq} s,{field_data_field_de_article_type} f"
				 ." WHERE"
				 ." n.status = 1 "
				 ." and n.type = 'de_article' "
				 ." and f.entity_id = n.nid"
				 ." and f.field_de_article_type_value = 'opinion' "
				 ." and d.entity_id = n.nid"
				 ." and d.field_date_value > :s"
				 ." and d.field_date_value <= :e"
				 ." and s.entity_id = n.nid"
				 ." order by s.field_seq_value ", array(':s' => $start, ':e' => $end))->fetchAll();//->fetchField();

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


$adv = db_query("SELECT  n.nid "
				 ."FROM {node} n, {field_data_field_folder} f, {field_data_field_date} d, {field_data_field_add_tomail} m "
				 ." WHERE"
				 ." n.status = 1"
				 ." and f.entity_id = n.nid"
				 ." and f.field_folder_tid = 71"
				 ." and d.entity_id = n.nid"
				 ." and d.field_date_value > :s"
				 ." and d.field_date_value <= :e"
				 ." and m.entity_id = n.nid"
				 ." and m.field_add_tomail_value =1"
				 ." order by d.field_date_value ", array(':s' => $start, ':e' => $end))->fetchAll();//->fetchField();

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


$column = db_query_range("SELECT n.nid "
				 ."FROM {node} n, {field_data_field_folder} f, {field_data_field_date} d"
				 ." WHERE"
				 ." n.status = 1"
				 ." and f.entity_id = n.nid"
				 ." and f.field_folder_tid = 7"
				 ." and d.entity_id = n.nid"
				 //." and d.field_date_value > :s"
				 //." and d.field_date_value <= :e"
  				 ." order by d.field_date_value desc"
				 ,0,1, array(':s' => $start, ':e' => $end))->fetchAll();//->fetchField();



$interview = db_query("SELECT n.nid "
				 ."FROM {node} n, {field_data_field_folder} f, {field_data_field_date} d"
				 ." WHERE"
				 ." n.status = 1"
				 ." and f.entity_id = n.nid"
				 ." and f.field_folder_tid = 9"
				 ." and d.entity_id = n.nid"
				 ." and d.field_date_value > :s"
				 ." and d.field_date_value <= :e"
				
				 , array(':s' => $start, ':e' => $end))->fetchAll();//->fetchField();

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




//
	foreach($news as $record){
	
		$node = node_load($record->nid);
		print '<item>';
		print '<title>'.htmlspecialchars(str_replace("~","",$node->title)).'</title>';
		if ($node->body['ru'][0]['summary'] != ''){
			print '<description>'.htmlspecialchars($node->body['ru'][0]['summary']).'</description>';
		} else {
			print '<description>'.htmlspecialchars(cn_anons($node->body['ru'][0]['value'])).'</description>';
		}
		print '<link>https://www.comnews.ru/'.drupal_get_path_alias('node/'.$node->nid).'</link>';
		print '<guid>https://www.comnews.ru/'.drupal_get_path_alias('node/'.$node->nid).'</guid>';
		print '<category>НОВОСТИ</category>';
		print '</item>';
	}

	foreach($adv as $record){
	
		$node = node_load($record->nid);
		print '<item>';
		print '<title>'.htmlspecialchars(str_replace("~","",$node->title)).'</title>';
		if ($node->body['ru'][0]['summary'] != ''){
			print '<description>'.htmlspecialchars($node->body['ru'][0]['summary']).'</description>';
		} else {
			print '<description>'.htmlspecialchars(cn_anons($node->body['ru'][0]['value'])).'</description>';
		}
		print '<link>https://www.comnews.ru/'.drupal_get_path_alias('node/'.$node->nid).'</link>';
		print '<guid>https://www.comnews.ru/'.drupal_get_path_alias('node/'.$node->nid).'</guid>';
		print '<category>НОВОСТИ</category>';
		print '</item>';
		
	}
						
	foreach($news_digest as $record){
	
		$node = node_load($record->nid);
		print '<item>';
		print '<title>'.htmlspecialchars(str_replace("~","",$node->title)).'</title>';
		if ($node->body['ru'][0]['summary'] != ''){
			print '<description>'.htmlspecialchars($node->body['ru'][0]['summary']).'</description>';
		} else {
			print '<description>'.htmlspecialchars(cn_anons($node->body['ru'][0]['value'])).'</description>';
		}
		print '<link>https://www.comnews.ru/'.drupal_get_path_alias('node/'.$node->nid).'</link>';
		print '<guid>https://www.comnews.ru/'.drupal_get_path_alias('node/'.$node->nid).'</guid>';
		print '<category>НОВОСТИ</category>';
		print '</item>';
		
	}
						
	if (count($de_news) || count($de_news1)){
		foreach($de_news as $record){
		
			$node = node_load($record->nid);
			print '<item>';
			print '<title>'.htmlspecialchars(str_replace("~","",$node->title)).'</title>';
			if ($node->body['ru'][0]['summary'] != ''){
				print '<description>'.htmlspecialchars($node->body['ru'][0]['summary']).'</description>';
			} else {
				print '<description>'.htmlspecialchars(cn_anons($node->body['ru'][0]['value'])).'</description>';
			}
			$turl = explode('/',drupal_get_path_alias('node/'.$node->nid));
			$turl[1] .= '/news';
			$turl = implode('/',$turl);
			print '<link>https://www.comnews.ru/digital-economy/'.$turl.'</link>'; 
			print '<guid>https://www.comnews.ru/digital-economy/'.$turl.'</guid>'; 
			print '<category>ЦИФРОВАЯ ЭКОНОМИКА / НОВОСТИ</category>';
			print '</item>';
		}
		foreach($de_news1 as $record){
		
			$node = node_load($record->nid);
			print '<item>';
			print '<title>'.htmlspecialchars(str_replace("~","",$node->title)).'</title>';
			if ($node->body['ru'][0]['summary'] != ''){
				print '<description>'.htmlspecialchars($node->body['ru'][0]['summary']).'</description>';
			} else {
				print '<description>'.htmlspecialchars(cn_anons($node->body['ru'][0]['value'])).'</description>';
			}
			$turl = explode('/',drupal_get_path_alias('node/'.$node->nid));
			$turl[1] .= '/news';
			$turl = implode('/',$turl);
			print '<link>https://www.comnews.ru/digital-economy/'.$turl.'</link>'; 
			print '<guid>https://www.comnews.ru/digital-economy/'.$turl.'</guid>'; 
			print '<category>ЦИФРОВАЯ ЭКОНОМИКА / НОВОСТИ</category>';
			print '</item>';
			
		}
	}
						
	if (count($de_opinion)){
		foreach($de_opinion as $record){
		
			$node = node_load($record->nid);
			print '<item>';
			print '<title>'.htmlspecialchars(str_replace("~","",$node->title)).'</title>';
			if ($node->body['ru'][0]['summary'] != ''){
				print '<description>'.htmlspecialchars($node->body['ru'][0]['summary']).'</description>';
			} else {
				print '<description>'.htmlspecialchars(cn_anons($node->body['ru'][0]['value'])).'</description>';
			}
			$turl = explode('/',drupal_get_path_alias('node/'.$node->nid));
			$turl[1] .= '/opinions';
			$turl = implode('/',$turl);
			print '<link>https://www.comnews.ru/digital-economy/'.$turl.'</link>'; 
			print '<guid>https://www.comnews.ru/digital-economy/'.$turl.'</guid>'; 
			print '<category>ЦИФРОВАЯ ЭКОНОМИКА / МНЕНИЕ ЭКСПЕРТОВ</category>';
			print '</item>';
			
		}
	}
		
	if (count($de_quot)){
			 
		foreach($de_quot as $record){
		
			$node = node_load($record->nid);
			print '<item>';
			print '<title>'.htmlspecialchars(str_replace("~","",$node->title)).'</title>';
			print '<description>'.htmlspecialchars($node->body['ru'][0]['value']).'</description>';
			$turl = explode('/',drupal_get_path_alias('node/'.$node->nid));
			$turl[1] .= '/quote';
			$turl = implode('/',$turl);
			print '<link>https://www.comnews.ru/digital-economy/'.$turl.'</link>'; 
			print '<guid>https://www.comnews.ru/digital-economy/'.$turl.'</guid>'; 
			print '<category>ЦИФРОВАЯ ЭКОНОМИКА / ЦИТАТА</category>';
			print '</item>';
			
		}
	}

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
	if (count($column)){
						
		foreach($column as $record){
		
			$node = node_load($record->nid);
			print '<item>';
			print '<title>'.htmlspecialchars(str_replace("~","",$node->title)).'</title>';
			if ($node->body['ru'][0]['summary'] != ''){
				print '<description>'.htmlspecialchars($node->body['ru'][0]['summary']).'</description>';
			} else {
				print '<description>'.htmlspecialchars(cn_anons($node->body['ru'][0]['value'])).'</description>';
			}
			print '<link>https://www.comnews.ru/'.drupal_get_path_alias('node/'.$node->nid).'</link>';
			print '<guid>https://www.comnews.ru/'.drupal_get_path_alias('node/'.$node->nid).'</guid>';
			print '<category>РЕДКОЛОНКА</category>';
			print '</item>';
			
		}
	}
	if (count($interview)){

		foreach($interview as $record){
		
			$node = node_load($record->nid);
			print '<item>';
			print '<title>'.htmlspecialchars(str_replace("~","",$node->title)).'</title>';
			if ($node->body['ru'][0]['summary'] != ''){
				print '<description>'.htmlspecialchars($node->body['ru'][0]['summary']).'</description>';
			} else {
				print '<description>'.htmlspecialchars(cn_anons($node->body['ru'][0]['value'])).'</description>';
			}
			print '<link>https://www.comnews.ru/'.drupal_get_path_alias('node/'.$node->nid).'</link>';
			print '<guid>https://www.comnews.ru/'.drupal_get_path_alias('node/'.$node->nid).'</guid>';
			print '<category>ТОЧКА ЗРЕНИЯ</category>';
			print '</item>';
			
		}
	}
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