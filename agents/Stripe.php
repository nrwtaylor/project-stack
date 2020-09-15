<?php
namespace Nrwtaylor\StackAgentThing;

ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);

ini_set("allow_url_fopen", 1);

class Stripe extends Agent
{
    // This gets items from the Stripe Finding API.

    public $var = 'hello';

    function is_positive_integer($str)
    {
        return is_numeric($str) && $str > 0 && $str == round($str);
    }

    function init()
    {
        $this->email = $this->thing->container['stack']['email'];
        $this->stack_email = $this->email;

        $this->response = "Init Stipe. ";
        $this->flag = "green";
        $this->stripe_daily_call_count = 0;
        $this->test = "Development code"; // Always

        $this->node_list = ["stripe" => ["stripe"]];
        $this->keywords = ['stripe', 'catalog', 'catalogue'];

        $this->environment = "production"; // production

        $word = strtolower($this->word) . "_" . $this->environment;
        $this->thing->log(
            $this->agent_prefix . 'using stripe keys for  ' . $word . ".",
            "DEBUG"
        );

        if (!isset($this->thing->container['api']['stripe'])) {
            $this->response .= "Settings not available. ";
            return true;
        }

        $this->credential_set =
            $this->thing->container['api']['stripe']['credential_set'];

        $word = $this->credential_set;

        $this->application_id = null;

        $this->publishable_key =
            $this->thing->container['api']['stripe'][$word]['publishable_key'];
        //        $this->devID = $this->thing->container['api']['stripe'][$word]['secret_key'];

        $this->desired_state =
            $this->thing->container['api']['stripe']['state'];

        $this->run_time_max = 360; // 5 hours

        $this->thing_report['help'] = 'This reads the Stripe catalog.';
    }

    public function errorStripe()
    {
        $this->sms_message = 'STRIPE | There is a problem with the Stripe API.';
        $this->message =
            $this->word . ' turned off the Stripe API. ' . $this->response;

        $message =
            'The stack saw errors back from the Stripe API. The Stripe API is currently ' .
            strtoupper($this->state) .
            ".";

        $thing = new Thing(null);

        $to = $this->stack_email;
        $thing->Create(
            $to,
            "human",
            's/ stripe error message to ' . $this->from
        );
        $thing->flagGreen();

        $thing_report['thing'] = $thing;
        $thing_report['message'] = $message;
        $thing_report['sms'] = $message;
        $thing_report['email'] = $message;

        $message_thing = new Message($thing, $thing_report);
        $this->thing_report['info'] = $message_thing->thing_report['info'];

        $this->response .= $this->thing_report['info'] . " ";

        return $this->message;
    }

    function run()
    {
        // Make sure the Snippet code is being run.
        $this->makeSnippet();

        // Do something.
    }

    // devstack

    public function makeWeb()
    {
        // https://stripe.com/docs/checkout/integration-builder

        $head = '    <script src="https://polyfill.io/v3/polyfill.min.js?version=3.52.1&features=fetch"></script>

    <script src="https://js.stripe.com/v3/"></script>';

        $endpoint = '/thing/' . $this->uuid . '/stripe';
        $product_image = "";

        $script =
            '<script type="text/javascript">

    // Create an instance of the Stripe object with your publishable API key

    var stripe = Stripe("' .
            $this->publishable_key .
            '");

    var checkoutButton = document.getElementById("checkout-button");

    checkoutButton.addEventListener("click", function () {

      fetch("' .
            $endpoint .
            '", {

        method: "POST",

      })

        .then(function (response) {

          return response.json();

        })

        .then(function (session) {

          return stripe.redirectToCheckout({ sessionId: session.id });

        })

        .then(function (result) {

          // If redirectToCheckout fails due to a browser or network

          // error, you should display the localized error message to your

          // customer using error.message.

          if (result.error) {

            alert(result.error.message);

          }

        })

        .catch(function (error) {

          console.error("Error:", error);

        });

    });

  </script>';

        $product =
            '      <div class="product">

        <img

          src="' .
            $this->web_prefix .
            $product_image .
            '"

          alt="The product image"

        />

        <div class="description">

          <h3>Stubborn Attachments</h3>

          <h5>$20.00</h5>

        </div>

      </div>

      <button id="checkout-button">Checkout</button>';

        $web = $product . $head . $script;

        $this->thing_report['web'] = $web;
    }

    function set()
    {
        if (!isset($this->state) or $this->state == false) {
            $this->state = "off";
            $this->state = "on";
        }

        $this->variables_agent->setVariable("state", $this->state);

        $this->variables_agent->setVariable("counter", $this->counter);
        $this->variables_agent->setVariable(
            "daily_call_count",
            $this->stripe_daily_call_count
        );
        $this->variables_agent->setVariable(
            "refreshed_at",
            $this->current_time
        );

        //     if ($this->last_state == false) {
        //            $this->readSubject();

        $this->runtime = $this->thing->elapsed_runtime() - $this->start_time;

        $this->thing->json->setField("variables");

        $this->thing->json->writeVariable(
            ["stripe", "runtime"],
            $this->runtime
        );

        $this->thing->json->writeVariable(["stripe", "state"], $this->state);
        $this->thing->json->writeVariable(
            ["stripe", "refreshed_at"],
            $this->current_time
        );

        $this->thing->log($this->agent_prefix . ' completed read.', "OPTIMIZE");
        //    }
    }

