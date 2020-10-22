<?php
function cn_createTags(){
    $text = '14|Связь и ТВ|0|x
29|Спутниковая связь|14|satellite
31|Почтовая связь|14|post
27|Вещание|14|broadcasting
16|Контент|14|x
15|Сервисы|0|x
38|Дата-центры|15|datacenter
18|Cloud|15|сloud
17|Электронная коммерция|15|e-commerce
19|Информационная безопасность|15|information_security
24|Интеграция|15|integration
54|Технологии|0|x
379|Сквозные технологии|54|blockchain
45|Оборудование|54|equipment
40|ПО|54|software
43|Электроника|54|electronics
47|Регулирование|0|x
53|Нормативные акты|47|regulations_acts
51|Суды|47|courts
250|Госполитика|47|x
46|Инвестиции|0|x
36|M&A|46|m&a
21|Отчеты|46|financial_reports
378|Криптовалюта|46|cryptocurrency
251|Конкурсы / тендеры|46|competitions_tenders
252|Ритейл|0|x
23|Регионы / СНГ|0|x
55|Россия|23|russia
58|Москва|55|moscow
59|Санкт-Петербург|55|petersburg
60|Центр|55|zfo
62|Северо-Запад|55|szfo
66|Дальний Восток|55|dfo
65|Сибирь|55|sfo
61|Урал|55|ufo
64|Поволжье|55|pfo
67|Кавказ|55|cfo
63|Юг|55|jufo
22|В мире|23|world
91|Кадры|0|manpower';

    $n = 10;
    $text = explode(chr(10),$text);
    //var_dump($text);die;
    $parents = array();
    foreach($text as $row){
        $str = explode('|',$row);
        //ksm($parents);
        if(intval($str[2]) == 0) {
            $p = 0;    
        } else $p = $parents[$str[2]];
        $term = \Drupal\taxonomy\Entity\Term::create([
                'vid' => 'tags',
                'langcode' => 'und',
                'name' => $str[1],
                'description' => ['value' => '',   'format' => 'full_html',    ],
                'weight' => $n,
                'field_old_tid' => ['value'=> $str[0]],
                'parent' => array($p),
        ]);
        $term->save();
        $parents[$str[0]] = $term->id();
        
        if($str[3] != 'x'){
        $id = $term->id();
        $path = \Drupal::service('path.alias_storage')->save('/taxonomy/term/' . $id, '/'.$str[3], 'und');
        }
        $n = $n +10;
        //break;
    }    
}
/**
 *  Перенос новостей со старого сайте
 * 
 * 
 */
function cn_createNews(){
    $out = '';
    $text = file_get_contents('news.txt');
    $text = explode('--#--',$text);
    
    $tags = array();
    foreach($text as $str){
        //break;
        if($str == '') break;

        $row = explode('--|--',$str);
        $comments = 'closed';
        if($row[9] == 2) $comments = 'open';
        // site folder
        $folder = 1000;
        $n = intval($row[4]);
        if($row[6] == 8) $folder = 1010; // short news
        else{
            if($n == 10) $folder = 1007; // main news
            else if($n < 100) $folder = 1008; // comnews
            else if($n < 200) $folder = 1009; // digest
            else $folder = 1011; // regional
        }
        // news tags
        $nTags = array();
        $oTags = explode(',',$row[7]);
         
        foreach($oTags as $oTag){
            if(isset($tags[$oTag])) {
                if(!in_array($tags[$oTag],$nTags)) $nTags[] = $tags[$oTag];
            } else {
                $query = \Drupal::entityQuery('taxonomy_term')
                ->condition('vid', 'tags')
                ->condition('field_old_tid', $oTag);
                $tids = $query->execute();

                if(count($tids)){
                    $tid = end($tids); //found!
                } else {
                    $tid = 1003; // hidden tag
                }
                if(!in_array($tid,$nTags)) $nTags[] = $tid;
                $tags[$oTag] = $tid;
            }
        }
        $tagValues = array();
        foreach($nTags as $nTag){
            if($nTag != 1003) $tagValues[] = array('target_id'=> $nTag);        
        }
        if(!count($tagValues)) $tagValues[] = array('target_id'=> 1003);         

        // author 
        $query = \Drupal::entityQuery('taxonomy_term')
        ->condition('vid', 'authors')
        ->condition('field_old_tid', $row[8]);
        $tids = $query->execute();
        if(count($tids)){
            $tid = end($tids); //found!
            $author = [['target_id' => $tid]];    
        } else {
            $author = array();
        }
        
       
        $node = Drupal\node\Entity\Node::create([
            'type' => 'article',
            'title' => $row[2],
            'langcode' => 'und',
            'uid' => 1,
            'status' => 1,
            'field_old_id' => [['value' => $row[0]],],
            'field_seq' => [['value' => $row[4]],],
            'body' => [
                [
                  'value' => $row[3],
                  'summary' => $row[3],
                  'format' => 'full_html',
                ]
            ],
            'field_date' => cn_convertDateToStorageFormat($row[1]),
            'field_hp_date' => date('Y-m-d',strtotime($row[1])),
            'field_folders' => [['target_id' => $folder],],
            'field_tags' => $tagValues,
            'field_person' => $author,
            'field_comments' => [['value' => $comments],],
            //'field_issues' => $issues,
        ]);
        //$node->save();
        
        $out .= '<li>'.$row[2].'</li>';
        
        
        
        //break;

    }

    return $out;
}

