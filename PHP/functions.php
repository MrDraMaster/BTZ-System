<?php /** @noinspection PhpIncludeInspection */
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
 * function Bestell_Formular ( $modus )
 * function Disclaimer ()
 * function Bestellung_Email ( $mail, $modus, $KW, $Eintrag )
 * function Woche_Ausgabe ( $woche )
 * function Tabelle_Erstellen ( $modus = 'speiseplan', $KW = 0)
 * function Kueche_offen ( $KW )
 * 
 */

// Bestellformular für Webseite (=teilnehmer), Säule/Mitarbeiter (=saeule), und Nachbestellen (=nach)
function Bestell_Formular ( $modus ) { // $modus = 'teilnehmer', 'saeule', 'nach'
	global $wpdb;
	
	// Wochenauswahl für Nachbestellung
	if ( 'nach' == $modus)
	{
		// beginnt output-buffering
		ob_start();
		?>
		<div>
			<div>
				<h3 id='nachbestellen'>Button drücken zum Nachbestellen für die jeweilige Woche:</h3>
			</div>
			<form action="#nachbestellen" method="post">
				<input type="submit" name="Nachbestellung" value="laufende Woche">
				<input type="submit" name="Nachbestellung" value="kommende Woche">
			</form>
		</div>
		<?php
		
		// beendet output-buffering und übergibt Buffer an $nach_auswahl für Ausgabe
		$nach_auswahl = ob_get_clean();
	}
	
	// falls 'teilnehmer', Name, Email und Kategorie bestimmen
	if ( 'teilnehmer' == $modus )
	{
		$user = wp_get_current_user();
		if ( $user->exists() )
		{
			$logged_in = true;
			
			if ( !strncmp('AEAP', $user->user_firstname, 4) ) // User ist AEAP
			{
				$Name = $user->user_firstname . ' ' . $user->user_lastname;
				$Email = $user->user_email;
				$Kategorie = 'AEAP';
			}
			else // User ist Teilnehmer 
			{
				$Name = $user->user_lastname . ', ' . $user->user_firstname;
				$Email = $user->user_email;
				$Kategorie = 'Teilnehmer';
			}
		}
		else $logged_in = false;
	}
	else
	{
		// Namens- und Emaillisten parat legen / $KATEGORIE = array( $Namen_array, $Email_array )
		$master_liste = kategorie_listen();
		
		$logged_in = true;
	}
	
	// nicht-Nachbestellung oder Nachbestellungswoche ausgewählt
	if ( 'nach' != $modus || isset($_POST['Nachbestellung']) )
	{
		if ( 'nach' == $modus )
		{
			$KW = ( 'laufende Woche' == $_POST['Nachbestellung'] ) ? date('W') : date('W', strtotime('+1 week'));
			$Flag_Woche = ( 'laufende Woche' == $_POST['Nachbestellung'] ) ? 'laufende' : 'kommende';
			$heute = ( 'laufende Woche' == $_POST['Nachbestellung'] ) ? (date('N')-1) : 0;
		}
		else
		{
			if ( 3 > date('N') || ( 3 == date('N') && 9 > date('G') ) ) // (Mo oder Di) ODER (Mi und vor 09:00)
			{
				$KW = date('W', strtotime('+1 week'));
				$Flag_Woche = 'kommende';
			}
			else // Rest der Woche
			{
				$KW = date('W', strtotime('+2 week'));
				$Flag_Woche = 'ueber';
			}
			$heute = 0;
		}
		
		switch ($Flag_Woche)
		{
			case 'laufende':
				$welche_woche = '<h3 id="bestellung">Bestellung für die laufende Woche ['.Woche_Ausgabe($Flag_Woche).']</h3>';
				break;
			case 'kommende':
				$welche_woche = '<h3 id="bestellung">Bestellung für die kommende Woche ['.Woche_Ausgabe($Flag_Woche).']</h3>';
				if ( 'nach' != $modus ) $welche_woche .= '<p>Bitte bis Mittwoch 09:00 bestellen. Für Nachbestellungen oder Änderungen für die laufende Woche bitte bei ihrem Sozialpädagogen oder der Küche melden.</p>'; // KIOSK_COMPUTER
				break;
			case 'ueber':
				$welche_woche = '<h3 id="bestellung">Bestellung für die übernächste Woche ['.Woche_Ausgabe($Flag_Woche).']</h3>';
				if ( 'nach' != $modus ) $welche_woche .= '<p>Für Nachbestellungen oder Änderungen für die laufende oder kommende Woche bitte bei ihrem Sozialpädagogen oder der Küche melden.</p>'; // KIOSK_COMPUTER
				break;
            default:
                $welche_woche = "ERROR";
		}
		
		// beginnt output-buffering
		ob_start();
		
		?>			
			<div>
				<?php echo $welche_woche; ?>
			</div>
			<form action="#bestellung" method="post" id="bestellung" name="bestellung" autocomplete="off">
				<table>
					<thead>
						<tr>
							<td width= '40%'>
								<?php
								if( 'teilnehmer' == $modus ) // Teilnehmer-Seite
								{
									if ( $logged_in ) // User ist eingeloggt
									{
										/** @noinspection PhpUndefinedVariableInspection */
										/** @noinspection PhpUndefinedVariableInspection */
										echo "<p><b>Kategorie:</b> $Kategorie</p><p><b>Name:</b> $Name</p>";
									} 
									else // nicht eingeloggt
									{
										echo "<a href='http://web.btz-jena.de/wordpress/wp-login.php'><h3 style='color:blue'>Hier anmelden zum Essenbestellen.</h3></a>";
									}
								}
								else // Säulenseite => Kategorie & Namen aus dropdown auswählen
								{
									// Kategorie-Auswahl
									echo "<p><b>Kategorie:</b> <select id='Kategorie' name='Kategorie'>
										<option></option>
										<option>AEAP</option>
										<option>Mitarbeiter</option>
										<option>Teilnehmer</option>
									</select> </p>";
									// Namen-Auswahl
									echo '<p><b>Name:</b> ';
									for ( $i = 0; $i < 3; $i++)
									{
										echo "<select id='Eingabe_".Variablen::$Kateg_liste[$i]."' name='Eingabe_".Variablen::$Kateg_liste[$i]."' class='hidden_name'>
											<option></option>";
										foreach ($master_liste[$i][0] as $key)
										{
											echo (0==$i)?"<option>".$key."</option>":"<option value='".$key."'>". substr($key, 0, (strpos($key, ', ')+3)) .".</option>";
										}
										echo '</select>';
									}
									echo '</p>';
								}
								?>
							</td>
							<td  width='20%'></td>
							<td>
								<b>
									Zum Bestellen:
									<ol>
										<?php echo ( 'teilnehmer' != $modus )?'<li>Kategorie auswählen</li><li>Namen auswählen</li>':'<li>Anmelden</li>'; ?>
										<li>Essensauswahl treffen</li>
										<li>"Bestellen" drücken</li>
									</ol>
								</b>
							</td>
						</tr>
					</thead>
				</table>
		<?php
		if ( $logged_in ) // Nutzer ist eingeloggt oder auf der Säulenseite
		{
			?>
				<div>
					<input type="submit" name="Bestellung" value="Bestellen" />
				</div>
			<?php
		}
		
		// beendet output-buffering und übergibt Buffer an $vortext für Ausgabe
		$vortext = ob_get_clean();
		
		// beginnt output-buffering
		ob_start();
					
		switch ($Flag_Woche)
		{
			case 'laufende':
				echo "<h3>Speiseplan der laufenden Woche:</h3>";
				break;
			case 'kommende':
				echo "<h3>Speiseplan der kommenden Woche:</h3>";
				break;
			case 'ueber':
				echo "<h3>Speiseplan der übernächsten Woche:</h3>";
				break;
		}
			?>
				<div>
					<div class="speisekarte-tabelle aktive-tabelle">
						<table>
							<thead>
								<tr>
									<th><?php echo Woche_Ausgabe($Flag_Woche); ?></th>
									<?php for ( $i = 0; $i < 5; $i += 1)
											{
												echo "<th>".Variablen::$Tag_name[$i]."</th>";
											}
									?>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td>Vollkost</td>
									<?php
									for ( $i = 0; $i < 5; $i++)
									{
										echo ($i%2)?("<td>"):("<td class='even'>");
										if ( $i >= $heute ) // Bei Nachbestellung für die laufende Woche Auswahl für vergangene Tage _nicht_ anzeigen 
										{
											if ( Kueche_offen($KW) && 0 == Kueche_offen($KW, Variablen::$Tag_kurz[$i]) )
											{
												echo "<input type='radio' id='".Variablen::$kurz_essen[(2*$i)]."' name='".Variablen::$Tag_name[$i]."' value='1'><label for='".Variablen::$kurz_essen[(2*$i)]."'>".Speisekarte_Ausgabe( Variablen::$kurz_essen[(2*$i)], $KW )."</label>";
											} else echo Speisekarte_Ausgabe( Variablen::$kurz_essen[(2*$i)], $KW );
										}
										echo '</td>';
									}
									?>
								</tr>
								<tr>
									<td>Vegetarisch</td>
									<?php 
									for ( $i = 0; $i < 5; $i++)
									{
										echo ($i%2)?("<td>"):("<td class='even'>");
										if ( $i >= $heute ) // Bei Nachbestellung für die laufende Woche Auswahl für vergangene Tage _nicht_ anzeigen 
										{
											if ( Kueche_offen($KW) && 0 == Kueche_offen($KW, Variablen::$Tag_kurz[$i]) )
											{
												echo "<input type='radio' id='".Variablen::$kurz_essen[(2*$i+1)]."' name='".Variablen::$Tag_name[$i]."' value='-1'><label for='".Variablen::$kurz_essen[(2*$i+1)]."'>".Speisekarte_Ausgabe( Variablen::$kurz_essen[(2*$i+1)], $KW )."</label>";
											} else echo Speisekarte_Ausgabe( Variablen::$kurz_essen[(2*$i+1)], $KW );
										}
										echo '</td>';
									}
									?>
								</tr>
								<tr>
									<td></td>
									<?php for ( $i = 0; $i < 5; $i++)
											{
												echo ($i%2)?("<td>"):("<td class='even'>");
												if ( $i >= $heute ) // Bei Nachbestellung für die laufende Woche Auswahl für vergangene Tage _nicht_ anzeigen
												{
													echo "<input type='radio' id='".Variablen::$kurz_essen[(10+$i)]."' name='".Variablen::$Tag_name[$i]."' value='0' ".(('nach'!=$modus)?("checked"):(""))."><label for='".Variablen::$kurz_essen[(10+$i)]."'>Nichts</label>";
												}
												echo "</td>";
											}
										?>
								</tr>
								<?php if ('nach' == $modus) {
									echo '<tr><td></td>';
									
									for ( $i = 0; $i < 5; $i++)
									{
										echo ($i%2)?("<td>"):("<td class='even'>");
										echo "<input type='radio' id='".Variablen::$Tag_kurz[$i]."_noedit' name='".Variablen::$Tag_name[$i]."' value='100' checked><label for='".Variablen::$Tag_kurz[$i]."_noedit'>keine Änderung</label></td>";
									}
									echo '</tr>';
								}
								?>
							</tbody>
						</table>
					</div>
					<?php
					// Bei Nachbestellung Wochenauswahl weiterreichen
					if('nach' == $modus) echo "<div class='hidden'><select id='Kalenderwoche' name='Kalenderwoche'><option>".$KW."</option></select></div>";
					
					if ( $logged_in ) // Nutzer ist eingeloggt oder auf der Säulenseite
					{
						?>
						<div>
							<input type="submit" name="Bestellung" value="Bestellen" />
						</div>
						<?php
					}
					?>
				</div>
			</form>
			
			<?php
		
		// beendet output-buffering und übergibt Buffer an $formular für Ausgabe
		$formular = ob_get_clean();
	}
	
	// Bestellung wurde gedrückt
	if ( isset($_POST['Bestellung']) )
	{
		// Überprüfung ob Kategorie & Name ausgewählt wurden / nicht nötig für $modus=teilnehmer
		if ( 'teilnehmer' != $modus && ( !isset($_POST['Kategorie']) || !(isset($_POST['Eingabe_'.$_POST['Kategorie']]) )) )
		{
			$formular .= "<h1>Bitte Kategorie und Namen auswählen.</h1>";
		}
		else
		{
			// für Nachbestellung $KW übernehmen
			if ( 'nach' == $modus ) $KW = $_POST['Kalenderwoche'];
			
			// Bestellliste parat legen
			$table = Tabelle_Erstellen('bestellung', $KW);
			
			// Formulareinträge übernehmen
			if ( 'teilnehmer' == $modus )
			{
				$Eintrag = array( 'Kategorie' => $Kategorie, 'Name' => $Name);
			}
			else
			{
				$Eintrag = array( );
				$Eintrag['Kategorie'] = ( 'nach' == $modus ) ? 'Nachbestellt' : $_POST['Kategorie'];
				$Eintrag['Name'] = $_POST['Eingabe_'.$_POST['Kategorie']];
			}
			
			if ( 'nach' == $modus )
			{
				// Für Nachbestellung: Test ob 'normale' Bestellung vorhanden
				$normal_test = "SELECT * FROM ".$table." WHERE Name = '".$Eintrag['Name']."' AND Kategorie = '".$_POST['Kategorie']."'";
				$normal_bestellt = $wpdb->get_row( $normal_test, 'ARRAY_A');
				// Für Nachbestellung: Test ob bereits einmal nachbestellt
				$nach_test = "SELECT * FROM ".$table." WHERE Name = '".$Eintrag['Name']."' AND Kategorie = '".$Eintrag['Kategorie']."'";
				$nach_bestellt = $wpdb->get_row( $nach_test, 'ARRAY_A');
				for ( $i=0; $i < 5; $i++)
				{
					if ( '100' != $_POST[Variablen::$Tag_name[$i]] ) {
						$Eintrag[Variablen::$kurz_essen[(2*$i)]] = ( 0 < $_POST[Variablen::$Tag_name[$i]]) ? 1:0;
						$Eintrag[Variablen::$kurz_essen[(2*$i+1)]] = ( 0 > $_POST[Variablen::$Tag_name[$i]]) ? 1:0;
					}
					else if ( $normal_bestellt )
					{
						$Eintrag[Variablen::$kurz_essen[(2*$i)]] = $normal_bestellt[''.Variablen::$kurz_essen[(2*$i)]];
						$Eintrag[Variablen::$kurz_essen[(2*$i+1)]] = $normal_bestellt[''.Variablen::$kurz_essen[(2*$i+1)]];
					}
					else if ( $nach_bestellt )
					{
						$Eintrag[Variablen::$kurz_essen[(2*$i)]] = $nach_bestellt[''.Variablen::$kurz_essen[(2*$i)]];
						$Eintrag[Variablen::$kurz_essen[(2*$i+1)]] = $nach_bestellt[''.Variablen::$kurz_essen[(2*$i+1)]];
					}
					else
					{
						$Eintrag[Variablen::$kurz_essen[(2*$i)]] = 0;
						$Eintrag[Variablen::$kurz_essen[(2*$i+1)]] = 0;
					}
				}
			}
			else
			{
				for ( $i=0; $i < 5; $i++)
				{
					$Eintrag[Variablen::$kurz_essen[(2*$i)]] = ( 0 < $_POST[Variablen::$Tag_name[$i]]) ? 1:0;
					$Eintrag[Variablen::$kurz_essen[(2*$i+1)]] = ( 0 > $_POST[Variablen::$Tag_name[$i]]) ? 1:0;
				}
			}
			$Eintrag['Datum'] = date('H:i d.m.');
			
			// Test ob schon bestellt
			$bestellt_test = "SELECT id FROM ".$table." WHERE Name = '".$Eintrag['Name']."' AND Kategorie = '".$Eintrag['Kategorie']."'";
			$schon_bestellt = $wpdb->get_row( $bestellt_test, 'ARRAY_A');
			
			// Bestellung neu eintragen oder aktualisieren
			if ( !$schon_bestellt ) // vorher noch nicht bestellt
			{
				$Flag_Bestellung = $wpdb->insert( $table, $Eintrag );
			}
			else // vorher bereits bestellt
			{
				$Eintrag['id'] = $schon_bestellt['id'];
				$Flag_Bestellung = $wpdb->replace( $table, $Eintrag );
			}
			
			// Für Nachbestellung: falls 'normale' Bestellung vorhanden, diese löschen
			if ( 'nach' == $modus && $normal_bestellt )
			{
				$wpdb->delete( $table, array( 'id' => $normal_bestellt['id'] ) );
			}
			
			if ( false !== $Flag_Bestellung ) // $Eintrag wurde erfolgreich in $table eingetragen
			{
				if ( 'teilnehmer' != $modus )
				{
					for ( $i = 0; $i < 3; $i++)
					{
						if ( Variablen::$Kateg_liste[$i] == $_POST['Kategorie'] )
						{
							$Email = $master_liste[$i][1][array_search( $Eintrag['Name'], $master_liste[$i][0])];
							break;
						}
					}
				}
				
				if ( Bestellung_Email( $Email, $modus, $KW, $Eintrag ) )
				{
					$formular .= '<h3 id="Erfolg-Text">Ihre Bestellung für KW '.$KW.' wurde gespeichert und eine Bestätigungsemail wurde verschickt.</h1>';
				}
				else
				{
					$formular .= '<h3 id="Erfolg-Text">Ihre Bestellung für KW '.$KW.' wurde gespeichert.</h1>';
				}
			}
			else // insert/replace hat nicht funktioniert
			{
				$formular .= '<h1>Es gab ein Problem bei der Bestellung.</h1>';
			}
		}
	}
	
	if ( 'nach' != $modus ) // Speisekarten für Teilnehmer und Säule vor und nach Formular einfügen (je nach Woche)
	{
		if ( 'kommende' == $Flag_Woche )
		{
			$html = $vortext . Speisekarte_Anzeige('laufende') . $formular . Speisekarte_Anzeige('ueber') . Disclaimer();
		} else $html = $vortext . Speisekarte_Anzeige('laufende'). Speisekarte_Anzeige('kommende') . $formular . Disclaimer();
	} else $html = $nach_auswahl . $vortext . $formular;
	
	return $html;
}

