<?php
require_once __DIR__.'/../db.php';
require_once __DIR__.'/../auth.php';
require_once __DIR__.'/../view.php';

$session_count = (int) q("SELECT COUNT(*) AS n FROM session")->fetch_assoc()['n'];

$page_title = 'Panel administratora';
require __DIR__.'/../_layout_head.php';
?>
<style>
    #panelsStayOpen-headingOne .accordion-button {
        background-color: #d1e7dd;
        color: #0f5132;
    }
    #panelsStayOpen-headingOne .accordion-button::after {
        filter: invert(24%) sepia(18%) saturate(846%) hue-rotate(95deg) brightness(96%) contrast(93%);
    }
    .copy-btn { transition: color 0.15s; }
    .copy-btn:hover { color: #9ec5fe !important; }
    .copy-btn:active { color: #0d6efd !important; }
</style>
<div class="container-xxl bd-gutter mt-3 my-md-4 bd-layout">
    <nav class="navbar bg-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <svg class="bi bi-balloon-heart-fill" fill="currentColor" height="16" viewbox="0 0 16 16" width="16" xmlns="http://www.w3.org/2000/svg">
                    <path d="M8.49 10.92C19.412 3.382 11.28-2.387 8 .986 4.719-2.387-3.413 3.382 7.51 10.92l-.234.468a.25.25 0 1 0 .448.224l.04-.08c.009.17.024.315.051.45.068.344.208.622.448 1.102l.013.028c.212.422.182.85.05 1.246-.135.402-.366.751-.534 1.003a.25.25 0 0 0 .416.278l.004-.007c.166-.248.431-.646.588-1.115.16-.479.212-1.051-.076-1.629-.258-.515-.365-.732-.419-1.004a2.376 2.376 0 0 1-.037-.289l.008.017a.25.25 0 1 0 .448-.224l-.235-.468ZM6.726 1.269c-1.167-.61-2.8-.142-3.454 1.135-.237.463-.36 1.08-.202 1.85.055.27.467.197.527-.071.285-1.256 1.177-2.462 2.989-2.528.234-.008.348-.278.14-.386Z" fill-rule="evenodd"/>
                </svg>
                Panel administratora
            </a>
            <a href="?logout" style="float: right;">wyloguj</a>
        </div>
    </nav>
    <div class="accordion" id="accordionPanelsStayOpenExample">
        <div class="accordion-item">
            <h2 class="accordion-header" id="panelsStayOpen-headingTwo">
                <button aria-controls="panelsStayOpen-collapseTwo" aria-expanded="true" class="accordion-button" data-bs-target="#panelsStayOpen-collapseTwo" data-bs-toggle="collapse" type="button">
                    <svg class="bi bi-window-plus" fill="currentColor" height="16" style="margin-right: 8px;" viewbox="0 0 16 16" width="16" xmlns="http://www.w3.org/2000/svg">
                        <path d="M2.5 5a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1ZM4 5a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1Zm2-.5a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0Z"/>
                        <path d="M0 4a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v4a.5.5 0 0 1-1 0V7H1v5a1 1 0 0 0 1 1h5.5a.5.5 0 0 1 0 1H2a2 2 0 0 1-2-2V4Zm1 2h13V4a1 1 0 0 0-1-1H2a1 1 0 0 0-1 1v2Z"/>
                        <path d="M16 12.5a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0Zm-3.5-2a.5.5 0 0 0-.5.5v1h-1a.5.5 0 0 0 0 1h1v1a.5.5 0 0 0 1 0v-1h1a.5.5 0 0 0 0-1h-1v-1a.5.5 0 0 0-.5-.5Z"/>
                    </svg>
                    Dodaj nową sesję
                </button>
            </h2>
            <div aria-labelledby="panelsStayOpen-headingTwo" class="accordion-collapse collapse show" id="panelsStayOpen-collapseTwo">
                <div class="accordion-body">
                    <form action="submit.php" enctype="multipart/form-data" method="post">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label" for="session_id">ID sesji</label>
                            <input class="form-control" id="session_id" name="session_id" readonly type="text" value="(generowane automatycznie)">
                            <div class="form-text">
                                ID jest losowany przy zapisie sesji - zdjęcia z sesji będą dostępne w folderze o tej nazwie.
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="session_name">Nazwa sesji</label>
                            <input class="form-control" id="session_name" name="session_name" type="text" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="session_url">Link do sesji</label>
                            <input class="form-control" id="session_url" name="session_url" readonly type="text" value="<?= h(BASE_URL) ?>/sesja/" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="session_password">Hasło</label>
                            <input class="form-control" id="session_password" name="session_password" value="" type="text">
                            <div class="form-text">Zostaw puste jeśli sesja nie ma być chroniona hasłem.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="session_description">Opis sesji</label>
                            <textarea class="form-control" id="session_description" name="session_description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="session_files">Zdjęcia</label>
                            <input class="form-control" id="session_files" multiple name="session_files[]" type="file">
                        </div>
                        <button class="btn btn-primary" type="submit">Dodaj</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="accordion-item">
            <h2 class="accordion-header" id="panelsStayOpen-headingOne">
                <button aria-controls="panelsStayOpen-collapseOne" aria-expanded="true" class="accordion-button" data-bs-target="#panelsStayOpen-collapseOne" data-bs-toggle="collapse" type="button">
                    <svg class="bi bi-camera2" fill="currentColor" height="16" style="margin-right: 8px;" viewbox="0 0 16 16" width="16" xmlns="http://www.w3.org/2000/svg">
                        <path d="M5 8c0-1.657 2.343-3 4-3V4a4 4 0 0 0-4 4z"/>
                        <path d="M12.318 3h2.015C15.253 3 16 3.746 16 4.667v6.666c0 .92-.746 1.667-1.667 1.667h-2.015A5.97 5.97 0 0 1 9 14a5.972 5.972 0 0 1-3.318-1H1.667C.747 13 0 12.254 0 11.333V4.667C0 3.747.746 3 1.667 3H2a1 1 0 0 1 1-1h1a1 1 0 0 1 1 1h.682A5.97 5.97 0 0 1 9 2c1.227 0 2.367.368 3.318 1zM2 4.5a.5.5 0 1 0-1 0 .5.5 0 0 0 1 0zM14 8A5 5 0 1 0 4 8a5 5 0 0 0 10 0z"/>
                    </svg>
                    Sesje zdjęciowe
                    <span class="badge bg-success ms-2" id="session-count-badge"><?= $session_count ?></span>
                </button>
            </h2>
            <div aria-labelledby="panelsStayOpen-headingOne" class="accordion-collapse collapse show" id="panelsStayOpen-collapseOne">
                <div class="accordion-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Nazwa</th>
                                <th scope="col">Opis</th>
                                <th scope="col">Ilość plików</th>
                                <th scope="col">ID</th>
                                <th scope="col">URL</th>
                                <th scope="col">Hasło</th>
                                <th scope="col">Liczba wybranych zdjęć</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Qualify session.id explicitly: adding any column to `choice` (e.g. a PK)
                            // would otherwise rebind the subquery and silently zero the count.
                            $result = q("SELECT id, name, url, description, file_names, password,
                                           (SELECT count(*) FROM choice WHERE session_id = session.id) AS chosen_images_count
                                         FROM session");
                            if ($result->num_rows === 0) {
                                echo '<tr><td colspan="7">0 results</td></tr>';
                            }
                            while ($row = $result->fetch_assoc()):
                                $url = h($row["url"]);
                                $has_choices = (int) $row["chosen_images_count"] > 0;
                            ?>
                                <tr<?= $has_choices ? ' style="--bs-table-bg: rgba(25,135,84,0.1);"' : '' ?>>
                                    <td>
                                        <?php if ($has_choices): ?>
                                        <svg class="bi bi-check-circle-fill text-success me-1" fill="currentColor" height="14" viewBox="0 0 16 16" width="14" xmlns="http://www.w3.org/2000/svg"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/></svg>
                                        <?php endif; ?>
                                        <?= h($row["name"]) ?>
                                    </td>
                                    <td><?= h($row["description"]) ?></td>
                                    <td><?= 1 + substr_count($row["file_names"] ?? '', "\n") ?></td>
                                    <td>
                                        <span class="d-inline-flex align-items-center gap-1">
                                            <span class="badge text-bg-secondary fw-normal font-monospace"
                                                  data-bs-toggle="tooltip" data-bs-placement="top"
                                                  data-bs-title="<?= h($row['id']) ?>"
                                                  style="cursor:default;">id</span>
                                            <button class="btn btn-sm p-0 border-0 text-muted copy-btn lh-1"
                                                    data-copy="<?= h($row['id']) ?>" type="button"
                                                    aria-label="Kopiuj ID">
                                                <svg fill="currentColor" height="13" viewBox="0 0 16 16" width="13" xmlns="http://www.w3.org/2000/svg"><path d="M4 1.5H3a2 2 0 0 0-2 2V14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V3.5a2 2 0 0 0-2-2h-1v1h1a1 1 0 0 1 1 1V14a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V3.5a1 1 0 0 1 1-1h1z"/><path d="M9.5 1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5zm-3-1A1.5 1.5 0 0 0 5 1.5H3.5a2 2 0 0 0-2 2V14a2 2 0 0 0 2 2h9a2 2 0 0 0 2-2V3.5a2 2 0 0 0-2-2H11A1.5 1.5 0 0 0 9.5 0z"/></svg>
                                            </button>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="d-inline-flex align-items-center gap-1">
                                            <a class="small" href="<?= $url ?>" rel="noopener"
                                               target="_blank"
                                               data-bs-toggle="tooltip" data-bs-placement="top"
                                               data-bs-title="<?= $url ?>">open ↗</a>
                                            <button class="btn btn-sm p-0 border-0 text-muted copy-btn lh-1"
                                                    data-copy="<?= $url ?>" type="button"
                                                    aria-label="Kopiuj URL">
                                                <svg fill="currentColor" height="13" viewBox="0 0 16 16" width="13" xmlns="http://www.w3.org/2000/svg"><path d="M4 1.5H3a2 2 0 0 0-2 2V14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V3.5a2 2 0 0 0-2-2h-1v1h1a1 1 0 0 1 1 1V14a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V3.5a1 1 0 0 1 1-1h1z"/><path d="M9.5 1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5zm-3-1A1.5 1.5 0 0 0 5 1.5H3.5a2 2 0 0 0-2 2V14a2 2 0 0 0 2 2h9a2 2 0 0 0 2-2V3.5a2 2 0 0 0-2-2H11A1.5 1.5 0 0 0 9.5 0z"/></svg>
                                            </button>
                                        </span>
                                    </td>
                                    <td><?= h($row["password"]) ?></td>
                                    <td><?= (int) $row["chosen_images_count"] ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$_base_url = json_encode(BASE_URL, JSON_HEX_TAG);
$page_scripts = <<<JS
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));

document.querySelectorAll('.copy-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(btn.dataset.copy);
            btn.classList.add('text-success');
            setTimeout(() => btn.classList.remove('text-success'), 1500);
        } catch (_) {}
    });
});

const session_name_input = document.getElementById('session_name');
const session_url_input = document.getElementById('session_url');

const replacePolishLetters = (text) => {
    const expression = /[ąćęłńóśźż]/gi;
    const replacements = {
        'ą': 'a', 'ć': 'c', 'ę': 'e', 'ł': 'l', 'ń': 'n', 'ó': 'o', 'ś': 's', 'ź': 'z', 'ż': 'z',
        'Ą': 'A', 'Ć': 'C', 'Ę': 'E', 'Ł': 'L', 'Ń': 'N', 'Ó': 'O', 'Ś': 'S', 'Ź': 'Z', 'Ż': 'Z'
    };
    return text.replace(expression, (letter) => replacements[letter]);
};

session_name_input.addEventListener('input', (e) => {
    session_url_input.value = {$_base_url} + '/sesja/'
        + replacePolishLetters(e.target.value).toLowerCase().trim()
            .replaceAll(/[^0-9a-z ]/gi, '').replaceAll(/\s+/g, '_');
});
JS;
require __DIR__.'/../_layout_foot.php';
?>
