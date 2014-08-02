<?php

// FLYBYPHPJS FRAMEWORK


// DATABASE  - MYSQLI Library /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

// Custom global variables declaration
$FL_DEFAULT_CONNECTION = null;
$FL_DEBUG_MODE = true;

FUNCTION objectToArray($o) {
	if (is_object($o)) {
		// Gets the properties of the given object
		// with get_object_vars function
		$o = get_object_vars($o);
	}

	if (is_array($o)) {
		/*
		* Return array converted to object
		* Using __FUNCTION__ (Magic constant)
		* for recursive call
		*/
		return array_map(__FUNCTION__, $o);
	} else {
		// Return array
		return $o;
	}
}

FUNCTION dbEscape(&$value) {
	if (is_string($value) == true) {
		$value = mysql_real_escape_string($value);
	}
}

FUNCTION dbEscapeF($value) {
	return(mysql_real_escape_string($value));
}


FUNCTION dbSetConn($connection) {
	global $FL_DEFAULT_CONNECTION;
	$FL_DEFAULT_CONNECTION = $connection;
}


FUNCTION dbConnect($hostname, $user, $pass, $database = '', $set_this_as_default_connection = true, $return_not_die = false, $return_array = true) {
	
	// Creazione della connessione al database	
	$connection = mysqli_connect($hostname, $user, $pass);
	$msg = 'Errore di connessione al database (# ' . mysqli_connect_errno() . ').<br>Descrizione errore:<br>' . mysqli_connect_error() . "\n";
	
	if (!$connection) {
		
		if ($return_not_die == true) {
			
			if ($return_array == true) {
				$result['RESULT'] = false;
				$result['LINK'] = false;
				$result['MESSAGE'] = $msg;
			} else {
				$result = false;
			}
			
			return($result);
			
		} else {
		
			die($msg);
			
		}
		
	} else {
		
		if ($set_this_as_default_connection == true) { dbSetConn($connection); };
		
		if ($database != '' AND is_null($database) != true) { mysqli_select_db($connection, $database); };
		
		if ($return_array == true) {
			$result['RESULT'] = true;
			$result['LINK'] = $connection;
			$result['MESSAGE'] = 'Connessione al database eseguita con successo.<br>' . mysqli_get_host_info($connection) . "\n";
		} else {
			$result = $connection;
		}
		
		return($result);

	}
	
}	// endfunction dbConnect

FUNCTION dbConnectEnd($connection) {
	mysqli_close($connection);
}

FUNCTION dbSelectDbs($database) {
	global $FL_DEFAULT_CONNECTION;
	mysqli_select_db($FL_DEFAULT_CONNECTION, $database);
}

FUNCTION dbInsert($table, $vettore, $print_info = false, $descr_info = '', $FL_DEBUG_MODE = null) {

	global $FL_DEFAULT_CONNECTION;
	
	if (is_null($FL_DEBUG_MODE) == true) { 
		global $FL_DEBUG_MODE;
		$FL_DEBUG_MODE = $FL_DEBUG_MODE;
	}
	
	array_walk($vettore, "dbEscape");	// Applica gli slash per i caratteri speciali
	
	// *** QUI BISOGNA INSERIRE UN SANITY CHECK CHE SCANSISCE L'ARRAY PER VERIFICARE CHE NON SIA UN ARRAY DI ARRAY ***
	
	$query  = "INSERT INTO " . $table;
	$query .= " (" . implode(", ", array_keys($vettore)) . ")";		// Implode dei nomi dei campi
	$query .= " VALUES ('" . implode("', '", $vettore) . "') ";		// Implode dei valori dei campi
		
	return(dbQuery($query, $connection = $FL_DEFAULT_CONNECTION, $print_info, $descr_info, $FL_DEBUG_MODE));
	
}

FUNCTION dbUpdate($table, $vettore, $where = '', $print_info = false, $descr_info = '', $FL_DEBUG_MODE = null) {
	
	global $FL_DEFAULT_CONNECTION;

	if (is_null($FL_DEBUG_MODE) == true) { 
		global $FL_DEBUG_MODE;
		$FL_DEBUG_MODE = $FL_DEBUG_MODE;
	}
	
	if (is_array($vettore) == false) {
		
		$view['_QUERY_'][] = false;
		$view['_DATI_'][] = array('ESITO'=>"<p class='warning'>Errore nella programmazione. È stata passata una stringa invece dell'array con i campi per l'update.</p>\n");
		
		return($view);
		
	} else {
		array_walk($vettore, "dbEscape");	// Applica gli slash per i caratteri speciali
		
		$c = count($vettore);
		$i = 0;
		
		$query  = "UPDATE " . $table . " SET ";
		foreach ($vettore as $key => $value) {
			$query .= $key . " = '" . $value . "'";		// Implode dei nomi dei campi
			$i++;
			if ($i < $c) { $query .= ", "; }
		}
		$query .= " WHERE (" . $where . ")";

		return(dbQuery($query, $connection = $FL_DEFAULT_CONNECTION, $print_info, $descr_info, $FL_DEBUG_MODE));
	}
}

