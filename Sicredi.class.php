<?php 

/*
*	Descri��o:  Classe para gera��o e leitura de arquivos retorno remessa em conta Sicredi.
*				Padr�o FEBRABAN
* 	Autor: Thiago R. Gham
* 	Vers�o: 0.1	 18-10-2016
*/
/*
1 = Banco emite e Processa o registro.
2 = Cliente emite e o Banco  somente  processa  o  registro 
*/
define('TIPO_EMISSAO_DOC_BANCO', 1);
define('TIPO_EMISSAO_DOC_CLIENTE', 2);

/*
Este campo s� permite usar os seguintes c�digos:
01 - Cadastro de t�tulo;
02 - Pedido de baixa;
04 - Concess�o de abatimento;
05 - Cancelamento de abatimento concedido;
06 - Altera��o de vencimento;
09 - Pedido de protesto;
18 - Sustar protesto e baixar t�tulo;
19 - Sustar protesto e manter em carteira; 
*/
define('INSTRUCAO_CADASTRO','01');
define('INSTRUCAO_BAIXA'  ,'02');
define('INSTRUCAO_ALTERACAO_VENCIMENTO'  ,'06');
/*
Este campo s� permite usar os seguintes c�digos:
A - Duplicata Mercantil por Indica��o;
B - Duplicata Rural;
C - Nota Promiss�ria;
D - Nota Promiss�ria Rural;
E - Nota de Seguros;
G � Recibo;
H - Letra de C�mbio;
I - Nota de D�bito;
J - Duplicata de Servi�o por Indica��o;
K � Outros.
O � Boleto Proposta
 */
define('ESPECIE_DOC','K');

class Sicredi{
	
	public $debug   = false;

	private $STRING = '';

