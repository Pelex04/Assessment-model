<?php
session_start();
$host = 'localhost';
$dbname = 'student_assessment_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Determine active section based on URL parameter
$active_section = isset($_GET['section']) ? $_GET['section'] : 'dashboard';

// Search functionality
$search_results = [];
if (isset($_POST['search'])) {
    $search_term = '%' . $_POST['search_term'] . '%';
    // Search Lecturers
    $stmt = $pdo->prepare("SELECT lecturer_id, first_name, last_name, 'Lecturer' as type FROM lecturers WHERE first_name LIKE ? OR last_name LIKE ? OR staff_id LIKE ?");
    $stmt->execute([$search_term, $search_term, $search_term]);
    $lecturers_search = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $search_results = array_merge($search_results, $lecturers_search);

    // Search Students
    $stmt = $pdo->prepare("SELECT student_id, first_name, last_name, 'Student' as type FROM students WHERE first_name LIKE ? OR last_name LIKE ? OR reg_number LIKE ?");
    $stmt->execute([$search_term, $search_term, $search_term]);
    $students_search = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $search_results = array_merge($search_results, $students_search);

    // Search Subjects
    $stmt = $pdo->prepare("SELECT subject_id, subject_name as name, 'Subject' as type FROM subjects WHERE subject_name LIKE ? OR subject_code LIKE ?");
    $stmt->execute([$search_term, $search_term]);
    $subjects_search = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $search_results = array_merge($search_results, $subjects_search);

    // Search Programs
    $stmt = $pdo->prepare("SELECT program_id, program_name as name, 'Program' as type FROM programs WHERE program_name LIKE ? OR program_code LIKE ?");
    $stmt->execute([$search_term, $search_term]);
    $programs_search = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $search_results = array_merge($search_results, $programs_search);
}

