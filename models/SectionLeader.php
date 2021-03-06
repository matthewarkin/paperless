<?php
require_once(dirname(dirname(__FILE__)) . "/models/User.php");
require_once(dirname(dirname(__FILE__)) . "/models/Student.php");

class SectionLeader extends User {
	
	// An array of usually one, but possibly more section ids
	private $section_id;
	
	/*
	 * Return an object that represnts the unknown section leader
	 */
	public function unknown($course){
		$this->course = $course;
		$this->sunetid = "unknown";
	}
	
	/*
	 * Load a section leader. A section leader may have multiple sections, so we have
	 * an array of section_ids.
	 *
	 * @author Jeremy Keeshin	February 7, 2012
	 */
	private function load_section_leader($course){
		$this->course = $course;
		$this->section_id = array();
		$db = Database::getConnection();
		$query = "SELECT ID FROM Sections WHERE SectionLeader = :uid AND Quarter = :qid AND Class = :class_id";		
		try {
			$sth = $db->prepare($query);
			$sth->execute(array(":uid" => $this->id, 
								":qid" => $this->course->quarter->id, 
								":class_id" => $this->course->id));

			$rows = $sth->fetchAll();			
			foreach($rows as $row){
				$this->section_id []= $row['ID'];
			}			
		} catch(PDOException $e) {
			echo $e->getMessage(); // TODO log this error instead of echoing
		}
	}

	/*
	 * Static factory constructor to make a SectionLeader from a sunetid
	 * and Course object.
	 */
	public function from_sunetid_and_course($sunetid, $course){
		$this->from_sunetid($sunetid);
		$this->load_section_leader($course);
	}
	
	public function from_id_and_course($id, $course){
		$this->from_id($id);
		$this->load_section_leader($course);
	}
	
	/*
	 * Get the students for this SL. Since they may have multiple sections, we build up 
	 * an array to include in the query
	 *
	 * @author	Jeremy Keeshin	February 7, 2012
	 */
	public function get_students(){
		$db = Database::getConnection();

		// Since a section leader may have more than one section in the same class (weird, right?)
		// We need to construct an array and custom string query that may have multiple sections.
		//
		// The format is
		//     :0 => <SECTION_ID_0>
		//	   :1 => <SECTION_ID_1> ..
		//
		$sql_arr = array();
		$counter = 0;
		foreach($this->section_id as $id){
			$sql_arr[':'.$counter] = $id;
			$counter++;
		}
		
		
		// Then the query string becomes
		// ... WHERE Section IN ( :0, :1 ...)
		$inQuery = implode(',', array_keys($sql_arr));

		$query = "SELECT ID, DisplayName, SUNetID, FirstName, LastName FROM People WHERE ID IN 
					(SELECT Person FROM SectionAssignments WHERE Section IN 
						(". $inQuery . ")
					)";

		$students = array();
		try {
			$sth = $db->prepare($query);

			$sth->execute($sql_arr);
			while($row = $sth->fetch()){
				$student = Student::from_row($row);
				$student->set_course($this->course);
				$students []= $student;
			}
		} catch(PDOException $e) {
			echo $e->getMessage(); // TODO log this error instead of echoing
		}
		return $students;
	}	
	
	// The section leader's base directory is the course base directory + the SL name
	public function get_base_directory(){
		return $this->course->get_base_directory() . '/' . $this->sunetid;
	}
	
}
?>