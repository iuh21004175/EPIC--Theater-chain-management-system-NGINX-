<?php
namespace App\Controllers;

use App\Services\Sc_GiaoDich;

class Ctrl_GiaoDich
{
    public function handleWebhook()
    {
        header('Content-Type: application/json'); 
        $service = new Sc_GiaoDich();

        try {
            $transaction = $service->luu();

            if ($transaction) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Transaction saved',
                    'data' => $transaction
                ]);
                exit;
            }

            echo json_encode([
                'success' => false,
                'message' => 'Failed to save transaction'
            ]);
            exit;

        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    public function checkTrangThai()
    {
        header('Content-Type: application/json');

        $data = json_decode(file_get_contents('php://input'), true);
        $donhangId = $data['donhang_id'] ?? null;

        $service = new Sc_GiaoDich();
        $status = $service->getTrangThai($donhangId);

        echo json_encode(['payment_status' => $status]);
        exit;
    }
}
