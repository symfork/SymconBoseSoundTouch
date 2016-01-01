<?
/*

SymconBoseSoundTouch

Bose SoundTouch Modul for IP-Symcon

filename:       bose_soundtouch_api.php
description:    Bose SoundTouch API
version         1.0.0
date:           12.12.2015
publisher:      Copyright (c) 2015, 2016 Ulrich Bittner
license:        CC BY-NC 4.0 Creative Commons Attribution-NonCommercial 4.0 International License
                http://creativecommons.org/licenses/by-nc/4.0/
environment:    IP-Symcon 4.0 (beta) on RPi

changelog:      version 1.0.0 12.12.2015 initialscript

todo:           (none)

github:         https://github.com/ubittner/SymconBoseSoundTouch.git
symcon forum:   https://www.symcon.de/forum/threads/29922-Bose-SoundTouch-Modul


get funvtions:  getDevicePresetsAPI
                getDeviceNowPlayingAPI
                getDeviceVolumeAPI

post functions: powerDeviceAPI
                setDeviceRadioStationAPI    ($location)
                setDeviceVolumeAPI          ($volume)
                setDeviceZoneAPI            ($zonemasterip,$zonemasterid,$zonememberip,$zonememberid)
                removeDeviceZoneSlaveAPI    ($zonemasterid,$zonememberip,$zonememberid)

*/

