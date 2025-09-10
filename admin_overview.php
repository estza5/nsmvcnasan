<?php
date_default_timezone_set('Asia/Bangkok');

// รับค่าวันที่จาก URL parameter, ถ้าไม่มีให้ใช้วันที่ปัจจุบัน
$selected_date = $_GET['date'] ?? date('Y-m-d');
// ตรวจสอบรูปแบบวันที่เบื้องต้น
if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $selected_date)) {
    $selected_date = date('Y-m-d'); // หากรูปแบบผิดพลาด ให้กลับไปเป็นวันที่ปัจจุบัน
}

// รับค่ามุมมอง (view) จาก URL, ถ้าไม่มีให้เป็น 'summary'
$view = $_GET['view'] ?? 'summary';

// --- ส่วนประมวลผลฟอร์ม (เพิ่มใหม่) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_late_record'])) {
    $attendance_id = (int)($_POST['attendance_id'] ?? 0);

    if ($attendance_id > 0) {
        $stmt = $conn->prepare("DELETE FROM attendance WHERE id = ? AND status = 'late'");
        $stmt->bind_param("i", $attendance_id);
        if ($stmt->execute()) {
            $_SESSION['admin_message'] = ['type' => 'success', 'text' => 'ลบรายการมาสายเรียบร้อยแล้ว'];
        } else {
            $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'เกิดข้อผิดพลาดในการลบ: ' . htmlspecialchars($stmt->error)];
        }
        $stmt->close();
    } else {
        $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'ID รายการไม่ถูกต้อง'];
    }
    // ส่งกลับไปที่หน้ารายงานการเข้าสายของวันเดิม
    header("Location: " . $base_url . "?page=overview&view=late_report&date=" . $selected_date);
    exit;
}

// --- ส่วนดึงข้อมูลสรุปการมาเรียน ---
$sql = "
SELECT 
    d.level,
    d.name AS department_name,
    
    -- นับจำนวนนักเรียนทั้งหมดในกลุ่มนี้
    COUNT(s.id) AS total_students,
    
    -- นับจำนวนนักเรียนชาย-หญิงทั้งหมด
    SUM(CASE WHEN s.gender = 'M' THEN 1 ELSE 0 END) AS total_male,
    SUM(CASE WHEN s.gender = 'F' THEN 1 ELSE 0 END) AS total_female,

    -- นับจำนวนการมาเรียน (present)
    SUM(CASE WHEN a.status = 'present' AND s.gender = 'M' THEN 1 ELSE 0 END) AS present_male,
    SUM(CASE WHEN a.status = 'present' AND s.gender = 'F' THEN 1 ELSE 0 END) AS present_female,

    -- นับจำนวนการมาสาย (late)
    SUM(CASE WHEN a.status = 'late' AND s.gender = 'M' THEN 1 ELSE 0 END) AS late_male,
    SUM(CASE WHEN a.status = 'late' AND s.gender = 'F' THEN 1 ELSE 0 END) AS late_female,

    -- นับจำนวนการลา (on_leave)
    SUM(CASE WHEN a.status = 'on_leave' AND s.gender = 'M' THEN 1 ELSE 0 END) AS on_leave_male,
    SUM(CASE WHEN a.status = 'on_leave' AND s.gender = 'F' THEN 1 ELSE 0 END) AS on_leave_female,

    -- นับจำนวนการขาด (absent หรือยังไม่เช็คชื่อ)
    SUM(CASE WHEN (a.status = 'absent' OR a.id IS NULL) AND s.gender = 'M' THEN 1 ELSE 0 END) AS absent_male,
    SUM(CASE WHEN (a.status = 'absent' OR a.id IS NULL) AND s.gender = 'F' THEN 1 ELSE 0 END) AS absent_female

FROM departments d
JOIN students s ON d.id = s.department_id
LEFT JOIN attendance a ON s.id = a.student_id AND a.check_in_date = ?
GROUP BY d.level, d.id
ORDER BY d.level, d.name;
";

$stmt = $conn->prepare($sql);

// เพิ่มการตรวจสอบว่า prepare สำเร็จหรือไม่
if ($stmt === false) {
    die("SQL Prepare Error: " . htmlspecialchars($conn->error));
}

$stmt->bind_param("s", $selected_date);
$stmt->execute();
$overview_result = $stmt->get_result();

?>

