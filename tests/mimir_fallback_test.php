<?php
/**
 * Simuleert een onbereikbare Mímir en controleert de directe BC-fallback.
 * Run: php tests/mimir_fallback_test.php
 */

$logFile = sys_get_temp_dir() . '/kubera-mimir-fallback-test.log';
@unlink($logFile);
ini_set('error_log', $logFile);
ini_set('log_errors', '1');

$mimirApi = 'mimir_test_key_should_not_leak';
$mimirBase = 'http://127.0.0.1:9';
$baseUrl = 'https://bc.example:7148/';
$environment = 'Production';
$auth = ['mode' => 'basic', 'user' => 'bcuser', 'pass' => 'bc-secret'];
$auth_list = ['Production' => $auth];

$calls = [];
$GLOBALS['KUBERA_ODATA_BC_FETCH'] = static function (string $url, array $auth, int $ttl) use (&$calls): array {
    $calls[] = [
        'url' => $url,
        'user' => (string) ($auth['user'] ?? ''),
        'ttl' => $ttl,
    ];
    if (preg_match('#/ODataV4/Company(?:\\?|$)#', $url) === 1) {
        return [
            ['Name' => 'KVT Gas'],
            ['Name' => 'Hunter van Twist'],
            ['name' => 'Koninklijke van Twist'],
        ];
    }
    return [['No' => 'WO-1']];
};

require dirname(__DIR__) . '/web/odata.php';

function fail(string $message): void
{
    fwrite(STDERR, "FAIL: $message\n");
    exit(1);
}

function fallback_log(): string
{
    global $logFile;
    $raw = @file_get_contents($logFile);
    return is_string($raw) ? $raw : '';
}

function fallback_count(): int
{
    return substr_count(fallback_log(), '[Kubera] Mímir failed, falling back to direct OData:');
}

if (odata_mimir_connect_timeout_seconds() !== 10) {
    fail('connect-timeout moet 10s zijn');
}
if (odata_mimir_timeout_seconds_for_sapi('cli') !== 600) {
    fail('CLI-timeout moet 600s blijven');
}
if (odata_mimir_timeout_seconds_for_sapi('fpm-fcgi') !== 90 || odata_mimir_timeout_seconds_for_sapi('apache2handler') !== 90) {
    fail('web-timeout moet ongeveer 90s zijn');
}
if (PHP_SAPI === 'cli' && odata_mimir_timeout_seconds() !== 600) {
    fail('huidige CLI-sapi moet de lange timeout gebruiken');
}

$names = odata_mimir_list_companies(null);
$expectedNames = ['Hunter van Twist', 'Koninklijke van Twist', 'KVT Gas'];
if ($names !== $expectedNames) {
    fail('company-fallback gaf ' . json_encode($names) . ' i.p.v. de gesorteerde BC-namen');
}
if (!odata_mimir_circuit_open()) {
    fail('circuit moet open na de eerste Mímir-fout');
}
if (count($calls) !== 1 || strpos($calls[0]['url'], 'https://bc.example:7148/Production/ODataV4/Company') !== 0) {
    fail('company-fallback riep de directe BC-fetch niet aan: ' . json_encode($calls));
}
if ($calls[0]['user'] !== 'bcuser') {
    fail('company-fallback gebruikte niet de BC-credentials');
}

$mimirBase = 'http://192.0.2.1:9';
$started = microtime(true);
$rows = odata_get_all(
    "https://mimir.invalid/Production/ODataV4/Company('Koninklijke%20van%20Twist')/AppWerkorders?\$select=No",
    $auth,
    120
);
$elapsed = microtime(true) - $started;
if ($elapsed >= 2.0) {
    fail('circuit breaker sloeg Mímir niet over (' . round($elapsed, 3) . 's)');
}
if (($rows[0]['No'] ?? '') !== 'WO-1') {
    fail('entity-fallback gaf niet de gestubde BC-rijen terug');
}
$entityCall = $calls[1] ?? null;
$expectedEntityUrl = "https://bc.example:7148/Production/ODataV4/Company('Koninklijke%20van%20Twist')/AppWerkorders?\$select=No";
if (!is_array($entityCall) || $entityCall['url'] !== $expectedEntityUrl || $entityCall['user'] !== 'bcuser' || $entityCall['ttl'] !== 120) {
    fail('entity-fallback URL/auth/ttl klopt niet: ' . json_encode($entityCall));
}
if (fallback_count() !== 1) {
    fail('alleen de eerste Mímir-fout mag gelogd worden, log=' . fallback_log());
}
$loggedWhileOpen = fallback_count();
odata_get_all(
    "https://mimir.invalid/Production/ODataV4/Company('Koninklijke%20van%20Twist')/AppWerkorders?\$select=No",
    $auth,
    10
);
if (fallback_count() !== $loggedWhileOpen) {
    fail('een open circuit mag niet opnieuw loggen, log=' . fallback_log());
}
$log = fallback_log();
if (strpos($log, 'mimir_test_key_should_not_leak') !== false || strpos($log, 'bc-secret') !== false) {
    fail('log bevat een geheim');
}
if (strpos($log, '[Kubera] Mímir failed, falling back to direct OData:') === false) {
    fail('logregel mist het verwachte prefix');
}

