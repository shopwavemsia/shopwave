<?php
    //include dirname(__FILE__) . '/Shopwave_Common.php';
    
    define("Config", "Config.ini");
        
    class DBTier //extends PDO
    {
        private $config = Config;      
        private $dbh;
        
        
        private $dsn;
        private $user;
        private $pass;
        private $options;
        
        public function __construct()
        {                                    
            try{
                $Util->logging('__construct');
                $settings       = parse_ini_file($this->config, TRUE);
                $host           = $settings['DB_setting']['db_Host'];
                $dbname         = $settings['DB_setting']['db_Name'];
                $this->user     = $settings['DB_setting']['db_User'];
                $this->pass     = $settings['DB_setting']['db_Pass'];
            
                // Set DSN                     
                $this->dsn = 'mysql:host=' . $host . ';dbname=' . $dbname;
                // Set options
                $this->options = array(
                    PDO::ATTR_PERSISTENT    => true,
                    PDO::ATTR_ERRMODE       => PDO::ERRMODE_EXCEPTION
                );    
            }
            catch (Exception $e){
                $Util = new Util();
                $Util->logging('[DBTier->Construct]'.$e->getMessage());
            }
            catch (PDOException $e) {
                $Util = new Util();
                $Util->logging('[DBTier->Construct]'.$e->getMessage());                
            }                       
        }
        public function __destruct()
        {
            try {                
                $this->dbh = null; //Closes connection
            } catch (PDOException $e) {
                $Util = new Util();
                $Util->logging('[DBTier->Destruct]'.$e->getMessage());
            }
        }
     
        public function connect()
        {
            try{                
                $this->dbh = new PDO($this->dsn, $this->user, $this->pass, $this->options);
                return true;
            }
            catch(PDOException $e){                
                $GLOBALS['Util']->logging($e->getMessage());
                return false;
            }            
        }
        
        public function destroy()
        {           
            $this->dbh = null;
        }
        
        public function lastInsertId()
        {
            return $this->dbh->lastInsertId();
        }
        public function query($query)
        {
            $this->stmt = $this->dbh->prepare($query);
        }
        
        public function bind($param, &$value, $type = null, $IsOutput = null)
        {
            if (is_null($type))
            {
                switch (true) {
                    case is_int($value):
                        $type = PDO::PARAM_INT;
                        break;
                    case is_bool($value):
                        $type = PDO::PARAM_BOOL;
                        break;
                    case is_null($value):
                        $type = PDO::PARAM_NULL;
                        break;
                    default:
                        $type = PDO::PARAM_STR;
                }
            }
            
            if (!is_null($IsOutput) && $IsOutput == 1){
                $type = PDO::PARAM_STR|PDO::PARAM_OUTPUT;
            }
            
            $this->stmt->bindValue($param, $value, $type);
        }
        
        public function bindParam($param, $value, $Input, $len)
        {
            if ($Input == 1){
                //$type = PDO::PARAM_STR|PDO::PARAM_INPUT;
                $this->stmt->bindParam($param, $value, PDO::PARAM_STR|PDO::PARAM_INPUT_OUTPUT, $len);
            }
            else{
               // $type= PDO::PARAM_STR|PDO::PARAM_OUTPUT;
                $this->stmt->bindParam($param, $value, PDO::PARAM_STR|PDO::PARAM_OUTPUT, $len);
            }
            //$this->stmt->bindparam($param, $value, $type, $len);
        }
        public function execute()
        {
            return $this->stmt->execute();
        }
        
        public function resultset()
        {
            $this->execute();
            return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        public function single()
        {
            $this->execute();
            return $this->stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        public function rowCount()
        {
            return $this->stmt->rowCount();
        }
        
        public function beginTransaction()
        {
            return $this->dbh->beginTransaction();
        }
        
        public function endTransaction()
        {
            return $this->dbh->commit();
        }
        
        public function cancelTransaction()
        {
            return $this->dbh->rollBack();
        }
        
        public function debugDumpParams()
        {
            return $this->stmt->debugDumpParams();
        }           
    }
       
?>