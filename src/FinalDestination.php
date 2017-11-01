<?php
namespace StefanGeorgescu\FinalDestination;

use DOMDocument;

class FinalDestination
{

    protected $url, $redirect_url;
    protected $times_redirected = 0;
    protected $html;
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
            $meta_url = $this->parseMetaTags();
            if($meta_url) {
                return $meta_url;
            }
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
        $response = curl_exec($ch);

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);
        $header = substr($response, 0, $header_size);
        $this->html = substr($response, $header_size);

        if($this->html) {
             if(preg_match('#Location: (.*)#', $header, $matches)) {
                return trim($matches[1]);
            }
        }

        return false;

    }

    private function parseMetaTags() {
        $dom = new DOMDocument;
        @$dom->loadHTML($this->html);

        foreach ($dom->getElementsByTagName('meta') as $meta) {
            $attr = array();
            if ($meta->hasAttributes()) {
                foreach ($meta->attributes as $attribute) {
                    $attr[$attribute->nodeName] = $attribute->nodeValue;
                }
                if(isset($attr['property']) && $attr['property']=='og:url') {
                    return $attr['content'];
                }
            }
        }
        foreach ($dom->getElementsByTagName('meta') as $meta) {
            $attr = array();
            if ($meta->hasAttributes()) {
                foreach ($meta->attributes as $attribute) {
                    $attr[$attribute->nodeName] = $attribute->nodeValue;
                }
                if(isset($attr['name']) && $attr['name']=='twitter:url') {
                    return $attr['content'];
                }
            }
        }
        foreach ($dom->getElementsByTagName('link') as $link) {
            $attr = array();
            if ($link->hasAttributes()) {
                foreach ($link->attributes as $attribute) {
                    $attr[$attribute->nodeName] = $attribute->nodeValue;
                }
                if(isset($attr['rel']) && $attr['rel']=='canonical') {
                    return $attr['href'];
                }
            }
        }

        return false;
    }

}