<?php

class PokemonTVBridge extends XPathAbstract {
	const NAME = 'Pokémon TV (German)';
	const URI = 'https://www.pokemon.com/de/pokemon-folgen/pokemon-tv-staffeln/';
	const DESCRIPTION = 'Gibt die letzten Folgen einer Staffel aus';
	const MAINTAINER = 'dhuschde';
	const CACHE_TIMEOUT = 3600; // 1 hour
	const XPATH_EXPRESSION_ITEM = '/html/body/div[4]/section[3]/div/ul/li[*]';
	const XPATH_EXPRESSION_ITEM_TITLE = './/*/span[3]';
	const XPATH_EXPRESSION_ITEM_URI = './/a/@href';
	const XPATH_EXPRESSION_ITEM_ENCLOSURES = './/*/img/@src';
	const SETTING_FIX_ENCODING = false;
	const PARAMETERS = array( // Language is not easy due to Pokemons bad Link structure... Feel free to make PR
		'' => array(
			'staffel' => array(
			'name' => 'Staffel',
			'required' => true
		)
	));

	protected function getSourceUrl(){
		return 'https://www.pokemon.com/de/pokemon-folgen/pokemon-tv-staffeln/staffeln-' . $this->getInput('staffel');
	}

	public function getIcon() {
		return 'https://www.google.com/s2/favicons?domain=pokemon.com/';
	}
}
