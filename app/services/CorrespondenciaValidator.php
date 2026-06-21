<?php
/**
 * MÓDULO: NÚCLEO / SERVICIOS
 * ARCHIVO: app/services/CorrespondenciaValidator.php
 * PROPÓSITO: Motor de consulta privada para validación de QRs de documentos
 *            de Correspondencia (cartas, memos, oficios, actas,
 *            reconocimientos, constancias). Espejo exacto de
 *            CertificateValidator.php, pero apuntando a
 *            tbl_correspondencia_documentos. No modifica ni depende de
 *            CertificateValidator (que sigue siendo exclusivo de
 *            Constancias de Estudio/Inscripción).
 * VERSIÓN: 1.0.0 - Creación inicial.
 */
class CorrespondenciaValidator {
    public static function validate($code) {
        try {
            $dbConfig = require_once __DIR__ . '/../config/database.php';
            $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";

            $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);

            $sql = "SELECT d.codigo AS code, d.tabla_objetivo, d.generated_at, d.valores_sistema,
                           p.nombre AS plantilla_nombre, p.tipo_documento
                    FROM tbl_correspondencia_documentos d
                    INNER JOIN tbl_correspondencia_plantillas p ON p.id = d.plantilla_id
                    WHERE d.codigo = ? LIMIT 1";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$code]);
            $row = $stmt->fetch();
            if (!$row) return null;

            $valores  = json_decode($row['valores_sistema'] ?? '{}', true) ?: [];
            $titular  = $valores['{estudiante_nombre}'] ?? $valores['{profesor_nombre}'] ?? $valores['{centro_nombre}'] ?? $valores['{departamento_nombre}'] ?? '';
            $apellido = $valores['{estudiante_apellido}'] ?? $valores['{profesor_apellido}'] ?? '';
            $row['titular'] = trim($titular . ' ' . $apellido);

            return $row;

        } catch (Exception $e) {
            error_log("Fallo en CorrespondenciaValidator: " . $e->getMessage());
            return null;
        }
    }
}