/**
 * Created by JetBrains PhpStorm.
 * User: pp
 * Date: 3/17/11
 * Time: 9:00 PM
 */

/**
 * Update polygon data
 * @param element String
 * @param polygon google.maps.Polygon
 */
function updatePolyData(polygon, element) {
    var id = element || 'vertices';
    var el = $(id);
    var data = $H();
    polygon.getPath().forEach(function(ll, idx) {
        var item = {
            order: idx,
            lat: ll.lat(),
            lng: ll.lng()
        };
        data.set(idx, item);
    });
    if (data.size()) {
        var val = Object.toJSON(data.toObject());
        el.value = val;
    } else {
        el.value = '';
    }

    if(typeof opConfig != 'undefined'){
        opConfig.reloadPrice();
    }
}


/**
 * Utility method for loading JS script files
 *
 * @param src String
 */
function loadScript(src) {
    var script = document.createElement("script");
    script.type = "text/javascript";
    script.src = src;
    var head = $(document.body).previous('head');
    head.insert(script);
}

/**
 * Add marker to polygon vertex
 * @param polygon google.maps.Polygon
 * @param coord google.maps.LatLng
 */
function addMarker(polygon, coord, icon) {
    if (!polygon.marker) {
        var map = polygon.getMap();

        var opts = {
            map: map,
            title: 'Click to delete last line'
        };
        polygon.marker = new google.maps.Marker(opts);
        if(icon) {
            var image = new google.maps.MarkerImage(icon, new google.maps.Size(26, 23),
                                                    new google.maps.Point(0, 0),
                                                    new google.maps.Point(0, 20));
            polygon.marker.setIcon(image);
        }
        google.maps.event.addListener(polygon.marker, 'click', function() {
            var mark = polygon.marker;
            var poly = polygon;
            var path = poly.getPath();
            path.pop(); // remove last vertex
            mark.setPosition(path.getAt(path.getLength() - 1)); // move marker to new last position
            updatePolyData(poly, poly.storage);
        });
    }
    var marker = polygon.marker;
    marker.setPosition(coord);
}

function setSearch(map) {
    var search_container = new Element('div', {'class': 'search-container'});
    search_container.setStyle({
        padding: '5px auto',
        width: '250px'
                              });
    var sub = new Element('button', {type: 'button', className: 'button search-button'}).update
            ('<span><span>Search</span></span>');
    var input = new Element('input', {type: 'text', name: 'search', 'class': 'input-text search-input'});
    search_container.insert(input).insert(sub);
    map.controls[google.maps.ControlPosition.TOP_CENTER].push(search_container);
    var doSearch = function(e) {
        Event.stop(e);
        var el = input;
        var params = {};
        params.address = el.getValue();
        var geocoder = new google.maps.Geocoder();
        geocoder.geocode(params, function(results, status) {
            if (status != google.maps.GeocoderStatus.OK) {
                alert("Geocode was not successful for the following reason: " + status);
            }

            var result = results[0];

            map.setCenter(result.geometry.location);
            map.setZoom(16);
            var content = result.formatted_address + '<br/>\n';
            content += result.geometry.location;

            var infowindow = new google.maps.InfoWindow({
                                                            position: result.geometry.location,
                                                            content: content
                                                        });
            infowindow.open(map);
        });
    };
    sub.observe('click', doSearch);
    input.observe('keypress', function(e){
        if(e.keyCode == Event.KEY_RETURN) {
            doSearch(e);
            return false;
        }
    });
}

function addKml(kmls, value) {
    try{
      var result = '';
      if(kmls && kmls.size()){
        kmls.each(function(kml){
          if(result) {
            return;
          }
          var opt = kml.option.strip().unescapeHTML();
          var val = value.strip().unescapeHTML();
            var opt_obj = opt.evalJSON();
            var val_obj = val.evalJSON();
            opt = Object.toJSON(opt_obj);
            val = Object.toJSON(val_obj);
          if(opt == val){
            result = kml.kml;
          }
        });
      }
    }catch(e) {
      console.warn(e);
    }
    return result;
}


/**
 * Google maps for magento
 */

