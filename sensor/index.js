module.exports.getSensor = function(drone) {

// Load the node-omron-envsensor and get a `Envsensor` constructor object
const Envsensor = require('node-omron-envsensor');
// Create an `Envsensor` object
const envsensor = new Envsensor();
// `EnvsensorDevice` object
let device = null;

// センサーデータ格納配列
var array = new Array();
// ドローンがアクション中かどうか
var moving = false;
var line_para = 4;

var diff = 0;
// Initialize the `Envsensor` object
envsensor.init().then(() => {
  // Discover a device
  return envsensor.discover({quick:true});
}).then((device_list) => {
  if(device_list.length === 0) {
    throw new Error('No device was found.');
  }
  // `EnvsensorDevice` object representing the found device
  device = device_list[0];
  // Connect to the device
  return device.connect();
}).then(() => {
  // Set the measurement interval to 3 seconds
  return device.setBasicConfigurations({
    measurementInterval: 2
  });
}).then(() => {
  // Set a callback function to receive notifications
  device.onsensordata = (data) => {

    // 最新のセンサーデータをいくつか確保
    array.push(data["soundNoise"]);
    if (array.length > 4){
      array.shift();
    }

    // 音圧変化量を取得
    var ave1 = (array[0]+array[1])/2;
    var ave2 = (array[2]+array[3])/2;
    diff = ave1 - ave2;
    console.log(array);

    if (diff <= -30) {
      line_para = 1;
    } else if (diff >= 30) {
      line_para = 2;
    } else {
      var sum  = function(arr) {
          var sum = 0;
          arr.forEach(function(elm) {
              sum += elm;
          });
          return sum;
      };
      var average = function(arr, fn) {
        return sum(arr, fn)/arr.length;
      };
      noise_ave = average(array);

      if (noise_ave >= 40) {
        line_para = 3;
      } else {
        line_para = 4;
      }
    }

    if( (moving == false) & (!isNaN(diff)) ){
      console.log(diff);
      // ドローンへの動作指示
      const promise = new Promise((resolve, reject) => resolve((function () {
        return 'a';
      })()));
      promise.then((result) => console.log(result));
    }
  };


  // Start monitoring data
  return device.startMonitoringData();
}).then(() => {
  // Stop monitoring data and disconnect the device in 10 seconds
  setTimeout(() => {
    // Stop monitoring data
    device.stopMonitoringData().then(() => {
      // Disconnect the device
      return device.disconnect();
    }).then(() => {
      process.exit();
    });
  }, 100000);
}).catch((error) => {
  console.error(error);
});

return line_para;

}
