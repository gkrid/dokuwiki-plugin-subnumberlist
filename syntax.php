<?php
/**
 * Plugin Color: Sets new colors for text and background.
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 */
 
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();
 
/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_subnumberlist extends DokuWiki_Syntax_Plugin {
 
	public function getType(){ return 'container'; }
	public function getAllowedTypes() { return array('container'); }   
	public function getSort(){ return 158; }
	public function connectTo($mode) {
		$this->Lexer->addEntryPattern('<subnumberlist>(?=.*?</subnumberlist>)',$mode,'plugin_subnumberlist');
	}
	public function postConnect() {
		$this->Lexer->addExitPattern('</subnumberlist>','plugin_subnumberlist');
	}
 
 
	/**
	 * Handle the match
	 */
	public function handle($match, $state, $pos, Doku_Handler $handler){
		switch ($state) {
			case DOKU_LEXER_ENTER:
				return array($state, $match);
 
			case DOKU_LEXER_UNMATCHED : 
				return array($state, $match);
 
			case DOKU_LEXER_EXIT :
				return array($state, '');
 
		}       
		return false;
	}
 

   public function render($mode, Doku_Renderer $renderer, $indata) {
		if($mode == 'xhtml') {
			list($state, $match) = $indata;
			switch ($state) {
			case DOKU_LEXER_ENTER :   
				$renderer->doc .= '<div class="subnumberlist">';
				break;
 
			  case DOKU_LEXER_UNMATCHED : 
				$renderer->doc .= $renderer->_xmlEntities($match);
				break;
 
			  case DOKU_LEXER_EXIT :
				$renderer->doc .= '</div>';
				break;
			}
			return true;
		}
		return false;
	}
}
 
//Setup VIM: ex: et ts=4 enc=utf-8 :
?>
