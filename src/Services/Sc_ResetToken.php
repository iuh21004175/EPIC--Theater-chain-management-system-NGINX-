<?php
    namespace App\Services;
    use App\Models\ResetToken;
    class Sc_ResetToken {
        public function createToken($khachHangId) {
            $token = new ResetToken();
            $token->khach_hang_id = $khachHangId;
            $token->token = bin2hex(random_bytes(32));
            $token->expire_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $token->save();
            return $token;
        }
    }
?>
