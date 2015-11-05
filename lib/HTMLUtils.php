<?php
class HTMLUtils {

	public static function getHelperButtonFormat($value, $name){
		return '<button type="submit" name="format" value="' . $value . '">' . $name . '</button>';
	}

	public static function getHelperButtonsFormat($formats){
		$buttons = '';
			foreach( $formats as $name => $infos )
			{
				if ( isset($infos['name']) )
				{
					$buttons .= HTMLUtils::getHelperButtonFormat($name, $infos['name']) . PHP_EOL;
				}
			}
		return $buttons;
	}

	public static function displayBridgeCard($bridgeName, $formats, $isActive = true)
	{


		$bridgeElement = Bridge::create($bridgeName);
		if($bridgeElement == false) {
			return "";
		}
		$bridgeElement->loadMetadatas();

		$name = '<a href="'.$bridgeElement->uri.'">'.$bridgeElement->name.'</a>';
		$description = $bridgeElement->description;

		$card = <<<CARD
		<section id="bridge-{$bridgeName}" data-ref="{$bridgeName}">
			<h2>{$name}</h2>
			<p class="description">
				{$description}
			</p>
CARD;

		// If we don't have any parameter for the bridge, we print a generic form to load it.
		if(count($bridgeElement->parameters) == 0) {

			$card .= '<form method="POST" action="?">
					<input type="hidden" name="action" value="display" />
					<input type="hidden" name="bridge" value="' . $bridgeName . '" />' . PHP_EOL;

			if ($isActive)
			{
				$card .= HTMLUtils::getHelperButtonsFormat($formats);
			}
			else
			{
				$card .= '<span style="font-weight: bold;">Inactive</span>';
			}
			$card .= '</form>' . PHP_EOL;

		}

		foreach($bridgeElement->parameters as $parameterName => $parameter)
		{
			$card .= '<ol class="list-use">' . PHP_EOL;
			if(!is_numeric($parameterName)) {
				$card .= '<h5>'.$parameterName.'</h5>' . PHP_EOL;
			}
			$card .= '<form method="POST" action="?">
						<input type="hidden" name="action" value="display" />
						<input type="hidden" name="bridge" value="' . $bridgeName . '" />' . PHP_EOL;

			$parameter = json_decode($parameter, true);

			foreach($parameter as $inputEntry) {

				if(!isset($inputEntry['exampleValue'])) $inputEntry['exampleValue'] = "";

				$idArg = 'arg-' . $bridgeName . '-' . $parameterName . '-' . $inputEntry['identifier'];

				$card .= '<label for="' .$idArg. '">' .$inputEntry['name']. ' : </label>' . PHP_EOL;

				if(!isset($inputEntry['type']) || $inputEntry['type'] == 'text') {
					$card .= '<input id="' . $idArg . '" type="text" value="" placeholder="' . $inputEntry['exampleValue'] . '" name="' . $inputEntry['identifier'] . '" /><br />' . PHP_EOL;
				} else if($inputEntry['type'] == 'number') {
					$card .= '<input id="' . $idArg . '" type="number" value="" placeholder="' . $inputEntry['exampleValue'] . '" name="' . $inputEntry['identifier'] . '" /><br />' . PHP_EOL;
				} else if($inputEntry['type'] == 'list') {
					$card .= '<select id="' . $idArg . '" name="' . $inputEntry['name'] . '" >';
					foreach($inputEntry['values'] as $listValues) {

						$card .= "<option value='" . $listValues['value'] . "'>" . $listValues['name'] . "</option>";

					}
					$card .= '</select><br >';
				} else if($inputEntry['type'] == 'checkbox') {

					$card .= '<input id="' . $idArg . '" type="checkbox" name="' . $inputEntry['identifier'] . '" /><br />' . PHP_EOL;

				}

			}
			if ($isActive)
			{
				$card .= HTMLUtils::getHelperButtonsFormat($formats);
			}
			else
			{
				$card .= '<span style="font-weight: bold;">Inactive</span>';
			}
			$card .= '</form>' . PHP_EOL;

		}

		$card .= '<span class="maintainer">'.$bridgeElement->maintainer.'</span>';
		$card .= '</section>';

		return $card;
	}


}
?>
