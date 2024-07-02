<?php
/**
 * ObjectExplorer
 * Copyright 2011-2024 Bob Ray
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
 * @author Bob Ray <https://bobsguides.com>

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
    protected $output;

    public $manager= null;

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
    /** @var $modx modX */
    public $modx;

    /** @var $props array -- scriptProperties array */
    public $props;

    protected $jumpList = array();

    /**
     * Constructor
     *
     * @access protected
     * @param xPDOObject &$modx MODX object
     * @param array &$model array created by parseSchema()
     * @param array &$props scriptProperties array
     */
    public function __construct(&$modx, &$model, &$props) {
        $this->modx =& $modx;
        $this->model =& $model;
        $this->props =& $props;
    }
       /**
        * Creates jumpList array of object names from schema.
        * Puts results in $this->jumpList
        */
    public function createJumplist() {
        $this->jumpList = array();
        foreach ($this->model as $key => $value) {
            $this->jumpList[] = $key;
        }
    }

    /** Produces jumplist string
     * @return string;
     */
    public function getJumplistDisplay() {
        if (empty($this->jumpList)) {
            $this->createJumplist();
        }
        $output = '';
        $numCols = $this->props['columns'];
        $cols = array_chunk($this->jumpList,ceil(count($this->jumpList)/$numCols));
        $rows = count($cols[0]);
        $colNum = count($cols);

        //return '<pre>' . print_r($cols) . '</pre>';


        for($i = 0; $i < $rows; $i++) {
            $output .= '<div class="oe_row">';
            for ($j = 0; $j < $colNum; $j++) {
                $item = @$cols[$j][$i];
                if (!empty($item)) {
                    $output .= '<div class="oe_cell">' .'<a href="[[~[[*id]]]]#'. $item .'">' . $item . '</a></div>';
                }
            }
            $output .= "</div>\n";
        }
        return $output;
    }


