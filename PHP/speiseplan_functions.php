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
 * function bestellliste_befehle_fct()
 * function Speiseplan_Eingabe()
 * function Speisekarte_Ausgabe( $spalte, $KW, $modus = 'standard' )
 * function Speiseplan_Anzeigen()
 * function speiseplan_export_fct()
 * function Kochplan_Aufruf_bestell_summe ()
 * function bestell_summe ( $KW )
 */

// Befehle zum Exportieren und Leeren der Bestellliste
function bestellliste_befehle_fct() {
	global $wpdb;
	
    // beginnt output-buffering
    ob_start();
	// Anleitung und KW-Wahl
    {
	?>
	<div>
		<div id='export'>
			"Bestellliste ausgeben" erzeugt einen Link über den man die Bestellliste der angegebenen Kalenderwoche herunterladen kann.
		</div>
		<form action="#export" method="post">
			<table><tbody><tr>
			<td width=15%>Kalenderwoche:</td>
			<td width=10%><input type="text" name="KW" value=""></td>
			<td width=5%></td>
			<td><input type="submit" name="Ausgabe" value="Bestellliste ausgeben"></td>
			</tr></tbody></table>
		</form>
	</div>
	<?php
	}
	// beendet output-buffering und übergibt Buffer an $html für Ausgabe
    $html = ob_get_clean();
    // "Bestellliste ausgeben" gedrückt
	if ( isset( $_POST["Ausgabe"]))
    {        
        if ( is_numeric($_POST["KW"]))
		{
			$KW = intval($_POST["KW"]);
			$KW = ($KW<10)?('0'.$KW):($KW);
			// Bestellliste parat legen
			$table = $wpdb->prefix . "bestellliste_KW_" . $KW;
			// SQL-Query
			$Anfrage = $wpdb->get_results("SELECT * FROM $table ORDER BY Kategorie ASC, Name ASC", 'ARRAY_A' );
			if ( NULL !== $Anfrage ) // SQL-Query hat funktioniert
			{
				// Dateiname und -pfad festlegen
				$Dateiname = 'Bestellliste_' . $KW;
				$Dateiverzeichnis = './Mittagessen/';
				if(!is_dir($Dateiverzeichnis)) // erstellt das Verzeichnis Mittagessen falls noch nicht vorhanden
				{
					mkdir($Dateiverzeichnis);
				}
				$Dateipfad = $Dateiverzeichnis . $Dateiname . '.csv';

				// Tabelle in string umwandeln und für .CSV formatieren
				// die Elemente in der Zeile mit Semikolon getrennt ausgeben, "\r\n" für den Zeilenumbruch in der .CSV
				$Tabellentext = "Kategorie; Name;";
				for ( $i = 0; $i < 10; $i++)
				{
					$Tabellentext .= Variablen::$kurz_essen[$i];
				}
				$Tabellentext .= "\r\n";
				for ( $i = 0; $i < count($Anfrage); $i++ ) // für jede Zeile der Tabelle
				{
					$Tabellentext .= $Anfrage[$i]['Kategorie'] . ';'
									. $Anfrage[$i]['Name'] . ';';
					for ( $j = 0; $j < 10; $j++)
					{
						$Tabellentext .= $Anfrage[$i][Variablen::$kurz_essen[$j]].";";
					}
					$Tabellentext .= "\r\n";
				}
				
				$Tabellentext = mb_convert_encoding($Tabellentext, 'Windows-1252');
				
				// Alte Datei-Version zur Sicherheit umbenennen
				if (file_exists($Dateipfad)) rename($Dateipfad, $Dateiverzeichnis . $Dateiname . '_alt' . '.csv');

				// Tabellentext in Datei speichern
				$Flag_file_put_contents = file_put_contents($Dateipfad, $Tabellentext);

				if ($Flag_file_put_contents)
				{
					// Link für Datei-Download
					if (file_exists($Dateipfad))
					{
						$html .= "<p><a href='./download.php?nummer=". $KW ."' target='_blank'>Diesen Link klicken und mit Excel öffnen, dann die Datei abspeichern.</a></p>";
					}
				}
				else $html .="<p>Fehler beim Ausgeben der Bestellliste.</p>";

			} else { $html .= "<p>SQL Anfrage misslungen.</p>"; }
		} else { $html .= "<p>Bitte Kalenderwoche angeben.</p>"; }
    }
	
	// outputs everything
    return $html;
    
}

