(function ($, Drupal) {
  "use strict";

  const appCallback = (consent, app) => {
    document.dispatchEvent(new CustomEvent('orejimeAppCallback', { detail: { consent, app: app.name } }))
  }

  Drupal.behaviors.orejime = {
    attach: function (context, settings) {
      if(settings.orejime){
        window.orejimeConfig = OrejimeConfig.init(context, settings);
      }
      if( document.querySelector('.consent-modal-button')){
        document.querySelector('.consent-modal-button').addEventListener('click', function () {
          orejime.show();
        }, false);
      }
      if( document.querySelector('.reset-button')) {
        document.querySelector('.reset-button').addEventListener('click', function () {
          orejime.internals.manager.resetConsent();
          location.reload();
        }, false);
      }
    }
  };

  var OrejimeConfig = {
    init: function (context, settings) {
      var lang = settings.orejime.language;
      var config = {
        elementID: 'orejime',
        // appElement: "#app",
        lang: lang,
        cookieName: settings.orejime.cookie_name,
        cookieExpiresAfterDays: settings.orejime.expires_after_days,
        cookieDomain: settings.orejime.cookie_domain,
        mustConsent: settings.orejime.must_consent,
        mustNotice: settings.orejime.must_notice,
        privacyPolicy: settings.orejime.privacy_policy,
        default: true,
        translations: {},
        logo: settings.orejime.logo,
        debug: settings.orejime.debug,
        apps: [],
      };
      config['translations'][lang] = $.extend({}, settings.orejime.texts, {'purposes': {}});
      $.each(settings.orejime.purposes, function (index, value) {
        config['translations'][lang]['purposes'][value] = value;
      });

      $.each(settings.orejime.manage, function (index, value) {
        config['translations'][lang][index] = {'description': value.description};
        config['apps'].push({
          name: value.name,
          title: value.label,
          purposes: value.purposes,
          cookies: value.cookies,
          required: parseInt(value.required),
          default: parseInt(value.default),
          callback: appCallback,
        });
      });
      if (settings.orejime.categories !== null && settings.orejime.categories.length != 0) {
        config['categories'] = {};
        var cat = [];
        var description = [];
        $.each(settings.orejime.categories, function (index, value) {
          var apps = []
          var c = 1;
          $.each(value.apps, function (i, v) {
            if (v !== 0) {
              apps[c] = v;
            }
            c++;
          });
          cat.push({
            name: value.name,
            title: value.title,
            apps: apps,
          });
          if (value.description !== '') {
            description[value.name] = {'description': value.description};
          }
        });
        config['categories'] = cat;
        config['translations'][lang]['categories'] = description;
      }

      return config;
    }
  };

})(jQuery, Drupal);
