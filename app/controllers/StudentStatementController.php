<?php
/**
 * MÓDULO: AUTOGESTIÓN ESTUDIANTIL
 * ARCHIVO: app/controllers/StudentStatementController.php
 * PROPÓSITO: Gestión multi-programa de estados de cuenta y reportes PDF.
 * VERSIÓN: 3.6.0 - Refactor Premium: Diseño de PDF homologado con Admin y validación estricta de Expediente.
 */

declare(strict_types=1);

namespace App\Controllers;

// --- CARGA DE LIBRERÍA DOMPDF ---
$dompdfAutoloader = dirname(__DIR__, 2) . '/tools/dompdf/autoload.inc.php';

if (file_exists($dompdfAutoloader)) {
    require_once $dompdfAutoloader;
} else {
    $fallbackPath = 'C:/xampp/htdocs/diplomatic/tools/dompdf/autoload.inc.php';
    if (file_exists($fallbackPath)) {
        require_once $fallbackPath;
    }
}

use App\Core\Controller;
use App\Models\StudentStatementModel;
use Dompdf\Dompdf;
use Dompdf\Options;
use Exception;

class StudentStatementController extends Controller
{
    private StudentStatementModel $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        if (!isset($_SESSION['user'])) {
            header('Location: /diplomatic/public/login');
            exit();
        }

