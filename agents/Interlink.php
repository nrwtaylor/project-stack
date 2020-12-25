<?php
namespace Nrwtaylor\StackAgentThing;

class Interlink extends Agent
{
    public $var = 'hello';

    function init()
    {
    }

    public function initInterlink()
    {
        //if (!isset($this->slug_agent)) {
        $this->slug_agent = new Slug($this->thing, "slug");
        //}
        //if (!isset($this->ngram_agent)) {
        $this->ngram_agent = new Ngram($this->thing, "ngram");
        //}
    }

    function run()
    {
        $this->runInterlink();
    }

    public function test()
    {
    }

    public function slugsInterlink($text = null)
    {
        if ($text == null) {
            return false;
        }
        if (!isset($this->slug_agent)) {
            $this->slug_agent = new Slug($this->thing, "slug");
        }
        if (!isset($this->ngram_agent)) {
            $this->ngram_agent = new Ngram($this->thing, "ngram");
        }

        $slugs = [];
        //$ngrams = $this->ngram_agent->getNgrams($text, 3);
        //$ngrams = $this->getNgrams($text, 3);

        $arr = explode('\%20', trim(strtolower($text)));

        $agents = [];
        $onegrams = $this->ngram_agent->getNgrams($text, $n = 1);
        $bigrams = $this->ngram_agent->getNgrams($text, $n = 2);
        $trigrams = $this->ngram_agent->getNgrams($text, $n = 3);

        $arr = array_merge($arr, $onegrams);
        $arr = array_merge($arr, $bigrams);
        $arr = array_merge($arr, $trigrams);

        usort($arr, function ($a, $b) {
            return strlen($b) <=> strlen($a);
        });

        $ngrams = $arr;

        foreach ($ngrams as $i => $ngram) {
            $slug = $this->slug_agent->getSlug($ngram);
            if ($slug === true) {
                continue;
            }
            $slugs[] = $slug;
        }
        return $slugs;
    }

    public function runInterlink()
    {
        $filename =
            "/home/nick/codebase/stackr-resources/calendar/calendar.txt";
        $p = new Contents($this->thing, $filename);

        $uuid_agent = new Uuid($this->thing, "uuid");
        $ngram_agent = new Ngram($this->thing, "ngram");
        $slug_agent = new Slug($this->thing, "slug");
        $paragraph_agent = new Paragraph($this->thing, $p->contents);

        $paragraphs = $paragraph_agent->paragraphs;
        $interlinks = [];
        foreach ($paragraphs as $i => $paragraph) {
            // Ignore empty paragraphs.
            $paragraph = trim($paragraph);
            if ($paragraph == "") {
                continue;
            }
            $uuid = $this->thing->getUUid();

            $paragraph_slugs = $this->slugsInterlink($paragraph);
            //$ngrams = $ngram_agent->getNgrams($paragraph, 3);
            $interlinks[$uuid] = [
                'text' => $paragraph,
                'slug_list' => $paragraph_slugs,
            ];
        }

        // Make a list of uuids for each slug.
        // Make an array of slugs
        $slugs = [];
        foreach ($interlinks as $uuid => $interlink) {
            if ($interlink['slug_list'] == []) {
                continue;
            }

            foreach ($interlink['slug_list'] as $i => $slug) {
                if ($slug == "") {
                    continue;
                }
                //if (!isset($slug)) {
                //    $slugs[$slug];
                //}

                if (!isset($slugs[$slug][$uuid])) {
                    $slugs[$slug][$uuid] = 0;
                }
                $slugs[$slug][$uuid] += 1;
            }
        }

        foreach ($interlinks as $uuid => $interlink) {
            foreach ($interlink['slug_list'] as $i => $slug) {
                if (!isset($slugs[$slug])) {continue;}  

                $count = count($slugs[$slug]);

                if ($count <= 1) {
                    continue;
                }

                $interlinks[$uuid]['slugs'][$slug] = $slugs[$slug];
            }
        }

        $this->txtInterlinks($interlinks);
// For development.
//        $this->echoInterlinks($interlinks);
        $this->saveInterlinks($interlinks);

        if ($this->agent_input == null) {
            $response = "N/A.";

            $this->interlink_message = $response; // mewsage?
        } else {
            $this->interlink_message = $this->agent_input;
        }
    }

    public function echoInterlinks($interlinks) {
        if (!isset($this->txt)) {$this->txtInterlinks($interlinks);}
        echo $this->txt;
    }

    public function txtInterlinks($interlinks)
    {
        $txt = "";
        foreach ($interlinks as $uuid => $interlink) {
            $txt .= $interlink['text'] . "\n";
            //$count = count($interlink['slugs']);
            if (isset($interlink['slugs'])) {
                foreach ($interlink['slugs'] as $slug => $uuids) {
                    if (!is_array($uuids)) {
                        continue;
                    }
                    $count = count($uuids);
                    $txt .= $slug . " " . $count . "\n";
                }
            }
            $txt .= "\n";
        }
        $this->txt = $txt;
    }

    public function makeTXT() {

        $this->thing_report['txt'] = $this->txt;

    }

    public function saveInterlinks($interlinks)
    {
        $path = null;
        if (isset($this->thing->container['stack']['path'])) {
            $path = $this->thing->container['stack']['path'];
        }

        // TODO - Save a readable require file.
        file_put_contents($path . 'test.php', print_r($interlinks, true));
    }

    public function respondResponse()
    {
        $this->thing->flagGreen();

        $this->thing_report["info"] =
            "This reads interlinks between blocks of text (paragraphs).";
        $this->thing_report["help"] = "This is about links between things.";

        $this->thing_report['message'] = $this->sms_message;
        $this->thing_report['txt'] = $this->sms_message;

        $message_thing = new Message($this->thing, $this->thing_report);
        $thing_report['info'] = $message_thing->thing_report['info'];
    }

    function makeSMS()
    {
        $this->node_list = ["interlink" => ["interlink"]];
        $sms = "INTERLINK | " . $this->interlink_message;
        $this->sms_message = "" . $sms;
        $this->thing_report['sms'] = $sms;
    }

    function makeChoices()
    {
        $choices = false;
        $this->thing_report['choices'] = $choices;
    }

    public function readSubject()
    {
    }
}
