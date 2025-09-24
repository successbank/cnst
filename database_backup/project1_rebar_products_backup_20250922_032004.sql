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
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_code` (`product_code`),
  KEY `category_code` (`category_code`),
  KEY `idx_parent_product` (`parent_product_id`),
  KEY `idx_specification` (`specification`),
  CONSTRAINT `fk_parent_product` FOREIGN KEY (`parent_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_code`) REFERENCES `product_categories` (`category_code`)
) ENGINE=InnoDB AUTO_INCREMENT=172 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--
-- WHERE:  category_code = 'rebar'

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES
(1,'rebar','철근(특판) D10',NULL,'D10 × 8m','콘크리트 구조물의 인장 보강재로 사용되는 고강도 이형철근입니다.',NULL,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,96,'2025-08-05 02:38:24','2025-09-18 12:41:22','linear','{\n  \"D10\": {\"SD400\": 0.560, \"SD500\": 0.560},\n  \"D13\": {\"SD400\": 0.995, \"SD500\": 0.995},\n  \"D16\": {\"SD400\": 1.560, \"SD500\": 1.560},\n  \"D19\": {\"SD400\": 2.250, \"SD500\": 2.250},\n  \"D22\": {\"SD400\": 3.040, \"SD500\": 3.040},\n  \"D25\": {\"SD400\": 3.980, \"SD500\": 3.980},\n  \"D29\": {\"SD400\": 5.040, \"SD500\": 5.040},\n  \"D32\": {\"SD400\": 6.230, \"SD500\": 6.230}\n}','[\"SD400\", \"SD500\"]','[\"D10\", \"D13\", \"D16\", \"D19\", \"D22\", \"D25\", \"D29\", \"D32\"]',1,'single',NULL,'D10',0.560,NULL,NULL,NULL,1,1),
(2,'rebar','철근(특판) D13',NULL,'D13 × 8m','콘크리트 구조물의 인장 보강재로 사용되는 고강도 이형철근입니다.',NULL,'TON',1,'in_stock',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,85,'2025-08-05 02:38:24','2025-09-18 12:16:10','linear','{\"D13\": {\"SD400\": 0.995, \"SD500\": 0.995}}','[\"SD400\", \"SD500\"]','[\"D13\"]',1,'single',NULL,'D13',0.995,NULL,NULL,NULL,0,0),
(6,'rebar','철근 D10 SD400 6m (테스트)',NULL,'D10 (10mm, 0.56kg/m)','테스트 철근 제품',300000.00,NULL,1,'in_stock',NULL,NULL,NULL,NULL,NULL,'SD400',6.0,NULL,'국산',NULL,0,1,9,'2025-09-18 11:44:02','2025-09-22 01:58:25','linear','{\"D10\": {\"SD400\": 0.560}}','[\"SD400\"]','[\"D10\"]',1,'single',NULL,'D10',0.560,NULL,NULL,NULL,0,0),
(145,'rebar','철근 HD10',NULL,'HD10','SD400 - 직경 10mm, 단위중량 0.56kg/m. 콘크리트 구조물의 인장강도 보강용 철근입니다.',NULL,'TON',1,'in_stock',NULL,NULL,NULL,'{\"diameter\":10}',NULL,NULL,NULL,NULL,NULL,NULL,0,1,1,'2025-09-22 02:33:35','2025-09-22 02:57:24','linear','{\"HD10\":{\"SD400\":0.56}}','[\"SD400\"]',NULL,1,'single',NULL,'HD10',0.560,NULL,NULL,NULL,0,0),
(146,'rebar','철근 HD13',NULL,'HD13','SD400 - 직경 13mm, 단위중량 1.00kg/m. 콘크리트 구조물의 인장강도 보강용 철근입니다.',NULL,'TON',1,'in_stock',NULL,NULL,NULL,'{\"diameter\":13}',NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 02:33:35','2025-09-22 02:33:35','linear','{\"HD13\":{\"SD400\":1}}','[\"SD400\"]',NULL,1,'single',NULL,'HD13',1.000,NULL,NULL,NULL,0,0),
(147,'rebar','철근 HD16',NULL,'HD16','SD400 - 직경 16mm, 단위중량 1.56kg/m. 콘크리트 구조물의 인장강도 보강용 철근입니다.',NULL,'TON',1,'in_stock',NULL,NULL,NULL,'{\"diameter\":16}',NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 02:33:35','2025-09-22 02:33:35','linear','{\"HD16\":{\"SD400\":1.56}}','[\"SD400\"]',NULL,1,'single',NULL,'HD16',1.560,NULL,NULL,NULL,0,0),
(148,'rebar','철근 HD19',NULL,'HD19','SD400 - 직경 19mm, 단위중량 2.25kg/m. 콘크리트 구조물의 인장강도 보강용 철근입니다.',NULL,'TON',1,'in_stock',NULL,NULL,NULL,'{\"diameter\":19}',NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 02:33:35','2025-09-22 02:33:35','linear','{\"HD19\":{\"SD400\":2.25}}','[\"SD400\"]',NULL,1,'single',NULL,'HD19',2.250,NULL,NULL,NULL,0,0),
(149,'rebar','철근 HD22',NULL,'HD22','SD400 - 직경 22mm, 단위중량 3.04kg/m. 콘크리트 구조물의 인장강도 보강용 철근입니다.',NULL,'TON',1,'in_stock',NULL,NULL,NULL,'{\"diameter\":22}',NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 02:33:35','2025-09-22 02:33:35','linear','{\"HD22\":{\"SD400\":3.04}}','[\"SD400\"]',NULL,1,'single',NULL,'HD22',3.040,NULL,NULL,NULL,0,0),
(150,'rebar','철근 HD25',NULL,'HD25','SD400 - 직경 25mm, 단위중량 3.98kg/m. 콘크리트 구조물의 인장강도 보강용 철근입니다.',NULL,'TON',1,'in_stock',NULL,NULL,NULL,'{\"diameter\":25}',NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 02:33:35','2025-09-22 02:33:35','linear','{\"HD25\":{\"SD400\":3.98}}','[\"SD400\"]',NULL,1,'single',NULL,'HD25',3.980,NULL,NULL,NULL,0,0),
(151,'rebar','철근 HD29',NULL,'HD29','SD400 - 직경 29mm, 단위중량 5.04kg/m. 콘크리트 구조물의 인장강도 보강용 철근입니다.',NULL,'TON',1,'in_stock',NULL,NULL,NULL,'{\"diameter\":29}',NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 02:33:35','2025-09-22 02:33:35','linear','{\"HD29\":{\"SD400\":5.04}}','[\"SD400\"]',NULL,1,'single',NULL,'HD29',5.040,NULL,NULL,NULL,0,0),
(152,'rebar','철근 HD32',NULL,'HD32','SD400 - 직경 32mm, 단위중량 6.23kg/m. 콘크리트 구조물의 인장강도 보강용 철근입니다.',NULL,'TON',1,'in_stock',NULL,NULL,NULL,'{\"diameter\":32}',NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 02:33:35','2025-09-22 02:33:35','linear','{\"HD32\":{\"SD400\":6.23}}','[\"SD400\"]',NULL,1,'single',NULL,'HD32',6.230,NULL,NULL,NULL,0,0),
(153,'rebar','철근 HD35',NULL,'HD35','SD400 - 직경 35mm, 단위중량 7.51kg/m. 콘크리트 구조물의 인장강도 보강용 철근입니다.',NULL,'TON',1,'in_stock',NULL,NULL,NULL,'{\"diameter\":35}',NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 02:33:35','2025-09-22 02:33:35','linear','{\"HD35\":{\"SD400\":7.51}}','[\"SD400\"]',NULL,1,'single',NULL,'HD35',7.510,NULL,NULL,NULL,0,0),
(154,'rebar','철근 HD38',NULL,'HD38','SD400 - 직경 38mm, 단위중량 8.95kg/m. 콘크리트 구조물의 인장강도 보강용 철근입니다.',NULL,'TON',1,'in_stock',NULL,NULL,NULL,'{\"diameter\":38}',NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 02:33:35','2025-09-22 02:33:35','linear','{\"HD38\":{\"SD400\":8.95}}','[\"SD400\"]',NULL,1,'single',NULL,'HD38',8.950,NULL,NULL,NULL,0,0),
(155,'rebar','철근 HD41',NULL,'HD41','SD400 - 직경 41mm, 단위중량 10.50kg/m. 콘크리트 구조물의 인장강도 보강용 철근입니다.',NULL,'TON',1,'in_stock',NULL,NULL,NULL,'{\"diameter\":41}',NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 02:33:35','2025-09-22 02:33:35','linear','{\"HD41\":{\"SD400\":10.5}}','[\"SD400\"]',NULL,1,'single',NULL,'HD41',10.500,NULL,NULL,NULL,0,0),
(156,'rebar','철근 HD51',NULL,'HD51','SD400 - 직경 51mm, 단위중량 15.90kg/m. 콘크리트 구조물의 인장강도 보강용 철근입니다.',NULL,'TON',1,'in_stock',NULL,NULL,NULL,'{\"diameter\":51}',NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 02:33:35','2025-09-22 02:33:35','linear','{\"HD51\":{\"SD400\":15.9}}','[\"SD400\"]',NULL,1,'single',NULL,'HD51',15.900,NULL,NULL,NULL,0,0),
(157,'rebar','철근 D10',NULL,'D10','SD300 - 직경 10mm, 단위중량 0.56kg/m. 콘크리트 구조물의 인장강도 보강용 철근입니다.',NULL,'TON',1,'in_stock',NULL,NULL,NULL,'{\"diameter\":10}',NULL,NULL,NULL,NULL,NULL,NULL,0,1,2,'2025-09-22 02:33:35','2025-09-22 03:19:14','linear','{\"D10\":{\"SD300\":0.56}}','[\"SD300\"]',NULL,1,'single',NULL,'D10',0.560,NULL,NULL,NULL,0,0),
(158,'rebar','철근 D13',NULL,'D13','SD300 - 직경 13mm, 단위중량 1.00kg/m. 콘크리트 구조물의 인장강도 보강용 철근입니다.',NULL,'TON',1,'in_stock',NULL,NULL,NULL,'{\"diameter\":13}',NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 02:33:35','2025-09-22 02:33:35','linear','{\"D13\":{\"SD300\":1}}','[\"SD300\"]',NULL,1,'single',NULL,'D13',1.000,NULL,NULL,NULL,0,0),
(159,'rebar','철근 D16',NULL,'D16','SD300 - 직경 16mm, 단위중량 1.56kg/m. 콘크리트 구조물의 인장강도 보강용 철근입니다.',NULL,'TON',1,'in_stock',NULL,NULL,NULL,'{\"diameter\":16}',NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 02:33:35','2025-09-22 02:33:35','linear','{\"D16\":{\"SD300\":1.56}}','[\"SD300\"]',NULL,1,'single',NULL,'D16',1.560,NULL,NULL,NULL,0,0),
(160,'rebar','철근 D19',NULL,'D19','SD300 - 직경 19mm, 단위중량 2.25kg/m. 콘크리트 구조물의 인장강도 보강용 철근입니다.',NULL,'TON',1,'in_stock',NULL,NULL,NULL,'{\"diameter\":19}',NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 02:33:35','2025-09-22 02:33:35','linear','{\"D19\":{\"SD300\":2.25}}','[\"SD300\"]',NULL,1,'single',NULL,'D19',2.250,NULL,NULL,NULL,0,0),
(161,'rebar','철근 D22',NULL,'D22','SD300 - 직경 22mm, 단위중량 3.04kg/m. 콘크리트 구조물의 인장강도 보강용 철근입니다.',NULL,'TON',1,'in_stock',NULL,NULL,NULL,'{\"diameter\":22}',NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 02:33:35','2025-09-22 02:33:35','linear','{\"D22\":{\"SD300\":3.04}}','[\"SD300\"]',NULL,1,'single',NULL,'D22',3.040,NULL,NULL,NULL,0,0),
(162,'rebar','철근 D25',NULL,'D25','SD300 - 직경 25mm, 단위중량 3.98kg/m. 콘크리트 구조물의 인장강도 보강용 철근입니다.',NULL,'TON',1,'in_stock',NULL,NULL,NULL,'{\"diameter\":25}',NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 02:33:35','2025-09-22 02:33:35','linear','{\"D25\":{\"SD300\":3.98}}','[\"SD300\"]',NULL,1,'single',NULL,'D25',3.980,NULL,NULL,NULL,0,0),
(163,'rebar','철근 UHD10',NULL,'UHD10','SD600 - 직경 10mm, 단위중량 0.56kg/m. 콘크리트 구조물의 인장강도 보강용 철근입니다.',NULL,'TON',1,'in_stock',NULL,NULL,NULL,'{\"diameter\":10}',NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 02:33:35','2025-09-22 02:33:35','linear','{\"UHD10\":{\"SD600\":0.56}}','[\"SD600\"]',NULL,1,'single',NULL,'UHD10',0.560,NULL,NULL,NULL,0,0),
(164,'rebar','철근 UHD13',NULL,'UHD13','SD600 - 직경 13mm, 단위중량 1.00kg/m. 콘크리트 구조물의 인장강도 보강용 철근입니다.',NULL,'TON',1,'in_stock',NULL,NULL,NULL,'{\"diameter\":13}',NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 02:33:35','2025-09-22 02:33:35','linear','{\"UHD13\":{\"SD600\":1}}','[\"SD600\"]',NULL,1,'single',NULL,'UHD13',1.000,NULL,NULL,NULL,0,0),
(165,'rebar','철근 UHD16',NULL,'UHD16','SD600 - 직경 16mm, 단위중량 1.56kg/m. 콘크리트 구조물의 인장강도 보강용 철근입니다.',NULL,'TON',1,'in_stock',NULL,NULL,NULL,'{\"diameter\":16}',NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 02:33:35','2025-09-22 02:33:35','linear','{\"UHD16\":{\"SD600\":1.56}}','[\"SD600\"]',NULL,1,'single',NULL,'UHD16',1.560,NULL,NULL,NULL,0,0),
(166,'rebar','철근 UHD19',NULL,'UHD19','SD600 - 직경 19mm, 단위중량 2.25kg/m. 콘크리트 구조물의 인장강도 보강용 철근입니다.',NULL,'TON',1,'in_stock',NULL,NULL,NULL,'{\"diameter\":19}',NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 02:33:35','2025-09-22 02:33:35','linear','{\"UHD19\":{\"SD600\":2.25}}','[\"SD600\"]',NULL,1,'single',NULL,'UHD19',2.250,NULL,NULL,NULL,0,0),
(167,'rebar','철근 UHD22',NULL,'UHD22','SD600 - 직경 22mm, 단위중량 3.04kg/m. 콘크리트 구조물의 인장강도 보강용 철근입니다.',NULL,'TON',1,'in_stock',NULL,NULL,NULL,'{\"diameter\":22}',NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 02:33:35','2025-09-22 02:33:35','linear','{\"UHD22\":{\"SD600\":3.04}}','[\"SD600\"]',NULL,1,'single',NULL,'UHD22',3.040,NULL,NULL,NULL,0,0),
(168,'rebar','철근 UHD25',NULL,'UHD25','SD600 - 직경 25mm, 단위중량 3.98kg/m. 콘크리트 구조물의 인장강도 보강용 철근입니다.',NULL,'TON',1,'in_stock',NULL,NULL,NULL,'{\"diameter\":25}',NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 02:33:35','2025-09-22 02:33:35','linear','{\"UHD25\":{\"SD600\":3.98}}','[\"SD600\"]',NULL,1,'single',NULL,'UHD25',3.980,NULL,NULL,NULL,0,0),
(169,'rebar','철근 SHD10',NULL,'SHD10','SD500 - 직경 10mm, 단위중량 0.56kg/m. 콘크리트 구조물의 인장강도 보강용 철근입니다.',NULL,'TON',1,'in_stock',NULL,NULL,NULL,'{\"diameter\":10}',NULL,NULL,NULL,NULL,NULL,NULL,0,1,2,'2025-09-22 02:33:35','2025-09-22 03:02:52','linear','{\"SHD10\":{\"SD500\":0.56}}','[\"SD500\"]',NULL,1,'single',NULL,'SHD10',0.560,NULL,NULL,NULL,0,0),
(170,'rebar','철근 SHD13',NULL,'SHD13','SD500 - 직경 13mm, 단위중량 1.00kg/m. 콘크리트 구조물의 인장강도 보강용 철근입니다.',NULL,'TON',1,'in_stock',NULL,NULL,NULL,'{\"diameter\":13}',NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 02:33:35','2025-09-22 02:33:35','linear','{\"SHD13\":{\"SD500\":1}}','[\"SD500\"]',NULL,1,'single',NULL,'SHD13',1.000,NULL,NULL,NULL,0,0),
(171,'rebar','철근 SHD16',NULL,'SHD16','SD500 - 직경 16mm, 단위중량 1.56kg/m. 콘크리트 구조물의 인장강도 보강용 철근입니다.',NULL,'TON',1,'in_stock',NULL,NULL,NULL,'{\"diameter\":16}',NULL,NULL,NULL,NULL,NULL,NULL,0,1,0,'2025-09-22 02:33:35','2025-09-22 02:33:35','linear','{\"SHD16\":{\"SD500\":1.56}}','[\"SD500\"]',NULL,1,'single',NULL,'SHD16',1.560,NULL,NULL,NULL,0,0);
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

-- Dump completed on 2025-09-22  3:20:04
