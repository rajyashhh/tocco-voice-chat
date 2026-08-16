<style>
    .jvectormap-container svg{
  height: 400px !important;
    }
</style>

<div class="card mt-4">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <strong>تحديد الدول المغطاة</strong>
        <button type="button" class="btn btn-sm btn-light" onclick="clearAllSelections()">
            <i class="fa fa-times"></i> إلغاء تحديد الكل
        </button>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <span class="badge" style="background-color: #4CAF50;">■</span> الدول التابعة لك
            <span class="badge" style="background-color: #e0e0e0; color: #333;">■</span> متاحة للتحديد
            <span class="badge" style="background-color: #B0BEC5; color: #fff;">■</span> محجوزة لمدير آخر
        </div>
        <div id="world-map" style="width: 100%; height: 480px;"></div>
        <hr>
        <div id="selected-countries-list" class="mt-2"></div>
    </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/jvectormap-next/jquery-jvectormap.css" rel="stylesheet">

<script>
function loadWorldMapScripts(callback) {
    const scripts = [
        'https://cdn.jsdelivr.net/npm/jvectormap-next/jquery-jvectormap.min.js',
        'https://cdn.jsdelivr.net/npm/jvectormap-content/world-mill.js'
    ];

    function loadSequentially(index = 0) {
        if (index >= scripts.length) return callback();
        $.getScript(scripts[index])
            .done(() => loadSequentially(index + 1))
            .fail((_, __, err) => {
                console.error('❌ فشل تحميل مكتبة:', scripts[index], err);
                location.reload();
            });
    }

    loadSequentially();
}

