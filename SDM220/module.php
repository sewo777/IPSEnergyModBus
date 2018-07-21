<?
class SDM220 extends IPSModule
{
    public function __construct($InstanceID)
    {
        parent::__construct($InstanceID);
    }
    public function Create()
    {
        parent::Create();
        $this->ConnectParent("{A5F663AB-C400-4FE5-B207-4D67CC030564}");
        $this->RegisterPropertyInteger("Interval", 0);
        $this->RegisterTimer("UpdateTimer", 0, "SDM220_RequestRead(\$_IPS['TARGET']);");
    }
    public function ApplyChanges()
    {
        parent::ApplyChanges();
        $this->RegisterVariableFloat("Volt", "Volt", "Volt", 1);
        $this->RegisterVariableFloat("Ampere", "Ampere", "Ampere.16", 2);
        $this->RegisterVariableFloat("Watt", "Watt", "Watt.14490", 3);
        $this->RegisterVariableFloat("Frequenz", "Frequenz", "Hertz.50", 4);
        $this->RegisterVariableFloat("Total", "Total kWh", "Electricity", 5);
        
        if ($this->ReadPropertyInteger("Interval") > 0)
            $this->SetTimerInterval("UpdateTimer", $this->ReadPropertyInteger("Interval"));
        else
            $this->SetTimerInterval("UpdateTimer", 0);
    }
    public function RequestRead()
    {
        $Gateway = IPS_GetInstance($this->InstanceID)['ConnectionID'];
        if ($Gateway == 0)
            return false;
        $IO = IPS_GetInstance($Gateway)['ConnectionID'];
        if ($IO == 0)
            return false;
        if (!$this->lock($IO))
            return false;
            
            
	//Spannung
        $Volt = $this->SendDataToParent(json_encode(Array("DataID" => "{E310B701-4AE7-458E-B618-EC13A1A6F6A8}", "Function" => 4, "Address" => 0, "Quantity" => 2, "Data" => "")));
        if ($Volt === false)
        {
            $this->unlock($IO);
            return false;
        }
        $Volt = unpack("f", strrev(substr($Volt, 2)))[1];
        $this->SendDebug('Volt', $Volt, 0);
        SetValue($this->GetIDForIdent("Volt"), $Volt);
        
  //Strom
        $ampere = $this->SendDataToParent(json_encode(Array("DataID" => "{E310B701-4AE7-458E-B618-EC13A1A6F6A8}", "Function" => 4, "Address" => 6, "Quantity" => 2, "Data" => "")));
        if ($ampere === false)
        {
            $this->unlock($IO);
            return false;
        }
        $ampere = unpack("f", strrev(substr($ampere, 2)))[1];
        $this->SendDebug('Strom', $ampere, 0);
        SetValue($this->GetIDForIdent("Strom"), $ampere);
        
        
  //Watt
        $watt = $this->SendDataToParent(json_encode(Array("DataID" => "{E310B701-4AE7-458E-B618-EC13A1A6F6A8}", "Function" => 4, "Address" => 12, "Quantity" => 2, "Data" => "")));
        if ($watt === false)
        {
            $this->unlock($IO);
            return false;
        }
        $watt = unpack("f", strrev(substr($watt, 2)))[1];
        $this->SendDebug('Strom', $strom, 0);
        SetValue($this->GetIDForIdent("Watt"), $watt);
        
        
  //Frequenz
        $frequenz = $this->SendDataToParent(json_encode(Array("DataID" => "{E310B701-4AE7-458E-B618-EC13A1A6F6A8}", "Function" => 4, "Address" => 70, "Quantity" => 2, "Data" => "")));
        if ($frequenz === false)
        {
            $this->unlock($IO);
            return false;
        }
        $frequenz = unpack("f", strrev(substr($frequenz, 2)))[1];
        $this->SendDebug('Frequenz', $frequenz, 0);
        SetValue($this->GetIDForIdent("Frequenz"), $frequenz);
        
	//Verbrauch
        
          $total= $this->SendDataToParent(json_encode(Array("DataID" => "{E310B701-4AE7-458E-B618-EC13A1A6F6A8}", "Function" => 4, "Address" => 72, "Quantity" => 2, "Data" => "")));   
        if ($total === false)   
        {   
            $this->unlock($IO);   
            return false;   
        }  
        $total = unpack("f", strrev(substr($total, 2)))[1];
        $this->SendDebug('Total', $total, 0);
        SetValue($this->GetIDForIdent("Total"), $total);
      
        IPS_Sleep(333);
        $this->unlock($IO);
        return true;
    }
    /**
     * Versucht eine Semaphore zu setzen und wiederholt dies bei Misserfolg bis zu 100 mal.
     * @param string $ident Ein String der den Lock bezeichnet.
     * @return boolean TRUE bei Erfolg, FALSE bei Misserfolg.
     */
    private function lock($ident)
    {
        for ($i = 0; $i < 100; $i++)
        {
            if (IPS_SemaphoreEnter('ModBus' . '.' . (string) $ident, 1))
            {
                return true;
            }
            else
            {
                IPS_Sleep(5);
            }
        }
        return false;
    }
    /**
     * Löscht eine Semaphore.
     * @param string $ident Ein String der den Lock bezeichnet.
     */
    private function unlock($ident)
    {
        IPS_SemaphoreLeave('ModBus' . '.' . (string) $ident);
    }
}
?>