/**
 *  Перенос interview со старого сайте
 * 
 * 
 */
function cn_createInterview(){
    $out = '';
    $text = file_get_contents('interview.txt');
    $text = explode('--#--',$text);
    
    $tags = array();
    foreach($text as $str){
        //break;
        if($str == '') break;

        $row = explode('--|--',$str);
        $comments = 'closed';
        if($row[7] == 2) $comments = 'open';
        // site folder
        $folder = 1013;
        
        
        // author 
        $query = \Drupal::entityQuery('taxonomy_term')
        ->condition('vid', 'authors')
        ->condition('field_old_tid', $row[4]);
        $tids = $query->execute();
        if(count($tids)){
            $tid = end($tids); //found!
            $author = [['target_id' => $tid]];    
        } else {
            $author = array();
        }
        $authors = $row[5];
        $persons = $row[6];
       
        $node = Drupal\node\Entity\Node::create([
            'type' => 'interview',
            'title' => $row[2],
            'langcode' => 'und',
            'uid' => 1,
            'status' => 1,
            'field_old_id' => [['value' => $row[0]],],
             
            'body' => [
                [
                  'value' => $row[3],
                  'summary' => $row[3],
                  'format' => 'full_html',
                ]
            ],
            'field_date' => cn_convertDateToStorageFormat($row[1]),
            'field_hp_date' => date('Y-m-d',strtotime($row[1])),
            'field_folders' => [['target_id' => $folder],],
            'field_person' => $author,
            'field_authors' => [['value' => $authors],],
            'field_i_persons' => [['value' => $persons],],
            'field_comments' => [['value' => $comments],],
            //'field_issues' => $issues,
        ]);
        //$node->save();
        
        $out .= $row[2].'<br>';
        
        
        
        //break;

    }

    return $out;
}

/**
 *  Перенос editorials со старого сайте
 * 
 * 
 */
function cn_createEditorials(){
    $out = '';
    $text = file_get_contents('editorials.txt');
    $text = explode('--#--',$text);
    
    $tags = array();
    foreach($text as $str){
        //break;
        if($str == '') break;

        $row = explode('--|--',$str);
        $comments = 'closed';
        if($row[6] == 2) $comments = 'open';
        // site folder
        $folder = 1002;
        
        
        // author 
        $query = \Drupal::entityQuery('taxonomy_term')
        ->condition('vid', 'authors')
        ->condition('field_old_tid', $row[4]);
        $tids = $query->execute();
        if(count($tids)){
            $tid = end($tids); //found!
            $author = [['target_id' => $tid]];    
        } else {
            $author = array();
        }
        $authors = $row[5];
         
       
        $node = Drupal\node\Entity\Node::create([
            'type' => 'editorial',
            'title' => $row[2],
            'langcode' => 'und',
            'uid' => 1,
            'status' => 1,
            'field_old_id' => [['value' => $row[0]],],
             
            'body' => [
                [
                  'value' => $row[3],
                  'summary' => $row[3],
                  'format' => 'full_html',
                ]
            ],
            'field_date' => cn_convertDateToStorageFormat($row[1]),
            'field_hp_date' => date('Y-m-d',strtotime($row[1])),
            'field_folders' => [['target_id' => $folder],],
            'field_person' => $author,
            'field_authors' => [['value' => $authors],],
            'field_comments' => [['value' => $comments],],
            //'field_issues' => $issues,
        ]);
        //$node->save();
        
        $out .= $row[2].'<br>';
        
        
        
        //break;

    }

    return $out;
}

