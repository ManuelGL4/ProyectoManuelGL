<?php 

    class Servicio {

        private $id;
        private $id_producto_externo;
        private $descripcion;
        private $precio_base;
        private $iva_total;
        private $total_con_descuento;
        private $total_con_iva;
        private $porcentaje_iva;
        private $porcentaje_descuento;
        private $cantidad_servicio;
        private $fecha_comienzo;
        private $fecha_fin;


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