/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.13-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: project5_db
-- ------------------------------------------------------
-- Server version	10.11.13-MariaDB-ubu2204

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
-- Table structure for table `board_comments`
--

DROP TABLE IF EXISTS `board_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `board_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `board_type` varchar(50) NOT NULL,
  `board_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `writer` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `board_comments`
--

LOCK TABLES `board_comments` WRITE;
/*!40000 ALTER TABLE `board_comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `board_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `board_consignment`
--

DROP TABLE IF EXISTS `board_consignment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `board_consignment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `writer` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `company_name` varchar(100) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `attachment` text DEFAULT NULL,
  `view_count` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `stock_quantity` varchar(100) DEFAULT NULL,
  `price_info` varchar(255) DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `contact_phone` varchar(50) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `member_id` int(11) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `status` enum('active','sold','inactive') DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `board_consignment`
--

LOCK TABLES `board_consignment` WRITE;
/*!40000 ALTER TABLE `board_consignment` DISABLE KEYS */;
INSERT INTO `board_consignment` VALUES
(1,'test','asdfsaf','테스트333','1212','성공은행','강널말뚝','[\"1756184277_Panamera Turbo S E-Hybrid (3).jpeg\",\"1756184277_Panamera Turbo S E-Hybrid (2).jpeg\",\"1756184277_Panamera Turbo S E-Hybrid (8).jpeg\",\"1756184277_Panamera Turbo S E-Hybrid (4).jpeg\",\"1756184277_Panamera Turbo S E-Hybrid (6).jpeg\"]',0,'2025-08-26 04:57:57','2025-08-26 04:57:57','100','333','박현','','powernews74@gmail.com','ddddd',NULL,0,'active');
/*!40000 ALTER TABLE `board_consignment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `board_news`
--

DROP TABLE IF EXISTS `board_news`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `board_news` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `writer` varchar(100) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `source` varchar(200) DEFAULT NULL,
  `source_url` varchar(500) DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `view_count` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `board_news`
--

