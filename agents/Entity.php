<?php
namespace Nrwtaylor\StackAgentThing;

// devstack need to think around designing for a maximum 4000 charactor json thing 
// constraints are good.  Remember arduinos.  So perhaps all agents don't get saved.
// Only the necessary ones.

ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);

ini_set("allow_url_fopen", 1);

class Entity extends Agent 
{

    // This is a headcode.  You will probably want to read up about
    // the locomotive headcodes used by British Rail.

    // A headcode takes the form (or did in the 1960s),
    // of NANN.  Where N is a digit from 0-9, and A is an uppercase character from A-Z.

    // This implementation recognizes lowercase and uppercase characters as the same.
    // 0t80. OT80. HEADODE 0t90.

    // The headcode is used by the Train agent to create the proto-train.
    // A Train must have a Headcode to run.  Rule #1.
    // RUN TRAIN.

    // A headcode must have a route. Route is a text string.  Examples of route are:
    //  Gilmore > Hastings > Place
    //  >> Gilmore >>
    //  > Hastings
    // ADD PLACE. ROUTE IS Gilmore> Hastings > Place.

    // A headcode may have a consist. (Z - indicates train may fill consist.
    // X - indicates train should specify the consist. (devstack: "Input" agent)
    // NnXZ is therefore a valid consist. As is "X" or "Z".
    // A consist must always resolve to a locomotive.  Specified as uppercase letter.
    // The locomotive closest to the first character is the engine.  And gives
    // commands to following locomotives to follow.
    // #devstack
    // The ordered-ness of Consist will come from building out of the orderness of Route.

    // This is the headcode manager.  This person is pretty special.
    // HEADCODE.

    public $var = 'hello';

    function __construct(Thing $thing, $agent_input = null) 
    {

//        var_dump($agent_input);

        $this->start_time = microtime(true);

        //if ($agent_input == null) {$agent_input = "";}
        $this->agent_name = "headcode";
        $this->agent_input = $agent_input;

        $this->thing = $thing;
        $this->start_time = $this->thing->elapsed_runtime();
        $this->thing_report['thing'] = $this->thing->thing;

        $this->agent_prefix = 'Agent "Headcode" ';

        $this->thing->log($this->agent_prefix . 'running on Thing '. $this->thing->nuuid . '.',"INFORMATION");

        $this->resource_path = $GLOBALS['stack_path'] . 'resources/';

        // Get some stuff from the stack which will be helpful.
        $this->web_prefix = $thing->container['stack']['web_prefix'];
        $this->mail_postfix = $thing->container['stack']['mail_postfix'];
        $this->word = $thing->container['stack']['word'];
        $this->email = $thing->container['stack']['email'];


        // I'm not sure quite what the node_list means yet
        // in the context of headcodes.
        // At the moment it seems to be the headcode routing.
        // Which is leading to me to question whether "is"
        // or "Place" is the next Agent to code up.  I think
        // it will be "Is" because you have to define what 
        // a "Place [is]".

 //       $this->node_list = array("start"=>array("stop 1"=>array("stop 2","stop 1"),"stop 3"),"stop 3");
 //       $this->thing->choice->load('headcode');

        $this->keywords = array('next', 'accept', 'clear', 'drop','add','new');

//        $this->headcode = new Variables($this->thing, "variables headcode " . $this->from);

        // So around this point I'd be expecting to define the variables.
        // But I can do that in each agent.  Though there will be some
        // common variables?

        // And Headcode is a context.

        // So here is building block of putting a headcode in each Thing.
        // And a little bit of work on a common variable framework.

        // Factor in the following code.

        // 'headcode' => array('default run_time'=>'105',
        //                        'negative_time'=>'yes'),

        //$this->default_run_time = $this->thing->container['api']['headcode']['default run_time'];
        $this->default_id = "ad20";
        if (isset($this->thing->container['api']['entity']['id'])) {
            $this->default_id = $this->thing->container['api']['entity']['id'];
        }
        // But for now use this below.

        // You will probably see these a lot.
        // Unless you learn headcodes after typing SYNTAX.
//        if (!isset($this->default_id)) {
//            $this->default_id = "ae20";
//        }
        // devstack
        //$this->head_code = "0Z" . str_pad($this->index + 11,2, '0', STR_PAD_LEFT);

        $this->default_alias = "Thing";
        $this->current_time = $this->thing->time();

        // Loads in headcode variables.
        // This will attempt to find the latest head_code
//        $this->get(); // Updates $this->elapsed_time as well as pulling in the current headcode

        // Now at this point a  "$this->headcode_thing" will be loaded.
        // Which will be re-factored eventaully as $this->variables_thing.

        // This looks like a reminder below that the json time generator might be creating a token.

		// So created a token_generated_time field.
        //        $this->thing->json->setField("variables");
        //        $this->thing->json->writeVariable( array("stopwatch", "request_at"), $this->thing->json->time() );

		$this->test= "Development code"; // Always iterative.

        $this->entity_agent = $this->agent_input;

        // Non-nominal
        $this->uuid = $thing->uuid;
        $this->to = $thing->to;
        // Potentially nominal
        $this->subject = $thing->subject;
        // Treat as nominal
        $this->from = $thing->from;

        // Agent variables
        $this->sqlresponse = null; // True - error. (Null or False) - no response. Text - response

        $this->link = $this->web_prefix . 'thing/' . $this->uuid . '/headcode';

//        $this->thing->log('<pre> Agent "Headcode" running on Thing '. $this->thing->nuuid . '.</pre>');
//        $this->thing->log('<pre> Agent "Headcode" received this Thing "'.  $this->subject . '".</pre>');

//$split_time = $this->thing->elapsed_runtime();
//        $this->headcode = new Variables($this->thing, "variables headcode " . $this->from);
//        $this->head_code = $this->headcode->getVariable('head_code', null);

//$this->get();

//$this->thing->log( $this->agent_prefix .' set up variables in ' . number_format($this->thing->elapsed_runtime() - $split_time) . 'ms.' );

        $this->state = null; // to avoid error messages

        // Read the subject to determine intent.
		$this->readSubject();

        // Generate a response based on that intent.
        // I think properly capitalized.
        //$this->set();
        if ($this->agent_input == null) {
		    $this->respond();
        }
        $this->set();

        $this->thing->log( $this->agent_prefix .' ran for ' . number_format($this->thing->elapsed_runtime() - $this->start_time) . 'ms.' );
        $this->thing_report['log'] = $this->thing->log;

        if (!isset($this->response)) {$this->response = "No response found.";}

        //var_dump($this->response);
        $this->thing_report['response'] = $this->response;

		return;
    }

