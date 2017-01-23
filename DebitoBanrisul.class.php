<?php 

/*
*	Descrição:  Classe para geração e leitura de arquivos retorno debito em conta Banrisul.
*				Padrão FEBRABAN
* 	Autor: Thiago R. Gham
* 	Versão: 0.1	 01-04-2015
	
	LAYOUT DOS RESGISTROS                                                          
	REGISTRO "A" - HEADER                                                          
	REGISTRO "B" - CADASTRAMENTO DE DÉBITO AUTOMÁTICO                              
	REGISTRO "C" - OCORRÊNCIAS NO CADASTRAMENTO DO DÉBITO AUTOMÁTICO               
	REGISTRO "D" - ALTERAÇÃO DA IDENTIFICAÇÃO DO CLIENTE NA EMPRESA                
	REGISTRO "E" - DÉBITO EM CONTA CORRENTE                                        
	REGISTRO "F" - RETORNO DO DÉBITO AUTOMÁTICO                                    
	REGISTRO "H" - OCORRÊNCIA DE ALTERAÇÃO DA IDENTIFICAÇÃO DO CLIENTE NA EMPRESA   
	REGISTRO "X" - RELAÇÃO DE AGÊNCIAS                                              
	REGISTRO "Z" - TRAILLER   
	
	X = ALFANUMÉRICO 9 = NUMÉRICO V = VÍRGULA DECIMAL ASSUMIDA                                              
*/

define('VERSAO_LAYOUT', '05');
define('CD_MOEDA', '03');
define('SERVICO', 'DEBITO AUTOMATICO');
define('C_MOVIMENTO_EXCLUIR', 1);
define('C_MOVIMENTO_INCLUIR', 2);
define('D_MOVIMENTO_ALTERAR', 0);
define('D_MOVIMENTO_EXCLUIR', 1);
define('E_MOVIMENTO_DEBITAR', 0);
define('E_MOVIMENTO_CANCELAR', 1);

class DebitoBanrisul{
	
