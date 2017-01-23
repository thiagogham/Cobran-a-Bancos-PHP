<?php 

/*
*	Descrição:  Classe para geração e leitura de arquivos retorno remessa em conta Banco do Brasil.
*				Padrão FEBRABAN
* 	Autor: Thiago R. Gham
* 	Versão: 0.1	 19-09-2016                                   
*/ 

define('TIPO_OPERACAO_TESTE', 'TESTE');  
define('TIPO_OPERACAO_REMESSA', 'REMESSA');
/*
	TIPO DE COBRANÇA 
	a) Carteiras 11 ou 17: 
		04DSC: Solicitação de registro na Modalidade Descontada 
		08VDR: Solicitação de registro na Modalidade BBVendor 
		02VIN: solicitação de registro na Modalidade Vinculada 
		BRANCOS: Registro na Modalidade Simples 
	b) Carteiras 12, 31, 51: Brancos 
 */
define('TIPO_COBRANCA_04DSC', '04DSC');
define('TIPO_COBRANCA_08VDR', '08VDR');
define('TIPO_COBRANCA_02VIN', '02VIN');
define('TIPO_COBRANCA_VAZIO', '');

define('ACEITO', 'A');
define('NAOACEITO', 'N');

define('MORA_DIARIA', 0);
define('MORA_MENSAL', 1);

define('TIPO_CARTEIRA',1);
/*
COMANDO 
01 - Registro de títulos 
02 - Solicitação de baixa 
03 - Pedido de débito em conta 
04 - Concessão de abatimento 
05 - Cancelamento de abatimento 
06 - Alteração de vencimento de título 
07 - Alteração do número de controle do participante 
08 - Alteração do número do titulo dado pelo cedente 
09 - Instrução para protestar (Nota 09)  
10 - Instrução para sustar protesto 
11 - Instrução para dispensar juros 
12 - Alteração de nome e endereço do Sacado 
16 – Alterar Juros de Mora (Vide Observações) 
31 - Conceder desconto 
32 - Não conceder desconto 
33 - Retificar dados da concessão de desconto 
34 - Alterar data para concessão de desconto 
35 - Cobrar multa (Nota 11) 
36 - Dispensar multa  (Nota 11) 
37 - Dispensar indexador 
38 - Dispensar prazo limite de recebimento (Nota 11) 
39 - Alterar prazo limite de recebimento (Nota 11)  
40 – Alterar modalidade (Vide Observações) 
*/
define('COMANDO_REMESSA','01');
define('COMANDO_BAIXA'  ,'02');
/*
01 – Remessa
02 – Pedido baixa
04 – Concessão de abatimento
05 – Cancelamento de abatimento
06 – Alteração de vencimento
07 – Alteração de uso empresa
08 – Alteração do Seu Número
09 – Protestar imediatamente
10 – Sustação de protesto
11 – Não cobrar juros de mora
12 - Reembolso e transferência Desconto e Vendor
13 – Reembolso e devolução Desconto e Vendor
16 – Alteração do número de dias para protesto
17 – Protestar imediatamente para fins de falência
18 – Alteração do nome do Pagador
19 – Alteração do endereço do Pagador
20 – Alteração da cidade do Pagador
21 – Alteração do CEP do Pagador (mudança de portadora)
68 – Acerto dos dados do rateio de crédito Vide item 2.6.1
69 – Cancelamento dos dados do rateio Vide item 2.6.1
*/

