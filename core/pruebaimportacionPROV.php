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

    $ruta = "archivosImportacion/CF-Proveedores.xlsx";

    $hoja = IOFactory::load($ruta);

    $sheet = $hoja->getActiveSheet();

    $filamasalta = $sheet->getHighestRow();
    $colummasalta = $sheet->getHighestColumn();

    $columnumero = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($colummasalta);

    $datos = array();

    $numeroIdentificador = 1;

    for ($fila = 884; $fila <= $filamasalta; $fila++) {

        $dataFila = array_fill(0,$columnumero,"");
        $dataFila[3] = 1;
        $dataFila[32] = 0;
        $dataFila[33] = 1;
        $dataFila[5] = "SU2401-".str_pad($numeroIdentificador, 5, 0, STR_PAD_LEFT);

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
            } else if ($col == 7) {     //Pueblo/ciudad
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $dataFila[10] = $celda;
            } else if ($col == 8) {     //ZP
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $dataFila[9] = $celda;
            } else if ($col == 9) {     //Provincia
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $dataFila[65] = $celda;

                if ($celda == "THE NETHERLANDS") {
                    $valor = 17;
                } else if ($celda == "ALEMANIA") {
                    $valor = 5;
                } else if ($celda == "FRANCIA") {
                    $valor = 1;
                } else if ($celda == "FRANCE") {
                    $valor = 1;
                } else if ($celda == "SUIZA") {
                    $valor = 6;
                } else if ($celda == "REINO UNIDO") {
                    $valor = 7;
                } else if ($celda == "HUNGRIA") {
                    $valor = 18;
                } else if ($celda == "USA") {
                    $valor = 11;
                } else if ($celda == "EEUU") {
                    $valor = 11;
                } else if ($celda == "PORTUGAL") {
                    $valor = 25;
                } else if ($celda == "ITALIA") {
                    $valor = 3;
                } else if ($celda == "CHINA") {
                    $valor = 9;
                } else if ($celda == "TIAJIN CHINA") {
                    $valor = 9;
                } else if ($celda == "TURKIA") {
                    $valor = 221;
                } else if ($celda == "JAPON") {
                    $valor = 123;
                } else if ($celda == "BELGICA") {
                    $valor = 2;
                } else if ($celda == "DINAMARCA") {
                    $valor = 80;
                } else if ($celda == "EGYPT") {
                    $valor = 85;
                } else if ($celda == "KENYA") {
                    $valor = 126;
                } else {
                    $valor = 4;
                }

                $celda = strtolower($celda);
                switch($celda){
                    case "madrid":
                        $valor2 = 269;
                        break;
                    case "la rioja":
                        $valor2 = 267;
                        break;
                    case "cantabria":
                        $valor2 = 280;
                        break;
                    case "sevilla":
                        $valor2 = 282;
                        break;
                    case "barcelona":
                        $valor2 = 249;
                        break;
                    case "alava":
                        $valor2 = 242;
                        break;
                    case "asturias":
                        $valor2 = 274;
                        break;
                    case "cuenca":
                        $valor2 = 257;
                        break;
                    case "granada":
                        $valor2 = 259;
                        break;
                    case "la coruña":
                        $valor2 = 256;
                        break;
                    case "murcia":
                        $valor2 = 271;
                        break;
                    case "guipuzcoa":
                        $valor2 = 261;
                        break;
                    case "zaragoza":
                        $valor2 = 291;
                        break;
                    case "valencia":
                        $valor2 = 287;
                        break;
                    case "soria":
                        $valor2 = 283;
                        break;
                    case "las palmas":
                        $valor2 = 276;
                        break;
                    case "girona":
                        $valor2 = 258;
                        break;
                    case "gerona":
                        $valor2 = 258;
                        break;
                    case "ciudad real":
                        $valor2 = 254;
                        break;
                    case "valladolid":
                        $valor2 = 288;
                        break;
                    case "pontevedra":
                        $valor2 = 277;
                        break;
                    case "vizcaya":
                        $valor2 = 289;
                        break;
                    case "bizcaia":
                        $valor2 = 289;
                        break;
                    case "vizcaia":
                        $valor2 = 289;
                        break;
                    case "vizkaya":
                        $valor2 = 289;
                        break;
                    case "bizkaia":
                        $valor2 = 289;
                        break;
                    case "vizkaia":
                        $valor2 = 289;
                        break;
                    case "cordoba":
                        $valor2 = 255;
                        break;
                    case "toledo":
                        $valor2 = 286;
                        break;
                    case "burgos":
                        $valor2 = 250;
                        break;
                    case "huelva":
                        $valor2 = 262;
                        break;
                    case "huesca":
                        $valor2 = 263;
                        break;
                    case "malaga":
                        $valor2 = 270;
                        break;
                    case "palencia":
                        $valor2 = 275;
                        break;
                    case "marsat":
                        $valor2 = 288;
                        break;
                    case "badajoz":
                        $valor2 = 247;
                        break;
                    case "navarra":
                        $valor2 = 272;
                        break;
                    case "buenos aires":
                        $valor2 = 610;
                        break;
                    case "cadiz":
                        $valor2 = 252;
                        break;
                    case "guadalajara":
                        $valor2 = 260;
                        break;
                    case "tarragona":
                        $valor2 = 284;
                        break;
                    case "lleida":
                        $valor2 = 283;
                        break;
                    case "illes balears":
                        $valor2 = 248;
                        break;
                    case "islas baleares":
                        $valor2 = 248;
                        break;
                    case "castellon":
                        $valor2 = 253;
                        break;
                    case "lugo":
                        $valor2 = 268;
                        break;
                    case "alicante":
                        $valor2 = 244;
                        break;
                    case "panama":
                        $valor2 = 1139;
                        break;
                    case "albacete":
                        $valor2 = 243;
                        break;
                    case "bogota":
                        $valor2 = 797;
                        break;
                    case "roma":
                        $valor2 = 199;
                        break;
                    case "florida":
                        $valor2 = 527;
                        break;
                    case "cordoba-ar":
                        $valor2 = 609;
                        break;
                    case "milano":
                        $valor2 = 171;
                        break;
                    case "milan":
                        $valor2 = 171;
                        break;
                    case "liege":
                        $valor2 = 110;
                        break;
                    case "paris":
                        $valor2 = 77;
                        break;
                    case "vermont":
                        $valor2 = 563;
                        break;
                    case "zamora":
                        $valor2 = 290;
                        break;
                    case "segovia":
                        $valor2 = 281;
                        break;
                    default:
                        $valor2 = "";
                        break;                  
                }

                $dataFila[12] = $valor;
                $dataFila[11] = $valor2;

            } /*else if ($col == 11) {    //Pais
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

            }*/  else if ($col == 10) {    //Telefono
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();

                $dataFila[13] = $celda;
            } else if ($col == 11) {    //Fax
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $celda = trim($celda);

                $char = $celda[0];
                if ($char != "") {
                    if (ctype_digit($char)){
                        $dataFila[14] = $celda;
                    } else {
                        $dataFila[16] = $celda;
                    }
                }

            } else if ($col == 12) {    //Email
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $celda = trim($celda);

                $char = $celda[0];
                if ($char != "") {
                    if (ctype_digit($char)){
                        $dataFila[14] = $celda;
                    } else {
                        $dataFila[16] = $celda;
                    }
                }

            } /*else if ($col == 32) {    //URL
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $dataFila[15] = $celda;
            }*/ else if ($col == 15) {    //Cuenta contable
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $dataFila[52] = $celda;
            } else if ($col == 18) {    //Libre 1
                $celda = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
                $dataFila[64] = $celda;
            } /*else if ($col == 42) {    //Forma de envío
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
            }*/

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
    header('Content-Disposition: attachment; filename="proveedores_totales.xlsx"');
    header('Cache-Control: max-age=0');
    
    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($nuevoExcel, 'Xlsx');
    $writer->save('php://output');
    exit;
    


?>