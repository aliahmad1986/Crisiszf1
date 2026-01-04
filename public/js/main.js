function rtl() {
  var body = document.body;
  body.classList.toggle("rtl");
}

function dark() {
  var body = document.body;
  body.classList.toggle("dark");
}

function GetContent(url,presentation,data={}){
    $(presentation).html('<div class="spinner-border text-danger"></div>');
    $.ajax({
        url: url,
        cash: false,
        type: "POST",
        timeout: 120000, ///2 min sec
       data:data,
        error: function (x, t, m) {
            $(presentation).html('<div class="alert col-12  alert-third alert-shade alert-dismissible fade show" role="alert">'
            +'<strong>خطا در دریافت اطلاعات پیش آمده است.لطفا با واحد پشتیبانی تماس بگیرید</strong></div>');
        },
        success: function (data) {
            $(presentation).html(data);
        }
    });
}
function savemodalAddRow(url,form,table) {
  $.ajax({
      url: url,
      method: "POST",
      data: $(form).serialize(),
  })
          .done(function (html) {
              toastr.success(html, 'انجام عملیات', { timeOut: 5000 });
              var table = $(table).DataTable().ajax.reload();
          });
}


$(document).ready(function () {
  $("ul.a-collapse").click(function () {
    // console.log($(this).hasClass("short"));
    if ($(this).hasClass("short")) {
      $(".a-collapse").addClass("short");
      $(this).toggleClass("short");
      $(".side-item-container").addClass("hide animated");
      $("div.side-item-container", this).toggleClass("hide animated");
    } else {
      $(this).toggleClass("short");
      $("div.side-item-container", this).toggleClass("hide animated");
    }


  });

});

// var mixChart = document.getElementById('myChart5');
// var mixedChart = new Chart(mixChart, {
//   type: 'bar',
  
//   data: {
//     labels: ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد','شهریور', 'مهر', 'آیان', 'آذر', 'دی','بهمن','اسفند'],
//       datasets: [{
//           label: 'Bar Dataset',
//           data: [6,8,5,2,3,10,20,30,40,50],
//           backgroundColor: [
//             '#ff6384',
//             '#4bc0c0',
//             '#ffcd56',
//             '#c9cbcf',
//             '#36a2eb',
//             '#ff6384',
//             '#4bc0c0',
//             '#ffcd56',
//             '#c9cbcf',
//             '#36a2ea',
//             '#36a2ec',
//             '#36a2er',
//         ]
//       }, {
//           label: 'Line Dataset',
//           data: [8,12,6,3,5,12,20,30,44,60,10,25],

//           // Changes this dataset to become a line
//           type: 'line'
//       }],
//   },
//   options: {}
// });






