<?php
session_start();
date_default_timezone_set('Asia/Bangkok');

// ตรวจสอบว่าล็อกอินหรือยัง และมี role เป็น teacher หรือไม่
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'teacher') {
    header('Location: index.php');
    exit;
}

include 'db_connect.php';

$teacher_id = $_SESSION['user_id'];
$today_date = date('Y-m-d');

// --- ส่วนประมวลผลฟอร์ม ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    $attendance_data = $_POST['attendance'] ?? [];

    // ปรับปรุง: สำหรับครูเวร จะเป็นการบันทึกเฉพาะคนมาสาย
    $stmt = $conn->prepare("
        INSERT INTO attendance (student_id, status, check_in_date, check_in_time, checked_by_user_id, late_reason)
        VALUES (?, 'late', ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE status = 'late', check_in_time = VALUES(check_in_time), checked_by_user_id = VALUES(checked_by_user_id), late_reason = VALUES(late_reason)
    ");

    if ($stmt) {
        $late_student_ids = $_POST['late_students'] ?? [];
        $late_reasons = $_POST['late_reason'] ?? [];

        foreach ($late_student_ids as $student_id) {
            $status = 'late'; // กำหนดสถานะเป็นมาสายเสมอ
            if (!empty($status)) {
                $check_in_time = $today_date . ' ' . date('H:i:s');
                $reason = $late_reasons[$student_id] ?? '';
                // Bind parameters: student_id, check_in_date, check_in_time, teacher_id, late_reason
                $stmt->bind_param("issis", $student_id, $today_date, $check_in_time, $teacher_id, $reason);
                $stmt->execute();
            }
        }
        $stmt->close();
        $_SESSION['teacher_message'] = ['type' => 'success', 'text' => 'บันทึกการเช็คชื่อเรียบร้อยแล้ว'];
    } else {
        $_SESSION['teacher_message'] = ['type' => 'error', 'text' => 'เกิดข้อผิดพลาดในการเตรียมบันทึกข้อมูล'];
    }
    header("Location: " . $_SERVER['REQUEST_URI']); // กลับมาที่หน้าเดิมพร้อม filter
    exit;
}

// --- ส่วนดึงข้อมูล ---

// 1. ตรวจสอบว่าครูเข้าเวรประจำวันนี้หรือไม่
$is_on_duty = false;
$day_of_week = date('N'); // 1 for Monday, 7 for Sunday
$duty_stmt = $conn->prepare("SELECT user_id FROM weekly_duty WHERE day_of_week = ?");
if ($duty_stmt) {
    $duty_stmt->bind_param("i", $day_of_week);
    $duty_stmt->execute();
    $duty_result = $duty_stmt->get_result(); // Get result
    if ($duty_row = $duty_result->fetch_assoc()) { // Fetch data
        if ($duty_row['user_id'] == $teacher_id) {
            $is_on_duty = true;
        }
    }
    $duty_stmt->close();
}

// 2. กำหนดหน้าที่จะแสดงผลจาก URL parameter
$page = $_GET['page'] ?? 'advisees'; // หน้าเริ่มต้นคือหน้านักเรียนในที่ปรึกษา

// ดึงข้อมูลแผนกสำหรับใช้ใน Filter
$departments_result = $conn->query("SELECT id, name, level FROM departments ORDER BY name, level");
$departments = $departments_result->fetch_all(MYSQLI_ASSOC);

$students = [];
$page_title = '';
$no_students_message = '';

// 3. ดึงข้อมูลนักเรียนตามหน้าที่ที่เลือก
if ($page === 'duty' && $is_on_duty) {
    // กรณีเป็นครูเวร และเลือกเมนูเช็คชื่อทั้งหมด
    $page_title = "เช็คชื่อนักเรียนทั้งหมด (เวรประจำวัน)";
    $no_students_message = "ไม่พบนักเรียนในระบบ";
    $search_dept_id = isset($_GET['search_dept']) ? (int)$_GET['search_dept'] : 0;

    $sql = "SELECT s.id, s.student_code, s.first_name, s.last_name, a.status, d.name as department_name, d.level as department_level 
        FROM students s
        JOIN departments d ON s.department_id = d.id
        LEFT JOIN attendance a ON s.id = a.student_id AND a.check_in_date = '{$today_date}' -- Corrected JOIN condition
    ";

    if ($search_dept_id > 0) {
        $sql .= " WHERE s.department_id = " . $search_dept_id;
    }

    $sql .= " ORDER BY d.name, d.level, s.student_code";

    $students_result = $conn->query($sql);

    if ($students_result) {
        while ($student = $students_result->fetch_assoc()) {
            $students[] = $student;
        }
    }
} 
else { // Default page or 'advisees' page
    // กรณีปกติ: ดึงเฉพาะนักเรียนในที่ปรึกษา
    $page_title = "เช็คชื่อนักเรียนในที่ปรึกษา";

    // หาแผนกที่ครูคนนี้เป็นที่ปรึกษา
    $stmt = $conn->prepare("SELECT id FROM departments WHERE advisor_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $teacher_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $department_ids = [];
        while ($row = $result->fetch_assoc()) {
            $department_ids[] = $row['id'];
        }
        $stmt->close();

        if (!empty($department_ids)) {
            // ดึงรายชื่อนักเรียนในแผนกนั้นๆ
            $id_list = implode(',', $department_ids);
            $students_result = $conn->query("
                SELECT s.id, s.student_code, s.first_name, s.last_name, a.status 
                FROM students s
                LEFT JOIN attendance a ON s.id = a.student_id AND a.check_in_date = '{$today_date}' -- Corrected JOIN condition
                WHERE s.department_id IN ({$id_list})
                ORDER BY s.student_code
            ");
            if ($students_result) {
                while ($student = $students_result->fetch_assoc()) {
                    $students[] = $student;
                }
            }
            $no_students_message = "ไม่พบนักเรียนในแผนกที่คุณเป็นที่ปรึกษา";
        } else {
            $no_students_message = "คุณยังไม่ได้รับมอบหมายให้เป็นครูที่ปรึกษาของแผนกใดๆ";
        }
    }
}

$status_options = ['present' => 'มา', 'late' => 'สาย', 'absent' => 'ขาด', 'on_leave' => 'ลา'];
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Teacher Dashboard</title>
    <link rel="stylesheet" href="admin_style.css"> <!-- ใช้ CSS ร่วมกับแอดมินเพื่อให้หน้าตาคล้ายกัน -->
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <h3>Teacher Panel</h3>
            <p>สวัสดี, คุณ <?php 
                $display_name = isset($_SESSION['first_name']) && !empty($_SESSION['first_name']) 
                                ? $_SESSION['first_name'] . ' ' . $_SESSION['last_name'] 
                                : $_SESSION['username'];
                echo htmlspecialchars($display_name); 
            ?></p>
            <ul>
                <li><a href="teacher_dashboard.php?page=advisees">เช็คชื่อนักเรียนที่ปรึกษา</a></li>
                <?php if ($is_on_duty): ?>
                <li><a href="teacher_dashboard.php?page=duty">เช็คชื่อนักเรียนทั้งหมด (เวรประจำวัน)</a></li>
                <?php endif; ?>
            </ul>
            <a href="logout.php" class="logout-btn">ออกจากระบบ</a>
        </div>
        <div class="main-content">
            <div class="content-header"><h1><?php echo $page_title; ?> (<?php echo $today_date; ?>)</h1></div>
            <div class="content-body">
                <?php if (isset($_SESSION['teacher_message'])): ?>
                    <div style="padding: 10px; margin-bottom: 15px; border-radius: 5px; color: white; background-color:<?php echo $_SESSION['teacher_message']['type'] === 'success' ? 'green' : 'red'; ?>;">
                        <?php echo htmlspecialchars($_SESSION['teacher_message']['text']); ?>
                    </div>
                    <?php unset($_SESSION['teacher_message']); ?>
                <?php endif; ?>

                <?php if ($page === 'duty' && $is_on_duty): ?>
                    <!-- ฟอร์มสำหรับครูเวร -->
                    <form action="teacher_dashboard.php" method="get" style="margin-bottom: 20px; display: flex; gap: 10px; align-items: center;">
                        <input type="hidden" name="page" value="duty">
                        <label for="search_dept">กรองตามแผนก:</label>
                        <select name="search_dept" id="search_dept" onchange="this.form.submit()" style="padding: 5px; border-radius: 4px;">
                            <option value="0">-- แสดงทั้งหมด --</option>
                            <?php foreach ($departments as $dept):
                                $selected = ($search_dept_id == $dept['id']) ? 'selected' : '';
                                echo "<option value='{$dept['id']}' {$selected}>".htmlspecialchars($dept['name'] . ' - ' . $dept['level'])."</option>";
                            endforeach; ?>
                        </select>
                    </form>
                    <form action="teacher_dashboard.php?page=duty&search_dept=<?php echo $search_dept_id; ?>" method="post">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr style="background-color: #f2f2f2;">
                                <th style="padding: 8px; border: 1px solid #ddd; width: 50px;">มาสาย</th>
                                <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">ชื่อ - นามสกุล</th>
                                <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">แผนก</th>
                                <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">เหตุผลที่มาสาย</th>
                            </tr>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">
                                        <input type="checkbox" name="late_students[]" value="<?php echo $student['id']; ?>" <?php if ($student['status'] === 'late') echo 'checked'; ?>>
                                    </td>
                                    <td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                    <td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars($student['department_name'] . ' - ' . $student['department_level']); ?></td>
                                    <td style="padding: 8px; border: 1px solid #ddd;">
                                        <input type="text" name="late_reason[<?php echo $student['id']; ?>]" style="width: 95%; padding: 4px;" placeholder="ระบุเหตุผล (ถ้ามี)">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                        <button type="submit" name="save_attendance" style="background-color: #e67e22; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; margin-top: 20px;">บันทึกข้อมูลคนมาสาย</button>
                    </form>
                <?php elseif (!empty($students)): ?>
                    <!-- ฟอร์มสำหรับครูที่ปรึกษา (เหมือนเดิม) -->
                    <p>ส่วนของครูที่ปรึกษาจะแสดงที่นี่</p>
                <?php else: ?>
                    <p><?php echo $no_students_message; ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>