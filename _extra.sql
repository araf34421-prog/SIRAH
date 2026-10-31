
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
DROP TABLE IF EXISTS `nilai_rapor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nilai_rapor` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_santri` int(11) NOT NULL,
  `kelas` varchar(50) DEFAULT 'Khusus Laki-Laki',
  `nama_materi` varchar(100) DEFAULT 'Materi Tahfiz',
  `label_pojok` varchar(30) DEFAULT 'Pojok',
  `label_1` varchar(60) DEFAULT NULL,
  `label_2` varchar(60) DEFAULT NULL,
  `label_3` varchar(60) DEFAULT NULL,
  `label_4` varchar(60) DEFAULT NULL,
  `label_5` varchar(60) DEFAULT NULL,
  `label_6` varchar(60) DEFAULT NULL,
  `label_7` varchar(60) DEFAULT NULL,
  `label_8` varchar(60) DEFAULT NULL,
  `label_9` varchar(60) DEFAULT NULL,
  `label_10` varchar(60) DEFAULT NULL,
  `label_11` varchar(60) DEFAULT NULL,
  `label_12` varchar(60) DEFAULT NULL,
  `label_13` varchar(60) DEFAULT NULL,
  `label_14` varchar(60) DEFAULT NULL,
  `label_15` varchar(60) DEFAULT NULL,
  `label_16` varchar(60) DEFAULT NULL,
  `label_17` varchar(60) DEFAULT NULL,
  `label_18` varchar(60) DEFAULT NULL,
  `label_19` varchar(60) DEFAULT NULL,
  `label_20` varchar(60) DEFAULT NULL,
  `semester` varchar(20) NOT NULL DEFAULT '2 (Dua)',
  `tahun_ajaran` varchar(20) NOT NULL DEFAULT '2026',
  `juz` int(11) NOT NULL DEFAULT 1,
  `judul_materi` varchar(100) DEFAULT NULL,
  `pojok_1` int(11) DEFAULT NULL,
  `pojok_2` int(11) DEFAULT NULL,
  `pojok_3` int(11) DEFAULT NULL,
  `pojok_4` int(11) DEFAULT NULL,
  `pojok_5` int(11) DEFAULT NULL,
  `pojok_6` int(11) DEFAULT NULL,
  `pojok_7` int(11) DEFAULT NULL,
  `pojok_8` int(11) DEFAULT NULL,
  `pojok_9` int(11) DEFAULT NULL,
  `pojok_10` int(11) DEFAULT NULL,
  `pojok_11` int(11) DEFAULT NULL,
  `pojok_12` int(11) DEFAULT NULL,
  `pojok_13` int(11) DEFAULT NULL,
  `pojok_14` int(11) DEFAULT NULL,
  `pojok_15` int(11) DEFAULT NULL,
  `pojok_16` int(11) DEFAULT NULL,
  `pojok_17` int(11) DEFAULT NULL,
  `pojok_18` int(11) DEFAULT NULL,
  `pojok_19` int(11) DEFAULT NULL,
  `pojok_20` int(11) DEFAULT NULL,
  `id_ustadz` int(11) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `materi_1` varchar(100) DEFAULT NULL,
  `materi_2` varchar(100) DEFAULT NULL,
  `materi_3` varchar(100) DEFAULT NULL,
  `materi_4` varchar(100) DEFAULT NULL,
  `materi_5` varchar(100) DEFAULT NULL,
  `materi_6` varchar(100) DEFAULT NULL,
  `materi_7` varchar(100) DEFAULT NULL,
  `materi_8` varchar(100) DEFAULT NULL,
  `materi_9` varchar(100) DEFAULT NULL,
  `materi_10` varchar(100) DEFAULT NULL,
  `materi_11` varchar(100) DEFAULT NULL,
  `materi_12` varchar(100) DEFAULT NULL,
  `materi_13` varchar(100) DEFAULT NULL,
  `materi_14` varchar(100) DEFAULT NULL,
  `materi_15` varchar(100) DEFAULT NULL,
  `materi_16` varchar(100) DEFAULT NULL,
  `materi_17` varchar(100) DEFAULT NULL,
  `materi_18` varchar(100) DEFAULT NULL,
  `materi_19` varchar(100) DEFAULT NULL,
  `materi_20` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unik_rapor_santri` (`id_santri`,`juz`,`semester`,`tahun_ajaran`),
  KEY `fk_rapor_ustadz` (`id_ustadz`),
  CONSTRAINT `fk_rapor_santri` FOREIGN KEY (`id_santri`) REFERENCES `santri` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rapor_ustadz` FOREIGN KEY (`id_ustadz`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `nilai_rapor` DISABLE KEYS */;
/*!40000 ALTER TABLE `nilai_rapor` ENABLE KEYS */;
DROP TABLE IF EXISTS `rapor_tahfidz`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rapor_tahfidz` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_santri` int(11) NOT NULL,
  `kelas` varchar(50) DEFAULT NULL,
  `judul_materi` varchar(100) DEFAULT 'Materi Tahfidz',
  `semester` varchar(20) NOT NULL,
  `tahun_ajaran` varchar(20) NOT NULL,
  `juz` int(11) NOT NULL,
  `id_ustadz` int(11) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_rapor_header_santri` (`id_santri`),
  CONSTRAINT `fk_rapor_header_santri` FOREIGN KEY (`id_santri`) REFERENCES `santri` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `rapor_tahfidz` DISABLE KEYS */;
INSERT INTO `rapor_tahfidz` VALUES (1,3,'Khusus Perempuan','Materi Tahfidz (Juz 1)','2 (Dua)','2026',1,1,'2026-10-21 06:45:40');
/*!40000 ALTER TABLE `rapor_tahfidz` ENABLE KEYS */;
DROP TABLE IF EXISTS `rapor_tahfidz_detail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rapor_tahfidz_detail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_rapor` int(11) NOT NULL,
  `urutan` int(11) NOT NULL,
  `nama_materi` varchar(100) NOT NULL,
  `nilai` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_rapor_detail` (`id_rapor`),
  CONSTRAINT `fk_rapor_detail` FOREIGN KEY (`id_rapor`) REFERENCES `rapor_tahfidz` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `rapor_tahfidz_detail` DISABLE KEYS */;
INSERT INTO `rapor_tahfidz_detail` VALUES (2,1,1,'Al-fatihah + Yasin',95);
/*!40000 ALTER TABLE `rapor_tahfidz_detail` ENABLE KEYS */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

