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
    $surname = $_POST['surname'];
    $staff_id = $_POST['staff_id'];
    $stmt = $pdo->prepare("SELECT lecturer_id, first_name, last_name FROM lecturers WHERE last_name = ? AND staff_id = ?");
    $stmt->execute([$surname, $staff_id]);
    $lecturer = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($lecturer) {
        $_SESSION['lecturer_id'] = $lecturer['lecturer_id'];
        $_SESSION['lecturer_name'] = $lecturer['first_name'] . ' ' . $lecturer['last_name'];
    } else {
        $login_error = "Invalid surname or staff ID.";
    }
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: lecturer_page.php");
    exit;
}

// Handle Assigning Grades and Computing Final Grades
if (isset($_SESSION['lecturer_id']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $lecturer_id = $_SESSION['lecturer_id'];

    // Assign Grade
    if (isset($_POST['assign_grade'])) {
        $stmt = $pdo->prepare("INSERT INTO student_grades (student_id, subject_id, assessment_type_id, semester, academic_year, score) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE score = ?");
        $stmt->execute([$_POST['student_id'], $_POST['subject_id'], $_POST['assessment_type_id'], $_POST['semester'], $_POST['academic_year'], $_POST['score'], $_POST['score']]);
    }

    // Compute Final Grades
    if (isset($_POST['compute_final_grades'])) {
        $subject_id = $_POST['subject_id'];
        $semester = $_POST['semester'];
        $academic_year = $_POST['academic_year'];

        // Fetch students enrolled in the subject (via program_subjects and students)
        $stmt = $pdo->prepare("SELECT s.student_id FROM students s JOIN program_subjects ps ON s.program_id = ps.program_id WHERE ps.subject_id = ? AND ps.semester = ?");
        $stmt->execute([$subject_id, $semester]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($students as $student) {
            $student_id = $student['student_id'];

            // Compute weighted score
            $stmt = $pdo->prepare("SELECT sg.score, aw.weight, at.assessment_type_id
                                   FROM student_grades sg
                                   JOIN assessment_weights aw ON sg.assessment_type_id = aw.assessment_type_id AND sg.subject_id = aw.subject_id
                                   JOIN assessment_types at ON sg.assessment_type_id = at.assessment_type_id
                                   WHERE sg.student_id = ? AND sg.subject_id = ? AND sg.semester = ? AND sg.academic_year = ?");
            $stmt->execute([$student_id, $subject_id, $semester, $academic_year]);
            $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $total_score = 0;
            $total_weight = 0;
            $all_graded = true;

            foreach ($grades as $grade) {
                if ($grade['score'] !== null) {
                    $total_score += $grade['score'] * ($grade['weight'] / 100);
                    $total_weight += $grade['weight'];
                } else {
                    $all_graded = false;
                    break;
                }
            }

            if ($all_graded && $total_weight == 100) {
                // Determine if the student passed (assuming 50 is the passing score)
                $passed = $total_score >= 50 ? 1 : 0;

                // Insert or update final grade
                $stmt = $pdo->prepare("INSERT INTO final_grades (student_id, subject_id, semester, academic_year, final_score, passed) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE final_score = ?, passed = ?");
                $stmt->execute([$student_id, $subject_id, $semester, $academic_year, $total_score, $passed, $total_score, $passed]);
            }
        }
    }
}

// Fetch data for logged-in lecturer
$lecturer_subjects = [];
$lecturer_grades = [];
$students = [];
$assessment_types = [];
if (isset($_SESSION['lecturer_id'])) {
    $lecturer_id = $_SESSION['lecturer_id'];

    // Fetch lecturer's assigned subjects
    $stmt = $pdo->prepare("SELECT sl.subject_id, s.subject_code, s.subject_name
                           FROM subject_lecturer sl
                           JOIN subjects s ON sl.subject_id = s.subject_id
                           WHERE sl.lecturer_id = ?");
    $stmt->execute([$lecturer_id]);
    $lecturer_subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all assessment types
    $stmt = $pdo->query("SELECT assessment_type_id, assessment_name FROM assessment_types");
    $assessment_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch students enrolled in lecturer's subjects
    $stmt = $pdo->prepare("SELECT DISTINCT s.student_id, s.first_name, s.last_name
                           FROM students s
                           JOIN program_subjects ps ON s.program_id = ps.program_id
                           WHERE ps.subject_id IN (SELECT subject_id FROM subject_lecturer WHERE lecturer_id = ?)");
    $stmt->execute([$lecturer_id]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch grades assigned by the lecturer
    $stmt = $pdo->prepare("SELECT sg.grade_id, sg.student_id, sg.subject_id, sg.assessment_type_id, sg.semester, sg.academic_year, sg.score,
                           s.first_name, s.last_name, sub.subject_name, at.assessment_name
                           FROM student_grades sg
                           JOIN students s ON sg.student_id = s.student_id
                           JOIN subjects sub ON sg.subject_id = sub.subject_id
                           JOIN assessment_types at ON sg.assessment_type_id = at.assessment_type_id
                           JOIN subject_lecturer sl ON sg.subject_id = sl.subject_id
                           WHERE sl.lecturer_id = ?");
    $stmt->execute([$lecturer_id]);
    $lecturer_grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lecturer Management</title>
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
            padding: 10px 30px; /* Increased right padding to ensure button visibility */
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            width: 100%;
            box-sizing: border-box; /* Ensure padding doesn't cause overflow */
            z-index: 1000;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            margin-left: 20px;
            padding: 20px;
            margin-top: 60px;
        }
        .login-form, .dashboard {
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
        .dashboard h2 {
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
        .add-btn, .save-btn, .compute-btn {
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
            padding: 8px 16px; /* Slightly adjusted padding for better fit */
            cursor: pointer;
            text-decoration: none;
            border-radius: 3px; /* Added for better appearance */
            white-space: nowrap; /* Prevent text wrapping */
        }
        .form-container {
            margin-bottom: 20px;
        }
        .form-container input, .form-container select {
            padding: 5px;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Lecturer Management</h1>
        <?php if (isset($_SESSION['lecturer_id'])): ?>
            <a href="?logout=1" class="logout-btn">Logout</a>
        <?php endif; ?>
    </div>
    <div class="content">
        <?php if (!isset($_SESSION['lecturer_id'])): ?>
            <div class="login-form">
                <h2>Lecturer Login</h2>
                <?php if (isset($login_error)): ?>
                    <p style="color: red;"><?php echo $login_error; ?></p>
                <?php endif; ?>
                <form method="POST">
                    <input type="text" name="surname" placeholder="Surname (e.g., Onyango)" required>
                    <input type="text" name="staff_id" placeholder="Staff ID (e.g., STF006)" required>
                    <button type="submit" name="login" class="add-btn">Login</button>
                </form>
            </div>
        <?php else: ?>
            <div class="dashboard">
                <h2>Welcome, <?php echo htmlspecialchars($_SESSION['lecturer_name']); ?></h2>

                <!-- Assigned Subjects -->
                <h2>My Assigned Subjects</h2>
                <table>
                    <tr>
                        <th>Subject Code</th>
                        <th>Subject Name</th>
                    </tr>
                    <?php foreach ($lecturer_subjects as $subject): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($subject['subject_code']); ?></td>
                            <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>

                <!-- Assign Grades -->
                <h2>Assign Grades</h2>
                <form method="POST" class="form-container">
                    <select name="subject_id" required>
                        <option value="">Select Subject</option>
                        <?php foreach ($lecturer_subjects as $subject): ?>
                            <option value="<?php echo $subject['subject_id']; ?>">
                                <?php echo htmlspecialchars($subject['subject_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="student_id" required>
                        <option value="">Select Student</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?php echo $student['student_id']; ?>">
                                <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="assessment_type_id" required>
                        <option value="">Select Assessment Type</option>
                        <?php foreach ($assessment_types as $type): ?>
                            <option value="<?php echo $type['assessment_type_id']; ?>">
                                <?php echo htmlspecialchars($type['assessment_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" name="semester" placeholder="Semester (1 or 2)" min="1" max="2" required>
                    <input type="text" name="academic_year" placeholder="Academic Year (e.g., 2024/2025)" required>
                    <input type="number" name="score" placeholder="Score (0-100)" min="0" max="100" step="0.01" required>
                    <button type="submit" name="assign_grade" class="save-btn">Assign Grade</button>
                </form>

                <!-- Compute Final Grades -->
                <h2>Compute Final Grades</h2>
                <form method="POST" class="form-container">
                    <select name="subject_id" required>
                        <option value="">Select Subject</option>
                        <?php foreach ($lecturer_subjects as $subject): ?>
                            <option value="<?php echo $subject['subject_id']; ?>">
                                <?php echo htmlspecialchars($subject['subject_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" name="semester" placeholder="Semester (1 or 2)" min="1" max="2" required>
                    <input type="text" name="academic_year" placeholder="Academic Year (e.g., 2024/2025)" required>
                    <button type="submit" name="compute_final_grades" class="compute-btn">Compute Final Grades</button>
                </form>

                <!-- View Assigned Grades -->
                <h2>Assigned Grades</h2>
                <table>
                    <tr>
                        <th>Student</th>
                        <th>Subject</th>
                        <th>Assessment Type</th>
                        <th>Semester</th>
                        <th>Academic Year</th>
                        <th>Score</th>
                    </tr>
                    <?php foreach ($lecturer_grades as $grade): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($grade['first_name'] . ' ' . $grade['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($grade['subject_name']); ?></td>
                            <td><?php echo htmlspecialchars($grade['assessment_name']); ?></td>
                            <td><?php echo htmlspecialchars($grade['semester']); ?></td>
                            <td><?php echo htmlspecialchars($grade['academic_year']); ?></td>
                            <td><?php echo htmlspecialchars($grade['score']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>