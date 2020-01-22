<?php
/**
 * Created by PhpStorm.
 * User: wjacobsen
 * Date: 5/6/16
 * Time: 11:32 AM
 */

namespace App\Core\Utility\Pageflex;
    /**
     * Pageflex and Mpower API
     * @package PageflexPHP
     * @version $Id$
     */

/**
 * The Variable class manages the properties of Pageflex variable.
 *
 * Supported variable types are
 * - Constant variables
 * - Variables bound to a datasource field
 * - Mapped values. Maps source values (extracted from another variable)
 * to destination values with a fallback default value (this is a restricted
 * type of rule based variable as these are known in Pageflex Studio).
 *
 * Note - error conditions are always returned through a PHP Exception
 *
 */

class Variable extends Object
{
    // Constants for $kind member
    const TEXT = 1;
    const IMAGE = 2;
    const FORMATTED_TEXT = 3;

    // Variable name
    private $_name;
    // Kind of variable (either TEXT, FORMATTED_TEXT or IMAGE)
    private $_kind;
    // Child variable type (var_const, var_data_source, var_rule, var_script, etc...)
    private $_childType;
    // Attributes for type of variable (OPTIONAL: used with var_script variable type)
    private $_childAttributes = array(
        'language' => null,
        'function_name' => null,
        'parameters' => null
    );
    // Value of variable (for isFixed==true variables)
    private $_value;
    // When set, this is a constant variable
    private $_isFixed;
    // When set, this is an optional variable (its value does not have to be present)
    private $_isOptional;
    // When set, the variable's value refers to a file (name)
    private $_isFileReference;
    // For bound variables, name of the database column from which the variable's value has to be extracted.
    private $_dbColumn;
    // For bound variables, name of the database table from which the variable's value has to be extracted.
    private $_dbTable;
    // For bound variables, name of the datasource from which the variable's value has to be extracted.
    private $_dbDb;
    // In case of a mapped value, the name of the "source" variable that will
    // provide values for this variable before mapping
    private $_mappedVarName;
    // Array with two elements
    //   The default value, when the value to map does not match with any of the provided source values.
    //   The second element is a nested array which maps source to target values.
    private $_mappedValues;

    /**
     * Constructor
     *
     * @param string $name Variable name
     *
     */
    public function __construct($name)
    {
        $this->_name = $name;
    }

    /**
     * Return variable name
     *
     * @return string Name of the variable
     *
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Set name of the variable
     *
     * @param string $name Name of the variable
     *
     */
    public function setName($name)
    {
        $this->_name = $name;
    }

    /**
     * Get "kind" property of the variable
     *
     * @return int Kind of the variable (either PFVariable::TEXT or PFVariable::IMAGE)
     */
    public function getKind()
    {
        return $this->_kind;
    }

    /**
     * Set variable kind
     *
     * @param int $kind Variable kind (either PFVariable::TEXT or PFVariable::IMAGE)
     *
     */
    public function setKind($kind)
    {
        $this->_kind = $kind;
    }

    /**
     * Return true if this variable's value should be interpreted as a file name
     *
     * @return boolean true when this variable's value should be interpreted as a file name
     *
     */
    public function isFileReference()
    {
        return $this->_isFileReference;
    }

    /**
     * Set's variable file reference property.
     *
     * If true, the variable's value will be interpreted as a reference to a file.
     * @param $isFileReference
     */
    public function setIsFileReference($isFileReference)
    {
        $this->_isFileReference = $isFileReference;
    }

    /**
     * Return true when the variable's value is fixed (does not depend on a database column)
     *
     * @return boolean true when this variable's value is fixed.
     */
    public function isFixed()
    {
        return $this->_isFixed;
    }

    /**
     * Set the fixed property of this variable.
     *
     * When true, the variable's value is fixed (does not depend on a database column)
     * @param $isFixed
     */
    public function setIsFixed($isFixed)
    {
        $this->_isFixed = $isFixed;
    }

    /**
     * Return variable's value. Only useful when isFixed() returns true
     *
     * @return string Value for this value
     *
     */
    public function getValue()
    {
        return $this->_value;
    }

    /**
     * Set variable's value. Only useful when isFixed() returns true.
     *
     * @param boolean $value true or false
     *
     */
    public function setValue($value)
    {
        $this->_value = $value;
    }

    public function setScript($script)
    {
        $this->_childType = 'var_script';
        //e.g. language=VBS&function_name=BreakToMultiLine&parameters=FrontMessage1,"12","";
        parse_str($script);
        $this->_childAttributes['language'] = $language;
        $this->_childAttributes['function_name'] = $function_name;
        $this->_childAttributes['parameters'] = $parameters;
    }

