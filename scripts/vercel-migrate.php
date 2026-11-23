<?php

if (!getenv('DATABASE_URL')) {
    fwrite(STDOUT, "DATABASE_URL is not set, so this build will not migrate.\n");
    exit(0);
}

passthru(PHP_BINARY.' artisan migrate --force', $code);
exit($code);
