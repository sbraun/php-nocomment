<?php

/**
* Outil d'analyse de code PHP
*/
class NoComment {
	
	
	/**
	* Loggeur associ� (optionnel)
	*/
	private $_log = NULL;
	
	/**
	* Version
	*/
	public $version = '0.1';
	
	/**
	* Liste des tokens
	*/
	private $tokens = array();

	/**
	* Contenu du fichier
	*/
	private $content = array();
	
	/**
	* Constructeur
	* @param object $Log Loggeur
	*/
	function __construct($Log = NULL) {
		$this->_log = $Log;
		$this->_nl = "\n";
	}
	
	
	/**
	* Enregistre un message de log. Si aucun Loggeur n'est d�finis, envoie sur la sortie standard
	* @param string $message	Message
	*/
	function log($message) {
		if ($this->_log === NULL)	echo $message."\n";
		else						$this->_log->log($message);
	}
	
	
	/**
	* Ajoute des commentaires aux fonctions
	* @param string $file			Chemin du fichier � traiter
	* @return string|false|NULL 	Retourne le fichier comment�. Retourne false en cas d'erreur. RETOURNE NULL SI 
									AUCUNE MODIFICATION n'a �t� faite dans le code source.
	*/
	function addFctComment($file) {
		
		$this->log('Analyse '.$file);
	
		if (!file_exists($file)) {
			$this->log(' - Fichier introuvable');
			return false;	
		}
		
		if ($out == '') {
			$out = $file;
		}
		
		$this->content = file($file);
		$this->tokens = token_get_all(implode('', $this->content));
		
		if (count($this->tokens) < 10) {
			$this->log(' - Echec tokenizer');
			return false;	
		}
		
		$modified = false;
		
		$this->n = count($this->tokens);
		for($i=0; $i < $this->n; $i++) {
			$t = $this->tokens[$i];
			
			// On ne s'interesse qu'au token de fonction
			if ($t[0] != T_FUNCTION) continue;
			
			// Distingue le cas d'une fonction avec retour par r�f�rence
			if (is_string($this->tokens[$i+2]) && $this->tokens[$i+2] == '&') {
				$log = '  l.'.$this->tokens[$i+3][2].'  function '.$this->tokens[$i+3][1].'()';
			} else {
				$log = '  l.'.$this->tokens[$i+2][2].'  function '.$this->tokens[$i+2][1].'()';				
			}
			
			// La fonction n'a pas de bloc de commentaire
			if ($this->_isCommentBefore($i) === false) {
				$log .=' ADD';
				
				// R�cup�re l'indentation de la d�finition de la fonction pour la r�appliquer
				// au bloc de commentaire
				preg_match("/^(\s*)(function|private|static|public|abstract|protected)/", $this->content[ $t[2]-1 ], $regs);
				$indent = $regs[1];


				// Extrait les informations de la d�finition de la fonction
				// G�n�re le bloc de commentaire
				// Ajout le bloc
				$params = $this->_extractArgs($i);
				if ($params === false) {
					$log .= ' ERROR PARAM SYNTAXE';	
				} else {
					$modified = true;
					$comment = $this->_produceComment($params, $indent);
					// Ajout du commentaire
					$this->_addContent($i, $comment);
				}
			} else {
				$log .=' OK';
			}
			$this->log($log);
		}
		
		unset($this->tokens);
		
		if ($modified) {
			$this->log('Fichier modifi�');
			return implode('', $this->content);
		} else {
			$this->log('Aucune MAJ');
			return NULL;
		}
		$this->log('-------------------------------------');
	}
	
	
	/**
	* Ajoute un bloc commentaire avant une fonction
	* @param int $num 			Num�ro de token
	* @param string $content	Bloc commentaire � ajouter
	*/
	function _addContent($num, $content) {
		$l = $this->tokens[$num][2]-1; // le contenu est num�rot� � partir de 0 alors que les token num�rotent � partir de 1	
		$this->content[$l] = $content.$this->_nl.$this->content[$l];
	}
	
	
	/**
	* G�n�re un bloc de commentaire d'une fonction
	* @param array $params	Liste des tags de la fonction (@param)
	* @param string	$indent	Identation du bloc
	* @return string
	*/
	function _produceComment($params, $indent = '') {
		$block = $indent."/**".$this->_nl;
		$block .= $indent."* @todo Comment".$this->_nl;
		foreach($params as $p) {
			$block.=$indent."* @param ".$p[1]." ".$p[0]."\t...";
			if ($p[2] !== false) $block .= ' (d�faut : '.$p[2].')';
			$block.=$this->_nl;
		}
		$block .= $indent."* @return ".$this->_nl;
		$block .= $indent."*/";
		return $block;
	}
	
	
	/**
	* Teste la pr�sence d'un bloc de commentaire pour une fonction
	* @param int $num		Num�ro de token
	* return boolean|int	Retourne le num�ro de token. Retourne false en cas d'erreur
	*/
	function _isCommentBefore($num) {
		
		// parcours tous les tokens pr�c�dents
		// seuls sont autoris�s les tockens :
		// - T_WHITESPACE : espaces
		// - T_COMMENT & T_DOC_COMMENT: commentaires
		for($i=$num-1; $i > 0; $i--) {
			if (is_string($this->tokens[$i]) && $this->tokens[$i] == ';') return false;
			$token = $this->tokens[$i][0];
			if (($token == T_COMMENT || $token == T_DOC_COMMENT) && substr($this->tokens[$i][1], 0, 2) == '/*') return $i;
			if ($token == T_WHITESPACE || $token == T_STATIC 
				|| $token == T_PRIVATE || $token ==  T_PUBLIC 
				|| $token ==  T_PROTECTED || $token == T_ABSTRACT) continue;
			return false;
		}
		return false;	
	}
	
	
	/**
	* Extrait la liste des arguments d'une fonction
	* @param int $num		Num�ro de token
	* return array|false	Retourne la liste des arguements sous la forme d'un tableau � deux dimensions :
							array (nom variable, type, valeur par d�faut).
							Retourne false en cas d'erreur
	*/
	function _extractArgs($num) {
		
		if ($this->tokens[$num][0] !== T_FUNCTION) return false;
		
		$args = array();
		for($i=$num+4; $i < $this->n; $i++) { // les arguments commencent 3 tocken apr�s le token "function"
			if (is_string($this->tokens[$i]) && $this->tokens[$i] == ')') break; // fin des arguments
					
			// une d�finition de variables trouv�e
			if ($this->tokens[$i][0] != T_VARIABLE) continue;
	
			$var = $this->tokens[$i][1];
			$type = 'string';
			
			$default = $this->_catchNextText($i, "=", T_WHITESPACE);
			if ($default !== false) {
				$i = $default; // saut
				$default = $this->_catchNextToken($default, array(T_CONSTANT_ENCAPSED_STRING, T_LNUMBER, T_STRING, T_FUNCTION, T_CONST), T_WHITESPACE);				
				if ($default !== false) {
					$i = $default; // saut				
					$default=$this->tokens[$default][1];
				}
			}
			$args[] = array($var, $type, $default);
		}
		return $args;
	}
	
	
	/**
	* Capture le prochain token qui est dans la liste indiqu�e et retourne son num�ro
	* @param int $start			Num�ro de token de d�part
	* @param array $token		Liste des tokens � capturer
	* @param int $ignore_token	Token � ignorer
	* @return int	Num�ro de token
	*/
	function _catchNextToken($start, $token, $ignore_token) {
		if (!is_array($token)) $token = array($token);
		for($i=$start+1; $i < $this->n; $i++) {
			if (is_string($this->tokens[$i])) return false;
			if ($this->tokens[$i][0] === $ignore_token) continue;
			if (in_array($this->tokens[$i][0], $token)) return $i;
			return false;
		}
	}
	
	
	/**
	* Retourne le num�ro du prochain token qui correspond au texte indiqu�. La recherche s'arr�te au premier
	* token qui n'est pas un token � ignorer.
	* @param int $start			Num�ro de token de d�part
	* @param string $txt		Liste des tokens � capturer
	* @param int $ignore_token	Token � ignorer
	* @return int	Num�ro de token
	*/
	function _catchNextText($start, $txt, $ignore_token) {
		for($i=$start+1; $i < $this->n; $i++) {
			if (is_string($this->tokens[$i]) && $this->tokens[$i] === $txt) return $i;
			if (!is_string($this->tokens[$i]) && $this->tokens[$i][0] === $ignore_token) continue;
			return false;
		}
	}
	
	
	/**
	* Ajoute le bloc de commentaire g�n�rique � un fichier
	* @param string $file		Chemin du fichier � traiter
	* @param array	$comments	Tableau des associatifs des valeurs par d�faut
								array(
								  'category'=>'',				  
								  'package'=>'',				  
								  'author'=>'',
								  'version'=>''				  
								  'copyright'=>''
								 )
	* @return string|false|NULL 	Retourne le fichier comment�. Retourne false en cas d'erreur. RETOURNE NULL SI 
									AUCUNE MODIFICATION n'a �t� faite dans le code source.
	*/
	function addFileComment($file, $comments) {
	
		if (!is_array($comments)) return false;
	
		$this->log('Analyse '.$file);

		if (!file_exists($file)) {
			$this->log(' - Fichier introuvable');
			return;	
		}
		
		$content = file_get_contents($file);
		$tokens = token_get_all($content);
		
		if (count($tokens) < 10) {
			$this->log(' - Echec tokenizer');
			return;	
		}
	
		$buffer = false;
		$start = 0;
		$start_tag = 0;
			
		// analyse les 10 premiers tokens � la recherche d'un commentaire
		for($i=0;$i<10;$i++) {
			$token = $tokens[$i];
			switch(token_name($token[0])) {
				case 'T_OPEN_TAG' :
							$continue = true;
							$start += strlen($token[1]);
							$start_tag = strlen($token[1]);
							break;
				case 'T_WHITESPACE' :
							$continue = true;
							$start += strlen($token[1]);
							break;
				case 'T_DOC_COMMENT' :
							if(substr($token[1], 0, 3) == '/**') {
								$buffer = substr($token[1], 0, -3);
								$len = strlen($token[1])-3;
							}
							$continue = false;
							break;
				default :
							$continue = false;
							break;
			}
			
			if ($continue == false) break;
		}
		unset($tokens);
				
		if (!$buffer) {
			// Aucun bloc de comentaire dans le fichier
			$buffer = "\n/**";
			foreach($comments as $k=>$v) {
				$buffer .= "\n"."* @".$k." ".$v;
			}
			$buffer .= "\n*/";
			
			$this->log(' - Ajout du bloc');	
			
			$content = substr_replace($content, $buffer, $start_tag, 0);
			return $content;			
			
		} else {
			// Le fichier commence par un bloc de commentaire, on assuer la fusion avec les valeurs voulues
			$buffer_new = $this->_majBloc($buffer, $comments);
			if ($buffer_new == $buffer) {
				$this->log(' - Aucune mise � jour');
				return NULL;
			} else {
				$this->log(' - Mise � jour du bloc');
				// reecrire le fichier au besoin
				$content = substr_replace($content, $buffer_new, $start, $len);
				return $content;				
			}
		}	
	}


	/**
	* Fusionne un bloc de commentaire existant avec les valeurs par configur�e. Les valeurs
	* configur�es ont priorit� sur celle pr�sente dans le bloc de commanaitre existant.
	* @param string $buffer		Bloc de commentaire � traiter
	* @param array $comments	Tableau associatif des valeurs 
	* @return string 			Bloc de commentaire mis � jour
	*/
	private function _majBloc($buffer, $comments) {		
		foreach($comments as $k=>$v) {
			if(preg_match("/\* @".$k."/", $buffer)) {
				$buffer = preg_replace("/\* @".$k.".*/", "* @".$k." ".$v, $buffer);
			} else {
				$buffer .= "\n"."* @".$k." ".$v;
			}
		}
		return $buffer;
	}

}

?>