<?php
declare(strict_types=1);

/* Copyright (C) 2026 ATM Consulting <support@atm-consulting.fr> */

global $conf, $user, $langs, $db;
require_once dirname(__FILE__).'/../../../../master.inc.php';
require_once dirname(__FILE__).'/../../../../comm/propal/class/propal.class.php';
require_once dirname(__FILE__).'/../../../../commande/class/commande.class.php';
require_once dirname(__FILE__).'/../../class/actions_clichaumeil.class.php';
require_once dirname(__FILE__).'/../../../../../test/phpunit/CommonClassTest.class.php';

/**
 * Integration test: the createFrom hook applies VT-25 to genuine clones only,
 * and leaves the devis->commande transformation untouched.
 *
 * @backupGlobals disabled
 */
class ActionsClichaumeilCreateFromCloneCostTest extends CommonClassTest
{
	/**
	 * devis->commande transformation: objFrom is a Propal while the hooked object is a
	 * Commande → hook must return 0 without recomputing the line buy price.
	 *
	 * @return void
	 */
	public function testDevisToCommandeTransformationIsIgnored(): void
	{
		global $db;

		$propal   = new Propal($db);
		$commande = new Commande($db);

		$actions = new ActionsClichaumeil($db);
		$parameters = array('objFrom' => $propal);
		$action = '';
		$result = $actions->createFrom($parameters, $commande, $action, new HookManager($db));

		$this->assertSame(0, $result, 'Hook must ignore the devis->commande transformation.');
		$this->assertEmpty($actions->errors);
	}

	/**
	 * Unsupported objFrom/object combination → hook returns 0, no side effect.
	 *
	 * @return void
	 */
	public function testUnsupportedCombinationReturnsZero(): void
	{
		global $db;

		$propalClone = new Propal($db);
		$actions = new ActionsClichaumeil($db);
		$parameters = array(); // no objFrom
		$action = '';
		$result = $actions->createFrom($parameters, $propalClone, $action, new HookManager($db));

		$this->assertSame(0, $result);
		$this->assertEmpty($actions->errors);
	}
}
