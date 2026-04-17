<?php
// 404 Not Found Error Page
?>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 text-center">
            <div class="error-page">
                <h1 class="display-1 text-danger">404</h1>
                <h2 class="mb-4">Page Not Found</h2>
                <p class="lead">The page you are looking for doesn't exist.</p>
                <a href="<?= Url::to('home') ?>" class="btn btn-primary">Go Home</a>
            </div>
        </div>
    </div>
</div>

<style>
.error-page {
    padding: 50px 0;
}
.error-page h1 {
    font-size: 100px;
    line-height: 1;
    margin-bottom: 0;
}
</style>