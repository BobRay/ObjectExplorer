<?php
/**
 * @package = CreateXpdoClasses
 *
 */

if (!defined('MODX_CORE_PATH')) {
    $outsideModx = true;
    /* put the path to your core in the next line to run outside of MODx */
    define(MODX_CORE_PATH, 'c:/xampp/htdocs/addons/core/');
    include_once MODX_CORE_PATH . '/model/modx/modx.class.php';
    $modx = new modX();
    $modx->initialize('mgr');
}

require_once $modx->getOption('oe.core_path', null, $modx->getOption('core_path') . 'components/objectexplorer/') . 'model/objectexplorer/mygenerator.class.php';

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
/* anchor for top of page */
$top = '<a name="top"></a>' . "\n";

/* link to top of page for each item */
$topJump = "\n" . '<a href="[[~[[*id]]]]#top">back to top . . .</a>' . "\n";

/* MODX schema file location */
$schemaFile = MODX_CORE_PATH . 'model/schema/modx.mysql.schema.xml';

/* Are we creating a quick reference or a full reference */
/* set it here if outside of MODX. Quick Reference is the default */

//$props['full'] = 1;
$quick = ! $modx->getOption('full', $props, null);

/* MODX DB table prefix */
$prefix = $modx->getOption('table_prefix');

/* Set log stuff */
$modx->setLogLevel(modX::LOG_LEVEL_INFO);
$modx->setLogTarget(XPDO_CLI_MODE ? 'ECHO' : 'HTML');

