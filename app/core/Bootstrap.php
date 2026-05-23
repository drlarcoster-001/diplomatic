<?php
/**
 * MÓDULO: NÚCLEO / RUTAS
 * ARCHIVO: app/config/Bootstrap.php
 * PROPÓSITO: Enrutador principal de la aplicación que mapea URLs a Controladores y Métodos.
 * VERSIÓN: 1.7.0 - Fix de rutas configuradas con soporte dinámico para la subcarpeta /diplomatic/public/ y adición del módulo de Rechazos.
 */


declare(strict_types=1);

namespace App\Core;

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\UsersController;
use App\Controllers\SettingsController;
use App\Controllers\SettingsWhatsappController;
use App\Controllers\SettingsCompanyController;
use App\Controllers\SettingsEventsController;
use App\Controllers\MetodosController;

use App\Controllers\ProfileController;
use App\Controllers\RegisterController;
use App\Controllers\SettingsEmailController;
use App\Controllers\UserSecurityController; 
use App\Controllers\AcademicController;
use App\Controllers\DiplomadosController;
use App\Controllers\CohortesController;
use App\Controllers\CohortesConfigController; 
use App\Controllers\GruposController;
use App\Controllers\ProfesoresController;
use App\Controllers\CampusesController;
use App\Controllers\OfertaAcademicaController;
use App\Controllers\SettingsWordpressController;
use App\Controllers\SettingsSecurityController;
use App\Controllers\AdministrativeController;
use App\Controllers\DocumentVerificationController;



// CONTROLADORES DE INSCRIPCIONES ADMINISTRATIVAS
use App\Controllers\AdministrativeInscriptionsController;
use App\Controllers\AdministrativeInscriptionsController_s1;
use App\Controllers\AdministrativeInscriptionsController_s5;
use App\Controllers\AdministrativeInscriptionsController_s6; 
// CONTROLADOR DE ESTUDIANTES ACADEMICOS
use App\Controllers\AdministrativeStudentsController;
// NUEVO: CONTROLADOR DE MATRÍCULAS Y ACTAS ACADÉMICAS
use App\Controllers\AdministrativeMatriculationsController;

// CONTROLADOR DE CONSTANCIAS (ADMINISTRATIVO)
use App\Controllers\AdministrativeCertificatesController;
// CONTROLADOR DE ANULACIONES Y REVERSIONES
use App\Controllers\AdministrativeAnnulmentsController;
// CONTROLADOR DE REACTIVACIONES
use App\Controllers\AdministrativeReactivationsController;
// CONTROLADOR DE DOCUMENTOS RECHAZADOS
use App\Controllers\AdministrativeRejectedController;
// CONTROLADOR DE SUSPENSIONES POR MOROSIDAD
use App\Controllers\AdministrativeSuspensionController;



// CONTROLADORES FINANCIEROS
use App\Controllers\FinancialController;
use App\Controllers\FinancialCashPagomovilController;
use App\Controllers\FinancialCashBinanceController;
use App\Controllers\FinancialCashEfectivoController;
use App\Controllers\FinancialPaymentValidationsController;
use App\Controllers\FinancialPaymentValidationsPagomovilController;
use App\Controllers\FinancialPaymentRegistrationController;
use App\Controllers\FinancialPaymentValidationsNotificationsController;
use App\Controllers\FinancialPaymentValidationsZelleController;
use App\Controllers\FinancialPaymentValidationsBinanceController;
use App\Controllers\FinancialPaymentValidationsEfectivoController;
use App\Controllers\FinancialStudentStatementController;
use App\Controllers\FinancialReverseOperationsController;
use App\Controllers\FinancialPaymentRejectionController;
use App\Controllers\FinancialBankStatementsController;



// CONTROLADORES DEL PANEL ESTUDIANTIL
use App\Controllers\StudentsController;
use App\Controllers\StudentsInscriptionsController;
use App\Controllers\StudentsInscriptionsController_s1;
use App\Controllers\StudentsInscriptionsController_s6; 
// CONTROLADOR DE PAGOS ESTUDIANTILES
use App\Controllers\StudentsPaymentRegistrationController;
// CONTROLADOR DE ESTADO DE CUENTA ESTUDIANTILES
use App\Controllers\StudentStatementController;
// CONTROLADOR DE CERTIFICADOS ESTUDIANTILES
use App\Controllers\StudentsCertificatesController;
// CONTROLADOR DE GESTION DE DOCUMENTOS ESTUDIANTILES
use App\Controllers\StudentsDocumentManagementController;




// CONTROLADORES DE TASAS DE CAMBIO (CONTROLADOR ESPECIALIZADO)
use App\Controllers\FinancialExchangeRatesController;
use App\Controllers\FinancialCashZelleController;

// CONTROLADORES DE MODULO OPERACIONAL WORDPRESS
use App\Controllers\OperationalController;
use App\Controllers\OperationalProfessorsController;
use App\Controllers\OperationalNewsController; 

// CONTROLADORES DE MODULO GERENCIAL
use App\Controllers\ManagerialController;
use App\Controllers\ManagerialPaymentsReportController;
use App\Controllers\ManagerialPendingPaymentsController;
use App\Controllers\ManagerialAcademicControlController;
use App\Controllers\ManagerialBankReconciliationController;
use App\Controllers\ManagerialMovementsReportController;


use PDO;

