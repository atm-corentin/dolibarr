<?php
/* Copyright (C) 2026 ATM Consulting
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    class/SupplierPriceSync/ValueObject/SupplierPriceSyncReport.php
 * \ingroup clichaumeil
 * \brief   Accumulates counters and issues of a synchronisation run.
 */

declare(strict_types=1);

require_once __DIR__ . '/SupplierPriceSyncIssue.php';

/**
 * Mutable report built during a synchronisation run.
 *
 * Provides counters, a capped cron output and a fuller mail body.
 */
final class SupplierPriceSyncReport
{
	/** @var int Maximum number of detailed issues rendered in the cron output. */
	private const MAX_DETAILED_ISSUES = 50;

	/** @var int Number of candidate lines scanned. */
	public int $scanned = 0;
	/** @var int Number of lines actually requested to the API. */
	public int $requested = 0;
	/** @var int Number of lines skipped (e.g. unmapped unit). */
	public int $skipped = 0;
	/** @var int Number of lines left unchanged. */
	public int $unchanged = 0;
	/** @var int Number of lines updated. */
	public int $updated = 0;
	/** @var int Number of lines created. */
	public int $created = 0;
	/** @var int Number of lines closed (deactivated). */
	public int $closed = 0;
	/** @var int Number of lines reactivated. */
	public int $reactivated = 0;

	/** @var SupplierPriceSyncIssue[] Issues raised during the run. */
	private array $issues = array();

	/**
	 * @param string $supplierCode Supplier code (for summary/mail subject).
	 */
	public function __construct(private readonly string $supplierCode)
	{
	}

	/**
	 * Increment the scanned counter.
	 *
	 * @return void
	 */
	public function incrementScanned(): void
	{
		$this->scanned++;
	}

	/**
	 * Increment the requested counter.
	 *
	 * @return void
	 */
	public function incrementRequested(): void
	{
		$this->requested++;
	}

	/**
	 * Increment the skipped counter.
	 *
	 * @return void
	 */
	public function incrementSkipped(): void
	{
		$this->skipped++;
	}

	/**
	 * Increment the unchanged counter.
	 *
	 * @return void
	 */
	public function incrementUnchanged(): void
	{
		$this->unchanged++;
	}

	/**
	 * Increment the updated counter.
	 *
	 * @return void
	 */
	public function incrementUpdated(): void
	{
		$this->updated++;
	}

	/**
	 * Increment the created counter.
	 *
	 * @return void
	 */
	public function incrementCreated(): void
	{
		$this->created++;
	}

	/**
	 * Increment the closed counter.
	 *
	 * @return void
	 */
	public function incrementClosed(): void
	{
		$this->closed++;
	}

	/**
	 * Increment the reactivated counter.
	 *
	 * @return void
	 */
	public function incrementReactivated(): void
	{
		$this->reactivated++;
	}

	/**
	 * Register an issue.
	 *
	 * @param SupplierPriceSyncIssue $issue Issue to record.
	 * @return void
	 */
	public function addIssue(SupplierPriceSyncIssue $issue): void
	{
		$this->issues[] = $issue;
	}

	/**
	 * Tell whether at least one blocking error occurred.
	 *
	 * @return bool
	 */
	public function hasFailures(): bool
	{
		return $this->countErrors() > 0;
	}

