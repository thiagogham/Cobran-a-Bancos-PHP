<?php 

/* 
*	Descri��o:  Classe para gera��o e leitura de arquivos retorno remessa em conta Bradesco.
*				Padr�o FEBRABAN
* 	Autor: Thiago R. Gham
* 	Vers�o: 1.0	 21-03-2016
	
	LAYOUT DOS RESGISTROS                                                          
	Remessa: 
	Registro 0 - Header Label
	Registro 1 - Transa��o
	Registro 2 - Mensagem (opcional)
	Registro 3 - Rateio de Cr�dito (opcional)
	Registro 7 - Pagador Avalista (opcional) 
	Registro 9 - Trailler

	Retorno: 
	Registro 0 - Header Label
	Registro 1 - Transa��o
	Registro 3 - Rateio de Cr�dito (opcional)                     
	Registro 9 - Trailler

*/
/*
1 = Banco emite e Processa o registro.
2 = Cliente emite e o Banco  somente  processa  o  registro 
*/
define('TIPO_EMISSAO_DOC_BANCO', 1);
define('TIPO_EMISSAO_DOC_CLIENTE', 2);

/*
Identifica��es de Ocorr�ncia
01..Remessa
02..Pedido de baixa
03..Pedido de Protesto  Falimentar
04..Concess�o de abatimento
05..Cancelamento de abatimento concedido
06..Altera��o de vencimento
07..Altera��o do controle do participante
08..Altera��o de seu n�mero
09..Pedido de protesto
18..Sustar protesto e baixar T�tulo
19..Sustar protesto e manter em carteira
22..Transfer�ncia Cess�o cr�dito ID. Prod.10  
23..Transfer�ncia entre Carteiras
24..Dev. Transfer�ncia entre Carteiras
31..Altera��o de outros dados
045..Pedido de Negativa��o (NOVO)
046..Excluir Negativa��o com baixa (NOVO)
047..Excluir negativa��o e manter pendente (NOVO)
68..Acerto nos dados do rateio de Cr�dito
69..Cancelamento do rateio de cr�dito.
*/
define('OCORRENCIA_REMESSA','01');
define('OCORRENCIA_BAIXA'  ,'02');


/*
01 - Duplicata             
02 - Nota Promiss�ria    
03 - Nota de Seguro         
04 - Cobran�a Seriada   
05 - Recibo                 
10 - Letras de C�mbio 
11 - Nota de D�bito   
12 - Duplicata de Serv.  
30 - Boleto de Proposta
99 - Outros 
*/
define('ESPECIE_DOC', '99');

class Bradesco{
	
	public $debug   = false;

	private $STRING = '';

