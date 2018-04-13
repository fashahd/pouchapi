<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . 'libraries/REST_Controller.php';
class Disbursement extends REST_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see https://codeigniter.com/user_guide/general/urls.html
	 */
	public function index()
	{
		$this->load->helper('url');

		$this->load->view('welcome_message');
	}
	
	function create_post(){
		$this->load->model("ModelDisbursement");
		// $headersa = apache_request_headers();
		$headers=array();
		foreach ($_SERVER as $name => $value) {
			$headers[$name] = $value;
		}
		if(!isset($headers["HTTP_KEY"])){
			$this->response([
                'status' => FALSE,
                'message' => 'INVALID_AUTHENTICATION'
			], REST_Controller::HTTP_BAD_REQUEST);
			return;
		}

		if($headers["HTTP_KEY"] == ""){
			$this->response([
				'status' => FALSE,
				'message' => 'EMPTY_KEY'
			], REST_Controller::HTTP_NOT_FOUND);
			return;
		}
		$key  = $headers["HTTP_KEY"];
		$tmpsecretkey = str_replace("pouch_dev_secret_","",$key);
		$secret_key   = $this->aes->decrypt_aes256API($tmpsecretkey);
		$arrsecretkey = explode("_",$secret_key);
		if(!isset($arrsecretkey[1]) OR !isset($arrsecretkey[0])){
			$this->response([
				'status' => FALSE,
				'message' => 'KEY_NOT_FOUND'
			], REST_Controller::HTTP_NOT_FOUND);
			return;
		}
		$user_id	  = $arrsecretkey[1];
		$company_id	  = $arrsecretkey[0];
		$cek_company_id = $this->ModelDisbursement->cek_company_id($company_id,$user_id);
		if($cek_company_id != "registed"){
			$this->response([
				'status' => FALSE,
				'message' => 'KEY_NOT_FOUND'
			], REST_Controller::HTTP_NOT_FOUND);
			return;
		}
		$external_id = $this->input->post("external_id");
		$amount 	 = $this->input->post("amount");
		$bank_code 	 = $this->input->post("bank_code");
		$account_name 	= $this->input->post("account_holder_name");
		$account_number = $this->input->post("account_number");
		$description = $this->input->post("description");
		$cek_amount = $this->ModelDisbursement->cek_amount($company_id,$amount);
		if($cek_amount != "enough"){
			$this->response([
				'status' => FALSE,
				'message' => 'OUT_OF_BALANCE'
			], REST_Controller::HTTP_BAD_REQUEST);
			return;
		}
		$cek_external_id = $this->ModelDisbursement->cek_external_id($external_id);
		if($cek_external_id != "unexisted"){
			$this->response([
				'status' => FALSE,
				'message' => 'DUPLICATE_TRANSACTION'
			], REST_Controller::HTTP_BAD_REQUEST);
			return;
		}

		$data 		= array($external_id,$amount,$bank_code,$account_name,$account_number,$description,$user_id,$company_id);
		$response	= $this->ModelDisbursement->create($data);
		$this->response($response, REST_Controller::HTTP_OK);
	}
}