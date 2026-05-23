<?php
/**
 * MÓDULO: NÚCLEO / SERVICIOS
 * ARCHIVO: app/services/CertificateValidator.php
 * PROPÓSITO: Motor de consulta privada para validación de QRs.
 * VERSIÓN: 1.1.2 - FIX: Inclusión de campo 'type' en SELECT para evitar Undefined Array Key.
 */

class CertificateValidator {
    public static function validate($code) {
        try {
            $dbConfig = require_once __DIR__ . '/../config/database.php';

            $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
            
            $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);

            // Se agregó c.type para que la interfaz pueda diferenciar los colores
            $sql = "SELECT u.first_name, u.last_name, d.name as diplomado, c.created_at, c.code, c.type
                    FROM tbl_students_certificates c
                    INNER JOIN tbl_students s ON c.student_id = s.id
                    INNER JOIN tbl_users u ON s.user_id = u.id
                    INNER JOIN tbl_academic_offerings ao ON c.offering_id = ao.id
                    INNER JOIN tbl_diplomados d ON ao.diploma_id = d.id
                    WHERE c.code = ? LIMIT 1";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$code]);
            return $stmt->fetch();
            
        } catch (Exception $e) {
            error_log("Fallo en CertificateValidator: " . $e->getMessage());
            return null;
        }
    }
}