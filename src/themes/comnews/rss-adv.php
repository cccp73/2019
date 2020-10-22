<?php
header('Content-Type: application/rss+xml; charset=utf-8');
/*print '<?xml version="1.0" encoding="UTF-8" ?>'; */

//$items = 'all';
//if(isset($_REQUEST['items'])) $items = explode(',',$_REQUEST['items']);
 
$q = $_SERVER['QUERY_STRING'];
$arrContextOptions=array(
    "ssl"=>array(
        "verify_peer"=>false,
        "verify_peer_name"=>false,
    ),
  ); 
$xml = file_get_contents('https://archiv.comnews.ru/themes/comnews/rss-adv.php?'.$q, false, stream_context_create($arrContextOptions));
print $xml; die;

