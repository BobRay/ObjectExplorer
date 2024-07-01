<?php
 /* ObjectExplorer snippet
 *
 * Copyright 2012-2024 Bob Ray <http:bobsguides.com>
 *
 * @author Bob Ray <https:bobsguides.com>
 *
 * ObjectExplorer is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option) any
 * later version.
 *
 * ObjectExplorer is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * ObjectExplorer; if not, write to the Free Software Foundation, Inc., 59 Temple
 * Place, Suite 330, Boston, MA 02111-1307 USA
 *
 * @package ObjectExplorer
 */

/**
 * @package = ObjectExplorer
 *
 */
/*$mtime = microtime();
$mtime = explode(" ", $mtime);
$mtime = $mtime[1] + $mtime[0];
$tstart = $mtime;
set_time_limit(0);

*/

if (!defined('MODX_CORE_PATH')) {
    $outsideModx = true;
    $scriptProperties = array();
    /* put the path to your core in the next line to run outside of MODX */
    define('MODX_CORE_PATH', 'c:/xampp/htdocs/addons/core/');
    require_once MODX_CORE_PATH . '/model/modx/modx.class.php';
    $modx = new modX();
    if (!$modx) {
        $modx->log(modX::LOG_LEVEL_ERROR, 'Could not create MODX class');
    }
    $modx->initialize('mgr');
} else {
    $outsideModx = false;
}
/* load lexicon */
$language = $modx->getOption('language', $scriptProperties, null);
$language = $language ? $language . ':' : '';
$modx->lexicon->load($language . 'objectexplorer:default');


/* Set log stuff */
$modx->setLogLevel(modX::LOG_LEVEL_INFO);
$modx->setLogTarget(XPDO_CLI_MODE ? 'ECHO' : 'HTML');


require_once $modx->getOption('oe.core_path', null, $modx->getOption('core_path') . 'components/objectexplorer/') . 'model/objectexplorer/mygenerator.class.php';

require_once $modx->getOption('oe.core_path', null, $modx->getOption('core_path') . 'components/objectexplorer/') . 'model/objectexplorer/objectexplorer.class.php';


$modx->regClientCSS($modx->getOption('oe.assets_url', null, $modx->getOption('assets_url') . 'components/objectexplorer/') . 'css/objectexplorer.css');

/* make sure we can get the xPDO manager and MyGenerator */
$manager = $modx->getManager();
if (!$manager) {
    $modx->log(modX::LOG_LEVEL_ERROR, $modx->lexicon('oe_could_not_get_manager'));
    exit();
}

$generator = new MyGenerator($manager);
if ($generator) {
    //$modx->log(modX::LOG_LEVEL_INFO, 'Got mygenerator');
} else {
    $modx->log(modX::LOG_LEVEL_ERROR, $modx->lexicon('oe_could_not_get_generator'));
    exit();
}

$props =& $scriptProperties;
/* link to top of page for each item */
if (empty($props['topJump'])) {
    $props['topJump'] = "\n" . '<a href="[[~[[*id]]]]#top">' . $modx->lexicon('oe_back_to_top') . '</a>' . "\n<hr>\n";
}

if (empty ($props['columns'])) {
    $props['columns'] = 2;
}
$props['tab'] = '    ';
$jumpList = array();

/* anchor for top of page */
$top = '<a id="top"></a>' . "\n";

/* MODX schema file location */
$currentVersion = $modx->getVersionData()['version'];
$show_modx3 = (bool) $modx->getOption('show_modx3', $props, $currentVersion >= 3, true);
if ( ($currentVersion < 3) && $show_modx3) {
    /* Show MODX 3 classes in MODX 2 */
    $schemaFiles = array(
        MODX_CORE_PATH . 'components/objectexplorer/model/objectexplorer/modx3.mysql.schema.xml',
        MODX_CORE_PATH . 'components/objectexplorer/model/objectexplorer/modx3.transport.schema.xml',
        MODX_CORE_PATH . 'components/objectexplorer/model/objectexplorer/modx3.sources.schema.xml',

    );
} elseif ($currentVersion >= 3 && $show_modx3 == false) {
    /* Show MODX 2 classes in MODX 3 */
    $schemaFiles = array(
        MODX_CORE_PATH . 'components/objectexplorer/model/objectexplorer/modx2.mysql.schema.xml',
        MODX_CORE_PATH . 'components/objectexplorer/model/objectexplorer/modx2.transport.schema.xml',
        MODX_CORE_PATH . 'components/objectexplorer/model/objectexplorer/modx2.sources.schema.xml');
} else {
    /* Show current version's classes in either 2 or 3 */
    $schemaFiles = array(
        MODX_CORE_PATH . 'model/schema/modx.mysql.schema.xml',
        MODX_CORE_PATH . 'model/schema/modx.sources.mysql.schema.xml',
        MODX_CORE_PATH . 'model/schema/modx.transport.mysql.schema.xml',
    );
}

/* Are we creating a quick reference or a full reference */
/* set it here if outside of MODX, Quick Reference is the default */

//$props['full'] = 1;
$quick = !$modx->getOption('full', $props, null);

/* have the generator parse the schema and store it in $model */
$finalModel = array();
foreach($schemaFiles as $schemaFile) {
    $model = $generator->parseSchema($schemaFile, '');

    if (!$model) {
        /* The parser failed */
        $modx->log(modX::LOG_LEVEL_ERROR, $modx->lexicon('oe_error_parsing_schema_file'));
        exit();

    }
    $finalModel = array_merge($finalModel, $model);
}
/* schema is quite in alphabetical order */
ksort($finalModel);

$explorer = new ObjectExplorer($modx, $finalModel, $props);
if ($explorer) {
    //$modx->log(modX::LOG_LEVEL_INFO, 'Got mygenerator');
} else {
    $modx->log(modX::LOG_LEVEL_ERROR, $modx->lexicon('oe_could_not_get_explorer'));
    exit();
}
$output = '';
$output .= $top;
$output .= "<h2>" . $modx->lexicon('oe_modx_objects') . "</h2>\n";
$output .= '<div  id="objectexplorer_jumplist_div" width="60%">' . "\n";

$output .= $explorer->getJumpListDisplay();

$output .= "\n</div>\n\n";

if ($quick) {
    $output .= "\n" . '<div class="quick-reference">' . "\n";
    foreach ($finalModel as $key => $value) {
        $output .= $explorer->getQuickSingle($key);
        $output .= $props['topJump'];
    }
    $output .= "</div>\n";
} else {
    $output .= "\n" . '<div class="full-reference">' . "\n";
    foreach ($finalModel as $key => $value) {
        $output .= $explorer->getFullSingle($key);
        $output .= $props['topJump'];
    }
    $output .= "</div>\n";
}

unset($model, $explorer);

if ($outsideModx) {
    echo $output;
} else {
    return $output;
}

/*$mtime= microtime();
$mtime= explode(" ", $mtime);
$mtime= $mtime[1] + $mtime[0];
$tend= $mtime;
$totalTime= ($tend - $tstart);
$totalTime= sprintf("%2.4f s", $totalTime);

$modx->log(modX::LOG_LEVEL_INFO,"Execution time: {$totalTime}\n");
*/
