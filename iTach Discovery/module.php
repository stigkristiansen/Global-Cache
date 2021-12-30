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
	}

	public function MessageSink($TimeStamp, $SenderID, $Message, $Data) {
		parent::MessageSink($TimeStamp, $SenderID, $Message, $Data);

		$this->SendDebug(__FUNCTION__, sprintf('Received a message: %d - %d - %d', $SenderID, $Message, $data[0]), 0);

		if ($Message == IPS_KERNELMESSAGE && $Data[0] == KR_READY) {
			$this->LogMessage('Detected "Kernel Ready"!', KL_NOTIFY);
			$this->Init();
		}
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

		$pre = stripos($multicast, 'amxb');
		$post = stripos($multicast, '&gt;&lt;cr&gt;');

		if($pre!==false && $post!==false) {
			$this->UpdateBufferRaw('multicast', '');
		} else {
			$this->UdateBufferRaw('multicast', $multicast);
			return;
		}

		$multicast = substr($multicast, $pre, $post-$pre+1);

		$multicast = str_replace('&lt;-', ';', $multicast);
		$multicast = str_replace('&gt;', '', $multicast);
		$multicast = str_replace('&lt', '', $multicast);

		$this->SendDebug(__FUNCTION__, 'Ready for handling: ' . $multicast, 0);

		return;

		$values = explode(';', $multicast);

		$device = [];
		$max = count($values)-1;
		for($i=1;$i<$max;$i++) {
			$value = explode('=', $values[$i]);
			$device[$value[0]] = $value[1];
		}

		$device['timestamp'] = time();


	}

	public function RequestAction($Ident, $Value) {
		switch (strtolower($Ident)) {
			case 'setioconfig':
				$this->SetIOConfig();
		}		
	}

	private function SetIOConfig() {
		$this->SendDebug(__FUNCTION__, 'Setting the configuration of the Multicast I/O instance...', 0);

		$this->SetTimerInterval('SetIOConfig', 0);
		
		$parentId = IPS_GetInstance($this->InstanceID)['ConnectionID'];
		
		IPS_SetProperty($parentId, 'BindPort', 9131);
		IPS_SetProperty($parentId, 'EnableReuseAddress', true);
		IPS_SetProperty($parentId, 'MulticastIP', '239.255.250.250');
		IPS_SetProperty($parentId, "Open", true);
		IPS_ApplyChanges($parentId);
	}
}