define('INSTRUCAO_1', '');
define('INSTRUCAO_2', '');
/*
CÓDIGO DA 1ª INSTRUÇÃO
- Campo numérico opcional.
- Conteúdo:
09 – Protestar caso impago NN dias após o vencimento. O número de dias para protesto, igual ou maior do que 03, deverá ser informado nas posições 370-371.
15 – Devolver se impago após NN dias do vencimento. Informar o número de dias para devolução nas posições 370-371.
Obs.: Para o número de dias igual a 00 será impresso no bloqueto: “NÃO RECEBER APÓS O VENCIMENTO”.
18 – Após NN dias do vencimento, cobrar xx,x% de multa.
20 – Após NN dias do vencimento, cobrar xx,x% de multa ao mês ou fração.
23 – Não protestar.
*/
define('ESPECIE_TITULO', 12);
/*
ESPÉCIE DE TÍTULO
01 - Duplicata Mercantil 
02 - Nota Promissória 
03 - Nota de Seguro 
05 – Recibo 
08 - Letra de Câmbio 
09 – Warrant 
10 – Cheque 
12 - Duplicata de Serviço 
13 - Nota de Débito 
15 - Apólice de Seguro 
25 - Dívida Ativa da União 
26 - Dívida Ativa de Estado  
27 - Dívida Ativa de Município 
Observações: 
As espécies “25” – Dívida Ativa da União -, “26” Dívida Ativa de Estado -, “27” – Dívida Ativa 
de Município - , somente são admissíveis nas Carteiras 11 e 17, como Cobrança Simples. 
Na  modalidade  de  Cobrança  Descontada  somente  são  permitidas  as  Espécies:  01-Duplicata 
Mercantil (DM), 12-Duplicata de Prestação de Serviço (DS) e 08-Letra de Câmbio (LC); 
Para  a  modalidade  Vendor  somente  são  permitidas  as Espécies:  01–Duplicata  Mercantil  (DM)  e 
12–Duplicata de Prestação de Serviço (DS). 
*/
class /*Remessa*/BancoBrasil{
	
	private $STRING = '';

