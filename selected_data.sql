-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: duracab_laravel
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
-- Table structure for table `brands`
--

DROP TABLE IF EXISTS `brands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `brands` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_selfdrive` tinyint(4) NOT NULL DEFAULT 0,
  `is_local` tinyint(4) NOT NULL DEFAULT 0,
  `is_populer` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `brands_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=113 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `brands`
--

LOCK TABLES `brands` WRITE;
/*!40000 ALTER TABLE `brands` DISABLE KEYS */;
INSERT INTO `brands` VALUES (1,'Agra,Uttar Pradesh','pages/car-rental-agra','brands/01J4445QZF58DAK21BWB17TANT.webp',1,1,1,1,'2031-07-23 23:53:00','2025-08-07 13:53:03'),(2,'Ajmer,Rajasthan','pages/car-rental-in-ajmer','brands/01J444M6KKP80GS2P0GFKPQ0YK.webp',1,0,0,0,'2031-07-23 23:53:00','2025-08-27 14:09:59'),(3,'Aligarh,Uttar Pradesh','pages/car-rental-in-aligarh','brands/01J444MTYVPG1KD00MZCV66FTA.jpg',1,0,0,0,'2031-07-23 23:53:00','2025-08-12 13:46:52'),(4,'Almora,Uttarakhand','pages/car-rental-in-almora','brands/01J444N938A9NAAAG23S6FPBT3.jpg',1,0,0,0,'2031-07-24 04:46:00','2025-08-12 13:47:51'),(5,'Alwar,Rajasthan','pages/car-rental-in-alwar',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 13:48:18'),(6,'Ambala,Haryana','pages/car-rental-in-ambala',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 13:48:46'),(7,'Amritsar,Punjab','pages/car-rental-in-amritsar',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 13:49:15'),(8,'Auraiya,Uttar Pradesh','pages/car-rental-in-auraiya',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 13:49:57'),(9,'Ayodhya,Uttar Pradesh','pages/car-rental-in-ayodhya',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 13:50:35'),(10,'Bageshwar,Uttarakhand','pages/car-rental-in-bageshwar',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 13:51:41'),(11,'Bahraich,Uttar Pradesh','pages/car-rental-in-bahraich',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 13:52:34'),(12,'Bareilly,Uttar Pradesh','pages/car-rental-in-bareilly',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 13:53:08'),(13,'Basti,Uttar Pradesh','pages/car-rental-in-basti',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 13:53:57'),(14,'Bathinda,Punjab','pages/car-rental-in-bathinda',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 13:55:01'),(15,'Bharatpur,Rajasthan','pages/car-rental-in-bharatpur',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 13:55:36'),(16,'Bhilwara,Rajasthan','pages/car-rental-in-bhilwara',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 13:56:54'),(17,'Bhind,Madhya Pradesh','pages/car-rental-in-bhind',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 13:57:28'),(18,'Bhiwani,Haryana','pages/car-rental-in-bhiwani',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 13:58:04'),(19,'Bhopal,Madhya Pradesh','pages/car-rental-in-bhopal',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 13:58:42'),(20,'Bijnor,Uttar Pradesh','pages/car-rental-bijnor',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 13:59:27'),(21,'Bikaner,Rajasthan','pages/car-rental-bikaner',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 14:00:55'),(22,'Bilaspur, Chhattisgarh','pages/car-rental-bilaspur',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-23 21:30:12'),(23,'Budaun,Uttar Pradesh','pages/car-rental-budaun',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 14:03:58'),(24,'Bulandshahr,Uttar Pradesh','pages/car-rental-bulandshahr',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 14:30:45'),(25,'Bundi,Rajasthan','pages/car-rental-bundi',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 14:31:29'),(26,'Chandauli,Uttar Pradesh','pages/car-rental-chandauli',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 14:32:07'),(27,'Chandigarh-International-Airport,Chandigarh','chandigarh-international-airport-chandigarh',NULL,0,0,0,0,'2016-10-24 06:57:00','2025-08-06 09:45:48'),(28,'Chandigarh,Chandigarh','pages/car-rental-in-chandigarh','brands/01JBBFAVAWG37ZC7024CFXF4W3.webp',1,0,0,1,'2016-10-24 06:57:00','2024-11-11 05:12:34'),(29,'Chitrakoot,Uttar Pradesh','pages/car-rental-chitrakoot',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 14:35:51'),(30,'Chittorgarh,Rajasthan','pages/car-rental-chittorgarh',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 14:36:31'),(31,'Churu,Rajasthan','pages/car-rental-churu',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 14:37:19'),(32,'Dausa,Rajasthan','pages/car-rental-dausa',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 14:37:58'),(33,'Dehradun,Uttarakhand','pages/car-rental-dehradun',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 14:38:30'),(34,'Deoria,Uttar Pradesh','pages/car-rental-deoria',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 14:39:06'),(35,'Dewas,Madhya Pradesh','pages/car-rental-dewas',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 14:39:42'),(36,'Dharamshala,Himachal Pradesh','pages/car-rental-in-dharmshala',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 14:40:33'),(37,'Dholpur,Rajasthan','pages/car-rental-dholpur',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 14:41:28'),(38,'Etah,Uttar Pradesh','pages/car-rental-etah',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 14:42:00'),(39,'Etawah,Uttar Pradesh','pages/car-rental-etawah',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 14:42:36'),(40,'Faridabad,Haryana','pages/car-rental-faridabad',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 14:43:13'),(41,'Faridkot,Punjab','pages/car-rental-faridkot',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 14:44:19'),(42,'Farrukhabad,Uttar Pradesh','pages/car-rental-farrukhabad',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 14:45:07'),(43,'Fatehabad,Haryana','pages/car-rental-fatehabad',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 14:45:55'),(44,'Fatehgarh Sahib,Punjab','pages/car-rental-fatehgarh-sahib',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 14:47:02'),(45,'Fatehpur,Uttar Pradesh','pages/car-rental-fatehpur',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 14:47:56'),(46,'Firozabad,Uttar Pradesh','pages/car-rental-firozabad',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 14:48:33'),(47,'Ghaziabad,Uttar Pradesh','pages/car-rental-ghaziabad',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 14:49:40'),(48,'Ghazipur,Uttar Pradesh','pages/car-rental-ghazipur',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 14:50:16'),(49,'Gorakhpur,Uttar Pradesh','pages/car-rental-gorakhpur',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 14:50:56'),(50,'Gurugram,Haryana','pages/car-rental-gurugram',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 14:51:35'),(51,'Gwalior,Madhya Pradesh','pages/car-rental-gwalior',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 14:52:13'),(52,'Haldwani,Uttarakhand','pages/car-rental-haldwani',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 14:52:54'),(53,'Hanumangarh,Rajasthan','pages/car-rental-hanumangarh',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 14:53:58'),(54,'Hapur,Uttar Pradesh','pages/car-rental-hapur',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 14:54:38'),(55,'Haridwar,Uttarakhand','pages/car-rental-in-haridwar','brands/01JBBF7ANM6SSPV0J0B2QQT1A0.webp',1,0,0,1,'2016-10-24 06:57:00','2024-11-11 05:13:47'),(56,'Hathras,Uttar Pradesh','pages/car-rental-hathras',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 14:55:15'),(57,'IGI(T1-Airport),New Delhi,Delhi','igi-t1-airport-new-delhi-delhi',NULL,0,0,0,0,'2016-10-24 06:57:00','2025-08-06 15:55:51'),(58,'IGI(T2-Airport),New Delhi,Delhi','igi-t2-airport-new-delhi-delhi',NULL,0,0,0,0,'2016-10-24 06:57:00','2025-08-06 09:45:22'),(59,'IGI(T3-Airport),New Delhi,Delhi','igi-t3-airport-new-delhi-delhi',NULL,0,0,0,0,'2016-10-24 06:57:00','2025-08-06 09:45:06'),(60,'Indore,Madhya Pradesh','pages/car-rental-indore',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 14:56:02'),(61,'Jabalpur,Madhya Pradesh','pages/car-rental-jabalpur',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 14:56:43'),(62,'Jaipur-International-Airport,Jaipur Rajasthan','jaipur-international-airport-jaipur-rajasthan',NULL,0,0,0,0,'2016-10-24 06:57:00','2025-08-06 09:44:57'),(63,'Jaipur,Rajasthan','pages/car-rental-in-jaipur','brands/01JBBF4PAVT2KH7JSAC2A0TW4B.webp',1,0,0,1,'2016-10-24 06:57:00','2024-11-11 05:14:41'),(64,'Jaisalmer,Rajasthan','pages/car-rental-jaisalmer',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 14:57:32'),(65,'Jalandhar,Punjab','pages/car-rental-jalandhar',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 14:58:24'),(66,'Jhansi,Uttar Pradesh','pages/car-rental-jhansi',NULL,1,0,0,0,'2016-10-24 06:57:00','2025-08-12 14:59:02'),(67,'Jhunjhunu,Rajasthan','pages/car-rental-jhunjhunu',NULL,1,0,0,0,'2016-10-24 07:09:00','2025-08-12 14:59:52'),(68,'Jodhpur,Rajasthan','pages/car-rental-jodhpur',NULL,1,0,0,0,'2016-10-24 07:09:00','2025-08-12 15:01:04'),(69,'Kanpur Dehat,Uttar Pradesh','kanpur-dehat-uttar-pradesh',NULL,0,0,0,0,'2016-10-24 07:09:00','2025-08-12 15:44:10'),(70,'Kanpur Nagar,Uttar Pradesh','kanpur-nagar-uttar-pradesh',NULL,0,0,0,0,'2016-10-24 07:09:00','2025-08-12 15:48:29'),(71,'Kapurthala,Punjab','pages/car-rental-kapurthala',NULL,1,0,0,0,'2016-10-24 07:09:00','2025-08-12 15:28:11'),(72,'Karauli,Rajasthan','pages/car-rental-karauli',NULL,1,0,0,0,'2016-10-24 07:09:00','2025-08-12 15:28:50'),(73,'Karnal,Haryana','pages/car-rental-karnal',NULL,1,0,0,0,'2016-10-24 07:09:00','2025-08-12 15:29:46'),(74,'Kasganj,Uttar Pradesh','pages/car-rental-kasganj',NULL,1,0,0,0,'2016-10-24 07:09:00','2025-08-12 15:30:34'),(75,'Kota,Rajasthan','pages/car-rental-kota',NULL,1,0,0,0,'2016-10-24 07:09:00','2025-08-12 15:31:34'),(76,'Kullu,Himachal Pradesh','pages/car-rental-kullu',NULL,1,0,0,0,'2016-10-24 07:09:00','2025-08-12 15:32:14'),(77,'Lucknow,Uttar Pradesh','pages/car-rental-in-lucknow',NULL,1,0,0,0,'2016-10-24 07:09:00','2025-08-12 15:33:06'),(78,'Ludhiana,Punjab','pages/car-rental-ludhiana',NULL,1,0,0,0,'2016-10-24 07:09:00','2025-08-12 15:33:51'),(79,'Mainpuri,Uttar Pradesh','pages/car-rental-mainpuri',NULL,1,0,0,0,'2016-10-24 07:09:00','2025-08-12 15:34:31'),(80,'Manali,Himachal Pradesh','pages/car-rental-in-manali','brands/01JBBF99NNS1WGHVWPWNQSK0YB.webp',1,0,0,1,'2016-10-24 07:09:00','2024-11-11 05:15:46'),(81,'Mandi,Himachal Pradesh','pages/car-rental-mandi',NULL,1,0,0,0,'2016-10-24 07:09:00','2025-08-12 15:35:09'),(82,'Mansa,Punjab','pages/car-rental-mansa',NULL,1,0,0,0,'2016-10-24 07:09:00','2025-08-12 15:35:59'),(83,'Mathura,Uttar Pradesh','pages/self-drive-car-rental-in-mathura','brands/01JBBF3G1YH7NRWKTDSWZYS0A6.webp',1,0,0,1,'2016-10-24 07:09:00','2024-11-11 05:17:40'),(84,'Meerut,Uttar Pradesh','pages/car-rental-meerut',NULL,1,0,0,0,'2016-10-24 07:09:00','2025-08-12 15:37:03'),(85,'Moradabad,Uttar Pradesh','pages/car-rental-moradabad',NULL,1,0,0,0,'2016-10-24 07:09:00','2025-08-12 15:39:31'),(86,'Morena,Madhya Pradesh','pages/car-rental-morena',NULL,1,0,0,0,'2016-10-24 07:09:00','2025-08-12 15:40:13'),(87,'Muzaffarnagar,Uttar Pradesh','pages/car-rental-muzaffarnagar',NULL,1,0,0,0,'2016-10-24 07:09:00','2025-08-12 15:40:59'),(88,'Nainital,Uttarakhand','pages/car-rental-nainital',NULL,1,0,0,0,'2016-10-24 07:09:00','2025-08-12 15:42:06'),(89,'New Delhi,Delhi','pages/car-rental-delhi','brands/01JBBF2HG9SG2WHE4SHKS7YT3W.webp',1,0,0,1,'2016-10-24 07:09:00','2024-11-11 05:18:30'),(90,'Noida,Uttar Pradesh','pages/car-rental-noida',NULL,1,0,0,0,'2016-10-24 07:09:00','2025-08-12 15:43:39'),(91,'Palwal,Haryana','pages/car-rental-palwal',NULL,1,0,0,0,'2016-10-24 07:09:00','2025-08-12 15:42:56'),(92,'Panchkula,Haryana','pages/car-rental-panchkula',NULL,1,0,0,0,'2016-10-24 07:09:00','2025-08-12 15:45:14'),(93,'Panipat,Haryana','pages/car-rental-panipat',NULL,1,0,0,0,'2016-10-24 07:09:00','2025-08-12 15:47:18'),(94,'Pathankot,Punjab','pages/car-rental-pathankot',NULL,1,0,0,0,'2016-10-24 07:09:00','2025-08-12 15:47:55'),(95,'Patiala,Punjab','pages/car-rental-patiala',NULL,1,0,0,0,'2016-10-24 07:09:00','2025-08-12 15:49:18'),(96,'PDDU-Airport,Agra,Uttar Pradesh','pddu-airport-agra-uttar-pradesh',NULL,0,0,0,0,'2016-10-24 07:09:00','2025-08-05 20:51:32'),(97,'Prayagraj,Uttar Pradesh','pages/car-rental-Prayagraj',NULL,1,0,0,0,'2016-10-24 07:09:00','2025-08-12 15:51:57'),(98,'Raebareli,Uttar Pradesh','pages/car-rental-raebareli',NULL,1,0,0,0,'2016-10-24 07:09:00','2025-08-12 15:52:39'),(99,'Rampur,Uttar Pradesh','pages/car-rental-rampur',NULL,1,0,0,0,'2016-10-24 07:09:00','2025-08-12 15:53:30'),(100,'Rishikesh uttrakhand','pages/car-rental-in-rishikesh','brands/01JBBF6FZHX121DQQ6DT3EWKX1.webp',1,0,0,1,'2016-10-24 07:09:00','2024-11-11 05:19:45'),(101,'Rudraprayag,Uttarakhand','pages/car-rental-rudraprayag',NULL,1,0,0,0,'2016-10-24 07:09:00','2025-08-12 15:54:27'),(102,'Saharanpur,Uttar?Pradesh','pages/car-rental-saharanpur',NULL,1,0,0,0,'2016-10-24 07:09:00','2025-08-12 15:58:28'),(103,'Shimla,Himachal Pradesh','pages/car-rental-in-shimla',NULL,1,0,0,0,'2016-10-24 07:09:00','2025-08-12 16:01:10'),(104,'Sikar,Rajasthan','pages/car-rental-sikar',NULL,1,0,0,0,'2016-10-24 07:09:00','2025-08-12 16:10:55'),(105,'Solan,Himachal Pradesh','pages/car-rental-solan',NULL,1,0,0,0,'2016-10-24 07:09:00','2025-08-12 16:11:53'),(106,'Sri Ganganagar,Rajasthan','pages/car-rental-sri-ganganagar',NULL,1,0,0,0,'2016-10-24 07:09:00','2025-08-12 16:12:33'),(107,'Udaipur,Rajasthan','pages/online-taxi-booking-in-udaipur','brands/01JBBF5J2DY2Q999B80VG6BZ6C.webp',1,0,0,1,'2016-10-24 07:09:00','2024-11-11 05:20:40'),(108,'Ujjain,Madhya Pradesh','pages/car-rental-ujjain',NULL,1,0,0,0,'2016-10-24 07:09:00','2025-08-12 16:13:22'),(109,'Unnao,Uttar Pradesh','pages/car-rental-unnao',NULL,1,0,0,0,'2016-10-24 07:09:00','2025-08-12 16:13:52'),(110,'Varanasi,Uttar Pradesh','pages/car-rental-varanasi',NULL,1,0,0,0,'2016-10-24 07:09:00','2025-08-12 16:14:21'),(111,'Vrindavan,Uttar Pradesh','pages/car-rental-vrindavan',NULL,1,0,0,0,'2016-10-24 07:09:00','2025-08-12 16:14:47'),(112,'0','0',NULL,1,0,0,0,'2024-11-07 03:53:47','2024-11-07 03:53:47');
/*!40000 ALTER TABLE `brands` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_media`
--

