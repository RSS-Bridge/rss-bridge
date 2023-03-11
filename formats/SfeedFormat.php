<?PHP
function escape(string $str) {
	$str = str_replace("\\", "\\\\", $str);
	$str = str_replace("\n", "\\n", $str);
	return str_replace("\t", "\\t", $str);
}

function getFirstEnclosure(array $enclosures) {
	if(count($enclosures) >= 1)
		return $enclosures[0];
	return "";
}

function getCategories(array $cats) {
	$toReturn = "";
	$i = 0;
	foreach($cats as $cat) {
		$toReturn .= $cat;
		if(count($cats) < $i)
			$toReturn .= '|';
		$i++;
	}
	return $toReturn;
}

class SfeedFormat extends FormatAbstract {
	const MIME_TYPE = 'text/plain';

	public function stringify() {
		$items = $this->getItems();

		$toReturn = "";
		foreach($items as $item) {
			$toReturn .= sprintf("%s\t%s\t%s\t%s\thtml\t\t%s\t%s\t%s\n",
					$item->toArray()["timestamp"],
					escape($item->toArray()["title"]),
					$item->toArray()["uri"],
					escape($item->toArray()["content"]),
					$item->toArray()["author"],
					getFirstEnclosure($item->toArray()["enclosures"]),
					getCategories($item->toArray()["categories"])
					);
		}

		// Remove invalid non-UTF8 characters
		ini_set('mbstring.substitute_character', 'none');
		$toReturn = mb_convert_encoding($toReturn, $this->getCharset(), 'UTF-8');
		return $toReturn;
	}
}
