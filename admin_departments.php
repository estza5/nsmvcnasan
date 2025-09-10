<?php
// --- ส่วนประมวลผลฟอร์ม ---

// จัดการการเพิ่มแผนกใหม่
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_department'])) {
    $dept_name = trim($_POST['department_name'] ?? '');
    $dept_level = trim($_POST['department_level'] ?? '');
    if (!empty($dept_name) && !empty($dept_level)) {
        $stmt = $conn->prepare("INSERT INTO departments (name, level) VALUES (?, ?)");
        $stmt->bind_param("ss", $dept_name, $dept_level);
        if ($stmt->execute()) {
            echo '<p style="color: green;">เพิ่มแผนกเรียบร้อยแล้ว</p>';
        } else {
            echo '<p style="color: red;">มีข้อผิดพลาด: ' . htmlspecialchars($stmt->error) . '</p>';
        }
        $stmt->close();
    } else {
        echo '<p style="color: red;">กรุณากรอกชื่อแผนกและชั้นปีให้ครบถ้วน</p>';
    }
}

// จัดการการแก้ไขแผนก
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_department'])) {
    $dept_id = (int)$_POST['department_id'];
    $dept_name = trim($_POST['department_name'] ?? '');
    $dept_level = trim($_POST['department_level'] ?? '');

    if (!empty($dept_name) && !empty($dept_level) && $dept_id > 0) {
        $stmt = $conn->prepare("UPDATE departments SET name = ?, level = ? WHERE id = ?");
        $stmt->bind_param("ssi", $dept_name, $dept_level, $dept_id);
        if ($stmt->execute()) {
            echo '<p style="color: green;">แก้ไขแผนกเรียบร้อยแล้ว</p>';
        } else {
            echo '<p style="color: red;">มีข้อผิดพลาดในการแก้ไข: ' . htmlspecialchars($stmt->error) . '</p>';
        }
        $stmt->close();
    } else {
        echo '<p style="color: red;">กรุณากรอกชื่อแผนกและชั้นปีให้ครบถ้วน</p>';
    }
}

// จัดการการลบแผนก
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_department'])) {
    $dept_id = (int)$_POST['department_id'];
    $stmt = $conn->prepare("DELETE FROM departments WHERE id = ?");
    $stmt->bind_param("i", $dept_id);
    if ($stmt->execute()) {
        echo '<p style="color: green;">ลบแผนกเรียบร้อยแล้ว</p>';
    } else {
        echo '<p style="color: red;">มีข้อผิดพลาดในการลบ: ' . htmlspecialchars($stmt->error) . '</p>';
    }
    $stmt->close();
}

// จัดการการกำหนดครูที่ปรึกษา
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_advisor'])) {
    $dept_id = (int)$_POST['department_id'];
    $advisor_id = !empty($_POST['advisor_id']) ? (int)$_POST['advisor_id'] : null;

    $stmt = $conn->prepare("UPDATE departments SET advisor_id = ? WHERE id = ?");
    $stmt->bind_param("ii", $advisor_id, $dept_id);
    if ($stmt->execute()) {
        echo '<p style="color: green;">อัปเดตครูที่ปรึกษาเรียบร้อยแล้ว</p>';
    } else {
        echo '<p style="color: red;">มีข้อผิดพลาดในการอัปเดต: ' . htmlspecialchars($stmt->error) . '</p>';
    }
    $stmt->close();
}

// --- ส่วนแสดงผล ---

// ดึงข้อมูลครูทั้งหมดสำหรับ Dropdown
$teachers_result = $conn->query("SELECT id, first_name, last_name FROM users WHERE role = 'teacher' AND status = 'approved' ORDER BY first_name");
$teachers = [];
if ($teachers_result) {
    while ($teacher = $teachers_result->fetch_assoc()) {
        $teachers[] = $teacher;
    }
}

// ดึงข้อมูลแผนกทั้งหมดพร้อมชื่อครูที่ปรึกษา
$departments_result = $conn->query("
    SELECT d.id, d.name, d.level, d.advisor_id, u.first_name as advisor_fname, u.last_name as advisor_lname " .
    // The rest of your query
    "FROM departments d LEFT JOIN users u ON d.advisor_id = u.id AND u.role = 'teacher' ORDER BY d.name"
);

// ดึงข้อมูลสำหรับ Filter
$distinct_levels = $conn->query("SELECT DISTINCT level FROM departments ORDER BY level")->fetch_all(MYSQLI_ASSOC);
$distinct_names = $conn->query("SELECT DISTINCT name FROM departments ORDER BY name")->fetch_all(MYSQLI_ASSOC);

$search_level = $_GET['search_level'] ?? '';
$search_name = $_GET['search_name'] ?? '';

$sql_departments = "SELECT d.id, d.name, d.level, d.advisor_id, u.first_name as advisor_fname, u.last_name as advisor_lname
                    FROM departments d
                    LEFT JOIN users u ON d.advisor_id = u.id AND u.role = 'teacher'";