<?php if ($view === 'late_report'): ?>
    <?php
    // --- ส่วนดึงข้อมูลรายงานคนมาสาย ---
    $late_sql = "
        SELECT a.id, s.student_code, s.first_name, s.last_name, d.name as department_name, d.level as department_level, a.late_reason, a.check_in_time,
               u.first_name as checker_fname, u.last_name as checker_lname
        FROM attendance a
        JOIN students s ON a.student_id = s.id
        JOIN departments d ON s.department_id = d.id
        LEFT JOIN users u ON a.checked_by_user_id = u.id
        WHERE a.status = 'late' AND a.check_in_date = ?
        ORDER BY a.check_in_time
    ";
    $late_stmt = $conn->prepare($late_sql);
    $late_stmt->bind_param("s", $selected_date);
    $late_stmt->execute();
    $late_result = $late_stmt->get_result();
    ?>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h4>รายงานการเข้าสาย ประจำวันที่ <?php echo $selected_date; ?></h4>
        <div style="display: flex; gap: 10px; align-items: center;">
            <button type="button" onclick="printLateReport()" style="background-color: #34495e; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer;">พิมพ์เอกสาร</button>
            <a href="<?php echo $base_url; ?>?page=overview&view=summary&date=<?php echo $selected_date; ?>" style="text-decoration: none; background-color: #3498db; color: white; padding: 6px 12px; border-radius: 4px;">ภาพรวมการเข้าเรียน</a>
        </div>
    </div>
    <div id="late-report-table">
    <table style="width: 100%; border-collapse: collapse; font-size: 14px;" >
        <thead style="background-color: #f2f2f2;">
            <tr>
                <th style="padding: 8px; border: 1px solid #ddd;">เวลา</th>
                <th style="padding: 8px; border: 1px solid #ddd;">รหัสนักเรียน</th>
                <th style="padding: 8px; border: 1px solid #ddd;">ชื่อ - นามสกุล</th>
                <th style="padding: 8px; border: 1px solid #ddd;">แผนก</th>
                <th style="padding: 8px; border: 1px solid #ddd;">เหตุผล</th>
                <th style="padding: 8px; border: 1px solid #ddd;">ผู้บันทึก</th>
                <th style="padding: 8px; border: 1px solid #ddd;">จัดการ</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($late_result && $late_result->num_rows > 0): ?>
                <?php while($row = $late_result->fetch_assoc()): ?>
                <tr>
                    <td style="padding: 8px; border: 1px solid #ddd; text-align: center;"><?php echo htmlspecialchars(date('H:i', strtotime($row['check_in_time']))); ?> น.</td>
                    <td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars($row['student_code']); ?></td>
                    <td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                    <td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars($row['department_name'] . ' - ' . $row['department_level']); ?></td>
                    <td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars($row['late_reason']); ?></td>
                    <td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars($row['checker_fname'] . ' ' . $row['checker_lname']); ?></td>
                    <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">
                        <form action="<?php echo $base_url; ?>?page=overview&view=late_report&date=<?php echo $selected_date; ?>" method="POST" onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบรายการนี้?');">
                            <input type="hidden" name="attendance_id" value="<?php echo $row['id']; ?>">
                            <button type="submit" name="delete_late_record" style="background: none; border: none; color: red; cursor: pointer; text-decoration: underline;">ลบ</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7" style="padding: 15px; text-align: center;">ไม่พบข้อมูลนักเรียนมาสายในวันที่เลือก</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
    <?php if(isset($late_stmt)) $late_stmt->close(); ?>