        $this->model = new StudentStatementModel();
    }

    public function index(): void
{
    if (ob_get_level() > 0) ob_end_clean();
    $userId = (int)$_SESSION['user']['id'];

    // --- BLOQUE DE SEGURIDAD ACADÉMICA ---
    // Verificamos que exista en tbl_students Y que su inscripción esté APROBADA[cite: 1, 2]
    $db = (new \App\Core\Database())->getConnection();
    $sql = "SELECT s.id FROM tbl_students s 
            INNER JOIN tbl_enrollments e ON s.enrollment_id = e.id 
            WHERE s.user_id = ? AND e.status = 'APROBADO' LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([$userId]);

    if (!$stmt->fetch()) {
        // Redirigimos al panel con la nueva alerta específica
        header('Location: /diplomatic/public/students?alert=no_statement_access');
        exit();
    }
    // -------------------------------------

    $myPrograms = $this->model->getMyPrograms($userId);

    $this->view('students/student_statement/index', [
        'title' => 'Mi Estado de Cuenta',
        'programs' => $myPrograms
    ]);
}


    /**
     * MOTOR DE VALIDACIÓN DE EXPEDIENTE (NUEVO)
     * Verifica si el usuario autenticado ya posee un registro formal en tbl_students.
     * Si no lo tiene, bloquea la carga del reporte para evitar errores de integridad.
     */
    private function validateActiveStudent(int $userId): void
{
    $db = (new \App\Core\Database())->getConnection();
    // Validamos la existencia y el estatus APROBADO de la inscripción vinculada[cite: 1, 2]
    $sql = "SELECT s.id FROM tbl_students s 
            INNER JOIN tbl_enrollments e ON s.enrollment_id = e.id 
            WHERE s.user_id = ? AND e.status = 'APROBADO' LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([$userId]);
    
    if (!$stmt->fetch()) {
        // Este mensaje será el que verán si intentan forzar una descarga por URL[cite: 1]
        throw new Exception("Su estado de cuenta estará disponible una vez que su inscripción sea validada por administración.");
    }
}

    public function getMyStatement(): void
    {
        if (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        try {
            $userId = (int)$_SESSION['user']['id'];
            $offeringId = (int)($_GET['offering_id'] ?? 0);
            
            if ($offeringId <= 0) throw new Exception('Debe seleccionar un programa válido.');

            // 1. Validar que tenga expediente real en tbl_students
            $this->validateActiveStudent($userId);

            // 2. Cargar datos del modelo
            $profile = $this->model->getProfileByProgram($userId, $offeringId);
            if (!$profile) throw new Exception('No se encontraron datos financieros para este programa.');

            $ledger = $this->model->getLedgerByProgram($userId, $offeringId);

            echo json_encode(['ok' => true, 'data' => ['student' => $profile, 'ledger' => $ledger]], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function getMyPaymentHistory(): void
    {
        if (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        try {
            $userId = (int)$_SESSION['user']['id'];
            
            // Validar que tenga expediente real en tbl_students
            $this->validateActiveStudent($userId);

            $history = $this->model->getPaymentHistory($userId);
            echo json_encode(['ok' => true, 'data' => $history], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

/**
     * EXPORTAR ESTADO DE CUENTA (Diseño Dinámico Bs/$)
     */
    public function exportMyStatementPdf(): void
    {
        if (ob_get_level() > 0) ob_end_clean();

        $userId = (int)$_SESSION['user']['id'];
        $offeringId = (int)($_GET['offering_id'] ?? 0);
        
        try {
            if ($offeringId <= 0) throw new Exception("Programa no especificado.");
            $this->validateActiveStudent($userId);
        } catch (Exception $e) {
            die("Error de validación: " . $e->getMessage());
        }

        $student = $this->model->getProfileByProgram($userId, $offeringId);
        if (!$student) die("Error: Datos del estudiante no encontrados.");
        
        $ledger = $this->model->getLedgerByProgram($userId, $offeringId);
        
        // Símbolo de moneda dinámico
        $simbol = (isset($student['moneda']) && $student['moneda'] === 'VES') ? 'Bs.' : '$';

        $html = '<html><head><style>
            body { font-family: "Helvetica", sans-serif; color: #333; font-size: 11px; padding: 20px; }
            .header { text-align: center; border-bottom: 2px solid #0d6efd; padding-bottom: 10px; margin-bottom: 20px; }
            .title { font-size: 22px; font-weight: bold; color: #0d6efd; text-transform: uppercase; margin: 0; }
            .info-box { border: 1px solid #ccc; border-radius: 5px; padding: 15px; margin-bottom: 20px; background-color: #fafafa; }
            .totals-box { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
            .totals-box td { padding: 10px; text-align: center; border: 1px solid #ddd; }
            .totals-box .bg-primary { background-color: #e9ecef; }
            .totals-box .bg-danger { background-color: #f8d7da; color: #842029; font-weight: bold; }
            table.ledger { width: 100%; border-collapse: collapse; margin-top: 10px; }
            table.ledger th { background-color: #0d6efd; color: white; padding: 8px; text-align: left; }
            table.ledger td { padding: 8px; border-bottom: 1px solid #eee; }
            .text-end { text-align: right; }
        </style></head><body>

            <div class="header">
                <h1 class="title">Estado de Cuenta Estudiantil</h1>
                <p style="margin:0; font-size:10px; color:#666;">Emitido por el sistema el: '.date('d/m/Y H:i').'</p>
            </div>

            <div class="info-box">
                <table width="100%" style="border:none;">
                    <tr>
                        <td width="60%"><strong>ESTUDIANTE:</strong> '.$student['first_name'].' '.$student['last_name'].'</td>
                        <td width="40%"><strong>CÉDULA:</strong> '.$student['document_id'].'</td>
                    </tr>
                    <tr>
                        <td colspan="2" style="padding-top:8px;"><strong>DIPLOMADO:</strong> '.$student['cohorte'].' - '.$student['diplomado'].'</td>
                    </tr>
                </table>
            </div>

            <table class="totals-box">
                <tr>
                    <td class="bg-primary"><strong>TOTAL DEUDA</strong><br><span style="font-size:14px;">'.$simbol.' '.number_format((float)$student['total_due'], 2).'</span></td>
                    <td class="bg-primary"><strong>TOTAL PAGADO</strong><br><span style="font-size:14px; color:green;">'.$simbol.' '.number_format((float)$student['total_paid'], 2).'</span></td>
                    <td class="bg-danger"><strong>SALDO PENDIENTE</strong><br><span style="font-size:16px;">'.$simbol.' '.number_format((float)$student['balance'], 2).'</span></td>
                </tr>
            </table>

            <h3 style="color:#0d6efd; border-bottom: 1px solid #0d6efd;">Detalle de Movimientos</h3>
            <table class="ledger">
                <thead><tr><th>FECHA</th><th>CONCEPTO</th><th class="text-end">CARGO</th><th class="text-end">ABONO</th><th class="text-end">SALDO</th></tr></thead>
                <tbody>';
                
                $runningBalance = 0;
                foreach ($ledger as $l) {
                    $cargo = (float)$l['amount_due'];
                    $abono = (float)$l['amount_paid'];
                    $runningBalance += ($cargo - $abono);
                    $html .= '<tr>
                        <td>'.$l['formatted_date'].'</td>
                        <td>'.$l['concept'].'</td>
                        <td class="text-end">'.($cargo > 0 ? $simbol.' '.number_format($cargo, 2) : '-').'</td>
                        <td class="text-end">'.($abono > 0 ? $simbol.' '.number_format($abono, 2) : '-').'</td>
                        <td class="text-end"><strong>'.$simbol.' '.number_format($runningBalance, 2).'</strong></td>
                    </tr>';
                }
                
        $html .= '</tbody></table></body></html>';
        $this->generatePdf($html, "Estado_Cuenta_".$student['document_id']);
    }

    /**
     * EXPORTAR HISTORIAL GLOBAL (Dual Bs/USD - Versión Estudiante)
     */
    public function exportMyPaymentPdf(): void
    {
        if (ob_get_level() > 0) ob_end_clean();
        $userId = (int)$_SESSION['user']['id'];
        
        try {
            $this->validateActiveStudent($userId);
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
        
        $student = $this->model->getBasicStudentData($userId);
        $payments = $this->model->getPaymentHistory($userId);

        $html = '<html><head><style>
            body { font-family: "Helvetica", sans-serif; color: #333; line-height: 1.4; padding: 15px; }
            .header { text-align: center; border-bottom: 2px solid #198754; padding-bottom: 8px; margin-bottom: 20px; }
            .title { font-size: 19px; font-weight: bold; color: #198754; text-transform: uppercase; margin: 0; }
            .student-info { margin-bottom: 20px; font-size: 12px; border: 1px solid #eee; padding: 12px; background-color: #fcfcfc; }
            table.main-table { width: 100%; border-collapse: collapse; }
            table.main-table th { background-color: #198754; color: white; font-size: 10px; padding: 8px; text-align: left; }
            table.main-table td { font-size: 10px; padding: 7px; border-bottom: 1px solid #eee; }
            .text-end { text-align: right; }
            .total-row { font-size: 14px; font-weight: bold; text-align: right; color: #198754; margin-top:10px; }
        </style></head><body>
            <div class="header">
                <h1 class="title">Mi Historial de Pagos Verificados</h1>
                <p style="margin:0; font-size:9px; color:#999;">Generado el: '.date('d/m/Y H:i').'</p>
            </div>
            <div class="student-info">
                <strong>ESTUDIANTE:</strong> ' . $student['document_id'] . ' - ' . $student['first_name'] . ' ' . $student['last_name'] . '
            </div>
            <table class="main-table">
                <thead>
                    <tr>
                        <th>FECHA</th>
                        <th>CONCEPTO / TASA</th>
                        <th class="text-end">MONTO BS.</th>
                        <th class="text-end">MONTO USD ($)</th>
                        <th class="text-end">REFERENCIA</th>
                    </tr>
                </thead>
                <tbody>';
                
                $totalBs = 0; $totalUsd = 0;

                foreach ($payments as $p) {
                    $mBs = (float)$p['monto_real_bs']; 
                    $mUsd = (float)$p['monto_usd'];
                    $tasa = (float)$p['tasa'];

                    $totalBs += $mBs;
                    $totalUsd += $mUsd;

                    $html .= '<tr>
                                <td>'.$p['formatted_date'].'</td>
                                <td><strong>'.$p['concepto'].'</strong><br><small style="color:#777;">Tasa: '.number_format($tasa, 2, ',', '.').' Bs.</small></td>
                                <td class="text-end">'.number_format($mBs, 2, ',', '.').'</td>
                                <td class="text-end" style="background-color:#f9f9f9;"><strong>$ '.number_format($mUsd, 2).'</strong></td>
                                <td class="text-end">'.$p['referencia'].'</td>
                              </tr>';
                }

        $html .= '</tbody></table>
            <div class="total-row">ABONOS TOTALES EN BOLÍVARES: '.number_format($totalBs, 2, ',', '.').' Bs.</div>
            <div class="total-row" style="font-size:17px;">ABONOS TOTALES EN DÓLARES: $ '.number_format($totalUsd, 2).'</div>
        </body></html>';

        $this->generatePdf($html, "Historial_Pagos_".$student['document_id']);
    }

    private function generatePdf(string $html, string $filename): void
    {
        try {
            if (ob_get_level() > 0) ob_end_clean();

            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'Helvetica');

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $filename . '.pdf"');
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');

            echo $dompdf->output();
            exit;
        } catch (Exception $e) {
            die("Error crítico PDF: " . $e->getMessage());
        }
    }

}