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

/* globale Variablen/Arrays
 * zur Nutzung " include( 'variablen.php'); " vorher in der Funktion ausführen
 */
final class Variablen {
	// Variablen für Essensbestellung und Speisepläne
	public static $Tag_name = array( 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag');
	public static $Tag_kurz = array( 'Mo', 'Di', 'Mi', 'Do', 'Fr');
	public static $kurz_essen = array( "Mo_voll", "Mo_veg", "Di_voll", "Di_veg", "Mi_voll", "Mi_veg", "Do_voll", "Do_veg", "Fr_voll", "Fr_veg", "Mo_nix", "Di_nix", "Mi_nix", "Do_nix", "Fr_nix" );
	public static $kurz_essen_num = array( "Mo_voll_num", "Mo_veg_num", "Di_voll_num", "Di_veg_num", "Mi_voll_num", "Mi_veg_num", "Do_voll_num", "Do_veg_num", "Fr_voll_num", "Fr_veg_num");

	public static $Kateg_liste = array( 'AEAP', 'Mitarbeiter', 'Teilnehmer');


	// Variablen für IU Veranstaltungen
	public static $IU_Tag_name = array( 'Dienstag', 'Mittwoch', 'Donnerstag' );
	public static $IU_Tag_kurz = array( 'Di', 'Mi', 'Do' );

	public static $IU_Kateg_liste = array ( 'AEAP', 'Teilnehmer' );
}

