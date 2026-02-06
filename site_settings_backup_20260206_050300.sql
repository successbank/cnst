/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.15-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: project1_db
-- ------------------------------------------------------
-- Server version	10.11.15-MariaDB-ubu2204

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
-- Table structure for table `site_settings`
--

DROP TABLE IF EXISTS `site_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `site_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` varchar(50) DEFAULT 'text',
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_settings`
--

LOCK TABLES `site_settings` WRITE;
/*!40000 ALTER TABLE `site_settings` DISABLE KEYS */;
INSERT INTO `site_settings` VALUES
(1,'backup_auto_enabled','1','text','자동 백업 활성화','2026-01-29 07:26:25','2026-01-29 09:14:55'),
(2,'backup_schedule_time','01:00','text','자동 백업 시간','2026-01-29 07:26:25','2026-01-29 07:26:25'),
(3,'backup_retention_days','30','text','백업 보관 일수','2026-01-29 07:26:25','2026-01-29 07:26:25'),
(4,'backup_max_files','30','text','최대 백업 파일 수','2026-01-29 07:26:25','2026-01-29 09:16:39'),
(5,'backup_email_auto_enabled','1','text','자동 백업 이메일 알림','2026-01-29 07:48:36','2026-01-29 09:15:27'),
(6,'backup_email_manual_enabled','1','text','수동 백업 이메일 알림','2026-01-29 07:48:36','2026-01-29 08:51:16'),
(7,'backup_email_recipients','ebooknara@naver.com','text','알림 수신 이메일 (쉼표 구분)','2026-01-29 07:48:36','2026-01-29 08:51:16'),
(8,'backup_email_on_success','1','text','성공 시 알림','2026-01-29 07:48:36','2026-01-29 07:48:36'),
(9,'backup_email_on_failure','1','text','실패 시 알림','2026-01-29 07:48:36','2026-01-29 07:48:36'),
(10,'log_retention_days','60','log',NULL,'2026-01-29 10:00:58','2026-01-29 10:19:52'),
(11,'log_auto_cleanup_enabled','0','log',NULL,'2026-01-29 10:00:58','2026-01-29 10:19:52'),
(12,'log_cleanup_schedule_time','02:00','log',NULL,'2026-01-29 10:00:58','2026-01-29 10:19:52'),
(13,'log_cleanup_targets','[\"member_login_logs\",\"calculation_logs\",\"email_logs\"]','log',NULL,'2026-01-29 10:00:58','2026-01-29 10:19:52');
/*!40000 ALTER TABLE `site_settings` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-02-06  5:03:00