	static $CD_RETORNO_BANCO =  array('02' => 'Entrada Confirmada',
									  '03' => 'Entrada rejeitada',
									  '06' => 'Liquida��o normal',
									  '08' => 'Baixa por pagamento, liquida��o pelo saldo',
									  '09' => 'Devolu��o autom�tica',
									  '10' => 'Baixado conforme instru��es da Ag�ncia',
									  '12' => 'Abatimento Concedido (sem motivo)',
									  '15' => 'Liquida��o em Cart�rio (sem motivo)',
									  '16' => 'T�tulo Pago em Cheque � Vinculado',
									  '17' => 'Liquida��o ap�s baixa ou T�tulo n�o registrado (sem motivo)',
									  '22' => 'T�tulo Com Pagamento Cancelado',
									  '27' => 'Baixa Rejeitada',
									  '30' => 'Altera��o de Outros Dados Rejeitados',
									  '32' => 'Instru��o Rejeitada',
									  '40' => 'Estorno de pagamento');
	/* 
	*	Ocorr�ncia = 02 - Entrada confirmada 
	*	Ocorr�ncia = 03 - Entrada Rejeitada
	*/
	static $CD_MOTIVO_02_03 =  array( '00' => 'Ocorr�ncia aceita',
									  '01' => 'C�digo do Banco inv�lido',
									  '02' => 'C�digo do registro detalhe inv�lido',
									  '04' => 'C�digo do movimento n�o permitido para a carteira',
									  '05' => 'C�digo de ocorr�ncia n�o num�rico',
									  '07' => 'Ag�ncia/Conta/d�gito inv�lidos',
									  '08' => 'Nosso n�mero inv�lido',
									  '10' => 'Carteira inv�lida',
									  '15' => 'Caracter�sticas da cobran�a incompat�veis',
									  '16' => 'Data de vencimento inv�lida',
									  '17' => 'Data de vencimento anterior � data de emiss�o',
									  '18' => 'Vencimento fora do prazo de opera��o',
									  '20' => 'Valor do t�tulo inv�lido',
									  '21' => 'Esp�cie do T�tulo inv�lido',
									  '22' => 'Esp�cie n�o permitida para a carteira',
									  '24' => 'Data da emiss�o inv�lida',
									  '27' => 'Valor/taxa de juros mora inv�lido',
									  '29' => 'Valor do desconto maior/igual ao valor do T�tulo',
									  '30' => 'Desconto a conceder n�o confere',
									  '33' => 'Valor do abatimento inv�lido',
									  '42' => 'C�digo para baixa/devolu��o inv�lido',
									  '43' => 'Prazo para baixa e devolu��o inv�lido',
									  '45' => 'Nome do Pagador inv�lido',
									  '47' => 'Endere�o do Pagador n�o informado',
									  '48' => 'CEP Inv�lido',
									  '50' => 'CEP referente a Banco correspondente',
									  '53' => 'N� de inscri��o do Pagador/avalista inv�lidos  (CPF/CNPJ)',
									  '54' => 'Pagador/avalista n�o informado',
									  '60' => 'Movimento para T�tulo n�o cadastrado',
									  '63' => 'Entrada para T�tulo j� cadastrado',
									  '86' => 'Seu n�mero inv�lido');
	/* 
	*	Ocorr�ncia = 06 - Baixado pelo Banco 
	*/
	static $CD_MOTIVO_06 =  array('00' => 'T�tulo pago com dinheiro',
								  '15' => 'T�tulo pago com cheque',
								  '42' => 'Rateio n�o efetuado, c�d. Calculo 2 (VLR.Registro)');
	/* 
	*	Ocorr�ncia = 10 - Liquida��o 
	*/
	static $CD_MOTIVO_10 =  array('00' => 'Baixado Conforme Instru��es da Ag�ncia',
								  '14' => 'T�tulo Protestado',
								  '15' => 'T�tulo exclu�do',
								  '16' => 'T�tulo Baixado pelo Banco por decurso Prazo');
	/*
	*	Descri��o: Armazena linhas do registro.
	* 	@param string $linha String contendo a linha.
	* 	@return string $codigo
	*/
	public function descricaoRetornoBanco($codigo) {
		return Bradesco::$CD_RETORNO_BANCO[$codigo];
	}
	/*
	*	Descri��o: Armazena linhas do registro.
	* 	@param string $linha String contendo a linha.
	* 	@return string $codigo
	*/
	public function descricaoErroRetornoBanco($codigo) {
		return Bradesco::$CD_RETORNO_BANCO_ERRO[$codigo];
	}
	