odata_mimir_circuit_reset();
$mimirBase = 'http://127.0.0.1:9';
$beforeQuery = count($calls);
$queryRows = odata_mimir_query('KVT Gas', 'AppResource', ['$select' => 'No,Name'], 60);
if (($queryRows[0]['No'] ?? '') !== 'WO-1') {
    fail('odata_mimir_query viel niet terug op de stub');
}
$queryCall = $calls[$beforeQuery] ?? null;
if (!is_array($queryCall) || strpos($queryCall['url'], "https://bc.example:7148/Production/ODataV4/Company('KVT%20Gas')/AppResource?") !== 0) {
    fail('query-fallback bouwde niet de pre-Mímir BC-URL: ' . json_encode($queryCall));
}

odata_mimir_circuit_reset();
$beforeFetch = count($calls);
$fetchRows = odata_mimir_fetch_all(
    "https://mimir.invalid/Production/ODataV4/Company('KVT%20Gas')/AppWerkorders?\$select=No",
    15
);
if (($fetchRows[0]['No'] ?? '') !== 'WO-1') {
    fail('odata_mimir_fetch_all viel niet terug');
}
$fetchCall = $calls[$beforeFetch] ?? null;
if (!is_array($fetchCall) || $fetchCall['url'] !== "https://bc.example:7148/Production/ODataV4/Company('KVT%20Gas')/AppWerkorders?\$select=No") {
    fail('fetch_all-fallback herschreef de URL niet: ' . json_encode($fetchCall));
}

odata_mimir_circuit_reset();
$beforeJson = count($calls);
$json = odata_get_json(
    "https://mimir.invalid/Production/ODataV4/Company('KVT%20Gas')/LVS_MainWorkOrderCard?\$count=true&\$top=0&\$select=No",
    $auth
);
if (($json['value'][0]['No'] ?? '') !== 'WO-1' || (int) ($json['@odata.count'] ?? -1) !== 1) {
    fail('odata_get_json viel niet terug op de stub: ' . json_encode($json));
}
$jsonCall = $calls[$beforeJson] ?? null;
$expectedJsonUrl = "https://bc.example:7148/Production/ODataV4/Company('KVT%20Gas')/LVS_MainWorkOrderCard?\$count=true&\$top=0&\$select=No";
if (!is_array($jsonCall) || $jsonCall['url'] !== $expectedJsonUrl) {
    fail('get_json-fallback herschreef de URL niet: ' . json_encode($jsonCall));
}

odata_mimir_circuit_reset();
$beforeRelative = count($calls);
$relativeRows = odata_get_all(
    "/Production/ODataV4/Company%28%27KVT%20Gas%27%29/LVS_MainWorkOrderCard?\$select=No",
    [],
    15
);
if (($relativeRows[0]['No'] ?? '') !== 'WO-1') {
    fail('relatieve Kubera-URL viel niet terug');
}
$relativeCall = $calls[$beforeRelative] ?? null;
$expectedRelative = "https://bc.example:7148/Production/ODataV4/Company%28%27KVT%20Gas%27%29/LVS_MainWorkOrderCard?\$select=No";
if (!is_array($relativeCall) || $relativeCall['url'] !== $expectedRelative || $relativeCall['user'] !== 'bcuser') {
    fail('relatieve URL werd niet naar BC herschreven met BC-auth: ' . json_encode($relativeCall));
}

odata_mimir_circuit_reset();
$map = odata_mimir_company_environment_map(null);
if (($map['Hunter van Twist'] ?? '') !== 'Production' || ($map['KVT Gas'] ?? '') !== 'Production') {
    fail('environment-map viel niet terug op BC: ' . json_encode($map));
}

