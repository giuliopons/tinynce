<?php
/*
	class to manage install process
*/

class install {

	var $gestore;


	function __construct () {
		global $root;
		$this->gestore = $_SERVER["PHP_SELF"];
	}

	/*
		mysql login data form
	*/
	function getDettaglioMysql() {

		global $root, $conn;

			/*
				modify
			*/
			$action = "modificaStep2";
			$html = loadTemplateAndParse("template/dettaglio.html");

			// build form
			$objform = new form();

			$host = new testo("host",WEBDOMAIN,40,30);
			$host->obbligatorio=1;
			$host->label="'{Host}'";
			$objform->addControllo($host);

			$dbname = new testo("dbname",DEFDBNAME,40,30);
			$dbname->obbligatorio=1;
			$dbname->label="'{Database}'";
			$objform->addControllo($dbname);

			$username = new testo("username",DEFUSERNAME,40,30);
			$username->obbligatorio=1;
			$username->label="'{Username}'";
			$objform->addControllo($username);

			$cr = new cryptor();
			$password = new testo("password",DEFDBPWD,40,30);
			$password->obbligatorio=0; // on developer enviroment it's possible to have an empty password
			$password->label="'{Password}'";
			$objform->addControllo($password);

			$lang = new optionlist("lang","en", array(
					"en"=> "English",
					"it"=> "Italiano",
				));
			$lang->obbligatorio=0;
			$lang->label="'{Language}'";
			$objform->addControllo($lang);

			$theme = new optionlist("theme","timy", array(
					"timy-theme"=> "Timy default theme",					
				));
			$theme->obbligatorio=0;
			$theme->label="'{Theme}'";
			$objform->addControllo($theme);

			$op = new hidden("op",$action);

			if( Connessione() ) {
				if(table_exists(DB_PREFIX."frw_vars")) {
					// UPDATE
					$html = str_replace("##ISUPDATE##", "", $html);
					$html = str_replace("##ISINSTALL##", " style='display:none' ", $html);

				} else {
					// INSTALL (ho i dati)
					$html = str_replace("##ISUPDATE##", "", $html);
					$html = str_replace("##ISINSTALL##", " style='display:none' ", $html);

				}
			
			} else {
				// INSTALL (missing mysql data)
				$html = str_replace("##ISUPDATE##", " style='display:none' ", $html);
				$html = str_replace("##ISINSTALL##", "", $html);

			}


			$html = str_replace("##STARTFORM##", $objform->startform(), $html);
			$html = str_replace("##op##", $op->gettag(), $html);
			$html = str_replace("##username##", $username->gettag(), $html);
			$html = str_replace("##password##", $password->gettag(), $html);
			$html = str_replace("##dbname##", $dbname->gettag(), $html);
			$html = str_replace("##host##", $host->gettag(), $html);
			//$html = str_replace("##email##", $email->gettag(), $html);
			$html = str_replace("##lang##", $lang->gettag(), $html);
			$html = str_replace("##theme##", $theme->gettag(), $html);
			//$html = str_replace("##envato##", $envato->gettag(), $html);
			$html = str_replace("##gestore##", $this->gestore, $html);
			$html = str_replace("##ENDFORM##", $objform->endform(), $html);



		return $html;
	}

	function update($arDati) {
		global $root,$conn;

		$html = "";


		if( !Connessione()) {
			// INSTALL file settings
			$full_path_to_file = $root."pons-settings.php";


			//
			// save data in pons.settings.php
			if(file_exists($full_path_to_file) && isset($arDati['host'])) {

				if(!is_writable($full_path_to_file)) @chmod($full_path_to_file, 0755); 
				if(!is_writable($full_path_to_file)) die("<pre>" . $full_path_to_file. " not writeable.</pre>");

				$contents0 = file_get_contents( $full_path_to_file );
				$contents = preg_replace( "/define\(\"WEBDOMAIN\",\"([^\"]*)\"\);/", "define(\"WEBDOMAIN\",\"".$arDati['host']."\");", $contents0 );
				$contents = preg_replace( "/define\(\"DEFDBNAME\",\"([^\"]*)\"\);/", "define(\"DEFDBNAME\",\"".$arDati['dbname']."\");", $contents );
				$contents = preg_replace( "/define\(\"DEFUSERNAME\",\"([^\"]*)\"\);/", "define(\"DEFUSERNAME\",\"".$arDati['username']."\");", $contents );
				$contents = preg_replace( "/define\(\"DEFDBPWD\",\"([^\"]*)\"\);/", "define(\"DEFDBPWD\",\"".$arDati['password']."\");", $contents );
				$contents = preg_replace( "/define\(\"LANGUAGEFILE\",\"([^\"]*)\"\);/", "define(\"LANGUAGEFILE\",\"".$arDati['lang'].".lang.txt\");", $contents );

				$contents = preg_replace( "/define\(\"DOMINIODEFAULT\",\"([^\"]*)\"\);/", "define(\"DOMINIODEFAULT\",\"".$arDati['theme']."\");", $contents );

				file_put_contents( $full_path_to_file, $contents );

				if($contents0 == $contents) die("<pre>" ."Cant' find config strings in pons-settings.php"."</pre>");

				echo "<script>document.location.href='".$root."src/componenti/".INSTALLER."/index.php?op=modificaStep2&rnd=".rand(1,1111)."';</script>";
				die; // refresh

			} else {
				if (isset($arDati['host'])) {
					die("file ".$full_path_to_file." not found.");
				} else {
					// go to db update
					echo "<pre>Reloading...</pre><script>setTimeout(function(){document.location.href='".$root."src/componenti/".INSTALLER."/index.php?op=modificaStep2&rnd3=".rand(1,1111)."';},1000);</script>";
					die;
				}
			}
		} else {

			if(!table_exists(DB_PREFIX."frw_vars")) {
				// INSTALL (data ok)
				$this->sql1();
			} 
			
			if(table_exists(DB_PREFIX."frw_vars")) {
				$this->sql423();
				$this->sql424();
				$this->sql426();
				$this->sql427();
				$this->sql428();
				$this->sql430();
				$this->sql433();
                $this->sql434();
				$this->sql435();
				$this->sql436();
			}


			if(!file_exists($root."data/dbimg/media") && file_exists($root."data/dbimg/demofiles")) {
				// move folder demo contents
				renamebetter($root."data/dbimg/demofiles",$root."data/dbimg/media");
			}

			
			if(!file_exists($root."data/dbimg/media") && file_exists($root."data/dbimg/7banner")) {
				// fix old version
				renamebetter($root."data/dbimg/7banner",$root."data/dbimg/media");
			}

			// remove lock file
			$lockupdate = $root. str_replace(basename(LOGS_FILENAME),"lock.txt", LOGS_FILENAME);
			if(file_exists($lockupdate)) {
				unlinkbetter($lockupdate);
			}

			$pons_install = $root. "pons-settings-install.php";
			if(file_exists($pons_install)) {
				unlinkbetter($pons_install);
			}


			echo "<script>document.location.href='".$root."src/logout.php?rnd=".rand(1,111111)."';</script>";
			die;


		}




		return $html;
	}



