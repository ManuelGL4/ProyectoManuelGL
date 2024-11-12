<?php 

    class Pais {

        private $id;
        private $nombre;
        private $codigo;
        private $codigo_iso;


        public function __get($propiedad)
        {
            return $this->$propiedad;
        }
        
        public function __set($propiedad,$valor)
        {
            $this->$propiedad=$valor;
        }
        

    }

?>