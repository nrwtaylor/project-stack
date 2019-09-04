<?php
/**
 * Time.php
 *
 * @package default
 */


namespace Nrwtaylor\StackAgentThing;

ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);

ini_set("allow_url_fopen", 1);


class Time extends Agent {

    public $var = 'hello';


    /**
     *
     * @param Thing   $thing
     * @param unknown $text  (optional)
     */
    function init() {
        $this->agent_name = "time";
        $this->test= "Development code";

        $this->thing_report["info"] = "This connects to an authorative time server.";
        $this->thing_report["help"] = "Get the time. Text CLOCKTIME.";

        $this->time_zone = 'America/Vancouver';
    }


    /**
     *
     * @return unknown
     */
/*
    public function respond() {
        $this->thing->flagGreen();

        $to = $this->thing->from;
        $from = "time";

        $this->makeSMS();
        $this->makeChoices();

        //$this->thing_report["info"] = "This is a ntp in a park.";
        //$this->thing_report["help"] = "This is finding picnics. And getting your friends to join you. Text RANGER.";

        $this->thing_report['message'] = $this->sms_message;
        $this->thing_report['txt'] = $this->sms_message;

        $message_thing = new Message($this->thing, $this->thing_report);
        $thing_report['info'] = $message_thing->thing_report['info'] ;

        return $this->thing_report;
    }
*/

    /**
     *
     */
    function makeSMS() {
        $this->node_list = array("time"=>array("time"));
        $m = strtoupper($this->agent_name) . " | " . $this->response;
        $this->sms_message = $m;
        $this->thing_report['sms'] = $m;
    }


    /**
     *
     */
    function makeChoices() {
        $choices = false;
        $this->thing_report['choices'] = $choices;
    }


    /**
     *
     * @param unknown $text (optional)
     * @return unknown
     */
    function doTime($text = null) {
        $datum = null;

        $timevalue = $text;
        if (($this->agent_input == "time") and  ($text == null)) {$timevalue = $this->current_time;}
        if ($text == "time") {$timevalue = $this->current_time;}

        if ($timevalue == null) {$timevalue = $this->current_time;}

var_dump($timevalue);
        $m =  "Unfortunately, the time server was not available. ";
        if (true) {

            $datum = new \DateTime($timevalue, new \DateTimeZone("UTC"));
            $datum->setTimezone(new \DateTimeZone($this->time_zone));

            $m = "Time check from stack server ". $this->web_prefix. ". ";
            $m .= "In the timezone " . $this->time_zone . ", it is " . $datum->format('l') . " " . $datum->format('d/m/Y, H:i:s') .". ";

        }

        $this->response = $m;
        $this->time_message = $this->response;

        $this->datum = $datum;
        return $datum;

    }


    /**
     *
     * @return unknown
     */
    public function readSubject() {
        $this->doTime();
        return false;
    }

}
