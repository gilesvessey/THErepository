<?php
namespace Drupal\dbclasses;
class DBAdmin
{
	public function insert($title, $source, $issn_l, $p_issn, $e_issn, $lcclass, $callnumber)
	{
		$database = \Drupal::database();	
		$database->insert('title');
			$fields = [
				'title' => $title,
				'source' => $source,
				'issn_l' => $issn_l,
				'p_issn' => $p_issn,
				'e_issn' => $e_issn,
				'lcclass' => $lcclass,
				'callnumber' => $callnumber,
				];
			$title_id = $database->insert('title')
				->fields($fields)
				->execute();
				
			return $title_id;
	}
	
	public function selectById($id)
	{
		$database = \Drupal::database();	
		$result = $database->query("SELECT * FROM {title} WHERE id = :id", [':id' => $id]);
		
		$recordSet = array();
		$setIndex = 0;
		
		foreach($result as $record)
		{
			$id = $record->id;
			$title = $record->title;
			$source = $record->source;
			$issn_l = $record->issn_l;
			$p_issn = $record->p_issn;
			$e_issn = $record->e_issn;
			$lcclass = $record->lcclass;
			$callnumber = $record->callnumber;
			
			$recordSet[$setIndex]  = new DBRecord($id, $title, $source, $issn_l, $p_issn, $e_issn, $lcclass, $callnumber);
			$setIndex++;
		}
		
		return $recordSet;
	}
	
	public function selectByTitle($title)
	{
		$database = \Drupal::database();	
		$result = $database->query("SELECT * FROM {title} WHERE title LIKE :title", [':title' => '%']);
		
		$recordSet = array();
		$setIndex = 0;
		
		foreach($result as $record)
		{
			$id = $record->id;
			$title = $record->title;
			$source = $record->source;
			$issn_l = $record->issn_l;
			$p_issn = $record->p_issn;
			$e_issn = $record->e_issn;
			$lcclass = $record->lcclass;
			$callnumber = $record->callnumber;
			
			$recordSet[$setIndex]  = new DBRecord($id, $title, $source, $issn_l, $p_issn, $e_issn, $lcclass, $callnumber);
			$setIndex++;
		}
		
		return $recordSet;
	}
	
	public function selectByISSN($issn) //matches any ISSN type
	{
		$database = \Drupal::database();	
		$result = $database->query("SELECT * FROM {title} WHERE issn_l = :issn OR p_issn = :issn OR e_issn = :issn", [':issn' => $issn]);
		
		$recordSet = array();
		$setIndex = 0;
		
		foreach($result as $record)
		{
			$id = $record->id;
			$title = $record->title;
			$source = $record->source;
			$issn_l = $record->issn_l;
			$p_issn = $record->p_issn;
			$e_issn = $record->e_issn;
			$lcclass = $record->lcclass;
			$callnumber = $record->callnumber;
			
			$recordSet[$setIndex]  = new DBRecord($id, $title, $source, $issn_l, $p_issn, $e_issn, $lcclass, $callnumber);
			$setIndex++;
		}
		
		return $recordSet;
	}
	
	public function selectByLCClass($lc_class)
	{
		$database = \Drupal::database();	
		$result = $database->query("SELECT * FROM {title} WHERE lcclass = :lc_class", [':lc_class' => $lc_class]);
		
		$recordSet = array();
		$setIndex = 0;
		
		foreach($result as $record)
		{
			$id = $record->id;
			$title = $record->title;
			$source = $record->source;
			$issn_l = $record->issn_l;
			$p_issn = $record->p_issn;
			$e_issn = $record->e_issn;
			$lcclass = $record->lcclass;
			$callnumber = $record->callnumber;
			
			$recordSet[$setIndex]  = new DBRecord($id, $title, $source, $issn_l, $p_issn, $e_issn, $lcclass, $callnumber);
			$setIndex++;
		}
		
		return $recordSet;
	}
}
?>
