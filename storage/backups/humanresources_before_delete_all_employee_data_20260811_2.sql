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
) ENGINE=InnoDB AUTO_INCREMENT=183 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_aktivitas`
--

LOCK TABLES `audit_aktivitas` WRITE;
/*!40000 ALTER TABLE `audit_aktivitas` DISABLE KEYS */;
INSERT INTO `audit_aktivitas` VALUES (1,1,'Mengubah pengaturan tampilan profil.','2026-08-05 06:26:12'),(2,1,'Menambahkan pengguna iqbal dengan role admin.','2026-08-05 06:37:37'),(3,2,'Menghapus data karyawan ID 364.','2026-08-05 06:40:38'),(4,2,'Mengimpor 20 data karyawan dari Excel.','2026-08-05 06:43:52'),(5,1,'Mengubah tampilan halaman publik.','2026-08-05 06:47:11'),(6,1,'Mengubah tampilan halaman publik.','2026-08-05 06:47:43'),(7,1,'Mengubah tampilan halaman publik.','2026-08-05 07:13:29'),(8,1,'Mengubah pengaturan tampilan profil.','2026-08-05 08:05:09'),(9,1,'Mengubah tampilan halaman publik.','2026-08-05 08:53:55'),(10,1,'Mengubah tampilan halaman publik.','2026-08-05 08:54:14'),(11,1,'Mengedit data karyawan Nanda Safitri (EMP020).','2026-08-06 01:39:26'),(12,1,'Mengedit data karyawan Nanda Safitri (EMP020).','2026-08-06 03:51:04'),(13,1,'Mengedit data karyawan Nanda Safitri (EMP020).','2026-08-06 03:51:14'),(14,1,'Mengedit data karyawan Nanda Safitri (EMP020).','2026-08-06 03:51:26'),(15,1,'Mengedit data karyawan Nanda Safitri (EMP020).','2026-08-06 03:52:21'),(16,1,'Mengedit data karyawan Nanda Safitri (EMP020).','2026-08-06 03:52:52'),(17,1,'Mengedit data karyawan Nanda Safitri (EMP020).','2026-08-06 03:54:08'),(18,1,'Mengedit data karyawan Nanda Safitri (EMP020).','2026-08-06 04:00:11'),(19,1,'Mengedit data karyawan Nanda Safitri (EMP020).','2026-08-06 06:16:08'),(20,1,'Mengedit data karyawan Nanda Safitri (EMP020).','2026-08-06 06:20:50'),(21,1,'Mengedit data karyawan Nanda Safitri (EMP020).','2026-08-06 06:33:58'),(22,1,'Mengedit data karyawan Nanda Safitri (EMP020).','2026-08-06 06:34:40'),(23,1,'Mengedit data karyawan Nanda Safitri (EMP020).','2026-08-06 06:36:37'),(24,1,'Mengedit data karyawan Nanda Safitri (EMP020).','2026-08-06 07:02:55'),(25,1,'Mengubah tampilan halaman publik.','2026-08-06 07:43:38'),(26,1,'Mengubah tampilan halaman publik.','2026-08-06 08:04:07'),(27,1,'Mengubah tampilan halaman publik.','2026-08-06 08:04:46'),(28,1,'Mengubah tampilan halaman publik.','2026-08-06 08:06:26'),(29,1,'Mengubah tampilan halaman publik.','2026-08-06 08:08:28'),(30,1,'Mengubah tampilan halaman publik.','2026-08-06 08:09:10'),(31,1,'Mengedit data karyawan Nanda Safitri (EMP020).','2026-08-06 08:56:50'),(32,1,'Mengedit data karyawan Nanda Safitri (EMP020).','2026-08-06 09:02:22'),(33,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:06:43'),(34,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:06:50'),(35,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:07:00'),(36,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:12:06'),(37,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:12:09'),(38,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:12:15'),(39,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:12:16'),(40,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:12:17'),(41,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:12:18'),(42,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:12:20'),(43,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:14:22'),(44,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:14:24'),(45,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:14:25'),(46,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:14:25'),(47,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:14:36'),(48,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:15:51'),(49,1,'Mengedit data karyawan Nanda Safitri (EMP020).','2026-08-07 02:16:13'),(50,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:16:19'),(51,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:16:29'),(52,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:18:00'),(53,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:18:06'),(54,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:25:02'),(55,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:36:11'),(56,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:36:18'),(57,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:36:54'),(58,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:41:35'),(59,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:50:22'),(60,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 02:54:14'),(61,1,'Mengedit data karyawan Nanda Safitri (EMP020).','2026-08-07 03:34:09'),(62,1,'Mengedit data karyawan Nanda Safitri (EMP020).','2026-08-07 03:35:03'),(63,1,'Mengedit data karyawan Nanda Safitri (EMP020).','2026-08-07 03:54:05'),(64,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 03:56:10'),(65,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 03:57:29'),(66,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 03:58:02'),(67,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 03:58:24'),(68,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 03:59:01'),(69,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 04:04:43'),(70,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 04:16:23'),(71,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 04:16:37'),(72,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 04:23:34'),(73,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 05:57:55'),(74,1,'Mengedit data karyawan Nanda Safitri (EMP020).','2026-08-07 05:59:11'),(75,1,'Membuat CV PDF untuk Nanda Safitri (EMP020).','2026-08-07 05:59:57'),(76,1,'Mengedit data karyawan Iqbal Maulana (EMP020).','2026-08-07 06:09:50'),(77,1,'Membuat CV PDF untuk Iqbal Maulana (EMP020).','2026-08-07 06:10:13'),(78,1,'Membuat CV PDF untuk Iqbal Maulana (EMP020).','2026-08-07 06:14:55'),(79,1,'Mengedit data karyawan Iqbal Maulana (EMP020).','2026-08-07 06:16:11'),(80,1,'Membuat CV PDF untuk Iqbal Maulana (EMP020).','2026-08-07 06:16:14'),(81,1,'Membuat CV PDF untuk Iqbal Maulana (EMP020).','2026-08-07 06:33:32'),(82,1,'Membuat CV PDF untuk Iqbal Maulana (EMP020).','2026-08-07 06:36:59'),(83,1,'Membuat CV PDF untuk Iqbal Maulana (EMP020).','2026-08-07 07:00:28'),(84,1,'Menambahkan karyawan aaa (EMP021).','2026-08-07 07:15:18'),(85,1,'Menambahkan karyawan adiguna (EMP022).','2026-08-07 07:15:44'),(86,1,'Menghapus data karyawan ID 403.','2026-08-07 07:15:50'),(87,1,'Mengedit data karyawan Iqbal Maulana (EMP020).','2026-08-07 07:31:49'),(88,1,'Membuat CV PDF untuk Iqbal Maulana (EMP020).','2026-08-07 07:31:56'),(89,2,'Membuat CV PDF untuk Iqbal Maulana (EMP020).','2026-08-07 07:33:26'),(90,2,'Mengedit data karyawan Iqbal Maulana (EMP020).','2026-08-07 07:36:02'),(91,2,'Membuat CV PDF untuk Iqbal Maulana (EMP020).','2026-08-07 07:36:08'),(92,5,'Membuat CV PDF untuk Iqbal Maulana (EMP020).','2026-08-07 07:36:28'),(93,5,'Membuat CV PDF untuk Iqbal Maulana (EMP020).','2026-08-07 07:37:51'),(94,1,'Membuat CV PDF untuk Iqbal Maulana (EMP020).','2026-08-07 07:38:24'),(95,1,'Membuat CV PDF untuk Iqbal Maulana (EMP020).','2026-08-07 07:44:52'),(96,1,'Membuat CV PDF untuk Iqbal Maulana (EMP020).','2026-08-07 07:46:26'),(97,1,'Membuat CV PDF untuk Iqbal Maulana (EMP020).','2026-08-07 07:46:41'),(98,1,'Membuat CV PDF untuk Iqbal Maulana (EMP020).','2026-08-07 07:47:01'),(99,1,'Membuat CV PDF untuk adiguna (EMP022).','2026-08-07 08:22:00'),(100,1,'Membuat CV PDF untuk Iqbal Maulana (EMP020).','2026-08-07 08:22:15'),(101,1,'Membuat CV PDF untuk Iqbal Maulana (EMP020).','2026-08-08 16:08:11'),(102,1,'Membuat CV PDF untuk Iqbal Maulana (EMP020).','2026-08-08 16:08:12'),(103,1,'Membuat CV PDF untuk Iqbal Maulana (EMP020).','2026-08-10 01:15:47'),(104,1,'Membuat CV PDF untuk Iqbal Maulana (EMP020).','2026-08-10 01:52:08'),(105,1,'Menambahkan pengguna naufal dengan role manager dan departemen ID 16.','2026-08-10 04:21:49'),(106,1,'Menambahkan pengguna koordinator dengan role koordinator dan departemen ID 16.','2026-08-10 04:52:21'),(107,1,'Mengubah pengguna koordinator menjadi role koordinator dan departemen ID 16.','2026-08-10 04:52:42'),(108,1,'Mengubah pengguna naufal menjadi role manager dan departemen ID 16.','2026-08-10 04:53:10'),(109,1,'Menambahkan pengguna pic dengan role pic dan departemen ID 16.','2026-08-10 04:54:05'),(110,8,'Membuat laporan lembur karyawan ID 404.','2026-08-10 04:56:31'),(111,7,'Memproses approval lembur ID 1 pada tahap koordinator.','2026-08-10 04:59:04'),(112,6,'Memproses approval lembur ID 1 pada tahap manager.','2026-08-10 04:59:26'),(113,8,'Memasukkan kompensasi lembur ID 1.','2026-08-10 05:10:20'),(114,1,'Membuat periode gaji 2026-01.','2026-08-10 06:00:22'),(115,1,'Mengunci periode gaji ID 1.','2026-08-10 06:00:29'),(116,1,'Membuat slip gaji periode ID 1.','2026-08-10 06:00:41'),(117,1,'Menambahkan komponen upah 1.','2026-08-10 06:10:43'),(118,1,'Mengubah profil upah karyawan EMP022.','2026-08-10 06:11:12'),(119,1,'Mengubah profil upah karyawan EMP022.','2026-08-10 06:12:16'),(120,1,'Mengubah status komponen upah ID 1.','2026-08-10 06:23:07'),(121,1,'Mengubah status komponen upah ID 1.','2026-08-10 06:24:25'),(122,1,'Mengubah profil upah karyawan EMP022.','2026-08-10 06:33:25'),(123,1,'Mengubah profil upah karyawan EMP022.','2026-08-10 06:44:14'),(124,1,'Mengubah profil upah karyawan EMP022.','2026-08-10 06:47:35'),(125,1,'Mengedit data karyawan Iqbal Maulana (EMP020).','2026-08-10 07:21:46'),(126,1,'Mengedit data karyawan Iqbal Maulana (EMP020).','2026-08-10 07:25:22'),(127,1,'Mengedit data karyawan Iqbal Maulana (EMP020).','2026-08-10 07:26:26'),(128,1,'Mengedit data karyawan Iqbal Maulana (EMP020).','2026-08-10 07:26:57'),(129,1,'Mengedit data karyawan Iqbal Maulana (EMP020).','2026-08-10 07:30:16'),(130,8,'Membuat laporan lembur karyawan ID 399.','2026-08-11 02:06:45'),(131,7,'Memproses approval lembur ID 2 pada tahap koordinator.','2026-08-11 02:07:18'),(132,6,'Memproses approval lembur ID 2 pada tahap manager.','2026-08-11 02:07:31'),(133,8,'Membatalkan laporan lembur ID 2.','2026-08-11 02:15:31'),(134,8,'Membatalkan laporan lembur ID 1.','2026-08-11 02:15:47'),(135,8,'Membuat laporan lembur karyawan ID 404.','2026-08-11 02:17:58'),(136,7,'Memproses approval lembur ID 3 pada tahap koordinator.','2026-08-11 02:21:44'),(137,6,'Memproses approval lembur ID 3 pada tahap manager.','2026-08-11 02:25:07'),(138,8,'Memasukkan kompensasi lembur ID 3.','2026-08-11 02:25:47'),(139,8,'Memasukkan kompensasi lembur ID 3.','2026-08-11 02:25:49'),(140,8,'Memasukkan kompensasi lembur ID 3.','2026-08-11 02:25:50'),(141,8,'Memasukkan kompensasi lembur ID 3.','2026-08-11 02:25:51'),(142,8,'Memasukkan kompensasi lembur ID 3.','2026-08-11 02:25:51'),(143,8,'Memasukkan kompensasi lembur ID 3.','2026-08-11 02:25:51'),(144,8,'Memasukkan kompensasi lembur ID 3.','2026-08-11 02:25:52'),(145,8,'Memasukkan kompensasi lembur ID 3.','2026-08-11 02:25:52'),(146,8,'Memasukkan kompensasi lembur ID 3.','2026-08-11 02:25:53'),(147,8,'Memasukkan kompensasi lembur ID 3.','2026-08-11 02:25:54'),(148,8,'Memasukkan kompensasi lembur ID 3.','2026-08-11 02:25:54'),(149,8,'Memasukkan kompensasi lembur ID 3.','2026-08-11 02:25:54'),(150,8,'Memasukkan kompensasi lembur ID 3.','2026-08-11 02:25:54'),(151,8,'Memasukkan kompensasi lembur ID 3.','2026-08-11 02:25:55'),(152,8,'Memasukkan kompensasi dan menyelesaikan lembur ID 3.','2026-08-11 02:28:05'),(153,1,'Mengubah profil upah karyawan EMP022.','2026-08-11 02:35:20'),(154,1,'Mengubah personalisasi tampilan publik, dashboard, dan grafik analisis.','2026-08-11 03:02:52'),(155,1,'Mengubah personalisasi tampilan publik, dashboard, dan grafik analisis.','2026-08-11 03:03:54'),(156,1,'Mengubah personalisasi tampilan publik, dashboard, dan grafik analisis.','2026-08-11 03:04:24'),(157,2,'Mengedit data karyawan Iqbal Maulana (EMP020).','2026-08-11 03:27:49'),(158,1,'Menambahkan pengguna pic2 dengan role pic dan departemen ID 9.','2026-08-11 03:31:20'),(159,1,'Menambahkan pengguna koor2 dengan role koordinator dan departemen ID 9.','2026-08-11 03:32:03'),(160,1,'Menambahkan pengguna manajer2 dengan role manager dan departemen ID 9.','2026-08-11 03:32:37'),(161,9,'Membuat laporan lembur karyawan ID 402.','2026-08-11 03:33:55'),(162,10,'Memproses approval lembur ID 4 pada tahap koordinator.','2026-08-11 03:34:23'),(163,11,'Memproses approval lembur ID 4 pada tahap manager.','2026-08-11 03:34:55'),(164,9,'Memasukkan kompensasi dan menyelesaikan lembur ID 4.','2026-08-11 03:35:11'),(165,1,'Mengubah profil upah karyawan EMP020.','2026-08-11 03:36:48'),(166,1,'Membuat laporan lembur karyawan ID 383.','2026-08-11 03:37:59'),(167,1,'Memproses persetujuan pusat lembur ID 5.','2026-08-11 03:38:02'),(168,1,'Menghapus laporan lembur ID 5.','2026-08-11 03:41:33'),(169,2,'Mengubah profil upah karyawan EMP022.','2026-08-11 03:44:19'),(170,8,'Membuat laporan lembur karyawan ID 399.','2026-08-11 03:49:36'),(171,2,'Memproses persetujuan pusat lembur ID 6.','2026-08-11 03:50:38'),(172,1,'Mengedit data karyawan Iqbal Maulana (EMP020).','2026-08-11 04:05:09'),(173,1,'Membuat CV PDF untuk Iqbal Maulana (EMP020).','2026-08-11 04:05:20'),(174,1,'Membuat CV PDF untuk Iqbal Maulana (EMP020).','2026-08-11 04:08:34'),(175,1,'Membuat CV PDF untuk Iqbal Maulana (EMP020).','2026-08-11 04:12:12'),(176,1,'Mengubah profil upah karyawan EMP009.','2026-08-11 04:16:07'),(177,8,'Memasukkan kompensasi dan menyelesaikan lembur ID 6.','2026-08-11 04:19:11'),(178,8,'Membuat laporan lembur karyawan ID 404.','2026-08-11 04:19:35'),(179,2,'Menghapus laporan lembur ID 7.','2026-08-11 04:21:03'),(180,1,'Mengimpor 220 data karyawan dari Excel.','2026-08-11 06:31:31'),(181,1,'Menghapus laporan lembur ID 6.','2026-08-11 06:37:09'),(182,1,'Menghapus laporan lembur ID 2.','2026-08-11 06:37:14');
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=604 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `karyawan`
--

LOCK TABLES `karyawan` WRITE;
/*!40000 ALTER TABLE `karyawan` DISABLE KEYS */;
INSERT INTO `karyawan` VALUES (383,'Andi Pratama','EMP001','Direktur Utama','FINANCE',8,30000000.00,'F','Menikah','2018-01-01','Kontrak','72',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(384,'Budi Pratama','EMP002','Manager Keuangan','FINANCE',8,15250000.00,'M','Belum Menikah','2019-02-02','Aktif','79',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(385,'Citra Pratama','EMP003','Staff Keuangan','FINANCE',8,6500000.00,'F','Belum Menikah','2020-03-03','Aktif','86',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(386,'Dedi Pratama','EMP004','Staff Keuangan','FINANCE',8,6750000.00,'M','Menikah','2021-04-04','Aktif','93',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(387,'Eka Pratama','EMP005','Staff Keuangan','FINANCE',8,6000000.00,'F','Belum Menikah','2022-05-05','Aktif','73',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(388,'Fajar Pratama','EMP006','Staff Keuangan','FINANCE',8,6250000.00,'M','Belum Menikah','2023-06-06','Aktif','80',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(389,'Gita Pratama','EMP007','Staff Keuangan','FINANCE',8,6500000.00,'F','Menikah','2024-07-07','Aktif','87',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(390,'Hendra Pratama','EMP008','Admin','FINANCE',8,6750000.00,'F','Belum Menikah','2025-08-08','Aktif','94',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(391,'Indah Pratama','EMP009','Admin','FINANCE',8,6000000.00,'F','Belum Menikah','2026-09-09','Aktif','74',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(392,'Joko Pratama','EMP010','Pengadaan','FINANCE',8,6250000.00,'M','Menikah','2018-10-10','Aktif','81',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(393,'Kartika Pratama','EMP011','Pengadaan','FINANCE',8,6500000.00,'M','Belum Menikah','2019-11-11','Kontrak','88',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(394,'Lukman Pratama','EMP012','Senior Advisor','FINANCE',8,15750000.00,'M','Belum Menikah','2020-12-12','Aktif','95',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(395,'Maya Pratama','EMP013','Direktur','PROJECT',14,25000000.00,'M','Menikah','2021-01-13','Aktif','75',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(396,'Naufal Pratama','EMP014','Site Manager PJBS','PROJECT',7,15250000.00,'M','Belum Menikah','2022-02-14','Aktif','82',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(397,'Oktavia Pratama','EMP015','Business Advisor','PROJECT',14,15500000.00,'F','Belum Menikah','2023-03-15','Aktif','89',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(398,'Putra Pratama','EMP016','Manager Contract Liaison','PROJECT',15,15750000.00,'M','Menikah','2024-04-16','Aktif','96',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(399,'Qori Pratama','EMP017','Staff Project','PROJECT',16,6000000.00,'M','Belum Menikah','2025-05-17','Aktif','76',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(400,'Rizky Pratama','EMP018','Staff Project','PROJECT',17,6250000.00,'M','Belum Menikah','2026-06-18','Aktif','83',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(401,'Sari Pratama','EMP019','Staff Project','PROJECT',18,6500000.00,'M','Menikah','2018-07-19','Aktif','90',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(402,'Taufik Pratama','EMP020','Staff Project','PROJECT',9,6750000.00,'M','Belum Menikah','2019-08-20','Aktif','97','0a36bfe49e19f02f259e3f1ebc757d86.jpg','41718561129fabf7817561d2fd3bb4c8.pdf','44334','tubanan','2026-08-06','islam','08233322','iqbal@gmail.com','f1b3955b9665414561ea2e4c066d10e9.pdf','c458dfb245699f6bc305655a5df5ffd0.pdf','Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.','',NULL,'',NULL,'2026-08-07','Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit animi id est laborum.','cv-generated-402-20260811-041211-40147825.pdf'),(404,'Vina Pratama','EMP022','Staff Project','PROJECT',16,6250000.00,'F','Menikah','2021-10-22','Aktif','84',NULL,NULL,'','',NULL,'','','',NULL,NULL,'','',NULL,'',NULL,NULL,'','cv-generated-404-20260807-082200-2f22ab2b.pdf'),(405,'Utami Pratama','EMP021','Staff Project','PROJECT',NULL,6000000.00,'M','Belum Menikah','2020-09-21','Kontrak','77',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(406,'Wahyu Pratama','EMP023','PIC','PROJECT',NULL,8500000.00,'M','Belum Menikah','2022-11-23','Aktif','91',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(407,'Yuni Pratama','EMP024','PIC','PROJECT',NULL,8750000.00,'M','Belum Menikah','2023-12-24','Aktif','98',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(408,'Zaki Pratama','EMP025','Site Manager','PROJECT',NULL,15000000.00,'M','Menikah','2024-01-25','Aktif','78',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(409,'Andi Santoso','EMP026','Site Manager','PROJECT',NULL,15250000.00,'M','Belum Menikah','2025-02-26','Aktif','85',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(410,'Budi Santoso','EMP027','Leader Project','PROJECT',NULL,8500000.00,'M','Belum Menikah','2026-03-27','Aktif','92',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(411,'Citra Santoso','EMP028','Leader Project','PROJECT',NULL,8750000.00,'M','Menikah','2018-04-01','Aktif','72',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(412,'Dedi Santoso','EMP029','Leader Project','PROJECT',NULL,8000000.00,'F','Belum Menikah','2019-05-02','Aktif','79',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(413,'Eka Santoso','EMP030','Surveyor','PROJECT',NULL,6250000.00,'M','Belum Menikah','2020-06-03','Aktif','86',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(414,'Fajar Santoso','EMP031','Surveyor','PROJECT',NULL,6500000.00,'M','Menikah','2021-07-04','Kontrak','93',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(415,'Gita Santoso','EMP032','Surveyor','PROJECT',NULL,6750000.00,'M','Belum Menikah','2022-08-05','Aktif','73',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(416,'Hendra Santoso','EMP033','Surveyor','PROJECT',NULL,6000000.00,'M','Belum Menikah','2023-09-06','Aktif','80',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(417,'Indah Santoso','EMP034','Admin Legal','PROJECT',NULL,6250000.00,'M','Menikah','2024-10-07','Aktif','87',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(418,'Joko Santoso','EMP035','Admin Legal','PROJECT',NULL,6500000.00,'F','Belum Menikah','2025-11-08','Aktif','94',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(419,'Kartika Santoso','EMP036','HSSE','PROJECT',NULL,8750000.00,'F','Belum Menikah','2026-12-09','Aktif','74',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(420,'Lukman Santoso','EMP037','HSSE','PROJECT',NULL,8000000.00,'M','Menikah','2018-01-10','Aktif','81',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(421,'Maya Santoso','EMP038','Admin HSSE','PROJECT',NULL,8250000.00,'M','Belum Menikah','2019-02-11','Aktif','88',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(422,'Naufal Santoso','EMP039','Admin HSSE','PROJECT',NULL,8500000.00,'F','Belum Menikah','2020-03-12','Aktif','95',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(423,'Oktavia Santoso','EMP040','Driver','PROJECT',NULL,6250000.00,'M','Menikah','2021-04-13','Aktif','75',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(424,'Putra Santoso','EMP041','Driver','PROJECT',NULL,5500000.00,'M','Belum Menikah','2022-05-14','Kontrak','82',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(425,'Qori Santoso','EMP042','Driver','PROJECT',NULL,5750000.00,'M','Belum Menikah','2023-06-15','Aktif','89',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(426,'Rizky Santoso','EMP043','Manager HRD','HRGA',NULL,15500000.00,'F','Menikah','2024-07-16','Aktif','96',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(427,'Sari Santoso','EMP044','Admin HR GA','HRGA',NULL,6750000.00,'M','Belum Menikah','2025-08-17','Aktif','76',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(428,'Taufik Santoso','EMP045','Admin HR GA','HRGA',NULL,6000000.00,'F','Belum Menikah','2026-09-18','Aktif','83',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(429,'Utami Santoso','EMP046','Admin HR GA','HRGA',NULL,6250000.00,'M','Menikah','2018-10-19','Aktif','90',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(430,'Vina Santoso','EMP047','Admin HR GA','HRGA',NULL,6500000.00,'F','Belum Menikah','2019-11-20','Aktif','97',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(431,'Wahyu Santoso','EMP048','Staff IT','HRGA',NULL,7750000.00,'M','Belum Menikah','2020-12-21','Aktif','77',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(432,'Yuni Santoso','EMP049','Staff IT','HRGA',NULL,7000000.00,'M','Menikah','2021-01-22','Aktif','84',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(433,'Zaki Santoso','EMP050','Staff IT','HRGA',NULL,7250000.00,'F','Belum Menikah','2022-02-23','Aktif','91',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(434,'Andi Wijaya','EMP051','Cleaning Office','HRGA',NULL,5300000.00,'M','Belum Menikah','2023-03-24','Kontrak','98',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(435,'Budi Wijaya','EMP052','Cleaning Office','HRGA',NULL,5550000.00,'M','Menikah','2024-04-25','Aktif','78',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(436,'Citra Wijaya','EMP053','Cleaning Office','HRGA',NULL,4800000.00,'M','Belum Menikah','2025-05-26','Aktif','85',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(437,'Dedi Wijaya','EMP054','Cleaning Office','HRGA',NULL,5050000.00,'M','Belum Menikah','2026-06-27','Aktif','92',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(438,'Eka Wijaya','EMP055','Cleaning Office','HRGA',NULL,5300000.00,'M','Menikah','2018-07-01','Aktif','72',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(439,'Fajar Wijaya','EMP056','Cleaning Office','HRGA',NULL,5550000.00,'M','Belum Menikah','2019-08-02','Aktif','79',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(440,'Gita Wijaya','EMP057','Cleaning Office','HRGA',NULL,4800000.00,'F','Belum Menikah','2020-09-03','Aktif','86',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(441,'Hendra Wijaya','EMP058','Cleaning Office','HRGA',NULL,5050000.00,'M','Menikah','2021-10-04','Aktif','93',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(442,'Indah Wijaya','EMP059','Security','HRGA',NULL,6000000.00,'M','Belum Menikah','2022-11-05','Aktif','73',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(443,'Joko Wijaya','EMP060','Security','HRGA',NULL,6250000.00,'M','Belum Menikah','2023-12-06','Aktif','80',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(444,'Kartika Wijaya','EMP061','Security','HRGA',NULL,5500000.00,'M','Menikah','2024-01-07','Kontrak','87',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(445,'Lukman Wijaya','EMP062','Security','HRGA',NULL,5750000.00,'M','Belum Menikah','2025-02-08','Aktif','94',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(446,'Maya Wijaya','EMP063','Security','HRGA',NULL,6000000.00,'M','Belum Menikah','2026-03-09','Aktif','74',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(447,'Naufal Wijaya','EMP064','Security','HRGA',NULL,6250000.00,'F','Menikah','2018-04-10','Aktif','81',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(448,'Oktavia Wijaya','EMP065','Security','HRGA',NULL,5500000.00,'M','Belum Menikah','2019-05-11','Aktif','88',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(449,'Putra Wijaya','EMP066','Security','HRGA',NULL,5750000.00,'M','Belum Menikah','2020-06-12','Aktif','95',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(450,'Qori Wijaya','EMP067','Security','HRGA',NULL,6000000.00,'M','Menikah','2021-07-13','Aktif','75',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(451,'Rizky Wijaya','EMP068','Security','HRGA',NULL,6250000.00,'M','Belum Menikah','2022-08-14','Aktif','82',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(452,'Sari Wijaya','EMP069','OB NPA','HRGA',NULL,4800000.00,'M','Belum Menikah','2023-09-15','Aktif','89',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(453,'Taufik Wijaya','EMP070','OB NPA','HRGA',NULL,5050000.00,'M','Menikah','2024-10-16','Aktif','96',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(454,'Utami Wijaya','EMP071','OB NPA','HRGA',NULL,5300000.00,'F','Belum Menikah','2025-11-17','Kontrak','76',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(455,'Vina Wijaya','EMP072','OB NPA','HRGA',NULL,5550000.00,'M','Belum Menikah','2026-12-18','Aktif','83',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(456,'Wahyu Wijaya','EMP073','OB NPA','HRGA',NULL,4800000.00,'M','Menikah','2018-01-19','Aktif','90',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(457,'Yuni Wijaya','EMP074','Leader Housekeeping','HRGA',NULL,8250000.00,'M','Belum Menikah','2019-02-20','Aktif','97',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(458,'Zaki Wijaya','EMP075','Leader Housekeeping','HRGA',NULL,8500000.00,'M','Belum Menikah','2020-03-21','Aktif','77',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(459,'Andi Lestari','EMP076','Helper Housekeeping','HRGA',NULL,5550000.00,'M','Menikah','2021-04-22','Aktif','84',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(460,'Budi Lestari','EMP077','Helper Housekeeping','HRGA',NULL,4800000.00,'M','Belum Menikah','2022-05-23','Aktif','91',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(461,'Citra Lestari','EMP078','Helper Housekeeping','HRGA',NULL,5050000.00,'F','Belum Menikah','2023-06-24','Aktif','98',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(462,'Dedi Lestari','EMP079','Helper Housekeeping','HRGA',NULL,5300000.00,'M','Menikah','2024-07-25','Aktif','78',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(463,'Eka Lestari','EMP080','Helper Housekeeping','HRGA',NULL,5550000.00,'M','Belum Menikah','2025-08-26','Aktif','85',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(464,'Fajar Lestari','EMP081','Admin Ops','OPERATIONAL & COMMERCIAL',NULL,6000000.00,'F','Belum Menikah','2026-09-27','Kontrak','92',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(465,'Gita Lestari','EMP082','Admin Ops','OPERATIONAL & COMMERCIAL',NULL,6250000.00,'M','Menikah','2018-10-01','Aktif','72',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(466,'Hendra Lestari','EMP083','Admin Ops','OPERATIONAL & COMMERCIAL',NULL,6500000.00,'F','Belum Menikah','2019-11-02','Aktif','79',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(467,'Indah Lestari','EMP084','Admin Ops','OPERATIONAL & COMMERCIAL',NULL,6750000.00,'M','Belum Menikah','2020-12-03','Aktif','86',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(468,'Joko Lestari','EMP085','Koordinator AYM FGD FABA','OPERATIONAL & COMMERCIAL',NULL,9500000.00,'F','Menikah','2021-01-04','Aktif','93',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(469,'Kartika Lestari','EMP086','Koordinator AYM FGD FABA','OPERATIONAL & COMMERCIAL',NULL,9750000.00,'M','Belum Menikah','2022-02-05','Aktif','73',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(470,'Lukman Lestari','EMP087','Admin AYM','OPERATIONAL & COMMERCIAL',NULL,6500000.00,'F','Belum Menikah','2023-03-06','Aktif','80',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(471,'Maya Lestari','EMP088','Admin AYM','OPERATIONAL & COMMERCIAL',NULL,6750000.00,'M','Menikah','2024-04-07','Aktif','87',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(472,'Naufal Lestari','EMP089','Admin Pemanfaatan FABA','OPERATIONAL & COMMERCIAL',NULL,6000000.00,'F','Belum Menikah','2025-05-08','Aktif','94',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(473,'Oktavia Lestari','EMP090','Admin Pemanfaatan FABA','OPERATIONAL & COMMERCIAL',NULL,6250000.00,'M','Belum Menikah','2026-06-09','Aktif','74',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(474,'Putra Lestari','EMP091','PIC MG JT Roadsweeper','OPERATIONAL & COMMERCIAL',NULL,8500000.00,'M','Menikah','2018-07-10','Kontrak','81',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(475,'Qori Lestari','EMP092','PIC FGD FABA','OPERATIONAL & COMMERCIAL',NULL,8750000.00,'F','Belum Menikah','2019-08-11','Aktif','88',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(476,'Rizky Lestari','EMP093','Operator Alat Berat','OPERATIONAL & COMMERCIAL',NULL,5500000.00,'M','Belum Menikah','2020-09-12','Aktif','95',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(477,'Sari Lestari','EMP094','Operator Alat Berat','OPERATIONAL & COMMERCIAL',NULL,5750000.00,'M','Menikah','2021-10-13','Aktif','75',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(478,'Taufik Lestari','EMP095','Operator Alat Berat','OPERATIONAL & COMMERCIAL',NULL,6000000.00,'M','Belum Menikah','2022-11-14','Aktif','82',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(479,'Utami Lestari','EMP096','Operator Alat Berat','OPERATIONAL & COMMERCIAL',NULL,6250000.00,'M','Belum Menikah','2023-12-15','Aktif','89',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(480,'Vina Lestari','EMP097','Operator Alat Berat','OPERATIONAL & COMMERCIAL',NULL,5500000.00,'M','Menikah','2024-01-16','Aktif','96',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(481,'Wahyu Lestari','EMP098','Operator Alat Berat','OPERATIONAL & COMMERCIAL',NULL,5750000.00,'M','Belum Menikah','2025-02-17','Aktif','76',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(482,'Yuni Lestari','EMP099','Operator Alat Berat','OPERATIONAL & COMMERCIAL',NULL,6000000.00,'F','Belum Menikah','2026-03-18','Aktif','83',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(483,'Zaki Lestari','EMP100','Operator Alat Berat','OPERATIONAL & COMMERCIAL',NULL,6250000.00,'M','Menikah','2018-04-19','Aktif','90',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(484,'Andi Saputra','EMP101','Operator Alat Berat','OPERATIONAL & COMMERCIAL',NULL,5500000.00,'M','Belum Menikah','2019-05-20','Kontrak','97',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(485,'Budi Saputra','EMP102','Operator Alat Berat','OPERATIONAL & COMMERCIAL',NULL,5750000.00,'M','Belum Menikah','2020-06-21','Aktif','77',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(486,'Citra Saputra','EMP103','Operator Alat Berat','OPERATIONAL & COMMERCIAL',NULL,6000000.00,'M','Menikah','2021-07-22','Aktif','84',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(487,'Dedi Saputra','EMP104','Operator Alat Berat','OPERATIONAL & COMMERCIAL',NULL,6250000.00,'M','Belum Menikah','2022-08-23','Aktif','91',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(488,'Eka Saputra','EMP105','Operator Alat Berat','OPERATIONAL & COMMERCIAL',NULL,5500000.00,'M','Belum Menikah','2023-09-24','Aktif','98',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(489,'Fajar Saputra','EMP106','Shift Leader','OPERATIONAL & COMMERCIAL',NULL,8250000.00,'F','Menikah','2024-10-25','Aktif','78',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(490,'Gita Saputra','EMP107','Shift Leader','OPERATIONAL & COMMERCIAL',NULL,8500000.00,'M','Belum Menikah','2025-11-26','Aktif','85',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(491,'Hendra Saputra','EMP108','Shift Leader','OPERATIONAL & COMMERCIAL',NULL,8750000.00,'M','Belum Menikah','2026-12-27','Aktif','92',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(492,'Indah Saputra','EMP109','Shift Leader','OPERATIONAL & COMMERCIAL',NULL,8000000.00,'M','Menikah','2018-01-01','Aktif','72',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(493,'Joko Saputra','EMP110','Shift Leader','OPERATIONAL & COMMERCIAL',NULL,8250000.00,'M','Belum Menikah','2019-02-02','Aktif','79',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(494,'Kartika Saputra','EMP111','Main Gate','OPERATIONAL & COMMERCIAL',NULL,6000000.00,'M','Belum Menikah','2020-03-03','Kontrak','86',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(495,'Lukman Saputra','EMP112','Main Gate','OPERATIONAL & COMMERCIAL',NULL,6250000.00,'M','Menikah','2021-04-04','Aktif','93',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(496,'Maya Saputra','EMP113','Main Gate','OPERATIONAL & COMMERCIAL',NULL,5500000.00,'F','Belum Menikah','2022-05-05','Aktif','73',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(497,'Naufal Saputra','EMP114','Main Gate','OPERATIONAL & COMMERCIAL',NULL,5750000.00,'M','Belum Menikah','2023-06-06','Aktif','80',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(498,'Oktavia Saputra','EMP115','Main Gate','OPERATIONAL & COMMERCIAL',NULL,6000000.00,'M','Menikah','2024-07-07','Aktif','87',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(499,'Putra Saputra','EMP116','Main Gate','OPERATIONAL & COMMERCIAL',NULL,6250000.00,'M','Belum Menikah','2025-08-08','Aktif','94',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(500,'Qori Saputra','EMP117','Jembatan Timbang','OPERATIONAL & COMMERCIAL',NULL,5500000.00,'M','Belum Menikah','2026-09-09','Aktif','74',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(501,'Rizky Saputra','EMP118','Jembatan Timbang','OPERATIONAL & COMMERCIAL',NULL,5750000.00,'M','Menikah','2018-10-10','Aktif','81',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(502,'Sari Saputra','EMP119','Jembatan Timbang','OPERATIONAL & COMMERCIAL',NULL,6000000.00,'M','Belum Menikah','2019-11-11','Aktif','88',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(503,'Taufik Saputra','EMP120','Jembatan Timbang','OPERATIONAL & COMMERCIAL',NULL,6250000.00,'F','Belum Menikah','2020-12-12','Aktif','95',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(504,'Utami Saputra','EMP121','Rigger','OPERATIONAL & COMMERCIAL',NULL,5500000.00,'M','Menikah','2021-01-13','Kontrak','75',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(505,'Vina Saputra','EMP122','Rigger','OPERATIONAL & COMMERCIAL',NULL,5750000.00,'M','Belum Menikah','2022-02-14','Aktif','82',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(506,'Wahyu Saputra','EMP123','Rigger','OPERATIONAL & COMMERCIAL',NULL,6000000.00,'M','Belum Menikah','2023-03-15','Aktif','89',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(507,'Yuni Saputra','EMP124','Rigger','OPERATIONAL & COMMERCIAL',NULL,6250000.00,'M','Menikah','2024-04-16','Aktif','96',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(508,'Zaki Saputra','EMP125','Rigger','OPERATIONAL & COMMERCIAL',NULL,5500000.00,'M','Belum Menikah','2025-05-17','Aktif','76',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(509,'Andi Hidayat','EMP126','Rigger','OPERATIONAL & COMMERCIAL',NULL,5750000.00,'M','Belum Menikah','2026-06-18','Aktif','83',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(510,'Budi Hidayat','EMP127','Operator Pompa','OPERATIONAL & COMMERCIAL',NULL,6000000.00,'F','Menikah','2018-07-19','Aktif','90',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(511,'Citra Hidayat','EMP128','Operator Pompa','OPERATIONAL & COMMERCIAL',NULL,6250000.00,'M','Belum Menikah','2019-08-20','Aktif','97',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(512,'Dedi Hidayat','EMP129','Operator Pompa','OPERATIONAL & COMMERCIAL',NULL,5500000.00,'M','Belum Menikah','2020-09-21','Aktif','77',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(513,'Eka Hidayat','EMP130','Operator Pompa','OPERATIONAL & COMMERCIAL',NULL,5750000.00,'M','Menikah','2021-10-22','Aktif','84',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(514,'Fajar Hidayat','EMP131','Operator Pompa','OPERATIONAL & COMMERCIAL',NULL,6000000.00,'M','Belum Menikah','2022-11-23','Kontrak','91',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(515,'Gita Hidayat','EMP132','Operator Pompa','OPERATIONAL & COMMERCIAL',NULL,6250000.00,'M','Belum Menikah','2023-12-24','Aktif','98',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(516,'Hendra Hidayat','EMP133','Operator Jaringan','OPERATIONAL & COMMERCIAL',NULL,5500000.00,'M','Menikah','2024-01-25','Aktif','78',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(517,'Indah Hidayat','EMP134','Operator Jaringan','OPERATIONAL & COMMERCIAL',NULL,5750000.00,'F','Belum Menikah','2025-02-26','Aktif','85',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(518,'Joko Hidayat','EMP135','Operator Jaringan','OPERATIONAL & COMMERCIAL',NULL,6000000.00,'M','Belum Menikah','2026-03-27','Aktif','92',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(519,'Kartika Hidayat','EMP136','Operator Jaringan','OPERATIONAL & COMMERCIAL',NULL,6250000.00,'M','Menikah','2018-04-01','Aktif','72',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(520,'Lukman Hidayat','EMP137','Cleaning Ash Yard','OPERATIONAL & COMMERCIAL',NULL,4800000.00,'M','Belum Menikah','2019-05-02','Aktif','79',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(521,'Maya Hidayat','EMP138','Cleaning Ash Yard','OPERATIONAL & COMMERCIAL',NULL,5050000.00,'M','Belum Menikah','2020-06-03','Aktif','86',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(522,'Naufal Hidayat','EMP139','Cleaning Ash Yard','OPERATIONAL & COMMERCIAL',NULL,5300000.00,'M','Menikah','2021-07-04','Aktif','93',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(523,'Oktavia Hidayat','EMP140','Cleaning Ash Yard','OPERATIONAL & COMMERCIAL',NULL,5550000.00,'M','Belum Menikah','2022-08-05','Aktif','73',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(524,'Putra Hidayat','EMP141','Cleaning Ash Yard','OPERATIONAL & COMMERCIAL',NULL,4800000.00,'F','Belum Menikah','2023-09-06','Kontrak','80',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(525,'Qori Hidayat','EMP142','Cleaning Ash Yard','OPERATIONAL & COMMERCIAL',NULL,5050000.00,'M','Menikah','2024-10-07','Aktif','87',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(526,'Rizky Hidayat','EMP143','Cleaning Ash Yard','OPERATIONAL & COMMERCIAL',NULL,5300000.00,'M','Belum Menikah','2025-11-08','Aktif','94',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(527,'Sari Hidayat','EMP144','Cleaning Ash Yard','OPERATIONAL & COMMERCIAL',NULL,5550000.00,'M','Belum Menikah','2026-12-09','Aktif','74',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(528,'Taufik Hidayat','EMP145','Cleaning Ash Yard','OPERATIONAL & COMMERCIAL',NULL,4800000.00,'M','Menikah','2018-01-10','Aktif','81',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(529,'Utami Hidayat','EMP146','Cleaning Ash Yard','OPERATIONAL & COMMERCIAL',NULL,5050000.00,'M','Belum Menikah','2019-02-11','Aktif','88',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(530,'Vina Hidayat','EMP147','Grass Cutting','OPERATIONAL & COMMERCIAL',NULL,5300000.00,'M','Belum Menikah','2020-03-12','Aktif','95',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(531,'Wahyu Hidayat','EMP148','Grass Cutting','OPERATIONAL & COMMERCIAL',NULL,5550000.00,'F','Menikah','2021-04-13','Aktif','75',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(532,'Yuni Hidayat','EMP149','Grass Cutting','OPERATIONAL & COMMERCIAL',NULL,4800000.00,'M','Belum Menikah','2022-05-14','Aktif','82',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(533,'Zaki Hidayat','EMP150','Grass Cutting','OPERATIONAL & COMMERCIAL',NULL,5050000.00,'M','Belum Menikah','2023-06-15','Aktif','89',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(534,'Andi Permata','EMP151','Grass Cutting','OPERATIONAL & COMMERCIAL',NULL,5300000.00,'M','Menikah','2024-07-16','Kontrak','96',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(535,'Budi Permata','EMP152','Mekanik','OPERATIONAL & COMMERCIAL',NULL,7750000.00,'M','Belum Menikah','2025-08-17','Aktif','76',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(536,'Citra Permata','EMP153','Mekanik','OPERATIONAL & COMMERCIAL',NULL,7000000.00,'M','Belum Menikah','2026-09-18','Aktif','83',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(537,'Dedi Permata','EMP154','Mekanik','OPERATIONAL & COMMERCIAL',NULL,7250000.00,'M','Menikah','2018-10-19','Aktif','90',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(538,'Eka Permata','EMP155','Mekanik','OPERATIONAL & COMMERCIAL',NULL,7500000.00,'F','Belum Menikah','2019-11-20','Aktif','97',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(539,'Fajar Permata','EMP156','Mekanik','OPERATIONAL & COMMERCIAL',NULL,7750000.00,'M','Belum Menikah','2020-12-21','Aktif','77',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(540,'Gita Permata','EMP157','Mekanik','OPERATIONAL & COMMERCIAL',NULL,7000000.00,'M','Menikah','2021-01-22','Aktif','84',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(541,'Hendra Permata','EMP158','Mekanik','OPERATIONAL & COMMERCIAL',NULL,7250000.00,'M','Belum Menikah','2022-02-23','Aktif','91',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(542,'Indah Permata','EMP159','Mekanik','OPERATIONAL & COMMERCIAL',NULL,7500000.00,'M','Belum Menikah','2023-03-24','Aktif','98',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(543,'Joko Permata','EMP160','Staff Gudang','OPERATIONAL & COMMERCIAL',NULL,6750000.00,'M','Menikah','2024-04-25','Aktif','78',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(544,'Kartika Permata','EMP161','Staff Gudang','OPERATIONAL & COMMERCIAL',NULL,6000000.00,'M','Belum Menikah','2025-05-26','Kontrak','85',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(545,'Lukman Permata','EMP162','Staff Gudang','OPERATIONAL & COMMERCIAL',NULL,6250000.00,'F','Belum Menikah','2026-06-27','Aktif','92',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(546,'Maya Permata','EMP163','Staff Gudang','OPERATIONAL & COMMERCIAL',NULL,6500000.00,'M','Menikah','2018-07-01','Aktif','72',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(547,'Naufal Permata','EMP164','OW','OPERATIONAL & COMMERCIAL',NULL,5550000.00,'M','Belum Menikah','2019-08-02','Aktif','79',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(548,'Oktavia Permata','EMP165','OW','OPERATIONAL & COMMERCIAL',NULL,4800000.00,'M','Belum Menikah','2020-09-03','Aktif','86',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(549,'Putra Permata','EMP166','OW','OPERATIONAL & COMMERCIAL',NULL,5050000.00,'M','Menikah','2021-10-04','Aktif','93',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(550,'Qori Permata','EMP167','Operator FGD','OPERATIONAL & COMMERCIAL',NULL,6000000.00,'M','Belum Menikah','2022-11-05','Aktif','73',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(551,'Rizky Permata','EMP168','Operator FGD','OPERATIONAL & COMMERCIAL',NULL,6250000.00,'M','Belum Menikah','2023-12-06','Aktif','80',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(552,'Sari Permata','EMP169','Operator FGD','OPERATIONAL & COMMERCIAL',NULL,5500000.00,'F','Menikah','2024-01-07','Aktif','87',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(553,'Taufik Permata','EMP170','Operator FGD','OPERATIONAL & COMMERCIAL',NULL,5750000.00,'M','Belum Menikah','2025-02-08','Aktif','94',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(554,'Utami Permata','EMP171','Operator FGD','OPERATIONAL & COMMERCIAL',NULL,6000000.00,'M','Belum Menikah','2026-03-09','Kontrak','74',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(555,'Vina Permata','EMP172','Operator FGD','OPERATIONAL & COMMERCIAL',NULL,6250000.00,'M','Menikah','2018-04-10','Aktif','81',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(556,'Wahyu Permata','EMP173','Mekanik FABA','OPERATIONAL & COMMERCIAL',NULL,7000000.00,'M','Belum Menikah','2019-05-11','Aktif','88',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(557,'Yuni Permata','EMP174','Mekanik FABA','OPERATIONAL & COMMERCIAL',NULL,7250000.00,'M','Belum Menikah','2020-06-12','Aktif','95',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(558,'Zaki Permata','EMP175','Mekanik FABA','OPERATIONAL & COMMERCIAL',NULL,7500000.00,'M','Menikah','2021-07-13','Aktif','75',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(559,'Andi Kurniawan','EMP176','Mekanik FABA','OPERATIONAL & COMMERCIAL',NULL,7750000.00,'F','Belum Menikah','2022-08-14','Aktif','82',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(560,'Budi Kurniawan','EMP177','Operator FABA','OPERATIONAL & COMMERCIAL',NULL,5500000.00,'M','Belum Menikah','2023-09-15','Aktif','89',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(561,'Citra Kurniawan','EMP178','Operator FABA','OPERATIONAL & COMMERCIAL',NULL,5750000.00,'M','Menikah','2024-10-16','Aktif','96',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(562,'Dedi Kurniawan','EMP179','Operator FABA','OPERATIONAL & COMMERCIAL',NULL,6000000.00,'M','Belum Menikah','2025-11-17','Aktif','76',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(563,'Eka Kurniawan','EMP180','Operator FABA','OPERATIONAL & COMMERCIAL',NULL,6250000.00,'M','Belum Menikah','2026-12-18','Aktif','83',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(564,'Fajar Kurniawan','EMP181','Operator FABA','OPERATIONAL & COMMERCIAL',NULL,5500000.00,'M','Menikah','2018-01-19','Kontrak','90',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(565,'Gita Kurniawan','EMP182','Operator FABA','OPERATIONAL & COMMERCIAL',NULL,5750000.00,'M','Belum Menikah','2019-02-20','Aktif','97',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(566,'Hendra Kurniawan','EMP183','Operator FABA','OPERATIONAL & COMMERCIAL',NULL,6000000.00,'F','Belum Menikah','2020-03-21','Aktif','77',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(567,'Indah Kurniawan','EMP184','Operator FABA','OPERATIONAL & COMMERCIAL',NULL,6250000.00,'M','Menikah','2021-04-22','Aktif','84',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(568,'Joko Kurniawan','EMP185','Operator FABA','OPERATIONAL & COMMERCIAL',NULL,5500000.00,'M','Belum Menikah','2022-05-23','Aktif','91',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(569,'Kartika Kurniawan','EMP186','Supervisor Conveyor','OPERATIONAL & COMMERCIAL',NULL,9750000.00,'M','Belum Menikah','2023-06-24','Aktif','98',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(570,'Lukman Kurniawan','EMP187','Supervisor Conveyor','OPERATIONAL & COMMERCIAL',NULL,10000000.00,'M','Menikah','2024-07-25','Aktif','78',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(571,'Maya Kurniawan','EMP188','Mekanik Conveyor','OPERATIONAL & COMMERCIAL',NULL,7750000.00,'M','Belum Menikah','2025-08-26','Aktif','85',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(572,'Naufal Kurniawan','EMP189','Mekanik Conveyor','OPERATIONAL & COMMERCIAL',NULL,7000000.00,'M','Belum Menikah','2026-09-27','Aktif','92',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(573,'Oktavia Kurniawan','EMP190','Mekanik Conveyor','OPERATIONAL & COMMERCIAL',NULL,7250000.00,'F','Menikah','2018-10-01','Aktif','72',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(574,'Putra Kurniawan','EMP191','Operator Conveyor','OPERATIONAL & COMMERCIAL',NULL,6000000.00,'M','Belum Menikah','2019-11-02','Kontrak','79',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(575,'Qori Kurniawan','EMP192','Operator Conveyor','OPERATIONAL & COMMERCIAL',NULL,6250000.00,'M','Belum Menikah','2020-12-03','Aktif','86',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(576,'Rizky Kurniawan','EMP193','Operator Conveyor','OPERATIONAL & COMMERCIAL',NULL,5500000.00,'M','Menikah','2021-01-04','Aktif','93',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(577,'Sari Kurniawan','EMP194','Operator Conveyor','OPERATIONAL & COMMERCIAL',NULL,5750000.00,'M','Belum Menikah','2022-02-05','Aktif','73',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(578,'Taufik Kurniawan','EMP195','Operator Conveyor','OPERATIONAL & COMMERCIAL',NULL,6000000.00,'M','Belum Menikah','2023-03-06','Aktif','80',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(579,'Utami Kurniawan','EMP196','Operator Conveyor','OPERATIONAL & COMMERCIAL',NULL,6250000.00,'M','Menikah','2024-04-07','Aktif','87',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(580,'Vina Kurniawan','EMP197','Operator Conveyor','OPERATIONAL & COMMERCIAL',NULL,5500000.00,'F','Belum Menikah','2025-05-08','Aktif','94',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(581,'Wahyu Kurniawan','EMP198','Biopori','OPERATIONAL & COMMERCIAL',NULL,5050000.00,'M','Belum Menikah','2026-06-09','Aktif','74',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(582,'Yuni Kurniawan','EMP199','Biopori','OPERATIONAL & COMMERCIAL',NULL,5300000.00,'M','Menikah','2018-07-10','Aktif','81',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(583,'Zaki Kurniawan','EMP200','Biopori','OPERATIONAL & COMMERCIAL',NULL,5550000.00,'M','Belum Menikah','2019-08-11','Aktif','88',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(584,'Andi Ramadhan','EMP201','Mekanik HEM TJB','OPERATIONAL & COMMERCIAL',NULL,7000000.00,'M','Belum Menikah','2020-09-12','Kontrak','95',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(585,'Budi Ramadhan','EMP202','Mekanik HEM TJB','OPERATIONAL & COMMERCIAL',NULL,7250000.00,'M','Menikah','2021-10-13','Aktif','75',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(586,'Citra Ramadhan','EMP203','Mekanik HEM TJB','OPERATIONAL & COMMERCIAL',NULL,7500000.00,'M','Belum Menikah','2022-11-14','Aktif','82',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(587,'Dedi Ramadhan','EMP204','Mekanik HEM TJB','OPERATIONAL & COMMERCIAL',NULL,7750000.00,'F','Belum Menikah','2023-12-15','Aktif','89',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(588,'Eka Ramadhan','EMP205','Operator Roadsweeper','OPERATIONAL & COMMERCIAL',NULL,5500000.00,'M','Menikah','2024-01-16','Aktif','96',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(589,'Fajar Ramadhan','EMP206','Operator Roadsweeper','OPERATIONAL & COMMERCIAL',NULL,5750000.00,'M','Belum Menikah','2025-02-17','Aktif','76',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(590,'Gita Ramadhan','EMP207','Operator Roadsweeper','OPERATIONAL & COMMERCIAL',NULL,6000000.00,'M','Belum Menikah','2026-03-18','Aktif','83',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(591,'Hendra Ramadhan','EMP208','Operator Roadsweeper','OPERATIONAL & COMMERCIAL',NULL,6250000.00,'M','Menikah','2018-04-19','Aktif','90',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(592,'Indah Ramadhan','EMP209','Elektrik','OPERATIONAL & COMMERCIAL',NULL,7000000.00,'M','Belum Menikah','2019-05-20','Aktif','97',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(593,'Joko Ramadhan','EMP210','Elektrik','OPERATIONAL & COMMERCIAL',NULL,7250000.00,'M','Belum Menikah','2020-06-21','Aktif','77',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(594,'Kartika Ramadhan','EMP211','Elektrik','OPERATIONAL & COMMERCIAL',NULL,7500000.00,'F','Menikah','2021-07-22','Kontrak','84',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(595,'Lukman Ramadhan','EMP212','Operator Fuel','OPERATIONAL & COMMERCIAL',NULL,6250000.00,'M','Belum Menikah','2022-08-23','Aktif','91',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(596,'Maya Ramadhan','EMP213','Operator Fuel','OPERATIONAL & COMMERCIAL',NULL,5500000.00,'M','Belum Menikah','2023-09-24','Aktif','98',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(597,'Naufal Ramadhan','EMP214','Operator Fuel','OPERATIONAL & COMMERCIAL',NULL,5750000.00,'M','Menikah','2024-10-25','Aktif','78',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(598,'Oktavia Ramadhan','EMP215','Environment','OPERATIONAL & COMMERCIAL',NULL,6500000.00,'M','Belum Menikah','2025-11-26','Aktif','85',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(599,'Putra Ramadhan','EMP216','Environment','OPERATIONAL & COMMERCIAL',NULL,6750000.00,'M','Belum Menikah','2026-12-27','Aktif','92',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(600,'Qori Ramadhan','EMP217','Supervisor Mekanik HEM','OPERATIONAL & COMMERCIAL',NULL,9500000.00,'M','Menikah','2018-01-01','Aktif','72',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(601,'Rizky Ramadhan','EMP218','Supervisor Mekanik HEM','OPERATIONAL & COMMERCIAL',NULL,9750000.00,'F','Belum Menikah','2019-02-02','Aktif','79',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(602,'Sari Ramadhan','EMP219','Leader FABA','OPERATIONAL & COMMERCIAL',NULL,8500000.00,'M','Belum Menikah','2020-03-03','Aktif','86',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(603,'Taufik Ramadhan','EMP220','Leader FABA','OPERATIONAL & COMMERCIAL',NULL,8750000.00,'M','Menikah','2021-04-04','Aktif','93',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=54092 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `master_departemen`
--

LOCK TABLES `master_departemen` WRITE;
/*!40000 ALTER TABLE `master_departemen` DISABLE KEYS */;
INSERT INTO `master_departemen` VALUES (1,'Teknologi Informasi',1),(2,'Sumber Daya Manusia',1),(3,'Keuangan',1),(4,'Pemasaran',1),(5,'Operasional',1),(6,'IT',1),(7,'Human Resources',1),(8,'Finance',1),(9,'Marketing',1),(10,'Operations',1),(11,'Sales',1),(12,'Procurement',1),(13,'Legal',1),(14,'Production',1),(15,'Strategy',1),(16,'Engineering',1),(17,'General Affairs',1),(18,'Security',1),(53873,'PROJECT',1),(53874,'HRGA',1),(53875,'OPERATIONAL & COMMERCIAL',1);
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
) ENGINE=InnoDB AUTO_INCREMENT=76411 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `master_posisi`
--

LOCK TABLES `master_posisi` WRITE;
/*!40000 ALTER TABLE `master_posisi` DISABLE KEYS */;
INSERT INTO `master_posisi` VALUES (8,'Accountant'),(74819,'Admin'),(74845,'Admin AYM'),(74836,'Admin HR GA'),(74833,'Admin HSSE'),(74831,'Admin Legal'),(74843,'Admin Ops'),(74846,'Admin Pemanfaatan FABA'),(74867,'Biopori'),(74824,'Business Advisor'),(21,'Business Analyst'),(74856,'Cleaning Ash Yard'),(74838,'Cleaning Office'),(25,'Content Writer'),(11,'Customer Service'),(13,'Data Analyst'),(74822,'Direktur'),(74816,'Direktur Utama'),(74834,'Driver'),(74870,'Elektrik'),(74872,'Environment'),(3,'Finance Analyst'),(74857,'Grass Cutting'),(74842,'Helper Housekeeping'),(7,'HR Officer'),(2,'HR Specialist'),(74832,'HSSE'),(74852,'Jembatan Timbang'),(74844,'Koordinator AYM FGD FABA'),(74874,'Leader FABA'),(74841,'Leader Housekeeping'),(74829,'Leader Project'),(17,'Legal Officer'),(74851,'Main Gate'),(22,'Maintenance Technician'),(74825,'Manager Contract Liaison'),(74835,'Manager HRD'),(74817,'Manager Keuangan'),(4,'Marketing Executive'),(9,'Marketing Specialist'),(74858,'Mekanik'),(74865,'Mekanik Conveyor'),(74862,'Mekanik FABA'),(74868,'Mekanik HEM TJB'),(10,'Network Administrator'),(74840,'OB NPA'),(23,'Office Administrator'),(74849,'Operator Alat Berat'),(74866,'Operator Conveyor'),(74863,'Operator FABA'),(74861,'Operator FGD'),(74871,'Operator Fuel'),(74855,'Operator Jaringan'),(74854,'Operator Pompa'),(74869,'Operator Roadsweeper'),(74860,'OW'),(74820,'Pengadaan'),(74827,'PIC'),(74848,'PIC FGD FABA'),(74847,'PIC MG JT Roadsweeper'),(15,'Procurement Staff'),(20,'Production Planner'),(5,'Project Manager'),(18,'Quality Control'),(19,'Recruitment Specialist'),(74853,'Rigger'),(12,'Sales Executive'),(74839,'Security'),(24,'Security Supervisor'),(74821,'Senior Advisor'),(74850,'Shift Leader'),(74828,'Site Manager'),(74823,'Site Manager PJBS'),(6,'Software Developer'),(1,'Software Engineer'),(74859,'Staff Gudang'),(74837,'Staff IT'),(74818,'Staff Keuangan'),(74826,'Staff Project'),(74864,'Supervisor Conveyor'),(74873,'Supervisor Mekanik HEM'),(74830,'Surveyor'),(16,'UI/UX Designer'),(14,'Warehouse Supervisor');
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
) ENGINE=InnoDB AUTO_INCREMENT=12120 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
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
-- Table structure for table `overtime_approvals`
--

DROP TABLE IF EXISTS `overtime_approvals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `overtime_approvals` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `overtime_id` int unsigned NOT NULL,
  `tahap` enum('koordinator','manager') NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `approver_user_id` int DEFAULT NULL,
  `catatan` text,
  `decided_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_overtime_approval_stage` (`overtime_id`,`tahap`),
  KEY `fk_overtime_approval_user` (`approver_user_id`),
  CONSTRAINT `fk_overtime_approval_report` FOREIGN KEY (`overtime_id`) REFERENCES `overtime_reports` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_overtime_approval_user` FOREIGN KEY (`approver_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `overtime_approvals`
