<?php
declare(strict_types=1);

function get(string $url, array $config = []): array
{
	$defaultConfig = [
		'useragent'         => 'thirdplace http client',
		'connect_timeout'   => 5,
		'timeout'           => 5,
		'headers'           => [],
	];
	$config = array_merge($defaultConfig, $config);

	$headers = [];
	foreach ($config['headers'] as $name => $value) {
		$headers[] = sprintf('%s: %s', $name, $value);
	}

	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,    true);
	curl_setopt($ch, CURLOPT_USERAGENT,         $config['useragent']);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,    $config['connect_timeout']);
	curl_setopt($ch, CURLOPT_TIMEOUT,           $config['timeout']);
	curl_setopt($ch, CURLOPT_HTTPHEADER,        $headers);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $config['body']);
	$result = curl_exec($ch);

	if ($result === false) {
		throw new \RuntimeException(
			sprintf('Curl failed for "%s": %s (%s)', $url, curl_error($ch), curl_errno($ch))
		);
	}

	$code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

	return [
		'code'  => $code,
		'body'  => $result,
	];
}

function createBridges(array $bridgeNames = []) {
	global $bridgeFactory;

	$bridges = [];
	foreach ($bridgeNames as $bridgeName) {
		$bridges[] = [
			'name'  => $bridgeName,
			'bridge' => $bridgeFactory->create($bridgeName),
			'contexts' => [],
			'inputs' => [],
		];
	}
	return $bridges;
}

function populateBridgesWithContexts(array $bridges): array
{
	foreach ($bridges as $bridgeKey => $bridge) {
		$contexts = $bridge['bridge']->getParameters();

		// this is a bridge without any context
		if ($contexts === []) {
			$bridges[$bridgeKey]['contexts'][] = [];
		}

		foreach ($contexts as $contextKey => $context) {
			// skip global context
			if (!is_numeric($contextKey) && $contextKey === 'global')
				continue;

			// merge the global context into the current context
			if (array_key_exists('global', $contexts))
				$context = array_merge($context, $contexts['global']);

			foreach ($context as $inputKey => $input) {
				if (!isset($input['exampleValue']))
					$input['exampleValue'] = '';
				if (!isset($input['defaultValue']))
					$input['defaultValue'] = '';

				if ($input['exampleValue'] && !$input['defaultValue']) {
					$input['defaultValue'] = $input['exampleValue'];
				}

				if (!isset($input['type']) || $input['type'] === 'text') {
					$input['type'] = 'text';
					$input['defaultValue'] = $input['defaultValue'] ?: ''; // dont think this is necessary
				}
				if ($input['type'] === 'number') {
				}
				if ($input['type'] === 'list') {
					// optgroup?
					if (is_array(array_values($input['values'])[0])) {
						foreach ($input['values'] as $v) {
							$input['defaultValue'] = array_shift($v);
							break;
						}
					} else {
						if (isset($input['defaultValue'])) {
							if (isset($input['values'][$input['defaultValue']])) {
								$input['defaultValue'] = $input['values'][$input['defaultValue']];
							}
							// grab the first value in the list
							$input['defaultValue'] = array_values($input['values'])[0];
						} else {
							// grab the first value in the list
							$input['defaultValue'] = array_values($input['values'])[0];
						}
					}
				}
				if ($input['type'] === 'checkbox') {
				}

				$context[$inputKey] = $input;
			}
			$bridges[$bridgeKey]['contexts'][$contextKey] = $context;
		}
	}
	return $bridges;
}

function populateBridgesWithInputs(array $bridges)
{
	foreach ($bridges as $bridgeKey => $bridge) {
		foreach ($bridge['contexts'] as $context) {
			$inputs = [];
			foreach ($context as $inputKey => $input) {
				if (in_array($inputKey, ['limit', 'max', 'n', 'postcount', 'pages', 'item_limit'])) {
					$inputs[$inputKey] = 1;
				} elseif ($inputKey === 'score') {
					// Manual fix for Reddit
					$inputs['score'] = 1;
				} else {
					$inputs[$inputKey] = $input['defaultValue'];
				}
			}
			$bridges[$bridgeKey]['inputs'][] = $inputs;
		}
	}
	return $bridges;
}

function logRecord(array $record)
{
	$eventsLog = __DIR__ . '/event.log';
	if (!is_file($eventsLog)) {
		file_put_contents($eventsLog, '[]');
	}
	$events = json_decode(file_get_contents($eventsLog), true);
	$events['events'][] = $record;
	file_put_contents($eventsLog, json_encode($events, JSON_PRETTY_PRINT));

	if ($record['status'] === 'FAIL') {
		print_r($record);
//		$json = [
//			'title' => $record['name'] . ' FAIL',
//			'body' => "```\n" . json_encode($record, JSON_PRETTY_PRINT) . "\n```\n",
//		];
//		$res = get('https://api.github.com/repos/dvikan/rss-bridge/issues', [
//			'headers' => [
//				'Authorization' => '',
//			],
//			'body' => json_encode($json, JSON_PRETTY_PRINT)
//		]);
//		print_r($res);
	} else {
		print '.';
	}
}