FUNCTION dbDelete($table, $where = '', $print_info = false, $descr_info = '', $FL_DEBUG_MODE = null) {
	
	global $FL_DEFAULT_CONNECTION;
	
	if (is_null($FL_DEBUG_MODE) == true) { 
		global $FL_DEBUG_MODE;
		$FL_DEBUG_MODE = $FL_DEBUG_MODE;
	}

	$query = "DELETE FROM " . $table;
	if ($where != '' AND !is_null($where)) { $query .= " WHERE(" . $where . ")"; }
	
	return (dbQuery($query, $connection = $FL_DEFAULT_CONNECTION, $print_info, $descr_info, $FL_DEBUG_MODE));
}

FUNCTION dbView($table, $where = '', $order_by = '', $print_info = false, $descr_info = '', $FL_DEBUG_MODE = null) {
	global $FL_DEFAULT_CONNECTION;
	
	$query = "SELECT * FROM " . $table;
	if ($where != '' AND !is_null($where)) { $query .= " WHERE(" . $where . ")"; }
	if ($order_by != '' AND !is_null($order_by)) { $query .= " ORDER BY (" . $order_by . ")"; }
	#$query = "SELECT * FROM " . $table . " WHERE (campo='" . mysql_real_escape_string($stringa) . "' AND campo=0) ORDER BY campo ASC";
	#$query = "SELECT * FROM " . $tab_ . " GROUP BY campo ORDER BY campo ASC";
	#$query = "SELECT * FROM " . $tab_ . " GROUP BY campo HAVING campo='" . mysql_real_escape_string($stringa) . "' ORDER BY campo ASC";
	
	return (dbQuery($query, $connection = $FL_DEFAULT_CONNECTION, $print_info, $descr_info, $FL_DEBUG_MODE));
}

