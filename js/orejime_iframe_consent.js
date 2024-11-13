(function ($, Drupal, drupalSettings) {
  "use strict";

  class IframeConsent extends HTMLElement {
    #provider = undefined
    #cmp = ''
    #cmpCallbackSet = false

    constructor() {
      super()

      const poster = this.getAttribute('poster') || ''
      const noConsent = this.hasAttribute('no-consent')
      const title = this.getAttribute('title') || ''
      // suppression de l'attribut title, dont le contenu est désormais transféré dans le DOM
      this.removeAttribute('title')
      const alt = this.getAttribute('alt') || ''

      this.#cmp = this.getCmpName()
      this.#provider = this.getProvider()

      this.template = document.createElement('template')

      this.template.innerHTML = `
      <style>
         ` +  drupalSettings.css_iframe_content +  `
      </style>
      <button class="iframe-poster">
        <img src="${poster}" alt="${alt}" class="iframe-img">
        ${title ? `<span class="iframe-title"><svg class="iframe-icon" xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 384 512"><path d="M73 39c-14.8-9.1-33.4-9.4-48.5-.9S0 62.6 0 80V432c0 17.4 9.4 33.4 24.5 41.9s33.7 8.1 48.5-.9L361 297c14.3-8.7 23-24.2 23-41s-8.7-32.2-23-41L73 39z"/></svg>${title}</span>` : ''}
      </button>
    `

      // On crée le shadow DOM
      this.attachShadow({ mode: 'open' })
      this.shadowRoot.appendChild(this.template.content.cloneNode(true))

      this.button = this.shadowRoot.querySelector('.iframe-poster')
      this.button.addEventListener('click', () => {
        if (!this.hasConsent(noConsent)) {
          return this.showBanner()
        }
        return this.setIframe()
      })
    }

    showBanner() {
      // On crée la bannière de consentement
      this.banner = document.createElement('div')
      this.banner.setAttribute('class', 'iframe-consent')

      if (this.#cmp) {
        this.banner.textContent = Drupal.t('Your consent preferences for %s do not allow you to access this content.').replace('%s', this.#provider)
      } else {
        this.banner.textContent = Drupal.t('By clicking on \"Continue\", you accept that the content provider (%s) may store cookies or trackers on your navigator.').replace('%s', this.#provider)
      }

      // On ajoute le bouton à la bannière
      const accept = document.createElement('button')
      if (this.#cmp) {
        accept.textContent = Drupal.t('Change your consent preferences')
        accept.addEventListener('click', () => this.openCmp().then(() => this.setIframe()))
      } else {
        accept.textContent = Drupal.t('Continue')
        accept.addEventListener('click', () => this.setIframe())
      }
      this.banner.appendChild(accept)

      this.shadowRoot.appendChild(this.banner)
    }

    setIframe() {
      if (this.banner) {
        this.banner.style.display = 'none'
      }
      this.button.style.display = 'none'

      const iframe = document.createElement('iframe')
      iframe.setAttribute('src', this.getAttribute('src'))
      iframe.setAttribute('frameborder', '0')
      iframe.setAttribute('allowfullscreen', '')
      iframe.setAttribute('allow', 'autoplay; encrypted-media; gyroscope')
      iframe.setAttribute('autoplay', '')

      this.shadowRoot.appendChild(iframe)
    }

    /**
     * @return {string}
     */
    getProvider() {
      let hostname = this.getAttribute('provider');
      if (this.#cmp === 'orejime') {
        const manager = window.orejime.internals.manager
        if (manager.getApp(hostname)) {
          return hostname
        } else {
          // la cmp ne gère pas ce provider, on repasse en mode standard
          this.#cmp = ''
          return Drupal.t('unknown')
        }
      }

      return hostname ||  Drupal.t('unknown')
    }

    hasConsent(noConsent) {
      if (noConsent) {
        return true
      }

      if (this.#cmp === 'orejime') {
        const manager = window.orejime.internals.manager
        return manager.getConsent(this.#provider)
      }

      return false
    }

    getCmpName() {
      if (window.orejime) {
        return 'orejime'
      }

      return ''
    }

    openCmp() {
      if (this.#cmp === 'orejime') {
        return new Promise((resolve) => {
          if (!this.#cmpCallbackSet) {
            document.addEventListener('orejimeAppCallback', (e) => {
              if (e.detail.app === this.#provider && e.detail.consent) {
                resolve()
              }
            })
            this.#cmpCallbackSet = true
          }
          window.orejime.show()
        })
      }

      return Promise.resolve(false)
    }
  }

  /**
   * @param {string} uri
   * @param {boolean} [keepExtension]
   * @return {string}
   */
  function canonicalHostname(uri, keepExtension = true) {
    uri = uri.trim().toLowerCase()
    if (uri === '' || !uri.includes('.')) {
      return ''
    }
    if (uri.startsWith('//')) {
      uri = 'https:' + uri
    } else if (!/^https?:\/\//.test(uri)) {
      uri = 'https://' + uri
    }

    const url = new URL(uri)
    let hostname = url.hostname.match(/([^.]+\.[^.]+)$/)[1]

    if (!keepExtension) {
      hostname = hostname.replace(/\.[^.]+$/, '')
    }

    return hostname
  }
  window.addEventListener("DOMContentLoaded",function() {
    customElements.define('iframe-consent', IframeConsent);
  });

})(jQuery, Drupal, drupalSettings);