    /**
     * Return this variable's datasource name
     *
     * Only relevant for bound variables.
     *
     * @return string datasource name
     *
     */
    public function getDbDb()
    {
        return $this->_dbDb;
    }

    /**
     * Set this variable's datasource name.
     *
     * Only relevant for bound variables.
     *
     * @param string $dbDb name of the datasource
     *
     */
    public function setDbDb($dbDb)
    {
        $this->_dbDb = $dbDb;
    }

    /**
     * Get name of the datasource table from which the variable's value has to be extracted.
     *
     * Only relevant for bound variables.
     *
     * @return string Name of the datasource table for this variable
     *
     */
    public function getDbTable()
    {
        return $this->_dbTable;
    }

    /**
     * Set name of the datasource table from which the variable's value has to be extracted.
     *
     * Only relevant for bound variables.
     *
     * @param string $dbTable Name of the datasource table for this variable
     *
     */
    public function setDbTable($dbTable)
    {
        $this->_dbTable = $dbTable;
    }

    /**
     * Get name of the datasource column from which the variable's value has to be extracted.
     *
     * Only relevant for bound variables.
     *
     * @return string Name of the datasource column for this variable
     *
     */
    public function getDbColumn()
    {
        return $this->_dbColumn;
    }

    /**
     * Set name of the datasource column from which the variable's value has to be extracted.
     *
     * Only relevant for bound variables.
     *
     * @param string $dbColumn Name of the datasource column for this variable
     *
     */
    public function setDbColumn($dbColumn)
    {
        $this->_dbColumn = $dbColumn;
    }

    /**
     * Return name of variable for which this variable contains mapping information (rules)
     *
     * Only relevant for mapped variables
     *
     * @return string Name of variable for which this variable contains mapping information
     */
    public function getMappedVarName()
    {
        return $this->_mappedVarName;
    }


    /**
     * Set name of variable for which this variable contains mapping information (rules)
     *
     * Only relevant for mapped variables
     *
     * @param string $mappedVarName Name of variable for which this variable contains mapping information
     */
    public function setMappedVarName($mappedVarName)
    {
        $this->_mappedVarName = $mappedVarName;
    }

    /**
     * Get variable's mapped value
     *
     * Only relevant for mapped variables
     *
     * This is an array with two elements
     *   The default value, when the value to map does not match with any of the provided source values.
     *   The second element is an array which maps source to target values.
     *
     * @return array With mapping information as described
     */
    public function getMappedValues()
    {
        return $this->_mappedValues;
    }

    /**
     * Set variable's mapped value
     *
     * Only relevant for mapped variables
     *
     * This is an array with two elements
     *   The default value, when the value to map does not match with any of the provided source values.
     *   The second element is an array which maps source to target values.
     *
     * @param $mappedValues
     * @return void With mapping information as described
     */
    public function setMappedValues($mappedValues)
    {
        $this->_mappedValues = $mappedValues;
    }

    /**
     * Return whether this variables value is required for rendering or optional.
     *
     * @return boolean true when variable is optional
     */
    public function isOptional()
    {
        return $this->_isOptional;
    }

    /**
     * Set whether this variables value is required for rendering or optional.
     *
     * @param boolean $isOptional
     */
    public function setIsOptional($isOptional)
    {
        $this->_isOptional = $isOptional;
    }

