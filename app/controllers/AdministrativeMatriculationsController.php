<?php
/**
 * MÓDULO: ADMINISTRATIVO / MATRÍCULAS
 * ARCHIVO: app/controllers/AdministrativeMatriculationsController.php
 * PROPÓSITO: Controlador maestro para la gestión de cohortes, actas de notas y promoción a EGRESADO.
 * VERSIÓN: 1.7.5 - Full SweetAlert2 Integration: Eliminación de alert() nativo en impresión de actas.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AdministrativeMatriculationsModel;
use Throwable;

class AdministrativeMatriculationsController extends Controller
{
    private AdministrativeMatriculationsModel $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        // Blindaje de seguridad: Solo ADMIN
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ADMIN') {
            header('Location: /diplomatic/public/login');
            exit();
        }

        $this->model = new AdministrativeMatriculationsModel();
    }

    /**
     * Panel principal de cohortes.
     */
    public function index(): void
    {
        $this->view('administrative/matriculations/index', [
            'cohorts' => $this->model->getActiveCohorts()
        ]);
    }

    /**
     * Gestión de una cohorte específica.
     */
    public function manage(): void
    {
        $offeringId = (int)($_GET['id'] ?? 0);

        if ($offeringId === 0) {
            header('Location: /diplomatic/public/administrative/matriculations');
            exit;
        }

        $this->view('administrative/matriculations/manage', [
            'offering_id' => $offeringId,
            'students'    => $this->model->getStudentsByOffering($offeringId)
        ]);
    }

    /**
     * ACCIÓN: PROCESAR ACTA
     * Regla Institucional: Nota >= 15 = APROBADO | < 15 = REPROBADO.
     * Ambos casos promueven al alumno a EGRESADO en tbl_students.
     */
    public function procesarNotas(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $notas = $_POST['notas'] ?? []; 
                $procesados = 0;

                foreach ($notas as $id => $valor) {
                    $notaFinal = (float)$valor;
                    $nuevoEstado = ($notaFinal >= 15.00) ? 'APROBADO' : 'REPROBADO';
                    
                    if ($this->model->processStudentGrade((int)$id, $notaFinal, $nuevoEstado)) {
                        $procesados++;
                    }
                }

                $this->jsonFinal([
                    'status' => 'success',
                    'message' => "Se procesaron {$procesados} alumnos exitosamente. Los registros han sido promovidos a EGRESADOS."
                ]);

            } catch (Throwable $e) {
                $this->jsonFinal(['status' => 'error', 'message' => $e->getMessage()], 500);
            }
        }
    }

    /**
     * Gestión de estados manuales (CONGELADO / RETIRADO).
     */
    public function cambiarEstado(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $mid    = (int)($_POST['matricula_id'] ?? 0);
            $sid    = (int)($_POST['student_id'] ?? 0);
            $estado = $_POST['estado'] ?? ''; 

            if (in_array($estado, ['CONGELADO', 'RETIRADO', 'ACTIVO'])) {
                $resMatricula = $this->model->processStudentGrade($mid, 0.00, $estado);
                $resMaster    = $this->model->syncMasterStatus($sid, $estado);

                if ($resMatricula && $resMaster) {
                    $this->jsonFinal(['status' => 'success']);
                } else {
                    $this->jsonFinal(['status' => 'error', 'message' => 'Error de sincronización.'], 500);
                }
            } else {
                $this->jsonFinal(['status' => 'error', 'message' => 'Estado no permitido.'], 400);
            }
        }
    }

    /**
     * Genera lista de asistencia simple.
     */
    public function imprimirListado(): void
    {
        $offeringId = (int)($_GET['id'] ?? 0);
        if ($offeringId === 0) die("Error: No se especificó la cohorte.");

        $this->view('administrative/matriculations/print', [
            'header'   => $this->model->getCohortHeaderInfo($offeringId),
            'students' => $this->model->getStudentsByOffering($offeringId)
        ]);
    }

    /**
     * Genera el Listado de Asistencia en PDF.
     * Incluye columnas para Cédula, Nombre y un espacio amplio para la FIRMA.
     */
