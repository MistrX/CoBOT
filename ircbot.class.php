<?php
/* 
 * Hecho por Ramiro Bou bajo la licencia CC-By-NC-SA
 * http://creativecommons.org/licenses/by-nc-sa/3.0/
 */ 

class IRCBot{
	public $conf;
	public $serv;
	public $nick;
	public $hdf;
	public $htf=array(); 
	public $initscript=array();
	public $connscript=array();
	public $joinscript=array();
	private $authd=array();
	private $plugins;
	private $pcomms=array();
	private $chanlst=array();
	public $disconn=0;
	private $callado;
	public function __construct($config,$callado=false){
		$this->conf=$config;
		
		include("errhandler.php");
		if($callado==false){echo "  - Resolviendo '". $this->conf['irc']['host'] ."'";}
		$this->serv['ip']=gethostbynamel($this->conf['irc']['host']);
		$this->nick=$this->conf['irc']['nick'];
		$this->callado=$callado;
		if($this->serv['ip']==false){die(" [1;31m[ERR][0m\n");}
		if($callado==false){ echo " [1;32m[OK][0m ".$this->serv['ip'][0]."\n";}
		
		$myconn=$this->myiConn();
		$sqlx=$myconn->query("select * from users")or die("  - ERROR: verifique que las tablas mysql esten creadas.");
		$myconn->close();

		ini_set('user_agent', 'CoBOT IRC BOT/'.VER);
		
		//Creamos los directorios importantes..
		if(!is_dir("old")){mkdir("old");} 
		if(!is_dir("log")){mkdir("log");} 
		if(!is_dir("plugins/temp")){mkdir("plugins/temp");} 
		
	}
	public function SendCommand($command){
		$command=$command."\r\n";
		$command=mb_convert_encoding($command,$this->conf['conn']['charset']);
		
		if(isset($this->serv['socket'])){
			fwrite($this->serv['socket'], $command, strlen($command));
		}else{
			$fp=fopen("SCcache","a+");
			fputs($fp,$command); 
			fclose($fp);
		}
	}
	
	public function SendPriv($chan,$msg,$arrow=false,$len=400, $sep=" "){
		$send="";
		if($arrow==true){
			$a=explode($sep, $msg);
			foreach($a as $key=>$val){
				if(strlen($send)+strlen($val)>=$len){
					time_nanosleep(0,250000000); 
					$this->SendCommand("PRIVMSG $chan :".$send." 06".mb_convert_encoding("&#8601;", 'UTF-8',  'HTML-ENTITIES'));
					$send="";
				}else{$send.=$val.$sep;}
			}
			$send=trim($send,$sep);
			
		}else{$send=$msg;}
		$this->SendCommand("PRIVMSG $chan :$send");
	}
	
	private function remChan($chan){
		foreach ($this->chanlst as $key => $this_channel){
			if($this_channel == $chan){unset($this->conf['irc']['channels'][$key]);}
		}
	}
	
	public function myConn(){
		$myconn=mysql_connect($this->conf['db']['host'],$this->conf['db']['user'],$this->conf['db']['pass']);
		mysql_select_db($this->conf['db']['name']);
		return $myconn;
	}
	
	public function myiConn(){
		$dbobj=mysqli_connect($this->conf['db']['host'],$this->conf['db']['user'],$this->conf['db']['pass'],$this->conf['db']['name']);
		return $dbobj;
	}
	
	public function jparam($param,$from){
		$i=$from;
		$ts="";
		while(@isset($param[$i])){
			$ts.=$param[$i]. " ";
			$i++;
		}
		return trim($ts);
	}
	
	public function CheckUpd($verbose=false,$channel=""){
		$s=0;$uparr=array();
		$f=trim(file_get_contents("http://upd.cobot.tk/updchk.php?f=ircbot.class.php"));
		$df=sha1_file("ircbot.class.php");
		if($df!=$f){$s=1;array_push($uparr,array("ircbot.class.php",0));if($verbose){$this->SendPriv($channel,"Actualización pendiente de 03ircbot.class.php");}}

		$f=trim(file_get_contents("http://upd.cobot.tk/updchk.php?f=ircbot.php"));
		$df=sha1_file("ircbot.php");
		if($df!=$f){$s=1;array_push($uparr,array("ircbot.php",0));if($verbose){$this->SendPriv($channel,"Actualización pendiente de 03ircbot.php");}}
		$f=trim(file_get_contents("http://upd.cobot.tk/updchk.php?f=plugins"));
		$fa=explode("\n",$f);
		foreach($fa as $key=>$val){
			$fe=explode("|",$val);
			if(file_exists("plugins/".$fe[0])){
				
				$hash=sha1_file("plugins/".$fe[0]);
				if($fe[1]!=$hash){$s=1;array_push($uparr,array("plugins/".$fe[0],0));if($verbose){$this->SendPriv($channel,"Actualización pendiente de 03plugins/".$fe[0]."");}}
			}else{$s=1;array_push($uparr,array("plugins/".$fe[0],1));if($verbose){$this->SendPriv($channel,"Actualización pendiente de 03plugins/".$fe[0]." 07[Nuevo]");}
}
		}
				
		if($s==1){return 1;}else{return 2;}
	}
	   
