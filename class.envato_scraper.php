<?php

define('_ENVATO_DEBUG_MODE',false);
define('_ENVATO_TMP_DIR',dirname(__FILE__).'/envato-cache/');
define('_ENVATO_SECRET',"asiu234lk23j4234l2j42l3i4u2j34k2134nlkj2h42kjgasf"); // some unique code

class envato_scraper{


    private $waiting_on_recaptcha = false;
    private $logged_in = false;
    private $username = false;
    // list of all supported marketplaces.
    private $marketplaces = array(
            "http://themeforest.net",
            "http://codecanyon.net",
            "http://activeden.net",
            "http://audiojungle.net",
            "http://videohive.net",
            "http://graphicriver.net",
            "http://3docean.net",
            "http://photodune.net",
        );
    private $authed_marketplaces=array();// which ones we have /sign_in?auto=true&to=X to
    private $main_marketplace = 'http://themeforest.net';

    public function __construct($main_marketplace='http://themeforest.net'){
        if(in_array($main_marketplace,$this->marketplaces)){
            $this->main_marketplace = $main_marketplace;
        }
        if(!is_dir(_ENVATO_TMP_DIR) || !is_writable(_ENVATO_TMP_DIR)){
            echo 'please make sure the temp directory '._ENVATO_TMP_DIR.' is writable by PHP scripts.';
        }
    }

    /**
     * This pulls back list of all user items across all marketplaces (or specified marketplace)
     *
     * @param $user
     * @param array $from_marketplaces
     * @return array of items
     */
    public function get_users_items($user,$from_marketplaces=array()){
        $files = array();
        if(!is_array($from_marketplaces))$from_marketplaces=array($from_marketplaces);
        foreach($from_marketplaces as $marketplace){
                  //http://marketplace.envato.com/api/v2/new-files-from-user:collis,themeforest.json
            $url = "http://marketplace.envato.com/api/v2/new-files-from-user:$user,$marketplace.json";
            if(_ENVATO_DEBUG_MODE){
                echo " Grabbing API url: $url <bR>\n";
            }
            $data = $this->_get_url($url,array(),false);
            if(!empty($data)) {
                $json_data = json_decode($data, true);
                if(_ENVATO_DEBUG_MODE){
                    echo "data: ";print_r($json_data);
                }
                $files = array_merge($files,$json_data['new-files-from-user']);
            }
        }
        return $files;
    }

    public function authenticate_marketplace($url){
        if(!in_array($url,$this->marketplaces))return false;
        $marketplace_tag = str_replace('.net','',str_replace('http://','',$url));
        if(isset($this->authed_marketplaces[$marketplace_tag])){
            $this->logged_in = true;
        }else{
            $auth_check = $this->_get_url('https://account.envato.com/sign_in?auto=true&to='.$marketplace_tag,array(),true); // todo - force this one?
            if(
                preg_match('#/sign_out["\?]#',$auth_check) && 
                preg_match('#meta content="([^"])+" name="csrf-token"#', $auth_check, $csrf_matches)
            ){
                $this->authed_marketplaces[$marketplace_tag]=$csrf_matches[1];
                return true;
            }
        }
        return false;
    }