// Disclaimer zur Anzeige unter dem Bestellformular
function Disclaimer () {
	// beginnt output-buffering
	ob_start();
	
	{
		{
		?>
			<h4>
				Änderungen sind vorbehalten.<br>
				Bei Fragen zu Allergien und Ähnlichem, bitte an die Küche wenden.<br>
				Eine Liste der Zusatzstoffe hängt im Speisesaal aus und ist auf der internen Webseite unter "Informationen" zu finden.
			</h4>
		<?php
		}
	}
	
	
	// beendet output-buffering und übergibt Buffer an $formular für Ausgabe
	$html = ob_get_clean();
	
	return $html;
}

// Bestätigungsemail für (Nach-)Bestellung abschicken
function Bestellung_Email ( $mail, $modus, $KW, $Eintrag ) { // Emailaddresse, Nachbestellung oder nicht, Bestellungsdaten für die Tabelle
	if ( "" == $mail ) // keine Addresse im System
	{
		$flag = false;
	}
	else
	{
		include( 'variablen.php');
		
		$betreff = ( 'nach' == $modus ) ? 'Nachbestellung für KW '.$KW : 'Bestellung für KW '.$KW;
		
		$header[] = 'MIME-Version: 1.0';
		$header[] = 'Content-type: text/html;';
		$header[] = 'From: Essensbestellung <Essensbestellung@btz-bewegt.de>';
		
		$text = '
		<html>
			<head>
				<title>'.$betreff.'</title>
			</head>
			<body>
				<p>'.$betreff.'</p>
				<table>
					<thead>
						<th width="100" align="left">Wochentag</th>
						<th width="100" align="left">Auswahl</th>
						<th align="left">Gericht</th>
					</thead>
					<tbody>
					';
		for ( $i = 0; $i < 5; $i++)
		{
			// Variablen::$Tag_name | ( Vollkost? JA: (Vollkost | Gericht) / NEIN: (Vegetarisch? JA: (Vegetarisch | Gericht) / NEIN: Kein Essen bestellt.) )
			$text .= '
			<tr>
				<td>'.Variablen::$Tag_name[$i].'</td>'
				.( (0<$Eintrag[Variablen::$kurz_essen[(2*$i)]])?('<td>Vollkost</td><td>'.Speisekarte_Ausgabe(Variablen::$kurz_essen[(2*$i)], $KW).'</td>'):( (0<$Eintrag[Variablen::$kurz_essen[(2*$i+1)]])?('<td>Vegetarisch</td><td>'.Speisekarte_Ausgabe(Variablen::$kurz_essen[(2*$i+1)], $KW).'</td>'):("<td colspan='2'>Kein Essen bestellt.</td>") ) ).
			'</tr>';
		}
		$text .= '
					</tbody>
				</table>	
			</body>
		</html>
		';
		
		$flag = wp_mail( $mail, $betreff, $text, $header );
	}
	return $flag;
}

