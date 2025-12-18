<?php

abstract class Charakter
{
    public function __construct(
        private string $Name,
        private int $Lebenspunkte,
        private int $Staerke = 0,
        private float $Geschick = 0,
        private float $Intelligenz = 0,
        private Waffenart $Waffenart = Waffenart::FAUST,
        private Blockrichtung $Blockrichtung = Blockrichtung::UNTEN,
        private Angriffsrichtung $angriffsrichtung = Angriffsrichtung::UNTEN
    ) {}
    // Konstruktor mit Vorgabewerten

    public function getName(): string { return $this->Name; }
    public function setName(string $value): void { $this->Name = $value; }
    // Name

    public function getLebenspunkte(): int { return $this->Lebenspunkte; }
    public function setLebenspunkte(int $value): void { $this->Lebenspunkte = $value; }
    // Lebenspunkte

    public function getStaerke(): int { return $this->Staerke; }
    public function setStaerke(int $value): void { $this->Staerke = $value; }
    // Staerke

    public function getGeschick(): float { return $this->Geschick; }
    public function setGeschick(float $value): void { $this->Geschick = $value; }
    // Geschick

    public function getIntelligenz(): float { return $this->Intelligenz; }
    public function setIntelligenz(float $value): void { $this->Intelligenz = $value; }
    // Intellenz

    public function getWaffenart(): Waffenart { return $this->Waffenart; }
    public function setWaffenart(Waffenart $value): void { $this->Waffenart = $value; }
    // Waffenart

    public function getBlockrichtung(): Blockrichtung { return $this->Blockrichtung; }
    public function setBlockrichtung(Blockrichtung $value): void { $this->Blockrichtung = $value; }
    // Blockrichtung

    public function getAngriffsrichtung(): Angriffsrichtung { return $this->angriffsrichtung; }
    public function setAngriffsrichtung(Angriffsrichtung $value): void { $this->angriffsrichtung = $value; }
    // Angriffsrichtung

    public bool $hatGecritted = false;

    public function getAschadenAusAngriffswerte(): int
    {
        $basisSchaden = ($this->Staerke * $this->Geschick * $this->Intelligenz);
        $gesamtSchaden = $basisSchaden + $this->Waffenart->get_schaden();

        $critChance = $this->Geschick * 10; 
        $zufall = rand(0, 100);

        if ($zufall < $critChance) {
            $gesamtSchaden *= 2; // Doppelter Schaden bei Crit
            $this->hatGecritted = true;
        } else {
            $this->hatGecritted = false;
        }

        return (int) $gesamtSchaden;
    }
    // aus eigenen angriffsrelevanten Werten den Angriff ermitteln

    public function getVschadenAusVerteidigungswerte(int $schadenInput, Angriffsrichtung $angrRichtung): int
    {
        $blockFaktor = $this->getBlockEffektivwert($angrRichtung);
        
        // Berechnung: Schaden * Blockfaktor (z.B. 1.0 = 100% geblockt) + Intelligenz als Rüstung
        $reduktion = ($schadenInput * $blockFaktor) + $this->Intelligenz;
        
        // max(0, ...) verhindert dass man durch Angriff geheilt wird (negativer Schaden)
        return (int) max(0, $schadenInput - $reduktion);
    }
    // Mit Übergabe von Angriffsrichtung und Schadenshöhe des Gegners wird die eigene Verteidigung ermittelt

    private function getBlockEffektivwert(Angriffsrichtung $angr): float
    {
        global $global__game_deterministic;
        $deterministic = $global__game_deterministic ?? false; // false = Fallback, falls Variable fehlt

        $blockId = $this->Blockrichtung->get_ID();
        $angrId  = $angr->get_ID();

        if ($blockId !== $angrId) {
            return 0.0; 
        }
        
        if ($deterministic) {
            // Ist man im deterministischen Modus bei richtiger Richtung wird 100% geblockt
            return 1.0; 
        }

        // Im Zufallsmodus kann man "ausrutschen" auch wenn richtig geblockt wird
        // -> zufaelliger Faktor (0.0 bis 1.0)
        return (float) (rand(50, 100) / 100); // Blockt zwischen 50% und 100% des Schadens
    }
    // Je nach Blockrichtung und globalen Einstellungen wird der Block-Faktor ermittelt. Er ist ausschlaggebend fuer die Effektivitaet des Angriffs
}