<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/config.php';
require_method('GET');
json_response(['csrfToken' => csrf_token()]);
