<?php
/**
 *<b>v php library module table</b>
 *@author Vasile Giorgi
 *@license lgpl
 *@copyright 2010 (c) Vasile Giorgi
 *@version 0.01.1114
 *@description: query database and return the result into specific format: csv, html, json, pdf,  xml, xls
 **/

/* extra: config*/
define('DB_ENGINE', 'mysqli');
define('DB_SERVER', '192.168.0.4');
define('DB_PORT', 3306);
define('DB_NAME', 'mpg');
global $DB_USER, $DB_PASS;
$DB_USER = "mpgfe";
$DB_PASS = "avidry";
/*end config*/

$oReport = new Report();
$oReport -> output();


class Report
{

	private $defReports = array();
	private $options = array(
		'response' => 'html',	//html | json
		'showTitle' => FALSE, //TRUE | FALSE, (only for options.response = html), on true add report title
		'isPage' => FALSE); //TRUE | FALSE, (only for options.response = html), on true add doctype and html
	private $report = NULL;
	private $oCon = NULL;
	public $request = NULL;

	public function __construct($request = FALSE)
	{
		if($request === FALSE) {
			$this -> request = $_REQUEST;
		}
		else {
			$this -> request = $request;
		}

		/* reports definition:
		 *
		 * structure:
		 * 	reportId
		 * 		- title
		 * 		- columns [id, title, source, order, align, total]
		 * 		- source
		 * 		- filter [key, source, type]
		 */
		$this -> defReports['GeneralOverview'] = array(
			'title' => 'GENERAL OVERVIEW',
			'columns' => array(
		array(
					'id' => 'DateOfBooking',
					'label' => 'date<br/>of<br/>booking',
					'source' => "DATE_FORMAT(`mira_richieste`.`ric_dataCreazione`, '%e-%b')",
					'align' => 'c'),
		array(
					'id' => 'InvoiceNumber',
					'label' => 'Invoice<br />number',
					'source' => "`mira_fatture`.`idFatture`",
					'order' => array(2, 'asc'),
					'align' => 'c'),
		array(
					'id' => 'VoucherNumber',
					'label' => 'Voucher<br />number',
					'source' => "`mira_richieste`.`ric_codVoucher`",
					'order' => array(1),
					'align' => 'c'),
		array(
					'id' => 'Customer',
					'label' => 'Customer',
					'source' => "CONCAT_WS(' ', `anag_clienti`.`cli_nome`, `anag_clienti`.`cli_cognome`)"),
		array(
					'id' => 'Package',
					'label' => 'Package',
					'source' => "`pacc_offerte`.`off_titolo`",
					'align' => 'l'),
		array(
					'id' => 'Hotel',
					'label' => 'Hotel',
					'source' => "`anag_alberghi`.`alb_nome`",
					'align' => 'l'),
		array(
					'id' => 'ArrivalDate',
					'label' => 'arrival date',
					'source' => "DATE_FORMAT(`mira_richieste`.`ric_dataArrivo`, '%d/%m/%Y')",
					'align' => 'r'),
		array(
					'id' => 'DepartureDate',
					'label' => 'Departure<br/>date',
					'source' => "DATE_FORMAT(`mira_richieste`.`ric_dataPartenza`, '%d/%m/%Y')",
					'align' => 'r'),
		array(
					'id' => 'Arrivals',
					'label' => 'Arrivals',
					'source' => "(SELECT SUM(vcam_nAdulti + vcam_nBambini) FROM `mira_vou_camere` WHERE vcam_codRichiesta =  `mira_richieste`.`idRichieste` )",
					'align' => 'c',
					'total' => 'sum'),
		array(
					'id' => 'RoomNights',
					'label' => 'Room<br />Nights',
					'source' => "(SELECT SUM((vcam_nAdulti + vcam_nBambini) * vcam_nNotti) FROM `mira_vou_camere` WHERE vcam_codRichiesta =  `mira_richieste`.`idRichieste` )",
					'align' => 'c',
					'total' => 'sum'),
		array(
					'id' => 'VoucherAmount',
					'label' => 'Voucher<br />Amount',
					'source' => "`mira_fatture`.`fat_importo`",
					'align' => 'r',
					'format' => '&euro;&nbsp;%s',
					'total' => 'sum'),
		array(
					'id' => 'Giropay',
					'label' => 'Giropay',
					'source' => "IF(`mira_richieste`.`ric_tipoPagamento` = 'GP', `mira_richieste`.`ric_pagato`, '-')",
					'align' => 'r',
					'format' => '&euro;&nbsp;%s',
					'total' => 'sum'),
		array(
					'id' => 'Sofort',
					'label' => 'Sofort',
					'source' => "IF(`mira_richieste`.`ric_tipoPagamento` = 'SF', `mira_richieste`.`ric_pagato`, '-')",
					'align' => 'r',
					'format' => '&euro;&nbsp;%s',
					'total' => 'sum'),
		array(
					'id' => 'Ideal',
					'label' => 'Ideal',
					'source' => "IF(`mira_richieste`.`ric_tipoPagamento` = 'ID', `mira_richieste`.`ric_pagato`, '-')",
					'align' => 'r',
					'format' => '&euro;&nbsp;%s',
					'total' => 'sum'),
		array(
					'id' => 'Paypal',
					'label' => 'Paypal',
					'source' => "'-'",
					'align' => 'r',
					'format' => '&euro;&nbsp;%s',
					'total' => 'sum'),
		array(
					'id' => 'CreditCard',
					'label' => 'Credit Card',
					'source' => "IF(`mira_richieste`.`ric_tipoPagamento` = 'CC', `mira_richieste`.`ric_pagato`, '-')",
					'align' => 'r',
					'format' => '&euro;&nbsp;%s',
					'total' => 'sum'),
		array(
					'id' => 'Cash',
					'label' => 'Cash',
					'source' => "IF(`mira_richieste`.`ric_tipoPagamento` IS NULL OR `mira_richieste`.`ric_tipoPagamento` = 'BB', `mira_richieste`.`ric_pagato`, '-')",
					'align' => 'r',
					'format' => '&euro;&nbsp;%s',
					'total' => 'sum')
		),
			'source' => "
				`mira_richieste`
				INNER JOIN `mira_fatture` ON `mira_richieste`.`idRichieste` = `mira_fatture`.`fat_codRichiesta`
				INNER JOIN `pacc_offerte` ON `pacc_offerte`.`idOfferte` = `mira_richieste`.`ric_codOfferta`
				INNER JOIN `anag_clienti` ON `anag_clienti`.`idClienti` = `mira_richieste`.`ric_codCliente`
				INNER JOIN `anag_alberghi` ON `anag_alberghi`.`idAlberghi` = `mira_richieste`.`ric_codAlbergo`",
			'filter' => array(
		array(
					'key' => 'from',
					'source' => " AND `mira_richieste`.`ric_dataCreazione` >= '{@from}' ",
					'type' => 'date'),
		array(
					'key' => 'to',
					'source' => " AND `mira_richieste`.`ric_dataCreazione` <= '{@to}' ",
					'type' => 'date'),
		array(
					'key' => 'off',
					'source' => " AND `mira_richieste`.`ric_codOfferta` = {@off} "),
		array(
					'key' => 'hot',
					'source' => " AND `anag_alberghi`.`idAlberghi` = {@hot} "),
		array(
					'key' => 'pay',
					'source' => " AND `mira_richieste`.`ric_tipoPagamento` = '{@pay}' ")
		)
		);


		$this -> defReports['HotelReport'] = array(
			'title' => 'HOTEL REPORT',
			'columns' => array(
		array(
					'id' => 'NrVoucher',
					'label' => 'N&deg;<br />Voucher',
					'source' => "`mira_richieste`.`ric_codVoucher`",
					'align' => 'c',
					'order' => array(1)),
		array(
					'id' => 'Customer',
					'label' => 'Customer',
					'source' => "CONCAT_WS(' ', `anag_clienti`.`cli_nome`, `anag_clienti`.`cli_cognome`)"),
		array(
					'id' => 'Arrivals',
					'label' => 'arrivals',
					'source' => "(SELECT SUM(vcam_nAdulti + vcam_nBambini) FROM `mira_vou_camere` WHERE vcam_codRichiesta =  `mira_richieste`.`idRichieste` )",
					'align' => 'c'),
		array(
					'id' => 'RoomNights',
					'label' => 'room nights',
					'source' => "(SELECT SUM((vcam_nAdulti + vcam_nBambini) * vcam_nNotti) FROM `mira_vou_camere` WHERE vcam_codRichiesta =  `mira_richieste`.`idRichieste` )",
					'align' => 'c'),
		array(
					'id' => 'Adults',
					'label' => 'adults',
					'source' => "(SELECT SUM(vcam_nAdulti) FROM `mira_vou_camere` WHERE vcam_codRichiesta =  `mira_richieste`.`idRichieste` )",
					'align' => 'c'),
		array(
					'id' => 'Children',
					'label' => 'children',
					'source' => "(SELECT SUM(vcam_nBambini) FROM `mira_vou_camere` WHERE vcam_codRichiesta =  `mira_richieste`.`idRichieste` )",
					'align' => 'c'),
		array(
					'id' => 'Children',
					'label' => 'room type',
					'source' => "`mira_fatture`.`fat_room`",
					'align' => 'l'),
		array(
					'id' => 'ArrivalDate',
					'label' => 'arrival date',
					'source' => "DATE_FORMAT(`mira_richieste`.`ric_dataArrivo`, '%d/%m/%Y')",
					'align' => 'r'),
		array(
					'id' => 'DepartureDate',
					'label' => 'Departure<br/>date',
					'source' => "DATE_FORMAT(`mira_richieste`.`ric_dataPartenza`, '%d/%m/%Y')",
					'align' => 'r'),
		array(
					'id' => 'NetRevenue',
					'label' => 'net<br/>revenue',
					'source' => "`mira_richieste`.`ric_totale` - `mira_richieste`.`ric_totBiglietti`",
					'align' => 'r',
					'format' => '&euro;&nbsp;%s',
					'total' => 'sum'),
		),
			'source' => "
				`mira_richieste`
				INNER JOIN `mira_fatture` ON `mira_richieste`.`idRichieste` = `mira_fatture`.`fat_codRichiesta`
				INNER JOIN `anag_clienti` ON `anag_clienti`.`idClienti` = `mira_richieste`.`ric_codCliente`
				INNER JOIN `anag_alberghi` ON `anag_alberghi`.`idAlberghi` = `mira_richieste`.`ric_codAlbergo`",
			'filter' => array(
		array(
					'key' => 'from',
					'source' => " AND `mira_richieste`.`ric_dataCreazione` >= '{@from}' ",
					'type' => 'date'),
		array(
					'key' => 'to',
					'source' => " AND `mira_richieste`.`ric_dataCreazione` <= '{@to}' ",
					'type' => 'date'),
		array(
					'key' => 'hot',
					'source' => " AND `anag_alberghi`.`idAlberghi` = {@hot} "),
		)
		);


		$this -> defReports['RoomsAvailability'] = array(
			'title' => 'Rooms availability per single hotel',
			'header' => array(
		array('label' => '', 'colspan' => 1),
		array('label' => 'Allotment', 'colspan' => 6),
		array('label' => '', 'colspan' => 1),
		array('label' => 'Free Sale', 'colspan' => 6),
		array('label' => '', 'colspan' => 1),
		array('label' => 'TOTAL', 'colspan' => 6)
		),
			'columns' => array(
		array(
					'id' => 'HotelGroup',
					'source' => '`tabl_calendari`.`cal_codAlbergo`',
					'group' => 1,
					'type' => 'hidden'),
		array(
					'id' => 'Hotel',
					'label' => 'Hotel',
					'source' => '`anag_alberghi`.`alb_nome` ',
					'order' => array(1)),
		//Allotment
		array(
					'id' => 'AllotmentS',
					'label' => 'S',
					'source' => "SUM(IF(`cal_stato`= 0 AND ((`cal_occupata` = 1 AND `vcam_maskVendutaCome` = 1) OR (`cal_occupata` = 0 AND MOSTPOW2(`cam_maskVendibileCome`) = 1)), 1, 0))",
					'align' => 'r',
					'total' => ''
					),
					array(
					'id' => 'AllotmentD',
					'label' => 'D',
					'source' => "SUM(IF(`cal_stato`= 0 AND ((`cal_occupata` = 1 AND `vcam_maskVendutaCome` = 2 OR `vcam_maskVendutaCome` = 4) OR (`cal_occupata` = 0 AND (MOSTPOW2(`cam_maskVendibileCome`) = 2 OR MOSTPOW2(`cam_maskVendibileCome`) = 4))), 1, 0))",
					'align' => 'r',
					'total' => ''
					),
					array(
					'id' => 'AllotmentT',
					'label' => 'T',
					'source' => "SUM(IF(`cal_stato`= 0 AND ((`cal_occupata` = 1 AND `vcam_maskVendutaCome` = 8) OR (`cal_occupata` = 0 AND MOSTPOW2(`cam_maskVendibileCome`) = 8)), 1, 0))",
					'align' => 'r',
					'total' => ''
					),
					array(
					'id' => 'AllotmentQd',
					'label' => 'QD',
					'source' => "SUM(IF(`cal_stato`= 0 AND ((`cal_occupata` = 1 AND `vcam_maskVendutaCome` = 16) OR (`cal_occupata` = 0 AND MOSTPOW2(`cam_maskVendibileCome`) = 16)), 1, 0))",
					'align' => 'r',
					'total' => ''
					),
					array(
					'id' => 'AllotmentQt',
					'label' => 'QT',
					'source' => "SUM(IF(`cal_stato`= 0 AND ((`cal_occupata` = 1 AND `vcam_maskVendutaCome` = 32) OR (`cal_occupata` = 0 AND MOSTPOW2(`cam_maskVendibileCome`) = 32)), 1, 0))",
					'align' => 'r',
					'total' => ''
					),
					array(
					'id' => 'AllotmentTotal',
					'label' => 'TOT',
					'source' => "SUM(IF(`cal_stato`= 0, 1, 0))",
					'align' => 'r',
					'total' => ''
					),
					array(
					'id' => 'AllotmentSeparator',
					'label' => '',
					'source' => "''"
					),
					//Free sale
					array(
					'id' => 'FreeSaleS',
					'label' => 'S',
					'source' => "SUM(IF(`cal_stato`= 2 AND ((`cal_occupata` = 1 AND `vcam_maskVendutaCome` = 1) OR (`cal_occupata` = 0 AND MOSTPOW2(`cam_maskVendibileCome`) = 1)), 1, 0))",
					'align' => 'r',
					'total' => ''
					),
					array(
					'id' => 'FreeSaleD',
					'label' => 'D',
					'source' => "SUM(IF(`cal_stato`= 2 AND ((`cal_occupata` = 1 AND `vcam_maskVendutaCome` = 2 OR `vcam_maskVendutaCome` = 4) OR (`cal_occupata` = 0 AND (MOSTPOW2(`cam_maskVendibileCome`) = 2 OR MOSTPOW2(`cam_maskVendibileCome`) = 4))), 1, 0))",
					'align' => 'r',
					'total' => ''
					),
					array(
					'id' => 'FreeSaleT',
					'label' => 'T',
					'source' => "SUM(IF(`cal_stato`= 2 AND ((`cal_occupata` = 1 AND `vcam_maskVendutaCome` = 8) OR (`cal_occupata` = 0 AND MOSTPOW2(`cam_maskVendibileCome`) = 8)), 1, 0))",
					'align' => 'r',
					'total' => ''
					),
					array(
					'id' => 'FreeSaleQd',
					'label' => 'QD',
					'source' => "SUM(IF(`cal_stato`= 2 AND ((`cal_occupata` = 1 AND `vcam_maskVendutaCome` = 16) OR (`cal_occupata` = 0 AND MOSTPOW2(`cam_maskVendibileCome`) = 16)), 1, 0))",
					'align' => 'r',
					'total' => ''
					),
					array(
					'id' => 'FreeSaleQt',
					'label' => 'QT',
					'source' => "SUM(IF(`cal_stato`= 2 AND ((`cal_occupata` = 1 AND `vcam_maskVendutaCome` = 32) OR (`cal_occupata` = 0 AND MOSTPOW2(`cam_maskVendibileCome`) = 32)), 1, 0))",
					'align' => 'r',
					'total' => ''
					),
					array(
					'id' => 'AllotmentTotal',
					'label' => 'TOT',
					'source' => "SUM(IF(`cal_stato`= 2, 1, 0))",
					'align' => 'r',
					'total' => ''
					),
					array(
					'id' => 'FreeSaleSeparator',
					'label' => '',
					'source' => "''"
					),
					//totals
					array(
					'id' => 'TotalS',
					'label' => 'S',
					'source' => "SUM(IF((`cal_occupata` = 1 AND `vcam_maskVendutaCome` = 1) OR (`cal_occupata` = 0 AND MOSTPOW2(`cam_maskVendibileCome`) = 1), 1, 0))",
					'align' => 'r',
					'total' => ''
					),
					array(
					'id' => 'TotalD',
					'label' => 'D',
					'source' => "SUM(IF((`cal_occupata` = 1 AND (`vcam_maskVendutaCome` = 2 OR `vcam_maskVendutaCome` = 4)) OR (`cal_occupata` = 0 AND (MOSTPOW2(`cam_maskVendibileCome`) = 2 OR MOSTPOW2(`cam_maskVendibileCome`) = 4)) , 1, 0))",
					'align' => 'r',
					'total' => ''
					),
					array(
					'id' => 'TotalT',
					'label' => 'T',
					'source' => "SUM(IF((`cal_occupata` = 1 AND `vcam_maskVendutaCome` = 8) OR (`cal_occupata` = 0 AND MOSTPOW2(`cam_maskVendibileCome`) = 8), 1, 0))",
					'align' => 'r',
					'total' => ''
					),
					array(
					'id' => 'TotalQd',
					'label' => 'QD',
					'source' => "SUM(IF((`cal_occupata` = 1 AND `vcam_maskVendutaCome` = 16) OR (`cal_occupata` = 0 AND MOSTPOW2(`cam_maskVendibileCome`) = 16), 1, 0))",
					'align' => 'r',
					'total' => ''
					),
					array(
					'id' => 'TotalQt',
					'label' => 'QT',
					'source' => "SUM(IF((`cal_occupata` = 1 AND `vcam_maskVendutaCome` = 32) OR (`cal_occupata` = 0 AND MOSTPOW2(`cam_maskVendibileCome`) = 32), 1, 0))",
					'align' => 'r',
					'total' => ''
					),
					array(
					'id' => 'TotalTot',
					'label' => 'TOT',
					'source' => "COUNT(`idCalendari`)",
					'align' => 'r',
					'total' => ''
					)
					),
			'source' =>
				"`tabl_calendari` "
				."LEFT JOIN `mira_richieste` ON `mira_richieste`.`idRichieste` = `tabl_calendari`.`cal_codRichiesta` "
				."INNER JOIN `anag_alberghi` ON `anag_alberghi`.`idAlberghi` = `tabl_calendari`.`cal_codAlbergo` "
				."INNER JOIN `anag_alb_camere` ON `anag_alb_camere`.`idCamere` = `tabl_calendari`.`cal_codCamera` "
				."LEFT JOIN `mira_vou_camere` ON `mira_vou_camere`.`vcam_codRichiesta` = `mira_richieste`.`idRichieste` ",
			'filter' => array(
				array(
					'key' => '',
					'source' => "AND `tabl_calendari`.`cal_disponibile` = 1 "),
				array(
					'key' => 'rep',
					'source' => "AND `tabl_calendari`.`cal_occupata` = {@rep} "),
				array(
					'key' => 'day',
					'source' => " AND `tabl_calendari`.`cal_data` = '{@day}' ",
					'type' => 'date'),
				array(
					'key' => 'cat',
					'source' => " AND `anag_alberghi`.`alb_nStelle` = {@cat} ")
				)
				);


				$this -> defReports['Incomes'] = array(
			'title' => 'Incomes by date of purchase',
			'columns' => array(
				array(
					'id' => 'RequestDate',
					'source' => "DATE(`mira_richieste`.`ric_dataCreazione`)",
					'group' => 1,
					'type' => 'hidden',
					'order' => array(1)),
				array(
					'id' => 'RequestDate',
					'source' => "DATE_FORMAT(`mira_richieste`.`ric_dataCreazione`, '%d/%m/%Y')",
					'align' => 'r'),
				array(
					'id' => 'Tickets',
					'label' => 'Tickets',
					'source' => "SUM(`ric_totBiglietti`)",
					'align' => 'r',
					'format' => '&euro;&nbsp;%s',
					'total' => ''),
				array(
					'id' => 'Rooms',
					'label' => 'Rooms',
					'source' => "SUM(`ric_totale` - `ric_totBiglietti`)",
					'align' => 'r',
					'format' => '&euro;&nbsp;%s',
					'total' => ''),
				array(
					'id' => 'AllInclusiveFee',
					'label' => 'all inclusive fee',
					'source' => "0",
					'align' => 'r',
					'format' => '&euro;&nbsp;%s',
					'total' => ''),
				array(
					'id' => 'CreditCardFee',
					'label' => 'credit card fee',
					'source' => "SUM(`fat_ccFee`)",
					'align' => 'r',
					'format' => '&euro;&nbsp;%s',
					'total' => ''),
				array(
					'id' => 'Total',
					'label' => 'Total',
					'source' => "SUM(`ric_totale`) + SUM(`fat_ccFee`)",
					'align' => 'r',
					'format' => '&euro;&nbsp;%s',
					'total' => '')
				),
			'source' => "`mira_richieste`"
			." INNER JOIN `mira_fatture` ON `mira_fatture`.`fat_codRichiesta` = `mira_richieste`.`idRichieste` AND `idFatture` IS NOT NULL",
			'filter' => array(
			array(
					'key' => '',
					'source' => "AND `ric_codVoucher` IS NOT NULL"),
			array(
					'key' => 'from',
					'source' => " AND `mira_richieste`.`ric_dataCreazione` >= '{@from}' ",
					'type' => 'date'),
			array(
					'key' => 'to',
					'source' => " AND `mira_richieste`.`ric_dataCreazione` <= '{@to}' ",
					'type' => 'date'),
			array(
					'key' => 'hot',
					'source' => " AND `mira_richieste`.`ric_codAlbergo` = {@hot} "),
			array(
					'key' => 'off',
					'source' => " AND `mira_richieste`.`ric_codOfferta` = {@off} ")
			)
			);


			$this -> defReports['Visitors'] = array(
			'title' => 'Visitors',
			'columns' => array(
			array(
					'id' => 'ArrivalDateOrder',
					'source' => "DATE(`mira_richieste`.`ric_dataArrivo`)",
					'group' => 1,
					'type' => 'hidden',
					'order' => array(1)),
			array(
					'id' => 'ArrivalDateDisplay',
					'source' => "DATE_FORMAT(`mira_richieste`.`ric_dataArrivo`, '%d/%m/%Y')",
					'align' => 'r'),
			array(
					'id' => 'Voucher',
					'label' => 'voucher',
					'source' => "COUNT(`mira_richieste`.`idRichieste`)",
					'align' => 'r',
					'total' => ''),
			array(
					'id' => 'Arrivals',
					'label' => 'arrivals',
					'source' => "SUM(`mira_vou_camere`.`vcam_nAdulti` + `mira_vou_camere`.`vcam_nBambini`)",
					'align' => 'r',
					'total' => ''),
			array(
					'id' => 'RoomNights',
					'label' => 'room nights',
					'source' => "SUM((`mira_vou_camere`.`vcam_nAdulti` + `mira_vou_camere`.`vcam_nBambini`) * `mira_vou_camere`.`vcam_nNotti`)",
					'align' => 'r',
					'total' => '')
			),
			'source' => "`mira_richieste` "
			."INNER JOIN `mira_vou_camere` ON `mira_vou_camere`.`vcam_codRichiesta` = `mira_richieste`.`idRichieste`",
			'filter' => array(
			array(
					'key' => '',
					'source' =>
						"AND `ric_codVoucher` IS NOT NULL "
						."AND `mira_richieste`.`ric_dataArrivo` IS NOT NULL" ),
						array(
					'key' => 'from',
					'source' => " AND `mira_richieste`.`ric_dataArrivo` >= '{@from}' ",
					'type' => 'date'),
						array(
					'key' => 'to',
					'source' => " AND `mira_richieste`.`ric_dataArrivo` <= '{@to}' ",
					'type' => 'date'),
						array(
					'key' => 'hot',
					'source' => " AND `mira_richieste`.`ric_codAlbergo` = {@hot} "),
						array(
					'key' => 'off',
					'source' => " AND `mira_richieste`.`ric_codOfferta` = {@off} ")
						)
						);


						$this -> defReports['RoomsAvailPerDay'] = array(
			'title' => 'Rooms availability',
			'header' => array(
						array('label' => '', 'colspan' => 1),
						array('label' => 'Allotment', 'colspan' => 6),
						array('label' => '', 'colspan' => 1),
						array('label' => 'Free Sale', 'colspan' => 6),
						array('label' => '', 'colspan' => 1),
						array('label' => 'TOTAL', 'colspan' => 6)
						),
			'columns' => array(
						array(
					'id' => 'BookingDayOrder',
					'source' => '`tabl_calendari`.`cal_data`',
					'group' => 1,
					'order' => array(1),
					'type' => 'hidden'),
						array(
					'id' => 'BookingDay',
					'source' => "DATE_FORMAT(`tabl_calendari`.`cal_data`, '%d/%m/%Y')"),
						//Allotment
						array(
					'id' => 'AllotmentS',
					'label' => 'S',
					'source' => "SUM(IF(`cal_stato`= 0 AND ((`cal_occupata` = 1 AND `vcam_maskVendutaCome` = 1) OR (`cal_occupata` = 0 AND MOSTPOW2(`cam_maskVendibileCome`) = 1)), 1, 0))",
					'align' => 'r',
					'total' => ''
					),
					array(
					'id' => 'AllotmentD',
					'label' => 'D',
					'source' => "SUM(IF(`cal_stato`= 0 AND ((`cal_occupata` = 1 AND `vcam_maskVendutaCome` = 2 OR `vcam_maskVendutaCome` = 4) OR (`cal_occupata` = 0 AND (MOSTPOW2(`cam_maskVendibileCome`) = 2 OR MOSTPOW2(`cam_maskVendibileCome`) = 4))), 1, 0))",
					'align' => 'r',
					'total' => ''
					),
					array(
					'id' => 'AllotmentT',
					'label' => 'T',
					'source' => "SUM(IF(`cal_stato`= 0 AND ((`cal_occupata` = 1 AND `vcam_maskVendutaCome` = 8) OR (`cal_occupata` = 0 AND MOSTPOW2(`cam_maskVendibileCome`) = 8)), 1, 0))",
					'align' => 'r',
					'total' => ''
					),
					array(
					'id' => 'AllotmentQd',
					'label' => 'QD',
					'source' => "SUM(IF(`cal_stato`= 0 AND ((`cal_occupata` = 1 AND `vcam_maskVendutaCome` = 16) OR (`cal_occupata` = 0 AND MOSTPOW2(`cam_maskVendibileCome`) = 16)), 1, 0))",
					'align' => 'r',
					'total' => ''
					),
					array(
					'id' => 'AllotmentQt',
					'label' => 'QT',
					'source' => "SUM(IF(`cal_stato`= 0 AND ((`cal_occupata` = 1 AND `vcam_maskVendutaCome` = 32) OR (`cal_occupata` = 0 AND MOSTPOW2(`cam_maskVendibileCome`) = 32)), 1, 0))",
					'align' => 'r',
					'total' => ''
					),
					array(
					'id' => 'AllotmentTotal',
					'label' => 'TOT',
					'source' => "SUM(IF(`cal_stato`= 0, 1, 0))",
					'align' => 'r',
					'total' => ''
					),
					array(
					'id' => 'AllotmentSeparator',
					'label' => '',
					'source' => "''"
					),
					//Free sale
					array(
					'id' => 'FreeSaleS',
					'label' => 'S',
					'source' => "SUM(IF(`cal_stato`= 2 AND ((`cal_occupata` = 1 AND `vcam_maskVendutaCome` = 1) OR (`cal_occupata` = 0 AND MOSTPOW2(`cam_maskVendibileCome`) = 1)), 1, 0))",
					'align' => 'r',
					'total' => ''
					),
					array(
					'id' => 'FreeSaleD',
					'label' => 'D',
					'source' => "SUM(IF(`cal_stato`= 2 AND ((`cal_occupata` = 1 AND `vcam_maskVendutaCome` = 2 OR `vcam_maskVendutaCome` = 4) OR (`cal_occupata` = 0 AND (MOSTPOW2(`cam_maskVendibileCome`) = 2 OR MOSTPOW2(`cam_maskVendibileCome`) = 4))), 1, 0))",
					'align' => 'r',
					'total' => ''
					),
					array(
					'id' => 'FreeSaleT',
					'label' => 'T',
					'source' => "SUM(IF(`cal_stato`= 2 AND ((`cal_occupata` = 1 AND `vcam_maskVendutaCome` = 8) OR (`cal_occupata` = 0 AND MOSTPOW2(`cam_maskVendibileCome`) = 8)), 1, 0))",
					'align' => 'r',
					'total' => ''
					),
					array(
					'id' => 'FreeSaleQd',
					'label' => 'QD',
					'source' => "SUM(IF(`cal_stato`= 2 AND ((`cal_occupata` = 1 AND `vcam_maskVendutaCome` = 16) OR (`cal_occupata` = 0 AND MOSTPOW2(`cam_maskVendibileCome`) = 16)), 1, 0))",
					'align' => 'r',
					'total' => ''
					),
					array(
					'id' => 'FreeSaleQt',
					'label' => 'QT',
					'source' => "SUM(IF(`cal_stato`= 2 AND ((`cal_occupata` = 1 AND `vcam_maskVendutaCome` = 32) OR (`cal_occupata` = 0 AND MOSTPOW2(`cam_maskVendibileCome`) = 32)), 1, 0))",
					'align' => 'r',
					'total' => ''
					),
					array(
					'id' => 'AllotmentTotal',
					'label' => 'TOT',
					'source' => "SUM(IF(`cal_stato`= 2, 1, 0))",
					'align' => 'r',
					'total' => ''
					),
					array(
					'id' => 'FreeSaleSeparator',
					'label' => '',
					'source' => "''"
					),
					//totals
					array(
					'id' => 'TotalS',
					'label' => 'S',
					'source' => "SUM(IF((`cal_occupata` = 1 AND `vcam_maskVendutaCome` = 1) OR (`cal_occupata` = 0 AND MOSTPOW2(`cam_maskVendibileCome`) = 1), 1, 0))",
					'align' => 'r',
					'total' => ''
					),
					array(
					'id' => 'TotalD',
					'label' => 'D',
					'source' => "SUM(IF((`cal_occupata` = 1 AND (`vcam_maskVendutaCome` = 2 OR `vcam_maskVendutaCome` = 4)) OR (`cal_occupata` = 0 AND (MOSTPOW2(`cam_maskVendibileCome`) = 2 OR MOSTPOW2(`cam_maskVendibileCome`) = 4)) , 1, 0))",
					'align' => 'r',
					'total' => ''
					),
					array(
					'id' => 'TotalT',
					'label' => 'T',
					'source' => "SUM(IF((`cal_occupata` = 1 AND `vcam_maskVendutaCome` = 8) OR (`cal_occupata` = 0 AND MOSTPOW2(`cam_maskVendibileCome`) = 8), 1, 0))",
					'align' => 'r',
					'total' => ''
					),
					array(
					'id' => 'TotalQd',
					'label' => 'QD',
					'source' => "SUM(IF((`cal_occupata` = 1 AND `vcam_maskVendutaCome` = 16) OR (`cal_occupata` = 0 AND MOSTPOW2(`cam_maskVendibileCome`) = 16), 1, 0))",
					'align' => 'r',
					'total' => ''
					),
					array(
					'id' => 'TotalQt',
					'label' => 'QT',
					'source' => "SUM(IF((`cal_occupata` = 1 AND `vcam_maskVendutaCome` = 32) OR (`cal_occupata` = 0 AND MOSTPOW2(`cam_maskVendibileCome`) = 32), 1, 0))",
					'align' => 'r',
					'total' => ''
					),
					array(
					'id' => 'TotalTot',
					'label' => 'TOT',
					'source' => "COUNT(`idCalendari`)",
					'align' => 'r',
					'total' => ''
					)
					),
			'source' =>
				"`tabl_calendari` "
				."LEFT JOIN `mira_richieste` ON `mira_richieste`.`idRichieste` = `tabl_calendari`.`cal_codRichiesta` "
				."INNER JOIN `anag_alberghi` ON `anag_alberghi`.`idAlberghi` = `tabl_calendari`.`cal_codAlbergo` "
				."INNER JOIN `anag_alb_camere` ON `anag_alb_camere`.`idCamere` = `tabl_calendari`.`cal_codCamera` "
				."LEFT JOIN `mira_vou_camere` ON `mira_vou_camere`.`vcam_codRichiesta` = `mira_richieste`.`idRichieste` ",
			'filter' => array(
				array(
					'key' => '',
					'source' => "AND `tabl_calendari`.`cal_disponibile` = 1 "),
				array(
					'key' => 'from',
					'source' => " AND `tabl_calendari`.`cal_data` >= '{@from}' ",
					'type' => 'date'),
				array(
					'key' => 'to',
					'source' => " AND `tabl_calendari`.`cal_data` <= '{@to}' ",
					'type' => 'date'),
				array(
					'key' => 'rep',
					'source' => "AND `tabl_calendari`.`cal_occupata` = {@rep} "),
				array(
					'key' => 'hot',
					'source' => " AND `tabl_calendari`.`cal_codAlbergo` = {@hot} ")
				)
				);


				$this -> defReports['AccountingReport'] = array(
			'title' => 'Riepilogo incassi  in base alla data di competenza (per amministrazione)',
			'columns' => array(
				array(
					'id' => 'ArrivalDateOrder',
					'source' => "DATE(`mira_richieste`.`ric_dataArrivo`)",
					'group' => 1,
					'type' => 'hidden',
					'order' => array(1)),
				array(
					'id' => 'ArrivalDateDisplay',
					'label' => 'Giorno',
					'source' => "DATE_FORMAT(`mira_richieste`.`ric_dataArrivo`, '%d/%m/%Y')",
					'align' => 'r'),
				array(
					'id' => 'Rooms',
					'label' => 'Fatturato hotel',
					'source' => "SUM(`ric_totale` - `ric_totBiglietti`)",
					'align' => 'r',
					'format' => '&euro;&nbsp;%s',
					'total' => ''),
				array(
					'id' => 'Tickets',
					'label' => 'Mpg tkt',
					'source' => "SUM(`ric_totBiglietti`)",
					'align' => 'r',
					'format' => '&euro;&nbsp;%s',
					'total' => ''),
				array(
					'id' => 'Total',
					'label' => 'Totale',
					'source' => "SUM(`ric_totale`) + SUM(`fat_ccFee`)",
					'align' => 'r',
					'format' => '&euro;&nbsp;%s',
					'total' => '')
				),
			'source' => "`mira_richieste`"
			." INNER JOIN `mira_fatture` ON `mira_fatture`.`fat_codRichiesta` = `mira_richieste`.`idRichieste` AND `idFatture` IS NOT NULL",
			'filter' => array(
			array(
					'key' => '',
					'source' => "AND `ric_codVoucher` IS NOT NULL"),
			array(
					'key' => 'from',
					'source' => " AND `mira_richieste`.`ric_dataArrivo` >= '{@from}' ",
					'type' => 'date'),
			array(
					'key' => 'to',
					'source' => " AND `mira_richieste`.`ric_dataArrivo` <= '{@to}' ",
					'type' => 'date')
			)
			);

			$this -> inputValidation();
	}


