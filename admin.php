<?php
require __DIR__.'/auth.php';
require_once __DIR__.'/view.php';

if (isset($_GET['logout'])) {
    admin_logout();
    header('Location: '.BASE_URL.'/admin');
    exit;
}

if (is_admin()) {
    include __DIR__.'/security/admin.php';
    exit;
}

$login_failed = false;
if (isset($_POST['password'])) {
    check_login_rate_limit();
    if (verify_admin_password($_POST['password'])) {
        admin_login();
        header('Location: '.BASE_URL.'/admin');
        exit;
    }
    record_failed_login();
    $login_failed = true;
}

$page_title = 'Panel administratora';
require __DIR__.'/_layout_head.php';
?>
<div class="container bd-gutter mt-3 my-md-4 bd-layout">
    <?php $form_action = 'admin.php'; require __DIR__.'/_login_form.php'; ?>
</div>
<?php require __DIR__.'/_layout_foot.php'; ?>