    function set()
    {
        // Apparently not needful of a variable.
        // It is a context. The managing Agents define it.
        // $this->head_code = "0Z15";
        // $entity = new Variables($this->thing, "variables entity " . $this->from);

        // Added test 26 July 2018
        $this->refreshed_at = $this->current_time;

        // Write the entity with the Variables agent.
        // No time needed(?).  Variables handles that.
        // entity_id suggests that this is the identifier of the Entity.
        // To distinguish from entity.   
        $this->entity_id->setVariable("id", $this->id);

        // Don't use an index with entity.
        // But allow Headcode to access the current index. 
        // But won't need this line.  Keep it to just head_code.
        // No Name either.  Trains have names.
        //   $this->headcode->setVariable("index", $this->index);

        $this->entity_id->setVariable("refreshed_at", $this->current_time);

        $this->thing->json->writeVariable( array("entity", "id"), $this->id );
        $this->thing->json->writeVariable( array("entity", "refreshed_at"), $this->current_time );

    }

    function nextEntitycode()
    {
        // #devstack

        $this->thing->log("next entitycode");
        // Pull up the current headcode
        $this->get();

        // Find the end time of the headcode
        // which is $this->end_at

        // One minute into next headcode
        $quantity = 1;
        $next_time = $this->thing->json->time(strtotime($this->end_at . " " . $quantity . " minutes"));

        $this->get($next_time);

        // So this should create a headcode in the next quantity unit.

        return $this->available;


    }

