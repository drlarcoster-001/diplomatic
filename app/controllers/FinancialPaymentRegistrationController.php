<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / CONTROLADORES
 * ARCHIVO: app/controllers/FinancialPaymentRegistrationController.php
 * PROPÓSITO: Orquestador central del Wizard de Pagos (Administrativo). 
 * Integra búsqueda (S1), selección (S2), persistencia (S4) y notificaciones (S5).
 * VERSIÓN: 2.2.0 - Sincronización con Modelo v2.6.0 y Trait S4 v2.4.0.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\FinancialPaymentRegistrationModel;

/**
 * NOTA PARA EL EQUIPO:
 * Este controlador no procesa lógica directamente, delega en los Traits.
 * Si se detecta un error en el registro de montos, el archivo a editar es el Trait S4.
 */
class FinancialPaymentRegistrationController extends Controller
{
    // --- INTEGRACIÓN DE MÓDULOS (TRAITS) ---
    // S1: Búsqueda de Estudiantes
    use FinancialPaymentRegistrationController_s1;
    
    // S2: Selección de Programas y Deuda
    use FinancialPaymentRegistrationController_s2; 
    
    // S4: Estado de Cuenta y Registro Físico (Aquí reside la lógica de conversión BS/USD)
    use FinancialPaymentRegistrationController_s4;
    
    // S5: Generación de Recibos y Notificaciones
    use FinancialPaymentRegistrationController_s5; 

    /**
     * El modelo se define como 'protected' para que los Traits
     * puedan acceder a los métodos de base de datos mediante $this->model.
     */
    protected FinancialPaymentRegistrationModel $model;

    public function __construct()
    {
        // Se inicializa el modelo unificado para todo el flujo financiero
        $this->model = new FinancialPaymentRegistrationModel();

        // Verificación de sesión administrativa
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] === 'STUDENT') {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Acceso administrativo requerido.']);
                exit;
            }
            header("Location: /diplomatic/public/login");
            exit;
        }
    }

    /**
     * Punto de entrada: Carga la interfaz principal del Wizard.
     * Implementa limpieza de búfer para evitar basura en el renderizado de la UI.
     */
    public function index(): void
    {
        // Shielding: Limpiar cualquier salida accidental previa (Warnings o espacios en blanco)
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        ob_start();

        // Carga de la vista maestra del registro de pagos
        $this->view('financial/payment_registration/index', [
            'title' => 'Registro de Pagos Administrativo',
            'tasa'  => $this->model->getLatestExchangeRate()
        ]);
        
        ob_end_flush();
    }
}