	private function inputValidation()
	{
		/*
		 * Parameters in request:
		 * - report
		 * - response: JSON/HTML (default = HTML)

		 */
		// check security:
		//		require_once('../../../config.php');
		//		require_once($_SERVER['DOCUMENT_ROOT'].'/dbConnBackEnd.php');
		//		require_once($_SERVER['DOCUMENT_ROOT'].'/common/db.php');
		//		require_once($_SERVER['DOCUMENT_ROOT'].'/classes/clsSec.php');

		if (!(Sec::IsLoggedIn() && Sec::HasAccess('backoffice', 9))) {
			if(isset($_REQUEST['key'])) {
				if(!Sec::LoginWithKey($_REQUEST['key'])) {
					$this -> outputHelp('Access denied - wrong key!');
					return FALSE;
				}
			}
			else {
				$this -> outputHelp('Access denied!');
				return FALSE;
			}
		}

		// check if report name was set:
		if(isset($this -> request['report'])) {
			$this -> report = $this -> request['report'];
		}
		else {
			$this -> options['isPage'] = TRUE;
			$this -> outputHelp('Missing parameter: report !');
			return false;
		}
		// check if report exist on definitions:
		if(isset($this -> defReports[$this -> report]))
		{
			$this -> report = $this -> defReports[$this -> report];
		}
		else
		{
			$this -> outputHelp('Invalid report: '.$this -> report);
			return false;
		}

		//response type is not mandatory:
		if(isset($this -> request['response'])) {
			$this -> request['response'] = strtolower($request['response']);
		}
		else {
			$this -> request['response'] = 'html';
		}

		if(isset($_REQUEST['help'])) {
			$this -> options['isPage'] = TRUE;
		}

		//normalize table definition:
		$iMax = count($this -> report['columns']);
		//columns:
		for($i = 0; $i < $iMax; $i++)
		{
			//default align = left:
			if(!isset($this -> report['columns'][$i]['align'])) {
				$this -> report['columns'][$i]['align'] = 'l';
			}
			//default label = '';
			if(!isset($this -> report['columns'][$i]['label'])) {
				$this -> report['columns'][$i]['label'] = '';
			}
		}
		//default filter = strings:
		foreach($this -> report['filter'] as $k => $v) {
			if(!isset($v['type'])) {
				$this -> report['filter'][$k]['type'] = 'string';
			}
		}
	}


