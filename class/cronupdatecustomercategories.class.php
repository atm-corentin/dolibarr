<?php

declare(strict_types=1);

// Load Dolibarr environment
$res = @include_once __DIR__ . '/../../main.inc.php';
if (!$res) {
	$res = @include_once __DIR__ . '/../../../main.inc.php';
}

require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once __DIR__ . '/CliChaumeilCommissionConfig.class.php';

class CronJobUpdateCustomerCategories
{
	/** @var DoliDB */
	public DoliDB $db;
	/** @var Conf */
	public Conf $conf;
	/** @var Translate */
	public Translate $langs;
	/** @var stdClass */
	public stdClass $job;
	/** @var string */
	public string $error = '';
	/** @var array<int,string> */
	public array $warnings = array();
	/** @var array<int,string> */
	public array $errors = array();
	/** @var string */
	public string $output = '';

	public function __construct(DoliDB $db)
	{
		global $conf, $langs;
		$this->db = $db;
		$this->conf = $conf;
		$this->langs = $langs;
		$langs->loadLangs(array('clichaumeil@clichaumeil'));
	}

	/**
	 * @return int 0 if OK, <0 if KO
	 */
	public function run(): int
	{
		global $conf, $langs, $user;

		// Trace a run identifier to correlate cron output with syslog entries.
		$runId = 'clichaumeil-' . dol_print_date(dol_now(), 'dayhourlog') . '-' . substr((string) mt_rand(), 0, 4);
		dol_syslog(__METHOD__ . ' start run=' . $runId, LOG_INFO);

		$newCategoryId = getDolGlobalInt(CliChaumeilCommissionConfig::CAT_NOUVEAU);
		$existingCategoryId = getDolGlobalInt(CliChaumeilCommissionConfig::CAT_ANCIEN);

		if (empty($newCategoryId) || empty($existingCategoryId)) {
			$missing = array();
			if (empty($newCategoryId)) {
				$missing[] = CliChaumeilCommissionConfig::CAT_NOUVEAU;
			}
			if (empty($existingCategoryId)) {
				$missing[] = CliChaumeilCommissionConfig::CAT_ANCIEN;
			}
			$this->error = $langs->trans('CliChaumeilCronMissingCategories');
			$message = $this->error . ' (' . implode(', ', $missing) . ')';
			if (!in_array($message, $this->errors, true)) {
				$this->errors[] = $message;
			}
			$this->output = $this->buildOutput(0, 0, $runId, 0, 0, 0, 0);
			dol_syslog(__METHOD__ . ' run=' . $runId . ' ' . implode(' | ', $this->errors), LOG_ERR);
			return -1;
		}

		$newCategory = new Categorie($this->db);
		$existingCategory = new Categorie($this->db);
		if ($newCategory->fetch($newCategoryId) <= 0 || $existingCategory->fetch($existingCategoryId) <= 0) {
			$this->error = $langs->trans('CliChaumeilCronMissingCategories');
			$message = $this->error . ' (ids: ' . $newCategoryId . ', ' . $existingCategoryId . ')';
			if (!in_array($message, $this->errors, true)) {
				$this->errors[] = $message;
			}
			$this->output = $this->buildOutput(0, 0, $runId, 0, 0, 0, 0);
			dol_syslog(__METHOD__ . ' run=' . $runId . ' ' . implode(' | ', $this->errors), LOG_ERR);
			return -1;
		}

		// Time thresholds for segmentation rules (datef comparisons).
		$oldThreshold = dol_time_plus_duree(dol_now(), -12, 'm');
		$recentThreshold = dol_time_plus_duree(dol_now(), -18, 'm');

		// Fetch only thirdparties that need a tag change to reduce load on large volumes.
		$rows = $this->fetchThirdpartyToUpdate($oldThreshold, $recentThreshold, $newCategoryId, $existingCategoryId);
		if (!is_array($rows)) {
			$this->error = $langs->trans('CliChaumeilCronFetchError');
			$this->errors[] = $this->error . ' (' . $this->db->lasterror() . ')';
			$this->output = $this->buildOutput(0, 0, $runId, 0, 0, 0, 0);
			dol_syslog(__METHOD__ . ' run=' . $runId . ' ' . implode(' | ', $this->errors), LOG_ERR);
			return -1;
		}

		if (empty($user) || empty($user->id)) {
			$user = new User($this->db);
			$user->fetch(1);
		}

		if (!isModEnabled('agenda')) {
			$this->warnings[] = $langs->trans('CliChaumeilCronAgendaDisabled');
		} elseif (!$user->hasRight('agenda', 'myactions', 'create')) {
			$this->warnings[] = $langs->trans('CliChaumeilCronAgendaNoRights');
		}

		$countNew = 0;
		$countExisting = 0;
		$countAddedNew = 0;
		$countRemovedNew = 0;
		$countAddedExisting = 0;
		$countRemovedExisting = 0;

		$totalEvaluated = count($rows);
		foreach ($rows as $row) {
			$thirdpartyId = (int) $row['rowid'];
			$thirdpartyName = $row['nom'];
			$thirdparty = new Societe($this->db);
			$thirdparty->id = $thirdpartyId;
			$thirdparty->name = $thirdpartyName;
			$thirdparty->entity = (int) $conf->entity;

			$isExisting = !empty($row['should_existing']);

			$targetCategory = $isExisting ? $existingCategory : $newCategory;
			$otherCategory = $isExisting ? $newCategory : $existingCategory;

			$targetHas = !empty($row['has_target']);
			$otherHas = !empty($row['has_other']);

			if ($targetHas && !$otherHas) {
				continue;
			}

			$changeLabel = '';

			// Keep per-thirdparty atomicity: remove/add tags in a local transaction.
			$this->db->begin();
			$localError = false;

			if ($otherHas) {
				$result = $otherCategory->del_type($thirdparty, 'customer');
				if ($result < 0) {
					$localError = true;
					$this->errors[] = $thirdpartyName . ' ' . $langs->trans('CliChaumeilCronThirdpartyError') . ' (' . $otherCategory->error . ')';
					dol_syslog(__METHOD__ . ' run=' . $runId . ' remove category error: ' . $thirdpartyName . ' (' . $otherCategory->error . ')', LOG_ERR);
				} else {
					if ($isExisting) {
						$countRemovedNew++;
					} else {
						$countRemovedExisting++;
					}
					$changeLabel = $otherCategory->label . ' -> ' . $targetCategory->label;
				}
			}

			if (!$targetHas && !$localError) {
				$result = $targetCategory->add_type($thirdparty, 'customer');
				if ($result < 0 && $result != -3) {
					$localError = true;
					$this->errors[] = $thirdpartyName . ' ' . $langs->trans('CliChaumeilCronThirdpartyError') . ' (' . $targetCategory->error . ')';
					dol_syslog(__METHOD__ . ' run=' . $runId . ' add category error: ' . $thirdpartyName . ' (' . $targetCategory->error . ')', LOG_ERR);
				} elseif ($result == -3) {
					dol_syslog(__METHOD__ . ' run=' . $runId . ' add category skipped (already exists) for ' . $thirdpartyName, LOG_DEBUG);
				} else {
					if ($isExisting) {
						$countAddedExisting++;
					} else {
						$countAddedNew++;
					}
					if (empty($changeLabel)) {
						$changeLabel = $targetCategory->label;
					}
				}
			}

			if ($localError) {
				$this->db->rollback();
				continue;
			}

			$this->db->commit();

			if ($isExisting) {
				$countExisting++;
			} else {
				$countNew++;
			}

			// Single event is handled only by the cron (no category add/remove event logging).
			if (!empty($changeLabel)) {
				$this->createSegmentationEvent($thirdparty, $changeLabel, $user);
			}
		}

		$this->output = $this->buildOutput(
			$countNew,
			$countExisting,
			$runId,
			$countAddedNew,
			$countRemovedNew,
			$countAddedExisting,
			$countRemovedExisting,
			$totalEvaluated
		);
		dol_syslog(__METHOD__ . ' end run=' . $runId . ' new=' . $countNew . ' existing=' . $countExisting, LOG_INFO);

		return 0;
	}

