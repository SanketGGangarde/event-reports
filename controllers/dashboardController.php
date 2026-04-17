<?php

/**
 * DashboardController
 * Handles the main dashboard / event list page
 */
use Ramsey\Uuid\Uuid;
require_once __DIR__ . '/../core/BaseController.php';

class DashboardController extends BaseController
{
    public function __construct()
    {
        
       
        
        parent::__construct();
        
        
    }

    public function index()
    {

   
        // ----------------------
        // AUTH CHECK
        // ----------------------
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
            exit;
        }

       

        // ----------------------
        // FETCH USER
        // ----------------------
       $stmtUser = $this->pdo->prepare("
    SELECT u.*, d.name AS department_name
    FROM users u
    LEFT JOIN departments d ON u.department_id = d.id
    WHERE u.id = ?
");
        $stmtUser->execute([$_SESSION['user_id']]);
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            session_destroy();
            $this->redirect('/login?error=user_not_found');
        }

        // Update session role (if needed)
        $_SESSION['role'] = $user['role'];

        $user_id   = $user['id'];
        $user_role = $user['role'];
        $user_dept = $user['department_id'];

        // ----------------------
        // FETCH EVENTS BY ROLE
        // ----------------------
        $events = [];

        if ($user_role === 'principal') {
            $stmt = $this->pdo->prepare("
    SELECT c.id, c.programme_name, c.programme_date,
           c.multi_day, c.programme_start_date,
           u.username as coordinator_name,
           d.name AS department_name
    FROM checklists c
    LEFT JOIN users u ON c.created_by = u.id
    LEFT JOIN departments d ON u.department_id = d.id
    ORDER BY c.programme_date DESC
");

            $stmt->execute();
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } 
        elseif ($user_role === 'hod') {
            $stmt = $this->pdo->prepare("
    SELECT c.id, c.programme_name, c.programme_date,
           c.multi_day, c.programme_start_date,
           u.username as coordinator_name,
           d.name AS department_name
    FROM checklists c
    LEFT JOIN users u ON c.created_by = u.id
    LEFT JOIN departments d ON u.department_id = d.id
    WHERE c.userdept_id = ?
    ORDER BY c.programme_date DESC
");

            $stmt->execute([$user_dept]);
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } 
        else {
            // Coordinator / others → only own events
            $stmt = $this->pdo->prepare("
    SELECT c.id, c.programme_name, c.programme_date,
           c.multi_day, c.programme_start_date,
           u.username as coordinator_name,
           d.name AS department_name
    FROM checklists c
    LEFT JOIN users u ON c.created_by = u.id
    LEFT JOIN departments d ON u.department_id = d.id
    WHERE c.created_by = ?
    ORDER BY c.programme_date DESC
");

            $stmt->execute([$user_id]);
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Prepare display dates for each event
        $preparedEvents = [];
        foreach ($events as $event) {
            $display_date = $event['multi_day'] 
                ? $event['programme_start_date'] 
                : $event['programme_date'];

            $preparedEvents[] = [
    'id'               => $event['id'],
    'programme_name'   => $event['programme_name'],
    'coordinator_name' => $event['coordinator_name'] ?? 'N/A',
    'department_name'  => $event['department_name'] ?? 'N/A',
    'display_date'     => $display_date,
    'formatted_date'   => $display_date ? date("d-m-Y", strtotime($display_date)) : 'N/A'
    
];
        }

         

        // Pass data to view
        $this->render('manage/eventmanage', [
            'user'          => $user,
            'events'        => $preparedEvents,
            'user_role'     => $user_role
        ]);

       
    }
}