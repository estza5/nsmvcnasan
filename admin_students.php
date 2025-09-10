<?php
// --- ส่วนประมวลผลฟอร์ม ---

// จัดการการเพิ่มนักเรียนทีละคน
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {
    $student_code = trim($_POST['student_code'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $department_id = (int)($_POST['department_id'] ?? 0);

    if (!empty($student_code) && !empty($first_name) && !empty($last_name) && !empty($gender) && $department_id > 0) {
        $stmt = $conn->prepare("INSERT INTO students (student_code, first_name, last_name, gender, department_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $student_code, $first_name, $last_name, $gender, $department_id);
        if ($stmt->execute()) {
            $_SESSION['admin_message'] = ['type' => 'success', 'text' => 'เพิ่มนักเรียนเรียบร้อยแล้ว'];
        } else {
            $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'มีข้อผิดพลาดในการเพิ่มนักเรียน: ' . htmlspecialchars($stmt->error)];
        }
        $stmt->close();
    } else {
        $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'กรุณากรอกข้อมูลนักเรียนให้ครบทุกช่อง'];
    }
    header("Location: admin_dashboard.php?page=students");
    exit;
}

// จัดการการแก้ไขนักเรียน
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_student'])) {
    $student_id = (int)($_POST['student_id'] ?? 0);
    $student_code = trim($_POST['student_code'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $department_id = (int)($_POST['department_id'] ?? 0);

    if ($student_id > 0 && !empty($student_code) && !empty($first_name) && !empty($last_name) && !empty($gender) && $department_id > 0) {
        $stmt = $conn->prepare("UPDATE students SET student_code = ?, first_name = ?, last_name = ?, gender = ?, department_id = ? WHERE id = ?");
        $stmt->bind_param("ssssii", $student_code, $first_name, $last_name, $gender, $department_id, $student_id);
        if ($stmt->execute()) {
            $_SESSION['admin_message'] = ['type' => 'success', 'text' => 'แก้ไขข้อมูลนักเรียนเรียบร้อยแล้ว'];
        } else {
            $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'มีข้อผิดพลาดในการแก้ไข: ' . htmlspecialchars($stmt->error)];
        }
        $stmt->close();
    } else {
        $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'กรุณากรอกข้อมูลให้ครบถ้วน'];
    }
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'admin_dashboard.php?page=students'));
    exit;
}

// จัดการการลบนักเรียนตามแผนก
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_by_department'])) {
    $dept_id_to_delete = (int)($_POST['department_id_to_delete'] ?? 0);
    if ($dept_id_to_delete > 0) {
        $stmt = $conn->prepare("DELETE FROM students WHERE department_id = ?");
        $stmt->bind_param("i", $dept_id_to_delete);
        if ($stmt->execute()) {
            $_SESSION['admin_message'] = ['type' => 'success', 'text' => 'ลบนักเรียนในแผนกที่เลือกเรียบร้อยแล้ว'];
        } else {
            $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'มีข้อผิดพลาดในการลบ: ' . htmlspecialchars($stmt->error)];
        }
        $stmt->close();
    } else {
        $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'กรุณาเลือกแผนกที่ต้องการลบ'];
    }
    header("Location: admin_dashboard.php?page=students");
    exit;
}

// จัดการการลบนักเรียนที่เลือก
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_selected'])) {
    $student_ids_to_delete = $_POST['student_ids'] ?? [];

    if (!empty($student_ids_to_delete)) {
        // Sanitize all IDs to be integers
        $sanitized_ids = array_map('intval', $student_ids_to_delete);

        // Create placeholders for the IN clause
        $placeholders = implode(',', array_fill(0, count($sanitized_ids), '?'));
        
        $stmt = $conn->prepare("DELETE FROM students WHERE id IN ($placeholders)");
        
        $types = str_repeat('i', count($sanitized_ids));
        $stmt->bind_param($types, ...$sanitized_ids);

        if ($stmt->execute()) {
            $_SESSION['admin_message'] = ['type' => 'success', 'text' => 'ลบนักเรียนที่เลือก ' . $stmt->affected_rows . ' คนเรียบร้อยแล้ว'];
        } else {
            $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'มีข้อผิดพลาดในการลบนักเรียนที่เลือก: ' . htmlspecialchars($stmt->error)];
        }
        $stmt->close();
    } else {
        $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'กรุณาเลือกนักเรียนที่ต้องการลบ'];
    }
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'admin_dashboard.php?page=students')); // Redirect back to the previous page
    exit;
}

// จัดการการลบนักเรียน
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_student'])) {
    $student_id = (int)$_POST['student_id'];
    $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
    $stmt->bind_param("i", $student_id);
    if ($stmt->execute()) {
        $_SESSION['admin_message'] = ['type' => 'success', 'text' => 'ลบนักเรียนเรียบร้อยแล้ว'];
    } else {
        $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'มีข้อผิดพลาดในการลบ: ' . htmlspecialchars($stmt->error)];
    }
    $stmt->close();
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'admin_dashboard.php?page=students')); // รีเฟรชหน้าเดิมเพื่อแสดงผล
    exit;
}