	public function Update($verbose=false,$channel=""){
		$k=0;
		$f=trim(file_get_contents("http://upd.cobot.tk/updchk.php?f=ircbot.class.php"));$df=sha1_file("ircbot.class.php");
		$r=0;
		if($f!=$df){$k=1;$r=1;
			if($verbose){$this->SendPriv($channel,"Actualizando 03ircbot.class.php");}
			copy("ircbot.class.php","old/ircbot.class.php");
			$f=file_get_contents("http://upd.cobot.tk/upd.php?f=ircbot.class.php");
			$fp=fopen("ircbot.class.php","w+");
			fputs($fp,$f); 
			fclose($fp); 
		}
		$f=trim(file_get_contents("http://upd.cobot.tk/updchk.php?f=ircbot.php"));$df=sha1_file("ircbot.php");
		if($f!=$df){$k=1;$r=1;
			if($verbose){$this->SendPriv($channel,"Actualizando 03ircbot.php");}
			copy("ircbot.php","old/ircbot.php");
			$f=file_get_contents("http://upd.cobot.tk/upd.php?f=ircbot.php");
			$fp=fopen("ircbot.php","w+");
			fputs($fp,$f); 
			fclose($fp);
		}
		$f=trim(file_get_contents("http://upd.cobot.tk/updchk.php?f=plugins"));
		$fa=explode("\n",$f);
		foreach($fa as $key=>$val){
			$fe=explode("|",$val);
			if(file_exists("plugins/".$fe[0])){  
				$hash=sha1_file("plugins/".$fe[0]);
				if($fe[1]!=$hash){$k=1;
					if($verbose){$this->SendPriv($channel,"Actualizando 03plugins/".$fe[0]."");}

					copy("plugins/".$fe[0],"old/".$fe[0]);

					$f=file_get_contents("http://upd.cobot.tk/upd.php?f=plugins&p=".$fe[0]);
					$fp=fopen("plugins/".$fe[0],"w+");
					fputs($fp,$f); 
					fclose($fp);
					$this->unload($fe[0]);
					$this->load($fe[0]);
				}
			}else{$k=1;
				if($verbose){$this->SendPriv($channel,"Actualizando 03plugins/".$fe[0]." 07[Nuevo]");}
				$f=file_get_contents("http://upd.cobot.tk/upd.php?f=plugins&p=".$fe[0]);
				$fp=fopen("plugins/".$fe[0],"w+");
				fputs($fp,$f); 
				fclose($fp);
			}
		}
		
		if($k==0){return -1;}
		if($r==0){return 0;}
		$this->disconn=$this->conf['conn']['reconnect']+2;
		$this->SendCommand("QUIT :[UPDATE] Aplicando actualizaciones.");
		exec("php restart.php &");
		exit;
	}
	
	public function rehash(){
		include("config.php");
		$this->conf=$conf;
		echo "Recargando el archivo de configuración [1;32m[OK][0m\n";
	}
	