	private function buildSql()
	{
		require_once($_SERVER['DOCUMENT_ROOT'].'/classes/clsConv.php');

		//select
		$sql = 'SELECT ';
		$iMax = count($this -> report['columns']);
		for($i = 0; $i < $iMax; $i++)
		{
			if(isset($this -> report['columns'][$i]['type'])) {
				if($this -> report['columns'][$i]['type'] === 'hidden') {
					//do nothing
				}
				else {
					//do somethig
				}
			}
			else {
				$sql .= $this -> report['columns'][$i]['source']." AS '".$this -> report['columns'][$i]['id']."', ";
			}
		}
		$sql = substr($sql, 0, strlen($sql) - 2);
		//from
		$sqlFrom = ' FROM '.$this->report['source'].' ';
		$sql .= $sqlFrom;
		//where
		$sqlWhere = 'WHERE 0 = 0 ';
		if(isset($this -> report['filter'])) {
			$iMax = count($this -> report['filter']);
			for($i = 0; $i < $iMax; $i++)
			{
				$key = $this -> report['filter'][$i]['key'];
				if($key === '') {
					$sqlWhere .= $this -> report['filter'][$i]['source'];
				}
				else {
					if(isset($this -> request[$key]) && $this -> request[$key] !== '')
					{
						switch($this -> report['filter'][$i]['type'])
						{
							case 'string':
								//do nothing;
								break;
							case 'date':
								$this -> request[$key] = Conv::Dtm2Db($this -> request[$key]);
								break;
						}
						$sqlWhere .= str_replace('{@'.$key.'}', $this -> request[$key], $this -> report['filter'][$i]['source']);
					}
				}
			}
		}
		$sql .= $sqlWhere;
		//group by
		$iMax = count($this -> report['columns']);
		$aGroup = array();
		for($i = 0; $i < $iMax; $i++)
		{
			if(isset($this -> report['columns'][$i]['group'])) {
				$aGroup[$this -> report['columns'][$i]['group']] = $this -> report['columns'][$i]['source'];
			}
		}
		if(count($aGroup) > 0)
		{
			ksort($aGroup, SORT_NUMERIC);
			$sql .= " GROUP BY ";
			foreach ($aGroup as $k => $v)
			{
				$sql .= $v.', ';
			}
			$sql = substr($sql, 0, strlen($sql) - 2);
		}

		//order by
		$iMax = count($this -> report['columns']);
		$aOrder = array();
		for($i = 0; $i < $iMax; $i++)
		{
			if(isset($this -> report['columns'][$i]['order'])) {
				$aOrder[$this -> report['columns'][$i]['order'][0]] = $this -> report['columns'][$i]['source'];
				if(isset($this -> report['columns'][$i]['order'][1])) {
					$aOrder[$this -> report['columns'][$i]['order'][0]] .= ' '.$this -> report['columns'][$i]['order'][1];
				}
			}
		}

		if(count($aOrder) > 0)
		{
			ksort($aOrder, SORT_NUMERIC);
			$sql .= " ORDER BY ";
			foreach ($aOrder as $k => $v)
			{
				$sql .= $v.', ';
			}
			$sql = substr($sql, 0, strlen($sql) - 2);
		}


		//build sql for total:
		$sqlTotal = 'SELECT ';
		$sqlTotalGroup = 'GROUP BY ';
		$iMax = count($this -> report['columns']);
		for($i = 0; $i < $iMax; $i++)
		{
			if(isset($this -> report['columns'][$i]['total'])) {
				$sqlTotal .= $this -> report['columns'][$i]['total']."(".$this -> report['columns'][$i]['source'].")AS '".$this -> report['columns'][$i]['id']."', ";
			}
			else {
				if(isset($this -> report['columns'][$i]['type']) && $this -> report['columns'][$i]['type'] === 'hidden') {
					//do nothing - is hidden
				}
				else {
					$sqlTotal .= "'', ";
				}
				//$sqlTotalGroup .= $this -> report['columns'][$i]['source'].', ';
			}
		}
		if (strlen($sqlTotal) > 7) {
			$sqlTotal = substr($sqlTotal, 0, strlen($sqlTotal) - 2);
			$sqlTotalGroup = substr($sqlTotalGroup, 0, strlen($sqlTotalGroup) - 2);

			$sqlTotal .= $sqlFrom.' '.$sqlWhere.' ';
		}
		else {
			$sqlTotal = FALSE;
		}

		return(array('sql' => $sql, 'total' => $sqlTotal));
	}