// ดึงข้อมูลแผนกทั้งหมดสำหรับ Dropdown
$departments_query = "SELECT id, name, level FROM departments ORDER BY name, level";
$departments_result_for_forms = $conn->query($departments_query);

// ดึงข้อมูลนักเรียนทั้งหมด
$search_dept_id = isset($_GET['search_dept']) ? (int)$_GET['search_dept'] : 0;

$sql = "SELECT s.id, s.student_code, s.first_name, s.last_name, s.gender, s.department_id, d.name as department_name, d.level as department_level
        FROM students s
        JOIN departments d ON s.department_id = d.id";

if ($search_dept_id > 0) {
    $sql .= " WHERE s.department_id = ?";
}

$sql .= " ORDER BY d.name, d.level, s.student_code";

$stmt = $conn->prepare($sql);
if ($search_dept_id > 0) {
    $stmt->bind_param("i", $search_dept_id);
}
$stmt->execute();
$students_result = $stmt->get_result();
?>

<h4>เพิ่มรายชื่อนักเรียน</h4>
<div style="display: flex; gap: 20px; margin-bottom: 20px;">
    <!-- ฟอร์มเพิ่มทีละคน -->
    <div style="flex: 1; border: 1px solid #eee; padding: 15px; border-radius: 5px;">
        <h5>เพิ่มนักเรียนทีละคน</h5>
        <form action="admin_dashboard.php?page=students" method="post" id="add-student-form">
            <div class="form-group">
                <label for="student_code">รหัสนักเรียน:</label>
                <input type="text" name="student_code" id="student_code" required>
            </div>
            <div class="form-group">
                <label for="first_name">ชื่อจริง:</label>
                <input type="text" name="first_name" id="first_name" required>
            </div>
            <div class="form-group">
                <label for="last_name">นามสกุล:</label>
                <input type="text" name="last_name" id="last_name" required>
            </div>
            <div class="form-group">
                <label for="gender">เพศ:</label>
                <select name="gender" id="gender" required>
                    <option value="">-- เลือกเพศ --</option>
                    <option value="M">ชาย</option>
                    <option value="F">หญิง</option>
                </select>
            </div>
            <div class="form-group">
                <label for="department_id_single">แผนก:</label>
                <select name="department_id" id="department_id_single" required>
                    <option value="">-- กรุณาเลือกแผนก --</option>
                    <?php
                        // โค้ดสำหรับแสดงตัวเลือกแผนกจะถูกเพิ่มที่นี่
                    ?>
                </select>
            </div>
            <button type="submit" name="add_student" style="background-color: #3498db; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer;">เพิ่มนักเรียน</button>
        </form>
    </div>
    <!-- ฟอร์มเพิ่มจาก CSV -->
    <div style="flex: 1; border: 1px solid #eee; padding: 15px; border-radius: 5px;">
        <h5>เพิ่มจากไฟล์ CSV</h5>
        <form action="upload_students_csv.php" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="department_csv">เลือกแผนกสำหรับนักเรียนในไฟล์นี้:</label>
                <select name="department_id" id="department_csv" required>
                    <option value="">-- กรุณาเลือกแผนก --</option>
                    <?php
                        if ($departments_result_for_forms->num_rows > 0) {
                            $departments_result_for_forms->data_seek(0); // รีเซ็ต pointer สำหรับวนลูปใหม่
                            while ($row = $departments_result_for_forms->fetch_assoc()) {
                                echo "<option value='{$row['id']}'>".htmlspecialchars($row['name'] . ' - ' . $row['level'])."</option>";
                            }
                         }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="csv_file">ไฟล์ CSV (student_code, first_name, last_name, gender):
                    <a href="sample_students.csv" download style="font-weight: normal; text-decoration: underline;">(ดาวน์โหลดไฟล์ตัวอย่าง)</a>
                </label>
                <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
            </div>
            <button type="submit" name="upload" style="background-color: #27ae60; border: none; color: white; padding: 10px 15px; border-radius: 4px; cursor: pointer;">อัปโหลด</button>
        </form>
    </div>
    <script>
        // ทำให้ Dropdown ของแผนกในฟอร์ม "เพิ่มทีละคน" มีข้อมูลเหมือนกับฟอร์ม CSV
        document.addEventListener('DOMContentLoaded', function() {
            const csvSelect = document.getElementById('department_csv');
            const singleSelect = document.getElementById('department_id_single');
            
            // ล้างตัวเลือกเก่า (ยกเว้นตัวเลือกแรก)
            while (singleSelect.options.length > 1) {
                singleSelect.remove(1);
            }

            // คัดลอกตัวเลือกจากฟอร์ม CSV มาใส่
            for (let i = 1; i < csvSelect.options.length; i++) {
                singleSelect.add(csvSelect.options[i].cloneNode(true));
            }
        });
    </script>
</div>