	public function load($plugin){
		copy("plugins/$plugin","plugins/temp/$plugin"); 
		$fp = fopen("plugins/temp/$plugin", "r");
		$pfile="";
		while(!feof($fp)){$pfile.= fgets($fp);}
		if(preg_match("@.*key=\"(.+)\";.*@",$pfile,$m)){$fkey=$m[1];}else{return -1;}
		if(preg_match("@.*name=\"(.+)\";.*@",$pfile,$m)){$mname=trim($m[1]);}else{return -1;}
		$ts=time();
		
		$nclassname=$mname."x".$ts;
		if($this->callado==false){echo "  - Cargando plugin ". $mname. " ";}
		if(@isset($this->plugins[$mname])){if($this->callado==false){ echo "[1;31m[ERR][0m El plugin ya está cargado\n";} return -2;}
		
		@$r=shell_exec("php -l plugins/$plugin");
		if(!preg_match("@.*No syntax errors detected.*@",$r)){
			if($this->callado==false){echo "[1;31m[ERR][0m El plugin parece tener errores de sintáxis!!\n";}
			return 3;
		}
		
		$nmod=preg_replace("/class $fkey{/","class $nclassname{",$pfile);
		fclose($fp);
		
		$fp = fopen("plugins/temp/$plugin", "w+");
		fputs($fp, $nmod);
		fclose($fp);
		
		include("plugins/temp/$plugin");
		if(!class_exists($nclassname)){if($this->callado==false){echo "[1;31m[ERR][0m No encuentro la funcion principal!!\n"; }return -3;}
		$this->plugins[$mname]=new $nclassname($this);
		if($this->callado==false){echo "[1;32m[OK][0m\n";}
		return 2;
	}
	public function unload($plugin){
		$fp = fopen("plugins/temp/$plugin", "r");
		$pfile="";
		if(!$fp){ if($this->callado==false){echo "[1;31m[ERR][0m El plugin no parece estar cargado o no esta en /temp\n";} return -2;}
		while(!feof($fp)){$pfile.= fgets($fp);}
		if(preg_match("@.*name=\"(.+)\";.*@",$pfile,$m)){$name=trim($m[1]);}else{return -1;} //obtenemos el nombre del modulo.
		fclose($fp); 
		//y ahora vamos a des-cargar sus funciones.
		$i=0;
		foreach($this->pcomms as $key=>$val){
			if($this->pcomms[$key]['pgin']==$name){unset($this->pcomms[$key]);}
			$i++;
		}
		foreach($this->hdf as $c=>$v){
			foreach($this->hdf[$c] as $a=>$k){
				if($this->hdf[$c][$a][0]==$name){ unset($this->hdf[$c][$a]); break;}
			}
		}
		if($this->callado==false){echo "  - Des-cargando plugin ". $name. " ";}
		if(!@isset($this->plugins[$name])){if($this->callado==false){ echo "[1;31m[ERR][0m El plugin no parece estar cargado\n";} return -2;}
		unset($this->plugins[$name]); // y descargamos el objeto
	if($this->callado==false){	echo "[1;32m[OK][0m\n";}
	}
	
	public function is_loaded($name){if(isset($this->plugins[$name])){return true;}else{return false;}}
	
	public function get_class($name){
		return $this->plugins[$name];
	}
	
	public function addcmd($oplugin,$command,$plugin,$alias=array()){
		array_push($this->pcomms,array('pgin'=>$plugin, 'comm'=>$command, 'ali'=>0));
		if(!@isset($alias[0])){return 0;}
		$i=0;while(@isset($alias[$i])){
			array_push($this->pcomms,array('pgin'=>$plugin, 'comm'=>$alias[$i], 'ali'=>1, 'alifn' => $command));
			$i++;
		}
	}

	private function addChan($chan){ array_push($this->conf['irc']['channels'], $chan);}
	
	private function helpsys($commands, $channel,$who){
		if(@!isset($commands[1])){
			$clist = "Lista de comandos: help auth"; //agregamos los comandos nativos...
			if($this->checkauth($who,8)){$clist.=" ignore";}
			if($this->checkauth($who,10)){$clist.=" updchk update";}
			//y agregamos todos los comandos cargados via plugin a la lista...
			$this->SendCommand("PRIVMSG ".$channel." :03Co04BOT v".VER." Por Mr. X Comandos empezar con ".$this->conf['irc']['prefix'].". Escriba ".$this->conf['irc']['prefix']."help <comando> para mas información acerca de un comando.");
			
			foreach($this->pcomms as $key => $val){
				if((@isset($val['comm']))&&($val['ali']!=1)){
					if(@isset($this->plugins[$val['pgin']]->help[$val['comm'].'_l'])){
						if($this->checkauth($who,$this->plugins[$val['pgin']]->help[$val['comm'].'_l'])!=1){continue;}
					}
					$clist.=" ".$val['comm'];
				}
			}

			$this->SendPriv($channel,trim($clist),true,380);
		}else{
			switch($commands[1]){
				case "ayuda":
				case "help":$this->SendCommand("PRIVMSG ".$channel." :help: Proporciona ayuda sobre un comando.");break;
				case "auth":$this->SendCommand("PRIVMSG ".$channel." :auth: Permite identificarse y usar ciertos comandos especiales. Sintaxis: auth <usuario> <contraseña>");break;
				case "ignore":$this->SendCommand("PRIVMSG ".$channel." :ignore: Ignora a un usuario. Sintaxis: ignore <mascara (regex)>");break;
				case "updchk":$this->SendPriv($channel,"updchk: Verifica si hay actualizaciones disponibles"); break;
				case "update":$this->SendPriv($channel,"update: Actualiza el núcleo del bot."); break;
			}
			foreach($this->pcomms as $key => $val){
				if((@isset($val['comm']))&&($val['comm']==$commands[1])){
					if($val['ali']==1){
						$this->SendCommand("PRIVMSG $channel :$commands[1] Alias de ".$val['alifn'].". ".$this->plugins[$val['pgin']]->help[$val['alifn']]);
					}else{$this->SendCommand("PRIVMSG $channel :$commands[1]: ".$this->plugins[$val['pgin']]->help[$commands[1]]);}
				}
			}
		}
	}
	