<?php else: // Default view is 'summary' ?>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h4>ภาพรวมการเข้าเรียนประจำวันที่ <?php echo $selected_date; ?></h4>
        <div style="display: flex; gap: 10px; align-items: center;">
            <a href="<?php echo $base_url; ?>?page=overview&view=late_report&date=<?php echo $selected_date; ?>" style="text-decoration: none; background-color: #e67e22; color: white; padding: 6px 12px; border-radius: 4px;">รายงานการเข้าสาย</a>
            <form action="<?php echo $base_url; ?>" method="get" style="display: flex; gap: 10px; align-items: center;">
                <!-- Action Buttons -->
                <button type="button" onclick="printReport()" style="background-color: #34495e; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer;">พิมพ์เอกสาร</button>
                
                <!-- Date Selector -->
                <input type="hidden" name="page" value="overview"> <!-- ใช้สำหรับ admin_dashboard -->
                <input type="hidden" name="view" value="summary">
                <label for="date_selector">เลือกวันที่:</label>
                <input type="date" id="date_selector" name="date" value="<?php echo htmlspecialchars($selected_date); ?>" style="padding: 5px; border-radius: 4px; border: 1px solid #ccc;">
                <button type="submit" style="background-color: #3498db; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer;">ดูข้อมูล</button>
            </form>
        </div>
    </div>

    <div id="report-table">
    <table style="width: 100%; border-collapse: collapse; font-size: 14px;" >
    <thead style="background-color: #f2f2f2;">
        <tr>
            <th rowspan="2" style="padding: 8px; border: 1px solid #ddd;">ชั้น</th>
            <th rowspan="2" style="padding: 8px; border: 1px solid #ddd;">แผนก</th>
            <th colspan="2" style="padding: 8px; border: 1px solid #ddd;">นักเรียนทั้งหมด</th>
            <th colspan="2" style="padding: 8px; border: 1px solid #ddd;">มาเรียน</th>
            <th colspan="2" style="padding: 8px; border: 1px solid #ddd;">มาสาย</th>
            <th colspan="2" style="padding: 8px; border: 1px solid #ddd;">ลา</th>
            <th colspan="2" style="padding: 8px; border: 1px solid #ddd;">ขาดเรียน</th>
        </tr>
        <tr>
            <th style="padding: 4px; border: 1px solid #ddd; font-weight: normal;">ช</th>
            <th style="padding: 4px; border: 1px solid #ddd; font-weight: normal;">ญ</th>
            <th style="padding: 4px; border: 1px solid #ddd; font-weight: normal;">ช</th>
            <th style="padding: 4px; border: 1px solid #ddd; font-weight: normal;">ญ</th>
            <th style="padding: 4px; border: 1px solid #ddd; font-weight: normal;">ช</th>
            <th style="padding: 4px; border: 1px solid #ddd; font-weight: normal;">ญ</th>
            <th style="padding: 4px; border: 1px solid #ddd; font-weight: normal;">ช</th>
            <th style="padding: 4px; border: 1px solid #ddd; font-weight: normal;">ญ</th>
            <th style="padding: 4px; border: 1px solid #ddd; font-weight: normal;">ช</th>
            <th style="padding: 4px; border: 1px solid #ddd; font-weight: normal;">ญ</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        if ($overview_result && $overview_result->num_rows > 0):
            // เตรียมตัวแปรสำหรับคำนวณผลรวมทั้งหมด
            $grand_total_students = 0;
            $grand_total_male = 0;
            $grand_total_female = 0;
            $grand_present_male = 0;
            $grand_present_female = 0;
            $grand_late_male = 0;
            $grand_late_female = 0;
            $grand_absent_male = 0;
            $grand_absent_female = 0;
            $grand_on_leave_male = 0;
            $grand_on_leave_female = 0;

            while($row = $overview_result->fetch_assoc()): 
                // คำนวณผลรวมสำหรับแต่ละแถว
                $total_present = $row['present_male'] + $row['present_female'] + $row['late_male'] + $row['late_female'];
                $attendance_percentage = ($row['total_students'] > 0) ? ($total_present / $row['total_students']) * 100 : 0;

                // สะสมค่าสำหรับผลรวมทั้งหมด
                $grand_total_students += $row['total_students'];
                $grand_total_male += $row['total_male'];
                $grand_total_female += $row['total_female'];
                $grand_present_male += $row['present_male'];
                $grand_present_female += $row['present_female'];
                $grand_late_male += $row['late_male'];
                $grand_late_female += $row['late_female'];
                $grand_absent_male += $row['absent_male'];
                $grand_absent_female += $row['absent_female'];
                $grand_on_leave_male += $row['on_leave_male'];
                $grand_on_leave_female += $row['on_leave_female'];
            ?>
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars($row['level']); ?></td>
                <td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars($row['department_name']); ?></td>
                <td style="padding: 8px; border: 1px solid #ddd; text-align: center;"><?php echo $row['total_male']; ?></td>
                <td style="padding: 8px; border: 1px solid #ddd; text-align: center;"><?php echo $row['total_female']; ?></td>
                <td style="padding: 8px; border: 1px solid #ddd; text-align: center;"><?php echo $row['present_male']; ?></td>
                <td style="padding: 8px; border: 1px solid #ddd; text-align: center;"><?php echo $row['present_female']; ?></td>
                <td style="padding: 8px; border: 1px solid #ddd; text-align: center;"><?php echo $row['late_male']; ?></td>
                <td style="padding: 8px; border: 1px solid #ddd; text-align: center;"><?php echo $row['late_female']; ?></td>
                <td style="padding: 8px; border: 1px solid #ddd; text-align: center;"><?php echo $row['on_leave_male']; ?></td>
                <td style="padding: 8px; border: 1px solid #ddd; text-align: center;"><?php echo $row['on_leave_female']; ?></td>
                <td style="padding: 8px; border: 1px solid #ddd; text-align: center;"><?php echo $row['absent_male']; ?></td>
                <td style="padding: 8px; border: 1px solid #ddd; text-align: center;"><?php echo $row['absent_female']; ?></td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="12" style="padding: 15px; text-align: center;">ยังไม่มีข้อมูลนักเรียนในระบบ</td></tr>
        <?php endif; ?>
    </tbody>
    <?php if ($overview_result && $overview_result->num_rows > 0): ?>
    <tfoot style="background-color: #e9ecef; font-weight: bold;">
        <tr>
            <td colspan="2" style="padding: 8px; border: 1px solid #ddd; text-align: right;">รวมทั้งหมด</td>
            <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">
                <?php echo $grand_total_male; ?>
                <br><small>(<?php echo ($grand_total_students > 0) ? number_format(($grand_total_male / $grand_total_students) * 100, 2) : '0.00'; ?>%)</small>
            </td> <!-- รวม ช -->
            <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">
                <?php echo $grand_total_female; ?>
                <br><small>(<?php echo ($grand_total_students > 0) ? number_format((($grand_total_male + $grand_total_female) / $grand_total_students) * 100, 2) : '0.00'; ?>%)</small>
            </td> <!-- รวม ญ -->
            <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">
                <?php echo $grand_present_male; ?>
                <br><small>(<?php echo ($grand_total_students > 0) ? number_format(($grand_present_male / $grand_total_students) * 100, 2) : '0.00'; ?>%)</small>
            </td>
            <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">
                <?php echo $grand_present_female; ?>
                <br><small>(<?php echo ($grand_total_students > 0) ? number_format(($grand_present_female / $grand_total_students) * 100, 2) : '0.00'; ?>%)</small>
            </td>
            <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">
                <?php echo $grand_late_male; ?>
                <br><small>(<?php echo ($grand_total_students > 0) ? number_format(($grand_late_male / $grand_total_students) * 100, 2) : '0.00'; ?>%)</small>
            </td>
            <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">
                <?php echo $grand_late_female; ?>
                <br><small>(<?php echo ($grand_total_students > 0) ? number_format(($grand_late_female / $grand_total_students) * 100, 2) : '0.00'; ?>%)</small>
            </td>
            <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">
                <?php echo $grand_on_leave_male; ?>
                <br><small>(<?php echo ($grand_total_students > 0) ? number_format(($grand_on_leave_male / $grand_total_students) * 100, 2) : '0.00'; ?>%)</small>
            </td>
            <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">
                <?php echo $grand_on_leave_female; ?>
                <br><small>(<?php echo ($grand_total_students > 0) ? number_format(($grand_on_leave_female / $grand_total_students) * 100, 2) : '0.00'; ?>%)</small>
            </td>
            <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">
                <?php echo $grand_absent_male; ?>
                <br><small>(<?php echo ($grand_total_students > 0) ? number_format(($grand_absent_male / $grand_total_students) * 100, 2) : '0.00'; ?>%)</small>
            </td>
            <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">
                <?php echo $grand_absent_female; ?>
                <br><small>(<?php echo ($grand_total_students > 0) ? number_format(($grand_absent_female / $grand_total_students) * 100, 2) : '0.00'; ?>%)</small>
            </td>
        </tr>
    </tfoot>
    <?php endif; ?>
