-- MySQL dump 10.13  Distrib 8.4.3, for Win64 (x86_64)
--
-- Host: localhost    Database: humanresources
-- ------------------------------------------------------
-- Server version	8.4.3

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `audit_aktivitas`
--

DROP TABLE IF EXISTS `audit_aktivitas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_aktivitas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `aktivitas` varchar(255) NOT NULL,
  `dibuat_pada` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=106 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_aktivitas`
--

LOCK TABLES `audit_aktivitas` WRITE;
/*!40000 ALTER TABLE `audit_aktivitas` DISABLE KEYS */;
INSERT INTO `audit_aktivitas` VALUES (1,1,'Mengubah pengaturan tampilan profil.','2026-08-05 06:26:12'),(2,1,'Menambahkan pengguna iqbal dengan role admin.','2026-08-05 06:37:37'),(3,2,'Menghapus data karyawan ID 364.','2026-08-05 06:40:38'),(4,2,'Mengimpor 20 data karyawan dari Excel.','2026-08-05 06:43:52'),(5,1,'Mengubah tampilan halaman publik.','2026-08-05 06:47:11'),(6,1,'Mengubah tampilan halaman publik.','2026-08-05 06:47:43'),(7,1,'Mengubah tampilan halaman publik.','2026-08-05 07:13:29'),(8,1,'Mengubah pengaturan tampilan profil.','2026-08-05 08:05:09'),(9,1,'Mengubah tampilan halaman publik.','2026-08-05 08:53:55'),(10,1,'Mengubah tampilan halaman publik.','2026-08-05 08:54:14'),(11,1,'Mengedit data karyawan Nanda Safitri (EMP020).','2026-08-06 01:39:26'),(12,1,'Mengedit data karyawan Nanda Safitri (EMP020).','2026-08-06 03:51:04'),(13,1,'Mengedit data karyawan Nanda Safitri (EMP020).','2026-08-06 03:51:14'),(14,1,'Mengedit data karyawan Nanda Safitri (EMP020).','2026-08-06 03:51:26'),(15,1,'Mengedit data karyawan Nanda Safitri (EMP020).','2026-08-06 03:52:21'),(16,1,'Mengedit data karyawan Nanda Safitri (EMP020).','2026-08-06 03:52:52'),(17,1,'Mengedit data karyawan Nanda Safitri (EMP020).','2026-08-06 03:54:08'),(18,1,'Mengedit data karyawan Nanda Safitri (EMP020).','2026-08-06 04:00:11'),(19,1,'Mengedit data karyawan Nanda Safitri (EMP020).','2026-08-06 06:16:08'),(20,1,'Mengedit data karyawan Nanda Safitri (EMP020).','2026-08-06 06:20:50'),(21,1,'Mengedit data karyawan Nanda Safitri (EMP020).','2026-08-06 06:33:58'),(22,1,'Mengedit data karyawan Nanda Safitri (EMP020).','2026-08-06 06:34:40'),(23,1,'Mengedit data karyawan Nanda Safitri (EMP020).','2026-08-06 06:36:37'),(24,1,'Mengedit data karyawan Nanda Safitri (EMP020).','2026-08-06 07:02:55'),(25,1,'Mengubah tampilan halaman publik.','2026-08-06 07:43:38'),(26,1,'Mengubah tampilan halaman publik.','2026-08-06 08:04:07'),(27,1,'Mengubah tampilan halaman publik.','2026-08-06 08:04:46'),(28,1,'Mengubah tampilan halaman publik.','2026-08-06 08:06:26'),(29,1,'Mengubah tampilan halaman publik.','2026-08-06 08:08:28'),(30,1,'Mengubah tampilan halaman publik.','2026-08-06 08:09:10'),(31,1,'Mengedit data karyawan Nanda Safitri (EMP020).','2026-08-06 08:56:50'),(32,1,'Mengedit data karyawan Nanda Safitri (EMP020).','2026-08-06 09:02:22'),(33,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:06:43'),(34,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:06:50'),(35,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:07:00'),(36,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:12:06'),(37,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:12:09'),(38,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:12:15'),(39,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:12:16'),(40,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:12:17'),(41,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:12:18'),(42,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:12:20'),(43,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:14:22'),(44,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:14:24'),(45,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:14:25'),(46,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:14:25'),(47,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:14:36'),(48,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:15:51'),(49,1,'Mengedit data karyawan Nanda Safitri (EMP020).','2026-08-07 02:16:13'),(50,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:16:19'),(51,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:16:29'),(52,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:18:00'),(53,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:18:06'),(54,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:25:02'),(55,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:36:11'),(56,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:36:18'),(57,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:36:54'),(58,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:41:35'),(59,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:50:22'),(60,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:54:14'),(61,1,'Mengedit data karyawan Nanda Safitri (EMP020).','2026-08-07 03:34:09'),(62,1,'Mengedit data karyawan Nanda Safitri (EMP020).','2026-08-07 03:35:03'),(63,1,'Mengedit data karyawan Nanda Safitri (EMP020).','2026-08-07 03:54:05'),(64,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 03:56:10'),(65,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 03:57:29'),(66,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 03:58:02'),(67,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 03:58:24'),(68,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 03:59:01'),(69,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 04:04:43'),(70,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 04:16:23'),(71,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 04:16:37'),(72,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 04:23:34'),(73,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 05:57:55'),(74,1,'Mengedit data karyawan Nanda Safitri (EMP020).','2026-08-07 05:59:11'),(75,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 05:59:57'),(76,1,'Mengedit data karyawan Iqbal Maulana (EMP020).','2026-08-07 06:09:50'),(77,1,'Membuat CV PDF untuk Iqbal Maulana (EMP020).','2026-08-07 06:10:13'),(78,1,'Membuat CV PDF untuk Iqbal Maulana (EMP020).','2026-08-07 06:14:55'),(79,1,'Mengedit data karyawan Iqbal Maulana (EMP020).','2026-08-07 06:16:11'),(80,1,'Membuat CV PDF untuk Iqbal Maulana (EMP020).','2026-08-07 06:16:14'),(81,1,'Membuat CV PDF untuk Iqbal Maulana (EMP020).','2026-08-07 06:33:32'),(82,1,'Membuat CV PDF untuk Iqbal Maulana (EMP020).','2026-08-07 06:36:59'),(83,1,'Membuat CV PDF untuk Iqbal Maulana (EMP020).','2026-08-07 07:00:28'),(84,1,'Menambahkan karyawan aaa (EMP021).','2026-08-07 07:15:18'),(85,1,'Menambahkan karyawan adiguna (EMP022).','2026-08-07 07:15:44'),(86,1,'Menghapus data karyawan ID 403.','2026-08-07 07:15:50'),(87,1,'Mengedit data karyawan Iqbal Maulana (EMP020).','2026-08-07 07:31:49'),(88,1,'Membuat CV PDF untuk Iqbal Maulana (EMP020).','2026-08-07 07:31:56'),(89,2,'Membuat CV PDF untuk Iqbal Maulana (EMP020).','2026-08-07 07:33:26'),(90,2,'Mengedit data karyawan Iqbal Maulana (EMP020).','2026-08-07 07:36:02'),(91,2,'Membuat CV PDF untuk Iqbal Maulana (EMP020).','2026-08-07 07:36:08'),(92,5,'Membuat CV PDF untuk Iqbal Maulana (EMP020).','2026-08-07 07:36:28'),(93,5,'Membuat CV PDF untuk Iqbal Maulana (EMP020).','2026-08-07 07:37:51'),(94,1,'Membuat CV PDF untuk Iqbal Maulana (EMP020).','2026-08-07 07:38:24'),(95,1,'Membuat CV PDF untuk Iqbal Maulana (EMP020).','2026-08-07 07:44:52'),(96,1,'Membuat CV PDF untuk Iqbal Maulana (EMP020).','2026-08-07 07:46:26'),(97,1,'Membuat CV PDF untuk Iqbal Maulana (EMP020).','2026-08-07 07:46:41'),(98,1,'Membuat CV PDF untuk Iqbal Maulana (EMP020).','2026-08-07 07:47:01'),(99,1,'Membuat CV PDF untuk adiguna (EMP022).','2026-08-07 08:22:00'),(100,1,'Membuat CV PDF untuk Iqbal Maulana (EMP020).','2026-08-07 08:22:15'),(101,1,'Membuat CV PDF untuk Iqbal Maulana (EMP020).','2026-08-08 16:08:11'),(102,1,'Membuat CV PDF untuk Iqbal Maulana (EMP020).','2026-08-08 16:08:12'),(103,1,'Membuat CV PDF untuk Iqbal Maulana (EMP020).','2026-08-10 01:15:47'),(104,1,'Membuat CV PDF untuk Iqbal Maulana (EMP020).','2026-08-10 01:52:08'),(105,1,'Menambahkan pengguna naufal dengan role manager dan departemen ID 16.','2026-08-10 04:21:49');
/*!40000 ALTER TABLE `audit_aktivitas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jenis_komponen_gaji`
--

DROP TABLE IF EXISTS `jenis_komponen_gaji`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jenis_komponen_gaji` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `kode` varchar(50) NOT NULL,
  `nama` varchar(150) NOT NULL,
  `kategori` enum('pendapatan','potongan') NOT NULL,
  `metode` enum('nominal','persentase') NOT NULL DEFAULT 'nominal',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_jenis_komponen_gaji_kode` (`kode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jenis_komponen_gaji`
--

LOCK TABLES `jenis_komponen_gaji` WRITE;
/*!40000 ALTER TABLE `jenis_komponen_gaji` DISABLE KEYS */;
/*!40000 ALTER TABLE `jenis_komponen_gaji` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `karyawan`
--

DROP TABLE IF EXISTS `karyawan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `karyawan` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_name` varchar(150) DEFAULT NULL,
  `emp_id` varchar(30) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `department_id` int unsigned DEFAULT NULL,
  `salary` decimal(15,2) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `marital_status` varchar(50) DEFAULT NULL,
  `date_of_hire` date DEFAULT NULL,
  `employment_status` varchar(50) DEFAULT NULL,
  `performance_score` varchar(50) DEFAULT NULL,
  `foto_profil` varchar(255) DEFAULT NULL,
  `file_cv` varchar(255) DEFAULT NULL,
  `nik` varchar(50) DEFAULT NULL,
  `alamat` text,
  `tanggal_lahir` date DEFAULT NULL,
  `agama` varchar(50) DEFAULT NULL,
  `kontak` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `file_ijazah` varchar(255) DEFAULT NULL,
  `file_mcu` varchar(255) DEFAULT NULL,
  `biografi` text,
  `riwayat_pekerjaan` text,
  `tanggal_riwayat_pekerjaan` date DEFAULT NULL,
  `riwayat_pendidikan` text,
  `tanggal_riwayat_pendidikan` date DEFAULT NULL,
  `tanggal_mcu_terakhir` date DEFAULT NULL,
  `keahlian` text,
  `file_cv_generated` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_karyawan_department` (`department_id`),
  CONSTRAINT `fk_karyawan_department` FOREIGN KEY (`department_id`) REFERENCES `master_departemen` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=405 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `karyawan`
--

LOCK TABLES `karyawan` WRITE;
/*!40000 ALTER TABLE `karyawan` DISABLE KEYS */;
INSERT INTO `karyawan` VALUES (383,'Andi Pratama','EMP001','Software Developer','IT',6,8500000.00,'M','Single','2022-01-10','Aktif','88',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(384,'Siti Rahmawati','EMP002','HR Officer','Human Resources',7,6500000.00,'F','Married','2021-03-15','Aktif','92',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(385,'Budi Santoso','EMP003','Accountant','Finance',8,7000000.00,'M','Married','2020-07-01','Aktif','84',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(386,'Dewi Lestari','EMP004','Marketing Specialist','Marketing',9,6800000.00,'F','Single','2023-02-20','Aktif','90',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(387,'Rizky Maulana','EMP005','Network Administrator','IT',6,7800000.00,'M','Single','2021-11-08','Aktif','86',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(388,'Nabila Putri','EMP006','Customer Service','Operations',10,5200000.00,'F','Single','2024-01-05','Aktif','81',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(389,'Fajar Nugroho','EMP007','Sales Executive','Sales',11,6200000.00,'M','Married','2022-06-13','Aktif','89',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(390,'Maya Sari','EMP008','Data Analyst','IT',6,8200000.00,'F','Single','2023-04-17','Aktif','94',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(391,'Arif Hidayat','EMP009','Warehouse Supervisor','Operations',10,6400000.00,'M','Married','2019-09-23','Aktif','83',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(392,'Rina Kusuma','EMP010','Procurement Staff','Procurement',12,6000000.00,'F','Married','2020-12-07','Aktif','87',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(393,'Dimas Saputra','EMP011','UI/UX Designer','IT',6,7600000.00,'M','Single','2023-08-14','Aktif','91',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(394,'Ayu Wulandari','EMP012','Legal Officer','Legal',13,7900000.00,'F','Single','2021-05-24','Aktif','85',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(395,'Yoga Prasetyo','EMP013','Quality Control','Production',14,5900000.00,'M','Married','2022-10-03','Aktif','82',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(396,'Intan Permata','EMP014','Recruitment Specialist','Human Resources',7,6700000.00,'F','Single','2023-06-19','Aktif','93',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(397,'Reza Kurniawan','EMP015','Production Planner','Production',14,7100000.00,'M','Married','2020-02-11','Aktif','80',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(398,'Putri Amelia','EMP016','Business Analyst','Strategy',15,8300000.00,'F','Single','2022-09-12','Aktif','95',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(399,'Hendra Wijaya','EMP017','Maintenance Technician','Engineering',16,6100000.00,'M','Married','2019-04-29','Aktif','78',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(400,'Lina Marlina','EMP018','Office Administrator','General Affairs',17,5500000.00,'F','Married','2024-03-04','Aktif','86',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(401,'Galih Ramadhan','EMP019','Security Supervisor','Security',18,5800000.00,'M','Married','2021-08-30','Aktif','79',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(402,'Iqbal Maulana','EMP020','Content Writer','Marketing',9,6300000.00,'M','Single','2023-11-06','Aktif','90','0a36bfe49e19f02f259e3f1ebc757d86.jpg','41718561129fabf7817561d2fd3bb4c8.pdf','44334','tubanan','2026-08-06','islam','08233322','iqbal@gmail.com','f1b3955b9665414561ea2e4c066d10e9.pdf','c458dfb245699f6bc305655a5df5ffd0.pdf','Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.','it support universitas dian Nuswantoro','2026-08-06','SMA kembang','2026-08-06','2026-08-07','Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit animi id est laborum.','cv-generated-402-20260810-015159-ca734573.pdf'),(404,'adiguna','EMP022','Accountant','Engineering',16,6000000.00,'M','',NULL,'Aktif','90',NULL,NULL,'','',NULL,'','','',NULL,NULL,'','',NULL,'',NULL,NULL,'','cv-generated-404-20260807-082200-2f22ab2b.pdf');
/*!40000 ALTER TABLE `karyawan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `komponen_gaji_karyawan`
--

DROP TABLE IF EXISTS `komponen_gaji_karyawan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `komponen_gaji_karyawan` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `profil_gaji_id` int unsigned NOT NULL,
  `jenis_komponen_id` int unsigned NOT NULL,
  `nilai` decimal(15,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_komponen_gaji_karyawan` (`profil_gaji_id`,`jenis_komponen_id`),
  KEY `fk_komponen_gaji_jenis` (`jenis_komponen_id`),
  CONSTRAINT `fk_komponen_gaji_jenis` FOREIGN KEY (`jenis_komponen_id`) REFERENCES `jenis_komponen_gaji` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_komponen_gaji_profil` FOREIGN KEY (`profil_gaji_id`) REFERENCES `profil_gaji` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `komponen_gaji_karyawan`
--

LOCK TABLES `komponen_gaji_karyawan` WRITE;
/*!40000 ALTER TABLE `komponen_gaji_karyawan` DISABLE KEYS */;
/*!40000 ALTER TABLE `komponen_gaji_karyawan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `master_departemen`
--

DROP TABLE IF EXISTS `master_departemen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `master_departemen` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nama` varchar(120) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `nama` (`nama`)
) ENGINE=InnoDB AUTO_INCREMENT=39665 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `master_departemen`
--

LOCK TABLES `master_departemen` WRITE;
/*!40000 ALTER TABLE `master_departemen` DISABLE KEYS */;
INSERT INTO `master_departemen` VALUES (1,'Teknologi Informasi',1),(2,'Sumber Daya Manusia',1),(3,'Keuangan',1),(4,'Pemasaran',1),(5,'Operasional',1),(6,'IT',1),(7,'Human Resources',1),(8,'Finance',1),(9,'Marketing',1),(10,'Operations',1),(11,'Sales',1),(12,'Procurement',1),(13,'Legal',1),(14,'Production',1),(15,'Strategy',1),(16,'Engineering',1),(17,'General Affairs',1),(18,'Security',1);
/*!40000 ALTER TABLE `master_departemen` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `master_posisi`
--

DROP TABLE IF EXISTS `master_posisi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `master_posisi` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nama` varchar(120) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nama` (`nama`)
) ENGINE=InnoDB AUTO_INCREMENT=55086 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `master_posisi`
--

LOCK TABLES `master_posisi` WRITE;
/*!40000 ALTER TABLE `master_posisi` DISABLE KEYS */;
INSERT INTO `master_posisi` VALUES (8,'Accountant'),(21,'Business Analyst'),(25,'Content Writer'),(11,'Customer Service'),(13,'Data Analyst'),(3,'Finance Analyst'),(7,'HR Officer'),(2,'HR Specialist'),(17,'Legal Officer'),(22,'Maintenance Technician'),(4,'Marketing Executive'),(9,'Marketing Specialist'),(10,'Network Administrator'),(23,'Office Administrator'),(15,'Procurement Staff'),(20,'Production Planner'),(5,'Project Manager'),(18,'Quality Control'),(19,'Recruitment Specialist'),(12,'Sales Executive'),(24,'Security Supervisor'),(6,'Software Developer'),(1,'Software Engineer'),(16,'UI/UX Designer'),(14,'Warehouse Supervisor');
/*!40000 ALTER TABLE `master_posisi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `master_status_kerja`
--

DROP TABLE IF EXISTS `master_status_kerja`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `master_status_kerja` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nama` (`nama`)
) ENGINE=InnoDB AUTO_INCREMENT=8839 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `master_status_kerja`
--

LOCK TABLES `master_status_kerja` WRITE;
/*!40000 ALTER TABLE `master_status_kerja` DISABLE KEYS */;
INSERT INTO `master_status_kerja` VALUES (1,'Aktif'),(2,'Kontrak'),(3,'Nonaktif');
/*!40000 ALTER TABLE `master_status_kerja` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pengaturan_profil`
--

DROP TABLE IF EXISTS `pengaturan_profil`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pengaturan_profil` (
  `id` tinyint unsigned NOT NULL,
  `judul` varchar(150) NOT NULL DEFAULT 'Profil Internal',
  `teks_pembuka` varchar(255) NOT NULL DEFAULT '',
  `warna_awal` char(7) NOT NULL DEFAULT '#1e3a8a',
  `warna_akhir` char(7) NOT NULL DEFAULT '#2563eb',
  `tampil_foto` tinyint(1) NOT NULL DEFAULT '1',
  `tampil_status` tinyint(1) NOT NULL DEFAULT '1',
  `tampil_dokumen` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pengaturan_profil`
--

LOCK TABLES `pengaturan_profil` WRITE;
/*!40000 ALTER TABLE `pengaturan_profil` DISABLE KEYS */;
INSERT INTO `pengaturan_profil` VALUES (1,'Profil Internal','','#ffd500','#247aeb',1,1,1);
/*!40000 ALTER TABLE `pengaturan_profil` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pengaturan_publik`
--

DROP TABLE IF EXISTS `pengaturan_publik`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pengaturan_publik` (
  `id` tinyint unsigned NOT NULL,
  `nama_situs` varchar(150) NOT NULL DEFAULT 'Profil Karyawan',
  `judul_hero` varchar(255) NOT NULL DEFAULT 'Profil Pekerja Perusahaan',
  `deskripsi_hero` text NOT NULL,
  `teks_tombol` varchar(100) NOT NULL DEFAULT 'Lihat Data Karyawan',
  `warna_utama` char(7) NOT NULL DEFAULT '#2563eb',
  `warna_hero` char(7) NOT NULL DEFAULT '#0f172a',
  `warna_dashboard_awal` char(7) NOT NULL DEFAULT '#1e3a8a',
  `warna_dashboard_akhir` char(7) NOT NULL DEFAULT '#2563eb',
  `warna_pie_laki` char(7) NOT NULL DEFAULT '#2563eb',
  `warna_pie_perempuan` char(7) NOT NULL DEFAULT '#ec4899',
  `warna_bar_awal` char(7) NOT NULL DEFAULT '#2563eb',
  `warna_bar_akhir` char(7) NOT NULL DEFAULT '#93c5fd',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pengaturan_publik`
--

LOCK TABLES `pengaturan_publik` WRITE;
/*!40000 ALTER TABLE `pengaturan_publik` DISABLE KEYS */;
INSERT INTO `pengaturan_publik` VALUES (1,'Profil Karyawan','Profil Pekerja Perusahaan','Website ini menyajikan informasi profil karyawan berdasarkan dataset Human Resources.','Lihat Data Karyawan','#0055ff','#f9d801','#ffea00','#0040ff','#2563eb','#ffdd00','#2563eb','#93c5fd');
/*!40000 ALTER TABLE `pengaturan_publik` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `profil_gaji`
--

DROP TABLE IF EXISTS `profil_gaji`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `profil_gaji` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `karyawan_id` int NOT NULL,
  `gaji_pokok` decimal(15,2) NOT NULL DEFAULT '0.00',
  `uang_makan` decimal(15,2) NOT NULL DEFAULT '0.00',
  `berlaku_mulai` date NOT NULL,
  `berlaku_sampai` date DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_profil_gaji_karyawan` (`karyawan_id`),
  KEY `fk_profil_gaji_created_by` (`created_by`),
  CONSTRAINT `fk_profil_gaji_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_profil_gaji_karyawan` FOREIGN KEY (`karyawan_id`) REFERENCES `karyawan` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `profil_gaji`
--

LOCK TABLES `profil_gaji` WRITE;
/*!40000 ALTER TABLE `profil_gaji` DISABLE KEYS */;
INSERT INTO `profil_gaji` VALUES (1,383,8500000.00,0.00,'2022-01-10',NULL,NULL,'2026-08-10 04:27:35','2026-08-10 04:27:35'),(2,384,6500000.00,0.00,'2021-03-15',NULL,NULL,'2026-08-10 04:27:35','2026-08-10 04:27:35'),(3,385,7000000.00,0.00,'2020-07-01',NULL,NULL,'2026-08-10 04:27:35','2026-08-10 04:27:35'),(4,386,6800000.00,0.00,'2023-02-20',NULL,NULL,'2026-08-10 04:27:35','2026-08-10 04:27:35'),(5,387,7800000.00,0.00,'2021-11-08',NULL,NULL,'2026-08-10 04:27:35','2026-08-10 04:27:35'),(6,388,5200000.00,0.00,'2024-01-05',NULL,NULL,'2026-08-10 04:27:35','2026-08-10 04:27:35'),(7,389,6200000.00,0.00,'2022-06-13',NULL,NULL,'2026-08-10 04:27:35','2026-08-10 04:27:35'),(8,390,8200000.00,0.00,'2023-04-17',NULL,NULL,'2026-08-10 04:27:35','2026-08-10 04:27:35'),(9,391,6400000.00,0.00,'2019-09-23',NULL,NULL,'2026-08-10 04:27:35','2026-08-10 04:27:35'),(10,392,6000000.00,0.00,'2020-12-07',NULL,NULL,'2026-08-10 04:27:35','2026-08-10 04:27:35'),(11,393,7600000.00,0.00,'2023-08-14',NULL,NULL,'2026-08-10 04:27:35','2026-08-10 04:27:35'),(12,394,7900000.00,0.00,'2021-05-24',NULL,NULL,'2026-08-10 04:27:35','2026-08-10 04:27:35'),(13,395,5900000.00,0.00,'2022-10-03',NULL,NULL,'2026-08-10 04:27:35','2026-08-10 04:27:35'),(14,396,6700000.00,0.00,'2023-06-19',NULL,NULL,'2026-08-10 04:27:35','2026-08-10 04:27:35'),(15,397,7100000.00,0.00,'2020-02-11',NULL,NULL,'2026-08-10 04:27:35','2026-08-10 04:27:35'),(16,398,8300000.00,0.00,'2022-09-12',NULL,NULL,'2026-08-10 04:27:35','2026-08-10 04:27:35'),(17,399,6100000.00,0.00,'2019-04-29',NULL,NULL,'2026-08-10 04:27:35','2026-08-10 04:27:35'),(18,400,5500000.00,0.00,'2024-03-04',NULL,NULL,'2026-08-10 04:27:35','2026-08-10 04:27:35'),(19,401,5800000.00,0.00,'2021-08-30',NULL,NULL,'2026-08-10 04:27:35','2026-08-10 04:27:35'),(20,402,6300000.00,0.00,'2023-11-06',NULL,NULL,'2026-08-10 04:27:35','2026-08-10 04:27:35'),(21,404,6000000.00,0.00,'2026-08-10',NULL,NULL,'2026-08-10 04:27:35','2026-08-10 04:27:35');
/*!40000 ALTER TABLE `profil_gaji` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `riwayat_pekerjaan`
--

DROP TABLE IF EXISTS `riwayat_pekerjaan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `riwayat_pekerjaan` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `karyawan_id` int NOT NULL,
  `nama_perusahaan` varchar(200) NOT NULL,
  `posisi` varchar(150) DEFAULT NULL,
  `departemen` varchar(150) DEFAULT NULL,
  `tanggal_mulai` date DEFAULT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `deskripsi` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_riwayat_pekerjaan_karyawan` (`karyawan_id`),
  CONSTRAINT `fk_riwayat_pekerjaan_karyawan` FOREIGN KEY (`karyawan_id`) REFERENCES `karyawan` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `riwayat_pekerjaan`
--

LOCK TABLES `riwayat_pekerjaan` WRITE;
/*!40000 ALTER TABLE `riwayat_pekerjaan` DISABLE KEYS */;
INSERT INTO `riwayat_pekerjaan` VALUES (1,402,'it support universitas dian Nuswantoro',NULL,NULL,NULL,'2026-08-06',NULL,'2026-08-10 04:08:56','2026-08-10 04:08:56');
/*!40000 ALTER TABLE `riwayat_pekerjaan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `riwayat_pendidikan`
--

DROP TABLE IF EXISTS `riwayat_pendidikan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `riwayat_pendidikan` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `karyawan_id` int NOT NULL,
  `institusi` varchar(200) NOT NULL,
  `jenjang` varchar(100) DEFAULT NULL,
  `jurusan` varchar(150) DEFAULT NULL,
  `tanggal_mulai` date DEFAULT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `keterangan` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_riwayat_pendidikan_karyawan` (`karyawan_id`),
  CONSTRAINT `fk_riwayat_pendidikan_karyawan` FOREIGN KEY (`karyawan_id`) REFERENCES `karyawan` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `riwayat_pendidikan`
--

LOCK TABLES `riwayat_pendidikan` WRITE;
/*!40000 ALTER TABLE `riwayat_pendidikan` DISABLE KEYS */;
INSERT INTO `riwayat_pendidikan` VALUES (1,402,'SMA kembang',NULL,NULL,NULL,'2026-08-06',NULL,'2026-08-10 04:08:56','2026-08-10 04:08:56');
/*!40000 ALTER TABLE `riwayat_pendidikan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `schema_migrations`
--

DROP TABLE IF EXISTS `schema_migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `schema_migrations` (
  `version` varchar(20) NOT NULL,
  `name` varchar(150) NOT NULL,
  `applied_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `schema_migrations`
--

LOCK TABLES `schema_migrations` WRITE;
/*!40000 ALTER TABLE `schema_migrations` DISABLE KEYS */;
INSERT INTO `schema_migrations` VALUES ('001','create_schema_migrations','2026-08-10 04:04:25'),('002','normalize_departments_and_histories','2026-08-10 04:08:56'),('003','extend_user_roles','2026-08-10 04:10:27'),('004','create_payroll_tables','2026-08-10 04:27:35');
/*!40000 ALTER TABLE `schema_migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama` varchar(150) NOT NULL,
  `role` enum('superadmin','admin','pic','koordinator','manager','viewer') NOT NULL DEFAULT 'viewer',
  `department_id` int unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `fk_users_department` (`department_id`),
  CONSTRAINT `fk_users_department` FOREIGN KEY (`department_id`) REFERENCES `master_departemen` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'superadmin','$2y$12$hhiH9rU56.3doruUGL2o8.buWbk2KybIKc0XoJFRBESGlp1AjzI8W','Super Admin','superadmin',NULL,1,'2026-08-04 04:35:57'),(2,'admin','$2y$12$OItz5OFPa9QILceCtTXC/OOk6h6.5/lxi6ONAJFJXVvKQR1epMtB.','Administrator','admin',NULL,1,'2026-08-04 04:35:57'),(5,'iqbal','$2y$10$M2ADHxjFlwP6B3aD0rmd9uTcPBtdo3xUq2MsnURtCTSksqrji2iVC','iqbal','admin',NULL,1,'2026-08-05 06:37:37'),(6,'naufal','$2y$10$V/vIesPgiyt5Ts3WdRP/RuqlytlNVDU8Cjww1jGvfb70BzpXtm7NC','Naufal','manager',16,1,'2026-08-10 04:21:49');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'humanresources'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-10 11:35:35
