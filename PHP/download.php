<?php
/**
 * download.php
 *
 * Author: Paul Gerlach
 * adaptiert von Theme Customisations function.php
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// überprüft und übernimmt parameter von der URL
if(isset($_REQUEST["nummer"]))
{
	// Parameter aus der URL entnehmen und kontrollieren ob korrekt (nur Zahlen werden akzeptiert)
	$parameter = $_REQUEST["nummer"];
	if(is_numeric($parameter))
	{
		// Parameter bereinigen und Dateiname&-pfad parat legen
		$nummer = intval($parameter);
		$nummer = ($nummer<10)?('0'.$nummer):($nummer);
		$Dateiname = 'Bestellliste_' . $nummer . '.csv';
		$Dateipfad = './Mittagessen/' . $Dateiname;
	} else die("Fehlerhafte Wochennummer " . $parameter);
	
    // Sichergehen, ob die Datei auch da ist
    if( !file_exists($Dateipfad) ) die("Bestellliste nicht gefunden.");
	
    // Download vorbereiten
	header("Content-Disposition: attachment; filename=$Dateiname");
	header("Content-Length: " . filesize($Dateipfad));
    header("Content-Type: text/csv;");
	// Datei auslesen und zum Download weiterreichen
	readfile($Dateipfad);
	
	exit;
}
if(isset($_REQUEST["speiseplan"]))
{
	// Parameter aus der URL entnehmen und kontrollieren ob korrekt (nur Zahlen werden akzeptiert)
	$parameter = $_REQUEST["speiseplan"];
	if(is_numeric($parameter))
	{
		// Parameter bereinigen und Dateiname&-pfad parat legen
		$nummer = intval($parameter);
		$nummer = ($nummer<10)?('0'.$nummer):($nummer);
		$Dateiname = 'Speiseplan_' . $nummer . '.csv';
		$Dateipfad = './Mittagessen/' . $Dateiname;
	} else die("Fehlerhafte Wochennummer " . $parameter);
	
    // Sichergehen, ob die Datei auch da ist
    if( !file_exists($Dateipfad) ) die("Speiseplan nicht gefunden.");
	
    // Download vorbereiten
	header("Content-Disposition: attachment; filename=$Dateiname");
	header("Content-Length: " . filesize($Dateipfad));
    header("Content-Type: text/csv;");
	// Datei auslesen und zum Download weiterreichen
	readfile($Dateipfad);
	
	exit;
}
if(isset($_REQUEST["bestellsumme"]))
{
	// Parameter aus der URL entnehmen und kontrollieren ob korrekt (nur Zahlen werden akzeptiert)
	$parameter = $_REQUEST["bestellsumme"];
	if(is_numeric($parameter))
	{
		// Parameter bereinigen und Dateiname&-pfad parat legen
		$nummer = intval($parameter);
		$nummer = ($nummer<10)?('0'.$nummer):($nummer);
		$Dateiname = 'Bestellsumme_' . $nummer . '.csv';
		$Dateipfad = './Mittagessen/' . $Dateiname;
	} else die("Fehlerhafte Wochennummer " . $parameter);
	
    // Sichergehen, ob die Datei auch da ist
    if( !file_exists($Dateipfad) ) die("Bestellsumme nicht gefunden.");
	
    // Download vorbereiten
	header("Content-Disposition: attachment; filename=$Dateiname");
	header("Content-Length: " . filesize($Dateipfad));
    header("Content-Type: text/csv;");
	// Datei auslesen und zum Download weiterreichen
	readfile($Dateipfad);
	
	exit;
}
