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
 * Immutable output contract for CliChaumeil product cost calculations.
 */
class CostBreakdownResult
{
	/**
	 * @var float
	 */
	public $baseCost;

	/**
	 * @var float
	 */
	public $packagingAmount;

	/**
	 * @var float
	 */
	public $transportAmount;

	/**
	 * @var float
	 */
	public $totalCosts;

	/**
	 * @var float|null
	 */
	public $paFg;

	/**
	 * @var float|null
	 */
	public $costPrice;

	/**
	 * @var bool
	 */
	public $isFinalComputable;

	/**
	 * @var string|null
	 */
	public $blockingReason;

	/**
	 * Build a deterministic computed breakdown result.
	 *
	 * @param float       $baseCost          Base cost.
	 * @param float       $packagingAmount   Packaging amount.
	 * @param float       $transportAmount   Transport amount.
	 * @param float       $totalCosts        Total costs.
	 * @param float|null  $paFg              Overhead amount.
	 * @param float|null  $costPrice         Final cost price.
	 * @param bool        $isFinalComputable True when final persistence is allowed.
	 * @param string|null $blockingReason    Blocking reason when final persistence is not allowed.
	 */
	public function __construct(
		float $baseCost,
		float $packagingAmount,
		float $transportAmount,
		float $totalCosts,
		?float $paFg,
		?float $costPrice,
		bool $isFinalComputable,
		?string $blockingReason
	) {
		$this->baseCost = $baseCost;
		$this->packagingAmount = $packagingAmount;
		$this->transportAmount = $transportAmount;
		$this->totalCosts = $totalCosts;
		$this->paFg = $paFg;
		$this->costPrice = $costPrice;
		$this->isFinalComputable = $isFinalComputable;
		$this->blockingReason = $blockingReason;
	}
}
