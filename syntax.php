<?php

// must be run within DokuWiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once DOKU_PLUGIN.'syntax.php';

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_subnumberlist extends DokuWiki_Syntax_Plugin {

    function getPType(){
       return 'block';
    }

    function getType() { return 'substition'; }
    function getSort() { return 1; }
    function getAllowedTypes() { return array('formatting', 'substition', 'disabled'); }     

    function connectTo($mode) {
        $this->Lexer->addEntryPattern('[ \t]*\n {2,}[\-\*]',$mode,'plugin_subnumberlist');
        $this->Lexer->addEntryPattern('[ \t]*\n\t{1,}[\-\*]',$mode,'plugin_subnumberlist');

        $this->Lexer->addPattern('\n {2,}[\-\*]','plugin_subnumberlist');
        $this->Lexer->addPattern('\n\t{1,}[\-\*]','plugin_subnumberlist');

    }
    
    function postConnect() {
        $this->Lexer->addExitPattern('\n','plugin_subnumberlist');
    }
    
    /*map real level(nr of spaces) into logical level*/
    private $levels_map = array();
    //ident
    private $levels = array();
    //types of lists;
    private $types = array();
    
    function interpretSyntax($match, &$type) {
        if ( substr($match,-1) == '*' ) {
            $type = 'u';
        } else {
            $type = 'o';
        }
        // Is the +1 needed? It used to be count(explode(...))
        // but I don't think the number is seen outside this handler
        return substr_count(str_replace("\t",'  ',$match), '  ') + 1;
    }

    function gen_numbers($level) {
        $r = '';
        for ($i = 0; $i <= $level; $i++)
       	    $r .= $this->levels[$i].'.';
       	return $r;
    }
    
    private $previous_level;
    function handle($match, $state, $pos, &$handler)
    {
        switch ($state) {
            case DOKU_LEXER_ENTER:
            	//list_open
            	$level = $this->interpretSyntax($match, $type); 
            	
 		$this->levels_map[$level] = 0;
            	$this->previous_level = 0;
                $this->levels[0] = 1;
                $this->types[0] = $type;
                
            	$nr = $this->gen_numbers(0);
            	return array(array('BEGIN_LIST', $type), array('BEGIN_ELEMENT', $nr, $type));
            break;
            case DOKU_LEXER_EXIT:
            	//list_close
               return array('END_ELEMENT', 'END_LIST');
            break;
            case DOKU_LEXER_MATCHED:
            	//list_item
            	$level = $this->interpretSyntax($match, $type); 
            	if (!isset($this->levels[$mlevel]))
            		$this->levels[$mlevel] = 0;
            	//schodzimy w dół
               	if (!isset($this->levels_map[$level]) || $this->levels_map[$level] > $this->previous_level) {
               		$this->previous_level++;
               		$this->levels_map[$level] = $this->previous_level;
               		$this->levels[$this->previous_level] = 1;
               		$this->types[$this->previous_level] = $type;
               		$nr = $this->gen_numbers($this->previous_level);
               		$r = array(array('BEGIN_LIST', $type), array('BEGIN_ELEMENT', $nr, $type));
               	//idziemy w górę
               	} else if ($this->levels_map[$level] < $this->previous_level) {
               		$return_level = $this->levels_map[$level];
               		$prev_lvl = $this->previous_level;
         		
               		$r = array();
               		for ($i = $prev_lvl; $i > $return_level; $i--) {
               			$r[] = 'END_ELEMENT';
               			//not last element
               			$r[] = array('END_LIST', $this->types[$i]);
               			
               			if ($i != $return_level-1) {
		       			$l = array_search($i, $this->levels_map);
		       			unset($this->levels_map[$l]);
		       		}
               		}
               		$r[] = 'END_ELEMENT';
               		
               		$this->levels[$return_level]++;
               		$nr = $this->gen_numbers($return_level);
               		$r[] = array('BEGIN_ELEMENT', $nr, $this->types[$return_level]);
               	
               		$this->previous_level = $return_level;
               	//zostajemy na tym samym poziomie
               	} else {
            		$mlevel = $this->levels_map[$level];
            	       	$this->levels[$mlevel]++;
            	       	$nr = $this->gen_numbers($mlevel);
            	       	
               		$r = array('END_ELEMENT', array('BEGIN_ELEMENT', $nr, $this->types[$mlevel]));
               }
               	return $r;
               	
            break;
            case DOKU_LEXER_UNMATCHED:
                return array(array('CDATA', $match));
            break;
        }
        return true;
    }

    function render($mode, &$renderer, $data) {
	if($mode == 'xhtml'){
       		foreach ($data as $action) {
       		    if (is_array($action))
       		        switch($action[0]) {
       		            case 'BEGIN_ELEMENT':
       		          	if ($action[2] == 'o')
       		               		$renderer->doc .= '<li>'.$action[1];
       		               	else
       		               		$renderer->doc .= '<li>';
       		               	$renderer->doc .= '<div class="li" style="display:inline">';
       		                break;
       		            case 'BEGIN_LIST':
       		            	if ($action[1] == 'o')
       		           		$renderer->doc .= "\n".'<ol style="list-style-type:none; margin-left:-1em;">'."\n";
       		           	else
       		           		$renderer->doc .= "\n".'<ul>'."\n";
       		            	break;
       		            case 'END_LIST':
       		            	if ($action[1] == 'o')
       		            		$renderer->doc .= '</ol>'."\n";
       		            	else
       		            		$renderer->doc .= '</ul>'."\n";
       		            break;
       		            case 'CDATA':
       		                $renderer->doc .= $action[1];
       		                break;
       		        }
       		   else
       		    switch($action) {
       		        case 'END_ELEMENT':
       		        	$renderer->doc .= '</div>';
       		            $renderer->doc .= '</li>'."\n";
       		            break;
       		    }
       		}
       		   
	}
	return false;
    }
}