/**
 *  Перенос pressreleases со старого сайте
 * 
 * 
 */
function cn_createPressReleases(){
    $out = '';
    $text = file_get_contents('pressreleases.txt');
    $text = explode('--#--',$text);
    
    $tags = array();
    foreach($text as $str){
        //break;
        if($str == '') break;

        $row = explode('--|--',$str);
        // site folder
        $folder = $row[4];
         
       
        $node = Drupal\node\Entity\Node::create([
            'type' => 'pressrelease',
            'title' => $row[2],
            'langcode' => 'und',
            'uid' => 1,
            'status' => 1,
            'field_old_id' => [['value' => $row[0]],],
             
            'body' => [
                [
                  'value' => $row[3],
                  'summary' => $row[3],
                  'format' => 'full_html',
                ]
            ],
            'field_date' => cn_convertDateToStorageFormat($row[1]),
            'field_hp_date' => date('Y-m-d',strtotime($row[1])),
            'field_folders' => [['target_id' => $folder],],
            'field_seq' => $row[5],
            
        ]);
        //$node->save();
        
        $out .= $row[2].'<br>';
        
        
        
        //break;

    }

    return $out;
}

/**
 *  Перенос quotes со старого сайте
 * 
 * 
 */
function cn_createQuotes(){
    $out = '';
    $text = file_get_contents('quotes.txt');
    $text = explode('--#--',$text);
    
    $tags = array();
    foreach($text as $str){
        //break;
        if($str == '') break;

        $row = explode('--|--',$str);
        // site folder
        $folder = $row[4];
        $comments = 'closed';
        if($row[6] == 2) $comments = 'open'; 
       
        $node = Drupal\node\Entity\Node::create([
            'type' => 'quote',
            'title' => $row[2],
            'langcode' => 'und',
            'uid' => 1,
            'status' => 1,
            'field_old_id' => [['value' => $row[0]],],
             
            'body' => [
                [
                  'value' => $row[3],
                  'summary' => strip_tags($row[3]),
                  'format' => 'full_html',
                ]
            ],
            'field_date' => cn_convertDateToStorageFormat($row[1]),
            'field_hp_date' => date('Y-m-d',strtotime($row[1])),
            'field_folders' => [['target_id' => $folder],],
            'field_comments' => [['value' => $comments],],
            'field_sources' => [['value' => $row[5]],],
        ]);
        //$node->save();
        
        $out .= $row[2].'<br>';
        
        
        
        //break;

    }

    return $out;
}

/**
 *  Перенос DE quotes со старого сайте
 * 
 * 
 */
function cn_createDEQuotes(){
    $out = '';
    $text = file_get_contents('dequotes.txt');
    $text = explode('--#--',$text);
    
    $tags = array();
    foreach($text as $str){
        //break;
        if($str == '') break;

        $row = explode('--|--',$str);
        // site folder
        $folder = $row[4];
        $comments = 'closed';
        if($row[6] == 2) $comments = 'open'; 
        
        // de folders
        $nTags = array();
        $oTags = explode(',',$row[7]);
         
        foreach($oTags as $oTag){
            if(isset($tags[$oTag])) {
                if(!in_array($tags[$oTag],$nTags)) $nTags[] = $tags[$oTag];
            } else {
                $query = \Drupal::entityQuery('taxonomy_term')
                ->condition('vid', 'de')
                ->condition('field_old_tid', $oTag);
                $tids = $query->execute();  

                if(count($tids)){
                    $tid = end($tids); //found!
                } else {
                    $tid = 1003; // hidden tag
                }
                if(!in_array($tid,$nTags)) $nTags[] = $tid;
                $tags[$oTag] = $tid;
            }
        }
        $tagValues = array();
        foreach($nTags as $nTag){
            if($nTag != 1003) $tagValues[] = array('target_id'=> $nTag);        
        }

        $node = Drupal\node\Entity\Node::create([
            'type' => 'quote',
            'title' => $row[2],
            'langcode' => 'und',
            'uid' => 1,
            'status' => 1,
            'field_old_id' => [['value' => $row[0]],],
             
            'body' => [
                [
                  'value' => $row[3],
                  'summary' => strip_tags($row[3]),
                  'format' => 'full_html',
                ]
            ],
            'field_date' => cn_convertDateToStorageFormat($row[1]),
            'field_hp_date' => date('Y-m-d',strtotime($row[1])),
            'field_folders' => [['target_id' => $folder],],
            'field_de_industry' => $tagValues,
            'field_comments' => [['value' => $comments],],
            'field_sources' => [['value' => $row[5]],],

        ]);
        //$node->save();
        
        $out .= $row[2].'<br>';
        
        
        
        //break;

    }

    return $out;
}


