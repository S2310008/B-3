document.addEventListener('DOMContentLoaded', () => {
    const unitDashboardContainer = document.getElementById('unitDashboardContainer');

    // --- ダミーデータ（モックデータ） ---
    // バックエンドからデータが提供されるようになったら、
    // この部分をfetch()などでバックエンドAPIを呼び出す形に置き換えます。
    const mockUnitData = [
        {
            category_name: "教養教育科目",
            current_units: 22,
            max_units: 30, // 例: 卒業に必要な最大単位数
            shared_units: 0,
            color_class: 'color-blue' // ここで使う色クラスを指定
        },
        {
            category_name: "工学基礎科目",
            current_units: 16,
            max_units: 20,
            shared_units: 0,
            color_class: 'color-light-blue'
        },
        {
            category_name: "情報基礎科目",
            current_units: 12,
            max_units: 12, // 満タンの例
            shared_units: 0,
            color_class: 'color-light-blue'
        },
        {
            category_name: "情報応用科目",
            current_units: 4,
            max_units: 10,
            shared_units: 0,
            color_class: 'color-light-blue'
        },
        {
            category_name: "第1メジャー科目",
            current_units: 12,
            max_units: 20,
            shared_units: 5, // 共有単位の例
            color_class: 'color-cyan' // 通常のバーの色
        },
        {
            category_name: "第2メジャー科目",
            current_units: 4,
            max_units: 15,
            shared_units: 0,
            color_class: 'color-purple'
        },
        {
            category_name: "その他メジャー科目",
            current_units: 4,
            max_units: 10,
            shared_units: 0,
            color_class: 'color-light-green'
        },
        {
            category_name: "自由選択科目",
            current_units: 4,
            max_units: 10,
            shared_units: 0,
            color_class: 'color-blue' // 自由選択も青系にしてみる
        },
        {
            category_name: "卒業研究",
            current_units: 0,
            max_units: 6,
            shared_units: 0,
            color_class: 'color-light-blue' // 未達成なので色は見えないが指定
        }
    ];
    // --- ダミーデータここまで ---


    // まずは「読み込み中」メッセージをクリア
    unitDashboardContainer.innerHTML = '';

    // ダミーデータを元にUIを生成
    mockUnitData.forEach(data => {
        // カテゴリ全体のセクションを作成
        const categorySection = document.createElement('div');
        categorySection.classList.add('category-section');

        // カテゴリ名と単位数の情報を表示する部分
        const categoryInfo = document.createElement('div');
        categoryInfo.classList.add('category-info');
        categoryInfo.innerHTML = `
            <span class="category-name">${data.category_name}</span>
            <span class="unit-count">${data.current_units}</span>
            ${data.shared_units > 0 ? `<span class="sub-info">（共有科目：${data.shared_units}）</span>` : ''}
        `;
        categorySection.appendChild(categoryInfo);

        // プログレスバーのコンテナを作成
        const progressBarContainer = document.createElement('div');
        progressBarContainer.classList.add('progress-bar-container');
        categorySection.appendChild(progressBarContainer);

        // 各単位を表す小さなバー（segment）を生成して追加
        for (let i = 0; i < data.max_units; i++) {
            const segment = document.createElement('div');
            segment.classList.add('progress-bar-segment');

            if (i < data.current_units) {
                // 達成済みの単位に色を付ける
                segment.classList.add(data.color_class);

                // 第1メジャー科目で、かつ共有単位の範囲内であれば特殊な色を適用
                // ここはデータ構造によって調整が必要です。
                // 例えば、共有単位が最後のn個であると仮定しています。
                if (data.category_name === '第1メジャー科目' && data.shared_units > 0 && i >= (data.current_units - data.shared_units)) {
                     segment.classList.remove(data.color_class); // 通常の色を削除
                     segment.classList.add('color-dark-cyan'); // 共有単位用の色を適用
                }
            }
            progressBarContainer.appendChild(segment);
        }

        unitDashboardContainer.appendChild(categorySection);
    });

    // バックエンド連携時のフェッチ処理の例 (将来的に使用)
    /*
    fetch('your_backend_api_endpoint.php') // バックエンドのAPIエンドポイントを指定
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(unitData => {
            // データ取得成功時にUIを生成するロジックをここに記述
            // mockUnitData を unitData に置き換えて上記の forEach ループを使う
        })
        .catch(error => {
            console.error('Error fetching unit data:', error);
            unitDashboardContainer.innerHTML = '<p style="color: red; text-align: center;">単位データの読み込みに失敗しました。</p>';
        });
    */
});