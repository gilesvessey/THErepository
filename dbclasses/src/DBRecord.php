<?php
namespace Drupal\dbclasses;
class DBRecord
{
	public $id;
	public $title;
	public $issn_l;
	public $p_issn;
	public $e_issn;
	public $callnumber;
	public $modified;
	public $user;
	public $source;
	
	public function __construct($id, $title, $source, $issn_l, $p_issn, $e_issn, $lcclass, $callnumber, $modified, $user)
	{
		$this->id = $id;
		$this->title = $title;
		$this->source = $source;
		$this->issn_l = $issn_l;
		$this->p_issn = $p_issn;
		$this->e_issn = $e_issn;
		$this->callnumber = $callnumber;
		$this->modified = $modified;
		$this->user = $user;
	}
}
?>
