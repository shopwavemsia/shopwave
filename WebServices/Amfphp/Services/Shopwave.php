<?php
    include dirname(__FILE__) . '/../ShopwaveClasses/Shopwave_Member.php';
    include dirname(__FILE__) . '/../ShopwaveClasses/Shopwave_SMS.php';    
   include dirname(__FILE__) . '/../ShopwaveClasses/Shopwave_DBTier.php';
    include dirname(__FILE__) . '/../ShopwaveClasses/Shopwave_DBTier.php';
    include dirname(__FILE__) . '/../ShopwaveClasses/Shopwave_Common.php';
       
    $GLOBALS['DBTier']  = new DBTier();
    $GLOBALS['SMS']     = new SMS(); 
    $GLOBALS['Util']    = new Util();
    
    DBTier $G_DBCON;
    MSGLOG $M;
    
    class Shopwave {
        
    class Shopwave {            
                
        //Params = $Login_Id, $Mobile_No, $Auth_Token
        //Sample = {"Login_Id":"ss.pang@gmail.com","Mobile_No":"60123910924","Auth_Token":"8817a7b57d7593981297ddbc04879e39"}
        function Verify_Auth_Token($Params)
        {
            $this->logging('[Verify_Auth_Token]'.'Login_Id:'.$Params->Login_Id.', Mobile_No:'.$Params->Mobile_No, true);
            if ($GLOBALS['Util']->Debugger_Log())
                $this->logging('Params:'.json_encode($Params));
                
            if ($GLOBALS['DBTier']->connect()){
                $Member = new Member();
                $Login_Id = $Params->Login_Id;
                $Mobile_No = $Params->Mobile_No;
                $Auth_Token = $Params->Auth_Token;
                
                If (!$Member->Member_Verify_AuthToken($Login_Id, $Mobile_No, $Auth_Token, $Member_Session_Id, $Code)) {
                    $Auth_Token = '';
                }
                $GLOBALS['DBTier']->destroy();
                $this->logging('Code:'. json_encode(array('Code' => $Code, 'Auth_Token'=>$Auth_Token)));
                return array('Code' => $Code, 'Auth_Token'=>$Auth_Token);
            }
            else{
                $this->logging('Code:'.ErrCode::Gen_DB_Connection_Failed);
                return array('Code' => ErrCode::Gen_DB_Connection_Failed);
            }                        
        }
       
        //Params = $Login_Id, $Mobile_No, $PIN, $Type
        //Sample = {"Login_Id":"amingyuen78@gmail.com","Mobile_No":"60126680335","PIN":"03UU36","Type":"FB"}
        function Verify_Pin($Params)
        {
            $this->logging('[Verify_Pin]'.'Login_Id:'.$Params->Login_Id.', Mobile_No:'.$Params->Mobile_No, true);
            if ($GLOBALS['Util']->Debugger_Log())
                $this->logging('Params:'.json_encode($Params));
            if ($GLOBALS['DBTier']->connect()){
                
                $Member = new Member();           
            
                $Login_Id = $Params->Login_Id;
                $Mobile_No = $Params->Mobile_No;
                $PIN = $Params->PIN;
                $Type = $Params->Type;     
                //$Result = $Member->MemberCount_Get($Login_Id, $Mobile_No);
                               
                $vfPin_Code = $Member->vsPin_Code($Mobile_No, $PIN, $Pin_Code_Id, $Code);
                
                if ($vfPin_Code==true){
                    IF ($Member->Auth_Token_Get($Login_Id, $Mobile_No, $Auth_Token, $Code)){
                        $data = array('Code' => ErrCode::Gen_Success, 'Auth_Token' => $Auth_Token, 'Point'=> 0,
                                      'Qualifying_Point'=>0, 'Next_Qualifying_Point'=>0, 'Rank'=>1, 'Member_Code'=>$Login_Id);
                        
                        if($Member->Pin_Update($Pin_Code_Id, $Code)){
                            $GLOBALS['DBTier']->destroy();
                            
                            $this->logging('Code:'.json_encode($data));    
                            Return $data;    
                        }
                        else{
                            $this->logging('Code:'.$Code);    
                            Return array('Code' => $Code);
                        }                        
                    }
                    else{
                        $this->logging('[Verify_Pin->Auth_Token_Get]Code:'.$Code);                        
                        Return array('Code' => $Code);
                    }
                }
                else{
                    $this->logging('[Verify_Pin->vsPin_Code]Code:'.$Code);
                    Return array('Code' => $Code);
                }    
            }
            else{
                $this->logging('Code:'.ErrCode::Gen_DB_Connection_Failed);
                return array('Code' => ErrCode::Gen_DB_Connection_Failed);
            }            
        }
            
        
        //Params = $Login_Id,$Password,$PasswordSalt,$Email,$Gender,$First_Name,
        //                        $Last_Name,$Race,$Religion,$DOB,$Mobile_No,$Identity_No,$Nationality,$Prefer_Lang,$Is_Complete,
        //                        $Status,$Social_ID_Ref,$Social_Type,$Social_Access_Token,
        //                        $Mo_Brand,$Mo_Model,$Mo_IMEI,$Mo_OS,$Mo_w_BLE,$Mo_w_NFC,$Mo_w_GPS,$Mo_w_Camera,$Updated_By,$Created_By
        //Sample = {"Login_Id":"ss.pang@gmail.com","Password":"","PasswordSalt":"","Email":"ss.pang@gmail.com","Gender":"M","First_Name":"saw","Last_Name":"seng","Race":"C","Religion":"","DOB":"1981-01-01","Mobile_No":"60123910924","Identity_No":"123","Nationality":"","Prefer_Lang":"","Is_Complete":"","Status":"","Social_ID_Ref":"qqq111www","Social_Type":"FB","Social_Access_Token":"222ss","Mo_Brand":"Asus","Mo_Model":"Nexus 7","Mo_IMEI":"","Mo_OS":"Android 4","Mo_w_BLE":"1","Mo_w_NFC":"1","Mo_w_GPS":"1","Mo_w_Camera":"1"}
        //         {"Login_Id":"mingyuen78@gmail.com","Password":"","PasswordSalt":"","Email":"mingyuen78@gmail.com","Gender":"male","First_Name":"Ming","Last_Name":"Yuen","Race":"","Religion":"","DOB":"1978-04-02","Mobile_No":"60126680335","Identity_No":"","Nationality":"Malaysia","Prefer_Lang":"en-US","Is_Complete":"","Status":"","Social_ID_Ref":"727236017369338","Social_Type":"FB","Social_Access_Token":"","Mo_Brand":"","Mo_Model":"","Mo_IMEI":"","Mo_OS":"","Mo_w_BLE":"0","Mo_w_NFC":"0","Mo_w_GPS":"0","Mo_w_Camera":"0"}
        function Verify_Member($Params)
        {
            $this->logging('[Verify_Member]'.'Login_Id:'.$Params->Login_Id.', Email:'.$Params->Email.', Gender:'.$Params->Gender.
                                      ', First_Name:'.$Params->First_Name.', Last_Name:'.$Params->Last_Name, true);
            if ($this->Debugger_Log())
                $this->logging('Params:'.json_encode($Params));
            
            $Member = new Member();
            $Code   = null;
            if      ($Params->Login_Id=='')     {$Code = ErrCode:: vfMem_Mandatory_Login;}
            elseif  ($Params->Email=='')        {$Code = ErrCode:: vfMem_Mandatory_Email;}
            elseif  ($Params->Gender=='')       {$Code = ErrCode:: vfMem_Mandatory_Gender;}
            elseif  ($Params->First_Name=='')   {$Code = ErrCode:: vfMem_Mandatory_FName;}
            elseif  ($Params->Last_Name=='')    {$Code = ErrCode:: vfMem_Mandatory_LName;}
            
            if (!is_null($Code) && !$Code = ''){
                $GLOBALS['Util']->logging('Mandatory field validation failed. Code:'.$Code);
                return array('Code' => $Code);
            }
            
        
            if($GLOBALS['DBTier']->connect()){
                $Result = $Member->MemberCount_Get($Params->Email, $Params->Mobile_No, $Code);
                             
                if ($Result == 'C2' || $Result == 'C4'){
                    //C2 = Login_Id not existed, Mobile existed            
                    //C4 = Login_Id & Mobile not existed
                    //Go to Register_New_Member
                
                    $Result1 = $Member->RegisterNewMember($Params->Login_Id,$Params->Password,$Params->PasswordSalt,$Params->Email,$Params->Gender,$Params->First_Name,
                                    $Params->Last_Name,$Params->Race,$Params->Religion,$Params->DOB,$Params->Mobile_No,$Params->Identity_No,$Params->Nationality,$Params->Prefer_Lang,$Params->Is_Complete,
                                    $Params->Status,$Params->Social_ID_Ref,$Params->Social_Type,$Params->Social_Access_Token,
                                    $Params->Mo_Brand,$Params->Mo_Model,$Params->Mo_IMEI,$Params->Mo_OS, (int)$Params->Mo_w_BLE,(int)$Params->Mo_w_NFC,(int)$Params->Mo_w_GPS,(int)$Params->Mo_w_Camera, $Member_Id, $Code);
                                    
                    if ($Result1){                                      
                        $smsSent_Status = $this->Request_OTP($Params->Mobile_No, 'REGISTRATION');                    
                    }
                    $GLOBALS['DBTier']->destroy();
                    
                    $this->logging('[Verify_Member->RegisterNewMember]. Code:'.$smsSent_Status);                                              
                    return array('Code' => $smsSent_Status);//$Code);//$Result1;
                                    
                }
                
                elseif($Result == 'C3' || $Result == 'C1'){
                    //C3 = Login_Id existed, Mobile not existed
                    //C1 = Login_Id existed, Mobile existed
                    //Go to Update_Member
                    $Result1 = $Member->UpdateMember($Params->Login_Id,$Params->Password,$Params->PasswordSalt,$Params->Email,$Params->Gender,$Params->First_Name,
                                    $Params->Last_Name,$Params->Race,$Params->Religion,$Params->DOB,$Params->Mobile_No,$Params->Identity_No,$Params->Nationality,$Params->Prefer_Lang,$Params->Is_Complete,
                                    $Params->Status,$Params->Social_ID_Ref,$Params->Social_Type,$Params->Social_Access_Token,
                                    $Params->Mo_Brand,$Params->Mo_Model,$Params->Mo_IMEI,$Params->Mo_OS, (int)$Params->Mo_w_BLE,(int)$Params->Mo_w_NFC,(int)$Params->Mo_w_GPS,(int)$Params->Mo_w_Camera, $Member_Id, $Code);
                    
                    if ($Result == 'C1'){
                        IF ($Member->Auth_Token_Get($Params->Login_Id, $Params->Mobile_No, $Auth_Token, $Code)){
                            $data = array('Code' => ErrCode::Gen_Success, 'Auth_Token' => $Auth_Token, 'Point'=> 0,
                                          'Qualifying_Point'=>0, 'Next_Qualifying_Point'=>0, 'Rank'=>1, 'Member_Code'=>$Params->Login_Id);
                            //$Member->Pin_Update($Pin_Code_Id, $Code);
                            $GLOBALS['DBTier']->destroy();
                            
                            $this->logging('[Verify_Member->Auth_Token_Get]'.'Code:'.json_encode($data));
                            Return $data;
                        }
                        else{
                            $GLOBALS['DBTier']->destroy();
                            $this->logging('[Verify_Member->Auth_Token_Get]'.'Code:'.$Code);
                            if ($this->Debugger_Log())
                                $this->logging($Long_Msg);
                            Return array('Code' => $Code);
                        }
                    }
                    else{
                        $GLOBALS['DBTier']->destroy();
                        $this->logging('[Verify_Member->UpdateMember]'.'Code:'.$Code);
                        return array('Code' => $Code);    
                    }
                    
                }
                else {
                    if (!is_null($Code)) {
                        $this->logging('[Verify_Member->MemberCount_Get]'.'Code:'.$Code);
                        Return array('Code' => $Code);
                    }
                }    
            }
            else{
                $this->logging('Code:'.ErrCode::Gen_DB_Connection_Failed);
                return array('Code' => ErrCode::Gen_DB_Connection_Failed);
            }                         
        }
              
        function Request_OTP($Mobile_No, $Purpose_Type)
        {            
            $SMS = $GLOBALS['SMS'];
            
            $SMS->MobileNo($Mobile_No);
            $SMS->Type($Purpose_Type);
            
            $SentResult = $SMS->Sent();
            $SMS = null;
            
            return $SentResult;
        }
      

        function logging($Content, $is_start=false)
        {
            $GLOBALS['Util']->logging($Content, $is_start);
        }
        
        function Debugger_log()
        {
            $GLOBALS['Util']->Debugger_Log();
        }
//      <<Alex's Testing>>
        public function Get_Outlet($param) {
            $ret = new stdClass();
            $ret->Code = "00000";
            $ret->Outlets = array();
            
            $item = new stdClass();
            $item->Id = 1;
            $item->Name = "Giant Spectrum Mall Ampang";
            $item->Address = "Lot 12G, Jalan Cerdas, Taman Connaught, 56000, Kuala Lumpur, Wilayah Persekutuan";
            $item->Latitude = 3.111111;
            $item->Longitude = 101.12222;
            $item->Distance = 1.2;
            $item->Description = "This is a descriptive text about this outlet named ".$item->Name;
            $item->PromotionAvailable = 1;
            $item->Picture = "outlet/banner/1.jpg";
            $item->Rewards = array();
    
            $item->Rewards[0] = new stdClass();
            $item->Rewards[0]->Type = "WALKIN";
            $item->Rewards[0]->RewardPoint = 1;
            $item->Rewards[0]->Id = 101;
            
            $item->Rewards[1] = new stdClass();
            $item->Rewards[1]->Type = "TAGIT";
            $item->Rewards[1]->RewardPoint = 1;
            $item->Rewards[1]->Id = 102;
            
            $item->Rewards[2] = new stdClass();
            $item->Rewards[2]->Type = "GRABIT";
            $item->Rewards[2]->RewardPoint = 2;
            $item->Rewards[2]->Id = 103;
            
            $item->Rewards[3] = new stdClass();
            $item->Rewards[3]->Type = "DISCOVERIT";
            $item->Rewards[3]->RewardPoint = 2;
            $item->Rewards[3]->Id = 104;
            
            $item->CatalogId = 1000;
            $item->Catalog_Products = array();
            
            $item->Catalog_Products[0] = new stdClass();
            $item->Catalog_Products[0]->Promo_Catalog_Id = 1002;
            $item->Catalog_Products[0]->Name = "Durban Mens Wear";
            $item->Catalog_Products[0]->DisplayPrice = true;
            $item->Catalog_Products[0]->Description = "Mens wear sweater for office. Selected items only. Up to 50% discount.";
            $item->Catalog_Products[0]->Category = "Fashion and Wears";
            $item->Catalog_Products[0]->Picture = "outlet/product/1002.jpg";
            $item->Catalog_Products[0]->Price = 79.9;
    
            $item->Catalog_Products[1] = new stdClass();
            $item->Catalog_Products[1]->Promo_Catalog_Id = 1005;
            $item->Catalog_Products[1]->Name = "MNG Women Wear";
            $item->Catalog_Products[1]->DisplayPrice = false;
            $item->Catalog_Products[1]->Description = "MNG latest fashion clothing for women up for grabs, up to 70% discount!.";
            $item->Catalog_Products[1]->Category = "Fashion and Wears";
            $item->Catalog_Products[1]->Picture = "outlet/product/1005.jpg";
            $item->Catalog_Products[1]->Price = 0;
    
            $item->Catalog_Products[2] = new stdClass();
            $item->Catalog_Products[2]->Promo_Catalog_Id = 1003;
            $item->Catalog_Products[2]->Name = "Designer White Chair";
            $item->Catalog_Products[2]->DisplayPrice = true;
            $item->Catalog_Products[2]->Description = "Designer white chair, made of faux leather and stainless steel stand.";
            $item->Catalog_Products[2]->Category = "Furniture and Fittings";
            $item->Catalog_Products[2]->Picture = "outlet/product/1003.jpg";
            $item->Catalog_Products[2]->Price = 39.9;
    
            $item->Catalog_Products[3] = new stdClass();
            $item->Catalog_Products[3]->Promo_Catalog_Id = 1004;
            $item->Catalog_Products[3]->Name = "Gucci Leather Summer Bags";
            $item->Catalog_Products[3]->DisplayPrice = true;
            $item->Catalog_Products[3]->Description = "Gucci leather summer collection bags. Available colors red and white up for grabs.";
            $item->Catalog_Products[3]->Category = "Fashion and Wears";
            $item->Catalog_Products[3]->Picture = "outlet/product/1004.jpg";
            $item->Catalog_Products[3]->Price = 129.9;
    
            $item->Catalog_Products[4] = new stdClass();
            $item->Catalog_Products[4]->Promo_Catalog_Id = 1000;
            $item->Catalog_Products[4]->Name = "Nescafe 3-in-1 Buy 1 free 1";
            $item->Catalog_Products[4]->DisplayPrice = false;
            $item->Catalog_Products[4]->Description = "The all new nescafe 3-in-1 is now on special promotion only for limited time only. Buy 3 free one for only RM 10.90.";
            $item->Catalog_Products[4]->Category = "Food and Beverage";
            $item->Catalog_Products[4]->Picture = "outlet/product/1000.jpg";
            $item->Catalog_Products[4]->Price = 0;
    
            $item->Catalog_Products[5] = new stdClass();
            $item->Catalog_Products[5]->Promo_Catalog_Id = 1001;
            $item->Catalog_Products[5]->Name = "Ayam Brands Sardin Can";
            $item->Catalog_Products[5]->DisplayPrice = true;
            $item->Catalog_Products[5]->Description = "Ayam Brands Sardin is only offering at RM 1.90 per can.";
            $item->Catalog_Products[5]->Category = "Food and Beverage";
            $item->Catalog_Products[5]->Picture = "outlet/product/1001.jpg";
            $item->Catalog_Products[5]->Price = 1.9;
    
            $ret->Outlets[0] = $item;
    
            $item2 = new stdClass();
            $item2->Id = 2;
            $item2->Name = "Tesco Ampang";
            $item2->Address = "PT 8880, Jalan Pandan Prima, Dataran Pandan Prima, 55100 Kuala Lumpur, Wilayah Persekutuan";
            $item2->Latitude = 3.1222211;
            $item2->Longitude = 101.133122;
            $item2->Distance = 2.1;
            $item2->Description = "This is a descriptive text about this outlet named ".$item2->Name;
            $item2->PromotionAvailable = 1;
            $item2->Picture = "outlet/banner/2.jpg";
            $item2->Rewards = array();
    
            $item2->Rewards[0] = new stdClass();
            $item2->Rewards[0]->Type = "WALKIN";
            $item2->Rewards[0]->RewardPoint = 2;
            $item2->Rewards[0]->Id = 201;
            
            $item2->Rewards[1] = new stdClass();
            $item2->Rewards[1]->Type = "TAGIT";
            $item2->Rewards[1]->RewardPoint = 1;
            $item2->Rewards[1]->Id = 202;
            
            $item2->Rewards[2] = new stdClass();
            $item2->Rewards[2]->Type = "GRABIT";
            $item2->Rewards[2]->RewardPoint = 1;
            $item2->Rewards[2]->Id = 203;
    
            $item2->CatalogId = 2000;
            $item2->Catalog_Products = array();
            
            $item2->Catalog_Products[0] = new stdClass();
            $item2->Catalog_Products[0]->Promo_Catalog_Id = 2000;
            $item2->Catalog_Products[0]->Name = "Ayam Brands Sardin Can";
            $item2->Catalog_Products[0]->DisplayPrice = true;
            $item2->Catalog_Products[0]->Description = "Ayam Brands Sardin is only offering at RM 2.90 per can.";
            $item2->Catalog_Products[0]->Category = "Food and Beverage";
            $item2->Catalog_Products[0]->Picture = "outlet/product/2000.jpg";
            $item2->Catalog_Products[0]->Price = 2.9;
    
            $item2->Catalog_Products[1] = new stdClass();
            $item2->Catalog_Products[1]->Promo_Catalog_Id = 2001;
            $item2->Catalog_Products[1]->Name = "Nescafe Mocha 150ml Can Drink";
            $item2->Catalog_Products[1]->DisplayPrice = true;
            $item2->Catalog_Products[1]->Description = "Nestle Nescafe Mocha Can Drink 150ml for only RM 1.90!";
            $item2->Catalog_Products[1]->Category = "Food and Beverage";
            $item2->Catalog_Products[1]->Picture = "outlet/product/2001.jpg";
            $item2->Catalog_Products[1]->Price = 1.9;
    
            $item2->Catalog_Products[2] = new stdClass();
            $item2->Catalog_Products[2]->Promo_Catalog_Id = 2002;
            $item2->Catalog_Products[2]->Name = "Mountain Dew 500ml Bottle";
            $item2->Catalog_Products[2]->DisplayPrice = true;
            $item2->Catalog_Products[2]->Description = "Mountain Dew standard 500ml bottle on promotion!";
            $item2->Catalog_Products[2]->Category = "Food and Beverage";
            $item2->Catalog_Products[2]->Picture = "outlet/product/2002.jpg";
            $item2->Catalog_Products[2]->Price = 2.1;
            
            $ret->Outlets[1] = $item2;
    
            $item3 = new stdClass();
            $item3->Id = 3;
            $item3->Name = "99 Speedmart Ampang";
            $item3->Address = "Jalan 12, Jalan Nanas, Taman Connaught, 56000, Kuala Lumpur, Wilayah Persekutuan";
            $item3->Latitude = 3.1422212;
            $item3->Longitude = 101.133122;
            $item3->Distance = 3.8;
            $item3->Description = "This is a descriptive text about this outlet named ".$item3->Name;
            $item3->PromotionAvailable = 0;
            $item3->Picture = "outlet/banner/3.jpg";
            $item3->Rewards = array();
    
            $item3->Rewards[0] = new stdClass();
            $item3->Rewards[0]->Type = "WALKIN";
            $item3->Rewards[0]->RewardPoint = 3;
            $item3->Rewards[0]->Id = 301;
            
            $item3->Rewards[1] = new stdClass();
            $item3->Rewards[1]->Type = "TAGIT";
            $item3->Rewards[1]->RewardPoint = 1;
            $item3->Rewards[1]->Id = 302;
            
            $item3->CatalogId = 3000;
            $item3->Catalog_Products = array();
            
            $ret->Outlets[2] = $item3;
    
            $item4 = new stdClass();
            $item4->Id = 4;
            $item4->Name = "Courts Mammoth Ampang";
            $item4->Address = "No. 1 & 2, Ukay Boulevard Jalan Lingkaran Tengah Dua, Hulu Kelang, Selangor, Ampang";
            $item4->Latitude = 3.1222212;
            $item4->Longitude = 101.233122;
            $item4->Distance = 4.2;
            $item4->Description = "This is a descriptive text about this outlet named ".$item4->Name;
            $item4->PromotionAvailable = 0;
            $item4->Picture = "outlet/banner/4.jpg";
            $item4->Rewards = array();
    
            $item4->Rewards[0] = new stdClass();
            $item4->Rewards[0]->Type = "WALKIN";
            $item4->Rewards[0]->RewardPoint = 1;
            $item4->Rewards[0]->Id = 401;
            
            $item4->Rewards[1] = new stdClass();
            $item4->Rewards[1]->Type = "TAGIT";
            $item4->Rewards[1]->RewardPoint = 1;
            $item4->Rewards[1]->Id = 402;
            
            $item4->Rewards[2] = new stdClass();
            $item4->Rewards[2]->Type = "DISCOVERIT";
            $item4->Rewards[2]->RewardPoint = 3;
            $item4->Rewards[2]->Id = 404;
    
            $item4->CatalogId = 4000;
            $item4->Catalog_Products = array();
            
            $ret->Outlets[3] = $item4;
    
            return $ret;
        }

        function Get_TaggableProduct($param) {
           // Accepts Parameter OutletID, AuthKey
           // Returns a Code with array list of taggable product inside the outlet. 
           $ret = new stdClass();
           $ret->Code = "00000";
           $ret->Products = array();

           $item = new stdClass();
           $item->Id = 10000;
           $item->Name = "Nescafe Latte Ice Coffee 250ml Can";
           $item->Picture = "outlet/product/10000.jpg";
           $item->Description = "A new product from Nestle Malaysia. Nescafe Latte with new packaging only priced at RM 2.00 per can. This price valid till 31st December 2014. Terms and condition applied.";
           $item->Price = 2;
           $item->MarketingURL = "http://www.nescafe.com/nescafelatte";
           $item->IsScanned = false;
           $item->PointPerScan = 2;
           $item->Category = "Food and Beverage";
           $item->Product_External_Code = "8812345678903";
           $ret->Products[0] = $item;

           $item2 = new stdClass();
           $item2->Id = 10001;
           $item2->Name = "Coke Social 330ml";
           $item2->Picture = "outlet/product/10001.jpg";
           $item2->Description = "The specially designed twin can twists off the top, allowing you to share it, perhaps making a new friend at the same time. ";
           $item2->Price = 2;
           $item2->MarketingURL = "http://www.coke.com/cokesocial";
           $item2->IsScanned = false;
           $item->PointPerScan = 2;
           $item2->Category = "Food and Beverage";
           $item2->Product_External_Code = "8812345678910";
           $ret->Products[1] = $item2;                

           $item3 = new stdClass();
           $item3->Id = 10002;
           $item3->Name = "Dettol Natural Soothing (Calendula and Chamomile)";
           $item3->Picture = "outlet/product/10002.jpg";
           $item3->Description = "New Dettol natural soothing body wash gives you the trusted Dettol protection plus nature's best for naturally, healthy skin. Now comes with Calendula and Chamomile scent.";
           $item3->Price = 8.9;
           $item3->MarketingURL = null;
           $item3->IsScanned = true;
           $item->PointPerScan = 2;
           $item3->Category = "Food and Beverage";
           $item3->Product_External_Code = "8812345678927";
           $ret->Products[2] = $item3;

           return $ret;
   }

        function Update_TaggableProduct($param) {
           // To mark this product has already scanned.
           // Accepts Parameter OutletID, ProductId, AuthKey
           // Returns Code;
           $ret = new stdClass();
           $ret->Code = "00000";
           return $ret;
        }
      
      
      //<<Under Development>>
        
        
      
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
       //function Test(){
       //     //return date_default_timezone_get();
       //     //return  date('Y/m/d H:i:s');
       //     $Member= new Member();
       //     if ($GLOBALS['DBTier']->connect()){
       //         return 'true';
       //         $result = $Member->TestGlobal('ss.pang@gmail.com');
       //         $GLOBALS['DBTier']->destroy();
       //         return $result;    
       //     }
       //     else{
       //         return 'false';
       //     }
       //     
       // 
       // 
       // }
       // 
       // function TestGlobal(){
       //     $Login_Id = 'ss.pang@gmail.com';
       //     
       //     $obj = $GLOBALS['DBTier'];
       //     //return $obj;
       //     //if (!$obj->connect()){
       //     //    return ErrCode::Gen_DB_Connection_Failed;
       //     //}
       //     //else {
       //         $obj->connect();
       //         
       //         $obj->query('select member_id from member where Login_Id=:Login_Id;');
       //         
       //         $obj->bind(':Login_Id', $Login_Id);
       //         
       //         $obj->execute();
       //         $rows=$obj->single();//(PDO::FETCH_ASSOC);// resultset();                       
       //         $obj->destroy();
       //               
       //         return $rows["member_id"];       
       //     //    return $obj;    
       //     //}
       //     
       // }
            
    }




?>