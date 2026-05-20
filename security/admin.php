<?php
require_once __DIR__.'/../db.php';
require_once __DIR__.'/../auth.php';
require_once __DIR__.'/../view.php';

$session_count = (int) q("SELECT COUNT(*) AS n FROM session")->fetch_assoc()['n'];

$page_title = 'Panel administratora';
$page_css = [
    'https://cdn.jsdelivr.net/npm/datatables.net-bs5@2.2.2/css/dataTables.bootstrap5.min.css',
    'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css',
];
$extra_scripts = [
    'https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js',
    'https://cdn.jsdelivr.net/npm/datatables.net@2.2.2/js/dataTables.min.js',
    'https://cdn.jsdelivr.net/npm/datatables.net-bs5@2.2.2/js/dataTables.bootstrap5.min.js',
    'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js',
];
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
    #sessions-table thead th { vertical-align: middle; text-align: center; }
    #sessions-table th:nth-child(1),
    #sessions-table td:nth-child(1) { min-width: 180px; }
    #dt-date-range { font-size: 0.65rem; }
    #sessions-table td:nth-child(1),
    #sessions-table td:nth-child(4),
    #sessions-table td:nth-child(5),
    #sessions-table td:nth-child(7),
    #sessions-table td:nth-child(8) { text-align: center; }
    #sessions-table th:nth-child(4),
    #sessions-table td:nth-child(4),
    #sessions-table th:nth-child(5),
    #sessions-table td:nth-child(5),
    #sessions-table th:nth-child(6),
    #sessions-table td:nth-child(6) { min-width: 140px; }
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
                        <div class="mb-3" hidden>
                            <label class="form-label" for="session_id">ID sesji</label>
                            <input class="form-control" id="session_id" name="session_id" readonly type="text" value="(generowane automatycznie)">
                            <div class="form-text">
                                ID jest losowany przy zapisie sesji - zdjęcia z sesji będą dostępne w folderze o tej nazwie.
                            </div>
                        </div>
                        <div class="row gy-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="session_name">Nazwa sesji</label>
                                <input class="form-control" id="session_name" name="session_name" type="text" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label invisible d-none d-md-block" aria-hidden="true">&nbsp;</label>
                                <input id="session_url" name="session_url" type="hidden" value="<?= h(BASE_URL) ?>/sesja/">
                                <div class="d-flex align-items-center gap-2 flex-nowrap">
                                    <label class="form-label mb-0 text-nowrap">Link do sesji</label>
                                    <span id="session_url_text" class="bg-light border rounded font-monospace small text-truncate px-2 py-1" style="min-width:0;"><?= h(BASE_URL) ?>/sesja/</span>
                                    <button class="btn btn-sm p-0 border-0 text-muted copy-btn lh-1 flex-shrink-0" id="session_url_copy" data-copy="<?= h(BASE_URL) ?>/sesja/" type="button" aria-label="Kopiuj URL">
                                        <svg fill="currentColor" height="13" viewBox="0 0 16 16" width="13" xmlns="http://www.w3.org/2000/svg"><path d="M4 1.5H3a2 2 0 0 0-2 2V14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V3.5a2 2 0 0 0-2-2h-1v1h1a1 1 0 0 1 1 1V14a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V3.5a1 1 0 0 1 1-1h1z"/><path d="M9.5 1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5zm-3-1A1.5 1.5 0 0 0 5 1.5H3.5a2 2 0 0 0-2 2V14a2 2 0 0 0 2 2h9a2 2 0 0 0 2-2V3.5a2 2 0 0 0-2-2H11A1.5 1.5 0 0 0 9.5 0z"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="session_password">Hasło <span class="text-muted">(opcjonalnie)</span></label>
                            <input class="form-control" id="session_password" name="session_password" value="" type="text">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="session_email">E-mail klienta <span class="text-muted">(opcjonalnie)</span></label>
                            <input class="form-control" id="session_email" name="session_email" type="email">
                        </div>
                        <div class="mb-3">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <label class="form-label mb-0" for="session_description">Opis sesji <span class="text-muted">(opcjonalnie)</span></label>
                                <div class="form-check mb-0 ms-3">
                                    <input class="form-check-input" id="remember_description" type="checkbox">
                                    <label class="form-check-label small text-muted" for="remember_description">zapamiętaj</label>
                                </div>
                            </div>
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
                    <table class="table" id="sessions-table">
                        <thead>
                            <tr>
                                <th scope="col">Data</th>
                                <th scope="col">Nazwa</th>
                                <th scope="col">Opis</th>
                                <th scope="col">ID</th>
                                <th scope="col">URL</th>
                                <th scope="col">Hasło</th>
                                <th scope="col">Ilość plików</th>
                                <th scope="col">Wybrano</th>
                                <th scope="col"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Qualify session.id explicitly: adding any column to `choice` (e.g. a PK)
                            // would otherwise rebind the subquery and silently zero the count.
                            // Templates for the "share" mailto link. A missing body template
                            // -> no share links (buttons fall back to the disabled state); a
                            // missing topic template just omits the subject.
                            $mail_template = @file_get_contents(__DIR__.'/../mail_template');
                            if (!is_string($mail_template)) { $mail_template = ''; }
                            $mail_topic_template = @file_get_contents(__DIR__.'/../mail_topic_template');
                            if (!is_string($mail_topic_template)) { $mail_topic_template = ''; }

                            $result = q("SELECT id, name, url, description, file_names, password, email, created_at,
                                           (SELECT count(*) FROM choice WHERE session_id = session.id) AS chosen_images_count
                                         FROM session");
                            if ($result->num_rows === 0) {
                                echo '<tr><td colspan="9">0 results</td></tr>';
                            }
                            while ($row = $result->fetch_assoc()):
                                $url = h($row["url"]);
                                $has_choices = (int) $row["chosen_images_count"] > 0;

                                // "Share" mailto: the client e-mail as recipient, mail_template
                                // as the body with {url} / {password} filled in. Empty when the
                                // session has no e-mail or the template file is missing.
                                $share_email  = (string) ($row['email'] ?? '');
                                $share_mailto = '';
                                if ($share_email !== '' && $mail_template !== '') {
                                    $tokens = [
                                        '{url}'      => (string) $row['url'],
                                        '{password}' => (string) ($row['password'] ?? ''),
                                    ];
                                    $share_body   = strtr($mail_template, $tokens);
                                    $share_topic  = trim(strtr($mail_topic_template, $tokens));
                                    $share_mailto = 'mailto:'.rawurlencode($share_email).'?';
                                    if ($share_topic !== '') {
                                        $share_mailto .= 'subject='.rawurlencode($share_topic).'&';
                                    }
                                    $share_mailto .= 'body='.rawurlencode($share_body);
                                }
                            ?>
                                <tr<?= $has_choices ? ' style="--bs-table-bg: rgba(25,135,84,0.1);"' : '' ?>>
                                    <td class="text-nowrap text-muted small"><?= h(substr($row['created_at'] ?? '', 0, 16)) ?></td>
                                    <td>
                                        <?php if ($has_choices): ?>
                                        <svg class="bi bi-check-circle-fill text-success me-1" fill="currentColor" height="14" viewBox="0 0 16 16" width="14" xmlns="http://www.w3.org/2000/svg"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/></svg>
                                        <?php endif; ?>
                                        <?= h($row["name"]) ?>
                                    </td>
                                    <td><?= h($row["description"]) ?></td>
                                    <td>
                                        <span class="visually-hidden"><?= h($row['id']) ?></span>
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
                                        <span class="visually-hidden"><?= $url ?></span>
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
                                    <td><?= 1 + substr_count($row["file_names"] ?? '', "\n") ?></td>
                                    <td><?= (int) $row["chosen_images_count"] ?></td>
                                    <td class="text-nowrap">
                                        <?php if ($share_mailto !== ''): ?>
                                        <a class="btn btn-sm btn-outline-primary me-1"
                                           href="<?= h($share_mailto) ?>"
                                           data-bs-toggle="tooltip" data-bs-placement="top"
                                           data-bs-title="Wyślij e-mail do: <?= h($share_email) ?>"
                                           aria-label="Udostępnij sesję e-mailem">
                                            <svg fill="currentColor" height="13" viewBox="0 0 16 16" width="13" xmlns="http://www.w3.org/2000/svg"><path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1zm13 2.383-4.708 2.825L15 11.105zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741M1 11.105l4.708-2.897L1 5.383z"/></svg>
                                        </a>
                                        <?php else: ?>
                                        <button class="btn btn-sm btn-outline-secondary me-1" type="button" disabled
                                                title="Brak e-maila klienta dla tej sesji">
                                            <svg fill="currentColor" height="13" viewBox="0 0 16 16" width="13" xmlns="http://www.w3.org/2000/svg"><path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1zm13 2.383-4.708 2.825L15 11.105zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741M1 11.105l4.708-2.897L1 5.383z"/></svg>
                                        </button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-outline-danger delete-session-btn"
                                                data-session-id="<?= h($row['id']) ?>"
                                                data-session-name="<?= h($row['name']) ?>"
                                                type="button" aria-label="Usuń sesję">
                                            <svg fill="currentColor" height="13" viewBox="0 0 16 16" width="13" xmlns="http://www.w3.org/2000/svg"><path d="M6.5 1h3a.5.5 0 0 1 .5.5v1H6v-1a.5.5 0 0 1 .5-.5M11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3A1.5 1.5 0 0 0 5 1.5v1H2.506a.58.58 0 0 0-.01 0H1.5a.5.5 0 0 0 0 1h.538l.853 10.66A2 2 0 0 0 4.885 16h6.23a2 2 0 0 0 1.994-1.84l.853-10.66h.538a.5.5 0 0 0 0-1h-.995a.59.59 0 0 0-.01 0zm1.958 1-.846 10.58a1 1 0 0 1-.997.92h-6.23a1 1 0 0 1-.997-.92L3.042 3.5zM5.509 5.47a.5.5 0 0 1 .528.47l.5 8.5a.5.5 0 0 1-.998.06L5 5.97a.5.5 0 0 1 .47-.53zm5.058 0a.5.5 0 0 1 .47.53l-.5 8.5a.5.5 0 1 1-.998-.06l.5-8.5a.5.5 0 0 1 .528-.47M8 4.5a.5.5 0 0 1 .5.5v8.5a.5.5 0 0 1-1 0V5a.5.5 0 0 1 .5-.5"/></svg>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div aria-hidden="true" aria-labelledby="deleteModalLabel" class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Usuń sesję</h5>
                <button aria-label="Zamknij" class="btn-close" data-bs-dismiss="modal" type="button"></button>
            </div>
            <div class="modal-body">
                <p>Czy na pewno chcesz usunąć sesję <strong id="deleteModalSessionName"></strong>?</p>
                <p class="text-danger small mb-0">Operacja usunie wszystkie zdjęcia i wybory klientów. Nie można jej cofnąć.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Anuluj</button>
                <form action="delete_session.php" method="post">
                    <?= csrf_field() ?>
                    <input id="deleteSessionId" name="session_id" type="hidden">
                    <button class="btn btn-danger" type="submit">Usuń</button>
                </form>
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
const session_url_text  = document.getElementById('session_url_text');
const session_url_copy  = document.getElementById('session_url_copy');

const replacePolishLetters = (text) => {
    const expression = /[ąćęłńóśźż]/gi;
    const replacements = {
        'ą': 'a', 'ć': 'c', 'ę': 'e', 'ł': 'l', 'ń': 'n', 'ó': 'o', 'ś': 's', 'ź': 'z', 'ż': 'z',
        'Ą': 'A', 'Ć': 'C', 'Ę': 'E', 'Ł': 'L', 'Ń': 'N', 'Ó': 'O', 'Ś': 'S', 'Ź': 'Z', 'Ż': 'Z'
    };
    return text.replace(expression, (letter) => replacements[letter]);
};

session_name_input.addEventListener('input', (e) => {
    const url = {$_base_url} + '/sesja/'
        + replacePolishLetters(e.target.value).toLowerCase().trim()
            .replaceAll(/[^0-9a-z ]/gi, '').replaceAll(/\s+/g, '_');
    session_url_input.value = url;
    session_url_text.textContent = url;
    session_url_copy.dataset.copy = url;
});

document.querySelectorAll('.delete-session-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('deleteModalSessionName').textContent = btn.dataset.sessionName;
        document.getElementById('deleteSessionId').value = btn.dataset.sessionId;
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    });
});

