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
        $this->Lexer->addEntryPattern('[ \t]*\n {2,}[\-]',$mode,'plugin_subnumberlist');
        $this->Lexer->addEntryPattern('[ \t]*\n\t{1,}[\-]',$mode,'plugin_subnumberlist');

        $this->Lexer->addPattern('\n {2,}[\-]','plugin_subnumberlist');
        $this->Lexer->addPattern('\n\t{1,}[\-]','plugin_subnumberlist');

    }
    
    function postConnect() {
        $this->Lexer->addExitPattern('\n','plugin_subnumberlist');
    }
    
    private $levels = array();
    private $max_levels = 100;
    private $start_level = 0;
    function init_levels() {
        $levels = array();
    	for ($i = 0; $i < $this->max_levels; $i++)
    		$this->levels[$i] = 0;
    }
    
    function interpretSyntax($match) {
        // Is the +1 needed? It used to be count(explode(...))
        // but I don't think the number is seen outside this handler
        return substr_count(str_replace("\t",'  ',$match), '  ') + 1;
    }
    

    
    function gen_numbers($level) {
        $r = '';
        for ($i = $this->start_level; $i < $level; $i++)
            $r .= "&nbsp;&nbsp;&nbsp;&nbsp;";
        for ($i = $this->start_level; $i <= $level; $i++)
       	    $r .= $this->levels[$i].'.';
       	return $r;
    }

    function handle($match, $state, $pos, &$handler)
    {
        switch ($state) {
            case DOKU_LEXER_ENTER:
            	//list_open
            	$this->init_levels();
            	$level = $this->interpretSyntax($match); 
            	$this->start_level = $level;
                $this->levels[$level]++;
            	$nr = $this->gen_numbers($level);
            	return array('BEGIN_LIST', array('BEGIN_ELEMENT', $nr));
            break;
            case DOKU_LEXER_EXIT:
            	//list_close
               return array('END_ELEMENT', 'END_LIST');
            break;
            case DOKU_LEXER_MATCHED:
            	//list_item
            	$level = $this->interpretSyntax($match); 
            	$this->levels[$level]++;
            	$nr = $this->gen_numbers($level);
                return array('END_ELEMENT', array('BEGIN_ELEMENT', $nr));
            break;
            case DOKU_LEXER_UNMATCHED:
                return array(array('CDATA', $match));
            break;
        }
        return true;
    }

    function render($mode, &$renderer, $data) {
	if($mode == 'xhtml'){
       		foreach ($data as $action)
       		    if (is_array($action))
       		        switch($action[0]) {
       		            case 'BEGIN_ELEMENT':
       		                $renderer->doc .= '<li>'.$action[1];
       		                break;
       		            case 'CDATA':
       		                $renderer->doc .= $action[1];
       		                break;
       		        }
       		   else
       		    switch($action) {
       		        case 'BEGIN_LIST':
       		            $renderer->doc .= '<ol style="list-style-type:none; margin-left:-1em;">';
       		            break;
       		        case 'END_LIST':
       		            $renderer->doc .= '</ol>';
       		            break;
       		        case 'END_ELEMENT':
       		            $renderer->doc .= '</li>';
       		            break;
       		    }
       		   
	}
	return false;
    }
}
