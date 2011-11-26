<?php
/**
 * ObjectExplorer
 * Copyright 2011 Bob Ray
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
 * ObjectExplorer; if not, write to the Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA 02111-1307 USA
 *
 * @package ObjectExplorer
 * @author Bob Ray <http://bobsguides.com>
 
 *
 * Description: The ObjectExplorer snippet presents a form in the front end for
 * creating resources. Rich text editing is available for text fields and TVs.
 **/

/**
 * Class for retrieving and displaying elements of xPDO schema files.
 *
 * @package objectexplorer
  */

/**
 * Parses the MODX Schema file.
 *
 * @package objectexplorer
  */
   class ObjectExplorer {
    /**
     * @var xPDOManager $manager A reference to the xPDOManager using this
     * generator.
     */
    public $manager= null;
    /**
     * @var xPDOSchemaManager $schemaManager
     */
    public $schemaManager= null;
    /**
     * @var xmlParser $xmlParser
     */
    public $xmlParser= null;
    /**
     * @var string $outputDir The absolute path to output the class and map
     * files to.
     */
    public $outputDir= '';
    /**
     * @var string $schemaFile An absolute path to the schema file.
     */
    public $schemaFile= '';
    /**
     * @var string $schemaContent The stored content of the newly-created schema
     * file.
     */
    public $schemaContent= '';
    /**
     * @var string $classTemplate The class template string to build the class
     * files from.
     */
    public $classTemplate= '';
    /**
     * @var string $platformTemplate The class platform template string to build
     * the class platform files from.
     */
    public $platformTemplate= '';
    /**
     * @var string $mapHeader The map header string to build the map files from.
     */
    public $mapHeader= '';
    /**
     * @var string $mapFooter The map footer string to build the map files from.
     */
    public $mapFooter= '';
    /**
     * @var array $model The stored model array.
     */
    public $model= array ();
    /**
     * @var array $classes The stored classes array.
     */
    public $classes= array ();
    /**
     * @var array $map The stored map array.
     */
    public $map= array ();

    /**
     * @var string $className A placeholder for the current class name.
     */
    public $className= '';
    /**
     * @var string $fieldKey A placeholder for the current field key.
     */
    public $fieldKey= '';
    /**
     * @var string $indexName A placeholder for the current index name.
     */
    public $indexName= '';

    protected $jumpList = array();

    /**
     * Constructor
     *
     * @access protected
     * @param xPDOObject &$modx MODX object
     * @param xPDOManager &$manager A reference to a valid xPDOManager instance.
     * @param array &$jumpList Jump list array for top of page
     * @param array &$ props scriptProperties array
     * @return parsed schema
     */
    public function __construct(&$modx, &$model, &$props) {
        $this->modx =& $modx;
        $this->model =& $model;
        $this->props =& $props;


    }
       /**
        * @return array $jumpList
        */
    public function getJumplist() {
        $jumpList = array();
        foreach ($this->model as $key => $value) {
            $jumpList[] = $key;

        }
        return $jumpList;
    }
    /**
     * Return displayable quick reference as string
     * @return string displayable quick reference
     */
    public function getQuickDisplay() {
            $objects = '';

        /* MODX DB table prefix */
        $prefix = $this->modx->getOption('table_prefix');
        

        /* build the output from the $model array */
        foreach ($this->model as $key => $value) {
            $objects .= '<a name="' . $key . '"></a>' . "\n";
            $objects .= $this->props['topJump'];
            //$jumpList[] = $key;
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
        /* take out the top "back to top" link */
        $objects = preg_replace('/<a href=.*back to top.*<\/a>/','',$objects,1);
        return $objects;
    }
    public function getFullDisplay() {
    /* Do Full Reference */
    $jumpList = array();
    foreach ($this->model as $key => $value) {
        $jumpList[] = $key;

    }
    $a = print_r($this->model, true);
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

    $a = preg_replace("/\n\s{4}\[([a-zA-Z]*)\]/", "\n<a name=\"$1\"></a>" . $this->props['topJump'] . "<hr>\n<pre>\n[$1]", $a);
    $a = str_replace("\n    )", "\n)</pre>\n", $a);

    /* Move everything else over where it belongs */
    $t = $this->props['tab']; /* tab width */


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
    /* take out the top "back to top" link */
    $a = preg_replace('/<a href=.*back to top.*<\/a>/', '',$a, 1);

    return $a;
    }
    public function getJumplistDisplay() {
        if (empty($this->jumpList)) {
            $this->jumpList = $this->getJumpList();
        }
        $output = '<div  class="objectexplorer_jumplist" width="60%">' . "\n";

        $numCols = 5;
        $cols = array_chunk($this->jumpList,ceil(count($this->jumpList)/$numCols));
        $rows = count($cols[0]);
        $colNum = count($cols);

        //return '<pre>' . print_r($cols) . '</pre>';

        $output .= '<table class="objectexplorer_jumplist" cellpadding="2" cellspacing="7">' . "\n" ;
        for($i = 0; $i < $rows; $i++) {
            $output .= '<tr>';
            for ($j = 0; $j < $colNum; $j++) {
                $output .= '<td>' .'<a href="[[~[[*id]]]]#'. @$cols[$j][$i] .'">' . @$cols[$j][$i] . '</a></td>';
            }

            $output .= "</tr>\n";
        }
        $output .= "</table>\n";
        $output .= "\n</div>\n\n";
        return $output;
    }

}