	private $STRING = '';
	static $CD_RETORNO_BANCO =  array('00' => 'Débito Efetuado',
									  '01' => 'Débito não Efetuado - Sem Fundos',
									  '02' => 'Débito não Efetuado - Conta corrente não Cadastrada',
									  '04' => 'Débito não Efetuado - Outras Restrições',
									  '05' => 'Débito não Efetuado - Valor do débito exede valor limite aprovado',
									  '10' => 'Débito não Efetuado - Agência em regime de encerramento',
									  '12' => 'Débito não Efetuado - Valor Inválido',
									  '13' => 'Débito não Efetuado - Data de Lançamento inválida',
									  '14' => 'Débito não Efetuado - Agência inválida',
									  '15' => 'Débito não Efetuado - Conta Corrente inválida',
									  '18' => 'Débito não Efetuado - Data do débito anterior à do processamento',
									  '19' => 'Débito não Efetuado - Agência/Conta não pertence ao CPF/CNPJ informado',
									  '20' => 'Débito não Efetuado - Conta corrente conjunta não solidária',
									  '30' => 'Débito não Efetuado - Sem contrato de Débito Automático',
									  '31' => 'Débito Efetuado em data diferente da data informada - feriado na preça de débito',
									  '96' => 'Manutenção do Cadastro',
									  '97' => 'Cancelamento - Não encontrado',
									  '98' => 'Cancelamento - Não efetuado fora do tempo hábil',
									  '99' => 'Cancelamento - Cancelado conforme solicitação');
	/*
	*	Descrição: Armazena linhas do registro.
	* 	@param string $linha String contendo a linha.
	* 	@return string $codigo
	*/
	public function descricaoRetornoBanco($codigo) {
		return DebitoBanrisul::$CD_RETORNO_BANCO[$codigo];
		//return $this->$CD_RETORNO_BANCO;
	}
	/*
	*	Descrição: Armazena linhas do registro.
	* 	@param string $linha String contendo a linha.
	* 	@return 
	*/
	function salvaArquivo($caminho) {
		return file_put_contents($caminho, $this->STRING);
	}
	/*
	*	Descrição: Executa o processamento de todo o arquivo, linha a linha
	* 	@param fiel $file Arquivo a ser processado
	* 	@return array Retorna um vetor contendo os dados das linhas do arquivo.*/
	function lerArquivoRetorno($file = NULL){
		$a_linhas = array();
		if($arq = file($file)) {
			foreach($arq as $linha) {
				$a_linhas[] = $linha;
			}
		}else{ 
			throw new Exception('Não foi possível abrir o arquivo '.$file);
		}
		return $a_linhas;
	}
	/*
	*	Descrição: Processa uma linha do arquivo de retorno.
	* 	@param string $linha String contendo a linha a ser processada
	* 	@return array Retorna um vetor associativo contendo os valores_linha processada.
	*/
	function processarLinha($linha) {
		if(trim($linha) == '') die('A linha está vazia.');
		/*Identificação do Registro*/
		$processar = 'processar'.substr($linha,0,1);
		return $this->$processar($linha);
	}
	/*
	*	Descrição: Armazena linhas do registro.
	* 	@param string $linha String contendo a linha.
	* 	@return 
	*/
	private function escreveLinha($linha) {
		$this->STRING .= "$linha\r\n";
	}
	/*
	*	Descrição: HEADER
	* 	@param 
	* 	@return string Linha.
	*/
	function registroA($cd_convenio, $nm_empresa, $cd_banco, $nm_banco, $dt_geracao, $NSA, $extra = ''){
		$this->escreveLinha('A1'
							.$this->formataCampoNumerico($cd_convenio,5)
							.$this->espacosBrancos(15)
							.$this->formataCampoString($nm_empresa,20)
							.$this->formataCampoNumerico($cd_banco,3)
							.$this->formataCampoString($nm_banco,20)
							.$this->formataCampoNumerico($dt_geracao,8)
							.$this->formataCampoNumerico($NSA,6)
							.$this->formataCampoNumerico(VERSAO_LAYOUT,2)
							.$this->formataCampoString(SERVICO,17)
							.$this->formataCampoString($extra,52));
	}
	/*
	*	Descrição: 	OCORRÊNCIAS NO CADASTRAMENTO DO DÉBITO AUTOMÁTICO
	*				cd_movimento - 1: Exclusão cliente 2: inclusão cliente 
	*				Somente para registros retornoados em B enviados pelo Banco não gerar para aceitos
	* 	@param 
	* 	@return string Linha.
	*/
	function registroC($cd_pessoa, $cd_agencia, $cd_ctacor, $motivo_recusa, $complemento_recusa, $cd_movimento){
		$this->escreveLinha('C'
							.$this->formataCampoString($nm_pessoa,25)
							.$this->formataCampoString($cd_agencia,4)
							.$this->formataCampoString($cd_ctacor.$this->digitoConta($cd_ctacor),10)
							.$this->espacosBrancos(4)
							.$this->formataCampoString($motivo_recusa,40)
							.$this->formataCampoString($complemento_recusa,40)
							.$this->espacosBrancos(25)
							.$this->formataCampoNumerico($cd_movimento,1));
	}
	
	/*
	*	Descrição: 	ALTERAÇÃO DA IDENTIFICAÇÃO DO CLIENTE NA EMPRESA
	*				cd_movimento - 0: Alteração cliente 1:Exclusão cliente 
	* 	@param 
	* 	@return string Linha.
	*/
	function registroD($cd_pessoa_old, $cd_agencia, $cd_ctacor, $cd_pessoa_new, $motivo_recusa, $cd_movimento){
		$this->escreveLinha('D'
							.$this->formataCampoString($cd_pessoa_old,25)
							.$this->formataCampoString($cd_agencia,4)
							.$this->formataCampoString($cd_ctacor.$this->digitoConta($cd_ctacor),10)
							.$this->espacosBrancos(4)
							.$this->formataCampoString($cd_pessoa_new,25)
							.$this->formataCampoString($motivo_recusa,60)
							.$this->espacosBrancos(20)
							.$this->formataCampoNumerico($cd_movimento,1));
	}
	