	/**
	 * @param Societe $thirdparty
	 * @param string  $changeLabel
	 * @param User    $user
	 * @return void
	 */
	private function createSegmentationEvent(Societe $thirdparty, string $changeLabel, User $user): void
	{
		if (!isModEnabled('agenda') || !$user->hasRight('agenda', 'myactions', 'create')) {
			return;
		}

		$action = new ActionComm($this->db);
		$action->datep = dol_now();
		$action->label = $this->langs->trans('CliChaumeilCronSegmentationLabel', $changeLabel);
		$action->note_private = $this->langs->trans('CliChaumeilCronSegmentationNote', $changeLabel, $user->getFullName($this->langs));
		$action->socid = $thirdparty->id;
		$action->fk_element = $thirdparty->id;
		$action->elementtype = $thirdparty->element;
		$action->user_creation_id = $user->id;
		$action->userownerid = $user->id;
		$action->type_code = 'AC_OTH_AUTO';
		$action->percentage = 100;
		$action->entity = !empty($thirdparty->entity) ? $thirdparty->entity : $this->conf->entity;

		$result = $action->create($user);
		if ($result < 0 && !empty($action->error)) {
			$this->warnings[] = $this->langs->trans('CliChaumeilCronAgendaEventFailed', $thirdparty->name, $action->error);
		}
	}

