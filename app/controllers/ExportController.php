<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: app/controllers/ExportController.php
 * Propósito: Generar PDF institucional y registrar auditoría.
 */

namespace App\Controllers;

use App\Models\DiplomadosModel;
use App\Services\AuditService;
use Dompdf\Dompdf;
use Dompdf\Options;

class ExportController {
    
    public function pdf() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $id = (int)($_GET['id'] ?? 0);
        $model = new DiplomadosModel();
        $d = $model->getById($id);
        
        if (!$d) die("Error: Registro no encontrado.");

        AuditService::log([
            'module' => 'ACADEMIC_DIPLOMADOS',
            'action' => 'DOWNLOAD_PDF',
            'description' => 'Descargó el PDF oficial de: ' . $d['name'],
            'entity_id' => $id
        ]);

        $autoloadPath = dirname(__DIR__, 2) . '/tools/dompdf/autoload.inc.php';
        require_once $autoloadPath;
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true); 
        $options->set('defaultFont', 'serif'); 
        $dompdf = new Dompdf($options);

        $html = '<html><head><style>body { font-family: "times", serif; line-height: 1.6; padding: 30px; } .title { text-align: center; font-weight: bold; text-transform: uppercase; border-bottom: 2px solid #000; } .label { font-weight: bold; text-decoration: underline; text-transform: uppercase; display: block; margin-top: 25px; } .saltopagina { page-break-before: always; }</style></head><body>';
        $html .= '<div class="title">' . htmlspecialchars($d['name']) . '</div>';
        $html .= '<span class="label">Dirigido a:</span><div>' . ($d['directed_to'] ?: 'Personal profesional.') . '</div>';
        $html .= '<span class="label">Descripción y Objetivos:</span><div>' . ($d['description'] ?: 'En revisión.') . '</div>';
        $html .= '<div class="saltopagina"></div>';
        $html .= '<span class="label">Requisitos:</span><ul>';
        foreach($model->getRequirements($id) as $r) { $html .= '<li>' . htmlspecialchars($r['requirement_text']) . '</li>'; }
        $html .= '</ul><span class="label">Condiciones:</span><ul>';
        foreach($model->getConditions($id) as $c) { $html .= '<li>' . htmlspecialchars($c['condition_text']) . '</li>'; }
        $html .= '</ul><div style="margin-top:50px; text-align:center; font-weight:bold; border-top:1px solid #000;">CARGA HORARIA: ' . ($d['total_hours'] ?: '200') . ' HORAS.</div></body></html>';

        $dompdf->loadHtml($html);
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();
        if (ob_get_length()) ob_end_clean();
        $dompdf->stream($d['code'] . ".pdf", ["Attachment" => true]);
        exit;
    }
}