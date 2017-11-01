<?php
namespace StefanGeorgescu\FinalDestination;

class FinalDestination
{

    protected $url, $redirect_url;
    protected $times_redirected = 0;
    protected $config = [
        'max_redirects' => 10,
        'clean_destination' => true,
        'user_agent' => 'Mozilla/5.0 (Windows NT 6.2; WOW64; rv:17.0) Gecko/20100101 Firefox/17.0'
    ];

    public function __construct($url, $config=null)
    {
        $this->url = $this->redirect_url = $url;
        if(isset($config)) {
            $this->config = array_merge($this->config, $config);
        }
    }

    public function get() {
        $location = $this->parseHeaderLocation();
        if($location){
            $this->times_redirected++;
            $this->redirect_url = $location;
            if($this->times_redirected < $this->config['max_redirects']) {
                 return $this->get();
            }
        }
        if(isset($this->config['clean_destination'])) {
            $this->redirect_url = strtok($this->redirect_url, "#");
        }
        return $this->redirect_url;
    }

    private function parseHeaderLocation() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->redirect_url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->config['user_agent']);
        $result = curl_exec($ch);
        curl_close($ch);

        if($result) {
             if(preg_match('#Location: (.*)#', $result, $matches)) {
                return trim($matches[1]);
            }
        }

        return false;

    }

}