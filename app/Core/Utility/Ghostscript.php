<?php
/**
 * Created by PhpStorm.
 * User: wayne.jacobsen
 * Date: 12/20/17
 * Time: 3:09 PM
 */

namespace App\Core\Utility;


class Ghostscript
{

    /**
     * @param $inputFile
     * @param $outputLocation
     * @param int $startPage
     * @param int $lastPage
     * @throws \Exception
     */
    public static function splitPdf($inputFile, $outputLocation, $startPage=1, $lastPage=1)
    {
        if (!file_exists($inputFile)) {
            throw new \Exception('Input File not found');
        }

        if (!file_exists($outputLocation)) {
            mkdir($outputLocation, 0755, true);
        }

        while ($startPage <= $lastPage) {
            $outFile = $outputLocation . (substr($outputLocation, -1) == '/' ? '' : '/') . $startPage . '.pdf';
            exec("gs -dBATCH -sOutputFile=\"$outFile\" -dFirstPage=$startPage -dLastPage=$startPage -sDEVICE=pdfwrite \"$inputFile\"");
            $startPage++;
        }
    }

    public static function getPageCount($inputFile)
    {
        return exec('gs -q -dNODISPLAY -c "(' . $inputFile . ') (r) file runpdfbegin pdfpagecount = quit"');
    }

}