FUNCTION dbQuery($query, $connection = '', $print_info = false, $descr_info = '', $FL_DEBUG_MODE = null, $list_fields_not_records = false) {
	
	set_time_limit(0);	// Impedisce il blocco dello script
	
	if ($connection == '') { 
		global $FL_DEFAULT_CONNECTION;
		$connection = $FL_DEFAULT_CONNECTION; 
	}
	
	if ($print_info == true) {
		echo "<h4>Operazione eseguita: " . $descr_info . "</h4>\n";
		echo "<p style='margin-top: 10px; margin-bottom: 10px;'>L'esecuzione è cominciata alle ore " . date("G:i:s") . ". </p>\n";	
	}
	
	if (is_null($FL_DEBUG_MODE) == true) { 
		global $FL_DEBUG_MODE;
		$FL_DEBUG_MODE = $FL_DEBUG_MODE;
	}
	
	if ($FL_DEBUG_MODE == true) {
		prCODE($testo_da_stampare = $query, $descrizione = 'DEBUG MODE // Query inviata:');
	}
	
	$tipoquery = trim(strtoupper(substr($query, 0, strpos($query, ' '))));			#prDIV("TIPO QUERY: " . $tipoquery);		// debug print
	
	$ris_query = mysqli_query($connection, $query);
	
	$cTOTALI = 0;
	$cGIUSTI = 0;
	$cERRATI = 0;
	$cRECORD = 0;	// Contatore del record corrente
	
	if (!$ris_query) { 
		
		$query_eseguita = false;
		
		if ($FL_DEBUG_MODE == true) {
			$view['_QUERY_'][] = $query_eseguita;
			$view['_DATI_'][] = array('ESITO ' . $tipoquery=>"<p class='warning'>Errore nella query:<br><code>$query</code><br><br>" . mysqli_error($connection) . "</p>\n");
		} else {
			$view['_QUERY_'][] = $query_eseguita;
			$view['_DATI_'][] = array('ESITO ' . $tipoquery=>"<p class='warning'>Impossibile eseguire la query, si è verificato un errore interno.</p>\n");
		}
			
	} else {
	
		$query_eseguita = true;
		
		// Contatore dei record totali, salvati correttamente o in errore
		$cTOTALI = mysqli_affected_rows($connection);
		
		
		if ($cTOTALI == 0) {
			// Nessun record è stato trovato dalla query di ricerca
			$view['_QUERY_'][] = $query_eseguita;
			$view['_DATI_'][] = array('ESITO ' . $tipoquery=>"<p class='warning'>Nessun record trovato o modificato</p>\n");
		} else {
	
			switch($tipoquery) {
				case "SELECT":
					$view['_QUERY_'][] = $query_eseguita;
					
					if ($list_fields_not_records == false) {
					
						// Scorre tutti i record 
						while ($riga = mysqli_fetch_assoc($ris_query)) {
							
							$res = array(); 
							$res = objectToArray($riga);
							
							$view['_DATI_'][] = $res;
							
							$cGIUSTI++;
							
						}	// fine while che scorre tabella per salvare i record
						
					} else {

						// Scorre tutti i record 
						while ($riga = mysqli_fetch_field($ris_query)) {
							
							/*
							$res = array(); 
							$res = objectToArray($riga);
							$view['_DATI_'][] = $res;
							*/
							$phpvarfld = "$" . "vett['" . $riga->name . "'] = ;";
							$view['_DATI_'][] = array($riga->table => $riga->name, 'phpvar' => $phpvarfld);
							
							$cGIUSTI++;
							
						}	// fine while che scorre tabella per salvare i campi
					
					}
				break;
				
				case "INSERT":
					$view['_QUERY_'][] = $query_eseguita;
					$view['_DATI_'][] = array('ESITO ' . $tipoquery=>"<p class='success'>Inserimento effettuato con successo</p>\n");
					$cGIUSTI = $cTOTALI;
				break;
				
				case "UPDATE":
					$view['_QUERY_'][] = $query_eseguita;
					$view['_DATI_'][] = array('ESITO ' . $tipoquery=>"<p class='success'>Modifica effettuata con successo</p>\n");
					$cGIUSTI = $cTOTALI;
				break;
				
				case "DELETE":
					$view['_QUERY_'][] = $query_eseguita;
					$view['_DATI_'][] = array('ESITO ' . $tipoquery=>"<p class='success'>Eliminazione effettuata con successo</p>\n");
					$cGIUSTI = $cTOTALI;
				break;
				
				default:
					$view['_QUERY_'][] = $query_eseguita;
					$view['_DATI_'][] = array('ESITO DEFAULT'=>"<p class='warning'>Impossibile riconoscere la query</p>\n");
					$cGIUSTI = $cTOTALI;				
				break;
				
			}
		}

	}

	if ($print_info == true) {
		// Stampa finale dopo le modifiche
		#echo "<a name='fine_query'><h4>Esito della query: " . $descr_info . "</h4></a>\n";
		if ($query_eseguita == true) {
			echo "<p>La query è stata completata alle ore " . date("G:i:s") . ", col seguente esito:<br>\n";
			echo "<span class='badge badge-success'>" . $cGIUSTI . "</span>  record esaminati con successo.<br>\n";
			if ($cERRATI == 0) {
				echo "<span class='badge'>" . $cERRATI . "</span> record non salvati.<br>\n";
			} else {
				echo "<span class='badge badge-important'>" . $cERRATI . "</span> record in errore.<br>\n";
			}
			echo "<span class='badge badge-info'>" . $cTOTALI . "</span>  record totali restituiti.<br>\n";
		} else {
			echo "<p>La query non è stata eseguita a causa di un errore interno. Orario finale: " . date("G:i:s") . "</p>\n";
			if ($FL_DEBUG_MODE == true) {
				prDIV("<h6>DEBUG MODE // Errore riscontrato:</h6>", $class = "", $style = "", $auto_margin = true);
				prDIV($testo_da_stampare = $view['_DATI_'][0]['ESITO ' . $tipoquery], $class = "well well-small", $style = "", $auto_margin = true);
			}			
		}
		echo "<hr>\n";
	}
	
	return ($view);
	
}	// endfunction dbquery


FUNCTION dbInfoListTables($database, $connection = '', $print_info = false, $descr_info = '', $FL_DEBUG_MODE = null) {
	$query = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA LIKE '" . $database . "'";
	$tablist = dbQuery($query, $connection = '', $print_info = false, $descr_info = '', $FL_DEBUG_MODE = null);
	
	return($tablist);
}