	/*
	*	Descri��o: Armazena linhas do registro.
	* 	@param string $linha String contendo a linha.
	* 	@return 
	*/
	function salvaArquivo($caminho) {
		//chmod($caminho, '0755');
		$nm_arquivo = "Bradesco".date('YmdHi').".txt";
		$caminho    = $caminho . $nm_arquivo;
		file_put_contents($caminho, $this->STRING);
		return $caminho;
	}
	/*
	*	Descri��o: Executa o processamento de todo o arquivo, linha a linha
	* 	@param fiel $file Arquivo a ser processado
	* 	@return array Retorna um vetor contendo os dados das linhas do arquivo.*/
	function lerArquivoRetorno($file = NULL){
		$a_linhas = array();
		if($arq = file($file)) {
			foreach($arq as $linha) {
				$a_linhas[] = $linha;
			}
		}else{ 
			throw new Exception('N�o foi poss�vel abrir o arquivo '.$file);
		}
		return $a_linhas;
	}
	/*
	*	Descri��o: Processa uma linha do arquivo de retorno.
	* 	@param string $linha String contendo a linha a ser processada
	* 	@return array Retorna um vetor associativo contendo os valores_linha processada.
	*/
	function processarLinha($linha) {
		if(trim($linha) == '') {
			die('A linha est� vazia.');
		}
		/*Identifica��o do Registro*/
		$processar = 'processar'.substr($linha,0,1);
		if(method_exists('Bradesco', $processar)){
			return $this->$processar($linha);
		}else{
			throw new Exception('Metodo n�o existe: '.$processar);
		}
	}
	/*
	*	Descri��o: Processa a linha header do arquivo
	* 	@param string $linha Linha do header de arquivo processado 
	* 	@return array Retorna um vetor contendo os dados dos campos do header do arquivo.
	*/
	function processarErro($linha){
		$vlinha = array();																														
		$vlinha["erro"]  = rtrim($linha);
		return $vlinha;
	}
	/*
	*	Descri��o: Processa a linha header do arquivo
	* 	@param string $linha Linha do header de arquivo processado 
	* 	@return array Retorna um vetor contendo os dados dos campos do header do arquivo.
	*/
	private function processar0($linha){
		$vlinha = array();																														
		$vlinha["cd_registro"]  = substr($linha, 0, 1);  		//001 a 001 Identifica��o do Registro 001 0
		$vlinha["cd_retorno"]   = trim(substr($linha, 1, 1));    //002 a 002 Identifica��o do Arquivo Retorno 001
		$vlinha["tx_retorno"]   = trim(substr($linha, 2, 7));    //003 a 009 Literal Retorno 007 Retorno
		$vlinha["cd_servico"]   = substr($linha, 9, 2); 		//010 a 011 C�digo do Servi�o 002 01
		$vlinha["tx_servico"]   = trim(substr($linha, 11, 15));  //012 a 026 Literal Servi�o 015 Cobran�a
		$vlinha["cd_empresa"]   = substr($linha, 26, 20); 		//027 a 046 C�digo da Empresa 020
		$vlinha["nm_empresa"]   = trim(substr($linha, 46, 30)); 	//047 a 076 Nome  da Empresa por Extenso 030
		$vlinha["cd_banco"]     = substr($linha, 76, 3); 		//077 a 079 N� do Bradesco na C�mara Compensa��o 003
		$vlinha["nm_banco"]     = trim(substr($linha, 79, 15));	//080 a 094 Nome do Banco por Extenso 015
		$vlinha["dt_emissao"]   = substr($linha, 94, 6);		    //095 a 100 Data da Grava��o do Arquivo 006
		$vlinha["nr_aviso"]     = substr($linha, 108, 5);	 	//109 a 113 N� Aviso Banc�rio 005
		$vlinha["dt_credito"]   = substr($linha, 379, 6); 		//380 a 385 Data do Cr�dito 006
		$vlinha["nr_sequencia"] = substr($linha, 394, 6); 		//395 a 400 N� Seq�encial de registro
		return $vlinha;
	}
	/*
	*	Descri��o: Processa a linha header do arquivo
	* 	Obs.: Quando se tratar de cobran�a sem registro, somente os campos assinalados com (*) SEM REGISTRO
	*	asterisco, ser�o confirmados no Arquivo - Retorno.
	* 	@param string $linha Linha do header de arquivo processado 
	* 	@return array Retorna um vetor contendo os dados dos campos do header do arquivo.
	*/
	private function processar1($linha){
		$vlinha = array();																														
		
		$vlinha["cd_registro"]  			= rtrim(substr($linha, 0, 1)); 	// (*)  1  Identifica��o do Registro
		$vlinha["tipo_cpf_cnpj"]  			= rtrim(substr($linha, 1, 2)); 	// (*)  2  Tipo de Inscri��o Empresa
		$vlinha["cpf_cnpj"]    				= rtrim(substr($linha, 3, 14)); 	// (*)  14 N� Inscri��o da Empresa
		$vlinha["cd_carteira"] 				= rtrim(substr($linha, 21, 2)); 	// (*)  2 Identifica��o da Empresa Benefici�rio no Banco
		$vlinha["cd_agencia"] 				= rtrim(substr($linha, 24, 4)); 	// (*)  4 Identifica��o da Empresa Benefici�rio no Banco
		$vlinha["cd_ctacor"] 				= rtrim(substr($linha, 29, 6)); 	// (*)  6 Identifica��o da Empresa Benefici�rio no Banco
		$vlinha["cd_bloqueto"] 				= rtrim(substr($linha, 37, 25)); 	//     25 N� Controle do Participante
		$vlinha["cd_bloqueto_dv"]			= rtrim(substr($linha, 70, 12)); 	// (*) 12 Identifica��o do T�tulo no Banco
		if(empty($vlinha["cd_bloqueto"])){
			$vlinha["cd_bloqueto"] = substr($vlinha["cd_bloqueto_dv"], 5, 6);
		}
		$vlinha["cd_carteira"] 				= rtrim(substr($linha, 107, 1)); 	//     1 Carteira
		$vlinha["cd_ocorrencia"]			= rtrim(substr($linha, 108, 2)); 	// (*) 2 Identifica��o de Ocorr�ncia
		$vlinha["tx_ocorrencia"]  			= rtrim($this->descricaoRetornoBanco($vlinha["cd_ocorrencia"]));
		$vlinha["dt_ocorrencia"]			= rtrim(substr($linha, 110, 6)); 	// (*) 6 Data Ocorr�ncia no Banco
		$vlinha["cd_titulo"] 				= rtrim(substr($linha, 116, 10)); 	//    10 N�mero do Documento
		$vlinha["dt_vencto"] 				= rtrim(substr($linha, 146, 6)); 	//    6 Data Vencimento do T�tulo
		$vlinha["vl_titulo"] 				= rtrim(substr($linha, 152, 13)); 	//    13 Valor do T�tulo
		$vlinha["cd_banco_cobrador"] 		= rtrim(substr($linha, 165, 3)); 	// (*) 3 Banco Cobrador
		$vlinha["cd_agencia_cobradora"] 	= rtrim(substr($linha, 168, 5)); 	// (*) 5 Ag�ncia Cobrador
		$vlinha["cd_doc"] 					= rtrim(substr($linha, 173, 2)); 	//    2 Esp�cie do T�tulo
		$vlinha["vl_despesas_cobranca"]    	= rtrim(substr($linha,175,13)) / 100; // 13 Despesas de cobran�a para os
		$vlinha["vl_outras_despesas"] 		= rtrim(substr($linha,188,13)) / 100; // 13 Outras despesas
		$vlinha["vl_iof"] 					= rtrim(substr($linha,214,13)) / 100; // 13 Valor IOF
		$vlinha["vl_abatimento"] 			= rtrim(substr($linha,227,13)) / 100; // 13 Abatimento Concedido sobre o T�tulo
		$vlinha["vl_descontos"] 			= rtrim(substr($linha,240,13)) / 100; // 13 Desconto Concedido
		$vlinha["vl_pagto"] 				= rtrim(substr($linha,253,13)) / 100; // 13 Valor Pago
		$vlinha["vl_juros"] 				= rtrim(substr($linha,266,13)) / 100; // 13 Valor Juros de Mora
		$vlinha["vl_outros_recebimentos"] 	= rtrim(substr($linha,279,13)) / 100; // 13 Outros Cr�ditos
		$vlinha["vl_pagto"] 				= $vlinha["vl_pagto"] + $vlinha["vl_juros"];
		$vlinha["vl_creditado"] 			= $vlinha["vl_pagto"] - $vlinha["vl_despesas_cobranca"];
		$vlinha["cd_confirmacao"] 			= trim(substr($linha, 294, 1)); 	// A � Aceito D � Desprezado
		$vlinha["dt_credito"]     			= rtrim(substr($linha,299,2)) == '' ? 'null' : '20'.substr($linha,299,2).'-'.substr($linha,297,2).'-'. substr($linha,295,2);
		$vlinha["cd_origem"] 				= rtrim(substr($linha, 301, 3));
		 

		return $vlinha;
	}
	/*
	*	Descri��o: Armazena linhas do registro.
	* 	@param string $linha String contendo a linha.
	* 	@return 
	*/
	private function escreveLinha($linha) {
		$this->STRING .= "$linha\r\n";
	}
	/*
	*	Descri��o: HEADER
	* 	@param 
	* 	@return string Linha.
	*/
	function registroHeader($cd_convenio, $nm_empresa, $dt_gravacao, $nrs, $nsr){
		$this->escreveLinha('0' //001 a 001 Identifica��o do Registro 001
							.'1' //002 a 002 Identifica��o do Arquivo Remessa 001
							.'REMESSA'//003 a 009 Literal Remessa 007
							.'01'//010 a 011 C�digo de Servi�o 002
							.$this->formataCampoString('COBRANCA',15)//012 a 026 Literal Servi�o 015
							.$this->formataCampoNumerico($cd_convenio,20)//027 a 046 C�digo da Empresa 020
							.$this->formataCampoString($nm_empresa,30)//047 a 076 Nome da Empresa 030
							.'237'//077 a 079 N�mero do Bradesco na C�mara de Compensa��o 003
							.$this->formataCampoString('Bradesco',15)//080 a 094 Nome do Banco por Extenso 015
							.$this->formataCampoNumerico($dt_gravacao,6)//09 5 a 100 DDMMAA Data da Grava��o do Arquivo 006
							.$this->espacosBrancos(8)//101 a 108 Branco 008
							.'MX'//109 a 110 Identifica��o do sistema 002
							.$this->formataCampoNumerico($nrs,7)//111 a 117 N� Seq�encial de Remessa 007
							.$this->espacosBrancos(277)
							.$this->formataCampoNumerico($nsr,6));//395 a 400 N� Seq�encial do Registro de Um em Um 006
	}
	/*
	*	Descri��o: Dados do Titulo
	*	
	*	Identif ica��es da Empresa Benefici�ria no Banco
	*	Deve � ser preenchido (esquerda para direita), da seguinte maneira:
	*	21 a 21  - Zero 
	*	22 a 24  - c�digos da carteira 
	*	25 a 29  - c�digos da Ag�ncia Benefici�rios, sem o d�gito. 
	*	30 a 36  - Contas Corrent 
	*	37 a 37  - d�gitos da Conta
	* 	@param datas DDMMAA
	* 	@return string Linha.
	*/
	function registroDadosTitulo($cd_carteira, $cd_agencia, $cd_ctacor, $cd_bloqueto, $cd_multa, $pc_multa, $vl_desconto_dia,
								 $cd_titulo, $dt_vencto, $vl_titulo, $dt_emissao, $vl_multa, $dt_desconto, $vl_desconto, 
								 $vl_abatimento,  $cpf_cnpj, $nm_pessoa, $endereco, $mensagem_1, $cep_sem_sufixo,  
								 $cep_sufixo, $mensagem_2, $nr_sequencia_registro){

		$digito_cd_ctacor     = substr($cd_ctacor, -1);
		$cd_ctacor_sem_digito = substr($cd_ctacor, 0, strlen($cd_ctacor)-1);

		$this->escreveLinha('1'//001 a 001 Identifica��o do Registro 001
							.$this->formataCampoNumerico('',5)//002 a 006 Ag�ncia de D�bito (opcional) 005
							.$this->formataCampoString('',1)//007 a 007 D�gito da Ag�ncia de D�bito (opcional) 001
							.$this->formataCampoNumerico('',5)//008 a 012 Raz�o da Conta Corrente (opcional) 005
							.$this->formataCampoNumerico('',7)//013 a 019 Conta Corrente (opcional) 007
							.$this->formataCampoString('',1)//020 a 020 D�gito da Conta Corrente (opcional) 
							.$this->formataCampoString('0'		//021 a 037 Identifica��o da Empresa Benefici�ria no Banco 017 001 
							.$this->formataCampoNumerico($cd_carteira,3)//009
							.$this->formataCampoNumerico($cd_agencia,5)//01878
							.$this->formataCampoNumerico($cd_ctacor_sem_digito,7)//0013089 13089
							.$this->formataCampoNumerico($digito_cd_ctacor,1),17)
							.$this->formataCampoString($cd_bloqueto,25)//038 a 062 N� Controle do Participante 025
							.$this->formataCampoNumerico(0,3)//063 a 065 C�digo do Banco a ser debitado na C�mara de Compensa��o 003
							.$this->formataCampoNumerico($cd_multa,1)//066 a 066 Campo de Multa 001
							.$this->formataCampoNumerico($pc_multa,4)//067 a 070 Percentual de multa 004
							.$this->formataCampoNumerico($cd_bloqueto,11)//071 a 081 Identifica��o do T�tulo no Banco 11
							.$this->formataCampoNumerico($this->DigitoVerificador(sprintf("%02d",$cd_carteira).sprintf("%011s", $cd_bloqueto)),1)//082 a 082 Digito de Auto Conferencia do N�mero Banc�rio. 001
							.$this->formataCampoNumerico($vl_desconto_dia,10)//083 a 092 Desconto Bonifica��o por dia 010
							.$this->formataCampoNumerico(TIPO_EMISSAO_DOC_CLIENTE,1)//093 a 093 Condi��o para Emiss�o da Papeleta de Cobran�a 001
							.'N'//094 a 094 Ident.  se  emite  Boleto  para  D�bito  Autom�tico 001
							.$this->espacosBrancos(10)//095 a 104 Identifica��o da Opera��o do Banco 010 Brancos
							.$this->formataCampoString('',1)//05 a 105 Indicador Rateio Cr�dito (opcional) 001
							.$this->formataCampoNumerico(1,1)//106 a 106 Endere�amento para Aviso do D�bito Autom�tico em Conta Corrente (opcional) 001
							.$this->espacosBrancos(2)
							.$this->formataCampoNumerico(OCORRENCIA_REMESSA,2)//109 a 110 Identifica��o da ocorr�ncia 002
							.$this->formataCampoString($cd_titulo,10)//111 a 120 N� do Documento 010
							.$this->formataCampoNumerico($dt_vencto,6)//121 a 126 Data do Vencimento do T�tulo 006 DDMMAA
							.$this->formataCampoNumerico($vl_titulo,13)//127 a 139 Valor do T�tulo 013
							.$this->formataCampoNumerico(0,3)//140 a 142 Banco Encarregado da Cobran�a 003 Preencher com zeros
							.$this->formataCampoNumerico(0,5)//143 a 147 Ag�ncia Deposit�ria 005 Preencher com zeros
							.$this->formataCampoNumerico(ESPECIE_DOC,2)//48 a 149 Esp�cie de T�tulo 002
							.'N'//150 a 150 Identifica��o 001 Sempre = N
							.$this->formataCampoNumerico($dt_emissao,6)//151 a 156 Data da emiss�o do T�tulo 006 DDMMAA
							.$this->formataCampoNumerico(0,2)//157 a 158 1� instru��o 002
							.$this->formataCampoNumerico(0,2)//159 a 160 2� instru��o 002
							.$this->formataCampoNumerico($vl_multa,13)//161 a 173 Valor a ser cobrado por Dia de Atraso 013
							.$this->formataCampoNumerico($dt_desconto,6)//174 a 179 Data Limite P/Concess�o de Desconto 006 DDMMAA
							.$this->formataCampoNumerico($vl_desconto,13)//180 a 192 Valor do Desconto 013
							.$this->formataCampoNumerico(0,13)//193 a 205 Valor do IOF 013
							.$this->formataCampoNumerico($vl_abatimento,13)//06 a 218 Valor do Abatimento a ser concedido ou cancelado 013
							.$this->formataCampoNumerico($this->identificacaoCPFCNPJ($cpf_cnpj),2)//19 a 220 Identifica��o do Tipo de Inscri��o do Pagador 002
							.$this->formataCampoNumerico($cpf_cnpj,14)//221 a 234 N� Inscri��o do Pagador 014
							.$this->formataCampoString($nm_pessoa,40)//235 a 274 Nome do Pagador 040
							.$this->formataCampoString($endereco,40)//275 a 314 Endere�o Completo 040
							.$this->formataCampoString($mensagem_1,12)//315 a 326 1� Mensagem 012
							.$this->formataCampoNumerico($cep_sem_sufixo,5)//327 a 331 CEP 005 CEP Pagador
							.$this->formataCampoNumerico($cep_sufixo,3)//332 a 334 Sufixo do CEP 003 Sufixo
							.$this->formataCampoString($mensagem_2,60)//335 a 394 Sacador/Avalista  ou 2� Mensagem 060
							.$this->formataCampoNumerico($nr_sequencia_registro,6));
	}
	/*
	*	Descri��o: 	Lay-out do Arquivo-Remessa -Registro de Transa��o-Tipo 3 Dados do Sacador Avalista  (OPCIONAL)
	* 	@param 
	* 	@return string Linha.
	*/
	function registroDadosSacadorAvalista(){
		return true;
	}
	/*
	*	Descri��o: 	Lay-out do Arquivo-Remessa -Registro de Transa��o-Tipo 2 (OPCIONAL)
	* 	@param 
	* 	@return string Linha.
	*/
	function registroDadosSacador(){
		return true;
	}
	/*
	*	Descri��o: Lay-out do Arquivo-Remessa -Registro de Transa��o- Tipo 3 Rateio de Credito(OPCIONAL)
	* 	@param 
	* 	@return string Linha.
	*/
	function registroRateioCredito(){
		return true;
	}
	/*
	*	Descri��o: 	TRAILLER
	* 	@param 
	* 	@return string Linha.
	*/
	function registroTrailler($nr_sequencia_registro){
		
		$this->STRING .= '9'
						 .$this->espacosBrancos(393)
						 .$this->formataCampoNumerico($nr_sequencia_registro,6);
	}

