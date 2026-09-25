<?php

/**
 * Includes/requires
 */

define('TALOS_SKIP_PROJECT_WORKORDERS_PAGE_LOAD', true);
require_once __DIR__ . '/content/bootstrap.php';
require_once __DIR__ . '/content/localization.php';
require_once __DIR__ . '/content/helpers.php';
require_once __DIR__ . '/odata.php';
require_once __DIR__ . '/content/project_workorders_page.php';

/**
 * Page load
 */

header('Content-Type: application/json; charset=UTF-8');

try {
    $requestedCompany = trim((string) ($_GET['company'] ?? ''));
    $selectedCompany = $requestedCompany === '__all__' ? '' : $requestedCompany;
    $skip = max(0, (int) ($_GET['skip'] ?? 0));
    $pageSize = max(1, (int) ($_GET['top'] ?? PROJECT_WORKORDERS_PAGE_SIZE));

    if ($selectedCompany === '') {
        echo json_encode([
            'ok' => true,
            'done' => true,
            'fetched' => 0,
            'next_skip' => $skip,
            'page_size' => $pageSize,
            'total_count' => null,
            'projects' => [],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    $environments = activeEnvironmentNamesForOdata();

    if ($environments === [] && !odataReadsUseMimir()) {
        throw new RuntimeException('Geen actieve omgevingen geconfigureerd.');
    }

    $odataBaseUrl = resolveOdataBaseUrl();

    $companyName = $selectedCompany;
    $companyEnvironment = function_exists('getEnvironmentForCompany')
        ? (string) getEnvironmentForCompany($companyName)
        : '';

    if ($companyEnvironment === '') {
        $companyEnvironmentMap = fetchCompanyEnvironmentMapForProjectOverview($odataBaseUrl, $environments);
        if (function_exists('setCompanyEnvironmentMap')) {
            setCompanyEnvironmentMap($companyEnvironmentMap);
        }

        $companyEnvironment = (string) ($companyEnvironmentMap[$companyName] ?? '');
        if ($companyEnvironment === '') {
            throw new RuntimeException('Geen omgeving gevonden voor geselecteerd bedrijf.');
        }
    }

    $authForEnvironment = resolveOdataAuthForEnvironment($companyEnvironment);

    $companyBaseUrl = buildOdataCompanyUrl($odataBaseUrl, $companyEnvironment, $companyName);
    $totalCount = null;
    if ($skip === 0) {
        $totalCount = fetchEntityCountForCompany(
            $companyBaseUrl,
            'LVS_MainWorkOrderCard',
            $authForEnvironment,
            "KVT_Document_Status eq '60-GEREED'",
            'No'
        );
    }

    $batchRows = fetchWorkorderBatchForCompany(
        $odataBaseUrl,
        $companyEnvironment,
        $authForEnvironment,
        $companyName,
        $skip,
        $pageSize
    );

    $departmentCodes = extractDepartmentCodesFromWorkorders($batchRows);
    if (!empty($departmentCodes)) {
        appendDepartmentCodesToCache($departmentCodes);
    }

    $fetched = count($batchRows);
    $nextSkip = $skip + $fetched;
    $done = $fetched < $pageSize;
    if ($totalCount !== null) {
        $done = $nextSkip >= $totalCount;
    }

    echo json_encode([
        'ok' => true,
        'company' => $companyName,
        'projects' => mapWorkordersByJobNo($batchRows),
        'fetched' => $fetched,
        'next_skip' => $nextSkip,
        'done' => $done,
        'page_size' => $pageSize,
        'total_count' => $totalCount,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => $exception->getMessage(),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
