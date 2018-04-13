<?php
	class ModelDisbursement extends CI_Model {

		function cek_external_id($external_id){
			$sql 	= "SELECT * FROM `pouch_disbursements` WHERE external_id = ?";
			$query	= $this->db->query($sql,array($external_id));
			if($query->num_rows()>0){
				return "existed";
			}else{
				return "unexisted";
			}
		}

		function cek_amount($company_id,$amount){
			$sql 	= "SELECT company_balance FROM `pouch_mastercompanyaccount` WHERE company_id = ?";
			$query	= $this->db->query($sql,array($company_id));
			if($query->num_rows()>0){
				$row = $query->row();
				$company_balance = $row->company_balance;
			}else{
				$company_balance = 0;
			}
			if($amount > $company_balance){
				return "not_enough";
			}else{
				return "enough";
			}
		}

		function cek_company_id($company_id,$user_id){
			$sql 	= "SELECT * FROM `pouch_masteremployeecredential` WHERE userID = ? and company_id = ?";
			$query	= $this->db->query($sql,array($user_id,$company_id));
			if($query->num_rows()>0){
				return "registed";
			}else{
				return "unregisted";
			}
		}

        function create($data){
			list($external_id,$amount,$bank_code,$account_name,$account_number,$description,$user_id,$company_id)=$data;
			$disburse_id = $this->disburse_id($company_id);
			$now = date("Y-m-d H:i:s");
			$inserdata = array(
				"disburse_id"=>$disburse_id,
				"external_id"=>$external_id,
				"amount"=>$amount,
				"bank_code"=>$bank_code,
				"account_name"=>$account_name,
				"account_number"=>$account_number,
				"description"=>$description,
				"status"=>"PENDING",
				"created_datetime"=> $now,
				"company_id"=>$company_id,
				"user_id"=>$user_id
			);
			$sql = $this->db->insert("pouch_disbursements",$inserdata);
			if($sql){
				$response = array(
					"id"=>md5($disburse_id),
					"external_id"=>$external_id,
					"amount"=>$amount,
					"bank_code"=>$bank_code,
					"account_name"=>$account_name,
					"account_number"=>$account_number,
					"description"=>$description,
					"status"=>"PENDING",
					"created_datetime"=> $now,
					"user_id"=>md5($user_id)
				);
			}else{
				$response = false;
			}
			return $response;
		}
		
		function disburse_id($company_id){
			$initiatx   = "TRDSB".$company_id;
            $trnsc      = strlen($initiatx);
            $month   = date("m");
            $day     = date("d");
            $year    = date("y");
            $sql    = "SELECT left(a.disburse_id,2) as fmonth, mid(a.disburse_id,3,2) as fday," 
                    . " mid(a.disburse_id,5,2) as fyear, mid(a.disburse_id,7,$trnsc) as initiat,"
                    . " right(a.disburse_id,6) as fno FROM pouch_disbursements AS a"
                    . " where left(a.disburse_id,2) = '$month' and mid(a.disburse_id,3,2) = '$day'"
                    . " and mid(a.disburse_id,5,2) = '$year' and mid(a.disburse_id,7,$trnsc)= '$initiatx'"
                    . " order by fmonth desc, CAST(fno AS SIGNED) DESC LIMIT 1";
            // return $sql;
            $result = $this->db->query($sql);	
            
            if($result->num_rows($result) > 0) {
                $row = $result->row();
                $initiat = $row->initiat;
                $fyear = $row->fyear;
                $fmonth = $row->fmonth;
                $fday = $row->fday;
                $fno = $row->fno;
                $fno++;
            } else {
                $initiat = $initiatx;
                $fyear   = $year;
                $fmonth  = $month;
                $fday    = $day;
                $fno     = 0;
                $fno++;
            }
            if (strlen($fno)==1){
                $strfno = "00000".$fno;
            } else if (strlen($fno)==2){
                $strfno = "0000".$fno;
            } else if (strlen($fno)==3){
                $strfno = "000".$fno;
            } else if (strlen($fno)==4){
                $strfno = "00".$fno;
            } else if (strlen($fno)==5){
                $strfno = "0".$fno;
            } else if (strlen($fno)==6){
                $strfno = $fno;
            }
            
            $disburse_id = $month.$day.$year.$initiat.$strfno;

            return $disburse_id;
		}
	}
?>