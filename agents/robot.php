<?php

ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);

  ini_set('user_agent', 'Stackr Robots Checker (https://stackr.ca)');

require '/var/www/html/stackr.ca/vendor/autoload.php';

require_once '/var/www/html/stackr.ca/agents/variables.php';
//require_once '/var/www/html/stackr.ca/agents/usermanager.php';


class Robot {

	function __construct(Thing $thing)
    {
		$this->thing = $thing;
		$this->agent_name = 'robot';
        $this->agent_prefix = 'Agent "' . ucwords($this->agent_name) . '" ';

        $this->thing_report['thing'] = $this->thing->thing;


		// So I could call
		if ($this->thing->container['stack']['state'] == 'dev') {$this->test = true;}
		// I think.
		// Instead.

        $this->uuid = $thing->uuid;
        $this->to = $thing->to;
        $this->from = $thing->from;
        $this->subject = $thing->subject;

		$this->node_list = array("start"=>array("acknowledge"));

        $this->thing->log( $this->agent_prefix . 'running on Thing '. $this->thing->nuuid . '.</pre>');

        $this->variables_agent = new Variables($this->thing, "variables robot " . $this->from);
        $this->current_time = $this->thing->json->time();

        $this->useragent = ini_get("user_agent");
        $url = "https://stackr.ca"; 
        //$return_status = $this->robots_allowed($url, $this->useragent);

//        $return_status = $this->getRobots($url);


//    $parser = new RobotsTxtParser(file_get_contents('https://stackr.ca/robots.txt'));
//echo "meep";
//exit();
//var_dump($parser);
//exit();
//var_dump( $parser->isDisallowed('/path/to/page.html') );
//exit();
//exit();


        $this->get();
		$this->readSubject();

        $this->set();
 		$this->respond();

		$this->thing->flagGreen();

        $this->thing->log( $this->agent_prefix .'ran for ' . number_format($this->thing->elapsed_runtime()) . 'ms.' );

        $this->thing_report['etime'] = number_format($this->thing->elapsed_runtime());
        $this->thing_report['log'] = $this->thing->log;



    	return;
	}



    function set()
    {
        $this->variables_agent->setVariable("counter", $this->counter);
        $this->variables_agent->setVariable("refreshed_at", $this->current_time);

//        $this->thing->choice->save('usermanager', $this->state);

        return;
    }


    function get()
    {
        $this->counter = $this->variables_agent->getVariable("counter");
        $this->refreshed_at = $this->variables_agent->getVariable("refreshed_at");

        $this->thing->log( $this->agent_prefix .  'loaded ' . $this->counter . ".");

        $this->counter = $this->counter + 1;

        return;
    }


    function makeTXT()
    {
        $txt = 'User-agent: *';
        $txt .= "\n";

        $txt .= 'Disallow:';
        $txt .= "\n";


        $this->thing_report['txt'] = $txt;
        $this->txt = $txt;


    }



    private function makeSMS() {

        switch ($this->counter) {
            case 1:
                $sms = "ROBOT | No robot yet, please. Read our Privacy Policy https://stackr.ca/policy";
                break;

            case null;

            default:
                $sms = "ROBOT | No robots yet, please. https://stackr.ca/privacy";

        }

            //$sms .= " | counter " . $this->counter;

            $this->sms_message = $sms;
            $this->thing_report['sms'] = $sms;

    }


    private function makeEmail() {

        switch ($this->counter) {
            case 1:

                $subject = "Hello Robot";

                $message = "Email access is in limited beta. No robots please.
                    <br>
                    Keep on stacking.

                    ";
                break;
            case 2:
                $subject = "Robot";

                $message = "No robots please.\n\n";

                break;

            case null;

            default:
                $message = "ROBOT | Acknowledged.  https://stackr.ca/privacy";

        }

            $this->message = $message;
            $this->thing_report['email'] = $message;

    }


