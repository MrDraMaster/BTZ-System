<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
// Regelt die Werkzeugleiste je nach Rolle
add_action('after_setup_theme', 'remove_admin_bar');
function remove_admin_bar() {
	if (!current_user_can('administrator') && !current_user_can('editor') && !current_user_can('author') && !current_user_can('contributor')) {
	  show_admin_bar(false);
	}
	
}

/* Funktionen in diesem .php:
 * 
 * function Check_IP ( $modus = 'standard' )
 * 
 * function Speisekarte_Master () shortcode: [speisekarte]
 * function Saeule_Master () shortcode: [saeule]
 * function Speiseplan_Master () shortcode: [speiseplan]
 * function Kochplan_Master () shortcode: [kochplan]
 * function Kiosk_Master () shortcode: [kiosk]
 * function AEAP_Master () shortcode: [aeap]
 * function Auster_Master () shortcode: [auster]
 * function BT_Master () shortcode: [bt]
 * function BVB_Master () shortcode: [bvb]
 * function Sachbearbeitung_Master () shortcode: [sachbearbeitung]
 * function IU_Kalender_Master () shortcode: [iu_kalender]
 * function IU_Verwaltung_Master ()shortcode: [iu_verwaltung]
 * function Cronjob_Woche_Master () action hook: Cronjob_Woche_Hook
 * function Cronjob_IU_Master () action hook: Cronjob_IU_Hook
 * 
 * function Test_Master ()
 */

/* Bei Änderung der IP-Adressen: strncmp Argumente in Check_IP überprüfen => IP_CHECK
 * 
 * Wenn der Kiosk den angedachten Computer kriegt: 
 * 		KIOSK_COMPUTER suchen
 * 		Bestell_Formular() benötigt Aktualisierung um Nachbesteller zu Küche oder _Kiosk_ zu schicken
 * 
 * Wenn System live geht:
 * 		Code zwischen DEBUG_ANF und DEBUG_END auskommentieren
 * 		SYSTEM_LIVE überprüfen und Kommentar aufheben
 */
 
function Check_IP ( $modus = 'standard' ) { // $modus: 'standard' für normales "nur Mitarbeiter", 'kiosk' für "nur Mitarbeiter oder Kiosk-Computer
	$client_IP = $_SERVER['REMOTE_ADDR'];
	
	// IP_CHECK
	$mitarbeiter_netz = '10.53.'; // konstanter Teil der IP-Addressen des Mitarbeiter-Netzes
	$kiosk_ip = 'keine echte IP'; // KIOSK_COMPUTER: String mit der echten Kiosk-Computer-IP ersetzen
	
	if( !strncmp( $client_IP, $mitarbeiter_netz, strlen($mitarbeiter_netz))) // Test ob im Mitarbeiter-Netz
	{
		$result = true;
	}
	elseif ( $modus == 'kiosk' && !strncmp( $client_IP, $kiosk_ip, strlen($kiosk_ip)) ) // 'kiosk' Modus [für die Kiosk-Seite] => Test ob Kiosk-Computer
	{
		$result = true;
	}
	elseif ( !strncmp($client_IP, '10.249.78.252', 13)) // Schulnetz-Rechner W&V Büro
	{
		$result = true;
	}
	elseif ( !strcmp($client_IP, '10.249.78.6')) // Polenz
	{
		$result = true;
	}
	elseif ( !strncmp($client_IP, '10.249.78.121', 13)) // DEBUG
	{
		$result = true;
	}
	else $result = false;
	
	
	return $result;
}

function Speisekarte_Master () {
	// falls Rechner im Mitarbeiternetz, umschalten auf Säulen-Modus
	if ( Check_IP() )
	{
		$modus = 'saeule';
	} else $modus = 'teilnehmer';
	
	$html  = Bestell_Formular( $modus );
	
	return $html;
}
add_shortcode('speisekarte', 'Speisekarte_Master');

function Saeule_Master () {
	// KIOSK_COMPUTER: Bestell_Formular() benötigt eine Anpassung
	$html  = Bestell_Formular( 'saeule' );
	
	return $html;
}
add_shortcode('saeule', 'Saeule_Master');

function Speiseplan_Master () {

	$html = "<p>".bestellliste_befehle_fct()."</p>";
	
	return $html;
}
add_shortcode('speiseplan', 'Speiseplan_Master');

function Kochplan_Master () {
	// nur anzeigen wenn der Rechner im Mitarbeiternetz ist
	if( Check_IP() )
	{
		$html  = "<p>".Speiseplan_Eingabe()."</p>";
		$html .= "<p>".Speiseplan_Anzeigen()."</p>";
		$html .= "<p>".speiseplan_export_fct()."</p>";
		$html .= "<p>".Bestell_Formular( 'nach' )."</p>";
		$html .= "<p>".Gaeste_Essen()."</p>";
		$html .= "<p>".Kochplan_Aufruf_bestell_summe()."</p>";
	} else $html = "<h3>Kein Zugriff</h3>";
	
	return $html;
}
add_shortcode('kochplan', 'Kochplan_Master');

function Kiosk_Master () {
	// nur anzeigen wenn der Rechner im Mitarbeiternetz ist //KIOSK_COMPUTER: oder wenn es der Kiosk Computer ist
	if( Check_IP('kiosk') )
	{
		$html = "<h3>Mitarbeiter</h3>" . kategorie_liste( "Mitarbeiter" ) .
				"<h3>AEAP</h3>" . kategorie_liste( "AEAP" ) .
				"<h3>Teilnehmer</h3>" . kategorie_liste( "Teilnehmer" ) .
				"<h3>Nachbestellt</h3>" . kategorie_liste( "Nachbestellt" );
		
		// KIOSK_COMPUTER: $html .= nachbestellung();	
	} else $html = "<h3>Kein Zugriff</h3>";
	
	return $html;
}
add_shortcode('kiosk', 'Kiosk_Master');