odata_mimir_circuit_reset();
$callerLogged = fallback_count();
$callerCalls = count($calls);
$callerError = null;
try {
    odata_get_all('https://mimir.invalid/not-an-odata-path', $auth, 10);
    fail('een onvertaalbare URL moet een caller-fout geven');
} catch (Throwable $exception) {
    $callerError = $exception;
}
if (!$callerError instanceof Throwable || strpos($callerError->getMessage(), 'kon niet worden vertaald') === false) {
    fail('caller-fout mist de vertaalboodschap: ' . ($callerError instanceof Throwable ? $callerError->getMessage() : 'geen exception'));
}
if (odata_mimir_circuit_open()) {
    fail('een caller-exception mag het circuit niet openen');
}
if (fallback_count() !== $callerLogged || count($calls) !== $callerCalls) {
    fail('een caller-exception mag geen fallback starten');
}

if (odata_bc_encode_environment('My%20Env') !== 'My%20Env' || odata_bc_encode_environment('My Env') !== 'My%20Env') {
    fail('environment-segment moet precies één keer geëncodeerd worden');
}

odata_mimir_circuit_reset();
$mimirBase = 'http://127.0.0.1:9';
$environment = 'Production';
$auth = ['mode' => 'basic', 'user' => 'bcuser', 'pass' => 'bc-secret'];
$sandboxAuth = ['mode' => 'basic', 'user' => 'sandbox-user', 'pass' => 'sandbox-secret'];
$auth_list = [
    'Production' => $auth,
    'Sandbox' => $sandboxAuth,
];
$GLOBALS['company_environment_map'] = [
    'Hunter van Twist' => 'Sandbox',
];
$beforeSandbox = count($calls);
$sandboxRows = odata_mimir_query('Hunter van Twist', 'AppResource', ['$select' => 'No'], 30);
if (($sandboxRows[0]['No'] ?? '') !== 'WO-1') {
    fail('tweede environment gaf geen stub-rijen');
}
$sandboxCall = $calls[$beforeSandbox] ?? null;
if (!is_array($sandboxCall) || strpos($sandboxCall['url'], "https://bc.example:7148/Sandbox/ODataV4/Company('Hunter%20van%20Twist')/AppResource?") !== 0 || $sandboxCall['user'] !== 'sandbox-user') {
    fail('query gebruikte niet het environment van het bedrijf: ' . json_encode($sandboxCall));
}

odata_mimir_circuit_reset();
$beforeSandboxUrl = count($calls);
$sandboxUrlRows = odata_get_all(
    "https://mimir.invalid/mimir/ODataV4/Company('Hunter%20van%20Twist')/AppWerkorders?\$select=No",
    $auth,
    12
);
if (($sandboxUrlRows[0]['No'] ?? '') !== 'WO-1') {
    fail('mimir-segment viel niet terug via de company-map');
}
$sandboxUrlCall = $calls[$beforeSandboxUrl] ?? null;
$expectedSandboxUrl = "https://bc.example:7148/Sandbox/ODataV4/Company('Hunter%20van%20Twist')/AppWerkorders?\$select=No";
if (!is_array($sandboxUrlCall) || $sandboxUrlCall['url'] !== $expectedSandboxUrl || $sandboxUrlCall['user'] !== 'sandbox-user') {
    fail('URL-herschrijving hield het primaire environment: ' . json_encode($sandboxUrlCall));
}
$sandboxLog = fallback_log();
if (strpos($sandboxLog, 'sandbox-secret') !== false || strpos($sandboxLog, 'bc-secret') !== false) {
    fail('tweede-environment-log bevat een geheim');
}
$cacheKey = build_cache_key(
    "https://mimir.invalid/mimir/ODataV4/Company('Hunter%20van%20Twist')/AppWerkorders",
    $auth
);
if (substr($cacheKey, -strlen('|Sandbox')) !== '|Sandbox' || strpos($cacheKey, '|mimir') !== false) {
    fail('cache-key moet het BC-environment van het bedrijf gebruiken, kreeg: ' . $cacheKey);
}
unset($GLOBALS['company_environment_map']);
$auth_list = ['Production' => $auth];

$loggedBeforeRethrow = fallback_count();
$callsBeforeRethrow = count($calls);
odata_mimir_circuit_reset();
$mimirBase = 'http://127.0.0.1:9';
$baseUrl = 'https://mimir.invalid/';
$environment = 'mimir';
$auth = [];
$auth_list = [];
$rethrown = null;
try {
    odata_get_all('https://mimir.invalid/mimir/ODataV4/Company(\'X\')/AppWerkorders', ['mode' => 'basic', 'user' => '', 'pass' => ''], 30);
    fail('zonder BC-credentials moet de oorspronkelijke Mímir-fout terugkomen');
} catch (Throwable $exception) {
    $rethrown = $exception;
}
if (!$rethrown instanceof Throwable || strpos($rethrown->getMessage(), 'Mímir') === false) {
    fail('hergooide fout is niet de Mímir-fout: ' . ($rethrown instanceof Throwable ? $rethrown->getMessage() : 'geen exception'));
}
if (stripos($rethrown->getMessage(), 'credential') !== false) {
    fail('hergooide fout maskeert Mímir met een credentials-melding: ' . $rethrown->getMessage());
}
if (count($calls) !== $callsBeforeRethrow) {
    fail('zonder BC-credentials mag de directe fetch niet starten');
}
if (fallback_count() !== $loggedBeforeRethrow) {
    fail('zonder BC-credentials mag er geen fallback gelogd worden');
}

