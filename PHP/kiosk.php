<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
/* Funktionen in diesem .php:
 * 
 * function hat_gegessen ( $kategorie, $heute )
 * function kategorie_liste ( $kategorie )
 */

// markiert Essen als ausgeteilt wenn Button in kategorie_liste gedrückt wurde
function hat_gegessen ( $kategorie, $table, $heute ) {
	global $wpdb;
	
	$update = array();
	$update[Variablen::$Tag_name[$heute]] = 1;
	$where = array();
	$where['Name'] = $_POST['ausgeteilt_'.$kategorie];
	$where['Kategorie'] = $kategorie;
	
	$wpdb->update(
			$table,
			$update,
			$where
			);
	
	return $html="";
}

// Erzeugt Tabelle für gewählte $kategorie und zeigt für jeden Namen die bestellten Essen je nach Wochentag an
// erlaubt außerdem per Klick das heutige Essen eines Namens als "ausgeteilt" zu markieren
function kategorie_liste ( $kategorie ) {
	global $wpdb;
	
	$KW = date('W');
	// Bestellliste parat legen
	$table = $wpdb->prefix . "bestellliste_KW_" . $KW;
	
	// Wochentag-Nummer angepasst auf 0~4 der for-Schleifen [Montag => 0, etc.]
	$heute = date('N') - 1;
	
	// markiert Essen als ausgeteilt wenn Button gedrückt wurde
	if ( isset($_POST['ausgeteilt_'.$kategorie]))
	{
		$hat_gegessen = hat_gegessen( $kategorie, $table, $heute);
	} else $hat_gegessen = '';
	
	// SQL-Query
	$kategorie_liste = $wpdb->get_results("SELECT * FROM $table WHERE Kategorie = '$kategorie' ORDER BY Name", 'ARRAY_A' );
		
	if ( NULL !== $kategorie_liste ) // SQL-Query hat funktioniert
	{	
		// beginnt output-buffering
		ob_start();
		?>
		<div>
			<form action="#button" method="post" id="button" name="button" autocomplete="off">
				<table class="bestellliste-tabelle">
					<thead>
						<tr>
							<th>Name</th>
							<?php for ( $i = 0; $i < 5; $i += 1)
								{
									echo "<th>".Variablen::$Tag_name[$i]."</th>";
								}
							?>
							<th>Bestellt am</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($kategorie_liste as $zeile) {?>
						<tr>
							<td><?php echo $zeile['Name']; ?></td>
							<?php for ( $i = 0; $i < 5; $i += 1)
								{
									$temp = ($zeile[Variablen::$Tag_kurz[$i].'_voll'])?"Vollkost":(($zeile[Variablen::$Tag_kurz[$i].'_veg'] > 0)?"Vegetarisch":"nicht bestellt");
									if ( 0 < $zeile[Variablen::$Tag_name[$i]] ) // für den Tag wurde "Essen ausgeteilt" bereits markiert
									{
										$temp = "<td>".$temp." ausgeteilt</td>";
									}
									elseif ( ($heute == $i) && ('nicht bestellt' != $temp) ) // nur für den heutigen Tag den "ausgeteilt"-Button anzeigen && nur wenn etwas bestellt
									{
										$temp = "<td bgcolor='#fddadd' ><label for='button_".$zeile['id']."'>$temp</label><input type='submit' name='ausgeteilt_".$zeile['Kategorie']."' id='button_".$zeile['id']."' value='".$zeile['Name']."' class='hidden'/></td>";
									}
									else $temp = "<td>".$temp."</td>";
									
									echo $temp;
								}
							?>
							<td><?php echo $zeile['Datum']; ?></td>
						</tr>
						<?php }?>
					</tbody>
				</table>
			</form>
		</div>
		<?php
		// beendet output-buffering und übergibt Buffer an $html für Ausgabe
		$html = $hat_gegessen.ob_get_clean();
	} else { $html = "<p>SQL Anfrage misslungen.</p>"; }
	
	return $html;
}
