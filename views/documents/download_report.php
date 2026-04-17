<?php
/**
 * Download Event Report as PDF using TCPDF
 * Fixed version - signatures always on last page, independent of photo count
 */

// -------------------- SESSION & DB --------------------
require_once __DIR__ . '/../layouts/header.php';

// CSRF token: generate if it does not exist
$_SESSION['csrf_token'] = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));

// -------------------- INPUT VALIDATION --------------------
$checklist_id = $_GET['checklist_id'] ?? '';
$checklist_id = trim($checklist_id);

if (empty($checklist_id)) {
    die("Invalid or missing Checklist ID");
}

// -------------------- PATH NORMALIZER --------------------
function normalizePath($path) {
    // Remove common project path prefixes to get relative path
    $path = str_replace([
        '/event-reports/public/', 
        'event-reports/public/',
        '/events-reports/public/',
        'events-reports/public/',
        '/event-reports/',
        'event-reports/',
        '/events-reports/',
        'events-reports/'
    ], '', $path);
    return ltrim($path, '/');
}

// -------------------- FETCH ALL DATA --------------------
try {
    // 1. Event Report
    $stmt = $conn->prepare("SELECT * FROM event_report WHERE checklist_id = ?");
    $stmt->bind_param("i", $checklist_id);
    $stmt->execute();
    $event = $stmt->get_result()->fetch_assoc();
    if (!$event) die("No event report found for this checklist.");

    // 2. Checklist
    $stmt = $conn->prepare("
        SELECT programme_name, programme_date, multi_day,
               programme_start_date, programme_end_date,
               department, created_by
        FROM checklists WHERE id = ?
    ");
    $stmt->bind_param("i", $checklist_id);
    $stmt->execute();
    $checklist = $stmt->get_result()->fetch_assoc();

    // 3. Notice
    $stmt = $conn->prepare("SELECT event_time, event_venue FROM notice WHERE checklist_id = ?");
    $stmt->bind_param("i", $checklist_id);
    $stmt->execute();
    $notice = $stmt->get_result()->fetch_assoc();

    // 4. Guests
    $stmt = $conn->prepare("SELECT guest_name, company_name, contact_no FROM checklist_guests WHERE checklist_id = ?");
    $stmt->bind_param("i", $checklist_id);
    $stmt->execute();
    $guests = $stmt->get_result();

    $resource_persons = $companies = $contacts = [];
    while ($g = $guests->fetch_assoc()) {
        $resource_persons[] = $g['guest_name'];
        $companies[]        = $g['company_name'];
        $contacts[]         = $g['contact_no'];
    }

    // 5. Header Image
    $dept_ids = json_decode($checklist['department'] ?? '[]', true) ?? [];
    $header_image = "";

    $stmt = $conn->prepare("SELECT image FROM default_header LIMIT 1");
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) $header_image = $row['image'] ?? '';

    if (count($dept_ids) === 1) {
        $dept_id = (int)$dept_ids[0];
        $stmt = $conn->prepare("SELECT header_image FROM departments WHERE id = ?");
        $stmt->bind_param("i", $dept_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!empty($row['header_image'])) {
            $header_image = $row['header_image'];
        }
    }

    // 6. Format event info
    $programme_name = htmlspecialchars($checklist['programme_name'] ?? 'Event');

    if (!empty($checklist['multi_day'])) {
        $event_date = date('d-m-Y', strtotime($checklist['programme_start_date'])) .
                      ' to ' .
                      date('d-m-Y', strtotime($checklist['programme_end_date']));
    } else {
        $event_date = date('d-m-Y', strtotime($checklist['programme_date'] ?? 'now'));
    }

    $event_time  = !empty($notice['event_time']) ? date('h:i A', strtotime($notice['event_time'])) : 'N/A';
    $event_venue = htmlspecialchars($notice['event_venue'] ?? 'N/A');

    $photos   = json_decode($event['photos']   ?? '[]', true) ?? [];
    $captions = json_decode($event['captions'] ?? '[]', true) ?? [];

    // 7. Coordinator
    $stmt = $conn->prepare("SELECT username, sign_image FROM users WHERE id = ?");
    $stmt->bind_param("i", $checklist['created_by']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $coordinator_name = $user['username']   ?? 'Coordinator';
    $coordinator_sign = $user['sign_image'] ?? '';

    // 8. HOD (if department known)
    $hod_name = 'HOD';
    $hod_sign = '';
    if (!empty($dept_id)) {
        $stmt = $conn->prepare("SELECT username, sign_image FROM users WHERE role = 'hod' AND department_id = ? LIMIT 1");
        $stmt->bind_param("i", $dept_id);
        $stmt->execute();
        $hod = $stmt->get_result()->fetch_assoc();
        if ($hod) {
            $hod_name = $hod['username'];
            $hod_sign = $hod['sign_image'] ?? '';
        }
    }

    // 9. Principal
    $stmt = $conn->prepare("SELECT username, sign_image FROM users WHERE role = 'principal' LIMIT 1");
    $stmt->execute();
    $principal = $stmt->get_result()->fetch_assoc();
    $principal_name = $principal['username']   ?? 'Principal';
    $principal_sign = $principal['sign_image'] ?? '';

} catch (Exception $e) {
    http_response_code(500);
    die("Database error: " . htmlspecialchars($e->getMessage()));
}

// -------------------- TCPDF INITIALIZATION --------------------
require_once __DIR__ . '/../../tcpdf/tcpdf.php';

$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

$pdf->SetCreator('Event Management System');
$pdf->SetAuthor('Keystone School of Engineering');
$pdf->SetTitle('Event Report - ' . $programme_name);
$pdf->SetSubject('Event Report');

$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

$pdf->SetMargins(18, 15, 18);
$pdf->SetAutoPageBreak(true, 15);

// -------------------- PAGE 1 – HEADER + DETAILS + SECTIONS --------------------
$pdf->AddPage();

$header_path = $_SERVER['DOCUMENT_ROOT'] . '/' . normalizePath($header_image);
if ($header_image && file_exists($header_path)) {
    $pdf->Image($header_path, 15, 12, 180, 0, '', '', 'T', false, 300);
    $pdf->Ln(42);
} else {
    $pdf->Ln(15);
}

$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'EVENT REPORT', 0, 1, 'C');
$pdf->Ln(12);