    function getVariable($variable_name = null, $variable = null)
    {
        // devstack remove?

        // This function does a minor kind of magic
        // to resolve between $variable, $this->variable,
        // and $this->default_variable.

        if ($variable != null) {
            // Local variable found.
            // Local variable takes precedence.
            return $variable;
        }

        if (isset($this->$variable_name)) {
            // Class variable found.
            // Class variable follows in precedence.
            return $this->$variable_name;
        }

        // Neither a local or class variable was found.
        // So see if the default variable is set.
        if (isset( $this->{"default_" . $variable_name} )) {

            // Default variable was found.
            // Default variable follows in precedence.
            return $this->{"default_" . $variable_name};
        }

        // Return false ie (false/null) when variable
        // setting is found.
        return false;
    }

    function getRoute()
    {
            //$this->route = $this->thing->json->readVariable( array("headcode", "route") );
//            $this->route = "na";

        //$route_agent = new Route($this->thing, $this->head_code);
        //$this->route = $route_agent->route;
        $this->route = "Place";
    }

    function getRunat()
    {
        if (isset($run_at)) {
            $this->run_at = $run_at;
        } else {
            $this->run_at = "X";
        }
    }

    function getQuantity ()
    {
        // $this->quantity = $this->thing->json->readVariable( array("headcode", "quantity"))  ;
        $this->quantity = "X";
    }


    function getEntities()
    {
        $this->entitycode_list = array();
        // See if a entitycode record exists.
        $findagent_thing = new FindAgent($this->thing, 'entity');

        $this->thing->log('Agent "Entity" found ' . count($findagent_thing->thing_report['things']) ." entity Things." );

        foreach (array_reverse($findagent_thing->thing_report['things']) as $thing_object) {
            // While timing is an issue of concern

            $uuid = $thing_object['uuid'];

//            $thing= new Thing($uuid);
//            $variables = $thing->account['stack']->json->array_data;

            $variables_json= $thing_object['variables'];
            $variables = $this->thing->json->jsontoArray($variables_json);

            if (isset($variables['entity'])) {
                $id = $variables['entity']['id'];
                $refreshed_at = $variables['entity']['refreshed_at'];

                $variables['entity'][] = $thing_object['task'];
                $this->entity_list[] = $variables['entity'];



                $entity = array("id"=>$id,
                                    "refreshed_at"=>$refreshed_at,
                                    "flag" =>$variables['flag'],
                                    "consist" =>$variables['consist'],
                                    "route" =>$variables['route'],
                                    "runtime" =>$variables['runtime'],
                                    "quantity" =>$variables['quantity'],
                                    "route" =>$variables['route']
                                    );

                $this->entities[] = $entity;

            }

        }

        return $this->entity_list;
    }

    function get($entity_id = null)
    {
        // This is a request to get the headcode from the Thing
        // and if that doesn't work then from the Stack.

        // 0. light engine with or without break vans.
        // Z. Always has been a special.
        // 10. Because starting at the beginning is probably a mistake. 
        // if you need 0Z00 ... you really need it.

        $agent_name = $this->entity_agent;
    
        $this->entity = new Variables($this->thing, "variables entity_" . $agent_name . " " . $this->id . " " . $this->from);
var_dump($this->entity->agent_command);
        $this->last_refreshed_at = $this->entity->getVariable("refreshed_at");

        // Don't need this as can access headcode variables at $this->headcode
        //$this->head_code = $this->headcode->getVariable("head_code");

//        $this->consist = $this->entity->getVariable("consist");
//        $this->run_at = $this->->getVariable("run_at");
//        $this->quantity = $this->headcode->getVariable("quantity");
//        $this->available = $this->headcode->getVariable("available");

//        $this->getRoute();
//        $this->getConsist();
//        $this->getRunat();
//        $this->getQuantity();
//        $this->getAvailable();

        return;
    }

    function dropEntity()
    {
        // devstack
        $this->thing->log($this->agent_prefix . "was asked to drop an entity.");

        // If it comes back false we will pick that up with an unset headcode thing.

        if (isset($this->entity)) {
            $this->entity->Forget();
            $this->entity = null;
        }

        $this->get();
    }

