<?php
require_once(__DIR__ . '/FormatInterface.php');
abstract class FormatAbstract implements FormatInterface {
	const DEFAULT_CHARSET = 'UTF-8';

	protected
		$contentType,
		$charset,
		$items,
		$extraInfos;

	public function setCharset($charset){
		$this->charset = $charset;

		return $this;
	}

	public function getCharset(){
		$charset = $this->charset;

		return is_null($charset) ? static::DEFAULT_CHARSET : $charset;
	}

	protected function setContentType($contentType){
		$this->contentType = $contentType;

		return $this;
	}

	protected function callContentType(){
		header('Content-Type: ' . $this->contentType);
	}

	public function display(){
		echo $this->stringify();

		return $this;
	}

	public function setItems(array $items){
		$this->items = array_map(array($this, 'array_trim'), $items);

		return $this;
	}

	public function getItems(){
		if(!is_array($this->items))
			throw new \LogicException('Feed the ' . get_class($this) . ' with "setItems" method before !');

		return $this->items;
	}

	/**
	* Define common informations can be required by formats and set default value for unknow values
	* @param array $extraInfos array with know informations (there isn't merge !!!)
	* @return this
	*/
	public function setExtraInfos(array $extraInfos = array()){
		foreach(array('name', 'uri') as $infoName){
			if( !isset($extraInfos[$infoName]) ){
				$extraInfos[$infoName] = '';
			}
		}

		$this->extraInfos = $extraInfos;

		return $this;
	}

	/**
	* Return extra infos
	* @return array See "setExtraInfos" detail method to know what extra are disponibles
	*/
	public function getExtraInfos(){
		if( is_null($this->extraInfos) ){ // No extra info ?
			$this->setExtraInfos(); // Define with default value
		}

		return $this->extraInfos;
	}

	/**
	 * Sanitized html while leaving it functionnal.
	 * The aim is to keep html as-is (with clickable hyperlinks)
	 * while reducing annoying and potentially dangerous things.
	 * Yes, I know sanitizing HTML 100% is an impossible task.
	 * Maybe we'll switch to http://htmlpurifier.org/
	 * or http://www.bioinformatics.org/phplabware/internal_utilities/htmLawed/index.php
	 */
	protected function sanitizeHtml($html)
	{
		$html = str_replace('<script','<&zwnj;script',$html); // Disable scripts, but leave them visible.
		$html = str_replace('<iframe','<&zwnj;iframe',$html);
		$html = str_replace('<link','<&zwnj;link',$html);
		// We leave alone object and embed so that videos can play in RSS readers.
		return $html;
	}

	protected function array_trim($elements){
		foreach($elements as $key => $value){
			if(is_string($value))
				$elements[$key] = trim($value);
		}
		return $elements;
	}
}
