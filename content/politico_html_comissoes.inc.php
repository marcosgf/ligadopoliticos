<?php					
	echo "<div class='divisao'>Comissões</div>";	
	$conta_comissao = 1;
	echo "<table border=1 class='tabelas'>
	<tr>
		<td>N<sup>o</sup></td>
		<td>Descrição</td>
		<td>Data Início</td>	
		<td>Data Fim</td>
		<td>Participação</td>					
	</tr>";
	while($row = mysql_fetch_array($sql5)){
		$data_inicio = date('d/m/Y', strtotime($row['data_inicio']));
		$data_fim = date('d/m/Y', strtotime($row['data_fim']));
		if ($data_fim == '01/01/1970')
			$data_fim = '-';
	
		echo "
		<tr>
			<td>$conta_comissao</td>
			<td>".$row['descricao']."</td>
			<td>".$data_inicio."</td>
			<td>".$data_fim."</td>
			<td>".$row['participacao']."</td>
		</tr>";
		$conta_comissao++;
	}
	echo "</table>";
	echo "<br />";
?>