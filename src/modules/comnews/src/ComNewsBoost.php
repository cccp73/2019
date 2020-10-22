<?php 
/*
 
*/
namespace Drupal\comnews;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

define('BOOST_DIR_MODE', 0775 );
define('BOOST_CACHE_TTL', 3600 );

class ComNewsBoost {

  private static $_boost = array('cache_this'=> TRUE);  
  /*
  */
  public function init(){
     
    ComNewsBoost::boost_init();
    //var_dump($_boost);die;
  }
   /*
  */
  public function clear(){
    ComNewsBoost::$_boost = array('cache_this'=> FALSE); 
  }
  /*
  */
  public function process($response){
    
    //var_dump(ComNewsBoost::$_boost);
    $current_user = \Drupal::currentUser();
    if(!$current_user->isAnonymous() && in_array('administrator', \Drupal::currentUser()->getRoles()) && \Drupal::request()->query->get('flushpagecache') == '1'){
      

        $c = $this->boost_flush_caches();
        \Drupal::messenger()->addMessage('Страничный кеш сброшен ('.$c.' файлов).');
    } 

    ComNewsBoost::boost_createCache($response);
  }
  /*
    boost init
  */
  public static function boost_init(){

    $request = \Drupal::request();
    $current_user = \Drupal::currentUser();
    //ComNewsBoost::$_boost['cache_this'] = TRUE;
    $path = explode('/', \Drupal::service('path.alias_manager')->getPathByAlias($request->getRequestUri()));
    // Make sure the page is/should be cached according to our current configuration.
    // Start with the quick checks
    if (
        strpos(\Drupal::request()->server->get('SCRIPT_FILENAME'), 'index.php') === FALSE
        //|| $_SERVER['SERVER_SOFTWARE'] === 'PHP CLI'
        || (\Drupal::request()->server->get('REQUEST_METHOD') != 'GET' && \Drupal::request()->server->get('REQUEST_METHOD') != 'HEAD')
        || (\Drupal::request()->query->get('nocache') == '1')
        || (cn_getVal($_COOKIE['showallbanners']) == 1)
        || (\Drupal::state()->get('maintenance_mode')) 
        || (defined('MAINTENANCE_MODE'))
        || (count(\Drupal::messenger()->all())) // do not cache pages with messages 
        || ($path[1] == 'user')
        || (!$current_user->isAnonymous())
    ) {
        ComNewsBoost::$_boost['cache_this'] = FALSE;//var_dump(\Drupal::state()->get('maintenance_mode'));
    }else {
        // More advanced checks
        ComNewsBoost::boost_transform_url();
        
    }
    // set nocache cookie
    if(!$current_user->isAnonymous()) ComNewsBoost::boost_set_cookie($current_user->id()); else ComNewsBoost::boost_set_cookie(0, TRUE);

  }

  public static function boost_set_cookie($uid, $expires = NULL) {
    if (!$expires) {
      $expires = ini_get('session.cookie_lifetime');
      $expires = (!empty($expires) && is_numeric($expires)) ?  \Drupal::time()->getRequestTime() + (int)$expires : 0;
      setcookie('DRUPAL_UID', strval($uid), $expires, ini_get('session.cookie_path'), ini_get('session.cookie_domain'), ini_get('session.cookie_secure') == '1');
    }
    else {
      setcookie('DRUPAL_UID', '0', $expires, ini_get('session.cookie_path'), ini_get('session.cookie_domain'), ini_get('session.cookie_secure') == '1');
    }
  }
  
  /**
 * Verify that the operation is going to operate in the cache dir.
 *
 * @param $file
 *  relative directory or file.
 */
public static function boost_in_cache_dir($file) {
    
    $good = TRUE;
    $real_file = realpath($file);
    $cache_dir = 'cache';
    $real_cache_dir = realpath($cache_dir);
  
    // Only operate in the cache dir.
    // Check the real path.
    if (   strpos($file, $cache_dir) !== 0
        || ($real_file && $real_cache_dir && strpos($real_file, $real_cache_dir) !== 0)
          ) {
      $good = FALSE;
    }
  
    // Send error to watchdog.
    if (!$good) {
        \Drupal::logger('comnews')->error('An operation outside of the cache directory was attempted on your system. %file or %real_file is outside the cache directory %cache or %real_cache.', array(
        '%file'       => $file,
        '%real_file'  => $real_file,
        '%cache'      => $cache_dir,
        '%real_cache' => $real_cache_dir,
        )
      );
      
    }
  
    return $good;
  }

