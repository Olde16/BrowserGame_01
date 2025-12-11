<?php
session_start();

# Klassenimport
require_once __DIR__ . '/../classes/Angriffsrichtung.php';
require_once __DIR__ . '/../classes/Blockrichtung.php';
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

    $auswahl_waffe = Waffenart::fromString($_POST['waffe'] ?? ""); # Waffenauswahl
    $auswahl_verteidigung = Blockrichtung::fromID($_POST['blockrichtung_id'] ?? -1); # Verteidigungs Wahl
    $auswahl_angriff = Angriffsrichtung::fromID($_POST['angriffsrichtung_id'] ?? -1); # Angriffs Wahl

    if ($auswahl_waffe == "") { # Fehler in Fehlerfeld
        $result["error"] = "Bitte eine gültige Waffe auswählen.";
        echo json_encode($result);
        exit;
    }
    if ($auswahl_verteidigung == -1) { # Fehler in Fehlerfeld
        $result["error"] = "Bitte eine gültige Verteidigungsrichtung auswählen.";
        echo json_encode($result);
        exit;
    }
    if ($auswahl_angriff == -1) { # Fehler in Fehlerfeld
        $result["error"] = "Bitte eine gültige Angriffsrichtung auswählen.";
        echo json_encode($result);
        exit;
    }

    # Spieler & Gegner erstellen
    $spieler = new Spieler("Spieler1", 10, 30, 0.5, 0.5);
    $spieler->set_Waffenart($auswahl_waffe);
    $spieler->set_blockWahl($auswahl_verteidigung);
    $spieler->set_Angriffsrichtung($auswahl_angriff);

    $gegner = new Gegner("Dundun", 10, 30, 0.5, 0.5);
    $gegner->set_Waffenart(Waffenart::FAUST);
    $gegner->set_blockWahl(Blockrichtung::OBEN);
    $gegner->set_Angriffsrichtung(Angriffsrichtung::UNTEN);

    # Berechnung
    $schadA = $spieler->get_aschaden_aus_angriffswerte();
    $angrRichtung = $spieler->get_Angriffsrichtung();
    $schadV = $gegner->get_vschaden_aus_verteidigungswerte($schadA, $angrRichtung);

    $result["output"] = # Ergebnis in Textfeld
        "Du greifst mit <b>{$auswahl_waffe->get_bezeichnung()}</b> <b>{$auswahl_angriff->get_bezeichnung()}</b> an und machst <b>$schadA</b> Schaden. " .
        "Der Gegner blockt <b>{$gegner->get_blockWahl()->get_bezeichnung()}</b> und erleidet <b>$schadV</b> Schaden.";

    echo json_encode($result);
}

    // TODO:
    // - Gegner/Spieler Balance
    // - Namen anzeigen lassen
    // - Implementierung der userspezifischen Blockrichtung in UI
    // - HP Charakter und Gegner + Textausgabe
    // - Schaden nehmen von Charakters (erst Angriff dann Vert)
    // - Siegerermittlung und Ehrung
    // Weitere Gestaltung nach erfüllung der oben genannten anforderungen

?>