--

LOCK TABLES `overtime_approvals` WRITE;
/*!40000 ALTER TABLE `overtime_approvals` DISABLE KEYS */;
INSERT INTO `overtime_approvals` VALUES (1,1,'koordinator','approved',7,'','2026-08-10 11:59:04','2026-08-10 04:56:34'),(2,1,'manager','approved',6,'','2026-08-10 11:59:26','2026-08-10 04:56:34'),(5,3,'koordinator','approved',7,'','2026-08-11 09:21:44','2026-08-11 02:20:56'),(6,3,'manager','approved',6,'','2026-08-11 09:25:07','2026-08-11 02:20:56'),(7,4,'koordinator','approved',10,'','2026-08-11 10:34:23','2026-08-11 03:33:59'),(8,4,'manager','approved',11,'','2026-08-11 10:34:55','2026-08-11 03:33:59');
/*!40000 ALTER TABLE `overtime_approvals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `overtime_compensations`
--

DROP TABLE IF EXISTS `overtime_compensations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `overtime_compensations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `overtime_id` int unsigned NOT NULL,
  `metode_perhitungan` enum('per_jam','nominal_final') NOT NULL,
  `tarif_per_jam` decimal(15,2) DEFAULT NULL,
  `jumlah_upah` decimal(15,2) NOT NULL,
  `dimasukkan_oleh_pic` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_overtime_compensation_report` (`overtime_id`),
  KEY `fk_overtime_compensation_pic` (`dimasukkan_oleh_pic`),
  CONSTRAINT `fk_overtime_compensation_pic` FOREIGN KEY (`dimasukkan_oleh_pic`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_overtime_compensation_report` FOREIGN KEY (`overtime_id`) REFERENCES `overtime_reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `overtime_compensations`
--

LOCK TABLES `overtime_compensations` WRITE;
/*!40000 ALTER TABLE `overtime_compensations` DISABLE KEYS */;
INSERT INTO `overtime_compensations` VALUES (1,1,'per_jam',50000.00,100000.00,8,'2026-08-10 05:10:20','2026-08-10 05:10:20'),(2,3,'per_jam',34682.08,34682.08,8,'2026-08-11 02:25:47','2026-08-11 02:25:47'),(17,4,'per_jam',36416.18,202109.80,9,'2026-08-11 03:35:11','2026-08-11 03:35:11');
/*!40000 ALTER TABLE `overtime_compensations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `overtime_reports`
--

DROP TABLE IF EXISTS `overtime_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `overtime_reports` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `karyawan_id` int NOT NULL,
  `department_id` int unsigned NOT NULL,
  `dibuat_oleh_pic` int NOT NULL,
  `mulai_at` datetime NOT NULL,
  `selesai_at` datetime NOT NULL,
  `total_menit` int unsigned NOT NULL DEFAULT '0',
  `deskripsi` text,
  `status` enum('draft','menunggu_koordinator','menunggu_manager','disetujui','ditolak','selesai') NOT NULL DEFAULT 'draft',
  `submitted_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_overtime_karyawan` (`karyawan_id`),
  KEY `idx_overtime_department` (`department_id`),
  KEY `idx_overtime_status` (`status`),
  KEY `fk_overtime_pic` (`dibuat_oleh_pic`),
  CONSTRAINT `fk_overtime_department` FOREIGN KEY (`department_id`) REFERENCES `master_departemen` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_overtime_karyawan` FOREIGN KEY (`karyawan_id`) REFERENCES `karyawan` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_overtime_pic` FOREIGN KEY (`dibuat_oleh_pic`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `overtime_reports`
--

LOCK TABLES `overtime_reports` WRITE;
/*!40000 ALTER TABLE `overtime_reports` DISABLE KEYS */;
INSERT INTO `overtime_reports` VALUES (1,404,16,8,'2026-08-10 11:55:00','2026-08-10 17:01:00',306,'tugas tambahan','ditolak','2026-08-10 11:56:34','2026-08-10 04:56:31','2026-08-11 02:15:47'),(3,404,16,8,'2026-08-11 09:17:00','2026-08-11 10:17:00',60,'lembur tugas','selesai','2026-08-11 09:20:56','2026-08-11 02:17:58','2026-08-11 02:28:05'),(4,402,9,9,'2026-08-11 17:00:00','2026-08-11 22:33:00',333,'','selesai','2026-08-11 10:33:59','2026-08-11 03:33:55','2026-08-11 03:35:11');
/*!40000 ALTER TABLE `overtime_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pendapatan_tambahan_karyawan`
--

DROP TABLE IF EXISTS `pendapatan_tambahan_karyawan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pendapatan_tambahan_karyawan` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `karyawan_id` int NOT NULL,
  `nama` varchar(150) NOT NULL,
  `nilai` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pendapatan_tambahan_karyawan` (`karyawan_id`),
  CONSTRAINT `fk_pendapatan_tambahan_karyawan` FOREIGN KEY (`karyawan_id`) REFERENCES `karyawan` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pendapatan_tambahan_karyawan`
--

LOCK TABLES `pendapatan_tambahan_karyawan` WRITE;
/*!40000 ALTER TABLE `pendapatan_tambahan_karyawan` DISABLE KEYS */;
INSERT INTO `pendapatan_tambahan_karyawan` VALUES (5,402,'tunjangan',500000.00,'2026-08-11 03:36:48','2026-08-11 03:36:48'),(6,404,'bonus kinerja',100000.00,'2026-08-11 03:44:19','2026-08-11 03:44:19'),(7,404,'tunjangan',500000.00,'2026-08-11 03:44:19','2026-08-11 03:44:19');
/*!40000 ALTER TABLE `pendapatan_tambahan_karyawan` ENABLE KEYS */;
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
  `warna_grafik_status` char(7) NOT NULL DEFAULT '#2563eb',
  `warna_grafik_tren` char(7) NOT NULL DEFAULT '#2563eb',
  `warna_grafik_posisi` char(7) NOT NULL DEFAULT '#2563eb',
  `warna_grafik_departemen` char(7) NOT NULL DEFAULT '#2563eb',
  `warna_grafik_gaji` char(7) NOT NULL DEFAULT '#2563eb',
  `warna_grafik_performa` char(7) NOT NULL DEFAULT '#2563eb',
  `kolom_tabel_publik` text,
  `kartu_dashboard` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pengaturan_publik`
--

LOCK TABLES `pengaturan_publik` WRITE;
/*!40000 ALTER TABLE `pengaturan_publik` DISABLE KEYS */;
INSERT INTO `pengaturan_publik` VALUES (1,'Profil Karyawan','Profil Pekerja Perusahaan','Website ini menyajikan informasi profil karyawan berdasarkan dataset Human Resources.','Lihat Data Karyawan','#0055ff','#f9d801','#2563eb','#f9d801','#2563eb','#ffdd00','#2563eb','#93c5fd','#eb2424','#38d435','#1a59e0','#2563eb','#05318f','#eb24c0',NULL,NULL);
/*!40000 ALTER TABLE `pengaturan_publik` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `periode_gaji`
--

DROP TABLE IF EXISTS `periode_gaji`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `periode_gaji` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tahun` smallint unsigned NOT NULL,
  `bulan` tinyint unsigned NOT NULL,
  `status` enum('draft','dikunci') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_periode_gaji_tahun_bulan` (`tahun`,`bulan`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `periode_gaji`
--

LOCK TABLES `periode_gaji` WRITE;
/*!40000 ALTER TABLE `periode_gaji` DISABLE KEYS */;
INSERT INTO `periode_gaji` VALUES (1,2026,1,'dikunci','2026-08-10 06:00:22');
/*!40000 ALTER TABLE `periode_gaji` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `potongan_karyawan`
--

DROP TABLE IF EXISTS `potongan_karyawan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `potongan_karyawan` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `karyawan_id` int NOT NULL,
  `nama` varchar(150) NOT NULL,
  `nilai` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_potongan_karyawan` (`karyawan_id`),
  CONSTRAINT `fk_potongan_karyawan` FOREIGN KEY (`karyawan_id`) REFERENCES `karyawan` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `potongan_karyawan`
--

LOCK TABLES `potongan_karyawan` WRITE;
/*!40000 ALTER TABLE `potongan_karyawan` DISABLE KEYS */;
INSERT INTO `potongan_karyawan` VALUES (5,402,'BPJS',100000.00,'2026-08-11 03:36:48','2026-08-11 03:36:48'),(6,402,'PPH21',45000.00,'2026-08-11 03:36:48','2026-08-11 03:36:48'),(7,404,'BPJS',90000.00,'2026-08-11 03:44:19','2026-08-11 03:44:19'),(8,404,'PPH21',44999.77,'2026-08-11 03:44:19','2026-08-11 03:44:19'),(9,391,'PPH21',898808.00,'2026-08-11 04:16:07','2026-08-11 04:16:07');
/*!40000 ALTER TABLE `potongan_karyawan` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `profil_gaji`
--

LOCK TABLES `profil_gaji` WRITE;
/*!40000 ALTER TABLE `profil_gaji` DISABLE KEYS */;
INSERT INTO `profil_gaji` VALUES (1,383,8500000.00,0.00,'2022-01-10',NULL,NULL,'2026-08-10 04:27:35','2026-08-10 04:27:35'),(2,384,6500000.00,0.00,'2021-03-15',NULL,NULL,'2026-08-10 04:27:35','2026-08-10 04:27:35'),(3,385,7000000.00,0.00,'2020-07-01',NULL,NULL,'2026-08-10 04:27:35','2026-08-10 04:27:35'),(4,386,6800000.00,0.00,'2023-02-20',NULL,NULL,'2026-08-10 04:27:35','2026-08-10 04:27:35'),(5,387,7800000.00,0.00,'2021-11-08',NULL,NULL,'2026-08-10 04:27:35','2026-08-10 04:27:35'),(6,388,5200000.00,0.00,'2024-01-05',NULL,NULL,'2026-08-10 04:27:35','2026-08-10 04:27:35'),(7,389,6200000.00,0.00,'2022-06-13',NULL,NULL,'2026-08-10 04:27:35','2026-08-10 04:27:35'),(8,390,8200000.00,0.00,'2023-04-17',NULL,NULL,'2026-08-10 04:27:35','2026-08-10 04:27:35'),(9,391,6400000.00,0.00,'2026-08-11',NULL,NULL,'2026-08-10 04:27:35','2026-08-11 04:16:07'),(10,392,6000000.00,0.00,'2020-12-07',NULL,NULL,'2026-08-10 04:27:35','2026-08-10 04:27:35'),(11,393,7600000.00,0.00,'2023-08-14',NULL,NULL,'2026-08-10 04:27:35','2026-08-10 04:27:35'),(12,394,7900000.00,0.00,'2021-05-24',NULL,NULL,'2026-08-10 04:27:35','2026-08-10 04:27:35'),(13,395,5900000.00,0.00,'2022-10-03',NULL,NULL,'2026-08-10 04:27:35','2026-08-10 04:27:35'),(14,396,6700000.00,0.00,'2023-06-19',NULL,NULL,'2026-08-10 04:27:35','2026-08-10 04:27:35'),(15,397,7100000.00,0.00,'2020-02-11',NULL,NULL,'2026-08-10 04:27:35','2026-08-10 04:27:35'),(16,398,8300000.00,0.00,'2022-09-12',NULL,NULL,'2026-08-10 04:27:35','2026-08-10 04:27:35'),(17,399,6100000.00,0.00,'2019-04-29',NULL,NULL,'2026-08-10 04:27:35','2026-08-10 04:27:35'),(18,400,5500000.00,0.00,'2024-03-04',NULL,NULL,'2026-08-10 04:27:35','2026-08-10 04:27:35'),(19,401,5800000.00,0.00,'2021-08-30',NULL,NULL,'2026-08-10 04:27:35','2026-08-10 04:27:35'),(20,402,6300000.00,100000.00,'2026-08-11',NULL,NULL,'2026-08-10 04:27:35','2026-08-11 03:36:48'),(21,404,6000000.00,10000.00,'2026-08-11',NULL,NULL,'2026-08-10 04:27:35','2026-08-11 03:44:19');
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
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `riwayat_pekerjaan`
--

LOCK TABLES `riwayat_pekerjaan` WRITE;
/*!40000 ALTER TABLE `riwayat_pekerjaan` DISABLE KEYS */;
INSERT INTO `riwayat_pekerjaan` VALUES (12,402,'PLN (persero)','Manajer','it','2026-08-10','2026-08-11','','2026-08-11 04:05:09','2026-08-11 04:05:09'),(13,402,'Telkom','Developer','teknologi','2024-01-11','2026-08-11','saya menjadi developer it di telkom','2026-08-11 04:05:09','2026-08-11 04:05:09');
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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `riwayat_pendidikan`
--

LOCK TABLES `riwayat_pendidikan` WRITE;
/*!40000 ALTER TABLE `riwayat_pendidikan` DISABLE KEYS */;
INSERT INTO `riwayat_pendidikan` VALUES (9,402,'udinus','s1','informatika','2023-07-11','2026-08-11','','2026-08-11 04:05:09','2026-08-11 04:05:09');
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
INSERT INTO `schema_migrations` VALUES ('001','create_schema_migrations','2026-08-10 04:04:25'),('002','normalize_departments_and_histories','2026-08-10 04:08:56'),('003','extend_user_roles','2026-08-10 04:10:27'),('004','create_payroll_tables','2026-08-10 04:27:35'),('005','create_overtime_tables','2026-08-10 04:35:55'),('006','create_overtime_approvals','2026-08-10 04:38:13'),('007','create_overtime_compensations','2026-08-10 05:02:06'),('008','create_payroll_period_and_slips','2026-08-10 05:04:47'),('009','create_employee_income_items','2026-08-10 06:31:48'),('010','create_employee_deduction_items','2026-08-10 06:42:50');
/*!40000 ALTER TABLE `schema_migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `slip_gaji`
--

