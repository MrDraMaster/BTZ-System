<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
/* Funktionen in diesem .php:
 * 
 * function Neue_AEAP_Runde ()
 * function AEAP_Sammel ()
 * function AEAP_Bestellung ( $table, $Nummer, $modus = 'standard', $Mi=0, $Do=0, $Fr=0 )
 * 
 * 
 */

// Standardbestellung für neue AEAP Runde [Mo&Di Veg. für $KW]
function Neue_AEAP_Runde () {
	// beginnt output-buffering
	ob_start();
	{
	?>
	<div>
		<div>
			<h3 id='standard'>Für wie viele neue AEAP-Teilnehmer soll in welcher Kalenderwoche für Montag und Dienstag Vegetarisch bestellt werden?</h3>
		</div>
		<form action="#standard" method="post">
			<table>
				<thead>
					<tr>
						<th>
                            <label>
                                Kalenderwoche:
                                <input type="text" name="KW" value="">
                            </label>
                        </th>
						<th>
                            <label>
                                Anzahl neue AEAP:
                                <input type="text" name="ANZ" value="">
                            </label>
                        </th>
						<th><input type="submit" name="Standard" value="AEAP Standardbestellung"></th>
					</tr>
				</thead>
			</table>
		</form>
	</div>
	<?php
	}
	// beendet output-buffering und übergibt Buffer an $html für Ausgabe
	$html = ob_get_clean();
	
	// Button wurde gedrückt
	if ( isset($_POST['Standard']) )
	{
		if ( isset($_POST['KW']) && isset($_POST['ANZ']) ) // Felder ausgefüllt
		{
			if ( is_numeric($_POST['KW']) && is_numeric($_POST['ANZ']) ) // Felder mit Zahlen ausgefüllt
			{
				$KW = intval($_POST['KW']);
				$KW = ($KW < 10)?('0'.$KW):($KW);
				$ANZ = intval($_POST['ANZ']);
				
				$table = Tabelle_Erstellen('bestellung', $KW);
				$html .= ( AEAP_Bestellung( $table, $ANZ ) )?('Standardbestellung für '.$ANZ.' AEAP Teilnehmer in KW '.$KW.' erfolgreich gespeichert.'):('Fehler bei der Standardbestellung.');
				$html .= '<br>';
			}
			else $html .= '<p>Bitte beide Felder richtig ausfüllen.</p>';
		}
		else $html .= '<p>Bitte beide Felder richtig ausfüllen.</p>';
	}
	
	return $html;
}

// Sammelbestellung für neue AEAP Runde [Tabelle für Mi, Do, Fr] für die laufende Woche
function AEAP_Sammel () {
	include( 'variablen.php');
	$KW = date('W');
	
	$html = Speisekarte_Anzeige('laufende');
	
	$ANZ = count(LDAP_Abfrage('AEAP')[0]);
	
	if ( !isset($_POST['Sammel']) ) // Button nicht gedrückt
	{
		// beginnt output-buffering
		ob_start();
		
		?>
		<div>
			<div>
				<h3 id='sammel'>Sammelbestellung für AEAP für die laufenden Woche</h3>
				<p><b>
					Für jede AEAP Nummer das passende Essen auswählen.<br>
					AEAP Nummern von Teilnehmern die verlängern müssen als "verlängert" markiert werden oder deren selbständig ausgefüllten Bestellungen werden überschrieben. 
				</b></p>
			</div>
			<form action="#sammel" method="post">
				<table>
					<thead>
						<tr>
							<th>Nummer</th>
							<th>Mi</th>
							<th>Do</th>
							<th>Fr</th>
						</tr>
					</thead>
					<tbody>
						<?php
						for ( $i = 1; $i <= $ANZ; $i++ )
						{
							echo '<tr><td>AEAP '.$i.'<br>
								<input type="checkbox" id="'.$i.'_extended" name="'.$i.'_extended" value=1>
								<label for="'.$i.'_extended">verlängert</label>
								</td>';
							for ( $j = 2; $j < 5; $j++ )
							{
								echo "<td><fieldset>
									<input type='radio' id='".$i.'_'.Variablen::$kurz_essen[(2*$j)]."' name='".$i.'_'.Variablen::$Tag_name[$j]."' value='1'>
									<label for='".$i.'_'.Variablen::$kurz_essen[(2*$j)]."'>Vollkost</label><br>
									<input type='radio' id='".$i.'_'.Variablen::$kurz_essen[(2*$j+1)]."' name='".$i.'_'.Variablen::$Tag_name[$j]."' value='-1'>
									<label for='".$i.'_'.Variablen::$kurz_essen[(2*$j+1)]."'>Vegetarisch</label><br>
									<input type='radio' id='".$i.'_'.Variablen::$Tag_kurz[$j].'_nix'."' name='".$i.'_'.Variablen::$Tag_name[$j]."' value='0' checked>
									<label for='".$i.'_'.Variablen::$Tag_kurz[$j].'_nix'."'>Nichts</label>
								</fieldset></td>";
							}
							echo '</tr>';
						}
						?>
					</tbody>
				</table>
				<input type="submit" name="Sammel" value="AEAP Sammelbestellung">
			</form>
		</div>
		<?php
		
		// beendet output-buffering und übergibt Buffer an $html für Ausgabe
		$html .= ob_get_clean();
	}
	else
	{
		$table = Tabelle_Erstellen('bestellung',$KW);
		for ( $i = 1; $i <= $ANZ; $i++ )
		{
// if für $i_extended zum überspringen
			if ( 1 == $_POST[$i.'_extended'] ) $html .= 'AEAP '.$i.' wurde übersprungen da verlängert.';
			else $html .= ( AEAP_Bestellung( $table, $i, 'sammel', $_POST[$i.'_Mittwoch'], $_POST[$i.'_Donnerstag'], $_POST[$i.'_Freitag'] ) )?('Bestellung für AEAP '.$i.' für laufende Woche erfolgreich gespeichert.'):('Fehler bei AEAP '.$i);
			$html .= '<br>';
		}
	}
	return $html;
}