// Formular für die Eingabe von Speiseplänen
function Speiseplan_Eingabe() {	
	global $wpdb;
    $table = Tabelle_Erstellen();
	
	// beginnt output-buffering
    ob_start();
	// Formular mit Eingabefeldern für Kalenderwoche und die Essen
    {
	?>
		<div>
			<div>
				<h3 id='speiseplan'>Eingabe Speiseplan:</h3>
				<b><ol>
					<li>Kalenderwoche eintragen</li>
					<li>Vollkost und Vegetarisch für jeden Tag eintragen</li>
					<li>Liste der Zusatzstoff-Nummern für jedes Essen eintragen</li>
					<li>bei Ausfällen den Grund für den Ausfall in das Vollkost Feld des Tages eintragen (Feiertag, etc.) und die entsprechende Option wählen ["keine Küche" = Küche geschlossen, Essen von extern / "kein Essen" = keine Essensausgabe]</li>
					<li>"Speiseplan speichern" drücken</li>
				</ol></b>
			</div>
			<form action="#speiseplan" method="post" id="speiseplan_eingabe" name="speiseplan_eingabe" autocomplete="off">
				<div>
					<table>
						<thead>
							<tr>
								<th>
									Kalenderwoche:<br>
									<input type="text" name="KW" value="">
								</th>
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
								<?php for ( $i = 0; $i < 10; $i += 2)
									{
										echo "<td><input type='text' name='".Variablen::$kurz_essen[$i]."'></td>";
									}
								?>
							</tr>
							<tr>
								<td>Zusatzstoffe:</td>
								<?php for ( $i = 0; $i < 10; $i += 2)
									{
										echo "<td><input type='text' name='".Variablen::$kurz_essen[$i]."'></td>";
									}
								?>
							</tr>
							<tr>
								<td>Vegetarisch</td>
								<?php for ( $i = 1; $i < 10; $i += 2)
									{
										echo "<td><input type='text' name='".Variablen::$kurz_essen[$i]."'></td>";
									}
								?>
							</tr>
							<tr>
								<td>Zusatzstoffe:</td>
								<?php for ( $i = 1; $i < 10; $i += 2)
									{
										echo "<td><input type='text' name='".Variablen::$kurz_essen[$i]."'></td>";
									}
								?>
							</tr>
							<tr>
								<td>Küche offen/geschlossen</td>
								<?php for ( $i = 0; $i < 5; $i++)
									{
										?>
										<td>
											<fieldset>
												<input type='radio' id='<?php echo Variablen::$Tag_kurz[$i].'_offen'?>' name='<?php echo Variablen::$Tag_kurz[$i].'_geschlossen'?>' value="0" checked>
												<label for='<?php echo Variablen::$Tag_kurz[$i].'_offen'?>'>offen</label><br>
												<input type='radio' id='<?php echo Variablen::$Tag_kurz[$i].'_geschlossen'?>' name='<?php echo Variablen::$Tag_kurz[$i].'_geschlossen'?>' value="-1">
												<label for='<?php echo Variablen::$Tag_kurz[$i].'_geschlossen'?>'>keine Küche</label><br>
												<input type='radio' id='<?php echo Variablen::$Tag_kurz[$i].'_nofood'?>' name='<?php echo Variablen::$Tag_kurz[$i].'_geschlossen'?>' value="1">
												<label for='<?php echo Variablen::$Tag_kurz[$i].'_nofood'?>'>kein Essen</label><br>
											</fieldset>
										</td>
										<?php
									}
								?>
							</tr>
						</tbody>
					</table>
				</div>
				<div>
					<p>
						<input type="checkbox" id="geschlossen" name="geschlossen" value="1">
						<label for="geschlossen">Küche für die gesamte Woche schließen (ignoriert alle Menü-Einträge)</label>
					</p>
				</div>
				<div>
					<input type="submit" name="Speiseplan_speichern" value="Speiseplan speichern" />
				</div>
			</form>
		</div>
	<?php
	}
	// beendet output-buffering und übergibt Buffer an $html für Ausgabe
	$html = ob_get_clean();
	
	// Button gedrückt
	if(isset($_POST["Speiseplan_speichern"]))
	{
		if(is_numeric($_POST["KW"])) // überprüft ob eine Zahl als KW angegeben wurde
		{
			$KW = intval($_POST["KW"]);
			$KW = ($KW<10)?('0'.$KW):($KW);
			
			if ( NULL === $wpdb->get_row("SELECT * FROM $table WHERE KW='$KW'")) // überprüft ob Speiseplan für $KW vorhanden und verhindert abspeichern falls ja
			{
				if ( 1 == $_POST['geschlossen'])
				{
					$Eintrag = array( 'KW' => $KW );
					for ( $i = 0; $i < 5; $i++)
					{
						$Eintrag[Variablen::$kurz_essen[(2*$i)]] = 'Küche geschlossen';
						$Eintrag[Variablen::$kurz_essen[(2*$i+1)]] = 'Bestellung über Zettel in den Bereichen';
						$Eintrag[Variablen::$Tag_kurz[$i].'_geschlossen'] = -1;
					}
					$Eintrag['geschlossen'] = 1;
					$wpdb->insert( // neue Zeile mit diesen Werten eintragen
						$table,
						$Eintrag
					);
					$html .= "<p>Küche für KW " . $KW . " geschlossen.</p>";
				}
				elseif(	'' != $_POST["Mo_voll"] && ( '' != $_POST["Mo_veg"] || 0 != $_POST['Mo_geschlossen'] ) &&
						'' != $_POST["Di_voll"] && ( '' != $_POST["Di_veg"] || 0 != $_POST['Di_geschlossen'] ) &&
						'' != $_POST["Mi_voll"] && ( '' != $_POST["Mi_veg"] || 0 != $_POST['Mi_geschlossen'] ) &&
						'' != $_POST["Do_voll"] && ( '' != $_POST["Do_veg"] || 0 != $_POST['Do_geschlossen'] ) &&
						'' != $_POST["Fr_voll"] && ( '' != $_POST["Fr_veg"] || 0 != $_POST['Fr_geschlossen'] ) ) // überprüft ob alle Felder gefüllt sind (oder für geschlossene Tage ob Vollkost gefüllt
				{
					$Eintrag = array( 'KW' => $KW );
					for ( $i = 0; $i < 5; $i++)
					{
						$Eintrag[Variablen::$kurz_essen[(2*$i)]] = $_POST[Variablen::$kurz_essen[(2*$i)]];
						switch (intval($_POST[Variablen::$Tag_kurz[$i].'_geschlossen']))
						{
							case 0: 
								$Eintrag[Variablen::$kurz_essen[(2*$i+1)]] = $_POST[Variablen::$kurz_essen[(2*$i+1)]];
								$Eintrag[Variablen::$kurz_essen_num[(2*$i)]] = $_POST[Variablen::$kurz_essen_num[(2*$i)]];
								$Eintrag[Variablen::$kurz_essen_num[(2*$i+1)]] = $_POST[Variablen::$kurz_essen_num[(2*$i+1)]];
								$Eintrag[Variablen::$Tag_kurz[$i].'_geschlossen'] = 0;
								break;
							case -1:
								$Eintrag[Variablen::$kurz_essen[(2*$i+1)]] = 'Bestellung über Zettel in den Bereichen';
								$Eintrag[Variablen::$Tag_kurz[$i].'_geschlossen'] = -1;
								break;
							case 1:
								$Eintrag[Variablen::$kurz_essen[(2*$i+1)]] = 'keine Essensausgabe';
								$Eintrag[Variablen::$Tag_kurz[$i].'_geschlossen'] = 1;
								break;
						}
					}
					$wpdb->insert( // neue Zeile mit diesen Werten eintragen
						$table,
						$Eintrag
					);
					$html .= "<p>Speiseplan für KW " . $KW . " gespeichert.</p>";
				} else $html .= "<p>Bitte alle Felder ausfüllen.</p>";
			} else $html .= "<p>Speiseplan für KW $KW schon vorhanden. Änderungen bitte mittels \"Speiseplan anzeigen und ändern\" ausführen.</p>";
		} else $html .= "<p>Bitte Kalenderwoche angeben.</p>";
	}
	
	return $html;
}

