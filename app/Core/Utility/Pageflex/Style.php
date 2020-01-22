<?php
namespace App\Core\Utility\Pageflex;
use App\Core\Models\EZT2\Design\Customizable\ColorList;

/**
 * wrapper class for pageflex .tsl file
 * @package App\Core\Utility\Pageflex
 */

class Style
{
    private $_front;
    private $_back;
    const _unitMultiplier = 254000;
    const _gridSubtractor = 127000;
    const _lineBreak = "\r\n";
    private $_numPages;

    function __construct($designs)
    {

        if (key_exists('1', $designs) && key_exists('2', $designs)) {
            $this->_front = $designs['1'];
            $this->_back = $designs['2'];
            $this->_numPages = 2;
        }

        if (key_exists('2', $designs) && !key_exists('1', $designs)) {
            $this->_front = $designs['2'];
            $this->_numPages = 1;

        }

        if (key_exists('1', $designs) && !key_exists('2', $designs)) {
            $this->_front = $designs['1'];
            $this->_numPages = 1;
        }

    }

    /**
     * main function to control creation of file contents to be returned to
     * caller as a string
     *
     * @return string
     */

    public function getPFStyle()
    {
        $styleFileContents = $this->getPreamble() . $this->getColorSection()
            . $this->getMainContents() . $this->getPageBaseContents() . $this->getClosingContents();
        return $styleFileContents;
    }

    /**
     * gets top portion of file contents
     *
     * @return string
     */

    private function getPreamble()
    {
        $preamble = "tsl_version 9;" . self::_lineBreak . self::_lineBreak . "/* EZT2 Update 1 July 2005 */" . self::_lineBreak;
        return $preamble;
    }

    /**
     * gets pagebase portion of the file
     * this portion determines the size and shape of the base page of
     * the design.  the size is computed using the dimensions of the
     * underlying pageflex template in inches and multiplying that by
     * a set multiplier.  The shape of the page is dependent on the orientation
     * of each of the pages in the template.  If the orientation is different,
     * then a square base page is created with the largest horizontal and vertical
     * measurements used to create that square's dimensions. If the orientation is the
     * same, then the horizontal and verical dimensions are set such that the longest
     * side is appropriate for the orientation (vertical for portrait, horizontal for landscape)
     *
     * @return string
     */

    private function getPageBaseContents()
    {
        $pageBaseContents = '';
        if ($this->_numPages > 1 && ($this->_front->orientation != $this->_back->orientation)) {
            if ($this->_numPages > 1 && ($this->_front->v_size_inches > $this->_front->h_size_inches)) {
                $pageHeight = $this->_front->v_size_inches * self::_unitMultiplier;
                $pageWidth = $this->_front->v_size_inches * self::_unitMultiplier;
            } else {
                $pageHeight = $this->_front->h_size_inches * self::_unitMultiplier;
                $pageWidth = $this->_front->h_size_inches * self::_unitMultiplier;
            }
        } else {
            if ($this->_front->orientation == 2) {
                $pageHeight = $this->_front->h_size_inches * self::_unitMultiplier;
                $pageWidth = $this->_front->v_size_inches * self::_unitMultiplier;
            } else {
                if ($this->_front->orientation == 1) {
                    $pageHeight = $this->_front->v_size_inches * self::_unitMultiplier;
                    $pageWidth = $this->_front->h_size_inches * self::_unitMultiplier;
                }
            }
        }

        $gridColomns = $pageWidth - self::_gridSubtractor;
        $gridRows = $pageHeight - self::_gridSubtractor;

        $pageBaseContents .= 'model PF_Page_Base : _page' . "" . self::_lineBreak;
        $pageBaseContents .= '   width "' . $pageWidth . '";' . "" . self::_lineBreak;
        $pageBaseContents .= '   height "' . $pageHeight . '";' . "" . self::_lineBreak;
        $pageBaseContents .= '   left_margin "63500";' . "" . self::_lineBreak;
        $pageBaseContents .= '   right_margin "63500";' . "" . self::_lineBreak;
        $pageBaseContents .= '   top_margin "63500";' . "" . self::_lineBreak;
        $pageBaseContents .= '   bottom_margin "63500";' . "" . self::_lineBreak;
        $pageBaseContents .= '   grid_columns "width=' . $gridColomns . '";' . "" . self::_lineBreak;
        $pageBaseContents .= '   grid_rows "height=' . $gridRows . '";' . "" . self::_lineBreak;
        $pageBaseContents .= '   border_color "Black";' . "" . self::_lineBreak;
        $pageBaseContents .= '   border_name "_none";' . "" . self::_lineBreak;
        $pageBaseContents .= '   border_simple_thickness "0.000000pt";' . "" . self::_lineBreak;
        $pageBaseContents .= '   fill_name "_none";' . "" . self::_lineBreak;
        $pageBaseContents .= '   substrate_color "_white";' . "" . self::_lineBreak;
        $pageBaseContents .= 'end' . "" . self::_lineBreak;
        return $pageBaseContents;
    }

