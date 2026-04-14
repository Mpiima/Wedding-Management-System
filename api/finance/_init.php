<?php

declare(strict_types=1);

require_once __DIR__ . '/../erp/_init.php';

function fin_send($payload, int $code = 200): void
{
    erp_send($payload, $code);
}

function fin_json(): array
{
    return erp_json_body();
}
