<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * OData-routing: Mímir als $mimirApi gezet is, BC als de key ontbreekt.
 */
class MimirOdataRoutingTest extends TestCase
{
    private static $server = null;
    private static int $port = 18953;
    private static string $mockLog = '';
    private static string $mockScript = '';
    private static string $authPath = '';
    private static ?string $authBackup = null;

    public static function setUpBeforeClass(): void
    {
        self::$mockLog = sys_get_temp_dir() . '/kubera-mimir-mock.log';
        self::$mockScript = sys_get_temp_dir() . '/kubera-mimir-mock.php';
        self::$authPath = dirname(__DIR__) . '/web/auth.php';
        self::$authBackup = is_file(self::$authPath) ? file_get_contents(self::$authPath) : null;

        self::writeMock();
        @unlink(self::$mockLog);
        self::$server = proc_open(
            [PHP_BINARY, '-S', '127.0.0.1:' . self::$port, self::$mockScript],
            [
                1 => ['file', sys_get_temp_dir() . '/kubera-mimir-mock.stdout', 'w'],
                2 => ['file', sys_get_temp_dir() . '/kubera-mimir-mock.stderr', 'w'],
            ],
            $pipes,
            sys_get_temp_dir()
        );
        if (!is_resource(self::$server)) {
            self::fail('mock-server start mislukt');
        }

        $ready = false;
        for ($i = 0; $i < 30; $i++) {
            $socket = @fsockopen('127.0.0.1', self::$port, $errno, $errstr, 0.2);
            if (is_resource($socket)) {
                fclose($socket);
                $ready = true;
                break;
            }
            usleep(100000);
        }
        if (!$ready) {
            self::fail('mock-server niet bereikbaar');
        }

        $GLOBALS['mimirApi'] = 'mimir_test_key';
        $GLOBALS['mimirBase'] = 'http://127.0.0.1:' . self::$port . '/mimir/api';
        require_once dirname(__DIR__) . '/web/odata.php';
        require_once dirname(__DIR__) . '/web/authhelper.php';
        if (!defined('TALOS_SKIP_PROJECT_WORKORDERS_PAGE_LOAD')) {
            define('TALOS_SKIP_PROJECT_WORKORDERS_PAGE_LOAD', true);
        }
        require_once dirname(__DIR__) . '/web/content/project_workorders_page.php';
    }

    public static function tearDownAfterClass(): void
    {
        if (is_resource(self::$server)) {
            proc_terminate(self::$server);
            proc_close(self::$server);
            self::$server = null;
        }
        if (self::$authBackup === null) {
            if (is_file(self::$authPath)) {
                @unlink(self::$authPath);
            }
        } else {
            file_put_contents(self::$authPath, self::$authBackup);
        }
        @unlink(self::$mockScript);
        @unlink(self::$mockLog);
    }

    protected function setUp(): void
    {
        $GLOBALS['mimirApi'] = 'mimir_test_key';
        $GLOBALS['mimirBase'] = 'http://127.0.0.1:' . self::$port . '/mimir/api';
        unset(
            $GLOBALS['environment'],
            $GLOBALS['auth_list'],
            $GLOBALS['auth'],
            $GLOBALS['baseUrl'],
            $GLOBALS['talos_mimir_environments']
        );
    }

    public function testMimirDisabledWithoutKey(): void
    {
        $GLOBALS['mimirApi'] = '';
        $this->assertFalse(odata_mimir_enabled());
    }

    public function testDefaultMimirBase(): void
    {
        unset($GLOBALS['mimirBase']);
        $this->assertSame('https://sleutels.kvt.nl/mimir/api', odata_mimir_base_url());
    }

