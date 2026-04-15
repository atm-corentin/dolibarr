<?php
declare(strict_types=1);

require_once DOL_DOCUMENT_ROOT . '/core/class/commonobjectline.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once __DIR__ . '/CliChaumeilPropalDefaultLineService.class.php';

/**
 * Provide view-level decisions for protected proposal lines.
 */
class CliChaumeilPropalDefaultLineViewHelper
{
	/**
	 * Tell whether delete action should be hidden for one line.
	 *
	 * @param CommonObjectLine $line Line object.
	 * @param User             $user Current user.
	 * @return bool
	 */
	public function shouldHideDeleteForLine(CommonObjectLine $line, User $user): bool
	{
		if (!$this->isProtectedLine($line)) {
			return false;
		}

		return !$this->canManageProtectedLine($user);
	}

	/**
	 * Tell whether edit action should be hidden for one line.
	 *
	 * @param CommonObjectLine $line Line object.
	 * @param User             $user Current user.
	 * @return bool
	 */
	public function shouldHideEditForLine(CommonObjectLine $line, User $user): bool
	{
		if (!$this->isProtectedLine($line)) {
			return false;
		}

		return !$this->canManageProtectedLine($user);
	}

	/**
	 * Tell whether quick price controls should be disabled for one line.
	 *
	 * @param CommonObjectLine $line Line object.
	 * @param User             $user Current user.
	 * @return bool
	 */
	public function shouldDisableQuickPriceForLine(CommonObjectLine $line, User $user): bool
	{
		if (!$this->isProtectedLine($line)) {
			return false;
		}

		return !$this->canManageProtectedLine($user);
	}

	/**
	 * Tell whether the line is protected.
	 *
	 * @param CommonObjectLine $line Line object.
	 * @return bool
	 */
	public function isProtectedLine(CommonObjectLine $line): bool
	{
		$currentValue = $line->array_options[CliChaumeilPropalDefaultLineService::EXTRAFIELD_OPTION_KEY] ?? null;
		return ((int) $currentValue) === 1;
	}

	/**
	 * Tell whether the user can manage protected lines.
	 *
	 * @param User $user Current user.
	 * @return bool
	 */
	private function canManageProtectedLine(User $user): bool
	{
		return $user->hasRight(
			CliChaumeilPropalDefaultLineService::RIGHT_MODULE,
			CliChaumeilPropalDefaultLineService::RIGHT_FEATURE,
			CliChaumeilPropalDefaultLineService::RIGHT_ACTION
		);
	}
}