	/**
	 * Count blocking (error) issues.
	 *
	 * @return int
	 */
	public function countErrors(): int
	{
		$count = 0;
		foreach ($this->issues as $issue) {
			if ($issue->isError()) {
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Count non-blocking (warning) issues.
	 *
	 * @return int
	 */
	public function countWarnings(): int
	{
		return count($this->issues) - $this->countErrors();
	}

	/**
	 * Build the short summary line (errors and warnings are counted separately).
	 *
	 * @return string
	 */
	public function summaryLine(): string
	{
		return sprintf(
			'[%s] scanned=%d requested=%d updated=%d created=%d closed=%d reactivated=%d unchanged=%d skipped=%d errors=%d warnings=%d',
			$this->supplierCode,
			$this->scanned,
			$this->requested,
			$this->updated,
			$this->created,
			$this->closed,
			$this->reactivated,
			$this->unchanged,
			$this->skipped,
			$this->countErrors(),
			$this->countWarnings()
		);
	}

	/**
	 * Build the human-readable, translated summary line (for cron output and mail).
	 *
	 * @param Translate $langs Translator (module file loaded).
	 * @return string
	 */
	private function translatedSummary(Translate $langs): string
	{
		// Token substitution (not trans() %s placeholders): trans() sprintf()s its
		// own up-to-4 params, so a 10-placeholder template would throw inside trans().
		return strtr(
			$langs->transnoentities('CliChaumeil_SupplierPriceSyncSummary'),
			array(
				'{sup}' => $this->supplierCode,
				'{scanned}' => (string) $this->scanned,
				'{updated}' => (string) $this->updated,
				'{created}' => (string) $this->created,
				'{closed}' => (string) $this->closed,
				'{reactivated}' => (string) $this->reactivated,
				'{unchanged}' => (string) $this->unchanged,
				'{skipped}' => (string) $this->skipped,
				'{errors}' => (string) $this->countErrors(),
				'{warnings}' => (string) $this->countWarnings(),
			)
		);
	}

	/**
	 * Render a single issue as a readable, translated text line.
	 *
	 * @param SupplierPriceSyncIssue $issue Issue to render.
	 * @param Translate              $langs Translator.
	 * @return string
	 */
	private function formatIssue(SupplierPriceSyncIssue $issue, Translate $langs): string
	{
		$severity = $langs->transnoentities($issue->isError()
			? 'CliChaumeil_SupplierPriceSyncSeverityError'
			: 'CliChaumeil_SupplierPriceSyncSeverityWarning');
		$message = $langs->transnoentities('CliChaumeil_SupplierPriceSync_' . $issue->code);
		$context = $issue->supplierRef !== ''
			? ' ' . $langs->transnoentities('CliChaumeil_SupplierPriceSyncRefQty', $issue->supplierRef, (string) $issue->quantity)
			: '';
		$detail = ($issue->message !== '' && $issue->message !== $issue->supplierRef) ? ' — ' . $issue->message : '';

		return sprintf('- [%s] %s%s%s', $severity, $message, $context, $detail);
	}

	/**
	 * Build the cron output (summary + capped issue list).
	 *
	 * @param Translate $langs Translator.
	 * @return string
	 */
	public function buildCronOutput(Translate $langs): string
	{
		$lines = array($this->translatedSummary($langs));

		$shown = array_slice($this->issues, 0, self::MAX_DETAILED_ISSUES);
		foreach ($shown as $issue) {
			$lines[] = $this->formatIssue($issue, $langs);
		}

		$total = count($this->issues);
		if ($total > self::MAX_DETAILED_ISSUES) {
			$lines[] = sprintf('... +%d (cap %d)', $total - self::MAX_DETAILED_ISSUES, self::MAX_DETAILED_ISSUES);
		}

		return implode("\n", $lines);
	}

	/**
	 * Build the mail subject.
	 *
	 * @param Translate $langs Translator (already loaded with the module file).
	 * @return string
	 */
	public function buildMailSubject(Translate $langs): string
	{
		return $langs->transnoentities('CliChaumeil_SupplierPriceSyncMailSubject', $this->supplierCode, count($this->issues));
	}

	/**
	 * Build the full mail body (all issues, uncapped).
	 *
	 * @param Translate $langs Translator (already loaded with the module file).
	 * @return string
	 */
	public function buildMailBody(Translate $langs): string
	{
		$lines = array($this->translatedSummary($langs), '');
		foreach ($this->issues as $issue) {
			$lines[] = $this->formatIssue($issue, $langs);
		}

		return implode("\n", $lines);
	}
}