	/**
	 * @return array<int,array<string,mixed>>|int
	 */
	private function fetchThirdpartyToUpdate(int $oldThreshold, int $recentThreshold, int $newCategoryId, int $existingCategoryId)
	{
		global $conf;

		$category = new Categorie($this->db);
		$targetTable = empty($category->MAP_CAT_TABLE['customer']) ? 'customer' : $category->MAP_CAT_TABLE['customer'];
		$targetFk = empty($category->MAP_CAT_FK['customer']) ? 'customer' : $category->MAP_CAT_FK['customer'];

		$oldThresholdSql = $this->db->idate($oldThreshold);
		$recentThresholdSql = $this->db->idate($recentThreshold);

		$baseFrom = " FROM " . $this->db->prefix() . "societe as s";
		$baseFrom .= " LEFT JOIN " . $this->db->prefix() . "facture as f";
		$baseFrom .= " ON f.fk_soc = s.rowid AND f.entity = s.entity";
		$baseWhere = " WHERE s.entity = " . ((int) $conf->entity);
		$baseWhere .= " AND s.client IN (1,2,3)";
		$baseGroup = " GROUP BY s.rowid";

		$existingSubquery = "SELECT s.rowid" . $baseFrom . $baseWhere . $baseGroup;
		$existingSubquery .= " HAVING COUNT(f.rowid) > 0";
		$existingSubquery .= " AND MIN(f.datef) < '" . $this->db->escape($oldThresholdSql) . "'";
		$existingSubquery .= " AND MAX(f.datef) > '" . $this->db->escape($recentThresholdSql) . "'";

		$rows = array();

		$sql = "SELECT s.rowid, s.nom, 1 as should_existing,";
		$sql .= " CASE WHEN ca.fk_" . $targetFk . " IS NULL THEN 0 ELSE 1 END as has_target,";
		$sql .= " CASE WHEN cn.fk_" . $targetFk . " IS NULL THEN 0 ELSE 1 END as has_other";
		$sql .= " FROM " . $this->db->prefix() . "societe as s";
		$sql .= " INNER JOIN (" . $existingSubquery . ") as sa ON sa.rowid = s.rowid";
		$sql .= " LEFT JOIN " . $this->db->prefix() . "categorie_" . $targetTable . " as ca";
		$sql .= " ON ca.fk_categorie = " . ((int) $existingCategoryId) . " AND ca.fk_" . $targetFk . " = s.rowid";
		$sql .= " LEFT JOIN " . $this->db->prefix() . "categorie_" . $targetTable . " as cn";
		$sql .= " ON cn.fk_categorie = " . ((int) $newCategoryId) . " AND cn.fk_" . $targetFk . " = s.rowid";
		$sql .= " WHERE ca.fk_" . $targetFk . " IS NULL OR cn.fk_" . $targetFk . " IS NOT NULL";

		$resql = $this->db->query($sql);
		if (!$resql) {
			return null;
		}
		while ($obj = $this->db->fetch_object($resql)) {
			$rows[] = array(
				'rowid' => (int) $obj->rowid,
				'nom' => $obj->nom,
				'should_existing' => 1,
				'has_target' => (int) $obj->has_target,
				'has_other' => (int) $obj->has_other,
			);
		}

		$sql = "SELECT s.rowid, s.nom, 0 as should_existing,";
		$sql .= " CASE WHEN cn.fk_" . $targetFk . " IS NULL THEN 0 ELSE 1 END as has_target,";
		$sql .= " CASE WHEN ca.fk_" . $targetFk . " IS NULL THEN 0 ELSE 1 END as has_other";
		$sql .= " FROM " . $this->db->prefix() . "societe as s";
		$sql .= " LEFT JOIN (" . $existingSubquery . ") as sa ON sa.rowid = s.rowid";
		$sql .= " LEFT JOIN " . $this->db->prefix() . "categorie_" . $targetTable . " as cn";
		$sql .= " ON cn.fk_categorie = " . ((int) $newCategoryId) . " AND cn.fk_" . $targetFk . " = s.rowid";
		$sql .= " LEFT JOIN " . $this->db->prefix() . "categorie_" . $targetTable . " as ca";
		$sql .= " ON ca.fk_categorie = " . ((int) $existingCategoryId) . " AND ca.fk_" . $targetFk . " = s.rowid";
		$sql .= " WHERE s.entity = " . ((int) $conf->entity);
		$sql .= " AND s.client IN (1,2,3)";
		$sql .= " AND sa.rowid IS NULL";
		$sql .= " AND (cn.fk_" . $targetFk . " IS NULL OR ca.fk_" . $targetFk . " IS NOT NULL)";

		$resql = $this->db->query($sql);
		if (!$resql) {
			return null;
		}
		while ($obj = $this->db->fetch_object($resql)) {
			$rows[] = array(
				'rowid' => (int) $obj->rowid,
				'nom' => $obj->nom,
				'should_existing' => 0,
				'has_target' => (int) $obj->has_target,
				'has_other' => (int) $obj->has_other,
			);
		}

		return $rows;
	}