    public function testEncodedCompanyUrlParsesWithoutBcHost(): void
    {
        $url = buildOdataCompanyUrl('', 'Production', "Van Twist's")
            . 'LVS_MainWorkOrderCard?$filter=' . rawurlencode("KVT_Document_Status eq '60-GEREED'")
            . '&$select=No,Job_No&$top=1&$skip=1';

        $parsed = odata_mimir_parse_entity_url($url);
        $this->assertIsArray($parsed);
        $this->assertSame("Van Twist's", $parsed['company'] ?? null);
        $this->assertSame('LVS_MainWorkOrderCard', $parsed['entity'] ?? null);
        $this->assertSame('No,Job_No', $parsed['query']['$select'] ?? null);
        $this->assertSame("KVT_Document_Status eq '60-GEREED'", $parsed['query']['$filter'] ?? null);
        $this->assertSame('1', $parsed['query']['$top'] ?? null);
        $this->assertSame('1', $parsed['query']['$skip'] ?? null);
        $this->assertNull(odata_mimir_parse_companies_url($url));
    }

    public function testCompaniesUrlParsesEnvironment(): void
    {
        $url = buildOdataRootUrl('https://bc.example', 'Production') . 'Companies?$select=Name';
        $parsed = odata_mimir_parse_companies_url($url);
        $this->assertIsArray($parsed);
        $this->assertSame('Production', $parsed['environment'] ?? null);
        $this->assertNull(odata_mimir_parse_entity_url($url));
    }

    public function testBcAuthStillThrowsWithoutMimir(): void
    {
        $GLOBALS['mimirApi'] = '';
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown environment');
        getAuthForEnvironment('Production');
    }

    public function testActiveEnvironmentsFallbackWithoutMimir(): void
    {
        $GLOBALS['mimirApi'] = '';
        $this->assertSame(['kvtmdlive_aad'], getActiveEnvironments());
    }

    public function testAuthhelperIncludeDoesNotFatalWhenOnlyMimirKeyIsSet(): void
    {
        $helper = dirname(__DIR__) . '/web/authhelper.php';
        $script = sys_get_temp_dir() . '/kubera-mimir-include-test.php';
        $helperExport = var_export($helper, true);
        file_put_contents($script, <<<PHP
<?php
\$mimirApi = 'mimir_test_key';
require {$helperExport};
if (!isset(\$auth) || !is_array(\$auth)) {
    fwrite(STDERR, 'auth sentinel ontbreekt');
    exit(2);
}
if (\$auth !== []) {
    fwrite(STDERR, 'auth sentinel niet leeg');
    exit(3);
}
echo 'ok';
PHP);
        $output = [];
        $exitCode = 0;
        exec(PHP_BINARY . ' ' . escapeshellarg($script) . ' 2>&1', $output, $exitCode);
        @unlink($script);
        $this->assertSame(0, $exitCode, implode("\n", $output));
        $this->assertSame('ok', implode("\n", $output));
    }

