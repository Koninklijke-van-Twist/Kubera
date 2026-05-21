<?php

/**
 * Functies
 */

const PROJECT_WORKORDERS_PAGE_SIZE = 10;
const PROJECT_WORKORDERS_MAX_PAGES = 250;
const PROJECT_WORKORDERS_CACHE_TTL_SECONDS = 3600;

function fetchCompanyRowsForEnvironment(string $baseUrl, string $environment, array $auth): array
{
    $rootUrl = buildOdataRootUrl($baseUrl, $environment);

    try {
        return odata_get_all($rootUrl . 'Company?$select=Name', $auth, 300);
    } catch (Exception $firstError) {
        return odata_get_all($rootUrl . 'Companies?$select=Name', $auth, 300);
    }
}

function fetchPagedEntityRowsForCompany(
    string $companyBaseUrl,
    string $entitySet,
    string $selectClause,
    array $auth,
    ?string $filterClause = null,
    int $pageSize = PROJECT_WORKORDERS_PAGE_SIZE,
    int $maxPages = PROJECT_WORKORDERS_MAX_PAGES,
    int $ttlSeconds = 300,
    ?callable $onPageLoaded = null,
    array $meta = []
): array {
    $pageSize = max(1, $pageSize);
    $maxPages = max(1, $maxPages);
    $ttlSeconds = max(1, $ttlSeconds);

    $rows = [];
    $skip = 0;

    for ($page = 0; $page < $maxPages; $page++) {
        $queryUrl = $companyBaseUrl
            . $entitySet
            . '?$select=' . $selectClause
            . '&$top=' . $pageSize
            . '&$skip=' . $skip;

        if ($filterClause !== null && trim($filterClause) !== '') {
            $queryUrl = $companyBaseUrl
                . $entitySet
                . '?$filter=' . rawurlencode($filterClause)
                . '&$select=' . $selectClause
                . '&$top=' . $pageSize
                . '&$skip=' . $skip;
        }

        $pageRows = odata_get_all($queryUrl, $auth, $ttlSeconds);
        if (empty($pageRows)) {
            break;
        }

        $rows = array_merge($rows, $pageRows);
        if ($onPageLoaded !== null) {
            $onPageLoaded([
                'entity' => $entitySet,
                'page' => $page + 1,
                'fetched' => count($pageRows),
                'rows' => $pageRows,
                'meta' => $meta,
            ]);
        }
        if (count($pageRows) < $pageSize) {
            break;
        }

        $skip += $pageSize;
    }

    return $rows;
}

function fetchEntityCountForCompany(
    string $companyBaseUrl,
    string $entitySet,
    array $auth,
    ?string $filterClause = null,
    string $countSelect = 'No'
): ?int {
    $queryUrl = $companyBaseUrl
        . $entitySet
        . '?$count=true&$top=0&$select=' . rawurlencode($countSelect);

    if ($filterClause !== null && trim($filterClause) !== '') {
        $queryUrl = $companyBaseUrl
            . $entitySet
            . '?$filter=' . rawurlencode($filterClause)
            . '&$count=true&$top=0&$select=' . rawurlencode($countSelect);
    }

    try {
        $response = odata_get_json($queryUrl, $auth);
    } catch (Throwable $exception) {
        return null;
    }

    if (!isset($response['@odata.count'])) {
        return null;
    }

    return max(0, (int) $response['@odata.count']);
}

function fetchCompanyEnvironmentMapForProjectOverview(string $baseUrl, array $environments): array
{
    global $auth;

    $companyEnvironmentMap = [];

    foreach ($environments as $environmentName) {
        $env = trim((string) $environmentName);
        if ($env === '') {
            continue;
        }

        $authForEnvironment = function_exists('getAuthForEnvironment')
            ? getAuthForEnvironment($env)
            : (is_array($auth) ? $auth : []);

        if (!is_array($authForEnvironment) || empty($authForEnvironment)) {
            throw new RuntimeException('Geen geldige authenticatie gevonden voor omgeving: ' . $env);
        }

        $companies = fetchCompanyRowsForEnvironment($baseUrl, $env, $authForEnvironment);

        foreach ($companies as $companyRow) {
            $companyName = trim((string) (($companyRow['Name'] ?? '') !== '' ? ($companyRow['Name'] ?? '') : ($companyRow['Name'] ?? '')));
            if ($companyName === '') {
                continue;
            }

            if (isset($companyEnvironmentMap[$companyName]) && $companyEnvironmentMap[$companyName] !== $env) {
                // Keep the first match to prevent a full page failure on overlap.
                continue;
            }

            $companyEnvironmentMap[$companyName] = $env;
        }
    }

    ksort($companyEnvironmentMap, SORT_NATURAL | SORT_FLAG_CASE);
    return $companyEnvironmentMap;
}

