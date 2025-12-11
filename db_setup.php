<?php
/**
 * DATABASE SETUP SCRIPT
 * Run this once to create all tables
 */

$host = "localhost";        
$user = "root";           
$password = "";            
$database = "tanglaw_lms";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8");

// ============= CREATE TABLES =============

// Teachers Table
$sql = "CREATE TABLE IF NOT EXISTS teachers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_number VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(150) UNIQUE,
    position VARCHAR(100),
    archived TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!$conn->query($sql)) {
    echo "❌ Error creating teachers table: " . $conn->error . "<br>";
} else {
    echo "✅ Teachers table created/exists<br>";
}

// Facilitators Table
$sql = "CREATE TABLE IF NOT EXISTS facilitators (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_number VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(150) UNIQUE,
    position VARCHAR(100),
    employment_status VARCHAR(50),
    archived TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!$conn->query($sql)) {
    echo "❌ Error creating facilitators table: " . $conn->error . "<br>";
} else {
    echo "✅ Facilitators table created/exists<br>";
}

// Detainees Table
$sql = "CREATE TABLE IF NOT EXISTS detainees (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_number VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(150) UNIQUE,
    grade_level VARCHAR(50),
    archived TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!$conn->query($sql)) {
    echo "❌ Error creating detainees table: " . $conn->error . "<br>";
} else {
    echo "✅ Detainees table created/exists<br>";
}

// Subjects Table
$sql = "CREATE TABLE IF NOT EXISTS subjects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    subject_code VARCHAR(50) UNIQUE NOT NULL,
    title VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    archived TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!$conn->query($sql)) {
    echo "❌ Error creating subjects table: " . $conn->error . "<br>";
} else {
    echo "✅ Subjects table created/exists<br>";
}

// Grade Levels Table
$sql = "CREATE TABLE IF NOT EXISTS grade_levels (
    id INT PRIMARY KEY AUTO_INCREMENT,
    level VARCHAR(50) UNIQUE NOT NULL,
    archived TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!$conn->query($sql)) {
    echo "❌ Error creating grade_levels table: " . $conn->error . "<br>";
} else {
    echo "✅ Grade Levels table created/exists<br>";
}

// Providers Table
$sql = "CREATE TABLE IF NOT EXISTS providers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_number VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) UNIQUE NOT NULL,
    provider_type VARCHAR(100),
    archived TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!$conn->query($sql)) {
    echo "❌ Error creating providers table: " . $conn->error . "<br>";
} else {
    echo "✅ Providers table created/exists<br>";
}

// Modules Table (for teacher uploads)
$sql = "CREATE TABLE IF NOT EXISTS modules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(100) NOT NULL,
    subject_id INT,
    grade_level_id INT,
    file_path VARCHAR(255),
    teacher_id INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id),
    FOREIGN KEY (grade_level_id) REFERENCES grade_levels(id),
    FOREIGN KEY (teacher_id) REFERENCES teachers(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!$conn->query($sql)) {
    echo "❌ Error creating modules table: " . $conn->error . "<br>";
} else {
    echo "✅ Modules table created/exists<br>";
}

// Activity Sheets Table
$sql = "CREATE TABLE IF NOT EXISTS activity_sheets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(100) NOT NULL,
    module_id INT,
    file_path VARCHAR(255),
    teacher_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (module_id) REFERENCES modules(id),
    FOREIGN KEY (teacher_id) REFERENCES teachers(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!$conn->query($sql)) {
    echo "❌ Error creating activity_sheets table: " . $conn->error . "<br>";
} else {
    echo "✅ Activity Sheets table created/exists<br>";
}

// Submissions Table (detainee submissions)
$sql = "CREATE TABLE IF NOT EXISTS submissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    detainee_id INT,
    activity_sheet_id INT,
    file_path VARCHAR(255),
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    facilitator_id INT,
    status ENUM('pending', 'submitted', 'graded') DEFAULT 'pending',
    grade DECIMAL(5,2),
    comments TEXT,
    FOREIGN KEY (detainee_id) REFERENCES detainees(id),
    FOREIGN KEY (activity_sheet_id) REFERENCES activity_sheets(id),
    FOREIGN KEY (facilitator_id) REFERENCES facilitators(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!$conn->query($sql)) {
    echo "❌ Error creating submissions table: " . $conn->error . "<br>";
} else {
    echo "✅ Submissions table created/exists<br>";
}

// Report Cards Table (for teacher grades)
$sql = "CREATE TABLE IF NOT EXISTS report_cards (
    id INT PRIMARY KEY AUTO_INCREMENT,
    detainee_id INT,
    subject_id INT,
    teacher_id INT,
    quarter INT,
    grade DECIMAL(5,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (detainee_id) REFERENCES detainees(id),
    FOREIGN KEY (subject_id) REFERENCES subjects(id),
    FOREIGN KEY (teacher_id) REFERENCES teachers(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!$conn->query($sql)) {
    echo "❌ Error creating report_cards table: " . $conn->error . "<br>";
} else {
    echo "✅ Report Cards table created/exists<br>";
}

// Distributions Table (module/activity distributions)
$sql = "CREATE TABLE IF NOT EXISTS distributions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    module_id INT,
    detainee_id INT,
    facilitator_id INT,
    distributed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (module_id) REFERENCES modules(id),
    FOREIGN KEY (detainee_id) REFERENCES detainees(id),
    FOREIGN KEY (facilitator_id) REFERENCES facilitators(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!$conn->query($sql)) {
    echo "❌ Error creating distributions table: " . $conn->error . "<br>";
} else {
    echo "✅ Distributions table created/exists<br>";
}

echo "<hr>";
echo "✅ Database setup completed successfully!<br>";

$conn->close();
?>
