<?php

/**
 * Debug data using var_dump with beautiful output
 *
 * @param  mixed  $data   Data to dump
 * @param  string $label  Label displayed at the top of the dump section ('' by default)
 * @param  bool   $return Return the result instead of printing it (false by default)
 * @return string|void
 */
function var_dump_pretty($data, $label = '', $return = false)
{
	$debug = debug_backtrace();
	$callingFile = $debug[0]['file'];
	$callingFileLine = $debug[0]['line'];

	ob_start();
	var_dump($data);
	$c = ob_get_contents();
	ob_end_clean();

	$c = preg_replace("/\r\n|\r/", "\n", $c);
	$c = str_replace("]=>\n", '] = ', $c);
	$c = preg_replace('/= {2,}/', '= ', $c);
	$c = preg_replace("/\[\"(.*?)\"\] = /i", "[$1] = ", $c);
	$c = preg_replace('/  /', "    ", $c);
	$c = preg_replace("/\"\"(.*?)\"/i", "\"$1\"", $c);
	$c = preg_replace("/(int|float)\(([0-9\.]+)\)/i", "$1() <span class=\"number\">$2</span>", $c);

	// Syntax Highlighting of Strings. This seems cryptic, but it will also allow non-terminated strings to get parsed.
	$c = preg_replace("/(\[[\w ]+\] = string\([0-9]+\) )\"(.*?)/sim", "$1<span class=\"string\">\"", $c);
	$c = preg_replace("/(\"\n{1,})( {0,}\})/sim", "$1</span>$2", $c);
	$c = preg_replace("/(\"\n{1,})( {0,}\[)/sim", "$1</span>$2", $c);
	$c = preg_replace("/(string\([0-9]+\) )\"(.*?)\"\n/sim", "$1<span class=\"string\">\"$2\"</span>\n", $c);

	$regex = array(
		// Numberrs
		'numbers' => array('/(^|] = )(array|float|int|string|resource|object\(.*\)|\&amp;object\(.*\))\(([0-9\.]+)\)/i', '$1$2(<span class="number">$3</span>)'),
		// Keywords
		'null' => array('/(^|] = )(null)/i', '$1<span class="keyword">$2</span>'),
		'bool' => array('/(bool)\((true|false)\)/i', '$1(<span class="keyword">$2</span>)'),
		// Types
		'types' => array('/(of type )\((.*)\)/i', '$1(<span class="type">$2</span>)'),
		// Objects
		'object' => array('/(object|\&amp;object)\(([\w]+)\)/i', '$1(<span class="object">$2</span>)'),
		// Function
		'function' => array('/(^|] = )(array|string|int|float|bool|resource|object|\&amp;object)\(/i', '$1<span class="function">$2</span>('),
	);

	foreach ($regex as $x) {
		$c = preg_replace($x[0], $x[1], $c);
	}

	$style = '
    /* outside div - it will float and match the screen */
    .dumpr {
        margin: 2px;
        padding: 2px;
        background-color: #fbfbfb;
        float: left;
        clear: both;
    }
    /* font size and family */
    .dumpr pre {
        color: #000000;
        font-size: 9pt;
        font-family: "Courier New",Courier,Monaco,monospace;
        margin: 0px;
        padding-top: 5px;
        padding-bottom: 7px;
        padding-left: 9px;
        padding-right: 9px;
    }
    /* inside div */
    .dumpr div {
        background-color: #fcfcfc;
        border: 1px solid #d9d9d9;
        float: left;
        clear: both;
    }
    /* syntax highlighting */
    .dumpr span.string {color: #c40000;}
    .dumpr span.number {color: #ff0000;}
    .dumpr span.keyword {color: #007200;}
    .dumpr span.function {color: #0000c4;}
    .dumpr span.object {color: #ac00ac;}
    .dumpr span.type {color: #0072c4;}
    ';

	$style = preg_replace("/ {2,}/", "", $style);
	$style = preg_replace("/\t|\r\n|\r|\n/", "", $style);
	$style = preg_replace("/\/\*.*?\*\//i", '', $style);
	$style = str_replace('}', '} ', $style);
	$style = str_replace(' {', '{', $style);
	$style = trim($style);

	$c = trim($c);
	$c = preg_replace("/\n<\/span>/", "</span>\n", $c);

	if ($label == '') {
		$line1 = '';
	} else {
		$line1 = "<strong>$label</strong> \n";
	}

	$out = "\n<!-- Dumpr Begin -->\n
        <style type=\"text/css\">".$style."</style>\n
        <div class=\"dumpr\">
        <div><pre>$line1 $callingFile : $callingFileLine \n$c\n</pre></div></div><div style=\"clear:both;\">&nbsp;</div>\n<!-- Dumpr End -->\n";
	if ($return) {
		return $out;
	} else {
		echo $out;
	}
}

/**
 * Write log message into outputs. Possible outputs can be:
 * SYSLOG_HANDLERS = ["mod_syslog_file"]        file name is then defined by SYSLOG_FILE
 * SYSLOG_HANDLERS = ["mod_syslog_syslog"]    facility is then defined by SYSLOG_FACILITY
 *
 * Note: If constant 'SYSLOG_FILE_NO_ERROR' defined, we never output any error message when writing to log fails.
 *
 * This function works only if syslog module is enabled.
 * This must not use any call to other function calling dol_syslog (avoid infinite loop).
 *
 * 	@param string    $content    Line to log.
 *  @param int       $level      Log level. This level also determines the ANSI color used for the message. (ex: LOG_ERR => light red, LOG_INFO => green, LOG_DEBUG => cyan)
 *  @param string    $color      Color of the text -> between 'brightred', 'magenta', 'red', 'lightred', 'yellow', 'blue','green', 'cyan', (if not provided => color of the 'level')
 *  @return void
 */
function printLog($content, $level = LOG_INFO, $color = '')
{
	global $conf;

	if (empty($conf->global->SYSLOG_FILE)) $logfile = DOL_DATA_ROOT . '/dolibarr.log';
	else $logfile = str_replace('DOL_DATA_ROOT', DOL_DATA_ROOT, $conf->global->SYSLOG_FILE);

	// Test constant SYSLOG_FILE_NO_ERROR (should stay a constant defined with define('SYSLOG_FILE_NO_ERROR',1);
	if (defined('SYSLOG_FILE_NO_ERROR')) $filefd = @fopen($logfile, 'a+');
	else $filefd = fopen($logfile, 'a+');

	if ($level > getDolGlobalInt('SYSLOG_LEVEL')) {
		return;
	}

	$logParams = array(
		LOG_EMERG   => array("text" => "EMERG",  "color" => "brightred"),
		LOG_ALERT   => array("text" => "ALERT",  "color" => "magenta"),
		LOG_CRIT    => array("text" => "CRIT",   "color" => "red"),
		LOG_ERR     => array("text" => "ERROR",  "color" => "lightred"),
		LOG_WARNING => array("text" => "WARNING","color" => "yellow"),
		LOG_NOTICE  => array("text" => "NOTICE", "color" => "blue"),
		LOG_INFO    => array("text" => "INFO",   "color" => "green"),
		LOG_DEBUG   => array("text" => "DEBUG",  "color" => "cyan")
	);

	$colors = array(
		"brightred" => "\033[1;31m",
		"magenta" => "\033[1;35m",
		"red" => "\033[0;31m",
		"lightred" => "\033[0;91m",
		"yellow" => "\033[0;33m",
		"blue" => "\033[0;34m",
		"green" => "\033[0;32m",
		"cyan" => "\033[0;36m"
	);

	$reset = "\033[0m"; // reset color
	$message = "\t" . dol_print_date(dol_now('gmt'), 'standard', 'gmt') . " " . $colors[$logParams[$level]['color']] . sprintf("%-7s", $logParams[$level]['text']) . " " . (empty($color) ? '' : $reset . $colors[$color]) . $content . $reset;
	fwrite($filefd, "\n" . $message . "\n");
	fclose($filefd);
	if ((float) DOL_VERSION >= 18) dolChmod($logfile); // Change mod of a file ($conf->global->MAIN_UMASK)
}

/**
 * start a 'section' into outputs. Possible outputs can be:
 * SYSLOG_HANDLERS = ["mod_syslog_file"]        file name is then defined by SYSLOG_FILE
 * SYSLOG_HANDLERS = ["mod_syslog_syslog"]    facility is then defined by SYSLOG_FACILITY
 * Note: If constant 'SYSLOG_FILE_NO_ERROR' defined, we never output any error message when writing to log fails.
 * This function works only if syslog module is enabled.
 * This must not use any call to other function calling dol_syslog (avoid infinite loop).
 * @param string $sectionName Name of the section => === START SECTION `$sectionName` === ('' by default)
 * @param int $level Log level. This level also determines the ANSI color used for the message. (ex: LOG_ERR => light red, LOG_INFO => green, LOG_DEBUG => cyan)
 * @param string $color Color of the text -> between 'brightred', 'magenta', 'red', 'lightred', 'yellow', 'blue','green', 'cyan', (if not provided => green)
 * @return void
 */
function startPrettyLogSection($sectionName = '', $level = LOG_INFO, $color = '')
{
	printLog("========== START SECTION " . ($sectionName ? " - " . $sectionName : "") . " ==========", $level, $color);
}

/**
 * Write log message into outputs. Possible outputs can be:
 * SYSLOG_HANDLERS = ["mod_syslog_file"]        file name is then defined by SYSLOG_FILE
 * SYSLOG_HANDLERS = ["mod_syslog_syslog"]    facility is then defined by SYSLOG_FACILITY
 *
 * Note: If constant 'SYSLOG_FILE_NO_ERROR' defined, we never output any error message when writing to log fails.
 *
 * This function works only if syslog module is enabled.
 * This must not use any call to other function calling dol_syslog (avoid infinite loop).
 *
 * 	@param string    $content    Line to log.
 *  @param int       $level      Log level. This level also determines the ANSI color used for the message. (ex: LOG_ERR => light red, LOG_INFO => green, LOG_DEBUG => cyan)
 *  @param string    $color      Color of the text -> between 'brightred', 'magenta', 'red', 'lightred', 'yellow', 'blue','green', 'cyan', (if not provided => color of the 'level')
 *  @return void
 */
function prettyLogSection($content, $level = LOG_INFO, $color = '')
{
	printLog("\t" . $content, $level, $color);
}

/**
 * end a 'section' into outputs. Possible outputs can be:
 * SYSLOG_HANDLERS = ["mod_syslog_file"]        file name is then defined by SYSLOG_FILE
 * SYSLOG_HANDLERS = ["mod_syslog_syslog"]    facility is then defined by SYSLOG_FACILITY
 * Note: If constant 'SYSLOG_FILE_NO_ERROR' defined, we never output any error message when writing to log fails.
 * This function works only if syslog module is enabled.
 * This must not use any call to other function calling dol_syslog (avoid infinite loop).
 * @param string $sectionName Name of the section => === END SECTION `$sectionName` === ('' by default)
 * @param int $level Log level. This level also determines the ANSI color used for the message. (ex: LOG_ERR => light red, LOG_INFO => green, LOG_DEBUG => cyan)
 * @param string $color Color of the text -> between 'brightred', 'magenta', 'red', 'lightred', 'yellow', 'blue','green', 'cyan', (if not provided => green)
 * @return void
 */
function endPrettyLogSection($sectionName = '', $level = LOG_INFO, $color = '')
{
	printLog("========== END SECTION " . ($sectionName ? " - " . $sectionName : "") . " ==========\n", $level, $color);
}
