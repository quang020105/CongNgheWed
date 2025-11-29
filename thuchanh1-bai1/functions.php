<?php
// functions.php

// đường dẫn
define('IMAGES_DIR', __DIR__ . '/images');
define('DATA_FILE', __DIR__ . '/data/flowers.json');

// đảm bảo thư mục data tồn tại
if (!is_dir(__DIR__ . '/data')) {
    mkdir(__DIR__ . '/data', 0755, true);
}

// tải metadata từ JSON (filename => ['name'=>..,'description'=>..])
function load_metadata() {
    if (file_exists(DATA_FILE)) {
        $json = @file_get_contents(DATA_FILE);
        $arr = json_decode($json, true);
        return is_array($arr) ? $arr : [];
    }
    return [];
}

function save_metadata($meta) {
    // $meta should be associative array filename => ['name'=>..,'description'=>..]
    file_put_contents(DATA_FILE, json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// tạo mảng $flowers từ file ảnh có trong images/ và metadata
function load_flowers() {
    $meta = load_metadata();

    // lấy file ảnh (đếm các extension thông dụng)
    $files = glob(IMAGES_DIR . '/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
    // Sắp xếp theo tên hoặc thời gian - ở đây theo filemtime (mới -> cũ)
    usort($files, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });

    $flowers = [];
    foreach ($files as $path) {
        $filename = basename($path);
        $base = pathinfo($filename, PATHINFO_FILENAME);

        // nếu metadata có, dùng; nếu chưa có, tạo mặc định
        if (isset($meta[$filename])) {
            $name = $meta[$filename]['name'] ?? ucwords(str_replace(['-','_'], ' ', $base));
            $desc = $meta[$filename]['description'] ?? "Mô tả cho $name.";
        } else {
            $name = ucwords(str_replace(['-','_'], ' ', $base));
            $desc = "Mô tả mặc định cho $name.";
        }

        $flowers[] = [
            'name' => $name,
            'description' => $desc,
            'image' => $filename
        ];
    }

    return $flowers;
}

// helper safe output
function e($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

// allowed mime check
function allowed_mime($tmpfile) {
    $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $tmpfile);
    finfo_close($finfo);
    return in_array($mime, $allowed);
}
