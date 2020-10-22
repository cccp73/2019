<?php
$t1 = time();
$arrContextOptions=array(
    "ssl"=>array(
        "verify_peer"=>false,
        "verify_peer_name"=>false,
    ),
  ); 
$tmp = file_get_contents('https://www.comnews.ru?rnd='.$t1, false, stream_context_create($arrContextOptions));
if (strpos($tmp,"UA-4916267-1")>0){
	$t2 = time()-$t1;
	print 'CMS = OK ('.$t2.'s)';
} else{
	print 'CMS ERROR : '.$tmp;
}

//
?>
