// 目標単位数を定義するオブジェクト (変更なし)
const targetUnits = {
    kyoyo: 21,         // 教養教育科目
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

    // --- 換算ロジックの適用 ---

    // 1. 第1メジャー、第2メジャー科目の超過分をその他メジャー科目に換算
    const major1Overflow = Math.max(0, currentAcquired.major1 - targetUnits.major1);
    if (major1Overflow > 0) {
        currentAcquired.otherMajor += major1Overflow;
        // console.log(`第1メジャー科目の超過 ${major1Overflow} をその他メジャー科目に換算`);
        currentAcquired.major1 = targetUnits.major1; // 超過分を換算したら、元科目の取得単位は目標値に丸める
    }

    const major2Overflow = Math.max(0, currentAcquired.major2 - targetUnits.major2);
    if (major2Overflow > 0) {
        currentAcquired.otherMajor += major2Overflow;
        // console.log(`第2メジャー科目の超過 ${major2Overflow} をその他メジャー科目に換算`);
        currentAcquired.major2 = targetUnits.major2; // 超過分を換算したら、元科目の取得単位は目標値に丸める
    }

    // 2. 教養教育科目、工学基礎科目、情報基礎科目、情報応用科目、その他メジャー科目の超過分を自由選択科目に換算
    const kyoyoOverflow = Math.max(0, currentAcquired.kyoyo - targetUnits.kyoyo);
    if (kyoyoOverflow > 0) {
        currentAcquired.jiyuSentaku += kyoyoOverflow;
        // console.log(`教養教育科目の超過 ${kyoyoOverflow} を自由選択科目に換算`);
        currentAcquired.kyoyo = targetUnits.kyoyo;
    }

    const kogakuKisoOverflow = Math.max(0, currentAcquired.kogakuKiso - targetUnits.kogakuKiso);
    if (kogakuKisoOverflow > 0) {
        currentAcquired.jiyuSentaku += kogakuKisoOverflow;
        // console.log(`工学基礎科目の超過 ${kogakuKisoOverflow} を自由選択科目に換算`);
        currentAcquired.kogakuKiso = targetUnits.kogakuKiso;
    }

    const johoKisoOverflow = Math.max(0, currentAcquired.johoKiso - targetUnits.johoKiso);
    if (johoKisoOverflow > 0) {
        currentAcquired.jiyuSentaku += johoKisoOverflow;
        // console.log(`情報基礎科目の超過 ${johoKisoOverflow} を自由選択科目に換算`);
        currentAcquired.johoKiso = targetUnits.johoKiso;
    }

    const johoOyoOverflow = Math.max(0, currentAcquired.johoOyo - targetUnits.johoOyo);
    if (johoOyoOverflow > 0) {
        currentAcquired.jiyuSentaku += johoOyoOverflow;
        // console.log(`情報応用科目の超過 ${johoOyoOverflow} を自由選択科目に換算`);
        currentAcquired.johoOyo = targetUnits.johoOyo;
    }

    const otherMajorOverflow = Math.max(0, currentAcquired.otherMajor - targetUnits.otherMajor);
    if (otherMajorOverflow > 0) {
        currentAcquired.jiyuSentaku += otherMajorOverflow;
        // console.log(`その他メジャー科目の超過 ${otherMajorOverflow} を自由選択科目に換算`);
        currentAcquired.otherMajor = targetUnits.otherMajor;
    }

    // --- 換算後の最終的な残りの単位数と合計を計算 ---
    const finalResults = {};
    let totalAcquired = 0;
    let totalTarget = 0;

    for (const category in targetUnits) {
        if (targetUnits.hasOwnProperty(category)) {
            // 各カテゴリの取得済み単位は、目標単位数を超えないように調整して最終結果に反映
            // ただし、自由選択科目は特別で、超過分も全体の取得済み単位に加算されるため後で処理
            if (category !== 'jiyuSentaku') {
                const finalAcquiredForCategory = Math.min(currentAcquired[category], targetUnits[category]);
                finalResults[category] = Math.max(0, targetUnits[category] - finalAcquiredForCategory);
                totalAcquired += finalAcquiredForCategory;
            }
            totalTarget += targetUnits[category];
        }
    }

    // 自由選択科目については、最終的な取得済み単位数（換算された単位を含む）を計算し、
    // 目標単位数を超過した分も全体の取得済み単位に含める
    finalResults.jiyuSentaku = Math.max(0, targetUnits.jiyuSentaku - currentAcquired.jiyuSentaku);
    totalAcquired += Math.min(currentAcquired.jiyuSentaku, targetUnits.jiyuSentaku); // 自由選択の目標分を加算
    totalAcquired += Math.max(0, currentAcquired.jiyuSentaku - targetUnits.jiyuSentaku); // 自由選択の超過分も総取得単位に加算

    // 最終的な全体の計算
    finalResults.totalRemaining = Math.max(0, totalTarget - totalAcquired);
    finalResults.totalAcquired = totalAcquired; // 換算された単位を含んだ総取得単位

    return finalResults;
}