    function ImageRectangleWithRoundedCorners(&$im, $x1, $y1, $x2, $y2, $radius, $color)
    {
        // devstack move to Image agent.

        // draw rectangle without corners
        imagefilledrectangle($im, $x1+$radius, $y1, $x2-$radius, $y2, $color);
        imagefilledrectangle($im, $x1, $y1+$radius, $x2, $y2-$radius, $color);

        // draw circled corners
        imagefilledellipse($im, $x1+$radius, $y1+$radius, $radius*2, $radius*2, $color);
        imagefilledellipse($im, $x2-$radius, $y1+$radius, $radius*2, $radius*2, $color);
        imagefilledellipse($im, $x1+$radius, $y2-$radius, $radius*2, $radius*2, $color);
        imagefilledellipse($im, $x2-$radius, $y2-$radius, $radius*2, $radius*2, $color);
    }


    function makeWeb()
    {
        $link = $this->web_prefix . 'thing/' . $this->uuid . '/agent';

        $this->node_list = array("entity web"=>array("entity", "entity 0Z99"));

        // Make buttons
        $this->thing->choice->Create($this->agent_name, $this->node_list, "entity web");
        $choices = $this->thing->choice->makeLinks('entity web');

        if (!isset($this->html_image)) {$this->makePNG();}

        $web = '<a href="' . $link . '">'. $this->html_image . "</a>";
        $web .= "<br>";

        $web .= '<b>' . ucwords($this->agent_name) . ' Agent</b><br>';


        $ago = $this->thing->human_time ( time() - strtotime($this->last_refreshed_at) );
        $web .= "Asserted about ". $ago . " ago.";

        $web .= "<br>";

        $web .= $this->makeSMS();

        $this->thing_report['web'] = $web;
    }

    public function makeMessage()
    {
        $message = "Entity is " . strtoupper($this->id) . ".";
        $this->message = $message;
        $this->thing_report['message'] = $message;
    }


    public function makePNG()
    {
        if (!isset($this->image)) {$this->makeImage();}

        $agent = new Png($this->thing, "png"); // long run
        $agent->makePNG($this->image);

        $this->html_image = $agent->html_image;
        $this->image = $agent->image;
        $this->PNG = $agent->PNG;
        $this->PNG_embed = $agent->PNG_embed;

        $this->thing_report['png'] = $agent->image_string;
    }

    function makeEntity($id = null)
    {
        $id = $this->getVariable('id', $id);

        $this->thing->log('Agent "Entity" will make a id for ' . $id . ".");

        $ad_hoc = true;
        if ( ($ad_hoc != false) ) {

            // Ad-hoc headcodes allows creation of headcodes on the fly.
            // 'Z' indicates the associated 'Place' is offering whatever it has.
            // Block is a Place.  Train is a Place (just a moving one).
            $quantity = "Z";

            // Otherwise we needs to make trains to run in the headcode.

            $this->thing->log($this->agent_prefix . "was told the Place is Useable but we might get kicked out.");

            // So we can create this headcode either from the variables provided to the function,
            // or leave them unchanged.

            $this->index = $this->max_index + 1;
            $this->max_index = $this->index;

            $this->current_id = $id;
            $this->id = $id;

            $this->quantity = $quantity; // which is run_time

            if (isset($run_at)) {
               $this->run_at = $run_at;
            } else {
                $this->run_at = "X";
            }

            $this->getEndat();
//            $this->getAvailable();

            // devstack?
            $this->entity_thing = $this->thing;

        }

        // Need to code in the X and <number> conditions for creating new headcodes.

        // Write the variables to the db.
        $this->set();

        //$this->headcode_thing = $this->thing;

        $this->thing->log('Agent "Entity" found id a pointed to it.');

    }

    function entityTime($input = null) {

        if ($input == null) {
            $input_time = $this->current_time;
        } else {
            $input_time = $input;
        }

        if ($input == "x") {
            $entity_time = "x";
            return $entity_time;
        }


        $t = strtotime($input_time);

        $this->hour = date("H",$t);
        $this->minute =  date("i",$t);

        $entity_time = $this->hour . $this->minute;

        if ($input == null) {$this->headcode_time = $entity_time;}

        return $entity_time;



    }

    function getEndat() {

        if (($this->run_at != "x") and ($this->quantity != "x")) {
            $this->end_at = $this->thing->json->time(strtotime($this->run_at . " " . $this->quantity . " minutes"));
        } else {
            $this->end_at = "x";
        }

        return $this->end_at;
    }

