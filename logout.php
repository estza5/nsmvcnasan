<?php
session_start(); // เริ่ม session
session_unset(); // ลบตัวแปร session ทั้งหมด
session_destroy(); // ทำลาย session

// ส่งกลับไปที่หน้าล็อกอิน
header("location: index.php");
exit;