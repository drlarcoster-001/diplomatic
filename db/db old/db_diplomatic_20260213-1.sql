-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 13-02-2026 a las 11:52:39
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `db_diplomatic`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_company_settings`
--

CREATE TABLE `tbl_company_settings` (
  `id` int(11) NOT NULL,
  `nombre_legal` varchar(255) DEFAULT NULL,
  `nombre_comercial` varchar(255) DEFAULT NULL,
  `id_fiscal` varchar(100) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `sitio_web` varchar(255) DEFAULT NULL,
  `representante` varchar(255) DEFAULT NULL,
  `cargo_rep` varchar(150) DEFAULT NULL,
  `tel_rep` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tbl_company_settings`
--

INSERT INTO `tbl_company_settings` (`id`, `nombre_legal`, `nombre_comercial`, `id_fiscal`, `direccion`, `telefono`, `email`, `sitio_web`, `representante`, `cargo_rep`, `tel_rep`, `created_at`, `updated_at`) VALUES
(1, 'Diplomados', 'Plataforma Diplomados', 'G1212121212', '10773 NW 58th ST, Doral FL 33178', '+584145365380', 'admin@plataformadiplomados.com', 'www.plataformadiplomados.com', 'JORGE PEREIRA', 'PROGRAMADOR', '+584145042125', '2026-02-12 15:54:22', '2026-02-12 15:58:10');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_email_settings`
--

CREATE TABLE `tbl_email_settings` (
  `id` int(11) NOT NULL,
  `tipo_correo` enum('INSCRIPCION','DOCUMENTOS') NOT NULL,
  `smtp_host` varchar(255) DEFAULT NULL,
  `smtp_port` int(11) DEFAULT NULL,
  `smtp_security` enum('TLS','SSL','NONE') DEFAULT NULL,
  `smtp_user` varchar(255) DEFAULT NULL,
  `smtp_password` varchar(255) DEFAULT NULL,
  `from_name` varchar(255) DEFAULT NULL,
  `from_email` varchar(255) DEFAULT NULL,
  `asunto` varchar(255) DEFAULT NULL,
  `contenido` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_email_settings`
--

INSERT INTO `tbl_email_settings` (`id`, `tipo_correo`, `smtp_host`, `smtp_port`, `smtp_security`, `smtp_user`, `smtp_password`, `from_name`, `from_email`, `asunto`, `contenido`, `created_at`, `updated_at`) VALUES
(1, 'INSCRIPCION', 'mail.plataformadiplomados.com', 465, 'SSL', 'no-reply@plataformadiplomados.com', 'WkV?yPC0!{]mgOMa', 'Diplamtic', 'no-reply@plataformadiplomados.com', '', '', '2026-02-12 13:31:16', '2026-02-12 13:31:16'),
(2, 'INSCRIPCION', 'mail.plataformadiplomados.com', 465, 'SSL', 'no-reply@plataformadiplomados.com', 'WkV?yPC0!{]mgOMa', 'Diplamtic', 'no-reply@plataformadiplomados.com', 'Diplomatic', 'Mensaje', '2026-02-12 14:06:42', '2026-02-12 14:06:42'),
(3, 'DOCUMENTOS', 'mail.plataformadiplomados.com', 465, 'SSL', 'no-reply@plataformadiplomados.com', 'WkV?yPC0!{]mgOMa', 'Diplomatic Certificados', 'no-reply@plataformadiplomados.com', '', '', '2026-02-12 14:14:53', '2026-02-12 14:14:53'),
(4, 'DOCUMENTOS', 'mail.plataformadiplomados.com', 465, 'SSL', 'no-reply@plataformadiplomados.com', 'WkV?yPC0!{]mgOMa', 'Diplomatic Certificados', 'no-reply@plataformadiplomados.com', 'Diplomatic Certificados', 'Certificado', '2026-02-12 14:15:41', '2026-02-12 14:15:41'),
(5, 'INSCRIPCION', '', 465, 'SSL', 'no-reply@plataformadiplomados.com', 'WkV?yPC0!{]mgOMa', 'Diplamtic', 'no-reply@plataformadiplomados.com', 'Diplomatic Inscripción', 'Mensaje', '2026-02-12 14:15:50', '2026-02-12 14:15:50'),
(6, 'INSCRIPCION', 'mail.plataformadiplomados.com', 465, 'SSL', 'no-reply@plataformadiplomados.com', 'WkV?yPC0!{]mgOMa', 'Diplomatic Inscripciones', 'no-reply@plataformadiplomados.com', 'Diplomatic Inscripción', 'Mensaje', '2026-02-12 14:16:42', '2026-02-12 14:16:42'),
(7, 'INSCRIPCION', 'mail.plataformadiplomados.com', 465, 'SSL', 'no-reply@plataformadiplomados.com', 'WkV?yPC0!{]mgOMa', 'Diplomatic Inscripcion', 'no-reply@plataformadiplomados.com', 'Diplomatic Inscripción', 'Mensaje', '2026-02-12 14:22:30', '2026-02-12 14:22:30');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_event_catalog`
--

CREATE TABLE `tbl_event_catalog` (
  `id` int(10) UNSIGNED NOT NULL,
  `event_code` varchar(30) NOT NULL,
  `event_type` enum('SECURITY','ACADEMIC','FINANCIAL','SYSTEM','DOCUMENT') NOT NULL,
  `severity` enum('LOW','MEDIUM','HIGH','CRITICAL') NOT NULL DEFAULT 'LOW',
  `title` varchar(120) NOT NULL,
  `message_template` varchar(255) NOT NULL,
  `status` enum('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_event_logs`