// standard: trägt für Neue AEAP $Nummer veg Essen an Mo&Di in $table ein
// sammel: trägt für AEAP $Nummer die Bestellung in $table ein
function AEAP_Bestellung ( $table, $Nummer, $modus = 'standard', $Mi=0, $Do=0, $Fr=0 ) { // $modus = 'standard'/'sammel'
	global $wpdb;
	include( 'variablen.php');
	
	if ( 'sammel' == $modus ) // Sammelbestellung
	{
		$Name = 'AEAP '.$Nummer;
		$Eintrag = array( 'Kategorie' => 'AEAP', 'Name' => $Name);
		
		if ( 0 < $Mi ) { $Eintrag['Mi_voll']=1; } elseif ( 0 > $Mi ) { $Eintrag['Mi_veg']=1; }
		if ( 0 < $Do ) { $Eintrag['Do_voll']=1; } elseif ( 0 > $Do ) { $Eintrag['Do_veg']=1; }
		if ( 0 < $Fr ) { $Eintrag['Fr_voll']=1; } elseif ( 0 > $Fr ) { $Eintrag['Fr_veg']=1; }
		$Eintrag['Datum'] = date('H:i d.m.');
		
		//$flag = $wpdb->update( $table, $Eintrag, array('Name' => 'AEAP '.$Nummer) );
		//$flag = $wpdb->replace( $table, $Eintrag );
		
		
		// Test ob schon bestellt
		$bestellt_test = "SELECT id FROM ".$table." WHERE Name = '".$Name."'";
		$schon_bestellt = $wpdb->get_row( $bestellt_test, 'ARRAY_A');
			
		
		// Bestellung neu eintragen oder aktualisieren
		if ( !$schon_bestellt ) // vorher noch nicht bestellt
			{
				$flag = $wpdb->insert( $table, $Eintrag );
			}
		else // vorher bereits bestellt
			{
				$Eintrag['id'] = $schon_bestellt['id'];
				$flag = $wpdb->replace( $table, $Eintrag );
			}
	}
	else // Standardbestellung
	{
		$Name = 'Neue AEAP';
		$Eintrag = array( 'Kategorie' => 'AEAP',
						'Name' => $Name,
						'Mo_voll' => 0,
						'Mo_veg' => $Nummer,
						'Di_voll' => 0,
						'Di_veg' => $Nummer,);
		for ( $i = 4; $i < 10; $i++)
		{
			$Eintrag[Variablen::$kurz_essen[$i]] = 0;
		}
		// Test ob 'Neue AEAP' schon vorhanden [erlaubt Änderungen]
		$bestellt_test = "SELECT id FROM ".$table." WHERE Name = '".$Name."'";
		$schon_bestellt = $wpdb->get_row( $bestellt_test, 'ARRAY_A');
		if ( $schon_bestellt ) $Eintrag['id'] = $schon_bestellt['id'];
		$Eintrag['Datum'] = date('H:i d.m.');
		
		$flag = $wpdb->replace($table, $Eintrag);
	}
	
	return $flag;
}
