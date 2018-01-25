<?php

  /**
   *   MMN.    .MM#   .JNMMMM{ dMMMMMNx.  (MMMMMNm,   MMm.   (M) .JNMMMN
   *   M#Mb   .d#M#  (M@`      dN~   (MN- (M]    TMp  M#HN,  (M) JM[
   *   Mb(M[  +#~M# .M#        dN~    ,Mr (M]     M#  M# TN, (M) .TMNJ,
   *   Mb ?N,.#> M# .MN.       dN~    .M% (M]    .MF  M#  ?NeJM)    ?TMN.
   *   Mb  WNM%  M#  ?MN,.  .. dN_ ..JMD  (M]  ..M#`  M#`  (MNM) (,. .JM\
   *   HD   HD   M@    7WMMH9! dMMMM9"`   (MMMMB"!    HE    .TM\ ?TMMH"^
   *   
   *   @author soradore
   *   
   *   Using conoha.jp
   *   project name=>[MCDDNS]
   *
   */

require "CONFIG.php";
require "punycode.php";

class API
{


    public $token_id;
    public $domain_id;
    public $record_id;

    /** var string $base  example=>[xn--????] **/

    public static $base;
	
    public function __construct()
    {
        $this->baseDomainToPuny();
        $this->setToken();
    }
  
    public function baseDomainToPuny()
    {
        $d = explode('.', BASE_DOMAIN_NAME);
        $puny = Punycode::encode($d[0]);
        self::$base = $puny.".".$d[1];
        echo self::$base;
    }


    public function setDomain_id($id = "")
    {
        
        if($id == "") $id = $this->token_id;

        $url = API_DNS_SERVICE."/v1/domains";
        $headers = [
                   "Accept: application/json",
                   "Content-Type: application/json",
                   "X-Auth-Token: {$id}" 
                  ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $res = curl_exec($ch);
        $res = json_decode($res, true);
        $d_id = null;
        foreach($res["domains"] as $domain){
        	if($domain["name"] === self::$base."."){
        		$d_id = $domain['id'];
        	}
        }
        if(empty($d_id)) return false;
        $this->domain_id = $d_id;

        return true;
    }


    public function setRecord_id($uniq_name)
    {
        $id = $this->token_id;
        $uuid = $this->domain_id;
        $url = API_DNS_SERVICE."/v1/domains/{$uuid}/records";
        $name = $uniq_name.".".self::$base.".";
        $headers = [
                   "Accept: application/json",
                   "Content-Type: application/json",
                   "X-Auth-Token: {$id}" 
                  ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $res = curl_exec($ch);
        $res = json_decode($res, true);
        $r_id = null;
        foreach($res["records"] as $record){
            if($record["name"] === $name){
                $r_id = $record['id'];
            }
        }

        $this->record_id = $r_id;
        return true;
    }
	

	public function setToken()
	{
	    $url = API_IDENTITY_SERVICE . "/tokens";

        $headers = array(
                         "Accept: application/json"
                        );

        $req_data = array(
            "auth" => array(
                "passwordCredentials" => array(
                    "username" => API_USERNAME,
                    "password" => API_PASSWORD
                ),
                "tenantId" => API_TENANT_ID
            )
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($req_data));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $res_json =  curl_exec($ch);
        $res_data = json_decode($res_json, true);
        curl_close($ch);
        $this->token_id = $id = $res_data["access"]["token"]["id"];
        return $this->setDomain_id($id);
    }  

    /**
     * @param string $name  example=>[www]
     * @param string $ip    example=>[192.168.0.1]
     */

    public function create_new_record($name, $ip)
    {
    	$id = $this->token_id;
    	$uuid = $this->domain_id;
    	$url = API_DNS_SERVICE."/v1/domains/{$uuid}/records";
    	$headers = [
    		        "Accept: application/json",
    		        "Content-Type: application/json",
    		        "X-Auth-Token: {$id}"
    		       ];
        $body = [
        	     "name" => $name.".".self::$base.".",
        	     "data" => $ip,
        	     "type" => "A",
        	     "gslb_region" => "JP"
                ];
        $req_json = json_encode($body);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $req_json);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $res = curl_exec($ch);
        /*$status = curl_getinfo($ch, CURL_HTTP_CODE);
        if($status !== 200) return false;*/
        $res = json_decode($res, true);

        return $res;

    }


    public function refresh_record($name, $ip)
    {
        $id = $this->token_id;
        $uuid = $this->domain_id;
        $r_id = $this->record_id;
        $url = API_DNS_SERVICE."/v1/domains/{$uuid}/records/{$r_id}";
        $headers = [
                    "Accept: application/json",
                    "Content-Type: application/json",
                    "X-Auth-Token: {$id}"
                   ];
        $req_data = [
                     "name" => $name.".".self::$base.".",
                     "type" => "A",
                     "data" => $ip
                    ];
        $req_json = json_encode($req_data);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $req_json);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $res = curl_exec($ch);

        return $res;
    }
}
