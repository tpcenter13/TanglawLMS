# Tanglaw LMS - Complete System Setup Guide

## 📋 System Overview

This is a comprehensive Learning Management System (LMS) for Tanglaw with the following roles and features:

### Roles:
1. **Admin** - Manages users, subjects, providers
2. **Teacher** - Uploads modules, receives submissions, computes grades
3. **Facilitator** - Distributes materials, collects submissions
4. **Detainee** - Views modules and submits activities

---

## 🚀 Installation Steps

### Step 1: Create the Database
1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Create a new database named `tanglaw_lms`
3. Go to the database and execute SQL to create all tables

### Step 2: Run Database Setup Script
1. Visit: `http://localhost/xampp/htdocs/tanglaw/tanglawelearning/db_setup.php`
2. This will create all necessary tables with proper constraints
3. Check all confirmations ✅

### Step 3: Access the System

**Login URL:** `http://localhost/xampp/htdocs/tanglaw/tanglawelearning/login.php`

---

## 🔐 Default Demo Credentials

### Admin Login:
- **Username:** admin
- **Password:** admin123
- **Role:** Administrator

### For Other Roles:
First use Admin to create users:
1. Login as Admin
2. Go to Admin Dashboard (http://localhost/.../admin_dashboard.php)
3. Create Teachers, Facilitators, and Detainees
4. Use their ID Numbers as usernames to login (no password required for demo)

---

## 📊 Dashboard Features

### 🛡️ Admin Dashboard (`admin_dashboard.php`)
**Features:**
- **User Management**
  - Add, Edit, Archive Teachers (ID Number, Name, Position)
  - Add, Edit, Archive Facilitators (ID Number, Name, Position, Employment Status)
  - Add, Edit, Archive Detainees (ID Number, Name, Grade Level)
  
- **Subject Management**
  - Add, Edit, Archive Subjects (Code, Title, Description)
  - Add, Edit, Archive Grade Levels (Grade 7-12)
  
- **Provider Management**
  - Add, Edit, Archive Providers (Marcelo, St. Martin, DepEd ALS, Other)

**Duplicate Prevention:**
- ✅ ID Numbers must be unique
- ✅ Names must be unique (case-insensitive)
- ✅ Subject Codes must be unique
- ✅ Subject Titles must be unique
- ✅ Grade Levels must be unique

---

### 👨‍🏫 Teacher Dashboard (`teacher_dashboard.php`)
**Features:**
1. **Upload Modules**
   - Title, Subject, Grade Level
   - Upload PDF/DOC files

2. **Upload Activity Sheets**
   - Tied to specific modules
   - Upload sheets for students

3. **Received Submissions**
   - View all submissions from facilitators
   - See submission status (pending/submitted/graded)

4. **Compute Grades**
   - Grade individual submissions
   - Add comments/feedback

5. **Report Cards**
   - Generate report cards per detainee/subject/quarter
   - Grades 0-100 scale

---

### 👥 Facilitator Dashboard (`facilitator_dashboard.php`)
**Features:**
1. **Print Activity Sheets**
   - Select activity sheets
   - Browser print function ready

2. **Distribute Modules/Activities**
   - Select multiple detainees
   - Distribute materials

3. **Collect Submissions**
   - Collect from individual detainees
   - Upload submitted files

4. **Submit to Teacher**
   - Batch submit collected materials
   - Mark as submitted to teacher

---

### 👨‍🎓 Student/Detainee Dashboard (`student_dashboard.php`)
**Features:**
- View assigned modules
- View activity sheets
- Submit completed activities
- View submissions status

---

## 📁 File Structure

```
tanglaw/tanglawelearning/
├── login.php                      # Login page for all roles
├── conn.php                       # Database connection
├── logout.php                     # Logout functionality
├── header.php                     # Common header
├── db_setup.php                   # Database initialization
├── admin_dashboard.php            # Admin management
├── teacher_dashboard.php          # Teacher portal
├── facilitator_dashboard.php      # Facilitator portal
├── student_dashboard.php          # Student/Detainee portal
├── admin_functions_users.php      # User management functions
├── admin_functions_subjects.php   # Subject management functions
├── admin_functions_providers.php  # Provider management functions
├── assets/css/style.css           # Styling
└── uploads/                       # File uploads directory
    ├── modules/
    ├── activities/
    └── submissions/
```

---

## 🗄️ Database Schema

### Tables (with duplicate prevention):

**teachers**
- id (PK)
- id_number (UNIQUE)
- name (UNIQUE)
- position
- archived

**facilitators**
- id (PK)
- id_number (UNIQUE)
- name (UNIQUE)
- position
- employment_status
- archived

**detainees**
- id (PK)
- id_number (UNIQUE)
- name (UNIQUE)
- grade_level
- archived

**subjects**
- id (PK)
- subject_code (UNIQUE)
- title (UNIQUE)
- description
- archived

**grade_levels**
- id (PK)
- level (UNIQUE)
- archived

**providers**
- id (PK)
- id_number (UNIQUE)
- name (UNIQUE)
- provider_type
- archived

**modules**
- id (PK)
- title
- subject_id (FK)
- grade_level_id (FK)
- file_path
- teacher_id (FK)

**activity_sheets**
- id (PK)
- title
- module_id (FK)
- file_path
- teacher_id (FK)

**submissions**
- id (PK)
- detainee_id (FK)
- activity_sheet_id (FK)
- file_path
- facilitator_id (FK)
- status (pending/submitted/graded)
- grade
- comments

**report_cards**
- id (PK)
- detainee_id (FK)
- subject_id (FK)
- teacher_id (FK)
- quarter (1-4)
- grade

**distributions**
- id (PK)
- module_id (FK)
- detainee_id (FK)
- facilitator_id (FK)
- distributed_at

---

## 🔧 Function Reference

### User Management Functions (`admin_functions_users.php`)

**Teachers:**
- `addTeacher($conn, $id_number, $name, $position)`
- `editTeacher($conn, $teacher_id, $id_number, $name, $position)`
- `archiveTeacher($conn, $teacher_id)`
- `getAllTeachers($conn, $includeArchived = false)`

**Facilitators:**
- `addFacilitator($conn, $id_number, $name, $position, $employment_status)`
- `editFacilitator($conn, $facilitator_id, $id_number, $name, $position, $employment_status)`
- `archiveFacilitator($conn, $facilitator_id)`
- `getAllFacilitators($conn, $includeArchived = false)`

**Detainees:**
- `addDetainee($conn, $id_number, $name, $grade_level)`
- `editDetainee($conn, $detainee_id, $id_number, $name, $grade_level)`
- `archiveDetainee($conn, $detainee_id)`
- `getAllDetainees($conn, $includeArchived = false)`

### Subject Management Functions (`admin_functions_subjects.php`)

**Subjects:**
- `addSubject($conn, $subject_code, $title, $description)`
- `editSubject($conn, $subject_id, $subject_code, $title, $description)`
- `archiveSubject($conn, $subject_id)`
- `getAllSubjects($conn, $includeArchived = false)`

**Grade Levels:**
- `addGradeLevel($conn, $level)`
- `editGradeLevel($conn, $grade_level_id, $level)`
- `archiveGradeLevel($conn, $grade_level_id)`
- `getAllGradeLevels($conn, $includeArchived = false)`

### Provider Management Functions (`admin_functions_providers.php`)

**Providers:**
- `addProvider($conn, $id_number, $name, $provider_type)`
- `editProvider($conn, $provider_id, $id_number, $name, $provider_type)`
- `archiveProvider($conn, $provider_id)`
- `getAllProviders($conn, $includeArchived = false)`

---

## ✅ Data Validation Rules

All functions enforce:
1. **Unique ID Numbers** - Cannot have duplicate ID numbers
2. **Unique Names** - Cannot have duplicate names (case-insensitive comparison)
3. **Unique Codes** - Subject codes must be unique
4. **Unique Titles** - Subject titles must be unique
5. **Soft Delete** - All deletions archive records (archived = 1)

---

## 🔒 Security Features

- Session-based authentication
- Role-based access control (RBAC)
- SQL Prepared Statements (prevents SQL injection)
- Input validation and sanitization
- HTML escaping (htmlspecialchars)
- CSRF protection ready

---

## 📝 Usage Examples

### Creating a Teacher via Admin
```
1. Login as admin / admin123
2. Go to Admin Dashboard > Teachers tab
3. Fill in:
   - ID Number: "T001" (unique)
   - Name: "Juan Dela Cruz" (unique)
   - Position: "Math Teacher"
4. Click "Add Teacher"
5. Teacher can login with ID Number "T001" (no password in demo)
```

### Creating Modules (Teacher)
```
1. Teacher logs in with their ID Number
2. Go to "Upload Modules" tab
3. Fill in:
   - Module Title: "Algebra Basics"
   - Subject: "Mathematics"
   - Grade Level: "Grade 7"
4. Upload PDF file
5. Module is available to detainees of that grade level
```

### Distributing Materials (Facilitator)
```
1. Facilitator logs in
2. Go to "Distribute" tab
3. Select Module/Activity
4. Check multiple detainees
5. Click "Distribute"
6. Record is logged
```

---

## 🐛 Troubleshooting

**Problem:** Can't access admin dashboard
- **Solution:** Make sure you're logged in as admin and role is set correctly

**Problem:** Duplicate name error
- **Solution:** Names are case-insensitive, check for existing names

**Problem:** Files not uploading
- **Solution:** Check if upload directories exist and are writable

**Problem:** Can't find grade level dropdown
- **Solution:** Create grade levels in Admin > Grade Levels first

---

## 📞 Support

For issues or questions, contact the development team.

---

**Version:** 1.0
**Last Updated:** December 2, 2025
