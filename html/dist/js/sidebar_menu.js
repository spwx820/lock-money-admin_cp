
$(document).ready(function()
{
 $('.active').click(function(event){
   event.preventDefault();
  })
});


(function()
{
  var str = '\
{\
  "m1" : {"name" : "运营", "sub_menu" : \
                                                  {"m2" : {"name" : "运营", "url" : "/admin/audit/index"},\
                                                  "m3" : {"name" : "运营", "url" : "/admin/audit/index"},\
                                                  "m4" : {"name" : "运营", "url" : "/admin/audit/index"},\
                                                  "m5" : {"name" : "运营", "url" : "/admin/audit/index"},\
                                                  "m6" : {"name" : "运营", "url" : "/admin/audit/index"}}\
                                                },\
  "m7" : {"name" : "运营", "sub_menu" :\
                                                  {"m8" : {"name" : "运营", "url" : "/admin/audit/index"},\
                                                  "m9" : {"name" : "运营", "url" : "/admin/audit/index"},\
                                                  "m10" : {"name" : "运营", "url" : "/admin/audit/index"},\
                                                  "m11" : {"name" : "运营", "url" : "/admin/audit/index"},\
                                                  "m12" : {"name" : "运营", "url" : "/admin/audit/index"}}\
                                                }\
                                              }'

var data = JSON.parse(str);

var html = []
html.push('<li class="header">MAIN NAVIGATION</li>')
var key_in = 0
var tree_menu = ''
for(var ii = 1; ii < 100; ii += 1) {
    if (key_in == 0) {
        if ("m" + ii in data) {
            key_in = 1
            tree_menu = data["m" + ii].sub_menu

                //<span class="label label-primary pull-right">4</span>\

            html.push('\
                    <li class="treeview">\
                      <a href="#">\
                        <i class="fa fa-files-o"></i>\
                        <span>Layout Options</span>\
                        \
                      </a>\
                      <ul class="treeview-menu">')

        }
        else {
            break;
        }
    } else {

        if ("m" + ii in tree_menu) {
            html.push('<li><a href="../layout/top-nav.html"><i class="fa fa-circle-o"></i> Top Navigation</a></li>')
        }
        else {
            key_in = 0
            html.push('\
                      </ul>\
                    </li>')
            ii -= 1;
        }
    }
}

$("#main_menu").append(html.join(""));
}());