	function sql1 () {
		global $conn;

		$a[] ="SET sql_mode = '';";

		$a[] = "SET NAMES 'utf8';";
		
		$a[] = "CREATE TABLE `".DB_PREFIX."frw_componenti` (
		  `id` int(11) NOT NULL,
		  `nome` varchar(100) NOT NULL DEFAULT '',
		  `descrizione` varchar(255) DEFAULT NULL,
		  `urlcomponente` varchar(255) NOT NULL DEFAULT '',
		  `label` varchar(100) NOT NULL DEFAULT '',
		  `urliconamenu` varchar(255) DEFAULT NULL,
		  `target` varchar(10) NOT NULL,
		  `fl_translate` tinyint(1) UNSIGNED NOT NULL DEFAULT '1'
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Components info';";


		$a[] = "INSERT INTO `".DB_PREFIX."frw_componenti` (`id`, `nome`, `descrizione`, `urlcomponente`, `label`, `urliconamenu`, `target`, `fl_translate`) VALUES
		(1, 'DEBUGGER', 'Debug tool.', 'componenti/debugger/test.php', 'debugger', 'componenti/debugger/images/debugger_ico.gif', '', 1),
		(5, 'GESTIONEUTENTI', 'User manager', 'componenti/gestioneutenti/index.php', 'Users', 'componenti/gestioneutenti/images/gestioneutenti_ico.gif', '', 1),
		(12, 'FRWCOMPONENTI', 'per gestire l\'installazione/rimozione di funzionalita e componenti', 'componenti/frwcomponenti/index.php', 'Menu items', 'componenti/frwcomponenti/images/ico.gif', '', 1),
		(14, 'FRWMODULI', 'Per la creazione di nuovi moduli', 'componenti/frwmoduli/index.php', 'Menu editor', 'componenti/frwmoduli/images/ico.gif', '', 1),
		(15, 'FRWPROFILI', 'Gestione dei profili degli utenti del sistema', 'componenti/frwprofili/index.php', 'User profiles', 'icon-menu', '', 1),
		(58, 'MIOPROFILO', 'Gestione cambio password e altri miei dati', 'componenti/gestioneutenti/mioprofilo.php', 'Edit my profile', 'componenti/gestioneutenti/images/gestioneutenti_ico.gif', '', 1),
		(61, 'FRWVARS', 'Settaggi', 'componenti/frwvars/index.php', 'Settings more', 'icone/cog.png', '', 1),
		(194, 'CONSTANTSSETTINGS', 'Vars and settings', 'componenti/frwconstants/index.php', 'Settings', NULL, '', 1),
		(212, 'LISTS', NULL, 'componenti/tslists/index.php', 'To do lists', 'icon-menu', '', 1),
		(213, 'THETASKS', NULL, 'componenti/tstasks/index.php', 'My tasks', 'icon-menu', '', 1),
		(155, 'TSREPARTI', 'Gestione reparti', 'componenti/tsreparti/index.php', 'Departments', 'icon-menu', '', 1),
		(156, 'TSTIPIORE', 'Gestione tipologie ore', 'componenti/tstipiore/index.php', 'Hour type', 'icon-menu', '', 1),
		(150, 'TSCLIENTI', 'Gestione clienti', 'componenti/tsclienti/index.php', 'Clients', 'icon-menu', '', 1),
		(151, 'TSJOB', 'Abilitazione gestione job', 'componenti/tsjob/index.php', 'Jobs', 'icon-menu', '', 1),
		(152, 'TSREPORT', 'Reportistica timesheet', 'componenti/tsreport/index.php', 'Reports', 'icon-menu', '', 1),
		(153, 'TSSTATS', 'classificona', 'componenti/tsstats/index.php', 'Classifica! (non usato)', 'componenti/tsstats/images/sport_soccer.png', '', 1),
		(154, 'TSCHEHOFATTO', 'Che ho fatto', 'componenti/tschehofatto/index.php', 'My log time', 'icon-menu', '', 1),
		(172, 'TSORE2', 'Inserimento timesheets', 'componenti/tsore2/index.php', 'Log time', 'icon-menu', '', 1),
		(173, 'TSOREMANCANTI', 'Visualizza le ore mancanti', 'componenti/tsoremancanti2/index.php', 'Missing log time', 'icon-menu', '', 1);";
		
		$a[] = "CREATE TABLE `".DB_PREFIX."frw_com_mod` (
		  `idcomponente` int(11) NOT NULL DEFAULT '0',
		  `idmodulo` int(11) NOT NULL DEFAULT '0',
		  `posizione` tinyint(3) UNSIGNED NOT NULL DEFAULT '0'
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
		
		
		$a[] = "INSERT INTO `".DB_PREFIX."frw_com_mod` (`idcomponente`, `idmodulo`, `posizione`) VALUES
		(5, 1, 6),
		(12, 1, 11),
		(14, 1, 10),
		(15, 1, 7),
		(58, 1, 0),
		(61, 1, 0),
		(150, 10, 6),
		(151, 10, 5),
		(152, 10, 40),
		(154, 10, 76),
		(155, 10, 10),
		(156, 10, 15),
		(172, 10, 0),
		(173, 10, 99),
		(194, 1, 1),
		(212, 11, 0),
		(213, 11, 0);";

		$a[] = "CREATE TABLE `".DB_PREFIX."frw_extrauserdata` (
		  `cd_user` int(10) UNSIGNED NOT NULL DEFAULT '0',
		  `de_email` varchar(200) NOT NULL,
		  `dt_datacreazione` date NOT NULL DEFAULT '1970-01-01',
		  `cd_reparto` int(11) NOT NULL DEFAULT '0',
		  `nu_oresettimanali` int(11) NOT NULL DEFAULT '0',
		  `de_temp` varchar(200) NOT NULL,
		  `de_lang` varchar(4) NOT NULL DEFAULT 'en',
		  `de_sigla` varchar(10) NOT NULL DEFAULT '',
		  `nu_costo` smallint(5) NOT NULL
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

		$TODAY = date('Y-m-d');
		
		$a[] = "INSERT INTO `".DB_PREFIX."frw_extrauserdata` (`cd_user`, `de_email`, `dt_datacreazione`, `cd_reparto`, `nu_oresettimanali`, `de_temp`, `de_lang`, `de_sigla`, `nu_costo`) VALUES
		(45, '', '".$TODAY."', 24, 40, '', 'en', 'MAR', 10),
		(36, '', '".$TODAY."', 24, 40, '', 'en', 'JON', 19);";
		
		$a[] = "CREATE TABLE `".DB_PREFIX."frw_funzionalita` (
		  `id` int(11) NOT NULL,
		  `idcomponente` int(11) NOT NULL DEFAULT '0',
		  `nome` varchar(100) NOT NULL DEFAULT '',
		  `descrizione` varchar(255) DEFAULT NULL,
		  `label` varchar(100) NOT NULL DEFAULT ''
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Functionalities';";
		
		$a[] = "INSERT INTO `".DB_PREFIX."frw_funzionalita` (`id`, `idcomponente`, `nome`, `descrizione`, `label`) VALUES
		(1, 1, 'DEBUGGER', 'Debugger', 'Debugger tool for support'),
		(8, 5, 'READ', 'UTENTI possibilita di vedere gli utenti', 'lettura'),
		(9, 5, 'WRITE', 'UTENTI possibilita di modificare/aggiungere/togliere utenti', 'scrittura'),
		(20, 12, 'FRWCOMPONENTI', 'gestione componenti', 'gestione componenti'),
		(24, 14, 'FRWMODULI', 'Per abilitare la possibilita di gestire moduli', 'Gestione moduli'),
		(25, 15, 'FRWPROFILI', 'Per abilitare il componente che crea i profili', 'Per abilitare il componente che crea i profili'),
		(78, 58, 'MIOPROFILO', 'MIOPROFILO', 'MIOPROFILO'),
		(81, 61, 'FRWVARS', 'FRWVARS', 'FRWVARS'),
		(100, 155, 'TSREPARTI', 'TSREPARTI', 'TSREPARTI'),
		(218, 194, 'CONSTANTSSETTINGS', 'CONSTANTSSETTINGS', 'CONSTANTSSETTINGS'),
		(212, 212, 'LISTS', 'LISTS', 'LISTS'),
		(213, 213, 'THETASKS', 'THETASKS', 'THETASKS'),
		(101, 156, 'TSTIPIORE', 'TSTIPIORE', 'TSTIPIORE'),
		(102, 150, 'TSCLIENTI', 'Abilitazione', 'TSCLIENTI'),
		(103, 151, 'TSJOB', 'Abilitazione', 'TSJOB'),
		(104, 152, 'TSREPORT', 'Abilitazione', 'TSREPORT'),
		(105, 153, 'TSSTATS', 'abilitazione', 'TSSTATS'),
		(106, 154, 'TSCHEHOFATTO', 'Abilitazione del componente', 'TSCHEHOFATTO'),
		(107, 172, 'TSORE2', 'TSORE2', 'TSORE2'),
		(108, 173, 'TSOREMANCANTI', 'TSOREMANCANTI', 'TSOREMANCANTI');";
		
		$a[] = "CREATE TABLE `".DB_PREFIX."frw_moduli` (
		  `id` int(11) NOT NULL,
		  `nome` varchar(100) NOT NULL DEFAULT '',
		  `label` varchar(100) NOT NULL DEFAULT '',
		  `visibile` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
		  `posizione` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
		  `fl_translate` tinyint(1) UNSIGNED NOT NULL DEFAULT '1'
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
		
		$a[] = "INSERT INTO `".DB_PREFIX."frw_moduli` (`id`, `nome`, `label`, `visibile`, `posizione`, `fl_translate`) VALUES
		(1, 'CONFIG', 'Config', 1, 0, 1),
		(11, 'TASKS', 'Tasks', 1, 10, 1),
		(10, 'TIMY', 'Timesheet', 1, 3, 1);";
		
		$a[] = "CREATE TABLE `".DB_PREFIX."frw_profili` (
		  `id_profilo` int(3) UNSIGNED NOT NULL DEFAULT '0',
		  `de_label` varchar(20) NOT NULL DEFAULT '0',
		  `de_descrizione` varchar(255) DEFAULT NULL,
		  `chiedita` varchar(100) DEFAULT NULL
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='User profiles';";

		$a[] = "INSERT INTO `".DB_PREFIX."frw_profili` (`id_profilo`, `de_label`, `de_descrizione`, `chiedita`) VALUES
		(20, 'administrator', 'administrator', ',20,5,15,10,'),
		(999999, 'superadmin', 'super', ',10,20,5,999999,15,16,4,'),
		(10, 'user', 'user', '');";
		
		$a[] = "CREATE TABLE `".DB_PREFIX."frw_profili_funzionalita` (
		  `cd_profilo` int(10) UNSIGNED NOT NULL DEFAULT '999999',
		  `cd_modulo` int(10) UNSIGNED NOT NULL DEFAULT '0',
		  `cd_funzionalita` int(10) UNSIGNED NOT NULL DEFAULT '0'
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Cross table between profiles and functionalities';";
		

		$a[] = "INSERT INTO `".DB_PREFIX."frw_profili_funzionalita` (`cd_profilo`, `cd_modulo`, `cd_funzionalita`) VALUES
		(10, 1, 78),
		(10, 10, 106),
		(10, 10, 107),
		(10, 11, 212),
		(10, 11, 213),
		(20, 1, 1),
		(20, 1, 8),
		(20, 1, 9),
		(20, 1, 20),
		(20, 1, 24),
		(20, 1, 78),
		(20, 1, 218),
		(20, 10, 100),
		(20, 10, 101),
		(20, 10, 102),
		(20, 10, 103),
		(20, 10, 104),
		(20, 10, 105),
		(20, 10, 107),
		(20, 10, 108),
		(20, 11, 212),
		(20, 11, 213),
		(999999, 1, 1),
		(999999, 1, 8),
		(999999, 1, 9),
		(999999, 1, 20),
		(999999, 1, 24),
		(999999, 1, 25),
		(999999, 1, 78),
		(999999, 1, 81),
		(999999, 1, 218),
		(999999, 10, 100),
		(999999, 10, 101),
		(999999, 10, 102),
		(999999, 10, 103),
		(999999, 10, 104),
		(999999, 10, 105),
		(999999, 10, 107),
		(999999, 10, 108),
		(999999, 11, 212),
		(999999, 11, 213);";
		
		$a[] = "CREATE TABLE `".DB_PREFIX."frw_utenti` (
		  `id` int(11) NOT NULL,
		  `username` varchar(20) NOT NULL DEFAULT '',
		  `password` varchar(60) NOT NULL DEFAULT '',
		  `nome` varchar(100) NOT NULL DEFAULT '',
		  `cognome` varchar(100) NOT NULL DEFAULT '',
		  `fl_attivo` int(1) UNSIGNED NOT NULL DEFAULT '1',
		  `cd_profilo` int(10) UNSIGNED NOT NULL DEFAULT '1'
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Users';";
		
		$a[] = "INSERT INTO `".DB_PREFIX."frw_utenti` (`id`, `username`, `password`, `nome`, `cognome`, `fl_attivo`, `cd_profilo`) VALUES
		(36, 'admin', 'BTIKMQEwXGIHYw==', 'John', 'Smith', 1, 20),
		(45, 'user', 'BSYKJgE4XHk=', 'Dean', 'Martin', 1, 10);";
		
		$a[] = "CREATE TABLE `".DB_PREFIX."frw_ute_fun` (
		  `idutente` int(11) NOT NULL DEFAULT '0',
		  `idfunzionalita` int(11) NOT NULL DEFAULT '0',
		  `idmodulo` int(10) UNSIGNED NOT NULL DEFAULT '0',
		  `fl_automatic` int(1) NOT NULL DEFAULT '1'
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='cross table between users and functionalities';";
		
		$a[] = "INSERT INTO `".DB_PREFIX."frw_ute_fun` (`idutente`, `idfunzionalita`, `idmodulo`, `fl_automatic`) VALUES
		(45, 78, 1, 1),
		(45, 106, 10, 1),
		(36, 213, 11, 1),
		(36, 212, 11, 1),
		(36, 108, 10, 1),
		(36, 107, 10, 1),
		(36, 105, 10, 1),
		(36, 104, 10, 1),
		(36, 103, 10, 1),
		(36, 102, 10, 1),
		(36, 101, 10, 1),
		(36, 100, 10, 1),
		(36, 218, 1, 1),
		(36, 81, 1, 1),
		(36, 78, 1, 1),
		(36, 25, 1, 1),
		(36, 24, 1, 1),
		(36, 20, 1, 1),
		(45, 107, 10, 1),
		(45, 212, 11, 1),
		(45, 213, 11, 1),
		(36, 9, 1, 1),
		(36, 8, 1, 1),
		(36, 1, 1, 1);";


		$a[] = "CREATE TABLE `".DB_PREFIX."frw_vars` (
		  `id_var` int(10) UNSIGNED NOT NULL,
		  `de_nome` varchar(50) NOT NULL,
		  `de_value` text NOT NULL
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
		
		$a[] = "INSERT INTO `".DB_PREFIX."frw_vars` (`id_var`, `de_nome`, `de_value`) VALUES
		(1, 'COLLATIONCONNECTIONQUERY', 'SET NAMES \'utf8\';'),
		(21, 'CREA_FUNZIONI_AUTOMATICAMENTE', '1'),
		(31, 'CONST_MONEY', '€'),
		(32, 'CONST_SERVER_EMAIL_ADDRESS', 'noreply@yourserver.com'),
		(33, 'CONST_DATEFORMAT', 'dd/mm/yyyy'),
		(115, 'CONST_MANUAL_PAYMENTS', 'ON'),
		(35, 'CONST_GEOIP_CSV', 'DB1LITE'),
		(36, 'CONST_GEOIP_TOKEN', ''),
		(37, 'CONST_GEOIP_LIMIT_COUNTRY', ''),
		(38, 'CONST_SERVER_NAME', 'Timy'),
		(39, 'CONST_PAYMENTS', 'OFF'),
		(40, 'CONST_PAYPAL_CLIENTID', ''),
		(41, 'CONST_PAYPAL_SECRET', ''),
		(42, 'CONST_PAYPAL_SERVER', 'https://api.sandbox.paypal.com'),
		(43, 'CONST_MIN_PRICE', '20'),
		(116, 'CONST_MANUAL_PAYMENTS_INFO', ''),
		(47, 'CONST_SMTP_SERVER', 'smtps.aruba.it'),
		(48, 'CONST_SMTP_AUTH', '1'),
		(49, 'CONST_SMTP_USERNAME', 'yourmail@server.com'),
		(50, 'CONST_SMTP_PASSWORD', 'yourmailpwd'),
		(51, 'CONST_SMTP_ENCRYPTION', 'SSL'),
		(52, 'CONST_SMTP_PORT', '465'),
		(53, 'CONST_MAXSIZE_UPLOAD', '1000'),
		(54, 'CONST_MONEY_CODE', 'USD'),
		(113, 'GEO_IP_STEP', ''),
		(107, 'CONST_COINBASE_API_KEY', ''),
		(114, 'CONST_CHECK_VERSION', 'ON'),
		(117, 'CURRENT_VERSION', '421'),
		(118, 'CONST_NUMBERFORMAT', '1000,00');";
	
		$a[] = "CREATE TABLE `".DB_PREFIX."ts_clienti` (
		  `id_cliente` int(11) UNSIGNED NOT NULL,
		  `de_nomecliente` varchar(50) NOT NULL
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
		
		$a[] = "INSERT INTO `".DB_PREFIX."ts_clienti` (`id_cliente`, `de_nomecliente`) VALUES
		(262, 'Internal Clients'),
		(263, 'Envato');";
		
		$a[] = "CREATE TABLE `".DB_PREFIX."ts_job` (
		  `id_job` int(11) UNSIGNED NOT NULL,
		  `de_codice` varchar(10) NOT NULL,
		  `de_nomejob` varchar(50) NOT NULL,
		  `dt_inizio` date NOT NULL DEFAULT '1970-01-01',
		  `dt_fine` date NOT NULL DEFAULT '1970-01-01',
		  `cd_cliente` mediumint(8) UNSIGNED NOT NULL DEFAULT '0',
		  `fl_attivo` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
		  `nu_budget` int(11) NOT NULL,
		  `de_note` text NOT NULL,
		  `de_color` varchar(20) NOT NULL DEFAULT '',
		  `nu_budget_hours` int(11) NOT NULL
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
		
		$a[] = "INSERT INTO `".DB_PREFIX."ts_job` (`id_job`, `de_codice`, `de_nomejob`, `dt_inizio`, `dt_fine`, `cd_cliente`, `fl_attivo`, `nu_budget`, `de_note`, `de_color`, `nu_budget_hours`) VALUES
			(859, 'TIM', 'Timy development', '".$TODAY."', '2030-09-01', 263, 1, 0, '', 'orange', 0)";


		$a[] = "CREATE TABLE `".DB_PREFIX."ts_lists` (
		  `id_list` int(11) NOT NULL,
		  `de_namelist` varchar(150) CHARACTER SET utf8 NOT NULL,
		  `dt_saved` datetime DEFAULT NULL,
		  `fl_private` tinyint(1) NOT NULL DEFAULT '0',
		  `cd_user` int(11) NOT NULL COMMENT 'target user',
		  `cd_owner` int(11) NOT NULL COMMENT 'creator',
		  `cd_reparto` int(11) DEFAULT NULL COMMENT 'target department'
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;";


		$a[] = "INSERT INTO `".DB_PREFIX."ts_lists` (`id_list`, `de_namelist`, `dt_saved`, `fl_private`, `cd_user`, `cd_owner`, `cd_reparto`) VALUES
			(1, 'Personal', '".$TODAY." 15:00:00', 1, 36, 36, NULL);";
		
		$a[] = "CREATE TABLE `".DB_PREFIX."ts_ore` (
		  `id_ora` int(11) UNSIGNED NOT NULL,
		  `cd_utente` int(11) UNSIGNED NOT NULL DEFAULT '0',
		  `de_nota` text NOT NULL,
		  `cd_job` int(11) UNSIGNED NOT NULL DEFAULT '0',
		  `nu_ore` decimal(3,1) UNSIGNED NOT NULL DEFAULT '0.0',
		  `dt_giorno` date NOT NULL DEFAULT '1970-01-01',
		  `cd_tipoora` int(11) NOT NULL DEFAULT '0' COMMENT 'types of hours',
		  `cd_reparto_ora` int(11) NOT NULL DEFAULT '0'
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

		$a[] = "INSERT INTO `".DB_PREFIX."ts_ore` (`id_ora`, `cd_utente`, `de_nota`, `cd_job`, `nu_ore`, `dt_giorno`, `cd_tipoora`,`cd_reparto_ora`) VALUES
			(1, 36, 'Working progress', 859, '3.0', '".$TODAY."', 273, 24),
			(2, 36, 'Gantt chart', 859, '5.0', '".$TODAY."', 276, 24),
			(3, 45, 'Added task management tool', 859, '7.0', '".$TODAY."', 269, 24),
			(4, 45, 'Bug fix for installation process', 859, '1.0', '".$TODAY."', 275, 24);";



		$a[] = "CREATE TABLE `".DB_PREFIX."ts_reparti` (
		  `id_reparto` int(11) UNSIGNED NOT NULL,
		  `de_nomereparto` varchar(50) NOT NULL,
		  `fl_default` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'default for reports'
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
		
		$a[] = "INSERT INTO `".DB_PREFIX."ts_reparti` (`id_reparto`, `de_nomereparto`, `fl_default`) VALUES
		(24, 'Dev', 1);";
		
		$a[] = "CREATE TABLE `".DB_PREFIX."ts_tasks` (
		  `id_task` int(11) NOT NULL,
		  `cd_list` int(11) NOT NULL,
		  `cd_author` int(11) NOT NULL,
		  `de_taskname` varchar(255) NOT NULL,
		  `en_status` enum('to do','done','in progress') NOT NULL DEFAULT 'to do',
		  `dt_opened` datetime NOT NULL,
		  `dt_closed` datetime NOT NULL,
		  `de_color` varchar(20) NOT NULL,
		  `fl_archived` tinyint(1) NOT NULL DEFAULT '0',
		  `de_link` varchar(255) NOT NULL DEFAULT '',
		  `dt_expiration` datetime DEFAULT NULL
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;";


		$a[] = "INSERT INTO `".DB_PREFIX."ts_tasks` (`id_task`, `cd_list`, `cd_author`, `de_taskname`, `en_status`, `dt_opened`, `dt_closed`, `de_color`, `fl_archived`, `de_link`, `dt_expiration`) VALUES
				(1, 1, 36, 'Say hello to developers :)', 'to do', '".$TODAY." 15:00:00', '1970-01-01 00:00:00', '', 0, '', NULL);";


		$a[] = "CREATE TABLE `".DB_PREFIX."ts_tbc_tipiore_reparti` (
		  `cd_tipoora` int(11) NOT NULL,
		  `cd_reparto` int(11) NOT NULL
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
		

		$a[] = "INSERT INTO `".DB_PREFIX."ts_tbc_tipiore_reparti` (`cd_tipoora`, `cd_reparto`) VALUES
		(1, 24),
		(268, 24),
		(269, 24),
		(270, 24),
		(273, 24),
		(274, 24),
		(275, 24),
		(276, 24);";
		
		$a[] = "CREATE TABLE `".DB_PREFIX."ts_tipiora` (
		  `id_tipoora` int(11) UNSIGNED NOT NULL,
		  `de_tipoora` varchar(50) NOT NULL
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
		
		$a[] = "INSERT INTO `".DB_PREFIX."ts_tipiora` (`id_tipoora`, `de_tipoora`) VALUES
		(1, 'Uncategorized'),
		(273, 'Meetings'),
		(268, 'Training'),
		(269, 'Development'),
		(274, 'Other'),
		(275, 'Customer care'),
		(276, 'Project management');";
		
		$a[] = "ALTER TABLE `".DB_PREFIX."frw_componenti`  ADD PRIMARY KEY (`id`);";
		
		$a[] = "ALTER TABLE `".DB_PREFIX."frw_com_mod`
		  ADD PRIMARY KEY (`idcomponente`,`idmodulo`,`posizione`),
		  ADD KEY `idcomponente` (`idcomponente`,`idmodulo`),
		  ADD KEY `posizione` (`posizione`);";
		
		$a[] = "ALTER TABLE `".DB_PREFIX."frw_extrauserdata` ADD PRIMARY KEY (`cd_user`);";
		
		$a[] = "ALTER TABLE `".DB_PREFIX."frw_funzionalita`
		  ADD PRIMARY KEY (`id`),
		  ADD UNIQUE KEY `nomeunico` (`nome`),
		  ADD KEY `idcomponente` (`idcomponente`);";

		$a[] = "ALTER TABLE `".DB_PREFIX."frw_moduli` ADD PRIMARY KEY (`id`);";
		
		$a[] = "ALTER TABLE `".DB_PREFIX."frw_profili` ADD PRIMARY KEY (`id_profilo`);";
		
		$a[] = "ALTER TABLE `".DB_PREFIX."frw_profili_funzionalita`
		  ADD PRIMARY KEY (`cd_profilo`,`cd_modulo`,`cd_funzionalita`),
		  ADD KEY `cd_profilo` (`cd_profilo`);";
		
		$a[] = "ALTER TABLE `".DB_PREFIX."frw_utenti`
		  ADD PRIMARY KEY (`id`),
		  ADD UNIQUE KEY `username` (`username`);";
		
		$a[] = "ALTER TABLE `".DB_PREFIX."frw_ute_fun`
		  ADD UNIQUE KEY `UNICO` (`idfunzionalita`,`idmodulo`,`idutente`),
		  ADD KEY `idutente` (`idutente`,`idfunzionalita`);";
		
		$a[] = "ALTER TABLE `".DB_PREFIX."frw_vars`
		  ADD PRIMARY KEY (`id_var`),
		  ADD UNIQUE KEY `label_unica` (`de_nome`);";
		
		$a[] = "ALTER TABLE `".DB_PREFIX."ts_clienti`
		  ADD PRIMARY KEY (`id_cliente`),
		  ADD UNIQUE KEY `de_nomecliente` (`de_nomecliente`);";
		
		$a[] = "ALTER TABLE `".DB_PREFIX."ts_job`
		  ADD PRIMARY KEY (`id_job`);";
		
		$a[] = "ALTER TABLE `".DB_PREFIX."ts_lists` ADD PRIMARY KEY (`id_list`);";
		
		$a[] = "ALTER TABLE `".DB_PREFIX."ts_ore`
		  ADD PRIMARY KEY (`id_ora`),
		  ADD KEY `job` (`cd_job`),
		  ADD KEY `cd_tipoora` (`cd_tipoora`);";
		
		$a[] = "ALTER TABLE `".DB_PREFIX."ts_reparti`
		  ADD PRIMARY KEY (`id_reparto`);";
		
		$a[] = "ALTER TABLE `".DB_PREFIX."ts_tasks`
		  ADD PRIMARY KEY (`id_task`);";
		
		$a[] = "ALTER TABLE `".DB_PREFIX."ts_tbc_tipiore_reparti`
		  ADD PRIMARY KEY (`cd_tipoora`,`cd_reparto`);";
		
		$a[] = "ALTER TABLE `".DB_PREFIX."ts_tipiora`
		  ADD PRIMARY KEY (`id_tipoora`);";
		
		$a[] = "ALTER TABLE `".DB_PREFIX."frw_componenti`
		  MODIFY `id` int(11) NOT NULL auto_increment, AUTO_INCREMENT=1004;";
		
		$a[] = 	"ALTER TABLE `".DB_PREFIX."frw_funzionalita`
		  MODIFY `id` int(11) NOT NULL auto_increment, AUTO_INCREMENT=1004;";
		
		$a[] = "ALTER TABLE `".DB_PREFIX."frw_moduli`
		  MODIFY `id` int(11) NOT NULL auto_increment, AUTO_INCREMENT=1003;";
		
		$a[] = "ALTER TABLE `".DB_PREFIX."frw_utenti`
		  MODIFY `id` int(11) NOT NULL auto_increment, AUTO_INCREMENT=46;";
		
		$a[] = "ALTER TABLE `".DB_PREFIX."frw_vars`
		  MODIFY `id_var` int(10) UNSIGNED NOT NULL auto_increment, AUTO_INCREMENT=119;";
		
		$a[] = "ALTER TABLE `".DB_PREFIX."ts_clienti`
		  MODIFY `id_cliente` int(11) UNSIGNED NOT NULL auto_increment, AUTO_INCREMENT=278;";
		
		$a[] = "ALTER TABLE `".DB_PREFIX."ts_job`
		  MODIFY `id_job` int(11) UNSIGNED NOT NULL auto_increment, AUTO_INCREMENT=882;";
		
		$a[] = "ALTER TABLE `".DB_PREFIX."ts_lists`
		  MODIFY `id_list` int(11) NOT NULL auto_increment, AUTO_INCREMENT=19;";
		
		$a[] = "ALTER TABLE `".DB_PREFIX."ts_ore`
		  MODIFY `id_ora` int(11) UNSIGNED NOT NULL auto_increment, AUTO_INCREMENT=45156;";
		
		$a[] = "ALTER TABLE `".DB_PREFIX."ts_reparti`
		  MODIFY `id_reparto` int(11) UNSIGNED NOT NULL auto_increment, AUTO_INCREMENT=27;";
		
		$a[] = "ALTER TABLE `".DB_PREFIX."ts_tasks`
		  MODIFY `id_task` int(11) NOT NULL auto_increment, AUTO_INCREMENT=130;";
		
		$a[] = "ALTER TABLE `".DB_PREFIX."ts_tipiora`
		  MODIFY `id_tipoora` int(11) UNSIGNED NOT NULL auto_increment, AUTO_INCREMENT=277;
		";
		

		foreach ($a as $s) {

			$conn->query($s) or die("Error executing query: <pre><code>$s</code></pre> Remove all the tables and try again.<br><br>Error:<br><br><b>".$conn->error."</b>");

		}



	}


