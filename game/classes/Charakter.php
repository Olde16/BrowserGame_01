<?php

abstract class Charakter
{
    // Konstruktor (Stärke auf int geändert für Konsistenz)
    public function __construct(
        private string $Name,
        private int $Lebenspunkte,
        private int $Staerke = 0,    // War float, jetzt int
        private float $Geschick = 0,
        private float $Intelligenz = 0,
        private Waffenart $Waffenart = Waffenart::FAUST,
        private Blockrichtung $Blockrichtung = Blockrichtung::UNTEN,
        private Angriffsrichtung $angriffsrichtung = Angriffsrichtung::UNTEN
    ) {}

    // --- Getter & Setter ---

    public function getName(): string { return $this->Name; }
    public function setName(string $value): void { $this->Name = $value; }

    public function getLebenspunkte(): int { return $this->Lebenspunkte; }
    public function setLebenspunkte(int $value): void { $this->Lebenspunkte = $value; }

    public function getStaerke(): int { return $this->Staerke; }
    public function setStaerke(int $value): void { $this->Staerke = $value; }

    public function getGeschick(): float { return $this->Geschick; }
    public function setGeschick(float $value): void { $this->Geschick = $value; }

    public function getIntelligenz(): float { return $this->Intelligenz; }
    public function setIntelligenz(float $value): void { $this->Intelligenz = $value; }

    public function getWaffenart(): Waffenart { return $this->Waffenart; }
    public function setWaffenart(Waffenart $value): void { $this->Waffenart = $value; }

    public function getBlockrichtung(): Blockrichtung { return $this->Blockrichtung; }
    public function setBlockrichtung(Blockrichtung $value): void { $this->Blockrichtung = $value; }

    public function getAngriffsrichtung(): Angriffsrichtung { return $this->angriffsrichtung; }
    public function setAngriffsrichtung(Angriffsrichtung $value): void { $this->angriffsrichtung = $value; }


    // --- Logik ---

    // In classes/Charakter.php

    public bool $hatGecritted = false; // Speichert für die Ausgabe, ob es ein Crit war

    public function getAschadenAusAngriffswerte(): int
    {
        $basisSchaden = ($this->Staerke * $this->Geschick * $this->Intelligenz);
        $gesamtSchaden = $basisSchaden + $this->Waffenart->get_schaden();

        // KRITISCHER TREFFER LOGIK
        // Geschick 1.0 = 10% Chance, 1.5 = 15% Chance, etc.
        $critChance = $this->Geschick * 10; 
        $zufall = rand(0, 100);

        if ($zufall < $critChance) {
            $gesamtSchaden *= 2; // Doppelter Schaden!
            $this->hatGecritted = true;
        } else {
            $this->hatGecritted = false;
        }

        return (int) $gesamtSchaden;
    }

    /**
     * Berechnet den tatsächlichen Schaden nach Verteidigung.
     */
    public function getVschadenAusVerteidigungswerte(int $schadenInput, Angriffsrichtung $angrRichtung): int
    {
        $blockFaktor = $this->getBlockEffektivwert($angrRichtung);
        
        // Berechnung: Schaden * Blockfaktor (z.B. 1.0 = 100% geblockt) + Intelligenz als Rüstung
        $reduktion = ($schadenInput * $blockFaktor) + $this->Intelligenz;
        
        // WICHTIG: max(0, ...) verhindert, dass man durch Angriff geheilt wird (negativer Schaden)
        return (int) max(0, $schadenInput - $reduktion);
    }

    /**
     * Ermittelt, wie gut geblockt wurde (0.0 = gar nicht, 1.0 = komplett).
     */
    private function getBlockEffektivwert(Angriffsrichtung $angr): float
    {
        global $global__game_deterministic;
        $deterministic = $global__game_deterministic ?? false; // Fallback, falls Variable fehlt

        $blockId = $this->Blockrichtung->get_ID();
        $angrId  = $angr->get_ID();

        // LOGIK-KORREKTUR:
        // Wenn Blockrichtung UNGLEICH Angriffsrichtung -> Kein Block (Faktor 0.0)
        // Du hattest vorher "return 1.0", das hieß: Falsch raten = 0 Schaden.
        if ($blockId !== $angrId) {
            return 0.0; 
        }

        // Ab hier: Richtige Richtung erraten!
        
        if ($deterministic) {
            // Im deterministischen Modus: Richtige Richtung = 100% Block
            return 1.0; 
        }

        // Im Zufallsmodus: Auch wenn man richtig blockt, kann man "ausrutschen"
        // Wir simulieren das durch einen zufälligen Faktor (0.0 bis 1.0)
        return (float) (rand(50, 100) / 100); // Blockt zwischen 50% und 100% des Schadens
    }
}