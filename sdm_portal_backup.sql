-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: sdm_portal
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `alerts`
--

DROP TABLE IF EXISTS `alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `alerts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'info',
  `alert_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `alerts`
--

LOCK TABLES `alerts` WRITE;
/*!40000 ALTER TABLE `alerts` DISABLE KEYS */;
/*!40000 ALTER TABLE `alerts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `classifications`
--

DROP TABLE IF EXISTS `classifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `classifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `color` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `classifications`
--

LOCK TABLES `classifications` WRITE;
/*!40000 ALTER TABLE `classifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `classifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `evidences`
--

DROP TABLE IF EXISTS `evidences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evidences` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `requirement_id` bigint(20) unsigned NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_type` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evidences`
--

LOCK TABLES `evidences` WRITE;
/*!40000 ALTER TABLE `evidences` DISABLE KEYS */;
/*!40000 ALTER TABLE `evidences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2025_11_01_234203_create_classifications_table',1),(5,'2025_11_01_234203_create_reporters_table',1),(6,'2025_11_01_234204_create_evidences_table',1),(7,'2025_11_01_234204_create_projects_table',1),(8,'2025_11_01_234204_create_requirements_table',1),(9,'2025_11_01_234207_create_alerts_table',1),(10,'2025_11_02_182148_add_missing_columns_to_classifications_table',1),(11,'2025_11_02_202535_create_service_families_table',1),(12,'2025_11_02_202535_create_service_level_agreements_table',1),(13,'2025_11_02_202535_create_services_table',1),(14,'2025_11_02_202535_create_sub_services_table',1),(15,'2025_11_02_202536_create_service_requests_table',1),(16,'2025_11_02_202538_create_sla_breach_logs_table',1),(17,'2025_11_03_182400_add_pause_fields_to_service_requests_table',1),(18,'2025_11_03_203004_create_service_request_evidences_table',1),(19,'2025_11_03_211141_add_deleted_at_to_service_request_evidences_table',1),(20,'2025_11_03_233907_add_user_id_to_service_request_evidences_table',1),(21,'2025_11_04_055637_add_web_routes_to_service_requests_table',1),(22,'2025_11_04_141333_add_sort_order_to_service_families_table',1),(23,'2025_11_04_200125_create_service_subservices_table',1),(24,'2025_11_04_200256_update_service_level_agreements_table',1),(25,'2025_11_04_225434_update_code_length_in_service_families_table',1),(26,'2025_11_05_011034_add_description_to_service_level_agreements_table',1),(27,'2025_11_05_011611_add_missing_columns_to_service_level_agreements_table',1),(28,'2025_11_05_011757_complete_missing_columns_in_service_level_agreements_table',1),(29,'2025_11_05_033456_remove_duplicate_sub_service_id_from_sla_final',1),(31,'2025_11_11_191925_add_paused_by_to_service_requests_table',2),(32,'2025_11_11_231918_add_rejection_fields_to_service_requests_table',3),(33,'2025_11_11_232743_add_rechazada_to_status_enum_in_service_requests_table',4),(34,'2025_11_13_175724_create_requesters_table',5),(38,'2025_11_13_175952_update_service_requests_add_requester_id',6),(39,'2025_11_14_230603_fix_empty_criticality_level_values',7);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `projects`
--

DROP TABLE IF EXISTS `projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `projects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `projects_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `projects`
--

LOCK TABLES `projects` WRITE;
/*!40000 ALTER TABLE `projects` DISABLE KEYS */;
/*!40000 ALTER TABLE `projects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reporters`
--

DROP TABLE IF EXISTS `reporters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reporters` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `department` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reporters_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reporters`
--

LOCK TABLES `reporters` WRITE;
/*!40000 ALTER TABLE `reporters` DISABLE KEYS */;
/*!40000 ALTER TABLE `reporters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `requesters`
--

DROP TABLE IF EXISTS `requesters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `requesters` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `position` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `requesters_name_index` (`name`),
  KEY `requesters_email_index` (`email`),
  KEY `requesters_department_index` (`department`),
  KEY `requesters_is_active_index` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `requesters`
--

LOCK TABLES `requesters` WRITE;
/*!40000 ALTER TABLE `requesters` DISABLE KEYS */;
INSERT INTO `requesters` VALUES (1,'Ledys Magaly Moreno Basto','mmoreno@movilidadbogota.gov.co',NULL,'Oficina Asesora de Comunicaciones y Cultura para la Movilidad','Profesional Especializado',1,'2025-11-14 00:16:23','2025-11-14 00:16:23',NULL),(2,'Luis Felipe Jaramillo Giraldo','ljaramillo@movilidadbogota.gov.co',NULL,'Oficina Asesora de Comunicaciones y Cultura para la Movilidad','Profesional Universitario',1,'2025-11-14 00:22:16','2025-11-14 00:22:16',NULL),(3,'Lina Maria Garcia Huertas','lgarciah@movilidadbogota.gov.co',NULL,'Oficina Asesora de Comunicaciones y Cultura para la Movilidad','Profesional Universitario',1,'2025-11-14 00:23:27','2025-11-14 00:23:27',NULL),(4,'Camilo Alfredo Garzon Guavita','cgarzong@movilidadbogota.gov.co',NULL,'Dirección de Investigaciones Administrativas al Tránsito y Transporte','Profesional Universitario',1,'2025-11-14 00:27:10','2025-11-14 00:27:10',NULL),(5,'Zandra Patricia Ramos Chavarro','zramos@movilidadbogota.gov.co',NULL,'Dirección Atención al Ciudadano','Profesional Universitario',1,'2025-11-14 00:28:23','2025-11-14 00:28:23',NULL),(6,'Gustavo Medina','gmedina@movilidadbogota.gov.co',NULL,'Oficina de Tecnología de la Información y las Comunicaciones','Especialista Web',1,'2025-11-14 01:39:16','2025-11-14 01:39:16',NULL),(7,'Edna Patricia Gutierrez Ortiz','egutierrezo@movilidadbogota.gov.co',NULL,'Oficina Asesora de Comunicaciones y Cultura para la Movilidad','Profesional Universitario',1,'2025-11-14 01:51:19','2025-11-14 01:51:19',NULL),(8,'Johana Marcela Morales Muete','jmmorales@movilidadbogota.gov.co',NULL,'Oficina Asesora de Comunicaciones y Cultura para la Movilidad','Profesional Universitario',1,'2025-11-14 01:54:59','2025-11-14 01:54:59',NULL),(9,'Claudia Elena Parada Aponte','cparada@movilidadbogota.gov.co',NULL,'Oficina Asesora de Planeación Institucional','Profesional Especializado',1,'2025-11-14 02:30:31','2025-11-14 02:30:31',NULL),(10,'Ana Milena Montes Montoya','amontes@movilidadbogota.gov.co',NULL,'Dirección de Ingeniería de Tránsito','Profesional Especializado',1,'2025-11-14 02:35:02','2025-11-14 02:35:02',NULL),(11,'Lady Carolina Cardenas Perez','lcardenasp@movilidadbogota.gov.coº',NULL,'Oficina Asesora de Planeación Institucional',' Profesional Universitario ',1,'2025-11-14 02:43:04','2025-11-14 02:43:04',NULL),(12,'Martha Cecilia Bayona Gomez','mbayona@movilidadbogota.gov.co',NULL,'Subdirección de Planes de Manejo de Tránsito','Profesional Universitario',1,'2025-11-14 03:02:26','2025-11-14 03:02:26',NULL),(13,'Deisy Geraldin Vargas Amaya','dgvargas@movilidadbogota.gov.co',NULL,'Subdirección de Infraestructura','Profesional Universitario',1,'2025-11-14 03:09:52','2025-11-14 03:09:52',NULL),(14,'Adriana Gutierrez Arismendi','adgutierrez@movilidadbogota.gov.co',NULL,'Oficina de Tecnología de la Información y las Comunicaciones','Profesional Universitario',1,'2025-11-14 03:17:14','2025-11-14 03:17:14',NULL),(15,'Aile de Milsed Rubiano Castañeda','arubiano@movilidadbogota.gov.co',NULL,'Oficina Asesora de Comunicaciones y Cultura para la Movilidad','Profesional Universitario',1,'2025-11-14 03:20:31','2025-11-14 03:20:31',NULL),(16,'Juan Sebastian Moreno Galindo','jsmoreno@movilidadbogota.gov.co',NULL,'Oficina de Gestión Social','Profesional Universitario',1,'2025-11-14 03:23:37','2025-11-14 03:23:37',NULL),(17,'Valentina Cardenas Echeverri','vcardenas@movilidadbogota.gov.co',NULL,'Oficina de Seguridad Vial','Contratista',1,'2025-11-14 03:27:19','2025-11-14 03:27:19',NULL),(18,'Diana Carolina Duran Forero','dcduran@movilidadbogota.gov.co',NULL,'Subdirección de la Bicicleta y el Peatón','Subdirectora',1,'2025-11-14 03:29:20','2025-11-14 03:29:20',NULL),(19,'Xiomara Gómez','xgomez@movilidadbogota.gov.co',NULL,'Subsecretaría de Gestión Jurídica','Profesional Especializado',1,'2025-11-14 03:40:05','2025-11-14 03:40:05',NULL);
/*!40000 ALTER TABLE `requesters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `requirements`
--

DROP TABLE IF EXISTS `requirements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `requirements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `code` varchar(255) NOT NULL,
  `reporter_id` bigint(20) unsigned NOT NULL,
  `classification_id` bigint(20) unsigned NOT NULL,
  `project_id` bigint(20) unsigned DEFAULT NULL,
  `priority` varchar(255) NOT NULL DEFAULT 'medium',
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `requirements_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `requirements`
--

LOCK TABLES `requirements` WRITE;
/*!40000 ALTER TABLE `requirements` DISABLE KEYS */;
/*!40000 ALTER TABLE `requirements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `service_families`
--

DROP TABLE IF EXISTS `service_families`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `service_families` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `service_families_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_families`
--

LOCK TABLES `service_families` WRITE;
/*!40000 ALTER TABLE `service_families` DISABLE KEYS */;
INSERT INTO `service_families` VALUES (1,'Gestión de Contenidos Web','WEB_CONTENT','Apoyar en la edición, diseño y organización de contenidos web y otros recursos relacionados.',1,1,'2025-11-05 19:14:21','2025-11-05 19:14:21',NULL),(2,'Cumplimiento Normativo','COMPLIANCE','Apoyar e implementar acciones que faciliten el cumplimento de los lineamientos del Modelo Integrado de Planeación y Gestión, la Ley 1712 de 2014 y el Decreto',1,2,'2025-11-05 19:14:21','2025-11-05 19:14:21',NULL),(3,'Seguimiento de Publicaciones','PUB_TRACKING','Realizar el seguimiento de solicitudes de publicación en la página web, intranet y otros portales web de la secretaría.',1,3,'2025-11-05 19:14:21','2025-11-05 19:14:21',NULL),(4,'Administración de Sitios Web','WEB_ADMIN','Apoyar en la administración y optimización de estilo, calidad y actualización de datos de los sitios web de la SDM.',1,4,'2025-11-05 19:14:21','2025-11-05 19:14:21',NULL),(5,'Validación de Contenidos Web','CONT_VALID','Validar y monitorear contenidos publicados en los portales Web de la SDM.',1,5,'2025-11-05 19:14:21','2025-11-05 19:14:21',NULL),(6,'Publicación de Información','INFO_PUB','Apoyar la publicación de información en la web, intranet y sitios web de la SDM.',1,6,'2025-11-05 19:14:21','2025-11-05 19:14:21',NULL),(7,'Disponibilidad de Servicios','SERV_AVAIL','Contar con disponibilidad para prestar sus servicios, de acuerdo con su especialidad, en los espacios acordados y requeridos por el supervisor según la necesidad del servicio.',1,7,'2025-11-05 19:14:21','2025-11-05 19:14:21',NULL),(8,'Tareas Asignadas por Supervisor','SUPER_TASKS','Las demás que le sean asignadas por el supervisor en relación con el objeto del contrato.',1,8,'2025-11-05 19:14:21','2025-11-05 19:14:21',NULL);
/*!40000 ALTER TABLE `service_families` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `service_level_agreements`
--

DROP TABLE IF EXISTS `service_level_agreements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `service_level_agreements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `service_subservice_id` bigint(20) unsigned DEFAULT NULL,
  `service_family_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `criticality_level` enum('BAJA','MEDIA','ALTA','CRITICA') NOT NULL,
  `response_time_hours` int(11) NOT NULL,
  `resolution_time_hours` int(11) NOT NULL,
  `availability_percentage` decimal(5,2) NOT NULL,
  `acceptance_time_minutes` int(11) NOT NULL DEFAULT 30,
  `response_time_minutes` int(11) NOT NULL,
  `resolution_time_minutes` int(11) NOT NULL,
  `conditions` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `service_level_agreements_service_family_id_foreign` (`service_family_id`),
  KEY `service_level_agreements_service_subservice_id_foreign` (`service_subservice_id`),
  CONSTRAINT `service_level_agreements_service_family_id_foreign` FOREIGN KEY (`service_family_id`) REFERENCES `service_families` (`id`) ON DELETE CASCADE,
  CONSTRAINT `service_level_agreements_service_subservice_id_foreign` FOREIGN KEY (`service_subservice_id`) REFERENCES `service_subservices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_level_agreements`
--

LOCK TABLES `service_level_agreements` WRITE;
/*!40000 ALTER TABLE `service_level_agreements` DISABLE KEYS */;
INSERT INTO `service_level_agreements` VALUES (1,1,1,'Correción de contenido publicado',NULL,'ALTA',2,12,99.90,30,120,720,'**Contrato de Nivel de Servicio (SLA)**\r\n\r\nEste acuerdo establece los compromisos de servicio entre las partes. El proveedor garantiza una disponibilidad mensual del 99.5%, excluyendo los periodos de mantenimiento programado comunicados con antelación.\r\n\r\nPara incidencias, el tiempo de respuesta no excederá las 4 horas hábiles para casos de alta prioridad, con una resolución máxima en 24 horas. Estos plazos están sujetos a la recepción de toda la información necesaria por parte del cliente.\r\n\r\nSe emitirá un informe mensual de desempeño. Quedan exentos de cumplimiento los fallos causados por fuerza mayor o por falta de colaboración oportuna del cliente.',1,'2025-11-05 19:57:38','2025-11-05 20:48:01',NULL),(2,3,8,'Correción de contenido de acuerdo con el requerimeinto asociado a la migración.',NULL,'CRITICA',2,8,99.90,30,150,480,'Creado automáticamente desde el formulario de solicitud de servicio',1,'2025-11-05 23:58:24','2025-11-06 00:16:10',NULL),(3,4,1,'Ajustes asociados a el menu principal','Todos ajustes del menú principal','MEDIA',1,4,99.90,30,60,240,'Creado automáticamente desde el formulario de solicitud de servicio',1,'2025-11-06 00:04:43','2025-11-06 00:04:43',NULL),(4,5,6,'SLA para Publicación de Banners en el Home del Portal Web SDM','Este acuerdo define los niveles de servicio para la gestión de solicitudes de publicación, actualización y despublicación de banners promocionales o informativos en la página de inicio (home) del Portal Web SDM, garantizando los tiempos de atención y la disponibilidad del servicio.','MEDIA',4,8,99.50,60,240,480,'<ul><li>Los tiempos de Aceptación, Respuesta y Resolución se calculan en horas hábiles (ej: de Lunes a Viernes, 9:00 a 18:00).</li><li>Las solicitudes deben incluir todos los assets finales (imágenes, textos, enlaces) y la aprobación correspondiente del área solicitante.</li><li>Para banners de campañas críticas o de alto impacto (ej: lanzamientos oficiales, comunicados urgentes), se puede activar un procedimiento de publicación express con tiempos reducidos, previa validación y priorización.</li><li>Este SLA no cubre el tiempo de diseño o desarrollo creativo del banner, solo el proceso de publicación una vez recibidos los materiales finales.</li><li>El cálculo de disponibilidad excluye ventanas de mantenimiento programado anunciado con 72 horas de antelación.</li></ul>',1,'2025-11-06 02:03:28','2025-11-06 02:03:28',NULL),(5,6,6,'SLA para Publicación de Documentos en el Portal Web SDM','Este acuerdo define los niveles de servicio para la gestión de solicitudes de publicación, actualización o eliminación de documentos (manuales, normativas, formularios, instructivos) en el Portal Web SDM. El servicio incluye la verificación del formato, la ubicación en la estructura del portal y la configuración de permisos de acceso según el requerimiento.','MEDIA',6,16,99.50,120,360,960,'<ul><li>Los tiempos de Aceptación, Respuesta y Resolución se calculan en horas hábiles (ej: de Lunes a Viernes, 9:00 a 18:00).</li><li>El tiempo de resolución puede variar significativamente dependiendo de la complejidad del requerimiento (ej.: publicar un PDF en una página existente vs. crear una nueva sección con múltiples documentos y permisos específicos).</li><li>La solicitud debe incluir el documento en versión final, la ubicación exacta deseada en el portal, la fecha de vigencia (si aplica) y el detalle de los perfiles de usuario que deben tener acceso.</li><li>No incluye la redacción, revisión legal o diseño gráfico del documento, solo su publicación técnica.</li><li>Para documentos de carácter Crítico (ej: normativas de cumplimiento obligatorio inmediato), se activará un canal prioritario con objetivos de resolución de 4 horas hábiles, sujeto a disponibilidad del equipo y aprobación por la gerencia.</li><li>El cálculo de disponibilidad excluye ventanas de mantenimiento programado anunciado con 72 horas de antelación.</li></ul>',1,'2025-11-06 03:07:14','2025-11-06 03:07:14',NULL),(6,7,6,'Tiempo de Publicación de Contenidos para el Portal Web SDM','Este SLA define los acuerdos de nivel de servicio para la gestión, revisión y publicación de noticias, PMTs (Planes de Manejo de Tránsito) o artículos solicitados para el Portal Web SDM, garantizando la disponibilidad de información actualizada y de calidad para los usuarios finales.','MEDIA',4,24,99.50,60,240,1440,'Los tiempos de resolución se cuentan a partir de la recepción de todo el contenido necesario y aprobado por el área solicitante.\r\n\r\n    Para publicaciones de alta prioridad (definidas por la Dirección de Comunicaciones), el tiempo de resolución se reduce a 8 horas laborales.\r\n\r\n    Este SLA no cubre la redacción o creación del contenido, solo su gestión editorial y publicación técnica en el portal.\r\n\r\n    Las solicitudes recibidas fuera del horario laboral se considerarán recibidas al inicio del siguiente día hábil.',1,'2025-11-06 20:17:52','2025-11-06 20:17:52',NULL),(7,8,4,'Cumplimiento normativo para desarrollos externos a la SDM',NULL,'MEDIA',8,48,99.90,60,480,2880,NULL,1,'2025-11-14 02:58:23','2025-11-14 02:58:23',NULL);
/*!40000 ALTER TABLE `service_level_agreements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `service_request_evidences`
--

DROP TABLE IF EXISTS `service_request_evidences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `service_request_evidences` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `service_request_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `evidence_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`evidence_data`)),
  `evidence_type` varchar(255) NOT NULL,
  `step_number` int(11) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_original_name` varchar(255) DEFAULT NULL,
  `file_mime_type` varchar(255) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `service_request_evidences_service_request_id_evidence_type_index` (`service_request_id`,`evidence_type`),
  KEY `service_request_evidences_service_request_id_step_number_index` (`service_request_id`,`step_number`),
  KEY `service_request_evidences_user_id_foreign` (`user_id`),
  CONSTRAINT `service_request_evidences_service_request_id_foreign` FOREIGN KEY (`service_request_id`) REFERENCES `service_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `service_request_evidences_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=97 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_request_evidences`
--

LOCK TABLES `service_request_evidences` WRITE;
/*!40000 ALTER TABLE `service_request_evidences` DISABLE KEYS */;
INSERT INTO `service_request_evidences` VALUES (46,24,'Solicitud Aceptada','La solicitud fue aceptada por William Sotaquirá','{\"action\":\"ACCEPTED\",\"accepted_by\":3,\"accepted_at\":\"2025-11-12T13:57:33.438772Z\",\"previous_status\":\"PENDIENTE\",\"new_status\":\"ACEPTADA\"}','SISTEMA',NULL,NULL,NULL,NULL,NULL,'2025-11-12 18:57:33','2025-11-12 18:57:33',NULL,NULL),(47,24,'Procesamiento Iniciado','El trabajo en la solicitud ha comenzado - Técnico: William Sotaquirá','{\"action\":\"STARTED\",\"started_by\":3,\"started_at\":\"2025-11-12T13:59:32.464282Z\",\"assigned_technician\":3,\"previous_status\":\"ACEPTADA\",\"new_status\":\"EN_PROCESO\"}','SISTEMA',NULL,NULL,NULL,NULL,NULL,'2025-11-12 18:59:32','2025-11-12 18:59:32',NULL,NULL),(48,24,'Screenshot 2025-11-12 at 09-07-11 Informes de Gestión y Evaluación SDM Secretaría Distrital de Movilidad.png','Archivo subido: Screenshot 2025-11-12 at 09-07-11 Informes de Gestión y Evaluación SDM Secretaría Distrital de Movilidad.png',NULL,'ARCHIVO',NULL,'evidences/service-request-24/1762956590_6914952e6429a_Screenshot 2025-11-12 at 09-07-11 Informes de Gestión y Evaluación SDM Secretaría Distrital de Movilidad.png','Screenshot 2025-11-12 at 09-07-11 Informes de Gestión y Evaluación SDM Secretaría Distrital de Movilidad.png','image/png',202980,'2025-11-12 19:09:50','2025-11-12 19:09:50',NULL,3),(49,24,'Solicitud Cerrada Definitivamente','La solicitud ha sido cerrada con 1 archivo adjuntos.','{\"action\":\"CLOSED\",\"closed_by\":3,\"closed_by_name\":\"William Sotaquir\\u00e1\",\"closed_at\":\"2025-11-12T14:10:47.118114Z\",\"previous_status\":\"RESUELTA\",\"new_status\":\"CERRADA\",\"ticket_number\":\"INF-PU-M-251112-001\",\"file_evidences_count\":1,\"files_verified\":true}','SISTEMA',NULL,NULL,NULL,NULL,NULL,'2025-11-12 19:10:47','2025-11-12 19:10:47',NULL,NULL),(50,25,'Solicitud Aceptada','La solicitud fue aceptada por William Sotaquirá','{\"action\":\"ACCEPTED\",\"accepted_by\":3,\"accepted_at\":\"2025-11-12T14:23:42.025676Z\",\"previous_status\":\"PENDIENTE\",\"new_status\":\"ACEPTADA\"}','SISTEMA',NULL,NULL,NULL,NULL,NULL,'2025-11-12 19:23:42','2025-11-12 19:23:42',NULL,NULL),(52,25,'Procesamiento Iniciado','Inicio de trabajo - Técnico: William Sotaquirá','{\"action\":\"STARTED\",\"started_by\":3,\"started_at\":\"2025-11-12T15:49:27.840779Z\",\"assigned_technician\":3,\"previous_status\":\"ACEPTADA\",\"new_status\":\"EN_PROCESO\"}','SISTEMA',NULL,NULL,NULL,NULL,NULL,'2025-11-12 20:49:27','2025-11-12 20:49:27',NULL,NULL),(53,25,'Procesamiento Iniciado','Inicio de trabajo - Técnico: William Sotaquirá','{\"action\":\"STARTED\",\"started_by\":3,\"started_at\":\"2025-11-12T15:54:13.169435Z\",\"assigned_technician\":3,\"previous_status\":\"ACEPTADA\",\"new_status\":\"EN_PROCESO\"}','SISTEMA',NULL,NULL,NULL,NULL,NULL,'2025-11-12 20:54:13','2025-11-12 20:54:13',NULL,NULL),(54,25,'Procesamiento Iniciado','Inicio de trabajo - Técnico: William Sotaquirá','{\"action\":\"STARTED\",\"started_by\":3,\"started_at\":\"2025-11-12T15:57:09.053248Z\",\"assigned_technician\":3,\"previous_status\":\"ACEPTADA\",\"new_status\":\"EN_PROCESO\"}','SISTEMA',NULL,NULL,NULL,NULL,NULL,'2025-11-12 20:57:09','2025-11-12 20:57:09',NULL,NULL),(55,25,'Procesamiento Iniciado','Inicio de trabajo - Técnico: William Sotaquirá','{\"action\":\"STARTED\",\"started_by\":3,\"started_at\":\"2025-11-12T16:01:38.381796Z\",\"assigned_technician\":3,\"previous_status\":\"ACEPTADA\",\"new_status\":\"EN_PROCESO\"}','SISTEMA',NULL,NULL,NULL,NULL,NULL,'2025-11-12 21:01:38','2025-11-12 21:01:38',NULL,NULL),(56,25,'Procesamiento Iniciado','Inicio de trabajo - Técnico: William Sotaquirá','{\"action\":\"STARTED\",\"started_by\":3,\"started_at\":\"2025-11-12T16:05:28.640079Z\",\"assigned_technician\":3,\"previous_status\":\"ACEPTADA\",\"new_status\":\"EN_PROCESO\"}','SISTEMA',NULL,NULL,NULL,NULL,NULL,'2025-11-12 21:05:28','2025-11-12 21:05:28',NULL,NULL),(57,25,'Procesamiento Iniciado','Inicio de trabajo - Técnico: William Sotaquirá','{\"action\":\"STARTED\",\"started_by\":3,\"started_at\":\"2025-11-12T16:38:41.187752Z\",\"assigned_technician\":3,\"previous_status\":\"ACEPTADA\",\"new_status\":\"EN_PROCESO\"}','SISTEMA',NULL,NULL,NULL,NULL,NULL,'2025-11-12 21:38:41','2025-11-12 21:38:41',NULL,NULL),(58,25,'Procesamiento Iniciado','Inicio de trabajo - Técnico: William Sotaquirá','{\"action\":\"STARTED\",\"started_by\":3,\"started_at\":\"2025-11-12T16:40:53.704001Z\",\"assigned_technician\":3,\"previous_status\":\"ACEPTADA\",\"new_status\":\"EN_PROCESO\"}','SISTEMA',NULL,NULL,NULL,NULL,NULL,'2025-11-12 21:40:53','2025-11-12 21:40:53',NULL,NULL),(59,25,'Procesamiento Iniciado','Inicio de trabajo - Técnico: William Sotaquirá','{\"action\":\"STARTED\",\"started_by\":3,\"started_at\":\"2025-11-12T16:41:44.585565Z\",\"assigned_technician\":3,\"previous_status\":\"ACEPTADA\",\"new_status\":\"EN_PROCESO\"}','SISTEMA',NULL,NULL,NULL,NULL,NULL,'2025-11-12 21:41:44','2025-11-12 21:41:44',NULL,NULL),(60,25,'Procesamiento Iniciado','Inicio de trabajo - Técnico: William Sotaquirá','{\"action\":\"STARTED\",\"started_by\":3,\"started_at\":\"2025-11-12T16:43:32.630291Z\",\"assigned_technician\":3,\"previous_status\":\"ACEPTADA\",\"new_status\":\"EN_PROCESO\"}','SISTEMA',NULL,NULL,NULL,NULL,NULL,'2025-11-12 21:43:32','2025-11-12 21:43:32',NULL,NULL),(61,25,'Procesamiento Iniciado','Inicio de trabajo - Técnico: William Sotaquirá','{\"action\":\"STARTED\",\"started_by\":3,\"started_at\":\"2025-11-12T16:46:25.827147Z\",\"assigned_technician\":3,\"previous_status\":\"ACEPTADA\",\"new_status\":\"EN_PROCESO\"}','SISTEMA',NULL,NULL,NULL,NULL,NULL,'2025-11-12 21:46:25','2025-11-12 21:46:25',NULL,NULL),(62,25,'Procesamiento Iniciado','Inicio de trabajo - Técnico: William Sotaquirá','{\"action\":\"STARTED\",\"started_by\":3,\"started_at\":\"2025-11-12T17:26:59.939117Z\",\"assigned_technician\":3,\"previous_status\":\"ACEPTADA\",\"new_status\":\"EN_PROCESO\"}','SISTEMA',NULL,NULL,NULL,NULL,NULL,'2025-11-12 22:26:59','2025-11-12 22:26:59',NULL,NULL),(63,25,'Procesamiento Iniciado','Inicio de trabajo - Técnico: William Sotaquirá','{\"action\":\"STARTED\",\"started_by\":3,\"started_at\":\"2025-11-12T17:27:32.722123Z\",\"assigned_technician\":3,\"previous_status\":\"ACEPTADA\",\"new_status\":\"EN_PROCESO\"}','SISTEMA',NULL,NULL,NULL,NULL,NULL,'2025-11-12 22:27:32','2025-11-12 22:27:32',NULL,NULL),(64,25,'Procesamiento Iniciado','Inicio de trabajo - Técnico: William Sotaquirá','{\"action\":\"STARTED\",\"started_by\":3,\"started_at\":\"2025-11-12T17:27:57.455084Z\",\"assigned_technician\":3,\"previous_status\":\"ACEPTADA\",\"new_status\":\"EN_PROCESO\"}','SISTEMA',NULL,NULL,NULL,NULL,NULL,'2025-11-12 22:27:57','2025-11-12 22:27:57',NULL,NULL),(65,25,'Procesamiento Iniciado','Inicio de trabajo - Técnico: William Sotaquirá','{\"action\":\"STARTED\",\"started_by\":3,\"started_at\":\"2025-11-12T17:29:13.128081Z\",\"assigned_technician\":3,\"previous_status\":\"ACEPTADA\",\"new_status\":\"EN_PROCESO\"}','SISTEMA',NULL,NULL,NULL,NULL,NULL,'2025-11-12 22:29:13','2025-11-12 22:29:13',NULL,NULL),(66,25,'Solicitud Aceptada','La solicitud fue aceptada por William Sotaquirá','{\"action\":\"ACCEPTED\",\"accepted_by\":3,\"accepted_at\":\"2025-11-12T17:40:54.624436Z\",\"previous_status\":\"PENDIENTE\",\"new_status\":\"ACEPTADA\"}','SISTEMA',NULL,NULL,NULL,NULL,NULL,'2025-11-12 22:40:54','2025-11-12 22:40:54',NULL,NULL),(67,25,'Procesamiento Iniciado','Inicio de trabajo - Técnico: William Sotaquirá','{\"action\":\"STARTED\",\"started_by\":3,\"started_at\":\"2025-11-12T17:41:00.316848Z\",\"assigned_technician\":3,\"previous_status\":\"ACEPTADA\",\"new_status\":\"EN_PROCESO\"}','SISTEMA',NULL,NULL,NULL,NULL,NULL,'2025-11-12 22:41:00','2025-11-12 22:41:00',NULL,NULL),(68,25,'Solicitud Rechazada','Prueba de rechazo de orden','{\"action\":\"REJECTED\",\"rejected_by\":3,\"rejected_at\":\"2025-11-12T20:34:21.125079Z\",\"rejection_reason\":\"Prueba de rechazo de orden\",\"previous_status\":\"PENDIENTE\",\"new_status\":\"RECHAZADA\"}','SISTEMA',NULL,NULL,NULL,NULL,NULL,'2025-11-13 01:34:21','2025-11-13 01:34:21',NULL,NULL),(69,25,'Solicitud Aceptada','La solicitud fue aceptada por William Sotaquirá','{\"action\":\"ACCEPTED\",\"accepted_by\":3,\"accepted_at\":\"2025-11-12T20:44:50.994830Z\",\"previous_status\":\"PENDIENTE\",\"new_status\":\"ACEPTADA\"}','SISTEMA',NULL,NULL,NULL,NULL,NULL,'2025-11-13 01:44:50','2025-11-13 01:44:50',NULL,NULL),(70,25,'Procesamiento Iniciado','Inicio de trabajo - Técnico: Jersson Hernandez','{\"action\":\"STARTED\",\"started_by\":3,\"started_at\":\"2025-11-12T20:47:36.668025Z\",\"assigned_technician\":5,\"previous_status\":\"ACEPTADA\",\"new_status\":\"EN_PROCESO\"}','SISTEMA',NULL,NULL,NULL,NULL,NULL,'2025-11-13 01:47:36','2025-11-13 01:47:36',NULL,NULL),(71,25,'Screenshot 2025-11-12 at 09-25-03 Bogotá reconoce a 26 empresas por su compromiso con una movilidad más sostenible y eficiente Secretaría Distrital de Movilidad.png','Archivo subido: Screenshot 2025-11-12 at 09-25-03 Bogotá reconoce a 26 empresas por su compromiso con una movilidad más sostenible y eficiente Secretaría Distrital de Movilidad.png',NULL,'ARCHIVO',NULL,'evidences/service-request-25/1762981373_6914f5fd502b2_Screenshot 2025-11-12 at 09-25-03 Bogotá reconoce a 26 empresas por su compromiso con una movilidad más sostenible y eficiente Secretaría Distrital de Movilidad.png','Screenshot 2025-11-12 at 09-25-03 Bogotá reconoce a 26 empresas por su compromiso con una movilidad más sostenible y eficiente Secretaría Distrital de Movilidad.png','image/png',857534,'2025-11-13 02:02:53','2025-11-13 02:02:53',NULL,3),(72,25,'Solicitud Reabierta','La solicitud ha sido reabierta para trabajo adicional.','{\"action\":\"REOPENED\",\"reopened_by\":3,\"reopened_at\":\"2025-11-12T22:39:41.944217Z\",\"previous_status\":\"EN_PROCESO\",\"new_status\":\"EN_PROCESO\"}','SISTEMA',NULL,NULL,NULL,NULL,NULL,'2025-11-13 03:39:41','2025-11-13 03:39:41',NULL,NULL),(73,25,'Técnico Reasignado','Sobre carga','{\"action\":\"REASSIGNED\",\"reassigned_by\":3,\"reassigned_at\":\"2025-11-12T23:10:51.801178Z\",\"previous_technician\":5,\"new_technician\":\"3\",\"reassignment_reason\":\"Sobre carga\"}','SISTEMA',NULL,NULL,NULL,NULL,NULL,'2025-11-13 04:10:51','2025-11-13 04:10:51',NULL,NULL),(74,26,'Solicitud Aceptada','La solicitud fue aceptada por William Sotaquirá','{\"action\":\"ACCEPTED\",\"accepted_by\":3,\"accepted_at\":\"2025-11-12T23:18:28.393701Z\",\"previous_status\":\"PENDIENTE\",\"new_status\":\"ACEPTADA\"}','SISTEMA',NULL,NULL,NULL,NULL,NULL,'2025-11-13 04:18:28','2025-11-13 04:18:28',NULL,NULL),(75,26,'Procesamiento Iniciado','Inicio de trabajo - Técnico: William Sotaquirá','{\"action\":\"STARTED\",\"started_by\":3,\"started_at\":\"2025-11-12T23:22:21.364618Z\",\"assigned_technician\":3,\"previous_status\":\"ACEPTADA\",\"new_status\":\"EN_PROCESO\"}','SISTEMA',NULL,NULL,NULL,NULL,NULL,'2025-11-13 04:22:21','2025-11-13 04:22:21',NULL,NULL),(76,26,'reporte-solicitud-INF-PU-M-251112-002.pdf','Archivo subido: reporte-solicitud-INF-PU-M-251112-002.pdf',NULL,'ARCHIVO',NULL,'evidences/service-request-26/1762989749_691516b5e06e0_reporte-solicitud-INF-PU-M-251112-002.pdf','reporte-solicitud-INF-PU-M-251112-002.pdf','application/pdf',888446,'2025-11-13 04:22:29','2025-11-13 04:22:29',NULL,3),(77,25,'Procesamiento Iniciado','Inicio de trabajo - Técnico: William Sotaquirá','{\"action\":\"STARTED\",\"started_by\":3,\"started_at\":\"2025-11-12T23:27:01.340518Z\",\"assigned_technician\":3,\"previous_status\":\"ACEPTADA\",\"new_status\":\"EN_PROCESO\"}','SISTEMA',NULL,NULL,NULL,NULL,NULL,'2025-11-13 04:27:01','2025-11-13 04:27:01',NULL,NULL),(78,26,'Screenshot 2025-11-10 at 08-26-31 planes institucionales y estratégicos.png','Archivo subido: Screenshot 2025-11-10 at 08-26-31 planes institucionales y estratégicos.png',NULL,'ARCHIVO',NULL,'evidences/service-request-26/1763004042_69154e8ae2c4a_Screenshot 2025-11-10 at 08-26-31 planes institucionales y estratégicos.png','Screenshot 2025-11-10 at 08-26-31 planes institucionales y estratégicos.png','image/png',392318,'2025-11-13 08:20:43','2025-11-13 08:20:43',NULL,3),(79,24,'Solicitud Aceptada','La solicitud fue aceptada por William Sotaquirá','{\"action\":\"ACCEPTED\",\"accepted_by\":3,\"accepted_at\":\"2025-11-13T04:01:50.135781Z\",\"previous_status\":\"PENDIENTE\",\"new_status\":\"ACEPTADA\"}','SISTEMA',NULL,NULL,NULL,NULL,NULL,'2025-11-13 09:01:50','2025-11-13 09:01:50',NULL,NULL),(80,24,'Procesamiento Iniciado','Inicio de trabajo - Técnico: William Sotaquirá','{\"action\":\"STARTED\",\"started_by\":3,\"started_at\":\"2025-11-13T04:11:13.581844Z\",\"assigned_technician\":3,\"previous_status\":\"ACEPTADA\",\"new_status\":\"EN_PROCESO\"}','SISTEMA',NULL,NULL,NULL,NULL,NULL,'2025-11-13 09:11:13','2025-11-13 09:11:13',NULL,NULL),(81,24,'Claudia_Diaz.jpg','Archivo subido: Claudia_Diaz.jpg',NULL,'ARCHIVO',NULL,'evidences/service-request-24/1763012113_69156e11a68ad_Claudia_Diaz.jpg','Claudia_Diaz.jpg','image/jpeg',88677,'2025-11-13 10:35:13','2025-11-13 10:35:13',NULL,3),(82,25,'DJI_0140.JPG','Archivo subido: DJI_0140.JPG',NULL,'ARCHIVO',NULL,'evidences/service-request-25/1763013277_6915729d04443_DJI_0140.JPG','DJI_0140.JPG','image/jpeg',4609013,'2025-11-13 10:54:37','2025-11-13 10:54:37',NULL,3),(83,27,'Solicitud Aceptada','La solicitud fue aceptada por William Sotaquirá','{\"action\":\"ACCEPTED\",\"accepted_by\":3,\"accepted_at\":\"2025-11-13T05:58:40.543319Z\",\"previous_status\":\"PENDIENTE\",\"new_status\":\"ACEPTADA\"}','SISTEMA',NULL,NULL,NULL,NULL,NULL,'2025-11-13 10:58:40','2025-11-13 10:58:40',NULL,NULL),(84,27,'naturalreflections.jpg','Archivo subido: naturalreflections.jpg',NULL,'ARCHIVO',NULL,'evidences/service-request-27/1763013556_691573b460e7d_naturalreflections.jpg','naturalreflections.jpg','image/jpeg',4610298,'2025-11-13 10:59:16','2025-11-13 10:59:16',NULL,3),(85,27,'Procesamiento Iniciado','Inicio de trabajo - Técnico: William Sotaquirá','{\"action\":\"STARTED\",\"started_by\":3,\"started_at\":\"2025-11-13T05:59:21.928902Z\",\"assigned_technician\":3,\"previous_status\":\"ACEPTADA\",\"new_status\":\"EN_PROCESO\"}','SISTEMA',NULL,NULL,NULL,NULL,NULL,'2025-11-13 10:59:21','2025-11-13 10:59:21',NULL,NULL),(86,27,'vastforest.jpg','Archivo subido: vastforest.jpg',NULL,'ARCHIVO',NULL,'evidences/service-request-27/1763013794_691574a2be6b8_vastforest.jpg','vastforest.jpg','image/jpeg',2565834,'2025-11-13 11:03:14','2025-11-13 11:03:14',NULL,3),(87,27,'logo22.png','Archivo subido: logo22.png',NULL,'ARCHIVO',NULL,'evidences/service-request-27/1763013883_691574fb86a36_logo22.png','logo22.png','image/png',9219,'2025-11-13 11:04:43','2025-11-13 11:04:43',NULL,3),(88,27,'logo11.png','Archivo subido: logo11.png',NULL,'ARCHIVO',NULL,'evidences/SR27-20251113-061226-466111.png','logo11.png','image/png',28092,'2025-11-13 11:12:26','2025-11-13 11:12:26',NULL,3),(89,25,'20251069-6-7-1292.png','Archivo subido: 20251069-6-7-1292.png',NULL,'ARCHIVO',NULL,'evidences/SR25-20251113-061342-225138.png','20251069-6-7-1292.png','image/png',189245,'2025-11-13 11:13:42','2025-11-13 11:13:42',NULL,3),(90,27,'Firma.png','Archivo subido: Firma.png',NULL,'ARCHIVO',NULL,'evidences/SR27-20251113-062910-504226.png','Firma.png','image/png',18394,'2025-11-13 11:29:10','2025-11-13 11:29:10',NULL,3),(91,27,'20251069-4-7-1319.png','Archivo subido: 20251069-4-7-1319.png',NULL,'ARCHIVO',NULL,'evidences/SR27-20251113-063530-301336.png','20251069-4-7-1319.png','image/png',70316,'2025-11-13 11:35:30','2025-11-13 11:35:30',NULL,3),(92,24,'SR24-20251113-064034-343388.png','Archivo subido: calidad.png',NULL,'ARCHIVO',NULL,'evidences/SR24-20251113-064034-343388.png','calidad.png','image/png',25511,'2025-11-13 11:40:34','2025-11-13 11:40:34',NULL,3),(93,30,'Solicitud Aceptada','La solicitud fue aceptada por William Sotaquirá','{\"action\":\"ACCEPTED\",\"accepted_by\":3,\"accepted_at\":\"2025-11-13T17:15:27.659730Z\",\"previous_status\":\"PENDIENTE\",\"new_status\":\"ACEPTADA\"}','SISTEMA',NULL,NULL,NULL,NULL,NULL,'2025-11-13 22:15:27','2025-11-13 22:15:27',NULL,NULL),(94,30,'Procesamiento Iniciado','Inicio de trabajo - Técnico: William Sotaquirá','{\"action\":\"STARTED\",\"started_by\":3,\"started_at\":\"2025-11-13T17:15:33.229861Z\",\"assigned_technician\":3,\"previous_status\":\"ACEPTADA\",\"new_status\":\"EN_PROCESO\"}','SISTEMA',NULL,NULL,NULL,NULL,NULL,'2025-11-13 22:15:33','2025-11-13 22:15:33',NULL,NULL),(95,30,'SR30-20251113-171557-572213.pdf','Archivo subido: 20251069_2025_10.pdf',NULL,'ARCHIVO',NULL,'evidences/SR30-20251113-171557-572213.pdf','20251069_2025_10.pdf','application/pdf',782093,'2025-11-13 22:15:57','2025-11-13 22:15:57',NULL,3),(96,30,'SR30-20251113-171857-373313.png','Archivo subido: Screenshot 2025-11-13 at 12-18-40 informe de octubre 2025 - wsotaquira@movilidadbogota.gov.co - Correo de Bogotá es TIC.png',NULL,'ARCHIVO',NULL,'evidences/SR30-20251113-171857-373313.png','Screenshot 2025-11-13 at 12-18-40 informe de octubre 2025 - wsotaquira@movilidadbogota.gov.co - Correo de Bogotá es TIC.png','image/png',40423,'2025-11-13 22:18:57','2025-11-13 22:18:57',NULL,3);
/*!40000 ALTER TABLE `service_request_evidences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `service_requests`
--

DROP TABLE IF EXISTS `service_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `service_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `requester_id` bigint(20) unsigned DEFAULT NULL,
  `ticket_number` varchar(255) NOT NULL,
  `sla_id` bigint(20) unsigned NOT NULL,
  `sub_service_id` bigint(20) unsigned NOT NULL,
  `requested_by` bigint(20) unsigned DEFAULT NULL,
  `assigned_to` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `web_routes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`web_routes`)),
  `main_web_route` varchar(255) DEFAULT NULL,
  `criticality_level` enum('BAJA','MEDIA','ALTA','URGENTE','CRITICA') NOT NULL DEFAULT 'MEDIA',
  `status` enum('PENDIENTE','ACEPTADA','EN_PROCESO','PAUSADA','RESUELTA','CERRADA','CANCELADA','RECHAZADA') NOT NULL DEFAULT 'PENDIENTE',
  `acceptance_deadline` timestamp NULL DEFAULT NULL,
  `response_deadline` timestamp NULL DEFAULT NULL,
  `resolution_deadline` timestamp NULL DEFAULT NULL,
  `accepted_at` timestamp NULL DEFAULT NULL,
  `responded_at` timestamp NULL DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `rejected_by` bigint(20) unsigned DEFAULT NULL,
  `satisfaction_score` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `is_paused` tinyint(1) NOT NULL DEFAULT 0,
  `pause_reason` text DEFAULT NULL,
  `paused_by` bigint(20) unsigned DEFAULT NULL,
  `paused_at` timestamp NULL DEFAULT NULL,
  `resumed_at` timestamp NULL DEFAULT NULL,
  `total_paused_minutes` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `service_requests_ticket_number_unique` (`ticket_number`),
  KEY `service_requests_sla_id_foreign` (`sla_id`),
  KEY `service_requests_sub_service_id_foreign` (`sub_service_id`),
  KEY `service_requests_requested_by_foreign` (`requested_by`),
  KEY `service_requests_assigned_to_foreign` (`assigned_to`),
  KEY `service_requests_paused_by_foreign` (`paused_by`),
  KEY `service_requests_rejected_at_index` (`rejected_at`),
  KEY `service_requests_rejected_by_index` (`rejected_by`),
  KEY `service_requests_requester_id_index` (`requester_id`),
  CONSTRAINT `service_requests_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`),
  CONSTRAINT `service_requests_paused_by_foreign` FOREIGN KEY (`paused_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `service_requests_rejected_by_foreign` FOREIGN KEY (`rejected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `service_requests_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`),
  CONSTRAINT `service_requests_requester_id_foreign` FOREIGN KEY (`requester_id`) REFERENCES `requesters` (`id`),
  CONSTRAINT `service_requests_sla_id_foreign` FOREIGN KEY (`sla_id`) REFERENCES `service_level_agreements` (`id`),
  CONSTRAINT `service_requests_sub_service_id_foreign` FOREIGN KEY (`sub_service_id`) REFERENCES `sub_services` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=72 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_requests`
--

LOCK TABLES `service_requests` WRITE;
/*!40000 ALTER TABLE `service_requests` DISABLE KEYS */;
INSERT INTO `service_requests` VALUES (24,NULL,'INF-PU-M-251112-001',5,22,3,3,'Fwd: Solicitud publicación página web Informe trimestral Vigencias Futuras contrato SDM-2025-2989','Mediante el presente me permito solicitar la colaboración en la publicación en la página web de la Entidad del Informe Trimestral de Vigencias Futuras (23-jul-25 al 30-sep-25) para el contrato SDM-2025-2989 (ver adjunto), en la siguiente ruta:\n\n    Sección Transparencia y acceso a la Información - Planeación, presupuesto e informes - Informes de gestión, evaluación y auditoría - Informe de gestión - Informes de ejecución de Vigencias Futuras o en https://www.movilidadbogota.gov.co/web/informes_de_gestion.\n\n\nSi se debe realizar algún procedimiento agradezco se me informe, gracias por la atención prestada.','[]',NULL,'MEDIA','CERRADA',NULL,NULL,NULL,'2025-11-13 09:01:50',NULL,'2025-11-13 10:24:14','2025-11-13 10:24:35','=== CIERRE NORMAL ===\nFecha/Hora: 13/11/2025 05:24:35\nUsuario: ID 3',NULL,NULL,NULL,NULL,'2025-11-12 18:55:45','2025-11-13 10:24:35',NULL,0,NULL,NULL,NULL,NULL,0),(25,NULL,'INF-PU-M-251112-002',6,23,3,3,'Nota página web','Bogotá reconoce a 26 empresas por su compromiso con una movilidad más sostenible y eficiente','[]',NULL,'MEDIA','CERRADA',NULL,NULL,NULL,'2025-11-13 01:44:50',NULL,'2025-11-13 04:27:16','2025-11-13 04:27:46','=== CIERRE NORMAL ===\nFecha/Hora: 12/11/2025 23:27:46\nUsuario: ID 3','Prueba de rechazo de orden','2025-11-13 01:34:21',3,NULL,'2025-11-12 19:23:17','2025-11-13 04:27:46',NULL,0,NULL,NULL,'2025-11-13 03:41:02','2025-11-13 03:41:05',76),(26,NULL,'SUP-IN-C-251112-001',2,32,3,3,'Mejoras de rendimiento','Estas son unas mejores rendimiento de prueba','[]',NULL,'URGENTE','CERRADA',NULL,NULL,NULL,'2025-11-13 04:18:28',NULL,'2025-11-13 04:24:32','2025-11-13 04:24:55','Se presentó el problema por sobre escritura de coheima\n\n=== CIERRE NORMAL ===\nFecha/Hora: 12/11/2025 23:24:55\nUsuario: ID 3',NULL,NULL,NULL,NULL,'2025-11-13 04:18:16','2025-11-13 04:24:55',NULL,0,NULL,NULL,'2025-11-13 04:22:40','2025-11-13 04:23:23',1),(27,NULL,'WEB-ER-H-251113-001',1,1,3,3,'Nocturno','Esta es una solicitud nocturna de prueba','[]',NULL,'ALTA','CERRADA',NULL,NULL,NULL,'2025-11-13 10:58:40',NULL,'2025-11-13 10:59:43','2025-11-13 10:59:55','=== CIERRE NORMAL ===\nFecha/Hora: 13/11/2025 05:59:55\nUsuario: ID 3',NULL,NULL,NULL,NULL,'2025-11-13 10:58:19','2025-11-13 10:59:55',NULL,0,NULL,NULL,NULL,NULL,0),(28,NULL,'WEB-ED-M-251113-001',1,5,3,NULL,'Link roto','Mediante la presente se solicita formalmente la corrección de un enlace roto identificado en el sitio web de la Secretaría de Movilidad (https://www.movilidadbogota.gov.co/consulta-de-comparendos), específicamente en el botón \"Consulta aquí la Tabla de autoliquidación de infracciones 2025\", el cual redirige a los usuarios a una página de error (404) en lugar de la tabla correspondiente, afectando un proceso clave de consulta para la ciudadanía.','[]',NULL,'MEDIA','PENDIENTE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-13 20:36:24','2025-11-13 20:36:24',NULL,0,NULL,NULL,NULL,NULL,0),(29,NULL,'INF-PU-M-251113-001',6,23,3,NULL,'Conozca los cierres viales y desvíos por la feria “EXPOCUNDINAMARCA 2025” External','Publicación de nota de prensa en el portal web de la entidad','[]',NULL,'MEDIA','PENDIENTE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-13 20:38:19','2025-11-13 20:38:19',NULL,0,NULL,NULL,NULL,NULL,0),(30,NULL,'COM-RE-C-251113-001',1,8,3,3,'informe de octubre 2025','Mediante la presente se formaliza por tercera vez la solicitud para realizar el cargue en la carpeta de contratación y posterior publicación en SECOP del informe con sus anexos correspondiente al mes de octubre, con el fin de atender de manera urgente el hallazgo de Control Interno y dar cumplimiento al Plan de Mejora liderado por la señora Milena, dado que a la fecha de este mensaje los documentos aún no han sido publicados.','[]',NULL,'CRITICA','CERRADA',NULL,NULL,NULL,'2025-11-13 22:15:27',NULL,'2025-11-13 22:16:34','2025-11-13 22:16:54','=== CIERRE NORMAL ===\nFecha/Hora: 13/11/2025 17:16:54\nUsuario: ID 3',NULL,NULL,NULL,NULL,'2025-11-13 20:41:51','2025-11-13 22:16:54',NULL,0,NULL,NULL,NULL,NULL,0),(31,NULL,'COM-AC-H-251113-001',1,6,3,NULL,'Solicitud urgente ajuste organigrama','Por medio de la presente se solicita formalmente la actualización del organigrama institucional tanto en el portal web público como en la intranet, utilizando para ello la versión más reciente que se adjunta a esta solicitud, con el fin de garantizar que la información organizacional que se divulga sea precisa y esté al día.','[]',NULL,'ALTA','PENDIENTE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-13 20:43:11','2025-11-13 20:43:11',NULL,0,NULL,NULL,NULL,NULL,0),(32,NULL,'INF-PU-H-251113-001',6,23,3,NULL,'Nota página web','Se solicita la publicación en la página web de la nota titulada \"Bogotá reconoce a 26 empresas por su compromiso con una movilidad más sostenible y eficiente\".','[]',NULL,'ALTA','PENDIENTE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-13 21:24:47','2025-11-13 21:24:47',NULL,0,NULL,NULL,NULL,NULL,0),(33,NULL,'COM-AS-H-251113-001',1,7,3,NULL,'Revisión y Actualización de Contenido Web Solicitada mediante Radicado','Se requiere realizar la revisión y actualización del contenido de la página web institucional, conforme a lo reportado en el radicado ORFEO 202561203806162, el cual contiene una queja sobre información desactualizada. Esta acción tiene como fecha de vencimiento el 10 de noviembre de 2025.','[]',NULL,'ALTA','PENDIENTE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-13 21:29:47','2025-11-13 21:29:47',NULL,0,NULL,NULL,NULL,NULL,0),(34,NULL,'WEB-ED-M-251113-002',1,5,1,NULL,'Corrección de Enlaces Rotos Identificados en Reporte de Octubre','De acuerdo con el reporte del mes de octubre, se identificaron 17 enlaces rotos de un total de 118 revisados, por lo que se solicita proceder con la corrección y restauración de dichos enlaces para garantizar la funcionalidad y accesibilidad del contenido web.','[]',NULL,'MEDIA','PENDIENTE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-13 21:58:27','2025-11-13 21:58:27',NULL,0,NULL,NULL,NULL,NULL,0),(35,NULL,'WEB-ED-H-251113-001',1,5,3,NULL,'Habilitar Enlace para Subasta Pública No. 30 Lote 1','Se solicita habilitar en la página web https://www.movilidadbogota.gov.co/web/subasta-abandonados el enlace https://naveltda.com.co/producto/mil-doscientos-diez-1-210-automotores-catalogados-como-chatarra-desintegracion-s-d-m/ para dar apertura a la Base de Datos de la Subasta Pública No. 30, Lote 1.','[]',NULL,'ALTA','PENDIENTE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-14 00:45:27','2025-11-14 00:45:27',NULL,0,NULL,NULL,NULL,NULL,0),(36,NULL,'SUP-IN-C-251113-001',2,32,3,NULL,'Formularios activos y con usuarios en el portal','Se adjunta la tabla de formularios activos con sus respectivos correos responsables y usuarios del portal, con el fin de realizar el análisis correspondiente y establecer, en conjunto con los equipos funcionales, los procedimientos y flujos de trabajo que se implementarán para la gestión adecuada de esta data.','[]',NULL,'CRITICA','PENDIENTE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-14 01:40:18','2025-11-14 01:40:18',NULL,0,NULL,NULL,NULL,NULL,0),(37,NULL,'INF-PU-M-251113-002',4,34,3,NULL,'Actualización banner Transporte Esepecial','Se solicita actualizar el banner de la convocatoria de Transporte Especial en la página web para reflejar que la fecha ha sido ampliada hasta el 31 de octubre.','[]',NULL,'MEDIA','PENDIENTE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-14 01:41:21','2025-11-14 01:41:21',NULL,0,NULL,NULL,NULL,NULL,0),(38,NULL,'SUP-MI-M-251113-001',1,31,3,NULL,'Directorios movidos portal principal','Se realizó la migración del document root de /var/www/html a /data en el ambiente de QA, copiando los directorios de contenido (Página básica, Avisos y procesos de contratación, Preguntas Frecuentes, PMT, Proyecto de Acuerdo y conceptos jurídicos) desde sus ubicaciones de origen en /mnt/portales hacia el nuevo destino /data/Portal_Web/web/sites/default/files/.','[]',NULL,'MEDIA','PENDIENTE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-14 01:42:09','2025-11-14 01:42:09',NULL,0,NULL,NULL,NULL,NULL,0),(39,NULL,'SUP-IN-C-251113-002',2,32,3,NULL,'Hallazgos migración','Se reportan los hallazgos identificados en la migración a QA de varias secciones del portal web, incluyendo desorden en la presentación de contenidos (no ordenados por fecha de publicación como en el origen), falta de información como descripciones y anexos en procesos de contratación, nombres de archivos con caracteres especiales en PMT, iconos de PDF flotantes sin enlaces, y la no migración completa de la sección de avisos electrónicos, requiriéndose la corrección integral de estas observaciones.','[]',NULL,'CRITICA','PENDIENTE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-14 01:42:57','2025-11-14 01:42:57',NULL,0,NULL,NULL,NULL,NULL,0),(40,NULL,'INF-PU-M-251113-003',6,23,3,NULL,'Nota página web','Se solicita publicar en el portal web la noticia cuyo contenido se encuentra en el siguiente enlace: https://docs.google.com/document/d/1Rcnel9E3wRX82D-CG8IiTpGKKeebys6-/edit#heading=h.q54hpvxn3bgv, realizando todas las configuraciones necesarias para su correcta visualización y disponibilidad.','[]',NULL,'MEDIA','PENDIENTE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-14 01:47:17','2025-11-14 01:47:17',NULL,0,NULL,NULL,NULL,NULL,0),(41,NULL,'INF-PU-M-251113-004',4,34,3,NULL,'URGENTE - Publicación hoy piezas subasta No. 30','Se requiere realizar con carácter urgente la publicación de los banners actualizados para la Subasta Pública No. 30 en el portal web, los cuales deben enlazar correctamente a la base de datos de la subasta y reflejar la información actualizada sobre los 1.210 vehículos, con cierre de ofertas el 22 de octubre y adjudicación el 23 de octubre de 2025.','[]',NULL,'MEDIA','PENDIENTE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-14 01:48:34','2025-11-14 01:48:34',NULL,0,NULL,NULL,NULL,NULL,0),(42,NULL,'INF-PU-M-251113-005',6,23,3,NULL,'Nota - Página web','Se solicita publicar en la página web la nota cuyo contenido se encuentra en el siguiente enlace: https://docs.google.com/document/d/1WFa2PcueRSlcw3eHmyR9NY-NAB97wffL/edit, realizando todas las configuraciones necesarias para su correcta visualización y disponibilidad.','[]',NULL,'MEDIA','PENDIENTE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-14 01:49:23','2025-11-14 01:49:23',NULL,0,NULL,NULL,NULL,NULL,0),(43,NULL,'COM-PU-M-251113-001',1,9,3,NULL,'Fwd: Solicitud publicación página web Informe trimestral Vigencias Futuras contrato SDM-2025-2989','Se solicita publicar en la página web el Informe Trimestral de Vigencias Futuras correspondiente al contrato SDM-2025-2989 (periodo del 23-jul-25 al 30-sep-25) en la sección de Transparencia, específicamente en la ruta Planeación, presupuesto e informes/Informes de gestión, evaluación y auditoría/Informe de gestión/Informes de ejecución de Vigencias Futuras, o en la URL https://www.movilidadbogota.gov.co/web/informes_de_gestion.','[]',NULL,'MEDIA','PENDIENTE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-14 01:50:11','2025-11-14 01:50:11',NULL,0,NULL,NULL,NULL,NULL,0),(44,NULL,'WEB-ED-M-251113-003',1,5,3,NULL,'Publicación de Video sobre Canales de Contacto','Se solicita publicar en la página web el video titulado \"Canales de contacto 2025 1.2 subti e interp.mp4\", el cual cuenta con subtítulos e interpretación en lengua de señas y ha sido aprobado para su difusión.','[]',NULL,'MEDIA','PENDIENTE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-14 01:52:21','2025-11-14 01:52:21',NULL,0,NULL,NULL,NULL,NULL,0),(45,NULL,'INF-PU-M-251113-006',6,23,3,NULL,'Favor publicación nota web - Salida de patios','Se solicita la publicación de la nota web adjunta sobre \"Salida de patios\" en el portal institucional, preferiblemente esta tarde o mañana a primera hora, y posteriormente enviar el enlace correspondiente para su distribución a medios de comunicación.','[]',NULL,'MEDIA','PENDIENTE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-14 02:29:12','2025-11-14 02:29:12',NULL,0,NULL,NULL,NULL,NULL,0),(46,NULL,'COM-AS-M-251113-001',1,7,3,NULL,'Re: Actualización caracterización de procesos','Se solicita realizar la actualización de la documentación de caracterización de procesos en el sistema MIPG, utilizando como referencia la información disponible en el enlace https://drive.google.com/drive/folders/16z7sKkLC2g6ysXS3HEGq1EZB9B89rxso?usp=sharing, con fecha límite para el cargue de documentos reprogramada para el viernes 14 de noviembre.','[]',NULL,'MEDIA','PENDIENTE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-14 02:32:01','2025-11-14 02:32:01',NULL,0,NULL,NULL,NULL,NULL,0),(47,NULL,'INF-PU-M-251113-007',6,23,3,NULL,'Oficina Asesora de Comunicaciones y Cultura para la Movilidad','Se solicita publicar en el portal web el PMT titulado \"Conozca el cierre total de la Av. Carrera 68 por Autopista sur\", el cual contiene información relevante sobre las restricciones viales para conocimiento de la ciudadanía.','[]',NULL,'MEDIA','PENDIENTE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-14 02:34:09','2025-11-14 02:34:09',NULL,0,NULL,NULL,NULL,NULL,0),(48,NULL,'SUP-MI-M-251113-002',1,31,3,NULL,'Confirmación de Validación de Contenido para Nuevo Portal Web','Se informa que la Dirección de Ingeniería de Tránsito ha realizado la revisión del contenido del nuevo portal web relacionado con sus competencias y da conformidad con la información publicada, indicando que la información dirigida a ciudadanía es gestionada principalmente por las Subdirecciones de Señalización y de Planes de Manejo de Tránsito, quienes realizarán sus respectivas validaciones.','[]',NULL,'MEDIA','PENDIENTE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-14 02:36:36','2025-11-14 02:36:36',NULL,0,NULL,NULL,NULL,NULL,0),(49,NULL,'INF-PU-M-251113-008',6,23,3,NULL,'Publicación de Nota Web sobre Comparendos','Se solicita publicar en el portal web la nota titulada \"¿Qué hacer cuando le imponen un comparendo?\" y posteriormente enviar el enlace correspondiente para su divulgación a través de los canales establecidos.','[]',NULL,'MEDIA','PENDIENTE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-14 02:38:53','2025-11-14 02:38:53',NULL,0,NULL,NULL,NULL,NULL,0),(50,NULL,'SUP-MI-M-251113-003',1,31,3,NULL,'Gestión de Creación de Vistas para Masivos Faltantes','Se requiere generar una nueva solicitud de requerimiento desde la Oficina de Comunicaciones para gestionar a través del contrato de fábrica de software la creación de las vistas faltantes para los masivos identificados, ya que estos no fueron incluidos en el archivo base de la migración suministrado el 24 de julio.','[]',NULL,'MEDIA','PENDIENTE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-14 02:40:36','2025-11-14 02:40:36',NULL,0,NULL,NULL,NULL,NULL,0),(51,NULL,'SUP-IN-C-251113-003',2,32,3,NULL,'Revisión y Corrección de Observaciones en Nuevo Portal Web SDM','Se requiere revisar y subsanar las observaciones identificadas durante la revisión general del menú de transparencia en el nuevo portal web, solicitando adicionalmente que cada responsable de numeral realice la validación específica de su contenido para garantizar el correcto funcionamiento del sitio.','[]',NULL,'CRITICA','PENDIENTE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-14 02:45:19','2025-11-14 02:45:19',NULL,0,NULL,NULL,NULL,NULL,0),(52,NULL,'WEB-AC-H-251113-001',7,35,3,NULL,'URGENTE - Validación accesibilidad web VUS','Se requiere entregar con carácter urgente, para mañana, el concepto técnico sobre las recomendaciones de colorimetría que garantice el cumplimiento de los estándares de accesibilidad web AAA en el portal transaccional de la Ventanilla Única de Servicios, utilizando los colores institucionales establecidos.','[]',NULL,'ALTA','PENDIENTE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-14 02:59:43','2025-11-14 02:59:43',NULL,0,NULL,NULL,NULL,NULL,0),(53,NULL,'WEB-ED-M-251113-004',1,5,3,NULL,'Calendario Pico y Placa Noviembre','Se solicita publicar en el portal web el calendario de Pico y Placa correspondiente al mes de noviembre, el cual se encuentra adjunto en el presente correo electrónico.','[]',NULL,'MEDIA','PENDIENTE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-14 03:01:16','2025-11-14 03:01:16',NULL,0,NULL,NULL,NULL,NULL,0),(54,NULL,'SUP-IN-C-251113-004',2,32,3,NULL,'Respuesta a memorando OACCM-202511000215863 - Instrucciones para la Validación de Contenidos en el Nuevo Portal Web','Se requiere completar la migración de toda la información del micrositio anterior de la Subdirección de Planes de Manejo de Tránsito que actualmente no se visualiza en el nuevo portal, incluyendo las publicaciones de respuestas a solicitudes de PMT y la sección de PMT por eventos. Es necesario rectificar la exactitud de la información publicada, incorporando los costos actualizados en UVB y pesos para cada tipo de trámite de PMT (obras y eventos según su nivel de impacto o complejidad), junto con los tiempos de resolución de 8 o 15 días, y ajustar el texto instructivo para la ciudadanía en la URL correspondiente. Adicionalmente, se debe garantizar la correcta funcionalidad de todos los enlaces y reubicar en un botón específico de \'cierres\' la información de constante consulta pública que se alojaba en el enlace antiguo de Cierres, así como integrar los esquemas típicos que tampoco son visibles en la nueva plataforma.','[]',NULL,'CRITICA','PENDIENTE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-14 03:05:40','2025-11-14 03:05:40',NULL,0,NULL,NULL,NULL,NULL,0),(55,NULL,'WEB-ED-M-251113-005',1,5,3,NULL,'Piezas carga PyP noviembre de 2025','Actualización de Piezas de Restricción de Transporte de Carga para el Portal Web\r\n\r\nProceder con la publicación de las piezas de restricción de transporte de carga para el mes de noviembre de 2025, las cuales han sido recibidas y deben ser integradas en la sección correspondiente del portal web para su debida difusión pública.','[]',NULL,'MEDIA','PENDIENTE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-14 03:06:57','2025-11-14 03:06:57',NULL,0,NULL,NULL,NULL,NULL,0),(56,NULL,'SUP-IN-C-251113-005',2,32,3,NULL,'Validación de contenidos nuevo portal Web- Observaciones SI','Mejora de la Funcionalidad de Búsqueda y Actualización de Contenidos para la Subdirección de Infraestructura\r\n\r\nImplementar las correcciones y observaciones detalladas en el documento \"Validación de contenidos en el nuevo portal web_Observaciones SI\" para garantizar la integridad, exactitud y ubicación correcta de toda la información bajo la competencia de la Subdirección de Infraestructura. Adicionalmente, se debe optimizar la precisión del módulo de búsqueda del portal web para que filtre eficazmente los resultados utilizando palabras clave o el nombre completo de los recursos solicitados, mejorando así su funcionalidad y usabilidad para el usuario.','[]',NULL,'CRITICA','PENDIENTE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-14 03:08:12','2025-11-14 03:08:12',NULL,0,NULL,NULL,NULL,NULL,0),(57,NULL,'COM-RE-M-251113-001',1,8,3,NULL,'relacionamiento','Actualizar el documento de relacionamiento','[]',NULL,'MEDIA','PENDIENTE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-14 03:14:55','2025-11-14 03:14:55',NULL,0,NULL,NULL,NULL,NULL,0),(58,NULL,'SUP-IN-C-251113-006',2,32,3,NULL,'Respuesta oficio 202511000215863','Restauración de la Sección de Defensa Judicial en el Portal Web\r\n\r\nVerificar y restaurar la información correspondiente a los \"Reportes y bases de datos sobre Defensa Pública y Prevención del Daño Antijurídico\" de la Dirección de Representación Judicial, la cual no se encuentra disponible en el nuevo portal web, asegurando que el contenido del enlace original sea migrado e integrado correctamente para su consulta pública.','[]',NULL,'CRITICA','PENDIENTE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-14 03:15:51','2025-11-14 03:15:51',NULL,0,NULL,NULL,NULL,NULL,0),(59,NULL,'SUP-MI-M-251113-004',1,31,3,NULL,'Página Web - Depuración de archivos External','Migración de Archivos y Configuración de Enlaces para el Portal Web en Producción\r\n\r\nCompletar la migración de la carpeta \"electrónicos\" y cualquier otro directorio pendiente a la ruta document root /data/Portal_Web/web del servidor Apache, verificando que los permisos de acceso estén correctamente configurados para garantizar la disponibilidad de todos los archivos. Se debe confirmar que los enlaces y custom tokens apunten correctamente a las nuevas ubicaciones, permitiendo la descarga y visualización de documentos como los masivos organizados de PMT, notificaciones de comparendos electrónicos y procesos de contratación, asegurando así el funcionamiento integral del portal web para su puesta en producción.','[]',NULL,'MEDIA','PENDIENTE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-14 03:19:00','2025-11-14 03:19:00',NULL,0,NULL,NULL,NULL,NULL,0),(60,NULL,'SUP-IN-C-251113-007',2,32,3,NULL,'Asignación Radicado # 202561200220013 SA Respuesta a memorando 202511000215863','Migración y Habilitación de Contenidos Esenciales de la Subdirección Administrativa\r\n\r\nCompletar la migración y verificación de las 15 URLs a cargo de la Subdirección Administrativa, publicando los informes de vigencias futuras 2025 y de austeridad del segundo y tercer trimestre de 2025, habilitando la descarga de los archivos del SIC, las políticas de gestión ambiental, documental y uso de papel, así como los documentos de eliminaciones documentales y TRD, y ubicando correctamente las secciones de manuales de gestión, respuestas a oficios sin notificar y la página principal de la subdirección, garantizando la integridad, exactitud y funcionalidad de todo el contenido esencial.','[]',NULL,'CRITICA','PENDIENTE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-14 03:22:08','2025-11-14 03:22:08',NULL,0,NULL,NULL,NULL,NULL,0),(61,NULL,'SUP-IN-C-251113-008',2,32,3,NULL,'validación contenido nuevo portal web - Oficina de Gestión Social','Corrección Integral de Enlaces y Contenidos para la Oficina de Gestión Social\r\n\r\nReparar todos los enlaces que no funcionan en las secciones de transparencia y participación, incluyendo el directorio de agremiaciones, los planes de acción, la rendición de cuentas, los espacios de participación ciudadana, el control social, los reportes de gestión local y la información para grupos de interés, asegurando que cada enlace abra correctamente. Se debe actualizar la exactitud e integridad de toda la información descriptiva para que coincida con el portal vigente, publicar la Cartilla pedagógica para el enfoque diferencial, habilitar la visualización del Plan Institucional de Participación Ciudadana SDM 2025, completar los informes de rendición de cuentas y agregar el boletín del tercer trimestre en los reportes de gestión local para todas las localidades.','[]',NULL,'CRITICA','PENDIENTE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-14 03:25:45','2025-11-14 03:25:45',NULL,0,NULL,NULL,NULL,NULL,0),(62,NULL,'SUP-IN-C-251113-009',2,32,3,NULL,'Validación de Contenidos | Memorando No. 202511000215863','Revisión e Implementación de Observaciones de la Oficina de Seguridad Vial para el Nuevo Portal Web\r\n\r\nAnalizar el memorando No. 202513000218913 de la Oficina de Seguridad Vial y ejecutar todas las acciones correctivas, ajustes de contenido y migración de información que allí se especifiquen para garantizar la integridad, exactitud y funcionalidad de los contenidos de la OSV en el nuevo portal web, asegurando el cumplimiento de los lineamientos establecidos.','[]',NULL,'CRITICA','PENDIENTE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-14 03:28:00','2025-11-14 03:28:00',NULL,0,NULL,NULL,NULL,NULL,0),(63,NULL,'SUP-IN-C-251113-010',2,32,3,NULL,'INSTRUCCIONES PARA LA VALIDACION DE CONTENIDOS EN EL NUEVO PORTAL WEB','Validación de Contenidos para el Nuevo Portal Web - Subdirección de la Bicicleta y el Peatón\r\n\r\nSe requiere la corrección de los enlaces de acceso directo para las Encuestas de Movilidad que actualmente no direccionan a ninguna página. Es necesario definir y consolidar una única ubicación para el organigrama en el sitio web, ya que la información se encuentra duplicada en al menos tres espacios diferentes, y proceder con la actualización de los datos de la Subdirectora actual. Debe revisarse la configuración de los enlaces en la sección de datos abiertos para las opciones \"SIMUR y GOV.co\", ya que ambas redirigen a la misma página destino.\r\n\r\nPara el módulo de Registro Bici, se debe reconfigurar el ícono correspondiente para que redirija correctamente a la página web oficial con la URL https://registrobicibogota.movilidadbogota.gov.co/#!/, en lugar de simplemente desplazarse al HOME o duplicar la misma página.\r\n\r\nRespecto a la Cicloinfraestructura, se necesita corregir el enlace de la extensión de la red de ciclorruta ubicado en la URL https://observatorio.movilidadbogota.gov.co/tableros/ciclista, el cual actualmente no direcciona a la página correcta y aparece como inexistente. Adicionalmente, en la página de prueba no se encuentra disponible la información que debería estar alojada en la URL https://www.movilidadbogota.gov.co/web/temasdeimpacto/estudios_tecnicos_de_ciclorrutas.\r\n\r\nEn el apartado de Políticas Públicas de la Bicicleta y el Peatón, si bien los ítems para cada política están presentes en el enlace de validación, al hacer clic sobre ellos no se visualiza ningún contenido, por lo que se debe habilitar la correcta visualización de esta información.\r\n\r\nPara la sección de Cicloparqueaderos, es necesario solucionar el problema por el cual la búsqueda de este término no arroja ningún resultado. Deben repararse los enlaces rotos localizados en las páginas 2 y 4 de \"Trámites y Servicios\" relacionados con \"sellos de calidad (cicloparqueaderos)\", los cuales actualmente muestran un error de página no encontrada. Se debe eliminar toda referencia pública al \"descuento tributario por habilitación de cicloparqueaderos (Plan Marshall)\", ya que esta medida ya no se encuentra vigente, para evitar generar confusión entre los usuarios. Se requiere actualizar toda la información del micrositio web de Cicloparqueaderos, el cual contiene datos desactualizados sobre estrategias, participación, ubicaciones y certificaciones. Asimismo, el botón de \"Habilitación de cicloparqueaderos\" debe ser reconfigurado o reetiquetado, ya que actualmente enlaza a información sobre un descuento tributario que ya no aplica.\r\n\r\nEn la Instancia de Coordinación, se debe completar la información faltante correspondiente a los meses de junio y septiembre. Finalmente, se solicita avanzar en la consolidación de un \"micrositio\" unificado que agrupe todos los temas relacionados con la bicicleta y el peatón, para lo cual se continuará con la recolección de la información necesaria.','[]',NULL,'CRITICA','PENDIENTE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-14 03:39:04','2025-11-14 03:39:04',NULL,0,NULL,NULL,NULL,NULL,0),(64,NULL,'SUP-IN-C-251113-011',2,32,3,NULL,'Respuesta solicitud Instrucciones para la Validación de Contenidos en el Nuevo Portal Web.','Validación de Contenidos para el Nuevo Portal Web - Subsecretaría de Gestión Jurídica\r\n\r\nDe acuerdo con lo solicitado mediante el memorando 202511000215863, se procedió a realizar la validación de la información en el nuevo portal web de la SDM, la cual es responsabilidad de la Subsecretaría de Gestión Jurídica. Durante este proceso de revisión, se identificaron diversas inconsistencias que han sido detalladas en la matriz adjunta. Es de resaltar que, de manera específica en el módulo del Comité Intersectorial de Coordinación Jurídica del Sector Administrativo de Movilidad, se detectó la presencia de varios documentos que en realidad corresponden a otros comités, lo que requiere una pronta corrección para garantizar la integridad y precisión de la información publicada.','[]',NULL,'CRITICA','PENDIENTE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-14 03:40:51','2025-11-14 03:40:51',NULL,0,NULL,NULL,NULL,NULL,0),(65,NULL,'WEB-ED-M-251113-006',1,5,3,NULL,'Fwd: LINK DE FACILIDADES DE PAGO','Actualización del Micrositio de Trámites y Servicios - Facilidades de Pago\r\n\r\nSe solicita realizar la actualización de la sección de Facilidades de Pago para deudores de obligaciones no tributarias dentro del micrositio de trámites y servicios. Es necesario eliminar las opciones actuales que consisten en \"Agenda Cita\", \"Presencial Sede Calle 13\" y \"Presencial Sede Paloquemao\". Posteriormente, se debe incorporar un nuevo botón con la etiqueta \"Solicitalo\", el cual debe estar configurado para redirigir a los usuarios al enlace https://movilidad.ucontactcloud.com/WebChat/SdmAcuerdosPago/.','[]',NULL,'MEDIA','PENDIENTE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-14 03:41:54','2025-11-14 03:41:54',NULL,0,NULL,NULL,NULL,NULL,0),(66,NULL,'WEB-ED-M-251113-007',1,5,3,NULL,'Modificación acceso a Facilidades de Pago - 31 de octubre','Actualización Urgente para Pruebas de Facilidades de Pago\r\n\r\nSe requiere una intervención prioritaria en el micrositio de trámites y servicios para la sección de Facilidades de Pago dirigida a deudores de obligaciones no tributarias. Es necesario eliminar de forma inmediata las opciones actuales que incluyen \"Agenda Cita\", \"Presencial Sede Calle 13\" y \"Presencial Sede Paloquemao\". De manera paralela, se debe implementar un nuevo botón con la etiqueta \"Solicitalo\" que redirija a los usuarios al enlace https://movilidad.ucontactcloud.com/WebChat/SdmAcuerdosPago/. Esta actualización es crítica ya que el área solicitante realizará pruebas de validación del sitio durante el día siguiente, con el objetivo de tener todo operativo para el lanzamiento programado para el martes 4 de noviembre en horas de la mañana.','[]',NULL,'MEDIA','PENDIENTE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-14 03:44:21','2025-11-14 03:44:21',NULL,0,NULL,NULL,NULL,NULL,0),(67,NULL,'SUP-IN-L-251113-001',2,32,3,NULL,'Asignación Radicado # 202532200220963 SEMA Respuesta al memorando 202511000215863','Validación de Contenidos para el Nuevo Portal Web - Subdirección Técnica de Semaforización\r\n\r\nEn atención al memorando 202511000215863, la Subdirección Técnica de Semaforización informa que se ha realizado la revisión y validación correspondiente de la información y recursos a su cargo, encontrándose todos completos y acordes. Se verificó que los datos y contenidos son correctos y están actualizados, confirmando que todos los enlaces y documentos funcionan correctamente. Finalmente, se validó que la información se encuentra ubicada en la sección correspondiente del portal web y cuenta con el contexto adecuado para su comprensión por parte de los usuarios.','[]',NULL,'BAJA','PENDIENTE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-14 03:46:47','2025-11-14 03:46:47',NULL,0,NULL,NULL,NULL,NULL,0),(68,NULL,'SUP-IN-C-251113-012',2,32,3,NULL,'Asignación Radicado # 202554000221143 DGC Respuesta al memorando 202511000215863','Validación de Contenidos para el Nuevo Portal Web - Dirección de Gestión de Cobro\r\n\r\nSe propone reubicar el acceso directo \"ABC pago de comparendos\" desde la parte inferior del home hacia una posición más prominente como banner flotante en la sección inicial, mejorando su visibilidad para la ciudadanía. Adicionalmente, se sugiere modificar la denominación por \"Consulta aquí comparendos, multas y acuerdos de pago\" incluyendo información sobre puntos de pago y preguntas frecuentes. Para la sección de normatividad, notificaciones, citación y comunicación de actos administrativos, se recomienda suprimir el banner \"Excepciones\" y migrar su contenido al banner de derechos de petición. El banner de medidas cautelares debe reconfigurarse como elemento informativo sobre medidas vigentes y desembargos, complementado con un banner adicional que invite al pago para usuarios con cartera vigente. Se propone cambiar la denominación del banner \"notificación del proceso de cobro\" por \"notificación de la orden de seguir adelante con la ejecución\". Es fundamental solucionar el problema por el cual todos los banners de esta pestaña no muestran contenido al hacer clic, e implementar un buscador que permita a los ciudadanos acceder a todos los documentos relacionados con sus notificaciones. En la sección de atención y servicios al ciudadano, específicamente en preguntas frecuentes sobre acuerdos de pago, se debe completar la información del segundo banner de \"control y transparencia, multas de tránsito\" agregando la referencia al \"Artículo 814\" que actualmente no se visualiza. Finalmente, se solicita coordinar una mesa de trabajo con el equipo de la Dirección de Gestión de Cobro para explicar en detalle las novedades identificadas en la nueva página web.','[]',NULL,'CRITICA','PENDIENTE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-14 03:48:58','2025-11-14 03:48:58',NULL,0,NULL,NULL,NULL,NULL,0),(69,NULL,'SUP-MI-M-251113-005',1,31,3,NULL,'Puesta en producción Página Web - Oct 30 RFC 2341','Implementación del Nuevo Portal Web - Control de Cambios 2341\r\n\r\nSe informa que el Control de Cambios 2341 para la puesta en producción del nuevo portal web de la SDM fue presentado y aprobado por el Comité de Cambios, programándose su ejecución para hoy a partir de las 9:00 p.m. y 11:00 p.m. Una vez finalizada la implementación, se requiere la aprobación funcional por parte de la Oficina de Comunicaciones a través de William Sotáquira, junto con la validación de los accesos a los aplicativos SIMUR por Jimy Sánchez y Kactus por Johon Rozo desde la OTIC. Se procederá a crear un grupo de seguimiento y una sesión Meet para monitorear el avance del proceso de implementación durante la actividad programada.','[]',NULL,'MEDIA','PENDIENTE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-14 03:53:28','2025-11-14 03:53:28',NULL,0,NULL,NULL,NULL,NULL,0),(70,NULL,'COM-RE-C-251113-002',1,8,3,NULL,'Planificación de los cambios en los Sistemas de Gestión de Calidad y Antisoborno | Plan de Trabajo Validación y Puesta en Marcha del Nuevo Portal Web','Plan de Trabajo Validación y Puesta en Marcha del Nuevo Portal Web\r\n\r\nSe establece el plan para la validación funcional y técnica del nuevo portal web de la SDM, coordinando las actividades de verificación entre todas las dependencias involucradas. Esto incluye la revisión de contenidos, funcionalidad de enlaces, integridad de la información y correcta ubicación de los recursos según las competencias de cada subdirección y dirección.\r\n\r\nSe programa la ejecución del Control de Cambios 2341 para la puesta en producción, el cual fue aprobado por el Comité de Cambios y está previsto realizarse en el horario establecido de 9:00 p.m. a 11:00 p.m. Posterior a la implementación, se requiere la aprobación funcional formal por parte de la Oficina de Comunicaciones y la validación técnica de los accesos a los aplicativos SIMUR y Kactus por los responsables designados en la OTIC.\r\n\r\nSe conforma un grupo de seguimiento para monitorear el avance durante el proceso de implementación, utilizando sesiones Meet para garantizar la comunicación en tiempo real. Finalmente, se recopilan todas las observaciones y solicitudes de ajuste identificadas durante la fase de validación por las diferentes dependencias para su priorización y resolución en el entorno de producción.','[]',NULL,'CRITICA','PENDIENTE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-14 03:55:48','2025-11-14 03:55:48',NULL,0,NULL,NULL,NULL,NULL,0),(71,NULL,'COM-RE-U-251113-001',1,8,3,NULL,'La Acción Migración del Portal web de la Secretaría Distrital de Movilidad.-1 del Plan de Acción Migración del Portal web de la Secretaría Distrital de Movilidad. requiere de su atención','Reunión de alineación de criterios de aceptación para la Migración del Portal web de la Secretaría Distrital de Movilidad.','[]',NULL,'MEDIA','PENDIENTE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-14 04:02:56','2025-11-14 04:02:56',NULL,0,NULL,NULL,NULL,NULL,0);
/*!40000 ALTER TABLE `service_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `service_subservices`
--

DROP TABLE IF EXISTS `service_subservices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `service_subservices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `service_family_id` bigint(20) unsigned NOT NULL,
  `service_id` bigint(20) unsigned NOT NULL,
  `sub_service_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `svc_subsvc_unique` (`service_family_id`,`service_id`,`sub_service_id`),
  KEY `service_subservices_service_id_foreign` (`service_id`),
  KEY `service_subservices_sub_service_id_foreign` (`sub_service_id`),
  CONSTRAINT `service_subservices_service_family_id_foreign` FOREIGN KEY (`service_family_id`) REFERENCES `service_families` (`id`) ON DELETE CASCADE,
  CONSTRAINT `service_subservices_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE,
  CONSTRAINT `service_subservices_sub_service_id_foreign` FOREIGN KEY (`sub_service_id`) REFERENCES `sub_services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_subservices`
--

LOCK TABLES `service_subservices` WRITE;
/*!40000 ALTER TABLE `service_subservices` DISABLE KEYS */;
INSERT INTO `service_subservices` VALUES (1,1,1,1,'Gestión de Contenidos Web - 1. Gestión de Contenidos Web y Recursos Digitales - Error o Problema con Contenido Publicado','Combinación automática: Gestión de Contenidos Web, 1. Gestión de Contenidos Web y Recursos Digitales, Error o Problema con Contenido Publicado',1,'2025-11-05 19:56:52','2025-11-05 19:56:52'),(2,2,2,6,'Cumplimiento Normativo - 2. Cumplimiento de Transparencia y Acceso a la Información - Actualización de Sección de Transparencia','Combinación automática: Cumplimiento Normativo, 2. Cumplimiento de Transparencia y Acceso a la Información, Actualización de Sección de Transparencia',1,'2025-11-05 20:37:55','2025-11-05 20:37:55'),(3,8,9,32,'SLA Auto - Problema o Incidencia Técnica durante el Desarrollo del Proyecto','Creado automáticamente para SLA',1,'2025-11-05 23:58:24','2025-11-05 23:58:24'),(4,1,1,2,'SLA Auto - Reorganización de Estructura Web','Creado automáticamente para SLA',1,'2025-11-06 00:04:43','2025-11-06 00:04:43'),(5,6,6,34,'Publicación de Información - 6. Publicación de Información en Portales Web - Publicacion de Banner en el Home Principal','Combinación automática: Publicación de Información, 6. Publicación de Información en Portales Web, Publicacion de Banner en el Home Principal',1,'2025-11-06 01:54:56','2025-11-06 01:54:56'),(6,6,6,22,'Publicación de Información - 6. Publicación de Información en Portales Web - Publicación de Documento','Combinación automática: Publicación de Información, 6. Publicación de Información en Portales Web, Publicación de Documento',1,'2025-11-06 03:05:20','2025-11-06 03:05:20'),(7,6,6,23,'Publicación de Información - 6. Publicación de Información en Portales Web - Publicación de Noticia, PMT o Artículo','Combinación automática: Publicación de Información, 6. Publicación de Información en Portales Web, Publicación de Noticia, PMT o Artículo',1,'2025-11-06 20:16:26','2025-11-06 20:16:26'),(8,4,4,35,'Administración de Sitios Web - 4. Administración y Optimización de Sitios Web - Acompañamiento actividades desarrollo externo','Combinación automática: Administración de Sitios Web, 4. Administración y Optimización de Sitios Web, Acompañamiento actividades desarrollo externo',1,'2025-11-14 02:56:53','2025-11-14 02:56:53');
/*!40000 ALTER TABLE `service_subservices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `services`
--

DROP TABLE IF EXISTS `services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `services` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `service_family_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `services_service_family_id_code_unique` (`service_family_id`,`code`),
  CONSTRAINT `services_service_family_id_foreign` FOREIGN KEY (`service_family_id`) REFERENCES `service_families` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `services`
--

LOCK TABLES `services` WRITE;
/*!40000 ALTER TABLE `services` DISABLE KEYS */;
INSERT INTO `services` VALUES (1,1,'1. Gestión de Contenidos Web y Recursos Digitales','GEST_CONT_WEB','Apoyar en la edición, diseño y organización de contenidos web y otros recursos relacionados.',1,1,'2025-11-05 19:14:21','2025-11-05 19:14:21',NULL),(2,2,'2. Cumplimiento de Transparencia y Acceso a la Información','CUMPL_TRANS','Apoyar e implementar acciones que faciliten el cumplimento de los lineamientos del Modelo Integrado de Planeación y Gestión, la Ley 1712 de 2014 y el Decreto',1,2,'2025-11-05 19:14:21','2025-11-05 19:14:21',NULL),(3,3,'3. Seguimiento de Solicitudes de Publicación','SEG_SOL_PUB','Realizar el seguimiento de solicitudes de publicación en la página web, intranet y otros portales web de la secretaría.',1,3,'2025-11-05 19:14:21','2025-11-05 19:14:21',NULL),(4,4,'4. Administración y Optimización de Sitios Web','ADMIN_OPT_WEB','Apoyar en la administración y optimización de estilo, calidad y actualización de datos de los sitios web de la SDM.',1,4,'2025-11-05 19:14:21','2025-11-05 19:14:21',NULL),(5,5,'5. Validación y Monitoreo de Contenidos Web','VAL_MON_CONT','Validar y monitorear contenidos publicados en los portales Web de la SDM.',1,5,'2025-11-05 19:14:21','2025-11-05 19:14:21',NULL),(6,6,'6. Publicación de Información en Portales Web','PUB_INFO_WEB','Apoyar la publicación de información en la web, intranet y sitios web de la SDM.',1,6,'2025-11-05 19:14:21','2025-11-05 19:14:21',NULL),(7,7,'7. Gestión de Disponibilidad y Despliegue del Contratista','GEST_DISP_CON','Contar con disponibilidad para prestar sus servicios, de acuerdo con su especialidad, en los espacios acordados y requeridos por el supervisor según la necesidad del servicio.',1,7,'2025-11-05 19:14:21','2025-11-05 19:14:21',NULL),(8,8,'8.1. Otras Actividades Asignadas','OTRAS_ACT','Las demás que le sean asignadas por el supervisor en relación con el objeto del contrato.',1,8,'2025-11-05 19:14:21','2025-11-05 19:14:21',NULL),(9,8,'8.2. Desarrollo de Nuevos Portales y Proyectos Web Especiales','DES_PORT_ESP','Las demás que le sean asignadas por el supervisor en relación con el objeto del contrato.',1,9,'2025-11-05 19:14:21','2025-11-05 19:14:21',NULL);
/*!40000 ALTER TABLE `services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('P6L8Vt3wbUKXvKnRq9L7CwhSyVK3dYQJrUxuYh7S',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiNWhEMnBFd0F0ZEQ0d1d3dmM5QjRLMmdZelQwT0pORmFRNUl0OW5UcyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9kYXNoYm9hcmQiO3M6NToicm91dGUiO3M6OToiZGFzaGJvYXJkIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTt9',1763182698),('xYxnV0auUautcOLQ219z9QoOCwiDmpLkWPekB98b',3,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0','YTo1OntzOjY6Il90b2tlbiI7czo0MDoiWmtYRkN3ZXcydlRsam96Y2hGTkxxNVo3RDNLdVRpd0xFRzVxamhTUyI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjQyOiJodHRwOi8vc2FwcC5sb2NhbDo4MDAwL3NlcnZpY2UtcmVxdWVzdHMvNzEiO3M6NToicm91dGUiO3M6MjE6InNlcnZpY2UtcmVxdWVzdHMuc2hvdyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjM7fQ==',1763178324);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sla_breach_logs`
--

DROP TABLE IF EXISTS `sla_breach_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sla_breach_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `service_request_id` bigint(20) unsigned NOT NULL,
  `breach_type` enum('ACEPTACION','RESPUESTA','RESOLUCION') NOT NULL,
  `breach_minutes` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sla_breach_logs_service_request_id_foreign` (`service_request_id`),
  CONSTRAINT `sla_breach_logs_service_request_id_foreign` FOREIGN KEY (`service_request_id`) REFERENCES `service_requests` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sla_breach_logs`
--

LOCK TABLES `sla_breach_logs` WRITE;
/*!40000 ALTER TABLE `sla_breach_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `sla_breach_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sub_services`
--

DROP TABLE IF EXISTS `sub_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sub_services` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `service_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `cost` decimal(10,2) DEFAULT NULL,
  `order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sub_services_service_id_code_unique` (`service_id`,`code`),
  CONSTRAINT `sub_services_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sub_services`
--

LOCK TABLES `sub_services` WRITE;
/*!40000 ALTER TABLE `sub_services` DISABLE KEYS */;
INSERT INTO `sub_services` VALUES (1,1,'Error o Problema con Contenido Publicado','ERROR_CONTENIDO','Reporte de errores o problemas con contenido ya publicado en los portales web',1,0.00,1,'2025-11-05 19:14:55','2025-11-05 19:14:55',NULL),(2,1,'Reorganización de Estructura Web','REORG_ESTRUCTURA','Reorganización y reestructuración de la arquitectura de información web',1,0.00,2,'2025-11-05 19:14:55','2025-11-05 19:14:55',NULL),(3,1,'Solicitud de Desarrollo de Micrositio Web','MICROSITIO_WEB','Solicitud para creación y desarrollo de micrositios web especializados',1,0.00,3,'2025-11-05 19:14:55','2025-11-05 19:14:55',NULL),(4,1,'Solicitud de Diseño Gráfico','DISENO_GRAFICO','Solicitud de servicios de diseño gráfico para contenidos web',1,0.00,4,'2025-11-05 19:14:55','2025-11-05 19:14:55',NULL),(5,1,'Solicitud de Edición o Ajuste de Contenido','EDICION_CONTENIDO','Solicitud para edición, ajuste o modificación de contenidos web existentes',1,0.00,5,'2025-11-05 19:14:55','2025-11-05 19:14:55',NULL),(6,2,'Actualización de Sección de Transparencia','ACT_TRANSPARENCIA','Actualización de contenidos en las secciones de transparencia y acceso a la información',1,0.00,1,'2025-11-05 19:14:55','2025-11-05 19:14:55',NULL),(7,2,'Asesoría en MIPG y Lineamientos','ASESORIA_MIPG','Asesoría en Modelo Integrado de Planeación y Gestión y otros lineamientos normativos',1,0.00,2,'2025-11-05 19:14:55','2025-11-05 19:14:55',NULL),(8,2,'Generación de Reportes de MIPG','REPORTES_MIPG','Generación de reportes y documentación requerida por el MIPG',1,0.00,3,'2025-11-05 19:14:55','2025-11-05 19:14:55',NULL),(9,2,'Publicación por Ley de Transparencia','PUB_TRANSPARENCIA','Publicación de contenidos requeridos por la Ley de Transparencia y Acceso a la Información',1,0.00,4,'2025-11-05 19:14:55','2025-11-05 19:14:55',NULL),(10,3,'Consulta de Estado de Solicitud','CONSULTA_ESTADO','Consulta sobre el estado actual de una solicitud de publicación',1,0.00,1,'2025-11-05 19:14:55','2025-11-05 19:14:55',NULL),(11,3,'Reporte de Demora en Publicación','DEMORA_PUBLICACION','Reporte de demoras o retrasos en procesos de publicación',1,0.00,2,'2025-11-05 19:14:55','2025-11-05 19:14:55',NULL),(12,3,'Solicitud de Publicación','SOL_PUBLICACION','Solicitud formal para publicación de contenidos en portales web',1,0.00,3,'2025-11-05 19:14:55','2025-11-05 19:14:55',NULL),(13,4,'Actualización Masiva de Datos','ACT_MASIVA_DATOS','Actualización masiva de datos y contenidos en sitios web',1,0.00,1,'2025-11-05 19:14:55','2025-11-05 19:14:55',NULL),(14,4,'Optimización de Estilos y Plantillas','OPT_ESTILOS','Optimización y mejora de estilos, plantillas y temas de sitios web',1,0.00,2,'2025-11-05 19:14:55','2025-11-05 19:14:55',NULL),(15,4,'Reporte de Inconsistencia en Calidad','INCONSISTENCIA_CALIDAD','Reporte de inconsistencias o problemas de calidad en sitios web',1,0.00,3,'2025-11-05 19:14:55','2025-11-05 19:14:55',NULL),(16,5,'Reporte de Enlace Roto o Contenido Obsoleto','ENLACE_ROTO','Reporte de enlaces rotos o contenidos obsoletos en portales web',1,0.00,1,'2025-11-05 19:14:55','2025-11-05 19:14:55',NULL),(17,5,'Reporte de Error en Contenido Publicado','ERROR_PUBLICADO','Reporte de errores específicos en contenidos ya publicados',1,0.00,2,'2025-11-05 19:14:55','2025-11-05 19:14:55',NULL),(18,5,'Reportes de Analítica Web','ANALITICA_WEB','Generación de reportes de analítica web y métricas de desempeño',1,0.00,3,'2025-11-05 19:14:55','2025-11-05 19:14:55',NULL),(19,5,'Solicitud de Eliminación o Retiro de Contenido','ELIMINACION_CONTENIDO','Solicitud para eliminación o retiro de contenidos específicos',1,0.00,4,'2025-11-05 19:14:55','2025-11-05 19:14:55',NULL),(20,5,'Validación Previa a Publicación','VALIDACION_PREVIA','Validación y revisión de contenidos antes de su publicación',1,0.00,5,'2025-11-05 19:14:55','2025-11-05 19:14:55',NULL),(21,6,'Falla en Proceso de Publicación','FALLA_PUBLICACION','Reporte de fallas o errores durante el proceso de publicación',1,0.00,1,'2025-11-05 19:14:55','2025-11-05 19:14:55',NULL),(22,6,'Publicación de Documento','PUB_DOCUMENTO','Publicación de documentos oficiales y archivos en portales web',1,0.00,2,'2025-11-05 19:14:55','2025-11-05 19:14:55',NULL),(23,6,'Publicación de Noticia, PMT o Artículo','PUB_NOTICIA','Publicación de noticias, artículos o contenidos del PMT',1,0.00,3,'2025-11-05 19:14:55','2025-11-05 19:14:55',NULL),(24,7,'Asignación de Tarea Ad-Hoc','TAREA_ADHOC','Asignación de tareas específicas y ad-hoc al contratista',1,0.00,1,'2025-11-05 19:14:55','2025-11-05 19:14:55',NULL),(25,7,'Reporte de Indisponibilidad','INDISPONIBILIDAD','Reporte de indisponibilidad del contratista o servicios',1,0.00,2,'2025-11-05 19:14:55','2025-11-05 19:14:55',NULL),(26,7,'Solicitud de Despliegue en Locación','DESPLIEGUE_LOCACION','Solicitud de despliegue del contratista en locación específica',1,0.00,3,'2025-11-05 19:14:55','2025-11-05 19:14:55',NULL),(27,8,'Asignación de Tarea No Especificada','TAREA_NO_ESPEC','Asignación de tareas no especificadas en otros subservicios',1,0.00,1,'2025-11-05 19:14:55','2025-11-05 19:14:55',NULL),(28,8,'Solicitud de Apoyo General','APOYO_GENERAL','Solicitud de apoyo general no categorizado en otros subservicios',1,0.00,2,'2025-11-05 19:14:55','2025-11-05 19:14:55',NULL),(29,9,'Desarrollo, Configuración e Implementación Técnica','DESARROLLO_TECNICO','Desarrollo, configuración e implementación técnica de soluciones web',1,0.00,1,'2025-11-05 19:14:55','2025-11-05 19:14:55',NULL),(30,9,'Diseño de Arquitectura de Información y Experiencia de Usuario (UX/UI)','DISENO_UX_UI','Diseño de arquitectura de información y experiencia de usuario',1,0.00,2,'2025-11-05 19:14:55','2025-11-05 19:14:55',NULL),(31,9,'Plan de Migración y Carga Masiva de Contenido Inicial','MIGRACION_CONTENIDO','Plan de migración y carga masiva de contenido inicial para nuevos portales',1,0.00,3,'2025-11-05 19:14:55','2025-11-05 19:14:55',NULL),(32,9,'Problema o Incidencia Técnica durante el Desarrollo del Proyecto','INCIDENCIA_DESARROLLO','Reporte de problemas o incidencias técnicas durante el desarrollo de proyectos',1,0.00,4,'2025-11-05 19:14:55','2025-11-05 19:14:55',NULL),(33,9,'Solicitud de Creación de un Nuevo Portal Web','NUEVO_PORTAL','Solicitud para creación y desarrollo de un nuevo portal web',1,0.00,5,'2025-11-05 19:14:55','2025-11-05 19:14:55',NULL),(34,6,'Publicacion de Banner en el Home Principal','PUBDEBANEN','Publicación de Banner en el home del portal web de la SDM',1,NULL,1,'2025-11-06 01:53:43','2025-11-06 01:53:43',NULL),(35,4,'Acompañamiento actividades desarrollo externo','ACO','Todo tarea que sea solicitada con el objetivo de validar el cumplimiento normativo para desarrollos externos',1,NULL,0,'2025-11-14 02:55:29','2025-11-14 02:55:29',NULL);
/*!40000 ALTER TABLE `sub_services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Administrador','william.sotaquira@gmail.com',NULL,'$2y$12$kEbTjh4XhrLaBQHE11lFHuSHCh5drBV4T8FOn/2PUbePshKhhtBGy',NULL,'2025-11-05 18:43:01','2025-11-05 18:43:01'),(3,'William Sotaquirá','wsotaquira@movilidadbogota.gov.co',NULL,'$2y$12$9epH4XmiGIkzxqAXMgVVmemGIWB1lGikK5uVhdYeJpB/1UoANLZZu',NULL,'2025-11-06 19:53:09','2025-11-06 19:53:09'),(5,'Jersson Hernandez','jhernandez@movilidadbogota.gov.co',NULL,'$2y$12$JGMA47doC3yxmB03TKihU.IwmUsXbJ00XX4eGXAVifQv/TlnPVmQy',NULL,'2025-11-06 20:21:41','2025-11-06 20:21:41');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-11-15  0:00:49