FUNCTION dbInfo($database, $connection = '', $print_info = false, $descr_info = '', $FL_DEBUG_MODE = null) {
	
	set_time_limit(0);	// Impedisce il blocco dello script
	
	$query = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA LIKE '" . $database . "'";
	$tablist = dbQuery($query, $connection = '', $print_info = false, $descr_info = '', $FL_DEBUG_MODE = null);
	
	//prR($tablist);
	
	if ($connection == '') { 
		global $FL_DEFAULT_CONNECTION;
		$connection = $FL_DEFAULT_CONNECTION; 
	}
	
	if ($print_info == true) {
		echo "<h4>Operazione eseguita: " . $descr_info . "</h4>\n";
		echo "<p style='margin-top: 10px; margin-bottom: 10px;'>L'esecuzione è cominciata alle ore " . date("G:i:s") . ". </p>\n";	
	}
	
	if (is_null($FL_DEBUG_MODE) == true) { 
		global $FL_DEBUG_MODE;
		$FL_DEBUG_MODE = $FL_DEBUG_MODE;
	}
	
	if ($FL_DEBUG_MODE == true) {
		prCODE($testo_da_stampare = $query, $descrizione = 'DEBUG MODE // Query inviata:');
	}
	
	// scorrere ogni record di tablist per ottenere i nomi delle tabelle, poi scorrere ogni tabella per ottenere la lista dei relativi campi
	foreach ($tablist['_DATI_'] as $key => $value) {
		
		$query = "SELECT * FROM " . $value['TABLE_NAME'];
		
		$ris_query = mysqli_query($connection, $query);
		
		$cTOTALI = 0;
		$cGIUSTI = 0;
		$cERRATI = 0;
		$cRECORD = 0;	// Contatore del record corrente
		
		if (!$ris_query) { 
			
			$query_eseguita = false;
			
			if ($FL_DEBUG_MODE == true) {
				$view['_QUERY_'][] = $query_eseguita;
				$view['_DATI_'][] = array('ESITO SELECT' => "<p class='warning'>Errore nella query:<br><code>$query</code><br><br>" . mysqli_error($connection) . "</p>\n");
			} else {
				$view['_QUERY_'][] = $query_eseguita;
				$view['_DATI_'][] = array('ESITO SELECT' => "<p class='warning'>Impossibile eseguire la query, si è verificato un errore interno.</p>\n");
			}
				
		} else {
		
			$query_eseguita = true;
			
			// Contatore dei record totali, salvati correttamente o in errore
			$cTOTALI = mysqli_affected_rows($connection);
			
			if ($cTOTALI == 0) {
				// Nessun record è stato trovato dalla query di ricerca
				$view['_QUERY_'][] = $query_eseguita;
				$view['_DATI_'][] = array('ESITO SELECT' => "<p class='warning'>Nessun record trovato o modificato</p>\n");
			} else {
				
				

				$view['_QUERY_'][] = $query_eseguita;
				
				// Scorre tutti i record 
				//while ($riga = mysqli_fetch_assoc($ris_query)) {
				
				while ($riga = mysqli_fetch_field($ris_query)) {
					
					$view['_DATI_'][] = $riga;
					
					$cGIUSTI++;
					
				}	// fine while che scorre tabella
					
			}

		}
	
	}	// end ciclo che scorre l'array con l'elenco delle tabelle
	
	if ($print_info == true) {
		// Stampa finale dopo le modifiche
		#echo "<a name='fine_query'><h4>Esito della query: " . $descr_info . "</h4></a>\n";
		if ($query_eseguita == true) {
			echo "<p>La query è stata completata alle ore " . date("G:i:s") . ", col seguente esito:<br>\n";
			echo "<span class='badge badge-success'>" . $cGIUSTI . "</span>  record esaminati con successo.<br>\n";
			if ($cERRATI == 0) {
				echo "<span class='badge'>" . $cERRATI . "</span> record non salvati.<br>\n";
			} else {
				echo "<span class='badge badge-important'>" . $cERRATI . "</span> record in errore.<br>\n";
			}
			echo "<span class='badge badge-info'>" . $cTOTALI . "</span>  record totali restituiti.<br>\n";
		} else {
			echo "<p>La query non è stata eseguita a causa di un errore interno. Orario finale: " . date("G:i:s") . "</p>\n";
			if ($FL_DEBUG_MODE == true) {
				prDIV("<h6>DEBUG MODE // Errore riscontrato:</h6>", $class = "", $style = "", $auto_margin = true);
				prDIV($testo_da_stampare = $view['_DATI_'][0]['ESITO SELECT'], $class = "well well-small", $style = "", $auto_margin = true);
			}			
		}
		echo "<hr>\n";
	}
	
	return ($view);
	
}	// endfunction dbquery


// PRECOSTRUTTI HTML /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

FUNCTION prBR($testo_da_stampare = "", $n_BR_dopo_testo = 1, $n_BR_prima_testo = 0) {
	echo str_repeat("<br>\n", $n_BR_prima_testo), (string) $testo_da_stampare, "\n", str_repeat("<br>\n", $n_BR_dopo_testo);
}

FUNCTION prDIV($testo_da_stampare = "", $class = "", $style = "", $auto_margin = false) {
	$st = "style='";
	if ($auto_margin == true) { $st .= "margin: 10px;"; }
	$style = $st . $style . "'";
	echo "<div class='" , $class , "' " , $style , ">\n\t", (string) $testo_da_stampare, "\n", "</div>\n";
}

