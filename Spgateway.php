<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Spgateway {

	private $HashKey = null;
	private $HashIV = null;
	private $MerchantID = null;
	
	private $amt = 0;
	private $chantorderno = '';
	private $returnurl;
	private $notifyurl;
	private $email;
	private $emailmodify;
	private $itemdesc;
	private $ordercomment;

	private $credit = 0;
	private $online = false;

	public function __construct($params){

		$this->HashIV = $params['HashKey'];
		$this->HashIV = $params['HashIV'];
		$this->MerchantID = $params['MerchantID'];
	}
	public function set_url($params){
		$this->returnurl = $params['ReturnURL'];
		$this->notifyurl = $params['NotifyURL'];
	}
	public function online($yes = false){
		if ($yes===true){
			$this->online = true;
		}else{
			$this->online = false;
		}
	}
	public function pay_credit($yes = true){
		$this->credit = ($yes) ? 1:0;
	}
	//產生驗證碼
	private function _getCheckValue($orderno, $amt, $time){
		$data_array = array(
			'MerchantID' => $this->MerchantID,
			'MerchantOrderNo' => $orderno,
			'TimeStamp' => $time,
			'Amt' => $amt,
			'Version' => '1.2'
			);
		ksort($data_array);
		$CheckValue_data = http_build_query($data_array);
		return strtoupper(hash("sha256", 'HashKey='.$this->HashKey.'&'.$CheckValue_data.'&HashIV='.$this->HashIV));
	}
	//產生檢核碼
	private function _getCheckCode($orderno, $amt, $tradeno){
		$data_array = array(
			'MerchantID' => $this->MerchantID,
			'MerchantOrderNo' => $orderno,
			'Amt' => $amt,
			'TradeNo' => $tradeno
		);
		ksort($data_array);
		$CheckCode_data = http_build_query($data_array);
		return strtoupper(hash("sha256", 'HashIV='.$this->HashIV.'&'.$CheckCode_data.'&HashKey='.$this->HashKey));
	}
	private function _order_check(){
		if (empty($this->chantorderno)) 
			throw new Exception("訂單編號空值");
		if (strlen($this->chantorderno)>20) 
			throw new Exception("訂單編號超過20字元");
		// money
		if (empty($this->amt)) 
			throw new Exception("金額空值");
		if (!is_numeric($this->amt)) 
			throw new Exception("金額格式錯誤");
		$amt_a = explode('.',$this->amt);
		if (count($amt_a)>1 || $amt_a[0]<0) 
			throw new Exception("金額格式錯誤");
		$amt = (int)$this->amt;
		if ($amt===0) 
			throw new Exception("金額需大於0");
		$this->amt = $amt;
		if (empty($this->itemdesc)) 
			throw new Exception("訂單資訊空值");
		if (strlen($this->itemdesc)>50) 
			throw new Exception("訂單資訊超過50字元");
		if (!empty($this->ordercomment)){
			if (strlen($this->ordercomment)>300) 
				throw new Exception("訂單備註超過300字元");
		}
	}
	private function _merchat_check(){

		if (empty($this->HashIV))
			throw new Exception("設定錯誤");
		if (empty($this->HashIV))
			throw new Exception("設定錯誤");
		if (empty($this->MerchantID));
			throw new Exception("設定錯誤");
	}
	//建立訂單
	public function order_init($params){
		$this->chantorderno = $params['orderno'];
		$this->amt = $params['amt'];
		$this->itemdesc = $params['title'];
		$this->ordercomment = $params['order_ps'];

		$this->email = $params['email'];
		$emailmodify = (int)$params['emailmodify'];
		if ($emailmodify!==0 && $emailmodify!==1) $emailmodify = 1;
		$this->emailmodify = $emailmodify;
		$this->_order_check();
	}
	// 進行交易
	public function pay(){
		$this->_merchat_check();
		$this->_order_check();
		$url = ($this->online) ? "https://core.spgateway.com/MPG/mpg_gateway":"https://ccore.spgateway.com/MPG/mpg_gateway";
		$time = time();
		$CheckValue = $this->_getCheckValue($this->chantorderno, $this->amt, $time);
		return '<form name="Pay2go" id="Pay2go" method="post" action="'.$url.'">
			<input type="hidden" name="MerchantID" value="'.$this->MerchantID.'">
			<input type="hidden" name="RespondType" value="JSON">
			<input type="hidden" name="CheckValue" value="'.$CheckValue.'">
			<input type="hidden" name="TimeStamp" value="'.$time.'">
			<input type="hidden" name="Version" value="1.2">
			<input type="hidden" name="LangType" value="zh-tw">
			<input type="hidden" name="MerchantOrderNo" value="'.$this->chantorderno.'">
			<input type="hidden" name="Amt" value="'.$this->amt.'">
			<input type="hidden" name="ItemDesc" value="'.$this->itemdesc.'">
			<input type="hidden" name="ReturnURL" value="'.$this->returnurl.'">
			<input type="hidden" name="NotifyURL" value="'.$this->notifyurl.'">
			<input type="hidden" name="ClientBackURL">
			<input type="hidden" name="Email" value="'.$this->email.'">
			<input type="hidden" name="EmailModify" value="'.$this->emailmodify.'">
			<input type="hidden" name="LoginType" value="0">
			<input type="hidden" name="OrderComment" value="'.$this->ordercomment.'">
			<input type="hidden" name="CREDIT" value="'.$this->credit.'">
			<input type="hidden" name="WEBATM" value="1">
			<input type="hidden" name="VACC" value="1">
			<input type="hidden" name="CVS" value="1">
			<input type="hidden" name="BARCODE" value="1">
		</form>
		<script type="text/javascript">
			//Pay2go.submit();
		</script>';
	}
	public function check_receive($params){
		return ($params['code']===$this->_getCheckCode($params['orderno'], $params['amt'], $params['tradeno']));
	}
}