// 動作確認のための例
let acquiredUnitsExample = {
    kyoyo: 25,         // 教養教育科目 (+4) -> 自由選択
    kogakuKiso: 18,    // 工学基礎科目 (+2) -> 自由選択
    johoKiso: 10,      // 情報基礎科目 (-2)
    johoOyo: 6,        // 情報応用科目 (+2) -> 自由選択
    major1: 30,        // 第1メジャー科目 (+2) -> その他メジャー
    major2: 15,        // 第2メジャー科目 (+1) -> その他メジャー
    otherMajor: 9,     // その他メジャー科目 (-1) (初期)
    sotsuken: 8,       // 卒業研究
    jiyuSentaku: 0     // 自由選択科目 (初期)
};

// 期待される動き:
// major1 (+2) + major2 (+1) = +3 が otherMajor へ
// otherMajor (初期9) + 換算 (+3) = 12
// otherMajor (+12) は目標10を超過しているので、超過分2を自由選択へ
// kyoyo (+4), kogakuKiso (+2), johoOyo (+2) もそれぞれ自由選択へ
// jiyuSentakuへの換算合計: otherMajorからの+2 + kyoyoからの+4 + kogakuKisoからの+2 + johoOyoからの+2 = 10
// 自由選択は目標8なので、10単位取得しても残り0。総取得単位には10が加算される。

const finalCalculations = calculateUnitsWithConversion(acquiredUnitsExample);

console.log("--- 最終的な単位状況（換算後） ---");
console.log(`教養教育科目 残り: ${finalCalculations.kyoyo} 単位`);
console.log(`工学基礎科目 残り: ${finalCalculations.kogakuKiso} 単位`);
console.log(`情報基礎科目 残り: ${finalCalculations.johoKiso} 単位`);
console.log(`情報応用科目 残り: ${finalCalculations.johoOyo} 単位`);
console.log(`第1メジャー科目 残り: ${finalCalculations.major1} 単位`);
console.log(`第2メジャー科目 残り: ${finalCalculations.major2} 単位`);
console.log(`その他メジャー科目 残り: ${finalCalculations.otherMajor} 単位`);
console.log(`卒業研究 残り: ${finalCalculations.sotsuken} 単位`);
console.log(`自由選択科目 残り: ${finalCalculations.jiyuSentaku} 単位`);
console.log(`------------------------------------`);
console.log(`全体の取得済み単位数: ${finalCalculations.totalAcquired} 単位`);
console.log(`全体の残りの単位数: ${finalCalculations.totalRemaining} 単位`);
console.log(`全体の目標単位数: ${Object.values(targetUnits).reduce((sum, val) => sum + val, 0)} 単位`);

// HTMLとの連携は`updateCalculations()` 関数内でこの関数を呼び出し、結果を表示します。