</table>
</div>

<?php
if (isset($stmt)) $stmt->close();
?>

<?php endif; ?>

<script>
function printReport() {
    const tableContent = document.getElementById('report-table').innerHTML;
    const selectedDate = '<?php echo $selected_date; ?>';

    const header = `
        <div style="text-align: center; margin-bottom: 20px;">
            <h2>รายงานการมาเรียน</h2>
            <h3>วิทยาลัยอาชีวศึกษาเทศบาลเมืองนาสาร</h3>
            <p>ประจำวันที่ ${selectedDate}</p>
        </div>
    `;

    const eventLogSection = `
        <div style="margin-top: 30px; page-break-inside: avoid;">
            <h4 style="margin-bottom: 5px; text-align: left; font-weight: bold;">บันทึกเหตุการณ์ประจำวัน</h4>
            <p style="border-bottom: 1px dotted #333; margin: 30px 0;"></p>
            <p style="border-bottom: 1px dotted #333; margin: 30px 0;"></p>
            <p style="border-bottom: 1px dotted #333; margin: 30px 0;"></p>
            <p style="border-bottom: 1px dotted #333; margin: 30px 0;"></p>
        </div>
    `;

    const footer = `
        <div style="margin-top: 50px; display: flex; justify-content: space-between; font-size: 14px; page-break-inside: avoid;">
            <div style="text-align: center; width: 45%;">
                <p style="margin-bottom: 5px;">....................................................</p>
                <p style="margin-bottom: 5px;">(...................................................)</p>
                <p>ครูเวรประจำวัน</p>
            </div>
            <div style="text-align: center; width: 45%;">
                <p style="margin-bottom: 5px;">....................................................</p>
                <p style="margin-bottom: 5px;">(...................................................)</p>
                <p>ผู้บริหารสถานศึกษา</p>
                <p>วันที่................................</p>
            </div>
        </div>
    `;

    const printStyles = `
        <style>
            body { font-family: 'Sarabun', sans-serif; margin: 25px; }
            table { width: 100%; border-collapse: collapse; font-size: 12px; }
            th, td { border: 1px solid #333; padding: 4px; text-align: center; }
            th, tfoot { background-color: #f2f2f2; }
        </style>
    `;

    const printWindow = window.open('', '', 'height=800,width=1000');
    printWindow.document.write(`<html><head><title>รายงานการมาเรียน</title>${printStyles}</head><body>`);
    printWindow.document.write(header);
    printWindow.document.write(tableContent);
    printWindow.document.write(eventLogSection);
    printWindow.document.write(footer);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.print();
}

