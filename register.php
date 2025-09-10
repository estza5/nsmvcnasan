<?php 
session_start();
include 'db_connect.php'; // เรียกใช้เพื่อดึงข้อมูลแผนก

// ดึงข้อมูลฟอร์มเก่ามาใช้ (ถ้ามี) เพื่อให้ผู้ใช้ไม่ต้องกรอกใหม่ทั้งหมด
$old_data = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']); // ล้างข้อมูลหลังจากดึงมาใช้แล้ว
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>สมัครสมาชิก</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <form action="register_process.php" method="post">
            <h2>สมัครสมาชิก</h2>
            <?php 
                if (isset($_SESSION['register_error'])) {
                    echo '<div class="error-message">' . htmlspecialchars($_SESSION['register_error']) . '</div>';
                    unset($_SESSION['register_error']); // ลบข้อความหลังจากแสดงผลแล้ว
                }
            ?>
            <div class="input-group">
                <label for="username">ชื่อผู้ใช้ (Username):</label>
                <input type="text" id="username" name="username" required value="<?php echo htmlspecialchars($old_data['username'] ?? ''); ?>">
            </div>
            <div class="input-group">
                <label for="email">อีเมล:</label>
                <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($old_data['email'] ?? ''); ?>">
            </div>
            <div class="input-group">
                <label for="first_name">ชื่อจริง:</label>
                <input type="text" id="first_name" name="first_name" required value="<?php echo htmlspecialchars($old_data['first_name'] ?? ''); ?>">
            </div>
            <div class="input-group">
                <label for="last_name">นามสกุล:</label>
                <input type="text" id="last_name" name="last_name" required value="<?php echo htmlspecialchars($old_data['last_name'] ?? ''); ?>">
            </div>
            <div class="input-group">
                <label for="password">รหัสผ่าน:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="input-group">
                <label for="role">บทบาท:</label>
                <select name="role" id="role" required onchange="handleRoleChange(this.value)" style="width: 100%; padding: 12px 15px; border: 1px solid #dddfe2; border-radius: 6px; font-size: 16px; font-family: 'Sarabun', sans-serif;">
                    <option value="teacher" <?php if (($old_data['role'] ?? 'teacher') === 'teacher') echo 'selected'; ?>>ครู</option>
                    <option value="director" <?php if (($old_data['role'] ?? '') === 'director') echo 'selected'; ?>>ผู้บริหาร</option>
                    <option value="admin" <?php if (($old_data['role'] ?? '') === 'admin') echo 'selected'; ?>>แอดมิน</option>
                </select>
            </div>
            <div class="input-group" id="department-group" style="display: block;"> <!-- แสดงเป็นค่าเริ่มต้น -->
                <label for="department_id">แผนก:</label>
                <select name="department_id" id="department_id" required style="width: 100%; padding: 12px 15px; border: 1px solid #dddfe2; border-radius: 6px; font-size: 16px; font-family: 'Sarabun', sans-serif;">
                    <option value="">-- กรุณาเลือกแผนก --</option>
                    <?php
                        $result = $conn->query("SELECT id, name, level FROM departments ORDER BY name, level");
                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $selected = (isset($old_data['department_id']) && $old_data['department_id'] == $row['id']) ? 'selected' : '';
                                echo "<option value='{$row['id']}' {$selected}>".htmlspecialchars($row['name'] . ' - ' . $row['level'])."</option>";
                            }
                        }
                    ?>
                </select>
            </div>
            <div class="input-group" id="admin-code-group" style="display: none;">
                <label for="admin_code">รหัสยืนยันแอดมิน:</label>
                <input type="password" id="admin_code" name="admin_code">
            </div>
            <button type="submit">สมัครสมาชิก</button>
        </form>
        <div class="register-link">
            <p>มีบัญชีอยู่แล้ว? <a href="index.php">เข้าสู่ระบบที่นี่</a></p>
        </div>
    </div>
    <script>
        // เรียกใช้ฟังก์ชันเมื่อหน้าเว็บโหลดเสร็จ
        document.addEventListener('DOMContentLoaded', function() {
            handleRoleChange(document.getElementById('role').value);
        });

        function handleRoleChange(role) {
            const adminCodeGroup = document.getElementById('admin-code-group');
            const adminCodeInput = document.getElementById('admin_code');
            const departmentGroup = document.getElementById('department-group');
            const departmentInput = document.getElementById('department_id');

            // ซ่อน/แสดง และ ตั้งค่า required สำหรับช่องแผนก
            departmentGroup.style.display = (role === 'teacher') ? 'block' : 'none';
            departmentInput.required = (role === 'teacher');

            // ซ่อน/แสดง และ ตั้งค่า required สำหรับรหัสแอดมิน
            adminCodeGroup.style.display = (role === 'admin') ? 'block' : 'none';
            adminCodeInput.required = (role === 'admin');
        }
    </script>
</body>
</html>