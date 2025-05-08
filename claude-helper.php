<?php
/**
 * Βοηθητική κλάση για επικοινωνία με το Claude API
 */
class ClaudeHelper {
    private $apiUrl = 'http://localhost:3000/api/claude';
    
    /**
     * Αποστολή ερώτησης στο Claude API
     * 
     * @param string $question Η ερώτηση του χρήστη
     * @param array $context Προαιρετικό συγκείμενο για την ερώτηση
     * @return string Η απάντηση του Claude
     */
    public function askClaude($question, $context = []) {
        $messages = [
            ['role' => 'user', 'content' => $question]
        ];
        
        if (!empty($context)) {
            // Προσθήκη συγκειμένου στο μήνυμα του χρήστη
            $messages[0]['content'] = "Συγκείμενο: " . json_encode($context, JSON_UNESCAPED_UNICODE) . "\n\nΕρώτηση: " . $question;
        }
        
        $data = [
            'messages' => $messages,
            'system' => 'Είσαι ένας ειδικός στον τομέα των μεταφορών και βοηθάς με την πλατφόρμα drivejob.',
            'max_tokens' => 1000
        ];
        
        $options = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($data, JSON_UNESCAPED_UNICODE)
            ]
        ];
        
        $context = stream_context_create($options);
        $result = file_get_contents($this->apiUrl, false, $context);
        
        if ($result === FALSE) {
            return "Σφάλμα κατά την επικοινωνία με το Claude API";
        }
        
        $response = json_decode($result, true);
        return $response['content'][0]['text'] ?? "Δεν ελήφθη έγκυρη απάντηση";
    }
    
    /**
     * Ανάλυση δεδομένων με το Claude
     * 
     * @param array $data Τα δεδομένα προς ανάλυση
     * @param string $instruction Συγκεκριμένες οδηγίες για την ανάλυση
     * @return string Η ανάλυση του Claude
     */
    public function analyzeData($data, $instruction) {
        $question = "Ανάλυσε τα παρακάτω δεδομένα: " . json_encode($data, JSON_UNESCAPED_UNICODE) . 
                    "\n\nΟδηγίες: " . $instruction;
        
        return $this->askClaude($question);
    }
}
?>
