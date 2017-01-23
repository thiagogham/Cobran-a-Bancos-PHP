<?php 
 
/*
*	Descrição:  Classe para geração e leitura de arquivos retorno remessa em conta Itau.
*				Padrão FEBRABAN
* 	Autor: Thiago R. Gham
* 	Versão: 0.1	 25-11-2015
	
	LAYOUT DOS RESGISTROS                                                          
	Header 		- Tipo 0 
	Transação	- Tipo 1 – Dados do Título
	Transação	- Tipo 1 – Dados do Sacador
	Transação	- Tipo 2 – Mensagem (Opcional)
	Transação	- Tipo 3 – Rateio de Crédito (Opcional)
	TRAILLER	- Tipo 9                                           
*/

define('PROCESSAMENTO_TESTE', 'X');
define('PROCESSAMENTO_PRODUCAO', 'P');

define('TIPO_DOC_COBRANCA_DIRETA', '04');
define('TIPO_DOC_COBRANCA_ESCRITUTAL', '06');
define('TIPO_DOC_COBRANCA_CCB', '08');
define('TIPO_DOC_COBRANCA_TERCEIROS', '09');

define('ACEITO', 'A');
define('NAOACEITO', 'N');

define('MORA_DIARIA', 0);
define('MORA_MENSAL', 1);

define('TIPO_CARTEIRA',1);
/*
1 – Cobrança Simples (8050.76)
3 – Cobrança Caucionada (8150.55) Reservado
4 – Cobrança em IGPM (8450.94) *
5 – Cobrança Caucionada CGB Especial (8355.01) Reservado
6 – Cobrança Simples Seguradora (8051.57)
7 – Cobrança em UFIR (8257.86) *
8 – Cobrança em IDTR (8356.84) *
C – Cobrança Vinculada (8250.34)
D – Cobrança CSB (8258.67)
E – Cobrança Caucionada Câmbio (8156.24)
F – Cobrança Vendor (8152.17) Reservado
H – Cobrança Caucionada Dólar (8157.05) Reservado **
I – Cobrança Caucionada Compror (8351.46) Reservado
K – Cobrança Simples INCC-M (8153.06)
M – Cobrança Partilhada (8154.70)
N – Capital de Giro CGB ICM (6130.96) Reservado
R – Desconto de Duplicata (6030.15) ***
S – Vendor Eletrônico – Valor Final (Corrigido) (6032.79) ***
X – Vendor BDL – Valor Inicial (Valor da NF) (6034.30) ***
*/
define('OCORRENCIA_REMESSA','01');
define('OCORRENCIA_BAIXA'  ,'02');
/*
01 REMESSA
02 PEDIDO DE BAIXA
04 CONCESSÃO DE ABATIMENTO (INDICADOR 12.5) VALOR DO ABATIMENTO
05 CANCELAMENTO DE ABATIMENTO VALOR DO ABATIMENTO
06 ALTERAÇÃO DO VENCIMENTO VENCIMENTO
07 ALTERAÇÃO DO USO DA EMPRESA USO DA EMPRESA
08 ALTERAÇÃO DO SEU NÚMERO SEU NÚMERO
09 PROTESTAR
10 NÃO PROTESTAR
11 PROTESTO PARA FINS FALIMENTARES
18 SUSTAR O PROTESTO
30 EXCLUSÃO DE SACADOR AVALISTA
31 ALTERAÇÃO DE OUTROS DADOS CAMPOS A ALTERAR
34 BAIXA POR TER SIDO PAGO DIRETAMENTE AO BENEFICIÁRIO
35 CANCELAMENTO DE INSTRUÇÃO CÓDIGO DA INSTRUÇÃO
37 ALTERAÇÃO DO VENCIMENTO E SUSTAR PROTESTO VENCIMENTO
38 BENEFICIÁRIO NÃO CONCORDA COM ALEGAÇÃO DO PAGADOR CÓDIGO DA ALEGAÇÃO
47 BENEFICIÁRIO SOLICITA DISPENSA DE JUROS
49 ALTERAÇÃO DE DADOS EXTRAS (REGISTRO DE MULTA)
66 ENTRADA EM NEGATIVAÇÃO EXPRESSA
67 NÃO NEGATIVAR (INIBE A ENTRADA EM NEGATIVAÇÃO EXPRESSA)
68 EXCLUIR NEGATIVAÇÃO EXPRESSA (ATÉ 15 DIAS CORRIDOS APÓS A ENTRADA EMNEGATIVAÇÃO EXPRESSA)
69 CANCELAR NEGATIVAÇÃO EXPRESSA (APÓS TÍTULO TER SIDO NEGATIVADO)
93 DESCONTAR TÍTULOS ENCAMINHADOS NO DIA
*/

