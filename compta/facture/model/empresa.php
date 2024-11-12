<?php 

    class Empresa {

        private $id;
        private $nombre;
        private $nif;
        private $tipoEmpresa;
        private $direccion;
        private $codigo_postal;
        private $ciudad;
        private $provincia;
        private $codigo_pais;
        private $web;
        private $email;

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