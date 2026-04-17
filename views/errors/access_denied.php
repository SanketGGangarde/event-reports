<?php require_once __DIR__ . '/../../views/layouts/header.php'; ?>

<div class="container-fluid mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-lg border-0">
                <div class="card-body text-center p-5">
                    <!-- Forbidden Icon -->
                    <div class="mb-4">
                        <i class="fas fa-ban text-danger" style="font-size: 4rem;"></i>
                    </div>
                    
                    <!-- Error Title -->
                    <h1 class="display-4 text-danger mb-3">Access Denied</h1>
                    
                    <!-- Error Message -->
                    <p class="lead text-muted mb-4">
                        You don't have permission to access this resource.
                    </p>
                    
                    <!-- Detailed Message -->
                    <div class="alert alert-info" role="alert">
                        <h6 class="alert-heading">What happened?</h6>
                        <p class="mb-0">
                            Your account doesn't have the necessary permissions to view this document or perform this action. 
                            This could be due to:
                        </p>
                        <ul class="mt-2 mb-0">
                            <li>Your role doesn't have access to this document type</li>
                            <li>The document belongs to a different department</li>
                            <li>The document was created by another user</li>
                        </ul>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <a href="<?= Url::to('/dashboard') ?>" class="btn btn-primary btn-lg w-100">
                                <i class="fas fa-arrow-left"></i> Return to Dashboard
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="<?= Url::to('/documents') ?>" class="btn btn-outline-secondary btn-lg w-100">
                                <i class="fas fa-file-alt"></i> View My Documents
                            </a>
                        </div>
                    </div>
                    
                    <!-- Contact Support -->
                    <div class="mt-4">
                        <p class="text-muted small">
                            If you believe this is an error, please contact your system administrator.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .card {
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    
    .card-body {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #0d6efd 0%, #007bff 100%);
        border: none;
        transition: transform 0.2s;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(13, 110, 253, 0.3);
    }
    
    .alert {
        border-left: 4px solid #0dcaf0;
    }
</style>

<?php require_once __DIR__ . '/../../views/includes/footer.php'; ?>