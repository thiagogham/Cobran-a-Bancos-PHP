<?php 
 
/*
*	Descri��o:  Classe para gera��o e leitura de arquivos retorno remessa em conta Banrisul.
*				Padr�o FEBRABAN
* 	Autor: Thiago R. Gham
* 	Vers�o: 1.0	 25-11-2015
	
	LAYOUT DOS RESGISTROS                                                          
	Header 		- Tipo 0 
	Transa��o	- Tipo 1 � Dados do T�tulo
	Transa��o	- Tipo 1 � Dados do Sacador
	Transa��o	- Tipo 2 � Mensagem (Opcional)
	Transa��o	- Tipo 3 � Rateio de Cr�dito (Opcional)
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
1 � Cobran�a Simples (8050.76)
3 � Cobran�a Caucionada (8150.55) Reservado
4 � Cobran�a em IGPM (8450.94) *
5 � Cobran�a Caucionada CGB Especial (8355.01) Reservado
6 � Cobran�a Simples Seguradora (8051.57)
7 � Cobran�a em UFIR (8257.86) *
8 � Cobran�a em IDTR (8356.84) *
C � Cobran�a Vinculada (8250.34)
D � Cobran�a CSB (8258.67)
E � Cobran�a Caucionada C�mbio (8156.24)
F � Cobran�a Vendor (8152.17) Reservado
H � Cobran�a Caucionada D�lar (8157.05) Reservado **
I � Cobran�a Caucionada Compror (8351.46) Reservado
K � Cobran�a Simples INCC-M (8153.06)
M � Cobran�a Partilhada (8154.70)
N � Capital de Giro CGB ICM (6130.96) Reservado
R � Desconto de Duplicata (6030.15) ***
S � Vendor Eletr�nico � Valor Final (Corrigido) (6032.79) ***
X � Vendor BDL � Valor Inicial (Valor da NF) (6034.30) ***
*/
define('OCORRENCIA_REMESSA','01');
define('OCORRENCIA_BAIXA'  ,'02');
/*
01 � Remessa
02 � Pedido baixa
04 � Concess�o de abatimento
05 � Cancelamento de abatimento
06 � Altera��o de vencimento
07 � Altera��o de uso empresa
08 � Altera��o do Seu N�mero
09 � Protestar imediatamente
10 � Susta��o de protesto
11 � N�o cobrar juros de mora
12 - Reembolso e transfer�ncia Desconto e Vendor
13 � Reembolso e devolu��o Desconto e Vendor
16 � Altera��o do n�mero de dias para protesto
17 � Protestar imediatamente para fins de fal�ncia
18 � Altera��o do nome do Pagador
19 � Altera��o do endere�o do Pagador
20 � Altera��o da cidade do Pagador
21 � Altera��o do CEP do Pagador (mudan�a de portadora)
68 � Acerto dos dados do rateio de cr�dito Vide item 2.6.1
69 � Cancelamento dos dados do rateio Vide item 2.6.1
*/

