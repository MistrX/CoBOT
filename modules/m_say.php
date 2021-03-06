<?php
/*
 * @name: Say
 * @desc: Hace que el bot hable
 * @ver: 1.0
 * @author: MRX
 * @id: say
 * @key: quemierdahacesmirandoesto
 *
 */

class quemierdahacesmirandoesto{
	public function __construct(&$core){
		$core->registerCommand("say", "say", "Hace que el bot hable. Sintaxis: say <canal> <mensaje>", 1, CUSTOMPRIV, null, SMARTIRC_TYPE_QUERY|SMARTIRC_TYPE_CHANNEL);
	}
	
  public function say_priv(&$irc, $data, &$core){return $core->authchk($data->from,1,$data->messageex[1]);}
  public function say(&$irc, $data, &$core){
    $ts = $core->jparam( $data->messageex,2);
    $irc->message(SMARTIRC_TYPE_CHANNEL, $data->messageex[1], $ts);
  }
}
