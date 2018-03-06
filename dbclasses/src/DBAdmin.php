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
	
	public function selectAll()
	{
		$database = \Drupal::database();	
		$result = $database->query("SELECT * FROM {title}");
		
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
		$result = $database->query("SELECT * FROM {title} WHERE title LIKE :title", [':title' => db_like($title).'%']);
		
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
	
	public function getISSNId($issn)
	{
		/*
		returns a single int (ID)
		returns a 0 on no result
		*/
		
		$database = \Drupal::database();	
		$result = $database->query("SELECT id FROM {title} WHERE issn_l = :issn OR p_issn = :issn OR e_issn = :issn", [':issn' => $issn]);
		
		$id = 0;
		
		foreach($result as $record)
		{
			$id = $record->id;
		}
		
		return $id;
	}
	
     	public function recordCount()
   	{
		$database = \Drupal::database();
		$result = $database->query("SELECT COUNT(*) AS numrows FROM title");
		$numrows = '';
		foreach($result as $record)
		{
			$numrows = $record->numrows;
		}
		return $numrows;
    	}
	
	public function deleteById($id)
	{
		$database = \Drupal::database();	
		$result = $database->query("DELETE FROM {title} WHERE id = :id", [':id' => $id]);
		
		return "$id deleted.";
	}
	
	/*
		For institution database table
	*/
	
	//Insert a new institution, contains the associated email extension and name
	public function insertInstitution($extension, $name)
	{
		$database = \Drupal::database();	
		$database->insert('institution');
			$fields = [
				'extension' => $extension,
				'name' => $name,
				];
			$institution_id = $database->insert('institution')
				->fields($fields)
				->execute();
				
			return $institution_id;
	}
	
	//Get the name of an institution from an email extension
	//Returns 0 on no result
	public function selectByExtension($extension) 
	{
		$database = \Drupal::database();	
		$result = $database->query("SELECT * FROM {institution} WHERE extension = :extension", [':extension' => $extension]);
		
		$name = 0;
		
		foreach($result as $record)
		{
			$name = $record->name;
		}
			
		return $name;
	}
	
	/*
		For user_institution table
	*/
	public function insertUser($user, $institution) {
		$database = \Drupal::database();	
		$database->insert('user_institution');
			$fields = [
				'user_id' => $user,
				'institution_id' => $institution,
				];
			$id = $database->insert('user_institution')
				->fields($fields)
				->execute();
				
			return $id;
	}
}
?>