// Gibt Speiseplaneintrag für Wochentag, Essenstyp und Kalenderwoche aus
function Speisekarte_Ausgabe( $spalte, $KW, $modus = 'standard' ) { // $spalte = Tageskürzel_Essenskürzel, $KW für Kalenderwoche, $modus = 'standard' für beides, 'essen' für nur Essen, 'zusatz' für nur Zusatz
	global $wpdb;
	// Speiseplan parat legen
    $table = $wpdb->prefix . "speiseplan";
	// SQL-Query um das Essen der Zelle zu finden
	$KW = intval($KW);
	$KW = ($KW<10)?('0'.$KW):($KW);
	$SQL = "SELECT ".$spalte.",".$spalte."_num FROM $table WHERE KW='$KW'";
	$Zelle = $wpdb->get_results($SQL, 'ARRAY_A');
	// Inhalt der Zelle ermitteln
	if( NULL !== $Zelle)
	{
		if ( 'standard' == $modus || 'essen' == $modus )
		{
			$html = str_replace('\\', '', $Zelle[0][$spalte] );
			if ( '' != $Zelle[0][$spalte.'_num'] && 'standard' == $modus)
			{
				$html .= '<br>[' . $Zelle[0][$spalte.'_num'] .']';
			}
		}
		elseif ( 'zusatz' == $modus )
		{
			$html = $Zelle[0][$spalte.'_num'];
		}
	} else { $html = "SQL-Fehler";}
	
	return $html;
}

