<?php
session_start();

// ตรวจสอบว่าล็อกอินหรือยัง และมี role เป็น admin หรือไม่
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

include 'db_connect.php';
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <!-- jsPDF and html2canvas libraries for PDF export are removed -->
    <link rel="stylesheet" href="admin_style.css"> <!-- ใช้ไฟล์ CSS ใหม่สำหรับแอดมิน -->
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <h3>Admin Panel</h3>
            <p>สวัสดี, <?php 
                // ตรวจสอบว่ามีชื่อ-นามสกุลใน session หรือไม่ ถ้าไม่มีให้ใช้ username แทน
                $display_name = isset($_SESSION['first_name']) && !empty($_SESSION['first_name']) 
                                ? $_SESSION['first_name'] . ' ' . $_SESSION['last_name'] 
                                : $_SESSION['username'];
                echo htmlspecialchars($display_name); 
            ?></p>
            <ul>
                <li><a href="admin_dashboard.php?page=overview">ภาพรวม</a></li>
                <li><a href="admin_dashboard.php?page=students">จัดการรายชื่อนักเรียน</a></li>
                <li><a href="admin_dashboard.php?page=departments">จัดการแผนก/ครูที่ปรึกษา</a></li>
                <li><a href="admin_dashboard.php?page=approvals">อนุมัติผู้ใช้งาน</a></li>
                <li><a href="admin_dashboard.php?page=duty">จัดการเวรประจำวัน</a></li>
            </ul>
            <a href="logout.php" class="logout-btn">ออกจากระบบ</a>
        </div>
        <div class="main-content">
            <div class="content-header">
                <h1>
                    <?php
                        $page = $_GET['page'] ?? 'overview';
                        switch ($page) {
                            case 'students': echo 'จัดการรายชื่อนักเรียน'; break;
                            case 'departments': echo 'จัดการแผนกและครูที่ปรึกษา'; break;
                            case 'approvals': echo 'อนุมัติผู้ใช้งาน'; break;
                            case 'duty': echo 'จัดการเวรประจำวัน'; break;
                            default: echo 'ภาพรวม'; break;
                        }
                    ?>
                </h1>
            </div>
            <div class="content-body">
                <?php
                    // แสดงข้อความแจ้งเตือนจาก Admin actions
                    if (isset($_SESSION['admin_message'])) {
                        $message = $_SESSION['admin_message'];
                        $message_type = $message['type'] === 'success' ? 'green' : 'red';
                        echo '<div style="padding: 10px; margin-bottom: 15px; border-radius: 5px; color: white; background-color:' . $message_type . ';">';
                        echo htmlspecialchars($message['text']);
                        echo '</div>';
                        unset($_SESSION['admin_message']);
                    }
                ?>

                <?php
                    // โหลดเนื้อหาตามหน้า
                    $base_url = 'admin_dashboard.php'; // กำหนด URL พื้นฐานสำหรับลิงก์ในหน้ารายงาน
                    if (file_exists("admin_{$page}.php")) {
                        include "admin_{$page}.php";
                    } else {
                        include "admin_overview.php"; // หน้าเริ่มต้น
                    }
                ?>
            </div>
        </div>
    </div>
</body>
</html>