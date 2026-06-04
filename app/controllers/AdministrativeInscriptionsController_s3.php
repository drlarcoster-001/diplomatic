<?php
/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA
 * ARCHIVO: app/Controllers/AdministrativeInscriptionsController_s3.php
 * PROPÓSITO: Manipulación y guardado físico de los documentos en el servidor.
 * VERSIÓN: 2.1.1 - FIX DE UNIFICACIÓN: Limpieza automática de prefijos 'public/' para evitar carpetas redundantes.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

final class AdministrativeInscriptionsController_s3 extends Controller
{
    /**
     * Sube el archivo al disco y retorna la ruta relativa para la BD.
     * @param string $input Nombre del campo en $_FILES.
     * @param string $physicalPath Ruta en disco (ej: uploads/enrollments/22/).
     * @param string $dbPath Ruta para guardar en DB (ej: uploads/enrollments/22/).
     */
   public function uploadFile(string $input, string $physicalPath, string $dbPath): ?string 
{
    if (!isset($_FILES[$input]) || $_FILES[$input]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    // FIX: Eliminamos 'public/' de la ruta física para evitar la carpeta duplicada
    // Al ser relativo, PHP lo guardará correctamente en la carpeta public actual.
    $cleanPhysical = ltrim(str_replace(['public/', 'public\\'], ['', ''], $physicalPath), '/\\');
    $cleanDb       = ltrim(str_replace(['public/', 'public\\'], ['', ''], $dbPath), '/\\');
    
    $ext = pathinfo($_FILES[$input]['name'], PATHINFO_EXTENSION);
    $userId = (int)($_POST['user_id'] ?? 0);
    $docType = str_replace(['file_', 'pay_'], ['', ''], $input);
    $fileName = 'DOC_' . $userId . '_' . $docType . '_' . time() . '.' . $ext;
    
    // Crear el directorio usando la ruta limpia
    if (!is_dir($cleanPhysical)) {
        mkdir($cleanPhysical, 0755, true);
    }

    if (move_uploaded_file($_FILES[$input]['tmp_name'], $cleanPhysical . $fileName)) {
        return $cleanDb . $fileName; 
    }
    return null;
}
}