  /**
 * Create a directory.
 *
 * @param $directory
 *  relative directory.
 */
public static function boost_mkdir($directory) {
    //global $_boost;
  
    // Only do something if it's not a dir.
    if (!is_dir($directory)) {
      if (!ComNewsBoost::boost_in_cache_dir($directory)) {
        return FALSE;
      }
  
      // Try to create the directory.
       
      if (!mkdir($directory, BOOST_DIR_MODE, TRUE)) {
        \Drupal::logger('comnews')->error('Could not create the directory %dir on your system', array('%dir' => $directory));
        return FALSE;
      }
      drupal_chmod($directory);
    }
    return TRUE;
  }
  
 /**
 * Write to a file. Ensures write is atomic via rename operation.
 *
 * @param $filename
 *  relative filename.
 * @param $data
 *  data to write to the file.
 */
  public static function boost_write_file($filename, $data) {
    // Create directory if it doesn't exist.
    $directory = dirname($filename);
    if (!ComNewsBoost::boost_mkdir($directory)) {
    return FALSE;
    }

    
    // Save data to a temp file.
    // file_unmanaged_save_data does not use rename.
    $tempname = \Drupal::service('file_system')->tempnam($directory, 'comnews');
    if (file_put_contents($tempname, $data) === FALSE) {
        \Drupal::logger('comnews')->error('Could not create the file %file on your system', array('%file' => $tempname));
        @unlink($tempname);
        return FALSE;
    }

    // Move temp file to real filename; windows can not do a rename replace.
    if (@rename($tempname, $filename) === FALSE) {
        $oldname = $tempname . 'old';
        if (@rename($filename, $oldname) !== FALSE) {
            if (@rename($tempname, $filename) === FALSE) {
            \Drupal::logger('comnews')->error('Could not rename the file %file on your system', array('%file' => $filename));
            @unlink($tempname);
            @rename($oldname, $filename);
            return FALSE;
            }
            else {
            @unlink($oldname);
            }
        }
    }
    
    // chmod file so webserver can send it out.
    \Drupal::service('file_system')->chmod($filename);
    return TRUE;
  }
 /**
 * Returns the age of a cached file, measured in seconds since it was last
 * updated.
 *
 * @param $filename
 *   Name of cached file
 * @return int
 */
  public static function boost_file_get_age($filename) {
    return  \Drupal::time()->getRequestTime() - filemtime($filename);
  }