    function extractConsists($input) {

        // devstack: probably need a word lookup 
        // or at least some thinking on how to differentiate Entity from NnX
        // as a valid consist.

        if (!isset($this->consists)) {
            $this->consists = array();
        }

        $pattern = "|[A-Za-z]|";

        preg_match_all($pattern, $input, $m);
//        return $m[0];
        $this->consists = $m[0];
        //array_pop($arr);

        return $this->consists;


    }

    function getConsist($input = null) {

        $consists = $this->extractConsists($input);

        if ((is_array($consists)) and (count($consists) == 1) and (strtolower($consists[0]) != 'train')) {

//        if ((count($consists) == 1) and (strtolower($consists[0]) != 'train')) {
            $this->consist = $consists[0];
            $this->thing->log('Agent "Entity" found a consist (' . $this->consist . ') in the text.');
            return $this->consist;
        }

        $this->consist = "X";

        if  ((is_array($consists)) and (count($consists) == 0)){return false;}
        if  ((is_array($consists)) and (count($consists) > 1)){return false;}

        //if (count($consists == 0)) {return false;}
        //if (count($consists > 1)) {return true;}

        return true;

    }



    function extractEntities($input = null)
    {
        $thing = new Nuuid($this->thing, "nuuid");
        $thing->extractNuuids($input);

        $this->ids = $thing->nuuids;

//        if (!isset($this->ids)) {
//            $this->ids = array();
//        }
        //Why not combine them into one character class? /^[0-9+#-]*$/ (for matching) and /([0$
        //$pattern = "|[A-Za-z]{4}|"; echo $input;

//        $pattern = "|\b\d{1}[A-Za-z]{1}\d{2}\b|";
//        $pattern = "|\b\[0-9A-Za-z]{4}\b|";
//        preg_match_all($pattern, $input, $m);
//        $this->ids = $m[0];

        return $this->ids;
    }

    function extractEntity($input)
    {
        $ids = $this->extractEntities($input);
        if (!(is_array($ids))) {return true;}

        if ((is_array($ids)) and (count($ids) == 1)) {
            $this->id = $ids[0];
            $this->thing->log('Agent "Entity" found a id (' . $this->id . ') in the text.');
            return $this->id;
        }

        if  ((is_array($ids)) and (count($ids) == 0)){return false;}
        if  ((is_array($ids)) and (count($ids) > 1)) {return true;}

        return true;
    }


    function read()
    {
        $this->thing->log("read");

//        $this->get();
        return $this->available;
    }



    function addEntity() {
        //$this->makeHeadcode();
        $this->get();
        return;
    }

    public function makeImage()
    {
        $text = strtoupper($this->id);

$image_height = 125;
$image_width = 125;

        // here DB request or some processing
//        $this->result = 1;
//        if (count($this->result) != 2) {return;}

//        $number = $this->result[1]['roll'];

        $image = imagecreatetruecolor($image_width, $image_height);

        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        $red = imagecolorallocate($image, 255, 0, 0);
        $green = imagecolorallocate($image, 0, 255, 0);
        $grey = imagecolorallocate($image, 128, 128, 128);

        imagefilledrectangle($image, 0, 0, $image_width, $image_height, $white);
        $textcolor = imagecolorallocate($image, 0, 0, 0);


        $this->ImageRectangleWithRoundedCorners($image, 0,0, $image_width, $image_height, 12, $black);
        $this->ImageRectangleWithRoundedCorners($image, 6,6, $image_width-6, $image_height-6, 12-6, $white);

        $font = $this->resource_path . 'roll/KeepCalm-Medium.ttf';

        // Add some shadow to the text
        //imagettftext($image, 40, 0, 0, 75, $grey, $font, $number);
        $sizes_allowed = array(72,36,24,18,12,6);

        foreach($sizes_allowed as $size) {
            $angle = 0;
            $bbox = imagettfbbox ($size, $angle, $font, $text); 
            $bbox["left"] = 0- min($bbox[0],$bbox[2],$bbox[4],$bbox[6]); 
            $bbox["top"] = 0- min($bbox[1],$bbox[3],$bbox[5],$bbox[7]); 
            $bbox["width"] = max($bbox[0],$bbox[2],$bbox[4],$bbox[6]) - min($bbox[0],$bbox[2],$bbox[4],$bbox[6]); 
            $bbox["height"] = max($bbox[1],$bbox[3],$bbox[5],$bbox[7]) - min($bbox[1],$bbox[3],$bbox[5],$bbox[7]); 
            extract ($bbox, EXTR_PREFIX_ALL, 'bb'); 

         //check width of the image 
            $width = imagesx($image); 
            $height = imagesy($image);
            if ($bbox['width'] < $image_width - 30) {break;}

        }

        $pad = 0;
        imagettftext($image, $size, $angle, $width/2-$bb_width/2, $height/2+ $bb_height/2, $grey, $font, $text);
        //imagestring($image, 2, $image_width-75, 10, $text, $textcolor);
        imagestring($image, 2, $image_width-45, 10, $this->entity->nuuid, $textcolor);

        $this->image = $image;
    }


