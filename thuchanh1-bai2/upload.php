<?php
// upload.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!isset($_FILES['quizfile']) || $_FILES['quizfile']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'Vui lòng chọn file .txt để upload']);
    exit;
}

// Option: nếu muốn replace toàn bộ bảng khi upload mới
$replace = isset($_POST['replace']) && $_POST['replace'] === '1';

$uploaddir = __DIR__ . '/uploads';
if (!is_dir($uploaddir)) mkdir($uploaddir, 0755, true);

$uploadfile = $uploaddir . '/' . basename($_FILES['quizfile']['name']);
if (!move_uploaded_file($_FILES['quizfile']['tmp_name'], $uploadfile)) {
    echo json_encode(['error' => 'Không thể lưu file uploaded']);
    exit;
}

$text = file_get_contents($uploadfile);
if ($text === false) {
    echo json_encode(['error' => 'Không thể đọc file uploaded']);
    exit;
}

// Normalize line endings
$text = str_replace(["\r\n", "\r"], "\n", $text);
// Split into blocks (one or more blank lines separate blocks)
$blocks = preg_split("/\n{2,}/", trim($text));

$parsed = [];
foreach ($blocks as $block) {
    $lines = array_map('trim', explode("\n", trim($block)));
    if (count($lines) === 0) continue;

    // find first option line index (A. B. C. D.)
    $optStart = -1;
    foreach ($lines as $i => $ln) {
        if (preg_match('/^[A-ＤＡ-Ｄ]\s*[.\)]/iu', $ln) || preg_match('/^[A-D]\s*[-–—]/i', $ln)) {
            $optStart = $i;
            break;
        }
        // also consider lines starting with "A. " (with ascii)
        if (preg_match('/^[A-D]\s+\S+/i', $ln)) {
            $optStart = $i;
            break;
        }
    }

    $questionText = '';
    $options = ['A' => null, 'B' => null, 'C' => null, 'D' => null];
    $answer = null;

    if ($optStart === -1) {
        // no explicit options found -> whole block is question
        $questionText = implode(' ', $lines);
    } else {
        $questionText = implode(' ', array_slice($lines, 0, $optStart));
        // parse options from optStart until ANSWER line or end
        $lastKey = null;
        for ($i = $optStart; $i < count($lines); $i++) {
            $ln = $lines[$i];
            // stop if ANSWER line
            if (preg_match('/^\s*ANSWER[:\s]/i', $ln) || preg_match('/^\s*Answer[:\s]/i', $ln) || preg_match('/^\s*ANS[:\s]/i', $ln)) {
                // extract answer letter
                if (preg_match('/([A-D])\b/i', $ln, $m)) {
                    $answer = strtoupper($m[1]);
                }
                break;
            }
            // option lines A. text or A) text or "A text"
            if (preg_match('/^\s*([A-D])\s*[.\)]\s*(.*)$/i', $ln, $m)) {
                $key = strtoupper($m[1]);
                $options[$key] = $m[2];
                $lastKey = $key;
                continue;
            }
            if (preg_match('/^\s*([A-D])\s+(.*)$/i', $ln, $m2)) {
                $key = strtoupper($m2[1]);
                $options[$key] = $m2[2];
                $lastKey = $key;
                continue;
            }
            // continuation of previous option
            if ($lastKey !== null) {
                $options[$lastKey] .= ' ' . $ln;
            }
        }

        // if answer not found earlier, try to find in remaining lines
        if ($answer === null) {
            foreach ($lines as $ln) {
                if (preg_match('/^\s*ANSWER[:\s]*([A-D])\b/i', $ln, $m)) { $answer = strtoupper($m[1]); break; }
                if (preg_match('/\bANS[:\s]*([A-D])\b/i', $ln, $m)) { $answer = strtoupper($m[1]); break; }
            }
        }
    }

    // trim values
    $questionText = trim($questionText);
    foreach ($options as $k => $v) $options[$k] = $v !== null ? trim($v) : null;

    if ($questionText === '' && empty(array_filter($options))) continue;

    $parsed[] = [
        'question' => $questionText ?: '(Không có tiêu đề câu hỏi)',
        'options' => $options,
        'answer' => $answer
    ];
}

// Option: replace table
if ($replace) {
    $pdo->exec("TRUNCATE TABLE questions");
}

// Insert parsed questions
$inserted = 0;
$stmt = $pdo->prepare("INSERT INTO questions
  (question_text, option_a, option_b, option_c, option_d, correct_option)
  VALUES (:q, :a, :b, :c, :d, :corr)");

foreach ($parsed as $p) {
    $a = $p['options']['A'] ?? null;
    $b = $p['options']['B'] ?? null;
    $c = $p['options']['C'] ?? null;
    $d = $p['options']['D'] ?? null;
    $corr = $p['answer'] ?? null;
    // if you want to avoid duplicates, you could check existing similar question before insert
    $stmt->execute([
        ':q' => $p['question'],
        ':a' => $a,
        ':b' => $b,
        ':c' => $c,
        ':d' => $d,
        ':corr' => $corr
    ]);
    $inserted++;
}

echo json_encode(['ok' => true, 'file' => basename($uploadfile), 'parsed' => count($parsed), 'inserted' => $inserted]);
