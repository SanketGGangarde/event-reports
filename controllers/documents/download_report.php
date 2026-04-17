<?php
/**
 * Download Event Report as PDF using TCPDF
 * Generates a real PDF file and sends it as a download
 */
// -------------------- SESSION --------------------
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// -------------------- DB --------------------
$conn = new mysqli(
    getenv('DB_HOST'),
    getenv('DB_USER'),
    getenv('DB_PASS'),
    getenv('DB_NAME'),
    getenv('DB_PORT') ?: 3306
);
// VERY IMPORTANT FOR RAILWAY
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
if ($conn->connect_error) {
    die("DB Error: " . $conn->connect_error);
}
// -------------------- CHECKLIST ID --------------------
$checklist_id = $_GET['id'] ?? null;
if(!$checklist_id){
    die("Checklist ID Missing");
}

function normalizePath($path){
    $path = str_replace('/event-reports/public/', '', $path);
    $path = str_replace('/event-reports/public/', '', $path);
    return ltrim($path,'/');
}

/**
 * Return full Cloudinary URL as-is (or fallback if needed)
 */
function buildImagePath($image_value) {
    if (empty($image_value)) return '';
    if (filter_var($image_value, FILTER_VALIDATE_URL) && stripos($image_value, 'https://res.cloudinary.com') === 0) {
        return $image_value;
    }
    return 'https://event-reports-production.up.railway.app/' . ltrim($image_value, '/');
}

