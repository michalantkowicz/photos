<?php
require_once __DIR__.'/../auth.php';
require_once __DIR__.'/../view.php';

$session_id   = $row['id'];
$session_name = $row['name'];
$photoDir     = __DIR__.'/../data/'.$session_id;

$images = is_dir($photoDir) ? array_diff(scandir($photoDir, 0), ['.', '..']) : [];

function renderCard(array $chosenImages, string $sessionId, string $filename): string {
    // The "image path" stored as a choice keeps the original `<sid>/<file>`
    // shape so existing choice rows continue to round-trip.
    $imagePath = $sessionId.'/'.$filename;
    $encoded   = base64_encode($imagePath);
    $src       = h(BASE_URL.'/photo.php?s='.urlencode($sessionId).'&f='.urlencode($filename));
    $basename  = h($filename);
    $isPicked  = in_array($encoded, $chosenImages, true);
    $cardCls   = $isPicked ? 'bg-success' : '';
    $checked   = $isPicked ? 'checked' : '';
    return '
        <div class="card '.$cardCls.'" style="padding:5px;">
            <img class="card-img-top" src="'.$src.'" alt="'.$basename.'" data-filename="'.$basename.'" data-bs-toggle="modal" data-bs-target="#image-modal-xl" onClick="showInModal(this)" style="cursor: zoom-in;">
            <div class="card-body">
                <div style="margin:10px;">
                    <span style="color:#AAA; font-size:8px;">'.$basename.'</span>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="chosenImages[]" value="'.$encoded.'" onchange="onChange(this)" id="id_'.$encoded.'" '.$checked.'>
                    <label class="form-check-label" for="id_'.$encoded.'" style="display: block;">
                        Wybierz zdjęcie
                    </label>
                </div>
            </div>
        </div>
    ';
}

$cards = "<div class='row align-items-center'>";
$i = 0;
foreach ($images as $image) {
    if ($i % 4 === 0 && $i > 0) {
        $cards .= "</div><div class='row align-items-center'>";
    }
    $cards .= "<div class='col-sm-6 col-lg-3 mb-4'>".renderCard($chosenImages, $session_id, $image).'</div>';
    $i++;
}
$cards .= '</div>';
?>

<form method="POST" action="<?= h(BASE_URL) ?>/submit_choices.php">
    <?= csrf_field() ?>
    <input type="hidden" name="session_id" value="<?= h($session_id) ?>">
    <input type="hidden" name="session_name" value="<?= h($session_name) ?>">

    <nav class="navbar navbar-expand-md navbar-dark fixed-top bg-dark">
        <a class="navbar-brand" href="#" style="margin-left:15px;">Sesja: <?= h($session_name) ?></a>
        <button id="chosenImagesButton" class="btn btn-outline-info ms-auto" data-bs-toggle="modal" data-bs-target="#chosen-images-modal-xl" type="button" onClick="updateChosenModal()">
            Liczba wybranych zdjęć: <b id="chosenImagesCount"><?= count($chosenImages) ?></b>
        </button>
        <button id="chooseImagesSubmitButton" class="btn btn-outline-success mt-2 mt-md-0 ms-auto" style="margin-right:15px;" type="submit">Zapisz wybór</button>
    </nav>

    <div class="container-fluid">
        <?= $cards ?>
    </div>
</form>

<script>
    function onChange(element) {
        document.getElementById("chosenImagesCount").innerHTML = document.querySelectorAll('input[name="chosenImages[]"]:checked').length;
        const card = element.closest('.card');
        if (element.checked) card.classList.add('bg-success');
        else card.classList.remove('bg-success');
    }

    function updateChosenModal() {
        const body = document.getElementById("chosen-images-modal-xl-body");
        body.replaceChildren();
        for (const card of document.getElementsByClassName("card bg-success")) {
            const imgEl = card.getElementsByTagName('img')[0];
            const figure = document.createElement('figure');
            figure.className = 'figure';
            const img = document.createElement('img');
            img.src = imgEl.src;
            img.className = 'figure-img img-fluid rounded';
            img.alt = imgEl.dataset.filename || '';
            img.style.marginTop = '20px';
            const caption = document.createElement('figcaption');
            caption.className = 'figure-caption text-end';
            caption.textContent = imgEl.dataset.filename || '';
            figure.append(img, caption);
            body.append(figure);
        }
    }

    function showInModal(element) {
        const filename = element.dataset.filename || '';
        document.getElementById("image-modal-xl-header").textContent = filename;
        const body = document.getElementById("image-modal-xl-body");
        body.replaceChildren();
        const img = document.createElement('img');
        img.src = element.src;
        img.className = 'img-fluid';
        img.alt = filename;
        body.append(img);
    }
</script>
