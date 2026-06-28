<?php
/**
 * MÓDULO: PORTAL DOCENTE / MATRÍCULA
 * ARCHIVO: app/controllers/ProfessorMatriculaController.php
 * PROPÓSITO: index() muestra el selector de clases + roster de estudiantes
 *            cuando se elige una. imprimir() genera el PDF oficial con
 *            encabezado UCLA (mismo patrón que StudentsCertificatesController)
 *            usando DomPDF (/tools/dompdf/autoload.inc.php).
 * VERSIÓN: 1.1.0 - Agrega método imprimir() para PDF de matrícula.
 *
 * RUTAS Bootstrap.php:
 *   $router->get('/professor/matricula',     [ProfessorMatriculaController::class, 'index']);
 *   $router->get('/professor/matricula/pdf', [ProfessorMatriculaController::class, 'imprimir']);
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ProfessorModel;
use App\Models\ProfessorMatriculaModel;
use Dompdf\Dompdf;
use Dompdf\Options;

class ProfessorMatriculaController extends Controller
{
    protected array $profesor;
    private ProfessorMatriculaModel $matriculaModel;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'PROFESOR') {
            header('Location: /diplomatic/public/login');
            exit;
        }

        $model    = new ProfessorModel();
        $profesor = $model->getProfessorByUserId((int) $_SESSION['user']['id']);

        if (!$profesor) {
            header('Location: /diplomatic/public/dashboard?error=profesor_sin_expediente');
            exit;
        }

        $this->profesor       = $profesor;
        $this->matriculaModel = new ProfessorMatriculaModel();
    }

    public function index(): void
    {
        $professorId  = (int) $this->profesor['id'];
        $periodoId    = (int) ($_GET['periodo_id'] ?? 0);
        $cohorteId    = (int) ($_GET['cohorte_id'] ?? 0);
        $offeringId   = (int) ($_GET['offering_id'] ?? 0);
        $cohorteId    = (int) ($_GET['cohorte_id'] ?? 0);
        $offeringId   = (int) ($_GET['offering_id'] ?? 0);
        $estudiantes  = null;
        $ofertaActiva = null;

        // Si no hay período seleccionado, mostrar TODAS las ofertas del profesor
$ofertas  = $this->matriculaModel->getMisOfertas($professorId, $periodoId ?: null, $cohorteId ?: null);
        $cohortes = $this->matriculaModel->getCohortesProfesor($professorId, $periodoId ?: 0);
$periodos = $this->matriculaModel->getPeriodosProfesor($professorId);
        $ofertasFiltradas = ($periodoId && $cohorteId) ? $ofertas : [];

        if ($offeringId) {
            if ($this->matriculaModel->profesorTieneAccesoOferta($professorId, $offeringId)) {
                $estudiantes = $this->matriculaModel->getEstudiantesPorOferta($offeringId);
                foreach ($ofertas as $o) {
                    if ((int) $o['offering_id'] === $offeringId) { $ofertaActiva = $o; break; }
                }
            }
        } else {
            $estudiantes = $this->matriculaModel->getTodosEstudiantes($professorId, $periodoId ?: null, $cohorteId ?: null);
        }

        $this->view('professor/matricula/index', [
            'profesor'     => $this->profesor,
            'ofertas'      => $ofertas,
            'offeringId'   => $offeringId,
            'estudiantes'  => $estudiantes,
            'ofertaActiva' => $ofertaActiva,
            'periodos'     => $periodos,
            'cohortes'     => $cohortes,
            'periodoId'    => $periodoId,
            'cohorteId'    => $cohorteId,
        ]);
    }

    public function imprimir(): void
    {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/diplomatic/tools/dompdf/autoload.inc.php';

        $professorId = (int) $this->profesor['id'];
        $offeringId  = (int) ($_GET['offering_id'] ?? 0);

        if (!$offeringId || !$this->matriculaModel->profesorTieneAccesoOferta($professorId, $offeringId)) {
            header('Location: /diplomatic/public/professor/matricula');
            exit;
        }

        $estudiantes  = $this->matriculaModel->getEstudiantesPorOferta($offeringId);
        $ofertaActiva = null;
        foreach ($this->matriculaModel->getMisOfertas($professorId) as $o) {
            if ((int) $o['offering_id'] === $offeringId) { $ofertaActiva = $o; break; }
        }

        if (!$ofertaActiva) {
            header('Location: /diplomatic/public/professor/matricula');
            exit;
        }

        $html = $this->construirHtml($ofertaActiva, $estudiantes);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();

        $nombreArchivo = 'Matricula-' . preg_replace('/[^A-Za-z0-9_-]/', '_', $ofertaActiva['diplomado_nombre']) . '-' . date('Ymd') . '.pdf';
        $dompdf->stream($nombreArchivo, ['Attachment' => 0]); // 0 = ver inline, 1 = descargar
        exit;
    }

    private function logoDataUrl(string $path, string $mime): string
    {
        $full = $_SERVER['DOCUMENT_ROOT'] . $path;
        if (!file_exists($full)) return '';
        return "data:{$mime};base64," . base64_encode(file_get_contents($full));
    }

    private function construirHtml(array $oferta, array $estudiantes): string
    {
        $imgUcla     = $this->logoDataUrl('/diplomatic/public/assets/uploads/logos/logo-ucla.png', 'image/png');
        $imgMedicina = $this->logoDataUrl('/diplomatic/public/assets/uploads/logos/logo-medicina.jpg', 'image/jpeg');

        $diplomado = htmlspecialchars($oferta['diplomado_nombre']);
        $cohorte   = htmlspecialchars($oferta['cohorte_nombre']);
        $grupos    = htmlspecialchars($oferta['grupos_nombre'] ?? '');
        $profesor  = htmlspecialchars($this->profesor['full_name']);
        $totalEst  = count($estudiantes);
        $fecha     = date('d/m/Y');

        $filas = '';
        $i = 1;
        foreach ($estudiantes as $e) {
            $filas .= "<tr>
                <td class='center'>{$i}</td>
                <td>" . htmlspecialchars($e['student_code']) . "</td>
                <td>" . htmlspecialchars($e['document_id']) . "</td>
                <td>" . htmlspecialchars($e['last_name'] . ', ' . $e['first_name']) . "</td>
                <td>" . htmlspecialchars($e['email']) . "</td>
                <td>" . htmlspecialchars($e['phone'] ?: '—') . "</td>
                <td class='center'>" . htmlspecialchars($e['status']) . "</td>
            </tr>";
            $i++;
        }
        if (empty($estudiantes)) {
            $filas = "<tr><td colspan='7' class='center' style='padding:20px;color:#888'>No hay estudiantes inscritos en esta oferta.</td></tr>";
        }

        return "<html><head><style>
            body { font-family: Helvetica, Arial, sans-serif; font-size: 9pt; margin: 1.2cm; }
            .header-table { width:100%; border-collapse:collapse; margin-bottom:15px; table-layout:fixed; }
            .header-table td { vertical-align:middle; }
            .header-title { font-weight:bold; font-size:10pt; line-height:1.3; text-align:center; }
            .titulo-doc { text-align:center; font-weight:bold; font-size:13pt; text-decoration:underline; margin:18px 0 14px 0; }
            .meta { border:1px solid #999; padding:10px; margin-bottom:12px; }
            .meta b { display:inline-block; min-width:90px; }
            table.lista { width:100%; border-collapse:collapse; }
            table.lista th { background:#eee; padding:6px 5px; border:1px solid #999; font-size:8.5pt; text-align:left; }
            table.lista td { padding:5px; border:1px solid #ccc; font-size:8.5pt; }
            .center { text-align:center; }
            .pie { font-size:7.5pt; color:#666; margin-top:18px; text-align:right; }
        </style></head><body>

        <table class='header-table'>
            <tr>
                <td style='width:15%;text-align:left;'><img src='{$imgUcla}' style='width:70px;'></td>
                <td style='width:70%;' class='header-title'>
                    UNIVERSIDAD CENTROCCIDENTAL &ldquo;LISANDRO ALVARADO&rdquo;<br>
                    DECANATO DE CIENCIAS DE LA SALUD<br>
                    &ldquo;Dr. PABLO ACOSTA ORTIZ&rdquo;<br>
                    COORDINACIÓN DE EXTENSIÓN
                </td>
                <td style='width:15%;text-align:right;'><img src='{$imgMedicina}' style='width:70px;'></td>
            </tr>
        </table>

        <div class='titulo-doc'>MATRÍCULA DE ESTUDIANTES</div>

        <div class='meta'>
            <div><b>Diplomado:</b> {$diplomado}</div>
            <div><b>Cohorte:</b> {$cohorte}" . ($grupos ? " &nbsp; <b>Grupo:</b> {$grupos}" : '') . "</div>
            <div><b>Profesor:</b> {$profesor}</div>
            <div><b>Total inscritos:</b> {$totalEst}</div>
        </div>

        <table class='lista'>
            <thead>
                <tr>
                    <th style='width:4%;'>#</th>
                    <th style='width:14%;'>Código</th>
                    <th style='width:10%;'>Cédula</th>
                    <th style='width:26%;'>Apellidos y Nombres</th>
                    <th style='width:21%;'>Email</th>
                    <th style='width:13%;'>Teléfono</th>
                    <th style='width:12%;'>Estado</th>
                </tr>
            </thead>
            <tbody>{$filas}</tbody>
        </table>

        <div class='pie'>Documento generado el {$fecha}</div>
        </body></html>";
    }
}