    /**
     * do_login! The magic method that logs you into envato marketplaces. yew!
     * Even supports recaptcha if you're loading this script from a web browser :P
     *
     * @param string $username
     * @param string $password
     * @param int $try_number
     * @param bool $data
     * @return bool|int
     */
    public function do_login($username,$password,$try_number=0,$data=false){

        if($this->logged_in)return true;

        $this->username = $username;
        if($this->waiting_on_recaptcha){
            echo 'Waiting on recaptcha. Run script from browser.';
            return false;
        }
        if(!$data){
            $data = $this->_clean($this->_get_url($this->main_marketplace.'/forums',array(),true));
        }
        // check if we are logged in or not.
        // simply look for the string logout and Log Out
        if($try_number>1){
            // TODO: handle if envato is down for maintenance
            echo "Unable to login. Sorry, please try again shortly.";
            return false;
        }else if(preg_match('#/sign_out["\?]#',$data)){
            // if sign_out is present on the page then we are logged in
            // new redirect hack with new account centre setup
            $this->logged_in = $this->authenticate_marketplace($this->main_marketplace);
        }else if($username && $password){

            $data = $this->_get_url('https://account.envato.com');
            $auth_token = '';
            if(preg_match('#name="authenticity_token" type="hidden" value="([^"]+)"#',$data,$matches)){
                $auth_token = $matches[1];
                if($auth_token){
                    $post_data = array(
                        "username"=>$username,
                        "password"=>$password,
                        "authenticity_token" => $auth_token,
                        "utf8" => '&#x2713;',
                        "commit" => 'Sign In',
                        //"from_header_bar"=>"true",
                    );
                    if(isset($_REQUEST['authentication_code'])){
                        $post_data['authentication_code'] = $_REQUEST['authentication_code'];
                    }

                    if(isset($_POST['recaptcha'.md5($this->main_marketplace)])){
                        $post_data["recaptcha_challenge_field"]=$_POST['recaptcha'.md5($this->main_marketplace)];
                        $post_data["recaptcha_response_field"]='manual_challenge';
                        unset($_POST['recaptcha'.md5($this->main_marketplace)]);
                    }
                    if(_ENVATO_DEBUG_MODE){
                        echo "Login attempt $try_number with username: ".$username." <br> ";
                    }
                    $url = "https://account.envato.com/sign_in";
                    $data = $this->_get_url($url,$post_data,true);
                    if(_ENVATO_DEBUG_MODE){
                        file_put_contents(_ENVATO_TMP_DIR."debug-envato_login-".$try_number.".html",$data);
                        echo "Saved LOGIN ATTEMPT file at: "._ENVATO_TMP_DIR."debug-envato_login-".$try_number.".html <br>";
                    }
                    if(preg_match('#temporarily locked out#',$data)){
                        echo "Sorry, temporarily locked out for too many failed login attempts.";
                        return 0;
                    }else if (preg_match('#recaptcha/api/noscript#',$data)){
                        $this->waiting_on_recaptcha=true;
                        echo "Sorry, too many failed envato login attempts on ".$this->main_marketplace.". Please enter the re-captcha code below. <br>";
                        // <iframe src="https://www.google.com/recaptcha/api/noscript?k=6LeL1wUAAAAAAJ6M4Rd6GzH86I_9_snNaLPqy_ff" h
                        if(preg_match('#<iframe src="https://www.google.com/recaptcha/api/noscript[^"]*"[^>]*>#',$data,$matches)){
                            echo $matches[0].'</iframe>';
                            ?>
                            <br>
                            <form action="" method="post">
                                Enter Code: <input type="text" name="recaptcha<?php echo md5($this->main_marketplace);?>"> <input type="submit" name="go" value="Submit">
                                <?php foreach($_POST as $key=>$val){
                                    if(strpos($key,'recaptcha')!==false && $key != 'recaptcha'.md5($this->main_marketplace)){
                                    ?>
                                <input type="hidden" name="<?php echo $key;?>" value="<?php echo $val;?>">
                                    <?php
                                    }
                                } ?>
                            </form>
                            <?php
                        }
                        return 0;
                    }
                }else{
                    echo 'failed: no auth token';
                }
            }else{
                echo 'failed. no auth token found on page';
            }
            return $this->do_login($username,$password,$try_number+1,$data);
        }else {
            // no username or password, set, return false so we prompt them to login.
            return false;
        }

        // $data now contains our home page in logged in version.
        // how much cash do we have?
        //<span class="user_balance">$4,829.40</span>
        /*if(preg_match('#class="user_balance">\$([^<]+)<#',$data,$matches)){
            print_r($matches);
        	$this->account_balance = preg_replace('/[^\.\d]/','',$matches[1]);
        }*/
        return $this->logged_in;
    }

