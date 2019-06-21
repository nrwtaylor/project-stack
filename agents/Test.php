<?php
/**
 * Test.php
 *
 * @package default
 */


namespace Nrwtaylor\StackAgentThing;

class Test extends Agent {

    public $var = 'hello';


    /**
     *
     * @param Thing   $thing
     * @param unknown $text  (optional)
     */


    /**
     * function __construct(Thing $thing, $text = null) {
     */
    function init() {
        $this->test= "Development code";
    }


    /**
     *
     */
    function run() {
        $this->doTest("ping");
    }


    /**
     *
     * @param unknown $input_agent_name (optional)
     */
    function doTest($input_agent_name = null) {
        $agent_class_name = ucwords($input_agent_name);
        $this->test_agent_name = $agent_class_name;



        if ($agent_class_name == null) {
            $agent_class_name = strtolower($this->agent_name);
        }
        $flag = "red";
        try {
            $message = "OK";
            $agent_namespace_name = '\\Nrwtaylor\\StackAgentThing\\'.$agent_class_name;

            $this->thing->log( 'trying Agent "' . $agent_class_name . '".', "INFORMATION" );
            $agent_test = new $agent_namespace_name($this->thing, $agent_class_name);
            $flag = "green";
        } catch (\Error $ex) { // Error is the base class for all internal PHP error exceptions.
            $this->thing->log( 'could not load "' . $agent_class_name . '".' , "WARNING" );
            // echo $ex;
            $message = $ex->getMessage();
            // $code = $ex->getCode();
            $file = $ex->getFile();
            $line = $ex->getLine();

            $input = $message . '  ' . $file . ' line:' . $line;
            $this->thing->log($input , "WARNING" );

            // This is an error in the Place, so Bork and move onto the next context.
            // $bork_agent = new Bork($this->thing, $input);
            //continue;
            //                return true;
        }


        $this->test_result = array("agent_name"=>$agent_class_name, "flag"=>$flag, "error"=>$message);


        //        var_dump( $this->isAgent("asdf"));

        //        $this->doTest();

        //        $agent_test = new Agent($this->thing, "agent");
        $agent_test->test();
        if (isset($agent_test->test_result)) {$this->response .= $agent_test->test_result;} else {$this->response .= "No test result found. ";}


    }


    /**
     *
     */
    function resultTest() {
        if ($this->value == $this->expected_value) {echo "Pass  \n";} else {
            echo "Fail  \n";
            echo 'returned $value1'; print_r($this->value); echo '\n';
            echo '$expected_response: '; print_r($this->expected_value); echo '\n';
        }
    }


    /**
     *
     * @param unknown $expected_value (optional)
     */
    function expectedTest($expected_value = null) {
        $this->expected_value = $expected_value;
    }



    // -----------------------

    /**
     *
     * @return unknown
     */
    public function respond() {
        $this->thing->flagGreen();

        $to = $this->thing->from;
        $from = "test";

        $this->makeSMS();
        //        $this->makeChoices();

        $this->thing_report["info"] = "This is saying you are here, when someone needs you.";
        $this->thing_report["help"] = "This is about being very consistent.";

        //$this->thing_report['sms'] = $this->sms_message;
        $this->thing_report['message'] = $this->sms_message;
        $this->thing_report['txt'] = $this->sms_message;

        $message_thing = new Message($this->thing, $this->thing_report);
        $thing_report['info'] = $message_thing->thing_report['info'] ;

        return $this->thing_report;
    }


    /**
     *
     */
    function makeSMS() {
        $this->node_list = array("test"=>array("stay", "go", "game"));
        $this->sms_message = "TEST | " . $this->test_agent_name ." " . $this->response;
        //if ($this->negative_time < 0) {
        //    $this->sms_message .= " " .$this->thing->human_time($this->negative_time/-1) . ".";
        //}
        $this->thing_report['sms'] = $this->sms_message;

    }


    /**
     *
     */
    //    function makeChoices() {
    //        $this->thing->choice->Create('channel', $this->node_list, "test");
    //        $choices = $this->thing->choice->makeLinks('test');
    //        $this->thing_report['choices'] = $choices;
    //    }


    /**
     *
     * @return unknown
     */
    public function readSubject() {
    }


}
