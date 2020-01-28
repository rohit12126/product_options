(function ($) {

  $.ajaxSetup({
    cache: false,
    beforeSend: function (xhr) {

      xhr.setRequestHeader('X-CSRF-TOKEN', $('meta[name="csrf-token"]').attr('content'));
    }
  });

  var ecl;
  var config;
  var ecl = {
    _callApi: function (
      urn,
      method,
      data,
      callback,
      erroCallback = undefined,
      headers = {}
    ) {
      $.ajax({
        url: urn,
        method: method,
        data: data,
        cache: false,
        headers: headers,
        success: eval(callback), // call back function and it returns to relevent function
        error: erroCallback
      });
    }
  };
  window.ecl = ecl;

  /*
   * Config is used for set APIKEY, APIHOST and SiteId
   * @file config.js
   */

  config = {
    format: "html",
    apiKey: "",
    apiHost: "",
    siteId: 2,

    setApiKey: function (value) {
      if ("" != value && value) {
        config.apiKey = value;
      }
    },

    setApiHost: function (value) {
      if ("" != value && value) {
        config.apiHost = value;
      }
    },

    setSiteId: function (value) {
      if ("" != value && value) {
        config.siteId = value;
      }
    },
    // @Sonu: to use throughout the application
    acceptedUploadFileFormats: [
      "doc",
      "docx",
      "pub",
      "ppt",
      "pptx",
      "jpeg",
      "jpg",
      "tif",
      "tiff",
      "pdf"
    ]
  };
  window.config = config;
  // @Sonu: Handle methods related to design uploader
  const printErrors = (xhr, element) => {
    const message = [];
    if (xhr.responseJSON) {
      if (xhr.responseJSON.errors) {
        for (const k in xhr.responseJSON.errors) {
          if (xhr.responseJSON.errors.hasOwnProperty(k)) {
            message.push(xhr.responseJSON.errors[k]);
          }
        }
      } else {
        message.push(xhr.responseJSON.message);
      }
    } else {
      message.push(xhr.responseText);
    }
    element.text(message);
  };

  //For Product Option
  const productOptionHandler = {

    handleDatePicker: () => {
      var date = new Date();
      var today = new Date(date.getFullYear(), date.getMonth(), date.getDate());
      $('.datepicker').datepicker({
        format: 'dd/mm/yyyy',
        todayHighlight: true,
        startDate: today,
        setDate: today,
      });
    },
    handleAddressBlockChange: $form => {
      $form.on('change', '.return-address', function () {
        if (this.checked) {
          $("#addressBlock").hide();
        } else {
          $("#addressBlock").show();
        }
      });
    },
    selectStockoption: $form => {
      $form.on('change', '.stock_option', function () {
        var data = {};
        data.id = $(this).val();
        console.log(data);
        ecl._callApi(
          'setStockOption', 'POST', data, function (response) {
            $(".bindery-option-block").show();
            $(".bindery-option-block").empty().append(response);
          }, function (response) {
            console.log(response);
          },
        )
      });
    },
    shippedProof: $form => {
      $form.on('change', '.shipped_proof', function () {
        var data = {};
        var checkbox = $('[name="shipped_proof"]');
        if (checkbox.is(':checked')) {
          data.id = '1';
        } else {
          data.id = '0';
        }
        ecl._callApi(
          'addProof', 'POST', data, function (response) {
            console.log(response);
          }, function (response) {
            console.log(response);
          }
        )
      });
    }


  };

  const productOption = {
    __init: () => {
      const $form = $("#productOptionForm");
      productOptionHandler.handleDatePicker();
      productOptionHandler.handleAddressBlockChange($form);
      productOptionHandler.selectStockoption($form);
      productOptionHandler.shippedProof($form);
    }
  };
  window.productOption = productOption;

})(jQuery);
