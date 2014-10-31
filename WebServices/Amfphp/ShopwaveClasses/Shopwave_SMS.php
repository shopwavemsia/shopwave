<?php
    //include dirname(__FILE__) . '/Shopwave_Common.php';
    //include dirname(__FILE__) . '/Shopwave_DBTier.php';    
    //define("Config", "Config.ini");
    
    class SMS
    {
	private $config = Config; 	
	private $MobileNo;
	private $Type;
	//private $Content;
	private $smsURL;
	private $Template_Id_OTP;
	private $Template_Id;
	
	public function __construct()
	{
	    $settings 		= parse_ini_file($this->config, TRUE);
            $User 		= $settings['SMS_setting']['sms_Username'];
	    $Pass 		= $settings['SMS_setting']['sms_Password'];
	    $Type 		= $settings['SMS_setting']['sms_Type'];
	    $Signature 		= $settings['SMS_setting']['sms_Signature'];
	    $ServiceId 		= $settings['SMS_setting']['sms_Service_Id'];
	    $ExpInterval 	= $settings['SMS_setting']['sms_Exp_Interval'];
	    $this->Template_Id_OTP = $settings['SMS_setting']['sms_Template_Id_OTP'];	    
	    
	    $this->smsURL	= $settings['SMS_setting']['sms_URL'];
	    $this->smsURL	= str_replace('<sms_Username>',	 	'=' . $User, 		$this->smsURL);
	    $this->smsURL	= str_replace('<sms_Password>',	 	'=' . $Pass, 		$this->smsURL);
	    $this->smsURL	= str_replace('<sms_Type>', 		'=' . $Type, 		$this->smsURL);
	    $this->smsURL	= str_replace('<sms_Signature>',	'=' . $Signature, 	$this->smsURL);
	    $this->smsURL	= str_replace('<sms_Service_Id>', 	'=' . $ServiceId, 	$this->smsURL);
	}
	
	public function Sent()
	{
	    //$status = file_get_contents("https://www.etracker.cc/mes/mesbulk.aspx?user=mktest69&pass=pos1511adsb&type=0&to=".$this->MobileNo."&from=Shopwave&text=".$this->Content."&servid=MES01");
	    //return $status;
	    	    	
	    if (!is_null($this->MobileNo) && !empty($this->MobileNo)){
		    
		$Content = $this->SmsTemplate_Get($this->Template_Id);
		    
		if (!is_null($Content) && !empty($Content)){
			$Content = $this->Template_Value_Replace($this->MobileNo, $this->Type, $Content);
			$Content = str_replace(' ', '+', $Content);
			$smsURL  = str_replace('<Content>', $Content, $this->smsURL);
			
			$arrlength = count($this->MobileNo);			
			for($x=0; $x<$arrlength; $x++){
			    $smsURL = str_replace('<Mobile_No>', $this->MobileNo[$x], $this->smsURL);
			    
			    $status = file_get_contents($smsURL);//$this->smsURL;
			    return $status;
			}
			
		    }
		else{
			return ErrCode::vfPin_Template_not_found;
		    }
		}
	    else{
		    return ErrCode::vfPin_InvalidPhone;
		}
	    
	    return $details;
	}
	public function MobileNo($arrValue)
        {
            $this->MobileNo = $arrValue;
        }
	    	
	public function Type($pType)
        {
	    $this->type = $pType;
	    switch ($pType) {
		case 'OTP':
		    $this->Template_Id = $this->Template_Id_OTP;
		    break;
	    }	
        }
	
	public function SmsTemplate_Get($Template_Id)
	{
	    try{
		$db = new DBTier();
		$sql = 'select Content from SMS_template where SMS_Template_Id = :SMS_Template_Id';
				
		$db->query($sql);
		$db->bind(':SMS_Template_Id', $Template_Id);	    
		$db->execute();// or die(mysql_error());
		$result = $db->single();
			
		$db->destroy();
		
		return $result['Content'];	
	    }
	    catch(PDOException $e)
	    {
		return '';
	    }
	}
	
	public function Template_Value_Replace($Mobile_No, $Type, $Content)
	{
	    switch($Type){
		case 'OTP':
		    $Pin = $this->Pin_Code_Generate($Mobile_No, $Type);
		    if (!is_null($Pin)){
			$Content = str_replace('<PIN>', $Pin, $Content);			
		    }
	    }
	    
	    return $Content;
	}
	
	public function Pin_Code_Generate($Mobile_No, $Type)
	{
            $db = new DBTier();	 
	    $db->query("CALL Pin_Code_Create(:Mobile_No, :Type, @Pin)");
	  
	    $db->bind(':Mobile_No', $Mobile_No);
            $db->bind(':Type', $Type);
            $db->execute();
            
            $db->query('select @Pin');
            $result = $db->single();
            $db->destroy();
            
            return $result['@Pin'];
        }
        

//	<<Checking used>>
//
//	public function Pin_Code_Get($Login_Id, $Mobile_No, &$Auth_Token)
//        {        
//            try{
//                $Err = new ErrCode();
//                $db = new DBTier();
//		// Temp commented, pending for sms service; $sql = 'SELECT COUNT(*) FROM Pin_Code where Mobile_No = :Mobile_No';
//                $sql = 'select  member_session.Mobile_No, member.login_id, member_session.Auth_Token
//			from member_session
//			left join Member
//			on member.Member_Id = member_session.member_id
//			where Member.Login_Id = :Login_Id and member_session.Mobile_No = :Mobile_No';
//                $dbCon = $db->getConnection();
//		$stmt = $dbCon->prepare($sql);
//                $stmt->bindParam(':Login_Id', $Login_Id, PDO::PARAM_STR);
//		$stmt->bindParam(':Mobile_No', $Mobile_No, PDO::PARAM_STR);
//                $stmt->execute();// or die(mysql_error());
//		$result = $stmt->fetch();
//                $dbCon = null;		
//                  
//                if (!is_null($result['Auth_Token'])){
//                    $Auth_Token = $result['Auth_Token'];
//                    return ErrCode::Gen_Success;
//                }
//                else{
//                    return ErrCode::Gen_RecordNotFound;
//                }                		
//            }
//            catch(PDOException $e){
//		return $e->getMessage();
//	    }
//        }
        
	//public function Pin_Send($Mobile_No, $Purpose_Type)
	//{
	//    
	//}	
    }   
    
?>