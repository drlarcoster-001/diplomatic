<?php
/**
 * MÓDULO: EVENTOS / ESTUDIANTES
 * ARCHIVO: app/Controllers/StudentsInscriptionsController_s2.php
 * PROPÓSITO: Validación y persistencia de información de base (Paso 2).
 * VERSIÓN: 1.0.0
 */

declare(strict_types=1);

namespace App\Controllers;

final class StudentsInscriptionsController_s2 
{
    /**
     * Valida si los datos del perfil son aptos para la inscripción.
     */
    public function validateProfileData(array $profile): bool 
    {
        return !empty($profile['undergraduate_degree']) && !empty($profile['provenance']);
    }
}