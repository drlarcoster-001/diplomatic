<?php
/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA
 * ARCHIVO: app/Controllers/AdministrativeInscriptionsController_s5.php
 * PROPÓSITO: API de procesamiento con Arquitectura de Metadata Maestro.
 * VERSIÓN: 3.2.2 - FIX: Inyección de ID de usuario en auditoría y redondeo financiero a 2 decimales en metadatos de pago.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AdministrativeInscriptionsModel;

final class AdministrativeInscriptionsController_s5 extends Controller
{
    private AdministrativeInscriptionsModel $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->model = new AdministrativeInscriptionsModel();
    }

    public function store(): void
    {
        try {
            $userId     = (int)($_POST['user_id'] ?? 0);
            $offeringId = (int)($_POST['offering_id'] ?? 0);
            $adminId    = (int)($_SESSION['user']['id'] ?? 0);
            
            $rawMetadata = json_decode($_POST['payment_metadata'] ?? '{}', true);
            
            $metaMethod = trim((string)($rawMetadata['metodo'] ?? ''));
            $postMethod = trim((string)($_POST['payment_method_type'] ?? ''));
            
            $payMethod = strtoupper($metaMethod ?: ($postMethod ?: 'CASH'));

            if (!$userId || !$offeringId) {
                throw new \Exception("Faltan identificadores esenciales (Usuario/Oferta).");
            }

            // --- FIX DE RUTAS FÍSICAS (UNIFICACIÓN) ---
            // Eliminamos el prefijo 'public/' porque el sistema ya corre desde esa carpeta.
            $docUploadDir = 'uploads/enrollments/' . $userId . '/';
            $docPhysicalPath = $docUploadDir; // Antes: 'public/' . $docUploadDir
            if (!is_dir($docPhysicalPath)) mkdir($docPhysicalPath, 0755, true);

            $payUploadDir = $docUploadDir . 'payment/';
            $payPhysicalPath = $payUploadDir; // Antes: 'public/' . $payUploadDir
            if (!is_dir($payPhysicalPath)) mkdir($payPhysicalPath, 0755, true);

            // Documentación personal
            $documents = [
                'ID_CARD' => $this->uploadFile('doc_id', $docPhysicalPath, $docUploadDir, $userId),
                'DEGREE'  => $this->uploadFile('doc_degree', $docPhysicalPath, $docUploadDir, $userId),
                'CV'      => $this->uploadFile('doc_cv', $docPhysicalPath, $docUploadDir, $userId)
            ];

            // 3. PROCESAMIENTO DE PAGO
            $paymentData = $this->preparePaymentData($payMethod, $rawMetadata, $payPhysicalPath, $payUploadDir, $userId, $adminId);
            //$paymentData = $this->preparePaymentData($payMethod, $rawMetadata, $payPhysicalPath, $payUploadDir, $userId);

            // 4. LÓGICA DE ESTATUS DINÁMICO
            $enrollmentStatus = ($payMethod === 'CASH') ? 'COMPROMISO' : 'REVISION';

            $enrollData = [
                'user_id'              => $userId,
                'offering_id'          => $offeringId,
                'undergraduate_degree' => $_POST['undergraduate_degree'] ?? 'N/A',
                'provenance'           => $_POST['provenance'] ?? 'N/A',
                'status'               => $enrollmentStatus, 
                'created_by'           => $adminId
            ];

            // 5. EJECUCIÓN EN MODELO
            $enrollId = $this->model->executeEnrollment($enrollData, $documents, $paymentData);
            
            $this->sendJson([
                'success' => true, 
                'enroll_id' => $enrollId, 
                'message' => "Registro completado. Estatus: " . $enrollmentStatus
            ]);

        } catch (\Throwable $e) {
            $this->sendJson(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    //private function preparePaymentData(string $method, array $rawMetadata, string $physicalPath, string $dbPath, int $userId): array
    private function preparePaymentData(string $method, array $rawMetadata, string $physicalPath, string $dbPath, int $userId, int $adminId): array
    {
        $clean = function($val) {
            if (is_null($val) || $val === '') return 0.0;
            $val = str_replace(['$', ' '], '', (string)$val);
            if (strpos($val, '.') !== false && strpos($val, ',') !== false) {
                $val = str_replace('.', '', $val);
                $val = str_replace(',', '.', $val);
            } else if (strpos($val, ',') !== false) {
                $val = str_replace(',', '.', $val);
            }
            return (float)$val;
        };

        $finalAmountUsd = $clean($rawMetadata['monto_sistema_usd'] ?? 0);
        $montoNativo    = $clean($rawMetadata['detalles_transaccion']['monto_nativo'] ?? 0);
        $monedaNativa   = $rawMetadata['detalles_transaccion']['moneda_nativa'] ?? 'USD';
        $finalReference = $rawMetadata['detalles_transaccion']['referencia'] ?? 'N/A';
        $tasaCambio     = $clean($rawMetadata['tasa_cambio'] ?? 1);

        $masterJson = [
            'metodo'            => $method,
            'monto_sistema_usd' => $finalAmountUsd,
            'tasa_cambio'       => $tasaCambio,
            'detalles_origen'   => [
                'identificador'      => $rawMetadata['detalles_origen']['identificador'] ?? 'N/A',
                'cuenta_correo_telf' => $rawMetadata['detalles_origen']['cuenta_correo_telf'] ?? 'N/A',
                'nombre_titular'     => $rawMetadata['detalles_origen']['nombre_titular'] ?? 'N/A',
                'banco_emisor'       => $rawMetadata['detalles_origen']['banco_emisor'] ?? 'N/A'
            ],
            'detalles_transaccion' => [
                'referencia'        => $finalReference,
                'fecha_comprobante' => $rawMetadata['detalles_transaccion']['fecha_comprobante'] ?? date('Y-m-d'),
                'monto_nativo'      => $montoNativo,
                'moneda_nativa'     => $monedaNativa
            ],
            'auditoria' => [
                'fecha_registro' => date('Y-m-d H:i:s'),
                'agente'         => $adminId // <-- FIX: Inyección del ID de la sesión
            ]
        ];

        return [
            'method'     => $method,
            'currency'   => $monedaNativa,
            'amount'     => ($method === 'CASH') ? $finalAmountUsd : $montoNativo,
            'reference'  => $finalReference,
            'metadata'   => json_encode($masterJson), 
            'screenshot' => ($method !== 'CASH') ? $this->uploadFile('pay_screenshot', $physicalPath, $dbPath, $userId) : null
        ];
    }

    private function uploadFile(string $inputName, string $physicalPath, string $dbPath, int $userId): ?string 
    {




        if (!isset($_FILES[$inputName]) || $_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) return null;
        $ext = pathinfo($_FILES[$inputName]['name'], PATHINFO_EXTENSION);
        
        // Unificación de nombre de archivo con los otros controladores
        $fileName = 'DOC_' . $userId . '_' . $inputName . '_' . time() . '.' . $ext;
        
        if (move_uploaded_file($_FILES[$inputName]['tmp_name'], $physicalPath . $fileName)) {
            // Retorna la ruta limpia para la base de datos
            return $dbPath . $fileName; 
        }
        return null;
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