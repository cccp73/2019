<?php
//handler for old comnews index.cfm 
if(isset($_GET['id']) && intval($_GET['id'])){
    $arrContextOptions=array("ssl"=>array("verify_peer"=>false,"verify_peer_name"=>false,),); 
    $nid = file_get_contents('https://archiv.comnews.ru/r.php?id='.intval($_GET['id']).'&noredirect=1', false, stream_context_create($arrContextOptions));
    $nid = explode('|',$nid);
    if(isset($nid[1])) $type = $nid[1];
}

if(intval($nid[0])){
    if($type == 'standart'){
        header("HTTP/1.1 301 Moved Permanently"); 
        header('Location: /standart/article/'.$nid[0]);  
    } else if($type == 'issue_toc'){
        header("HTTP/1.1 301 Moved Permanently"); 
        header('Location: /standart/issue/'.$nid[0]);  
    } else {
        header("HTTP/1.1 301 Moved Permanently"); 
        header('Location: /content/'.$nid[0]);  
    }
}

?>