/**
     * Genera el Listado de Asistencia Profesional en PDF.
     * Incluye: Datos de Cohorte, Grupos asignados y espacio para Firma.
     */
    public function imprimirAsistencia(): void
    {
        // 1. Limpieza de salida para evitar errores en el PDF
        while (ob_get_level() > 0) ob_end_clean();

        $offeringId = (int)($_GET['id'] ?? 0);
        if ($offeringId === 0) die("Error: ID de cohorte no válido.");

        // 2. Obtener datos del Modelo (Ya incluye los grupos)
        $header = $this->model->getCohortHeaderInfo($offeringId);
        $students = $this->model->getStudentsByOffering($offeringId);

        if (empty($students)) die("No hay alumnos inscritos en esta cohorte.");

        // 3. Configuración de Dompdf
        require_once __DIR__ . '/../../tools/dompdf/autoload.inc.php';
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new \Dompdf\Dompdf($options);

        // 4. Diseño del Documento (HTML + CSS)
        $baseDir      = dirname(__DIR__, 2);
        $pathUcla     = $baseDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'logos' . DIRECTORY_SEPARATOR . 'logo-ucla.png';
        $pathMedicina = $baseDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'logos' . DIRECTORY_SEPARATOR . 'logo-medicina.jpg';

        $logoUcla     = file_exists($pathUcla)     ? 'data:image/png;base64,'  . base64_encode(file_get_contents($pathUcla))     : '';
        $logoMedicina = file_exists($pathMedicina) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($pathMedicina)) : '';

        $html = "
        <html>
        <head>
            <style>
                body { font-family: 'Helvetica', sans-serif; padding: 15px; color: #333; }
                .header-box { border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 20px; }
                .header-table { width: 100%; border-collapse: collapse; }
                .header-logo { width: 80px; text-align: center; vertical-align: middle; }
                .header-logo img { max-width: 75px; max-height: 75px; }
                .header-text { text-align: center; vertical-align: middle; font-size: 12px; line-height: 1.6; }
                .header-text strong { font-size: 13px; }
                .title { font-size: 16px; font-weight: bold; text-transform: uppercase; margin-top: 5px; }
                
                .info-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 11px; }
                .info-table td { padding: 5px; border: 1px solid #eee; }
                .label { font-weight: bold; background-color: #f9f9f9; width: 20%; }
                
                .attendance-table { width: 100%; border-collapse: collapse; }
                .attendance-table th { background-color: #0d6efd; color: white; border: 1px solid #000; padding: 8px; font-size: 10px; text-transform: uppercase; }
                .attendance-table td { border: 1px solid #000; padding: 10px 5px; font-size: 10px; }
                
                .text-center { text-align: center; }
                .signature-space { width: 180px; }
                .footer { margin-top: 30px; font-size: 9px; text-align: center; color: #666; font-style: italic; }
            </style>
        </head>
        <body>
                <div class='header-box'>
                <table class='header-table'>
                    <tr>
                        <td class='header-logo'>
                            <img src='{$logoUcla}'>
                        </td>
                        <td class='header-text'>
                            <strong>UNIVERSIDAD CENTROCCIDENTAL &quot;LISANDRO ALVARADO&quot;</strong><br>
                            DECANATO DE CIENCIAS DE LA SALUD<br>
                            COORDINACIÓN DE EXTENSIÓN<br>
                            <div class='title'>LISTADO DE INSCRITOS</div>
                            <div style='font-size: 11px; margin-top: 3px;'>{$header['diplomado_name']}</div>
                        </td>
                        <td class='header-logo'>
                            <img src='{$logoMedicina}'>
                        </td>
                    </tr>
                </table>
            </div>

            <table class='info-table'>
                <tr>
                    <td class='label'>COHORTE:</td>
                    <td>{$header['cohort_code']}</td>
                    <td class='label'>GRUPO(S):</td>
                    <td style='color: #0d6efd; font-weight: bold;'>".($header['grupos_nombres'] ?: 'GENERAL')."</td>
                </tr>
                <tr>
                    <td class='label'>FACILITADOR:</td>
                    <td>__________________________________</td>
                    <td class='label'>FECHA:</td>
                    <td>____ / ____ / ________</td>
                </tr>
                <tr>
                    <td class='label'>SESIÓN N°:</td>
                    <td>__________</td>
                    <td class='label'>OBSERVACIÓN:</td>
                    <td>_______________________</td>
                </tr>
            </table>

            <table class='attendance-table'>
                <thead>
                    <tr>
                        <th width='4%'>N°</th>
                        <th width='12%'>Cédula</th>
                        <th width='54%'>Apellidos y Nombres</th>
                        <th width='30%'>Firma del Estudiante</th>
                    </tr>
                </thead>
                <tbody>";

        $i = 1;
        foreach ($students as $s) {
            $nombreComp = mb_strtoupper($s['last_name'] . ', ' . $s['first_name'], 'UTF-8');
            $html .= "
                <tr>
                    <td class='text-center'>$i</td>
                    <td class='text-center'>{$s['cedula']}</td>
                    <td>{$nombreComp}</td>
                    <td class='signature-space'></td>
                </tr>";
            $i++;
        }

        $html .= "
                </tbody>
            </table>

            <div class='footer'>
                Este listado es propiedad de la Coordinación Académica. Prohibido tachaduras o enmiendas. <br>
                Generado por Sistema DIPLOMATIC el ".date('d/m/Y h:i A')."
            </div>
        </body>
        </html>";

        // 5. Generación del PDF
        $dompdf->loadHtml($html);
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();

        // 6. Lanzar al navegador
        $dompdf->stream("Asistencia_{$header['cohort_code']}.pdf", ["Attachment" => false]);
        exit;
    }

    /**
     * Genera el Acta Final en PDF.
     * VALIDACIÓN: Solo para alumnos con estatus APROBADO o REPROBADO con SweetAlert2.
     */
    public function imprimirActa(): void
    {
        while (ob_get_level() > 0) ob_end_clean();

        $offeringId = (int)($_GET['id'] ?? 0);
        if ($offeringId === 0) die("ID de cohorte no válido.");

        $header = $this->model->getCohortHeaderInfo($offeringId);
        $allStudents = $this->model->getStudentsByOffering($offeringId);

        // Filtramos solo los egresados (quienes terminaron el ciclo académico)
        $students = array_filter($allStudents, function($s) {
            return in_array($s['academic_status'], ['APROBADO', 'REPROBADO']);
        });

        // REGLA: Si no hay nadie calificado, lanzamos SweetAlert2 profesional
        if (empty($students)) {
            echo "
            <!DOCTYPE html>
            <html lang='es'>
            <head>
                <meta charset='UTF-8'>
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <style> body { font-family: sans-serif; background-color: #f8f9fa; } </style>
            </head>
            <body>
                <script>
                    Swal.fire({
                        icon: 'info',
                        title: 'Acta sin registros',
                        text: 'No se encontraron alumnos con estatus final (Aprobado/Reprobado). Procese el acta antes de imprimir.',
                        confirmButtonColor: '#0d6efd',
                        confirmButtonText: 'Regresar'
                    }).then(() => {
                        window.close();
                    });
                </script>
            </body>
            </html>";
            exit;
        }

        // Procedemos con la generación del PDF
        require_once __DIR__ . '/../../tools/dompdf/autoload.inc.php';
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new \Dompdf\Dompdf($options);

        $fecha = date("d/m/Y");
        $html = "
        <html>
        <head>
            <style>
                body { font-family: 'Helvetica', sans-serif; padding: 20px; color: #333; }
                .header { text-align: center; margin-bottom: 30px; border-bottom: 3px double #000; padding-bottom: 10px; }
                .title { font-size: 18px; font-weight: bold; text-transform: uppercase; margin-bottom: 5px; }
                .info-table { width: 100%; margin-bottom: 20px; font-size: 13px; }
                .grades-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                .grades-table th { background-color: #f2f2f2; border: 1px solid #000; padding: 8px; font-size: 12px; }
                .grades-table td { border: 1px solid #000; padding: 6px; font-size: 11px; text-align: center; }
                .text-left { text-align: left !important; }
                .footer { margin-top: 50px; width: 100%; }
                .sign-box { width: 45%; text-align: center; display: inline-block; }
                .line { border-top: 1px solid #000; width: 80%; margin: 0 auto 5px auto; }
            </style>
        </head>
        <body>
            <div class='header'>
                <div class='title'>Acta Final de Calificaciones</div>
                <div style='font-size: 12px;'>Control de Estudios y Registro Académico</div>
            </div>

            <table class='info-table'>
                <tr>
                    <td width='15%'><strong>Diplomado:</strong></td>
                    <td width='45%'>{$header['diplomado_name']}</td>
                    <td width='15%'><strong>Fecha:</strong></td>
                    <td width='25%'>$fecha</td>
                </tr>
                <tr>
                    <td><strong>Cohorte:</strong></td>
                    <td>{$header['cohort_code']}</td>
                    <td><strong>Estatus:</strong></td>
                    <td>CIERRE DE ACTA</td>
                </tr>
            </table>

            <table class='grades-table'>
                <thead>
                    <tr>
                        <th width='5%'>N°</th>
                        <th width='15%'>Cédula</th>
                        <th width='40%'>Apellidos y Nombres</th>
                        <th width='20%'>Estatus Final</th>
                        <th width='20%'>Calificación</th>
                    </tr>
                </thead>
                <tbody>";

        $index = 1;
        foreach ($students as $s) {
            $nota = number_format((float)$s['final_grade'], 2);
            $html .= "
                <tr>
                    <td>{$index}</td>
                    <td>{$s['cedula']}</td>
                    <td class='text-left'>{$s['last_name']} {$s['first_name']}</td>
                    <td style='font-weight: bold;'>{$s['academic_status']}</td>
                    <td style='font-size: 14px;'><strong>$nota</strong></td>
                </tr>";
            $index++;
        }

        $html .= "
                </tbody>
            </table>

            <div class='footer'>
                <div class='sign-box' style='float: left;'>
                    <div class='line'></div>
                    <div style='font-size: 12px;'>Firma del Facilitador</div>
                </div>
                <div class='sign-box' style='float: right;'>
                    <div class='line'></div>
                    <div style='font-size: 12px;'>Control de Estudios (Sello)</div>
                </div>
            </div>
        </body>
        </html>";

        $dompdf->loadHtml($html);
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();

        $dompdf->stream("Acta_{$header['cohort_code']}.pdf", ["Attachment" => false]);
        exit;
    }

    /**
     * Blindaje de salida JSON pura para evitar errores de parseo en el frontend.
     */
    private function jsonFinal(array $payload, int $code = 200): void 
    {
        while (ob_get_level() > 0) ob_end_clean();
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}