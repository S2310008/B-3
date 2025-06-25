// 目標単位数を定義するオブジェクト
const targetUnits = {
    kyoyo: 24,         // 教養教育科目
    kogakuKiso: 16,    // 工学基礎科目
    johoKiso: 12,      // 情報基礎科目
    johoOyo: 4,        // 情報応用科目
    major1: 28,        // 第1メジャー科目
    major2: 14,        // 第2メジャー科目
    otherMajor: 10,    // その他メジャー科目
    sotsuken: 8,       // 卒業研究
    jiyuSentaku: 8     // 自由選択科目
};

/**
 * 各科目の残りの単位数を計算し、超過単位を換算する関数。
 * @param {object} initialAcquiredUnits - 初期に取得済みの各科目の単位数を格納したオブジェクト
 * @returns {object} - 各科目の最終的な残りの単位数と、全体の単位状況を格納したオブジェクト
 */
function calculateUnitsWithConversion(initialAcquiredUnits) {
    // 処理中に単位数が変動するため、作業用のコピーを作成
    const currentAcquired = { ...initialAcquiredUnits };

    // --- 換算ロジックの適用（順序が重要） ---

    // 1. 第1メジャー、第2メジャー科目の超過分をその他メジャー科目に換算
    const major1Overflow = Math.max(0, currentAcquired.major1 - targetUnits.major1);
    if (major1Overflow > 0) {
        currentAcquired.otherMajor += major1Overflow;
        currentAcquired.major1 = targetUnits.major1; // 超過分を換算したら、元科目の取得単位は目標値に丸める
    }

    const major2Overflow = Math.max(0, currentAcquired.major2 - targetUnits.major2);
    if (major2Overflow > 0) {
        currentAcquired.otherMajor += major2Overflow;
        currentAcquired.major2 = targetUnits.major2; // 超過分を換算したら、元科目の取得単位は目標値に丸める
    }

    // 2. 教養教育科目、工学基礎科目、情報基礎科目、情報応用科目、その他メジャー科目の超過分を自由選択科目に換算
    const kyoyoOverflow = Math.max(0, currentAcquired.kyoyo - targetUnits.kyoyo);
    if (kyoyoOverflow > 0) {
        currentAcquired.jiyuSentaku += kyoyoOverflow;
        currentAcquired.kyoyo = targetUnits.kyoyo;
    }

    const kogakuKisoOverflow = Math.max(0, currentAcquired.kogakuKiso - targetUnits.kogakuKiso);
    if (kogakuKisoOverflow > 0) {
        currentAcquired.jiyuSentaku += kogakuKisoOverflow;
        currentAcquired.kogakuKiso = targetUnits.kogakuKiso;
    }

    const johoKisoOverflow = Math.max(0, currentAcquired.johoKiso - targetUnits.johoKiso);
    if (johoKisoOverflow > 0) {
        currentAcquired.jiyuSentaku += johoKisoOverflow;
        currentAcquired.johoKiso = targetUnits.johoKiso;
    }

    const johoOyoOverflow = Math.max(0, currentAcquired.johoOyo - targetUnits.johoOyo);
    if (johoOyoOverflow > 0) {
        currentAcquired.jiyuSentaku += johoOyoOverflow;
        currentAcquired.johoOyo = targetUnits.johoOyo;
    }

    const otherMajorOverflow = Math.max(0, currentAcquired.otherMajor - targetUnits.otherMajor);
    if (otherMajorOverflow > 0) {
        currentAcquired.jiyuSentaku += otherMajorOverflow;
        currentAcquired.otherMajor = targetUnits.otherMajor;
    }

    // --- 換算後の最終的な残りの単位数と合計を計算 ---
    const finalResults = {};
    let totalAcquiredForGraduation = 0; // 卒業要件の総取得単位計算用
    let totalTargetUnitsSum = 0; // 各科目の目標単位数の合計

    for (const category in targetUnits) {
        if (targetUnits.hasOwnProperty(category)) {
            // 各カテゴリの取得済み単位は、目標単位数を超えないように調整して最終結果に反映
            // ここで算出される finalAcquiredForCategory が、そのカテゴリで「目標として認められる単位」
            const finalAcquiredForCategory = Math.min(currentAcquired[category], targetUnits[category]);
            
            // 個々のカテゴリの残り単位数を計算
            finalResults[category] = Math.max(0, targetUnits[category] - finalAcquiredForCategory);
            
            // 卒業要件上の総取得単位に加算（自由選択科目は後で特別に加算）
            if (category !== 'jiyuSentaku') {
                totalAcquiredForGraduation += finalAcquiredForCategory;
            }
            
            // 全ての目標単位を合算
            totalTargetUnitsSum += targetUnits[category];
        }
    }

    // 自由選択科目の残り単位は、目標値と実際の取得済み単位（換算含む）に基づいて計算
    finalResults.jiyuSentaku = Math.max(0, targetUnits.jiyuSentaku - currentAcquired.jiyuSentaku);
    
    // 全体の取得済み単位数（卒業要件用）には、自由選択科目の取得済み単位（換算含む）をそのまま加算
    // ここで、自由選択科目の取得済み単位を合算する
    totalAcquiredForGraduation += currentAcquired.jiyuSentaku;

    // 最終的な全体の計算
    finalResults.totalAcquired = totalAcquiredForGraduation; // 全体の取得済み単位数はこれで確定

    // 全体の残り単位数: これは「各カテゴリの目標単位数の合計」から
    // 「各カテゴリの目標達成分（自由選択は超過分も含む）の合計」を引くことで算出
    // これにより、個別の残り単位の和が正確に反映される
    const GRADUATION_TARGET_UNITS = 124; // 卒業に必要な総単位数（この値も最終確認用として残します）

    // ここで、各カテゴリの目標単位の合計 (totalTargetUnitsSum) から、
    // 各カテゴリで「目標として認められる単位」の合計 (totalAcquiredForGraduation) を引くことで、
    // 全体の残り単位数を算出します。
    // ※この計算方法では、自由選択科目の超過分が全体の残り単位数に影響しないように注意が必要です。
    //   「各科目の残り単位数の和」という定義に最も忠実にするため、
    //   finalResultsに格納された個々の残り単位を合計し直す方が確実です。
    
    // 以下のように再計算することで、個々の科目の残り単位の合計とします。
    let recalculatedTotalRemaining = 0;
    for (const category in finalResults) {
        if (finalResults.hasOwnProperty(category)) {
            recalculatedTotalRemaining += finalResults[category];
        }
    }
    finalResults.totalRemaining = recalculatedTotalRemaining;

    return finalResults;
}