// Speiseplan der angegebenen Woche als vorausgefülltes Eingabeformular anzeigen
function Speiseplan_Anzeigen() {
	include( 'variablen.php');
	// beginnt output-buffering
    ob_start();
	// Textfeld für Kalenderwoche und Button zum Ausführen
    {
		?>
		<div>
			<h3 id="anzeige" >"Speiseplan anzeigen und ändern" öffnet den Speiseplan für die gewählte Woche und erlaubt Änderungen.</h3>
			<form action="#anzeige" method="post" name="Speiseplan_anzeigen" autocomplete="off">
				<table><tbody><tr>
				<td width=15%>Kalenderwoche:</td>
				<td width=10%><input type="text" name="Kalenderwoche" value=""></td>
				<td width=5%></td>
				<td><input type="submit" name="Speiseplan_anzeigen" value="Speiseplan anzeigen und ändern" /></td>
				</tr></tbody></table>
			</form>
		</div>
		<?php
	}
	// beendet output-buffering und übergibt Buffer an $html für Ausgabe
	$html = ob_get_clean();
	
	// Anzeige-Button gedrückt
	if(isset($_POST["Speiseplan_anzeigen"]))
	{
		if(is_numeric($_POST["Kalenderwoche"]))
		{
			$KW = $_POST["Kalenderwoche"];
			$KW = ($KW<10)?('0'.$KW):($KW);
			// beginnt output-buffering für das Formular
			ob_start();
			// Tabelle mit Eingabeformularen (vorgefüllt mit bereits gespeicherten Einträgen)
			?>
			<form action="#speiseplan_edit" method="post" id="edit_form" name="edit_form" autocomplete="off">
				<div>
					<table>
						<thead>
							<tr>
								<th>
									KW: <?php echo $KW; ?>
								</th>
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
								<?php for ( $i = 0; $i < 10; $i += 2)
								{
									echo "<td><input type='text' name='".Variablen::$kurz_essen[$i]."_edit' value='".Speisekarte_Ausgabe( Variablen::$kurz_essen[$i], $KW, 'essen' )."'></td>";
								}
								?>
							</tr>
							<tr>
								<td>Zusatzstoffe</td>
								<?php for ( $i = 0; $i < 10; $i += 2)
								{
									echo "<td><input type='text' name='".Variablen::$kurz_essen_num[$i]."_edit' value='".Speisekarte_Ausgabe( Variablen::$kurz_essen[$i], $KW, 'zusatz' )."'></td>";
								}
								?>
							</tr>
							<tr>
								<td>Vegetarisch</td>
								<?php for ( $i = 1; $i < 10; $i += 2)
								{
									echo "<td><input type='text' name='".Variablen::$kurz_essen[$i]."_edit' value='".Speisekarte_Ausgabe( Variablen::$kurz_essen[$i], $KW, 'essen' )."'></td>";
								}
								?>
							</tr>
							<tr>
								<td>Zusatzstoffe</td>
								<?php for ( $i = 1; $i < 10; $i += 2)
								{
									echo "<td><input type='text' name='".Variablen::$kurz_essen_num[$i]."_edit' value='".Speisekarte_Ausgabe( Variablen::$kurz_essen[$i], $KW, 'zusatz' )."'></td>";
								}
								?>
							</tr>
							<tr>
								<td>Küche offen/geschlossen</td>
								<?php for ( $i = 0; $i < 5; $i++)
									{
										?>
										<td>
											<fieldset>
												<input type='radio' id='<?php echo Variablen::$Tag_kurz[$i].'_offen_edit'?>' name='<?php echo Variablen::$Tag_kurz[$i].'_geschlossen_edit'?>' value="0" <?php if ( 0 == Kueche_offen($KW, Variablen::$Tag_kurz[$i]) ) echo 'checked'; ?>>
												<label for='<?php echo Variablen::$Tag_kurz[$i].'_offen_edit'?>'>offen</label><br>
												<input type='radio' id='<?php echo Variablen::$Tag_kurz[$i].'_geschlossen_edit'?>' name='<?php echo Variablen::$Tag_kurz[$i].'_geschlossen_edit'?>' value="-1" <?php if ( -1 == Kueche_offen($KW, Variablen::$Tag_kurz[$i]) ) echo 'checked'; ?>>
												<label for='<?php echo Variablen::$Tag_kurz[$i].'_geschlossen_edit'?>'>keine Küche</label><br>
												<input type='radio' id='<?php echo Variablen::$Tag_kurz[$i].'_nofood_edit'?>' name='<?php echo Variablen::$Tag_kurz[$i].'_geschlossen_edit'?>' value="1" <?php if ( 1 == Kueche_offen($KW, Variablen::$Tag_kurz[$i]) ) echo 'checked'; ?>>
												<label for='<?php echo Variablen::$Tag_kurz[$i].'_nofood_edit'?>'>kein Essen</label><br>
											</fieldset>
										</td>
										<?php
									}
								?>
							</tr>
						</tbody>
					</table>
				</div>
				<div>
					<?php
					if ( !Kueche_offen($KW) ) // Küche ist ganze Woche geschlossen
					{
						?>
						<div>
							<p>
								<label for="offen">Die Küche ist für diese Woche geschlossen. Küche öffnen?</label>
								<input type="checkbox" id="offen" name="offen" value="1"><br>
								Alle weiteren Eingaben werden ignoriert, so lange diese Box nicht markiert wird.
							</p>
						</div>
						<?php
					}
					else
					{
						echo '<div>
							<p>
								<input type="checkbox" id="geschlossen_edit" name="geschlossen_edit" value="1">
								<label for="geschlossen_edit">Küche für die gesamte Woche schließen (ignoriert alle Menü-Einträge)</label>
							</p>
						</div>';
					}				
					?>	
					<div class="hidden">
						<input type="text" name="Kalenderwoche" value="<?php echo $KW; ?>"> <!-- verstecktes Textfeld zur Übergabe der $KW bei Knopfdruck-->
					</div>
					<input type="submit" name="Speiseplan_edit" value="Speiseplan speichern" />
				</div>
			</form>
			<?php
			// beendet output-buffering und übergibt Buffer an $html für Ausgabe
			$html .= ob_get_clean();
		} else $html .= "<p>Bitte Kalenderwoche angeben.</p>";
	}
	
	// wenn Edit-Button gedrückt wurde
	if (isset($_POST["Speiseplan_edit"]))
	{				
		// wp_speiseplan parat legen
		global $wpdb;
		$table = $wpdb->prefix . "speiseplan";
		$KW = intval($_POST["Kalenderwoche"]);
		$KW = ($KW<10)?('0'.$KW):($KW);
		if ( 1 == $_POST['geschlossen_edit'])
		{
			$Eintrag = array( 'KW' => $KW );
			for ( $i = 0; $i < 5; $i++)
			{
				$Eintrag[Variablen::$kurz_essen[(2*$i)]] = 'Küche geschlossen';
				$Eintrag[Variablen::$kurz_essen[(2*$i+1)]] = 'Bestellung über Zettel in den Bereichen';
				$Eintrag[Variablen::$Tag_kurz[$i].'_geschlossen'] = -1;
			}
			$Eintrag['geschlossen'] = 1;
			$wpdb->replace( // vorhandene Zeile mit diesen Werten überschreiben
				$table,
				$Eintrag
			);
			$html .= "<p>Küche für KW " . $KW . " geschlossen.</p>";
		}
		elseif ( Kueche_offen($KW) || 1 == $_POST['offen'] ) // Küche ist offen ODER Küche wurde geöffnet
		{
			if	(	'' != $_POST["Mo_voll_edit"] && ( '' != $_POST["Mo_veg_edit"] || 0 != $_POST['Mo_geschlossen_edit'] ) &&
					'' != $_POST["Di_voll_edit"] && ( '' != $_POST["Di_veg_edit"] || 0 != $_POST['Di_geschlossen_edit'] ) &&
					'' != $_POST["Mi_voll_edit"] && ( '' != $_POST["Mi_veg_edit"] || 0 != $_POST['Mi_geschlossen_edit'] ) &&
					'' != $_POST["Do_voll_edit"] && ( '' != $_POST["Do_veg_edit"] || 0 != $_POST['Do_geschlossen_edit'] ) &&
					'' != $_POST["Fr_voll_edit"] && ( '' != $_POST["Fr_veg_edit"] || 0 != $_POST['Fr_geschlossen_edit'] ) ) // überprüft ob alle Felder gefüllt sind (oder für geschlossene Tage ob Vollkost gefüllt)
			{
				$Eintrag = array();
				$Eintrag['geschlossen'] = 0;
				for ( $i = 0; $i < 5; $i++)
				{
					$Eintrag[Variablen::$kurz_essen[(2*$i)]] = $_POST[Variablen::$kurz_essen[(2*$i)].'_edit'];
					switch (intval($_POST[Variablen::$Tag_kurz[$i].'_geschlossen_edit']))
					{
						case 0: 
							$Eintrag[Variablen::$kurz_essen[(2*$i+1)]] = $_POST[Variablen::$kurz_essen[(2*$i+1)].'_edit'];
							$Eintrag[Variablen::$kurz_essen_num[(2*$i)]] = $_POST[Variablen::$kurz_essen_num[(2*$i)].'_edit'];
							$Eintrag[Variablen::$kurz_essen_num[(2*$i+1)]] = $_POST[Variablen::$kurz_essen_num[(2*$i+1)].'_edit'];
							$Eintrag[Variablen::$Tag_kurz[$i].'_geschlossen'] = 0;
							break;
						case -1:
							$Eintrag[Variablen::$kurz_essen[(2*$i+1)]] = 'Bestellung über Zettel in den Bereichen';
							$Eintrag[Variablen::$Tag_kurz[$i].'_geschlossen'] = -1;
							break;
						case 1:
							$Eintrag[Variablen::$kurz_essen[(2*$i+1)]] = 'keine Essensausgabe';
							$Eintrag[Variablen::$Tag_kurz[$i].'_geschlossen'] = 1;
							break;
					}
				}
				$wpdb->update( // vorhandene Zeile mit diesen Werten überschreiben
					$table,
					$Eintrag,
					array( 'KW' => $KW )
				);
				$html .= "<p>Speiseplan für KW " . $KW . " gespeichert.</p>";
			} else $html .= "<p>Bitte alle Felder ausfüllen.</p>";
		}
		else $html .= "<p>Küche ist weiterhin für KW ".$KW." geschlossen.</p>";
	}
	
	return $html;
}

