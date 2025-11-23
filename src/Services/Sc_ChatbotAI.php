<?php
    namespace App\Services;
    class Sc_ChatbotAI {
        /**
         * Làm sạch chuỗi UTF-8, loại bỏ ký tự không hợp lệ
         * 
         * @param string $str Chuỗi cần làm sạch
         * @return string Chuỗi đã được làm sạch
         */
        private function cleanUtf8($str) {
            if (!is_string($str)) {
                return $str;
            }
            
            // Loại bỏ ký tự không hợp lệ
            $str = mb_convert_encoding($str, 'UTF-8', 'UTF-8');
            
            // Loại bỏ ký tự control không hợp lệ (giữ lại \n, \r, \t)
            $str = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $str);
            
            return $str;
        }
        
        /**
         * Làm sạch mảng đệ quy để đảm bảo tất cả string đều là UTF-8 hợp lệ
         * 
         * @param mixed $data Dữ liệu cần làm sạch
         * @return mixed Dữ liệu đã được làm sạch
         */
        private function cleanArrayUtf8($data) {
            if (is_array($data)) {
                return array_map([$this, 'cleanArrayUtf8'], $data);
            } elseif (is_string($data)) {
                return $this->cleanUtf8($data);
            } else {
                return $data;
            }
        }
        
        // Lấy danh sách tin nhắn
        public function getMessageList() {
            try {
                // Kiểm tra xem người dùng đã đăng nhập hay chưa
                if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
                    // Trả về mảng rỗng hoặc tin nhắn chào mừng cho người dùng chưa đăng nhập
                    return [
                        [
                            'id' => uniqid(),
                            'noi_dung' => 'Vui lòng đăng nhập để bắt đầu chat!',
                            'loai_noi_dung' => 1,
                            'nguoi_gui' => null,
                            'created_at' => date('Y-m-d H:i:s')
                        ]
                    ];
                }
                
                $userId = $_SESSION['user']['id'];
                $cookieName = 'chatbotai_history_' . $userId;
                
                // Kiểm tra cookie có tồn tại không
                if (!isset($_COOKIE[$cookieName]) || empty($_COOKIE[$cookieName])) {
                    // Tạo tin nhắn chào mừng
                    $welcomeMessage = [
                        'id' => uniqid(),
                        'noi_dung' => 'Chào bạn! Tôi là Chatbot AI, tôi có thể giúp gì cho bạn?',
                        'loai_noi_dung' => 1, // Text
                        'nguoi_gui' => null, // Hệ thống
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    
                    // Không set cookie ở đây vì có thể đã có output
                    // Cookie sẽ được set ở lần gửi tin nhắn đầu tiên
                    
                    return [$welcomeMessage];
                } 
                
                // Nếu cookie tồn tại, decode an toàn
                try {
                    $chatHistory = json_decode($_COOKIE[$cookieName], true);
                    
                    // Kiểm tra xem decode có thành công không
                    if (is_null($chatHistory) || !is_array($chatHistory)) {
                        throw new \Exception("Invalid JSON in cookie");
                    }
                    
                    // Làm sạch UTF-8 để tránh lỗi encoding
                    $chatHistory = $this->cleanArrayUtf8($chatHistory);
                    
                    return $chatHistory;
                } catch (\Exception $e) {
                    // Log lỗi
                    error_log("Error decoding chat history: " . $e->getMessage());
                    
                    // Reset cookie với giá trị mặc định
                    $welcomeMessage = [
                        'id' => uniqid(),
                        'noi_dung' => 'Xin lỗi, có lỗi xảy ra. Chúng ta bắt đầu lại cuộc trò chuyện nhé!',
                        'loai_noi_dung' => 1,
                        'nguoi_gui' => null,
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    
                    return [$welcomeMessage];
                }
            } catch (\Exception $e) {
                // Log lỗi và trả về mảng rỗng
                error_log("Error in getMessageList: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                
                return [
                    [
                        'id' => uniqid(),
                        'noi_dung' => 'Xin lỗi, có lỗi xảy ra khi tải lịch sử chat.',
                        'loai_noi_dung' => 1,
                        'nguoi_gui' => null,
                        'created_at' => date('Y-m-d H:i:s')
                    ]
                ];
            }
        }
        
        /**
         * Thêm tin nhắn vào lịch sử chat
         * 
         * @param string $message Nội dung tin nhắn
         * @param int|null $nguoiGui 1: Khách hàng, null: AI
         * @param int $loaiNoiDung 1: Text, 2: Hình ảnh, ...
         * @return bool Thành công hay không
         */
        public function addMessage() {
            try {
                $message = $_POST['message'] ?? '';
                $nguoiGui = isset($_POST['nguoi_gui']) ? intval($_POST['nguoi_gui']) : null; // 1: Khách hàng, null: AI
                
                // Kiểm tra xem người dùng đã đăng nhập hay chưa
                if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
                    return false;
                }
            
            $userId = $_SESSION['user']['id'];
            $cookieName = 'chatbotai_history_' . $userId;
            
            // Lấy lịch sử chat hiện tại
            $chatHistory = [];
            if (isset($_COOKIE[$cookieName]) && !empty($_COOKIE[$cookieName])) {
                try {
                    $chatHistory = json_decode($_COOKIE[$cookieName], true);
                    
                    // Nếu không phải mảng hợp lệ, khởi tạo mảng mới
                    if (!is_array($chatHistory)) {
                        $chatHistory = [];
                    }
                } catch (\Exception $e) {
                    // Nếu có lỗi khi decode, khởi tạo mảng mới
                    $chatHistory = [];
                }
            }
            
            // Tạo tin nhắn mới
            $newMessage = [
                'id' => uniqid(),
                'noi_dung' => $message,
                'loai_noi_dung' => 1,
                'nguoi_gui' => $nguoiGui,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Thêm tin nhắn mới vào lịch sử
            $chatHistory[] = $newMessage;
            
            // Kiểm tra kích thước mảng, giới hạn lịch sử nếu cần
            // Cookie có giới hạn kích thước (~4KB), nên giữ lịch sử ngắn
            $maxHistoryLength = 50; // Giới hạn số tin nhắn lưu trữ
            if (count($chatHistory) > $maxHistoryLength) {
                // Cắt bớt lịch sử, giữ lại tin nhắn mới nhất
                $chatHistory = array_slice($chatHistory, -$maxHistoryLength);
            }
            
            // Làm sạch UTF-8 trước khi encode
            $chatHistory = $this->cleanArrayUtf8($chatHistory);
            
            // Lưu lịch sử mới vào cookie
            $encoded = json_encode($chatHistory, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE);
            
            // Nếu chuỗi JSON quá lớn, có thể cần nén
            if (strlen($encoded) > 3500) { // Ngưỡng an toàn cho cookie
                // Giảm thêm số lượng tin nhắn
                $chatHistory = array_slice($chatHistory, -($maxHistoryLength/2)); 
                $encoded = json_encode($chatHistory, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE);
            }
            
            $result = setcookie($cookieName, $encoded, strtotime('tomorrow'), '/');
            
            return $result;
            } catch (\Exception $e) {
                error_log("Error in addMessage: " . $e->getMessage());
                return false;
            }
        }
        
        /**
         * Gọi Python script để xử lý câu hỏi và trả về câu trả lời từ AI
         * 
         * @param string $question Câu hỏi từ người dùng
         * @param string $language Ngôn ngữ (vi/en)
         * @param array $chatHistory Lịch sử chat (tối đa 10 tin nhắn gần nhất)
         * @return array Kết quả từ AI hoặc lỗi
         */
        public function getAIResponse($question, $language = 'vi', $chatHistory = []) {
            try {
                // Kiểm tra đăng nhập
                if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
                    return [
                        'success' => false,
                        'error' => 'Vui lòng đăng nhập để sử dụng chatbot'
                    ];
                }
            
            $userId = $_SESSION['user']['id'];
            $sessionId = session_id();
            
            // Lấy lịch sử chat từ cookie nếu không truyền vào
            if (empty($chatHistory)) {
                $cookieName = 'chatbotai_history_' . $userId;
                if (isset($_COOKIE[$cookieName]) && !empty($_COOKIE[$cookieName])) {
                    try {
                        $chatHistory = json_decode($_COOKIE[$cookieName], true);
                        if (!is_array($chatHistory)) {
                            $chatHistory = [];
                        }
                    } catch (\Exception $e) {
                        $chatHistory = [];
                    }
                }
            }
            
            // Lấy 10 tin nhắn gần nhất để làm context (tránh quá dài)
            $recentHistory = array_slice($chatHistory, -10);
            
            // Chuẩn bị lịch sử chat dạng đơn giản cho Python
            $historyForAI = [];
            foreach ($recentHistory as $msg) {
                if (isset($msg['noi_dung']) && isset($msg['nguoi_gui'])) {
                    $historyForAI[] = [
                        'role' => $msg['nguoi_gui'] === 1 ? 'user' : 'assistant',
                        'content' => $msg['noi_dung']
                    ];
                }
            }
            
            // Đường dẫn Python script
            $pythonScript = __DIR__ . '/../../bin/python/chatbot_ai.py';
            
            // Đường dẫn file log
            $logFile = __DIR__ . '/../../cache/log/chatbot_ai.log';
            $logDir = dirname($logFile);
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            
            // Lấy đường dẫn Python từ biến môi trường, fallback về python3 nếu không có
            $pythonPath = $_ENV['PYTHON_PATH'] ?? 'python3';
            
            // Escape arguments để tránh injection
            $questionEscaped = escapeshellarg($question);
            $languageEscaped = escapeshellarg($language);
            $userIdEscaped = escapeshellarg($userId);
            $sessionIdEscaped = escapeshellarg($sessionId);
            $historyJsonEscaped = escapeshellarg(json_encode($historyForAI, JSON_UNESCAPED_UNICODE));
            
            // Build command với Python path từ biến môi trường
            $command = sprintf(
                '%s %s %s %s %s %s %s 2>&1',
                escapeshellarg($pythonPath),
                escapeshellarg($pythonScript),
                $questionEscaped,
                $languageEscaped,
                $userIdEscaped,
                $sessionIdEscaped,
                $historyJsonEscaped
            );
            
            // Ghi log trước khi thực thi
            $logStart = date('Y-m-d H:i:s') . " [START] User ID: $userId | Session: $sessionId | Language: $language\n";
            $logStart .= "Question: " . substr($question, 0, 200) . "\n";
            $logStart .= "History count: " . count($historyForAI) . "\n";
            $logStart .= "Command: " . substr($command, 0, 300) . "\n";
            $logStart .= str_repeat("-", 80) . "\n";
            @file_put_contents($logFile, $logStart, FILE_APPEND);
            
            // Thời gian bắt đầu
            $startTime = microtime(true);
            
            // Execute Python script
            $output = shell_exec($command);
            
            // Thời gian kết thúc
            $endTime = microtime(true);
            $executionTime = round(($endTime - $startTime) * 1000, 2); // milliseconds
            
            // Ghi log kết quả
            $logResult = date('Y-m-d H:i:s') . " [RESULT] Execution time: {$executionTime}ms\n";
            
            if ($output === null) {
                $logResult .= "ERROR: Không thể kết nối đến AI service (output is null)\n";
                $logResult .= str_repeat("-", 80) . "\n\n";
                @file_put_contents($logFile, $logResult, FILE_APPEND);
                
                return [
                    'success' => false,
                    'error' => 'Không thể kết nối đến AI service'
                ];
            }
            
            // Parse JSON response
            $result = json_decode(trim($output), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Log error chi tiết
                $logResult .= "ERROR: Invalid JSON response\n";
                $logResult .= "JSON Error: " . json_last_error_msg() . "\n";
                $logResult .= "Output (first 500 chars): " . substr($output, 0, 500) . "\n";
                $logResult .= str_repeat("-", 80) . "\n\n";
                @file_put_contents($logFile, $logResult, FILE_APPEND);
                
                // Log error vào error_log cũng
                error_log("Chatbot AI Error: Invalid JSON response. Output: " . substr($output, 0, 500));
                
                return [
                    'success' => false,
                    'error' => 'Lỗi xử lý phản hồi từ AI',
                    'raw_output' => substr($output, 0, 200) // Debug info
                ];
            }
            
            // Ghi log thành công
            $logResult .= "SUCCESS: Intent: " . ($result['intent'] ?? 'N/A') . "\n";
            $logResult .= "Confidence: " . ($result['confidence'] ?? 'N/A') . "\n";
            $logResult .= "Answer length: " . strlen($result['answer'] ?? '') . " chars\n";
            $logResult .= "Sources count: " . count($result['sources'] ?? []) . "\n";
            $logResult .= str_repeat("-", 80) . "\n\n";
            @file_put_contents($logFile, $logResult, FILE_APPEND);
            
            return $result;
            } catch (\Exception $e) {
                // Log lỗi
                error_log("Error in getAIResponse: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                
                return [
                    'success' => false,
                    'error' => 'Lỗi xử lý: ' . $e->getMessage()
                ];
            }
        }
        
    }
?>