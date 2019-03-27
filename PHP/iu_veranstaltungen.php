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
 * function IU_Tabelle_Erstellen ( $modus = 'namen', $KW = 0 )
 * function IU_Namensliste ()
 * function IU_Namensliste_Anzeigen ()
 * function IU_Namensliste_Delete ()
 * function IU_Namensliste_Neue_Namen()
 * function IU_Namensliste_Neue_Namen_Eintragen ()
 * function IU_Namentest ( $Name, $Kategorie )
 * 
 * function IU_Anmeldung ( $modus )
 * function IU_Anmeldung_Namenwahl ()
 * function IU_Check( $Name )
 * function IU_Woche_Ausgabe ( $diff )
 * 
 * function IU_wer_ist_angemeldet ()
 * function IU_Angemeldet_Email ( $heute_nummer )
 * function IU_cleanup ( $Tiefe = 0 )
 * 
 */

// Erstellt die Tabellen die für die IU Veranstaltungen benötigt werden
function IU_Tabelle_Erstellen ( $modus = 'namen', $KW = 0 ) {// default: Namensliste || 'woche' für Zusage-Liste für $KW
	global $wpdb;
	
	$charset_collate = $wpdb->get_charset_collate();

	if ( 'woche' == $modus)
	{
		$table = $wpdb->prefix . "IU_KW_" . $KW;
		
		$SQL = "CREATE TABLE IF NOT EXISTS $table (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				Kategorie text NOT NULL,
				Name text NOT NULL,
				Di mediumint(9) NOT NULL,
				Mi mediumint(9) NOT NULL,
				Do mediumint(9) NOT NULL,
				UNIQUE (id)
			) $charset_collate;";
	}
	else 
	{
		$table = $wpdb->prefix . "IU_namen";
		
		$SQL = "CREATE TABLE IF NOT EXISTS $table (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				Kategorie text NOT NULL,
				Name text NOT NULL,
				UNIQUE (id)
			) $charset_collate;";
	}
	
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $SQL );
	
	return $table;
}

// Namensliste anzeigen und ändern
function IU_Namensliste () {
	// Buttons für Namensliste anzeigen oder Formular zum Eintragen von Number neuen Namen öffnen
    $html = "
		<div>
			<h3 id='liste'>
				IU Namensliste anzeigen und alte Namen aus der IU Namensliste löschen:
			</h3>
			<form action='#liste' method='post'>
				<input type='submit' name='Anzeigen' value='IU Namensliste anzeigen.'>
			</form>
		</div>";

	// Namensliste anzeigen
	if ( isset($_POST['Anzeigen']) ) 
	{
		$html .= IU_Namensliste_Anzeigen();
	}
	
	// Gewählte Namen aus Namensliste löschen
	if ( isset($_POST['Delete']) )
	{
		$html .= IU_Namensliste_Delete();
	}
	
	$html .= "
		<div>
			<h3 id='neu'>
				Neue IU Bewohner in die IU Namensliste eintragen:
			</h3>
			<form action='#neu' method='post'>
				<table><tbody><tr>
				<td width=25%>Wieviele neue Einträge?</td>
				<td width=10%><input type='text' name='Number' value=''></td>
				<td width=5%></td>
				<td><input type='submit' name='Neu' value='Formular für neue Namen öffnen.'></td>
				</tr></tbody></table>
			</form>
		</div>";
	
	
	// Formular für neue Namen
	if ( isset($_POST['Neu']) ) 
	{
		$html .= IU_Namensliste_Neue_Namen();
	}
	
	// Neue Namen in Tabelle eintragen
	if ( isset($_POST['Edit']) )
	{
		$html .= IU_Namensliste_Neue_Namen_Eintragen();
	}
	
	return $html;
}

