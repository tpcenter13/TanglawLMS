<?php
include 'conn.php';
include 'admin_functions_users.php';
include 'admin_functions_subjects.php';
include 'admin_functions_providers.php';

// Check if user is admin
if (!isset($_SESSION['loggedUser']) || $_SESSION['loggedUser']['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get current section
$section = $_GET['section'] ?? 'dashboard';
$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    // ====== TEACHER MANAGEMENT ======
    if ($action == 'edit_teacher') {
        if (empty($_POST['teacher_id']) || empty($_POST['id_number']) || empty($_POST['name'])) {
            $message = '❌ Please fill in all required fields.';
        } elseif (empty($_POST['provider_id'])) {
            $message = '❌ School/Provider is required for teachers.';
        } elseif (!empty($_POST['new_password']) && strlen($_POST['new_password']) < 8) {
            $message = '❌ Password must be at least 8 characters long.';
        } else {
            $result = editTeacher(
                $conn,
                $_POST['teacher_id'],
                $_POST['id_number'],
                $_POST['name'],
                $_POST['email'] ?? '',
                $_POST['position'] ?? '',
                $_POST['provider_id'] ?? null,
                $_POST['level'] ?? null,
                null,
                !empty($_POST['new_password']) ? $_POST['new_password'] : null
            );
            $message = $result['message'];
            $section = 'teachers';
        }
    }
    
    if ($action == 'add_teacher') {
        if (empty($_POST['id_number']) || empty($_POST['name'])) {
            $message = '❌ Please fill in all required fields.';
        } elseif (empty($_POST['provider_id'])) {
            $message = '❌ School/Provider is required for teachers.';
        } elseif (!empty($_POST['password']) && strlen($_POST['password']) < 8) {
            $message = '❌ Password must be at least 8 characters long.';
        } else {
            $result = addTeacher(
                $conn,
                $_POST['id_number'],
                $_POST['name'],
                $_POST['email'] ?? '',
                $_POST['position'] ?? '',
                !empty($_POST['provider_id']) ? (int)$_POST['provider_id'] : null,
                $_POST['level'] ?? null,
                null,
                !empty($_POST['password']) ? $_POST['password'] : null
            );
            $message = $result['message'];
            $section = 'teachers';
        }
    }
    
    if ($action == 'archive_teacher') {
        if (empty($_POST['teacher_id'])) {
            $message = '❌ Please fill in all fields.';
        } else {
            $result = archiveTeacher($conn, $_POST['teacher_id']);
            $message = $result['message'];
        }
    }
    
    // ====== FACILITATOR MANAGEMENT ======
    if ($action == 'add_facilitator') {
        if (empty($_POST['id_number']) || empty($_POST['name'])) {
            $message = '❌ Please fill in all required fields.';
        } elseif (!empty($_POST['password']) && strlen($_POST['password']) < 8) {
            $message = '❌ Password must be at least 8 characters long.';
        } else {
            $result = addFacilitator($conn, $_POST['id_number'], $_POST['name'], $_POST['email'] ?? '', $_POST['position'] ?? '', $_POST['employment_status'] ?? '', !empty($_POST['password']) ? $_POST['password'] : null);
            $message = $result['message'];
        }
    }
    
    if ($action == 'edit_facilitator') {
        if (empty($_POST['facilitator_id']) || empty($_POST['id_number']) || empty($_POST['name'])) {
            $message = '❌ Please fill in all required fields.';
        } elseif (!empty($_POST['new_password']) && strlen($_POST['new_password']) < 8) {
            $message = '❌ Password must be at least 8 characters long.';
        } else {
            $result = editFacilitator($conn, $_POST['facilitator_id'], $_POST['id_number'], $_POST['name'], $_POST['email'] ?? '', $_POST['position'] ?? '', $_POST['employment_status'] ?? '', !empty($_POST['new_password']) ? $_POST['new_password'] : null);
            $message = $result['message'];
        }
    }
    
    if ($action == 'archive_facilitator') {
        if (empty($_POST['facilitator_id'])) {
            $message = '❌ Please fill in all fields.';
        } else {
            $result = archiveFacilitator($conn, $_POST['facilitator_id']);
            $message = $result['message'];
        }
    }
    
    // ====== DETAINEE MANAGEMENT ======
    if ($action == 'add_detainee') {
        if (empty($_POST['id_number']) || empty($_POST['name']) || empty($_POST['grade_level'])) {
            $message = '❌ Please fill in all required fields.';
        } elseif (empty($_POST['school'])) {
            $message = '❌ School is required for students.';
        } elseif (!empty($_POST['password']) && strlen($_POST['password']) < 8) {
            $message = '❌ Password must be at least 8 characters long.';
        } else {
            $result = addDetainee($conn, $_POST['id_number'], $_POST['name'], $_POST['email'] ?? '', $_POST['grade_level'], $_POST['school'] ?? null, !empty($_POST['password']) ? $_POST['password'] : null);
            $message = $result['message'];
        }
    }
    
    if ($action == 'edit_detainee') {
        if (empty($_POST['detainee_id']) || empty($_POST['id_number']) || empty($_POST['name']) || empty($_POST['grade_level'])) {
            $message = '❌ Please fill in all required fields.';
        } elseif (empty($_POST['school'])) {
            $message = '❌ School is required for students.';
        } elseif (!empty($_POST['new_password']) && strlen($_POST['new_password']) < 8) {
            $message = '❌ Password must be at least 8 characters long.';
        } else {
            $result = editDetainee($conn, $_POST['detainee_id'], $_POST['id_number'], $_POST['name'], $_POST['email'] ?? '', $_POST['grade_level'], $_POST['school'] ?? null, !empty($_POST['new_password']) ? $_POST['new_password'] : null);
            $message = $result['message'];
        }
    }
    
    if ($action == 'archive_detainee') {
        if (empty($_POST['detainee_id'])) {
            $message = '❌ Please fill in all fields.';
        } else {
            $result = archiveDetainee($conn, $_POST['detainee_id']);
            $message = $result['message'];
        }
    }
    
    // ====== SUBJECT MANAGEMENT ======
    if ($action == 'add_subject') {
        $subject_file_path = null;
        if (!empty($_FILES['subject_file']['tmp_name'])) {
            $upload_dir = 'uploads/subjects/';
            if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }
            $fname = time() . '_' . basename($_FILES['subject_file']['name']);
            $target = $upload_dir . $fname;
            if (@move_uploaded_file($_FILES['subject_file']['tmp_name'], $target)) {
                $subject_file_path = $target;
            }
        }
        $result = addSubject($conn, $_POST['subject_code'], $_POST['title'], $_POST['description'], $_POST['level'] ?? null, $subject_file_path);
        $message = $result['message'];
    }
    
    if ($action == 'edit_subject') {
        $subject_file_path = null;
        if (!empty($_FILES['subject_file']['tmp_name'])) {
            $upload_dir = 'uploads/subjects/';
            if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }
            $fname = time() . '_' . basename($_FILES['subject_file']['name']);
            $target = $upload_dir . $fname;
            if (@move_uploaded_file($_FILES['subject_file']['tmp_name'], $target)) {
                $subject_file_path = $target;
            }
        }
        $result = editSubject($conn, $_POST['subject_id'], $_POST['subject_code'], $_POST['title'], $_POST['description'], $_POST['level'] ?? null, $subject_file_path);
        $message = $result['message'];
    }
    
    if ($action == 'archive_subject') {
        $result = archiveSubject($conn, $_POST['subject_id']);
        $message = $result['message'];
    }
    
    // ====== GRADE LEVEL MANAGEMENT ======
    if ($action == 'add_grade_level') {
        $result = addGradeLevel($conn, $_POST['level']);
        $message = $result['message'];
    }
    
    if ($action == 'edit_grade_level') {
        $result = editGradeLevel($conn, $_POST['grade_level_id'], $_POST['level']);
        $message = $result['message'];
    }
    
    if ($action == 'archive_grade_level') {
        $result = archiveGradeLevel($conn, $_POST['grade_level_id']);
        $message = $result['message'];
    }
    
    // ====== PROVIDER MANAGEMENT ======
    if ($action == 'add_provider') {
        $result = addProvider($conn, $_POST['id_number'], $_POST['name'], $_POST['provider_type']);
        $message = $result['message'];
    }
    
    if ($action == 'edit_provider') {
        $result = editProvider($conn, $_POST['provider_id'], $_POST['id_number'], $_POST['name'], $_POST['provider_type']);
        $message = $result['message'];
    }
    
    if ($action == 'archive_provider') {
        $result = archiveProvider($conn, $_POST['provider_id']);
        $message = $result['message'];
    }

    // ====== PASSWORD ACTIONS ======
    if ($action == 'send_reset_email') {
        $role = $_POST['role'] ?? '';
        $user_id = intval($_POST['user_id'] ?? 0);
        if ($role && $user_id) {
            if ($role === 'teacher') {
                $stmt = $conn->prepare("SELECT id_number, name, email FROM teachers WHERE id = ? LIMIT 1");
            } elseif ($role === 'facilitator') {
                $stmt = $conn->prepare("SELECT id_number, name, email FROM facilitators WHERE id = ? LIMIT 1");
            } else {
                $stmt = $conn->prepare("SELECT id_number, name, email FROM detainees WHERE id = ? LIMIT 1");
            }
            if ($stmt) {
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                $res = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if ($res && !empty($res['email'])) {
                    $token = createPasswordResetToken($conn, $role, $user_id);
                    if ($token) {
                        sendPasswordResetEmail($res['email'], $res['name'], $token);
                        $message = '✅ Password reset email sent to ' . htmlspecialchars($res['email']);
                    } else {
                        $message = '❌ Failed to create reset token';
                    }
                } else {
                    $message = '❌ User email not found';
                }
            }
        }
    }
}