	private function result()
	{
		require_once($_SERVER['DOCUMENT_ROOT'].'/common/clisrv.php');

		$aSql = $this -> buildSql();

		//connect to db:
		$oC = DBConnect();
		if(!$oC) {
			$this -> outputHelp('DB connect!');
			return false;
		}
		if (!$rs = @$oC->query($aSql['sql'])) {
			$this->outputHelp('SQL:'.$aSql['sql']);
			return false;
		}

		//get the result:
		switch($this -> request['response'])
		{
			case 'html':
				$result = '';
				$pattern = '<tr>';
				$iMax = count($this -> report['columns']);
				for($i = 0; $i < $iMax; $i++)
				{
					if(isset($this -> report['columns'][$i]['type']) && $this -> report['columns'][$i]['type'] === 'hidden') {
						//exclude it
					}
					else {
						if(isset($this -> report['columns'][$i]['format'])) {
							$format = $this -> report['columns'][$i]['format'];
						}
						else {
							$format = '%s';
						}
						if($this -> report['columns'][$i]['align'] !== 'l') {
							$pattern .= '<td class="'.$this -> report['columns'][$i]['align'].'">'.$format.'</td>';
						}
						else {
							$pattern .= '<td>'.$format.'</td>';
						}
					}
				}
				$pattern .= "</tr>\n";
				while ($r = $rs->fetch_row()) {
					$result .= vsprintf($pattern, $r);
				}
				break;
			case 'json':
				$result = array();
				while ($row = $rs->fetch_assoc()) {
					$result[] = $row;
				}
				break;
		}

		//totals
		switch($this -> request['response'])
		{
			case 'html':
				if($aSql['total'] !== FALSE) {
					if (!$rs = @$oC->query($aSql['total'])) {
						$this->outputHelp('SQL for total:'.$aSql['total']);
						return false;
					}
					$pattern = str_replace('<td', '<th', $pattern);
					$pattern = str_replace('</td>', '</th>', $pattern);
					while ($r = $rs->fetch_row()) {
						$result .= vsprintf($pattern, $r);
					}
				}
				if(@$_REQUEST['test'] === 'on') {
					$result .= '<tr><th colspan="'.$iMax.'">test</th></tr>'
					.'<tr><td>main sql:</td><td colspan="'.($iMax - 1).'">'.$aSql['sql'].'</td></tr>'
					.'<tr><td>total sql:</td><td colspan="'.($iMax - 1).'">'.$aSql['total'].'</td></tr>';
				}
				break;
			case 'json':
				break;
		}

		return($result);
	}


