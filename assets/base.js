window.onload = async function () {
  const randomString = () => (Date.now() + Math.random()).toString(16).replace('.', '')

  const session = JSON.parse(document.querySelector('meta[name="session"]').getAttribute('content'))
  const assets = JSON.parse(document.querySelector('meta[name="assets"]').getAttribute('content'))
  const app = document.getElementById('app')

  const componentsCache = new Map()

  window.COMPONENTS = new Map()
  window.SCRIPTS = new Map()

  if (!session.user) {
    const component1 = renderComponent('loginForm')
    app.appendChild(await component1)

    const component2 = renderComponent('loginForm')
    app.appendChild(await component2)

    const component3 = renderComponent('test')
    app.appendChild(await component3)

    const component4 = renderComponent('test')
    app.appendChild(await component4)
  }

  async function renderComponent (name) {
    if (!assets.components || !assets.components[name]) {
      throw new Error(`Component ${name} does not exist`)
    }

    const componentMeta = assets.components[name]

    const componentData = componentsCache.has(name)
      ? componentsCache
          .get(name)
          .map(({ headers, data }) => ({ headers, data }))
      : await Promise.all(
        componentMeta.map(meta => fetch(`assets/components/${meta}`))
      ).then(results => Promise.all(
        results.map(async result => ({
          headers: result.headers,
          data: await result.text()
        }))
      ))

    if (!componentsCache.has(name)) {
      componentsCache.set(
        name,
        componentData.map(({ headers, data }) => ({ headers, data }))
      )
    }

    let componentKey
    do {
      componentKey = randomString()
    } while (window.COMPONENTS.has(componentKey))
    window.COMPONENTS.set(componentKey, null)

    componentData.forEach((file, index) => {
      const contentType = file.headers.get('Content-Type')
      if (/text\/html/.test(contentType)) {
        const el = document.createElement('html')
        el.innerHTML = file.data

        const html = el.querySelector('div')
        if (!(html instanceof HTMLDivElement)) {
          throw new Error(`HTML ${componentMeta[index]} is invalid`)
        }

        html.className = 'component'
        file.html = html
      } else if (/text\/css/.test(contentType)) {
        const el = document.createElement('style')
        el.innerText = file.data.split('#{SELF}').join(`#${componentKey}`)
        file.element = el
      } else if (/application\/javascript/.test(contentType)) {
        /* eslint-disable-next-line no-eval */
        const script = eval(file.data)
        if (typeof script !== 'function') {
          throw new Error(`Script ${componentMeta[index]} is invalid`)
        }

        let key
        do {
          key = `${componentKey}-${randomString()}`
        } while (window.SCRIPTS.has(key))

        window.SCRIPTS.set(key, script)
        const el = document.createElement('script')
        el.innerText = `window.SCRIPTS.get('${key}').call(null, document.getElementById('${componentKey}'))`
        file.element = el
      }
    })

    if (componentData.filter(data => Object.prototype.hasOwnProperty.call(data, 'html')).length !== 1) {
      throw new Error(`Component ${name} is invalid`)
    }

    const component = document.createElement('div')
    component.setAttribute('id', componentKey)
    window.COMPONENTS.set(componentKey, component)

    componentData
      .forEach(file => {
        if (Object.prototype.hasOwnProperty.call(file, 'html')) {
          component.appendChild(file.html)
        } else {
          component.appendChild(file.element)
        }
      })

    return component
  }
}