final class Bootstrap
{
    public function run(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $db = (new \App\Core\Database())->getConnection();

        /**
         * 1. REPARACIÓN DE SESIÓN (COOKIES / RECORDARME)
         */
        if (empty($_SESSION['user']['id']) && isset($_COOKIE['remember_me'])) {
            try {
                $hash = hash('sha256', $_COOKIE['remember_me']);
                $sql = "SELECT u.* FROM tbl_users u 
                        JOIN tbl_user_remember_tokens t ON u.id = t.user_id 
                        WHERE t.token_hash = ? AND t.expires_at > NOW() AND u.status = 'ACTIVE' LIMIT 1";
                
                $stmt = $db->prepare($sql);
                $stmt->execute([$hash]);
                $u = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($u) {
                    $this->setSession($u);
                }
            } catch (\Throwable $e) {}
        }

        /**
         * 2. PARCHE DE EMERGENCIA: document_id
         */
        if (!empty($_SESSION['user']['id']) && empty($_SESSION['user']['document_id'])) {
            $stmt = $db->prepare("SELECT document_id FROM tbl_users WHERE id = ? LIMIT 1");
            $stmt->execute([$_SESSION['user']['id']]);
            $doc = $stmt->fetchColumn();
            if ($doc) $_SESSION['user']['document_id'] = $doc;
        }

        $router = new Router();

        // --- RUTAS DE AUTENTICACIÓN Y PERFIL ---
        $router->get('/', [AuthController::class, 'showLogin']);
        $router->post('/login', [AuthController::class, 'doLogin']);
        $router->get('/logout', [AuthController::class, 'logout']);
        $router->get('/dashboard', [DashboardController::class, 'index']);
        
        $router->get('/profile', [ProfileController::class, 'index']);
        $router->post('/profile/update', [ProfileController::class, 'update']);
        $router->get('/profile/security', [ProfileController::class, 'security']);
        $router->post('/profile/change-password', [ProfileController::class, 'changePassword']);

        // --- CONFIGURACIONES ---
        $router->get('/settings', [SettingsController::class, 'index']);
        $router->get('/settings/correo', [SettingsEmailController::class, 'index']);
        $router->post('/settings/save-correo', [SettingsEmailController::class, 'save']);
        $router->post('/settings/test-correo', [SettingsEmailController::class, 'test']);
        $router->get('/settings/empresa', [SettingsCompanyController::class, 'index']);
        $router->post('/settings/empresa/save', [SettingsCompanyController::class, 'save']);
        $router->get('/settings/whatsapp', [SettingsWhatsappController::class, 'index']);
        $router->post('/settings/whatsapp/save-template', [SettingsWhatsappController::class, 'saveTemplate']);
        $router->post('/settings/whatsapp/log', [SettingsWhatsappController::class, 'logSend']);
        $router->get('/settings/eventos', [SettingsEventsController::class, 'index']);
        $router->get('/settings/eventos/filter', [SettingsEventsController::class, 'filter']);

        // --- SECCIÓN MODIFICADA: WP BRIDGE ---
        $router->get('/settings/wordpress', [SettingsWordpressController::class, 'index']);
        $router->post('/settings/wordpress/save', [SettingsWordpressController::class, 'save']); // <--- ANTES: saveConfig
        $router->post('/settings/wordpress/test', [SettingsWordpressController::class, 'test']); // <--- ANTES: testConnection
        $router->post('/settings/wordpress/sync-prof', [SettingsWordpressController::class, 'syncProfessor']);
        $router->post('/settings/wordpress/unsync-prof', [SettingsWordpressController::class, 'unsyncProfessor']);
        $router->get('/settings/wordpress/test-push', [SettingsWordpressController::class, 'testPush']);
        $router->post('/settings/wordpress/delete-prof', [SettingsWordpressController::class, 'deleteProfessorPost']);

        // --- MANTENIMIENTO DE MÉTODOS DE PAGO ---
        $router->get('/settings/paymentMethods', [MetodosController::class, 'index']);
        $router->post('/settings/paymentMethods/save', [MetodosController::class, 'save']);
        $router->get('/settings/paymentMethods/getPMData', [MetodosController::class, 'getPMData']);
        




        // --- GESTIÓN ACADÉMICA (DIPLOMADOS) ---
        $router->get('/academic', [AcademicController::class, 'index']);
        $router->get('/academic/diplomados', [DiplomadosController::class, 'index']);
        $router->get('/academic/diplomados/create', [DiplomadosController::class, 'create']);
        $router->post('/academic/diplomados/save', [DiplomadosController::class, 'save']);
        $router->get('/academic/diplomados/edit', [DiplomadosController::class, 'edit']); 
        $router->post('/academic/diplomados/update', [DiplomadosController::class, 'update']);
        $router->post('/academic/diplomados/delete', [DiplomadosController::class, 'delete']);
        $router->get('/academic/diplomados/getDetails', [DiplomadosController::class, 'getDetails']);
        
        // NUEVA RUTA: Exportación PDF
        $router->get('/academic/diplomados/exportPdf', [DiplomadosController::class, 'exportPdf']);

        // --- GESTIÓN ACADÉMICA (COHORTES) ---
        $router->get('/academic/cohortes', [CohortesController::class, 'index']);
        $router->get('/academic/cohortes/create', [CohortesController::class, 'create']);
        $router->post('/academic/cohortes/save', [CohortesController::class, 'save']);
        $router->post('/academic/cohortes/update', [CohortesController::class, 'update']);
        $router->post('/academic/cohortes/delete', [CohortesController::class, 'delete']);
        $router->get('/academic/cohortes/getDetails', [CohortesController::class, 'getDetails']);
        $router->get('/academic/cohortes/changeStatus', [CohortesController::class, 'changeStatus']);
        $router->get('/academic/cohortes/logAccess', [CohortesController::class, 'logAccess']); 
        
        $router->get('/academic/cohortes-config', [CohortesConfigController::class, 'index']);
        $router->get('/academic/cohortes-config/getDetails', [CohortesConfigController::class, 'getDetails']);
        $router->post('/academic/cohortes-config/updateStatus', [CohortesConfigController::class, 'updateStatus']);
        $router->post('/academic/cohortes-config/hardDelete', [CohortesConfigController::class, 'hardDelete']);

        // --- GESTIÓN ACADÉMICA (GRUPOS) ---
        $router->get('/academic/grupos', [GruposController::class, 'index']);
        $router->post('/academic/grupos/save', [GruposController::class, 'save']);
        $router->post('/academic/grupos/update', [GruposController::class, 'update']);
        $router->post('/academic/grupos/delete', [GruposController::class, 'delete']);
        $router->get('/academic/grupos/getDetails', [GruposController::class, 'getDetails']);
        $router->get('/academic/grupos/logAccess', [GruposController::class, 'logAccess']);

        // --- GESTIÓN ACADÉMICA (PROFESORES) ---
        $router->get('/academic/profesores', [ProfesoresController::class, 'index']);
        $router->get('/academic/profesores/create', [ProfesoresController::class, 'create']);
        $router->post('/academic/profesores/save', [ProfesoresController::class, 'save']);
        $router->get('/academic/profesores/edit', [ProfesoresController::class, 'edit']);
        $router->post('/academic/profesores/updateBase', [ProfesoresController::class, 'updateBase']);
        $router->post('/academic/profesores/delete', [ProfesoresController::class, 'delete']);
        $router->get('/academic/profesores/getDetails', [ProfesoresController::class, 'getDetails']);
        $router->get('/academic/profesores/logAccess', [ProfesoresController::class, 'logAccess']);
        $router->post('/academic/profesores/saveFormation', [ProfesoresController::class, 'saveFormation']);
        $router->post('/academic/profesores/deleteFormation', [ProfesoresController::class, 'deleteFormation']);
        $router->post('/academic/profesores/saveWork', [ProfesoresController::class, 'saveWork']);
        $router->post('/academic/profesores/deleteWork', [ProfesoresController::class, 'deleteWork']);
        $router->post('/academic/profesores/saveSpecialty', [ProfesoresController::class, 'saveSpecialty']);
        $router->post('/academic/profesores/deleteSpecialty', [ProfesoresController::class, 'deleteSpecialty']);
        $router->post('/academic/profesores/uploadDocument', [ProfesoresController::class, 'uploadDocument']);
        $router->post('/academic/profesores/deleteDocument', [ProfesoresController::class, 'deleteDocument']);
        $router->post('/academic/profesores/uploadPhoto', [ProfesoresController::class, 'uploadPhoto']);

        $router->get('/academic/campuses', [CampusesController::class, 'index']);
        $router->post('/academic/campuses/save', [CampusesController::class, 'save']);
        $router->post('/academic/campuses/update', [CampusesController::class, 'update']);
        $router->post('/academic/campuses/delete', [CampusesController::class, 'delete']);
        $router->get('/academic/campuses/getDetails', [CampusesController::class, 'getDetails']);

        // --- GESTIÓN ACADÉMICA (OFERTA ACADÉMICA) ---
        $router->get('/academic/oferta', [OfertaAcademicaController::class, 'index']);
        $router->get('/academic/oferta/create', [OfertaAcademicaController::class, 'create']);
        $router->post('/academic/oferta/save', [OfertaAcademicaController::class, 'save']);
        $router->get('/academic/oferta/edit', [OfertaAcademicaController::class, 'edit']);
        $router->post('/academic/oferta/update', [OfertaAcademicaController::class, 'update']);
        $router->post('/academic/oferta/delete', [OfertaAcademicaController::class, 'delete']);
        $router->get('/academic/oferta/logAccess', [OfertaAcademicaController::class, 'logAccess']);
        $router->post('/academic/oferta/executeOpen', [OfertaAcademicaController::class, 'executeOpen']); 
        $router->post('/academic/oferta/logSummaryPopup', [OfertaAcademicaController::class, 'logSummaryPopup']);
        $router->post('/academic/oferta/logLockPopup', [OfertaAcademicaController::class, 'logLockPopup']);
        $router->post('/academic/oferta/verifyAdmin', [OfertaAcademicaController::class, 'verifyAdmin']);
        $router->post('/academic/oferta/changeStatusAdmin', [OfertaAcademicaController::class, 'changeStatusAdmin']); 
        $router->post('/academic/oferta/changeStatus', [OfertaAcademicaController::class, 'changeStatus']);
        $router->post('/academic/oferta/toggleActive', [OfertaAcademicaController::class, 'toggleActive']);
        $router->get('/academic/oferta/getCohortCampuses', [OfertaAcademicaController::class, 'getCohortCampuses']);

        // --- PANEL ADMINISTRATIVO GENERAL ---
        $router->get('/administrative', [AdministrativeController::class, 'index']);
        $router->get('/administrative/enrollment', [AdministrativeController::class, 'enrollment']);
        $router->get('/administrative/students', [AdministrativeController::class, 'students']);
        $router->get('/administrative/certificates', [AdministrativeController::class, 'certificates']);
        $router->get('/administrative/document-verification', [AdministrativeController::class, 'documentVerification']);
        // =========================================================================
        // --- DIRECTORIO DE ESTUDIANTES (NUEVO MÓDULO) ---
        // =========================================================================
        $router->get('/administrative/students/directory', [AdministrativeStudentsController::class, 'index']);
        $router->get('/administrative/students/list', [AdministrativeStudentsController::class, 'list']);
        $router->post('/administrative/students/updateStatus', [AdministrativeStudentsController::class, 'updateStatus']);
        // =========================================================================
        // --- GESTIÓN DE MATRÍCULAS Y ACTAS DE NOTAS (NUEVO) ---
        // =========================================================================
        $router->get('/administrative/matriculations', [AdministrativeMatriculationsController::class, 'index']);
        $router->get('/administrative/matriculations/manage', [AdministrativeMatriculationsController::class, 'manage']);

        // Procesamiento de Calificaciones (Lógica Nota >= 15)
        $router->post('/administrative/matriculations/procesarNotas', [AdministrativeMatriculationsController::class, 'procesarNotas']);

        // Acciones Especiales (Retirar / Congelar con Sincronización a tbl_students)
        $router->post('/administrative/matriculations/cambiarEstado', [AdministrativeMatriculationsController::class, 'cambiarEstado']);

        // Exportación de Listado PDF (Nombre | Cédula | Firma)
        $router->get('/administrative/matriculations/imprimirAsistencia', [AdministrativeMatriculationsController::class, 'imprimirAsistencia']);
        $router->get('/administrative/matriculations/imprimirListado', [AdministrativeMatriculationsController::class, 'imprimirListado']);
        $router->get('/administrative/matriculations/imprimirActa', [AdministrativeMatriculationsController::class, 'imprimirActa']);



        // --- MÓDULO ESPECIALIZADO DE INSCRIPCIONES ---
        $router->get('/administrative/inscriptions', [AdministrativeInscriptionsController::class, 'index']);
        $router->get('/administrative/inscriptions/create', [AdministrativeInscriptionsController::class, 'create']);
        $router->get('/administrative/inscriptions/search', [AdministrativeInscriptionsController_s1::class, 'search']);
        $router->get('/administrative/inscriptions/checkExisting', [AdministrativeInscriptionsController_s1::class, 'checkExisting']);
        $router->post('/administrative/inscriptions/reject', [AdministrativeInscriptionsController::class, 'reject']);
        $router->post('/administrative/inscriptions/store', [AdministrativeInscriptionsController_s5::class, 'store']);
        $router->post('/administrative/inscriptions/send-email', [AdministrativeInscriptionsController_s6::class, 'sendEmail']);
        
        $router->get('/administrative/inscriptions/getPaymentPlan', [AdministrativeInscriptionsController::class, 'getPaymentPlan']);

        
        // VERIFICACIÓN DE DOCUMENTOS (SECRETARÍA ACADÉMICA)
        // 1. La vista principal (La que ya tienes)
        $router->get('/administrative/document-verification', [DocumentVerificationController::class, 'index']);

        // 2. La ruta para cargar los datos de la tabla (GET)
        $router->get('/administrative/document-verification/getPending', [DocumentVerificationController::class, 'getPendingVerifications']);

        // 3. La ruta para el botón de Aprobar (POST)
        $router->post('/administrative/document-verification/approve', [DocumentVerificationController::class, 'approveDocuments']);

        // 4. Las rutas para Rechazar y Observar (POST)
        $router->post('/administrative/document-verification/reject', [DocumentVerificationController::class, 'rejectDocuments']);
        $router->post('/administrative/document-verification/observe', [DocumentVerificationController::class, 'observeDocuments']);
        // 5. Imprimir Listado
        $router->get('/administrative/document-verification/imprimir', [DocumentVerificationController::class, 'ImprimirListadoPDF']);

        // --- GESTIÓN DE CONSTANCIAS (ADMINISTRATIVO) ---
        $router->get('/administrative/certificates', [AdministrativeCertificatesController::class, 'index']);
        $router->get('/administrative/certificates/search', [AdministrativeCertificatesController::class, 'search']);
        $router->get('/administrative/certificates/getPrograms', [AdministrativeCertificatesController::class, 'getPrograms']);
        $router->get('/administrative/certificates/generate', [AdministrativeCertificatesController::class, 'generate']);
        $router->post('/administrative/certificates/sendEmail', [AdministrativeCertificatesController::class, 'sendEmail']);
        $router->get('/administrative/certificates/finalizeAndDownload', [AdministrativeCertificatesController::class, 'finalizeAndDownload']);
        $router->get('/administrative/certificates/getStudentPrograms', [AdministrativeCertificatesController::class, 'getStudentPrograms']);
        $router->get('/verify', [DocumentVerificationController::class, 'publicVerify']);


        // --- MÓDULO DE CANCELACIÓN DE INSCRIPCIONES (EXCEPCIONES) ---
        // Busca la sección de anulaciones y añade la línea de getDetails:
        $router->get('/administrative/annulments', [AdministrativeAnnulmentsController::class, 'index']);
        $router->get('/administrative/annulments/list', [AdministrativeAnnulmentsController::class, 'list']);
        $router->get('/administrative/annulments/getDetails', [AdministrativeAnnulmentsController::class, 'getDetails']);
        $router->post('/administrative/annulments/process', [AdministrativeAnnulmentsController::class, 'process']);

        // RUTAS DE REACTIVACIÓN
        $router->get('/administrative/reactivations', [AdministrativeReactivationsController::class, 'index']);
        $router->get('/administrative/reactivations/manage', [AdministrativeReactivationsController::class, 'manage']); // <--- ESTA ES LA QUE FALTA
        $router->get('/administrative/reactivations/list', [AdministrativeReactivationsController::class, 'list']);
        $router->post('/administrative/reactivations/processMassive', [AdministrativeReactivationsController::class, 'processMassive']);
         // RUTAS DE DOCUMENTOS RECHAZADOS
        $router->get('/administrative/rejected', [AdministrativeRejectedController::class, 'index']);
        $router->post('/administrative/rejected/changeStatus', [AdministrativeRejectedController::class, 'changeStatus']);
        

        // --- GESTIÓN DE SUSPENSIONES (NUEVO MÓDULO) ---
        $router->get('/administrative/suspensions', [AdministrativeSuspensionController::class, 'index']);
        $router->get('/administrative/suspensions/manage', [AdministrativeSuspensionController::class, 'manage']);
        $router->get('/administrative/suspensions/getStudentsJson', [AdministrativeSuspensionController::class, 'getStudentsJson']);
        $router->post('/administrative/suspensions/toggleStatus', [AdministrativeSuspensionController::class, 'toggleStatus']);
        $router->post('/administrative/suspensions/sendEmail', [AdministrativeSuspensionController::class, 'sendEmail']);





        // --- PANEL FINANCIERO (MAESTRO) ---
        $router->get('/financial', [FinancialController::class, 'index']);
        $router->get('/financial/cash-operations', [FinancialController::class, 'cashOperations']);
        $router->get('/financial/management-monitor', [FinancialController::class, 'managementMonitor']);



        // --- SUB-MÓDULOS DE CAJA (CONCILIACIÓN) --- PAGO MOVIL
        $router->get('/financial/cash-operations/pagomovil', [FinancialCashPagomovilController::class, 'index']);
        $router->get('/financial/cash-operations/pagomovil/getPendingPayments', [FinancialCashPagomovilController::class, 'getPendingPayments']);
        $router->post('/financial/cash-operations/pagomovil/uploadFile', [FinancialCashPagomovilController::class, 'uploadFile']);
        
        // ¡NUEVAS RUTAS PARA APROBACIÓN DE PAGO MÓVIL!
        $router->post('/financial/cash-operations/pagomovil/validatePayment', [FinancialCashPagomovilController::class, 'validatePayment']);
        $router->post('/financial/cash-operations/pagomovil/approveMassivePayments', [FinancialCashPagomovilController::class, 'approveMassivePayments']);
        // ESTA ES LA QUE TIENES QUE AGREGAR PARA QUITAR EL ERROR:
        $router->post('/financial/cash-operations/pagomovil/rejectPayment', [FinancialCashPagomovilController::class, 'rejectPayment']);
        

        
        
        // RUTAS PARA APROBACION DE ZELLE (USD)
        // SUB-MÓDULO: ZELLE (USD)
        $router->get('/financial/cash-operations/zelle', [FinancialCashZelleController::class, 'index']);
        $router->get('/financial/cash-operations/zelle/getPendingPayments', [FinancialCashZelleController::class, 'getPendingPayments']);
        $router->post('/financial/cash-operations/zelle/validatePayment', [FinancialCashZelleController::class, 'validatePayment']);
        $router->post('/financial/cash-operations/zelle/rejectPayment', [FinancialCashZelleController::class, 'rejectPayment']);

        // --- RUTAS DE GESTIÓN FINANCIERA: BINANCE PAY ---
        $router->get('/financial/cash-operations/binance', [FinancialCashBinanceController::class, 'index']);
        $router->get('/financial/cash-operations/binance/getPendingPayments', [FinancialCashBinanceController::class, 'getPendingPayments']);
        $router->post('/financial/cash-operations/binance/validatePayment', [FinancialCashBinanceController::class, 'validatePayment']);
        $router->post('/financial/cash-operations/binance/rejectPayment', [FinancialCashBinanceController::class, 'rejectPayment']);

        // --- GESTIÓN DE EFECTIVO (VENTANILLA) ---
        $router->get('/financial/cash-operations/efectivo', [FinancialCashEfectivoController::class, 'index']);
        $router->get('/financial/cash-operations/efectivo/getPendingPayments', [FinancialCashEfectivoController::class, 'getPendingPayments']);
        $router->post('/financial/cash-operations/efectivo/validatePayment', [FinancialCashEfectivoController::class, 'validatePayment']);
        $router->post('/financial/cash-operations/efectivo/rejectPayment', [FinancialCashEfectivoController::class, 'rejectPayment']);

        


        // --- TASAS DE CAMBIO (SYNC: exchange_rates) ---
        $router->get('/financial/exchange_rates', [FinancialController::class, 'exchange_rates']);
        $router->post('/financial/store_rate', [FinancialController::class, 'store_rate']);
        $router->get('/financial/exchange_rates', [FinancialExchangeRatesController::class, 'index']);
        $router->get('/financial/exchange_rates/fetchBCV', [FinancialExchangeRatesController::class, 'fetchBCV']);
        $router->post('/financial/exchange_rates/store', [FinancialExchangeRatesController::class, 'store']);
        $router->post('/financial/exchange_rates/delete', [FinancialExchangeRatesController::class, 'delete']);
        $router->get('/financial/exchange_rates/getRateByDate', [FinancialExchangeRatesController::class, 'getRateByDate']);


        // --- VALIDACIÓN DE PAGOS REGULARES (CUOTAS) ---
        $router->get('/financial/payment_validations', [FinancialPaymentValidationsController::class, 'index']);
        $router->get('/financial/payment_validations/getPendingCounts', [FinancialPaymentValidationsController::class, 'getPendingCounts']);



        // --- ENDPOINTS OPERATIVOS (PAGO MÓVIL) ---
        // Obtener lista de pagos
        $router->get('/financial/payment_validations/pagomovil/getPendingPayments', [FinancialPaymentValidationsPagomovilController::class, 'getPendingPayments']);

        // ¡ESTA ES LA QUE FALTABA! -> Subir el archivo Excel
        $router->post('/financial/payment_validations/pagomovil/uploadFile', [FinancialPaymentValidationsPagomovilController::class, 'uploadFile']);

        // Aprobar individual
        $router->post('/financial/payment_validations/pagomovil/validatePayment', [FinancialPaymentValidationsPagomovilController::class, 'validatePayment']);

        // Rechazar individual
        $router->post('/financial/payment_validations/pagomovil/rejectPayment', [FinancialPaymentValidationsPagomovilController::class, 'rejectPayment']);

        // ¡ESTA TAMBIÉN FALTABA! -> Aprobación Masiva
        $router->post('/financial/payment_validations/pagomovil/approveMassivePayments', [FinancialPaymentValidationsPagomovilController::class, 'approveMassivePayments']);

        // Vista principal de Pago Móvil
        $router->get('/financial/payment_validations/pagomovil', [FinancialPaymentValidationsController::class, 'pagomovil']);
        $router->post('/financial/payment_validations/pagomovil', [FinancialPaymentValidationsController::class, 'pagomovil']);


        // Validaciones de pago de Zelle
        $router->get('/financial/payment_validations/zelle', [FinancialPaymentValidationsZelleController::class, 'index']);
        $router->get('/financial/payment_validations/zelle/getPendingPayments', [FinancialPaymentValidationsZelleController::class, 'getPendingPayments']);
        $router->post('/financial/payment_validations/zelle/validatePayment', [FinancialPaymentValidationsZelleController::class, 'validatePayment']);
        $router->post('/financial/payment_validations/zelle/rejectPayment', [FinancialPaymentValidationsZelleController::class, 'rejectPayment']);

        // Validacion de pago de Binance
        $router->get('/financial/payment_validations/binance', [FinancialPaymentValidationsBinanceController::class, 'index']);
        $router->get('/financial/payment_validations/binance/getPendingPayments', [FinancialPaymentValidationsBinanceController::class, 'getPendingPayments']);
        $router->post('/financial/payment_validations/binance/validatePayment', [FinancialPaymentValidationsBinanceController::class, 'validatePayment']);
        $router->post('/financial/payment_validations/binance/rejectPayment', [FinancialPaymentValidationsBinanceController::class, 'rejectPayment']);

        // Validacion de pago de Efectivo

        $router->get('/financial/payment_validations/efectivo', [FinancialPaymentValidationsEfectivoController::class, 'index']);
        $router->get('/financial/payment_validations/efectivo/getPendingPayments', [FinancialPaymentValidationsEfectivoController::class, 'getPendingPayments']);
        $router->post('/financial/payment_validations/efectivo/validatePayment', [FinancialPaymentValidationsEfectivoController::class, 'validatePayment']);
        $router->post('/financial/payment_validations/efectivo/rejectPayment', [FinancialPaymentValidationsEfectivoController::class, 'rejectPayment']);




        // --- MÓDULO DE REGISTRO DE PAGOS (Sincronización v5.0.0) ---
        $router->get('/financial/payment_registration', [FinancialPaymentRegistrationController::class, 'index']);

        // S1: Búsqueda reactiva (search -> searchStudents)
        $router->get('/financial/payment_registration/searchStudents', [FinancialPaymentRegistrationController::class, 'searchStudents']);

        // S2: Carga de diplomados (getOfferings -> getOfferingsByUser)
        $router->get('/financial/payment_registration/getOfferingsByUser', [FinancialPaymentRegistrationController::class, 'getOfferingsByUser']);

        // S4: Estado de cuenta y Tasa BCV (Rutas faltantes)
        $router->get('/financial/payment_registration/getAccountStatus', [FinancialPaymentRegistrationController::class, 'getAccountStatus']);
        $router->get('/financial/payment_registration/getLatestExchangeRate', [FinancialPaymentRegistrationController::class, 'getLatestExchangeRate']);

        // S4: Procesamiento de pago
        $router->post('/financial/payment_registration/store', [FinancialPaymentRegistrationController::class, 'store']);

        // ¡NUEVA RUTA S5! Envío de correo de confirmación de pago
        $router->post('/financial/payment_registration/sendPaymentEmail', [FinancialPaymentRegistrationController::class, 'sendPaymentEmail']);
        $router->get('/financial/payment_registration/getStudentIdentity', [FinancialPaymentRegistrationController::class, 'getStudentIdentity']);
        
        // Notificaciones de validaciones de pago
        $router->post('/financial/notifications/sendPaymentApprovedEmail', [FinancialPaymentValidationsNotificationsController::class, 'sendPaymentApprovedEmail']);


        // --- ESTADOS DE CUENTA ---
        $router->get('/financial/student_statement', [FinancialStudentStatementController::class, 'index']);
        $router->get('/financial/student_statement/getStatement', [FinancialStudentStatementController::class, 'getStatement']);
        $router->get('/financial/student_statement/searchStudents', [FinancialStudentStatementController::class, 'searchStudents']);
        $router->get('/financial/student_statement/getPaymentHistory', [FinancialStudentStatementController::class, 'getPaymentHistory']);
        $router->get('/financial/student_statement/exportStatementPdf', [FinancialStudentStatementController::class, 'exportStatementPdf']);
        $router->get('/financial/student_statement/exportPaymentPdf', [FinancialStudentStatementController::class, 'exportPaymentPdf']);

        
        // --- REVERSO DE OPERACIONES FINANCIERAS ---
        $router->get('/financial/reverse_operations', [FinancialReverseOperationsController::class, 'index']);
        $router->post('/financial/reverse_operations/search_inscripciones', [FinancialReverseOperationsController::class, 'search_inscripciones']);
        $router->post('/financial/reverse_operations/search_cuotas', [FinancialReverseOperationsController::class, 'search_cuotas']);
        $router->post('/financial/reverse_operations/reverse_inscripcion', [FinancialReverseOperationsController::class, 'reverse_inscripcion']);
        $router->post('/financial/reverse_operations/reverse_cuota', [FinancialReverseOperationsController::class, 'reverse_cuota']);


        // --- MÓDULO: RECHAZOS DE PAGO ---
        $router->get('/financial/payment_rejections', [FinancialPaymentRejectionController::class, 'index']);

        // AJAX: Inscripciones
        $router->post('/financial/payment_rejections/search_inscripciones', [FinancialPaymentRejectionController::class, 'search_inscripciones']);
        $router->post('/financial/payment_rejections/incorporar_inscripcion', [FinancialPaymentRejectionController::class, 'incorporar_inscripcion']);
        $router->post('/financial/payment_rejections/eliminar_inscripcion', [FinancialPaymentRejectionController::class, 'eliminar_inscripcion']);

        // AJAX: Regulares
        $router->post('/financial/payment_rejections/search_regulares', [FinancialPaymentRejectionController::class, 'search_regulares']);
        $router->post('/financial/payment_rejections/incorporar_regular', [FinancialPaymentRejectionController::class, 'incorporar_regular']);
        $router->post('/financial/payment_rejections/eliminar_regular', [FinancialPaymentRejectionController::class, 'eliminar_regular']);

        // --- PANEL ESTUDIANTIL ---
        $router->get('/students', [StudentsController::class, 'index']);
        $router->get('/students/inscriptions', [StudentsInscriptionsController::class, 'index']);
        $router->get('/students/inscriptions/create', [StudentsInscriptionsController::class, 'create']);
        $router->get('/students/inscriptions/checkExisting', [StudentsInscriptionsController_s1::class, 'checkExisting']);
        $router->get('/students/inscriptions/getPaymentPlan', [StudentsInscriptionsController::class, 'getPaymentPlan']);
        $router->get('/students/inscriptions/getRateByDate', [StudentsInscriptionsController::class, 'getRateByDate']);
        $router->post('/students/inscriptions/store', [StudentsInscriptionsController::class, 'store']);
        $router->post('/students/inscriptions/send-email', [StudentsInscriptionsController_s6::class, 'sendEmail']);

        // --- AUTOGESTIÓN ESTUDIANTIL: REGISTRO DE PAGOS ---
        $router->get('/students/payment_registration', [StudentsPaymentRegistrationController::class, 'index']);
        // S1: Carga automática de los datos del estudiante en sesión (Reemplaza a la barra de búsqueda)
        $router->get('/students/payment_registration/getStudentData', [StudentsPaymentRegistrationController::class, 'getStudentData']);
        // S2: Carga de diplomados (Solo los asociados al estudiante en sesión)
        $router->get('/students/payment_registration/getOfferingsByUser', [StudentsPaymentRegistrationController::class, 'getOfferingsByUser']);
        // S4: Estado de cuenta y Tasa BCV
        $router->get('/students/payment_registration/getAccountStatus', [StudentsPaymentRegistrationController::class, 'getAccountStatus']);
        $router->get('/students/payment_registration/getLatestExchangeRate', [StudentsPaymentRegistrationController::class, 'getLatestExchangeRate']);
        // S4: Procesamiento de pago (Queda en PENDING)
        $router->post('/students/payment_registration/store', [StudentsPaymentRegistrationController::class, 'store']);
        // S5: Envío de correo de confirmación de pago en revisión
        $router->post('/students/payment_registration/sendPaymentEmail', [StudentsPaymentRegistrationController::class, 'sendPaymentEmail']);

        // 1. Vista Principal (Interfaz de usuario)
        $router->get('/students/student_statement', [StudentStatementController::class, 'index']);
        $router->get('/students/student_statement/getMyStatement', [StudentStatementController::class, 'getMyStatement']);
        $router->get('/students/student_statement/getMyPaymentHistory', [StudentStatementController::class, 'getMyPaymentHistory']);
        $router->get('/students/student_statement/exportMyStatementPdf', [StudentStatementController::class, 'exportMyStatementPdf']);
        $router->get('/students/student_statement/exportMyPaymentPdf', [StudentStatementController::class, 'exportMyPaymentPdf']);

        // --- AUTOGESTIÓN ESTUDIANTIL: MIS CERTIFICADOS ---
        $router->get('/students/certificates', [StudentsCertificatesController::class, 'index']);
        $router->get('/students/certificates/getPrograms', [StudentsCertificatesController::class, 'getPrograms']);
        $router->get('/students/certificates/generate', [StudentsCertificatesController::class, 'generate']);
        $router->post('/students/certificates/sendEmail', [StudentsCertificatesController::class, 'sendEmail']);
        $router->get('/students/certificates/finalizeAndDownload', [StudentsCertificatesController::class, 'finalizeAndDownload']); 

        // --- AUTOGESTIÓN ESTUDIANTIL: GESTIÓN DE DOCUMENTOS (CÉDULA, TÍTULO, CV) ---
        $router->get('/students/documents', [StudentsDocumentManagementController::class, 'index']);
        $router->get('/students/documents/index', [StudentsDocumentManagementController::class, 'index']);
        // Ruta para cargar un diplomado específico
        
        $router->get('/students/documents', [StudentsDocumentManagementController::class, 'index']);
        // Endpoint para la carga de archivos (AJAX)
        $router->post('/students/documents/upload', [StudentsDocumentManagementController::class, 'upload']);
        // Endpoint para eliminar documentos (AJAX)
        $router->post('/students/documents/deleteDocument', [StudentsDocumentManagementController::class, 'deleteDocument']);


        // --- MÓDULO: SEGURIDAD (PRE-USERS Y TOKENS) ---
        $router->get('/settings/seguridad', [SettingsSecurityController::class, 'index']);
        $router->get('/settings/seguridad/getPreUsers', [SettingsSecurityController::class, 'getPreUsers']);
        $router->post('/settings/seguridad/deletePreUser', [SettingsSecurityController::class, 'deletePreUser']);
        $router->post('/settings/seguridad/cleanExpiredTokens', [SettingsSecurityController::class, 'cleanExpiredTokens']);

        // --- USUARIOS Y SEGURIDAD ---
        $router->get('/UserSecurity', [UserSecurityController::class, 'index']);
        $router->get('/UserSecurity/getUsers', [UserSecurityController::class, 'getUsers']);
        $router->post('/UserSecurity/updatePassword', [UserSecurityController::class, 'updatePassword']);
        $router->post('/UserSecurity/updateStatus', [UserSecurityController::class, 'updateStatus']);
        $router->post('/UserSecurity/deletePhysical', [UserSecurityController::class, 'deletePhysical']);


        $router->get('/users', [UsersController::class, 'index']);
        $router->get('/users/getUsers', [UsersController::class, 'getUsers']);
        $router->post('/users/save', [UsersController::class, 'save']);
        $router->post('/users/delete', [UsersController::class, 'delete']);

        // --- REGISTRO Y RECUPERACIÓN ---
        $router->get('/register', [RegisterController::class, 'index']);
        $router->post('/register/submit', [RegisterController::class, 'submit']);
        $router->get('/register/validate', [RegisterController::class, 'validateToken']);
        $router->post('/register/create-password', [RegisterController::class, 'createPassword']);
        $router->get('/register/complete', [RegisterController::class, 'completeProfile']);
        $router->get('/forgot-password', [RegisterController::class, 'forgotPasswordIndex']);
        $router->post('/forgot-password/submit', [RegisterController::class, 'forgotPasswordSubmit']);

        // --- GESTIÓN OPERATIVA (WP BRIDGE) ---
        $router->get('/operational', [OperationalController::class, 'index']);
        // --- GESTIÓN OPERATIVA (WP BRIDGE) ---
        $router->get('/operational/professors', [OperationalProfessorsController::class, 'index']);
        $router->get('/operational/professors/list', [OperationalProfessorsController::class, 'list']);

        // AÑADE ESTAS DOS RUTAS NUEVAS PARA GUARDAR:
        $router->post('/operational/professors/saveTexts', [OperationalProfessorsController::class, 'saveTexts']);
        $router->post('/operational/professors/saveImage', [OperationalProfessorsController::class, 'saveImage']);


        // NOTICIAS / CARTELERA (NUEVO)

        $router->get('/operational/news', [OperationalNewsController::class, 'index']);
        $router->get('/operational/news/list', [OperationalNewsController::class, 'list']);
        $router->post('/operational/news/saveTexts', [OperationalNewsController::class, 'saveTexts']);
        $router->post('/operational/news/saveImage', [OperationalNewsController::class, 'saveImage']);
        $router->post('/operational/news/delete', [OperationalNewsController::class, 'delete']);
        $router->post('/operational/news/publish', [OperationalNewsController::class, 'publish']);
        // NOTICIAS / CARTELERA
        $router->post('/operational/news/unpublish', [OperationalNewsController::class, 'unpublish']);


        // PANEL GERENCIAL - OPCIONES GERENCIALES
        // PANEL GERENCIAL - OPCIONES GERENCIALES
        $router->get('/managerial', [ManagerialController::class, 'index']);
        $router->get('/managerial/matriculations-report', [ManagerialController::class, 'matriculationsReport']);
        $router->get('/managerial/bank-movements', [ManagerialController::class, 'bankMovements']);

        // MÓDULO GERENCIAL: REPORTE DE PAGOS DE INSCRITOS
        // 1. Ruta para cargar la vista
        $router->get('/managerial/payments-report', [ManagerialPaymentsReportController::class, 'index']);
        // 2. RUTA QUE TE FALTABA: Para que el JavaScript obtenga los datos JSON
        $router->get('/ManagerialPaymentsReport/getReportData', [ManagerialPaymentsReportController::class, 'getReportData']);
        // 3. RUTA PARA EL PDF:
        $router->get('/ManagerialPaymentsReport/exportPdf', [ManagerialPaymentsReportController::class, 'exportPdf']);

        // MÓDULO GERENCIAL: CONTROL DE PAGOS PENDIENTES
        $router->get('/managerial/pending-payments', [ManagerialPendingPaymentsController::class, 'index']);
        $router->get('/ManagerialPendingPayments/getPendingData', [ManagerialPendingPaymentsController::class, 'getPendingData']);
        $router->get('/ManagerialPendingPayments/exportPdf', [ManagerialPendingPaymentsController::class, 'exportPdf']);
        $router->get('/ManagerialPaymentsReport/getGroupsByOffering', [ManagerialPaymentsReportController::class, 'getGroupsByOffering']);

        // MÓDULO GERENCIAL: CONTROL ACADÉMICO (TRAZABILIDAD)
        $router->get('/managerial/academic-control', [ManagerialAcademicControlController::class, 'index']);
        $router->get('/managerial/academic-control/data', [ManagerialAcademicControlController::class, 'getEnrollmentData']);
        $router->get('/managerial/academic-control/groups', [ManagerialAcademicControlController::class, 'getGroups']);
        $router->get('/managerial/academic-control/exportPdf', [ManagerialAcademicControlController::class, 'exportPdf']);

        // MÓDULO GERENCIAL: CONCILIACIÓN BANCARIA
        $router->get('/managerial/bank-reconciliation', [ManagerialBankReconciliationController::class, 'index']);
        $router->get('/managerial/bank-reconciliation/data', [ManagerialBankReconciliationController::class, 'getReconciliationData']);
        $router->post('/managerial/bank-reconciliation/auto-match', [ManagerialBankReconciliationController::class, 'processAutoMatch']);
        $router->post('/managerial/bank-reconciliation/link-payment', [ManagerialBankReconciliationController::class, 'linkManualPayment']);

        // --- MÓDULO GERENCIAL: REPORTE GENERAL DE MOVIMIENTOS (TRAZABILIDAD 360°) ---
        // 1. Carga de la interfaz principal (Vista)
        $router->get('/managerial/movements-report', [ManagerialMovementsReportController::class, 'index']);

        // 2. Endpoint para obtener la matriz de datos detallada (AJAX)
        // CAMBIO: Se cambió getReportData por loadData para coincidir con el controlador
        $router->get('/ManagerialMovementsReport/loadData', [ManagerialMovementsReportController::class, 'loadData']);

        // 3. Endpoint para el filtro dinámico de grupos asociados a la oferta
        $router->get('/ManagerialMovementsReport/getGroupsByOffering', [ManagerialMovementsReportController::class, 'getGroupsByOffering']);


        // --- MÓDULO: ESTADOS DE CUENTA BANCARIOS ---
        $router->get('/financial/bank_statements', [FinancialBankStatementsController::class, 'index']);
        $router->get('/financial/bank_statements/tpago', [FinancialBankStatementsController::class, 'tpago']);
        $router->get('/financial/bank_statements/tpago/getTransactions', [FinancialBankStatementsController::class, 'getTpagoTransactions']);
        $router->post('/financial/bank_statements/tpago/uploadFile', [FinancialBankStatementsController::class, 'uploadTpagoFile']);
        $router->get('/financial/bank_statements/movimientos', [FinancialBankStatementsController::class, 'movimientos']);
        $router->get('/financial/bank_statements/movimientos/getTransactions', [FinancialBankStatementsController::class, 'getMovimientosTransactions']);
        $router->post('/financial/bank_statements/movimientos/uploadFile', [FinancialBankStatementsController::class, 'uploadMovimientosFile']);

        // --- ENDPOINT: Logos en Base64 para Excel ---
        $router->get('/assets/logos/base64', [FinancialController::class, 'getLogosBase64']);
        $router->dispatch();
    }

    private function setSession(array $u): void
    {
        $_SESSION['user'] = [
            'id'          => $u['id'],
            'user_id'     => $u['id'], 
            'name'        => trim($u['first_name'] . ' ' . $u['last_name']),
            'email'       => $u['email'],
            'document_id' => $u['document_id'] ?? '', 
            'user_type'   => $u['user_type'],
            'role'        => strtoupper($u['role']), 
            'status'      => $u['status'],
            'avatar'      => $u['avatar'] ?? 'default.png',
        ];
    }
}