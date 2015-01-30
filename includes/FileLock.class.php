<?php

class FileLock
{
	/**
	 * @var resource
	 */
	private $lockFile = null;

	/**
	 * Gives a new, uninitialized instance of FileLock
	 *
	 * @return FileLock
	 */
	public function __construct()
	{
	}

	/**
	 * Initializes the FileLock with given file
	 *
	 * @param string $filename filename to init current FileLock with
	 *
	 * @return void
	 */
	public function init($filename)
	{
		$this->lockFile = @fopen($filename, 'r+');
		if (!$this->lockFile)
		{
			throw new RuntimeException("could not open $filename");
		}
	}

	/**
	 * Discards the FileLock. The FileLock has to be reinitialized to be usable again
	 *
	 * @return void
	 */
	public function discard()
	{
		if (!fclose($this->lockFile))
		{
			throw new RuntimeException("could not close lock file");
		}
		$this->lockFile = null;
	}

	/**
	 * Waits at most a given nr. of seconds to get the FileLock. This call is blocking while waiting.
	 *
	 * @param int $maxTries max nr. of seconds to wait
	 *
	 * @return bool true if lock could be acquired
	 */
	public function lock($maxTries = 60)
	{
		$triesLeft = $maxTries;
		do
		{
			if ($this->tryLock())
			{
				return true;
			} else
			{
				sleep(1);
			}

		} while ($triesLeft--);

		return false;
	}

	/**
	 * Tries to get the lock on FileLock. This call is not blocking
	 *
	 * @return bool true if lock could be acquired
	 */
	public function tryLock()
	{
		return flock($this->lockFile, LOCK_EX | LOCK_NB);
	}

	/**
	 * Unlocks FileLock
	 *
	 * @return void
	 */
	public function unlock()
	{
		flock($this->lockFile, LOCK_UN | LOCK_NB);
	}

}

?>
