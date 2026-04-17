<?php

/**
 * HomeController
 * Handles dashboard/home page: upcoming events carousel + event reports list
 */
use Ramsey\Uuid\Uuid;
require_once __DIR__ . '/../core/BaseController.php';

class HomeController extends BaseController
{
    public function __construct()
    {
       
        
        parent::__construct();
        
        
    }

    public function index()
    {
        $errors = [];
        $upcomingEvents = [];
        $eventReports = [];

        try {
            // 1. Upcoming Events (from 'events' table)
            $currentDate = date('Y-m-d');

            $stmtUpcoming = $this->pdo->prepare("
                SELECT event_name, start_date, end_date, image_path
                FROM events
                WHERE end_date >= ?
                ORDER BY start_date ASC
            ");
            $stmtUpcoming->execute([$currentDate]);
            $upcomingEvents = $stmtUpcoming->fetchAll(PDO::FETCH_ASSOC);

            // 2. Event Reports List (from checklists + event_report)
            $stmtReports = $this->pdo->prepare("
                SELECT
                    c.id,
                    c.programme_name,
                    c.programme_date,
                    c.multi_day,
                    c.programme_start_date,
                    c.programme_end_date
                FROM checklists c
                INNER JOIN event_report er ON er.checklist_id = c.id
                ORDER BY c.programme_date DESC
            ");
            $stmtReports->execute();
            $eventReports = $stmtReports->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Home page query error: " . $e->getMessage());
            $errors[] = "Unable to load data. Please try again later.";
        }

        // Render view
        $this->render('home', [
            'upcomingEvents' => $upcomingEvents,
            'eventReports'   => $eventReports,
            'errors'         => $errors
        ]);
    }
}