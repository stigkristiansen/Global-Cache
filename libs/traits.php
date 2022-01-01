<?PHP

declare(strict_types=1);

trait iTach {
    public function Destroy() {
		//Never delete this line!
		parent::Destroy();
	}

	public function ApplyChanges() {
		//Never delete this line!
		parent::ApplyChanges();
	}


	public function ReceiveData($JSONString)
	{
		$data = json_decode($JSONString);
		
		$this->SendDebug(__FUNCTION__, sprintf('Received data: %s', utf8_decode($data->Buffer)), 0);
	}
}