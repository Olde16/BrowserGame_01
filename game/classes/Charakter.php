<?php

    abstract class Charakter{
        private string $Name; // Char Name
        private int $Lebenspunkte; // 10 min bis x
        private int $Staerke; // 30 min bis x
        private float $Geschick; // 0.5 min bis x -> rand(bereich y bis x) unterer y und oberer x Bereich skill bar
        private float $Intelligenz; // 0.5 min bis x -> rand(bereich y bis x) unterer y und oberer x Bereich skill bar
        private Waffenart $Waffenart; // Ausgerustete Waffe
        private string $blockWahl;

        public function get_Name(): string{
            return $this->Name;
        }
        public function set_Name(string $value): void{
            $this->Name = $value;
        }
        public function get_Lebenspunkte(): int{
            return $this->Lebenspunkte;
        }
        public function set_Lebenspunkte(int $value): void{
            $this->Lebenspunkte = $value;
        }
        public function get_Staerke(): int{
            return $this->Staerke;
        }
        public function set_Staerke(int $value): void{
            $this->Staerke = $value;
        }
        public function get_Geschick(): float{
            return $this->Geschick;
        }
        public function set_Geschick(float $value): void{
            $this->Geschick = $value;
        }
        public function get_Intelligenz(): float{
            return $this->Intelligenz;
        }
        public function set_Intelligenz(float $value): void{
            $this->Intelligenz = $value;
        }
        public function get_Waffenart(): Waffenart{
            return $this->Waffenart;
        }
        public function set_Waffenart(Waffenart $value): void{
            $this->Waffenart = $value;
        }
        public function get_blockWahl(): string{
            return $this->blockWahl;
        }
        public function set_blockWahl(string $value): void{
            $this->blockWahl = $value;
        }
        public function __construct(string $v_Name, int $v_Lebenspunkte,float $v_Staerke = 0, float $v_Geschick = 0, float $v_Intelligenz = 0){
            $this->Name = $v_Name;
            $this->Lebenspunkte = $v_Lebenspunkte;
            $this->Staerke = $v_Staerke;
            $this->Geschick = $v_Geschick;
            $this->Intelligenz = $v_Intelligenz;
            $this->Waffenart = Waffenart::FAUST;
            $this->blockWahl = "oben";
        }
        public function __destruct(){

        }
        public function get_aschaden_aus_angriffswerte(): int{ // ermittelt den von den Angriffswerten abhangigen abgegebenen Schaden
            return (int)(($this->Staerke * $this->Geschick * $this->Intelligenz) + $this->Waffenart->get_schaden()); // Waffenart als Eigenschaft des Angreifers
        }
        public function get_vschaden_aus_verteidigungswerte(int $schaden_aus_angriffswert): int{ // ermittelt den von den Verteidigungswerten abhangigen verbleibenden Schaden
             return (int)($schaden_aus_angriffswert - (($schaden_aus_angriffswert * $this->get_block_effektivwert()) + $this->Intelligenz )); // Blockrichtung (effektivwert) als Eigenschaft des Angegriffenen
        }
        private function get_block_effektivwert():float{ // ermittelt den Effektivwert der Blockrichtung in Abhangigkeit von Angriffsrichtung des Gegners welche (prozentual) mit rand ermittelt wird
            global $global__game_deterministic;
            $unten = 0;
            $mitte = 0;
            $oben = 0;

            if($global__game_deterministic){  // ist das Spiel vom Spieler deterministisch eingestellt, wird mit absoluten Werten statt mit Prozenten gearbeitet (schwerer)
                $p = rand(min: 0,max: 2);
                switch ($p) {
                    case 0:
                        $unten = 1;
                        break;
                    case 1:
                        $mitte = 1;
                        break;
                    case 2:
                        $oben = 1;
                        break;
                }
            }
            else
            {
                $unten = (rand(min: 0,max: 100)/100);
                $oben = (rand(min: 0,max: 100- $unten)/100);
                $mitte = (rand(min: 0,max: 100- $unten - $oben)/100);

                if ($unten < 0.3) { $unten = 0; }
                if ($oben < 0.3) {$oben = 0; }
                if ($mitte < 0.3) { $mitte = 0; }
            }
            switch ($this->blockWahl) { // je nach auswahl des charakters richtigen Wert wahlen
                case 'oben':
                    return $oben;
                case 'unten':
                    return $unten;
                case 'mitte':
                    return $mitte;
                default:
                    return 0;
            }
        }
    }

?>