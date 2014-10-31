<?php
    include dirname(__FILE__) . '/Shopwave_Common.php';
    
    define("Config", "Config.ini");
        
    class DBTier //extends PDO
    {
        private $config = Config;      
        private $dbh;
        private $error_str;
    
        
        public function __construct()
        {                      
            $settings   = parse_ini_file($this->config, TRUE);
            $host       = $settings['DB_setting']['db_Host'];
            $dbname     = $settings['DB_setting']['db_Name'];
            $user       = $settings['DB_setting']['db_User'];
            $pass       = $settings['DB_setting']['db_Pass'];
            
            // Set DSN                     
            $dsn = 'mysql:host=' . $host . ';dbname=' . $dbname;
            // Set options
            $options = array(
                PDO::ATTR_PERSISTENT    => true,
                PDO::ATTR_ERRMODE       => PDO::ERRMODE_EXCEPTION
            );
            // Create a new PDO instanace
            try{
                $this->dbh = new PDO($dsn, $user, $pass, $options);
            }
            // Catch any errors
            catch(PDOException $e){
                $this->error_str = $e->getMessage();
                $this->dbh = null;

            }
        }
        public function getDB()
        {
            return $this->dbh;
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