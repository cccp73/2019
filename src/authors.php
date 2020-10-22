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

//if(!cn_isAdmin()) die;

$nid = intval(cn_getVal($_GET['nid']));
if(!in_array($nid , array('201640','202214','202213'))) die; 

$node = \Drupal\node\Entity\Node::load($nid);

print $node->body->value;

?>
