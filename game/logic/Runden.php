<?php
header('Content-Type: application/json');

// --- Error Handling ---
// Fängt Fehler ab und sendet sie als JSON, damit das Frontend nicht abstürzt
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) return;
    echo json_encode(["error" => "PHP Error: $message in line $line", "text" => ""]);
    exit;
});

set_exception_handler(function($e) {
    echo json_encode(["error" => "Exception: " . $e->getMessage(), "text" => ""]);
    exit;
});

session_start();

// --- Importe ---
require_once __DIR__ . '/../classes/Angriffsrichtung.php';
require_once __DIR__ . '/../classes/Blockrichtung.php';
require_once __DIR__ . '/../classes/Waffenart.php';
require_once __DIR__ . '/../classes/Charakter.php';
require_once __DIR__ . '/../classes/Spieler.php';
require_once __DIR__ . '/../classes/Gegner.php';

// --- KONFIGURATION ---
$gegnerListe = [
    0 => [
        'name' => 'Dundun', 'max_hp' => 100,
        'str' => 6, 'dex' => 1.0, 'int' => 1.0,
        'waffe' => [Waffenart::FAUST, Waffenart::DOLCH],
        'loot' => ['id' => 2, 'amount' => 5, 'name' => 'Schwert'] 
    ],
    1 => [
        'name' => 'Dandadan', 'max_hp' => 180,
        'str' => 10, 'dex' => 1.4, 'int' => 1.5,
        'waffe' => [Waffenart::DOLCH, Waffenart::SCHWERT],
        'loot' => ['id' => 3, 'amount' => 4, 'name' => 'Laserschwert'] 
    ],
    2 => [
        'name' => 'Mukbang (Endboss)', 'max_hp' => 400,
        'str' => 20, 'dex' => 0.9, 'int' => 2.0,
        'waffe' => [Waffenart::SCHWERT, Waffenart::LASERSCHWERT],
        'loot' => ['id' => 4, 'amount' => 1, 'name' => 'Magie'] 
    ]
];

$spielerMaxHP = 250; 

// Start-Inventar (Waffen-ID => Anzahl Nutzungen)
$startInventar = [
    0 => 9999, // Faust
    1 => 9999, // Dolch
    2 => 5,    // Schwert
    3 => 2,    // Laser
    4 => 1     // Magie
];

$result = ["error" => "", "text" => "", "inventory" => []];

