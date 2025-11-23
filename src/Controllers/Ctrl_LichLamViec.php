<?php
    namespace App\Controllers;
    use function App\Core\view;
    class Ctrl_LichLamViec{
        public function index(){
            return view('internal.lich-lam-viec');
        }

        public function xemLuong(){
            return view('internal.luong');
        }

        public function yeuCau(){
            return view('internal.yeu-cau');
        }
    }
?>