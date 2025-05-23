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

// Handle Login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $stmt = $pdo->prepare("SELECT student_id, first_name, last_name FROM students WHERE first_name = ? AND last_name = ?");
    $stmt->execute([$first_name, $last_name]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($student) {
        $_SESSION['student_id'] = $student['student_id'];
        $_SESSION['student_name'] = $student['first_name'] . ' ' . $student['last_name'];
    } else {
        $login_error = "Invalid first name or last name.";
    }
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: student_page.php");
    exit;
}

// Fetch data for logged-in student
$enrolled_subjects = [];
$student_grades = [];
$final_grades = [];
$academic_standings = [];
$student_details = [];
if (isset($_SESSION['student_id'])) {
    $student_id = $_SESSION['student_id'];

    // Fetch student's program
    $stmt = $pdo->prepare("SELECT program_id FROM students WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $student_program = $stmt->fetch(PDO::FETCH_ASSOC);
    $program_id = $student_program['program_id'];

    // Fetch enrolled subjects
    $stmt = $pdo->prepare("SELECT s.subject_id, s.subject_code, s.subject_name, ps.semester
                           FROM subjects s
                           JOIN program_subjects ps ON s.subject_id = ps.subject_id
                           WHERE ps.program_id = ?");
    $stmt->execute([$program_id]);
    $enrolled_subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch student grades
    $stmt = $pdo->prepare("SELECT sg.grade_id, sg.subject_id, sg.assessment_type_id, sg.semester, sg.academic_year, sg.score,
                           s.subject_name, at.assessment_name
                           FROM student_grades sg
                           JOIN subjects s ON sg.subject_id = s.subject_id
                           JOIN assessment_types at ON sg.assessment_type_id = at.assessment_type_id
                           WHERE sg.student_id = ?");
    $stmt->execute([$student_id]);
    $student_grades = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch final grades
    $stmt = $pdo->prepare("SELECT fg.final_grade_id, fg.subject_id, fg.semester, fg.academic_year, fg.final_score, fg.passed,
                           s.subject_name
                           FROM final_grades fg
                           JOIN subjects s ON fg.subject_id = s.subject_id
                           WHERE fg.student_id = ?");
    $stmt->execute([$student_id]);
    $final_grades = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch academic standings
    $stmt = $pdo->prepare("SELECT standing_id, academic_year, standing_status FROM academic_standings WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $academic_standings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch student details
    $stmt = $pdo->prepare("SELECT first_name, last_name, reg_number, gender, program_id, enrollment_year FROM students WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $student_details = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Determine which section to display
$section = isset($_GET['section']) ? $_GET['section'] : 'dashboard';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
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
            padding: 10px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            width: 100%;
            box-sizing: border-box;
            z-index: 1000;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .sidebar {
            width: 250px;
            background-color: #333;
            color: white;
            position: fixed;
            top: 60px;
            left: 0;
            bottom: 0;
            padding-top: 20px;
            z-index: 999;
        }
        .sidebar a {
            display: block;
            color: white;
            padding: 15px 20px;
            text-decoration: none;
            font-size: 16px;
        }
        .sidebar a:hover, .sidebar a.active {
            background-color: #4A2C2A;
        }
        .content {
            margin-left: 270px;
            padding: 20px;
            margin-top: 60px;
            min-height: calc(100vh - 60px);
        }
        .login-form, .dashboard, .section {
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .login-form input, .login-form button {
            padding: 10px;
            margin: 5px 0;
            width: 100%;
            box-sizing: border-box;
        }
        .dashboard h2, .section h2 {
            color: #4A2C2A;
        }
        table {
            width: 100%;
            border-collapse: collapse;
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
        .logout-btn {
            background-color: #ff3333;
            color: white;
            border: none;
            padding: 8px 16px;
            cursor: pointer;
            text-decoration: none;
            border-radius: 3px;
            white-space: nowrap;
        }
        .progress-bar {
            background-color: #ddd;
            border-radius: 5px;
            overflow: hidden;
            height: 20px;
            margin: 10px 0;
        }
        .progress {
            background-color: #4A2C2A;
            height: 100%;
            text-align: center;
            color: white;
            line-height: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Student Dashboard</h1>
        <?php if (isset($_SESSION['student_id'])): ?>
            <a href="?logout=1" class="logout-btn">Logout</a>
        <?php endif; ?>
    </div>

    <?php if (isset($_SESSION['student_id'])): ?>
        <div class="sidebar">
            <a href="?section=dashboard" <?php echo $section == 'dashboard' ? 'class="active"' : ''; ?>>Dashboard</a>
            <a href="?section=courses" <?php echo $section == 'courses' ? 'class="active"' : ''; ?>>View Courses</a>
            <a href="?section=grades" <?php echo $section == 'grades' ? 'class="active"' : ''; ?>>View Grades</a>
            <a href="?section=details" <?php echo $section == 'details' ? 'class="active"' : ''; ?>>Personal Details</a>
            <a href="?section=progress" <?php echo $section == 'progress' ? 'class="active"' : ''; ?>>Academic Progress</a>
        </div>
    <?php endif; ?>

    <div class="content">
        <?php if (!isset($_SESSION['student_id'])): ?>
            <div class="login-form">
                <h2>Student Login</h2>
                <?php if (isset($login_error)): ?>
                    <p style="color: red;"><?php echo $login_error; ?></p>
                <?php endif; ?>
                <form method="POST">
                    <input type="text" name="first_name" placeholder="First Name" required>
                    <input type="text" name="last_name" placeholder="Last Name" required>
                    <button type="submit" name="login" class="add-btn">Login</button>
                </form>
            </div>
        <?php else: ?>
            <?php if ($section == 'dashboard'): ?>
                <div class="dashboard">
                    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['student_name']); ?></h2>
                    <p>This is your student dashboard. Use the sidebar to navigate through your courses, grades, personal details, and academic progress.</p>
                    <h3>Quick Overview</h3>
                    <p><strong>Enrolled Courses:</strong> <?php echo count($enrolled_subjects); ?></p>
                    <p><strong>Latest Academic Standing:</strong> 
                        <?php echo !empty($academic_standings) ? htmlspecialchars(end($academic_standings)['standing_status']) : 'N/A'; ?>
                    </p>
                </div>
            <?php elseif ($section == 'courses'): ?>
                <div class="section">
                    <h2>My Enrolled Subjects</h2>
                    <table>
                        <tr>
                            <th>Subject Code</th>
                            <th>Subject Name</th>
                            <th>Semester</th>
                        </tr>
                        <?php foreach ($enrolled_subjects as $subject): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($subject['subject_code']); ?></td>
                                <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                <td><?php echo htmlspecialchars($subject['semester']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            <?php elseif ($section == 'grades'): ?>
                <div class="section">
                    <h2>My Assessment Grades</h2>
                    <table>
                        <tr>
                            <th>Subject</th>
                            <th>Assessment Type</th>
                            <th>Semester</th>
                            <th>Academic Year</th>
                            <th>Score</th>
                        </tr>
                        <?php foreach ($student_grades as $grade): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($grade['subject_name']); ?></td>
                                <td><?php echo htmlspecialchars($grade['assessment_name']); ?></td>
                                <td><?php echo htmlspecialchars($grade['semester']); ?></td>
                                <td><?php echo htmlspecialchars($grade['academic_year']); ?></td>
                                <td><?php echo htmlspecialchars($grade['score']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>

                    <h2>My Final Grades</h2>
                    <table>
                        <tr>
                            <th>Subject</th>
                            <th>Semester</th>
                            <th>Academic Year</th>
                            <th>Final Score</th>
                            <th>Passed</th>
                        </tr>
                        <?php foreach ($final_grades as $grade): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($grade['subject_name']); ?></td>
                                <td><?php echo htmlspecialchars($grade['semester']); ?></td>
                                <td><?php echo htmlspecialchars($grade['academic_year']); ?></td>
                                <td><?php echo htmlspecialchars($grade['final_score']); ?></td>
                                <td><?php echo $grade['passed'] ? 'Yes' : 'No'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            <?php elseif ($section == 'details'): ?>
                <div class="section">
                    <h2>Personal Details</h2>
                    <p><strong>First Name:</strong> <?php echo htmlspecialchars($student_details['first_name']); ?></p>
                    <p><strong>Last Name:</strong> <?php echo htmlspecialchars($student_details['last_name']); ?></p>
                    <p><strong>Registration Number:</strong> <?php echo htmlspecialchars($student_details['reg_number']); ?></p>
                    <p><strong>Gender:</strong> <?php echo htmlspecialchars($student_details['gender'] == 'M' ? 'Male' : 'Female'); ?></p>
                    <p><strong>Program ID:</strong> <?php echo htmlspecialchars($student_details['program_id']); ?></p>
                    <p><strong>Enrollment Year:</strong> <?php echo htmlspecialchars($student_details['enrollment_year']); ?></p>
                </div>
            <?php elseif ($section == 'progress'): ?>
                <div class="section">
                    <h2>Academic Progress</h2>
                    <h3>Academic Standing</h3>
                    <table>
                        <tr>
                            <th>Academic Year</th>
                            <th>Standing Status</th>
                        </tr>
                        <?php foreach ($academic_standings as $standing): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($standing['academic_year']); ?></td>
                                <td><?php echo htmlspecialchars($standing['standing_status']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                    <h3>Course Completion</h3>
                    <?php
                    $total_subjects = count($enrolled_subjects);
                    $passed_subjects = count(array_filter($final_grades, fn($grade) => $grade['passed']));
                    $completion_percentage = $total_subjects > 0 ? ($passed_subjects / $total_subjects) * 100 : 0;
                    ?>
                    <p><strong>Completed Courses:</strong> <?php echo $passed_subjects; ?> / <?php echo $total_subjects; ?></p>
                    <div class="progress-bar">
                        <div class="progress" style="width: <?php echo $completion_percentage; ?>%;">
                            <?php echo round($completion_percentage, 1); ?>%
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>