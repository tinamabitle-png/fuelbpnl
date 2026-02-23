-- MariaDB dump 10.19  Distrib 10.4.28-MariaDB, for osx10.10 (x86_64)
--
-- Host: 127.0.0.1    Database: fuellevy
-- ------------------------------------------------------
-- Server version	10.4.28-MariaDB

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
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `audit_logs_model_type_model_id_index` (`model_type`,`model_id`),
  KEY `audit_logs_user_id_index` (`user_id`),
  KEY `audit_logs_action_index` (`action`),
  KEY `audit_logs_created_at_index` (`created_at`),
  CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bank_statement_accounts`
--

DROP TABLE IF EXISTS `bank_statement_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bank_statement_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `upload_id` bigint(20) unsigned NOT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `account_number_masked` varchar(255) DEFAULT NULL,
  `period_start` date DEFAULT NULL,
  `period_end` date DEFAULT NULL,
  `opening_balance` decimal(12,2) DEFAULT NULL,
  `closing_balance` decimal(12,2) DEFAULT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'ZAR',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bank_statement_accounts_upload_id_period_start_period_end_index` (`upload_id`,`period_start`,`period_end`),
  CONSTRAINT `bank_statement_accounts_upload_id_foreign` FOREIGN KEY (`upload_id`) REFERENCES `bank_statement_uploads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bank_statement_accounts`
--

LOCK TABLES `bank_statement_accounts` WRITE;
/*!40000 ALTER TABLE `bank_statement_accounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `bank_statement_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bank_statement_features`
--

DROP TABLE IF EXISTS `bank_statement_features`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bank_statement_features` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `account_id` bigint(20) unsigned NOT NULL,
  `salary_inflows` decimal(12,2) DEFAULT NULL,
  `avg_monthly_income` decimal(12,2) DEFAULT NULL,
  `avg_monthly_expenses` decimal(12,2) DEFAULT NULL,
  `spend_volatility` decimal(8,4) DEFAULT NULL,
  `overdraft_count` int(10) unsigned NOT NULL DEFAULT 0,
  `nsf_count` int(10) unsigned NOT NULL DEFAULT 0,
  `avg_daily_balance` decimal(12,2) DEFAULT NULL,
  `cash_buffer_days` decimal(8,2) DEFAULT NULL,
  `risk_score` tinyint(3) unsigned DEFAULT NULL,
  `risk_band` enum('low','medium','high') DEFAULT NULL,
  `computed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bank_statement_features_account_id_risk_band_index` (`account_id`,`risk_band`),
  CONSTRAINT `bank_statement_features_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `bank_statement_accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bank_statement_features`
--

LOCK TABLES `bank_statement_features` WRITE;
/*!40000 ALTER TABLE `bank_statement_features` DISABLE KEYS */;
/*!40000 ALTER TABLE `bank_statement_features` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bank_statement_transactions`
--

DROP TABLE IF EXISTS `bank_statement_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bank_statement_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `account_id` bigint(20) unsigned NOT NULL,
  `transaction_date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(12,2) DEFAULT NULL,
  `type` enum('credit','debit') DEFAULT NULL,
  `balance_after` decimal(12,2) DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bank_statement_transactions_account_id_transaction_date_index` (`account_id`,`transaction_date`),
  KEY `bank_statement_transactions_account_id_type_index` (`account_id`,`type`),
  CONSTRAINT `bank_statement_transactions_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `bank_statement_accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bank_statement_transactions`
--

LOCK TABLES `bank_statement_transactions` WRITE;
/*!40000 ALTER TABLE `bank_statement_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `bank_statement_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bank_statement_uploads`
--

DROP TABLE IF EXISTS `bank_statement_uploads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bank_statement_uploads` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `source` enum('web','email','mobile') NOT NULL,
  `source_reference` varchar(255) DEFAULT NULL,
  `original_filename` varchar(255) DEFAULT NULL,
  `mime_type` varchar(255) DEFAULT NULL,
  `file_size` bigint(20) unsigned DEFAULT NULL,
  `temporary_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','processing','completed','failed','needs_review') NOT NULL DEFAULT 'pending',
  `ocr_provider` varchar(255) NOT NULL DEFAULT 'document_ai',
  `ocr_processor_type` varchar(255) DEFAULT NULL,
  `ocr_region` varchar(255) DEFAULT NULL,
  `ocr_confidence` decimal(5,2) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bank_statement_uploads_user_id_status_index` (`user_id`,`status`),
  KEY `bank_statement_uploads_source_created_at_index` (`source`,`created_at`),
  CONSTRAINT `bank_statement_uploads_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bank_statement_uploads`
--

LOCK TABLES `bank_statement_uploads` WRITE;
/*!40000 ALTER TABLE `bank_statement_uploads` DISABLE KEYS */;
/*!40000 ALTER TABLE `bank_statement_uploads` ENABLE KEYS */;
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
INSERT INTO `cache` VALUES ('fuellevy-cache-5c785c036466adea360111aa28563bfd556b5fba','i:2;',1771663837),('fuellevy-cache-5c785c036466adea360111aa28563bfd556b5fba:timer','i:1771663837;',1771663837),('fuellevy-cache-settings.app_name','s:8:\"fuellevy\";',2087023538),('fuellevy-cache-settings.app_url','s:21:\"http://localhost:8000\";',2087023538),('fuellevy-cache-settings.auto_approve_vouchers','s:1:\"0\";',2087023538),('fuellevy-cache-settings.cache_driver','s:8:\"database\";',2087023538),('fuellevy-cache-settings.currency','s:3:\"ZAR\";',2087023538),('fuellevy-cache-settings.currency_symbol','s:1:\"R\";',2087023538),('fuellevy-cache-settings.debug_mode','b:1;',2087023538),('fuellevy-cache-settings.default_payment_method','s:4:\"card\";',2087023538),('fuellevy-cache-settings.enable_bank_transfer','s:1:\"0\";',2087023538),('fuellevy-cache-settings.enable_mpesa','s:1:\"0\";',2087023538),('fuellevy-cache-settings.enable_netcash','b:0;',2087023538),('fuellevy-cache-settings.enable_paystack','s:1:\"1\";',2087023538),('fuellevy-cache-settings.locale','s:2:\"en\";',2087023538),('fuellevy-cache-settings.mail_driver','s:3:\"log\";',2087023538),('fuellevy-cache-settings.mail_encryption','N;',2087024340),('fuellevy-cache-settings.mail_from_address','s:17:\"hello@example.com\";',2087023538),('fuellevy-cache-settings.mail_from_name','s:8:\"fuellevy\";',2087023538),('fuellevy-cache-settings.mail_host','s:9:\"127.0.0.1\";',2087023538),('fuellevy-cache-settings.mail_password','N;',2087024339),('fuellevy-cache-settings.mail_port','s:4:\"2525\";',2087023538),('fuellevy-cache-settings.mail_username','N;',2087024339),('fuellevy-cache-settings.max_voucher_amount','i:50000;',2087023538),('fuellevy-cache-settings.min_voucher_amount','i:100;',2087023538),('fuellevy-cache-settings.payment_gateway','s:8:\"paystack\";',2087023538),('fuellevy-cache-settings.queue_driver','s:8:\"database\";',2087023538),('fuellevy-cache-settings.require_approval_threshold','N;',2087024340),('fuellevy-cache-settings.session_driver','s:8:\"database\";',2087023538),('fuellevy-cache-settings.timezone','s:3:\"UTC\";',2087023538),('fuellevy-cache-settings.uber_credit_boost_percent','d:20;',2087023538),('fuellevy-cache-settings.uber_credit_limit_cap','d:75000;',2087023538),('fuellevy-cache-settings.voucher_expiry_days','i:7;',2087023538),('fuellevy-cache-welcome:fuel-benchmarks','a:4:{s:8:\"currency\";s:3:\"ZAR\";s:6:\"prices\";a:3:{s:6:\"petrol\";d:20.10000000000000142108547152020037174224853515625;s:5:\"super\";d:19.989999999999998436805981327779591083526611328125;s:6:\"diesel\";d:17.949999999999999289457264239899814128875732421875;}s:6:\"source\";s:8:\"fallback\";s:10:\"fetched_at\";s:19:\"2026-02-21 08:50:35\";}',1771664435);
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
-- Table structure for table `chat_messages`
--

