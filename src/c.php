<?php

/* Календарь */

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
$d=0; $n = ''; $t ='';

if(isset($_GET['d'])){
    $d = $_GET['d'];
    $d = intval($d);
}
if(isset($_GET['n'])){
    $n = $_GET['n'];
}
if(isset($_GET['t'])){
    $t = $_GET['t'];
    $d = strtotime($t);
}

if ($d == 0) $d = time();
print date('d').'.'.date('m').'.'.date('Y').', '.date('D').'~';
print cn_MonthCalendar($d,$n);
?>
