<?php
header('Content-Type: application/rss+xml; charset=utf-8');
print '<?xml version="1.0" encoding="UTF-8" ?>'; 

?>
<rss version="2.0">

<channel>
  <title>Comnews articles</title>
  <link>https://www.comnews.ru</link>
  <description></description>


<?php

$s = strtotime('today 00:00');
$months = array("","января","февраля","марта","апреля","мая","июня","июля","августа","сентября","октября","ноября","декабря");
			
$tmp = date('d',$s).' '.$months[intval(date('m',$s))].' '.date('Y',$s).'г.';
print '<item><title>'.$tmp.'</title><description></description><link>https://www.comnews.ru/</link><guid>https://www.comnews.ru/#'.date('Y-m-d').'</guid></item>';
				
?>
             
             
</channel>

</rss>             