define('INSTRUCAO_1', '18');
/*
C�DIGO DA 1� INSTRU��O
- Campo num�rico opcional.
- Conte�do:
09 � Protestar caso impago NN dias ap�s o vencimento. O n�mero de dias para protesto, igual ou maior do que 03, dever� ser informado nas posi��es 370-371.
15 � Devolver se impago ap�s NN dias do vencimento. Informar o n�mero de dias para devolu��o nas posi��es 370-371.
Obs.: Para o n�mero de dias igual a 00 ser� impresso no bloqueto: �N�O RECEBER AP�S O VENCIMENTO�.
18 � Ap�s NN dias do vencimento, cobrar xx,x% de multa.
20 � Ap�s NN dias do vencimento, cobrar xx,x% de multa ao m�s ou fra��o.
23 � N�o protestar.
*/
/*
C�DIGO DE OCORR�NCIA RETORNO DO BANCO
02 � Confirma��o de entrada
03 � Entrada rejeitada
04 � Baixa de t�tulo liquidado por edital
06 � Liquida��o normal
07 � Liquida��o parcial
08 � Baixa por pagamento, liquida��o pelo saldo
09 � Devolu��o autom�tica
10 � Baixado conforme instru��es
11 � Arquivo levantamento
Observa��o: Para este c�digo, o campo Data da Ocorr�ncia no Banco (posi��es 111-116)
ser� a data do registro do t�tulo.
12 � Concess�o de abatimento
13 � Cancelamento de abatimento
14 � Vencimento alterado
15 � Pagamento em cart�rio
19
16 � Altera��o de dados (*)
18 � Altera��o de instru��es (*)
19 � Confirma��o de instru��o protesto
20 � Confirma��o de instru��o para sustar protesto
21 � Aguardando autoriza��o para protesto por edital
22 � Protesto sustado por altera��o de vencimento e prazo de cart�rio
23 � Confirma��o da entrada em cart�rio
Observa��o: A data da entrega em cart�rio � informada nas posi��es 111-116.
25 � Devolu��o, liquidado anteriormente
Observa��o: A informa��o da data da liquida��o est� nas posi��es 111-116.
26 � Devolvido pelo cart�rio � erro de informa��o.
30 � cobran�a a creditar (liquida��o em tr�nsito) (**)
31 � T�tulo em tr�nsito pago em cart�rio (**)
32 � Reembolso e transfer�ncia Desconto e Vendor ou carteira em garantia
33 � Reembolso e devolu��o Desconto e Vendor
34 � Reembolso n�o efetuado por falta de saldo
40 � Baixa de t�tulos protestados (**)
41 � Despesa de aponte. (**)
42 � Altera��o de t�tulo
43 � Rela��o de t�tulos
44 � Manuten��o mensal
45 � Susta��o de cart�rio e envio de t�tulo a cart�rio
46 � Fornecimento de formul�rio pr�-impresso
47 � Confirma��o de entrada � Pagador DDA (**)
68 � Acerto dos dados do rateio de cr�dito
Observa��o: Verificar motivo do registro tipo 3. Vide item 2.6.1
69 � Cancelamento dos dados do rateio
Observa��o: Verificar motivo do registro tipo 3. Vide item 2.6.1
(*) MOTIVOS DAS OCORR�NCIAS 03, 16 E 18: est�o no final das especifica��es do
arquivo retorno e s�o informados nas posi��es 383-392 do registro de retorno do t�tulo.
(**) C�DIGOS 30, 31, 40, 41

ERROS 
01 � C�digo do Banco inv�lido
02 � Ag�ncia/Conta/N�mero de controle � Inv�lido Cobran�a Partilhada
04 � C�digo do movimento n�o permitido para a carteira
05 � C�digo do movimento inv�lido
08 � Nosso N�mero inv�lido
09 � Nosso N�mero duplicado
10 � Carteira inv�lida
16 � Data de vencimento inv�lida
17 � Data de vencimento anterior � data de emiss�o
18 � Vencimento fora do prazo de opera��o
20 � Valor do t�tulo inv�lido (n�o num�rico)
21 � Esp�cie do t�tulo inv�lida (arquivo de registro)
23 � Aceite inv�lido � verifica conte�do v�lido
48 � CEP inv�lido ou altera��o de CEP n�o permitida
*/
class Banrisul{
	
	private $STRING = '';

	static $CD_RETORNO_BANCO =  array('02' => 'Confirma��o de entrada',
									  '2'  => 'Confirma��o de entrada',
									  '03' => 'Entrada rejeitada',
									  '3'  => 'Entrada rejeitada',
									  '04' => 'Baixa de t�tulo liquidado por edital',
									  '6'  => 'Liquida��o normal',
									  '06' => 'Liquida��o normal',
									  '07' => 'Liquida��o parcial',
									  '08' => 'Baixa por pagamento, liquida��o pelo saldo',
									  '09' => 'Devolu��o autom�tica',
									  '9'  => 'Devolu��o autom�tica',
									  '10' => 'Baixado conforme instru��es',
									  '16' => 'Altera��o de dados (*)',
									  '18' => 'Altera��o de instru��es (*)',
									  '44' => 'Manuten��o mensal',
									  '42' => 'Altera��o de t�tulo',);