// Deterministischer Modus
if (isset($_POST['cbAbsolutMode'])) $_SESSION['cbAbsolutMode'] = true;
elseif (isset($_POST)) $_SESSION['cbAbsolutMode'] = false;
$global__game_deterministic = $_SESSION['cbAbsolutMode'] ?? false;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- 1. SETUP & RESET ---
    if (!isset($_SESSION['player_hp']) || $_SESSION['player_hp'] <= 0 || !isset($_SESSION['stage'])) {
        $_SESSION['player_hp'] = $spielerMaxHP;
        $_SESSION['stage'] = 0;
        $_SESSION['enemy_hp'] = $gegnerListe[0]['max_hp'];
        $_SESSION['inventory'] = $startInventar; 
        $textPrefix = "<b>Neues Spiel!</b> Die Arena öffnet ihre Tore.<br>Du checkst deinen Rucksack...<br><hr>";
    } else {
        $textPrefix = "";
    }
    
    // Nach Sieg Reset prüfen
    if ($_SESSION['stage'] >= count($gegnerListe)) {
        session_destroy();
        echo json_encode(["text" => "<b>Spiel vorbei!</b> Bitte Seite neu laden.", "finished" => true]);
        exit;
    }

    // --- 2. EINGABEN & INVENTAR PRÜFUNG ---
    $postWaffe = $_POST['waffe'] ?? "";
    $auswahl_waffe = Waffenart::fromString($postWaffe);
    $auswahl_verteidigung = Blockrichtung::fromString($_POST['block'] ?? "");
    $auswahl_angriff = Angriffsrichtung::fromString($_POST['angriff'] ?? "");

    if (!$auswahl_waffe || !$auswahl_verteidigung || !$auswahl_angriff) {
        throw new Exception("Bitte alle Befehle erteilen!");
    }

    // Munition prüfen!
    $waffenID = $auswahl_waffe->get_ID();
    $currentAmmo = $_SESSION['inventory'][$waffenID] ?? 0;

    if ($currentAmmo <= 0) {
        $auswahl_waffe = Waffenart::FAUST(); // Fallback auf Faust
        $textPrefix .= "<i>Klick... Leer! Keine Munition mehr! Du nutzt deine Fäuste.</i><br>";
    } else {
        // Munition abziehen (außer bei Unendlich-Waffen)
        if ($_SESSION['inventory'][$waffenID] < 1000) {
            $_SESSION['inventory'][$waffenID]--;
        }
    }

    // --- 3. KAMPF START ---
    $currentStage = $_SESSION['stage'];
    $gegnerDaten = $gegnerListe[$currentStage];

    // Spieler erstellen (Werte leicht erhöht für Fairness)
    $spieler = new Spieler("Held", $_SESSION['player_hp'], 12, 1.2, 1.5);
    $spieler->setWaffenart($auswahl_waffe);
    $spieler->setBlockrichtung($auswahl_verteidigung);
    $spieler->setAngriffsrichtung($auswahl_angriff);

    // Gegner erstellen
    $gegner = new Gegner($gegnerDaten['name'], $_SESSION['enemy_hp'], $gegnerDaten['str'], $gegnerDaten['dex'], $gegnerDaten['int']);
    
    // Zufällige Gegner-Waffe & Taktik
    $rndWaffe = $gegnerDaten['waffe'][array_rand($gegnerDaten['waffe'])];
    $gegner->setWaffenart($rndWaffe::fromID($rndWaffe->get_ID()));
    $gegner->setBlockrichtung(Blockrichtung::fromID(rand(0, 2)));
    $gegner->setAngriffsrichtung(Angriffsrichtung::fromID(rand(0, 2)));


    // --- 4. BERECHNUNG ---
    $log = $textPrefix;

    // A) Spieler greift an
    $schadA = $spieler->getAschadenAusAngriffswerte(); // Hier war vorher der Fehler ($schadA_Spieler)
    
    // Verteidigung berechnen
    $schadRealG = $gegner->getVschadenAusVerteidigungswerte($schadA, $spieler->getAngriffsrichtung());
    
    // HP abziehen
    $gegner->setLebenspunkte($gegner->getLebenspunkte() - $schadRealG);
    $_SESSION['enemy_hp'] = $gegner->getLebenspunkte();

    // Log schreiben
    $log .= "Du nutzt <b>{$auswahl_waffe->get_DisplayName()}</b>.<br>";
    
    // Crit Anzeige (Prüfung auf Property aus Charakter.php)
    if (isset($spieler->hatGecritted) && $spieler->hatGecritted) {
        $log .= "<b style='color:orange'>🔥 KRITISCHER TREFFER!</b><br>";
    }

    $log .= "{$gegner->getName()} nimmt <b style='color:green'>{$schadRealG}</b> Schaden.<br>";


    // B) Gegner tot?
    if ($gegner->getLebenspunkte() <= 0) {
        $nextStage = $currentStage + 1;
        
        if (isset($gegnerListe[$nextStage])) {
            // LOOTEN!
            $loot = $gegnerDaten['loot'];
            $_SESSION['inventory'][$loot['id']] += $loot['amount'];

            // Heilen (30%)
            $heilung = (int)($spielerMaxHP * 0.30);
            $_SESSION['player_hp'] = min($spielerMaxHP, $spieler->getLebenspunkte() + $heilung);
            
            // Next Stage Setup
            $_SESSION['stage'] = $nextStage;
            $_SESSION['enemy_hp'] = $gegnerListe[$nextStage]['max_hp'];

            $log .= "<br><b>🏆 {$gegner->getName()} besiegt!</b><br>";
            $log .= "<span style='color:orange'>Loot: Du findest {$loot['amount']}x {$loot['name']}!</span><br>";
            $log .= "<span style='color:blue'>Heilung: +{$heilung} HP.</span><br>";
            $log .= "<hr><b>BOSS-ALARM:</b> {$gegnerListe[$nextStage]['name']} erscheint!";
        } else {
            // Gesamtsieg
            $log .= "<br><h1 style='color:gold'>👑 MUKBANG BESIEGT! 👑</h1>Du bist der Champion!";
            session_destroy();
        }
    } else {
        // C) Gegner lebt noch -> Rückschlag
        $log .= "<hr>";
        $schadAG = $gegner->getAschadenAusAngriffswerte();
        
        $schadRealS = $spieler->getVschadenAusVerteidigungswerte($schadAG, $gegner->getAngriffsrichtung());
        
        $spieler->setLebenspunkte($spieler->getLebenspunkte() - $schadRealS);
        $_SESSION['player_hp'] = $spieler->getLebenspunkte();

        $log .= "{$gegner->getName()} kontert mit {$gegner->getWaffenart()->get_DisplayName()}!<br>";
        
        // Optional: Gegner Crit Anzeige (falls Gegner auch critten kann)
        if (isset($gegner->hatGecritted) && $gegner->hatGecritted) {
            $log .= "<b style='color:red'>💥 GEGNER CRIT!</b><br>";
        }

        $log .= "Du nimmst <b style='color:red'>{$schadRealS}</b> Schaden.<br>";
        
        if ($spieler->getLebenspunkte() <= 0) {
            $log .= "<br><b>💀 GAME OVER</b>";
            session_destroy();
        }
    }

    $result["text"] = $log;
    
    // Daten für Frontend (Inventar Update)
    $result["inventory"] = $_SESSION['inventory'];
    
    // Daten für Frontend (Lebensbalken Update)
    // Wir senden die rohen Zahlen, damit JS die Balken malen kann
    $result["status"] = [
        "p_hp" => max(0, $_SESSION['player_hp'] ?? 0),
        "p_max" => $spielerMaxHP,
        "e_hp" => max(0, $_SESSION['enemy_hp'] ?? 0),
        "e_max" => $gegnerListe[$currentStage]['max_hp'],
        "e_name" => $gegnerListe[$currentStage]['name']
    ];

    echo json_encode($result);
}
?>