/* have the generator parse the schema and store it in $model */
$model = $generator->parseSchema($schemaFile, '');
if (! $model) {
    /* The parser failed */
    return 'Error parsing schema file';
}
 if ($quick) {
    /* schema is not quite in alphabetical order */
    ksort($model);
    /* initialize array for jump links at top of page */
    $jumpList = array();

    /* $objects will be the output for a quick reference */
    $objects = '';

    /* build the output from the $model array */
    foreach ($model as $key => $value) {
        $objects .= '<a name="' . $key . '"></a>' . "\n";
        $objects .= $topJump;
        $jumpList[] = $key;
        $objects .= '<h3>' . $key . '</h3>';
        if (isset($value['extends'])) {
            $objects .= "\n" . '   Extends: ' . $value['extends'] . "\n";
        }
        if (isset($value['table'])) {
            $objects .= '   Table: ' . $prefix . $value['table'] . "\n";
        }

        if (isset($value['fields'])) {
            $objects .= '   Fields:' . "\n";
            $fields = $value['fields'];

            foreach ($fields as $field => $name) {
                $objects .= '      ' . $field;
                if (isset($value['fieldMeta'])) {
                    $objects .= ' (' . $value['fieldMeta'][$field]['phptype'] . ")\n";
                }
            }

        }

        if (isset($value['indexes'])) {
            $indexArray = array();
            foreach ($value['indexes'] as $index => $indexValue) {
                if ($index == 'sctive') {
                    $index = 'active';
                }
                $indexArray[] = $index;
            }
            /* wrap long index lists */
            $i = 1;
            $objects .= '   Indexes:';
            if (count($indexArray > 5)) {
                $indexArray = array_chunk($indexArray,ceil(count($indexArray)/5),true);
                foreach($indexArray as $indexList) {
                    $objects .=  "\n" .'        ' . implode(', ', $indexList);
                }
            } else {
                $objects .= '   Indexes: ' . implode(', ', $indexArray) . "\n";
            }



        }
        if (isset($value['aggregates']) || isset($value['composites'])) {
            $objects .= "\n" .'   Aliases:' . "\n";
        }
        if (isset($value['aggregates'])) {
            if (!empty($value['aggregates'])) {
                //$objects .= '   Aggregate Aliases:' . "\n";
                foreach ($value['aggregates'] as $aggregate => $aggregateValue) {
                    if (substr($aggregate,0,3) != 'mod') { /* skip legacy aliases */
                        $objects .= '      ' . $aggregate;
                        if ($aggregateValue['cardinality'] == 'one') {
                            $objects .= "\n" . '        -- use getOne(\'' . $aggregate . '\') -- returns a ' . $aggregateValue['class'] . ' object';
                        } else {
                            $objects .= "\n" . '        -- use getMany(\'' . $aggregate . '\') -- returns an array of ' . $aggregateValue['class'] . ' objects';
                        }
                        $objects .= "\n";
                    }
                }
            }
        }
        if (isset($value['composites'])) {
            if (!empty($value['composites'])) {
                //$objects .= '   Composite Aliases:' . "\n";
                foreach ($value['composites'] as $composite => $compositeValue) {
                    if (substr($composite,0,3) != 'mod') { /* skip legacy aliases */
                        $objects .= '      ' . $composite;
                        if ($compositeValue['cardinality'] == 'one') {
                            $objects .= "\n" . '        -- use getOne(\'' . $composite . '\') -- returns a ' . $compositeValue['class'] . ' object';
                        } else {
                            $objects .= "\n" . '        -- use getMany(\'' . $composite . '\') -- returns an array of ' . $compositeValue['class'] . ' objects';
                        }
                        $objects .= "\n";
                    }
                }
            }
        }
    }
} else {
    /* Do Full Reference */
    $jumpList = array();
    foreach ($model as $key => $value) {
        $jumpList[] = $key;

    }
    $a = print_r($model, true);
    /* fix typo in schema */
    $a = str_replace('sctive', 'active', $a);
    /* move '(' to end of line above */
    $a = preg_replace('/Array[\s\n\r]*\(/', " Array (", $a);
    /* remove outer array indicators */
    $a = substr($a, 8);
    $a = substr($a, 0, -2);
    /* delete empty lines */
    $a = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $a);

    /* move all ) characters 4 spaces left */
    $a = preg_replace('/\s{4}\)/', ')', $a);

    /* move main heads to left margin */

    $a = preg_replace("/\n\s{4}\[([a-zA-Z]*)\]/", "\n<a name=\"$1\"></a>" . $topJump . "<hr>\n<pre>\n[$1]", $a);
    $a = str_replace("\n    )", "\n)</pre>\n", $a);

    /* Move everything else over where it belongs */
    $t = '   '; /* tab width */


    $a = str_replace("\n            [", "\n{$t}[", $a);
    $a = str_replace("\n            )", "\n{$t})", $a);

    $a = str_replace("\n                    [", "\n{$t}{$t}[", $a);
    $a = str_replace("\n                    )", "\n{$t}{$t})", $a);

    $a = str_replace("\n                            [", "\n{$t}{$t}{$t}[", $a);
    $a = str_replace("\n                            )", "\n{$t}{$t}{$t})", $a);

    $a = str_replace("\n                                    [", "\n{$t}{$t}{$t}{$t}[", $a);
    $a = str_replace("\n                                    )", "\n{$t}{$t}{$t}{$t})", $a);

    $a = str_replace("\n                                            [", "\n{$t}{$t}{$t}{$t}{$t}[", $a);
    $a = str_replace("\n                                            )", "\n{$t}{$t}{$t}{$t}{$t})", $a);

}

echo $top;
echo '<h2>MODX Objects</h2>';
echo '<div  class="objectexplorer_jumplist" width="60%">' . "\n";

$numCols = 5;
$cols = array_chunk($jumpList,ceil(count($jumpList)/$numCols));
$rows = count($cols[0]);
$colNum = count($cols);

//return '<pre>' . print_r($cols) . '</pre>';

echo '<table class="objectexplorer_jumplist" cellpadding="2" cellspacing="7">' . "\n" ;
for($i = 0; $i < $rows; $i++) {
    echo '<tr>';
    for ($j = 0; $j < $colNum; $j++) {
        echo '<td>' .'<a href="[[~[[*id]]]]#'. @$cols[$j][$i] .'">' . @$cols[$j][$i] . '</a></td>';
    }

    echo "</tr>\n";
}
echo "</table>\n";



echo "\n</div>\n\n";
if ($quick) {
    /* take out the top "back to top" link */
    $objects = preg_replace('/<a href=.*back to top.*<\/a>/','',$objects,1);
    echo '<div class="reference">';
    echo "<pre>\n" . $objects . "\n</pre>";

} else {
    /* take out the top "back to top" link */
    $a = preg_replace('/<a href=.*back to top.*<\/a>/', '',$a, 1);
    echo '<div class="quick-reference">';
    echo $a;
}
echo '</div>';
unset($jumpList, $objects, $a);
// $modx->log(modX::LOG_LEVEL_INFO, 'FINISHED');
return '';