// Get data
$teachers = getAllTeachers($conn);
$facilitators = getAllFacilitators($conn);
$detainees = getAllDetainees($conn);
$subjects = getAllSubjects($conn);
$grade_levels = getAllGradeLevels($conn);
$providers = getAllProviders($conn);

?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin Dashboard - Tanglaw LMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/portal-shared.css">
    <style>
        .admin-header {
            background: linear-gradient(120deg, var(--portal-primary), var(--portal-primary-2));
            color: white;
            padding: 18px 32px;
            border-radius: 0;
            position: fixed;
            top: 0;
            left: 280px;
            width: calc(100% - 280px);
            z-index: 300;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        body {
            overflow: hidden;
        }
        html {
            height: 100%;
        }
        .main-content { 
            margin-left: 280px;
            padding-top: 110px;
            padding-bottom: 60px;
            height: 100vh;
            overflow-y: auto;
        }
        .main-content .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 24px;
        }
        
        .section { 
            display: none; 
        }
        .section.active { 
            display: block;
            max-width: 100%;
            margin: 0;
        }
        .section.active .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }
        
        @media (max-width: 1200px) {
            .section.active .grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .section.active .grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 1024px) {
            .admin-header {
                left: 260px;
                width: calc(100% - 260px);
            }
            .main-content {
                margin-left: 260px;
                padding-top: 110px;
            }
        }

        @media (max-width: 900px) {
            .admin-header {
                left: 0;
                width: 100%;
            }
            .main-content {
                margin-left: 0;
                padding: 120px 16px 48px;
            }
        }

        .section.dashboard-scrollable .grid-wrapper {
            max-height: 60vh;
            overflow-y: auto;
            padding-right: 8px;
        }
        .section.active .grid .card {
            background: white;
            padding: 20px;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 12px 28px rgba(15,23,42,0.08);
        }
        .section h2 {
            margin-top: 0;
            margin-bottom: 24px;
        }
        .section .card {
            margin-top: 0;
            margin-bottom: 20px;
        }
        .section .grid {
            margin-top: 0;
            margin-bottom: 20px;
        }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px; }
        .form-row.full { grid-template-columns: 1fr; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-weight: 600; margin-bottom: 5px; }
        .form-group label .required { color: #dc2626; font-weight: 700; }
        .form-group input, .form-group select {
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
        }
        .form-group input:invalid:not(:placeholder-shown), 
        .form-group select:invalid:not(:placeholder-shown) {
            border-color: #dc2626;
        }
        .password-hint {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
        }
        button[type="submit"] {
            background:#4f772d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }
        button[type="submit"]:hover { background: #15803d; }
        .alert { padding: 12px; border-radius: 6px; margin-bottom: 15px; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        table thead { background: #f3f4f6; }
        table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        .btn-edit { padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; margin-right: 5px; display: inline-block; text-align: center; background: #2563eb; color: white; }
        .btn-delete { padding: 4px 8px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; margin-right: 5px; display: inline-block; text-align: center; background: #ef4444 !important; color: white; line-height: 1; vertical-align: middle; }
        .btn-edit:hover { background: #1d4ed8; }
        .btn-delete:hover { background: #dc2626 !important; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.4); }
        .modal.show { display: block; }
        .modal-content { background-color: white; margin: 10% auto; padding: 30px; border-radius: 8px; width: 500px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .modal-close { color: #999; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        .modal-close:hover { color: #000; }
        .modal h2 { margin-top: 0; }
        .action-buttons { white-space: nowrap; }

        /* ===== NOTIFICATION MODAL ===== */
        #notif_modal {
            z-index: 2000;
        }
        #notif_modal .modal-content {
            margin: 19% auto;
            width: 380px;
            max-width: 90%;
            padding: 36px 28px 28px;
            text-align: center;
            border-radius: 14px;
            position: relative;
            animation: notifPop 0.25s ease;
        }
        @keyframes notifPop {
            0%   { transform: scale(0.85); opacity: 0; }
            100% { transform: scale(1);    opacity: 1; }
        }
        #notif_modal .notif-close-btn {
            position: absolute;
            top: 10px;
            right: 16px;
            font-size: 24px;
            color: #999;
            cursor: pointer;
            background: none;
            border: none;
            line-height: 1;
        }
        #notif_modal .notif-close-btn:hover { color: #333; }
        #notif_modal .notif-icon  { font-size: 42px; margin-bottom: 8px; }
        #notif_modal .notif-text  { margin: 0; font-size: 16px; font-weight: 600; }

        /* progress bar at the bottom of the card */
        #notif_modal .notif-bar-wrap {
            margin-top: 18px;
            height: 4px;
            background: rgba(0,0,0,0.08);
            border-radius: 2px;
            overflow: hidden;
        }
        #notif_modal .notif-bar {
            height: 100%;
            width: 100%;
            border-radius: 2px;
            animation: notifDrain 3s linear forwards;
        }
        @keyframes notifDrain {
            0%   { width: 100%; }
            100% { width: 0%; }
        }
    </style>
    <script>
        // Client-side password validation
        function validatePassword(input) {
            if (input.value.length > 0 && input.value.length < 8) {
                input.setCustomValidity('Password must be at least 8 characters long');
            } else {
                input.setCustomValidity('');
            }
        }

        // Validate form before submission
        function validateForm(form) {
            const passwordInputs = form.querySelectorAll('input[type="password"]');
            let isValid = true;

            passwordInputs.forEach(input => {
                if (input.value.length > 0 && input.value.length < 8) {
                    alert('Password must be at least 8 characters long');
                    isValid = false;
                    input.focus();
                    return false;
                }
            });

            // Check required fields for teachers
            if (form.querySelector('input[name="action"][value="add_teacher"]') || 
                form.querySelector('input[name="action"][value="edit_teacher"]')) {
                const providerSelect = form.querySelector('select[name="provider_id"]');
                if (providerSelect && !providerSelect.value) {
                    alert('School/Provider is required for teachers');
                    providerSelect.focus();
                    return false;
                }
            }

            // Check required fields for students
            if (form.querySelector('input[name="action"][value="add_detainee"]') || 
                form.querySelector('input[name="action"][value="edit_detainee"]')) {
                const schoolSelect = form.querySelector('select[name="school"]');
                if (schoolSelect && !schoolSelect.value) {
                    alert('School is required for students');
                    schoolSelect.focus();
                    return false;
                }
            }

            return isValid;
        }

        function openModal(modalId) {
            document.getElementById(modalId).classList.add('show');
        }
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
        }
        window.onclick = function(event) {
            if (event.target.classList.contains('modal') && event.target.id !== 'notif_modal') {
                event.target.classList.remove('show');
            }
        }
        function editItem(type, data) {
            openModal(`edit_${type}_modal`);
            if (type == 'teacher') {
                document.getElementById('edit_teacher_id').value = data.id;
                document.getElementById('edit_teacher_id_number').value = data.id_number;
                document.getElementById('edit_teacher_name').value = data.name;
                document.getElementById('edit_teacher_email').value = data.email || '';
                document.getElementById('edit_teacher_position').value = data.position || '';
                if (document.getElementById('edit_teacher_provider_id')) {
                    document.getElementById('edit_teacher_provider_id').value = data.provider_id ? data.provider_id : '';
                }
                if (document.getElementById('edit_teacher_level')) {
                    document.getElementById('edit_teacher_level').value = data.level ? data.level : '';
                }
            } else if (type == 'facilitator') {
                document.getElementById('edit_facilitator_id').value = data.id;
                document.getElementById('edit_facilitator_id_number').value = data.id_number;
                document.getElementById('edit_facilitator_name').value = data.name;
                document.getElementById('edit_facilitator_email').value = data.email;
                document.getElementById('edit_facilitator_position').value = data.position;
                document.getElementById('edit_facilitator_employment_status').value = data.employment_status;
            } else if (type == 'detainee') {
                document.getElementById('edit_detainee_id').value = data.id;
                document.getElementById('edit_detainee_id_number').value = data.id_number;
                document.getElementById('edit_detainee_name').value = data.name;
                document.getElementById('edit_detainee_email').value = data.email;
                if (document.getElementById('edit_detainee_school')) {
                    document.getElementById('edit_detainee_school').value = data.school || '';
                }
                if (typeof setDetaineeGradeOptions === 'function') {
                    var schoolVal = document.getElementById('edit_detainee_school') ? document.getElementById('edit_detainee_school').value : '';
                    setDetaineeGradeOptions(schoolVal, document.getElementById('edit_detainee_grade_level'));
                }
                document.getElementById('edit_detainee_grade_level').value = data.grade_level;
            } else if (type == 'subject') {
                document.getElementById('edit_subject_id').value = data.id;
                document.getElementById('edit_subject_code').value = data.subject_code;
                document.getElementById('edit_subject_title').value = data.title;
                document.getElementById('edit_subject_description').value = data.description;
                if (document.getElementById('edit_subject_level')) {
                    document.getElementById('edit_subject_level').value = data.level || '';
                }
            } else if (type == 'grade_level') {
                document.getElementById('edit_grade_level_id').value = data.id;
                document.getElementById('edit_grade_level_level').value = data.level;
            } else if (type == 'provider') {
                document.getElementById('edit_provider_id').value = data.id;
                document.getElementById('edit_provider_id_number').value = data.id_number;
                document.getElementById('edit_provider_name').value = data.name;
                document.getElementById('edit_provider_type').value = data.provider_type;
            }
        }

        /* ===== show the notification modal ===== */
        function showNotifModal(message) {
            var isSuccess  = message.indexOf('✅') !== -1;
            var modal      = document.getElementById('notif_modal');
            var card       = modal.querySelector('.modal-content');
            var icon       = modal.querySelector('.notif-icon');
            var text       = modal.querySelector('.notif-text');
            var bar        = modal.querySelector('.notif-bar');

            // colours
            if (isSuccess) {
                card.style.background = '#dcfce7';
                card.style.border    = '1px solid #bbf7d0';
                text.style.color     = '#166534';
                bar.style.background = '#22c55e';
            } else {
                card.style.background = '#fee2e2';
                card.style.border    = '1px solid #fecaca';
                text.style.color     = '#991b1b';
                bar.style.background = '#ef4444';
            }

            icon.textContent = isSuccess ? '✅' : '❌';
            text.textContent = message;

            // restart progress-bar animation
            bar.style.animation = 'none';
            void bar.offsetWidth; // force reflow
            bar.style.animation = 'notifDrain 3s linear forwards';

            modal.style.display = 'block';

            // auto-close after 3 s
            setTimeout(function(){ modal.style.display = 'none'; }, 3000);
        }
    </script>
    <script>
        // Build grade arrays from PHP-provided grade levels
        var allGrades = [];
        <?php foreach($grade_levels as $gl): ?>
        allGrades.push(<?= json_encode($gl['level']) ?>);
        <?php endforeach; ?>

       function setDetaineeGradeOptions(school, selectEl) {
    if (!selectEl) return;

    var val = (school || '').trim().toLowerCase();
    var allowed = [];

    if (val.includes('marcelo') || val.includes('st') || val.includes('st. martin') || val.includes('st martin')) {
        allowed = allGrades.filter(g => 
            ['Grade 11', 'Grade 12', 'grade 11', 'grade 12']
                .some(pattern => g.toLowerCase() === pattern)
        );
    } 
    else if (val.includes('als')) {
        allowed = allGrades.filter(g => 
            ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 
             'grade 7', 'grade 8', 'grade 9', 'grade 10']
                .some(pattern => g.toLowerCase() === pattern)
        );
    } 
    else {
        allowed = allGrades.slice();
    }

    selectEl.innerHTML = '';

    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = 'Select Grade Level';
    selectEl.appendChild(placeholder);

    allowed.sort((a, b) => {
        const numA = parseInt(a.replace(/\D/g, '')) || 0;
        const numB = parseInt(b.replace(/\D/g, '')) || 0;
        return numA - numB;
    });

    allowed.forEach(g => {
        const opt = document.createElement('option');
        opt.value = g;
        opt.textContent = g;
        selectEl.appendChild(opt);
    });
}

        document.addEventListener('DOMContentLoaded', function(){
            var addSchool = document.getElementById('add_detainee_school');
            var addGrade = document.getElementById('add_detainee_grade_level');
            if (addSchool && addGrade) {
                addSchool.addEventListener('change', function(){ setDetaineeGradeOptions(this.value, addGrade); });
                setDetaineeGradeOptions(addSchool.value, addGrade);
            }

            // Add password validation listeners
            const passwordInputs = document.querySelectorAll('input[type="password"]');
            passwordInputs.forEach(input => {
                input.addEventListener('input', function() {
                    validatePassword(this);
                });
            });
        });
       
    </script>
    <script>
        function toggleSidebar() {
            var body = document.body;
            var backdrop = document.getElementById('sidebarBackdrop');
            if (window.innerWidth <= 900) {
                if (body.classList.contains('sidebar-open')) {
                    body.classList.remove('sidebar-open');
                } else {
                    body.classList.add('sidebar-open');
                }
            } else {
                body.classList.toggle('sidebar-collapsed');
            }
            if (backdrop) backdrop.style.display = body.classList.contains('sidebar-open') ? 'block' : 'none';
        }
        document.addEventListener('DOMContentLoaded', function(){
            document.body.classList.remove('sidebar-open');
            document.body.classList.remove('sidebar-collapsed');
        });
    </script>
</head>
<body class="role-admin">
<?php include 'sidebar.php'; ?>

<div class="sidebar-backdrop" id="sidebarBackdrop" onclick="toggleSidebar()" style="display:none"></div>

<header class="admin-header portal-header">
    <div style="display:flex;align-items:center;gap:12px">
        <a href="#" style="color:#fff;font-weight:700;text-decoration:none;font-size:18px">TANGLAW LEARN</a>
    </div>
    <div></div>
</header>

<div class="main-content portal-main">

    <!-- ===== NOTIFICATION MODAL (replaces the old top alert) ===== -->
    <div id="notif_modal" class="modal" style="display:none;">
        <div class="modal-content">
            <button class="notif-close-btn" onclick="document.getElementById('notif_modal').style.display='none'">&times;</button>
            <div class="notif-icon">✅</div>
            <p  class="notif-text"></p>
            <div class="notif-bar-wrap"><div class="notif-bar"></div></div>
        </div>
    </div>

    <!-- fire the modal if PHP set $message -->
    <?php if ($message): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function(){
            showNotifModal(<?= json_encode($message) ?>);
        });
    </script>
    <?php endif; ?>

    <!-- DASHBOARD SECTION -->
   <br> <br> <br>
    <div class="section <?= $section == 'dashboard' ? 'active' : '' ?>">
        <div class="grid">
            <div class="card">
                <div class="kpi"><?= count($teachers) ?></div>
                <p class="small">👨‍🏫 Teachers</p>
            </div>
            <div class="card">
                <div class="kpi"><?= count($facilitators) ?></div>
                <p class="small">👥 Facilitators</p>
            </div>
            <div class="card">
                <div class="kpi"><?= count($detainees) ?></div>
                <p class="small">👨‍🎓 Students</p>
            </div>
            <div class="card">
                <div class="kpi"><?= count($subjects) ?></div>
                <p class="small">📚 Subjects</p>
            </div>
            <div class="card">
                <div class="kpi"><?= count($grade_levels) ?></div>
                <p class="small">📊 Grade Levels</p>
            </div>
            <div class="card">
                <div class="kpi"><?= count($providers) ?></div>
                <p class="small">🏢 Providers</p>
            </div>
        </div>
    </div>

    <!-- TEACHERS SECTION -->
    <div class="section <?= $section == 'teachers' ? 'active' : '' ?>">
        <h2>👨‍🏫 Teacher Management</h2>
        
        <div class="card">
            <h3>Add New Teacher</h3>
            <form method="POST" onsubmit="return validateForm(this)">
                <input type="hidden" name="action" value="add_teacher">
                <div class="form-row">
                    <div class="form-group">
                        <label>ID Number <span class="required">*</span></label>
                        <input type="text" name="id_number" required>
                    </div>
                    <div class="form-group">
                        <label>Name <span class="required">*</span></label>
                        <input type="text" name="name" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" placeholder="user@example.com">
                    </div>
                </div>
                <div class="form-row full">
                    <div class="form-group">
                        <label>Position</label>
                        <input type="text" name="position" placeholder="e.g., Science Teacher">
                    </div>
                    <div class="form-group">
                        <label>School / Provider <span class="required">*</span></label>
                        <select name="provider_id" required>
                            <option value="">Select School/Provider</option>
                            <?php foreach($providers as $prov): ?>
                            <option value="<?= $prov['id'] ?>"><?= htmlspecialchars($prov['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row full">
                    <div class="form-group">
                        <label>Teaching Level</label>
                        <select name="level">
                            <option value="">Select level</option>
                            <option value="Elementary">Elementary</option>
                            <option value="High School">High School</option>
                            <option value="Senior High School">Senior High School</option>
                            <option value="College">College</option>
                        </select>
                    </div>
                </div>
                <div class="form-row full">
                    <div class="form-group">
                        <label>Password (optional)</label>
                        <input type="password" name="password" placeholder="Leave blank to auto-generate" minlength="8" oninput="validatePassword(this)">
                        <small class="password-hint">If blank, a random password will be generated. Minimum 8 characters required.</small>
                    </div>
                </div>
                <button type="submit">Add Teacher</button>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID Number</th>
                    <th>Name</th>
                    <th>Position</th>
                    <th>Level</th>
                    <th>School</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($teachers as $teacher): ?>
                <tr>
                    <td><?= htmlspecialchars($teacher['id_number']) ?></td>
                    <td><?= htmlspecialchars($teacher['name']) ?></td>
                    <td><?= htmlspecialchars($teacher['position'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($teacher['level'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($teacher['provider_name'] ?? '—') ?></td>
                    <td class="action-buttons">
                        <button class="btn-edit" onclick="editItem('teacher', <?= htmlspecialchars(json_encode($teacher)) ?>)">✎ Edit</button>
                        <form style="display:inline;" method="POST" onsubmit="return confirm('Delete this teacher?')">
                            <input type="hidden" name="action" value="archive_teacher">
                            <input type="hidden" name="teacher_id" value="<?= $teacher['id'] ?>">
                            <button class="btn-delete" type="submit">🗑 Delete</button>
                        </form>
                        <form style="display:inline; margin-left:6px;" method="POST" onsubmit="return confirm('Send password reset email to this teacher?')">
                            <input type="hidden" name="action" value="send_reset_email">
                            <input type="hidden" name="role" value="teacher">
                            <input type="hidden" name="user_id" value="<?= $teacher['id'] ?>">
                            <button type="submit" style="background:#06b6d4;color:white;border:0;padding:6px;border-radius:6px;">📧 Reset</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Edit Teacher Modal -->
    <div id="edit_teacher_modal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModal('edit_teacher_modal')">&times;</span>
            <h2>Edit Teacher</h2>
            <form method="POST" enctype="multipart/form-data" onsubmit="return validateForm(this)">
                <input type="hidden" name="action" value="edit_teacher">
                <input type="hidden" name="teacher_id" id="edit_teacher_id">
                <div class="form-row">
                    <div class="form-group">
                        <label>ID Number <span class="required">*</span></label>
                        <input type="text" name="id_number" id="edit_teacher_id_number" required>
                    </div>
                    <div class="form-group">
                        <label>Name <span class="required">*</span></label>
                        <input type="text" name="name" id="edit_teacher_name" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" id="edit_teacher_email" placeholder="user@example.com">
                    </div>
                </div>
                <div class="form-row full">
                    <div class="form-group">
                        <label>Position</label>
                        <input type="text" name="position" id="edit_teacher_position" placeholder="e.g., Science Teacher">
                    </div>
                    <div class="form-group">
                        <label>School / Provider <span class="required">*</span></label>
                        <select name="provider_id" id="edit_teacher_provider_id" required>
                            <option value="">Select School/Provider</option>
                            <?php foreach($providers as $prov): ?>
                            <option value="<?= $prov['id'] ?>"><?= htmlspecialchars($prov['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row full">
                    <div class="form-group">
                        <label>Teaching Level</label>
                        <select name="level" id="edit_teacher_level">
                            <option value="">Select level</option>
                            <option value="Elementary">Elementary</option>
                            <option value="High School">High School</option>
                            <option value="Senior High School">Senior High School</option>
                            <option value="College">College</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Set new password</label>
                        <input type="password" name="new_password" id="edit_teacher_new_password" placeholder="Leave blank to keep current password" minlength="8" oninput="validatePassword(this)">
                        <small class="password-hint">Minimum 8 characters required.</small>
                    </div>
                </div>
                <button type="submit">Update Teacher</button>
            </form>
        </div>
    </div>


    <!-- FACILITATORS SECTION -->
    <div class="section <?= $section == 'facilitators' ? 'active' : '' ?>">
        <h2>👥 Facilitator Management</h2>
        
        <div class="card">
            <h3>Add New Facilitator</h3>
            <form method="POST" onsubmit="return validateForm(this)">
                <input type="hidden" name="action" value="add_facilitator">
                <div class="form-row">
                    <div class="form-group">
                        <label>ID Number <span class="required">*</span></label>
                        <input type="text" name="id_number" required>
                    </div>
                    <div class="form-group">
                        <label>Name <span class="required">*</span></label>
                        <input type="text" name="name" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" placeholder="user@example.com">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Position</label>
                        <input type="text" name="position">
                    </div>
                    <div class="form-group">
                        <label>Employment Status</label>
                        <select name="employment_status">
                            <option>Full-time</option>
                            <option>Part-time</option>
                            <option>Contract</option>
                        </select>
                    </div>
                </div>
                <div class="form-row full">
                    <div class="form-group">
                        <label>Password (optional)</label>
                        <input type="password" name="password" placeholder="Leave blank to auto-generate" minlength="8" oninput="validatePassword(this)">
                        <small class="password-hint">If blank, a random password will be generated. Minimum 8 characters required.</small>
                    </div>
                </div>
                <button type="submit">Add Facilitator</button>
            </form>
        </div>


        <table>
            <thead>
                <tr>
                    <th>ID Number</th>
                    <th>Name</th>
                    <th>Position</th>
                    <th>Employment Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($facilitators as $fac): ?>
                <tr>
                    <td><?= htmlspecialchars($fac['id_number']) ?></td>
                    <td><?= htmlspecialchars($fac['name']) ?></td>
                    <td><?= htmlspecialchars($fac['position'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($fac['employment_status'] ?? 'N/A') ?></td>
                    <td class="action-buttons">
                        <button class="btn-edit" onclick="editItem('facilitator', <?= htmlspecialchars(json_encode($fac)) ?>)">✎ Edit</button>
                        <form style="display:inline;" method="POST" onsubmit="return confirm('Delete this facilitator?')">
                            <input type="hidden" name="action" value="archive_facilitator">
                            <input type="hidden" name="facilitator_id" value="<?= $fac['id'] ?>">
                            <button class="btn-delete" type="submit">🗑 Delete</button>
                        </form>
                        <form style="display:inline; margin-left:6px;" method="POST" onsubmit="return confirm('Send password reset email to this facilitator?')">
                            <input type="hidden" name="action" value="send_reset_email">
                            <input type="hidden" name="role" value="facilitator">
                            <input type="hidden" name="user_id" value="<?= $fac['id'] ?>">
                            <button type="submit" style="background:#06b6d4;color:white;border:0;padding:6px;border-radius:6px;">📧 Reset</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Edit Facilitator Modal -->
    <div id="edit_facilitator_modal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModal('edit_facilitator_modal')">&times;</span>
            <h2>Edit Facilitator</h2>
            <form method="POST" onsubmit="return validateForm(this)">
                <input type="hidden" name="action" value="edit_facilitator">
                <input type="hidden" name="facilitator_id" id="edit_facilitator_id">
                <div class="form-row">
                    <div class="form-group">
                        <label>ID Number <span class="required">*</span></label>
                        <input type="text" name="id_number" id="edit_facilitator_id_number" required>
                    </div>
                    <div class="form-group">
                        <label>Name <span class="required">*</span></label>
                        <input type="text" name="name" id="edit_facilitator_name" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" id="edit_facilitator_email" placeholder="user@example.com">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Position</label>
                        <input type="text" name="position" id="edit_facilitator_position">
                    </div>
                    <div class="form-group">
                        <label>Employment Status</label>
                        <select name="employment_status" id="edit_facilitator_employment_status">
                            <option>Full-time</option>
                            <option>Part-time</option>
                            <option>Contract</option>
                        </select>
                    </div>
                </div>
                <div class="form-row full">
                    <div class="form-group">
                        <label>Set new password</label>
                        <input type="password" name="new_password" id="edit_facilitator_new_password" placeholder="Leave blank to keep current password" minlength="8" oninput="validatePassword(this)">
                        <small class="password-hint">Minimum 8 characters required.</small>
                    </div>
                </div>
                <button type="submit">Update Facilitator</button>
            </form>
        </div>
    </div>

    <!-- DETAINEES SECTION -->
    <div class="section <?= $section == 'detainees' ? 'active' : '' ?>">
        <h2>👨‍🎓 Student Management</h2>
        
        <div class="card">
            <h3>Add New Student</h3>
            <form method="POST" onsubmit="return validateForm(this)">
                <input type="hidden" name="action" value="add_detainee">
                <div class="form-row">
                    <div class="form-group">
                        <label>ID Number <span class="required">*</span></label>
                        <input type="text" name="id_number" required>
                    </div>
                    <div class="form-group">
                        <label>Name <span class="required">*</span></label>
                        <input type="text" name="name" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" placeholder="user@example.com">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>School <span class="required">*</span></label>
                        <select name="school" id="add_detainee_school" required>
                            <option value="">Select School</option>
                            <option value="Marcelo Facility">Marcelo Facility</option>
                            <option value="St. Martin Center">St. Martin Center</option>
                            <option value="DepEd ALS Program">DepEd ALS Program</option>
                            <option value="Other">Other / Community</option>
                        </select>
                    </div>
                </div>
                <div class="form-row full">
                    <div class="form-group">
                        <label>Grade Level <span class="required">*</span></label>
                        <select name="grade_level" id="add_detainee_grade_level" required>
                            <option value="">Select Grade Level</option>
                            <?php foreach($grade_levels as $gl): ?>
                            <option><?= htmlspecialchars($gl['level']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row full">
                    <div class="form-group">
                        <label>Password (optional)</label>
                        <input type="password" name="password" placeholder="Leave blank to auto-generate" minlength="8" oninput="validatePassword(this)">
                        <small class="password-hint">If blank, a random password will be generated. Minimum 8 characters required.</small>
                    </div>
                </div>
                <button type="submit">Add Student</button>
            </form>
        </div>


        <?php
            $detaineeGroups = [];
            foreach ($detainees as $det) {
                $school = trim((string)($det['school'] ?? ''));
                if ($school === '') $school = 'Unspecified';
                if (!isset($detaineeGroups[$school])) $detaineeGroups[$school] = [];
                $detaineeGroups[$school][] = $det;
            }

            foreach ($detaineeGroups as $schoolName => $group) :
        ?>
        <h3 style="margin-top:18px;">🏫 <?= htmlspecialchars($schoolName) ?> (<?= count($group) ?>)</h3>
        <table>
            <thead>
                <tr>
                    <th>ID Number</th>
                    <th>Name</th>
                    <th>Grade Level</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($group as $det): ?>
                <tr>
                    <td><?= htmlspecialchars($det['id_number']) ?></td>
                    <td><?= htmlspecialchars($det['name']) ?></td>
                    <td><?= htmlspecialchars($det['grade_level'] ?? 'N/A') ?></td>
                    <td class="action-buttons">
                        <button class="btn-edit" onclick="editItem('detainee', <?= htmlspecialchars(json_encode($det)) ?>)">✎ Edit</button>
                        <form style="display:inline;" method="POST" onsubmit="return confirm('Delete this detainee?')">
                            <input type="hidden" name="action" value="archive_detainee">
                            <input type="hidden" name="detainee_id" value="<?= $det['id'] ?>">
                            <button class="btn-delete" type="submit">🗑 Delete</button>
                        </form>
                        <form style="display:inline; margin-left:6px;" method="POST" onsubmit="return confirm('Send password reset email to this detainee?')">
                            <input type="hidden" name="action" value="send_reset_email">
                            <input type="hidden" name="role" value="detainee">
                            <input type="hidden" name="user_id" value="<?= $det['id'] ?>">
                            <button type="submit" style="background:#06b6d4;color:white;border:0;padding:6px;border-radius:6px;">📧 Reset</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endforeach; ?>
    </div>

    <!-- Edit Detainee Modal -->
    <div id="edit_detainee_modal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModal('edit_detainee_modal')">&times;</span>
            <h2>Edit Student</h2>
            <form method="POST" onsubmit="return validateForm(this)">
                <input type="hidden" name="action" value="edit_detainee">
                <input type="hidden" name="detainee_id" id="edit_detainee_id">
                <div class="form-row">
                    <div class="form-group">
                        <label>ID Number <span class="required">*</span></label>
                        <input type="text" name="id_number" id="edit_detainee_id_number" required>
                    </div>
                    <div class="form-group">
                        <label>Name <span class="required">*</span></label>
                        <input type="text" name="name" id="edit_detainee_name" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" id="edit_detainee_email" placeholder="user@example.com">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>School <span class="required">*</span></label>
                        <select name="school" id="edit_detainee_school" onchange="setDetaineeGradeOptions(this.value, document.getElementById('edit_detainee_grade_level'))" required>
                            <option value="">Select School</option>
                            <option value="Marcelo Facility">Marcelo Facility</option>
                            <option value="St. Martin Center">St. Martin Center</option>
                            <option value="DepEd ALS Program">DepEd ALS Program</option>
                            <option value="Other">Other / Community</option>
                        </select>
                    </div>
                </div>
                <div class="form-row full">
                    <div class="form-group">
                        <label>Grade Level <span class="required">*</span></label>
                        <select name="grade_level" id="edit_detainee_grade_level" required>
                            <option value="">Select Grade Level</option>
                            <?php foreach($grade_levels as $gl): ?>
                            <option><?= htmlspecialchars($gl['level']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row full">
                    <div class="form-group">
                        <label>Set new password</label>
                        <input type="password" name="new_password" id="edit_detainee_new_password" placeholder="Leave blank to keep current password" minlength="8" oninput="validatePassword(this)">
                        <small class="password-hint">Minimum 8 characters required.</small>
                    </div>
                </div>
                <button type="submit">Update Student</button>
            </form>
        </div>
    </div>

    <!-- SUBJECTS SECTION -->
  <div class="section <?= $section == 'subjects' ? 'active' : '' ?>">
    <h2>📚 Subject Management</h2>
    
    <div class="card">
        <h3>Add New Subject</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_subject">
            <div class="form-row">
                <div class="form-group">
                    <label>Subject Code <span class="required">*</span></label>
                    <input type="text" name="subject_code" required>
                </div>
                <div class="form-group">
                    <label>Title <span class="required">*</span></label>
                    <input type="text" name="title" required>
                </div>
            </div>
            <div class="form-row full">
                <div class="form-group">
                    <label>Description</label>
                    <input type="text" name="description" placeholder="Optional description">
                </div>
            </div>
            <div class="form-row full">
                <div class="form-group">
                    <label>Level</label>
                    <select name="level">
                        <option value="">Select level</option>
                        <option value="Elementary">Elementary</option>
                        <option value="High School">High School</option>
                        <option value="Senior High School">Senior High School</option>
                        <option value="College">College</option>
                    </select>
                </div>
            </div>
            <button type="submit">Add Subject</button>
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>Code</th>
                <th>Title</th>
                <th>Level</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($subjects as $subj): ?>
            <tr>
                <td><?= htmlspecialchars($subj['subject_code']) ?></td>
                <td><?= htmlspecialchars($subj['title']) ?></td>
                <td><?= htmlspecialchars($subj['level'] ?? '—') ?></td>
                <td><?= htmlspecialchars($subj['description'] ?? '') ?></td>
                <td class="action-buttons">
                    <button class="btn-edit" onclick="editItem('subject', <?= htmlspecialchars(json_encode($subj)) ?>)">✎ Edit</button>
                    <form style="display:inline;" method="POST" onsubmit="return confirm('Delete this subject?')">
                        <input type="hidden" name="action" value="archive_subject">
                        <input type="hidden" name="subject_id" value="<?= $subj['id'] ?>">
                        <button class="btn-delete" type="submit">🗑 Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

    <!-- Edit Subject Modal -->
    <div id="edit_subject_modal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModal('edit_subject_modal')">&times;</span>
            <h2>Edit Subject</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit_subject">
                <input type="hidden" name="subject_id" id="edit_subject_id">
                <div class="form-row">
                    <div class="form-group">
                        <label>Subject Code <span class="required">*</span></label>
                        <input type="text" name="subject_code" id="edit_subject_code" required>
                    </div>
                    <div class="form-group">
                        <label>Title <span class="required">*</span></label>
                        <input type="text" name="title" id="edit_subject_title" required>
                    </div>
                </div>
                <div class="form-row full">
                    <div class="form-group">
                        <label>Description</label>
                        <input type="text" name="description" id="edit_subject_description" placeholder="Optional description">
                    </div>
                </div>
                <div class="form-row full">
                    <div class="form-group">
                        <label>Level</label>
                        <select name="level" id="edit_subject_level">
                            <option value="">Select level</option>
                            <option value="Elementary">Elementary</option>
                            <option value="High School">High School</option>
                            <option value="Senior High School">Senior High School</option>
                            <option value="College">College</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Dropbox (Upload file)</label>
                        <input type="file" name="subject_file" accept=".pdf,.doc,.docx,.zip,.ppt,.pptx">
                    </div>
                </div>
                <button type="submit">Update Subject</button>
            </form>
        </div>
    </div>

    <!-- GRADE LEVELS SECTION -->
    <div class="section <?= $section == 'grades' ? 'active' : '' ?>">
        <h2>📊 Grade Level Management</h2>
        
        <div class="card">
            <h3>Add New Grade Level</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_grade_level">
                <div class="form-row full">
                    <div class="form-group">
                        <label>Level <span class="required">*</span></label>
                        <input type="text" name="level" placeholder="e.g., Grade 7, Grade 8, Grade 9..." required>
                    </div>
                </div>
                <button type="submit">Add Grade Level</button>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Level</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($grade_levels as $gl): ?>
                <tr>
                    <td><?= htmlspecialchars($gl['level']) ?></td>
                    <td class="action-buttons">
                        <button class="btn-edit" onclick="editItem('grade_level', <?= htmlspecialchars(json_encode($gl)) ?>)">✎ Edit</button>
                        <form style="display:inline;" method="POST" onsubmit="return confirm('Delete this grade level?')">
                            <input type="hidden" name="action" value="archive_grade_level">
                            <input type="hidden" name="grade_level_id" value="<?= $gl['id'] ?>">
                            <button class="btn-delete" type="submit">🗑 Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Edit Grade Level Modal -->
    <div id="edit_grade_level_modal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModal('edit_grade_level_modal')">&times;</span>
            <h2>Edit Grade Level</h2>
            <form method="POST">
                <input type="hidden" name="action" value="edit_grade_level">
                <input type="hidden" name="grade_level_id" id="edit_grade_level_id">
                <div class="form-row full">
                    <div class="form-group">
                        <label>Level <span class="required">*</span></label>
                        <input type="text" name="level" id="edit_grade_level_level" placeholder="e.g., Grade 7, Grade 8, Grade 9..." required>
                    </div>
                </div>
                <button type="submit">Update Grade Level</button>
            </form>
        </div>
    </div>

    <!-- PROVIDERS SECTION -->
    <div class="section <?= $section == 'providers' ? 'active' : '' ?>">
        <h2>🏢 Provider Management</h2>
        
        <div class="card">
            <h3>Add New Provider</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_provider">
                <div class="form-row">
                    <div class="form-group">
                        <label>ID Number <span class="required">*</span></label>
                        <input type="text" name="id_number" required>
                    </div>
                    <div class="form-group">
                        <label>Name <span class="required">*</span></label>
                        <input type="text" name="name" required>
                    </div>
                </div>
                <div class="form-row full">
                    <div class="form-group">
                        <label>Provider Type</label>
                        <select name="provider_type">
                            <option>Private</option>
                            <option>Public</option>
                            <option>Semi Private</option>
                        </select>
                    </div>
                </div>
                <button type="submit">Add Provider</button>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID Number</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($providers as $prov): ?>
                <tr>
                    <td><?= htmlspecialchars($prov['id_number']) ?></td>
                    <td><?= htmlspecialchars($prov['name']) ?></td>
                    <td><?= htmlspecialchars($prov['provider_type']) ?></td>
                    <td class="action-buttons">
                        <button class="btn-edit" onclick="editItem('provider', <?= htmlspecialchars(json_encode($prov)) ?>)">✎ Edit</button>
                        <form style="display:inline;" method="POST" onsubmit="return confirm('Delete this provider?')">
                            <input type="hidden" name="action" value="archive_provider">
                            <input type="hidden" name="provider_id" value="<?= $prov['id'] ?>">
                            <button class="btn-delete" type="submit">🗑 Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Edit Provider Modal -->
    <div id="edit_provider_modal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModal('edit_provider_modal')">&times;</span>
            <h2>Edit Provider</h2>
            <form method="POST">
                <input type="hidden" name="action" value="edit_provider">
                <input type="hidden" name="provider_id" id="edit_provider_id">
                <div class="form-row">
                    <div class="form-group">
                        <label>ID Number <span class="required">*</span></label>
                        <input type="text" name="id_number" id="edit_provider_id_number" required>
                    </div>
                    <div class="form-group">
                        <label>Name <span class="required">*</span></label>
                        <input type="text" name="name" id="edit_provider_name" required>
                    </div>
                </div>
                <div class="form-row full">
                    <div class="form-group">
                        <label>Provider Type</label>
                        <select name="provider_type" id="edit_provider_type">
                            <option>Private</option>
                            <option>Public</option>
                            <option>Semi Private</option>
                        </select>
                    </div>
                </div>
                <button type="submit">Update Provider</button>
            </form>
        </div>
    </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/@emailjs/browser@3/dist/email.min.js"></script>
<script>
// EmailJS Configuration
const EMAILJS_PUBLIC_KEY = "bjKEcCXpriGPTWoIB";
const EMAILJS_SERVICE_ID = "service_4viy27k";
const EMAILJS_TEMPLATE_ID = "template_1axzswl";

// Initialize EmailJS
emailjs.init(EMAILJS_PUBLIC_KEY);
console.log('✅ EmailJS initialized');

// Intercept all password reset form submissions
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔍 Looking for reset forms...');
    
    const allForms = document.querySelectorAll('form');
    console.log('Found', allForms.length, 'forms total');
    
    allForms.forEach((form, index) => {
        const actionInput = form.querySelector('input[name="action"][value="send_reset_email"]');
        
        if (actionInput) {
            console.log('✅ Found reset form #' + index);
            
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                console.log('🚀 Reset form submitted!');
                
                const formData = new FormData(this);
                const role = formData.get('role');
                const userId = formData.get('user_id');
                
                console.log('=== Password Reset via EmailJS ===');
                console.log('Role:', role);
                console.log('User ID:', userId);
                
                const submitButton = this.querySelector('button[type="submit"]');
                const originalText = submitButton.innerHTML;
                submitButton.disabled = true;
                submitButton.innerHTML = '⏳ Sending...';
                
                fetch('admin_reset_handler.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=generate_reset_token&role=' + encodeURIComponent(role) + '&user_id=' + encodeURIComponent(userId)
                })
                .then(response => {
                    console.log('📡 Got response from PHP');
                    return response.json();
                })
                .then(data => {
                    console.log('📦 PHP Response:', data);
                    
                    if (data.success) {
                        const resetLink = window.location.origin + 
                                        window.location.pathname.replace('admin_dashboard.php', 'reset_password.php') + 
                                        '?token=' + data.token;
                        
                        const templateParams = {
                            to_email: data.email,
                            name: data.name,
                            message: resetLink,
                            email: data.email,
                            year: new Date().getFullYear()
                        };
                        
                        console.log('📧 Sending email to:', data.email);
                        console.log('🔗 Reset link:', resetLink);
                        console.log('📨 Template params:', templateParams);
                        
                        return emailjs.send(EMAILJS_SERVICE_ID, EMAILJS_TEMPLATE_ID, templateParams);
                    } else {
                        throw new Error(data.message || 'Failed to generate reset token');
                    }
                })
                .then(response => {
                    console.log('✅ EmailJS SUCCESS!', response);
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalText;
                    showNotifModal('✅ Password reset email sent successfully!');
                })
                .catch(error => {
                    console.error('❌ Error:', error);
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalText;
                    showNotifModal('❌ Failed to send reset email: ' + error.message);
                });
                
                return false;
            });
        }
    });
});
</script>
</body>
</html>