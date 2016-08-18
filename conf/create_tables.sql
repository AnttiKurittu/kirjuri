SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
CREATE DATABASE IF NOT EXISTS `kirjuri_db` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `kirjuri_db`;

CREATE TABLE `event_log` (
  `id` int(11) NOT NULL,
  `event_timestamp` datetime DEFAULT NULL,
  `event_descr` text,
  `event_level` tinytext,
  `ip` varchar(16) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `exam_requests` (
  `id` int(11) NOT NULL,
  `parent_id` int(16) DEFAULT NULL,
  `case_id` int(16) DEFAULT NULL,
  `case_name` text COLLATE utf8_unicode_ci,
  `case_suspect` text COLLATE utf8_unicode_ci,
  `case_file_number` text COLLATE utf8_unicode_ci,
  `case_added_date` datetime DEFAULT NULL,
  `case_confiscation_date` date DEFAULT NULL,
  `case_start_date` datetime DEFAULT NULL,
  `case_ready_date` datetime DEFAULT NULL,
  `case_remove_date` datetime DEFAULT NULL,
  `case_devicecount` int(16) DEFAULT NULL,
  `case_investigator` text COLLATE utf8_unicode_ci,
  `forensic_investigator` text COLLATE utf8_unicode_ci,
  `phone_investigator` text COLLATE utf8_unicode_ci,
  `case_investigation_lead` text COLLATE utf8_unicode_ci,
  `case_investigator_tel` text COLLATE utf8_unicode_ci,
  `case_investigator_unit` text COLLATE utf8_unicode_ci,
  `case_crime` text COLLATE utf8_unicode_ci,
  `copy_location` text COLLATE utf8_unicode_ci,
  `is_removed` int(1) DEFAULT NULL,
  `case_status` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `case_requested_action` text COLLATE utf8_unicode_ci,
  `device_action` text COLLATE utf8_unicode_ci,
  `case_contains_mob_dev` int(1) DEFAULT NULL,
  `case_urgency` int(1) DEFAULT NULL,
  `case_urg_justification` text COLLATE utf8_unicode_ci,
  `case_request_description` text COLLATE utf8_unicode_ci,
  `examiners_notes` text COLLATE utf8_unicode_ci,
  `device_type` text COLLATE utf8_unicode_ci,
  `device_manuf` text COLLATE utf8_unicode_ci,
  `device_model` text COLLATE utf8_unicode_ci,
  `device_os` text COLLATE utf8_unicode_ci,
  `device_identifier` text COLLATE utf8_unicode_ci,
  `device_location` text COLLATE utf8_unicode_ci,
  `device_item_number` int(4) DEFAULT NULL,
  `device_document` text COLLATE utf8_unicode_ci,
  `device_owner` text COLLATE utf8_unicode_ci,
  `device_is_host` int(1) DEFAULT '0',
  `device_host_id` int(16) DEFAULT NULL,
  `device_include_in_report` int(1) DEFAULT NULL,
  `device_time_deviation` text COLLATE utf8_unicode_ci,
  `device_size_in_gb` int(16) DEFAULT NULL,
  `device_contains_evidence` int(1) DEFAULT '0',
  `last_updated` datetime DEFAULT NULL,
  `classification` text COLLATE utf8_unicode_ci,
  `report_notes` mediumtext COLLATE utf8_unicode_ci
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


ALTER TABLE `event_log`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `exam_requests`
  ADD PRIMARY KEY (`id`),
  ADD FULLTEXT KEY `tapaus` (`case_name`,`case_suspect`,`case_file_number`,`case_investigator`,`forensic_investigator`,`phone_investigator`,`case_investigation_lead`,`case_investigator_unit`,`case_crime`,`case_requested_action`,`case_request_description`,`report_notes`,`device_manuf`,`device_model`,`device_identifier`,`device_owner`);


ALTER TABLE `event_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `exam_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
