var layerStyle = new ol.style.Style({
    stroke: new ol.style.Stroke({
        color: 'rgba(0,0,255,0.6)',
        width: 2
    }),
    fill: new ol.style.Fill({
        color: 'rgba(0,0,200,0.1)'
    })
});
var layerRed = new ol.style.Style({
    stroke: new ol.style.Stroke({
        color: 'rgba(255,0,0,0.6)',
        width: 2
    }),
    fill: new ol.style.Fill({
        color: 'rgba(200,0,0,0.1)'
    })
});

var projection = ol.proj.get('EPSG:3857');
var projectionExtent = projection.getExtent();
var size = ol.extent.getWidth(projectionExtent) / 256;
var resolutions = new Array(20);
var matrixIds = new Array(20);
for (var z = 0; z < 20; ++z) {
    // generate resolutions and matrixIds arrays for this WMTS
    resolutions[z] = size / Math.pow(2, z);
    matrixIds[z] = z;
}
var popup = new ol.Overlay.Popup();

/*
 * layer
 * EMAP2: 臺灣通用電子地圖透明
 * EMAP6: 臺灣通用電子地圖(不含等高線)
 * EMAP7: 臺灣通用電子地圖EN(透明)
 * EMAP8: Taiwan e-Map
 * PHOTO2: 臺灣通用正射影像
 * ROAD: 主要路網
 */

var mapLayers = [new ol.layer.Tile({
        source: new ol.source.WMTS({
            matrixSet: 'EPSG:3857',
            format: 'image/png',
            url: 'http://maps.nlsc.gov.tw/S_Maps/wmts',
            layer: 'LUIMAP',
            tileGrid: new ol.tilegrid.WMTS({
                origin: ol.extent.getTopLeft(projectionExtent),
                resolutions: resolutions,
                matrixIds: matrixIds
            }),
            style: 'default',
            wrapX: true,
            attributions: '<a href="http://maps.nlsc.gov.tw/" target="_blank">國土測繪圖資服務雲</a>'
        }),
        opacity: 0.3
    })];

var biLayer = new ol.layer.Vector({
    source: new ol.source.Vector({
        url: 'zones.json',
        format: new ol.format.GeoJSON()
    }),
    style: layerRed
});
mapLayers.push(biLayer);
var pointsLayer = new ol.layer.Vector({
    source: new ol.source.Vector({
        url: 'points.json',
        format: new ol.format.GeoJSON()
    }),
    style: new ol.style.Style({
      image: new ol.style.Circle({
        radius: 3,
        fill: new ol.style.Fill({
            color: 'black'
        }),
        stroke: new ol.style.Stroke({color: 'orange', width: 1})
      })
    })
});
mapLayers.push(pointsLayer);
var iaLayer = new ol.layer.Vector({
    source: new ol.source.Vector({
        url: 'ia.json',
        format: new ol.format.GeoJSON()
    }),
    style: layerStyle
});
mapLayers.push(iaLayer);
var map = new ol.Map({
    layers: mapLayers,
    target: 'map',
    controls: ol.control.defaults({
        attributionOptions: /** @type {olx.control.AttributionOptions} */ ({
            collapsible: false
        })
    }),
    view: new ol.View({
        center: ol.proj.fromLonLat([120.4782261982077, 24.085695695162585]),
        zoom: 14
    })
});
map.addOverlay(popup);
map.on('singleclick', onLayerClick);

function onLayerClick(e) {
    var message = '';
    map.forEachFeatureAtPixel(e.pixel, function (feature, layer) {
        var p = feature.getProperties();
        for (k in p) {
            if (k !== 'geometry') {
                message += k + ': ' + p[k] + '<br />';
            }
        }
    });
    if (message !== '') {
        popup.show(e.coordinate, message + ol.proj.transform(e.coordinate, 'EPSG:3857', 'EPSG:4326'));
        map.getView().setCenter(e.coordinate);
        map.getView().setZoom(16);
    }
}
