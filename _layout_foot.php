<script crossorigin="anonymous" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<?php foreach ($extra_scripts ?? [] as $src): ?>
<script src="<?= h($src) ?>"></script>
<?php endforeach; ?>
<?php if (!empty($page_scripts)): ?>
<script>
<?= $page_scripts ?>
</script>
<?php endif; ?>
</body>
</html>