	/**
	 * Updates for version 4.23
	 * 
	 * @return void
	 */
	function sql423 () {
		global $conn;

		if(!table_exists(DB_PREFIX."ts_default_job_tipi")) {

			$a[] = "CREATE TABLE `".DB_PREFIX."ts_default_job_tipi` (
				`cd_job` int(11) NOT NULL,
				`cd_type` int(11) NOT NULL,
				`cd_user` int(11) NOT NULL
			) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
			
			$a[] = "ALTER TABLE `".DB_PREFIX."ts_default_job_tipi`
				ADD PRIMARY KEY (`cd_job`,`cd_type`,`cd_user`);";
		}

		$a[] = "INSERT IGNORE INTO ".DB_PREFIX."frw_vars (de_nome,de_value) VALUES ( 'CONST_OPENAI_API_KEY','')";

		if(!table_exists(DB_PREFIX."ts_chat")) {

			$a[]= "CREATE TABLE `".DB_PREFIX."ts_chat` (
				`id_chat_msg` int(11) NOT NULL,
				`cd_user` int(11) NOT NULL,
				`fl_bot` tinyint(1) NOT NULL COMMENT '0 = user; 1 = bot',
				`de_msg` text CHARACTER SET utf8 NOT NULL,
				`dt_saved` datetime NOT NULL
			) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

