<?php
$servername = "localhost"; // หรือ IP ของ Server
$username = "root"; // ชื่อผู้ใช้ของฐานข้อมูล
$password = ""; // รหัสผ่านของฐานข้อมูล
$dbname = "my_login_db"; // ชื่อฐานข้อมูล

// สร้างการเชื่อมต่อ
$conn = new mysqli($servername, $username, $password, $dbname);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// ตั้งค่า character set เป็น utf8 เพื่อรองรับภาษาไทย
$conn->set_charset("utf8mb4");
?>