(function() {
    const LS_REMEMBER = 'admin_remember_desc';
    const LS_VALUE    = 'admin_desc_value';
    const chk  = document.getElementById('remember_description');
    const area = document.getElementById('session_description');

    // Restore state on load
    if (localStorage.getItem(LS_REMEMBER) === '1') {
        chk.checked = true;
        area.value = localStorage.getItem(LS_VALUE) || '';
    }

    // Keep stored value in sync while typing (only when remembered)
    area.addEventListener('input', () => {
        if (chk.checked) localStorage.setItem(LS_VALUE, area.value);
    });

    // Toggle persistence
    chk.addEventListener('change', () => {
        if (chk.checked) {
            localStorage.setItem(LS_REMEMBER, '1');
            localStorage.setItem(LS_VALUE, area.value);
        } else {
            localStorage.removeItem(LS_REMEMBER);
            localStorage.removeItem(LS_VALUE);
        }
    });

    // On submit: if remembered, update stored value; if not, nothing to do
    area.closest('form').addEventListener('submit', () => {
        if (chk.checked) localStorage.setItem(LS_VALUE, area.value);
    });
})();

let dtFp = null;

const fmtYMD = d => d.getFullYear() + '-'
    + String(d.getMonth() + 1).padStart(2, '0') + '-'
    + String(d.getDate()).padStart(2, '0');

