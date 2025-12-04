<?php

    class Gegner extends Charakter{
        private string $Klasse = "Gegner";
        public function get_Klasse():string {
            return $this->Klasse;
        }
    }

?>