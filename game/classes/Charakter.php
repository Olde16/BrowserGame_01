<?php

    abstract class Charakter{
        private string $Name; // Char Name
        private int $Lebenspunkte; // 10 min bis x
        private int $Staerke; // 30 min bis x
        private float $Geschick; // 0.5 min bis x -> rand(bereich y bis x) unterer y und oberer x Bereich skill bar
        private float $Intelligenz; // 0.5 min bis x -> rand(bereich y bis x) unterer y und oberer x Bereich skill bar
        private Waffenart $Waffenart; // Ausgerustete Waffe
        private string $blockWahl;
        private string $angriffsrichtung;

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
        public function get_Angriffsrichtung() : string {
            return $this->angriffsrichtung;
        }
        public function set_Angriffsrichtung(string $value) : void {
            $this->angriffsrichtung = $value;
        }

        public function __construct(string $v_Name, int $v_Lebenspunkte,float $v_Staerke = 0, float $v_Geschick = 0, float $v_Intelligenz = 0, Waffenart $v_Waffenart = Waffenart::FAUST, string $v_blockwahl = "oben"){
            $this->Name = $v_Name;
            $this->Lebenspunkte = $v_Lebenspunkte;
            $this->Staerke = $v_Staerke;
            $this->Geschick = $v_Geschick;
            $this->Intelligenz = $v_Intelligenz;
            $this->Waffenart = $v_Waffenart;
            $this->blockWahl = $v_blockwahl;
        }
        public function __destruct(){

        }
        public function get_aschaden_aus_angriffswerte(): int{ // ermittelt den von den Angriffswerten abhangigen abgegebenen Schaden
            return (int)(($this->Staerke * $this->Geschick * $this->Intelligenz) + $this->Waffenart->get_schaden()); // Waffenart als Eigenschaft des Angreifers
        }
        public function get_vschaden_aus_verteidigungswerte(int $schaden_aus_angriffswert): int{ // ermittelt den von den Verteidigungswerten abhangigen verbleibenden Schaden, Schaden aus Angriff als Eigenschaft des Angreifers
             return (int)($schaden_aus_angriffswert - (($schaden_aus_angriffswert * $this->get_block_effektivwert()) + $this->Intelligenz )); // Blockrichtung (effektivwert) als Eigenschaft des Angegriffenen
        }
        # Charaktere koennen Gegner oder Spieler sein. Um die Implementierung des Angriff und Verteidigung so einfach wie moeglich zu gestalten wurde sich dafuer entschieden, 
        # die Angriff (get_aschaden_aus_angriffswerte) und Verteidigung Funktion (get_vschaden_aus_verteidigungswerten) in die Klasse Charakter mit einzubauen.
        # Das bietet den Vorteil: Im Programmcode werden mit Aufruf der Funktionen automatisch die korrekten Verteidigungs bzw. Angriffswerte aus den eigenen Eigenschaften ermittelt.
        # So muss nicht wiederholt eine Berechnung gestartet und auf verschiedene Werte zugegriffen werden, sondern lediglich die dafür vorbereiteten Funktionen aufgerufen werden.
        # Das macht das Spiel dynamischer in Gestaltungsspielraum.
        private function get_attk_effectiveness() : float {
            
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
                if ($oben < 0.3) { $oben = 0; }
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