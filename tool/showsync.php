<?
include(dirname(__FILE__).'/../../config/config.inc.php');

if(Configuration::get('EGN_SYNC') != 'COMPLETE') {$runing = 'Running...';
$bottom = "<br><br>If your process stops for more than 10 sec. click 
<br><input type = button value = 'RESTART' class=button onclick='setInterval( \"updateSyncStatus()\", 500 ); $.get(\"".str_replace(_PS_ROOT_DIR_,Configuration::get('CANONICAL_URL'),_PS_MODULE_DIR_)."egenesetrack/synchronisation.php\");'>";
} else {$runing = ''; $bottom='';}

echo ($runing." ".Configuration::get('EGN_SYNC').$bottom);
?>