/**
     * Return displayable quick reference as string
     * @param string $objectName
     * @return string displayable quick reference for a single object
     */
    public function getQuickSingle($objectName) {
            $objects = '';

        /* MODX DB table prefix */
        $prefix = $this->modx->getOption('table_prefix');


        /* build the output from the $model array */
        //foreach ($this->model as $key => $value) {
        $key = $objectName;
        $value = $this->model[$objectName];
        $objects .= '<a name="' . $key . '"></a>' . "\n";
        //$objects .= $this->props['topJump'];
        $objects .= "\n<h3>" . $key .  "</h3>\n<pre>";

        if (isset($value['extends'])) {
            $objects .= "\n" . '   ' . $this->modx->lexicon('oe_extends') . ': ' . $value['extends'];

            $parentFields = $this->getInheritedFields(array(), $value['extends']);
            //$x = print_r($parentFields, true);
            //$objects .= "\nDUMP:\n" . $x . "\n\n";

        }
        if (isset($value['table'])) {
            $objects .= "\n" . '   ' . $this->modx->lexicon('oe_table') . ': <fixedpre>' . $prefix . $value['table'] . "</fixedpre>\n";
        }

        if (isset($value['fields']) || (isset($parentFields) && (!empty($parentFields)))) {
            $objects .= "\n" . '   ' . $this->modx->lexicon('oe_fields') . ':' . "\n";
            if (isset($parentFields) && is_array($parentFields)) {
                foreach($parentFields as $fieldName => $data) {
                    $objects .= '      <span class="oe_field" >' . $fieldName . '</span> ' . $data . "\n";
                }
            }

            $fields = isset($value['fields'])? $value['fields']: array();


           // $fields = is_array($fields)? $fields : array();

            foreach ($fields as $field => $name) {
                $objects .= '      <span class="oe_field" >'  . $field . '</span> ';

                if (isset($value['fieldMeta']) && in_array($field, array_keys($value['fields']),true)) {
                    if (isset($value['fieldMeta'][$field]['phptype'])) {
                        $objects .= ' (' . $value['fieldMeta'][$field]['phptype'] . ")\n";
                        // $objects .= ' (' . getType($name) . ")\n";
                    } else {
                        $objects .= "\n";
                    }
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

            $objects .= '   ' . $this->modx->lexicon('oe_indexes') . ':';
            if (count($indexArray) > 5) {
                $indexArray = array_chunk($indexArray,ceil(count($indexArray)/5),true);
                foreach($indexArray as $indexList) {
                    $objects .=  "\n" .'        ' . implode(', ', $indexList);
                }
            } else {
                $objects .= '   '. $this->modx->lexicon('oe_indexes') . ': ' . implode(', ', $indexArray) . "\n";
            }



        }
        if (isset($value['aggregates']) || isset($value['composites'])) {
            $objects .= "\n" .'   ' . $this->modx->lexicon('oe_aliases') . ':' . "\n";
        }
        if (isset($value['aggregates'])) {
            if (!empty($value['aggregates'])) {
                //$objects .= '   Aggregate Aliases:' . "\n";
                foreach ($value['aggregates'] as $aggregate => $aggregateValue) {
                    if (substr($aggregate,0,3) != 'mod') { /* skip legacy aliases */
                        $objects .= '      ' . $aggregate;
                        if ($aggregateValue['cardinality'] == $this->modx->lexicon('oe_one')) {
                            $objects .= "\n" . '        -- ' . $this->modx->lexicon('oe_use_getOne') . '(\'' . $aggregate . '\') -- ' . $this->modx->lexicon('oe_returns_a') . ' ' . $aggregateValue['class'] . ' ' . $this->modx->lexicon('oe_object');
                        } else {
                            $objects .= "\n" . '        -- ' . $this->modx->lexicon('oe_use_getMany') . '(\'' . $aggregate . '\') -- ' . $this->modx->lexicon('oe_returns_an_array_of') . ' ' . $aggregateValue['class'] . ' ' . $this->modx->lexicon('oe_object') . 's';
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
                        $objects .= "\n" . '      ' . $composite;
                        if ($compositeValue['cardinality'] == $this->modx->lexicon('oe_one')) {
                            $objects .= "\n" . '        -- ' . $this->modx->lexicon('oe_use_getOne') . '(\'' . $composite . '\') -- ' . $this->modx->lexicon('oe_returns_a')  . '' . $compositeValue['class'] . ' ' . $this->modx->lexicon('oe_object');
                        } else {
                            $objects .= "\n" . '        -- ' . $this->modx->lexicon('oe_use_getMany') . '(\'' . $composite . '\') -- ' . $this->modx->lexicon('oe_returns_an_array_of') . ' ' . $compositeValue['class'] . ' ' . $this->modx->lexicon('oe_object') . 's';
                        }
                        //$objects .= "\n";
                    }
                }
            }
        }

        /* take out the top "back to top" link */
        $objects = preg_replace('/<a href=.*back to top.*<\/a>/','',$objects,1);
        return $objects . "\n</pre>\n";
    }
    public function getFullSingle($objectName) {
     /* Do Full Reference  for single object*/
    $this->output  = "\n" .'<a name="' . $objectName . '"></a>';
    $this->output .= "\n<h3>" . $objectName . "</h3>\n<pre>";
    $this->doArray($this->model[$objectName], 1);
    $this->output .= "\n</pre>\n";
    return $this->output;
    }

    public function getInheritedFields($fields, $class, $i = -1) {

        if ($i == -1) {
            $fields = array();
            $i++;
        }
            $parent = isset($this->model[$class]) ? $this->model[$class] : array();
            if ($parent && isset($parent['fields'])) {
                $newFields = isset($parent['fields'])? $parent['fields'] : array();
                if (is_array($newFields)) {
                    foreach($newFields as $key => $value) {
                        $type = '';
                        if (isset($parent['fieldMeta'][$key]['phptype'])) {
                            $type = '(' . $parent['fieldMeta'][$key]['phptype'] . ')';
                            //$objects .= ' (' . $value['fieldMeta'][$field]['phptype'] . ")\n";

                            // $objects .= ' (' . getType($name) . ")\n";
                        }
                        $newFields[$key] = $type . ' - ' . $this->modx->lexicon('oe_inherited_from') . ' ' . '<fixedpre>' . $class . '</fixedpre>';
                    }
                   $fields = array_merge($newFields, $fields);
                }
                /*foreach ($newFields as $key => $value) {
                    $fields[$key] = $value;
                }*/
                if (isset($parent['extends'])) {
                    $fields = $this->getInheritedFields($fields, $parent['extends'], $i);
                }
            }


        return $fields;
    }

    /**
     * Recursive function to print the array
     * Puts results in $this->output;
     * @param array $model The big array of classes
     * @param int $level Sets indentation level
     * @return void
     */
    public function doArray($model, $level) {
        foreach($model as $key => $value) {
            $tab = '    ';
            for ($i=1; $i < $level; $i++) {
                $tab .= '    ';
            }
            if (is_array($value)) {
                $key = $key=='sctive'? 'active': $key;
                $this->output .= "\n" . $tab .  '[' . $key . ']';
                $this->doArray($value, $level + 1);
            } else {
                $this->output .= "\n" . $tab  . '['. $key . '] => ' . $value;
            }
        }

    }

   /**
    * @return array array of object names from the schema
    */
    public function getJumpList() {
        if (empty($this->jumpList)) {
            $this->createJumplist();
        }
        return $this->jumpList;
    }
}
