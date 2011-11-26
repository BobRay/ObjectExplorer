<?php
/**
 * @package = ObjectExplorer
 *
 */
$mtime = microtime();
$mtime = explode(" ", $mtime);
$mtime = $mtime[1] + $mtime[0];
$tstart = $mtime;
/* get rid of time limit */

set_time_limit(0);
if (!defined('MODX_CORE_PATH')) {
    $outsideModx = true;
    /* put the path to your core in the next line to run outside of MODx */
    define(MODX_CORE_PATH, 'c:/xampp/htdocs/addons/core/');
    require_once MODX_CORE_PATH . '/model/modx/modx.class.php';
    $modx = new modX();
    if (! $modx) {
        $modx->log(modX::LOG_LEVEL_ERROR, 'Could create MODX class');
    }
    $modx->initialize('mgr');

}

require_once $modx->getOption('oe.core_path', null, $modx->getOption('core_path') . 'components/objectexplorer/') . 'model/objectexplorer/mygenerator.class.php';

require_once $modx->getOption('oe.core_path', null, $modx->getOption('core_path') . 'components/objectexplorer/') . 'model/objectexplorer/objectexplorer.class.php';


$modx->regClientCss($modx->getOption('oe.assets_url', null, $modx->getOption('assets_url') . 'components/objectexplorer/') . 'css/objectexplorer.css');

/* make sure we can get the xPDO manager and MyGenerator */
$manager = $modx->getManager();
if (! $manager) {
    $modx->log(modX::LOG_LEVEL_ERROR, 'Could not get Manager');
    exit();
}

$generator = new MyGenerator($manager);
if ($generator) {
    //$modx->log(modX::LOG_LEVEL_INFO, 'Got mygenerator');
} else {
    $modx->log(modX::LOG_LEVEL_ERROR, 'Could not get Generator');
    exit();
}

$props =& $scriptProperties;
/* link to top of page for each item */
$props['topJump'] = "\n" . '<a href="[[~[[*id]]]]#top">back to top . . .</a>' . "\n<hr>\n" ;
$props['tab'] = '    ';
$jumpList = array();

/* anchor for top of page */
$top = '<a name="top"></a>' . "\n";

/* MODX schema file location */
$schemaFile = MODX_CORE_PATH . 'model/schema/modx.mysql.schema.xml';

/* Are we creating a quick reference or a full reference */
/* set it here if outside of MODX. Quick Reference is the default */

$props['full'] = 1;
$quick = ! $modx->getOption('full', $props, null);

/* Set log stuff */
$modx->setLogLevel(modX::LOG_LEVEL_INFO);
$modx->setLogTarget(XPDO_CLI_MODE ? 'ECHO' : 'HTML');

/* have the generator parse the schema and store it in $model */
$model = $generator->parseSchema($schemaFile, '');
if (! $model) {
    /* The parser failed */
    return 'Error parsing schema file';
}
/* schema is not quite in alphabetical order */
ksort($model);

$explorer = new ObjectExplorer($modx, $model, $props);
if ($explorer) {
    //$modx->log(modX::LOG_LEVEL_INFO, 'Got mygenerator');
} else {
    $modx->log(modX::LOG_LEVEL_ERROR, 'Could not get Explorer');
    exit();
}
$output = '';
$output .= $top;
$output .= "<h2>MODX Objects</h2>\n";
$output .= $explorer->getJumpListDisplay();

if ($quick) {
    $output .= "\n" .'<div class="quick-reference">' ."\n";
    foreach($model as $key => $value) {
        $output .= $props['topJump'];
        $output .= $explorer->getQuickSingle($key);
    }
    $output .= "</div>\n";
}  else {
    $output .=  "\n" . '<div class="quick-reference">' . "\n";

    //$output .= $explorer->getQuickDisplay($model);
    $output .= "\n" .'<div class="quick-reference">' ."\n";
    foreach($model as $key => $value) {
        $output .= $props['topJump'];
        $output .= $explorer->getFullSingle($key);
    }
    $output .= "</div>\n";
}

unset($model);

if ($outsideModx) {
    echo $output;
} else {
    return $output;
}
$mtime= microtime();
$mtime= explode(" ", $mtime);
$mtime= $mtime[1] + $mtime[0];
$tend= $mtime;
$totalTime= ($tend - $tstart);
$totalTime= sprintf("%2.4f s", $totalTime);

$modx->log(modX::LOG_LEVEL_INFO,"Execution time: {$totalTime}\n");