	private $cd_ctacor = 0;
	/**
	 * [$CD_RETORNO_BANCO description]
	 * @var array
	 */
	static $CD_RETORNO_BANCO =  array('02' => 'Entrada Confirmada',//Sem Registro tbm
									  '03' => 'Entrada rejeitada',//Sem Registro tbm
									  '06' => 'Liquida��o normal',//Sem Registro tbm
									  '09' => 'Baixado automaticamente via arquivo',
									  '10' => 'Baixado conforme instru��es da cooperativa de cr�dito',
									  '12' => 'Abatimento Concedido',
									  '13' => 'Abatimento Cancelado',
									  '14' => 'Vencimento alterado',
									  '15' => 'Liquida��o em Cart�rio',
									  '17' => 'Liquida��o ap�s baixa ou T�tulo n�o registrado',
									  '19' => 'Confirma��o de recebimento de instru��o de protesto',
									  '20' => 'Confirma��o de recebimento de instru��o de susta��o de protesto',
									  '23' => 'Entrada de t�tulo em cart�rio',
									  '24' => 'Entrada rejeitada por CEP irregular',
									  '27' => 'Baixa Rejeitada',
									  '28' => 'Tarifa',//Sem Registro tbm
									  '30' => 'Altera��o Rejeitada',
									  '32' => 'Instru��o Rejeitada',
									  '33' => 'Confirma��o de pedido de altera��o de outros dados',
									  '34' => 'Retirado de cart�rio e manuten��o em carteira',
									  '35' => 'Aceite do pagador');
	/**
	 * [$CD_MOTIVO_RETORNO description]
	 * @var array
	 */
	static $CD_MOTIVO_RETORNO = array(	'01' => 'C�digo do banco inv�lido',
										'02' => 'C�digo do registro detalhe inv�lido',
										'03' => 'C�digo da ocorr�ncia inv�lido',
										'04' => 'C�digo de ocorr�ncia n�o permitida para a carteira',
										'05' => 'C�digo de ocorr�ncia n�o num�rico',
										'07' => 'Cooperativa/ag�ncia/conta/d�gito inv�lidos',
										'08' => 'Nosso n�mero inv�lido',
										'09' => 'Nosso n�mero duplicado',
										'10' => 'Carteira inv�lida',
										'14' => 'T�tulo protestado',
										'15' => 'Cooperativa/carteira/ag�ncia/conta/nosso n�mero inv�lidos',
										'16' => 'Data de vencimento inv�lida',
										'17' => 'Data de vencimento anterior � data de emiss�o',
										'18' => 'Vencimento fora do prazo de opera��o',
										'20' => 'Valor do t�tulo inv�lido',
										'21' => 'Esp�cie do t�tulo inv�lida',
										'22' => 'Esp�cie n�o permitida para a carteira',
										'24' => 'Data de emiss�o inv�lida',
										'29' => 'Valor do desconto maior/igual ao valor do t�tulo',
										'31' => 'Concess�o de desconto - existe desconto anterior',
										'33' => 'Valor do abatimento inv�lido',
										'34' => 'Valor do abatimento maior/igual ao valor do t�tulo',
										'36' => 'Concess�o de abatimento - existe abatimento anterior',
										'38' => 'Prazo para protesto inv�lido',
										'39' => 'Pedido para protesto n�o permitido para o t�tulo',
										'40' => 'T�tulo com ordem de protesto emitida',
										'41' => 'Pedido cancelamento/susta��o sem instru��o de protesto',
										'44' => 'Cooperativa de cr�dito/ag�ncia benefici�ria n�o prevista',
										'45' => 'Nome do pagador inv�lido',
										'46' => 'Tipo/n�mero de inscri��o do pagador inv�lidos',
										'47' => 'Endere�o do pagador n�o informado',
										'48' => 'CEP irregular',
										'49' => 'N�mero de Inscri��o do pagador/avalista inv�lido',
										'50' => 'Pagador/avalista n�o informado',
										'60' => 'Movimento para t�tulo n�o cadastrado',
										'63' => 'Entrada para t�tulo j� cadastrado',
										'A'  => 'Aceito',
										'D'  => 'Desprezado',
										'A1' => 'Pra�a do pagador n�o cadastrada',
										'A2' => 'Tipo de cobran�a do t�tulo divergente com a pra�a do pagador',
										'A3' => 'Cooperativa/ag�ncia deposit�ria divergente: atualiza o cadastro de pra�as da Coop./ag�ncia benefici�ria',
										'A4' => 'Benefici�rio n�o cadastrado ou possui CGC/CIC inv�lido',
										'A5' => 'Pagador n�o cadastrado',
										'A6' => 'Data da instru��o/ocorr�ncia inv�lida',
										'A7' => 'Ocorr�ncia n�o pode ser comandada',
										'A8' => 'Recebimento da liquida��o fora da rede Sicredi - via compensa��o eletr�nica',
										'B4' => 'Tipo de moeda inv�lido',
										'B5' => 'Tipo de desconto/juros inv�lido',
										'B6' => 'Mensagem padr�o n�o cadastrada',
										'B7' => 'Seu n�mero inv�lido',
										'B8' => 'Percentual de multa inv�lido',
										'B9' => 'Valor ou percentual de juros inv�lido',
										'C1' => 'Data limite para concess�o de desconto inv�lida',
										'C2' => 'Aceite do t�tulo inv�lido',
										'C3' => 'Campo alterado na instru��o �31 � altera��o de outros dados� inv�lido',
										'C4' => 'T�tulo ainda n�o foi confirmado pela centralizadora',
										'C5' => 'T�tulo rejeitado pela centralizadora',
										'C6' => 'T�tulo j� liquidado',
										'C7' => 'T�tulo j� baixado',
										'C8' => 'Existe mesma instru��o pendente de confirma��o para este t�tulo',
										'C9' => 'Instru��o pr�via de concess�o de abatimento n�o existe ou n�o confirmada',
										'D1' => 'T�tulo dentro do prazo de vencimento (em dia)',
										'D2' => 'Esp�cie de documento n�o permite protesto de t�tulo',
										'D3' => 'T�tulo possui instru��o de baixa pendente de confirma��o',
										'D4' => 'Quantidade de mensagens padr�o excede o limite permitido',
										'D5' => 'Quantidade inv�lida no pedido de boletos pr�-impressos da cobran�a sem registro',
										'D6' => 'Tipo de impress�o inv�lida para cobran�a sem registro',
										'D7' => 'Cidade ou Estado do pagador n�o informado',
										'D8' => 'Seq��ncia para composi��o do nosso n�mero do ano atual esgotada',
										'D9' => 'Registro mensagem para t�tulo n�o cadastrado',
										'E2' => 'Registro complementar ao cadastro do t�tulo da cobran�a com e sem registro n�o cadastrado',
										'E3' => 'Tipo de postagem inv�lid. o, diferente de . S, N e branco',
										'E4' => 'Pedido de boletos pr�-impressos',
										'E5' => 'Confirma��o/rejei��o para pedidos de boletos n�o cadastrado',
										'E6' => 'Pagador/avalista n�o cadastrado',
										'E7' => 'Informa��o para atualiza��o do valor do t�tulo para protesto inv�lido',
										'E8' => 'Tipo de impress�o inv�lid. o, diferente de . A, B e branco',
										'E9' => 'C�digo do pagador do t�tulo divergente com o c�digo da cooperativa de cr�dito',
										'F1' => 'Liquidado no sistema do cliente',
										'F2' => 'Baixado no sistema do cliente',
										'F3' => 'Instru��o inv�lid. a, este t�tulo est� caucionado/descontado',
										'F4' => 'Instru��o fixa com caracteres inv�lidos',
										'F6' => 'Nosso n�mero / n�mero da parcela fora de seq��ncia � total de parcelas inv�lido',
										'F7' => 'Falta de comprovante de presta��o de servi�o',
										'F8' => 'Nome do benefici�rio incompleto / incorreto',
										'F9' => 'CNPJ / CPF incompat�vel com o nome do pagador / Sacador Avalista',
										'G1' => 'CNPJ / CPF do pagador Incompat�vel com a esp�cie',
										'G2' => 'T�tulo aceito: sem a assinatura do pagador',
										'G3' => 'T�tulo aceito: rasurado ou rasgado',
										'G4' => 'T�tulo aceito: falta t�tulo (cooperativa/ag. benefici�ria dever� envi�-lo)',
										'G5' => 'Pra�a de pagamento incompat�vel com o endere�o',
										'G6' => 'T�tulo aceito: sem endosso ou benefici�rio irregular',
										'G7' => 'T�tulo aceito: valor por extenso diferente do valor num�rico',
										'G8' => 'Saldo maior que o valor do t�tulo',
										'G9' => 'Tipo de endosso inv�lido',
										'H1' => 'Nome do pagador incompleto / Incorreto',
										'H2' => 'Susta��o judicial',
										'H3' => 'Pagador n�o encontrado',
										'H4' => 'Altera��o de carteira',
										'H5' => 'Recebimento de liquida��o fora da rede Sicredi � VLB Inferior � Via Compensa��o',
										'H6' => 'Recebimento de liquida��o fora da rede Sicredi � VLB Superior � Via Compensa��o',
										'H7' => 'Esp�cie de documento necessita benefici�rio ou avalista PJ',
										'H8' => 'Recebimento de liquida��o fora da rede Sicredi � Conting�ncia Via Compe',
										'H9' => 'Dados do t�tulo n�o conferem com disquete',
										'I1' => 'Pagador e Sacador Avalista s�o a mesma pessoa',
										'I2' => 'Aguardar um dia �til ap�s o vencimento para protestar',
										'I3' => 'Data do vencimento rasurada',
										'I4' => 'Vencimento � extenso n�o confere com n�mero',
										'I5' => 'Falta data de vencimento no t�tulo',
										'I6' => 'DM/DMI sem comprovante autenticado ou declara��o',
										'I7' => 'Comprovante ileg�vel para confer�ncia e microfilmagem',
										'I8' => 'Nome solicitado n�o confere com emitente ou pagador',
										'I9' => 'Confirmar se s�o 2 emitentes. Se si. m, indicar os dados dos 2',
										'J1' => 'Endere�o do pagador igual ao do pagador ou do portador',
										'J2' => 'Endere�o do apresentante incompleto ou n�o informado',
										'J3' => 'Rua/n�mero inexistente no endere�o',
										'J4' => 'Falta endosso do favorecido para o apresentante',
										'J5' => 'Data da emiss�o rasurada',
										'J6' => 'Falta assinatura do pagador no t�tulo',
										'J7' => 'Nome do apresentante n�o informado/incompleto/incorreto',
										'J8' => 'Erro de preenchimento do titulo',
										'J9' => 'Titulo com direito de regresso vencido',
										'K1' => 'Titulo apresentado em duplicidade',
										'K2' => 'Titulo j� protestado',
										'K3' => 'Letra de cambio vencida � falta aceite do pagador',
										'K4' => 'Falta declara��o de saldo assinada no t�tulo',
										'K5' => 'Contrato de cambio � Falta conta gr�fica',
										'K6' => 'Aus�ncia do documento f�sico',
										'K7' => 'Pagador falecido',
										'K8' => 'Pagador apresentou quita��o do t�tulo',
										'K9' => 'T�tulo de outra jurisdi��o territorial',
										'L1' => 'T�tulo com emiss�o anterior a concordata do pagador',
										'L2' => 'Pagador consta na lista de fal�ncia',
										'L3' => 'Apresentante n�o aceita publica��o de edital',
										'L4' => 'Dados do Pagador em Branco ou inv�lido',
										'L5' => 'C�digo do Pagador na ag�ncia benefici�ria est� duplicado',
										'M1' => 'Reconhecimento da d�vida pelo pagador',
										'M2' => 'N�o reconhecimento da d�vida pelo pagador',
										'X1' => 'Regulariza��o centralizadora � Rede Sicredi',
										'X2' => 'Regulariza��o centralizadora � Compensa��o',
										'X3' => 'Regulariza��o centralizadora � Banco correspondente',
										'X4' => 'Regulariza��o centralizadora - VLB Inferior - via compensa��o',
										'X5' => 'Regulariza��o centralizadora - VLB Superior - via compensa��o',
										'X0' => 'Pago com cheque',
										'X6' => 'Pago com cheque � bloqueado 24 horas',
										'X7' => 'Pago com cheque � bloqueado 48 horas',
										'X8' => 'Pago com cheque � bloqueado 72 horas',
										'X9' => 'Pago com cheque � bloqueado 96 horas',
										'XA' => 'Pago com cheque � bloqueado 120 horas',
										'XB' => 'Pago com cheque � bloqueado 144 horas');
	/**
	 * [$CD_MOTIVO_TARIFA description]
	 * @var array
	 */
	static $CD_MOTIVO_TARIFA = array(	'03' => 'Tarifa de susta��o',
										'04' => 'Tarifa de protesto',
										'08' => 'Tarifa de custas de protesto',
										'A9' => 'Tarifa de manuten��o de t�tulo vencido',
										'B1' => 'Tarifa de baixa da carteira',
										'B3' => 'Tarifa de registro de entrada do t�tulo',
										'F5' => 'Tarifa de entrada na rede Sicredi');