function main($argc, $argv): void
{
	global $bridgeFactory;

	require __DIR__ . '/../lib/rssbridge.php';

	ini_set('max_execution_time', '600'); // 10m

	set_error_handler(function ($code, $message, $file, $line) {
		//print '.';
		//print "$message\n";
		//throw new \ErrorException($message, 0, $code, $file, $line);
	});

	Configuration::loadConfiguration();

	umask(0);

	define(
		'USER_AGENT',
		sprintf(
			'Mozilla/5.0 (X11; Linux x86_64; rv:72.0) Gecko/20100101 Firefox/72.0(rss-bridge/%s;+%s)',
			Configuration::$VERSION,
			REPOSITORY
		)
	);

	ini_set('user_agent', USER_AGENT);

	$bridgeFactory = new BridgeFactory();
	$bridgeFactory->setWorkingDir(PATH_LIB_BRIDGES);

	$allBridges = $bridgeFactory->getBridgeNames();
	$failingBridges = require __DIR__ . '/failing-bridges.php';
	$excludedBridges = [
		'YouTubeCommunityTab', // broken, needs geoip solution
		'Spotify', // needs credentials
	    'FurAffinityUser', // requires credentials (username and password (and later cookies in PR))
		'Facebook', // flaky
		'Instagram', // works but flaky
		'FB2', // Required parameter(s) missing
//		'Anidex', // flaky (TLS)
		'Twitter', // flaky, 0 items UnexpectedResponseException: Unexpected response from upstream
		'IKWYD', // works when supplied with a useful ip address
		'FolhaDeSaoPaulo', // works but slow 50s execution time. also curl timeout
		'TwitScoop', // curl timed out after 15s
		'Ello', // works but cloudflare bot response, CloudflareChallengeException: The server responded with a Cloudflare challenge
//		'SensCritique', // works but flaky??
		'StockFilings', // works, but flaky??  UnexpectedResponseException: Unexpected response from upstream
		'InternetArchive', //works but slow, 15s+
//		'NextInpact', // works but 27s execution time
//		'DeveloppezDotCom', // works but 10s execution time
//		'BandcampDaily', // works but 13s execution time
//		'ASRockNews', // works but 9s execution time
//		'BleepingComputer', // works but 10s execution time
//		'DauphineLibere', // works but 7s execution time
//		'GBAtemp', // works but 12s execution time
//		'Listverse', // works but 11s execution time
//		'DarkReading', // works but 16s execution time
//		'EsquerdaNet', // works but 17 execution time
//		'NyaaTorrents', // works but 10s execution time
//		'Crewbay', // works but 9s execution time
//		'HardwareInfo', // works but 8s execution time
//		'NeuviemeArt', // works but 8s execution time
//		'Pixiv', // works but 12s exec time
//		'WeLiveSecurity', // works but 12s/19s exec time
//		'SplCenter', // works but 8s/11s exec time
//		'Openly', // works but 14s execution time
//		'IGN', // works but 8s exec time
//		'MallTv', // works but 10s exec time
//		'Nextgov', // works but 7s/9s exec time
//		'NYT', // works but 8s exec time
//		'Urlebird', // works but 7s/10s
//		'SteamCommunity', // works but 5s/7s exec time
//		'Variety', // works but 6s/8s exec time
//		'PcGamer', // works but 15s execution time
//		'GQMagazine', // works but 28s execution time
//		'FindACrew', // works but 25s execution time
		'Wired', // works but 47s execution time, flaky?? UnexpectedResponseException: Unexpected response from upstream
//		'ZDNet', // works but 25s execution time
//		'CarThrottle', // works but 7s exec time
//		'ComicsKingdom', // works but 6s exec time
//		'CourrierInternational', // works but 5s exec time
//		'FreeCodeCamp', // works but 5s/6s
//		'FuturaSciences', // works but 6s
//		'Gizmodo', // works but 7s
//		'GoComics', // works but 5s exec time
//		'Hashnode', // works but 5s
//		'LeMondeInformatique', // works but 5s
//		'RaceDepartment', // works but 5s/7s
//		'Reuters', // works but 4s/7s
//		'StanfordSIRbookreview', // works but 5s
//		'Unogs', // works but 15s!
//		'Vice', // works but 5s
//		'WallmineNews', // works but 5s
//		'AutoJM', // works but 4s
//		'Basta', // works but 4s
	];
	//$bridgesToBeTested = ['Nordbayern'];
	//$bridgesToBeTested = $allBridges;
	$bridgesToBeTested = array_diff($allBridges, $failingBridges, $excludedBridges);
	//$bridgesToBeTested = $failingBridges;
	if ($argc === 2) {
		$bridgesToBeTested = [$argv[1]];
	}
	$bridges = createBridges($bridgesToBeTested);
	$bridges = populateBridgesWithContexts($bridges);
	$bridges = populateBridgesWithInputs($bridges);

	foreach ($bridges as $bridge) {
		$bridge['bridge']->loadConfiguration();

		foreach ($bridge['inputs'] as $input) {
			$record = [
				'name'				=> $bridge['name'],
				'created_at'		=> (new \DateTimeImmutable())->getTimestamp(),
				'created_at_human'	=> (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
				'status'			=> null, // OK, FAIL
				'execution_time'	=> null, // s
				'error'				=> null, // error string
				'context'			=> $input,
			];

			try {
				$bridge['bridge']->setDatas($input);

				$startTime = microtime(true);
				$bridge['bridge']->collectData();
				$record['execution_time'] = microtime(true) - $startTime;
			} catch (\Throwable $e) {
				$record['status'] = 'FAIL';
				$record['error'] = sprintf('%s: %s', get_class($e), $e->getMessage());
				logRecord($record);
				break;
			}

			$items = $bridge['bridge']->getItems();

			$record['item_count'] = count($items);

			if ($record['item_count'] === 0) {
				$record['status'] = 'FAIL';
				$record['error'] = 'zero items';
			} else {
				$record['status'] = 'OK';
			}
			logRecord($record);
			break; // test only the first context
		}
	}
}

main($argc, $argv);
