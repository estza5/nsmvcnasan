<?php
// สร้าง CSRF token เพื่อความปลอดภัย
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// ส่วนประมวลผลการอนุมัติ/ปฏิเสธ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['user_id'], $_POST['csrf_token'])) {
    // ตรวจสอบ CSRF token
    if (hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $action = $_POST['action'];
        $user_id = (int)$_POST['user_id'];
        $new_status = '';

        if ($action === 'approve') {
            $new_status = 'approved';
        } 
        elseif ($action === 'reject') {
            $new_status = 'rejected';
        }

        if (!empty($new_status)) {
            $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ? AND role != 'admin'");
            $stmt->bind_param("si", $new_status, $user_id);
        } 
        elseif ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
            $stmt->bind_param("i", $user_id);
        } 
        elseif ($action === 'reset_password' && !empty($_POST['new_password'])) {
            $new_password = $_POST['new_password'];
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ? AND role != 'admin'");
            $stmt->bind_param("si", $hashed_password, $user_id);
        }

        // Execute the prepared statement if it was set
        if (isset($stmt)) {
            $is_successful = $stmt->execute();

            if ($is_successful) {
                if ($stmt->affected_rows > 0) {
                    $_SESSION['admin_message'] = ['type' => 'success', 'text' => 'ดำเนินการสำเร็จ!'];
                } else {
                    $_SESSION['admin_message'] = ['type' => 'success', 'text' => 'ดำเนินการสำเร็จ (ไม่มีข้อมูลที่ต้องเปลี่ยนแปลง)'];
                }
            } else {
                $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'เกิดข้อผิดพลาด: ' . htmlspecialchars($stmt->error)];
            }
            $stmt->close();

            header('Location: admin_dashboard.php?page=approvals');
            exit;
        }
    } else {
        die('CSRF token validation failed.');
    }
}

// ดึงข้อมูลครูที่รอการอนุมัติ
$result = $conn->query("SELECT id, username, first_name, last_name, role, status, created_at FROM users WHERE role != 'admin' ORDER BY created_at DESC");
?>

<h4>จัดการสถานะผู้ใช้งาน</h4>

<?php 
// ตรวจสอบว่า query สำเร็จหรือไม่ ก่อนที่จะใช้งาน $result
if ($result && $result->num_rows > 0): 
?>
    <table style="width: 100%; border-collapse: collapse;">
        <tr style="background-color: #f2f2f2;">
            <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">ชื่อ - นามสกุล</th>
            <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Username</th>
            <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">บทบาท</th>
            <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">สถานะปัจจุบัน</th>
            <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">วันที่สมัคร</th>
            <th style="padding: 8px; border: 1px solid #ddd; text-align: center;">จัดการ</th>
        </tr>
        <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                <td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars($row['username']); ?></td>
                <td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars($row['role']); ?></td>
                <td style="padding: 8px; border: 1px solid #ddd;">
                    <?php 
                        $status_text = ['pending' => 'รออนุมัติ', 'approved' => 'อนุมัติแล้ว', 'rejected' => 'ถูกปฏิเสธ'];
                        $status_color = ['pending' => 'orange', 'approved' => 'green', 'rejected' => 'red'];
                        echo '<span style="color: ' . ($status_color[$row['status']] ?? 'black') . ';">' . ($status_text[$row['status']] ?? 'ไม่ทราบ') . '</span>';
                    ?>
                </td>
                <td style="padding: 8px; border: 1px solid #ddd;"><?php echo $row['created_at']; ?></td>
                <td style="padding: 8px; border: 1px solid #ddd; text-align: center; min-width: 250px;">
                    <!-- ฟอร์มแก้ไขรหัสผ่าน -->
                    <form action="admin_dashboard.php?page=approvals" method="POST" style="display: inline-block; margin-right: 5px;">
                        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                        <input type="hidden" name="action" value="reset_password">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="password" name="new_password" placeholder="รหัสผ่านใหม่" required style="padding: 2px 4px; width: 100px;">
                        <button type="submit" style="background: none; border: none; color: #f39c12; cursor: pointer; padding: 0; font-size: inherit; text-decoration: underline;">แก้ไข</button>
                    </form>
                    |
                    <!-- ฟอร์มจัดการสถานะ -->
                    <?php if ($row['status'] !== 'approved'): ?>
                    <form action="admin_dashboard.php?page=approvals" method="POST" style="display: inline-block;">
                        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <button type="submit" style="background: none; border: none; color: green; cursor: pointer; padding: 0; font-size: inherit; text-decoration: underline;">อนุมัติ</button>
                    </form>
                    <?php endif; ?>
                    <?php if ($row['status'] !== 'rejected'): ?>
                     | 
                    <form action="admin_dashboard.php?page=approvals" method="POST" style="display: inline-block;">
                        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <button type="submit" style="background: none; border: none; color: red; cursor: pointer; padding: 0; font-size: inherit; text-decoration: underline;">ปฏิเสธ</button>
                    </form>
                    <?php endif; ?>
                    |
                    <!-- ฟอร์มลบผู้ใช้ -->
                    <form action="admin_dashboard.php?page=approvals" method="POST" style="display: inline-block;" onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบบัญชีนี้? การกระทำนี้ไม่สามารถย้อนกลับได้');">
                        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <button type="submit" style="background: none; border: none; color: #c0392b; cursor: pointer; padding: 0; font-size: inherit; text-decoration: underline;">ลบ</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
<?php else: ?>
    <p>ไม่มีผู้ใช้งานในระบบ (นอกเหนือจากแอดมิน)</p>
    <?php 
        // หาก query ล้มเหลว ให้แสดงข้อความผิดพลาดเพื่อช่วยในการดีบัก
        if ($result === false) {
            echo '<p style="color: red;">เกิดข้อผิดพลาดในการดึงข้อมูล: ' . htmlspecialchars($conn->error) . '</p>';
        }
    ?>
<?php endif; ?>