<?php
/**
 * MÓDULO: NÚCLEO / BRIDGE WP
 * ARCHIVO: /diplomatic-api.php
 * PROPÓSITO: Versión con soporte para campo personalizado "subtitulo".
 * VERSIÓN: 4.1.0 - Mapping de subtitulo y cuerpo_noticia.
 */

define('WP_USE_THEMES', false);
define('WP_ADMIN', true); 
ini_set('display_errors', '0'); 

try {
    $wp_load_path = __DIR__ . '/wp-load.php';
    if (!file_exists($wp_load_path)) throw new Exception('No se encuentra wp-load.php');
    require_once($wp_load_path);

    require_once(ABSPATH . 'wp-admin/includes/post.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/taxonomy.php');

    $mi_llave_secreta = 'Diplomatic_Secure_2026_Token'; 
    if (($_REQUEST['token'] ?? '') !== $mi_llave_secreta) {
        header('HTTP/1.0 403 Forbidden'); exit('Acceso denegado');
    }

    $accion = $_REQUEST['action'] ?? 'ping';
    if (ob_get_level() > 0) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');

    if ($accion === 'ping') exit(json_encode(['ok' => true, 'version' => '4.1.0']));

    if ($accion === 'create_post') {
        $post_id_remoto = (int)($_REQUEST['post_id'] ?? 0);
        $titulo    = $_REQUEST['title'] ?? 'Sin Título';
        $raw_text  = $_REQUEST['content'] ?? '';
        
        $contenido_con_p = wpautop($raw_text); 
        $meta_data = $_REQUEST['meta'] ?? [];
        $extracto = $meta_data['excerpt'] ?? '';

        $post_data = [
            'post_title'   => $titulo,
            'post_content' => $contenido_con_p,
            'post_excerpt' => $extracto,
            'post_status'  => 'publish',
            'post_type'    => 'post',
            'post_author'  => 1
        ];

        if ($post_id_remoto > 0 && get_post($post_id_remoto)) {
            $post_data['ID'] = $post_id_remoto;
            $post_id = wp_update_post($post_data);
        } else {
            $post_id = wp_insert_post($post_data);
        }

        if (is_wp_error($post_id)) throw new Exception($post_id->get_error_message());

        // ============================================================
        // MAPEO DE CAMPOS PERSONALIZADOS PARA PLANTILLA NEWS
        // ============================================================
        // 1. Cuerpo de la noticia
        update_post_meta($post_id, 'cuerpo_noticia', $contenido_con_p);
        
        // 2. Subtítulo (El que se me había escapado)
        if (!empty($extracto)) {
            update_post_meta($post_id, 'subtitulo', $extracto);
        }

        // Reset de plantilla para que el Theme Builder trabaje tranquilo
        update_post_meta($post_id, '_wp_page_template', 'default');
        delete_post_meta($post_id, '_elementor_edit_mode');
        delete_post_meta($post_id, '_elementor_data');

        // Otros metadatos (Profesores, etc.)
        if (is_array($meta_data)) {
            foreach ($meta_data as $key => $val) {
                update_post_meta($post_id, sanitize_key($key), $val);
            }
        }

        // Imagen Destacada
        if (!empty($_REQUEST['photo_base64'])) {
            $photo_base64 = $_REQUEST['photo_base64'];
            if (strpos($photo_base64, 'base64,') !== false) $photo_base64 = explode('base64,', $photo_base64)[1];
            $decoded = base64_decode($photo_base64);
            if ($decoded) {
                $upload = wp_upload_bits($_REQUEST['photo_name'] ?? 'news.jpg', null, $decoded);
                if (!$upload['error']) {
                    $attach_id = wp_insert_attachment([
                        'post_mime_type' => wp_check_filetype($upload['file'])['type'],
                        'post_title'     => sanitize_file_name($titulo),
                        'post_status'    => 'inherit'
                    ], $upload['file'], $post_id);
                    wp_update_attachment_metadata($attach_id, wp_generate_attachment_metadata($attach_id, $upload['file']));
                    set_post_thumbnail($post_id, $attach_id);
                }
            }
        }

        // Categoría
        $categoria_input = $_REQUEST['category'] ?? 'Cartelera';
        $term = term_exists($categoria_input, 'category');
        $cat_id = $term ? (is_array($term) ? $term['term_id'] : $term) : wp_create_category($categoria_input);
        wp_set_post_categories($post_id, [(int)$cat_id]);

        echo json_encode(['ok' => true, 'post_id' => $post_id, 'message' => "Sincronizado con subtitulo."]);
        exit;
    }

    // Acción Eliminar (Sin cambios)
    if ($accion === 'delete_post') {
        $id = (int)($_REQUEST['post_id'] ?? 0);
        if ($id > 0) {
            $thumb = get_post_thumbnail_id($id);
            if ($thumb) wp_delete_attachment($thumb, true);
            wp_delete_post($id, true);
        }
        echo json_encode(['ok' => true]); exit;
    }

} catch (\Throwable $e) {
    echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
}