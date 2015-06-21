/**
 * Copyright (c) 2011-2012 Andreas Heigl<andreas@heigl.org>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
// Defining some markers
var lightIcon  = L.Icon.Default;
var darkIcon   = L.Icon.Default.extend({options: {iconUrl: '/img/marker-desat.png'}});
var redIcon    = L.Icon.Default.extend({options:{iconUrl: 'img/marker-icon-red.png'}});
var greenIcon  = L.Icon.Default.extend({options:{iconUrl: 'img/marker-icon-green.png'}});
var orangeIcon = L.Icon.Default.extend({options:{iconUrl: 'img/marker-icon-orange.png'}});
var grayIcon   = L.Icon.Default.extend({options:{iconUrl: 'img/marker-gray.png'}});
var numberedIcon = L.Icon.extend({
    options: {
// EDIT THIS TO POINT TO THE FILE AT http://www.charliecroom.com/marker_hole.png (or your own marker)
        iconUrl: 'img/marker-green.png',
        number: '',
        shadowUrl: null,
        iconSize: new L.Point(25, 41),
        iconAnchor: new L.Point(13, 41),
        popupAnchor: new L.Point(0, -33),
        /*
         iconAnchor: (Point)
         popupAnchor: (Point)
         */
        className: 'leaflet-div-icon'
    },

    createIcon: function () {
        var div = document.createElement('div');
        var img = this._createImg(this.options['iconUrl']);
        var numdiv = document.createElement('div');
        numdiv.setAttribute ( "class", "number" );
        numdiv.innerHTML = this.options['number'] || '';
        div.appendChild ( img );
        div.appendChild ( numdiv );
        this._setIconStyles(div, 'icon');
        return div;
    },

//you could change this to add a shadow like in the normal marker if you really wanted
    createShadow: function () {
        return null;
    }
});

var map = L.map('map');

var oms = new OverlappingMarkerSpiderfier(map, {keepSpiderfied: true});
oms.addListener('spiderfy', function(markers) {
    map.closePopup();
});

var redMarker = L.VectorMarkers.icon({
    icon: 'heart',
    markerColor: 'red'
});

var anthony = L.marker([42.4475,-71.2333], {icon: redMarker}).addTo(map);

var openstreetmap = L.tileLayer('http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap contributors</a>',
    maxZoom: 18
})
// Create a tile-server for Esri-Satellite images
var esriSatellite = L.tileLayer('http://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
    attribution: '<a href="http://www.esri.com/">Esri</a>, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP and the GIS User Community',
    maxZoom : 18
})

var phpug = L.layerJSON({
    url           : "/manage/api/json",
    propertyLoc   : ['latitude', 'longitude'],
    propertyTitle : 'name',
    buildPopup    : function (data) {
        var content = '<div class="popup">'
                + '<h4>'
                + '%name%'
                + '</h4>';
        if (data['message']) {
            content = content + '<h5>Message</h5>'
            + data['message'];
        }
        content = content +'</div>'
            ;

        var contact = '<a href="%url%" title="%value%" target="_blank">'
            + '<i class="%cssClass%"></i>'
            + '</a>';
        var contacts = [];

        if (data.icalendar_url) {
            contacts.push(
                contact.replace(/%type%/, 'icalendar')
                    .replace(/%url%/, data.icalendar_url)
                    .replace(/%value%/, 'iCal-File')
                    .replace(/%cssClass%/, 'fa-calendar fa')
            );
        }

        for (i in data.contacts) {
            cont = data.contacts[i];
            contacts.push(
                contact.replace(/%type%/, cont.type.toLowerCase())
                    .replace(/%url%/, cont.url)
                    .replace(/%value%/, cont.name)
                    .replace(/%cssClass%/, cont.cssClass)
            );
        }
        if (data.edit) {
            var edit = '<a href="ug/edit/' + data.shortname + '" title="Edit"><i class="fa fa-edit"></i></a>';
            contacts.push(edit);
        }
        contacts = contacts.join('</li><li>');
        if (contacts) {
            contacts = '<ul><li>' + contacts + '</li></ul>';
        }
        content = content.replace(/%url%/, data.url)
            .replace(/%name%/, data.name)
            .replace(/%shortname%/, data.shortname)
            .replace(/%contacts%/, contacts);

        if (center && center === data.shortname){
            map.setView(new L.LatLng(data.latitude,data.longitude), 8);
        }
        return content;
    },
    filterData : function(e){
        return e.groups;
    },
    buildIcon : function(data){
        console.log(data);
        if (data.state == 0) {
            return new darkIcon();
        }
        if (data.state == 2) {
            return new grayIcon();
        }
        return new L.Icon.Default;
    },
    onEachMarker  : function(e, marker){
        oms.addMarker(marker);
        marker.bindLabel(e.name, {opacity:0.9});

        var latlngs = Array();

        //Get latlng from first marker
        latlngs.push(marker.getLatLng());

        //Get latlng from first marker
        latlngs.push(anthony.getLatLng());
        var polyline = L.polyline(latlngs, {color: 'darkblue'}).addTo(map);

        return;
    }
});

