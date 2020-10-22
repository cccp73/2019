<?php
/**
 * @file
 * Contains \Drupal\helloworld\Controller\HelloWorldController.
 * ^ Пишется по следующему типу:
 *  - \Drupal - это указывает что данный файл относится к ядру Drupal, ведь
 *    теперь там еще есть Symfony.
 *  - helloworld - название модуля.
 *  - Controller - тип файла. Папка src опускается всегда.
 *  - HelloWorldController - название нашего класса.
 */

/**
 * Пространство имен нашего контроллера. Обратите внимание что оно схоже с тем
 * что описано выше, только опускается название нашего класса.
 */
namespace Drupal\comnews\Controller;

/**
 * Используем друпальный класс ControllerBase. Мы будем от него наследоваться,
 * а он за нас сделает все обязательные вещи которые присущи всем контроллерам.
 */
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use \GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpFoundation\RedirectResponse;
/**
 * Объявляем наш класс-контроллер.
 */
class ComNewsController extends ControllerBase {
  /**
   * {@inheritdoc}
   *
   * отрисовываем контент статьи на адресе /content/XXX
   */
  public function renderTest() {
    return array('#title'=>'TEST','#children'=>cn_test());
  }
  /**
   * {@inheritdoc}
   *
   * отрисовываем list of voprosa на адресе /polls/XXX
   */
  public function renderPolls($misc = null) {
    
    $title = 'Вопрос недели';
    
    
    $body = '';
    $month = date('Y-m');
    
    if($misc){
      $tmp = explode('-',$misc);
      if(isset($tmp[0]) && intval($tmp[0])){
        if(isset($tmp[1]) && intval($tmp[1])) $month = intval($tmp[0]).'-'.cn_lidZero(intval($tmp[1]),2);
      }
      
    }

    $title = '<div class="s1"><div>Вопрос недели <span class="black"> / '.cn_month(strtotime($month.'-01'),'январь').'</span></div></div>';
    $output = array('#title'=>$title,'#children'=>'');
    cn_setHTMLTitle($output['#title']);

    $d1 = $month.'-01';
    $d2 = $d1.' + 1 month';

    //$d1 = '2020-01-01';
    //$d2 = $d1.' + 2 month';

    $query = \Drupal::entityQuery('node')
        ->condition('type', 'vopros')
        ->condition('status', 1)
        ->condition('field_date', cn_convertDateToStorageFormat($d1),'>=')
        ->condition('field_date', cn_convertDateToStorageFormat($d2),'<')->sort('field_date','DESC')
        ;
      $nids = $query->execute();
     
    if(!count($nids)){
      $d1 = $month.'-01 - 1 month';
      $d2 = $month.'-01';
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'vopros')
        ->condition('status', 1)
        ->condition('field_date', cn_convertDateToStorageFormat($d1),'>=')
        ->condition('field_date', cn_convertDateToStorageFormat($d2),'<')->sort('field_date','DESC')
        ;
      $nids = $query->execute();
    
    }  
    if(count($nids)){
      $node_storage = \Drupal::entityTypeManager()->getStorage('node');
      $nodes = $node_storage->loadMultiple($nids);
      $node = current($nodes);
      if($node->field_end_date->value >= date('Y-m-d') && $node->field_date->value <= cn_convertDateToStorageFormat('today')){
        $url = cn_getNodeAlias($node);
        $output['#children'] .= '<div class="poll"><div class="poll-dates">'.cn_convertDateFromStorageFormat($node->field_date->value,'d.m.Y').' - '.cn_convertDateFromStorageFormat($node->field_end_date->value.'T00:00:00','d.m.Y').'</div><h2><a href="'.$url.'">'.$node->title->value.'</a></h2><div class="v-form">
              <div class="v-graph 2019" rel="'.$node->id().'" style="margin:20px auto; min-height:250px;">
                <img src="/img/1/loader.gif" style="margin: 60px auto 0px; display:block;" class="loader"/>
              </div>
            </div></div>';  
            array_splice($nodes, 0, 1);    
      }
      foreach($nodes as $node){
        $url = cn_getNodeAlias($node);
        $output['#children'] .= '<div class="poll"><div class="poll-dates">'.cn_convertDateFromStorageFormat($node->field_date->value,'d.m.Y').' - '.cn_convertDateFromStorageFormat($node->field_end_date->value.'T00:00:00','d.m.Y').'</div><h3><a href="'.$url.'">'.$node->title->value.'</a></h3><div class="v-form">
              <div class="v-graph" rel="'.$node->id().'" style="margin:20px auto; min-height:250px;">
                '.cn_getVoprosResults($node->id()).'
              </div>
            </div></div>';
      }
      
    }
    for($yy = intval(date('Y')); $yy >= 2020; $yy--){
      
      $y1 .= '<hr><div style="text-alighn:right">'.$yy.' : ';
      //'<a href="/polls/2020-01">январь</a></div>';
      for($i=1; $i < intval(date('m')); $i++){ 
        $y1 .= '<a href="/polls/'.$yy.'-'.cn_lidZero($i,2).'">'.cn_month(strtotime($yy.'-'.cn_lidZero($i,2).'-01'),'январь').'</a> | ';
      }
      $y1 .='</div>';
    }
    $y2 = '<hr><div style="text-alighn:right">2019 : ';
    for($i=10; $i <= 12; $i++){ $y2 .= '<a href="/polls/2019-'.cn_lidZero($i,2).'">'.cn_month(strtotime('2019-'.cn_lidZero($i,2).'-01'),'январь').'</a> | ';}
    $y2 .='</div>';

