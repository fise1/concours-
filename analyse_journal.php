<?php
/**
 * Script d'analyse du journal de production
 * Usage: php analyse_journal.php journal_production.log
 */

$logFile = $argv[1] ?? 'journal_production.log';

if (!file_exists($logFile)) {
    die("Erreur : Le fichier journal '$logFile' est introuvable.\n");
}

$lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$tablesAttribuees = [];
$tableDoublons = [];
$inscriptionsParNpiConcours = [];
$inscriptionsDoublons = 0;
$lenteursIdentite = 0;
$periodeLenteurDebut = null;
$periodeLenteurFin = null;
$refusAgeTor = 0;
$tempsReponseListes = [];

foreach ($lines as $line) {
    // 1. Horodatage & Conversion UTC vers Heure du Bénin (UTC+1)
    $beninTimestamp = null;
    if (preg_match('/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}Z)/', $line, $matches)) {
        $utcTime = new DateTime($matches[1], new DateTimeZone('UTC'));
        $utcTime->setTimezone(new DateTimeZone('Africa/Porto-Novo'));
        $beninTimestamp = $utcTime->format('Y-m-d H:i:s');
    }

    // 2. Détection des lenteurs / blocages du service d'identité (>= 5000 ms) - INC-206
    if (strpos($line, 'WARN') !== false && strpos($line, 'appel identite') !== false) {
        if (preg_match('/\((\d+)\s*ms\)/', $line, $m)) {
            $duration = (int)$m[1];
            if ($duration >= 5000) {
                $lenteursIdentite++;
                if (!$periodeLenteurDebut) {
                    $periodeLenteurDebut = $beninTimestamp;
                }
                $periodeLenteurFin = $beninTimestamp;
            }
        }
    }

    // 3. Analyse des inscriptions POST réussies (Code HTTP 201)
    if (strpos($line, 'POST /api/inscriptions') !== false && strpos($line, '201') !== false) {
        preg_match('/concours=([A-Z0-9]+)/', $line, $cMatch);
        preg_match('/npi=(\d+)/', $line, $npiMatch);
        preg_match('/numero=([A-Z0-9\-]+)/', $line, $numMatch);

        $concours = $cMatch[1] ?? '';
        $npi = $npiMatch[1] ?? '';
        $numero = $numMatch[1] ?? '';

        // Détection des doublons de numéros de table (INC-201)
        if ($numero) {
            $tablesAttribuees[$numero] = ($tablesAttribuees[$numero] ?? 0) + 1;
        }

        // Détection des tentatives de double inscription (INC-203)
        if ($npi && $concours) {
            $key = "$npi-$concours";
            if (isset($inscriptionsParNpiConcours[$key])) {
                $inscriptionsDoublons++;
            }
            $inscriptionsParNpiConcours[$key] = true;
        }
    }

    // 4. Candidats refusés pour motif d'âge limite (INC-205)
    if (strpos($line, 'Âge limite dépassé') !== false) {
        $refusAgeTor++;
    }

    // 5. Analyse des performances d'affichage des listes (INC-207)
    if (strpos($line, 'GET /api/centres/') !== false && strpos($line, 'inscrits') !== false) {
        if (preg_match('/200\s+\((\d+)\s*ms\)/', $line, $m)) {
            $tempsReponseListes[] = (int)$m[1];
        }
    }
}

// Extraction des numéros de table attribués plusieurs fois
foreach ($tablesAttribuees as $num => $count) {
    if ($count > 1) {
        $tableDoublons[$num] = $count;
    }
}

// Statistiques de performance
$totalConsultations = count($tempsReponseListes);
$dureeMoyenneListe = $totalConsultations > 0 ? array_sum($tempsReponseListes) / $totalConsultations : 0;
$dureeMaxListe = $totalConsultations > 0 ? max($tempsReponseListes) : 0;

// Affichage du rapport
echo "=====================================================\n";
echo "  RAPPORT D'ANALYSE DU JOURNAL (Heure du Bénin - UTC+1)\n";
echo "=====================================================\n";
echo "• Numéros de table attribués en doublon : " . count($tableDoublons) . "\n";
echo "• Tentatives d'inscriptions multiples détectées : $inscriptionsDoublons\n";
echo "• Appels au service d'identité >= 5s : $lenteursIdentite\n";
if ($periodeLenteurDebut) {
    echo "  - Début de la période critique : $periodeLenteurDebut\n";
    echo "  - Fin de la période critique   : $periodeLenteurFin\n";
}
echo "• Candidats refusés (motif âge limite) : $refusAgeTor\n";
echo "• Temps moyen d'affichage des listes : " . round($dureeMoyenneListe, 2) . " ms (Max: {$dureeMaxListe} ms)\n";
echo "=====================================================\n";