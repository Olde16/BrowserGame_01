<?php

enum Waffenart {
    case SCHWERT;
    case DOLCH;
    case FAUST;
    case LASERSCHWERT;
    case MAGIE;

    public function get_schaden(): int {
        return match($this) {
            self::FAUST => 3,           // Kleiner Buff (war 2)
            self::DOLCH => 7,           // Kleiner Buff (war 5)
            self::SCHWERT => 15,        // Solider Schaden (war 10)
            self::LASERSCHWERT => 35,   // Stark! (war 18)
            self::MAGIE => 120,         // BOOM! Echte Ulti (war 25)
        };
    }

    // ... (Der Rest der Datei bleibt gleich wie vorher) ...
    
    public function get_DisplayName(): string {
        return match($this) {
            self::FAUST => "Faust",
            self::DOLCH => "Dolch",
            self::SCHWERT => "Schwert",
            self::LASERSCHWERT => "Laserschwert",
            self::MAGIE => "Magie",
        };
    }

    public function get_ID(): int {
        return match($this) {
            self::FAUST => 0,
            self::DOLCH => 1,
            self::SCHWERT => 2,
            self::LASERSCHWERT => 3,
            self::MAGIE => 4,
        };
    }

    public static function fromString(string $name): ?self {
        $name = trim($name);
        foreach (self::cases() as $w) {
            if (strcasecmp($w->get_DisplayName(), $name) === 0 || strcasecmp($w->name, $name) === 0) {
                return $w;
            }
        }
        return null;
    }

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