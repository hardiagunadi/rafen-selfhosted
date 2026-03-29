<?php

function selfHostedRepoPath(string $path = ''): string
{
    $basePath = dirname(__DIR__, 2);

    return $path === '' ? $basePath : $basePath.'/'.$path;
}

function removeDirectory(string $path): void
{
    if (! is_dir($path)) {
        return;
    }

    exec('rm -rf '.escapeshellarg($path));
}

afterEach(function (): void {
    removeDirectory(selfHostedRepoPath('storage/framework/testing/self-hosted-installer-test'));
});

it('ships a self-hosted installer with install deploy and status workflows', function () {
    $scriptPath = selfHostedRepoPath('install-selfhosted.sh');
    $scriptSource = file_get_contents($scriptPath);

    expect($scriptSource)
        ->toContain('MODE="install"')
        ->toContain('ensure_runtime_directories()')
        ->toContain('configure_environment()')
        ->toContain('prepare_sqlite_database()')
        ->toContain('composer_install()')
        ->toContain('npm_build()')
        ->toContain('run_artisan_runtime_setup()')
        ->toContain('user:create-super-admin')
        ->toContain('LICENSE_SELF_HOSTED_ENABLED')
        ->toContain('GENIEACS_NBI_URL')
        ->toContain('GENIEACS_CWMP_STATUS_COMMAND')
        ->toContain('RUN_WIREGUARD_SYSTEM_BOOTSTRAP')
        ->toContain('bootstrap_wireguard_system_service()')
        ->toContain('wireguard:sync')
        ->toContain('show_status()')
        ->not->toContain('install-server-all-in-one.sh');
});

it('passes bash syntax validation', function () {
    $scriptPath = selfHostedRepoPath('install-selfhosted.sh');
    exec('bash -n '.escapeshellarg($scriptPath).' 2>&1', $output, $exitCode);

    if ($exitCode !== 0) {
        test()->fail(implode(PHP_EOL, $output));
    }

    expect($exitCode)->toBe(0);
});

it('prepares a temp workspace during install mode', function () {
    $workspace = selfHostedRepoPath('storage/framework/testing/self-hosted-installer-test/install-workspace');
    mkdir($workspace, 0777, true);
    file_put_contents($workspace.'/artisan', "#!/usr/bin/env bash\nexit 0\n");
    file_put_contents($workspace.'/composer.json', json_encode(['name' => 'rafen/self-hosted-test'], JSON_PRETTY_PRINT));
    file_put_contents($workspace.'/.env.example', <<<'ENV'
APP_NAME="Rafen Self-Hosted"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://localhost
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
CACHE_STORE=file
LICENSE_SELF_HOSTED_ENABLED=true
LICENSE_ENFORCE=true
ENV);
    chmod($workspace.'/artisan', 0755);

    $scriptPath = selfHostedRepoPath('install-selfhosted.sh');
    $command = sprintf(
        'ALLOW_NON_ROOT=1 APP_DIR=%s EXPECTED_APP_DIR=%s DB_CONNECTION=sqlite DB_DATABASE=%s RUN_COMPOSER_INSTALL=0 RUN_NPM_BUILD=0 RUN_MIGRATE=0 RUN_SUPER_ADMIN_SETUP=0 bash %s install --app-url %s',
        escapeshellarg($workspace),
        escapeshellarg($workspace),
        escapeshellarg($workspace.'/database/database.sqlite'),
        escapeshellarg($scriptPath),
        escapeshellarg('https://billing.example.test'),
    );

    exec($command.' 2>&1', $output, $exitCode);

    if ($exitCode !== 0) {
        test()->fail(implode(PHP_EOL, $output));
    }

    clearstatcache();
    $env = file_get_contents($workspace.'/.env');
    preg_match('/^DB_DATABASE=(.+)$/m', $env ?: '', $databaseMatches);
    $databasePath = $databaseMatches[1] ?? '';
    $databasePath = trim($databasePath, "\" \t\n\r\0\x0B");
    preg_match('/^LICENSE_FILE_PATH=(.+)$/m', $env ?: '', $licenseMatches);
    $licensePath = $licenseMatches[1] ?? '';
    $licensePath = trim($licensePath, "\" \t\n\r\0\x0B");

    expect($exitCode)->toBe(0)
        ->and(is_dir($workspace.'/bootstrap/cache'))->toBeTrue()
        ->and(is_dir($workspace.'/storage/app/license'))->toBeTrue()
        ->and(is_dir($workspace.'/storage/app/radius'))->toBeTrue()
        ->and(is_dir($workspace.'/storage/app/wireguard'))->toBeTrue()
        ->and(is_dir($workspace.'/storage/.pm2'))->toBeTrue()
        ->and(is_dir($workspace.'/wa-multi-session'))->toBeTrue()
        ->and(is_dir($workspace.'/storage/framework/cache/data'))->toBeTrue()
        ->and(is_dir($workspace.'/storage/framework/sessions'))->toBeTrue()
        ->and(is_dir($workspace.'/storage/framework/views'))->toBeTrue()
        ->and($databasePath)->not->toBe('')
        ->and(file_exists($databasePath))->toBeTrue()
        ->and($databasePath)->toEndWith('/database/database.sqlite')
        ->and($env)->toContain('APP_URL=https://billing.example.test')
        ->and($licensePath)->toBe($workspace.'/storage/app/license/rafen.lic')
        ->and($env)->toContain('GENIEACS_NBI_URL=http://127.0.0.1:7557')
        ->and($env)->toContain('GENIEACS_UI_URL=http://127.0.0.1:3000')
        ->and($env)->toContain('GENIEACS_LOG_PATH='.$workspace.'/storage/logs/genieacs.log')
        ->and($env)->toContain('WG_CONFIG_PATH='.$workspace.'/storage/app/wireguard/wg0.conf')
        ->and($env)->toContain('WG_SERVER_PRIVATE_KEY_PATH='.$workspace.'/storage/app/wireguard/server_private.key')
        ->and($env)->toContain('WG_SERVER_PUBLIC_KEY_PATH='.$workspace.'/storage/app/wireguard/server_public.key')
        ->and($env)->toContain('WA_MULTI_SESSION_PATH='.$workspace.'/wa-multi-session')
        ->and($env)->toContain('WA_MULTI_SESSION_PM2_HOME='.$workspace.'/storage/.pm2')
        ->and($env)->toContain('WA_MULTI_SESSION_LOG_FILE='.$workspace.'/storage/logs/wa-multi-session-pm2.log')
        ->and($env)->toContain('RADIUS_CLIENTS_PATH='.$workspace.'/storage/app/radius/clients-selfhosted.conf')
        ->and($env)->toContain('RADIUS_LOG_PATH='.$workspace.'/storage/logs/freeradius.log')
        ->and($env)->not->toContain('WG_APPLY_COMMAND=');
});

