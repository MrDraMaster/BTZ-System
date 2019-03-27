<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
/* Funktionen in diesem .php:
 *
 * function Neue_Teilnehmer_Runde ( $modus )
 * function Teilnehmer_Sammel ( $modus )
 * Teilnehmer_Bestellung ( $table, $kategorie, $Name, $modus = 'standard', $Do=0, $Fr=0 )
 * 
 * 
 */

// Standardbestellung für neue Teilnehmer Runde [Mo nichts & Di&Mi Veg. für $KW]
function Neue_Teilnehmer_Runde ( $modus ) { // $modus = Auster, BT, BVB
	// beginnt output-buffering
	ob_start();
	{
	?>
	<div>
		<div>
			<h3 id='standard'>Für wieviele neue <?php echo $modus; ?>-Teilnehmer soll in welcher Kalenderwoche für Montag nichts und Dienstag und Mittwoch Vegetarisch bestellt werden?</h3>
		</div>
		<form action="#standard" method="post">
			<table>
				<thead>
					<tr>
						<th>Kalenderwoche:</th>
						<th><input type="text" name="KW" value=""></th>
						<th>Anzahl neuer Teilnehmer:</th>
						<th><input type="text" name="ANZ" value=""></th>
						<th><input type="submit" name="Standard" value="Teilnehmer Standardbestellung"></th>
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
				if ($KW < 10) $KW = '0'.$KW;
				$ANZ = intval($_POST['ANZ']);
				
				// beginnt output-buffering
				ob_start();

				?>
				<div>
					<form action="#standard" method="post">
						<table>
							<p>
								Hier die Namen eintragen.<br>
								Format: "Nachname, Vorname"
							</p>
							<thead>
								<tr>
									<th>Namen:</th>
								</tr>
							</thead>
							<tbody>
								<?
								for ( $i=0; $i<$ANZ; $i++)
								{
								?>
								
								<tr><td><label>
                                            <input type="text" name="Name_neu_<?php echo $i; ?>" value="">
                                        </label></td></tr>
								
								<?
								}
								?>
							</tbody>
						</table>
						<div class='hidden'>
                            <label>
                                <select name="Kalenderwoche"><option><?php echo $KW;?></option></select>
                            </label>
                            <label>
                                <select name="Anzahl"><option><?php echo $ANZ;?></option></select>
                            </label>
                        </div>
						<input type="submit" name="Bestellung" value="Teilnehmer Standardbestellung">
					</form>
				</div>
				<?php

				// beendet output-buffering und übergibt Buffer an $html für Ausgabe
				$html .= ob_get_clean();
			}
			else $html .= '<p>Bitte beide Felder richtig ausfüllen.</p>';
		}
		else $html .= '<p>Bitte beide Felder richtig ausfüllen.</p>';
	}
	
	if ( isset($_POST['Bestellung']) ) // Standardbestellung abgeschickt
	{
		$KW = intval($_POST['Kalenderwoche']);
		$KW = ($KW < 10)?('0'.$KW):($KW);
		$table = Tabelle_Erstellen('bestellung',$KW);
		$ANZ = intval($_POST['Anzahl']);
		$teilnehmer_liste = Teilnehmer_liste();
		
		for ( $i=0;$i<$ANZ;$i++ )
		{
			if ( '' != $_POST['Name_neu_'.$i] )
			{
				if ( false !== array_search( $_POST['Name_neu_'.$i], $teilnehmer_liste[0] ) )
				{
					$html .= ( Teilnehmer_Bestellung( $table, $modus, $_POST['Name_neu_'.$i], 'standard' ) )?('Standardbestellung für '.$_POST['Name_neu_'.$i].' für KW '.$KW.' erfolgreich gespeichert.'):('Fehler bei '.$_POST['Name_neu_'.$i]);
					$html .= '<br>';
				}
				else $html .= '"'.$_POST['Name_neu_'.$i].'" ist nicht in der Datenbank oder wurde falsch geschrieben.<br>';
			}
		}
	}
	
	return $html;
}

