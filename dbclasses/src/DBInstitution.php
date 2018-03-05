<?php
namespace Drupal\dbclasses;
class DBInstitution
{
	public $id;
	public $extension;
	public $name;
	
	public function __construct($id, $extension, $name)
	{
		$this->id = $id;
		$this->extension = $extension;
		$this->name = $name;
	}
}
?>