FUNCTION prCODE($testo_da_stampare = '', $descrizione = '') {
	echo "<div style='margin: 10px;'>\n";
	if ($descrizione != "" AND !is_null($descrizione)) { echo "\t<h6>\n\t\t" . $descrizione . "\n\t</h6>\n"; }
	#echo "<textarea>";
	echo "\t<CODE>\n\t\t", (string) $testo_da_stampare, "\n", "\t</CODE>\n";
	#echo "</textarea>";
	echo "</div>\n";
}

FUNCTION prHR() {
	echo "<hr>\n";
}

FUNCTION prR($vettore, $titolo = "") {
	if ($titolo != "") { echo $titolo . "\n<br>\n"; }
	echo "<pre>\n";
	print_r($vettore);
	echo "</pre>\n";
}

FUNCTION duR($vettore, $titolo = "") {
	if ($titolo != "") { echo $titolo . "<br>"; }
	echo "<pre>\n";
	var_dump($vettore);
	echo "</pre>\n";
}





// STAMPA ARRAY MULTIDIMENSIONALE IN TABELLA HTML ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

FUNCTION tblR($vettoreDati, $CSS, $recursive = false, $return = false, $null = '&nbsp;', $ordina = false, $mostra_contatore = false, $inizio_contatore = 1, $step_contatore = 1) {
	
	################################################################################################################################################################################
	################################################################################################################################################################################
	## ATTENZIONE!!! PER FUNZIONARE CORRETTAMENTE QUESTA FUNZIONE RICHIEDE CHE IL PRIMO ELEMENTO DELLA LISTA SIA 0, altrimenti le singole righe diventeranno colonne ###############
	################################################################################################################################################################################
	################################################################################################################################################################################
	
	// Sanity check
    if (empty($vettoreDati) || !is_array($vettoreDati)) {
        return false;
    }
 
    if (!isset($vettoreDati[0]) || !is_array($vettoreDati[0])) {
        $vettoreDati = array($vettoreDati);
    }
	
	if ($ordina == true) {
		if (is_int(key($vettoreDati)) == true) {
			asort($vettoreDati);
		}
	}
	
    // Start the table

	if (is_array($CSS) == false OR !isset($CSS) OR is_null($CSS)) {
		$CSS = array();
		cssInit($vettoreDati[0], $CSS);
		cssMake($CSS);
	}
	
	
	if (array_key_exists('TBL', $CSS) === true) {
		$table = "\n<table" . $CSS['TBL']['attr_id'] . $CSS['TBL']['attr_name'] . $CSS['TBL']['CSS'] . $CSS['TBL']['TAGS'] . ">\n";
	} else {
		$table = "\n<table>\n";
	}
	
	$col = 0;
	
    // The header
    $table .= "\t<tr>";
    // Take the keys from the first row as the headings
    foreach (array_keys($vettoreDati[0]) as $key => $heading) {
		if ($mostra_contatore == true AND $col == 0) { $table .= '<th style="text-align:center; vertical-align:bottom">#</th>'; }
        if (array_key_exists($heading, $CSS['FLD']) === true) {
			$table .= '<th style="text-align: center;vertical-align:bottom">' . $CSS['FLD'][$heading]['label'] . '</th>';
		} else {
			$table .= '<th style="text-align: center;vertical-align:bottom">' . $heading . '</th>';
		}
		++$col;
    }
    $table .= "\t</tr>\n";
	
	// Contatore delle righe della tabella
	$c = $inizio_contatore;
	
    // The body
    foreach ($vettoreDati as $row) {
        $table .= "\t<tr>\n" ;
		
		if ($mostra_contatore == true) { $table .= "\t\t<td style='text-align: center;'><b>" . $c . "</b></td>\n"; }
		
        foreach ($row as $key => $cell) {

			if (array_key_exists($key, $CSS['FLD']) === true) {
				$FLD_attr_id = " id='" . $CSS['FLD'][$key]['attr_id'] . "_R" . $c . "'";
				$FLD_attr_name = " name='" . $CSS['FLD'][$key]['attr_name'] . "_R" . $c . "'";
				$FLD_CSS = $CSS['FLD'][$key]['CSS'];
				$table .= "\t\t<td" . $FLD_attr_id . $FLD_attr_name . $FLD_CSS . '>';
			} else {
				$table .= "\t\t<td>";
			}
 
            // Cast objects
            if (is_object($cell)) { $cell = (array) $cell; }
            
            if ($recursive === true && is_array($cell) && !empty($cell)) {              
				// Recursive mode
                $table .= "\n" . tblR($cell, true, true, $null, $ordina, $CSS) . "\n";
            } else {
				if (array_key_exists($key, $CSS['FLD']) === true) {
					if ($CSS['FLD'][$key]['callback'] != "" AND is_null($CSS['FLD'][$key]['callback']) == false) {
						$cell = call_user_func($CSS['FLD'][$key]['callback'], $cell);
					}
				}
				if (is_array($cell) == true) {
					$table .= "Array";
					//$table .= tblR($cell, $CSS = null, $recursive = false, $return = true, $null = '&nbsp;', $ordina = false, $mostra_contatore = false, $inizio_contatore = 1, $step_contatore = 1);
				} else {
					$table .= (strlen($cell) > 0) ?
						//htmlspecialchars((string) $cell) :
						(string) $cell : 
						$null;
				}
            }
 
            $table .= "</td>\n";
        }
 
        $table .= "\t</tr>\n";
		$c = $c + $step_contatore;
    }
 
    // End the table
    $table .= '</table>';
 
    // Method of output
    if ($return === false) {
        echo $table;
    } else {
        return $table;
    }
}	//endfuntion tblR




