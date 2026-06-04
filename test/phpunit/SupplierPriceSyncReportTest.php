<?php
/* Copyright (C) 2026 ATM Consulting
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * @file    test/phpunit/SupplierPriceSyncReportTest.php
 * @brief   Unit tests for SupplierPriceSyncReport (ACHT-2-ANTALIS).
 *
 * Run: phpunit htdocs/custom/clichaumeil/test/phpunit/SupplierPriceSyncReportTest.php
 *
 * @backupGlobals          disabled
 * @backupStaticAttributes enabled
 */

declare(strict_types=1);

global $conf, $user, $langs, $db;

require_once dirname(__FILE__) . '/../../../../master.inc.php';
require_once dirname(__FILE__) . '/../../../../../test/phpunit/CommonClassTest.class.php';
require_once dirname(__FILE__) . '/../../class/SupplierPriceSync/SupplierPriceSyncConstants.php';
require_once dirname(__FILE__) . '/../../class/SupplierPriceSync/ValueObject/SupplierPriceSyncIssue.php';
require_once dirname(__FILE__) . '/../../class/SupplierPriceSync/ValueObject/SupplierPriceSyncReport.php';

/**
 * Tests for SupplierPriceSyncReport.
 */
class SupplierPriceSyncReportTest extends CommonClassTest
{
	/**
	 * Counters render in the cron output and no error means no failure.
	 *
	 * @return void
	 */
	public function testCountersAndNoFailure(): void
	{
		global $langs;
		$langs->loadLangs(array('clichaumeil@clichaumeil'));
		$report = new SupplierPriceSyncReport('ANTALIS');
		$report->incrementUpdated();
		$report->incrementUpdated();
		$report->incrementClosed();

		$out = $report->buildCronOutput($langs);
		$this->assertStringContainsString('ANTALIS', $out);
		$this->assertStringContainsString('2 mises à jour', $out);
		$this->assertStringContainsString('1 clôturées', $out);
		$this->assertFalse($report->hasFailures());
	}

	/**
	 * An error-severity issue marks the run as failed and appears in the output.
	 *
	 * @return void
	 */
	public function testErrorIssueMarksFailure(): void
	{
		global $langs;
		$langs->loadLangs(array('clichaumeil@clichaumeil'));
		$report = new SupplierPriceSyncReport('ANTALIS');
		$report->addIssue(new SupplierPriceSyncIssue(
			SupplierPriceSyncIssue::SEVERITY_ERROR,
			SupplierPriceSyncConstants::ISSUE_REFERENCE_NOT_FOUND,
			'',
			'264910',
			'P1',
			500.0
		));

		$this->assertTrue($report->hasFailures());
		$out = $report->buildCronOutput($langs);
		$this->assertStringContainsString('264910', $out);
		$this->assertStringContainsString('Erreur', $out);
	}

	/**
	 * The cron output caps detailed issues at 50 and mentions the overflow.
	 *
	 * @return void
	 */
	public function testCronOutputCapsAtFifty(): void
	{
		global $langs;
		$langs->loadLangs(array('clichaumeil@clichaumeil'));
		$report = new SupplierPriceSyncReport('ANTALIS');
		for ($i = 0; $i < 60; $i++) {
			$report->addIssue(new SupplierPriceSyncIssue(
				SupplierPriceSyncIssue::SEVERITY_ERROR,
				'X',
				'e' . $i,
				(string) $i,
				'P',
				1.0
			));
		}

		$out = $report->buildCronOutput($langs);
		// 50 capped issue bullets are rendered, plus an overflow marker.
		$this->assertSame(50, substr_count($out, "\n- "));
		$this->assertStringContainsString('cap 50', $out);
	}

	/**
	 * Dry-run output shows the simulation banner and the per-line change detail.
	 *
	 * @return void
	 */
	public function testDryRunBannerAndChangeDetail(): void
	{
		global $langs;
		$langs->loadLangs(array('clichaumeil@clichaumeil'));
		$report = new SupplierPriceSyncReport('ANTALIS');
		$report->dryRun = true;
		$report->incrementUpdated();
		$report->recordChange(
			SupplierPriceSyncConstants::CHANGE_UPDATE,
			'264910',
			'P1',
			0.048,
			0.0321,
			'Feuille'
		);

		$out = $report->buildCronOutput($langs);
		$this->assertStringContainsString('SIMULATION', $out);
		$this->assertStringContainsString('264910', $out);
		$this->assertStringContainsString('0.048', $out);
		$this->assertStringContainsString('0.0321', $out);
		$this->assertStringContainsString('Feuille', $out);
		$this->assertSame(1, $report->countChanges());
	}
}