function mapWorkordersByJobNo(array $workorderRows): array
{
    $projectsByNo = [];

    foreach ($workorderRows as $workorderRow) {
        $projectNo = trim((string) (($workorderRow['Job_No'] ?? '') !== '' ? ($workorderRow['Job_No'] ?? '') : ($workorderRow['No'] ?? '')));
        if ($projectNo === '') {
            continue;
        }

        if (!isset($projectsByNo[$projectNo])) {
            $projectsByNo[$projectNo] = [
                'no' => $projectNo,
                'description' => '',
                'department_code' => '',
                'workorders' => [],
            ];
        }

        $workorderNo = trim((string) ($workorderRow['No'] ?? ''));
        if ($workorderNo === '') {
            continue;
        }

        $departmentCode = trim((string) ($workorderRow['Job_Dimension_1_Value'] ?? ''));
        if ($departmentCode !== '' && $projectsByNo[$projectNo]['department_code'] === '') {
            $projectsByNo[$projectNo]['department_code'] = $departmentCode;
        }

        $projectsByNo[$projectNo]['workorders'][] = [
            'no' => $workorderNo,
            'description' => trim((string) ($workorderRow['Task_Description'] ?? '')),
        ];
    }

    foreach ($projectsByNo as &$project) {
        usort($project['workorders'], static function (array $a, array $b): int {
            return strnatcasecmp((string) ($a['no'] ?? ''), (string) ($b['no'] ?? ''));
        });
    }
    unset($project);

    $projects = array_values($projectsByNo);
    usort($projects, static function (array $a, array $b): int {
        return strnatcasecmp((string) ($a['no'] ?? ''), (string) ($b['no'] ?? ''));
    });

    return $projects;
}

function getDepartmentsCachePath(): string
{
    if (defined('TALOS_DEPARTMENTS_CACHE_PATH')) {
        return (string) TALOS_DEPARTMENTS_CACHE_PATH;
    }

    return __DIR__ . '/../cache/departments.json';
}

function getUserDataDirectoryPath(): string
{
    return __DIR__ . '/../cache/userdata';
}

function buildUserDataFileName(string $userEmail): string
{
    $normalized = strtolower(trim($userEmail));
    if ($normalized === '') {
        $normalized = 'anonymous';
    }

    $safe = preg_replace('/[^a-z0-9._@-]+/i', '_', $normalized);
    $safe = trim((string) $safe, '._-');
    if ($safe === '') {
        $safe = 'anonymous';
    }

    return $safe . '.json';
}

function getUserPreferencesCachePath(string $userEmail): string
{
    return getUserDataDirectoryPath() . '/' . buildUserDataFileName($userEmail);
}

function readUserSelectionPreferences(string $userEmail): array
{
    $path = getUserPreferencesCachePath($userEmail);
    if (!is_file($path)) {
        return [
            'company' => '',
            'department' => '__all__',
        ];
    }

    $raw = file_get_contents($path);
    if ($raw === false || $raw === '') {
        return [
            'company' => '',
            'department' => '__all__',
        ];
    }

    $payload = json_decode($raw, true);
    if (!is_array($payload)) {
        return [
            'company' => '',
            'department' => '__all__',
        ];
    }

    return [
        'company' => trim((string) ($payload['company'] ?? '')),
        'department' => trim((string) ($payload['department'] ?? '__all__')),
    ];
}

function writeUserSelectionPreferences(string $userEmail, string $company, string $department): void
{
    $directory = getUserDataDirectoryPath();
    if (!is_dir($directory)) {
        mkdir($directory, 0750, true);
    }

    $path = getUserPreferencesCachePath($userEmail);
    $payload = [
        'updated_at' => gmdate('c'),
        'company' => trim($company),
        'department' => trim($department) !== '' ? trim($department) : '__all__',
    ];

    file_put_contents(
        $path,
        json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
    );
}

