<?php
/* Copyright (C) 2026 ATM Consulting
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * @file    test/phpunit/SharedPropalCloneEligibleTest.php
 * @brief   Unit tests for isSharedPropalCloneEligible (R5-DIV).
 *
 * Run: phpunit htdocs/custom/clichaumeil/test/phpunit/SharedPropalCloneEligibleTest.php
 *
 * Tests the private eligibility guard for the cross-entity proposal clone feature.
 * Uses Reflection to reach the private method — justified because the method contains
 * real business logic (entity comparison, cached-right check) with no DB or UI deps.
 * doActions / formConfirm are not tested here (hooks + header() = outside scope per guide).
 *
 * @backupGlobals          disabled
 * @backupStaticAttributes enabled
 */

global $conf, $user, $langs, $db;

require_once dirname(__FILE__) . '/../../../../master.inc.php';
require_once dirname(__FILE__) . '/../../../../../test/phpunit/CommonClassTest.class.php';
require_once dirname(__FILE__) . '/../../class/actions_clichaumeil.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';

/**
 * Tests for ActionsCliChaumeil::isSharedPropalCloneEligible.
 *
 * The method is private and accessed via ReflectionMethod.
 */
class SharedPropalCloneEligibleTest extends CommonClassTest
{
	/** @var ActionsCliChaumeil */
	private $hook;

	/** @var ReflectionMethod */
	private $isEligible;

	/** @var ReflectionProperty */
	private $nativeCreerProp;

	/** @var int Original $conf->entity restored after each test */
	private $savedEntity;

	/**
	 * No DB writes in this class — begin/rollback not needed.
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
	}

	/**
	 * No DB writes in this class — begin/rollback not needed.
	 *
	 * @return void
	 */
	public static function tearDownAfterClass(): void
	{
	}

	/**
	 * Initialise Reflection accessors and fix conf->entity to 1 for each test.
	 *
	 * @return void
	 */
	protected function setUp(): void
	{
		global $db, $conf;

		$this->hook = new ActionsCliChaumeil($db);

		$rc = new ReflectionClass(ActionsCliChaumeil::class);

		$this->isEligible = $rc->getMethod('isSharedPropalCloneEligible');
		$this->isEligible->setAccessible(true);

		$this->nativeCreerProp = $rc->getProperty('nativePropalCreer');
		$this->nativeCreerProp->setAccessible(true);

		$this->savedEntity = (int) $conf->entity;
		$conf->entity      = 1;
	}

	/**
	 * Restore original conf->entity.
	 *
	 * @return void
	 */
	protected function tearDown(): void
	{
		global $conf;
		$conf->entity = $this->savedEntity;
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Build a minimal Propal stub (no DB save needed).
	 *
	 * @param int $entity Entity id to assign to the proposal.
	 * @param int $id     Proposal id (default 1).
	 *
	 * @return Propal
	 */
	private function makePropal(int $entity, int $id = 1): Propal
	{
		global $db;

		$obj         = new Propal($db);
		$obj->entity = $entity;
		$obj->id     = $id;
		$obj->socid  = 1;
		return $obj;
	}

	// -----------------------------------------------------------------------
	// Tests
	// -----------------------------------------------------------------------

	/**
	 * Context string that does not contain 'propalcard' must return false.
	 *
	 * @return void
	 */
	public function testReturnsFalseWhenContextLacksPropalcard(): void
	{
		global $user, $conf;

		$this->nativeCreerProp->setValue($this->hook, true);
		$propal = $this->makePropal(2);

		$result = $this->isEligible->invoke($this->hook, 'globalcard', $propal, $user, $conf);
		$this->assertFalse($result, 'Context without propalcard must return false');
	}

	/**
	 * A non-Propal object must return false regardless of other conditions.
	 *
	 * @return void
	 */
	public function testReturnsFalseWhenObjectIsNotPropal(): void
	{
		global $user, $conf, $db;

		$this->nativeCreerProp->setValue($this->hook, true);

		require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
		$order = new Commande($db);

		$result = $this->isEligible->invoke($this->hook, 'propalcard', $order, $user, $conf);
		$this->assertFalse($result, 'Non-Propal object must return false');
	}

	/**
	 * A Propal with id=0 (not yet persisted) must return false.
	 *
	 * @return void
	 */
	public function testReturnsFalseWhenPropalHasNoId(): void
	{
		global $user, $conf;

		$this->nativeCreerProp->setValue($this->hook, true);
		$propal     = $this->makePropal(2);
		$propal->id = 0;

		$result = $this->isEligible->invoke($this->hook, 'propalcard', $propal, $user, $conf);
		$this->assertFalse($result, 'Propal without id must return false');
	}

	/**
	 * A Propal belonging to the current entity is not cross-entity — must return false.
	 *
	 * @return void
	 */
	public function testReturnsFalseWhenPropalBelongsToCurrentEntity(): void
	{
		global $user, $conf;

		$this->nativeCreerProp->setValue($this->hook, true);
		$propal = $this->makePropal((int) $conf->entity);

		$result = $this->isEligible->invoke($this->hook, 'propalcard', $propal, $user, $conf);
		$this->assertFalse($result, 'Proposal from current entity must return false (not cross-entity)');
	}

	/**
	 * Cached nativePropalCreer=false means the user has no native right — must return false.
	 *
	 * @return void
	 */
	public function testReturnsFalseWhenNativeRightIsFalse(): void
	{
		global $user, $conf;

		$this->nativeCreerProp->setValue($this->hook, false);
		$propal = $this->makePropal(2);

		$result = $this->isEligible->invoke($this->hook, 'propalcard', $propal, $user, $conf);
		$this->assertFalse($result, 'Cached nativePropalCreer=false must return false even for cross-entity proposal');
	}

	/**
	 * Cross-entity proposal with cached right=true is the eligible case — must return true.
	 *
	 * @return void
	 */
	public function testReturnsTrueForCrossEntityPropalWithCachedRight(): void
	{
		global $user, $conf;

		$this->nativeCreerProp->setValue($this->hook, true);
		$propal = $this->makePropal(2);

		$result = $this->isEligible->invoke($this->hook, 'propalcard', $propal, $user, $conf);
		$this->assertTrue($result, 'Cross-entity proposal with nativePropalCreer=true must return true');
	}

	/**
	 * A colon-separated context string containing 'propalcard' must still match.
	 *
	 * @return void
	 */
	public function testContextStringWithMultipleContextsStillMatches(): void
	{
		global $user, $conf;

		$this->nativeCreerProp->setValue($this->hook, true);
		$propal = $this->makePropal(2);

		$result = $this->isEligible->invoke($this->hook, 'main:propalcard:globalcard', $propal, $user, $conf);
		$this->assertTrue($result, 'Context string containing propalcard among others must return true');
	}
}
