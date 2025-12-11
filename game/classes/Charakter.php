<?php

    abstract class Charakter{
        private string $Name; // Char Name
        private int $Lebenspunkte; // 10 min bis x
        private int $Staerke; // 30 min bis x
        private float $Geschick; // 0.5 min bis x -> rand(bereich y bis x) unterer y und oberer x Bereich skill bar
        private float $Intelligenz; // 0.5 min bis x -> rand(bereich y bis x) unterer y und oberer x Bereich skill bar
        private Waffenart $Waffenart; // Ausgerustete Waffe
        private Blockrichtung $Blockrichtung; // Richtung der Verteidigung
        private Angriffsrichtung $angriffsrichtung; // Richtung des Angriffs

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
        public function get_Blockrichtung(): Blockrichtung{
            return $this->blockWahl;
        }
        public function set_Blockrichtung(Blockrichtung $value): void{
            $this->blockWahl = $value;
        }
        public function get_Angriffsrichtung() : Angriffsrichtung {
            return $this->angriffsrichtung;
        }
        public function set_Angriffsrichtung(Angriffsrichtung $value) : void {
            $this->angriffsrichtung = $value;
        }

        public function __construct(string $v_Name, int $v_Lebenspunkte,float $v_Staerke = 0, float $v_Geschick = 0, float $v_Intelligenz = 0, Waffenart $v_Waffenart = Waffenart::FAUST, Blockrichtung $v_blockwahl = Blockrichtung::UNTEN, Angriffsrichtung $v_angriffsrichtung = Angriffsrichtung::UNTEN){
            $this->Name = $v_Name;
            $this->Lebenspunkte = $v_Lebenspunkte;
            $this->Staerke = $v_Staerke;
            $this->Geschick = $v_Geschick;
            $this->Intelligenz = $v_Intelligenz;
            $this->Waffenart = $v_Waffenart;
            $this->blockWahl = $v_blockwahl;
            $this->angriffsrichtung = $v_angriffsrichtung;
        }
        public function __destruct(){
            
        }
        public function get_aschaden_aus_angriffswerte(): int{ // ermittelt den von den Angriffswerten abhangigen abgegebenen Schaden
            return (int)(($this->Staerke * $this->Geschick * $this->Intelligenz) + $this->Waffenart->get_schaden()); // Waffenart als Eigenschaft des Angreifers
        }
        public function get_vschaden_aus_verteidigungswerte(int $schaden_aus_angriffswert, string $angrRichtung): int{ // ermittelt den von den Verteidigungswerten abhangigen verbleibenden Schaden, Schaden aus Angriff als Eigenschaft des Angreifers
             return (int)($schaden_aus_angriffswert - (($schaden_aus_angriffswert * $this->get_block_effektivwert($angriffsrichtung)) + $this->Intelligenz )); // Blockrichtung (effektivwert) als Eigenschaft des Angegriffenen
        }
        # Charaktere koennen Gegner oder Spieler sein. Um die Implementierung des Angriff und Verteidigung so einfach wie moeglich zu gestalten wurde sich dafuer entschieden, 
        # die Angriff (get_aschaden_aus_angriffswerte) und Verteidigung Funktion (get_vschaden_aus_verteidigungswerten) in die Klasse Charakter mit einzubauen.
        # Das bietet den Vorteil: Im Programmcode werden mit Aufruf der Funktionen automatisch die korrekten Verteidigungs bzw. Angriffswerte aus den eigenen Eigenschaften ermittelt.
        # So muss nicht wiederholt eine Berechnung gestartet und auf verschiedene Werte zugegriffen werden, sondern lediglich die dafür vorbereiteten Funktionen aufgerufen werden.
        # Das macht das Spiel dynamischer im Gestaltungsspielraum und nutzt die Klasseneigenschaften maximal aus. Die einzelnen Werte werden so nicht verstreut in einer globalen Variable gespeichert, sondern im Objekt selbst.

        private function get_block_effektivwert(Angriffsrichtung $angr):float{ // ermittelt den Effektivwert der Blockrichtung in Abhangigkeit von Angriffsrichtung des Gegners welche (prozentual) mit rand ermittelt wird
            global $global__game_deterministic; // Variable fuer die "extra" Herausforderung
            float $unten = 0;
            float $mitte = 0;
            float $oben = 0;

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
                $p = rand(min: 0,max: 2);
                $gewichtung = (rand(min: 0,max: 100)/100);
                switch ($p) {
                    case 0:
                        $unten = $gewichtung;
                        break;
                    case 1:
                        $mitte = $gewichtung;
                        break;
                    case 2:
                        $oben = $gewichtung;
                        break;
                }

                if ($unten <= 0.1) { $unten = 0; }
                if ($oben <= 0.1) { $oben = 0; }
                if ($mitte <= 0.1) { $mitte = 0; }
            }
            switch ($this->blockWahl->get_ID()) { // je nach auswahl des charakters richtigen Wert wahlen
                case 0:
                    if (0 !== $angr->get_ID()) return 1; // wurde von der geblockten richtung angegriffen ist der Blockwert 1 = 100% andernfalls prozentual oder wie oben absolut (0)
                    return $oben;
                case 1:
                    if (1 !== $angr->get_ID()) return 1;
                    return $unten;
                case 2:
                    if (2 !== $angr->get_ID()) return 1;
                    return $mitte;
                default:
                    return 0;
            }
        }
    }

?>