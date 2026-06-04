<?php
/**
 * MÓDULO: AUTOGESTIÓN ESTUDIANTIL / CONTROLADORES
 * ARCHIVO: app/controllers/StudentsPaymentRegistrationController.php
 * PROPÓSITO: Orquestador central de pagos para alumnos. Integra lógica de S1 a S5.
 * VERSIÓN: 1.3.0 - FIX: Inyección de tasa BCV en el index y blindaje de roles.
 * REGLA DE EQUIPO: Este archivo pega los Traits. La lógica de montos reside en el Trait S4.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\StudentsPaymentRegistrationModel;

class StudentsPaymentRegistrationController extends Controller
{
    // --- INTEGRACIÓN DE MÓDULOS (TRAITS ESTUDIANTILES) ---
    // Cada Trait maneja una etapa del Wizard (Búsqueda, Selección, Registro, Notificación)
    use StudentsPaymentRegistrationController_s1;
    use StudentsPaymentRegistrationController_s2; 
    use StudentsPaymentRegistrationController_s4;
    use StudentsPaymentRegistrationController_s5; 

    /**
     * El modelo debe ser 'protected' para que los Traits puedan 
     * acceder a $this->model sin restricciones.
     */
    protected StudentsPaymentRegistrationModel $model;

    public function __construct()
    {
        // Asegurar que la sesión esté disponible para las validaciones
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->model = new StudentsPaymentRegistrationModel();

        // BLINDAJE 1: Verificación de sesión activa
        if (!isset($_SESSION['user']['id'])) {
            header('Location: /diplomatic/public/login');
            exit;
        }

        // BLINDAJE 2: Verificación de Identidad Estudiantil
        // Evita que un administrativo o un usuario sin perfil de alumno use este wizard
        $userId = (int) $_SESSION['user']['id'];
        $studentId = $this->model->getStudentIdByUserId($userId);
        
        if ($studentId === null) {
            // Redirección con bandera de alerta para disparar SweetAlert o Modal en el Dashboard
            header('Location: /diplomatic/public/students?alert=needs_enrollment');
            exit;
        }
    }

    /**
     * Punto de entrada: Carga la interfaz del wizard de reporte de pagos.
     * Sincronizado con la lógica administrativa para proveer la tasa del día.
     */
    public function index(): void
    {
        // Shielding: Limpiar cualquier salida accidental previa para evitar errores de cabecera
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        ob_start();

        /**
         * DETALLE CORREGIDO: 
         * Se inyecta la tasa BCV y el título de la página.
         * El frontend necesita 'tasa' para calcular la equivalencia en el modal de Pago Móvil.
         */
        $this->view('students/payment_registration/index', [
            'title' => 'Reportar Pago de Cuota',
            'tasa'  => $this->model->getLatestExchangeRate(),
            'user'  => $_SESSION['user']
        ]);
        
        ob_end_flush();
    }
}