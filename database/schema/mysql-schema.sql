/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `avatars`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `avatars` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `category` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `part` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `matrix` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `avatars_category_unique` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `document_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `document_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `category` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `order` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `document_categories_category_unique` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `documents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `nav_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `maintainer` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `revision_history` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `published` tinyint(1) NOT NULL DEFAULT '0',
  `restricted` tinyint(1) NOT NULL DEFAULT '0',
  `order` int NOT NULL,
  `document_category_id` bigint unsigned NOT NULL,
  `rev_history` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `documents_document_category_id_foreign` (`document_category_id`),
  CONSTRAINT `documents_document_category_id_foreign` FOREIGN KEY (`document_category_id`) REFERENCES `document_categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ldraw_colours`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ldraw_colours` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` int NOT NULL,
  `value` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `edge` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `alpha` int DEFAULT NULL,
  `luminance` int DEFAULT NULL,
  `chrome` tinyint(1) NOT NULL DEFAULT '0',
  `pearlescent` tinyint(1) NOT NULL DEFAULT '0',
  `rubber` tinyint(1) NOT NULL DEFAULT '0',
  `matte_metallic` tinyint(1) NOT NULL DEFAULT '0',
  `metal` tinyint(1) NOT NULL DEFAULT '0',
  `glitter` tinyint(1) NOT NULL DEFAULT '0',
  `speckle` tinyint(1) NOT NULL DEFAULT '0',
  `material_value` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `material_alpha` int DEFAULT NULL,
  `material_luminance` int DEFAULT NULL,
  `material_fraction` double DEFAULT NULL,
  `material_vfraction` double DEFAULT NULL,
  `material_size` double DEFAULT NULL,
  `material_minsize` double DEFAULT NULL,
  `material_maxsize` double DEFAULT NULL,
  `lego_id` int DEFAULT NULL,
  `lego_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rebrickable_id` int DEFAULT NULL,
  `rebrickable_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `brickset_id` int DEFAULT NULL,
  `brickset_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fabric` tinyint(1) NOT NULL DEFAULT '0',
  `material_fabric_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ldraw_colours_name_unique` (`name`),
  UNIQUE KEY `ldraw_colours_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `media` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `model_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  `uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `collection_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `disk` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `conversions_disk` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size` bigint unsigned NOT NULL,
  `manipulations` json NOT NULL,
  `custom_properties` json NOT NULL,
  `generated_conversions` json NOT NULL,
  `responsive_images` json NOT NULL,
  `order_column` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `media_uuid_unique` (`uuid`),
  KEY `media_model_type_model_id_index` (`model_type`,`model_id`),
  KEY `media_order_column_index` (`order_column`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `omr_models`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `omr_models` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `user_id` bigint unsigned NOT NULL,
  `set_id` bigint unsigned NOT NULL,
  `missing_parts` tinyint(1) NOT NULL,
  `missing_patterns` tinyint(1) NOT NULL,
  `missing_stickers` tinyint(1) NOT NULL,
  `approved` tinyint(1) NOT NULL,
  `alt_model` tinyint(1) NOT NULL,
  `alt_model_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` json DEFAULT NULL,
  `license` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `omr_models_user_id_foreign` (`user_id`),
  KEY `omr_models_set_id_foreign` (`set_id`),
  KEY `omr_models_created_at_index` (`created_at`),
  CONSTRAINT `omr_models_set_id_foreign` FOREIGN KEY (`set_id`) REFERENCES `sets` (`id`),
  CONSTRAINT `omr_models_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pan_analytics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pan_analytics` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `impressions` bigint unsigned NOT NULL DEFAULT '0',
  `hovers` bigint unsigned NOT NULL DEFAULT '0',
  `clicks` bigint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `part_bodies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `part_bodies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `part_id` bigint unsigned NOT NULL,
  `body` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `part_bodies_part_id_foreign` (`part_id`),
  CONSTRAINT `part_bodies_part_id_foreign` FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `part_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `part_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `initial_submit` tinyint(1) DEFAULT NULL,
  `part_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned NOT NULL,
  `part_release_id` bigint unsigned DEFAULT NULL,
  `deleted_filename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deleted_description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `moved_from_filename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `moved_to_filename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `header_changes` json DEFAULT NULL,
  `vote_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `comment` mediumtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `part_events_part_release_id_foreign` (`part_release_id`),
  KEY `part_events_user_id_index` (`user_id`),
  KEY `part_events_part_id_index` (`part_id`),
  KEY `part_events_event_type_index` (`event_type`),
  KEY `part_events_vote_type_index` (`vote_type`),
  KEY `part_events_created_at_index` (`created_at`),
  CONSTRAINT `part_events_part_id_foreign` FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `part_events_part_release_id_foreign` FOREIGN KEY (`part_release_id`) REFERENCES `part_releases` (`id`),
  CONSTRAINT `part_events_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `part_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `part_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `user_id` bigint unsigned NOT NULL,
  `part_id` bigint unsigned NOT NULL,
  `comment` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `part_histories_user_id_index` (`user_id`),
  KEY `part_histories_part_id_index` (`part_id`),
  KEY `part_histories_created_at_index` (`created_at`),
  CONSTRAINT `part_histories_part_id_foreign` FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `part_histories_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `part_keywords`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `part_keywords` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `keyword` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `part_keywords_keyword_unique` (`keyword`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `part_releases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `part_releases` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `short` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '0',
  `total` int NOT NULL DEFAULT '0',
  `new` int NOT NULL DEFAULT '0',
  `new_of_type` json NOT NULL,
  PRIMARY KEY (`id`),
  KEY `part_releases_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `parts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `parts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `user_id` bigint unsigned NOT NULL,
  `part_release_id` bigint unsigned DEFAULT NULL,
  `filename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `header` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `unofficial_part_id` bigint unsigned DEFAULT NULL,
  `part_status` int NOT NULL DEFAULT '1',
  `cmdline` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bfc` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `delete_flag` tinyint(1) NOT NULL DEFAULT '0',
  `missing_parts` json DEFAULT NULL,
  `manual_hold_flag` tinyint(1) NOT NULL DEFAULT '0',
  `can_release` tinyint(1) NOT NULL DEFAULT '0',
  `part_check` json DEFAULT NULL,
  `week` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS (str_to_date(concat(yearweek(`created_at`,2),_utf8mb4' Sunday'),_utf8mb4'%X%V %W')) VIRTUAL,
  `sticker_sheet_id` bigint unsigned DEFAULT NULL,
  `marked_for_release` tinyint(1) NOT NULL DEFAULT '0',
  `ready_for_admin` tinyint(1) NOT NULL DEFAULT '1',
  `has_minor_edit` tinyint(1) NOT NULL DEFAULT '0',
  `is_pattern` tinyint(1) NOT NULL DEFAULT '0',
  `is_composite` tinyint(1) NOT NULL DEFAULT '0',
  `is_dual_mould` tinyint(1) NOT NULL DEFAULT '0',
  `base_part_id` bigint unsigned DEFAULT NULL,
  `unknown_part_number_id` bigint unsigned DEFAULT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type_qualifier` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `rebrickable` json DEFAULT NULL,
  `preview` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rebrickable_part_id` bigint unsigned DEFAULT NULL,
  `help` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `parts_filename_part_release_id_unique` (`filename`,`part_release_id`),
  KEY `parts_part_release_id_foreign` (`part_release_id`),
  KEY `parts_user_id_index` (`user_id`),
  KEY `parts_filename_index` (`filename`),
  KEY `parts_description_index` (`description`),
  KEY `parts_unofficial_part_id_foreign` (`unofficial_part_id`),
  KEY `parts_sticker_sheet_id_foreign` (`sticker_sheet_id`),
  KEY `parts_base_part_id_foreign` (`base_part_id`),
  KEY `parts_unknown_part_number_id_foreign` (`unknown_part_number_id`),
  KEY `parts_type_index` (`type`),
  KEY `parts_type_qualifier_index` (`type_qualifier`),
  KEY `parts_license_index` (`license`),
  KEY `parts_vote_sort_index` (`part_status`),
  KEY `parts_category_index` (`category`),
  KEY `parts_rebrickable_part_id_foreign` (`rebrickable_part_id`),
  KEY `parts_created_at_index` (`created_at`),
  CONSTRAINT `parts_base_part_id_foreign` FOREIGN KEY (`base_part_id`) REFERENCES `parts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `parts_part_release_id_foreign` FOREIGN KEY (`part_release_id`) REFERENCES `part_releases` (`id`),
  CONSTRAINT `parts_rebrickable_part_id_foreign` FOREIGN KEY (`rebrickable_part_id`) REFERENCES `rebrickable_parts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `parts_sticker_sheet_id_foreign` FOREIGN KEY (`sticker_sheet_id`) REFERENCES `sticker_sheets` (`id`) ON DELETE SET NULL,
  CONSTRAINT `parts_unknown_part_number_id_foreign` FOREIGN KEY (`unknown_part_number_id`) REFERENCES `unknown_part_numbers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `parts_unofficial_part_id_foreign` FOREIGN KEY (`unofficial_part_id`) REFERENCES `parts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `parts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `parts_part_keywords`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `parts_part_keywords` (
  `part_id` bigint unsigned NOT NULL,
  `part_keyword_id` bigint unsigned NOT NULL,
  UNIQUE KEY `parts_part_keywords_part_id_part_keyword_id_unique` (`part_id`,`part_keyword_id`),
  KEY `parts_part_keywords_part_id_index` (`part_id`),
  KEY `parts_part_keywords_part_keyword_id_index` (`part_keyword_id`),
  CONSTRAINT `parts_part_keywords_part_id_foreign` FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `parts_part_keywords_part_keyword_id_foreign` FOREIGN KEY (`part_keyword_id`) REFERENCES `part_keywords` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `poll_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `poll_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `poll_id` bigint unsigned NOT NULL,
  `item` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `poll_votes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `poll_votes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `poll_item_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `polls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `polls` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ends_on` datetime NOT NULL,
  `choices_limit` int NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '0',
  `has_been_enabled` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pulse_aggregates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pulse_aggregates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bucket` int unsigned NOT NULL,
  `period` mediumint unsigned NOT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `key` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `key_hash` binary(16) GENERATED ALWAYS AS (unhex(md5(`key`))) VIRTUAL,
  `aggregate` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` decimal(20,2) NOT NULL,
  `count` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pulse_aggregates_bucket_period_type_aggregate_key_hash_unique` (`bucket`,`period`,`type`,`aggregate`,`key_hash`),
  KEY `pulse_aggregates_period_bucket_index` (`period`,`bucket`),
  KEY `pulse_aggregates_type_index` (`type`),
  KEY `pulse_aggregates_period_type_aggregate_bucket_index` (`period`,`type`,`aggregate`,`bucket`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pulse_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pulse_entries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `timestamp` int unsigned NOT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `key` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `key_hash` binary(16) GENERATED ALWAYS AS (unhex(md5(`key`))) VIRTUAL,
  `value` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pulse_entries_timestamp_index` (`timestamp`),
  KEY `pulse_entries_type_index` (`type`),
  KEY `pulse_entries_key_hash_index` (`key_hash`),
  KEY `pulse_entries_timestamp_type_key_hash_value_index` (`timestamp`,`type`,`key_hash`,`value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pulse_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pulse_values` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `timestamp` int unsigned NOT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `key` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `key_hash` binary(16) GENERATED ALWAYS AS (unhex(md5(`key`))) VIRTUAL,
  `value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pulse_values_type_key_hash_unique` (`type`,`key_hash`),
  KEY `pulse_values_timestamp_index` (`timestamp`),
  KEY `pulse_values_type_index` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rebrickable_parts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rebrickable_parts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bricklink` json DEFAULT NULL,
  `brickset` json DEFAULT NULL,
  `brickowl` json DEFAULT NULL,
  `lego` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rebrickable_parts_number_unique` (`number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `related_parts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `related_parts` (
  `parent_id` bigint unsigned NOT NULL,
  `subpart_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`parent_id`,`subpart_id`),
  KEY `related_parts_parent_id_index` (`parent_id`),
  KEY `related_parts_subpart_id_index` (`subpart_id`),
  KEY `related_parts_subpart_id_parent_id_index` (`subpart_id`,`parent_id`),
  CONSTRAINT `related_parts_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `parts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `related_parts_subpart_id_foreign` FOREIGN KEY (`subpart_id`) REFERENCES `parts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `review_summaries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `review_summaries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `header` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `order` int NOT NULL,
  `list` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `year` year NOT NULL,
  `rb_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `theme_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sets_number_unique` (`number`),
  KEY `sets_theme_id_foreign` (`theme_id`),
  KEY `sets_created_at_index` (`created_at`),
  CONSTRAINT `sets_theme_id_foreign` FOREIGN KEY (`theme_id`) REFERENCES `themes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `group` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `locked` tinyint(1) NOT NULL DEFAULT '0',
  `payload` json NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_group_name_unique` (`group`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sticker_sheets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sticker_sheets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `rebrickable` json DEFAULT NULL,
  `ldraw_colour_id` bigint unsigned DEFAULT NULL,
  `part_colors` json DEFAULT NULL,
  `rebrickable_part_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sticker_sheets_number_unique` (`number`),
  KEY `sticker_sheets_ldraw_colour_id_foreign` (`ldraw_colour_id`),
  KEY `sticker_sheets_rebrickable_part_id_foreign` (`rebrickable_part_id`),
  CONSTRAINT `sticker_sheets_ldraw_colour_id_foreign` FOREIGN KEY (`ldraw_colour_id`) REFERENCES `ldraw_colours` (`id`),
  CONSTRAINT `sticker_sheets_rebrickable_part_id_foreign` FOREIGN KEY (`rebrickable_part_id`) REFERENCES `rebrickable_parts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `themes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `themes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tracker_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tracker_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `history_data` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `tracker_histories_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `unknown_part_numbers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `unknown_part_numbers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `user_id` bigint unsigned NOT NULL,
  `number` int NOT NULL,
  `notes` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `unknown_part_numbers_user_id_foreign` (`user_id`),
  CONSTRAINT `unknown_part_numbers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_part_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_part_notifications` (
  `user_id` bigint unsigned NOT NULL,
  `part_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`part_id`,`user_id`),
  KEY `user_part_notifications_user_id_index` (`user_id`),
  KEY `user_part_notifications_part_id_index` (`part_id`),
  KEY `user_part_notifications_user_id_part_id_index` (`user_id`,`part_id`),
  CONSTRAINT `user_part_notifications_part_id_foreign` FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_part_notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `realname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `forum_user_id` bigint DEFAULT NULL,
  `loginkey` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_synthetic` tinyint(1) NOT NULL DEFAULT '0',
  `is_legacy` tinyint(1) NOT NULL DEFAULT '0',
  `is_ptadmin` tinyint(1) NOT NULL DEFAULT '0',
  `ca_confirm` tinyint(1) NOT NULL DEFAULT '0',
  `license` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_name_unique` (`name`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_realname_unique` (`realname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `votes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `votes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `part_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `vote_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `votes_part_id_user_id_unique` (`part_id`,`user_id`),
  KEY `votes_user_id_index` (`user_id`),
  KEY `votes_part_id_index` (`part_id`),
  KEY `votes_vote_type_index` (`vote_type`),
  CONSTRAINT `votes_part_id_foreign` FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `votes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'2013_01_29_204146_create_part_licenses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'2014_10_12_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'2014_10_12_100000_create_password_resets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2019_08_19_000000_create_failed_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2019_12_14_000001_create_personal_access_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2020_08_11_170352_create_permission_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2022_01_19_194016_create_part_categories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2022_01_30_002507_create_vote_types_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2022_01_30_002508_create_part_releases_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2022_01_30_002509_create_part_types_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2022_01_30_002510_create_part_type_qualifiers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2022_01_30_002844_create_parts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2022_01_30_002845_create_votes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2022_01_30_002846_related_parts',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2022_02_02_034800_create_part_event_types_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2022_02_02_034901_create_part_events_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2022_02_13_034848_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2022_02_24_015447_create_part_histories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2022_11_27_163648_create_part_keywords_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2022_11_28_014017_parts_part_keywords_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2023_01_29_052913_user_part_notification_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2023_01_31_044145_create_tracker_histories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2023_02_01_010626_create_review_summaries_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2023_02_01_010702_create_parts_review_summaries_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2023_02_02_201005_alter_parts_table_add_cmdline_bfc',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2023_02_02_201100_create_part_helps_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2023_02_04_184303_add_part_id_to_part_helps',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2023_02_06_033305_create_part_bodies_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2023_02_26_233655_alter_part_release_table_add_parts',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2023_03_01_172138_create_job_batches_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2023_03_23_020851_alter_part_events_table_add_deleted_moved_filenames',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2023_03_24_014842_alter_parts_table_add_delete_flag',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2023_03_24_185420_alter_part_releases_table_add_release_data',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2023_03_25_212723_update_parts_table_remove_soft_deletes',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2023_03_26_173942_alter_parts_table_add_minor_edits_flag',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2023_04_05_025110_alter_parts_table_add_minor_edit_data',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2023_04_11_185513_alter_parts_table_add_missing_parts',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2023_04_12_000000_alter_parts_table_remove_minor_edits_flag',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2023_05_06_225719_drop_table_parts_review_summaries',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2023_05_06_230030_create_review_summary_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2023_05_06_230621_alter_review_summaries_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2023_06_03_012049_alter_parts_table_add_manual_hold_flag',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2023_06_26_011337_alter_parts_table_part_release_nullable',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2023_06_26_014916_alter_part_events_table_part_release_nullable',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2023_07_06_151850_alter_users_table_add_settings_mybbloginkey',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2023_07_22_012944_alter_part_events_table_add_moved_to_filename',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2023_07_22_034538_alter_users_table_add_account_type',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2023_08_10_035408_alter_part_events_table_add_header_changes',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2023_09_14_021598_create_themes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2023_09_14_021599_create_sets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2023_09_14_021600_create_omr_models_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2023_09_29_223731_add_profile_settings_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2024_01_27_185955_add_sort_to_vote_types_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2024_02_05_002439_add_week_column_to_parts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2024_03_05_235809_alter_official_unofficial_part_in_parts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2024_03_10_020603_add_account_types_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2024_03_16_222721_add_can_release_reasons_to_parts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2024_04_30_232546_create_rebrickable_parts_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2024_04_30_232556_create_sticker_sheets_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2024_05_04_125758_alter_parts_table_add_sticker_sheet',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2024_05_07_230351_create_part_render_views_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2024_05_08_170859_create_document_categories_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2024_05_08_200832_create_documents_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2022_12_14_083707_create_settings_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2024_05_16_230802_alter_vote_types_table_sort_to_order',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2024_05_16_233915_add_in_use_to_part_licenses_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2024_05_17_043355_create_library_settings',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2024_05_18_000545_add_default_render_setting',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2024_05_18_004049_drop_part_render_views_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (74,'2024_05_27_210910_add_marked_for_release_to_parts_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (75,'2024_06_07_023407_add_ca_confirm_to_users_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (76,'2024_06_12_145500_add_indexes_to_related_parts_table',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (77,'2024_06_12_150924_add_indexes_to_user_part_notifications_table',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (78,'2024_06_13_234020_add_not_ready_for_admin_flag_to_parts_table',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (79,'2024_06_30_210522_create_tracker_lock_setting',13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (80,'2024_10_07_010238_add_model_image_size',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (81,'2024_10_10_022351_alter_minor_edit_dat_on_parts_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (82,'2024_10_22_191832_create_pan_analytics_table',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (83,'2024_10_28_225238_add_meta_columns_to_parts_table',17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (85,'2024_11_01_150308_create_ldraw_colours_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (86,'2024_11_15_005620_create_unknown_part_number_table',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (87,'2024_11_17_011340_create_parts_unknown_part_numbers_table',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (88,'2024_11_28_191428_add_vote_type_to_votes_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (89,'2024_11_28_193101_add_enum_types_to_part_events_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (90,'2024_11_29_061157_remove_vote_and_event_type_from_part_events_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (91,'2024_11_29_061221_remove_vote_type_code_from_votes_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (92,'2024_11_29_065104_drop_vote_and_part_event_types_tables',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (93,'2024_11_29_180749_add_part_type_enums_to_part_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (94,'2024_11_29_184550_add_license_enum_to_users_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (95,'2024_11_30_170048_update_default_license_type',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (96,'2024_12_01_180608_drop_enumed_columns_from_parts_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (97,'2024_12_01_180615_drop_enumed_columns_from_users_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (98,'2024_12_01_183024_drop_enumed_tables',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (99,'2024_12_01_183322_add_license_to_omr_models_table',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (100,'2024_12_01_184725_drop_part_license_from_omr_models_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (101,'2024_12_01_184726_drop_part_licenses_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (102,'2024_12_02_034722_add_indexes_to_parts_table',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (103,'2024_12_03_181115_add_indexes_to_part_events_table',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (104,'2024_12_03_181124_add_indexes_to_votes_table',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (105,'2024_12_03_181125_add_external_site_ids_to_parts_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (106,'2024_12_15_065746_add_indexes_to_parts_table',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (107,'2024_12_15_065752_add_indexes_to_part_events_table',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (108,'2024_12_18_073755_add_external_info_to_sticker_sheets_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (109,'2025_01_20_045104_add_enabled_to_part_releases_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (110,'2025_02_02_000000_create_telescope_entries_table',34);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (111,'2025_02_03_034228_create_polls_table',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (112,'2025_02_03_034235_create_poll_items_table',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (113,'2025_02_04_164900_create_poll_votes_table',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (114,'2025_02_19_235340_add_preview_to_parts_table',36);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (115,'2025_02_26_055827_alter_vote_sort_to_part_status_in_parts_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (116,'2025_03_07_180323_add_category_to_parts_table',38);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (117,'2025_03_08_071837_drop_part_category_table',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (118,'2025_03_08_072853_library_database_cleanup',40);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (119,'2025_03_11_060620_update_foreign_key_behavior_in_parts_table',41);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (120,'2025_03_12_191023_rename_external_ids_in_parts_table',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (121,'2025_03_27_185715_create_rebrickable_parts_table',43);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (122,'2025_03_28_060708_add_rebrickable_part_id',43);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (123,'2025_04_06_211334_alter_part_check_in_parts_table',44);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (124,'2025_04_13_234802_add_fabric_to_ldraw_colours_table',45);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (125,'2025_04_22_055504_sync_columns_with_rb_in_themes_table',46);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (126,'2025_04_24_150403_alter_rebrickable_column_to_nullable_in_sticker_sheets_table',47);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (127,'2025_06_18_022605_remove_telescope',48);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (128,'2025_06_18_023912_create_pulse_tables',49);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (129,'2025_07_27_012549_add_help_to_parts_table',50);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (130,'2025_07_27_044921_drop_parthelps_table',51);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (131,'2025_08_05_020802_alter_revision_history_in_documents_table',52);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (132,'2025_08_23_065321_add_list_to_review_summaries_table',52);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (133,'2025_08_23_213916_drop_review_summary_items_table',53);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (134,'2025_09_04_171235_create_avatars_table',54);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (135,'2025_09_16_180016_create_media_table',55);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (136,'2025_09_29_173225_drop_part_list_from_part_releases_table',56);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (137,'2025_10_01_034314_add_notes_data_to_part_releases_table',56);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (138,'2025_10_02_193319_add_date_indexes',56);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (139,'2025_10_04_060416_drop_josn_columns_from_part_releases_table',57);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (140,'2025_10_06_185835_change_comment_to_mediumtext_in_part_events_table',58);
