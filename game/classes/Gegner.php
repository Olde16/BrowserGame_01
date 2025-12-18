<?php

require_once __DIR__ . '/Charakter.php';

class Gegner extends Charakter {
    private string $klasse = "Gegner";

    public function getKlasse(): string {
        return $this->klasse;
    }
}
// Gegner erbt Charakter und bietet weitere Ausbaumöglichkeiten