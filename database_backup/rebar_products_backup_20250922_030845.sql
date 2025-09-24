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
  `min_price` decimal(12,2) DEFAULT NULL,
  `max_price` decimal(12,2) DEFAULT NULL,
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
  `available_origins` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`available_origins`)),
  `origin_price_data` longtext DEFAULT NULL COMMENT '원산지별 추가 비용 정보 (JSON 형식, kg당 원)',
  `material_price_data` longtext DEFAULT NULL COMMENT '재질별 추가 비용 정보 (JSON 형식, kg당 원)',
  `delivery_info` text DEFAULT NULL,
  `quality_cert` varchar(500) DEFAULT NULL COMMENT '품질 인증',
  `product_features` text DEFAULT NULL COMMENT '제품 특징',
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
  `length_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '길이별 철근 데이터 (본중, 톤당본수, 톤당중량)' CHECK (json_valid(`length_data`)),
  `weight_per_meter` decimal(10,4) DEFAULT NULL COMMENT '미터당 중량(kg)',
  `pieces_per_ton` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '길이별 톤당 본수 데이터' CHECK (json_valid(`pieces_per_ton`)),
  `show_on_homepage` tinyint(1) DEFAULT 0,
  `homepage_display_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_code` (`product_code`),
  KEY `category_code` (`category_code`),
  KEY `idx_parent_product` (`parent_product_id`),
  KEY `idx_specification` (`specification`),
  KEY `idx_products_category_name` (`category_code`,`product_name`),
  KEY `idx_homepage_display` (`show_on_homepage`,`homepage_display_order`),
  CONSTRAINT `fk_parent_product` FOREIGN KEY (`parent_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_code`) REFERENCES `product_categories` (`category_code`)
) ENGINE=InnoDB AUTO_INCREMENT=473 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--
-- WHERE:  id BETWEEN 153 AND 171

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES
(153,'h-beam','H형강',NULL,NULL,'',1000.00,900.00,1100.00,NULL,1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[\"국산\",\"중국산\",\"일본산\",\"베트남산\",\"바레이산\",\"수입산\",\"쟁가재고\",\"중고\"]','{\"국산\":0,\"중국산\":-50,\"일본산\":200,\"베트남산\":-30,\"바레인산\":100,\"수입산\":50,\"장기재고\":-100,\"중고\":-150}','{\"SS400\":0,\"SM490A\":50,\"SM490B\":200}',NULL,NULL,NULL,0,1,0,'2025-08-31 06:35:29','2025-09-18 09:29:02','linear','{\"175*175*7.5*11\":{\"SS400\":40.2,\"SM490A\":40.2,\"SM490B\":40.2}}','[\"SS400\",\"SM490A\",\"SM490B\"]','[\"175*175*7.5*11\"]',1,'single',149,'175*175*7.5*11',40.200,NULL,NULL,NULL,0,0),
(154,'h-beam','H형강',NULL,NULL,'',1000.00,900.00,1100.00,NULL,1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[\"국산\",\"중국산\",\"일본산\",\"베트남산\",\"바레이산\",\"수입산\",\"쟁가재고\",\"중고\"]','{\"국산\":0,\"중국산\":-50,\"일본산\":200,\"베트남산\":-30,\"바레인산\":100,\"수입산\":50,\"장기재고\":-100,\"중고\":-150}','{\"SS400\":0,\"SM490A\":0,\"SM490B\":250}',NULL,NULL,NULL,0,1,0,'2025-08-31 06:35:29','2025-09-18 09:29:02','linear','{\"200*200*8*12\":{\"SS400\":49.9,\"SM490A\":49.9,\"SM490B\":49.9}}','[\"SS400\",\"SM490A\",\"SM490B\"]','[\"200*200*8*12\"]',1,'single',149,'200*200*8*12',49.900,NULL,NULL,NULL,0,0),
(155,'h-beam','H형강',NULL,NULL,'',1000.00,900.00,1100.00,NULL,1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[\"국산\",\"중국산\",\"일본산\",\"베트남산\",\"바레이산\",\"수입산\",\"쟁가재고\",\"중고\"]','{\"국산\":0,\"중국산\":-50,\"일본산\":200,\"베트남산\":-30,\"바레인산\":100,\"수입산\":50,\"장기재고\":-100,\"중고\":-150}','{\"SS400\":0,\"SM490A\":50,\"SM490B\":150}',NULL,NULL,NULL,0,1,0,'2025-08-31 06:35:29','2025-09-18 09:29:02','linear','{\"250*250*9*14\":{\"SS400\":72.4,\"SM490A\":72.4,\"SM490B\":72.4}}','[\"SS400\",\"SM490A\",\"SM490B\"]','[\"250*250*9*14\"]',1,'single',149,'250*250*9*14',72.400,NULL,NULL,NULL,0,0),
(156,'h-beam','H형강',NULL,NULL,'',1000.00,900.00,1100.00,NULL,1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[\"국산\",\"중국산\",\"일본산\",\"베트남산\",\"바레이산\",\"수입산\",\"쟁가재고\",\"중고\"]','{\"국산\":0,\"중국산\":-50,\"일본산\":200,\"베트남산\":-30,\"바레인산\":100,\"수입산\":50,\"장기재고\":-100,\"중고\":-150}','{\"SS400\":0,\"SM490A\":50,\"SM490B\":150}',NULL,NULL,NULL,0,1,1,'2025-08-31 06:35:29','2025-09-18 09:29:02','linear','{\"300*300*10*15\":{\"SS400\":94,\"SM490A\":94,\"SM490B\":94}}','[\"SS400\",\"SM490A\",\"SM490B\"]','[\"300*300*10*15\"]',1,'single',149,'300*300*10*15',94.000,NULL,NULL,NULL,0,0),
(157,'h-beam','H형강',NULL,NULL,'',1000.00,900.00,1100.00,NULL,1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[\"국산\",\"중국산\",\"일본산\",\"베트남산\",\"바레이산\",\"수입산\",\"쟁가재고\",\"중고\"]','{\"국산\":0,\"중국산\":-50,\"일본산\":200,\"베트남산\":-30,\"바레인산\":100,\"수입산\":50,\"장기재고\":-100,\"중고\":-150}','{\"SS400\":0,\"SM490A\":0,\"SM490B\":250}',NULL,NULL,NULL,0,1,0,'2025-08-31 06:35:29','2025-09-18 09:29:02','linear','{\"300*300*12*12\":{\"SS400\":94,\"SM490A\":94,\"SM490B\":94}}','[\"SS400\",\"SM490A\",\"SM490B\"]','[\"300*300*12*12\"]',1,'single',149,'300*300*12*12',94.000,NULL,NULL,NULL,0,0),
(158,'h-beam','H형강',NULL,NULL,'',1000.00,900.00,1100.00,NULL,1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[\"국산\",\"중국산\",\"일본산\",\"베트남산\",\"바레이산\",\"수입산\",\"쟁가재고\",\"중고\"]','{\"국산\":0,\"중국산\":-50,\"일본산\":200,\"베트남산\":-30,\"바레인산\":100,\"수입산\":50,\"장기재고\":-100,\"중고\":-150}','{\"SS400\":0,\"SM490A\":50,\"SM490B\":0}',NULL,NULL,NULL,0,1,0,'2025-08-31 06:35:29','2025-09-18 09:29:02','linear','{\"350*350*12*19\":{\"SS400\":137,\"SM490A\":137,\"SM490B\":137}}','[\"SS400\",\"SM490A\",\"SM490B\"]','[\"350*350*12*19\"]',1,'single',149,'350*350*12*19',137.000,NULL,NULL,NULL,0,0),
(159,'h-beam','H형강',NULL,NULL,'',1000.00,900.00,1100.00,NULL,1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[\"국산\",\"중국산\",\"일본산\",\"베트남산\",\"바레이산\",\"수입산\",\"쟁가재고\",\"중고\"]','{\"국산\":0,\"중국산\":-50,\"일본산\":200,\"베트남산\":-30,\"바레인산\":100,\"수입산\":50,\"장기재고\":-100,\"중고\":-150}','{\"SS400\":0,\"SM490A\":100,\"SM490B\":200}',NULL,NULL,NULL,0,1,7,'2025-08-31 06:35:29','2025-09-18 09:29:02','linear','{\"400*400*13*21\":{\"SS400\":172,\"SM490A\":172,\"SM490B\":172}}','[\"SS400\",\"SM490A\",\"SM490B\"]','[\"400*400*13*21\"]',1,'single',149,'400*400*13*21',172.000,NULL,NULL,NULL,0,0),
(160,'h-beam','H형강',NULL,NULL,'',1000.00,900.00,1100.00,NULL,1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[\"국산\",\"중국산\",\"일본산\",\"베트남산\",\"바레이산\",\"수입산\",\"쟁가재고\",\"중고\"]','{\"국산\":0,\"중국산\":-50,\"일본산\":200,\"베트남산\":-30,\"바레인산\":100,\"수입산\":50,\"장기재고\":-100,\"중고\":-150}','{\"SS400\":0,\"SM490A\":250,\"SM490B\":150}',NULL,NULL,NULL,0,1,11,'2025-08-31 06:35:29','2025-09-18 09:29:02','linear','{\"450*400*18*28\":{\"SS400\":233,\"SM490A\":233,\"SM490B\":233}}','[\"SS400\",\"SM490A\",\"SM490B\"]','[\"450*400*18*28\"]',1,'single',149,'450*400*18*28',233.000,NULL,NULL,NULL,0,0),
(161,'h-beam','H형강',NULL,NULL,'',1000.00,900.00,1100.00,NULL,1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[\"국산\",\"중국산\",\"일본산\",\"베트남산\",\"바레이산\",\"수입산\",\"쟁가재고\",\"중고\"]','{\"국산\":0,\"중국산\":-50,\"일본산\":200,\"베트남산\":-30,\"바레인산\":100,\"수입산\":50,\"장기재고\":-100,\"중고\":-150}','{\"SS400\":0,\"SM490A\":100,\"SM490B\":250}',NULL,NULL,NULL,0,1,12,'2025-08-31 06:35:29','2025-09-18 09:29:02','linear','{\"500*300*11*18\":{\"SS400\":114,\"SM490A\":114,\"SM490B\":114}}','[\"SS400\",\"SM490A\",\"SM490B\"]','[\"500*300*11*18\"]',1,'single',149,'500*300*11*18',114.000,NULL,NULL,NULL,0,0),
(162,'h-beam','H형강',NULL,NULL,'',1000.00,900.00,1100.00,NULL,1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[\"국산\",\"중국산\",\"일본산\",\"베트남산\",\"바레이산\",\"수입산\",\"쟁가재고\",\"중고\"]','{\"국산\":0,\"중국산\":-50,\"일본산\":200,\"베트남산\":-30,\"바레인산\":100,\"수입산\":50,\"장기재고\":-100,\"중고\":-150}','{\"SS400\":0,\"SM490A\":200,\"SM490B\":50}',NULL,NULL,NULL,0,1,9,'2025-08-31 06:35:29','2025-09-18 09:29:02','linear','{\"600*300*12*20\":{\"SS400\":137,\"SM490A\":137,\"SM490B\":137}}','[\"SS400\",\"SM490A\",\"SM490B\"]','[\"600*300*12*20\"]',1,'single',149,'600*300*12*20',137.000,NULL,NULL,NULL,0,0),
(163,'h-beam','H형강',NULL,'1','',1000.00,900.00,1100.00,'',1,'in_stock',NULL,NULL,'','','','SS400','','국산','[\"국산\",\"일본산\",\"베트남산\",\"바레이산\",\"수입산\",\"쟁가재고\",\"중고\"]','{\"국산\":\"1000\",\"일본산\":\"200\",\"베트남산\":\"-30\",\"바레이산\":\"0\",\"수입산\":\"50\",\"쟁가재고\":\"0\",\"중고\":\"-150\"}','{\"SS400\":\"0\",\"SM490A\":\"150\",\"SM490B\":\"50\",\"SM570\":\"0\",\"SUS316\":\"0\",\"S45C\":\"0\",\"SPHC\":\"0\"}','',NULL,NULL,0,1,15,'2025-08-31 06:35:29','2025-09-18 09:35:27','linear','{\"700*300*13*24\":{\"SS400\":173,\"SM490A\":173,\"SM490B\":173}}','[\"SS400\",\"SM490A\",\"SM490B\",\"SM570\",\"SUS316\",\"S45C\",\"SPHC\"]','[\"700*300*13*24\"]',1,'single',149,'700*300*13*24',173.000,NULL,NULL,NULL,0,0),
(164,'h-beam','H형강',NULL,'800*300*14*26','',1000.00,900.00,1100.00,'',1,'out_of_stock','/uploads/products/164_1757312282.jpg',NULL,'','','','SS400','','국산','[\"국산\",\"중국산\",\"일본산\",\"베트남산\",\"바레이산\",\"수입산\",\"쟁가재고\",\"중고\"]','{\"국산\":\"0\",\"중국산\":\"-50\",\"일본산\":\"200\",\"베트남산\":\"-30\",\"바레이산\":\"0\",\"수입산\":\"50\",\"쟁가재고\":\"0\",\"중고\":\"-150\"}','{\"SS400\":\"0\",\"SM490A\":\"50\",\"SM490B\":\"100\"}','',NULL,NULL,0,1,41,'2025-08-31 06:35:29','2025-09-18 11:25:36','linear','{\"800*300*14*26\":{\"SS400\":202,\"SM490A\":202,\"SM490B\":202}}','[\"SS400\",\"SM490A\",\"SM490B\"]','[\"800*300*14*26\"]',1,'single',149,'800*300*14*26',202.000,NULL,NULL,NULL,0,0),
(165,'h-beam','H형강',NULL,'200','',1000.00,900.00,1100.00,'',1,'in_stock',NULL,NULL,'','','','SM490A','','바레이산','[\"바레이산\",\"수입산\",\"쟁가재고\",\"중고\"]','{\"바레이산\":\"0\",\"수입산\":\"50\",\"쟁가재고\":\"0\",\"중고\":\"-150\"}','{\"SM490A\":\"1000\",\"SM490B\":\"2000\"}','',NULL,NULL,0,1,90,'2025-08-31 06:35:29','2025-09-18 10:45:21','linear','{\"900*300*16*28\":{\"SS400\":243,\"SM490A\":243,\"SM490B\":243}}','[\"SM490A\",\"SM490B\"]','[\"900*300*16*28\"]',1,'single',149,'900*300*16*28',243.000,NULL,NULL,NULL,0,0),
(166,'i-beam','I형강',NULL,NULL,'',1000.00,900.00,1100.00,NULL,1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[\"국산\",\"중국산\",\"일본산\",\"베트남산\",\"바레이산\",\"수입산\",\"쟁가재고\",\"중고\"]','{\"국산\":0,\"중국산\":-50,\"일본산\":200,\"베트남산\":-30,\"바레인산\":100,\"수입산\":50,\"장기재고\":-100,\"중고\":-150}','{\"SS400\":0}',NULL,NULL,NULL,0,1,12,'2025-08-31 06:35:29','2025-09-17 03:06:01','linear','{\"100*75*5*8\":{\"SS400\":13.9},\"125*75*5.5*9.5\":{\"SS400\":16.1},\"150*75*5.5*9.5\":{\"SS400\":17.1},\"150*125*8.5*14\":{\"SS400\":36.2},\"180*100*6*10\":{\"SS400\":23.6},\"200*100*70*10\":{\"SS400\":26},\"200*150*9*16\":{\"SS400\":50.4},\"250*125*7.5*12.5\":{\"SS400\":38.3},\"250*125*10*19\":{\"SS400\":55.5},\"300*150*8*13\":{\"SS400\":48.3},\"300*150*10.5*16.5\":{\"SS400\":62.5},\"350*175*10*16\":{\"SS400\":75},\"400*200*10*16\":{\"SS400\":83.3},\"450*200*11*18\":{\"SS400\":101},\"500*200*11.5*19\":{\"SS400\":113},\"600*220*12*20\":{\"SS400\":133},\"700*250*13*24\":{\"SS400\":183},\"800*280*14*26\":{\"SS400\":219},\"900*300*16*28\":{\"SS400\":258}}','[\"SS400\"]','[\"100*75*5*8\",\"125*75*5.5*9.5\",\"150*75*5.5*9.5\",\"150*125*8.5*14\",\"180*100*6*10\",\"200*100*70*10\",\"200*150*9*16\",\"250*125*7.5*12.5\",\"250*125*10*19\",\"300*150*8*13\",\"300*150*10.5*16.5\",\"350*175*10*16\",\"400*200*10*16\",\"450*200*11*18\",\"500*200*11.5*19\",\"600*220*12*20\",\"700*250*13*24\",\"800*280*14*26\",\"900*300*16*28\"]',1,'by_specification',NULL,NULL,NULL,NULL,NULL,NULL,0,0),
(167,'i-beam','I형강',NULL,NULL,'',1000.00,900.00,1100.00,NULL,1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[\"국산\",\"중국산\",\"일본산\",\"베트남산\",\"바레이산\",\"수입산\",\"쟁가재고\",\"중고\"]','{\"국산\":0,\"중국산\":-50,\"일본산\":200,\"베트남산\":-30,\"바레인산\":100,\"수입산\":50,\"장기재고\":-100,\"중고\":-150}','{\"SS400\":0,\"SM490A\":0,\"SM490B\":100}',NULL,NULL,NULL,0,1,1,'2025-08-31 06:35:29','2025-09-18 09:29:02','linear','{\"100*75*5*7\":{\"SS400\":10.1,\"SM490A\":10.1,\"SM490B\":10.1}}','[\"SS400\",\"SM490A\",\"SM490B\"]','[\"100*75*5*7\"]',1,'single',166,'100*75*5*7',10.100,NULL,NULL,NULL,0,0),
(168,'i-beam','I형강',NULL,NULL,'',1000.00,900.00,1100.00,NULL,1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[\"국산\",\"중국산\",\"일본산\",\"베트남산\",\"바레이산\",\"수입산\",\"쟁가재고\",\"중고\"]','{\"국산\":0,\"중국산\":-50,\"일본산\":200,\"베트남산\":-30,\"바레인산\":100,\"수입산\":50,\"장기재고\":-100,\"중고\":-150}','{\"SS400\":0,\"SM490A\":0,\"SM490B\":0}',NULL,NULL,NULL,0,1,1,'2025-08-31 06:35:29','2025-09-18 09:29:02','linear','{\"125*75*5.5*7\":{\"SS400\":11.9,\"SM490A\":11.9,\"SM490B\":11.9}}','[\"SS400\",\"SM490A\",\"SM490B\"]','[\"125*75*5.5*7\"]',1,'single',166,'125*75*5.5*7',11.900,NULL,NULL,NULL,0,0),
(169,'i-beam','I형강',NULL,NULL,'',1000.00,900.00,1100.00,NULL,1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[\"국산\",\"중국산\",\"일본산\",\"베트남산\",\"바레이산\",\"수입산\",\"쟁가재고\",\"중고\"]','{\"국산\":0,\"중국산\":-50,\"일본산\":200,\"베트남산\":-30,\"바레인산\":100,\"수입산\":50,\"장기재고\":-100,\"중고\":-150}','{\"SS400\":0,\"SM490A\":250,\"SM490B\":250}',NULL,NULL,NULL,0,1,0,'2025-08-31 06:35:29','2025-09-18 09:29:02','linear','{\"150*100*5.5*7\":{\"SS400\":15.5,\"SM490A\":15.5,\"SM490B\":15.5}}','[\"SS400\",\"SM490A\",\"SM490B\"]','[\"150*100*5.5*7\"]',1,'single',166,'150*100*5.5*7',15.500,NULL,NULL,NULL,0,0),
(170,'i-beam','I형강',NULL,NULL,'',1000.00,900.00,1100.00,NULL,1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[\"국산\",\"중국산\",\"일본산\",\"베트남산\",\"바레이산\",\"수입산\",\"쟁가재고\",\"중고\"]','{\"국산\":0,\"중국산\":-50,\"일본산\":200,\"베트남산\":-30,\"바레인산\":100,\"수입산\":50,\"장기재고\":-100,\"중고\":-150}','{\"SS400\":0,\"SM490A\":100,\"SM490B\":250}',NULL,NULL,NULL,0,1,0,'2025-08-31 06:35:29','2025-09-18 09:29:02','linear','{\"180*100*6*8\":{\"SS400\":18.9,\"SM490A\":18.9,\"SM490B\":18.9}}','[\"SS400\",\"SM490A\",\"SM490B\"]','[\"180*100*6*8\"]',1,'single',166,'180*100*6*8',18.900,NULL,NULL,NULL,0,0),
(171,'i-beam','I형강',NULL,NULL,'',1000.00,900.00,1100.00,NULL,1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[\"국산\",\"중국산\",\"일본산\",\"베트남산\",\"바레이산\",\"수입산\",\"쟁가재고\",\"중고\"]','{\"국산\":0,\"중국산\":-50,\"일본산\":200,\"베트남산\":-30,\"바레인산\":100,\"수입산\":50,\"장기재고\":-100,\"중고\":-150}','{\"SS400\":0,\"SM490A\":250,\"SM490B\":200}',NULL,NULL,NULL,0,1,0,'2025-08-31 06:35:29','2025-09-18 09:29:02','linear','{\"200*100*6.5*8.5\":{\"SS400\":21.7,\"SM490A\":21.7,\"SM490B\":21.7}}','[\"SS400\",\"SM490A\",\"SM490B\"]','[\"200*100*6.5*8.5\"]',1,'single',166,'200*100*6.5*8.5',21.700,NULL,NULL,NULL,0,0);
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

-- Dump completed on 2025-09-22  3:13:41