var Distromap = Class.create({
    config : {
        zoom : '',
        mapTypeId : '',
        center : '',
        element_id : '',
        storage: null
    },
    map: null,
    static: false, // should map be interactive
    initialize : function(config, static) {
        config = config || {};
        this.static = static || false;
        this.config.zoom = config.zoom || 15;
        this.config.mapTypeId = config.maptype
                || google.maps.MapTypeId.ROADMAP;
        this.config.element_id = config.element || 'map_canvas';
        this.config.storage = config.storage || 'options_1_file'; // we should really never have to use this default.
        var lat = 40.698470; //
        var lng = -73.951442; //
        var initialLocation = new google.maps.LatLng(lat, lng);
        this.config.center = initialLocation; // this.getUserLocation(this.map);

        this.map = new google.maps.Map($(this.config.element_id), {
            mapTypeId: this.config.mapTypeId,
            zoom: this.config.zoom,
            center: this.config.center
        });
        if(this.static) {
            if(typeof config.coords != 'undefined') {
                this.addStaticArea(config.coords);
            }
            return;
        }

        var polyOptions = {
            clickable: true,
            geodesic: true,
            strokeColor: '#ff0000',
            strokeWeight: 2,
            strokeOpacity: 0.8,
            map: this.map
        };
        var poly = new google.maps.Polygon(polyOptions);
        poly.storage = this.config.storage;
        google.maps.event.addListener(this.map, 'click', function(e) {
            poly.getPath().push(e.latLng);
            var icon = config.marker_icon;
            addMarker(poly, e.latLng, icon); // add marker to point of click
            updatePolyData(poly, poly.storage);
        });

        google.maps.event.addListener(poly, 'click', function() {
            if (confirm('Do you want to start over?')) {
                poly.getPath().clear();
                poly.marker.setMap(null);
                poly.marker = null;
                updatePolyData(poly, poly.storage);
            }
        });
        setSearch(this.map);
    },
    addStaticArea: function(coords){
        var polyOptions = {
            clickable: false,
            geodesic: true,
            strokeColor: '#ff0000',
            strokeWeight: 2,
            strokeOpacity: 0.8,
            map: this.map
        };
        var poly = new google.maps.Polygon(polyOptions);
        var path = [];
        var bounds = new google.maps.LatLngBounds();
        for (var i in coords) {
            var c = coords[i];
            var latlng = new google.maps.LatLng(c.lat, c.lng);
            bounds.extend(latlng);
            path.push(latlng);
        }
        poly.setPath(path);
        var center = bounds.getCenter();

        this.map.panToBounds(bounds);
        this.map.setCenter(center);
    },
    getUserLocation : function(map) {
        var nav = navigator;
        var browserSupportFlag = false;
        var lat = 40.698470; //
        var lng = -73.951442; //
        var initialLocation = new google.maps.LatLng(lat, lng);
        // Try W3C Geolocation (Preferred)
        if (nav.geolocation) {
            browserSupportFlag = true;
            nav.geolocation.getCurrentPosition(function(position) {
                lat = position.coords.latitude;
                lng = position.coords.longitude;
                initialLocation = new google.maps.LatLng(lat, lng);
                map.setCenter(initialLocation);
            }, function() {
                this.handleNoGeolocation(browserSupportFlag);
                map.setCenter(initialLocation);
            });
        } else {
            this.handleNoGeolocation(browserSupportFlag);
            map.setCenter(initialLocation);
        }
        console.info(initialLocation);
        return initialLocation;
    },
    handleNoGeolocation : function(errorFlag) {
        if (errorFlag == true) {
            console.warn("Geolocation service failed.");
        } else {
            console.warn("Your browser doesn't support geolocation. We've placed you in New York.");
        }
    },
    addHintOverlay: function(text, link_text) {
        var hint = new Element('div', {'class': 'usage-hint'}).update(text);
        hint.setStyle({
                        zIndex: 100,
                        width: '88%',
                        height: '88%',
                        background: '#ffffff',
                        padding: '2%',
                        position: 'absolute',
                        textAlign: 'left',
                        fontSize: '150%',
                        left: '4%',
                        top: '4%',
                        overflow: 'auto'
                      });
        link_text = link_text || 'Close';
        hint.setOpacity(0.8);
        hint.insert({bottom: '<a title="' + link_text +'" class="close-handle">' + link_text + '</a>'});

        $(this.config.element_id).insert({top: hint});
        var link = hint.down('a.close-handle');
        link.setStyle({
                        display: 'block',
                        cursor: 'pointer',
                        textAlign: 'center',
                        margin: '3em 0 0'
                      });
        hint.observe('click', function(e){
            hint.remove();
        });
        link.observe('click', function(e){
            if(hint.parentNode)
                hint.remove();
        });
    }
});

