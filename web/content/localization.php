<?php

const FLAG_SVGS = [
    'nl' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 900 600"><rect width="900" height="600" fill="#AE1C28"/><rect width="900" height="400" fill="#fff"/><rect width="900" height="200" fill="#fff"/><rect width="900" height="200" y="0" fill="#AE1C28"/><rect width="900" height="200" y="200" fill="#fff"/><rect width="900" height="200" y="400" fill="#21468B"/></svg>',
    'en' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 40"><clipPath id="a"><path d="M0 0v40h60V0z"/></clipPath><clipPath id="b"><path d="M30 20h30v20zv20H0zH0V0zV0h30z"/></clipPath><g clip-path="url(#a)"><path d="M0 0v40h60V0z" fill="#012169"/><path d="M0 0l60 40m0-40L0 40" stroke="#fff" stroke-width="8"/><path d="M0 0l60 40m0-40L0 40" clip-path="url(#b)" stroke="#C8102E" stroke-width="5"/><path d="M30 0v40M0 20h60" stroke="#fff" stroke-width="13"/><path d="M30 0v40M0 20h60" stroke="#C8102E" stroke-width="8"/></g></svg>',
    'de' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 5 3"><rect width="5" height="3" y="0" fill="#000"/><rect width="5" height="2" y="1" fill="#D00"/><rect width="5" height="1" y="2" fill="#FFCE00"/></svg>',
    'fr' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 900 600"><rect width="900" height="600" fill="#ED2939"/><rect width="600" height="600" fill="#fff"/><rect width="300" height="600" fill="#002395"/></svg>',
];
const SUPPORTED_LANGUAGES = [
    'nl' => ['flag' => '🇳🇱', 'label' => 'Nederlands'],
    'en' => ['flag' => '🇬🇧', 'label' => 'English'],
    'de' => ['flag' => '🇩🇪', 'label' => 'Deutsch'],
    'fr' => ['flag' => '🇫🇷', 'label' => 'Français'],
];

/**
 * Constants
 */

const TRANSLATIONS = [
    'nl' => [
        'page.projects.title' => 'Kubera - Projecten en werkorders',
        'page.projects.heading' => 'Projecten en werkorders',
        'page.projects.intro' => 'Overzicht van alle gereed gemelde projecten met bijbehorende werkorders.',
        'table.job' => 'Project',
        'table.company' => 'Bedrijf',
        'table.description' => 'Omschrijving',
        'table.work_order' => 'Werkorder',
        'filter.company' => 'Bedrijf',
        'filter.apply' => 'Toon',
        'filter.all_companies' => 'Alle bedrijven',
        'filter.cost_center_code' => 'Afdeling',
        'filter.cost_center_code_all' => 'Alle afdelingen',
        'table.cost_center_code' => 'Afdeling',
        'msg.no_projects_found' => 'Geen werkorders gevonden met status 60-GEREED.',
        'msg.no_workorders_for_project' => 'Geen werkorders gevonden voor dit project met status 60-GEREED.',
        'progress.waiting' => 'Laden gestart...',
        'progress.done' => 'Laden voltooid.',
        'progress.failed' => 'Laden mislukt.',
        'progress.step_workorders' => 'Werkorders ophalen',
        'progress.step_projects' => 'Projecten ophalen',
        'section.ready_to_invoice_in' => 'Gereed om te Factureren in %s',
        'error.odata_failed' => 'Fout bij ophalen van projectfacturatiegegevens. Probeer het later opnieuw.',
    ],
    'en' => [
        'page.projects.title' => 'Kubera - Projects and work orders',
        'page.projects.heading' => 'Projects and work orders',
        'page.projects.intro' => 'Overview of all projects marked as ready with their related work orders.',
        'table.job' => 'Project',
        'table.company' => 'Company',
        'table.description' => 'Description',
        'table.work_order' => 'Work order',
        'filter.company' => 'Company',
        'filter.apply' => 'Show',
        'filter.all_companies' => 'All companies',
        'filter.cost_center_code' => 'Department',
        'filter.cost_center_code_all' => 'All departments',
        'table.cost_center_code' => 'Department',
        'msg.no_projects_found' => 'No work orders found with status 60-GEREED.',
        'msg.no_workorders_for_project' => 'No work orders found for this project with status 60-GEREED.',
        'progress.waiting' => 'Loading started...',
        'progress.done' => 'Loading completed.',
        'progress.failed' => 'Loading failed.',
        'progress.step_workorders' => 'Fetching work orders',
        'progress.step_projects' => 'Fetching projects',
        'section.ready_to_invoice_in' => 'Ready to invoice in %s',
        'error.odata_failed' => 'Error retrieving project invoicing data. Please try again later.',
    ],
    'de' => [
        'page.projects.title' => 'Kubera - Projekte und Arbeitsauftraege',
        'page.projects.heading' => 'Projekte und Arbeitsauftraege',
        'page.projects.intro' => 'Uebersicht aller als fertig gemeldeten Projekte mit zugehoerigen Arbeitsauftraegen.',
        'table.job' => 'Projekt',
        'table.company' => 'Unternehmen',
        'table.description' => 'Beschreibung',
        'table.work_order' => 'Arbeitsauftrag',
        'filter.company' => 'Unternehmen',
        'filter.apply' => 'Anzeigen',
        'filter.all_companies' => 'Alle Unternehmen',
        'filter.cost_center_code' => 'Abteilung',
        'filter.cost_center_code_all' => 'Alle Abteilungen',
        'table.cost_center_code' => 'Abteilung',
        'msg.no_projects_found' => 'Keine Arbeitsauftraege mit Status 60-GEREED gefunden.',
        'msg.no_workorders_for_project' => 'Keine Arbeitsauftraege fuer dieses Projekt mit Status 60-GEREED gefunden.',
        'progress.waiting' => 'Laden gestartet...',
        'progress.done' => 'Laden abgeschlossen.',
        'progress.failed' => 'Laden fehlgeschlagen.',
        'progress.step_workorders' => 'Arbeitsauftraege laden',
        'progress.step_projects' => 'Projekte laden',
        'section.ready_to_invoice_in' => 'Bereit zur Fakturierung in %s',
        'error.odata_failed' => 'Fehler beim Abrufen der Projektabrechnungsdaten. Bitte versuchen Sie es spaeter erneut.',
    ],
    'fr' => [
        'page.projects.title' => 'Kubera - Projets et ordres de travail',
        'page.projects.heading' => 'Projets et ordres de travail',
        'page.projects.intro' => 'Apercu de tous les projets marques prets avec leurs ordres de travail associes.',
        'table.job' => 'Projet',
        'table.company' => 'Societe',
        'table.description' => 'Description',
        'table.work_order' => 'Ordre',
        'filter.company' => 'Societe',
        'filter.apply' => 'Afficher',
        'filter.all_companies' => 'Toutes les societes',
        'filter.cost_center_code' => 'Departement',
        'filter.cost_center_code_all' => 'Tous les departements',
        'table.cost_center_code' => 'Departement',
        'msg.no_projects_found' => 'Aucun ordre de travail trouve avec le statut 60-GEREED.',
        'msg.no_workorders_for_project' => 'Aucun ordre de travail trouve pour ce projet avec le statut 60-GEREED.',
        'progress.waiting' => 'Chargement demarre...',
        'progress.done' => 'Chargement termine.',
        'progress.failed' => 'Chargement echoue.',
        'progress.step_workorders' => 'Chargement des ordres de travail',
        'progress.step_projects' => 'Chargement des projets',
        'section.ready_to_invoice_in' => 'Pret a facturer dans %s',
        'error.odata_failed' => 'Erreur lors de la recuperation des donnees de facturation projet. Veuillez reessayer plus tard.',
    ],
];

/**
 * Functies
 */

function getCurrentLanguage(): string
{
    $allowed = ['nl', 'en', 'de', 'fr'];

    if (isset($_GET['lang'])) {
        $requestedLang = trim((string) $_GET['lang']);
        if (in_array($requestedLang, $allowed, true)) {
            $userKey = (string) ($_SESSION['user']['email'] ?? 'anonymous');
            if (!isset($_SESSION['lang_by_user']) || !is_array($_SESSION['lang_by_user'])) {
                $_SESSION['lang_by_user'] = [];
            }

            $_SESSION['lang_by_user'][$userKey] = $requestedLang;
            // Backward compatibility for existing code/tests that read $_SESSION['lang'].
            $_SESSION['lang'] = $requestedLang;
        }
    }

    $userKey = (string) ($_SESSION['user']['email'] ?? 'anonymous');
    $userLang = (string) ($_SESSION['lang_by_user'][$userKey] ?? '');
    if (in_array($userLang, $allowed, true)) {
        return $userLang;
    }

    $legacyLang = (string) ($_SESSION['lang'] ?? 'nl');
    return in_array($legacyLang, $allowed, true) ? $legacyLang : 'nl';
}

function getLanguageFlagSvg(string $language): string
{
    return (string) (FLAG_SVGS[$language] ?? FLAG_SVGS['nl']);
}

function LOC(string $key, ...$args): string
{
    $lang = getCurrentLanguage();
    $string = TRANSLATIONS[$lang][$key] ?? TRANSLATIONS['nl'][$key] ?? $key;
    if (!empty($args)) {
        return sprintf($string, ...$args);
    }
    return $string;
}