/**
 *  Перенос DE новостей со старого сайте
 * 
 * 
 */
function cn_createDENews(){
    $out = '';
    $text = file_get_contents('denews.txt');
    $text = explode('--#--',$text);
    
    $tags = array();
    foreach($text as $str){
        //break;
        if($str == '') break;

        $row = explode('--|--',$str);
        $comments = 'closed';
        if($row[6] == 2) $comments = 'open';
        
        // de folders
        $nTags = array();
        $oTags = explode(',',$row[5]);
         
        foreach($oTags as $oTag){
            if(isset($tags[$oTag])) {
                if(!in_array($tags[$oTag],$nTags)) $nTags[] = $tags[$oTag];
            } else {
                $query = \Drupal::entityQuery('taxonomy_term')
                ->condition('vid', 'de')
                ->condition('field_old_tid', $oTag);
                $tids = $query->execute();

                if(count($tids)){
                    $tid = end($tids); //found!
                } else {
                    $tid = 1003; // hidden tag
                }
                if(!in_array($tid,$nTags)) $nTags[] = $tid;
                $tags[$oTag] = $tid;
            }
        }
        $tagValues = array();
        foreach($nTags as $nTag){
            if($nTag != 1003) $tagValues[] = array('target_id'=> $nTag);        
        }
        //if(!count($tagValues)) $tagValues[] = array('target_id'=> 1003);         

        
        $node = Drupal\node\Entity\Node::create([
            'type' => 'article',
            'title' => $row[2],
            'langcode' => 'und',
            'uid' => 1,
            'status' => 1,
            'field_old_id' => [['value' => $row[0]],],
            'field_seq' => [['value' => $row[4]],],
            'body' => [
                [
                  'value' => $row[3],
                  'summary' => $row[3],
                  'format' => 'full_html',
                ]
            ],
            'field_date' => cn_convertDateToStorageFormat($row[1]),
            'field_hp_date' => date('Y-m-d',strtotime($row[1])),
            'field_folders' => [['target_id' => 1012],],
            'field_tags' => [['target_id' => 1003],], // hidden tag
            'field_de_industry' => $tagValues,
            'field_comments' => [['value' => $comments],],
            
        ]);
        //$node->save();
        
        $out .= $row[2].'<br>';
        
        
        
        //break;

    }

    return $out;
}

/**
 *  Перенос DE opinions со старого сайте
 * 
 * 
 */