// Namensliste anzeigen mit Checkboxen zum Löschen von einzelnen Einträgen
function IU_Namensliste_Anzeigen () {
	global $wpdb;
	$table = IU_Tabelle_Erstellen();
	
	// Liste ermitteln
	$SQL = "SELECT * FROM $table ORDER BY Kategorie, Name";
	$search = $wpdb->get_results( $SQL, ARRAY_A);
	
	// Liste sortieren
	$length = count($search);
	$liste = array( 'id' => array(), 'Kategorie' => array(), 'Name' => array() );
	for ( $i = 0; $i < $length; $i++)
	{
		$liste['id'][$i] = $search[$i]['id'];
		$liste['Kategorie'][$i] = $search[$i]['Kategorie'];
		$liste['Name'][$i] = $search[$i]['Name'];
	}
	array_multisort($liste['Kategorie'], SORT_ASC, SORT_NATURAL, $liste['Name'], SORT_ASC, SORT_NATURAL, $liste['id'], SORT_ASC, SORT_NATURAL);
	
	// beginnt output-buffering
	ob_start();
	
	{
		echo "<div>
			<form action='#liste' method='post'>
				<table>
					<thead>
						<tr>
							<td>Kategorie</td>
							<td>Name</td>
							<td>Löschen?</td>
						</tr>
					</thead>
					<tbody>";
						for ( $i = 0; $i < $length; $i++ )
						{
							echo '<tr>
								<td>'.$liste['Kategorie'][$i].'</td>
								<td>'.$liste['Name'][$i].'</td>
								<td><input type="checkbox" name="'.$liste['id'][$i].'" value="löschen"></td>
							</tr>';
						}
					echo "</tbody>
				</table>
				<input type='submit' name='Delete' value='Markierte Einträge löschen.'>
			</form>
		</div>";
	}
		
	// beendet output-buffering und übergibt Buffer an $html für Ausgabe
	$html = ob_get_clean();
	
	return $html;
}

// Ausgewählte Namen aus der Namensliste löschen
function IU_Namensliste_Delete () {
	global $wpdb;
	$table = IU_Tabelle_Erstellen();
	
	// zu löschende Zeilen ermitteln
	$ID = array_keys($_POST, 'löschen');
	$liste = '(';
	for ( $i = 0, $l = count($ID); $i < $l; $i++)
	{
		$liste .= $ID[$i].(($i == ($l-1))?(''):(', '));
	}
	$liste .= ')';
	
	// Zeilen löschen
	$deletion = $wpdb->query( "DELETE FROM $table WHERE id IN ".$liste );
	
	// Ergebnisbericht
	if ( false === $deletion )
	{
		$html = '<p>Fehler beim Löschen der Namen.</p>';
	}
	elseif ( 0 === $deletion )
	{
		$html = '';
	}
	else
	{
		$html = '<p>Gelöschte Namen: '.$deletion.'</p>';
	}
	
	return $html;
}

// Formular für neue Namen
function IU_Namensliste_Neue_Namen() {
	if ( is_numeric($_POST["Number"]) )
		{
			$Number = $_POST['Number'];
			
			// beginnt output-buffering
			ob_start();
			// Namensliste anzeigen oder Formular zum Eintragen von Number neuen Namen öffnen
			?>
			<div>
				<div>
					<p>Format:
					<br>AEAP: "AEAP #"
					<br>Teilnehmer: "Nachname, Vorname"</p>
				</div>
				<form action='#neu' method='post'>
					<table>
						<thead>
							<tr>
								<!-- <th>Kategorie</th> -->
								<th>Name</th>
							</tr>
						</thead>
						<tbody>
			<?php
			for ( $i = 0; $i < $Number ; $i++ )
			{
				?>
							<tr>
								<td><input type='text' name='Name_<?php echo $i; ?>' value=''  width=30%></td>
							</tr>
			<?php } ?>
						</tbody>
					</table>
					<div class='hidden'>
						<select id='Number' name='Number'><option><?php echo $Number; ?></option></select>
					</div>
					<input type='submit' name='Edit' value='Namen in Liste eintragen.'>
				</form>
			</div>
			<?php
			// beendet output-buffering und übergibt Buffer an $html für Ausgabe
			$html = ob_get_clean();
		} else { $html = "<p>Bitte Anzahl neuer Namen angeben.</p>"; }
	return $html;
}

