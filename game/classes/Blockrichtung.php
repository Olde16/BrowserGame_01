<?php

enum Blockrichtung {
    case OBEN;
    case UNTEN;
    case MITTE;
    // Drei Blockrichtungen

    public function get_DisplayName(): string {
        return match($this) {
            self::OBEN => "Oben",
            self::UNTEN => "Unten",
            self::MITTE => "Mitte",
        };
    }
    // Zur einheitlichen Ausgabe an Spieler

    public function get_ID(): int {
        return match($this) {
            self::OBEN => 0,
            self::UNTEN => 1,
            self::MITTE => 2,
        };
    }
    // Zur programm-internen Verarbeitung als IDs

    public static function fromString(string $name): ?self {
        $name = trim($name);
        foreach (self::cases() as $w) {
            // Vergleicht "Oben" (für Anzeige) ODER "OBEN" (Name) case-insensitive
            if (strcasecmp($w->get_DisplayName(), $name) === 0 || strcasecmp($w->name, $name) === 0) {
                return $w;
            }
        }
        return null;
    }
    // ENUM erkennung mittels String Darstellung oder Enum Eigenschaft

    public static function fromID(int $id): ?self {
        foreach (self::cases() as $w) {
            if ($w->get_ID() === $id) {
                return $w;
            }
        }
        return null;
    }
    // ENUM erkennung mittels interner ID
    
}