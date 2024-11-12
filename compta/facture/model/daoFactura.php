<?php

require_once('../../DB/libreriaPDO.php');
require_once('factura.php');
require_once('servicio.php');
require_once('entidad.php');
require_once('empresa.php');
require_once('departamento.php');
require_once('pais.php');
require_once('delegacion.php');

class daoFactura extends DB
{

    public function obtenerFactura($id_factura)
    {
        $consulta = "SELECT * FROM ".MAIN_DB_PREFIX."facture
        WHERE rowid=".$id_factura;

        $param = array();
        $this->ConsultaDatos($consulta, $param);
        
        foreach ($this->filas as $fila) {

            $factura = new Factura();
            
            $factura->__set("id", $fila['rowid']);
            $factura->__set("referencia", $fila['ref']);
            $factura->__set("tipo", $fila['type']);
            $factura->__set("id_sociedad", $fila['fk_soc']);
            $factura->__set("fecha_creacion", $fila['datef']);
            $factura->__set("total_con_iva", $fila['total_ttc']);
            $factura->__set("total", $fila['total_ht']);
            $factura->__set("iva", $fila['total_tva']);
            //number_format($fila['tva'], 2, '.', '')
            
        }


        return $factura;
    }

    public function obtenerExtrasFactura($factura)
    {
        $consulta = "SELECT * FROM ".MAIN_DB_PREFIX."facture_extrafields
        WHERE fk_object=".$factura->__get("id");
        
        $param = array();
        $this->ConsultaDatos($consulta, $param);
        
        foreach ($this->filas as $fila) {
            
            $factura->__set("motivo_retencion", $fila['motivo_retencion']);
            $factura->__set("porcentaje_retencion", $fila['retencion']);
            $factura->__set("informacion_adicional", $fila['informacion_adicional']);
            
        }
        
    }

    public function obtenerServiciosFactura($id_factura)
    {
        $consulta = "SELECT * FROM ".MAIN_DB_PREFIX."facturedet
        WHERE fk_facture=".$id_factura;

        $param = array();
        $this->ConsultaDatos($consulta, $param);
        
        $this->Servicios = array();
        foreach ($this->filas as $fila) {

            $servicio = new Servicio();
            
            $servicio->__set("id_producto_externo", $fila['fk_product']);
            $servicio->__set("descripcion", $fila['description']);
            $servicio->__set("porcentaje_iva", $fila['tva_tx']);
            $servicio->__set("cantidad_servicio", $fila['qty']);
            $servicio->__set("precio_base", $fila['subprice']);
            $servicio->__set("iva_total", $fila['total_tva']);
            $servicio->__set("total_con_iva", $fila['total_ttc']);
            $servicio->__set("porcentaje_descuento", $fila['remise_percent']);
            $servicio->__set("total_con_descuento", $fila['total_ht']);
            $servicio->__set("fecha_comienzo", $fila['date_start']);
            $servicio->__set("fecha_fin", $fila['date_end']);
            
            $this->Servicios[] = $servicio;
            
        }

        
        return $this->Servicios;
    }

    public function obtenerEntidadFactura($id_entidad)
    {
        $consulta = "SELECT * FROM ".MAIN_DB_PREFIX."societe
        WHERE rowid=".$id_entidad;
        
        $param = array();
        $this->ConsultaDatos($consulta, $param);
        
        foreach ($this->filas as $fila) {

            $entidad = new Entidad();
            
            $entidad->__set("nif", $fila['siren']);
            if ($fila["siren"]!="") {

                $tipoEntidad=$this->calcularTipoEntidad($fila["siren"]);
                $entidad->__set("tipoEntidad",$tipoEntidad );
            }
            
            $entidad->__set("nombre", $fila['nom']);
            $entidad->__set("alias", $fila['name_alias']);
            $entidad->__set("codigo_cliente", $fila['code_client']);
            $entidad->__set("direccion", $fila['address']);
            $entidad->__set("ciudad", $fila['town']);
            $entidad->__set("codigo_postal", $fila['zip']);
            $entidad->__set("pais", $fila['fk_pays']);
            $entidad->__set("telefono", $fila['phone']);
            $entidad->__set("id_departamento", $fila['fk_departement']);
            $entidad->__set("email", $fila['email']);
            $entidad->__set("web", $fila['url']);
            
        }
        
        return $entidad;
    }

