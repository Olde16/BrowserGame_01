<?php
    enum Waffenart {
        case SCHWERT;
        case DOLCH;
        case FAUST;
        case LASERSCHWERT;
        case MAGIE;
        
        public function get_schaden(): int {
            return match($this){
                Waffenart::FAUST => 4,
                Waffenart::DOLCH => 50,
                Waffenart::SCHWERT => 100,
                Waffenart::LASERSCHWERT => 300,
                Waffenart::MAGIE => 600,
            };
        }
        public function get_bezeichnung(): string {
            return match($this){
                Waffenart::FAUST => "Faust",
                Waffenart::DOLCH => "Dolch",
                Waffenart::SCHWERT => "Schwert",
                Waffenart::LASERSCHWERT => "Laserschwert",
                Waffenart::MAGIE => "Magie",
            };
        }
        public static function fromString(string $name): ?Waffenart {
            foreach (self::cases() as $w) {
                if (strcasecmp(string1: $w->get_bezeichnung(), string2: $name) === 0) {
                    return $w;
                }
            }
        return null;
        }
    }
?>