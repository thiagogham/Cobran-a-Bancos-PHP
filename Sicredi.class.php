<?php 

/*
*	Descrição:  Classe para geração e leitura de arquivos retorno remessa em conta Sicredi.
*				Padrão FEBRABAN
* 	Autor: Thiago R. Gham
* 	Versão: 0.1	 18-10-2016
*/
/*
1 = Banco emite e Processa o registro.
2 = Cliente emite e o Banco  somente  processa  o  registro 
*/
define('TIPO_EMISSAO_DOC_BANCO', 1);
define('TIPO_EMISSAO_DOC_CLIENTE', 2);

/*
Este campo só permite usar os seguintes códigos:
01 - Cadastro de título;
02 - Pedido de baixa;
04 - Concessão de abatimento;
05 - Cancelamento de abatimento concedido;
06 - Alteração de vencimento;
09 - Pedido de protesto;
18 - Sustar protesto e baixar título;
19 - Sustar protesto e manter em carteira; 
*/
define('INSTRUCAO_CADASTRO','01');
define('INSTRUCAO_BAIXA'  ,'02');
define('INSTRUCAO_ALTERACAO_VENCIMENTO'  ,'06');
/*
Este campo só permite usar os seguintes códigos:
A - Duplicata Mercantil por Indicação;
B - Duplicata Rural;
C - Nota Promissória;
D - Nota Promissória Rural;
E - Nota de Seguros;
G – Recibo;
H - Letra de Câmbio;
I - Nota de Débito;
J - Duplicata de Serviço por Indicação;
K – Outros.
O – Boleto Proposta
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
									  '06' => 'Liquidação normal',//Sem Registro tbm
									  '09' => 'Baixado automaticamente via arquivo',
									  '10' => 'Baixado conforme instruções da cooperativa de crédito',
									  '12' => 'Abatimento Concedido',
									  '13' => 'Abatimento Cancelado',
									  '14' => 'Vencimento alterado',
									  '15' => 'Liquidação em Cartório',
									  '17' => 'Liquidação após baixa ou Título não registrado',
									  '19' => 'Confirmação de recebimento de instrução de protesto',
									  '20' => 'Confirmação de recebimento de instrução de sustação de protesto',
									  '23' => 'Entrada de título em cartório',
									  '24' => 'Entrada rejeitada por CEP irregular',
									  '27' => 'Baixa Rejeitada',
									  '28' => 'Tarifa',//Sem Registro tbm
									  '30' => 'Alteração Rejeitada',
									  '32' => 'Instrução Rejeitada',
									  '33' => 'Confirmação de pedido de alteração de outros dados',
									  '34' => 'Retirado de cartório e manutenção em carteira',
									  '35' => 'Aceite do pagador');
	/**
	 * [$CD_MOTIVO_RETORNO description]
	 * @var array
	 */
	static $CD_MOTIVO_RETORNO = array(	'01' => 'Código do banco inválido',
										'02' => 'Código do registro detalhe inválido',
										'03' => 'Código da ocorrência inválido',
										'04' => 'Código de ocorrência não permitida para a carteira',
										'05' => 'Código de ocorrência não numérico',
										'07' => 'Cooperativa/agência/conta/dígito inválidos',
										'08' => 'Nosso número inválido',
										'09' => 'Nosso número duplicado',
										'10' => 'Carteira inválida',
										'14' => 'Título protestado',
										'15' => 'Cooperativa/carteira/agência/conta/nosso número inválidos',
										'16' => 'Data de vencimento inválida',
										'17' => 'Data de vencimento anterior à data de emissão',
										'18' => 'Vencimento fora do prazo de operação',
										'20' => 'Valor do título inválido',
										'21' => 'Espécie do título inválida',
										'22' => 'Espécie não permitida para a carteira',
										'24' => 'Data de emissão inválida',
										'29' => 'Valor do desconto maior/igual ao valor do título',
										'31' => 'Concessão de desconto - existe desconto anterior',
										'33' => 'Valor do abatimento inválido',
										'34' => 'Valor do abatimento maior/igual ao valor do título',
										'36' => 'Concessão de abatimento - existe abatimento anterior',
										'38' => 'Prazo para protesto inválido',
										'39' => 'Pedido para protesto não permitido para o título',
										'40' => 'Título com ordem de protesto emitida',
										'41' => 'Pedido cancelamento/sustação sem instrução de protesto',
										'44' => 'Cooperativa de crédito/agência beneficiária não prevista',
										'45' => 'Nome do pagador inválido',
										'46' => 'Tipo/número de inscrição do pagador inválidos',
										'47' => 'Endereço do pagador não informado',
										'48' => 'CEP irregular',
										'49' => 'Número de Inscrição do pagador/avalista inválido',
										'50' => 'Pagador/avalista não informado',
										'60' => 'Movimento para título não cadastrado',
										'63' => 'Entrada para título já cadastrado',
										'A'  => 'Aceito',
										'D'  => 'Desprezado',
										'A1' => 'Praça do pagador não cadastrada',
										'A2' => 'Tipo de cobrança do título divergente com a praça do pagador',
										'A3' => 'Cooperativa/agência depositária divergente: atualiza o cadastro de praças da Coop./agência beneficiária',
										'A4' => 'Beneficiário não cadastrado ou possui CGC/CIC inválido',
										'A5' => 'Pagador não cadastrado',
										'A6' => 'Data da instrução/ocorrência inválida',
										'A7' => 'Ocorrência não pode ser comandada',
										'A8' => 'Recebimento da liquidação fora da rede Sicredi - via compensação eletrônica',
										'B4' => 'Tipo de moeda inválido',
										'B5' => 'Tipo de desconto/juros inválido',
										'B6' => 'Mensagem padrão não cadastrada',
										'B7' => 'Seu número inválido',
										'B8' => 'Percentual de multa inválido',
										'B9' => 'Valor ou percentual de juros inválido',
										'C1' => 'Data limite para concessão de desconto inválida',
										'C2' => 'Aceite do título inválido',
										'C3' => 'Campo alterado na instrução “31 – alteração de outros dados” inválido',
										'C4' => 'Título ainda não foi confirmado pela centralizadora',
										'C5' => 'Título rejeitado pela centralizadora',
										'C6' => 'Título já liquidado',
										'C7' => 'Título já baixado',
										'C8' => 'Existe mesma instrução pendente de confirmação para este título',
										'C9' => 'Instrução prévia de concessão de abatimento não existe ou não confirmada',
										'D1' => 'Título dentro do prazo de vencimento (em dia)',
										'D2' => 'Espécie de documento não permite protesto de título',
										'D3' => 'Título possui instrução de baixa pendente de confirmação',
										'D4' => 'Quantidade de mensagens padrão excede o limite permitido',
										'D5' => 'Quantidade inválida no pedido de boletos pré-impressos da cobrança sem registro',
										'D6' => 'Tipo de impressão inválida para cobrança sem registro',
										'D7' => 'Cidade ou Estado do pagador não informado',
										'D8' => 'Seqüência para composição do nosso número do ano atual esgotada',
										'D9' => 'Registro mensagem para título não cadastrado',
										'E2' => 'Registro complementar ao cadastro do título da cobrança com e sem registro não cadastrado',
										'E3' => 'Tipo de postagem inválid. o, diferente de . S, N e branco',
										'E4' => 'Pedido de boletos pré-impressos',
										'E5' => 'Confirmação/rejeição para pedidos de boletos não cadastrado',
										'E6' => 'Pagador/avalista não cadastrado',
										'E7' => 'Informação para atualização do valor do título para protesto inválido',
										'E8' => 'Tipo de impressão inválid. o, diferente de . A, B e branco',
										'E9' => 'Código do pagador do título divergente com o código da cooperativa de crédito',
										'F1' => 'Liquidado no sistema do cliente',
										'F2' => 'Baixado no sistema do cliente',
										'F3' => 'Instrução inválid. a, este título está caucionado/descontado',
										'F4' => 'Instrução fixa com caracteres inválidos',
										'F6' => 'Nosso número / número da parcela fora de seqüência – total de parcelas inválido',
										'F7' => 'Falta de comprovante de prestação de serviço',
										'F8' => 'Nome do beneficiário incompleto / incorreto',
										'F9' => 'CNPJ / CPF incompatível com o nome do pagador / Sacador Avalista',
										'G1' => 'CNPJ / CPF do pagador Incompatível com a espécie',
										'G2' => 'Título aceito: sem a assinatura do pagador',
										'G3' => 'Título aceito: rasurado ou rasgado',
										'G4' => 'Título aceito: falta título (cooperativa/ag. beneficiária deverá enviá-lo)',
										'G5' => 'Praça de pagamento incompatível com o endereço',
										'G6' => 'Título aceito: sem endosso ou beneficiário irregular',
										'G7' => 'Título aceito: valor por extenso diferente do valor numérico',
										'G8' => 'Saldo maior que o valor do título',
										'G9' => 'Tipo de endosso inválido',
										'H1' => 'Nome do pagador incompleto / Incorreto',
										'H2' => 'Sustação judicial',
										'H3' => 'Pagador não encontrado',
										'H4' => 'Alteração de carteira',
										'H5' => 'Recebimento de liquidação fora da rede Sicredi – VLB Inferior – Via Compensação',
										'H6' => 'Recebimento de liquidação fora da rede Sicredi – VLB Superior – Via Compensação',
										'H7' => 'Espécie de documento necessita beneficiário ou avalista PJ',
										'H8' => 'Recebimento de liquidação fora da rede Sicredi – Contingência Via Compe',
										'H9' => 'Dados do título não conferem com disquete',
										'I1' => 'Pagador e Sacador Avalista são a mesma pessoa',
										'I2' => 'Aguardar um dia útil após o vencimento para protestar',
										'I3' => 'Data do vencimento rasurada',
										'I4' => 'Vencimento – extenso não confere com número',
										'I5' => 'Falta data de vencimento no título',
										'I6' => 'DM/DMI sem comprovante autenticado ou declaração',
										'I7' => 'Comprovante ilegível para conferência e microfilmagem',
										'I8' => 'Nome solicitado não confere com emitente ou pagador',
										'I9' => 'Confirmar se são 2 emitentes. Se si. m, indicar os dados dos 2',
										'J1' => 'Endereço do pagador igual ao do pagador ou do portador',
										'J2' => 'Endereço do apresentante incompleto ou não informado',
										'J3' => 'Rua/número inexistente no endereço',
										'J4' => 'Falta endosso do favorecido para o apresentante',
										'J5' => 'Data da emissão rasurada',
										'J6' => 'Falta assinatura do pagador no título',
										'J7' => 'Nome do apresentante não informado/incompleto/incorreto',
										'J8' => 'Erro de preenchimento do titulo',
										'J9' => 'Titulo com direito de regresso vencido',
										'K1' => 'Titulo apresentado em duplicidade',
										'K2' => 'Titulo já protestado',
										'K3' => 'Letra de cambio vencida – falta aceite do pagador',
										'K4' => 'Falta declaração de saldo assinada no título',
										'K5' => 'Contrato de cambio – Falta conta gráfica',
										'K6' => 'Ausência do documento físico',
										'K7' => 'Pagador falecido',
										'K8' => 'Pagador apresentou quitação do título',
										'K9' => 'Título de outra jurisdição territorial',
										'L1' => 'Título com emissão anterior a concordata do pagador',
										'L2' => 'Pagador consta na lista de falência',
										'L3' => 'Apresentante não aceita publicação de edital',
										'L4' => 'Dados do Pagador em Branco ou inválido',
										'L5' => 'Código do Pagador na agência beneficiária está duplicado',
										'M1' => 'Reconhecimento da dívida pelo pagador',
										'M2' => 'Não reconhecimento da dívida pelo pagador',
										'X1' => 'Regularização centralizadora – Rede Sicredi',
										'X2' => 'Regularização centralizadora – Compensação',
										'X3' => 'Regularização centralizadora – Banco correspondente',
										'X4' => 'Regularização centralizadora - VLB Inferior - via compensação',
										'X5' => 'Regularização centralizadora - VLB Superior - via compensação',
										'X0' => 'Pago com cheque',
										'X6' => 'Pago com cheque – bloqueado 24 horas',
										'X7' => 'Pago com cheque – bloqueado 48 horas',
										'X8' => 'Pago com cheque – bloqueado 72 horas',
										'X9' => 'Pago com cheque – bloqueado 96 horas',
										'XA' => 'Pago com cheque – bloqueado 120 horas',
										'XB' => 'Pago com cheque – bloqueado 144 horas');
	/**
	 * [$CD_MOTIVO_TARIFA description]
	 * @var array
	 */
	static $CD_MOTIVO_TARIFA = array(	'03' => 'Tarifa de sustação',
										'04' => 'Tarifa de protesto',
										'08' => 'Tarifa de custas de protesto',
										'A9' => 'Tarifa de manutenção de título vencido',
										'B1' => 'Tarifa de baixa da carteira',
										'B3' => 'Tarifa de registro de entrada do título',
										'F5' => 'Tarifa de entrada na rede Sicredi');

	/*
	*	Descrição: Armazena linhas do registro.
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
	*	Descrição: Armazena linhas do registro.
	* 	@param string $linha String contendo a linha.
	* 	@return 
	*/
	function salvaArquivo($caminho) {
		/*
		CCCCC = código beneficiário
		MDD = cód. do mês e nº do dia da data de geração do arquivo
		CRM = Indica que é o 1º arquivo remessa
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
	*	Descrição: Executa o processamento de todo o arquivo, linha a linha
	* 	@param fiel $file Arquivo a ser processado
	* 	@return array Retorna um vetor contendo os dados das linhas do arquivo.*/
	public function lerArquivoRetorno($file = NULL){
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
	public function processarLinha($linha) {
		if(trim($linha) == '') {
			die('A linha está vazia.');
		}
		/*Identificação do Registro*/
		$processar = 'processar'.substr($linha,0,1);
		if(method_exists('Sicredi', $processar)){
			return $this->$processar($linha);
		}else{
			die('Metodo não existe: '.$processar);
		}
	}
	/*
	*	Descrição: Processa a linha header do arquivo
	* 	@param string $linha Linha do header de arquivo processado 
	* 	@return array Retorna um vetor contendo os dados dos campos do header do arquivo.
	*/
	private function processar0($linha){
		$vlinha = array();																														
		$vlinha["cd_registro"]  = substr($linha, 0, 1);  		//001 a 001 Identificação do Registro 0
		$vlinha["cd_retorno"]   = trim(substr($linha, 1, 1));   //002 a 002 Identificação do Arquivo Retorno 2
		$vlinha["tx_retorno"]   = trim(substr($linha, 2, 7));   //003 a 009 Literal Retorno 007 RETORNO
		$vlinha["cd_servico"]   = substr($linha, 9, 2); 		//010 a 011 Código do Serviço 002 01
		$vlinha["tx_servico"]   = trim(substr($linha, 11, 15)); //012 a 026 Literal Serviço 015 COBRANÇA
		$vlinha["cd_ctacor"]    = substr($linha, 26, 5); 		//027 a 031 005 Código do beneficiário 
		$vlinha["cd_cnpj"]      = substr($linha, 31, 14); 		//032 a 045 014 CIC/CGC do beneficiário 
		$vlinha["cd_banco"]     = substr($linha, 76, 3); 		//077 a 079 003 Número do Sicredi
		$vlinha["nm_banco"]     = trim(substr($linha, 79, 15));	//080 a 094 015 Literal BANSicredi 
		$vlinha["dt_emissao"]   = substr($linha, 94, 8);		//095 a 102 Data da Gravação do Arquivo AAAAMMDD
		$vlinha["nr_remessa"]   = substr($linha, 110, 7);	 	//111 a 117 007 Número do retorno
		$vlinha["cd_versao"]    = substr($linha, 389, 6); 		//390 a 394 005 Versão do sistema 99.99
		$vlinha["nr_sequencia"] = substr($linha, 394, 6); 		//395 a 400 Nº Seqüencial de registro
		return $vlinha;
	}
	/*
	*	Descrição: Processa a linha header do arquivo
	* 	Obs.: Quando se tratar de cobrança sem registro, somente os campos assinalados com (*) SEM REGISTRO
	*	asterisco, serão confirmados no Arquivo - Retorno.
	* 	@param string $linha Linha do header de arquivo processado 
	* 	@return array Retorna um vetor contendo os dados dos campos do header do arquivo.
	*/
	private function processar1($linha){
		$vlinha = array();																														
		
		$vlinha["cd_registro"]  			= substr($linha, 0, 1); 	// (*)  1  Identificação do Registro
		$vlinha["tipo_cobranca"]  			= substr($linha, 13, 1); 	//014 a 014 001 Tipo de cobrança “A” - Sicredi Cobrança com registro
		$vlinha["cd_pagador"]    			= substr($linha, 14, 5); 	//015 a 019 005 Código do pagador na cooperativa do
		$vlinha["cd_pessoa"] 				= substr($linha, 19, 05); 	//020 a 024 005 Código do pagador junto ao associado
		$vlinha["tp_boleto"] 				= substr($linha, 24, 1); 	//025 a 025 001 Boleto DDA
		$vlinha["cd_bloqueto"] 				= rtrim(substr($linha, 47, 15)); 	//048 a 062 015 Nosso número Sicredi sem edição
		$vlinha["cd_ocorrencia"]			= substr($linha, 108, 2); 	// (*) 2 Identificação de Ocorrência
		$vlinha["tx_ocorrencia"]  			= $this->descricaoRetornoBanco($vlinha["cd_ocorrencia"]);
		$vlinha["dt_ocorrencia"]			= substr($linha, 110, 6); 	// (*) 6 Data Ocorrência no Banco DDMMAA
		$vlinha["dt_ocorrencia"] 			= empty($vlinha["dt_ocorrencia"]) ? 'NULL' : '20'.substr($vlinha["dt_ocorrencia"], -2).'-'.substr($vlinha["dt_ocorrencia"], 2, 2).'-'.substr($vlinha["dt_ocorrencia"], 0,2);
		$vlinha["dt_pagto"] 				= $vlinha["dt_ocorrencia"];
		$vlinha["cd_titulo"] 				= substr($linha, 116, 10); 	//    10 Número do Documento titulo
		$vlinha["dt_vencto"] 				= rtrim(substr($linha, 146, 6)); 	//    6 Data Vencimento do Título DDMMAA 
		$vlinha["dt_vencto"] 				= empty($vlinha["dt_vencto"]) ? 'NULL' : '20'.substr($vlinha["dt_vencto"], -2).'-'.substr($vlinha["dt_vencto"], 2, 2).'-'.substr($vlinha["dt_vencto"], 0,2);
		$vlinha["vl_titulo"] 				= substr($linha, 152, 13); 	//    13 Valor do Título
		$vlinha["especie_doc"] 				= substr($linha, 174, 1); 	//    Espécie de documento
		$vlinha["vl_despesas_cobranca"]    	= substr($linha, 175, 13) / 100; //  13 Despesas de cobrança para os
		$vlinha["vl_outras_despesas"] 		= substr($linha, 188, 13) / 100; //  13 Outras despesas
		$vlinha["vl_abatimento"] 			= substr($linha, 227, 13) / 100; //  13 Abatimento Concedido sobre o Título
		$vlinha["vl_descontos"] 			= substr($linha, 240, 13) / 100; //  13 Desconto Concedido
		$vlinha["vl_pagto"] 				= substr($linha, 253, 13) / 100; //  13 Valor Pago
		$vlinha["vl_juros"] 				= substr($linha, 266, 13) / 100; //  13 Valor Juros de Mora
		$vlinha["vl_multa"] 				= substr($linha, 279, 13) / 100; // 13 Valor Multa
		$vlinha["cd_confirmacao"] 	   	    = substr($linha, 294, 1); // Somente para ocorrência “19” 
		$vlinha["cd_motivos_ocorrencia"] 	= rtrim(substr($linha, 318, 10)); //Motivos da ocorrência
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
		$vlinha["nr_sequencia"] 			= substr($linha, 394, 6); 		//395 a 400 Nº Seqüencial de registro

		return $vlinha;
	}
	/**
	 * [processar9 Registro Trailler]
	 * @param  [type] $linha [description]
	 * @return [type]        [description]
	 */
	private function processar9($linha){

		$vlinha = array();																														
		$vlinha["cd_registro"]  = substr($linha, 0, 1);  		//001 a 001 001 Identificação do registro trailer “9”
		$vlinha["cd_retorno"]   = trim(substr($linha, 1, 1));   //002 a 002 001 Identificação do arquivo retorno “2”
		$vlinha["tx_retorno"]   = trim(substr($linha, 2, 3));   //003 a 005 003 Número do Sicredi “748”
		$vlinha["cd_ctacor"]    = substr($linha, 5, 5); 		//006 a 010 005 Código do beneficiário Conta Corrente sem o DV ou conta beneficiário
		$vlinha["nr_sequencia"] = substr($linha, 394, 6); 		//395 a 400 Nº Seqüencial de registro
		return $vlinha;
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
	public function registroHeader($cd_beneficiario, $cd_cnpj, $dt_gravacao, $nrs, $nsr){
		$this->escreveLinha('0' //001 a 001 Identificação do Registro 001
							.'1' //002 a 002 Identificação do Arquivo Remessa 001
							.'REMESSA'//003 a 009 Literal Remessa 007
							.'01'//010 a 011 Código de Serviço 002
							.$this->formataCampoString('COBRANCA',15)//012 a 026 Literal Serviço 015
							.$this->formataCampoNumerico($cd_beneficiario,5)//027 a 031 Código do beneficiário
							.$this->formataCampoNumerico($cd_cnpj,14)//032 a 045
							.$this->espacosBrancos(31)//046 a 076
							.'748'//Número do Sicredi
							.$this->formataCampoString('SICREDI',15)//Nome do Banco por Extenso 015
							.$this->formataCampoNumerico($dt_gravacao,8)//095 a 102 AAAAMMDD Data da Gravação do Arquivo 006
							.$this->espacosBrancos(8)//103 a 110 Branco 008
							.$this->formataCampoNumerico($nrs,7)//111 a 117 Nº Seqüencial de Remessa 007
							.$this->espacosBrancos(273)
							.'2.00' //Versão do sistema
							.$this->formataCampoNumerico($nsr,6));//395 a 400 Nº Seqüencial do Registro de Um em Um 006
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

		$this->escreveLinha('1'//001 a 001 Identificação do Registro 001
							.'A'//Tipo de cobrança “A” - Sicredi Com Registro
							.$this->formataCampoString($cd_carteira,1)//009
							.$this->formataCampoString($cd_tipo_impressao,1)//004 //Tipo de Impressão A normal “B” – Carnê
							.$this->espacosBrancos(12)
							.'A'//Tipo de moeda “A” – Real 
							.'A'//Tipo de desconto “A” – Valor “B” – Percentual
							.'B'//Tipo de juros “A” – Valor “B” – Percentual
							.$this->espacosBrancos(28)
							.$this->formataCampoNumerico($cd_bloqueto,9)//048 056 Nosso número Sicredi
							.$this->espacosBrancos(6)
							.$this->formataCampoNumerico($dt_gravacao,8)//Data da Instrução AAAAMMDD
							.$this->espacosBrancos(1)
							//.'N'//Postagem do título
							.$this->formataCampoString($cd_tipo_postagem,1)//“S” - Para postar o título diretamente ao pagador “N” - Não postar e remeter o título para o beneficiário
							.$this->espacosBrancos(1)
							.$this->formataCampoString($cd_tipo_emissao,1)// “A” – Impressão é feita pelo Sicredi “B” – Impressão é feita pelo Beneficiário
							//.'B'//“B” – Impressão é feita pelo Beneficiário
							.$this->formataCampoString($nr_parcela,2)//Número da parcela do carnê
							//.$this->espacosBrancos(2)//Na Frugui está sem
							.$this->formataCampoString($nr_total_parcelas,2)//Número total de parcelas do carnê
							//.$this->espacosBrancos(2)//Na Frugui está sem
							.$this->espacosBrancos(4)
							.$this->formataCampoNumerico($vl_desconto,10)//Valor de desconto por dia de antecipação
							.$this->formataCampoNumerico($pc_multa,4)//093 a 096 Percentual de multa 004
							.$this->espacosBrancos(12)
							.$this->formataCampoNumerico($cd_instrucao,2)//INSTRUCAO_CADASTRO constante
							.$this->formataCampoNumerico($cd_titulo,10)//111 a 120 Seu número
							.$this->formataCampoNumerico($dt_vencto,6)//DDMMAA
							.$this->formataCampoNumerico($vl_titulo,13)
							.$this->espacosBrancos(9)
							.$this->formataCampoString(ESPECIE_DOC,1)
							//.'N'//Coloquei N pq no da frugui está N
							.'S'//Aceite do título S ou N
							.$this->formataCampoNumerico($dt_emissao,6)//Data de emissão DDMMAA
							.'00'//Instrução de protesto automático  “00” - Não protestar automaticamente
							.'00'//Número de dias p/protesto automático
							.$this->formataCampoNumerico($vl_juros,13)//Valor/% de juros por dia de atraso
							.$this->formataCampoNumerico($dt_desconto,6)//174 a 179 Data Limite P/Concessão de Desconto 006 DDMMAA
							.$this->formataCampoNumerico($vl_desconto,13)//Valor/% do desconto
							.$this->formataCampoNumerico(0,13)//193 a 205 013 Filler Sempre preencher com zeros neste campo
							.$this->formataCampoNumerico($vl_abatimento,13)
							.$this->formataCampoNumerico($this->identificacaoCPFCNPJ($cpf_cnpj),1)
							.'0'//220 a 220 001 Filler Sempre preencher com zeros neste campo.
							.$this->formataCampoNumerico($cpf_cnpj,14)
							.$this->formataCampoString($nm_pessoa,40)//235 a 274 Nome do Pagador 040
							.$this->formataCampoString($endereco,40)//275 a 314 Endereço Completo 040
							.'00000'//Código do pagador na cooperativa
							.$this->formataCampoNumerico(0,6)//320 a 325 006 Filler Sempre preencher com zeros neste campo.
							.$this->espacosBrancos(1)
							.$this->formataCampoNumerico($cep,8)
							.'00000'//Código do Pagador junto ao cliente
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
	* 	@return integer Retorna check-digit (módulo 10)
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