	public function outputHtml()
	{
		if($this -> options['isPage']) {
			echo(
				'<!DOCTYPE html><html><head>'
				.'<meta http-equiv="content-type" content="text/html; charset=UTF-8" />'
				.'<link type="text/css" href="/reports.css" rel="stylesheet"/>'
				.'<title>'.$this -> report['title'].'</title>'
				.'</head>'
				.'<body class="result">');
		}

		if(isset($_REQUEST['help']) && $_REQUEST['help'] === 'on') {
			$this->outputHelpForReport();
			return;
		}
		if($this -> options['showTitle']) {
			//output title:
			echo('<h1>'.$this -> report['title'].'</h1>');
		}

		//main table:
		echo('<table cellspacing="0" cellpadding="4">');
		echo('<caption>'.$this -> report['title'].'</caption>');


		//columns style:
		/*
		 * 		$iMax = count($this -> report['columns']);
		 echo('<colgroup>');
		 for($i = 0; $i < $iMax; $i++)
		 {
			echo('<col class="'.$this -> report['columns'][$i]['align'].'" />');
			}
			echo('</colgroup>');
			*/
		//header
		echo('<thead><tr>');
		if(isset($this -> report['header'])) {
			$iMax = count($this -> report['header']);
			for($i = 0; $i < $iMax; $i++) {
				if($this -> report['header'][$i]['colspan'] > 1) {
					echo('<th colspan="'.$this -> report['header'][$i]['colspan'].'">');
				}
				else {
					echo('<th>');
				}
				echo($this -> report['header'][$i]['label'].'</th>');
			}
			echo('</tr><tr>');
		}

		$iMax = count($this -> report['columns']);
		for($i = 0; $i < $iMax; $i++)
		{
			if(isset($this -> report['columns'][$i]['type']) && $this -> report['columns'][$i]['type'] === 'hidden') {

			}
			else {
				echo('<th>'.$this -> report['columns'][$i]['label'].'</th>');
			}
		}
		echo('</tr></thead>');

		//body
		echo('<tbody>'.$this->result().'</tbody>');

		//footer (totals)
		echo('<tfoot>');
		echo('</tfoot>');
		echo('</table>');

		if($this -> options['isPage']) {
			echo('</body></html>');
		}
	}


