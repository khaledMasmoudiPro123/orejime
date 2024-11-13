## SUMMARY

Orejime is an open-source JavaScript library you can use on your website
to let users choose what third-party cookies they allow. Itâ€™s
specifically made to comply with the GDPR. Orejime is a fork of Klaro
that focuses on accessibility. It follows Web Content Accessibility
Guidelines (WCAG) via the French RGAA.

The final goal is to make certain scripts available only if the user
accepts it on a modal. This therefore prevents the creation of cookies.

This module is an implementation of this library. The objective is to provide
a back office allowing the administrator to directly manage the options of
this library (Texts, List of services…).

This implementation is sponsored by the GAYA web agency.

## REQUIREMENTS

This module requires the css file and js of following library:

  - [Orejime](https://github.com/empreinte-digitale/orejime)

  - We advise you to upgrade to the latest version: 2.3.1

## INSTALLATION

  - Install as you would normally install a contributed Drupal module.

<!-- end list -->

    Optional. A list of categories under which apps will be classified. This
    allows for a visual grouping of the different apps, along with a description
    of their purpose.

## COOKIES CONFIGURATION

The page /admin/content/orejime_service provides a list of cookies
lists (called a service) that a user can accept or reject. It is
possible to add or delete them. A service has different configurations :

    - Sytem name : The system name of the service. This parameter is used
    internally by Orejime and that serves as a link between the configuration
    and the scripts.
    - Label :  The title of you service as listed in the consent modal.
    - Description : The description of you service as listed in the consent
    modal.
    - Purposes : Allows you to add purposes to your service. Will be listed on
    the consent notice. To put several, just separate them with commas.
    - Cookies List : The list of cookies corresponding to this service. If the
    user withdraws consent for a given app, Orejime will then automatically
    delete all matching cookies.
    To get UA code, the token{ga} is available.
    - Scripts : The list of scripts depending on this service. These scripts
    will be played only if this service is accepted by the visitor.
    Each line corresponds to another file. Just put the name of the JS file
    (for example locale.translation.js). If several files have the same name it
    will suffice to put its full path (example: core / modules / locale /
    locale.translation.js)
    - Required : In order to make a service required. This parameter is mainly
    used for cookies strictly necessary for the operation of the site.
    - Enabled by default : Sets the default value for the service. If is set to
    true, the service will be accepted by default on the popin.
    - Published Status : Allows you to publish a service. If unchecked the
    service will not appear on the popup.

## FRONT CONFIGURATION

If you import directly javascript on your template, you need to add the
relationship between the service and the script.

  - For inline scripts, set the type attribute to opt-in to keep the
    browser from executing the script. Also add a data-name attribute
    with a short, unique, spaceless name for this script:
```
<script
     type="opt-in"
     data-type="application/javascript"
     data-name="$SYSTEM_NAME">
  $SCRIPT$
  </script>
```
  - For external scripts or img tags (for tracking pixels), do the same,
    and rename the src attribute to data-src:
```
    <script
     type="opt-in"
     data-type="application/javascript"
     data-name="$SYSTEM_NAME"
     data-src="$SCRIPT_URL"></script>
```
## IFRAME-CONSENT
If you enable the iframe-consent option in the box, you can force client
validation before launching the iframe.

You need to insert :
```
<iframe-consent
type="video"
src="$EMBED_URL"
poster="$IMAGE_URL"
title="$IFRAME_TITLE"
alt="$IFRAME_ALT"
></iframe-consent>
```
Currently specific configurations are created for Youtube and Dailymotion.
You need to create a service youtube and/or dailymotion to use them.
If you need to add more, please open an issue.
