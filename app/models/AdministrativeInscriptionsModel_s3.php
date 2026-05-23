<?php
/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA
 * ARCHIVO: app/Models/AdministrativeInscriptionsModel_s3.php
 * PROPÓSITO: Estructura base para validaciones de base de datos en el Paso 3 (Documentos).
 * VERSIÓN: 2.0.0 - División modular. Preparado para futuras validaciones documentales.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class AdministrativeInscriptionsModel_s3
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    // El manejo físico de archivos (uploadFile) se realiza en el Controlador.
    // Este espacio queda reservado para futuras validaciones SQL de documentos (ej. OCR metadata).
}