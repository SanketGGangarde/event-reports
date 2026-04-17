<?php
/**
 * Event Cleanup Utility
 * Automatically removes expired events from the database
 */

require_once __DIR__ . '/_dbconnect.php';

function cleanupExpiredEvents() {
    global $pdo;
    
    try {
        // Get current date
        $currentDate = date('Y-m-d');
        
        // Find expired events (end_date is less than current date)
        $stmt = $pdo->prepare("
            SELECT id, image_path 
            FROM events 
            WHERE end_date < ?
        ");
        $stmt->execute([$currentDate]);
        $expiredEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($expiredEvents)) {
            // Delete image files
            foreach ($expiredEvents as $event) {
                if ($event['image_path'] && file_exists($event['image_path'])) {
                    unlink($event['image_path']);
                }
            }
            
            // Delete expired events from database
            $stmt = $pdo->prepare("
                DELETE FROM events 
                WHERE end_date < ?
            ");
            $stmt->execute([$currentDate]);
            
            return count($expiredEvents);
        }
        
        return 0;
    } catch (Exception $e) {
        error_log("Event cleanup failed: " . $e->getMessage());
        return false;
    }
}

// Run cleanup when this file is included
$deletedCount = cleanupExpiredEvents();
if ($deletedCount > 0) {
    error_log("Cleaned up {$deletedCount} expired events");
}
?>