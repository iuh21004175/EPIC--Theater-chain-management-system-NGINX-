<?php
    namespace App\Services;

    use App\Models\ServerChamCong;

    class Sc_ServerChamCong
    {
        public function getServerChamCong(){
            $serverChamCong = ServerChamCong::where('id_rapphim', $_SESSION['UserInternal']['ID_RapPhim'] ?? null)->first();
            if($serverChamCong){
                return $serverChamCong;
            }else{
                return null;
            }
        }
        public function updateServerChamCong(){
            $idRapPhim = $_SESSION['UserInternal']['ID_RapPhim'];
            $wifi = $_POST['wifiIp'] ?? '';
            $wifiTen = $_POST['wifiTen'] ?? '';
            $serverPort = $_POST['serverPort'] ?? '';
            $server= ServerChamCong::where('id_rapphim', $idRapPhim)->first();
            if($server){
                $server->wifi_ip = $wifi;
                $server->wifi_ten = $wifiTen;
                $server->server_port = $serverPort;
                $server->save();
            }
            else{
                ServerChamCong::create([
                    'id_rapphim' => $idRapPhim,
                    'wifi_ip' => $wifi,
                    'wifi_ten' => $wifiTen,
                    'server_port' => $serverPort
                ]);
            }
        }
    }
?>