// Befehle zum Exportieren der Speisepläne
function speiseplan_export_fct() {
	global $wpdb;
    // beginnt output-buffering
    ob_start();
    {
		?>
		<div>
			<h3 id='export'>"Speiseplan ausgeben" erzeugt einen Link über den man den Speiseplan der angegebenen Kalenderwoche herunterladen kann.</h3>
		</div>
		<form action="#export" method="post">
			<table><tbody><tr>
			<td width=15%>Kalenderwoche:</td>
			<td width=10%><input type="text" name="KW" value=""></td>
			<td width=5%></td>
			<td><input type="submit" name="Export" value="Speiseplan ausgeben"></td>
			</tr></tbody></table>
		</form>
		<?php
	}
	// beendet output-buffering und übergibt Buffer an $html für Ausgabe
    $html = ob_get_clean();
    // "Bestellliste ausgeben" gedrückt
	if ( isset( $_POST["Export"]))
    {        
        if ( is_numeric($_POST["KW"]))
		{
			$KW = intval($_POST["KW"]);
			$KW = ($KW<10)?('0'.$KW):($KW);
			// Bestellliste parat legen
			$table = $wpdb->prefix . "speiseplan";
			// SQL-Query
			$Anfrage = $wpdb->get_results("SELECT * FROM $table WHERE KW = '$KW'", 'ARRAY_A' );
			if ( NULL !== $Anfrage ) // SQL-Query hat funktioniert
			{
				// Dateiname und -pfad festlegen
				$Dateiname = 'Speiseplan_' . $KW;
				$Dateiverzeichnis = './Mittagessen/';
				if(!is_dir($Dateiverzeichnis)) // erstellt das Verzeichnis Mittagessen falls noch nicht vorhanden
				{
					mkdir($Dateiverzeichnis);
				}
				$Dateipfad = $Dateiverzeichnis . $Dateiname . '.csv';

				// Tabelle in string umwandeln und für .CSV formatieren
				$Tabellentext = "Kalenderwoche $KW;";
				for ( $i = 0; $i < 5; $i++)
					{
						$Tabellentext .= " ".Variablen::$Tag_name[$i].";";
					}
				$Tabellentext .= "\r\nVollkost";
				for ( $i = 0; $i < 10; $i += 2)
					{
						$Tabellentext .= ";".str_replace('\\', '', $Anfrage[0][Variablen::$kurz_essen[$i]]);
					}
				$Tabellentext .= "\r\nZusatzstoffe";
				for ( $i = 0; $i < 10; $i += 2)
					{
						$Tabellentext .= ";".$Anfrage[0][Variablen::$kurz_essen_num[$i]];
					}
				$Tabellentext .= "\r\nVegetarisch";
				for ( $i = 1; $i < 10; $i += 2)
					{
						$Tabellentext .= ";".str_replace('\\', '', $Anfrage[0][Variablen::$kurz_essen[$i]]);
					}
				$Tabellentext .= "\r\nZusatzstoffe";
				for ( $i = 1; $i < 10; $i += 2)
					{
						$Tabellentext .= ";".$Anfrage[0][Variablen::$kurz_essen_num[$i]];
					}
					
				$Tabellentext = mb_convert_encoding($Tabellentext, 'Windows-1252');
				
				// Alte Datei-Version zur Sicherheit umbenennen
				if (file_exists($Dateipfad)) rename($Dateipfad, $Dateiverzeichnis . $Dateiname . '_alt' . '.csv');

				// Tabellentext in Datei speichern
				$Flag_file_put_contents = file_put_contents($Dateipfad, $Tabellentext);

				if ($Flag_file_put_contents)
				{
					// Link für Datei-Download
					if (file_exists($Dateipfad))
					{
						$html .= "<p><a href='./download.php?speiseplan=". $KW ."' target='_blank'>Diesen Link klicken und mit Excel öffnen, dann die Datei abspeichern.</a></p>";
					}
				}
				else $html .="<p>Fehler beim Ausgeben des Speiseplans.</p>";

			} else { $html .= "<p>SQL Anfrage misslungen.</p>"; }
		}
    }
	
    // outputs everything
    return $html;
    
}

