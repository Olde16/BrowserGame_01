<?php

    class Spieler extends Charakter{
        private string $Klasse = "Spieler";
        public function get_Klasse():string {
            return $this->Klasse;
        }
    }

?>