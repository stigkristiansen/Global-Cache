<?php

declare(strict_types=1);

require_once(__DIR__ . '/../libs/buffer.php');

class iTachDiscovery extends IPSModule {
	Use Buffer; 
	
	public function Create() {
		//Never delete this line!
		parent::Create();

		$this->ForceParent('{BAB408E0-0A0F-48C3-B14E-9FB2FA81F66A}');
		

		$this->RegisterTimer('SetIOConfig', 0, 'IPS_RequestAction(' . (string)$this->InstanceID . ', "SetIOConfig", 0);'); 
		$this->RegisterTimer('UpdateDeviceConfig', 0, 'IPS_RequestAction(' . (string)$this->InstanceID . ', "DeviceConfig", 0);'); 

		$this->SetBuffer('FormDevices', json_encode([]));
		$this->SetBuffer('MulticastDevices', json_encode([]));
		$this->SetBuffer('SearchInProgress', json_encode(false));
	}

	public function Destroy() {
		//Never delete this line!
		parent::Destroy();
	}

	public function ApplyChanges() {
		//Never delete this line!
		parent::ApplyChanges();

		$this->SetReceiveDataFilter('.*UUID=GlobalCache.*');

		$this->RegisterMessage(0, IPS_KERNELMESSAGE);

		if (IPS_GetKernelRunlevel() == KR_READY) {
			$this->Init();
		}
	}

	private function Init() {
		$msg = 'Initializing...';
		
		$this->LogMessage($msg, KL_NOTIFY);
		$this->SendDebug(__FUNCTION__, $msg, 0);

		$this->SetTimerInterval('SetIOConfig', 1000);
		$this->SetTimerInterval('UpdateDeviceConfig', 180000);
	}

	public function MessageSink($TimeStamp, $SenderID, $Message, $Data) {
		parent::MessageSink($TimeStamp, $SenderID, $Message, $Data);

		$this->SendDebug(__FUNCTION__, sprintf('Received a message: %d - %d - %d', $SenderID, $Message, $data[0]), 0);

		if ($Message == IPS_KERNELMESSAGE && $Data[0] == KR_READY) {
			$this->LogMessage('Detected "Kernel Ready"!', KL_NOTIFY);
			$this->Init();
		}
	}

	public function GetConfigurationForm() {
		$this->SendDebug(__FUNCTION__, 'Generating the form...', 0);
		$this->SendDebug(__FUNCTION__, sprintf('SearchInProgress is "%s"', json_decode($this->GetBuffer('SearchInProgress'))?'TRUE':'FALSE'), 0);
					
		$devices = json_decode($this->GetBuffer('FormDevices'));
	   
		if (!json_decode($this->GetBuffer('SearchInProgress'))) {
			$this->SendDebug(__FUNCTION__, 'Setting SearchInProgress to TRUE', 0);
			$this->SetBuffer('SearchInProgress', json_encode(true));
			
			$this->SendDebug(__FUNCTION__, 'Starting a timer to process the search in a new thread...', 0);
			$this->RegisterOnceTimer('LoadDevicesTimer', 'IPS_RequestAction(' . (string)$this->InstanceID . ', "Discover", 0);');
		}

		$form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
		$form['actions'][0]['visible'] = count($devices)==0;
		
		$this->SendDebug(__FUNCTION__, 'Adding cached devices to the form', 0);
		$form['actions'][1]['values'] = $devices;

		$this->SendDebug(__FUNCTION__, 'Finished generating the form', 0);

		return json_encode($form);
	}