// Aufruf von bestell_summe für laufende und kommende Woche für Kochplan
function Kochplan_Aufruf_bestell_summe () {
	$html = bestell_summe(date('W')) . bestell_summe(date('W', strtotime('+1 week')));
	return $html;
}

// Eintragen von Anzahl Gäste-Essen für laufende oder spezifische Woche
function Gaeste_Essen () {
	global $wpdb;
    // beginnt output-buffering
    ob_start();
    {
		?>
		<div>
			<h3 id='gaeste'>"Gäste-Essen eintragen" öffnet das Gäste-Essen Formular für die gewählte Woche. Bei leerem Kalenderwoche-Feld wird die laufende Woche gewählt.</h3>
		</div>
		<form action="#gaeste" method="post">
			<table><tbody><tr>
			<td width=15%>Kalenderwoche:</td>
			<td width=10%><input type="text" name="Kalenderwoche" value=""></td>
			<td width=5%></td>
			<td><input type="submit" name="Gaeste" value="Gäste-Essen eintragen"></td>
			</tr></tbody></table>
		</form>
		<?php
	}
	// beendet output-buffering und übergibt Buffer an $html für Ausgabe
    $html = ob_get_clean();
	
	// "Gäste-Essen eintragen" gedrückt
	if(isset($_POST["Gaeste"]))
	{
		if( is_numeric($_POST["Kalenderwoche"]) || '' == $_POST['Kalenderwoche'] )
		{
			$KW = ('' == $_POST['Kalenderwoche'])?(date('W')):(intval($_POST["Kalenderwoche"]));
			$KW = ($KW<10)?('0'.$KW):($KW);
			
			// Überprüfen ob bereits Daten vorhanden
			$table = Tabelle_Erstellen('bestellung', $KW);
			$bestellt_test = "SELECT * FROM ".$table." WHERE Name = '#Gäste-Essen' AND Kategorie = 'Mitarbeiter'";
			$gaeste_vorhanden = $wpdb->get_row( $bestellt_test, 'ARRAY_A');
			
			// falls Daten bereits vorhanden, diese für Formular parat legen
			if ( false !== $gaeste_vorhanden )
			{
				$alte_Daten = array();
				for ( $i = 0; $i<10; $i++ )
				{
					$alte_Daten[Variablen::$kurz_essen[$i]] = $gaeste_vorhanden[Variablen::$kurz_essen[$i]];
				}
			}
			
			// beginnt output-buffering für das Formular
			ob_start();
			// Tabelle mit Eingabeformularen (vorgefüllt mit bereits gespeicherten Einträgen)
			?>
			<form action="#gaeste" method="post" id="gaeste_form" name="gaeste_form" autocomplete="off">
				<div>
					<table>
						<thead>
							<tr>
								<th>
									KW: <?php echo $KW; ?>
								</th>
								<?php for ( $i = 0; $i < 5; $i += 1)
								{
									echo "<th>".Variablen::$Tag_name[$i]."</th>";
								}
								?>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td>Gäste Vollkost</td>
								<?php for ( $i = 0; $i < 10; $i += 2)
								{
									echo "<td><input type='text' name='gaeste_".Variablen::$kurz_essen[$i]."' value='".$alte_Daten[Variablen::$kurz_essen[$i]]."'></td>";
								}
								?>
							</tr>
							<tr>
								<td>Gäste Vegetarisch</td>
								<?php for ( $i = 1; $i < 10; $i += 2)
								{
									echo "<td><input type='text' name='gaeste_".Variablen::$kurz_essen[$i]."' value='".$alte_Daten[Variablen::$kurz_essen[$i]]."'></td>";
								}
								?>
							</tr>
						</tbody>
					</table>
				</div>
				<div>
					<div class="hidden">
						<input type="text" name="Kalenderwoche" value="<?php echo $KW; ?>"> <!-- verstecktes Textfeld zur Übergabe der $KW bei Knopfdruck-->
					</div>
					<input type="submit" name="Gaeste_form" value="Formular abschicken" />
				</div>
			</form>
			<?php
			// beendet output-buffering und übergibt Buffer an $html für Ausgabe
			$html .= ob_get_clean();
		} else $html .= "<p>Bitte Kalenderwoche angeben oder Feld leer lassen für die laufende Woche.</p>";
	}
	
	// wenn "Formular abschicken"-Button gedrückt wurde
	if (isset($_POST["Gaeste_form"]))
	{				
		// Bestellliste parat legen
		$KW = intval($_POST["Kalenderwoche"]);
		$KW = ($KW<10)?('0'.$KW):($KW);
		$table = Tabelle_Erstellen('bestellung', $KW);

		// $Eintrag füllen
		$Eintrag = array();
		$Fehler = array();
		$Eintrag['Name'] = '#Gäste-Essen';
		$Eintrag['Kategorie'] = 'Mitarbeiter';
		for ( $i = 0; $i < 10; $i++)
		{
			if( '' == $_POST['gaeste_'.Variablen::$kurz_essen[$i]])
			{
				$Eintrag[Variablen::$kurz_essen[$i]] = 0;
			}
			elseif ( is_numeric($_POST['gaeste_'.Variablen::$kurz_essen[$i]]) )
			{
				$Eintrag[Variablen::$kurz_essen[$i]] = intval($_POST['gaeste_'.Variablen::$kurz_essen[$i]]);
			}
			else
			{
				$Fehler[Variablen::$kurz_essen[$i]] = "Fehler bei ".Variablen::$kurz_essen[$i].": keine Zahl eingetragen. Bitte erneut eintragen.";
			}
		}
		$Eintrag['Datum'] = date('H:i d.m.');
		
		// Test ob schon vorhanden
		$bestellt_test = "SELECT id FROM ".$table." WHERE Name = '".$Eintrag['Name']."' AND Kategorie = '".$Eintrag['Kategorie']."'";
		$gaeste_vorhanden = $wpdb->get_row( $bestellt_test, 'ARRAY_A');
		
		// Gäste-Essen neu eintragen oder aktualisieren
			if ( !$gaeste_vorhanden ) // neu eintragen
			{
				$Flag_Bestellung = $wpdb->insert( $table, $Eintrag );
			}
			else // aktualisieren
			{
				$Eintrag['id'] = $gaeste_vorhanden['id'];
				$Flag_Bestellung = $wpdb->replace( $table, $Eintrag );
			}
			
		// Erfolg oder SQL-Fehler Meldung
		if ( false !== $Flag_Bestellung )
		{
			if ( !empty($Fehler))
			{
				foreach ($Fehler as $key)
				{
					$html .= '<br>'.$key;
				}
			}
			$html .= "<p>Gäste-Essen für KW " . $KW . " eingetragen.</p>";
		}
		else $html .= "<p>Es gab einen Fehler beim abspeichern der Gäste-Essen.</p>";
		
		echo '<pre>'.print_r($gaeste_vorhanden, true).'</pre>';
		echo '<pre>'.print_r($bestellt_test, true).'</pre>';
	}
	
    // outputs everything
    return $html;
}

