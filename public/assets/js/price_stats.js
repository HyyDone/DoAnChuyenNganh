
document.addEventListener('DOMContentLoaded', function () {
    initComparisonChart();

    
    
    loadCity('Hà Nội');
});


async function initComparisonChart() {
    const canvasHanoi = document.getElementById('chartHanoi');
    const canvasHCM = document.getElementById('chartHCM');

    if (!canvasHanoi || !canvasHCM) {
        console.error("Chart canvases not found");
        return;
    }

    const ctxHanoi = canvasHanoi.getContext('2d');
    const ctxHCM = canvasHCM.getContext('2d');

    try {
        const response = await fetch('/api/price_stats.php?action=chart_data');
        const result = await response.json();

        if (result.status !== 'success') {
            console.error('API Error:', result.message);
            return;
        }

        const data = result.data;

        
        const months = [];
        const today = new Date();
        for (let i = 5; i >= 0; i--) {
            const d = new Date(today.getFullYear(), today.getMonth() - i, 1);
            months.push('T' + (d.getMonth() + 1));
        }

        
        const getIsoMonth = (offset) => {
            const d = new Date(today.getFullYear(), today.getMonth() - offset, 1);
            const year = d.getFullYear();
            const month = String(d.getMonth() + 1).padStart(2, '0');
            return `${year}-${month}`;
        };
        const isoMonths = [5, 4, 3, 2, 1, 0].map(i => getIsoMonth(i));

        
        const processCityData = (cityName, cityUtf8) => {
            let lastValue = null;

            
            const latest = data.find(item => item.city === cityName || item.city === cityUtf8);
            if (latest) lastValue = parseFloat(latest.avg_price_per_m2);

            return isoMonths.map(m => {
                const found = data.find(item => item.month === m && (item.city === cityName || item.city === cityUtf8));
                if (found) {
                    lastValue = parseFloat(found.avg_price_per_m2);
                    return lastValue;
                }
                return lastValue;
            });
        };

        const hanoiData = processCityData('Ha Noi', 'Hà Nội');
        const hcmData = processCityData('Ho Chi Minh', 'Hồ Chí Minh');

        
        function createGradient(ctx, colorStart, colorEnd) {
            const gradient = ctx.createLinearGradient(0, 0, 0, 400);
            gradient.addColorStop(0, colorStart);
            gradient.addColorStop(1, colorEnd);
            return gradient;
        }

        const hanoiGradient = createGradient(ctxHanoi, 'rgba(40, 167, 69, 0.5)', 'rgba(40, 167, 69, 0.0)');
        const hcmGradient = createGradient(ctxHCM, 'rgba(0, 123, 255, 0.5)', 'rgba(0, 123, 255, 0.0)');

        
        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(255, 255, 255, 0.9)',
                    titleColor: '#333',
                    bodyColor: '#333',
                    borderColor: '#ddd',
                    borderWidth: 1,
                    displayColors: false,
                    callbacks: {
                        label: function (context) {
                            if (context.parsed.y !== null) {
                                return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(context.parsed.y);
                            }
                            return '';
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        borderDash: [5, 5],
                        color: '#eee'
                    },
                    title: { display: true, text: 'Triệu/m²' },
                    ticks: {
                        callback: function (value) {
                            return value / 1000000;
                        }
                    }
                }
            }
        };

        
        new Chart(ctxHanoi, {
            type: 'line',
            data: {
                labels: months,
                datasets: [{
                    label: 'Hà Nội',
                    data: hanoiData,
                    borderColor: '#28a745',
                    backgroundColor: hanoiGradient,
                    borderWidth: 2,
                    pointRadius: 4,
                    pointBackgroundColor: '#28a745',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: commonOptions
        });

        
        new Chart(ctxHCM, {
            type: 'line',
            data: {
                labels: months,
                datasets: [{
                    label: 'TP. Hồ Chí Minh',
                    data: hcmData,
                    borderColor: '#007bff',
                    backgroundColor: hcmGradient,
                    borderWidth: 2,
                    pointRadius: 4,
                    pointBackgroundColor: '#007bff',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: commonOptions
        });

    } catch (error) {
        console.error('Error fetching chart data:', error);
    }
}


let map;
let layerGroup;


const DISTRICT_COORDS = {
    
    'Ba Đình': [21.0341, 105.8152],
    'Hoàn Kiếm': [21.0285, 105.8542],
    'Tây Hồ': [21.0667, 105.8167],
    'Long Biên': [21.0333, 105.9000],
    'Cầu Giấy': [21.0333, 105.7833],
    'Đống Đa': [21.0167, 105.8333],
    'Hai Bà Trưng': [21.0000, 105.8500],
    'Hoàng Mai': [20.9667, 105.8500],
    'Thanh Xuân': [20.9933, 105.8133],
    'Nam Từ Liêm': [21.0125, 105.7608],
    'Bắc Từ Liêm': [21.0667, 105.7667],
    'Hà Đông': [20.9608, 105.7697],

    
    'Quận 1': [10.7769, 106.7009],
    'Quận 3': [10.7788, 106.6859],
    'Quận 4': [10.7588, 106.7032],
    'Quận 5': [10.7540, 106.6634],
    'Quận 6': [10.7490, 106.6341],
    'Quận 7': [10.7340, 106.7218],
    'Quận 8': [10.7230, 106.6264],
    'Quận 10': [10.7720, 106.6669],
    'Quận 11': [10.7634, 106.6502],
    'Quận 12': [10.8672, 106.6466],
    'Bình Thạnh': [10.8106, 106.7091],
    'Thủ Đức': [10.8499, 106.7656],
    'Gò Vấp': [10.8387, 106.6653],
    'Phú Nhuận': [10.7992, 106.6805],
    'Tân Bình': [10.8015, 106.6526],
    'Tân Phú': [10.7915, 106.6266],
    'Bình Tân': [10.7651, 106.6038],
    'Bình Chánh': [10.6869, 106.5937],
    'Nhà Bè': [10.6953, 106.7328],

    
    'Hải Châu': [16.0667, 108.2167],
    'Thanh Khê': [16.0601, 108.1887],
    'Sơn Trà': [16.0833, 108.2333],
    'Ngũ Hành Sơn': [16.0333, 108.2500],
    'Liên Chiểu': [16.0833, 108.1333],
    'Cẩm Lệ': [16.0142, 108.1869]
};

const CITY_CENTERS = {
    'Hà Nội': [21.0285, 105.8542],
    'Hồ Chí Minh': [10.7769, 106.7009],
    'Đà Nẵng': [16.0544, 108.2022]
};

function initMap() {
    if (!map) {
        map = L.map('rentalHeatmap').setView(CITY_CENTERS['Hà Nội'], 11);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        layerGroup = L.layerGroup().addTo(map);
    }
}

async function loadCity(cityName) {
    initMap();

    
    document.querySelectorAll('.city-btn').forEach(btn => btn.classList.remove('active'));
    if (cityName === 'Hà Nội') document.getElementById('btn-hanoi').classList.add('active');
    if (cityName === 'Đà Nẵng') document.getElementById('btn-danang').classList.add('active');
    if (cityName === 'Hồ Chí Minh') document.getElementById('btn-hcm').classList.add('active');

    
    const center = CITY_CENTERS[cityName];
    if (center) {
        map.setView(center, 12);
    }

    
    layerGroup.clearLayers();

    try {
        const response = await fetch(`/api/price_stats.php?action=heatmap_data&city=${encodeURIComponent(cityName)}`);
        const result = await response.json();

        if (result.status === 'success') {
            result.data.forEach(item => {
                
                
                
                let coords = null;
                const distName = item.district;

                
                if (DISTRICT_COORDS[distName]) {
                    coords = DISTRICT_COORDS[distName];
                } else {
                    
                    
                    for (const key in DISTRICT_COORDS) {
                        if (distName.includes(key) || key.includes(distName)) {
                            coords = DISTRICT_COORDS[key];
                            break;
                        }
                    }
                }

                if (coords) {
                    const price = parseFloat(item.avg_price_per_m2);
                    let color = '#28a745'; 
                    if (price > 5000000) color = '#ffc107'; 
                    if (price > 10000000) color = '#dc3545'; 

                    const formattedPrice = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(price);

                    L.circle(coords, {
                        color: color,
                        fillColor: color,
                        fillOpacity: 0.5,
                        radius: 800 
                    })
                        .bindPopup(`<b>${distName}</b><br>Giá TB: ${formattedPrice}`)
                        .addTo(layerGroup);
                }
            });
        }
    } catch (e) {
        console.error("Heatmap error", e);
    }
}
