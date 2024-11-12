<?php 

    class Delegacion {

        private $id;
        private $nombre;
        private $telefono1;
        private $telefono2;
        private $direccion;
        private $codigo_postal;
        private $localidad;
        private $provincia;
        private $pais;
        private $direccion_material;
        private $direccion_factura;
        private $tercero;
        private $oficina_contable;
        private $organo_gestor;
        private $unidad_tramitadora;


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