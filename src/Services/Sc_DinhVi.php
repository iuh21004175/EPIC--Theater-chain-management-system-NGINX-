<?php
    namespace App\Services;

    use App\Models\DinhVi;

    class Sc_DinhVi
    {
        public function getDinhVi(){
            $dinhVi = DinhVi::where('id_rapphim', $_SESSION['UserInternal']['ID_RapPhim'] ?? null)->first();
            if($dinhVi){
                return $dinhVi;
            }else{
                return null;
            }
        }
        public function updateDinhVi(){
            $idRapPhim = $_SESSION['UserInternal']['ID_RapPhim'];
            $wifi = $_POST['wifiIp'] ?? '';
            $wifiTen = $_POST['wifiTen'] ?? '';
            $dinhVi = DinhVi::where('id_rapphim', $idRapPhim)->first();
            if($dinhVi){
                $dinhVi->wifi_ip = $wifi;
                $dinhVi->wifi_ten = $wifiTen;
                $dinhVi->save();
            }
            else{
                DinhVi::create([
                    'id_rapphim' => $idRapPhim,
                    'wifi_ip' => $wifi,
                    'wifi_ten' => $wifiTen
                ]);
            }
        }
    }
?>