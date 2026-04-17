<?php
/**
 * Route Configuration for Event Management System
 * All application routes and their handlers
 */

require_once __DIR__ . '/../core/Router.php';
require_once __DIR__ . '/../core/Url.php';

/* ==============================
   DATABASE CONNECTION
================================ */
require_once __DIR__ . '/../init/_dbconnect.php';

/* ==============================
   CONTROLLERS
================================ */
require_once __DIR__ . '/../controllers/departmentController.php';
require_once __DIR__ . '/../controllers/authController.php';
require_once __DIR__ . '/../controllers/usersController.php';
require_once __DIR__ . '/../controllers/profileController.php';

require_once __DIR__ . '/../controllers/checklistController.php';
require_once __DIR__ . '/../controllers/dashboardController.php';

require_once __DIR__ . '/../controllers/inviteController.php';
require_once __DIR__ . '/../controllers/noticeController.php';
require_once __DIR__ . '/../controllers/appreciationController.php';
require_once __DIR__ . '/../controllers/eventReportController.php';
require_once __DIR__ . '/../controllers/upcomingEventController.php';
require_once __DIR__ . '/../controllers/homeController.php';


$router = new Router();

/* ============================================================
   MIDDLEWARE DEFINITIONS
   (Enhanced with new middleware architecture)
============================================================ */

// Include new middleware classes
require_once __DIR__ . '/../Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../Middleware/RoleMiddleware.php';
require_once __DIR__ . '/../Middleware/AccessControlMiddleware.php';
require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';
require_once __DIR__ . '/../Middleware/AuditLogMiddleware.php';
require_once __DIR__ . '/../Middleware/RateLimitMiddleware.php';
require_once __DIR__ . '/../Middleware/UuidValidationMiddleware.php';
require_once __DIR__ . '/../Middleware/DocumentAuthorizationMiddleware.php';

// Enhanced Authentication Middleware
$router->middleware('auth', function() {
    global $pdo;
    $auth = new AuthMiddleware($pdo);
    return $auth->handle([], function($params) { return true; });
});

// Role-based Middleware
$router->middleware('role:principal', function() {
    global $pdo;
    $role = new RoleMiddleware('principal');
    return $role->handle([], function($params) { return true; });
});

$router->middleware('role:hod', function() {
    global $pdo;
    $role = new RoleMiddleware('hod');
    return $role->handle([], function($params) { return true; });
});

$router->middleware('role:coordinator', function() {
    global $pdo;
    $role = new RoleMiddleware('coordinator');
    return $role->handle([], function($params) { return true; });
});

// Access Control Middleware (NEW)
$router->middleware('access', function($config = []) {
    return (new AccessControlMiddleware($config))->handle(
        func_get_args(), 
        fn($p) => true
    );
});

// CSRF Protection Middleware
$router->middleware('csrf', function() {
    global $pdo;
    $csrf = new CsrfMiddleware($pdo);
    return $csrf->handle([], function($params) { return true; });
});

// UUID Validation Middleware
$router->middleware('uuid', function() {
    global $pdo;
    $uuid = new UuidValidationMiddleware($pdo);
    return $uuid->handle([], function($params) { return true; });
});

// Document Authorization Middleware
$router->middleware('documentAuth', function($params) {
    $middleware = new DocumentAuthorizationMiddleware();
    return $middleware->handle($params, function($p) {
        return true;
    });
});
// Rate Limiting Middleware
$router->middleware('rateLimit:60,60,ip', function() {
    $rate = new RateLimitMiddleware(60, 60, 'ip');
    return $rate->handle([], function($params) { return true; });
});

$router->middleware('rateLimit:100,60,user', function() {
    $rate = new RateLimitMiddleware(100, 60, 'user');
    return $rate->handle([], function($params) { return true; });
});

// Audit Logging Middleware
$router->middleware('audit:view', function() {
    global $pdo;
    $audit = new AuditLogMiddleware('view', 'page');
    return $audit->handle([], function($params) { return true; });
});

$router->middleware('audit:create', function() {
    global $pdo;
    $audit = new AuditLogMiddleware('create', 'document');
    return $audit->handle([], function($params) { return true; });
});

$router->middleware('audit:update', function() {
    global $pdo;
    $audit = new AuditLogMiddleware('update', 'document');
    return $audit->handle([], function($params) { return true; });
});

