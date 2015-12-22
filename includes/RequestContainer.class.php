<?php
require_once 'FillObjectFromArray.php';

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
	public function __construct($capacity)
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
	 * @param mixed    $item
	 *
	 * @param null|int $index
	 */
	public function add($item, $index = null)
	{
		if (count($this->items) < $this->capacity)
		{
			if (is_null($index))
			{
				$this->items[] = $item;
			} else
			{
				$this->items[$index] = $item;
			}
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