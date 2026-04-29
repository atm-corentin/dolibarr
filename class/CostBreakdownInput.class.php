<?php
declare(strict_types=1);
/* Copyright (C) 2026  ATM Consulting
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * Immutable input contract for CliChaumeil product cost calculations.
 */
class CostBreakdownInput
{
	/**
	 * @var float
	 */
	public $paSupport;

	/**
	 * @var float
	 */
	public $paSav;

	/**
	 * @var float
	 */
	public $paMachine;

	/**
	 * @var float
	 */
	public $paEncre;

	/**
	 * @var float
	 */
	public $paMo;

	/**
	 * @var float
	 */
	public $packagingPercent;

	/**
	 * @var float
	 */
	public $transportPercent;

	/**
	 * @var float
	 */
	public $tauxFraisDossier;

	/**
	 * @var float|null
	 */
	public $fgPercent;

	/**
	 * Build a normalized cost breakdown input.
	 *
	 * @param float      $paSupport         Support amount.
	 * @param float      $paSav             After-sales amount.
	 * @param float      $paMachine         Machine amount.
	 * @param float      $paEncre           Ink amount.
	 * @param float      $paMo              Labor amount.
	 * @param float      $packagingPercent  Packaging percent.
	 * @param float      $transportPercent  Transport percent.
	 * @param float      $tauxFraisDossier  File fee percent.
	 * @param float|null $fgPercent         Overhead percent.
	 */
	public function __construct(
		float $paSupport,
		float $paSav,
		float $paMachine,
		float $paEncre,
		float $paMo,
		float $packagingPercent,
		float $transportPercent,
		float $tauxFraisDossier,
		?float $fgPercent
	) {
		$this->paSupport = $paSupport;
		$this->paSav = $paSav;
		$this->paMachine = $paMachine;
		$this->paEncre = $paEncre;
		$this->paMo = $paMo;
		$this->packagingPercent = $packagingPercent;
		$this->transportPercent = $transportPercent;
		$this->tauxFraisDossier = $tauxFraisDossier;
		$this->fgPercent = $fgPercent;
	}
}