// HTMLの <script> タグ内でこの関数を呼び出す
function updateCalculations() {
    const acquiredUnits = {
        kyoyo: parseInt(document.getElementById('kyoyoUnits').value) || 0,
        kogakuKiso: parseInt(document.getElementById('kogakuKisoUnits').value) || 0,
        johoKiso: parseInt(document.getElementById('johoKisoUnits').value) || 0,
        johoOyo: parseInt(document.getElementById('johoOyoUnits').value) || 0,
        major1: parseInt(document.getElementById('major1Units').value) || 0,
        major2: parseInt(document.getElementById('major2Units').value) || 0,
        otherMajor: parseInt(document.getElementById('otherMajorUnits').value) || 0,
        sotsuken: parseInt(document.getElementById('sotsukenUnits').value) || 0,
        jiyuSentaku: parseInt(document.getElementById('jiyuSentakuUnits').value) || 0
    };

    // calculateUnitsWithConversion を呼び出す
    const results = calculateUnitsWithConversion(acquiredUnits);

    document.getElementById('resultKyoyo').textContent = results.kyoyo;
    document.getElementById('resultKogakuKiso').textContent = results.kogakuKiso;
    document.getElementById('resultJohoKiso').textContent = results.johoKiso;
    document.getElementById('resultJohoOyo').textContent = results.johoOyo;
    document.getElementById('resultMajor1').textContent = results.major1;
    document.getElementById('resultMajor2').textContent = results.major2;
    document.getElementById('resultOtherMajor').textContent = results.otherMajor;
    document.getElementById('resultSotsuken').textContent = results.sotsuken;
    document.getElementById('resultJiyuSentaku').textContent = results.jiyuSentaku;

    document.getElementById('resultTotalAcquired').textContent = results.totalAcquired;
    document.getElementById('resultTotalRemaining').textContent = results.totalRemaining;
}

// ページロード時に一度計算を実行して初期表示を更新
window.onload = updateCalculations;