// Event Details
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'Event Details', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 11);

$details = [
    'Name of Event'    => $programme_name,
    'Day & Date'       => $event_date,
    'Time'             => $event_time,
    'Venue'            => $event_venue,
    'Resource Person'  => implode(', ', $resource_persons) ?: '—',
    'Company Details'  => implode(', ', $companies) ?: '—',
    'Contact No.'      => implode(', ', $contacts) ?: '—'
];

foreach ($details as $label => $value) {
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(50, 7, $label . ':', 0, 0);
    $pdf->SetFont('helvetica', '', 11);
    $pdf->MultiCell(0, 7, $value, 0, 'L');
    $pdf->Ln(1);
}
$pdf->Ln(10);

// Description Sections
$sections = [
    'Description'                           => $event['description'] ?? '',
    'Activities and Highlights'             => $event['activities'] ?? '',
    'Significance'                          => $event['significance'] ?? '',
    'Conclusion'                            => $event['conclusion'] ?? '',
    "Faculties' Responses & Participation"  => $event['faculties_participation'] ?? ''
];

foreach ($sections as $title => $text) {
    if (trim($text) === '') continue;
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, $title . ':', 0, 1);
    $pdf->SetFont('helvetica', '', 11);
    $pdf->MultiCell(0, 6, strip_tags($text), 0, 'L');
    $pdf->Ln(8);
}

// -------------------- PHOTOS – on new page --------------------
$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Event Photographs', 0, 1, 'C');
$pdf->Ln(10);

if (empty($photos)) {

    $pdf->SetFont('helvetica', 'I', 11);
    $pdf->Cell(0, 8, 'No photographs available.', 0, 1, 'C');

} else {

    $imageWidth  = 100;
    $imageHeight = 65;
    $blockGap    = 18;

    foreach ($photos as $idx => $rel_path) {

        $img_path = $_SERVER['DOCUMENT_ROOT'] . '/' . normalizePath($rel_path);
        if (!file_exists($img_path)) continue;

        // Page break protection
        if ($pdf->GetY() + $imageHeight + 30 > 
            ($pdf->getPageHeight() - $pdf->getBreakMargin())) {
            $pdf->AddPage();
        }

        // Center X
        $x = ($pdf->getPageWidth() - $imageWidth) / 2;
        $y = $pdf->GetY();

        // Draw image
        $pdf->Image($img_path, $x, $y, $imageWidth, $imageHeight);

        // Move cursor below image
        $pdf->SetY($y + $imageHeight + 4);

        // Caption
        $pdf->SetFont('helvetica', 'I', 10);
        $pdf->MultiCell(0, 6, htmlspecialchars($captions[$idx] ?? ''), 0, 'C');

        // Space before next photo
        $pdf->Ln($blockGap);
    }
}

