<?php namespace h2g2;
/* Copyright (C) 2021  Fabien FERNANDES ALVES <fabien@code42.fr>
 * Copyright (C) ---Put here your own copyright and developer email---
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file        class/querybuilderexception.class.php
 * \ingroup     h2g2
 * \brief       This file is an exception definition for the query builder
 */
class QueryBuilderException extends \Exception
{
	/**
	 * @var String      Request in error
	 */
	private $request;

	/**
	 * QueryBuilderException constructor.
	 *
	 * @param string         $message  Exception message to throw
	 * @param string         $request  Exception request
	 * @param int            $code     Exception code
	 * @param Throwable|null $previous The previous exception used for the exception chaining.
	 */
	public function __construct($message = "", $request = "", $code = 0, Throwable $previous = null)
	{
		parent::__construct($message, $code, $previous);

		$this->request = $request;
	}

	/**
	 * Get the request in error
	 *
	 * @return String
	 */
	public function getRequest()
	{
		return $this->request;
	}
}
