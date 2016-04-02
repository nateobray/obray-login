<?php

/***********************************************************************
	
Obray - Super lightweight framework.  Write a little, do a lot, fast.
Copyright (C) 2013  Nathan A Obray

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

***********************************************************************/

if (!class_exists( 'OObject' )) { die(); }

Class cLogin extends cRoot {
	
	public function __construct(){
		
		parent::__construct();
		$this->permissions = array(
			"object" => "any",
			"index" => "any",
			"authenticate" => "any",
			"forgot" => "any",
			"reset" => "any"
		);

		if( empty($_SESSION['cart']) ){
			$_SESSION["cart"] = new stdClass();
			$_SESSION["cart"]->items = array();
			$_SESSION["cart"]->total = 0;
		}
		
		if( empty($_SESSION["shipTo"]) ){
			$_SESSION["shipTo"]	= array();
		} 
		
		$this->js("serialize,jquery.payment.min.js,oRouter,login");
		$this->css("cart");
		
	}
	
	/***********************************************************************
	
		PUBLIC: INDEX Function
		
	***********************************************************************/
	
	public function index($params=array()){
		
		$this->setContentType("text/html");
		if( !empty($params["id"]) && !empty($params["token"]) ){
			$ocustomer = $this->route("/m/customers/oCustomers/get/?with=user&filter=false&ocustomer_id=".urlencode($params["id"]) )->getFirst();
			
			if( !empty($ocustomer) && md5($ocustomer->ocustomer_id.$ocustomer->ocustomer_email) == $params["token"] ){
				
				$this->set("reset",TRUE);
				
				if( empty($ocustomer->user[0]->ouser_id) ){
					$ouser = $this->route("/m/oUsers/add/?ouser_email=".$ocustomer->ocustomer_email.'&ouser_permission_level=1&ouser_status=active&ouser_password='.md5(rand()))->getFirst();
					$ocustomer = $this->route("/m/customers/oCustomers/update/?ocustomer_id=".$ocustomer->ocustomer_id."&ouser_id=".$ouser->ouser_id);
					$_SESSION["reset_user"] = $ouser->ouser_id;
				} else {
					$_SESSION["reset_user"] = $ocustomer->user[0]->ouser_id;
				}
				
				
				
			}
		}
		
		$this->load('login');	
		
	}
	
	public function authenticate( $params=array() ){
		
		$response = $this->route("/m/oUsers/login/?ouser_email=".urlencode($params["ouser_email"])."&ouser_password=".urlencode($params["ouser_password"]) );
		
		if( !empty($response->errors) ){
			$this->throwError("There was an error.");
			$this->errors = $response->errors;
		} else if ( empty($response->data) ){
			$this->throwError("login invalid.");
		} else {
			$this->data = $response->data;
			$response = $this->route("/m/customers/oCustomers/get/?ouser_id=".$this->data[0]->ouser_id."&filter=false&with=shipTo|card");
			if( empty($response->errors) ){
				$_SESSION['customer'] = $response->data[0];
				if( !empty($response->data[0]->shipTo[0]) ){
					$_SESSION['shipTo'] = $response->data[0]->shipTo[0];
					$_SESSION['card'] = $response->data[0]->card[0];
				}
				$this->data[0]->customer = $response->data[0];
				
			} else {
				$this->throwError("There was an error.");
				$this->errors = $response->errors;
			}
		}
		
	}
	
	public function forgot( $params=array() ){
		
		$ouser = $this->route("/m/oUsers/get/?ouser_email=".$params["ouser_email"]."&with=customer&filter=false")->getFirst();
		
		if( !empty($ouser) ){
			
			$this->route("/m/oMail/send/",array(
				"omail_to" => "nathanobray@gmail.com",$ouser->ouser_email,
				"omail_from" => __OUTGOING_EMAIL_ADDRESS__,
				"omail_subject" => "Stewart Time: Reset Password Request",
				"omail_message" => '<p>Hello '.$ouser->customer[0]->ocustomer_name.',</p>We\'ve received a request to reset your password.  If you made this request please <a href="'.__SITE_URL__.'/login/?id='.$ouser->customer[0]->ocustomer_id.'&token='.md5($ouser->customer[0]->ocustomer_id.$ouser->customer[0]->ocustomer_email).'">click here</a> to reset your password.<p><p>If you didn\'t make this request please disregard this email.</p><p>Thank you!<br/>Stewart Time Customer Care</p>'
			));
			
		} else{
			
			$this->throwError("User not found.");
			
		}
		
		
	}
	
	public function reset( $params=array() ){
		if( !empty($_SESSION["reset_user"]) && $params["ouser_password"] === $params["ouser_confirm_password"] ){
			$response = $this->route('/m/oUsers/update/?ouser_id='.$_SESSION["reset_user"].'&ouser_password='.$params["ouser_password"]);
			if( !empty($response->errors) ){
				$this->throwError("There was an error.");
				$this->errors = $response->errors;
			} else {
				$this->data = $response->data;
			}
		}
	}
	
	
		
}?>
