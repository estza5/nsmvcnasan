<?php
session_start(); // เริ่มต้น session
include 'db_connect.php'; // เรียกไฟล์เชื่อมต่อฐานข้อมูล

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // ใช้ Prepared Statement เพื่อป้องกัน SQL Injection
    $stmt = $conn->prepare("SELECT id, username, password, role, status, first_name, last_name FROM users WHERE username = ?");
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        // พบผู้ใช้งาน
        $row = $result->fetch_assoc();

        // 1. ตรวจสอบรหัสผ่านที่เข้ารหัสด้วย password_verify()
        if (password_verify($password, $row['password'])) {
            // 2. ตรวจสอบสถานะการอนุมัติ
            if ($row['status'] === 'approved') {
                // รหัสผ่านถูกต้องและบัญชีได้รับการอนุมัติ
                $_SESSION['loggedin'] = true;
                $_SESSION['username'] = $row['username'];
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['role'] = $row['role'];
                $_SESSION['first_name'] = $row['first_name'];
                $_SESSION['last_name'] = $row['last_name'];
                
                // ตรวจสอบ role แล้วส่งไปยังหน้าที่ถูกต้อง
                switch ($row['role']) {
                    case 'admin':
                        header("location: admin_dashboard.php");
                        break;
                    case 'director':
                        header("location: director_dashboard.php");
                        break;
                    case 'teacher':
                        header("location: teacher_dashboard.php");
                        break;
                }
            } else {
                // บัญชียังไม่ได้รับการอนุมัติหรือถูกระงับ
                $_SESSION['login_error'] = "บัญชีของคุณยังไม่ได้รับการอนุมัติหรือถูกระงับ!";
                header("location: index.php");
            }
            exit;
        } else {
            // รหัสผ่านไม่ถูกต้อง
            $_SESSION['login_error'] = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
            header("location: index.php");
            exit;
        }
    } else {
        // ไม่พบผู้ใช้งาน
        $_SESSION['login_error'] = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
        header("location: index.php");
        exit;
    }
    
    $stmt->close();
    $conn->close();
}
?>