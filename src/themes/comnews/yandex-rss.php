<?php
header('Content-Type: application/rss+xml; charset=utf-8');
print '<?xml version="1.0" encoding="UTF-8" ?>
<rss xmlns:yandex="http://news.yandex.ru" xmlns:media="http://search.yahoo.com/mrss/" xmlns:turbo="http://turbo.yandex.ru" version="2.0">
<channel>
<title>ComNews.ru</title>
<link>https://www.comnews.ru</link>
<description>
ComNews.ru - российская ежедневная интернет-газета о новостях на рынке телекоммуникаций, ИТ и вещания (Свидетельство о регистрации СМИ от 8 декабря 2006 г. Эл № ФC 77-26395). ComNews.ru создан в 1999 г., издается Группой Компаний COMNEWS.
</description>
<image>
<url>https://www.comnews.ru/img/c1.png</url>
<link>https://www.comnews.ru</link>
<title>ComNews.ru</title>
</image>';

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;
$autoloader = require_once '../../autoload.php';
$request = Request::createFromGlobals();
//$kernel = new DrupalKernel('prod', $autoloader);
$kernel = DrupalKernel::createFromRequest($request, $autoloader, 'prod');
$kernel->boot();
$kernel->prepareLegacyRequest($request);


print '<lastBuildDate>'.date('d M Y H:i:s +0300').'</lastBuildDate>';


$start = strtotime('today -1 days 20:00:00');
$end = strtotime('today 20:00:00');
//$start = strtotime('2018-05-09 20:00:00');
//$end = strtotime('2018-05-10 20:00:00');

$query = \Drupal::entityQuery('node')->condition('type', 'article')->condition('status', 1)
        ->condition('field_folders', [1007,1008], 'IN') 
        ->condition('field_hp_date', cn_shortDBDate())
        ->sort('field_seq','ASC')
        ;
$nids = $query->execute();
$node_storage = \Drupal::entityTypeManager()->getStorage('node');
$news = $node_storage->loadMultiple($nids);

$query = \Drupal::entityQuery('node')->condition('type', 'article')->condition('status', 1)
        ->condition('field_folders', 1009)
        ->condition('field_hp_date', cn_shortDBDate())
        ->sort('field_seq','ASC')
        ;
$nids = $query->execute();
$news_digest = $node_storage->loadMultiple($nids);

$query = \Drupal::entityQuery('node')->condition('type', 'interview')->condition('status', 1)
        ->condition('field_folders', 1013)
        ->condition('field_hp_date', cn_shortDBDate())
        ->sort('field_seq','ASC')
        ;
$nids = $query->execute();
$points = $node_storage->loadMultiple($nids);	

$query = \Drupal::entityQuery('node')->condition('type', 'editorial')->condition('status', 1)
        ->condition('field_folders', 1002)
        ->condition('field_hp_date', cn_shortDBDate())
        ->sort('field_seq','ASC')
        ;
$nids = $query->execute();
$editorials = $node_storage->loadMultiple($nids);	

$query = \Drupal::entityQuery('node')->condition('type', 'interview')->condition('status', 1)
        ->condition('field_folders', 1016)
        ->condition('field_hp_date', cn_shortDBDate())
        ->sort('field_seq','ASC')
        ;
$nids = $query->execute();
$de_opinions = $node_storage->loadMultiple($nids);	

$query = \Drupal::entityQuery('node')->condition('type', 'article')->condition('status', 1)
        ->condition('field_folders', 1012)
        ->condition('field_hp_date', cn_shortDBDate())
        ->sort('field_seq','ASC')
        ;
$nids = $query->execute();
$de_news = $node_storage->loadMultiple($nids);	

$all = array($news,$news_digest,$points, $editorials, $de_news, $de_opinions);

foreach($all as $news){

	foreach($news as $node){

		print '<item>';
		print '<title>'.htmlspecialchars($node->title->value).'</title>';
		print '<description>'.rtrim(htmlspecialchars(cn_renderLid($node->body,220,true)), '\n .').'</description>';
		print '<link>https://www.comnews.ru'.cn_getNodeAlias($node).'</link>';
		print '<guid>https://www.comnews.ru'.cn_getNodeAlias($node).'</guid>';

		if(count($node->field_image->getValue())){
			
			$tmp = explode('.php',cn_getImgUrl('large', $node->field_image));
			if(count($tmp)>1){
				$img = 'https://www.comnews.ru'.$tmp[1];
			} else {
				$img = $tmp[0];
			}
			print '<media:group><media:thumbnail url="'.$img.'"></media:thumbnail></media:group>';
		}
		
        print '<yandex:full-text>'.htmlspecialchars($node->body->value).'</yandex:full-text>';
        print '<pubDate>'.date('d M Y').' 02:30 +0300</pubDate>';
		print '<category>НОВОСТИ</category>';
		print '</item>';
	}

}	


	
print '</channel></rss>';

?>
             
             
             