    /**
     * Read variable information from PageFlex Project File "var" element passed in as $varElement parameter
     *
     * @ignore
     * @param \DOMElement $varElement
     * @throws \Exception
     */
    public function fromDom(\DOMElement $varElement)
    {
        // var/@Kind =  kind of variable. Should be Text or Image
        $this->_value = trim($this->_value);

        $kind = $varElement->getAttribute("kind");
        if ($kind == "Text") {
            $this->_kind = self::TEXT;
        } else {
            if ($kind == "Image") {
                $this->_kind = self::IMAGE;
            } else {
                if ($kind == "FormattedText") {
                    $this->_kind = self::FORMATTED_TEXT;
                } else {
                    throw new \Exception(
                        sprintf(
                            "[PFVariable::fromDom]Variables of type %s" .
                            " are not supported", $kind
                        )
                    );
                }
            }
        }
        // var/@as_file - file reference or not (should be Yes or No)
        $asFile = $varElement->getAttribute("as_file");
        if ($asFile == "No") {
            $this->_isFileReference = false;
        } else {
            if ($asFile == "Yes") {
                $this->_isFileReference = true;
            } else {
                throw new \Exception(
                    sprintf(
                        "[PFVariable::fromDom]Variables with as_file" .
                        " equal to %s are not supported",
                        $asFile
                    )
                );
            }
        }
        // var/@ok_no_val
        $okNoVal = $varElement->getAttribute("ok_no_val");
        if ($okNoVal == "Yes") {
            $this->_isOptional = true;
        } else {
            if ($okNoVal == "No") {
                $this->_isOptional = false;
            } else {
                throw new \Exception(
                    sprintf(
                        "[PFVariable::fromDom]Variables with ok_no_val equal to %s are not supported",
                        $okNoVal
                    )
                );
            }
        }

        //var element content
        $this->_isFixed = false;
        $varChildren = $varElement->getElementsByTagName("*");
        $varChild = $varChildren->item(0);
        $this->_childType = $varChild->nodeName;

        if ($varChild->nodeName == "var_const") {
            // Variable has fixed value. The value is the content of the var_const element
            $this->_isFixed = true;
            $this->_value = $varChild->nodeValue;

        } else {
            if ($varChild->nodeName == "var_data_source") {
                // Variable value mapped from database
                //  var/var_data_source/@source is reference to a PageFLex data source
                $this->_dbDb = $varChild->getAttribute("source");
                //  var/var_data_source/@table is the table in the data source containing the variable values
                $this->_dbTable = $varChild->getAttribute("table");
                //  var/var_data_source/@field is the name of the database column containing the variable values
                $this->_dbColumn = $varChild->getAttribute("field");
                if (!$this->_dbDb || !$this->_dbTable || !$this->_dbColumn) {
                    throw new \Exception(
                        sprintf(
                            "[PFVariable::fromDom]Variable with name %s" .
                            " has unsupported var_data_source content",
                            $this->_name
                        )
                    );
                }

            } else {
                if ($varChild->nodeName == "var_rule") {
                    // Variable value is the result of rule
                    // This is trickier. The XML to interpret should look like
                    /*
                       <var_rule>
                       <query_line category="&lt;City>" qualifier="Is" logical_op="None">Boston</query_line>
                       <var_rule_result>Boston.gif</var_rule_result>
                       <query_line category="&lt;City>" qualifier="Is" logical_op="None">Antwerp</query_line>
                       <var_rule_result>Antwerp.gif</var_rule_result>
                       <var_rule_result>City.gif</var_rule_result>
                       </var_rule>
                     */
                    // Note that only a 1-to-1 mapping is supported, this means'
                    // 1. All the different category attributes have to refer to the same variable name
                    // 2. Only Is qualifiers are allowed
                    // 3. Only None for logical_op is accepted.

                    // Iterate over all var_rule children
                    $rules = $varChild->getElementsByTagName("*");

                    $sourceVarName = null;
                    $sourceVal = null;
                    $defaultValMap = null;
                    $valMap = array();
                    for ($i = 0; $i < $rules->length; $i++) {
                        $currElem = $rules->item($i);
                        if ($currElem->nodeName == "query_line") {
                            // Check category,qualifier and logical_op attributes
                            $category = $currElem->getAttribute("category");
                            $qualifier = $currElem->getAttribute("qualifier");
                            $logicalOp = $currElem->getAttribute("logical_op");
                            if ($sourceVarName == null) {
                                // This is the first query_line element being handled ==> store @category (== mapped variable)
                                $sourceVarName = $category;
                            } else {
                                if ($sourceVarName != $category) {
                                    // This is not the first query_line element being
                                    //handled and category content does not match first one
                                    //==> bail out
                                    throw new \Exception(
                                        sprintf(
                                            "[PFVariable::fromDom]Variable with name %s" .
                                            " has unsupported rules based value", $this->_name
                                        )
                                    );
                                }
                            }
                            if ($qualifier != "Is" || $logicalOp != "None") {
                                throw new \Exception(
                                    sprintf(
                                        "[PFVariable::fromDom]Variable with name %s" .
                                        " has unsupported rules based value", $this->_name
                                    )
                                );
                            }
                            // So all is well - keep query_line value as "source" value
                            $sourceVal = $currElem->nodeValue;
                        } else {
                            if ($currElem->nodeName == "var_rule_result") {
                                $mappedTo = $currElem->nodeValue;
                                if ($sourceVal != null) {
                                    // Map previous source_val to var_rule_result
                                    $valMap[$sourceVal] = $mappedTo;
                                    $sourceVal = null;
                                } else {
                                    // No previous sourceVal, so this is the "default" case
                                    $defaultValMap = $mappedTo;
                                }

                            } else {
                                // No idea what to do with this, bail out.
                                throw new \Exception(
                                    sprintf(
                                        "[PFVariable::fromDom]Variable with name %s" .
                                        " has unsupported rules based value", $this->_name
                                    )
                                );
                            }
                        }
                    }
                    // Build members (mappedVarName is stored in Category as <name>, so get rid of leading < and trailing >
                    $this->_mappedVarName = substr($sourceVarName, 1, strlen($sourceVarName) - 2);
                    // First entry in mappedValues array is the default case
                    $this->_mappedValues[0] = $defaultValMap;
                    // Second entry in the mappedValues array is the mapping from source value to mapped value
                    $this->_mappedValues[1] = $valMap;
                } else {
                    if ($varChild->nodeName == "var_script") {
                        $this->_value = $varChild->nodeValue;
                        $this->_childAttributes['language'] = $varChild->getAttribute("language");
                        $this->_childAttributes['function_name'] = $varChild->getAttribute("function_name");
                        $this->_childAttributes['parameters'] = $varChild->getAttribute("parameters");
                    } else {
                        throw new \Exception(
                            sprintf(
                                "[PFVariable::fromDom]Variable %s is of an unsupported kind", $this->_name
                            )
                        );
                    }
                }
            }
        }
        // Clear user properties
        $this->userProperties = null;

    }

