<?php
header('Content-Type: application/json');

set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) return;
    echo json_encode(["error" => "PHP Error: $message in line $line", "text" => ""]);
    exit;
});
// Faengt Errors ab und sendet sie als JSON, damit das Frontend nicht abstürzt

set_exception_handler(function($e) {
    echo json_encode(["error" => "Exception: " . $e->getMessage(), "text" => ""]);
    exit;
});
// Faengt Exceptions ab und sendet sie als JSON, damit das Frontend nicht abstürzt
// Beachte: Exceptions != Error

session_start();

require_once __DIR__ . '/../classes/Angriffsrichtung.php';
require_once __DIR__ . '/../classes/Blockrichtung.php';
require_once __DIR__ . '/../classes/Waffenart.php';
require_once __DIR__ . '/../classes/Charakter.php';
require_once __DIR__ . '/../classes/Spieler.php';
require_once __DIR__ . '/../classes/Gegner.php';
// Klassen und ENUMs importieren

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
// Gegner Konfig fuer leichte Anpassung

$spielerMaxHP = 250; 

$startInventar = [
    0 => 9999, // Faust -> Hat man halt und kann nicht aufgebraucht werden
    1 => 9999, // Dolch -> siehe geschmackloser Kommentar in ENUM
    2 => 5,    // Schwert
    3 => 2,    // Laser
    4 => 1     // Magie -> Ult basically => nur ein Mal zu benutzen
];
// Hier werden die Waffen IDs auf die Anzahl der Nutzungen gemappt (referenziert)
// z.B. ID 2 ist das Schwert und es bekommt 5 Verwendungen zugeordnet

$result = ["error" => "", "text" => "", "inventory" => []];
// Vorbereitung der Ausgabe an JSON

if (isset($_POST['cbAbsolutMode'])) $_SESSION['cbAbsolutMode'] = true;
elseif (isset($_POST)) $_SESSION['cbAbsolutMode'] = false;
$global__game_deterministic = $_SESSION['cbAbsolutMode'] ?? false;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_SESSION['player_hp']) || $_SESSION['player_hp'] <= 0 || !isset($_SESSION['stage'])) {
        $_SESSION['player_hp'] = $spielerMaxHP;
        $_SESSION['stage'] = 0;
        $_SESSION['enemy_hp'] = $gegnerListe[0]['max_hp'];
        $_SESSION['inventory'] = $startInventar; 
        $textPrefix = "<b>Neues Spiel!</b> Die Arena öffnet ihre Tore.<br>Du checkst deinen Rucksack...<br><hr>";
    } else {
        $textPrefix = "";
    }
    
    if ($_SESSION['stage'] >= count($gegnerListe)) {
        session_destroy();
        echo json_encode(["text" => "<b>Spiel vorbei!</b> Bitte Seite neu laden.", "finished" => true]);
        exit;
    }

    $postWaffe = $_POST['waffe'] ?? "";
    $auswahl_waffe = Waffenart::fromString($postWaffe);
    $auswahl_verteidigung = Blockrichtung::fromString($_POST['block'] ?? "");
    $auswahl_angriff = Angriffsrichtung::fromString($_POST['angriff'] ?? "");

    if (!$auswahl_waffe || !$auswahl_verteidigung || !$auswahl_angriff) {
        throw new Exception("Bitte alle Befehle erteilen!");
    }

    $waffenID = $auswahl_waffe->get_ID();
    $currentAmmo = $_SESSION['inventory'][$waffenID] ?? 0;

    if ($currentAmmo <= 0) {
        $auswahl_waffe = Waffenart::FAUST();
        $textPrefix .= "<i>Klick... Leer! Keine Munition mehr! Du nutzt deine Fäuste.</i><br>";
    } else {
        if ($_SESSION['inventory'][$waffenID] < 1000) {
            $_SESSION['inventory'][$waffenID]--;
        }
    }

    $currentStage = $_SESSION['stage'];
    $gegnerDaten = $gegnerListe[$currentStage];

    $spieler = new Spieler("Held", $_SESSION['player_hp'], 12, 1.2, 1.5);
    $spieler->setWaffenart($auswahl_waffe);
    $spieler->setBlockrichtung($auswahl_verteidigung);
    $spieler->setAngriffsrichtung($auswahl_angriff);

    $gegner = new Gegner($gegnerDaten['name'], $_SESSION['enemy_hp'], $gegnerDaten['str'], $gegnerDaten['dex'], $gegnerDaten['int']);
    
    // Zufällige Gegner-Waffe & Taktik
    $rndWaffe = $gegnerDaten['waffe'][array_rand($gegnerDaten['waffe'])];
    $gegner->setWaffenart($rndWaffe::fromID($rndWaffe->get_ID()));
    $gegner->setBlockrichtung(Blockrichtung::fromID(rand(0, 2)));
    $gegner->setAngriffsrichtung(Angriffsrichtung::fromID(rand(0, 2)));

    $log = $textPrefix;

    $schadA = $spieler->getAschadenAusAngriffswerte(); // Hier war vorher der Fehler ($schadA_Spieler)
    
    $schadRealG = $gegner->getVschadenAusVerteidigungswerte($schadA, $spieler->getAngriffsrichtung());
    
    $gegner->setLebenspunkte($gegner->getLebenspunkte() - $schadRealG);
    $_SESSION['enemy_hp'] = $gegner->getLebenspunkte();

    $log .= "Du nutzt <b>{$auswahl_waffe->get_DisplayName()}</b>.<br>";
    
    if (isset($spieler->hatGecritted) && $spieler->hatGecritted) {
        $log .= "<b style='color:orange'>🔥 KRITISCHER TREFFER!</b><br>";
    }

    $log .= "{$gegner->getName()} nimmt <b style='color:green'>{$schadRealG}</b> Schaden.<br>";

    if ($gegner->getLebenspunkte() <= 0) {
        $nextStage = $currentStage + 1;
        
        if (isset($gegnerListe[$nextStage])) {
            // Looten
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
        // Gegner lebt noch -> Rückschlag
        $log .= "<hr>";
        $schadAG = $gegner->getAschadenAusAngriffswerte();
        
        $schadRealS = $spieler->getVschadenAusVerteidigungswerte($schadAG, $gegner->getAngriffsrichtung());
        
        $spieler->setLebenspunkte($spieler->getLebenspunkte() - $schadRealS);
        $_SESSION['player_hp'] = $spieler->getLebenspunkte();

        $log .= "{$gegner->getName()} kontert mit {$gegner->getWaffenart()->get_DisplayName()}!<br>";
        
        // Gegner Crit Anzeige (falls Gegner auch critten kann)
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
    // Senden der rohen Zahlen, damit JS die Balken erstellen kann
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