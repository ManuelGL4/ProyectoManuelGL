<?php 

    class Factura {

        private $id;
        private $referencia;
        private $tipo;
        private $id_sociedad;
        private $fecha_creacion;
        private $total_con_iva;
        private $total;
        private $iva;
        private $porcentaje_retencion;
        private $motivo_retencion;
        private $informacion_adicional;


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