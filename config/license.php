<?php

return [
    'self_hosted_enabled' => env('LICENSE_SELF_HOSTED_ENABLED', false),
    'enforce' => env('LICENSE_ENFORCE', false),
    'public_key' => env('LICENSE_PUBLIC_KEY'),
    'path' => env('LICENSE_FILE_PATH', storage_path('app/license/rafen.lic')),
    'machine_id_path' => env('LICENSE_MACHINE_ID_PATH', '/etc/machine-id'),
    'default_grace_days' => (int) env('LICENSE_DEFAULT_GRACE_DAYS', 21),
];
