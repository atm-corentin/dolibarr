<?php
declare(strict_types=1);

require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once __DIR__ . '/CliChaumeilPropalDefaultLineService.class.php';

/**
 * Bridge VT-18 protected proposal lines with the MassAction UI/backend flows.
 */
class CliChaumeilMassActionPropalGuard
{
	/**
	 * Shared protected line service.
	 *
	 * @var CliChaumeilPropalDefaultLineService
	 */
	private CliChaumeilPropalDefaultLineService $propalDefaultLineService;

	/**
	 * Constructor.
	 *
	 * @param DoliDB                              $db                        Database handler.
	 * @param CliChaumeilPropalDefaultLineService|null $propalDefaultLineService Shared service.
	 */
	public function __construct(DoliDB $db, ?CliChaumeilPropalDefaultLineService $propalDefaultLineService = null)
	{
		$this->propalDefaultLineService = $propalDefaultLineService ?: new CliChaumeilPropalDefaultLineService($db);
	}

	/**
	 * Return all protected line identifiers for the given proposal.
	 *
	 * @param Propal $propal Proposal object.
	 * @return int[]
	 */
	public function getProtectedLineIds(Propal $propal): array
	{
		return $this->propalDefaultLineService->getProtectedLineIds($propal);
	}

	/**
	 * Build the frontend payload used to neutralize MassAction on protected lines.
	 *
	 * @param Propal $propal Proposal object.
	 * @param User   $user   Current user.
	 * @return array<string,mixed>
	 */
	public function getMassActionGuardPayload(Propal $propal, User $user): array
	{
		global $langs;

		if ($this->propalDefaultLineService->canManageProtectedLine($user)) {
			return array();
		}

		$protectedLineIds = $this->getProtectedLineIds($propal);
		if (empty($protectedLineIds)) {
			return array();
		}

		return array(
			'protectedLineIds' => $protectedLineIds,
			'checkboxSelector' => '.checkforselect',
			'selectedLinesSelector' => '#selectedLines',
			'forbiddenMessage' => $langs->trans('CLICHAUMEIL_DEFAULT_PROPAL_LINE_MASSACTION_FORBIDDEN'),
		);
	}

	/**
	 * Tell whether the provided selection contains at least one protected line.
	 *
	 * @param Propal      $propal  Proposal object.
	 * @param array<int,mixed> $lineIds Selected line identifiers.
	 * @return bool
	 */
	public function selectionContainsProtectedLines(Propal $propal, array $lineIds): bool
	{
		$normalizedLineIds = $this->normalizeLineIds($lineIds);
		if (empty($normalizedLineIds)) {
			return false;
		}

		$protectedLineIds = $this->getProtectedLineIds($propal);
		if (empty($protectedLineIds)) {
			return false;
		}

		return count(array_intersect($normalizedLineIds, $protectedLineIds)) > 0;
	}

	/**
	 * Filter protected proposal line ids out of a MassAction selection.
	 *
	 * @param Propal            $propal  Proposal object.
	 * @param array<int,mixed>  $lineIds Raw selected line identifiers.
	 * @return int[]
	 */
	public function filterProtectedLineIdsFromSelection(Propal $propal, array $lineIds): array
	{
		$normalizedLineIds = $this->normalizeLineIds($lineIds);
		if (empty($normalizedLineIds)) {
			return array();
		}

		$protectedLineIds = $this->getProtectedLineIds($propal);
		if (empty($protectedLineIds)) {
			return $normalizedLineIds;
		}

		return array_values(array_filter($normalizedLineIds, static function (int $lineId) use ($protectedLineIds) {
			return !in_array($lineId, $protectedLineIds, true);
		}));
	}

	/**
	 * Normalize line identifiers into a unique list of positive integers.
	 *
	 * @param array<int,mixed> $lineIds Raw line identifiers.
	 * @return int[]
	 */
	private function normalizeLineIds(array $lineIds): array
	{
		$normalizedLineIds = array();

		foreach ($lineIds as $lineId) {
			$normalizedLineId = (int) $lineId;
			if ($normalizedLineId <= 0) {
				continue;
			}

			$normalizedLineIds[$normalizedLineId] = $normalizedLineId;
		}

		return array_values($normalizedLineIds);
	}
}