map.on('popupopen', function(p){
    var shortname = p.popup.getContent().match(/"next_event_([^"]+)"/)[1];
    if (! shortname){
        return false;
    }
    pushNextMeeting(p, shortname);
    return true;
});

var getQueryParameter = function(variable) {
    var query = window.location.search.substring(1);
    var vars = query.split('&');
    for (var i = 0; i < vars.length; i++) {
        var pair = vars[i].split('=');
        if (decodeURIComponent(pair[0]) == variable) {
            return decodeURIComponent(pair[1]);
        }
    }

    return false;
}

var getUriGeolocation = function(){
    try {
        var obj = {
            lat : null,
            lng : null,
            zoom: 8
        };
        obj.lat = getQueryParameter('lat');
        obj.lng = getQueryParameter('lng');
        var mZoom = getQueryParameter('zoom');
        if (mZoom) obj.zoom = mZoom;
        if (!obj.lat || !obj.lng) return false;

        return obj;
    }catch(e){
        return false;
    }
}

var center = getQueryParameter('center');
var coord = new L.LatLng(0,0);
var zoom  = 2;
var loc = getUriGeolocation();
if(false !== loc) {
    coord.lat = loc.lat;
    coord.lng = loc.lng;
    zoom      = loc.zoom;
} else if($.cookie("map")){
    var mp = $.parseJSON($.cookie('map'));
    coord.lat = mp.lat;
    coord.lng = mp.lng;
    zoom      = mp.zoom;
}else{
    navigator.geolocation.getCurrentPosition(function(position){
        coord.lat = position.coords.latitude;
        coord.lng = position.coords.longitude;
        zoom = 8;
        map.setView(coord, zoom);
        return true;
    },function(){
        return true;
    },{timeout:3000});
}

map.setView(coord, zoom)
    .addLayer(openstreetmap);

L.control.layers({
    'OpenStreetMap' : openstreetmap,
    'Satellite'  : esriSatellite
},{
    'PHP-Usergroups' : phpug,
},{
    'position' : 'bottomleft'
}).addTo(map);

switch(window.location.hash) {
    case '#mentoring':
        map.addLayer(mentoring);
        break;
    case '#events':
    case '#event':
    case '#joindin':
    case '#joind.in':
        map.addLayer(joindin);
        break;
    case '#cfp':
        map.addLayer(cfp);
        break;
    default:
        map.addLayer(phpug);
        break;
}

var oms = new OverlappingMarkerSpiderfier(map, {keepSpiderfied: true});
oms.addListener('spiderfy', function(markers) {
    map.closePopup();
});
oms.clearListeners('click');
oms.addListener('click', function(marker) {
    var info = getContent(marker);
    popup.setContent(info.desc);
    popup.setLatLng(marker.getLatLng());
    map.openPopup(popup, info.shortname);
});

map.addControl( new L.Control.Search({
    url: 'http://nominatim.openstreetmap.org/search?format=json&q={s}',
    jsonpParam: 'json_callback',
    propertyName: 'display_name',
    propertyLoc: ['lat','lon'],
    circleLocation: true,
    markerLocation: false,
    autoType: false,
    autoCollapse: true,
    minLength: 2,
    zoom:10
}) );


var pushNextMeeting = function(popup, id)
{
    $.ajax({
        type: 'POST',
        url: "/api/v1/usergroup/next-event/" + id,
        dataTpye: 'json',
        context : {'id':id, 'popup':popup},
        success : function(a){
            var content='<h6><a href="%url%">%title%</a></h6><dl title="%description%"><dt>Starts</dt><dd>%startdate%</dd><dt>Ends</dt><dd>%enddate%</dd><dt>Location:</dt><dd>%location%</dd></dl>';
            content = content.replace(/%url%/g, a.url)
                .replace(/%title%/g, a.summary)
                .replace(/%startdate%/g, a.start)
                .replace(/%enddate%/g, a.end)
                .replace(/%description%/g, a.description)
                .replace(/%location%/g, a.location)
            ;
            $('#next_event_' + this.id).html(content);
            var newContent = $('#next_event_' + this.id).closest('.popup').html();
            this.popup.setContent(newContent);
            this.popup.update();
        },
        error : function(a){
            $('#next_event_' + this.id).html('Could not retrieve an event.');
            var newContent = $('#next_event_' + this.id).parent('.popup').html();
            this.popup.setContent(newContent);
            this.popup.update();
        }
    })
}

window.onbeforeunload = function(e){
    $.cookie("map", JSON.stringify({lat:map.getCenter().lat, lng:map.getCenter().lng, zoom:map.getZoom()}));
};
