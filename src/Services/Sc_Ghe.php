<?php
namespace App\Services;
use App\Models\Ghe;
class Sc_Ghe {
    public function them(){
        $ten = $_POST['ten'];
        $ma_mau = $_POST['ma_mau'];
        $mo_ta = $_POST['mo_ta'] ?? null;
        $ghe = Ghe::create([
            'ten' => $ten,
            'mo_ta' => $mo_ta,
            'ma_mau' => $ma_mau
        ]);
        if($ghe){
            return true;
        }
        return false;
    }
    public function doc(){
        return Ghe::all();
    }
    public function sua($id){
        $ghe = Ghe::find($id);
        $data = json_decode(file_get_contents('php://input'), true);
        if(!$ghe){
            throw new \Exception("Ghế không tồn tại");
        }
        $ghe->update([
            'ten' => $data['ten'],
            'ma_mau' => $data['ma_mau'],
            'mo_ta' => $data['mo_ta'] ?? null
        ]);
        return $ghe;
    }  
}
?>