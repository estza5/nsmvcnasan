 <?php
session_start();

// ตรวจสอบว่าล็อกอินหรือยัง และมี role เป็น director หรือไม่
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'director') {
    header('Location: index.php');
    exit;
}

include 'db_connect.php';
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Director Dashboard</title>
    <link rel="stylesheet" href="admin_style.css"> <!-- ใช้ CSS ร่วมกับแอดมินเพื่อให้หน้าตาคล้ายกัน -->
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <h3>Director Panel</h3>
            <p>สวัสดี, คุณ <?php 
                $display_name = isset($_SESSION['first_name']) && !empty($_SESSION['first_name']) 
                                ? $_SESSION['first_name'] . ' ' . $_SESSION['last_name'] 
                                : $_SESSION['username'];
                echo htmlspecialchars($display_name); 
            ?></p>
            <ul>
                <!-- ในอนาคตสามารถเพิ่มเมนูสำหรับผู้บริหารได้ที่นี่ -->
                <li><a href="director_dashboard.php">ภาพรวม</a></li>
            </ul>
            <a href="logout.php" class="logout-btn">ออกจากระบบ</a>
        </div>
        <div class="main-content">
            <div class="content-body">
                <?php
                    $base_url = 'director_dashboard.php'; // กำหนด URL พื้นฐานสำหรับลิงก์ในหน้ารายงาน
                    // เรียกใช้หน้ารายงานภาพรวมเดียวกับที่แอดมินใช้
                    // เพื่อให้ง่ายต่อการบำรุงรักษาในอนาคต
                    include 'admin_overview.php';
                ?>
            </div>
        </div>
    </div>
</body>
</html>