// -------------------- FETCH DATA --------------------
try {
    $er_stmt = $conn->prepare("SELECT * FROM event_report WHERE checklist_id=?");
    $er_stmt->bind_param("s",$checklist_id);
    $er_stmt->execute();
    $event = $er_stmt->get_result()->fetch_assoc();
    if(!$event){
        die("No Event Report Found");
    }
    $chk_stmt = $conn->prepare("
        SELECT programme_name, programme_date, multi_day,
               programme_start_date, programme_end_date,
               department, created_by
        FROM checklists
        WHERE id=?
    ");
    $chk_stmt->bind_param("s",$checklist_id);
    $chk_stmt->execute();
    $checklist = $chk_stmt->get_result()->fetch_assoc();

    $notice_stmt = $conn->prepare("SELECT event_time,event_venue FROM notice WHERE checklist_id=?");
    $notice_stmt->bind_param("s",$checklist_id);
    $notice_stmt->execute();
    $notice = $notice_stmt->get_result()->fetch_assoc();

    $guest_stmt=$conn->prepare("
        SELECT guest_name, company_name, designation , guest_email
        FROM checklist_guests
        WHERE checklist_id=?
    ");
    $guest_stmt->bind_param("s",$checklist_id);
    $guest_stmt->execute();
    $guest_res=$guest_stmt->get_result();
    $guest_name=[];
    $company_name=[];
    $designation=[];
    $email=[];
   
    while($g=$guest_res->fetch_assoc()){
        $guest_name[]=$g['guest_name'];
        $company_name[]=$g['company_name'];
        $designation[]=$g['designation'];
        $guest_email[]=$g['guest_email'];
    }

    $deptArray = json_decode($checklist['department'], true);
    $default_stmt = $conn->prepare("SELECT image FROM default_header LIMIT 1");
    $default_stmt->execute();
    $default_row = $default_stmt->get_result()->fetch_assoc();
    $header_image = $default_row['image'] ?? "";
    $dept_id = null;
    if(is_array($deptArray) && count($deptArray)==1){
        $dept_id=$deptArray[0];
        $dept_stmt=$conn->prepare("SELECT header_image FROM departments WHERE id=?");
        $dept_stmt->bind_param("s",$dept_id);
        $dept_stmt->execute();
        $dept_row=$dept_stmt->get_result()->fetch_assoc();
        if(!empty($dept_row['header_image'])){
            $header_image=$dept_row['header_image'];
        }
    }

    $programme_name = htmlspecialchars($checklist['programme_name']);
    if($checklist['multi_day']){
        $event_date=date('d-m-Y',strtotime($checklist['programme_start_date'])).
                    " to ".
                    date('d-m-Y',strtotime($checklist['programme_end_date']));
    }else{
        $event_date=date('d-m-Y',strtotime($checklist['programme_date']));
    }
    $event_time = !empty($notice['event_time']) ? date('h:i A',strtotime($notice['event_time'])) : "N/A";
    $event_venue = $notice['event_venue'] ?? "N/A";
    $photos=json_decode($event['photos'],true) ?? [];
    $captions=json_decode($event['captions'],true) ?? [];
   
    $coordinator_id = $checklist['created_by'];
    $pc_stmt = $conn->prepare("
        SELECT username, sign_image
        FROM users
        WHERE id=?
    ");
    $pc_stmt->bind_param("s",$coordinator_id);
    $pc_stmt->execute();
    $pc = $pc_stmt->get_result()->fetch_assoc();
    $coordinator_name = $pc['username'] ?? "Coordinator";
    $coordinator_sign = $pc['sign_image'] ?? "";

    $hod_name="N/A";
    $hod_sign="";
    $deptArray = json_decode($checklist['department'] ?? '[]', true);
    if (is_array($deptArray) && count($deptArray) === 1) {
        $dept_id = $deptArray[0];
        $hod_stmt=$conn->prepare("
            SELECT username,sign_image
            FROM users
            WHERE role='hod' AND department_id=?
            LIMIT 1
        ");
        $hod_stmt->bind_param("s",$dept_id);
        $hod_stmt->execute();
        $hod=$hod_stmt->get_result()->fetch_assoc();
        $hod_name=$hod['username'] ?? "N/A";
        $hod_sign=$hod['sign_image'] ?? "";
    }

    $pr_stmt=$conn->prepare("
        SELECT username,sign_image
        FROM users
        WHERE role='principal'
        LIMIT 1
    ");
    $pr_stmt->execute();
    $principal=$pr_stmt->get_result()->fetch_assoc();
    $principal_name=$principal['username'] ?? "Principal";
    $principal_sign=$principal['sign_image'] ?? "";
} catch (Exception $e) {
    die("Database Error: " . htmlspecialchars($e->getMessage()));
}
// -------------------- TCPDF --------------------
require_once __DIR__ . '/../../tcpdf/tcpdf.php';

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

$pdf->SetCreator('Event Management System');
$pdf->SetAuthor('Keystone School of Engineering');
$pdf->SetTitle('Event Report - ' . $programme_name);
$pdf->SetSubject('Event Report');
$pdf->SetKeywords('Event, Report, PDF');

$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

$pdf->SetMargins(18, 15, 18);
$pdf->SetAutoPageBreak(true, 15);

$pdf->AddPage();

// Important settings for images (auto size + best quality)
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
$pdf->setJPEGQuality(100);

$pdf->SetFont('helvetica', 'B', 16);

// -------------------- HEADER IMAGE --------------------
$header_image_url = buildImagePath($header_image);
if (!empty($header_image_url)) {
    $pdf->Image($header_image_url, 15, 12, 180, 0, '', '', 'T', false, 300);
    $pdf->Ln(42);
} else {
    $pdf->Ln(12);
}

// -------------------- TITLE --------------------
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'EVENT REPORT', 0, 1, 'C');
$pdf->Ln(10);

// -------------------- EVENT DETAILS --------------------
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'Event Details', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 11);

$details = [
    'Name of Event' => $programme_name,
    'Day & Date' => $event_date,
    'Time' => $event_time,
    'Venue' => htmlspecialchars($event_venue)
];

foreach ($details as $label => $value) {
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(45, 6, $label . ':', 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 11);
    $pdf->MultiCell(0, 6, $value, 0, 'L');
    $pdf->Ln(2);
}
$pdf->Ln(5);

// -------------------- GUEST TABLE --------------------
if (!empty($guest_name)) {
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Guest Details', 0, 1, 'L');
    $pdf->Ln(2);
   
    $pdf->SetFillColor(0, 0, 0);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(45, 8, 'Name', 1, 0, 'C', true);
    $pdf->Cell(45, 8, 'Company', 1, 0, 'C', true);
    $pdf->Cell(45, 8, 'Designation', 1, 0, 'C', true);
    $pdf->Cell(45, 8, 'Email', 1, 1, 'C', true);
   
    $pdf->SetTextColor(0, 0, 0);
   
    $pdf->SetFont('helvetica', '', 10);
    for ($i = 0; $i < count($guest_name); $i++) {
        $pdf->Cell(45, 8, htmlspecialchars($guest_name[$i]), 1, 0, 'L');
        $pdf->Cell(45, 8, htmlspecialchars($company_name[$i] ?? ''), 1, 0, 'L');
        $pdf->Cell(45, 8, htmlspecialchars($designation[$i] ?? ''), 1, 0, 'L');
        $pdf->Cell(45, 8, htmlspecialchars($guest_email[$i] ?? ''), 1, 1, 'L');
    }
   
    $pdf->Ln(5);
}

// -------------------- DESCRIPTION & CONTENT --------------------
$sections = [
    'Description' => $event['description'] ?? '',
    'Activities and Highlights' => $event['activities'] ?? '',
    'Significance' => $event['significance'] ?? '',
    'Conclusion' => $event['conclusion'] ?? '',
    'Faculties\' Responses & Participation' => $event['faculties_participation'] ?? ''
];

foreach ($sections as $title => $content) {
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, $title . ':', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 11);
    $pdf->writeHTMLCell(0, 6, '', '', $content, 0, 1, false, true, 'L');
    $pdf->Ln(5);
}

// -------------------- PHOTOS --------------------
if (!empty($photos)) {
    $pdf->Cell(0, 10, 'Photos', 0, 1, 'C');
    $pdf->Ln(5);

    foreach ($photos as $i => $photo_db_value) {
        $photo_url = buildImagePath($photo_db_value);

        if (empty($photo_url)) continue;

        $current_y = $pdf->GetY();
        $page_height = $pdf->getPageHeight();
        $margin_bottom = $pdf->getBreakMargin();

        if ($current_y + 120 > $page_height - $margin_bottom) {
            $pdf->AddPage();
        }

        // $pdf->SetX(55);
        
        // Auto size (width 100, height 0 = preserve aspect ratio)
        // Desired width
        $img_width = 100;

// Safe image size fetch
$img_info = @getimagesize($photo_url);

if ($img_info) {
    list($original_width, $original_height) = $img_info;
} else {
    $original_width = 100;
    $original_height = 100;
}

// Calculate proportional height
$img_height = ($original_height / $original_width) * $img_width;

// Limit height (no distortion)
$max_height = 120;
if ($img_height > $max_height) {
    $ratio = $max_height / $img_height;
    $img_height = $max_height;
    $img_width = $img_width * $ratio;
}

// Page break check
$current_y = $pdf->GetY();
$page_height = $pdf->getPageHeight();
$margin_bottom = $pdf->getBreakMargin();

if ($current_y + $img_height + 20 > $page_height - $margin_bottom) {
    $pdf->AddPage();
}

// Center image
$pdf->SetX((210 - $img_width) / 2);

// Draw image
$pdf->Image($photo_url, '', '', $img_width, $img_height);

// Move cursor
$pdf->Ln($img_height + 5);

// Caption
$pdf->SetFont('helvetica', 'I', 10);
$pdf->Cell(0, 5, htmlspecialchars($captions[$i] ?? ""), 0, 1, 'C');
$pdf->Ln(8);
    }
} else {
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(0, 10, 'No photos available.', 0, 1, 'C');
    $pdf->Ln(5);
}

// ================== PAGE BREAK FOR SIGNATURES ==================
$current_y = $pdf->GetY();
$page_height = $pdf->getPageHeight();
$margin_bottom = $pdf->getBreakMargin();
$signature_block_height = 100;

if ($current_y + $signature_block_height > $page_height - $margin_bottom) {
    $pdf->AddPage();
    $margins = $pdf->getMargins();
    $pdf->SetY($margins['top']);
} else {
    $pdf->Ln(8);
}

// -------------------- SIGNATURES --------------------
$coordinator_path = buildImagePath($coordinator_sign);
$hod_path         = buildImagePath($hod_sign);
$principal_path   = buildImagePath($principal_sign);

$signature_data = [
    ['name' => $coordinator_name, 'title' => 'Coordinator', 'path' => $coordinator_path]
];

if (!empty($hod_name) && $hod_name !== 'N/A') {
    $signature_data[] = ['name' => $hod_name, 'title' => 'HOD', 'path' => $hod_path];
}

$signature_data[] = ['name' => $principal_name, 'title' => 'Principal', 'path' => $principal_path];

$signature_count = count($signature_data);
$col_width = 64;
$sig_image_width = 36;
$sig_y = $pdf->GetY() + 12;
$line_y = $sig_y + 20;
$name_y = $line_y + 6;
$title_y = $name_y + 8;

if ($signature_count == 2) {
    $start_x = 20;
    $right_x = $pdf->GetPageWidth() - 20 - $col_width;
} else {
    $start_x = ($pdf->GetPageWidth() - (3 * $col_width)) / 2;
}

// Signature images (auto height)
foreach ($signature_data as $index => $sig) {
    $x = ($signature_count == 2) ? (($index == 0) ? $start_x : $right_x) : ($start_x + ($index * $col_width));

    if (!empty($sig['path'])) {
        $pdf->Image(
            $sig['path'],
            $x + ($col_width - $sig_image_width)/2,
            $sig_y,
            $sig_image_width,
            0,   // ← auto height (default behavior)
            '',
            '',
            'T',
            false,
            300
        );
    }
}

// Horizontal lines
$pdf->SetLineWidth(0.5);
foreach ($signature_data as $index => $sig) {
    $x = ($signature_count == 2) ? (($index == 0) ? $start_x : $right_x) : ($start_x + ($index * $col_width));
    $pdf->Line($x + 8, $line_y, $x + $col_width - 8, $line_y);
}

// Names
$pdf->SetFont('helvetica', '', 10);
foreach ($signature_data as $index => $sig) {
    $x = ($signature_count == 2) ? (($index == 0) ? $start_x : $right_x) : ($start_x + ($index * $col_width));
    $pdf->SetXY($x, $name_y);
    $pdf->Cell($col_width, 7, $sig['name'], 0, 0, 'C');
}

// Titles
$pdf->SetFont('helvetica', '', 9);
foreach ($signature_data as $index => $sig) {
    $x = ($signature_count == 2) ? (($index == 0) ? $start_x : $right_x) : ($start_x + ($index * $col_width));
    $pdf->SetXY($x, $title_y);
    $pdf->Cell($col_width, 6, $sig['title'], 0, 0, 'C');
}

$pdf->SetY($title_y + 10);

// Footer
$pdf->Ln(12);
$pdf->SetFont('helvetica', '', 9);
$pdf->SetLineWidth(0.4);
$footer_y = $pdf->GetY();
$pdf->Line(18, $footer_y, $pdf->GetPageWidth() - 18, $footer_y);
$pdf->SetY($footer_y + 4);
$pdf->Cell(0, 6, 'Keystone School of Engineering, Near Handewadi Chowk, Urali Devachi, Shewalewadi, Pune - 412308', 0, 1, 'C');
$pdf->Cell(0, 6, 'www.keystoneschoolofengineering.com', 0, 1, 'C');
$pdf->Ln(6);

// -------------------- OUTPUT --------------------
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="Event_Report_' . $checklist_id . '.pdf"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

echo $pdf->Output('Event_Report_' . $checklist_id . '.pdf', 'S');
exit();
