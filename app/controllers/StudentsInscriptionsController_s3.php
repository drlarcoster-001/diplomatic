<?php
/**
 * MÓDULO: EVENTOS / ESTUDIANTES
 * ARCHIVO: app/Controllers/StudentsInscriptionsController_s3.php
 * PROPÓSITO: Procesamiento físico de archivos PDF y gestión de rutas de carga.
 * VERSIÓN: 1.0.0
 */

declare(strict_types=1);

namespace App\Controllers;

final class StudentsInscriptionsController_s3 
{
    private string $uploadDir = 'storage/students/documents/';

    /**
     * Gestiona la subida física del archivo al servidor.
     */
    public function handleFileUpload(array $file, string $type, int $userId): ?string 
    {
        if ($file['error'] !== UPLOAD_ERR_OK) return null;

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = "DOC_{$userId}_{$type}_" . time() . "." . $extension;
        $targetPath = $this->uploadDir . $fileName;

        if (!is_dir($this->uploadDir)) mkdir($this->uploadDir, 0755, true);

        return move_uploaded_file($file['tmp_name'], $targetPath) ? $targetPath : null;
    }
}