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

$issue = abs(intval(cn_getVal($_REQUEST['i'])));
$variant = abs(intval(cn_getVal($_REQUEST['v'])));
$pwd = trim(cn_getVal($_REQUEST['p']));

$node = \Drupal\node\Entity\Node::load($issue);
if($node){
    $v = $node->field_issues->getValue();
    $v = cn_getVal($v[$variant]['value']);
    if(!empty($v)){
        
        $v = explode('|',$v);
         
        if($v[2] == $pwd){
            print '<a href="'.$v[1].'" target="_blank">Скачать PDF</a>';
        } else {
            print 'Пароль указан неверно!';
        }
    }
}

?>
