<?php
    enum Blockrichtung {
        case OBEN;
        case UNTEN;
        case MITTE;
        
        public function get_DisplayName(): string {
            return match($this){
                Blockrichtung::OBEN => "Oben",
                Blockrichtung::UNTEN => "Unten",
                Blockrichtung::MITTE => "Mitte",
            };
        }
        public function get_ID(): int {
            return match($this){
                Blockrichtung::OBEN => 0,
                Blockrichtung::UNTEN => 1,
                Blockrichtung::MITTE => 2,
            };
        }
        public static function fromString(string $name): ?Blockrichtung {
            foreach (self::cases() as $w) {
                if (strcasecmp(string1: $w->get_DisplayName(), string2: $name) === 0) {
                    return $w;
                }
            }
        return null;
        }

        public static function fromID(int $id): ?Blockrichtung {
            foreach (self::cases() as $w) {
                if $w->get_ID() === $id {
                    return $w;
                }
            }
        return null;
        }
    }
?>