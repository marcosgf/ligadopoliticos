<?php

	error_reporting(E_ALL);
	//Login:Senha
	$login = "????:????" ;

	/* Funções referentes as tabelas do bando de dados.
	 * Encontra um certo dado e o atualiza ou
	 * Insere um novo dado no banco.
	*/


	// returna o id do politico se o mesmo existir	
	function existePoli($nome, $data, $cidade){

		$format = 'application/sparql-results+xml';
		
		$endereco = "select ?id {?id foaf:name ?name.
					 ?id foaf:birthday ?birthday.
					 ?id being:place-of-birth ?city.
			    		 FILTER regex(?name, '$nome', \"i\")
			   		 FILTER regex(?birthday, '$data', \"i\")
			    		 FILTER regex(?city, '$cidade', \"i\")
           			}";

		$url = urlencode($endereco);
		$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'+limit+1';

		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
	    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
	    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
	    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
	    	$resposta = curl_exec( $curl );
	    	curl_close($curl);


		$resposta =  str_replace("http://ligadonospoliticos.com.br/politico/","", $resposta);		
		
		if($resposta == null){return 0;}
		if($resposta != null){return $resposta;}

	}



	function afastamento($id_politico, $cargo, $cargo_uf, $data, $tipo, $motivo){


		$format = 'application/sparql-results+json';
		
		$endereco = "select ?cargo ?cargo_uf ?tipo ?motivo {  
					 <http://ligadonospoliticos.com.br/politico/$id_politico> polbr:absence ?absence.
					 ?absence timeline:atDate \"$data\".
					 OPTIONAL { ?absence  pol:Office ?cargo}
					 OPTIONAL { ?absence  geospecies:State ?cargo_uf }
					 OPTIONAL { ?absence dcterms:type ?tipo }
					 OPTIONAL { ?absence  event:fact ?motivo }
           			}";

		

		$url = urlencode($endereco);
		$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'+limit+1';

		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
	    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
	    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
	    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
	    	$resposta = curl_exec( $curl );
	    	curl_close($curl);


		$achou = false;
		$resposta_decoded = json_decode($resposta);

		//verifica se algum valor da consulta é diferente de null
		foreach($resposta_decoded->results->bindings as $reg){
			if($reg != null)
			{
				$achou = true;
			}
		}

		//existe o afastamento
		if($achou){

			
			$objects = array();
		        $results = json_decode($resposta);//descodifica o objeto json para um array
			//pega o valor dentro de dois array
			    foreach($results->results->bindings as $reg){
				   $obj = new stdClass();
				   foreach($reg as $field => $value){
				       $obj->$field = $value->value;
				   }
				   $objects[] = $obj;//guarda no array o objeto pretendido
			       }


			if (!empty($objects[0]->cargo)){ $NewCargo = $objects[0]->cargo ;}else{ $NewCargo = null;}
			if (!empty($objects[0]->cargo_uf)){ $NewCargoUf = $objects[0]->cargo_uf ;}else{ $NewCargoUf = null;}
			if (!empty($objects[0]->tipo)){ $NewTipo = $objects[0]->tipo ;}else{ $NewTipo = null;}
			if (!empty($objects[0]->motivo)){ $NewMotivo = $objects[0]->motivo ;}else{ $NewMotivo = null;}

			
			$format = 'application/sparql-results+xml';

			//deletando dados para inserir dados novos
			$where = "WHERE { <http://ligadonospoliticos.com.br/politico/$id_politico> polbr:absence ?S .
    					?S timeline:atDate \"$data\". };";			

			$endereco = "DELETE { ?S pol:Office \"$NewCargo\" } $where
				    DELETE { ?S geospecies:State \"$NewCargoUf\" }  $where
				    DELETE { ?S dcterms:type \"$NewTipo\" } $where
				    DELETE { ?S event:fact \"$NewMotivo\" } $where
				";
			

			$url = urlencode($endereco);
			$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'';		


			$curl = curl_init();
			curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
		    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST"); // Delete precisa ser feito por POST
		    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
		    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
		    	$resposta = curl_exec( $curl );

		    	curl_close($curl);

			

			if ($cargo != null ){ $NewCargo = $cargo ;}else{ $NewCargo = $objects[0]->cargo ;}
			if ($cargo_uf != null){ $NewCargoUf = $cargo_uf ;}else{ $NewCargoUf = $objects[0]->cargo_uf;}
			if ($tipo != null){ $NewTipo = $tipo ;}else{ $NewTipo = $objects[0]->tipo;}
			if ($motivo != null){ $NewMotivo = $motivo ;}else{ $NewMotivo = $objects[0]->motivo;}


			//inserindo os novos
			$endereco = "insert {  
						 ?S pol:Office \"$NewCargo\".
					         ?S geospecies:State \"$NewCargoUf\" .
				                 ?S dcterms:type \"$NewTipo\" .
				                 ?S event:fact \"$NewMotivo \" .
		   			} where{ <http://ligadonospoliticos.com.br/politico/$id_politico> polbr:absence ?S. 
						?S timeline:atDate \"$data\". }"; 
			


			$url = urlencode($endereco);
			$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'';		


			$curl = curl_init();
			curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
		    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST"); 
		    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
		    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
		    	$resposta = curl_exec( $curl );

		    	curl_close($curl);



			
		}
		//nao existe o afastamento		
		else{

			$format = 'application/sparql-results+xml';
		
			$endereco = "insert data {  
						 <http://ligadonospoliticos.com.br/politico/$id_politico> polbr:absence _:blank .
						 _:blank pol:Office \"$cargo\" .
					         _:blank geospecies:State \"$cargo_uf\" .
					         _:blank timeline:atDate \"$data\" .
				                 _:blank dcterms:type \"$tipo\" .
				                 _:blank event:fact \"$motivo\" .
		   			}";


			$url = urlencode($endereco);
			$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'';			

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
		    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST"); 
		    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
		    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
		    	$resposta = curl_exec( $curl );
		    	curl_close($curl);


		}


	}

	
	function comissao($id_politico, $participacao, $descricao, $data_inicio, $data_fim){

		$format = 'application/sparql-results+json';
		
		$endereco = "select ?data_fim ?participacao {  
					 <http://ligadonospoliticos.com.br/politico/$id_politico>  polbr:committee ?committee.
					 ?committee timeline:beginsAtDateTime \"$data_inicio\".
					 ?committee dcterms:description \"$descricao\" . 
					 OPTIONAL { ?committee  timeline:endsAtDateTime ?data_fim}
					 OPTIONAL { ?committee  vcard:role ?participacao }

           			}";

		

		$url = urlencode($endereco);
		$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'+limit+1';

		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
	    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
	    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
	    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
	    	$resposta = curl_exec( $curl );
	    	curl_close($curl);


		$achou = false;
		$resposta_decoded = json_decode($resposta);

		
		//verifica se algum valor da consulta é diferente de null
		foreach($resposta_decoded->results->bindings as $reg){
			if($reg != null)
			{
				$achou = true;
			}
		}

		if($achou){

		

			$objects = array();
		        $results = json_decode($resposta);//descodifica o objeto json para um array
			//pega o valor dentro de dois array
			    foreach($results->results->bindings as $reg){
				   $obj = new stdClass();
				   foreach($reg as $field => $value){
				       $obj->$field = $value->value;
				   }
				   $objects[] = $obj;//guarda no array o objeto pretendido
			       }


			if (!empty($objects[0]->data_fim)){ $NewDataFim = $objects[0]->data_fim ;}else{ $NewDataFim = null;}
			if (!empty($objects[0]->participacao)){ $NewParticipacao = $objects[0]->participacao ;}else{ $NewParticipacao = null;}

			
			$format = 'application/sparql-results+xml';

			//deletando dados para inserir dados novos
			$where = "WHERE { <http://ligadonospoliticos.com.br/politico/$id_politico> polbr:committee ?S .
					?S timeline:beginsAtDateTime \"$data_inicio\".
					?S dcterms:description \"$descricao\" . 
};";			


			$endereco = "DELETE { ?S timeline:endsAtDateTime \"$NewDataFim\" } $where
				    DELETE { ?S vcard:role \"$NewParticipacao\" }  $where
				";
			
			$url = urlencode($endereco);
			$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'';		

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
		    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST"); // Delete precisa ser feito por POST
		    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
		    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
		    	$resposta = curl_exec( $curl );

		    	curl_close($curl);

			

			if ($data_fim != null ){ $NewDataFim = $data_fim ;}else{ $NewDataFim= $objects[0]->data_fim ;}
			if ($participacao != null){ $NewParticipacao = $participacao ;}else{ $NewParticipacao = $objects[0]->participacao;}
			

			//inserindo os novos
			$endereco = "insert {  
						 ?S timeline:endsAtDateTimee \"$NewDataFim\".
					         ?S vcard:role \"$NewParticipacao\" .

		   			} where{ <http://ligadonospoliticos.com.br/politico/$id_politico> polbr:committee ?S. 
					 ?S timeline:beginsAtDateTime \"$data_inicio\".
					 ?S dcterms:description \"$descricao\" .  }"; 
			


			$url = urlencode($endereco);
			$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'';		

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
		    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST"); 
		    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
		    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
		    	$resposta = curl_exec( $curl );

		    	curl_close($curl);


		}
		else{

			//é feita uma contagem de quantas comissões ele tem, para saber qual numero da proxima

			$format = 'text/integer';
			$endereco = "select ?committee {  
						 <http://ligadonospoliticos.com.br/politico/$id_politico>  polbr:committee ?committee.
	
		   			}";

			$url = urlencode($endereco);
			$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'';
		
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
		    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
		    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
		    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
		    	$contador = curl_exec( $curl );
		    	curl_close($curl);



			//agora é feita a inserção

			$contador = $contador + 1 ;

			$format = 'application/sparql-results+xml';
		
			$endereco = "insert data {  
						 <http://ligadonospoliticos.com.br/politico/$id_politico> polbr:committee _:blank .
						 _:blank biblio:number \"$contador\" .
					         _:blank dcterms:description \"$descricao\" .
					         _:blank timeline:beginsAtDateTime \"$data_inicio\" .
				                 _:blank timeline:endsAtDateTime \"$data_fim\" .
				                 _:blank vcard:role \"$participacao\" .
		   			}";


			$url = urlencode($endereco);
			$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'';			

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
		    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST"); 
		    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
		    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
		    	$resposta = curl_exec( $curl );
		    	curl_close($curl);


		}		

	}
	

		
	function declaracao_bens($id_politico, $ano, $descricao, $tipo, $valor){

		$format = 'application/sparql-results+json';
		
		$endereco = "select ?tipo ?valor {  
					 <http://ligadonospoliticos.com.br/politico/$id_politico>  polbr:declarationOfAssets ?declarationOfAssets.
					 ?declarationOfAssets timeline:atYear \"$ano\".
					 ?declarationOfAssets polbr:DeclarationOfAssets ?DeclarationOfAssets .
					 ?DeclarationOfAssets dcterms:description \"$descricao\" . 
					 OPTIONAL { ?DeclarationOfAssets dcterms:type ?tipo}
					 OPTIONAL { ?DeclarationOfAssets rdfmoney:Price ?valor }

           			}";


		$url = urlencode($endereco);
		$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'+limit+1';

		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
	    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
	    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
	    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
	    	$resposta = curl_exec( $curl );
	    	curl_close($curl);


		$achou = false;
		$resposta_decoded = json_decode($resposta);

		
		//verifica se algum valor da consulta é diferente de null
		foreach($resposta_decoded->results->bindings as $reg){
			if($reg != null)
			{
				$achou = true;
			}
		}
	
		
		if($achou){
		
		$objects = array();
		        $results = json_decode($resposta);//descodifica o objeto json para um array
			//pega o valor dentro de dois array
			    foreach($results->results->bindings as $reg){
				   $obj = new stdClass();
				   foreach($reg as $field => $value){
				       $obj->$field = $value->value;
				   }
				   $objects[] = $obj;//guarda no array o objeto pretendido
			       }


			if (!empty($objects[0]->tipo)){ $NewTipo = $objects[0]->tipo ;}else{ $NewTipo = null;}
			if (!empty($objects[0]->valor)){ $NewValor = $objects[0]->valor ;}else{ $NewValor = null;}

			
			$format = 'application/sparql-results+xml';

			//deletando dados para inserir dados novos
			$where = "WHERE { <http://ligadonospoliticos.com.br/politico/$id_politico> polbr:declarationOfAssets ?declarationOfAssets.
					 ?declarationOfAssets timeline:atYear \"$ano\".
					 ?declarationOfAssets polbr:DeclarationOfAssets ?DeclarationOfAssets .
					 ?DeclarationOfAssets dcterms:description \"$descricao\" .
};";			


			$endereco = "DELETE { ?DeclarationOfAssets dcterms:type \"$NewTipo\" } $where
				    DELETE { ?DeclarationOfAssets rdfmoney:Price \"$NewValor\" }  $where
				";
			
			$url = urlencode($endereco);
			$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'';		

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
		    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST"); // Delete precisa ser feito por POST
		    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
		    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
		    	$resposta = curl_exec( $curl );

		    	curl_close($curl);

			

			if ($tipo != null ){ $NewTipo = $tipo ;}else{ $NewTipo= $objects[0]->tipo ;}
			if ($valor != null){ $NewValor = $valor ;}else{ $NewValor = $objects[0]->valor;}
			

			//inserindo os novos
			$endereco = "insert {  
						 ?DeclarationOfAssets dcterms:type \"$NewTipo\".
					        ?DeclarationOfAssets rdfmoney:Price \"$NewValor\".

		   			} where{ <http://ligadonospoliticos.com.br/politico/$id_politico> polbr:declarationOfAssets ?declarationOfAssets.
					 ?declarationOfAssets timeline:atYear \"$ano\".
					 ?declarationOfAssets polbr:DeclarationOfAssets ?DeclarationOfAssets .
					 ?DeclarationOfAssets dcterms:description \"$descricao\" .}"; 
			


			$url = urlencode($endereco);
			$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'';		

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
		    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST"); 
		    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
		    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
		    	$resposta = curl_exec( $curl );

		    	curl_close($curl);


		}
		else{
	
			//é feita uma contagem de quantas declarações ele tem, para saber qual numero da proxima

			$format = 'text/integer';
			$endereco = "select ?DeclarationOfAssets {  
						 <http://ligadonospoliticos.com.br/politico/$id_politico>  polbr:declarationOfAssets ?declarationOfAssets.
				?declarationOfAssets timeline:atYear \"$ano\".
				?declarationOfAssets polbr:DeclarationOfAssets ?DeclarationOfAssets.
	
		   			}";

			$url = urlencode($endereco);
			$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'';
		
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
		    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
		    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
		    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
		    	$contador = curl_exec( $curl );
		    	curl_close($curl);



			//agora é feita a inserção

			$contador = $contador + 1 ;
			
			$format = 'application/sparql-results+xml';
		
			$endereco = "insert data {  
						 <http://ligadonospoliticos.com.br/politico/$id_politico> polbr:declarationOfAssets _:declaration .
						 _:declaration timeline:atYear \"$ano\" .
						 _:declaration polbr:DeclarationOfAssets _:DeclarationOf . 
					         _:DeclarationOf rdf:type being:owns .
					         _:DeclarationOf biblio:number \"$contador\" .
				                 _:DeclarationOf dcterms:description \"$descricao\" .
				                 _:DeclarationOf dcterms:type \"$tipo\" .
						 _:DeclarationOf rdfmoney:Price \"$valor\" .
		   			}";


			$url = urlencode($endereco);
			$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'';			

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
		    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST"); 
		    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
		    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
		    	$resposta = curl_exec( $curl );
		    	curl_close($curl);


		}		

	}



	function eleicao($id_politico, $ano, $nome_urna, $numero_candidato, $partido, $cargo, $cargo_uf, $resultado, $nome_coligacao, $partidos_coligacao, $situacao_candidatura, $numero_protocolo, $numero_processo, $cnpj_campanha){

		$format = 'application/sparql-results+json';
		
		$endereco = "select ?nome_urna ?numero_candidato ?partido ?cargo ?cargo_uf ?resultado ?nome_coligacao ?partidos_coligacao ?situacao_candidatura ?numero_protocolo ?numero_processo ?cnpj_campanha {  
					 <http://ligadonospoliticos.com.br/politico/$id_politico>  polbr:election ?election.
					 ?election timeline:atYear \"$ano\".
					 OPTIONAL { ?election foaf:name ?nome_urna}
					 OPTIONAL { ?election biblio:number ?numero_candidato }
					 OPTIONAL { ?election pol:party ?partido }
					 OPTIONAL { ?election pol:Office ?cargo }
					 OPTIONAL { ?election geospecies:State ?cargo_uf }
					 OPTIONAL { ?election earl:outcome ?resultado }
					 OPTIONAL { ?election spinrdf:Union ?nome_coligacao }
					 OPTIONAL { ?election polbr:unionParties ?partidos_coligacao }
					 OPTIONAL { ?election polbr:situation ?situacao_candidatura }
					 OPTIONAL { ?election polbr:protocolNumber ?numero_protocolo }
					 OPTIONAL { ?election polbr:processNumber ?numero_processo }
					 OPTIONAL { ?election polbr:CNPJ ?cnpj_campanha }


           			}";


		$url = urlencode($endereco);
		$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'+limit+1';

		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
	    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
	    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
	    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
	    	$resposta = curl_exec( $curl );
	    	curl_close($curl);


		$achou = false;
		$resposta_decoded = json_decode($resposta);

		
		//verifica se algum valor da consulta é diferente de null
		foreach($resposta_decoded->results->bindings as $reg){
			if($reg != null)
			{
				$achou = true;
			}
		}
	
		if($achou){
		
		$objects = array();
		        $results = json_decode($resposta);//descodifica o objeto json para um array
			//pega o valor dentro de dois array
			    foreach($results->results->bindings as $reg){
				   $obj = new stdClass();
				   foreach($reg as $field => $value){
				       $obj->$field = $value->value;
				   }
				   $objects[] = $obj;//guarda no array o objeto pretendido
			       }


			if (!empty($objects[0]->nome_urna)){ $NewNomeUrna = $objects[0]->nome_urna ;}else{ $NewNomeUrna = null;}
			if (!empty($objects[0]->numero_candidato)){ $NewNrCandidato = $objects[0]->numero_candidato ;}else{ $NewNrCandidato = null;}
			if (!empty($objects[0]->partido)){ $NewPartido = $objects[0]->partido ;}else{ $NewPartido = null;}
			if (!empty($objects[0]->cargo)){ $NewCargo = $objects[0]->cargo ;}else{ $NewCargo = null;}
			if (!empty($objects[0]->cargo_uf)){ $NewCargoUf = $objects[0]->cargo_uf ;}else{ $NewCargoUf = null;}
			if (!empty($objects[0]->resultado)){ $NewResultado = $objects[0]->resultado ;}else{ $NewResultado = null;}
			if (!empty($objects[0]->nome_coligacao)){ $NewNomeColigacao = $objects[0]->nome_coligacao ;}else{ $NewNomeColigacao = null;}
			if (!empty($objects[0]->partidos_coligacao)){ $NewPartidosColigacao  = $objects[0]->partidos_coligacao  ;}else{ $NewPartidosColigacao  = null;}
			if (!empty($objects[0]->situacao_candidatura)){ $NewSituacaoCandidatura = $objects[0]->situacao_candidatura ;}else{ $NewSituacaoCandidatura = null;}
			if (!empty($objects[0]->numero_protocolo)){ $NewNrProtocolo = $objects[0]->numero_protocolo ;}else{ $NewNrProtocolo = null;}
			if (!empty($objects[0]->numero_processo)){ $NewNrProcesso = $objects[0]->numero_processo ;}else{ $NewNrProcesso = null;}
			if (!empty($objects[0]->cnpj_campanha)){ $NewCnpjCampanha = $objects[0]->cnpj_campanha ;}else{ $NewCnpjCampanha = null;}
	

			
			$format = 'application/sparql-results+xml';

			//deletando dados para inserir dados novos
			$where = "WHERE { <http://ligadonospoliticos.com.br/politico/$id_politico>  polbr:election ?election.
					 ?election timeline:atYear \"$ano\".
};";			

			$endereco = "DELETE { ?election foaf:name \"$NewNomeUrna\" } $where
				     DELETE { ?election biblio:number \"$NewNrCandidato\" } $where
				     DELETE { ?election pol:party \"$NewPartido\" } $where
				     DELETE { ?election pol:Office \"$NewCargo\" } $where
				     DELETE { ?election geospecies:State \"$NewCargoUf\" } $where
				     DELETE { ?election earl:outcome \"$NewResultado\" } $where
				     DELETE { ?election spinrdf:Union \"$NewNomeColigacao\" } $where
				     DELETE { ?election polbr:unionParties \"$NewPartidosColigacao\" } $where
				     DELETE { ?election polbr:situation \"$NewSituacaoCandidatura\" } $where
				     DELETE { ?election polbr:protocolNumber \"$NewNrProtocolo\" } $where
				     DELETE { ?election polbr:processNumber \"$NewNrProcesso\" } $where
				     DELETE { ?election polbr:CNPJ \"$NewCnpjCampanha\" } $where

				";
			
			$url = urlencode($endereco);
			$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'';		

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
		    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST"); // Delete precisa ser feito por POST
		    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
		    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
		    	$resposta = curl_exec( $curl );

		    	curl_close($curl);


			if ($nome_urna != null ){ $NewNomeUrna = $nome_urna ;}else{ $NewNomeUrna = $objects[0]->nome_urna ;}
			if ($numero_candidato != null){ $NewNrCandidato = $numero_candidato ;}else{ $NewNrCandidato = $objects[0]->numero_candidato;}
			if ($partido != null ){ $NewPartido = $partido ;}else{ $NewPartido = $objects[0]->partido ;}
			if ($cargo != null ){ $NewCargo = $cargo ;}else{ $NewCargo = $objects[0]->cargo ;}
			if ($cargo_uf != null ){ $NewCargoUf = $cargo_uf ;}else{ $NewCargoUf = $objects[0]->cargo_uf ;}
			if ($resultado != null ){ $NewResultado = $resultado ;}else{ $NewResultado = $objects[0]->resultado ;}
			if ($nome_coligacao != null ){ $NewNomeColigacao = $nome_coligacao ;}else{ $NewNomeColigacao = $objects[0]->nome_coligacao ;}
			if ($partidos_coligacao != null ){ $NewPartidosColigacao = $partidos_coligacao ;}else{ $NewPartidosColigacao= $objects[0]->partidos_coligacao ;}
			if ($situacao_candidatura != null ){ $NewSituacaoCandidatura = $situacao_candidatura ;}else{ $NewSituacaoCandidatura = $objects[0]->situacao_candidatura ;}
			if ($numero_protocolo != null ){ $NewNrProtocolo = $numero_protocolo ;}else{ $NewNrProtocolo = $objects[0]->numero_protocolo ;}
			if ($numero_processo != null ){ $NewNrProcesso = $numero_processo ;}else{ $NewNrProcesso = $objects[0]->numero_processo ;}
			if ($cnpj_campanha != null ){ $NewCnpjCampanha = $cnpj_campanha ;}else{ $NewCnpjCampanha = $objects[0]->cnpj_campanha ;}

			

			//inserindo os novos
			$endereco = "insert {  
						  ?election foaf:name \"$NewNomeUrna\" .
						  ?election biblio:number \"$NewNrCandidato\" .
						  ?election pol:party \"$NewPartido\" .
						  ?election pol:Office \"$NewCargo\" .
						  ?election geospecies:State \"$NewCargoUf\" .
						  ?election earl:outcome \"$NewResultado\" .
						  ?election spinrdf:Union \"$NewNomeColigacao\" .
						  ?election polbr:unionParties \"$NewPartidosColigacao\".
						  ?election polbr:situation \"$NewSituacaoCandidatura\" .
						  ?election polbr:protocolNumber \"$NewNrProtocolo\" .
						  ?election polbr:processNumber \"$NewNrProcesso\" .
						  ?election polbr:CNPJ \"$NewCnpjCampanha\" .

		   			} where{ <http://ligadonospoliticos.com.br/politico/$id_politico>  polbr:election ?election.
					 ?election timeline:atYear \"$ano\".}"; 
			


			$url = urlencode($endereco);
			$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'';		

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
		    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST"); 
		    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
		    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
		    	$resposta = curl_exec( $curl );

		    	curl_close($curl);


		}
		else{
			
			$format = 'application/sparql-results+xml';
		
			$endereco = "insert data {  
						 <http://ligadonospoliticos.com.br/politico/$id_politico>  polbr:election _:election.
						 _:election timeline:atYear \"$ano\".
						 _:election foaf:name \"$nome_urna\" .
						 _:election biblio:number \"$numero_candidato\" .
						 _:election pol:party \"$partido\" .
						 _:election pol:Office \"$cargo\" .
						 _:election geospecies:State \"$cargo_uf\" .
						 _:election earl:outcome \"$resultado\" .
						 _:election spinrdf:Union \"$nome_coligacao\" .
						 _:election polbr:unionParties \"$partidos_coligacao\".
						 _:election polbr:situation \"$situacao_candidatura\" .
						 _:election polbr:protocolNumber \"$numero_protocolo\" .
						 _:election polbr:processNumber \"$numero_processo\" .
						 _:election polbr:CNPJ \"$cnpj_campanha\" .
		   			}";


			$url = urlencode($endereco);
			$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'';			

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
		    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST"); 
		    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
		    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
		    	$resposta = curl_exec( $curl );
		    	curl_close($curl);


		}
		
	}

	
		
	function endereco_parlamentar_politico($id_politico, $anexo, $ala, $gabinete, $email, $telefone, $fax, $tipo, $rua, $bairro, $cidade, $estado, $CEP, $CNPJ, $telefone_parlamento, $disque, $site){


		$format = 'application/sparql-results+json';
		
		$politico = "<http://ligadonospoliticos.com.br/politico/$id_politico>";

		$endereco = "select ?gabinete ?email ?fax ?tipo ?rua ?bairro ?cidade ?estado ?CEP ?CNPJ ?telefone_parlamento ?disque ?site {  
					 $politico polbr:annex \"$anexo\" .
					 $politico  polbr:wing \"$ala\" .
					 $politico foaf:phone \"$telefone\" .
					 OPTIONAL { $politico polbr:cabinet ?gabinete}
					 OPTIONAL { $politico biblio:Email ?email }
					 OPTIONAL { $politico vcard:fax ?fax }
					 OPTIONAL { $politico po:Place ?tipo }
					 OPTIONAL { $politico vcard:street-address ?rua }
					 OPTIONAL { $politico polbr:district ?bairro }
					 OPTIONAL { $politico vcard:locality ?cidade }
					 OPTIONAL { $politico geospecies:State ?estado }
					 OPTIONAL { $politico vcard:postal-code ?CEP }
					 OPTIONAL { $politico polbr:CNPJ ?CNPJ }
					 OPTIONAL { $politico foaf:phone ?telefone_parlamento }
					 OPTIONAL { $politico foaf:phone ?disque }
					 OPTIONAL { $politico foaf:homepage ?site }
					 FILTER ( ?telefone != ?telefone_parlamento )
  					 FILTER ( ?telefone != ?disque )
  					 FILTER ( ?disque != ?telefone_parlamento )
					 FILTER regex(?disque, \"0800\")

           			}";


		$url = urlencode($endereco);
		$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'+limit+1';

		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
	    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
	    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
	    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
	    	$resposta = curl_exec( $curl );
	    	curl_close($curl);


		$achou = false;
		$resposta_decoded = json_decode($resposta);

		
		//verifica se algum valor da consulta é diferente de null
		foreach($resposta_decoded->results->bindings as $reg){
			if($reg != null)
			{
				$achou = true;
			}
		}

		if($achou){


		$objects = array();
		
	        $results = json_decode($resposta);//descodifica o objeto json para um array
			//pega o valor dentro de dois array
			    foreach($results->results->bindings as $reg){
				   $obj = new stdClass();
				   foreach($reg as $field => $value){
				       $obj->$field = $value->value;
				   }
				   $objects[] = $obj;//guarda no array o objeto pretendido
			       }


			if (!empty($objects[0]->gabinete)){ $NewGabinete = $objects[0]->gabinete ;}else{ $NewGabinete = null;}
			if (!empty($objects[0]->email)){ $NewEmail = $objects[0]->email ;}else{ $NewEmail = null;}
			if (!empty($objects[0]->fax)){ $NewFax = $objects[0]->fax ;}else{ $NewFax = null;}
			if (!empty($objects[0]->tipo)){ $NewTipo = $objects[0]->tipo ;}else{ $NewTipo = null;}
			if (!empty($objects[0]->rua)){ $NewRua = $objects[0]->rua ;}else{ $NewRua = null;}
			if (!empty($objects[0]->bairro)){ $NewBairro = $objects[0]->bairro ;}else{ $NewBairro = null;}
			if (!empty($objects[0]->cidade)){ $NewCidade = $objects[0]->cidade ;}else{ $NewCidade = null;}
			if (!empty($objects[0]->estado)){ $NewEstado  = $objects[0]->estado ;}else{ $NewEstado  = null;}
			if (!empty($objects[0]->CEP)){ $NewCEP = $objects[0]->CEP ;}else{ $NewCEP = null;}
			if (!empty($objects[0]->CNPJ)){ $NewCNPJ = $objects[0]->CNPJ ;}else{ $NewCNPJ = null;}
			if (!empty($objects[0]->telefone_parlamento)){ $NewTelefoneParl = $objects[0]->telefone_parlamento ;}else{ $NewTelefoneParl = null;}
			if (!empty($objects[0]->disque)){ $NewDisque = $objects[0]->disque ;}else{ $NewDisque = null;}
			if (!empty($objects[0]->site)){ $NewSite = $objects[0]->site ;}else{ $NewSite = null;}
	
			
			$format = 'application/sparql-results+xml';

			//deletando dados para inserir dados novos
			$where = "WHERE {  $politico polbr:annex \"$anexo\" .
					   $politico  polbr:wing \"$ala\" .
					   $politico foaf:phone \"$telefone\" .
};";			
		
			$endereco = "DELETE { $politico polbr:cabinet \"$NewGabinete\" } $where
				     DELETE { $politico biblio:Email \"$NewEmail\" } $where
				     DELETE { $politico vcard:fax \"$NewFax\" } $where
				     DELETE { $politico po:Place \"$NewTipo\" } $where
				     DELETE { $politico vcard:street-address \"$NewRua\" } $where
				     DELETE { $politico polbr:district \"$NewBairro\" } $where
				     DELETE { $politico vcard:locality \"$NewCidade\" } $where
				     DELETE { $politico geospecies:State \"$NewEstado\" } $where
				     DELETE { $politico vcard:postal-code \"$NewCEP\" } $where
				     DELETE { $politico polbr:CNPJ \"$NewCNPJ\" } $where
				     DELETE { $politico foaf:phone \"$NewTelefoneParl\" } $where
				     DELETE { $politico foaf:phone \"$NewDisque\" } $where
				     DELETE { $politico foaf:homepage \"$NewSite\" } $where

				";
			
			$url = urlencode($endereco);
			$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'';		

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
		    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST"); // Delete precisa ser feito por POST
		    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
		    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
		    	$resposta = curl_exec( $curl );

		    	curl_close($curl);



			if ($gabinete != null ){ $NewGabinete = $gabinete ;}else{ $NewGabinete= $objects[0]->gabinete ;}
			if ($email != null){ $NewEmail = $email ;}else{ $NewEmail = $objects[0]->email;}
			if ($fax != null ){ $NewFax = $fax ;}else{ $NewFax= $objects[0]->fax ;}
			if ($tipo != null ){ $NewTipo = $tipo ;}else{ $NewTipo= $objects[0]->tipo ;}
			if ($rua != null){ $NewRua = $rua ;}else{ $NewRua = $objects[0]->rua;}
			if ($bairro != null ){ $NewBairro = $bairro ;}else{ $NewBairro= $objects[0]->bairro ;}
			if ($cidade != null){ $NewCidade = $cidade ;}else{ $NewCidade = $objects[0]->cidade;}
			if ($estado != null){ $NewEstado = $estado ;}else{ $NewEstado = $objects[0]->estado;}
			if ($CEP != null ){ $NewCEP = $CEP ;}else{ $NewCEP= $objects[0]->CEP ;}
			if ($CNPJ != null){ $NewCNPJ = $CNPJ ;}else{ $NewCNPJ = $objects[0]->CNPJ;}
			if ($telefone_parlamento != null ){ $NewTelefoneParl = $telefone_parlamento ;}else{ $NewTelefoneParl= $objects[0]->telefone_parlamento ;}
			if ($disque != null){ $NewDisque = $disque ;}else{ $NewDisque = $objects[0]->disque;}
			if ($site != null ){ $NewSite = $site ;}else{ $NewSite= $objects[0]->site ;}


			//inserindo os novos
			$endereco = "insert {  
						  $politico polbr:cabinet \"$NewGabinete\".
				   		  $politico biblio:Email \"$NewEmail\" .
				     		  $politico vcard:fax \"$NewFax\" .
				     		  $politico po:Place \"$NewTipo\" .
				    		  $politico vcard:street-address \"$NewRua\" .
				     		  $politico polbr:district \"$NewBairro\" .
				     		  $politico vcard:locality \"$NewCidade\" .
				    		  $politico geospecies:State \"$NewEstado\" .
				     		  $politico vcard:postal-code \"$NewCEP\" .
				     		  $politico polbr:CNPJ \"$NewCNPJ\" .
				     		  $politico foaf:phone \"$NewTelefoneParl\" .
				     		  $politico foaf:phone \"$NewDisque\" .
				     		  $politico foaf:homepage \"$NewSite\" .

		   			} where{ $politico polbr:annex \"$anexo\" .
					 	 $politico  polbr:wing \"$ala\" .
					 	 $politico foaf:phone \"$telefone\" .}"; 
			


			$url = urlencode($endereco);
			$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'';		

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
		    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST"); 
		    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
		    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
		    	$resposta = curl_exec( $curl );

		    	curl_close($curl);

		}
		else{

			$format = 'application/sparql-results+xml';
		
			$endereco = "insert data {  
						 $politico polbr:annex \"$anexo\" .
					 	 $politico  polbr:wing \"$ala\" .
					 	 $politico foaf:phone \"$telefone\"
						 $politico polbr:cabinet \"$NewGabinete\".
				   		 $politico biblio:Email \"$NewEmail\" .
				     		 $politico vcard:fax \"$NewFax\" .
				     		 $politico po:Place \"$NewTipo\" .
				    		 $politico vcard:street-address \"$NewRua\" .
				     		 $politico polbr:district \"$NewBairro\" .
				     		 $politico vcard:locality \"$NewCidade\" .
				    		 $politico geospecies:State \"$NewEstado\" .
				     		 $politico vcard:postal-code \"$NewCEP\" .
				     		 $politico polbr:CNPJ \"$NewCNPJ\" .
				     		 $politico foaf:phone \"$NewTelefoneParl\" .
				     		 $politico foaf:phone \"$NewDisque\" .
				     		 $politico foaf:homepage \"$NewSite\" .
		   			}";


			$url = urlencode($endereco);
			$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'';			

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
		    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST"); 
		    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
		    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
		    	$resposta = curl_exec( $curl );
		    	curl_close($curl);


		}

		
	}
	

	
	
	function lideranca($id_politico, $descricao, $tipo, $data_inicio, $data_fim){

		$format = 'application/sparql-results+json';
		
		$endereco = "select ?data_fim ?tipo {  
					 <http://ligadonospoliticos.com.br/politico/$id_politico>  polbr:leadership ?leadership.
					 ?leadership timeline:beginsAtDateTime \"$data_inicio\".
					 ?leadership dcterms:description \"$descricao\" . 
					 OPTIONAL { ?leadership  timeline:endsAtDateTime ?data_fim}
					 OPTIONAL { ?leadership  dcterms:type ?tipo }

           			}";

		

		$url = urlencode($endereco);
		$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'+limit+1';

		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
	    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
	    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
	    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
	    	$resposta = curl_exec( $curl );
	    	curl_close($curl);


		$achou = false;
		$resposta_decoded = json_decode($resposta);

		
		//verifica se algum valor da consulta é diferente de null
		foreach($resposta_decoded->results->bindings as $reg){
			if($reg != null)
			{
				$achou = true;
			}
		}

		if($achou){


			$objects = array();
		        $results = json_decode($resposta);//descodifica o objeto json para um array
			//pega o valor dentro de dois array
			    foreach($results->results->bindings as $reg){
				   $obj = new stdClass();
				   foreach($reg as $field => $value){
				       $obj->$field = $value->value;
				   }
				   $objects[] = $obj;//guarda no array o objeto pretendido
			       }


			if (!empty($objects[0]->data_fim)){ $NewDataFim = $objects[0]->data_fim ;}else{ $NewDataFim = null;}
			if (!empty($objects[0]->tipo)){ $NewTipo = $objects[0]->tipo ;}else{ $NewTipo = null;}

			
			$format = 'application/sparql-results+xml';

			//deletando dados para inserir dados novos
			$where = "WHERE { <http://ligadonospoliticos.com.br/politico/$id_politico> polbr:leadership ?S .
					?S timeline:beginsAtDateTime \"$data_inicio\".
					?S dcterms:description \"$descricao\" . 
};";			


			$endereco = "DELETE { ?S timeline:endsAtDateTime \"$NewDataFim\" } $where
				    DELETE { ?S dcterms:type \"$NewTipo\" }  $where
				";
			
			$url = urlencode($endereco);
			$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'';		

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
		    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST"); // Delete precisa ser feito por POST
		    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
		    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
		    	$resposta = curl_exec( $curl );

		    	curl_close($curl);

			

			if ($data_fim != null ){ $NewDataFim = $data_fim ;}else{ $NewDataFim= $objects[0]->data_fim ;}
			if ($tipo != null){ $NewTipo = $tipo ;}else{ $NewTipo = $objects[0]->tipo;}
			

			//inserindo os novos
			$endereco = "insert {  
						 ?S timeline:endsAtDateTimee \"$NewDataFim\".
					         ?S dcterms:type \"$NewTipo\" .

		   			} where{ <http://ligadonospoliticos.com.br/politico/$id_politico> polbr:leadership ?S. 
					 ?S timeline:beginsAtDateTime \"$data_inicio\".
					 ?S dcterms:description \"$descricao\" .  }"; 
			


			$url = urlencode($endereco);
			$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'';		

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
		    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST"); 
		    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
		    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
		    	$resposta = curl_exec( $curl );

		    	curl_close($curl);


		}
		else{

			//é feita uma contagem de quantas comissões ele tem, para saber qual numero da proxima

			$format = 'text/integer';
			$endereco = "select ?leadership {  
						 <http://ligadonospoliticos.com.br/politico/$id_politico>  polbr:leadership ?leadership.
	
		   			}";

			$url = urlencode($endereco);
			$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'';
		
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
		    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
		    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
		    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
		    	$contador = curl_exec( $curl );
		    	curl_close($curl);



			//agora é feita a inserção

			$contador = $contador + 1 ;

			$format = 'application/sparql-results+xml';
		
			$endereco = "insert data {  
						 <http://ligadonospoliticos.com.br/politico/$id_politico> polbr:leadership _:blank .
						 _:blank biblio:number \"$contador\" .
					         _:blank dcterms:description \"$descricao\" .
					         _:blank timeline:beginsAtDateTime \"$data_inicio\" .
				                 _:blank timeline:endsAtDateTime \"$data_fim\" .
				                 _:blank dcterms:type \"$tipo\" .
		   			}";


			$url = urlencode($endereco);
			$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'';			

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
		    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST"); 
		    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
		    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
		    	$resposta = curl_exec( $curl );
		    	curl_close($curl);


		}		

	}
	
	
	function mandato($id_politico, $cargo, $data_inicio, $data_fim){

		$format = 'application/sparql-results+json';
		
		$endereco = "select ?data_fim {  
					 <http://ligadonospoliticos.com.br/politico/$id_politico>  pol:Term ?term.
					 ?term timeline:beginsAtDateTime \"$data_inicio\".
					 ?term pol:Office \"$cargo\" . 
					 OPTIONAL { ?term  timeline:endsAtDateTime ?data_fim}
					

           			}";

		

		$url = urlencode($endereco);
		$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'+limit+1';

		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
	    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
	    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
	    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
	    	$resposta = curl_exec( $curl );
	    	curl_close($curl);


		$achou = false;
		$resposta_decoded = json_decode($resposta);

		
		//verifica se algum valor da consulta é diferente de null
		foreach($resposta_decoded->results->bindings as $reg){
			if($reg != null)
			{
				$achou = true;
			}
		}

		if($achou){


			$objects = array();
		        $results = json_decode($resposta);//descodifica o objeto json para um array
			//pega o valor dentro de dois array
			    foreach($results->results->bindings as $reg){
				   $obj = new stdClass();
				   foreach($reg as $field => $value){
				       $obj->$field = $value->value;
				   }
				   $objects[] = $obj;//guarda no array o objeto pretendido
			       }


			if (!empty($objects[0]->data_fim)){ $NewDataFim = $objects[0]->data_fim ;}else{ $NewDataFim = null;}

			
			$format = 'application/sparql-results+xml';

			//deletando dados para inserir dados novos
			$where = "WHERE { <http://ligadonospoliticos.com.br/politico/$id_politico> pol:Term ?S .
					?S timeline:beginsAtDateTime \"$data_inicio\".
					?S pol:Office \"$cargo\" . 
};";			


			$endereco = "DELETE { ?S timeline:endsAtDateTime \"$NewDataFim\" } $where
				
				";
			
			$url = urlencode($endereco);
			$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'';		

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
		    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST"); // Delete precisa ser feito por POST
		    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
		    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
		    	$resposta = curl_exec( $curl );

		    	curl_close($curl);

			

			if ($data_fim != null ){ $NewDataFim = $data_fim ;}else{ $NewDataFim= $objects[0]->data_fim ;}
			
			

			//inserindo os novos
			$endereco = "insert {  
						 ?S timeline:endsAtDateTimee \"$NewDataFim\".


		   			} where{ <http://ligadonospoliticos.com.br/politico/$id_politico> pol:Term ?S. 
					 ?S timeline:beginsAtDateTime \"$data_inicio\".
					 ?S pol:Office \"$cargo\" .  }"; 
			


			$url = urlencode($endereco);
			$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'';		

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
		    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST"); 
		    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
		    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
		    	$resposta = curl_exec( $curl );

		    	curl_close($curl);


		}
		else{

			//é feita uma contagem de quantas comissões ele tem, para saber qual numero da proxima

			$format = 'text/integer';
			$endereco = "select ?term {  
						 <http://ligadonospoliticos.com.br/politico/$id_politico>  pol:Term ?term.
	
		   			}";

			$url = urlencode($endereco);
			$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'';
		
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
		    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
		    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
		    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
		    	$contador = curl_exec( $curl );
		    	curl_close($curl);



			//agora é feita a inserção

			$contador = $contador + 1 ;

			$format = 'application/sparql-results+xml';
		
			$endereco = "insert data {  
						 <http://ligadonospoliticos.com.br/politico/$id_politico> pol:Term _:blank .
						 _:blank biblio:number \"$contador\" .
					         _:blank pol:Office \"$cargo\" .
					         _:blank timeline:beginsAtDateTime \"$data_inicio\" .
				                 _:blank timeline:endsAtDateTime \"$data_fim\" .
				                 
		   			}";


			$url = urlencode($endereco);
			$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'';			

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
		    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST"); 
		    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
		    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
		    	$resposta = curl_exec( $curl );
		    	curl_close($curl);


		}
		
	}
		
	
	function missao($id_politico, $descricao, $tipo, $data_inicio, $data_fim, $documento){


		$format = 'application/sparql-results+json';
		
		$endereco = "select ?data_fim ?tipo ?documento {  
					 <http://ligadonospoliticos.com.br/politico/$id_politico>  polbr:mission ?mission.
					 ?mission timeline:beginsAtDateTime \"$data_inicio\".
					 ?mission dcterms:description \"$descricao\" . 
					 OPTIONAL { ?mission  timeline:endsAtDateTime ?data_fim}
					 OPTIONAL { ?mission  dcterms:type ?tipo}
					 OPTIONAL { ?mission  foaf:Document ?documento}
					

           			}";

		

		$url = urlencode($endereco);
		$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'+limit+1';

		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
	    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
	    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
	    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
	    	$resposta = curl_exec( $curl );
	    	curl_close($curl);


		$achou = false;
		$resposta_decoded = json_decode($resposta);

		
		//verifica se algum valor da consulta é diferente de null
		foreach($resposta_decoded->results->bindings as $reg){
			if($reg != null)
			{
				$achou = true;
			}
		}

		if($achou){


			$objects = array();
		        $results = json_decode($resposta);//descodifica o objeto json para um array
			//pega o valor dentro de dois array
			    foreach($results->results->bindings as $reg){
				   $obj = new stdClass();
				   foreach($reg as $field => $value){
				       $obj->$field = $value->value;
				   }
				   $objects[] = $obj;//guarda no array o objeto pretendido
			       }


			if (!empty($objects[0]->data_fim)){ $NewDataFim = $objects[0]->data_fim ;}else{ $NewDataFim = null;}
			if (!empty($objects[0]->tipo)){ $NewTipo = $objects[0]->tipo ;}else{ $NewTipo = null;}
			if (!empty($objects[0]->documento)){ $NewDocumento = $objects[0]->documento ;}else{ $NewDocumento = null;}

			
			$format = 'application/sparql-results+xml';

			//deletando dados para inserir dados novos
			$where = "WHERE { <http://ligadonospoliticos.com.br/politico/$id_politico> polbr:mission ?S .
					?S timeline:beginsAtDateTime \"$data_inicio\".
					?S dcterms:description \"$descricao\" . 
};";			


			$endereco = "DELETE { ?S timeline:endsAtDateTime \"$NewDataFim\" } $where
				     DELETE { ?S dcterms:type \"$NewTipo\" } $where
				     DELETE { ?S foaf:Document \"$NewDocumento\" } $where
				
				";
			
			$url = urlencode($endereco);
			$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'';		

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
		    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST"); // Delete precisa ser feito por POST
		    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
		    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
		    	$resposta = curl_exec( $curl );

		    	curl_close($curl);

			

			if ($data_fim != null ){ $NewDataFim = $data_fim ;}else{ $NewDataFim = $objects[0]->data_fim ;}
			if ($tipo != null ){ $NewTipo = $tipo ;}else{ $NewTipo = $objects[0]->tipo ;}
			if ($documento != null ){ $NewDocumento = $documento ;}else{ $NewDocumento = $objects[0]->documento ;}
			
			

			//inserindo os novos
			$endereco = "insert {  
						 ?S timeline:endsAtDateTimee \"$NewDataFim\".
						 ?S dcterms:type \"$NewTipo\" .
       						 ?S foaf:Document \"$NewDocumento\" .


		   			} where{ <http://ligadonospoliticos.com.br/politico/$id_politico> polbr:mission ?S. 
					 ?S timeline:beginsAtDateTime \"$data_inicio\".
					 ?S dcterms:description \"$descricao\" .  }"; 
			


			$url = urlencode($endereco);
			$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'';		

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
		    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST"); 
		    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
		    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
		    	$resposta = curl_exec( $curl );

		    	curl_close($curl);


		}
		else{

			//é feita uma contagem de quantas comissões ele tem, para saber qual numero da proxima

			$format = 'text/integer';
			$endereco = "select ?mission {  
						 <http://ligadonospoliticos.com.br/politico/$id_politico>  polbr:mission ?mission.
	
		   			}";

			$url = urlencode($endereco);
			$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'';
		
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
		    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
		    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
		    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
		    	$contador = curl_exec( $curl );
		    	curl_close($curl);



			//agora é feita a inserção

			$contador = $contador + 1 ;

			$format = 'application/sparql-results+xml';
		
			$endereco = "insert data {  
						 <http://ligadonospoliticos.com.br/politico/$id_politico> polbr:mission _:blank .
						 _:blank biblio:number \"$contador\" .
					         _:blank dcterms:description \"$descricao\" .
						 _:blank dcterms:type \"$tipo\" .
					         _:blank timeline:beginsAtDateTime \"$data_inicio\" .
				                 _:blank timeline:endsAtDateTime \"$data_fim\" .
						 _:blank foaf:Document \"$documento\" .
				                 
		   			}";


			$url = urlencode($endereco);
			$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'';			

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
		    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST"); 
		    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
		    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
		    	$resposta = curl_exec( $curl );
		    	curl_close($curl);

		}
		
	}

	
	
	function politico($nome_civil, $nome_parlamentar, $nome_pai, $nome_mae, $foto, $sexo, $cor, $data_nascimento, $estado_civil, $ocupacao, $grau_instrucao, $nacionalidade, $cidade_nascimento, $estado_nascimento, $cidade_eleitoral, $estado_eleitoral, $site, $email, $cargo, $cargo_uf, $partido, $situacao){

		//usando a função existePoli para descobrir o ID.
		$id_politico = existePoli($nome_civil, $data_nascimento, $cidade_nascimento);

		$politico = "<http://ligadonospoliticos.com.br/politico/$id_politico>":
		
		$format = 'application/sparql-results+json';
		
		$endereco = "select ?nome_parlamentar ?nome_pai ?nome_mae ?foto ?sexo ?cor ?estado_civil ?ocupacao ?grau_instrucao ?nacionalidade ?estado_nascimento ?cidade-estado_eleitoral ?site ?email ?cargo ?cargo_uf ?partido ?situacao {  

					 OPTIONAL { $politico polbr:governmentalName ?nome_parlamentar }
					 OPTIONAL { $politico bio:father ?nome_pai }
					 OPTIONAL { $politico bio:mother ?nome_mae }
					 OPTIONAL { $politico foaf:img ?foto }
					 OPTIONAL { $politico foaf:gender ?sexo }
					 OPTIONAL { $politico person:complexion ?cor }
					 OPTIONAL { $politico polbr:maritalStatus ?estado_civil }
					 OPTIONAL { $politico person:occupation ?ocupacao }
					 OPTIONAL { $politico person:occupation ?ocupacao_uri }
					 OPTIONAL { $politico dcterms:educationLevel ?grau_instrucao }
					 OPTIONAL { $politico dbpprop:nationality ?nacionalidade }
					 OPTIONAL { $politico polbr:state-of-birth ?estado_nascimento }
                                         OPTIONAL { $politico polbr:state-of-birth ?estado_nascimento_uri }
					 OPTIONAL { $politico polbr:place-of-vote ?cidade-estado_eleitoral }
					 OPTIONAL { $politico foaf:homepage ?site }
					 OPTIONAL { $politico biblio:Email ?email }
					 OPTIONAL { $politico pol:Office ?cargo }
					 OPTIONAL { $politico polbr:officeState ?cargo_uf }
					 OPTIONAL { $politico pol:party ?partido }
					 OPTIONAL { $politico pol:party ?partido_uri }
					 OPTIONAL { $politico polbr:situation ?situacao }
					 FILTER ( ?ocupacao != ?ocupacao_uri )
					 FILTER regex(?ocupacao_uri, \"http\")
					 FILTER ( ?estado_nascimento != ?estado_nascimento_uri )
					 FILTER regex(?estado_nascimento_uri, \"http\")
					 FILTER ( ?partido != ?partido_uri )
					 FILTER regex(?partido_uri, \"http\")


					 OPTIONAL { ?term  timeline:endsAtDateTime ?data_fim}
					

           			}";

		

		$url = urlencode($endereco);
		$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'+limit+1';

		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
	    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
	    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
	    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
	    	$resposta = curl_exec( $curl );
	    	curl_close($curl);



		if($id_politivo != 0){


			$objects = array();
		        $results = json_decode($resposta);//descodifica o objeto json para um array
			//pega o valor dentro de dois array
			    foreach($results->results->bindings as $reg){
				   $obj = new stdClass();
				   foreach($reg as $field => $value){
				       $obj->$field = $value->value;
				   }
				   $objects[] = $obj;//guarda no array o objeto pretendido
			       }


			if (!empty($objects[0]->nome_parlamentar)){ $NewNomeParlamentar = $objects[0]->nome_parlamentar ;}else{ $NewNomeParlamentar = null;}
			if (!empty($objects[0]->nome_pai)){ $NewNomePai = $objects[0]->nome_pai ;}else{ $NewNomePai = null;}
			if (!empty($objects[0]->nome_mae )){ $NewNomeMae = $objects[0]->nome_mae ;}else{ $NewNomeMae = null;}
			if (!empty($objects[0]->foto)){ $NewFoto = $objects[0]->foto ;}else{ $NewFoto = null;}
			if (!empty($objects[0]->sexo)){ $NewSexo = $objects[0]->sexo ;}else{ $NewSexo = null;}
			if (!empty($objects[0]->cor)){ $NewCor = $objects[0]->cor ;}else{ $NewCor = null;}
			if (!empty($objects[0]->estado_civil)){ $NewEstadoCivil = $objects[0]->estado_civil ;}else{ $NewEstadoCivil = null;}
			if (!empty($objects[0]->ocupacao)){ $NewOcupacao = $objects[0]->ocupacao ;}else{ $NewOcupacao = null;}
			if (!empty($objects[0]->grau_instrucao)){ $NewGrauInstrucao = $objects[0]->grau_instrucao ;}else{ $NewGrauInstrucao = null;}
			if (!empty($objects[0]->nacionalidade)){ $NewNacionalidade = $objects[0]->nacionalidade ;}else{ $NewNacionalidade = null;}
			if (!empty($objects[0]->estado_nascimento)){ $NewEstadoNascimento = $objects[0]->estado_nascimento ;}else{ $NewEstadoNascimento = null;}
			if (!empty($objects[0]->cidade-estado_eleitoral)){ $NewCidadeEstadoEleitoral = $objects[0]->cidade-estado_eleitoral ;}else{ $NewCidadeEstadoEleitoral = null;}
			if (!empty($objects[0]->site)){ $NewSite = $objects[0]->site ;}else{ $NewSite = null;}
			if (!empty($objects[0]->email)){ $NewEmail = $objects[0]->email ;}else{ $NewEmail = null;}
			if (!empty($objects[0]->cargo)){ $NewCargo = $objects[0]->cargo ;}else{ $NewCargo = null;}
			if (!empty($objects[0]->cargo_uf)){ $NewCargoUf = $objects[0]->cargo_uf ;}else{ $NewCargoUf = null;}
			if (!empty($objects[0]->partido)){ $NewPartido = $objects[0]->partido ;}else{ $NewPartido = null;}
			if (!empty($objects[0]->situacao)){ $NewSituacao = $objects[0]->situacao ;}else{ $NewSituacao = null;}

			
			$format = 'application/sparql-results+xml';

			//deletando dados para inserir dados novos
		

			$endereco = "
					DELETE DATA{ $politico polbr:governmentalName \"$NewNomeParlamentar\" };
					DELETE DATA{ $politico bio:father \"$NewNomePai\"};
					DELETE DATA{ $politico bio:mother \"$NewNomeMae\" };
					DELETE DATA{ $politico foaf:img \"$NewFoto\" };
					DELETE DATA{ $politico foaf:gender \"$NewSexo\" };
					DELETE DATA{ $politico person:complexion \"$NewCor\" };
					DELETE DATA{ $politico polbr:maritalStatus \"$NewEstadoCivil\" };
					DELETE DATA{ $politico person:occupation \"$NewOcupacao\" };
					DELETE DATA{ $politico dcterms:educationLevel \"$NewGrauInstrucao\" };
					DELETE DATA{ $politico dbpprop:nationality \"$NewNacionalidade\" };
					DELETE DATA{ $politico polbr:state-of-birth \"$NewEstadoNascimento\" };
					DELETE DATA{ $politico polbr:place-of-vote \"$NewCidadeEstadoEleitoral\" };
					DELETE DATA{ $politico foaf:homepage \"$NewSite\" };
					DELETE DATA{ $politico biblio:Email \"$NewEmail\" };
					DELETE DATA{ $politico pol:Office \"$NewCargo\" };
					DELETE DATA{ $politico polbr:officeState \"$NewCargoUf\" };
					DELETE DATA{ $politico pol:party \"$NewPartido\" };
					DELETE DATA{ $politico polbr:situation \"$NewSituacao\" };
				
				";
			
			$url = urlencode($endereco);
			$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'';		

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
		    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST"); // Delete precisa ser feito por POST
		    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
		    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
		    	$resposta = curl_exec( $curl );

		    	curl_close($curl);

			

			if ($nome_parlamentar != null ){ $NewNomeParlamentar = $nome_parlamentar ;}else{ $NewNomeParlamentar = $objects[0]->nome_palarmentar ;}
			if ($nome_pai != null ){ $NewNomePai = $nome_pai ;}else{ $NewNomePai = $objects[0]->nome_pai ;}
			if ($nome_mae != null ){ $NewNomeMae = $nome_mae ;}else{ $NewNomeMae = $objects[0]->nome_mae ;}
			if ($foto != null ){ $NewFoto = $foto ;}else{ $NewFoto = $objects[0]->foto ;}
			if ($sexo != null ){ $NewSexo = $sexo ;}else{ $NewSexo = $objects[0]->sexo ;}
			if ($cor != null ){ $NewCor = $cor ;}else{ $NewCor = $objects[0]->cor ;}
			if ($estado_civil != null ){ $NewEstadoCivil = $estado_civil ;}else{ $NewEstadoCivil = $objects[0]->estado_civil ;}
			if ($ocupacao != null ){ $NewOcupacao = $ocupacao ;}else{ $NewOcupacao = $objects[0]->ocupacao ;}
			if ($grau_instrucao != null ){ $NewGrauInstrucao = $grau_instrucao ;}else{ $NewGrauInstrucao = $objects[0]->grau_instrucao ;}
			if ($nacionalidade != null ){ $NewNacionalidade = $nacionalidade ;}else{ $NewNacionalidade = $objects[0]->nacionalidade ;}
			if ($estado_nascimento != null ){ $NewEstadoNascimento = $estado_nascimento ;}else{ $NewEstadoNascimento = $objects[0]->estado_nascimento ;}
			if ($cidade_eleitoral != null && $estado_eleitoral != null){ $NewCidadeEstadoEleitoral = $cidade_eleitoral + " - " + $estado_eleitoral ;}else{ $NewCidadeEstadoEleitoral = $objects[0]->cidade-estado_eleitoral ;}
			if ($site != null ){ $NewSite = $site ;}else{ $NewSite = $objects[0]->site ;}
			if ($email != null ){ $NewEmail = $email ;}else{ $NewEmail = $objects[0]->email ;}
			if ($cargo != null ){ $NewCargo = $cargo ;}else{ $NewCargo = $objects[0]->cargo ;}
			if ($cargo_uf != null ){ $NewCargoUf = $cargo_uf ;}else{ $NewCargoUf = $objects[0]->cargo_uf ;}
			if ($partido != null ){ $NewPartido = $partido ;}else{ $NewPartido = $objects[0]->partido ;}
			if ($situacao != null ){ $NewSituacao = $situacao ;}else{ $NewSituacao = $objects[0]->situacao ;}

			
			

			//inserindo os novos
			$endereco = "insert data{  
					$politico polbr:governmentalName \"$NewNomeParlamentar\" .
					$politico bio:father \"$NewNomePai\" .
					$politico bio:mother \"$NewNomeMae\" .
					$politico foaf:img \"$NewFoto\" .
					$politico foaf:gender \"$NewSexo\" .
					$politico person:complexion \"$NewCor\" .
					$politico polbr:maritalStatus \"$NewEstadoCivil\" .
					$politico person:occupation \"$NewOcupacao\" .
					$politico dcterms:educationLevel \"$NewGrauInstrucao\" .
					$politico dbpprop:nationality \"$NewNacionalidade\" .
					$politico polbr:state-of-birth \"$NewEstadoNascimento\" .
					$politico polbr:place-of-vote \"$NewCidadeEstadoEleitoral\" .
					$politico foaf:homepage \"$NewSite\" .
					$politico biblio:Email \"$NewEmail\" .
					$politico pol:Office \"$NewCargo\" .
					$politico polbr:officeState \"$NewCargoUf\" .
					$politico pol:party \"$NewPartido\" .
					$politico polbr:situation \"$NewSituacao\" .


		   			}"; 
			


			$url = urlencode($endereco);
			$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'';		

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
		    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST"); 
		    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
		    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
		    	$resposta = curl_exec( $curl );

		    	curl_close($curl);


		}
		else{


		     	//pegar o maior id e somar mais um

			$format = 'application/sparql-results+xml';
			$endereco = "select ?id { ?id ?p ?o .
						FILTER regex(?id, \"<http://ligadonospoliticos.com.br/politico/\")
		   			}ORDER BY ASC(?id)";

			$url = urlencode($endereco);
			$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'+limit+1';
		
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
		    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
		    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
		    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
		    	$resposta = curl_exec( $curl );
		    	curl_close($curl);

			$resposta =  str_replace("http://ligadonospoliticos.com.br/politico/","", $resposta)

			//agora é feita a inserção
			
			$novopolitico = "<http://ligadonospoliticos.com.br/politico/$resposta>";
	
			$format = 'application/sparql-results+xml';

			$descricaoRDF =  "Descrição RDF de $nome_civil" ;
			$siteRDF = "http://ligadonospoliticos.com.br/content/foaf.rdf";
	 		$dataatual = date("Ymd");
			$siteprojeto = "http://ligadonospoliticos.com.br";
			$sitecomId = "http://ligadonospoliticos.com.br/resource/$resposta/html ";


			$cidadeEstadoEleitoral = "$cidade_eleitoral - $estado_eleitoral";

			$endereco = "insert data {  
						 $novopolitico a rdf:Description .
						 $novopolitico rdfs:label \"$descricaoRDF\" .
					         $novopolitico dc:creator \"$siteRDF\" .
						 $novopolitico dc:publisher \"$siteRDF\" .
						 $novopolitico dc:created \"$dataatual\" .
						 $novopolitico dc:rights \"$siteprojeto\" .
						 $novopolitico dcterms:language \"pt-br\" .
						 $novopolitico foaf:primaryTopic \"$sitecomID\" .
						 $novopolitico foaf:name \"$nome_civil\".
						 $novopolitico foaf:birthday \"$data_nascimento\" .
						 $novopolitico being:place-of-birth \"$cidade_nascimento\" .

						 $novopolitico polbr:governmentalName \"$nome_parlamentar\" .
						 $novopolitico bio:father \"$nome_pai\" .
						 $novopolitico bio:mother \"$nome_mae\" .
						 $novopolitico foaf:img \"$foto\" .
						 $novopolitico foaf:gender \"$sexo\" .
						 $novopolitico person:complexion \"$cor\" .
						 $novopolitico polbr:maritalStatus \"$estado_civil\" .
						 $novopolitico person:occupation \"$ocupacao\" .
						 $novopolitico dcterms:educationLevel \"$grau_instrucao\" .
						 $novopolitico dbpprop:nationality \"$nacionalidade\" .
						 $novopolitico polbr:state-of-birth \"$estado_nascimento\" .
						 $novopolitico polbr:place-of-vote \"$cidadeEstadoEleitoral\" .
						 $novopolitico foaf:homepage \"$site\" .
						 $novopolitico biblio:Email \"$email\" .
						 $novopolitico pol:Office \"$cargo\" .
						 $novopolitico polbr:officeState \"$cargo_uf\" .
						 $novopolitico pol:party \"$partido\" .
						 $novopolitico polbr:situation \"$situacao\" .

				                 
		   			}";


			$url = urlencode($endereco);
			$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'';			

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
		    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST"); 
		    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
		    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
		    	$resposta = curl_exec( $curl );
		    	curl_close($curl);

		}

		}

	
	}


	function pronunciamento($id_politico, $tipo, $data, $casa, $partido, $uf, $resumo){


		$format = 'application/sparql-results+json';
		
		$endereco = "select ?tipo ?casa ?uf {  
					 <http://ligadonospoliticos.com.br/politico/$id_politico>  biblio:Speech ?Speech.
					 ?Speech timeline:atDate \"$data\".
					 ?Speech biblio:abstract \"$resumo\" . 
					 ?Speech pol:party \"$partido\" .
					 OPTIONAL { ?Speech dcterms:type ?tipo}
					 OPTIONAL { ?Speech po:Place ?casa}
					 OPTIONAL { ?Speech geospecies:State ?uf}
					

           			}";

		

		$url = urlencode($endereco);
		$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'+limit+1';

		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
	    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
	    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
	    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
	    	$resposta = curl_exec( $curl );
	    	curl_close($curl);


		$achou = false;
		$resposta_decoded = json_decode($resposta);

		
		//verifica se algum valor da consulta é diferente de null
		foreach($resposta_decoded->results->bindings as $reg){
			if($reg != null)
			{
				$achou = true;
			}
		}

		if($achou){


			$objects = array();
		        $results = json_decode($resposta);//descodifica o objeto json para um array
			//pega o valor dentro de dois array
			    foreach($results->results->bindings as $reg){
				   $obj = new stdClass();
				   foreach($reg as $field => $value){
				       $obj->$field = $value->value;
				   }
				   $objects[] = $obj;//guarda no array o objeto pretendido
			       }

			if (!empty($objects[0]->tipo)){ $NewTipo = $objects[0]->tipo ;}else{ $NewTipo = null;}
			if (!empty($objects[0]->casa)){ $NewCasa = $objects[0]->casa ;}else{ $NewCasa = null;}
			if (!empty($objects[0]->uf)){ $NewUf = $objects[0]->uf ;}else{ $NewUf = null;}

			
			$format = 'application/sparql-results+xml';

			//deletando dados para inserir dados novos
			$where = "WHERE { <http://ligadonospoliticos.com.br/politico/$id_politico> biblio:Speech ?S .
					?S timeline:atDate \"$data\".
					?S biblio:abstract \"$resumo\" . 
					?S pol:party \"$partido\" .
};";			

			$endereco = "DELETE { ?S dcterms:type   \"$NewTipo\" } $where
				     DELETE { ?S po:Place \"$NewCasa\" } $where
				     DELETE { ?S geospecies:State \"$NewUf\" } $where
				
				";
			
			$url = urlencode($endereco);
			$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'';		

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
		    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST"); // Delete precisa ser feito por POST
		    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
		    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
		    	$resposta = curl_exec( $curl );

		    	curl_close($curl);

			
			if ($tipo != null ){ $NewTipo = $tipo ;}else{ $NewTipo = $objects[0]->tipo ;}
			if ($casa != null ){ $NewCasa = $casa ;}else{ $NewCasa = $objects[0]->casa ;}
			if ($uf != null ){ $NewUf = $uf ;}else{ $NewUf = $objects[0]->uf ;}
			
			

			//inserindo os novos
			$endereco = "insert {  
						 ?S dcterms:type \"$NewTipo\".
						 ?S po:Place \"$NewCasa\" .
       						 ?S geospecies:State \"$NewUf\" .


		   			} where{ <http://ligadonospoliticos.com.br/politico/$id_politico> biblio:Speech ?S. 
					 ?S timeline:atDate \"$data\".
					 ?S biblio:abstract \"$resumo\" . 
					 ?S pol:party \"$partido\" .  }"; 
			


			$url = urlencode($endereco);
			$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'';		

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
		    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST"); 
		    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
		    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
		    	$resposta = curl_exec( $curl );

		    	curl_close($curl);


		}
		else{

			//é feita uma contagem de quantas pronunciamentos ele tem, para saber qual numero da proxima

			$format = 'text/integer';
			$endereco = "select ?Speech {  
						 <http://ligadonospoliticos.com.br/politico/$id_politico>  biblio:Speech ?Speech.
	
		   			}";

			$url = urlencode($endereco);
			$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'';
		
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
		    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
		    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
		    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
		    	$contador = curl_exec( $curl );
		    	curl_close($curl);



			//agora é feita a inserção

			$contador = $contador + 1 ;

			$format = 'application/sparql-results+xml';
		
			$endereco = "insert data {  
						 <http://ligadonospoliticos.com.br/politico/$id_politico> biblio:Speech _:blank .
						 _:blank biblio:number \"$contador\" .
					         _:blank timeline:atDate \"$data\" .
						 _:blank dcterms:type \"$tipo\" .
					         _:blank po:Place \"$casa\" .
				                 _:blank pol:party \"$partido\" .
						 _:blank geospecies:State \"$uf\".
						 _:blank biblio:abstract \"$resumo\".
				                 
		   			}";


			$url = urlencode($endereco);
			$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'';			

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
		    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST"); 
		    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
		    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
		    	$resposta = curl_exec( $curl );
		    	curl_close($curl);

		}

	}
	
	
	function proposicao($id_politico, $titulo, $data, $casa, $tipo, $descricao_tipo, $ementa){

		$format = 'application/sparql-results+json';
		
		$endereco = "select ?casa ?tipoWithDescricao ?ementa {  
					 <http://ligadonospoliticos.com.br/politico/$id_politico>  polbr:proposition ?Proposition.
					 ?Proposition dc:title \"$titulo\".
					 ?Proposition timeline:atDate \"$data\" . 
					 OPTIONAL { ?Proposition po:Place ?casa}
					 OPTIONAL { ?Proposition dcterms:type ?tipoWithDescricao}
					 OPTIONAL { ?Proposition dcterms:description ?ementa}

           			}";

		

		$url = urlencode($endereco);
		$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'+limit+1';

		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
	    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
	    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
	    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
	    	$resposta = curl_exec( $curl );
	    	curl_close($curl);


		$achou = false;
		$resposta_decoded = json_decode($resposta);

		
		//verifica se algum valor da consulta é diferente de null
		foreach($resposta_decoded->results->bindings as $reg){
			if($reg != null)
			{
				$achou = true;
			}
		}

		if($achou){


			$objects = array();
		        $results = json_decode($resposta);//descodifica o objeto json para um array
			//pega o valor dentro de dois array
			    foreach($results->results->bindings as $reg){
				   $obj = new stdClass();
				   foreach($reg as $field => $value){
				       $obj->$field = $value->value;
				   }
				   $objects[] = $obj;//guarda no array o objeto pretendido
			       }


			if (!empty($objects[0]->casa)){ $NewCasa = $objects[0]->casa ;}else{ $NewCasa = null;}
			if (!empty($objects[0]->tipoWithDescricao)){ $NewTipoDescricao = $objects[0]->tipoWithDescricao ;}else{ $NewTipoDescricao = null;}
			if (!empty($objects[0]->ementa)){ $NewEmenta = $objects[0]->ementa ;}else{ $NewEmenta = null;}

			
			$format = 'application/sparql-results+xml';

			//deletando dados para inserir dados novos
			$where = "WHERE { <http://ligadonospoliticos.com.br/politico/$id_politico> polbr:proposition ?S .
					?S dc:title \"$titulo\".
					?S timeline:atDate \"$data\" . 
	
};";			

			$endereco = "DELETE { ?S dcterms:type \"$NewTipoDescricao\" } $where
				     DELETE { ?S po:Place \"$NewCasa\" } $where
				     DELETE { ?S dcterms:description \"$NewEmenta\" } $where
				
				";
			
			$url = urlencode($endereco);
			$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'';		

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
		    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST"); // Delete precisa ser feito por POST
		    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
		    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
		    	$resposta = curl_exec( $curl );

		    	curl_close($curl);

			
			if ($tipo != null && $descricao_tipo != null ){ $NewTipoDescriccao = "$tipo - $descricao_tipo" ;}else{ $NewTipoDescricao = $objects[0]->tipoWithDescricao ;}
			if ($casa != null ){ $NewCasa = $casa ;}else{ $NewCasa = $objects[0]->casa ;}
			if ($ementa != null ){ $NewEmenta = $ementa ;}else{ $NewEmenta = $objects[0]->ementa ;}
			
			

			//inserindo os novos
			$endereco = "insert {  
						 ?S dcterms:type \"$NewTipoDescricao\"
						 ?S po:Place \"$NewCasa\" .
       						 ?S dcterms:description \"$NewEmenta\" .


		   			} where{ <http://ligadonospoliticos.com.br/politico/$id_politico> polbr:proposition ?S .
					?S dc:title \"$titulo\".
					?S timeline:atDate \"$data\" .   }"; 
			


			$url = urlencode($endereco);
			$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'';		

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
		    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST"); 
		    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
		    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
		    	$resposta = curl_exec( $curl );

		    	curl_close($curl);


		}
		else{

			//é feita uma contagem de quantas pronunciamentos ele tem, para saber qual numero da proxima

			$format = 'text/integer';
			$endereco = "select ?Proposition {  
						 <http://ligadonospoliticos.com.br/politico/$id_politico>  polbr:proposition ?Proposition.
	
		   			}";

			$url = urlencode($endereco);
			$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'';
		
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
		    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
		    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
		    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
		    	$contador = curl_exec( $curl );
		    	curl_close($curl);



			//agora é feita a inserção

			$contador = $contador + 1 ;

			$format = 'application/sparql-results+xml';
		 	$tipodesc =  "$tipo - $descricao_tipo";
			$endereco = "insert data {  
						 <http://ligadonospoliticos.com.br/politico/$id_politico> polbr:proposition _:blank .
						 _:blank biblio:number \"$contador\" .
					         _:blank dc:title \"$titulo\" .
						 _:blank timeline:atDate \"$data\" .
					         _:blank po:Place \"$casa\" .
				                 _:blank dcterms:type \"$tipodesc\" .
						 _:blank dcterms:description \"$ementa\".
				                 
		   			}";


			$url = urlencode($endereco);
			$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'';			

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
		    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST"); 
		    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
		    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
		    	$resposta = curl_exec( $curl );
		    	curl_close($curl);

		}
		
		
	}
	

	function return_id_fonte($url){


		$format = 'application/sparql-results+json';
		
		$endereco = "select ?id  {  
					 <Fontes> opmx:source ?Source.
					 ?Source opmx:id ?id . 
					 ?Source opmx:value \"$url\". 

           			}";

		

		$url = urlencode($endereco);
		$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'+limit+1';

		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
	    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
	    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
	    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
	    	$resposta = curl_exec( $curl );
	    	curl_close($curl);

		$objects = array();
		        $results = json_decode($resposta);//descodifica o objeto json para um array
			//pega o valor dentro de dois array
			    foreach($results->results->bindings as $reg){
				   $obj = new stdClass();
				   foreach($reg as $field => $value){
				       $obj->$field = $value->value;
				   }
				   $objects[] = $obj;//guarda no array o objeto pretendido
			       }


		return $objects[0]->id ;



	}


	function fonte_politico($id_politico, $id_fonte){

			
		$format = 'application/sparql-results+json';
		
		$endereco = "select ?time  {  
					 <Fontes> opmx:source ?Source.
					 ?Source opmx:id \"$id_fonte\".
					 timeline:atDate
					 OPTIONAL { ?Source polbr:identifier ?Politico}
					 OPTIONAL { ?Politico biblio:number \"$id_politico\"}
					 OPTIONAL { ?Politico timeline:atDate ?time}

           			}";

		

		$url = urlencode($endereco);
		$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'+limit+1';

		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
	    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
	    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
	    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
	    	$resposta = curl_exec( $curl );
	    	curl_close($curl);


		$achou = false;
		$resposta_decoded = json_decode($resposta);

		
		//verifica se algum valor da consulta é diferente de null
		foreach($resposta_decoded->results->bindings as $reg){
			if($reg != null)
			{
				$achou = true;
			}
		}

		if($achou){


			$objects = array();
		        $results = json_decode($resposta);//descodifica o objeto json para um array
			//pega o valor dentro de dois array
			    foreach($results->results->bindings as $reg){
				   $obj = new stdClass();
				   foreach($reg as $field => $value){
				       $obj->$field = $value->value;
				   }
				   $objects[] = $obj;//guarda no array o objeto pretendido
			       }


			if (!empty($objects[0]->time)){ $NewTime = $objects[0]->time ;}else{ $NewTime = null;}

			
			$format = 'application/sparql-results+xml';


			//deletando dados para inserir dados novos
			$where = "WHERE { <Fontes> opmx:source ?S .
					?S opmx:id \"$id_fonte\". 
					?S polbr:identifier ?Politico .
					?Politico biblio:number \"$id_politico\".
	
};";			

			$endereco = "DELETE { ?Politico timeline:atDate \"$NewTime\" } $where

				
				";
			
			$url = urlencode($endereco);
			$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'';		

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
		    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST"); // Delete precisa ser feito por POST
		    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
		    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
		    	$resposta = curl_exec( $curl );

		    	curl_close($curl);


			$NewTime = date("d/m/Y");
			
			

			//inserindo os novos
			$endereco = "insert {  
						 ?Politico timeline:atDate \"$NewTime\"


		   			} where{ <Fontes> opmx:source ?S .
					?S opmx:id \"$id_fonte\". 
					?S polbr:identifier ?Politico .
					?Politico biblio:number \"$id_politico\". }"; 
			


			$url = urlencode($endereco);
			$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'';		

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
		    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST"); 
		    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
		    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
		    	$resposta = curl_exec( $curl );

		    	curl_close($curl);


		}
		else{



			$NewTime = date("d/m/Y");

			$format = 'application/sparql-results+xml';

			$endereco = "insert {  
						 ?S polbr:identifier _:Politico .
						 _:Politico biblio:number \"$id_politico\".
						 _:Politico timeline:atDate \"$NewTime\"


		   			} where{ <Fontes> opmx:source ?S .
						?S opmx:id \"$id_fonte\". 

					 }";


			$url = urlencode($endereco);
			$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'';			

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
		    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST"); 
		    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
		    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
		    	$resposta = curl_exec( $curl );
		    	curl_close($curl);

		}

	}



	function fonte($nome, $uri, $url){

		$format = 'application/sparql-results+json';
		
		$endereco = "select ?nome ?uri  {  
					 <Fontes> opmx:source ?Source.
					 ?Source opmx:value \"$url\". 
					 OPTIONAL { ?Source opmx:label ?nome}
					 OPTIONAL { ?Source opmx:uri ?uri}

           			}";

		

		$url = urlencode($endereco);
		$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'+limit+1';

		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
	    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
	    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
	    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
	    	$resposta = curl_exec( $curl );
	    	curl_close($curl);


		$achou = false;
		$resposta_decoded = json_decode($resposta);

		
		//verifica se algum valor da consulta é diferente de null
		foreach($resposta_decoded->results->bindings as $reg){
			if($reg != null)
			{
				$achou = true;
			}
		}

		if($achou){


			$objects = array();
		        $results = json_decode($resposta);//descodifica o objeto json para um array
			//pega o valor dentro de dois array
			    foreach($results->results->bindings as $reg){
				   $obj = new stdClass();
				   foreach($reg as $field => $value){
				       $obj->$field = $value->value;
				   }
				   $objects[] = $obj;//guarda no array o objeto pretendido
			       }


			if (!empty($objects[0]->nome)){ $NewNome = $objects[0]->nome ;}else{ $NewNome = null;}
			if (!empty($objects[0]->uri)){ $NewUri = $objects[0]->uri ;}else{ $NewUri = null;}


			
			$format = 'application/sparql-results+xml';

			//deletando dados para inserir dados novos
			$where = "WHERE { <Fontes> opmx:source ?S .
					?S opmx:value \"$url\". 
	
};";			

			$endereco = "DELETE { ?S opmx:label \"$NewNome\" } $where
				     DELETE { ?S opmx:uri \"$NewUri\" } $where
				
				";
			
			$url = urlencode($endereco);
			$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'';		

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
		    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST"); // Delete precisa ser feito por POST
		    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
		    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
		    	$resposta = curl_exec( $curl );

		    	curl_close($curl);


			if ($nome != null ){ $NewNome = $nome ;}else{ $NewNome = $objects[0]->nome ;}
			if ($uri != null ){ $NewUri = $uri ;}else{ $NewUri = $objects[0]->uri ;}
			
			

			//inserindo os novos
			$endereco = "insert {  
						 ?S opmx:label \"$NewNome\"
						 ?S opmx:uri \"$NewUri\" .


		   			} where{ <Fontes> opmx:source ?S .
					?S opmx:value \"$url\". }"; 
			


			$url = urlencode($endereco);
			$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'';		

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
		    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST"); 
		    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
		    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
		    	$resposta = curl_exec( $curl );

		    	curl_close($curl);


		}
		else{

			//é feita uma contagem de quantas pronunciamentos ele tem, para saber qual numero da proxima

			$format = 'text/integer';
			$endereco = "select ?Source {  
						 <Fontes>  opmx:source ?Source.
	
		   			}";

			$url = urlencode($endereco);
			$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'';
		
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
		    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
		    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
		    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
		    	$contador = curl_exec( $curl );
		    	curl_close($curl);



			//agora é feita a inserção

			$contador = $contador + 1 ;

			$format = 'application/sparql-results+xml';
			$endereco = "insert data {  
						 <Fontes> opmx:source _:blank .
						 _:blank opmx:id \"$contador\" .
					         _:blank opmx:label \"$nome\" .
						 _:blank opmx:uri \"$uri\" .
					         _:blank opmx:value \"$url\" .
				                 
		   			}";


			$url = urlencode($endereco);
			$sparqlURL = 'http://localhost:10035/repositories/ligados?query='.$url.'';			

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_USERPWD, $GLOBALS['login']);	
		    	curl_setopt($curl, CURLOPT_URL, $sparqlURL);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST"); 
		    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Recebe o output da url como uma string
		    	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Accept: '.$format ));
		    	$resposta = curl_exec( $curl );
		    	curl_close($curl);

		}
		
		
	}

?>



