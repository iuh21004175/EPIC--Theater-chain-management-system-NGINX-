<?php
namespace App\Controllers;

use App\Services\Sc_ChamCong;
use App\Services\Sc_DinhVi;
use function App\Core\view;

class Ctrl_ChamCong
{
    /**
     * Trang chấm công
     */
    public function index()
    {   
        $service = new Sc_DinhVi();
        $dinhVi = $service->getDinhVi();
        return view('internal.cham-cong', [
            'dinhVi' => $dinhVi
        ]);
    }

    /**
     * Trang đăng ký khuôn mặt
     */
    public function dangKyKhuonMat()
    {
        return view('internal.dang-ky-khuon-mat');
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
}
