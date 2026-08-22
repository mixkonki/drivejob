<?php

use Drivejob\Core\Session;

foreach (['success_message' => 'ok', 'error_message' => 'err'] as $key => $kind) {
    if (Session::has($key)) {
        printf(
            '<div class="app-alert app-alert-%s">%s</div>',
            $kind,
            htmlspecialchars((string) Session::get($key), ENT_QUOTES, 'UTF-8')
        );
        Session::remove($key);
    }
}
