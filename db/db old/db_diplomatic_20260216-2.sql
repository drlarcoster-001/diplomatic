-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 17-02-2026 a las 03:09:57
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
-- Estructura de tabla para la tabla `tbl_audit_logs`
--

CREATE TABLE `tbl_audit_logs` (
  `log_id` bigint(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `device` varchar(50) DEFAULT NULL,
  `os` varchar(50) DEFAULT NULL,
  `module` varchar(100) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `entity` varchar(100) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `db_action` varchar(20) DEFAULT NULL,
  `data_before` longtext DEFAULT NULL,
  `data_after` longtext DEFAULT NULL,
  `endpoint` varchar(255) DEFAULT NULL,
  `http_method` varchar(10) DEFAULT NULL,
  `status_code` int(5) DEFAULT NULL,
  `response_time_ms` int(11) DEFAULT NULL,
  `event_type` enum('NORMAL','WARNING','ERROR','SECURITY') DEFAULT 'NORMAL',
  `is_failed_attempt` tinyint(1) DEFAULT 0,
  `is_critical` tinyint(1) DEFAULT 0,
  `request_id` varchar(100) DEFAULT NULL,
  `event_origin` varchar(50) DEFAULT 'BACKEND',
  `environment` varchar(20) DEFAULT 'PROD',
  `app_version` varchar(20) DEFAULT '1.0.0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tbl_audit_logs`
--

INSERT INTO `tbl_audit_logs` (`log_id`, `created_at`, `user_id`, `session_id`, `ip_address`, `user_agent`, `device`, `os`, `module`, `action`, `description`, `entity`, `entity_id`, `db_action`, `data_before`, `data_after`, `endpoint`, `http_method`, `status_code`, `response_time_ms`, `event_type`, `is_failed_attempt`, `is_critical`, `request_id`, `event_origin`, `environment`, `app_version`) VALUES
(1, '2026-02-13 11:15:25', 1, '3nrvio4fbv1qarvrgccv59bght', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS_COMPANY', 'ACCESS', 'User accessed the institutional identity profile', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/empresa', 'GET', NULL, NULL, 'NORMAL', 0, 0, '34fc62483c6676fe', 'BACKEND', 'DEV', '1.0.0'),
(2, '2026-02-13 11:15:27', 1, '3nrvio4fbv1qarvrgccv59bght', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS_COMPANY', 'UPDATE', 'Updated legal and commercial identity information', 'tbl_company_settings', 1, 'UPDATE', '{\"id\":1,\"nombre_legal\":\"Diplomados\",\"nombre_comercial\":\"Plataforma Diplomados\",\"id_fiscal\":\"G1212121212\",\"direccion\":\"10773 NW 58th ST, Doral FL 33178\",\"telefono\":\"+584145365380\",\"email\":\"admin@plataformadiplomados.com\",\"sitio_web\":\"www.plataformadiplomados.com\",\"representante\":\"JORGE PEREIRA\",\"cargo_rep\":\"PROGRAMADOR\",\"tel_rep\":\"+584145042125\",\"created_at\":\"2026-02-12 11:54:22\",\"updated_at\":\"2026-02-12 11:58:10\"}', '{\"nombre_legal\":\"Diplomados\",\"nombre_comercial\":\"Plataforma Diplomados\",\"id_fiscal\":\"G1212121212\",\"direccion\":\"10773 NW 58th ST, Doral FL 33178\",\"telefono\":\"+584145365380\",\"email\":\"admin@plataformadiplomados.com\",\"sitio_web\":\"www.plataformadiplomados.com\",\"representante\":\"JORGE PEREIRA\",\"cargo_rep\":\"PROGRAMADOR\",\"tel_rep\":\"+584145042125\"}', '/diplomatic/public/settings/empresa/save', 'POST', NULL, NULL, 'WARNING', 0, 0, 'f25953b12108244d', 'BACKEND', 'DEV', '1.0.0'),
(3, '2026-02-13 11:22:49', 6, '8suk4a6q1eh8c8mag3beus4emv', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN', 'User session started successfully for: Luis Olivares', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, 'NORMAL', 0, 0, '4ff9ca2a63821eeb', 'BACKEND', 'DEV', '1.0.0'),
(4, '2026-02-13 11:23:20', 1, 'mo9k8kh2pgppvc14lvu228gdfl', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN', 'User session started successfully for: ADMIN GENERAL', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, 'NORMAL', 0, 0, '6d5f1b34d3a29979', 'BACKEND', 'DEV', '1.0.0'),
(5, '2026-02-13 11:25:11', 1, 'mo9k8kh2pgppvc14lvu228gdfl', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS_COMPANY', 'ACCESS', 'User accessed the institutional identity profile', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/empresa', 'GET', NULL, NULL, 'NORMAL', 0, 0, 'e6b5666408414aea', 'BACKEND', 'DEV', '1.0.0'),
(6, '2026-02-13 11:25:13', 1, 'mo9k8kh2pgppvc14lvu228gdfl', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS_COMPANY', 'UPDATE', 'Updated legal and commercial identity information', 'tbl_company_settings', 1, 'UPDATE', '{\"id\":1,\"nombre_legal\":\"Diplomados\",\"nombre_comercial\":\"Plataforma Diplomados\",\"id_fiscal\":\"G1212121212\",\"direccion\":\"10773 NW 58th ST, Doral FL 33178\",\"telefono\":\"+584145365380\",\"email\":\"admin@plataformadiplomados.com\",\"sitio_web\":\"www.plataformadiplomados.com\",\"representante\":\"JORGE PEREIRA\",\"cargo_rep\":\"PROGRAMADOR\",\"tel_rep\":\"+584145042125\",\"created_at\":\"2026-02-12 11:54:22\",\"updated_at\":\"2026-02-12 11:58:10\"}', '{\"nombre_legal\":\"Diplomados\",\"nombre_comercial\":\"Plataforma Diplomados\",\"id_fiscal\":\"G1212121212\",\"direccion\":\"10773 NW 58th ST, Doral FL 33178\",\"telefono\":\"+584145365380\",\"email\":\"admin@plataformadiplomados.com\",\"sitio_web\":\"www.plataformadiplomados.com\",\"representante\":\"JORGE PEREIRA\",\"cargo_rep\":\"PROGRAMADOR\",\"tel_rep\":\"+584145042125\"}', '/diplomatic/public/settings/empresa/save', 'POST', NULL, NULL, 'WARNING', 0, 0, '2c92bb03cca95132', 'BACKEND', 'DEV', '1.0.0'),
(7, '2026-02-13 11:26:44', 1, 'mo9k8kh2pgppvc14lvu228gdfl', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS_COMPANY', 'ACCESS', 'User accessed the institutional identity profile', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/empresa', 'GET', NULL, NULL, 'NORMAL', 0, 0, '216d0051e6bd91fd', 'BACKEND', 'DEV', '1.0.0'),
(8, '2026-02-13 11:26:46', 1, 'mo9k8kh2pgppvc14lvu228gdfl', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS_COMPANY', 'UPDATE', 'Updated legal and commercial identity information', 'tbl_company_settings', 1, 'UPDATE', '{\"id\":1,\"nombre_legal\":\"Diplomados\",\"nombre_comercial\":\"Plataforma Diplomados\",\"id_fiscal\":\"G1212121212\",\"direccion\":\"10773 NW 58th ST, Doral FL 33178\",\"telefono\":\"+584145365380\",\"email\":\"admin@plataformadiplomados.com\",\"sitio_web\":\"www.plataformadiplomados.com\",\"representante\":\"JORGE PEREIRA\",\"cargo_rep\":\"PROGRAMADOR\",\"tel_rep\":\"+584145042125\",\"created_at\":\"2026-02-12 11:54:22\",\"updated_at\":\"2026-02-12 11:58:10\"}', '{\"nombre_legal\":\"Diplomados\",\"nombre_comercial\":\"Plataforma Diplomados\",\"id_fiscal\":\"G1212121212\",\"direccion\":\"10773 NW 58th ST, Doral FL 33178\",\"telefono\":\"+584145365380\",\"email\":\"admin@plataformadiplomados.com\",\"sitio_web\":\"www.plataformadiplomados.com\",\"representante\":\"JORGE PEREIRA\",\"cargo_rep\":\"PROGRAMADOR\",\"tel_rep\":\"+584145042125\"}', '/diplomatic/public/settings/empresa/save', 'POST', NULL, NULL, 'WARNING', 0, 0, '01b0ca3b92d54b50', 'BACKEND', 'DEV', '1.0.0'),
(9, '2026-02-13 11:29:02', 1, 'mo9k8kh2pgppvc14lvu228gdfl', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS_COMPANY', 'ACCESS', 'User accessed the institutional identity profile', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/empresa', 'GET', NULL, NULL, 'NORMAL', 0, 0, 'f3caf429b8bd7a30', 'BACKEND', 'DEV', '1.0.0'),
(10, '2026-02-13 11:29:05', 1, 'mo9k8kh2pgppvc14lvu228gdfl', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS_COMPANY', 'UPDATE', 'Updated legal and commercial identity information', 'tbl_company_settings', 1, 'UPDATE', '{\"id\":1,\"nombre_legal\":\"Diplomados\",\"nombre_comercial\":\"Plataforma Diplomados\",\"id_fiscal\":\"G1212121212\",\"direccion\":\"10773 NW 58th ST, Doral FL 33178\",\"telefono\":\"+584145365380\",\"email\":\"admin@plataformadiplomados.com\",\"sitio_web\":\"www.plataformadiplomados.com\",\"representante\":\"JORGE PEREIRA\",\"cargo_rep\":\"PROGRAMADOR\",\"tel_rep\":\"+584145042125\",\"created_at\":\"2026-02-12 11:54:22\",\"updated_at\":\"2026-02-12 11:58:10\"}', '{\"nombre_legal\":\"Diplomados\",\"nombre_comercial\":\"Plataforma Diplomados\",\"id_fiscal\":\"G1212121212\",\"direccion\":\"10773 NW 58th ST, Doral FL 33178\",\"telefono\":\"+584145365380\",\"email\":\"admin@plataformadiplomados.com\",\"sitio_web\":\"www.plataformadiplomados.com\",\"representante\":\"JORGE PEREIRA\",\"cargo_rep\":\"PROGRAMADOR\",\"tel_rep\":\"+584145042125\"}', '/diplomatic/public/settings/empresa/save', 'POST', NULL, NULL, 'WARNING', 0, 0, 'cbc93a2f13fc2999', 'BACKEND', 'DEV', '1.0.0'),
(11, '2026-02-13 11:31:32', 1, 'mo9k8kh2pgppvc14lvu228gdfl', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS_COMPANY', 'ACCESS', 'User accessed the institutional identity profile', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/empresa', 'GET', NULL, NULL, 'NORMAL', 0, 0, 'c99a54f3f6a2063b', 'BACKEND', 'DEV', '1.0.0'),
(12, '2026-02-13 11:31:34', 1, 'mo9k8kh2pgppvc14lvu228gdfl', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS_COMPANY', 'UPDATE', 'Updated legal and commercial identity information', 'tbl_company_settings', 1, 'UPDATE', '{\"id\":1,\"nombre_legal\":\"Diplomados\",\"nombre_comercial\":\"Plataforma Diplomados\",\"id_fiscal\":\"G1212121212\",\"direccion\":\"10773 NW 58th ST, Doral FL 33178\",\"telefono\":\"+584145365380\",\"email\":\"admin@plataformadiplomados.com\",\"sitio_web\":\"www.plataformadiplomados.com\",\"representante\":\"JORGE PEREIRA\",\"cargo_rep\":\"PROGRAMADOR\",\"tel_rep\":\"+584145042125\",\"created_at\":\"2026-02-12 11:54:22\",\"updated_at\":\"2026-02-12 11:58:10\"}', '{\"nombre_legal\":\"Diplomados\",\"nombre_comercial\":\"Plataforma Diplomados\",\"id_fiscal\":\"G1212121212\",\"direccion\":\"10773 NW 58th ST, Doral FL 33178\",\"telefono\":\"+584145365380\",\"email\":\"admin@plataformadiplomados.com\",\"sitio_web\":\"www.plataformadiplomados.com\",\"representante\":\"JORGE PEREIRA\",\"cargo_rep\":\"PROGRAMADOR\",\"tel_rep\":\"+584145042125\"}', '/diplomatic/public/settings/empresa/save', 'POST', NULL, NULL, 'WARNING', 0, 0, '9680efbb28f42070', 'BACKEND', 'DEV', '1.0.0'),
(13, '2026-02-13 11:35:06', 1, 'mo9k8kh2pgppvc14lvu228gdfl', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUDIT_SYSTEM', 'ACCESS', 'User opened the security audit console', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/eventos?search=&date_from=&date_to=', 'GET', NULL, NULL, 'NORMAL', 0, 0, '5150aefd7526206f', 'BACKEND', 'DEV', '1.0.0'),
(14, '2026-02-13 11:35:11', 1, 'mo9k8kh2pgppvc14lvu228gdfl', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUDIT_SYSTEM', 'ACCESS', 'User opened the security audit console', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/eventos?search=update&date_from=&date_to=', 'GET', NULL, NULL, 'NORMAL', 0, 0, 'da567750e97acb63', 'BACKEND', 'DEV', '1.0.0'),
(15, '2026-02-13 11:35:14', 1, 'mo9k8kh2pgppvc14lvu228gdfl', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUDIT_SYSTEM', 'ACCESS', 'User opened the security audit console', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/eventos?search=UPDATE&date_from=&date_to=', 'GET', NULL, NULL, 'NORMAL', 0, 0, 'f9ddfec0abfa0f91', 'BACKEND', 'DEV', '1.0.0'),
(16, '2026-02-13 11:39:41', 1, 'mo9k8kh2pgppvc14lvu228gdfl', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUDIT_SYSTEM', 'ACCESS', 'User opened the security audit console', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/eventos', 'GET', NULL, NULL, 'NORMAL', 0, 0, 'a99e358795780b78', 'BACKEND', 'DEV', '1.0.0'),
(17, '2026-02-13 11:39:44', 1, 'mo9k8kh2pgppvc14lvu228gdfl', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUDIT_SYSTEM', 'ACCESS', 'User opened the security audit console', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/eventos?search=UPDATE&date_from=&date_to=', 'GET', NULL, NULL, 'NORMAL', 0, 0, '7995ede910b1608a', 'BACKEND', 'DEV', '1.0.0'),
(18, '2026-02-13 11:43:45', 1, 'mo9k8kh2pgppvc14lvu228gdfl', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUDIT_SYSTEM', 'ACCESS', 'User opened the security audit console', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/eventos', 'GET', NULL, NULL, 'NORMAL', 0, 0, 'fa3ca712426fdf4e', 'BACKEND', 'DEV', '1.0.0'),
(19, '2026-02-13 16:32:01', 1, '5i8un53lan6peeblgu83fqerpc', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN', 'Inicio de sesión exitoso: ADMIN GENERAL', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, '', 0, 0, 'c3a0e4edf1ae6923', 'BACKEND', 'DEV', '1.0.0'),
(20, '2026-02-13 16:32:03', 1, '5i8un53lan6peeblgu83fqerpc', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS', 'ACCESS', 'Ingreso al menú de configuración general', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings', 'GET', NULL, NULL, 'NORMAL', 0, 0, '9f9702192b5f26b7', 'BACKEND', 'DEV', '1.0.0'),
(21, '2026-02-13 16:39:14', 1, '5i8un53lan6peeblgu83fqerpc', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS', 'ACCESS', 'Ingreso al menú de configuración general', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings', 'GET', NULL, NULL, 'NORMAL', 0, 0, '6d4e3ef5e7730925', 'BACKEND', 'DEV', '1.0.0'),
(22, '2026-02-13 16:39:15', 1, '5i8un53lan6peeblgu83fqerpc', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS_MAIL', 'ACCESS', 'Acceso a configuración SMTP', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/correo', 'GET', NULL, NULL, 'NORMAL', 0, 0, '2fa166d80a8ed0fd', 'BACKEND', 'DEV', '1.0.0'),
(23, '2026-02-13 16:39:17', 1, '5i8un53lan6peeblgu83fqerpc', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS_MAIL', 'UPDATE', 'Se actualizaron los parámetros de correo para: INSCRIPCION', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/save-correo', 'POST', NULL, NULL, 'WARNING', 0, 0, 'dcc1135ad76d4e4b', 'BACKEND', 'DEV', '1.0.0'),
(24, '2026-02-13 16:39:19', 1, '5i8un53lan6peeblgu83fqerpc', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS', 'ACCESS', 'Ingreso al menú de configuración general', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings', 'GET', NULL, NULL, 'NORMAL', 0, 0, '68cc42db16b30d95', 'BACKEND', 'DEV', '1.0.0'),
(25, '2026-02-13 16:39:20', 1, '5i8un53lan6peeblgu83fqerpc', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'WHATSAPP', 'ACCESS', 'El usuario accedió al panel de configuración de WhatsApp Manual', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/whatsapp', 'GET', NULL, NULL, 'NORMAL', 0, 0, '83e96166249a482c', 'BACKEND', 'DEV', '1.0.0'),
(26, '2026-02-13 16:39:24', 1, '5i8un53lan6peeblgu83fqerpc', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'WHATSAPP', 'UPDATE_TEMPLATE', 'Se actualizó la plantilla del evento: CONSTANCIA_ESTUDIO (Estado: Activo)', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/whatsapp/save-template', 'POST', NULL, NULL, 'WARNING', 0, 0, '1e4c6d9a3b508a7b', 'BACKEND', 'DEV', '1.0.0'),
(27, '2026-02-13 16:39:33', 1, '5i8un53lan6peeblgu83fqerpc', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'WHATSAPP', 'SEND_LOG', 'Notificación enviada a Juan Pérez (+584145042125) - Evento: CONSTANCIA_ESTUDIO', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/whatsapp/log', 'POST', NULL, NULL, '', 0, 0, '79c3dd66fc873479', 'BACKEND', 'DEV', '1.0.0'),
(28, '2026-02-13 16:39:35', 1, '5i8un53lan6peeblgu83fqerpc', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'WHATSAPP', 'ACCESS', 'El usuario accedió al panel de configuración de WhatsApp Manual', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/whatsapp', 'GET', NULL, NULL, 'NORMAL', 0, 0, 'd3160d9261dd9ec1', 'BACKEND', 'DEV', '1.0.0'),
(29, '2026-02-13 16:39:49', 1, '5i8un53lan6peeblgu83fqerpc', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS', 'ACCESS', 'Ingreso al menú de configuración general', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings', 'GET', NULL, NULL, 'NORMAL', 0, 0, '8d105f0e7cbb5fab', 'BACKEND', 'DEV', '1.0.0'),
(30, '2026-02-13 16:39:50', 1, '5i8un53lan6peeblgu83fqerpc', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS', 'ACCESS', 'Ingreso al menú de configuración general', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings', 'GET', NULL, NULL, 'NORMAL', 0, 0, '3956d2814639f62d', 'BACKEND', 'DEV', '1.0.0'),
(31, '2026-02-13 16:40:55', 1, '5i8un53lan6peeblgu83fqerpc', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS', 'ACCESS', 'Ingreso al menú de configuración general', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings', 'GET', NULL, NULL, 'NORMAL', 0, 0, '82bca4769af70a3e', 'BACKEND', 'DEV', '1.0.0'),
(32, '2026-02-13 16:40:59', 1, '5i8un53lan6peeblgu83fqerpc', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS', 'ACCESS', 'Ingreso al menú de configuración general', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings', 'GET', NULL, NULL, 'NORMAL', 0, 0, '181697a7035c5333', 'BACKEND', 'DEV', '1.0.0'),
(33, '2026-02-13 16:41:48', 1, '5i8un53lan6peeblgu83fqerpc', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'USUARIOS', 'UPDATE', 'Se actualizó al usuario: Homer Pereira (ID: 4)', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/users/save', 'POST', NULL, NULL, 'NORMAL', 0, 0, '52a0555a0bee5693', 'BACKEND', 'DEV', '1.0.0'),
(34, '2026-02-13 16:41:53', 1, '5i8un53lan6peeblgu83fqerpc', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGOUT', 'Cierre de sesión manual: ADMIN GENERAL', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/logout', 'GET', NULL, NULL, 'NORMAL', 0, 0, '2d6863f888e5ece7', 'BACKEND', 'DEV', '1.0.0'),
(35, '2026-02-13 16:42:00', 4, 'kkq0s7skeqvihhhf7nifs8bba5', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN', 'Inicio de sesión exitoso: Homer Pereira', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, '', 0, 0, 'c9741cfa0def07bc', 'BACKEND', 'DEV', '1.0.0'),
(36, '2026-02-13 16:42:03', 4, 'kkq0s7skeqvihhhf7nifs8bba5', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'PROFILE', 'ACCESS', 'El usuario accedió a gestionar su perfil personal', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/profile', 'GET', NULL, NULL, 'NORMAL', 0, 0, '34ab08650bebfc17', 'BACKEND', 'DEV', '1.0.0'),
(37, '2026-02-13 16:42:39', 4, 'kkq0s7skeqvihhhf7nifs8bba5', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGOUT', 'Cierre de sesión manual: Homer Pereira', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/logout', 'GET', NULL, NULL, 'NORMAL', 0, 0, 'a83aba3c29588a2c', 'BACKEND', 'DEV', '1.0.0'),
(38, '2026-02-13 16:42:51', NULL, '1pete7d3lmbbgmn9bu4tgfelmu', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN_FAILED', 'Intento fallido para el correo: admin@diplomatic.local. Motivo: Credenciales incorrectas o cuenta inactiva.', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, 'SECURITY', 0, 0, '9f43d112ff636f65', 'BACKEND', 'DEV', '1.0.0'),
(39, '2026-02-13 16:42:58', NULL, '1pete7d3lmbbgmn9bu4tgfelmu', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN_FAILED', 'Intento fallido para el correo: admin@diplomatic.local. Motivo: Credenciales incorrectas o cuenta inactiva.', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, 'SECURITY', 0, 0, 'b2ab9b699bd22425', 'BACKEND', 'DEV', '1.0.0'),
(40, '2026-02-13 16:43:07', 1, '1pete7d3lmbbgmn9bu4tgfelmu', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN', 'Inicio de sesión exitoso: ADMIN GENERAL', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, '', 0, 0, 'd5ab8926f545ac6a', 'BACKEND', 'DEV', '1.0.0'),
(41, '2026-02-13 16:45:15', 1, '1pete7d3lmbbgmn9bu4tgfelmu', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGOUT', 'Cierre de sesión manual: ADMIN GENERAL', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/logout', 'GET', NULL, NULL, 'NORMAL', 0, 0, '1f2ad912d701e53f', 'BACKEND', 'DEV', '1.0.0'),
(42, '2026-02-13 16:45:22', 4, 'qsap6btq7fschqppv3s99tjcff', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN', 'Inicio de sesión exitoso: Homer Pereira', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, '', 0, 0, 'be52a9a2376a954b', 'BACKEND', 'DEV', '1.0.0'),
(43, '2026-02-13 16:45:25', 4, 'qsap6btq7fschqppv3s99tjcff', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'PROFILE', 'ACCESS', 'El usuario accedió a gestionar su perfil personal', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/profile', 'GET', NULL, NULL, 'NORMAL', 0, 0, '0d12b4d2b94292b8', 'BACKEND', 'DEV', '1.0.0'),
(44, '2026-02-13 16:58:16', 4, 'qsap6btq7fschqppv3s99tjcff', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'PROFILE', 'ACCESS', 'Acceso al perfil personal para actualización de datos', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/profile', 'GET', NULL, NULL, 'NORMAL', 0, 0, 'e26c256214cbf5e0', 'BACKEND', 'DEV', '1.0.0'),
(45, '2026-02-13 16:58:20', 4, 'qsap6btq7fschqppv3s99tjcff', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGOUT', 'Cierre de sesión manual: Homer Pereira', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/logout', 'GET', NULL, NULL, 'NORMAL', 0, 0, 'fdcf5f7fe85d1e2c', 'BACKEND', 'DEV', '1.0.0'),
(46, '2026-02-13 16:58:33', 1, 'ig6hmhj8lrkn5dgd0g453tvp43', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN', 'Inicio de sesión exitoso: ADMIN GENERAL', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, '', 0, 0, '2127c01bbafc94f9', 'BACKEND', 'DEV', '1.0.0'),
(47, '2026-02-13 16:59:09', 1, 'ig6hmhj8lrkn5dgd0g453tvp43', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGOUT', 'Cierre de sesión manual: ADMIN GENERAL', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/logout', 'GET', NULL, NULL, 'NORMAL', 0, 0, '7be28f6b55484e89', 'BACKEND', 'DEV', '1.0.0'),
(48, '2026-02-13 16:59:14', 4, '2lo5plibnm0a9n5is0udqal5ib', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN', 'Inicio de sesión exitoso: Homer Pereira', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, '', 0, 0, 'd572ec4587322189', 'BACKEND', 'DEV', '1.0.0'),
(49, '2026-02-13 16:59:17', 4, '2lo5plibnm0a9n5is0udqal5ib', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'PROFILE', 'ACCESS', 'Acceso al perfil personal para actualización de datos', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/profile', 'GET', NULL, NULL, 'NORMAL', 0, 0, '20544810d9049301', 'BACKEND', 'DEV', '1.0.0'),
(50, '2026-02-13 17:03:20', 4, '2lo5plibnm0a9n5is0udqal5ib', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGOUT', 'Cierre de sesión manual: Homer Pereira', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/logout', 'GET', NULL, NULL, 'NORMAL', 0, 0, 'a17a41ad109df3bd', 'BACKEND', 'DEV', '1.0.0'),
(51, '2026-02-13 17:03:27', 1, 'c1ce4ppdpsnuf1ojrpf6eqm9lt', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN', 'Inicio de sesión exitoso: ADMIN GENERAL', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, '', 0, 0, 'cebb0b9a433ad18a', 'BACKEND', 'DEV', '1.0.0'),
(52, '2026-02-13 17:03:38', 1, 'c1ce4ppdpsnuf1ojrpf6eqm9lt', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'USUARIOS', 'UPDATE', 'Se actualizó al usuario: Homer Pereira (ID: 4)', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/users/save', 'POST', NULL, NULL, 'NORMAL', 0, 0, '79c1c1d0161d380b', 'BACKEND', 'DEV', '1.0.0'),
(53, '2026-02-13 17:03:41', 1, 'c1ce4ppdpsnuf1ojrpf6eqm9lt', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGOUT', 'Cierre de sesión manual: ADMIN GENERAL', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/logout', 'GET', NULL, NULL, 'NORMAL', 0, 0, 'bb0a6954fc38f434', 'BACKEND', 'DEV', '1.0.0'),
(54, '2026-02-13 17:03:47', 4, 'bnu4b12g8n49qg8cb3001h5o0v', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN', 'Inicio de sesión exitoso: Homer Pereira', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, '', 0, 0, '3b8970895832b3d2', 'BACKEND', 'DEV', '1.0.0'),
(55, '2026-02-13 17:03:55', 4, 'bnu4b12g8n49qg8cb3001h5o0v', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'PROFILE', 'UPDATE', 'Usuario actualizó sus datos personales y foto de perfil', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/profile/update', 'POST', NULL, NULL, '', 0, 0, '7cd58faf9108dc7a', 'BACKEND', 'DEV', '1.0.0'),
(56, '2026-02-13 17:03:59', 4, 'bnu4b12g8n49qg8cb3001h5o0v', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGOUT', 'Cierre de sesión manual: Homer Pereira', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/logout', 'GET', NULL, NULL, 'NORMAL', 0, 0, '92800f549d048a98', 'BACKEND', 'DEV', '1.0.0'),
(57, '2026-02-13 17:04:14', NULL, 'b7vkksmoeejdocob8auig1sntp', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN_FAILED', 'Intento fallido para el correo: admin@diplomatic.local. Motivo: Credenciales incorrectas o cuenta inactiva.', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, 'SECURITY', 0, 0, 'd3823f12c9c1134c', 'BACKEND', 'DEV', '1.0.0'),
(58, '2026-02-13 17:04:20', 1, 'b7vkksmoeejdocob8auig1sntp', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN', 'Inicio de sesión exitoso: ADMIN GENERAL', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, '', 0, 0, '141c99079eb4fcdd', 'BACKEND', 'DEV', '1.0.0'),
(59, '2026-02-13 17:09:06', 1, 'b7vkksmoeejdocob8auig1sntp', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGOUT', 'Cierre de sesión manual: ADMIN GENERAL', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/logout', 'GET', NULL, NULL, 'NORMAL', 0, 0, '588f4698ff2bbf96', 'BACKEND', 'DEV', '1.0.0'),
(60, '2026-02-13 17:09:12', 4, '7mq7s55oibl14rglqie585a0h9', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN', 'Inicio de sesión exitoso: Homer Pereira', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, '', 0, 0, 'a9d81d10445a63af', 'BACKEND', 'DEV', '1.0.0'),
(61, '2026-02-13 17:09:18', 4, '7mq7s55oibl14rglqie585a0h9', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'PROFILE', 'UPDATE', 'Usuario actualizó sus datos y foto de perfil', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/profile/update', 'POST', NULL, NULL, '', 0, 0, '8dd9a6a5d4fadedc', 'BACKEND', 'DEV', '1.0.0'),
(62, '2026-02-13 17:09:20', 4, '7mq7s55oibl14rglqie585a0h9', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'PROFILE', 'UPDATE', 'Usuario actualizó sus datos y foto de perfil', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/profile/update', 'POST', NULL, NULL, '', 0, 0, '81e06a46a95963bc', 'BACKEND', 'DEV', '1.0.0'),
(63, '2026-02-13 17:09:23', 4, '7mq7s55oibl14rglqie585a0h9', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'PROFILE', 'UPDATE', 'Usuario actualizó sus datos y foto de perfil', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/profile/update', 'POST', NULL, NULL, '', 0, 0, '522a9f4cd39a3e5c', 'BACKEND', 'DEV', '1.0.0'),
(64, '2026-02-13 17:11:27', 4, '7mq7s55oibl14rglqie585a0h9', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'PROFILE', 'UPDATE', 'El usuario actualizó sus datos profesionales y foto de perfil', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/profile/update', 'POST', NULL, NULL, '', 0, 0, 'cc54270c6b6820c7', 'BACKEND', 'DEV', '1.0.0'),
(65, '2026-02-13 17:11:38', 4, '7mq7s55oibl14rglqie585a0h9', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGOUT', 'Cierre de sesión manual: Homer Pereira', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/logout', 'GET', NULL, NULL, 'NORMAL', 0, 0, 'c2d2c2473c2928bd', 'BACKEND', 'DEV', '1.0.0'),
(66, '2026-02-13 17:11:45', NULL, 'orjh62pnod07rk0nsvhk5linmq', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN_FAILED', 'Intento fallido para el correo: admin@diplomatic.local. Motivo: Credenciales incorrectas o cuenta inactiva.', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, 'SECURITY', 0, 0, '1d0816f1eca1dd0f', 'BACKEND', 'DEV', '1.0.0'),
(67, '2026-02-13 17:11:53', NULL, 'orjh62pnod07rk0nsvhk5linmq', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN_FAILED', 'Intento fallido para el correo: admin@diplomatic.local. Motivo: Credenciales incorrectas o cuenta inactiva.', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, 'SECURITY', 0, 0, 'ec790ca80c5e42ca', 'BACKEND', 'DEV', '1.0.0'),
(68, '2026-02-13 17:12:01', 1, 'orjh62pnod07rk0nsvhk5linmq', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN', 'Inicio de sesión exitoso: ADMIN GENERAL', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, '', 0, 0, '1bc41f8aa2e768d9', 'BACKEND', 'DEV', '1.0.0'),
(69, '2026-02-13 17:12:06', 1, 'orjh62pnod07rk0nsvhk5linmq', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS', 'ACCESS', 'Ingreso al menú de configuración general', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings', 'GET', NULL, NULL, 'NORMAL', 0, 0, '9e3c7d3542c1ba8b', 'BACKEND', 'DEV', '1.0.0'),
(70, '2026-02-13 17:12:25', 1, 'orjh62pnod07rk0nsvhk5linmq', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGOUT', 'Cierre de sesión manual: ADMIN GENERAL', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/logout', 'GET', NULL, NULL, 'NORMAL', 0, 0, '362e11c700f2d899', 'BACKEND', 'DEV', '1.0.0'),
(71, '2026-02-14 00:16:23', 1, 'gkrkoh1jhe4vqmi0h6kh5et8o4', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN', 'Inicio de sesión exitoso: ADMIN GENERAL', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, '', 0, 0, '598fd438089d3fd1', 'BACKEND', 'DEV', '1.0.0'),
(72, '2026-02-14 00:16:26', 1, 'gkrkoh1jhe4vqmi0h6kh5et8o4', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS', 'ACCESS', 'Ingreso al menú de configuración general', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings', 'GET', NULL, NULL, 'NORMAL', 0, 0, 'ae4d24406b65f3fd', 'BACKEND', 'DEV', '1.0.0'),
(73, '2026-02-14 00:16:40', 1, 'gkrkoh1jhe4vqmi0h6kh5et8o4', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS', 'ACCESS', 'Ingreso al menú de configuración general', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings', 'GET', NULL, NULL, 'NORMAL', 0, 0, '72217cb77f1a41d3', 'BACKEND', 'DEV', '1.0.0'),
(74, '2026-02-14 00:16:43', 1, 'gkrkoh1jhe4vqmi0h6kh5et8o4', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGOUT', 'Cierre de sesión manual: ADMIN GENERAL', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/logout', 'GET', NULL, NULL, 'NORMAL', 0, 0, '2ae9e4c1bfc64c88', 'BACKEND', 'DEV', '1.0.0'),
(75, '2026-02-14 00:16:51', NULL, 'o4p56t72qf2d191i710jot2vcv', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN_FAILED', 'Intento fallido para el correo: ddrgomes@gmail.com. Motivo: Credenciales incorrectas o cuenta inactiva.', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, 'SECURITY', 0, 0, 'fd0ea14ff79d9366', 'BACKEND', 'DEV', '1.0.0'),
(76, '2026-02-14 00:16:58', 4, 'o4p56t72qf2d191i710jot2vcv', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN', 'Inicio de sesión exitoso: Homer Pereira', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, '', 0, 0, 'fbc5a3baad6bb79d', 'BACKEND', 'DEV', '1.0.0'),
(77, '2026-02-14 00:17:05', 4, 'o4p56t72qf2d191i710jot2vcv', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'PROFILE', 'UPDATE', 'El usuario actualizó sus datos profesionales y foto de perfil', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/profile/update', 'POST', NULL, NULL, '', 0, 0, '11e286ace19ef366', 'BACKEND', 'DEV', '1.0.0'),
(78, '2026-02-14 00:17:13', 4, 'o4p56t72qf2d191i710jot2vcv', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGOUT', 'Cierre de sesión manual: Homer Pereira', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/logout', 'GET', NULL, NULL, 'NORMAL', 0, 0, 'ea726e471337faee', 'BACKEND', 'DEV', '1.0.0'),
(79, '2026-02-14 00:17:23', 1, 'jgjiglk190nv9sfhcfioodvdr4', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN', 'Inicio de sesión exitoso: ADMIN GENERAL', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, '', 0, 0, '28f3cd86155370c3', 'BACKEND', 'DEV', '1.0.0'),
(80, '2026-02-14 00:17:33', 1, 'jgjiglk190nv9sfhcfioodvdr4', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'USUARIOS', 'UPDATE', 'Se actualizó al usuario: Homer Pereira (ID: 4)', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/users/save', 'POST', NULL, NULL, 'NORMAL', 0, 0, 'e15ffbb3629f001a', 'BACKEND', 'DEV', '1.0.0'),
(81, '2026-02-14 00:17:37', 1, 'jgjiglk190nv9sfhcfioodvdr4', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS', 'ACCESS', 'Ingreso al menú de configuración general', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings', 'GET', NULL, NULL, 'NORMAL', 0, 0, '828403f30c9964b8', 'BACKEND', 'DEV', '1.0.0'),
(82, '2026-02-14 00:37:46', 1, 'jgjiglk190nv9sfhcfioodvdr4', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS', 'ACCESS', 'Ingreso al menú de configuración general', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings', 'GET', NULL, NULL, 'NORMAL', 0, 0, '98b2821fae38db56', 'BACKEND', 'DEV', '1.0.0'),
(83, '2026-02-14 00:38:09', 1, 'jgjiglk190nv9sfhcfioodvdr4', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS', 'ACCESS', 'Ingreso al menú de configuración general', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings', 'GET', NULL, NULL, 'NORMAL', 0, 0, 'de5a847ef3ad68f6', 'BACKEND', 'DEV', '1.0.0'),
(84, '2026-02-14 00:38:25', 1, 'jgjiglk190nv9sfhcfioodvdr4', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS', 'ACCESS', 'Ingreso al menú de configuración general', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings', 'GET', NULL, NULL, 'NORMAL', 0, 0, 'f840af3153be21a7', 'BACKEND', 'DEV', '1.0.0'),
(85, '2026-02-14 00:45:22', 1, 'jgjiglk190nv9sfhcfioodvdr4', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGOUT', 'Cierre de sesión manual: ADMIN GENERAL', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/logout', 'GET', NULL, NULL, 'NORMAL', 0, 0, '1630af320b603d45', 'BACKEND', 'DEV', '1.0.0'),
(86, '2026-02-14 00:45:30', NULL, 'i8e1u3aque7e53qidf2k4o8pt8', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN_FAILED', 'Intento fallido para el correo: ddrgomes@gmail.com. Motivo: Credenciales incorrectas o cuenta inactiva.', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, 'SECURITY', 0, 0, '0cffb1879bc28801', 'BACKEND', 'DEV', '1.0.0'),
(87, '2026-02-14 00:45:39', 4, 'i8e1u3aque7e53qidf2k4o8pt8', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN', 'Inicio de sesión exitoso: Homer Pereira', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, '', 0, 0, 'aac464503401ebcb', 'BACKEND', 'DEV', '1.0.0'),
(88, '2026-02-14 00:47:15', 4, 'i8e1u3aque7e53qidf2k4o8pt8', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGOUT', 'Cierre de sesión manual: Homer Pereira', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/logout', 'GET', NULL, NULL, 'NORMAL', 0, 0, '7b579786d7ff13c3', 'BACKEND', 'DEV', '1.0.0'),
(89, '2026-02-14 01:10:48', 4, 'im3no5gkpg3s5jb9o3fkfutan1', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN', 'Inicio de sesión exitoso: Homer Pereira', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, '', 0, 0, '9322a21a19c72e74', 'BACKEND', 'DEV', '1.0.0'),
(90, '2026-02-14 01:13:29', 4, 'im3no5gkpg3s5jb9o3fkfutan1', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'PROFILE_SECURITY', 'CHANGE_PASSWORD_SUCCESS', 'Contraseña actualizada tras validar identidad previa', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/profile/change-password', 'POST', NULL, NULL, 'WARNING', 0, 0, 'f8392a4605b3cf84', 'BACKEND', 'DEV', '1.0.0'),
(91, '2026-02-14 01:13:33', 4, 'im3no5gkpg3s5jb9o3fkfutan1', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGOUT', 'Cierre de sesión manual: Homer Pereira', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/logout', 'GET', NULL, NULL, 'NORMAL', 0, 0, 'ec97361b84c20647', 'BACKEND', 'DEV', '1.0.0'),
(92, '2026-02-14 01:13:39', NULL, 'vgeti331n1o4k9vd0dggplgihp', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN_FAILED', 'Intento fallido para el correo: ddrgomes@gmail.com. Motivo: Credenciales incorrectas o cuenta inactiva.', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, 'SECURITY', 0, 0, '410c907c8101bf2e', 'BACKEND', 'DEV', '1.0.0'),
(93, '2026-02-14 01:13:46', NULL, 'vgeti331n1o4k9vd0dggplgihp', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN_FAILED', 'Intento fallido para el correo: ddrgomes@gmail.com. Motivo: Credenciales incorrectas o cuenta inactiva.', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, 'SECURITY', 0, 0, '991a9bb40c6ad9b7', 'BACKEND', 'DEV', '1.0.0'),
(94, '2026-02-14 01:13:58', NULL, 'vgeti331n1o4k9vd0dggplgihp', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN_FAILED', 'Intento fallido para el correo: ddrgomes@gmail.com. Motivo: Credenciales incorrectas o cuenta inactiva.', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, 'SECURITY', 0, 0, 'b4db4ded94e97950', 'BACKEND', 'DEV', '1.0.0'),
(95, '2026-02-14 01:14:06', NULL, 'vgeti331n1o4k9vd0dggplgihp', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN_FAILED', 'Intento fallido para el correo: ddrgomes@gmail.com. Motivo: Credenciales incorrectas o cuenta inactiva.', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, 'SECURITY', 0, 0, 'c60775408db9929e', 'BACKEND', 'DEV', '1.0.0'),
(96, '2026-02-14 01:14:15', 1, 'vgeti331n1o4k9vd0dggplgihp', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN', 'Inicio de sesión exitoso: ADMIN GENERAL', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, '', 0, 0, '32b903e981fa37ff', 'BACKEND', 'DEV', '1.0.0'),
(97, '2026-02-14 01:14:23', 1, 'vgeti331n1o4k9vd0dggplgihp', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS', 'ACCESS', 'Ingreso al menú de configuración general', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings', 'GET', NULL, NULL, 'NORMAL', 0, 0, '0975b0980fc395d0', 'BACKEND', 'DEV', '1.0.0'),
(98, '2026-02-14 01:17:30', 1, 'vgeti331n1o4k9vd0dggplgihp', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGOUT', 'Cierre de sesión manual: ADMIN GENERAL', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/logout', 'GET', NULL, NULL, 'NORMAL', 0, 0, '9643c433b8e2082a', 'BACKEND', 'DEV', '1.0.0'),
(99, '2026-02-14 01:17:36', NULL, 'rd1jd2etimur7vuqbh6egej0e7', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN_FAILED', 'Intento fallido para el correo: ddrgomes@gmail.com. Motivo: Credenciales incorrectas o cuenta inactiva.', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, 'SECURITY', 0, 0, 'c405a995a7d88fcc', 'BACKEND', 'DEV', '1.0.0'),
(100, '2026-02-14 01:17:44', 4, 'rd1jd2etimur7vuqbh6egej0e7', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN', 'Inicio de sesión exitoso: Homer Pereira', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, '', 0, 0, 'f559f93cbeca37c6', 'BACKEND', 'DEV', '1.0.0'),
(101, '2026-02-14 01:18:03', 4, 'rd1jd2etimur7vuqbh6egej0e7', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGOUT', 'Cierre de sesión manual: Homer Pereira', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/logout', 'GET', NULL, NULL, 'NORMAL', 0, 0, '4db56b32aad52bb9', 'BACKEND', 'DEV', '1.0.0'),
(102, '2026-02-14 01:18:10', 1, '9gn71t1mg6me4l7vv4bg0f5ilr', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN', 'Inicio de sesión exitoso: ADMIN GENERAL', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, '', 0, 0, '26e68e2fc96f8b9b', 'BACKEND', 'DEV', '1.0.0'),
(103, '2026-02-14 01:18:21', 1, '9gn71t1mg6me4l7vv4bg0f5ilr', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGOUT', 'Cierre de sesión manual: ADMIN GENERAL', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/logout', 'GET', NULL, NULL, 'NORMAL', 0, 0, '596897aff27011aa', 'BACKEND', 'DEV', '1.0.0'),
(104, '2026-02-14 01:18:30', NULL, 'q1s286dvgvi940fa13pnhv4np3', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN_FAILED', 'Intento fallido para el correo: ddrgomes@gmail.com. Motivo: Credenciales incorrectas o cuenta inactiva.', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, 'SECURITY', 0, 0, '410b4f77f3467fe0', 'BACKEND', 'DEV', '1.0.0');
INSERT INTO `tbl_audit_logs` (`log_id`, `created_at`, `user_id`, `session_id`, `ip_address`, `user_agent`, `device`, `os`, `module`, `action`, `description`, `entity`, `entity_id`, `db_action`, `data_before`, `data_after`, `endpoint`, `http_method`, `status_code`, `response_time_ms`, `event_type`, `is_failed_attempt`, `is_critical`, `request_id`, `event_origin`, `environment`, `app_version`) VALUES
(105, '2026-02-14 01:18:39', 4, 'q1s286dvgvi940fa13pnhv4np3', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN', 'Inicio de sesión exitoso: Homer Pereira', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, '', 0, 0, '020608d56dd3a737', 'BACKEND', 'DEV', '1.0.0'),
(106, '2026-02-14 01:19:01', 4, 'q1s286dvgvi940fa13pnhv4np3', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'PROFILE_SECURITY', 'CHANGE_PASSWORD_SUCCESS', 'Contraseña actualizada tras validar identidad previa', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/profile/change-password', 'POST', NULL, NULL, 'WARNING', 0, 0, '4aca4bd6e3c93799', 'BACKEND', 'DEV', '1.0.0'),
(107, '2026-02-14 01:19:05', 4, 'q1s286dvgvi940fa13pnhv4np3', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGOUT', 'Cierre de sesión manual: Homer Pereira', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/logout', 'GET', NULL, NULL, 'NORMAL', 0, 0, '59104ac71c15921c', 'BACKEND', 'DEV', '1.0.0'),
(108, '2026-02-14 01:19:11', 4, '5hrmvk3fgot1892oq7emg7b7fs', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN', 'Inicio de sesión exitoso: Homer Pereira', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, '', 0, 0, 'f80a858622e83d31', 'BACKEND', 'DEV', '1.0.0'),
(109, '2026-02-14 01:19:21', 4, '5hrmvk3fgot1892oq7emg7b7fs', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGOUT', 'Cierre de sesión manual: Homer Pereira', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/logout', 'GET', NULL, NULL, 'NORMAL', 0, 0, '10cbaf3fb4c891a0', 'BACKEND', 'DEV', '1.0.0'),
(110, '2026-02-16 17:18:53', 1, 'n1geod3uqorlger2u3544um40l', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS_MAIL', 'UPDATE', 'Se actualizaron los parámetros de correo para: INSCRIPCION', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/save-correo', 'POST', NULL, NULL, 'WARNING', 0, 0, 'e762a0798e6c7cd3', 'BACKEND', 'DEV', '1.0.0'),
(111, '2026-02-16 17:26:11', 1, 'n1geod3uqorlger2u3544um40l', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS_MAIL', 'ACCESS', 'Acceso a configuración SMTP', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/correo', 'GET', NULL, NULL, 'NORMAL', 0, 0, 'fb1753d222d98066', 'BACKEND', 'DEV', '1.0.0'),
(112, '2026-02-16 17:28:50', 1, 'n1geod3uqorlger2u3544um40l', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS_MAIL', 'ACCESS', 'Acceso a configuración SMTP', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/correo', 'GET', NULL, NULL, 'NORMAL', 0, 0, '7fcd15c0d8d18c9e', 'BACKEND', 'DEV', '1.0.0'),
(113, '2026-02-16 17:46:50', 1, 'n1geod3uqorlger2u3544um40l', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS', 'ACCESS', 'Ingreso al menú de configuración general', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings', 'GET', NULL, NULL, 'NORMAL', 0, 0, 'f16a1647df2afd64', 'BACKEND', 'DEV', '1.0.0'),
(114, '2026-02-16 17:46:51', 1, 'n1geod3uqorlger2u3544um40l', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS_MAIL', 'ACCESS', 'Acceso a configuración SMTP', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/correo', 'GET', NULL, NULL, 'NORMAL', 0, 0, '8b45bc93db4cca92', 'BACKEND', 'DEV', '1.0.0'),
(115, '2026-02-16 17:46:53', 1, 'n1geod3uqorlger2u3544um40l', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS_MAIL', 'ACCESS', 'Acceso a configuración SMTP', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/correo', 'GET', NULL, NULL, 'NORMAL', 0, 0, '4cc64a988a191892', 'BACKEND', 'DEV', '1.0.0'),
(116, '2026-02-16 17:57:27', 1, 'n1geod3uqorlger2u3544um40l', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS_MAIL', 'ACCESS', 'Acceso a configuración SMTP profesional', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/correo', 'GET', NULL, NULL, 'NORMAL', 0, 0, 'bc58b448d001507c', 'BACKEND', 'DEV', '1.0.0'),
(117, '2026-02-16 18:01:07', 1, 'n1geod3uqorlger2u3544um40l', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS_MAIL', 'ACCESS', 'Acceso a configuración SMTP profesional', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/correo', 'GET', NULL, NULL, 'NORMAL', 0, 0, '494118ed6ea75349', 'BACKEND', 'DEV', '1.0.0'),
(118, '2026-02-16 18:01:11', 1, 'n1geod3uqorlger2u3544um40l', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS_MAIL', 'UPDATE', 'Parámetros actualizados: INSCRIPCION', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/save-correo', 'POST', NULL, NULL, 'WARNING', 0, 0, 'd2fca398fe4541fe', 'BACKEND', 'DEV', '1.0.0'),
(119, '2026-02-16 18:02:36', 1, 'n1geod3uqorlger2u3544um40l', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS_MAIL', 'UPDATE', 'Parámetros actualizados: INSCRIPCION', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/save-correo', 'POST', NULL, NULL, 'WARNING', 0, 0, 'e17d5ce972b179df', 'BACKEND', 'DEV', '1.0.0'),
(120, '2026-02-16 18:06:01', 1, 'n1geod3uqorlger2u3544um40l', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGOUT', 'Cierre de sesión manual: ADMIN GENERAL', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/logout', 'GET', NULL, NULL, 'NORMAL', 0, 0, 'e3d3e13f5ccc7613', 'BACKEND', 'DEV', '1.0.0'),
(121, '2026-02-16 18:06:06', 1, '6u186khddn7pavke9hsp676unf', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN', 'Inicio de sesión exitoso: ADMIN GENERAL', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, '', 0, 0, '6c5c4bdca4a6d178', 'BACKEND', 'DEV', '1.0.0'),
(122, '2026-02-16 18:09:49', 1, '6u186khddn7pavke9hsp676unf', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS', 'ACCESS', 'Ingreso al menú de configuración general', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings', 'GET', NULL, NULL, 'NORMAL', 0, 0, '91d5ad83055d22ca', 'BACKEND', 'DEV', '1.0.0'),
(123, '2026-02-16 18:09:50', 1, '6u186khddn7pavke9hsp676unf', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS_MAIL', 'ACCESS', 'Acceso a configuración SMTP', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/correo', 'GET', NULL, NULL, 'NORMAL', 0, 0, 'ca205759f02e61d0', 'BACKEND', 'DEV', '1.0.0'),
(124, '2026-02-16 18:09:53', 1, '6u186khddn7pavke9hsp676unf', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS_MAIL', 'UPDATE', 'Se actualizaron los parámetros de correo para: INSCRIPCION', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/save-correo', 'POST', NULL, NULL, 'WARNING', 0, 0, '4caac74063cb077e', 'BACKEND', 'DEV', '1.0.0'),
(125, '2026-02-16 18:12:04', 1, '6u186khddn7pavke9hsp676unf', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS_MAIL', 'ACCESS', 'Acceso a configuración SMTP', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/correo', 'GET', NULL, NULL, 'NORMAL', 0, 0, '793a8faa2ee5dffe', 'BACKEND', 'DEV', '1.0.0'),
(126, '2026-02-16 18:17:42', 1, '6u186khddn7pavke9hsp676unf', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS_MAIL', 'ACCESS', 'Acceso a configuración SMTP', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/correo', 'GET', NULL, NULL, 'NORMAL', 0, 0, '5f779e874cbe3796', 'BACKEND', 'DEV', '1.0.0'),
(127, '2026-02-16 18:30:39', 1, '6u186khddn7pavke9hsp676unf', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS_COMPANY', 'ACCESS', 'User accessed the company profile', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/empresa', 'GET', NULL, NULL, 'NORMAL', 0, 0, 'af98c2eb5052166d', 'BACKEND', 'DEV', '1.0.0'),
(128, '2026-02-16 18:30:42', 1, '6u186khddn7pavke9hsp676unf', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS_COMPANY', 'ACCESS', 'User accessed the company profile', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/empresa', 'GET', NULL, NULL, 'NORMAL', 0, 0, 'df09971eebc8bc68', 'BACKEND', 'DEV', '1.0.0'),
(129, '2026-02-16 18:30:43', 1, '6u186khddn7pavke9hsp676unf', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'WHATSAPP', 'ACCESS', 'El usuario accedió al panel de configuración de WhatsApp Manual', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/whatsapp', 'GET', NULL, NULL, 'NORMAL', 0, 0, '5c03d212bfed978d', 'BACKEND', 'DEV', '1.0.0'),
(130, '2026-02-16 18:31:14', 1, '6u186khddn7pavke9hsp676unf', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS_COMPANY', 'ACCESS', 'User accessed the company profile', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/empresa', 'GET', NULL, NULL, 'NORMAL', 0, 0, '7b65552dc00a39b2', 'BACKEND', 'DEV', '1.0.0'),
(131, '2026-02-16 18:35:28', 1, '6u186khddn7pavke9hsp676unf', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS_COMPANY', 'ACCESS', 'User accessed the company profile', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/empresa', 'GET', NULL, NULL, 'NORMAL', 0, 0, '8b5aefb4ca9a9ec4', 'BACKEND', 'DEV', '1.0.0'),
(132, '2026-02-16 20:52:47', 1, '6u186khddn7pavke9hsp676unf', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGOUT', 'Cierre de sesión manual: ADMIN GENERAL', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/logout', 'GET', NULL, NULL, 'NORMAL', 0, 0, '3ed9f60bdab3c6c0', 'BACKEND', 'DEV', '1.0.0'),
(133, '2026-02-16 20:52:52', 1, 'ii4vm49tqukkc4d3m27ik1flld', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN', 'Inicio de sesión exitoso: ADMIN GENERAL', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, '', 0, 0, 'da2546c9c6a6552f', 'BACKEND', 'DEV', '1.0.0'),
(134, '2026-02-16 21:10:30', 1, 'ii4vm49tqukkc4d3m27ik1flld', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'WHATSAPP', 'ACCESS', 'El usuario accedió al panel de configuración de WhatsApp Manual', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/whatsapp', 'GET', NULL, NULL, 'NORMAL', 0, 0, 'fee77fbda3ee271f', 'BACKEND', 'DEV', '1.0.0'),
(135, '2026-02-16 21:22:21', 1, 'ii4vm49tqukkc4d3m27ik1flld', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGOUT', 'Cierre de sesión manual: ADMIN GENERAL', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/logout', 'GET', NULL, NULL, 'NORMAL', 0, 0, '737439454c847fee', 'BACKEND', 'DEV', '1.0.0'),
(136, '2026-02-16 21:22:26', 1, 'dt4jec2u7gpoo1bbhvse6o3sst', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN', 'Inicio de sesión exitoso: ADMIN GENERAL', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, '', 0, 0, '2171d763915536e0', 'BACKEND', 'DEV', '1.0.0'),
(137, '2026-02-16 22:02:19', 1, 'dt4jec2u7gpoo1bbhvse6o3sst', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS_EMAIL', 'ACCESS', 'Acceso al módulo independiente de correo', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/correo', 'GET', NULL, NULL, 'NORMAL', 0, 0, '5bd38d868d9e2a2b', 'BACKEND', 'DEV', '1.0.0'),
(138, '2026-02-16 22:04:56', 1, 'dt4jec2u7gpoo1bbhvse6o3sst', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS_EMAIL', 'ACCESS', 'Acceso al módulo independiente de correo', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/correo', 'GET', NULL, NULL, 'NORMAL', 0, 0, '0b6d56fe70fc6492', 'BACKEND', 'DEV', '1.0.0'),
(139, '2026-02-16 22:04:58', 1, 'dt4jec2u7gpoo1bbhvse6o3sst', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS_EMAIL', 'UPDATE', 'Se actualizó la configuración de correo: INSCRIPCION', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/save-correo', 'POST', NULL, NULL, 'WARNING', 0, 0, 'ece0d6d709393987', 'BACKEND', 'DEV', '1.0.0'),
(140, '2026-02-16 22:05:08', 1, 'dt4jec2u7gpoo1bbhvse6o3sst', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS_EMAIL', 'UPDATE', 'Se actualizó la configuración de correo: DOCUMENTOS', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/save-correo', 'POST', NULL, NULL, 'WARNING', 0, 0, '6f33096bc5719e93', 'BACKEND', 'DEV', '1.0.0'),
(141, '2026-02-16 22:05:21', 1, 'dt4jec2u7gpoo1bbhvse6o3sst', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'WHATSAPP', 'ACCESS', 'El usuario accedió al panel de configuración de WhatsApp Manual', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/whatsapp', 'GET', NULL, NULL, 'NORMAL', 0, 0, '1e4b3538bcf1b1d2', 'BACKEND', 'DEV', '1.0.0'),
(142, '2026-02-16 22:05:23', 1, 'dt4jec2u7gpoo1bbhvse6o3sst', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS_EMAIL', 'ACCESS', 'Acceso al módulo independiente de correo', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/correo', 'GET', NULL, NULL, 'NORMAL', 0, 0, 'b27aa744253177d2', 'BACKEND', 'DEV', '1.0.0'),
(143, '2026-02-16 22:09:44', 1, 'dt4jec2u7gpoo1bbhvse6o3sst', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS_EMAIL', 'ACCESS', 'Acceso al módulo independiente de correo', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/correo', 'GET', NULL, NULL, 'NORMAL', 0, 0, 'bd73fe912b38d061', 'BACKEND', 'DEV', '1.0.0'),
(144, '2026-02-16 22:10:14', 1, 'dt4jec2u7gpoo1bbhvse6o3sst', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS_EMAIL', 'TEST_SEND', 'Envío de prueba exitoso a: gorgepereira@gmail.com', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/test-correo', 'POST', NULL, NULL, '', 0, 0, '958f282865fd2cd9', 'BACKEND', 'DEV', '1.0.0'),
(145, '2026-02-16 22:11:02', 1, 'dt4jec2u7gpoo1bbhvse6o3sst', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS_EMAIL', 'ACCESS', 'Acceso al módulo independiente de correo', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/correo', 'GET', NULL, NULL, 'NORMAL', 0, 0, 'a0eb0350884b211d', 'BACKEND', 'DEV', '1.0.0'),
(146, '2026-02-16 22:13:32', 1, 'dt4jec2u7gpoo1bbhvse6o3sst', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGOUT', 'Cierre de sesión manual: ADMIN GENERAL', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/logout', 'GET', NULL, NULL, 'NORMAL', 0, 0, 'cae1d91f63d205ae', 'BACKEND', 'DEV', '1.0.0'),
(147, '2026-02-16 22:15:34', 1, '4505tb2t9ont24mvreh7pfl3se', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN', 'Inicio de sesión exitoso: ADMIN GENERAL', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, '', 0, 0, '3973ffbbd3a51b61', 'BACKEND', 'DEV', '1.0.0'),
(148, '2026-02-16 22:15:36', 1, '4505tb2t9ont24mvreh7pfl3se', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS_EMAIL', 'ACCESS', 'Acceso al módulo independiente de correo', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/correo', 'GET', NULL, NULL, 'NORMAL', 0, 0, '107ab4d337005145', 'BACKEND', 'DEV', '1.0.0'),
(149, '2026-02-16 22:23:13', 1, '4505tb2t9ont24mvreh7pfl3se', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS_EMAIL', 'ACCESS', 'Acceso al módulo independiente de correo', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/correo', 'GET', NULL, NULL, 'NORMAL', 0, 0, '47304c002926ba2b', 'BACKEND', 'DEV', '1.0.0'),
(150, '2026-02-16 22:23:33', 1, '4505tb2t9ont24mvreh7pfl3se', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGOUT', 'Cierre de sesión manual: ADMIN GENERAL', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/logout', 'GET', NULL, NULL, 'NORMAL', 0, 0, '799cd10f159eee96', 'BACKEND', 'DEV', '1.0.0'),
(151, '2026-02-16 22:51:37', NULL, '1gdr4jqb1g7q877rgmfmrl0tml', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'REGISTER', 'PRE_USER_CREATED', 'Se envió correo de validación a gorgepereira@gmail.com', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/register/submit', 'POST', NULL, NULL, 'NORMAL', 0, 0, 'ef44323874c5abf6', 'BACKEND', 'DEV', '1.0.0'),
(152, '2026-02-16 23:01:56', 1, '1gdr4jqb1g7q877rgmfmrl0tml', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN', 'Inicio de sesión exitoso: ADMIN GENERAL', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, '', 0, 0, '1e67a3c5ce9c21f4', 'BACKEND', 'DEV', '1.0.0'),
(153, '2026-02-16 23:02:40', 1, '1gdr4jqb1g7q877rgmfmrl0tml', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGOUT', 'Cierre de sesión manual: ADMIN GENERAL', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/logout', 'GET', NULL, NULL, 'NORMAL', 0, 0, 'f272d32eafabc9b9', 'BACKEND', 'DEV', '1.0.0'),
(154, '2026-02-16 23:04:24', NULL, 'hohm14a89ft602of46teil6gu3', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN_FAILED', 'Intento fallido para el correo: gorgepereira@gmail.com. Motivo: Credenciales incorrectas o cuenta inactiva.', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, 'SECURITY', 0, 0, '43528330f7730f86', 'BACKEND', 'DEV', '1.0.0'),
(155, '2026-02-16 23:08:33', 7, 'hohm14a89ft602of46teil6gu3', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN', 'Inicio de sesión exitoso: JORGE LUIS PEREIRA OLIVARES', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, '', 0, 0, 'd65e4a08ef158141', 'BACKEND', 'DEV', '1.0.0'),
(156, '2026-02-16 23:09:36', 7, 'hohm14a89ft602of46teil6gu3', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'PROFILE', 'UPDATE', 'El usuario actualizó sus datos profesionales y foto de perfil', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/profile/update', 'POST', NULL, NULL, '', 0, 0, '404119e90fd204fa', 'BACKEND', 'DEV', '1.0.0'),
(157, '2026-02-16 23:09:44', 7, 'hohm14a89ft602of46teil6gu3', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'PROFILE', 'UPDATE', 'El usuario actualizó sus datos profesionales y foto de perfil', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/profile/update', 'POST', NULL, NULL, '', 0, 0, '7befd9e3462d40ef', 'BACKEND', 'DEV', '1.0.0'),
(158, '2026-02-16 23:09:50', 7, 'hohm14a89ft602of46teil6gu3', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'PROFILE', 'UPDATE', 'El usuario actualizó sus datos profesionales y foto de perfil', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/profile/update', 'POST', NULL, NULL, '', 0, 0, 'e0e02854981c0f66', 'BACKEND', 'DEV', '1.0.0'),
(159, '2026-02-16 23:10:21', 7, 'hohm14a89ft602of46teil6gu3', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'PROFILE_SECURITY', 'CHANGE_PASSWORD_SUCCESS', 'Contraseña actualizada tras validar identidad previa', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/profile/change-password', 'POST', NULL, NULL, 'WARNING', 0, 0, 'dfa61546978d16cd', 'BACKEND', 'DEV', '1.0.0'),
(160, '2026-02-16 23:10:26', 7, 'hohm14a89ft602of46teil6gu3', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGOUT', 'Cierre de sesión manual: JORGE LUIS PEREIRA OLIVARES', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/logout', 'GET', NULL, NULL, 'NORMAL', 0, 0, '75246ff6fd4ba43b', 'BACKEND', 'DEV', '1.0.0'),
(161, '2026-02-16 23:10:33', 1, 'kbsr20uvhvrmrr6riva9dlmjl5', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN', 'Inicio de sesión exitoso: ADMIN GENERAL', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, '', 0, 0, 'b0ff053389095bfc', 'BACKEND', 'DEV', '1.0.0'),
(162, '2026-02-16 23:11:51', 1, 'kbsr20uvhvrmrr6riva9dlmjl5', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGOUT', 'Cierre de sesión manual: ADMIN GENERAL', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/logout', 'GET', NULL, NULL, 'NORMAL', 0, 0, '953f4e0b4d62e754', 'BACKEND', 'DEV', '1.0.0'),
(163, '2026-02-17 00:20:03', 1, '7p3vi8ulbnnvgdgqk64q2b31v7', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN', 'Inicio de sesión exitoso: ADMIN GENERAL', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, '', 0, 0, 'e381a4f0d0abed8c', 'BACKEND', 'DEV', '1.0.0'),
(164, '2026-02-17 00:20:12', 1, '7p3vi8ulbnnvgdgqk64q2b31v7', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGOUT', 'Cierre de sesión manual: ADMIN GENERAL', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/logout', 'GET', NULL, NULL, 'NORMAL', 0, 0, '44597890ab6104d6', 'BACKEND', 'DEV', '1.0.0'),
(165, '2026-02-17 00:21:28', 7, '1j8952dqs6pco7i65ka4dk2g88', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN', 'Inicio de sesión exitoso: JORGE LUIS PEREIRA OLIVARES', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, '', 0, 0, 'b56f571b814778d5', 'BACKEND', 'DEV', '1.0.0'),
(166, '2026-02-17 00:28:21', 7, '1j8952dqs6pco7i65ka4dk2g88', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGOUT', 'Cierre de sesión manual: JORGE LUIS PEREIRA OLIVARES', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/logout', 'GET', NULL, NULL, 'NORMAL', 0, 0, 'b0accfe2c5434352', 'BACKEND', 'DEV', '1.0.0'),
(167, '2026-02-17 00:41:34', 7, 'fd0rh7p5eopfj2sdblq18iscff', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN', 'Inicio de sesión exitoso: JORGE LUIS PEREIRA OLIVARES', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, '', 0, 0, 'c19d6b2d86e75ab2', 'BACKEND', 'DEV', '1.0.0'),
(168, '2026-02-17 00:41:51', 7, 'fd0rh7p5eopfj2sdblq18iscff', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'PROFILE_SECURITY', 'CHANGE_PASSWORD_SUCCESS', 'Contraseña actualizada tras validar identidad previa', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/profile/change-password', 'POST', NULL, NULL, 'WARNING', 0, 0, '250616c8f3f34d99', 'BACKEND', 'DEV', '1.0.0'),
(169, '2026-02-17 00:41:54', 7, 'fd0rh7p5eopfj2sdblq18iscff', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGOUT', 'Cierre de sesión manual: JORGE LUIS PEREIRA OLIVARES', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/logout', 'GET', NULL, NULL, 'NORMAL', 0, 0, '74a232375d3f065a', 'BACKEND', 'DEV', '1.0.0'),
(170, '2026-02-17 00:48:26', 1, '025onvrevpc1hj9p53ottef7o0', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN', 'Inicio de sesión exitoso: ADMIN GENERAL', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, '', 0, 0, '8d9e93bbf47154ab', 'BACKEND', 'DEV', '1.0.0'),
(171, '2026-02-17 00:51:51', 1, '025onvrevpc1hj9p53ottef7o0', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGOUT', 'Cierre de sesión manual: ADMIN GENERAL', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/logout', 'GET', NULL, NULL, 'NORMAL', 0, 0, '507dd88343a7f94c', 'BACKEND', 'DEV', '1.0.0'),
(172, '2026-02-17 01:12:16', 8, '81li63v6ctqeq2aqs14curr70j', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN', 'Inicio de sesión exitoso: JORGE L PEREIRA O', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, '', 0, 0, '72dc8b97e1eaf8ba', 'BACKEND', 'DEV', '1.0.0'),
(173, '2026-02-17 01:12:30', 8, '81li63v6ctqeq2aqs14curr70j', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'PROFILE', 'UPDATE', 'El usuario actualizó sus datos profesionales y foto de perfil', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/profile/update', 'POST', NULL, NULL, '', 0, 0, '83fe6be687856fef', 'BACKEND', 'DEV', '1.0.0'),
(174, '2026-02-17 01:12:35', 8, '81li63v6ctqeq2aqs14curr70j', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGOUT', 'Cierre de sesión manual: JORGE L PEREIRA O', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/logout', 'GET', NULL, NULL, 'NORMAL', 0, 0, '7aa0d99fed153dd9', 'BACKEND', 'DEV', '1.0.0'),
(175, '2026-02-17 01:12:44', 1, 'on424ptlpqgl26695qga1s9lts', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN', 'Inicio de sesión exitoso: ADMIN GENERAL', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, '', 0, 0, '9436ecf4e8629ebc', 'BACKEND', 'DEV', '1.0.0'),
(176, '2026-02-17 01:13:06', 1, 'on424ptlpqgl26695qga1s9lts', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'USUARIOS', 'UPDATE', 'Se actualizó al usuario: JORGE L PEREIRA O (ID: 8)', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/users/save', 'POST', NULL, NULL, 'NORMAL', 0, 0, '98203d6f6ab2944a', 'BACKEND', 'DEV', '1.0.0'),
(177, '2026-02-17 01:18:19', 1, 'on424ptlpqgl26695qga1s9lts', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGOUT', 'Cierre de sesión manual: ADMIN GENERAL', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/logout', 'GET', NULL, NULL, 'NORMAL', 0, 0, '406f5eed8233cfa5', 'BACKEND', 'DEV', '1.0.0'),
(178, '2026-02-17 01:34:39', 13, 'cbgj0g90pvqbmcdoqi0hrd0cs2', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN', 'Inicio de sesión exitoso: JORGE L PEREIRA O', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, '', 0, 0, 'f21ab6403552ffed', 'BACKEND', 'DEV', '1.0.0'),
(179, '2026-02-17 01:35:40', 13, 'cbgj0g90pvqbmcdoqi0hrd0cs2', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'PROFILE', 'UPDATE', 'El usuario actualizó sus datos profesionales y foto de perfil', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/profile/update', 'POST', NULL, NULL, '', 0, 0, 'b8b1abe6158618b3', 'BACKEND', 'DEV', '1.0.0'),
(180, '2026-02-17 01:36:01', 13, 'cbgj0g90pvqbmcdoqi0hrd0cs2', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGOUT', 'Cierre de sesión manual: JORGE L PEREIRA O', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/logout', 'GET', NULL, NULL, 'NORMAL', 0, 0, '2d5fde68d11b08ef', 'BACKEND', 'DEV', '1.0.0'),
(181, '2026-02-17 01:41:35', 1, 'rla8r81vph5id96c0ok52nt7vn', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN', 'Inicio de sesión exitoso: ADMIN GENERAL', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, '', 0, 0, '334e848c682e6c6e', 'BACKEND', 'DEV', '1.0.0'),
(182, '2026-02-17 01:48:14', 1, 'rla8r81vph5id96c0ok52nt7vn', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGOUT', 'Cierre de sesión manual: ADMIN GENERAL', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/logout', 'GET', NULL, NULL, 'NORMAL', 0, 0, '8121291f96a0df09', 'BACKEND', 'DEV', '1.0.0'),
(183, '2026-02-17 01:48:37', 16, '3cgi02pbh42gj183bdjfmsfmj4', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN', 'Inicio de sesión exitoso: JORGE L PEREIRA O', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, '', 0, 0, '133efa056eb37d8c', 'BACKEND', 'DEV', '1.0.0'),
(184, '2026-02-17 01:48:49', 16, '3cgi02pbh42gj183bdjfmsfmj4', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'PROFILE', 'UPDATE', 'El usuario actualizó sus datos profesionales y foto de perfil', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/profile/update', 'POST', NULL, NULL, '', 0, 0, '93bc17220abef6f2', 'BACKEND', 'DEV', '1.0.0'),
(185, '2026-02-17 01:48:52', 16, '3cgi02pbh42gj183bdjfmsfmj4', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGOUT', 'Cierre de sesión manual: JORGE L PEREIRA O', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/logout', 'GET', NULL, NULL, 'NORMAL', 0, 0, 'c587723198aa6d24', 'BACKEND', 'DEV', '1.0.0'),
(186, '2026-02-17 01:49:00', 1, 'lll1gbibo875arr8t4ftj8tir6', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN', 'Inicio de sesión exitoso: ADMIN GENERAL', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, '', 0, 0, '0745b4a1ce283137', 'BACKEND', 'DEV', '1.0.0'),
(187, '2026-02-17 01:49:13', 1, 'lll1gbibo875arr8t4ftj8tir6', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'USUARIOS', 'UPDATE', 'Se actualizó al usuario: JORGE L PEREIRA O (ID: 16)', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/users/save', 'POST', NULL, NULL, 'NORMAL', 0, 0, 'c5d230e78b2beed1', 'BACKEND', 'DEV', '1.0.0'),
(188, '2026-02-17 01:49:17', 1, 'lll1gbibo875arr8t4ftj8tir6', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGOUT', 'Cierre de sesión manual: ADMIN GENERAL', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/logout', 'GET', NULL, NULL, 'NORMAL', 0, 0, 'c7824e91c36cc940', 'BACKEND', 'DEV', '1.0.0'),
(189, '2026-02-17 01:58:23', 16, '8daaidh4nckvs7d1d5l0ji3ga9', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN', 'Inicio de sesión exitoso: JORGE L PEREIRA O', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, '', 0, 0, '72241c2023f91503', 'BACKEND', 'DEV', '1.0.0'),
(190, '2026-02-17 01:58:27', 16, '8daaidh4nckvs7d1d5l0ji3ga9', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGOUT', 'Cierre de sesión manual: JORGE L PEREIRA O', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/logout', 'GET', NULL, NULL, 'NORMAL', 0, 0, 'c7ee4b67659fb34d', 'BACKEND', 'DEV', '1.0.0'),
(191, '2026-02-17 01:58:51', 1, 'g2p2icn6r1qrp1aqmue403tftq', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN', 'Inicio de sesión exitoso: ADMIN GENERAL', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, '', 0, 0, '54dc59d10d54f00a', 'BACKEND', 'DEV', '1.0.0'),
(192, '2026-02-17 01:58:56', 1, 'g2p2icn6r1qrp1aqmue403tftq', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS_COMPANY', 'ACCESS', 'User accessed the company profile', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/empresa', 'GET', NULL, NULL, 'NORMAL', 0, 0, '04ab6bb32b985b49', 'BACKEND', 'DEV', '1.0.0'),
(193, '2026-02-17 01:59:36', 1, 'g2p2icn6r1qrp1aqmue403tftq', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGOUT', 'Cierre de sesión manual: ADMIN GENERAL', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/logout', 'GET', NULL, NULL, 'NORMAL', 0, 0, 'bea544edd6375083', 'BACKEND', 'DEV', '1.0.0'),
(194, '2026-02-17 02:05:28', 16, '5sf9s6n96tsp7qi89ra5i4i0tj', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN', 'Inicio de sesión exitoso: JORGE L PEREIRA O', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, '', 0, 0, 'f754af1bffad4d15', 'BACKEND', 'DEV', '1.0.0'),
(195, '2026-02-17 02:05:32', 16, '5sf9s6n96tsp7qi89ra5i4i0tj', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGOUT', 'Cierre de sesión manual: JORGE L PEREIRA O', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/logout', 'GET', NULL, NULL, 'NORMAL', 0, 0, 'ae02928675f012ce', 'BACKEND', 'DEV', '1.0.0'),
(196, '2026-02-17 02:05:38', 1, 'rlqj2r7g3j452lbum33aph3su1', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN', 'Inicio de sesión exitoso: ADMIN GENERAL', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, '', 0, 0, '4df9bc3eafbbc123', 'BACKEND', 'DEV', '1.0.0'),
(197, '2026-02-17 02:05:46', 1, 'rlqj2r7g3j452lbum33aph3su1', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'USUARIOS', 'UPDATE', 'Se actualizó al usuario: JORGE L PEREIRA O (ID: 16)', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/users/save', 'POST', NULL, NULL, 'NORMAL', 0, 0, 'd5750ebcb0e5a9fd', 'BACKEND', 'DEV', '1.0.0'),
(198, '2026-02-17 02:05:49', 1, 'rlqj2r7g3j452lbum33aph3su1', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGOUT', 'Cierre de sesión manual: ADMIN GENERAL', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/logout', 'GET', NULL, NULL, 'NORMAL', 0, 0, '8e81cba7ef44e61c', 'BACKEND', 'DEV', '1.0.0'),
(199, '2026-02-17 02:05:58', 16, 'nd55hf4vt1r0gbebnrvg387beh', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN', 'Inicio de sesión exitoso: JORGE L PEREIRA O', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, '', 0, 0, 'bf2668667321bd9e', 'BACKEND', 'DEV', '1.0.0'),
(200, '2026-02-17 02:06:46', 16, 'nd55hf4vt1r0gbebnrvg387beh', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS_EMAIL', 'ACCESS', 'Acceso al módulo independiente de correo', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/correo', 'GET', NULL, NULL, 'NORMAL', 0, 0, '0aa9810731710318', 'BACKEND', 'DEV', '1.0.0'),
(201, '2026-02-17 02:07:56', 16, 'nd55hf4vt1r0gbebnrvg387beh', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS_EMAIL', 'ACCESS', 'Acceso al módulo independiente de correo', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/correo', 'GET', NULL, NULL, 'NORMAL', 0, 0, 'd7b19386b80318a6', 'BACKEND', 'DEV', '1.0.0'),
(202, '2026-02-17 02:07:59', 16, 'nd55hf4vt1r0gbebnrvg387beh', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'WHATSAPP', 'ACCESS', 'El usuario accedió al panel de configuración de WhatsApp Manual', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/whatsapp', 'GET', NULL, NULL, 'NORMAL', 0, 0, '660a09b23db9c35c', 'BACKEND', 'DEV', '1.0.0'),
(203, '2026-02-17 02:08:15', 16, 'nd55hf4vt1r0gbebnrvg387beh', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'SETTINGS_COMPANY', 'ACCESS', 'User accessed the company profile', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/settings/empresa', 'GET', NULL, NULL, 'NORMAL', 0, 0, '424b5a78548b7b59', 'BACKEND', 'DEV', '1.0.0'),
(204, '2026-02-17 02:08:25', 16, 'nd55hf4vt1r0gbebnrvg387beh', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGOUT', 'Cierre de sesión manual: JORGE L PEREIRA O', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/logout', 'GET', NULL, NULL, 'NORMAL', 0, 0, 'f4a63908e3f281e9', 'BACKEND', 'DEV', '1.0.0'),
(205, '2026-02-17 02:08:31', 1, '8k8c2s6ccltddln9kmv5ad5cvs', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN', 'Inicio de sesión exitoso: ADMIN GENERAL', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, '', 0, 0, '81d12ce597c4be4b', 'BACKEND', 'DEV', '1.0.0'),
(206, '2026-02-17 02:09:14', 1, '8k8c2s6ccltddln9kmv5ad5cvs', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'USUARIOS', 'CREATE', 'Nuevo registro: OPERADOR DIPLOMATIC (oper@diplomatic.local)', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/users/save', 'POST', NULL, NULL, '', 0, 0, '8c302be54f1fec64', 'BACKEND', 'DEV', '1.0.0'),
(207, '2026-02-17 02:09:23', 1, '8k8c2s6ccltddln9kmv5ad5cvs', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGOUT', 'Cierre de sesión manual: ADMIN GENERAL', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/logout', 'GET', NULL, NULL, 'NORMAL', 0, 0, '3d24b3a455f4fde4', 'BACKEND', 'DEV', '1.0.0'),
(208, '2026-02-17 02:09:30', 17, 'oqmtqb8rpmcnojmjso00e23ond', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGIN', 'Inicio de sesión exitoso: OPERADOR DIPLOMATIC', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/login', 'POST', NULL, NULL, '', 0, 0, '2a82098d4db902b4', 'BACKEND', 'DEV', '1.0.0'),
(209, '2026-02-17 02:09:33', 17, 'oqmtqb8rpmcnojmjso00e23ond', '200.8.212.43', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', 'Windows', 'AUTH', 'LOGOUT', 'Cierre de sesión manual: OPERADOR DIPLOMATIC', NULL, NULL, NULL, NULL, NULL, '/diplomatic/public/logout', 'GET', NULL, NULL, 'NORMAL', 0, 0, '8b841506acee1e45', 'BACKEND', 'DEV', '1.0.0');

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
  `tipo_correo` enum('INSCRIPCION','DOCUMENTOS','RECUPERACION') NOT NULL,
  `smtp_host` varchar(255) DEFAULT NULL,
  `smtp_port` int(11) DEFAULT NULL,
  `smtp_security` enum('TLS','SSL','NONE') DEFAULT NULL,
  `smtp_user` varchar(255) DEFAULT NULL,
  `smtp_password` varchar(255) DEFAULT NULL,
  `from_name` varchar(255) DEFAULT NULL,
  `from_email` varchar(255) DEFAULT NULL,
  `reply_to` varchar(255) DEFAULT NULL,
  `asunto` varchar(255) DEFAULT NULL,
  `contenido` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_email_settings`
--

INSERT INTO `tbl_email_settings` (`id`, `tipo_correo`, `smtp_host`, `smtp_port`, `smtp_security`, `smtp_user`, `smtp_password`, `from_name`, `from_email`, `reply_to`, `asunto`, `contenido`, `created_at`, `updated_at`) VALUES
(1, 'INSCRIPCION', 'mail.plataformadiplomados.com', 465, 'SSL', 'info@plataformadiplomados.com', 'i1;r+e}kmkoBDJ!W', 'Soporte de Inscripción Plataforma Diplomados', 'info@plataformadiplomados.com', NULL, 'Confirma tu registro en Plataforma Diplomados', '<!doctype html>\r\n<html>\r\n<body style=\"margin:0; padding:0; font-family: Arial, sans-serif; color:#333; line-height:1.5;\">\r\n\r\n<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\">\r\n  <tr>\r\n    <td align=\"center\">\r\n\r\n      <table width=\"600\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"padding:20px;\">\r\n\r\n        <tr>\r\n          <td>\r\n\r\n            <p>Hola {nombre} {apellido},</p>\r\n\r\n            <p>\r\n              Gracias por registrarte en Plataforma Diplomados.\r\n            </p>\r\n\r\n            <p>\r\n              Para completar la activación de tu cuenta, haz clic en el siguiente botón:\r\n            </p>\r\n\r\n            <!-- BOTÓN -->\r\n            <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"margin:20px 0;\">\r\n              <tr>\r\n                <td align=\"center\" bgcolor=\"#1a73e8\" style=\"border-radius:4px;\">\r\n                  <a href=\"{link_inscripcion}\" \r\n                     style=\"display:inline-block; padding:12px 20px; color:#ffffff; text-decoration:none; font-family:Arial, sans-serif; font-size:14px;\">\r\n                    Confirmar mi cuenta\r\n                  </a>\r\n                </td>\r\n              </tr>\r\n            </table>\r\n\r\n            <p>\r\n              Si no puedes hacer clic en el botón, copia y pega el siguiente enlace en tu navegador:\r\n            </p>\r\n\r\n            <p style=\"word-break: break-all;\">\r\n              {link_inscripcion}\r\n            </p>\r\n\r\n            <p>\r\n              Si no realizaste este registro, puedes ignorar este mensaje.\r\n            </p>\r\n\r\n            <p>\r\n              Saludos,<br>\r\n              Equipo Plataforma Diplomados\r\n            </p>\r\n\r\n          </td>\r\n        </tr>\r\n\r\n      </table>\r\n\r\n    </td>\r\n  </tr>\r\n</table>\r\n\r\n</body>\r\n</html>\r\n', '2026-02-16 18:29:44', '2026-02-16 19:12:35'),
(2, 'DOCUMENTOS', 'mail.plataformadiplomados.com', 465, 'SSL', 'info@plataformadiplomados.com', 'i1;r+e}kmkoBDJ!W', 'Soporte de Certificados Plataforma Diplomados ', 'info@plataformadiplomados.com', NULL, 'Tu constancia está disponible para descarga', '<!doctype html>\r\n<html lang=\"es\">\r\n<body style=\"margin:0; padding:0; font-family: Arial, sans-serif; color:#333; line-height:1.5;\">\r\n\r\n<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\">\r\n  <tr>\r\n    <td align=\"center\">\r\n\r\n      <table width=\"600\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"padding:20px;\">\r\n        <tr>\r\n          <td>\r\n\r\n            <p>Hola {nombre} {apellido},</p>\r\n\r\n            <p>\r\n              Tu constancia de estudio ya está disponible en <strong>Plataforma Diplomados Online</strong>.\r\n            </p>\r\n\r\n            <p>\r\n              Para descargarla, haz clic en el siguiente botón:\r\n            </p>\r\n\r\n            <table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"margin:20px 0;\">\r\n              <tr>\r\n                <td align=\"center\" bgcolor=\"#1a73e8\" style=\"border-radius:4px;\">\r\n                  <a href=\"{link_descarga}\"\r\n                     style=\"display:inline-block; padding:12px 20px; color:#ffffff; text-decoration:none; font-family:Arial, sans-serif; font-size:14px;\">\r\n                    Descargar constancia\r\n                  </a>\r\n                </td>\r\n              </tr>\r\n            </table>\r\n\r\n            <p>\r\n              Si no puedes hacer clic en el botón, copia y pega el siguiente enlace en tu navegador:\r\n            </p>\r\n\r\n            <p style=\"word-break: break-all;\">\r\n              {link_descarga}\r\n            </p>\r\n\r\n            <p>\r\n              Si no solicitaste esta constancia, puedes ignorar este mensaje.\r\n            </p>\r\n\r\n            <p>\r\n              Saludos,<br>\r\n              Equipo Plataforma Diplomados Online\r\n            </p>\r\n\r\n          </td>\r\n        </tr>\r\n      </table>\r\n\r\n    </td>\r\n  </tr>\r\n</table>\r\n\r\n</body>\r\n</html>\r\n', '2026-02-16 18:30:18', '2026-02-16 21:10:26'),
(23, 'RECUPERACION', 'mail.plataformadiplomados.com', 465, 'SSL', 'info@plataformadiplomados.com', 'i1;r+e}kmkoBDJ!W', 'Soporte de Recuperación Plataforma Diplomados', 'info@plataformadiplomados.com', NULL, 'Recupera tu contraseña en Plataforma Diplomados', '<!doctype html><html><body style=\"font-family: Arial; color:#333;\"><p>Hola {{nombre}} {{apellido}},</p><p>Para recuperar tu contraseña en Plataforma Diplomados, haz clic en el siguiente botón:</p><div style=\"margin:20px 0;\"><a href=\"{{link_inscripcion}}\" style=\"background:#1a73e8; color:#ffffff; padding:12px 20px; text-decoration:none; border-radius:4px;\">Cambiar mi contraseña</a></div><p>Si no puedes hacer clic, usa este enlace:<br>{{link_inscripcion}}</p><p>Saludos,<br>Equipo Plataforma Diplomados</p></body></html>', '2026-02-17 00:40:14', '2026-02-17 00:40:14');

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

--
-- Volcado de datos para la tabla `tbl_pre_users`
--

INSERT INTO `tbl_pre_users` (`id`, `first_name`, `last_name`, `email`, `phone`, `document_id`, `status`, `email_verified`, `temp_password_hash`, `temp_password_expires_at`, `created_at`, `updated_at`) VALUES
(9, 'JORGE L', 'PEREIRA O', 'gorgepereira@gmail.com', '+584145365380', '14979523', 'VERIFIED', 1, NULL, NULL, '2026-02-16 21:08:38', '2026-02-16 21:48:23');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_pre_user_tokens`
--

CREATE TABLE `tbl_pre_user_tokens` (
  `id` int(11) UNSIGNED NOT NULL,
  `pre_user_id` int(11) DEFAULT NULL,
  `token_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_pre_user_tokens`
--

INSERT INTO `tbl_pre_user_tokens` (`id`, `pre_user_id`, `token_hash`, `expires_at`, `used_at`, `created_at`) VALUES
(7, 9, '31749796c9d94fe36c406d1d49d9f949dbfa8fd82d4041a719f8df5157101b7b', '2026-02-17 21:08:38', '2026-02-16 21:48:23', '2026-02-16 21:08:38');

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
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `profile_complete` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_users`
--

INSERT INTO `tbl_users` (`id`, `user_type`, `status`, `first_name`, `last_name`, `email`, `role`, `phone`, `document_id`, `address`, `provenance`, `undergraduate_degree`, `avatar`, `password_hash`, `last_login_at`, `created_at`, `updated_at`, `profile_complete`) VALUES
(1, 'INTERNAL', 'ACTIVE', 'ADMIN', 'GENERAL', 'admin@diplomatic.local', 'ADMIN', NULL, NULL, NULL, NULL, NULL, 'default_avatar.png', '$2y$10$00VC/qpQDbIDycCmDk0.Au4AEP7EfpgQ8J451Zs9YOvzdH4Vpty5K', '2026-02-16 22:08:30', '2026-02-07 17:08:04', '2026-02-16 22:08:30', 0),
(2, 'INTERNAL', 'ACTIVE', 'JORGE', 'PEREIRA', 'raizamileva@gmail.com', 'ADMIN', '+584145365380', '149795231', 'Urb El Valle, Calle Principal, Casa Nro 23, Cabudare, Jose Gregorio Bastidas, Palavecino', 'Cabudare', 'Ingenieria', 'usr_1770810835.png', '$2y$10$Pa.672SkyV7tzG8yfpeI5uInUoOt7T/YKWdtVa.dfqqsKjjLFn8pG', NULL, '2026-02-08 11:35:40', '2026-02-16 19:53:07', 0),
(4, 'INTERNAL', 'ACTIVE', 'Homer', 'Pereira', 'ddrgomes@gmail.com', 'PARTICIPANT', '+584145278577', '13567117', 'Urb Club Hipico Las Trinitarias, Res La Trinitaria Piso 4 Apartament 44', 'Barquisimeto', 'Ingenieria Quimica', 'usr_1770811774.png', '$2y$10$QswXlqG07r/h6hGKMdOoUu0gQWNShrTrl37F0QWoMDSn8QnHcw6aG', '2026-02-13 21:19:11', '2026-02-11 08:05:52', '2026-02-13 21:19:11', 1),
(6, 'INTERNAL', 'ACTIVE', 'Luis', 'Olivares', 'jorgeluispereiraolivares@gmail.com', 'ADMIN', '+5841450421255', '1497952312', 'Calle 52', 'Cabudare', 'Ingenieria', 'usr_1770814743.png', '$2y$10$0Lmn1psgOnluJk9Ndeg7AeLmiFc8RfSwvcFwuAJwuUChqnPq4570u', '2026-02-13 07:22:49', '2026-02-11 08:58:54', '2026-02-16 19:53:21', 0),
(16, 'INTERNAL', 'ACTIVE', 'JORGE L', 'PEREIRA O', 'gorgepereira@gmail.com', 'ADMIN', '+584145365380', '14979523', 'Urb El Valle, Calle Principal, Casa Nro 23, Cabudare, Jose Gregorio Bastidas, Palavecino', 'Barquisimeto', 'Ingeniero', 'usr_1771292929.webp', '$2y$10$U1.h0ijFByFvimvloqPbS..0mqAr.YyvmEKfv4EQfmS3zCSuXdPcS', '2026-02-16 22:05:58', '2026-02-16 21:48:23', '2026-02-16 22:05:58', 1),
(17, 'INTERNAL', 'ACTIVE', 'OPERADOR', 'DIPLOMATIC', 'oper@diplomatic.local', 'OPERATOR', '', '1', '', '', '', 'default_avatar.png', '$2y$10$ru/XBzGeMeidv8SVtsml6uBl1DuBGZhOmsRxy027EPqJpGWsJ5q3C', '2026-02-16 22:09:29', '2026-02-16 22:09:14', '2026-02-16 22:09:29', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_user_recovery_tokens`
--

CREATE TABLE `tbl_user_recovery_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tbl_user_recovery_tokens`
--

INSERT INTO `tbl_user_recovery_tokens` (`id`, `user_id`, `token_hash`, `expires_at`, `used_at`, `created_at`) VALUES
(9, 16, 'dafdfee6440e6603b402d58297e63e4980a23372804e7c46297a85cc839723cc', '2026-02-16 23:55:59', '2026-02-16 21:58:17', '2026-02-17 01:55:59'),
(10, 16, '4136b5b091c1ba4482248531b53e0ff505b038a999624f203af896ebc80192fb', '2026-02-17 00:04:37', '2026-02-16 22:05:18', '2026-02-17 02:04:37');

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
(2, 'Raiza Mileva', 'CONSTANCIA_ESTUDIO', '+584124571599', '2026-02-12 17:31:59'),
(3, 'Juan Pérez', 'CONSTANCIA_ESTUDIO', '+584145042125', '2026-02-13 16:39:33');

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
-- Indices de la tabla `tbl_audit_logs`
--
ALTER TABLE `tbl_audit_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_event_type` (`event_type`);

--
-- Indices de la tabla `tbl_company_settings`
--
ALTER TABLE `tbl_company_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `tbl_email_settings`
--
ALTER TABLE `tbl_email_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tipo_correo` (`tipo_correo`);

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
-- Indices de la tabla `tbl_user_recovery_tokens`
--
ALTER TABLE `tbl_user_recovery_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_token_hash` (`token_hash`),
  ADD KEY `idx_recovery_user` (`user_id`);

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
-- AUTO_INCREMENT de la tabla `tbl_audit_logs`
--
ALTER TABLE `tbl_audit_logs`
  MODIFY `log_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=210;

--
-- AUTO_INCREMENT de la tabla `tbl_company_settings`
--
ALTER TABLE `tbl_company_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `tbl_email_settings`
--
ALTER TABLE `tbl_email_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `tbl_pre_user_tokens`
--
ALTER TABLE `tbl_pre_user_tokens`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `tbl_user_recovery_tokens`
--
ALTER TABLE `tbl_user_recovery_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `tbl_wa_manual_logs`
--
ALTER TABLE `tbl_wa_manual_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
