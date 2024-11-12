<?php
require_once DOL_DOCUMENT_ROOT.'/conf/conf.php';
//Clase que me permite acceder a BBDD
class DB
{


    private $host;
    protected  $dbname;
    private $user;
    private $pass;
    protected $db;    

    public $filas = array();   

    public function __construct($base, $user, $host, $pass)
    {
        $this->dbname = $base;  //Al instanciar el objeto DB establecemos la BBDD en la que vamos a trabajar
		$this->user = $user;
		$this->host = $host;
		$this->pass = $pass;  
    }

    private function Conectar()
    {

        try {
            $this->db = new PDO("mysql:host=$this->host;dbname=$this->dbname;port=3307", $this->user, $this->pass);   
            $this->db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);    
            $this->db->exec("set names utf8mb4");
        } catch (PDOException $e) {                                          

            echo  "  <p>Error: No puede conectarse con la base de datos.</p>\n\n";
            echo  "  <p>Error: " . $e->getMessage() . "</p>\n";
        }
    }
    private function Cerrar()
    {
        $this->db = NULL;
    }

    public function ConsultaSimple($consulta, $param)
    {
        $this->Conectar();
        $sta = $this->db->prepare($consulta);
        if (!$sta->execute($param)) {
            echo "Error al ejecutar la consulta";
        }


        $this->Cerrar();
    }
    public function ConsultaDatos($consulta, $param)
    {
        $this->Conectar();
        
        $sta = $this->db->prepare($consulta);

        if (!$sta->execute($param)) {
            echo "Error al ejecutar la consulta";
        } else 
        {
            $this->filas = array();  

            foreach ($sta as $fila) 
            {
                $this->filas[] = $fila;
            }
        }

        $this->Cerrar();
    }
}

?>