	/*
	*	NC (n�mero de controle), que � calculado de acordo com o m�dulo 10 e o m�dulo 11
	* 	@param $nosso_numero bloqueto
	* 	@return int Retorna nosso numero com os digitos de controle
	*/
	function DigitoVerificador($nosso_numero){
        $resto  = $this->Modulo11($nosso_numero, 7, 1);
        $digito = 11 - $resto;
        if ($resto == 1) {
            $digito = "P";
        } elseif ($resto == 0) {
            $digito = 0;
        }
        return $digito;
    }
    /*
	*	Calculo modulo 10 Banrisul.
	* 	@param $num bloqueto
	* 	@return integer Retorna check-digit (m�dulo 10)
	*/
    function modulo10($num){
        $fator = 2;
        for ($i = strlen($num); $i > 0; $i--) {
            $numeros[$i] = substr($num,$i-1,1);
            $multiplicacao[$i] = ($numeros[$i] * $fator);
            if ($multiplicacao[$i]>9) {
                $multiplicacao[$i] = $multiplicacao[$i] - 9;
            }
            if ($fator == 2) {
                $fator = 1;
            } else {
                $fator = 2; 
            }
        }
        $soma = 0;
        for ($i = strlen($num); $i > 0; $i--) {
            $soma += $multiplicacao[$i];                
        }
        $resto = $soma % 10;
        if ($soma < 10){ $resto = $soma; }
        $digito = 10 - $resto;
        if ($resto == 0){ $digito = 0; }
        return $digito;
    }
    /*
    *   Descri��o: Armazena linhas do registro.
    *   @param string $linha String contendo a linha.
    *   @return 
    */
    private function Modulo11($num, $base=9, $r=0, $bradesco=0){
        $soma   = 0;
        $fator  = 2;
        for ($i = strlen($num); $i > 0; $i--) {
            $numeros[$i] = substr($num,$i-1,1);
            $parcial[$i] = $numeros[$i] * $fator;
            $soma += $parcial[$i];
            if ($fator == $base) {
                $fator = 1;
            }
            $fator++;
        }
        /* Calculo do modulo 11 */
        if ($bradesco == 1) {
            $resto  = $soma % 11;
            $digito = 11 - $resto;
            if ($digito==10 or $digito==11) {
                $digito = 1;
            }
            return $digito;
        }
        if ($r == 0) {
            $soma *= 10;
            $digito = $soma % 11;
            if ($digito == 10) {
                $digito = 0;
            }
            return $digito;
        } elseif ($r == 1){
            $resto = $soma % 11;
            return $resto;
        }
    } 
	/*
	*	Descri��o: Formata uma string, contendo um valor real (float) sem o separador de decimais, para a sua correta representa��o real.
	* 	@param string $valor String contendo o valor na representa��o usada nos arquivos de retorno do banco, sem o separador de decimais.
	* 	@param int $numCasasDecimais Total de casas decimais do n�mero representado em $valor.
	* 	@return float Retorna o n�mero representado em $valor, no seu formato float, contendo o separador de decimais.
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
	*	Formata uma string com zeros.
	* 	@param $numero numero de zeros.
	* 	@return string Retorna zeros.
	*/
	private function identificacaoCPFCNPJ($valor){
		/*
		01 - 	CPF
		02 - 	CNPJ
		03 - 	PIS/PASEP
		98 - 	N�o tem
		99 - 	Outros
		*/
		return strlen($valor) <= 11 ? '01' : '02'; 
	}
	/*
	*	Formata uma string com espa�os em branco.
	* 	@param $numero numero de espa�os.
	* 	@return string Retorna espa�os em branco.
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
	*	Formata uma string com espa�os em branco.
	* 	@param $numero numero de espa�os.
	* 	@return string Retorna espa�os em branco.
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
	*	Formata uma string com espa�os em branco.
	* 	@param $numero numero de espa�os.
	* 	@return string Retorna espa�os em branco.
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