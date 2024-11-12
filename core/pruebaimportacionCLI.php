<?php

    require __DIR__ . '/../vendor/autoload.php';

    require '../main.inc.php';
    require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
    require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
    require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
    require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
    require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
    require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
    require_once DOL_DOCUMENT_ROOT.'/core/modules/project/modules_project.php';
    require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
    require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

    // Get parameters
    $id = GETPOST('id', 'int');
    $ref        = GETPOST('ref', 'alpha');
    $action = GETPOST('action', 'aZ09');
    $cancel     = GETPOST('cancel', 'aZ09');
    $backtopage = GETPOST('backtopage', 'alpha');

    // Initialize technical objects
    $object = new Project($db);
    $extrafields = new ExtraFields($db);
    $diroutputmassaction = $conf->mantenimiento->dir_output . '/temp/massgeneration/' . $user->id;
    $hookmanager->initHooks(array('equipos', 'globalcard')); // Note that conf->hooks_modules contains array
    // Fetch optionals attributes and labels
    $extrafields->fetch_name_optionals_label($object->table_element);

    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
    use PhpOffice\PhpSpreadsheet\Style\Alignment;
    use PhpOffice\PhpSpreadsheet\Style\Fill;
    use PhpOffice\PhpSpreadsheet\Style\Border;
    use PhpOffice\PhpSpreadsheet\IOFactory;

    $ruta = "archivosImportacion/CF-Clientes.xlsx";

    $hoja = IOFactory::load($ruta);

    $sheet = $hoja->getActiveSheet();

    $filamasalta = $sheet->getHighestRow();
    $colummasalta = $sheet->getHighestColumn();

    $columnumero = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($colummasalta);

    $datos = array();

    $numeroIdentificador = 1;

    for ($fila = 2447; $fila <= $filamasalta; $fila++) {

        $dataFila = array_fill(0,$columnumero,"");
        $dataFila[3] = 1;
        $dataFila[32] = 1;
        $dataFila[33] = 0;
        $dataFila[4] = "CU2401-".str_pad($numeroIdentificador, 5, 0, STR_PAD_LEFT);

        for ($col = 1; $col <= $columnumero; $col++) {

            if ($col == 3) {    //Nombre
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $dataFila[0] = $celda;
            } else if ($col == 5) {     //CIF
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $dataFila[21] = $celda;
            } else if ($col == 6) {     //Direccion
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $dataFila[8] = $celda;
            } else if ($col == 8) {     //Pueblo/ciudad
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $dataFila[10] = $celda;
            } else if ($col == 9) {     //ZP
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $dataFila[9] = $celda;
            } else if ($col == 10) {     //Provincia
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $dataFila[65] = $celda;

                $celda = strtolower($celda);
                switch($celda){
                    case "madrid":
                        $valor = 269;
                        break;
                    case "la rioja":
                        $valor = 267;
                        break;
                    case "cantabria":
                        $valor = 280;
                        break;
                    case "sevilla":
                        $valor = 282;
                        break;
                    case "barcelona":
                        $valor = 249;
                        break;
                    case "alava":
                        $valor = 242;
                        break;
                    case "asturias":
                        $valor = 274;
                        break;
                    case "cuenca":
                        $valor = 257;
                        break;
                    case "granada":
                        $valor = 259;
                        break;
                    case "la coruña":
                        $valor = 256;
                        break;
                    case "murcia":
                        $valor = 271;
                        break;
                    case "guipuzcoa":
                        $valor = 261;
                        break;
                    case "zaragoza":
                        $valor = 291;
                        break;
                    case "valencia":
                        $valor = 287;
                        break;
                    case "soria":
                        $valor = 283;
                        break;
                    case "las palmas":
                        $valor = 276;
                        break;
                    case "girona":
                        $valor = 258;
                        break;
                    case "gerona":
                        $valor = 258;
                        break;
                    case "ciudad real":
                        $valor = 254;
                        break;
                    case "valladolid":
                        $valor = 288;
                        break;
                    case "pontevedra":
                        $valor = 277;
                        break;
                    case "vizcaya":
                        $valor = 289;
                        break;
                    case "bizcaia":
                        $valor = 289;
                        break;
                    case "vizcaia":
                        $valor = 289;
                        break;
                    case "vizkaya":
                        $valor = 289;
                        break;
                    case "bizkaia":
                        $valor = 289;
                        break;
                    case "vizkaia":
                        $valor = 289;
                        break;
                    case "cordoba":
                        $valor = 255;
                        break;
                    case "toledo":
                        $valor = 286;
                        break;
                    case "burgos":
                        $valor = 250;
                        break;
                    case "huelva":
                        $valor = 262;
                        break;
                    case "huesca":
                        $valor = 263;
                        break;
                    case "malaga":
                        $valor = 270;
                        break;
                    case "palencia":
                        $valor = 275;
                        break;
                    case "marsat":
                        $valor = 288;
                        break;
                    case "badajoz":
                        $valor = 247;
                        break;
                    case "navarra":
                        $valor = 272;
                        break;
                    case "buenos aires":
                        $valor = 610;
                        break;
                    case "cadiz":
                        $valor = 252;
                        break;
                    case "guadalajara":
                        $valor = 260;
                        break;
                    case "tarragona":
                        $valor = 284;
                        break;
                    case "lleida":
                        $valor = 283;
                        break;
                    case "illes balears":
                        $valor = 248;
                        break;
                    case "islas baleares":
                        $valor = 248;
                        break;
                    case "castellon":
                        $valor = 253;
                        break;
                    case "lugo":
                        $valor = 268;
                        break;
                    case "alicante":
                        $valor = 244;
                        break;
                    case "panama":
                        $valor = 1139;
                        break;
                    case "albacete":
                        $valor = 243;
                        break;
                    case "bogota":
                        $valor = 797;
                        break;
                    case "roma":
                        $valor = 199;
                        break;
                    case "florida":
                        $valor = 527;
                        break;
                    case "cordoba-ar":
                        $valor = 609;
                        break;
                    case "milano":
                        $valor = 171;
                        break;
                    case "milan":
                        $valor = 171;
                        break;
                    case "liege":
                        $valor = 110;
                        break;
                    case "paris":
                        $valor = 77;
                        break;
                    case "vermont":
                        $valor = 563;
                        break;
                    case "zamora":
                        $valor = 290;
                        break;
                    case "segovia":
                        $valor = 281;
                        break;                  
                }

                $dataFila[11] = $valor;
            } else if ($col == 11) {    //Pais
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                switch ($celda) {
                    case "ESPAÑA":
                        $valor = 4;
                        break;
                    case "España":
                        $valor = 4;
                        break;
                    case "Italia":
                        $valor = 3;
                        break;
                    case "Portugal":
                        $valor = 25;
                        break;
                    case "Alemania":
                        $valor = 5;
                        break;
                    case "Iraq":
                        $valor = 120;
                        break;
                    case "Canad":
                        $valor = 14;
                        break;
                    case "Mxico":
                        $valor = 154;
                        break;
                    case "Pases Bajos":
                        $valor = 17;
                        break;
                    case "Irn":
                        $valor = 119;
                        break;
                    case "Argentina":
                        $valor = 19;
                        break;
                    case "Israel":
                        $valor = 121;
                        break;
                    case "Francia":
                        $valor = 1;
                        break;
                    case "Arabia Saud":
                        $valor = 26;
                        break;
                    case "Estados Unidos":
                        $valor = 11;
                        break;
                    case "Jordania":
                        $valor = 124;
                        break;
                    case "Per":
                        $valor = 181;
                        break;
                    case "Serbia y Montenegro":
                        $valor = 244;
                        break;
                    case "Honduras":
                        $valor = 114;
                        break;
                    case "Repblica Dominicana":
                        $valor = 83;
                        break;
                    case "Brasil":
                        $valor = 56;
                        break;
                    default:
                        $valor = ""; // Valor por defecto si $celda no coincide con ningún caso
                }
                

                $dataFila[12] = $valor;

            }  else if ($col == 12) {    //Telefono
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $dataFila[13] = $celda;
            } else if ($col == 13) {    //Fax
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $dataFila[14] = $celda;
            } else if ($col == 14) {    //Email
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $dataFila[16] = $celda;
            } else if ($col == 32) {    //URL
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $dataFila[15] = $celda;
            } else if ($col == 27) {    //Cuenta contable
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $dataFila[52] = $celda;
            } else if ($col == 30) {    //Libre 1
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $dataFila[64] = $celda;
            } else if ($col == 42) {    //Forma de envío
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $dataFila[55] = $celda;
            } else if ($col == 21) {    //Forma pago
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $dataFila[67] = $celda;
            } else if ($col == 24) {    //Codigo cliente
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $dataFila[66] = $celda;
            } else if ($col == 25) {    //Dto
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $dataFila[49] = $celda;
            }

        }

        $datos[] = $dataFila;

        $numeroIdentificador++;

    }

    //print_r($datos);

    //CREAR NUEVO ARCHIVO
    $nuevoExcel = new Spreadsheet();
    $hojaNueva = $nuevoExcel->getActiveSheet();

    $cabecera = ["Nombre* (s.nom)", "Apodo (s.name_alias)", "Sede central (s.parent)", "Estado (s.status)", "Código cliente (s.code_client)", "Código proveedor (s.code_fournisseur)", "Código contable cliente (s.code_compta)", "Código contable proveedor (s.code_compta_fournisseur)", "Dirección (s.address)", "Código postal (s.zip)", "Población (s.town)", "Código de estado/provincia (s.fk_departement)", "Código país (s.fk_pays)", "Teléfono (s.phone)", "Fax (s.fax)", "Url (s.url)", "Correo (s.email)", "Skype (s.skype)", "Empleados (s.fk_effectif)", "Tipo de tercero (s.fk_typent)", "Tipo de entidad comercial (s.fk_forme_juridique)", "ID profesional 1 (s.siren)", "ID profesional 2 (s.siret)", "ID profesional 3 (s.ape)", "ID profesional 4 (s.idprof4)", "ID profesional 5 (s.idprof5)", "ID profesional 6 (s.idprof6)", "CIF intra. (s.tva_intra)", "Capital (s.capital)", "Estado cliente potencial (s.fk_stcomm)", "Nota (privada) (s.note_private)", "Nota (pública) (s.note_public)", "Cliente* (s.client)", "Proveedor* (s.fournisseur)", "Cliente potencial (s.fk_prospectlevel)", "Tipo de pago - Cliente (s.mode_reglement)", "Condiciones de pago - Cliente (s.cond_reglement)", "Tipo de pago - Proveedor (s.mode_reglement_supplier)", "Condiciones de pago - Proveedor (s.cond_reglement_supplier)", "Importe máximo para facturas pendientes (s.outstanding_limit)", "Cuenta bancaria de pago (s.fk_account)", "Incoterms (s.fk_incoterms)", "Sujeto a IVA (s.tva_assuj)", "Código de barras (s.barcode)", "Idioma predeterminado (s.default_lang)", "Canvas (s.canvas)", "Fecha de creación (s.datec)", "Usar Multimoneda (s.fk_multicurrency)", "Divisa (s.multicurrency_code)", "Descuento (extra.discount)", "Estado (extra.status)", "Datos para la Facturación (extra.fact_datos)", "Cuenta Contable (extra.cuenta_contable)", "Periodicidad (extra.period_pago)", "Cuentas Bancarias (extra.cuentas_bank)", "Forma de Envío (extra.forma_envio)", "Ruta (extra.route)", "BICS/SWIFTS (extra.bics)", "Tipo factura (extra.tipo_factura)", "Datos proveedores (extra.codigos_dir3)", "Otros (extra.other)", "Calificación tiempo (extra.puntuacion_tiempo)", "Calificación calidad (extra.puntuacion_calidad)", "Días de pago (extra.payment_days)", "Libre 1 (extra.libre_1)", "Provincia 2 (extra.provincia_2)", "Codigo Cliente (extra.codigo_cliente)", "Forma Pago (extra.forma_pago)"];

    $hojaNueva->fromArray($cabecera, NULL, 'A1');
    
    $num = 1;
    foreach ($datos as $fila => $dataFila) {

        if ($dataFila[0] == "") {
            $num = 2;
        } else {
            foreach ($dataFila as $col => $dato) {
                // +1 porque las filas y columnas en Excel empiezan desde 1, no desde 0
                $hojaNueva->setCellValueByColumnAndRow($col + 1, $fila + 2, $dato);
            }
        }

    }
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="clientes_totales.xlsx"');
    header('Cache-Control: max-age=0');
    
    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($nuevoExcel, 'Xlsx');
    $writer->save('php://output');
    exit;
    


?>