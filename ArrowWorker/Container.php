<?php

namespace ArrowWorker;

/**
 * Class Container
 * @package ArrowWorker
 */
class Container
{
	
	private array $entries = [];
	
	public function Has(string $name)
	{
		return isset($this->entries[$name]);
	}
	
	public function Get(string $name)
	{
		return $this->entries[$name];
	}
	
	public function Set(string $name, $value)
	{
		$this->entries[$name] = $value;
	}
	
	
}