FUNCTION cssInit($vettoreDati, &$vCSS, $attr_id = '', $attr_name = '', $useBootstrapCSS = true, $label_maiuscole = true, $css_personalizzato = '') {
	
	if ($attr_id == "") { $attr_id = time(); } else { $attr_id = $attr_id; }

	if ($attr_name == "") { $attr_name = $attr_id; } else {	$attr_name = $attr_name; }
	
	$vCSS['TBL']['attr_id'] = " id='" . $attr_id . "'";
	$vCSS['TBL']['attr_name'] = " name='" . $attr_name . "'";
	
	if ($useBootstrapCSS == true) {
		$vCSS['TBL']['CSS'] = rtrim(" class='table table-striped table-bordered table-condensed table-hover' " . $css_personalizzato);
	} else {
		$vCSS['TBL']['CSS'] = rtrim(" " . $css_personalizzato);
	}
	$vCSS['TBL']['TAGS'] = "";
	$vCSS['FLD'] = array();
	
	if (empty($vettoreDati) == true) {
		$vettoreDati['ESITO INSERT'] = 'ESITO INSERT';
		$vettoreDati['ESITO UPDATE'] = 'ESITO UPDATE';
		$vettoreDati['ESITO DELETE'] = 'ESITO DELETE';
		cssCreate($vettoreDati, $vCSS['FLD'], $table_attr_id = $attr_id, $label_maiuscole = true);
	} else {
		cssCreate($vettoreDati, $vCSS['FLD'], $table_attr_id = $attr_id, $label_maiuscole = true);
	}
	
}

FUNCTION cssCreate($vettoreDati, &$vCSS, $table_attr_id, $label_maiuscole = true) {
	
	//prR($vettoreDati);
	
	foreach ($vettoreDati as $campo => $value) {
		
		if (is_array($value) == false OR is_string($campo) == true) { 
		
			if ($label_maiuscole == true) { 
				$label = strtoupper(str_replace("_", "<br>", $campo)); 
			} else { 
				$label = str_replace("_", "<br>", $campo); 
			}
			
			$attr_id = $table_attr_id . "_" . $campo;
			$attr_name = $table_attr_id . "_" . $campo;
			
			$vCSS[$campo] = array(
			'attr_id'=>$attr_id, 'attr_name'=>$attr_name, 'label'=>$label, 
			'callback'=>'', 'tipoCSS'=>'st', 'class'=>'', 
			'style'=>array('font-size'=>'', 'text-align'=>'center', 'color'=>'', 'background-color'=>''), 
			'CSS_personalizzato'=>'', 'CSS'=>'');
		
		} else {
			
			cssCreate($value, $vCSS, $table_attr_id);
		
		}
	
	}

}


FUNCTION cssEditFLD(&$vCSS, $campo, $label = '@', $attr_id = '@', $attr_name = '@', $tipoCSS = 'clst', $class = '', $Talign = 'center', $Tcol = '', $BGcol = '', $Fsize = '', $css_personalizzato = '', $callback = '') {
	if ($attr_id == "@") { $vCSS['FLD'][$campo]['attr_id'] = $campo; } else { $vCSS['FLD'][$campo]['attr_id'] = $attr_id; }
	if ($attr_name == "@") { $vCSS['FLD'][$campo]['attr_name'] = $campo; } else { $vCSS['FLD'][$campo]['attr_name'] = $attr_name; }
	if ($label == "@") { $label = strtoupper(str_replace("_", "<br>", $campo)); }
	$vCSS['FLD'][$campo]['label'] = $label;
	$vCSS['FLD'][$campo]['callback'] = $callback;
	$vCSS['FLD'][$campo]['tipoCSS'] = $tipoCSS;
	$vCSS['FLD'][$campo]['class'] = $class;
	$vCSS['FLD'][$campo]['style']['font-size'] = $Fsize;
	$vCSS['FLD'][$campo]['style']['text-align'] = $Talign;
	$vCSS['FLD'][$campo]['style']['color'] = $Tcol;
	$vCSS['FLD'][$campo]['style']['background-color'] = $BGcol;
	$vCSS['FLD'][$campo]['CSS_personalizzato'] = $css_personalizzato;
	$vCSS['FLD'][$campo]['CSS'] = "";
}