// Neue Namen aus Formular eintragen
function IU_Namensliste_Neue_Namen_Eintragen () {
	$Number = $_POST['Number'];
		
	// Eingabe auswerten
	$Eintrag = array( );
	$fehlerhafter_Eintrag = array();
	$doppelter_Eintrag = array();
	for ( $i = 0, $key = 0; $i < $Number; $i++ )
	{
		if ( '' != $_POST['Name_'.$i] ) // ignoriere Einträge mit leerem Namensfeld
		{
			if ( !strncmp('AEAP ', $_POST['Name_'.$i], 5) )// Name beginnt mit 'AEAP ' (und muss somit ein AEAP Name sein)
			{
				if ( false !== IU_Namentest( $_POST['Name_'.$i], 'AEAP') ) // Test ob Name in der AEAP Liste des AD vorhanden ist
				{
					if ( !IU_Check($_POST['Name_'.$i]) )
					{
						$Eintrag[] = array( 'Kategorie' => 'AEAP', 'Name' => $_POST['Name_'.$i] );
					}
					else $doppelter_Eintrag[] = $_POST['Name_'.$i];
				}
				else $fehlerhafter_Eintrag[] = $_POST['Name_'.$i];
			}
			elseif ( false !== IU_Namentest( $_POST['Name_'.$i], 'Teilnehmer') ) // nicht AEAP => Teilnehmer, Test ob Name in Auster oder Gesamt Liste des AD vorhanden ist
			{
				if ( !IU_Check($_POST['Name_'.$i]) )
				{
					$Eintrag[] = array( 'Kategorie' => 'Teilnehmer', 'Name' => $_POST['Name_'.$i] );
				}
				else $doppelter_Eintrag[] = $_POST['Name_'.$i];
			}
			else $fehlerhafter_Eintrag[] = $_POST['Name_'.$i];
		}
	}

	// korrekte Namen in Liste eintragen
	global $wpdb;
	$table = IU_Tabelle_Erstellen();
	$counter = 0;
	$SQL_Fehler = array();
	foreach ( $Eintrag as $Zeile )
	{
		$test = $wpdb->insert( $table, $Zeile );
		if ( $test )
		{
			$counter++;
		}
		else
		{
			$SQL_Fehler = $Zeile['Name'];
		}
	}
	
	// Ergebnisbericht
	$html = '<p>Erfolgreich eingetragene Namen: '.$counter.'</p>';
	foreach ( $SQL_Fehler as $error )
	{
		$html .= $error['Name'].' konnte auf Grund eines Fehlers nicht eingetragen werden.<br>';
	}
	foreach ( $fehlerhafter_Eintrag as $error )
	{
		$html .= $error.' hat nicht das richtige Format oder ist falsch geschrieben.<br>';
	}
	foreach ( $doppelter_Eintrag as $error )
	{
		$html .= $error.' ist bereits in der Liste.<br>';
	}
	return $html;
}

// Test ob $Name in $Kategorie im AD is
function IU_Namentest ( $Name, $Kategorie ) { // Kategorie = AEAP, Teilnehmer
	$aeap_liste = AEAP_liste();
	$teilnehmer_liste = Teilnehmer_liste();
	
	switch ($Kategorie)
	{
		case 'AEAP':
			return array_search( $Name, $aeap_liste[0] );
		case 'Teilnehmer':
			return array_search( $Name, $teilnehmer_liste[0] );
	}
}

