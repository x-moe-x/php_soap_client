<?php

/**
 * Class RequestContainer is a container to prepare a request which contains multiple elements
 */
abstract class RequestContainer
{
	/**
	 * @var array
	 */
	protected $items;

	/**
	 * @var int
	 */
	private $capacity;

	/**
	 * creates a new RequestContainer with a specified capacity
	 *
	 * @param int $capacity
	 *
	 * @return RequestContainer
	 */
	protected function __construct($capacity)
	{
		$this->items = array();
		$this->capacity = $capacity;
	}

	/**
	 * returns the assembled request
	 *
	 * @return mixed
	 */
	public abstract function getRequest();

	/**
	 * if container isn't full an item is added at it's end
	 *
	 * @param mixed $item
	 *
	 * @return void
	 */
	public function add($item)
	{
		if (count($this->items) < $this->capacity)
		{
			$this->items[] = $item;
		}
	}

	/**
	 * gives information if container is full
	 *
	 * @return bool
	 */
	public function isFull()
	{
		return count($this->items) === $this->capacity;
	}

}