<?php
// api.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

// Simple API: GET /api.php -> trả tất cả câu hỏi
try {
    $stmt = $pdo->query("SELECT id, question_text, option_a, option_b, option_c, option_d, correct_option FROM questions ORDER BY id ASC");
    $rows = $stmt->fetchAll();
    // convert rows to structured format
    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'id' => (int)$r['id'],
            'question' => $r['question_text'],
            'options' => [
                'A' => $r['option_a'],
                'B' => $r['option_b'],
                'C' => $r['option_c'],
                'D' => $r['option_d']
            ],
            'answer' => $r['correct_option']
        ];
    }
    echo json_encode(['ok' => true, 'questions' => $out], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