DROP TABLE IF EXISTS `chat_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chat_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `thread_id` bigint(20) unsigned NOT NULL,
  `sender_id` bigint(20) unsigned NOT NULL,
  `sender_role` varchar(50) DEFAULT NULL,
  `type` varchar(20) NOT NULL DEFAULT 'text',
  `body` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_mime` varchar(255) DEFAULT NULL,
  `file_size` bigint(20) unsigned DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `stream_message_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `chat_messages_thread_id_created_at_index` (`thread_id`,`created_at`),
  KEY `chat_messages_sender_id_created_at_index` (`sender_id`,`created_at`),
  KEY `chat_messages_stream_message_id_index` (`stream_message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chat_messages`
--

LOCK TABLES `chat_messages` WRITE;
/*!40000 ALTER TABLE `chat_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `chat_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chat_threads`
--

DROP TABLE IF EXISTS `chat_threads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chat_threads` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `station_id` bigint(20) unsigned NOT NULL,
  `owner_id` bigint(20) unsigned NOT NULL,
  `created_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `last_message_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `chat_threads_station_id_owner_id_index` (`station_id`,`owner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chat_threads`
--

LOCK TABLES `chat_threads` WRITE;
/*!40000 ALTER TABLE `chat_threads` DISABLE KEYS */;
/*!40000 ALTER TABLE `chat_threads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `credit_attributes`
--

DROP TABLE IF EXISTS `credit_attributes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `credit_attributes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `attribute_key` varchar(120) NOT NULL,
  `attribute_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`attribute_value`)),
  `as_of_date` date DEFAULT NULL,
  `source` varchar(60) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `credit_attributes_user_id_attribute_key_index` (`user_id`,`attribute_key`),
  KEY `credit_attributes_attribute_key_as_of_date_index` (`attribute_key`,`as_of_date`),
  CONSTRAINT `credit_attributes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `credit_attributes`
--

LOCK TABLES `credit_attributes` WRITE;
/*!40000 ALTER TABLE `credit_attributes` DISABLE KEYS */;
/*!40000 ALTER TABLE `credit_attributes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `credit_audit_logs`
--

DROP TABLE IF EXISTS `credit_audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `credit_audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `actor_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(120) NOT NULL,
  `entity_id` bigint(20) unsigned DEFAULT NULL,
  `payload_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload_json`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `credit_audit_logs_entity_type_entity_id_index` (`entity_type`,`entity_id`),
  KEY `credit_audit_logs_actor_id_action_index` (`actor_id`,`action`),
  CONSTRAINT `credit_audit_logs_actor_id_foreign` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `credit_audit_logs`
--

LOCK TABLES `credit_audit_logs` WRITE;
/*!40000 ALTER TABLE `credit_audit_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `credit_audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `credit_consents`
--

DROP TABLE IF EXISTS `credit_consents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `credit_consents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `source` varchar(60) NOT NULL,
  `scope` text NOT NULL,
  `granted_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  `revoked_at` timestamp NULL DEFAULT NULL,
  `evidence_ref` varchar(255) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `credit_consents_user_id_source_index` (`user_id`,`source`),
  KEY `credit_consents_user_id_granted_at_index` (`user_id`,`granted_at`),
  CONSTRAINT `credit_consents_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `credit_consents`
--

LOCK TABLES `credit_consents` WRITE;
/*!40000 ALTER TABLE `credit_consents` DISABLE KEYS */;
/*!40000 ALTER TABLE `credit_consents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `credit_data_sources`
--

DROP TABLE IF EXISTS `credit_data_sources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `credit_data_sources` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `source_type` varchar(60) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `last_synced_at` timestamp NULL DEFAULT NULL,
  `raw_ref` varchar(255) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `credit_data_sources_user_id_source_type_index` (`user_id`,`source_type`),
  KEY `credit_data_sources_status_last_synced_at_index` (`status`,`last_synced_at`),
  CONSTRAINT `credit_data_sources_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `credit_data_sources`
--

LOCK TABLES `credit_data_sources` WRITE;
/*!40000 ALTER TABLE `credit_data_sources` DISABLE KEYS */;
/*!40000 ALTER TABLE `credit_data_sources` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `credit_decisions`
--

DROP TABLE IF EXISTS `credit_decisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `credit_decisions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `upload_id` bigint(20) unsigned DEFAULT NULL,
  `score_id` bigint(20) unsigned DEFAULT NULL,
  `score` tinyint(3) unsigned DEFAULT NULL,
  `decision` enum('approve','review','deny') DEFAULT NULL,
  `application_type` varchar(60) NOT NULL DEFAULT 'voucher_bnpl',
  `reasons` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`reasons`)),
  `explanation_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`explanation_json`)),
  `model_version` varchar(255) NOT NULL DEFAULT 'rules-v1',
  `policy_version` varchar(50) NOT NULL DEFAULT 'policy-v1',
  `source` varchar(255) NOT NULL DEFAULT 'bank_statement',
  `decided_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `credit_decisions_upload_id_foreign` (`upload_id`),
  KEY `credit_decisions_user_id_decision_index` (`user_id`,`decision`),
  KEY `credit_decisions_score_id_foreign` (`score_id`),
  KEY `credit_decisions_application_type_decision_index` (`application_type`,`decision`),
  KEY `credit_decisions_user_id_decided_at_index` (`user_id`,`decided_at`),
  CONSTRAINT `credit_decisions_score_id_foreign` FOREIGN KEY (`score_id`) REFERENCES `credit_scores` (`id`) ON DELETE SET NULL,
  CONSTRAINT `credit_decisions_upload_id_foreign` FOREIGN KEY (`upload_id`) REFERENCES `bank_statement_uploads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `credit_decisions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `credit_decisions`
--

LOCK TABLES `credit_decisions` WRITE;
/*!40000 ALTER TABLE `credit_decisions` DISABLE KEYS */;
/*!40000 ALTER TABLE `credit_decisions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `credit_limits`
--

DROP TABLE IF EXISTS `credit_limits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `credit_limits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `limit` decimal(15,2) NOT NULL DEFAULT 5000.00,
  `used` decimal(15,2) NOT NULL DEFAULT 0.00,
  `review_date` date NOT NULL,
  `status` enum('active','frozen','under_review') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `credit_limits_user_id_unique` (`user_id`),
  KEY `credit_limits_review_date_index` (`review_date`),
  CONSTRAINT `credit_limits_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `credit_limits`
--

LOCK TABLES `credit_limits` WRITE;
/*!40000 ALTER TABLE `credit_limits` DISABLE KEYS */;
/*!40000 ALTER TABLE `credit_limits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `credit_scores`
--

DROP TABLE IF EXISTS `credit_scores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `credit_scores` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `score` smallint(5) unsigned NOT NULL,
  `band` varchar(30) NOT NULL,
  `version` varchar(50) NOT NULL DEFAULT 'rules-v1',
  `reasons_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`reasons_json`)),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `scored_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `credit_scores_user_id_scored_at_index` (`user_id`,`scored_at`),
  KEY `credit_scores_band_version_index` (`band`,`version`),
  CONSTRAINT `credit_scores_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `credit_scores`
--

LOCK TABLES `credit_scores` WRITE;
/*!40000 ALTER TABLE `credit_scores` DISABLE KEYS */;
/*!40000 ALTER TABLE `credit_scores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `developer_api_tokens`
--

DROP TABLE IF EXISTS `developer_api_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `developer_api_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `personal_access_token_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `key_prefix` varchar(16) DEFAULT NULL,
  `abilities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`abilities`)),
  `allowed_ips` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`allowed_ips`)),
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `revoked_at` timestamp NULL DEFAULT NULL,
  `revoked_reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `developer_api_tokens_user_id_revoked_at_index` (`user_id`,`revoked_at`),
  KEY `developer_api_tokens_personal_access_token_id_index` (`personal_access_token_id`),
  CONSTRAINT `developer_api_tokens_personal_access_token_id_foreign` FOREIGN KEY (`personal_access_token_id`) REFERENCES `personal_access_tokens` (`id`) ON DELETE SET NULL,
  CONSTRAINT `developer_api_tokens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `developer_api_tokens`
--

LOCK TABLES `developer_api_tokens` WRITE;
/*!40000 ALTER TABLE `developer_api_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `developer_api_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `devices`
--

DROP TABLE IF EXISTS `devices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `devices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `device_id` varchar(255) NOT NULL,
  `device_name` varchar(255) NOT NULL,
  `device_type` enum('android','ios','web') NOT NULL,
  `fcm_token` varchar(255) DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `devices_user_id_device_id_unique` (`user_id`,`device_id`),
  KEY `devices_device_id_index` (`device_id`),
  CONSTRAINT `devices_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `devices`
--

LOCK TABLES `devices` WRITE;
/*!40000 ALTER TABLE `devices` DISABLE KEYS */;
/*!40000 ALTER TABLE `devices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `driver_documents`
--

DROP TABLE IF EXISTS `driver_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `driver_documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `document_type` enum('driver_license','sa_id') NOT NULL,
  `document_path` varchar(255) NOT NULL,
  `document_name` varchar(255) NOT NULL,
  `document_number` varchar(255) DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `verified` tinyint(1) NOT NULL DEFAULT 0,
  `verified_by` bigint(20) unsigned DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `driver_documents_user_id_document_type_unique` (`user_id`,`document_type`),
  KEY `driver_documents_verified_by_foreign` (`verified_by`),
  CONSTRAINT `driver_documents_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `driver_documents_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `driver_documents`
--

LOCK TABLES `driver_documents` WRITE;
/*!40000 ALTER TABLE `driver_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `driver_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `energy_assets`
--

DROP TABLE IF EXISTS `energy_assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `energy_assets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fuel_station_id` bigint(20) unsigned NOT NULL,
  `owner_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `asset_type` enum('solar','battery','ev_charger','efficiency','wind','other') NOT NULL DEFAULT 'solar',
  `status` enum('planned','active','maintenance','offline','retired') NOT NULL DEFAULT 'planned',
  `capacity_kw` decimal(10,2) DEFAULT NULL,
  `capacity_kwh` decimal(10,2) DEFAULT NULL,
  `asset_cost` decimal(12,2) DEFAULT NULL,
  `vendor` varchar(255) DEFAULT NULL,
  `model` varchar(255) DEFAULT NULL,
  `serial_number` varchar(255) DEFAULT NULL,
  `commissioned_at` date DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `energy_assets_fuel_station_id_asset_type_index` (`fuel_station_id`,`asset_type`),
  KEY `energy_assets_owner_id_status_index` (`owner_id`,`status`),
  CONSTRAINT `energy_assets_fuel_station_id_foreign` FOREIGN KEY (`fuel_station_id`) REFERENCES `fuel_stations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `energy_assets_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `energy_assets`
--

LOCK TABLES `energy_assets` WRITE;
/*!40000 ALTER TABLE `energy_assets` DISABLE KEYS */;
/*!40000 ALTER TABLE `energy_assets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `energy_projects`
--

DROP TABLE IF EXISTS `energy_projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `energy_projects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fuel_station_id` bigint(20) unsigned NOT NULL,
  `owner_id` bigint(20) unsigned DEFAULT NULL,
  `energy_asset_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `project_type` enum('solar','battery','ev_charger','efficiency','wind','other') NOT NULL DEFAULT 'solar',
  `status` enum('planned','active','suspended','completed','cancelled') NOT NULL DEFAULT 'planned',
  `total_cost` decimal(12,2) DEFAULT NULL,
  `financed_amount` decimal(12,2) DEFAULT NULL,
  `interest_rate` decimal(5,2) DEFAULT NULL,
  `term_months` int(10) unsigned DEFAULT NULL,
  `monthly_payment` decimal(12,2) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `activated_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `energy_projects_energy_asset_id_foreign` (`energy_asset_id`),
  KEY `energy_projects_fuel_station_id_status_index` (`fuel_station_id`,`status`),
  KEY `energy_projects_owner_id_project_type_index` (`owner_id`,`project_type`),
  CONSTRAINT `energy_projects_energy_asset_id_foreign` FOREIGN KEY (`energy_asset_id`) REFERENCES `energy_assets` (`id`) ON DELETE SET NULL,
  CONSTRAINT `energy_projects_fuel_station_id_foreign` FOREIGN KEY (`fuel_station_id`) REFERENCES `fuel_stations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `energy_projects_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `energy_projects`
--

LOCK TABLES `energy_projects` WRITE;
/*!40000 ALTER TABLE `energy_projects` DISABLE KEYS */;
/*!40000 ALTER TABLE `energy_projects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `energy_readings`
--

DROP TABLE IF EXISTS `energy_readings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `energy_readings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `energy_asset_id` bigint(20) unsigned NOT NULL,
  `fuel_station_id` bigint(20) unsigned NOT NULL,
  `reading_type` enum('production','consumption','export','import','storage_charge','storage_discharge') NOT NULL DEFAULT 'production',
  `value` decimal(12,3) NOT NULL,
  `unit` varchar(255) NOT NULL DEFAULT 'kWh',
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `period_start` timestamp NULL DEFAULT NULL,
  `period_end` timestamp NULL DEFAULT NULL,
  `source` varchar(255) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `energy_readings_fuel_station_id_recorded_at_index` (`fuel_station_id`,`recorded_at`),
  KEY `energy_readings_energy_asset_id_recorded_at_index` (`energy_asset_id`,`recorded_at`),
  CONSTRAINT `energy_readings_energy_asset_id_foreign` FOREIGN KEY (`energy_asset_id`) REFERENCES `energy_assets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `energy_readings_fuel_station_id_foreign` FOREIGN KEY (`fuel_station_id`) REFERENCES `fuel_stations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `energy_readings`
--

LOCK TABLES `energy_readings` WRITE;
/*!40000 ALTER TABLE `energy_readings` DISABLE KEYS */;
/*!40000 ALTER TABLE `energy_readings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `energy_repayments`
--

DROP TABLE IF EXISTS `energy_repayments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `energy_repayments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `energy_project_id` bigint(20) unsigned NOT NULL,
  `fuel_station_id` bigint(20) unsigned NOT NULL,
  `owner_id` bigint(20) unsigned DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `due_date` date NOT NULL,
  `paid_at` datetime DEFAULT NULL,
  `status` enum('pending','paid','overdue','defaulted') NOT NULL DEFAULT 'pending',
  `payment_method` varchar(255) DEFAULT NULL,
  `transaction_reference` varchar(255) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `energy_repayments_energy_project_id_status_index` (`energy_project_id`,`status`),
  KEY `energy_repayments_fuel_station_id_status_index` (`fuel_station_id`,`status`),
  KEY `energy_repayments_owner_id_status_index` (`owner_id`,`status`),
  KEY `energy_repayments_due_date_index` (`due_date`),
  KEY `energy_repayments_paid_at_index` (`paid_at`),
  CONSTRAINT `energy_repayments_energy_project_id_foreign` FOREIGN KEY (`energy_project_id`) REFERENCES `energy_projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `energy_repayments_fuel_station_id_foreign` FOREIGN KEY (`fuel_station_id`) REFERENCES `fuel_stations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `energy_repayments_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `energy_repayments`
--

LOCK TABLES `energy_repayments` WRITE;
/*!40000 ALTER TABLE `energy_repayments` DISABLE KEYS */;
/*!40000 ALTER TABLE `energy_repayments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `energy_subscription_repayments`
--

DROP TABLE IF EXISTS `energy_subscription_repayments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `energy_subscription_repayments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `energy_subscription_id` bigint(20) unsigned NOT NULL,
  `energy_project_id` bigint(20) unsigned NOT NULL,
  `fuel_station_id` bigint(20) unsigned NOT NULL,
  `merchant_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `due_date` date NOT NULL,
  `paid_at` datetime DEFAULT NULL,
  `status` enum('pending','paid','overdue','defaulted') NOT NULL DEFAULT 'pending',
  `payment_method` varchar(255) DEFAULT NULL,
  `transaction_reference` varchar(255) DEFAULT NULL,
  `platform_fee_amount` decimal(12,2) DEFAULT NULL,
  `net_amount` decimal(12,2) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `energy_subscription_repayments_merchant_id_foreign` (`merchant_id`),
  KEY `esr_sub_status_idx` (`energy_subscription_id`,`status`),
  KEY `esr_project_status_idx` (`energy_project_id`,`status`),
  KEY `esr_station_status_idx` (`fuel_station_id`,`status`),
  KEY `esr_user_status_idx` (`user_id`,`status`),
  KEY `esr_due_idx` (`due_date`),
  KEY `esr_paid_idx` (`paid_at`),
  CONSTRAINT `energy_subscription_repayments_energy_project_id_foreign` FOREIGN KEY (`energy_project_id`) REFERENCES `energy_projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `energy_subscription_repayments_energy_subscription_id_foreign` FOREIGN KEY (`energy_subscription_id`) REFERENCES `energy_subscriptions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `energy_subscription_repayments_fuel_station_id_foreign` FOREIGN KEY (`fuel_station_id`) REFERENCES `fuel_stations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `energy_subscription_repayments_merchant_id_foreign` FOREIGN KEY (`merchant_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `energy_subscription_repayments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `energy_subscription_repayments`
--

LOCK TABLES `energy_subscription_repayments` WRITE;
/*!40000 ALTER TABLE `energy_subscription_repayments` DISABLE KEYS */;
/*!40000 ALTER TABLE `energy_subscription_repayments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `energy_subscriptions`
--

DROP TABLE IF EXISTS `energy_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `energy_subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `energy_project_id` bigint(20) unsigned NOT NULL,
  `fuel_station_id` bigint(20) unsigned NOT NULL,
  `merchant_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `principal_amount` decimal(12,2) NOT NULL,
  `interest_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `interest_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL,
  `term_months` int(10) unsigned NOT NULL,
  `monthly_payment` decimal(12,2) NOT NULL,
  `platform_fee_rate` decimal(5,2) NOT NULL DEFAULT 2.00,
  `platform_fee_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending_approval','approved','active','completed','cancelled','rejected','defaulted') NOT NULL DEFAULT 'pending_approval',
  `requested_at` timestamp NULL DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `activated_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `defaulted_at` timestamp NULL DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `energy_subscriptions_merchant_id_foreign` (`merchant_id`),
  KEY `energy_subscriptions_approved_by_foreign` (`approved_by`),
  KEY `esub_project_status_idx` (`energy_project_id`,`status`),
  KEY `esub_station_status_idx` (`fuel_station_id`,`status`),
  KEY `esub_user_status_idx` (`user_id`,`status`),
  CONSTRAINT `energy_subscriptions_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `energy_subscriptions_energy_project_id_foreign` FOREIGN KEY (`energy_project_id`) REFERENCES `energy_projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `energy_subscriptions_fuel_station_id_foreign` FOREIGN KEY (`fuel_station_id`) REFERENCES `fuel_stations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `energy_subscriptions_merchant_id_foreign` FOREIGN KEY (`merchant_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `energy_subscriptions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `energy_subscriptions`
--

LOCK TABLES `energy_subscriptions` WRITE;
/*!40000 ALTER TABLE `energy_subscriptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `energy_subscriptions` ENABLE KEYS */;
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
-- Table structure for table `fuel_station_prices`
--

DROP TABLE IF EXISTS `fuel_station_prices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fuel_station_prices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fuel_station_id` bigint(20) unsigned NOT NULL,
  `fuel_type` enum('petrol','diesel','super') NOT NULL,
  `price_per_liter` decimal(10,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'ZAR',
  `effective_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fuel_station_prices_fuel_station_id_fuel_type_unique` (`fuel_station_id`,`fuel_type`),
  KEY `fuel_station_prices_fuel_station_id_fuel_type_index` (`fuel_station_id`,`fuel_type`),
  CONSTRAINT `fuel_station_prices_fuel_station_id_foreign` FOREIGN KEY (`fuel_station_id`) REFERENCES `fuel_stations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fuel_station_prices`
--

LOCK TABLES `fuel_station_prices` WRITE;
/*!40000 ALTER TABLE `fuel_station_prices` DISABLE KEYS */;
/*!40000 ALTER TABLE `fuel_station_prices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fuel_station_services`
--

DROP TABLE IF EXISTS `fuel_station_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fuel_station_services` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fuel_station_id` bigint(20) unsigned NOT NULL,
  `service_key` varchar(255) NOT NULL,
  `service_label` varchar(255) NOT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fuel_station_services_fuel_station_id_service_key_unique` (`fuel_station_id`,`service_key`),
  KEY `fuel_station_services_fuel_station_id_service_key_index` (`fuel_station_id`,`service_key`),
  CONSTRAINT `fuel_station_services_fuel_station_id_foreign` FOREIGN KEY (`fuel_station_id`) REFERENCES `fuel_stations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fuel_station_services`
--

LOCK TABLES `fuel_station_services` WRITE;
/*!40000 ALTER TABLE `fuel_station_services` DISABLE KEYS */;
/*!40000 ALTER TABLE `fuel_station_services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fuel_stations`
--

DROP TABLE IF EXISTS `fuel_stations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fuel_stations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `company` varchar(255) NOT NULL,
  `license_number` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `city` varchar(255) NOT NULL,
  `country` varchar(255) NOT NULL DEFAULT 'South Africa',
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `contact_person` varchar(255) NOT NULL,
  `contact_phone` varchar(255) NOT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `owner_id` bigint(20) unsigned DEFAULT NULL,
  `wallet_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_settlements` decimal(15,2) NOT NULL DEFAULT 0.00,
  `payout_method` varchar(255) NOT NULL DEFAULT 'bank_transfer',
  `payout_bank_name` varchar(255) DEFAULT NULL,
  `payout_bank_code` varchar(255) DEFAULT NULL,
  `payout_account_name` varchar(255) DEFAULT NULL,
  `payout_account_number` varchar(255) DEFAULT NULL,
  `payout_branch_code` varchar(255) DEFAULT NULL,
  `payout_reference` varchar(255) DEFAULT NULL,
  `payout_email` varchar(255) DEFAULT NULL,
  `payout_recipient_code` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fuel_stations_license_number_unique` (`license_number`),
  KEY `fuel_stations_owner_id_foreign` (`owner_id`),
  KEY `fuel_stations_status_index` (`status`),
  KEY `fuel_stations_latitude_longitude_index` (`latitude`,`longitude`),
  KEY `fuel_stations_company_index` (`company`),
  CONSTRAINT `fuel_stations_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fuel_stations`
--

LOCK TABLES `fuel_stations` WRITE;
/*!40000 ALTER TABLE `fuel_stations` DISABLE KEYS */;
/*!40000 ALTER TABLE `fuel_stations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fuel_vouchers`
--

DROP TABLE IF EXISTS `fuel_vouchers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fuel_vouchers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL,
  `qr_code` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `fuel_station_id` bigint(20) unsigned NOT NULL,
  `lease_id` bigint(20) unsigned DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `liters` decimal(8,3) NOT NULL,
  `fuel_type` enum('petrol','diesel','super') NOT NULL,
  `status` enum('issued','approved','redeemed','expired','cancelled') NOT NULL DEFAULT 'issued',
  `issued_at` datetime NOT NULL DEFAULT current_timestamp(),
  `redeemed_at` datetime DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `settlement_id` bigint(20) unsigned DEFAULT NULL,
  `settled_at` datetime DEFAULT NULL,
  `transaction_reference` varchar(255) DEFAULT NULL,
  `pump_number` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fuel_vouchers_code_unique` (`code`),
  UNIQUE KEY `fuel_vouchers_qr_code_unique` (`qr_code`),
  KEY `fuel_vouchers_lease_id_foreign` (`lease_id`),
  KEY `fuel_vouchers_settlement_id_foreign` (`settlement_id`),
  KEY `fuel_vouchers_user_id_status_index` (`user_id`,`status`),
  KEY `fuel_vouchers_fuel_station_id_status_index` (`fuel_station_id`,`status`),
  KEY `fuel_vouchers_expires_at_index` (`expires_at`),
  KEY `fuel_vouchers_redeemed_at_index` (`redeemed_at`),
  CONSTRAINT `fuel_vouchers_fuel_station_id_foreign` FOREIGN KEY (`fuel_station_id`) REFERENCES `fuel_stations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fuel_vouchers_lease_id_foreign` FOREIGN KEY (`lease_id`) REFERENCES `leases` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fuel_vouchers_settlement_id_foreign` FOREIGN KEY (`settlement_id`) REFERENCES `settlements` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fuel_vouchers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fuel_vouchers`
--

LOCK TABLES `fuel_vouchers` WRITE;
/*!40000 ALTER TABLE `fuel_vouchers` DISABLE KEYS */;
/*!40000 ALTER TABLE `fuel_vouchers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `investor_documents`
--

DROP TABLE IF EXISTS `investor_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `investor_documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `investor_id` bigint(20) unsigned NOT NULL,
  `document_type` enum('registration_certificate','tax_certificate','license_certificate','environmental_clearance','safety_certificate','financial_statement','bank_reference','director_ids','proof_of_address','other') NOT NULL,
  `document_path` varchar(255) NOT NULL,
  `document_name` varchar(255) NOT NULL,
  `document_number` varchar(255) DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `verified` tinyint(1) NOT NULL DEFAULT 0,
  `verified_by` bigint(20) unsigned DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `investor_documents_verified_by_foreign` (`verified_by`),
  KEY `investor_documents_investor_id_document_type_index` (`investor_id`,`document_type`),
  CONSTRAINT `investor_documents_investor_id_foreign` FOREIGN KEY (`investor_id`) REFERENCES `investors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `investor_documents_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `investor_documents`
--

LOCK TABLES `investor_documents` WRITE;
/*!40000 ALTER TABLE `investor_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `investor_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `investors`
--

DROP TABLE IF EXISTS `investors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `investors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_name` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `registration_number` varchar(255) NOT NULL,
  `tax_id` varchar(255) DEFAULT NULL,
  `contact_person` varchar(255) NOT NULL,
  `contact_email` varchar(255) NOT NULL,
  `contact_phone` varchar(255) NOT NULL,
  `company_address` text NOT NULL,
  `city` varchar(255) NOT NULL,
  `country` varchar(255) NOT NULL,
  `total_investment_capital` decimal(15,2) NOT NULL DEFAULT 0.00,
  `available_capital` decimal(15,2) NOT NULL DEFAULT 0.00,
  `invested_capital` decimal(15,2) NOT NULL DEFAULT 0.00,
  `interest_earned` decimal(15,2) NOT NULL DEFAULT 0.00,
  `risk_profile` enum('conservative','moderate','aggressive') NOT NULL,
  `minimum_investment_amount` decimal(15,2) NOT NULL DEFAULT 1000.00,
  `maximum_investment_amount` decimal(15,2) NOT NULL DEFAULT 100000.00,
  `preferred_interest_rate_min` decimal(5,2) NOT NULL DEFAULT 5.00,
  `preferred_interest_rate_max` decimal(5,2) NOT NULL DEFAULT 25.00,
  `investment_horizon` enum('short_term','medium_term','long_term') NOT NULL,
  `status` enum('active','pending_approval','suspended') NOT NULL DEFAULT 'pending_approval',
  `credit_score` int(11) NOT NULL DEFAULT 500,
  `investor_score` int(11) NOT NULL DEFAULT 0,
  `auto_invest_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `powerbi_export_token` varchar(100) DEFAULT NULL,
  `powerbi_export_expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `investors_registration_number_unique` (`registration_number`),
  UNIQUE KEY `investors_powerbi_export_token_unique` (`powerbi_export_token`),
  KEY `investors_user_id_foreign` (`user_id`),
  CONSTRAINT `investors_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `investors`
--

LOCK TABLES `investors` WRITE;
/*!40000 ALTER TABLE `investors` DISABLE KEYS */;
/*!40000 ALTER TABLE `investors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lease_investments`
--

DROP TABLE IF EXISTS `lease_investments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lease_investments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `lease_id` bigint(20) unsigned NOT NULL,
  `investor_id` bigint(20) unsigned NOT NULL,
  `amount_invested` decimal(15,2) NOT NULL,
  `percentage_ownership` decimal(5,2) NOT NULL,
  `interest_rate` decimal(5,2) NOT NULL,
  `expected_interest` decimal(15,2) NOT NULL,
  `interest_earned` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('active','completed','defaulted','cancelled') NOT NULL DEFAULT 'active',
  `investment_date` datetime NOT NULL,
  `maturity_date` datetime NOT NULL,
  `expected_maturity_date` datetime NOT NULL,
  `actual_maturity_date` datetime DEFAULT NULL,
  `return_on_investment` decimal(5,2) NOT NULL DEFAULT 0.00,
  `payment_schedule` enum('daily','weekly','monthly') NOT NULL DEFAULT 'daily',
  `auto_reinvest` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lease_investments_lease_id_foreign` (`lease_id`),
  KEY `lease_investments_investor_id_foreign` (`investor_id`),
  CONSTRAINT `lease_investments_investor_id_foreign` FOREIGN KEY (`investor_id`) REFERENCES `investors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lease_investments_lease_id_foreign` FOREIGN KEY (`lease_id`) REFERENCES `leases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lease_investments`
--

LOCK TABLES `lease_investments` WRITE;
/*!40000 ALTER TABLE `lease_investments` DISABLE KEYS */;
/*!40000 ALTER TABLE `lease_investments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leases`
--

DROP TABLE IF EXISTS `leases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leases` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `principal_amount` decimal(15,2) NOT NULL,
  `interest_rate` decimal(5,2) NOT NULL,
  `interest_amount` decimal(15,2) NOT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `term_days` int(11) NOT NULL,
  `daily_repayment` decimal(15,2) NOT NULL,
  `status` enum('active','completed','defaulted','cancelled') NOT NULL DEFAULT 'active',
  `issued_at` datetime NOT NULL DEFAULT current_timestamp(),
  `due_date` date NOT NULL,
  `completed_at` datetime DEFAULT NULL,
  `defaulted_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `leases_user_id_status_index` (`user_id`,`status`),
  KEY `leases_due_date_index` (`due_date`),
  KEY `leases_status_due_date_index` (`status`,`due_date`),
  CONSTRAINT `leases_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leases`
--

LOCK TABLES `leases` WRITE;
/*!40000 ALTER TABLE `leases` DISABLE KEYS */;
/*!40000 ALTER TABLE `leases` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `merchant_device_keys`
--

DROP TABLE IF EXISTS `merchant_device_keys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `merchant_device_keys` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `key_id` varchar(40) NOT NULL,
  `secret_encrypted` text NOT NULL,
  `label` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `last_used_at` timestamp NULL DEFAULT NULL,
  `last_used_ip` varchar(64) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `merchant_device_keys_key_id_unique` (`key_id`),
  KEY `merchant_device_keys_user_id_status_index` (`user_id`,`status`),
  CONSTRAINT `merchant_device_keys_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `merchant_device_keys`
--

LOCK TABLES `merchant_device_keys` WRITE;
/*!40000 ALTER TABLE `merchant_device_keys` DISABLE KEYS */;
/*!40000 ALTER TABLE `merchant_device_keys` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2014_10_12_000000_create_users_table',1),(2,'2014_10_12_100000_create_password_reset_tokens_table',1),(3,'2019_08_19_000000_create_failed_jobs_table',1),(4,'2019_12_14_000001_create_personal_access_tokens_table',1),(5,'2024_01_01_000000_create_fuel_bnpl_tables',1),(6,'2024_01_01_000013_create_cache_table',1),(7,'2024_01_01_000013_create_sessions_table',1),(8,'2026_01_29_181541_create_permission_tables',1),(9,'2026_02_03_115706_create_investors_table',1),(10,'2026_02_03_115801_create_lease_investments_table',1),(11,'2026_02_03_115837_create_investor_documents_table',1),(12,'2026_02_04_200000_create_payments_table',1),(13,'2026_02_04_210000_add_payout_fields_to_fuel_stations_table',1),(14,'2026_02_04_220000_add_settled_at_to_fuel_vouchers_table',1),(15,'2026_02_04_230000_create_fuel_station_prices_table',1),(16,'2026_02_04_231000_create_fuel_station_services_table',1),(17,'2026_02_04_232000_add_powerbi_export_token_to_investors_table',1),(18,'2026_02_04_233000_create_settings_table',1),(19,'2026_02_05_000001_add_approved_status_to_fuel_vouchers_table',1),(20,'2026_02_06_000001_create_driver_documents_table',1),(21,'2026_02_06_120000_add_peach_fields_to_users_table',1),(22,'2026_02_06_130000_add_autopay_fields_to_users_table',1),(23,'2026_02_06_130001_add_autopay_fields_to_repayments_table',1),(24,'2026_02_06_140000_create_bank_statement_uploads_table',1),(25,'2026_02_06_140100_create_bank_statement_accounts_table',1),(26,'2026_02_06_140200_create_bank_statement_transactions_table',1),(27,'2026_02_06_140300_create_bank_statement_features_table',1),(28,'2026_02_06_140400_create_credit_decisions_table',1),(29,'2026_02_06_170000_add_autopay_metadata_to_users_table',1),(30,'2026_02_06_220000_add_notification_settings_to_users_table',1),(31,'2026_02_07_000000_add_approved_by_to_settlements_table',1),(32,'2026_02_08_200000_create_chat_threads_table',1),(33,'2026_02_08_200100_create_chat_messages_table',1),(34,'2026_02_09_120000_add_stream_message_id_to_chat_messages_table',1),(35,'2026_02_09_150000_add_checkid_fields_to_users_table',1),(36,'2026_02_09_170000_add_payout_recipient_code_to_fuel_stations_table',1),(37,'2026_02_09_173000_add_payout_bank_code_to_fuel_stations_table',1),(38,'2026_02_09_180000_create_energy_assets_table',1),(39,'2026_02_09_180100_create_energy_projects_table',1),(40,'2026_02_09_180200_create_energy_readings_table',1),(41,'2026_02_09_181000_create_energy_repayments_table',1),(42,'2026_02_09_182000_create_energy_subscriptions_table',1),(43,'2026_02_09_182100_create_energy_subscription_repayments_table',1),(44,'2026_02_10_120000_add_asset_cost_to_energy_assets_table',1),(45,'2026_02_10_230000_add_pump_number_to_fuel_vouchers_table',1),(46,'2026_02_11_120000_add_oauth_fields_to_users_table',1),(47,'2026_02_11_130000_create_credit_domain_tables',1),(48,'2026_02_11_130100_alter_credit_decisions_add_policy_fields',1),(49,'2026_02_12_000100_create_developer_api_tokens_table',1),(50,'2026_02_12_140000_add_registration_artifact_fields_to_users_table',1),(51,'2026_02_12_190000_add_uber_driver_fields_to_users_table',1),(52,'2026_02_16_060000_add_bolt_driver_fields_to_users_table',1),(53,'2026_02_16_210000_create_shell_driver_profiles_table',1),(54,'2026_02_16_220000_create_shell_transaction_snapshots_table',1),(55,'2026_02_20_000001_create_nfc_cards_table',1),(56,'2026_02_20_000002_create_nfc_tap_events_table',1),(57,'2026_02_20_000003_create_merchant_device_keys_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_permissions`
--

LOCK TABLES `model_has_permissions` WRITE;
/*!40000 ALTER TABLE `model_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `model_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_roles`
--

LOCK TABLES `model_has_roles` WRITE;
/*!40000 ALTER TABLE `model_has_roles` DISABLE KEYS */;
INSERT INTO `model_has_roles` VALUES (3,'App\\Models\\User',12);
/*!40000 ALTER TABLE `model_has_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nfc_cards`
--

DROP TABLE IF EXISTS `nfc_cards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nfc_cards` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `token` varchar(32) NOT NULL,
  `uid_hash` varchar(64) NOT NULL,
  `label` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `bound_at` timestamp NULL DEFAULT NULL,
  `last_tapped_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nfc_cards_token_unique` (`token`),
  UNIQUE KEY `nfc_cards_uid_hash_unique` (`uid_hash`),
  KEY `nfc_cards_user_id_status_index` (`user_id`,`status`),
  CONSTRAINT `nfc_cards_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nfc_cards`
--

LOCK TABLES `nfc_cards` WRITE;
/*!40000 ALTER TABLE `nfc_cards` DISABLE KEYS */;
/*!40000 ALTER TABLE `nfc_cards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nfc_tap_events`
--

DROP TABLE IF EXISTS `nfc_tap_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nfc_tap_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nfc_card_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `fuel_station_id` bigint(20) unsigned DEFAULT NULL,
  `merchant_user_id` bigint(20) unsigned DEFAULT NULL,
  `approved_voucher_id` bigint(20) unsigned DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `liters` decimal(10,3) DEFAULT NULL,
  `fuel_type` varchar(20) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'declined',
  `decline_reason` varchar(255) DEFAULT NULL,
  `transaction_reference` varchar(255) DEFAULT NULL,
  `request_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`request_payload`)),
  `response_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`response_payload`)),
  `tapped_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `nfc_tap_events_nfc_card_id_foreign` (`nfc_card_id`),
  KEY `nfc_tap_events_merchant_user_id_foreign` (`merchant_user_id`),
  KEY `nfc_tap_events_approved_voucher_id_foreign` (`approved_voucher_id`),
  KEY `nfc_tap_events_status_tapped_at_index` (`status`,`tapped_at`),
  KEY `nfc_tap_events_user_id_tapped_at_index` (`user_id`,`tapped_at`),
  KEY `nfc_tap_events_fuel_station_id_tapped_at_index` (`fuel_station_id`,`tapped_at`),
  CONSTRAINT `nfc_tap_events_approved_voucher_id_foreign` FOREIGN KEY (`approved_voucher_id`) REFERENCES `fuel_vouchers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nfc_tap_events_fuel_station_id_foreign` FOREIGN KEY (`fuel_station_id`) REFERENCES `fuel_stations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nfc_tap_events_merchant_user_id_foreign` FOREIGN KEY (`merchant_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nfc_tap_events_nfc_card_id_foreign` FOREIGN KEY (`nfc_card_id`) REFERENCES `nfc_cards` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nfc_tap_events_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nfc_tap_events`
--

LOCK TABLES `nfc_tap_events` WRITE;
/*!40000 ALTER TABLE `nfc_tap_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `nfc_tap_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `otps`
--

DROP TABLE IF EXISTS `otps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `otps` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `phone` varchar(255) NOT NULL,
  `code` varchar(6) NOT NULL,
  `purpose` enum('registration','login','reset_password','transaction') NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `otps_user_id_foreign` (`user_id`),
  KEY `otps_phone_code_used_index` (`phone`,`code`,`used`),
  KEY `otps_expires_at_index` (`expires_at`),
  CONSTRAINT `otps_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `otps`
--

LOCK TABLES `otps` WRITE;
/*!40000 ALTER TABLE `otps` DISABLE KEYS */;
/*!40000 ALTER TABLE `otps` ENABLE KEYS */;
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
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `type` varchar(255) NOT NULL,
  `payable_type` varchar(255) DEFAULT NULL,
  `payable_id` bigint(20) unsigned DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'ZAR',
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `gateway` varchar(255) NOT NULL DEFAULT 'payfast',
  `merchant_reference` varchar(255) NOT NULL,
  `pf_payment_id` varchar(255) DEFAULT NULL,
  `pf_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`pf_data`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payments_merchant_reference_unique` (`merchant_reference`),
  KEY `payments_user_id_foreign` (`user_id`),
  KEY `payments_payable_type_payable_id_index` (`payable_type`,`payable_id`),
  KEY `payments_status_gateway_index` (`status`,`gateway`),
  KEY `payments_type_payable_type_payable_id_index` (`type`,`payable_type`,`payable_id`),
  CONSTRAINT `payments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `category` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `repayments`
--

DROP TABLE IF EXISTS `repayments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `repayments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `lease_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `due_date` date NOT NULL,
  `paid_at` date DEFAULT NULL,
  `status` enum('pending','paid','overdue','defaulted') NOT NULL DEFAULT 'pending',
  `payment_method` varchar(255) DEFAULT NULL,
  `transaction_reference` varchar(255) DEFAULT NULL,
  `autopay_attempts` int(10) unsigned NOT NULL DEFAULT 0,
  `autopay_last_attempt_at` timestamp NULL DEFAULT NULL,
  `autopay_next_attempt_at` timestamp NULL DEFAULT NULL,
  `autopay_status` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `repayments_lease_id_status_index` (`lease_id`,`status`),
  KEY `repayments_user_id_status_index` (`user_id`,`status`),
  KEY `repayments_due_date_index` (`due_date`),
  KEY `repayments_paid_at_index` (`paid_at`),
  CONSTRAINT `repayments_lease_id_foreign` FOREIGN KEY (`lease_id`) REFERENCES `leases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `repayments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `repayments`
--

LOCK TABLES `repayments` WRITE;
/*!40000 ALTER TABLE `repayments` DISABLE KEYS */;
/*!40000 ALTER TABLE `repayments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_has_permissions`
--

LOCK TABLES `role_has_permissions` WRITE;
/*!40000 ALTER TABLE `role_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `role_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (3,'driver','web',NULL,'2026-02-21 06:53:12','2026-02-21 06:53:12');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
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
INSERT INTO `sessions` VALUES ('Cetbr3pwVofVbLy252IHXA6WgmWRoiyGzDXn8DWN',NULL,'127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiZXd5c2JoOUh6alZocGhUWFo5MXdiZE02YWdxT2tGODBWZFdTWDRMUSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMCI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1771664052),('VGo4JLU12aCbrlL3Oif5y2Sl3DkFXXbcijIgB08t',NULL,'127.0.0.1','Symfony','YTozOntzOjY6Il90b2tlbiI7czo0MDoiQkowc1lzWkJFMFNjWGttRGFoUm5PNGpONTFNNjBQS2NjNkFjc0pIbiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMCI7czo1OiJyb3V0ZSI7czoyNzoiZ2VuZXJhdGVkOjpGejdzWmMwTmo3cEJEVHQwIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1771660098);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `group` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settlements`
--

DROP TABLE IF EXISTS `settlements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settlements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fuel_station_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `voucher_count` int(11) NOT NULL,
  `status` enum('pending','processing','completed','failed') NOT NULL DEFAULT 'pending',
  `reference` varchar(255) NOT NULL,
  `settlement_date` date NOT NULL,
  `processed_at` datetime DEFAULT NULL,
  `payment_method` varchar(255) NOT NULL DEFAULT 'bank_transfer',
  `transaction_reference` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settlements_reference_unique` (`reference`),
  KEY `settlements_fuel_station_id_status_index` (`fuel_station_id`,`status`),
  KEY `settlements_settlement_date_index` (`settlement_date`),
  KEY `settlements_reference_index` (`reference`),
  KEY `settlements_approved_by_foreign` (`approved_by`),
  CONSTRAINT `settlements_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `settlements_fuel_station_id_foreign` FOREIGN KEY (`fuel_station_id`) REFERENCES `fuel_stations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settlements`
--

LOCK TABLES `settlements` WRITE;
/*!40000 ALTER TABLE `settlements` DISABLE KEYS */;
/*!40000 ALTER TABLE `settlements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shell_driver_profiles`
--

DROP TABLE IF EXISTS `shell_driver_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shell_driver_profiles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `shell_account_id` varchar(255) DEFAULT NULL,
  `shell_card_id` varchar(255) DEFAULT NULL,
  `shell_vehicle_id` varchar(255) DEFAULT NULL,
  `shell_customer_code` varchar(255) DEFAULT NULL,
  `shell_cost_center` varchar(255) DEFAULT NULL,
  `shell_status` varchar(255) DEFAULT NULL,
  `last_synced_at` timestamp NULL DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shell_driver_profiles_user_id_unique` (`user_id`),
  KEY `shell_driver_profiles_shell_account_id_index` (`shell_account_id`),
  KEY `shell_driver_profiles_shell_card_id_index` (`shell_card_id`),
  CONSTRAINT `shell_driver_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shell_driver_profiles`
--

LOCK TABLES `shell_driver_profiles` WRITE;
/*!40000 ALTER TABLE `shell_driver_profiles` DISABLE KEYS */;
/*!40000 ALTER TABLE `shell_driver_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shell_transaction_snapshots`
--

DROP TABLE IF EXISTS `shell_transaction_snapshots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shell_transaction_snapshots` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `shell_driver_profile_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `external_transaction_id` varchar(191) NOT NULL,
  `occurred_at` timestamp NULL DEFAULT NULL,
  `merchant_name` varchar(255) DEFAULT NULL,
  `station_name` varchar(255) DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `card_id` varchar(255) DEFAULT NULL,
  `vehicle_id` varchar(255) DEFAULT NULL,
  `currency` varchar(8) DEFAULT NULL,
  `amount` decimal(14,2) DEFAULT NULL,
  `source_endpoint` varchar(255) DEFAULT NULL,
  `raw_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`raw_payload`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shell_tx_profile_external_unique` (`shell_driver_profile_id`,`external_transaction_id`),
  KEY `shell_transaction_snapshots_user_id_occurred_at_index` (`user_id`,`occurred_at`),
  CONSTRAINT `shell_transaction_snapshots_shell_driver_profile_id_foreign` FOREIGN KEY (`shell_driver_profile_id`) REFERENCES `shell_driver_profiles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shell_transaction_snapshots_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shell_transaction_snapshots`
--

LOCK TABLES `shell_transaction_snapshots` WRITE;
/*!40000 ALTER TABLE `shell_transaction_snapshots` DISABLE KEYS */;
/*!40000 ALTER TABLE `shell_transaction_snapshots` ENABLE KEYS */;
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
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) NOT NULL,
  `id_number` varchar(20) DEFAULT NULL,
  `id_document_path` varchar(255) DEFAULT NULL,
  `driver_license_path` varchar(255) DEFAULT NULL,
  `bank_statement_path` varchar(255) DEFAULT NULL,
  `payment_method_preference` varchar(50) DEFAULT NULL,
  `payment_account_name` varchar(255) DEFAULT NULL,
  `payment_account_number` varchar(255) DEFAULT NULL,
  `payment_bank_name` varchar(255) DEFAULT NULL,
  `payment_branch_code` varchar(255) DEFAULT NULL,
  `id_verification_status` varchar(255) NOT NULL DEFAULT 'unverified',
  `id_verified_at` timestamp NULL DEFAULT NULL,
  `id_verification_provider` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `oauth_provider` varchar(255) DEFAULT NULL,
  `oauth_provider_id` varchar(255) DEFAULT NULL,
  `uber_driver_id` varchar(255) DEFAULT NULL,
  `uber_verified_at` timestamp NULL DEFAULT NULL,
  `uber_trip_count` int(10) unsigned DEFAULT NULL,
  `uber_rating` decimal(3,2) DEFAULT NULL,
  `uber_activation_status` varchar(255) DEFAULT NULL,
  `uber_scope` varchar(255) DEFAULT NULL,
  `uber_last_synced_at` timestamp NULL DEFAULT NULL,
  `bolt_driver_id` varchar(255) DEFAULT NULL,
  `bolt_verified_at` timestamp NULL DEFAULT NULL,
  `bolt_trip_count` int(10) unsigned DEFAULT NULL,
  `bolt_rating` decimal(3,2) DEFAULT NULL,
  `bolt_activation_status` varchar(255) DEFAULT NULL,
  `bolt_scope` varchar(255) DEFAULT NULL,
  `bolt_last_synced_at` timestamp NULL DEFAULT NULL,
  `avatar_url` text DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `device_fingerprint` varchar(255) DEFAULT NULL,
  `credit_score` int(11) NOT NULL DEFAULT 500,
  `status` enum('active','suspended','flagged','blocked') NOT NULL DEFAULT 'active',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `peach_registration_id` varchar(255) DEFAULT NULL,
  `peach_autopay_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `peach_autopay_status` varchar(255) DEFAULT NULL,
  `peach_last_charge_at` timestamp NULL DEFAULT NULL,
  `peach_next_charge_at` timestamp NULL DEFAULT NULL,
  `autopay_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `autopay_gateway` varchar(255) DEFAULT NULL,
  `autopay_token` varchar(255) DEFAULT NULL,
  `autopay_email` varchar(255) DEFAULT NULL,
  `autopay_customer_code` varchar(255) DEFAULT NULL,
  `autopay_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`autopay_details`)),
  `autopay_status` varchar(255) DEFAULT NULL,
  `autopay_failures` int(10) unsigned NOT NULL DEFAULT 0,
  `autopay_last_attempt_at` timestamp NULL DEFAULT NULL,
  `autopay_next_attempt_at` timestamp NULL DEFAULT NULL,
  `notification_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`notification_settings`)),
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_phone_unique` (`phone`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_id_number_unique` (`id_number`),
  UNIQUE KEY `users_oauth_provider_oauth_provider_id_unique` (`oauth_provider`,`oauth_provider_id`),
  KEY `users_phone_status_index` (`phone`,`status`),
  KEY `users_credit_score_index` (`credit_score`),
  KEY `users_uber_driver_id_index` (`uber_driver_id`),
  KEY `users_bolt_driver_id_index` (`bolt_driver_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (12,'Tlhologelo Mabitle','tlhologelo.mabitle3@gmail.com','google_100038240256804439469',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'unverified',NULL,NULL,NULL,'google','100038240256804439469',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'https://lh3.googleusercontent.com/a/ACg8ocKvl0h0hvvi5iFEh-ErklOH6siounAkpQmjEVadOMdJa2PgASHf=s96-c','$2y$12$4QrOCAeVicFFaw5aLAYqb.tzg6e7ym0siDRppbaYI8robGFHe02Zu',NULL,500,'active','2026-02-21 06:53:12','127.0.0.1',NULL,0,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'H5YLHyM1kwBqwoPNiJPuXjJpRIwtIastxR8oFXERklnNomml1AAwh3vJzrR5','2026-02-21 06:53:12','2026-02-21 06:53:12',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wallet_transactions`
--

DROP TABLE IF EXISTS `wallet_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wallet_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `wallet_id` bigint(20) unsigned NOT NULL,
  `type` enum('credit','debit') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `balance_before` decimal(15,2) NOT NULL,
  `balance_after` decimal(15,2) NOT NULL,
  `description` varchar(255) NOT NULL,
  `reference` varchar(255) NOT NULL,
  `status` enum('pending','completed','failed','reversed') NOT NULL DEFAULT 'pending',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wallet_transactions_reference_unique` (`reference`),
  KEY `wallet_transactions_wallet_id_type_index` (`wallet_id`,`type`),
  KEY `wallet_transactions_reference_index` (`reference`),
  KEY `wallet_transactions_created_at_index` (`created_at`),
  CONSTRAINT `wallet_transactions_wallet_id_foreign` FOREIGN KEY (`wallet_id`) REFERENCES `wallets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wallet_transactions`
--

LOCK TABLES `wallet_transactions` WRITE;
/*!40000 ALTER TABLE `wallet_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `wallet_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wallets`
--

DROP TABLE IF EXISTS `wallets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wallets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `outstanding_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_credit_used` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_repayments` decimal(15,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(3) NOT NULL DEFAULT 'KES',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wallets_user_id_unique` (`user_id`),
  KEY `wallets_outstanding_balance_index` (`outstanding_balance`),
  CONSTRAINT `wallets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wallets`
--

LOCK TABLES `wallets` WRITE;
/*!40000 ALTER TABLE `wallets` DISABLE KEYS */;
/*!40000 ALTER TABLE `wallets` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-02-21 10:59:00