--

CREATE TABLE `tbl_event_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `event_catalog_id` int(10) UNSIGNED NOT NULL,
  `event_code` varchar(30) NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `role_key` varchar(60) DEFAULT NULL,
  `session_id` int(10) UNSIGNED DEFAULT NULL,
  `module_key` varchar(60) DEFAULT NULL,
  `action_key` varchar(60) DEFAULT NULL,
  `result` enum('SUCCESS','FAIL') NOT NULL DEFAULT 'SUCCESS',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `entity_table` varchar(80) DEFAULT NULL,
  `entity_id` bigint(20) UNSIGNED DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `occurred_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_permissions`
--

CREATE TABLE `tbl_permissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `perm_key` varchar(80) NOT NULL,
  `module_key` varchar(60) NOT NULL,
  `action_key` varchar(60) NOT NULL,
  `description` varchar(255) NOT NULL,
  `status` enum('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_pre_users`
--

CREATE TABLE `tbl_pre_users` (
  `id` int(10) UNSIGNED NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `document_id` varchar(60) DEFAULT NULL,
  `status` enum('PENDING','VERIFIED','EXPIRED','BLOCKED') NOT NULL DEFAULT 'PENDING',
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `temp_password_hash` varchar(255) DEFAULT NULL,
  `temp_password_expires_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_pre_user_tokens`
--

CREATE TABLE `tbl_pre_user_tokens` (
  `id` int(11) UNSIGNED NOT NULL,
  `pre_user_id` int(10) UNSIGNED NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_roles`
--

CREATE TABLE `tbl_roles` (
  `id` int(10) UNSIGNED NOT NULL,
  `role_key` varchar(60) NOT NULL,
  `name` varchar(120) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `level` int(11) NOT NULL DEFAULT 1,
  `status` enum('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_roles`
--

INSERT INTO `tbl_roles` (`id`, `role_key`, `name`, `description`, `level`, `status`, `created_at`, `updated_at`) VALUES
(1, 'ADMIN', 'Administrador', 'Control total, auditoría y configuración global del sistema', 10, 'ACTIVE', '2026-02-11 08:40:30', '2026-02-11 08:40:30'),
(2, 'FINANZAS', 'Finanzas', 'Gestión centralizada de cobros, validación de soportes y reportes financieros', 5, 'ACTIVE', '2026-02-11 08:40:30', '2026-02-11 08:40:30'),
(3, 'ACADEMIC', 'Académico', 'Administración de diplomados, cohortes, profesores y control de estudios', 5, 'ACTIVE', '2026-02-11 08:40:30', '2026-02-11 08:40:30'),
(4, 'OPERATOR', 'Operador', 'Administración de cartelera digital, noticias y soporte a inscripciones', 3, 'ACTIVE', '2026-02-11 08:40:30', '2026-02-11 08:40:30'),
(5, 'PARTICIPANT', 'Participante', 'Acceso a oferta académica, registro de pagos y estado de cuenta', 1, 'ACTIVE', '2026-02-11 08:40:30', '2026-02-11 08:40:30');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_role_permissions`
--

CREATE TABLE `tbl_role_permissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `role_id` int(10) UNSIGNED NOT NULL,
  `permission_id` int(10) UNSIGNED NOT NULL,
  `status` enum('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_sessions`
--

CREATE TABLE `tbl_sessions` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `started_at` datetime NOT NULL DEFAULT current_timestamp(),
  `last_activity_at` datetime DEFAULT NULL,
  `ended_at` datetime DEFAULT NULL,
  `end_reason` enum('LOGOUT','TIMEOUT','FORCED','UNKNOWN') DEFAULT NULL,
  `status` enum('ACTIVE','CLOSED') NOT NULL DEFAULT 'ACTIVE',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_users`
--

CREATE TABLE `tbl_users` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_type` enum('INTERNAL','PARTICIPANT') NOT NULL DEFAULT 'INTERNAL',
  `status` enum('ACTIVE','SUSPENDED','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(190) NOT NULL,
  `role` varchar(60) DEFAULT 'PARTICIPANT',
  `phone` varchar(30) DEFAULT NULL,
  `document_id` varchar(30) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `provenance` varchar(150) DEFAULT NULL,
  `undergraduate_degree` varchar(150) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT 'default_avatar.png',
  `password_hash` varchar(255) NOT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_users`
--

INSERT INTO `tbl_users` (`id`, `user_type`, `status`, `first_name`, `last_name`, `email`, `role`, `phone`, `document_id`, `address`, `provenance`, `undergraduate_degree`, `avatar`, `password_hash`, `last_login_at`, `created_at`, `updated_at`) VALUES
(1, 'INTERNAL', 'ACTIVE', 'ADMIN', 'GENERAL', 'admin@diplomatic.local', 'ADMIN', NULL, NULL, NULL, NULL, NULL, 'default_avatar.png', '$2y$10$00VC/qpQDbIDycCmDk0.Au4AEP7EfpgQ8J451Zs9YOvzdH4Vpty5K', '2026-02-13 06:14:18', '2026-02-07 17:08:04', '2026-02-13 06:14:18'),
(2, 'INTERNAL', 'ACTIVE', 'JORGE', 'PEREIRA', 'raizamileva@gmail.com', 'ADMIN', '+584145365380', '14979523', 'Urb El Valle, Calle Principal, Casa Nro 23, Cabudare, Jose Gregorio Bastidas, Palavecino', 'Cabudare', 'Ingenieria', 'usr_1770810835.png', '$2y$10$Pa.672SkyV7tzG8yfpeI5uInUoOt7T/YKWdtVa.dfqqsKjjLFn8pG', NULL, '2026-02-08 11:35:40', '2026-02-11 09:01:47'),
(4, 'INTERNAL', 'ACTIVE', 'Homer', 'Pereira', 'ddrgomes@gmail.com', 'FINANZAS', '', '13567117', 'Urb Club Hipico Las Trinitarias, Res La Trinitaria Piso 4 Apartament 44', 'Barquisimeto', 'Ingenieria Quimica', 'usr_1770811774.png', '$2y$10$3BwNQJ7Ym4CqChkOZ1bTe.NfrBfO89YNFsjtdE5Q.EAqN3ab027p6', NULL, '2026-02-11 08:05:52', '2026-02-11 09:03:58'),
(6, 'INTERNAL', 'ACTIVE', 'Luis', 'Olivares', 'jorgeluispereiraolivares@gmail.com', 'OPERATOR', '+5841450421255', '149795231', 'Calle 52', 'Cabudare', 'Ingenieria', 'usr_1770814743.png', '$2y$10$0Lmn1psgOnluJk9Ndeg7AeLmiFc8RfSwvcFwuAJwuUChqnPq4570u', NULL, '2026-02-11 08:58:54', '2026-02-11 09:39:40');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_wa_manual_logs`
--

CREATE TABLE `tbl_wa_manual_logs` (
  `id` int(11) NOT NULL,
  `estudiante` varchar(255) DEFAULT NULL,
  `evento` varchar(50) DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `fecha_envio` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_wa_manual_logs`
--

INSERT INTO `tbl_wa_manual_logs` (`id`, `estudiante`, `evento`, `telefono`, `fecha_envio`) VALUES
(1, 'Jorge Pereira', 'INSCRIPCION', '+584145365380', '2026-02-12 17:31:26'),
(2, 'Raiza Mileva', 'CONSTANCIA_ESTUDIO', '+584124571599', '2026-02-12 17:31:59');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_wa_manual_templates`
--

CREATE TABLE `tbl_wa_manual_templates` (
  `id` int(11) NOT NULL,
  `evento` varchar(50) NOT NULL,
  `mensaje` text DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_wa_manual_templates`
--

INSERT INTO `tbl_wa_manual_templates` (`id`, `evento`, `mensaje`, `activo`) VALUES
(1, 'INSCRIPCION', 'Hola {NOMBRE}, tu inscripción al diplomado {DIPLOMADO} ha sido confirmada. ¡Nos vemos pronto! {URL_PORTAL}', 1),
(2, 'CONSTANCIA_ESTUDIO', 'Hola {NOMBRE}, tu constancia de estudio para {DIPLOMADO} ya está disponible aquí: {URL_PORTAL}', 1),
(3, 'CONSTANCIA_INSCRIPCION', 'Hola {NOMBRE}, adjuntamos tu constancia de inscripción al diplomado {DIPLOMADO}: {URL_PORTAL}', 1),
(4, 'PAGO', 'Hola {NOMBRE}, te recordamos que tienes un pago pendiente en el diplomado {DIPLOMADO}. Puedes gestionarlo aquí: {URL_PORTAL}', 1);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `tbl_company_settings`
--
ALTER TABLE `tbl_company_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `tbl_email_settings`
--
ALTER TABLE `tbl_email_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `tbl_event_catalog`
--
ALTER TABLE `tbl_event_catalog`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_event_catalog_code` (`event_code`),
  ADD KEY `idx_event_catalog_type` (`event_type`),
  ADD KEY `idx_event_catalog_severity` (`severity`);

--
-- Indices de la tabla `tbl_event_logs`
--
ALTER TABLE `tbl_event_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_event_logs_code` (`event_code`),
  ADD KEY `idx_event_logs_user` (`user_id`),
  ADD KEY `idx_event_logs_session` (`session_id`),
  ADD KEY `idx_event_logs_occurred` (`occurred_at`),
  ADD KEY `idx_event_logs_entity` (`entity_table`,`entity_id`),
  ADD KEY `fk_event_logs_catalog` (`event_catalog_id`);

--
-- Indices de la tabla `tbl_permissions`
--
ALTER TABLE `tbl_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_permissions_key` (`perm_key`),
  ADD KEY `idx_permissions_module` (`module_key`),
  ADD KEY `idx_permissions_action` (`action_key`);

--
-- Indices de la tabla `tbl_pre_users`
--
ALTER TABLE `tbl_pre_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_pre_users_email` (`email`);

--
-- Indices de la tabla `tbl_pre_user_tokens`
--
ALTER TABLE `tbl_pre_user_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pre_tokens_pre_user_id` (`pre_user_id`);

--
-- Indices de la tabla `tbl_roles`
--
ALTER TABLE `tbl_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_roles_key` (`role_key`);

--
-- Indices de la tabla `tbl_role_permissions`
--
ALTER TABLE `tbl_role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_role_permission` (`role_id`,`permission_id`),
  ADD KEY `fk_role_permissions_permission` (`permission_id`);

--
-- Indices de la tabla `tbl_sessions`
--
ALTER TABLE `tbl_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sessions_token` (`session_token`),
  ADD KEY `idx_sessions_user` (`user_id`),
  ADD KEY `idx_sessions_status` (`status`);

--
-- Indices de la tabla `tbl_users`
--
ALTER TABLE `tbl_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_email` (`email`);

--
-- Indices de la tabla `tbl_wa_manual_logs`
--
ALTER TABLE `tbl_wa_manual_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `tbl_wa_manual_templates`
--
ALTER TABLE `tbl_wa_manual_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_evento_wa` (`evento`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `tbl_company_settings`
--
ALTER TABLE `tbl_company_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `tbl_email_settings`
--
ALTER TABLE `tbl_email_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `tbl_event_catalog`
--
ALTER TABLE `tbl_event_catalog`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tbl_event_logs`
--
ALTER TABLE `tbl_event_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tbl_permissions`
--
ALTER TABLE `tbl_permissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tbl_pre_users`
--
ALTER TABLE `tbl_pre_users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tbl_pre_user_tokens`
--
ALTER TABLE `tbl_pre_user_tokens`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tbl_roles`
--
ALTER TABLE `tbl_roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `tbl_role_permissions`
--
ALTER TABLE `tbl_role_permissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tbl_sessions`
--
ALTER TABLE `tbl_sessions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tbl_users`
--
ALTER TABLE `tbl_users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `tbl_wa_manual_logs`
--
ALTER TABLE `tbl_wa_manual_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `tbl_wa_manual_templates`
--
ALTER TABLE `tbl_wa_manual_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `tbl_event_logs`
--
ALTER TABLE `tbl_event_logs`
  ADD CONSTRAINT `fk_event_logs_catalog` FOREIGN KEY (`event_catalog_id`) REFERENCES `tbl_event_catalog` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_event_logs_session` FOREIGN KEY (`session_id`) REFERENCES `tbl_sessions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_event_logs_user` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `tbl_pre_user_tokens`
--
ALTER TABLE `tbl_pre_user_tokens`
  ADD CONSTRAINT `fk_pre_tokens_pre_users` FOREIGN KEY (`pre_user_id`) REFERENCES `tbl_pre_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `tbl_role_permissions`
--
ALTER TABLE `tbl_role_permissions`
  ADD CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `tbl_permissions` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `tbl_roles` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `tbl_sessions`
--
ALTER TABLE `tbl_sessions`
  ADD CONSTRAINT `fk_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