    function makeTXT()
    {
        if (!isset($this->entity_list)) {$this->getEntities();}
        //$this->getHeadcodes();

        $txt = 'These are ENTITIES for RAILWAY ' . $this->entity->nuuid . '. ';
        $txt .= "\n";

        $count = "X";
        if (is_array($this->entities)) {
            $count = count($this->entities);
        }

        $txt .= "Last " . $count. ' Entities retrieved.';

        $txt .= "\n";
        $txt .= "\n";

        //$txt .= str_pad("INDEX", 7, ' ', STR_PAD_LEFT);
//        $txt .= " " . str_pad("RUN AT", 20, " ", STR_PAD_RIGHT);

//        $txt .= " " . str_pad("HEAD", 4, " ", STR_PAD_LEFT);
//        $txt.= " " . str_pad("FLAG", 8, " ", STR_PAD_LEFT);

//        $txt .= " " . str_pad("ALIAS", 10, " " , STR_PAD_RIGHT);
        //$txt .= " " . str_pad("DAY", 4, " ", STR_PAD_LEFT);

        //$txt .= " " . str_pad("RUNAT", 6, " ", STR_PAD_LEFT);
//        $txt .= " " . str_pad("RUNTIME", 8, " ", STR_PAD_LEFT);

//        $txt .= " " . str_pad("AVAILABLE", 9, " ", STR_PAD_LEFT);
//        $txt .= " " . str_pad("QUANTITY", 9, " ", STR_PAD_LEFT);
//        $txt .= " " . str_pad("CONSIST",9, " ", STR_PAD_LEFT);
//        $txt .= " " . str_pad("ROUTE", 9, " ", STR_PAD_LEFT);

        $txt .= "\n";
        $txt .= "\n";



        //$txt = "Test \n";
        foreach (array_reverse($this->entities) as $entity) {

//            $txt .= " " . str_pad(strtoupper($headcode['head_code']), 4, "X", STR_PAD_LEFT);
            //$txt .= " " . str_pad($train['alias'], 10, " " , STR_PAD_RIGHT);

            $refreshed_at = "X";
            if (isset($entity['refreshed_at'])) {
                // devstack
                // $agent = new Timestamp($this->thing, $headcode['refreshed_at']);  
                $refreshed_at = strtoupper(date('Y M d D H:i', strtotime($entity['refreshed_at'])));
            }
            $txt .= " " . str_pad($refreshed_at, 20, " ", STR_PAD_LEFT);


            $txt .= " " . str_pad(strtoupper($entity['id']), 4, "X", STR_PAD_LEFT);

            $flag_state = "X";
            if (isset($entity['flag']['state'])) {
                $flag_state = $entity['flag']['state'];

                //$txt .= " " . str_pad($headcode['flag']['state'], 8, " ", STR_PAD_LEFT);
            }
            $txt .= " " . str_pad($flag_state, 8, " ", STR_PAD_LEFT);

//            if (isset($headcode['refreshed_at'])) {
//                $txt .= " " . str_pad($headcode['refreshed_at'], 12, " ", STR_PAD_LEFT);
//            }

           $runtime_minutes = "X";
           if (isset($entity['runtime']['minutes'])) {$runtime_minutes = $entity['runtime']['minutes'];}
           $txt .= " " . str_pad($runtime_minutes, 8, " ", STR_PAD_LEFT);


//            if (isset($headcode['run_at'])) {
//                $txt .= " " . str_pad($headcode['run_at'], 8, " ", STR_PAD_LEFT);
//            }
//            if (isset($headcode['available'])) {
//                $txt .= " " . str_pad($headcode['available'], 9, " ", STR_PAD_LEFT);
//            }
//            if (isset($headcode['quantity'])) {
//                $txt .= " " . str_pad($headcode['quantity'], 9, " ", STR_PAD_LEFT);
//            }
//            if (isset($headcode['consist'])) {
//                $txt .= " " . str_pad($headcode['consist'], 9, " ", STR_PAD_LEFT);
//            }
//            if (isset($headcode['route'])) {
//                $txt .= " " . str_pad($headcode['route'], 9, " ", STR_PAD_LEFT);
//            }
            $txt .= "\n";

        }

        $this->thing_report['txt'] = $txt;
        $this->txt = $txt;

    }

