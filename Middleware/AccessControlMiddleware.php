<?php
/**
 * Unified Access Control Middleware
 * Handles document-level, file-level, department-level and role-based authorization
 */
require_once __DIR__ . '/MiddlewareInterface.php';

class AccessControlMiddleware implements MiddlewareInterface
{
    private $pdo;
    private $user;
    private $config;

    // Allowed config keys and their meaning
    private static $knownScopes = [
        'document', 'file', 'department', 'generic_document'
    ];

    public function __construct(array $config = [])
    {
        global $pdo;
        $this->pdo = $pdo;

        $this->config = array_merge([
            'scope'       => 'document',           // document | file | department | generic_document
            'types'       => [],                   // ['checklist', 'invitation', ...] or empty = all
            'operations'  => ['view'],             // view, create, update, download, ...
            'allow_principal_all' => true,
        ], $config);

        $this->user = $this->getCurrentUser();
    }

    public function handle($params, $next)
    {
        if (!$this->user) {
            $this->deny('authentication_required');
        }

        $scope = $this->config['scope'];

        // Quick bypass for principal if allowed
        if ($this->config['allow_principal_all'] && $this->user['role'] === 'principal') {
            return $next($params);
        }

        $authorized = false;

        switch ($scope) {
            case 'document':
            case 'generic_document':
                $authorized = $this->checkDocumentAccess($params);
                break;

            case 'file':
                $authorized = $this->checkFileAccess($params);
                break;

            case 'department':
                $authorized = $this->checkDepartmentAccess($params);
                break;

            default:
                $this->deny('invalid_scope_configuration');
        }

        if (!$authorized) {
            $this->deny('unauthorized_access');
        }

        $this->logAccess('success');
        return $next($params);
    }

    // ────────────────────────────────────────────────
    //   Core authorization methods
    // ────────────────────────────────────────────────

    private function checkDocumentAccess($params): bool
    {
        $id = $this->extractId($params, ['checklist_id', 'id']);
        if (!$id) return false;

        $doc = $this->getDocumentOwnerInfo($id);
        if (!$doc) return false;

        $role = $this->user['role'];

        if ($role === 'coordinator') {
            return $doc['created_by'] === $this->user['id'];
        }

        if ($role === 'hod') {
            $myDept = $this->user['department_id'];
            if (!$myDept) return false;

            if ($doc['userdept_id'] === $myDept) return true;

            $depts = json_decode($doc['department'] ?? '[]', true);
            return is_array($depts) && in_array($myDept, $depts);
        }

        // principal already bypassed above unless disabled
        return false;
    }

    private function checkFileAccess($params): bool
    {
        $id = $this->extractId($params, ['id']);
        if (!$id) return false;

        $role = $this->user['role'];

        if ($role === 'principal') return $this->fileExists($id);

        if ($role === 'hod') {
            return $this->fileBelongsToDepartment($id, $this->user['department_id']);
        }

        if ($role === 'coordinator') {
            return $this->fileBelongsToUser($id, $this->user['id']);
        }

        return false;
    }

    private function checkDepartmentAccess($params): bool
    {
        if ($this->user['role'] !== 'hod') return false;

        $requestedDept = $this->extractId($params, ['department_id']);
        if ($requestedDept) {
            return $requestedDept === $this->user['department_id'];
        }

        // no specific dept → allow if user is HOD
        return $this->user['department_id'] !== null;
    }

    // ────────────────────────────────────────────────
    //   Helpers
    // ────────────────────────────────────────────────

    private function getCurrentUser(): ?array
    {
        if (!isset($_SESSION['user_id'])) return null;

        $stmt = $this->pdo->prepare("SELECT id, username, role, department_id FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function extractId($params, array $possibleKeys): ?string
    {
        foreach ($possibleKeys as $key) {
            if (isset($params[$key]))     return $params[$key];
            if (isset($_GET[$key]))        return $_GET[$key];
            if (isset($_POST[$key]))       return $_POST[$key];
            if (isset($_REQUEST[$key]))    return $_REQUEST[$key];
        }
        return null;
    }

    private function getDocumentOwnerInfo(string $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                c.id,
                c.created_by,
                c.userdept_id,
                c.department,
                u.role AS created_by_role
            FROM checklists c
            LEFT JOIN users u ON u.id = c.created_by
            WHERE c.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function fileExists(string $id): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT 1 FROM checklists WHERE id = ? AND application_letter IS NOT NULL
            UNION
            SELECT 1 FROM event_report WHERE checklist_id = ? AND photos IS NOT NULL
            LIMIT 1
        ");
        $stmt->execute([$id, $id]);
        return $stmt->fetch() !== false;
    }

    private function fileBelongsToDepartment(string $id, ?string $deptId): bool
    {
        if (!$deptId) return false;
        $stmt = $this->pdo->prepare("
            SELECT 1 FROM checklists 
            WHERE id = ? AND userdept_id = ? AND application_letter IS NOT NULL
            UNION
            SELECT 1 FROM event_report er
            JOIN checklists c ON er.checklist_id = c.id
            WHERE er.checklist_id = ? AND c.userdept_id = ? AND er.photos IS NOT NULL
            LIMIT 1
        ");
        $stmt->execute([$id, $deptId, $id, $deptId]);
        return $stmt->fetch() !== false;
    }

    private function fileBelongsToUser(string $id, string $userId): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT 1 FROM checklists 
            WHERE id = ? AND created_by = ? AND application_letter IS NOT NULL
            UNION
            SELECT 1 FROM event_report er
            JOIN checklists c ON er.checklist_id = c.id
            WHERE er.checklist_id = ? AND c.created_by = ? AND er.photos IS NOT NULL
            LIMIT 1
        ");
        $stmt->execute([$id, $userId, $id, $userId]);
        return $stmt->fetch() !== false;
    }

    private function deny(string $reason)
    {
        $this->logAccess("denied: $reason");

        $code = match($reason) {
            'authentication_required' => 401,
            'unauthorized_access'     => 403,
            default                   => 404,
        };

        http_response_code($code);

        if ($reason === 'authentication_required') {
            require __DIR__ . '/../views/errors/404.php'; // or redirect to login
        } else {
            require __DIR__ . '/../views/errors/access_denied.php';
        }
        exit;
    }

    private function logAccess(string $status)
    {
        $msg = sprintf(
            "AccessControl [%s] user=%s role=%s scope=%s status=%s ip=%s uri=%s",
            date('Y-m-d H:i:s'),
            $this->user['username'] ?? 'anonymous',
            $this->user['role']     ?? 'none',
            $this->config['scope']  ?? 'unknown',
            $status,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['REQUEST_URI'] ?? 'unknown'
        );
        error_log($msg);
    }
}
