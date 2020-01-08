<?php
/*
 * Load configuration.
 */
require __DIR__ . '/config.php';

/*
 * Autoload composer and load GuzzleHttp client.
 */
require __DIR__ . '/vendor/autoload.php';
use GuzzleHttp\Client;
use PhpSlugger\PhpSlugger;

/*
 * Initialzie connection to Netbox
 */
$netbox = new GuzzleHttp\Client(['base_uri' => $netbox_url, 'headers' => ['accept' => 'application/json', 'Content-Type' => 'application/json', 'authorization' => 'Token ' . $netbox_token]]);

/*
 * Fetch all circuits from Netbox and decode json.
 */
$circuits = $netbox->request('GET', '/api/circuits/circuits/', ['query' => ['limit' => 999]]);
if(!$circuits)
	exit('Unable to fetcgh circuits from Netbox!');
$circuits = json_decode($circuits->getBody(), true);

/*
 * Iterate trough circuits.
 */
foreach($circuits['results'] as $circuit){
	/*
	 * Fetch and decoode terminations of circuit.
	 */
	$circuit_terminations = $netbox->request('GET', '/api/circuits/circuit-terminations/', ['query' => ['circuit_id' => $circuit['id']]]);
	if(!$circuit_terminations)
		exit('Unable to fetch circuit terminations.');
	$circuit_terminations = json_decode($circuit_terminations->getBody(), true);

	/*
	 * Iterate through terminations to find A and Z end.
	 */
	foreach($circuit_terminations['results'] as $termination){
		$circuit['termination'][$termination['term_side']] = $termination;
	}

	/*
	 * Export to stdout.
	 */
	$row = Array(
		$circuit['cid'],
		$circuit['status']['label'],
		$circuit['provider']['name'],
		$circuit['type']['name'],
		$circuit['tenant']['name'],
		$circuit['description'],
		$circuit['custom_fields']['ext_contract'],
	);


	/*
	 * Iterate through A and Z end.
	 */
	foreach(Array('A', 'Z') as $end){
		$path = Array();
		/*
		 * Extend path if is connected.
		 */
		if(isset($circuit['termination'][$end])){
			$path[] = $circuit['termination'][$end]['site']['name'];

			/*
			 * Check if patchpanel info does exsist.
			 */
			if(strlen(trim($circuit['termination'][$end]['pp_info'])) > 0)
				$path[] = trim($circuit['termination'][$end]['pp_info']);

			/*
			 * If terminated to a device add device name and port to the path.
			 */
			if(isset($circuit['termination'][$end]['connected_endpoint'])){
				$path[] = $circuit['termination'][$end]['connected_endpoint']['device']['name'];
				$path[] = $circuit['termination'][$end]['connected_endpoint']['name'];
			}
		}
		$row[] = implode(' / ', $path);
	}

	echo implode(',', $row) . PHP_EOL;
}
