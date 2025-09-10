<?php session_start(); ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>หน้าล็อกอิน</title>
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
        <form action="login_process.php" method="post">
            <center><h2>ระบบเช็คชื่อนักเรียน</h2></center>
            <center><h3>วิทยาลัยอาชีวศึกษาเทศบาลเมืองนาสาร</h3></center>
            <?php 
                if (isset($_SESSION['login_error'])) {
                    echo '<div class="error-message">' . $_SESSION['login_error'] . '</div>';
                    unset($_SESSION['login_error']); // ลบข้อความหลังจากแสดงผลแล้ว
                }
            ?>
            <div class="input-group">
                <label for="username">ชื่อผู้ใช้:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="input-group">
                <label for="password">รหัสผ่าน:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">เข้าสู่ระบบ</button>
        </form>
        <div class="register-link">
            <p>ยังไม่มีบัญชี? <a href="register.php">สมัครสมาชิกที่นี่</a></p>
        </div>
    </div>
</body>
</html>