FUNCTION cssMake(&$vCSS) {
	array_walk($vCSS['FLD'], "cssProcess");
}


FUNCTION cssProcess(&$vCSS) {
	
	if (empty($vCSS) || !is_array($vCSS)) {
		
		$vCSS['CSS'] = "";
		return($vCSS);
		
	} else {
		
		if (array_key_exists('tipoCSS', $vCSS) == true) {
			switch($vCSS['tipoCSS']) {
				
				case "cl":
				case "class":
				case 1:		// solo class
					if (!is_null($vCSS['class']) AND $vCSS['class'] != "") {
						$vCSS['CSS'] = " class='" . $vCSS['class'] . "'";
					}
				break;
				
				case "st":
				case "style":
				case 2:		// solo style
					$vCSS['CSS'] = " style='";
					foreach ($vCSS['style'] as $key => $value) {
						if (!is_null($value) AND $value != "") {
							$vCSS['CSS'] .= $key . ":" . $value . "; ";
						}
					}
					$vCSS['CSS'] = rtrim($vCSS['CSS']);
					$vCSS['CSS'] .= $vCSS['CSS_personalizzato'] . "'";

				break;
				
				case "clst":
				case "class style":
				case "class e style":
				case "style class":
				case "style e class":
				case 3:		// class e style
					if (!is_null($vCSS['class']) AND $vCSS['class'] != "") {
						$vCSS['CSS'] = " class='" . $vCSS['class'] . "'";
					}
					$vCSS['CSS'] .= " style='";
					foreach ($vCSS['style'] as $key => $value) {
						if (!is_null($value) AND $value != "") {
							$vCSS['CSS'] .= $key . ":" . $value . "; ";
						}
					}
					$vCSS['CSS'] = rtrim($vCSS['CSS']);
					$vCSS['CSS'] .= $vCSS['CSS_personalizzato'] . "'";
				break;
				
				default:		// nessuno
					$vCSS['CSS'] = "";
				break;
			}
			
		} else {
			$vCSS['CSS'] = "";
		}

	}
}	// endfunction cssProcess


// DA CONTROLLARE /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

FUNCTION func_TDH2($tipo_cella_TH_o_TD, $testo_da_stampare, $colspan, $allineamento_L_C_R = 0, $font_size_BIG_MED_SMA = 0, $set_colori_foreback_BW_WB_BY = 0, $degree90 = 0){
	// CODICE PER LA STAMPA PREDEFINITA DI UNA CELLA DELLA TABELLA PER RIPORTARE I RISULTATI DELLA QUERY
	// La tabella è formattata secondo gli standard di TWITTER BOOTSTRAP, ma con personalizzazione mia

	// Definizioni di stile CSS da inserire sui campi della tabella
	// Allineamenti
	$txt[0] = "";
	$txt['L'] = "text-align: left;";
	$txt['C'] = "text-align: center;";
	$txt['R'] = "text-align: right;";
	// Dimensione font
	$sz[0] = "";
	$sz['BIG'] = "font-size: 12px;";
	$sz['MED'] = "font-size: 10px;";
	$sz['SMA'] = "font-size: 8px;";
	// Set di colori della cella (font/sfondo)
	$col[0] = "";
	$col["BW"] = "color: black; background-color: white";
	$col["WB"] = "color: white; background-color: black";
	$col["BY"] = "color: black; background-color: yellow";
	$col["BG"] = "color: black; background-color: palegreen";
	$col["BP"] = "color: black; background-color: pink";
	
	// Rotazione in verticale del testo
	$rot[0] = "";
	$rot[90] = "-webkit-transform: rotate(-90deg); -moz-transform: rotate(-90deg);"; 
	
	// Composizione del codice per stampare la cella
	$cella = "<" . $tipo_cella_TH_o_TD . " style = '" . $sz[$font_size_BIG_MED_SMA] . " " . $txt[$allineamento_L_C_R] . " " . $col[$set_colori_foreback_BW_WB_BY] . " " . $rot[$degree90] . "' colspan='" . $colspan . "'>" . $testo_da_stampare . "</" . $tipo_cella_TH_o_TD . ">\n";
	
	return($cella);
}


FUNCTION extended_date_it($timestamp_data) {
	// Trasforma la data, dal nuovo timestamp, in testo leggibile in chiaro
	$data_partita = date("D, d-m-Y", $timestamp_data);		
	return ($data_partita);
}	// FINE func_data_partita_in_chiaro


// FILESYSTEM /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

