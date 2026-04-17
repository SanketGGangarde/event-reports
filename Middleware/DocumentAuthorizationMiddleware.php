<?php
require_once __DIR__ . '/MiddlewareInterface.php';

class DocumentAuthorizationMiddleware implements MiddlewareInterface
{
    private $pdo;

    public function __construct()
    {
        global $pdo;
        $this->pdo = $pdo;
    }

    public function handle($params, $next)
    {
        // User must be logged in
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            require __DIR__ . '/../views/errors/404.php';
            exit;
        }

        // Get logged user
        $user = $this->getUser($_SESSION['user_id']);

        if (!$user) {
            http_response_code(401);
            exit;
        }

        // Get checklist id from route or query
        $checklist_id =
            $params['checklist_id']
            ?? $params['id']
            ?? $_GET['checklist_id']
            ?? null;

        if (!$checklist_id) {
            http_response_code(404);
            exit;
        }

        // Fetch checklist
        $checklist = $this->getChecklist($checklist_id);

        if (!$checklist) {
            http_response_code(404);
            exit;
        }

        // Authorization check
        if (!$this->authorize($user, $checklist)) {
            http_response_code(403);
            require __DIR__ . '/../views/errors/access_denied.php';
            exit;
        }

        return $next($params);
    }

    /**
     * Fetch logged user
     */
    private function getUser($id)
    {
        $stmt = $this->pdo->prepare("
            SELECT id, role, department_id
            FROM users
            WHERE id = ?
        ");

        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Fetch checklist document
     */
    private function getChecklist($id)
    {
        $stmt = $this->pdo->prepare("
            SELECT id, created_by, department
            FROM checklists
            WHERE id = ?
        ");

        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Authorization logic
     */
    private function authorize($user, $checklist)
    {
        $user_id = $user['id'];
        $user_role = $user['role'];
        $user_department = $user['department_id'];

        $deptArray = json_decode($checklist['department'] ?? '[]', true);

        // 1️⃣ Coordinator who created checklist
        if ($user_role === 'coordinator' && $checklist['created_by'] === $user_id) {
            return true;
        }

        // 2️⃣ Principal can access everything
        if ($user_role === 'principal') {
            return true;
        }

        // 3️⃣ HOD of department included in checklist
        if (
            $user_role === 'hod' &&
            is_array($deptArray) &&
            in_array($user_department, $deptArray)
        ) {
            return true;
        }

        return false;
    }
}