// Tabelle zum Anmelden
function IU_Anmeldung ( $modus ) { // $modus = 'saeule' / 'teilnehmer'
	if ( 'teilnehmer' == $modus ) 
	{
		$user = wp_get_current_user();
		$logged_in = $user->exists();
		if ( !strncmp('AEAP', $user->user_firstname, 4) ) // User ist AEAP
		{
			$Name = $user->user_firstname . ' ' . $user->user_lastname;
		}
		else // User ist Teilnehmer 
		{
			$Name = $user->user_lastname . ', ' . $user->user_firstname;
		}
	}
	elseif ( 'saeule' == $modus )
	{
		if ( isset($_POST['Namenwahl']) ) $Name = $_POST['Eingabe_'.$_POST['Kategorie']];
		if ( isset($_POST['Anmeldung']) ) $Name = $_POST['Name'];
		
		$logged_in = ( '' != $Name )?(1):(0);
	}
	
	if ( $logged_in || ( $logged_in && isset($_POST['Namenwahl']) ) || isset($_POST['Anmeldung']) )
	{
		// nur für IU-Bewohner
		if ( IU_Check( $Name ) || 'saeule' == $modus )
		{
			$Anz_Wochen = 2; // Wie viele Wochen sollen angezeigt werden?
            
			global $wpdb;
			
			$KW = date('W');
			$heute = date('N');
			$table = array();
			$teilnahme = array();
			for ( $i = 0; $i < $Anz_Wochen; $i++ )
			{
				$table[$i] = IU_Tabelle_Erstellen('woche', date('W', strtotime('+'.$i.' week')));
				$teilnahme[$i] = $wpdb->get_row( "SELECT * FROM ".$table[$i]." WHERE Name = '$Name'", ARRAY_A );
			}
			
			// Anmelde-Button gedrückt
			if ( isset($_POST['Anmeldung']) )
			{
				$Eintrag = array();
				for ( $i = 0; $i < $Anz_Wochen; $i++ ) // alle Wochen durchgehen
				{
					for ( $j = 0; $j < 3; $j++ ) // Di, Mi, Do
					{
						switch ($_POST[Variablen::$IU_Tag_kurz[$j].'_'.$i])
						{
							case 'ab':
								$Eintrag[Variablen::$IU_Tag_kurz[$j]] = 0;
								break;
							case 'an':
								$Eintrag[Variablen::$IU_Tag_kurz[$j]] = 1;
								break;
							default:
								$Eintrag[Variablen::$IU_Tag_kurz[$j]] = ( isset($teilnahme[$i]['id']) )?$teilnahme[$i][Variablen::$IU_Tag_kurz[$j]]:0;

						}
					}
					// Eintrag['Name'] & ['Kategorie']
					$Eintrag['Name'] = $Name;
					$Eintrag['Kategorie'] = (!strncmp('AEAP', $Name, 4)) ? ('AEAP') : ('Teilnehmer');
					
					// if teilnahme[$i]['id'] exists => hat schonmal angemeldet => replace
					if ( isset($teilnahme[$i]['id']) ) $Eintrag['id'] = $teilnahme[$i]['id'];
					
					$wpdb->replace($table[$i], $Eintrag);
					$teilnahme[$i] = $wpdb->get_row( "SELECT * FROM ".$table[$i]." WHERE Name = '$Name'", ARRAY_A );
				}
				
				$edit = '<h3 id="Erfolg-Text">Änderungen gespeichert.</h3>';
			}
			
			if ( 'teilnehmer' == $modus || isset($_POST['Namenwahl']) )
			{
				// beginnt output-buffering
				ob_start();
				
				echo '<div>
					<p>';
						if ( 'teilnehmer' == $modus )
						{
							echo "Anmeldung als: $Name";
						}
						else
						{
							echo 'Anmeldung als: ';
							echo ('Teilnehmer'==$_POST['Kategorie'])?( substr($Name, 0, (strpos($Name, ', ')+3)) ):( $Name );
						}
						echo '<br>
						Anmeldungen müssen vor 12:00 am Vortag abgeschickt werden.
					</p>
					<form action="#anmeldung" method="post">
						<table>
							<thead>
								<tr>
									<td>[KW] Di ~ Do</td>
									<td>Dienstag</td>
									<td>Mittwoch</td>
									<td>Donnerstag</td>
								</tr>
							</thead>
							<tbody>';
								for ( $i = 0; $i < $Anz_Wochen; $i++ )
								{
									echo '<tr>
										<td>'.IU_Woche_Ausgabe($i).'</td>';
									for ( $j = 0; $j < 3; $j++ ) // Di, Mi, Do
									{
										if ( ( ($j+2 == $heute) && (12>date('G')) ) || ($j + 2) > $heute || $i != 0 ) // für laufende Woche Anmeldeschluss am Vortag um 12:00
										{
											if ( $teilnahme[$i][Variablen::$IU_Tag_kurz[$j]] ) // Für den Tag bereits angemeldet
											{
												echo '<td>'.
													IU_Datum_Ausgabe( $j, $i ).'<br>
													Angemeldet<br>
													<input type="checkbox" id="'.Variablen::$IU_Tag_kurz[$j].'_'.$i.'" name="'.Variablen::$IU_Tag_kurz[$j].'_'.$i.'" value="ab">
													<label for="'.Variablen::$IU_Tag_kurz[$j].'_'.$i.'">Abmelden?</label>
												</td>';
											}
											else
											{
												echo '<td>'.
													IU_Datum_Ausgabe( $j, $i ).'<br>
													<input type="checkbox" id="'.Variablen::$IU_Tag_kurz[$j].'_'.$i.'" name="'.Variablen::$IU_Tag_kurz[$j].'_'.$i.'" value="an">
													<label for="'.Variablen::$IU_Tag_kurz[$j].'_'.$i.'">Anmelden</label>
												</td>';
											}
										}
										else
										{
											if ( $teilnahme[$i][Variablen::$IU_Tag_kurz[$j]] ) // Für den Tag angemeldet
											{
												echo "<td>".IU_Datum_Ausgabe( $j, $i )."<br>Angemeldet</td>";
											}
											else
											{
												echo "<td>".IU_Datum_Ausgabe( $j, $i )."<br>Nicht angemeldet</td>";
											}
										}
									}
									echo '</tr>';
								}
							echo "</tbody>
						</table>";
							// Bei Säule Namen weiterreichen
							if('saeule' == $modus) {
								echo "<div class='hidden'><select name='Name'><option>".$Name."</option></select></div>";
							}
						
						echo "<input type='submit' name='Anmeldung' value='Abschicken'>
					</form>
				</div>";
					
				// beendet output-buffering und übergibt Buffer an $html für Ausgabe
				$html = ob_get_clean() . $edit;
			}
			else $html = $edit;
		}
		else $html = "<p>Teilnahme nur für IU-Bewohner</p>";
	}
	elseif ( 'teilnehmer' == $modus ) $html = "<p>Teilnahme nur für IU-Bewohner<br><a href='http://web.btz-jena.de/wordpress/wp-login.php'><div style='color:blue'>Hier klicken zum Anmelden.</div></a></p>";
	elseif ( !$logged_in && isset($_POST['Namenwahl']) ) $html = '<h1>Bitte Kategorie und Name auswählen.</h1>';
	
	return $html;
}