    /**
     * creates the list of colors styles for the style sheet based on the
     * color_list table in the ezt2 database
     *
     * @return string
     */

    private function getColorSection()
    {
        $colorSection = '';
        $colors = ColorList::all();

        foreach ($colors as $color) {
            $colorSection .= "color \"" . $color->color_name . "\"" . self::_lineBreak . "\t";
            $colorSection .= "cmyk \"" . $color->cval . ".000%, " . $color->mval . ".000%, "
                . $color->yval . ".000%, " . $color->kval . ".000%\";" . self::_lineBreak . "\t";
            $colorSection .= "color_type process;\r\n";
            $colorSection .= "end" . self::_lineBreak . self::_lineBreak;
        }

        return $colorSection;
    }

    /**
     * main contents of the style sheet
     *
     * @return string
     */

    private function getMainContents()
    {
        $mainContents = '';
        $mainContents .= 'model PF_Designer_Doc : _document' . "" . self::_lineBreak;
        $mainContents .= '   initial_content "<PF_Page_Base></PF_Page_Base>";' . "" . self::_lineBreak;
        $mainContents .= '   language_id "english";' . "" . self::_lineBreak;
        $mainContents .= 'end' . "\r\n\r\n";

        $mainContents .= 'model PF_Box_Base : _box' . "\r\n";
        $mainContents .= '   min_width "0.030000in";' . "\r\n";
        $mainContents .= '   max_width "11.000000in";' . "\r\n";
        $mainContents .= '   min_height "0.030000in";' . "\r\n";
        $mainContents .= '   max_height "17.000000in";' . "\r\n";
        $mainContents .= '   border_name "_simple_border";' . "\r\n";
        $mainContents .= '   border_simple_thickness "1.000000pt";' . "\r\n";
        $mainContents .= '   border_color "Black";' . "\r\n";
        $mainContents .= 'end' . "\r\n\r\n";

        $mainContents .= 'model PF_TextBox_Base : PF_Box_Base' . "\r\n";
        $mainContents .= '   border_name "_none";' . "\r\n";
        $mainContents .= '   border_simple_thickness "0.000000pt";' . "\r\n";
        $mainContents .= '   top_margin "0.000000in";' . "\r\n";
        $mainContents .= '   left_margin "0.000000in";' . "\r\n";
        $mainContents .= '   bottom_margin "0.000000in";' . "\r\n";
        $mainContents .= '   right_margin "0.000000in";' . "\r\n";
        $mainContents .= '   runaround "false";' . "\r\n";
        $mainContents .= 'end' . "\r\n\r\n";

        $mainContents .= 'model PF_Area_Template_Base : PF_Box_Base' . "\r\n";
        $mainContents .= '   border_name "_none";' . "\r\n";
        $mainContents .= '   border_simple_thickness "0.000000pt";' . "\r\n";
        $mainContents .= '   clip_content "true";' . "\r\n";
        $mainContents .= 'end' . "\r\n\r\n";

        $mainContents .= 'model PF_Area_Template_Box : PF_Box_Base' . "\r\n";
        $mainContents .= '   border_name "_none";' . "\r\n";
        $mainContents .= '   border_simple_thickness "0.000000pt";' . "\r\n";
        $mainContents .= '   clip_content "true";' . "\r\n";
        $mainContents .= 'end' . "\r\n\r\n";

        $mainContents .= 'model PF_Circle_Base : _circle' . "\r\n";
        $mainContents .= '   min_width "0.250000in";' . "\r\n";
        $mainContents .= '   max_width "11.000000in";' . "\r\n";
        $mainContents .= '   min_height "0.250000in";' . "\r\n";
        $mainContents .= '   max_height "17.000000in";' . "\r\n";
        $mainContents .= '   border_name "_simple_border";' . "\r\n";
        $mainContents .= '   border_simple_thickness "1.000000pt";' . "\r\n";
        $mainContents .= '   border_color "Black";' . "\r\n";
        $mainContents .= 'end' . "\r\n\r\n";

        $mainContents .= 'model PF_TextCircle_Base : PF_Circle_Base' . "\r\n";
        $mainContents .= '   border_name "_none";' . "\r\n";
        $mainContents .= '   border_simple_thickness "0.000000pt";' . "\r\n";
        $mainContents .= '   top_margin "0.000000in";' . "\r\n";
        $mainContents .= '   left_margin "0.000000in";' . "\r\n";
        $mainContents .= '   bottom_margin "0.000000in";' . "\r\n";
        $mainContents .= '   right_margin "0.000000in";' . "\r\n";
        $mainContents .= 'end' . "\r\n\r\n";

        $mainContents .= 'model PF_Area_Template_Circle : PF_Circle_Base' . "\r\n";
        $mainContents .= '   border_name "_none";' . "\r\n";
        $mainContents .= '   border_simple_thickness "0.000000pt";' . "\r\n";
        $mainContents .= '   clip_content "true";' . "\r\n";
        $mainContents .= 'end' . "\r\n\r\n";

        $mainContents .= 'model PF_Polygon : _figure' . "\r\n";
        $mainContents .= '   contour "move 0in 0in, line 0.25in 0in";' . "\r\n";
        $mainContents .= '   border_name "_simple_border";' . "\r\n";
        $mainContents .= '   border_simple_thickness "1.000000pt";' . "\r\n";
        $mainContents .= '   border_color "Black";' . "\r\n";
        $mainContents .= 'end' . "\r\n\r\n";

        $mainContents .= 'model PF_Area_Template_Polygon : PF_Polygon' . "\r\n";
        $mainContents .= '   border_name "_none";' . "\r\n";
        $mainContents .= '   border_simple_thickness "0.000000pt";' . "\r\n";
        $mainContents .= '   clip_content "true";' . "\r\n";
        $mainContents .= 'end' . "\r\n\r\n";

        $mainContents .= 'model PF_Text_Polygon : _figure' . "\r\n";
        $mainContents .= '   contour "move 0in 0in, line 0.25in 0in";' . "\r\n";
        $mainContents .= '   border_color "Black";' . "\r\n";
        $mainContents .= '   border_name "_none";' . "\r\n";
        $mainContents .= '   border_simple_thickness "0.000000pt";' . "\r\n";
        $mainContents .= '   top_margin "0.000000in";' . "\r\n";
        $mainContents .= '   left_margin "0.000000in";' . "\r\n";
        $mainContents .= '   bottom_margin "0.000000in";' . "\r\n";
        $mainContents .= '   right_margin "0.000000in";' . "\r\n";
        $mainContents .= '   runaround "false";' . "\r\n";
        $mainContents .= 'end' . "\r\n\r\n";

        $mainContents .= 'model PF_Line_Base : _figure' . "\r\n";
        $mainContents .= '   contour "move 0in 0in, line 0.25in 0in";' . "\r\n";
        $mainContents .= '   border_name "_simple_border";' . "\r\n";
        $mainContents .= '   border_simple_thickness "1.000000pt";' . "\r\n";
        $mainContents .= '   border_color "Black";' . "\r\n";
        $mainContents .= '   border_simple_placement "centered";' . "\r\n";
        $mainContents .= '   border_dash_gap "0.000000pt";' . "\r\n";
        $mainContents .= 'end' . "\r\n\r\n";

        $mainContents .= 'model PF_Image_Base : _image' . "\r\n";
        $mainContents .= '   border_simple_placement "inside";' . "\r\n";
        $mainContents .= '   border_color "Black";' . "\r\n";
        $mainContents .= '   border_name "_none";' . "\r\n";
        $mainContents .= '   border_simple_thickness "0.000000pt";' . "\r\n";
        $mainContents .= '   height "1.000000scale";' . "\r\n";
        $mainContents .= '   width "1.000000scale";' . "\r\n";
        $mainContents .= '   avoid_me "false";' . "\r\n";
        $mainContents .= '   use_clipping_path "true";' . "\r\n";
        $mainContents .= '   use_runaround_path "true";' . "\r\n";
        $mainContents .= '   bumper "0";' . "\r\n";
        $mainContents .= '   recurrence_weight "100";' . "\r\n";
        $mainContents .= '   recurrence_scope "job";' . "\r\n";
        $mainContents .= 'end' . "\r\n\r\n";

        $mainContents .= 'model PF_TextFrame_Base : _text_frame' . "\r\n";
        $mainContents .= '   num_columns "1";' . "\r\n";
        $mainContents .= '   gutter_width "0.000000in";' . "\r\n";
        $mainContents .= '   min_width "0.030000in";' . "\r\n";
        $mainContents .= '   max_width "11.000000in";' . "\r\n";
        $mainContents .= '   min_height "0.030000in";' . "\r\n";
        $mainContents .= '   max_height "17.000000in";' . "\r\n";
        $mainContents .= '   border_name "_none";' . "\r\n";
        $mainContents .= '   border_simple_thickness "0.000000pt";' . "\r\n";
        $mainContents .= '   top_margin "0.000000in";' . "\r\n";
        $mainContents .= '   left_margin "0.000000in";' . "\r\n";
        $mainContents .= '   bottom_margin "0.000000in";' . "\r\n";
        $mainContents .= '   right_margin "0.000000in";' . "\r\n";
        $mainContents .= '   runaround "false";' . "\r\n";
        $mainContents .= '   column_balancing_tolerance "3.000000pt";' . "\r\n";
        $mainContents .= 'end' . "\r\n\r\n";

        $mainContents .= 'model PF_HBox_Base : _hbox' . "\r\n";
        $mainContents .= '   border_name "_none";' . "\r\n";
        $mainContents .= '   border_simple_thickness "0.000000pt";' . "\r\n";
        $mainContents .= '   min_width "0.250000in";' . "\r\n";
        $mainContents .= '   min_height "0.250000in";' . "\r\n";
        $mainContents .= '   max_width "11.000000in";' . "\r\n";
        $mainContents .= '   max_height "17.000000in";' . "\r\n";
        $mainContents .= '   clip_content "true";' . "\r\n";
        $mainContents .= 'end' . "\r\n\r\n";

        $mainContents .= 'model PF_VBox_Base : _vbox' . "\r\n";
        $mainContents .= '   border_name "_none";' . "\r\n";
        $mainContents .= '   border_simple_thickness "0.000000pt";' . "\r\n";
        $mainContents .= '   min_width "0.250000in";' . "\r\n";
        $mainContents .= '   min_height "0.250000in";' . "\r\n";
        $mainContents .= '   max_width "11.000000in";' . "\r\n";
        $mainContents .= '   max_height "17.000000in";' . "\r\n";
        $mainContents .= '   clip_content "true";' . "\r\n";
        $mainContents .= 'end' . "\r\n\r\n";

        $mainContents .= 'model PF_Char_Base : _char' . "\r\n";
        $mainContents .= 'end' . "\r\n\r\n";

        $mainContents .= 'model PF_Para_Base : _para' . "\r\n";
        $mainContents .= '   language_id "english";' . "\r\n";
        $mainContents .= '   font_name "/Arial";' . "\r\n";
        $mainContents .= '   font_size "12.000000pt";' . "\r\n";
        $mainContents .= '   text_color "Black";' . "\r\n";
        $mainContents .= 'end' . "\r\n\r\n";

        $mainContents .= 'model Normal : _para' . "\r\n";
        $mainContents .= '   subscript_shift "-33%";' . "\r\n";
        $mainContents .= '   subscript_size "100%";' . "\r\n";
        $mainContents .= '   superscript_shift "33%";' . "\r\n";
        $mainContents .= '   superscript_size "100%";' . "\r\n";
        $mainContents .= '   text_color "Black";' . "\r\n";
        $mainContents .= '   font_size "12.000000pt";' . "\r\n";
        $mainContents .= '   align "left";' . "\r\n";
        $mainContents .= '   set_size "100%";' . "\r\n";
        $mainContents .= '   drop_char_count "0";' . "\r\n";
        $mainContents .= '   drop_size "0lines";' . "\r\n";
        $mainContents .= '   baseline_shift "0.000000pt";' . "\r\n";
        $mainContents .= '   kern_pairs "true";' . "\r\n";
        $mainContents .= '   hyph_max_consecutive_lines "2";' . "\r\n";
        $mainContents .= '   left_indent "0.000000pt";' . "\r\n";
        $mainContents .= '   right_indent "0.000000pt";' . "\r\n";
        $mainContents .= '   initial_indent "0.000000pt";' . "\r\n";
        $mainContents .= '   auto_line_spacing "true";' . "\r\n";
        $mainContents .= '   line_spacing "120%";' . "\r\n";
        $mainContents .= '   language_id "english";' . "\r\n";
        $mainContents .= '   hyph_earliest "3";' . "\r\n";
        $mainContents .= '   hyph_latest "3";' . "\r\n";
        $mainContents .= '   space_before "0.000000pt";' . "\r\n";
        $mainContents .= '   space_after "0.000000pt";' . "\r\n";
        $mainContents .= '   underline "false";' . "\r\n";
        $mainContents .= '   normal_space_width "1.000000nsw";' . "\r\n";
        $mainContents .= '   max_space_width "1.000000nsw";' . "\r\n";
        $mainContents .= '   min_space_width "1.000000nsw";' . "\r\n";
        $mainContents .= '   normal_letterspacing "0.000000nsw";' . "\r\n";
        $mainContents .= '   max_letterspacing "0.000000nsw";' . "\r\n";
        $mainContents .= '   min_letterspacing "0.000000nsw";' . "\r\n";
        $mainContents .= '   keep_para_with_next "false";' . "\r\n";
        $mainContents .= '   supersub "none";' . "\r\n";
        $mainContents .= '   text_tint "100.000000";' . "\r\n";
        $mainContents .= '   font_name "/Times New Roman";' . "\r\n";
        $mainContents .= '   keep_para_together "false";' . "\r\n";
        $mainContents .= '   min_lines_before_break "0";' . "\r\n";
        $mainContents .= '   min_lines_after_break "0";' . "\r\n";
        $mainContents .= 'end' . "\r\n\r\n";

        // Bezier support
        $mainContents .= 'model PF_Area_Template_Bezier : PF_Polygon'."\r\n";
        $mainContents .= '   border_name "_none";'."\r\n";
        $mainContents .= '   border_simple_thickness "0.000000pt";'."\r\n";
        $mainContents .= '   clip_content "true";'."\r\n";
        $mainContents .= 'end'."\r\n\r\n";

        $mainContents .= 'model PF_Bezier_Shape : _figure'."\r\n";
        $mainContents .= '   contour "move 0in 0in, line 0.25in 0in";'."\r\n";
        $mainContents .= '   border_name "_simple_border";'."\r\n";
        $mainContents .= '   border_simple_thickness "1.000000pt";'."\r\n";
        $mainContents .= '   border_color "Black";'."\r\n";
        $mainContents .= 'end'."\r\n\r\n";

        $mainContents .= 'model PF_Bezier_Line : _figure'."\r\n";
        $mainContents .= '   contour "move 0in 0in, line 0.25in 0in";'."\r\n";
        $mainContents .= '   border_name "_simple_border";'."\r\n";
        $mainContents .= '   border_simple_thickness "1.000000pt";'."\r\n";
        $mainContents .= '   border_color "Black";'."\r\n";
        $mainContents .= 'end'."\r\n\r\n";

        $mainContents .= 'model PF_Bezier_Line_Text : _figure'."\r\n";
        $mainContents .= '   contour "move 0in 0in, line 0.25in 0in";'."\r\n";
        $mainContents .= '   border_name "_none";'."\r\n";
        $mainContents .= '   border_simple_thickness "0.000000pt";'."\r\n";
        $mainContents .= '   top_margin "0.000000in";'."\r\n";
        $mainContents .= '   left_margin "0.000000in";'."\r\n";
        $mainContents .= '   bottom_margin "0.000000in";'."\r\n";
        $mainContents .= '   right_margin "0.000000in";'."\r\n";
        $mainContents .= '   runaround "false";'."\r\n";
        $mainContents .= '   content_goes "edge";'."\r\n";
        $mainContents .= 'end'."\r\n\r\n";

        $mainContents .= 'model PF_Text_Bezier : _figure'."\r\n";
        $mainContents .= '   contour "move 0in 0in, line 0.25in 0in";'."\r\n";
        $mainContents .= '   border_color "Black";'."\r\n";
        $mainContents .= '   border_name "_none";'."\r\n";
        $mainContents .= '   border_simple_thickness "0.000000pt";'."\r\n";
        $mainContents .= '   top_margin "0.000000in";'."\r\n";
        $mainContents .= '   left_margin "0.000000in";'."\r\n";
        $mainContents .= '   bottom_margin "0.000000in";'."\r\n";
        $mainContents .= '   right_margin "0.000000in";'."\r\n";
        $mainContents .= '   runaround "false";'."\r\n";
        $mainContents .= 'end'."\r\n\r\n";

        $mainContents .= 'model PF_Paragroup_Base : _paragroup' . "\r\n";
        $mainContents .= 'end' . "\r\n\r\n";
        return $mainContents;
    }

    /**
     * gets the closing contents of the style file
     *
     * @return string
     */

    private function getClosingContents()
    {
        $closingContents = '';
        $closingContents .= 'settings ' . "" . self::_lineBreak;
        $closingContents .= ' default_para_model "PF_Para_Base"; default_font_name "/Arial";end ' . "" . self::_lineBreak;
        $closingContents .= '/* Pageflex Creation Data */' . "" . self::_lineBreak;
        $closingContents .= '/* Created by: Pageflex Designer */' . "" . self::_lineBreak;
        $closingContents .= '/* AppVersion 3.5.0.120 */' . "" . self::_lineBreak;
        $closingContents .= '/* EngineVersion 4.0.7.0 */' . "" . self::_lineBreak . self::_lineBreak;
        return $closingContents;
    }
}