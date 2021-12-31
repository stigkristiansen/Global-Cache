<?php

declare(strict_types=1);
class iTachDevice extends IPSModule {
	public function Create() {
		//Never delete this line!
		parent::Create();

		$this->ForceParent('{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}');

		$this->RegisterPropertyString('IPAddress', '');
		$this->RegisterPropertyString('Model', '');
		$this->RegisterPropertyString('Name', '');

		$this->RegisterTimer('SetIOConfig', 0, 'IPS_RequestAction(' . (string)$this->InstanceID . ', "SetIOConfig", 0);'); 
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

		//$this->SetTimerInterval('SetIOConfig', 1000);
	}

	public function RequestAction($Ident, $Value) {
		switch (strtolower($Ident)) {
			case 'setioconfig':
				$this->SetIOConfig();
				break;
			}
	}

	private function SetIOConfig() {
		$this->SendDebug(__FUNCTION__, 'Setting the configuration of the Socket Client I/O instance...', 0);

		$this->SetTimerInterval('SetIOConfig', 0);
		
		$parentId = IPS_GetInstance($this->InstanceID)['ConnectionID'];
		
		IPS_SetProperty($parentId, 'Host', $this->ReadPropertyString('IPAddress'));
		IPS_SetProperty($parentId, 'Port', 4998);
		IPS_SetProperty($parentId, "Open", true);
		IPS_ApplyChanges($parentId);
	}

	public function ReceiveData($JSONString)
	{
		$data = json_decode($JSONString);
		IPS_LogMessage('Device RECV', utf8_decode($data->Buffer));
	}

	public function SendIRCommand(string $Device, string $Command) {

	}
}