function initWorldMap() {
    console.log('%c[Map Init] ✅ تم تحميل الخريطة', 'color:#28a745;font-weight:bold;');

    const countries = {!! $countriesJson !!};
    const selectedCountries = {!! $selectedCountriesJson !!};
    const currentAreaManagerId = {{ $currentAreaManagerId ?? 'null' }};

    const countryMap = {};
    const regionColors = {};
    const myCountries = [];

    countries.forEach(country => {
        if (!country.iso2) return;
        const iso = country.iso2.toUpperCase();
        countryMap[iso] = country;

        const firstRegion = country.regions?.[0];
        const manager = firstRegion?.manager;

       // console.log(country);
       // console.log('mkkk');
       // console.log(manager);

        if (manager?.id === currentAreaManagerId && currentAreaManagerId != null) {
            regionColors[iso] = '#4CAF50'; 
            myCountries.push(iso);
        } else if (manager?.id && manager?.default !== 1) {
            regionColors[iso] = '#B0BEC5'; 
        } else {
            regionColors[iso] = '#e0e0e0';
        }
    });


    $('#world-map').empty();

    const $map = $('#world-map').vectorMap({
        map: 'world_mill',
        backgroundColor: '#f8f9fa',
        zoomOnScroll: false,
        regionStyle: {
            initial: {
                fill: '#e0e0e0',
                stroke: '#ffffff',
                "stroke-width": 1
            },
            hover: {
                "fill-opacity": 0.8,
                cursor: 'pointer'
            },
            selected: {
                fill: '#2196F3'
            }
        },
        series: {
            regions: [{
                values: regionColors,
                scale: {
                    '#e0e0e0': '#e0e0e0',
                    '#4CAF50': '#4CAF50',
                    '#B0BEC5': '#B0BEC5',
                    '#2196F3': '#2196F3'
                },
                normalizeFunction: 'polynomial'
            }]
        },
        
        onRegionClick: function(e, code) {
            const mapObj = $('#world-map').vectorMap('get', 'mapObject');
            const iso = code.toUpperCase();
            const country = countryMap[iso];

            if (!country) return;

            let managerId = null;
            let isDefaultManager = false;

            if (country.regions && country.regions.length) {
                const region = country.regions.find(r => r.manager && r.manager.id === currentAreaManagerId)
                            || country.regions[0]; // إذا لم يوجد، خذ أول منطقة
                if (region.manager) {
                    managerId = region.manager.id;
                    isDefaultManager = region.manager.default === 1;
                }
            }

            if (managerId && managerId !== currentAreaManagerId && !isDefaultManager) {
                e.preventDefault();
                if (typeof toastr !== 'undefined') toastr.warning('❌ لا يمكن تحديد هذه الدولة لأنها تابعة لمدير آخر.');
                return;
            }

            let selectedRegions = mapObj.getSelectedRegions();
            const isSelected = selectedRegions.includes(code);

            if (isSelected) {
                selectedRegions = selectedRegions.filter(c => c !== code);
                mapObj.clearSelectedRegions();
                mapObj.setSelectedRegions(selectedRegions);

                if (managerId === currentAreaManagerId) {
                    mapObj.series.regions[0].setValues({ [iso]: '#4CAF50' });
                } else {
                    mapObj.series.regions[0].setValues({ [iso]: '#e0e0e0' });
                }
            } else {
                selectedRegions.push(code);
                mapObj.setSelectedRegions(selectedRegions);
                mapObj.series.regions[0].setValues({ [iso]: '#2196F3' });
            }

            updateSelectedCountries(mapObj);
            updateCountryList(mapObj);
        },
        onRegionTipShow: function(e, el, code) {
      
            
            const c = countryMap[code.toUpperCase()];

            if (c) {
                const firstRegion = c.regions[0];
                const manager = firstRegion?.manager; 

                let text = `<strong>${c.name}</strong>`;

                if (manager?.id === currentAreaManagerId) {
                    text += `<br><small style="color:#4CAF50;font-weight:bold;">✅ تابعة لك بالفعل</small>`;
                } else if (manager?.id && manager?.default !== 1) {
                    text += `<br><small style="color:red;">❌ تابعة لمدير آخر</small>`;
                } else if (manager?.default === 1) {
                    text += `<br><small style="color:#2196F3;">📍 تابعة للمدير الافتراضي - يمكن اختيارها</small>`;
                } else {
                    text += `<br><small style="color:#666;">📍 متاحة للتحديد</small>`;
                }

                el.html(`<div style="padding:5px;">${text}</div>`);
            }
        }
    });

    const mapObj = $('#world-map').vectorMap('get', 'mapObject');

    if (myCountries.length > 0) {
        console.log('✅ تحديد الدول التابعة للمستخدم:', myCountries);
        mapObj.setSelectedRegions(myCountries);

        myCountries.forEach(iso => {
            mapObj.series.regions[0].setValues({ [iso]: '#2196F3' });
        });

        updateSelectedCountries(mapObj);
        updateCountryList(mapObj);
    }

    function updateSelectedCountries(mapObj) {
        const selected = mapObj.getSelectedRegions();
        const selectedData = selected.map(code => {
            const c = countryMap[code];
            return c ? { id: c.id, name: c.name, iso2: c.iso2 } : null;
        }).filter(Boolean);
        $('#covered-countries-input').val(JSON.stringify(selectedData));
    }

    function updateCountryList(mapObj) {
    const selected = mapObj.getSelectedRegions();
    const html = selected.map(code => {
        const c = countryMap[code];
        if (!c) return '';

        let managerId = null;
        let isDefaultManager = false;

        if (c.regions && c.regions.length) {
            const region = c.regions.find(r => r.manager && r.manager.id === currentAreaManagerId)
                         || c.regions[0];

            if (region && region.manager) {
                managerId = region.manager.id;
                isDefaultManager = region.manager.default === 1;
            }
        }

        let badgeColor = 'badge-secondary';
        if (managerId === currentAreaManagerId) {
            badgeColor = 'badge-success'; 
        } else if (isDefaultManager) {
            badgeColor = 'badge-info'; 
        } else {
            badgeColor = 'badge-primary'; 
        }

        return `
            <span class="badge ${badgeColor} m-1" style="cursor:pointer;" onclick="removeCountry('${code}')">
                ${c.name} <i class="fa fa-times"></i>
            </span>
        `;
    }).join('');

    $('#selected-countries-list').html(html || '<span class="text-muted">لم يتم اختيار دول بعد</span>');
}


    window.removeCountry = function(code) {
        const mapObj = $('#world-map').vectorMap('get', 'mapObject');
        const iso = code.toUpperCase();
        const country = countryMap[iso];

        const updatedRegions = mapObj.getSelectedRegions().filter(c => c !== code);
        mapObj.setSelectedRegions(updatedRegions);

        let managerId = null;
        let isDefaultManager = false;

        if (country && country.regions && country.regions.length) {
            const region = country.regions.find(r => r.manager && r.manager.id === currentAreaManagerId)
                        || country.regions[0]; 

            if (region && region.manager) {
                managerId = region.manager.id;
                isDefaultManager = region.manager.default === 1;
            }
        }

        if (managerId === currentAreaManagerId) {
            mapObj.series.regions[0].setValues({ [iso]: '#4CAF50' }); 
        } else if (isDefaultManager) {
            mapObj.series.regions[0].setValues({ [iso]: '#2196F3' }); 
        } else {
            mapObj.series.regions[0].setValues({ [iso]: '#e0e0e0' }); 
        }

        updateSelectedCountries(mapObj);
        updateCountryList(mapObj);
    };

    window.clearAllSelections = function() {
        const mapObj = $('#world-map').vectorMap('get', 'mapObject');
        const selectedRegions = mapObj.getSelectedRegions();

        selectedRegions.forEach(iso => {
            const country = countryMap[iso];
            if (!country) return;

            let managerId = null;
            let isDefaultManager = false;

            if (country.regions && country.regions.length) {
                const region = country.regions.find(r => r.manager && r.manager.id === currentAreaManagerId)
                            || country.regions[0];

                if (region && region.manager) {
                    managerId = region.manager.id;
                    isDefaultManager = region.manager.default === 1;
                }
            }

            if (managerId === currentAreaManagerId) {
                mapObj.series.regions[0].setValues({ [iso]: '#4CAF50' }); 
            } else if (isDefaultManager) {
                mapObj.series.regions[0].setValues({ [iso]: '#2196F3' }); 
            } else {
                mapObj.series.regions[0].setValues({ [iso]: '#e0e0e0' }); 
            }
        });

        mapObj.clearSelectedRegions();
        updateSelectedCountries(mapObj);
        updateCountryList(mapObj);

        console.log('🗑️ تم إلغاء تحديد جميع الدول');
    };
}

function reloadMapWhenFormOpens() {
    const mapElement = $('#world-map');

    if (!mapElement.length) {
        console.warn("world-map not found yet... retrying");
        setTimeout(reloadMapWhenFormOpens, 200); 
        return;
    }

    mapElement.empty();

    if (typeof $.fn.vectorMap === 'undefined') {
        loadWorldMapScripts(() => initWorldMap());
    } else {
        initWorldMap();
    }
}

$(document).ready(reloadMapWhenFormOpens);
$(document).on('pjax:complete', reloadMapWhenFormOpens);

</script>