// Sammelbestellung für neue Teilnehmer Runde [Tabelle für Mi, Do, Fr] für die laufende Woche
function Teilnehmer_Sammel ( $modus ) { // $modus = Auster, BT, BVB
	global $wpdb;
	$KW = date('W');
	$table = Tabelle_Erstellen('bestellung', $KW);
	
	$neue_teilnehmer = $wpdb->get_results("SELECT * FROM ".$table." WHERE Flag_neu = '".$modus."' ORDER BY Name ASC", ARRAY_A);
	
	$html = Speisekarte_Anzeige('laufende');
	
	$html .= '<h3 id="sammel">Sammelbestellung für neue '.$modus.'-Teilnehmer für die laufenden Woche</h3>';
	
	if ( !isset($_POST['Sammel']) ) // Button nicht gedrückt
	{
		// beginnt output-buffering
		ob_start();
		
		?>
		<div>
			<div>
				<p><b>
					Für die Teilnehmer, für die bestellt werden soll, die Checkbox "bestellen" markieren und dann das jeweilige Essen auswählen.
				</b></p>
			</div>
			<form action="#sammel" method="post">
				<table>
					<thead>
						<tr>
							<th>Name</th>
							<th>Do</th>
							<th>Fr</th>
						</tr>
					</thead>
					<tbody>
						<?php
						for ( $i = 0; $i < count($neue_teilnehmer); $i++ )
						{
							echo '<tr><td>Teilnehmer '.$neue_teilnehmer[$i]['Name'].'<br>
								<input type="checkbox" id="'.$i.'_bestellen" name="'.$i.'_bestellen" value=1>
								<label for="'.$i.'_bestellen">bestellen</label>
								</td>';
							for ( $j = 3; $j < 5; $j++ )
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
				<input type="submit" name="Sammel" value="Teilnehmer Sammelbestellung">
			</form>
		</div>
		<?php
		
		// beendet output-buffering und übergibt Buffer an $html für Ausgabe
		$html .= ob_get_clean();
	}
	else
	{
		for ( $i = 0; $i < count($neue_teilnehmer); $i++ )
		{
			if ( $_POST[$i.'_bestellen'] )
			{
				$html .= ( Teilnehmer_Bestellung( $table, $modus, $neue_teilnehmer[$i]['Name'], 'sammel', $_POST[$i.'_Donnerstag'], $_POST[$i.'_Freitag'] ) )?('Bestellung für Teilnehmer '.$neue_teilnehmer[$i]['Name'].' für laufende Woche erfolgreich gespeichert.'):('Fehler bei Teilnehmer '.$neue_teilnehmer[$i]['Name']);
				$html .= '<br>';
			}
			else $html .= $neue_teilnehmer[$i]['Name'].' wurde übersprungen.<br>';
		}
	}
	return $html;
}

// standard: trägt für neue Teilnehmer veg Essen an Mo&Di in $table ein
// sammel: trägt für Teilnehmer die Bestellung in $table ein
function Teilnehmer_Bestellung ( $table, $kategorie, $Name, $modus = 'standard', $Do=0, $Fr=0 ) { // $modus = 'standard'/'sammel'
	global $wpdb;
	include( 'variablen.php');
	
	if ( 'sammel' == $modus ) // Sammelbestellung
	{
		$Eintrag = array( 'Kategorie' => 'Teilnehmer' );
		
		if ( 0 < $Do ) { $Eintrag['Do_voll']=1; } elseif ( 0 > $Do ) { $Eintrag['Do_veg']=1; }
		if ( 0 < $Fr ) { $Eintrag['Fr_voll']=1; } elseif ( 0 > $Fr ) { $Eintrag['Fr_veg']=1; }
		$Eintrag['Datum'] = date('H:i d.m.');
		
		$flag = $wpdb->update( $table, $Eintrag, array('Name' => $Name) );
	}
	else // Standardbestellung
	{
		$Eintrag = array( 'Kategorie' => 'Teilnehmer',
						'Name' => $Name,
						'Flag_neu' => $kategorie,
						'Mo_voll' => 0,
						'Mo_veg' => 0,
						'Di_voll' => 0,
						'Di_veg' => 1,
						'Mi_voll' => 0,
						'Mi_veg' => 1,);
		for ( $i = 6; $i < 10; $i++)
		{
			$Eintrag[Variablen::$kurz_essen[$i]] = 0;
		}
		// Test ob $Name schon vorhanden [erlaubt Änderungen]
		$bestellt_test = "SELECT id FROM ".$table." WHERE Name = '".$Name."'";
		$schon_bestellt = $wpdb->get_row( $bestellt_test, 'ARRAY_A');
		if ( $schon_bestellt ) $Eintrag['id'] = $schon_bestellt['id'];
		$Eintrag['Datum'] = date('H:i d.m.');
		
		$flag = $wpdb->replace($table, $Eintrag);
	}
	
	return $flag;
}
