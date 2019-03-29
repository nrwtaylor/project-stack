<?php
namespace Nrwtaylor\StackAgentThing;

ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);

ini_set("allow_url_fopen", 1);

// Canadian Hydrographic Service
class CHS extends Agent
{
    // SOAP needs enabling in PHP.ini

    // https://www.waterlevels.gc.ca/docs/Specifications%20-%20Spine%20observation%20and%20predictions%202.0.3(en).pdf

    // https://www.waterlevels.gc.ca/eng/info/Webservices
    // Canadian Hydrographic Service

    // License required from Canadian Hydrographic Service to re-publish.
    // https://www.waterlevels.gc.ca/eng/info/Licence

    //  “This product has been produced by or for
    // [insert User's corporate name] and includes data and
    // services provided by the Canadian Hydrographic Service
    // of the Department of Fisheries and Oceans. The
    // incorporation of data sourced from the Canadian
    //  Hydrographic Service of the Department of Fisheries
    // and Oceans within this product does NOT constitute an
    // endorsement by the Canadian Hydrographic Service or
    // the Department of Fisheries and Oceans of this product.”

    public $var = 'hello';

    function init()
    {
        $this->keyword = "environment";

        $this->agent_prefix = 'Agent "Weather" ';

        $this->keywords = array('water', 'level', 'tide', 'tides',
            'height', 'prediction', 'metocean', 'tides','nautical');

        $this->variables_agent = new Variables($this->thing, "variables " . "weather" . " " . $this->from);

        // Loads in Weather variables.

        if ($this->verbosity == false) {$this->verbosity = 2;}

        // Create the SoapClient instance
        $url         = "https://ws-shc.qc.dfo-mpo.gc.ca/predictions" . "?wsdl"; 
        $this->client     = new \SoapClient($url, array("trace" => 1, "exception" => 0));

        $this->getWeather();
    }

    function set()
    {
        $this->variables_agent->setVariable("state", $this->state);

        $this->variables_agent->setVariable("verbosity", $this->verbosity);

        if (!isset($this->current_conditions)) {$this->current_conditions = null;}
        if (!isset($this->forecast_conditions)) {$this->forecast_conditions = null;}

        $this->variables_agent->setVariable("current_conditions", $this->current_conditions);
        $this->variables_agent->setVariable("forecast_conditions", $this->forecast_conditions);

        $this->variables_agent->setVariable("refreshed_at", $this->current_time);

        $this->refreshed_at = $this->current_time;
    }

    function get()
    {
        $this->state = $this->variables_agent->getVariable("state")  ;

        $this->last_current_conditions = $this->variables_agent->getVariable("current_conditions")  ;
        $this->last_forecast_conditions = $this->variables_agent->getVariable("forecast_conditions")  ;

        $this->last_refreshed_at = $this->variables_agent->getVariables("refreshed_at");

        $this->verbosity = $this->variables_agent->getVariable("verbosity")  ;
    }

    function getWeather()
    {
        $this->predictions = array();
        $this->stations = array();
        $today = date("Y-m-d");

        $date = "2018-03-30"; // for testing
        $date = $today;

        $lat = 49.30;
        $long = -122.86;

        $units = "m";

        $size = 0.5;

        // example from CHS
        // $m = $client->search("hilo", 47.5, 47.7, -61.6, -61.4, 0.0, 0.0, $date . " ". "00:00:00", $date . " " . "23:59:59", 1, 100, true, "", "asc");

        $m = $this->client->search("hilo", $lat - $size, $lat + $size, $long - $size, $long + $size, 0.0, 0.0, $date . " ". "00:00:00", $date . " " . "23:59:59", 1, 100, true, "", "asc");

        //var_dump($m->data);
        foreach ($m->data as $key=>$item) {

            //echo "station" . $value->metadata[0]->value . " ";
            //echo $value->metadata[1]->value . "";

            $date_min = $item->boundaryDate->min;
            $date_max = $item->boundaryDate->max;

            if ($date_min == $date_max) {
                // expected
                $date = $date_min;
            } else {
                $date = true;
            }

            $name = $item->metadata[1]->value;
            $id = $item->metadata[0]->value;
            $value = $item->value;

            $prediction = array("date"=>$date, "name"=>$name, "id"=>$id, "value"=>$value ,"units"=>$units,"item"=>$item);
            $this->predictions[] = $prediction;
            $this->stations[$name] = $prediction;
        }

        $this->refreshed_at = $this->current_time;
    }

    function getTemperature()
    {
        // devstack not finished
        if (!isset($this->conditions)) {$this->getWeather();}
        $this->current_temperature = -1;

    }

