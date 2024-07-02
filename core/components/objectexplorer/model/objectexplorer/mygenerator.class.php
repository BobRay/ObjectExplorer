<?php
/*
 * Copyright 2010-2024 by MODX, LLC.
 *
 * This file is adapted from a part of xPDO.
 * xPDO was created by Jason Coward
 *
 * xPDO is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * xPDO is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * xPDO; if not, write to the Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA 02111-1307 USA
 */

/**
 * Class for Parsing xPDO schema files.
 *
 * @package objectexplorer
  */

/**
 * Parses the MODX Schema file.
 *
 * @package objectexplorer
  */
   class MyGenerator {
    /**
     * @var xPDOManager $manager A reference to the xPDOManager using this
     * generator.
     */
    public $manager= null;

    /**
     * @var xmlParser $xmlParser
     */
    public $xmlParser= null;
    /**
     * @var string $outputDir The absolute path to output the class and map
     * files to.
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

    /**
     * Constructor
     *
     * @access protected
     * @param xPDOManager $manager A reference to a valid xPDOManager instance.
     * @return void
     */
    public function __construct($manager) {
        $this->manager= $manager;
    }

    /**
     * Formats a class name to a specific value, stripping the prefix if
     * specified.
     *
     * @access public
     * @param string $string The name to format.
     * @param string $prefix If specified, will strip the prefix out of the
     * first argument.
     * @param boolean $prefixRequired If true, will return a blank string if the
     * prefix specified is not found.
     * @return string The formatting string.
     */
    public function getTableName($string, $prefix= '', $prefixRequired= false) {
        if (!empty($prefix) && strpos($string, $prefix) === 0) {
            $string= substr($string, strlen($prefix));
        }
        elseif ($prefixRequired) {
            $string= '';
        }
        return $string;
    }

    /**
     * Gets a class name from a table name by splitting the string by _ and
     * capitalizing each token.
     *
     * @access public
     * @param string $string The table name to format.
     * @return string The formatted string.
     */
    public function getClassName($string) {
        if (is_string($string) && $strArray = explode('_', $string)) {
            $return= '';
            foreach ($strArray as $k => $v) {
                $return.= strtoupper(substr($v, 0, 1)) . substr($v, 1) . '';
            }
            $string= $return;
        }
        return trim($string);
    }


    /**
     * Parses an XPDO XML schema and generates classes and map files from it.
     *
     * @param string $schemaFile The name of the XML file representing the
     * schema.
     * @param string $outputDir The directory in which to generate the class and
     * map files into.
     * @param boolean $compile Create compiled copies of the classes and maps from the schema.
     * @return array | false schema file converted to a multi-dimensional array, false on error.
     */
    public function parseSchema($schemaFile, $outputDir= '', $compile= false) {
        $this->schemaFile= $schemaFile;
        //$this->classTemplate= $this->getClassTemplate();
        if (!is_file($schemaFile)) {
            $this->manager->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Could not find specified XML schema file {$schemaFile}");
            return false;
        } else {
            $fileContent= @ file($schemaFile);
            $this->schemaContent= implode('', $fileContent);
        }

        /* Fix bug in invalid MODX schema */
        $this->schemaContent = str_replace('<!\\s', '!\\s', $this->schemaContent);
        /* Create the parser and set handlers. */
        $this->xmlParser= xml_parser_create('UTF-8');

        xml_set_object($this->xmlParser, $this);
        xml_parser_set_option($this->xmlParser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($this->xmlParser, XML_OPTION_TARGET_ENCODING, 'UTF-8');
        xml_set_element_handler($this->xmlParser, '_handleOpenElement', '_handleCloseElement');
        xml_set_character_data_handler($this->xmlParser, "_handleCData");

        /* Parse it. */
        if (!xml_parse($this->xmlParser, $this->schemaContent)) {
            $ln= xml_get_current_line_number($this->xmlParser);
            $msg= xml_error_string(xml_get_error_code($this->xmlParser));
            $this->manager->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Error parsing XML schema on line $ln: $msg");
            return false;
        }

        /* Free up the parser and clear memory */
        xml_parser_free($this->xmlParser);
        unset ($this->xmlParser);

    return $this->map;

    }

    /**
     * Handles formatting of the open XML element.
     *
     * @access protected
     * @param xmlParser $parser
     * @param string $element
     * @param array $attributes
     */
    protected function _handleOpenElement($parser, $element, $attributes) {
        $element= strtolower($element);
        switch ($element) {
            case 'model' :
                foreach ($attributes as $attrName => $attrValue) {
                    $this->model[$attrName]= $attrValue;
                }
                break;
            case 'object' :
                foreach ($attributes as $attrName => $attrValue) {
                    switch ($attrName) {
                        case 'class' :
                            $this->className= "{$attrValue}";
                            if (!isset ($this->classes[$this->className])) {
                                $this->classes[$this->className]= array ();
                                $this->map[$this->className]= array ();
                                if(isset($attributes['extends'])) {
                                    $extends = $attributes['extends'];
                                }
                                $this->classes[$this->className]['extends']= $this->model['baseClass'];
                                /* my addition - put extends info in map array */
                                //$this->map[$this->className]['extends']= $this->model['baseClass'];
                                if ($extends) {
                                    $this->map[$this->className]['extends'] = $extends;
                                } else {
                                    $this->map[$this->className]['extends'] = $this->model['baseClass'];
                                }
                                /* ********* */
                            }
                            if (isset ($this->model['package'])) {
                                $this->map[$this->className]['package']= $this->model['package'];
                            }
                            if (isset ($this->model['version'])) {
                                $this->map[$this->className]['version']= $this->model['version'];
                            }
                            break;
                        case 'table' :
                            $this->map[$this->className]['table']= $attrValue;
                            break;
                        case 'extends' :
                            $this->classes[$this->className]['extends']= $attrValue;
                            break;
                        default:
                            $this->classes[$this->className][$attrName]= $attrValue;
                            break;
                    }
                }
                break;
            case 'field' :
                $dbtype = 'varchar';
                foreach ($attributes as $attrName => $attrValue) {
                    switch ($attrName) {
                        case 'key' :
                            $this->fieldKey= "{$attrValue}";
                            $this->map[$this->className]['fields'][$this->fieldKey]= null;
                            $this->map[$this->className]['fieldMeta'][$this->fieldKey]= array ();
                            break;
                        case 'default' :
                            $attrValue = ($attrValue === 'NULL' ? null : $attrValue);
                            switch ($this->manager->xpdo->driver->getPhpType($dbtype)) {
                                case 'integer':
                                case 'boolean':
                                case 'bit':
                                    $attrValue = (integer) $attrValue;
                                    break;
                                case 'float':
                                case 'numeric':
                                    $attrValue = (float) $attrValue;
                                    break;
                                default:
                                    break;
                            }
                            $this->map[$this->className]['fields'][$this->fieldKey]= $attrValue;
                            $this->map[$this->className]['fieldMeta'][$this->fieldKey]['default']= $attrValue;
                            break;
                        case 'null' :
                            $attrValue = ($attrValue && $attrValue !== 'false' ? true : false);
                        default :
                            if ($attrName == 'dbtype') $dbtype = $attrValue;
                            $this->map[$this->className]['fieldMeta'][$this->fieldKey][$attrName]= $attrValue;
                            break;
                    }
                }
                break;
            case 'index' :
                $node= array ();
                foreach ($attributes as $attrName => $attrValue) {
                    switch ($attrName) {
                        case 'name':
                            $this->indexName= $attrValue;
                            break;
                        case 'primary':
                        case 'unique':
                        case 'fulltext':
                            $attrValue = (empty($attrValue) || $attrValue === 'false' ? false : true);
                        default:
                            $node[$attrName] = $attrValue;
                            break;
                    }
                }
                if ($node) {
                    $node['columns']= array();
                    $this->map[$this->className]['indexes'][$this->indexName]= $node;
                }
                break;
            case 'column' :
                $key = '';
                $node = array ();
                foreach ($attributes as $attrName => $attrValue) {
                    switch ($attrName) {
                        case 'key':
                            $key= $attrValue;
                            break;
                        case 'null':
                            $attrValue = (empty($attrValue) || $attrValue === 'false' ? false : true);
                        default:
                            $node[$attrName]= $attrValue;
                            break;
                    }
                }
                if ($key) {
                    $this->map[$this->className]['indexes'][$this->indexName]['columns'][$key]= $node;
                }
                break;
            case 'aggregate' :
                $alias= '';
                $node= array ();
                foreach ($attributes as $attrName => $attrValue) {
                    switch ($attrName) {
                        case 'alias' :
                            $alias= "{$attrValue}";
                            break;
                        default :
                            $node[$attrName]= $attrValue;
                            break;
                    }
                }
                if ($alias && $node) {
                    $this->map[$this->className]['aggregates'][$alias]= $node;
                }
                break;
            case 'composite' :
                $alias= '';
                $node= array ();
                foreach ($attributes as $attrName => $attrValue) {
                    switch ($attrName) {
                        case 'alias' :
                            $alias= "{$attrValue}";
                            break;
                        default :
                            $node[$attrName]= $attrValue;
                            break;
                    }
                }
                if ($alias && $node) {
                    $this->map[$this->className]['composites'][$alias]= $node;
                }
                break;
            case 'validation' :
                $node= array ();
                foreach ($attributes as $attrName => $attrValue) {
                    $node[$attrName]= $attrValue;
                }
                if ($node) {
                    $node['rules']= array();
                    $this->map[$this->className]['validation']= $node;
                }
                break;
            case 'rule' :
                $field= '';
                $name= '';
                $node= array ();
                foreach ($attributes as $attrName => $attrValue) {
                    switch ($attrName) {
                        case 'field' :
                            $field= "{$attrValue}";
                            break;
                        case 'name' :
                            $name= "{$attrValue}";
                            break;
                        default :
                            $node[$attrName]= $attrValue;
                            break;
                    }
                }
                if ($field && $name && $node) {
                    $this->map[$this->className]['validation']['rules'][$field][$name]= $node;
                }
                break;
        }
    }

    /**
     * Handles the closing of XML tags.
     *
     * @access protected
     * @param xmlParser $parser
     * @param string $element
     */
    protected function _handleCloseElement($parser, $element) {}

    /**
     * Handles the XML CDATA tags
     *
     * @access protected
     * @param xmlParser $parser
     * @param string $data
     */
    protected function _handleCData($parser, $data) {}

}