function AEAP_Master () {
	// nur anzeigen wenn der Rechner im Mitarbeiternetz ist
	if( Check_IP() ) 
	{
		$html = Neue_AEAP_Runde();
		$html .= AEAP_Sammel();
		$html .= Bestell_Formular( 'nach' );
	} else $html = "<h3>Kein Zugriff</h3>";
	
	return $html;
}
add_shortcode('aeap', 'AEAP_Master');

function Auster_Master () {
	// nur anzeigen wenn der Rechner im Mitarbeiternetz ist
	if( Check_IP() )
	{
		$html = Neue_Teilnehmer_Runde('Auster');
		$html .= Teilnehmer_Sammel('Auster');
		$html .= Bestell_Formular( 'nach' );
	} else $html = "<h3>Kein Zugriff</h3>";
	
	return $html;
}
add_shortcode('auster', 'Auster_Master');

function BT_Master () {
	// nur anzeigen wenn der Rechner im Mitarbeiternetz ist
	if( Check_IP() )
	{
		$html = Neue_Teilnehmer_Runde('BT');
		$html .= Teilnehmer_Sammel('BT');
		$html .= Bestell_Formular( 'nach' );
	} else $html = "<h3>Kein Zugriff</h3>";
	
	return $html;
}
add_shortcode('bt', 'BT_Master');

function BVB_Master () {
	// nur anzeigen wenn der Rechner im Mitarbeiternetz ist
	if( Check_IP() )
	{
		$html = Neue_Teilnehmer_Runde('BVB');
		$html .= Teilnehmer_Sammel('BVB');
		$html .= Bestell_Formular( 'nach' );
	} else $html = "<h3>Kein Zugriff</h3>";
	
	return $html;
}
add_shortcode('bvb', 'BVB_Master');

function EA_Master () {
	// nur anzeigen wenn der Rechner im Mitarbeiternetz ist
	if( Check_IP() )
	{
		$html = Neue_Teilnehmer_Runde('EA');
		$html .= Teilnehmer_Sammel('EA');
		$html .= Bestell_Formular( 'nach' );
	} else $html = "<h3>Kein Zugriff</h3>";
	
	return $html;
}
add_shortcode('ea', 'EA_Master');

function Sachbearbeitung_Master () {
	// nur anzeigen wenn der Rechner im Mitarbeiternetz ist
	if( Check_IP() )
	{
		$html = Bestell_Formular( 'nach' );
	} else $html = "<h3>Kein Zugriff</h3>";
	
	return $html;
}
add_shortcode('sachbearbeitung', 'Sachbearbeitung_Master');

function IU_Kalender_Master () {
	// nur anzeigen wenn der Rechner im Mitarbeiternetz ist
	if( Check_IP('kiosk') )
	{
		$html  = IU_Anmeldung_Namenwahl();
		$html .= IU_Anmeldung('saeule');
	} else $html = IU_Anmeldung('teilnehmer');
	
	return $html;
}
add_shortcode('iu_kalender', 'IU_Kalender_Master');

function IU_Verwaltung_Master () {
	// nur anzeigen wenn der Rechner im Mitarbeiternetz ist
	if( Check_IP('kiosk') )
	{
		$html = IU_wer_ist_angemeldet();
		$html .= IU_Namensliste();
	} else $html = "<h3>Kein Zugriff</h3>";
	
	return $html;
}
add_shortcode('iu_verwaltung', 'IU_Verwaltung_Master');

function Cronjob_Woche_Master () {
	cleanup_bestellung( 5 );
	cleanup_speiseplan( 5 );
	
	IU_cleanup( 5 );
	
	/* SYSTEM_LIVE: wer_hat_nicht_bestellt => EMAIL_VERTEILER überprüfen	*/
	wer_hat_nicht_bestellt( 'AEAP' );
	wer_hat_nicht_bestellt( 'Auster' );
	wer_hat_nicht_bestellt( 'Gesamt' );
	/* SYSTEM_LIVE */
	
	// DEBUG_ANF
	wp_mail( 'paul.gerlach@btz-bewegt.de', 'CRON Test', 'Cronjob_Woche_Master wurde ausgeführt.' );
	// DEBUG_END
}
add_action( 'Cronjob_Woche_Hook', 'Cronjob_Woche_Master' );

function Cronjob_IU_Master () {
	$heute_nummer = (date('N')-1);
	
	/* SYSTEM_LIVE: IU_Angemeldet_Email => EMAIL_VERTEILER überprüfen	*/
	IU_Angemeldet_Email($heute_nummer);
	/* SYSTEM_LIVE */
	
	// DEBUG_ANF
	wp_mail( 'paul.gerlach@btz-bewegt.de', 'CRON Test', 'Cronjob_IU_Master wurde für '.$heute_nummer.' [='.Variablen::$Tag_name[$heute_nummer].'] ausgeführt.' );
	// DEBUG_END
}
add_action( 'Cronjob_IU_Hook', 'Cronjob_IU_Master' );

function Test_Master () {
	$client_IP = $_SERVER['REMOTE_ADDR'];
	$html = '<p>IP: '.$client_IP.'</p>';
	$html.= '<p>Zugriff: '.Check_IP().'</p>';
	
	return $html;
}
add_shortcode('test', 'Test_Master');
