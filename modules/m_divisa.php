<?php
/*
 * @name: Divisas
 * @desc: Muestra la cotización de una divisa
 * @ver: 1.0
 * @author: MRX
 * @id: divisa
 * @key: amodkey
 *
 */

class amodkey{
	public $divisas;
	public $divinam;
	private $conf;
	private $lastupd;
	public function __construct(&$core){
        $core->registerCommand("conv", "divisa", "Muestra el precio de una divisa en otra divisa. Sintaxis: conv <Divisa origen> <Divisa destino> <cantidad> (Las divisas deben estar en formato ISO 4217)");
        //$core->registerTimeHandler(1800000, "divisa", "divapiupd"); // Esto es para economizar llamadas a la api..
        $this->conf = $core->conf;
        $this->divapiupd();
        $this->divivar();
	}
	
	public function divapiupd(){
		$d = file_get_contents("http://openexchangerates.org/api/latest.json?app_id={$this->conf['divisa']['openexchangerates_api-key']}");
		$this->divisas = json_decode($d);
		$this->lastupd = time();
	}
	public function conv(&$irc, &$data, &$core){
		if(!isset($data->messageex[3])){$irc->message(SMARTIRC_TYPE_CHANNEL ,"03Error: Faltan parámetros");return 0;}
		if((time() - $this->lastupd) > 1800000){$this->divapiupd();} // Actualizar cada media hora para ahorrar llamadas a la api.
		
		$d1 = strtoupper($data->messageex[1]);
		$d2 = strtoupper($data->messageex[2]);
		$m1 = $this->divisas->rates->$d1;
		$m2 = $this->divisas->rates->$d2;

		if(!$m1){
			$r = "\00304Error\003: No se reconoce la divisa \"\2{$d1}\2\".";
		}elseif(!$m2){
			$r = "\00304Error\003: No se reconoce la divisa \"\2{$d2}\2\".";
		}else{
			$r = "Convirtiendo del \2".$this->divinam[$d1]."\2 al \2".$this->divinam[$d2]."\2: \2". ($m2 / $m1);
		}
		
		$irc->message(SMARTIRC_TYPE_CHANNEL, $data->channel, $r);
	}
	
