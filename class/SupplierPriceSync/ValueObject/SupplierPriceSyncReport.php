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

	/** @var int Maximum number of detailed change lines rendered. */
	private const MAX_DETAILED_CHANGES = 100;

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

	/** @var bool Whether the run was a dry-run (computed but nothing written). */
	public bool $dryRun = false;

	/** @var SupplierPriceSyncIssue[] Issues raised during the run. */
	private array $issues = array();

	/** @var array<int,array<string,mixed>> Per-line changes recorded during the run. */
	private array $changes = array();

	/**
	 * @param string $supplierCode Supplier code (for summary/mail subject).
	 */
	public function __construct(private readonly string $supplierCode)
	{
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
	 * Record a per-line change for the detailed report.
	 *
	 * @param string     $type        One of SupplierPriceSyncConstants::CHANGE_*.
	 * @param string     $supplierRef Supplier reference.
	 * @param string     $productRef  Dolibarr product reference.
	 * @param float|null $oldPrice    Previous unit price (null when not applicable).
	 * @param float|null $newPrice    New unit price (null when not applicable).
	 * @param string     $unit        Packaging/price unit label.
	 * @return void
	 */
	public function recordChange(string $type, string $supplierRef, string $productRef, ?float $oldPrice, ?float $newPrice, string $unit): void
	{
		$this->changes[] = array(
			'type' => $type,
			'supplierRef' => $supplierRef,
			'productRef' => $productRef,
			'oldPrice' => $oldPrice,
			'newPrice' => $newPrice,
			'unit' => $unit,
		);
	}

	/**
	 * Count recorded per-line changes.
	 *
	 * @return int
	 */
	public function countChanges(): int
	{
		return count($this->changes);
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
	 * Build the leading lines: dry-run banner (when applicable) and the summary.
	 *
	 * @param Translate $langs Translator.
	 * @return string[]
	 */
	private function headerLines(Translate $langs): array
	{
		$lines = array();
		if ($this->dryRun) {
			$lines[] = $langs->transnoentities('CliChaumeil_SupplierPriceSyncDryRunNotice', (string) count($this->changes));
		}
		$lines[] = $this->translatedSummary($langs);

		return $lines;
	}

	/**
	 * Format a unit price compactly (up to 5 decimals, trailing zeros trimmed).
	 *
	 * @param float $value Price.
	 * @return string
	 */
	private function formatPrice(float $value): string
	{
		$formatted = number_format($value, 5, '.', '');
		if (strpos($formatted, '.') !== false) {
			$formatted = rtrim(rtrim($formatted, '0'), '.');
		}

		return $formatted === '' ? '0' : $formatted;
	}

	/**
	 * Format a recorded change as a readable, translated text line.
	 *
	 * @param array<string,mixed> $change Recorded change.
	 * @param Translate           $langs  Translator.
	 * @return string
	 */
	private function formatChange(array $change, Translate $langs): string
	{
		$labels = array(
			SupplierPriceSyncConstants::CHANGE_UPDATE => 'CliChaumeil_SupplierPriceSyncChangeUpdate',
			SupplierPriceSyncConstants::CHANGE_CREATE => 'CliChaumeil_SupplierPriceSyncChangeCreate',
			SupplierPriceSyncConstants::CHANGE_CLOSE => 'CliChaumeil_SupplierPriceSyncChangeClose',
			SupplierPriceSyncConstants::CHANGE_REACTIVATE => 'CliChaumeil_SupplierPriceSyncChangeReactivate',
		);
		$label = $langs->transnoentities($labels[$change['type']] ?? $change['type']);
		$ref = $change['supplierRef'];
		if ($change['productRef'] !== '') {
			$ref .= ' / ' . $change['productRef'];
		}
		$unit = $change['unit'] !== '' ? ' ' . $change['unit'] : '';

		if ($change['oldPrice'] !== null && $change['newPrice'] !== null) {
			return sprintf('- %s %s : %s → %s%s', $label, $ref, $this->formatPrice($change['oldPrice']), $this->formatPrice($change['newPrice']), $unit);
		}
		if ($change['newPrice'] !== null) {
			return sprintf('- %s %s : %s%s', $label, $ref, $this->formatPrice($change['newPrice']), $unit);
		}

		return sprintf('- %s %s%s', $label, $ref, $change['unit'] !== '' ? ' (' . $change['unit'] . ')' : '');
	}

	/**
	 * Render the change-detail section (header + capped lines), empty when no change.
	 *
	 * @param Translate $langs Translator.
	 * @param int       $cap   Maximum change lines to render.
	 * @return string[]
	 */
	private function renderChangeLines(Translate $langs, int $cap): array
	{
		if ($this->changes === array()) {
			return array();
		}
		$lines = array($langs->transnoentities('CliChaumeil_SupplierPriceSyncChangesHeader'));
		foreach (array_slice($this->changes, 0, $cap) as $change) {
			$lines[] = $this->formatChange($change, $langs);
		}
		$total = count($this->changes);
		if ($total > $cap) {
			$lines[] = $langs->transnoentities('CliChaumeil_SupplierPriceSyncChangesMore', (string) ($total - $cap));
		}

		return $lines;
	}

	/**
	 * Render the issues section (header + lines), empty when no issue.
	 *
	 * @param Translate $langs Translator.
	 * @param int|null  $cap   Maximum issue lines to render (null = uncapped).
	 * @return string[]
	 */
	private function renderIssueLines(Translate $langs, ?int $cap): array
	{
		if ($this->issues === array()) {
			return array();
		}
		$lines = array($langs->transnoentities('CliChaumeil_SupplierPriceSyncIssuesHeader'));
		$shown = $cap === null ? $this->issues : array_slice($this->issues, 0, $cap);
		foreach ($shown as $issue) {
			$lines[] = $this->formatIssue($issue, $langs);
		}
		$total = count($this->issues);
		if ($cap !== null && $total > $cap) {
			$lines[] = sprintf('... +%d (cap %d)', $total - $cap, $cap);
		}

		return $lines;
	}

	/**
	 * Build the cron output (dry-run banner + summary + changes + capped issues).
	 *
	 * @param Translate $langs Translator.
	 * @return string
	 */
	public function buildCronOutput(Translate $langs): string
	{
		$lines = $this->headerLines($langs);
		$lines = array_merge($lines, $this->renderChangeLines($langs, self::MAX_DETAILED_CHANGES));
		$lines = array_merge($lines, $this->renderIssueLines($langs, self::MAX_DETAILED_ISSUES));

		return implode("\n", $lines);
	}

	/**
	 * Build the mail subject (prefixed with a dry-run tag in simulation).
	 *
	 * @param Translate $langs Translator (already loaded with the module file).
	 * @return string
	 */
	public function buildMailSubject(Translate $langs): string
	{
		$subject = $langs->transnoentities('CliChaumeil_SupplierPriceSyncMailSubject', $this->supplierCode, count($this->issues));
		if ($this->dryRun) {
			$subject = $langs->transnoentities('CliChaumeil_SupplierPriceSyncDryRunTag') . ' ' . $subject;
		}

		return $subject;
	}

	/**
	 * Build the full mail body (dry-run banner + summary + changes + all issues).
	 *
	 * @param Translate $langs Translator (already loaded with the module file).
	 * @return string
	 */
	public function buildMailBody(Translate $langs): string
	{
		$lines = $this->headerLines($langs);
		$lines[] = '';
		$lines = array_merge($lines, $this->renderChangeLines($langs, self::MAX_DETAILED_CHANGES));
		$lines = array_merge($lines, $this->renderIssueLines($langs, null));

		return implode("\n", $lines);
	}
}