 /**
 * Recursive version of rmdir(); use with extreme caution.
 *
 * Function also checks file age and only removes expired files.
 *
 * @param $dir
 *   The top-level directory that will be recursively removed.
 * @param $flush
 *   Instead of removing expired cached files, remove all files.
 */
  public static function boost_rmdir($dir = NULL, $flush = TRUE) {
    static $lifetimes = array();
    static $counter = 0;
     
    if($dir === NULL){
       $parts = ComNewsBoost::boost_parse_url();
       
       if(isset($parts['base_dir'])) $dir = $parts['base_dir'];     
        
    }
    if (is_dir($dir) == FALSE) {
      return FALSE;
    }
    
    if (!ComNewsBoost::boost_in_cache_dir($dir)) {
      return FALSE;
    }
  
    // Map extensions to cache lifetimes
    if (empty($lifetimes)) {
      
      $lifetimes['html'] = BOOST_CACHE_TTL;  // 1 hour
      // Be sure to recreate the htaccess file just in case.
      ComNewsBoost::boost_htaccess_cache_dir_put();
      //ComNewsBoost::boost_htaccess_cache_dir_generate();
    }
  
    $objects = scandir($dir);
    $empty_dir = TRUE;
    foreach ($objects as $object) {
      if ($object == "." || $object == "..") {
        continue;
      }
      if ($object == ".htaccess") {
        $empty_dir = FALSE;
        continue;
      }
      
      $file = $dir . "/" . $object;
       
      if (is_dir($file)) {
        ComNewsBoost::boost_rmdir($file, $flush);
      }
      elseif ($flush) {
       
        unlink($file);
        $counter++;
      }
      else {
        // Need to handle gzipped files.
        // Nice if  it supported multi level cache expiration per content type.
        $ext = substr(strrchr($file, '.'), 1);
        $age = ComNewsBoost::boost_file_get_age($file);
        if (isset($lifetimes[$ext]) && $age > $lifetimes[$ext]) {
          unlink($file);
          $counter++;
        }
        else {
          $empty_dir = FALSE;
        }
      }
    }
    if ($empty_dir && is_dir($dir)) {
      // #1138630 @ error suppression used due to rmdir being a race condition.
      @rmdir($dir);
    }
    return $counter;
  }

/**
 * Returns the relative normal cache dir. cache/normal.
 */
  public static function boost_get_normal_cache_dir() {
    return 'cache' . '/' . 'normal';
  }
  

/**
 * Overwrite old htaccess rules with new ones.
 */
  public static function boost_htaccess_cache_dir_put() {
    global $base_path, $base_root;
    if (empty(ComNewsBoost::$_boost['base_dir'])) {
      $url = $base_root . \Drupal::request()->getRequestUri();
      $parts = parse_url($url);
      $parts['host'] = 'main-host';
      ComNewsBoost::$_boost['base_dir'] = ComNewsBoost::boost_get_normal_cache_dir() . '/' . $parts['host'] . $base_path; //.'/';
    }
    ComNewsBoost::boost_write_file(ComNewsBoost::$_boost['base_dir'] .'/'. '.htaccess', ComNewsBoost::boost_htaccess_cache_dir_generate());
  }  

