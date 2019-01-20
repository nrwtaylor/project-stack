<?php
namespace Nrwtaylor\StackAgentThing;

ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);

ini_set("allow_url_fopen", 1);

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;
use \PDO;

class Database
{

	public $var = 'hello';

	function __construct($uuid, $nom_from)
    {
        $start_time = microtime(true);
        $this->start_time = $start_time;
        $this->split_time = $start_time;
        $this->operations_time = 0;
        $this->operations = 0;
        $this->log = array();
        //echo $this->get_calling_function() . "<br>";

		// Database controls access by $uuid.

		// Should know $nom_from of requester.

		// Thing Database services.
		// "Without a name AND a uuid I'm not doing anything."
		// Which I started at 10.10am.

		// Basic Database should only be able to query multiple records
		// by $nom_from, or by the records which contain it's UUid.

		// So if $uuid is blank, that's okay. > Return matching 
		// nom_from records.   And if $nom_from is blank
		// that's okay > Return matching uuid records.

		// The problem is when they are both null.
		// Code here should allow either.

		if (($nom_from == null) and ($uuid == null)) {throw new Exception('No 
			$nom_from and $uuid provided to Class Db.');}


		if ($nom_from == null) {throw new Exception('No $nom_from provided to
			Class Db.');}
		if ($uuid == null) {throw new Exception('No $uuid provided to
			Class Db.');}

		$this->from = $nom_from;
		$this->uuid = $uuid;


		// create container and configure it

        $settings = require $GLOBALS['stack_path'] . "private/settings.php";

        $this->web_prefix = $settings['settings']['stack']['web_prefix'];
        $this->state = $settings['settings']['stack']['state'];

        $this->container = new \Slim\Container($settings);

		// create app instance
		$app = new \Slim\App($this->container);
		$c = $app->getContainer();

// Haven't seen this triggered.
$c['errorHandler'] = function ($c) {
    return function ($request, $response, $exception) use ($c) {
        return $c['response']->withStatus(500)
                             ->withHeader('Content-Type', 'text/html')
                             ->write('AGENT | Maintenance.');
    };
};

		$c['db'] = function ($c) {
			$db = $c['settings']['db'];
			$pdo = new PDO("mysql:host=" . $db['host'] . ";dbname=" . $db['dbname'],
				$db['user'], $db['pass']);
			$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
			return $pdo;
			};

		$c['stack'] = function ($c) {
			$db = $c['settings']['stack'];
			return $db;
			};

        $this->get_calling_function();
        //        $this->caller = $r;
        // $t[count($t)-2];

        $this->test( "<b>".$this->get_calling_function()."</b>" );

        // NRW Taylor 12 June 2018
        // devstack Database for to disk persistent memory calls, redis for in ram persistent calls

		$this->char_max = $c['stack']['char_max'];

		$this->uuid = $uuid;


        $this->test("Database set-up ");

		// Which means at this point, we have a UUID
		// whether or not the record exists is another question.

		// But we don't need to find, it because the UUID is randomly created.
		// Chance of collision super-super-small.

		// So just return the contents of thing.  false if it doesn't exist.

        $this->split_time = microtime(true);

        $r = "";

		return $r;
	}

    function __destruct()
    {
        // Log database transactions in test
        $this->test("Database destruct ");
    }

    function test($comment = null, $instruction=null)
    {

        if ($this->state != "test") {return;}

        if (($comment ==null) and ($instruction == null)) {
            $comment ="";
        }

        // echo in test
        // devstack save to variable
        echo "<pre>";
        echo substr($this->uuid,0,4) . "-" . $this->caller . " ";
        echo str_pad(number_format((microtime(true) - $this->start_time)*1000) ."ms", 0, " ",STR_PAD_RIGHT);

        echo "[" . number_format($this->operations_time * 1e3) . "ms]";
        echo " ". $comment. " ";
        echo "split time " . number_format( (microtime(true) - $this->split_time)*1000)."ms " ;
        echo "operations " . $this->operations . " ";
        echo "<br>";

        foreach ($this->log as $key=>$value) {
            echo str_pad("",4, " ") .   $value . "<br>";
        }
        $this->log = array();
        echo "</pre>";
    }

    function get_calling_function()
    {
        // see stackoverflow.com/questions/190421
        $caller = debug_backtrace();

        // echo "function ". debug_backtrace()[1]['function'];

        $caller = $caller[2];
        $r = $caller['function'] . '()';

        if (isset($caller['class'])) {
            $r .= ' called by ';
            // $r .= $caller['class'];
            $t = explode("\\",$caller['class']);
            $r .= "" . $t[count($t) -1];
        }

        $this->caller = $t[count($t)-1];

        $r .= "\\";
        $r .=  debug_backtrace()[1]['function'];

        if (isset($caller['object'])) {
            //$r .= ' (' . get_class($caller['object']) . ')';
        }
        return $r;
    }


	function priorGet($created_at = null)
    {
		// Given a $uuid.  Find the previous record the $from user
		// created.

		// Review thought.  Wouldn't searching for the latest record 
		// before the time_stamp be more efficient?

        // http://stackoverflow.com/questions/28451031/how-to-get-second-last-row-from-mysql-database
        // Doesn't work in case of same time_stamp.
        // That is acceptable.  A second resolution for creating records is 
        // likely a good limit.  Easy to upgrade by adding a 'microsecond' column to the
        // database.

        // Change to InnoDB means stack is likely now working on microsecond
        // time quantum.

        // sqlinjection commentary
        // nom_from is a carrier provided identifier, therefore judged safe to
        // pass by message carriers.
        // created_at is a stack created field

		if ($created_at == null) {
			$query_string = "SELECT * FROM (SELECT * FROM stack WHERE
				nom_from='". $this->from . 
				"' ORDER BY created_at DESC LIMIT 2) AS t ORDER BY created_at ASC LIMIT 2";

		} else {
			$query_string = "SELECT * FROM stack where nom_from = '" . $this->from . "' and created_at < '" . $created_at . "' order by created_at DESC LIMIT 3";
		};

	    $sth = $this->container->db->prepare($query_string);
	    $sth->execute();
		$thing = $sth->fetchObject();

		$thingreport = array('thing' => $thing, 'info' => 'Turns out it has an imperfect and forgetful memory.  But you can see what is on the stack by typing ' . $this->web_prefix . 'api/thing/<32 characters>.','help' => 'Check your junk/spam folder.');

        // Runs in 0 to 8ms

		return $thingreport;
	}

	function writeField($field_text, $string_text) 
    {
        $this->split_time = microtime(true);
        $this->log = array($field_text, $string_text);
        //$this->test( $this->get_calling_function() );

        // sqlinjection commentary
        // user provided string_text
        // stack provided field_text

        try {
            $query = "UPDATE stack SET $field_text=:string_text WHERE uuid=:uuid";

            $sth = $this->container->db->prepare($query);

            $sth->bindParam(":uuid", $this->uuid);
		    $sth->bindParam(":string_text", $string_text);

            // This is not allowed by PHP.
            // Noting that field_text is generated by an Agent.  Not channel input.
            //$sth->bindParam(":field_text", $field_text);

            $sth->execute();

        } catch (\Exception $e) {
            // Devstack - decide how to handle thing full
            // Do this for now.

            $t = new Thing(null);
            $t->Create("meep","meep",$e->getMessage());
            $t = new Bork($t);
            echo "BORK | Thing is full.";
            //echo 'Caught error: ',  $e->getMessage(), "\n";
            $thing = false;
        }
        $this->operations_time += (microtime(true) - $this->split_time);
        $this->operations += 1;

        $this->test("writeField");

		return;
	}


    function count()
    {
        $sth = $this->container->db->prepare("SELECT count(*) FROM stack");
        $sth->execute();

        $thing_count = $sth->fetchColumn();

        $thingreport = array('things' => false, 
            'info' => 'Counted ' . $thing_count . '  records on stack.',
            'help' => 'This is how big the stack is.');
        $thingreport['number'] = $thing_count;

        return $thingreport;
    }

	function readField($field)
    {
		$thingreport = $this->Get();
		$this->thing = $thingreport['thing'];

		if (isset($this->thing->$field)) {
			// I think I should also do
			$this->$field = $this->thing->$field;
			return $this->thing->$field;
		} else {
			return false;
		}
	}

	function Create($subject, $to)
    {
		// Create a new record in the db for the Thing.
        $this->split_time = microtime(true);

        //$this->test("Create");

	    $query = $this->container->db->prepare("INSERT INTO stack
			(uuid,task,nom_from,nom_to)
			VALUES (:uuid, :task, :nom_from, :nom_to)");

		$query->bindParam(':uuid', $uuid);
		$query->bindParam(':task', $task);
		$query->bindParam(':nom_from', $nom_from);
		$query->bindParam(':nom_to', $nom_to);

		$uuid = $this->uuid;
		$task = $subject;
		$nom_from = $this->from;
		$nom_to = $to;

		$query->execute();

		return $query;
	}

	function Get()
    {
		// But we don't need to find, it because the UUID is randomly created.	
		// Chance of collision super-super-small.

		// So just return the contents of thing.  false if it doesn't exist.

        try {
            // Trying long form.  Doesn't seme to have performance advantage.
            $sth = $this->container->db->prepare("SELECT uuid, task, nom_from, nom_to, created_at, message0, settings, variables FROM stack WHERE uuid=:uuid");

            //$sth = $this->container->db->prepare("SELECT * FROM stack WHERE uuid=:uuid");
            $sth->bindParam("uuid", $this->uuid);
        	$sth->execute();
            $thing = $sth->fetchObject();
        } catch (\Exception $e) {
            echo 'Caught error: ',  $e->getMessage(), "\n";
            $thing = false;
		}

		$thingreport = array('thing' => $thing, 'info' => 'Turns out it has an imperfect and forgetful memory.  But you can see what is on the stack by typing ' . $this->web_prefix . 'api/thing/<32 characters>.','help' => 'Check your junk/spam folder.');

        $this->test();

        return $thingreport;
    }

	function Forget()
    {
        $sth = $this->container->db->prepare("DELETE FROM stack WHERE uuid=:uuid");
        $sth->bindParam("uuid", $this->uuid);
        $sth->execute();

        $thingreport = array('info' => 'That thing was forgotten.');
        return $thingreport;
	}

	function setUser($id = null)
    {

		if ($id == null) {$id = "null@" . $settings['settings']['stack']['mail_postfix'];}
		$this->from = $id;
		return;
	}

/*
    // Add bindparam
	function userRecords($user_search, $limit = null)
    {

		if ($limit == null) {$limit = 3;}

		$user_search = $this->from;
		$query = "SELECT * FROM stack WHERE nom_from LIKE '%$user_search%' ORDER BY created_at DESC LIMIT $limit";

		$sth = $this->container->db->prepare($query);

		$sth->execute();
		$things = $sth->fetchAll();

		$thingreport = array('thing' => $things, 'info' => 'So here are three things you put on the stack.  That\'s what you wanted.','help' => 'It is up to you what you do with these.');

		return $thingreport;
	}
*/

	function setFrom($nom_from)
    {
        $this->from = $nom_from;
        return;
    }


    function variableSearch($path, $value, $max = null)
    {

        if ($max == null) {$max = 3;}
        $max = (int) $max;

        $user_search = $this->from;

        // https://stackoverflow.com/questions/11068230/using-like-in-bindparam-for-a-mysql-pdo-query
        $value = "%$value%"; // Value to search for in Variables
  //      $value = "*$value*"; // Value to search for in Variables

//        $max = (int) $max;

        $thingreport['things'] = array();

        try{
            $value = "%$value%"; // Value to search for in Variables
            $query = "SELECT * FROM stack WHERE nom_from=:user_search AND variables LIKE :value ORDER BY created_at DESC LIMIT :max";
            // $query = "SELECT * FROM stack WHERE nom_from=:user_search AND MATCH(variables) AGAINST(:value IN BOOLEAN MODE ) ORDER BY created_at DESC LIMIT :max";
            // $query = "SELECT * FROM stack WHERE nom_from=:user_search AND MATCH(variables) AGAINST(:value IN BOOLEAN MODE ) ORDER BY created_at DESC LIMIT :max";
            // $query = "SELECT uuid, task, nom_from, nom_to, created_at, message0, settings, variables FROM stack WHERE nom_from=:user_search AND MATCH(variables) AGAINST(:value IN BOOLEAN MODE ) ORDER BY created_at DESC LIMIT :max";

            // $value = "*$value*"; // Value to search for in Variables
            // $query = "SELECT uuid, variables FROM stack WHERE nom_from=:user_search AND MATCH(variables) AGAINST(:value IN BOOLEAN MODE ) ORDER BY created_at DESC LIMIT :max";

            $sth = $this->container->db->prepare($query);

            $sth->bindParam(":user_search", $user_search);
            $sth->bindParam(":value", $value);
            $sth->bindParam(":max", $max, PDO::PARAM_INT);
            $sth->execute();

            $things = $sth->fetchAll();

            $thingreport['info'] = 'So here are Things with the variable you provided in \$variables. That\'s what you want';
                $thingreport['things'] = $things;

        } catch(\PDOException $e) {
            // echo "Error in PDO: ".$e->getMessage()."<br>";
            $thingreport['info'] = $e->getMessage();
            $thingreport['things'] = [];
        }


        return $thingreport;
	}

    function agentSearch($agent, $max = null)
    {

        if ($max == null) {$max = 3;}
        $max = (int) $max;

		$user_search = $this->from;
        //$user_search= "%$user_search%"; // Value to search for in Variables

        //$query = "SELECT * FROM stack WHERE nom_from LIKE :user_search AND nom_to = :agent ORDER BY created_at DESC LIMIT :max";
        $query = "SELECT * FROM stack WHERE nom_from = :user_search AND nom_to = :agent ORDER BY created_at DESC LIMIT :max";

		$sth = $this->container->db->prepare($query);
        $sth->bindParam(":user_search", $user_search);
        $sth->bindParam(":agent", $agent);
        $sth->bindParam(":max", $max, PDO::PARAM_INT);
		$sth->execute();

		$things = $sth->fetchAll();

		$thingreport = array('things' => $things, 'info' => 'So here are Things with the phrase you provided in \$variables. That\'s what you wanted.','help' => 'It is up to you what you do with these.', 'whatisthis' => 'A list of Things which match at the provided phrase.');
		return $thingreport;
	}


    // add bindparam
	function userSearch($keyword)
    {
		$user_search = $this->from;
        $keyword = "%$keyword%"; // Value to search for in Variables

//		$query = "SELECT * FROM stack WHERE nom_from LIKE '%$user_search%' AND task LIKE '%$keyword%' ORDER BY created_at DESC";
//      $query = "SELECT * FROM stack WHERE nom_from = :user_search AND task LIKE '%$keyword%' ORDER BY created_at DESC";
        $query = "SELECT * FROM stack WHERE nom_from = :user_search AND task LIKE :keyword ORDER BY created_at DESC";

        try {
    		$sth = $this->container->db->prepare($query);

            $sth->bindParam(":user_search", $user_search);
            $sth->bindParam(":keyword", $keyword);

		    $sth->execute();
		    $things = $sth->fetchAll();

        } catch (\Exception $e) {
            echo 'Caught error: ',  $e->getMessage(), "\n";
            $things = false;
        }

		$thingreport = array('thing' => $things, 'info' => 'Searches by nom_from and task.','help' => 'Keyword subject line search.');

		return $thingreport;
	}

/*
    // add bindparam
    function userThings() {

        $user_search = $this->from;

        $query = "SELECT * FROM stack WHERE nom_from LIKE '%$user_search%' ORDER BY created_at DESC";


        $sth = $this->container->db->prepare($query);

        $sth->execute();

        //$this->container->db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);

        //if (!isset($this->test_count)) {$this->test_count = 0;}
        //$this->test_count += 1;
        //echo $this->test_count;
        $this->user_things = $sth;
        $this->user = $this->from;
        //$things = $sth->fetchAll();

        $thing_report['thing'] = false;
        $thing_report['info'] = 'So here is a db pointer.';
        $thing_report['help'] = 'No help';

        return $thing_report;
    }
*/
    function userNextThing()
    {
        if (!isset($this->user_things)) {$this->userThings();}

        if ($this->from != $this->user) {return;}

        $thing = $this->user_things->fetch();
        //$things = $sth->fetchAll();

        $thing_report['thing'] = $thing;
        $thing_report['info'] = 'So here is the next thing.';
        $thing_report['help'] = 'No help';

        return $thing_report;
    }
/*
    // deprecated
	function userState()
    {
		$useremail = $this->from;

		// First question is there an opt-in or opt-out
		$thingreport = $this->userSearch("Use agreement: Opt-");
		if (count($thingreport['thing']) == 0) {
			// No opt-in records found

			// Second question.  Have the terms and conditions been sent.

			$thingreport = $this->userSearch("Terms and Conditions of Use");

			if (count($thingreport['thing']) != 0) {
				// No records found
				return "terms sent";
			}

			$thingreport = $this->userSearch("");

			$thingreport = $this->userRecords($useremail);

			if (count($thingreport['thing']) == 0) {
				// No records found
				return "not found";
			} else {
				return "found";
			}

        } else {
				$t = $thingreport['thing'][0];

				if ($t['task'] == "Use agreement: Opt-in") {return "opt-in";}
				if ($t['task'] == "Use agreement: Opt-out") {return "opt-out";}
		}

		return false;
	}
*/
	private function updateThing()
    {
		$this->uuid = $thing->uuid;
		$this->to = $thing->nom_to;
		$this->from = $thing->nom_from;
		$this->subject = $thing->task;
		return "Thing read " . $this->uuid;
	}

/*
    // deprecated
	function keyword() {

		$sth = $this->container->db->prepare("SELECT * FROM stack WHERE nom_to NOT LIKE '%@%' ORDER BY RAND( ) LIMIT 1");


		//$sth->bindParam("nom_from", $nom_from);
		$sth->execute();
		$thing = $sth->fetchObject();

		$this->uuid = $thing->uuid;
		$this->to = $thing->nom_to;
		$this->from = $thing->nom_from;
		$this->subject = $thing->task;

		$thingreport = array('thing' => $thing, 'info' => 'So here are three things you put on the stack.  That\'s what you wanted.','help' => 'It is up to you what you do with these.');

		return $thingreport;
	}
*/
/*
    // add bindparam
	function byWordlist(Array $words) {

		$word_list = implode("|", $words);

		$query = "SELECT * FROM stack
			WHERE variables REGEXP '$word_list'
			ORDER BY RAND()
			";

		$sth = $this->container->db->prepare($query);

		//$sth->bindParam("nom_from", $nom_from);
		$sth->execute();
		//$thing = $sth->fetchObject();
		$things = $sth->fetchAll();


		$thingreport = array('thing' => $things, 'info' => 'So here are Things matching at least one of the words provided. That\'s what you wanted.','help' => 'It is up to you what you do with these.', 'whatisthis' => 'A list of Things which match at least one keyword.');



		return $thingreport;
	}
*/

	function validateThing()
    {

		if ((!isset($this->from)) and (!isset($this->uuid))) {throw new Exception('$this->from and $this->uuid not set.  Required.');}

		// Fail if a null nom_from is provided
		if ( $this->from == null ) {throw new Exception('$this->nom_from set as null.  Required.');}

	    // Every Thing should be able to do this.
	    // but which Things reply should be private to the Things.
	    // But for accounting a Thing has to be able to say 
	    // "Do you know me. Tell me your balance"
	    //
	    // This will need to be a public Stack variable.
	
	    // Double-UU intentionally.

		return;
	}

	function UUids($uuid = null)
    {
		// Either $uuid or $nom_from might not be set.
		// UUids requires both to be set to do a search
		// for all records contain the $uuid.

		$this->validateThing(); // Throws exception if request
								// is asking for too much.

		// Do a self look-up if no uuid provided.
		if ( $uuid == null ); {

			if ( $this->uuid == null ) {

                $thingreport = array('things' => false); 
	        	$thingreport['info'] = 'So here are the uuids of all records matching your request.  That\'s what you wanted.';
		        $thingreport['help'] = 'No matching records returned.';

				return $thingreport;

			} else {
				$uuid = $this->uuid;
			}
		}

		// Should be able to request multiple Things uuids at the same time.
		if ( is_string($uuid) ); {$task_inclusions = array($uuid);}

		// Should not be able to request by nom_from.
		// So commented out here.
		//if ( is_string($nom_from) ); {$nom_to_inclusions = array($nom_from);
		//$nom_from = 'bob@example.com';
		//$nom_to_inclusions = array($nom_from);
		// For stack_db.php

		$task_exclusions = null;

        $querable_fields = array('nom_from','nom_to','associations','task','message0','settings');

		// Double capitals to make you thing before you ask a Thing
		// about what other Things it knows.

		// Which is why this only returns 5 to 7.  Mostly.

		$mostly = rand(0,3) + 4;

		$task_sql = "";
		$exclusion_sql = "";
		$inclusion_sql = "";
		$sub_query = "";

		foreach ($task_inclusions as $task_inclusion) {

// Example:
// SELECT * FROM stack WHERE nom_from='<blank>' and ((task not like '%?%') or ($field not like '%transit%') or ($field not like '%test%')) ORDER BY RAND() LIMIT 3;

			if ($task_inclusions == null) {
				$task_sql = "";
			} else {
				$or = "";
				$task_sql = "";
				foreach ($querable_fields as $querable_field) {
					$task_sql .= $or . "($querable_field like '%" . $task_inclusion . "%')";
					if (count($task_inclusions) > 0) {$or = " or ";}
				}
			}

		    $inclusion_sql .= $task_sql;

//		if ($task_exclusions == null) {
//			$task_sql = "";
//		} else {
//			$or = "";
////			$task_sql = "";
//			foreach ($querable_fields as $querable_field) {
//				$task_sql .= $or . "($querable_field not like '%" . $task_exclusion . "%')";
//				if (count($task_exclusion) > 1) {$or = " or ";}
//			}
//		}

//		$exclusion_sql .= $task_sql;

// It's the or here that makes the exclusion script fail.
//		$sub_query .= "' and (" . $inclusion_sql . " or " . $exclusion_sql . ")";
		    $sub_query .= "' and (" . $inclusion_sql . ")";

        }

        $query = "SELECT * FROM stack WHERE nom_from='". $this->from . $sub_query . " ORDER BY RAND()"; // LIMIT 3";

		$sth = $this->container->db->prepare($query);
		$sth->bindParam("nom_from", $this->from);
		$sth->execute();
		$things = $sth->fetchAll();


		$thingreport = array('things' => $things, 'info' => 'So here are the uuids of all records matching your request.  That\'s what you wanted.','help' => 'It is up to you what you do with these.');

		return $thingreport;
    }


/*
    // add bindparam
	function byPhrase($phrase){

		$query = "SELECT * FROM stack
			WHERE variables LIKE '%$phrase%'
			ORDER BY RAND()
			";

		$sth = $this->container->db->prepare($query);
		$sth->execute();
		$things = $sth->fetchAll();


		$thingreport = array('thing' => $things, 'info' => 'So here are Things with the phrase you provided in \$variables. That\'s what you wanted.','help' => 'It is up to you what you do with these.', 'whatisthis' => 'A list of Things which match at the provided phrase.');

		return $thingreport;
	}
*/

/*
    // add bindparam
	function excludeWordlist(Array $words) {
        //http://www.sqltrainingonline.com/sql-not-like-with-multiple-values/

		// SELECT * FROM stack WHERE not (variables like '%dispatch%' or variables like '%iching%' or variables like '%credit%');
		$query = 'SELECT * FROM stack WHERE not (';


		$flag = false;
		foreach ($words as $word) {
			if ($flag == true) {$query .= ' OR ';}
			$query .= "variables LIKE '%$word%'";
			$flag = true;
		}
		$query .= ')';

		$sth = $this->container->db->prepare($query);

		//$sth->bindParam("nom_from", $nom_from);
		$sth->execute();
		//$thing = $sth->fetchObject();
		$things = $sth->fetchAll();


		$thingreport = array('thing' => $things, 'info' => 'So here are Things matching at least one of the words provided. That\'s what you wanted.','help' => 'It is up to you what you do with these.', 'whatisthis' => 'A list of Things which match at least one keyword.');



		return $thingreport;
	}
*/

	function getRed()
    {
        // Get all red items on the stack.
        // Not an identity function.
        //http://www.sqltrainingonline.com/sql-not-like-with-multiple-values/

		$search_term = "'%{\"status\":\"red\"}%'";

		$query = "SELECT * FROM stack WHERE variables LIKE " . $search_term;

		$sth = $this->container->db->prepare($query);
		$sth->execute();
		//$thing = $sth->fetchObject();
		$things = $sth->fetchAll();

		//$this->uuid = $thing->uuid;

		//$this->to = $thing->nom_to;
		//$this->from = $thing->nom_from;
		//$this->subject = $thing->task;

		$thingreport = array('thing' => $things, 'info' => 'So here are Things which are flagged red.','help' => 'It is up to you what you do with these.', 'whatisthis' => 'A list of Things which have status red.');

		return $thingreport;
	}

	public static function getNew()
    {
		$query = "SELECT * FROM stack WHERE variables is NULL";

		$sth = $this->container->db->prepare($query);
		$sth->execute();
		$things = $sth->fetchAll();

		$thingreport = array('thing' => $things, 'info' => 'So here are Things which are flagged red.','help' => 'It is up to you what you do with these.', 'whatisthis' => 'A list of Things which have status red.');

		return $thingreport;
    }

	function connections()
    {
		// NOT TESTED

		$query = "SHOW STATUS WHERE `variable_name` = 'Threads_connected'";

		$sth = $this->container->db->prepare($query);
		$sth->execute();
		$response = $sth->fetchAll();

		$keys = array_keys($response);

		$thingreport = array('thing' => false, 'db' => $response, 'info' => 'So here are Things matching at least one of the words provided. That\'s what you wanted.','help' => 'It is up to you what you do with these.', 'whatisthis' => 'A list of Things which match at least one keyword.');

		//$thingreport = false;

		return $thingreport;
	}

	function random($nom_from = null, $n = 1)
    {

        if ($nom_from == null) {
            // https://explainextended.com/2009/03/01/selecting-random-rows/
            // https://stackoverflow.com/questions/1244555/how-can-i-optimize-mysqls-order-by-rand-function
/*
            $q = "SELECT  *
                FROM    (
                    SELECT  @cnt := COUNT(*) + 1,
                        @lim := 1
                FROM stack
                ) vars
                STRAIGHT_JOIN
                (
                SELECT  r.*,
                    @lim := @lim - 1
                    FROM    stack r
                    WHERE   (@cnt := @cnt - 1)
                    AND RAND(20090301) < @lim / @cnt
                ) i";
*/
//            $q = "SELECT * FROM stack WHERE RAND()<=0.0006 limit 1";
//            $q = "SELECT * FROM stack WHERE RAND()<(SELECT ((1/COUNT(*))*10) FROM stack) LIMIT 1";
//            $q = "SELECT * FROM stack WHERE RAND()<(SELECT ((1/COUNT(*))*10) FROM stack) LIMIT 1";
//            $q = "SELECT * FROM stack WHERE RAND()<(SELECT ((1/COUNT(*))*10) FROM stack) ORDER BY RAND() LIMIT 1";

            $q = "SELECT * FROM stack WHERE RAND()<(SELECT ((" . $n . "/COUNT(*))*10) FROM stack) ORDER BY RAND() LIMIT " . $n;

//            $q = "SELECT * FROM stack ORDER BY RAND() LIMIT " . $n;
//            $q = "SELECT * FROM stack WHERE RAND()<(SELECT ((20/COUNT(*))*10) FROM stack) ORDER BY RAND() LIMIT 20";

            $sth = $this->container->db->prepare($q);

    		$sth->execute();
//	    	$thing = $sth->fetchObject();
            $things = $sth->fetchAll();

  //  		$this->to = $thing->nom_to;
//	    	$this->from = $thing->nom_from;
//		    $this->subject = $thing->task;

		    $thingreport = array('things' => $things, 'info' => 'So here are three things you put on the stack.  That\'s what you wanted.','help' => 'It is up to you what you do with these.');

        } else {

            $q = "SELECT * FROM stack WHERE RAND()<(SELECT ((1/COUNT(*))*10) FROM stack) ORDER BY RAND() LIMIT 1";
            $sth = $this->container->db->prepare($q);

            $sth->execute();
            $thing = $sth->fetchObject();

            $this->to = $thing->nom_to;
            $this->from = $thing->nom_from;
            $this->subject = $thing->task;

            $thingreport = array('things' => $thing, 'info' => 'So here are three things you put on the stack.  That\'s what you wanted.','help' => 'It is up to you what you do with these.');

        }

		return $thingreport;
	}

	function randomN($nom_from, $n=3) 
    {

        // Pick N of identity's things.
		$sth = $this->container->db->prepare("SELECT * FROM stack WHERE nom_from=:nom_from ORDER BY RAND() LIMIT 3");
		$sth->bindParam("nom_from", $nom_from);
		$sth->execute();
		$things = $sth->fetchAll();

		$thingreport = array('thing' => $things, 'info' => 'So here are three things you put on the stack.  That\'s what you wanted.','help' => 'It is up to you what you do with these.');

		return $thingreport;
    }

    // code review
	function reminder($nom_from, $task_exclusions = null, $nom_to_exclusions = null)
    {
        // Example:
        // SELECT * FROM stack WHERE nom_from='test@test.test' and ((task not like '%?%') or (nom_from not like '%transit%') or (nom_from not like '%test%')) ORDER BY RAND() LIMIT 3;

        // sqlinjection comment
        // $task_exlusions and $nom_to_exclusions are code defined

		if ($task_exclusions == null) {
			$task_exclusions = "";
		} else {
			$and = "";
			$task_sql = "(";
			foreach ($task_exclusions as $task_exclusion) {
				$task_sql .= $and . "task not like '%" . $task_exclusion . "%'";
				$and = " and ";
			}
			$task_sql .= " and task not like ''";
			$task_sql .= ")";
		}

		if ($nom_to_exclusions == null) {
			$nom_to_exclusions = "";
		} else {
			$nom_to_sql = "(";
			$and = "";
			//$nom_to_sql = "";
			foreach ($nom_to_exclusions as $nom_to_exclusion) {
				$nom_to_sql .= $and . "nom_to not like '%" . $nom_to_exclusion . "%'";
				$and = " and ";
			}
			$nom_to_sql .= ")";
		}

        $query = "SELECT * FROM stack WHERE nom_from='". $nom_from . "' and (" . $task_sql . " and " . $nom_to_sql . ") ORDER BY RAND() LIMIT 3";

		$sth = $this->container->db->prepare($query);
		$sth->bindParam("nom_from", $nom_from);
		$sth->execute();
		$things = $sth->fetchAll();


		$thingreport = array('thing' => $things, 'info' => 'So here are three things you put on the stack.  That\'s what you wanted.','help' => 'It is up to you what you do with these.');

		return $thingreport;
    }

}


// https://insomanic.me.uk/php-trick-catching-fatal-errors-e-error-with-a-custom-error-handler-cea2262697a2
//set_error_handler('myErrorHandler');
register_shutdown_function('Nrwtaylor\StackAgentThing\fatalErrorShutdownHandler');

function myErrorHandler($errno, $errstr, $errfile, $errline)
{
    if ( E_RECOVERABLE_ERROR===$errno ) {
        //ob_clean();
        //echo "BORK | Bounty ";
        //ob_clean();
        if (ob_get_contents()) ob_clean();
        echo "BORK | 3797e2c1-6585-4ae8-a256-b3e5466c980f ";
        //echo "'caught' fatal error E_RECOVERABLE_ERROR\n";
        return true;
    } elseif ( E_ERROR===$errno ) {
        // If there is stuff in the screen buffer clear it.

        if (ob_get_contents()) ob_clean();
        //ob_clean();
        echo "BORK | e5ffb5de-a502-466e-8ecc-f0ec9f861e0d";
        if(preg_match('(Maximum|execution|time|exceeded)', $errstr) === 1) {
            echo " | We only a limited amount of steam on this server.";
        }

        // echo $errno . "\n";
        // echo $errstr . "\n";
        // echo $errfile . "\n";
        // echo $errline . "\n";

//        echo "'caught' fatal E_ERROR | BOUNTY\n";
//        $actual_link = "meep";
        // For debugging.
        // https://stackoverflow.com/questions/6768793/get-the-full-url-in-php
//        $actual_link = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
//        echo $actual_link;

        return true;
    }
    if (ob_get_contents()) ob_clean();

    //ob_clean();
    echo "BORK | 00539cf2-0f56-495a-87df-64746cebfd41";
    echo "'dropped' catchable fatal error\n";
    return false;
}

function fatalErrorShutdownHandler()
{
    // Options

    // This displays on each uuid end-point
    // echo "#test test test global notification";
    // exit(); // will not stop display of full Stackr response

    // Present blank screen
    // ob_clean();
    // Add text.
    //echo "BORK | Bounty ";

    // This is the end-point to the full Stackr response.
    // exit(); // has no effect/affect
    // Present blank screen
    // ob_clean();

    //Add text.
    //echo "BORK | Bounty ";
    //exit();

    $last_error = error_get_last();
    if ($last_error['type'] === E_ERROR) {
        // fatal error
        myErrorHandler(E_ERROR, $last_error['message'], $last_error['file'], $last_error['line']);
    }
        

    // This is the end-point to the full Stackr response.
    // 
    //ob_clean(); // Stops buttons and footer.
    //Add text.
    //echo "BORK | No response."; // Will appear in the web page
    // An exit here will cut off the footer display.
    //exit();


}

?>