DataTable.ext.search.push(function(settings, data) {
    if (settings.nTable.id !== 'sessions-table') return true;
    if (!dtFp?.selectedDates.length) return true;
    const min = fmtYMD(dtFp.selectedDates[0]);
    const max = dtFp.selectedDates[1] ? fmtYMD(dtFp.selectedDates[1]) : '';
    const d = (data[0] || '').substring(0, 10);
    if (min && d < min) return false;
    if (max && d > max) return false;
    return true;
});

const dt = new DataTable('#sessions-table', {
    paging: false,
    layout: { topStart: null, topEnd: null, bottomStart: null, bottomEnd: null },
    order: [[0, 'desc']],
    columnDefs: [{ targets: 8, orderable: false, searchable: false }],
    initComplete: function() {
        const api = this.api();
        const saved = JSON.parse(sessionStorage.getItem('admin_dt_filters') || 'null');
        sessionStorage.removeItem('admin_dt_filters');
        api.columns().every(function(i) {
            if (i >= 6) return; // no filter for Ilość plików, Wybrano, delete
            const col = this;
            const th = col.header();
            if (i === 0) {
                const wrapper = document.createElement('div');
                wrapper.className = 'mt-1';
                wrapper.style.position = 'relative';

                const inp = document.createElement('input');
                inp.type = 'text';
                inp.id = 'dt-date-range';
                inp.className = 'form-control form-control-sm';
                inp.placeholder = 'Od – do…';
                inp.style.paddingRight = '1.4rem';
                inp.addEventListener('click', e => e.stopPropagation());

                const clearBtn = document.createElement('button');
                clearBtn.type = 'button';
                clearBtn.textContent = '×';
                clearBtn.style.cssText = 'position:absolute;right:4px;top:50%;transform:translateY(-50%);border:none;background:none;padding:0;line-height:1;color:#6c757d;font-size:0.9rem;cursor:pointer;';
                clearBtn.addEventListener('click', e => {
                    e.stopPropagation();
                    dtFp.clear();
                    api.draw();
                });

                wrapper.appendChild(inp);
                wrapper.appendChild(clearBtn);
                th.appendChild(wrapper);

                dtFp = flatpickr(inp, {
                    mode: 'range',
                    dateFormat: 'y/m/d',
                    locale: { rangeSeparator: ' - ' },
                    onChange: () => api.draw(),
                });
                if (saved?.dates?.length) {
                    dtFp.setDate(saved.dates.map(s => new Date(s)), false);
                }
            } else {
                const inp = document.createElement('input');
                inp.type = 'search';
                inp.className = 'form-control form-control-sm mt-1';
                inp.placeholder = 'Szukaj…';
                const savedVal = saved?.cols?.[i - 1] || '';
                if (savedVal) { inp.value = savedVal; col.search(savedVal); }
                inp.addEventListener('input', function() {
                    if (col.search() !== this.value) col.search(this.value).draw();
                });
                inp.addEventListener('click', e => e.stopPropagation());
                th.appendChild(inp);
            }
        });
        if (saved) api.draw();
    }
});

document.querySelector('#deleteModal form').addEventListener('submit', () => {
    const filters = {
        dates: dtFp?.selectedDates.map(d => d.toISOString()) ?? [],
        cols: [1,2,3,4,5].map(i => dt.column(i).search() || ''),
    };
    sessionStorage.setItem('admin_dt_filters', JSON.stringify(filters));
});
JS;
require __DIR__.'/../_layout_foot.php';
?>