    public function testCompanyDiscoveryAndReadsWithoutBcConfig(): void
    {
        @unlink(self::$mockLog);
        $beforeCache = glob(dirname(__DIR__) . '/web/cache/odata/*.json') ?: [];

        $discovered = fetchCompanyEnvironmentMapForProjectOverview('', []);
        $this->assertSame([
            'Hunter van Twist' => 'Sandbox',
            'Koninklijke van Twist' => 'Production',
            "Van Twist's" => 'Production',
        ], $discovered);

        $this->assertSame([], getAuthForEnvironment('Production'));
        unset($GLOBALS['talos_mimir_environments']);
        $this->assertSame(['Production', 'Sandbox'], getActiveEnvironments());

        $batch = fetchWorkorderBatchForCompany('', 'Production', [], 'Koninklijke van Twist', 1, 1);
        $this->assertCount(1, $batch);
        $this->assertSame('WO-2', $batch[0]['No'] ?? null);

        $companyBase = buildOdataCompanyUrl('', 'Production', 'Koninklijke van Twist');
        $count = fetchEntityCountForCompany(
            $companyBase,
            'LVS_MainWorkOrderCard',
            [],
            "KVT_Document_Status eq '60-GEREED'",
            'No'
        );
        $this->assertSame(3, $count);

        $full = odata_get_all(
            $companyBase . 'LVS_MainWorkOrderCard?$select=No,Job_No&$filter=' . rawurlencode("No eq 'WO-1'"),
            [],
            60
        );
        $this->assertCount(3, $full);
        $this->assertSame('Koninklijke van Twist', $full[0]['company'] ?? null);
        $this->assertSame('LVS_MainWorkOrderCard', $full[0]['table'] ?? null);
        $this->assertSame("No eq 'WO-1'", $full[0]['filter'] ?? null);
        $this->assertSame(60, $full[0]['max_age'] ?? null);
        $this->assertSame(0, $full[0]['request_top'] ?? null);
        $this->assertSame(['No', 'Job_No'], $full[0]['select'] ?? null);

        $companyRows = odata_get_all('https://bc.example/Sandbox/ODataV4/Company?$select=Name', [], 30);
        $names = array_map(static function (array $row): string {
            return (string) ($row['Name'] ?? '');
        }, $companyRows);
        $this->assertSame(['Hunter van Twist'], $names);

        $afterCache = glob(dirname(__DIR__) . '/web/cache/odata/*.json') ?: [];
        $this->assertCount(count($beforeCache), $afterCache);

        $hitBcHost = false;
        $sawMimirUa = false;
        foreach (self::mockRequests() as $request) {
            if (str_contains((string) ($request['uri'] ?? ''), 'bc.example')) {
                $hitBcHost = true;
            }
            if (($request['ua'] ?? '') === 'Kubera-MimirClient/1.0' && str_contains((string) ($request['uri'] ?? ''), '/mimir/api/')) {
                $sawMimirUa = true;
            }
        }
        $this->assertFalse($hitBcHost);
        $this->assertTrue($sawMimirUa);
    }

    public function testLeftoverEnvironmentWithoutAuthListDoesNotLimitMimir(): void
    {
        $GLOBALS['environment'] = 'kvtmdlive_aad';
        unset($GLOBALS['talos_mimir_environments']);
        $this->assertSame(['Production', 'Sandbox'], getActiveEnvironments());
        $map = fetchCompanyEnvironmentMapForProjectOverview('', []);
        $this->assertArrayHasKey('Hunter van Twist', $map);
        $this->assertArrayHasKey('Koninklijke van Twist', $map);
    }

    public function testConfiguredEnvironmentStillFiltersMimirCompanies(): void
    {
        $GLOBALS['environment'] = 'Sandbox';
        $GLOBALS['auth_list'] = [
            'Sandbox' => ['mode' => 'basic', 'user' => 'u', 'pass' => 'p'],
        ];
        $map = fetchCompanyEnvironmentMapForProjectOverview('', activeEnvironmentNamesForOdata());
        $this->assertSame(['Hunter van Twist' => 'Sandbox'], $map);
    }

    public function testBcEnvironmentWithoutAuthListStaysConfigured(): void
    {
        $GLOBALS['mimirApi'] = '';
        $GLOBALS['environment'] = 'Production';
        $this->assertSame(['Production'], getActiveEnvironments());
    }

    public function testCompanyNameOverlapKeepsFirstEnvironment(): void
    {
        $GLOBALS['mimirBase'] = 'http://127.0.0.1:' . self::$port . '/mimir-dup/api';
        $map = fetchCompanyEnvironmentMapForProjectOverview('', []);
        $this->assertSame(['Overlap BV' => 'Production'], $map);
    }