	public function checkauth ($uhost,$lvlr, $ch = "*"){
		$f=file("authinf");$authd="";
		foreach($f as $key=>$val){
			$authd.=$val;
		}
		if($authd==""){return 0;}
		$authd=unserialize($authd);
		
		foreach($authd as $key => $val){
			if($val['hst']==$uhost){
				$pr2=explode("|",$val['rng']);
				$pr4=array();
				foreach($pr2 as $key=>$val){
					$pr3=explode(",",$val);
					array_push($pr4,$pr3);
				}
				$w=0;
				foreach($pr4 as $key=>$val){
					if(($ch==$pr4[$key][1]) || ($pr4[$key][1]=="*")){$w++;}
					if((is_numeric($pr4[$key][0])) && ($pr4[$key][0]>=$lvlr)){$w++;}elseif($pr4[$key][0]==("o".$lvlr)){$w++;}
					if($w==2){return 1;}	
					$w=0;			
				}
			}
		}
		return 0;
	}
	
	public function mask2nick($mask){$nick=explode("!",$mask);return $nick[0];}
	// Ten cuidado!! esto está atado con alambres!
	private function procom($msg, $guy, $channel){
		$param=explode(" ", substr ($msg, 1));
		$nk=explode("!",$guy);
		$g=0;if(preg_match("#{$this->nick}(,|:) (.*)#",$msg,$aq)){
			if(!trim($aq[2])){return 0;}
			$param=explode(" ", $aq[2]);
			$g=1;
		}
		$param[0]=strtolower($param[0]);
		if($msg[0]==$this->conf['irc']['prefix'] || $g==1){
			switch($param[0]){
				case "ayuda":
				case "help":
					$this->helpsys($param,$channel,$guy);return 0;
				case "auth":
					if($channel==$this->nick){
						$myconn=$this->myiConn();
						$sqlx=$myconn->query("select * from users where user='".$param[1]."' AND pass=sha1('".$param[2]."')");
						$i=0;
						while($rowx=$sqlx->fetch_array()){$i++;
							$this->SendCommand("PRIVMSG ".$nk[0]." :Autenticado exitosamente.");
							array_push($this->authd,array('rng'=>$rowx['rng'],'hst'=>$guy));
							unlink("authinf");file_put_contents("authinf",serialize($this->authd));
							return 0;
						}
						$myconn->close();

						if($i==0){$this->SendCommand("PRIVMSG ".$nk[0]." :05ERROR: Usuario/Contraseña incorrectos.");}
					}else{$this->SendCommand("PRIVMSG ".$channel." :05ERROR: Este comando no debe ser utilizado en un canal.");}
					return 0;
				case "ignore":
					if($this->checkauth($guy,8)){
						if(!@$param[1]){break;}
						$myconn=$this->myiConn();
						$sqlx=$myconn->query("INSERT INTO `ignore` (host) VALUES ('$param[1]')");
						$myconn->close();
					}else{	$this->SendCommand("PRIVMSG ".$channel." :04ERROR: No autorizado");}
					return 0;
				case "updchk":
					$r=$this->CheckUpd(true,$channel);
					if($r==2){$this->SendPriv($channel,"No hay actualizaciones disponibles");}
					return 0;
				case "update":
					$r=$this->Update(true,$channel);
					if($r==-1){$this->SendPriv($channel, "03Error: No hay actualizaciones pendientes!");}
					return 0;
			}
			foreach($this->pcomms as $key => $val){
				if((isset($val['comm'])) && (@$val['comm']==$param[0])){
					if($val['ali']!=1){$fn=$val['comm'];}else{$fn=$val['alifn'];}
					$this->plugins[$val['pgin']]->$fn($this,$msg,strtolower($channel),$param,$guy);
				}
			}
		}
	} 
	