// class definition
class BoseSoundTouchAPI 
{
    private $deviceip = "";
    public function __construct($deviceip) {
        $this->deviceip = $deviceip;
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////
    // get functions
    /////////////////////////////////////////////////////////////////////////////////////////////////////////
    
    // get device presets
    public function getDevicePresetsAPI() 
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://".$this->deviceip.":8090/presets",
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1));
        $result = curl_exec($curl);
        if (!curl_exec($curl))  die('Error: "' . curl_error($curl) . '" - Code: ' . curl_errno($curl));
        $xmldata = new SimpleXMLElement ($result);
        // return results
        return $xmldata;
    }


    // get device now playing
    public function getDeviceNowPlayingAPI() 
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://".$this->deviceip.":8090/now_playing",
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1));
        $result = curl_exec($curl);
        $xmldata = new SimpleXMLElement ($result);
        // get device id
        $deviceid = $xmldata['deviceID'];
        // get device mode
        $devicemode = $xmldata->ContentItem["source"];
        $devicepower = true;
        switch ($devicemode) {
            case "INVALID_SOURCE":
                $devicemode = "Invalid Source";
                $devicepower = false;
            break;
            case "SLAVE_SOURCE":
                $devicemode = "Slave Source";
            break;
            case "INTERNET_RADIO":
                $devicemode = "Internet Radio";
            break;
            case "PANDORA":
                $devicemode = "Pandora";
            break;
            case "AIRPLAY":
                $devicemode = "Airplay";
            break;
            case "STORED_MUSIC":
                $devicemode = "Stored Music";
            break;
            case "AUX":
                $devicemode = "Aux";
            break;
            case "OFF_SOURCE":
                $devicemode = "Off Source";
                $devicepower = false;
            break;
            case "CURRATED_RADIO":
                $devicemode = "Currated Radio";
                $devicepower = false;
            break;
            case "STANDBY":
                $devicemode = "Standby";
                $devicepower = false;
            break;
            case "DEEZER":
                $devicemode = "Deezer";
            break;
            case "SPOTIFY":
                $devicemode = "Spotify";
            break;
            case "IHEART":
                $devicemode = "Iheart";
            break;
            case "BLUETOOTH":
                $devicemode = "Bluetooth";
            break;
            default:
                $devicemode = "";
                $devicepower == false;
        }
        // get device state
        $devicestate = utf8_decode($xmldata->playStatus);
        switch ($devicestate) {
            case "PLAY_STATE":
                $devicestate = "Play";
            break;
            case "PAUSE_STATE":
                $devicestate = "Pause";
            break;
            case "STOP_STATE":
                $devicestate = "Stop";
            break;
            case "BUFFERING_STATE":
                $devicestate = "Reload";
            break;  
            case "INVALID_PLAY_STATUS":
                $devicestate = "Invalid Play Status";
            break;
            case "LOCAL":
                $devicestate = "Local";
            break;
            default:
                $devicestate = "";
        }
        // get now playing
        $nowplaying = utf8_decode($xmldata->stationName);
        // get description
        $description = utf8_decode($xmldata->description);
        // get logo
        $logo = utf8_decode($xmldata->art);
        if ($logo <> "") {
            $logourl = (string)"<center><embed src=".$logo."></embed></center>";
        }
        else {
            $logourl = "";
        }
        // return results       
        return array(   "deviceid"      => $deviceid,
                        "devicemode"    => $devicemode,
                        "devicepower"   => $devicepower,
                        "devicestate"   => $devicestate, 
                        "nowplaying"    => $nowplaying,
                        "description"   => $description,
                        "logourl"       => $logourl);
    }


    // get device volume
    public function getDeviceVolumeAPI()
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://".$this->deviceip.":8090/volume",
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1));
        $result = curl_exec($curl);
        $xmldata = new SimpleXMLElement($result);
        $targetvolume = (integer)$xmldata->targetvolume;
        $actualvolume = (integer)$xmldata->actualvolume;
        $mutedvolume = (integer)$xmldata->muteenabled;
        // return results
        return array(   "targetvolume"  => $targetvolume,
                        "actualvolume"  => $actualvolume,
                        "mutedvolume"   => $mutedvolume);
    }


    // get device zone
    public function getDeviceZoneAPI()
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://".$this->deviceip.":8090/getZone",
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1));
        $result = curl_exec($curl);
        $xmldata = new SimpleXMLElement($result);
        $zonemasterid = $xmldata["master"];
        $zonememberid = $xmldata->member;
        // return results
        return array(   "zonemasterid"  => $zonemasterid,
                        "zonememberid"  => $zonememberid);
    }


    /////////////////////////////////////////////////////////////////////////////////////////////////////////
    // post functions
    /////////////////////////////////////////////////////////////////////////////////////////////////////////
    
    // power device
    public function powerDeviceAPI()
    {
        $xmldata1 = "<key state=press sender=Gabbo>POWER</key>";
        $xmldata2 = "<key state=release sender=Gabbo>POWER</key>";
        for ($i=1 ; $i < 3 ; $i++) {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "http://".$this->deviceip.":8090/key",
                CURLOPT_HEADER => 0,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => ${"xmldata".$i},
                CURLOPT_HTTPHEADER => array("Content-type: text/xml")));
            $result = curl_exec($curl);
            curl_close($curl);
        }
    }

    // set device play / pause
    

    public function setDevicePlayPauseAPI()
    {
        $xmldata1 = "<key state=press sender=Gabbo>PLAY_PAUSE</key>";
        $xmldata2 = "<key state=release sender=Gabbo>PLAY_PAUSE</key>";
        for ($i=1 ; $i < 3 ; $i++) {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "http://".$this->deviceip.":8090/key",
                CURLOPT_HEADER => 0,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => ${"xmldata".$i},
                CURLOPT_HTTPHEADER => array("Content-type: text/xml")));
            $result = curl_exec($curl);
            curl_close($curl);
        }
    }


    // set device radio station
    public function setDeviceRadioStationAPI($location)
    {
        $xmldata = "<ContentItem source=INTERNET_RADIO location=".$location."></ContentItem>";
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://".$this->deviceip.":8090/select",
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $xmldata,
            CURLOPT_HTTPHEADER => array("Content-type: text/xml")));
        $result = curl_exec($curl);
        curl_close($curl);
    }


    // set device volume
    public function setDeviceVolumeAPI($volume)
    {
        $xmldata = "<volume>".$volume."</volume>";
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://".$this->deviceip.":8090/volume",
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $xmldata,
            CURLOPT_HTTPHEADER => array("Content-type: text/xml")));
        $result = curl_exec($curl);
        curl_close($curl);
    }


    // set device zone
    public function setDeviceZoneAPI($zonemasterip,$zonemasterid,$zonememberip,$zonememberid)
    {
        $xmldata = "<zone master=".$zonemasterid."><member ipaddress=".$zonemasterip.">".$zonemasterid."</member><member ipaddress=".$zonememberip.">".$zonememberid."</member></zone>";
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => "http://".$this->deviceip.":8090/setZone",
        CURLOPT_HEADER => 0,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => $xmldata,
        CURLOPT_HTTPHEADER => array("Content-type: text/xml")));
        $request = curl_exec($curl);
        curl_close($curl);
    }


    // remove device zone slave
    public function removeDeviceZoneSlaveAPI($zonemasterid,$zonememberip,$zonememberid)
    {
        $xmldata = "<zone master=".$zonemasterid."><member ipaddress=".$zonememberip.">".$zonememberid."</member></zone>";
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://".$this->deviceip.":8090/removeZoneSlave",
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $xmldata,
            CURLOPT_HTTPHEADER => array("Content-type: text/xml")));
        $request = curl_exec($curl);
        curl_close($curl);
    }
} 
?>