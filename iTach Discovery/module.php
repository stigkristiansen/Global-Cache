<?php

declare(strict_types=1);
	class iTachDiscovery extends IPSModule
	{
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
			$this->SendDebug(__FUNCTION__, 'Received from parent: ' . utf8_decode($data->Buffer), 0);
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