    $output['#children'] .= $y1.$y2.'<div class="polls-arhiv arhiv-link"><a href="/arhiv/polls">Архив вопросов недели</a></div>';
    return $output;
  }
  /**
   * {@inheritdoc}
   *
   * отрисовываем список выставок
   */
  public function renderExhibitions($misc = null) {
    
    $body = '';
    $month = date('Y-m');
    
    if($misc){
      $tmp = explode('-',$misc);
      if(isset($tmp[0]) && intval($tmp[0])){
        if(isset($tmp[1]) && intval($tmp[1])) $month = intval($tmp[0]).'-'.cn_lidZero(intval($tmp[1]),2);
      }
      
    }

    $m = explode('-',$month);
    $title = '<div class="s1"><div>Конференции / Выставки <span class="black"> / '.cn_month(strtotime($month.'-01'),'январь').' '.$m[0].'</span></div></div>';
    $output = array('#title'=>$title,'#children'=>'');
    cn_setHTMLTitle($output['#title']);

    $d1 = $month.'-01';
    $d2 = $d1.' + 1 month';

    $output['#children'] .= cn_renderExhibitions($d1,$d2);
    
    
    return $output;
  }
  /**
   * {@inheritdoc}
   *
   * отрисовываем страницу выставки
   */
  public function renderExhibition($id = null) {
    $output = array();
    $node = \Drupal\node\Entity\Node::load($id);
    if($node && $node->getType() == 'event' && $node->isPublished() == 1){ // если статья есть в базе
      
      $output['#title'] = '<div class="s1"><div>Конференции / Выставки</div></div><div class="s2">'.$node->getTitle().'</div>';
      cn_setHTMLTitle('Конференции / Выставки : '.$node->getTitle());
      
      $title = 'Конференции / Выставки : '.$node->getTitle();
      
      $body = '<div class="dates"><div>'.cn_dates($node->field_start_date->value,$node->field_end_date->value).'</div></div>'
      .cn_renderField($node->body->value,'full-html field-name-body')
      ;

      $soc_img = 'https://www.comnews.ru/img/3/soc-logo4.png';

      cn_setMetaTag('property="og:title"',$title);
      cn_setMetaTag('property="og:image"',$soc_img);

      $shareBtns = '<div id="sharebuttons-up" class="sharebuttons">
              <div class="ya-share2" 
                data-services="vkontakte,facebook,twitter,telegram"
                data-title="ComNews.ru : '.cn_t($title).'"
                data-image="'.$soc_img.'"
                data-url="https://www.comnews.ru/exhibition/'.$node->id.'"
              >
          </div></div>';
      $body = '<div id="node-'.$id.'" class="node-wrapper node node-'.str_replace('_','-',$node->getType()).'">'.$shareBtns.'<div class="node-txt">'.$body.'</div></div>';        
      $output['#children'] = cn_renderAdmLinksForNode($id).$body;
      /*******************/
      $m = explode('-',$node->field_start_date->value);
      
      $output['#children'] .= cn_renderBannerSite('bnExhibitions',0);

      $output['#children'] .= '<h1 class="page-header"><div class="s1"><div>Конференции / Выставки <span class="black"> / '.cn_month($node->field_start_date->date->getTimestamp(),'январь').' '.$m[0].'</span></div></div></h1>';
      
      $d1 = $m[0].'-'.$m[1].'-01';
      $d2 = $d1.' + 1 month';

      $output['#children'] .= cn_renderExhibitions($d1,$d2,1);


    } else throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    
    return $output;
  }
  

  /**
   * {@inheritdoc}
   *
   * отрисовываем архив
   */
  public function renderArhiv($mode = null,$misc = null) {
    $output = array();
    $title = '';
    $body = '';
    $allowedModes = array('news','point-of-view','shortnews','editorials','tag','polls','quotes','rebuttals','pressreleases','regions');
    if(!in_array($mode,$allowedModes)){
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();  
    }
    $page = intval(\Drupal::request()->query->get('page'));
    switch ($mode){
      case 'news':  $url = 'news';
                    $_url = '/arhiv/'.$mode; // адрес для пагинатора
                    $title = 'Новости <span class="black">/ Архив</span>';
                    break;
      case 'point-of-view':  $url = 'point-of-view';
                    $_url = '/arhiv/'.$mode; // адрес для пагинатора
                    $title = 'Точка зрения <span class="black">/ Архив</span>';
                    break;  
      case 'shortnews':  $url = 'shortnews';
                    $_url = '/arhiv/'.$mode; // адрес для пагинатора
                    $title = 'В фокусе <span class="black">/ Архив</span>';
                    break;    
      case 'editorials':  $url = 'editorials';
                    $_url = '/arhiv/'.$mode; // адрес для пагинатора
                    $title = 'Редколонка <span class="black">/ Архив</span>';
                    break;
      case 'quotes':  $url = 'quotes';
                    $_url = '/arhiv/'.$mode; // адрес для пагинатора
                    $title = 'Цитата дня <span class="black">/ Архив</span>';
                    break;                  
      case 'polls': $url = 'taxonomy/term/311';
                    $_url = '/arhiv/'.$mode; // адрес для пагинатора
                    $title = 'Вопрос недели <span class="black">/ Архив</span>';
                    break;  
      case 'rebuttals': $url = 'taxonomy/term/10';
                    $_url = '/arhiv/'.$mode; // адрес для пагинатора
                    $title = 'Опровержения <span class="black">/ Архив</span>';
                    break; 
      case 'pressreleases': $url = 'pressreleases';
                    $_url = '/arhiv/'.$mode; // адрес для пагинатора
                    $title = 'Новости компаний <span class="black">/ Архив</span>';
                    break;
      case 'regions': $url = 'taxonomy/term/23';
                    $_url = '/arhiv/'.$mode; // адрес для пагинатора
                    $title = 'Региональные новости <span class="black">/ Архив</span>';
                    break;              
      /*              
      case 'solutions': $url = 'pressreleases';
                    $_url = '/arhiv/'.$mode; // адрес для пагинатора
                    $title = 'Новости компаний <span class="black">/ Архив</span>';
                    break;                                            
      */              
      case 'tag':   if(intval($misc)){
                      $term = \Drupal\taxonomy\Entity\Term::load(intval($misc));
                    } else {
                      $path = explode('/', \Drupal::service('path.alias_manager')->getPathByAlias('/'.$misc));
                      $term = \Drupal\taxonomy\Entity\Term::load(intval(end($path)));
                    }
                    $url = 'taxonomy/term/'.$term->field_old_tid->value; 
                    $_url = '/arhiv/'.$mode.'/'.$misc;
                    $title = $term->getName().' <span class="black">/ Архив</span>';
                    break;

      default: $url = '';
    }
     
    try{
      //$client = \Drupal::httpClient(); 
      //$request = $client->get('https://archiv.comnews.ru/'.$url.'?page='.$page); 
      $turl = 'https://archiv.comnews.ru/'.$url.'?page='.$page;
      // 
      //$markup = (string)$request->getBody();
      $arrContextOptions=array("ssl"=>array("verify_peer"=>false,"verify_peer_name"=>false,),); 
			$markup = file_get_contents($turl, false, stream_context_create($arrContextOptions));
      require_once('phpQuery.php');
      $doc = \phpQuery::newDocumentHTML($markup);
      // чистим 
      pq('script, .ya-cn')->remove();
      // адрес в пагинаторе
      $surl = explode('?',$doc->find('#block-system-main .nav-line a')[0]->attr('href'));
      $surl = $surl[0];
      // пагинатор
      $navline = $doc->find('#block-system-main .nav-line')->html();  
      $navline = cn_transformOldNavLine($navline,$surl,$_url);
      
      //тело
      $body = $doc->find('#block-system-main .view-content')->html();
      
      $output['#title'] = '<div class="s1"><div>'.$title.'</div></div>';
      cn_setHTMLTitle($output['#title']);
      
      $output['#children'] = '<div class="view view-arhiv"><div class="view-header"></div><div class="view-content">'.$body.$navline.'</div></div>';
    } catch (RequestException $exception) {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }  
    return $output;
  }

  /**
   * {@inheritdoc}
   *
   * отрисовываем DE архив
   */
  public function renderDEArhiv($mode = null,$misc = null) {
    $output = array();
    $title = '';
    $body = '';
    //var_dump($mode);die;
    $allowedModes = array('news','opinions','quotes','companies');
    if(!in_array($mode,$allowedModes)){
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();  
    }
    $page = intval(\Drupal::request()->query->get('page'));
    $url = 'digital-economy/'.$mode;
    $_url = '/digital-economy/arhiv/'.$mode;
    if(!empty($misc)) {
      $url = $url .'/'. $misc;
      $_url = $_url .'/'. $misc;
    }   
    
    $title = 'Архив';
    if($mode == 'news') $title = 'Новости <span class="black">/ Архив</span>';
    if($mode == 'opinions') $title = 'Мнение <span class="black">/ Архив</span>';
    if($mode == 'quotes') $title = 'Цитаты <span class="black">/ Архив</span>';
     
    try{
      //$client = \Drupal::httpClient(); 
      //$request = $client->get('https://archiv.comnews.ru/'.$url.'?page='.$page); 
      $turl = 'https://archiv.comnews.ru/'.$url.'?page='.$page;
      // 
      //$markup = (string)$request->getBody();
      $arrContextOptions=array("ssl"=>array("verify_peer"=>false,"verify_peer_name"=>false,),); 
			$markup = file_get_contents($turl, false, stream_context_create($arrContextOptions));
      require_once('phpQuery.php');
      $doc = \phpQuery::newDocumentHTML($markup);
      // чистим 
      pq('script, .ya-cn')->remove();
      // адрес в пагинаторе
      $surl = explode('?',$doc->find('#block-system-main .nav-line a')[0]->attr('href'));
      $surl = $surl[0];
      
      
      if($mode == 'companies'){
        $doc->find('.view-de-news')->addClass('view-arhiv');
        $title = $doc->find('.pane-title')->text().' <span class="black">/ Архив</span>';
        
        $subtitle = $doc->find('.pane-de-category strong')->text();
        $doc->find('.pane-de-category')->append('<h2 class="solutions"><span><p><strong>'.$subtitle.'</strong></p>
        </span></h2>');
        $doc->find('.pane-title,.pane-de-category .pane-content')->remove();
        $body = $doc->find('#de-companies')->html();
        $navline = $doc->find('#block-system-main .nav-line')->html();  
        $newnavline = cn_transformOldNavLine($navline,$surl,$_url);
        $body = str_replace($navline,$newnavline,$body);
        $output['#children'] = str_replace($surl,$_url,$body);


      } else {
        // пагинатор
        $navline = $doc->find('#block-system-main .nav-line')->html();  
        $navline = cn_transformOldNavLine($navline,$surl,$_url);
        //тело
        $body = $doc->find('#block-system-main .view-content')->html();
        $output['#children'] = '<div class="view view-arhiv"><div class="view-header"></div><div class="view-content">'.$body.$navline.'</div></div>';
      }
      $output['#title'] = '<div class="s1"><div>'.$title.'</div></div>';
      cn_setHTMLTitle($output['#title']);
    } catch (RequestException $exception) {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }  
    return $output;
  }
  /**
   * {@inheritdoc}
   *
   * отрисовываем DE companies page
   */
  public function renderDECompanies($mode = '') {
    $output = array();
    $title = '';
    $body = '';
      
    //var_dump($mode);die;
    $url =  explode('/',\Drupal::service('path.alias_manager')->getPathByAlias('/'.$mode));
    if($url[1] != 'taxonomy'){
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }
    $tid = intval(end($url));

    $term = \Drupal\taxonomy\Entity\Term::load(intval($tid));
    $title = 'Новости <span class="black">/ '.$term->name->value.'</span>';
    $nodes = views_embed_view('rubrika', 'page_5', $tid);
    $body .= \Drupal::service('renderer')->render($nodes);

    $output['#title'] = '<div class="s1"><div>'.$title.'</div></div>';
    cn_setHTMLTitle($output['#title']);
    $body .= '<h2 class="solutions"><span>'.$term->description->value.'</span></h2>';

    try{
      //$client = \Drupal::httpClient(); 
      //$request = $client->get('https://archiv.comnews.ru/digital-economy/companies/'.$mode); 
      $turl = 'https://archiv.comnews.ru/digital-economy/companies/'.$mode;
      // 
      //$markup = (string)$request->getBody();
      $arrContextOptions=array("ssl"=>array("verify_peer"=>false,"verify_peer_name"=>false,),); 
			$markup = file_get_contents($turl, false, stream_context_create($arrContextOptions));
      require_once('phpQuery.php');
      $doc = \phpQuery::newDocumentHTML($markup);
      // чистим 
      pq('script, .ya-cn')->remove();
      
      //тело
      $body .= $doc->find('.pane-de-companies .pane-content')->html();
      
      $output['#children'] = $body;
    } catch (RequestException $exception) {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }  
    return $output;
  }
  /**
   * {@inheritdoc}
   *
   * отрисовываем контент статьи на адресе /content/XXX
   */
  public function renderNode($id = null, $misc = null) {
    $output = array();
    $body = '';
    $node = \Drupal\node\Entity\Node::load($id);
    if($node){ // если статья есть в базе
      
      if(!$node->isPublished() && !cn_isAdmin() && !isset($_REQUEST['pass']) && !isset($_REQUEST['pass'])) throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException(); //  

      $request_url = explode('?',\Drupal::request()->getRequestUri());
      $q = '';
      if(isset($request_url[1])&& !empty($request_url[1])) $q = '?'.$request_url[1];
      $node_url = cn_getNodeAlias($node);
      if($request_url[0] != $node_url){
          \Drupal::service('comnews.boost')->clear();
          $response = new RedirectResponse($node_url.$q, 302);
          $response->send();
      }
      $description = $node->body->summary;
      if(empty($description)){
        $description = explode('==#==',strip_tags(str_replace('</p>','==#==',$node->body->value)));
        $description = !empty(trim($description[0]))?trim($description[0]):(isset($description[1])?trim($description[1]):'');
      } 

      $tmp = $node->field_folders->getValue();
      $folders = array();
      foreach($tmp as $t){
        $folders[] = $t['target_id'];
      }
      $d = date('d.m.Y',$node->field_date->date->getTimestamp());
      $d1 = cn_month($node->field_date->date->getTimestamp()).' '.date('Y',$node->field_date->date->getTimestamp());   
      $oldnews_tags = array();
      if($node->getType() == 'article'){
        // article ******************************************************************************************
        $folder = 'Новости';
        if(in_array(1011, $folders)) $folder = 'Региональные новости';
        if(in_array(1010, $folders)) $folder = 'В фокусе';

        $tags = array();
            foreach($node->field_tags->getValue() as $f){
              if(!in_array($f['target_id'],array(1003))){
                $term = \Drupal\taxonomy\Entity\Term::load(intval($f['target_id']));
                $oldnews_tags[] = $term;
                $tags[] = '<a href="'.\Drupal::service('path.alias_manager')->getAliasByPath('/taxonomy/term/'.$term->id()).'">'.$term->name->value.'</a>';
              }
            }
        $tags = implode(' &bull; ',$tags);
        
        $output['#title'] = '<div class="s1"><div>'.$folder.'<span class="black"> / '.$d1.'</span></div></div><div class="tags">'.$tags.'</div><div class="s2">'.$node->getTitle().'</div>';
        $img = cn_renderImage($node->field_image,'large');
        $img_text = cn_renderField($node->field_image_text->getValue(),'image-text field-name-image-text');
        if(!empty($img)) $body .= '<div class="node-header">'.$img.$img_text.'<div class="clear"></div></div>';

        $body .= cn_renderField($node->field_authors->getValue(),'person field-name-authors')  
                .cn_renderField($node->field_source->getValue(),'source field-name-source')
                .cn_renderField($d,'field-name-date')
                
                .'<div id="vote-container"></div>'  
                .cn_renderField(cn_renderNodeImages($node->body->value,$node),'full-html field-name-body')
        ;
        if(!$node->field_no_ads->value){    
          cn_addToHiddenBanners(cn_renderBannerSite('bn0038'));    
        }
        
        
      } else if($node->getType() == 'interview'){
        // interview ****************************************************************************************
        $folder = 'Точка зрения';
        if(in_array(1014, $folders)) $folder = 'Опровержение';
        if(in_array(1170, $folders)) $folder = 'Видеоинтервью';
        $output['#title'] = '<div class="s1"><div>'.$folder.'<span class="black"> / '.$d1.'</span></div></div><div class="s2">'.$node->getTitle().'</div>';
        if(!empty($node->field_alt_person->value)){
          $person = cn_renderField($node->field_alt_person->value,'person field-name-persons');
        }  else {
          $person = cn_renderField($node->field_i_persons->getValue(),'person field-name-persons');
        }
        $body .= '<div class="node-header">'
                .cn_renderImage($node->field_image,'large')
                .$person  
                .'<div class="clear"></div></div>'
                .cn_renderField($node->field_authors->getValue(),'person field-name-authors')
                .cn_renderField($node->field_source->getValue(),'source field-name-source')
                .cn_renderField($d,'field-name-date')
                .'<div id="vote-container"></div>'  
                .cn_renderField(cn_renderNodeImages($node->body->value,$node),'full-html field-name-body')
                
        ;

      } else if($node->getType() == 'editorial'){
        // **************************************************************************************************
        $folder = 'Редколонка';
        $output['#title'] = '<div class="s1"><div>'.$folder.'<span class="black"> / '.$d1.'</span></div></div><div class="s2">'.$node->getTitle().'</div>';

        $body .= '<div class="node-header">'
                .cn_renderImage($node->field_image,'large')
                .cn_renderField($node->field_authors->getValue(),'person field-name-persons')
                .'<div class="clear"></div></div>'
                .cn_renderField($node->field_source->getValue(),'source field-name-source')
                .cn_renderField($d,'field-name-date')
                .'<div id="vote-container"></div>'   
                .cn_renderField(cn_renderNodeImages($node->body->value,$node),'full-html field-name-body')
        ;

      } else if($node->getType() == 'pressrelease'){
        // **************************************************************************************************
        $folder = 'Новости компаний';
        //ksm($folders);
        if(in_array(1019, $folders)) {
          $folder = 'Решения / Технологии';
        
            $body .= //'<div class="node-header">'.
            // cn_renderImage($node->field_image,'large')
            cn_renderField($node->field_authors->getValue(),'person field-name-authors')  
            .cn_renderField($node->field_source->getValue(),'source field-name-source')
            .cn_renderField($d,'field-name-date')
            //.'<div class="clear"></div></div>'
            //.'<div id="vote-container"></div>'  
            .cn_renderField(cn_renderNodeImages($node->body->value,$node),'full-html field-name-body')
            ;
          } else if(in_array(1162, $folders)){  
            $folder = '<a href="/covid-19">Oтрасль в ответ на COVID-19</a>';
            $body .= ''
            .cn_renderField($node->field_authors->getValue(),'person field-name-authors')  
            .cn_renderField($node->field_source->getValue(),'source field-name-source')
            .cn_renderField($d,'field-name-date')
            //.'<div id="vote-container"></div>'  
            .cn_renderField(cn_renderNodeImages($node->body->value,$node),'full-html field-name-body')
            ;
          } else {

            $body .= ''
            .cn_renderField($node->field_authors->getValue(),'person field-name-authors')  
            .cn_renderField($node->field_source->getValue(),'source field-name-source')
            .cn_renderField($d,'field-name-date')
            //.'<div id="vote-container"></div>'  
            .cn_renderField(cn_renderNodeImages($node->body->value,$node),'full-html field-name-body')
            ;
          }  
        $output['#title'] = '<div class="s1"><div>'.$folder.'<span class="black"> / '.$d1.'</span></div></div><div class="s2">'.$node->getTitle().'</div>';

      } else if($node->getType() == 'quote'){
        // **************************************************************************************************
        $folder = 'Цитата дня';
        $output['#title'] = '<div class="s1"><div>'.$folder.'<span class="black"> / '.$d1.'</span></div></div><div class="s2">'.$node->getTitle().'</div>';

            $body .= ''
            .cn_renderField($node->field_source->getValue(),'source field-name-source')
            .cn_renderField($d,'field-name-date')
            .'<div id="vote-container"></div>'  
            .cn_renderField(cn_renderNodeImages($node->body->value,$node),'quote full-html field-name-body')
            ;
      } else if($node->getType() == 'vopros'){
        // **************************************************************************************************
        $folder = 'Вопрос недели';
        $output['#title'] = '<div class="s1"><div>'.$folder.'<span class="black"> / '.$d1.'</span></div></div><div class="s2">'.$node->getTitle().'</div>';


        
          if($node->field_end_date->value >= date('Y-m-d') && $node->field_date->value <= cn_convertDateToStorageFormat('today')){
            $body .= '<div class="v-form">
                <div class="v-graph 2019" rel="'.$node->id().'" style="margin:20px auto; min-height:250px;">
                  <img src="/img/1/loader.gif" style="margin: 60px auto 0px; display:block;" class="loader"/>
                </div>
              </div>';
          } else {
            $body .= '<div class="v-form">
                <div class="v-graph" rel="'.$node->id().'" style="margin:20px auto; min-height:250px;">
                  '.cn_getVoprosResults($node->id()).'
                </div>
              </div>';
          }  
        
      } else if($node->getType() == 'review'){
        // review ****************************************************************************************
        if($node->field_vision_parent->target_id){
          $issue = \Drupal\node\Entity\Node::load($node->field_vision_parent->target_id);
          if($issue->field_seq->value != 9999) {
            $folder = 'Vision: <a href="/vision/'.$issue->field_seq->value.'/'.$issue->field_issue_url->value.'">'.$issue->title->value.'</a>';
            $footer = '<div class="review-back"><a href="/vision/'.$issue->field_seq->value.'/'.$issue->field_issue_url->value.'">Вернуться на главную страницу выпуска</a></div>';
          } else {
            $folder = '<a href="/vision/">Vision</a> ';  
            $footer = '<div class="review-back"><a href="/vision/">Вернуться на главную страницу</a></div>';
          }  
        } else {
          $folder = 'Обзор: <a href="/reviews/'.$node->field_r_parent->value.'">'.$node->field_r_title->value.'</a>';
          $footer = '<div class="review-back"><a href="/reviews/'.$node->field_r_parent->value.'">Вернуться на главную страницу обзора</a></div>';
        }
        cn_addToExcludedBanners('bn0007');
        //if(in_array(1014, $folders)) $folder = 'Опровержение';
        $output['#title'] = '<div class="s1"><div>'.$folder.'<span class="black"> / '.$d1.'</span></div></div><div class="s2">'.$node->getTitle().'</div>';
        
        if(count($node->field_i_persons->getValue())){
          $person = cn_renderField($node->field_i_persons->getValue(),'person field-name-persons');
          $body .= '<div class="node-header">'
                    .cn_renderImage($node->field_image,'large')
                    .$person  
                    .'<div class="v-quote">'.$node->field_image_text->value.'</div>'
                    .'<div class="clear"></div></div>';
        }
        
        
        
        $body .= ''
                .cn_renderField($node->field_authors->getValue(),'person field-name-authors')
                .cn_renderField($node->field_source->getValue(),'source field-name-source')
                .cn_renderField($d,'field-name-date')
                .'<div id="vote-container"></div>'  
                .cn_renderField(cn_renderNodeImages($node->body->value,$node),'full-html field-name-body')
                //
                .$footer.$css;
                
        

      }
      /************************************************************/
      

    // oldnews
    $oldnews = '';
    foreach($oldnews_tags as $term){
      
        $onews = '';
        /*
        $subterms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('tags', $parent = $term->tid, $max_depth = NULL, $load_entities = FALSE);
        $tags = array($term->tid);
        foreach($subterms as $subterm){
          $tags[] = $subterm->tid;
        }
        */
        $news_showed = array($node->id());
        $query = \Drupal::entityQuery('node')->condition('type', 'article')->condition('status', 1)
        //->condition('field_folders', [1000,1007,1008,1009,1011],'IN')
        ->condition('field_tags', $term->id(),'IN')
        ->condition('nid',$news_showed,'NOT IN')
        ->sort('field_date','DESC')->sort('field_seq','ASC')->sort('created','DESC')->range(0,2);
        $nids = $query->execute();
     
        $node_storage = \Drupal::entityTypeManager()->getStorage('node');
        $_nodes = $node_storage->loadMultiple($nids);
        
        foreach($_nodes as $_node){
          $news_showed[] = $_node->id();
          $lid = cn_renderLid($_node->body,200,true);
          $onews .= '<div class="block-node"><a href="'.cn_getNodeAlias($_node).'"><h4 class="node-title">'.$_node->title->value.'</h4><div class="node-text">'.$lid.'</div></a></div>';
           
        }
        if(!empty($onews)){
          $oldnews .= cn_renderHPblock('tag-'.$term->id(),$term->name->value,\Drupal::service('path.alias_manager')->getAliasByPath('/taxonomy/term/'.$term->id()),$onews,'gray-bg old-news');
            
        }
      
    }
      
      if(empty($soc_img)) $soc_img = cn_getImgUrl('original',$node->field_fb_image);
      if(empty($soc_img)) $soc_img = cn_getImgUrl('original',$node->field_image);
      if(empty($soc_img)) $soc_img = 'https://www.comnews.ru/img/3/soc-logo4.png';
        
      $title = $node->getTitle();
      cn_setHTMLTitle($title);  

      
      $description = preg_replace(array('/%[a-zA-Z0-9]+%/i'),array(''),$description);
      
      cn_setMetaTag('name="description"',$description);
      cn_setMetaTag('property="og:description"',$description);
      cn_setMetaTag('property="og:title"',$title);
      cn_setMetaTag('property="og:image"',$soc_img);

     

      $shareBtns = '<div id="sharebuttons-up" class="sharebuttons">
                        <div class="ya-share2" 
                          data-services="vkontakte,facebook,twitter,telegram"
                          data-title="ComNews.ru : '.cn_t($title).'"
                          data-description="'.cn_t($description).'"
                          data-image="'.$soc_img.'"
                          data-url="https://www.comnews.ru'.$node_url.'"
                        >
                    </div></div>';
      $body = '<div id="node-'.$id.'" class="node-wrapper node node-'.str_replace('_','-',$node->getType()).'">'.$shareBtns.'<div class="node-txt">'.$body.'</div></div>';

      $output['#children'] = cn_renderAdmLinksForNode($id).$body;


      
      //var_dump($node->field_comments->value);
      if(isset($node->field_comments) && $node->field_comments->value == 'open'){
        $output['#children'] .= '<div id="phpbb" data-node-nid="'.$id.'" data-node-type="'.$node->getType().'" data-node-comments="2" data-node-date="'.$node->field_date->value.'" data-node-mid="'.(intval($id)+100000).'" data-node-topic="0-0-0-0"><img src="/img/1/loader.gif" style="margin: 60px auto 0px; display:block;" class="loader"/></div>'; 
      } else {
        $output['#children'] .= '<div id="no-phpbb"></div>';
      }
      $output['#children'] .= cn_renderBannerSite('bn0042',0);
      if(!empty($oldnews)){
        $output['#children'] .= '<h3 class="othernews-header">Новости из связанных рубрик</h3>'.$oldnews;
      }

      $css = '';
      if(isset($node->field_css->value) && $node->field_css->value != ''){
        $css .= cn_renderNodeImages($node->field_css->value,$node);
        cn_addToHiddenBanners($css);
      }
      
    } else { // статьи нет в базе
      if($id < 200000){ // статья из архива
        try {
          // тянем статью из архива
          $url = 'https://archiv.comnews.ru/node/'.$id; 
          //$client = \Drupal::httpClient(); 
          //$request = $client->get($url); 
          // 
          //$markup = (string)$request->getBody();
          $arrContextOptions=array("ssl"=>array("verify_peer"=>false,"verify_peer_name"=>false,),); 
			    $markup = file_get_contents($url, false, stream_context_create($arrContextOptions));
          require_once('phpQuery.php');
          $doc = \phpQuery::newDocumentHTML($markup);
          // параметры статьи
          $node_type = $doc->find('#data-node')->attr('data-node-type');
          $node_date = $doc->find('#data-node')->attr('data-node-date');
          $node_comments = $doc->find('#data-node')->attr('data-node-comments');
          $node_url = $doc->find('#data-node')->attr('data-node-url');
          if(in_array($node_type, array('de_article_news','de_article_opinion','de_article_quot'))){
             $node_url = '/digital-economy'.$node_url; // статья цифровой экономики - принудительно редирект
          }   
          $node_mid = $doc->find('#data-node')->attr('data-node-oldid');
          $node_topic = $doc->find('#data-node')->attr('data-node-topic');

          //var_dump($node_type, $node_url, \Drupal::request()->getRequestUri());die;

          if($node_type != ''){
            // если статья запрошена не по основному адресу - редиректим на основной
            $request_url = explode('?',\Drupal::request()->getRequestUri());
            $q = '';
            if(isset($request_url[1])&& !empty($request_url[1])) $q = '?'.$request_url[1];
            if($request_url[0] != $node_url){
              $node_args = explode('/',$node_url);
              if($node_args[1] !== 'node'){  
                \Drupal::service('comnews.boost')->clear();
                $response = new RedirectResponse($node_url.$q, 302);
                $response->send();
              }
            }
          } else {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();  
          }
     
          
          $title = $doc->find('.node-txt h1,.node-txt h2,.node-txt h3, .node-txt h4, .node-txt h5')[0]->text();
          $folder = explode('|',$doc->find('.headerFolder')[0]->text());
          $folder = $folder[1];
          $d1 = cn_month(strtotime($node_date)).' '.date('Y',strtotime($node_date)); 

          $tags = $doc->find('.headerTags')[0]->html();         
          $output['#title'] = '<div class="s1"><div>'.$folder.'<span class="black"> / '.$d1.'</span></div></div><div class="tags">'.$tags.'</div><div class="s2">'.$title.'</div>';
          $doc->find('.node-txt h1,.node-txt h2,.node-txt h3, .node-txt h4, .node-txt h5')[0]->remove();
          
          $description = $doc->find('.node-txt .field-name-body .field-item, .node-txt .quote')->html();
          $description = explode('==#==',strip_tags(str_replace('</p>','==#==',$description)));
          $description = !empty(trim($description[0]))?trim($description[0]):(isset($description[1])?trim($description[1]):'');
          
          cn_setHTMLTitle($title);
          $img_to_remove = '.headerPanel + .field-name-field-bigimg,';
          $img = $doc->find('.headerPanel + .field-name-field-bigimg')[0]->html(); 
          $soc_img = 'https://www.comnews.ru/img/3/soc-logo4.png';   
          if(!empty($img)){
            $img_to_remove = '.field-name-field-bigimg,';
            $soc_img = str_replace('/styles/interview_big/public','',$doc->find('.headerPanel + .field-name-field-bigimg img')[0]->attr('src'));
            $img = '<div class="'.$doc->find('.headerPanel + .field-name-field-bigimg')[0]->attr('class').'">'.$img.'</div>';
          }
          // чистим текст от лишнего 
          pq('script, '.$img_to_remove.' .node-txt > .headerPanel, .article-subscribe-form, .field-name-field-folder, #sharebuttons, #shareinit, #cross, #phpbb, .vote, .ya-cn, .field-name-body style')->remove();
          
          
          if($node_type == 'vopros'){
            $url = 'https://archiv.comnews.ru/v.php?n='.$id.'&g=1'; 
            //$client = \Drupal::httpClient(); 
            //$request = $client->get($url); 
            $arrContextOptions=array("ssl"=>array("verify_peer"=>false,"verify_peer_name"=>false,),); 
			      $tmarkup = file_get_contents($url, false, stream_context_create($arrContextOptions));
            $doc->find('#nodegraph')->html($tmarkup /*(string)$request->getBody()*/);
          }
          
          $description = preg_replace(array('/%[a-zA-Z0-9]+%/i'),array(''),$description);
          cn_setMetaTag('name="description"',$description);
          cn_setMetaTag('property="og:description"',$description);
          cn_setMetaTag('property="og:title"',$title);
          cn_setMetaTag('property="og:image"',$soc_img);  

          // https://yandex.ru/dev/share/doc/dg/add-docpage/
          $shareBtns = '<div id="sharebuttons-up" class="sharebuttons">
              <div class="ya-share2" 
                data-services="vkontakte,facebook,twitter,telegram"
                data-title="ComNews.ru : '.cn_t($title).'"
                data-description="'.cn_t($description).'"
                data-image="'.$soc_img.'"
                data-url="https://www.comnews.ru'.$node_url.'"
              >
          </div></div>';
          $body = '<div id="node-'.$id.'" class="node-wrapper node-old node-'.str_replace('_','-',$node_type).'">'.$shareBtns.$img.$doc->find('#node-'.$id.' > .content')->html().'</div>';
          
          if($node_comments == 2 || $node_topic != '0-0-0-0'){
            $body .= '<div id="phpbb" data-node-nid="'.$id.'" data-node-type="'.$node_type.'" data-node-comments="'.$node_comments.'" data-node-date="'.$node_date.'" data-node-mid="'.$node_mid.'" data-node-topic="'.$node_topic.'"><img src="/img/1/loader.gif" style="margin: 60px auto 0px; display:block;" class="loader"/></div>'; 
          }
          // отрисовка
          $output['#children'] = cn_renderAdmLinksForNode($id).$body; 
          
        }
        catch (RequestException $exception) {
          throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
        }
      } else throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }
    return $output;
  }

  /**
   * {@inheritdoc}
   *
   * отрисовываем контент статьи DE на адресе /digital-economy/content/XXX
   */
  public function renderDENode($id = null, $misc = null) {
    
    $output = array();
    $body = '';
    $node = \Drupal\node\Entity\Node::load($id);
    if($node){ // если статья есть в базе
      if(!$node->isPublished() && !cn_isAdmin()) throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
      $request_url = explode('?',\Drupal::request()->getRequestUri());
      $q = '';
      if(isset($request_url[1])&& !empty($request_url[1])) $q = '?'.$request_url[1];
      $node_url = cn_getNodeAlias($node);
      
      if($request_url[0] != $node_url){
          \Drupal::service('comnews.boost')->clear();
          $response = new RedirectResponse($node_url.$q, 302);
          $response->send();
      }

      $description = $node->body->summary;
      if(empty($description)){
        $description = explode('==#==',strip_tags(str_replace('</p>','==#==',$node->body->value)));
        $description = !empty(trim($description[0]))?trim($description[0]):(isset($description[1])?trim($description[1]):'');
      } 

      $folders = $node->field_folders->getValue();
      $d = date('d.m.Y',$node->field_date->date->getTimestamp());
      $d1 = cn_month($node->field_date->date->getTimestamp()).' '.date('Y',$node->field_date->date->getTimestamp());
      
      $tags = array();
          foreach($node->field_de_industry->getValue() as $f){
            if(!in_array($f['target_id'],array(1003))){
              $term = \Drupal\taxonomy\Entity\Term::load(intval($f['target_id']));
              $c = explode(';',$term->field_de_color->value);
              $tags[] = '<a style="color:'.$c[1].'" href="/digital-economy/companies'.\Drupal::service('path.alias_manager')->getAliasByPath('/taxonomy/term/'.$term->id()).'">'.$term->name->value.'</a>';
            }
          }
      $tags = implode(' &bull; ',$tags);

      if($node->getType() == 'article'){
        // article **************************************
        $folder = 'Новости';
        
        $output['#title'] = '<div class="s1"><div>'.$folder.'<span class="black"> / '.$d1.'</span></div></div><div class="tags">'.$tags.'</div><div class="s2">'.$node->getTitle().'</div>';
        $img = cn_renderImage($node->field_image,'large');
        $img_text = cn_renderField($node->field_image_text->getValue(),'image-text field-name-image-text');
        if(!empty($img)) $body .= '<div class="node-header">'.$img.$img_text.'<div class="clear"></div></div>';

        $body .= cn_renderField($node->field_authors->getValue(),'person field-name-authors')  
                .cn_renderField($node->field_source->getValue(),'source field-name-source')
                .cn_renderField($d,'field-name-date')
                //.'<div id="vote-container"></div>'  
                .cn_renderField(cn_renderNodeImages($node->body->value,$node),'full-html field-name-body')
        ;    

      } else if($node->getType() == 'interview'){
        // interview ******************************************
        $folder = 'Мнение';
        $output['#title'] = '<div class="s1"><div>'.$folder.'<span class="black"> / '.$d1.'</span></div></div><div class="tags">'.$tags.'</div><div class="s2">'.$node->getTitle().'</div>';

        $body .= '<div class="node-header">'
                .cn_renderImage($node->field_image,'large')
                .cn_renderField($node->field_i_persons->getValue(),'person field-name-persons')  
                .'<div class="clear"></div></div>'
                .cn_renderField($node->field_authors->getValue(),'person field-name-authors')
                .cn_renderField($node->field_source->getValue(),'source field-name-source')
                .cn_renderField($d,'field-name-date')
                //.'<div id="vote-container"></div>'  
                .cn_renderField(cn_renderNodeImages($node->body->value,$node),'full-html field-name-body')
                
        ;  


      } else if($node->getType() == 'quote'){
        $folder = 'Цитата';
        $output['#title'] = '<div class="s1"><div>'.$folder.'<span class="black"> / '.$d1.'</span></div></div><div class="tags">'.$tags.'</div><div class="s2">'.$node->getTitle().'</div>';

        $body .= ''
            .cn_renderField($node->field_source->getValue(),'source field-name-source')
            .cn_renderField($d,'field-name-date')
            //.'<div id="vote-container"></div>'
            .cn_renderImage($node->field_image,'large')  
            .cn_renderField(cn_renderNodeImages($node->body->value,$node),'quote full-html field-name-body')
            ;

      } 
      /******************************************* */
        

      if(empty($soc_img)) $soc_img = cn_getImgUrl('original',$node->field_fb_image);
      if(empty($soc_img)) $soc_img = cn_getImgUrl('original',$node->field_image);
      if(empty($soc_img)) $soc_img = 'https://www.comnews.ru/img/3/soc-logo4.png';

      $title = $node->getTitle();
      cn_setHTMLTitle($title);  

      
      $description = preg_replace(array('/%[a-zA-Z0-9]+%/i'),array(''),$description);
      cn_setMetaTag('name="description"',$description);
      cn_setMetaTag('property="og:description"',$description);
      cn_setMetaTag('property="og:title"',$title);
      cn_setMetaTag('property="og:image"',$soc_img);

     

      $shareBtns = '<div id="sharebuttons-up" class="sharebuttons">
                        <div class="ya-share2" 
                          data-services="vkontakte,facebook,twitter,telegram"
                          data-title="ComNews.ru : '.cn_t($title).'"
                          data-description="'.cn_t($description).'"
                          data-image="'.$soc_img.'"
                          data-url="https://www.comnews.ru'.$node_url.'"
                        >
                    </div></div>';
      $body = '<div id="node-'.$id.'" class="node-wrapper node node-de node-'.str_replace('_','-',$node->getType()).'">'.$shareBtns.'<div class="node-txt">'.$body.'</div></div>';

      $output['#children'] = cn_renderAdmLinksForNode($id).$body;
      
      if(isset($node->field_comments) && $node->field_comments->value == 'open'){
        $output['#children'] .= '<div id="phpbb" data-node-nid="'.$id.'" data-node-type="'.$node->getType().'" data-node-comments="2" data-node-date="'.$node->field_date->value.'" data-node-mid="'.(intval($id)+100000).'" data-node-topic="0-0-0-0"><img src="/img/1/loader.gif" style="margin: 60px auto 0px; display:block;" class="loader"/></div>'; 
      }

    } else { // статьи нет в базе
      if($id < 200000){ // статья из архива
        try {
          // тянем статью из архива
          $url = 'http://archiv.comnews.ru/node/'.$id; 
          //$client = \Drupal::httpClient(); 
          //$request = $client->get($url); 
          // 
          //$markup = (string)$request->getBody();
          $arrContextOptions=array("ssl"=>array("verify_peer"=>false,"verify_peer_name"=>false,),); 
			    $markup = file_get_contents($url, false, stream_context_create($arrContextOptions));
          require_once('phpQuery.php');
          $doc = \phpQuery::newDocumentHTML($markup);
          // параметры статьи
          $node_type = $doc->find('#data-node')->attr('data-node-type');
          $node_date = $doc->find('#data-node')->attr('data-node-date');
          $node_comments = $doc->find('#data-node')->attr('data-node-comments');
          $node_url = '/digital-economy'.$doc->find('#data-node')->attr('data-node-url');
          
          $node_mid = $doc->find('#data-node')->attr('data-node-oldid');
          $node_topic = $doc->find('#data-node')->attr('data-node-topic');

          if($node_type != ''){
            // если статья запрошена не по основному адресу - редиректим на основной
            $request_url = explode('?',\Drupal::request()->getRequestUri());
            $q = '';
            if(isset($request_url[1])&& !empty($request_url[1])) $q = '?'.$request_url[1];
            if($request_url[0] != $node_url){
              $node_args = explode('/',$node_url);
              if($node_args[1] !== 'node'){  
                \Drupal::service('comnews.boost')->clear();
                $response = new RedirectResponse($node_url.$q, 302);
                $response->send();
              }
            }
          } else {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();  
          }


          $folder = '';
          $title = $doc->find('.node-txt h1,.node-txt h2,.node-txt h3')[0]->text();
          if($node_type == 'de_article_news'){
            $folder = 'Новости';
          }
          if($node_type == 'de_article_opinion'){
            $folder = 'Мнение';
          }
          if($node_type == 'de_article_quot'){
            $folder = 'Цитаты';
          }
          $d1 = cn_month(strtotime($node_date)).' '.date('Y',strtotime($node_date)); 
          $tags = $doc->find('.field-name-field-de-folder .field-item span')[0]->html();         
          $output['#title'] = '<div class="s1"><div>'.$folder.'<span class="black"> / '.$d1.'</span></div></div><div class="tags">'.$tags.'</div><div class="s2">'.$title.'</div>';
          pq('.node-txt h1,.node-txt h2,.node-txt h3')[0]->remove();
          cn_setHTMLTitle($title);

          $description = $doc->find('.node-txt .field-name-body .field-item, .node-txt .quote')->html();
          $description = explode('==#==',strip_tags(str_replace('</p>','==#==',$description)));
          $description = !empty(trim($description[0]))?trim($description[0]):(isset($description[1])?trim($description[1]):'');

          $img_to_remove = '.headerPanel + .field-name-field-bigimg,';
          $img = $doc->find('.headerPanel + .field-name-field-bigimg')[0]->html(); 
          $soc_img = 'https://www.comnews.ru/img/3/soc-logo4.png';   
          if(!empty($img)){
            $img_to_remove = '.field-name-field-bigimg,';
            $soc_img = str_replace('/styles/interview_big/public','',$doc->find('.headerPanel + .field-name-field-bigimg img')[0]->attr('src'));
            $img = '<div class="'.$doc->find('.headerPanel + .field-name-field-bigimg')[0]->attr('class').'">'.$img.'</div>';
          }
          // чистим текст от лишнего 
          pq('script, '.$img_to_remove.' .node-txt > .headerPanel, .article-subscribe-form, .field-name-field-folder, .field-name-field-de-folder, #sharebuttons, #shareinit, #cross, #phpbb, .vote, .ya-cn, .field-name-body style')->remove();
          $description = preg_replace(array('/%[a-zA-Z0-9]+%/i'),array(''),$description);
          cn_setMetaTag('name="description"',$description);
          cn_setMetaTag('property="og:description"',$description);
          cn_setMetaTag('property="og:title"',$title);
          cn_setMetaTag('property="og:image"',$soc_img);  

          // https://yandex.ru/dev/share/doc/dg/add-docpage/
          $shareBtns = '<div id="sharebuttons-up" class="sharebuttons">
              <div class="ya-share2" 
                data-services="vkontakte,facebook,twitter,telegram"
                data-title="ComNews.ru : '.cn_t($title).'"
                data-description="'.cn_t($description).'"
                data-image="'.$soc_img.'"
                data-url="https://www.comnews.ru'.$node_url.'"
              >
          </div></div>';
          $body = '<div id="node-'.$id.'" class="node-wrapper node-old node-de-article node-'.str_replace('_','-',$node_type).'">'.$shareBtns.$img.$doc->find('#node-'.$id.' > .content')->html().'</div>';

          if($node_comments == 2){
            $body .= '<div id="phpbb" data-node-nid="'.$id.'" data-node-type="de_article" data-node-comments="'.$node_comments.'" data-node-date="'.$node_date.'" data-node-mid="'.$node_mid.'" data-node-topic="'.$node_topic.'"><img src="/img/1/loader.gif" style="margin: 60px auto 0px; display:block;" class="loader"/></div>'; 
          }
          // отрисовка
          $output['#children'] = cn_renderAdmLinksForNode($id).$body; 
          
        }
        catch (RequestException $exception) {
          throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
        }
      } else throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }
    return $output;
  }
  /*

    отрисовывает last комменты 
  */
  public function renderLastComments($qnt) {
    $output = array();
    $output['#children'] = '';

    $url = 'https://comments.comnews.ru/cs/viewforum.php?f=4&sk=t&sd=d&view=comments'; 
    //$client = \Drupal::httpClient(); 
    //$request = $client->get($url); 
    // 
    //$markup = (string)$request->getBody();
    $arrContextOptions=array("ssl"=>array("verify_peer"=>false,"verify_peer_name"=>false,),); 
		$markup = file_get_contents($url, false, stream_context_create($arrContextOptions));
    require_once('phpQuery.php');
    $doc = \phpQuery::newDocumentHTML($markup);
    $body = '';
    $rows = $doc->find('.topiclist.topics .row');
    $i = 1;
    foreach ($rows as $row) {
      $pqRow = pq($row);

      $replies = $pqRow->find('.h-replies')->text();
      if($replies){
        $link = $pqRow->find('.text a')->attr('href');
        $title = $pqRow->find('.text h3')->text();
        $body .= '<div class="block-node small"> <a href="'.$link.'"><h4 class="node-title">[<span>'.$replies.'</span>] '.$title.'</h4></a></div>';
        $i++;
      }
      if($i > $qnt) break;
           
    }
    $output['#children'] = str_replace(array('http://www.comnews.ru','//www.comnews.ru'),'',$body); 

    return $output;
  }
  /*

    отрисовывает комменты для статьи /comments/ID
  */
  public function renderComments($id = null, $mode = '') {
    $output = array();
    $output['#children'] = '';
    $forum_id = 4; // форум комментов
    $id = intval($id);    
    if ($id > 0){
      if( $id < 200000 ){
        $type = \Drupal::request()->query->get('type');
        
        $date = \Drupal::request()->query->get('date');
        $comments = intval(\Drupal::request()->query->get('comments'));
        $mid = intval(\Drupal::request()->query->get('mid'));
        $topic = explode('-',\Drupal::request()->query->get('topic'));
        $topic_id = intval($topic[0]);

        db_set_active('forum');
          //достаем топик из базы phpbb
          $tmp_topic = db_query("SELECT * FROM {topics} WHERE material_id = :id", array(':id' => $mid))->fetchAll();
          if(count($tmp_topic)){
            $topic = intval($tmp_topic[0]->topic_id).'-'.intval($tmp_topic[0]->topic_replies).'-'.intval($tmp_topic[0]->like_cnt).'-'.intval($tmp_topic[0]->dislike_cnt);
            $topic_id = intval($tmp_topic[0]->topic_id);
            
          }
        db_set_active();
          
          

      } else {  
        $node = \Drupal\node\Entity\Node::load($id);
        if($node){
          // тип ноды
          $type = $node->getType();
          $date = '';
          $comments = 1; // closed
          if($node->field_comments->value == 'open') $comments = 2; // open
          $mid = $id + 100000;
          $topic_id = 0; //++
          $topic = array(0,0,0,0);
          
          db_set_active('forum');
            //достаем топик из базы phpbb
            $tmp_topic = db_query("SELECT * FROM {topics} WHERE material_id = :id", array(':id' => $mid))->fetchAll();
            if(count($tmp_topic)){
              $topic = intval($tmp_topic[0]->topic_id).'-'.intval($tmp_topic[0]->topic_replies).'-'.intval($tmp_topic[0]->like_cnt).'-'.intval($tmp_topic[0]->dislike_cnt);
              $topic_id = intval($tmp_topic[0]->topic_id);
            }
          db_set_active();
        }
      }

      // типы нод для которых разрешено комментирование
      if (($id < 200000 && // старый сайт
            ($type == '_oldarticle' || $type == '_article' || $type == 'interview' || $type == '_editor_column' || $type == 'quot' || $type == 'vopros' || $type == 'video' || $type == 'mtt_mvne' || $type == 'de_article' || $type == 'dtvnews' || $type == 'review'))
          ||  
          ($id > 200000 && // новый сайт
            ($type == 'article' || $type == 'interview' || $type == 'editorial' || $type == 'quote' || $type == 'vopros' || $type == 'review'))
      ){
        
        if ($type != '_oldarticle' && $comments ==2){
          if(!is_array($topic)) $t = explode('-',cn_getVal($topic));
          $output['#children'] .= '<div id="vote-src" style="display:none;"><div class="vote">[<span class="comments-icon" title="Комментарии">'.intval(cn_getVal($t[1])).'<img src="/img/3/comments-icon.png" border="0"/></span>, <a class="m-dislike" title="Не нравится" id="up-dislike-'.$mid.'" href="javascript:void(0);"><span id="up-dislike-'.$mid.'_span">-'.intval(cn_getVal($t[3])).'</span><img src="/img/3/dislike-icon.png" border="0"/></a>, <a class="m-like" title="Нравится" id="up-like-'.$mid.'" href="javascript:void(0);"><span id="up-like-'.$mid.'_span">+'.intval(cn_getVal($t[2])).'</span><img src="/img/3/like-icon.png" border="0"/></a>]</div></div>';
        }  
          
        if ($topic_id > 0){
            // топик уже создан
          if ($mode == 'reply'){
            // попали сюда поошибке - перенаправляем на страницу добавления комента
            header('Location: https://comments.comnews.ru/cs/posting.php?mode=reply&f='.$forum_id.'&t='.$topic_id);  
        
          } else {
            // достаем комменты
            $url = 'https://comments.comnews.ru/cs/viewtopic.php?f='.$forum_id.'&t='.$topic_id.'&start=0&view=comnews';
            
            //$client = \Drupal::httpClient(); 
            //$request = $client->get($url); 
            // Do whatever's needed to extract the data you need from the request... 
            //$content = (string)$request->getBody();      
            $arrContextOptions=array("ssl"=>array("verify_peer"=>false,"verify_peer_name"=>false,),); 
            $content = file_get_contents($url, false, stream_context_create($arrContextOptions));
            
            $output['#children'] .= '<div class="comments clearfix">';
                    
                    if(cn_isAdmin()){
              $output['#children'] .= '<div id="moderator" style="position:absolute;top:10px;left:150px; width:300px;padding:3px;background-color:red;color:white;">[ <a style="color:white;" href="//comments.comnews.ru/cs/viewtopic.php?f='.$forum_id.'&t='.$topic_id.'">модерация</a> ]</div>';	
            }
                    
                    
            $output['#children'] .= '<div class="field-items">';
            $content = str_replace('http://www.comnews.ru/img/1/ComNews-2011_Logo_R.png','//www.comnews.ru/img/1/ComNews-2011_Logo_R.png',$content);
            $output['#children'] .= '<div class="field-item">'.str_replace(array('./'),array('//comments.comnews.ru/cs/'),preg_replace('#<a([^<]*)href=["\']http://(?!comnews.ru|www\.comnews.ru|archiv\.comnews.ru|comments\.comnews.ru)([^"\']*)["\']([^<]*)>(.*)</a>#ismU',
            '<noindex><a$1href="http://$2"$3 rel="nofollow">$4</a></noindex>', $content)).'</div>';
            $output['#children'] .= '</div></div>'; 
          }
          
        } else {
          // топика нет
          if ($mode == 'reply'){
            $ok = FALSE;        
            // попытка добавить коммент значит нужно создать топик
            if($id >200000 ){ // новые статьи 2019
              $node = \Drupal\node\Entity\Node::load($id);
              if($node){
                $title = $node->getTitle();
                $dd = $node->field_date->date->getTimestamp();
                $url = cn_getNodeAlias($node);
                if ($node->body->summary == '') {
                  
                  $anons = explode('.', strip_tags($node->body->value));
                  $anons = $anons[0].$anons[1].$anons[2];
                  
                  $lid = explode("</strong>",strip_tags($node->body->value,"<strong>"));
                  $lid = strip_tags($lid[0]);
                  
                  if(mb_strlen($lid) < mb_strlen($anons)) $anons = $lid;
                  
                } else {
                  $anons = strip_tags ($node->body->summary);
                }
                $ok = TRUE;
              }
            } else { // старые статьи  
              $arrContextOptions=array(
                "ssl"=>array(
                    "verify_peer"=>false,
                    "verify_peer_name"=>false,
                ),
              ); 
              $node = file_get_contents('https://archiv.comnews.ru/n.php?id='.$id, false, stream_context_create($arrContextOptions));
              $node = explode('|',$node);
              if(count($node) == 5){
                
                $title = $node[1];
                $dd = $node[2];
                $url = '/'.$node[3];
                $anons = $node[4];
                $ok = TRUE;
              }
            
            }
            if($ok){
              // формируем текст первого сообщения
              $body = '<img src="//www.comnews.ru/img/1/ComNews-2011_Logo_R.png"><br>'
              .'<h3>'.$title.'</h3>'
              .'<p>'.$anons.'</p>'
              .'<br /> полный текст статьи: <a href="//www.comnews.ru'.$url.'">https://www.comnews.ru'.$url.'</a>';

              
              $output['#children'] .= $body;
              
              db_set_active('forum');
              // дата новости
              //$dd = $node->field_date['ru'][0]['value'];
              // создаем топик
              $topic_id = db_insert('topics')
                  ->fields(array('FORUM_ID' => $forum_id, 
                        'TOPIC_APPROVED' => 1, 
                        'TOPIC_FIRST_POSTER_COLOUR' => 'AA0000', 
                        'TOPIC_FIRST_POSTER_NAME' => 'comnews', 
                        'TOPIC_FIRST_POST_ID' => 0, 
                        'TOPIC_LAST_POSTER_COLOUR' => 'AA0000',  
                        'TOPIC_LAST_POSTER_ID' => 2, 
                        'TOPIC_LAST_POSTER_NAME' => 'comnews',
                        'TOPIC_LAST_POST_ID' => 0, 
                        'TOPIC_LAST_POST_SUBJECT' => '', 
                        'TOPIC_LAST_POST_TIME' => $dd, 
                        'TOPIC_POSTER' => 2, 
                        'TOPIC_STATUS' => 0, 
                        'TOPIC_TIME' => $dd, 
                        'TOPIC_TITLE' => $title, 
                        'TOPIC_TYPE' => 0, 
                        'material_id' => $mid
                ))->execute();
              
              $tmp = db_query("SELECT forum_topics FROM {forums} WHERE forum_id = :id", array(':id' => $forum_id))->fetchField();
              $tmp1 = db_query("SELECT forum_topics_real FROM {forums} WHERE forum_id = :id", array(':id' => $forum_id))->fetchField();
              db_update('forums')->fields(array('forum_topics' => $tmp+1, 'forum_topics_real' => $tmp1+1))->condition('forum_id', $forum_id)->execute();	
                
              // создаем первое сообщение
              $post_id = db_insert('posts')
                  ->fields(array(
                        'BBCODE_BITFIELD' => '', 
                        'BBCODE_UID' => '', 
                        'ENABLE_BBCODE' => 1, 
                        'ENABLE_MAGIC_URL' => 1, 
                        'ENABLE_SIG' => 1, 
                        'ENABLE_SMILIES' => 1,
                        'FORUM_ID' => $forum_id,
                        'ICON_ID' => 0, 
                        'MATERIAL_ID' => $mid, 
                        'POSTER_ID' => 2, 
                        'POSTER_IP' => '127.0.0.1', 
                        'POST_APPROVED' => 1, 
                        'POST_ATTACHMENT' => 0,
                        'POST_CHECKSUM' => '', 
                        'POST_EDIT_COUNT' => 0, 
                        'POST_EDIT_LOCKED' => 0, 
                        'POST_EDIT_REASON' => 0, 
                        'POST_EDIT_TIME' => $dd, 
                        'POST_EDIT_USER' => 0,
                        'POST_POSTCOUNT' => 1, 
                        'POST_REPORTED' => 0, 
                        'POST_SUBJECT' => '   ', 
                        'POST_TEXT' => $body, 
                        'POST_TIME' => $dd, 
                        'POST_USERNAME' => '',
                        'TOPIC_ID' => $topic_id
                ))->execute();
                
              db_update('topics')->fields(array(
                      'TOPIC_LAST_POST_SUBJECT' => $title, 
                      'TOPIC_FIRST_POST_ID' => $post_id, 
                      'TOPIC_LAST_POST_ID' => $post_id
                  ))->condition('topic_id', $topic_id)->execute();		
              db_update('posts')->fields(array(
                      'HIERARCHY' => cn_lidZero($post_id,9)
                  ))->condition('post_id', $post_id)->execute();		
              
              db_set_active();
              // переадресация на страницу добавления коммента
              header('Location: https://comments.comnews.ru/cs/posting.php?mode=reply&f='.$forum_id.'&t='.$topic_id);
            }  
          } else {
            // топика еще нет рисуем ссылку на создание топика и первого коммента
            $output['#children'] .= '<div class="comments clearfix">';
          
            if ($type != '_oldarticle' && $comments ==2) $output['#children'] .= '<div id="wrap"><div class="buttons"><div style="float:left;"><h3>Обсуждение</h3><div id="vote-container"></div></div><div class="reply-icon" style="float:left"><a title="Добавить комментарий" href="/comments/'.$id.'/reply?type='.$type.'&comments='.$comments.'&mid='.$mid.'&topic='.implode('-',$topic).'">Добавить комментарий</a></div></div></div>'; 
          
            $output['#children'] .= '</div>';
          }
        }
      }
    }
    return $output;
  }  
  
  /**
   * главная страница стандарта
   */
  public function renderStandartHP(){
    $output = array();
    $title = 'Журнал "Стандарт"';
    $body = '';


    $output['#title'] = $title;
    cn_setHTMLTitle($output['#title']);

    $query = \Drupal::entityQuery('node')
      ->condition('type', 'standart')
      ->condition('status', 1)->sort('field_seq','DESC')->range(0,1);  
    $nids = $query->execute();
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nodes = $node_storage->loadMultiple($nids);
     
    foreach($nodes as $node){
      $pdf = '';
      if($node->id() && $node->isPublished() && count($node->get('field_issue_pdf'))){
          foreach($node->get('field_issue_pdf') as $f){
              $pdf = 'https://www.comnews.ru'.$f->entity->createFileUrl();
              
          }
      } else 
      
          if($node->id() && $node->isPublished() && $node->field_free_pdf->value != ''){
              
              $pdf = 'https://www.comnews.ru'.$node->field_free_pdf->value;
              
          }

      if($pdf != ''){
          $pdf = '<a style="display: block; width: 250px; text-align:center; height: 30px; line-height: 30px;   border-radius: 5px; background-color: #be0027; color: #fff; font-size: 20px; margin: 15px auto; text-decoration:none!important;" href="/getissue.php?m=preview-pdf&id='.$node->id().'" target="_blank">Скачать PDF</a>';
      }
      $body .= '
        <div class="section1">
          <div class="row1">
            <div class="new-issue">
              <div class="cover"><a href="/standart/issue/'.(intval($node->field_old_id->value) > 0 ? $node->field_old_id->value : $node->id()).'"><img src="'.trim($node->field_cover_old->value == ''?cn_getImgUrl('standart_big',$node->field_cover):$node->field_cover_old->value).'"/></a></div>
              <div class="description">
                <h2 class="title">'.$node->title->value.'</h2>
                <div class="subtitle">'.$node->field_subtitle->value.'</div>
                <div class="text">'.$node->field_anons->value.'</div>
                <div style="text-align:center; margin:15px 0px;"><a href="/standart/issue/'.(intval($node->field_old_id->value) > 0 ? $node->field_old_id->value : $node->id()).'"><strong>Содержание номера</strong></a></div>
                '.$pdf.'
              </div>
            </div>
          </div>
          <div class="row2">
            '.cn_renderStandartLinksBlock().'
            <div class="h">О журнале</div>
            <div class="l"><a href="/standart/2020">"Стандарт" 2020 год</a></div>
            <div class="l"><a href="/standart/posters">Архив аналитических карт</a></div>
            <div class="l"><a href="https://www.comnews.ru/sites/default/files/standart2020-03.pdf">Прайс</a></div>
            <div class="l"><a href="/standart/adv/requirements">Технические требования</a></div>
            <div class="l"><a href="/standart/subscription">Подписка на журнал</a></div>
          </div>
        </div>
      
      ';
    }

    $issues = views_embed_view('standart', 'block_1');
    $body .= \Drupal::service('renderer')->render($issues);

    $body .= '
      <div class="section2">
        <div class="about"><p><b>"Стандарт"</b> – деловой журнал, посвященный развитию и практическому применению информационных и коммуникационных технологий в различных отраслях экономики в России и мире. Основные темы издания: цифровая трансформация, Интернет вещей, Индустрия 4.0, ИКТ на вертикальных рынках, телекоммуникации и вещание. Журнал выходит с 2000 года и зарекомендовал себя поставщиком объективной эксклюзивной информации в новостном и аналитическом форматах.</p>
        <p> </p>
        <p>В каждом выпуске "Стандарт" публикует новости о событиях, значимых для развития ИКТ в России и мире, аналитические обзоры различных рыночных сегментов и трендов, интервью с первыми лицами отечественных и международных компаний из ИТ-сектора и различных вертикальных рынков, анонсы крупнейших ИКТ-мероприятий в РФ и за рубежом.</p>
        </div>
        '.cn_renderStandartContacts().'
      </div>
    ';  

    $output['#children'] = $body;
    return $output;
  }
  /**
   * страница старого выпуска стандарта
   */
  public function renderStandartOldIssue($oldid = null){
    $output = array();
    $title = '';
    $body = '';
    if(intval($oldid)){
      if(intval($oldid) < 200000){
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'standart')
        ->condition('field_old_id', intval($oldid));  
      $nids = $query->execute();
       
      $node_storage = \Drupal::entityTypeManager()->getStorage('node');
      $nodes = $node_storage->loadMultiple($nids);
      } else {
        $nodes = array(\Drupal\node\Entity\Node::load(intval($oldid)));
      }
      if(count($nodes)){
        $node = current($nodes);
        $title = '<div class="s1"><div>Журнал Стандарт <span class="black"> / '.$node->title->value.'</span></div></div>';
        $maps = '';
        $m = 0;
        if(count($node->get('field_map_imgs'))){
          if(count($node->get('field_map_imgs')) == 1){
            $maps .= '<p class="text-align-center"><strong>Аналитическая карта</strong></p>';
          } else {
            $maps .= '<p class="text-align-center"><strong>Аналитические карты</strong></p>';
          }
          $pdfs = $node->get('field_map_pdfs');
          foreach($node->get('field_map_imgs') as $img){
              
              $maps .='<div class="map">
                <h3>'.$img->alt.'</h3>
                <a class="img" href="'.cn_getImgStyleUrl('original',$img->entity->getFileUri()).'" rel="lightbox[img]['.htmlspecialchars($img->alt).']"><img src="'.cn_getImgStyleUrl('large',$img->entity->getFileUri()).'"/></a><p>'.$img->title.'<div style="text-align:right;"><a style="display: inline-block; width: 200px; text-align:center; height: 30px; line-height: 30px;   border-radius: 5px; background-color: #be0027; color: #fff; font-size: 20px; margin: 5px 5px; text-decoration:none!important;" href="'.$pdfs[$m]->entity->createFileUrl().'" target="_blank">Скачать PDF</a></div></p>
              </div>';
            
            $m++;
          }
        }
        $pdf = '';
        if($node->id() && $node->isPublished() && count($node->get('field_issue_pdf'))){
            foreach($node->get('field_issue_pdf') as $f){
                $pdf = 'https://www.comnews.ru'.$f->entity->createFileUrl();
                
            }
        } else 
        
            if($node->id() && $node->isPublished() && $node->field_free_pdf->value != ''){
                
                $pdf = 'https://www.comnews.ru'.$node->field_free_pdf->value;
                
            }

        if($pdf != ''){
            $pdf = '<a style="display: block; width: 250px; text-align:center; height: 30px; line-height: 30px;   border-radius: 5px; background-color: #be0027; color: #fff; font-size: 20px; margin: 15px auto; text-decoration:none!important;" href="/getissue.php?m=preview-pdf&id='.$node->id().'" target="_blank">Скачать PDF</a>';
        
            $hbody .= '
              <div class="new-issue" style="margin:0px;">
                    <div class="cover"><a href="/standart/issue/'.(intval($node->field_old_id->value) > 0 ? $node->field_old_id->value : $node->id()).'"><img src="'.trim($node->field_cover_old->value == ''?cn_getImgUrl('standart_big',$node->field_cover):$node->field_cover_old->value).'"/></a></div>
                    <div class="description">
                      <h2 class="title">'.$node->title->value.'</h2>
                      <div class="subtitle">'.$node->field_subtitle->value.'</div>
                      <div class="text">'.$node->field_anons->value.'</div>
                      
                      '.$pdf.'
                    </div>
              </div>';
              $cover = '';
        } else {
          $hbody = '';      
          $cover = '<div class="cover"><img src="'.trim($node->field_cover_old->value == ''?cn_getImgUrl('standart_big',$node->field_cover):$node->field_cover_old->value).'"/></div>';
        }  
        $body = ' <div id="page-standart" class="download-free-pdf">
                    <div class="node-standart"><div style="padding: 0px 0px 30px; font-size: 28px; line-height: 28px; color: #000;          font-weight: 500; text-align: center;">Содержание</div>'.$hbody.str_replace(array(
                            'style="text-align: center;"',
                            'style="text-align:center;"'
                          ),array(
                            ' class="t-a-center"',
                            ' class="t-a-center"',
                          ),$node->body->value).'
                      
                          '.$pdf.'
                    </div>            
                    <div class="sidebar">
                        <div class="node-maps standart-links" style="text-align:left;">'.$maps.'</div>
                      '.$cover.'
                      '.cn_renderStandartLinksBlock().'
                      <div class="page-standart">
                        <div class="h">О журнале</div>
                        <div class="l"><a href="/standart/2020">"Стандарт" 2020 год</a></div>
                        <div class="l"><a href="/standart/posters">Архив аналитических карт</a></div>
                        <div class="l"><a href="https://www.comnews.ru/sites/default/files/standart2020-03.pdf">Прайс</a></div>
                        <div class="l"><a href="/standart/adv/requirements">Технические требования</a></div>
                        <div class="l"><a href="/standart/subscription">Подписка на журнал</a></div>
                        </div>
                      '.cn_renderBannerSite('bnStandart1').'
                      <div class="gray-bg">'.cn_renderStandartContacts().'</div>
                    </div>
                  </div>';  // 
        
        $issues = views_embed_view('standart', 'block_1');
        $body .= \Drupal::service('renderer')->render($issues); 
      } else {
        throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();  
      }
    } else {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }

    $output['#title'] = $title;
    cn_setHTMLTitle($output['#title']);
    $output['#children'] = $body;
    return $output;
  }
  /**
   * страница статьи стандарта
   */
  public function renderStandartOldArticle($id = null){
    $output = array();
    $title = '';
    $body = '';
    if(intval($id)){
      try {
        // тянем статью из архива
        $url = 'https://archiv.comnews.ru/node/'.intval($id); 
        //$client = \Drupal::httpClient(); 
        //$request = $client->get($url); 
        // 
        //$markup = (string)$request->getBody();
        $arrContextOptions=array("ssl"=>array("verify_peer"=>false,"verify_peer_name"=>false,),); 
				$markup = file_get_contents($url, false, stream_context_create($arrContextOptions));
				
        require_once('phpQuery.php');
        $doc = \phpQuery::newDocumentHTML($markup);
        // чистим текст от лишнего
        $title = $doc->find('span.rdf-meta.element-hidden[property="dc:title"]')[0]->attr('content');
        $issue = intval($doc->find('.node-txt')->attr('data-issue'));
        if($issue){
          $query = \Drupal::entityQuery('node')
            ->condition('type', 'standart')
            ->condition('field_old_id', $issue);  
          $nids = $query->execute();
          $node_storage = \Drupal::entityTypeManager()->getStorage('node');
          $nodes = $node_storage->loadMultiple($nids);
          if(count($nodes)){
            $node = current($nodes);
            $title = '<div class="s1"><div>Журнал Стандарт <span class="black"> / '.$node->title->value.'</span></div></div><div class="s2">'.$title.'</div>';
            
          
            pq('script, .field-name-field-folder, #sharebuttons, #shareinit, #cross, #phpbb, .ya-cn')->remove();
            $body = $doc->find('#node-'.intval($id).' > .content')->html();

            $body = ' <div id="page-standart">
                    <div class="node-standart">'.str_replace(array(
                            'qq',
                            
                          ),array(
                            'qq',
                            
                          ),$body).'</div>
                    <div class="sidebar">
                      <div class="cover"><img src="'.trim($node->field_cover_old->value == ''?cn_getImgUrl('standart_big',$node->field_cover):$node->field_cover_old->value).'"/></div>
                      '.cn_renderStandartLinksBlock().'
                      '.cn_renderBannerSite('bnStandart1').'
                      <div class="gray-bg">'.cn_renderStandartContacts().'</div>
                    </div>
                  </div>';  
          }
        }                    
        $issues = views_embed_view('standart', 'block_1');
        $body .= \Drupal::service('renderer')->render($issues);
        
      }
      catch (RequestException $exception) {
        throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
      }    
    } else {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }
    $output['#title'] = $title;
    cn_setHTMLTitle($output['#title']);
    $output['#children'] = $body;
    return $output;
  }
  /**
   * страница выпуска стандарта
   */
  public function renderStandartIssue(){
    $output = array();
    $title = 'страница выпуска стандарта';
    $body = '';


    $output['#title'] = $title;
    cn_setHTMLTitle($output['#title']);
    $output['#children'] = $body;
    return $output;
  }
  /**
   * страница архива стандарта
   */
  public function renderStandartArhiv(){
    $output = array();
    $title = 'Журнал Стандарт';
    $body = '';
    
    $issues = views_embed_view('standart', 'block_2');
    $body .= \Drupal::service('renderer')->render($issues);
    

    $output['#title'] = $title;
    cn_setHTMLTitle($output['#title']);
    $output['#children'] = $body;
    return $output;
  }

  /**
   * страница авторов
   */
  public function renderAuthors(){
    $output = array();
    $title = 'Авторы';
    $body = '';
    
    //$issues = views_embed_view('standart', 'block_2');
    //$body .= \Drupal::service('renderer')->render($issues);
    

    $output['#title'] = $title;
    cn_setHTMLTitle($output['#title']);

    $body .= $this->_renderAuthorsList();


    $output['#children'] = $body;
    return $output;
  }

  /**
   * render authors list
   */
  private function _renderAuthorsList($excluded = 0){
    $body = '';
    
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('authors', $parent = 0, $max_depth = 1, $load_entities = TRUE);

    $body .= '<div id="list" class="authors">';
    foreach($terms as $term){
      if($term->id() != $excluded && $term->field_show->value){
        $img = '';
        if(isset($term->field_bigimg->entity)) $img = '<img src="'.\Drupal\image\Entity\ImageStyle::load('large')->buildUrl($term->field_bigimg->entity->getFileUri()).'"/>';
        
        if($term->field_show_editorials->value) $url = 'editorials';
        if($term->field_show_articles->value) $url = 'articles';
        if($term->field_show_bio->value) $url = 'bio';
        
        $body .= '<div class="author"><a href="/authors/'.$url.'/'.$term->id().'">'.$img.'<h3>'.$term->name->value.'</h3><span>'.$term->field_jobtitle->value.'</span></a><a class="email" href="mailto:'.$term->field_email->value.'">'.$term->field_email->value.'</a></div>';
      }
    }
    $body .= '</div>';
    return $body;
  }

  /**
   * render author header
   */
  private function _renderAuthorHeader($term,$page){
    $body = '';

    if(!$term->field_show->value) throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();

    $body .= '<div id="author-header">';
    $img = '';
    if(isset($term->field_bigimg->entity)) $img = '<img src="'.\Drupal\image\Entity\ImageStyle::load('large')->buildUrl($term->field_bigimg->entity->getFileUri()).'"/>';

    $body .= $img.'<h3>'.$term->name->value.'</h3><span>'.$term->field_jobtitle->value.'</span><a href="mailto:'.$term->field_email->value.'">'.$term->field_email->value.'</a><div class="quote">'.$term->field_quot_txt->value.'</div>';

    $body .= '</div><div id="author-links">';
    if($term->field_show_articles->value) $body .= '<a class="'.trim($page=='articles'?'active':'').'" href="/authors/articles/'.$term->id().'">Статьи<span></span></a>';
    if($term->field_show_editorials->value) $body .= '<a class="'.trim($page=='editorials'?'active':'').'" href="/authors/editorials/'.$term->id().'">Редколонки<span></span></a>';
    if($term->field_show_bio->value) $body .= '<a class="'.trim($page=='bio'?'active':'').'" href="/authors/bio/'.$term->id().'">Биография<span></span></a>';
    $body .= '</div>';
    
    return $body;
  }


  /**
   * страница авторов bio
   */
  public function renderAuthorBio($tid){
    $output = array();
    $title = 'Авторы';
    
    $term = \Drupal\taxonomy\Entity\Term::load(intval($tid));
    if(!$term){
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }

    $output['#title'] = $title;
    cn_setHTMLTitle($output['#title'].' | '.$term->name->value);

    $body = $this->_renderAuthorHeader($term,'bio');
    $body .= '<div class="author-bio">'.$term->description->value.'</div>';
    //$issues = views_embed_view('standart', 'block_2');
    //$body .= \Drupal::service('renderer')->render($issues);
    $body .= $this->_renderAuthorsList($tid);

    $output['#children'] = $body;
    return $output;
  }
  
    /**
   * страница авторов articles
   */
  public function renderAuthorArticles($tid){
    $output = array();
    $title = 'Авторы';
    $body = '';
    
    $term = \Drupal\taxonomy\Entity\Term::load(intval($tid));
    if(!$term){
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }

    $output['#title'] = $title;
    cn_setHTMLTitle($output['#title'].' | '.$term->name->value);

    $body = $this->_renderAuthorHeader($term,'articles');
    $nodes = views_embed_view('authors_articles', 'page_1', $tid);
    $body .= \Drupal::service('renderer')->render($nodes);
    if(intval($term->field_old_tid->value)) $body .= '<div class="arhiv-link"><a href="/authors/articles/arhiv/'.$tid.'">Архив статей</a></div>';
    $body .= $this->_renderAuthorsList($tid);

    $output['#children'] = $body;
    return $output;
  }

  /**
   * страница авторов editorials
   */
  public function renderAuthorEditorials($tid){
    $output = array();
    $title = 'Авторы';
    $body = '';
    
    $term = \Drupal\taxonomy\Entity\Term::load(intval($tid));
    if(!$term){
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }

    $output['#title'] = $title;
    cn_setHTMLTitle($output['#title'].' | '.$term->name->value);

    $body = $this->_renderAuthorHeader($term,'editorials');
    $nodes = views_embed_view('authors_articles', 'page_2', $tid);
    $body .= \Drupal::service('renderer')->render($nodes);
    if(intval($term->field_old_tid->value)) $body .= '<div class="arhiv-link"><a href="/authors/editorials/arhiv/'.$tid.'">Архив колонок</a></div>';
    $body .= $this->_renderAuthorsList($tid);

    $output['#children'] = $body;
    return $output;
  }


  /**
   * страница авторов arhiv articles
   */
  public function renderAuthorArhivArticles($tid){
    $output = array();
    $title = 'Авторы';
    $body = '';
    
    $term = \Drupal\taxonomy\Entity\Term::load(intval($tid));
    if(!$term){
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }

    $output['#title'] = $title;
    cn_setHTMLTitle($output['#title'].' | '.$term->name->value);
    
    $body = $this->_renderAuthorHeader($term,'articles');
    $body .= '<div id="author-arhiv"><h3>Архив статей</h3>'.$this->_renderAuthorsArhiv('articles',$tid,$term->field_old_tid->value).'</div>';
    $body .= $this->_renderAuthorsList($tid);

    $output['#children'] = $body;
    return $output;
  }

  /**
   * страница авторов arhiv editorials
   */
  public function renderAuthorArhivEditorials($tid){
    $output = array();
    $title = 'Авторы';
    $body = '';
    
    $term = \Drupal\taxonomy\Entity\Term::load(intval($tid));
    if(!$term){
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }

    $output['#title'] = $title;
    cn_setHTMLTitle($output['#title'].' | '.$term->name->value);

    $body = $this->_renderAuthorHeader($term,'editorials');
    $body .= '<div id="author-arhiv" class=""><h3>Архив колонок</h3>'.$this->_renderAuthorsArhiv('editorials',$tid,$term->field_old_tid->value).'</div>';
    $body .= $this->_renderAuthorsList($tid);

    $output['#children'] = $body;
    return $output;
  }

  /**
   * отрисовываем архив авторов
   */
  private function _renderAuthorsArhiv($mode = null,$tid = null,$old_tid) {
    
    $body = '';
    $page = \Drupal::request()->query->get('page');
    $url = 'authors/'.$mode.'/'.$old_tid;
    $_url = '/authors/'.$mode.'/arhiv/'.$tid; // адрес для пагинатора
     
    try{
      //$client = \Drupal::httpClient(); 
      //$request = $client->get('https://archiv.comnews.ru/'.$url.'?page='.$page); 
      $turl = 'https://archiv.comnews.ru/'.$url.'?page='.$page;
      // 
      //$markup = (string)$request->getBody();
      $arrContextOptions=array("ssl"=>array("verify_peer"=>false,"verify_peer_name"=>false,),); 
			$markup = file_get_contents($turl, false, stream_context_create($arrContextOptions));
      require_once('phpQuery.php');
      $doc = \phpQuery::newDocumentHTML($markup);
      // чистим 
      pq('script, .ya-cn')->remove();
      // адрес в пагинаторе
      $surl = explode('?',$doc->find('#block-system-main .nav-line a')[0]->attr('href'));
      $surl = $surl[0];
      // пагинатор
      $navline = $doc->find('#block-system-main .nav-line')->html();  
      $navline = cn_transformOldNavLine($navline,$surl,$_url);
      
      //тело
      $body = $doc->find('#block-system-main .view-content')->html();
      
      
      return '<div class="view view-arhiv"><div class="view-header"></div><div class="view-content">'.$body.$navline.'</div></div>';
    } catch (RequestException $exception) {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }  
    return '';
  } 

  /**
   * отрисовываем awards 2018
   */
  public function renderAwards2018($folder = null,$industry = null) {
    
    $output = array();
    $title = 'ComNews Awards 2018';
    
    $output['#title'] = $title;
    cn_setHTMLTitle($output['#title']);
    
    $body = '';

    $url = 'digital-economy/awards/2018';
    if($folder) {
      $url .= '/'.$folder;
      if($industry) $url .= '/'.$industry;
    }  
     
    try{
      //$client = \Drupal::httpClient(); 
      //$request = $client->get('https://archiv.comnews.ru/'.$url); 
      $turl = 'https://archiv.comnews.ru/'.$url;
      // 
      //$markup = (string)$request->getBody();
      $arrContextOptions=array("ssl"=>array("verify_peer"=>false,"verify_peer_name"=>false,),); 
			$markup = file_get_contents($turl, false, stream_context_create($arrContextOptions));
      require_once('phpQuery.php');
      $doc = \phpQuery::newDocumentHTML($markup);
      // чистим 
      pq('script, .ya-cn')->remove();
      
      //тело
      $body = $doc->find('#block-system-main')->html();
            
      $output['#children'] = '<div class="digital-economy-awards-2018"><a class="awards-link-2018" href="/digital-economy/awards/2018/"></a>'.$body.'</div>';
      
    } catch (RequestException $exception) {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }  
    
    return $output;
  } 
  /**
   * отрисовываем Tricolor2019
   */
  public function renderTricolor2019($folder = '',$page = '') {
    
    $output = array();
    
    $request = \Drupal::request();
    $url = $request->getRequestUri();
    /*
    $url = explode('?',$request->getRequestUri());
    $url = $url[0];
    $url = explode('/',$url);

    $url = $url[1].'/';
    if($folder) {
      $url .= '/'.$folder;
      if($page) $url .= '/'.$page;
    }  
    */

    try{

      //$client = \Drupal::httpClient(); 
      //$request = $client->get('https://archiv.comnews.ru/'.$url); 
      $turl = 'https://archiv.comnews.ru/'.$url;
      // 
      $arrContextOptions=array("ssl"=>array("verify_peer"=>false,"verify_peer_name"=>false,),); 
			$markup = file_get_contents($turl, false, stream_context_create($arrContextOptions));
      $markup = str_replace(array('"/themes/'),array('"https://archiv.comnews.ru/themes/'), $markup /*(string)$request->getBody()*/);

      print $markup;
      die;

      /*
      require_once('phpQuery.php');
      $doc = \phpQuery::newDocumentHTML($markup);
      // чистим 
      pq('script, .ya-cn')->remove();

      $title = $doc->find('head title')->text();
    
      $output['#title'] = $title;
      cn_setHTMLTitle($output['#title']);
      
      //тело
      //$body = $doc->find('#block-system-main')->html();
            
      $output['#children'] = $body;
      */
      
      
    } catch (RequestException $exception) {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }  
    
    return $output;
  } 
  /**
   * отрисовываем Megafon cloud 2020
   */
  public function rendermegafonCloud2020() {
    
    $output = array();
    
    $request = \Drupal::request();
    $url = $request->getRequestUri();
    /*
    $url = explode('?',$request->getRequestUri());
    $url = $url[0];
    $url = explode('/',$url);

    $url = $url[1].'/';
    if($folder) {
      $url .= '/'.$folder;
      if($page) $url .= '/'.$page;
    }  
    */

    try{

      /*
      $turl = 'https://archiv.comnews.ru/'.$url;
      $arrContextOptions=array("ssl"=>array("verify_peer"=>false,"verify_peer_name"=>false,),); 
			$markup = file_get_contents($turl, false, stream_context_create($arrContextOptions));
      $markup = str_replace(array('"/themes/'),array('"https://archiv.comnews.ru/themes/'), $markup);

      print $markup;
      die;
      */
      // <p class="video"><a class="desktop" href="https://www.youtube.com/watch?v=qIZ0tM3U8W0">%simple-img-1%<span class="text">Используйте «МегаФон Облако» на выгодных условиях, переносите бизнес-приложения в облако и надежно храните данные</span></a><a class="mobile" href="https://www.youtube.com/watch?v=qIZ0tM3U8W0">%simple-img-14%</a></p>

      cn_addToExcludedBanners('bn0002');
      cn_addToExcludedBanners('bn0009');
      cn_addToExcludedBanners('bn0013');
      cn_addToExcludedBanners('bn0015');
      cn_addToExcludedBanners('bnM0002');
      cn_addToExcludedBanners('bnPopUp');
      


      $title = 'Облака будущего';
      $descr = 'Запустив собственную облачную платформу, МегаФон создал фундамент для масштабирования высокотехнологичных цифровых услуг на базе облачных решений. Ведь в условиях быстрых изменений на рынке запускать новые проекты стратегически важно компаниям разных размеров — от среднего до крупного бизнеса, государственным структурам, в том числе федерального масштаба.';
      cn_setMetaTag('name="description"',$descr);
      cn_setMetaTag('property="og:description"',$descr);
      cn_setMetaTag('property="og:title"',$title);
      cn_setMetaTag('property="og:image"',"https://www.comnews.ru/themes/site/images/pr-megafon-cloud-fb.jpg");

     

      $shareBtns = '<div id="sh-up">
                        <div class="ya-share2" 
                          data-services="vkontakte,facebook,twitter,telegram"
                          data-title="'.cn_t($title).'"
                          data-description="'.cn_t($descr).'"
                          data-image="https://www.comnews.ru/themes/site/images/pr-megafon-cloud-fb.jpg" 
                          data-url="https://www.comnews.ru/projects/megafon-cloud"></div></div>
                          <script src="https://yastatic.net/es5-shims/0.0.2/es5-shims.min.js"></script><script src="https://yastatic.net/share2/share.js"></script>
                          ';
      
      $header = '<div id="m-header">
      <a href="#" id="top"></a>
      <h1>'.$title.'</h1>
      </div>'.$shareBtns.'
      ';

      $footer = '<br>'.$shareBtns.''.cn_renderCounters();
      cn_setCustomHeader($header);
      cn_setCustomFooter($footer);
      cn_setHTMLTitle($title);
      $node = \Drupal\node\Entity\Node::load(208145);
       
      $output = array('#children' => cn_renderNodeImages($node->body->value,$node));

    } catch (RequestException $exception) {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }  
    
    return $output;
  } 
  /**
   * отрисовываем starlink
   */
  public function renderStarlink() {
    
    $output = array();
    
    $request = \Drupal::request();
    $url = $request->getRequestUri();
    
    try{

    
      $title = 'Энциклопедия Starlink';
      $descr = '';
      cn_setMetaTag('name="description"',$descr);
      cn_setMetaTag('property="og:description"',$descr);
      cn_setMetaTag('property="og:title"',$title);
      cn_setMetaTag('property="og:image"',"https://www.comnews.ru/sites/default/files2019/styles/img1200/public/pages/2020-10/01.jpg?itok=Gi8riJni");

     

      $shareBtns = '<div id="sh-up" class="sharebuttons">
                        <div class="ya-share2" 
                          data-services="vkontakte,facebook,twitter,telegram"
                          data-title="'.cn_t($title).'"
                          data-description="'.cn_t($descr).'"
                          data-image="https://www.comnews.ru/sites/default/files2019/styles/img1200/public/pages/2020-10/01.jpg?itok=Gi8riJni" 
                          data-url="https://www.comnews.ru/projects/encyclopedia-starlink"></div></div>
                          
                    ';
      
      
      
      $more = '<div></div>';
      $more = cn_renderHPblock('toc','Содержание','',$more,'gray-bg');  
      cn_setCustomSideBar($more);

      $footer = '<br>'.$shareBtns.''.cn_renderCounters();
      cn_setCustomHeader(cn_renderComnewsHeader());
      cn_setCustomFooter(cn_renderComnewsFooter());
      cn_setHTMLTitle($title);
      $node = \Drupal\node\Entity\Node::load(209406);
      
      $css = '
      <style>
      h1.page-header { padding-bottom: 20px!important;}
      .node-wrapper.node .node-txt .field-name-body .content-img.left { width: 250px;}
      .node-txt>.sharebuttons { margin-bottom:-30px;}
      .node-wrapper .sharebuttons { display:block!important;}
      .node-txt #b1 { font-size:15px; font-weight:500;}
    .node-txt #b1 .content-img { /*height:550px;*/}
      .node-txt #b1 .content-img span:last-child { display:none!important;}
      .hfixed { position:fixed; top: 100px; width:380px;}
      @media screen and (max-width: 750px){
        .node-txt #b1 { font-size:20px;}
        .node-txt #b1 .content-img { height:auto;}  
        
      }
      </style>
      <script>
      (function ($) {$(function(){
        let toc = "";
        let i = 1;
          $(".node-txt h3").each(function(){
            $(this).attr("id","toc"+i); 
            toc = toc + \'<li><a id="atoc\'+i+\'" href="#toc\'+ i +\'">\' + $(this).text() + \'</a></li>\';
            i++;
          });
        $("#toc .hp-block-body div").append("<ul>"+toc+"</ul>");
//----------------------------
        function scrollTracking(){
          $(".node-txt h3").each(function(){
            let wt = $(window).scrollTop();
            let wh = $(window).height();
            let et = $(this).offset().top;
            var eh = $(this).outerHeight();
          
            if (wt + wh >= et && wt + wh - eh * 2 <= et + (wh - eh)){
              $("#toc a").css("font-weight","300");
              $("#toc #a"+$(this).attr("id")).css("font-weight","600");
            } else {
              
            }
          });
        }
        
        $(window).scroll(function(){
          scrollTracking();
        });
          
        $(document).ready(function(){ 
          scrollTracking();
        });
//----------------------
        });}(jQuery));
        var cbHeader = (function() {
          var docElem = document.documentElement, header = document.querySelector( \'#toc\' ), didScroll = false, changeHeaderOn = 170;
        
          function init() {
            window.addEventListener( \'scroll\', function( event ) { if( !didScroll ) { didScroll = true; setTimeout( scrollPage, 50 ); }}, false );
          }
          function scrollPage() {
            var sy = scrollY();
            if ( sy >= changeHeaderOn && sy < jQuery(\'body\').height()-(2* window.screen.height) ) jQuery(\'#toc\').addClass(\'hfixed\' );
            else jQuery(\'#toc\').removeClass(\'hfixed\');
            didScroll = false;
          }
          function scrollY() {return window.pageYOffset || docElem.scrollTop;}
          init();
        
        })();
        
      </script>
      <script src="https://yastatic.net/es5-shims/0.0.2/es5-shims.min.js"></script><script src="https://yastatic.net/share2/share.js"></script>
      ';
      cn_addToHiddenBanners($css); 
      
      $output = array('#children' => '<div class="node node-wrapper"><div class="node-txt">'.str_replace('sh-up','sharebuttons-up',$shareBtns).'<div class="field field-text field-multiple source field-name-source"><div class="field-items"><div class="field-item"><span>© ComNews</span></div></div></div><div class="field field-text full-html field-name-body">'.cn_renderNodeImages(check_markup($node->body->value,'full_html'),$node).'</div>'.str_replace('sh-up','sharebuttons-dn',$shareBtns).'<p style="height:40px;"> </p></div></div>');

    } catch (RequestException $exception) {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }  
    
    return $output;
  } 
  /**
   * отрисовываем DE HP mainpage
   */
  public function renderSearchPage() {
    
    $output = array();
    $pager = '';
    $output['#title'] = 'Поиск по материалам сайта';
    cn_setHTMLTitle('Поиск по материалам сайта');
    $options = intval(\Drupal::request()->query->get('options'));
    if(!in_array($options,array(1,2,3))) $options = 1;
    $text = trim(\Drupal::request()->query->get('text'));
    $out = '<div class="srch-form"><form action="" method="get"><input placeholder="Укажите критерий поиска ..." class="srch-text form-control form-input" type="text" name="text" value="'.cn_t($text).'"/><input id="form-btn" class="srch-btn" type="submit" value=" "/>
    <div class="srch-options"> Искать: 
    <label for="srch1"><input id="srch1" type="radio" name="options" value="1"'.($options == 1?' checked ':'').'/> Любое слово</label>
    <label for="srch2"><input id="srch2" type="radio" name="options" value="2"'.($options == 2?' checked ':'').'/> Все слова</label>
    <label for="srch3"><input id="srch3" type="radio" name="options" value="3"'.($options == 3?' checked ':'').'/> Всю фразу</label>
    </div>
    </form></div>';

    $srch_words = cn_getWords($text);

    $srch_words = array_slice($srch_words, 0, 4);
    $base_forms = $srch_words;
    if($options != 3) $base_forms = array_keys(cn_getSearchBaseForms(implode(' ',$srch_words),false));

    
    if(count($base_forms)){
        
        $query = \Drupal::database()->select('a_search', 'a');
        $query->fields('a', ['nid']);
    
        if($options == 1){
          $condition = new \Drupal\Core\Database\Query\Condition('OR');
          foreach($base_forms as $word){
              $condition->condition('a.morphy', '%|'.$word.'|%','LIKE');
          }
          $query->condition($condition);
        } else if($options == 2){
          foreach($base_forms as $word){
            $query->condition('a.morphy', '%|'.$word.'|%','LIKE');
          }
        } else {
          $query->condition('a.body', '%'.$text.'%','LIKE');
          
        }
        $row_count = $query->countQuery()->execute()->fetchField();
        $per_page = 20;
        $current_page = pager_default_initialize($row_count, $per_page);
        $pager = array();
        $pager[] = array('#type' => 'pager');
        $pager = str_replace(array('>next<','>last<','>first<','>previous<'),array('>››<','>»<','>«<','>‹‹<'),render($pager));
         
        $query = \Drupal::database()->select('a_search', 'a');
        $query->fields('a', ['nid','date','title','path','body','morphy']);
        
        if($options == 1){
          $condition = new \Drupal\Core\Database\Query\Condition('OR');
          foreach($base_forms as $word){
              $condition->condition('a.morphy', '%|'.$word.'|%','LIKE');
          }
          $query->condition($condition);
        } else if($options == 2){
          foreach($base_forms as $word){
            $query->condition('a.morphy', '%|'.$word.'|%','LIKE');
          }
        } else {
          $query->condition('a.body', '%'.$text.'%','LIKE');
        }

        $rows = $query->orderBy('a.date ','DESC')->range($current_page * $per_page,$per_page)->execute()->fetchAll();
         
        $out .= '<div class="srch-results">';
        if(count($rows)){
          foreach($rows as $row){
              $node = \Drupal\node\Entity\Node::load($row->nid);
              if($options != 3){
                $words = cn_getWords('      '.$row->body.'      ',false);
                $text_base_forms = cn_getBaseForms(cn_getWords('      '.$row->body.'      '));
                $founded = array();
                foreach($base_forms as $base_form){
                    foreach($text_base_forms as $n => $forms){
                        if(in_array($base_form, $forms)) $founded[] = $n;
                    }
                }
                $body = '';
                foreach($founded as $f){
                    $body .= ' ... '.$words[$f-4].' '.$words[$f-3].' '.$words[$f-2].' '.$words[$f-1].' <b>'.$words[$f].'</b> '.$words[$f+1].' '.$words[$f+2].' '.$words[$f+3].' '.$words[$f+4].' ... ';
                }
              } else {
                $body = str_replace($text,'<b>'.$text.'</b>',$row->body);
                $pos1 = mb_strpos($body, '<b>');
                $pos1 = $pos1 - 50 > 0?$pos1 - 50 : 0;
                while ($pos1 > 0 && mb_substr($body,$pos1,1) != ' ' ){
                  $pos1--;
                }
                $pos2 = mb_strpos($body, '</b>');
                $pos2 = $pos2 + 50 < mb_strlen($body)?$pos2 + 50 : mb_strlen($body)-1;
                while ($pos2 < mb_strlen($body) && mb_substr($body,$pos2,1) != ' ' ){
                  $pos2++;
                }
                $body = '... '.mb_substr($body,$pos1,$pos2-$pos1).' ...';
                
              }
              $out .= '<hr><a class="srch-row" href="/'.$row->path.'"><h3 class="srch-title"><div class="srch-date">'.date('d.m.Y',$row->date).'</div>'.$row->title.'</h3><div class="srch-body">'.$body.'</div></a>';
          }
        } else {
          $out .= '<div style="padding:100px;text-align:center;">По Вашему запросу ничего не найдено!</div>';
        }  
        $out .= '</div>';
         
    }
    

    $output['#children'] = $out.'<div style="height:60px;"></div>'.$pager;
    return $output; 
  }  
  /**
   * отрисовываем DE HP mainpage
   */
  public function renderDEMainPage() {
    
    $output = array();
    $title = '';
    
    $output['#title'] = $title;
    cn_setHTMLTitle('Цифровая экономика');
    
    $news_showed = array(0);

    $body = '';
    
    // news  ==================================================================================================
    $news = '';
    $lim = 8;
    $query = \Drupal::entityQuery('node')->condition('type', 'article')->condition('status', 1)
        ->condition('field_folders', 1012)
        ->condition('field_hp_date', cn_shortDBDate())
        ->sort('field_seq','ASC')
        ;
    $nids = $query->execute();

    if(count($nids) < $lim){
      $query = \Drupal::entityQuery('node')->condition('type', 'article')->condition('status', 1)
        ->condition('field_folders', 1012)
        ->sort('field_date','DESC')->sort('field_seq','ASC')->sort('created','DESC')->range(0,$lim);
        $nids = $query->execute();
    } 

    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nodes = $node_storage->loadMultiple($nids);
    
    foreach($nodes as $node){
      $img = cn_renderBlockImage($node->field_image,'de-hp',cn_getNodeAlias($node));
      $cl = '';
      if(empty($img)) $cl = ' no-img';
      $lid = cn_renderLid($node->body,300);
      $tags = cn_renderNodeDETags($node);
      $news_showed[] = $node->id();
      $news .= '<div class="block-node">'.$img.'<div class="de-lid'.$cl.'"><a href="'.cn_getNodeAlias($node).'"><div class="node-date">'.cn_convertDateFromStorageFormat($node->field_date->value,'d.m.Y').'</div><h4 class="node-title">'.$node->title->value.'</h4><div class="node-text">'.$lid.'</div></a>'.$tags.'</div><div class="clear"></div></div>';  
    }
    $news =cn_renderHPblock('news','Новости','/digital-economy/news',$news,'white-bg'); // news  
    $docs = views_embed_view('documents', 'block_1');
    $docs = \Drupal::service('renderer')->render($docs);
    $docs =cn_renderHPblock('docs','Документы','/digital-economy/documents',$docs,'gray-bg'); // news  
    
    $body = '<div id="de-hp-desktop">'.$news.$docs.'</div>';
    $output['#children'] = $body;
    return $output;
  }  
  /**
   * отрисовываем HP mainpage
   */
  public function renderAdmPage() {
    
    $output = array();
    $title = 'ADM';
    $body = '';
    $output['#title'] = $title;
    cn_setHTMLTitle($output['#title']);

    if(!cn_isAdmin()){
      
      
    } else {
      $body = '<h1>Logged</h1>';

      if(cn_isAdmin() && \Drupal::request()->query->get('flushcache') == '1'){
        $c = \Drupal\comnews\ComNewsBoost::boost_flush_caches();
        \Drupal::messenger()->addMessage('Страничный кеш сброшен ('.$c.' файлов).');
      }
      $body .='<a href="/adm?flushcache=1" class="btn btn-info">Сбросить страничный кеш</a>'; 

    }



    $output['#children'] = $body;
    return $output;
  }
  /**
   * отрисовываем redirect from 47 & 29
   */
  public function redirectFromSNLink($id) {
    
    $output = array();
    if(intval($id)){
      
      

        $request = \Drupal::request();
        $url = explode('?',$request->getRequestUri());
        $url = $url[0];
        //$path = explode('/', \Drupal::service('path.alias_manager')->getPathByAlias($url));
        $url = explode('/',$url);

        if(cn_getVal($url[1]) == '47'){
          $q = '?utm_source=facebook&utm_medium=general&utm_campaign=general';
        } else {
          $q = '?utm_source=telegram&utm_medium=general&utm_campaign=general';
        }
        $_url = '/node/'.intval($id).$q;


        \Drupal::service('comnews.boost')->clear();
        $response = new RedirectResponse($_url, 302);
        $response->send();
        
    }  

    return $output;
  }
  /**
   * отрисовываем HP mainpage
   */
  public function renderMainPage() {
    
    $output = array();
    $title = 'ComNews.ru. Новости цифровой трансформации, ИТ и телекоммуникаций';
    
    $output['#title'] = $title;
    cn_setHTMLTitle($output['#title']);
    
    $news_showed = array(0);

    $body = '';
    // main news ==================================================================================================
    $mainNews = '';
    $query = \Drupal::entityQuery('node')->condition('type', 'article')->condition('status', 1)
        ->condition('field_folders', 1007)
        ->condition('field_hp_date', cn_shortDBDate())
        ->sort('field_date','DESC')
        ;
    $nids = $query->execute();
    if(!count($nids)){
      $query = \Drupal::entityQuery('node')->condition('type', 'article')->condition('status', 1)
        ->condition('field_folders', 1007)
        ->sort('field_date','DESC')->sort('field_seq','ASC')->sort('created','DESC')->range(0,1);
        $nids = $query->execute();
    } 
    if(count($nids)){
      $node = \Drupal\node\Entity\Node::load(current($nids));
        //$node_storage = \Drupal::entityTypeManager()->getStorage('node');
        //$nodes = $node_storage->loadMultiple($nids);
      $img = cn_renderBlockImage($node->field_image);
      $lid = cn_renderLid($node->body,200,true);
      $tags = cn_renderNodeTags($node);
      $news_showed[] = $node->id();
      $mainNews =cn_renderHPblock('mainnews','Главная новость','/news','<div class="block-node"><a href="'.cn_getNodeAlias($node).'">'.$img.'<h4 class="node-title">'.$node->title->value.'</h4><div class="node-text">'.$lid.'</div></a>'.$tags.'</div>','gray-bg');  
    }
    // news comnews ==================================================================================================
    $news = '';
    $query = \Drupal::entityQuery('node')->condition('type', 'article')->condition('status', 1)
        ->condition('field_folders', 1008) 
        ->condition('field_hp_date', cn_shortDBDate())
        ->sort('field_seq','ASC')
        ;
    $nids = $query->execute();

    if(count($nids) < 3){
      $query = \Drupal::entityQuery('node')->condition('type', 'article')->condition('status', 1)
        ->condition('field_folders', 1008)
        ->sort('field_date','DESC')->sort('field_seq','ASC')->sort('created','DESC')->range(0,3);
        $nids = $query->execute();
    } 

    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nodes = $node_storage->loadMultiple($nids);
    
    foreach($nodes as $node){
      $img = '';//cn_renderBlockImage($node->field_image);
      $lid = cn_renderLid($node->body,200,true);
      $tags = cn_renderNodeTags($node);
      $news_showed[] = $node->id();
      if(!empty($node->field_alt_title->value)) $t = $node->field_alt_title->value; else $t = $node->title->value;
      $news .= '<div class="block-node"><a href="'.cn_getNodeAlias($node).'">'.$img.'<h4 class="node-title">'.$t.'</h4><div class="node-text">'.$lid.'</div></a>'.$tags.'</div>';  
    }
    // news digest ==================================================================================================
    $query = \Drupal::entityQuery('node')->condition('type', 'article')->condition('status', 1)
        ->condition('field_folders', 1009)
        ->condition('field_hp_date', cn_shortDBDate())
        ->sort('field_seq','ASC')
        ;
    $nids = $query->execute();

    if(count($nids) < 3){
      $query = \Drupal::entityQuery('node')->condition('type', 'article')->condition('status', 1)
        ->condition('field_folders', 1009)
        ->sort('field_hp_date','DESC')->sort('field_date','DESC')->sort('field_seq','ASC')->sort('created','DESC')->range(0,3);
        $nids = $query->execute();
    } 

    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nodes = $node_storage->loadMultiple($nids);
    
    foreach($nodes as $node){
      $img = '';//cn_renderBlockImage($node->field_image);
      $lid = cn_renderLid($node->body,200,true);
      $tags = cn_renderNodeTags($node);
      $news_showed[] = $node->id();
      $news .= '<div class="block-node"><a href="'.cn_getNodeAlias($node).'">'.$img.'<h4 class="node-title">'.$node->title->value.'</h4><div class="node-text">'.$lid.'</div></a>'.$tags.'</div>';  
    }
    $news = cn_renderHPblock('news','Новости','/news',$news,'white-bg'); // news comnews + digest 
    
    // point-of-view ==================================================================================================
    // interview from review ==================================================================================================
    $reviews = '';
    $query = \Drupal::entityQuery('node')->condition('type', 'review')->condition('status', 1)
        ->condition('field_folders', array(1166,1179),'IN')
        ->condition('field_hp_date', cn_shortDBDate())
        ->sort('field_seq','ASC')
        ;
    $nids = $query->execute();

    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nodes = $node_storage->loadMultiple($nids);
    
    foreach($nodes as $node){
      $img = cn_renderBlockImage($node->field_image);
      $lid = cn_renderLid($node->body,300);
      $person = cn_renderNodePerson($node);
      $news_showed[] = $node->id();
      $reviews .= '<div class="block-node person"><a href="'.cn_getNodeAlias($node).'">'.$img.$person.'<h4 class="node-title">'.$node->title->value.'</h4><div class="node-text">'.$lid.'</div></a></div>';  
    }

    $points = '';
    $query = \Drupal::entityQuery('node')->condition('type', 'interview')->condition('status', 1)
        ->condition('field_folders', 1013)
        ->condition('field_hp_date', cn_shortDBDate())
        ->sort('field_seq','ASC')
        ;
    $nids = $query->execute();

    if(!count($nids) && $reviews == ''){
      $query = \Drupal::entityQuery('node')->condition('type', 'interview')->condition('status', 1)
        ->condition('field_folders', 1013)
        ->sort('field_date','DESC')->sort('field_seq','ASC')->sort('created','DESC')->range(0,1);
        $nids = $query->execute();
    } 

    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nodes = $node_storage->loadMultiple($nids);
    
    $show_editorial = true;//count($nodes) == 1;
    $int = 0;
    foreach($nodes as $node){
      
      $img = cn_renderBlockImage($node->field_image);
      $lid = cn_renderLid($node->body,300);
      $person = cn_renderNodePerson($node);
      $news_showed[] = $node->id();
      $points .= '<div class="block-node person"><a href="'.cn_getNodeAlias($node).'">'.$img.$person.'<h4 class="node-title">'.$node->title->value.'</h4><div class="node-text">'.$lid.'</div></a></div>';  
      $int++;
      if($int == 1) $points .= cn_renderBannerSite('bn0041',0);
    }
    //$points =cn_renderHPblock('pointofview','Точка зрения','/point-of-view',$points,'gray-bg'); 
    

    $points =cn_renderHPblock('pointofview','Точка зрения','/point-of-view',$points.$reviews,'gray-bg'); 
    
    // editorials ==================================================================================================
    $editorials = '';
    if($show_editorial){

      //$d1 = cn_shortDBDate('this monday');
      //$d2 = cn_shortDBDate('this sunday');

      $d1 = cn_shortDBDate(date('Y-m-d',strtotime(date('Y-m-d',time()).' + 1 day')).' last monday');
      $d2 = cn_shortDBDate(date('Y-m-d',time()).' sunday');

      //ksm($d1,$d2);
      
      $query = \Drupal::entityQuery('node')->condition('type', 'editorial')->condition('status', 1)
          ->condition('field_folders', 1002)
          ->condition('field_hp_date', $d1, '>=')->condition('field_hp_date', $d2, '<=')
          //->condition('field_hp_date', cn_shortDBDate())
          ->sort('field_seq','ASC')
          ;
      $nids = $query->execute();
      /*
      if(!count($nids)){
        $query = \Drupal::entityQuery('node')->condition('type', 'editorial')->condition('status', 1)
          ->condition('field_folders', 1002)
          ->sort('field_date','DESC')->sort('field_seq','DESC')->sort('created','DESC')->range(0,1);
          $nids = $query->execute();
      } 
      */

      $node_storage = \Drupal::entityTypeManager()->getStorage('node');
      $nodes = $node_storage->loadMultiple($nids);
      
      foreach($nodes as $node){
        $img = cn_renderBlockImage($node->field_image);
        $lid = cn_renderLid($node->body,300);
        $person = cn_renderNodePerson($node);
        $news_showed[] = $node->id();
        $editorials .= '<div class="block-node person"><a href="'.cn_getNodeAlias($node).'">'.$img.$person.'<h4 class="node-title">'.$node->title->value.'</h4><div class="node-text">'.$lid.'</div></a></div>';  
      }
      $editorials =cn_renderHPblock('editorials','Редколонка','/editorials',$editorials,'gray-bg'); 
    }


    // de_opinions ==================================================================================================

    // de_opinions ==================================================================================================
    $de_opinions_top = '';
    $query = \Drupal::entityQuery('node')->condition('type', 'interview')->condition('status', 1)
        ->condition('field_folders', 1016)
        ->condition('field_show_on_top', 1)
        ->condition('field_hp_date', cn_shortDBDate())
        ->sort('field_seq','ASC')
        ;
    $nids = $query->execute();
     
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nodes = $node_storage->loadMultiple($nids);
    
    foreach($nodes as $node){
      $img = cn_renderBlockImage($node->field_image);
      $lid = cn_renderLid($node->body,300);
      $person = cn_renderNodePerson($node);
      $news_showed[] = $node->id();
      $de_opinions_top .= '<div class="block-node person"><a href="'.cn_getNodeAlias($node).'">'.$img.$person.'<h4 class="node-title">'.$node->title->value.'</h4><div class="node-text">'.$lid.'</div></a><br/></div>';  
    }
    //if(!empty($de_opinions_top)) $de_opinions_top .=''; 


    $de_opinions = '';
    $query = \Drupal::entityQuery('node')->condition('type', 'interview')->condition('status', 1)
        ->condition('field_folders', 1016)
        ->condition('nid',$news_showed,'NOT IN') 
        ->condition('field_hp_date', cn_shortDBDate())
        ->sort('field_seq','ASC')
        ;
    $nids = $query->execute();
    
    if(!count($nids)){
      $q = 2;
      if(!empty($de_opinions_top)) $q=1;
      $query = \Drupal::entityQuery('node')->condition('type', 'interview')->condition('status', 1)
        ->condition('field_folders', 1016)
        ->condition('nid',$news_showed,'NOT IN') 
        ->sort('field_date','DESC')->sort('field_seq','DESC')->sort('created','DESC')->range(0,$q);
        $nids = $query->execute();
    } 
     
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nodes = $node_storage->loadMultiple($nids);
    
    foreach($nodes as $node){
      $img = cn_renderBlockImage($node->field_image);
      $lid = cn_renderLid($node->body,300);
      $person = cn_renderNodePerson($node);
      $news_showed[] = $node->id();
      $de_opinions .= '<div class="block-node person"><a href="'.cn_getNodeAlias($node).'">'.$img.$person.'<h4 class="node-title">'.$node->title->value.'</h4><div class="node-text">'.$lid.'</div></a></div>';  
    }
    $de_opinions =cn_renderHPblock('de-opinions','Мнение','/digital-economy/opinions',$de_opinions,'gray-bg'); 

    
    // de_news ==================================================================================================
    $de_news = '';
    $query = \Drupal::entityQuery('node')->condition('type', 'article')->condition('status', 1)
        ->condition('field_folders', 1012)
        ->condition('field_hp_date', cn_shortDBDate())
        ->sort('field_seq','ASC')
        ;
    $nids = $query->execute();

    if(count($nids) < 8 ){
      $query = \Drupal::entityQuery('node')->condition('type', 'article')->condition('status', 1)
        ->condition('field_folders', 1012)
        ->sort('field_date','DESC')->sort('field_seq','ASC')->sort('created','DESC')->range(0,8);
        $nids = $query->execute();
    } 

    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nodes = $node_storage->loadMultiple($nids);
    
    foreach($nodes as $node){
      $news_showed[] = $node->id();
      $de_news .= '<div class="block-node small"><a href="'.cn_getNodeAlias($node).'"><h4 class="node-title">'.$node->title->value.'</h4></a></div>';  
    }
    $de_news =cn_renderHPblock('de-news','Цифровая экономика','/digital-economy','<div class="logos">Партнеры:<br/>'.cn_renderBannerSite('LOGOSDE','all').'</div>'.$de_opinions_top.$de_news,'white-bg'); 

    // regionalnews ==================================================================================================
    $regionalnews = '';
    
      $query = \Drupal::entityQuery('node')->condition('type', 'article')->condition('status', 1)
        ->condition('field_folders', 1011)
        ->sort('field_date','DESC')->sort('field_seq','ASC')->sort('created','DESC')->range(0,7);
        $nids = $query->execute();
     

    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nodes = $node_storage->loadMultiple($nids);
    
    foreach($nodes as $node){
      $news_showed[] = $node->id();
      $regionalnews .= '<div class="block-node small"><a href="'.cn_getNodeAlias($node).'"><h4 class="node-title">'.$node->title->value.'</h4></a></div>';  
    }
    $regionalnews = cn_renderHPblock('regionalnews','Региональные новости','/regions',$regionalnews,'white-bg'); 

    // pressreleases ==================================================================================================
    $pressreleases = '';
    $query = \Drupal::entityQuery('node')->condition('type', 'pressrelease')->condition('status', 1)
        ->condition('field_folders', 1018)
        ->sort('field_date','DESC')->sort('field_seq','DESC')->sort('created','DESC')->range(0,4);
        
    $nids = $query->execute();
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nodes = $node_storage->loadMultiple($nids);
    
    foreach($nodes as $node){
      $news_showed[] = $node->id();
      $pressreleases .= '<div class="block-node small"><a href="'.cn_getNodeAlias($node).'"><h4 class="node-title">'.$node->title->value.'</h4></a></div>';  
    }
    $pressreleases = cn_renderHPblock('pressreleases','Новости компаний','/pressreleases',$pressreleases,'white-bg'); 

    // solutions ==================================================================================================  
    $solutions = '';
    $query = \Drupal::entityQuery('node')->condition('type', 'pressrelease');
    if(!cn_isAdmin()) $query->condition('status', 1);
    $query->condition('field_folders', 1019)
        ->condition('field_hp_date', cn_shortDBDate())
        ->sort('field_seq','DESC')
        ;
    $nids = $query->execute();
    /*
    if(!count($nids)){
      $query = \Drupal::entityQuery('node')->condition('type', 'pressrelease')->condition('status', 1)
        ->condition('field_folders', 1019)
        ->sort('field_date','DESC')->sort('field_seq','DESC')->sort('created','DESC')->range(0,1);
        $nids = $query->execute();
    } 
    */
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nodes = $node_storage->loadMultiple($nids);
    
    foreach($nodes as $node){
      /*
      $img = cn_renderBlockImage($node->field_image);
      $lid = cn_renderLid($node->body,300);
      $person = cn_renderNodePerson($node);
      $news_showed[] = $node->id();
      $solutions .= '<div class="block-node"><a href="'.cn_getNodeAlias($node).'">'.$img.'<h4 class="node-title">'.$node->title->value.'</h4>'.$person.'<div class="node-text">'.$lid.'</div></a></div>';  
      */
      $img = cn_renderBlockImage($node->field_image);
      $lid = cn_renderLid($node->body,300);
      $person = '';
      if($node->field_authors->value !== ''){
        $person = '<div class="node-person">'.str_replace('<br>','<br/>', cn_renderPerson($node->field_authors->value)).'</div>';// '<div class="node-person"><span>Дмит­рий <br> Вол­ков</span> <br>  тех­ни­чес­кий ди­рек­тор крип­то­бир­жи CEX.IO</div>';
      }
      $news_showed[] = $node->id();
      $solutions .= '<div class="block-node '.($person == ''?'person':'').'"><a href="'.cn_getNodeAlias($node).'">'.$img.$person.'<h4 class="node-title">'.$node->title->value.'</h4><div class="node-text">'.$lid.'</div></a></div>';  

    }
    $solutions =cn_renderHPblock('solutions','Решения / Технологии','/solutions',$solutions,'white-bg'); 
    // COVID-19 ==================================================================================================  
    $covid19 = '';
    $qnt = 3;
    $query = \Drupal::entityQuery('node')->condition('type', 'pressrelease');
    if(!cn_isAdmin()) $query->condition('status', 1);
    $query->condition('field_folders', 1162)
        ->condition('nid',$news_showed,'NOT IN') 
        ->condition('field_hp_date', cn_shortDBDate())
        ->sort('field_date','DESC')->sort('field_seq','ASC')->sort('created','DESC')
        ;
    $nids = $query->execute();
    
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nodes1 = $node_storage->loadMultiple($nids);
    
    $covid19 .= '<div class="logos">Партнеры:<br/>'.cn_renderBannerSite('LOGOS','all').'</div>';

    foreach($nodes1 as $node){
      $img = '';// cn_renderBlockImage($node->field_image);
      $lid = '';// cn_renderLid($node->body,300);
      $person = '';// cn_renderNodePerson($node);
      $news_showed[] = $node->id();
      $covid19 .= '<div class="block-node small"><a href="'.cn_getNodeAlias($node).'">'.$img.'<h4 class="node-title">'.$node->title->value.'</h4>'.$person.'<div class="node-text">'.$lid.'</div></a></div>';  
    }
    $nodes2 = array();
    if(count($nodes1) < $qnt){
      $qnt = $qnt - count($nodes1); 
      $query = \Drupal::entityQuery('node')->condition('type', 'pressrelease')->condition('status', 1)
        ->condition('field_folders', 1162)
        ->condition('nid',$news_showed,'NOT IN') 
        ->sort('field_date','DESC')->sort('field_seq','ASC')->sort('created','DESC')->range(0,$qnt);
        $nids = $query->execute();
      $nodes2 = $node_storage->loadMultiple($nids); 
    }  
    
    foreach($nodes2 as $node){
      $img = '';// cn_renderBlockImage($node->field_image);
      $lid = '';// cn_renderLid($node->body,300);
      $person = '';// cn_renderNodePerson($node);
      $news_showed[] = $node->id();
      
      $covid19 .= '<div class="block-node small"><a href="'.cn_getNodeAlias($node).'">'.$img.'<h4 class="node-title">'.$node->title->value.'</h4>'.$person.'<div class="node-text">'.$lid.'</div></a></div>';  
    }
    $covid19 =cn_renderHPblock('covid-19','Oтрасль в ответ на COVID-19','/covid-19',$covid19,'gray-bg'); 
    
    // short_news ==================================================================================================
    $short_news = '';
    if(!empty($solutions)) $qnt = 3; else $qnt = 4;
    //if(date('Ymd') >= '20191118' && date('Ymd') < '20191202') $qnt = 6;
    $query = \Drupal::entityQuery('node')->condition('type', 'article')->condition('status', 1)
        ->condition('field_folders', 1010)
        ->sort('field_date','DESC')->sort('field_seq','DESC')->sort('created','DESC')->range(0,$qnt);
        
    $nids = $query->execute();
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nodes = $node_storage->loadMultiple($nids);
    
    foreach($nodes as $node){
      $news_showed[] = $node->id();
      $short_news .= '<div class="block-node small"><a href="'.cn_getNodeAlias($node).'"><h4 class="node-title">'.$node->title->value.'</h4></a></div>';  
    }
    $short_news =cn_renderHPblock('short-news','В фокусе','/shortnews',$short_news,'white-bg'); 
    // reviews ==================================================================================================
    
    $reviews = '';
    /*    
    $nids = array(207918,207926);
    if (date('Ymd')>= '20200715') $nids = array(207925,207915);
    if (date('Ymd')>= '20200722') $nids = array(207912,207939);
    if (date('Ymd')>= '20200727') $nids = array(207917,207926);
    if (date('Ymd')>= '20200731') $nids = array(207917,207927);
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nodes = $node_storage->loadMultiple($nids);
    
    foreach($nodes as $node){
      $news_showed[] = $node->id();
      if(count($node->field_i_persons->getValue()) == 0) 
        $t = $node->title->value; 
      else { 
        if(strpos($node->field_i_persons->value, ';')) $t = explode(';',$node->field_i_persons->value);
        else $t = explode(',',$node->field_i_persons->value);
        $t = $t[0].': '.$node->title->value;
      }  
      $reviews .= '<div class="block-node small"><a href="'.cn_getNodeAlias($node).'"><h4 class="node-title">'.$t.'</h4></a></div>';  
    }
    if(cn_isAdmin() || date('Ymd') >= 20200708) $reviews =cn_renderHPblock('reviews','Обзор: Цифровой Челябинск','/reviews/digital-chelyabinsk',$reviews,'white-bg');
    else $reviews = '';   
    */
     
    // today short_news ==================================================================================================
    $today_short_news = '';
    $query = \Drupal::entityQuery('node')->condition('type', 'article')->condition('status', 1)
        ->condition('field_folders', 1010)
        ->condition('field_hp_date', cn_shortDBDate())
        ->sort('field_date','DESC')->sort('field_seq','DESC')->sort('created','DESC')->range(0,6);
        
    $nids = $query->execute();
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nodes = $node_storage->loadMultiple($nids);
    
    foreach($nodes as $node){
      $news_showed[] = $node->id();
      $today_short_news .= '<div class="block-node small"><a href="'.cn_getNodeAlias($node).'"><h4 class="node-title">'.$node->title->value.'</h4></a></div>';  
    }
    $today_short_news =cn_renderHPblock('today-short-news','В фокусе','/shortnews',$today_short_news,'white-bg'); 

    

    // quotes ==================================================================================================
    $quotes = '';
    $query = \Drupal::entityQuery('node')->condition('type', 'quote')->condition('status', 1)
        ->condition('field_folders', 1021)
        ->condition('field_hp_date', cn_shortDBDate())
        ->sort('field_seq','ASC')
        ;
    $nids = $query->execute();
    /*
    if(!count($nids)){
      $query = \Drupal::entityQuery('node')->condition('type', 'quote')->condition('status', 1)
        ->condition('field_folders', 1021)
        ->sort('field_date','DESC')->sort('field_seq','ASC')->sort('created','DESC')->range(0,1);
        $nids = $query->execute();
    } 
    */
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nodes = $node_storage->loadMultiple($nids);
    
    foreach($nodes as $node){
      $lid = cn_renderLid($node->body,30000);
      $news_showed[] = $node->id();
      $quotes .= '<div class="block-node"><a href="'.cn_getNodeAlias($node).'"><h4 class="node-title">'.$node->title->value.'</h4><div class="node-text">'.$lid.'</div></a></div>';  
    }
    $quotes = cn_renderHPblock('quotes','Цитата дня','/quotes',$quotes,'gray-bg'); 


    // vopros ==================================================================================================
    $vopros = '';
    $query = \Drupal::entityQuery('node')
        ->condition('type', 'vopros')
        ->condition('status', 1)
        ->sort('field_date','DESC')->range(0,1)
        ;
      $nids = $query->execute();
      
    if(count($nids)){
      $node_storage = \Drupal::entityTypeManager()->getStorage('node');
      $nodes = $node_storage->loadMultiple($nids);
      $node = current($nodes);
      if($node->field_end_date->value >= date('Y-m-d') && $node->field_date->value <= cn_convertDateToStorageFormat('today')){
        $url = cn_getNodeAlias($node);
        $vopros .= '<div class="poll"><a href="'.cn_getNodeAlias($node).'"><h4 class="node-title">'.$node->title->value.'</h4></a><div class="v-form">
              <div class="v-graph 2019" rel="'.$node->id().'" style="margin:10px auto 40px;">
                <img src="/img/1/loader.gif" style="margin: 60px auto 0px; display:block;" class="loader"/>
              </div>
            </div></div>';  
            
      } else {
        $url = cn_getNodeAlias($node);
        $vopros .= '<div class="poll"><h4 class="node-title"><a href="'.cn_getNodeAlias($node).'">'.$node->title->value.'</a></h4><div class="v-form">
              <div class="v-graph" rel="'.$node->id().'" style="margin:10px auto 40px;">
                '.cn_getVoprosResults($node->id()).'
              </div>
            </div></div>';
      }
      $news_showed[] = $node->id();
      $vopros = cn_renderHPblock('vopros','Вопрос недели','/polls',$vopros,'gray-bg'); 
    }

    // exhibitions ==================================================================================================
    $exhibitions = '';
    
      $query = \Drupal::entityQuery('node')->condition('type', 'event')->condition('status', 1);
      
        $query->condition('field_start_date', cn_shortDBDate(),'>=');
        $query->condition('field_start_date', cn_shortDBDate('today + 1 week'),'<=');
     
      $query->sort('field_start_date','ASC')->sort('created','DESC');
        $nids = $query->execute();
     

    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nodes = $node_storage->loadMultiple($nids);
    
    foreach($nodes as $node){
      $exhibitions .= '<div class="block-node"><a href="/exhibition/'.$node->id().'"><div class="node-dates"><span>'.date('d.m.Y',$node->field_start_date->date->getTimestamp()).'</span><span>'.trim(count($node->field_end_date->getValue())?date('d.m.Y',$node->field_end_date->date->getTimestamp()):'').'</span></div><h4 class="node-title">'.$node->title->value.'</h4></a></div>';  
    }
    $exhibitions = cn_renderHPblock('exhibitions','Конференции / Выставки','/exhibitions',$exhibitions,'white-bg'); 

    // birthdays ==================================================================================================
    $birthdays = cn_getTodayBR();
    if ($birthdays != '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><div></div>') 
      $birthdays = cn_renderHPblock('birthdays','Дни рождения','https://whoiswho.comnews.ru/birthdays',$birthdays,'gray-bg'); 
    else $birthdays= cn_renderBannerSite('bnDR01',0);
    
    // oldnews
    $oldnews = '';
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('tags', $parent = 0, $max_depth = 1, $load_entities = FALSE);
    
    $tmp_banner = cn_renderBannerSite('bn0021',0);
    foreach($terms as $term){
      if($term->tid != 1003){

        $onews = '';
        $subterms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('tags', $parent = $term->tid, $max_depth = NULL, $load_entities = FALSE);
        $tags = array($term->tid);
        foreach($subterms as $subterm){
          $tags[] = $subterm->tid;
        }
        
        $query = \Drupal::entityQuery('node')->condition('type', 'article')->condition('status', 1)
        //->condition('field_folders', [1000,1007,1008,1009,1011],'IN')
        ->condition('field_tags', $tags,'IN')
        ->condition('nid',$news_showed,'NOT IN')
        ->sort('field_date','DESC')->sort('field_seq','ASC')->sort('created','DESC')->range(0,2);
        $nids = $query->execute();
     
        $node_storage = \Drupal::entityTypeManager()->getStorage('node');
        $nodes = $node_storage->loadMultiple($nids);
        
        foreach($nodes as $node){
          $news_showed[] = $node->id();
          $lid = cn_renderLid($node->body,200,true);
          $onews .= '<div class="block-node"><a href="'.cn_getNodeAlias($node).'"><h4 class="node-title">'.$node->title->value.'</h4><div class="node-text">'.$lid.'</div></a></div>';
           
        }
        if(!empty($onews)){
          $oldnews .= cn_renderHPblock('tag-'.$term->tid,$term->name,\Drupal::service('path.alias_manager')->getAliasByPath('/taxonomy/term/'.$term->tid),$onews.$tmp_banner,'gray-bg old-news');
          $tmp_banner = '';  
        }
      }
    }

    $lastcomments = cn_renderHPblock('last-comments','Сейчас обсуждают','','<div style="height:650px;" data-qnt="7"><img src="/img/1/loader.gif" style="margin: 60px auto 0px; display:block;" class="loader"/></div>','gray-bg last-comments');

    

    $body = '   <div id="hp-desktop" class="desktop"> 
                  <div class="primary-row">
                    '.cn_renderBannerSite('bn0006',0).'
                    <div class="section-1"> 
                      <div class="r1">'.$mainNews.cn_renderBannerSite('bn0003',0).$news.'</div>
                      <div class="r2">'.$points.cn_renderBannerSite('bn0016',0).$editorials.cn_renderBannerSite('bn0017',0).$solutions.$reviews.$short_news.cn_renderBannerSite('bnst01',0).cn_renderBannerSite('bn0037',0).'</div>
                    </div>
                    '.cn_renderBannerSite('bn0018',0).'
                    <div class="section-2">
                      '.$vopros.'
                    </div>
                    '.cn_renderBannerSite('bn0019',0).'
                    <div class="section-3">
                      <div class="r1">'.$regionalnews.'</div>
                      <div class="r2">'.$exhibitions.$birthdays.'</div>
                    </div>
                    '.cn_renderBannerSite('bn0020',0).'
                    <div class="section-4">
                      '.$oldnews.'
                    </div>  
                    '.cn_renderBannerSite('bn0014',0).'
                  </div>
                  <div class="side-row">'.(cn_GetVal($_REQUEST['p']) == 'main'?'<img src="/themes/site/images/300-500.jpg" style="display:block; margin:0px auto 20px;"/>':cn_renderBannerSite('bn0010',0).cn_renderBannerSite('bn0039',0).cn_renderBannerSite('bn0040',0)).cn_renderBannerSite('bnCOVID19',0).$de_news.cn_renderBannerSite('bn0022',0).$de_opinions.cn_renderBannerSite('bn0023',0).$covid19.$quotes.cn_renderBannerSite('bn0024',0).$pressreleases.cn_renderBannerSite('bn0025',0).$lastcomments.cn_renderBannerSite('bn0035',0).'<br>'.renderStandartBanner(date('Ymd')>='20200213' && date('Ymd')<='20200216').'</div>
                </div>
                <div id="hp-mobile" class="mobile">'.$mainNews.'</div><div class="mobile">'.$today_short_news.'</div>';
    $output['#children'] = $body;
    return $output;
  }

  /**
   * отрисовываем vision page
   */
  public function renderVision() {
    $out = '';
    
    $query = \Drupal::entityQuery('node')->condition('type', 'vision_issue')->condition('status', 1);
    $query->sort('field_seq','DESC');
    $nids = $query->execute();
    


    if(intval(current($nids))) $out = $this->renderVisionIssueHP(intval(current($nids)));
      else 
        throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
      
    return $out;
  }
  /**
   * отрисовываем vision issues page
   */
  public function renderVisionIssues() {
    $out = '';
    $output = array();
    $title = 'Vision: все выпуски';
    
    $output['#title'] = '';
    cn_setHTMLTitle($title);
    
    cn_addToExcludedBanners('bn0002');
    cn_addToExcludedBanners('bn0009');

    $query = \Drupal::entityQuery('node')->condition('type', 'vision_issue')->condition('field_seq', 9999, '!=' );
    if(!cn_isAdmin()) $query->condition('status', 1);
    $query->sort('field_seq','DESC');
    $nids = $query->execute();
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $issues = $node_storage->loadMultiple($nids);

    foreach($issues as $issue){
      $out .= '
      
      <div class="issue">
      <a href="/vision/'.$issue->field_seq->value.'/'.$issue->field_issue_url->value.'" class="i-header desktop" style="background-image:url('.cn_getImgUrl('original',$issue->field_cover).');">
        <h1>'.$issue->title->value.'</h1>
        <h3>'.$issue->field_alt_title->value.'</h3>
      </a>
      <a href="/vision/'.$issue->field_seq->value.'/'.$issue->field_issue_url->value.'" class="i-header mobile" style="background-image:url('.cn_getImgUrl('original',$issue->field_image).');">
        <h1>'.$issue->title->value.'</h1>
        <h3>'.$issue->field_alt_title->value.'</h3>
      </a>
      </div>
      
      '; // <div class="v-main"><div class="lid">'.$issue->	field_anons->value.'</div>
    }
    $out .= '
    
    <div class="issue">
      <a href="/reviews/digital-chelyabinsk" class="i-header desktop" style="background-image:url(https://www.comnews.ru/themes/site/images/bg1.jpg);">
        <h1></h1>
        <h3>Цифровой Челябинск</h3>
      </a>
      <a href="/reviews/digital-chelyabinsk" class="i-header mobile" style="background-image:url(https://www.comnews.ru/themes/site/images/bgm1.jpg);">
        <h1></h1>
        <h3>Цифровой Челябинск</h3>
      </a>
    </div>
    <div class="issue">
      <a href="/reviews/novyi-center-upravleniya" class="i-header desktop" style="background-image:url(https://www.comnews.ru/themes/site/images/bg.jpg);">
        <h1></h1>
        <h3>Центры управления и порталы обратной связи с населением</h3>
      </a>
      <a href="/reviews/novyi-center-upravleniya" class="i-header mobile" style="background-image:url(https://www.comnews.ru/themes/site/images/bgm.jpg);">
        <h1></h1>
        <h3>Центры управления и порталы обратной связи с населением</h3>
      </a>
    </div>
    ';

    
      
    $output['#children'] = '<h1 class="i-h1">Архив выпусков</h1><div class="issues">'.$out.'</div>';
    return $output;
  }

  /**
   * отрисовываем vision issue page
   */
  public function renderVisionIssue($issue,$dump,$block) {
    $out = '';
    $query = \Drupal::entityQuery('node')->condition('type', 'vision_issue');
    if(!cn_isAdmin()) $query->condition('status', 1);
    $query->condition('field_seq', intval($issue));
    $nids = $query->execute();
    


    if(intval(current($nids))) $out = $this->renderVisionIssueHP(intval(current($nids)));
      else 
        throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
      
    return $out;
  } 
  /**
   * отрисовываем reviw page
   */
  public function renderVisionIssueHP($nid) {
    
    $issue = \Drupal\node\Entity\Node::load($nid);
    $output = array();
    $title = 'Vision: '.$issue->title->value;
    
    $output['#title'] = '';
    cn_setHTMLTitle($title);
    
    cn_addToExcludedBanners('bn0002');
    cn_addToExcludedBanners('bn0009');

    $pass = '';
    if( cn_isAdmin()) $pass = '?pass=1';

    //cn_addToExcludedBanners('bn0013');
    $body = '';
    $blocksTxt = cn_renderBannerSite('bnVM01',0);
    $query = \Drupal::entityQuery('node')->condition('type', 'vision_block');
    if(!cn_isAdmin()) $query->condition('status', 1);
    $query->condition('field_vision_parent', $nid);
    $query->sort('field_seq','ASC');
    $nids = $query->execute();
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $blocks = $node_storage->loadMultiple($nids);
    $block_class = ' white-bg ';
    $blkCount = 0;
    foreach($blocks as $block){
      $blockTxt = '';
      
      
      
      $img_pos = 'img-left';
      foreach(explode(chr(10),$block->field_r_links->value) as $link){
        
        $n = explode('/',$link);
        $n = intval($n[4]);
        if($n){
          $node = \Drupal\node\Entity\Node::load($n);
          $type = '';
          $folders = $node->get('field_folders')->getValue();
          foreach($folders as $folder){
            if(in_array($folder['target_id'],array('1178'))){ 
              $type = 'review';
              $img = '<div class="node-img"><img src="'.cn_getImgUrl('large',$node->field_image).'" alt="'.htmlspecialchars($node->field_image->alt).'" title="'.htmlspecialchars($node->field_image->title).'"/></div>';
              $lid = cn_renderLid($node->body,300);
              $blockTxt .= '<div class="block-node  '.$type.' '.$img_pos.'"><a href="'.cn_getNodeAlias($node).$pass.'">'.$img.'<h4 class="node-title">'.$node->title->value.'</h4><div class="node-text">'.$lid.'</div></a></div>';  
              if($img_pos == 'img-left') $img_pos = 'img-right'; else $img_pos = 'img-left';
              break;
            }
            if(in_array($folder['target_id'],array('1179'))){ 
              $type = 'interview'; 
              $img = cn_renderBlockImage($node->field_image);
              $lid = cn_renderLid($node->body,300);
              $person = str_replace('</div>','<div class="v-quote">'.$node->field_image_text->value.'</div></div>',cn_renderNodePerson($node));
              $person = str_replace('   ','<br/>',$person);
              $blockTxt .= '<div class="block-node person '.$type.'"><a href="'.cn_getNodeAlias($node).$pass.'">'.$img.$person.'<h4 class="node-title">'.$node->title->value.'</h4><div class="node-text">'.$lid.'</div></a></div>';  
              break;
            }
            if(in_array($folder['target_id'],array('1180'))){ 
              $type = 'analytics'; 
              $img = '<div class="node-img">'.cn_renderImage($node->field_image,'original').'</div>';
              $lid = cn_renderLid($node->body,300);
              $blockTxt .= '<div class="block-node '.$type.'"><a href="'.cn_getNodeAlias($node).$pass.'"><h4 class="node-title">'.$node->title->value.'</h4><div class="node-text">'.$lid.'</div>'.$img.'</a></div>';  
              break;
            }

          } 
          
          
        }
        
      }  
      if($blkCount == 1) $blocksTxt .= cn_renderBannerSite('bnV008',0).cn_renderBannerSite('bnVM02',0);
      if($blkCount == 2) $blocksTxt .= cn_renderBannerSite('bnVM03',0);
      $blocksTxt .=cn_renderHPblock('block'.$block->Id(),$block->title->value, '', $blockTxt, $block_class.' v-'.$block->field_issue_class->value); 
      if($block_class == ' white-bg ') $block_class = ' gray-bg '; else $block_class = ' white-bg ';
      $blkCount++;
    }
    $blocksTxt .= cn_renderBannerSite('bnVM04',0);
    /*
    $node = \Drupal\node\Entity\Node::load(208026);
    $img = cn_renderBlockImage($node->field_image);
    $lid = cn_renderLid($node->body,200,true);
    $more = '<div class="block-node"><a href="'.cn_getNodeAlias($node).'">'.$img.'<h4 class="node-title">'.$node->title->value.'</h4><div class="node-text">'.$lid.'</div></a>'.$tags.'</div>';  
    $node = \Drupal\node\Entity\Node::load(208028);
    $img = cn_renderBlockImage($node->field_image);
    $lid = cn_renderLid($node->body,200,true);
    $more .= '<div class="block-node"><a href="'.cn_getNodeAlias($node).'">'.$img.'<h4 class="node-title">'.$node->title->value.'</h4><div class="node-text">'.$lid.'</div></a>'.$tags.'</div>';  
    $node = \Drupal\node\Entity\Node::load(208031);
    $img = cn_renderBlockImage($node->field_image);
    $lid = cn_renderLid($node->body,200,true);
    $more .= '<div class="block-node"><a href="'.cn_getNodeAlias($node).'">'.$img.'<h4 class="node-title">'.$node->title->value.'</h4><div class="node-text">'.$lid.'</div></a>'.$tags.'</div>';  
    */
    $more = '';
    
    $style = '';
    //ksm($issue->field_cover_old->value);
    if($issue->field_cover_old->value != ''){
      $colors = explode('|',$issue->field_cover_old->value);
      $style = '
      <style>
                  body.level1-vision:before {
                    background: rgb(138,177,206);
                    background: -moz-linear-gradient(45deg, '.$colors[0].' 40%, rgba(32,124,202,1) 50%, '.$colors[1].' 60%);
                    background: -webkit-linear-gradient(45deg, '.$colors[0].' 40%,rgba(32,124,202,1) 50%,'.$colors[1].' 60%);
                    background: linear-gradient(45deg, '.$colors[0].' 40%,rgba(32,124,202,1) 50%,'.$colors[1].' 60%);
                    filter: progid:DXImageTransform.Microsoft.gradient( startColorstr=\''.$colors[0].'\', endColorstr=\''.$colors[1].'\',GradientType=1 );
                    
                  }
                  </style>
      ';
    }

    $team = cn_renderHPblock('team','Контакты','',
    '
    <p><strong>Редактор</strong><br/><a href="mailto:ks.prudnikova@comnews.ru">Ксения Прудникова</a></p>
    <p><strong>Обозреватель</strong><br/><a href="mailto:ys@comnews.ru">Яков Шпунт</a></p>
    <p><strong>Специалист по исследованию рынка</strong><br/><a href="mailto:sn@comnews.ru">Наталья Смирнова</a></p>
    <p><strong>Вопросы коммерческого сотрудничества</strong><br/><a href="mailto:irina@comnews.ru">Ирина Глухова</a></p>
    '
    ,'gray-bg');  
  
    /*
    $node = \Drupal\node\Entity\Node::load(205768);
    $n = explode(',',$node->field_i_persons->value);
    if(!empty($node->field_company->value))
      $n = $n[0].', <span>'.$node->field_company->value.'</span>'; 
    else 
      $n = $n[0]; 
    $int1 = '<a href="'.cn_getNodeAlias($node).'" class="i-block">
          <div class="img">'.cn_renderBlockImage($node->field_image).'</div>
          <h3><strong>'.$n.'</strong> '.$node->title->value.'</h3><p></p>
    </a>';
    */ 
    cn_setMetaTag('property="og:image"','https://www.comnews.ru/themes/site/images/vision.jpg');

    $body = '   <div id="hp-vision" class="hp-vision"> 
                  '.$style.'
                  <div class="v-header desktop" style="background-image:url('.cn_getImgUrl('original',$issue->field_cover).');">
                    <h1>'.$issue->title->value.'</h1>
                    <h3>'.$issue->field_alt_title->value.'</h3>
                  </div>
                  <div class="v-header mobile" style="background-image:url('.cn_getImgUrl('original',$issue->field_image).');">
                    <h1>'.$issue->title->value.'</h1>
                    <h3>'.$issue->field_alt_title->value.'</h3>
                  </div>
                  <div class="v-main">
                    <div class="lid">
                      '.$issue->	field_anons->value.'
                    </div>
                    '.$blocksTxt.'
                  </div>
                  <div class="v-sidebar">
                  '.cn_renderBannerSite('bnV002',0).cn_renderBannerSite('bnV004',0).' 
                  <br/> '.$more.cn_renderBannerSite('bnV003',0).cn_renderBannerSite('bnV005',0).cn_renderBannerSite('bnV006',0).cn_renderBannerSite('bnV007',0).'<br/>'.$team.'
                  </div>
                  <div class="clear"></div>
                </div>';
    $output['#children'] = $body;
    return $output;
  }

  /**
   * отрисовываем reviw page
   */
  public function renderReview($review) {
    $out = '';
    if($review == 'novyi-center-upravleniya') $out = $this->renderReview042020();
      else 
    if($review == 'digital-chelyabinsk') $out = $this->renderReview072020();
      else {
        throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
      }
    return $out;
  } 
  /**
   * отрисовываем reviw page
   */
  public function renderReview042020() {
    
    $output = array();
    $title = 'Обзор: Центры управления и порталы обратной связи с населением';
    
    $output['#title'] = $title;
    cn_setHTMLTitle($output['#title']);
    
    cn_addToExcludedBanners('bn0002');
    cn_addToExcludedBanners('bn0009');
    cn_addToExcludedBanners('bn0013');
    $body = '';
    /*
    // main news ==================================================================================================
    $mainNews = '';
    $query = \Drupal::entityQuery('node')->condition('type', 'article')->condition('status', 1)
        ->condition('field_folders', 1007)
        ->condition('field_hp_date', cn_shortDBDate())
        ->sort('field_date','DESC')
        ;
    $nids = $query->execute();
    if(!count($nids)){
      $query = \Drupal::entityQuery('node')->condition('type', 'article')->condition('status', 1)
        ->condition('field_folders', 1007)
        ->sort('field_date','DESC')->sort('field_seq','ASC')->sort('created','DESC')->range(0,1);
        $nids = $query->execute();
    } 
    if(count($nids)){
      $node = \Drupal\node\Entity\Node::load(current($nids));
        //$node_storage = \Drupal::entityTypeManager()->getStorage('node');
        //$nodes = $node_storage->loadMultiple($nids);
      $img = cn_renderBlockImage($node->field_image);
      $lid = cn_renderLid($node->body,200,true);
      $tags = cn_renderNodeTags($node);
      $news_showed[] = $node->id();
      $mainNews =cn_renderHPblock('mainnews','Главная новость','/news','<div class="block-node"><a href="'.cn_getNodeAlias($node).'">'.$img.'<h4 class="node-title">'.$node->title->value.'</h4><div class="node-text">'.$lid.'</div></a>'.$tags.'</div>','gray-bg');  
    }
    */
    $node = \Drupal\node\Entity\Node::load(205797);
    $n = explode(';',$node->field_authors->value);
    $st1 = '
        <div class="c1">
          <h3><a href="'.cn_getNodeAlias($node).'">'.$node->title->value.'</a></h3>
          <p>'.$n[0].'</p>
        </div>
        <a href="'.cn_getNodeAlias($node).'" class="c2">'.cn_renderLid($node->body,300,true).'</a>
        <div class="c3" style="background-image:url(\''.cn_getImgUrl('large',$node->field_image).'\')"></div>
        ';
    $node = \Drupal\node\Entity\Node::load(205774);
    $n = explode(';',$node->field_authors->value);
    $st2 = '
        <div class="c1">
          <h3><a href="'.cn_getNodeAlias($node).'">'.$node->title->value.'</a></h3>
          <p>'.$n[0].'</p>
        </div>
        <a href="'.cn_getNodeAlias($node).'" class="c2">'.cn_renderLid($node->body,300,true).'</a>
        <div class="c3" style="background-image:url(\''.cn_getImgUrl('large',$node->field_image).'\')"></div>
        ';
    $node = \Drupal\node\Entity\Node::load(205804);
    $n = explode(';',$node->field_authors->value);
    $st3 = '
          <a href="'.cn_getNodeAlias($node).'" class="c1">
          <img src="/themes/site/images/b10.jpg" style="width:100%;"/>
          </a>
          <div class="c2">
          <a href="'.cn_getNodeAlias($node).'"><p>'.cn_renderLid($node->body,420,true).'</p></a>
          </div>';
    $node = \Drupal\node\Entity\Node::load(205802);
    $n = explode(';',$node->field_authors->value);
    $st4 = '
        <div class="c1">
          <h3><a href="'.cn_getNodeAlias($node).'">'.$node->title->value.'</a></h3>
          <p>'.$n[0].'</p>
        </div>
        <a href="'.cn_getNodeAlias($node).'" class="c2">'.cn_renderLid($node->body,300,true).'</a>
        <div class="c3" style="background-image:url(\''.cn_getImgUrl('large',$node->field_image).'\')"></div>
        ';   
        $st4 = '<a href="'.cn_getNodeAlias($node).'" class="i-block">
          <div class="img"><img src="'.cn_getImgUrl('large',$node->field_image).'" style="height:186px; width: auto;margin-left: -28px;"/></div>
          <h3>'.$node->title->value.'</h3>
          <p>'.cn_renderLid($node->body,300,true).'</p>
        </a>';     
    $node = \Drupal\node\Entity\Node::load(205825);
    $n = explode(';',$node->field_authors->value);
    $st5 = '<a href="'.cn_getNodeAlias($node).'">
              <h3>'.$node->title->value.'</h3>
              <img src="'.cn_getImgUrl('original',$node->field_image).'"/>
            </a>';
    $node = \Drupal\node\Entity\Node::load(205768);
    $n = explode(',',$node->field_i_persons->value);
    if(!empty($node->field_company->value))
      $n = $n[0].', <span>'.$node->field_company->value.'</span>'; 
    else 
      $n = $n[0]; 
    $int1 = '<a href="'.cn_getNodeAlias($node).'" class="i-block">
          <div class="img">'.cn_renderBlockImage($node->field_image).'</div>
          <h3><strong>'.$n.'</strong> '.$node->title->value.'</h3><p></p>
    </a>';

    $node = \Drupal\node\Entity\Node::load(205762);
    $n = explode(',',$node->field_i_persons->value);
    if(!empty($node->field_company->value))
      $n = $n[0].', <span>'.$node->field_company->value.'</span>'; 
    else 
      $n = $n[0];
    $int2 = '<a href="'.cn_getNodeAlias($node).'" class="i-block">
          <div class="img">'.cn_renderBlockImage($node->field_image).'</div>
          <h3><strong>'.$n.'</strong> '.$node->title->value.'</h3><p></p>
    </a>';
    
    cn_setMetaTag('property="og:image"','https://www.comnews.ru/themes/site/images/fb1.jpg');

    $node = \Drupal\node\Entity\Node::load(205767);
    $n = explode(',',$node->field_i_persons->value);
    if(!empty($node->field_company->value))
      $n = $n[0].', <span>'.$node->field_company->value.'</span>'; 
    else 
      $n = $n[0];
    $int3 = '<a href="'.cn_getNodeAlias($node).'" class="i-block">
          <div class="img">'.cn_renderBlockImage($node->field_image).'</div>
          <h3><strong>'.$n.'</strong> '.$node->title->value.'</h3><p></p>
    </a>';

    $node = \Drupal\node\Entity\Node::load(205765);
    $n = explode(',',$node->field_i_persons->value);
    if(!empty($node->field_company->value))
      $n = $n[0].', <span>'.$node->field_company->value.'</span>'; 
    else 
      $n = $n[0];
    $int4 = '<a href="'.cn_getNodeAlias($node).'" class="i-block">
          <div class="img">'.cn_renderBlockImage($node->field_image).'</div>
          <h3><strong>'.$n.'</strong> '.$node->title->value.'</h3><p></p>
    </a>';

    $node = \Drupal\node\Entity\Node::load(205747);
    $n = explode(',',$node->field_i_persons->value);
    if(!empty($node->field_company->value))
      $n = $n[0].', <span>'.$node->field_company->value.'</span>'; 
    else 
      $n = $n[0];
    $int5 = '<a href="'.cn_getNodeAlias($node).'" class="i-block">
          <div class="img">'.cn_renderBlockImage($node->field_image).'</div>
          <h3><strong>'.$n.'</strong> '.$node->title->value.'</h3><p>'.cn_renderLid($node->body,400,true).'</p>
    </a>';
    $node = \Drupal\node\Entity\Node::load(205796);
    $n = explode(',',$node->field_i_persons->value);
    if(!empty($node->field_company->value))
      $n = $n[0].', <span>'.$node->field_company->value.'</span>'; 
    else 
      $n = $n[0];    
    $int6 = '<a href="'.cn_getNodeAlias($node).'" class="i-block">
          <div class="img">'.cn_renderBlockImage($node->field_image).'</div>
          <h3><strong>'.$n.'</strong> '.$node->title->value.'</h3><p></p>
    </a>';
    $node = \Drupal\node\Entity\Node::load(205832);
    $n = explode(',',$node->field_i_persons->value);
    if(!empty($node->field_company->value))
      $n = $n[0].', <span>'.$node->field_company->value.'</span>'; 
    else 
      $n = $n[0];
    $int7 = '<a href="'.cn_getNodeAlias($node).'" class="i-block">
          <div class="img">'.cn_renderBlockImage($node->field_image).'</div>
          <h3><strong>'.$n.'</strong> '.$node->title->value.'</h3><p></p>
    </a>';  
    
    $node = \Drupal\node\Entity\Node::load(207039);
    $n = explode(',',$node->field_i_persons->value);
    if(!empty($node->field_company->value))
      $n = $n[0].', <span>'.$node->field_company->value.'</span>'; 
    else 
      $n = $n[0];
    $int8 = '<a href="'.cn_getNodeAlias($node).'" class="i-block">
          <div class="img">'.cn_renderBlockImage($node->field_image).'</div>
          <h3><strong>'.$n.'</strong> '.$node->title->value.'</h3><p>'.cn_renderLid($node->body,200,true).'</p>
    </a>';
    $body = '   <div id="hp-review-04-2020" class="hp-review"> 
                    <div class="section-1">
                      <h1>Центры управления и порталы обратной связи с населением</h1>
                      <h3>Как надо <br>слушать граждан</h3>
                      <p>В России начинают создаваться центры управления регионами. Пока речь идет об обязательном запуске порталов обратной связи с населением. Образец – широко разрекламированный подмосковный портал «Добродел». Каким должен быть полноценный центр управления? Какие инструменты он должен содержать для того, чтобы стать реальным инструментом повышения эффективности работы органов власти?</p>
                    </div>
                    <div class="section-13-23"> 
                      '.$st1.'
                    </div>
                    <div class="section-2">
                      <div class="c">'.$int5.'</div>
                      <div class="c">'.$int8.'</div>
                    </div>
                    <div class="section-3">
                      <div class="c">'.$int3.'</div>
                      <div class="c">'.$int1.'</div>
                      <div class="c">'.$int2.'</div>
                    </div>
                    <div class="section-13-23"> 
                      '.$st2.'
                    </div>
                    <div class="section-map">
                      <img id="m0" style="display:none;"  src="/themes/site/images/r1-m0.jpg"/>
                      <img id="m1" style="display:block;"  src="/themes/site/images/r5-m1.jpg"/>
                      <img id="m2"  src="/themes/site/images/r5-m2.jpg"/>
                      <img id="m3"  src="/themes/site/images/r5-m3.jpg"/>
                      <img id="m4"  src="/themes/site/images/r5-m4.jpg"/>
                      <img id="m5"  src="/themes/site/images/r5-m5.jpg"/>
                      <img id="m6"  src="/themes/site/images/r5-m6.jpg"/>
                      <img id="m7"  src="/themes/site/images/r5-m7.jpg"/>
                      <img id="m8"  src="/themes/site/images/r5-m8.jpg"/>
                      <div rel="m1" id="d1"></div>
                      <div rel="m2" id="d2"></div>
                      <div rel="m3" id="d3"></div>
                      <div rel="m4" id="d4"></div>
                      <div rel="m5" id="d5"></div>
                      <div rel="m6" id="d6"></div>
                      <div rel="m7" id="d7"></div>
                      <div rel="m8" id="d8"></div>
                      <span>[ для просмотра выберите компанию из списка ]</span>
                    </div>
                    <div class="section-3">
                      <div class="c">'.$int4.'</div>
                      <div class="c">'.$int6.'</div>
                      <div class="c">'.$int7.'</div>
                    </div>
                    <div class="section-12-12">
                      '.$st3.'
                    </div>
                    <div class="section-2"> 
                      
                      <div class="c tab">'.$st5.'</div>
                      <div class="c">'.$st4.'</div>
                      
                    </div>
                    <div class="desktop">
                    <a rel="lightbox[img][Захватывающая история разработки единой цифровой платформы]" href="https://www.comnews.ru/sites/default/files2019/review/2020-04/baner-razvernutyy2-2000.jpg"><img src="https://www.comnews.ru/sites/default/files2019/styles/img1200/public/review/2020-04/baner-razvernutyy2-2000.jpg?itok=PLRU4Cb5" title="Увеличить"></a>
                    </div>
                    <div class="mobile" style="height:600px; overflow:hidden; position:relative;">
                    <a href="https://www.comnews.ru/sites/default/files2019/review/2020-04/baner-razvernutyy-vert.jpg" target="_blank"><img src="https://www.comnews.ru/sites/default/files2019/styles/img1200/public/review/2020-04/baner-razvernutyy-vert.jpg?itok=s8kNOk6T" title="Увеличить"><span style="text-align:center;position:absolute; bottom:10px; left:10px; right:10px; color:#fff; ">[ Развернуть ]</span></a>
                    </div>
                </div>';
    $output['#children'] = $body;
    return $output;
  }

  /**
   * отрисовываем reviw page
   */
  public function renderReview072020() {
    
    $output = array();
    $title = 'Обзор: Цифровой Челябинск';
    
    $output['#title'] = $title;
    cn_setHTMLTitle($output['#title']);
    
    cn_addToExcludedBanners('bn0002');
    cn_addToExcludedBanners('bn0009');
    cn_addToExcludedBanners('bn0013');
    $body = '';
    cn_setMetaTag('property="og:image"','https://www.comnews.ru/sites/default/files2019/reviews/2020-07/shapka-obzora-400-400.jpg');
    $pass = '';//'?pass=1';
    $node = \Drupal\node\Entity\Node::load(207918);
    $n = explode(';',$node->field_authors->value);
    $st1 = '
        <div class="c1">
          <h3><a href="'.cn_getNodeAlias($node).$pass.'">'.$node->title->value.'</a></h3>
          <p>'.$n[0].'</p>
        </div>
        <a href="'.cn_getNodeAlias($node).$pass.'" class="c2">'.cn_renderLid($node->body,300,true).'</a>
        <div class="c3" style="background-image:url(\''.cn_getImgUrl('large',$node->field_image).'\')"></div>
        ';
    $node = \Drupal\node\Entity\Node::load(207927);
    
    $n = explode(',','Вадим Злобин, генеральный директор компании "Систематика Консалтинг"');
    if(!empty($node->field_company->value))
      $n = $n[0].', <span>'.$node->field_company->value.'</span>'; 
    else 
      $n = $n[0];
    $st2 = '<a href="'.cn_getNodeAlias($node).$pass.'" class="i-block">
          <div class="img">'.cn_renderBlockImage($node->field_image).'</div>
          <h3><strong>'.$n.'</strong> '.$node->title->value.'</h3><p>'.cn_renderLid($node->body,400,true).'</p>
    </a>';
    /*
    $n = explode(',',$node->field_i_persons->value);
        $st2 = '<a href="'.cn_getNodeAlias($node).$pass.'" class="i-block">
              <div class="img">'.cn_renderBlockImage($node->field_image).'</div>
              <h3>'.$node->title->value.'</h3><p>'.cn_renderLid($node->body,400,true).'</p>
        </a>';//                  
    */    
    $node = \Drupal\node\Entity\Node::load(207916);
    $n = explode(';',$node->field_authors->value);
    $st3 = '<a href="'.cn_getNodeAlias($node).$pass.'">
              <h3>'.$node->title->value.'</h3>
              <img src="'.cn_getImgUrl('original',$node->field_image).'"/>
              </a>';
    $node = \Drupal\node\Entity\Node::load(207926);
          $st4 = '<a href="'.cn_getNodeAlias($node).$pass.'" class="i-block">
          <div class="img">'.cn_renderBlockImage($node->field_image).'</div>
          <h3>'.$node->title->value.'</h3><p>'.cn_renderLid($node->body,120,true).'</p>
            </a>';  
    $node = \Drupal\node\Entity\Node::load(207925);
          $st5 = '<a href="'.cn_getNodeAlias($node).$pass.'" class="i-block">
          <div class="img">'.cn_renderBlockImage($node->field_image).'</div>
          <h3>'.$node->title->value.'</h3><p>'.cn_renderLid($node->body,400,true).'</p>
              </a>';
    $node = \Drupal\node\Entity\Node::load(207915);
        $n = explode(',',$node->field_i_persons->value);
        if(!empty($node->field_company->value))
          $n = $n[0].', <span>'.$node->field_company->value.'</span>'; 
        else 
          $n = $n[0];
        $int1 = '<a href="'.cn_getNodeAlias($node).$pass.'" class="i-block">
              <div class="img">'.cn_renderBlockImage($node->field_image).'</div>
              <h3><strong>'.$n.'</strong> '.$node->title->value.'</h3><p>'.cn_renderLid($node->body,400,true).'</p>
        </a>';
    $node = \Drupal\node\Entity\Node::load(207912);
        $n = explode(';',$node->field_i_persons->value);
        if(!empty($node->field_company->value))
          $n = $n[0].', <span>'.$node->field_company->value.'</span>'; 
        else 
          $n = $n[0];
        $int2 = '<a href="'.cn_getNodeAlias($node).$pass.'" class="i-block">
              <div class="img">'.cn_renderBlockImage($node->field_image).'</div>
              <h3><strong>'.$n.'</strong> '.$node->title->value.'</h3><p>'.cn_renderLid($node->body,400,true).'</p>
        </a>';
    $node = \Drupal\node\Entity\Node::load(207917);
        $n = explode(',',$node->field_i_persons->value);
        if(!empty($node->field_company->value))
          $n = $n[0].', <span>'.$node->field_company->value.'</span>'; 
        else 
          $n = $n[0];
        $int3 = '<a href="'.cn_getNodeAlias($node).$pass.'" class="i-block">
              <div class="img">'.cn_renderBlockImage($node->field_image).'</div>
              <h3><strong>'.$n.'</strong> '.$node->title->value.'</h3>
        </a>';//<p>'.cn_renderLid($node->body,400,true).'</p>
    $node = \Drupal\node\Entity\Node::load(207913);
        $n = explode(',',$node->field_i_persons->value);
        if(!empty($node->field_company->value))
          $n = $n[0].', <span>'.$node->field_company->value.'</span>'; 
        else 
          $n = $n[0];
        $int5 = '<a href="'.cn_getNodeAlias($node).$pass.'" class="i-block">
              <div class="img">'.cn_renderBlockImage($node->field_image).'</div>
              <h3><strong>'.$n.'</strong> '.$node->title->value.'</h3><p>'.cn_renderLid($node->body,400,true).'</p>
        </a>';
    $node = \Drupal\node\Entity\Node::load(207914);
        $n = explode(',',$node->field_i_persons->value);
        if(!empty($node->field_company->value))
          $n = $n[0].', <span>'.$node->field_company->value.'</span>'; 
        else 
          $n = $n[0];
        $int7 = '<a href="'.cn_getNodeAlias($node).$pass.'" class="i-block">
              <div class="img">'.cn_renderBlockImage($node->field_image).'</div>
              <h3><strong>'.$n.'</strong> '.$node->title->value.'</h3><p>'.cn_renderLid($node->body,400,true).'</p>
        </a>';
    $node = \Drupal\node\Entity\Node::load(207919);
        $n = explode(',',$node->field_i_persons->value);
        if(!empty($node->field_company->value))
          $n = $n[0].', <span>'.$node->field_company->value.'</span>'; 
        else 
          $n = $n[0];
        $int8 = '<a href="'.cn_getNodeAlias($node).$pass.'" class="i-block">
              <div class="img">'.cn_renderBlockImage($node->field_image).'</div>
              <h3><strong>'.$n.'</strong> '.$node->title->value.'</h3><p>'.cn_renderLid($node->body,200,true).'</p>
        </a>';
    $node = \Drupal\node\Entity\Node::load(207939);
        $n = explode(';',$node->field_i_persons->value);
        if(!empty($node->field_company->value))
          $n = $n[0].', <span>'.$node->field_company->value.'</span>'; 
        else 
          $n = $n[0];
        $int9 = '<a href="'.cn_getNodeAlias($node).$pass.'" class="i-block">
              <div class="img">'.cn_renderBlockImage($node->field_image).'</div>
              <h3><strong>'.$n.'</strong> '.$node->title->value.'</h3>
        </a>';//<p>'.cn_renderLid($node->body,200,true).'</p>
    $body = '   <div id="hp-review-07-2020" class="hp-review"> 
                    <div class="section-1">
                      <h1><span>Цифровой</span> Челябинск</h1>
                      
                      <p>Технологичный муниципалитет —  это не только стандарты умного города, это стратегия развития экономики и среды обитания. <br/><span>Как этот путь развития проходят в Челябинске, какой у города есть опыт и потенциал, как выглядит ландшафт работающих решений, кто делает его цифровым, взгляд изнутри в обзоре Цифровой Челябинск.</span></p>
                    </div>
                    <div class="section-13-23"> 
                      '.$st1.'
                    </div>
                    <div class="section-2">
                      <div class="c">'.$int1.'</div>
                      <div class="c">'.$int2.'</div>
                    </div>
                    <div class="section-2"> 
                      
                      <div class="c">'.$st2.'</div>
                      <div class="c tab">'.$st3.'</div>
                      
                    </div>
                    <div class="section-full desktop"> 
                      <a href="/content/207904/2020-07-08/2020-w28/cifrovoy-chelyabinsk'.$pass.'"><img src="/themes/site/images/t1-072020-1.jpg"/></a><br/><br/>
                    </div>
                    <div class="section-full mobile"> 
                      <a href="/content/207904/2020-07-08/2020-w28/cifrovoy-chelyabinsk'.$pass.'"><img src="/themes/site/images/t1-072020-1m.jpg"/></a><br/><br/>
                    </div>

                    <div class="section-3">
                      <div class="c">'.$int9.'</div>
                      <div class="c">'.$int3.'</div>
                      <div class="c">'.$st4.'</div>
                      
                    </div>
                    <div class="section-full desktop"> 
                      <a href="/content/207906/2020-07-08/2020-w28/5-zvezd-che-luchshie-proekty-goroda'.$pass.'"><img src="/themes/site/images/t1-072020-2.jpg"/></a><br/><br/>
                    </div>
                    <div class="section-full mobile"> 
                      <a href="/content/207906/2020-07-08/2020-w28/5-zvezd-che-luchshie-proekty-goroda'.$pass.'"><img src="/themes/site/images/t1-072020-2m.jpg"/></a><br/><br/>
                    </div>
                    <div class="section-2">
                      <div class="c">'.$int5.'</div>
                      <div class="c">'.$st5.'</div>
                      
                    </div>
                    <div class="section-2">
                      <div class="c">'.$int7.'</div>
                      <div class="c">'.$int8.'</div>
                      
                    </div>
                    
                </div>';
    $output['#children'] = $body;
    return $output;
  }

  public function renderCalendar($date) {
    
    $date = strtotime($date);
    if($date === false){
      $date = strtotime('today');
    }
    // выходных нет
    //if(!cn_isWorkDay($date)) throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();

    $output = array();
    $title = '<div class="s1"><div>Архив ComNews.ru <span class="black"> / '.date('d.m.Y',$date).'</span></div></div>';
    
    $output['#title'] = $title;
    cn_setHTMLTitle($output['#title']);
    
    $news_showed = array(0);

    $body = '';
    // main news ==================================================================================================
    $mainNews = '';
    $query = \Drupal::entityQuery('node')->condition('type', 'article')->condition('status', 1)
        ->condition('field_folders', 1007)
        ->condition('field_hp_date', cn_shortDBDate($date))
        ->sort('field_date','DESC')
        ;
    $nids = $query->execute();
     
    if(count($nids)){
      $node = \Drupal\node\Entity\Node::load(current($nids));
        //$node_storage = \Drupal::entityTypeManager()->getStorage('node');
        //$nodes = $node_storage->loadMultiple($nids);
      $img = cn_renderBlockImage($node->field_image);
      $class = !empty($img)?' with-img ':'';
      $lid = cn_renderLid($node->body,200,true);
      $news_showed[] = $node->id();
      $mainNews =cn_renderHPblock('mainnews','Главная новость','/news','<a href="'.cn_getNodeAlias($node).'" class="block-node '.$class.'">'.$img.'<h4 class="node-title">'.$node->title->value.'</h4><div class="node-text">'.$lid.'</div></a>','gray-bg');  
    }
    // news comnews ==================================================================================================
    $news = '';
    $query = \Drupal::entityQuery('node')->condition('type', 'article')->condition('status', 1)
        ->condition('field_folders', 1008)
        ->condition('field_hp_date', cn_shortDBDate($date))
        ->sort('field_seq','ASC')
        ;
    $nids = $query->execute();
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nodes = $node_storage->loadMultiple($nids);
    
    foreach($nodes as $node){
      $img = '';//cn_renderBlockImage($node->field_image);
      $lid = cn_renderLid($node->body,200,true);
      $tags = cn_renderNodeTags($node);
      $news_showed[] = $node->id();
      $news .= '<div class="block-node"><a href="'.cn_getNodeAlias($node).'">'.$img.'<h4 class="node-title">'.$node->title->value.'</h4><div class="node-text">'.$lid.'</div></a>'.$tags.'</div>';  
    }
    // news digest ==================================================================================================
    $query = \Drupal::entityQuery('node')->condition('type', 'article')->condition('status', 1)
        ->condition('field_folders', 1009)
        ->condition('field_hp_date', cn_shortDBDate($date))
        ->sort('field_seq','ASC')
        ;
    $nids = $query->execute();

    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nodes = $node_storage->loadMultiple($nids);
    
    foreach($nodes as $node){
      $img = '';//cn_renderBlockImage($node->field_image);
      $lid = cn_renderLid($node->body,200,true);
      $tags = cn_renderNodeTags($node);
      $news_showed[] = $node->id();
      $news .= '<div class="block-node"><a href="'.cn_getNodeAlias($node).'">'.$img.'<h4 class="node-title">'.$node->title->value.'</h4><div class="node-text">'.$lid.'</div></a>'.$tags.'</div>';  
    }
    $news =cn_renderHPblock('news','Новости','/news',$news,'white-bg'); // news comnews + digest 
    
    // de_news ==================================================================================================
    $de_news = '';
    $query = \Drupal::entityQuery('node')->condition('type', 'article')->condition('status', 1)
        ->condition('field_folders', 1012)
        ->condition('field_hp_date', cn_shortDBDate($date))
        ->sort('field_seq','ASC')
        ;
    $nids = $query->execute();

    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nodes = $node_storage->loadMultiple($nids);
    
    foreach($nodes as $node){
      $news_showed[] = $node->id();
      $img = cn_renderBlockImage($node->field_image);
      $class = !empty($img)?' with-img ':'';
      $lid = cn_renderLid($node->body,300,true);
      $tags = cn_renderNodeDETags($node);
      $de_news .= '<div class="block-node '.$class.'"><a href="'.cn_getNodeAlias($node).'">'.$img.'<h4 class="node-title">'.$node->title->value.'</h4><div class="node-text">'.$lid.'</div></a>'.$tags.'</div>';  
    }
    $de_news =cn_renderHPblock('de-news','Цифровая экономика','/digital-economy/news',$de_news,'white-bg'); 

    // regionalnews ==================================================================================================
    $regionalnews = '';
    
      $query = \Drupal::entityQuery('node')->condition('type', 'article')->condition('status', 1)
        ->condition('field_folders', 1011)
        ->condition('field_hp_date', cn_shortDBDate($date))
        ->sort('field_date','DESC')->sort('field_seq','ASC')->sort('created','DESC');
        $nids = $query->execute();
    
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nodes = $node_storage->loadMultiple($nids);
    
    foreach($nodes as $node){
      $news_showed[] = $node->id();
      $lid = cn_renderLid($node->body,200,true);
      $tags = cn_renderNodeTags($node);
      $regionalnews .= '<div class="block-node"><a href="'.cn_getNodeAlias($node).'"><h4 class="node-title">'.$node->title->value.'</h4><div class="node-text">'.$lid.'</div></a>'.$tags.'</div>';  
    }
    $regionalnews = cn_renderHPblock('regionalnews','Региональные новости','/regions',$regionalnews,'white-bg'); 

    // solutions ==================================================================================================  
    $solutions = '';
    $query = \Drupal::entityQuery('node')->condition('type', 'pressrelease')->condition('status', 1)
        ->condition('field_folders', 1019)
        ->condition('field_hp_date', cn_shortDBDate($date))
        ->sort('field_seq','DESC')
        ;
    $nids = $query->execute();

    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nodes = $node_storage->loadMultiple($nids);
    
    foreach($nodes as $node){
      $img = cn_renderBlockImage($node->field_image);
      $class = !empty($img)?' with-img ':'';
      $lid = cn_renderLid($node->body,300);
      $person = cn_renderNodePerson($node);
      $news_showed[] = $node->id();
      $solutions .= '<div class="block-node '.$class.'"><a href="'.cn_getNodeAlias($node).'">'.$img.'<h4 class="node-title">'.$node->title->value.'</h4>'.$person.'<div class="node-text">'.$lid.'</div></a></div>';  
    }
    $solutions =cn_renderHPblock('solutions','Решения / Технологии','/solutions',$solutions,'white-bg'); 
    // COVID-19 ==================================================================================================  
    $covid19 = '';
    $query = \Drupal::entityQuery('node')->condition('type', 'pressrelease');
    if(!cn_isAdmin()) $query->condition('status', 1);
    $query->condition('field_folders', 1162)
        ->condition('nid',$news_showed,'NOT IN') 
        ->condition('field_hp_date', cn_shortDBDate($date))
        ->sort('field_date','DESC')->sort('field_seq','ASC')->sort('created','DESC')
        ;
    $nids = $query->execute();
    
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nodes1 = $node_storage->loadMultiple($nids);
    
    

    foreach($nodes1 as $node){
      $img = '';// cn_renderBlockImage($node->field_image);
      $lid = cn_renderLid($node->body,300);
      $person = '';// cn_renderNodePerson($node);
      $news_showed[] = $node->id();
      $covid19 .= '<div class="block-node"><a href="'.cn_getNodeAlias($node).'">'.$img.'<h4 class="node-title">'.$node->title->value.'</h4>'.$person.'<div class="node-text">'.$lid.'</div></a></div>';  
    }
    if(!empty($covid19)) $covid19 = '<div class="logos">Партнеры:<br/>'.cn_renderBannerSite('LOGOS','all').'</div>'.$covid19;

    $covid19 =cn_renderHPblock('covid-19','Oтрасль в ответ на COVID-19','/covid-19',$covid19,'gray-bg'); 

    // quotes ==================================================================================================
    $quotes = '';
    $query = \Drupal::entityQuery('node')->condition('type', 'quote')->condition('status', 1)
        ->condition('field_folders', 1021)
        ->condition('field_hp_date', cn_shortDBDate($date))
        ->sort('field_seq','ASC')
        ;
    $nids = $query->execute();

    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nodes = $node_storage->loadMultiple($nids);
    
    foreach($nodes as $node){
      $lid = cn_renderLid($node->body,30000);
      $news_showed[] = $node->id();
      $quotes .= '<div class="block-node"><a href="'.cn_getNodeAlias($node).'"><h4 class="node-title">'.$node->title->value.'</h4><div class="node-text">'.$lid.'</div></a></div>';  
    }
    $quotes = cn_renderHPblock('quotes','Цитата дня','/quotes',$quotes,'gray-bg'); 


    // vopros ==================================================================================================
    $vopros = '';
    $query = \Drupal::entityQuery('node')
        ->condition('type', 'vopros')
        ->condition('status', 1)
        ->condition('field_date', cn_convertDateToStorageFormat($date),'<=')
        ->condition('field_end_date', cn_shortDBDate($date),'>=')
        ->sort('field_date','DESC')->range(0,1)
        ;
      $nids = $query->execute();
       
    if(count($nids)){
      $node_storage = \Drupal::entityTypeManager()->getStorage('node');
      $nodes = $node_storage->loadMultiple($nids);
      $node = current($nodes);
      if($node->field_end_date->value >= date('Y-m-d') && $node->field_date->value <= cn_convertDateToStorageFormat('today')){
        $url = cn_getNodeAlias($node);
        $vopros .= '<div class="poll"><a href="'.cn_getNodeAlias($node).'"><h4 class="node-title">'.$node->title->value.'</h4></a><div class="v-form">
              <div class="v-graph 2019" rel="'.$node->id().'" style="margin:10px auto 40px;">
                <img src="/img/1/loader.gif" style="margin: 60px auto 0px; display:block;" class="loader"/>
              </div>
            </div></div>';  
            
      } else {
        $url = cn_getNodeAlias($node);
        $vopros .= '<div class="poll"><h4 class="node-title"><a href="'.cn_getNodeAlias($node).'">'.$node->title->value.'</a></h4><div class="v-form">
              <div class="v-graph" rel="'.$node->id().'" style="margin:10px auto 40px;">
                '.cn_getVoprosResults($node->id()).'
              </div>
            </div></div>';
      }
      $news_showed[] = $node->id();
      $vopros = cn_renderHPblock('vopros','Вопрос недели','/polls',$vopros,'gray-bg'); 
    }

    $body = '   <div id="big-calendar" rel="'.$date.'" data-d="'.date('Y-m-d',$date).'"><div class="a-c-body"></div><div class="clear"></div></div>
                <div id="arhiv-desktop"> 
                  '.$mainNews.$news.$covid19.$de_news.$regionalnews.$vopros.$quotes.'
                </div>
                <div id="hp-mobile"></div>';
    $output['#children'] = $body;
    return $output;
  
  }

  /**
  * 
  * 
  */
  public function renderPaymentForm(){
	
    $output = array();
    $title = 'Оплата услуг ComNews.ru через системы приема платежей Ассист';
    
    $output['#title'] = $title;
    cn_setHTMLTitle($output['#title']);
    
    $body = '';
  
    $ordernomber = date("Ymd-His");	
    $body .= '
  
  
  <style>
      #p1 #ass {border: none; margin-top: 0px;}
      #hdr {height:60px;padding:3px; background-color:#fff;}
      #ass {width:250px;}
      .tabl td input[type="text"], .tabl td textarea {width:100%;}
      .tabl td {padding: 2px 20px;}
      #frm{ margin:40px auto; background-color:#f0f0f0; border: solid 2px #e2e2e2; padding: 0px 0px 20px;}

  </style>

  <div id="p1" style="">
    <p>С помощью платежной системы ASSIST Вы можете оплатить все услуги и товары группы компаний ComNews.</p>
    <p>Для оплаты наших услуг Вам необходимо заполнить графы: ФИО, адрес электронной почты и номер телефона. А также Вам необходимо предварительно получить идентификатор платежа и сумму к оплате у менеджера, с которым Вы работаете (Телефоны:  в Москве (495) 933-5483/85; в Санкт-Петербурге (812) 670-2030) и добавить Ваш комментарий к платежу.</p>
    <p>Обращаем Ваше внимание на то, что адрес электронной почты и номер телефона должны быть действующими.</p>
    <div id="frm" style="">
    <div id="hdr" style="padding-left:30px;">
    <div id="ass"><img src="/img/3/assist.png" width="193" height="49" border="0" alt="Система электронных платежей"></div>
    </div>
    <table class="tabl" cellpadding="30" bgcolor="#cccccc" cellspacing="0" border="0" width="100%">
      <tr>
  <script>
    function doSubmit(){
      if (document.getElementById("pid").value == "") {
      alert("Заполните поле Идентификатор платежа");
      return false;
      }
      if (document.getElementById("sum").value == "") {
        alert("Заполните поле Сумма платежа");
      return false;
      }
      if (isNaN(parseInt(document.getElementById("sum").value))) {
        alert("Значение поля Сумма платежа должно быть целым числом");
      return false;
      }


      if (document.getElementById("ssfirstname").value == "") {
      alert("Заполните поле Имя");
      return false;
      }

      if (document.getElementById("sslastname").value == "") {
      alert("Заполните поле Фамилия");
      return false;
      }

      if (document.getElementById("ssemail").value == "") {
      alert("Заполните поле E-mail.");
      return false;
      }

      if (document.getElementById("ssphone").value == "") {
      alert("Заполните поле Телефон.");
      return false;
      }

      document.getElementById("as_lastname").value = document.getElementById("sslastname").value;
      document.getElementById("as_firstname").value = document.getElementById("ssfirstname").value;
      document.getElementById("as_pid").value = document.getElementById("pid").value;
      document.getElementById("as_sum").value = parseInt(document.getElementById("sum").value);
      document.getElementById("as_email").value = document.getElementById("ssemail").value;
      document.getElementById("as_phone").value = document.getElementById("ssphone").value;
      document.getElementById("as_orderdetail").value = document.getElementById("sscomment").value;
      document.getElementById("assistform").submit();
      document.getElementById("f").reset();
      return false;
    }
  </script>
  <FORM id="assistform" ACTION=" https://payments179.paysecure.ru/pay/order.cfm" METHOD="POST">
  <INPUT TYPE="HIDDEN" NAME="Merchant_ID" VALUE="652602">
  <INPUT TYPE="HIDDEN" id="as_pid" NAME="OrderNumber" VALUE="">
  <INPUT TYPE="HIDDEN" id="as_sum" NAME="OrderAmount" VALUE="">
  <INPUT TYPE="HIDDEN" NAME="OrderCurrency" VALUE="RUB">
  <INPUT TYPE="HIDDEN" id="as_firstname" NAME="FirstName" VALUE="">
  <INPUT TYPE="HIDDEN" id="as_lastname" NAME="LastName" VALUE="">
  <INPUT TYPE="HIDDEN" id="as_email" NAME="Email" VALUE="">
  <INPUT TYPE="HIDDEN" id="as_phone" NAME="HomePhone" VALUE="">
  <INPUT TYPE="HIDDEN" id="as_orderdetail" NAME="OrderComment" VALUE="">
  </FORM>
    <td><form id="f" method="POST" action="" onsubmit="return doSubmit();">
            Идентификатор платежа:<br>
            <input class="form-control form-input" type="text" id="pid" size="60" maxsize="70">
            <br>
            Сумма платежа:<br>
            <input class="form-control form-input" type="text" id="sum" size="60"  maxsize="10">
            <br>
        Имя:<br>
            <input class="form-control form-input" type="text" id="ssfirstname" size="60" maxsize="70">
            <br>
        Фамилия:<br>
            <input class="form-control form-input" type="text" id="sslastname" size="60" maxsize="70">
            <br>
            E-mail:<br>
            <input class="form-control form-input" type="text" id="ssemail" size="60" maxsize="70">
            <br>
        Телефон:<br>
            <input class="form-control form-input" type="text" id="ssphone" size="60" maxsize="20">
            <br>
        Комментарий:<br>
            <textarea class="form-control form-input" id="sscomment" style=""></textarea>
            <br>
            <br>
            <div style="text-align:right;">
            <input class="btn" type="submit" id="button" value="Оплатить через Ассист">
            </div>
            <br>
          </form></td>
      </tr>
    </table>
    </div>
  <span id="a_text" style="margin-top:50px;display:block;clear:both;"><p><strong>ASSIST</strong> - это мультибанковская система платежей по пластиковым и виртуальным картам через интернет, позволяющая в реальном времени производить авторизацию и обработку транcакций.</p><p><strong>ASSIST</strong> занимает лидирующее положение на российском рынке, проводя более 80% всех совершаемых в российском интернете транcакций !</p><p>В дополнение к стандартному набору карт VISA, MasterCard, ASSIST также предоставляет возможность оплаты электронной наличностью – WebMoney, Яндекс.Деньги, e-port, Kredit Pilot в рамках единого пользовательского интерфейса.</p>
  <p>Расчеты, проводимые с использованием системы ASSIST, полностью соответствуют законодательству РФ и регулируются соответствующими статьями Гражданского Кодекса Российской Федерации (ГК РФ). Платежи с использованием банковских кредитных карточек проводятся по схеме MOTO (Mail Order Telephone Order) в строгом соответствии правилам платежных систем (VISA, Europay и др.).</p> 
  <p><img hspace="3" src="/img/3/logos.png"></p></span> 
  </div>
  <div style="clear:both;"></div>
  <p>По вопросам предоставления оплаченных услуг или доставке товаров обращаться к менеджеру, с которым Вы работаете. Телефоны:  в Москве (495) 933-5483/85; в Санкт-Петербурге (812) 670-2030.</p>
  <p>По вопросу возврата денежных средств, предоставления взаимозаменяемых товаров/услуг, обмена товаров/услуг, при отказе от товара/услуги обращаться к менеджеру, с которым Вы работаете. Телефоны:  в Москве (495) 933-5483/85; в Санкт-Петербурге (812) 670-2030.</p>	
    ';
    
    $output['#children'] = $body;
    return $output;
  

  }

  public function renderPaymentFormEn(){
    
    $output = array();
    $title = 'Payment form';
    
    $output['#title'] = $title;
    cn_setHTMLTitle($output['#title']);
    
    $news_showed = array(0);

    $body = '';
  
    
    $ordernomber = date("Ymd-His");	
    $body .= '
    

  <style>
      #p1 #ass {border: none; margin-top: 0px;}
      #hdr {height:60px;padding:3px; background-color:#fff;}
      #ass {width:250px;}
      .tabl td input[type="text"], .tabl td textarea {width:100%;}
      .tabl td {padding: 2px 20px;}
      #frm{ margin:40px auto; background-color:#f0f0f0; border: solid 2px #e2e2e2; padding: 0px 0px 20px;}
  </style>

  <div id="p1" style="">
    <p>With the ASSIST payment system you can pay for all ComNews services and products. To pay for our services please fill in: Name, Surname, e-mail address and phone number. First you must obtain the payment identifier (payment ID) and the payable amount from your Manager (phones: in Moscow (495) 933-5483/85; in Saint Petersburg (812) 670-2030) and add your comment to the payment.</p>
    <p> Please note that your e-mail and phone number must be valid.</p>
    <div id="frm">
    <div id="hdr" style="padding-left:30px;">
    <div id="ass"><img src="/img/3/assist.png" width="193" height="49" border="0" alt="Система электронных платежей"></div>
    </div>
    <table class="tabl" cellpadding="30" bgcolor="#cccccc" cellspacing="0" border="0" width="100%">
      <tr>
  <script>
    function doSubmit(){
      if (document.getElementById("pid").value == "") {
      alert("Payment ID is required!");
      return false;
      }
      if (document.getElementById("sum").value == "") {
        alert("Payment ammount is required!");
      return false;
      }
      if (isNaN(parseInt(document.getElementById("sum").value))) {
        alert("Payment amount must have numeric value!");
      return false;
      }

      if (document.getElementById("ssfirstname").value == "") {
      alert("First name is required!");
      return false;
      }

      if (document.getElementById("sslastname").value == "") {
      alert("Last name is required!");
      return false;
      }

      if (document.getElementById("ssemail").value == "") {
      alert("E-mail is required!");
      return false;
      }

      if (document.getElementById("ssphone").value == "") {
      alert("Phone number is required!");
      return false;
      }

      document.getElementById("as_lastname").value = document.getElementById("sslastname").value;
      document.getElementById("as_firstname").value = document.getElementById("ssfirstname").value;
      document.getElementById("as_pid").value = document.getElementById("pid").value;
      document.getElementById("as_sum").value = parseInt(document.getElementById("sum").value);
      document.getElementById("as_email").value = document.getElementById("ssemail").value;
      document.getElementById("as_phone").value = document.getElementById("ssphone").value;
      document.getElementById("as_orderdetail").value = document.getElementById("sscomment").value;
      document.getElementById("assistform").submit();
      document.getElementById("f").reset();
      return false;
    }
  </script>
  <FORM id="assistform" ACTION=" https://payments179.paysecure.ru/pay/order.cfm" METHOD="POST">
  <INPUT TYPE="HIDDEN" NAME="Merchant_ID" VALUE="652602">
  <INPUT TYPE="HIDDEN" id="as_pid" NAME="OrderNumber" VALUE="">
  <INPUT TYPE="HIDDEN" id="as_sum" NAME="OrderAmount" VALUE="">
  <INPUT TYPE="HIDDEN" NAME="OrderCurrency" VALUE="RUB">
  <INPUT TYPE="HIDDEN" id="as_firstname" NAME="FirstName" VALUE="">
  <INPUT TYPE="HIDDEN" id="as_lastname" NAME="LastName" VALUE="">
  <INPUT TYPE="HIDDEN" id="as_email" NAME="Email" VALUE="">
  <INPUT TYPE="HIDDEN" id="as_phone" NAME="HomePhone" VALUE="">
  <INPUT TYPE="HIDDEN" id="as_orderdetail" NAME="OrderComment" VALUE="">
  <INPUT TYPE="HIDDEN" id="Language" NAME="Language" VALUE="EN">
  </FORM>
        <td><form id="f" method="POST" action="" onsubmit="return doSubmit();">
            Payment ID:<br>
            <input class="form-control form-input" type="text" id="pid" size="60" maxsize="70">
            <br>
            Payment amount:<br>
            <input class="form-control form-input" type="text" id="sum" size="60"  maxsize="10">
            <br>
        First name:<br>
            <input class="form-control form-input" type="text" id="ssfirstname" size="60" maxsize="70">
            <br>
        Last name:<br>
            <input class="form-control form-input" type="text" id="sslastname" size="60" maxsize="70">
            <br>
            E-mail:<br>
            <input class="form-control form-input" type="text" id="ssemail" size="60" maxsize="70">
            <br>
        Phone number:<br>
            <input class="form-control form-input" type="text" id="ssphone" size="60" maxsize="20">
            <br>
        Comment:<br>
            <textarea class="form-control form-input" id="sscomment"></textarea>
            <br>
            <br>
            <div style="text-align:right;">
            <input type="submit" id="button" value="Proceed payment...">
            </div>
            <br>
          </form></td>
      </tr>
    </table>
    </div>
  <span id="a_text" style="margin-top:50px;display:block;"><p><strong>ASSIST</strong> is a multibank system of payments on plastic and virtual cards via Internet that allows you authorize decisions and process transactions in real time.</p><p><strong>ASSIST</strong> takes the leading position in the Russian market, making more than 80% of all committed transactions performed in the Russian Internet!</p><p>In addition to the standard set of VISA, MasterCard, ASSIST also provides electronic cash payment option – WebMoney, Yandex.money, e-port, Kredit Pilot within a single user interface.</p>
  <p>Calculations carried out using the ASSIST system are fully complied with the legislation of the Russian Federation and are governed by the relevant articles of the Civil Code of the Russian Federation (CCRF). Payments made via Bank credit cards are made by MOTO (Mail Order Telephone Order) in strict accordance with the payment system rules (VISA, Europay etc.).</p> 
  <p><img hspace="3" src="/img/3/logos.png"></p></span> 
  </div>
  <div style="clear:both;"></div>
  <p>Concerning provision of paid services or delivery of goods, please, contact your manager. Phones: Moscow (495) 933-5483/85; in St. Petersburg (812) 670-2030. On the issue of refund, provision of interchangeable goods/services, the exchange of goods and services, the product/service cancellation, please, contact your manager. Phones: Moscow (495) 933-5483/85; in St. Petersburg (812) 670-2030.</p>	
    ';	
  
    $output['#children'] = $body;
    return $output;
  
  
  }
  /**
   * 
   * 
   * 
   */
  public function renderSubscriptionForm (){

    $issue = ''; //standart

    $output = array();
    $title = '<div class="s1"><div>Журнал Стандарт <span class="black"> / Форма подписки на журнал</span></div></div>';
    
    $output['#title'] = $title;
    cn_setHTMLTitle($output['#title']);
    
    $body = cn_renderSForm (''); // standart

    $output['#children'] = $body;
    return $output;
  } 
  /**
   * 
   * 
   * 
   */
  public function renderTop2019 (){
    //renderMain
    $output = array();
    $title = 'Топ материалов ComNews.ru за 2019 год';
    
    $output['#title'] = $title;
    cn_setHTMLTitle($output['#title']);
    
    $data = file_get_contents('/var/www/comnews2019/top2019.txt');
    $data = explode('+++',$data);

    //==== news ====
    
    $news1 = '';
    $news2 = '';
    $news3 = '';
    $news4 = '';
    
    $ii=0;
    foreach(explode('===',$data[0]) as $row){
      $item = explode(chr(10),trim($row));
      $img = '<div class="node-img" style="background-image:url('.$item[3].'); width:340px; height:262px; background-position:center center; background-repeat:no-repeat; background-size: auto 100%; overflow:hidden; float:left; margin-right: 30px;"></div>'; 
      $lid = $item[1];
      
      /*
      if($ii > 1){
        if( $ii % 2 == 0){
          $news3 .= '<div class="block-node"><a href="'.$item[2].'">'.$img.'<h4 class="node-title">'.$item[0].'</h4><div class="node-text">'.$lid.'</div></a></div>';  
        } else {
          $news4 .= '<div class="block-node"><a href="'.$item[2].'">'.$img.'<h4 class="node-title">'.$item[0].'</h4><div class="node-text">'.$lid.'</div></a></div>';  
        }
      } else {
        if( $ii % 2 == 0){
          $news1 .= '<div class="block-node"><a href="'.$item[2].'">'.$img.'<h4 class="node-title">'.$item[0].'</h4><div class="node-text">'.$lid.'</div></a></div>';  
        } else {
          $news2 .= '<div class="block-node"><a href="'.$item[2].'">'.$img.'<h4 class="node-title">'.$item[0].'</h4><div class="node-text">'.$lid.'</div></a></div>';  
        }
      }
      */
      if($ii > 3){
        $news2 .= '<div class="block-node" style="border-bottom:none;"><a href="'.$item[2].'">'.$img.'<h4 class="node-title" style="clear:none;">'.$item[0].'</h4><div class="node-text">'.$lid.'</div><div class="clear"></div></a></div>';  
        
      } else {
        $news1 .= '<div class="block-node" style="border-bottom:none;"><a href="'.$item[2].'">'.$img.'<h4 class="node-title" style="clear:none;">'.$item[0].'</h4><div class="node-text">'.$lid.'</div><div class="clear"></div></a></div>';  
        
      }
      

      $ii++;
    }
    $news1 =cn_renderHPblock('news1','Топ новостей 2019','/news',$news1,'white-bg');  
    $news2 =cn_renderHPblock('news2','','/news',$news2,'white-bg');  
    $news3 =cn_renderHPblock('news3','','/news',$news3,'white-bg');  
    $news4 =cn_renderHPblock('news4','','/news',$news4,'white-bg');  
    
    $de_news = '';
    foreach(explode('===',$data[4]) as $row){
      $item = explode(chr(10),trim($row));
      $img = '<div class="node-img" style="background-image:url('.$item[3].'); width:340px; height:262px; background-position:center center; background-repeat:no-repeat; background-size: auto 100%; overflow:hidden; float:left; margin-right:30px;"></div>'; 
      $lid = $item[1];
      $de_news .= '<div class="block-node" style="border-bottom:none;"><a href="'.$item[2].'">'.$img.'<h4 class="node-title" style="clear:none;">'.$item[0].'</h4><div class="node-text">'.$lid.'</div><div class="clear"></div></a></div>';  
      
    }
    $de_news =cn_renderHPblock('de_news','Топ новостей Цифровой экономики','/digital-economy/news',$de_news,'white-bg');  


    $editorials = '';
    foreach(explode('===',$data[1]) as $row){
      $item = explode(chr(10),trim($row));

      $img = $img = '<div class="node-img"><img src="'.$item[4].'"/></div>'; ;
      $lid = $item[2];
      $pers = explode(',',$item[1]);
      $person = '<div class="node-person"><span>'.$pers[0].'</span><br> '.$pers[1].'</div>';
      $editorials .= '<div class="block-node person" style="border-bottom:none;"><a href="'.$item[3].'">'.$img.$person.'<h4 class="node-title">'.$item[0].'</h4><div class="node-text">'.$lid.'</div></a></div>';  
    }
    $editorials =cn_renderHPblock('editorials','Топ редколонок','/editorials',$editorials,'gray-bg'); 

    $points = '';
    foreach(explode('===',$data[2]) as $row){
      $item = explode(chr(10),trim($row));

      $img = $img = '<div class="node-img"><img src="'.$item[4].'"/></div>'; ;
      $lid = $item[2];
      $pers = explode(',',$item[1]);
      $person = '<div class="node-person"><span>'.$pers[0].'</span><br> '.$pers[1].'</div>';
      $points .= '<div class="block-node person" style="border-bottom:none;"><a href="'.$item[3].'">'.$img.$person.'<h4 class="node-title">'.$item[0].'</h4><div class="node-text">'.$lid.'</div></a></div>';  
    }
    $points =cn_renderHPblock('pointofview','Топ точек зрения','/point-of-view',$points,'gray-bg');

    $de_opinions = '';
    foreach(explode('===',$data[3]) as $row){
      $item = explode(chr(10),trim($row));

      $img = $img = '<div class="node-img"><img src="'.$item[4].'"/></div>'; ;
      $lid = $item[2];
      $pers = explode(',',$item[1]);
      $person = '<div class="node-person"><span>'.$pers[0].'</span><br> '.$pers[1].'</div>';
      $de_opinions .= '<div class="block-node person" style="border-bottom:none;"><a href="'.$item[3].'">'.$img.$person.'<h4 class="node-title">'.$item[0].'</h4><div class="node-text">'.$lid.'</div></a></div>';  
    }
    $de_opinions =cn_renderHPblock('de_opinions','Топ Мнений','/digital-economy/opinions',$de_opinions,'gray-bg');

    // pressreleases ==================================================================================================
    $pressreleases = '';
    $query = \Drupal::entityQuery('node')->condition('type', 'pressrelease')->condition('status', 1)
        ->condition('field_folders', 1018)
        ->sort('field_date','DESC')->sort('field_seq','DESC')->sort('created','DESC')->range(0,7);
        
    $nids = $query->execute();
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nodes = $node_storage->loadMultiple($nids);
    
    foreach($nodes as $node){
      $pressreleases .= '<div class="block-node small"><a href="'.cn_getNodeAlias($node).'"><h4 class="node-title">'.$node->title->value.'</h4></a></div>';  
    }
    $pressreleases = cn_renderHPblock('pressreleases','Новости компаний','/pressreleases',$pressreleases,'white-bg'); 

    // vopros ==================================================================================================
    $vopros = '';
    
    $node = \Drupal\node\Entity\Node::load(203775);
      if($node->field_end_date->value >= date('Y-m-d') && $node->field_date->value <= cn_convertDateToStorageFormat('today')){
        $url = cn_getNodeAlias($node);
        $vopros .= '<div class="poll"><a href="'.cn_getNodeAlias($node).'"><h4 class="node-title">'.$node->title->value.'</h4></a><div class="v-form">
              <div class="v-graph 2019" rel="'.$node->id().'" style="margin:10px auto 40px;">
                <img src="/img/1/loader.gif" style="margin: 60px auto 0px; display:block;" class="loader"/>
              </div>
            </div></div>';  
            
      } else {
        $url = cn_getNodeAlias($node);
        $vopros .= '<div class="poll"><h4 class="node-title"><a href="'.cn_getNodeAlias($node).'">'.$node->title->value.'</a></h4><div class="v-form">
              <div class="v-graph" rel="'.$node->id().'" style="margin:10px auto 40px;">
                '.cn_getVoprosResults($node->id()).'
              </div>
            </div></div>';
      }
      $vopros = cn_renderHPblock('vopros','Вопрос года','/polls',$vopros,'gray-bg'); 
    

    // exhibitions ==================================================================================================
    $exhibitions = '';
    
      $query = \Drupal::entityQuery('node')->condition('type', 'event')->condition('status', 1);
      if(date('Ymd') < '20200107'){
        $query->condition('field_start_date', cn_shortDBDate('2020-01-01'),'>=');
        $query->condition('field_start_date', cn_shortDBDate('2020-01-20'),'<=');
      } else {  
        $query->condition('field_start_date', cn_shortDBDate(),'>=');
        $query->condition('field_start_date', cn_shortDBDate('today + 1 week'),'<=');
      }  
      $query->sort('field_start_date','ASC')->sort('created','DESC');
        $nids = $query->execute();
     

    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nodes = $node_storage->loadMultiple($nids);
    
    foreach($nodes as $node){
      $exhibitions .= '<div class="block-node"><a href="/exhibition/'.$node->id().'"><div class="node-dates"><span>'.date('d.m.Y',$node->field_start_date->date->getTimestamp()).'</span><span>'.trim(count($node->field_end_date->getValue())?date('d.m.Y',$node->field_end_date->date->getTimestamp()):'').'</span></div><h4 class="node-title">'.$node->title->value.'</h4></a></div>';  
    }
    $exhibitions = cn_renderHPblock('exhibitions','Конференции / Выставки','/exhibitions',$exhibitions,'white-bg'); 

    // birthdays ==================================================================================================
    $birthdays = cn_getTodayBR();
    if ($birthdays != '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><div></div>') 
      $birthdays = cn_renderHPblock('birthdays','Дни рождения','https://whoiswho.comnews.ru/birthdays',$birthdays,'gray-bg'); 
    else $birthdays= cn_renderBannerSite('bnDR01',0);
    
    $lastcomments = cn_renderHPblock('last-comments','Сейчас обсуждают','','<div style="height:650px;" data-qnt="7"><img src="/img/1/loader.gif" style="margin: 60px auto 0px; display:block;" class="loader"/></div>','gray-bg last-comments');

    //ksm(count($data));

    $body = '   <div class="page-top2019"><div id="hp-desktop" class="desktop"> 
                  <div class="primary-row">
                    '.cn_renderBannerSite('bn0006',0).'
                    <div class="section-1">
                      '.$news1.' 
                      <div class="r1"></div>
                      <div class="r2"></div>
                    </div>
                    '.cn_renderBannerSite('bn0018',0).'
                    <div class="section-2">
                      '.$vopros.'
                    </div>
                    '.cn_renderBannerSite('bn0019',0).'
                    <div class="section-3">
                      '.$news2.'
                      <div class="r1"></div>
                      <div class="r2"></div>
                    </div>
                    '.cn_renderBannerSite('bn0020',0).'
                    <div class="section-4">
                      '.$de_news.'
                    </div>  
                    '.cn_renderBannerSite('bn0014',0).'
                  </div>
                  <div class="side-row"><p style="text-align:center;"><img src="/themes/site/images/ny2019-d.jpg" class="desktop"/></p>'.cn_renderBannerSite('bn0010',0).$editorials.cn_renderBannerSite('bn0023',0).$points.cn_renderBannerSite('bn0024',0).$de_opinions.cn_renderBannerSite('bn0025',0).$exhibitions.cn_renderBannerSite('bn0035',0).'<br>'.$birthdays.cn_renderBannerSite('bn0037',0).$lastcomments.cn_renderBannerSite('bn0022',0).$pressreleases.'<p style="text-align:center;"><a href="https://www.comnews-conferences.ru/ru/conference/tn2020"><img src="/sites/default/files2019/bn-images/300x500_TN2020.gif"/></a></p></div>
                </div>
                <div id="hp1-mobile" class="mobile"><p style="text-align:center;"><img src="/themes/site/images/ny2019-m.jpg" class="mobile"/></p>'.cn_renderBannerSite('bnM0004',0).$news1.$vopros.$news2.$de_news.$editorials.$points.$de_opinions.$lastcomments.'</div>
                <style>
                .page-header { display:none; }
                .slick-dots .slick-active button { background-color:#000!important;}
                </style></div>';
    $output['#children'] = $body;
    return $output;
  }  
/**
   * 
   * 
   * 
   */
  public function renderStandartFreePDF (){

    $issue = ''; //standart


    if(isset($_REQUEST['m']) && $_REQUEST['m'] == 'pdf' && isset($_REQUEST['guid'])){
      $arrContextOptions=array(
        "ssl"=>array(
            "verify_peer"=>false,
            "verify_peer_name"=>false,
        ),
      ); 
      $issue_id = intval(trim(file_get_contents('https://www.comnews-conferences.ru/getissue.php?m=cover&guid='.$_REQUEST['guid'], false, stream_context_create($arrContextOptions))));

      if($issue_id){
          $node = \Drupal\node\Entity\Node::load($issue_id);
          //var_dump($node->id());
          if(!$node->id() || !$node->isPublished() || ($node->field_free_pdf->value == '' && count($node->get('field_issue_pdf'))==0)){
               
              //404
              throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
          }
      } else {
        //404
        
        throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
        
      }
      $pdflink = '/getissue.php?m=pdf&guid='.$_REQUEST['guid'];
      $cover = '/getissue.php?m=cover&guid='.$_REQUEST['guid'];
    } else if(isset($_REQUEST['m']) && $_REQUEST['m'] == 'preview-pdf' && isset($_REQUEST['id'])){
      $issue_id = intval(trim($_REQUEST['id']));
      
      if($issue_id){
          $node = \Drupal\node\Entity\Node::load($issue_id);
          if(!$node->id() || !$node->isPublished() || ($node->field_free_pdf->value == '' && count($node->get('field_issue_pdf'))==0)){
            //404
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
          }
      } else {
        //404
        throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
      }
      $pdflink = '/getissue.php?m=preview-pdf&id='.$_REQUEST['id'];
      $cover = '/getissue.php?m=preview-cover&id='.$_REQUEST['id'];
    } else {
      //404
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }


    $output = array();
    $title = '<div class="s1"><div>Журнал Стандарт "'.$node->title->value.'"<span class="black"> / Скачать в PDF</span></div></div>';
    
    $output['#title'] = $title;
    cn_setHTMLTitle($output['#title']);
    
    
    $body = '<div id="page-standart" class="download-free-pdf">
    <div class="node-standart">
    <h2>Уважаемые читатели!</h2>
    <p>Мы с удовольствием предоставляем Вам возможность скачать электронную версию издания журнала СТАНДАРТ '.$node->title->value.'.</p>
    <p></p>
    <a target="_blank" href="'.$pdflink.'" class="download"><img style="max-width:100%" src="'.$cover.'"/> <div>Скачать бесплатно</div></a>
    <div class="raspr">
    <div>по вопросам размещения рекламно–информационных материалов в проектах ГК ComNews, просьба обращаться к<br/><strong>Сергею Болдыреву</strong> <a href="mailto:sr@comnews.ru">sr@comnews.ru</a>
    </div>
    <div>по вопросам подписки и приобретения отдельных выпусков издания и аналитических карт просьба обращаться к<br/> 
    <strong>Татьяне Ромо Маурейра</strong> <a href="mailto:office@comnews.ru">office@comnews.ru</a>
    </div>
    <br><br><br>
    <img style="max-width:100%;display:block; margin-top:50px;" src="/themes/site/images/str1.jpg"/>
    <br>
    <img style="max-width:100%;display:block; margin-top:20px;"  src="/themes/site/images/str3.jpg"/>
    </div>


    </div>
    <div class="sidebar">
      
      '.cn_renderStandartLinksBlock().'
      '.cn_renderBannerSite('bnStandart1',0).'
      <div class="gray-bg">'.cn_renderStandartContacts().'</div>
    </div>
    </div>
    ';



    $output['#children'] = $body;
    return $output;
  }    
/**
   * 
   * 
   * 
   */
  public function renderStandartPosters (){

    $issue = ''; //standart

    $output = array();
    $title = '<div class="s1"><div>Журнал Стандарт <span class="black"> / Архив аналитических карт</span></div></div>';
    
    $output['#title'] = $title;
    cn_setHTMLTitle($output['#title']);
    
    $query = \Drupal::entityQuery('node')->condition('type', 'standart')->condition('status', 1)
        ->sort('field_seq','DESC')
        ;
    $nids = $query->execute();
     
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nodes = $node_storage->loadMultiple($nids);
    
    $maps = '';
    
    
    foreach($nodes as $node){
      $m = 0;$pdfs = $node->get('field_map_pdfs');
      foreach($node->get('field_map_imgs') as $img){
        if($img->entity){
          $maps .='<div class="map">
            <h3>'.$img->alt.'</h3>
            <a class="img" href="'.cn_getImgStyleUrl('original',$img->entity->getFileUri()).'" rel="lightbox[img]['.htmlspecialchars($img->alt).']"><img src="'.cn_getImgStyleUrl('large',$img->entity->getFileUri()).'"/></a><p>'.$img->title.'<div style="text-align:right;"><a style="display: inline-block; width: 200px; text-align:center; height: 30px; line-height: 30px;   border-radius: 5px; background-color: #be0027; color: #fff; font-size: 20px; margin: 5px 5px; text-decoration:none!important;" href="'.$pdfs[$m]->entity->createFileUrl().'" target="_blank">Скачать PDF</a></div></p>
          </div>';
        }
        $m++;
      }
    }
    
    $body = '<div id="page-standart" class="download-free-pdf">
    <div class="node-standart">
    
    <div class="node-maps">
    <div class="map">
            <h3>Охват территории РФ сетями спутникового ТВ</h3>
            <a class="img" href="/sites/default/files2019/reviews/2020-08/43545353.jpg" rel="lightbox[img][Охват территории РФ сетями спутникового ТВ]"><img src="/sites/default/files2019/reviews/2020-08/43545353.jpg"></a><p></p><div style="text-align:right;"><a style="display: inline-block; width: 200px; text-align:center; height: 30px; line-height: 30px;   border-radius: 5px; background-color: #be0027; color: #fff; font-size: 20px; margin: 5px 5px; text-decoration:none!important;" href="/sites/default/files2019/vision-files/mapsattv2020-6.pdf" target="_blank">Скачать PDF</a></div><p></p>
          </div>
    '.$maps.'
    </div>
    </div>
    <div class="sidebar">
      
      '.cn_renderStandartLinksBlock().'
      '.cn_renderBannerSite('bnStandart1',0).'
      <div class="gray-bg">'.cn_renderStandartContacts().'</div>
    </div>
    </div>
    ';



    $output['#children'] = $body;
    return $output;
  }      
/**
   * 
   * 
   * 
   */
  public function renderStandartPDFForm (){

    $issue = ''; //standart

    $output = array();
    $title = '<div class="s1"><div>Журнал Стандарт <span class="black"> / Скачать в PDF</span></div></div>';
    
    $output['#title'] = $title;
    cn_setHTMLTitle($output['#title']);
    
    $query = \Drupal::entityQuery('node')->condition('type', 'standart')->condition('status', 1)
        ->sort('field_seq','DESC')
        ;
    $nids = $query->execute();
     
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nodes = $node_storage->loadMultiple($nids);

    $select = '<label>Выберите номер журнала</label> <select id="issue" class="form-control form-select">';
    $cover = '';
    foreach($nodes as $node){
      $variants = array();
      foreach($node->field_issues->getValue() as $i => $item){
        
        $issue = explode('|',$item['value']);
        if(intval(cn_getVal($issue[3]) > 0)){
          $variants[] = cn_t($issue[0]).'|'.$i;
        }
      }
      if(count($variants)){
        $img = trim($node->field_cover_old->value == ''?cn_getImgUrl('standart_big',$node->field_cover):$node->field_cover_old->value);
        if(empty($cover)) $cover = $img;
        $select .= '<option value="'.$node->id().'" data-cover="'.$img.'" data-variants="'.implode(',',$variants).'">'.$node->title->value.'</option>';
      }  
    }
    $select .= '</select> <label>Выберите состав пакета</label> <select id="variant" class="form-control form-select"></select>';
    
    $body = '<div id="page-standart" class="download-pdf">
    <div class="node-standart">
    <h3>Уважаемые читатели!</h3>
    <p>Мы с удовольствием предоставляем Вам возможность приобрести электронную версию издания журнала СТАНДАРТ.</p>
    <p>Для получения доступа к PDF-версии введите пароль, отправленный Вам на электронную почту, и нажмите кнопку «получить ссылку». После чего у Вас появится возможность скачать электронную версию издания.</p>
    <p>Желаем Вам приятного чтения!!!</p>
    <div class="pdf-form">
    '.$select.'
    <div class="pwd"><input autocomplete="new-password" class="form-control form-input" type="password" placeholder="Укажите пароль" value="" id="pswd"/> <button id="btn" class="btn">Получить ссылку</button></div>
    <div id="msg-box"></div>
    </div>
    <h3>О правах на результаты интеллектуальной деятельности</h3>
    <p>Исключительное право на все материалы журнала «Стандарт», за исключением материалов, сопровождаемых указанием иного правообладателя, принадлежит ООО «КомНьюс Груп».</p>
    <p>Использование материалов любыми способами, предусмотренными законодательством РФ и международными актами об интеллектуальной собственности, включая воспроизведение, распространение, переработку и доведение до всеобщего сведения, допускается только с письменного разрешения ООО «КомНьюс Груп».</p>
    </div>
    <div class="sidebar">
      
      '.cn_renderStandartLinksBlock().'<div class="cover"><img id="cover" src="'.$cover.'"/></div>
      '.cn_renderBannerSite('bnStandart1',0).'
      <div class="gray-bg">'.cn_renderStandartContacts().'</div>
    </div>
    </div>
    ';



    $output['#children'] = $body;
    return $output;
  }  
  /**
   * 
   * 
   * 
   */
  public function renderStandartBuyPDFForm (){

    $ordernomber = "ST-PDF-".date("Ymd-His");	

    $output = array();
    $title = '<div class="s1"><div>Журнал Стандарт <span class="black"> / Купить в PDF</span></div></div>';
    
    $output['#title'] = $title;
    cn_setHTMLTitle($output['#title']);
    
    $query = \Drupal::entityQuery('node')->condition('type', 'standart')->condition('status', 1)
        ->sort('field_seq','DESC')
        ;
    $nids = $query->execute();
     
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nodes = $node_storage->loadMultiple($nids);

    $select = '<label>Выберите номер журнала</label> <select id="ssissue" class="form-control form-select">';
    $cover = '';
    foreach($nodes as $node){
      $variants = array();
      foreach($node->field_issues->getValue() as $i => $item){
        
        $issue = explode('|',$item['value']);
        if(intval(cn_getVal($issue[3]) > 0)){
          $variants[] = cn_t($issue[0]).'|'.$issue[3];
        }
      }
      if(count($variants)){
        $img = trim($node->field_cover_old->value == ''?cn_getImgUrl('standart_big',$node->field_cover):$node->field_cover_old->value);
        if(empty($cover)) $cover = $img;
        $select .= '<option value="'.str_replace('№','N',$node->title->value).'" data-cover="'.$img.'" data-variants="'.implode(',',$variants).'">'.$node->title->value.'</option>';
      }  
    }
    $select .= '</select> <label>Выберите состав пакета</label> <select id="ssvariant" class="form-control form-select"></select>';
    
    $body = '<div id="page-standart" class="buy-pdf">
    <div class="node-standart">

<h3><strong>Здесь Вы можете купить:</strong></h3>
<ul>
<li>Электронную версию журнала Стандарт</li>
<li>Электронную версию Карты (специальное ежемесячное приложение к журналу)</li>
<li>Электронную версию журнала Стандарт вместе с Картой</li>
</ul>
<p>Нужный вариант издания необходимо выбрать из списка в поле «Номер журнала».</p>
<h3><strong>Для юридических лиц</strong></h3>
<p>Для приобретения электронной версии журнала Вам необходимо отправить реквизиты Вашей компании на адрес электронной почты <a href="mailto:sr@comnews.ru">sr@comnews.ru</a> или <a href="mailto:office@comnews.ru">office@comnews.ru</a> , а в теме письма указать: &quot;Электронная версия журнала СТАНДАРТ&quot;.</p>
  <h3><strong>Для физических лиц</strong></h3>
  <p>Для приобретения электронной версии журнала Вам необходимо заполнить графы: ФИО, адрес электронной почты и номер телефона.</p>
  <p>Обращаем Ваше внимание на то, что адрес электронной почты должен быть действующим, т.к. на него Вам будут отправлены ссылка и пароль для скачивания электронной версии издания в формате PDF.</p>
  <div class="buy-form">
    <div id="hdr" style="padding-left:30px;">
      <img src="/img/3/assist.png" width="193" height="49" vspace="5" border="0" alt="Система электронных платежей"> 
    </div>
   
    
      <script>
      function doSubmit(){
        if (document.getElementById("ssfirstname").value == "") {
          alert("Заполните поле Имя");
          return false;
        }

        if (document.getElementById("sslastname").value == "") {
          alert("Заполните поле Фамилия");
          return false;
        }

        if (document.getElementById("ssemail").value == "") {
          alert("Заполните поле E-mail.");
          return false;
        }
        if (document.getElementById("ssphone").value == "") {
          alert("Заполните поле Телефон.");
          return false;
        }

        document.getElementById("as_lastname").value = document.getElementById("sslastname").value;
        document.getElementById("as_firstname").value = document.getElementById("ssfirstname").value;
        document.getElementById("as_email").value = document.getElementById("ssemail").value;
        document.getElementById("as_phone").value = document.getElementById("ssphone").value;
        document.getElementById("as_price").value = document.getElementById("ssprice").value;
        document.getElementById("as_orderdetail").value = "ФИО: " + document.getElementById("ssfirstname").value + " " + document.getElementById("sslastname").value + "; E-mail:"+ document.getElementById("ssemail").value + "; Телефон: " + document.getElementById("ssphone").value + "; номер журнала: " + jQuery("#ssissue").val()+" ( "+jQuery("#ssvariant").val()+" ); цена: "+document.getElementById("ssprice").value;
        document.getElementById("assist").submit();
        return false;
        document.getElementById("f").reset();
      }
</script>
<FORM id="assist" ACTION=" https://payments179.paysecure.ru/pay/order.cfm" METHOD="POST">
<INPUT TYPE="HIDDEN" NAME="Merchant_ID" VALUE="652602">
<INPUT TYPE="HIDDEN" NAME="OrderNumber" VALUE="'.$ordernomber.'">
<INPUT TYPE="HIDDEN" id="as_price" NAME="OrderAmount" VALUE="125">
<INPUT TYPE="HIDDEN" NAME="OrderCurrency" VALUE="RUB">
<INPUT TYPE="HIDDEN" id="as_firstname" NAME="FirstName" VALUE="">
<INPUT TYPE="HIDDEN" id="as_lastname" NAME="LastName" VALUE="">
<INPUT TYPE="HIDDEN" id="as_email" NAME="Email" VALUE="">
<INPUT TYPE="HIDDEN" id="as_phone" NAME="HomePhone" VALUE="">
<INPUT TYPE="HIDDEN" id="as_orderdetail" NAME="OrderComment" VALUE="">
</FORM>

      <form id="f" method="POST" action="" onsubmit="return doSubmit();">
          
        '.$select.'
          <label>Цена: <span id="sstxtprice"></span></label>
          <input type="hidden" id="ssprice" value=""/>
          <label>Имя:</label>
          <input class="form-control form-input" type="text" id="ssfirstname" size="60" maxsize="70">
          <label>Фамилия:</label>
          <input  class="form-control form-input"  type="text" id="sslastname" size="60" maxsize="70">
          <label>E-mail:</label>
          <input  class="form-control form-input"  type="email" id="ssemail" size="60" maxsize="128">
          <label>Телефон:</label>
          <input  class="form-control form-input"  type="text" id="ssphone" size="60" maxsize="20">
          <div style="text-align:right; margin:40px 0px;">
          <input class="btn" type="submit" id="button" value="Оплатить через Ассист">
          </div>
        </form>
  </div>      
  
  <span id="y_text" style="display:none;margin-top:50px;"><span id="internal-source-marker_0.3001988610728249" style="background-color: transparent; font-variant: normal; font-style: normal; font-family: Calibri; color: #000000; font-size: 11pt; vertical-align: baseline; font-weight: bold; text-decoration: none;">Яндекс.Деньги</span><span style="background-color: transparent; font-variant: normal; font-style: normal; font-family: Calibri; color: #000000; font-size: 11pt; vertical-align: baseline; font-weight: normal; text-decoration: none;"> &ndash; доступный и безопасный способ платить за товары и услуги через интернет. Заполнив форму оплаты&nbsp;на нашем сайте, Вы будете перенаправлены на сайт Яндекс.Деньги, где сможете завершить платеж. Если у вас нет счета в Яндекс.Деньгах, его нужно открыть на </span><a href="https://money.yandex.ru/"><span style="background-color: transparent; font-variant: normal; font-style: normal; font-family: Calibri; color: #0000ff; font-size: 11pt; vertical-align: baseline; font-weight: normal; text-decoration: underline;">сайте платежной системы</span></a><span style="background-color: transparent; font-variant: normal; font-style: normal; font-family: Calibri; color: #000000; font-size: 11pt; vertical-align: baseline; font-weight: normal; text-decoration: none;">. </span>
  </span>
 <span id="a_text" style="margin-top:50px;display:block;"><p><strong>ASSIST</strong> - это мультибанковская система платежей по пластиковым и виртуальным картам через интернет, позволяющая в реальном времени производить авторизацию и обработку транcакций.</p><p><strong>ASSIST</strong> занимает лидирующее положение на российском рынке, проводя более 80% всех совершаемых в российском интернете транcакций !</p><p>В дополнение к стандартному набору карт VISA, MasterCard, ASSIST также предоставляет возможность оплаты электронной наличностью – WebMoney, Яндекс.Деньги, e-port, Kredit Pilot в рамках единого пользовательского интерфейса.</p>
<p>Расчеты, проводимые с использованием системы ASSIST, полностью соответствуют законодательству РФ и регулируются соответствующими статьями Гражданского Кодекса Российской Федерации (ГК РФ). Платежи с использованием банковских кредитных карточек проводятся по схеме MOTO (Mail Order Telephone Order) в строгом соответствии правилам платежных систем (VISA, Europay и др.).</p> 
<p><img hspace="3" src="/img/3/logos.png"></p></span> 

<br><br>
<p>По вопросам предоставления оплаченных услуг или доставке товаров обращаться к менеджеру, с которым Вы работаете. Телефоны:  в Москве (495) 933-5483/85; в Санкт-Петербурге (812) 670-2030.</p>
<p>По вопросу возврата денежных средств, предоставления взаимозаменяемых товаров/услуг, обмена товаров/услуг, при отказе от товара/услуги обращаться к менеджеру, с которым Вы работаете. Телефоны:  в Москве (495) 933-5483/85; в Санкт-Петербурге (812) 670-2030.</p>	
    </div>
    <div class="sidebar">
      '.cn_renderStandartLinksBlock().'<div class="cover"><img id="cover" src="'.$cover.'"/></div>
      '.cn_renderBannerSite('bnStandart1',0).'
      
      <div class="gray-bg">'.cn_renderStandartContacts().'</div>
    </div>
    </div>
    ';



    $output['#children'] = $body;
    return $output;
  }  

  /**
   * 
   * 
   * 
   */
  public function renderStandartBuyPaperForm (){

    $ordernomber = "ST-PAPER-".date("Ymd-His");	

    $output = array();
    $title = '<div class="s1"><div>Журнал Стандарт <span class="black"> / Купить печатную версию</span></div></div>';
    
    $output['#title'] = $title;
    cn_setHTMLTitle($output['#title']);
    
    $query = \Drupal::entityQuery('node')->condition('type', 'standart')->condition('status', 1)
        ->sort('field_seq','DESC')
        ;
    $nids = $query->execute();
     
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nodes = $node_storage->loadMultiple($nids);

    $select = '<label>Выберите номер журнала</label> <select id="ssissue" class="form-control form-select">';
    $cover = '';
    foreach($nodes as $node){
      $variants = array();
      foreach($node->field_issues->getValue() as $i => $item){
        
        $issue = explode('|',$item['value']);
        if(intval(cn_getVal($issue[4]) > 0)){
          $variants[] = cn_t($issue[0]).'|'.$issue[4];
        }
      }
      if(count($variants)){
        $img = trim($node->field_cover_old->value == ''?cn_getImgUrl('standart_big',$node->field_cover):$node->field_cover_old->value);
        if(empty($cover)) $cover = $img;
        $select .= '<option value="'.str_replace('№','N',$node->title->value).'" data-cover="'.$img.'" data-variants="'.implode(',',$variants).'">'.$node->title->value.'</option>';
      }  
    }
    $select .= '</select> <label>Выберите состав пакета</label> <select id="ssvariant" class="form-control form-select"></select>';
    
    $body = '<div id="page-standart" class="buy-pdf">
    <div class="node-standart">

<h3><strong>Здесь Вы можете купить:</strong></h3>
<ul>
<li>Печатные выпуски журнала Стандарт с приложением (Картой)</li>
<li>Печатную версию Карты (специальное ежемесячное приложение к журналу)</li>
</ul>
<p>Нужный вариант издания необходимо выбрать из списка в поле «Номер журнала».</p>
<h3><strong>Для юридических лиц</strong></h3>
<p>Для приобретения печатной версии журнала Вам необходимо отправить реквизиты Вашей компании на адрес электронной почты <a href="mailto:sr@comnews.ru">sr@comnews.ru</a> или <a href="mailto:office@comnews.ru">office@comnews.ru</a> , а в теме письма указать: &quot;Печатная версия журнала СТАНДАРТ&quot;.</p>
  <h3><strong>Для физических лиц</strong></h3>
  <p>Для приобретения печатной версии журнала Вам необходимо заполнить графы: ФИО, адрес электронной почты и номер телефона.</p>
  <p>Обращаем Ваше внимание на то, что адрес электронной почты должен быть действующим.</p>
  <div class="buy-form">
    <div id="hdr" style="padding-left:30px;">
      <img src="/img/3/assist.png" width="193" height="49" vspace="5" border="0" alt="Система электронных платежей"> 
    </div>
   
    
      <script>
      function doSubmit(){
        if (document.getElementById("ssfirstname").value == "") {
          alert("Заполните поле Имя");
          return false;
        }

        if (document.getElementById("sslastname").value == "") {
          alert("Заполните поле Фамилия");
          return false;
        }

        if (document.getElementById("ssemail").value == "") {
          alert("Заполните поле E-mail.");
          return false;
        }
        if (document.getElementById("ssphone").value == "") {
          alert("Заполните поле Телефон.");
          return false;
        }
        if(jQuery("#ssdelivery").val() != 0){
          if (document.getElementById("sszip").value == "") {
            alert("Заполните поле Почтовый индекс.");
            return false;
          }
          if (document.getElementById("sscity").value == "") {
            alert("Заполните поле город.");
            return false;
          }
          if (document.getElementById("ssaddress").value == "") {
            alert("Заполните поле Адрес.");
            return false;
          }
        }	

        document.getElementById("as_lastname").value = document.getElementById("sslastname").value;
        document.getElementById("as_firstname").value = document.getElementById("ssfirstname").value;
        document.getElementById("as_email").value = document.getElementById("ssemail").value;
        document.getElementById("as_phone").value = document.getElementById("ssphone").value;

        document.getElementById("as_address").value = document.getElementById("ssaddress").value;
        document.getElementById("as_country").value = document.getElementById("sscountry").value;
        document.getElementById("as_city").value = document.getElementById("sscity").value;
        document.getElementById("as_zip").value = document.getElementById("sszip").value;

        document.getElementById("as_price").value = parseInt(document.getElementById("ssprice").value,10)+parseInt(jQuery("#ssdelivery").val(),10);

        let delivery = "Самовывоз";
        if(jQuery("#ssdelivery").val() == 300) delivery = "Доставка по Москве/СПБ";
        if(jQuery("#ssdelivery").val() == 550) delivery = "Доставка по России";

        document.getElementById("as_orderdetail").value = "ФИО: " + document.getElementById("ssfirstname").value + " " + document.getElementById("sslastname").value + "; E-mail:"+ document.getElementById("ssemail").value + "; Телефон: " + document.getElementById("ssphone").value + "; номер журнала: " + jQuery("#ssissue").val()+" ( "+jQuery("#ssvariant").val()+" ); цена: "+document.getElementById("ssprice").value + "; Способ доставки: " + delivery + "; Стоимость доставки: " + jQuery("#ssdelivery").val();

        document.getElementById("assist").submit();
        return false;
        document.getElementById("f").reset();
      }
</script>
<FORM id="assist" ACTION=" https://payments179.paysecure.ru/pay/order.cfm" METHOD="POST">
<INPUT TYPE="HIDDEN" NAME="Merchant_ID" VALUE="652602">
<INPUT TYPE="HIDDEN" NAME="OrderNumber" VALUE="'.$ordernomber.'">
<INPUT TYPE="HIDDEN" id="as_price" NAME="OrderAmount" VALUE="125">
<INPUT TYPE="HIDDEN" NAME="OrderCurrency" VALUE="RUB">
<INPUT TYPE="HIDDEN" id="as_firstname" NAME="FirstName" VALUE="">
<INPUT TYPE="HIDDEN" id="as_lastname" NAME="LastName" VALUE="">
<INPUT TYPE="HIDDEN" id="as_email" NAME="Email" VALUE="">
<INPUT TYPE="HIDDEN" id="as_phone" NAME="HomePhone" VALUE="">

<INPUT TYPE="HIDDEN" id="as_zip" NAME="Zip" VALUE="">
<INPUT TYPE="HIDDEN" id="as_country" NAME="Country" VALUE="">
<INPUT TYPE="HIDDEN" id="as_city" NAME="City" VALUE="">
<INPUT TYPE="HIDDEN" id="as_address" NAME="Address" VALUE="">

<INPUT TYPE="HIDDEN" id="as_orderdetail" NAME="OrderComment" VALUE="">
</FORM>

      <form id="f" method="POST" action="" onsubmit="return doSubmit();">
          
        '.$select.'
          <label>Цена: <span id="sstxtprice"></span></label>
          <input type="hidden" id="ssprice" value=""/>
          <label>Имя:</label>
          <input class="form-control form-input" type="text" id="ssfirstname" size="60" maxsize="70">
          <label>Фамилия:</label>
          <input  class="form-control form-input"  type="text" id="sslastname" size="60" maxsize="70">
          <label>E-mail:</label>
          <input  class="form-control form-input"  type="email" id="ssemail" size="60" maxsize="128">
          <label>Телефон:</label>
          <input  class="form-control form-input"  type="text" id="ssphone" size="60" maxsize="20">

          <label>Способ доставки:</label>
          <select id="ssdelivery" class="form-control form-select"><option value="0"/>Самовывоз</option><option value="300"/> Доставка по Москве (МО) / Санкт-Петербургу (ЛО)</option><option value="550"/> Доставка по России</option></select>
          <label>Стоимость доставки: <span id="ssdeliverycost"></span> руб.
		      <div id="ssadr" style="display:none;">
            <label>Страна:</label>
            <input class="form-control form-input" type="text" id="sscountry" size="60" value="Россия" disabled maxsize="20">	
            <label>Почтовый индекс:</label>
            <input class="form-control form-input" type="text" id="sszip" size="60" maxsize="20">
            <label>Город:</label>
            <input class="form-control form-input" type="text" id="sscity" size="60" maxsize="20">
            <label>Адрес:</label>
            <input class="form-control form-input" type="text" id="ssaddress" size="60" maxsize="20">	
		      </div>
          <br>
          <hr>
          <span>Итого:</span> <span id="sstotal"></span>

          <div style="text-align:right; margin:40px 0px;">
          <input class="btn" type="submit" id="button" value="Оплатить через Ассист">
          </div>
        </form>
  </div>      
  
  <span id="y_text" style="display:none;margin-top:50px;"><span id="internal-source-marker_0.3001988610728249" style="background-color: transparent; font-variant: normal; font-style: normal; font-family: Calibri; color: #000000; font-size: 11pt; vertical-align: baseline; font-weight: bold; text-decoration: none;">Яндекс.Деньги</span><span style="background-color: transparent; font-variant: normal; font-style: normal; font-family: Calibri; color: #000000; font-size: 11pt; vertical-align: baseline; font-weight: normal; text-decoration: none;"> &ndash; доступный и безопасный способ платить за товары и услуги через интернет. Заполнив форму оплаты&nbsp;на нашем сайте, Вы будете перенаправлены на сайт Яндекс.Деньги, где сможете завершить платеж. Если у вас нет счета в Яндекс.Деньгах, его нужно открыть на </span><a href="https://money.yandex.ru/"><span style="background-color: transparent; font-variant: normal; font-style: normal; font-family: Calibri; color: #0000ff; font-size: 11pt; vertical-align: baseline; font-weight: normal; text-decoration: underline;">сайте платежной системы</span></a><span style="background-color: transparent; font-variant: normal; font-style: normal; font-family: Calibri; color: #000000; font-size: 11pt; vertical-align: baseline; font-weight: normal; text-decoration: none;">. </span>
  </span>
 <span id="a_text" style="margin-top:50px;display:block;"><p><strong>ASSIST</strong> - это мультибанковская система платежей по пластиковым и виртуальным картам через интернет, позволяющая в реальном времени производить авторизацию и обработку транcакций.</p><p><strong>ASSIST</strong> занимает лидирующее положение на российском рынке, проводя более 80% всех совершаемых в российском интернете транcакций !</p><p>В дополнение к стандартному набору карт VISA, MasterCard, ASSIST также предоставляет возможность оплаты электронной наличностью – WebMoney, Яндекс.Деньги, e-port, Kredit Pilot в рамках единого пользовательского интерфейса.</p>
<p>Расчеты, проводимые с использованием системы ASSIST, полностью соответствуют законодательству РФ и регулируются соответствующими статьями Гражданского Кодекса Российской Федерации (ГК РФ). Платежи с использованием банковских кредитных карточек проводятся по схеме MOTO (Mail Order Telephone Order) в строгом соответствии правилам платежных систем (VISA, Europay и др.).</p> 
<p><img hspace="3" src="/img/3/logos.png"></p></span> 

<br><br>
<p>По вопросам предоставления оплаченных услуг или доставке товаров обращаться к менеджеру, с которым Вы работаете. Телефоны:  в Москве (495) 933-5483/85; в Санкт-Петербурге (812) 670-2030.</p>
<p>По вопросу возврата денежных средств, предоставления взаимозаменяемых товаров/услуг, обмена товаров/услуг, при отказе от товара/услуги обращаться к менеджеру, с которым Вы работаете. Телефоны:  в Москве (495) 933-5483/85; в Санкт-Петербурге (812) 670-2030.</p>	
    </div>
    <div class="sidebar">
      '.cn_renderStandartLinksBlock().'<div class="cover"><img id="cover" src="'.$cover.'"/></div>
      '.cn_renderBannerSite('bnStandart1',0).'
      
      <div class="gray-bg">'.cn_renderStandartContacts().'</div>
    </div>
    </div>
    ';



    $output['#children'] = $body;
    return $output;
  }  

}