define('ESPECIE', '06');
/*
01 DUPLICATA MERCANTIL
02 NOTA PROMISSÓRIA 
03 NOTA DE SEGURO
04 MENSALIDADE ESCOLAR
05 RECIBO
06 CONTRATO
07 COSSEGUROS
08 DUPLICATA DE SERVIÇO
09 LETRA DE CÂMBIO
13 NOTA DE DÉBITOS
15 DOCUMENTO DE DÍVIDA
16 ENCARGOS CONDOMINIAIS
17 CONTA DE PRESTAÇÃO DE SERVIÇOS
18 BOLETO DE PROPOSTA*
99 DIVERSOS
*/
class Itau{
	
	private $STRING = '';

	static $CD_RETORNO_BANCO =  array('02' => 'Confirmação de entrada',
									  '2'  => 'Confirmação de entrada',
									  '03' => 'Entrada rejeitada',
									  '3'  => 'Entrada rejeitada',
									  '04' => 'Baixa de título liquidado por edital',
									  '6'  => 'Liquidação normal',
									  '06' => 'Liquidação normal',
									  '07' => 'Liquidação parcial',
									  '08' => 'Baixa por pagamento, liquidação pelo saldo',
									  '09' => 'Devolução automática',
									  '9'  => 'Devolução automática',
									  '10' => 'Baixado conforme instruções',
									  '16' => 'Alteração de dados (*)',
									  '18' => 'Alteração de instruções (*)',
									  '44' => 'Manutenção mensal',
									  '42' => 'Alteração de título',);

	static $CD_RETORNO_BANCO_ERRO =  array(	  '01' => 'Código do Banco inválido',
											  '02' => 'Agência/Conta/Número de controle – Inválido',
											  '04' => 'Código do movimento não permitido para a carteira',
											  '05' => 'Código do movimento inválido',
											  '07' => 'Liquidação parcial',
											  '08' => 'Nosso Número inválido',
											  '09' => 'Nosso Número duplicado',
											  '10' => 'Carteira inválida',
											  '16' => 'Data de vencimento inválida',
											  '18' => 'Data de vencimento anterior à data de emissão',
											  '20' => 'Valor do título inválido (não numérico)',
											  '48' => 'CEP inválido ou alteração de CEP não permitida',
											  '42' => 'Código para baixa/devolução ou instrução inválido – verifica se o código é branco, 0, 1 ou 2',
											  '60' => 'Movimento para título não cadastrado – alteração ou devolução');
	/*
	*	Descrição: Armazena linhas do registro.
	* 	@param string $linha String contendo a linha.
	* 	@return string $codigo
	*/
	public function descricaoRetornoBanco($codigo) {
		return Itau::$CD_RETORNO_BANCO[$codigo];
	}
	/*
	*	Descrição: Armazena linhas do registro.
	* 	@param string $linha String contendo a linha.
	* 	@return string $codigo
	*/
	public function descricaoErroRetornoBanco($codigo) {
		return Itau::$CD_RETORNO_BANCO_ERRO[$codigo];
	}
	