	static $CD_RETORNO_BANCO_ERRO =  array(	  '01' => 'C�digo do Banco inv�lido',
											  '02' => 'Ag�ncia/Conta/N�mero de controle � Inv�lido',
											  '04' => 'C�digo do movimento n�o permitido para a carteira',
											  '05' => 'C�digo do movimento inv�lido',
											  '07' => 'Liquida��o parcial',
											  '08' => 'Nosso N�mero inv�lido',
											  '09' => 'Nosso N�mero duplicado',
											  '10' => 'Carteira inv�lida',
											  '16' => 'Data de vencimento inv�lida',
											  '18' => 'Data de vencimento anterior � data de emiss�o',
											  '20' => 'Valor do t�tulo inv�lido (n�o num�rico)',
											  '48' => 'CEP inv�lido ou altera��o de CEP n�o permitida',
											  '42' => 'C�digo para baixa/devolu��o ou instru��o inv�lido � verifica se o c�digo � branco, 0, 1 ou 2',
											  '60' => 'Movimento para t�tulo n�o cadastrado � altera��o ou devolu��o');
	/*
	*	Descri��o: Armazena linhas do registro.
	* 	@param string $linha String contendo a linha.
	* 	@return string $codigo
	*/
	public function descricaoRetornoBanco($codigo) {
		return Banrisul::$CD_RETORNO_BANCO[$codigo];
	}
	/*
	*	Descri��o: Armazena linhas do registro.
	* 	@param string $linha String contendo a linha.
	* 	@return string $codigo
	*/
	public function descricaoErroRetornoBanco($codigo) {
		return Banrisul::$CD_RETORNO_BANCO_ERRO[$codigo];
	}
	