// -------------------- SIGNATURES – dedicated final page --------------------
$pdf->SetAutoPageBreak(false);
$pdf->AddPage();
$pdf->setPageMark();

// Force enough top space – prevents jumping
$pdf->Ln(40);  // ← increased from 25 – gives breathing room

$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Approved By', 0, 1, 'C');
$pdf->Ln(30);  // more space before signatures

// ────────────────────────────────────────────────
// Use FIXED starting Y positions (independent of previous content)
// ────────────────────────────────────────────────
$start_y       = $pdf->GetY();           // usually ~80–100 after above Ln()
$sig_image_y   = $start_y;
$line_y        = $sig_image_y + 28;      // higher line → more room for tall signatures
$name_y        = $line_y + 10;
$title_y       = $name_y + 10;

$col_w         = 62;
$start_x       = ($pdf->getPageWidth() - 3 * $col_w) / 2;
$img_size      = 38;

$signatures = [
    [
        'name'  => $coordinator_name,
        'title' => 'Coordinator',
        'path'  => $_SERVER['DOCUMENT_ROOT'] . '/' . normalizePath($coordinator_sign)
    ],
    [
        'name'  => $hod_name,
        'title' => 'HOD',
        'path'  => $_SERVER['DOCUMENT_ROOT'] . '/' . normalizePath($hod_sign)
    ],
    [
        'name'  => $principal_name,
        'title' => 'Principal',
        'path'  => $_SERVER['DOCUMENT_ROOT'] . '/' . normalizePath($principal_sign)
    ]
];

foreach ($signatures as $i => $s) {
    $x = $start_x + $i * $col_w;

    // Signature image – centered in column
    if (!empty($s['path']) && file_exists($s['path'])) {
        $pdf->Image(
            $s['path'],
            $x + ($col_w - $img_size)/2,
            $sig_image_y,
            $img_size,
            0,
            '',
            '',
            'T',
            false,
            300
        );
    }

    // Signature line – longer & higher
    $pdf->SetLineWidth(0.6);
    $pdf->Line($x + 6, $line_y, $x + $col_w - 6, $line_y);

    // Name – boldish
    $pdf->SetFont('helvetica', '', 11);
    $pdf->SetXY($x, $name_y);
    $pdf->Cell($col_w, 8, $s['name'], 0, 0, 'C');

    // Title – smaller
    $pdf->SetFont('helvetica', 'I', 10);
    $pdf->SetXY($x, $title_y);
    $pdf->Cell($col_w, 6, $s['title'], 0, 0, 'C');
}

// Institute footer – push to bottom
$pdf->SetY(-55);   // ← higher number = closer to bottom
$pdf->SetFont('helvetica', '', 9);
$pdf->Line(18, $pdf->GetY(), $pdf->getPageWidth() - 18, $pdf->GetY());
$pdf->Ln(8);
$pdf->Cell(0, 6, 'Keystone School of Engineering, Near Handewadi Chowk, Urali Devachi, Shewalewadi, Pune - 412308', 0, 1, 'C');
$pdf->Cell(0, 6, 'www.keystoneschoolofengineering.com', 0, 1, 'C');
// Institute footer
$pdf->SetY(-50);
$pdf->SetFont('helvetica', '', 9);
$pdf->Line(18, $pdf->GetY(), $pdf->getPageWidth() - 18, $pdf->GetY());
$pdf->Ln(6);
$pdf->Cell(0, 6, 'Keystone School of Engineering, Near Handewadi Chowk, Urali Devachi, Shewalewadi, Pune - 412308', 0, 1, 'C');
$pdf->Cell(0, 6, 'www.keystoneschoolofengineering.com', 0, 1, 'C');

// -------------------- OUTPUT AS DOWNLOAD --------------------
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="Event_Report_' . $checklist_id . '.pdf"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$pdf->Output('Event_Report_' . $checklist_id . '.pdf', 'D');
exit();