FUNCTION fsListFiles($dirname) {

	$arrayfiles = Array();

	if (file_exists($dirname)) {
		
		$handle = opendir($dirname);
		
		while (false !== ($file = readdir($handle))) { 
			if(is_file($dirname.$file)){
				array_push($arrayfiles,$file);
			}
		}
		
		$handle = closedir($handle);
		
	}

	sort($arrayfiles);

	return $arrayfiles;
    
}

FUNCTION fsListDirs() {
	// *** WORK IN PROGRESS ***
}


#####################################################################################################################################################################
#####################################################################################################################################################################
#####################################################################################################################################################################
#####################################################################################################################################################################
#####################################################################################################################################################################

// DEBUG SPACE FOR THE FRAMEWORK


FUNCTION main() {

	//set_time_limit(0);	// Impedisce il blocco dello script
	
	// Richiede i parametri base del database ($hostname, $user, $pass, $database)
	#require_once("__db.php");
	
	// Apre una connessione verso il database (e la setta come predefinita per le azioni successive)
	#dbConnect($hostname, $user, $pass, $database, $set_this_as_default_connection = true, $return_not_die = false, $return_array = true);
	
	/*
	
	### QUERY DI SELECT #########################################################################################################################################
	
	// Esegue una query di select e restituisce l'esito in un array chiamato vDati
	$vDati = dbView($table = "soc_partite", $where = "id_torneo = 39 AND giornata < 3", $order_by = "", $print_info = true, $descr_info = 'Prova di select', $FL_DEBUG_MODE = true);
	
	// Stampa un messaggio se la query è andata a buon fine
	if ($vDati['_QUERY_'] == true) { echo "La query ha funzionato<br>"; } else { echo "La query è fallita<br>"; }
	
	// Predisponiamo il codice CSS per personalizzare la stampa del vettore in una tabella HTML
	$vCSS = array();
	cssInit($vDati['_DATI_'], $vCSS);
	cssEditFLD($vCSS, $campo = "sport", $label = '@', $attr_id = '@', $attr_name = '@', $tipoCSS = 'clst', $class = '', $Talign = 'center', $Tcol = '', $BGcol = '', $Fsize = '', $css_personalizzato = 'font-variant:small-caps;', $callback = "");
	cssEditFLD($vCSS, $campo = "data_partita", $label = '@', $attr_id = '@', $attr_name = '@', $tipoCSS = 'clst', $class = '', $Talign = 'center', $Tcol = '', $BGcol = '', $Fsize = '', $css_personalizzato = '', $callback = "extended_date_it");
	cssMake($vCSS);
	
	// Stampa del vettore nella tabella HTML
	tblR($vDati['_DATI_'], $vCSS, $recursive = true, $return = false, $null = '&nbsp;', $ordina = true, $mostra_contatore = true, $inizio_contatore = 1);
	
	// Visualizza con i vettori con print_r
	prR($vDati);
	prR($vCSS);
	
	### QUERY DI INSERT #########################################################################################################################################
	
	$record2add = array('testo1' => 'primo record', 'testo2' => 'seconda colonna');
	
	$vCSS2 = array();
	cssInit($record2add, $vCSS2);
	cssMake($vCSS2);
	
	$added = dbInsert($table = "test_tab", $record2add, $print_info = true, $descr_info = 'Prova inserimento', $FL_DEBUG_MODE = null);
	
	tblR($added['_DATI_'], $vCSS2, $recursive = true, $return = false, $null = '&nbsp;', $ordina = true, $mostra_contatore = true, $inizio_contatore = 1);
	
	### QUERY DI UPDATE #########################################################################################################################################

	$c = array('testo1' => '1° record', 'testo2' => 'seconda colonna');
	
	$vCSS2 = array();
	cssInit($c, $vCSS2);
	cssMake($vCSS2);
	
	$d = dbUpdate($table = "test_tab", $c, $where = 'id = 11',$print_info = true, $descr_info = 'Prova update', $FL_DEBUG_MODE = null);
	
	tblR($d['_DATI_'], $vCSS2, $recursive = true, $return = false, $null = '&nbsp;', $ordina = true, $mostra_contatore = true, $inizio_contatore = 1);

	
	### QUERY DI DELETE #########################################################################################################################################

	$f = dbDelete($table = "test_tab", $where = 'id = 11', $print_info = true, $descr_info = 'Prova delete', $FL_DEBUG_MODE = null);
	
	$vCSS2 = array();
	cssInit($f, $vCSS2);
		
	tblR($f['_DATI_'], $vCSS2, $recursive = true, $return = false, $null = '&nbsp;', $ordina = true, $mostra_contatore = true, $inizio_contatore = 1);

	*/
	
}

// Decommenta la linea seguente per eseguire le operazioni previste nella main:
//main();

?>