DROP TABLE IF EXISTS `slip_gaji`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `slip_gaji` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `periode_gaji_id` int unsigned NOT NULL,
  `karyawan_id` int NOT NULL,
  `employee_id_snapshot` varchar(30) NOT NULL,
  `nama_snapshot` varchar(150) NOT NULL,
  `posisi_snapshot` varchar(100) DEFAULT NULL,
  `departemen_snapshot` varchar(100) DEFAULT NULL,
  `total_pendapatan` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_potongan` decimal(15,2) NOT NULL DEFAULT '0.00',
  `gaji_bersih` decimal(15,2) NOT NULL DEFAULT '0.00',
  `generated_by` int NOT NULL,
  `generated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slip_gaji_periode_karyawan` (`periode_gaji_id`,`karyawan_id`),
  KEY `fk_slip_gaji_karyawan` (`karyawan_id`),
  KEY `fk_slip_gaji_generator` (`generated_by`),
  CONSTRAINT `fk_slip_gaji_generator` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_slip_gaji_karyawan` FOREIGN KEY (`karyawan_id`) REFERENCES `karyawan` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_slip_gaji_periode` FOREIGN KEY (`periode_gaji_id`) REFERENCES `periode_gaji` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `slip_gaji`
--

LOCK TABLES `slip_gaji` WRITE;
/*!40000 ALTER TABLE `slip_gaji` DISABLE KEYS */;
INSERT INTO `slip_gaji` VALUES (1,1,383,'EMP001','Andi Pratama','Software Developer','IT',8500000.00,0.00,8500000.00,1,'2026-08-10 06:00:41'),(2,1,384,'EMP002','Siti Rahmawati','HR Officer','Human Resources',6500000.00,0.00,6500000.00,1,'2026-08-10 06:00:41'),(3,1,385,'EMP003','Budi Santoso','Accountant','Finance',7000000.00,0.00,7000000.00,1,'2026-08-10 06:00:41'),(4,1,386,'EMP004','Dewi Lestari','Marketing Specialist','Marketing',6800000.00,0.00,6800000.00,1,'2026-08-10 06:00:41'),(5,1,387,'EMP005','Rizky Maulana','Network Administrator','IT',7800000.00,0.00,7800000.00,1,'2026-08-10 06:00:41'),(6,1,388,'EMP006','Nabila Putri','Customer Service','Operations',5200000.00,0.00,5200000.00,1,'2026-08-10 06:00:41'),(7,1,389,'EMP007','Fajar Nugroho','Sales Executive','Sales',6200000.00,0.00,6200000.00,1,'2026-08-10 06:00:41'),(8,1,390,'EMP008','Maya Sari','Data Analyst','IT',8200000.00,0.00,8200000.00,1,'2026-08-10 06:00:41'),(9,1,391,'EMP009','Arif Hidayat','Warehouse Supervisor','Operations',6400000.00,0.00,6400000.00,1,'2026-08-10 06:00:41'),(10,1,392,'EMP010','Rina Kusuma','Procurement Staff','Procurement',6000000.00,0.00,6000000.00,1,'2026-08-10 06:00:41'),(11,1,393,'EMP011','Dimas Saputra','UI/UX Designer','IT',7600000.00,0.00,7600000.00,1,'2026-08-10 06:00:41'),(12,1,394,'EMP012','Ayu Wulandari','Legal Officer','Legal',7900000.00,0.00,7900000.00,1,'2026-08-10 06:00:41'),(13,1,395,'EMP013','Yoga Prasetyo','Quality Control','Production',5900000.00,0.00,5900000.00,1,'2026-08-10 06:00:41'),(14,1,396,'EMP014','Intan Permata','Recruitment Specialist','Human Resources',6700000.00,0.00,6700000.00,1,'2026-08-10 06:00:41'),(15,1,397,'EMP015','Reza Kurniawan','Production Planner','Production',7100000.00,0.00,7100000.00,1,'2026-08-10 06:00:41'),(16,1,398,'EMP016','Putri Amelia','Business Analyst','Strategy',8300000.00,0.00,8300000.00,1,'2026-08-10 06:00:41'),(17,1,399,'EMP017','Hendra Wijaya','Maintenance Technician','Engineering',6100000.00,0.00,6100000.00,1,'2026-08-10 06:00:41'),(18,1,400,'EMP018','Lina Marlina','Office Administrator','General Affairs',5500000.00,0.00,5500000.00,1,'2026-08-10 06:00:41'),(19,1,401,'EMP019','Galih Ramadhan','Security Supervisor','Security',5800000.00,0.00,5800000.00,1,'2026-08-10 06:00:41'),(20,1,402,'EMP020','Iqbal Maulana','Content Writer','Marketing',6300000.00,0.00,6300000.00,1,'2026-08-10 06:00:41'),(21,1,404,'EMP022','adiguna','Accountant','Engineering',6000000.00,0.00,6000000.00,1,'2026-08-10 06:00:41');
/*!40000 ALTER TABLE `slip_gaji` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `slip_gaji_items`
--

DROP TABLE IF EXISTS `slip_gaji_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `slip_gaji_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `slip_gaji_id` int unsigned NOT NULL,
  `kategori` enum('pendapatan','potongan') NOT NULL,
  `kode` varchar(50) NOT NULL,
  `nama` varchar(150) NOT NULL,
  `jumlah` decimal(15,2) NOT NULL DEFAULT '0.00',
  `sumber_reference` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_slip_gaji_items_slip` (`slip_gaji_id`),
  CONSTRAINT `fk_slip_gaji_items_slip` FOREIGN KEY (`slip_gaji_id`) REFERENCES `slip_gaji` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `slip_gaji_items`
