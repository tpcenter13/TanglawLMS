# Activity Submissions — Tanglaw LMS

This adds a lightweight activity submission feature to allow students to submit activity sheets to the Tanglaw Facilitator.

What was added
- `submit_activity.php` — Form to upload an activity sheet (PDF, DOC, DOCX, JPG, PNG) and submit it to the facilitator.
- `my_submissions.php` — List of student's previous submissions, with links to view files and basic status.
- `uploads/activity_sheets/` — Directory to store uploaded files.
- The `student_modules.php` page now includes a link to submit an activity sheet and a link to view submitted sheets.

Database
- `submit_activity.php` automatically creates an `activity_submissions` table if it does not already exist. Schema:

    CREATE TABLE activity_submissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        module_id INT NOT NULL,
        file_path VARCHAR(255) NOT NULL,
        comments TEXT,
        status VARCHAR(50) DEFAULT 'submitted',
        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

Security & notes
- File uploads are limited to PDF, DOC, DOCX, JPG, PNG and size max 10 MB. Adjust as needed in `submit_activity.php`.
- Uploaded files are stored in `uploads/activity_sheets/` — ensure that directory permissions are correct on your server.
- For production, prefer storing uploads outside webroot and serve with a controller that checks permissions.

 Next steps (optional)
 - Add email or notification to the facilitator on submission
 - Add a facilitator portal to review and grade submissions
 - Add deletion / resubmission flows

 Student Dashboard
 - `student_dashboard.php` — aggregates: student info, modules count, submissions count, recent modules and recent submissions. Quick links to `student_modules.php`, `submit_activity.php` and `my_submissions.php`.

Design
- `assets/css/style.css` — small CSS to provide a cleaner UI and consistent look across pages.
- `header.php` and `footer.php` — shared page header and footer with navigation links for quick access.

