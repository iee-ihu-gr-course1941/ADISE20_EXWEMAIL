window.onload = async function () {
  const session = JSON.parse(document.querySelector('meta[name="session"]').getAttribute('content'))
  const assets = JSON.parse(document.querySelector('meta[name="assets"]').getAttribute('content'))
  const app = document.getElementById('app')

  const componentsAssets = new Map()

  Object.keys(assets.components)
    .filter(name => name.charAt(0) !== '_')
    .forEach(name => componentsAssets.set(`component-${name}`, assets.components[name].map(file => ({ name: file, url: assets.components._url + file }))))

  Object.keys(assets.pages)
    .filter(name => name.charAt(0) !== '_')
    .forEach(name => componentsAssets.set(`page-${name}`, assets.pages[name].map(file => ({ name: file, url: assets.pages._url + file }))))

  const componentsCache = new Map()

  let componentsCounter = 0
  let scriptsCounter = 0

  const globalComponents = new Map()
  const globalInjections = new Map()

  const commonGlobals = Object.freeze({
    get session () {
      return session
    },
    set session (update) {
      session[update.field] = update.value
    },
    renderPage
  })

  if (session.user) {
    renderPage('lobby')
  } else {
    renderPage('login')
  }

  function fetchComponent (name, isPage = false) {
    const _name = isPage ? `page-${name}` : `component-${name}`

    if (!componentsAssets.has(_name)) {
      throw new Error(`${isPage ? 'Page' : 'Component'} ${name} does not exist`)
    }

    const componentMeta = componentsAssets.get(_name)

    const componentDataPromise = componentsCache.has(_name)
      ? componentsCache
          .get(_name)
          .then(data => data.map(file => ({ ...file })))
      : Promise.all(
        componentMeta.map(async meta => ({ name: meta.name, data: await fetch(meta.url) }))
      ).then(results => Promise.all(
        results.map(async result => ({
          name: result.name,
          headers: result.data.headers,
          data: await result.data.text()
        }))
      ))

    if (!componentsCache.has(_name)) {
      componentsCache.set(
        _name,
        componentDataPromise.then(data => data.map(file => ({ ...file })))
      )
    }

    return componentDataPromise
  }

  async function parseComponent (name, isPage = false) {
    const _name = isPage ? `page-${name}` : `component-${name}`

    const componentMeta = componentsAssets.get(_name)

    const componentKey = `${_name}--${componentsCounter++}`
    globalComponents.set(componentKey, null)
    globalInjections.set(componentKey, { globals: commonGlobals })

    const componentData = await fetchComponent(name, isPage)

    componentData.forEach((file, index) => {
      const contentType = file.headers.get('Content-Type')
      if (/text\/html/.test(contentType)) {
        const el = document.createElement('html')
        el.innerHTML = file.data

        const html = el.querySelector('div')
        if (!(html instanceof HTMLDivElement)) {
          throw new Error(`HTML ${componentMeta[index]} is invalid`)
        }

        html.className = isPage ? 'page-body' : 'component-body'
        file.html = html
      } else if (/text\/css/.test(contentType)) {
        const el = document.createElement('style')
        el.innerText = file.data.split('#SELF').join(`[data-base-component-key="${componentKey}"]`)
        file.element = el
      } else if (/application\/javascript/.test(contentType)) {
        /* eslint-disable-next-line no-eval */
        const script = eval(file.data)
        if (typeof script !== 'function') {
          throw new Error(`Script ${componentMeta[index]} is invalid`)
        }

        const key = `${componentKey}-${scriptsCounter++}`

        document.addEventListener(key, async function handler () {
          document.removeEventListener(key, handler)
          await Promise.resolve() // Make sure page is rendered before executing
          const self = document.querySelector(`[data-base-component-key="${componentKey}"]`)
          self.removeChild(document.getElementById(key))
          return script({ self, ...globalInjections.get(componentKey) })
        })

        const el = document.createElement('script')
        el.setAttribute('id', key)
        el.innerText = `document.dispatchEvent(new Event('${key}'))`
        file.element = el
      }
    })

    if (componentData.filter(data => Object.prototype.hasOwnProperty.call(data, 'html')).length !== 1) {
      throw new Error(`${isPage ? 'Page' : 'Component'} ${name} is invalid`)
    }

    const component = document.createElement('div')
    component.setAttribute('data-base-component-key', componentKey)
    component.setAttribute('class', `component component--${name}`)
    globalComponents.set(componentKey, component)

    componentData
      .forEach(file => {
        if (Object.prototype.hasOwnProperty.call(file, 'html')) {
          component.appendChild(file.html)
        } else {
          component.appendChild(file.element)
        }
      })

    component.querySelectorAll('div[data-base-component]')
      .forEach(async nested => {
        const { component: rendered, componentKey: renderedKey } = await parseComponent(nested.getAttribute('data-base-component'))

        const re = /^data-base-event-([a-z-]+[a-z])$/
        const events = [...nested.attributes]
          .map(({ name }) => name)
          .filter(name => re.test(name))

        events.forEach(attr => {
          const eventNamePascal = _dashSplitToPascal(re.exec(attr)[1])
          const localNamePascal = _dashSplitToPascal(nested.getAttribute(attr))
          const localName = 'on' + localNamePascal

          const renderedInjections = globalInjections.get(renderedKey)
          if (!Object.prototype.hasOwnProperty.call(renderedInjections, 'events')) {
            renderedInjections.events = {}
          }

          renderedInjections.events['dispatch' + eventNamePascal] = (detail) => {
            component.dispatchEvent(
              new CustomEvent(localName, { detail })
            )
          }
        })

        nested.parentNode.replaceChild(rendered, nested)
      })

    component.querySelectorAll('[data-base-content]')
      .forEach(content => {
        const name = _dashSplitToPascal(content.getAttribute('data-base-content'))

        const injections = {
          ...globalInjections.get(componentKey),
          [`set${name}`]: (data) => { content.innerText = data }
        }

        globalInjections.set(componentKey, injections)
      })

    component.querySelectorAll('input[data-base-model]')
      .forEach(model => {
        const attr = model.getAttribute('data-base-model')
        const name = 'model' + _dashSplitToPascal(attr)
        const injections = globalInjections.get(componentKey)

        if (Object.prototype.hasOwnProperty.call(injections, name)) {
          throw new Error(`Duplicate model '${attr}'`)
        }

        globalInjections.set(componentKey, {
          ...injections,
          [name]: (cb) => {
            const handler = (event) => cb(event.target ? event.target.value : event)
            model.oninput = handler
          }
        })
      })

    return { component, componentKey }
  }

  async function renderPage (name) {
    const { component, componentKey } = await parseComponent(name, true)

    while (app.firstChild) {
      app.firstChild.remove()
    }

    app.append(...component.children)
    app.setAttribute('data-base-current-page', name)
    app.setAttribute('data-base-component-key', componentKey)
  }

  // Helper functions
  function _dashSplitToPascal (str) {
    return str.toLowerCase()
      .split('-')
      .map(part => part.charAt(0).toUpperCase() + part.slice(1))
      .join('')
  }
}
