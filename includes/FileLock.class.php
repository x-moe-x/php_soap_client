<?php

class FileLock {
	/**
	 * @var resource
	 */
	private $lockFile = null;

	public function __construct() {
	}

	public function init($filename) {
		$this -> lockFile = @fopen($filename, 'r+');
		if (!$this -> lockFile) {
			throw new RuntimeException("could not open $filename");
		}
	}

	public function discard() {
		if (!fclose($this -> lockFile)) {
			throw new RuntimeException("could not close lock file");
		}
		$this -> lockFile = null;
	}

	public function lock($maxTries = 60) {
		$triesLeft = $maxTries;
		do {
			if ($this -> tryLock()) {
				return true;
			} else {
				sleep(1);
			}

		} while ($triesLeft--);
		return false;
	}

	public function tryLock() {
		return flock($this -> lockFile, LOCK_EX | LOCK_NB);
	}

	public function unlock() {
		flock($this -> lockFile, LOCK_UN | LOCK_NB);
	}

}
		?>
