<?php
// Charakter.php

// "abstract" heißt, diese Klasse ist nur eine Vorlage. Man kann sie nicht direkt "new" machen.
abstract class Charakter
{
    // Konstruktor: Setzt die Startwerte beim Erstellen eines Spielers/Gegners.
    // Wir nutzen "private Properties", damit niemand von außen die Werte kaputt macht.
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

    public function __destruct() {}

    // Status-Variablen für das Text-Log (damit wir wissen, was passiert ist)
    public bool $hatGecritted = false;   // Kritischer Treffer?
    public bool $hatAusgewichen = false; // Lucky Dodge?

    // --- Getter & Setter (Standard-Methoden um Werte zu lesen/schreiben) ---
    public function getName(): string { return $this->Name; }
    public function getLebenspunkte(): int { return $this->Lebenspunkte; }
    // setLebenspunkte wird benutzt, wenn Schaden genommen wurde.
    public function setLebenspunkte(int $value): void { $this->Lebenspunkte = $value; }
    
    public function getStaerke(): int { return $this->Staerke; }
    public function getGeschick(): float { return $this->Geschick; }
    public function getIntelligenz(): float { return $this->Intelligenz; }
    
    public function getWaffenart(): Waffenart { return $this->Waffenart; }
    public function setWaffenart(Waffenart $value): void { $this->Waffenart = $value; }
    
    public function getBlockrichtung(): Blockrichtung { return $this->Blockrichtung; }
    public function setBlockrichtung(Blockrichtung $value): void { $this->Blockrichtung = $value; }
    
    public function getAngriffsrichtung(): Angriffsrichtung { return $this->angriffsrichtung; }
    public function setAngriffsrichtung(Angriffsrichtung $value): void { $this->angriffsrichtung = $value; }


    // --- ANGRIFFS-BERECHNUNG ---
    public function getAschadenAusAngriffswerte(): int
    {
        // 1. Basis-Schaden aus den Attributen
        $basisSchaden = ($this->Staerke * $this->Geschick * $this->Intelligenz);
        // 2. Waffenschaden dazu
        $waffenSchaden = $this->Waffenart->get_schaden();
        
        // FEATURE: HEADSHOT (Risiko-Mechanik)
        // Wenn man nach OBEN schlägt, trifft man schwerer (weniger Basis-Schaden),
        // hat aber eine viel höhere Chance auf kritische Treffer.
        if ($this->angriffsrichtung == Angriffsrichtung::OBEN) {
            $basisSchaden *= 0.8; // 20% weniger Wucht
            $bonusCritChance = 25; // Aber +25% Crit-Chance
        } else {
            $bonusCritChance = 0;
        }

        $gesamtSchaden = $basisSchaden + $waffenSchaden;

        // FEATURE: KRITISCHER TREFFER
        // Chance = Geschick * 15 (z.B. 1.2 * 15 = 18%) + evtl. Headshot-Bonus
        $critChance = ($this->Geschick * 15) + $bonusCritChance; 
        
        // Würfeln (0-100)
        if (rand(0, 100) < $critChance) {
            $gesamtSchaden *= 2; // Doppelter Schaden!
            $this->hatGecritted = true;
        } else {
            $this->hatGecritted = false;
        }

        return (int) $gesamtSchaden;
    }

    // --- VERTEIDIGUNGS-BERECHNUNG ---
    public function getVschadenAusVerteidigungswerte(int $schadenInput, Angriffsrichtung $angrRichtung): int
    {
        // Wir holen den Block-Faktor (0.0 = Volltreffer, 1.0 = Geblockt, 0.5 = Dodge)
        $blockFaktor = $this->getBlockEffektivwert($angrRichtung);
        
        // Schaden reduzieren (Block + Intelligenz als Rüstung)
        $reduktion = ($schadenInput * $blockFaktor) + $this->Intelligenz;
        
        // Verhindern, dass Schaden negativ wird (Heilung durch Schaden)
        return (int) max(0, $schadenInput - $reduktion);
    }

    // Interne Logik für Blocken und Ausweichen
    private function getBlockEffektivwert(Angriffsrichtung $angr): float
    {
        $this->hatAusgewichen = false; // Reset Status

        // 1. Perfekter Block: Richtungen stimmen überein
        if ($this->Blockrichtung->get_ID() === $angr->get_ID()) {
            return 1.0; // 100% Absorbiert
        }

        // 2. FEATURE: LUCKY DODGE (Ausweichen)
        // Wenn Richtung falsch, würfeln wir auf Geschick.
        $dodgeChance = $this->Geschick * 15;
        
        if (rand(0, 100) < $dodgeChance) {
            $this->hatAusgewichen = true; 
            return 0.5; // Glück gehabt! 50% Schaden vermieden.
        }

        // 3. Pech gehabt: Voller Schaden
        return 0.0; 
    }
}
?>