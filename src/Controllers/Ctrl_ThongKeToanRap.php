<?php
    namespace App\Controllers;

    use App\Services\Sc_RapPhim;
    use function App\Core\view;
    class Ctrl_ThongKeToanRap {
        public function index() {
            // Logic thống kê doanh thu
            $scRapPhim = new Sc_RapPhim();
            $rapPhim = $scRapPhim->doc();
            return view("internal.thong-ke-toan-rap", ['rapPhim' => $rapPhim]);
        }
    }
?>
