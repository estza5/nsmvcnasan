<?php
// --- ส่วนประมวลผลฟอร์ม ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_duty'])) {
    $duty_assignments = $_POST['duty'] ?? [];

    // ล้างข้อมูลเก่าทั้งหมดเพื่อเตรียมบันทึกใหม่
    $conn->query("TRUNCATE TABLE weekly_duty");

    // เตรียมคำสั่งสำหรับเพิ่มข้อมูลใหม่
    $stmt = $conn->prepare("INSERT INTO weekly_duty (day_of_week, user_id) VALUES (?, ?)");

    foreach ($duty_assignments as $day_of_week => $user_id) {
        // บันทึกเฉพาะที่มีการเลือกครูเท่านั้น
        if (!empty($user_id)) {
            $stmt->bind_param("ii", $day_of_week, $user_id);
            $stmt->execute();
        }
    }
    $stmt->close();
    echo '<p style="color: green;">บันทึกตารางเวรเรียบร้อยแล้ว</p>';
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

// ดึงข้อมูลเวรที่กำหนดไว้แล้ว
$duty_schedule = [];
$duty_result = $conn->query("SELECT day_of_week, user_id FROM weekly_duty");
if ($duty_result) {
    while ($row = $duty_result->fetch_assoc()) {
        $duty_schedule[$row['day_of_week']] = $row['user_id'];
    }
}

$days_of_week_th = [
    1 => "จันทร์", 
    2 => "อังคาร", 
    3 => "พุธ", 
    4 => "พฤหัสบดี", 
    5 => "ศุกร์"
];
?>

<h4>ตั้งค่าเวรประจำสัปดาห์</h4>
<form action="admin_dashboard.php?page=duty" method="post">
    <table style="width: 100%; border-collapse: collapse;">
        <tr style="background-color: #f2f2f2;">
            <th style="padding: 8px; border: 1px solid #ddd; text-align: left; width: 20%;">วัน</th>
            <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">เลือกครูผู้รับผิดชอบ</th>
        </tr>
        <?php foreach ($days_of_week_th as $day_number => $day_name): 
            $assigned_user_id = $duty_schedule[$day_number] ?? null;
        ?>
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd;"><strong><?php echo $day_name; ?></strong></td>
            <td style="padding: 8px; border: 1px solid #ddd;">
                <select name="duty[<?php echo $day_number; ?>]" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
                    <option value="">-- ไม่กำหนด --</option>
                    <?php foreach ($teachers as $teacher): ?>
                        <option value="<?php echo $teacher['id']; ?>" <?php if ($teacher['id'] == $assigned_user_id) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <button type="submit" name="save_duty" style="background-color: #27ae60; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; margin-top: 20px;">บันทึกการเปลี่ยนแปลง</button>
</form>