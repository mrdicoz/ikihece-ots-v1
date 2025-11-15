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
            'margin_left' => 20,    // 10mm
            'margin_right' => 20,   // 15mm
            'margin_top' => 15,     // 25mm
            'margin_bottom' => 15,  // 15mm
            'default_font' => 'Times New Roman',   // Boş - varsayılan font
            'dpi' => 96,            // 96 DPI
            'img_dpi' => 96         // 96 DPI
        ]);
        $style = '
        <style>
            body {
                font-family: "Times New Roman", serif;;
                font-size: 11pt;
                color: #000000;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                border: 0; /* Ensure border property is set */
            }
            td, th {
                padding: 5px;
                border: 0; /* Ensure border property is set */
                text-align: left;
            }
            #footer {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                width: 100%;
            }

        </style>
        ';

        $mpdf->WriteHTML($style . $html);
        
        return $mpdf->Output($filename, $output);
    }
}