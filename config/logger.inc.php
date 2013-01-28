<?php

/*
 * log output directory
 * current: ../log/
 */
define('LOG_DIR', ROOT.'log');

/*
 * log file name
 */
define('LOG_FILENAME', 'soap.log');

/*
 * log level
 */
define('PY_CRIT',	9);     /* critical errors will be logged at any time */
define('PY_ERR',	3);     /* error conditions */
define('PY_DEBUG',	2);     /* debug-level messages */
define('PY_INFO',	1);     /* simple info messages */

/*
 * Which log level should be written?
 */
define('LOG_LEVEL', PY_DEBUG);

/*
 * Should I display the log output?
 * Use this mode during the development process only. 
 */
define('LOG_OUTPUT', true);


?>