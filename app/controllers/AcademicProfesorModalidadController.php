<?php
/**
 * MÓDULO: ACADÉMICO / ASIGNACIÓN DE MODALIDAD A PROFESORES
 * ARCHIVO: app/controllers/AcademicProfesorModalidadController.php
 * PROPÓSITO: index() lista asignaciones con buscador. save() crea una
 *            nueva. update() edita una existente (desde el modal que se
 *            abre al hacer clic en una fila). delete() elimina.
 * VERSIÓN: 2.0.0 - Rediseño: modal crear/editar, buscadores inteligentes,
 *           clic en fila para editar.
 *
 * RUTAS Bootstrap.php:
 *   use App\Controllers\AcademicProfesorModalidadController;
 *   $router->get('/academic/profesor-modalidad',         [...,'index']);
 *   $router->post('/academic/profesor-modalidad/save',   [...,'save']);
 *   $router->post('/academic/profesor-modalidad/update', [...,'update']);
 *   $router->post('/academic/profesor-modalidad/delete', [...,'delete']);
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AcademicProfesorModalidadModel;
use App\Services\AuditService;
use Throwable;

class AcademicProfesorModalidadController extends Controller
{
    private AcademicProfesorModalidadModel $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ADMIN') {
            header('Location: /diplomatic/public/login');
            exit;
        }
        $this->model = new AcademicProfesorModalidadModel();
    }

    public function index(): void
    {
        $search = trim($_GET['search'] ?? '');
        $this->view('academic/profesor_modalidad/index', [
            'asignaciones'        => $this->model->getAsignaciones($search),
            'profesores'          => $this->model->getProfesores(),
            'ofertas'             => $this->model->getOfertas(),
            'mapaProfesorOfertas' => $this->model->getMapaProfesorOfertas(),
            'mapaOfertaGrupos'    => $this->model->getMapaOfertaGrupos(),
            'search'              => $search,
        ]);
    }

    public function save(): void
    {
        try {
            $offeringId  = (int) ($_POST['offering_id'] ?? 0);
            $professorId = (int) ($_POST['professor_id'] ?? 0);
            $groupId     = !empty($_POST['offering_group_id']) ? (int) $_POST['offering_group_id'] : null;
            $modalidades = $_POST['modalidad'] ?? []; // ahora llega como array (checkboxes)
            if (!is_array($modalidades)) $modalidades = [$modalidades];

            $tiposValidos = ['TEORICA', 'PRACTICA', 'VIRTUAL'];
            $modalidades  = array_values(array_intersect($modalidades, $tiposValidos));

            if (!$offeringId || !$professorId || empty($modalidades)) {
                header('Location: /diplomatic/public/academic/profesor-modalidad?error=incompleto');
                exit;
            }
            if (!$this->model->profesorTieneOferta($professorId, $offeringId)) {
                header('Location: /diplomatic/public/academic/profesor-modalidad?error=sinvinculo');
                exit;
            }
            if (in_array('TEORICA', $modalidades, true) && !$groupId) {
                header('Location: /diplomatic/public/academic/profesor-modalidad?error=falta_grupo');
                exit;
            }
            if ($this->model->esOfertaOnline($offeringId) && array_diff($modalidades, ['VIRTUAL'])) {
                header('Location: /diplomatic/public/academic/profesor-modalidad?error=oferta_online');
                exit;
            }

            $userId = $_SESSION['user']['id'];
            $resultado = $this->model->crearAsignacionesMultiples($offeringId, $professorId, $modalidades, $userId, $groupId);

            AuditService::log($userId, 'ProfesorModalidad', 'CREAR_ASIGNACION', "Asignó modalidades [" . implode(',', $resultado['creadas']) . "] (oferta {$offeringId}, profesor {$professorId})", $offeringId);

            if (!empty($resultado['omitidas']) && empty($resultado['creadas'])) {
                header('Location: /diplomatic/public/academic/profesor-modalidad?error=duplicado');
                exit;
            }
            if (!empty($resultado['omitidas'])) {
                header('Location: /diplomatic/public/academic/profesor-modalidad?created=1&parcial=' . implode(',', $resultado['omitidas']));
                exit;
            }

            header('Location: /diplomatic/public/academic/profesor-modalidad?created=1');
            exit;
        } catch (Throwable $e) {
            header('Location: /diplomatic/public/academic/profesor-modalidad?error=db');
            exit;
        }
    }

    public function update(): void
    {
        try {
            $id          = (int) ($_POST['id'] ?? 0);
            $offeringId  = (int) ($_POST['offering_id'] ?? 0);
            $professorId = (int) ($_POST['professor_id'] ?? 0);
            $groupId     = !empty($_POST['offering_group_id']) ? (int) $_POST['offering_group_id'] : null;
            $modalidadArr = $_POST['modalidad'] ?? [];
            if (!is_array($modalidadArr)) $modalidadArr = [$modalidadArr];
            $modalidad = $modalidadArr[0] ?? '';

            $tiposValidos = ['TEORICA', 'PRACTICA', 'VIRTUAL'];
            if (!$id || !$offeringId || !$professorId || !in_array($modalidad, $tiposValidos, true)) {
                header('Location: /diplomatic/public/academic/profesor-modalidad?error=incompleto');
                exit;
            }
            if (!$this->model->profesorTieneOferta($professorId, $offeringId)) {
                header('Location: /diplomatic/public/academic/profesor-modalidad?error=sinvinculo');
                exit;
            }
            if ($modalidad === 'TEORICA' && !$groupId) {
                header('Location: /diplomatic/public/academic/profesor-modalidad?error=falta_grupo');
                exit;
            }
            if ($this->model->esOfertaOnline($offeringId) && $modalidad !== 'VIRTUAL') {
                header('Location: /diplomatic/public/academic/profesor-modalidad?error=oferta_online');
                exit;
            }
            if ($this->model->asignacionExistsExcluding($offeringId, $modalidad, $id, $groupId)) {
                header('Location: /diplomatic/public/academic/profesor-modalidad?error=duplicado');
                exit;
            }

            $userId = $_SESSION['user']['id'];
            $this->model->actualizarAsignacion($id, $offeringId, $professorId, $modalidad, $groupId);

            AuditService::log($userId, 'ProfesorModalidad', 'EDITAR_ASIGNACION', "Editó asignación ID {$id}", $id);

            header('Location: /diplomatic/public/academic/profesor-modalidad?updated=1');
            exit;
        } catch (Throwable $e) {
            header('Location: /diplomatic/public/academic/profesor-modalidad?error=db');
            exit;
        }
    }

    public function delete(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id) {
            $userId = $_SESSION['user']['id'];
            $this->model->eliminarAsignacion($id);
            AuditService::log($userId, 'ProfesorModalidad', 'ELIMINAR_ASIGNACION', "Eliminó asignación de modalidad ID {$id}", $id);
        }
        header('Location: /diplomatic/public/academic/profesor-modalidad?deleted=1');
        exit;
    }
}