<?php

require_once __DIR__ . '/Charakter.php';

class Spieler extends Charakter {
    private string $klasse = "Spieler";

    public function getKlasse(): string {
        return $this->klasse;
    }
}