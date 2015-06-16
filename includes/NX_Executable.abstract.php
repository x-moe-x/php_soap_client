<?php

abstract class NX_Executable
{
	/**
	 * Identifier string for Logger
	 *
	 * @var string
	 */
	private $identifier4Logger;

	/**
	 *
	 * @var boolean
	 */
	private $verbose = true;

	/**
	 * @param string $identifier4Logger
	 */
	public function __construct($identifier4Logger)
	{
		if (is_string($identifier4Logger) && strlen($identifier4Logger))
		{
			$this->identifier4Logger = $identifier4Logger;
		} else
		{
			$this->identifier4Logger = __CLASS__;
		}
	}

	public abstract function execute();

	/**
	 * call getLogger()->debug($message) if $this->verbose
	 *
	 * @param string $message
	 */
	protected function debug($message)
	{
		if ($this->verbose === true)
		{
			$this->getLogger()->debug($message);
		}
	}

	/**
	 *
	 * @return Logger
	 */
	protected function getLogger()
	{
		return Logger::instance($this->identifier4Logger);
	}
}
