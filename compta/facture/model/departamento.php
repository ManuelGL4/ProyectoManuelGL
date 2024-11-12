<?php 

    class Departamento {

        private $id;
        private $nombre;
        private $codigo;
        private $provincia;

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