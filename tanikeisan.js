// 目標単位数を定義するオブジェクト (変更なし)
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
    let totalAcquired = 0;
    const GRADUATION_TARGET_UNITS = 124; // 卒業に必要な総単位数を固定値として定義

    for (const category in targetUnits) {
        if (targetUnits.hasOwnProperty(category)) {
            if (category !== 'jiyuSentaku') {
                const finalAcquiredForCategory = Math.min(currentAcquired[category], targetUnits[category]);
                finalResults[category] = Math.max(0, targetUnits[category] - finalAcquiredForCategory);
                totalAcquired += finalAcquiredForCategory;
            }
        }
    }

    // 自由選択科目については、最終的な取得済み単位数（換算された単位を含む）を計算し、
    // 目標単位数を超過した分も全体の取得済み単位に含める
    finalResults.jiyuSentaku = Math.max(0, targetUnits.jiyuSentaku - currentAcquired.jiyuSentaku);
    totalAcquired += Math.min(currentAcquired.jiyuSentaku, targetUnits.jiyuSentaku); // 自由選択の目標分を加算
    totalAcquired += Math.max(0, currentAcquired.jiyuSentaku - targetUnits.jiyuSentaku); // 自由選択の超過分も総取得単位に加算

    // 最終的な全体の計算
    finalResults.totalAcquired = totalAcquired; // 換算された単位を含んだ総取得単位
    // 全体の残り単位数 = 卒業目標単位数 (124) - 全体の取得済み単位数
    finalResults.totalRemaining = Math.max(0, GRADUATION_TARGET_UNITS - finalResults.totalAcquired);

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