	/*
	*	Descri��o: Armazena linhas do registro.
	* 	@param string $linha String contendo a linha.
	* 	@return 
	*/
	function salvaArquivo($caminho) {
		$nm_arquivo = "banrisul".date('YmdHi').".txt";
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
		if(trim($linha) == '') die('A linha est� vazia.');
		/*Identifica��o do Registro*/
		$processar = 'processar'.substr($linha,0,1);
		if(method_exists('Banrisul', $processar)){
			return $this->$processar($linha);
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
	private function processarC($linha){
		$vlinha = array();																														
		$vlinha["constante1"]  = substr($linha, 0, 19);
		return $vlinha;
	}
	/*
	*	Descri��o: Processa a linha header do arquivo
	* 	@param string $linha Linha do header de arquivo processado 
	* 	@return array Retorna um vetor contendo os dados dos campos do header do arquivo.
	*/
	private function processar0($linha){
		$vlinha = array();																														
		$vlinha["constante1"]  = substr($linha, 0, 19); 	
		$vlinha["cd_agencia"]  = substr($linha, 25, 4); 	
		$vlinha["cd_cedente"]  = substr($linha, 29, 9); 	
		$vlinha["constante2"]  = substr($linha, 76, 11); 	
		return $vlinha;
	}
	/*
	*	Descri��o: Processa a linha header do arquivo
	* 	@param string $linha Linha do header de arquivo processado 
	* 	@return array Retorna um vetor contendo os dados dos campos do header do arquivo.
	*/
	private function processar1($linha){
		$vlinha = array();																														
		$vlinha["inscricao"]  	  			= substr($linha, 1, 2); 	
		$vlinha["cpf_cnpj"]   	  			= substr($linha, 3, 14); 		
		$vlinha["cd_agencia1"]    			= substr($linha, 17, 3); 	
		$vlinha["cd_cedente"]  	  			= substr($linha, 20, 9); 	
		$vlinha["tipo_cobranca"]  			= substr($linha, 29, 6); 	
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
			# Cobran�a Normal
			case '805998':
				$vlinha["cd_bloqueto"]    = trim(sprintf("%8d",substr($linha, 62, 8)));
				$vlinha["dt_pagto"]       = '20'.substr($linha,114,2).'-'.substr($linha,112,2).'-'.substr($linha,110,2);
				$vlinha["dt_credito"]     = '20'.substr($linha,299,2).'-'.substr($linha,297,2).'-'. substr($linha,295,2);
				break;
			# Cobran�a Registrada 805076
			# Cobran�a Registrada VAZIO
			default:
				$vlinha["cd_titulo"]  	  		= substr($linha, 37, 25); //038 062 IDENTIFICA��O DO T�TULO PARA O BENEFICI�RIO cd_titulo
				$vlinha["cd_ocorrencia"]  		= (int)rtrim(substr($linha, 108, 2)); //109 110 C�DIGO DE OCORR�NCIA
				$vlinha["tx_ocorrencia"]  		= $this->descricaoRetornoBanco($vlinha["cd_ocorrencia"]);
				$vlinha["cd_ocorrencia_erro"]  	= trim(substr($linha, 382, 10)); //109 110 C�DIGO DE OCORR�NCIA //383 392
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
				$vlinha["dt_ocorrencia"]  = trim(substr($linha,114,2)) == '' ? 'null' : '20'.substr($linha,114,2).'-'.substr($linha,112,2).'-'.substr($linha,110,2); substr($linha, 110, 6); 
				$vlinha["cd_bloqueto"]    = trim(sprintf("%8d",substr($linha, 116, 10))); 
				$vlinha["dt_vencto"]      = trim(substr($linha,150,2)) == '' ? 'null' : '20'.substr($linha,150,2).'-'.substr($linha,148,2).'-'.substr($linha,146,2);
				$vlinha["vl_titulo"]      = floatval(substr($linha, 152, 13));
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
	function registroHeader($cd_agencia, $cd_cedente, $nm_empresa, $dt_geracao, $nr_sequencia_registro){
		$this->escreveLinha('01REMESSA' //9
							.$this->espacosBrancos(17)
							.$this->formataCampoNumerico($cd_agencia.$cd_cedente,13)
							.$this->espacosBrancos(7)
							.$this->formataCampoString($nm_empresa,30)
							.'041BANRISUL'//11
							.$this->espacosBrancos(7)
							.$this->formataCampoNumerico($dt_geracao,6)
							.$this->espacosBrancos(9)
							.$this->formataCampoString('',4)//C�DIGO DO SERVI�O
							.$this->espacosBrancos(1)
							.$this->formataCampoString('',1)//TIPO DE PROCESSAMENTO
							.$this->espacosBrancos(1)
							.$this->formataCampoString('',10)//C�DIGO DO CLIENTE NO OFFICE BANKING
							.$this->espacosBrancos(268)
							.$this->formataCampoNumerico($nr_sequencia_registro,6));
	}
	/*
	*	Descri��o: Dados do Titulo
	* 	@param datas DDMMAA
	* 	@return string Linha.
	*/
	function registroDadosTitulo($cd_agencia, $cd_cedente, $cd_titulo, $cd_bloqueto, $instrucao, $ocorrencia, $dt_vencto, $vl_titulo, $dt_emissao,
								$cd_instrucao1, $cd_instrucao2, $vl_taxa_juros, $dt_desconto, $vl_desconto, $vl_iof, $vl_abatimento, $cpf_cnpj, $nm_pagador, $endereco_pagador,
								$vl_multa, $nr_dias_multa, $cep_pagador, $nm_cidade_pagador, $uf_pagador, $vl_dia_pag_antecipado, $vl_cal_desconto, $nr_dias_protesto, $nr_sequencia_registro){
		$this->escreveLinha('1'
							.$this->espacosBrancos(16)
							.$this->formataCampoNumerico($cd_agencia,4)
							.$this->formataCampoNumerico($cd_cedente,9)
							.$this->espacosBrancos(7)
							.$this->formataCampoString($cd_titulo,25)
							.$this->formataCampoString($this->digitoNossoNumero($cd_bloqueto),10)
							.$this->formataCampoString($instrucao,32)
							.$this->espacosBrancos(3)
							.$this->formataCampoString(TIPO_CARTEIRA,1)
							.$this->formataCampoNumerico($ocorrencia,2)
							.$this->formataCampoNumerico($cd_bloqueto,10)
							.$this->formataCampoNumerico($dt_vencto,6) 
							.$this->formataCampoNumerico($vl_titulo,13)
							.'041'
							.$this->espacosBrancos(5)
							.$this->formataCampoNumerico(TIPO_DOC_COBRANCA_CCB,2)
							.$this->formataCampoString(ACEITO,1)
							.$this->formataCampoNumerico($dt_emissao,6)
							.$this->formataCampoNumerico($cd_instrucao1,2)
							.$this->formataCampoNumerico($cd_instrucao2,2)
							.$this->formataCampoNumerico(MORA_DIARIA,1)
							.$this->formataCampoNumerico($vl_taxa_juros,12)
							.$this->formataCampoNumerico($dt_desconto,6)
							.$this->formataCampoNumerico($vl_desconto,13)
							.$this->formataCampoNumerico($vl_iof,13)
							.$this->formataCampoNumerico($vl_abatimento,13)
							.$this->formataCampoNumerico($this->identificacaoCPFCNPJ($cpf_cnpj),2)
							.$this->formataCampoNumerico($cpf_cnpj,14)
							.$this->formataCampoString($nm_pagador,35)
							.$this->espacosBrancos(5)
							.$this->formataCampoString($endereco_pagador,40)
							.$this->espacosBrancos(7)
							.$this->formataCampoNumerico($vl_multa,3)
							.$this->formataCampoNumerico($nr_dias_multa,2)
							.$this->formataCampoNumerico($cep_pagador,8)
							.$this->formataCampoString($nm_cidade_pagador,15)
							.$this->formataCampoString($uf_pagador,2)
							.$this->formataCampoNumerico($vl_dia_pag_antecipado,4)
							.$this->espacosBrancos(1)
							.$this->formataCampoNumerico($vl_cal_desconto,13)
							.$this->formataCampoNumerico($nr_dias_protesto,2)
							.$this->espacosBrancos(23)
							.$this->formataCampoNumerico($nr_sequencia_registro,6));
	}
	
	/*
	*	Descri��o: 	Transa��o tipo1: Dados do Sacador
	* 	@param 
	* 	@return string Linha.
	*/
	function registroDadosSacador($cd_agencia, $cd_cedente, $cd_bloqueto, $cpf_cnpj, $nm_pagador, $endereco_pagador, $cep_pagador, $nr_sequencia_registro){
		$this->escreveLinha('1'
							.$this->espacosBrancos(16)
							.$this->formataCampoNumerico($cd_agencia,4)
							.$this->formataCampoNumerico($cd_cedente,9)
							.$this->espacosBrancos(32)
							.$this->formataCampoNumerico($this->digitoNossoNumero($cd_bloqueto),10)
							.$this->espacosBrancos(36)
							.$this->formataCampoNumerico(14,2)
							.$this->espacosBrancos(37)
							.$this->formataCampoNumerico(TIPO_DOC_COBRANCA_CCB,2)
							.$this->espacosBrancos(69)
							.$this->formataCampoNumerico($this->identificacaoCPFCNPJ($cpf_cnpj),2)
							.$this->formataCampoNumerico($cpf_cnpj,14)
							.$this->formataCampoString($nm_pagador,40)
							.$this->formataCampoString($endereco_pagador,40)
							.$this->espacosBrancos(12)
							.$this->formataCampoNumerico($cep_pagador,8)
							.$this->espacosBrancos(60)
							.$this->formataCampoNumerico($nr_sequencia_registro,6));
	}
	/*
	*	Descri��o: 	Transa��o tipo2: Mensagem (OPCIONAL)
	* 	@param 
	* 	@return string Linha.
	*/
	function registroMensagem(){
		//$this->escreveLinha();
	}
	/*
	*	Descri��o: 	Transa��o tipo3: Rateio de Cr�dito (OPCIONAL)
	* 	@param 
	* 	@return string Linha.
	*/
	function registroRateioCredito(){
		//$this->escreveLinha();
	}
	/*
	*	Descri��o: 	TRAILLER
	* 	@param 
	* 	@return string Linha.
	*/
	function registroTrailler($vl_total, $nr_sequencia_registro){
		$this->escreveLinha('9'
							.$this->espacosBrancos(26)
							.$this->formataCampoNumerico($vl_total,13)
							.$this->espacosBrancos(354)
							.$this->formataCampoNumerico($nr_sequencia_registro,6));
	}

	/*
	*	NC (n�mero de controle), que � calculado de acordo com o m�dulo 10 e o m�dulo 11
	* 	@param $nosso_numero bloqueto
	* 	@return int Retorna nosso numero com os digitos de controle
	*/
	function digitoNossoNumero($nosso_numero){
        $nosso_numero = sprintf("%08d", "$nosso_numero");
        $DAC_NN1    = $this->modulo10("$nosso_numero");
        while (true) {
            $resto = $this->modulo11("$nosso_numero$DAC_NN1", 7, 1);         
            if ($resto==1) {
                $DAC_NN1++;
                if ($DAC_NN1==10) {
                    $DAC_NN1 = 0;
                }
            }elseif ($resto==0) {
                $DAC_NN2 = 0;           
                break;
            }else {
                $DAC_NN2 = $this->modulo11("$nosso_numero$DAC_NN1", 7, 0);           
                break;
            }
        }
        return "$nosso_numero$DAC_NN1$DAC_NN2";
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
	*	Calculo modulo 11 Banrisul.
	* 	@param $num bloqueto
	* 	@return integer Retorna check-digit (m�dulo 11)
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
	*	Gera c�digo de Conta Corrente Banrisul.
	* 	@param $ctacor conta corrente EENNNNNNCD.
	* 	@return integer Retorna check-digit (m�dulo 11) C�digo de Conta Corrente.
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
			case 0:  break;
			case 1:  $resto = 1; break;
			default: break;
		}
		return $resto;
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
		return strlen($valor) <= 11 ? '02' : '01'; 
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