	private function LoadDevices() {
		$this->SendDebug(__FUNCTION__, 'Updating Discovery form...', 0);

		$devices = $this->DiscoverGCDevices();
		$instances = $this->GetGCInstances();
		
		$this->SendDebug(__FUNCTION__, 'Setting SearchInProgress to FALSE', 0);
		$this->SetBuffer('SearchInProgress', json_encode(false));
		
		$values = [];
		
		// Add devices that are discovered
		if(count($devices)>0) {
			$this->SendDebug(__FUNCTION__, 'Adding discovered devices...', 0);
		} else {
			$this->SendDebug(__FUNCTION__, 'No devices discovered!', 0);
		}

		foreach ($devices as $name => $device) {
			$value = [
				'Name' => $name,
				'Model' => $device['Model'],
				'IPAddress' => $device['IPAddress'],
				'instanceID' => 0
			];

			$this->SendDebug(__FUNCTION__, sprintf('Added device with name "%s"', $name), 0);
			
			// Check if discovered device has an instance that is created earlier. If found, set InstanceID
			$instanceId = array_search($name, $instances);
			if ($instanceId !== false) {
				$this->SendDebug(__FUNCTION__, sprintf('The device (%s) already has an instance (%s). Setting InstanceId and changing the name to "%s"', $name, $instanceId, IPS_GetName($instanceId)), 0);
				unset($instances[$instanceId]); // Remove from list to avoid duplicates
				$value['instanceID'] = $instanceId;
				$value['Name'] = IPS_GetName($instanceId);
			} 
			
			$value['create'] = [
				[
					'moduleID'       => $this->GetModuleIdByModel($device['Model']),  
					'name'			 => $name,
					'configuration'	 => [
						'Model' 		=> $device['Model'],
						'Name'			=> $name
					]
				],
				[
					'moduleID'       => '{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}',  
					'configuration'	 => [
						'Open' 			=> true,
						'Host'		=> $device['IPAddress'],
						'Port'			=> 4998
					]
				]
			];
		
			$values[] = $value;
		}

		// Add devices that are not discovered, but created earlier
		if(count($instances)>0) {
			$this->SendDebug(__FUNCTION__, 'Adding instances that are not discovered...', 0);
		}
		foreach ($instances as $instanceId => $name) {
			$values[] = [
				'Name' 		 	=> IPS_GetName($instanceId), //json_decode(IPS_GetConfiguration($instanceId),true)['Name'],
				'Model'		 	=> json_decode(IPS_GetConfiguration($instanceId),true)['Model'],
				'IPAddress'	 	=> json_decode(IPS_GetConfiguration($instanceId),true)['IPAddress'],
				'instanceID' 	=> $instanceId
			];

			$this->SendDebug(__FUNCTION__, sprintf('Added instance "%s" with InstanceID "%s"', IPS_GetName($instanceId), $instanceId), 0);
		}

		$newDevices = json_encode($values);
		$this->SetBuffer('Devices', $newDevices);
					
		$this->UpdateFormField('Discovery', 'values', $newDevices);
		$this->UpdateFormField('SearchingInfo', 'visible', false);

		$this->SendDebug(__FUNCTION__, 'Updating Discovery form completed', 0);
	}

	privte function GetModuleIdByModel(string $Model) {
		switch(strtolower($Model)) {
			case 'itachwf2ir':
			case 'itachip2ir':
				return '{C507B0A6-C990-9CC5-8752-FCA481CE66DD}';
			case 'itachip2cc':
			case 'itachwf2cc':	
				return '{F505B016-C990-9CC5-8752-EDA48ECE66DF}';
			default:
				return '';
		}
	}

	private function DiscoverGCDevices() : array {
		$this->LogMessage('Discovering iTach devices...', KL_NOTIFY);

		$this->SendDebug(__FUNCTION__, 'Discovering iTach devices...', 0);

		$devices = [];
		if($this->Lock('MulticastDevices')) {
			$discoveredDevices = json_decode($this->GetBuffer('MulticastDevices'), true);
			$this->Unlock('MulticastDevices');
		} else {
			return $devices;
		}

		foreach($discoveredDevices as $device) {
			$ipAddress = substr($device['config-url'], 7);
			$devices[$device['uuid']] = ['Model' => $device['model'], 'IPAddress' => $ipAddress];
		}

		$this->SendDebug(__FUNCTION__, sprintf('Found %d iTach device(s)', count($devices)), 0);
		$this->SendDebug(__FUNCTION__, 'Finished discovering iTach devices', 0);

		return $devices;
	}

	private function GetGCInstances () : array {
		$instances = [];

		$this->SendDebug(__FUNCTION__, 'Searching for existing instances of iTach devices...', 0);

		$instanceIds = IPS_GetInstanceListByModuleID('{C507B0A6-C990-9CC5-8752-FCA481CE66DD}');
		
		foreach ($instanceIds as $instanceId) {
			$instances[$instanceId] = IPS_GetProperty($instanceId, 'Name');
		}

		$this->SendDebug(__FUNCTION__, sprintf('Found %d instance(s) of iTach devices', count($instances)), 0);
		$this->SendDebug(__FUNCTION__, 'Finished searching for iTach devices', 0);	

		return $instances;
	}


