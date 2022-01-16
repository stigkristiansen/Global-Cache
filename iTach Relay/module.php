<?php

declare(strict_types=1);

require_once(__DIR__ . '/../libs/traits.php');

class iTachDeviceRelay extends IPSModule {
	use Messages;

	public function Create() {
		//Never delete this line!
		parent::Create();

		$this->ForceParent('{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}');

		$this->RegisterPropertyString('Model', '');
		$this->RegisterPropertyString('Name', '');
		$this->RegisterPropertyString('Relay', '1:1');

		$this->RegisterVariableBoolean('Relay1', 'Relay #1', '~Switch', 0);
		$this->EnableAction('Relay1');
		$this->RegisterVariableBoolean('Relay2', 'Relay #2', '~Switch', 1);
		$this->EnableAction('Relay2');
		$this->RegisterVariableBoolean('Relay3', 'Relay #3', '~Switch', 2);
		$this->EnableAction('Relay3');

		$this->RegisterTimer('RequestState', 0, 'IPS_RequestAction(' . (string)$this->InstanceID . ', "RequestState", 0);'); 
	}

	public function Destroy() {
		//Never delete this line!
		parent::Destroy();
	}

	public function ApplyChanges() {
		//Never delete this line!
		parent::ApplyChanges();
		
		$this->RegisterMessage(0, IPS_KERNELMESSAGE);

		if (IPS_GetKernelRunlevel() == KR_READY) {
            $this->Init();
        }
	}

	public function MessageSink($TimeStamp, $SenderID, $Message, $Data) {
        parent::MessageSink($TimeStamp, $SenderID, $Message, $Data);

		$this->SendDebug(__FUNCTION__, sprintf('Received a message: %d - %d - %d', $SenderID, $Message, $data[0]), 0);

		if ($Message == IPS_KERNELMESSAGE && $Data[0] == KR_READY) {
            $this->LogMessage('Detected "Kernel Ready"!', KL_NOTIFY);
			$this->Init();
		}
    }

	private function Init() {
		$msg = 'Initializing...';
		
		$this->LogMessage($msg, KL_NOTIFY);
		$this->SendDebug(__FUNCTION__, $msg, 0);

		$this->SetTimerInterval('RequestState', 1000);

		$this->SetBuffer('IncomingData', json_encode(''));
	}

	public function GetConfigurationForm() {
		
		$return = file_get_contents(__DIR__ . '/form.json');
	}

	public function RequestAction($Ident, $Value) {
		switch($Ident) {
			case 'RequestState':
				$this->SetTimerInterval('RequestState', 0);	
				$this->RequestState();
				break;
			case 'Relay1':
				$this->SendRelayCommandEx($Value, '1:1');
				break;
			case 'Relay2':
				$this->SendRelayCommandEx($Value, '1:2');
				break;
			case 'Relay3':
				$this->SendRelayCommandEx($Value, '1:3');
				break;
		}
	}
	
	private function HandleState(array $Msg) {
		$port = $Msg[1];
		$state = (int)$Msg[2];

		switch($port) {
			case '1:1':
				$this->SetValue('Relay1', $state==1?true:false);
				break;
			case '1:2':
				$this->SetValue('Relay2', $state==1?true:false);
				break;
			case '1:3':
				$this->SetValue('Relay3', $state==1?true:false);
				break;
			default: 
				$msg = sprintf('Invalid port: %s', $port);

				$this->LogMessage($msg, KL_ERROR);
				$this->SendDebug(__FUNCTION__, $msg, 0);
		}
	}

	public function SendRelayCommand(bool $State) {
		$this->SendRelayCommandEx($State, $this->ReadPropertyString("Relay"));
	}

	public function SendRelayCommandEx(bool $State, string $Relay) {
		$buffer = sprintf('setstate,%s,%d%c', $Relay, $State?1:0,13);
		
		try {
				$this->SendDataToParent(json_encode(Array("DataID" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}", "Buffer" => $buffer)));
				return true;
		} catch (Exeption $ex) {
				$msg = sprintf('Failed to send the state "%d" to %s. Error: %s', $State?1:0, $Relay, $ex->getMessage());
				
				$this->LogMessage($msg, KL_ERROR);
				$this->SendDebug(__FUNCTION__, $msg, 0);

				return false;
		}
	}

	public function GetRelayStatus() {
		$this->GetRelayStatusEx($this->ReadPropertyString("Relay"));
	}

	public function GetRelayStatusEx(string $Relay) {
		$buffer = sprintf('getstate,%s,%c%c', $Relay, 13,10);
		
		try {
				$this->SendDataToParent(json_encode(Array("DataID" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}", "Buffer" => $buffer)));
				return true;
		} catch (Exeption $ex) {
				$msg = sprintf('Failed to send the state request to %s. Error: %s', $Relay, $ex->getMessage());
				
				$this->LogMessage($msg, KL_ERROR);
				$this->SendDebug(__FUNCTION__, $msg, 0);

				return false;
		}
	}

	private function RequestState(string $Relay='') {
		if(strlen($Relay)==0) {
			$this->GetRelayStatusEx('1:1');
			$this->GetRelayStatusEx('1:2');
			$this->GetRelayStatusEx('1:3');
		} else {
			$this->GetRelayStatusEx($Relay);
		}
	}
}