			$a[] = "ALTER TABLE `".DB_PREFIX."ts_chat` ADD PRIMARY KEY (`id_chat_msg`);";
		  
		  	$a[] = "ALTER TABLE `".DB_PREFIX."ts_chat` MODIFY `id_chat_msg` int(11) NOT NULL auto_increment, AUTO_INCREMENT=61;";

		}

		foreach ($a as $s) {

			$conn->query($s) or die("Error executing query: <pre><code>$s</code></pre> Remove all the tables and try again.<br><br>Error:<br><br><b>".$conn->error."</b>");

		}
	}

	function sql424() {        
		global $conn;
		$ar = array();
		$q = execute_scalar( "SELECT count(1) FROM INFORMATION_SCHEMA.columns WHERE TABLE_NAME = '".DB_PREFIX."frw_extrauserdata' AND TABLE_SCHEMA='".DEFDBNAME."' AND COLUMN_NAME='cd_default_component'");
		if($q == 0) {
            $ar[] = "ALTER TABLE `".DB_PREFIX."frw_extrauserdata` ADD `cd_default_component` INT NOT NULL DEFAULT '0' AFTER `de_lang`;";
        }
		$ar[] = "INSERT IGNORE INTO `".DB_PREFIX."frw_vars` (`de_nome`, `de_value`) VALUES  ('CONST_NOTIFY_NEW_USERS_TO_ADMIN', 'OFF');";
		
		foreach ($ar as $s) { 
			$conn->query($s) or die("Error executing query: <pre><code>$s</code></pre>.<br><br>Error:<br><br><b>".$conn->error."</b>");
		}
	}


	function sql426() {        
		global $conn;
		$ar = array();
		$ar[] = "INSERT IGNORE INTO `".DB_PREFIX."frw_vars` (`de_nome`, `de_value`) VALUES  ('CONST_STRONG_PASSWORD', 'OFF');";
		$ar[] = "UPDATE `".DB_PREFIX."frw_componenti` SET `urlcomponente`= 'componenti/gestioneutentitimy/index.php' WHERE `urlcomponente`= 'componenti/gestioneutenti/index.php'"; 

		if(!table_exists(DB_PREFIX."ts_users_annual_cost")) {

			$ar[] = "ALTER TABLE `".DB_PREFIX."frw_extrauserdata` CHANGE `nu_costo` `nu_costo` SMALLINT(5) NOT NULL COMMENT 'cost per hour';";

			$ar[] = "CREATE TABLE `".DB_PREFIX."ts_users_annual_cost` (
					`cd_user` int(11) NOT NULL,
					`nu_anno` smallint(6) NOT NULL,
					`nu_cost` smallint(6) NOT NULL
					) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

			$ar[]	= 	"ALTER TABLE `".DB_PREFIX."ts_users_annual_cost`  ADD PRIMARY KEY (`cd_user`,`nu_anno`);";

		}

		if(!table_exists(DB_PREFIX."ts_worked_reports")) {
			$ar[] = "CREATE TABLE `".DB_PREFIX."ts_worked_reports` ( `id_report` INT NOT NULL AUTO_INCREMENT , `de_link` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , `dt_saved` DATETIME NOT NULL , PRIMARY KEY (`id_report`)) ENGINE = MyISAM;";
		}

        foreach ($ar as $s) { 
			$conn->query($s) or die("Error executing query: <pre><code>$s</code></pre>.<br><br>Error:<br><br><b>".$conn->error."</b>");
		}
	}

	

	function sql427() {        
		global $conn;
		$ar = array();
		$q = execute_scalar( "SELECT count(1) FROM INFORMATION_SCHEMA.columns WHERE TABLE_NAME = '".DB_PREFIX."ts_tasks' AND TABLE_SCHEMA='".DEFDBNAME."' AND COLUMN_NAME='dt_priority'");
		if($q == 0) {

			$ar[] = "ALTER TABLE `ts_tasks` ADD `dt_priority` DATETIME NULL AFTER `dt_expiration`;";
		}


		// support for menu editable items that are after 1000
		$ai = execute_scalar("SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '".DEFDBNAME."' AND TABLE_NAME = '".DB_PREFIX."frw_moduli';");
		if($ai<1000) {
			$ar[] = "ALTER TABLE ".DB_PREFIX."frw_moduli AUTO_INCREMENT = 1001;";
			$ar[] = "ALTER TABLE ".DB_PREFIX."frw_componenti AUTO_INCREMENT = 1001;";
			$ar[] = "ALTER TABLE ".DB_PREFIX."frw_funzionalita AUTO_INCREMENT = 1001;";
		}

        foreach ($ar as $s) { 
			$conn->query($s) or die("Error executing query: <pre><code>$s</code></pre>.<br><br>Error:<br><br><b>".$conn->error."</b>");
		}
	}

	function sql428() {        
		global $conn;
		$ar = array();
		if(!table_exists(DB_PREFIX."ts_planning")) {

			$ar[] = "CREATE TABLE `".DB_PREFIX."ts_planning` (
				`id_planning` int(10) NOT NULL,
				`dt_date` date NOT NULL DEFAULT '1970-01-01',
				`nu_hours` smallint(5) UNSIGNED NOT NULL,
				`cd_user` int(11) NOT NULL,
				`cd_job` int(11) NOT NULL,
				`cd_todo` int(11) NOT NULL,
				`cd_creator` int(11) NOT NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";
	  
			$ar[] = "CREATE TABLE `".DB_PREFIX."ts_todos` (
				`id_todo` int(11) NOT NULL,
				`cd_job` int(11) NOT NULL,
				`cd_creator` int(11) NOT NULL,
				`de_label` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
				`dt_saved` datetime NOT NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";
	  

			$ar[] = "ALTER TABLE `".DB_PREFIX."ts_planning` ADD PRIMARY KEY (`id_planning`),ADD KEY `cd_user` (`cd_user`),ADD KEY `cd_job` (`cd_job`);";
			$ar[] = "ALTER TABLE `".DB_PREFIX."ts_todos` ADD PRIMARY KEY (`id_todo`),ADD KEY `cd_job` (`cd_job`);";
			$ar[] = "ALTER TABLE `".DB_PREFIX."ts_planning` MODIFY `id_planning` int(10) NOT NULL AUTO_INCREMENT;";
			$ar[] = "ALTER TABLE `".DB_PREFIX."ts_todos` MODIFY `id_todo` int(11) NOT NULL AUTO_INCREMENT;";
			$ar[] = "ALTER TABLE `".DB_PREFIX."ts_todos` ADD `fl_status` TINYINT(1) NOT NULL DEFAULT '1' AFTER `dt_saved`;";
			$ar[] = "ALTER TABLE `".DB_PREFIX."ts_todos` ADD `dt_week_or_ever` DATE NULL DEFAULT NULL COMMENT 'NULL = forever, date = just that week' AFTER `fl_status`;";
			$ar[] = "ALTER TABLE `".DB_PREFIX."ts_planning` ADD UNIQUE(`dt_date`, `cd_user`, `cd_todo`);";			
			$ar[] = "ALTER TABLE `".DB_PREFIX."ts_planning` ADD `de_comment` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `cd_creator`;";
		}

		foreach ($ar as $s) { 
			$conn->query($s) or die("Error executing query: <pre><code>$s</code></pre>.<br><br>Error:<br><br><b>".$conn->error."</b>");
		}
	}


	function sql430() {   
		global $conn;
		$ar = array();
		$ar[] = "INSERT IGNORE INTO ".DB_PREFIX."frw_vars (id_var,de_nome,de_value) VALUES ( NULL,'CONST_SHOW_USER_INFO','OFF');";
		$ar[] = "INSERT IGNORE INTO ".DB_PREFIX."frw_vars (id_var,de_nome,de_value) VALUES ( NULL,'CONST_LOG_BLOCKED','OFF');";
		$ar[] = "UPDATE `".DB_PREFIX."frw_vars` set de_value='430' WHERE de_nome='CURRENT_VERSION';";
		$ar[] = "INSERT IGNORE INTO ".DB_PREFIX."frw_vars (de_nome,de_value) VALUES ('DBINTEGRITYDATA','')";
		$ar[] = "INSERT IGNORE INTO ".DB_PREFIX."frw_vars (de_nome,de_value) VALUES ('CONST_MAXSIZE_UPLOAD','1000')";

		$q = execute_scalar( "SELECT count(1) FROM INFORMATION_SCHEMA.columns WHERE TABLE_NAME = '".DB_PREFIX."ts_tasks' AND TABLE_SCHEMA='".DEFDBNAME."' AND COLUMN_NAME='de_text'");
		if($q == 0) {
			$ar[] = "ALTER TABLE `".DB_PREFIX."ts_tasks` ADD `de_text` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;";
		}
		foreach ($ar as $s) { 
			$conn->query($s) or die("Error executing query: <pre><code>$s</code></pre>.<br><br>Error:<br><br><b>".$conn->error."</b>");
		}
	}

	function sql433() {   
		global $conn;
		$ar = array();
		$ar[] = "INSERT IGNORE INTO ".DB_PREFIX."frw_vars (id_var,de_nome,de_value) VALUES ( NULL,'CONST_SHOW_USER_INFO','OFF');";
		$ar[] = "INSERT IGNORE INTO ".DB_PREFIX."frw_vars (id_var,de_nome,de_value) VALUES ( NULL,'CONST_LOG_BLOCKED','OFF');";
		$ar[] = "UPDATE `".DB_PREFIX."frw_vars` set de_value='4.3.3b' WHERE de_nome='CURRENT_VERSION';";
		$ar[] = "INSERT IGNORE INTO ".DB_PREFIX."frw_vars (de_nome,de_value) VALUES ('DBINTEGRITYDATA','')";
		$ar[] = "INSERT IGNORE INTO ".DB_PREFIX."frw_vars (de_nome,de_value) VALUES ('CONST_MAXSIZE_UPLOAD','1000')";

		$ar[] = "ALTER TABLE `".DB_PREFIX."frw_extrauserdata` CHANGE `nu_costo` `nu_costo` SMALLINT(5) NOT NULL DEFAULT '10' COMMENT 'cost per hour';";
		$ar[] = "ALTER TABLE `".DB_PREFIX."frw_extrauserdata` CHANGE `de_temp` `de_temp` VARCHAR(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';";
		$ar[] = "ALTER TABLE `".DB_PREFIX."frw_extrauserdata` CHANGE `de_email` `de_email` VARCHAR(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';";

		$q = execute_scalar( "SELECT count(1) FROM INFORMATION_SCHEMA.columns WHERE TABLE_NAME = '".DB_PREFIX."ts_tasks' AND TABLE_SCHEMA='".DEFDBNAME."' AND COLUMN_NAME='de_text'");
		if($q == 0) {
			$ar[] = "ALTER TABLE `".DB_PREFIX."ts_tasks` ADD `de_text` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;";
		}
		foreach ($ar as $s) { 
			$conn->query($s) or die("Error executing query: <pre><code>$s</code></pre>.<br><br>Error:<br><br><b>".$conn->error."</b>");
		}
	}

	private function sql434() {   
		global $conn;
		$ar = array();

		$ar[] = "INSERT IGNORE INTO `".DB_PREFIX."frw_vars` (`de_nome`, `de_value`) VALUES ('CURRENT_VERSION', '4.3.4');";
		$ar[] = "UPDATE `".DB_PREFIX."frw_vars` set de_value='4.3.4' WHERE de_nome='CURRENT_VERSION';";
		$ar[] = "ALTER TABLE `".DB_PREFIX."frw_extrauserdata` CHANGE `de_temp` `de_temp` VARCHAR(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'used for reset password code';";

		foreach ($ar as $s) { 
			$conn->query($s) or die("Error executing query: <pre><code>$s</code></pre>.<br><br>Error:<br><br><b>".$conn->error."</b>");
		}
	}

	private function sql435() {   
		global $conn;
		$ar = array();

		$ar[] = "INSERT IGNORE INTO `".DB_PREFIX."frw_vars` (`de_nome`, `de_value`) VALUES ('CURRENT_VERSION', '4.3.5');";
		$ar[] = "UPDATE `".DB_PREFIX."frw_vars` set de_value='4.3.5' WHERE de_nome='CURRENT_VERSION';";

		$q = execute_scalar( "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
		 	WHERE TABLE_SCHEMA='".DEFDBNAME."' AND TABLE_NAME = '".DB_PREFIX."frw_utenti' AND COLUMN_NAME = 'dt_last_access'");

		if($q == 0) {
			$ar[] = "ALTER TABLE `".DB_PREFIX."frw_utenti` ADD `dt_last_access` DATETIME NULL AFTER `cd_profilo`;";

            $ar[] = "ALTER TABLE `".DB_PREFIX."ts_lists` DROP `cd_user`, DROP `cd_reparto`;";

			$ar[] = "CREATE TABLE `".DB_PREFIX."ts_tbc_lists_users` (
					`cd_list` int(11) NOT NULL,
					`cd_user` int(11) NOT NULL
					) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
			$ar[] = "ALTER TABLE `".DB_PREFIX."ts_tbc_lists_users`
					ADD PRIMARY KEY (`cd_list`,`cd_user`);";
			$ar[] = "CREATE TABLE `".DB_PREFIX."ts_tbc_lists_reparti` (
					`cd_list` int(11) NOT NULL,
					`cd_reparto` int(11) NOT NULL
					) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
			$ar[] = "ALTER TABLE `".DB_PREFIX."ts_tbc_lists_reparti`
					ADD PRIMARY KEY (`cd_list`,`cd_reparto`);";

		}
		
		foreach ($ar as $s) { 
			$conn->query($s) or die("Error executing query: <pre><code>$s</code></pre>.<br><br>Error:<br><br><b>".$conn->error."</b>");
		}
	}


	private function sql436() {   
		global $conn;
		$ar = array();

		$ar[] = "INSERT IGNORE INTO `".DB_PREFIX."frw_vars` (`de_nome`, `de_value`) VALUES ('CURRENT_VERSION', '4.3.6');";
		$ar[] = "UPDATE `".DB_PREFIX."frw_vars` set de_value='4.3.6' WHERE de_nome='CURRENT_VERSION';";
		$ar[] = "INSERT IGNORE INTO `".DB_PREFIX."frw_vars` (`id_var`, `de_nome`, `de_value`) VALUES (NULL, 'CONST_SMTP_VERIFY_PEER', 'ON');";
		$q = execute_scalar("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
			WHERE TABLE_SCHEMA='".DEFDBNAME."' AND TABLE_NAME = '".DB_PREFIX."frw_extrauserdata' AND COLUMN_NAME = 'fl_darkmode'");

		if($q == 0) {
			$ar[] = "ALTER TABLE `".DB_PREFIX."frw_extrauserdata` ADD `fl_darkmode` TINYINT(1) NOT NULL DEFAULT '0';";


			$ar[] = "CREATE TABLE `".DB_PREFIX."ts_notes` (
			`id_note` int(11) NOT NULL,
			`de_title` varchar(255) NOT NULL DEFAULT '',
			`de_note` text,
			`dt_saved` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			`cd_author` int(11) NOT NULL DEFAULT '0',
			`fl_archived` tinyint(1) NOT NULL DEFAULT '0',
			`de_color` varchar(20) NOT NULL DEFAULT ''
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";


			$ar[] = "ALTER TABLE `".DB_PREFIX."ts_notes`
			ADD PRIMARY KEY (`id_note`),
			ADD KEY `idx_cd_author` (`cd_author`),
			ADD KEY `idx_dt_saved` (`dt_saved`);";

			
			$ar[] = "CREATE TABLE `".DB_PREFIX."ts_fornitori` (
			`id_fornitore` int(11) UNSIGNED NOT NULL,
			`de_nomefornitore` varchar(50) NOT NULL
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";

			$ar[] = "ALTER TABLE `".DB_PREFIX."ts_fornitori`
			ADD PRIMARY KEY (`id_fornitore`),
			ADD UNIQUE KEY `de_nomefornitore` (`de_nomefornitore`);";

			$ar[] = "ALTER TABLE `".DB_PREFIX."ts_fornitori`
			MODIFY `id_fornitore` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;";


		}
		foreach ($ar as $s) { 
			$conn->query($s) or die("Error executing query: <pre><code>$s</code></pre>.<br><br>Error:<br><br><b>".$conn->error."</b>");
		}
	}


}