// Handle CRUD for Users (Lecturers and Students)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['search'])) {
    // Create Lecturer
    if (isset($_POST['add_lecturer'])) {
        $stmt = $pdo->prepare("INSERT INTO lecturers (first_name, last_name, email, staff_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_POST['first_name'], $_POST['last_name'], $_POST['email'], $_POST['staff_id']]);
    }
    // Update Lecturer
    if (isset($_POST['update_lecturer'])) {
        $stmt = $pdo->prepare("UPDATE lecturers SET first_name = ?, last_name = ?, email = ?, staff_id = ? WHERE lecturer_id = ?");
        $stmt->execute([$_POST['first_name'], $_POST['last_name'], $_POST['email'], $_POST['staff_id'], $_POST['lecturer_id']]);
    }
    // Delete Lecturer
    if (isset($_POST['delete_lecturer'])) {
        $stmt = $pdo->prepare("DELETE FROM lecturers WHERE lecturer_id = ?");
        $stmt->execute([$_POST['lecturer_id']]);
    }

    // Create Student
    if (isset($_POST['add_student'])) {
        $stmt = $pdo->prepare("INSERT INTO students (reg_number, first_name, last_name, gender, program_id, enrollment_year) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['reg_number'], $_POST['first_name'], $_POST['last_name'], $_POST['gender'], $_POST['program_id'], $_POST['enrollment_year']]);
    }
    // Update Student
    if (isset($_POST['update_student'])) {
        $stmt = $pdo->prepare("UPDATE students SET reg_number = ?, first_name = ?, last_name = ?, gender = ?, program_id = ?, enrollment_year = ? WHERE student_id = ?");
        $stmt->execute([$_POST['reg_number'], $_POST['first_name'], $_POST['last_name'], $_POST['gender'], $_POST['program_id'], $_POST['enrollment_year'], $_POST['student_id']]);
    }
    // Delete Student
    if (isset($_POST['delete_student'])) {
        $stmt = $pdo->prepare("DELETE FROM students WHERE student_id = ?");
        $stmt->execute([$_POST['student_id']]);
    }

    // Create Subject
    if (isset($_POST['add_subject'])) {
        $stmt = $pdo->prepare("INSERT INTO subjects (subject_code, subject_name) VALUES (?, ?)");
        $stmt->execute([$_POST['subject_code'], $_POST['subject_name']]);
    }
    // Update Subject
    if (isset($_POST['update_subject'])) {
        $stmt = $pdo->prepare("UPDATE subjects SET subject_code = ?, subject_name = ? WHERE subject_id = ?");
        $stmt->execute([$_POST['subject_code'], $_POST['subject_name'], $_POST['subject_id']]);
    }
    // Delete Subject
    if (isset($_POST['delete_subject'])) {
        $stmt = $pdo->prepare("DELETE FROM subjects WHERE subject_id = ?");
        $stmt->execute([$_POST['subject_id']]);
    }

    // Create Program
    if (isset($_POST['add_program'])) {
        $stmt = $pdo->prepare("INSERT INTO programs (program_code, program_name) VALUES (?, ?)");
        $stmt->execute([$_POST['program_code'], $_POST['program_name']]);
    }
    // Update Program
    if (isset($_POST['update_program'])) {
        $stmt = $pdo->prepare("UPDATE programs SET program_code = ?, program_name = ? WHERE program_id = ?");
        $stmt->execute([$_POST['program_code'], $_POST['program_name'], $_POST['program_id']]);
    }
    // Delete Program
    if (isset($_POST['delete_program'])) {
        $stmt = $pdo->prepare("DELETE FROM programs WHERE program_id = ?");
        $stmt->execute([$_POST['program_id']]);
    }
}

// Fetch records for display
$lecturers = $pdo->query("SELECT * FROM lecturers")->fetchAll(PDO::FETCH_ASSOC);
$students = $pdo->query("SELECT s.*, p.program_name FROM students s JOIN programs p ON s.program_id = p.program_id")->fetchAll(PDO::FETCH_ASSOC);
$subjects = $pdo->query("SELECT * FROM subjects")->fetchAll(PDO::FETCH_ASSOC);
$programs = $pdo->query("SELECT * FROM programs")->fetchAll(PDO::FETCH_ASSOC);

// Read single record for editing
$edit_lecturer = null;
if (isset($_GET['edit_lecturer'])) {
    $stmt = $pdo->prepare("SELECT * FROM lecturers WHERE lecturer_id = ?");
    $stmt->execute([$_GET['edit_lecturer']]);
    $edit_lecturer = $stmt->fetch(PDO::FETCH_ASSOC);
}
$edit_student = null;
if (isset($_GET['edit_student'])) {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->execute([$_GET['edit_student']]);
    $edit_student = $stmt->fetch(PDO::FETCH_ASSOC);
}
$edit_subject = null;
if (isset($_GET['edit_subject'])) {
    $stmt = $pdo->prepare("SELECT * FROM subjects WHERE subject_id = ?");
    $stmt->execute([$_GET['edit_subject']]);
    $edit_subject = $stmt->fetch(PDO::FETCH_ASSOC);
}
$edit_program = null;
if (isset($_GET['edit_program'])) {
    $stmt = $pdo->prepare("SELECT * FROM programs WHERE program_id = ?");
    $stmt->execute([$_GET['edit_program']]);
    $edit_program = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University Assessment System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .header {
            background-color: #4A2C2A;
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
        }
        .header h1 {
            margin: 0;
        }
        .nav {
            width: 200px;
            background-color: #d9d9d9;
            height: calc(100vh - 60px);
            position: fixed;
            top: 60px;
            padding-top: 20px;
        }
        .nav a {
            display: block;
            width: 100%;
            padding: 10px;
            text-align: left;
            border: none;
            background: none;
            color: #333;
            cursor: pointer;
            text-decoration: none;
            box-sizing: border-box;
        }
        .nav a:hover {
            background-color: #ccc;
        }
        .nav .active {
            background-color: #4A2C2A;
            color: white;
            width: 200px;
        }
        .content {
            margin-left: 220px;
            padding: 20px;
            margin-top: 60px;
        }
        .search-bar {
            margin-bottom: 20px;
        }
        .search-bar input {
            padding: 5px;
            width: 200px;
        }
        .search-bar button {
            padding: 5px 10px;
            background-color: #4A2C2A;
            color: white;
            border: none;
            cursor: pointer;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .add-btn {
            background-color: #4A2C2A;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
        }
        .delete-btn {
            background-color: #ff3333;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
        }
        .edit-btn {
            background-color: #4A2C2A;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            text-decoration: none;
            margin-right: 5px;
        }
        .pagination {
            margin-top: 10px;
        }
        .pagination button {
            padding: 5px 10px;
            background-color: #ddd;
            border: 1px solid #ccc;
            cursor: pointer;
        }
        .add-form, .edit-form {
            margin-bottom: 20px;
        }
        .add-form input, .add-form select, .edit-form input, .edit-form select {
            padding: 5px;
            margin-right: 10px;
        }
        .add-form button, .edit-form button {
            padding: 5px 10px;
            background-color: #4A2C2A;
            color: white;
            border: none;
            cursor: pointer;
        }
        .section {
            margin-bottom: 40px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>University Assessment System</h1>
        <button>Admin ▼</button>
    </div>
    <div class="nav">
        <a href="?section=dashboard" class="<?php echo $active_section == 'dashboard' ? 'active' : ''; ?>">Dashboard</a>
        <a href="?section=users" class="<?php echo $active_section == 'users' ? 'active' : ''; ?>">User Management</a>
        <a href="?section=courses" class="<?php echo $active_section == 'courses' ? 'active' : ''; ?>">Courses</a>
        <a href="?section=programs" class="<?php echo $active_section == 'programs' ? 'active' : ''; ?>">Programs</a>
        <a href="?section=departments" class="<?php echo $active_section == 'departments' ? 'active' : ''; ?>">Departments</a>
        <a href="?section=settings" class="<?php echo $active_section == 'settings' ? 'active' : ''; ?>">System Settings</a>
    </div>
    <div class="content">
        <!-- Search Bar (Always Visible) -->
        <div class="search-bar">
            <form method="POST">
                <input type="text" name="search_term" placeholder="Search...">
                <button type="submit" name="search">🔍</button>
                <?php if ($active_section != 'dashboard'): ?>
                    <button class="add-btn" type="button" onclick="document.querySelector('.add-form').style.display='block';">+ Add New</button>
                <?php endif; ?>
            </form>
        </div>

        <!-- Search Results (Visible on Dashboard or After Search) -->
        <?php if ($active_section == 'dashboard' || !empty($search_results)): ?>
            <h2>Search Results</h2>
            <table>
                <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Action</th>
                </tr>
                <?php if (!empty($search_results)): ?>
                    <?php foreach ($search_results as $result): ?>
                        <tr>
                            <td>
                                <?php 
                                if ($result['type'] == 'Lecturer' || $result['type'] == 'Student') {
                                    echo $result['first_name'] . ' ' . $result['last_name'];
                                } else {
                                    echo $result['name'];
                                }
                                ?>
                            </td>
                            <td><?php echo $result['type']; ?></td>
                            <td>
                                <a href="?section=<?php 
                                    echo $result['type'] == 'Lecturer' ? 'users&edit_lecturer=' . $result['lecturer_id'] : 
                                    ($result['type'] == 'Student' ? 'users&edit_student=' . $result['student_id'] : 
                                    ($result['type'] == 'Subject' ? 'courses&edit_subject=' . $result['subject_id'] : 
                                    'programs&edit_program=' . $result['program_id'])); 
                                ?>" class="edit-btn">Edit</a>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="<?php 
                                        echo $result['type'] == 'Lecturer' ? 'lecturer_id' : 
                                        ($result['type'] == 'Student' ? 'student_id' : 
                                        ($result['type'] == 'Subject' ? 'subject_id' : 'program_id')); 
                                    ?>" value="<?php 
                                        echo $result['type'] == 'Lecturer' ? $result['lecturer_id'] : 
                                        ($result['type'] == 'Student' ? $result['student_id'] : 
                                        ($result['type'] == 'Subject' ? $result['subject_id'] : $result['program_id'])); 
                                    ?>">
                                    <button type="submit" name="delete_<?php 
                                        echo $result['type'] == 'Lecturer' ? 'lecturer' : 
                                        ($result['type'] == 'Student' ? 'student' : 
                                        ($result['type'] == 'Subject' ? 'subject' : 'program')); 
                                    ?>" class="delete-btn">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3">No results found.</td>
                    </tr>
                <?php endif; ?>
            </table>
        <?php endif; ?>

        <!-- Users Section -->
        <?php if ($active_section == 'users'): ?>
            <h2>Users</h2>
            <form class="add-form" method="POST" style="display: none;">
                <select name="user_type" onchange="this.form.submit()" style="padding: 5px; margin-right: 10px;">
                    <option value="lecturer" <?php echo (!isset($_POST['user_type']) || $_POST['user_type'] == 'lecturer') ? 'selected' : ''; ?>>Lecturer</option>
                    <option value="student" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'student') ? 'selected' : ''; ?>>Student</option>
                </select>
                <?php if (!isset($_POST['user_type']) || $_POST['user_type'] == 'lecturer'): ?>
                    <input type="text" name="first_name" placeholder="First Name" required>
                    <input type="text" name="last_name" placeholder="Last Name" required>
                    <input type="email" name="email" placeholder="Email">
                    <input type="text" name="staff_id" placeholder="Staff ID" required>
                    <button type="submit" name="add_lecturer">Add Lecturer</button>
                <?php else: ?>
                    <input type="text" name="reg_number" placeholder="Reg Number" required>
                    <input type="text" name="first_name" placeholder="First Name" required>
                    <input type="text" name="last_name" placeholder="Last Name" required>
                    <select name="gender" required>
                        <option value="M">Male</option>
                        <option value="F">Female</option>
                    </select>
                    <select name="program_id" required>
                        <?php foreach ($programs as $program): ?>
                            <option value="<?php echo $program['program_id']; ?>"><?php echo $program['program_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="enrollment_year" placeholder="Enrollment Year" required>
                    <button type="submit" name="add_student">Add Student</button>
                <?php endif; ?>
            </form>
            <!-- Edit User Form -->
            <?php if ($edit_lecturer): ?>
                <form class="edit-form" method="POST">
                    <input type="hidden" name="lecturer_id" value="<?php echo $edit_lecturer['lecturer_id']; ?>">
                    <input type="text" name="first_name" value="<?php echo $edit_lecturer['first_name']; ?>" required>
                    <input type="text" name="last_name" value="<?php echo $edit_lecturer['last_name']; ?>" required>
                    <input type="email" name="email" value="<?php echo $edit_lecturer['email']; ?>">
                    <input type="text" name="staff_id" value="<?php echo $edit_lecturer['staff_id']; ?>" required>
                    <button type="submit" name="update_lecturer">Update Lecturer</button>
                </form>
            <?php elseif ($edit_student): ?>
                <form class="edit-form" method="POST">
                    <input type="hidden" name="student_id" value="<?php echo $edit_student['student_id']; ?>">
                    <input type="text" name="reg_number" value="<?php echo $edit_student['reg_number']; ?>" required>
                    <input type="text" name="first_name" value="<?php echo $edit_student['first_name']; ?>" required>
                    <input type="text" name="last_name" value="<?php echo $edit_student['last_name']; ?>" required>
                    <select name="gender" required>
                        <option value="M" <?php echo $edit_student['gender'] == 'M' ? 'selected' : ''; ?>>Male</option>
                        <option value="F" <?php echo $edit_student['gender'] == 'F' ? 'selected' : ''; ?>>Female</option>
                    </select>
                    <select name="program_id" required>
                        <?php foreach ($programs as $program): ?>
                            <option value="<?php echo $program['program_id']; ?>" <?php echo $edit_student['program_id'] == $program['program_id'] ? 'selected' : ''; ?>>
                                <?php echo $program['program_name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="enrollment_year" value="<?php echo $edit_student['enrollment_year']; ?>" required>
                    <button type="submit" name="update_student">Update Student</button>
                </form>
            <?php endif; ?>

            <!-- Lecturers Table -->
            <div class="section">
                <h3>Lecturers</h3>
                <table>
                    <tr>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Courses</th>
                        <th>Action</th>
                    </tr>
                    <?php foreach ($lecturers as $lecturer): ?>
                        <tr>
                            <td><?php echo $lecturer['first_name'] . ' ' . $lecturer['last_name']; ?></td>
                            <td>Computer Science</td> <!-- Placeholder, adjust with real data if needed -->
                            <td>3 Active</td> <!-- Placeholder, adjust with real data if needed -->
                            <td>
                                <a href="?section=users&edit_lecturer=<?php echo $lecturer['lecturer_id']; ?>" class="edit-btn">Edit</a>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="lecturer_id" value="<?php echo $lecturer['lecturer_id']; ?>">
                                    <button type="submit" name="delete_lecturer" class="delete-btn">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                <div class="pagination">
                    <button>◄</button>
                    <span>Page 1 of 5</span>
                    <button>►</button>
                </div>
            </div>

            <!-- Students Table -->
            <div class="section">
                <h3>Students</h3>
                <table>
                    <tr>
                        <th>Reg Number</th>
                        <th>Name</th>
                        <th>Gender</th>
                        <th>Program</th>
                        <th>Enrollment Year</th>
                        <th>Action</th>
                    </tr>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?php echo $student['reg_number']; ?></td>
                            <td><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></td>
                            <td><?php echo $student['gender']; ?></td>
                            <td><?php echo $student['program_name']; ?></td>
                            <td><?php echo $student['enrollment_year']; ?></td>
                            <td>
                                <a href="?section=users&edit_student=<?php echo $student['student_id']; ?>" class="edit-btn">Edit</a>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
                                    <button type="submit" name="delete_student" class="delete-btn">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                <div class="pagination">
                    <button>◄</button>
                    <span>Page 1 of 5</span>
                    <button>►</button>
                </div>
            </div>
        <?php endif; ?>

        <!-- Courses Section -->
        <?php if ($active_section == 'courses'): ?>
            <h2>Courses</h2>
            <form class="add-form" method="POST" style="display: none;">
                <input type="text" name="subject_code" placeholder="Subject Code" required>
                <input type="text" name="subject_name" placeholder="Subject Name" required>
                <button type="submit" name="add_subject">Add Subject</button>
            </form>
            <!-- Edit Subject Form -->
            <?php if ($edit_subject): ?>
                <form class="edit-form" method="POST">
                    <input type="hidden" name="subject_id" value="<?php echo $edit_subject['subject_id']; ?>">
                    <input type="text" name="subject_code" value="<?php echo $edit_subject['subject_code']; ?>" required>
                    <input type="text" name="subject_name" value="<?php echo $edit_subject['subject_name']; ?>" required>
                    <button type="submit" name="update_subject">Update Subject</button>
                </form>
            <?php endif; ?>
            <div class="section">
                <table>
                    <tr>
                        <th>Subject Code</th>
                        <th>Subject Name</th>
                        <th>Action</th>
                    </tr>
                    <?php foreach ($subjects as $subject): ?>
                        <tr>
                            <td><?php echo $subject['subject_code']; ?></td>
                            <td><?php echo $subject['subject_name']; ?></td>
                            <td>
                                <a href="?section=courses&edit_subject=<?php echo $subject['subject_id']; ?>" class="edit-btn">Edit</a>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="subject_id" value="<?php echo $subject['subject_id']; ?>">
                                    <button type="submit" name="delete_subject" class="delete-btn">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                <div class="pagination">
                    <button>◄</button>
                    <span>Page 1 of 5</span>
                    <button>►</button>
                </div>
            </div>
        <?php endif; ?>

        <!-- Programs Section -->
        <?php if ($active_section == 'programs'): ?>
            <h2>Programs</h2>
            <form class="add-form" method="POST" style="display: none;">
                <input type="text" name="program_code" placeholder="Program Code" required>
                <input type="text" name="program_name" placeholder="Program Name" required>
                <button type="submit" name="add_program">Add Program</button>
            </form>
            <!-- Edit Program Form -->
            <?php if ($edit_program): ?>
                <form class="edit-form" method="POST">
                    <input type="hidden" name="program_id" value="<?php echo $edit_program['program_id']; ?>">
                    <input type="text" name="program_code" value="<?php echo $edit_program['program_code']; ?>" required>
                    <input type="text" name="program_name" value="<?php echo $edit_program['program_name']; ?>" required>
                    <button type="submit" name="update_program">Update Program</button>
                </form>
            <?php endif; ?>
            <div class="section">
                <table>
                    <tr>
                        <th>Program Code</th>
                        <th>Program Name</th>
                        <th>Action</th>
                    </tr>
                    <?php foreach ($programs as $program): ?>
                        <tr>
                            <td><?php echo $program['program_code']; ?></td>
                            <td><?php echo $program['program_name']; ?></td>
                            <td>
                                <a href="?section=programs&edit_program=<?php echo $program['program_id']; ?>" class="edit-btn">Edit</a>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="program_id" value="<?php echo $program['program_id']; ?>">
                                    <button type="submit" name="delete_program" class="delete-btn">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                <div class="pagination">
                    <button>◄</button>
                    <span>Page 1 of 5</span>
                    <button>►</button>
                </div>
            </div>
        <?php endif; ?>

        <!-- Departments Section (Alias for Programs) -->
        <?php if ($active_section == 'departments'): ?>
            <h2>Departments</h2>
            <form class="add-form" method="POST" style="display: none;">
                <input type="text" name="program_code" placeholder="Program Code" required>
                <input type="text" name="program_name" placeholder="Program Name" required>
                <button type="submit" name="add_program">Add Department</button>
            </form>
            <!-- Edit Program Form -->
            <?php if ($edit_program): ?>
                <form class="edit-form" method="POST">
                    <input type="hidden" name="program_id" value="<?php echo $edit_program['program_id']; ?>">
                    <input type="text" name="program_code" value="<?php echo $edit_program['program_code']; ?>" required>
                    <input type="text" name="program_name" value="<?php echo $edit_program['program_name']; ?>" required>
                    <button type="submit" name="update_program">Update Department</button>
                </form>
            <?php endif; ?>
            <div class="section">
                <table>
                    <tr>
                        <th>Program Code</th>
                        <th>Program Name</th>
                        <th>Action</th>
                    </tr>
                    <?php foreach ($programs as $program): ?>
                        <tr>
                            <td><?php echo $program['program_code']; ?></td>
                            <td><?php echo $program['program_name']; ?></td>
                            <td>
                                <a href="?section=departments&edit_program=<?php echo $program['program_id']; ?>" class="edit-btn">Edit</a>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="program_id" value="<?php echo $program['program_id']; ?>">
                                    <button type="submit" name="delete_program" class="delete-btn">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                <div class="pagination">
                    <button>◄</button>
                    <span>Page 1 of 5</span>
                    <button>►</button>
                </div>
            </div>
        <?php endif; ?>

        <!-- System Settings Section -->
        <?php if ($active_section == 'settings'): ?>
            <h2>System Settings</h2>
            <p>Settings content goes here.</p>
        <?php endif; ?>
    </div>
</body>
</html>