$router->middleware('audit:delete', function() {
    global $pdo;
    $audit = new AuditLogMiddleware('delete', 'document');
    return $audit->handle([], function($params) { return true; });
});

// User must be logged out
$router->middleware('guest', function() {
    if (isset($_SESSION['user_id'])) {
        header('Location: ' . Url::to('dashboard'));
        return false;
    }
    return true;
});


/* ============================================================
PUBLIC HOME ROUTE
============================================================ */

// Home page
// Home / Dashboard (now using controller)
$router->get('/', 'HomeController@index');          // Public access



/* ============================================================
   AUTHENTICATION ROUTES
============================================================ */

$router->get('/login',    'AuthController@showLogin',   ['guest']);
$router->post('/login',   'AuthController@login');

$router->get('/logout',   'AuthController@logout');

$router->get('/signup',   'AuthController@showSignup',  ['guest']);
$router->post('/signup',  'AuthController@signup');

// Forgot Password + OTP + Reset Flow
$router->get('/forgot-password',  'AuthController@showForgotPassword', ['guest']);
$router->post('/send-reset-otp',  'AuthController@sendResetOtp');

$router->get('/verify-otp', 'AuthController@showVerifyOtp');
$router->post('/verify-otp',      'AuthController@verifyOtp');

$router->get('/reset-password',   'AuthController@showResetPassword', ['guest']);
$router->post('/reset-password',  'AuthController@resetPassword');

/* ============================================================
   DASHBOARD & PROFILE ROUTES
============================================================ */

// Dashboard
// Dashboard (now using controller)
$router->get('/dashboard', 'DashboardController@index', ['auth']);




/* ============================================================
   MANAGEMENT MODULES
============================================================ */

$router->get('/manage', function() {
    header("Location: " . Url::to('/manage/departments'));
    exit;
}, ['auth', 'role:principal']);

// Manage Departments (Principal only)
$router->get('/manage/departments', 'DepartmentController@index', ['auth', 'role:principal']);
$router->post('/manage/departments', 'DepartmentController@store', ['auth', 'role:principal']);

// Update route (separate path for clarity)
$router->post('/manage/departments/update', 'DepartmentController@update', ['auth', 'role:principal']);

// Default Header Logo upload/update
$router->post('/manage/default-header', 'DepartmentController@updateDefaultHeader', ['auth', 'role:principal']);

//Manage upcomingEvents
$router->get('/manage/events', 'UpcomingEventController@index', ['auth','role:hod']);
$router->post('/manage/events', 'UpcomingEventController@store', ['auth','role:hod']);
$router->get('/manage/events/delete', 'UpcomingEventController@destroy', ['auth'],'role:hod');






// Manage USERS 


$router->get('/manage/hods', 'UsersController@hodIndex', ['auth', 'role:principal']);
$router->post('/manage/hods', 'UsersController@createUser', ['auth', 'role:principal']);
$router->post('/manage/hods/update', 'UsersController@updateUser', ['auth', 'role:principal']);

$router->get('/manage/coordinators', 'UsersController@coordinatorsIndex', ['auth', 'role:hod']);
$router->post('/manage/coordinators', 'UsersController@createUser', ['auth', 'role:hod']);
$router->post('/manage/coordinators/update', 'UsersController@updateUser', ['auth', 'role:hod']);

$router->get('/manage/profile', 'ProfileController@manageProfile', ['auth']);
$router->post('/manage/profile', 'ProfileController@updateProfile', ['auth']);










// Manage Events
// Event Report form (create/update)
$router->get(
    '/documents/event-report/{checklist_id}',
    'EventReportController@manage',
    ['auth', 'uuid', 'documentAuth', 'audit:view']
);

$router->post(
    '/documents/event-report/{checklist_id}',
    'EventReportController@manage',
    ['auth', 'uuid', 'documentAuth', 'csrf', 'audit:update']
);

/* ============================================================
   CHECKLIST DELETE ROUTE
============================================================ */
$router->post(
    '/documents/checklist/delete',
    'ChecklistController@delete',
    ['auth','csrf']
);

// View saved event report
$router->get(
    '/documents/view/event-report/{checklist_id}',
    'EventReportController@view',
    [ 'uuid', 'audit:view']
);


/* ============================================================
   DOCUMENT CREATION ROUTES
============================================================ */

