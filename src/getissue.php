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

//$issue = abs(intval(cn_getVal($_REQUEST['i'])));
//$variant = abs(intval(cn_getVal($_REQUEST['v'])));
$mode = trim(cn_getVal($_REQUEST['m']));
if(!in_array($mode, array('f-list','cover','pdf','preview-cover','preview-pdf','maps','maps-archive'))){
    die();
}

if($mode == 'cover' && isset($_REQUEST['guid'])){
    $arrContextOptions=array("ssl"=>array("verify_peer"=>false,"verify_peer_name"=>false,),); 
    $issue_id = intval(trim(file_get_contents('https://www.comnews-conferences.ru/getissue.php?m=cover&guid='.$_REQUEST['guid'], false, stream_context_create($arrContextOptions))));
    
    if($issue_id){
        $node = \Drupal\node\Entity\Node::load($issue_id);
        //var_dump($node->id());
        if($node->id() && $node->isPublished()){
             
            $img = trim($node->field_cover_old->value == ''?cn_getImgUrl('original',$node->field_cover):'https://www.comnews.ru'.$node->field_cover_old->value);
            header('Location: '.$img);
        }
    }
}

if($mode == 'preview-cover' && isset($_REQUEST['id'])){
    $issue_id = intval(trim($_REQUEST['id']));
    if($issue_id){
        $node = \Drupal\node\Entity\Node::load($issue_id);
        if($node->id() && $node->isPublished()){
            $img = trim($node->field_cover_old->value == ''?cn_getImgUrl('original',$node->field_cover):'https://www.comnews.ru'.$node->field_cover_old->value);
            header('Location: '.$img);
        }
    }
}
if($mode == 'preview-pdf' && isset($_REQUEST['id'])){
    $issue_id = intval(trim($_REQUEST['id']));
    if($issue_id){
        $pdf = '';
        $node = \Drupal\node\Entity\Node::load($issue_id);
        if($node->id() && $node->isPublished() && count($node->get('field_issue_pdf'))){
            foreach($node->get('field_issue_pdf') as $f){
                $pdf = 'https://www.comnews.ru'.$f->entity->createFileUrl();
                
            }
        } else 
            if($node->id() && $node->isPublished() && $node->field_free_pdf->value != ''){
                $pdf = 'https://www.comnews.ru'.$node->field_free_pdf->value;
                
            }

        if($pdf != ''){
            $qnt = intval($node->field_issue_dl_qnt->value) + 1;
            $node->set('field_issue_dl_qnt',$qnt);
            $node->save();
            header('Location: '.$pdf);

        }    
        
    }
}

if($mode == 'maps' && isset($_REQUEST['id'])){
    $issue_id = intval(trim($_REQUEST['id']));
    if($issue_id){
        $node = \Drupal\node\Entity\Node::load($issue_id);
        if($node->id() && $node->isPublished() && count($node->get('field_map_imgs'))){
            $maps = array();
            foreach($node->get('field_map_imgs') as $img){
                $maps[] = $img->alt.'|'.cn_getImgStyleUrl('original',$img->entity->getFileUri()).'|'.$img->title;
            }
            print implode(chr(10),$maps); 
            die();
        }
    }
}
if($mode == 'maps-archive' && isset($_REQUEST['id'])){
    $issue_id = intval(trim($_REQUEST['id']));
    if($issue_id){
        $maps = '';
        $query = \Drupal::entityQuery('node')->condition('type', 'standart')->condition('status', 1)
        ->sort('field_seq','DESC')
        ;
        $nids = $query->execute();
        
        $node_storage = \Drupal::entityTypeManager()->getStorage('node');
        $nodes = $node_storage->loadMultiple($nids);
        foreach($nodes as $node){
            if($node->id() && $node->id() != $issue_id && $node->isPublished() && count($node->get('field_map_imgs'))){
                
                foreach($node->get('field_map_imgs') as $img){
                    $maps .='<div class="map" style="margin-bottom:50px;">
                            <img style="width:50%; float:left; margin-right:30px;" src="'.cn_getImgStyleUrl('large',$img->entity->getFileUri()).'"/>
                            <h3>'.$img->alt.'</h3>
                            <div style="clear:both"></div>
                            </div>';
                }
                
            }
        }
        print $maps; 
        die();
    }
}

if($mode == 'pdf' && isset($_REQUEST['guid'])){
    $log = urlencode('['.$_SERVER['REMOTE_ADDR'].'] - '.$_SERVER['HTTP_USER_AGENT'].'==='.$_SERVER['HTTP_COOKIE']);
    $arrContextOptions=array("ssl"=>array("verify_peer"=>false,"verify_peer_name"=>false,),); 
    $issue_id = intval(trim(file_get_contents('https://www.comnews-conferences.ru/getissue.php?m=pdf&guid='.$_REQUEST['guid'].'&l='.$log, false, stream_context_create($arrContextOptions))));
    
    if($issue_id){
        $node = \Drupal\node\Entity\Node::load($issue_id);
        //var_dump($node->id());
        $pdf = '';
        if($node->id() && $node->isPublished() && count($node->get('field_issue_pdf'))){
            foreach($node->get('field_issue_pdf') as $f){
                $pdf = 'https://www.comnews.ru'.$f->entity->createFileUrl();
                
            }
        } else 
        
            if($node->id() && $node->isPublished() && $node->field_free_pdf->value != ''){
                
                $pdf = 'https://www.comnews.ru'.$node->field_free_pdf->value;
                //header('Location: '.$pdf);
            }

        if($pdf != ''){
            $qnt = intval($node->field_issue_dl_qnt->value) + 1;
            $node->set('field_issue_dl_qnt',$qnt);
            $node->save();
            $response = new \Symfony\Component\HttpFoundation\RedirectResponse($pdf, 302);
            $response->send();

        }    
        
    }
}

if($mode == 'f-list'){

    $query = \Drupal::entityQuery('node')->condition('type', 'standart')->condition('status', 1)
        ->sort('field_seq','DESC')
        ;
    $nids = $query->execute();
     
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nodes = $node_storage->loadMultiple($nids);

    $select = '';
    
    foreach($nodes as $node){
      
      if($node->field_free_pdf->value != '' || count($node->get('field_issue_pdf'))){
        $select .= '<option value="'.$node->id().'">'.$node->title->value.'</option>';
      }  
    }
    print $select;
    die();
}

?>
