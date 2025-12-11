<?php
/**
 * DATABASE SQL REFERENCE & EXAMPLE DATA
 * Use this to understand and populate the database
 */

// ============================================================
// TABLE CREATION STATEMENTS (Already in db_setup.php)
// ============================================================

/**
 * TEACHERS TABLE
 */
/*
CREATE TABLE IF NOT EXISTS teachers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_number VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) UNIQUE NOT NULL,
    position VARCHAR(100),
    archived TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
*/

/**
 * FACILITATORS TABLE
 */
/*
CREATE TABLE IF NOT EXISTS facilitators (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_number VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) UNIQUE NOT NULL,
    position VARCHAR(100),
    employment_status VARCHAR(50),
    archived TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
*/

/**
 * DETAINEES TABLE
 */
/*
CREATE TABLE IF NOT EXISTS detainees (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_number VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) UNIQUE NOT NULL,
    grade_level VARCHAR(50),
    archived TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
*/

/**
 * SUBJECTS TABLE
 */
/*
CREATE TABLE IF NOT EXISTS subjects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    subject_code VARCHAR(50) UNIQUE NOT NULL,
    title VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    archived TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
*/

/**
 * GRADE LEVELS TABLE
 */
/*
CREATE TABLE IF NOT EXISTS grade_levels (
    id INT PRIMARY KEY AUTO_INCREMENT,
    level VARCHAR(50) UNIQUE NOT NULL,
    archived TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
*/

/**
 * PROVIDERS TABLE
 */
/*
CREATE TABLE IF NOT EXISTS providers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_number VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) UNIQUE NOT NULL,
    provider_type VARCHAR(100),
    archived TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
*/

// ============================================================
// SAMPLE DATA INSERTION STATEMENTS
// ============================================================

/**
 * Example: Insert Grade Levels
 */
/*
INSERT INTO grade_levels (level) VALUES 
('Grade 7'),
('Grade 8'),
('Grade 9'),
('Grade 10'),
('Grade 11'),
('Grade 12');
*/

/**
 * Example: Insert Subjects
 */
/*
INSERT INTO subjects (subject_code, title, description) VALUES 
('ENG', 'English', 'English Language and Literature'),
('MAT', 'Mathematics', 'General Mathematics'),
('SCI', 'Science', 'General Science'),
('SOC', 'Social Studies', 'History and Civics'),
('APE', 'Arts/PE', 'Physical Education');
*/

/**
 * Example: Insert Teachers
 */
/*
INSERT INTO teachers (id_number, name, position) VALUES 
('T001', 'Juan Dela Cruz', 'Math Teacher'),
('T002', 'Maria Garcia', 'English Teacher'),
('T003', 'Pedro Santos', 'Science Teacher');
*/

/**
 * Example: Insert Facilitators
 */
/*
INSERT INTO facilitators (id_number, name, position, employment_status) VALUES 
('F001', 'Rosa Lopez', 'Facilitator', 'Full-time'),
('F002', 'Carlos Reyes', 'Facilitator', 'Part-time');
*/

/**
 * Example: Insert Detainees
 */
/*
INSERT INTO detainees (id_number, name, grade_level) VALUES 
('D001', 'Ana Rodriguez', 'Grade 7'),
('D002', 'Bong Fernandez', 'Grade 7'),
('D003', 'Cecilia Nunez', 'Grade 8'),
('D004', 'Daniel Gonzales', 'Grade 8'),
('D005', 'Elena Torres', 'Grade 9');
*/

/**
 * Example: Insert Providers
 */
/*
INSERT INTO providers (id_number, name, provider_type) VALUES 
('P001', 'Marcelo Facility', 'Marcelo'),
('P002', 'St. Martin Center', 'St. Martin'),
('P003', 'DepEd ALS Program', 'DepEd ALS');
*/

// ============================================================
// USEFUL QUERIES FOR TESTING
// ============================================================

/**
 * Check for duplicate ID numbers (example)
 */
/*
SELECT id_number, COUNT(*) as count FROM teachers GROUP BY id_number HAVING COUNT(*) > 1;
SELECT id_number, COUNT(*) as count FROM facilitators GROUP BY id_number HAVING COUNT(*) > 1;
SELECT id_number, COUNT(*) as count FROM detainees GROUP BY id_number HAVING COUNT(*) > 1;
*/