	public function IRCConnect(){
		if($this->callado==false){echo "  - Conectando a '". $this->serv['ip'][0].":".$this->conf['irc']['port']."'";}
		$this->serv['socket']=fsockopen(($this->conf['irc']['ssl']?"ssl://":"").$this->serv['ip'][0], $this->conf['irc']['port'], $errno, $errstr, 20);		
		if($this->serv['socket']){
			if($this->callado==false){echo " [1;32m[OK][0m \r\n";}
			socket_set_blocking($this->serv['socket'], false);
			$this->SendCommand("NICK " . $this->nick);
			$this->SendCommand("USER " . $this->nick. " * * :CoBOT, IRC Bot");
			socket_set_timeout($this->serv['socket'], 600);
			foreach($this->initscript as $key => $val){@$this->SendCommand($val);}
			while(!@feof($this->serv['socket'])){
				$this->serv['rbuffer'] = mb_convert_encoding(fgets($this->serv['socket'], 1024),"latin1"); 
				if($this->callado==false){echo $this->serv['rbuffer'];}
				if(file_exists("SCcache")){
					$f=file("SCcache");
					foreach($f as $key => $val){
						usleep(750000); // Odio esperar
						$this->SendCommand(trim($val));
					}
					unlink("SCcache");
				}
				if((file_exists("child-todo")) && ($conf['threads']['use']==false)){
					include("child-todo");
					unlink("child-todo");
				}
				foreach($this->htf as $key => $val){
					$this->plugins[$val[0]]->$val[1]($this,$this->serv['rbuffer']);
				}
				if(empty($this->serv['rbuffer'])){sleep(1);continue;}	
				preg_match('@^(?:\:.*? )?(.*?) @', $this->serv['rbuffer'], $coi);
				@$this->serv['command'] = $coi[1];
				switch($this->serv['command']){
					case "001":
						foreach($this->connscript as $key => $val){time_nanosleep(0,250000000); @$this->SendCommand($val);}
						foreach($this->conf['irc']['channels'] as $key => $val){$this->SendCommand("JOIN ".$val);}
						break;
					case "PING":
						$this->SendCommand('PONG :' . substr($this->serv['rbuffer'], 6)); 
						break;
					case "JOIN":
						if (preg_match('@^:'.preg_quote($this->nick, '@').'!.+ JOIN (.+)$@',$this->serv['rbuffer'], $matches)){
							$this->addChan($matches[1]);
							foreach($this->joinscript as $key => $val){
								time_nanosleep(0,250000000);
								$joinscript=str_replace("&c",substr($matches[1],1,strlen($matches[1])-1),$val);
								@$this->SendCommand($joinscript);
							}
						}
						break;
					case "PART":
						if (preg_match('@^:'.preg_quote($this->nick, '@').'!.+ PART (.+)$@',$this->serv['rbuffer'], $matches)){	$this->remChan($matches[1]);}
						break;
					case "KICK": if (preg_match('@^:.+!.+ KICK (.+)$@',$this->serv['rbuffer'], $matches)){$this->SendCommand("JOIN ".$matches[1]);}	break; //autojoin
					case "PRIVMSG":
						$msg = explode('PRIVMSG ',$this->serv['rbuffer'],2);
						preg_match('/:(.*)/',$msg[0],$matches);
						$who = $matches[1];
						list($channel,$msg) = explode(' :',$msg[1],2);
						$msg=substr($msg,0,strlen($msg)-2);
						/*
						$myconn=$this->myiConn();
						$rsx = $myconn->query("SELECT * FROM `ignore`");
							while(@$rowx=$rsx->fetch_array()){
								if(preg_match("#".$rowx['host']."#",$who,$m)){break;}
							}
						$myconn->close();*/
						$this->procom($msg,$who,$channel);
						break;
				}
				/*
				$myconn=$this->myiConn();
				$rsx = $myconn->query("SELECT * FROM `ignore`");
				while(@$rowx=$rsx->fetch_array()){
					if(preg_match("#.*".$rowx['host'].".*#",$this->serv['rbuffer'],$m)){$k=1;continue;}
				}
				$myconn->close();*/
				if(@isset($this->hdf[$this->serv['command']])){
					foreach($this->hdf[$this->serv['command']] as $key => $val){
						$fn=$val[1];
						$this->plugins[$val[0]]->$fn($this,$this->serv['rbuffer']);
					}
				}
			}
		}else{if($this->callado==false){echo" [1;31m[ERR][0m\r\n";}$this->disconn++;}
	}
	
	
}
