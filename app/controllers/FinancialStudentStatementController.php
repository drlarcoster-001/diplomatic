<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / ESTADOS DE CUENTA
 * ARCHIVO: app/controllers/FinancialStudentStatementController.php
 * PROPÓSITO: APIs JSON y Reportes PDF con Resolución Dual de IDs (Enrollment/Offering).
 * VERSIÓN: 3.6.0 - Diseño Premium de PDFs: Inclusión de expediente completo y consolidación de saldos.
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
use App\Models\FinancialStudentStatementModel;
use Dompdf\Dompdf;
use Dompdf\Options;
use Exception;

class FinancialStudentStatementController extends Controller
{
    private FinancialStudentStatementModel $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->model = new FinancialStudentStatementModel();
    }

    public function index(): void
    {
        if (ob_get_level() > 0) ob_end_clean();
        $this->view('financial/student_statement/index', ['title' => 'Estados de Cuenta']);
    }

    /**
     * PASO 1: Búsqueda reactiva de estudiantes para el dropdown.
     */
    public function searchStudents(): void
    {
        if (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        try {
            $term = trim($_GET['term'] ?? '');
            if (strlen($term) < 3) throw new Exception('Término demasiado corto.');
            
            $students = $this->model->searchStudentsForDropdown($term);
            echo json_encode(['ok' => true, 'data' => $students], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

/**
     * MOTOR DE RESOLUCIÓN DE IDs (CORREGIDO v3.6.1)
     * Ahora valida estrictamente que la inscripción pertenezca al usuario para evitar cruces.
     */
private function resolveEnrollmentId(int $inputId, int $userId = 0): int
{
    if ($inputId <= 0) throw new Exception('Identificador de consulta no válido.');

    $db = (new \App\Core\Database())->getConnection();

    // Si tenemos el userId, validamos que la inscripción le pertenezca estrictamente
    if ($userId > 0) {
        $stmt = $db->prepare("SELECT id FROM tbl_enrollments WHERE (id = ? OR offering_id = ?) AND user_id = ? LIMIT 1");
        $stmt->execute([$inputId, $inputId, $userId]);
        $enrollment = $stmt->fetch();

        if ($enrollment) {
            return (int)$enrollment['id'];
        }
    }

    // Fallback: Si no hay userId, buscamos existencia directa (solo por compatibilidad)
    $stmtFallback = $db->prepare("SELECT id FROM tbl_enrollments WHERE id = ?");
    $stmtFallback->execute([$inputId]);
    $res = $stmtFallback->fetch();

    return $res ? (int)$res['id'] : throw new Exception("La inscripción no coincide con el estudiante seleccionado.");
}



    /**
     * PASO 2: Obtener Ledger 
     */
    public function getStatement(): void
    {
        if (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $inputId = (int)($_GET['enrollment_id'] ?? 0);
            $userId = (int)($_GET['user_id'] ?? 0);

            // Traducción del ID antes de buscar el dinero
            $finalId = $this->resolveEnrollmentId($inputId, $userId);

            // Carga de datos financieros
            $studentProfile = $this->model->getStudentFinancialProfileByEnrollment($finalId);
            $ledgerData = $this->model->getLedgerMovementsByEnrollment($finalId);

            echo json_encode([
                'ok' => true, 
                'data' => [
                    'student' => $studentProfile, 
                    'ledger' => $ledgerData
                ]
            ], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            echo json_encode([
                'ok' => false, 
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    /**
     * PASO 3: Historial de pagos (API para el Modal)
     */
    public function getPaymentHistory(): void
    {
        if (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        try {
            $inputId = (int)($_GET['enrollment_id'] ?? 0);
            $userId = (int)($_GET['user_id'] ?? 0);

            // Traducción del ID
            $finalId = $this->resolveEnrollmentId($inputId, $userId);

            $history = $this->model->getPaymentHistoryByEnrollment($finalId);
            echo json_encode(['ok' => true, 'data' => $history], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }


/**
     * REPORTE PDF: Historial de Pagos (CORREGIDO v3.6.2 - Cero errores de redondeo)
     */
    public function exportPaymentPdf(): void
    {
        $inputId = (int)($_GET['enrollment_id'] ?? 0);
        $userId = (int)($_GET['user_id'] ?? 0);

        try {
            $finalId = $this->resolveEnrollmentId($inputId, $userId);
        } catch (Exception $e) {
            die("Error de Resolución: " . $e->getMessage());
        }

        $student = $this->model->getStudentFinancialProfileByEnrollment($finalId);
        if (!$student) die("Error: Datos no encontrados.");
        
        $payments = $this->model->getPaymentHistoryByEnrollment($finalId);

        // Estilos y encabezado (Mantenemos lo que ya tienes)
        $html = '<html><head><style>
            body { font-family: "Helvetica", sans-serif; color: #333; line-height: 1.4; padding: 15px; }
            .header { text-align: center; border-bottom: 2px solid #198754; padding-bottom: 8px; margin-bottom: 20px; }
            .title { font-size: 19px; font-weight: bold; color: #198754; text-transform: uppercase; margin: 0; }
            .student-info { margin-bottom: 20px; font-size: 12px; border: 1px solid #eee; padding: 12px; background-color: #fcfcfc; }
            table.main-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            table.main-table th { background-color: #198754; color: white; font-size: 10px; padding: 8px; text-align: left; }
            table.main-table td { font-size: 10px; padding: 7px; border-bottom: 1px solid #eee; }
            .text-end { text-align: right; }
            .total-section { margin-top: 20px; border-top: 2px solid #198754; padding-top: 10px; }
            .total-row { font-size: 14px; font-weight: bold; text-align: right; color: #198754; }
        </style></head><body>
            <div class="header">
                <h1 class="title">Historial de Pagos Verificados</h1>
                <p style="margin:0; font-size:9px; color:#999;">Generado el: '.date('d/m/Y H:i').'</p>
            </div>
            <div class="student-info">
                <strong>PROGRAMA:</strong> ' . $student['cohorte'] . ' - ' . $student['diplomado'] . '<br>
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
                
                $totalBs = 0;
                $totalUsd = 0;

                foreach ($payments as $p) {
                    // REGLA DE ORO: Usamos el monto_real_bs que viene del Modelo (vía JSON_VALUE)
                    // Este valor ya trae los 5.000,00 exactos de la base de datos.
                    $montoBs = (float)$p['monto_real_bs']; 
                    $montoUsd = (float)$p['monto_usd'];
                    $tasa = (float)$p['tasa'];

                    $totalBs += $montoBs;
                    $totalUsd += $montoUsd;

                    $html .= '<tr>
                                <td>'.$p['formatted_date'].'</td>
                                <td>
                                    <strong>'.$p['concept'].'</strong><br>
                                    <span style="font-size:8px; color:#777;">Tasa aplicada: '.number_format($tasa, 2, ',', '.').' Bs.</span>
                                </td>
                                <td class="text-end">'.number_format($montoBs, 2, ',', '.').'</td>
                                <td class="text-end" style="background-color:#f9f9f9;"><strong>$ '.number_format($montoUsd, 2).'</strong></td>
                                <td class="text-end">'.$p['referencia'].'</td>
                              </tr>';
                }

        $html .= '</tbody>
            </table>
            <div class="total-section">
                <div class="total-row">TOTAL PAGADO EN BOLÍVARES: '.number_format($totalBs, 2, ',', '.').' Bs.</div>
                <div class="total-row" style="font-size:18px;">TOTAL PAGADO EN DÓLARES: $ '.number_format($totalUsd, 2).'</div>
            </div>
        </body></html>';

        $this->generatePdf($html, "Pagos_".$student['document_id']);
    }

    /**
     * REPORTE PDF: Estado de Cuenta.
     * Incorpora el nuevo diseño detallado (Nombre, Deuda, Pagado, Saldo Pendiente).
     */
    public function exportStatementPdf(): void
    {
        $inputId = (int)($_GET['enrollment_id'] ?? 0);
        $userId = (int)($_GET['user_id'] ?? 0);

        try {
            // Traducción del ID para el PDF
            $finalId = $this->resolveEnrollmentId($inputId, $userId);
        } catch (Exception $e) {
            die("Error de Resolución: " . $e->getMessage());
        }

        $student = $this->model->getStudentFinancialProfileByEnrollment($finalId);
        if (!$student) die("Error: Datos no encontrados.");
        
        $ledger = $this->model->getLedgerMovementsByEnrollment($finalId);

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
                <p style="margin:0; font-size:10px; color:#666;">Fecha de Emisión: '.date('d/m/Y H:i').'</p>
            </div>

            <div class="info-box">
                <table width="100%" style="border:none; margin:0; padding:0;">
                    <tr>
                        <td width="60%" style="border:none; padding:0;"><strong>ESTUDIANTE:</strong> '.$student['first_name'].' '.$student['last_name'].'</td>
                        <td width="40%" style="border:none; padding:0;"><strong>CÉDULA / ID:</strong> '.$student['document_id'].'</td>
                    </tr>
                    <tr>
                        <td colspan="2" style="border:none; padding-top: 8px;"><strong>DIPLOMADO:</strong> '.$student['cohorte'].' - '.$student['diplomado'].'</td>
                    </tr>
                </table>
            </div>

            <table class="totals-box">
                <tr>
                    <td class="bg-primary"><strong>TOTAL DEUDA CONTRATADA</strong><br><span style="font-size:14px;">$ '.number_format((float)$student['total_due'], 2).'</span></td>
                    <td class="bg-primary"><strong>TOTAL PAGADO A LA FECHA</strong><br><span style="font-size:14px; color:green;">$ '.number_format((float)$student['total_paid'], 2).'</span></td>
                    <td class="bg-danger"><strong>SALDO PENDIENTE</strong><br><span style="font-size:16px;">$ '.number_format((float)$student['balance'], 2).'</span></td>
                </tr>
            </table>

            <h3 style="color:#0d6efd; border-bottom: 1px solid #0d6efd; padding-bottom: 5px;">Detalle de Movimientos</h3>
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
                        <td class="text-end">'.($cargo > 0 ? '$ '.number_format($cargo, 2) : '-').'</td>
                        <td class="text-end">'.($abono > 0 ? '$ '.number_format($abono, 2) : '-').'</td>
                        <td class="text-end"><strong>$ '.number_format($runningBalance, 2).'</strong></td>
                    </tr>';
                }
                
        $html .= '</tbody></table></body></html>';

        $this->generatePdf($html, "Estado_Cuenta_".$student['document_id']);
    }


private function generatePdf(string $html, string $filename): void
{
    try {
        // BLINDAJE: Limpiamos cualquier eco, warning o espacio en blanco previo
        if (ob_get_level() > 0) ob_end_clean();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Enviamos las cabeceras correctas manualmente para asegurar la carga
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '.pdf"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        echo $dompdf->output();
        exit; // Detenemos la ejecución aquí
    } catch (Exception $e) {
        die("Error crítico en generación PDF: " . $e->getMessage());
    }
}
    }