// Dropdown Kategorie- & Namenswahl für IU_Anmeldung('saeule')
function IU_Anmeldung_Namenwahl () {
	if ( !isset($_POST['Namenwahl']) || !isset($_POST['Eingabe_'.$_POST['Kategorie']] ) )
	{
		global $wpdb;
		$table = IU_Tabelle_Erstellen();
		
		$AEAP = $wpdb->get_results("SELECT * FROM $table WHERE Kategorie = 'AEAP'", ARRAY_A);
		$Teilnehmer = $wpdb->get_results("SELECT * FROM $table WHERE Kategorie = 'Teilnehmer'", ARRAY_A);
		
		// beginnt output-buffering
		ob_start();
		{
			echo "<div>
				<form action='#namenwahl' method='post'>
					<table>
						<tbody>
							<tr>
								<td width=10%>Kategorie: </td>
								<td width=20%><select id='Kategorie' name='Kategorie'>
										<option></option>
										<option>AEAP</option>
										<option>Teilnehmer</option>
									</select></td>
								<td width=5%></td>
								<td width=10%>Name: </td>
								<td width=20%>
									<select id='Eingabe_AEAP' name='Eingabe_AEAP' value='' class='hidden_name'>
										<option></option>";
		foreach ( $AEAP as $key )
		{
			echo						'<option>'.$key['Name'].'</option>';
		}
									echo "</select>
									<select id='Eingabe_Teilnehmer' name='Eingabe_Teilnehmer' value='' class='hidden_name'>
										<option></option>";
		foreach ( $Teilnehmer as $key )
		{
			echo						"<option value='".$key['Name']."'>". substr($key['Name'], 0, (strpos($key['Name'], ', ')+3)) .".</option>";
		}
									echo "</select>
								</td>
							</tr>
						</tbody>
					</table>
					<input type='submit' name='Namenwahl' value='Namen auswählen'>
				</form>
			</div>";
		}
		// beendet output-buffering und übergibt Buffer an $html für Ausgabe
		$html = ob_get_clean();
	}
	
	return $html;
}

// Test ob $Name in IU Namensliste ist
function IU_Check( $Name ) {
	global $wpdb;
	$table = IU_Tabelle_Erstellen();
	return $wpdb->query("SELECT * FROM $table where Name = '$Name'");
}

