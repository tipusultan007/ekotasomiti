<?php

namespace App\Services;

use Illuminate\Http\Response;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

class PdfService
{
    public static function create(string $format = 'A4', string $orientation = 'P'): Mpdf
    {
        $defaultConfig = (new ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];

        $defaultFontConfig = (new FontVariables())->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];

        $tempDir = storage_path('app/mpdf_temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $fontDirList = array_merge($fontDirs, [
            resource_path('fonts'),
        ]);

        $customFonts = [
            'kalpurush' => [
                'R' => 'kalpurush.ttf',
                'useOTL' => 0xFF,
            ],
        ];

        return new Mpdf([
            'fontDir' => $fontDirList,
            'fontdata' => $fontData + $customFonts,
            'default_font' => 'kalpurush',
            'mode' => 'utf-8',
            'format' => $format,
            'orientation' => $orientation,
            'tempDir' => $tempDir,
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 12,
            'margin_bottom' => 12,
        ]);
    }

    public static function streamView(string $view, array $data, string $filename, string $format = 'A4', string $orientation = 'P'): Response
    {
        $html = view($view, $data)->render();
        $mpdf = self::create($format, $orientation);
        $mpdf->WriteHTML($html);

        $output = $mpdf->Output($filename, 'S');

        return response($output, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    public static function downloadView(string $view, array $data, string $filename, string $format = 'A4', string $orientation = 'P'): Response
    {
        $html = view($view, $data)->render();
        $mpdf = self::create($format, $orientation);
        $mpdf->WriteHTML($html);

        $output = $mpdf->Output($filename, 'S');

        return response($output, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