	private function outputHelpForReport() {
		echo('<div class="help">');
		echo('<h2>Help for report: '.$this->report['title'].'</h2>');
		echo('<a href="/reports.php?help=reports">back</a>');
		echo('<h3>Parameters</h3>');
		echo('<form action="http://'.$_SERVER['HTTP_HOST'].'/admin/pages/riepiloghi/riepiloghi.php" method="post" >');
		echo('<table class="help" cellspacing="0" cellpadding="4"><thead><tr><th>Key</th><th>Value</th><th>Type</th><th>Observations</th><th>Test</th></tr></thead>');
		echo('<tbody><tr><th class="l">report</th><td>'.$this->request['report'].'</td><td>string</td><td>mandatory</td><td><input type="hidden" name="report" value="'.$this->request['report'].'"/><input type="hidden" name="test" value="on" /></td></tr>');
		echo('<tr><th class="l">help</th><td>on</td><td>string</td><td>will not get any result, instead will display this help</td></tr>');
		if(isset($this -> report['filter'])) {
			$iMax = count($this -> report['filter']);
			for($i = 0; $i < $iMax; $i++) {
				echo('<tr><th class="l">'.$this -> report['filter'][$i]['key'].'</th><td>-</td><td>'.$this -> report['filter'][$i]['type'].'</td>');
				if($this -> report['filter'][$i]['key'] === '') {
					echo('<td>'.$this -> report['filter'][$i]['source'].'</td><td></td>');
				}
				else {
					echo('<td>SQL: '.$this -> report['filter'][$i]['source'].'</td><td><input type="text" name="'.$this -> report['filter'][$i]['key'].'" />');
				}
				echo('</tr>');
			}
		}
		echo('<tr><td colspan="4"></td><td><input type="submit" value="test" /></td></tr>');
		echo('</tbody></table>');

		echo('</form>');
		echo('<h3>Columns</h3>');
		echo('<table class="help" cellspacing="0" cellpadding="4"><thead><tr><th>Id</th><th>Label</th><th>Group</th><th>Order</th><th>Total</th><th>Align</th><th>Format</th><th>Source</th></tr></thead>');
		$iMax = count($this -> report['columns']);
		echo('<tbody>');
		for($i = 0; $i < $iMax; $i++) {
			echo('<tr>'
			.'<td>'.$this -> report['columns'][$i]['id'].'</td>'
			.'<td>'.$this -> report['columns'][$i]['label'].'</td>'
			.'<td>'.@$this -> report['columns'][$i]['group'].'</td>'
			.'<td>'.@$this -> report['columns'][$i]['order'][0].' - '.@$this -> report['columns'][$i]['order'][1].'</td>'
			.'<td>'.@$this -> report['columns'][$i]['total'].'</td>'
			.'<td>'.$this -> report['columns'][$i]['align'].'</td>'
			.'<td>'.@$this -> report['columns'][$i]['format'].'</td>'
			.'<td>SQL: '.$this -> report['columns'][$i]['source'].'</td></tr>');
		}
		echo('</tbody></table>');

		echo('<h3>Source</h3><div class="code">'.$this -> report['source'].'</div>');

		echo('<h3>SQL</h3>');
		$aSql = $this->buildSql();
		echo('<h4>Main</h4>');
		echo('<div class="code">'.$aSql['sql'].'</div>');
		echo('<h4>Total</h4>');
		echo('<div class="code">'.$aSql['total'].'</div>');

		echo('<h3>Definitions</h3>');
		echo('<div class="code"><textarea rows="20" cols="100">'.print_r($this->report, true).'</textarea></div>');
		echo('</div>');
	}