function printLateReport() {
    // Clone the table to modify it for printing without affecting the screen
    const originalTable = document.querySelector('#late-report-table table');
    if (!originalTable) return;

    const tableToPrint = originalTable.cloneNode(true);

    // Remove the last two columns ("ผู้บันทึก", "จัดการ") from header
    const headerRow = tableToPrint.querySelector('thead tr');
    headerRow.deleteCell(-1); // Remove "จัดการ"
    headerRow.deleteCell(-1); // Remove "ผู้บันทึก"

    // Remove the last two columns from each body row
    const bodyRows = tableToPrint.querySelectorAll('tbody tr');
    bodyRows.forEach(row => {
        // Check if it's a data row or the "no data" row
        if (row.cells.length > 1) {
            row.deleteCell(-1); // Remove "จัดการ"
            row.deleteCell(-1); // Remove "ผู้บันทึก"
        } else {
            // Adjust colspan for the "no data" row
            row.cells[0].colSpan = 5;
        }
    });

    const tableContent = tableToPrint.outerHTML;
    const selectedDate = '<?php echo $selected_date; ?>';

    const header = `
        <div style="text-align: center; margin-bottom: 20px;">
            <h2>รายงานการมาสาย</h2>
            <h3>วิทยาลัยอาชีวศึกษาเทศบาลเมืองนาสาร</h3>
            <p>ประจำวันที่ ${selectedDate}</p>
        </div>
    `;

    const footer = `
        <div style="margin-top: 50px; display: flex; justify-content: space-between; font-size: 14px; page-break-inside: avoid;">
            <div style="text-align: center; width: 45%;">
                <p style="margin-bottom: 5px;">.......................................</p>
                <p style="margin-bottom: 5px;">(....................................)</p>
                <p>ครูเวรประจำวัน</p>
            </div>
            <div style="text-align: center; width: 45%;">
                <p style="margin-bottom: 5px;">..........................................</p>
                <p style="margin-bottom: 5px;">(............................................)</p>
                <p>ผู้บริหารสถานศึกษา</p>
                <p>วันที่........................................</p>
            </div>
        </div>
    `;

    const printWindow = window.open('', '', 'height=800,width=1000');
    printWindow.document.write(`<html><head><title>รายงานการมาสาย</title><style>body { font-family: 'Sarabun', sans-serif; margin: 25px; } table { width: 100%; border-collapse: collapse; font-size: 12px; } th, td { border: 1px solid #333; padding: 4px; } th { background-color: #f2f2f2; }</style></head><body>`);
    printWindow.document.write(header);
    printWindow.document.write(tableContent);
    printWindow.document.write(footer);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.print();
}
</script>