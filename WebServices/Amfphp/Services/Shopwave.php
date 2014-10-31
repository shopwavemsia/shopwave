<?php
    include dirname(__FILE__) . '/../ShopwaveClasses/Shopwave_Member.php';
    include dirname(__FILE__) . '/../ShopwaveClasses/Shopwave_SMS.php';    
   include dirname(__FILE__) . '/../ShopwaveClasses/Shopwave_DBTier.php';
    
    DBTier $G_DBCON;
    MSGLOG $M;
    
    class Shopwave {
        
        //Params = $Login_Id, $Mobile_No, $Auth_Token
        //Sample = {"Login_Id":"ss.pang@gmail.com","Mobile_No":"0123910924","Auth_Token":"9509c33693d88f6277bdd103b7f6e154"}
        
   
        
        function Verify_Auth_Token($Params)
        {
            $G_DBCON = new DBTier();
            openlogfile();
            
            $M->print_msg("===Verify_Auth_Token called ====",VERBOSE);
            if (gDB->get_DB== null) return DB_error
            
                
            $Member = new Member();
            
            $Login_Id = $Params->Login_Id;
            $Mobile_No = $Params->Mobile_No;
            $Auth_Token = $Params->Auth_Token;
            print_msg("===Verify_Auth_Token: ".$Login_Id. ."" ."====",OPTIMIZED);
            
            $Code = $Member->Member_Verify_AuthToken($Login_Id, $Mobile_No, $Auth_Token, $Member_Session_Id);
            
            If ($Code != ErrCode::Gen_Success) {
                $Auth_Token = '';
            }
            
            return array('Code' => $Code, 'Auth_Token'=>$Auth_Token);
        }
       
        //Params = $Login_Id, $Mobile_No, $PIN, $Type
        //Sample = {"Login_Id":"ss.pang@gmail.com","Mobile_No":"0123910924","PIN":"1111","Type":"FB"}
        function Verify_Pin($Params)
        {
            $Member = new Member();
            
            $Login_Id = $Params->Login_Id;
            $Mobile_No = $Params->Mobile_No;
            $PIN = $Params->PIN;
            $Type = $Params->Type;     
            $Result = $Member->MemberCount_Get($Login_Id, $Mobile_No);
           
            
            //if ($Result == 'C2' || $Result == 'C4')
            //{
            //    
            //}
            //if($Result == 'C3')
            //{
            //                
            //}
            //if($Result == 'C1')
            //{
                IF ($PIN == '1111')
                {
                    //return $PIN;
                    //Get Information
                   
                    //$Auth_Token = $Member->Auth_Token_Get //select and check with case, return 1/0
                    
                    IF ($Member->Auth_Token_Get($Login_Id, $Mobile_No, $Auth_Token) == ErrCode::Gen_Success){
                        $data = array('Code' => ErrCode::Gen_Success, 'Auth_Token' => $Auth_Token, 'Point'=> 0,
                                      'Qualifying_Point'=>0, 'Next_Qualifying_Point'=>0, 'Rank'=>1, 'Member_Code'=>$Login_Id);
                        Return $data;
                    }
                    else{
                        Return array('Code' => ErrCode::Gen_RecordNotFound);
                    }
                    
                    
                }
                else{
                    return array('Code' => ErrCode::vfAuth_ExpiredToken);
                }
            //}
        }
            
        
        //Params = $Login_Id,$Password,$PasswordSalt,$Email,$Gender,$First_Name,
        //                        $Last_Name,$Race,$Religion,$DOB,$Mobile_No,$Identity_No,$Nationality,$Prefer_Lang,$Is_Complete,
        //                        $Status,$Social_ID_Ref,$Social_Type,$Social_Access_Token,
        //                        $Mo_Brand,$Mo_Model,$Mo_IMEI,$Mo_OS,$Mo_w_BLE,$Mo_w_NFC,$Mo_w_GPS,$Mo_w_Camera,$Updated_By,$Created_By
        //Sample = {"Login_Id":"ss.pang@gmail.com","Password":"","PasswordSalt":"","Email":"ss.pang@gmail.com","Gender":"M","First_Name":"saw","Last_Name":"seng","Race":"C","Religion":"","DOB":"1981-01-01","Mobile_No":"0123910924","Identity_No":"123","Nationality":"","Prefer_Lang":"","Is_Complete":"","Status":"","Social_ID_Ref":"qqq111www","Social_Type":"FB","Social_Access_Token":"222ss","Mo_Brand":"Asus","Mo_Model":"Nexus 7","Mo_IMEI":"","Mo_OS":"Android 4","Mo_w_BLE":"1","Mo_w_NFC":"1","Mo_w_GPS":"1","Mo_w_Camera":"1"}
        function Verify_Member($Params)
        {        
            $Member = new Member();
            
            if($Params->Login_Id==''){return array('Code' => ErrCode:: vfMem_MLogin);}
            if($Params->Email==''){return array('Code' => ErrCode:: vfMem_MEmail);}
            if($Params->Gender==''){return array('Code' => ErrCode:: vfMem_MGender);}
            if($Params->First_Name==''){return array('Code' => ErrCode:: vfMem_MFName);}
            if($Params->Last_Name==''){return array('Code' => ErrCode:: vfMem_MLName);}
                 
            $Result = $Member->MemberCount_Get($Params->Email, $Params->Mobile_No);
                 
            if ($Result == 'C2' || $Result == 'C4'){
                //C2 = Login_Id not existed, Mobile existed            
                //C4 = Login_Id & Mobile not existed
                //Go to Register_New_Member
            
                $Result1 = $Member->RegisterNewMember($Params->Login_Id,$Params->Password,$Params->PasswordSalt,$Params->Email,$Params->Gender,$Params->First_Name,
                                $Params->Last_Name,$Params->Race,$Params->Religion,$Params->DOB,$Params->Mobile_No,$Params->Identity_No,$Params->Nationality,$Params->Prefer_Lang,$Params->Is_Complete,
                                $Params->Status,$Params->Social_ID_Ref,$Params->Social_Type,$Params->Social_Access_Token,
                                $Params->Mo_Brand,$Params->Mo_Model,$Params->Mo_IMEI,$Params->Mo_OS, (int)$Params->Mo_w_BLE,(int)$Params->Mo_w_NFC,(int)$Params->Mo_w_GPS,(int)$Params->Mo_w_Camera, $Member_Id);
                
                if ($Result1 == ErrCode::Gen_Success)
                {                   
                    //$sms = new SMS();
                    //$sms->MobileNo($Params->Mobile_No);
                    ////$sms->Content('Welcome to shopwave.');
                    //$sms->Sent();
                    Request_OTP($Params->Mobile_No);
                }
                
                return array('Code' => $Result1);//$Result1;
                                
            }
            
            if($Result == 'C3' || $Result == 'C1'){
                //C3 = Login_Id existed, Mobile not existed
                //Go to Update_Member
                $Result1 = $Member->UpdateMember($Params->Login_Id,$Params->Password,$Params->PasswordSalt,$Params->Email,$Params->Gender,$Params->First_Name,
                                $Params->Last_Name,$Params->Race,$Params->Religion,$Params->DOB,$Params->Mobile_No,$Params->Identity_No,$Params->Nationality,$Params->Prefer_Lang,$Params->Is_Complete,
                                $Params->Status,$Params->Social_ID_Ref,$Params->Social_Type,$Params->Social_Access_Token,
                                $Params->Mo_Brand,$Params->Mo_Model,$Params->Mo_IMEI,$Params->Mo_OS, (int)$Params->Mo_w_BLE,(int)$Params->Mo_w_NFC,(int)$Params->Mo_w_GPS,(int)$Params->Mo_w_Camera, $Member_Id);
                
                return array('Code' => $Result1);
            }       
            
        }
      
        
      
      
      
      
      
      //<<Under Development>>
      
        function Request_OTP($Mobile_No){
            
            $SMS = new SMS();
            $SMS->MobileNo($Mobile_No);
            $SMS->Type('OTP');
            
            return $SMS->Sent();
            //return $SMS->Pin_Code_Generate($Mobile_No,'REGISTRATION');
        }
        
        
        
        //function Send_SMS($Mobile_No, $Content){
        //    $Content = str_replace(' ', '+', $Content);
        //    //$details=file_get_contents("https://www.etracker.cc/mes/mesbulk.aspx?user=mktest69&pass=pos1511adsb&type=0&to=60123910924&from=macrokiosk&text=Welcome+to+MACROKIOSK&servid=MES01");
        //    $details = file_get_contents("https://www.etracker.cc/mes/mesbulk.aspx?user=mktest69&pass=pos1511adsb&type=0&to=".$Mobile_No."&from=Shopwave&text=".$Content."&servid=MES01");
        //    //$details = file_get_contents("https://www.etracker.cc/mes/mesbulk.aspx?user=mktest69&pass=pos1511adsb&type=5&to=".$Mobile_No."&from=Shopwave&text=4e00&servid=MES01");
        //    return $details;
        //}
      //
      //  function Get_Ip(){
      //      $ip = '121.121.21.130';//$_SERVER['REMOTE_ADDR'];
      //      $details = json_decode(file_get_contents("http://ipinfo.io/{$ip}/json"));
      //      //http://freegeoip.net/json/121.121.21.130
      //      return $details;//->city; // -> "Mountain View"       
      //  }
      //  
      //  function Get_Distance(){
      //      $lat1 = 3.0471;
      //      $lon1 = 101.6294;
      //      //3.1276398,101.6454086
      //      $lat2 = 3.0471;
      //      $lon2 = 101.6294;
      //      $unit = 'K';
      //      //return this->distance($lat1, $lon1, $lat2, $lon2, $unit);
      //  }
      //  
      //  
      //
      //  function distance($lat1, $lon1, $lat2, $lon2, $unit) {
        //    $lat1 = 3.0471;
        //    $lon1 = 101.6294;
        //    $lat2 = 3.1276398;
        //    $lon2 = 101.6454086;
        //    $unit = 'M';
        //    
        //    $theta = $lon1 - $lon2;
        //
        //    $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        //            
        //    $dist = acos($dist);
        //
        //    $dist = rad2deg($dist);
        //
        //    $miles = $dist * 60 * 1.1515;
        //
        //    $unit = strtoupper($unit);
        //
        //    //return $dist.', '.$miles.', '.$unit;
        //    if ($unit == "K") {
        //        return ($miles * 1.609344);
        //
        //    } else if ($unit == "N") {
        //
        //    return ($miles * 0.8684);
        //
        //    } else {
        //
        //        return $miles;
        //
        //}
       
            
    }




?>