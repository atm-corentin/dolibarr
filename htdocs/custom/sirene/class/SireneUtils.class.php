<?php
/* Copyright (C) 2025 Open-Dsi          <support@open-dsi.fr>
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
 * or see http://www.gnu.org/
 */

/**
 * File of class with tools method
 */

/**
 * Class with tools method
 */
class SireneUtils
{
	/**
	 * @var array    List of chronometer data
	 */
	protected static $chronometer = array();


	/**
	 * Start stopwatch
	 *
	 * @param	string	$log_label				Log label
	 * @param	bool	$log_processing_times	Log processing times ?
	 * @return	int								ID of the started stopwatch
	 */
	static public function startStopwatch($log_label, $log_processing_times = false)
	{
		$stopwatch_id = self::getNextFreeStopwatchId();

		self::$chronometer[$stopwatch_id] = array(
			'start_time' => microtime(true),
			'log_label' => $log_label,
		);

		if ($log_processing_times) {
			dol_syslog("Stopwatch " . sprintf("%04d", $stopwatch_id) . " - " . $log_label . " - Start", LOG_ALERT);
		}

		return $stopwatch_id;
	}

	/**
	 * Stop stopwatch
	 *
	 * @param	int		$stopwatch_id			ID of the started stopwatch
	 * @param	bool	$log_processing_times	Log processing times ?
	 * @return	int								Elapsed time (in second), =-1 if stopwatch not exist
	 */
	static public function stopStopwatch($stopwatch_id, $log_processing_times = false)
	{
		if (isset(self::$chronometer[$stopwatch_id])) {
			$elapsed_time = microtime(true) - self::$chronometer[$stopwatch_id]['start_time'];

			if ($log_processing_times) {
				dol_syslog("Stopwatch " . sprintf("%04d", $stopwatch_id) . " - " . self::$chronometer[$stopwatch_id]['log_label'] . " - Elapsed time : " . self::microTimeToTime($elapsed_time), LOG_ALERT);
			}

			unset(self::$chronometer[$stopwatch_id]);

			return $elapsed_time;
		}

		return -1;
	}

	/**
	 * Get next free stopwatch ID
	 *
	 * @return	int					The next free stopwatch ID
	 */
	static protected function getNextFreeStopwatchId()
	{
		$stopwatch_id = 0;

		if (!empty(self::$chronometer)) {
			$stopwatch_id = max(array_keys(self::$chronometer)) + 1;
		}

		return $stopwatch_id;
	}

	/**
	 * Convert micro time to string time
	 *
	 * @param	int		$micro_time		Micro time
	 * @return	string					Time formatted (Hours:Minutes:Seconds)
	 */
	public static function microTimeToTime($micro_time)
	{
		$hours = (int) ($micro_time / 3600);
		$minutes = (int) (($micro_time / 60) - $hours * 60);
		$seconds = $micro_time - $hours * 3600 - $minutes * 60;
		return sprintf("%02d:%02d:%09.6f", $hours, $minutes, $seconds);
	}
}
