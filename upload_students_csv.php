<?php
session_start();
include 'db_connect.php';

// 1. ตรวจสอบสิทธิ์: ต้องเป็นแอดมินที่ล็อกอินอยู่เท่านั้น
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// 2. ตรวจสอบว่ามีการส่งฟอร์มมาหรือไม่
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload'])) {

    // 3. ตรวจสอบข้อมูลที่ส่งมา
    if (empty($_POST['department_id'])) {
        $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'กรุณาเลือกแผนกก่อนอัปโหลดไฟล์'];
        header('Location: admin_dashboard.php?page=students');
        exit;
    }

    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'เกิดข้อผิดพลาดในการอัปโหลดไฟล์'];
        header('Location: admin_dashboard.php?page=students');
        exit;
    }

    $department_id = (int)$_POST['department_id'];
    $file_tmp_path = $_FILES['csv_file']['tmp_name'];
    $file_name = $_FILES['csv_file']['name'];
    $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    if ($file_extension !== 'csv') {
        $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'กรุณาอัปโหลดไฟล์นามสกุล .csv เท่านั้น'];
        header('Location: admin_dashboard.php?page=students');
        exit;
    }

    // 4. ประมวลผลไฟล์ CSV
    $file = fopen($file_tmp_path, 'r');
    if ($file === false) {
        $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'ไม่สามารถเปิดไฟล์ CSV ได้'];
        header('Location: admin_dashboard.php?page=students');
        exit;
    }

    // เริ่ม Transaction เพื่อความปลอดภัยของข้อมูล
    $conn->begin_transaction();

    try {
        // เตรียมคำสั่ง SQL สำหรับเพิ่มข้อมูล
        $stmt = $conn->prepare("INSERT INTO students (student_code, first_name, last_name, gender, department_id) VALUES (?, ?, ?, ?, ?)");
        if ($stmt === false) {
            throw new Exception("Prepare statement failed: " . $conn->error);
        }

        $row_count = 0;
        $inserted_count = 0;
        
        // ตรวจสอบ UTF-8 BOM และข้ามแถวหัวข้อ (header)
        $bom = "\xef\xbb\xbf";
        if (fgets($file, 4) !== $bom) {
            rewind($file); // ถ้าไม่มี BOM ให้ย้อนกลับไปที่จุดเริ่มต้นของไฟล์
        }

        // วนลูปอ่านข้อมูลทีละแถว
        while (($data = fgetcsv($file)) !== FALSE) {
            $row_count++;
            // ตรวจสอบว่ามีข้อมูลครบ 4 คอลัมน์หรือไม่
            if (count($data) >= 4) {
                $student_code = trim($data[0]);
                $first_name_raw = trim($data[1]);
                $last_name_raw = trim($data[2]);
                $gender_raw = strtoupper(trim($data[3])); // อ่านข้อมูลเพศและแปลงเป็นตัวพิมพ์ใหญ่

                // ตรวจสอบและแปลง Encoding ของข้อมูลให้เป็น UTF-8
                $first_name = mb_convert_encoding($first_name_raw, 'UTF-8', 'auto');
                $last_name = mb_convert_encoding($last_name_raw, 'UTF-8', 'auto');

                // ตรวจสอบค่า gender ให้ถูกต้อง (ต้องเป็น 'M' หรือ 'F' เท่านั้น)
                $gender = ($gender_raw === 'M' || $gender_raw === 'F') ? $gender_raw : '';
                
                if (!empty($student_code) && !empty($first_name) && !empty($last_name) && !empty($gender)) {
                    $stmt->bind_param("ssssi", $student_code, $first_name, $last_name, $gender, $department_id);
                    if (!$stmt->execute()) {
                        // หากมีข้อผิดพลาดแม้แต่แถวเดียว ให้ยกเลิกทั้งหมด
                        throw new Exception("ไม่สามารถเพิ่มข้อมูลนักเรียนรหัส '{$student_code}' ได้: " . $stmt->error);
                    }
                    $inserted_count++;
                }
            }
        }
        
        $conn->commit(); // ยืนยันการบันทึกข้อมูลทั้งหมด
        $_SESSION['admin_message'] = ['type' => 'success', 'text' => "อัปโหลดสำเร็จ! เพิ่มข้อมูลนักเรียน {$inserted_count} จาก {$row_count} รายการ"];

    } catch (Exception $e) {
        $conn->rollback(); // ยกเลิกการบันทึกทั้งหมดหากเกิดข้อผิดพลาด
        $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
    } finally {
        if (isset($stmt)) $stmt->close();
        fclose($file);
    }

    // 5. ส่งกลับไปหน้าเดิมพร้อมข้อความแจ้งเตือน
    header('Location: admin_dashboard.php?page=students');
    exit;
}
?>