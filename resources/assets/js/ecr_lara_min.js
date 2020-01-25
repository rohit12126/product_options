(function($) {
  var ecl;
  var config;
  var ecl = {
    _callApi: function(
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
        contentType: "application/json",
        cache: false,
        processData: false,
        ...headers,
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

    setApiKey: function(value) {
      if ("" != value && value) {
        config.apiKey = value;
      }
    },

    setApiHost: function(value) {
      if ("" != value && value) {
        config.apiHost = value;
      }
    },

    setSiteId: function(value) {
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
        todayHighlight:true,
        startDate: today
      });
    },
    handleAddressBlockChange: $form => {
      $form.on('change', '.return-address', function () {
        if (this.checked) {
          $("#addressBlock").hide();
        }else{
          $("#addressBlock").show();
        }
      });   
    }
  };

  const productOption = {
    __init: () => {
      const $form = $("#productOptionForm");
      productOptionHandler.handleDatePicker();
      productOptionHandler.handleAddressBlockChange($form);
    }
  };
  window.productOption = productOption;

})(jQuery);