    public function testBcFetchStillWorksWithoutMimirKey(): void
    {
        $GLOBALS['mimirApi'] = '';
        $GLOBALS['baseUrl'] = 'http://127.0.0.1:' . self::$port;
        $GLOBALS['environment'] = 'Production';
        $GLOBALS['auth_list'] = [
            'Production' => [
                'mode' => 'basic',
                'user' => 'bcuser',
                'pass' => 'bcpass',
            ],
        ];
        $GLOBALS['auth'] = $GLOBALS['auth_list']['Production'];
        file_put_contents(self::$authPath, "<?php\n\$baseUrl = " . var_export($GLOBALS['baseUrl'], true) . ";\n\$environment = 'Production';\n\$auth_list = " . var_export($GLOBALS['auth_list'], true) . ";\n\$mimirApi = '';\n");
        @unlink(self::$mockLog);

        try {
            $this->assertFalse(odata_mimir_enabled());
            $bcRows = odata_get_all($GLOBALS['baseUrl'] . '/Production/ODataV4/Companies?$select=Name', $GLOBALS['auth'], 30);
            $this->assertSame('bc', $bcRows[0]['via'] ?? null);
            $this->assertSame('bcuser', $bcRows[0]['user'] ?? null);

            $hitMimir = false;
            foreach (self::mockRequests() as $request) {
                if (str_contains((string) ($request['uri'] ?? ''), '/mimir/')) {
                    $hitMimir = true;
                }
            }
            $this->assertFalse($hitMimir);
        } finally {
            if (self::$authBackup === null && is_file(self::$authPath)) {
                @unlink(self::$authPath);
            }
            foreach (glob(dirname(__DIR__) . '/web/cache/odata/*.json') ?: [] as $cacheFile) {
                $raw = file_get_contents($cacheFile);
                if (is_string($raw) && str_contains($raw, '127.0.0.1:' . self::$port)) {
                    @unlink($cacheFile);
                }
            }
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function mockRequests(): array
    {
        if (!is_file(self::$mockLog)) {
            return [];
        }
        $rows = [];
        foreach (file(self::$mockLog, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
            $decoded = json_decode($line, true);
            if (is_array($decoded)) {
                $rows[] = $decoded;
            }
        }
        return $rows;
    }

    private static function writeMock(): void
    {
        $log = var_export(self::$mockLog, true);
        $php = <<<'PHP'
<?php
$log = LOG_PATH;
$uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
$method = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');
$authorization = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? '');
$bodyRaw = (string) file_get_contents('php://input');
file_put_contents($log, json_encode([
    'uri' => $uri,
    'method' => $method,
    'ua' => (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''),
    'authorization' => $authorization,
    'api_key' => (string) ($_SERVER['HTTP_X_API_KEY'] ?? ''),
], JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
header('Content-Type: application/json');

if (str_contains($uri, '/mimir-dup/api/companies.php')) {
    echo json_encode(['value' => [
        ['name' => 'Overlap BV', 'environment' => 'Production'],
        ['name' => 'Overlap BV', 'environment' => 'Sandbox'],
    ]]);
    exit;
}

if (str_contains($uri, '/mimir/api/companies.php')) {
    echo json_encode(['value' => [
        ['name' => 'Koninklijke van Twist', 'environment' => 'Production'],
        ['name' => "Van Twist's", 'environment' => 'Production'],
        ['name' => 'Hunter van Twist', 'environment' => 'Sandbox'],
    ]]);
    exit;
}

if (str_contains($uri, '/mimir/api/query.php') || str_contains($uri, '/mimir-dup/api/query.php')) {
    $body = json_decode($bodyRaw, true);
    if (!is_array($body)) {
        $body = [];
    }
    $row = [
        'company' => (string) ($body['company'] ?? ''),
        'table' => (string) ($body['table'] ?? ''),
        'select' => $body['select'] ?? [],
        'filter' => (string) ($body['filter'] ?? ''),
        'max_age' => $body['max_age'] ?? null,
        'request_top' => $body['top'] ?? null,
    ];
    echo json_encode(['value' => [
        ['No' => 'WO-1'] + $row,
        ['No' => 'WO-2'] + $row,
        ['No' => 'WO-3'] + $row,
    ]]);
    exit;
}

$user = (string) ($_SERVER['PHP_AUTH_USER'] ?? '');
if ($user === '' && str_starts_with($authorization, 'Basic ')) {
    $decoded = base64_decode(substr($authorization, 6), true);
    if (is_string($decoded) && str_contains($decoded, ':')) {
        $user = explode(':', $decoded, 2)[0];
    }
}
echo json_encode(['value' => [[
    'Name' => 'BC Company',
    'via' => 'bc',
    'user' => $user,
]]]);
PHP;
        file_put_contents(self::$mockScript, str_replace('LOG_PATH', $log, $php));
    }
}
