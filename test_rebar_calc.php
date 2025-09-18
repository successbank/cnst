<!DOCTYPE html>
<html>
<head>
    <title>철근 계산 테스트</title>
</head>
<body>
    <h1>철근 계산 테스트 (D10)</h1>
    
    <form>
        <label>길이: 
            <select id="length" onchange="calculate()">
                <option value="">선택</option>
                <option value="6.0">6.0m (300본/번들, 1008kg/번들)</option>
                <option value="8.0">8.0m (210본/번들, 941kg/번들)</option>
                <option value="10.0">10.0m (180본/번들, 1008kg/번들)</option>
            </select>
        </label><br><br>
        
        <label>번들 수량: 
            <input type="number" id="quantity" value="1" onchange="calculate()">
        </label><br><br>
        
        <div id="result" style="margin-top: 20px; padding: 20px; background: #f0f0f0;"></div>
    </form>

    <script>
    const bundleData = {
        "6.0": {bd_count: 300, bd_weight: 1008},
        "8.0": {bd_count: 210, bd_weight: 941},
        "10.0": {bd_count: 180, bd_weight: 1008}
    };
    
    function calculate() {
        const length = document.getElementById('length').value;
        const quantity = parseInt(document.getElementById('quantity').value) || 0;
        
        console.log('Length:', length, 'Quantity:', quantity);
        
        if (length && quantity && bundleData[length]) {
            const bundleInfo = bundleData[length];
            const totalBundles = quantity;
            const totalPieces = totalBundles * bundleInfo.bd_count;
            const totalWeight = totalBundles * bundleInfo.bd_weight;
            
            document.getElementById('result').innerHTML = `
                <h3>계산 결과:</h3>
                <p>총 번들 수: ${totalBundles}번들</p>
                <p>총 본수: ${totalPieces}본</p>
                <p>총 중량: ${totalWeight}kg</p>
            `;
        } else {
            document.getElementById('result').innerHTML = '<p>길이와 수량을 입력하세요.</p>';
        }
    }
    
    // 초기 계산
    calculate();
    </script>
</body>
</html>