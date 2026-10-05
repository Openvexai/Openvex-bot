<?php
/**
 * Raptor AI Telegram Bot
 */

// ⚠️ توکن ربات رو اینجا بذار
define('BOT_TOKEN', 'YOUR_BOT_TOKEN_HERE');

// آدرس Sky API روی InfinityFree
define('SKY_API_URL', 'https://openvex.xo.je/sky/v1/ask.php');
define('SKY_API_TOKEN', 'sk_test_1234567890abcdef');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$input = file_get_contents('php://input');
$update = json_decode($input, true);

if (!$update) exit;

function sendMessage($chat_id, $text, $parse_mode = null) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage";
    $data = ['chat_id' => $chat_id, 'text' => $text, 'disable_web_page_preview' => true];
    if ($parse_mode) $data['parse_mode'] = $parse_mode;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_exec($ch);
    curl_close($ch);
}

function sendTyping($chat_id) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendChatAction";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['chat_id' => $chat_id, 'action' => 'typing']));
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_exec($ch);
    curl_close($ch);
}

function askRaptor($message, $user_id) {
    $url = SKY_API_URL . '?' . http_build_query([
        'token' => SKY_API_TOKEN,
        'msg' => $message,
        'uid' => $user_id
    ]);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 || !$response) {
        return 'خطا در ارتباط با سرور! دوباره امتحان کن.';
    }
    
    $data = json_decode($response, true);
    
    if (!$data || !$data['success']) {
        return 'متأسفم، جوابی پیدا نکردم.';
    }
    
    return $data['data']['reply'] ?? 'خطا در پاسخ.';
}

if (isset($update['message'])) {
    $message = $update['message'];
    $chat_id = $message['chat']['id'];
    $user_id = $message['from']['id'];
    $username = $message['from']['first_name'] ?? 'کاربر';
    $text = trim($message['text'] ?? '');
    
    if (empty($text)) {
        sendMessage($chat_id, 'لطفاً یه پیام متنی بفرست.');
        exit;
    }
    
    if ($text === '/start') {
        $welcome = "سلام $username! 👋\n\n";
        $welcome .= "من 🤖 *Raptor AI* هستم، دستیار هوشمند OpenVex.\n\n";
        $welcome .= "می‌تونم به سوالاتت جواب بدم:\n";
        $welcome .= "• 📚 اطلاعات عمومی\n";
        $welcome .= "• 🔢 محاسبات ریاضی\n";
        $welcome .= "• 💙 همدلی\n";
        $welcome .= "• 🌐 ترجمه\n\n";
        $welcome .= "فقط پیام بفرست! 🚀";
        sendMessage($chat_id, $welcome, 'Markdown');
        exit;
    }
    
    if ($text === '/help' || $text === '/راهنما') {
        $help = "📚 *راهنما:*\n\n";
        $help .= "1️⃣ سوال بپرس\n";
        $help .= "2️⃣ ریاضی: `2+2`\n";
        $help .= "3️⃣ احساسی: `حالم خوب نیست`\n\n";
        $help .= "🔧 /start /help /about";
        sendMessage($chat_id, $help, 'Markdown');
        exit;
    }
    
    if ($text === '/about') {
        $about = "🤖 *Raptor AI 1.1 Mini*\n\n";
        $about .= "• سازنده: Erfan Coder\n";
        $about .= "• پروژه: OpenVex\n";
        $about .= "• دانش: ۲۷۰۰+ سوال\n";
        $about .= "• وبسایت: openvex.xo.je";
        sendMessage($chat_id, $about, 'Markdown');
        exit;
    }
    
    sendTyping($chat_id);
    $reply = askRaptor($text, $user_id);
    sendMessage($chat_id, $reply);
    exit;
}

http_response_code(200);
echo 'OK';
?>
