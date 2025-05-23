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

// Determine selected query
$selected_query = isset($_GET['query']) ? $_GET['query'] : '';
$query_results = [];
$query_title = '';
$query_description = '';

if ($selected_query) {
    switch ($selected_query) {
        case 'q1':
            $query_title = 'Students Scheduled to Repeat the Year';
            $query_description = 'Lists students who failed two or more modules in the same academic year.';
            $stmt = $pdo->prepare("
                SELECT s.student_id, s.reg_number, s.first_name, s.last_name, fg.academic_year, COUNT(*) as failed_modules
                FROM students s
                JOIN final_grades fg ON s.student_id = fg.student_id
                WHERE fg.passed = 0
                GROUP BY s.student_id, fg.academic_year
                HAVING failed_modules >= 2
                ORDER BY fg.academic_year, s.reg_number
            ");
            $stmt->execute();
            $query_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'q2':
            $query_title = 'Students Who Passed DMS and Failed OS2';
            $query_description = 'Lists students who passed Database Management System but failed Operating Systems 2.';
            $stmt = $pdo->prepare("
                SELECT s.student_id, s.reg_number, s.first_name, s.last_name
                FROM students s
                JOIN final_grades fg_dms ON s.student_id = fg_dms.student_id
                JOIN subjects sub_dms ON fg_dms.subject_id = sub_dms.subject_id
                JOIN final_grades fg_os2 ON s.student_id = fg_os2.student_id
                JOIN subjects sub_os2 ON fg_os2.subject_id = sub_os2.subject_id
                WHERE sub_dms.subject_code = 'DMS-301'
                AND fg_dms.passed = 1
                AND sub_os2.subject_code = 'OPS-302'
                AND fg_os2.passed = 0
                ORDER BY s.reg_number
            ");
            $stmt->execute();
            $query_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'q3':
            $query_title = 'Performance Comparison for DSA-301 by Gender';
            $query_description = 'Compares average performance of females and males in DSA-301 for BIT and BIS programs.';
            $stmt = $pdo->prepare("
                SELECT p.program_name, s.gender, AVG(fg.final_score) as avg_score, COUNT(s.student_id) as student_count
                FROM students s
                JOIN programs p ON s.program_id = p.program_id
                JOIN final_grades fg ON s.student_id = fg.student_id
                JOIN subjects sub ON fg.subject_id = sub.subject_id
                WHERE p.program_code IN ('BIT', 'BIS')
                AND sub.subject_code = 'DSA-301'
                GROUP BY p.program_name, s.gender
                ORDER BY p.program_name, s.gender
            ");
            $stmt->execute();
            $query_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'q4':
            $query_title = 'Students with Distinction Average';
            $query_description = 'Lists students with an average final score of 75 or higher for the academic year.';
            $stmt = $pdo->prepare("
                SELECT s.student_id, s.reg_number, s.first_name, s.last_name, fg.academic_year, AVG(fg.final_score) as avg_score
                FROM students s
                JOIN final_grades fg ON s.student_id = fg.student_id
                GROUP BY s.student_id, fg.academic_year
                HAVING avg_score >= 75
                ORDER BY fg.academic_year, s.reg_number
            ");
            $stmt->execute();
            $query_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'q5':
            $query_title = 'Subjects by Program and Lecturers';
            $query_description = 'Shows subjects taught in BIT only, BIS only, or both, along with their assigned lecturers.';
            $stmt = $pdo->prepare("
                SELECT 
                    sub.subject_name,
                    CASE 
                        WHEN COUNT(DISTINCT p.program_code) = 2 THEN 'Both BIT and BIS'
                        WHEN p.program_code = 'BIT' THEN 'BIT Only'
                        WHEN p.program_code = 'BIS' THEN 'BIS Only'
                    END as program_status,
                    GROUP_CONCAT(CONCAT(l.first_name, ' ', l.last_name) ORDER BY l.first_name) as lecturers
                FROM subjects sub
                JOIN program_subjects ps ON sub.subject_id = ps.subject_id
                JOIN programs p ON ps.program_id = p.program_id
                LEFT JOIN subject_lecturer sl ON sub.subject_id = sl.subject_id
                LEFT JOIN lecturers l ON sl.lecturer_id = l.lecturer_id
                WHERE p.program_code IN ('BIT', 'BIS')
                GROUP BY sub.subject_name
                ORDER BY program_status, sub.subject_name
            ");
            $stmt->execute();
            $query_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'q6':
            $query_title = 'Gradebook for BIS Students';
            $query_description = 'Displays a gradebook for BIS students, including assessment scores, final grades, and missing grades.';
            $stmt = $pdo->prepare("
                SELECT 
                    s.reg_number,
                    s.first_name,
                    s.last_name,
                    sub.subject_name,
                    at.assessment_name,
                    sg.score,
                    fg.final_score,
                    fg.passed
                FROM students s
                JOIN programs p ON s.program_id = p.program_id
                JOIN program_subjects ps ON p.program_id = ps.program_id
                JOIN subjects sub ON ps.subject_id = sub.subject_id
                LEFT JOIN student_grades sg ON s.student_id = sg.student_id AND sub.subject_id = sg.subject_id
                LEFT JOIN assessment_types at ON sg.assessment_type_id = at.assessment_type_id
                LEFT JOIN final_grades fg ON s.student_id = fg.student_id AND sub.subject_id = fg.subject_id
                WHERE p.program_code = 'BIS'
                ORDER BY s.reg_number, sub.subject_name, at.assessment_name
            ");
            $stmt->execute();
            $query_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Part A - SQL Query Report</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            color: #333;
        }
        .header {
            background-color: #4A2C2A;
            color: white;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .query-selector {
            margin-bottom: 20px;
            text-align: center;
        }
        .query-selector label {
            font-size: 18px;
            margin-right: 10px;
        }
        .query-selector select {
            padding: 10px;
            font-size: 16px;
            border-radius: 5px;
            border: 1px solid #ccc;
            width: 300px;
        }
        .query-selector button {
            padding: 10px 20px;
            background: #4A2C2A;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-left: 10px;
        }
        .query-selector button:hover {
            background: #6B4E31;
        }
        .results {
            margin-top: 20px;
        }
        .results h2 {
            color: #4A2C2A;
            font-size: 24px;
            margin-bottom: 10px;
        }
        .results p {
            color: #666;
            font-size: 16px;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #4A2C2A;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .no-results {
            text-align: center;
            color: #666;
            font-size: 16px;
        }
        @media (max-width: 768px) {
            .container {
                margin: 10px;
                padding: 15px;
            }
            .query-selector select {
                width: 100%;
                margin-bottom: 10px;
            }
            .query-selector button {
                width: 100%;
                margin-left: 0;
            }
            table {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Part A - SQL Query Report</h1>
    </div>
    <div class="container">
        <div class="query-selector">
            <form method="GET">
                <label for="query">Select Query:</label>
                <select name="query" id="query" onchange="this.form.submit()">
                    <option value="">-- Select a Query --</option>
                    <option value="q1" <?php echo $selected_query == 'q1' ? 'selected' : ''; ?>>1. Students Repeating the Year</option>
                    <option value="q2" <?php echo $selected_query == 'q2' ? 'selected' : ''; ?>>2. Passed DMS, Failed OS2</option>
                    <option value="q3" <?php echo $selected_query == 'q3' ? 'selected' : ''; ?>>3. DSA-301 Performance by Gender</option>
                    <option value="q4" <?php echo $selected_query == 'q4' ? 'selected' : ''; ?>>4. Distinction Average Students</option>
                    <option value="q5" <?php echo $selected_query == 'q5' ? 'selected' : ''; ?>>5. Subjects by Program and Lecturers</option>
                    <option value="q6" <?php echo $selected_query == 'q6' ? 'selected' : ''; ?>>6. BIS Gradebook</option>
                </select>
            </form>
        </div>

        <?php if ($selected_query): ?>
            <div class="results">
                <h2><?php echo htmlspecialchars($query_title); ?></h2>
                <p><?php echo htmlspecialchars($query_description); ?></p>
                
                <?php if (count($query_results) > 0): ?>
                    <p>Number of results: <?php echo count($query_results); ?></p>
                    <table>
                        <thead>
                            <tr>
                                <?php foreach (array_keys($query_results[0]) as $column): ?>
                                    <th><?php echo htmlspecialchars(str_replace('_', ' ', ucwords($column))); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($query_results as $row): ?>
                                <tr>
                                    <?php foreach ($row as $value): ?>
                                        <?php if ($selected_query == 'q6'): ?>
                                            <td><?php echo htmlspecialchars($value ?? 0); ?></td>
                                        <?php else: ?>
                                            <td><?php echo htmlspecialchars($value ?? 'NULL'); ?></td>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="no-results">No results found (<?php echo count($query_results); ?> rows). Query execution status: <?php echo ($stmt ? 'Success' : 'Failed'); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>