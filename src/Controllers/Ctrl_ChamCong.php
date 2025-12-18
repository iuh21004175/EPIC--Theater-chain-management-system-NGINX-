<?php
namespace App\Controllers;

use App\Services\Sc_ChamCong;
use App\Services\Sc_ServerChamCong;
use function App\Core\view;

class Ctrl_ChamCong
{
    /**
     * Trang chấm công
     */
    public function index()
    {   
        $service = new Sc_ServerChamCong();
        $serverChamCong = $service->getServerChamCong();
        return view('internal.cham-cong', [
            'serverChamCong' => $serverChamCong
        ]);
    }

    /**
     * Trang đăng ký khuôn mặt
     */
    public function dangKyKhuonMat()
    {
        $service = new Sc_ServerChamCong();
        $serverChamCong = $service->getServerChamCong();
        return view('internal.dang-ky-khuon-mat', [
            'serverChamCong' => $serverChamCong
        ]);
    }

    /**
     * Xử lý đăng ký khuôn mặt
     */
    public function xuLyDangKyKhuonMat(){
        $service = new Sc_ChamCong();
        try {
            $service->dangKyKhuonMat();
            return[
                'success' => true,
                'message' => 'Đăng ký khuôn mặt thành công'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    public function kiemTraDangKy(){
        $service = new Sc_ChamCong();
        try{
            $dangKy = $service->kiemTraDangKy();
            return [
                'success' => true,
                'message' => 'Đã đăng ký khuôn mặt',
                'data' => $dangKy
            ];
        }
        catch(\Exception $e){
            return [
                'success' => false,
                'message' => 'Lỗi hệ thống: ' . $e->getMessage()
            ];
        }
    }
    public function lichSuChamCong(){

        $service = new Sc_ChamCong();
        try{
            $lichSu = $service->lichSuChamCong();
            return [
                'success' => true,
                'data' => $lichSu
            ];
        }
        catch(\Exception $e){
            return [
                'success' => false,
                'message' => 'Lỗi hệ thống: ' . $e->getMessage()
            ];
        }
    }
    public function chamCongKhuonMat(){
        $service = new Sc_ChamCong();
        try{
            $nhanVien = $service->chamCongKhuonMat();
            return [
                'success' => true,
                'message' => 'Chấm công thành công',
                'data' => $nhanVien
            ];
        }
        catch(\Exception $e){
            return [
                'success' => false,
                'message' =>  $e->getMessage()
            ];
        }
    }
    
    /**
     * Xử lý đăng ký khuôn mặt nâng cấp từ .NET
     * Nhận JSON: {id_nhanvien: string}
     */
    public function xuLyDangKyKhuonMatNangCap(){
        $service = new Sc_ChamCong();
        try {
            $result = $service->dangKyKhuonMatNangCap();
            return $result;
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Xử lý chấm công nâng cấp từ .NET
     * Nhận JSON: {id_nhanvien: string, loai: string}
     */
    public function xuLyChamCongNangCap(){
        $service = new Sc_ChamCong();
        try {
            $result = $service->chamCongNangCap();
            return $result;
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