	/*
	*	Descrição: Armazena linhas do registro.
	* 	@param string $linha String contendo a linha.
	* 	@return 
	*/
	function salvaArquivo($caminho) {
		//chmod($caminho, '0755');
		$nm_arquivo = "Itau".date('YmdHi').".txt";
		$caminho    = $caminho . $nm_arquivo;
		file_put_contents($caminho, $this->STRING);
		return $caminho;
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
			die('Não foi possível abrir o arquivo '.$file);
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
		if(method_exists('Itau', $processar)){
			return $this->$processar($linha);
		}
	}
	/*
	*	Descrição: Processa a linha header do arquivo
	* 	@param string $linha Linha do header de arquivo processado 
	* 	@return array Retorna um vetor contendo os dados dos campos do header do arquivo.
	*/
	function processarErro($linha){
		$vlinha = array();																														
		$vlinha["erro"]  = rtrim($linha);
		return $vlinha;
	}
	/*
	*	Descrição: Processa a linha header do arquivo
	* 	@param string $linha Linha do header de arquivo processado 
	* 	@return array Retorna um vetor contendo os dados dos campos do header do arquivo.
	*/
	private function processarC($linha){
		$vlinha = array();																														
		$vlinha["constante1"]  = substr($linha, 0, 19);
		return $vlinha;
	}
	/*
	*	Descrição: Processa a linha header do arquivo
	* 	@param string $linha Linha do header de arquivo processado 
	* 	@return array Retorna um vetor contendo os dados dos campos do header do arquivo.
	*/
	private function processar0($linha){
		$vlinha = array();																														
		$vlinha["constante1"]  = substr($linha, 0, 19);
		$vlinha["cd_agencia"]  = substr($linha, 26, 4); 
		$vlinha["cd_ctacor"]   = substr($linha, 32, 5) . "-" . substr($linha, 37, 1);
		$vlinha["nm_empresa"]  = trim(substr($linha, 46, 30));
		$vlinha["nm_empresa"]  = trim(substr($linha, 46, 30));
		return $vlinha;
	}
	/*
	*	Descrição: Processa a linha header do arquivo
	* 	@param string $linha Linha do header de arquivo processado 
	* 	@return array Retorna um vetor contendo os dados dos campos do header do arquivo.
	*/
	private function processar1($linha){
		/*
		$cd_agencia				= substr($TX_LINHA,17,4);
					$cd_cedente				= substr($TX_LINHA,23,5);
					$pgano					= "20".substr($TX_LINHA,114,2);
					$pgmes					= substr($TX_LINHA,112,2);
					$pgdia					= substr($TX_LINHA,110,2);
					$cd_arquivo				= $cd_arquivo;
					$nr_linha				= substr($TX_LINHA,396,6);
					$cd_bloqueto			= substr($TX_LINHA,62,8);
					$cd_bloqueto			= trim(sprintf("%8d",$cd_bloqueto));
					$dt_pagto				= "20".substr($TX_LINHA,114,2)."-".substr($TX_LINHA,112,2)."-". substr($TX_LINHA,110,2);
					$cd_banco_cobrador		= substr($TX_LINHA,165,3);
					$cd_agencia_cobradora	= substr($TX_LINHA,168,5);
					$vl_despesas_cobranca	= substr($TX_LINHA,175,13) / 100;
					$vl_outras_despesas		= "0.00"; // NAO TEM NO MANUAL
					$vl_abatimento			= substr($TX_LINHA,227,13) / 100;
					$vl_descontos			= substr($TX_LINHA,240,13) / 100;
					$vl_pagto				= substr($TX_LINHA,253,13) / 100;
					$vl_juros				= substr($TX_LINHA,266,13) / 100;
					$vl_outros_recebimentos	= substr($TX_LINHA,279,13) / 100;
					$vl_pagto				= $vl_pagto + $vl_juros;
					$dt_credito				= "20".substr($TX_LINHA,299,2)."-".substr($TX_LINHA,297,2)."-". substr($TX_LINHA,295,2);
					$dm_moeda				= +substr($TX_LINHA,342,1);
					$vl_creditado			= $vl_pagto;
					$vl_pagto				= $vl_pagto + $vl_despesas_cobranca;
					$dt_pagto_aux		    = $dt_pagto;
		 */
		$vlinha = array();																														
		$vlinha["inscricao"]  	  			= substr($linha, 1, 2); 		//002 003 TIPO DE INSCRIÇÃO: 01 – CPF; 02 – CNPJ.
		$vlinha["cpf_cnpj"]   	  			= substr($linha, 3, 14); 		//004 017 cpf_cnpj "09110655000197"
		$vlinha["cd_agencia1"]    			= substr($linha, 17, 3); 	//018 030 CÓDIGO DE CEDENTE AAAACCCCCCCCC 0943
		$vlinha["cd_cedente"]  	  			= substr($linha, 20, 9); 	//018 030 CÓDIGO DE CEDENTE AAAACCCCCCCCC 078026067
		$vlinha["tipo_cobranca"]  			= substr($linha, 29, 6); 	//031 036 ESPÉCIE DE COBRANÇA 80507
		$vlinha["cd_banco_cobrador"]    	= substr($linha, 165, 3); 
		$vlinha["cd_agencia_cobradora"]    	= substr($linha, 169, 4);
		$vlinha["dm_moeda"]    				=+substr($linha, 342, 1); 
		$vlinha["vl_despesas_cobranca"]    	= substr($linha,175,13) / 100;
		$vlinha["vl_outras_despesas"] 		= substr($linha,188,13) / 100;
		$vlinha["vl_abatimento"] 			= substr($linha,227,13) / 100;
		$vlinha["vl_descontos"] 			= substr($linha,240,13) / 100;
		$vlinha["vl_pagto"] 				= substr($linha,253,13) / 100;
		$vlinha["vl_juros"] 				= substr($linha,266,13) / 100;
		$vlinha["vl_outros_recebimentos"] 	= substr($linha,279,13) / 100;
		$vlinha["vl_pagto"] 				= $vlinha["vl_pagto"] + $vlinha["vl_juros"];
		$vlinha["vl_creditado"] 			= $vlinha["vl_pagto"] - $vlinha["vl_despesas_cobranca"];
		$vlinha["tx_ocorrencia"]  			= 'Ok';
		$vlinha["cd_ocorrencia"]  			= 0;
		switch ($vlinha["tipo_cobranca"]) {
			# Cobrança Normal
			case '805998':
				$vlinha["cd_bloqueto"]    = trim(sprintf("%8d",substr($linha, 62, 8)));
				$vlinha["dt_pagto"]       = '20'.substr($linha,114,2).'-'.substr($linha,112,2).'-'.substr($linha,110,2);
				$vlinha["dt_credito"]     = '20'.substr($linha,299,2).'-'.substr($linha,297,2).'-'. substr($linha,295,2);
				break;
			# Cobrança Registrada 805076
			# Cobrança Registrada VAZIO
			default:
				$vlinha["cd_titulo"]  	  		= substr($linha, 37, 25); //038 062 IDENTIFICAÇÃO DO TÍTULO PARA O BENEFICIÁRIO cd_titulo
				$vlinha["cd_ocorrencia"]  		= (int)rtrim(substr($linha, 108, 2)); //109 110 CÓDIGO DE OCORRÊNCIA
				$vlinha["tx_ocorrencia"]  		= $this->descricaoRetornoBanco($vlinha["cd_ocorrencia"]);
				$vlinha["cd_ocorrencia_erro"]  	= trim(substr($linha, 382, 10)); //109 110 CÓDIGO DE OCORRÊNCIA //383 392
				$array_ocorrencias 				= array($vlinha["cd_ocorrencia_erro"]);
				switch (strlen($vlinha["cd_ocorrencia_erro"])) {
					case 2:
						$array_ocorrencias = array($vlinha["cd_ocorrencia_erro"]);
						break;
					case 4:
						$array_ocorrencias = array(substr($vlinha["cd_ocorrencia_erro"], 0, 2),substr($vlinha["cd_ocorrencia_erro"], -2));
						break;
					case 6:
						$array_ocorrencias = array(substr($vlinha["cd_ocorrencia_erro"], 0, 2),substr($vlinha["cd_ocorrencia_erro"], 2, 2),substr($vlinha["cd_ocorrencia_erro"], -2));
						break;
					default:
						# code...
						break;
				}
				$tx_ocorrencia_erro = '';
				foreach ($array_ocorrencias as $cd_ocorrencia) {
					switch ($vlinha["cd_ocorrencia"]){
						case '03':
							$tx_ocorrencia_erro .= $this->descricaoErroRetornoBanco($cd_ocorrencia)."\n";
							break;
						case '16':
							$tx_ocorrencia_erro .= $this->descricaoErroRetornoBanco($cd_ocorrencia)."\n";
							break;
						case '18':
							$tx_ocorrencia_erro .= $this->descricaoErroRetornoBanco($cd_ocorrencia)."\n";
							break;
					}
				}
				$vlinha["tx_ocorrencia_erro"] = $tx_ocorrencia_erro;
				$vlinha["dt_ocorrencia"]  = trim(substr($linha,114,2)) == '' ? 'null' : '20'.substr($linha,114,2).'-'.substr($linha,112,2).'-'.substr($linha,110,2); substr($linha, 110, 6); // 150116 111 116 DATA DA OCORRÊNCIA PARA O BANCO dt_pagto
				$vlinha["cd_bloqueto"]    = trim(sprintf("%8d",substr($linha, 116, 10))); //substr($linha, 116, 10); //117 126 SEU NÚMERO cd_bloqueto
				$vlinha["dt_vencto"]      = trim(substr($linha,150,2)) == '' ? 'null' : '20'.substr($linha,150,2).'-'.substr($linha,148,2).'-'.substr($linha,146,2);
				$vlinha["vl_titulo"]      = floatval(substr($linha, 152, 13)); //153 165 VALOR DO TÍTULO
				$vlinha["dt_pagto"]       = rtrim(substr($linha,114,2)) == '' ? 'null' : '20'.substr($linha,114,2).'-'.substr($linha,112,2).'-'.substr($linha,110,2);
				$vlinha["dt_credito"]     = rtrim(substr($linha,299,2)) == '' ? 'null' : '20'.substr($linha,299,2).'-'.substr($linha,297,2).'-'. substr($linha,295,2);
				break;
		}

		return $vlinha;
	}
	/**
	 * [processarRegistro description]
	 * @param  [type] $linha [description]
	 * @return [type]        [description]
	 */
	public function processarRegistro($linha){

		$vlinha = array();																														
		$vlinha["cd_agencia"] 		= substr($linha, 15, 4);
		$vlinha["cd_cedente"] 		= substr($linha, 19, 9);
		$vlinha["cd_titulo"]  		= substr($linha, 37, 25);
		$vlinha["cd_bloqueto"]  	= substr($linha, 62, 10);
		$vlinha["dt_vencto"]  		= substr($linha, 120, 6);
		$vlinha["vl_titulo"]  		= substr($linha, 126, 13);
		$vlinha["vl_taxa_juros"]  	= substr($linha, 161, 12);
		$vlinha["vl_desconto"]  	= substr($linha, 180, 13);
		$vlinha["vl_abatimento"]  	= substr($linha, 20, 13);
		$vlinha["vl_multa"]  		= substr($linha, 321, 3);	
		$vlinha["nm_pagador"]  		= substr($linha, 234, 35);

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
	function registroHeader($cd_agencia, $cd_ctacor, $nm_empresa, $dt_geracao, $nr_sequencia_registro){
		$this->escreveLinha('01REMESSA'
							.'01'
							.$this->formataCampoString('COBRANCA', 15)
							.$this->formataCampoNumerico($cd_agencia, 4)
							.'00'
							.$this->formataCampoNumerico(substr($cd_ctacor, 0, 5), 5)
							.$this->formataCampoNumerico(substr($cd_ctacor, 5, 1), 1)
							.$this->espacosBrancos(8)
							.$this->formataCampoString(strtoupper($nm_empresa), 30)
							.'341' 
							.$this->formataCampoString('BANCO ITAU SA', 15)
							.$this->formataCampoNumerico($dt_geracao, 6)//DDMMAA
							.$this->espacosBrancos(294)
							.$this->formataCampoNumerico($nr_sequencia_registro,6));
	}
	/*
	*	Descrição: Dados do Titulo
	* 	@param datas DDMMAA
	* 	@return string Linha.
	*/
	function registroDadosTitulo($cpf_cnpj_empresa, $cd_agencia, $cd_ctacor, $cd_alegacao, $cd_titulo, $cd_bloqueto, $cd_carteira, $carteira_variavel, 
								 $cd_ocorrencia, $dt_vencto, $vl_titulo, $dt_emissao, $vl_mora, $dt_desconto, $vl_desconto, $vl_iof, $vl_abatimento, 
								 $cpf_cnpj, $nm_pagador, $endereco_pagador, $bairro_pagador, $cep_pagador, $nm_cidade_pagador, $uf_pagador, 
								 $nm_avalista, $dt_mora, $nr_dias_mora, $nr_sequencia_registro){
		$this->escreveLinha('1' //TIPO DE REGISTRO
							//CÓDIGO DE INSCRIÇÃO (1-CPF/2-CNPJ)
							.$this->formataCampoNumerico($this->identificacaoCPFCNPJ($cpf_cnpj_empresa), 2)
							//NÚMERO DE INSCRIÇÃO CPF/CNPJ 
							.$this->formataCampoNumerico($cpf_cnpj_empresa, 14)
							//AGÊNCIA
							.$this->formataCampoNumerico($cd_agencia, 4)
							//ZEROS
							.'00'
							//CONTA
							.$this->formataCampoNumerico(substr($cd_ctacor, 0, 5), 5)
							//DAC
							.$this->formataCampoNumerico(substr($cd_ctacor, 5, 1), 1)
							//BRANCOS
							.$this->espacosBrancos(4)
							//INSTRUÇÃO/ALEGAÇÃO
							/*
							*Deve ser preenchido na remessa somente quando utilizados, 
							* na posição 109-110, os códigos de ocorrência 35 – Cancelamento de Instrução e 38 
							* beneficiário não concorda com alegação do pagador.
							* Para os demais códigos de ocorrência este campo deverá ser preenchido com zeros. 
							*/
							.$this->formataCampoNumerico($cd_alegacao, 4)
							//USO DA EMPRESA
							.$this->formataCampoString($cd_titulo, 25)
							//NOSSO NÚMERO
							.$this->formataCampoNumerico($cd_bloqueto, 8)
							//QTDE DE MOEDA
							.$this->formataCampoNumerico(0, 13)
							//Nº DA CARTEIRA
							.$this->formataCampoNumerico($cd_carteira, 3)
							//USO DO BANCO - IDENTIFICAÇÃO DA OPERAÇÃO NO BANCO
							.$this->espacosBrancos(21) 
							//CARTEIRA (VERIFICAR)
							.'|'.$this->formataCampoString($carteira_variavel, 1).'|'
							//CÓD. DE OCORRÊNCIA (1-REMESSA/2-BAIXA)
							.$this->formataCampoString($cd_ocorrencia, 2)
							//Nº DO DOCUMENTO 
							.$this->formataCampoNumerico($cd_bloqueto . $this->digitoNossoNumero($cd_agencia.substr($cd_ctacor, 0, 5).$cd_carteira.$cd_bloqueto), 10)
							//VENCIMENTO
							.$this->formataCampoNumerico($dt_vencto, 6)
							 //VALOR DO TÍTULO
							.$this->formataCampoNumerico($vl_titulo, 13)
							//CÓDIGO DO BANCO  = OK
							.'341' 
							//AGÊNCIA COBRADORA AGÊNCIA ONDE O TÍTULO SERÁ COBRADO
							.$this->formataCampoNumerico(0, 5)
							//ESPÉCIE (06 CONTRATO)
							.$this->formataCampoString(ESPECIE, 2)
							//ACEITE 
							.$this->formataCampoString(ACEITO, 1)
							//DATA DE EMISSÃO
							.$this->formataCampoNumerico($dt_emissao, 6)
							//INSTRUÇÃO1 (90 - NO VENCIMENTO PAGÁVEL EM QUALQUER AGÊNCIA BANCÁRIA)
							.$this->formataCampoNumerico(90, 2)
							//INSTRUÇÃO2 (05 - RECEBER CONFORME INSTRUÇÕES NO PRÓPRIO TÍTULO)
							.$this->formataCampoNumerico('05', 2)
							//JUROS DE 1 DIA
							.$this->formataCampoNumerico($vl_mora, 13)
							//DESCONTO ATÉ
							.$this->formataCampoNumerico($dt_desconto, 6)
							//VALOR DO DESCONTO
							.$this->formataCampoNumerico($vl_desconto, 13)
							//VALOR DO I.O.F.
							.$this->formataCampoNumerico($vl_iof, 13)
							//ABATIMENTO
							.$this->formataCampoNumerico($vl_abatimento, 13)
							//CÓDIGO DE INSCRIÇÃO
							.$this->formataCampoNumerico($this->identificacaoCPFCNPJ($cpf_cnpj), 2)
							//NÚMERO DE INSCRIÇÃO
							.$this->formataCampoNumerico($cpf_cnpj, 14)
							//NOME DO PAGADOR
							.$this->formataCampoString(strtoupper($nm_pagador), 30)
							//BRANCOS
							.$this->espacosBrancos(10)
							//LOGRADOURO
							.$this->formataCampoString(strtoupper($endereco_pagador), 40)
							//BAIRRO
							.$this->formataCampoString(strtoupper($bairro_pagador), 12)
							//CEP
							.$this->formataCampoNumerico($cep_pagador, 8)
							//CIDADE
							.$this->formataCampoString(strtoupper($nm_cidade_pagador), 15)
							//ESTADO
							.$this->formataCampoString($uf_pagador,2)
							//SACADOR/AVALISTA
							.$this->espacosBrancos(30)
							//BRANCOS
							.$this->espacosBrancos(4)
							//BRANCOS = OK
							.$this->formataCampoNumerico($dt_mora, 6)
							//DATA DE MORA = OK
							.$this->formataCampoNumerico($nr_dias_mora, 2)
							//PRAZO = OK
							.$this->espacosBrancos(1)
							//BRANCOS = OK
							.$this->formataCampoNumerico($nr_sequencia_registro, 6));
							//NÚMERO SEQÜENCIAL = OK
	}
	/*
	*	Descrição: 	TRAILLER
	* 	@param 
	* 	@return string Linha.
	*/
	function registroTrailler($nr_sequencia_registro){
		$this->escreveLinha('9'
							.$this->espacosBrancos(393)
							.$this->formataCampoNumerico($nr_sequencia_registro, 6));
	}

	/*
	*	NC (número de controle), que é calculado de acordo com o módulo 10 e o módulo 11
	* 	@param $nosso_numero bloqueto
	* 	@return int Retorna nosso numero com os digitos de controle
	*/
	function digitoNossoNumero($nosso_numero){
        return $this->modulo10("$nosso_numero");
    }
    /*
	*	Calculo modulo 10 Itau.
	* 	@param $num bloqueto
	* 	@return integer Retorna check-digit (módulo 10)
	*/
    function modulo10($num){
        $numtotal10 = 0;
        $fator      = 2;
        for ($i = strlen($num); $i > 0; $i--) {
            $numeros[$i] = substr($num,$i-1,1);
            $parcial10[$i] = $numeros[$i] * $fator;
            $numtotal10 .= $parcial10[$i];
            if ($fator == 2) {
                $fator = 1;
            } else {
                $fator = 2;
            }
        }
        $soma = 0;
        for ($i = strlen($numtotal10); $i > 0; $i--) {
            $numeros[$i] = substr($numtotal10,$i-1,1);
            $soma += $numeros[$i];              
        }
        $resto = $soma % 10;
        $digito = 10 - $resto;
        if ($resto == 0) {
            $digito = 0;
        }
        return $digito;
    }
    /*
	*	Calculo modulo 11 Itau.
	* 	@param $num bloqueto
	* 	@return integer Retorna check-digit (módulo 11)
	*/
	function modulo11($num, $base = 9, $r = 0){
        $soma 	= 0;
        $fator 	= 2;
        for ($i = strlen($num); $i > 0; $i--) {
            $numeros[$i] = substr($num,$i-1,1);
            $parcial[$i] = $numeros[$i] * $fator;
            $soma += $parcial[$i];
            if ($fator == $base) { $fator = 1; }
            $fator++;
        }
        if ($r == 0) {
            $soma *= 10;
            $digito = $soma % 11;
            if ($digito == 10) { $digito = 0; }
            return $digito;
        } elseif ($r == 1){
            return ($soma % 11);
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
		return strlen($valor) <= 11 ? '01' : '02'; 
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



