<?php

define('_ENVATO_DEBUG',false);

// EXAMPLE USAGE, return all of author "dtbaker" items from codecanyon and themeforest
$my_scraper = new envato_scraper();
$items = $my_scraper->get_users_items('dtbaker',array('http://codecanyon.net','http://themeforest.net'));
print_r($items);

class envato_scraper{

    private $ch; // curl objecct

    public function __construct(){

        // list of all supported marketplaces.
        $this->marketplaces = array(
            "http://codecanyon.net",
            "http://themeforest.net",
            "http://activeden.net",
            "http://audiojungle.net",
            "http://videohive.net",
            "http://graphicriver.net",
            "http://3docean.net",
            "http://photodune.net",
        );

    }

    /**
     * This pulls back list of all user items across all marketplaces (or specified marketplace)
     *
     * @return array of items
     */
    public function get_users_items($username,$from_marketplaces=array()){
        if(!is_array($from_marketplaces))$from_marketplaces=array($from_marketplaces);
        $items = array();
        if($username){
            foreach($this->marketplaces as $marketplace_url){
                if($from_marketplaces && !in_array($marketplace_url,$from_marketplaces))continue;
				$page_number = 1;
				$item_count = 0;
				while(true){
					// dub call to above, will be cached so not worried.
                    $url = $marketplace_url . '/user/'.$username .'/portfolio?page='.$page_number;

                    if(_ENVATO_DEBUG){
                        echo "Checking URL $url<br>\n";
                    }
					$feed_data = $this->_get_url($url);
                    if(!preg_match('#Currently Viewing#',$feed_data)){
                        // no items on this marketplace, continue onto next.
                        break;
                    }
                    // split each item up.
                    if(preg_match_all('#<li class="[^"]*" data-item-id="(\d+)">.*</li>#imsU',$feed_data,$matches)){
                        // we now have each item on this page.
                        // grab the preview image:
                        foreach($matches[0] as $match_id => $match){
                            $item = array();
                            $item['item_id'] = $matches[1][$match_id];
                            if(preg_match('#data-preview-url="([^"]*)"#',$match,$preview_image)){
                                $item['preview_image'] = $preview_image[1];
                            }
                            if(preg_match('#data-item-cost="([^"]*)"#',$match,$data)){
                                $item['cost'] = $data[1];
                            }
                            if(preg_match('#data-item-name="([^"]*)"#',$match,$data)){
                                $item['name'] = html_entity_decode($data[1]);
                            }
                            if(preg_match('#<small class="sale-count">(\d+) Sales</small>#',$match,$data)){
                                $item['sales'] = $data[1];
                            }
                            if(preg_match('#data-item-category="([^"]*)"#',$match,$data)){
                                $item['category'] = $data[1];
                            }
                            // first image is thumb
                            if(preg_match('#<img src="([^"]+)"#',$match,$thumb)){
                                $item['thumb_image'] = $thumb[1];
                            }
                            if(preg_match('#<h3>\s*<a href="([^"\?]+)[?"][^>]*>([^<]+)</a>\s*</h3>#imsU',$match,$title_url) ){
                                //$item["name"]=html_entity_decode($title_url[2]);
                                $item['url'] = (preg_match('/^http/',$title_url[1])) ? $title_url[1] : $marketplace_url . $title_url[1];
                            }
                            $item['marketplace']=$marketplace_url;
                            $items[] = $item;
                            $item_count++;
                        }
                    }
					if(_ENVATO_DEBUG){
						echo "Found $item_count items on $marketplace_url from page $page_number<br>";
					}
					$page_number++;
					if(!preg_match('/portfolio\?page='.$page_number.'/',$feed_data)){
						break;
					}
					if(_ENVATO_DEBUG){
						echo "Trying to read page $page_number of profile items.. <br>";
					}
				}
				if(_ENVATO_DEBUG){
					echo "Finished searching $marketplace_url for users items, found $item_count items.<br>";
				}
            }

        }
        return $items;
    }

    /**
     * This method handles all the remote URL gets, and caching.
     *
     * @param string $url Url to get: eg http://themeforest.net/user/dtbaker
     * @param array $post Any post data to send (eg: login details)
     * @param bool $force Force it to refresh, aka: dont read from cache.
     * @return string  HTML data that came back from request.
     */
    private function _get_url($url,$post=array(),$force=false){

        $cache_key = md5($url . serialize($post));
        $data = ($force) ? false : $this->_get_cache($cache_key);
        if(!$data){
            if(!$this->ch){
                $this->ch = curl_init();
                curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
                @curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 3);
                curl_setopt($this->ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($this->ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
                curl_setopt($this->ch, CURLOPT_HEADER, 0);
            }
            curl_setopt($this->ch, CURLOPT_URL, $url);
            if($post){
                curl_setopt($this->ch, CURLOPT_POST, true);
                curl_setopt($this->ch, CURLOPT_POSTFIELDS, $post);
            }else{
                curl_setopt($this->ch, CURLOPT_POST, false);
                curl_setopt($this->ch, CURLOPT_POSTFIELDS, false);
            }

            $data = curl_exec($this->ch);
            $this->_save_cache($cache_key,$data);
        }
        return $data;

    }


    private function _get_cache($key){
        if(is_file('envato-cache/'.basename($key))){
            return @unserialize(file_get_contents('envato-cache/'.basename($key)));
        }
        return false;
    }
    public function _save_cache($key,$data){
        file_put_contents('envato-cache/'.basename($key),serialize($data));
        return true;
    }

}


