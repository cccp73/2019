<?php

namespace Drupal\comnews\Twig\Extension;

/**
 * Custom twig extensions.
 */
class Extensions extends \Twig_Extension {

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    $functions = [];

    $functions[] = new \Twig_SimpleFunction('cn_page_title', [
      $this,
      'cnPageTitle',
    ]);
    $functions[] = new \Twig_SimpleFunction('cn_meta_tags', [
      $this,
      'cnRenderMetaTags',
    ]);
    $functions[] = new \Twig_SimpleFunction('cn_render_hidden_banners', [
      $this,
      'cnRenderHiddenBanners',
    ]);
    $functions[] = new \Twig_SimpleFunction('cn_content', [
        $this,
        'cnRenderContent',
      ]);
    $functions[] = new \Twig_SimpleFunction('cn_rnd', [
        $this,
        'cnRenderTime',
      ]); 
    $functions[] = new \Twig_SimpleFunction('cn_mobileCSS', [
        $this,
        'cnRenderMobileCSS',
      ]); 
    $functions[] = new \Twig_SimpleFunction('cn_mobile_menu', [
        $this,
        'cnRenderMobileMenu',
      ]);  
    return $functions;
  }

  /**
   * Replace page title in twig
   */
  public function cnPageTitle($default) {
    
    if(isset($_REQUEST['page-title']) && $_REQUEST['page-title']!=''){  
          $default = $_REQUEST['page-title'];
    }
    if(is_array($default) || is_object($default)){
      
      $str = render($default);
      //ksm($str); 
      if($str) {
        $str = explode('|',$str->__toString());
        if(count($str) == 1){
          $str = '<div class="s1"><div>'.$str[0].'</div></div>';
        } else {
          $str = '<div class="s1"><div>'.$str[0].' <span class="black"> / '.$str[1].'</span></div></div>';
        }
      }  
      
      return array('#children'=>$str);
    } else {
      if(strip_tags($default) == $default) $default = '<div class="s1"><div>'.$default.'</div></div>';
      return array('#children'=>$default);
    }  
    
  }
  /**
   * Render content from request variable
   * 
   */
  public function cnRenderContent($content,$node) {
    $tmp = render($content);
    if($tmp) $tmp = $tmp->__toString(); else $tmp = '';
    return array('#children' => cn_renderNodeImages($tmp,$node));
  }
  /**
   * Render meta tags request variable
   * 
   */
  public function cnRenderMetaTags() {
    return array('#children'=> implode(' ',cn_getMetaTags()));
  }
  /**
   * cnRenderHiddenBanners
   * 
   */
  public function cnRenderHiddenBanners() {
    return array('#children'=> cn_renderHiddenBanners());
  }
  /**
   * cnRenderTime
   * 
   */
  public function cnRenderTime() {
    return array('#children'=> time());
  }
  /**
   * cnRenderMobileMenu
   * 
   */
  public function cnRenderMobileMenu() {
    return array('#children'=> cn_renderMobileMenu());
  }
  /**
   * cnRenderMobileCSS
   * 
   */
  public function cnRenderMobileCSS() {
    //$s = cn_getVal($_COOKIE['force-full-version']);
    //if($s=="1"){
      $s = '<link id="mobile_css" href="/themes/site/css/mobile.css?rr='.time().'" rel="stylesheet" />';
    //} else {
    //  $s = '';
    //}
    return array('#children'=> $s);
  }
}