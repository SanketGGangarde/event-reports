<?php
session_start();
require_once __DIR__ . '/../../init/_dbconnect.php';

if(!isset($_GET['checklist_id'])){
    die("Checklist ID Missing");
}

$checklist_id = $_GET['checklist_id'] ?? null;
if(!$checklist_id){
    die("Checklist ID Missing");
}

/* ======================================================
   FETCH EVENT REPORT
====================================================== */
$er = $conn->prepare("SELECT * FROM event_report WHERE checklist_id=?");
$er->bind_param("s",$checklist_id);
$er->execute();
$event = $er->get_result()->fetch_assoc();

/* ======================================================
   FETCH CHECKLIST
====================================================== */
$chk = $conn->prepare("SELECT programme_name, programme_date, multi_day, programme_start_date, programme_end_date, department FROM checklists WHERE id=?");
$chk->bind_param("s",$checklist_id);
$chk->execute();
$checklist = $chk->get_result()->fetch_assoc();

/* ======================================================
   FETCH NOTICE
====================================================== */
$n = $conn->prepare("SELECT event_time,event_venue FROM notice WHERE checklist_id=?");
$n->bind_param("s",$checklist_id);
$n->execute();
$notice = $n->get_result()->fetch_assoc();

/* ======================================================
   FETCH GUESTS
====================================================== */
$g = $conn->prepare("SELECT guest_name,company_name,contact_no FROM checklist_guests WHERE checklist_id=?");
$g->bind_param("s",$checklist_id);
$g->execute();
$gr=$g->get_result();

$rp=[];$comp=[];$con=[];
while($row=$gr->fetch_assoc()){
    $rp[]=$row['guest_name'];
    $comp[]=$row['company_name'];
    $con[]=$row['contact_no'];
}

/* ======================================================
   HEADER IMAGE
====================================================== */
$deptArray=json_decode($checklist['department'],true);

$h=$conn->query("SELECT image FROM default_header LIMIT 1")->fetch_assoc();
$header_image=$h['image'];

if(is_array($deptArray)&&count($deptArray)==1){
    $dept_id = $deptArray[0]; // Keep as UUID string
    $d=$conn->prepare("SELECT header_image FROM departments WHERE id=?");
    $d->bind_param("s",$dept_id);
    $d->execute();
    $r=$d->get_result()->fetch_assoc();
    if(!empty($r['header_image'])){
        $header_image=$r['header_image'];
    }
}

/* ======================================================
   USERS
====================================================== */
$uid=$_SESSION['user_id'];

$pc=$conn->prepare("SELECT username,sign_image FROM users WHERE id=?");
$pc->bind_param("s",$uid);
$pc->execute();
$pc=$pc->get_result()->fetch_assoc();

$hod=$conn->query("SELECT username,sign_image FROM users WHERE role='hod' LIMIT 1")->fetch_assoc();
$pr=$conn->query("SELECT username,sign_image FROM users WHERE role='principal' LIMIT 1")->fetch_assoc();

/* ======================================================
   FORMAT DATE
====================================================== */
if($checklist['multi_day']){
    $event_date=date("d-m-Y",strtotime($checklist['programme_start_date']))." to ".
                date("d-m-Y",strtotime($checklist['programme_end_date']));
}else{
    $event_date=date("d-m-Y",strtotime($checklist['programme_date']));
}

$photos=json_decode($event['photos'],true) ?? [];
$captions=json_decode($event['captions'],true) ?? [];

/* ======================================================
   CONVERT IMAGE TO BASE64 FUNCTION
====================================================== */
function imgToBase64($path){

    if(empty($path)) return "";

    $path = ltrim($path, "/");

    // Possible base roots
    $roots = [
        "/opt/lampp/htdocs/",
        "/opt/lampp/htdocs/Final_Project/"
    ];

    foreach($roots as $root){
        $full = $root . $path;
        if(file_exists($full)){
            $ext  = pathinfo($full, PATHINFO_EXTENSION);
            $data = base64_encode(file_get_contents($full));
            return "data:image/$ext;base64,$data";
        }
    }

    // nothing found
    return "";
}

/* ======================================================
   DOWNLOAD HEADERS
====================================================== */
header("Content-Type: application/vnd.ms-word");
header("Content-Disposition: attachment; filename=Event_Report_$checklist_id.doc");

/* ======================================================
   START HTML
====================================================== */
echo "<html>
<head>
<style>
body{font-family:Times New Roman;}
img{max-width:200px;}
.center{text-align:center;}
table{width:100%;}
td{text-align:center;padding-top:60px;}
</style>
</head>

<body>

<div class='center'>
<img src='".imgToBase64($header_image)."'><br><br>
<h2>EVENT REPORT</h2>
</div>

<p><b>Name of Event:</b> {$checklist['programme_name']}</p>
<p><b>Day & Date:</b> $event_date</p>
<p><b>Time:</b> {$notice['event_time']}</p>
<p><b>Venue:</b> {$notice['event_venue']}</p>

<p><b>Resource Person:</b> ".implode(", ",$rp)."</p>
<p><b>Company Details:</b> ".implode(", ",$comp)."</p>
<p><b>Contact No:</b> ".implode(", ",$con)."</p>

<p><b>Description:</b><br>{$event['activities']}</p>
<p><b>Significance:</b><br>{$event['significance']}</p>
<p><b>Conclusion:</b><br>{$event['conclusion']}</p>
<p><b>Faculties Participation:</b><br>{$event['faculties_participation']}</p>

<h3 class='center'>Photos</h3>";

/* ======================================================
   PHOTOS
====================================================== */
foreach($photos as $i=>$p){

    $img = imgToBase64($p);
    if($img!=""){
        echo "<div class='center'>
                <img src='$img'><br>
                <b>".($captions[$i] ?? "")."</b>
              </div><br>";
    }
}

/* ======================================================
   SIGNATURES
====================================================== */
echo "<table>
<tr>

<td>
<img src='".imgToBase64($pc['sign_image'])."' width='120'><br>
<b>{$pc['username']}</b><br>
Coordinator
</td>

<td>
<img src='".imgToBase64($hod['sign_image'])."' width='120'><br>
<b>{$hod['username']}</b><br>
HOD
</td>

<td>
<img src='".imgToBase64($pr['sign_image'])."' width='120'><br>
<b>{$pr['username']}</b><br>
Principal
</td>

</tr>
</table>

</body>
</html>";
?>