    function match_all($needles, $haystack)
    {
        if(empty($needles)){
            return false;
        }

        foreach($needles as $needle) {
            if (strpos($haystack, $needle) == false) {
                return false;
            }
        }
        return true;
    }

	public function respond()
    {

		// Thing actions
		$this->thing->flagGreen();
		// Generate email response.

		$to = $this->thing->from;
		$from = "chs";

        $choices = false;
		$this->thing_report['choices'] = $choices;

        $this->makeSms();
        $this->makeMessage();

        $this->thing_report['email'] = $this->sms_message;
        //$this->thing_report['message'] = $this->sms_message; // NRWTaylor 4 Oct - slack can't take html in $test_message;
        $this->thing_report['txt'] = $this->sms_message;

        if ($this->agent_input == null) {
            $message_thing = new Message($this->thing, $this->thing_report);
            $this->thing_report['info'] = $message_thing->thing_report['info'] ;
        }

        $this->makeWeb();

        $this->thing_report['help'] = 'This reads a web resource.';
		return;
	}

    public function makeWeb()
    {
        $web = "<b>CHS Agent</b>";
        $web .= "<p>";

        foreach($this->predictions as $key=>$prediction) {

            $web .= $prediction['date'] . " ";
            $web .= $prediction['id'] . " ";
            $web .= $prediction['name']. " ";
            $web .= $prediction['value'] . " " . $prediction['units'];
            $web .= "<br>";

        }


        $web .= "<p>";
//        $web .= "current conditions are " . $this->current_conditions . "<br>";
        $web .= "forecast conditions becoming " . $this->forecast_conditions . "<br>";

//        $web .= "data from " . $this->link . "<br>";
        $web .= "source is CHS" . "<br>";



        $web .="<br>";

        $ago = $this->thing->human_time ( time() - strtotime($this->refreshed_at) );

        $web .= "CHS feed last queried " . $ago .  " ago.<br>";

        //$this->sms_message = $sms_message;
        $this->thing_report['web'] = $web;

    }

    public function makeSms()
    {

        if (!isset($this->forecast_conditions)) {$this->forecast_conditions = "No forecast available.";}

        $sms_message = "TIDES | " . null;
        $sms_message .= $this->forecast_conditions;
//        $sms_message .= " | link " . $this->link;
        $sms_message .= " | source CHS";

        $this->sms_message = $sms_message;
        $this->thing_report['sms'] = $sms_message;


    }

    public function makeMessage()
    {
        $message = "Tides are " . null . ".";
        $message .= " " . "Licensed by Canadian Hydrographic Service.";

        $this->message = $message;
        $this->thing_report['message'] = $message;
    }


    public function extractNumber($input = null)
    {
        if ($input == null) {$input = $this->subject;}

        $pieces = explode(" ", strtolower($input));

        // Extract number
        $matches = 0;
        foreach ($pieces as $key=>$piece) {

            if (is_numeric($piece)) {
                $number = $piece;
                $matches += 1;
            }

        }

        if ($matches == 1) {
            if (is_integer($number)) {
                $this->number = intval($number);
            } else {
                $this->number = floatval($number);
            }
        } else {
            $this->number = true;
        }
        return $this->number;
    }

    public function readSubject()
    {
        $this->response = null;
        $this->num_hits = 0;

        //$this->number = extractNumber();
        $keywords = $this->keywords;

        if ($this->agent_input != null) {

            // If agent input has been provided then
            // ignore the subject.
            // Might need to review this.
            $input = strtolower($this->agent_input);

        } else {
            $input = strtolower($this->subject);
        }

        $this->input = $input;

		$haystack = $this->agent_input . " " . $this->from . " " . $this->subject;

//		$this->requested_state = $this->discriminateInput($haystack); // Run the discriminator.

        $prior_uuid = null;

        $pieces = explode(" ", strtolower($input));

		// So this is really the 'sms' section
		// Keyword
        if (count($pieces) == 1) {

            if ($input == 'weather') {

                //echo "readsubject block";
                //$this->read();
                $this->response = "Did nothing.";
                return;

            }

            // Drop through
            // return "Request not understood";

        }

        foreach ($pieces as $key=>$piece) {
            foreach ($keywords as $command) {
                if (strpos(strtolower($piece),$command) !== false) {

                    switch($piece) {
                        case 'verbosity':
                        case 'mode':
                            $number = $this->extractNumber();
                            if (is_numeric($number)) {
                                $this->verbosity = $number;
                                $this->set();
                            }
                            return;

                        default:
                            //$this->read();
                           //echo 'default';

                    }
                }
            }
        }
        return "Message not understood";
		return false;
	}
}