	/*
	*	Descri��o: Armazena linhas do registro.
	* 	@param string $linha String contendo a linha.
	* 	@return string $codigo
	*/
	private function descricaoRetornoBanco($codigo) {
		return Sicredi::$CD_RETORNO_BANCO[$codigo];
	}
	/**
	 * [descricaoMotivosOcorrencia description]
	 * @param  [type] $cd_motivos [description]
	 * @return [type]             [description]
	 */
	private function descricaoMotivosOcorrencia($cd_motivos, $modo = 'normal') {
		$tx_descricao = '';
		$codigo 	  = '';
		$letras = str_split(rtrim($cd_motivos));

		for ($i=0; $i < count($letras); $i++) {
			$codigo .= $letras[$i];
			if($i % 2){
				if($codigo != '00' and $codigo != '0' and !empty($codigo)){
					if($modo == 'normal'){
						$tx_descricao .= Sicredi::$CD_MOTIVO_RETORNO[$codigo] . ' ';
					}else{
						$tx_descricao .= Sicredi::$CD_MOTIVO_TARIFA[$codigo]  . ' ';
					}
				}
				$codigo = '';
			}
		}
		return $tx_descricao;
	}
	/*
	*	Descri��o: Armazena linhas do registro.
	* 	@param string $linha String contendo a linha.
	* 	@return 
	*/
	function salvaArquivo($caminho) {
		/*
		CCCCC = c�digo benefici�rio
		MDD = c�d. do m�s e n� do dia da data de gera��o do arquivo
		CRM = Indica que � o 1� arquivo remessa
		CCCCCMDD.CRM
		 */
		$dia 	    = date('d');
		$mes 		= $this->retonaMes();
		$nm_arquivo = substr($this->cd_ctacor, 0, 5) . $mes . $dia . '.crm';
		$caminho    = $caminho . $nm_arquivo;

		file_put_contents($caminho, $this->STRING);

		return $caminho;
	}
	/**
	 * [retonaMes description]
	 * @return [type] [description]
	 */
	private function retonaMes(){
		$mes = date('n');
		switch ($mes) {
			case '10':
				$mes = 'o';
				break;
			case '11':
				$mes = 'n';
				break;
			case '12':
				$mes = 'd';
				break;
		}
		return $mes;
	}
	/*
	*	Descri��o: Executa o processamento de todo o arquivo, linha a linha
	* 	@param fiel $file Arquivo a ser processado
	* 	@return array Retorna um vetor contendo os dados das linhas do arquivo.*/
	public function lerArquivoRetorno($file = NULL){
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
	public function processarLinha($linha) {
		if(trim($linha) == '') {
			die('A linha est� vazia.');
		}
		/*Identifica��o do Registro*/
		$processar = 'processar'.substr($linha,0,1);
		if(method_exists('Sicredi', $processar)){
			return $this->$processar($linha);
		}else{
			die('Metodo n�o existe: '.$processar);
		}
	}
	/*
	*	Descri��o: Processa a linha header do arquivo
	* 	@param string $linha Linha do header de arquivo processado 
	* 	@return array Retorna um vetor contendo os dados dos campos do header do arquivo.
	*/
	private function processar0($linha){
		$vlinha = array();																														
		$vlinha["cd_registro"]  = substr($linha, 0, 1);  		//001 a 001 Identifica��o do Registro 0
		$vlinha["cd_retorno"]   = trim(substr($linha, 1, 1));   //002 a 002 Identifica��o do Arquivo Retorno 2
		$vlinha["tx_retorno"]   = trim(substr($linha, 2, 7));   //003 a 009 Literal Retorno 007 RETORNO
		$vlinha["cd_servico"]   = substr($linha, 9, 2); 		//010 a 011 C�digo do Servi�o 002 01
		$vlinha["tx_servico"]   = trim(substr($linha, 11, 15)); //012 a 026 Literal Servi�o 015 COBRAN�A
		$vlinha["cd_ctacor"]    = substr($linha, 26, 5); 		//027 a 031 005 C�digo do benefici�rio 
		$vlinha["cd_cnpj"]      = substr($linha, 31, 14); 		//032 a 045 014 CIC/CGC do benefici�rio 
		$vlinha["cd_banco"]     = substr($linha, 76, 3); 		//077 a 079 003 N�mero do Sicredi
		$vlinha["nm_banco"]     = trim(substr($linha, 79, 15));	//080 a 094 015 Literal BANSicredi 
		$vlinha["dt_emissao"]   = substr($linha, 94, 8);		//095 a 102 Data da Grava��o do Arquivo AAAAMMDD
		$vlinha["nr_remessa"]   = substr($linha, 110, 7);	 	//111 a 117 007 N�mero do retorno
		$vlinha["cd_versao"]    = substr($linha, 389, 6); 		//390 a 394 005 Vers�o do sistema 99.99
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
		
		$vlinha["cd_registro"]  			= substr($linha, 0, 1); 	// (*)  1  Identifica��o do Registro
		$vlinha["tipo_cobranca"]  			= substr($linha, 13, 1); 	//014 a 014 001 Tipo de cobran�a �A� - Sicredi Cobran�a com registro
		$vlinha["cd_pagador"]    			= substr($linha, 14, 5); 	//015 a 019 005 C�digo do pagador na cooperativa do
		$vlinha["cd_pessoa"] 				= substr($linha, 19, 05); 	//020 a 024 005 C�digo do pagador junto ao associado
		$vlinha["tp_boleto"] 				= substr($linha, 24, 1); 	//025 a 025 001 Boleto DDA
		$vlinha["cd_bloqueto"] 				= rtrim(substr($linha, 47, 15)); 	//048 a 062 015 Nosso n�mero Sicredi sem edi��o
		$vlinha["cd_ocorrencia"]			= substr($linha, 108, 2); 	// (*) 2 Identifica��o de Ocorr�ncia
		$vlinha["tx_ocorrencia"]  			= $this->descricaoRetornoBanco($vlinha["cd_ocorrencia"]);
		$vlinha["dt_ocorrencia"]			= substr($linha, 110, 6); 	// (*) 6 Data Ocorr�ncia no Banco DDMMAA
		$vlinha["dt_ocorrencia"] 			= empty($vlinha["dt_ocorrencia"]) ? 'NULL' : '20'.substr($vlinha["dt_ocorrencia"], -2).'-'.substr($vlinha["dt_ocorrencia"], 2, 2).'-'.substr($vlinha["dt_ocorrencia"], 0,2);
		$vlinha["dt_pagto"] 				= $vlinha["dt_ocorrencia"];
		$vlinha["cd_titulo"] 				= substr($linha, 116, 10); 	//    10 N�mero do Documento titulo
		$vlinha["dt_vencto"] 				= rtrim(substr($linha, 146, 6)); 	//    6 Data Vencimento do T�tulo DDMMAA 
		$vlinha["dt_vencto"] 				= empty($vlinha["dt_vencto"]) ? 'NULL' : '20'.substr($vlinha["dt_vencto"], -2).'-'.substr($vlinha["dt_vencto"], 2, 2).'-'.substr($vlinha["dt_vencto"], 0,2);
		$vlinha["vl_titulo"] 				= substr($linha, 152, 13); 	//    13 Valor do T�tulo
		$vlinha["especie_doc"] 				= substr($linha, 174, 1); 	//    Esp�cie de documento
		$vlinha["vl_despesas_cobranca"]    	= substr($linha, 175, 13) / 100; //  13 Despesas de cobran�a para os
		$vlinha["vl_outras_despesas"] 		= substr($linha, 188, 13) / 100; //  13 Outras despesas
		$vlinha["vl_abatimento"] 			= substr($linha, 227, 13) / 100; //  13 Abatimento Concedido sobre o T�tulo
		$vlinha["vl_descontos"] 			= substr($linha, 240, 13) / 100; //  13 Desconto Concedido
		$vlinha["vl_pagto"] 				= substr($linha, 253, 13) / 100; //  13 Valor Pago
		$vlinha["vl_juros"] 				= substr($linha, 266, 13) / 100; //  13 Valor Juros de Mora
		$vlinha["vl_multa"] 				= substr($linha, 279, 13) / 100; // 13 Valor Multa
		$vlinha["cd_confirmacao"] 	   	    = substr($linha, 294, 1); // Somente para ocorr�ncia �19� 
		$vlinha["cd_motivos_ocorrencia"] 	= rtrim(substr($linha, 318, 10)); //Motivos da ocorr�ncia
		if($vlinha["cd_ocorrencia"] != '28'){
			$vlinha["tx_ocorrencia_erro"] = $this->descricaoMotivosOcorrencia($vlinha["cd_motivos_ocorrencia"]);	
		}else{
			/*Tarifa Bancaria*/
			$vlinha["tx_ocorrencia_erro"] = $this->descricaoMotivosOcorrencia($vlinha["cd_motivos_ocorrencia"], 'tarifa');
		}
		
		$vlinha["dt_credito"]     			= rtrim(substr($linha, 328, 8));
		$vlinha["dt_credito"]     			= empty($vlinha["dt_credito"]) ? date('Y-m-d') : $vlinha["dt_credito"];
		$vlinha["vl_pagto"] 				= $vlinha["vl_pagto"] + $vlinha["vl_juros"];
		$vlinha["vl_creditado"] 			= $vlinha["vl_pagto"] - $vlinha["vl_despesas_cobranca"];
		$vlinha["nr_sequencia"] 			= substr($linha, 394, 6); 		//395 a 400 N� Seq�encial de registro

		return $vlinha;
	}
	/**
	 * [processar9 Registro Trailler]
	 * @param  [type] $linha [description]
	 * @return [type]        [description]
	 */
	private function processar9($linha){

		$vlinha = array();																														
		$vlinha["cd_registro"]  = substr($linha, 0, 1);  		//001 a 001 001 Identifica��o do registro trailer �9�
		$vlinha["cd_retorno"]   = trim(substr($linha, 1, 1));   //002 a 002 001 Identifica��o do arquivo retorno �2�
		$vlinha["tx_retorno"]   = trim(substr($linha, 2, 3));   //003 a 005 003 N�mero do Sicredi �748�
		$vlinha["cd_ctacor"]    = substr($linha, 5, 5); 		//006 a 010 005 C�digo do benefici�rio Conta Corrente sem o DV ou conta benefici�rio
		$vlinha["nr_sequencia"] = substr($linha, 394, 6); 		//395 a 400 N� Seq�encial de registro
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
	public function registroHeader($cd_beneficiario, $cd_cnpj, $dt_gravacao, $nrs, $nsr){
		$this->escreveLinha('0' //001 a 001 Identifica��o do Registro 001
							.'1' //002 a 002 Identifica��o do Arquivo Remessa 001
							.'REMESSA'//003 a 009 Literal Remessa 007
							.'01'//010 a 011 C�digo de Servi�o 002
							.$this->formataCampoString('COBRANCA',15)//012 a 026 Literal Servi�o 015
							.$this->formataCampoNumerico($cd_beneficiario,5)//027 a 031 C�digo do benefici�rio
							.$this->formataCampoNumerico($cd_cnpj,14)//032 a 045
							.$this->espacosBrancos(31)//046 a 076
							.'748'//N�mero do Sicredi
							.$this->formataCampoString('SICREDI',15)//Nome do Banco por Extenso 015
							.$this->formataCampoNumerico($dt_gravacao,8)//095 a 102 AAAAMMDD Data da Grava��o do Arquivo 006
							.$this->espacosBrancos(8)//103 a 110 Branco 008
							.$this->formataCampoNumerico($nrs,7)//111 a 117 N� Seq�encial de Remessa 007
							.$this->espacosBrancos(273)
							.'2.00' //Vers�o do sistema
							.$this->formataCampoNumerico($nsr,6));//395 a 400 N� Seq�encial do Registro de Um em Um 006
	}
	/**
	 * [registroDadosTitulo description]
	 * @param  [type]  $cd_instrucao          [description]
	 * @param  [type]  $cd_carteira           [description]
	 * @param  [type]  $cd_titulo             [description]
	 * @param  [type]  $cd_bloqueto           [description]
	 * @param  [type]  $dt_emissao            [description]
	 * @param  [type]  $dt_vencto             [description]
	 * @param  [type]  $dt_gravacao           [description]
	 * @param  [type]  $vl_titulo             [description]
	 * @param  [type]  $vl_juros              [description]
	 * @param  [type]  $vl_desconto           [description]
	 * @param  [type]  $dt_desconto           [description]
	 * @param  [type]  $vl_abatimento         [description]
	 * @param  [type]  $pc_multa              [description]
	 * @param  [type]  $cpf_cnpj              [description]
	 * @param  [type]  $nm_pessoa             [description]
	 * @param  [type]  $endereco              [description]
	 * @param  [type]  $cep                   [description]
	 * @param  [type]  $nr_sequencia_registro [description]
	 * @param  string  $cpf_cnpj_sacador      [description]
	 * @param  string  $nm_sacador            [description]
	 * @param  integer $nr_parcela            [description]
	 * @param  integer $nr_total_parcelas     [description]
	 * @return [type]                         [description]  
	 */
	public function registroDadosTitulo($cd_instrucao = INSTRUCAO_CADASTRO, $cd_carteira, $cd_tipo_impressao, $cd_titulo, $cd_bloqueto, $dt_emissao, $dt_vencto, $dt_gravacao, 
								$cd_tipo_postagem, $cd_tipo_emissao, $vl_titulo, $vl_juros, $vl_desconto, $dt_desconto, 
								 $vl_abatimento, $pc_multa, $cpf_cnpj, $nm_pessoa, $endereco, $cep, $nr_sequencia_registro, $nr_parcela = '', $nr_total_parcelas = '', $cpf_cnpj_sacador = '', $nm_sacador = ''){

		$this->escreveLinha('1'//001 a 001 Identifica��o do Registro 001
							.'A'//Tipo de cobran�a �A� - Sicredi Com Registro
							.$this->formataCampoString($cd_carteira,1)//009
							.$this->formataCampoString($cd_tipo_impressao,1)//004 //Tipo de Impress�o A normal �B� � Carn�
							.$this->espacosBrancos(12)
							.'A'//Tipo de moeda �A� � Real 
							.'A'//Tipo de desconto �A� � Valor �B� � Percentual
							.'B'//Tipo de juros �A� � Valor �B� � Percentual
							.$this->espacosBrancos(28)
							.$this->formataCampoNumerico($cd_bloqueto,9)//048 056 Nosso n�mero Sicredi
							.$this->espacosBrancos(6)
							.$this->formataCampoNumerico($dt_gravacao,8)//Data da Instru��o AAAAMMDD
							.$this->espacosBrancos(1)
							//.'N'//Postagem do t�tulo
							.$this->formataCampoString($cd_tipo_postagem,1)//�S� - Para postar o t�tulo diretamente ao pagador �N� - N�o postar e remeter o t�tulo para o benefici�rio
							.$this->espacosBrancos(1)
							.$this->formataCampoString($cd_tipo_emissao,1)// �A� � Impress�o � feita pelo Sicredi �B� � Impress�o � feita pelo Benefici�rio
							//.'B'//�B� � Impress�o � feita pelo Benefici�rio
							.$this->formataCampoString($nr_parcela,2)//N�mero da parcela do carn�
							//.$this->espacosBrancos(2)//Na Frugui est� sem
							.$this->formataCampoString($nr_total_parcelas,2)//N�mero total de parcelas do carn�
							//.$this->espacosBrancos(2)//Na Frugui est� sem
							.$this->espacosBrancos(4)
							.$this->formataCampoNumerico($vl_desconto,10)//Valor de desconto por dia de antecipa��o
							.$this->formataCampoNumerico($pc_multa,4)//093 a 096 Percentual de multa 004
							.$this->espacosBrancos(12)
							.$this->formataCampoNumerico($cd_instrucao,2)//INSTRUCAO_CADASTRO constante
							.$this->formataCampoNumerico($cd_titulo,10)//111 a 120 Seu n�mero
							.$this->formataCampoNumerico($dt_vencto,6)//DDMMAA
							.$this->formataCampoNumerico($vl_titulo,13)
							.$this->espacosBrancos(9)
							.$this->formataCampoString(ESPECIE_DOC,1)
							//.'N'//Coloquei N pq no da frugui est� N
							.'S'//Aceite do t�tulo S ou N
							.$this->formataCampoNumerico($dt_emissao,6)//Data de emiss�o DDMMAA
							.'00'//Instru��o de protesto autom�tico  �00� - N�o protestar automaticamente
							.'00'//N�mero de dias p/protesto autom�tico
							.$this->formataCampoNumerico($vl_juros,13)//Valor/% de juros por dia de atraso
							.$this->formataCampoNumerico($dt_desconto,6)//174 a 179 Data Limite P/Concess�o de Desconto 006 DDMMAA
							.$this->formataCampoNumerico($vl_desconto,13)//Valor/% do desconto
							.$this->formataCampoNumerico(0,13)//193 a 205 013 Filler Sempre preencher com zeros neste campo
							.$this->formataCampoNumerico($vl_abatimento,13)
							.$this->formataCampoNumerico($this->identificacaoCPFCNPJ($cpf_cnpj),1)
							.'0'//220 a 220 001 Filler Sempre preencher com zeros neste campo.
							.$this->formataCampoNumerico($cpf_cnpj,14)
							.$this->formataCampoString($nm_pessoa,40)//235 a 274 Nome do Pagador 040
							.$this->formataCampoString($endereco,40)//275 a 314 Endere�o Completo 040
							.'00000'//C�digo do pagador na cooperativa
							.$this->formataCampoNumerico(0,6)//320 a 325 006 Filler Sempre preencher com zeros neste campo.
							.$this->espacosBrancos(1)
							.$this->formataCampoNumerico($cep,8)
							.'00000'//C�digo do Pagador junto ao cliente
							.$this->formataCampoNumerico($cpf_cnpj_sacador,14)//CPF/CNPJ do Sacador Avalista
							.$this->formataCampoString($nm_sacador,41)//Nome do Sacador Avalista
							.$this->formataCampoNumerico($nr_sequencia_registro,6));
	}
	/**
	 * [registroTrailler description]
	 * @param  [type] $nr_sequencia_registro [description]
	 * @param  [type] $cd_ctacor             [description]
	 * @return [type]                        [description]
	 */
	function registroTrailler($nr_sequencia_registro, $cd_ctacor){
		
		$this->cd_ctacor = $cd_ctacor;

		$this->escreveLinha('9'
						    .'1'
						    .'748'
						    .$this->formataCampoNumerico($cd_ctacor,5)
						    .$this->espacosBrancos(384)
						    .$this->formataCampoNumerico($nr_sequencia_registro,6));
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
    /**
     * [GeraNossoNumero description]
     * @param [type] $numero [description]
     */
    public function GeraNossoNumero($agencia, $posto, $conta, $numero) {
        $byteidt             = 2;
        $inicio_nosso_numero = date("y");
        $numero              = sprintf("%05d", $numero);
        $nosso_numero        = $inicio_nosso_numero . $byteidt . $numero;
        $dv_nosso_numero     = $this->DigitoVerificadorNossoNumero("$agencia$posto$conta$nosso_numero");
        $nosso_numero        = "$nosso_numero$dv_nosso_numero";

        return $nosso_numero;
    }
    /**
     * [DigitoVerificadorNossoNumero description]
     * @param [type] $numero [description]
     */
    private function DigitoVerificadorNossoNumero($numero) {
        $resto2 = $this->Modulo11($numero, 9, 1);
         $digito = 11 - $resto2;
         if ($digito > 9 ) {
            $dv = 0;
         } else {
            $dv = $digito;
         }
     return $dv;
    }
    /**
     * [Modulo11 description]
     * @param [type]  $num  [description]
     * @param integer $base [description]
     * @param integer $r    [description]
     */
    private function Modulo11($num, $base=9, $r=0)  {
        $soma = 0;
        $fator = 2;
        /* Separacao dos numeros */
        for ($i = strlen($num); $i > 0; $i--) {
            // pega cada numero isoladamente
            $numeros[$i] = substr($num,$i-1,1);
            // Efetua multiplicacao do numero pelo falor
            $parcial[$i] = $numeros[$i] * $fator;
            // Soma dos digitos
            $soma += $parcial[$i];
            if ($fator == $base) {
                // restaura fator de multiplicacao para 2 
                $fator = 1;
            }
            $fator++;
        }
        /* Calculo do modulo 11 */
        if ($r == 0) {
            $soma *= 10;
            $digito = $soma % 11;
            return $digito;
        } elseif ($r == 1){
            // esta rotina sofrer algumas altera??es para ajustar no layout do SICREDI
            $r_div = (int)($soma/11);
            $digito = ($soma - ($r_div * 11));
            return $digito;
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
		1 - 	CPF
		2 - 	CNPJ
		*/
		return strlen($valor) <= 11 ? '1' : '2'; 
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



