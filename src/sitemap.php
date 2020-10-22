<?php
use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;
$autoloader = require_once 'autoload.php';
$request = Request::createFromGlobals();
//$kernel = new DrupalKernel('prod', $autoloader);
$kernel = DrupalKernel::createFromRequest($request, $autoloader, 'prod');
$kernel->boot();
$kernel->prepareLegacyRequest($request);



$start = strtotime('today -1 month 00:00:00');
$end = strtotime('today 23:59:00');

$query = \Drupal::entityQuery('node')->condition('type', ['article','interview','editorial','quote','pressrelease'],'IN')->condition('status', 1)
		->condition('field_hp_date', cn_shortDBDate($start),'>=')
		->condition('field_hp_date', cn_shortDBDate($end),'<=')
        ->sort('field_seq','ASC')
        ;
$nids = $query->execute();
$node_storage = \Drupal::entityTypeManager()->getStorage('node');
$nodes = $node_storage->loadMultiple($nids);






$static = 'https://www.comnews.ru/news|НОВОСТИ

https://www.comnews.ru/expansion|Экспансия
https://www.comnews.ru/moscow|Москва
https://www.comnews.ru/petersburg|Санкт-Петербург
https://www.comnews.ru/zfo|Центр
https://www.comnews.ru/szfo|Северо-Запад
https://www.comnews.ru/dfo|Дальний Восток
https://www.comnews.ru/sfo|Сибирь
https://www.comnews.ru/ufo|Урал
https://www.comnews.ru/pfo|Поволжье
https://www.comnews.ru/cfo|Кавказ
https://www.comnews.ru/jufo|Юг
https://www.comnews.ru/baltics|Страны Балтии
https://www.comnews.ru/sng|СНГ
https://www.comnews.ru/world|В мире
https://www.comnews.ru/taxonomy/term/252|Ритейл
https://www.comnews.ru/retail_federation|Федеральные сети
https://www.comnews.ru/retail_regions|Региональные сети
https://www.comnews.ru/gadgets|Гаджеты
https://www.comnews.ru/taxonomy/term/46|Финансы
https://www.comnews.ru/m%26a|M&amp;A
https://www.comnews.ru/cryptocurrency|Криптовалюта
https://www.comnews.ru/stock_market|Фондовый рынок
https://www.comnews.ru/financial_reports|Финансовые отчеты
https://www.comnews.ru/investments|Инвестиции
https://www.comnews.ru/competitions_tenders|Конкурсы / тендеры
https://www.comnews.ru/taxonomy/term/258|Кадры \ Компенсации и премии
https://www.comnews.ru/manpower|Кадры \ Назначения,Отставки
https://www.comnews.ru/taxonomy/term/14|Операторы
https://www.comnews.ru/wireless|Беспроводная связь
https://www.comnews.ru/fixed|Фиксированная связь
https://www.comnews.ru/broadcasting|Вещание
https://www.comnews.ru/magistral_business|Магистральный бизнес
https://www.comnews.ru/satellite|Спутниковая связь
https://www.comnews.ru/post|Почтовая связь
https://www.comnews.ru/broadband|ШПД
https://www.comnews.ru/taxonomy/term/15|Сервисы
https://www.comnews.ru/outsourcing|Аутсорсинг
https://www.comnews.ru/datacenter|Дата-центры
https://www.comnews.ru/callcenter|Колл-центры
https://www.comnews.ru/videoconferencing|ВКС
https://www.comnews.ru/%D1%81loud|Cloud, виртуализация
https://www.comnews.ru/e-commerce|Электронная коммерция
https://www.comnews.ru/information_security|Информационная безопасность
https://www.comnews.ru/integration|Интеграция
https://www.comnews.ru/electronic_services|Электронные госуслуги
https://www.comnews.ru/taxonomy/term/16|Медиа
https://www.comnews.ru/tv_content|ТВ-контент
https://www.comnews.ru/internet_media|Интернет-медиа
https://www.comnews.ru/mobile_content|Мобильный контент
https://www.comnews.ru/IoT|IoT
https://www.comnews.ru/blockchain|Блокчейн
https://www.comnews.ru/equipment|Оборудование
https://www.comnews.ru/software|ПО
https://www.comnews.ru/technoparks|Технопарки
https://www.comnews.ru/venture_projects|Венчурные проекты
https://www.comnews.ru/electronics|Электроника
https://www.comnews.ru/industry_standards|Отраслевые стандарты
https://www.comnews.ru/taxonomy/term/47|Регулирование
https://www.comnews.ru/regulations_acts|Нормативные акты
https://www.comnews.ru/courts|Суды
https://www.comnews.ru/conflicts|Конфликты
https://www.comnews.ru/taxonomy/term/250|Госполитика

https://www.comnews.ru/editorials|РЕДКОЛОНКА
https://www.comnews.ru/point-of-view|ТОЧКА ЗРЕНИЯ
https://www.comnews.ru/shortnews|В ФОКУСЕ
https://www.comnews.ru/quotes|ЦИТАТА ДНЯ
https://www.comnews.ru/polls|ВОПРОС НЕДЕЛИ
https://www.comnews.ru/exhibitions|КОНФЕРЕНЦИИ / ВЫСТАВКИ
https://www.comnews.ru/authors|АВТОРЫ
https://www.comnews.ru/digital-economy|ЦИФРОВАЯ ЭКОНОМИКА
https://www.comnews.ru/standart|Стандарт
https://www.comnews.ru/pressreleases|НОВОСТИ КОМПАНИЙ';


	print '<?xml version="1.0" encoding="UTF-8"?>
	<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

	$static = explode(chr(10),$static);
	foreach($static as $link){
		if(!empty($link)){
			$url = explode('|',$link);
			print '<url><loc>'.$url[0].'</loc><lastmod>'.date('Y-m-d').'</lastmod><changefreq>daily</changefreq><priority>0.8</priority></url>';
		}
	}


	foreach($nodes as $node){

		print '<url><loc>https://www.comnews.ru'.cn_getNodeAlias($node).'</loc><lastmod>'.cn_convertDateFromStorageFormat($node->field_date->value,'Y-m-d').'</lastmod><changefreq>never</changefreq><priority>1</priority></url>';

	}


	print '</urlset>';
?>
