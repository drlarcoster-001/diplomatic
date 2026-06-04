-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 11-02-2026 a las 13:15:41
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
  `phone` varchar(30) DEFAULT NULL,
  `document_id` varchar(30) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `provenance` varchar(150) DEFAULT NULL,
  `undergraduate_degree` varchar(150) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT 'default_avatar.png',
  `password_hash` varchar(255) NOT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `role` varchar(255) DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_users`
--

INSERT INTO `tbl_users` (`id`, `user_type`, `status`, `first_name`, `last_name`, `email`, `phone`, `document_id`, `address`, `provenance`, `undergraduate_degree`, `avatar`, `password_hash`, `last_login_at`, `created_at`, `updated_at`, `role`) VALUES
(1, 'INTERNAL', 'ACTIVE', 'ADMIN', 'GENERAL', 'admin@diplomatic.local', NULL, NULL, NULL, NULL, NULL, 'default_avatar.png', '$2y$10$00VC/qpQDbIDycCmDk0.Au4AEP7EfpgQ8J451Zs9YOvzdH4Vpty5K', NULL, '2026-02-07 17:08:04', '2026-02-08 01:48:48', 'ADMIN'),
(2, 'INTERNAL', 'ACTIVE', 'JORGE', 'PEREIRA', 'raizamileva@gmail.com', '', '14979523', 'Urb El Valle, Calle Principal, Casa Nro 23, Cabudare, Jose Gregorio Bastidas, Palavecino', 'Cabudare', 'Ingenieria', 'usr_1770810835.png', '$2y$10$Pa.672SkyV7tzG8yfpeI5uInUoOt7T/YKWdtVa.dfqqsKjjLFn8pG', NULL, '2026-02-08 11:35:40', '2026-02-11 07:54:16', 'ADMIN'),
(3, 'INTERNAL', 'ACTIVE', 'Luis', 'Olivares', 'jorgeluispereiraolivares@gmail.com', '', '149795231', '10773 NW 58th ST, Doral FL 33178', 'Barquisimeto', 'Ingenieria', 'usr_1770810903.png', '$2y$10$HI6YEnRVg21tKiaoj/1uaeFZaS4ruNvjM1SYcXfnbItRhpBMZXXtm', NULL, '2026-02-11 07:54:54', '2026-02-11 08:04:39', 'ADMIN'),
(4, 'INTERNAL', 'ACTIVE', 'Homer', 'Pereira', 'ddrgomes@gmail.com', '', '13567117', 'Urb Club Hipico Las Trinitarias, Res La Trinitaria Piso 4 Apartament 44', 'Barquisimeto', 'Ingenieria Quimica', 'usr_1770811774.png', '$2y$10$3BwNQJ7Ym4CqChkOZ1bTe.NfrBfO89YNFsjtdE5Q.EAqN3ab027p6', NULL, '2026-02-11 08:05:52', '2026-02-11 08:09:34', 'ADMIN');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_user_roles`
--

CREATE TABLE `tbl_user_roles` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `role_id` int(10) UNSIGNED NOT NULL,
  `status` enum('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  `assigned_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

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
-- Indices de la tabla `tbl_user_roles`
--
ALTER TABLE `tbl_user_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_role` (`user_id`,`role_id`),
  ADD KEY `fk_user_roles_role` (`role_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

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
-- AUTO_INCREMENT de la tabla `tbl_roles`
--
ALTER TABLE `tbl_roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `tbl_user_roles`
--
ALTER TABLE `tbl_user_roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

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

--
-- Filtros para la tabla `tbl_user_roles`
--
ALTER TABLE `tbl_user_roles`
  ADD CONSTRAINT `fk_user_roles_role` FOREIGN KEY (`role_id`) REFERENCES `tbl_roles` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user_roles_user` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
