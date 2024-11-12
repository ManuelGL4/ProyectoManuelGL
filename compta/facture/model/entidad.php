<?php 

    class Entidad {

        private $id;
        private $nif;
        private $tipoEntidad;
        private $nombre;
        private $alias;
        private $codigo_cliente;
        private $direccion;
        private $ciudad;
        private $codigo_postal;
        private $id_departamento;
        private $pais;
        private $telefono;
        private $web;
        private $email;
        private $codigo_delegacion;
        private $tipo_delegacion;
        


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