    private function getFlag()
    {
//        $this->flag = new Flag($this->thing, "flag " .$this->head_code);
        $this->flag = new Flag($this->thing, "flag");

        if (!isset($this->flag->state)) { $this->flag->state = "X";}
    }

    private function makeSMS() {

        //$s = "GREEN";
        if (!isset($this->flag->state)) {$this->getFlag();}
        //$s = strtoupper($this->flag->state);
        

        $sms_message = "ENTITY " . strtoupper($this->id) ." | " . $this->flag->state;
        //$sms_message .= " | " . $this->headcodeTime($this->start_at);
        $sms_message .= " | ";

        $sms_message .= $this->route . " [" . $this->consist . "] " . $this->quantity;
 
//        $sms_message .= " | index " . $this->index;
//        $sms_message .= " | available " . $this->available;

        //$sms_message .= " | from " . $this->headcodeTime($this->start_at) . " to " . $this->headcodeTime($this->end_at);
        //$sms_message .= " | now " . $this->headcodeTime();
        $sms_message .= " | nuuid " . strtoupper($this->entity->nuuid);
        $sms_message .= " | ~rtime " . number_format($this->thing->elapsed_runtime())."ms";

        $this->sms_message = $sms_message;
        $this->thing_report['sms'] = $sms_message;

        return $sms_message;
    }

	public function respond() {

		// Thing actions

		$this->thing->flagGreen();

		// Generate email response.

		$to = $this->thing->from;
		$from = "entity";


		//$choices = $this->thing->choice->makeLinks($this->state);
        $choices = false;
		$this->thing_report['choices'] = $choices;

        //$this->makeTXT();


$available = $this->thing->human_time($this->available);

if (!isset($this->index)) {
    $index = "0";
} else {
    $index = $this->index;//
}

        $this->makeSMS();



  

		$test_message = 'Last thing heard: "' . $this->subject . '".  Your next choices are [ ' . $choices['link'] . '].';
		$test_message .= '<br>entity state: ' . $this->state . '<br>';

		$test_message .= '<br>' . $this->sms_message;

//		$test_message .= '<br>Current node: ' . $this->thing->choice->current_node;

        $test_message .= '<br>run_at: ' . $this->run_at;
        $test_message .= '<br>end_at: ' . $this->end_at;


//		$test_message .= '<br>Requested state: ' . $this->requested_state;

			//$this->thing_report['sms'] = $sms_message;
			$this->thing_report['email'] = $this->sms_message;

        $this->makeMessage();
	    //$this->thing_report['message'] = $this->sms_message; // NRWTaylor 4 Oct - slack can't take html in $test_message;

        $this->makePNG();
        $this->makeWeb();

        if (!$this->thing->isData($this->agent_input)) {
                        $message_thing = new Message($this->thing, $this->thing_report);

                        $this->thing_report['info'] = $message_thing->thing_report['info'] ;
        } else {
            $this->thing_report['info'] = 'Agent input was "' . $this->agent_input . '".' ;
        }

        $this->makeTXT();

        $this->thing_report['help'] = 'This is a entity.';



		return;


	}

    function isData($variable) {
        if ( 
            ($variable !== false) and
            ($variable !== true) and
            ($variable != null) ) {
 
            return true;

        } else {
            return false;
        }
    }