// Checklist routes
// Show Create Form
$router->get('/documents/checklist', 'ChecklistController@createForm', ['auth', 'audit:view']);

// Show Edit Form (with UUID validation and document access control)
$router->get('/documents/checklist/view/{id}', 'ChecklistController@view', ['auth', 'uuid', 'documentAuth', 'audit:view']);

// Create checklist (with CSRF protection)
$router->post('/documents/checklist', 'ChecklistController@create', ['auth', 'csrf', 'audit:create']);

// Update checklist (with UUID validation, document access control, and CSRF protection)
$router->post(
    '/documents/checklist/update/{id}',
    'ChecklistController@update',
    ['auth', 'uuid', 'documentAuth', 'csrf', 'audit:update']
);
// Notice
// Notice - Form (create/update)
$router->get(
'/documents/notice/{checklist_id}',
'NoticeController@manage',
['auth', 'uuid', 'documentAuth', 'audit:view']
);

$router->post(
'/documents/notice/{checklist_id}',
'NoticeController@manage',
['auth', 'uuid', 'documentAuth', 'csrf', 'audit:create']
);
// Notice - View saved notice (final formatted page)
$router->get('/documents/view/notice/{checklist_id}', 'NoticeController@view',  ['auth', 'uuid', 'documentAuth', 'audit:view']);

// Invitation


// Invitation form (create/update)
$router->get('/documents/invitation/{checklist_id}', 'InviteController@manage', ['auth', 'uuid', 'documentAuth', 'audit:view']);
$router->post('/documents/invitation/{checklist_id}', 'InviteController@manage', ['auth', 'uuid', 'documentAuth', 'csrf', 'audit:create']);
//, 
// Final invitation view (after submit)
$router->get('/documents/view/invitation/{checklist_id}', 'InviteController@view', ['auth', 'uuid', 'documentAuth', 'audit:view']);

// Appreciation
// Appreciation form (create/update per guest with pagination)
$router->get('/documents/appreciation/{checklist_id}', 'AppreciationController@manage', ['auth', 'uuid', 'documentAuth', 'audit:view']);
$router->post('/documents/appreciation/{checklist_id}', 'AppreciationController@manage', ['auth', 'uuid', 'documentAuth', 'csrf', 'audit:create']);

// View saved appreciation letter (final read-only page)
$router->get('/documents/view/appreciation/{checklist_id}', 'AppreciationController@view', ['auth', 'uuid', 'documentAuth', 'audit:view']);


/* ============================================================
   DOCUMENT VIEW & DOWNLOAD
============================================================ */

$router->get('/documents', function() {
    header("Location: " . Url::to('dashboard'));
    exit;
}, ['auth']);



// Generic document view route (catches other types)
$router->get('/documents/view/{type}/{id}', function($type,$id) {
    // Pass path parameter to GET so views can access it
    // Special handling for types that need controllers
    if ($type === 'invitation') {
        $_GET['checklist_id'] = $id;
        // Use controller to fetch data and include view
        require_once __DIR__ . '/../controllers/documents/save_view_invitation.php';
    } elseif ($type === 'appreciation') {
        $_GET['checklist_id'] = $id;
        // Use controller to fetch data and include view
        require_once __DIR__ . '/../controllers/documents/save_view_appreciation.php';
    } elseif ($type === 'notice') {
        $_GET['checklist_id'] = $id;
        // Use controller to fetch data and include view
        require_once __DIR__ . '/../controllers/documents/save_view_notice.php';
    } else {
        $_GET['id'] = $id;
        require_once __DIR__ . '/../views/documents/view_'.$type.'.php';
    }
}, ['auth']);

// Download PDF (with UUID validation and file access control)
$router->get('/documents/download/{id}', function($id) {
    $_GET['id'] = $id;
    require_once __DIR__ . '/../controllers/documents/download_report.php';
}, ['auth', 'uuid', 'fileAccess', 'audit:create']);

// Download Word (with UUID validation and file access control)
$router->get('/documents/download-word/{id}', function($id) {
    $_GET['id'] = $id;
    require_once __DIR__ . '/../controllers/documents/download_word.php';
}, ['auth', 'uuid', 'fileAccess', 'audit:create']);


/* ============================================================
   RETURN ROUTER
============================================================ */

return $router;
