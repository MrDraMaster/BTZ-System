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
/*
 * functions.php
 * Add PHP snippets here
 */

/* Funktionen in diesem .php:
 * 
 * function Speisekarte_Anzeige ( $woche )
 */

function Speisekarte_Anzeige ( $woche ) { // woche= laufende/kommende/ueber
	// beginnt output-buffering
    ob_start();
	switch ($woche)
	{
		case 'laufende':
			echo "<h3>Speiseplan der laufenden Woche:</h3>";
			$KW = date('W');
			break;
		case 'kommende':
			echo "<h3>Speiseplan der kommenden Woche:</h3>";
			$KW = date('W', strtotime('+1 week'));
			break;
		case 'ueber':
			echo "<h3>Speiseplan der übernächsten Woche:</h3>";
			$KW = date('W', strtotime('+2 week'));
			break;
	}
    {
		?>
		<div class="speisekarte-tabelle">
			<table>
				<thead>
					<tr>
						<th><?php echo Woche_Ausgabe($woche); ?></th>
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
						<?php for ( $i = 0; $i < 5; $i ++)
								{
									echo ($i%2)?("<td>"):("<td class='even'>");
									echo Speisekarte_Ausgabe( Variablen::$kurz_essen[2*$i], $KW )."</td>";
								}
							?>
					</tr>
					<tr>
						<td>Vegetarisch</td>
						<?php for ( $i = 0; $i < 5; $i ++)
								{
									echo ($i%2)?("<td>"):("<td class='even'>");
									echo Speisekarte_Ausgabe( Variablen::$kurz_essen[2*$i+1], $KW )."</td>";
								}
							?>
					</tr>
				</tbody>
			</table>
		</div>
		<?php
	}
	// beendet output-buffering und übergibt Buffer an $html für Ausgabe
    $html = ob_get_clean();
	return '<p>'.$html.'</p>';
}