    public function readSubject() 
    {

        $this->response = null;
        $this->num_hits = 0;

        $keywords = $this->keywords;

        if ($this->agent_input != null) {
            // If agent input has been provided then
            // ignore the subject.
            // Might need to review this.
            if ($this->agent_input == "extract") {
                $input = strtolower($this->subject);
            } else {
                $input = strtolower($this->agent_input);
            }
        } else {
            $input = strtolower($this->from . " " . $this->subject);
        }


		//$haystack = $this->agent_input . " " . $this->from . " " . $this->subject;

        $prior_uuid = null;

        // Is there a headcode in the provided datagram
        $x = $this->extractEntity($input);

//        $agent_name = "crow";

        $agent_name = $this->entity_agent;


        $this->entity_id = new Variables($this->thing, "variables entity_" . $agent_name . " " . $this->from);

        if (!isset($this->id) or ($this->id == false)) {
            $this->id = $this->entity_id->getVariable('id', null);
            //var_dump($this->head_code);
            if (!isset($this->id) or ($this->id == false)) {
                $this->id = $this->getVariable('id', null);
                //var_dump($this->head_code);

                if (!isset($this->id) or ($this->id == false)) {
                    $this->id = "ae30";
                    //var_dump($this->head_code);
                }
            }
        }


        $this->get();

        if ( ($this->agent_input == "extract") and (strpos(strtolower($this->subject),'roll') !== false )   ) {

//            echo "headcode found was " . $this->head_code ."\n";

            if (strtolower($this->id[1]) == "d") {
                $this->response = true; // Which flags not to use response.  
                //$this->response = "Not a headcode."; 
                return;
            }
        }

        // Bail at this point if only a headcode check is needed.
        if ($this->agent_input == "extract") {$this->response = "Extract";return;}

        $pieces = explode(" ", strtolower($input));

		// So this is really the 'sms' section
		// Keyword
        if (count($pieces) == 1) {

            if ($input == 'entity') {

                $this->read();
                $this->response = "Read entity";
                return;
            }

        }

    foreach ($pieces as $key=>$piece) {
        foreach ($keywords as $command) {
            if (strpos(strtolower($piece),$command) !== false) {

                switch($piece) {


    case 'next':
        $this->thing->log("read subject nextentity");
        $this->nextentity();
        $this->response = "Got next entity";
        break;

   case 'drop':
   //     //$this->thing->log("read subject nextheadcode");
        $this->dropentity();
        $this->response = "Dropped entity";
        break;


   case 'add':
   //     //$this->thing->log("read subject nextheadcode");
        //$this->makeheadcode();
        $this->get();
        $this->response = "Added entity";
        break;


    default:

                                        }

                                }
                        }

                }


// Check whether headcode saw a run_at and/or run_time
// Intent at this point is less clear.  But headcode
// might have extracted information in these variables.

// $uuids, $head_codes, $this->run_at, $this->run_time

    if ($this->isData($this->id)) {
        $this->set();
        $this->response = "Set entity to " . strtoupper($this->id);
        return;
    }

    $this->read();
    $this->response = "Read";



                return "Message not understood";




		return false;

	
	}






	function kill() {
		// No messing about.
		return $this->thing->Forget();
	}

}

/* More on headcodes

http://myweb.tiscali.co.uk/gansg/3-sigs/bellhead.htm
1 Express passenger or mail, breakdown train en route to a job or a snow plough going to work.
2 Ordinary passenger train or breakdown train not en route to a job
3 Express parcels permitted to run at 90 mph or more
4 Freightliner, parcels or express freight permitted to run at over 70 mph
5 Empty coaching stock
6 Fully fitted block working, express freight, parcels or milk train with max speed 60 mph
7 Express freight, partially fitted with max speed of 45 mph
8 Freight partially fitted max speed 45 mph
9 Unfitted freight (requires authorisation) engineers train which might be required to stop in section.
0 Light engine(s) with or without brake vans

E     Train going to       Eastern Region
M         "     "     "         London Midland Region
N         "     "     "         North Eastern Region (disused after 1967)
O         "     "     "         Southern Region
S          "     "     "         Scottish Region
V         "     "     "         Western Region

*/
?>