<?php

define('LOGFILE', '/Applications/MAMP/logs/mysql.log.0.gz');

if (empty($_SERVER['argv'][1]) || !is_file($_SERVER['argv'][1])) {
	trigger_error("The first argument must be the name of a file", E_USER_ERROR);
}

$filename = $_SERVER['argv'][1];
if (substr($filename, '-3') == '.gz') {
	$filename = 'compress.zlib://'.$filename;
}

$connections = array();

$stats = array('unknown' => array());

$fp = fopen($filename, 'r');

while (!feof($fp)) {
	$line = fgets($fp, 100);
	if (preg_match('#^\s+(\d+)\s+Init DB\s+(.*?)\s*$#', $line, $matches)) {
		$connections[$matches[1]] = $matches[2];
		$stats[$matches[2]] = array();
		continue;
	}

	if (preg_match('#^\s+(\d+)\s+Query\s+(.*)#', $line, $matches)) {
		if (isset($connections[$matches[1]])) {
			$dbname = $connections[$matches[1]];
		} else {
			$dbname = 'unknown';
		}

		$type = GetQueryType($matches[2]);


		if (!isset($stats[$dbname][$type])) {
			$stats[$dbname][$type] = 0;
		}

		$stats[$dbname][$type]++;
	}
}


function GetQueryType($line)
{
	$line = trim($line);
	$line = strtoupper($line);

	if (preg_match('#^/\*\!\d+\s+#', $line)) {
		$line = preg_replace('#^/\*\!\d+\s+#', '', $line);
	}

	$starts = array(
		'SELECT',
		'INSERT INTO',
		'DELETE',
		'REPLACE',
		'UPDATE',
		'SET',
		'DROP',
		'GRANT',
		'CREATE TABLE',
		'LOCK TABLES',
		'UNLOCK TABLES',
		'TRUNCATE',
		'SHOW COLUMNS FROM',
		'SHOW INDEX FROM',
		'SHOW TABLES',
		'SHOW DATABASES',
		'SHOW CREATE TABLE',
		'SHOW FIELDS FROM',
		'SHOW TRIGGERS',
		'SHOW TABLE STATUS',
		'CREATE DATABASE',
		'SHOW VARIABLES',
		'CREATE ALGORITHM',
		'ALTER TABLE',
		'INSERT IGNORE INTO',
	);

	foreach ($starts as $start) {
		if (BeginsWith($start, $line)) {
			return $start;
		}
	}

	fwrite(STDERR, "Query not matched $line\n");
}

function BeginsWith($needle, $haystack)
{
	return (substr($haystack, 0, strlen($needle)) == $needle);
}
fclose($fp);

print_r($stats);




