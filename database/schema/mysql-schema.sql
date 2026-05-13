/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
DROP TABLE IF EXISTS `bp_fueling_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bp_fueling_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `bp_payment_method_id` bigint(20) unsigned DEFAULT NULL,
  `fuel_voucher_id` bigint(20) unsigned DEFAULT NULL,
  `provider_transaction_id` varchar(255) DEFAULT NULL,
  `provider_reference` varchar(255) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `site_id` varchar(255) DEFAULT NULL,
  `site_name` varchar(255) DEFAULT NULL,
  `pump_id` varchar(255) DEFAULT NULL,
  `nozzle_id` varchar(255) DEFAULT NULL,
  `amount` decimal(12,2) DEFAULT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'ZAR',
  `liters` decimal(10,3) DEFAULT NULL,
  `fuel_grade` varchar(255) DEFAULT NULL,
  `receipt_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`receipt_data`)),
  `request_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`request_payload`)),
  `response_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`response_payload`)),
  `provider_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`provider_payload`)),
  `authorized_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bp_fueling_sessions_bp_payment_method_id_foreign` (`bp_payment_method_id`),
  KEY `bp_fueling_sessions_fuel_voucher_id_foreign` (`fuel_voucher_id`),
  KEY `bp_fueling_sessions_user_id_status_index` (`user_id`,`status`),
  KEY `bp_fueling_sessions_provider_transaction_id_index` (`provider_transaction_id`),
  KEY `bp_fueling_sessions_provider_reference_index` (`provider_reference`),
  KEY `bp_fueling_sessions_status_index` (`status`),
  KEY `bp_fueling_sessions_site_id_index` (`site_id`),
  CONSTRAINT `bp_fueling_sessions_bp_payment_method_id_foreign` FOREIGN KEY (`bp_payment_method_id`) REFERENCES `bp_payment_methods` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bp_fueling_sessions_fuel_voucher_id_foreign` FOREIGN KEY (`fuel_voucher_id`) REFERENCES `fuel_vouchers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bp_fueling_sessions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bp_payment_methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bp_payment_methods` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `provider_method_id` varchar(255) DEFAULT NULL,
  `provider_token` varchar(255) DEFAULT NULL,
  `label` varchar(255) DEFAULT NULL,
  `card_brand` varchar(255) DEFAULT NULL,
  `card_last4` varchar(4) DEFAULT NULL,
  `vehicle_registration` varchar(255) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bp_payment_methods_user_id_is_default_index` (`user_id`,`is_default`),
  KEY `bp_payment_methods_provider_method_id_index` (`provider_method_id`),
  KEY `bp_payment_methods_status_index` (`status`),
  CONSTRAINT `bp_payment_methods_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
DROP TABLE IF EXISTS `credit_decisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `credit_decisions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `upload_id` bigint(20) unsigned DEFAULT NULL,
  `score_id` bigint(20) unsigned DEFAULT NULL,
  `score` smallint(5) unsigned DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB AUTO_INCREMENT=118 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB AUTO_INCREMENT=313 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB AUTO_INCREMENT=341 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB AUTO_INCREMENT=220 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB AUTO_INCREMENT=215 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB AUTO_INCREMENT=153 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB AUTO_INCREMENT=9776 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `voucher_anomaly_checks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `voucher_anomaly_checks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fuel_voucher_id` bigint(20) unsigned NOT NULL,
  `flagged` tinyint(1) NOT NULL DEFAULT 0,
  `risk_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `confidence` decimal(5,2) DEFAULT NULL,
  `provider` varchar(32) NOT NULL DEFAULT 'openai',
  `model` varchar(100) DEFAULT NULL,
  `reasons` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`reasons`)),
  `raw_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`raw_response`)),
  `checked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `voucher_anomaly_checks_fuel_voucher_id_unique` (`fuel_voucher_id`),
  KEY `voucher_anomaly_checks_flagged_checked_at_index` (`flagged`,`checked_at`),
  CONSTRAINT `voucher_anomaly_checks_fuel_voucher_id_foreign` FOREIGN KEY (`fuel_voucher_id`) REFERENCES `fuel_vouchers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
