-- ============================================================
-- TANGLAW LMS - COMPLETE DATABASE CREATION SCRIPT
-- ============================================================
-- Import this SQL file into phpMyAdmin to create all tables
-- Database Name: tanglaw_lms
-- Created: December 2, 2025
-- ============================================================

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS `tanglaw_lms`;
USE `tanglaw_lms`;

-- ============================================================
-- TABLE: teachers
-- Purpose: Store teacher information
-- Constraints: Unique ID Number and Name
-- ============================================================
CREATE TABLE IF NOT EXISTS `teachers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `position` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `level` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_id` int(11) DEFAULT NULL,
  `archived` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_id_number` (`id_number`),
  UNIQUE KEY `unique_name` (`name`),
  KEY `idx_provider_id` (`provider_id`),
  INDEX `idx_archived` (`archived`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: facilitators
-- Purpose: Store facilitator information
-- Constraints: Unique ID Number and Name
-- ============================================================
CREATE TABLE IF NOT EXISTS `facilitators` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `position` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employment_status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `archived` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_id_number` (`id_number`),
  UNIQUE KEY `unique_name` (`name`),
  INDEX `idx_archived` (`archived`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: detainees
-- Purpose: Store detainee/student information
-- Constraints: Unique ID Number and Name
-- ============================================================
CREATE TABLE IF NOT EXISTS `detainees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `grade_level` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `school` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `archived` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_id_number` (`id_number`),
  UNIQUE KEY `unique_name` (`name`),
  INDEX `idx_archived` (`archived`),
  INDEX `idx_grade_level` (`grade_level`),
  INDEX `idx_school` (`school`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: subjects
-- Purpose: Store subject/course information
-- Constraints: Unique Subject Code and Title
-- ============================================================
CREATE TABLE IF NOT EXISTS `subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subject_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `archived` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_code` (`subject_code`),
  UNIQUE KEY `unique_title` (`title`),
  INDEX `idx_archived` (`archived`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: grade_levels
-- Purpose: Store grade level information
-- Constraints: Unique Level
-- ============================================================
CREATE TABLE IF NOT EXISTS `grade_levels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `level` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `archived` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_level` (`level`),
  INDEX `idx_archived` (`archived`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: providers
-- Purpose: Store provider/organization information
-- Constraints: Unique ID Number and Name
-- ============================================================
CREATE TABLE IF NOT EXISTS `providers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `archived` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_id_number` (`id_number`),
  UNIQUE KEY `unique_name` (`name`),
  INDEX `idx_archived` (`archived`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add foreign key from teachers.provider_id to providers.id (if teachers table exists)
ALTER TABLE `teachers` ADD CONSTRAINT `fk_teachers_provider` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`);

-- ============================================================
-- TABLE: modules
-- Purpose: Store educational modules uploaded by teachers
-- Foreign Keys: subject_id, grade_level_id, teacher_id
-- ============================================================
CREATE TABLE IF NOT EXISTS `modules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `grade_level_id` int(11) DEFAULT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_subject_id` (`subject_id`),
  KEY `fk_grade_level_id` (`grade_level_id`),
  KEY `fk_teacher_id` (`teacher_id`),
  CONSTRAINT `fk_module_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`),
  CONSTRAINT `fk_module_grade_level` FOREIGN KEY (`grade_level_id`) REFERENCES `grade_levels` (`id`),
  CONSTRAINT `fk_module_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: activity_sheets
-- Purpose: Store activity sheets created by teachers
-- Foreign Keys: module_id, teacher_id
-- ============================================================
CREATE TABLE IF NOT EXISTS `activity_sheets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `module_id` int(11) DEFAULT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_module_id` (`module_id`),
  KEY `fk_teacher_id` (`teacher_id`),
  CONSTRAINT `fk_activity_module` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`),
  CONSTRAINT `fk_activity_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: submissions
-- Purpose: Store student submissions and grades
-- Foreign Keys: detainee_id, activity_sheet_id, facilitator_id
-- ============================================================
CREATE TABLE IF NOT EXISTS `submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `detainee_id` int(11) NOT NULL,
  `activity_sheet_id` int(11) NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `facilitator_id` int(11) DEFAULT NULL,
  `status` enum('pending','submitted','graded') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `grade` decimal(5,2) DEFAULT NULL,
  `comments` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_detainee_id` (`detainee_id`),
  KEY `fk_activity_sheet_id` (`activity_sheet_id`),
  KEY `fk_facilitator_id` (`facilitator_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_submission_detainee` FOREIGN KEY (`detainee_id`) REFERENCES `detainees` (`id`),
  CONSTRAINT `fk_submission_activity` FOREIGN KEY (`activity_sheet_id`) REFERENCES `activity_sheets` (`id`),
  CONSTRAINT `fk_submission_facilitator` FOREIGN KEY (`facilitator_id`) REFERENCES `facilitators` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: report_cards
-- Purpose: Store quarterly grades for detainees
-- Foreign Keys: detainee_id, subject_id, teacher_id
-- ============================================================
CREATE TABLE IF NOT EXISTS `report_cards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `detainee_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `quarter` int(11) NOT NULL,
  `grade` decimal(5,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_detainee_id` (`detainee_id`),
  KEY `fk_subject_id` (`subject_id`),
  KEY `fk_teacher_id` (`teacher_id`),
  UNIQUE KEY `unique_grade` (`detainee_id`, `subject_id`, `quarter`),
  CONSTRAINT `fk_report_detainee` FOREIGN KEY (`detainee_id`) REFERENCES `detainees` (`id`),
  CONSTRAINT `fk_report_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`),
  CONSTRAINT `fk_report_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: distributions
-- Purpose: Track module/activity distribution to detainees
-- Foreign Keys: module_id, detainee_id, facilitator_id
-- ============================================================
CREATE TABLE IF NOT EXISTS `distributions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module_id` int(11) NOT NULL,
  `detainee_id` int(11) NOT NULL,
  `facilitator_id` int(11) NOT NULL,
  `distributed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_module_id` (`module_id`),
  KEY `fk_detainee_id` (`detainee_id`),
  KEY `fk_facilitator_id` (`facilitator_id`),
  CONSTRAINT `fk_distribution_module` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`),
  CONSTRAINT `fk_distribution_detainee` FOREIGN KEY (`detainee_id`) REFERENCES `detainees` (`id`),
  CONSTRAINT `fk_distribution_facilitator` FOREIGN KEY (`facilitator_id`) REFERENCES `facilitators` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- INSERT SAMPLE DATA
-- ============================================================

-- Sample Grade Levels
INSERT INTO `grade_levels` (`level`) VALUES 
('Grade 7'),
('Grade 8'),
('Grade 9'),
('Grade 10'),
('Grade 11'),
('Grade 12');

-- Sample Subjects
INSERT INTO `subjects` (`subject_code`, `title`, `description`) VALUES 
('ENG', 'English', 'English Language and Literature'),
('MAT', 'Mathematics', 'General Mathematics'),
('SCI', 'Science', 'General Science'),
('SOC', 'Social Studies', 'History and Social Studies'),
('PE', 'Physical Education', 'Physical Education and Health');

-- Sample Teachers
INSERT INTO `teachers` (`id_number`, `name`, `position`) VALUES 
('T001', 'Juan Dela Cruz', 'English Teacher'),
('T002', 'Maria Garcia', 'Math Teacher'),
('T003', 'Pedro Santos', 'Science Teacher');

-- Sample Facilitators
INSERT INTO `facilitators` (`id_number`, `name`, `position`, `employment_status`) VALUES 
('F001', 'Rosa Lopez', 'Facilitator', 'Full-time'),
('F002', 'Carlos Reyes', 'Facilitator', 'Part-time');

-- Sample Detainees
INSERT INTO `detainees` (`id_number`, `name`, `grade_level`) VALUES 
('D001', 'Ana Rodriguez', 'Grade 7'),
('D002', 'Bong Fernandez', 'Grade 7'),
('D003', 'Cecilia Nunez', 'Grade 8'),
('D004', 'Daniel Gonzales', 'Grade 8'),
('D005', 'Elena Torres', 'Grade 9');

-- Sample Providers
INSERT INTO `providers` (`id_number`, `name`, `provider_type`) VALUES 
('P001', 'Marcelo Facility', 'Marcelo'),
('P002', 'St. Martin Center', 'St. Martin'),
('P003', 'DepEd ALS Program', 'DepEd ALS');

-- ============================================================
-- INDEXES FOR PERFORMANCE
-- ============================================================

-- Create indexes for common queries
CREATE INDEX idx_teachers_archived ON teachers(archived);
CREATE INDEX idx_facilitators_archived ON facilitators(archived);
CREATE INDEX idx_detainees_archived ON detainees(archived);
CREATE INDEX idx_subjects_archived ON subjects(archived);
CREATE INDEX idx_grade_levels_archived ON grade_levels(archived);
CREATE INDEX idx_providers_archived ON providers(archived);
CREATE INDEX idx_submissions_status ON submissions(status);
CREATE INDEX idx_submissions_detainee ON submissions(detainee_id);
CREATE INDEX idx_report_detainee ON report_cards(detainee_id);

-- ============================================================
-- DATABASE SETUP COMPLETE
-- ============================================================
-- Tables Created: 11
-- Constraints: UNIQUE on all ID Numbers and Names
-- Foreign Keys: All relationships configured
-- Sample Data: Loaded with demo records
-- Ready to use!
-- ============================================================
