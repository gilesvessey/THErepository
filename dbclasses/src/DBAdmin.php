<?php
namespace Drupal\dbclasses;
class DBAdmin
{
	public function insert($title, $source, $issn_l, $p_issn, $e_issn, $lcclass, $callnumber)
	{
		$user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
		$database = \Drupal::database();	
		
		$database->insert('issn');
			$fields = [
				'title' => $title,
				'issn_l' => $issn_l,
				'p_issn' => $p_issn,
				'e_issn' => $e_issn,
				];
			$issn_id = $database->insert('issn')
				->fields($fields)
				->execute();
				
		$database->insert('lc');
			$fields = [
				'issn_id' => $issn_id,
				'lc' => $callnumber,
				'user_id' => $user->get('uid')->value,
				];
			$lc_id = $database->insert('lc')
				->fields($fields)
				->execute();
				
		return $issn_id;
	}
	
	public function selectById($id)
	{
		$database = \Drupal::database();
		$sql = "SELECT 
					issn.id as id,
					issn.title as title,
					issn.issn_l as issn_l,
					issn.p_issn as p_issn,
					issn.e_issn as e_issn,
					issn.modified as modified,
					institution.name as name,
					lc.lc as lc,
					lc.user_id as user_id					
				FROM issn
				LEFT OUTER JOIN lc
					ON lc.issn_id = issn.id
					LEFT OUTER JOIN user_institution
						ON user_institution.user_id = lc.user_id
						LEFT OUTER JOIN institution
						ON institution.id = user_institution.institution_id
				WHERE issn.id = $id;
				";
				
		$result = db_query($sql);
		
		$recordSet = array();
		$setIndex = 0;
		
		foreach($result as $record)
		{
			$id = $record->id;
			$title = $record->title;
			$modified = $record->modified;
			$issn_l = $record->issn_l;
			$p_issn = $record->p_issn;
			$e_issn = $record->e_issn;
			$callnumber = $record->lc;
			$source = $record->name;
			$user = $record->user_id;
			
			$recordSet[$setIndex]  = new DBRecord($id, $title, $source, $issn_l, $p_issn, $e_issn, '', $callnumber, $modified, $user);
			$setIndex++;
		}
		
		return $recordSet;
	}
	
	public function selectAll()
	{
		$database = \Drupal::database();
		$sql = "SELECT 
					issn.id as id,
					issn.title as title,
					issn.issn_l as issn_l,
					issn.p_issn as p_issn,
					issn.e_issn as e_issn,
					issn.modified as modified,
					institution.name as name,
					lc.lc as lc,
					lc.user_id as user_id					
				FROM issn
				LEFT OUTER JOIN lc
					ON lc.issn_id = issn.id
					LEFT OUTER JOIN user_institution
						ON user_institution.user_id = lc.user_id
						LEFT OUTER JOIN institution
						ON institution.id = user_institution.institution_id;
				";
				
		$result = db_query($sql);
		$setIndex = 0;
		
		foreach($result as $record)
		{
			$id = $record->id;
			$title = $record->title;
			$modified = $record->modified;
			$issn_l = $record->issn_l;
			$p_issn = $record->p_issn;
			$e_issn = $record->e_issn;
			$callnumber = $record->lc;
			$source = $record->name;
			$user = $record->user_id;
			
			$recordSet[$setIndex]  = new DBRecord($id, $title, $source, $issn_l, $p_issn, $e_issn, '', $callnumber, $modified, $user);
			$setIndex++;
		}
		
		return $recordSet;
	}
	
	public function selectByTitle($title)
	{
		$database = \Drupal::database();
		$sql = "SELECT 
					issn.id as id,
					issn.title as title,
					issn.issn_l as issn_l,
					issn.p_issn as p_issn,
					issn.e_issn as e_issn,
					issn.modified as modified,
					institution.name as name,
					lc.lc as lc,
					lc.user_id as user_id					
				FROM issn
				LEFT OUTER JOIN lc
					ON lc.issn_id = issn.id
					LEFT OUTER JOIN user_institution
						ON user_institution.user_id = lc.user_id
						LEFT OUTER JOIN institution
						ON institution.id = user_institution.institution_id
				WHERE issn.title LIKE '%$title%';
				";
				
		$result = db_query($sql);
		
		$recordSet = array();
		$setIndex = 0;
		
		foreach($result as $record)
		{
			$id = $record->id;
			$title = $record->title;
			$modified = $record->modified;
			$issn_l = $record->issn_l;
			$p_issn = $record->p_issn;
			$e_issn = $record->e_issn;
			$callnumber = $record->lc;
			$source = $record->name;
			$user = $record->user_id;
			
			$recordSet[$setIndex]  = new DBRecord($id, $title, $source, $issn_l, $p_issn, $e_issn, '', $callnumber, $modified, $user);
			$setIndex++;
		}
		
		return $recordSet;
	}
	