	/*
	*	Descrição: 	DÉBITO EM CONTA CORRENTE
	*				cd_movimento - 0: Débito Normal 1: Cancelamento do enviado anteriormente 
	* 	@param 
	* 	@return string Linha.
	*/
	function registroE($cd_pessoa, $cd_agencia, $cd_ctacor, $dt_vencto, $vl_titulo, $cd_bloqueto, $cpf_cnpj, $cd_movimento){
		$this->escreveLinha('E'
							.$this->formataCampoString($cd_pessoa,25)
							.$this->formataCampoString($cd_agencia,4)
							.$this->formataCampoString($cd_ctacor.$this->digitoConta($cd_ctacor),10)
							.$this->espacosBrancos(4)
							.$this->formataCampoNumerico($dt_vencto,8)
							.$this->formataCampoNumerico($vl_titulo,15)
							.$this->formataCampoString(CD_MOEDA,2)
							.$this->formataCampoString($cd_bloqueto,60)
							.$this->formataCampoNumerico($this->identificacaoCPFCNPJ($cpf_cnpj),1)
							.$this->formataCampoNumerico($cpf_cnpj,15)
							.$this->espacosBrancos(4)
							.$this->formataCampoNumerico($cd_movimento,1));
	}
	/*
	*	Descrição: 	TRAILLER
	* 	@param 
	* 	@return string Linha.
	*/
	function registroZ($nr_registros, $vl_total, $extra = ''){
		$this->escreveLinha('Z'
							.$this->formataCampoNumerico(($nr_registros+1),6)
							.$this->formataCampoNumerico($vl_total,17)
							.$this->formataCampoString($extra,126));
	}
	/*
	*	Descrição: Processa a linha header do arquivo
	* 	@param string $linha Linha do header de arquivo processado
	* 	@return array Retorna um vetor contendo os dados dos campos do header do arquivo.
	*/
	private function processarA($linha) {
		$vlinha = array();																														
		$vlinha["cd_registro"]     = substr($linha, 0, 1); 	 		//X Identificação do Registro Header: A
		$vlinha["cd_remessa"]      = substr($linha, 1, 1); 	 		//9 Tipo de Remessa: 1 ou 2
		$vlinha["cd_convenio"]     = substr($linha, 2, 5); 	 		//9 Codigo do Convenio: 
		$vlinha["nm_empresa"]      = trim(substr($linha, 22, 20)); 	//X Nome da Empresa: Websul Telecom 
		$vlinha["cd_banco"]        = substr($linha, 42, 3);  		//9 Codigo Banco: 041
		$vlinha["nm_banco"]        = trim(substr($linha, 45, 20)); 	//X Nome Banco: BANRISUL
		$vlinha["dt_geracao"]      = $this->formataData(substr($linha, 65, 8)); //9 Data da Geração: YYYYMMDD
		$vlinha["nr_sequencia"]    = trim(substr($linha, 73, 6));  	//9 Número sequencial do arquivo NSA: 000001
		$vlinha["nr_versao"]       = substr($linha, 79, 2);  		//9 Versão do Layout: 05
		$vlinha["id_servico"]      = trim(substr($linha, 81, 17)); 	//X Identificação do Serviço: DÉBITO AUTOMATICO
		$vlinha["reservado"]       = trim(substr($linha, 98, 52)); 	//X Reservado para o Futuro.
		return $vlinha;
	}
	/*
	* 	Descrição: Processa a linha do Regitro B
	* 	@param string $linha Linha do arquivo processado
	* 	@return array Retorna um vetor contendo os dados dos campos do arquivo.
	*/
	private function processarB($linha) {
		$vlinha = array();																														
		$vlinha["cd_registro"]     = substr($linha, 0, 1); 	 		//X Identificação do Registro: B
		$vlinha["cd_pessoa"]       = trim(substr($linha, 1, 25));	//X Identificação da Pessoa: 5575
		$vlinha["cd_agencia"]      = trim(substr($linha, 26, 4));   //X Codigo da Agencia para o Débito: 0575 
		$vlinha["cd_ctacor"]       = trim(substr($linha, 30, 10));  //X Codigo da Conta Corrente da Pessoa: 0123456789
		$vlinha["dt_movimento"]    = $this->formataData(substr($linha, 44, 8)); //9 Data do Movimento: YYYYMMDD
		$vlinha["reservado"]       = trim(substr($linha, 52, 97)); //X Reservado para o Futuro.
		$vlinha["cd_movimento"]    = substr($linha, 149, 1);	   //9 Codigo do Movimento: 1 - Exclusão 2- Inclusão
		return $vlinha;
	}
	/*
	* 	Descrição: Processa a linha do Regitro C
	* 	@param string $linha Linha do arquivo processado
	* 	@return array Retorna um vetor contendo os dados dos campos do arquivo.
	*/
	private function processarC($linha) {
		$vlinha = array();																														
		$vlinha["cd_registro"]    = substr($linha, 0, 1); 	 	  //X Identificação do Registro: C
		$vlinha["cd_pessoa"]      = trim(substr($linha, 1, 25));  //X Identificação da Pessoa: 5575
		$vlinha["cd_agencia"]     = trim(substr($linha, 26, 4));  //X Codigo da Agencia para o Débito: 0575 
		$vlinha["cd_ctacor"]      = trim(substr($linha, 30, 10)); //X Codigo da Conta Corrente da Pessoa: 0123456789
		$vlinha["ocorrencia1"]    = trim(substr($linha, 44, 40)); //X Mensagem explicativa da recusa
		$vlinha["ocorrencia2"]    = trim(substr($linha, 84, 40)); //X Complemtento Mensagem explicativa da recusa
		$vlinha["reservado"]      = trim(substr($linha, 124, 25));//X Reservado para o Futuro.
		$vlinha["cd_movimento"]   = substr($linha, 149, 1); 	  //9 Codigo do Movimento: 1 - Exclusão 2- Inclusão
		return $vlinha;
	}
	/*
	* 	Descrição: Processa a linha do Regitro C
	* 	@param string $linha Linha do arquivo processado
	* 	@return array Retorna um vetor contendo os dados dos campos do arquivo.
	*/
	private function processarD($linha) {
		$vlinha = array();																														
		$vlinha["cd_registro"]    = substr($linha, 0, 1); 	 	  //X Identificação do Registro: D
		$vlinha["cd_pessoa_old"]  = trim(substr($linha, 1, 25));  //X Identificação da Pessoa: 5575
		$vlinha["cd_agencia"]     = trim(substr($linha, 26, 4));  //X Codigo da Agencia para o Débito: 0575 
		$vlinha["cd_ctacor"]      = trim(substr($linha, 30, 10)); //X Codigo da Conta Corrente da Pessoa: 0123456789
		$vlinha["cd_pessoa_new"]  = trim(substr($linha, 44, 25)); //X Identificação da Pessoa: 5575
		$vlinha["ocorrencia"]     = trim(substr($linha, 69, 60)); //X Mensagem explicativa da recusa
		$vlinha["reservado"]      = trim(substr($linha, 129, 20));//X Reservado para o Futuro.
		$vlinha["cd_movimento"]   = substr($linha, 149, 1); 	  //9 Codigo do Movimento: 0 - Alteração 1- Exclusão
		return $vlinha;
	}
	/*
	* 	Descrição: Processa a linha do Regitro E
	* 	@param string $linha Linha do arquivo processado
	* 	@return array Retorna um vetor contendo os dados dos campos do arquivo.
	*/
	//EThiago Rodrigues TESTE   94308033862077    2015051000000000000599003106776                                                      2000022222222222    0
	private function processarE($linha) {
		$vlinha = array();																													
		$vlinha["cd_registro"]     	= substr($linha, 0, 1); 	 		//X Identificação do Registro: F
		$vlinha["cd_pessoa"]       	= rtrim(substr($linha, 1, 25));		//X Identificação da Pessoa: 5575
		$vlinha["cd_agencia"]      	= rtrim(substr($linha, 26, 4));   	//X Codigo da Agencia para o Débito: 0575 
		$vlinha["cd_ctacor"]       	= rtrim(substr($linha, 30, 10));  	//X Codigo da Conta Corrente da Pessoa: 0123456789
		$vlinha["dt_debito"]       	= $this->formataData(substr($linha, 44, 8)); 	//9 Data do Vencimento: YYYYMMDD
		$vlinha["vl_debito"]       	= $this->formataNumero(substr($linha, 52, 15)); //9 Valor a ser debitado.
		$vlinha["cd_retorno"]      	= rtrim(substr($linha, 67, 2)); 	//X Codigo Moeda: 03 real.
		$vlinha["cd_bloqueto"]     	= rtrim(substr($linha, 69, 59));	//X Reservado para o Futuro.
		$vlinha["identificacao"]	= rtrim(substr($linha, 129, 1));	   	//9 Codigo de Identificação: 1 - CNPJ 2- CPF
		$vlinha["cpf_cnpj"]	        = rtrim(substr($linha, 130, 15));	   	//9 CNPJ
		if($vlinha["identificacao"] == '2') $vlinha["cpf_cnpj"] = substr($linha, 133, 15);//9 CPF
		$vlinha["reservado"]        = rtrim(substr($linha, 145, 4)); //X Reservado para o Futuro.
		$vlinha["cd_movimento"]    	= rtrim(substr($linha, 149, 1));	   	//9 Codigo do Movimento: 0 - Débito Normal 1- Cancelamento (exclusão) lançamento enviado antes.
		return $vlinha;
	}
	/*
	* 	Descrição: Processa a linha do Regitro F
	* 	@param string $linha Linha do arquivo processado
	* 	@return array Retorna um vetor contendo os dados dos campos do arquivo.
	*/
	private function processarF($linha) {
		$vlinha = array();																													
		$vlinha["cd_registro"]     	= substr($linha, 0, 1); 	 		//X Identificação do Registro: F
		$vlinha["cd_pessoa"]       	= rtrim(substr($linha, 1, 25));		//X Identificação da Pessoa: 5575
		$vlinha["cd_agencia"]      	= rtrim(substr($linha, 26, 4));   	//X Codigo da Agencia para o Débito: 0575 
		$vlinha["cd_ctacor"]       	= rtrim(substr($linha, 30, 10));  	//X Codigo da Conta Corrente da Pessoa: 0123456789
		$vlinha["dt_debito"]       	= $this->formataData(substr($linha, 44, 8)); 	//9 Data do Vencimento: YYYYMMDD
		$vlinha["vl_debito"]       	= $this->formataNumero(substr($linha, 52, 15)); //9 Valor a ser debitado.
		$vlinha["cd_retorno"]      	= rtrim(substr($linha, 67, 2)); 	//X Codigo Moeda: 03 real.
		$vlinha["tx_ocorrencia"]   	= $this->descricaoRetornoBanco($vlinha["cd_retorno"]);
		$vlinha["cd_bloqueto"]     	= rtrim(substr($linha, 69, 59));	//X Reservado para o Futuro.
		$vlinha["identificacao"]	= rtrim(substr($linha, 129, 1));	   	//9 Codigo de Identificação: 1 - CNPJ 2- CPF
		$vlinha["cpf_cnpj"]	        = rtrim(substr($linha, 130, 15));	   	//9 CNPJ
		if($vlinha["identificacao"] == '2') $vlinha["cpf_cnpj"] = substr($linha, 133, 15);//9 CPF
		$vlinha["reservado"]        = rtrim(substr($linha, 145, 4)); //X Reservado para o Futuro.
		$vlinha["cd_movimento"]    	= rtrim(substr($linha, 149, 1));	   	//9 Codigo do Movimento: 0 - Débito Normal 1- Cancelamento (exclusão) lançamento enviado antes.
		return $vlinha;
	}
	/*
	* 	Descrição: Processa a linha do Regitro H
	* 	@param string $linha Linha do arquivo processado
	* 	@return array Retorna um vetor contendo os dados dos campos do arquivo.
	*/
	private function processarH($linha) {
		$vlinha = array();																														
		$vlinha["cd_registro"]    = substr($linha, 0, 1); 	 	  //X Identificação do Registro: H
		$vlinha["cd_pessoa_old"]  = trim(substr($linha, 1, 25));  //X Identificação da Pessoa: 5575
		$vlinha["cd_agencia"]     = trim(substr($linha, 26, 4));  //X Codigo da Agencia para o Débito: 0575 
		$vlinha["cd_ctacor"]      = trim(substr($linha, 30, 10)); //X Codigo da Conta Corrente da Pessoa: 0123456789
		$vlinha["cd_pessoa_new"]  = trim(substr($linha, 44, 25)); //X Identificação da Pessoa: 5575
		$vlinha["ocorrencia"]     = trim(substr($linha, 69, 58)); //X Mensagem explicativa da recusa
		$vlinha["reservado"]      = trim(substr($linha, 127, 22));//X Reservado para o Futuro.
		$vlinha["cd_movimento"]   = trim(substr($linha, 149, 1)); 	  //9 Codigo do Movimento: 0 - Alteração 1- Exclusão
		return $vlinha;
	}
	/*
	* 	Descrição: Processa a linha do Regitro X
	* 	@param string $linha Linha do arquivo processado
	* 	@return array Retorna um vetor contendo os dados dos campos do arquivo.
	*/
	private function processarX($linha) {
		$vlinha = array();																														
		$vlinha["cd_registro"]    = substr($linha, 0, 1); 	 	  //X Identificação do Registro: H
		$vlinha["cd_agencia"]     = trim(substr($linha, 1,   4));  //X Identificação da Pessoa: 5575
		$vlinha["nm_agencia"]     = trim(substr($linha, 5,   30));  //X Codigo da Agencia para o Débito: 0575 
		$vlinha["endereco"]       = trim(substr($linha, 35,  30)); //X Codigo da Conta Corrente da Pessoa: 0123456789
		$vlinha["numero"]         = trim(substr($linha, 65,  5)); //X Identificação da Pessoa: 5575
		$vlinha["cep"]	          = trim(substr($linha, 70,  5)); //X Mensagem explicativa da recusa
		$vlinha["s_cep"]          = trim(substr($linha, 75,  3));//X Reservado para o Futuro.
		$vlinha["cidade"]         = trim(substr($linha, 78,  20)); 	  //9 Codigo do Movimento: 0 - Alteração 1- Exclusão
		$vlinha["cd_estado"]      = trim(substr($linha, 98,  2));//X Reservado para o Futuro.
		$vlinha["situacao"]       = trim(substr($linha, 100, 1));//X Reservado para o Futuro.
		$vlinha["reservado"]      = trim(substr($linha, 101, 49));//X Reservado para o Futuro.
		return $vlinha;
	}
	/*
	* 	Descrição: Processa a linha do Regitro Z
	* 	@param string $linha Linha do arquivo processado
	* 	@return array Retorna um vetor contendo os dados dos campos do arquivo.
	*/
	private function processarZ($linha) {
		$vlinha = array();																												
		$vlinha["cd_registro"]     = substr($linha, 0, 1); 	 		//X Identificação do Registro: Z
		$vlinha["total_registros"] = trim(substr($linha, 1, 6));	//9 Somatorio dos registros linhas do arquivo
		$vlinha["vl_total"]	       = trim(substr($linha, 7, 17));   //9 Valor total dos registros 
		$vlinha["reservado"]       = trim(substr($linha, 24, 126));	//X Reservado para o Futuro.
		return $vlinha;
	}
	/*
	*	Formata uma string com zeros.
	* 	@param $numero numero de zeros.
	* 	@return string Retorna zeros.
	*/
	private function identificacaoCPFCNPJ($valor){
		return strlen($valor) <= 11 ? 2 : 1; 
	}
	/*
	*	Gera código de Conta Corrente Banrisul.
	* 	@param $ctacor conta corrente EENNNNNNCD.
	* 	@return integer Retorna check-digit (módulo 11) Código de Conta Corrente.
	*/
	function digitoConta($ctacor){
		$somatorio    = 0;
		$a_padrao     = array(3,2,4,7,6,5,4,3,2);
		$a_cd_ctacor  = str_split($ctacor);
		for($x = 0; $x < 9; $x++){
			$somatorio += $a_padrao[$x] * $a_cd_ctacor[$x];
		}	
		$resto = 11 - gmp_strval( gmp_div_r($somatorio, 11));
		switch($resto){
			case 0:
				break;
			case 1:
				$resto = 1;
				break;
			default:
				break;
		}
		return $resto;
	}
	/*
	*	Descrição: Formata uma string, contendo um valor real (float) sem o separador de decimais, para a sua correta representação real.
	* 	@param string $valor String contendo o valor na representação usada nos arquivos de retorno do banco, sem o separador de decimais.
	* 	@param int $numCasasDecimais Total de casas decimais do número representado em $valor.
	* 	@return float Retorna o número representado em $valor, no seu formato float, contendo o separador de decimais.
	*/
	function formataNumero($valor, $numCasasDecimais = 2) {
		if($valor == '') return 0;
		if($numCasasDecimais > 0) {
			$valor = substr($valor, 0, strlen($valor)-$numCasasDecimais) . "." . substr($valor, strlen($valor)-$numCasasDecimais, $numCasasDecimais);
			$valor = (float)$valor;
		}else 
			$valor = (int)$valor;
		return $valor;
	}
	/*
	*	Formata uma string, contendo uma data sem o separador.
	* 	@param string $data String contendo a data no formato YYYYMMDD.
	* 	@return string Retorna a data non formato YYYY-MM-DD.
	*/
	function formataData($data) {
		if($data == '') return '';
		$data = substr($data, 0, 4).'-'.substr($data, 4, 2).'-'.substr($data, 6, 2);
		return date("Y-m-d", strtotime($data));
	}
	/*
	*	Formata uma string com espaços em branco.
	* 	@param $numero numero de espaços.
	* 	@return string Retorna espaços em branco.
	*/
	private function formataCampoString($vl_campo, $tamanho){
		if (strlen($vl_campo) >= $tamanho) {
			$vl_valor =	substr($vl_campo,0,$tamanho);
		}else{
			$vl_valor = $vl_campo.$this->espacosBrancos($tamanho - strlen($vl_campo));
		}
		return $vl_valor;
	}
	/*
	*	Formata uma string com espaços em branco.
	* 	@param $numero numero de espaços.
	* 	@return string Retorna espaços em branco.
	*/
	private function formataCampoNumerico($vl_campo, $tamanho){
		if (strlen($vl_campo) >= $tamanho) {
			$vl_valor =	substr($vl_campo,0,$tamanho);
		}else{
			$vl_valor = $this->zeros($tamanho - strlen($vl_campo)).$vl_campo;
		}
		return $vl_valor;
	}
	/*
	*	Formata uma string com espaços em branco.
	* 	@param $numero numero de espaços.
	* 	@return string Retorna espaços em branco.
	*/
	private function espacosBrancos($numero) {
		return str_repeat(" ",$numero);
	}
	/*
	*	Formata uma string com zeros.
	* 	@param $numero numero de zeros.
	* 	@return string Retorna zeros.
	*/
	private function zeros($numero) {
		return str_repeat('0',$numero);
	}
}




