    function setStripe($response = null)
    {
        if ($response == null) {
            return true;
        }

        $this->thing->log('called setStripe()');

        $this->thing->db->setFrom($this->from);

        $this->thing->json->setField("message0");
        $this->thing->json->writeVariable(["stripe"], $response);
    }

    function getStripe()
    {
        $this->success_url = $this->web_prefix;
        $this->cancel_url = $this->cancel_prefix;

        $this->stripe = new \Stripe\StripeClient($this->publishable_key);

        $checkout_session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],

            'line_items' => [
                [
                    'price_data' => [
                        'currency' => 'usd',

                        'unit_amount' => 2000,

                        'product_data' => [
                            'name' => 'Test Product',

                            'images' => ["https://i.imgur.com/EHyR2nP.png"],
                        ],
                    ],

                    'quantity' => 1,
                ],
            ],

            'mode' => 'payment',

            'success_url' => $this->success_url,

            'cancel_url' => $this->cancel_url,
        ]);

        echo json_encode(['id' => $checkout_session->id]);

        $this->thing->log('called getStripe()');

        $this->thing->db->setFrom($this->from);

        $this->thing->json->setField("message0");
        $response = $this->thing->json->readVariable(["stripe"]);

        //                $this->variablesGet();

        return $response;
    }

    public function getLink($variable = null)
    {
        $this->link = "www.stripe.com";
    }

    function get()
    {
        //$from = $this->from;
        // Because this is a per key allowance.
        $from = "stack";

        // $this->from is set by the calling agent.
        // See thing-wordpress.php / thing-keybase / etc
        $this->variables_agent = new Variables(
            $this->thing,
            "variables " . "stripe" . " " . $from
        );

        $this->last_state = $this->variables_agent->getVariable("state");

        // Count calls to Stripe API. Note call limits.
        $this->counter = $this->variables_agent->getVariable("counter");

        $this->stripe_daily_call_count = $this->variables_agent->getVariable(
            "daily_call_count"
        );

        $this->refreshed_at = $this->variables_agent->getVariable(
            "refreshed_at"
        );

        $this->thing->log(
            $this->agent_prefix . 'loaded ' . $this->counter . ".",
            "DEBUG"
        );

        $this->counter = $this->counter + 1;
    }

    function logStripe($text, $type = "ERROR")
    {
        if ($text == null) {
            $text = "MErp";
        }

        //var_dump($text);
        //var_dump($text['errorMessage']['error']['message']);

        $log_text = "Error message not found.";
        if (isset($text['errorMessage']['error']['message'])) {
            $log_text = $text['errorMessage']['error']['message'];
        }

        $request = "No request. ";
        if (isset($this->request)) {
            $request = $this->request;
        }

        $calling_function = debug_backtrace()[1]['function'];

        $thing = new Thing(null);
        $thing->Create(
            "meep",
            "stripe",
            "g/ stripe " .
                $type .
                " " .
                $calling_function .
                " - " .
                $request .
                " - " .
                $log_text
        );

        //$this->state = $this->last_state;

        $this->thing->db->setFrom($this->from);

        $this->thing->json->setField("message1");
        $this->thing->json->writeVariable(["stripe"], $text);

        $this->flag = "red";
        $this->response .= "Logging " . $request . " " . $log_text . ". ";

        if ($type == "WARNING") {
            return true;
        }

        // Okay at this point we have one error...
        // Have we had other errors recently?

        $findagent_thing = new Findagent($this->thing, 'stripe');

        $count = count($findagent_thing->thing_report['things']);
        $this->thing->log(
            'found ' .
                count($findagent_thing->thing_report['things']) .
                " place Things."
        );

        if ($findagent_thing->thing_report['things'] == true) {
        }

        if (!$this->is_positive_integer($count)) {
            // Do nothing
        } else {
            $now = strtotime($this->thing->time());

            $count = 0;
            foreach (
                $findagent_thing->thing_report['things']
                as $thing_object
            ) {
                $time_string = $thing_object['created_at'];
                $created_at = strtotime($time_string);

                $age = $now - $created_at;

                if ($age < 60 * 5) {
                    $this->response .= "Saw error  " . $age . "s ago. ";
                    $count += 1;
                }
            }
        }

        if ($count > 2) {
            $this->thing->log("Turned Stripe off.");
            $this->response .= "Turned Stripe off. ";
            $this->state = "off";

            // Send a message. Handle the error.
            $this->errorStripe();
        }

        // Log to the created error Thing.
        $thing->json->writeVariable(["stripe", "state"], $this->state);
        $thing->json->writeVariable(
            ["stripe", "refreshed_at"],
            $this->current_time
        );
    }

    function doApi($text = null)
    {
        // Each of these calls has a cost.
        // If we do all three we get the widest net.

        // Collates all the items to $this->items

        $this->stripeApi($text); // no return blue tablecloth with giraffes

        // Could also do.

        //        $this->wideApi($text); // Lots of returns
        //        $this->ngramApi($text);

        $this->thing->log("search for " . $text . ".");
    }

    function stripeApi($text = null)
    {
        if ($this->state == "off") {
            return true;
        }
        $keywords = $text;
        $this->thing->log("did a Finding API search for " . $keywords . ".");
    }

    public function makeSnippet()
    {
        if (isset($this->thing_report['snippet'])) {
            return;
        }

        $web = "Stripe Dev";

        $snippet_prefix = '<span class = "' . $this->agent_name . '">';
        $snippet_postfix = '</span>';
        //$web .= $web_items;
        $web = $snippet_prefix . $web . $snippet_postfix;
        $this->thing_report['snippet'] = $web;
        $this->thing->log("made snippet.");
    }

    public function makeTXT()
    {
        if (isset($this->thing_report['web'])) {
            return;
        }

        $txt = "STRIPE\n";
        $txt .= "Stripe items\n";

        if (!isset($this->items) or count($this->items) == 0) {
            return;
        }

        $txt_items = "";
        foreach ($this->items as $id => $item) {
            $parsed_item = $this->parseItem($item);
            $txt_items .=
                "\n" . $parsed_item['title'] . " " . $parsed_item['price'];
        }

        $txt .= $txt_items;
        $this->thing_report['txt'] = $txt;
        $this->txt = $txt;
        $this->thing->log("made text.");
    }

    public function makeSMS()
    {
        $sms = "STRIPE";
        $sms .= " | " . $this->state . "";
        if (isset($this->search_words) and $this->search_words != "") {
            $sms .= " " . strtoupper($this->search_words);
        }

        $items_count = 0;
        if (isset($this->items_count)) {
            $items_count = $this->items_count;
        }

        switch ($items_count) {
            case 0:
                $sms .= " | No items found.";
                break;
            case 1:
                $item = $this->items[0];
                $parsed_item = $this->parseItem($item);
                $sms .=
                    "" . $parsed_item['title'] . " " . $parsed_item['price'];
                break;
            default:
                foreach ($this->items as $item) {
                    $parsed_item = $this->parseItem($item);
                    $sms .=
                        " / " .
                        $parsed_item['title'] .
                        " " .
                        $parsed_item['price'];
                }
        }

        $sms .= " | " . $this->response;
        $sms .= " daily call count " . $this->stripe_daily_call_count;
        $this->sms_message = $sms;
        $this->thing_report['sms'] = $sms;
    }

    public function makeMessage()
    {
        $message = "Stripe";

        $this->message = $message;
        $this->thing_report['message'] = $message;
    }

    public function readSubject()
    {
        $this->thing->log('Stripe read input, "' . $this->input . '".');
        //$this->response .= null;

        if (strtolower($this->input) == "stripe on") {
            $this->state = "on";
            return;
        }
        if (strtolower($this->input) == "stripe off") {
            $this->state = "off";
            return;
        }

        $this->state = $this->last_state;

        if ($this->last_state == "off") {
            $this->response .= "stripe is in an OFF condition. ";
            return;
        }

        //$this->state = $this->last_state;

        if (strtolower($this->input) == "stripe") {
            $this->response .= "Checked Stripe state. ";
            return;
        }

        $keywords = $this->keywords;

        $input = $this->input;

        $pieces = explode(" ", strtolower($input));

        // So this is really the 'sms' section
        // Keyword

        if ($this->agent_input == "stripe") {
            $this->response .= "Set up a connector to the Stripe API(s). ";
            return;
        }

        if (count($pieces) == 1) {
            if ($input == 'stripe') {
                $this->response .= "Did not ask Stripe about nothing. ";
                return;
            }
        }

        // Don't pull anything. Just set up the connector.
        //return;

        $whatIWant = $input;
        if (($pos = strpos(strtolower($input), "stripe is")) !== false) {
            $whatIWant = substr(strtolower($input), $pos + strlen("stripe is"));
        } elseif (($pos = strpos(strtolower($input), "stripe")) !== false) {
            $whatIWant = substr(strtolower($input), $pos + strlen("stripe"));
        }

        $filtered_input = ltrim(strtolower($whatIWant), " ");

        if ($filtered_input != "") {
            $this->search_words = $filtered_input;
            $this->doApi($this->search_words);

            $this->response .=
                "Asked Stripe about the word " . $this->search_words . ". ";
            $this->thing->log("asked about " . $this->search_words . ".");

            return false;
        }

        $this->thing->log("did not understand subject.");

        $this->response .= "Message not understood. ";
        return true;
    }
}
