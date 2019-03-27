<?php
/**
 * Functions.php
 *
 * @package  Theme_Customisations
 * @author   WooThemes
 * @since    1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
/* Funktionen in diesem .php:
 * 
 * function cleanup_bestellung ( $Tiefe = 0 )
 * function cleanup_speiseplan ( $Tiefe = 0 )
 * function wer_hat_nicht_bestellt ( $Kategorie )
 */

// löscht alte Bestelllisten, startend bei der vorletzten Woche und $Tiefe weitere Wochen in die Vergangenheit
function cleanup_bestellung ( $Tiefe = 0 ) { // default: 0, nur die vorletzte Woche
	global $wpdb;
	
	$delete = array();
	
	for ( $i = 0; $i <= $Tiefe; $i++)
	{
		$delete[] = date('W', strtotime("-".($i+2)." week"));
	}
	
	foreach ( $delete as $key )
	{
		$table = $wpdb->prefix.'bestellliste_KW_'.$key;
		$wpdb->query('DROP TABLE '.$table);
	}
}

// löscht alte Speisepläne, startend bei der vorletzten Woche und $Tiefe weitere Wochen in die Vergangenheit
function cleanup_speiseplan ( $Tiefe = 0 ) { // default: 0, nur die vorletzte Woche
	global $wpdb;
	
	$table = Tabelle_Erstellen();
	
	$delete = array();
	
	for ( $i = 0; $i <= $Tiefe; $i++)
	{
		$delete[] = date('W', strtotime("-".($i+2)." week"));
	}
	
	foreach ( $delete as $key )
	{
		$wpdb->delete( $table, array( 'KW' => $key ) );
	}
}

// Email an Verteiler schicken mit Liste aller Teilnehmer in $Kategorie die keinen Eintrag in der Bestellliste der kommenden Woche haben
function wer_hat_nicht_bestellt ( $Kategorie ) {
	global $wpdb;
	$KW = date('W', strtotime('+1 week')); // check immer für die kommende Woche
	if ( Kueche_offen($KW) ) // Emails nur verschicken, wenn die Küche auch auf ist
	{
		$table = Tabelle_Erstellen('bestellung', $KW);
		
		// Soll-Liste abfragen
		$liste_alle = LDAP_Abfrage($Kategorie);
		array_multisort($liste_alle[0], SORT_ASC, SORT_NATURAL, $liste_alle[1]);
		
		// Ist-Liste abfragen
		if ( 'Auster' == $Kategorie || 'Gesamt' == $Kategorie )
		{
			$SQL = "SELECT Name FROM $table WHERE Kategorie ='Teilnehmer' OR Kategorie='Nachbestellt' ORDER BY Name";
		}
		else $SQL = "SELECT Name FROM $table WHERE Kategorie ='$Kategorie' OR Kategorie='Nachbestellt' ORDER BY Name";
		$liste_bestellt = $wpdb->get_col($SQL);
		
		// Differenz ermitteln
		$liste_fehlt = array_diff($liste_alle[0], $liste_bestellt);
		
		// Erinnerungsemail vorbereiten
		switch ($Kategorie) // EMAIL_VERTEILER
		{
			case 'AEAP':
				$mail = 'essen-aeap-btz-jena@faw.de';
				break;
			case 'Auster':
				$mail = 'essen-auster-btz-jena@faw.de';
				break;
			case 'Gesamt':
				$mail = 'essen-extern-btz-jena@faw.de';
				break;
		}
		
		$betreff = 'Fehlende Essensbestellungen für die kommende Woche [KW '.$KW.']';
		
		$header[] = 'MIME-Version: 1.0';
		$header[] = 'Content-type: text/html;';
		$header[] = 'From: Essensbestellung <Essensbestellung@btz-bewegt.de>';
		
		$text = '
			<html>
				<body>
					<p>Diese Teilnehmer haben noch nicht für die kommende Woche bestellt:</p>
					<table>
						<tbody>
						';
			foreach ( $liste_fehlt as $name )
			{
				$text .= '<tr><td>'.$name.'</td></tr>';
			}
			$text .= '
						</tbody>
					</table>	
				</body>
			</html>
			';
			
//		$flag = wp_mail( $mail, $betreff, $text, $header );
		wp_mail( $mail, $betreff, $text, $header );
	}
}