	static $CD_RETORNO_BANCO =  array('02' => 'Confirmação de entrada',
									  '03' => 'Entrada rejeitada',
									  '04' => 'Baixa de título liquidado por edital',
									  '06' => 'Liquidação normal',
									  '07' => 'Liquidação parcial',
									  '08' => 'Baixa por pagamento, liquidação pelo saldo',
									  '09' => 'Devolução automática',
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
	*	Descrição: Armazena linhas do registro.
	* 	@param string $linha String contendo a linha.
	* 	@return 
	*/
	private function escreveLinha($linha) {
		$this->STRING .= "$linha\r\n";
	}
	/**
	 * [registroHeader description]
	 * @param  [type] $tipo_operacao         [description]
	 * @param  [type] $cd_agencia            [description]
	 * @param  [type] $cd_ctacor             [description]
	 * @param  [type] $nm_empresa            [description]
	 * @param  [type] $dt_geracao            [description]
	 * @param  [type] $nr_sequencia_registro [description]
	 * @param  [type] $cd_convenio           [description]
	 * @return [type]                        [description]
	 */
	function registroHeader($tipo_operacao, $cd_agencia, $cd_ctacor, $nm_empresa, $dt_geracao, $nr_sequencia_registro, $cd_convenio){
		$this->escreveLinha('01'
							.$this->formataCampoString($tipo_operacao,7)
							.'01'
							.'COBRANCA'
							.$this->espacosBrancos(7)
							.$this->formataCampoNumerico($cd_agencia,4)
							.$this->formataCampoString($this->getAgenciaDV($cd_agencia),1)//Dígito Verificador - D.V. - do Prefixo da Agência. 
							.$this->formataCampoNumerico(substr($cd_ctacor, 0, -1),8)
							.$this->formataCampoString($this->getContaDV($cd_ctacor),1)
							.'000000'
							.$this->formataCampoString(strtoupper($nm_empresa),30)//Nome Cedente
							.$this->formataCampoString('001BANCODOBRASIL',18)
							.$this->formataCampoNumerico($dt_geracao,6)//DDMMAA
							.$this->formataCampoNumerico($nr_sequencia_registro,7)
							.$this->espacosBrancos(22)
							.$this->formataCampoNumerico($cd_convenio,7)
							.$this->espacosBrancos(258)
							.'000001');
	}
	/**
	 * [registroDadosTitulo description]
	 * @param  [type] $cpf_cnpj_cedente      [description]
	 * @param  [type] $cd_agencia            [description]
	 * @param  [type] $cd_ctacor             [description]
	 * @param  [type] $cd_carteira           [description]
	 * @param  [type] $cd_convenio           [description]
	 * @param  [type] $cd_titulo             [description]
	 * @param  [type] $cd_bloqueto           [description]
	 * @param  [type] $variacao_carteira     [description]
	 * @param  [type] $dt_vencto             [description]
	 * @param  [type] $vl_titulo             [description]
	 * @param  [type] $dt_emissao            [description]
	 * @param  [type] $cd_instrucao1         [description]
	 * @param  [type] $cd_instrucao2         [description]
	 * @param  [type] $vl_taxa_juros         [description]
	 * @param  [type] $dt_desconto           [description]
	 * @param  [type] $vl_desconto           [description]
	 * @param  [type] $vl_iof                [description]
	 * @param  [type] $vl_abatimento         [description]
	 * @param  [type] $cpf_cnpj              [description]
	 * @param  [type] $nm_pagador            [description]
	 * @param  [type] $endereco_pagador      [description]
	 * @param  [type] $bairro_pagador        [description]
	 * @param  [type] $cep_pagador           [description]
	 * @param  [type] $nm_cidade_pagador     [description]
	 * @param  [type] $uf_pagador            [description]
	 * @param  [type] $observacoes           [description]
	 * @param  [type] $nr_dias_protesto      [description]
	 * @param  [type] $nr_sequencia_registro [description]
	 * @return [type]                        [description]
	 */
	 function registroDadosTitulo($cpf_cnpj_cedente, $cd_agencia, $cd_ctacor, $cd_carteira, $cd_convenio, $cd_titulo, $cd_bloqueto, $variacao_carteira, $cd_comando,
								 $dt_vencto, $vl_titulo, $dt_emissao, $cd_instrucao1, $cd_instrucao2, $vl_taxa_juros, $dt_desconto, $vl_desconto, $vl_iof, $vl_abatimento,
								 $cpf_cnpj, $nm_pagador, $endereco_pagador, $bairro_pagador, $cep_pagador, $nm_cidade_pagador, $uf_pagador, $observacoes, $nr_dias_protesto, $nr_sequencia_registro){
		
								
		$this->escreveLinha('7' //Identificação do Registro Detalhe: 7 (sete)
							//Tipo de Inscrição do Cedente (01 FÍSICA/ 02 JURIDICA)
							.$this->formataCampoNumerico($this->identificacaoCPFCNPJ($cpf_cnpj_cedente),2)
							//Número do CPF/CNPJ do Cedente 
							.$this->formataCampoNumerico($cpf_cnpj_cedente,14)//09110655000197
							//Prefixo da Agência 
							.$this->formataCampoNumerico($cd_agencia,4)//2733
							//Dígito Verificador - D.V. - do Prefixo da Agência 
							.$this->formataCampoString($this->getAgenciaDV($cd_agencia),1)//2
							.$this->formataCampoNumerico(substr($cd_ctacor, 0, -1),8)//00228214
							//Dígito Verificador - D.V. - do Número da Conta Corrente do Cedente
							.$this->formataCampoString($this->getContaDV($cd_ctacor),1)//4
							//Número do Convênio de Cobrança do Cedente
							.$this->formataCampoNumerico($cd_convenio,7)//2572985
							//Código de Controle da Empresa 
							.$this->formataCampoString($cd_titulo,25)//106762   
							//Nosso-Número 
							.$this->formataCampoNumerico($this->getNossoNumero($cd_carteira, $cd_convenio, $cd_bloqueto),17)//25729850000015038
							//Número da Prestação: “00” (Zeros)
							.$this->formataCampoNumerico('00',2)
							//Grupo de Valor: “00” (Zeros)
							.$this->formataCampoNumerico('00',2)
							//Complemento do Registro: “Brancos”
							.$this->espacosBrancos(3)// --
							.$this->espacosBrancos(1)// -Indicativo de Mensagem ou Sacador/Avalista 
							.$this->espacosBrancos(3)// --
							//Variação da Carteira 
							.$this->formataCampoNumerico($variacao_carteira,3)//002
							//Conta Caução: “0” (Zero)
							.$this->formataCampoNumerico('00',1)
							//Número do Borderô: “000000” (Zeros) 
							.$this->formataCampoNumerico('000000',6)
							//Tipo de Cobrança (04DSC/08VDR/02VIN/BRANCOS)
							.$this->formataCampoString(TIPO_COBRANCA_VAZIO,5)//04DSC
							//Carteira de Cobrança 
							.$this->formataCampoNumerico($cd_carteira,2)//18
							//Comando (REMESSA/BAIXA)
							.$this->formataCampoNumerico($cd_comando,2)//01
							//Seu Número/Número do Título Atribuído pelo Cedente
							.$this->formataCampoString($cd_titulo,10)//15038     
							//Data de Vencimento 
							.$this->formataCampoNumerico($dt_vencto,6) //230916
							//Valor do Título 
							.$this->formataCampoNumerico($vl_titulo,13)
							//Número do Banco: “001”
							.$this->formataCampoNumerico('001',3)
							//Prefixo da Agência Cobradora: “0000” 
							.$this->formataCampoNumerico('0000',4)
							//Dígito Verificador do Prefixo da Agência Cobradora: “Brancos”
							.$this->espacosBrancos(1)// --
							//Espécie de Titulo (12 - Duplicata de Serviço)
							.$this->formataCampoNumerico(ESPECIE_TITULO,2)//12
							//Aceite do Título: (A-ACEITE/N-NEGADO)
							.$this->formataCampoString(ACEITO,1)//A
							//Data de Emissão: Informe no formato “DDMMAA”
							.$this->formataCampoNumerico($dt_emissao,6)//210716
							//Instrução Codificada 
							.$this->formataCampoNumerico($cd_instrucao1,2)//00
							.$this->formataCampoNumerico($cd_instrucao2,2)//00
							//Juros de Mora por Dia de Atraso 
							.$this->formataCampoNumerico($vl_taxa_juros,13)//00000000013
							//Data Limite para Concessão de Desconto/Data de Operação do BBVendor/Juros de Mora.
							.$this->formataCampoNumerico($dt_desconto,6)//000000
							//Valor do Desconto 
							.$this->formataCampoNumerico($vl_desconto,13)//00000000000
							//Valor do IOF/Qtde Unidade Variável. 
							.$this->formataCampoNumerico($vl_iof,13)//00000000000
							//Valor do Abatimento 
							.$this->formataCampoNumerico($vl_abatimento,13)//00000000000
							//Tipo de Inscrição do Sacado 
							.$this->formataCampoNumerico($this->identificacaoCPFCNPJ($cpf_cnpj),2)//01
							//Número do CNPJ ou CPF do Sacado
							.$this->formataCampoNumerico($cpf_cnpj,14)//00001528230000
							//Nome do Sacado 
							.$this->formataCampoString(strtoupper($nm_pagador),37)//Andrew cristiano                     --
							//Complemento do Registro: “Brancos”
							.$this->espacosBrancos(3)
							//Endereço do Sacado
							.$this->formataCampoString(strtoupper($endereco_pagador),40)//Av. mariluz norte 
							//Bairro do Sacado
							.$this->formataCampoString(strtoupper($bairro_pagador),12)//Mariluz nort
							//CEP do Endereço do Sacado 
							.$this->formataCampoNumerico($cep_pagador,8)//95625000
							//Cidade do Sacado
							.$this->formataCampoString(strtoupper($nm_cidade_pagador),15)//Imbe           --
							//UF da Cidade do Sacado
							.$this->formataCampoString($uf_pagador,2)//RS
							//Observações/Mensagem ou Sacador/Avalista
							.$this->espacosBrancos(40)
							//Número de Dias Para Protesto
							.$this->formataCampoString($nr_dias_protesto,2)//  --
							//Complemento do Registro: “Brancos”
							.$this->espacosBrancos(1)// --
							//Seqüencial de Registro
							.$this->formataCampoNumerico($nr_sequencia_registro,6));//000002
	}
	/**
	 * [registroTrailler description]
	 * @param  [type] $vl_total              [description]
	 * @param  [type] $nr_sequencia_registro [description]
	 * @return [type]                        [description]
	 */
	function registroTrailler($nr_sequencia_registro){
		$this->escreveLinha('9'
							.$this->espacosBrancos(393)
							.$this->formataCampoNumerico($nr_sequencia_registro,6));
	}
	/**
	 * [formataDinheiro description]
	 * @param  [type] $valor [description]
	 * @return [type]        [description]
	 */
	private function formataDinheiro($valor){
        return $valor ? number_format($valor, 2, /*'.'*/'', '') : '';
    }
	/**
	 * [getAgenciaDV description]
	 * @param  [type] $cd_agencia [description]
	 * @return [type]             [description]
	 */
	private function getAgenciaDV($cd_agencia){
		return substr($cd_agencia, -1);
	}
	/**
	 * [getContaDV description]
	 * @param  [type] $cd_ctacor  [description]
	 * @return [type]             [description]
	 */
	private function getContaDV($cd_ctacor){
		return substr($cd_ctacor, -1);
	}
	/**
	 * [getNossoNumero description]
	 * @param  [type] $cd_convenio  [description]
	 * @param  [type] $nosso_numero [description]
	 * @return [type]               [description]
	 */
    private function getNossoNumero($cd_carteira, $cd_convenio, $nosso_numero){
        $numero = null;
        switch (strlen($cd_convenio)) {
            case 4: // Convênio de 4 dígitos, são 11 dígitos no nosso número
                $numero = $this->formataCampoNumerico($cd_convenio, 4) . $this->formataCampoNumerico($nosso_numero, 7);
                break;
            case 6:// Convênio de 6 dígitos, são 11 dígitos no nosso número
                if ($cd_carteira == 21) {// Exceto no caso de ter a carteira 21, onde são 17 dígitos
                    $numero = $this->formataCampoNumerico($nosso_numero, 17);
                } else {
                    $numero = $this->formataCampoNumerico($cd_convenio, 6) . $this->formataCampoNumerico($nosso_numero, 5);
                }
                break;
            case 7: // Convênio de 7 dígitos, são 17 dígitos no nosso número
                $numero = $this->formataCampoNumerico($cd_convenio, 7) . $this->formataCampoNumerico($nosso_numero, 10);
                break;
            default:
                $numero = $this->formataCampoNumerico($nosso_numero, 17);
        }

        // Quando o nosso número tiver menos de 17 dígitos, colocar o dígito
        if (strlen($numero) < 17) {
            $numero .= $this->modulo11($numero);
        }

        return $numero;
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
     * [modulo11 description]
     * @param  [type]  $num  [description]
     * @param  integer $base [description]
     * @param  integer $r    [description]
     * @return [type]        [description]
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
	/**
	 * [formataNumero description]
	 * @param  [type]  $valor            [description]
	 * @param  integer $numCasasDecimais [description]
	 * @return [type]                    [description]
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
	/**
	 * [formataData description]
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	function formataData($data) {
		if($data == '') return '';
		$data = substr($data, 0, 4).'-'.substr($data, 4, 2).'-'.substr($data, 6, 2);
		return date("Y-m-d", strtotime($data));
	}
	/**
	 * [identificacaoCPFCNPJ description]
	 * @param  [type] $valor [description]
	 * @return [type]        [description]
	 */
	private function identificacaoCPFCNPJ($valor){
		return strlen(preg_replace("/\D+/", "", $valor)) <= 11 ? '01' : '02'; 
	}
	/**
	 * [formataCampoString description]
	 * @param  [type] $vl_campo [description]
	 * @param  [type] $tamanho  [description]
	 * @return [type]           [description]
	 */
	private function formataCampoString($vl_campo, $tamanho){
		if (strlen($vl_campo) >= $tamanho) {
			$vl_valor =	substr($vl_campo,0,$tamanho);
		}else{
			$vl_valor = $vl_campo.$this->espacosBrancos($tamanho - strlen($vl_campo));
		}
		return $vl_valor;
	}
	/**
	 * [formataCampoNumerico description]
	 * @param  [type] $vl_campo [description]
	 * @param  [type] $tamanho  [description]
	 * @return [type]           [description]
	 */
	private function formataCampoNumerico($vl_campo, $tamanho){
		if (strlen($vl_campo) >= $tamanho) {
			$vl_valor =	substr($vl_campo,0,$tamanho);
		}else{
			$vl_valor = $this->zeros($tamanho - strlen($vl_campo)).$vl_campo;
		}
		return $vl_valor;
	}
	/**
	 * [espacosBrancos description]
	 * @param  [type] $numero [description]
	 * @return [type]         [description]
	 */
	private function espacosBrancos($numero) {
		return str_repeat(" ",$numero);
	}
	/**
	 * [zeros description]
	 * @param  [type] $numero [description]
	 * @return [type]         [description]
	 */
	private function zeros($numero) {
		return str_repeat('0',$numero);
	}
}