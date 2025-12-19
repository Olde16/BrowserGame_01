<?php
// DATEI: classes/Waffenart.php

enum Waffenart {
    // Die verfügbaren Typen
    case SCHWERT;
    case DOLCH;
    case FAUST;
    case LASERSCHWERT;
    case MAGIE;

    // Gibt den Schaden zurück, der zu dieser Waffe gehört.
    public function get_schaden(): int {
        return match($this) {
            self::FAUST => 3,
            self::DOLCH => 7,
            self::SCHWERT => 15,
            self::LASERSCHWERT => 35,
            self::MAGIE => 120, // Spezialangriff (Ulti)
        };
    }

    // Gibt den Namen als schönen String zurück (für die Textausgabe).
    public function get_DisplayName(): string {
        return match($this) {
            self::FAUST => "Faust",
            self::DOLCH => "Dolch",
            self::SCHWERT => "Schwert",
            self::LASERSCHWERT => "Laserschwert",
            self::MAGIE => "Magie",
        };
    }

    // Gibt eine ID zurück (0-4), die wir für Arrays oder Dropdowns nutzen.
    public function get_ID(): int {
        return match($this) {
            self::FAUST => 0,
            self::DOLCH => 1,
            self::SCHWERT => 2,
            self::LASERSCHWERT => 3,
            self::MAGIE => 4,
        };
    }

    // Hilfsfunktion: Macht aus einem String ("Schwert") wieder ein Enum-Objekt.
    public static function fromString(string $name): ?self {
        $name = trim($name);
        foreach (self::cases() as $w) {
            if (strcasecmp($w->get_DisplayName(), $name) === 0 || strcasecmp($w->name, $name) === 0) {
                return $w;
            }
        }
        return null;
    }

    // Hilfsfunktion: Macht aus einer Zahl (2) wieder ein Enum-Objekt.
    public static function fromID(int $id): ?self {
        foreach (self::cases() as $w) {
            if ($w->get_ID() === $id) {
                return $w;
            }
        }
        return null;
    }
}
?>