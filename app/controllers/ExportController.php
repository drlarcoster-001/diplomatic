<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: app/controllers/ExportController.php
 * Propósito: Generar PDF institucional con saltos de página controlados.
 * Nota: Utiliza Dompdf con carga automática desde /tools.
 */

namespace App\Controllers;

use App\Models\DiplomadosModel;
use Dompdf\Dompdf;
use Dompdf\Options;

class ExportController {
    
    public function pdf() {
        $id = (int)($_GET['id'] ?? 0);
        $model = new DiplomadosModel();
        $d = $model->getById($id);
        
        if (!$d) die("Error: Registro no encontrado.");

        $requirements = $model->getRequirements($id);
        $conditions = $model->getConditions($id);

        // Carga de Dompdf
        $autoloadPath = dirname(__DIR__, 2) . '/tools/dompdf/autoload.inc.php';
        if (!file_exists($autoloadPath)) die("Error: Cargador no encontrado.");

        require_once $autoloadPath;

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true); 
        $options->set('defaultFont', 'serif'); 

        $dompdf = new Dompdf($options);

        // Estructura con Salto de Página
        $html = '
        <html>
        <head>
            <style>
                body { font-family: "times", serif; line-height: 1.6; color: #000; padding: 30px; }
                .title { text-align: center; font-weight: bold; text-transform: uppercase; font-size: 18pt; border-bottom: 2px solid #000; padding-bottom: 10px; }
                .code-info { text-align: center; font-size: 10pt; margin-bottom: 30px; margin-top: 5px; }
                .label { font-weight: bold; text-decoration: underline; text-transform: uppercase; display: block; margin-top: 25px; font-size: 12pt; }
                .content { text-align: justify; margin-top: 5px; font-size: 11pt; }
                .footer-hours { margin-top: 60px; text-align: center; font-weight: bold; border-top: 2px solid #000; padding-top: 15px; font-size: 13pt; }
                
                /* Clase para el salto de página */
                .saltopagina { page-break-before: always; }
                
                ul { margin-top: 5px; }
                li { margin-bottom: 8px; }
            </style>
        </head>
        <body>
            <div class="title">' . htmlspecialchars($d['name']) . '</div>
            <div class="code-info">IDENTIFICADOR INSTITUCIONAL: ' . htmlspecialchars($d['code']) . '</div>

            <span class="label">Dirigido a:</span>
            <div class="content">' . ($d['directed_to'] ?: 'Personal profesional calificado.') . '</div>

            <span class="label">Descripción y Objetivos:</span>
            <div class="content">' . ($d['description'] ?: 'Información en revisión académica.') . '</div>

            <div class="saltopagina"></div>

            <span class="label">Requisitos:</span>
            <div class="content">
                <ul>';
                foreach($requirements as $r) {
                    $html .= '<li>' . htmlspecialchars($r['requirement_text']) . '</li>';
                }
                if(empty($requirements)) $html .= '<li>Consultar requisitos en la oficina correspondiente.</li>';
        $html .= '</ul>
            </div>

            <span class="label">Algunas Condiciones Generales:</span>
            <div class="content">
                <ul>';
                foreach($conditions as $c) {
                    $html .= '<li>' . htmlspecialchars($c['condition_text']) . '</li>';
                }
                if(empty($conditions)) $html .= '<li>Sujeto a normativas administrativas vigentes.</li>';
        $html .= '</ul>
            </div>

            <div class="footer-hours">
                CARGA HORARIA TOTAL: ' . ($d['total_hours'] ?: '200') . ' HORAS ACADÉMICAS.
            </div>
        </body>
        </html>';

        $dompdf->loadHtml($html);
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();

        if (ob_get_length()) ob_end_clean();

        $dompdf->stream($d['code'] . ".pdf", ["Attachment" => true]);
        exit;
    }
}