function readDepartmentCodesFromCache(): array
{
    $path = getDepartmentsCachePath();
    if (!is_file($path)) {
        return [];
    }

    $raw = file_get_contents($path);
    if ($raw === false || $raw === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    if (is_array($decoded) && isset($decoded['codes']) && is_array($decoded['codes'])) {
        return array_values(array_filter(array_map('strval', $decoded['codes']), static function (string $value): bool {
            return trim($value) !== '';
        }));
    }

    if (is_array($decoded)) {
        return array_values(array_filter(array_map('strval', $decoded), static function (string $value): bool {
            return trim($value) !== '';
        }));
    }

    return [];
}

function writeDepartmentCodesToCache(array $codes): void
{
    $normalized = array_values(array_unique(array_filter(array_map(static function ($value): string {
        return trim((string) $value);
    }, $codes), static function (string $value): bool {
        return $value !== '';
    })));
    usort($normalized, 'strnatcasecmp');

    $path = getDepartmentsCachePath();
    $payload = [
        'updated_at' => gmdate('c'),
        'codes' => $normalized,
    ];

    file_put_contents($path, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
}

function appendDepartmentCodesToCache(array $codes): void
{
    $normalizedIncoming = array_values(array_unique(array_filter(array_map(static function ($value): string {
        return trim((string) $value);
    }, $codes), static function (string $value): bool {
        return $value !== '';
    })));

    if (empty($normalizedIncoming)) {
        return;
    }

    $current = readDepartmentCodesFromCache();
    $currentLookup = array_fill_keys($current, true);

    $hasNewCode = false;
    foreach ($normalizedIncoming as $code) {
        if (!isset($currentLookup[$code])) {
            $hasNewCode = true;
            break;
        }
    }

    if (!$hasNewCode) {
        return;
    }

    $merged = array_merge($current, $normalizedIncoming);
    writeDepartmentCodesToCache($merged);
}

function extractDepartmentCodesFromWorkorders(array $workorderRows): array
{
    $codes = [];
    foreach ($workorderRows as $workorderRow) {
        $code = trim((string) ($workorderRow['Job_Dimension_1_Value'] ?? ''));
        if ($code !== '') {
            $codes[$code] = true;
        }
    }

    $values = array_keys($codes);
    usort($values, 'strnatcasecmp');
    return $values;
}

function fetchWorkorderBatchForCompany(
    string $baseUrl,
    string $environment,
    array $auth,
    string $companyName,
    int $skip,
    int $top = PROJECT_WORKORDERS_PAGE_SIZE
): array {
    $companyBaseUrl = buildOdataCompanyUrl($baseUrl, $environment, $companyName);
    $safeTop = max(1, $top);
    $safeSkip = max(0, $skip);

    $queryUrl = $companyBaseUrl
        . 'LVS_MainWorkOrderCard'
        . '?$filter=' . rawurlencode("KVT_Document_Status eq '60-GEREED'")
        . '&$select=No,Job_No,Task_Description,Job_Dimension_1_Value'
        . '&$top=' . $safeTop
        . '&$skip=' . $safeSkip;

    return odata_get_all($queryUrl, $auth, PROJECT_WORKORDERS_CACHE_TTL_SECONDS);
}

function fetchWorkorderRowsForCompany(
    string $baseUrl,
    string $environment,
    array $auth,
    string $companyName,
    ?callable $onPageLoaded = null
): array {
    $companyBaseUrl = buildOdataCompanyUrl($baseUrl, $environment, $companyName);

    return fetchPagedEntityRowsForCompany(
        $companyBaseUrl,
        'LVS_MainWorkOrderCard',
        'No,Job_No,Task_Description,Job_Dimension_1_Value',
        $auth,
        "KVT_Document_Status eq '60-GEREED'",
        PROJECT_WORKORDERS_PAGE_SIZE,
        PROJECT_WORKORDERS_MAX_PAGES,
        PROJECT_WORKORDERS_CACHE_TTL_SECONDS,
        $onPageLoaded,
        [
            'company' => $companyName,
            'step' => 'workorders',
        ]
    );
}

function getSelectedCompaniesToLoad(array $availableCompanies, string $selectedCompany): array
{
    if ($selectedCompany !== '' && in_array($selectedCompany, $availableCompanies, true)) {
        return [$selectedCompany];
    }

    return $availableCompanies;
}

if (defined('TALOS_SKIP_PROJECT_WORKORDERS_PAGE_LOAD') && TALOS_SKIP_PROJECT_WORKORDERS_PAGE_LOAD) {
    return;
}

/**
 * Variabelen
 */

$userKey = (string) ($_SESSION['user']['email'] ?? 'anonymous');
$userPreferences = readUserSelectionPreferences($userKey);
if (!isset($_SESSION['selected_company_by_user']) || !is_array($_SESSION['selected_company_by_user'])) {
    $_SESSION['selected_company_by_user'] = [];
}

$requestedCompany = trim((string) ($_GET['company'] ?? ''));
$requestedDepartment = trim((string) ($_GET['department'] ?? ''));
$showOdataErrorDetails = isset($_GET['debug_odata']) && (string) $_GET['debug_odata'] === '1';
$isAllCompaniesSelection = $requestedCompany === '__all__';
$selectedCompany = $requestedCompany !== ''
    ? $requestedCompany
    : ((string) ($userPreferences['company'] ?? '') !== ''
        ? (string) ($userPreferences['company'] ?? '')
        : (string) ($_SESSION['selected_company_by_user'][$userKey] ?? ''));

$selectedDepartment = $requestedDepartment !== ''
    ? $requestedDepartment
    : (string) ($userPreferences['department'] ?? '__all__');

if ($selectedDepartment === '') {
    $selectedDepartment = '__all__';
}

if ($isAllCompaniesSelection) {
    $selectedCompany = '';
}

/**
 * Page load
 */

$odataError = null;
$availableCompanies = [];
$projectOverviewByCompany = [];

try {
    global $environment;

    $environments = function_exists('getActiveEnvironments')
        ? getActiveEnvironments()
        : (is_array($environment ?? null) ? $environment : [((string) ($environment ?? ''))]);

    $environments = array_values(array_filter(array_map('trim', array_map('strval', $environments)), static function (string $item): bool {
        return $item !== '';
    }));

    if (empty($environments) && function_exists('getPrimaryEnvironment')) {
        $primaryEnvironment = trim((string) getPrimaryEnvironment());
        if ($primaryEnvironment !== '') {
            $environments = [$primaryEnvironment];
        }
    }

    if (empty($environments)) {
        throw new RuntimeException('Geen actieve omgevingen geconfigureerd.');
    }

    $companyEnvironmentMap = fetchCompanyEnvironmentMapForProjectOverview($baseUrl, $environments);
    if (function_exists('setCompanyEnvironmentMap')) {
        setCompanyEnvironmentMap($companyEnvironmentMap);
    }

    $availableCompanies = array_keys($companyEnvironmentMap);

    if ($selectedCompany !== '' && !in_array($selectedCompany, $availableCompanies, true)) {
        $selectedCompany = '';
    }

    $companiesToLoad = getSelectedCompaniesToLoad($availableCompanies, $selectedCompany);

    $autoLoadData = isset($_GET['autoload']) && (string) $_GET['autoload'] === '1';
    if ($autoLoadData) {
        foreach ($companiesToLoad as $companyName) {
            $companyEnvironment = (string) ($companyEnvironmentMap[$companyName] ?? '');
            if ($companyEnvironment === '') {
                continue;
            }

            $authForEnvironment = function_exists('getAuthForEnvironment')
                ? getAuthForEnvironment($companyEnvironment)
                : $auth;

            $workorderRows = fetchWorkorderRowsForCompany($baseUrl, $companyEnvironment, $authForEnvironment, $companyName);
            $projectOverviewByCompany[$companyName] = mapWorkordersByJobNo($workorderRows);
        }
    }

    $_SESSION['selected_company_by_user'][$userKey] = $selectedCompany !== '' ? $selectedCompany : '__all__';
} catch (Throwable $exception) {
    $odataError = $exception->getMessage();
    $projectOverviewByCompany = [];
}
