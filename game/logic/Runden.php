<?php
// DATEI: logic/Runden.php

// Zusatz Funktionen
// Pakt mit dem Teufel Mechanik, Loot basiertes Inverntar, Headshot (Crit), Reset Knopf


// Header setzen, damit der Browser weiß: Hier kommt JSON (Daten), kein HTML.
header('Content-Type: application/json');

// Fehlerbehandlung: Verhindert weiße Seite bei PHP-Fehlern
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) return;
    echo json_encode(["error" => "PHP Error: $message line $line", "text" => ""]);
    exit;
});
set_exception_handler(function($e) { echo json_encode(["error" => "Exception: " . $e->getMessage(), "text" => ""]); exit; });

session_start(); // Startet die Sitzung (Speicher)

// Alle Klassen laden
require_once __DIR__ . '/../classes/Angriffsrichtung.php';
require_once __DIR__ . '/../classes/Blockrichtung.php';
require_once __DIR__ . '/../classes/Waffenart.php';
require_once __DIR__ . '/../classes/Charakter.php';
require_once __DIR__ . '/../classes/Spieler.php';
require_once __DIR__ . '/../classes/Gegner.php';

// Konfiguration der Gegner
$gegnerListe = [
    0 => ['name' => 'Dundun', 'max_hp' => 100, 'str' => 6, 'dex' => 1.0, 'int' => 1.0, 
          'waffe' => [Waffenart::FAUST, Waffenart::DOLCH], 'loot' => ['id' => 2, 'amount' => 5, 'name' => 'Schwert']],
    1 => ['name' => 'Dandadan', 'max_hp' => 180, 'str' => 10, 'dex' => 1.4, 'int' => 1.5, 
          'waffe' => [Waffenart::DOLCH, Waffenart::SCHWERT], 'loot' => ['id' => 3, 'amount' => 4, 'name' => 'Laserschwert']],
    2 => ['name' => 'Mukbang (Endboss)', 'max_hp' => 400, 'str' => 20, 'dex' => 0.9, 'int' => 2.0, 
          'waffe' => [Waffenart::SCHWERT, Waffenart::LASERSCHWERT], 'loot' => ['id' => 4, 'amount' => 1, 'name' => 'Magie']]
];
$spielerMaxHP = 250; 
$startInventar = [0 => 9999, 1 => 9999, 2 => 5, 3 => 2, 4 => 1];

