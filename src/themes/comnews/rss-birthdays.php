<?php
header('Content-Type: application/rss+xml; charset=utf-8');
$arrContextOptions=array(
    "ssl"=>array(
        "verify_peer"=>false,
        "verify_peer_name"=>false,
    ),
  ); 
$out = file_get_contents('https://whoiswho.comnews.ru/people5.php?mode=mail', false, stream_context_create($arrContextOptions));
print $out;
exit;
