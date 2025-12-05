<?php
session_start();

# Klassenimport
require_once __DIR__ . '/../classes/Waffenart.php';
require_once __DIR__ . '/../classes/Charakter.php';
require_once __DIR__ . '/../classes/Spieler.php';
require_once __DIR__ . '/../classes/Gegner.php';

$result = [ # Ergebnis vorbereiten
    "error" => null,
    "text" => ""
];

$global__game_deterministic = $_SESSION['cbAbsolutMode'] ?? false; # Deterministischer Modus?

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $_SESSION['cbAbsolutMode'] = isset($_POST['cbAbsolutMode']);
    $global__game_deterministic = $_SESSION['cbAbsolutMode'];

    $auswahl = Waffenart::fromString($_POST['waffe'] ?? ""); # Waffenauswahl

    if (!$auswahl) { # Fehler in Fehlerfeld
        $result["error"] = "Bitte eine gültige Waffe auswählen.";
        echo json_encode($result);
        exit;
    }
    
    # Spieler & Gegner erstellen
    $spieler = new Spieler("Spieler1", 10, 30, 0.5, 0.5);
    $spieler->set_Waffenart($auswahl);

    $gegner = new Gegner("Dundun", 10, 30, 0.5, 0.5);
    $gegner->set_Waffenart(Waffenart::FAUST);
    $gegner->set_blockWahl("oben");

    # Berechnung
    $schadA = $spieler->get_aschaden_aus_angriffswerte();
    $schadV = $gegner->get_vschaden_aus_verteidigungswerte($schadA);

    $result["text"] = # Ergebnis in Textfeld
        "Du greifst mit <b>{$auswahl->get_bezeichnung()}</b> an und machst <b>$schadA</b> Schaden. " .
        "Der Gegner blockt und erleidet <b>$schadV</b> Schaden.";

    echo json_encode($result);
}

    // TODO:
    // - Gegner/Spieler Balance
    // - Namen anzeigen lassen
    //IA - Logik attr angriffsrichtung
    // - Implementierung der userspezifischen Blockrichtung
    // - HP Charakter und Gegner + Textausgabe
    // - Schaden nehmen von Charakters (erst Angriff dann Vert)
    // - Siegerermittlung und Ehrung
    // Weitere Gestaltung nach erfüllung der oben genannten anforderungen
    // Angriffsrichtung und Blockrichtung als Enum

?>