<?php
    namespace App\Controllers;
    use App\Services\Sc_ChatbotAI;
    class Ctrl_ChatBotAI {
        protected $chatService;
        public function __construct() {
            try {
                $this->chatService = new Sc_ChatbotAI();
            } catch (\Throwable $e) {
                error_log("Error creating Sc_ChatbotAI: " . $e->getMessage());
                throw $e;
            }
        }
        // Lấy danh sách tin nhắn
        public function getMessages() {
            try {
                // Kiểm tra service có tồn tại không
                if (!$this->chatService) {
                    throw new \Exception("Chat service not initialized");
                }
                
                $messages = $this->chatService->getMessageList();
                
                // Đảm bảo $messages là array
                if (!is_array($messages)) {
                    $messages = [];
                }
                
                return [
                    'success' => true,
                    'data' => $messages
                ];
            } catch (\Throwable $e) {
                // Log lỗi chi tiết
                error_log("Error in getMessages: " . $e->getMessage());
                error_log("File: " . $e->getFile() . " Line: " . $e->getLine());
                error_log("Stack trace: " . $e->getTraceAsString());
                
                // Xử lý lỗi - luôn trả về array hợp lệ
                return [
                    'success' => false,
                    'error' => 'Lỗi khi tải lịch sử chat: ' . $e->getMessage(),
                    'data' => []
                ];
            }
        }
        // Thêm tin nhắn mới
        public function addMessage(){
            try{
                $result = $this->chatService->addMessage();
                if ($result) {
                    return [
                        'success' => true,
                        'message' => 'Tin nhắn đã được thêm thành công.'
                    ];
                } else {
                    return [
                        'success' => false,
                        'error' => 'Không thể thêm tin nhắn. Vui lòng đăng nhập.'
                    ];
                }
            } catch (\Exception $e) {
                // Xử lý lỗi
                return [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        // API trả lời câu hỏi từ AI
        public function getAIAnswer() {
            try {
                // Lấy dữ liệu từ POST
                $question = $_POST['question'] ?? '';
                $language = $_POST['language'] ?? 'vi';
                $chatHistory = isset($_POST['chat_history']) ? json_decode($_POST['chat_history'], true) : [];
                
                // Validate
                if (empty($question)) {
                    return [
                        'success' => false,
                        'error' => 'Câu hỏi không được để trống'
                    ];
                }
                
                // Gọi service để lấy câu trả lời từ AI
                $result = $this->chatService->getAIResponse($question, $language, $chatHistory);
                
                return $result;
                
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'error' => 'Lỗi xử lý: ' . $e->getMessage()
                ];
            }
        }
    }
?>