// Deterministischer Modus ist fest an
$_SESSION['cbAbsolutMode'] = true;
$global__game_deterministic = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- FEATURE: RESET ---
    // Wenn "Reset" gedrückt wurde, alles zurücksetzen
    if (isset($_POST['reset'])) {
        $_SESSION['player_hp'] = $spielerMaxHP;
        $_SESSION['stage'] = 0;
        $_SESSION['enemy_hp'] = $gegnerListe[0]['max_hp'];
        $_SESSION['inventory'] = $startInventar; 
        $_SESSION['pact_used'] = false; // Pakt wieder erlauben
        
        echo json_encode([
            "text" => "<b>🔄 Spiel wurde zurückgesetzt!</b><br>Alles auf Anfang.",
            "inventory" => $_SESSION['inventory'],
            "pact_used" => false,
            "status" => [
                "p_hp" => $spielerMaxHP, "p_max" => $spielerMaxHP,
                "e_hp" => $_SESSION['enemy_hp'], "e_max" => $gegnerListe[0]['max_hp'],
                "e_name" => $gegnerListe[0]['name']
            ]
        ]);
        exit; // Wichtig: Hier aufhören
    }

    // --- INITIALISIERUNG ---
    if (!isset($_SESSION['player_hp']) || $_SESSION['player_hp'] <= 0 || !isset($_SESSION['stage'])) {
        $_SESSION['player_hp'] = $spielerMaxHP;
        $_SESSION['stage'] = 0;
        $_SESSION['enemy_hp'] = $gegnerListe[0]['max_hp'];
        $_SESSION['inventory'] = $startInventar;
        $_SESSION['pact_used'] = false;
        $textPrefix = "<b>Neues Spiel!</b> Strategie-Modus aktiv.<br><hr>";
    } else { $textPrefix = ""; }
    
    // Check ob Spiel vorbei
    if ($_SESSION['stage'] >= count($gegnerListe)) {
        session_destroy();
        echo json_encode(["text" => "<b>Spiel vorbei!</b>", "finished" => true]);
        exit;
    }
    $currentStage = $_SESSION['stage'];

    // --- FEATURE: PAKT MIT DEM TEUFEL ---
    if (isset($_POST['gambleOption']) && $_POST['gambleOption'] == "1") {
        
        if (isset($_SESSION['pact_used']) && $_SESSION['pact_used'] === true) {
            echo json_encode(["error" => "Pakt bereits genutzt!"]); exit;
        }
        $_SESSION['pact_used'] = true;

        $log = $textPrefix . "<b>😈 Pakt mit dem Teufel...</b><br>";
        if (rand(1, 100) > 50) {
            // Gewinn
            $heilung = 150;
            $_SESSION['player_hp'] = min($spielerMaxHP, $_SESSION['player_hp'] + $heilung);
            $_SESSION['inventory'][2] += 5; // Schwerter
            $_SESSION['inventory'][3] += 2; // Laser
            $log .= "<b style='color:green'>ERFOLG!</b> +{$heilung} HP & Loot.";
        } else {
            // Verlust
            $schaden = 100;
            $_SESSION['player_hp'] -= $schaden;
            $log .= "<b style='color:red'>VERDAMMT!</b> -{$schaden} HP (Seelenraub).";
        }

        // Tod durch Pakt?
        if ($_SESSION['player_hp'] <= 0) {
            $log .= "<br><br><b>💀 GAME OVER.</b>";
            session_destroy();
        } else {
            $log .= "<br><i>(Runde übersprungen)</i>";
        }

        echo json_encode([
            "text" => $log,
            "inventory" => $_SESSION['inventory'],
            "pact_used" => true,
            "status" => [
                "p_hp" => max(0, $_SESSION['player_hp']), "p_max" => $spielerMaxHP,
                "e_hp" => max(0, $_SESSION['enemy_hp']), "e_max" => $gegnerListe[$currentStage]['max_hp'],
                "e_name" => $gegnerListe[$currentStage]['name']
            ]
        ]);
        exit;
    }

    // --- NORMALE RUNDE START ---
    
    // 1. Eingaben holen
    $postWaffe = $_POST['waffe'] ?? "";
    $auswahl_waffe = Waffenart::fromString($postWaffe);
    $auswahl_verteidigung = Blockrichtung::fromString($_POST['block'] ?? "");
    $auswahl_angriff = Angriffsrichtung::fromString($_POST['angriff'] ?? ""); 

    if (!$auswahl_waffe || !$auswahl_verteidigung || !$auswahl_angriff) { throw new Exception("Bitte alle Befehle erteilen!"); }

    // 2. Munition prüfen
    $waffenID = $auswahl_waffe->get_ID();
    if (($_SESSION['inventory'][$waffenID] ?? 0) <= 0) {
        $auswahl_waffe = Waffenart::FAUST();
        $textPrefix .= "<i>Keine Munition! Faust benutzt.</i><br>";
    } else {
        if ($_SESSION['inventory'][$waffenID] < 1000) $_SESSION['inventory'][$waffenID]--;
    }

    // 3. Charaktere aufbauen
    $gegnerDaten = $gegnerListe[$currentStage];
    $spieler = new Spieler("Held", $_SESSION['player_hp'], 12, 1.2, 1.5);
    $spieler->setWaffenart($auswahl_waffe);
    $spieler->setBlockrichtung($auswahl_verteidigung);
    $spieler->setAngriffsrichtung($auswahl_angriff);

    $gegner = new Gegner($gegnerDaten['name'], $_SESSION['enemy_hp'], $gegnerDaten['str'], $gegnerDaten['dex'], $gegnerDaten['int']);
    
    // KI Aktionen
    $rndWaffe = $gegnerDaten['waffe'][array_rand($gegnerDaten['waffe'])];
    $gegner->setWaffenart($rndWaffe::fromID($rndWaffe->get_ID()));
    $gegner->setBlockrichtung(Blockrichtung::fromID(rand(0, 2)));
    $gegner->setAngriffsrichtung(Angriffsrichtung::fromID(rand(0, 2))); 

    // 4. Kampfablauf
    $log = $textPrefix;
    
    // A) Spieler Angriff
    $schadA = $spieler->getAschadenAusAngriffswerte(); 
    $schadRealG = $gegner->getVschadenAusVerteidigungswerte($schadA, $spieler->getAngriffsrichtung());
    $gegner->setLebenspunkte($gegner->getLebenspunkte() - $schadRealG);
    $_SESSION['enemy_hp'] = $gegner->getLebenspunkte();

    // Log A
    $angriffsText = ($auswahl_angriff == Angriffsrichtung::OBEN) ? "Kopfschlag (Oben)" : $auswahl_angriff->get_DisplayName();
    $log .= "Du greifst an: <b>{$auswahl_waffe->get_DisplayName()} ({$angriffsText})</b>.<br>";
    
    if ($spieler->hatGecritted) { $log .= "<b style='color:orange'>🔥 KRITISCHER TREFFER!</b><br>"; }
    if ($gegner->hatAusgewichen) { $log .= "<i>Gegner weicht teilweise aus!</i><br>"; }
    $log .= "{$gegner->getName()} nimmt <b style='color:green'>{$schadRealG}</b> Schaden.<br>";

    // B) Gegner besiegt?
    if ($gegner->getLebenspunkte() <= 0) {
        $nextStage = $currentStage + 1;
        if (isset($gegnerListe[$nextStage])) {
            $loot = $gegnerDaten['loot'];
            $_SESSION['inventory'][$loot['id']] += $loot['amount'];
            $heilung = (int)($spielerMaxHP * 0.30);
            $_SESSION['player_hp'] = min($spielerMaxHP, $spieler->getLebenspunkte() + $heilung);
            $_SESSION['stage'] = $nextStage;
            $_SESSION['enemy_hp'] = $gegnerListe[$nextStage]['max_hp'];

            $log .= "<br><b>🏆 {$gegner->getName()} besiegt!</b><br>";
            $log .= "<span style='color:orange'>Loot: {$loot['amount']}x {$loot['name']}!</span><br>";
            $log .= "<hr><b>ACHTUNG:</b> {$gegnerListe[$nextStage]['name']} erscheint!";
        } else {
            $log .= "<br><h1 style='color:gold'>👑 SIEG!</h1>Du hast alle Gegner besiegt!";
            session_destroy();
        }
    } else {
        // C) Gegner Konter
        $log .= "<hr>";
        $schadAG = $gegner->getAschadenAusAngriffswerte();
        $schadRealS = $spieler->getVschadenAusVerteidigungswerte($schadAG, $gegner->getAngriffsrichtung());
        $spieler->setLebenspunkte($spieler->getLebenspunkte() - $schadRealS);
        $_SESSION['player_hp'] = $spieler->getLebenspunkte();

        $log .= "{$gegner->getName()} kontert!<br>";
        if ($gegner->hatGecritted) { $log .= "<b style='color:red'>💥 GEGNER CRIT!</b><br>"; }
        if ($spieler->hatAusgewichen) { $log .= "<b style='color:blue'>✨ LUCKY DODGE!</b> Du weichst aus!<br>"; }
        $log .= "Du nimmst <b style='color:red'>{$schadRealS}</b> Schaden.<br>";
        
        if ($spieler->getLebenspunkte() <= 0) {
            $log .= "<br><b>💀 NIEDERLAGE</b>";
            session_destroy();
        }
    }

    echo json_encode([
        "text" => $log,
        "inventory" => $_SESSION['inventory'],
        "pact_used" => $_SESSION['pact_used'] ?? false,
        "status" => [
            "p_hp" => max(0, $_SESSION['player_hp']), "p_max" => $spielerMaxHP,
            "e_hp" => max(0, $_SESSION['enemy_hp']), "e_max" => $gegnerListe[$currentStage]['max_hp'],
            "e_name" => $gegnerListe[$currentStage]['name']
        ]
    ]);
}
?>