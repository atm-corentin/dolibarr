<?php

class compteurHolidayManager {

	public function addTableOfTypeHolidays() {
		require_once DOL_DOCUMENT_ROOT . '/holiday/class/holiday.class.php';

		global $db, $object, $langs, $conf;

		$MAXLIST = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;

		// Exécute la requête SQL et génère le tableau HTML
		$resql = $db->query($this->buildSQL($object->id));
		if ($resql) {
			$num = $db->num_rows($resql);

			$table = $this->generateTableHeader($num, $MAXLIST);
			$table .= $this->generateTableRows($resql, $num, $MAXLIST);
			$table .= '</table>';

			$db->free($resql);

			// JavaScript pour insérer le tableau dans la page
			$this->insertTableIntoPage($table);
		} else {
			dol_print_error($db);
		}
	}

	/**
	 * Construit la requête SQL pour récupérer les types de congés de l'utilisateur
	 */
	private function buildSQL($userId) {
		global $db;

		$sql = "SELECT ht.label, hu.nb_holiday";
		$sql .= " FROM " . $db->prefix() . "holiday_users as hu";
		$sql .= " INNER JOIN " . $db->prefix() . "c_holiday_types as ht ON hu.fk_type = ht.rowid";
		$sql .= " WHERE hu.fk_user = " . ((int) $userId);
		$sql .= " AND ht.active = 1";
		$sql .= " AND ht.affect = 1";

		return $sql;
	}

	/**
	 * Génère l'en-tête du tableau HTML
	 */
	private function generateTableHeader($num, $MAXLIST) {
		global $langs;
		$url = dol_buildpath('holiday/define_holiday.php', 1);

		$table = '<table class="noborder centpercent">';
		$table .= '<tr class="liste_titre">';
		$table .= '<td colspan="4"><table class="nobordernopadding centpercent"><tr><td>';
		$table .= $langs->trans("TypeHolidays", ($num <= $MAXLIST ? "" : $MAXLIST));
		$table .= '</td><td class="right"><a class="notasortlink" href="' . $url . '">';
		$table .= $langs->trans("AllHolidays") . '</a></td></tr></table>';

		return $table;
	}

	/**
	 * Génère les lignes du tableau en fonction des résultats de la requête
	 */
	private function generateTableRows($resql, $num, $MAXLIST) {
		global $db, $langs;
		$table = '';

		if ($num <= 0) {
			$table .= '<tr><td colspan="4"><span class="opacitymedium">' . $langs->trans("None") . '</span></td></tr>';
		} else {
			$i = 0;
			while ($i < $num && $i < $MAXLIST) {
				$objp = $db->fetch_object($resql);

				$table .= '<tr class="oddeven">';
				$table .= '<td class="nowraponall">' . $objp->label . '</td>';
				$table .= '<td class="right nowraponall">' . $objp->nb_holiday . ' j.</td>';
				$table .= '<td class="right nowraponall"></td>';
				$table .= '</tr>';
				$i++;
			}
		}

		return $table;
	}

	/**
	 * Insère le tableau HTML dans la page via JavaScript
	 */
	private function insertTableIntoPage($table) {
		?>
		<script>
			$(document).ready(function() {
				$(".fichehalfright").append($('<?php echo $table ?>'));
			});
		</script>
		<?php
	}

	/**
	 * @param $startDate
	 * @param $endDate
	 * @return int  Calcul de la différence entre deux date en jours
	 */
	public static function  calculateDaysDifference($startDate, $endDate) {

		include_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

		//verification des jour off et jour ouvré
		$noWorkingDays = num_public_holiday($startDate, $endDate, 'FR');

		// Si les dates sont des timestamps, les convertir en chaînes de date
		if (is_numeric($startDate)) {
			$startDate = date('Y-m-d H:i:s', $startDate);
		}
		if (is_numeric($endDate)) {
			$endDate = date('Y-m-d H:i:s', $endDate);
		}

		$start = new DateTime($startDate);
		$end = new DateTime($endDate);

		$difference = $start->diff($end);
		$days = $difference->days + 1;

		return $days - $noWorkingDays;
	}
}