function bestell_summe ( $KW ) {
	global $wpdb;
	// passende Bestellliste parat legen
	$table = $wpdb->prefix . "bestellliste_KW_" . $KW;

	// SQL um alle Bestellungen in ein Array zu ziehen
	$query = "SELECT * FROM $table";
	$bestellungen = $wpdb->get_results($query, ARRAY_A);

	// Bestellungen zusammenzählen
	$summe = array();
	for ( $i = 0; $i < 10; $i++)
	{
		$summe[Variablen::$kurz_essen[$i]] = 0;
	}
	
	foreach ($bestellungen as $zeile)
	{
		for ( $i = 0; $i < 10; $i++)
		{
			$summe[Variablen::$kurz_essen[$i]] += $zeile[Variablen::$kurz_essen[$i]];
		}
	}
	$gesamt_voll = 0;
	$gesamt_veg = 0;
	for ( $i = 0; $i < 10; )
	{
		$gesamt_voll += $summe[Variablen::$kurz_essen[$i]];
		$i++;
		$gesamt_veg += $summe[Variablen::$kurz_essen[$i]];
		$i++;
	}

	// Ausgabe Summen in Tabelle
	// beginnt output-buffering
	ob_start();
	if (date('W') == $KW) echo "<h3>Bestellsumme der laufenden Woche:</h3>";
	if (date('W', strtotime('+1 week')) == $KW) echo "<h3>Bestellsumme der kommende Woche:</h3>";
	{
		?>
		<div class="speisekarte-tabelle">
			<table>
				<thead>
					<tr>
						<th>KW: <?php echo $KW; ?></th>
						<?php for ( $i = 0; $i < 5; $i += 1)
						{
							echo "<th>".Variablen::$Tag_name[$i]."</th>";
						}
						?>
						<th>Gesamt</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>Vollkost</td>
						<?php for ( $i = 0; $i < 10; $i += 2)
						{
							echo "<td>".$summe[Variablen::$kurz_essen[$i]]."</td>";
						}
						echo "<td>".$gesamt_voll."</td>"; ?>
					</tr>
					<tr>
						<td>Vegetarisch</td>
						<?php for ( $i = 1; $i < 10; $i += 2)
						{
							echo "<td>".$summe[Variablen::$kurz_essen[$i]]."</td>";
						}
						echo "<td>".$gesamt_veg."</td>"; ?>
					</tr>
				</tbody>
			</table>
		</div>
		<?php
	}
	// beendet output-buffering und übergibt Buffer an $html für Ausgabe
	$html = ob_get_clean();

	// Bestellsummen über Link als .CSV ausgeben
	// Dateiname und -pfad festlegen
	$Dateiname = 'Bestellsumme_' . $KW;
	$Dateiverzeichnis = './Mittagessen/';
	if(!is_dir($Dateiverzeichnis)) // erstellt das Verzeichnis Mittagessen falls noch nicht vorhanden
	{
		mkdir($Dateiverzeichnis);
	}
	$Dateipfad = $Dateiverzeichnis . $Dateiname . '.csv';

	// Tabelle in string umwandeln und für .CSV formatieren
	$Tabellentext = "Kalenderwoche $KW;";
	for ( $i = 0; $i < 5; $i++)
	{
		$Tabellentext .= " ".Variablen::$Tag_name[$i].";";
	}
	$Tabellentext .= "Gesamt\r\nVollkost";
	for ( $i = 0; $i < 10; $i += 2)
	{
		$Tabellentext .= ";".$summe[Variablen::$kurz_essen[$i]];
	}
	$Tabellentext .= ";$gesamt_voll\r\nVegetarisch";
	for ( $i = 1; $i < 10; $i += 2)
	{
		$Tabellentext .= ";".$summe[Variablen::$kurz_essen[$i]];
	}
	$Tabellentext .= ";$gesamt_veg";

	$Tabellentext = utf8_decode($Tabellentext);

	// Alte Datei-Version zur Sicherheit umbenennen
	if (file_exists($Dateipfad)) rename($Dateipfad, $Dateiverzeichnis . $Dateiname . '_alt' . '.csv');

	// Tabellentext in Datei speichern
	$Flag_file_put_contents = file_put_contents($Dateipfad, $Tabellentext);

	if ($Flag_file_put_contents)
	{
		// Link für Datei-Download
		if (file_exists($Dateipfad))
		{
			$html .= "<p><a href='./download.php?bestellsumme=". $KW ."' target='_blank'>Diesen Link klicken und mit Excel öffnen, dann die Datei abspeichern.</a></p>";
		}
	}
	else $html .="<p>Fehler beim Ausgeben der Bestellsumme.</p>";
	
	return $html;
}
