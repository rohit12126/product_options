<?php

namespace App\Core\Utility\Pageflex;
use App\Core\Models\EZT2\User\Project as UserProject;

/**
 * wrapper class for pageflex .xdt file
 * @package App\Core\Utility\Pageflex
 */

class Template
{
    private $_front;
    private $_back;
    private $_originalPageForFront;
    private $_numPages;
    const _lineBreak = "\r\n";

    function __construct($designs)
    {
        if (key_exists('1', $designs) && key_exists('2', $designs)) {
            $this->_front = $designs['1'];
            $this->_back = $designs['2'];
            $this->_numPages = 2;
            $this->_originalPageForFront = 1;
        }

        if (key_exists('2', $designs) && !key_exists('1', $designs)) {
            $this->_front = $designs['2'];
            $this->_numPages = 1;
            $this->_originalPageForFront = 2;

        }

        if (key_exists('1', $designs) && !key_exists('2', $designs)) {
            $this->_front = $designs['1'];
            $this->_numPages = 1;
            $this->_originalPageForFront = 1;
        }

    }

    /**
     * main function to control creation of file contents to be returned to
     * caller as a string
     *
     * @return string
     */

    public function getPFTemplate()
    {
        $fileContents = $this->getPreamble() . $this->getMainContents() . $this->getClosingContents();
        $fileContents .= 'Compatible with: Pageflex Designer' . self::_lineBreak . 'AppVersion 3.5.0.115'
            . self::_lineBreak . 'EngineVersion 4.0.7' . self::_lineBreak . '-->' . self::_lineBreak;
        return $fileContents;
    }

    /**
     * returns intro portion of .xdt file as string
     *
     * @return string
     */

    private function getPreamble()
    {
        $preamble = '<?xml version="1.0" ?>' . self::_lineBreak . '<?NuDoc tsl_version="9" ?>' . self::_lineBreak;
        $preamble .= '<PF_Designer_Doc composition_version="9">' . self::_lineBreak;
        return $preamble;
    }

    /**
     * returns main portion of .xdt file as string which takes the template specific
     * portion of the xdt from the templateXdt field in the customizable_design table
     *
     * @return string
     */

    private function getMainContents()
    {
        if ($this->_front) {
            $genericNeedle = "</PF_Page_Base>";
            //if design is a user_project, than this is a user design
            //that has potentially been edited by the user in the advanced editor
            //which requires that we grab the existing template file contents
            //and drop it into the new template file
            if ($this->_front instanceof UserProject) {
                $frontXdt = $this->getTemplateFromDisk($this->_front, $this->_originalPageForFront);
            } else {
                $frontXdt = $this->_front->template_xdt;
                $myReg = preg_match("/(<PF_Page_Base)?([^>]+)/i", $frontXdt, $regs);
                $frontXdt = str_replace($regs[0], $regs[1], $frontXdt);
            }
            $genericImage = '<PF_Image_Base pf.edit_ui_class="genericImage" name="dupeimage" pfimage_fit="fit_height" ';
            $genericImage .= 'default_image_data_height="410660"';
            $genericImage .= ' default_image_data_width="220242" src="" height="381000" width="proportional" ';
            $genericImage .= 'display_order="21" y_position="261747" x_position="-435229"></PF_Image_Base>'
                . self::_lineBreak;

            $munchedContents = str_replace($genericNeedle, $genericImage, $frontXdt);
            $mainContents = $munchedContents;
            $mainContents .= '</PF_Page_Base>' . self::_lineBreak;
        }
        if ($this->_back) {
            if ($this->_back instanceof UserProject) {
                $backXdt = $this->getTemplateFromDisk($this->_back, 2);
            } else {
                $backXdt = $this->_back->template_xdt;
                $myReg2 = preg_match("/(<PF_Page_Base)?([^>]+)/i", $backXdt, $regs2);
                $backXdt = str_replace($regs2[0], $regs2[1], $backXdt);
            }
            $genericImage2 = '<PF_Image_Base pf.edit_ui_class="genericImage" name="dupeimage2" ';
            $genericImage2 .= 'pfimage_fit="fit_height" default_image_data_height="410660"';
            $genericImage2 .= ' default_image_data_width="220242" src="" height="381000" width="proportional" ';
            $genericImage2 .= 'display_order="21" y_position="261747" x_position="-435229"></PF_Image_Base>'
                . self::_lineBreak;

            $munchedContents = str_replace($genericNeedle, $genericImage2, $backXdt);
            $mainContents .= $munchedContents;
            $mainContents .= '</PF_Page_Base>' . self::_lineBreak;
        }
        return $mainContents;
    }

    /**
     * returns closing portion of .xdt file as string
     *
     * @return string
     */

    private function getClosingContents()
    {
        $closingContents = self::_lineBreak . '</PF_Designer_Doc>' . self::_lineBreak
            . '<!-- Pageflex Creation Data' . self::_lineBreak
            . 'Created by: Pageflex .EDIT Server Version 1.0.1 (Build 159)'
            . self::_lineBreak;
        return $closingContents;

    }

    private function getTemplateFromDisk($design, $page)
    {
        //for some reason the dom getElementsByTagName does no 
        //work with the one PF_Page_Base tag
        $path = str_replace('template.pf', 'template.xdt', $design->filePath);
        if ($contents = file_get_contents($path)) {
            //there exists in the wild documents that have no spaces between certain attributes
            //for the dupe image. the below corrects that prior to loading as an xml file
            //which would break otherwise
            $contents = str_replace(
                'name="dupeimage2"pfimage_fit', 'name="dupeimage2" pfimage_fit', $contents
            );
            $contents = str_replace(
                'width="proportional"display_order=',
                'width="proportional" display_order=',
                $contents
            );
            $contents = str_replace(
                'pfimage_fit="fit_height"default_image_data_height',
                'pfimage_fit="fit_height" default_image_data_height',
                $contents
            );
            $xml = new \SimpleXMLElement($contents);
            $matchCount = count($xml->PF_Page_Base);
            if ($page == 1) {
                $output = $xml->PF_Page_Base[0]->asXML();
            } else {
                if ($page == 2) {
                    if ($matchCount == 1) {
                        $output = $xml->PF_Page_Base[0]->asXML();
                    } else {
                        if ($matchCount == 2) {
                            $output = $xml->PF_Page_Base[1]->asXML();
                        }
                    }
                }
            }
            return $output;
        } else {
            return $design->templateXdt;
            //could not find exiting template file, use one from design
        }

    }
}