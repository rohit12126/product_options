<?php

namespace App\Core\Utility\Pageflex;
/*
 * Utility class to handle the synchronization of the template.pf
 * and template.xvp pageflex assets.
 */

use App\Core\Models\EZT2\User\Project as UserProject;

class Utility
{
    /*
         * method to call when leaving the advanced editor. this method
         * synchronizes the pageflex project file (.pf) with the
         * pageflex .edit variable file (.xvp). the method is
         * necessary because of the two different variable definition
         * methods in pageflex (project files for pageflex server jobs
         * and .xvp files for pageflex interactive editing sessions)
         */
    public static function leaveAdvanceEditor($userProjectId)
    {
        $userProject = UserProject::findorfail($userProjectId);
        $varXpath = '//pfjob:var';
        $xvpDomDoc = self::getXvpFileAsDom($userProject);
        if ($xvpDomDoc) {
            $pfFile = self::getProjectFile($userProject);
            if ($pfFile) {
                $pfVariables = $pfFile->getVariables();
                $xvpXpath = new \DOMXPath($xvpDomDoc);
                $xvpXpath->registerNamespace('pfjob', 'http://www.pageflex.com/xvp');
                if (null != $xvpXpath->query($varXpath)) {
                    $xpvMatchs = $xvpXpath->query($varXpath);
                    foreach ($xpvMatchs as $xvpMatch) {
                        foreach ($pfVariables as $pfVariable) {
                            if ($pfVariable->getName() == $xvpMatch->getAttribute('name')) {
                                $pfVariable->setValue((string)$xvpMatch->nodeValue);
                            }
                        }
                    }

                    $pfFile->setVariables($pfVariables);
                    $pfFile->save($userProject->filePath);
                }
            }
        }
    }

    public static function savingProjectFile($userProject)
    {
        $xvpDomDoc = self::getXvpFileAsDom($userProject);
        if ($xvpDomDoc) {
            $pfFile = self::getProjectFile($userProject);
            if ($pfFile) {
                $pfVariables = $pfFile->getVariables();
                $xvpXpath = new \DOMXPath($xvpDomDoc);
                $xvpXpath->registerNamespace('pfjob', 'http://www.pageflex.com/xvp');
                foreach ($pfVariables as $pfVariable) {
                    $varXvpXpath = self::getXpathExpresion($pfVariable->getName(), 'xvp');
                    if (null != $xvpXpath->query($varXvpXpath)) {
                        $xpvMatchs = $xvpXpath->query($varXvpXpath);
                        foreach ($xpvMatchs as $xvpMatch) {
                            $xvpMatch->nodeValue = htmlentities($pfVariable->getValue());
                        }
                    }
                }
                $xvpDomDoc->save(str_replace('template.pf', 'template.xvp', $userProject->filePath));
            }
        }
    }

    private static function getProjectFile($userProject)
    {
        $pfFilePath = $userProject->filePath;
        if (file_exists($pfFilePath)) {
            $pfFile = new UserProject($pfFilePath);
        } else {
            $pfFile = false;
        }
        return $pfFile;
    }

    private static function getXvpFileAsDom($userProject)
    {
        $xvpFilePath = str_replace('template.pf', 'template.xvp', $userProject->filePath);
        if (file_exists($xvpFilePath)) {
            $xvpFile = new \DOMDocument("1.0", "UCS-2");
            // Options: LIBXML_NOERROR so that entities outside of 5 predefined can also process
            $checker = $xvpFile->load($xvpFilePath, LIBXML_NOERROR);
        } else {
            $xvpFile = false;
        }
        return $xvpFile;
    }

    private static function getXpathExpresion($varName, $fileType)
    {
        if ($fileType == 'xvp') {
            $xpath = "//pfjob:var[@name='" . $varName . "']";
        } else {
            if ($fileType == 'pf') {
                $xpath = "//var[@name='" . $varName . "']/var_const";
            }
        }
        return $xpath;
    }
}