// gibt das Datum des Wochentages der laufenden oder der kommenden Woche aus
function IU_Datum_Ausgabe ( $tag_nummer, $diff ) { // $tag_nummer == 0>Di, 1>Mi, 2>Do $diff == 0 => laufende Woche, == 1 => kommende, etc.
	$heute = date('w');
	$tag = array();
	if ( 0 != $diff ) 
	{
		$plus = ' +'.$diff.' week';
		$KW = date('W', strtotime('+1 week'));
	}
	else 
	{
		$plus = '';
		$KW = date('W');
	}
	switch ($heute)
	{
		case 1: // Montag
			$tag[0] = date('d.m.', strtotime('Tuesday'.$plus));
			$tag[1] = date('d.m.', strtotime('Wednesday'.$plus));
			$tag[2] = date('d.m.', strtotime('Thursday'.$plus));
			break;
		case 2: // Dienstag
			$tag[0] = date('d.m', strtotime('Tuesday'.$plus));
			$tag[1] = date('d.m.', strtotime('Wednesday'.$plus));
			$tag[2] = date('d.m.', strtotime('Thursday'.$plus));
			break;
		case 3: // Mittwoch
			$tag[0] = date('d.m.', strtotime('last Tuesday'.$plus));
			$tag[1] = date('d.m.', strtotime('Wednesday'.$plus));
			$tag[2]= date('d.m.', strtotime('Thursday'.$plus));
			break;
		case 4: // Donnerstag
			$tag[0] = date('d.m.', strtotime('last Tuesday'.$plus));
			$tag[1] = date('d.m.', strtotime('last Wednesday'.$plus));
			$tag[2] = date('d.m.'.$plus);
			break;
		case 5: // Freitag
		default: // Wochenende
			$tag[0] = date('d.m.', strtotime('last Tuesday'.$plus));
			$tag[1] = date('d.m.', strtotime('last Wednesday'.$plus));
			$tag[2] = date('d.m.', strtotime('last Thursday'.$plus));
	}
	return $tag[$tag_nummer];
}

// gibt Kalenderwoche und Datum von Dienstag bis Donnerstag aus für die KW in $diff Wochen
function IU_Woche_Ausgabe ( $diff ) { // $diff == 0 => laufende Woche, == 1 => kommende, etc.
	$heute = date('w');
	
	if ( 0 == $diff )
	{
		$KW = date('W');
		
		switch ($heute)
		{
			case 1: // Montag
				$Dienstag = date('d.m.', strtotime('Tuesday'));
				$Donnerstag = date('d.m.', strtotime('Thursday'));
				break;
			case 2: // Dienstag
				$Dienstag = date('d.m');
				$Donnerstag = date('d.m.', strtotime('Thursday'));
				break;
			case 3: // Mittwoch
				$Dienstag = date('d.m.', strtotime('last Tuesday'));
				$Donnerstag= date('d.m.', strtotime('Thursday'));
				break;
			case 4: // Donnerstag
				$Dienstag = date('d.m.', strtotime('last Tuesday'));
				$Donnerstag = date('d.m.');
				break;
			case 5: // Freitag
			default: // Wochenende
				$Dienstag = date('d.m.', strtotime('last Tuesday'));
				$Donnerstag = date('d.m.', strtotime('last Thursday'));
		}
	}
	else
	{
		$KW = date('W', strtotime('+1 week'));
		
		switch ($heute)
		{
			case 1: // Montag
				$Dienstag = date('d.m.', strtotime('Tuesday +'.$diff.' week'));
				$Donnerstag = date('d.m.', strtotime('Thursday +'.$diff.' week'));
				break;
			case 2: // Dienstag
			case 3: // Mittwoch
				$Dienstag = date('d.m.', strtotime('Tuesday +'.($diff).' week'));
				$Donnerstag = date('d.m.', strtotime('Thursday +'.$diff.' week'));
				break;
			case 4: // Donnerstag
			case 5: // Freitag
			default: // Wochenende
				$Dienstag = date('d.m.', strtotime('Tuesday +'.($diff).' week'));
				$Donnerstag = date('d.m.', strtotime('Thursday +'.($diff).' week'));
		}
	}
	
	$html = "[$KW] $Dienstag ~ $Donnerstag";
	return $html;
}

