<?php
    enum Angriffsrichtung {
        case OBEN;
        case UNTEN;
        case MITTE;
        
        public function get_DisplayName(): string {
            return match($this){
                Angriffsrichtung::OBEN => "Oben",
                Angriffsrichtung::UNTEN => "Unten",
                Angriffsrichtung::MITTE => "in der Mitte",
            };
        }
        public function get_ID(): int {
            return match($this){
                Angriffsrichtung::OBEN => 0,
                Angriffsrichtung::UNTEN => 1,
                Angriffsrichtung::MITTE => 2,
            };
        }
        public static function fromString(string $name): ?Angriffsrichtung {
            foreach (self::cases() as $w) {
                if (strcasecmp(string1: $w->get_DisplayName(), string2: $name) === 0) {
                    return $w;
                }
            }
        return null;
        }
    }
?>