    public function obtenerDelegacionesEntidadFactura($id_sociedad){

        $consulta = "SELECT * FROM ".MAIN_DB_PREFIX."delegacion
        WHERE fk_tercero=".$id_sociedad;

        $param = array();
        $this->ConsultaDatos($consulta, $param);
        
        $this->Delegaciones = array();
        foreach ($this->filas as $fila) {

            $delegacion = new Delegacion();
            
            $delegacion->__set("id", $fila['id']);
            $delegacion->__set("nombre", $fila['nombre']);
            $delegacion->__set("telefono1", $fila['telef1']);
            $delegacion->__set("telefono2", $fila['telef2']);
            $delegacion->__set("direccion", $fila['direccion']);
            $delegacion->__set("codigo_postal", $fila['cp']);
            $delegacion->__set("localidad", $fila['localidad']);
            $delegacion->__set("provincia", $fila['provincia']);
            $delegacion->__set("direccion_material", $fila['direccion_material']);
            $delegacion->__set("direccion_factura", $fila['direccion_factura']);
            $delegacion->__set("pais", $fila['pais']);
            $delegacion->__set("tercero", $fila['fk_tercero']);
            $delegacion->__set("fk_tipo_delegacion", $fila['fk_tipo_delegacion']);
            $delegacion->__set("codigo_delegacion", $fila['codigo_delegacion']);
            
            
            $this->Delegaciones[] = $delegacion;
            
        }

        
        return $this->Delegaciones;

    }

    public function obtenerTipoDelegacion($id_delegacion){

        $consulta = "SELECT * FROM ".MAIN_DB_PREFIX."tipo_delegacion
        WHERE id=".$id_delegacion;

        $param = array();
        $this->ConsultaDatos($consulta, $param);
        
        $this->Delegaciones = array();
        foreach ($this->filas as $fila) {
            
            
            $valor=$fila['valor'];
            
        }

        
        return $valor;

    }

    public function calcularTipoEntidad($nif){
        $valor="";
        
        $primeraLetra=strtoupper(substr($nif,0,1));

        $arrayLetras=["A","B","C","D","E","F","G","H","N","P","Q","S"];

        $juridico=in_array($primeraLetra,$arrayLetras);

        if($juridico==true){

            $valor= 'J';

        }else{
            $valor= 'F';
        }
       
        return $valor;
    }

    public function obtenerPaisEntidadFactura($id_pais)
    {
        $consulta = "SELECT * FROM ".MAIN_DB_PREFIX."c_country
        WHERE rowid=".$id_pais;

        $param = array();
        $this->ConsultaDatos($consulta, $param);
        
        foreach ($this->filas as $fila) {

            $pais = new Pais();
            
            $pais->__set("nombre", $fila['label']);
            $pais->__set("codigo", $fila['code']);
            $pais->__set("codigo_iso", $fila['code_iso']);
            
        }
        
        return $pais;
    }

    public function obtenerDepartamentoEntidadFactura($id_departamento)
    {
        $consulta = "SELECT * FROM ".MAIN_DB_PREFIX."c_departements
        WHERE rowid=".$id_departamento;

        $param = array();
        $this->ConsultaDatos($consulta, $param);
        
        foreach ($this->filas as $fila) {

            $departamento = new Pais();
            
            $departamento->__set("nombre", $fila['nom']);
            $departamento->__set("codigo", $fila['code_departement']);
            $departamento->__set("provincia", $fila['ncc']);
            
        }
        
        return $departamento;
    }

    public function obtenerEmpresaFactura()
    {
        $consulta = "SELECT * FROM ".MAIN_DB_PREFIX."const
        WHERE name LIKE 'MAIN_INFO%'";

        $param = array();
        $this->ConsultaDatos($consulta, $param);
        
        $empresa = new Empresa();

        foreach ($this->filas as $fila) {

            if ($fila["name"]=="MAIN_INFO_SOCIETE_STATE") {
                $provinciaBase=$fila["value"];
                $provinciaArray=explode(":",$provinciaBase);
                $provincia=$provinciaArray[2];

                $empresa->__set("provincia", $provincia);
            }

            if ($fila["name"]=="MAIN_INFO_SOCIETE_COUNTRY") {
                $paisBase=$fila["value"];
                $paisArray=explode(":",$paisBase);
                $idPais=$paisArray[0];
                $infoPais=$this->obtenerPaisEntidadFactura($idPais);
                $pais=$infoPais->__get("codigo_iso");

                $empresa->__set("codigo_pais", $pais);
            }

            if ($fila["name"]=="MAIN_INFO_SOCIETE_NOM") {
                $empresa->__set("nombre", $fila['value']);
            }

            if ($fila["name"]=="MAIN_INFO_SOCIETE_ADDRESS") {
                $empresa->__set("direccion", $fila['value']);
            }

            if ($fila["name"]=="MAIN_INFO_SOCIETE_ZIP") {
                $empresa->__set("codigo_postal", $fila['value']);
            }

            if ($fila["name"]=="MAIN_INFO_SOCIETE_TOWN") {
                $empresa->__set("ciudad", $fila['value']);
            }

            if ($fila["name"]=="MAIN_INFO_SOCIETE_MAIL") {
                $empresa->__set("email", $fila['value']);
            }

            if ($fila["name"]=="MAIN_INFO_SOCIETE_WEB") {
                $empresa->__set("web", $fila['value']);
            }

            if ($fila["name"]=="MAIN_INFO_SIREN") {
                $empresa->__set("nif", $fila['value']);

                $tipoEmpresa=$this->calcularTipoEntidad($fila['value']);
                $empresa->__set("tipoEmpresa", $tipoEmpresa);

            }

            
        }
        
        return $empresa;
    }
}
