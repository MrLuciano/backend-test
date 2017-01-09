<?php

/*
 * Programa de API para Backend test da Catho.
 * 
 * Autor: Luciano Cesar Marinho
 * 
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Expose-Headers: x-authorization');
header('Access-Control-Allow-Headers: origin, content-type, accept, x-authorization, set-cookie, cookie');
// Allow cookie credentials because we're on the same domain
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');//POST, GET, OPTIONS, PUT, DELETE');
header('Access-Control-Max-Age: 86400');
header("Content-Type: application/json; charset=utf-8");


$api = new Vagas();
$api->qryString = filter_input(INPUT_SERVER,'QUERY_STRING');
//$api->busca($this->qryString());
http_response_code(200);
exit($api->busca($api->qryString));

/**
 * Classe Vagas para a api de busca em json
 * Exemplo de url de acesso: 
 * http://localhost/api.php?texto=texto&cidade=cidade&salOrd=asc|desc
 * 
 * @author Luciano C. Marinho
 */
class Vagas {
    
    //nome do arquivo de vagas
    private $nomeArq = "vagas.json";
    
    //query string a tratar
    private $qryString;
    
    //methodo a atender
    private $method;
    
    //url
    private $url;
        
    /**
     * Construtor
     */
    public function __construct() {
        //$this->method = $_SERVER['REQUEST_METHOD'];
        $this->method = filter_input(INPUT_SERVER, 'REQUEST_METHOD', FILTER_SANITIZE_STRING );
        //$this->url = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $this->method = filter_input(INPUT_SERVER, 'HTTP_HOST') . filter_input(INPUT_SERVER, 'REQUEST_URI');
        $this->setArquivoJson("vagas.json");
    }
    
    
    /**
     * Setter generico
     */
    public function __set($property, $value){ 
        if(property_exists($this, $property)) {
            $this->$property = $value; 
        }
    }
    /**
     * Getter generico
     */
    public function __get($property) { 
        if (property_exists($this, $property)) {
            return $this->$property;
        } 
    }
    
    /**
     * Retorna true somente para os casos permitidos, 501 ou 400 para os outros
     * casos.
     * 
     * @return boolean
     */
    protected function validaUso() {
        //Tratar somente GET
        if ($this->method <> 'GET') {
            http_response_code(501); //Not Implemented
            return false;
        }

        if (filter_var($this->url, FILTER_VALIDATE_URL, FILTER_FLAG_QUERY_REQUIRED) === false) {
            http_response_code(400); //bad request
            return false;
        }
    }
    
    /**
     * Troca arquivo json, padrao e vagas.json
     */
    public function setArquivoJson($arq = "vagas.json") {
        $this->nomeArq = $arq;
    }
    
    /**
     * Prepara a entrada de 'sujeiras' 
     */
    protected function test_str_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        $data = filter_var($data, FILTER_SANITIZE_STRING);
        return $data;
    }

    /**
     * Tira acentuacao
     */
    protected function tirarAcentos($string) {
        return preg_replace(array("/(á|à|ã|â|ä)/", "/(Á|À|Ã|Â|Ä)/", "/(é|è|ê|ë)/", "/(É|È|Ê|Ë)/", "/(í|ì|î|ï)/", "/(Í|Ì|Î|Ï)/", "/(ó|ò|õ|ô|ö)/", "/(Ó|Ò|Õ|Ô|Ö)/", "/(ú|ù|û|ü)/", "/(Ú|Ù|Û|Ü)/", "/(ñ)/", "/(Ñ)/"), explode(" ", "a A e E i I o O u U n N"), $string);
    }

    /**
    * Funcao de Comparacao
    * compara dois objetos de vagas pelo salario ascendente
     * 
     * @param array $a
     * @param array $b
     * @return int
     */
    protected function compVagasSalA(array $a, array $b) {
        if ($a['salario'] < $b['salario']) {
            return -1;
        } else if ($a['salario'] > $b['salario']) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * Funcao de Comparacao
     *
     * compara dois objetos de vagas pelo salario descendente
     * 
     * @param array $a
     * @param array $b
     * @return int
     */
    protected function compVagasSalD(array $a, array $b) {
        if ($a['salario'] > $b['salario']) {
            return -1;
        } else if ($a['salario'] < $b['salario']) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * executa a busca a partir da queryString passada
     * @param $queryStr string de query do comando GET
     * 
     */
    public function busca($queryStr) {
        
        if (strlen($queryStr) < 9) { //tamanho minimo de uma busca
            if (isset($this->qryString) && strlen($this->qryString) > 7) {
                $queryStr = $this->qryString;
            }
            else return;
        }
        parse_str($queryStr);
        $texto = filter_input(INPUT_GET, 'texto', FILTER_SANITIZE_SPECIAL_CHARS);
        $cidade = filter_input(INPUT_GET, 'cidade', FILTER_SANITIZE_SPECIAL_CHARS);
        $salOrd = filter_input(INPUT_GET, 'salOrd', FILTER_SANITIZE_SPECIAL_CHARS);

        //limpa as entradas e as coloca em minusculas
        $texto = strtolower($this->test_str_input($texto));
        $cidade = strtolower($this->test_str_input($cidade));
        $salOrd = strtolower($this->test_str_input($salOrd));//opcional

        //remove acentuacao para facilitar/melhorar resultado da busca
        $texto = $this->tirarAcentos($texto);
        $cidade = $this->tirarAcentos($cidade);
        $salOrd = strtolower($salOrd);
        
        //verificar se salOrd está na query e se é asc ou desc
        if (strlen($salOrd) > 0 && !($salOrd == 'asc' || $salOrd == 'desc')) {
            http_response_code(400); //bad request
            return false;
        }
        
        if (!file_exists($this->nomeArq)) {
            http_response_code(412);//Arquivo de vagas.json nao encontrado.
            return;
        }
        $entrada = file_get_contents($this->nomeArq);
        $jsonIn = json_decode($entrada, true);
        $saida = array();
        foreach ($jsonIn->docs as $item) {
            //procurar nos itens titulo e descricao pelo texto dado
            //iniciar por texto de tamanho 3 (arbitrario)
            if ( strlen($texto) > 2 && 
                 (stripos(tirarAcentos($item->title), $texto) || 
                  stripos(tirarAcentos($item->description), $texto))) {
                array_push($saida, $item);
            }
            //procurar no item cidade pela cidade dada
            //iniciar por texto de tamaho 3 (arbitrario)
            if ( strlen($cidade) > 2 && 
                 stripos(tirarAcentos($item->title), $cidade) ) {
                array_push($saida, $item);
            }
        }
        
        if (array_count_values($saida) > 0) {
            //montar a saida como ascendente ou descendente, conforme parametro
            if (strlen($salOrd) > 0 && $salOrd == 'desc') {
                // funcao de comparacao de objetos no json de entrada modo descendente
                uasort($saida, 'compVagasSalD');
            } else {
                // funcao de comparacao de objetos no json de entrada modo ascendente
                uasort($saida, 'compVagasSalA');
            }
        }
        //prepara array de retorno como json
        $jsonOut = json_encode($saida);
        
        return $jsonOut;
    }

}

?>