odata_mimir_circuit_reset();
$mimirApi = '';
$mimirBase = 'http://127.0.0.1:9';
$baseUrl = 'https://bc.example:7148/';
$environment = 'Production';
$auth = ['mode' => 'basic', 'user' => 'bcuser', 'pass' => 'bc-secret'];
$auth_list = ['Production' => $auth];
$loggedBeforeDirect = fallback_count();
$directOnlyUrl = 'https://mimir.invalid/Production/ODataV4/Company(\'KVT%20Gas\')/AppWerkorders?$select=No';
$directRows = odata_get_all($directOnlyUrl, $auth, 45);
if (odata_mimir_circuit_open()) {
    fail('lege $mimirApi mag Mímir niet proberen');
}
if (fallback_count() !== $loggedBeforeDirect) {
    fail('lege $mimirApi mag geen Mímir-fallback loggen');
}
$directCall = $calls[count($calls) - 1] ?? null;
if (($directRows[0]['No'] ?? '') !== 'WO-1' || !is_array($directCall) || $directCall['url'] !== $directOnlyUrl) {
    fail('lege $mimirApi moet de oude directe route ongewijzigd gebruiken: ' . json_encode($directCall));
}

$authFile = sys_get_temp_dir() . '/kubera-auth-globals.php';
file_put_contents($authFile, <<<'PHP'
<?php
$baseUrl = 'https://from-auth.example/';
$environment = 'FromFile';
$auth = ['mode' => 'basic', 'user' => 'file-user', 'pass' => 'file-secret'];
$auth_list = ['FromFile' => $auth];
$base = 'https://from-auth-base.example/';
PHP);
$savedGlobals = [
    'baseUrl' => $GLOBALS['baseUrl'] ?? null,
    'environment' => $GLOBALS['environment'] ?? null,
    'auth' => $GLOBALS['auth'] ?? null,
    'auth_list' => $GLOBALS['auth_list'] ?? null,
    'base' => array_key_exists('base', $GLOBALS) ? $GLOBALS['base'] : null,
    'base_set' => array_key_exists('base', $GLOBALS),
];
unset($GLOBALS['KUBERA_AUTH_PHP_INCLUDED']);
$GLOBALS['KUBERA_AUTH_PHP_PATH'] = $authFile;
$GLOBALS['baseUrl'] = 'https://already-set.example/';
unset($GLOBALS['environment'], $GLOBALS['auth'], $GLOBALS['auth_list'], $GLOBALS['base']);
odata_bc_ensure_auth_loaded();
if (($GLOBALS['baseUrl'] ?? '') !== 'https://already-set.example/') {
    fail('gezette baseUrl werd overschreven: ' . (string) ($GLOBALS['baseUrl'] ?? ''));
}
if (($GLOBALS['environment'] ?? '') !== 'FromFile') {
    fail('environment uit auth.php kwam niet in $GLOBALS');
}
if (($GLOBALS['auth']['user'] ?? '') !== 'file-user') {
    fail('auth uit auth.php kwam niet in $GLOBALS');
}
if (!isset($GLOBALS['auth_list']['FromFile']) || ($GLOBALS['base'] ?? '') !== 'https://from-auth-base.example/') {
    fail('auth_list of base uit auth.php kwam niet in $GLOBALS');
}
odata_bc_ensure_auth_loaded();
if (($GLOBALS['baseUrl'] ?? '') !== 'https://already-set.example/' || ($GLOBALS['environment'] ?? '') !== 'FromFile') {
    fail('tweede load overschreef gezette globals');
}
$GLOBALS['baseUrl'] = $savedGlobals['baseUrl'];
$GLOBALS['environment'] = $savedGlobals['environment'];
$GLOBALS['auth'] = $savedGlobals['auth'];
$GLOBALS['auth_list'] = $savedGlobals['auth_list'];
if ($savedGlobals['base_set']) {
    $GLOBALS['base'] = $savedGlobals['base'];
} else {
    unset($GLOBALS['base']);
}
unset($GLOBALS['KUBERA_AUTH_PHP_PATH'], $GLOBALS['KUBERA_AUTH_PHP_INCLUDED']);
@unlink($authFile);

echo "OK\n";