	private function divivar(){
		$this->divinam = array(
		"AED" => "Dirham de los Emiratos Árabes Unidos",
"AFN" => "Afgani afgano",
"ALL" => "Lek albanés",
"AMD" => "Dram armenio",
"ANG" => "Florín antillano neerlandés",
"AOA" => "angoleño",
"ARS" => "Peso argentino",
"AUD" => "Dólar australiano",
"AWG" => "Florín arubeño",
"AZM" => "Manat azerbaiyano",
"BAM" => "Marco convertible de Bosnia-Herzegovina ",
"BBD" => "Dólar de Barbados",
"BDT" => "Taka de Bangladés",
"BGN" => "Lev búlgaro",
"BHD" => "Dinar bahreiní",
"BIF" => "Franco burundés",
"BMD" => "Dólar de Bermuda",
"BND" => "Dólar de Brunéi",
"BOB" => "Boliviano",
"BOV" => "Boliviano con mantenimiento de valor respecto al dólar estadounidense",
"BRL" => "Real brasileño",
"BSD" => "Dólar bahameño",
"BTN" => "Ngultrum de Bután",
"BWP" => "Pula de Botsuana",
"BYR" => "Rublo bielorruso",
"BZD" => "Dólar de Belice",
"CAD" => "Dólar canadiense",
"CDF" => "Franco congoleño, o congolés",
"CHF" => "Franco suizo",
"CLF" => "Unidades de fomento chilenas (código de fondos)",
"CLP" => "Peso chileno",
"CNY" => "Yuan chino",
"COP" => "Peso colombiano",
"COU" => "Unidad de valor real colombiana (añadida al COP)",
"CRC" => "Colón costarricense",
"CUP" => "Peso cubano",
"CUC" => "Peso cubano convertible",
"CVE" => "Escudo caboverdiano",
"CZK" => "Koruna checa",
"DJF" => "Franco yibutiano",
"DKK" => "Corona danesa",
"DOP" => "Peso dominicano",
"DZD" => "Dinar algerino",
"EGP" => "Libra egipcia",
"ERN" => "Nakfa eritreo",
"ETB" => "Birr etíope",
"EUR" => "Euro",
"FJD" => "Dólar fiyiano",
"FKP" => "Libra malvinense",
"GBP" => "Libra esterlina (libra de Gran Bretaña)",
"GEL" => "Lari georgiano",
"GHS" => "Cedi ghanés",
"GIP" => "Libra de Gibraltar",
"GMD" => "Dalasi gambiano",
"GNF" => "Franco guineano",
"GTQ" => "Quetzal guatemalteco",
"GYD" => "Dólar guyanés",
"HKD" => "Dólar de Hong Kong",
"HNL" => "Lempira hondureño",
"HRK" => "Kuna croata",
"HTG" => "Gourde haitiano",
"HUF" => "Forint húngaro ",
"IDR" => "Rupiah indonesia",
"ILS" => "Nuevo shéquel israelí",
"INR" => "Rupia india",
"IQD" => "Dinar iraquí",
"IRR" => "Rial iraní",
"ISK" => "Króna islandesa",
"JMD" => "Dólar jamaicano",
"JOD" => "Dinar jordano",
"JPY" => "Yen japonés",
"KES" => "Chelín keniata",
"KGS" => "Som kirguís (de Kirguistán)",
"KHR" => "Riel camboyano",
"KMF" => "Franco comoriano (de Comoras)",
"KPW" => "Won norcoreano",
"KRW" => "Won surcoreano",
"KWD" => "Dinar kuwaití",
"KYD" => "Dólar caimano (de Islas Caimán)",
"KZT" => "Tenge kazajo",
"LAK" => "Kip lao",
"LBP" => "Libra libanesa",
"LKR" => "Rupia de Sri Lanka",
"LRD" => "Dólar liberiano",
"LSL" => "Loti lesotense",
"LTL" => "Litas lituano",
"LVL" => "Lat letón",
"LYD" => "Dinar libio",
"MAD" => "Dirham marroquí",
"MDL" => "Leu moldavo",
"MGA" => "Ariary malgache",
"MKD" => "Denar macedonio",
"MMK" => "Kyat birmano",
"MNT" => "Tughrik mongol",
"MOP" => "Pataca de Macao",
"MRO" => "Ouguiya mauritana",
"MUR" => "Rupia mauricia",
"MVR" => "Rufiyaa maldiva",
"MWK" => "Kwacha malauí",
"MXN" => "Peso mexicano",
"MXV" => "Unidad de Inversión (UDI) mexicana (código de fondos)",
"MYR" => "Ringgit malayo",
"MZN" => "Metical mozambiqueño",
"NAD" => "Dólar namibio",
"NGN" => "Naira nigeriana",
"NIO" => "Córdoba nicaragüense",
"NOK" => "Corona noruega",
"NPR" => "Rupia nepalesa",
"NZD" => "Dólar neozelandés",
"OMR" => "Rial omaní",
"PAB" => "Balboa panameña",
"PEN" => "Nuevo sol peruano",
"PGK" => "Kina de Papúa Nueva Guinea",
"PHP" => "Peso filipino",
"PKR" => "Rupia pakistaní",
"PLN" => "zloty polaco",
"PYG" => "Guaraní paraguayo",
"QAR" => "Rial qatarí",
"RON" => "Leu rumano",
"RUB" => "Rublo ruso",
"RWF" => "Franco ruandés",
"SAR" => "Riyal saudí",
"SBD" => "Dólar de las Islas Salomón",
"SCR" => "Rupia de Seychelles",
"SDG" => "Dinar sudanés",
"SEK" => "Corona sueca",
"SGD" => "Dólar de Singapur",
"SHP" => "Libra de Santa Helena",
"SLL" => "Leone de Sierra Leona",
"SOS" => "Chelín somalí",
"SRD" => "Dólar surinamés",
"STD" => "Dobra de Santo Tomé y Príncipe",
"SYP" => "Libra siria",
"SZL" => "Lilangeni suazi",
"THB" => "Baht tailandés",
"TJS" => "Somoni tayik (de Tayikistán)",
"TMT" => "Manat turcomano",
"TND" => "Dinar tunecino",
"TOP" => "Pa'anga tongano",
"TRY" => "Lira turca",
"TTD" => "Dólar de Trinidad y Tobago",
"TWD" => "Dólar taiwanés",
"TZS" => "Chelín tanzano",
"UAH" => "Grivna ucraniana",
"UGX" => "Chelín ugandés",
"USD" => "Dólar estadounidense",
"USN" => "Dólar estadounidense (Siguiente día) (código de fondos)",
"USS" => "Dólar estadounidense (Mismo día) (código de fondos)",
"UYU" => "Peso uruguayo",
"UZS" => "Som uzbeko",
"VEF" => "Bolívar fuerte venezolano",
"VND" => "Dong vietnamita",
"VUV" => "Vatu vanuatense",
"WST" => "Tala samoana",
"XAF" => "Franco CFA de África Central",
"XAG" => "Onza de plata",
"XAU" => "Onza de oro",
"XBA" => "European Composite Unit (EURCO) (unidad del mercado de bonos)",
"XBB" => "European Monetary Unit (E.M.U.-6) (unidad del mercado de bonos)",
"XBC" => "European Unit of Account 9 (E.U.A.-9) (unidad del mercado de bonos)",
"XBD" => "European Unit of Account 17 (E.U.A.-17) (unidad del mercado de bonos)",
"XCD" => "Dólar del Caribe Oriental",
"XDR" => "Derechos Especiales de Giro (Fondo Monetario Internacional)",
"XFO" => "Franco de oro (Special settlement currency)",
"XFU" => "Franco Unión Internacional de Ferrocarriles (Special settlement currency)",
"XOF" => "Franco CFA de África Occidental",
"XPD" => "Onza de paladio",
"XPF" => "Franco CFP",
"XPT" => "Onza de platino",
"XTS" => "Reservado para pruebas",
"XXX" => "Sin divisa",
"YER" => "Rial yemení (de Yemen)",
"ZAR" => "Rand sudafricano",
"ZMK" => "Kwacha zambiano",
"ZWL" => "Dólar zimbabuense"

		);
	}

}
