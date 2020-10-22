<?php

/* вопросы недели */

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

$autoloader = require_once 'autoload.php';
$request = Request::createFromGlobals();
//$kernel = new DrupalKernel('prod', $autoloader);

$kernel = DrupalKernel::createFromRequest($request, $autoloader, 'prod');
$kernel->boot();
$kernel->prepareLegacyRequest($request);

//$response = $kernel->handle($request);
//$response->send();

//$kernel->terminate($request, $response);

$old = abs(intval(cn_getVal($_REQUEST['o']))); // старый вопрос
$res = abs(intval(cn_getVal($_REQUEST['r'])));
$nid = abs(intval(cn_getVal($_REQUEST['n'])));
$graph = abs(intval(cn_getVal($_REQUEST['g'])));

if($old){
		$url = 'http://archiv.comnews.ru/v.php?n='.$nid.'&g=1'; 
		//$client = \Drupal::httpClient(); 
		//$_request = $client->get($url); 
		$arrContextOptions=array("ssl"=>array("verify_peer"=>false,"verify_peer_name"=>false,),); 
		$markup = file_get_contents($url, false, stream_context_create($arrContextOptions));
		print $markup; //(string)$_request->getBody();
	
} else {
	
	if ($nid == 0) { // если nid не передан тащим последний по дате
		/*
		$tmp = db_query_range("SELECT n.nid FROM {node} n, {field_data_field_date} d WHERE d.field_date_value <= ".time()." and d.entity_id = n.nid and n.type = 'vopros' and n.status = 1 order by d.field_date_value desc",0,1)->fetchAll();
		$nid = intval($tmp[0]->nid);
		*/
	}

	if ($nid){
		
		$node = \Drupal\node\Entity\Node::load($nid);
		$max = count($node->get('field_variants')->getValue());
		
		
		if ($res > 0 && $res <= $max) { // передан вариант ответа
			if ($node->getType() == 'vopros' && $node->isPublished() == 1 && $node->field_end_date->value >= date('Y-m-d') && $node->field_date->value <= cn_convertDateToStorageFormat('today')){
				$res = $res-1;
				$cnt = count($node->get('field_results')->getValue());
				if ($cnt < $max){ // если результатов меньше чем вариантов - создаем результаты для каждого варианта
					$tmp = array();
					foreach($node->get('field_variants')->getValue() as $i=>$var){
						$tmp[] = array('value'=>0);
					}	
				} else $tmp = $node->get('field_results')->getValue();
				$tmp[$res]['value']++;
				$node->set('field_results',$tmp);
				$node->save();
			}
			print '{"res":"'.$node->id().'"}';
		} else 
			if ($node->id() && $node->getType()=='vopros'){
				//print cn_getVoprosResults($nid);
				if ($graph) { // если передан запрос графика
					print cn_getVoprosResults($nid); 
				} else 
					if ($node->isPublished() == 1 && $node->field_end_date->value >= date('Y-m-d') && $node->field_date->value <= cn_convertDateToStorageFormat('today')){
						// опрос открыт - форма
						print cn_renderVoprosForm($nid);	
					} else {
						// опрос закрыт - инициализация перезапроса графика
						print 'graph';	
					}
			}
			
	}
	
}

?>
