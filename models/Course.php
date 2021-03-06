<?php
require_once(dirname(dirname(__FILE__)) . "/config.php");
require_once(dirname(dirname(__FILE__)) . "/models/Model.php");
require_once(dirname(dirname(__FILE__)) . "/models/Quarter.php");
require_once(dirname(dirname(__FILE__)) . "/models/CourseSettings.php");


class Course extends Model {

	public $id;
	public $name;
	public $quarter;
	public $settings;
	
	/* Returns if a file name is valid for this class */	
	public function code_file_is_valid($filename){
		$ext = pathinfo($filename, PATHINFO_EXTENSION);
		$ext = strtolower($ext);
		$allowed = $this->get_file_types();
		return in_array($ext, $allowed);
	}
	
	public static function format_file_type($val){
		return trim(strtolower($val));
	}
	
	/* Return an array of the allows file types for this class.*/
	public function get_file_types(){
		$arr = explode(',', $this->settings->get_value('file_types'));
		$filtered = array_map('Course::format_file_type', $arr);
		return $filtered;
	}
	
	public static function from_name_and_quarter_id($name, $qid){
		$db = Database::getConnection();	
		$instance = new self();
		$instance->name = strtolower($name);
		$instance->quarter = new Quarter($qid);
		$query = "SELECT ID FROM Courses WHERE NAME = :class;";
		try {
			$sth = $db->prepare($query);
			$sth->execute(array(":class" => $instance->name));
			$sth->setFetchMode(PDO::FETCH_ASSOC);
			if($row = $sth->fetch()) {
			  $instance->id = $row['ID'];
			}
		} catch(PDOException $e) {
			echo $e->getMessage(); // TODO log this error instead of echoing
		}
		
		$instance->settings = CourseSettings::get_for_course($instance);
		return $instance;
	}
	
	public static function from_id($id){
		$db = Database::getConnection();	
		
		$instance = new self();
		$instance->id = $id;

		$query = "SELECT Name FROM Courses WHERE ID = :id;";
		try {
			$sth = $db->prepare($query);
			$sth->execute(array(":id" => $id));
			$sth->setFetchMode(PDO::FETCH_ASSOC);
			if($row = $sth->fetch()) {
			  $instance->name = $row['Name'];
			}
		} catch(PDOException $e) {
			echo $e->getMessage(); // TODO log this error instead of echoing
		}
		return $instance;
	}
	
	// Return a short string representation
	public function short(){
		return strtoupper($this->name) . ' - ' . $this->quarter;
	}
	
	public function __toString(){
		return $this->name. " in ". $this->quarter;
	}
	
	public static function from_row($row){
		$instance = Course::from_id($row['Class']);
		$instance->quarter = new Quarter($row['Quarter']);
		return $instance;
	}
	
	/*
	 * Return the base link for this class
	 */
	public function get_link(){
		return ROOT_URL . $this->quarter->id . '/' . $this->name;
	}
	
	/*
	 * Since we are working with archived courses as well, the 
	 * base directory now takes the form
	 * /afs/ir/class/archive/cs/cs106a.PeopleSoftCode/submissions/
	 * 
	 * SUBMISSIONS_PREFIX/cs106a.PeopleSoftCode/submissions
	 */
	public function get_base_directory(){
		$people_soft_code = $this->quarter->get_people_soft_code();
		return SUBMISSIONS_PREFIX .'/'. $this->name .'/' . $this->name . '.'. $people_soft_code .'/submissions';
	}
}
?>