<?php

return [
    'clients_path' => env('RADIUS_CLIENTS_PATH', storage_path('app/radius/clients-selfhosted.conf')),
    'log_path' => env('RADIUS_LOG_PATH', storage_path('logs/freeradius.log')),
    'reload_command' => env('RADIUS_RELOAD_COMMAND', 'systemctl reload freeradius'),
    'restart_command' => env('RADIUS_RESTART_COMMAND', 'systemctl restart freeradius'),
    'status_command' => env('RADIUS_STATUS_COMMAND', 'systemctl is-active freeradius'),
    'server_ip' => env('RADIUS_SERVER_IP', env('WG_SERVER_IP', '127.0.0.1')),
];