--

LOCK TABLES `slip_gaji_items` WRITE;
/*!40000 ALTER TABLE `slip_gaji_items` DISABLE KEYS */;
INSERT INTO `slip_gaji_items` VALUES (1,1,'pendapatan','GAJI_POKOK','Gaji Pokok',8500000.00,NULL),(2,1,'pendapatan','UANG_MAKAN','Uang Makan',0.00,NULL),(3,2,'pendapatan','GAJI_POKOK','Gaji Pokok',6500000.00,NULL),(4,2,'pendapatan','UANG_MAKAN','Uang Makan',0.00,NULL),(5,3,'pendapatan','GAJI_POKOK','Gaji Pokok',7000000.00,NULL),(6,3,'pendapatan','UANG_MAKAN','Uang Makan',0.00,NULL),(7,4,'pendapatan','GAJI_POKOK','Gaji Pokok',6800000.00,NULL),(8,4,'pendapatan','UANG_MAKAN','Uang Makan',0.00,NULL),(9,5,'pendapatan','GAJI_POKOK','Gaji Pokok',7800000.00,NULL),(10,5,'pendapatan','UANG_MAKAN','Uang Makan',0.00,NULL),(11,6,'pendapatan','GAJI_POKOK','Gaji Pokok',5200000.00,NULL),(12,6,'pendapatan','UANG_MAKAN','Uang Makan',0.00,NULL),(13,7,'pendapatan','GAJI_POKOK','Gaji Pokok',6200000.00,NULL),(14,7,'pendapatan','UANG_MAKAN','Uang Makan',0.00,NULL),(15,8,'pendapatan','GAJI_POKOK','Gaji Pokok',8200000.00,NULL),(16,8,'pendapatan','UANG_MAKAN','Uang Makan',0.00,NULL),(17,9,'pendapatan','GAJI_POKOK','Gaji Pokok',6400000.00,NULL),(18,9,'pendapatan','UANG_MAKAN','Uang Makan',0.00,NULL),(19,10,'pendapatan','GAJI_POKOK','Gaji Pokok',6000000.00,NULL),(20,10,'pendapatan','UANG_MAKAN','Uang Makan',0.00,NULL),(21,11,'pendapatan','GAJI_POKOK','Gaji Pokok',7600000.00,NULL),(22,11,'pendapatan','UANG_MAKAN','Uang Makan',0.00,NULL),(23,12,'pendapatan','GAJI_POKOK','Gaji Pokok',7900000.00,NULL),(24,12,'pendapatan','UANG_MAKAN','Uang Makan',0.00,NULL),(25,13,'pendapatan','GAJI_POKOK','Gaji Pokok',5900000.00,NULL),(26,13,'pendapatan','UANG_MAKAN','Uang Makan',0.00,NULL),(27,14,'pendapatan','GAJI_POKOK','Gaji Pokok',6700000.00,NULL),(28,14,'pendapatan','UANG_MAKAN','Uang Makan',0.00,NULL),(29,15,'pendapatan','GAJI_POKOK','Gaji Pokok',7100000.00,NULL),(30,15,'pendapatan','UANG_MAKAN','Uang Makan',0.00,NULL),(31,16,'pendapatan','GAJI_POKOK','Gaji Pokok',8300000.00,NULL),(32,16,'pendapatan','UANG_MAKAN','Uang Makan',0.00,NULL),(33,17,'pendapatan','GAJI_POKOK','Gaji Pokok',6100000.00,NULL),(34,17,'pendapatan','UANG_MAKAN','Uang Makan',0.00,NULL),(35,18,'pendapatan','GAJI_POKOK','Gaji Pokok',5500000.00,NULL),(36,18,'pendapatan','UANG_MAKAN','Uang Makan',0.00,NULL),(37,19,'pendapatan','GAJI_POKOK','Gaji Pokok',5800000.00,NULL),(38,19,'pendapatan','UANG_MAKAN','Uang Makan',0.00,NULL),(39,20,'pendapatan','GAJI_POKOK','Gaji Pokok',6300000.00,NULL),(40,20,'pendapatan','UANG_MAKAN','Uang Makan',0.00,NULL),(41,21,'pendapatan','GAJI_POKOK','Gaji Pokok',6000000.00,NULL),(42,21,'pendapatan','UANG_MAKAN','Uang Makan',0.00,NULL);
/*!40000 ALTER TABLE `slip_gaji_items` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'superadmin','$2y$12$hhiH9rU56.3doruUGL2o8.buWbk2KybIKc0XoJFRBESGlp1AjzI8W','Super Admin','superadmin',NULL,1,'2026-08-04 04:35:57'),(2,'admin','$2y$12$OItz5OFPa9QILceCtTXC/OOk6h6.5/lxi6ONAJFJXVvKQR1epMtB.','Administrator','admin',NULL,1,'2026-08-04 04:35:57'),(5,'iqbal','$2y$10$M2ADHxjFlwP6B3aD0rmd9uTcPBtdo3xUq2MsnURtCTSksqrji2iVC','iqbal','admin',NULL,1,'2026-08-05 06:37:37'),(6,'naufal','$2y$10$V/vIesPgiyt5Ts3WdRP/RuqlytlNVDU8Cjww1jGvfb70BzpXtm7NC','enginering','manager',16,1,'2026-08-10 04:21:49'),(7,'koordinator','$2y$10$P3t6a.L/.Np6yJp1y7Snbuiw20lJ54D2hdb/NLNCsv3LT3LYvbMNy','enginering2','koordinator',16,1,'2026-08-10 04:52:21'),(8,'pic','$2y$10$cQ1gzKPkHemdlxNmuC6SuO/pq9BqyoFGLVGfc79JfJ5BRkZvVMbH.','enginering3','pic',16,1,'2026-08-10 04:54:05'),(9,'pic2','$2y$10$PK0YO0J4xtwaCI29pMPa3Onp30iXFydyYezpru9jvCOvlQPDkIfha','MarketingPIC','pic',9,1,'2026-08-11 03:31:20'),(10,'koor2','$2y$10$.w6VemkuWGXs6KfXn5Sgn.DKFpJr/7BeZa7JtR3x/wQodYpu.my3i','MarketingKoor','koordinator',9,1,'2026-08-11 03:32:03'),(11,'manajer2','$2y$10$HOtvRWZRWDApg7vrTfXS0OM/paSCA99r5rlyqY1rit.5xXe/uiZWm','MarketingManajer','manager',9,1,'2026-08-11 03:32:37');
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

-- Dump completed on 2026-08-11 13:44:26