 /**
 * Generate htaccess rules for the cache directory.
 */
  public static function boost_htaccess_cache_dir_generate() {
    $char_type = 'utf-8';
    $etag = 3; 
  
    // Go through every storage type getting data needed to build htaccess file.
    $gzip = TRUE;
    $data = array();
    $files = array();
    $forcetype = '\.html(\.gz)?$';
    $files['html'] = 'html';
    $data['html'] = array(
    'type' => 'text/html',
    'forcetype' => $forcetype,
    );   
      
    // Add in default charset
    $string = "AddDefaultCharset " . $char_type . "\n";
      
    // Set FileETag
    if ($etag == 1) {
      $string .= "FileETag None\n";
    }
    elseif ($etag == 2) {
      $string .= "FileETag All\n";
    }
    elseif ($etag == 3) {
      $string .= "FileETag MTime Size\n";
    }
  
    // Set html expiration time to the past and put in boost header if desired.
    $files = '(' . implode('|' , $files) . ')';
    if ($gzip) {
      $files .= '(\.gz)?';
    }
    $string .= "<FilesMatch \"\.$files$\">\n";
    $string .= "  <IfModule mod_expires.c>\n";
    $string .= "    ExpiresDefault A5\n";
    $string .= "  </IfModule>\n";
    $string .= "  <IfModule mod_headers.c>\n";
    $string .= "    Header set Expires \"Sun, 19 Nov 1978 05:00:00 GMT\"\n";
    $string .= "    Header unset Last-Modified\n";
    $string .= "    Header append Vary Accept-Encoding\n";
    $string .= "    Header set Cache-Control \"no-store, no-cache, must-revalidate, post-check=0, pre-check=0\"\n";
    
      $string .= "    Header set X-Cached-By \"Boost\"\n";
    
    $string .= "  </IfModule>\n";
    $string .= "</FilesMatch>\n";
  
    // Set charset and content encoding.
    $string .= "<IfModule mod_mime.c>\n";
    foreach ($data as $extension => $values) {
      $string .= "  AddCharset " . $char_type . " ." . $extension . "\n";
    }
    $string .= $gzip ? "  AddEncoding gzip .gz\n" : '';
    $string .= "</IfModule>\n";
  
    // Fix for versions of apache that do not respect the T='' RewriteRule
    foreach ($data as $extension => $values) {
      $forcetype = $values['forcetype'];
      $type = $values['type'];
      $string .= "<FilesMatch \"$forcetype\">\n";
      $string .= "  ForceType " . $type . "\n";
      $string .= "</FilesMatch>\n";
    }
  
    // Make sure files can not execute in the cache dir.
    $string .= "\n";
    $string .= "SetHandler Drupal_Security_Do_Not_Remove_See_SA_2006_006\n";
    $string .= "Options None\n";
    //$string .= "Options +" . ( variable_get('boost_match_symlinks_options', BOOST_MATCH_SYMLINKS_OPTIONS) ? "FollowSymLinks" : "SymLinksIfOwnerMatch" ) . "\n";
    $string .= "Options +" . "FollowSymLinks" . "\n";
    $string .= "\n";
  
    return $string;
  } 

/**
 * Implements hook_exit().
 */
  public static function boost_createCache($response) {
    // Bail out of caching.
    if (!isset(ComNewsBoost::$_boost['cache_this'])) {
      if (!isset(ComNewsBoost::$_boost['is_cacheable'])) {
        return;
      }
      elseif (!ComNewsBoost::$_boost['is_cacheable']) {
        return;
      }
    }
  
    if (isset(ComNewsBoost::$_boost['cache_this']) && ComNewsBoost::$_boost['cache_this'] == FALSE) {
      return;
    }
    elseif (!isset(ComNewsBoost::$_boost['is_cacheable']) || !ComNewsBoost::$_boost['is_cacheable']) {
      return;
    }
    /*
    elseif (!drupal_page_is_cacheable()) {
      ComNewsBoost::$_boost['is_cacheable'] = FALSE;
      return;
    }
    */

    // Get the data to cache.
    $data = $response->getContent(); //ob_get_contents();
    
    // Add note to bottom of content if possible.
    
      $expire = BOOST_CACHE_TTL; // 1 hour
      $cached_at = date('Y-m-d H:i:s', \Drupal::time()->getRequestTime());
      $expires_at = date('Y-m-d H:i:s', \Drupal::time()->getRequestTime() + $expire);
      $note = "\n" . '<!-- ' . 'Page cached by Boost @ ' . $cached_at . ', expires @ ' . $expires_at . ', lifetime ' . $expire . 's. -->';
      $data .= $note;
    
      
    // Write data to a file.
    if (ComNewsBoost::$_boost['filename']) {
      // Attach extension to filename.
      ComNewsBoost::$_boost['filename'] .= '.' . 'html';
      // Write to file.
      ComNewsBoost::boost_write_file(ComNewsBoost::$_boost['filename'], $data);
  
      // Gzip support.
      // #1416214
      // boost_write_file(ComNewsBoost::$_boost['filename'] . '.gz', gzencode($data, 9));
      
    }
  }

/**
 * Implements hook_flush_caches(). Deletes all static files.
 */
  public static function boost_flush_caches() {
    // Remove all files from the cache
    
    $count = ComNewsBoost::boost_rmdir(NULL, TRUE);
    if($count !== FALSE) 
        \Drupal::logger('comnews')->info('Flushed all files (%count) from static page cache.', array('%count' => $count));
    else 
        \Drupal::logger('comnews')->error('No files was flushed from static page cache due some problem.');

    return $count;
  } 
  
/**
 * Given a URL give back eveything we know
 *
 * @param $url
 *   Full URL
 * @param $b_path
 *   Base Path
 */
  public static function boost_transform_url($url = NULL, $b_path = NULL) {
    global $base_root, $base_path;
    
      $parts = ComNewsBoost::boost_parse_url($url, $b_path);
      
      if (!$parts) {
        $parts = array('cache_this' => FALSE);
        ComNewsBoost::$_boost = $parts;
        return $parts; 
      }
      
      //$parts['base_dir'] = ComNewsBoost::boost_get_normal_cache_dir() . '/' . $parts['host'] . $base_path;
      $parts['filename'] = $parts['base_dir'] . '/' . $parts['full_path'] . '_' . $parts['query'];
      $parts['directory'] = dirname($parts['filename']);
  
      // Get the internal path (node/8).
      if (\Drupal::service('path.matcher')->isFrontPage()) {
        $parts['normal_path'] = \Drupal::config('system.site')->get('page.front');// \Drupal::state()->get('site_frontpage');
      }
      else {
        $parts['normal_path'] = \Drupal::service('path.alias_manager')->getPathByAlias('/'.$parts['path']);
      }
      
      // Get the alias (content/about-us).
      $parts['path_alias'] = \Drupal::service('path.alias_manager')->getAliasByPath($parts['normal_path']);
      
     
      // See if url is cacheable.
      $parts = ComNewsBoost::boost_is_cacheable($parts);
      
      
    
    ComNewsBoost::$_boost = array_merge (ComNewsBoost::$_boost, $parts);  
    return ComNewsBoost::$_boost; 
  }
 
/**
 * parse_url that takes into account the base_path
 *
 * @param $url
 *   Full URL
 * @param $b_path
 *   Base Path
 */
  public static function boost_parse_url($url = NULL, $b_path = NULL) {
    global $base_root, $base_path;
    // Set defaults.
    if ($url === NULL) {
      $url = $base_root . \Drupal::request()->getRequestUri();
    }
    if ($b_path == NULL) {
      $b_path = $base_path;
    }
    
    // Parse url.
    $parts = parse_url($url);
    if (empty($parts['host']) || empty($parts['path'])) {
      return FALSE;
    }
    $parts['host'] = 'main-host';
    if (!isset($parts['query'])) {
      $parts['query'] = '';
    }
    
    $parts['path'] = $parts['full_path'] = urldecode(preg_replace('/^' . preg_quote($b_path, '/') . '/i', '', $parts['path']));
    $parts['base_path'] = $b_path;
    if($b_path == '/') $b_path = '';
    $parts['base_dir'] = ComNewsBoost::boost_get_normal_cache_dir() . '/' . $parts['host'] . $b_path; //.'/';
    $parts['query_array'] = array();
    
    parse_str($parts['query'], $parts['query_array']);
  
    
  
    // Get page number and info from the query string.
    if (!empty($parts['query_array'])) {
      $query = array();
      foreach ($parts['query_array'] as $key => $val) {
        if ($key != 'q' && $key != 'destination' && $key != 'page' && !empty($val)) {
          $query[$key] = $val;
        }
        if ($key == 'page' && is_numeric($val)) {
          $parts['page_number'] = $val;
        }
      }
      ksort($query);
      $parts['query_extra'] = str_replace('&amp;', '&', urldecode(http_build_query($query)));
    }
  
    // Get fully decoded URL.
    $decoded1 = urldecode($parts['base_path'] . $parts['path'] . '_' . $parts['query']);
    $decoded2 = urldecode($decoded1);
    while ($decoded1 != $decoded2) {
      $decoded1 = urldecode($decoded2);
      $decoded2 = urldecode($decoded1);
    }
    $decoded = $decoded2;
    unset($decoded2);
    unset($decoded1);
  
    $parts['url_full'] = $parts['host'] . $parts['base_path'] . $parts['path'] . '_' . $parts['query'];
    $parts['url'] = $url;
    $parts['url_decoded'] = $decoded;
    return $parts;
  }

/**
 * Determines whether a given url can be cached or not by boost.
 *
 * TODO: Add in support for the menu_item
 *
 * @param $parts
 *   $parts
 * @param $request_type
 *   May be 'status' to skip some checks in order to show the status
 *   block on the admin interface (otherwise we will always mention
 *   that the page is non-cacheable, since user is logged in).
 *   Please don't rely on this parameter if you are extending boost,
 *   this is likely to change in the future. Contact us if you use it.
 *
 * @return $parts
 */
  public static function boost_is_cacheable($parts, $request_type = 'normal') {
    // Set local variables.
    $path = $parts['path'];
    $query = $parts['query'];
    $full = $parts['url_full'];
    $normal_path = $parts['normal_path'];
    $alias = $parts['path_alias'];
    $decoded = $parts['url_decoded'];
  

    $parts['is_cacheable'] = TRUE;
    // Never cache
    //  the user autocomplete/login/registration/password/reset/logout pages
    //  any admin pages
    //  comment reply pages
    //  node add page
    //  openid login page
    //  URL variables that contain / or \
    //  if incoming URL contains '..' or null bytes
    //  if decoded URL contains :// outside of the host portion of the url
    //  Limit the maximum directory nesting depth of the path
    //  Do not cache if destination is set.
    if (   $normal_path == '/user'
        || preg_match('!^/user/(autocomplete|login|register|password|reset|logout)!', $normal_path)
        || preg_match('!^/admin!', $normal_path)
        || preg_match('!^/adm!', $normal_path)
        || preg_match('!^/comments!', $normal_path)
        || preg_match('!^/comment/reply!', $normal_path)
        || preg_match('!^/node/1!', $normal_path)
        || preg_match('!^/node/add!', $normal_path)
        || preg_match('!^/index.php!', $path)
        || preg_match('!\.php$!', $path)
        || preg_match('!^/openid/authenticate!', $normal_path)
        || strpos($query, '/') !== FALSE
        || strpos($query, "\\") !== FALSE
        || strpos($full, '..') !== FALSE
        || strpos($full, "\0") !== FALSE
        || count(explode('/', $path)) > 10
        || strpos($decoded, "://") !== FALSE
        || !empty($query_array['destination'])
      ) {
      $parts['is_cacheable'] = FALSE;
      $parts['is_cacheable_reason'] = 'Core Drupal dynamic pages';
      return $parts;
    }
  
    // Check for reserved characters if on windows.
    // http://en.wikipedia.org/wiki/Filename#Reserved_characters_and_words
    // " * : < > |
    $chars = '"*:<>|';
    if (stristr(PHP_OS, 'WIN') && preg_match("/[" . $chars . "]/", $full)) {
      $parts['is_cacheable'] = FALSE;
      $parts['is_cacheable_reason'] = 'Reserved characters on MS Windows';
      return $parts;
    }
  
    /*
    // Match the user's cacheability settings against the path.
    // See http://api.drupal.org/api/function/block_block_list_alter/7
    $visibility = variable_get('boost_cacheability_option', BOOST_VISIBILITY_NOTLISTED);
    $pages_setting = variable_get('boost_cacheability_pages', BOOST_CACHEABILITY_PAGES);
    if ($pages_setting) {
      // Convert path string to lowercase. This allows comparison of the same path
      // with different case. Ex: /Page, /page, /PAGE.
      $pages = drupal_strtolower($pages_setting);
      if ($visibility < BOOST_VISIBILITY_PHP) {
        // Convert the alias to lowercase.
        $path = drupal_strtolower($alias);
        // Compare the lowercase internal and lowercase path alias (if any).
        $page_match = drupal_match_path($path, $pages);
        if ($path != $normal_path) {
          $page_match = $page_match || drupal_match_path($normal_path, $pages);
        }
        // When 'boost_cacheability_option' has a value of 0 (BOOST_VISIBILITY_NOTLISTED),
        // Boost will cache all pages except those listed in 'boost_cacheability_pages'.
        // When set to 1 (BOOST_VISIBILITY_LISTED), Boost will only cache those
        // pages listed in 'boost_cacheability_pages'.
        $page_match = !($visibility xor $page_match);
      }
      elseif (module_exists('php')) {
        $page_match = php_eval($pages_setting);
      }
      else {
        $page_match = FALSE;
      }
    }
    else {
      $page_match = TRUE;
    }
  
    $parts['is_cacheable'] = $page_match;
  
    if (! $page_match) {
      $parts['is_cacheable_reason'] = 'Page excluded from cache by the include/exclude paths defined by site admin.';
    }

    if (!$parts['is_cacheable']) {
      return $parts;
    }
    
    // Invoke hook_boost_is_cacheable($path).
    $modules = boost_module_implements('boost_is_cacheable', 'boost');
    foreach ($modules as $module) {
      $result = module_invoke($module, 'boost_is_cacheable', $parts, $request_type);
      if ($result['is_cacheable'] === FALSE) {
        if (! isset($result['is_cacheable'])) {
          $result['is_cacheable_reason'] = 'Page excluded from cache by a third-party module.';
        }
  
        return $result;
      }
    }
    */
    return $parts;
  }


}