	public function selectByISSN($issn) //matches any ISSN type
	{
		$database = \Drupal::database();
		$sql = "SELECT 
					issn.id as id,
					issn.title as title,
					issn.issn_l as issn_l,
					issn.p_issn as p_issn,
					issn.e_issn as e_issn,
					issn.modified as modified,
					institution.name as name,
					lc.lc as lc,
					lc.user_id as user_id					
				FROM issn
				LEFT OUTER JOIN lc
					ON lc.issn_id = issn.id
					LEFT OUTER JOIN user_institution
						ON user_institution.user_id = lc.user_id
						LEFT OUTER JOIN institution
						ON institution.id = user_institution.institution_id
				WHERE issn.issn_l = $issn
					OR issn.p_issn = $issn
					OR issn.e_issn = $issn; 
				";
				
		$result = db_query($sql);
		
		$recordSet = array();
		$setIndex = 0;
		
		foreach($result as $record)
		{
			$id = $record->id;
			$title = $record->title;
			$modified = $record->modified;
			$issn_l = $record->issn_l;
			$p_issn = $record->p_issn;
			$e_issn = $record->e_issn;
			$callnumber = $record->lc;
			$source = $record->name;
			$user = $record->user_id;
			
			$recordSet[$setIndex]  = new DBRecord($id, $title, $source, $issn_l, $p_issn, $e_issn, '', $callnumber, $modified, $user);
			$setIndex++;
		}
		
		return $recordSet;
	}
	
	public function selectByLC($lc)
	{
		$database = \Drupal::database();
		$sql = "SELECT 
					issn.id as id,
					issn.title as title,
					issn.issn_l as issn_l,
					issn.p_issn as p_issn,
					issn.e_issn as e_issn,
					issn.modified as modified,
					institution.name as name,
					lc.lc as lc,
					lc.user_id as user_id					
				FROM issn
				LEFT OUTER JOIN lc
					ON lc.issn_id = issn.id
					LEFT OUTER JOIN user_institution
						ON user_institution.user_id = lc.user_id
						LEFT OUTER JOIN institution
						ON institution.id = user_institution.institution_id
				WHERE lc.lc = '$lc';
				";
				
		$result = db_query($sql);
		
		$recordSet = array();
		$setIndex = 0;
		
		foreach($result as $record)
		{
			$id = $record->id;
			$title = $record->title;
			$modified = $record->modified;
			$issn_l = $record->issn_l;
			$p_issn = $record->p_issn;
			$e_issn = $record->e_issn;
			$callnumber = $record->lc;
			$source = $record->name;
			$user = $record->user_id;
			
			$recordSet[$setIndex]  = new DBRecord($id, $title, $source, $issn_l, $p_issn, $e_issn, '', $callnumber, $modified, $user);
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
		$sql = "SELECT 
					issn.id as id					
				FROM issn
				WHERE issn.issn_l = $issn
					OR issn.p_issn = $issn
					OR issn.e_issn = $issn;
				";
				
		$result = db_query($sql);
		
		$recordSet = array();
		$setIndex = 0;
		
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
		$result = $database->query("SELECT COUNT(*) AS numrows FROM issn");
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
		$result = $database->query("DELETE FROM {issn} WHERE id = :id", [':id' => $id]);	
		$result = $database->query("DELETE FROM {lc} WHERE issn_id = :id", [':id' => $id]);
		
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
				'domain' => $extension,
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
		$result = $database->query("SELECT name FROM {institution} WHERE domain = :extension", [':extension' => $extension]);
		
		$name = 0;
		
		foreach($result as $record)
		{
			$name = $record->name;
		}
			
		return $name;
	}
	
	public function getInstitutionID($name) 
	{
		$database = \Drupal::database();	
		$result = $database->query("SELECT id FROM {institution} WHERE name = :name", [':name' => $name]);
		
		$id = 0;
		
		foreach($result as $record)
		{
			$id = $record->id;
		}
			
		return $id;
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