// Anzeige wer ist angemeldet
function IU_wer_ist_angemeldet () {
	global $wpdb;
	include( 'variablen.php');
	$Anz_Wochen = 2;
	$KW = date('W');
	$table = array();
	$namen = array();
	
	$html = '<h3>Liste der angemeldeten Teilnehmer:</h3>';
	
	for ( $i = 0; $i < $Anz_Wochen; $i++ )
	{
		$table[$i] = IU_Tabelle_Erstellen('woche', date('W', strtotime('+'.$i.' week')));
		
		// Listen ermitteln
		$AEAP_raw = $wpdb->get_results("SELECT * FROM ".$table[$i]." WHERE Kategorie = 'AEAP'", ARRAY_A);
		$Teilnehmer_raw = $wpdb->get_results("SELECT * FROM ".$table[$i]." WHERE Kategorie = 'Teilnehmer'", ARRAY_A);
		
		// Listen sortieren
		if ( !empty($AEAP_raw))
		{
			$AEAP = array( 'Name' => array(), 'Di'  => array(), 'Mi'  => array(), 'Do' => array() );
			$AEAP_length = count($AEAP_raw);
			for ( $i = 0; $i < $AEAP_length; $i++ )
			{
				$AEAP['Name'][$i] = $AEAP_raw[$i]['Name'];
				$AEAP['Di'][$i] = $AEAP_raw[$i]['Di'];
				$AEAP['Mi'][$i] = $AEAP_raw[$i]['Mi'];
				$AEAP['Do'][$i] = $AEAP_raw[$i]['Do'];
			}
			array_multisort($AEAP['Name'], SORT_ASC, SORT_NATURAL);
		}
		
		if ( !empty($Teilnehmer_raw))
		{
			$Teilnehmer = array( 'Name' => array(), 'Di'  => array(), 'Mi'  => array(), 'Do' => array() );
			$Teilnehmer_length = count($Teilnehmer_raw);
			for ( $i = 0; $i < $Teilnehmer_length; $i++ )
			{
				$Teilnehmer['Name'][$i] = $Teilnehmer_raw[$i]['Name'];
				$Teilnehmer['Di'][$i] = $Teilnehmer_raw[$i]['Di'];
				$Teilnehmer['Mi'][$i] = $Teilnehmer_raw[$i]['Mi'];
				$Teilnehmer['Do'][$i] = $Teilnehmer_raw[$i]['Do'];
			}
			array_multisort($Teilnehmer['Name'], SORT_ASC, SORT_NATURAL);
		}
		
		// Teilnehmer Anzahl Listen vorbereiten
		$anz_anmeldung = array( 0, 0, 0);
		
		// beginnt output-buffering
		ob_start();
		
		?>
		<div class="speisekarte-tabelle">
			<table>
				<thead>
					<tr>
						<th>KW: <?php echo IU_Woche_Ausgabe( $i ); ?></th>
						<th>Dienstag</th>
						<th>Mittwoch</th>
						<th>Donnerstag</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>AEAP</td>
						<?php 
						for ( $j = 0; $j < 3; $j++ )
						{
							echo '<td>';
							for ( $k = 0; $k < $AEAP_length; $k++ )
							{
								if ( '1' == $AEAP[Variablen::$IU_Tag_kurz[$j]][$k])
								{
									echo $AEAP['Name'][$k].'<br>';
									$anz_anmeldung[$j]++;
								}
							}
							echo '</td>';
						}?>
					</tr>
					<tr>
						<td>Teilnehmer</td>
						<?php 
						for ( $j = 0; $j < 3; $j++ )
						{
							echo '<td>';
							for ( $k = 0; $k < $Teilnehmer_length; $k++ )
							{
								if ( '1' == $Teilnehmer[Variablen::$IU_Tag_kurz[$j]][$k]) 
								{
									echo $Teilnehmer['Name'][$k].'<br>';
									$anz_anmeldung[$j]++;
								}
							}
							echo '</td>';
						}?>
					</tr>
					<tr>
						<td>Gesamt</td>
						<?php 
						for ( $j = 0; $j < 3; $j++ )
						{
							echo '<td>'.$anz_anmeldung[$j].'</td>';
						}?>
					</tr>
				</tbody>
			</table>
		</div>
		<?php
		// beendet output-buffering und übergibt Buffer an $html für Ausgabe
		$html .= ob_get_clean();
	}
	return $html;
}

