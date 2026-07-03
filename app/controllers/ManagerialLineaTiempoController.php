<?php
/**
 * MÓDULO: GESTIÓN GERENCIAL / LÍNEA DE TIEMPO DEL ESTUDIANTE
 * ARCHIVO: app/controllers/ManagerialLineaTiempoController.php
 * PROPÓSITO: index() muestra la línea de tiempo de una inscripción puntual.
 *            getUsuarios() busca usuarios en todo el sistema (paso 1).
 *            getPeriodosPorUsuario() trae solo los períodos donde ese
 *            usuario tiene inscripciones (paso 2).
 *            getOfertasPorUsuario() trae los diplomados de ese usuario en
 *            ese período, con su enrollment_id (paso 3) — soporta usuarios
 *            con varios diplomados inscritos.
 * VERSIÓN: 2.0.0 - Cascada invertida: Usuario → Período → Diplomado.
 *          Reemplaza getOfertas()/getEstudiantes() por getUsuarios(),
 *          getPeriodosPorUsuario() y getOfertasPorUsuario(). Fix: getActa
 *          ahora recibe siempre el offering_id real del enrollment.
 *
 * RUTAS Bootstrap.php:
 *   use App\Controllers\ManagerialLineaTiempoController;
 *   $router->get('/managerial/linea-tiempo',          [ManagerialLineaTiempoController::class, 'index']);
 *   $router->get('/managerial/linea-tiempo/usuarios', [ManagerialLineaTiempoController::class, 'getUsuarios']);
 *   $router->get('/managerial/linea-tiempo/periodos', [ManagerialLineaTiempoController::class, 'getPeriodosPorUsuario']);
 *   $router->get('/managerial/linea-tiempo/ofertas',  [ManagerialLineaTiempoController::class, 'getOfertasPorUsuario']);
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ManagerialLineaTiempoModel;
use Throwable;

class ManagerialLineaTiempoController extends Controller
{
    private ManagerialLineaTiempoModel $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ADMIN') {
            header('Location: /diplomatic/public/login');
            exit;
        }
        $this->model = new ManagerialLineaTiempoModel();
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(): void
    {
        $userId       = (int)   ($_GET['user_id']       ?? 0);
        $periodoId    = (int)   ($_GET['periodo_id']    ?? 0);
        $enrollmentId = (int)   ($_GET['enrollment_id'] ?? 0);
        $userSearch   = trim(   $_GET['user_search']    ?? '');

        $estudiante = null;
        $eventos    = [];

        if ($enrollmentId) {
            $estudiante = $this->model->getDatosEstudiante($enrollmentId);

            if ($estudiante) {
                $offeringId = (int) $estudiante['offering_id'];

                $pagosInsc  = $this->model->getPagosInscripcion($enrollmentId);
                $ledger     = $this->model->getLedger($enrollmentId);
                $pagosCuota = $this->model->getPagosCuotas($enrollmentId);
                $notas      = $this->model->getNotas($enrollmentId);
                $acta       = $this->model->getActa($offeringId, $enrollmentId);

                // Armar ledger por payment_id para lookup rápido
                $ledgerPorPago = [];
                foreach ($ledger as $l) {
                    if ($l['payment_id']) {
                        $ledgerPorPago[$l['payment_id']][] = $l;
                    }
                }

                $eventos = $this->buildEventos($estudiante, $pagosInsc, $ledger, $ledgerPorPago, $pagosCuota, $notas, $acta);
            }
        }

        $this->view('managerial/linea_tiempo/index', [
            'userId'       => $userId,
            'periodoId'    => $periodoId,
            'enrollmentId' => $enrollmentId,
            'userSearch'   => $userSearch,
            'estudiante'   => $estudiante,
            'eventos'      => $eventos,
        ]);
    }

    // =========================================================================
    // ARMAR EVENTOS
    // =========================================================================

    private function buildEventos(array $est, array $pagosInsc, array $ledger, array $ledgerPorPago, array $pagosCuota, array $notas, ?array $acta): array
    {
        $eventos = [];

        // 1. REGISTRO
        $eventos[] = [
            'tipo'    => 'registro',
            'icono'   => 'bi-person-plus-fill',
            'color'   => '#0d6efd',
            'titulo'  => 'Registro de Usuario',
            'fecha'   => $est['fecha_registro'],
            'detalle' => 'El usuario creó su cuenta en el sistema.',
        ];

        // 2. INSCRIPCIÓN
        $eventos[] = [
            'tipo'    => 'inscripcion',
            'icono'   => 'bi-clipboard-plus-fill',
            'color'   => '#6f42c1',
            'titulo'  => 'Solicitud de Inscripción',
            'fecha'   => $est['fecha_inscripcion'],
            'detalle' => 'Se registró la solicitud de inscripción al diplomado.',
        ];

        // 3. DOCUMENTOS
        $docsFecha = $est['fecha_inscripcion']; // misma fecha aprox
        $docsOk    = !empty($est['doc_id_card']) && !empty($est['doc_degree']) && !empty($est['doc_cv']);
        $eventos[] = [
            'tipo'    => 'documentos',
            'icono'   => 'bi-folder-fill',
            'color'   => '#fd7e14',
            'titulo'  => 'Carga de Documentos',
            'fecha'   => $docsFecha,
            'detalle' => $docsOk ? 'Cédula, título y CV cargados correctamente.' : 'Documentos incompletos.',
            'estado'  => $docsOk ? 'ok' : 'pendiente',
        ];

        // 4. PAGOS DE INSCRIPCIÓN
        foreach ($pagosInsc as $pago) {
            $meta   = json_decode($pago['payment_metadata'] ?? '{}', true);
            $usd    = $meta['monto_sistema_usd'] ?? null;

            $ledgerItems = $ledgerPorPago[$pago['id']] ?? [];
            $conceptos   = array_column($ledgerItems, 'concept');
            $conceptoStr = !empty($conceptos) ? implode(', ', $conceptos) : '—';

            $labelMethod = ['CASH' => 'Efectivo', 'ZELLE' => 'Zelle', 'BINANCE' => 'Binance', 'PAGOMOVIL' => 'Pago Móvil'];
            $metodo      = $labelMethod[$pago['method']] ?? $pago['method'];
            $labelStatus = ['APPROVED' => 'Aprobado', 'PENDING' => 'Pendiente', 'REJECTED' => 'Rechazado'];
            $colorStatus = ['APPROVED' => '#198754', 'PENDING' => '#ffc107', 'REJECTED' => '#dc3545'];

            $eventos[] = [
                'tipo'       => 'pago_inscripcion',
                'icono'      => 'bi-cash-coin',
                'color'      => '#198754',
                'titulo'     => 'Registro de Pago',
                'fecha'      => $pago['created_at'],
                'estado'     => $pago['status'],
                'detalle'    => "Método: {$metodo} · Ref: {$pago['reference_id']} · Bs. " . number_format((float)$pago['amount'], 2) . ($usd ? " · \${$usd} USD" : ''),
                'sub'        => $conceptoStr,
                'validado'   => $pago['validation_date'],
                'color_est'  => $colorStatus[$pago['status']] ?? '#6c757d',
                'label_est'  => $labelStatus[$pago['status']] ?? $pago['status'],
            ];

            // 5. VALIDACIÓN DEL PAGO
            if ($pago['validation_date']) {
                $eventos[] = [
                    'tipo'    => 'validacion_pago',
                    'icono'   => 'bi-check-circle-fill',
                    'color'   => '#198754',
                    'titulo'  => 'Validación de Pago',
                    'fecha'   => $pago['validation_date'],
                    'detalle' => "Pago ref. {$pago['reference_id']} validado.",
                    'sub'     => "Correspondió a: {$conceptoStr}",
                ];
            }
        }

        // 6. APROBACIÓN DE EXPEDIENTE
        if ($est['expediente_status'] === 'APROBADO') {
            $eventos[] = [
                'tipo'    => 'expediente',
                'icono'   => 'bi-shield-fill-check',
                'color'   => '#20c997',
                'titulo'  => 'Aprobación de Expediente',
                'fecha'   => $est['fecha_expediente'],
                'detalle' => 'El expediente del participante fue revisado y aprobado.',
            ];
        }

        // 7. CREACIÓN DE ESTUDIANTE
        if ($est['student_id']) {
            $eventos[] = [
                'tipo'    => 'estudiante',
                'icono'   => 'bi-person-badge-fill',
                'color'   => '#533AB7',
                'titulo'  => 'Creación de Estudiante',
                'fecha'   => $est['fecha_creacion_estudiante'],
                'detalle' => "Código de matrícula asignado: {$est['student_code']}",
            ];
        }

        // 8. MATRÍCULA
        if ($est['matriculation_id']) {
            $eventos[] = [
                'tipo'    => 'matricula',
                'icono'   => 'bi-mortarboard-fill',
                'color'   => '#0dcaf0',
                'titulo'  => 'Asignación de Matrícula',
                'fecha'   => $est['fecha_matricula'],
                'detalle' => "Matrícula #{$est['student_code']} · Estado: {$est['academic_status']}",
            ];
        }

        // 9. PAGOS DE CUOTAS
        $labelMethod = ['CASH' => 'Efectivo', 'ZELLE' => 'Zelle', 'BINANCE' => 'Binance', 'PAGOMOVIL' => 'Pago Móvil'];
        foreach ($pagosCuota as $pc) {
            $meta    = json_decode($pc['payment_metadata'] ?? '{}', true);
            $usd     = $meta['monto_sistema_usd'] ?? null;
            $metodo  = $labelMethod[$pc['method']] ?? $pc['method'];
            $cobrador = !empty($pc['cobrador_last']) ? "{$pc['cobrador_last']}, {$pc['cobrador_first']}" : '—';

            $eventos[] = [
                'tipo'    => 'pago_cuota',
                'icono'   => 'bi-wallet2',
                'color'   => '#0d6efd',
                'titulo'  => 'Pago de Cuota',
                'fecha'   => $pc['created_at'],
                'detalle' => "Método: {$metodo} · Ref: {$pc['reference_id']} · Bs. " . number_format((float)$pc['amount'], 2) . ($usd ? " · \${$usd} USD" : ''),
                'sub'     => "Registrado por: {$cobrador}",
                'estado'  => $pc['status'],
            ];
        }

        // 10. NOTAS
        if (!empty($notas)) {
            $labelMod = ['TEORICA' => 'Teórica', 'PRACTICA' => 'Práctica', 'VIRTUAL' => 'Virtual'];
            $detNotas = [];
            foreach ($notas as $n) {
                $detNotas[] = ($labelMod[$n['modalidad']] ?? $n['modalidad']) . ': ' . (int)round((float)$n['nota']);
            }
            $eventos[] = [
                'tipo'    => 'notas',
                'icono'   => 'bi-journal-check',
                'color'   => '#d63384',
                'titulo'  => 'Registro de Notas',
                'fecha'   => $notas[0]['created_at'] ?? null,
                'detalle' => implode(' · ', $detNotas),
            ];
        }

        // 11. CIERRE DEL ACTA
        if ($acta) {
            $eventos[] = [
                'tipo'    => 'acta',
                'icono'   => 'bi-lock-fill',
                'color'   => '#dc3545',
                'titulo'  => 'Cierre del Acta',
                'fecha'   => $acta['fecha_acta'],
                'detalle' => 'Acta aprobada y expediente cerrado.',
            ];
        }

        // Ordenar por fecha
        usort($eventos, fn($a, $b) => strtotime($a['fecha'] ?? '0') <=> strtotime($b['fecha'] ?? '0'));

        return $eventos;
    }

    // =========================================================================
    // AJAX — PASO 1: BÚSQUEDA GLOBAL DE USUARIO
    // =========================================================================

    public function getUsuarios(): void
    {
        $search = trim($_GET['search'] ?? '');
        $this->jsonResponse($this->model->buscarUsuarios($search));
    }

    // =========================================================================
    // AJAX — PASO 2: PERÍODOS DONDE ESE USUARIO TIENE INSCRIPCIONES
    // =========================================================================

    public function getPeriodosPorUsuario(): void
    {
        $this->jsonResponse($this->model->getPeriodosByUsuario((int)($_GET['user_id'] ?? 0)));
    }

    // =========================================================================
    // AJAX — PASO 3: DIPLOMADOS DE ESE USUARIO EN ESE PERÍODO
    // =========================================================================

    public function getOfertasPorUsuario(): void
    {
        $this->jsonResponse($this->model->getOfertasByUsuarioPeriodo(
            (int) ($_GET['user_id']    ?? 0),
            (int) ($_GET['periodo_id'] ?? 0)
        ));
    }

    private function jsonResponse(array $data): void
    {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
        exit;
    }
}