	public function ReceiveData($JSONString) {
		$data = json_decode($JSONString);
		$multicast = utf8_decode($data->Buffer);

		$this->SendDebug(__FUNCTION__, 'Received from parent: ' . $multicast, 0);

		$multicast = htmlspecialchars($multicast);

		$buffer = $this->FetchBufferRaw('multicast');

		if(strlen($buffer)>0) {
			$multicast = $buffer . $multicast;
		}

		$this->SendDebug(__FUNCTION__, 'Checking for complete multicast: ' . $multicast, 0);

		$pre = stripos($multicast, 'AMXB');
		$post = stripos($multicast, chr(13));

		if($pre!==false && $post!==false) {
			$this->UpdateBufferRaw('multicast', '');
			$this->SendDebug(__FUNCTION__, 'Complete multicast received', 0);
		} else {
			$this->UpdateBufferRaw('multicast', $multicast);
			$this->SendDebug(__FUNCTION__, 'Incomplete data received. Saving for later usage...', 0);
			return;
		}

		$multicast = substr($multicast, $pre+9, $post-$pre-9);

		$multicast = str_replace('&lt;-', ';', $multicast);
		$multicast = str_replace('&gt;', '', $multicast);
		$multicast = str_replace('&lt', '', $multicast);

		$values = explode(';', $multicast);

		$device = [];
		$max = count($values);
		for($i=0;$i<$max;$i++) {
			$value = explode('=', $values[$i]);
			$device[strtolower($value[0])] = $value[1];
		}

		$device['timestamp'] = time();

		$this->SendDebug(__FUNCTION__, 'Received multicast: ' . json_encode($device), 0);

		if($this->Lock('MulticastDevices')) {
			$devices = json_decode($this->GetBuffer('MulticastDevices'), true);
			if(array_key_exists($device['uuid'], $devices)) {
				$this->SendDebug(__FUNCTION__, 'Device is received earlier. Updating timestamp...', 0);
				$devices[$device['uuid']]['timestamp'] = time();
			} else {
				$this->SendDebug(__FUNCTION__, 'Adding new device to devices list', 0);
				$devices[$device['uuid']] = $device;
			}

			$this->SetBuffer('MulticastDevices', json_encode($devices));
			$this->Unlock('MulticastDevices');
		}
	}

	public function RequestAction($Ident, $Value) {
		switch (strtolower($Ident)) {
			case 'setioconfig':
				$this->SetIOConfig();
				break;
			case 'discover':
				$this->SendDebug(__FUNCTION__, 'Calling LoadDevices()...', 0);
				$this->LoadDevices();
				break;
			case 'deviceconfig':
				$this->SendDebug(__FUNCTION__, 'Calling DeviceConfig()...', 0);
				$this->DeviceConfig();
				break;
		}		
	}

	private function DeviceConfig() {
		$this->SendDebug(__FUNCTION__, 'Checking if IO-configuration has changed for any of the created devices...', 0);
		$devices = $this->DiscoverGCDevices();
		$instances = $this->GetGCInstances();

		foreach ($devices as $name => $device) {
			$instanceId = array_search($name, $instances);
			if ($instanceId !== false) {
				$ipsName = IPS_GetName($instanceId);
				
				$this->SendDebug(__FUNCTION__, sprintf('Found instance %s(Id: %d). Checking...',$ipsName, $instanceId), 0);

				$parentId = IPS_GetInstance($instanceId)['ConnectionID'];

				$host = IPS_GetProperty($parentId, 'Host');
				$port = IPS_GetProperty($parentId, 'Port');
				
				$currentHost = $device['IPAddress'];
		
				if($host!=$currentHost || $port != 4998) {
					$this->SendDebug(__FUNCTION__, sprintf('Configuration for instance %s has changed. Reconfiguring to %s:4998....',$ipsName, $currentHost), 0);
					IPS_SetProperty($parentId, 'Host', $currentHost);
					IPS_SetProperty($parentId, 'Port', 4998);
					IPS_SetProperty($parentId, "Open", true);
					IPS_ApplyChanges($parentId);
				} else {
					$this->SendDebug(__FUNCTION__, sprintf('Configuraton for instance %s is unchanged', $ipsName), 0);
				}
			}
		}		
	}

	private function SetIOConfig() {
		$this->SetTimerInterval('SetIOConfig', 0);

		$this->SendDebug(__FUNCTION__, 'Setting the configuration of the Multicast I/O instance...', 0);
		
		$parentId = IPS_GetInstance($this->InstanceID)['ConnectionID'];
		
		IPS_SetProperty($parentId, 'BindPort', 9131);
		IPS_SetProperty($parentId, 'EnableReuseAddress', true);
		IPS_SetProperty($parentId, 'MulticastIP', '239.255.250.250');
		IPS_SetProperty($parentId, "Open", true);
		IPS_ApplyChanges($parentId);
	}
}