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

require_once __DIR__ . '/../SupplierPriceSyncConstants.php';
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

	/** @var int Maximum number of detailed issues rendered in the mail body. */
	private const MAX_DETAILED_ISSUES_MAIL = 200;

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

	/** @var int Run timestamp (set by the cron; 0 when not provided). */
	public int $executedAt = 0;

	/** @var float Run duration in seconds (set by the cron; 0 when not provided). */
	public float $durationSeconds = 0.0;

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
	 * Decide whether the report should be emailed, given the configured policy.
	 *
	 * @param string $policy One of SupplierPriceSyncConstants::MAIL_POLICY_*.
	 * @return bool
	 */
	public function shouldNotify(string $policy): bool
	{
		switch ($policy) {
			case SupplierPriceSyncConstants::MAIL_POLICY_NEVER:
				return false;
			case SupplierPriceSyncConstants::MAIL_POLICY_ALWAYS:
				return true;
			case SupplierPriceSyncConstants::MAIL_POLICY_ERRORS_WARNINGS:
				return $this->countErrors() > 0 || $this->countWarnings() > 0;
			case SupplierPriceSyncConstants::MAIL_POLICY_ERRORS:
			default:
				return $this->countErrors() > 0;
		}
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
	 * Substitute the run counters into a {token} template.
	 *
	 * Token substitution (not trans() %s placeholders): trans() sprintf()s its own
	 * up-to-4 params, so a 10-placeholder template would throw inside trans().
	 *
	 * @param string $template Template containing {sup}, {scanned}, ... tokens.
	 * @return string
	 */
	private function fillCounters(string $template): string
	{
		return strtr(
			$template,
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
	 * Build the human-readable, translated summary block (several lines).
	 *
	 * @param Translate $langs Translator (module file loaded).
	 * @return string[]
	 */
	private function translatedSummary(Translate $langs): array
	{
		return array(
			$this->fillCounters($langs->transnoentities('CliChaumeil_SupplierPriceSyncSummaryHead')),
			$this->fillCounters($langs->transnoentities('CliChaumeil_SupplierPriceSyncSummaryChanges')),
			$this->fillCounters($langs->transnoentities('CliChaumeil_SupplierPriceSyncSummaryNeutral')),
			$this->fillCounters($langs->transnoentities('CliChaumeil_SupplierPriceSyncSummaryIssues')),
		);
	}

	/**
	 * Build a one-line readable summary (persisted and shown on the admin page).
	 *
	 * @param Translate $langs Translator (module file loaded).
	 * @return string
	 */
	public function compactSummary(Translate $langs): string
	{
		return $this->fillCounters($langs->transnoentities('CliChaumeil_SupplierPriceSyncCompactSummary'));
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
		if ($this->executedAt > 0) {
			$lines[] = $langs->transnoentities(
				'CliChaumeil_SupplierPriceSyncRunMeta',
				dol_print_date($this->executedAt, 'dayhour'),
				(string) round($this->durationSeconds, 1)
			);
		}

		return array_merge($lines, $this->translatedSummary($langs));
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
			$variation = '';
			if ((float) $change['oldPrice'] !== 0.0) {
				$variation = sprintf(' (%+.0f%%)', (($change['newPrice'] - $change['oldPrice']) / $change['oldPrice']) * 100);
			}

			return sprintf('- %s %s : %s → %s%s%s', $label, $ref, $this->formatPrice($change['oldPrice']), $this->formatPrice($change['newPrice']), $unit, $variation);
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
			$lines[] = $langs->transnoentities('CliChaumeil_SupplierPriceSyncIssuesMore', (string) ($total - $cap));
		}

		return $lines;
	}

	/**
	 * Join sections (arrays of lines), dropping empty ones and inserting an optional
	 * blank line between consecutive non-empty sections.
	 *
	 * @param string[][] $sections     Sections, each an array of lines.
	 * @param bool       $blankBetween Insert a blank line between sections.
	 * @return string
	 */
	private function joinSections(array $sections, bool $blankBetween): string
	{
		$lines = array();
		foreach ($sections as $section) {
			if ($section === array()) {
				continue;
			}
			if ($lines !== array() && $blankBetween) {
				$lines[] = '';
			}
			$lines = array_merge($lines, $section);
		}

		return implode("\n", $lines);
	}

	/**
	 * Build the cron output. Anomalies come first (they are the reason to look), then
	 * the per-line changes (dry-run banner + summary header on top).
	 *
	 * @param Translate $langs Translator.
	 * @return string
	 */
	public function buildCronOutput(Translate $langs): string
	{
		return $this->joinSections(
			array(
				$this->headerLines($langs),
				$this->renderIssueLines($langs, self::MAX_DETAILED_ISSUES),
				$this->renderChangeLines($langs, self::MAX_DETAILED_CHANGES),
			),
			false
		);
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
		return $this->joinSections(
			array(
				$this->headerLines($langs),
				$this->renderIssueLines($langs, self::MAX_DETAILED_ISSUES_MAIL),
				$this->renderChangeLines($langs, self::MAX_DETAILED_CHANGES),
			),
			true
		);
	}

	/**
	 * Render a section (header line + "- " bullet lines) as an escaped HTML block.
	 *
	 * Reuses the same text renderers as the plain body (caps and markers stay in sync);
	 * only the presentation differs.
	 *
	 * @param string[] $lines       Section lines (first = header, rest = bullets).
	 * @param string   $headerColor CSS colour for the header.
	 * @return string
	 */
	private function sectionHtml(array $lines, string $headerColor): string
	{
		if ($lines === array()) {
			return '';
		}
		$header = array_shift($lines);
		$html = '<h3 style="margin:14px 0 4px;font-size:14px;color:' . $headerColor . '">' . dol_escape_htmltag($header) . '</h3>';
		$html .= '<ul style="margin:0;padding-left:18px">';
		foreach ($lines as $line) {
			$text = preg_replace('/^- /', '', $line);
			$html .= '<li>' . dol_escape_htmltag($text) . '</li>';
		}
		$html .= '</ul>';

		return $html;
	}

	/**
	 * Build the HTML mail body (same content as buildMailBody(), richer presentation).
	 *
	 * @param Translate $langs Translator (module file loaded).
	 * @return string
	 */
	public function buildMailBodyHtml(Translate $langs): string
	{
		$html = '<div style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#333">';

		if ($this->dryRun) {
			$html .= '<div style="background:#fff3cd;border:1px solid #ffeeba;padding:8px;margin-bottom:10px"><strong>'
				. dol_escape_htmltag($langs->transnoentities('CliChaumeil_SupplierPriceSyncDryRunNotice', (string) count($this->changes))) . '</strong></div>';
		}
		if ($this->executedAt > 0) {
			$html .= '<p style="color:#888;margin:0 0 8px">' . dol_escape_htmltag($langs->transnoentities(
				'CliChaumeil_SupplierPriceSyncRunMeta',
				dol_print_date($this->executedAt, 'dayhour'),
				(string) round($this->durationSeconds, 1)
			)) . '</p>';
		}

		// Summary block (first line emphasised).
		$summary = $this->translatedSummary($langs);
		$html .= '<div style="margin-bottom:6px"><strong>' . dol_escape_htmltag(array_shift($summary)) . '</strong></div>';
		foreach ($summary as $summaryLine) {
			$html .= '<div>' . dol_escape_htmltag($summaryLine) . '</div>';
		}

		// Anomalies first (the reason the mail is sent), then the per-line changes.
		$html .= $this->sectionHtml($this->renderIssueLines($langs, self::MAX_DETAILED_ISSUES_MAIL), '#c0392b');
		$html .= $this->sectionHtml($this->renderChangeLines($langs, self::MAX_DETAILED_CHANGES), '#2c3e50');
		$html .= '</div>';

		return $html;
	}
}
