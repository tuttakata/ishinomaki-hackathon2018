var bebop = require('node-bebop');

var drone = bebop.createClient();

drone.connect(function() {
    drone.on('ready', function(){
        setTimeout(function() {
            drone.takeOff();
        }, 1000);

        setTimeout(function() {
            drone.land();
        }, 4000);
    });
});