/**
 * View all active teachers
 */
/*
SELECT * FROM teachers WHERE archived = 0 ORDER BY name;
*/

/**
 * View all detainees by grade level
 */
/*
SELECT * FROM detainees WHERE archived = 0 AND grade_level = 'Grade 7' ORDER BY name;
*/

/**
 * View modules for a specific grade level
 */
/*
SELECT m.*, s.title as subject_title, gl.level 
FROM modules m 
JOIN subjects s ON m.subject_id = s.id 
JOIN grade_levels gl ON m.grade_level_id = gl.id 
WHERE gl.level = 'Grade 7';
*/

/**
 * View all submissions for a specific activity
 */
/*
SELECT s.*, det.name, a.title as activity_title 
FROM submissions s 
JOIN detainees det ON s.detainee_id = det.id 
JOIN activity_sheets a ON s.activity_sheet_id = a.id 
WHERE s.status = 'pending';
*/

/**
 * Get grades for a specific detainee
 */
/*
SELECT rc.*, s.title as subject_title, t.name as teacher_name 
FROM report_cards rc 
JOIN subjects s ON rc.subject_id = s.id 
JOIN teachers t ON rc.teacher_id = t.id 
WHERE rc.detainee_id = 1;
*/

/**
 * View all distributions by a facilitator
 */
/*
SELECT d.*, m.title as module_title, det.name as detainee_name 
FROM distributions d 
JOIN modules m ON d.module_id = m.id 
JOIN detainees det ON d.detainee_id = det.id 
WHERE d.facilitator_id = 1;
*/

// ============================================================
// VALIDATION QUERIES
// ============================================================

/**
 * Check for duplicate names (case-insensitive)
 */
/*
SELECT name, COUNT(*) as count FROM teachers 
GROUP BY LOWER(name) HAVING COUNT(*) > 1;
*/

/**
 * Find archived vs active records
 */
/*
SELECT 'Teachers Active' as category, COUNT(*) as count FROM teachers WHERE archived = 0
UNION
SELECT 'Teachers Archived', COUNT(*) FROM teachers WHERE archived = 1
UNION
SELECT 'Detainees Active', COUNT(*) FROM detainees WHERE archived = 0
UNION
SELECT 'Detainees Archived', COUNT(*) FROM detainees WHERE archived = 1;
*/

// ============================================================
// REFERENCE: Available Functions
// ============================================================

/*

USER MANAGEMENT (admin_functions_users.php):

TEACHERS:
  - addTeacher($conn, $id_number, $name, $position)
  - editTeacher($conn, $teacher_id, $id_number, $name, $position)
  - archiveTeacher($conn, $teacher_id)
  - getAllTeachers($conn, $includeArchived = false)

FACILITATORS:
  - addFacilitator($conn, $id_number, $name, $position, $employment_status)
  - editFacilitator($conn, $facilitator_id, $id_number, $name, $position, $employment_status)
  - archiveFacilitator($conn, $facilitator_id)
  - getAllFacilitators($conn, $includeArchived = false)

DETAINEES:
  - addDetainee($conn, $id_number, $name, $grade_level)
  - editDetainee($conn, $detainee_id, $id_number, $name, $grade_level)
  - archiveDetainee($conn, $detainee_id)
  - getAllDetainees($conn, $includeArchived = false)


SUBJECT MANAGEMENT (admin_functions_subjects.php):

SUBJECTS:
  - addSubject($conn, $subject_code, $title, $description)
  - editSubject($conn, $subject_id, $subject_code, $title, $description)
  - archiveSubject($conn, $subject_id)
  - getAllSubjects($conn, $includeArchived = false)

GRADE LEVELS:
  - addGradeLevel($conn, $level)
  - editGradeLevel($conn, $grade_level_id, $level)
  - archiveGradeLevel($conn, $grade_level_id)
  - getAllGradeLevels($conn, $includeArchived = false)


PROVIDER MANAGEMENT (admin_functions_providers.php):

PROVIDERS:
  - addProvider($conn, $id_number, $name, $provider_type)
  - editProvider($conn, $provider_id, $id_number, $name, $provider_type)
  - archiveProvider($conn, $provider_id)
  - getAllProviders($conn, $includeArchived = false)

*/

?>
