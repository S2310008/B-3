// 目標単位数を定義するオブジェクト
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

// ユーザーが入力した（またはデータベースから取得した）取得済み単位数の例
// 実際には、フォームからの入力値や動的に更新される値を使います
let acquiredUnits = {
    kyoyo: 15,
    kogakuKiso: 10,
    johoKiso: 5,
    johoOyo: 0,
    major1: 20,
    major2: 7,
    otherMajor: 3,
    sotsuken: 0,
    jiyuSentaku: 2
};

/**
 * 各科目の残りの単位数を計算し、結果を返します。
 * @param {object} currentAcquiredUnits - 現在取得済みの各科目の単位数を格納したオブジェクト
 * @returns {object} - 各科目の残りの単位数と、全体の残りの単位数を格納したオブジェクト
 */
function calculateRemainingUnits(currentAcquiredUnits) {
    const remaining = {};
    let totalAcquired = 0;
    let totalTarget = 0;

    for (const category in targetUnits) {
        if (targetUnits.hasOwnProperty(category)) {
            const acquired = currentAcquiredUnits[category] || 0; // 取得済みがない場合は0とする
            const remainingUnits = targetUnits[category] - acquired;
            remaining[category] = Math.max(0, remainingUnits); // マイナスにならないように0以上にする
            totalAcquired += acquired;
            totalTarget += targetUnits[category];
        }
    }

    // 全体の残りの単位数
    remaining.totalRemaining = Math.max(0, totalTarget - totalAcquired);

    // 全体の取得済み単位数
    remaining.totalAcquired = totalAcquired;

    return remaining;
}

// 計算を実行
const results = calculateRemainingUnits(acquiredUnits);

console.log("--- 残りの単位数 ---");
console.log(`教養教育科目: ${results.kyoyo} 単位`);
console.log(`工学基礎科目: ${results.kogakuKiso} 単位`);
console.log(`情報基礎科目: ${results.johoKiso} 単位`);
console.log(`情報応用科目: ${results.johoOyo} 単位`);
console.log(`第1メジャー科目: ${results.major1} 単位`);
console.log(`第2メジャー科目: ${results.major2} 単位`);
console.log(`その他メジャー科目: ${results.otherMajor} 単位`);
console.log(`卒業研究: ${results.sotsuken} 単位`);
console.log(`自由選択科目: ${results.jiyuSentaku} 単位`);
console.log(`--------------------`);
console.log(`全体の残りの単位数: ${results.totalRemaining} 単位`);
console.log(`全体の取得済み単位数: ${results.totalAcquired} 単位`);
console.log(`全体の目標単位数: ${Object.values(targetUnits).reduce((sum, val) => sum + val, 0)} 単位`);

// 例: 取得済み単位数を更新して再計算
console.log("\n--- 取得済み単位数を更新して再計算 ---");
acquiredUnits.johoOyo = 4; // 情報応用科目を全て取得したとする
acquiredUnits.sotsuken = 8; // 卒業研究を全て取得したとする
const updatedResults = calculateRemainingUnits(acquiredUnits);
console.log(`情報応用科目 (更新後): ${updatedResults.johoOyo} 単位`);
console.log(`卒業研究 (更新後): ${updatedResults.sotsuken} 単位`);
console.log(`全体の残りの単位数 (更新後): ${updatedResults.totalRemaining} 単位`);