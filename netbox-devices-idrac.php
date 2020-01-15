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
 * Fetch all devices from Netbox and decode json.
 */
$devices = $netbox->request('GET', '/api/dcim/devices/', ['query' => ['limit' => 999, 'manufacturer_id' => 3]]);
if(!$devices)
	exit('Unable to fetcgh circuits from Netbox!');
$devices = json_decode($devices->getBody(), true);

/*
 * Iterate trough devices.
 */
foreach($devices['results'] as $device){
	/*
	 * Fetch interfaces from device.
	 */
	$interfaces = $netbox->request('GET', '/api/dcim/interfaces/', ['query' => ['limit' => 999, 'device_id' => $device['id']]]);
	$interfaces = json_decode($interfaces->getBody(), true);

	/*
	 * Iterate through interfaces of device.
	 */
	foreach($interfaces['results'] as $interface){
		/*
		 * Output only if iDRAC interface exists.
		 */
		if($interface['name'] == 'iDRAC'){
			/*
			 * Output first device information.
			 */
			echo $device['name'] . PHP_EOL;
			echo '   ' . $interface['name'] . PHP_EOL;

			/*
			 * Fetch IPs for interface and decode JSON.
			 */
			$ips = $netbox->request('GET', '/api/ipam/ip-addresses/', ['query' => ['limit' => 999, 'device_id' => $device['id'], 'interface_id' => $interface['id']]]);
			$ips = json_decode($ips->getBody(), true);

			/*
			 * Iterate through IPs.
			 */
			foreach($ips['results'] as $ip){
				echo '      ' . $ip['address'] . PHP_EOL;
			}
		}
	}
}
