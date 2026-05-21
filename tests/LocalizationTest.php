<?php

declare(strict_types=1);
use PHPUnit\Framework\TestCase;

class LocalizationTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset session lang to default
        $_SESSION['lang'] = 'nl';
    }

    public function testLocReturnsNlByDefault(): void
    {
        $result = LOC('page.projects.title');
        $this->assertSame('Kubera - Projecten en werkorders', $result);
    }

    public function testLocReturnsEnglish(): void
    {
        $_SESSION['lang'] = 'en';
        $result = LOC('page.projects.title');
        $this->assertSame('Kubera - Projects and work orders', $result);
    }

    public function testLocReturnsDeutsch(): void
    {
        $_SESSION['lang'] = 'de';
        $result = LOC('page.projects.title');
        $this->assertSame('Kubera - Projekte und Arbeitsauftraege', $result);
    }

    public function testLocReturnsFrench(): void
    {
        $_SESSION['lang'] = 'fr';
        $result = LOC('page.projects.title');
        $this->assertSame('Kubera - Projets et ordres de travail', $result);
    }

    public function testLocFallsBackToNlForUnknownLang(): void
    {
        $_SESSION['lang'] = 'xx';
        $result = LOC('page.projects.title');
        $this->assertSame('Kubera - Projecten en werkorders', $result);
    }

    public function testLocReturnsKeyForMissingTranslation(): void
    {
        $_SESSION['lang'] = 'nl';
        $result = LOC('nonexistent.key');
        $this->assertSame('nonexistent.key', $result);
    }

    public function testLocFormatsSprintfArgs(): void
    {
        $_SESSION['lang'] = 'nl';
        $result = LOC('section.ready_to_invoice_in', 'KVT');
        $this->assertSame('Gereed om te Factureren in KVT', $result);
    }

    public function testAllKeysExistInAllLanguages(): void
    {
        $languages = ['nl', 'en', 'de', 'fr'];
        $nlKeys = array_keys(TRANSLATIONS['nl']);

        foreach ($languages as $lang) {
            foreach ($nlKeys as $key) {
                $this->assertArrayHasKey(
                    $key,
                    TRANSLATIONS[$lang],
                    "Sleutel '$key' ontbreekt in taal '$lang'"
                );
            }
        }
    }

    public function testActiveProjectWorkordersLabelsExistInDutch(): void
    {
        $_SESSION['lang'] = 'nl';

        $this->assertSame('Project', LOC('table.job'));
        $this->assertSame('Afdeling', LOC('table.cost_center_code'));
        $this->assertSame('Werkorder', LOC('table.work_order'));
        $this->assertSame('Geen werkorders gevonden met status 60-GEREED.', LOC('msg.no_projects_found'));
    }
}
