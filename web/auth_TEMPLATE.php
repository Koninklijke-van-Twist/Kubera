<?php
/**
 * Sjabloon voor web/auth.php (niet committen; auth.php staat in .gitignore).
 *
 * Mímir eerst, en de BC-gegevens ernaast laten staan als automatische fallback
 * wanneer Mímir uitvalt:
 *   $mimirApi  = 'mimir_…';
 *   $mimirBase = 'https://sleutels.kvt.nl/mimir/api'; // optioneel
 *
 * Met $mimirApi gezet probeert Kubera eerst Mímir en valt terug op $baseUrl /
 * $environment / $auth_list / $auth hieronder. Zonder $mimirApi wordt alleen
 * dat BC-blok gebruikt. Overige secrets (mail, gebruikers) blijven zoals ze zijn.
 */

// --- Mímir ---
// $mimirApi  = 'mimir_…';
// $mimirBase = 'https://sleutels.kvt.nl/mimir/api';

// --- Business Central (direct pad, én fallback als Mímir faalt) ---
$auth_list = [
    'env1' => ['mode' => 'basic', 'user' => 'USERNAME', 'pass' => 'PASSWORD'],
];
$environment = 'env1';
$auth = $auth_list[$environment];
$baseUrl = 'https://my-bc-domain.com:7148/';