<hr style="margin: 30px 0;">

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
    <h4>รายชื่อนักเรียนทั้งหมด</h4>
    <form action="admin_dashboard.php" method="get" style="display: flex; gap: 10px; align-items: center;">
        <input type="hidden" name="page" value="students">
        <label for="search_dept">ค้นหาตามแผนก:</label>
        <select name="search_dept" id="search_dept" onchange="this.form.submit()" style="padding: 5px; border-radius: 4px;">
            <option value="0">-- แสดงทั้งหมด --</option>
            <?php
                if ($departments_result_for_forms->num_rows > 0) {
                    $departments_result_for_forms->data_seek(0);
                    while ($row = $departments_result_for_forms->fetch_assoc()) {
                        $selected = ($search_dept_id == $row['id']) ? 'selected' : '';
                        echo "<option value='{$row['id']}' {$selected}>".htmlspecialchars($row['name'] . ' - ' . $row['level'])."</option>";
                    }
                }
            ?>
        </select>
    </form>
</div>

<form action="admin_dashboard.php?page=students" method="POST" id="student-list-form">
    <table style="width: 100%; border-collapse: collapse;">
        <tr style="background-color: #f2f2f2;">
            <th style="padding: 8px; border: 1px solid #ddd; width: 30px;"><input type="checkbox" id="select-all"></th>
            <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">รหัสนักเรียน</th>
            <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">ชื่อ - นามสกุล</th>
            <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">แผนก</th>
            <th style="padding: 8px; border: 1px solid #ddd; text-align: center;">จัดการ</th>
        </tr>
        <?php if ($students_result && $students_result->num_rows > 0): ?>
            <?php while($student = $students_result->fetch_assoc()): ?>
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">
                    <input type="checkbox" name="student_ids[]" value="<?php echo $student['id']; ?>" class="student-checkbox">
                </td>
                <td colspan="3" style="padding: 0; border: 1px solid #ddd;">
                    <form action="admin_dashboard.php?page=students&search_dept=<?php echo $search_dept_id; ?>" method="POST" style="display: flex; gap: 5px; align-items: center; padding: 8px; width: 100%; box-sizing: border-box;">
                        <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                        <input type="text" name="student_code" value="<?php echo htmlspecialchars($student['student_code']); ?>" required title="รหัสนักเรียน" style="flex: 2; padding: 5px; border: 1px solid #ccc; border-radius: 4px;">
                        <input type="text" name="first_name" value="<?php echo htmlspecialchars($student['first_name']); ?>" required title="ชื่อจริง" style="flex: 2; padding: 5px; border: 1px solid #ccc; border-radius: 4px;">
                        <input type="text" name="last_name" value="<?php echo htmlspecialchars($student['last_name']); ?>" required title="นามสกุล" style="flex: 2; padding: 5px; border: 1px solid #ccc; border-radius: 4px;">
                        <select name="gender" required title="เพศ" style="flex: 1; padding: 5px; border: 1px solid #ccc; border-radius: 4px;">
                            <option value="M" <?php if($student['gender'] == 'M') echo 'selected'; ?>>ชาย</option>
                            <option value="F" <?php if($student['gender'] == 'F') echo 'selected'; ?>>หญิง</option>
                        </select>
                        <select name="department_id" required title="แผนก" style="flex: 3; padding: 5px; border: 1px solid #ccc; border-radius: 4px;">
                            <?php
                                if ($departments_result_for_forms->num_rows > 0) {
                                    $departments_result_for_forms->data_seek(0);
                                    while ($row = $departments_result_for_forms->fetch_assoc()) {
                                        $selected = ($student['department_id'] == $row['id']) ? 'selected' : '';
                                        echo "<option value='{$row['id']}' {$selected}>".htmlspecialchars($row['name'] . ' - ' . $row['level'])."</option>";
                                    }
                                }
                            ?>
                        </select>
                        <button type="submit" name="edit_student" style="background-color: #f39c12; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer;">บันทึก</button>
                    </form>
                </td>
                <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">
                    <form action="admin_dashboard.php?page=students&search_dept=<?php echo $search_dept_id; ?>" method="POST" onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบนักเรียนคนนี้?');" style="display:inline;">
                        <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                        <button type="submit" name="delete_student" style="background: none; border: none; color: red; cursor: pointer; text-decoration: underline;">ลบ</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="3" style="padding: 8px; border: 1px solid #ddd; text-align: center;">ไม่พบข้อมูลนักเรียนตามเงื่อนไขที่ค้นหา</td></tr>
        <?php endif; ?>
    </table>
    <div style="margin-top: 15px;">
        <button type="submit" name="delete_selected" class="btn-delete-selected" style="background-color: #e74c3c; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer;" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบนักเรียนที่เลือกทั้งหมด?');">ลบรายการที่เลือก</button>
    </div>
</form>

<script>
document.getElementById('select-all').addEventListener('click', function(event) {
    var checkboxes = document.querySelectorAll('.student-checkbox');
    for (var checkbox of checkboxes) {
        checkbox.checked = event.target.checked;
    }
});
</script>