<?php

enum Waffenart {
    case SCHWERT;
    case DOLCH;
    case FAUST;
    case LASERSCHWERT;
    case MAGIE;
    // Ohne Waffen kein Game

    public function get_schaden(): int {
        return match($this) {
            self::FAUST => 3,           // Digga muss das...
            self::DOLCH => 7,           // Irgendwo findet sich immer ein Messer...
            self::SCHWERT => 15,        // Solid.
            self::LASERSCHWERT => 35,   // Joda sein ich bin nicht... Kaffee ich brauchen.
            self::MAGIE => 120,         // Nanu wo kam das her...
        };
    }
    // Wir entschuldigen uns fuer den cringen Humor unseres Kommentators...
    // Hier werden - wenn auch geschmacklos kommentiert - die Schadenswerte für die Waffen festgelegt
    
    public function get_DisplayName(): string {
        return match($this) {
            self::FAUST => "Faust",
            self::DOLCH => "Dolch",
            self::SCHWERT => "Schwert",
            self::LASERSCHWERT => "Laserschwert",
            self::MAGIE => "Magie",
        };
    }
    // Zur einheitlichen Ausgabe an Spieler

    public function get_ID(): int {
        return match($this) {
            self::FAUST => 0,
            self::DOLCH => 1,
            self::SCHWERT => 2,
            self::LASERSCHWERT => 3,
            self::MAGIE => 4,
        };
    }
    // Zur programm-internen Verarbeitung als IDs

    public static function fromString(string $name): ?self {
        $name = trim($name);
        foreach (self::cases() as $w) {
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
?>