$conditions = [];
if (!empty($search_level)) {
    $conditions[] = "d.level = '" . $conn->real_escape_string($search_level) . "'";
}
if (!empty($search_name)) {
    $conditions[] = "d.name = '" . $conn->real_escape_string($search_name) . "'";
}

if (!empty($conditions)) {
    $sql_departments .= " WHERE " . implode(' AND ', $conditions);
}

$sql_departments .= " ORDER BY d.name, d.level";

$departments_result = $conn->query($sql_departments);
?>

<h4>เพิ่มแผนกใหม่</h4>
<form action="admin_dashboard.php?page=departments" method="post" style="margin-bottom: 30px;">
    <div class="form-group">
        <label for="department_name">ชื่อแผนก:</label>
        <input type="text" id="department_name" name="department_name" required style="width: 250px; padding: 8px; border: 1px solid #ccc; border-radius: 4px; margin-right: 10px;">
        <label for="department_level">ชั้นปี:</label>
        <input type="text" id="department_level" name="department_level" required placeholder="เช่น ปวช. 1" style="width: 150px; padding: 8px; border: 1px solid #ccc; border-radius: 4px; margin-right: 10px;">
        <button type="submit" name="add_department" style="background-color: #3498db; color: white; border: none; padding: 9px 15px; border-radius: 4px; cursor: pointer;">เพิ่มแผนก</button>
    </div>
</form>

<hr style="margin: 30px 0;">

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
    <h4>รายชื่อแผนกทั้งหมด</h4>
    <form action="admin_dashboard.php" method="get" style="display: flex; gap: 10px; align-items: center;">
        <input type="hidden" name="page" value="departments">
        <label for="search_name">แผนก:</label>
        <select name="search_name" id="search_name" style="padding: 5px; border-radius: 4px;">
            <option value="">-- ทั้งหมด --</option>
            <?php foreach ($distinct_names as $name): ?>
                <option value="<?php echo htmlspecialchars($name['name']); ?>" <?php if ($search_name === $name['name']) echo 'selected'; ?>><?php echo htmlspecialchars($name['name']); ?></option>
            <?php endforeach; ?>
        </select>
        <label for="search_level">ชั้นปี:</label>
        <select name="search_level" id="search_level" style="padding: 5px; border-radius: 4px;">
            <option value="">-- ทั้งหมด --</option>
            <?php foreach ($distinct_levels as $level): ?>
                <option value="<?php echo htmlspecialchars($level['level']); ?>" <?php if ($search_level === $level['level']) echo 'selected'; ?>><?php echo htmlspecialchars($level['level']); ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" style="background-color: #3498db; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer;">ค้นหา</button>
    </form>
</div>

<table style="width: 100%; border-collapse: collapse;">
    <tr style="background-color: #f2f2f2;">
        <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">ชื่อแผนก</th>
        <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">ชั้นปี</th>
        <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">ครูที่ปรึกษา</th>
        <th style="padding: 8px; border: 1px solid #ddd; text-align: center;">จัดการ</th>
    </tr>
    <?php if ($departments_result && $departments_result->num_rows > 0): ?>
        <?php while($dept = $departments_result->fetch_assoc()): ?>
        <tr>
            <td colspan="2" style="padding: 8px; border: 1px solid #ddd;">
                <form action="admin_dashboard.php?page=departments" method="POST" style="display: flex; align-items: center; gap: 10px;">
                    <input type="hidden" name="department_id" value="<?php echo $dept['id']; ?>" />
                    <input type="text" name="department_name" value="<?php echo htmlspecialchars($dept['name']); ?>" required style="padding: 5px; border-radius: 4px; border: 1px solid #ccc; width: 60%;" />
                    <input type="text" name="department_level" value="<?php echo htmlspecialchars($dept['level']); ?>" required style="padding: 5px; border-radius: 4px; border: 1px solid #ccc; width: 30%;" />
                    <button type="submit" name="edit_department" style="background-color: #f39c12; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer;">แก้ไข</button>
                </form>
            </td>
            <td style="padding: 8px; border: 1px solid #ddd;">
                <form action="admin_dashboard.php?page=departments" method="POST" style="display: flex; align-items: center; gap: 10px;">
                    <input type="hidden" name="department_id" value="<?php echo $dept['id']; ?>">
                    <select name="advisor_id" style="padding: 5px; border-radius: 4px; border: 1px solid #ccc;">
                        <option value="">-- ไม่กำหนด --</option>
                        <?php foreach ($teachers as $teacher): ?>
                            <option value="<?php echo $teacher['id']; ?>" <?php if ($teacher['id'] == $dept['advisor_id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="assign_advisor" style="background-color: #27ae60; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer;">บันทึก</button>
                </form>
            </td>
            <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">
                <form action="admin_dashboard.php?page=departments" method="POST" onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบแผนกนี้?');">
                    <input type="hidden" name="department_id" value="<?php echo $dept['id']; ?>">
                    <button type="submit" name="delete_department" style="background: none; border: none; color: red; cursor: pointer; text-decoration: underline;">ลบ</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="4" style="padding: 8px; border: 1px solid #ddd; text-align: center;">ยังไม่มีแผนกในระบบ</td></tr>
    <?php endif; ?>
</table>