	/**
	 * @param int $countNew
	 * @param int $countExisting
	 * @return string
	 */
	private function buildOutput(
		int $countNew,
		int $countExisting,
		string $runId,
		int $countAddedNew,
		int $countRemovedNew,
		int $countAddedExisting,
		int $countRemovedExisting,
		int $totalEvaluated
	): string
	{
		$lines = array();
		$lines[] = $this->langs->trans('CliChaumeilCronReportTitle');
		$lines[] = $this->langs->trans('CliChaumeilCronRunId', $runId);
		$lines[] = $this->langs->trans('CliChaumeilCronShortSummary', $countNew, $countExisting);
		$lines[] = $this->langs->trans('CliChaumeilCronTotalEvaluated', $totalEvaluated, $countNew + $countExisting);
		$lines[] = $this->langs->trans('CliChaumeilCronThresholds', 12, 18);
		$lines[] = $this->langs->trans(
			'CliChaumeilCronCategoryImpact',
			$countAddedNew,
			$countRemovedNew,
			$countAddedExisting,
			$countRemovedExisting
		);

		$uniqueWarnings = array_values(array_unique($this->warnings));
		if (!empty($uniqueWarnings)) {
			$lines[] = '';
			$lines[] = $this->langs->trans('CliChaumeilCronWarningsTitle');
			foreach ($uniqueWarnings as $warning) {
				$lines[] = '- ' . $warning;
			}
		}

		$uniqueErrors = array_values(array_unique($this->errors));
		if (!empty($uniqueErrors)) {
			$lines[] = '';
			$lines[] = '---';
			$lines[] = $this->langs->trans('CliChaumeilCronErrorsTitle');
			sort($uniqueErrors, SORT_NATURAL | SORT_FLAG_CASE);
			foreach ($uniqueErrors as $error) {
				$lines[] = '- ' . $error;
			}
		}

		return implode("\n", $lines);
	}

}
