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

  window.COMPONENTS = new Map()
  window.SCRIPTS = new Map()

  if (!session.user) {
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
    window.COMPONENTS.set(componentKey, null)

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
        el.innerText = file.data.split('#{SELF}').join(`[data-base-component-key="${componentKey}"]`)
        file.element = el
      } else if (/application\/javascript/.test(contentType)) {
        /* eslint-disable-next-line no-eval */
        const script = eval(file.data)
        if (typeof script !== 'function') {
          throw new Error(`Script ${componentMeta[index]} is invalid`)
        }

        const key = `${componentKey}-${scriptsCounter++}`

        window.SCRIPTS.set(key, script)
        const el = document.createElement('script')
        el.innerText = `window.SCRIPTS.get('${key}').call(null, document.querySelector('[data-base-component-key="${componentKey}"]'))`
        file.element = el
      }
    })

    if (componentData.filter(data => Object.prototype.hasOwnProperty.call(data, 'html')).length !== 1) {
      throw new Error(`${isPage ? 'Page' : 'Component'} ${name} is invalid`)
    }

    const component = document.createElement('div')
    component.setAttribute('data-base-component-key', componentKey)
    component.setAttribute('class', `component component--${name}`)
    window.COMPONENTS.set(componentKey, component)

    componentData
      .forEach(file => {
        if (Object.prototype.hasOwnProperty.call(file, 'html')) {
          component.appendChild(file.html)
        } else {
          component.appendChild(file.element)
        }
      })

    component.querySelectorAll('div[data-base-component]')
      .forEach(
        component => parseComponent(component.getAttribute('data-base-component'))
          .then(({ component: rendered }) => component.parentNode.replaceChild(rendered, component))
      )

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
}