// gibt Kalenderwoche und Datum von Montag bis Freitag aus
function Woche_Ausgabe ( $woche ) { // $woche = laufende/kommende/ueber
	$heute = date('w');
	
	switch ($woche)
	{
		case 'laufende':
			$KW = date('W');
			
			switch ($heute)
			{
				case 1: // Montag
					$Montag = date('d.m.');
					$Freitag = date('d.m.', strtotime('Friday'));
					break;
				case 2: // Dienstag
				case 3: // Mittwoch
				case 4: // Donnerstag
					$Montag = date('d.m.', strtotime('last Monday'));
					$Freitag = date('d.m.', strtotime('Friday'));
					break;
				case 5: // Freitag
					$Montag = date('d.m.', strtotime('last Monday'));
					$Freitag = date('d.m.', strtotime('Friday'));
					break;
				default: // Wochenende
					$Montag = date('d.m.', strtotime('Monday'));
					$Freitag = date('d.m.', strtotime('Friday'));
			}
			break;
		case 'kommende':
			$KW = date('W', strtotime('+1 week'));
			
			switch ($heute)
			{
				case 1: // Montag
					$Montag = date('d.m.', strtotime('Monday +1 week'));
					$Freitag = date('d.m.', strtotime('Friday +1 week'));
					break;
				case 2: // Dienstag
				case 3: // Mittwoch
				case 4: // Donnerstag
					$Montag = date('d.m.', strtotime('Monday'));
					$Freitag = date('d.m.', strtotime('Friday +1 week'));
					break;
				case 5: // Freitag
					$Montag = date('d.m.', strtotime('Monday'));
					$Freitag = date('d.m.', strtotime('Friday +1 week'));
					break;
				default: // Wochenende
					$Montag = date('d.m.', strtotime('Monday'));
					$Freitag = date('d.m.', strtotime('Friday'));
			}
			break;
		case 'ueber':
			$KW = date('W', strtotime('+2 week'));
			
			switch ($heute)
			{
				case 1: // Montag
					$Montag = date('d.m.', strtotime('Monday +2 week'));
					$Freitag = date('d.m.', strtotime('Friday +2 week'));
					break;
				case 2: // Dienstag
				case 3: // Mittwoch
				case 4: // Donnerstag
					$Montag = date('d.m.', strtotime('Monday +1 week'));
					$Freitag = date('d.m.', strtotime('Friday +2 week'));
					break;
				case 5: // Freitag
					$Montag = date('d.m.', strtotime('Monday +1 week'));
					$Freitag = date('d.m.', strtotime('Friday +2 week'));
					break;
				default: // Wochenende
					$Montag = date('d.m.', strtotime('Monday +1 week'));
					$Freitag = date('d.m.', strtotime('Friday +1 week'));
			}
			break;
        default:
            $KW      = "ERROR";
            $Montag  = "ERROR";
            $Freitag = "ERROR";
	}
	
	$html = "KW $KW: $Montag ~ $Freitag";
	return $html;
}

