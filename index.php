<?php
require __DIR__.'/db.php';
require __DIR__.'/auth.php';
require_once __DIR__.'/view.php';

if (!isset($_GET['sesja'])) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

$url = BASE_URL.'/sesja/'.$_GET['sesja'];

$result = q("SELECT id, name, description, password FROM session WHERE url = ?", [$url]);
if ($result->num_rows === 0) {
    die("Unexpected error during loading session. Contact administrator.");
}
$row = $result->fetch_assoc();

$chosenImages = [];
$choiceResult = q("SELECT image FROM choice WHERE session_id = ?", [$row['id']]);
while ($choiceRow = $choiceResult->fetch_assoc()) {
    $chosenImages[] = strval($choiceRow['image']);
}

$page_title = 'Sesja fotograficzna';
$body_style  = 'padding-top: 70px';
require __DIR__.'/_layout_head.php';
?>
<div class="modal modal-xl fade" id="chosen-images-modal-xl" tabindex="-1" aria-labelledby="chosen-images-modal-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="chosen-images-modal-label">Wybrane zdjęcia</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="chosen-images-modal-xl-body" style="text-align: center;"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zamknij</button>
            </div>
        </div>
    </div>
</div>

<div class="modal modal-xl fade" id="image-modal-xl" tabindex="-1" aria-labelledby="image-modal-xl-header" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="image-modal-xl-header" class="modal-title fs-5"></h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="image-modal-xl-body" style="text-align: center;"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zamknij</button>
            </div>
        </div>
    </div>
</div>

<?php
if (empty($row['password']) || gallery_is_unlocked($row['id'])) {
    include __DIR__.'/security/gallery.php';
} else {
    $login_failed = false;
    if (isset($_POST['password'])) {
        if (verify_session_password($_POST['password'], $row['password'])) {
            gallery_unlock($row['id']);
            header('Location: '.$url);
            exit;
        }
        $login_failed = true;
    }
    ?>
    <div class="container">
        <h2>Sesja: <?= h($row['name']) ?></h2>
        <br>
        <?php require __DIR__.'/_login_form.php'; ?>
    </div>
    <?php
}

require __DIR__.'/_layout_foot.php';
?>