	private function outputHelp($sError)
	{
		?>
<!DOCTYPE html>
<html>
<head>
<title>Reports</title>
<link type="text/css" href="/reports.css" rel="stylesheet" />
</head>
<body>
	<h1>Riepilogi</h1>
	<div>
<?php
	if(isset($_REQUEST['help']) && $_REQUEST['help'] === 'reports') {
		echo('<h2>Defined reports:</h2><ul>');
		foreach ($this -> defReports as $k1 => $v1) {
			echo('<li>'.$v1['title'].'&nbsp;<a href="/reports.php?report='.$k1.'&help=on">info</a></li>');
		}
		echo('</ul>');
	}
	else {
		echo('<a href="/reports.php?help=reports">Help</a>');
	}
?>
	</div>
	<div class="error">
		ERROR:
		<?php echo($sError);?>
	</div>
</body>
</html>
		<?php
		exit();
	}


	public function output()
	{
		switch($this -> request['response'])
		{
			case 'html':
				$this -> outputHtml();
				break;
			case 'json':
				$this -> outputJson();
				break;
			default:
				echo($this -> request['response']);
				break;
		}
	}
}



/* Extra definition (dependencies)*/
function DBConnect($strUsername=null, $strPassword=null, $strDatabaseName=DB_NAME, $strServerName=DB_SERVER, $strServerPort=DB_PORT)
{
	global $DB_USER, $DB_PASS;
	//echo "$strUsername\n$strPassword\n$strDatabaseName\n$strServerName\n$strServerPort";
	if (!$strServerName) $strServerName = '127.0.0.1';
	if($strUsername == null || $strUsername == ""){
		$strUsername = $DB_USER;
	}
	if($strPassword == null){
		$strPassword = $DB_PASS;
	}
	$oC = @mysqli_connect($strServerName, $strUsername, $strPassword, $strDatabaseName, $strServerPort);
	if (!$oC) {
		//die(' Error #'.mysqli_connect_errno().': Unable to connect to server:'.$strServerName.'<hr />MySql Server is down or connection is not properly configured. Please check the configuration of sql connection!<hr />'.mysqli_connect_error());
		return false;
	} else {
		$oC->set_charset("utf8");
		return $oC;
	}
}


class Sec
{
	public static function IsLoggedIn() {
		return true;
	}

	public static function HasAccess() {
		return true;
	}

}
?>