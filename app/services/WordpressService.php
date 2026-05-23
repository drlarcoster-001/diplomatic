<?php
/**
 * MÓDULO: NÚCLEO / SERVICIOS
 * ARCHIVO: app/Services/WordpressService.php
 * PROPÓSITO: Transporte HTTP para el Bridge de WordPress con soporte para Base64.
 * VERSIÓN: 2.3.0 - Soporte para transferencia de imágenes por Base64 puro.
 */

declare(strict_types=1);

namespace App\Services;

use Exception;

final class WordpressService 
{
    private string $baseUrl;
    private string $token;

    public function __construct(string $baseUrl, string $token) 
    {
        $this->baseUrl = rtrim(trim($baseUrl), '/');
        $this->token   = trim($token);
    }

    /**
     * Verifica la conectividad con el Bridge remoto (Ping).
     */
    public function authenticate(): bool 
    {
        try {
            $response = $this->executeRequest([
                'token'  => $this->token,
                'action' => 'ping'
            ]);
            return isset($response['ok']) && $response['ok'] === true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Envía una publicación a WordPress.
     */
    public function createPost(
        string $title, 
        string $content, 
        string $category, 
        string $photoUrl = '', 
        int $wpPostId = 0, 
        array $extra = []
    ): array {
        // Preparamos el paquete base
        $postFields = [
            'token'     => $this->token,
            'action'    => 'create_post',
            'post_id'   => $wpPostId,
            'title'     => $title,
            'content'   => $content,
            'category'  => $category,
            'tags'      => 'docente', 
        ];

        // Mantenemos la photoUrl por compatibilidad (por si acaso)
        if (!empty($photoUrl)) {
            $postFields['photo_url'] = $photoUrl;
        }

        // Inyectamos los metadatos (tipo y bio)
        if (!empty($extra['meta'])) {
            $postFields['meta'] = $extra['meta'];
        }

        // --- NUEVO: INYECTAMOS LA IMAGEN EN BASE64 ---
        if (!empty($extra['image_data']['base64'])) {
            $postFields['photo_base64'] = $extra['image_data']['base64'];
            $postFields['photo_name']   = $extra['image_data']['name'] ?? 'docente.jpg';
        }
        // ----------------------------------------------

        return $this->executeRequest($postFields);
    }

    /**
     * Elimina un post de WordPress por su ID.
     */
    public function deletePost(int $postId): array
    {
        return $this->executeRequest([
            'token'   => $this->token,
            'action'  => 'delete_post',
            'post_id' => $postId
        ]);
    }

    /**
     * Motor central de ejecución cURL.
     */
    private function executeRequest(array $postFields): array
    {
        $endpoint = "{$this->baseUrl}/diplomatic-api.php";
        
        $ch = curl_init($endpoint);
        
        // http_build_query codificará automáticamente el gigantesco texto Base64
        // para que viaje seguro por internet sin corromperse.
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true, 
            CURLOPT_TIMEOUT        => 60, // Subimos a 60s porque subir fotos físicas toma un poquito más
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'DiplomaticPanel/2.3.0',
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($postFields)
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new Exception("Error de comunicación (cURL): " . $curlError);
        }

        $cleanResponse = trim((string)$response);
        $data = json_decode($cleanResponse, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $preview = substr(strip_tags($cleanResponse), 0, 200);
            throw new Exception("Respuesta inválida del Bridge (HTTP $httpCode). Contenido: " . $preview);
        }

        return $data;
    }
}