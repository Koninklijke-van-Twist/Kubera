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
    if ((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    $rawBody = file_get_contents('php://input');
    $payload = is_string($rawBody) && $rawBody !== '' ? json_decode($rawBody, true) : [];
    if (!is_array($payload)) {
        $payload = [];
    }

    $company = trim((string) ($payload['company'] ?? ''));
    $department = trim((string) ($payload['department'] ?? '__all__'));
    if ($department === '') {
        $department = '__all__';
    }

    $userEmail = (string) ($_SESSION['user']['email'] ?? 'anonymous');
    writeUserSelectionPreferences($userEmail, $company, $department);

    echo json_encode([
        'ok' => true,
        'company' => $company,
        'department' => $department,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => $exception->getMessage(),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
