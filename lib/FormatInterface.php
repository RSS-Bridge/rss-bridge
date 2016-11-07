<?php
interface FormatInterface {
	public function stringify();
	public function display();
	public function setItems(array $bridges);
	public function getItems();
	public function setExtraInfos(array $infos);
	public function getExtraInfos();
	public function setCharset($charset);
	public function getCharset();
}
