<?php

declare(strict_types=1);
	class iTachDevice extends IPSModule
	{
		public function Create()
		{
			//Never delete this line!
			parent::Create();

			$this->ForceParent('{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}');

			$this->RegisterPropertyString('IPAddress', '');
			$this->RegisterPropertyString('Model', '');
			$this->RegisterPropertyString('Name', '');
		}

		public function Destroy()
		{
			//Never delete this line!
			parent::Destroy();
		}

		public function ApplyChanges()
		{
			//Never delete this line!
			parent::ApplyChanges();
		}

		public function ReceiveData($JSONString)
		{
			$data = json_decode($JSONString);
			IPS_LogMessage('Device RECV', utf8_decode($data->Buffer));
		}
	}