    /**
     * Create DOM element for this variable and hook it under the given $parent
     *
     * @ignore
     * @param \DOMElement $parent
     */
    public function toDom(\DOMElement $parent)
    {
        /* <var>  */

        $this->_value = trim($this->_value);

        $varEl = $parent->ownerDocument->createElement("var");
        $parent->appendChild($varEl);
        // var/@kind
        if ($this->_kind == Variable::TEXT) {
            $varEl->setAttribute("kind", "Text");
        } else {
            if ($this->_kind == Variable::FORMATTED_TEXT) {
                $varEl->setAttribute("kind", "FormattedText");
            } else {
                $varEl->setAttribute("kind", "Image");
            }
        }
        // var/@ok_no_val
        if ($this->_isOptional) {
            $varEl->setAttribute("ok_no_val", "Yes");
        } else {
            $varEl->setAttribute("ok_no_val", "No");
        }
        // var/@as_file
        if ($this->_isFileReference) {
            $varEl->setAttribute("as_file", "Yes");
        } else {
            $varEl->setAttribute("as_file", "No");
        }
        // var/#@name
        $varEl->setAttribute("name", $this->_name);
        // var content

        if ($this->_isFixed) {
            // var_const
            $varConstEl = $parent->ownerDocument->createElement("var_const", htmlspecialchars($this->_value));
            $varEl->appendChild($varConstEl);

        } else {
            if ($this->getDbDb()) {
                // var_data_source
                $varDataSourceEl = $parent->ownerDocument->createElement("var_data_source");
                $varEl->appendChild($varDataSourceEl);
                // source, table and field attributes
                $varDataSourceEl->setAttribute("source", $this->_dbDb);
                $varDataSourceEl->setAttribute("table", $this->_dbTable);
                $varDataSourceEl->setAttribute("field", $this->_dbColumn);
            } else {
                if ($this->_childType == 'var_script') {
                    $varScriptEl = $parent->ownerDocument->createElement(
                        "var_script", htmlspecialchars($this->_value)
                    );
                    $varScriptEl->setAttribute("language", $this->_childAttributes['language']);
                    $varScriptEl->setAttribute("function_name", $this->_childAttributes['function_name']);
                    $varScriptEl->setAttribute("parameters", $this->_childAttributes['parameters']);
                    $varEl->appendChild($varScriptEl);
                } else {
                    /* <var_rule> */
                    $varRuleEl = $parent->ownerDocument->createElement("var_rule");
                    $varEl->appendChild($varRuleEl);
                    // mappings
                    foreach ($this->_mappedValues[1] as $srcVal => $dstVal) {
                        /* <query_line category=... qualifier="Is" logical_op="None">source value</query_line> */
                        $queryLineEl = $parent->ownerDocument->createElement(
                            "query_line", htmlspecialchars($srcVal)
                        );
                        $varRuleEl->appendChild($queryLineEl);
                        $queryLineEl->setAttribute("category", "<" . $this->_mappedVarName . ">");
                        $queryLineEl->setAttribute("qualifier", "Is");
                        $queryLineEl->setAttribute("logical_op", "None");
                        /* <var_rule_result>mapped value</var_rule_result> */
                        $varRuleResultEl = $parent->ownerDocument->createElement(
                            "var_rule_result", htmlspecialchars($dstVal)
                        );
                        $varRuleEl->appendChild($varRuleResultEl);
                    }
                    // Default value
                    $varRuleResultEl = $parent->ownerDocument
                        ->createElement("var_rule_result", htmlspecialchars($this->_mappedValues[0]));
                    $varRuleEl->appendChild($varRuleResultEl);
                }
            }
        }
    }
}