    private function makeChoices()
    {

            $choices = $this->thing->choice->makeLinks('start');

            $this->choices = $choices;
            $this->thing_report['choices'] = $choices;

    }



	public function respond() {

		// Thing actions

		// New user is triggered when there is no nom_from in the db.
		// If this is the case, then Stackr should send out a response
		// which explains what stackr is and asks either
		// for a reply to the email, or to send an email to opt-in@stackr.co.


		$this->thing->flagGreen();

		// Get the current user-state.



        $this->makeSMS();
        $this->makeEmail();
        $this->makeChoices();

        $this->thing_report['message'] = $this->sms_message;
        $this->thing_report['email'] = $this->sms_message;
        $this->thing_report['sms'] = $this->sms_message;

        // While we work on this
        $message_thing = new Message($this->thing, $this->thing_report);


        $this->makeTxt();

        $this->thing_report['info'] = $message_thing->thing_report['info'];

        $this->thing_report['help'] = $this->agent_prefix  .'responding to a message from a robot.';


		return;
	}



	public function readSubject() {
        $this->start();
//		$this->thing->choice->Choose("new user");
		return;		

	}


	function start() {

        // Call the Usermanager agent and update the state
        $agent = new Usermanager($this->thing, "robot start");
        $this->thing->log( $this->agent_prefix .'called the Usermanager to update user state to start.' );


		return;
	}



    function robots_allowed($url, $useragent=false)
    {
        // https://www.the-art-of-web.com/php/parse-robots/
        // parse url to retrieve host and path
        $parsed = parse_url($url);

        $agents = array(preg_quote('*'));
        if($useragent) $agents[] = preg_quote($useragent);
        $agents = implode('|', $agents);

        // location of robots.txt file
        $robotstxt = file("http://{$parsed['host']}/robots.txt");

        // if there isn't a robots, then we're allowed in
        if(empty($robotstxt)) return true;

        $rules = array();
        $ruleApplies = false;
        foreach($robotstxt as $line) {
        // skip blank lines
        if(!$line = trim($line)) continue;

        // following rules only apply if User-agent matches $useragent or '*'
        if(preg_match('#^\s*User-agent: (.*)#i', $line, $match)) {
            $ruleApplies = preg_match("#($agents)#i", $match[1]);
        }
        if($ruleApplies && preg_match('#^\s*Disallow:(.*)#i', $line, $regs)) {
            // an empty rule implies full access - no further tests required
            if(!$regs[1]) return true;
            // add rules that apply to array for testing
            $rules[] = preg_quote(trim($regs[1]), '/');
        }
    }

        foreach($rules as $rule) {
            // check if page is disallowed to us
            if(preg_match("#^$rule#", $parsed['path'])) return false;
        }

        // page is not disallowed
        return true;    
    }


    function getRobots($url)
    {
        $robotsUrl = $url . "/robots.txt";
          $robot = null;
          //create an object
          $allRobots = [];
          $fh = fopen($robotsUrl,'r');
          while (($line = fgets($fh)) != false) {
            echo $line . "<br>";
           if (preg_match("/user-agent.*/i", $line) ){
                if($robot != null){
                  array_push($allRobots, $robot);
                }

                $robot = new stdClass();
                $robot->userAgent = [];
                $robot->userAgent = explode(':', $line, 2)[1];
                $robot->disAllow = [];
                $robot->allow = [];


              }
            if (preg_match("/disallow.*/i", $line)){
              array_push($robot->disAllow, explode(':', $line, 2)[1]);
            }
            else if (preg_match("/^allow.*/i", $line)){
              array_push($robot->allow, explode(':', $line, 2)[1]);
           }


          }

          var_dump($line);

          if($robot != null){
            array_push($allRobots, $robot);
          }


          //Lazy way of outputting. Loop through for prettier output.
        echo "<pre>";
          var_dump($allRobots);
        echo "</pre>";
    }









}









?>