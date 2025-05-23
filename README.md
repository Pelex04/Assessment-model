# Assessment-model
Assessment-model
Overview
The Assessment-model is a web-based application for managing academic records and assessments at a university. It supports administrators, lecturers, and students. Administrators manage users, courses, and programs; lecturers assign and compute grades; and students view their enrolled subjects, grades, and academic progress. A reporting module provides insights into academic performance.
Features

Admin Dashboard:
CRUD operations for lecturers, students, subjects, and programs.
Search functionality and paginated record tables.


Lecturer Dashboard:
Login with surname and staff ID (e.g., Don Mkavea, D1; Goodall Nyirenda, D2; Hope Chilunga, D3; Mike Chinguwo, D4; Fatima Abdalla, D5; Robert Ngugi, D6).
View assigned subjects, assign assessment grades, compute final grades, and review grades.


Student Dashboard:
Login with first name and last name.
View enrolled subjects, assessment grades, final grades, personal details, and academic progress with a course completion progress bar.


Reports Module:
SQL-based reports, including:
Students repeating the year (failed 2+ modules).
Students who passed DMS-301 but failed OPS-302.
DSA-301 performance by gender.
Students with distinction average (≥75).
Subjects by program and lecturer.
BIS program gradebook.




Responsive Design:
CSS styling with fixed headers, student sidebar, and responsive tables.



Prerequisites

PHP >= 7.4
MySQL or MariaDB (Server version: 10.4.32-MariaDB)
Web server (e.g., Apache, Nginx)
Browser (e.g., Chrome, Firefox)

Installation

Clone the Repository:git clone https://github.com/Pelex04/Assessment-model.git


Navigate to the Project Directory:cd Assessment-model


Set Up the Database:
Create a MySQL database named student_assessment_db.
Import the provided SQL file (student_assessment_db (3).sql) to set up the schema and data.
Update database connection settings in PHP files ($host, $dbname, $username, $password) if needed.


Configure the Web Server:
Place project files in the web server's root directory (e.g., /var/www/html for Apache).
Ensure PHP and MySQL support.


Access the Application:
Navigate to http://localhost/Assessment-model/welcome.html in a browser.
Select a role (Student or Lecturer) or access admin_page.php for admin functions.



Usage

Welcome Page (welcome.html):
Choose a role to access student or lecturer login.


Admin Access (admin_page.php):
Go to http://localhost/Assessment-model/admin_page.php.
Use the sidebar to manage users, courses, programs, or settings; search records or add new ones.


Lecturer Access (lecturer_page.php):
Login with surname and staff ID (e.g., Mkavea, D1; Nyirenda, D2; Chilunga, D3; Chinguwo, D4; Abdalla, D5; Ngugi, D6).
Manage assigned subjects, grades, and final grade computations.


Student Access (student_page.php):
Login with first name and last name.
View dashboard, courses, grades, personal details, or academic progress via the sidebar.


Reports (report4.php):
Access http://localhost/Assessment-model/report4.php.
Select a report query from the dropdown to view results.



Database Details

Name: student_assessment_db
Key Tables:
lecturers: Lecturer details (e.g., Don Mkavea, D1; Goodall Nyirenda, D2).
students: Student details (reg_number, first_name, last_name, program_id).
subjects: Subject details (e.g., DMS-301, OPS-301).
programs: Program details (BIS, BIT).
student_grades: Individual assessment scores.
final_grades: Computed final grades with pass/fail status.
assessment_types: Assessment types (e.g., Assignment 1, Mid Term, Examination).
assessment_weights: Assessment weights per subject.
program_subjects: Subject-program-semester mappings.
subject_lecturer: Lecturer-subject assignments.


Triggers:
after_student_grade_insert and after_student_grade_update compute and update final grades in final_grades based on weighted scores.




For questions or feedback, contact Pelex04.

