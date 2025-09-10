<?php
session_start();
include 'db_connect.php'; // เรียกไฟล์เชื่อมต่อฐานข้อมูล

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // รับค่าจากฟอร์ม
    $_SESSION['form_data'] = $_POST; // เก็บข้อมูลฟอร์มไว้ใน session เพื่อกรอกกลับ

    $username = $_POST['username'];
    $email = $_POST['email'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    $department_id = $_POST['department_id'] ?? null;

    // --- การตรวจสอบข้อมูลเบื้องต้น ---
    // ตรวจสอบว่ามีค่าว่างหรือไม่
    if (empty($username) || empty($email) || empty($first_name) || empty($last_name) || empty($password) || empty($role)) {
        $_SESSION['register_error'] = "กรุณากรอกข้อมูลให้ครบทุกช่อง";
        header('Location: register.php');
        exit;
    }

    // ตรวจสอบว่า role ที่ส่งมาถูกต้องหรือไม่ (ป้องกันการแก้ไขค่าจากหน้าเว็บ)
    if (!in_array($role, ['teacher', 'director', 'admin'])) {
        $_SESSION['register_error'] = "บทบาทที่เลือกไม่ถูกต้อง";
        header('Location: register.php');
        exit;
    }

    // --- ตรวจสอบรหัสยืนยันสำหรับแอดมิน ---
    if ($role === 'admin') {
        $admin_code = $_POST['admin_code'] ?? '';
        // คุณสามารถเปลี่ยนรหัสนี้ได้ตามต้องการ หรือเก็บไว้ในไฟล์ config
        if ($admin_code !== 'SUPER_ADMIN_2024') { 
            $_SESSION['register_error'] = "รหัสยืนยันแอดมินไม่ถูกต้อง";
            header('Location: register.php');
            exit;
        }
    }

    // --- ตรวจสอบข้อมูลแผนกสำหรับครู ---
    if ($role === 'teacher' && empty($department_id)) {
        $_SESSION['register_error'] = "กรุณาเลือกแผนกสำหรับครู";
        header('Location: register.php');
        exit;
    }

    // --- ตรวจสอบ Username และ Email ซ้ำ ---
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $_SESSION['register_error'] = "ขออภัย, ชื่อผู้ใช้หรืออีเมลนี้มีอยู่ในระบบแล้ว";
        header('Location: register.php');
        exit;
    }
    $stmt->close();

    // กำหนดสถานะตามบทบาท
    $status = 'pending'; // ค่าเริ่มต้นสำหรับครู
    if ($role === 'admin' || $role === 'director') {
        $status = 'approved'; // อนุมัติทันทีสำหรับแอดมินและผู้บริหาร
    }

    // --- การเข้ารหัสรหัสผ่าน (สำคัญมาก!) ---
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // --- การบันทึกข้อมูลลงฐานข้อมูลด้วย Prepared Statement (ปลอดภัยจาก SQL Injection) ---
    $stmt = $conn->prepare("INSERT INTO users (username, email, first_name, last_name, password, role, status, department_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    // ตรวจสอบว่า prepare สำเร็จหรือไม่
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }

    // ถ้าไม่ใช่ครู ให้ department_id เป็น NULL
    $final_department_id = ($role === 'teacher') ? $department_id : null;

    $stmt->bind_param("sssssssi", $username, $email, $first_name, $last_name, $hashed_password, $role, $status, $final_department_id);

    if ($stmt->execute()) {
        unset($_SESSION['form_data']); // ล้างข้อมูลฟอร์มเมื่อสำเร็จ
        unset($_SESSION['register_error']); // ล้าง error message เมื่อสำเร็จ
        // สมัครสมาชิกสำเร็จ
        echo "สมัครสมาชิกสำเร็จ! คุณสามารถเข้าสู่ระบบได้แล้ว <br>";
        echo "<a href='index.php'>กลับไปหน้าเข้าสู่ระบบ</a>";
    } else {
        // มีข้อผิดพลาดเกิดขึ้น
        $_SESSION['register_error'] = "เกิดข้อผิดพลาดในการสมัครสมาชิก: " . $stmt->error;
        header('Location: register.php');
        exit;
    }

    $stmt->close();
    $conn->close();
}
?>