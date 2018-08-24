var bebop = require('node-bebop');
var droneMove = require('../droneMove');

var drone = bebop.createClient();

drone.connect(function() {
    drone.on('ready', function(){
        setTimeout(function() {
            droneMove.droneStart(drone);
        }, 1000);

        setTimeout(function() {
            //droneMove.youOkay(drone);
            //droneMove.letsGo(drone);
            //droneMove.whatsUp(drone);
            droneMove.demo1(drone);
        }, 4000);

        setTimeout(function() {
            droneMove.droneStop(drone);
        }, 10000);
    });
});