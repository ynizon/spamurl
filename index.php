<?php
require_once 'vendor/autoload.php';

use voku\helper\HtmlDomParser;

//Insert Spammers Urls here
$urls = [
    "http://antiarnaque.test/test_post.php",
    "http://antiarnaque.test/test_get.php",
    //"https://istruckerids.com/c/p8LThfp4wLjmza7dIzeM6CiWS1?s1=102e9e53fcde121e855c0dcd0765ac"
    ];

foreach ($urls as $url)
{
    $spamUrl = new SpamUrl($url);
    $spamUrl->go();
}

class SpamUrl
{
    protected $ip = '93.4.72.171';//Protect your own IP
    protected $locale = 'fr_FR';
    protected $url = '';
    protected $faker;
    public function __construct(string $url)
    {
        $this->faker = Faker\Factory::create($this->locale);
        $this->url = $url;
    }

    public function go()
    {
        $html = $this->getHtmlFromUrl($this->url);
        $html = HtmlDomParser::str_get_html($html);
        $forms = $html->find('form');
        if (count($forms) == 0){
            foreach($html->find('iframe') as $iframe) {
                $url = $iframe->src;
                $newSpamUrl = new SpamUrl($url);
                $newSpamUrl->go();
            }
        } else {
            foreach ($forms as $form)
            {
                $method = $form->hasAttribute("method") ? strtolower($form->method) : "get";
                $infosUrl = parse_url($this->url);
                $action = $form->hasAttribute("action") ? $form->action : "";
                if ($action == "") {
                    //Sometimes url are defined in JS
                    $keywordUrl = "submitUrl";
                    $posStart = stripos($html, $keywordUrl);
                    if ($posStart !== false)
                    {
                        $posEnd = stripos($html, ";", $posStart);
                        $action = substr($html, $posStart+strlen($keywordUrl), $posEnd - $posStart - strlen($keywordUrl));
                        $action = str_replace("=","", $action);
                        $action = trim(str_replace('"',"", $action));
                    }

                }

                $elements = $this->replaceElements($form);
                $postForm = $this->postForm($method,$action,$elements, $infosUrl);
                file_put_contents("result.html",$postForm);
            }
        }
    }

    protected function getHtmlFromUrl($url)
    {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, $this->faker->userAgent());
        return curl_exec($curl);
    }

    protected function postForm(string $method, string $url, array $elements, array $infosUrl)
    {
        if ($method == "get")
        {
            foreach ($elements as $element=>$value)
            {
                if (stripos($url, "?") === false ){
                    $url .= str_replace("\n","","?".urlencode($element)."=".urlencode($value));
                } else {
                    $url .= str_replace("\n","","&".urlencode($element)."=".urlencode($value));
                }
            }
        }

        //Fix protocol for url
        if (stripos($url,"http") === false){
            if (substr($url,0,1) != "/"){
                $url = "/".$url;
            }
            $url = $infosUrl['scheme']."://".$infosUrl['host'].$url;
        }

        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($curl, CURLOPT_USERAGENT, $this->faker->userAgent());
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        if ($method == "post")
        {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $elements);
        }

        $info = curl_exec($curl);
        if (curl_errno($curl)) {
            $error_msg = curl_error($curl);
            echo $error_msg;
        }

        return $info;
    }

    protected function getIPAddress()
    {
        //whether ip is from the share internet
        $ip = '';
        if(!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        //whether ip is from the proxy
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        //whether ip is from the remote address
        else{
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        if ($ip == '127.0.0.1'){
            $ip = $this->ip;
        }

        return $ip;
    }

    protected function replaceElements($form){
        $elements = [];
        foreach($form->find('input') as $input) {
            if ($input->hasAttribute("checkbox"))
            {
                $elements[$input->name] = $input->value;
            } else
            {
                $elements[$input->name] = $input->value;
            }
        }

        foreach($form->find('select') as $select) {
            $optionValues = [];
            $options = $select->find("option");
            foreach ($options as $option)
            {
                if ($option->value != ""){
                    $optionValues[] = $option->value;
                }
            }

            if (count($optionValues) > 0)
            {
                $randKey = array_rand($optionValues, 1);
                $elements[$select->name] = $optionValues[$randKey];
            }
        }

        $ip = $this->getIPAddress();
        $fakeIp = $this->faker->localIpv4();
        $mapping = [
            "name" => "lastname",
            "nom" => "lastname",
            "lastname" => "lastname",
            "firstname" => "firstName",
            "prenom" => "firstName",
            "phone" => "phoneNumber",
            "post" => "postcode",
            "address" => "address",
            "adresse" => "address",
            "email" => "email",

        ];
        foreach ($elements as $element => $value)
        {
            foreach ($mapping as $field => $function)
            {
                if (stripos($element, $field) !== false)
                {
                    $elements[$element] = $this->faker->$function();
                }
                if (stripos($value, $ip) !== false)
                {
                    $elements[$element] = str_replace($ip,$fakeIp,$value);
                }
                if (stripos($element,"date") !== false){
                    //Sometimes we need BirthDate field
                    // outputs something like 17/09/2001
                    $elements[$element] = $this->faker->dateTimeBetween('1990-01-01', '2012-12-31')
                        ->format('Y-m-d');
                }
            }

        }

        return $elements;
    }
}

?>