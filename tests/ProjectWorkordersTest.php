<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

define('TALOS_SKIP_PROJECT_WORKORDERS_PAGE_LOAD', true);
require_once __DIR__ . '/../web/content/project_workorders_page.php';

class ProjectWorkordersTest extends TestCase
{
    private string $departmentsCachePath = '';

    protected function setUp(): void
    {
        parent::setUp();

        if (!defined('TALOS_DEPARTMENTS_CACHE_PATH')) {
            $this->departmentsCachePath = sys_get_temp_dir() . '/talos_departments_cache_' . uniqid('', true) . '.json';
            define('TALOS_DEPARTMENTS_CACHE_PATH', $this->departmentsCachePath);
        }
    }

    protected function tearDown(): void
    {
        if ($this->departmentsCachePath !== '' && is_file($this->departmentsCachePath)) {
            @unlink($this->departmentsCachePath);
        }

        parent::tearDown();
    }

    public function testMapWorkordersByJobNoGroupsWithoutProjectRows(): void
    {
        $workorders = [
            [
                'No' => 'WO-20',
                'Job_No' => 'P-100',
                'Task_Description' => 'Tweede',
                'Job_Dimension_1_Value' => 'AFD-10',
            ],
            [
                'No' => 'WO-10',
                'Job_No' => 'P-100',
                'Task_Description' => 'Eerste',
            ],
            [
                'No' => 'WO-01',
                'Job_No' => 'P-200',
                'Task_Description' => 'Andere',
                'Job_Dimension_1_Value' => 'AFD-20',
            ],
        ];

        $grouped = mapWorkordersByJobNo($workorders);

        $this->assertCount(2, $grouped);
        $this->assertSame('P-100', $grouped[0]['no']);
        $this->assertSame('', $grouped[0]['description']);
        $this->assertSame('AFD-10', $grouped[0]['department_code']);
        $this->assertCount(2, $grouped[0]['workorders']);
        $this->assertSame('WO-10', $grouped[0]['workorders'][0]['no']);
        $this->assertSame('WO-20', $grouped[0]['workorders'][1]['no']);
    }

    public function testMapWorkordersByJobNoFallsBackToNoWhenJobNoMissing(): void
    {
        $workorders = [
            [
                'No' => 'P-300',
                'Job_No' => '',
                'Task_Description' => 'Fallback project',
            ],
        ];

        $mapped = mapWorkordersByJobNo($workorders);

        $this->assertCount(1, $mapped);
        $this->assertSame('P-300', $mapped[0]['no']);
        $this->assertCount(1, $mapped[0]['workorders']);
        $this->assertSame('P-300', $mapped[0]['workorders'][0]['no']);
    }

    public function testAppendDepartmentCodesToCacheWritesOnlyWhenNewCodesExist(): void
    {
        writeDepartmentCodesToCache(['A-01', 'B-01']);
        $initialPayload = (string) file_get_contents(getDepartmentsCachePath());

        appendDepartmentCodesToCache(['B-01']);
        $afterDuplicatePayload = (string) file_get_contents(getDepartmentsCachePath());

        $this->assertSame($initialPayload, $afterDuplicatePayload);

        appendDepartmentCodesToCache(['C-01']);
        $updatedCodes = readDepartmentCodesFromCache();

        $this->assertSame(['A-01', 'B-01', 'C-01'], $updatedCodes);
    }
}