it('runs deploy commands against the configured toolchain', function () {
    $workspace = selfHostedRepoPath('storage/framework/testing/self-hosted-installer-test/deploy-workspace');
    $binDirectory = selfHostedRepoPath('storage/framework/testing/self-hosted-installer-test/bin');
    $logPath = selfHostedRepoPath('storage/framework/testing/self-hosted-installer-test/command.log');

    mkdir($workspace, 0777, true);
    mkdir($binDirectory, 0777, true);
    file_put_contents($workspace.'/artisan', "#!/usr/bin/env bash\nexit 0\n");
    file_put_contents($workspace.'/composer.json', json_encode(['name' => 'rafen/self-hosted-test'], JSON_PRETTY_PRINT));
    file_put_contents($workspace.'/package.json', json_encode(['private' => true], JSON_PRETTY_PRINT));
    file_put_contents($workspace.'/.env.example', <<<'ENV'
APP_NAME="Rafen Self-Hosted"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://localhost
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
CACHE_STORE=file
LICENSE_SELF_HOSTED_ENABLED=true
LICENSE_ENFORCE=true
ENV);
    chmod($workspace.'/artisan', 0755);

    file_put_contents($binDirectory.'/composer', <<<BASH
#!/usr/bin/env bash
printf 'composer:%s\n' "\$*" >> {$logPath}
exit 0
BASH);
    file_put_contents($binDirectory.'/npm', <<<BASH
#!/usr/bin/env bash
printf 'npm:%s\n' "\$*" >> {$logPath}
exit 0
BASH);
    file_put_contents($binDirectory.'/php', <<<BASH
#!/usr/bin/env bash
printf 'php:%s\n' "\$*" >> {$logPath}
exit 0
BASH);
    chmod($binDirectory.'/composer', 0755);
    chmod($binDirectory.'/npm', 0755);
    chmod($binDirectory.'/php', 0755);

    $scriptPath = selfHostedRepoPath('install-selfhosted.sh');
    $command = sprintf(
        'ALLOW_NON_ROOT=1 APP_DIR=%s EXPECTED_APP_DIR=%s DB_CONNECTION=sqlite DB_DATABASE=%s PHP_BIN=%s COMPOSER_BIN=%s NPM_BIN=%s ADMIN_NAME=%s ADMIN_EMAIL=%s ADMIN_PASSWORD=%s bash %s deploy',
        escapeshellarg($workspace),
        escapeshellarg($workspace),
        escapeshellarg($workspace.'/database/database.sqlite'),
        escapeshellarg($binDirectory.'/php'),
        escapeshellarg($binDirectory.'/composer'),
        escapeshellarg($binDirectory.'/npm'),
        escapeshellarg('Rafen Admin'),
        escapeshellarg('admin@example.com'),
        escapeshellarg('secret-123'),
        escapeshellarg($scriptPath),
    );

    exec($command.' 2>&1', $output, $exitCode);

    if ($exitCode !== 0) {
        test()->fail(implode(PHP_EOL, $output));
    }

    clearstatcache();
    $log = file_get_contents($logPath);

    expect($exitCode)->toBe(0)
        ->and($log)->toContain('composer:install --no-interaction --prefer-dist --optimize-autoloader')
        ->and($log)->toContain('npm:install')
        ->and($log)->toContain('npm:run build')
        ->and($log)->toContain('php:artisan config:clear --ansi')
        ->and($log)->toContain('php:artisan key:generate --force')
        ->and($log)->toContain('php:artisan migrate --force --ansi')
        ->and($log)->toContain('php:artisan wireguard:sync --ansi')
        ->and($log)->toContain('php:artisan user:create-super-admin Rafen Admin admin@example.com --password=secret-123 --ansi');
});

