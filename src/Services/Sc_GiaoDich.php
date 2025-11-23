<?php
namespace App\Services;

use App\Models\GiaoDich;
use App\Models\DonHang;
use App\Models\Ve;
use App\Models\MuaPhim;
use function App\Core\getRedisConnection;

class Sc_GiaoDich
{
    public function luu()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data || !is_array($data)) {
            echo "Invalid data received\n";
            return false;
        }

        try {
            $amount_in = ($data['transferType'] ?? null) === 'in' ? ($data['transferAmount'] ?? 0) : 0;
            $amount_out = ($data['transferType'] ?? null) === 'out' ? ($data['transferAmount'] ?? 0) : 0;

            $transaction = GiaoDich::create([
                'gateway'             => $data['gateway'] ?? null,
                'transaction_date'    => $data['transactionDate'] ?? null,
                'account_number'      => $data['accountNumber'] ?? null,
                'sub_account'         => $data['subAccount'] ?? null,
                'amount_in'           => $amount_in,
                'amount_out'          => $amount_out,
                'accumulated'         => $data['accumulated'] ?? 0,
                'code'                => $data['code'] ?? null,
                'transaction_content' => $data['content'] ?? null,
                'reference_number'    => $data['referenceCode'] ?? null,
                'body'                => $data['description'] ?? null,
                'created_at'          => date('Y-m-d H:i:s')
            ]);

            echo "Transaction saved: " . $transaction->id . "\n";

            $transactionContent = trim($data['content'] ?? '');
            echo "Transaction content: " . $transactionContent . "\n";

            if (preg_match_all('/DH(\d+)/i', $transactionContent, $matches)) {
                echo "Found DonHang IDs: " . implode(', ', $matches[1]) . "\n";

                foreach ($matches[1] as $donhangId) {
                    $donHang = DonHang::find((int)$donhangId);
                    if ($donHang) {
                        $donHang->trang_thai = 2;
                        $donHang->save();
                        echo "Updated DonHang ID {$donhangId} to trang_thai=2\n";
                        $gheIds = [];
                        foreach ($donHang->ve as $ve) {
                            $gheIds[] = $ve->ghe_id;
                        }
                        getRedisConnection()->publish('thanh-toan-don-hang-thanh-cong', json_encode(['suatChieuId' => $donHang->suat_chieu_id, 'gheIds' => $gheIds]));
                        Ve::where('donhang_id', $donhangId)
                          ->update(['trang_thai' => 2]);
                        echo "Updated Ve of DonHang ID {$donhangId} to 'da_dat'\n";

                        MuaPhim::where('don_hang_id', $donhangId)
                            ->update(['trang_thai' => 2]);
                        echo "Updated MuaPhim of DonHang ID {$donhangId} to trang_thai=2\n";
                    } else {
                        echo "DonHang ID {$donhangId} not found\n";
                    }
                }
            } else {
                echo "No DHxxx found in content\n";
            }

            return $transaction;

        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            return false;
        }
    }

    public function getTrangThai($donhangId)
    {
        if (!$donhangId || !is_numeric($donhangId)) {
            return 'invalid_id';
        }

        $donHang = DonHang::find($donhangId);

        if (!$donHang) {
            return 'order_not_found';
        }

        return $donHang->trang_thai == 2 ? 'Paid' : 'Unpaid';
    }
}
?>
