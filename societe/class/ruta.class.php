<?php
/* Copyright (C) 2002-2006  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2021  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2004       Eric Seigne             <eric.seigne@ryxeo.com>
 * Copyright (C) 2003       Brian Fraval            <brian@fraval.org>
 * Copyright (C) 2006       Andre Cianfarani        <acianfa@free.fr>
 * Copyright (C) 2005-2017  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2008       Patrick Raguin          <patrick.raguin@auguria.net>
 * Copyright (C) 2010-2018  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2013       Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2013       Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2013       Peter Fontaine          <contact@peterfontaine.fr>
 * Copyright (C) 2014-2015  Marcos Garc�a           <marcosgdf@gmail.com>
 * Copyright (C) 2015       Rapha�l Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2017       Rui Strecht			    <rui.strecht@aliartalentos.com>
 * Copyright (C) 2018	    Philippe Grand	        <philippe.grand@atoo-net.com>
 * Copyright (C) 2019-2020  Josep Llu�s Amador      <joseplluis@lliuretic.cat>
 * Copyright (C) 2019-2021  Fr�d�ric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2020       Open-Dsi         		<support@open-dsi.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/societe/class/societe.class.php
 *	\ingroup    societe
 *	\brief      File for third party class
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonincoterm.class.php';
require_once DOL_DOCUMENT_ROOT.'/multicurrency/class/multicurrency.class.php';

/**
 *	Class to manage third parties objects (customers, suppliers, prospects...)
 */

 class Ruta extends Commonobject{
	public $element = 'ruta';

	public $table_element = MAIN_DB_PREFIX.'ruta';

	public $ismultientitymanaged = 0;

	public $picto = 'lock';
	
	public $fields = array(
		'id' => array('type'=>'integer', 'label'=>'id', 'visible'=>1, 'enabled'=>1,   )
	)

 }