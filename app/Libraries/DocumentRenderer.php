<?php

namespace App\Libraries;

use Mpdf\Mpdf;

class DocumentRenderer
{
    public function generatePDF($html, $filename = 'document.pdf', $output = 'D')
    {
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 15,    // 10mm
            'margin_right' => 15,   // 15mm
            'margin_top' => 25,     // 25mm
            'margin_bottom' => 15,  // 15mm
            'default_font' => '',   // Boş - varsayılan font
            'dpi' => 96,            // 96 DPI
            'img_dpi' => 96         // 96 DPI
        ]);
        $style = '
        <style>
            @page {
                margin: 25mm 15mm 15mm 15mm;
            }
            body {
                font-family: "dejavusans", sans-serif;
                font-size: 11pt;
                line-height: 1.6;
                color: #000000;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 10px 0;
            }
            td, th {
                padding: 8px;
                border: 0px solid #000000;
                text-align: left;
            }
            th {
                font-weight: bold;
                background-color: #f5f5f5;
            }
            p {
                margin: 0 0 12px 0;
                text-align: justify;
            }
            h1, h2, h3 {
                margin: 15px 0 10px 0;
                line-height: 1.3;
            }                
            h4 {
                margin: 0 0 8px 0;
                text-align: justify;
            }
            .text-center {
                text-align: center;
            }
            .text-right {
                text-align: right;
            }
            .bold {
                font-weight: bold;
            }
        </style>
        ';

        $mpdf->WriteHTML($style . $html);
        
        return $mpdf->Output($filename, $output);
    }
}