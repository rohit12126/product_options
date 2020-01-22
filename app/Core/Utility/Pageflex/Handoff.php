<?php
/**
 * Created by PhpStorm.
 * User: wjacobsen
 * Date: 7/18/16
 * Time: 9:31 AM
 */

namespace App\Core\Utility\Pageflex;


use Illuminate\Support\Facades\File;

class Handoff
{

    private $_frontXmlPresetFile;
    private $_backXmlPresetFile;
    private $_applyUserPreferencesFront = true;
    private $_applyUserPreferencesBack = true;
    private $_frontDesign;
    private $_backDesign;
    private $_frontVariables;
    private $_backVariables;
    private $_frontType;
    private $_backType;
    private $_sides;
    private $_hasBackXvpFile;
    private $_hasFrontXvpFile;
    private $_frontXvpFile;
    private $_backXvpFile;
    private $_sameProject;


    function __construct($designArray)
    {
        if (key_exists('1', $designArray)) {
            $this->_frontDesign = $designArray['1'];
            $this->_sides[1] = 'front';
        }
        if (key_exists('2', $designArray)) {
            $this->_backDesign = $designArray['2'];
            $this->_sides[2] = 'back';
        }
    }

    public function getProjectFile($userDesignPreferences = null)
    {
        $this->_getTypes();
        $variables = array();
        foreach ($this->_sides as $page => $side) {
            foreach ($this->{'_' . $side . 'Variables'} as $pageVar) {
                $variable = new Variable($pageVar->variable->name);
                if ($this->{'_has' . ucfirst($side) . 'XvpFile'}) {
                    $varValue = $this->getValueFromXvp($pageVar->variable->name, $side);
                }
                if (isset($varValue)) {
                    $variable->setValue($varValue);
                    $varValue = false;
                } else {
                    $varXpath = $this->getXpathExpression($pageVar->variable->name, $side);
                    $xpath = new \DOMXPath($this->{'_' . $side . 'XmlPresetFile'});
                    $xpMatch = $xpath->query($varXpath);
                    if ($xpMatch->length > 0) {
                        $xpMatch = $xpath->query($varXpath);
                        if (null != $xpMatch->item(0)) {
                            $variable->setValue((string)$xpMatch->item(0)->nodeValue);
                        }
                    }
                }
                if ($pageVar->variable->pfkey == "Text") {
                    $variable->setKind(1);
                } else {
                    if ($pageVar->variable->pfkey == 'FormattedText') {
                        $variable->setKind(Variable::FORMATTED_TEXT);
                    } else {
                        $variable->setKind(2);
                        $variable->setIsFileReference(true);
                        /*
                         * Ensure that there is a file extenstion.
                         */
                        if (!File::extension($variable->getValue())) {
                            $variable->setValue('');
                        }
                    }
                }
                if ($this->{'_applyUserPreferences' . ucfirst($side)} && null != $userDesignPreferences) {
                    if (array_key_exists($pageVar->variable->name, $userDesignPreferences)) {
                        $variable->setValue($userDesignPreferences[$pageVar->variable->name]);
                    }
                }
                $variable->setIsOptional(true);
                $variable->setIsFixed(true);
                // when stock variable has script
                if (!is_null($pageVar->variable->scriptDefault)) {
                    // fetch script defaults from template's variable
                    $scriptVar = $this->{'_' . $side . 'Design'}->getCustomizableDesignVariable($pageVar->id);
                    $variable->setIsFixed(false);
                    $variable->setScript($scriptVar->script);
                }
                $variables[] = $variable;
            }
        };

        $pfProject = new Project();
        $pfProject->setVariables($variables);
        //return a null? or only return the appropriate one.
        if ($this->_frontDesign) {
            $designArray['1'] = $this->_frontDesign;
        }
        if ($this->_backDesign) {
            $designArray['2'] = $this->_backDesign;
        }
        $results = array('projectFile' => $pfProject,
                         'designArray' => $designArray);
        return $results;
    }

    /**
     * Checks the type of the design being passed in and does the necessary
     * based on that type.
     *
     * The xml file and xpath expressions are used to retrieve the default
     * values from the appropriate place for inclusion in the eventual project file.
     *
     * @return void
     */
    private function _getTypes()
    {
        $frontId = null;
        if (null != $this->_frontDesign) {
            $this->_applyUserPreferencesFront = true;
            $this->_frontVariables = $this->_frontDesign->getVariables();
            $this->_frontXmlPresetFile = new \DOMDocument();
            $checker = @$this->_frontXmlPresetFile->loadXML(
                utf8_encode($this->_frontDesign->xvp_file)
            );
        }
        if (null != $this->_backDesign) {
            $this->_applyUserPreferencesBack = true;
            $this->_backVariables = $this->_backDesign->getVariables();
            $this->_backXmlPresetFile = new \DOMDocument();
            $checker = $this->_backXmlPresetFile->loadXML(utf8_encode($this->_backDesign->xvp_file));
        }

    }

    private function getXpathExpression($varName, $side)
    {
        if ($side == 'front') {
            $xpath = '//' . $varName;
        } else {
            $xpath = '//' . $varName;
        }
        return $xpath;
    }

    private function getPfProject($side)
    {
        if ($side == 'back' && $this->_sameProject) {
            return $this->_frontXmlPresetFile;
        } else {
            $path = $this->{'_' . $side . 'Design'}->filePath;
            $projectDomDoc = new \DOMDocument("1.0", "UCS-2");
            @$projectDomDoc->load($path);
            return $projectDomDoc;
        }
    }

    private function getXvpFile($side)
    {
        if ($side == 'back' && $this->_sameProject) {
            return $this->_frontXvpFile;
        } else {
            $path = str_replace('template.pf', 'template.xvp', $this->{'_' . $side . 'Design'}->filePath);
            $xvpDomDoc = new \DOMDocument("1.0", "UCS-2");
            @$xvpDomDoc->load($path);
            return $xvpDomDoc;
        }
    }

    private function getValueFromXvp($variable, $side)
    {
        $varXpath = "//var[@name='" . $variable . "']";
        $varXpathWns = "//pfjob:var[@name='" . $variable . "']";
        $xpath = new \DOMXPath($this->{'_' . $side . 'XvpFile'});
        $xpMatch = $xpath->query($varXpath);
        $value = false;
        if ($xpMatch->length > 0) {
            $xpMatch = $xpath->query($varXpath);
            $value = (string)$xpMatch->item(0)->nodeValue;
        } else {
            if (null != @$xpath->query($varXpathWns)) {
                $xpMatch = @$xpath->query($varXpathWns);
                if ($xpMatch->length > 0) {
                    $value = (string)$xpMatch->item(0)->nodeValue;
                }
            }
        }
        return $value;
    }

}