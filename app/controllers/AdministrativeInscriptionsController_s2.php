<?php
/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA
 * ARCHIVO: app/Controllers/AdministrativeInscriptionsController_s2.php
 * PROPÓSITO: API interna para persistencia de borradores.
 * VERSIÓN: 2.0.0
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AdministrativeInscriptionsModel_s2;

final class AdministrativeInscriptionsController_s2 extends Controller
{
    private AdministrativeInscriptionsModel_s2 $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->model = new AdministrativeInscriptionsModel_s2();
    }

    public function saveDraft(): void
    {
        try {
            $adminId = (int)$_SESSION['user']['id'];
            $offeringId = (int)($_POST['offering_id'] ?? 0);
            
            $draftData = [
                'user_id'              => $_POST['user_id'] ?? null,
                'display_name'         => $_POST['display_name_hidden'] ?? '',
                'document_id'          => $_POST['document_id_hidden'] ?? '', 
                'undergraduate_degree' => $_POST['undergraduate_degree'] ?? '',
                'provenance'           => $_POST['provenance'] ?? '',
                'payment_final_method' => $_POST['payment_final_method'] ?? 'CASH',
                'current_step'         => (int)($_POST['current_step'] ?? 1),
                'avatar'               => $_POST['avatar_hidden'] ?? 'default.png'
            ];

            $this->model->saveDraft($adminId, $offeringId, $draftData);
            $this->sendJson(['ok' => true]);
        } catch (\Exception $e) {
            $this->sendJson(['ok' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    private function sendJson(array $data, int $code = 200): void
    {
        while (ob_get_level()) ob_end_clean(); 
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
}