DROP TABLE IF EXISTS `app_media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_media` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `media_type` varchar(50) NOT NULL,
  `module` varchar(100) DEFAULT NULL,
  `disk` varchar(50) NOT NULL DEFAULT 'public',
  `directory` varchar(255) DEFAULT NULL,
  `original_path` varchar(255) DEFAULT NULL,
  `large_path` varchar(255) DEFAULT NULL,
  `medium_path` varchar(255) DEFAULT NULL,
  `thumbnail_path` varchar(255) DEFAULT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `original_extension` varchar(20) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `original_size` bigint(20) unsigned NOT NULL DEFAULT 0,
  `optimized_size` bigint(20) unsigned NOT NULL DEFAULT 0,
  `width` int(10) unsigned DEFAULT NULL,
  `height` int(10) unsigned DEFAULT NULL,
  `quality` tinyint(3) unsigned DEFAULT NULL,
  `file_hash` varchar(64) DEFAULT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `caption` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_public` tinyint(1) NOT NULL DEFAULT 1,
  `reference_count` int(10) unsigned NOT NULL DEFAULT 0,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `uploaded_by` bigint(20) unsigned DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `app_media_uuid_unique` (`uuid`),
  KEY `app_media_type_module_active_idx` (`media_type`,`module`,`is_active`),
  KEY `app_media_public_active_sort_idx` (`is_public`,`is_active`,`sort_order`),
  KEY `app_media_slug_index` (`slug`),
  KEY `app_media_media_type_index` (`media_type`),
  KEY `app_media_module_index` (`module`),
  KEY `app_media_file_hash_index` (`file_hash`),
  KEY `app_media_is_active_index` (`is_active`),
  KEY `app_media_is_public_index` (`is_public`),
  KEY `app_media_sort_order_index` (`sort_order`),
  KEY `app_media_uploaded_by_index` (`uploaded_by`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_media`
