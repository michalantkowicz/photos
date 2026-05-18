<?php // Variables: $form_action (string, defaults to POST-to-self), $login_failed (bool) ?>
<form method="POST"<?= !empty($form_action) ? ' action="'.h($form_action).'"' : '' ?>>
    <div class="input-group mb-3<?= $login_failed ? ' has-validation' : '' ?>">
        <input type="password" class="form-control<?= $login_failed ? ' is-invalid' : '' ?>" placeholder="Hasło" aria-label="password" name="password" aria-describedby="login-btn">
        <button class="btn <?= $login_failed ? 'btn-outline-secondary' : 'btn-primary' ?>" type="submit" id="login-btn">OK</button>
        <?php if ($login_failed): ?>
            <div class="invalid-feedback">Podaj poprawne hasło</div>
        <?php endif; ?>
    </div>
</form>
