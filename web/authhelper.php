<?php

if (!function_exists('talosNormalizeEnvironmentList')) {
    function talosNormalizeEnvironmentList($environmentValue): array
    {
        if (is_array($environmentValue)) {
            $list = array_values(array_filter(array_map('strval', $environmentValue), static function (string $item): bool {
                return trim($item) !== '';
            }));
            return array_values(array_unique($list));
        }

        $single = trim((string) $environmentValue);
        return $single === '' ? [] : [$single];
    }
}

if (!function_exists('talosEnsureOdataLoaded')) {
    /**
     * Laadt odata.php indien nodig en checkt of Mímir actief is ($mimirApi gezet).
     */
    function talosEnsureOdataLoaded(): void
    {
        if (function_exists('odata_mimir_enabled')) {
            return;
        }
        $odataPath = __DIR__ . '/odata.php';
        if (is_file($odataPath)) {
            require_once $odataPath;
        }
    }
}

if (!function_exists('talosMimirEnabled')) {
    function talosMimirEnabled(): bool
    {
        talosEnsureOdataLoaded();
        return function_exists('odata_mimir_enabled') && odata_mimir_enabled();
    }
}

if (!function_exists('getActiveEnvironments')) {
    function getActiveEnvironments(): array
    {
        global $environment, $auth_list;

        $configured = talosNormalizeEnvironmentList($environment ?? []);
        if (!talosMimirEnabled()) {
            if ($configured !== []) {
                return $configured;
            }
            return ['kvtmdlive_aad'];
        }

        // Mímir beheert environments. $environment telt alleen mee als het ook in $auth_list staat.
        // Zonder $auth_list (ook als $environment nog gezet is) komen ze uit companies.php.
        $known = is_array($auth_list ?? null) ? array_map('strval', array_keys($auth_list)) : [];
        if ($known === []) {
            $configured = [];
        } elseif ($configured !== []) {
            $knownMap = array_fill_keys($known, true);
            $configured = array_values(array_filter($configured, static function (string $item) use ($knownMap): bool {
                return isset($knownMap[$item]);
            }));
        }

        if ($configured === [] && $known !== []) {
            return [(string) $known[0]];
        }

        if ($configured !== []) {
            return $configured;
        }

        // Geen lokale BC-config: bij Mímir environments afleiden uit companies.php.
        $cached = $GLOBALS['talos_mimir_environments'] ?? null;
        if (is_array($cached) && $cached !== []) {
            return array_values(array_map('strval', $cached));
        }
        try {
            if (!function_exists('odata_mimir_companies_as_rows')) {
                return [];
            }
            $rows = odata_mimir_companies_as_rows(null);
            $envs = [];
            $seen = [];
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $env = trim((string) ($row['environment'] ?? ''));
                if ($env === '' || isset($seen[$env])) {
                    continue;
                }
                $seen[$env] = true;
                $envs[] = $env;
            }
            $GLOBALS['talos_mimir_environments'] = $envs;
            return $envs;
        } catch (Throwable $ignored) {
            return [];
        }
    }
}

if (!function_exists('getPrimaryEnvironment')) {
    function getPrimaryEnvironment(): string
    {
        $environments = getActiveEnvironments();
        return (string) ($environments[0] ?? '');
    }
}

if (!function_exists('getAuthForEnvironment')) {
    function getAuthForEnvironment(string $environmentName): array
    {
        global $auth_list;

        $environmentKey = trim($environmentName);
        $list = is_array($auth_list ?? null) ? $auth_list : [];

        if ($environmentKey === '' || !isset($list[$environmentKey]) || !is_array($list[$environmentKey])) {
            // Mímir-modus zonder BC-auth: leftover callers krijgen lege auth i.p.v. exception.
            if (talosMimirEnabled()) {
                return [];
            }
            throw new InvalidArgumentException('Unknown environment: ' . $environmentName);
        }

        return $list[$environmentKey];
    }
}

if (!function_exists('setCompanyEnvironmentMap')) {
    function setCompanyEnvironmentMap(array $map): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $normalized = [];
        foreach ($map as $companyName => $environmentName) {
            $company = trim((string) $companyName);
            $environment = trim((string) $environmentName);
            if ($company === '' || $environment === '') {
                continue;
            }

            $normalized[$company] = $environment;
        }

        $_SESSION['company_environment_map'] = $normalized;
    }
}

if (!function_exists('getEnvironmentForCompany')) {
    function getEnvironmentForCompany(string $company): ?string
    {
        $companyName = trim($company);
        if ($companyName === '' || session_status() !== PHP_SESSION_ACTIVE) {
            return null;
        }

        $map = $_SESSION['company_environment_map'] ?? [];
        if (!is_array($map)) {
            return null;
        }

        $environmentName = (string) ($map[$companyName] ?? '');
        return $environmentName === '' ? null : $environmentName;
    }
}

talosEnsureOdataLoaded();
if (talosMimirEnabled()) {
    // BC-auth alleen als lokaal geconfigureerd; anders lege sentinel.
    // Geen companies.php-call: ontbrekende BC-config mag deze include niet fatal maken.
    $auth = [];
    $configuredEnvironments = talosNormalizeEnvironmentList($environment ?? []);
    $primaryEnvironment = (string) ($configuredEnvironments[0] ?? '');
    if ($primaryEnvironment !== '' && isset($auth_list) && is_array($auth_list) && isset($auth_list[$primaryEnvironment]) && is_array($auth_list[$primaryEnvironment])) {
        $auth = $auth_list[$primaryEnvironment];
    }
} else {
    $auth = getAuthForEnvironment(getPrimaryEnvironment());
}