--

LOCK TABLES `app_media` WRITE;
/*!40000 ALTER TABLE `app_media` DISABLE KEYS */;
INSERT INTO `app_media` VALUES (1,'3a6f5c65-5a75-4407-878e-f8eeaa6cd83a','dsad','dsad','other',NULL,'public','app-media/other/2026/07/3a6f5c65-5a75-4407-878e-f8eeaa6cd83a','app-media/other/2026/07/3a6f5c65-5a75-4407-878e-f8eeaa6cd83a/original/3a6f5c65-5a75-4407-878e-f8eeaa6cd83a.png','app-media/other/2026/07/3a6f5c65-5a75-4407-878e-f8eeaa6cd83a/large/3a6f5c65-5a75-4407-878e-f8eeaa6cd83a.webp','app-media/other/2026/07/3a6f5c65-5a75-4407-878e-f8eeaa6cd83a/medium/3a6f5c65-5a75-4407-878e-f8eeaa6cd83a.webp','app-media/other/2026/07/3a6f5c65-5a75-4407-878e-f8eeaa6cd83a/thumbnail/3a6f5c65-5a75-4407-878e-f8eeaa6cd83a.webp','Red Grey Bold Modern Car Rental Instagram Post.png','png','image/png',1807469,137950,1755,1755,82,'7925dcbddfc02c88bef5569addcc877c50ac16c512195f96a54d9ff9e64701e7','dsad',NULL,1,1,0,0,2,'{\"generated_format\":\"webp\",\"large_size\":{\"width\":1400,\"height\":1000},\"medium_size\":{\"width\":900,\"height\":643},\"thumbnail_size\":{\"width\":420,\"height\":300},\"variant_paths\":[\"app-media\\/other\\/2026\\/07\\/3a6f5c65-5a75-4407-878e-f8eeaa6cd83a\\/large\\/3a6f5c65-5a75-4407-878e-f8eeaa6cd83a.webp\",\"app-media\\/other\\/2026\\/07\\/3a6f5c65-5a75-4407-878e-f8eeaa6cd83a\\/medium\\/3a6f5c65-5a75-4407-878e-f8eeaa6cd83a.webp\",\"app-media\\/other\\/2026\\/07\\/3a6f5c65-5a75-4407-878e-f8eeaa6cd83a\\/thumbnail\\/3a6f5c65-5a75-4407-878e-f8eeaa6cd83a.webp\"],\"processing_engine\":\"intervention-image\",\"processing_version\":\"4\",\"processed_at\":\"2026-07-15T00:24:33+05:30\",\"uploader_type\":\"admin\",\"upload_source\":\"admin_panel\",\"uploaded_ip\":\"127.0.0.1\",\"uploaded_user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/150.0.0.0 Safari\\/537.36\"}','2026-07-14 18:54:33','2026-07-14 18:54:33',NULL),(3,'5a438f11-1639-484d-a554-4de8e35bcb5b','hello','hello','banner','banners','public','app-media/banners/banners/2026/07/5a438f11-1639-484d-a554-4de8e35bcb5b','app-media/banners/banners/2026/07/5a438f11-1639-484d-a554-4de8e35bcb5b/original/5a438f11-1639-484d-a554-4de8e35bcb5b.png','app-media/banners/banners/2026/07/5a438f11-1639-484d-a554-4de8e35bcb5b/large/5a438f11-1639-484d-a554-4de8e35bcb5b.webp','app-media/banners/banners/2026/07/5a438f11-1639-484d-a554-4de8e35bcb5b/medium/5a438f11-1639-484d-a554-4de8e35bcb5b.webp','app-media/banners/banners/2026/07/5a438f11-1639-484d-a554-4de8e35bcb5b/thumbnail/5a438f11-1639-484d-a554-4de8e35bcb5b.webp','01JBC7FA31DR1T5AJ3HSPYE4WH.png','png','image/png',86612,58730,1011,639,82,'104b3d0c64188dc1b7f95be4d13dfd4a4fdecf502e45ead97434d07fb9b11ca1','maruti-ritz-2025',NULL,1,1,1,0,2,'{\"generated_format\":\"webp\",\"large_size\":{\"width\":1600,\"height\":650},\"medium_size\":{\"width\":1000,\"height\":406},\"thumbnail_size\":{\"width\":600,\"height\":244},\"variant_paths\":[\"app-media\\/banners\\/banners\\/2026\\/07\\/5a438f11-1639-484d-a554-4de8e35bcb5b\\/large\\/5a438f11-1639-484d-a554-4de8e35bcb5b.webp\",\"app-media\\/banners\\/banners\\/2026\\/07\\/5a438f11-1639-484d-a554-4de8e35bcb5b\\/medium\\/5a438f11-1639-484d-a554-4de8e35bcb5b.webp\",\"app-media\\/banners\\/banners\\/2026\\/07\\/5a438f11-1639-484d-a554-4de8e35bcb5b\\/thumbnail\\/5a438f11-1639-484d-a554-4de8e35bcb5b.webp\"],\"processing_engine\":\"intervention-image\",\"processing_version\":\"4\",\"processed_at\":\"2026-07-15T03:04:12+05:30\",\"uploader_type\":\"customer\",\"upload_source\":\"website\",\"module\":\"banners\",\"uploaded_ip\":\"127.0.0.1\",\"uploaded_user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/150.0.0.0 Safari\\/537.36\",\"banner_id\":19,\"banner_title\":\"hello\",\"banner_name\":\"RITZ 1699\",\"ride_type\":\"self_drive\",\"updated_from\":\"App\\\\Filament\\\\Resources\\\\BannersResource\\\\Pages\\\\EditBanners\"}','2026-07-14 21:34:12','2026-07-14 21:34:12',NULL),(5,'ee8ea425-e5ac-4ece-b6a3-4dcf8c81ede8','Mahindra Thar Roxx (UP80CT1831) Front Photo','mahindra-thar-roxx-up80ct1831-front-photo','vehicle','vehicle-front','public','app-media/vehicles/vehicle-front/2026/07/ee8ea425-e5ac-4ece-b6a3-4dcf8c81ede8','app-media/vehicles/vehicle-front/2026/07/ee8ea425-e5ac-4ece-b6a3-4dcf8c81ede8/ee8ea425-e5ac-4ece-b6a3-4dcf8c81ede8.webp',NULL,NULL,NULL,'without_driver.png','webp','image/webp',331655,43198,666,375,84,'7d12697710bf983a636fc01c498cb5574453b38f20e887e2bba1f50091491406','Mahindra Thar Roxx (UP80CT1831) Front Photo',NULL,1,1,0,0,2,'{\"storage_mode\":\"single_file\",\"generated_format\":\"webp\",\"uploaded_extension\":\"png\",\"uploaded_mime_type\":\"image\\/png\",\"uploaded_size\":331655,\"source_width\":666,\"source_height\":375,\"stored_width\":666,\"stored_height\":375,\"stored_size\":43198,\"original_preserved\":false,\"variants_generated\":false,\"uploader_type\":\"admin\",\"upload_source\":\"admin_panel\",\"module\":\"vehicle-front\",\"uploaded_ip\":\"127.0.0.1\",\"uploaded_user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/150.0.0.0 Safari\\/537.36\",\"resource\":\"App\\\\Filament\\\\Resources\\\\VehicleResource\",\"vehicle_id\":6,\"vehicle_media_field\":\"front_image\",\"is_vehicle_document\":false}','2026-07-15 11:21:07','2026-07-16 02:53:41',NULL),(6,'16afe53f-f8bf-4035-8886-1cf603505867','Hyundai Aura (UP80JA8496) Front Photo','hyundai-aura-up80ja8496-front-photo','vehicle','vehicle-front','public','app-media/vehicles/vehicle-front/2026/07/16afe53f-f8bf-4035-8886-1cf603505867','app-media/vehicles/vehicle-front/2026/07/16afe53f-f8bf-4035-8886-1cf603505867/16afe53f-f8bf-4035-8886-1cf603505867.webp',NULL,NULL,NULL,'01KXC3BE1HP17QFEES9713SYV0.webp','webp','image/webp',155490,45212,1400,927,84,'212ee54f3e8ea8ec32eb1c2ef1ad78d2e168257a9642a04cefa000316ef173f1','Hyundai Aura (UP80JA8496) Front Photo',NULL,1,1,1,0,2,'{\"storage_mode\":\"single_file\",\"generated_format\":\"webp\",\"uploaded_extension\":\"webp\",\"uploaded_mime_type\":\"image\\/webp\",\"uploaded_size\":155490,\"source_width\":3750,\"source_height\":2484,\"stored_width\":1400,\"stored_height\":927,\"stored_size\":45212,\"original_preserved\":false,\"variants_generated\":false,\"uploader_type\":\"admin\",\"upload_source\":\"admin_panel\",\"module\":\"vehicle-front\",\"uploaded_ip\":\"127.0.0.1\",\"uploaded_user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/150.0.0.0 Safari\\/537.36\",\"resource\":\"App\\\\Filament\\\\Resources\\\\VehicleResource\",\"vehicle_id\":7,\"vehicle_media_field\":\"front_image\",\"is_vehicle_document\":false}','2026-07-15 13:31:33','2026-07-15 13:31:33',NULL),(7,'fb92e230-6083-40d9-a9f8-182c05428ea2','Maruti Suzuki Ertiga (UP80HS2031) Front Photo','maruti-suzuki-ertiga-up80hs2031-front-photo','vehicle','vehicle-front','public','app-media/vehicles/vehicle-front/2026/07/fb92e230-6083-40d9-a9f8-182c05428ea2','app-media/vehicles/vehicle-front/2026/07/fb92e230-6083-40d9-a9f8-182c05428ea2/fb92e230-6083-40d9-a9f8-182c05428ea2.webp',NULL,NULL,NULL,'01KXC3JYMEEXNBYVCWKMD7ZNMZ.png','webp','image/webp',482386,35740,1048,582,84,'7d95aa0288211d435d8dc524f72248c7057089f1b55b9700e1ee6415eb7056ad','Maruti Suzuki Ertiga (UP80HS2031) Front Photo',NULL,1,1,1,0,2,'{\"storage_mode\":\"single_file\",\"generated_format\":\"webp\",\"uploaded_extension\":\"png\",\"uploaded_mime_type\":\"image\\/png\",\"uploaded_size\":482386,\"source_width\":1048,\"source_height\":582,\"stored_width\":1048,\"stored_height\":582,\"stored_size\":35740,\"original_preserved\":false,\"variants_generated\":false,\"uploader_type\":\"admin\",\"upload_source\":\"admin_panel\",\"module\":\"vehicle-front\",\"uploaded_ip\":\"127.0.0.1\",\"uploaded_user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/150.0.0.0 Safari\\/537.36\",\"resource\":\"App\\\\Filament\\\\Resources\\\\VehicleResource\",\"vehicle_id\":8,\"vehicle_media_field\":\"front_image\",\"is_vehicle_document\":false}','2026-07-15 13:32:13','2026-07-15 13:32:13',NULL),(8,'34978d8a-8c40-4cc0-b04e-333c7c705c31','Mahindra Thar Roxx (UP80CT1831) Front Photo','mahindra-thar-roxx-up80ct1831-front-photo','vehicle','vehicle-front','public','app-media/vehicles/vehicle-front/2026/07/34978d8a-8c40-4cc0-b04e-333c7c705c31','app-media/vehicles/vehicle-front/2026/07/34978d8a-8c40-4cc0-b04e-333c7c705c31/34978d8a-8c40-4cc0-b04e-333c7c705c31.webp',NULL,NULL,NULL,'IMG-20250216-WA0021.jpg','webp','image/webp',299220,124964,591,1050,84,'066a507cf8cbc7a37da9e62466e235d2d8fe81c68f3456731806ee2c1d6ca342','Mahindra Thar Roxx (UP80CT1831) Front Photo',NULL,1,1,1,0,2,'{\"storage_mode\":\"single_file\",\"generated_format\":\"webp\",\"uploaded_extension\":\"jpg\",\"uploaded_mime_type\":\"image\\/jpeg\",\"uploaded_size\":299220,\"source_width\":900,\"source_height\":1600,\"stored_width\":591,\"stored_height\":1050,\"stored_size\":124964,\"original_preserved\":false,\"variants_generated\":false,\"uploader_type\":\"admin\",\"upload_source\":\"admin_panel\",\"module\":\"vehicle-front\",\"uploaded_ip\":\"127.0.0.1\",\"uploaded_user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/150.0.0.0 Safari\\/537.36\",\"resource\":\"App\\\\Filament\\\\Resources\\\\VehicleResource\",\"vehicle_id\":6,\"vehicle_media_field\":\"front_image\",\"is_vehicle_document\":false,\"replaced_media_id\":5}','2026-07-16 02:53:40','2026-07-16 02:53:41',NULL),(9,'b1390173-39ca-4ad2-be77-4c48a49a054b','Mahindra Thar Roxx (UP80CT1831) Back Photo','mahindra-thar-roxx-up80ct1831-back-photo','vehicle','vehicle-back','public','app-media/vehicles/vehicle-back/2026/07/b1390173-39ca-4ad2-be77-4c48a49a054b','app-media/vehicles/vehicle-back/2026/07/b1390173-39ca-4ad2-be77-4c48a49a054b/b1390173-39ca-4ad2-be77-4c48a49a054b.webp',NULL,NULL,NULL,'IMG-20250216-WA0023.jpg','webp','image/webp',331811,231708,1400,788,84,'495da1fc6d4e6a1c878cffc787d66d799406fe57c33d01c3da7171469bce1e04','Mahindra Thar Roxx (UP80CT1831) Back Photo',NULL,1,1,1,0,2,'{\"storage_mode\":\"single_file\",\"generated_format\":\"webp\",\"uploaded_extension\":\"jpg\",\"uploaded_mime_type\":\"image\\/jpeg\",\"uploaded_size\":331811,\"source_width\":1600,\"source_height\":900,\"stored_width\":1400,\"stored_height\":788,\"stored_size\":231708,\"original_preserved\":false,\"variants_generated\":false,\"uploader_type\":\"admin\",\"upload_source\":\"admin_panel\",\"module\":\"vehicle-back\",\"uploaded_ip\":\"127.0.0.1\",\"uploaded_user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/150.0.0.0 Safari\\/537.36\",\"resource\":\"App\\\\Filament\\\\Resources\\\\VehicleResource\",\"vehicle_id\":6,\"vehicle_media_field\":\"back_image\",\"is_vehicle_document\":false}','2026-07-16 02:53:40','2026-07-16 02:53:41',NULL);
/*!40000 ALTER TABLE `app_media` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vehicles`
--

DROP TABLE IF EXISTS `vehicles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vehicles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `service_type` varchar(40) DEFAULT NULL,
  `vehicle_category` varchar(50) DEFAULT NULL,
  `vehicle_body_type` varchar(40) DEFAULT NULL,
  `bike_type` varchar(40) DEFAULT NULL,
  `engine_cc` int(10) unsigned DEFAULT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `transporter_profile_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `vehicle_number` varchar(255) DEFAULT NULL,
  `chassis_number` varchar(255) DEFAULT NULL,
  `engine_number` varchar(255) DEFAULT NULL,
  `insurance_number` varchar(255) DEFAULT NULL,
  `owner_name` varchar(255) DEFAULT NULL,
  `car_company_name` varchar(255) DEFAULT NULL,
  `model_name` varchar(255) DEFAULT NULL,
  `manufacture_year` smallint(5) unsigned DEFAULT NULL,
  `fuel_type` varchar(50) DEFAULT NULL,
  `transmission` varchar(50) DEFAULT NULL,
  `seats` tinyint(3) unsigned DEFAULT NULL,
  `bags` tinyint(3) unsigned DEFAULT NULL,
  `hourly_price` decimal(10,2) DEFAULT NULL,
  `daily_price` decimal(10,2) DEFAULT NULL,
  `commission_percentage` decimal(5,2) NOT NULL DEFAULT 30.00,
  `security_deposit` decimal(10,2) NOT NULL DEFAULT 0.00,
  `minimum_booking_hours` int(10) unsigned NOT NULL DEFAULT 24,
  `car_classification` varchar(255) DEFAULT NULL,
  `car_color` varchar(255) DEFAULT NULL,
  `insurance_company_name` varchar(255) DEFAULT NULL,
  `rc_image` varchar(255) DEFAULT NULL,
  `rc_media_id` bigint(20) unsigned DEFAULT NULL,
  `insurance_image` varchar(255) DEFAULT NULL,
  `insurance_media_id` bigint(20) unsigned DEFAULT NULL,
  `polution_image` varchar(255) DEFAULT NULL,
  `pollution_media_id` bigint(20) unsigned DEFAULT NULL,
  `front_image` varchar(255) DEFAULT NULL,
  `front_media_id` bigint(20) unsigned DEFAULT NULL,
  `back_image` varchar(255) DEFAULT NULL,
  `back_media_id` bigint(20) unsigned DEFAULT NULL,
  `interior_image` varchar(255) DEFAULT NULL,
  `interior_media_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `verification_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `rejection_reason` text DEFAULT NULL,
  `weekly_discount` decimal(5,2) NOT NULL DEFAULT 20.00,
  `monthly_discount` decimal(5,2) NOT NULL DEFAULT 30.00,
  `included_km_per_day` int(10) unsigned DEFAULT NULL,
  `extra_km_charge` decimal(10,2) NOT NULL DEFAULT 0.00,
  `late_charge_per_hour` decimal(10,2) NOT NULL DEFAULT 0.00,
  `helmet_policy` varchar(40) NOT NULL DEFAULT 'one_included',
  `second_helmet_charge` decimal(10,2) NOT NULL DEFAULT 100.00,
  `fuel_policy` varchar(255) DEFAULT NULL,
  `is_live` tinyint(1) NOT NULL DEFAULT 0,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `helmet_option` varchar(50) DEFAULT NULL,
  `bike_engine_cc` int(10) unsigned DEFAULT NULL,
  `vehicle_type` varchar(30) DEFAULT 'car',
  `weekly_price` decimal(12,2) DEFAULT 0.00,
  `monthly_price` decimal(12,2) DEFAULT 0.00,
  `free_km` decimal(10,2) DEFAULT 0.00,
  `extra_km_rate` decimal(10,2) DEFAULT 0.00,
  `extra_hour_rate` decimal(10,2) DEFAULT 0.00,
  `bike_category` varchar(50) DEFAULT NULL,
  `gear_type` varchar(30) DEFAULT NULL,
  `helmet_available` tinyint(1) NOT NULL DEFAULT 0,
  `included_helmets` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `maximum_helmets` tinyint(3) unsigned NOT NULL DEFAULT 2,
  `helmet_charge` decimal(10,2) NOT NULL DEFAULT 100.00,
  `fuel_capacity` decimal(8,2) DEFAULT NULL,
  `mileage` decimal(8,2) DEFAULT NULL,
  `maximum_booking_hours` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vehicles_vehicle_number_unique` (`vehicle_number`),
  KEY `vehicles_user_id_foreign` (`user_id`),
  KEY `vehicles_product_id_index` (`product_id`),
  KEY `vehicles_transporter_profile_id_index` (`transporter_profile_id`),
  KEY `vehicles_verification_status_index` (`verification_status`),
  KEY `vehicles_front_media_id_foreign` (`front_media_id`),
  KEY `vehicles_back_media_id_foreign` (`back_media_id`),
  KEY `vehicles_interior_media_id_foreign` (`interior_media_id`),
  KEY `vehicles_rc_media_id_foreign` (`rc_media_id`),
  KEY `vehicles_insurance_media_id_foreign` (`insurance_media_id`),
  KEY `vehicles_pollution_media_id_foreign` (`pollution_media_id`),
  KEY `vehicles_service_type_index` (`service_type`),
  KEY `vehicles_is_live_index` (`is_live`),
  KEY `vehicles_is_verified_index` (`is_verified`),
  KEY `vehicles_vehicle_type_idx` (`vehicle_type`),
  KEY `vehicles_bike_category_idx` (`bike_category`),
  CONSTRAINT `vehicles_back_media_id_foreign` FOREIGN KEY (`back_media_id`) REFERENCES `app_media` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vehicles_front_media_id_foreign` FOREIGN KEY (`front_media_id`) REFERENCES `app_media` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vehicles_insurance_media_id_foreign` FOREIGN KEY (`insurance_media_id`) REFERENCES `app_media` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vehicles_interior_media_id_foreign` FOREIGN KEY (`interior_media_id`) REFERENCES `app_media` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vehicles_pollution_media_id_foreign` FOREIGN KEY (`pollution_media_id`) REFERENCES `app_media` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vehicles_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vehicles_rc_media_id_foreign` FOREIGN KEY (`rc_media_id`) REFERENCES `app_media` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vehicles_transporter_profile_id_foreign` FOREIGN KEY (`transporter_profile_id`) REFERENCES `fleet_transporter_profiles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vehicles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vehicles`
--

LOCK TABLES `vehicles` WRITE;
/*!40000 ALTER TABLE `vehicles` DISABLE KEYS */;
INSERT INTO `vehicles` VALUES (6,'self_drive',NULL,NULL,NULL,NULL,NULL,12,'2026-07-12 18:40:39','2026-08-01 07:48:24','UP80CT1831','CHASSES123','ENGG123','polcy123','Sanjay Singh','Maruti Suzuki','CIAZ',2026,'petrol','manual',5,2,200.00,4800.00,30.00,5000.00,24,'SUV','Red',NULL,'vehicles/rc/01KXBT397824QT4M75E8458CYY.jpeg',NULL,'vehicles/insurance/01KXBT397DDB0C70JGH1Q734E9.jpeg',NULL,'vehicles/puc/01KXBT397HH8VC5PW6T4N334VR.jpeg',NULL,'app-media/vehicles/vehicle-front/2026/07/34978d8a-8c40-4cc0-b04e-333c7c705c31/34978d8a-8c40-4cc0-b04e-333c7c705c31.webp',8,'app-media/vehicles/vehicle-back/2026/07/b1390173-39ca-4ad2-be77-4c48a49a054b/b1390173-39ca-4ad2-be77-4c48a49a054b.webp',9,'vehicles/photos/interior/01KXBT397YR65WSHEXE5BC44J4.png',NULL,2008,1,'approved',NULL,20.00,30.00,NULL,0.00,0.00,'one_included',100.00,NULL,1,1,NULL,NULL,'car',26880.00,100800.00,0.00,0.00,0.00,NULL,NULL,0,0,2,100.00,NULL,NULL,NULL),(7,'self_drive',NULL,NULL,NULL,NULL,NULL,12,'2026-07-12 21:22:23','2026-08-01 07:48:24','UP80JA8496',NULL,NULL,NULL,'Sukh Dev','Hyundai','Aura',2026,'cng','manual',5,2,125.00,3000.00,30.00,5000.00,12,'Sedan','Gray',NULL,'vehicles/rc/01KXC3BE1AN9YM3TC42KZFWVZK.jpeg',NULL,'vehicles/insurance/01KXC3BE1F0CHGMEQDB96WPCYG.jpeg',NULL,NULL,NULL,'app-media/vehicles/vehicle-front/2026/07/16afe53f-f8bf-4035-8886-1cf603505867/16afe53f-f8bf-4035-8886-1cf603505867.webp',6,NULL,NULL,NULL,NULL,2008,1,'approved',NULL,20.00,30.00,NULL,0.00,0.00,'one_included',100.00,NULL,1,1,NULL,NULL,'car',16800.00,63000.00,0.00,0.00,0.00,NULL,NULL,0,0,2,100.00,NULL,NULL,NULL),(8,'self_drive',NULL,NULL,NULL,NULL,NULL,12,'2026-07-12 21:26:29','2026-07-19 12:11:55','UP80HS2031',NULL,NULL,NULL,'Shezal','Maruti Suzuki','Ertiga',2026,'cng','manual',7,4,146.00,3500.00,30.00,5000.00,12,'SUV','White',NULL,'vehicles/rc/01KXC3JYM799BWQNMZMYRVKE0Q.png',NULL,'vehicles/insurance/01KXC3JYMB4X2PRVT0143FYJME.png',NULL,NULL,NULL,'app-media/vehicles/vehicle-front/2026/07/fb92e230-6083-40d9-a9f8-182c05428ea2/fb92e230-6083-40d9-a9f8-182c05428ea2.webp',7,'vehicles/photos/back/01KXC4HS104NDPBK09B3CNXMCJ.webp',NULL,NULL,NULL,2008,1,'approved',NULL,20.00,30.00,NULL,0.00,0.00,'one_included',100.00,NULL,1,1,NULL,NULL,'car',19600.00,73500.00,0.00,300.00,0.00,NULL,NULL,0,0,2,100.00,NULL,NULL,NULL);
/*!40000 ALTER TABLE `vehicles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `smart_home_blocks`
--

DROP TABLE IF EXISTS `smart_home_blocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `smart_home_blocks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `block_type` varchar(50) NOT NULL,
  `service_type` varchar(50) DEFAULT NULL,
  `from_city_id` bigint(20) unsigned DEFAULT NULL,
  `to_city_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `is_dynamic` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `priority` int(10) unsigned NOT NULL DEFAULT 1,
  `starts_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `smart_home_blocks_block_type_index` (`block_type`),
  KEY `smart_home_blocks_service_type_index` (`service_type`),
  KEY `smart_home_blocks_priority_index` (`priority`),
  KEY `smart_home_blocks_is_active_index` (`is_active`),
  KEY `smart_home_blocks_block_type_is_active_index` (`block_type`,`is_active`),
  KEY `smart_home_blocks_service_type_is_active_index` (`service_type`,`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `smart_home_blocks`
--

LOCK TABLES `smart_home_blocks` WRITE;
/*!40000 ALTER TABLE `smart_home_blocks` DISABLE KEYS */;
INSERT INTO `smart_home_blocks` VALUES (1,'hero','one_way',1,89,NULL,NULL,1,1,1,NULL,NULL,'2026-07-21 13:46:13','2026-07-22 19:59:15'),(2,'hero','one_way',1,63,NULL,NULL,1,1,1,NULL,NULL,'2026-07-22 20:11:47','2026-07-22 20:11:47'),(3,'hero','one_way',1,90,NULL,NULL,1,1,1,NULL,NULL,'2026-07-23 08:52:07','2026-07-23 08:52:07'),(4,'hero','one_way',1,55,NULL,NULL,1,1,1,NULL,NULL,'2026-07-23 08:52:42','2026-07-23 08:52:42'),(5,'hero','one_way',89,1,NULL,NULL,1,1,1,NULL,NULL,'2026-07-23 08:53:15','2026-07-23 08:53:15'),(6,'hero','one_way',89,63,NULL,NULL,1,1,1,NULL,NULL,'2026-07-23 08:53:46','2026-07-23 08:53:46'),(7,'hero','one_way',1,28,NULL,NULL,1,1,1,NULL,NULL,'2026-07-23 08:54:16','2026-07-23 08:54:16'),(8,'hero','round_trip',1,89,NULL,NULL,1,1,1,NULL,NULL,'2026-07-23 08:55:32','2026-07-23 08:55:32'),(9,'hero','one_way',1,63,NULL,NULL,1,1,1,NULL,NULL,'2026-07-23 08:56:18','2026-07-23 08:56:18'),(10,'hero','self_drive',1,NULL,NULL,NULL,1,1,10,NULL,NULL,'2026-07-23 18:51:40','2026-07-31 09:25:43');
/*!40000 ALTER TABLE `smart_home_blocks` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-01 14:07:46