function cn_createDEOpinions(){
    $out = '';
    $text = file_get_contents('deopinions.txt');
    $text = explode('--#--',$text);
    
    $tags = array();
    foreach($text as $str){
        //break;
        if($str == '') break;

        $row = explode('--|--',$str);
        $comments = 'closed';
        if($row[8] == 2) $comments = 'open';
        
        // de folders
        $nTags = array();
        $oTags = explode(',',$row[5]);
         
        foreach($oTags as $oTag){
            if(isset($tags[$oTag])) {
                if(!in_array($tags[$oTag],$nTags)) $nTags[] = $tags[$oTag];
            } else {
                $query = \Drupal::entityQuery('taxonomy_term')
                ->condition('vid', 'de')
                ->condition('field_old_tid', $oTag);
                $tids = $query->execute();

                if(count($tids)){
                    $tid = end($tids); //found!
                } else {
                    $tid = 1003; // hidden tag
                }
                if(!in_array($tid,$nTags)) $nTags[] = $tid;
                $tags[$oTag] = $tid;
            }
        }
        $tagValues = array();
        foreach($nTags as $nTag){
            if($nTag != 1003) $tagValues[] = array('target_id'=> $nTag);        
        }
        //if(!count($tagValues)) $tagValues[] = array('target_id'=> 1003);         

        
        $node = Drupal\node\Entity\Node::create([
            'type' => 'interview',
            'title' => $row[2],
            'langcode' => 'und',
            'uid' => 1,
            'status' => 1,
            'field_old_id' => [['value' => $row[0]],],
            'field_seq' => [['value' => $row[4]],],
            'body' => [
                [
                  'value' => $row[3],
                  'summary' => $row[3],
                  'format' => 'full_html',
                ]
            ],
            'field_date' => cn_convertDateToStorageFormat($row[1]),
            'field_hp_date' => date('Y-m-d',strtotime($row[1])),
            'field_folders' => [['target_id' => 1016],],
            'field_de_industry' => $tagValues,
            'field_authors' => [['value' => $row[6]],],
            'field_i_persons' => [['value' => $row[7]],],
            'field_comments' => [['value' => $comments],],
            
        ]);
        //$node->save();
        
        $out .= $row[2].'<br>';
        
        
        
        //break;

    }

    return $out;
}


/**
 *  Перенос выставок со старого сайте
 * 
 * 
 */
function cn_createExhibitions(){
    $out = '';
    $text = file_get_contents('exhibitions.txt');
    $text = explode('#####',$text);
    
    $tags = array();
    foreach($text as $str){
        break;
        if($str == '') break;

        $row = explode('--|--',$str);
        
        $d1 = explode(' ',$row[1])[0];
        $d2 = '';
        if(!empty($row[2])) $d2 = explode(' ',$row[2])[0]; 

         
       

        $node = Drupal\node\Entity\Node::create([
            'type' => 'event',
            'title' => $row[3],
            'langcode' => 'und',
            'uid' => 1,
            'status' => 1,
            
            'body' => [
                [
                  'value' => $row[5],
                  'summary' => $row[4],
                  'format' => 'full_html',
                ]
            ],

            'field_start_date' => $d1,
            'field_end_date' => $d2,
            'field_country' => $row[6],
            'field_city' => $row[7],
            'field_event_type' => $row[8],
            //'field_issues' => $issues,
        ]);
        //$node->save();
        
        $out .= $row[3].'<br>';
        
        
        
        break;

    }

    return $out;
}

/**
 * создаем номера журнала стандарт из файла
 */
function cn_createStandartIssuesFromFile(){

    $out = '';
    $text = file_get_contents('standart.txt');
    $text = explode('#####',$text);
    
    foreach($text as $str){
        
        break;

        $row = explode('-|-',$str);
        if($row[5] != ''){
            $title = '№'.$row[5].'('.$row[6].') '.$row[7].' '.$row[8];
        } else {
            $title = $row[2];
        }    
        $issues = array();
        if($row[9] !=''){
            $issues[] = array('value'=>'Журнал|'.$row[9].'|'.$row[10].'|'.$row[11].'|'.$row[12]);
        }
        if($row[13] !=''){
            $issues[] = array('value'=>'Приложение к журналу|'.$row[13].'|'.$row[14].'|'.$row[15].'|'.$row[16]);
        }
        if($row[17] !=''){
            $issues[] = array('value'=>'Журнал + приложение|'.$row[17].'|'.$row[18].'|'.$row[19].'|'.$row[20]);
        }
        $node = Drupal\node\Entity\Node::create([
            'type' => 'standart',
            'title' => $title,
            'langcode' => 'und',
            'uid' => 1,
            'status' => 1,
            'field_old_id' => [['value' => $row[0]],],
            'field_seq' => [['value' => $row[1]],],
            'body' => [
                [
                  'value' => $row[3],
                  'summary' => '',
                  'format' => 'html',
                ]
            ],
            'field_cover_old' => [['value' => $row[4]],],
            'field_n' => [['value' => $row[5]],],
            'field_number' => [['value' => $row[6]],],
            'field_month' => [['value' => $row[7]],],
            'field_year' => [['value' => $row[8]],],
            'field_issues' => $issues,
        ]);
        $node->save();
        
        $out .= $title.'<br>';
        
        
        
        break;
    }
    

    return $out;
}
