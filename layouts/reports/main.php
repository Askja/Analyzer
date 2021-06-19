<?php

use config\ApplicationKeys;
use engine\Render; ?>
<main class="<?= ApplicationKeys::DEFAULT_PREFIX ?>-app">
    <?= Render::out('layouts/reports/aside.php', []) ?>

    <?= Render::out('layouts/reports/reports.php', []) ?>

    <?= Render::out('layouts/reports/groups.php', []) ?>
</main>
<?= Render::out('layouts/reports/modal_settings.php', []) ?>
