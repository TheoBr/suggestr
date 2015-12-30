<?php
// Page Controller for the Index Page
require_once(ROOT.'Controllers/suggestions.php');
//error_reporting(0);
class HomeController extends PageController {
	public $pageTemplate = "Home";
	public function predictClasses(){//Take in session_id

		$command = 'python schedule.py';// . json_encode($classes)
		$result = json_decode(shell_exec('python "schedule.py"'),true);
		return $result;
	}
	public function process($get, $post) {
		$_COOKIE['sessionId'] = 1;
		$this->pageData["Title"] = "Home";
		//Generate the data from mysql.

		//NEED TO FIGURE OUT WHY COOKIE IS NOT WORKING. HERE I SET IT manually.
		/*
		if(isset($_COOKIE['sessionId'])){
			echo "cookie set to: ".$_COOKIE['sessionId'];
		}else{

			//$_COOKIE['sessionId'] = rand(880,925);
			
			echo "cookie not set. now set to: ".$_COOKIE['sessionId'];
		}*/
		//setcookie('sessionId', rand(880,925), time()+315360000, '/');
		 
		
		//}
		
		//Something wrong when no classes are taken by a given session.
		//I'll try to fix that someday.
	


		$Data = new Database();

		//Setting up student
		//Replace with $session_id. The cookie doesn't work for me(Lucien) though...
		//$session = 912;//intval($_COOKIE['sessionId']);
		//echo "num: ".$session;
		$student = $Data->getStudent($_COOKIE['sessionId']);
		$studentCourses = $student->getTaken();

		//echo " Session: ".$student->getId()." major: ".$student->getMajor()." year: ".$student->getYear();
		
		foreach($studentCourses as $course){
	        $result = new Course();
	        $result->findById($course);
	        //echo "<h4>".$result->get('name')."<h4>";
		}

		//Here's the array of courses generated by the Jaccard index.
		$JaccardCourses = $Data->getSuggestedCourses($studentCourses);
		


		

		// Select all of the courses that this user is already added or ignored
		$query = new Query('action');
		$result = $query->select('*', array(array('session_id', '=', $_COOKIE['sessionId'])));
		$idsAlreadyAdded = array();
		foreach($result as $action){
			array_push($idsAlreadyAdded, $action->get('course_id'));
		}

		// Generate all of the courses (for testing)

		//Get list of predicted courses from Python.
		$predClasses = $this->predictClasses();

		$allCourses = array();
		$query = new Query('courses');

		//Adding predicted courses to the $allCourses array.
		/*foreach($predClasses as $class){
			$result = new Course();
			$result->findById($class['id']);
			array_push($allCourses,$result);
		}*/

		//Grabs the Id's from the Jaccard Array and 
		foreach($JaccardCourses as $class => $score){
			$result = new Course();
			$result->findById($class);
			array_push($allCourses,$result);
		}
		//$allcourses only contains the course id's

		
		//Create new array containing all the course details based on what is in Allcourses.
		$allNewCourses = array();
		foreach($allCourses as $course){
			try{
				if(!in_array($course->get('id'), $idsAlreadyAdded)){ // Check that this course has not been added by the user yet
					array_push($allNewCourses, array('id' => $course->get('id'),
												  'name' => ucwords(strtolower($course->get('name'))),
												  'department_id' => $course->get('department_id'),
												  'number' => $course->get('number'),
												  'description' => ((strlen($course->get('description'))==0)?'No description':$course->get('description')),
												  'allTags' => $Data->courseTags($course->get('id')),//Should contain 5 tags.
												  ));

				}//var_dump($Data->courseTags($course->get('id')));
			}catch(Exception $e){}
		}

		//Populate webpage with all the different courses that were predicted.
		$this->pageData['allCourses'] = $allNewCourses;
		
		/*
		IMPORTANT NODE


		*/
		//////////
		// Select all of the courses that this user is already added
		$query = new Query('action');
		$result = $query->select('*', array(array('session_id', '=', $_COOKIE['sessionId']),
											array('choice', '=', 1)));///is 0 in leo's version, 1 in my old database. we need to sort that shit.

		$idsAlreadyAdded = array();
		foreach($result as $action){
			array_push($idsAlreadyAdded, $action->get('course_id'));
		}
		
		// Get all of the courses in this user's session
		$usersCourses = array();
		foreach($idsAlreadyAdded as $courseId){
			try{
				$course = new Course();
				$course->findById($courseId);
				array_push($usersCourses, array('id' => $course->get('id'),
											  'name' => ucwords(strtolower($course->get('name'))),
											  'department_id' => $course->get('department_id'),
											  'number' => $course->get('number')));
			}catch(Exception $e){}
		}

		$this->pageData['usersCourses'] = $usersCourses;
		//Pushes all the new courses to the view.
		
	}
}

?>