LOCK TABLES `board_news` WRITE;
/*!40000 ALTER TABLE `board_news` DISABLE KEYS */;
INSERT INTO `board_news` VALUES
(1,'철강 가격 동향 및 전망','최근 국제 철강 가격이 상승세를 보이고 있습니다.\n\n중국의 생산 감축과 원자재 가격 상승이 주요 원인으로 분석됩니다.','관리자','1234','한국철강신문',NULL,NULL,4,'2025-08-05 01:52:35','2025-08-10 06:07:11'),
(2,'친환경 철강 생산 기술 개발','포스코가 수소환원제철 기술 개발에 성공했다고 발표했습니다.\n\n이를 통해 탄소 배출을 획기적으로 줄일 수 있을 것으로 기대됩니다.','관리자','1234','철강업계뉴스',NULL,NULL,0,'2025-08-05 01:52:35','2025-08-05 01:52:35'),
(3,'testsddfd','sadfas','admin','','','',NULL,1,'2025-08-26 04:05:01','2025-08-26 04:05:10');
/*!40000 ALTER TABLE `board_news` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `board_notice`
--

DROP TABLE IF EXISTS `board_notice`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `board_notice` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `writer` varchar(100) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `view_count` int(11) DEFAULT 0,
  `is_important` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `board_notice`
--

LOCK TABLES `board_notice` WRITE;
/*!40000 ALTER TABLE `board_notice` DISABLE KEYS */;
INSERT INTO `board_notice` VALUES
(1,'test','sdf','admin','',NULL,1,1,'2025-08-26 04:02:24','2025-08-26 04:02:44');
/*!40000 ALTER TABLE `board_notice` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `board_quote`
--

DROP TABLE IF EXISTS `board_quote`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `board_quote` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `writer` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `company` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `attachment` text DEFAULT NULL,
  `view_count` int(11) DEFAULT 0,
  `is_answered` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `display_order` int(11) DEFAULT 0,
  `admin_reply` text DEFAULT NULL,
  `replied_at` datetime DEFAULT NULL,
  `member_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `board_quote`
--

LOCK TABLES `board_quote` WRITE;
/*!40000 ALTER TABLE `board_quote` DISABLE KEYS */;
INSERT INTO `board_quote` VALUES
(1,'sdf','dd','ssdf','1111','successbank','dev@successbank.co.kr','010-4147-9977','',0,0,'2025-08-26 04:18:12','2025-08-26 04:18:12',0,NULL,NULL,NULL),
(2,'ddddfdf','sdf','테스트333','1111','successbank','powernews74@gmail.com','010-4147-9977','1756182515_KakaoTalk_20250804_221050254.jpg',0,0,'2025-08-26 04:28:35','2025-08-26 04:28:35',0,NULL,NULL,NULL),
(3,'233424','fsdgdsfg','dsf','121212','successbank','dev@successbank.co.kr','010-4147-9977','1756182547_Panamera Turbo S E-Hybrid (3).jpeg',0,0,'2025-08-26 04:29:07','2025-08-26 04:40:38',0,NULL,NULL,NULL),
(4,'hdf','fsfsd','ssdf','1111','successbank22','dev@successbank.co.kr','010-4147-9977','',0,0,'2025-08-26 04:41:49','2025-08-26 04:41:49',0,NULL,NULL,NULL),
(5,'test','sadfsaf','dddfsfd','1212','sdfaf','powernews74@gmail.com','010-4147-9977','[\"1756184009_Panamera Turbo S E-Hybrid (3).jpeg\",\"1756184009_Panamera Turbo S E-Hybrid (2).jpeg\",\"1756184009_Panamera Turbo S E-Hybrid (8).jpeg\",\"1756184009_Panamera Turbo S E-Hybrid (4).jpeg\",\"1756184009_Panamera Turbo S E-Hybrid (6).jpeg\"]',1,0,'2025-08-26 04:53:29','2025-08-26 04:53:36',0,NULL,NULL,NULL);
/*!40000 ALTER TABLE `board_quote` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `members`
--

DROP TABLE IF EXISTS `members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `company` varchar(100) DEFAULT NULL,
  `department` varchar(50) DEFAULT NULL,
  `position` varchar(50) DEFAULT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `members`
--

LOCK TABLES `members` WRITE;
/*!40000 ALTER TABLE `members` DISABLE KEYS */;
/*!40000 ALTER TABLE `members` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_categories`
--

DROP TABLE IF EXISTS `product_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_code` varchar(50) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `click_count` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `category_code` (`category_code`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_categories`
--

LOCK TABLES `product_categories` WRITE;
/*!40000 ALTER TABLE `product_categories` DISABLE KEYS */;
INSERT INTO `product_categories` VALUES
(1,'rebar','철근',1,1,127,'2025-08-05 02:38:24'),
(2,'h-beam','H형강',2,1,122,'2025-08-05 02:38:24'),
(3,'steel-plate','철강(강판)',3,1,29,'2025-08-05 02:38:24'),
(4,'metal-lath','메탈라스',4,1,6,'2025-08-05 02:38:24'),
(5,'light-h-beam','경량H형강',5,1,62,'2025-08-05 02:38:24'),
(6,'i-beam','I형강',6,1,1,'2025-08-05 02:38:24'),
(7,'angle','ㄱ형강(앵글)',7,1,74,'2025-08-05 02:38:24'),
(8,'channel','ㄷ형강(찬넬)',8,1,3,'2025-08-05 02:38:24'),
(9,'round-bar','환봉',9,1,52,'2025-08-05 02:38:24'),
(10,'flat-bar','평철',10,1,4,'2025-08-05 02:38:24'),
(11,'c-beam','C형강',11,1,19,'2025-08-05 02:38:24'),
(12,'deck-plate','테크플레이트',12,1,121,'2025-08-05 02:38:24'),
(13,'square-pipe','사각파이프',13,1,14,'2025-08-05 02:38:24'),
(14,'round-pipe','원형파이프',14,1,2,'2025-08-05 02:38:24'),
(15,'rail','레일',15,1,59,'2025-08-05 02:38:24'),
(16,'sheet-pile','강널말뚝',16,1,53,'2025-08-05 02:38:24'),
(17,'stainless','스테인레스',17,1,16,'2025-08-05 02:38:24');
/*!40000 ALTER TABLE `product_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_images`
--

DROP TABLE IF EXISTS `product_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `image_url` varchar(500) NOT NULL,
  `image_type` enum('main','detail','spec') DEFAULT 'detail',
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_images`
--

LOCK TABLES `product_images` WRITE;
/*!40000 ALTER TABLE `product_images` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_code` varchar(50) NOT NULL,
  `product_name` varchar(200) NOT NULL,
  `product_code` varchar(100) DEFAULT NULL,
  `specifications` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(12,2) DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `min_order_qty` int(11) DEFAULT 1,
  `stock_status` enum('in_stock','out_of_stock','on_order') DEFAULT 'in_stock',
  `main_image` varchar(500) DEFAULT NULL,
  `detail_images` text DEFAULT NULL,
  `features` text DEFAULT NULL,
  `dimensions` text DEFAULT NULL,
  `weight` varchar(100) DEFAULT NULL,
  `material` varchar(200) DEFAULT NULL,
  `manufacturer` varchar(200) DEFAULT NULL,
  `origin` varchar(100) DEFAULT NULL,
  `delivery_info` text DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `view_count` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_code` (`product_code`),
  KEY `category_code` (`category_code`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_code`) REFERENCES `product_categories` (`category_code`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES
(1,'rebar','철근(특판) D10',NULL,'D10 × 8m','콘크리트 구조물의 인장 보강재로 사용되는 고강도 이형철근입니다.',NULL,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,96,'2025-08-05 02:38:24','2025-08-12 05:55:04'),
(2,'rebar','철근(특판) D13',NULL,'D13 × 8m','콘크리트 구조물의 인장 보강재로 사용되는 고강도 이형철근입니다.',NULL,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,85,'2025-08-05 02:38:24','2025-08-12 06:10:26'),
(3,'h-beam','H형강 100×100',NULL,'100×100×6×8','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',NULL,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,4,'2025-08-05 02:38:24','2025-08-07 20:27:35'),
(4,'h-beam','H형강 200×200',NULL,'200×200×8×12','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',NULL,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,5,'2025-08-05 02:38:24','2025-08-14 14:11:29'),
(5,'steel-plate','일반 강판 6T',NULL,'6T × 1524 × 3048','일반 구조용 및 용접 구조용으로 사용되는 열간 압연 강판입니다.',NULL,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,11,'2025-08-05 02:38:24','2025-08-24 21:51:04');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rebar_length_data`
--

DROP TABLE IF EXISTS `rebar_length_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rebar_length_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `spec_name` varchar(50) NOT NULL,
  `length` decimal(5,1) NOT NULL,
  `piece_weight` decimal(10,3) DEFAULT NULL,
  `pieces_per_ton` int(11) DEFAULT NULL,
  `weight_per_ton` decimal(10,1) DEFAULT NULL,
  `unit_weight` decimal(10,3) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_spec_length` (`spec_name`,`length`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rebar_length_data`
--

LOCK TABLES `rebar_length_data` WRITE;
/*!40000 ALTER TABLE `rebar_length_data` DISABLE KEYS */;
INSERT INTO `rebar_length_data` VALUES
(1,'D10',6.0,NULL,300,1008.0,0.560,'2025-08-05 02:47:30'),
(2,'D10',7.0,NULL,270,1058.0,0.560,'2025-08-05 02:47:30'),
(3,'D10',8.0,NULL,210,941.0,0.560,'2025-08-05 02:47:30'),
(4,'D13',6.0,NULL,168,1004.0,0.995,'2025-08-05 02:47:30'),
(5,'D13',7.0,NULL,144,1004.0,0.995,'2025-08-05 02:47:30'),
(6,'D13',8.0,NULL,126,1004.0,0.995,'2025-08-05 02:47:30');
/*!40000 ALTER TABLE `rebar_length_data` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rebar_length_info`
--

DROP TABLE IF EXISTS `rebar_length_info`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rebar_length_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `spec_id` int(11) NOT NULL,
  `length` decimal(5,1) NOT NULL,
  `pieces_per_ton` int(11) NOT NULL,
  `total_weight` decimal(10,1) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_spec_length` (`spec_id`,`length`),
  CONSTRAINT `rebar_length_info_ibfk_1` FOREIGN KEY (`spec_id`) REFERENCES `rebar_specifications` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rebar_length_info`
--

LOCK TABLES `rebar_length_info` WRITE;
/*!40000 ALTER TABLE `rebar_length_info` DISABLE KEYS */;
/*!40000 ALTER TABLE `rebar_length_info` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rebar_materials`
--

DROP TABLE IF EXISTS `rebar_materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rebar_materials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `material_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `material_name` (`material_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rebar_materials`
--

LOCK TABLES `rebar_materials` WRITE;
/*!40000 ALTER TABLE `rebar_materials` DISABLE KEYS */;
/*!40000 ALTER TABLE `rebar_materials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rebar_prices`
--

DROP TABLE IF EXISTS `rebar_prices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rebar_prices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `spec_name` varchar(50) NOT NULL,
  `origin` varchar(100) DEFAULT NULL,
  `manufacturer` varchar(100) DEFAULT NULL,
  `price` decimal(12,2) DEFAULT NULL,
  `price_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_spec_origin` (`spec_name`,`origin`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rebar_prices`
--

LOCK TABLES `rebar_prices` WRITE;
/*!40000 ALTER TABLE `rebar_prices` DISABLE KEYS */;
INSERT INTO `rebar_prices` VALUES
(1,'D10','포항','현대제철',850000.00,'2025-08-05',1,'2025-08-05 02:44:08','2025-08-05 02:44:08'),
(2,'D13','포항','현대제철',830000.00,'2025-08-05',1,'2025-08-05 02:44:08','2025-08-05 02:44:08'),
(3,'D16','포항','현대제철',820000.00,'2025-08-05',1,'2025-08-05 02:44:08','2025-08-05 02:44:08');
/*!40000 ALTER TABLE `rebar_prices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rebar_products`
--

DROP TABLE IF EXISTS `rebar_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rebar_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `spec_id` int(11) NOT NULL,
  `material_id` int(11) DEFAULT NULL,
  `origin` varchar(100) DEFAULT NULL,
  `manufacturer` varchar(100) DEFAULT NULL,
  `stock_status` varchar(50) DEFAULT NULL,
  `price` decimal(12,2) DEFAULT NULL,
  `available_origins` text DEFAULT NULL,
  `stock_types` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `spec_id` (`spec_id`),
  KEY `material_id` (`material_id`),
  CONSTRAINT `rebar_products_ibfk_1` FOREIGN KEY (`spec_id`) REFERENCES `rebar_specifications` (`id`),
  CONSTRAINT `rebar_products_ibfk_2` FOREIGN KEY (`material_id`) REFERENCES `rebar_materials` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rebar_products`
--

LOCK TABLES `rebar_products` WRITE;
/*!40000 ALTER TABLE `rebar_products` DISABLE KEYS */;
/*!40000 ALTER TABLE `rebar_products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rebar_specifications`
--

DROP TABLE IF EXISTS `rebar_specifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rebar_specifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `spec_name` varchar(50) NOT NULL,
  `diameter` decimal(5,1) NOT NULL,
  `weight_per_meter` decimal(10,3) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `spec_name` (`spec_name`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rebar_specifications`
--

LOCK TABLES `rebar_specifications` WRITE;
/*!40000 ALTER TABLE `rebar_specifications` DISABLE KEYS */;
INSERT INTO `rebar_specifications` VALUES
(1,'D10',10.0,0.560,'2025-08-05 02:40:29',1),
(2,'D13',12.7,0.995,'2025-08-05 02:40:29',1),
(3,'D16',15.9,1.560,'2025-08-05 02:40:29',1),
(4,'D19',19.1,2.250,'2025-08-05 02:40:29',1),
(5,'D22',22.2,3.040,'2025-08-05 02:40:29',1),
(6,'D25',25.4,3.980,'2025-08-05 02:40:29',1),
(7,'D29',28.6,5.040,'2025-08-05 02:40:29',1),
(8,'D32',31.8,6.230,'2025-08-05 02:40:29',1),
(9,'D35',34.9,7.510,'2025-08-05 02:40:29',1),
(10,'D38',38.1,8.950,'2025-08-05 02:40:29',1),
(11,'D41',41.3,10.500,'2025-08-05 02:40:29',1),
(12,'D51',50.8,15.900,'2025-08-05 02:40:29',1);
/*!40000 ALTER TABLE `rebar_specifications` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_settings`
--

LOCK TABLES `site_settings` WRITE;
/*!40000 ALTER TABLE `site_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `site_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `unit_weights`
--

DROP TABLE IF EXISTS `unit_weights`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `unit_weights` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_type` varchar(50) NOT NULL COMMENT '제품유형',
  `specification` varchar(100) NOT NULL COMMENT '규격',
  `unit_weight` decimal(10,2) NOT NULL COMMENT '단위중량',
  `material` varchar(50) DEFAULT NULL COMMENT '재질',
  `height` int(11) DEFAULT NULL COMMENT '높이',
  `width` int(11) DEFAULT NULL COMMENT '너비',
  `web_thickness` decimal(5,1) DEFAULT NULL COMMENT '웹두께',
  `flange_thickness` decimal(5,1) DEFAULT NULL COMMENT '플랜지두께',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_spec` (`product_type`,`specification`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `unit_weights`
--

LOCK TABLES `unit_weights` WRITE;
/*!40000 ALTER TABLE `unit_weights` DISABLE KEYS */;
INSERT INTO `unit_weights` VALUES
(1,'H형강','100×100×6×8',17.20,NULL,NULL,NULL,NULL,NULL,1,'2025-08-05 02:40:29','2025-08-05 02:40:29'),
(2,'H형강','125×125×6.5×9',23.80,NULL,NULL,NULL,NULL,NULL,1,'2025-08-05 02:40:29','2025-08-05 02:40:29'),
(3,'H형강','150×150×7×10',31.50,NULL,NULL,NULL,NULL,NULL,1,'2025-08-05 02:40:29','2025-08-05 02:40:29'),
(4,'H형강','200×200×8×12',50.50,NULL,NULL,NULL,NULL,NULL,1,'2025-08-05 02:40:29','2025-08-05 02:40:29'),
(5,'H형강','250×250×9×14',72.40,NULL,NULL,NULL,NULL,NULL,1,'2025-08-05 02:40:29','2025-08-05 02:40:29'),
(6,'경량H형강','100×50×5×7',9.30,NULL,NULL,NULL,NULL,NULL,1,'2025-08-05 02:40:29','2025-08-05 02:40:29'),
(7,'경량H형강','150×75×5×7',14.00,NULL,NULL,NULL,NULL,NULL,1,'2025-08-05 02:40:29','2025-08-05 02:40:29'),
(8,'경량H형강','200×100×5.5×8',21.30,NULL,NULL,NULL,NULL,NULL,1,'2025-08-05 02:40:29','2025-08-05 02:40:29');
/*!40000 ALTER TABLE `unit_weights` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-08-26  5:09:48
