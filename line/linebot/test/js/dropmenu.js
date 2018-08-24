.dropmenu{
  *zoom: 1;
  list-style-type: none;
  width: 960px;
  margin: 5px auto 30px;
  padding: 0;
}
.dropmenu:before, .dropmenu:after{
  content: "";
  display: table;
}
.dropmenu:after{
  clear: both;
}
.dropmenu li{
  position: relative;
  width: 20%;
  float: left;
  margin: 0;
  padding: 0;
  text-align: center;
}
.dropmenu li a{
  display: block;
  margin: 0;
  padding: 15px 0 11px;
  background: #8a9b0f;
  color: #6e7c0c;
  font-size: 14px;
  line-height: 1;
  text-decoration: none;
}
.dropmenu li ul{
  list-style: none;
  position: absolute;
  z-index: 9999;
  top: 100%;
  left: 0;
  margin: 0;
  padding: 0;
}
.dropmenu li ul li{
  width: 100%;
}
.dropmenu li ul li a{
  padding: 13px 15px;
  border-top: 1px solid #7c8c0e;
  background: #6e7c0c;
  text-align: left;
}
.dropmenu li:hover > a{
  background: #6e7c0c;
}
.dropmenu li a:hover{
  background: #616d0b;
}