    /**
     *
     * This method will return the CSV statement for Envato earnings.
     * Useful for manual calculations within your own system.
     * eg: a system that automatically calculates split earnings on collaboration items.
     *
     * @param bool|string $datefrom
     * @param bool|string $dateto
     *
     * @return array
     */
    public function get_statement($datefrom,$dateto=false){

        //if(!$this->logged_in || !$this->username || !$datefrom)return array();

        $items = array();
        $current_month = date('n');
        $current_year = date('Y');
        // work out what dates we need to grab from the statement.
        list($from_month,$from_year) = explode('/',$datefrom);
        $statement_url_requests = array();
        if($from_year<=$current_year && (
            ($from_year==$current_year && $from_month <= $current_month) ||
            ($from_year<$current_year)
        )){
            // we have a valid from date! do the loop.
            $xm = $from_month;
            for($xy=$from_year;$xy<=$current_year;$xy++){
                while(
                    $xm <= 12 && (
                        ($xy==$current_year && $xm<=$current_month) ||
                        ($xy<$current_year)
                    )
                ){
                    $statement_url_requests[] = $this->main_marketplace . "/user/".$this->username."/download_statement_as_csv?month=".$xm.'&year='.$xy;
                    $xm++;
                }
                if($xm>12){
                    $xm=1;
                }
            }
        }
        if(_ENVATO_DEBUG_MODE){
            echo 'grabbing these statement urls:';
            print_r($statement_url_requests);
        }

        foreach($statement_url_requests as $url){
            if(strpos($url,'month='.$current_month.'&year='.$current_year)){
                // we always grab a new copy of the latest months statement:
                // any previous months we always use the cached version if they exist.
                $data = $this->_get_url($url,array(),true);
            }else{
                // fall back to cache.
                $data = $this->_get_url($url,array(),false);
                if(preg_match('#<html#',$data) && $this->_got_url_from_cache){
                    // we got a cached html file, try again without cache mode just for kicks.
                    $data = $this->_get_url($url,array(),true);
                }
            }
            if(preg_match('#<html#',$data)){
                echo 'failed, probably not logged in correctly, invalid month or envato is temporarily down.';
                return array();
            }
            // save as temp file and use fgetcsv
            // dont want to use str_getcsv because it requires 5.3 and some people are still on 5.2.
            $temp_csv_file = _ENVATO_TMP_DIR.'envato_'.basename($this->username)."_statement-csv-current.csv";
            file_put_contents($temp_csv_file,$data);
            if($temp_csv_file && is_file($temp_csv_file)){
                $fd = fopen($temp_csv_file,"r");
                $count = 1;
                while (($data = fgetcsv($fd, 1000, ",")) !== FALSE) {
                    if(1 == $count){
                        $count++;
                        continue;
                    } // dont save header.
                    if(count($data)<2)continue;
                    $items[]=$data;
                    $count++;
                }
                if(_ENVATO_DEBUG_MODE){
                    echo "Month: $temp_csv_file got $count items<br>";
                }
                fclose($fd);
            }


        }

        foreach($items as &$foo){

            $item_name = str_replace('"','',$foo[2]);
            $item_id = (int)str_replace('"','',$foo[3]);

            $item_type = str_replace('"','',trim($foo[1]));
            $item_amount = 0;
            $item_rate = str_replace('%','',$foo[5]);
            $earnt = str_replace('"','',$foo[4]);
            if($item_type == 'sale'){
                // support the old method of logging stuff:
                if(preg_match('/sold (.*) for (\d.*) w\/ rate of (.*)%/U',str_replace('"','',$foo[3]),$matches)){
                    $item_name = $matches[1];
                    $item_amount = $matches[2];
                    $item_rate = $matches[3];
                    $earnt = str_replace('"','',$foo[2]);
                }else{
                    $item_name = $foo[2];
                    $item_id = (int)$foo[3];
                    $item_amount = $foo[6];
                    $item_rate = str_replace('%','',$foo[5]);
                    $earnt = str_replace('"','',$foo[4]);
                }

            }

            $line = array(
                "type" => $item_type,
                "date" => trim($foo[0]),
                "time" => strtotime(trim($foo[0])),
                "item" => $item_name,
                "item_id" => $item_id,
                'envato_item_id'=>0, // database id.
                "earnt" => $earnt,
                "amount" => $item_amount,
                "rate" => $item_rate,
            );
            $foo = $line;
        }
        if(_ENVATO_DEBUG_MODE){
            echo "There are ".count($items)." lines in your statement CSV file. Is this correct? <br>";
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
    private $_got_url_from_cache = false;
    private function _get_url($url,$post=array(),$force=false){

        $cache_key = md5(_ENVATO_SECRET . $url . serialize($post));
        $data = ($force) ? false : $this->_get_cache($cache_key);
        if(!$data){
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_USERAGENT, "EnvatoScraper/1.0 (compatible;)"); // for teh devs
            curl_setopt($ch, CURLOPT_HEADER, _ENVATO_DEBUG_MODE); // debug
            curl_setopt($ch, CURLINFO_HEADER_OUT, _ENVATO_DEBUG_MODE); // debug
            $cookies = _ENVATO_TMP_DIR.'cookie-'.md5(_ENVATO_SECRET.$this->username.__FILE__);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookies);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookies);
            if($post){
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            }

            $data = curl_exec($ch);
            if(_ENVATO_DEBUG_MODE){
                $headers = curl_getinfo($ch, CURLINFO_HEADER_OUT);
                echo '<hr>headers for url '.$url.'<br>';var_dump($headers);echo '<hr>';
                file_put_contents(_ENVATO_TMP_DIR."envato_request-".preg_replace('#[^a-z]#','',$url).".html",$data);
                if(preg_match('#Not Allowed#',$data)){
                    echo "Failed with nginx not allowed on request $url with post data:<br>"; print_r($post);
                }
            }
            $this->_save_cache($cache_key,$data);
            $this->_got_url_from_cache=false;
        }else{
            $this->_got_url_from_cache=true;
        }
        return $data;

    }
    /** caching so we don't hit envato too much **/
    private function _get_cache($key){
        if(is_file(_ENVATO_TMP_DIR.'cache-'.basename($key))){
            return @unserialize(file_get_contents(_ENVATO_TMP_DIR.'cache-'.basename($key)));
        }
        return false;
    }
    private function _save_cache($key,$data){
        file_put_contents(_ENVATO_TMP_DIR.'cache-'.basename($key),serialize($data));
        return true;
    }
    // wack everything on 1 line for easier regex scraping
    private function _clean($data){
        $data = preg_replace("/\r|\n/","",$data);
        $data = preg_replace("/\s+/"," ",$data);
        return $data;
    }

}


