<?php

/**
 * CSVファイルを読み込み、連想配列の配列として返す関数
 * @return array 科目データの配列
 */
function get_all_subjects_from_csv() {
    $csv_file_path = __DIR__ . '/syllabus_data.csv';
    if (!file_exists($csv_file_path)) {
        return [];
    }

    $all_subjects = [];

    $file = fopen($csv_file_path, 'r');
    if ($file === false) {
        return [];
    }
    
    // ↓↓↓ 1つ目の修正箇所 ↓↓↓
    // 1行目（ヘッダー）を読み込む
    $header = fgetcsv($file, 0, ",", "\"", "\\"); // 引数を省略しない形に変更
    
    if ($header === false) {
        fclose($file);
        return [];
    }

    // BOM（UTF-8の目印）がヘッダーの先頭に含まれていたら削除する
    if (isset($header[0])) {
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
    }

    // ↓↓↓ 2つ目の修正箇所 ↓↓↓
    // 2行目以降（データ）を最後まで読み込む
    while (($row = fgetcsv($file, 0, ",", "\"", "\\")) !== false) { // 引数を省略しない形に変更
        // 列の数がヘッダーと合わない行はスキップする
        if (count($header) !== count($row)) {
            continue;
        }

        $subject_data = array_combine($header, $row);
        
        if (!empty($subject_data['時間割コード'])) {
            $all_subjects[$subject_data['時間割コード']] = $subject_data;
        }
    }
    
    fclose($file);
    
    return $all_subjects;
}