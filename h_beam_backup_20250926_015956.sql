/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.13-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: project1_db
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
  `length` decimal(5,1) DEFAULT NULL,
  `manufacturer` varchar(200) DEFAULT NULL,
  `origin` varchar(100) DEFAULT NULL,
  `delivery_info` text DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `view_count` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `calculation_type` enum('linear','sheet') DEFAULT 'linear' COMMENT '계산 유형: linear(선형), sheet(판재)',
  `unit_weight_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '규격별 단위중량 데이터' CHECK (json_valid(`unit_weight_data`)),
  `available_materials` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '사용 가능한 재질 목록' CHECK (json_valid(`available_materials`)),
  `available_sizes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '사용 가능한 규격 목록' CHECK (json_valid(`available_sizes`)),
  `has_calculator` tinyint(1) DEFAULT 0 COMMENT '계산기 사용 여부',
  `display_mode` enum('single','by_specification') DEFAULT 'single' COMMENT '표시 모드: single(단일), by_specification(규격별)',
  `parent_product_id` int(11) DEFAULT NULL COMMENT '부모 제품 ID (규격별 제품인 경우)',
  `specification` varchar(100) DEFAULT NULL COMMENT '개별 규격 (규격별 제품인 경우)',
  `specification_weight` decimal(10,3) DEFAULT NULL COMMENT '해당 규격의 단위중량',
  `origin_price_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '원산지별 추가 가격 데이터' CHECK (json_valid(`origin_price_data`)),
  `material_price_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '재질별 추가 가격 데이터' CHECK (json_valid(`material_price_data`)),
  `available_origins` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '사용 가능한 원산지 목록' CHECK (json_valid(`available_origins`)),
  `show_on_homepage` tinyint(1) DEFAULT 0 COMMENT '홈페이지 표시 여부',
  `homepage_display_order` int(11) DEFAULT 0 COMMENT '홈페이지 표시 순서',
  `min_length` decimal(10,2) DEFAULT NULL COMMENT '최소 길이(m)',
  `max_length` decimal(10,2) DEFAULT NULL COMMENT '최대 길이(m)',
  `standard_length` decimal(10,2) DEFAULT NULL COMMENT '표준 길이(m)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_code` (`product_code`),
  KEY `category_code` (`category_code`),
  KEY `idx_parent_product` (`parent_product_id`),
  KEY `idx_specification` (`specification`),
  CONSTRAINT `fk_parent_product` FOREIGN KEY (`parent_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_code`) REFERENCES `product_categories` (`category_code`)
) ENGINE=InnoDB AUTO_INCREMENT=206 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--
-- WHERE:  product_name LIKE '%H형강%'

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES
(3,'h-beam','H형강 100×100',NULL,'100×100×6×8','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,4,'2025-08-05 02:38:24','2025-09-26 01:52:57','linear','{\"100×100×6×8\": {\"SS400\": 17.2, \"SM490\": 17.2}}','[\"SS400\", \"SM490\"]','[\"100×100×6×8\"]',1,'single',NULL,'100×100×6×8',17.200,NULL,NULL,NULL,1,2,8.00,25.00,10.00),
(4,'h-beam','H형강 200×200',NULL,'200×200×8×12','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,'','','','SS400',NULL,'','국산','',0,1,13,'2025-08-05 02:38:24','2025-09-26 01:52:57','linear','{\"200×200×8×12\": {\"SS400\": 49.9, \"SM490\": 49.9}}','[\"SS400\",\"SM490\",\"SUS430\",\"SCM440\"]','[\"200×200×8×12\"]',1,'single',NULL,'200×200×8×12',49.900,'{\"국산\":\"0\",\"일본산\":\"0\"}','{\"SS400\":\"0\",\"SM490\":\"0\",\"SUS430\":\"0\",\"SCM440\":\"0\"}','[\"국산\",\"일본산\"]',0,0,8.00,25.00,10.00),
(7,'light-h-beam','경량H형강 600×200×8×14','LHB-600-200','600×200×8×14',NULL,1000.00,NULL,1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,8,'2025-09-18 12:16:43','2025-09-26 01:52:57','linear','{\"600×200×8×14\": {\"SS400\": 117.0, \"SM490\": 117.0}}','[\"SS400\", \"SM490\"]','[\"600×200×8×14\"]',1,'single',NULL,'600×200×8×14',117.000,NULL,NULL,NULL,1,4,5.00,15.00,10.00),
(13,'h-beam','H형강 125×125×6.5×9',NULL,'125×125×6.5×9','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:52:13','2025-09-26 01:52:57','linear','{\"125×125×6.5×9\": {\"SS400\": 23.8, \"A36\": 23.8}}','[\"SS400\", \"A36\"]',NULL,1,'single',NULL,'125×125×6.5×9',23.800,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(14,'h-beam','H형강 150×75×5×7',NULL,'150×75×5×7','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:06','2025-09-26 01:52:57','linear','{\"150×75×5×7\":{\"SHN400\":14}}','[\"SHN400\"]',NULL,1,'single',NULL,'150×75×5×7',14.000,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(15,'h-beam','H형강 148×100×6×9',NULL,'148×100×6×9','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:06','2025-09-26 01:52:57','linear','{\"148×100×6×9\":{\"SS490\":21.1}}','[\"SS490\"]',NULL,1,'single',NULL,'148×100×6×9',21.100,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(16,'h-beam','H형강 150×150×7×10',NULL,'150×150×7×10','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:06','2025-09-26 01:52:57','linear','{\"150×150×7×10\":{\"SS540\":31.5}}','[\"SS540\"]',NULL,1,'single',NULL,'150×150×7×10',31.500,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(17,'h-beam','H형강 198×99×4.5×7',NULL,'198×99×4.5×7','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:06','2025-09-26 01:52:57','linear','{\"198×99×4.5×7\":{\"SM400A\":18.2}}','[\"SM400A\"]',NULL,1,'single',NULL,'198×99×4.5×7',18.200,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(18,'h-beam','H형강 200×100×5.5×8',NULL,'200×100×5.5×8','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:06','2025-09-26 01:52:57','linear','{\"200×100×5.5×8\":{\"SM400B\":21.3}}','[\"SM400B\"]',NULL,1,'single',NULL,'200×100×5.5×8',21.300,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(19,'h-beam','H형강 194×150×6×9',NULL,'194×150×6×9','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:06','2025-09-26 01:52:57','linear','{\"194×150×6×9\":{\"SHN490\":30.6}}','[\"SHN490\"]',NULL,1,'single',NULL,'194×150×6×9',30.600,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(20,'h-beam','H형강 200×204×12×12',NULL,'200×204×12×12','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:06','2025-09-26 01:52:57','linear','{\"200×204×12×12\":{\"SM490B\":56.2}}','[\"SM490B\"]',NULL,1,'single',NULL,'200×204×12×12',56.200,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(21,'h-beam','H형강 208×202×10×16',NULL,'208×202×10×16','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:06','2025-09-26 01:52:57','linear','{\"208×202×10×16\":{\"SM490YA\":65.7}}','[\"SM490YA\"]',NULL,1,'single',NULL,'208×202×10×16',65.700,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(22,'h-beam','H형강 248×124×5×8',NULL,'248×124×5×8','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:06','2025-09-26 01:52:57','linear','{\"248×124×5×8\":{\"SM490YB\":25.7}}','[\"SM490YB\"]',NULL,1,'single',NULL,'248×124×5×8',25.700,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(23,'h-beam','H형강 250×125×6×9',NULL,'250×125×6×9','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:06','2025-09-26 01:52:57','linear','{\"250×125×6×9\":{\"SS400\":29.6}}','[\"SS400\"]',NULL,1,'single',NULL,'250×125×6×9',29.600,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(24,'h-beam','H형강 244×175×7×11',NULL,'244×175×7×11','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:06','2025-09-26 01:52:57','linear','{\"244×175×7×11\":{\"SS400\":44.1}}','[\"SS400\"]',NULL,1,'single',NULL,'244×175×7×11',44.100,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(25,'h-beam','H형강 244×252×11×11',NULL,'244×252×11×11','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:06','2025-09-26 01:52:57','linear','{\"244×252×11×11\":{\"SS400\":64.4}}','[\"SS400\"]',NULL,1,'single',NULL,'244×252×11×11',64.400,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(26,'h-beam','H형강 248×249×8×13',NULL,'248×249×8×13','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:06','2025-09-26 01:52:57','linear','{\"248×249×8×13\":{\"SS400\":66.5}}','[\"SS400\"]',NULL,1,'single',NULL,'248×249×8×13',66.500,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(27,'h-beam','H형강 250×250×9×14',NULL,'250×250×9×14','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:06','2025-09-26 01:52:57','linear','{\"250×250×9×14\":{\"SS400\":72.4}}','[\"SS400\"]',NULL,1,'single',NULL,'250×250×9×14',72.400,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(28,'h-beam','H형강 250×255×14×14',NULL,'250×255×14×14','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:06','2025-09-26 01:52:57','linear','{\"250×255×14×14\":{\"SS400\":82.2}}','[\"SS400\"]',NULL,1,'single',NULL,'250×255×14×14',82.200,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(29,'h-beam','H형강 298×149×5.5×8',NULL,'298×149×5.5×8','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:06','2025-09-26 01:52:57','linear','{\"298×149×5.5×8\":{\"SS400\":32}}','[\"SS400\"]',NULL,1,'single',NULL,'298×149×5.5×8',32.000,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(30,'h-beam','H형강 300×150×6.5×9',NULL,'300×150×6.5×9','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:06','2025-09-26 01:52:57','linear','{\"300×150×6.5×9\":{\"SS400\":36.7}}','[\"SS400\"]',NULL,1,'single',NULL,'300×150×6.5×9',36.700,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(31,'h-beam','H형강 294×200×8×12',NULL,'294×200×8×12','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:06','2025-09-26 01:52:57','linear','{\"294×200×8×12\":{\"SS400\":56.8}}','[\"SS400\"]',NULL,1,'single',NULL,'294×200×8×12',56.800,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(32,'h-beam','H형강 298×201×9×14',NULL,'298×201×9×14','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:06','2025-09-26 01:52:57','linear','{\"298×201×9×14\":{\"SS400\":65.4}}','[\"SS400\"]',NULL,1,'single',NULL,'298×201×9×14',65.400,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(33,'h-beam','H형강 294×302×12×12',NULL,'294×302×12×12','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:06','2025-09-26 01:52:57','linear','{\"294×302×12×12\":{\"SS400\":84.5}}','[\"SS400\"]',NULL,1,'single',NULL,'294×302×12×12',84.500,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(34,'h-beam','H형강 298×299×9×14',NULL,'298×299×9×14','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:06','2025-09-26 01:52:57','linear','{\"298×299×9×14\":{\"SS400\":87}}','[\"SS400\"]',NULL,1,'single',NULL,'298×299×9×14',87.000,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(35,'h-beam','H형강 300×300×10×15',NULL,'300×300×10×15','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:06','2025-09-26 01:52:57','linear','{\"300×300×10×15\":{\"SS400\":94}}','[\"SS400\"]',NULL,1,'single',NULL,'300×300×10×15',94.000,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(36,'h-beam','H형강 300×305×15×15',NULL,'300×305×15×15','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:06','2025-09-26 01:52:57','linear','{\"300×305×15×15\":{\"SS400\":106}}','[\"SS400\"]',NULL,1,'single',NULL,'300×305×15×15',106.000,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(37,'h-beam','H형강 304×301×11×17',NULL,'304×301×11×17','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:06','2025-09-26 01:52:57','linear','{\"304×301×11×17\":{\"SS400\":106}}','[\"SS400\"]',NULL,1,'single',NULL,'304×301×11×17',106.000,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(38,'h-beam','H형강 310×305×15×20',NULL,'310×305×15×20','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:06','2025-09-26 01:52:57','linear','{\"310×305×15×20\":{\"SS400\":130}}','[\"SS400\"]',NULL,1,'single',NULL,'310×305×15×20',130.000,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(39,'h-beam','H형강 310×310×20×20',NULL,'310×310×20×20','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:06','2025-09-26 01:52:57','linear','{\"310×310×20×20\":{\"SS400\":142}}','[\"SS400\"]',NULL,1,'single',NULL,'310×310×20×20',142.000,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(40,'h-beam','H형강 346×174×6×9',NULL,'346×174×6×9','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:06','2025-09-26 01:52:57','linear','{\"346×174×6×9\":{\"SS400\":41.4}}','[\"SS400\"]',NULL,1,'single',NULL,'346×174×6×9',41.400,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(41,'h-beam','H형강 350×175×7×11',NULL,'350×175×7×11','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:06','2025-09-26 01:52:57','linear','{\"350×175×7×11\":{\"SS400\":49.6}}','[\"SS400\"]',NULL,1,'single',NULL,'350×175×7×11',49.600,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(42,'h-beam','H형강 354×176×8×13',NULL,'354×176×8×13','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:06','2025-09-26 01:52:57','linear','{\"354×176×8×13\":{\"SS400\":57.8}}','[\"SS400\"]',NULL,1,'single',NULL,'354×176×8×13',57.800,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(43,'h-beam','H형강 336×249×8×12',NULL,'336×249×8×12','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:06','2025-09-26 01:52:57','linear','{\"336×249×8×12\":{\"SS400\":69.2}}','[\"SS400\"]',NULL,1,'single',NULL,'336×249×8×12',69.200,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(44,'h-beam','H형강 340×250×9×14',NULL,'340×250×9×14','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:06','2025-09-26 01:52:57','linear','{\"340×250×9×14\":{\"SS400\":79.7}}','[\"SS400\"]',NULL,1,'single',NULL,'340×250×9×14',79.700,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(45,'h-beam','H형강 344×348×10×16',NULL,'344×348×10×16','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:06','2025-09-26 01:52:57','linear','{\"344×348×10×16\":{\"SS400\":115}}','[\"SS400\"]',NULL,1,'single',NULL,'344×348×10×16',115.000,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(46,'h-beam','H형강 344×354×16×16',NULL,'344×354×16×16','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:06','2025-09-26 01:52:57','linear','{\"344×354×16×16\":{\"SS400\":131}}','[\"SS400\"]',NULL,1,'single',NULL,'344×354×16×16',131.000,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(47,'h-beam','H형강 350×350×12×19',NULL,'350×350×12×19','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:06','2025-09-26 01:52:57','linear','{\"350×350×12×19\":{\"SS400\":137}}','[\"SS400\"]',NULL,1,'single',NULL,'350×350×12×19',137.000,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(48,'h-beam','H형강 350×357×19×19',NULL,'350×357×19×19','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:06','2025-09-26 01:52:57','linear','{\"350×357×19×19\":{\"SS400\":156}}','[\"SS400\"]',NULL,1,'single',NULL,'350×357×19×19',156.000,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(49,'h-beam','H형강 396×199×7×11',NULL,'396×199×7×11','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:06','2025-09-26 01:52:57','linear','{\"396×199×7×11\":{\"SS400\":56.6}}','[\"SS400\"]',NULL,1,'single',NULL,'396×199×7×11',56.600,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(50,'h-beam','H형강 400×200×8×13',NULL,'400×200×8×13','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:06','2025-09-26 01:52:57','linear','{\"400×200×8×13\":{\"SS400\":66}}','[\"SS400\"]',NULL,1,'single',NULL,'400×200×8×13',66.000,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(51,'h-beam','H형강 404×201×9×15',NULL,'404×201×9×15','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:06','2025-09-26 01:52:57','linear','{\"404×201×9×15\":{\"SS400\":75.5}}','[\"SS400\"]',NULL,1,'single',NULL,'404×201×9×15',75.500,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(52,'h-beam','H형강 386×299×9×14',NULL,'386×299×9×14','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:06','2025-09-26 01:52:57','linear','{\"386×299×9×14\":{\"SS400\":94.3}}','[\"SS400\"]',NULL,1,'single',NULL,'386×299×9×14',94.300,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(53,'h-beam','H형강 390×300×10×16',NULL,'390×300×10×16','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:06','2025-09-26 01:52:57','linear','{\"390×300×10×16\":{\"SS400\":107}}','[\"SS400\"]',NULL,1,'single',NULL,'390×300×10×16',107.000,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(54,'h-beam','H형강 388×402×15×15',NULL,'388×402×15×15','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:06','2025-09-26 01:52:57','linear','{\"388×402×15×15\":{\"SS400\":140}}','[\"SS400\"]',NULL,1,'single',NULL,'388×402×15×15',140.000,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(55,'h-beam','H형강 394×398×11×18',NULL,'394×398×11×18','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:06','2025-09-26 01:52:57','linear','{\"394×398×11×18\":{\"SS400\":147}}','[\"SS400\"]',NULL,1,'single',NULL,'394×398×11×18',147.000,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(56,'h-beam','H형강 394×405×18×18',NULL,'394×405×18×18','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:06','2025-09-26 01:52:57','linear','{\"394×405×18×18\":{\"SS400\":168}}','[\"SS400\"]',NULL,1,'single',NULL,'394×405×18×18',168.000,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(57,'h-beam','H형강 400×400×13×21',NULL,'400×400×13×21','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:07','2025-09-26 01:52:57','linear','{\"400×400×13×21\":{\"SS400\":172}}','[\"SS400\"]',NULL,1,'single',NULL,'400×400×13×21',172.000,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(58,'h-beam','H형강 400×408×21×21',NULL,'400×408×21×21','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:07','2025-09-26 01:52:57','linear','{\"400×408×21×21\":{\"SS400\":197}}','[\"SS400\"]',NULL,1,'single',NULL,'400×408×21×21',197.000,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(59,'h-beam','H형강 406×403×16×24',NULL,'406×403×16×24','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:07','2025-09-26 01:52:57','linear','{\"406×403×16×24\":{\"SS400\":200}}','[\"SS400\"]',NULL,1,'single',NULL,'406×403×16×24',200.000,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(60,'h-beam','H형강 414×405×18×28',NULL,'414×405×18×28','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:07','2025-09-26 01:52:57','linear','{\"414×405×18×28\":{\"SS400\":232}}','[\"SS400\"]',NULL,1,'single',NULL,'414×405×18×28',232.000,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(61,'h-beam','H형강 428×407×20×35',NULL,'428×407×20×35','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:07','2025-09-26 01:52:57','linear','{\"428×407×20×35\":{\"SS400\":283}}','[\"SS400\"]',NULL,1,'single',NULL,'428×407×20×35',283.000,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(62,'h-beam','H형강 458×417×30×50',NULL,'458×417×30×50','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:12','2025-09-26 01:52:57','linear','{\"458×417×30×50\":{\"SS400\":415}}','[\"SS400\"]',NULL,1,'single',NULL,'458×417×30×50',415.000,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(63,'h-beam','H형강 498×432×45×70',NULL,'498×432×45×70','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:12','2025-09-26 01:52:57','linear','{\"498×432×45×70\":{\"SS400\":605}}','[\"SS400\"]',NULL,1,'single',NULL,'498×432×45×70',605.000,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(64,'h-beam','H형강 446×199×8×12',NULL,'446×199×8×12','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:12','2025-09-26 01:52:57','linear','{\"446×199×8×12\":{\"SS400\":66.2}}','[\"SS400\"]',NULL,1,'single',NULL,'446×199×8×12',66.200,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(65,'h-beam','H형강 450×200×9×14',NULL,'450×200×9×14','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:12','2025-09-26 01:52:57','linear','{\"450×200×9×14\":{\"SS400\":76}}','[\"SS400\"]',NULL,1,'single',NULL,'450×200×9×14',76.000,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(66,'h-beam','H형강 434×299×10×15',NULL,'434×299×10×15','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:12','2025-09-26 01:52:57','linear','{\"434×299×10×15\":{\"SS400\":106}}','[\"SS400\"]',NULL,1,'single',NULL,'434×299×10×15',106.000,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(67,'h-beam','H형강 440×300×11×18',NULL,'440×300×11×18','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:12','2025-09-26 01:52:57','linear','{\"440×300×11×18\":{\"SS400\":124}}','[\"SS400\"]',NULL,1,'single',NULL,'440×300×11×18',124.000,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(68,'h-beam','H형강 496×199×9×14',NULL,'496×199×9×14','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:12','2025-09-26 01:52:57','linear','{\"496×199×9×14\":{\"SS400\":79.5}}','[\"SS400\"]',NULL,1,'single',NULL,'496×199×9×14',79.500,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(69,'h-beam','H형강 500×200×10×16',NULL,'500×200×10×16','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:12','2025-09-26 01:52:57','linear','{\"500×200×10×16\":{\"SS400\":89.6}}','[\"SS400\"]',NULL,1,'single',NULL,'500×200×10×16',89.600,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(70,'h-beam','H형강 506×201×11×19',NULL,'506×201×11×19','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:12','2025-09-26 01:52:57','linear','{\"506×201×11×19\":{\"SS400\":103}}','[\"SS400\"]',NULL,1,'single',NULL,'506×201×11×19',103.000,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(71,'h-beam','H형강 482×300×11×15',NULL,'482×300×11×15','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:12','2025-09-26 01:52:57','linear','{\"482×300×11×15\":{\"SS400\":114}}','[\"SS400\"]',NULL,1,'single',NULL,'482×300×11×15',114.000,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(72,'h-beam','H형강 488×300×11×18',NULL,'488×300×11×18','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:12','2025-09-26 01:52:57','linear','{\"488×300×11×18\":{\"SS400\":128}}','[\"SS400\"]',NULL,1,'single',NULL,'488×300×11×18',128.000,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(73,'h-beam','H형강 596×199×10×15',NULL,'596×199×10×15','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,3,'2025-09-22 01:53:12','2025-09-26 01:52:57','linear','{\"596×199×10×15\":{\"SS400\":94.6}}','[\"SS400\"]',NULL,1,'single',NULL,'596×199×10×15',94.600,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(74,'h-beam','H형강 600×200×11×17',NULL,'600×200×11×17','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:12','2025-09-26 01:52:57','linear','{\"600×200×11×17\":{\"SS400\":106}}','[\"SS400\"]',NULL,1,'single',NULL,'600×200×11×17',106.000,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(75,'h-beam','H형강 606×201×12×20',NULL,'606×201×12×20','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:12','2025-09-26 01:52:57','linear','{\"606×201×12×20\":{\"SS400\":120}}','[\"SS400\"]',NULL,1,'single',NULL,'606×201×12×20',120.000,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(76,'h-beam','H형강 612×202×13×23',NULL,'612×202×13×23','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:12','2025-09-26 01:52:57','linear','{\"612×202×13×23\":{\"SS400\":134}}','[\"SS400\"]',NULL,1,'single',NULL,'612×202×13×23',134.000,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(77,'h-beam','H형강 582×300×12×17',NULL,'582×300×12×17','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:12','2025-09-26 01:52:57','linear','{\"582×300×12×17\":{\"SS400\":137}}','[\"SS400\"]',NULL,1,'single',NULL,'582×300×12×17',137.000,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(78,'h-beam','H형강 588×300×12×20',NULL,'588×300×12×20','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,2,'2025-09-22 01:53:12','2025-09-26 01:52:57','linear','{\"588×300×12×20\":{\"SS400\":151}}','[\"SS400\"]',NULL,1,'single',NULL,'588×300×12×20',151.000,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(79,'h-beam','H형강 594×302×14×23',NULL,'594×302×14×23','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:12','2025-09-26 01:52:57','linear','{\"594×302×14×23\":{\"SS400\":175}}','[\"SS400\"]',NULL,1,'single',NULL,'594×302×14×23',175.000,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(80,'h-beam','H형강 692×300×13×20',NULL,'692×300×13×20','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:12','2025-09-26 01:52:57','linear','{\"692×300×13×20\":{\"SS400\":166}}','[\"SS400\"]',NULL,1,'single',NULL,'692×300×13×20',166.000,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(81,'h-beam','H형강 700×300×13×24',NULL,'700×300×13×24','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:12','2025-09-26 01:52:57','linear','{\"700×300×13×24\":{\"SS400\":185}}','[\"SS400\"]',NULL,1,'single',NULL,'700×300×13×24',185.000,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(82,'h-beam','H형강 708×302×15×28',NULL,'708×302×15×28','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:12','2025-09-26 01:52:57','linear','{\"708×302×15×28\":{\"SS400\":215}}','[\"SS400\"]',NULL,1,'single',NULL,'708×302×15×28',215.000,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(83,'h-beam','H형강 792×300×14×22',NULL,'792×300×14×22','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,1,'2025-09-22 01:53:12','2025-09-26 01:52:57','linear','{\"792×300×14×22\":{\"SS400\":191}}','[\"SS400\"]',NULL,1,'single',NULL,'792×300×14×22',191.000,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(84,'h-beam','H형강 800×300×14×26',NULL,'800×300×14×26','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:12','2025-09-26 01:52:57','linear','{\"800×300×14×26\":{\"SS400\":210}}','[\"SS400\"]',NULL,1,'single',NULL,'800×300×14×26',210.000,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(85,'h-beam','H형강 808×302×16×30',NULL,'808×302×16×30','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 01:53:12','2025-09-26 01:52:57','linear','{\"808×302×16×30\":{\"SS400\":241}}','[\"SS400\"]',NULL,1,'single',NULL,'808×302×16×30',241.000,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(86,'h-beam','H형강 890×299×15×23',NULL,'890×299×15×23','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,1,'2025-09-22 01:53:12','2025-09-26 01:52:57','linear','{\"890×299×15×23\":{\"SS400\":213}}','[\"SS400\"]',NULL,1,'single',NULL,'890×299×15×23',213.000,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(87,'h-beam','H형강 900×300×16×28',NULL,'900×300×16×28','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,2,'2025-09-22 01:53:12','2025-09-26 01:52:57','linear','{\"900×300×16×28\":{\"SS400\":243}}','[\"SS400\"]',NULL,1,'single',NULL,'900×300×16×28',243.000,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(88,'h-beam','H형강 912×302×18×34',NULL,'912×302×18×34','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,2,'2025-09-22 01:53:12','2025-09-26 01:52:57','linear','{\"912×302×18×34\":{\"SS400\":286}}','[\"SS400\"]',NULL,1,'single',NULL,'912×302×18×34',286.000,NULL,NULL,NULL,0,0,8.00,25.00,10.00),
(89,'h-beam','H형강 918×303×19×37',NULL,'918×303×19×37','건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',1000.00,'TON',1,'in_stock',NULL,NULL,'','','','SS400',NULL,'','일본산','',0,1,15,'2025-09-22 01:53:13','2025-09-26 01:52:57','linear','{\"918×303×19×37\":{\"SS400\":307}}','[\"SS400\",\"SM490A\",\"SM570\"]',NULL,1,'single',NULL,'918×303×19×37',307.000,'{\"일본산\":\"100\",\"중국산\":\"200\",\"수입산\":\"300\"}','{\"SS400\":\"100\",\"SM490A\":\"200\",\"SM570\":\"300\"}','[\"일본산\",\"중국산\",\"수입산\"]',0,0,8.00,25.00,10.00);
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-09-26  1:59:56