// Email für IU-Freizeit Anmeldungen
function IU_Angemeldet_Email ( $heute_nummer ) { // $heute_nummer: 0, 1, 2, 3,...
	global $wpdb;
	include( 'variablen.php');
	
	switch ($heute_nummer)
	{
		case (0): // Montag
			$flag = Variablen::$Tag_kurz[$heute_nummer];
			break;
		case (1): // Dienstag
			$flag = Variablen::$Tag_kurz[$heute_nummer];
			break;
		case (2): // Mittwoch
			$flag = Variablen::$Tag_kurz[$heute_nummer];
			break;
		default:
			$flag = NULL;
	}
	if ( $flag )
	{
		// Listen ermitteln
		$table = IU_Tabelle_Erstellen('woche', date('W'));
		$AEAP_raw = $wpdb->get_results("SELECT * FROM ".$table." WHERE Kategorie = 'AEAP'", ARRAY_A);
		$Teilnehmer_raw = $wpdb->get_results("SELECT * FROM ".$table." WHERE Kategorie = 'Teilnehmer'", ARRAY_A);
		
		// Listen sortieren
		$AEAP = array( 'Name' => array(), 'Di'  => array(), 'Mi'  => array(), 'Do' => array() );
		$Teilnehmer = array( 'Name' => array(), 'Di'  => array(), 'Mi'  => array(), 'Do' => array() );
		$AEAP_length = count($AEAP_raw);
		for ( $i = 0; $i < $AEAP_length; $i++ )
		{
			$AEAP['Name'][$i] = $AEAP_raw[$i]['Name'];
			$AEAP['Di'][$i] = $AEAP_raw[$i]['Di'];
			$AEAP['Mi'][$i] = $AEAP_raw[$i]['Mi'];
			$AEAP['Do'][$i] = $AEAP_raw[$i]['Do'];
		}
		$Teilnehmer_length = count($Teilnehmer_raw);
		for ( $i = 0; $i < $Teilnehmer_length; $i++ )
		{
			$Teilnehmer['Name'][$i] = $Teilnehmer_raw[$i]['Name'];
			$Teilnehmer['Di'][$i] = $Teilnehmer_raw[$i]['Di'];
			$Teilnehmer['Mi'][$i] = $Teilnehmer_raw[$i]['Mi'];
			$Teilnehmer['Do'][$i] = $Teilnehmer_raw[$i]['Do'];
		}
		array_multisort($AEAP['Name'], SORT_ASC, SORT_NATURAL);
		array_multisort($Teilnehmer['Name'], SORT_ASC, SORT_NATURAL);
		$anz_anmeldung = 0;
		
		// Erinnerungsemail vorbereiten
		$mail = 'freizeit-iu-btz-jena@faw.de'; // EMAIL_VERTEILER

		$betreff = 'IU-Freizeit Anmeldungen für '.Variablen::$Tag_name[$heute_nummer];
		
		$header[] = 'MIME-Version: 1.0';
		$header[] = 'Content-type: text/html;';
		$header[] = 'From: IU-Veranstaltungen <Essensbestellung@btz-bewegt.de>';
		
		$text = '
			<html>
				<body>
					<table>
						<thead>
							<tr>
								<th>Kategorie</th>
								<th>'.Variablen::$IU_Tag_name[$heute_nummer].', '.date('d.m.', strtotime('+1 day')).'</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td>AEAP</td>
								<td>';
		for ( $k = 0; $k < $AEAP_length; $k++ )
		{
			if ( '1' == $AEAP[Variablen::$IU_Tag_kurz[$heute_nummer]][$k]) 
			{
				$text .= $AEAP['Name'][$k].'<br>';
				$anz_anmeldung++;
			}
		}
		$text .=				'</td>
							</tr>
							<tr>
								<td>Teilnehmer</td>
								<td>';
		for ( $k = 0; $k < $Teilnehmer_length; $k++ )
		{
			if ( '1' == $Teilnehmer[Variablen::$IU_Tag_kurz[$heute_nummer]][$k]) 
			{
				$text .= $Teilnehmer['Name'][$k].'<br>';
				$anz_anmeldung++;
			}
		}
		$text .=				'</td>
							</tr>
							<tr>
								<td>Gesamt</td>
								<td>';
		$text .=					$anz_anmeldung;
		$text .=				'</td>
							</tr>
						</tbody>
					</table>	
				</body>
			</html>
			';
			
		$gesendet = wp_mail( $mail, $betreff, $text, $header );
	}
	
	
	return $html = $flag;
}

// löscht alte Anmeldelisten, startend bei der vorletzten Woche und $Tiefe weitere Wochen in die Vergangenheit
function IU_cleanup ( $Tiefe = 0 ) { // default: 0, nur die vorletzte Woche
	global $wpdb;
	
	$delete = array();
	
	for ( $i = 0; $i <= $Tiefe; $i++)
	{
		$delete[] = date('W', strtotime("-".($i+2)." week"));
	}
	
	foreach ( $delete as $key )
	{
		$table = $wpdb->prefix.'IU_KW_'.$key;
		$wpdb->query('DROP TABLE '.$table);
	}
}
?>