it('can bootstrap optional wireguard system integration with local stubs', function () {
    $workspace = selfHostedRepoPath('storage/framework/testing/self-hosted-installer-test/wireguard-system-workspace');
    $binDirectory = selfHostedRepoPath('storage/framework/testing/self-hosted-installer-test/wireguard-system-bin');
    $logPath = selfHostedRepoPath('storage/framework/testing/self-hosted-installer-test/wireguard-system.log');
    $wireguardSystemDirectory = selfHostedRepoPath('storage/framework/testing/self-hosted-installer-test/wireguard-system');
    $sudoersPath = selfHostedRepoPath('storage/framework/testing/self-hosted-installer-test/rafen-wireguard.sudoers');

    mkdir($workspace, 0777, true);
    mkdir($binDirectory, 0777, true);
    file_put_contents($workspace.'/artisan', "#!/usr/bin/env bash\nexit 0\n");
    file_put_contents($workspace.'/composer.json', json_encode(['name' => 'rafen/self-hosted-test'], JSON_PRETTY_PRINT));
    file_put_contents($workspace.'/.env.example', <<<'ENV'
APP_NAME="Rafen Self-Hosted"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://localhost
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
CACHE_STORE=file
LICENSE_SELF_HOSTED_ENABLED=true
LICENSE_ENFORCE=true
ENV);
    chmod($workspace.'/artisan', 0755);

    file_put_contents($binDirectory.'/php', <<<BASH
#!/usr/bin/env bash
printf 'php:%s\n' "\$*" >> {$logPath}
exit 0
BASH);
    file_put_contents($binDirectory.'/apt-get', <<<BASH
#!/usr/bin/env bash
printf 'apt-get:%s\n' "\$*" >> {$logPath}
exit 0
BASH);
    file_put_contents($binDirectory.'/systemctl', <<<BASH
#!/usr/bin/env bash
printf 'systemctl:%s\n' "\$*" >> {$logPath}
exit 0
BASH);
    file_put_contents($binDirectory.'/visudo', <<<BASH
#!/usr/bin/env bash
printf 'visudo:%s\n' "\$*" >> {$logPath}
exit 0
BASH);
    chmod($binDirectory.'/php', 0755);
    chmod($binDirectory.'/apt-get', 0755);
    chmod($binDirectory.'/systemctl', 0755);
    chmod($binDirectory.'/visudo', 0755);

    $scriptPath = selfHostedRepoPath('install-selfhosted.sh');
    $command = sprintf(
        'ALLOW_NON_ROOT=1 APP_DIR=%s EXPECTED_APP_DIR=%s DB_CONNECTION=sqlite DB_DATABASE=%s PHP_BIN=%s APT_GET_BIN=%s SYSTEMCTL_BIN=%s VISUDO_BIN=%s RUN_COMPOSER_INSTALL=0 RUN_NPM_BUILD=0 RUN_SUPER_ADMIN_SETUP=0 RUN_WIREGUARD_SYSTEM_BOOTSTRAP=1 WG_SYSTEM_DIR=%s WG_SUDOERS_PATH=%s WG_SYNC_HELPER_PATH=%s bash %s install',
        escapeshellarg($workspace),
        escapeshellarg($workspace),
        escapeshellarg($workspace.'/database/database.sqlite'),
        escapeshellarg($binDirectory.'/php'),
        escapeshellarg($binDirectory.'/apt-get'),
        escapeshellarg($binDirectory.'/systemctl'),
        escapeshellarg($binDirectory.'/visudo'),
        escapeshellarg($wireguardSystemDirectory),
        escapeshellarg($sudoersPath),
        escapeshellarg($workspace.'/scripts/wireguard-apply.sh'),
        escapeshellarg($scriptPath),
    );

    exec($command.' 2>&1', $output, $exitCode);

    if ($exitCode !== 0) {
        test()->fail(implode(PHP_EOL, $output));
    }

    $env = file_get_contents($workspace.'/.env');
    $helperScript = file_get_contents($workspace.'/scripts/wireguard-apply.sh');
    $log = file_get_contents($logPath);

    expect($exitCode)->toBe(0)
        ->and($env)->toContain('WG_APPLY_COMMAND='.$workspace.'/scripts/wireguard-apply.sh')
        ->and($helperScript)->toContain('install -m 600')
        ->and($helperScript)->toContain($wireguardSystemDirectory.'/wg0.conf')
        ->and($helperScript)->toContain($binDirectory.'/systemctl')
        ->and($log)->toContain('apt-get:update')
        ->and($log)->toContain('apt-get:install -y wireguard-tools')
        ->and($log)->toContain('php:artisan wireguard:sync --ansi')
        ->and(file_exists($sudoersPath))->toBeFalse();
});