// Erstellt SQL-Tabelle für wp_speiseplan oder wp_bestellliste_KW_$KW falls noch nicht vorhanden und gibt Tabellen namen zurück
function Tabelle_Erstellen ( $modus = 'speiseplan', $KW = 0) { // $modus = 'speiseplan' oder 'bestellung'
	global $wpdb;
	
	$charset_collate = $wpdb->get_charset_collate();

	if ( 'bestellung' == $modus)
	{
		$table = $wpdb->prefix . "bestellliste_KW_" . $KW;
		
		$SQL = "CREATE TABLE IF NOT EXISTS $table (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				Kategorie text NOT NULL,
				Name text NOT NULL,
				Mo_voll mediumint(9) NOT NULL,
				Mo_veg mediumint(9) NOT NULL,
				Di_voll mediumint(9) NOT NULL,
				Di_veg mediumint(9) NOT NULL,
				Mi_voll mediumint(9) NOT NULL,
				Mi_veg mediumint(9) NOT NULL,
				Do_voll mediumint(9) NOT NULL,
				Do_veg mediumint(9) NOT NULL,
				Fr_voll mediumint(9) NOT NULL,
				Fr_veg mediumint(9) NOT NULL,
				Montag mediumint(9) NOT NULL,
				Dienstag mediumint(9) NOT NULL,
				Mittwoch mediumint(9) NOT NULL,
				Donnerstag mediumint(9) NOT NULL,
				Freitag mediumint(9) NOT NULL,
				Datum text NOT NULL,
				Flag_neu text NOT NULL,
				UNIQUE (id)
			) $charset_collate;";
	}
	else 
	{
		$table = $wpdb->prefix . "speiseplan";
		
		$SQL = "CREATE TABLE IF NOT EXISTS $table (
				KW mediumint(9) NOT NULL,
				Mo_voll text NOT NULL,
				Mo_veg text NOT NULL,
				Mo_voll_num text NOT NULL,
				Mo_veg_num text NOT NULL,
				Di_voll text NOT NULL,
				Di_veg text NOT NULL,
				Di_voll_num text NOT NULL,
				Di_veg_num text NOT NULL,
				Mi_voll text NOT NULL,
				Mi_veg text NOT NULL,
				Mi_voll_num text NOT NULL,
				Mi_veg_num text NOT NULL,
				Do_voll text NOT NULL,
				Do_veg text NOT NULL,
				Do_voll_num text NOT NULL,
				Do_veg_num text NOT NULL,
				Fr_voll text NOT NULL,
				Fr_veg text NOT NULL,
				Fr_voll_num text NOT NULL,
				Fr_veg_num text NOT NULL,
				Mo_geschlossen mediumint(9) NOT NULL,
				Di_geschlossen mediumint(9) NOT NULL,
				Mi_geschlossen mediumint(9) NOT NULL,
				Do_geschlossen mediumint(9) NOT NULL,
				Fr_geschlossen mediumint(9) NOT NULL,
				geschlossen mediumint(9) NOT NULL,
				UNIQUE (KW)
			) $charset_collate;";
	}
	
//	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	require_once( $_SERVER["DOCUMENT_ROOT"] . 'wp-admin/includes/upgrade.php' );
	dbDelta( $SQL );
	
	return $table;
}

// Überprüft ob für die $KW im Speiseplan 'geschlossen' gesetzt ist / returns true für offen, false für geschlossen
// falls $Tag angegeben ist, wird stattdessen '$Tag_geschlossen' überprüft und returned
function Kueche_offen ( $KW, $Tag='' ) { // $Tag = Mo, Di, ...
	global $wpdb;
	$table = Tabelle_Erstellen();
	
	$KW = intval($KW);
	$status = $wpdb->get_row( "SELECT * FROM $table WHERE KW = '$KW'", ARRAY_A);
	
	if ( '' == $Tag )
	{
		if ( 1 == $status['geschlossen']) $flag = false;
		else $flag = true;
	}
	else $flag = $status[$Tag.'_geschlossen'];
	
	return $flag;
	
}
