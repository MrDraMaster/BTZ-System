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
/**
 * functions.php
 * Add PHP snippets here
 */


/* Funktionen in diesem .php:
 * 
 * function kategorie_listen ()
 * function AEAP_liste ()
 * function Teilnehmer_liste ()
 * function LDAP_Abfrage ( $Gruppe )
 */

// Erstellt Namens- und Emailliste für die Kategorien via LDAP_Abfrage und sortiert sie
function kategorie_listen () {
	// lädt die Besteller Namen und Emails über LDAP
	$aeap_liste = LDAP_Abfrage("AEAP");
	$auster_liste = LDAP_Abfrage("Auster");
	$gesamt_liste = LDAP_Abfrage("Gesamt");
	$mitarbeiter_liste = LDAP_Abfrage("Mitarbeiter");

	// packt Auster und Gesamt Teilnehmer in ein Array
	$teilnehmer_liste = array( array(), array() );
	$teilnehmer_liste[0] = array_merge($auster_liste[0], $gesamt_liste[0]);
	$teilnehmer_liste[1] = array_merge($auster_liste[1], $gesamt_liste[1]);

	// Namen sortieren
	array_multisort($aeap_liste[0], SORT_ASC, SORT_NATURAL, $aeap_liste[1]);
	array_multisort($mitarbeiter_liste[0], SORT_ASC, SORT_NATURAL, $mitarbeiter_liste[1]);
	array_multisort($teilnehmer_liste[0], SORT_ASC, SORT_NATURAL, $teilnehmer_liste[1]);
	
	$master_liste = array($aeap_liste, $mitarbeiter_liste, $teilnehmer_liste);
	
	return $master_liste;
}

// Erstellt Namens- und Emailliste für die AEAP via LDAP_Abfrage und sortiert sie
function AEAP_liste () {
	// lädt die Besteller Namen und Emails über LDAP
	$aeap_liste = LDAP_Abfrage("AEAP");
	
	// Namen sortieren
	array_multisort($aeap_liste[0], SORT_ASC, SORT_NATURAL, $aeap_liste[1]);
	
	return $aeap_liste;
}

// Erstellt Namens- und Emailliste für die Teilnehmer via LDAP_Abfrage und sortiert sie
function Teilnehmer_liste () {
	// lädt die Besteller Namen und Emails über LDAP
	$auster_liste = LDAP_Abfrage("Auster");
	$gesamt_liste = LDAP_Abfrage("Gesamt");
	
	// packt Auster und Gesamt Teilnehmer in ein Array
	$teilnehmer_liste = array( array(), array() );
	$teilnehmer_liste[0] = array_merge($auster_liste[0], $gesamt_liste[0]);
	$teilnehmer_liste[1] = array_merge($auster_liste[1], $gesamt_liste[1]);
	
	// Namen sortieren
	array_multisort($teilnehmer_liste[0], SORT_ASC, SORT_NATURAL, $teilnehmer_liste[1]);
	
	return $teilnehmer_liste;
}

// LDAP Abfrage für alle User in $Gruppe
function LDAP_Abfrage ( $Gruppe ) {
	// Verbindung zum LDAP Server testen
	$Verbindung = ldap_connect('ldap://BTZJESSRV01.btz-jena.de:389') or die("Verbindung fehlgeschlagen.");

	if ($Verbindung)
	{
		// ldap user & password
		$user = 'ldap';
		$password = '123456';
		// Einloggen von $user in $Verbindung
		$login = ldap_bind($Verbindung, $user, $password);
		
		// Fallunterscheidung nach $Gruppe
		switch ($Gruppe)
		{
			case 'AEAP':
				$Anfrage = "OU=User AEAP,OU=Schulnetz User,DC=btz-jena,DC=de";
				break;
			case 'Auster':
				$Anfrage = "OU=User Auster,OU=Schulnetz User,DC=btz-jena,DC=de";
				break;
			case 'Gesamt':
				$Anfrage = "OU=User Gesamt,OU=Schulnetz User,DC=btz-jena,DC=de";
				break;
			case 'Mitarbeiter':
				$Anfrage = "OU=Persoenlich,OU=User Mitarbeiter,OU=Schulnetz User,DC=btz-jena,DC=de";
				break;
		}
		
		// Suche nach $Gruppe Benutzern
		$Suche = ldap_search($Verbindung, $Anfrage, "sn=*");
		$Namen = ldap_get_entries($Verbindung, $Suche);
		
		// Übertragung Ergebnis in Ausgabe Array (mit Einfügen des Kommas zwischen Nach- und Vorname)
		$Resultat = array( array(), array() );
		for ( $i=0; $i < $Namen["count"]; $i++)
		{
			if ( "Gast 1" != $Namen[$i]["cn"][0] && "Gast 2" != $Namen[$i]["cn"][0] && "KOPIER USER" != $Namen[$i]["cn"][0] && "KOPIER AUSTER" != $Namen[$i]["cn"][0] ) // Überspringe AEAP:'Gast 1'&'Gast 2' und Gesamt:'KOPIER USER'
			{
				// konvertiert Kodierung (damit Umlaute funktionieren) und dann "Nachname Vorname" => "Nachname, Vorname"
				if ( !strncmp($Namen[$i]["cn"][0], "AEAP", 4)) $Resultat[0][] = mb_convert_encoding($Namen[$i]["cn"][0], 'UTF-8', 'ISO-8859-1');
				else $Resultat[0][] = str_replace( ' ' , ', ' , mb_convert_encoding($Namen[$i]["cn"][0], 'UTF-8', 'ISO-8859-1'));
				$Resultat[1][] = mb_convert_encoding($Namen[$i]["mail"][0], 'UTF-8', 'ISO-8859-1');
			}
		}
		
		// Ausloggen
		ldap_close($Verbindung);
		
	}
	
	return $Resultat;
}
